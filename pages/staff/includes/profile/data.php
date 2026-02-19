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
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no,user_id&user_id=eq.' . rawurlencode($staffUserId) . '&limit=1',
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
    'role_name' => cleanText($roleRow['role']['role_name'] ?? null) ?? cleanText($staffRoleName ?? null) ?? 'Staff',
    'office_name' => cleanText($roleRow['office']['office_name'] ?? null) ?? cleanText($staffOfficeName ?? null) ?? 'Organization Scope',
    'account_status' => $accountStatusLabel,
    'account_status_class' => $accountStatusClass,
    'last_login' => formatDateTimeForPhilippines(cleanText($accountRow['last_login_at'] ?? null), 'M d, Y · h:i A'),
    'member_since' => formatDateTimeForPhilippines(cleanText($accountRow['created_at'] ?? null), 'M d, Y'),
];