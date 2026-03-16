<?php

require_once __DIR__ . '/includes/auth-support.php';
require_once dirname(__DIR__) . '/admin/includes/notifications/email.php';

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
  authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
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
  'admin' => '../admin/dashboard.php',
  'hr_officer' => '../staff/dashboard.php',
  'supervisor' => '../staff/dashboard.php',
  'staff' => '../staff/dashboard.php',
  'employee' => '../employee/dashboard.php',
  'applicant' => '../applicant/dashboard.php',
];

if ($roleKey === '' || !isset($redirectMap[$roleKey])) {
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

if ($roleKey === 'admin') {
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
      'name' => (string)(
        $authUser['user_metadata']['full_name']
        ?? $authUser['user_metadata']['name']
        ?? $userEmail
      ),
      'role_key' => $roleKey,
    ],
    'tokens' => [
      'access_token' => (string)$accessToken,
      'refresh_token' => (string)($authResponse['data']['refresh_token'] ?? ''),
      'expires_at' => (int)($authResponse['data']['expires_at'] ?? 0),
    ],
  ]);

  unset($_SESSION[authPendingMfaSessionKey()]);
  header('Location: ' . $redirectMap[$roleKey]);
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
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'mfa_otp_issue_failed',
    'metadata' => [
      'purpose' => 'login',
      'reason' => 'smtp_config_not_ready',
      'smtp_host_present' => trim((string)($smtpConfig['host'] ?? '')) !== '',
      'smtp_port' => (int)($smtpConfig['port'] ?? 0),
      'smtp_auth' => (string)($smtpConfig['auth'] ?? '1'),
      'mail_from_present' => trim($mailFrom) !== '',
    ],
  ]);
  header('Location: login.php?error=mfa_config_missing');
  exit;
}

$verificationCode = authGenerateOtpCode();
$displayName = (string)(
  $authUser['user_metadata']['full_name']
  ?? $authUser['user_metadata']['name']
  ?? $userEmail
);
$pendingMfa = authStorePendingMfaChallenge([
  'purpose' => 'login',
  'email' => $userEmail,
  'user_id' => $userId,
  'remember_me' => $rememberMe,
  'redirect_to' => $redirectMap[$roleKey],
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
], $verificationCode);

$safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
$safeExpiry = htmlspecialchars(hrisEmailFormatPhilippinesTimestamp((int)$pendingMfa['expires_at']), ENT_QUOTES, 'UTF-8');

$mailResponse = smtpSendTransactionalEmail(
  $smtpConfig,
  $mailFrom,
  $mailFromName,
  $userEmail,
  $displayName,
  'ATI HRIS Portal Login Verification Code',
  '<p>Hello,</p>'
    . '<p>Use the verification code below to complete your ATI HRIS Portal sign-in.</p>'
    . '<p><strong>Verification Code</strong><br>'
    . '<span style="display:inline-block;margin-top:8px;font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</span></p>'
    . '<p>This code expires on <strong>' . $safeExpiry . '</strong>.</p>'
    . '<p>If you did not attempt to sign in, you can ignore this email and consider changing your password.</p>'
);

if (!isSuccessful($mailResponse)) {
  unset($_SESSION[authPendingMfaSessionKey()]);
  $mailFailure = trim((string)($mailResponse['raw'] ?? ''));
  if ($mailFailure !== '') {
    error_log('Login MFA email send failed for ' . $userEmail . ': ' . $mailFailure);
  }
  authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'mfa_otp_issue_failed',
    'metadata' => [
      'purpose' => 'login',
      'reason' => 'smtp_send_failed',
      'mail_response' => $mailFailure,
    ],
  ]);
  header('Location: login.php?error=mfa_send_failed');
  exit;
}

authLogLoginAuditEvent($supabaseUrl, $supabaseServiceRoleKey, [
  'user_id' => $userId,
  'email_attempted' => $email,
  'auth_provider' => 'password',
  'event_type' => 'mfa_otp_issued',
  'metadata' => [
    'purpose' => 'login',
    'channel' => (string)($pendingMfa['channel'] ?? 'email'),
    'role_key' => $roleKey,
  ],
]);

header('Location: mfa-verify.php?mode=login&sent=1');
exit;
