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

if (!function_exists('authIsValidEmailAddress')) {
    function authIsValidEmailAddress(?string $value): bool
    {
        $email = strtolower(trim((string)$value));
        if ($email === '' || strlen($email) > 254) {
            return false;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $email) === 1;
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
            CURLOPT_CONNECTTIMEOUT => 5,
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

if (!function_exists('authIssueEmailOtpChallenge')) {
    function authIssueEmailOtpChallenge(array $challenge, bool $isResend = false): array
    {
        authLoadProjectEnv();
        require_once dirname(__DIR__, 2) . '/admin/includes/notifications/email.php';

        $supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
        $supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');
        if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
            return ['ok' => false, 'code' => 'config'];
        }

        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $supabaseServiceRoleKey,
            'Authorization: Bearer ' . $supabaseServiceRoleKey,
        ];

        $smtpConfig = [
            'host' => (string)(authEnvValue('SMTP_HOST') ?? ''),
            'port' => (int)(authEnvValue('SMTP_PORT') ?? 587),
            'username' => (string)(authEnvValue('SMTP_USERNAME') ?? ''),
            'password' => (string)(authEnvValue('SMTP_PASSWORD') ?? ''),
            'encryption' => (string)(authEnvValue('SMTP_ENCRYPTION') ?? 'tls'),
            'auth' => (string)(authEnvValue('SMTP_AUTH') ?? '1'),
        ];
        $mailFrom = (string)(authEnvValue('MAIL_FROM') ?? '');
        $mailFromName = (string)(authEnvValue('MAIL_FROM_NAME') ?? 'ATI HRIS Portal');

        $resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
        $smtpConfig = (array)($resolvedMail['smtp'] ?? $smtpConfig);
        $mailFrom = (string)($resolvedMail['from'] ?? $mailFrom);
        $mailFromName = (string)($resolvedMail['from_name'] ?? $mailFromName);

        $purpose = strtolower(trim((string)($challenge['purpose'] ?? 'login')));
        $recipientEmail = trim((string)($challenge['email'] ?? ''));
        $recipientName = trim((string)(
            ($challenge['user']['name'] ?? '')
            ?: trim(((string)($challenge['registration']['first_name'] ?? '')) . ' ' . ((string)($challenge['registration']['surname'] ?? '')))
        ));
        $previousChallenge = (array)($_SESSION[authPendingMfaSessionKey()] ?? []);

        if ($recipientEmail === '' || !authIsValidEmailAddress($recipientEmail)) {
            return ['ok' => false, 'code' => 'invalid_email'];
        }

        if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
            authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
                'user_id' => $challenge['user_id'] ?? null,
                'email_attempted' => $recipientEmail,
                'auth_provider' => 'password',
                'event_type' => 'mfa_otp_issue_failed',
                'metadata' => [
                    'purpose' => $purpose,
                    'reason' => 'smtp_config_not_ready',
                    'phase' => $isResend ? 'resend' : 'initial',
                ],
            ]);

            return ['ok' => false, 'code' => 'config'];
        }

        $verificationCode = authGenerateOtpCode();
        $storedChallenge = authStorePendingMfaChallenge($challenge, $verificationCode);
        $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
        $safeExpiry = htmlspecialchars(authFormatPhilippinesTimestamp((int)($storedChallenge['expires_at'] ?? 0)), ENT_QUOTES, 'UTF-8');
        $subject = $purpose === 'register'
            ? 'ATI HRIS Portal Registration Verification Code'
            : 'ATI HRIS Portal Login Verification Code';
        $htmlBody = $purpose === 'register'
            ? '<p>Hello,</p><p>Use the verification code below to complete your ATI HRIS Portal registration.</p>'
            : '<p>Hello,</p><p>Use the verification code below to complete your ATI HRIS Portal sign-in.</p>';
        $htmlBody .= '<p><strong>Verification Code</strong><br><span style="display:inline-block;margin-top:8px;font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</span></p>'
            . '<p>This code expires on <strong>' . $safeExpiry . '</strong>.</p>';

        $mailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $recipientEmail,
            $recipientName,
            $subject,
            $htmlBody
        );

        if (!isSuccessful($mailResponse)) {
            $mailFailure = trim((string)($mailResponse['raw'] ?? ''));
            if ($mailFailure !== '') {
                error_log('MFA code send failed for ' . $recipientEmail . ': ' . $mailFailure);
            }

            if (!empty($previousChallenge)) {
                $_SESSION[authPendingMfaSessionKey()] = $previousChallenge;
            } else {
                unset($_SESSION[authPendingMfaSessionKey()]);
            }

            authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
                'user_id' => $storedChallenge['user_id'] ?? null,
                'email_attempted' => $recipientEmail,
                'auth_provider' => 'password',
                'event_type' => 'mfa_otp_issue_failed',
                'metadata' => [
                    'purpose' => $purpose,
                    'reason' => 'smtp_send_failed',
                    'phase' => $isResend ? 'resend' : 'initial',
                    'mail_response' => $mailFailure,
                ],
            ]);

            return ['ok' => false, 'code' => 'send_failed'];
        }

        authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
            'user_id' => $storedChallenge['user_id'] ?? null,
            'email_attempted' => $recipientEmail,
            'auth_provider' => 'password',
            'event_type' => 'mfa_otp_issued',
            'metadata' => [
                'purpose' => $purpose,
                'channel' => (string)($storedChallenge['channel'] ?? 'email'),
                'resend' => $isResend,
            ],
        ]);

        return ['ok' => true, 'code' => null, 'challenge' => $storedChallenge];
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

