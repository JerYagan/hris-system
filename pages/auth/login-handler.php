<?php
session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: login.php');
  exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  header('Location: login.php?error=invalid');
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

function http_json_request(string $method, string $url, array $headers, ?array $body = null): array
{
  $ch = curl_init($url);
  $requestHeaders = array_merge(['Content-Type: application/json'], $headers);

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $requestHeaders,
    CURLOPT_TIMEOUT => 20,
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

function log_login_event(string $supabaseUrl, ?string $serviceRoleKey, array $payload): void
{
  if (!$serviceRoleKey) {
    return;
  }

  http_json_request(
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
load_env_file_if_present($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(env_value('SUPABASE_URL') ?? ''), '/');
$supabaseAnonKey = env_value('SUPABASE_ANON_KEY');
$supabaseServiceRoleKey = env_value('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$supabaseAnonKey || !$supabaseServiceRoleKey) {
  header('Location: login.php?error=config');
  exit;
}

$authResponse = http_json_request(
  'POST',
  $supabaseUrl . '/auth/v1/token?grant_type=password',
  [
    'apikey: ' . $supabaseAnonKey,
  ],
  [
    'email' => $email,
    'password' => $password,
  ]
);

$authUser = $authResponse['data']['user'] ?? null;
$accessToken = $authResponse['data']['access_token'] ?? null;

if ($authResponse['status'] !== 200 || !is_array($authUser) || !$accessToken) {
  log_login_event($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => null,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'login_failed',
    'metadata' => ['reason' => 'invalid_credentials'],
  ]);

  header('Location: login.php?error=invalid');
  exit;
}

$userId = (string)($authUser['id'] ?? '');
$userEmail = (string)($authUser['email'] ?? $email);

$accountResponse = http_json_request(
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

$roleResponse = http_json_request(
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

session_regenerate_id(true);

http_json_request(
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
