<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'praise.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'praise.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if ($action !== 'submit_self_evaluation') {
    redirectWithState('error', 'Unsupported PRAISE action.', 'praise.php');
}

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$cycleId = $toNullable($_POST['cycle_id'] ?? null, 36);
$finalRatingRaw = $toNullable($_POST['final_rating'] ?? null, 10);
$remarks = $toNullable($_POST['remarks'] ?? null, 2000);

if (!isValidUuid($cycleId) || $remarks === null) {
    redirectWithState('error', 'Performance cycle and self-evaluation comments are required.', 'praise.php');
}

$finalRating = null;
if ($finalRatingRaw !== null) {
    if (!is_numeric($finalRatingRaw)) {
        redirectWithState('error', 'Self-rating must be a valid numeric value.', 'praise.php');
    }

    $finalRating = round((float)$finalRatingRaw, 2);
    if ($finalRating < 1 || $finalRating > 5) {
        redirectWithState('error', 'Self-rating must be between 1.00 and 5.00.', 'praise.php');
    }
}

$cycleResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,status'
    . '&id=eq.' . rawurlencode((string)$cycleId)
    . '&status=eq.open'
    . '&limit=1',
    $headers
);

if (!isSuccessful($cycleResponse) || empty((array)($cycleResponse['data'] ?? []))) {
    redirectWithState('error', 'Selected cycle is invalid or no longer open for self-evaluation.', 'praise.php');
}

$existingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,status,final_rating,remarks'
    . '&cycle_id=eq.' . rawurlencode((string)$cycleId)
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&evaluator_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($existingResponse)) {
    redirectWithState('error', 'Unable to validate existing self-evaluation record.', 'praise.php');
}

$existingRows = (array)($existingResponse['data'] ?? []);

if (!empty($existingRows)) {
    $existing = (array)$existingRows[0];
    $existingId = cleanText($existing['id'] ?? null);
    $existingStatus = strtolower((string)($existing['status'] ?? 'draft'));

    if (!isValidUuid($existingId)) {
        redirectWithState('error', 'Existing self-evaluation record is invalid.', 'praise.php');
    }

    if (in_array($existingStatus, ['reviewed', 'approved'], true)) {
        redirectWithState('error', 'This self-evaluation is already locked by HR/supervisor review.', 'praise.php');
    }

    $updatePayload = [
        'final_rating' => $finalRating,
        'remarks' => $remarks,
        'status' => 'submitted',
    ];

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl
        . '/rest/v1/performance_evaluations?id=eq.' . rawurlencode((string)$existingId),
        $headers,
        $updatePayload
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Failed to update self-evaluation.', 'praise.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'performance_evaluations',
            'entity_id' => $existingId,
            'action_name' => 'update_self_evaluation',
            'new_data' => [
                'cycle_id' => $cycleId,
                'final_rating' => $finalRating,
                'status' => 'submitted',
            ],
        ]]
    );

    redirectWithState('success', 'Self-evaluation updated and submitted successfully.', 'praise.php');
}

$insertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/performance_evaluations',
    $headers,
    [[
        'cycle_id' => $cycleId,
        'employee_person_id' => $employeePersonId,
        'evaluator_user_id' => $employeeUserId,
        'final_rating' => $finalRating,
        'remarks' => $remarks,
        'status' => 'submitted',
    ]]
);

if (!isSuccessful($insertResponse)) {
    redirectWithState('error', 'Failed to submit self-evaluation.', 'praise.php');
}

$newRow = (array)(((array)$insertResponse['data'])[0] ?? []);
apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'performance_evaluations',
        'entity_id' => (string)($newRow['id'] ?? null),
        'action_name' => 'submit_self_evaluation',
        'new_data' => [
            'cycle_id' => $cycleId,
            'final_rating' => $finalRating,
            'status' => 'submitted',
        ],
    ]]
);

redirectWithState('success', 'Self-evaluation submitted successfully.', 'praise.php');
