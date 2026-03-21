<?php

$dataLoadError = null;

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};

$accountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,username,mobile_no,account_status,last_login_at,created_at&id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$appendDataError('User account', $accountResponse);
$accountRow = isSuccessful($accountResponse) ? ($accountResponse['data'][0] ?? null) : null;

$personResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no,profile_photo_url,user_id&user_id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$appendDataError('Person profile', $personResponse);
$personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;

$roleResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/user_role_assignments?select=id,is_primary,role:roles(role_name),office:offices(office_name)'
    . '&user_id=eq.' . rawurlencode($staffUserId)
    . '&is_primary=eq.true&limit=1',
    $headers
);
$appendDataError('Role assignment', $roleResponse);
$roleRow = isSuccessful($roleResponse) ? ($roleResponse['data'][0] ?? null) : null;

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'active' => ['Active', 'text-emerald-700'],
        'locked' => ['Locked', 'text-rose-700'],
        'inactive' => ['Inactive', 'text-slate-700'],
        default => [ucfirst($key !== '' ? $key : 'Pending'), 'text-amber-700'],
    };
};

$firstName = cleanText($personRow['first_name'] ?? null) ?? '';
$middleName = cleanText($personRow['middle_name'] ?? null) ?? '';
$surname = cleanText($personRow['surname'] ?? null) ?? '';
$nameExtension = cleanText($personRow['name_extension'] ?? null) ?? '';
$displayName = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $nameExtension);
if ($displayName === '') {
    $displayName = 'Staff User';
}

[$accountStatusLabel, $accountStatusClass] = $statusPill((string)(cleanText($accountRow['account_status'] ?? null) ?? 'pending'));

$profileSummary = [
    'person_id' => cleanText($personRow['id'] ?? null) ?? '',
    'display_name' => $displayName,
    'first_name' => $firstName,
    'middle_name' => $middleName,
    'surname' => $surname,
    'name_extension' => $nameExtension,
    'email' => cleanText($accountRow['email'] ?? null) ?? '',
    'username' => cleanText($accountRow['username'] ?? null) ?? '',
    'mobile_no' => cleanText($personRow['mobile_no'] ?? null) ?? cleanText($accountRow['mobile_no'] ?? null) ?? '',
    'personal_email' => cleanText($personRow['personal_email'] ?? null) ?? '',
    'profile_photo_url' => cleanText($personRow['profile_photo_url'] ?? null) ?? '',
    'role_name' => cleanText($roleRow['role']['role_name'] ?? null) ?? cleanText($staffRoleName ?? null) ?? 'Staff',
    'office_name' => cleanText($roleRow['office']['office_name'] ?? null) ?? cleanText($staffOfficeName ?? null) ?? 'Organization Scope',
    'account_status' => $accountStatusLabel,
    'account_status_class' => $accountStatusClass,
    'last_login' => formatDateTimeForPhilippines(cleanText($accountRow['last_login_at'] ?? null), 'M d, Y · h:i A'),
    'member_since' => formatDateTimeForPhilippines(cleanText($accountRow['created_at'] ?? null), 'M d, Y'),
];

$rawProfilePhotoPath = trim((string)($profileSummary['profile_photo_url'] ?? ''));
$profileSummary['resolved_profile_photo_url'] = $rawProfilePhotoPath === ''
    ? ''
    : (str_starts_with($rawProfilePhotoPath, 'http://') || str_starts_with($rawProfilePhotoPath, 'https://') || str_starts_with($rawProfilePhotoPath, '/')
        ? $rawProfilePhotoPath
        : '/hris-system/storage/document/' . ltrim($rawProfilePhotoPath, '/'));

$passwordChangePending = (array)($_SESSION['staff_profile_password_change'] ?? []);
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

$loginHistoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/login_audit_logs?select=id,event_type,auth_provider,ip_address,user_agent,created_at&user_id=eq.' . rawurlencode($staffUserId) . '&order=created_at.desc&limit=500',
    $headers
);

$loginHistoryRowsRaw = isSuccessful($loginHistoryResponse) ? (array)($loginHistoryResponse['data'] ?? []) : [];

$latestSuccessfulLoginAt = '';
foreach ($loginHistoryRowsRaw as $loginHistoryRow) {
    $eventType = strtolower(trim((string)($loginHistoryRow['event_type'] ?? '')));
    $createdAt = trim((string)($loginHistoryRow['created_at'] ?? ''));
    if ($eventType === 'login_success' && $createdAt !== '') {
        $latestSuccessfulLoginAt = $createdAt;
        break;
    }
}

if ($latestSuccessfulLoginAt !== '') {
    $profileSummary['last_login'] = formatDateTimeForPhilippines($latestSuccessfulLoginAt, 'M d, Y · h:i A');
}

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