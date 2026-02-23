<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$action = cleanText($_POST['form_action'] ?? null) ?? '';
if ($action !== 'submit_employee_evaluation') {
    redirectWithState('error', 'Unknown employee evaluation action.');
}

$employeePersonIdInput = cleanText($_POST['employee_person_id'] ?? null) ?? '';
$cycleIdInput = cleanText($_POST['cycle_id'] ?? null) ?? '';
$feedbackInput = cleanText($_POST['feedback'] ?? null) ?? '';
$finalRatingInput = (float)(cleanText($_POST['final_rating'] ?? null) ?? '0');

if (!isValidUuid($employeePersonIdInput)) {
    redirectWithState('error', 'Please select a valid employee.');
}

if (!isValidUuid($cycleIdInput)) {
    redirectWithState('error', 'Please select a valid evaluation cycle.');
}

if ($feedbackInput === '') {
    redirectWithState('error', 'Performance feedback is required.');
}

if ($finalRatingInput < 1 || $finalRatingInput > 5) {
    redirectWithState('error', 'Final rating must be between 1.00 and 5.00.');
}

$employeeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=person_id,person:people(first_name,surname,user_id)'
    . '&person_id=eq.' . rawurlencode($employeePersonIdInput)
    . '&is_current=eq.true'
    . '&limit=1',
    $headers
);

$employeeRow = isSuccessful($employeeResponse) ? ($employeeResponse['data'][0] ?? null) : null;
if (!is_array($employeeRow)) {
    redirectWithState('error', 'Selected employee is not an active employment record.');
}

$cycleResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,cycle_name,status'
    . '&id=eq.' . rawurlencode($cycleIdInput)
    . '&limit=1',
    $headers
);

$cycleRow = isSuccessful($cycleResponse) ? ($cycleResponse['data'][0] ?? null) : null;
if (!is_array($cycleRow)) {
    redirectWithState('error', 'Selected evaluation cycle does not exist.');
}

$cycleStatus = strtolower((string)(cleanText($cycleRow['status'] ?? null) ?? 'draft'));
if (!in_array($cycleStatus, ['open', 'closed'], true)) {
    redirectWithState('error', 'Selected evaluation cycle is not available for submission.');
}

$existingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,status'
    . '&cycle_id=eq.' . rawurlencode($cycleIdInput)
    . '&employee_person_id=eq.' . rawurlencode($employeePersonIdInput)
    . '&evaluator_user_id=eq.' . rawurlencode($staffUserId)
    . '&limit=1',
    $headers
);

$existingRow = isSuccessful($existingResponse) ? ($existingResponse['data'][0] ?? null) : null;
$evaluationId = cleanText($existingRow['id'] ?? null) ?? '';
$oldStatus = strtolower((string)(cleanText($existingRow['status'] ?? null) ?? 'draft'));

if ($evaluationId !== '' && !isValidUuid($evaluationId)) {
    redirectWithState('error', 'Evaluation record is invalid.');
}

if ($oldStatus === 'approved') {
    redirectWithState('error', 'Approved evaluations can no longer be modified.');
}

if ($evaluationId !== '' && !in_array($oldStatus, ['draft', 'submitted', 'reviewed'], true)) {
    redirectWithState('error', 'Current evaluation status cannot be forwarded.');
}

$newPayload = [
    'cycle_id' => $cycleIdInput,
    'employee_person_id' => $employeePersonIdInput,
    'evaluator_user_id' => $staffUserId,
    'final_rating' => round($finalRatingInput, 2),
    'remarks' => $feedbackInput,
    'status' => 'submitted',
    'updated_at' => gmdate('c'),
];

$writeResponse = $evaluationId !== ''
    ? apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/performance_evaluations?id=eq.' . rawurlencode($evaluationId),
        array_merge($headers, ['Prefer: return=representation']),
        $newPayload
    )
    : apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/performance_evaluations',
        array_merge($headers, ['Prefer: return=representation']),
        [$newPayload]
    );

if (!isSuccessful($writeResponse)) {
    redirectWithState('error', 'Failed to forward employee evaluation for admin approval.');
}

$writtenRow = (array)($writeResponse['data'][0] ?? []);
$resolvedEvaluationId = cleanText($writtenRow['id'] ?? null) ?? ($evaluationId !== '' ? $evaluationId : null);

$employeeName = trim(
    (string)(cleanText($employeeRow['person']['first_name'] ?? null) ?? '')
    . ' '
    . (string)(cleanText($employeeRow['person']['surname'] ?? null) ?? '')
);
if ($employeeName === '') {
    $employeeName = 'Employee';
}

$cycleName = cleanText($cycleRow['cycle_name'] ?? null) ?? 'Evaluation Cycle';

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => isValidUuid($staffUserId) ? $staffUserId : null,
        'module_name' => 'employee_evaluation',
        'entity_name' => 'performance_evaluations',
        'entity_id' => $resolvedEvaluationId,
        'action_name' => 'submit_employee_evaluation_for_approval',
        'old_data' => $evaluationId !== '' ? ['status' => $oldStatus] : null,
        'new_data' => [
            'status' => 'submitted',
            'employee_person_id' => $employeePersonIdInput,
            'employee_name' => $employeeName,
            'cycle_id' => $cycleIdInput,
            'cycle_name' => $cycleName,
            'final_rating' => round($finalRatingInput, 2),
            'feedback' => $feedbackInput,
        ],
        'ip_address' => clientIp(),
    ]]
);

$adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
foreach (array_keys($adminUserIdMap) as $adminUserId) {
    if (!isValidUuid((string)$adminUserId)) {
        continue;
    }

    if (strcasecmp((string)$adminUserId, (string)$staffUserId) === 0) {
        continue;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $adminUserId,
            'category' => 'evaluation',
            'title' => 'Employee Evaluation For Approval',
            'body' => $employeeName . ' was evaluated for ' . $cycleName . ' and is awaiting your approval.',
            'link_url' => '/hris-system/pages/admin/praise.php',
        ]]
    );
}

redirectWithState('success', 'Employee evaluation submitted for admin approval.');
