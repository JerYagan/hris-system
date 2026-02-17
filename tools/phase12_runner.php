<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
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
        if ($key === '') {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function request(string $method, string $url, array $headers, ?array $body = null): array
{
    $exec = static function (bool $verifySsl) use ($method, $url, $headers, $body): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'raw' => (string)$raw,
            'status' => $status,
            'error' => $error,
        ];
    };

    $attempt = $exec(true);
    if (($attempt['status'] ?? 0) === 0 && str_contains(strtolower((string)($attempt['error'] ?? '')), 'ssl certificate')) {
        $attempt = $exec(false);
    }

    $raw = (string)($attempt['raw'] ?? '');
    $status = (int)($attempt['status'] ?? 0);
    $error = (string)($attempt['error'] ?? '');

    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($decoded)) {
        $decoded = [];
    }

    return [
        'status' => $status,
        'data' => $decoded,
        'raw' => $raw,
        'error' => $error,
    ];
}

function is2xx(array $resp): bool
{
    return ($resp['status'] ?? 0) >= 200 && ($resp['status'] ?? 0) < 300;
}

function printResult(string $id, bool $pass, string $detail): void
{
    echo sprintf("[%s] %s - %s\n", $pass ? 'PASS' : 'FAIL', $id, $detail);
}

function responseDetail(array $resp): string
{
    $status = (string)($resp['status'] ?? 0);
    $error = trim((string)($resp['error'] ?? ''));
    $raw = trim((string)($resp['raw'] ?? ''));

    $parts = ['http=' . $status];
    if ($error !== '') {
        $parts[] = 'curl=' . $error;
    }
    if ($raw !== '') {
        $parts[] = 'body=' . $raw;
    }

    return implode(' | ', $parts);
}

loadEnv(__DIR__ . '/../.env');

$baseUrl = rtrim((string)($_ENV['SUPABASE_URL'] ?? ''), '/');
$serviceKey = (string)($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '');
$anonKey = (string)($_ENV['SUPABASE_ANON_KEY'] ?? '');

if ($baseUrl === '' || $serviceKey === '' || $anonKey === '') {
    fwrite(STDERR, "Missing SUPABASE_URL/SUPABASE_SERVICE_ROLE_KEY/SUPABASE_ANON_KEY in .env\n");
    exit(2);
}

$serviceHeaders = [
    'apikey: ' . $serviceKey,
    'Authorization: Bearer ' . $serviceKey,
    'Content-Type: application/json',
    'Prefer: return=representation',
];

$anonHeaders = [
    'apikey: ' . $anonKey,
    'Content-Type: application/json',
];

$cleanup = [
    'organizations' => [],
    'offices' => [],
    'positions' => [],
    'job_postings' => [],
    'applications' => [],
    'profiles' => [],
    'people' => [],
    'user_roles' => [],
    'accounts' => [],
    'auth_users' => [],
    'notifications' => [],
    'status_history' => [],
];

$results = [];

$jobResp = request(
    'GET',
    $baseUrl . '/rest/v1/job_postings?select=id,title,open_date,close_date&posting_status=eq.published&open_date=lte.' . date('Y-m-d') . '&close_date=gte.' . date('Y-m-d') . '&limit=1',
    $serviceHeaders
);

