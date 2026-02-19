<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$resolvedStaffOfficeId = cleanText($staffOfficeId ?? null) ?? '';

$isPersonInScope = static function (string $personId) use ($isAdminScope, $resolvedStaffOfficeId, $supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    if ($isAdminScope) {
        return true;
    }

    if (!isValidUuid($resolvedStaffOfficeId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&office_id=eq.' . rawurlencode($resolvedStaffOfficeId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    return isSuccessful($scopeResponse) && !empty($scopeResponse['data'][0]);
};

$notifyUser = static function (string $recipientUserId, string $title, string $body) use ($supabaseUrl, $headers): void {
    if (!isValidUuid($recipientUserId)) {
        return;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $recipientUserId,
            'category' => 'evaluation',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/staff/evaluation.php',
        ]]
    );
};

$canTransitionEvaluation = static function (string $oldStatus, string $newStatus): bool {
    $oldKey = strtolower(trim($oldStatus));
    $newKey = strtolower(trim($newStatus));

    if ($oldKey === '' || $newKey === '') {
        return false;
    }

    if ($oldKey === $newKey) {
        return true;
    }

    $rules = [
        'draft' => ['submitted'],
        'submitted' => ['reviewed', 'approved'],
        'reviewed' => ['submitted', 'approved'],
    ];

    return isset($rules[$oldKey]) && in_array($newKey, $rules[$oldKey], true);
};

if ($action === 'review_performance_evaluation') {
    $evaluationId = cleanText($_POST['evaluation_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = cleanText($_POST['remarks'] ?? null);

    if (!isValidUuid($evaluationId)) {
        redirectWithState('error', 'Invalid performance evaluation selected.');
    }

    if (!in_array($decision, ['submitted', 'reviewed', 'approved'], true)) {
        redirectWithState('error', 'Invalid evaluation decision selected.');
    }

    $evaluationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/performance_evaluations?select=id,status,employee_person_id,evaluator_user_id,cycle:cycle_id(cycle_name),employee:employee_person_id(first_name,surname,user_id)'
        . '&id=eq.' . rawurlencode($evaluationId)
        . '&limit=1',
        $headers
    );

    $evaluationRow = isSuccessful($evaluationResponse) ? ($evaluationResponse['data'][0] ?? null) : null;
    if (!is_array($evaluationRow)) {
        redirectWithState('error', 'Performance evaluation record not found.');
    }

    $employeePersonId = cleanText($evaluationRow['employee_person_id'] ?? null) ?? '';
    $employeeUserId = cleanText($evaluationRow['employee']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($employeePersonId)) {
        redirectWithState('error', 'Performance evaluation is outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($evaluationRow['status'] ?? null) ?? 'draft'));
    if (!$canTransitionEvaluation($oldStatus, $decision)) {
        redirectWithState('error', 'Invalid evaluation transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/performance_evaluations?id=eq.' . rawurlencode($evaluationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'remarks' => $remarks,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update performance evaluation decision.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'evaluation',
            'entity_name' => 'performance_evaluations',
            'entity_id' => $evaluationId,
            'action_name' => 'review_performance_evaluation',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'remarks' => $remarks],
            'ip_address' => clientIp(),
        ]]
    );

    $employeeName = trim(
        (string)(cleanText($evaluationRow['employee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($evaluationRow['employee']['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    $cycleName = cleanText($evaluationRow['cycle']['cycle_name'] ?? null) ?? 'current cycle';

    $evaluatorUserId = cleanText($evaluationRow['evaluator_user_id'] ?? null) ?? '';
    if (isValidUuid($evaluatorUserId) && strcasecmp($evaluatorUserId, $staffUserId) !== 0) {
        $notifyUser(
            $evaluatorUserId,
            'Performance Evaluation Updated',
            'Evaluation for ' . $employeeName . ' in ' . $cycleName . ' was marked as ' . str_replace('_', ' ', $decision) . '.'
        );
    }

    redirectWithState('success', 'Performance evaluation updated to ' . $decision . '.');
}

redirectWithState('error', 'Unknown evaluation action.');
