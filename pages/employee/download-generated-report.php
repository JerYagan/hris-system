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

$reportId = cleanText($_GET['report_id'] ?? null) ?? '';
if (!isValidUuid($reportId) || !isValidUuid($employeeUserId)) {
    http_response_code(400);
    exit('Invalid generated report request.');
}

$reportResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/generated_reports?select=id,requested_by,report_type,file_format,status,storage_path,generated_at'
    . '&id=eq.' . rawurlencode($reportId)
    . '&requested_by=eq.' . rawurlencode($employeeUserId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($reportResponse) || empty((array)($reportResponse['data'] ?? []))) {
    http_response_code(404);
    exit('Generated report not found.');
}

$reportRow = (array)$reportResponse['data'][0];
$status = strtolower((string)($reportRow['status'] ?? 'queued'));
$storagePath = cleanText($reportRow['storage_path'] ?? null);

if ($status !== 'ready' || $storagePath === null) {
    http_response_code(409);
    exit('This report file is not ready for download yet.');
}

$relativeStoragePath = $storagePath;
if (str_starts_with($relativeStoragePath, 'storage/reports/')) {
    $relativeStoragePath = substr($relativeStoragePath, strlen('storage/reports/'));
}

$resolvedFile = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/reports', $relativeStoragePath);
if ($resolvedFile === null) {
    http_response_code(404);
    exit('Generated report file not found.');
}

$absolutePath = (string)$resolvedFile['absolute_path'];
$mimeType = (string)(mime_content_type($absolutePath) ?: 'application/octet-stream');
$extension = strtolower((string)pathinfo($absolutePath, PATHINFO_EXTENSION));
$reportType = strtolower((string)($reportRow['report_type'] ?? 'report'));
$downloadName = 'employee-' . preg_replace('/[^a-z0-9\-_]+/i', '-', $reportType) . '-report';
if (!is_string($downloadName) || trim($downloadName) === '') {
    $downloadName = 'employee-report';
}
$downloadName .= $extension !== '' ? ('.' . $extension) : '';

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'generated_reports',
        'entity_id' => $reportId,
        'action_name' => 'download_personal_report',
        'new_data' => [
            'report_type' => $reportType,
            'file_format' => strtolower((string)($reportRow['file_format'] ?? $extension)),
            'storage_path' => $storagePath,
        ],
    ]]
);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($absolutePath);
exit;