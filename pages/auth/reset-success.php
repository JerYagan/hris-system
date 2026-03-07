<?php

require_once __DIR__ . '/includes/auth-support.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: reset-password.php');
  exit;
}

authStartSession();

$pendingReset = (array)($_SESSION['forgot_password_reset'] ?? []);
if (empty($pendingReset)) {
  header('Location: reset-password.php?error=missing_request');
  exit;
}

$verificationCode = trim((string)($_POST['verification_code'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

if ($verificationCode === '' || !preg_match('/^[0-9]{6}$/', $verificationCode)) {
  header('Location: reset-password.php?error=invalid_code');
  exit;
}

if ($password !== $confirmPassword) {
  header('Location: reset-password.php?error=password_mismatch');
  exit;
}

if (authValidateStrongPassword($password) !== null) {
  header('Location: reset-password.php?error=weak_password');
  exit;
}

$expiresAt = (int)($pendingReset['expires_at'] ?? 0);
if ($expiresAt <= time()) {
  unset($_SESSION['forgot_password_reset']);
  header('Location: reset-password.php?error=expired');
  exit;
}

$expectedHash = (string)($pendingReset['code_hash'] ?? '');
$attempts = (int)($pendingReset['attempts'] ?? 0);
if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $verificationCode))) {
  $attempts++;
  if ($attempts >= 5) {
    unset($_SESSION['forgot_password_reset']);
    header('Location: reset-password.php?error=attempts');
    exit;
  }

  $pendingReset['attempts'] = $attempts;
  $_SESSION['forgot_password_reset'] = $pendingReset;
  header('Location: reset-password.php?error=invalid_code');
  exit;
}

$userId = (string)($pendingReset['user_id'] ?? '');
$email = strtolower(trim((string)($pendingReset['email'] ?? '')));
if ($userId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  unset($_SESSION['forgot_password_reset']);
  header('Location: reset-password.php?error=missing_request');
  exit;
}

$rootDir = dirname(__DIR__, 2);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseServiceRoleKey) {
  header('Location: reset-password.php?error=reset_failed');
  exit;
}

$headers = [
  'Content-Type: application/json',
  'apikey: ' . $supabaseServiceRoleKey,
  'Authorization: Bearer ' . $supabaseServiceRoleKey,
];

$resetResponse = authHttpJsonRequest(
  'PUT',
  $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($userId),
  $headers,
  [
    'password' => $password,
    'email_confirm' => true,
  ]
);

if (!isSuccessful($resetResponse)) {
  header('Location: reset-password.php?error=reset_failed');
  exit;
}

authHttpJsonRequest(
  'PATCH',
  $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($userId),
  array_merge($headers, ['Prefer: return=minimal']),
  [
    'must_change_password' => false,
    'failed_login_count' => 0,
    'lockout_until' => null,
    'updated_at' => gmdate('c'),
  ]
);

authHttpJsonRequest(
  'POST',
  $supabaseUrl . '/rest/v1/login_audit_logs',
  array_merge($headers, ['Prefer: return=minimal']),
  [[
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'password_reset_success',
    'metadata' => ['source' => 'forgot_password'],
  ]]
);

authHttpJsonRequest(
  'POST',
  $supabaseUrl . '/rest/v1/activity_logs',
  array_merge($headers, ['Prefer: return=minimal']),
  [[
    'actor_user_id' => $userId,
    'module_name' => 'auth',
    'entity_name' => 'user_accounts',
    'entity_id' => $userId,
    'action_name' => 'forgot_password_reset',
    'old_data' => null,
    'new_data' => ['password_reset' => true],
    'ip_address' => authClientIp(),
  ]]
);

unset($_SESSION['forgot_password_reset']);

header('Location: login.php?reset=1');
exit;
