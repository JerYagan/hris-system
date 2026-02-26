<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';

$documentId = cleanText($_GET['document_id'] ?? null) ?? '';
$download = (string)(cleanText($_GET['download'] ?? null) ?? '') === '1';

$isUuid = static function (?string $value): bool {
    $candidate = strtolower(trim((string)$value));
    return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $candidate);
};

$fail = static function (string $message): never {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
};

if (!$isUuid($documentId)) {
    $fail('Invalid document reference.');
}

$response = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/application_documents?select=id,file_url,file_name,mime_type'
    . '&id=eq.' . rawurlencode($documentId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($response) || empty($response['data'][0])) {
    $fail('Document not found.');
}

$row = (array)$response['data'][0];
$rawUrl = trim((string)($row['file_url'] ?? ''));
$fileName = trim((string)($row['file_name'] ?? 'document'));
$mimeType = trim((string)($row['mime_type'] ?? 'application/octet-stream'));

$localDocumentRoot = realpath(__DIR__ . '/../../storage/document') ?: (__DIR__ . '/../../storage/document');

$safeJoinPath = static function (string $root, array $segments): string {
    $safe = [];
    foreach ($segments as $segment) {
        $decoded = trim((string)rawurldecode($segment));
        if ($decoded === '' || $decoded === '.' || $decoded === '..') {
            continue;
        }
        $safe[] = $decoded;
    }
    return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safe);
};

$candidatePaths = [];
$normalized = str_replace('\\', '/', $rawUrl);
$parsedPath = parse_url($normalized, PHP_URL_PATH);
$pathForExtraction = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : $normalized;
$pathForExtraction = ltrim((string)$pathForExtraction, '/');

$pathForExtraction = preg_replace('#^/?storage/v1/object/(?:public/)?[^/]+/#i', '', $pathForExtraction);
$pathForExtraction = preg_replace('#^document/#i', '', $pathForExtraction);
$pathForExtraction = preg_replace('#^storage/document/#i', '', $pathForExtraction);

$segments = array_values(array_filter(explode('/', (string)$pathForExtraction), static fn(string $segment): bool => trim($segment) !== ''));
if (!empty($segments)) {
    $candidatePaths[] = $safeJoinPath($localDocumentRoot, $segments);
}

$basename = $fileName !== '' ? basename($fileName) : basename((string)$rawUrl);
if ($basename !== '') {
    $candidatePaths[] = rtrim($localDocumentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
}

$localPath = '';
foreach ($candidatePaths as $candidatePath) {
    if (is_file($candidatePath)) {
        $localPath = $candidatePath;
        break;
    }
}

if ($localPath !== '') {
    $size = filesize($localPath);
    header('Content-Type: ' . ($mimeType !== '' ? $mimeType : 'application/octet-stream'));
    header('Content-Length: ' . (is_int($size) ? (string)$size : '0'));
    $disposition = $download ? 'attachment' : 'inline';
    header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $basename !== '' ? $basename : 'document') . '"');
    readfile($localPath);
    exit;
}

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

$extractStorageObjectParts = static function (string $value): ?array {
    $normalized = str_replace('\\', '/', trim($value));
    if ($normalized === '') {
        return null;
    }

    $path = $normalized;
    if (preg_match('#^https?://#i', $normalized) === 1) {
        $parsedPath = parse_url($normalized, PHP_URL_PATH);
        $path = is_string($parsedPath) ? $parsedPath : '';
    }

    $path = ltrim((string)$path, '/');
    if (!str_starts_with($path, 'storage/v1/object/')) {
        return null;
    }

    $tail = substr($path, strlen('storage/v1/object/'));
    $tail = ltrim((string)$tail, '/');

    $isPublic = false;
    if (str_starts_with($tail, 'public/')) {
        $isPublic = true;
        $tail = substr($tail, strlen('public/'));
    }

    $parts = explode('/', (string)$tail, 2);
    $bucket = trim((string)($parts[0] ?? ''));
    $objectPath = trim((string)($parts[1] ?? ''));
    if ($bucket === '' || $objectPath === '') {
        return null;
    }

    return [
        'bucket' => $bucket,
        'object_path' => $objectPath,
        'is_public_variant' => $isPublic,
    ];
};

if ($rawUrl === '') {
    $fail('Document file URL is missing.');
}

$storageParts = $extractStorageObjectParts($rawUrl);
if ($storageParts !== null) {
    $bucket = (string)$storageParts['bucket'];
    $objectPath = (string)$storageParts['object_path'];
    $encodedObjectPath = implode('/', array_map('rawurlencode', array_values(array_filter(explode('/', $objectPath), static fn(string $segment): bool => $segment !== ''))));

    $remoteCandidates = [];
    $remoteCandidates[] = rtrim((string)$supabaseUrl, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encodedObjectPath;
    $remoteCandidates[] = rtrim((string)$supabaseUrl, '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $encodedObjectPath;
    if (preg_match('#^https?://#i', $rawUrl) === 1) {
        $remoteCandidates[] = $rawUrl;
    }

    foreach ($remoteCandidates as $remoteUrl) {
        $remote = $streamRemoteObject($remoteUrl, $headers);
        if (!is_array($remote)) {
            continue;
        }

        $status = (int)($remote['status'] ?? 0);
        if ($status >= 200 && $status < 300) {
            $payload = (string)($remote['body'] ?? '');
            header('Content-Type: ' . ((string)($remote['content_type'] ?? '') !== '' ? (string)$remote['content_type'] : ($mimeType !== '' ? $mimeType : 'application/octet-stream')));
            header('Content-Length: ' . (string)strlen($payload));
            $disposition = $download ? 'attachment' : 'inline';
            header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $basename !== '' ? $basename : 'document') . '"');
            echo $payload;
            exit;
        }
    }
}

if (str_starts_with($rawUrl, 'document/')) {
    $localRedirect = '/hris-system/storage/' . ltrim($rawUrl, '/');
    header('Location: ' . $localRedirect, true, 302);
    exit;
}

if (str_starts_with($rawUrl, 'storage/document/')) {
    $localRedirect = '/hris-system/' . ltrim($rawUrl, '/');
    header('Location: ' . $localRedirect, true, 302);
    exit;
}

$fail('Document file is unavailable in local storage and remote object lookup failed.');
