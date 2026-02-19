<?php

$evaluationCycleRows = [];
$evaluationDecisionRows = [];
$evaluationMetrics = [
    'open_cycles' => 0,
    'pending_reviews' => 0,
    'reviewed_records' => 0,
    'approved_records' => 0,
];
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


$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';

$personScopeMap = [];
if (!$isAdminScope && isValidUuid((string)$staffOfficeId)) {
    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id'
        . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        . '&is_current=eq.true'
        . '&limit=5000',
        $headers
    );

    $appendDataError('Evaluation scope', $scopeResponse);
    $scopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];

    foreach ($scopeRows as $scopeRow) {
        $personId = cleanText($scopeRow['person_id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            continue;
        }

        $personScopeMap[$personId] = true;
    }
}

$cyclesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,cycle_name,period_start,period_end,status,updated_at'
    . '&order=period_start.desc&limit=500',
    $headers
);
$appendDataError('Performance cycles', $cyclesResponse);
$cycleRows = isSuccessful($cyclesResponse) ? (array)($cyclesResponse['data'] ?? []) : [];

$evaluationEndpoint = $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,cycle_id,employee_person_id,evaluator_user_id,final_rating,remarks,status,updated_at,employee:employee_person_id(first_name,surname,user_id),evaluator:evaluator_user_id(email),cycle:cycle_id(cycle_name,period_start,period_end,status)'
    . '&order=updated_at.desc&limit=2000';

if (!$isAdminScope) {
    $personIds = array_keys($personScopeMap);
    if (empty($personIds)) {
        $evaluationEndpoint .= '&id=is.null';
    } else {
        $evaluationEndpoint .= '&employee_person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')');
    }
}

$evaluationsResponse = apiRequest('GET', $evaluationEndpoint, $headers);
$appendDataError('Performance evaluations', $evaluationsResponse);
$evaluationRows = isSuccessful($evaluationsResponse) ? (array)($evaluationsResponse['data'] ?? []) : [];

$cycleStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'open' => ['Open', 'bg-emerald-100 text-emerald-800'],
        'closed' => ['Closed', 'bg-slate-200 text-slate-700'],
        'archived' => ['Archived', 'bg-slate-100 text-slate-700'],
        default => ['Draft', 'bg-amber-100 text-amber-800'],
    };
};

$evaluationStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Submitted', 'bg-amber-100 text-amber-800'],
        'reviewed' => ['Reviewed', 'bg-blue-100 text-blue-800'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        default => ['Draft', 'bg-slate-100 text-slate-700'],
    };
};

$evaluationCountByCycle = [];

foreach ($evaluationRows as $evaluation) {
    $evaluationId = cleanText($evaluation['id'] ?? null) ?? '';
    if (!isValidUuid($evaluationId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($evaluation['status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $evaluationStatusPill($statusRaw);

    if ($statusRaw === 'submitted') {
        $evaluationMetrics['pending_reviews']++;
    }
    if ($statusRaw === 'reviewed') {
        $evaluationMetrics['reviewed_records']++;
    }
    if ($statusRaw === 'approved') {
        $evaluationMetrics['approved_records']++;
    }

    $employeeName = trim(
        (string)(cleanText($evaluation['employee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($evaluation['employee']['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $cycleName = cleanText($evaluation['cycle']['cycle_name'] ?? null) ?? 'Unassigned Cycle';
    $evaluatorEmail = cleanText($evaluation['evaluator']['email'] ?? null) ?? '-';

    $cycleId = cleanText($evaluation['cycle_id'] ?? null) ?? '';
    if (isValidUuid($cycleId)) {
        $evaluationCountByCycle[$cycleId] = (int)($evaluationCountByCycle[$cycleId] ?? 0) + 1;
    }

    $evaluationDecisionRows[] = [
        'id' => $evaluationId,
        'employee_name' => $employeeName,
        'cycle_name' => $cycleName,
        'evaluator_email' => $evaluatorEmail,
        'rating_label' => is_numeric($evaluation['final_rating'] ?? null) ? number_format((float)$evaluation['final_rating'], 2) . ' / 5.00' : '-',
        'remarks' => cleanText($evaluation['remarks'] ?? null) ?? '-',
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'updated_label' => formatDateTimeForPhilippines(cleanText($evaluation['updated_at'] ?? null), 'M d, Y'),
        'search_text' => strtolower(trim($employeeName . ' ' . $cycleName . ' ' . $evaluatorEmail . ' ' . $statusLabel)),
    ];
}

foreach ($cycleRows as $cycle) {
    $cycleId = cleanText($cycle['id'] ?? null) ?? '';
    if (!isValidUuid($cycleId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($cycle['status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $cycleStatusPill($statusRaw);

    if ($statusRaw === 'open') {
        $evaluationMetrics['open_cycles']++;
    }

    $cycleName = cleanText($cycle['cycle_name'] ?? null) ?? 'Unnamed Cycle';

    $evaluationCycleRows[] = [
        'id' => $cycleId,
        'cycle_name' => $cycleName,
        'period_range' => formatDateTimeForPhilippines(cleanText($cycle['period_start'] ?? null), 'M d, Y')
            . ' - '
            . formatDateTimeForPhilippines(cleanText($cycle['period_end'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'evaluation_count' => (int)($evaluationCountByCycle[$cycleId] ?? 0),
    ];
}
