<?php

require_once __DIR__ . '/includes/auth-support.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: login.php');
    exit;
}

authStartSession();

$pendingMfa = (array)($_SESSION[authPendingMfaSessionKey()] ?? []);
$purpose = strtolower((string)($pendingMfa['purpose'] ?? 'login'));
$redirectVerify = 'mfa-verify.php?mode=' . rawurlencode($purpose);

if (empty($pendingMfa)) {
    header('Location: login.php?error=mfa_missing');
    exit;
}

if ((int)($pendingMfa['resend_available_at'] ?? 0) > time()) {
    header('Location: ' . $redirectVerify . '&error=cooldown');
    exit;
}

authLoadProjectEnv();
$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
    header('Location: ' . $redirectVerify . '&error=config');
    exit;
}

$challengeData = $pendingMfa;
unset(
    $challengeData['code_hash'],
    $challengeData['issued_at'],
    $challengeData['expires_at'],
    $challengeData['resend_available_at'],
    $challengeData['attempts'],
    $challengeData['attempt_limit']
);
$otpResult = authIssueEmailOtpChallenge($challengeData, true);
if (!($otpResult['ok'] ?? false)) {
    $errorCode = (string)($otpResult['code'] ?? 'send_failed');
    if ($errorCode !== 'config') {
        $errorCode = 'send_failed';
    }
    header('Location: ' . $redirectVerify . '&error=' . urlencode($errorCode));
    exit;
}

header('Location: ' . $redirectVerify . '&resent=1');
exit;