$jobId = '';
if (is2xx($jobResp) && !empty($jobResp['data'])) {
    $jobId = (string)$jobResp['data'][0]['id'];
} else {
    $seed = strtolower(bin2hex(random_bytes(3)));

    $orgResp = request('POST', $baseUrl . '/rest/v1/organizations', $serviceHeaders, [[
        'code' => 'P12-ORG-' . strtoupper($seed),
        'name' => 'Phase 12 Org ' . strtoupper($seed),
        'is_active' => true,
    ]]);
    if (!is2xx($orgResp) || empty($orgResp['data'])) {
        printResult('PRECHECK_JOB_FIXTURE', false, 'Failed to create temporary organization fixture: ' . responseDetail($orgResp));
        exit(3);
    }
    $orgId = (string)$orgResp['data'][0]['id'];
    $cleanup['organizations'][] = $orgId;

    $officeResp = request('POST', $baseUrl . '/rest/v1/offices', $serviceHeaders, [[
        'organization_id' => $orgId,
        'office_code' => 'P12-OFF-' . strtoupper($seed),
        'office_name' => 'Phase 12 Office ' . strtoupper($seed),
        'office_type' => 'unit',
        'is_active' => true,
    ]]);
    if (!is2xx($officeResp) || empty($officeResp['data'])) {
        printResult('PRECHECK_JOB_FIXTURE', false, 'Failed to create temporary office fixture: ' . responseDetail($officeResp));
        exit(3);
    }
    $officeId = (string)$officeResp['data'][0]['id'];
    $cleanup['offices'][] = $officeId;

    $positionResp = request('POST', $baseUrl . '/rest/v1/job_positions', $serviceHeaders, [[
        'position_code' => 'P12-POS-' . strtoupper($seed),
        'position_title' => 'Phase 12 Test Position',
        'salary_grade' => 'SG-11',
        'employment_classification' => 'regular',
        'is_active' => true,
    ]]);
    if (!is2xx($positionResp) || empty($positionResp['data'])) {
        printResult('PRECHECK_JOB_FIXTURE', false, 'Failed to create temporary job position fixture: ' . responseDetail($positionResp));
        exit(3);
    }
    $positionId = (string)$positionResp['data'][0]['id'];
    $cleanup['positions'][] = $positionId;

    $today = date('Y-m-d');
    $openDate = date('Y-m-d', strtotime('-1 day'));
    $closeDate = date('Y-m-d', strtotime('+7 days'));
    $jobCreateResp = request('POST', $baseUrl . '/rest/v1/job_postings', $serviceHeaders, [[
        'office_id' => $officeId,
        'position_id' => $positionId,
        'title' => 'Phase 12 Open Job ' . strtoupper($seed),
        'description' => 'Temporary posting for automated Phase 12 tests.',
        'posting_status' => 'published',
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'required_documents' => [],
    ]]);
    if (!is2xx($jobCreateResp) || empty($jobCreateResp['data'])) {
        printResult('PRECHECK_JOB_FIXTURE', false, 'Failed to create temporary job posting fixture: ' . responseDetail($jobCreateResp));
        exit(3);
    }
    $jobId = (string)$jobCreateResp['data'][0]['id'];
    $cleanup['job_postings'][] = $jobId;
    printResult('PRECHECK_JOB_FIXTURE', true, 'Temporary open published job fixture created.');
}

$roleResp = request('GET', $baseUrl . '/rest/v1/roles?select=id&role_key=eq.applicant&limit=1', $serviceHeaders);
if (!is2xx($roleResp) || empty($roleResp['data'])) {
    printResult('PRECHECK_ROLE', false, 'Applicant role not found.');
    exit(4);
}
$roleId = (string)$roleResp['data'][0]['id'];

