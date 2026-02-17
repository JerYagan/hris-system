<?php
session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: register-applicant.php');
  exit;
}

function load_env_file_if_present(string $envPath): void
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

function env_value(string $key): ?string
{
  $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
  if ($value === false || $value === null || $value === '') {
    return null;
  }

  return $value;
}

function redirect_with_error(string $code): void
{
  header('Location: register-applicant.php?error=' . urlencode($code));
  exit;
}

function http_json_request(string $method, string $url, array $headers, $body = null): array
{
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 25,
  ]);

  if ($body !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
  }

  $responseBody = curl_exec($ch);
  $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
  if (!is_array($decoded)) {
    $decoded = [];
  }

  return [
    'status' => $statusCode,
    'data' => $decoded,
    'raw' => $responseBody,
  ];
}

function clean_text(?string $value): ?string
{
  if ($value === null) {
    return null;
  }

  $trimmed = trim($value);
  return $trimmed === '' ? null : $trimmed;
}

function delete_auth_user(string $supabaseUrl, string $serviceRoleKey, string $userId): void
{
  http_json_request(
    'DELETE',
    $supabaseUrl . '/auth/v1/admin/users/' . $userId,
    [
      'apikey: ' . $serviceRoleKey,
      'Authorization: Bearer ' . $serviceRoleKey,
    ]
  );
}

$email = strtolower((string)clean_text($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$firstName = (string)clean_text($_POST['first_name'] ?? '');
$surname = (string)clean_text($_POST['surname'] ?? '');
$mobileNo = clean_text($_POST['mobile'] ?? null);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  redirect_with_error('invalid_email');
}

if ($password === '' || strlen($password) < 8) {
  redirect_with_error('weak_password');
}

if ($password !== $confirmPassword) {
  redirect_with_error('password_mismatch');
}

if ($firstName === '' || $surname === '') {
  redirect_with_error('missing_name');
}

$rootDir = dirname(__DIR__, 2);
load_env_file_if_present($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(env_value('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = env_value('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
  redirect_with_error('config');
}

$commonHeaders = [
  'Content-Type: application/json',
  'apikey: ' . $supabaseServiceRoleKey,
  'Authorization: Bearer ' . $supabaseServiceRoleKey,
];

$fullName = trim($firstName . ' ' . $surname);

$createAuthResponse = http_json_request(
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

$roleResponse = http_json_request(
  'GET',
  $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.applicant&limit=1',
  $commonHeaders
);

$roleId = (string)($roleResponse['data'][0]['id'] ?? '');
if ($roleId === '') {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('role_missing');
}

$accountInsert = http_json_request(
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

$roleAssignResponse = http_json_request(
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

$personResponse = http_json_request(
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

$profileResponse = http_json_request(
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

http_json_request(
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

http_json_request(
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
