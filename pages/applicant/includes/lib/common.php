<?php

require_once __DIR__ . '/../../../shared/lib/system-helpers.php';

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $envPath): void
    {
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim((string)$value);
            $value = trim($value, "\"'");

            if ($key !== '') {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('cleanText')) {
    function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('isValidUuid')) {
    function isValidUuid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return (bool)preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            trim($value)
        );
    }
}

if (!function_exists('ensureCsrfToken')) {
    function ensureCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $token = $_SESSION['csrf_token'] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }
}

if (!function_exists('isValidCsrfToken')) {
    function isValidCsrfToken(?string $submittedToken): bool
    {
        if ($submittedToken === null || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('detectUploadedMimeType')) {
    function detectUploadedMimeType(string $tmpFilePath): ?string
    {
        if ($tmpFilePath === '' || !is_file($tmpFilePath)) {
            return null;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $tmpFilePath);
                finfo_close($finfo);

                if (is_string($mimeType) && trim($mimeType) !== '') {
                    return trim($mimeType);
                }
            }
        }

        return null;
    }
}

if (!function_exists('safeLocalLink')) {
    function safeLocalLink(?string $url): ?string
    {
        $candidate = cleanText($url);
        if ($candidate === null) {
            return null;
        }

        $lower = strtolower($candidate);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:') || str_starts_with($lower, '//')) {
            return null;
        }

        $parts = parse_url($candidate);
        if ($parts === false) {
            return null;
        }

        if (!empty($parts['scheme']) || !empty($parts['host'])) {
            return null;
        }

        return $candidate;
    }
}

if (!function_exists('redirectWithState')) {
    function redirectWithState(string $state, string $message, ?string $path = null): never
    {
        $targetPath = $path;
        if ($targetPath === null || trim($targetPath) === '') {
            $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            $targetPath = basename($requestPath !== '' ? $requestPath : '');
            if ($targetPath === '') {
                $targetPath = 'dashboard.php';
            }
        }

        $params = http_build_query([
            'state' => $state,
            'message' => $message,
        ]);

        header('Location: ' . $targetPath . '?' . $params);
        exit;
    }
}

if (!function_exists('apiRequest')) {
    function apiRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $responseBody = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status' => $statusCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
        ];
    }
}

if (!function_exists('isSuccessful')) {
    function isSuccessful(array $response): bool
    {
        return ($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300;
    }
}

if (!function_exists('normalizeUploadFilename')) {
    function normalizeUploadFilename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return 'file';
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'file';
        $filename = trim($filename, '-.');

        if ($filename === '') {
            return 'file';
        }

        return strtolower($filename);
    }
}

if (!function_exists('uploadFileToSupabaseStorage')) {
    function uploadFileToSupabaseStorage(
        string $supabaseUrl,
        string $serviceRoleKey,
        string $bucket,
        string $storagePath,
        string $tmpFilePath,
        string $contentType
    ): array {
        $uploadUrl = rtrim($supabaseUrl, '/')
            . '/storage/v1/object/'
            . rawurlencode($bucket)
            . '/'
            . str_replace('%2F', '/', rawurlencode(ltrim($storagePath, '/')));

        $payload = file_get_contents($tmpFilePath);
        if ($payload === false) {
            return [
                'status' => 0,
                'data' => [],
                'raw' => '',
                'error' => 'Unable to read uploaded temporary file.',
            ];
        }

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $serviceRoleKey,
            'Authorization: Bearer ' . $serviceRoleKey,
            'Content-Type: ' . ($contentType !== '' ? $contentType : 'application/octet-stream'),
            'x-upsert: false',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $responseBody = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status' => $statusCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
            'error' => $curlError !== '' ? $curlError : null,
        ];
    }
}

if (!function_exists('defaultRequiredDocumentConfig')) {
    function defaultRequiredDocumentConfig(): array
    {
        return [
            [
                'key' => 'application_letter',
                'label' => 'Application Letter',
                'document_type' => 'other',
                'required' => true,
            ],
            [
                'key' => 'pds_form_212',
                'label' => 'Personal Data Sheet (CSC Form 212)',
                'document_type' => 'pds',
                'required' => true,
            ],
            [
                'key' => 'resume',
                'label' => 'Updated Resume',
                'document_type' => 'resume',
                'required' => true,
            ],
            [
                'key' => 'transcript_diploma',
                'label' => 'Transcript of Records / Diploma',
                'document_type' => 'transcript',
                'required' => true,
            ],
            [
                'key' => 'government_id',
                'label' => 'Valid Government ID',
                'document_type' => 'id',
                'required' => true,
            ],
        ];
    }
}

if (!function_exists('normalizeRequiredDocumentConfig')) {
    function normalizeRequiredDocumentConfig(mixed $rawConfig): array
    {
        $default = defaultRequiredDocumentConfig();

        if (!is_array($rawConfig) || empty($rawConfig)) {
            return $default;
        }

        $normalized = [];
        $allowedDocumentTypes = ['resume', 'pds', 'transcript', 'certificate', 'id', 'other'];

        foreach ($rawConfig as $item) {
            if (is_string($item)) {
                $item = [
                    'key' => $item,
                    'label' => ucwords(str_replace(['_', '-'], ' ', $item)),
                    'document_type' => 'other',
                    'required' => true,
                ];
            }

            if (!is_array($item)) {
                continue;
            }

            $rawKey = cleanText($item['key'] ?? null);
            if ($rawKey === null) {
                continue;
            }

            $key = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $rawKey) ?? $rawKey);
            $key = trim($key, '_');
            if ($key === '') {
                continue;
            }

            $label = cleanText($item['label'] ?? null) ?? ucwords(str_replace('_', ' ', $key));
            $documentType = strtolower((string)(cleanText($item['document_type'] ?? null) ?? 'other'));
            if (!in_array($documentType, $allowedDocumentTypes, true)) {
                $documentType = 'other';
            }

            $required = array_key_exists('required', $item) ? (bool)$item['required'] : true;

            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'document_type' => $documentType,
                'required' => $required,
            ];
        }

        if (empty($normalized)) {
            return $default;
        }

        return $normalized;
    }
}