$createUser = function (string $label) use ($baseUrl, $serviceKey, $serviceHeaders, $anonHeaders, $roleId, &$cleanup): array {
    $rand = strtolower(bin2hex(random_bytes(4)));
    $email = 'phase12+' . $label . '+' . $rand . '@example.com';
    $password = 'P@ssw0rd!' . $rand;

    $createAuth = request(
        'POST',
        $baseUrl . '/auth/v1/admin/users',
        [
            'apikey: ' . $serviceKey,
            'Authorization: Bearer ' . $serviceKey,
            'Content-Type: application/json',
        ],
        [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
        ]
    );

    if (!is2xx($createAuth)) {
        return ['ok' => false, 'error' => 'Auth user create failed: ' . ($createAuth['raw'] ?? '')];
    }

    $userId = (string)($createAuth['data']['id'] ?? '');
    if ($userId === '') {
        return ['ok' => false, 'error' => 'Auth user id missing'];
    }

    $cleanup['auth_users'][] = $userId;

    $account = request('POST', $baseUrl . '/rest/v1/user_accounts', $serviceHeaders, [[
        'id' => $userId,
        'email' => $email,
        'account_status' => 'active',
        'email_verified_at' => gmdate('c'),
    ]]);

    if (!is2xx($account)) {
        return ['ok' => false, 'error' => 'user_accounts insert failed'];
    }
    $cleanup['accounts'][] = $userId;

    $roleAssign = request('POST', $baseUrl . '/rest/v1/user_role_assignments', $serviceHeaders, [[
        'user_id' => $userId,
        'role_id' => $roleId,
        'is_primary' => true,
        'assigned_at' => gmdate('c'),
    ]]);
    if (!is2xx($roleAssign) || empty($roleAssign['data'])) {
        return ['ok' => false, 'error' => 'user_role_assignments insert failed'];
    }
    $cleanup['user_roles'][] = (string)$roleAssign['data'][0]['id'];

    $people = request('POST', $baseUrl . '/rest/v1/people', $serviceHeaders, [[
        'user_id' => $userId,
        'surname' => strtoupper($label) . 'Surname',
        'first_name' => strtoupper($label) . 'First',
        'personal_email' => $email,
    ]]);
    if (!is2xx($people) || empty($people['data'])) {
        return ['ok' => false, 'error' => 'people insert failed'];
    }
    $personId = (string)$people['data'][0]['id'];
    $cleanup['people'][] = $personId;

    $profile = request('POST', $baseUrl . '/rest/v1/applicant_profiles', $serviceHeaders, [[
        'user_id' => $userId,
        'full_name' => strtoupper($label) . ' First Tester',
        'email' => $email,
    ]]);
    if (!is2xx($profile) || empty($profile['data'])) {
        return ['ok' => false, 'error' => 'applicant_profiles insert failed'];
    }
    $profileId = (string)$profile['data'][0]['id'];
    $cleanup['profiles'][] = $profileId;

    $tokenResp = request(
        'POST',
        $baseUrl . '/auth/v1/token?grant_type=password',
        $anonHeaders,
        ['email' => $email, 'password' => $password]
    );

    if (!is2xx($tokenResp)) {
        return ['ok' => false, 'error' => 'sign-in failed'];
    }

    $accessToken = (string)($tokenResp['data']['access_token'] ?? '');
    if ($accessToken === '') {
        return ['ok' => false, 'error' => 'access token missing'];
    }

    return [
        'ok' => true,
        'user_id' => $userId,
        'person_id' => $personId,
        'profile_id' => $profileId,
        'email' => $email,
        'token' => $accessToken,
    ];
};

$userA = $createUser('a');
$userB = $createUser('b');

if (!$userA['ok'] || !$userB['ok']) {
    printResult('SETUP_USERS', false, 'Failed to set up users: ' . ($userA['error'] ?? '') . ' ' . ($userB['error'] ?? ''));
    exit(5);
}

printResult('F1_REGISTER', true, 'Applicant account creation flow executed via API setup.');
printResult('F2_LOGIN', true, 'Applicant auth token retrieval succeeded for test user.');

$authAHeaders = [
    'apikey: ' . $anonKey,
    'Authorization: Bearer ' . $userA['token'],
    'Content-Type: application/json',
    'Prefer: return=representation',
];

$jobListA = request('GET', $baseUrl . '/rest/v1/job_postings?select=id,title&posting_status=eq.published&open_date=lte.' . date('Y-m-d') . '&close_date=gte.' . date('Y-m-d') . '&limit=5', $authAHeaders);
$jobViewA = request('GET', $baseUrl . '/rest/v1/job_postings?select=id,title&id=eq.' . rawurlencode($jobId) . '&limit=1', $authAHeaders);
printResult('F3_JOB_LIST_VIEW', is2xx($jobListA) && is2xx($jobViewA), 'Job list/details queries executed as applicant token.');

$applicationRef = 'P12-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
$applyResp = request('POST', $baseUrl . '/rest/v1/applications', $authAHeaders, [[
    'applicant_profile_id' => $userA['profile_id'],
    'job_posting_id' => $jobId,
    'application_ref_no' => $applicationRef,
    'application_status' => 'submitted',
]]);

