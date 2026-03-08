<?php
require_once __DIR__ . '/includes/lib/admin-backend.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    http_response_code(500);
    exit('Missing backend configuration.');
}

$documentId = cleanText($_GET['document_id'] ?? null) ?? '';
$returnTo = trim((string)(cleanText($_GET['return_to'] ?? null) ?? '/hris-system/pages/admin/document-management.php'));

if (!isValidUuid($documentId)) {
    http_response_code(400);
    exit('Invalid document preview request.');
}

$documentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,storage_bucket,storage_path'
    . '&id=eq.' . rawurlencode($documentId)
    . '&limit=1',
    $headers
);

$title = 'Document Preview';
$fileName = 'document';
$previewUrl = '';
$errorMessage = '';

$resolveDocumentUrl = static function (?string $bucket, ?string $path) use ($supabaseUrl): string {
    $bucketValue = strtolower(trim((string)$bucket));
    $pathValue = trim((string)$path);
    if ($pathValue === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $pathValue) === 1 || str_starts_with($pathValue, '/')) {
        return $pathValue;
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

if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
    $errorMessage = 'Document not found.';
} else {
    $documentRow = (array)$documentResponse['data'][0];
    $title = cleanText($documentRow['title'] ?? null) ?? 'Document Preview';
    $fileName = basename((string)(cleanText($documentRow['storage_path'] ?? null) ?? $title));
    $previewUrl = $resolveDocumentUrl(cleanText($documentRow['storage_bucket'] ?? null), cleanText($documentRow['storage_path'] ?? null));
}

$extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
$previewKind = 'unsupported';
if (in_array($extension, ['pdf'], true)) {
    $previewKind = 'pdf';
} elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
    $previewKind = 'image';
}

$pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$previewUrlEscaped = htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8');
$errorMessageEscaped = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
$returnToJson = json_encode($returnTo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    <script>
        (() => {
            const returnTo = <?= is_string($returnToJson) ? $returnToJson : '"/hris-system/pages/admin/document-management.php"' ?>;
            window.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                event.preventDefault();

                let closed = false;
                try {
                    window.close();
                    closed = window.closed === true;
                } catch (_error) {
                    closed = false;
                }

                if (closed) {
                    return;
                }

                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                if (typeof returnTo === 'string' && returnTo.trim() !== '') {
                    window.location.replace(returnTo);
                }
            });
        })();
    </script>
</body>
</html>