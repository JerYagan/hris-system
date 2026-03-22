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

if (!function_exists('logApiRequestPerformance')) {
    function logApiRequestPerformance(string $method, string $url, int $statusCode, int $durationMs, mixed $data, ?string $error = null): void
    {
        systemQaPerfTrackApiRequest('admin', $method, $url, $statusCode, $durationMs, $data, $error);

        $enabledRaw = strtolower(trim((string)($_ENV['HRIS_PERF_LOGGING'] ?? $_SERVER['HRIS_PERF_LOGGING'] ?? '1')));
        if (in_array($enabledRaw, ['0', 'false', 'off', 'no'], true)) {
            return;
        }

        $thresholdMs = (int)($_ENV['HRIS_PERF_SLOW_MS'] ?? $_SERVER['HRIS_PERF_SLOW_MS'] ?? 300);
        if ($thresholdMs < 0) {
            $thresholdMs = 300;
        }

        if ($durationMs < $thresholdMs) {
            return;
        }

        $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $endpointPath = (string)parse_url($url, PHP_URL_PATH);
        $rowCount = is_array($data) ? count($data) : 0;

        error_log(sprintf(
            '[HRIS][PERF][admin] page=%s method=%s endpoint=%s status=%d duration_ms=%d rows=%d error=%s',
            $requestPath !== '' ? $requestPath : 'unknown',
            strtoupper($method),
            $endpointPath !== '' ? $endpointPath : $url,
            $statusCode,
            $durationMs,
            $rowCount,
            $error !== null && $error !== '' ? $error : '-'
        ));
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

        $startedAt = microtime(true);
        $responseBody = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        logApiRequestPerformance($method, $url, $statusCode, $durationMs, $decoded, $error !== '' ? $error : null);

        return [
            'status' => $statusCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
            'error' => $error !== '' ? $error : null,
            'duration_ms' => $durationMs,
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
        $key = normalizeWorkflowStatus($status);

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

if (!function_exists('normalizeWorkflowStatus')) {
    function normalizeWorkflowStatus(?string $status): string
    {
        $value = strtolower(trim((string)$status));
        $value = str_replace([' ', '-'], '_', $value);

        return match ($value) {
            'inreview' => 'in_review',
            'needsrevision' => 'needs_revision',
            'cancel', 'canceled' => 'cancelled',
            default => $value !== '' ? $value : 'pending',
        };
    }
}

if (!function_exists('logStatusTransition')) {
    function logStatusTransition(
        string $supabaseUrl,
        array $headers,
        ?string $actorUserId,
        string $moduleName,
        string $entityName,
        ?string $entityId,
        string $actionName,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason = null,
        array $context = []
    ): void {
        $normalizedFrom = normalizeWorkflowStatus($fromStatus);
        $normalizedTo = normalizeWorkflowStatus($toStatus);
        $payloadContext = [];

        foreach ($context as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
            $payloadContext[$key] = $value;
        }

        $newData = [
            'status' => $normalizedTo,
            'status_from' => $normalizedFrom,
            'status_to' => $normalizedTo,
            'status_reason' => cleanText($reason),
        ];

        if (!empty($payloadContext)) {
            $newData['status_context'] = $payloadContext;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => cleanText($actorUserId),
                'module_name' => $moduleName,
                'entity_name' => $entityName,
                'entity_id' => cleanText($entityId),
                'action_name' => $actionName,
                'old_data' => ['status' => $normalizedFrom],
                'new_data' => $newData,
                'ip_address' => clientIp(),
            ]]
        );
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