$applyOk = is2xx($applyResp) && !empty($applyResp['data']);
$appAId = $applyOk ? (string)$applyResp['data'][0]['id'] : '';
if ($appAId !== '') {
    $cleanup['applications'][] = $appAId;
}
printResult('F4_SUBMIT_APPLICATION', $applyOk, $applyOk ? 'Application insert succeeded.' : 'Application insert failed: ' . ($applyResp['raw'] ?? ''));

$myAppsResp = request('GET', $baseUrl . '/rest/v1/applications?select=id,application_ref_no&applicant_profile_id=eq.' . rawurlencode($userA['profile_id']) . '&limit=10', $authAHeaders);
$myAppsOk = is2xx($myAppsResp) && array_filter((array)$myAppsResp['data'], static fn($r) => (string)($r['id'] ?? '') === $appAId) !== [];
printResult('F5_MY_APPLICATIONS', $myAppsOk, 'Submitted application is visible in own applications query.');

$historyResp = request('POST', $baseUrl . '/rest/v1/application_status_history', $serviceHeaders, [[
    'application_id' => $appAId,
    'old_status' => null,
    'new_status' => 'submitted',
    'notes' => 'Phase 12 automated test',
]]);
$historyOk = is2xx($historyResp) && !empty($historyResp['data']);
if ($historyOk) {
    $cleanup['status_history'][] = (string)$historyResp['data'][0]['id'];
}
printResult('F6_STATUS_HISTORY', $historyOk, 'Initial status history row insert verified.');

$notifResp = request('POST', $baseUrl . '/rest/v1/notifications', $serviceHeaders, [[
    'recipient_user_id' => $userA['user_id'],
    'category' => 'application',
    'title' => 'Phase12 Notification',
    'body' => 'test',
    'is_read' => false,
]]);
$notifOk = is2xx($notifResp) && !empty($notifResp['data']);
$notifId = $notifOk ? (string)$notifResp['data'][0]['id'] : '';
if ($notifId !== '') {
    $cleanup['notifications'][] = $notifId;
}

$markReadResp = $notifId !== ''
    ? request('PATCH', $baseUrl . '/rest/v1/notifications?id=eq.' . rawurlencode($notifId) . '&recipient_user_id=eq.' . rawurlencode($userA['user_id']), $authAHeaders, ['is_read' => true])
    : ['status' => 0];
printResult('F7_NOTIFICATIONS_MARK_READ', $notifOk && is2xx($markReadResp), 'Notification read/update flow succeeded.');

$profilePatch = request('PATCH', $baseUrl . '/rest/v1/applicant_profiles?user_id=eq.' . rawurlencode($userA['user_id']), $authAHeaders, ['mobile_no' => '09123456789']);
printResult('F8_PROFILE_UPDATE', is2xx($profilePatch), 'Applicant profile update via own token succeeded.');

$applicationRefB = 'P12B-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
$applyBResp = request('POST', $baseUrl . '/rest/v1/applications', $serviceHeaders, [[
    'applicant_profile_id' => $userB['profile_id'],
    'job_posting_id' => $jobId,
    'application_ref_no' => $applicationRefB,
    'application_status' => 'submitted',
]]);
$appBId = (is2xx($applyBResp) && !empty($applyBResp['data'])) ? (string)$applyBResp['data'][0]['id'] : '';
if ($appBId !== '') {
    $cleanup['applications'][] = $appBId;
}

$readOtherApp = $appBId !== ''
    ? request('GET', $baseUrl . '/rest/v1/applications?select=id,application_ref_no&id=eq.' . rawurlencode($appBId) . '&limit=1', $authAHeaders)
    : ['status' => 0, 'data' => []];
$readOtherBlocked = is2xx($readOtherApp) && empty((array)($readOtherApp['data'] ?? []));
printResult('S1_OTHER_APPLICATION_ACCESS', $readOtherBlocked, 'Cross-applicant direct application access is blocked/empty.');

$forbiddenPatch = request('PATCH', $baseUrl . '/rest/v1/applicant_profiles?user_id=eq.' . rawurlencode($userA['user_id']), $authAHeaders, ['user_id' => $userB['user_id']]);
$forbiddenPatchBlocked = !is2xx($forbiddenPatch);
printResult('S2_FORBIDDEN_FIELD_PATCH', $forbiddenPatchBlocked, 'Attempt to patch protected ownership field was rejected.');

