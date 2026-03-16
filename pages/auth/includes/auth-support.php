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

if (!function_exists('authProjectRoot')) {
    function authProjectRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}

if (!function_exists('authLoadProjectEnv')) {
    function authLoadProjectEnv(): void
    {
        authLoadEnvFileIfPresent(authProjectRoot() . DIRECTORY_SEPARATOR . '.env');
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

if (!function_exists('authAppBasePath')) {
    function authAppBasePath(): string
    {
        authLoadProjectEnv();

        $configuredUrl = trim((string)(authEnvValue('APP_BASE_URL') ?? ''));
        if ($configuredUrl !== '') {
            $parsedPath = parse_url($configuredUrl, PHP_URL_PATH);
            if (is_string($parsedPath)) {
                $normalized = '/' . trim($parsedPath, '/');
                return $normalized === '/' ? '' : $normalized;
            }
        }

        return '/hris-system';
    }
}

if (!function_exists('authAppPath')) {
    function authAppPath(string $path = ''): string
    {
        $basePath = authAppBasePath();
        $suffix = '/' . ltrim($path, '/');
        if ($suffix === '/') {
            $suffix = '';
        }

        return ($basePath === '' ? '' : $basePath) . $suffix;
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

if (!function_exists('authUserAgent')) {
    function authUserAgent(): ?string
    {
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($userAgent === '') {
            return null;
        }

        return substr($userAgent, 0, 1000);
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

if (!function_exists('authValidatePersonName')) {
    function authValidatePersonName(?string $value, string $label): ?string
    {
        $name = trim((string)$value);
        if ($name === '') {
            return $label . ' is required.';
        }

        if (mb_strlen($name) < 2) {
            return $label . ' must be at least 2 characters.';
        }

        if (mb_strlen($name) > 80) {
            return $label . ' must be 80 characters or fewer.';
        }

        if (!preg_match("/^[A-Za-z][A-Za-z\s'.-]*$/", $name)) {
            return $label . ' may only contain letters, spaces, apostrophes, periods, and hyphens.';
        }

        return null;
    }
}

if (!function_exists('authNormalizeMobileNumber')) {
    function authNormalizeMobileNumber(?string $value): ?string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $raw);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '09') && strlen($normalized) === 11) {
            return '+63' . substr($normalized, 1);
        }

        if (str_starts_with($normalized, '639') && strlen($normalized) === 12) {
            return '+' . $normalized;
        }

        if (str_starts_with($raw, '+639') && preg_match('/^\+639\d{9}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}

if (!function_exists('authFormatPhilippinesTimestamp')) {
    function authFormatPhilippinesTimestamp(int|string|null $timestamp, string $format = 'M d, Y h:i A'): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '-';
        }

        $normalizedTimestamp = is_numeric($timestamp) ? (int)$timestamp : strtotime((string)$timestamp);
        if (!is_int($normalizedTimestamp) || $normalizedTimestamp <= 0) {
            return '-';
        }

        try {
            $dateTime = new DateTimeImmutable('@' . $normalizedTimestamp);
            $dateTime = $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
            return $dateTime->format($format) . ' PST';
        } catch (Throwable) {
            return '-';
        }
    }
}

if (!function_exists('authValidateMobileNumber')) {
    function authValidateMobileNumber(?string $value): ?string
    {
        if (authNormalizeMobileNumber($value) === null) {
            return 'Enter a valid Philippine mobile number using 09XXXXXXXXX or +639XXXXXXXXX.';
        }

        return null;
    }
}

if (!function_exists('authMfaConfig')) {
    function authMfaConfig(): array
    {
        return [
            'channel' => 'email',
            'expiry_seconds' => 10 * 60,
            'resend_cooldown_seconds' => 60,
            'attempt_limit' => 5,
            'fallback_behavior' => 'Request a new code and restart the verification step.',
        ];
    }
}

if (!function_exists('authGenerateOtpCode')) {
    function authGenerateOtpCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('authOtpHash')) {
    function authOtpHash(string $code): string
    {
        return hash('sha256', $code);
    }
}

if (!function_exists('authPendingMfaSessionKey')) {
    function authPendingMfaSessionKey(): string
    {
        return 'auth_pending_mfa';
    }
}

if (!function_exists('authStorePendingMfaChallenge')) {
    function authStorePendingMfaChallenge(array $challenge, string $otpCode): array
    {
        $config = authMfaConfig();
        $now = time();

        $storedChallenge = array_merge($challenge, [
            'channel' => (string)($challenge['channel'] ?? $config['channel']),
            'code_hash' => authOtpHash($otpCode),
            'issued_at' => $now,
            'expires_at' => $now + (int)$config['expiry_seconds'],
            'resend_available_at' => $now + (int)$config['resend_cooldown_seconds'],
            'attempts' => 0,
            'attempt_limit' => (int)$config['attempt_limit'],
            'fallback_behavior' => (string)$config['fallback_behavior'],
        ]);

        $_SESSION[authPendingMfaSessionKey()] = $storedChallenge;
        return $storedChallenge;
    }
}

if (!function_exists('authMaskEmail')) {
    function authMaskEmail(string $email): string
    {
        $normalized = trim($email);
        if ($normalized === '' || !str_contains($normalized, '@')) {
            return $normalized;
        }

        [$localPart, $domain] = explode('@', $normalized, 2);
        $localLength = strlen($localPart);
        if ($localLength <= 2) {
            $maskedLocal = substr($localPart, 0, 1) . str_repeat('*', max(1, $localLength - 1));
        } else {
            $maskedLocal = substr($localPart, 0, 1) . str_repeat('*', max(1, $localLength - 2)) . substr($localPart, -1);
        }

        return $maskedLocal . '@' . $domain;
    }
}

if (!function_exists('authLogLoginAuditEvent')) {
    function authLogLoginAuditEvent(string $supabaseUrl, ?string $serviceRoleKey, array $payload): void
    {
        if ($serviceRoleKey === null || trim($serviceRoleKey) === '' || trim($supabaseUrl) === '') {
            return;
        }

        if (!array_key_exists('ip_address', $payload)) {
            $payload['ip_address'] = authClientIp();
        }

        if (!array_key_exists('user_agent', $payload)) {
            $payload['user_agent'] = authUserAgent();
        }

        authHttpJsonRequest(
            'POST',
            rtrim($supabaseUrl, '/') . '/rest/v1/login_audit_logs',
            [
                'apikey: ' . $serviceRoleKey,
                'Authorization: Bearer ' . $serviceRoleKey,
                'Prefer: return=minimal',
            ],
            [$payload]
        );
    }
}

if (!function_exists('authDeleteAuthUser')) {
    function authDeleteAuthUser(string $supabaseUrl, string $serviceRoleKey, string $userId): void
    {
        authHttpJsonRequest(
            'DELETE',
            rtrim($supabaseUrl, '/') . '/auth/v1/admin/users/' . rawurlencode($userId),
            [
                'apikey: ' . $serviceRoleKey,
                'Authorization: Bearer ' . $serviceRoleKey,
            ]
        );
    }
}

if (!function_exists('authCreateApplicantAccount')) {
    function authCreateApplicantAccount(array $registration): array
    {
        authLoadProjectEnv();

        $email = strtolower(trim((string)($registration['email'] ?? '')));
        $password = (string)($registration['password'] ?? '');
        $firstName = trim((string)($registration['first_name'] ?? ''));
        $surname = trim((string)($registration['surname'] ?? ''));
        $mobileNo = authNormalizeMobileNumber((string)($registration['mobile'] ?? ''));

        $supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
        $supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

        if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
            return ['ok' => false, 'code' => 'config'];
        }

        $commonHeaders = [
            'Content-Type: application/json',
            'apikey: ' . $supabaseServiceRoleKey,
            'Authorization: Bearer ' . $supabaseServiceRoleKey,
        ];

        $fullName = trim($firstName . ' ' . $surname);

        $createAuthResponse = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/auth/v1/admin/users',
            $commonHeaders,
            [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $fullName,
                    'role_requested' => 'applicant',
                ],
            ]
        );

        if (!isSuccessful($createAuthResponse)) {
            $errorBody = strtolower((string)($createAuthResponse['raw'] ?? ''));
            if (str_contains($errorBody, 'already') || str_contains($errorBody, 'exists')) {
                return ['ok' => false, 'code' => 'email_exists'];
            }

            return ['ok' => false, 'code' => 'create_failed'];
        }

        $userId = (string)($createAuthResponse['data']['id'] ?? '');
        if ($userId === '') {
            return ['ok' => false, 'code' => 'create_failed'];
        }

        $roleResponse = authHttpJsonRequest(
            'GET',
            $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.applicant&limit=1',
            $commonHeaders
        );

        $roleId = (string)($roleResponse['data'][0]['id'] ?? '');
        if ($roleId === '') {
            authDeleteAuthUser($supabaseUrl, $supabaseServiceRoleKey, $userId);
            return ['ok' => false, 'code' => 'role_missing'];
        }

        $accountInsert = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_accounts',
            array_merge($commonHeaders, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'id' => $userId,
                'email' => $email,
                'mobile_no' => $mobileNo,
                'account_status' => 'active',
                'email_verified_at' => gmdate('c'),
            ]]
        );

        if (!isSuccessful($accountInsert)) {
            authDeleteAuthUser($supabaseUrl, $supabaseServiceRoleKey, $userId);
            return ['ok' => false, 'code' => 'create_failed'];
        }

        $roleAssignResponse = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_role_assignments',
            array_merge($commonHeaders, ['Prefer: return=minimal']),
            [[
                'user_id' => $userId,
                'role_id' => $roleId,
                'is_primary' => true,
                'assigned_at' => gmdate('c'),
            ]]
        );

        if (!isSuccessful($roleAssignResponse)) {
            authDeleteAuthUser($supabaseUrl, $supabaseServiceRoleKey, $userId);
            return ['ok' => false, 'code' => 'create_failed'];
        }

        $personResponse = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($commonHeaders, ['Prefer: return=representation']),
            [[
                'user_id' => $userId,
                'surname' => $surname,
                'first_name' => $firstName,
                'mobile_no' => $mobileNo,
                'personal_email' => $email,
            ]]
        );

        if (!isSuccessful($personResponse)) {
            authDeleteAuthUser($supabaseUrl, $supabaseServiceRoleKey, $userId);
            return ['ok' => false, 'code' => 'create_failed'];
        }

        $profileResponse = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/rest/v1/applicant_profiles?on_conflict=user_id',
            array_merge($commonHeaders, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'user_id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'mobile_no' => $mobileNo,
                'current_address' => null,
            ]]
        );

        if (!isSuccessful($profileResponse)) {
            authDeleteAuthUser($supabaseUrl, $supabaseServiceRoleKey, $userId);
            return ['ok' => false, 'code' => 'create_failed'];
        }

        authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
            'user_id' => $userId,
            'email_attempted' => $email,
            'auth_provider' => 'password',
            'event_type' => 'register_success',
            'metadata' => ['role_key' => 'applicant'],
        ]);

        authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($commonHeaders, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $userId,
                'module_name' => 'auth',
                'entity_name' => 'user_accounts',
                'entity_id' => $userId,
                'action_name' => 'register_applicant',
                'old_data' => null,
                'new_data' => [
                    'email' => $email,
                    'role_key' => 'applicant',
                    'full_name' => $fullName,
                ],
                'ip_address' => authClientIp(),
            ]]
        );

        return ['ok' => true, 'code' => null, 'user_id' => $userId, 'email' => $email];
    }
}

if (!function_exists('authFinalizeLoginSession')) {
    function authFinalizeLoginSession(array $pendingMfa): void
    {
        $_SESSION['user'] = (array)($pendingMfa['user'] ?? []);
        $_SESSION['supabase'] = (array)($pendingMfa['tokens'] ?? []);
        $_SESSION['remember_me'] = (bool)($pendingMfa['remember_me'] ?? false);

        session_regenerate_id(true);
        authSyncRememberMeCookie((bool)($pendingMfa['remember_me'] ?? false));
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