if (!function_exists('authRoleRedirectMap')) {
    function authRoleRedirectMap(): array
    {
        return [
            'admin' => '../admin/dashboard.php',
            'hr_officer' => '../staff/dashboard.php',
            'supervisor' => '../staff/dashboard.php',
            'staff' => '../staff/dashboard.php',
            'employee' => '../employee/dashboard.php',
            'applicant' => '../applicant/dashboard.php',
        ];
    }
}

if (!function_exists('authRoleRedirectPath')) {
    function authRoleRedirectPath(string $roleKey): ?string
    {
        $normalizedRoleKey = strtolower(trim($roleKey));
        if ($normalizedRoleKey === '') {
            return null;
        }

        $redirectMap = authRoleRedirectMap();
        return $redirectMap[$normalizedRoleKey] ?? null;
    }
}

if (!function_exists('authResolveLoginContext')) {
    function authResolveLoginContext(string $supabaseUrl, string $serviceRoleKey, string $userId): array
    {
        $headers = [
            'apikey: ' . $serviceRoleKey,
            'Authorization: Bearer ' . $serviceRoleKey,
        ];

        $accountResponse = authHttpJsonRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=account_status&id=eq.' . rawurlencode($userId) . '&limit=1',
            $headers
        );

        $accountStatus = $accountResponse['data'][0]['account_status'] ?? null;
        if ($accountStatus !== null && $accountStatus !== 'active') {
            return [
                'ok' => false,
                'code' => 'inactive',
                'account_status' => $accountStatus,
            ];
        }

        $roleResponse = authHttpJsonRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_role_assignments?select=is_primary,role:roles(role_key)&user_id=eq.' . rawurlencode($userId) . '&expires_at=is.null&order=is_primary.desc&limit=1',
            $headers
        );

        $roleKey = strtolower((string)($roleResponse['data'][0]['role']['role_key'] ?? ''));
        $redirectTo = authRoleRedirectPath($roleKey);
        if ($roleKey === '' || $redirectTo === null) {
            return [
                'ok' => false,
                'code' => 'role',
            ];
        }

        return [
            'ok' => true,
            'code' => null,
            'account_status' => $accountStatus,
            'role_key' => $roleKey,
            'redirect_to' => $redirectTo,
        ];
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
        authSyncPersistentLoginCookie((bool)($pendingMfa['remember_me'] ?? false), [
            'user' => $_SESSION['user'],
            'tokens' => $_SESSION['supabase'],
        ]);
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

