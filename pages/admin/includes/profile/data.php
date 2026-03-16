<?php

$accountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,username,mobile_no,account_status,last_login_at,created_at&id=eq.' . $adminUserId . '&limit=1',
    $headers
);

$personResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no,profile_photo_url,user_id&user_id=eq.' . $adminUserId . '&limit=1',
    $headers
);

$roleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=id,is_primary,role:role_id(role_name),office:office_id(office_name)&user_id=eq.' . $adminUserId . '&is_primary=eq.true&limit=1',
    $headers
);

$loginHistoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/login_audit_logs?select=id,event_type,auth_provider,ip_address,user_agent,created_at&user_id=eq.' . $adminUserId . '&order=created_at.desc&limit=500',
    $headers
);

$accountRow = (isSuccessful($accountResponse) ? ($accountResponse['data'][0] ?? null) : null);
$personRow = (isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null);
$roleRow = (isSuccessful($roleResponse) ? ($roleResponse['data'][0] ?? null) : null);
$loginHistoryRowsRaw = isSuccessful($loginHistoryResponse) ? (array)($loginHistoryResponse['data'] ?? []) : [];

$firstName = (string)($personRow['first_name'] ?? '');
$middleName = (string)($personRow['middle_name'] ?? '');
$surname = (string)($personRow['surname'] ?? '');
$nameExtension = (string)($personRow['name_extension'] ?? '');

$displayNameParts = array_filter([$firstName, $middleName, $surname, $nameExtension], static fn(string $part): bool => trim($part) !== '');
$displayName = !empty($displayNameParts) ? implode(' ', $displayNameParts) : 'Admin User';

$roleName = (string)($roleRow['role']['role_name'] ?? 'Administrator');
$officeName = (string)($roleRow['office']['office_name'] ?? 'Organization Scope');

$accountStatusRaw = (string)($accountRow['account_status'] ?? 'pending');
[$accountStatusLabel, $accountStatusClass] = toStatusPill($accountStatusRaw);

$profileSummary = [
    'person_id' => (string)($personRow['id'] ?? ''),
    'display_name' => $displayName,
    'email' => (string)($accountRow['email'] ?? ''),
    'username' => (string)($accountRow['username'] ?? ''),
    'mobile_no' => (string)($personRow['mobile_no'] ?? $accountRow['mobile_no'] ?? ''),
    'personal_email' => (string)($personRow['personal_email'] ?? ''),
    'profile_photo_url' => (string)($personRow['profile_photo_url'] ?? ''),
    'first_name' => $firstName,
    'middle_name' => $middleName,
    'surname' => $surname,
    'name_extension' => $nameExtension,
    'role_name' => $roleName,
    'office_name' => $officeName,
    'account_status' => $accountStatusLabel,
    'account_status_class' => $accountStatusClass,
    'last_login_at' => !empty($accountRow['last_login_at']) ? formatDateTimeForPhilippines((string)$accountRow['last_login_at'], 'M d, Y h:i A') . ' PST' : 'No login activity yet',
    'member_since' => !empty($accountRow['created_at']) ? formatDateTimeForPhilippines((string)$accountRow['created_at'], 'M d, Y') : '-',
];

$rawProfilePhotoPath = trim((string)($profileSummary['profile_photo_url'] ?? ''));
$profileSummary['resolved_profile_photo_url'] = $rawProfilePhotoPath === ''
    ? ''
    : (str_starts_with($rawProfilePhotoPath, 'http://') || str_starts_with($rawProfilePhotoPath, 'https://') || str_starts_with($rawProfilePhotoPath, '/')
        ? $rawProfilePhotoPath
        : '/hris-system/storage/document/' . ltrim($rawProfilePhotoPath, '/'));

$csrfToken = ensureCsrfToken();

