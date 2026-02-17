<?php

require_once __DIR__ . '/includes/lib/employee-backend.php';

$backend = employeeBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$employeeUserId = (string)($backend['employee_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    http_response_code(500);
    exit('Missing backend configuration.');
}

$employeeContext = resolveEmployeeIdentityContext($supabaseUrl, $headers, $employeeUserId);
if (!(bool)($employeeContext['is_valid'] ?? false)) {
    renderEmployeeContextErrorAndExit((string)($employeeContext['error'] ?? 'Employee context could not be resolved.'));
}

$employeePersonId = cleanText($employeeContext['person_id'] ?? null) ?? '';
$payslipId = cleanText($_GET['payslip_id'] ?? null) ?? '';

if (!isValidUuid($payslipId) || !isValidUuid($employeePersonId)) {
    http_response_code(400);
    exit('Invalid payslip request.');
}

$payslipResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,pdf_storage_path'
    . '&id=eq.' . rawurlencode($payslipId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($payslipResponse) || empty((array)($payslipResponse['data'] ?? []))) {
    http_response_code(404);
    exit('Payslip not found.');
}

$payslipRow = (array)$payslipResponse['data'][0];
$payrollItemId = cleanText($payslipRow['payroll_item_id'] ?? null) ?? '';
$pdfStoragePath = cleanText($payslipRow['pdf_storage_path'] ?? null) ?? '';

if (!isValidUuid($payrollItemId) || $pdfStoragePath === '') {
    http_response_code(404);
    exit('Payslip file is not available.');
}

$ownershipResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_items?select=id,person_id'
    . '&id=eq.' . rawurlencode($payrollItemId)
    . '&person_id=eq.' . rawurlencode($employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($ownershipResponse) || empty((array)($ownershipResponse['data'] ?? []))) {
    http_response_code(403);
    exit('You are not allowed to access this payslip.');
}

$resolvedPath = $pdfStoragePath;
if (str_starts_with($resolvedPath, '/hris-system/storage/payslips/')) {
    $resolvedPath = substr($resolvedPath, strlen('/hris-system/storage/payslips/'));
}

$resolvedFile = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/payslips', $resolvedPath);
if ($resolvedFile === null) {
    http_response_code(404);
    exit('Payslip file not found.');
}

$absolutePath = (string)$resolvedFile['absolute_path'];

$mimeType = (string)(mime_content_type($absolutePath) ?: 'application/pdf');
$fileName = basename($absolutePath);

apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/payslips?id=eq.' . rawurlencode($payslipId),
    $headers,
    ['viewed_at' => gmdate('c')]
);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');

readfile($absolutePath);
exit;
