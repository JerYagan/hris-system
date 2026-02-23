<?php

$evaluationRows = [];
$evaluationMetrics = [
    'submitted' => 0,
    'reviewed' => 0,
    'approved' => 0,
    'total' => 0,
];
$employeeOptions = [];
$cycleOptions = [];
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

$employeesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=person_id,office:offices(office_name),person:people!employment_records_person_id_fkey(first_name,surname)'
    . '&is_current=eq.true&limit=5000',
    $headers
);
$appendDataError('Employees', $employeesResponse);

$seenPersonMap = [];
foreach ((array)($employeesResponse['data'] ?? []) as $employee) {
    $personId = cleanText($employee['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId) || isset($seenPersonMap[$personId])) {
        continue;
    }

    $name = trim(
        (string)(cleanText($employee['person']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($employee['person']['surname'] ?? null) ?? '')
    );
    if ($name === '') {
        $name = 'Unknown Employee';
    }

    $officeName = cleanText($employee['office']['office_name'] ?? null) ?? 'Unassigned Office';

    $employeeOptions[] = [
        'person_id' => $personId,
        'label' => $name . ' - ' . $officeName,
    ];

    $seenPersonMap[$personId] = true;
}

$cyclesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,cycle_name,status,period_start,period_end'
    . '&status=in.(open,closed)'
    . '&order=period_start.desc&limit=500',
    $headers
);
$appendDataError('Performance cycles', $cyclesResponse);

foreach ((array)($cyclesResponse['data'] ?? []) as $cycle) {
    $cycleId = cleanText($cycle['id'] ?? null) ?? '';
    if (!isValidUuid($cycleId)) {
        continue;
    }

    $cycleName = cleanText($cycle['cycle_name'] ?? null) ?? 'Unnamed Cycle';
    $status = strtolower((string)(cleanText($cycle['status'] ?? null) ?? 'draft'));
    $window = (cleanText($cycle['period_start'] ?? null) ?? '-') . ' to ' . (cleanText($cycle['period_end'] ?? null) ?? '-');

    $cycleOptions[] = [
        'id' => $cycleId,
        'label' => $cycleName . ' (' . ucfirst($status) . ') - ' . $window,
    ];
}

$evaluationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,cycle_id,employee_person_id,evaluator_user_id,final_rating,remarks,status,updated_at,employee:employee_person_id(first_name,surname),cycle:cycle_id(cycle_name),evaluator:evaluator_user_id(email)'
    . '&order=updated_at.desc&limit=2000',
    $headers
);
$appendDataError('Employee evaluations', $evaluationsResponse);

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Submitted', 'bg-amber-100 text-amber-800'],
        'reviewed' => ['Reviewed', 'bg-blue-100 text-blue-800'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        default => ['Draft', 'bg-slate-100 text-slate-700'],
    };
};

foreach ((array)($evaluationsResponse['data'] ?? []) as $row) {
    $evaluationId = cleanText($row['id'] ?? null) ?? '';
    if (!isValidUuid($evaluationId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($row['status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $employeeName = trim(
        (string)(cleanText($row['employee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($row['employee']['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $cycleName = cleanText($row['cycle']['cycle_name'] ?? null) ?? 'Unassigned Cycle';
    $evaluatorEmail = cleanText($row['evaluator']['email'] ?? null) ?? '-';

    $evaluationMetrics['total']++;
    if (isset($evaluationMetrics[$statusRaw])) {
        $evaluationMetrics[$statusRaw]++;
    }

    $evaluationRows[] = [
        'id' => $evaluationId,
        'employee_name' => $employeeName,
        'cycle_name' => $cycleName,
        'evaluator_email' => $evaluatorEmail,
        'rating_label' => is_numeric($row['final_rating'] ?? null) ? number_format((float)$row['final_rating'], 2) . ' / 5.00' : '-',
        'remarks' => cleanText($row['remarks'] ?? null) ?? '-',
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'updated_label' => formatDateTimeForPhilippines(cleanText($row['updated_at'] ?? null), 'M d, Y h:i A'),
        'search_text' => strtolower(trim($employeeName . ' ' . $cycleName . ' ' . $evaluatorEmail . ' ' . $statusLabel . ' ' . (cleanText($row['remarks'] ?? null) ?? ''))),
    ];
}

usort($employeeOptions, static function (array $left, array $right): int {
    return strcmp((string)($left['label'] ?? ''), (string)($right['label'] ?? ''));
});
