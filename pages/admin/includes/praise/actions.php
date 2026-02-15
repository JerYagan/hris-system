<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'create_evaluation_cycle') {
    $cycleName = cleanText($_POST['cycle_name'] ?? null) ?? '';
    $periodStart = cleanText($_POST['period_start'] ?? null) ?? '';
    $periodEnd = cleanText($_POST['period_end'] ?? null) ?? '';

    if ($cycleName === '' || $periodStart === '' || $periodEnd === '') {
        redirectWithState('error', 'Cycle name, start date, and end date are required.');
    }

    if (strtotime($periodEnd) < strtotime($periodStart)) {
        redirectWithState('error', 'End date must be on or after start date.');
    }

    $existingCycleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/performance_cycles?select=id&cycle_name=eq.' . encodeFilter($cycleName) . '&period_start=eq.' . $periodStart . '&period_end=eq.' . $periodEnd . '&limit=1',
        $headers
    );

    if (isSuccessful($existingCycleResponse) && !empty($existingCycleResponse['data'])) {
        redirectWithState('error', 'Evaluation period already exists.');
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/performance_cycles',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'cycle_name' => $cycleName,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'open',
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to create evaluation cycle.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'performance_cycles',
            'entity_id' => null,
            'action_name' => 'create_cycle',
            'old_data' => null,
            'new_data' => [
                'cycle_name' => $cycleName,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'open',
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Evaluation cycle created successfully.');
}

if ($action === 'approve_supervisor_rating') {
    $evaluationId = cleanText($_POST['evaluation_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = cleanText($_POST['remarks'] ?? null);

    if ($evaluationId === '' || $decision === '') {
        redirectWithState('error', 'Evaluation and decision are required.');
    }

    if (!in_array($decision, ['approved', 'reviewed'], true)) {
        redirectWithState('error', 'Invalid decision selected.');
    }

    $evaluationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/performance_evaluations?select=id,status,remarks&id=eq.' . $evaluationId . '&limit=1',
        $headers
    );

    $evaluationRow = $evaluationResponse['data'][0] ?? null;
    if (!is_array($evaluationRow)) {
        redirectWithState('error', 'Performance evaluation record not found.');
    }

    $oldStatus = (string)($evaluationRow['status'] ?? 'submitted');

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/performance_evaluations?id=eq.' . $evaluationId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'remarks' => $remarks,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update supervisor rating decision.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'performance_evaluations',
            'entity_id' => $evaluationId,
            'action_name' => 'approve_supervisor_rating',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'remarks' => $remarks],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Supervisor rating decision saved successfully.');
}

if ($action === 'create_award_category') {
    $awardName = cleanText($_POST['award_name'] ?? null) ?? '';
    $awardCode = cleanText($_POST['award_code'] ?? null) ?? '';
    $criteria = cleanText($_POST['criteria'] ?? null);
    $description = cleanText($_POST['description'] ?? null);
    $isActive = ((string)($_POST['is_active'] ?? '1')) === '0' ? false : true;

    if ($awardName === '') {
        redirectWithState('error', 'Award category name is required.');
    }

    if ($awardCode === '') {
        $awardCode = 'AWD-' . strtoupper(substr(md5($awardName . microtime(true)), 0, 8));
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/praise_awards',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'award_code' => strtoupper($awardCode),
            'award_name' => $awardName,
            'description' => $description,
            'criteria' => $criteria,
            'is_active' => $isActive,
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to create award category.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'praise_awards',
            'entity_id' => null,
            'action_name' => 'create_award_category',
            'old_data' => null,
            'new_data' => [
                'award_code' => strtoupper($awardCode),
                'award_name' => $awardName,
                'criteria' => $criteria,
                'is_active' => $isActive,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Award category created successfully.');
}

if ($action === 'update_award_category') {
    $awardId = cleanText($_POST['award_id'] ?? null) ?? '';
    $awardName = cleanText($_POST['award_name'] ?? null) ?? '';
    $awardCode = cleanText($_POST['award_code'] ?? null) ?? '';
    $criteria = cleanText($_POST['criteria'] ?? null);
    $description = cleanText($_POST['description'] ?? null);
    $isActive = ((string)($_POST['is_active'] ?? '1')) === '0' ? false : true;

    if ($awardId === '' || $awardName === '') {
        redirectWithState('error', 'Award category and category name are required.');
    }

    if ($awardCode === '') {
        $awardCode = 'AWD-' . strtoupper(substr(md5($awardName . microtime(true)), 0, 8));
    }

    $existingAwardResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/praise_awards?select=id,award_code,award_name,criteria,description,is_active&id=eq.' . $awardId . '&limit=1',
        $headers
    );

    $existingAward = $existingAwardResponse['data'][0] ?? null;
    if (!is_array($existingAward)) {
        redirectWithState('error', 'Award category not found.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/praise_awards?id=eq.' . $awardId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'award_code' => strtoupper($awardCode),
            'award_name' => $awardName,
            'description' => $description,
            'criteria' => $criteria,
            'is_active' => $isActive,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update award category.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'praise_awards',
            'entity_id' => $awardId,
            'action_name' => 'update_award_category',
            'old_data' => [
                'award_code' => (string)($existingAward['award_code'] ?? ''),
                'award_name' => (string)($existingAward['award_name'] ?? ''),
                'description' => cleanText($existingAward['description'] ?? null),
                'criteria' => cleanText($existingAward['criteria'] ?? null),
                'is_active' => (bool)($existingAward['is_active'] ?? false),
            ],
            'new_data' => [
                'award_code' => strtoupper($awardCode),
                'award_name' => $awardName,
                'description' => $description,
                'criteria' => $criteria,
                'is_active' => $isActive,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Award category updated successfully.');
}

if ($action === 'review_nomination') {
    $nominationId = cleanText($_POST['nomination_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));

    if ($nominationId === '' || $decision === '') {
        redirectWithState('error', 'Nomination and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid nomination decision selected.');
    }

    $nominationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/praise_nominations?select=id,status&id=eq.' . $nominationId . '&limit=1',
        $headers
    );

    $nominationRow = $nominationResponse['data'][0] ?? null;
    if (!is_array($nominationRow)) {
        redirectWithState('error', 'Nomination record not found.');
    }

    $oldStatus = (string)($nominationRow['status'] ?? 'pending');

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/praise_nominations?id=eq.' . $nominationId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update nomination decision.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'praise_nominations',
            'entity_id' => $nominationId,
            'action_name' => 'review_nomination',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Nomination decision saved successfully.');
}

if ($action === 'publish_awardee') {
    $nominationId = cleanText($_POST['nomination_id'] ?? null) ?? '';

    if ($nominationId === '') {
        redirectWithState('error', 'Nomination is required for publishing.');
    }

    $nominationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/praise_nominations?select=id,status,nominee_person_id,award:award_id(award_name),nominee:nominee_person_id(user_id,first_name,surname)&id=eq.' . $nominationId . '&limit=1',
        $headers
    );

    $nominationRow = $nominationResponse['data'][0] ?? null;
    if (!is_array($nominationRow)) {
        redirectWithState('error', 'Nomination record not found.');
    }

    $status = strtolower((string)($nominationRow['status'] ?? 'pending'));
    if ($status !== 'approved') {
        redirectWithState('error', 'Only approved nominations can be published.');
    }

    $awardName = (string)($nominationRow['award']['award_name'] ?? 'PRAISE Award');
    $nomineeName = trim(((string)($nominationRow['nominee']['first_name'] ?? '')) . ' ' . ((string)($nominationRow['nominee']['surname'] ?? '')));
    if ($nomineeName === '') {
        $nomineeName = 'Employee';
    }

    $recipientUserId = (string)($nominationRow['nominee']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'praise',
                'title' => 'PRAISE Award Published',
                'body' => 'Congratulations! Your ' . $awardName . ' recognition has been published.',
                'link_url' => '/hris-system/pages/employee/praise.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'praise',
            'entity_name' => 'praise_nominations',
            'entity_id' => $nominationId,
            'action_name' => 'publish_awardee',
            'old_data' => ['status' => 'approved'],
            'new_data' => ['status' => 'approved', 'award_name' => $awardName, 'awardee' => $nomineeName],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Awardee published successfully.');
}

if ($action !== '') {
    redirectWithState('error', 'Unknown PRAISE action.');
}