$resolveDeviceLabel = static function (string $userAgent): string {
    $agent = strtolower(trim($userAgent));
    if ($agent === '' || $agent === '-') {
        return 'Unknown Device';
    }

    if (str_contains($agent, 'bot') || str_contains($agent, 'spider') || str_contains($agent, 'crawler')) {
        return 'Bot / Script';
    }

    if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
        return 'Tablet';
    }

    if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
        return 'Mobile';
    }

    return 'Desktop';
};

$loginSearchQuery = strtolower(trim((string)($_GET['login_search'] ?? '')));
$loginEventFilter = trim((string)($_GET['login_event'] ?? ''));
$loginDeviceFilter = trim((string)($_GET['login_device'] ?? ''));
$loginPage = max(1, (int)($_GET['login_page'] ?? 1));
$loginPerPage = 10;

$loginEventOptions = [];
$loginDeviceOptions = [];

$loginHistoryRows = [];
foreach ($loginHistoryRowsRaw as $entry) {
    $eventType = (string)($entry['event_type'] ?? 'unknown');
    $eventLabel = ucwords(str_replace('_', ' ', $eventType));
    $createdAt = (string)($entry['created_at'] ?? '');

    $userAgent = (string)($entry['user_agent'] ?? '-');
    $deviceLabel = $resolveDeviceLabel($userAgent);

    if ($eventLabel !== '') {
        $loginEventOptions[$eventLabel] = true;
    }
    $loginDeviceOptions[$deviceLabel] = true;

    $loginHistoryRows[] = [
        'event_type' => $eventType,
        'event_label' => $eventLabel,
        'auth_provider' => (string)($entry['auth_provider'] ?? 'password'),
        'ip_address' => (string)($entry['ip_address'] ?? 'unknown'),
        'user_agent' => $userAgent,
        'device_label' => $deviceLabel,
        'created_at' => $createdAt !== '' ? formatDateTimeForPhilippines($createdAt, 'M d, Y h:i A') . ' PST' : '-',
        'search_text' => strtolower(trim($eventLabel . ' ' . ((string)($entry['auth_provider'] ?? '')) . ' ' . ((string)($entry['ip_address'] ?? '')) . ' ' . $userAgent . ' ' . $deviceLabel)),
    ];
}

$loginHistoryRowsFiltered = array_values(array_filter(
    $loginHistoryRows,
    static function (array $row) use ($loginSearchQuery, $loginEventFilter, $loginDeviceFilter): bool {
        if ($loginEventFilter !== '' && (string)($row['event_label'] ?? '') !== $loginEventFilter) {
            return false;
        }

        if ($loginDeviceFilter !== '' && (string)($row['device_label'] ?? '') !== $loginDeviceFilter) {
            return false;
        }

        if ($loginSearchQuery !== '' && !str_contains((string)($row['search_text'] ?? ''), $loginSearchQuery)) {
            return false;
        }

        return true;
    }
));

$loginHistoryTotal = count($loginHistoryRowsFiltered);
$loginTotalPages = max(1, (int)ceil($loginHistoryTotal / $loginPerPage));
$loginPage = min($loginPage, $loginTotalPages);
$loginOffset = ($loginPage - 1) * $loginPerPage;
$loginHistoryRows = array_slice($loginHistoryRowsFiltered, $loginOffset, $loginPerPage);

$loginEventOptions = array_keys($loginEventOptions);
sort($loginEventOptions);
$loginDeviceOptions = array_keys($loginDeviceOptions);
sort($loginDeviceOptions);

$passwordChangePending = (array)($_SESSION['admin_profile_password_change'] ?? []);
$passwordChangeStatus = [
    'is_pending' => false,
    'expires_at' => '-',
    'email' => '',
];

$pendingExpiresAt = (int)($passwordChangePending['expires_at'] ?? 0);
if ($pendingExpiresAt > time()) {
    $passwordChangeStatus['is_pending'] = true;
    $passwordChangeStatus['expires_at'] = formatUnixTimestampForPhilippines($pendingExpiresAt, 'M d, Y h:i A') . ' PST';
    $passwordChangeStatus['email'] = (string)($passwordChangePending['email'] ?? '');
}
