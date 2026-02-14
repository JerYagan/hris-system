<?php
session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  header('Location: register.php');
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
  header('Location: register.php?error=' . urlencode($code));
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

function normalize_role_key(string $rawRole): ?string
{
  $normalized = strtolower(trim($rawRole));
  $map = [
    'employee' => 'employee',
    'supervisor' => 'supervisor',
    'hr officer' => 'hr_officer',
    'hr_officer' => 'hr_officer',
  ];

  return $map[$normalized] ?? null;
}

function yes_no_to_bool(?string $value): ?bool
{
  $normalized = strtolower(trim((string)$value));
  if ($normalized === 'yes') {
    return true;
  }
  if ($normalized === 'no') {
    return false;
  }
  return null;
}

function map_education_level(?string $value): ?string
{
  $normalized = strtolower(trim((string)$value));
  $map = [
    'elementary' => 'elementary',
    'secondary' => 'secondary',
    'vocational/trade course' => 'vocational',
    'vocational' => 'vocational',
    'college' => 'college',
    'graduate studies' => 'graduate',
    'graduate' => 'graduate',
  ];

  return $map[$normalized] ?? null;
}

function get_client_ip(): ?string
{
  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
  if (!$ip) {
    return null;
  }

  $parts = explode(',', $ip);
  return trim($parts[0]);
}

function has_any_value(array $fields): bool
{
  foreach ($fields as $value) {
    if (clean_text((string)$value) !== null) {
      return true;
    }
  }

  return false;
}

