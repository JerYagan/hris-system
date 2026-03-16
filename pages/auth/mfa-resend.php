<?php

require_once __DIR__ . '/includes/auth-support.php';
require_once dirname(__DIR__) . '/admin/includes/notifications/email.php';

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
    header('Location: ' . $redirectVerify . '&error=cooldown');
    exit;
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

if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
    authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
        'user_id' => $pendingMfa['user_id'] ?? null,
        'email_attempted' => (string)($pendingMfa['email'] ?? ''),
        'auth_provider' => 'password',
        'event_type' => 'mfa_otp_issue_failed',
        'metadata' => [
            'purpose' => $purpose,
            'reason' => 'smtp_config_not_ready',
            'phase' => 'resend',
        ],
    ]);
    header('Location: ' . $redirectVerify . '&error=config');
    exit;
}

$verificationCode = authGenerateOtpCode();
$challengeData = $pendingMfa;
unset(
    $challengeData['code_hash'],
    $challengeData['issued_at'],
    $challengeData['expires_at'],
    $challengeData['resend_available_at'],
    $challengeData['attempts'],
    $challengeData['attempt_limit']
);
$updatedChallenge = authStorePendingMfaChallenge($challengeData, $verificationCode);

$recipientEmail = (string)($updatedChallenge['email'] ?? '');
$recipientName = (string)(($updatedChallenge['user']['name'] ?? '') ?: trim(((string)($updatedChallenge['registration']['first_name'] ?? '')) . ' ' . ((string)($updatedChallenge['registration']['surname'] ?? ''))));
$safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
$safeExpiry = htmlspecialchars(hrisEmailFormatPhilippinesTimestamp((int)$updatedChallenge['expires_at']), ENT_QUOTES, 'UTF-8');
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
        error_log('MFA resend failed for ' . $recipientEmail . ': ' . $mailFailure);
    }
    authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
        'user_id' => $updatedChallenge['user_id'] ?? null,
        'email_attempted' => $recipientEmail,
        'auth_provider' => 'password',
        'event_type' => 'mfa_otp_issue_failed',
        'metadata' => [
            'purpose' => $purpose,
            'reason' => 'smtp_send_failed',
            'phase' => 'resend',
            'mail_response' => $mailFailure,
        ],
    ]);
    header('Location: ' . $redirectVerify . '&error=send_failed');
    exit;
}

authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $updatedChallenge['user_id'] ?? null,
    'email_attempted' => $recipientEmail,
    'auth_provider' => 'password',
    'event_type' => 'mfa_otp_issued',
    'metadata' => [
        'purpose' => $purpose,
        'channel' => (string)($updatedChallenge['channel'] ?? 'email'),
        'resend' => true,
    ],
]);

header('Location: ' . $redirectVerify . '&resent=1');
exit;