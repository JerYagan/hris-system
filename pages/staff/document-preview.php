<?php
require_once __DIR__ . '/includes/lib/staff-backend.php';

$backend = staffBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$staffUserId = (string)($backend['staff_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    http_response_code(500);
    exit('Missing backend configuration.');
}

$staffContext = resolveStaffIdentityContext($supabaseUrl, $headers, $staffUserId);
if (!(bool)($staffContext['is_valid'] ?? false)) {
    renderStaffContextErrorAndExit((string)($staffContext['error'] ?? 'Staff context could not be resolved.'));
}

$source = strtolower((string)(cleanText($_GET['source'] ?? null) ?? ''));
$documentId = cleanText($_GET['document_id'] ?? null) ?? '';
$embedded = (string)(cleanText($_GET['embedded'] ?? null) ?? '') === '1';

if (!in_array($source, ['employee', 'applicant'], true) || !isValidUuid($documentId)) {
    http_response_code(400);
    exit('Invalid document preview request.');
}

$title = 'Document Preview';
$fileName = 'document';
$previewUrl = '';
$downloadUrl = '';
$mimeType = '';
$errorMessage = '';

$resolveEmployeeDocumentUrl = static function (?string $bucket, ?string $path) use ($supabaseUrl): string {
    $bucketValue = strtolower(trim((string)$bucket));
    $pathValue = trim((string)$path);
    if ($pathValue === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $pathValue) === 1 || str_starts_with($pathValue, '/')) {
        return $pathValue;
    }

    $localResolved = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/document', $pathValue);
    if (is_array($localResolved) && !empty($localResolved['relative_path'])) {
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', explode('/', (string)$localResolved['relative_path'])));
    }

    if (str_contains($pathValue, '/storage/v1/object/public/')) {
        return $pathValue;
    }

    if ($bucketValue === '' || in_array($bucketValue, ['local_documents', 'local', 'filesystem'], true)) {
        $normalizedPath = preg_replace('#^document/#i', '', $pathValue);
        $segments = array_values(array_filter(explode('/', (string)$normalizedPath), static fn(string $segment): bool => $segment !== ''));
        return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
    }

    $segments = array_values(array_filter(explode('/', $pathValue), static fn(string $segment): bool => $segment !== ''));
    return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucketValue) . '/' . implode('/', array_map('rawurlencode', $segments));
};

if ($source === 'employee') {
    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/documents?select=id,title,storage_bucket,storage_path'
        . '&id=eq.' . rawurlencode($documentId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
        $errorMessage = 'Document not found.';
    } else {
        $documentRow = (array)$documentResponse['data'][0];
        $title = cleanText($documentRow['title'] ?? null) ?? 'Employee Document';
        $fileName = basename((string)(cleanText($documentRow['storage_path'] ?? null) ?? $title));
        $previewUrl = $resolveEmployeeDocumentUrl(cleanText($documentRow['storage_bucket'] ?? null), cleanText($documentRow['storage_path'] ?? null));
        $downloadUrl = $previewUrl;
    }
} else {
    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_documents?select=id,file_name,mime_type'
        . '&id=eq.' . rawurlencode($documentId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
        $errorMessage = 'Applicant document not found.';
    } else {
        $documentRow = (array)$documentResponse['data'][0];
        $fileName = cleanText($documentRow['file_name'] ?? null) ?? 'Applicant Document';
        $title = $fileName;
        $mimeType = strtolower((string)(cleanText($documentRow['mime_type'] ?? null) ?? ''));
        $previewUrl = '/hris-system/pages/staff/applicant-document.php?document_id=' . rawurlencode($documentId);
        $downloadUrl = $previewUrl . '&download=1';
    }
}

$extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
$previewKind = 'unsupported';
if (in_array($extension, ['pdf'], true) || $mimeType === 'application/pdf') {
    $previewKind = 'pdf';
} elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true) || str_starts_with($mimeType, 'image/')) {
    $previewKind = 'image';
}

$pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$previewUrlEscaped = htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8');
$errorMessageEscaped = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');


if ($embedded) {
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <style>
        html,body{margin:0;height:100%;font-family:Arial,sans-serif;background:#fff;color:#0f172a}
        .frame{width:100%;height:100vh;border:0;background:#fff}
        .image-wrap{display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f8fafc;padding:16px;box-sizing:border-box}
        .image-wrap img{max-width:100%;max-height:calc(100vh - 32px);object-fit:contain;border-radius:8px}
        .message{max-width:720px;margin:32px auto;padding:24px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;text-align:center}
        .message h2{margin:0 0 10px;font-size:22px}
        .message p{margin:0 0 12px;color:#475569;line-height:1.5}
        .error{border-color:#fecaca;background:#fff1f2}
    </style>
</head>
<body>
    <?php if ($errorMessage !== ''): ?>
        <div class="message error">
            <h2>Unable to load preview</h2>
            <p><?= $errorMessageEscaped ?></p>
        </div>
    <?php elseif ($previewUrl === ''): ?>
        <div class="message error">
            <h2>Preview unavailable</h2>
            <p>The document preview could not be prepared.</p>
        </div>
    <?php elseif ($previewKind === 'pdf'): ?>
        <iframe class="frame" src="<?= $previewUrlEscaped ?>" title="<?= $pageTitle ?>"></iframe>
    <?php elseif ($previewKind === 'image'): ?>
        <div class="image-wrap">
            <img src="<?= $previewUrlEscaped ?>" alt="<?= $pageTitle ?>">
        </div>
    <?php else: ?>
        <div class="message">
            <h2>Preview not supported</h2>
            <p>File type not supported for preview. Please download the file to view its contents.</p>
        </div>
    <?php endif; ?>
</body>
</html>
<?php
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>
    <style>
        html,body{margin:0;height:100%;font-family:Arial,sans-serif;background:#fff;color:#0f172a}
        .viewer{width:100vw;height:100vh;background:#fff}
        .frame{width:100%;height:100%;border:0;background:#fff}
        .image-wrap{display:flex;justify-content:center;align-items:center;width:100%;height:100%;background:#f8fafc}
        .image-wrap img{max-width:100%;max-height:100%;object-fit:contain}
        .message-wrap{display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:24px;box-sizing:border-box;background:#f8fafc}
        .message{max-width:720px;width:100%;padding:24px;border:1px dashed #cbd5e1;border-radius:14px;background:#fff;text-align:center;box-sizing:border-box}
        .message h2{margin:0 0 10px;font-size:22px}
        .message p{margin:0 0 12px;color:#475569;line-height:1.5}
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
                </div>
            </div>
        <?php elseif ($previewUrl === ''): ?>
            <div class="message-wrap">
                <div class="message error">
                    <h2>Preview unavailable</h2>
                    <p>The document preview could not be prepared.</p>
                </div>
            </div>
        <?php elseif ($previewKind === 'pdf'): ?>
            <iframe class="frame" src="<?= $previewUrlEscaped ?>" title="<?= $pageTitle ?>"></iframe>
        <?php elseif ($previewKind === 'image'): ?>
            <div class="image-wrap">
                <img src="<?= $previewUrlEscaped ?>" alt="<?= $pageTitle ?>">
            </div>
        <?php else: ?>
            <div class="message-wrap">
                <div class="message">
                    <h2>Preview not supported</h2>
                    <p>File type not supported for preview. Please download the file to view its contents.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
