<?php

require_once __DIR__ . '/includes/auth-support.php';
require_once dirname(__DIR__) . '/admin/includes/notifications/email.php';

authStartSession();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: register-applicant.php');
  exit;
}

function redirect_with_error(string $code): void
{
  header('Location: register-applicant.php?error=' . urlencode($code));
  exit;
}

$email = strtolower((string)authCleanText($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$firstName = (string)authCleanText($_POST['first_name'] ?? '');
$surname = (string)authCleanText($_POST['surname'] ?? '');
$mobileNo = (string)($_POST['mobile'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  redirect_with_error('invalid_email');
}

if ($password === '') {
  redirect_with_error('weak_password');
}

$passwordValidationMessage = authValidateStrongPassword($password);
if ($passwordValidationMessage !== null) {
  redirect_with_error('weak_password');
}

if ($password !== $confirmPassword) {
  redirect_with_error('password_mismatch');
}

if (authValidatePersonName($firstName, 'First name') !== null) {
  redirect_with_error('invalid_first_name');
}

if (authValidatePersonName($surname, 'Surname') !== null) {
  redirect_with_error('invalid_surname');
}

if (authValidateMobileNumber($mobileNo) !== null) {
  redirect_with_error('invalid_mobile');
}

$normalizedMobileNo = authNormalizeMobileNumber($mobileNo);

$rootDir = dirname(__DIR__, 2);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
  redirect_with_error('config');
}

$headers = [
  'Content-Type: application/json',
  'apikey: ' . $supabaseServiceRoleKey,
  'Authorization: Bearer ' . $supabaseServiceRoleKey,
];

$existingAccountResponse = authHttpJsonRequest(
  'GET',
  $supabaseUrl . '/rest/v1/user_accounts?select=id&email=eq.' . rawurlencode($email) . '&limit=1',
  $headers
);

if (!empty($existingAccountResponse['data'])) {
  redirect_with_error('email_exists');
}

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
  redirect_with_error('config');
}

$verificationCode = authGenerateOtpCode();
$mfaChallenge = authStorePendingMfaChallenge([
  'purpose' => 'register',
  'email' => $email,
  'registration' => [
    'email' => $email,
    'password' => $password,
    'first_name' => $firstName,
    'surname' => $surname,
    'mobile' => $normalizedMobileNo,
  ],
], $verificationCode);

$safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
$safeExpiry = htmlspecialchars(hrisEmailFormatPhilippinesTimestamp((int)$mfaChallenge['expires_at']), ENT_QUOTES, 'UTF-8');
$mailResponse = smtpSendTransactionalEmail(
  $smtpConfig,
  $mailFrom,
  $mailFromName,
  $email,
  trim($firstName . ' ' . $surname),
  'ATI HRIS Portal Registration Verification Code',
  '<p>Hello,</p>'
    . '<p>Use the verification code below to complete your ATI HRIS Portal registration.</p>'
    . '<p><strong>Verification Code</strong><br>'
    . '<span style="display:inline-block;margin-top:8px;font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</span></p>'
    . '<p>This code expires on <strong>' . $safeExpiry . '</strong>.</p>'
    . '<p>If you did not start this registration, you can ignore this email.</p>'
);

if (!isSuccessful($mailResponse)) {
  unset($_SESSION[authPendingMfaSessionKey()]);
  redirect_with_error('otp_send_failed');
}

authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
  'user_id' => null,
  'email_attempted' => $email,
  'auth_provider' => 'password',
  'event_type' => 'mfa_otp_issued',
  'metadata' => [
    'purpose' => 'register',
    'channel' => (string)($mfaChallenge['channel'] ?? 'email'),
  ],
]);

header('Location: mfa-verify.php?mode=register&sent=1');
exit;
