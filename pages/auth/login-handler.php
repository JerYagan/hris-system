<?php

require_once __DIR__ . '/includes/auth-support.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: login.php');
  exit;
}

$rememberMe = authShouldRememberSession();
authStartSession($rememberMe);

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  header('Location: login.php?error=invalid');
  exit;
}

function log_login_event(string $supabaseUrl, ?string $serviceRoleKey, array $payload): void
{
  if (!$serviceRoleKey) {
    return;
  }

  authHttpJsonRequest(
    'POST',
    $supabaseUrl . '/rest/v1/login_audit_logs',
    [
      'apikey: ' . $serviceRoleKey,
      'Authorization: Bearer ' . $serviceRoleKey,
      'Prefer: return=minimal',
    ],
    $payload
  );
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

  log_login_event($supabaseUrl, $supabaseServiceRoleKey, [
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

$accountResponse = authHttpJsonRequest(
  'GET',
  $supabaseUrl . '/rest/v1/user_accounts?select=account_status&id=eq.' . $userId . '&limit=1',
  [
    'apikey: ' . $supabaseServiceRoleKey,
    'Authorization: Bearer ' . $supabaseServiceRoleKey,
  ]
);

$accountStatus = $accountResponse['data'][0]['account_status'] ?? null;
if ($accountStatus !== null && $accountStatus !== 'active') {
  log_login_event($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => ['reason' => 'account_not_active', 'account_status' => $accountStatus],
  ]);

  header('Location: login.php?error=inactive');
  exit;
}

$roleResponse = authHttpJsonRequest(
  'GET',
  $supabaseUrl . '/rest/v1/user_role_assignments?select=is_primary,role:roles(role_key)&user_id=eq.' . $userId . '&expires_at=is.null&order=is_primary.desc&limit=1',
  [
    'apikey: ' . $supabaseServiceRoleKey,
    'Authorization: Bearer ' . $supabaseServiceRoleKey,
  ]
);

$roleKey = strtolower((string)($roleResponse['data'][0]['role']['role_key'] ?? ''));
$redirectMap = [
  'admin' => '/hris-system/pages/admin/dashboard.php',
  'hr_officer' => '/hris-system/pages/staff/dashboard.php',
  'supervisor' => '/hris-system/pages/staff/dashboard.php',
  'staff' => '/hris-system/pages/staff/dashboard.php',
  'employee' => '/hris-system/pages/employee/dashboard.php',
  'applicant' => '/hris-system/pages/applicant/dashboard.php',
];

if ($roleKey === '' || !isset($redirectMap[$roleKey])) {
  log_login_event($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => ['reason' => 'no_role_assignment'],
  ]);

  header('Location: login.php?error=role');
  exit;
}

$_SESSION['user'] = [
  'id' => $userId,
  'email' => $userEmail,
  'name' => (string)(
    $authUser['user_metadata']['full_name']
    ?? $authUser['user_metadata']['name']
    ?? $userEmail
  ),
  'role_key' => $roleKey,
];

$_SESSION['supabase'] = [
  'access_token' => (string)$accessToken,
  'refresh_token' => (string)($authResponse['data']['refresh_token'] ?? ''),
  'expires_at' => (int)($authResponse['data']['expires_at'] ?? 0),
];
$_SESSION['remember_me'] = $rememberMe;

session_regenerate_id(true);
authSyncRememberMeCookie($rememberMe);

authHttpJsonRequest(
  'PATCH',
  $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
  [
    'apikey: ' . $supabaseServiceRoleKey,
    'Authorization: Bearer ' . $supabaseServiceRoleKey,
    'Prefer: return=minimal',
  ],
  ['last_login_at' => gmdate('c')]
);

log_login_event($supabaseUrl, $supabaseServiceRoleKey, [
  'user_id' => $userId,
  'email_attempted' => $email,
  'auth_provider' => 'password',
  'event_type' => 'login_success',
  'metadata' => ['role_key' => $roleKey],
]);

header('Location: ' . $redirectMap[$roleKey]);
exit;
