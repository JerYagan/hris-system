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
$documentId = cleanText($_GET['document_id'] ?? null) ?? '';

if (!isValidUuid($documentId) || !isValidUuid($employeePersonId)) {
    http_response_code(400);
    exit('Invalid document request.');
}

$documentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,storage_path,owner_person_id'
    . '&id=eq.' . rawurlencode($documentId)
    . '&owner_person_id=eq.' . rawurlencode($employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
    http_response_code(404);
    exit('Document not found.');
}

$documentRow = (array)$documentResponse['data'][0];
$storagePath = cleanText($documentRow['storage_path'] ?? null);

if ($storagePath === null) {
    http_response_code(404);
    exit('Document storage path is missing.');
}

$resolvedFile = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/document', $storagePath);
if ($resolvedFile === null) {
    http_response_code(404);
    exit('Document file not found.');
}

$absolutePath = (string)$resolvedFile['absolute_path'];

$mimeType = (string)(mime_content_type($absolutePath) ?: 'application/octet-stream');
$fileName = basename($absolutePath);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/document_access_logs',
    $headers,
    [[
        'document_id' => $documentId,
        'viewer_user_id' => $employeeUserId,
        'access_type' => 'download',
    ]]
);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');

readfile($absolutePath);
exit;
