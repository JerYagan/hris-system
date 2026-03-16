<?php

require_once __DIR__ . '/includes/auth-support.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: login.php');
    exit;
}

authStartSession();

$pendingMfa = (array)($_SESSION[authPendingMfaSessionKey()] ?? []);
if (empty($pendingMfa)) {
    header('Location: login.php?error=mfa_missing');
    exit;
}

$purpose = strtolower((string)($pendingMfa['purpose'] ?? 'login'));
$redirectVerify = 'mfa-verify.php?mode=' . rawurlencode($purpose);
$originRedirect = $purpose === 'register' ? 'register-applicant.php' : 'login.php';
$verificationCode = trim((string)($_POST['verification_code'] ?? ''));

if ($verificationCode === '' || !preg_match('/^[0-9]{6}$/', $verificationCode)) {
    header('Location: ' . $redirectVerify . '&error=invalid_code');
    exit;
}

$expiresAt = (int)($pendingMfa['expires_at'] ?? 0);
if ($expiresAt <= time()) {
    header('Location: ' . $redirectVerify . '&error=expired');
    exit;
}

authLoadProjectEnv();
$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

$expectedHash = (string)($pendingMfa['code_hash'] ?? '');
if ($expectedHash === '' || !hash_equals($expectedHash, authOtpHash($verificationCode))) {
    $attempts = (int)($pendingMfa['attempts'] ?? 0) + 1;
    $pendingMfa['attempts'] = $attempts;
    $_SESSION[authPendingMfaSessionKey()] = $pendingMfa;

    authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
        'user_id' => $pendingMfa['user_id'] ?? null,
        'email_attempted' => (string)($pendingMfa['email'] ?? ''),
        'auth_provider' => 'password',
        'event_type' => 'mfa_otp_failed',
        'metadata' => [
            'purpose' => $purpose,
            'attempt' => $attempts,
            'attempt_limit' => (int)($pendingMfa['attempt_limit'] ?? 5),
        ],
    ]);

    if ($attempts >= (int)($pendingMfa['attempt_limit'] ?? 5)) {
        authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
            'user_id' => $pendingMfa['user_id'] ?? null,
            'email_attempted' => (string)($pendingMfa['email'] ?? ''),
            'auth_provider' => 'password',
            'event_type' => 'mfa_otp_locked',
            'metadata' => [
                'purpose' => $purpose,
                'attempt_limit' => (int)($pendingMfa['attempt_limit'] ?? 5),
            ],
        ]);

        unset($_SESSION[authPendingMfaSessionKey()]);
        header('Location: ' . $originRedirect . '?error=mfa_locked');
        exit;
    }

    header('Location: ' . $redirectVerify . '&error=invalid_code');
    exit;
}

authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $pendingMfa['user_id'] ?? null,
    'email_attempted' => (string)($pendingMfa['email'] ?? ''),
    'auth_provider' => 'password',
    'event_type' => 'mfa_otp_verified',
    'metadata' => [
        'purpose' => $purpose,
        'channel' => (string)($pendingMfa['channel'] ?? 'email'),
    ],
]);

if ($purpose === 'register') {
    $result = authCreateApplicantAccount((array)($pendingMfa['registration'] ?? []));
    unset($_SESSION[authPendingMfaSessionKey()]);

    if (!($result['ok'] ?? false)) {
        header('Location: register-applicant.php?error=' . urlencode((string)($result['code'] ?? 'create_failed')));
        exit;
    }

    header('Location: login.php?registered=1');
    exit;
}

$userId = (string)($pendingMfa['user']['id'] ?? '');
if ($userId !== '' && $supabaseUrl !== '' && $supabaseServiceRoleKey) {
    authHttpJsonRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($userId),
        [
            'apikey: ' . $supabaseServiceRoleKey,
            'Authorization: Bearer ' . $supabaseServiceRoleKey,
            'Prefer: return=minimal',
        ],
        ['last_login_at' => gmdate('c')]
    );

    authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
        'user_id' => $userId,
        'email_attempted' => (string)($pendingMfa['email'] ?? ''),
        'auth_provider' => 'password',
        'event_type' => 'login_success',
        'metadata' => ['role_key' => (string)($pendingMfa['user']['role_key'] ?? '')],
    ]);
}

authFinalizeLoginSession($pendingMfa);
unset($_SESSION[authPendingMfaSessionKey()]);

header('Location: ' . (string)($pendingMfa['redirect_to'] ?? '../applicant/dashboard.php'));
exit;