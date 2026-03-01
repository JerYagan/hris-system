<?php

$announcementTargetEmployees = [];
$announcementTargetGroups = [];
$announcementTargetRoles = [];

$rolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=role_key,role_name&order=role_name.asc&limit=1000',
    $headers
);

if (isSuccessful($rolesResponse)) {
    foreach ((array)($rolesResponse['data'] ?? []) as $roleRow) {
        $role = (array)$roleRow;
        $roleKey = strtolower(trim((string)($role['role_key'] ?? '')));
        if ($roleKey === '') {
            continue;
        }

        $roleName = trim((string)($role['role_name'] ?? ''));
        $announcementTargetRoles[] = [
            'role_key' => $roleKey,
            'label' => $roleName !== '' ? $roleName : ucwords(str_replace('_', ' ', $roleKey)),
        ];
    }
}

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname&user_id=not.is.null&limit=10000',
    $headers
);

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id&is_current=eq.true&limit=10000',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_type,is_active&is_active=eq.true&limit=10000',
    $headers
);

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_key)&is_primary=eq.true&expires_at=is.null&limit=10000',
    $headers
);

$peopleRows = isSuccessful($peopleResponse) ? (array)($peopleResponse['data'] ?? []) : [];
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];
$officeRows = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];
$roleAssignmentRows = isSuccessful($roleAssignmentsResponse) ? (array)($roleAssignmentsResponse['data'] ?? []) : [];

$employeeRoleUserIds = [];
foreach ($roleAssignmentRows as $assignmentRow) {
    $assignment = (array)$assignmentRow;
    $userId = strtolower(trim((string)($assignment['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }

    $roleKey = strtolower(trim((string)($assignment['role']['role_key'] ?? '')));
    if ($roleKey === 'employee') {
        $employeeRoleUserIds[$userId] = true;
    }
}

$officeById = [];
foreach ($officeRows as $officeRow) {
    $office = (array)$officeRow;
    $officeId = strtolower(trim((string)($office['id'] ?? '')));
    if ($officeId === '') {
        continue;
    }
    $officeById[$officeId] = [
        'name' => trim((string)($office['office_name'] ?? '')),
        'type' => strtolower(trim((string)($office['office_type'] ?? ''))),
    ];
}

$officeIdByPersonId = [];
foreach ($employmentRows as $employmentRow) {
    $employment = (array)$employmentRow;
    $personId = strtolower(trim((string)($employment['person_id'] ?? '')));
    $officeId = strtolower(trim((string)($employment['office_id'] ?? '')));
    if ($personId === '' || $officeId === '') {
        continue;
    }
    $officeIdByPersonId[$personId] = $officeId;
}

$employeeCountByOfficeId = [];
foreach ($peopleRows as $personRow) {
    $person = (array)$personRow;

    $userId = strtolower(trim((string)($person['user_id'] ?? '')));
    if ($userId === '' || !isset($employeeRoleUserIds[$userId])) {
        continue;
    }

    $personId = strtolower(trim((string)($person['id'] ?? '')));
    $officeId = (string)($officeIdByPersonId[$personId] ?? '');

    if ($officeId !== '') {
        $employeeCountByOfficeId[$officeId] = (int)($employeeCountByOfficeId[$officeId] ?? 0) + 1;
    }

    $firstName = trim((string)($person['first_name'] ?? ''));
    $middleName = trim((string)($person['middle_name'] ?? ''));
    $surname = trim((string)($person['surname'] ?? ''));

    $fullNameParts = [];
    if ($surname !== '') {
        $fullNameParts[] = $surname . ',';
    }
    if ($firstName !== '') {
        $fullNameParts[] = $firstName;
    }
    if ($middleName !== '') {
        $fullNameParts[] = $middleName;
    }

    $nameLabel = trim(implode(' ', $fullNameParts));
    if ($nameLabel === '') {
        $nameLabel = 'Employee';
    }

    $officeLabel = '-';
    if ($officeId !== '' && isset($officeById[$officeId])) {
        $officeLabel = (string)($officeById[$officeId]['name'] ?: '-');
    }

    $announcementTargetEmployees[] = [
        'user_id' => $userId,
        'label' => $nameLabel . ' · ' . $officeLabel,
    ];
}

usort($announcementTargetEmployees, static function (array $a, array $b): int {
    return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
});

foreach ($officeById as $officeId => $officeData) {
    $employeeCount = (int)($employeeCountByOfficeId[$officeId] ?? 0);
    if ($employeeCount <= 0) {
        continue;
    }

    $officeName = trim((string)($officeData['name'] ?? ''));
    if ($officeName === '') {
        $officeName = 'Office';
    }

    $announcementTargetGroups[] = [
        'office_id' => $officeId,
        'label' => $officeName . ' (' . $employeeCount . ' employee' . ($employeeCount === 1 ? '' : 's') . ')',
    ];
}

usort($announcementTargetGroups, static function (array $a, array $b): int {
    return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
});

$announcementLogsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at,actor_user_id,actor:user_accounts(email)&module_name=eq.create_announcement&entity_name=eq.announcements&action_name=eq.publish_announcement&order=created_at.desc&limit=100',
    $headers
);

$announcementLogs = isSuccessful($announcementLogsResponse) ? (array)($announcementLogsResponse['data'] ?? []) : [];

$dataLoadError = null;
if (!isSuccessful($announcementLogsResponse)) {
    $dataLoadError = 'Announcement logs query failed (HTTP ' . (int)($announcementLogsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($announcementLogsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $dataLoadError .= ' ' . $raw;
    }
}

$announcementRows = [];
$totalPublished = 0;
$totalInAppSent = 0;
$totalEmailSent = 0;

foreach ($announcementLogs as $log) {
    $payload = (array)($log['new_data'] ?? []);
    $delivery = (array)($payload['delivery_summary'] ?? []);

    $inAppSent = (int)($delivery['in_app_sent'] ?? 0);
    $emailSent = (int)($delivery['email_sent'] ?? 0);
    $targetedUsers = (int)($delivery['targeted_users'] ?? 0);

    $totalPublished++;
    $totalInAppSent += $inAppSent;
    $totalEmailSent += $emailSent;

    $createdAt = (string)($log['created_at'] ?? '');
    $createdAtLabel = $createdAt !== '' ? date('M d, Y h:i A', strtotime($createdAt)) : '-';

    $announcementRows[] = [
        'title' => (string)($payload['title'] ?? 'Untitled Announcement'),
        'category' => ucfirst((string)($payload['category'] ?? 'announcement')),
        'audience' => ucfirst(str_replace('_', ' ', (string)($delivery['audience'] ?? 'all_users'))),
        'channel' => strtoupper((string)($delivery['channel'] ?? 'both')),
        'targeted_users' => $targetedUsers,
        'in_app_sent' => $inAppSent,
        'email_sent' => $emailSent,
        'created_at' => $createdAtLabel,
        'actor_email' => (string)($log['actor']['email'] ?? '-'),
    ];
}
