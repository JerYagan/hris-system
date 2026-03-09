<?php

require_once __DIR__ . '/includes/auth-support.php';
require_once dirname(__DIR__) . '/admin/includes/notifications/email.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: forgot-password.php');
  exit;
}

authStartSession();

$email = strtolower((string)authCleanText($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: forgot-password.php?error=invalid_email');
  exit;
}

$rootDir = dirname(__DIR__, 2);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
  header('Location: forgot-password.php?error=config');
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
$mailFromName = (string)(authEnvValue('MAIL_FROM_NAME') ?? 'DA-ATI HRIS');

$resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
$smtpConfig = (array)($resolvedMail['smtp'] ?? $smtpConfig);
$mailFrom = (string)($resolvedMail['from'] ?? $mailFrom);
$mailFromName = (string)($resolvedMail['from_name'] ?? $mailFromName);

if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
  header('Location: forgot-password.php?error=config');
  exit;
}

$accountLookup = authHttpJsonRequest(
  'GET',
  $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status&id=not.is.null&email=eq.' . rawurlencode($email) . '&limit=1',
  $headers
);

$accountRow = (array)($accountLookup['data'][0] ?? []);
$userId = (string)($accountRow['id'] ?? '');
$accountEmail = strtolower(trim((string)($accountRow['email'] ?? '')));

if ($userId !== '' && filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
  $verificationCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expiresAt = time() + (10 * 60);

  $_SESSION['forgot_password_reset'] = [
    'user_id' => $userId,
    'email' => $accountEmail,
    'code_hash' => hash('sha256', $verificationCode),
    'expires_at' => $expiresAt,
    'attempts' => 0,
  ];

  $safeEmail = htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8');
  $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
  $safeExpiry = htmlspecialchars(hrisEmailFormatPhilippinesTimestamp($expiresAt), ENT_QUOTES, 'UTF-8');

  $mailResponse = smtpSendTransactionalEmail(
    $smtpConfig,
    $mailFrom,
    $mailFromName,
    $accountEmail,
    $accountEmail,
    'DA-ATI HRIS Password Reset Verification Code',
    '<p>Hello,</p>'
      . '<p>We received a request to reset the password for your DA-ATI HRIS account.</p>'
      . '<p><strong>Verification Code</strong><br>'
      . '<span style="display:inline-block;margin-top:8px;font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</span></p>'
      . '<p>Enter this code on the password reset page within 10 minutes. The code expires on <strong>' . $safeExpiry . '</strong>.</p>'
      . '<p>If you did not request a password reset for ' . $safeEmail . ', you may ignore this email. No changes will be made unless the code is entered.</p>'
  );

  if (!isSuccessful($mailResponse)) {
    unset($_SESSION['forgot_password_reset']);
    header('Location: forgot-password.php?error=send_failed');
    exit;
  }

  authHttpJsonRequest(
    'POST',
    $supabaseUrl . '/rest/v1/login_audit_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
      'user_id' => $userId,
      'email_attempted' => $accountEmail,
      'auth_provider' => 'password',
      'event_type' => 'password_reset_code_sent',
      'metadata' => ['channel' => 'email', 'source' => 'forgot_password'],
    ]]
  );
} else {
  unset($_SESSION['forgot_password_reset']);
}

header('Location: reset-password.php?sent=1');
exit;