if (!function_exists('authPersistentLoginCookieName')) {
    function authPersistentLoginCookieName(): string
    {
        return 'hris_persistent_auth';
    }
}

if (!function_exists('authPersistentLoginLifetime')) {
    function authPersistentLoginLifetime(): int
    {
        return 60 * 60 * 24 * 365;
    }
}

if (!function_exists('authBase64UrlEncode')) {
    function authBase64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('authBase64UrlDecode')) {
    function authBase64UrlDecode(string $value): ?string
    {
        $paddingLength = (4 - (strlen($value) % 4)) % 4;
        $decoded = base64_decode(strtr($value . str_repeat('=', $paddingLength), '-_', '+/'), true);
        return is_string($decoded) ? $decoded : null;
    }
}

if (!function_exists('authPersistentLoginSecret')) {
    function authPersistentLoginSecret(): string
    {
        authLoadProjectEnv();

        $secretSeed = (string)(
            authEnvValue('AUTH_PERSISTENT_LOGIN_SECRET')
            ?? authEnvValue('APP_KEY')
            ?? authEnvValue('SUPABASE_SERVICE_ROLE_KEY')
            ?? authProjectRoot()
        );

        return hash('sha256', $secretSeed . '|' . authProjectRoot(), true);
    }
}

if (!function_exists('authEncodePersistentLoginPayload')) {
    function authEncodePersistentLoginPayload(array $payload): ?string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return null;
        }

        $encodedPayload = authBase64UrlEncode($json);
        $signature = authBase64UrlEncode(hash_hmac('sha256', $encodedPayload, authPersistentLoginSecret(), true));

        return $encodedPayload . '.' . $signature;
    }
}

if (!function_exists('authDecodePersistentLoginPayload')) {
    function authDecodePersistentLoginPayload(?string $cookieValue): ?array
    {
        $value = trim((string)$cookieValue);
        if ($value === '' || !str_contains($value, '.')) {
            return null;
        }

        [$encodedPayload, $signature] = explode('.', $value, 2);
        if ($encodedPayload === '' || $signature === '') {
            return null;
        }

        $expectedSignature = authBase64UrlEncode(hash_hmac('sha256', $encodedPayload, authPersistentLoginSecret(), true));
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decodedPayload = authBase64UrlDecode($encodedPayload);
        if ($decodedPayload === null) {
            return null;
        }

        $payload = json_decode($decodedPayload, true);
        return is_array($payload) ? $payload : null;
    }
}

if (!function_exists('authBuildPersistentLoginPayload')) {
    function authBuildPersistentLoginPayload(array $sessionState): ?array
    {
        $refreshToken = trim((string)($sessionState['tokens']['refresh_token'] ?? $sessionState['refresh_token'] ?? ''));
        $userId = trim((string)($sessionState['user']['id'] ?? ''));
        if ($refreshToken === '' || $userId === '') {
            return null;
        }

        return [
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $userId,
                'email' => (string)($sessionState['user']['email'] ?? ''),
                'name' => (string)($sessionState['user']['name'] ?? ''),
                'role_key' => (string)($sessionState['user']['role_key'] ?? ''),
            ],
            'issued_at' => time(),
        ];
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

        if ((string)($_COOKIE[authRememberMeCookieName()] ?? '') === '1') {
            return true;
        }

        return trim((string)($_COOKIE[authPersistentLoginCookieName()] ?? '')) !== '';
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
        $lifetime = $remember ? authPersistentLoginLifetime() : 0;
        if ($lifetime > 0) {
            ini_set('session.gc_maxlifetime', (string)$lifetime);
        }

        session_set_cookie_params(authSessionCookieParams($lifetime));
        session_start();

        if (!isset($_SESSION['user']) || !isset($_SESSION['supabase']['access_token'])) {
            authRestorePersistentLoginSession();
        }
    }
}

