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

if (!function_exists('splitFullName')) {
    function splitFullName(string $fullName): array
    {
        $name = preg_replace('/\s+/', ' ', trim($fullName));
        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }

        $surname = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName, $surname];
    }
}

if (!function_exists('clientIp')) {
    function clientIp(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            $raw = $_SERVER[$key] ?? '';
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $segments = explode(',', $raw);
                return trim($segments[0]);
            }

            return trim($raw);
        }

        return 'unknown';
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

if (!function_exists('encodeFilter')) {
    function encodeFilter(string $value): string
    {
        return str_replace('%40', '@', rawurlencode($value));
    }
}

if (!function_exists('toStatusPill')) {
    function toStatusPill(string $status): array
    {
        $key = strtolower(trim($status));

        if ($key === 'active') {
            return ['Active', 'bg-emerald-100 text-emerald-800'];
        }
        if ($key === 'suspended') {
            return ['Suspended', 'bg-amber-100 text-amber-800'];
        }
        if ($key === 'disabled') {
            return ['Disabled', 'bg-rose-100 text-rose-800'];
        }
        if ($key === 'archived') {
            return ['Archived', 'bg-slate-200 text-slate-700'];
        }

        return [ucfirst($key !== '' ? $key : 'pending'), 'bg-blue-100 text-blue-800'];
    }
}
