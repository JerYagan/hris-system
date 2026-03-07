<?php

if (!function_exists('authLoadEnvFileIfPresent')) {
    function authLoadEnvFileIfPresent(string $envPath): void
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
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

if (!function_exists('authEnvValue')) {
    function authEnvValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return (string)$value;
    }
}

if (!function_exists('authCleanText')) {
    function authCleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('authHttpJsonRequest')) {
    function authHttpJsonRequest(string $method, string $url, array $headers, $body = null): array
    {
        $normalizedHeaders = [];
        $hasContentType = false;

        foreach ($headers as $header) {
            $normalizedHeaders[] = $header;
            if (stripos((string)$header, 'Content-Type:') === 0) {
                $hasContentType = true;
            }
        }

        if ($body !== null && !$hasContentType) {
            $normalizedHeaders[] = 'Content-Type: application/json';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $normalizedHeaders,
            CURLOPT_TIMEOUT => 25,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status' => (int)$statusCode,
            'data' => $decoded,
            'raw' => $responseBody,
            'curl_error' => $curlError,
        ];
    }
}

if (!function_exists('apiRequest')) {
    function apiRequest(string $method, string $url, array $headers, $body = null): array
    {
        return authHttpJsonRequest($method, $url, $headers, $body);
    }
}

if (!function_exists('isSuccessful')) {
    function isSuccessful(array $response): bool
    {
        $status = (int)($response['status'] ?? 0);
        return $status >= 200 && $status < 300;
    }
}

if (!function_exists('authClientIp')) {
    function authClientIp(): ?string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if (!$ip) {
            return null;
        }

        $parts = explode(',', (string)$ip);
        return trim((string)($parts[0] ?? '')) ?: null;
    }
}

if (!function_exists('authValidateStrongPassword')) {
    function authValidateStrongPassword(string $password, string $label = 'Password'): ?string
    {
        if (strlen($password) < 10) {
            return $label . ' must be at least 10 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return $label . ' must include at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return $label . ' must include at least one lowercase letter.';
        }
        if (!preg_match('/\d/', $password)) {
            return $label . ' must include at least one number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return $label . ' must include at least one special character.';
        }

        return null;
    }
}

if (!function_exists('authIsSecureRequest')) {
    function authIsSecureRequest(): bool
    {
        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return $https === 'on' || $https === '1' || $forwardedProto === 'https';
    }
}

if (!function_exists('authRememberMeCookieName')) {
    function authRememberMeCookieName(): string
    {
        return 'hris_remember_me';
    }
}

if (!function_exists('authRememberMeLifetime')) {
    function authRememberMeLifetime(): int
    {
        return 60 * 60 * 24 * 30;
    }
}

if (!function_exists('authShouldRememberSession')) {
    function authShouldRememberSession(?bool $explicitValue = null): bool
    {
        if ($explicitValue !== null) {
            return $explicitValue;
        }

        $postValue = $_POST['remember_me'] ?? null;
        if ($postValue !== null) {
            return in_array(strtolower(trim((string)$postValue)), ['1', 'true', 'on', 'yes'], true);
        }

        return (string)($_COOKIE[authRememberMeCookieName()] ?? '') === '1';
    }
}

if (!function_exists('authCookieOptions')) {
    function authCookieOptions(int $lifetime): array
    {
        return [
            'expires' => $lifetime > 0 ? time() + $lifetime : 0,
            'path' => '/',
            'domain' => '',
            'secure' => authIsSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('authSessionCookieParams')) {
    function authSessionCookieParams(int $lifetime): array
    {
        return [
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => authIsSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('authStartSession')) {
    function authStartSession(?bool $rememberMe = null): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $remember = authShouldRememberSession($rememberMe);
        $lifetime = $remember ? authRememberMeLifetime() : 0;
        session_set_cookie_params(authSessionCookieParams($lifetime));
        session_start();
    }
}

if (!function_exists('authSyncRememberMeCookie')) {
    function authSyncRememberMeCookie(bool $enabled): void
    {
        $options = authCookieOptions($enabled ? authRememberMeLifetime() : 0);
        if (!$enabled) {
            $options['expires'] = time() - 3600;
        }

        setcookie(authRememberMeCookieName(), $enabled ? '1' : '', $options);
    }
}