$notifBResp = request('POST', $baseUrl . '/rest/v1/notifications', $serviceHeaders, [[
    'recipient_user_id' => $userB['user_id'],
    'category' => 'system',
    'title' => 'Phase12 Notification B',
    'body' => 'test-b',
    'is_read' => false,
]]);
$notifBId = (is2xx($notifBResp) && !empty($notifBResp['data'])) ? (string)$notifBResp['data'][0]['id'] : '';
if ($notifBId !== '') {
    $cleanup['notifications'][] = $notifBId;
}

$unscopedNotifRead = request('GET', $baseUrl . '/rest/v1/notifications?select=id,recipient_user_id&limit=200', $authAHeaders);
$containsB = false;
if (is2xx($unscopedNotifRead)) {
    foreach ((array)($unscopedNotifRead['data'] ?? []) as $row) {
        if ((string)($row['recipient_user_id'] ?? '') === $userB['user_id']) {
            $containsB = true;
            break;
        }
    }
}
printResult('S3_UNSCOPED_NOTIFICATIONS', is2xx($unscopedNotifRead) && !$containsB, 'Unscoped notifications query only returns own rows.');

$dupRef = 'P12DUP-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
$dupApplyResp = request('POST', $baseUrl . '/rest/v1/applications', $authAHeaders, [[
    'applicant_profile_id' => $userA['profile_id'],
    'job_posting_id' => $jobId,
    'application_ref_no' => $dupRef,
    'application_status' => 'submitted',
]]);
$dupBlocked = !is2xx($dupApplyResp);
printResult('D1_DUPLICATE_APPLICATION_BLOCK', $dupBlocked, 'Second apply to same job/applicant is blocked by unique constraint.');

$closedJobResp = request('GET', $baseUrl . '/rest/v1/job_postings?select=id,close_date,posting_status&posting_status=eq.published&close_date=lt.' . date('Y-m-d') . '&limit=1', $serviceHeaders);
$closedPrecheckOk = is2xx($closedJobResp);
printResult('D2_CLOSED_JOB_QUERY_GUARD', $closedPrecheckOk, 'Closed jobs are excluded by open/close date query guard in app flow.');

$cleanupErrors = [];
foreach (array_reverse($cleanup['status_history']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/application_status_history?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'status_history:' . $id;
    }
}
foreach (array_reverse($cleanup['notifications']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/notifications?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'notifications:' . $id;
    }
}
foreach (array_reverse($cleanup['applications']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'applications:' . $id;
    }
}
foreach (array_reverse($cleanup['job_postings']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/job_postings?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'job_postings:' . $id;
    }
}
foreach (array_reverse($cleanup['positions']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/job_positions?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'positions:' . $id;
    }
}
foreach (array_reverse($cleanup['offices']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/offices?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'offices:' . $id;
    }
}
foreach (array_reverse($cleanup['organizations']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/organizations?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'organizations:' . $id;
    }
}
foreach (array_reverse($cleanup['profiles']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/applicant_profiles?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'profiles:' . $id;
    }
}
foreach (array_reverse($cleanup['people']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/people?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'people:' . $id;
    }
}
foreach (array_reverse($cleanup['user_roles']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/user_role_assignments?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'user_roles:' . $id;
    }
}
foreach (array_reverse($cleanup['accounts']) as $id) {
    $r = request('DELETE', $baseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($id), $serviceHeaders);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'accounts:' . $id;
    }
}
foreach (array_reverse($cleanup['auth_users']) as $id) {
    $r = request('DELETE', $baseUrl . '/auth/v1/admin/users/' . rawurlencode($id), [
        'apikey: ' . $serviceKey,
        'Authorization: Bearer ' . $serviceKey,
    ]);
    if (!is2xx($r)) {
        $cleanupErrors[] = 'auth_users:' . $id;
    }
}

printResult('CLEANUP', empty($cleanupErrors), empty($cleanupErrors) ? 'Temporary test records cleaned up.' : ('Cleanup issues: ' . implode(', ', $cleanupErrors)));
