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
    . '/rest/v1/documents?select=id,title,storage_bucket,storage_path,owner_person_id'
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
$storageBucket = cleanText($documentRow['storage_bucket'] ?? null) ?? '';
$storagePath = cleanText($documentRow['storage_path'] ?? null);

if ($storagePath === null) {
    http_response_code(404);
    exit('Document storage path is missing.');
}

$resolveRemoteCandidates = static function (string $bucket, string $path, string $supabaseUrl): array {
    $candidates = [];
    $normalizedPath = trim(str_replace('\\', '/', $path));
    if ($normalizedPath === '') {
        return $candidates;
    }

    if (preg_match('#^https?://#i', $normalizedPath) === 1) {
        $candidates[] = $normalizedPath;
        $parsedPath = parse_url($normalizedPath, PHP_URL_PATH);
        $normalizedPath = is_string($parsedPath) ? ltrim($parsedPath, '/') : '';
    }

    if ($normalizedPath !== '' && str_starts_with($normalizedPath, 'storage/v1/object/')) {
        $tail = substr($normalizedPath, strlen('storage/v1/object/'));
        $tail = ltrim((string)$tail, '/');
        if (str_starts_with($tail, 'public/')) {
            $tail = substr($tail, strlen('public/'));
        }
        $parts = explode('/', (string)$tail, 2);
        $bucket = trim((string)($parts[0] ?? ''));
        $normalizedPath = trim((string)($parts[1] ?? ''));
    }

    if ($bucket !== '' && $normalizedPath !== '') {
        $encodedPath = implode('/', array_map('rawurlencode', array_values(array_filter(explode('/', $normalizedPath), static fn(string $segment): bool => $segment !== ''))));
        $candidates[] = rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encodedPath;
        $candidates[] = rtrim($supabaseUrl, '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $encodedPath;
    }

    return array_values(array_unique(array_filter($candidates)));
};

$streamRemoteObject = static function (string $url, array $headers): ?array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream');
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $error !== '') {
        return null;
    }

    return [
        'status' => $status,
        'body' => (string)$body,
        'content_type' => $contentType,
    ];
};

$resolvedFile = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/document', $storagePath);
$absolutePath = is_array($resolvedFile) ? (string)$resolvedFile['absolute_path'] : '';
$mimeType = '';
$fileName = '';

if ($absolutePath !== '' && is_file($absolutePath)) {
    $mimeType = (string)(mime_content_type($absolutePath) ?: 'application/octet-stream');
    $fileName = basename($absolutePath);
} else {
    $remotePayload = null;
    foreach ($resolveRemoteCandidates($storageBucket, (string)$storagePath, $supabaseUrl) as $remoteUrl) {
        $remotePayload = $streamRemoteObject($remoteUrl, $headers);
        if (is_array($remotePayload) && (int)($remotePayload['status'] ?? 0) >= 200 && (int)($remotePayload['status'] ?? 0) < 300) {
            break;
        }
        $remotePayload = null;
    }

    if ($remotePayload === null) {
        http_response_code(404);
        exit('Document file not found.');
    }

    $mimeType = (string)($remotePayload['content_type'] ?? 'application/octet-stream');
    $fileName = basename((string)$storagePath) ?: ((string)($documentRow['title'] ?? 'document'));

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
    header('Content-Length: ' . strlen((string)$remotePayload['body']));
    header('X-Content-Type-Options: nosniff');

    echo (string)$remotePayload['body'];
    exit;
}

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
