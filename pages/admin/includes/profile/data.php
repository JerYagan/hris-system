<?php

$accountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,username,mobile_no,account_status,last_login_at,created_at&id=eq.' . $adminUserId . '&limit=1',
    $headers
);

$personResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no,user_id&user_id=eq.' . $adminUserId . '&limit=1',
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
    'display_name' => $displayName,
    'email' => (string)($accountRow['email'] ?? ''),
    'username' => (string)($accountRow['username'] ?? ''),
    'mobile_no' => (string)($personRow['mobile_no'] ?? $accountRow['mobile_no'] ?? ''),
    'personal_email' => (string)($personRow['personal_email'] ?? ''),
    'first_name' => $firstName,
    'middle_name' => $middleName,
    'surname' => $surname,
    'name_extension' => $nameExtension,
    'role_name' => $roleName,
    'office_name' => $officeName,
    'account_status' => $accountStatusLabel,
    'account_status_class' => $accountStatusClass,
    'last_login_at' => !empty($accountRow['last_login_at']) ? date('M d, Y h:i A', strtotime((string)$accountRow['last_login_at'])) : 'No login activity yet',
    'member_since' => !empty($accountRow['created_at']) ? date('M d, Y', strtotime((string)$accountRow['created_at'])) : '-',
];

$loginHistoryRows = [];
foreach ($loginHistoryRowsRaw as $entry) {
    $eventType = (string)($entry['event_type'] ?? 'unknown');
    $eventLabel = ucwords(str_replace('_', ' ', $eventType));
    $createdAt = (string)($entry['created_at'] ?? '');

    $loginHistoryRows[] = [
        'event_type' => $eventType,
        'event_label' => $eventLabel,
        'auth_provider' => (string)($entry['auth_provider'] ?? 'password'),
        'ip_address' => (string)($entry['ip_address'] ?? 'unknown'),
        'user_agent' => (string)($entry['user_agent'] ?? '-'),
        'created_at' => $createdAt !== '' ? date('M d, Y h:i A', strtotime($createdAt)) : '-',
        'search_text' => strtolower(trim($eventLabel . ' ' . ((string)($entry['auth_provider'] ?? '')) . ' ' . ((string)($entry['ip_address'] ?? '')) . ' ' . ((string)($entry['user_agent'] ?? '')))),
    ];
}
