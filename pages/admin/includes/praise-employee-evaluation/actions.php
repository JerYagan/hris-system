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

if ($action !== '') {
    redirectWithState('error', 'Unknown employee evaluation action.');
}
