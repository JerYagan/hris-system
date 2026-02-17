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

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('cleanText')) {
    function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string)$value);
        return $text === '' ? null : $text;
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
    function isValidCsrfToken(?string $token): bool
    {
        if ($token === null || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('redirectWithState')) {
    function redirectWithState(string $state, string $message, ?string $path = null): never
    {
        $targetPath = cleanText($path);
        if ($targetPath === null) {
            $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            $targetPath = basename($requestPath !== '' ? $requestPath : 'dashboard.php');
        }

        if ($targetPath === '') {
            $targetPath = 'dashboard.php';
        }

        $query = http_build_query([
            'state' => $state,
            'message' => $message,
        ]);

        header('Location: ' . $targetPath . '?' . $query);
        exit;
    }
}

if (!function_exists('apiRequest')) {
    function apiRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $attempts = strtoupper($method) === 'GET' ? 2 : 1;
        $attempt = 0;
        $last = [
            'status' => 0,
            'data' => [],
            'raw' => '',
            'error' => null,
        ];

        while ($attempt < $attempts) {
            $attempt++;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $raw = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                $decoded = [];
            }

            $last = [
                'status' => $status,
                'data' => $decoded,
                'raw' => (string)$raw,
                'error' => $error !== '' ? $error : null,
            ];

            $isTransientFailure = $status === 0 || $status >= 500;
            if ($attempt < $attempts && $isTransientFailure) {
                usleep(random_int(80, 160) * 1000);
                continue;
            }

            break;
        }

        return $last;
    }
}

if (!function_exists('isSuccessful')) {
    function isSuccessful(array $response): bool
    {
        $status = (int)($response['status'] ?? 0);
        return $status >= 200 && $status < 300;
    }
}

if (!function_exists('normalizeRelativeStoragePath')) {
    function normalizeRelativeStoragePath(?string $storagePath): ?string
    {
        $value = cleanText($storagePath);
        if ($value === null) {
            return null;
        }

        $value = str_replace('\\', '/', $value);
        $value = preg_replace('#/+#', '/', $value) ?? $value;
        $value = ltrim($value, '/');

        if ($value === '' || str_contains($value, "\0") || str_contains($value, '..')) {
            return null;
        }

        if (!preg_match('#^[a-zA-Z0-9_./\-]+$#', $value)) {
            return null;
        }

        return $value;
    }
}

if (!function_exists('resolveStorageFilePath')) {
    function resolveStorageFilePath(string $storageRoot, ?string $storagePath): ?array
    {
        $relativePath = normalizeRelativeStoragePath($storagePath);
        if ($relativePath === null) {
            return null;
        }

        $rootReal = realpath($storageRoot);
        if ($rootReal === false || !is_dir($rootReal)) {
            return null;
        }

        $absolutePath = $rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $fileReal = realpath($absolutePath);
        if ($fileReal === false || !is_file($fileReal)) {
            return null;
        }

        $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($fileReal, $rootPrefix)) {
            return null;
        }

        return [
            'root' => $rootReal,
            'relative_path' => str_replace('\\', '/', $relativePath),
            'absolute_path' => $fileReal,
        ];
    }
}
