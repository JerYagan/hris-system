<?php

require_once __DIR__ . '/includes/auth-support.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: login.php');
  exit;
}

$rememberMe = true;
authStartSession($rememberMe);

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  header('Location: login.php?error=invalid');
  exit;
}

$rootDir = dirname(__DIR__, 2);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$supabaseAnonKey = authEnvValue('SUPABASE_ANON_KEY');
$supabaseServiceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseAnonKey || !$supabaseServiceRoleKey) {
  header('Location: login.php?error=config');
  exit;
}

$authResponse = authHttpJsonRequest(
  'POST',
  $supabaseUrl . '/auth/v1/token?grant_type=password',
  [
    'apikey: ' . $supabaseAnonKey,
    'Content-Type: application/json',
  ],
  [
    'email' => $email,
    'password' => $password,
  ]
);

$authUser = $authResponse['data']['user'] ?? null;
$accessToken = $authResponse['data']['access_token'] ?? null;

if ($authResponse['status'] !== 200 || !is_array($authUser) || !$accessToken) {
  $failureMetadata = [
    'reason' => 'invalid_credentials',
    'status' => (int)($authResponse['status'] ?? 0),
  ];

  $authResponseBody = trim((string)($authResponse['raw'] ?? ''));
  $curlError = trim((string)($authResponse['curl_error'] ?? ''));

  if ($authResponseBody !== '') {
    $failureMetadata['auth_response'] = $authResponseBody;
  }

  if ($curlError !== '') {
    $failureMetadata['curl_error'] = $curlError;
  }

  error_log('Login failure for ' . $email . ' | status=' . (string)($authResponse['status'] ?? 0) . ' | curl_error=' . ($curlError !== '' ? $curlError : 'none') . ' | response=' . ($authResponseBody !== '' ? $authResponseBody : 'empty'));

  authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => null,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => $failureMetadata,
  ]);

  header('Location: login.php?error=invalid');
  exit;
}

$userId = (string)($authUser['id'] ?? '');
$userEmail = (string)($authUser['email'] ?? $email);

$loginContext = authResolveLoginContext($supabaseUrl, $supabaseServiceRoleKey, $userId);

if (!($loginContext['ok'] ?? false) && ($loginContext['code'] ?? '') === 'inactive') {
  authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => ['reason' => 'account_not_active', 'account_status' => $loginContext['account_status'] ?? null],
  ]);

  header('Location: login.php?error=inactive');
  exit;
}

if (!($loginContext['ok'] ?? false)) {
  authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => ['reason' => 'no_role_assignment'],
  ]);

  header('Location: login.php?error=role');
  exit;
}

$roleKey = (string)($loginContext['role_key'] ?? '');
$redirectTo = (string)($loginContext['redirect_to'] ?? '../applicant/dashboard.php');
$displayName = (string)(
  $authUser['user_metadata']['full_name']
  ?? $authUser['user_metadata']['name']
  ?? $userEmail
);

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
  'email_attempted' => $email,
  'auth_provider' => 'password',
  'event_type' => 'login_success',
  'metadata' => [
    'role_key' => $roleKey,
    'mfa_bypassed' => true,
  ],
]);

authFinalizeLoginSession([
  'remember_me' => $rememberMe,
  'user' => [
    'id' => $userId,
    'email' => $userEmail,
    'name' => $displayName,
    'role_key' => $roleKey,
  ],
  'tokens' => [
    'access_token' => (string)$accessToken,
    'refresh_token' => (string)($authResponse['data']['refresh_token'] ?? ''),
    'expires_at' => (int)($authResponse['data']['expires_at'] ?? 0),
  ],
]);

unset($_SESSION[authPendingMfaSessionKey()]);
header('Location: ' . $redirectTo);
exit;
