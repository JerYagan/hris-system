<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$staffOfficeIdForScope = cleanText($staffOfficeId ?? null) ?? '';

$resolveScopedEmployment = static function (string $employmentId, string $personId) use ($supabaseUrl, $headers, $isAdminScope, $staffOfficeIdForScope): ?array {
    if (!isValidUuid($employmentId) || !isValidUuid($personId)) {
        return null;
    }

    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,is_current,separation_date'
        . '&id=eq.' . rawurlencode($employmentId)
        . '&person_id=eq.' . rawurlencode($personId)
        . '&is_current=eq.true&limit=1',
        $headers
    );

    $row = isSuccessful($response) ? ($response['data'][0] ?? null) : null;
    if (!is_array($row)) {
        return null;
    }

    if (!$isAdminScope) {
        $targetOfficeId = cleanText($row['office_id'] ?? null) ?? '';
        if (!isValidUuid($staffOfficeIdForScope) || strcasecmp($targetOfficeId, $staffOfficeIdForScope) !== 0) {
            return null;
        }
    }

    return $row;
};

if ($action === 'update_employee_profile') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $employmentId = cleanText($_POST['employment_id'] ?? null) ?? '';
    $firstName = trim((string)(cleanText($_POST['first_name'] ?? null) ?? ''));
    $middleName = trim((string)(cleanText($_POST['middle_name'] ?? null) ?? ''));
    $surname = trim((string)(cleanText($_POST['surname'] ?? null) ?? ''));
    $nameExtension = trim((string)(cleanText($_POST['name_extension'] ?? null) ?? ''));
    $personalEmail = strtolower(trim((string)(cleanText($_POST['personal_email'] ?? null) ?? '')));
    $mobileNo = trim((string)(cleanText($_POST['mobile_no'] ?? null) ?? ''));

    if ($firstName === '' || $surname === '') {
        redirectWithState('error', 'First name and surname are required.');
    }

    if ($personalEmail !== '' && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Please enter a valid personal email.');
    }

    if ($mobileNo !== '' && !preg_match('/^\+?[0-9][0-9\s-]{6,19}$/', $mobileNo)) {
        redirectWithState('error', 'Please enter a valid mobile number.');
    }

    $employmentRow = $resolveScopedEmployment($employmentId, $personId);
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'Employee scope validation failed.');
    }

    $personLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname,name_extension,personal_email,mobile_no&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personLookup) ? ($personLookup['data'][0] ?? null) : null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile not found.');
    }

    $employeeUserId = cleanText($personRow['user_id'] ?? null) ?? '';

    $patchPayload = [
        'first_name' => $firstName,
        'middle_name' => $middleName !== '' ? $middleName : null,
        'surname' => $surname,
        'name_extension' => $nameExtension !== '' ? $nameExtension : null,
        'personal_email' => $personalEmail !== '' ? $personalEmail : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'updated_at' => gmdate('c'),
    ];

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update employee profile details.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'update_employee_profile',
            'old_data' => [
                'first_name' => cleanText($personRow['first_name'] ?? null),
                'middle_name' => cleanText($personRow['middle_name'] ?? null),
                'surname' => cleanText($personRow['surname'] ?? null),
                'name_extension' => cleanText($personRow['name_extension'] ?? null),
                'personal_email' => cleanText($personRow['personal_email'] ?? null),
                'mobile_no' => cleanText($personRow['mobile_no'] ?? null),
            ],
            'new_data' => $patchPayload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee profile has been updated successfully.');
}

if ($action === 'update_employee_status') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $employmentId = cleanText($_POST['employment_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $transitionNote = cleanText($_POST['transition_note'] ?? null);

    $allowedStatuses = ['active', 'on_leave', 'resigned', 'retired', 'terminated'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        redirectWithState('error', 'Invalid employment status selected.');
    }

    $employmentRow = $resolveScopedEmployment($employmentId, $personId);
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'Employee scope validation failed.');
    }

    $oldStatus = strtolower((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'active'));

    $canTransitionEmployment = static function (string $old, string $new): bool {
        if ($old === $new) {
            return true;
        }

        $rules = [
            'active' => ['on_leave', 'resigned', 'retired', 'terminated'],
            'on_leave' => ['active', 'resigned', 'retired', 'terminated'],
            'resigned' => ['terminated'],
        ];

        if (!isset($rules[$old])) {
            return false;
        }

        return in_array($new, $rules[$old], true);
    };

    if (!$canTransitionEmployment($oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid status transition from ' . $oldStatus . ' to ' . $newStatus . '.');
    }

    $patchPayload = [
        'employment_status' => $newStatus,
        'updated_at' => gmdate('c'),
    ];

    if (in_array($newStatus, ['resigned', 'retired', 'terminated'], true)) {
        $patchPayload['separation_date'] = cleanText($employmentRow['separation_date'] ?? null) ?: gmdate('Y-m-d');
        $patchPayload['separation_reason'] = $transitionNote;
    }

    $statusPatchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?id=eq.' . rawurlencode($employmentId),
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($statusPatchResponse)) {
        redirectWithState('error', 'Failed to update employment status.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
    $employeeUserId = cleanText($personRow['user_id'] ?? null) ?? '';

    $employeeName = is_array($personRow)
        ? trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''))
        : 'Employee';
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    if (strcasecmp($employeeUserId, $staffUserId) !== 0) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'employee_profile',
                'title' => 'Employment Status Updated',
                'body' => 'Your employment status has been updated to ' . str_replace('_', ' ', $newStatus) . '.',
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => $employmentId,
            'action_name' => 'update_employee_status',
            'old_data' => ['employment_status' => $oldStatus],
            'new_data' => [
                'employment_status' => $newStatus,
                'transition_note' => $transitionNote,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', $employeeName . ' status updated to ' . str_replace('_', ' ', $newStatus) . '.');
}

redirectWithState('error', 'Unknown personal information action.');