if (!function_exists('authSyncRememberMeCookie')) {
    function authSyncRememberMeCookie(bool $enabled): void
    {
        $options = authCookieOptions($enabled ? authPersistentLoginLifetime() : 0);
        if (!$enabled) {
            $options['expires'] = time() - 3600;
        }

        setcookie(authRememberMeCookieName(), $enabled ? '1' : '', $options);
    }
}

if (!function_exists('authSyncPersistentLoginCookie')) {
    function authSyncPersistentLoginCookie(bool $enabled, ?array $sessionState = null): void
    {
        $options = authCookieOptions($enabled ? authPersistentLoginLifetime() : 0);
        if (!$enabled) {
            $options['expires'] = time() - 3600;
            setcookie(authPersistentLoginCookieName(), '', $options);
            return;
        }

        $payload = authBuildPersistentLoginPayload($sessionState ?? []);
        $encodedPayload = $payload === null ? null : authEncodePersistentLoginPayload($payload);
        if ($encodedPayload === null || $encodedPayload === '') {
            $options['expires'] = time() - 3600;
            setcookie(authPersistentLoginCookieName(), '', $options);
            return;
        }

        setcookie(authPersistentLoginCookieName(), $encodedPayload, $options);
    }
}

if (!function_exists('authRestorePersistentLoginSession')) {
    function authRestorePersistentLoginSession(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (isset($_SESSION['user']) && isset($_SESSION['supabase']['access_token'])) {
            return true;
        }

        $persistentPayload = authDecodePersistentLoginPayload($_COOKIE[authPersistentLoginCookieName()] ?? null);
        if ($persistentPayload === null) {
            return false;
        }

        $refreshToken = trim((string)($persistentPayload['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            authSyncRememberMeCookie(false);
            authSyncPersistentLoginCookie(false);
            return false;
        }

        authLoadProjectEnv();
        $supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
        $supabaseAnonKey = authEnvValue('SUPABASE_ANON_KEY');
        $supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');
        if ($supabaseUrl === '' || !$supabaseAnonKey || !$supabaseServiceRoleKey) {
            authSyncRememberMeCookie(false);
            authSyncPersistentLoginCookie(false);
            return false;
        }

        $refreshResponse = authHttpJsonRequest(
            'POST',
            $supabaseUrl . '/auth/v1/token?grant_type=refresh_token',
            [
                'apikey: ' . $supabaseAnonKey,
                'Content-Type: application/json',
            ],
            ['refresh_token' => $refreshToken]
        );

        $refreshedUser = $refreshResponse['data']['user'] ?? null;
        $accessToken = $refreshResponse['data']['access_token'] ?? null;
        $nextRefreshToken = trim((string)($refreshResponse['data']['refresh_token'] ?? ''));
        if ((int)($refreshResponse['status'] ?? 0) !== 200 || !is_array($refreshedUser) || !is_string($accessToken) || $accessToken === '' || $nextRefreshToken === '') {
            authSyncRememberMeCookie(false);
            authSyncPersistentLoginCookie(false);
            return false;
        }

        $userId = (string)($refreshedUser['id'] ?? ($persistentPayload['user']['id'] ?? ''));
        $loginContext = authResolveLoginContext($supabaseUrl, $supabaseServiceRoleKey, $userId);
        if (!($loginContext['ok'] ?? false)) {
            authSyncRememberMeCookie(false);
            authSyncPersistentLoginCookie(false);
            return false;
        }

        $userEmail = (string)($refreshedUser['email'] ?? ($persistentPayload['user']['email'] ?? ''));
        $displayName = (string)(
            $refreshedUser['user_metadata']['full_name']
            ?? $refreshedUser['user_metadata']['name']
            ?? ($persistentPayload['user']['name'] ?? $userEmail)
        );

        authFinalizeLoginSession([
            'remember_me' => true,
            'user' => [
                'id' => $userId,
                'email' => $userEmail,
                'name' => $displayName,
                'role_key' => (string)($loginContext['role_key'] ?? ''),
            ],
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $nextRefreshToken,
                'expires_at' => (int)($refreshResponse['data']['expires_at'] ?? 0),
            ],
        ]);

        return true;
    }
}