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
$errorMessage = '';
$streamPreview = (string)(cleanText($_GET['stream'] ?? null) ?? '') === '1';

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

if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
    $errorMessage = 'Document not found.';
} else {
    $documentRow = (array)$documentResponse['data'][0];
    $title = cleanText($documentRow['title'] ?? null) ?? 'Document Preview';
    $storageBucket = cleanText($documentRow['storage_bucket'] ?? null) ?? '';
    $storagePath = cleanText($documentRow['storage_path'] ?? null) ?? '';
    $resolvedFile = resolveStorageFilePath(dirname(__DIR__, 2) . '/storage/document', $storagePath);
    $absolutePath = is_array($resolvedFile) ? (string)($resolvedFile['absolute_path'] ?? '') : '';
    $mimeType = '';
    $remotePayload = null;

    $fileName = basename($storagePath !== '' ? $storagePath : $title);

    if ($absolutePath !== '' && is_file($absolutePath)) {
        $mimeType = (string)(mime_content_type($absolutePath) ?: 'application/octet-stream');
        $fileName = basename($absolutePath);

        if ($streamPreview) {
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
            header('Content-Length: ' . (string)filesize($absolutePath));
            header('X-Content-Type-Options: nosniff');
            readfile($absolutePath);
            exit;
        }
    } else {
        foreach ($resolveRemoteCandidates($storageBucket, $storagePath, $supabaseUrl) as $remoteUrl) {
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
            $fileName = basename($storagePath) ?: $title;

            if ($streamPreview) {
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
                header('Content-Length: ' . (string)strlen((string)$remotePayload['body']));
                header('X-Content-Type-Options: nosniff');
                echo (string)$remotePayload['body'];
                exit;
            }
        }
    }
}

$extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
$previewKind = 'unsupported';
if (in_array($extension, ['pdf'], true) || (isset($mimeType) && $mimeType === 'application/pdf')) {
    $previewKind = 'pdf';
} elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true) || (isset($mimeType) && str_starts_with((string)$mimeType, 'image/'))) {
    $previewKind = 'image';
}

$pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$errorMessageEscaped = htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8');
$returnToJson = json_encode($returnTo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$streamUrl = 'document-preview.php?document_id=' . rawurlencode($documentId) . '&stream=1';
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