function encrypt_sensitive_value(string $plainText, string $keyMaterial): ?string
{
  $plainText = trim($plainText);
  if ($plainText === '') {
    return null;
  }

  $key = hash('sha256', $keyMaterial, true);
  $iv = random_bytes(16);
  $cipherRaw = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

  if ($cipherRaw === false) {
    return null;
  }

  return base64_encode($iv . $cipherRaw);
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

function is_unique_violation(array $response): bool
{
  $code = (string)($response['data']['code'] ?? '');
  if ($code === '23505') {
    return true;
  }

  $raw = strtolower((string)($response['raw'] ?? ''));
  return str_contains($raw, 'duplicate key value violates unique constraint');
}

function response_mentions_field(array $response, string $fieldName): bool
{
  $needle = strtolower($fieldName);
  $details = strtolower((string)($response['data']['details'] ?? ''));
  $message = strtolower((string)($response['data']['message'] ?? ''));
  $raw = strtolower((string)($response['raw'] ?? ''));

  return str_contains($details, $needle)
    || str_contains($message, $needle)
    || str_contains($raw, $needle);
}

$email = strtolower((string)clean_text($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$rawRole = (string)($_POST['account_role'] ?? '');
$roleKey = normalize_role_key($rawRole);

$firstName = (string)clean_text($_POST['first_name'] ?? '');
$surname = (string)clean_text($_POST['surname'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  redirect_with_error('invalid_email');
}

if ($password === '' || strlen($password) < 8) {
  redirect_with_error('weak_password');
}

if ($password !== $confirmPassword) {
  redirect_with_error('password_mismatch');
}

if ($roleKey === null) {
  redirect_with_error('invalid_role');
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
      'role_requested' => $roleKey,
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
  $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.' . rawurlencode($roleKey) . '&limit=1',
  $commonHeaders
);

$roleId = (string)($roleResponse['data'][0]['id'] ?? '');
if ($roleId === '') {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('role_missing');
}

$officeResponse = http_json_request(
  'GET',
  $supabaseUrl . '/rest/v1/offices?select=id&office_code=eq.DA-ATI-CENTRAL&limit=1',
  $commonHeaders
);
$officeId = (string)($officeResponse['data'][0]['id'] ?? '');

$mobileNo = clean_text($_POST['mobile'] ?? null);

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
  redirect_with_error('account_failed');
}

$rolePayload = [
  'user_id' => $userId,
  'role_id' => $roleId,
  'is_primary' => true,
  'assigned_at' => gmdate('c'),
];
if ($officeId !== '') {
  $rolePayload['office_id'] = $officeId;
}

$roleAssignResponse = http_json_request(
  'POST',
  $supabaseUrl . '/rest/v1/user_role_assignments',
  array_merge($commonHeaders, ['Prefer: return=minimal']),
  [$rolePayload]
);

if ($roleAssignResponse['status'] < 200 || $roleAssignResponse['status'] >= 300) {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('role_assign_failed');
}

$personPayload = [
  'user_id' => $userId,
  'surname' => $surname,
  'first_name' => $firstName,
  'middle_name' => clean_text($_POST['middle_name'] ?? null),
  'name_extension' => clean_text($_POST['name_extension'] ?? null),
  'date_of_birth' => clean_text($_POST['date_of_birth'] ?? null),
  'place_of_birth' => clean_text($_POST['place_of_birth'] ?? null),
  'sex_at_birth' => strtolower((string)clean_text($_POST['sex_at_birth'] ?? null)),
  'civil_status' => clean_text($_POST['civil_status'] ?? null),
  'height_m' => clean_text($_POST['height_m'] ?? null),
  'weight_kg' => clean_text($_POST['weight_kg'] ?? null),
  'blood_type' => clean_text($_POST['blood_type'] ?? null),
  'citizenship' => clean_text($_POST['citizenship'] ?? null),
  'dual_citizenship' => yes_no_to_bool($_POST['dual_citizenship'] ?? null),
  'dual_citizenship_country' => clean_text($_POST['dual_country'] ?? null),
  'telephone_no' => clean_text($_POST['telephone'] ?? null),
  'mobile_no' => $mobileNo,
  'personal_email' => $email,
  'agency_employee_no' => clean_text($_POST['agency_employee_no'] ?? null),
];

if (!in_array($personPayload['sex_at_birth'], ['male', 'female'], true)) {
  $personPayload['sex_at_birth'] = null;
}

$personResponse = http_json_request(
  'POST',
  $supabaseUrl . '/rest/v1/people',
  array_merge($commonHeaders, ['Prefer: return=representation']),
  [$personPayload]
);

if ($personResponse['status'] < 200 || $personResponse['status'] >= 300) {
  if (is_unique_violation($personResponse) && response_mentions_field($personResponse, 'agency_employee_no')) {
    delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
    redirect_with_error('agency_employee_no_exists');
  }

  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('profile_failed');
}

$personId = (string)($personResponse['data'][0]['id'] ?? '');
if ($personId === '') {
  delete_auth_user($supabaseUrl, $supabaseServiceRoleKey, $userId);
  redirect_with_error('profile_failed');
}

$addresses = [];
if (has_any_value([
  $_POST['res_house'] ?? null,
  $_POST['res_street'] ?? null,
  $_POST['res_subdivision'] ?? null,
  $_POST['res_barangay'] ?? null,
  $_POST['res_city'] ?? null,
  $_POST['res_province'] ?? null,
  $_POST['res_zip'] ?? null,
])) {
  $addresses[] = [
    'person_id' => $personId,
    'address_type' => 'residential',
    'house_no' => clean_text($_POST['res_house'] ?? null),
    'street' => clean_text($_POST['res_street'] ?? null),
    'subdivision' => clean_text($_POST['res_subdivision'] ?? null),
    'barangay' => clean_text($_POST['res_barangay'] ?? null),
    'city_municipality' => clean_text($_POST['res_city'] ?? null),
    'province' => clean_text($_POST['res_province'] ?? null),
    'zip_code' => clean_text($_POST['res_zip'] ?? null),
    'is_primary' => true,
  ];
}

if (has_any_value([
  $_POST['perm_house'] ?? null,
  $_POST['perm_street'] ?? null,
  $_POST['perm_subdivision'] ?? null,
  $_POST['perm_barangay'] ?? null,
  $_POST['perm_city'] ?? null,
  $_POST['perm_province'] ?? null,
  $_POST['perm_zip'] ?? null,
])) {
  $addresses[] = [
    'person_id' => $personId,
    'address_type' => 'permanent',
    'house_no' => clean_text($_POST['perm_house'] ?? null),
    'street' => clean_text($_POST['perm_street'] ?? null),
    'subdivision' => clean_text($_POST['perm_subdivision'] ?? null),
    'barangay' => clean_text($_POST['perm_barangay'] ?? null),
    'city_municipality' => clean_text($_POST['perm_city'] ?? null),
    'province' => clean_text($_POST['perm_province'] ?? null),
    'zip_code' => clean_text($_POST['perm_zip'] ?? null),
  ];
}

if (!empty($addresses)) {
  http_json_request(
    'POST',
    $supabaseUrl . '/rest/v1/person_addresses',
    array_merge($commonHeaders, ['Prefer: return=minimal']),
    $addresses
  );
}

$govIdMap = [
  'umid' => $_POST['umid'] ?? null,
  'pagibig' => $_POST['pagibig'] ?? null,
  'philhealth' => $_POST['philhealth'] ?? null,
  'psn' => $_POST['psn'] ?? null,
  'tin' => $_POST['tin'] ?? null,
];

$govRows = [];
foreach ($govIdMap as $idType => $rawValue) {
  $value = clean_text((string)$rawValue);
  if ($value === null) {
    continue;
  }

  $encrypted = encrypt_sensitive_value($value, $supabaseServiceRoleKey);
  if ($encrypted === null) {
    continue;
  }

  $govRows[] = [
    'person_id' => $personId,
    'id_type' => $idType,
    'id_value_encrypted' => $encrypted,
    'last4' => substr(preg_replace('/\D+/', '', $value) ?: $value, -4),
  ];
}

if (!empty($govRows)) {
  http_json_request(
    'POST',
    $supabaseUrl . '/rest/v1/person_government_ids',
    array_merge($commonHeaders, ['Prefer: resolution=merge-duplicates,return=minimal']),
    $govRows
  );
}

$fatherValues = [
  'surname' => clean_text($_POST['father_surname'] ?? null),
  'first_name' => clean_text($_POST['father_first_name'] ?? null),
  'middle_name' => clean_text($_POST['father_middle_name'] ?? null),
  'extension_name' => clean_text($_POST['father_extension'] ?? null),
];
if (has_any_value($fatherValues)) {
  http_json_request(
    'POST',
    $supabaseUrl . '/rest/v1/person_parents',
    array_merge($commonHeaders, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
      'person_id' => $personId,
      'parent_type' => 'father',
      'surname' => $fatherValues['surname'],
      'first_name' => $fatherValues['first_name'],
      'middle_name' => $fatherValues['middle_name'],
      'extension_name' => $fatherValues['extension_name'],
    ]]
  );
}

$motherValues = [
  'surname' => clean_text($_POST['mother_surname'] ?? null),
  'first_name' => clean_text($_POST['mother_first_name'] ?? null),
  'middle_name' => clean_text($_POST['mother_middle_name'] ?? null),
];
if (has_any_value($motherValues)) {
  http_json_request(
    'POST',
    $supabaseUrl . '/rest/v1/person_parents',
    array_merge($commonHeaders, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
      'person_id' => $personId,
      'parent_type' => 'mother',
      'surname' => $motherValues['surname'],
      'first_name' => $motherValues['first_name'],
      'middle_name' => $motherValues['middle_name'],
    ]]
  );
}

$spouses = $_POST['spouses'] ?? [];
if (is_array($spouses)) {
  $spouseRows = [];
  foreach (array_values($spouses) as $index => $spouse) {
    if (!is_array($spouse)) {
      continue;
    }

    $surnameValue = clean_text($spouse['surname'] ?? null);
    $firstNameValue = clean_text($spouse['first_name'] ?? null);
    if ($surnameValue === null && $firstNameValue === null) {
      continue;
    }

    $spouseRows[] = [
      'person_id' => $personId,
      'surname' => $surnameValue,
      'first_name' => $firstNameValue,
      'middle_name' => clean_text($spouse['middle_name'] ?? null),
      'extension_name' => clean_text($spouse['extension'] ?? null),
      'occupation' => clean_text($spouse['occupation'] ?? null),
      'employer_business_name' => clean_text($spouse['employer'] ?? null),
      'business_address' => clean_text($spouse['business_address'] ?? null),
      'telephone_no' => clean_text($spouse['telephone'] ?? null),
      'sequence_no' => $index + 1,
    ];
  }

  if (!empty($spouseRows)) {
    http_json_request(
      'POST',
      $supabaseUrl . '/rest/v1/person_family_spouses',
      array_merge($commonHeaders, ['Prefer: return=minimal']),
      $spouseRows
    );
  }
}

$children = $_POST['children'] ?? [];
if (is_array($children)) {
  $childrenRows = [];
  foreach (array_values($children) as $index => $child) {
    if (!is_array($child)) {
      continue;
    }

    $fullNameValue = clean_text($child['name'] ?? null);
    if ($fullNameValue === null) {
      continue;
    }

    $childrenRows[] = [
      'person_id' => $personId,
      'full_name' => $fullNameValue,
      'birth_date' => clean_text($child['birth_date'] ?? null),
      'sequence_no' => $index + 1,
    ];
  }

  if (!empty($childrenRows)) {
    http_json_request(
      'POST',
      $supabaseUrl . '/rest/v1/person_family_children',
      array_merge($commonHeaders, ['Prefer: return=minimal']),
      $childrenRows
    );
  }
}

$educationRows = [];
$education = $_POST['education'] ?? [];
if (is_array($education)) {
  foreach (array_values($education) as $index => $row) {
    if (!is_array($row)) {
      continue;
    }

    $level = map_education_level($row['level'] ?? null);
    $school = clean_text($row['school'] ?? null);
    if ($level === null && $school === null) {
      continue;
    }

    if ($level === null) {
      continue;
    }

    $educationRows[] = [
      'person_id' => $personId,
      'education_level' => $level,
      'school_name' => $school,
      'course_degree' => clean_text($row['course'] ?? null),
      'period_from' => clean_text($row['from'] ?? null),
      'period_to' => clean_text($row['to'] ?? null),
      'highest_level_units' => clean_text($row['highest_level'] ?? null),
      'year_graduated' => clean_text($row['year_graduated'] ?? null),
      'honors_received' => clean_text($row['honors'] ?? null),
      'sequence_no' => $index + 1,
    ];
  }
}

if (!empty($educationRows)) {
  http_json_request(
    'POST',
    $supabaseUrl . '/rest/v1/person_educations',
    array_merge($commonHeaders, ['Prefer: return=minimal']),
    $educationRows
  );
}

$ipAddress = get_client_ip();
$userAgent = clean_text($_SERVER['HTTP_USER_AGENT'] ?? null);

http_json_request(
  'POST',
  $supabaseUrl . '/rest/v1/login_audit_logs',
  array_merge($commonHeaders, ['Prefer: return=minimal']),
  [[
    'user_id' => $userId,
    'email_attempted' => $email,
    'auth_provider' => 'password',
    'event_type' => 'register_success',
    'ip_address' => $ipAddress,
    'user_agent' => $userAgent,
    'metadata' => [
      'role_key' => $roleKey,
    ],
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
    'action_name' => 'register',
    'old_data' => null,
    'new_data' => [
      'email' => $email,
      'role_key' => $roleKey,
      'person_id' => $personId,
    ],
    'ip_address' => $ipAddress,
  ]]
);

header('Location: login.php?registered=1');
exit;
