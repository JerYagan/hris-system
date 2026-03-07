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

function delete_auth_user(string $supabaseUrl, string $serviceRoleKey, string $userId): void
{
  authHttpJsonRequest(
    'DELETE',
    $supabaseUrl . '/auth/v1/admin/users/' . $userId,
    [
      'apikey: ' . $serviceRoleKey,
      'Authorization: Bearer ' . $serviceRoleKey,
    ]
  );
}

$email = strtolower((string)authCleanText($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$firstName = (string)authCleanText($_POST['first_name'] ?? '');
$surname = (string)authCleanText($_POST['surname'] ?? '');
$mobileNo = authCleanText($_POST['mobile'] ?? null);

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

if ($firstName === '' || $surname === '') {
  redirect_with_error('missing_name');
}

$rootDir = dirname(__DIR__, 2);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
  redirect_with_error('config');
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

if ($createAuthResponse['status'] < 200 || $createAuthResponse['status'] >= 300) {
  $errorBody = strtolower((string)($createAuthResponse['raw'] ?? ''));
  if (str_contains($errorBody, 'already') || str_contains($errorBody, 'exists')) {
    redirect_with_error('email_exists');
  }

  redirect_with_error('create_failed');
}

$userId = (string)($createAuthResponse['data']['id'] ?? '');
if ($userId === '') {
  redirect_with_error('create_failed');
}

$roleResponse = authHttpJsonRequest(
  'GET',
  $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.applicant&limit=1',
  $commonHeaders
);

$roleId = (string)($roleResponse['data'][0]['id'] ?? '');
if ($roleId === '') {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('role_missing');
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

if ($accountInsert['status'] < 200 || $accountInsert['status'] >= 300) {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('create_failed');
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

if ($roleAssignResponse['status'] < 200 || $roleAssignResponse['status'] >= 300) {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('create_failed');
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

if ($personResponse['status'] < 200 || $personResponse['status'] >= 300) {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('create_failed');
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

if ($profileResponse['status'] < 200 || $profileResponse['status'] >= 300) {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('create_failed');
}

authHttpJsonRequest(
  'POST',
  $supabaseUrl . '/rest/v1/login_audit_logs',
  array_merge($commonHeaders, ['Prefer: return=minimal']),
  [[
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'register_success',
    'metadata' => ['role_key' => 'applicant'],
  ]]
);

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
  ]]
);

header('Location: login.php?registered=1');
exit;
