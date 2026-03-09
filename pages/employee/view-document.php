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
$employeeContextResolved = (bool)($employeeContext['is_valid'] ?? false);
if (!$employeeContextResolved) {
    renderEmployeeContextErrorAndExit((string)($employeeContext['error'] ?? 'Employee context could not be resolved.'));
}

$employeePersonId = cleanText($employeeContext['person_id'] ?? null) ?? '';
$documentId = cleanText($_GET['document_id'] ?? null) ?? '';
$streamPreview = (string)(cleanText($_GET['stream'] ?? null) ?? '') === '1';

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
$title = cleanText($documentRow['title'] ?? null) ?? 'document';

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
$errorMessage = '';

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
        $errorMessage = 'Document file not found.';
    } else {
        $mimeType = (string)($remotePayload['content_type'] ?? 'application/octet-stream');
        $fileName = basename((string)$storagePath) ?: $title;

        if ($streamPreview) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/document_access_logs',
                $headers,
                [[
                    'document_id' => $documentId,
                    'viewer_user_id' => $employeeUserId,
                    'access_type' => 'view',
                ]]
            );

            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
            header('Content-Length: ' . strlen((string)$remotePayload['body']));
            header('X-Content-Type-Options: nosniff');

            echo (string)$remotePayload['body'];
            exit;
        }
    }
}

$extension = strtolower((string)pathinfo($fileName !== '' ? $fileName : (string)$storagePath, PATHINFO_EXTENSION));
$previewKind = 'unsupported';
if (in_array($extension, ['pdf'], true) || $mimeType === 'application/pdf') {
    $previewKind = 'pdf';
} elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true) || str_starts_with($mimeType, 'image/')) {
    $previewKind = 'image';
}

if ($streamPreview) {
    if ($absolutePath === '' || !is_file($absolutePath)) {
        http_response_code(404);
        exit($errorMessage !== '' ? $errorMessage : 'Document file not found.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/document_access_logs',
        $headers,
        [[
            'document_id' => $documentId,
            'viewer_user_id' => $employeeUserId,
            'access_type' => 'view',
        ]]
    );

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
    header('Content-Length: ' . filesize($absolutePath));
    header('X-Content-Type-Options: nosniff');

    readfile($absolutePath);
    exit;
}

$pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$errorMessageEscaped = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
$downloadUrl = 'download-document.php?document_id=' . rawurlencode($documentId);
$streamUrl = 'view-document.php?document_id=' . rawurlencode($documentId) . '&stream=1';
$downloadUrlEscaped = htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8');
$streamUrlEscaped = htmlspecialchars($streamUrl, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <style>
        html,body{margin:0;height:100%;background:#fff;color:#0f172a;font-family:Arial,sans-serif}
        .viewer{width:100vw;height:100vh;background:#fff}
        .frame{width:100%;height:100%;border:0;background:#fff}
        .image-wrap{display:flex;justify-content:center;align-items:center;width:100%;height:100%;background:#f8fafc}
        .image-wrap img{max-width:100%;max-height:100%;object-fit:contain}
        .message-wrap{display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:24px;box-sizing:border-box;background:#f8fafc}
        .message{max-width:720px;width:100%;padding:24px;border:1px dashed #cbd5e1;border-radius:14px;background:#fff;text-align:center;box-sizing:border-box}
        .message h2{margin:0 0 10px;font-size:22px}
        .message p{margin:0;color:#475569;line-height:1.5}
        .message-actions{margin-top:18px;display:flex;justify-content:center;gap:12px;flex-wrap:wrap}
        .button{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600}
        .button-primary{background:#166534;color:#fff}
        .button-secondary{border:1px solid #cbd5e1;color:#0f172a;background:#fff}
        .error{border-color:#fecaca;background:#fff1f2}
    </style>
</head>
<body>
    <div class="viewer">
        <?php if ($errorMessage !== ''): ?>
            <div class="message-wrap">
                <div class="message error">
                    <h2>Unable to load preview</h2>
                    <p><?= $errorMessageEscaped ?></p>
                    <div class="message-actions">
                        <a class="button button-secondary" href="<?= $downloadUrlEscaped ?>">Download File</a>
                    </div>
                </div>
            </div>
        <?php elseif ($previewKind === 'pdf'): ?>
            <iframe class="frame" src="<?= $streamUrlEscaped ?>" title="<?= $pageTitle ?>"></iframe>
        <?php elseif ($previewKind === 'image'): ?>
            <div class="image-wrap">
                <img src="<?= $streamUrlEscaped ?>" alt="<?= $pageTitle ?>">
            </div>
        <?php else: ?>
            <div class="message-wrap">
                <div class="message">
                    <h2>Preview not supported</h2>
                    <p>File type not supported for preview. Please download the file to view its contents.</p>
                    <div class="message-actions">
                        <a class="button button-primary" href="<?= $downloadUrlEscaped ?>">Download File</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
exit;
