<?php

require_once __DIR__ . '/includes/auth-support.php';

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

if ($firstName === '') {
  redirect_with_error('missing_first_name');
}

if ($surname === '') {
  redirect_with_error('missing_surname');
}

if ($email === '') {
  redirect_with_error('missing_email');
}

if ($mobileNo === '' || authCleanText($mobileNo) === null) {
  redirect_with_error('missing_mobile');
}

if ($password === '') {
  redirect_with_error('missing_password');
}

if ($confirmPassword === '') {
  redirect_with_error('missing_confirm_password');
}

if (!authIsValidEmailAddress($email)) {
  redirect_with_error('invalid_email');
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

$otpResult = authIssueEmailOtpChallenge([
  'purpose' => 'register',
  'email' => $email,
  'registration' => [
    'email' => $email,
    'password' => $password,
    'first_name' => $firstName,
    'surname' => $surname,
    'mobile' => $normalizedMobileNo,
  ],
]);

if (!($otpResult['ok'] ?? false)) {
  redirect_with_error((string)($otpResult['code'] ?? 'create_failed'));
}

header('Location: mfa-verify.php?mode=register&sent=1');
exit;
