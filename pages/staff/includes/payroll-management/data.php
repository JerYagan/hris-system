<?php

$payrollPeriodRows = [];
$payrollRunRows = [];
$payrollMetrics = [
    'open_periods' => 0,
    'processing_periods' => 0,
    'active_runs' => 0,
    'released_runs' => 0,
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
$runScopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$periodsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,updated_at'
    . '&order=period_end.desc&limit=500',
    $headers
);
$appendDataError('Payroll periods', $periodsResponse);
$periodRows = isSuccessful($periodsResponse) ? (array)($periodsResponse['data'] ?? []) : [];

$runsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_runs?select=id,payroll_period_id,office_id,run_status,generated_by,generated_at,approved_at,created_at,office:offices(office_name),period:payroll_periods(period_code,period_start,period_end,status)'
    . $runScopeFilter
    . '&order=created_at.desc&limit=1500',
    $headers
);
$appendDataError('Payroll runs', $runsResponse);
$runRows = isSuccessful($runsResponse) ? (array)($runsResponse['data'] ?? []) : [];

$runIds = [];
foreach ($runRows as $run) {
    $runId = cleanText($run['id'] ?? null);
    if ($runId === null || !isValidUuid($runId)) {
        continue;
    }
    $runIds[] = $runId;
}

$itemRows = [];
if (!empty($runIds)) {
    $itemsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,gross_pay,net_pay,deductions_total,created_at'
        . '&payroll_run_id=in.' . rawurlencode('(' . implode(',', $runIds) . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Payroll items', $itemsResponse);
    $itemRows = isSuccessful($itemsResponse) ? (array)($itemsResponse['data'] ?? []) : [];
}

$itemPersonIds = [];
foreach ($itemRows as $item) {
    $personId = cleanText($item['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $itemPersonIds[$personId] = true;
}

$personUserIdByPersonId = [];
if (!empty($itemPersonIds)) {
    $peopleResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,user_id'
        . '&id=in.' . rawurlencode('(' . implode(',', array_keys($itemPersonIds)) . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Payroll item people', $peopleResponse);
    $peopleRows = isSuccessful($peopleResponse) ? (array)($peopleResponse['data'] ?? []) : [];

    foreach ($peopleRows as $personRow) {
        $personId = cleanText($personRow['id'] ?? null) ?? '';
        $userId = cleanText($personRow['user_id'] ?? null) ?? '';
        if (!isValidUuid($personId) || !isValidUuid($userId)) {
            continue;
        }

        $personUserIdByPersonId[$personId] = $userId;
    }
}

$itemIds = [];
foreach ($itemRows as $item) {
    $itemId = cleanText($item['id'] ?? null);
    if ($itemId === null || !isValidUuid($itemId)) {
        continue;
    }
    $itemIds[] = $itemId;
}

$releasedByItemId = [];
if (!empty($itemIds)) {
    $payslipResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payslips?select=payroll_item_id,released_at'
        . '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Payslips', $payslipResponse);
    $payslipRows = isSuccessful($payslipResponse) ? (array)($payslipResponse['data'] ?? []) : [];

    foreach ($payslipRows as $payslip) {
        $itemId = cleanText($payslip['payroll_item_id'] ?? null) ?? '';
        if (!isValidUuid($itemId)) {
            continue;
        }

        $releasedByItemId[$itemId] = cleanText($payslip['released_at'] ?? null) !== null;
    }
}

$periodStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'processing' => ['Processing', 'bg-amber-100 text-amber-800'],
        'posted' => ['Posted', 'bg-blue-100 text-blue-800'],
        'closed' => ['Closed', 'bg-emerald-100 text-emerald-800'],
        default => ['Open', 'bg-violet-100 text-violet-800'],
    };
};

$runStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'computed' => ['Computed', 'bg-violet-100 text-violet-800'],
        'approved' => ['Approved', 'bg-blue-100 text-blue-800'],
        'released' => ['Released', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-800'],
        default => ['Draft', 'bg-amber-100 text-amber-800'],
    };
};

$periodStatsById = [];
$runStatsById = [];

foreach ($itemRows as $item) {
    $runId = cleanText($item['payroll_run_id'] ?? null) ?? '';
    if (!isValidUuid($runId)) {
        continue;
    }

    $personId = cleanText($item['person_id'] ?? null) ?? '';
    if (!isset($runStatsById[$runId])) {
        $runStatsById[$runId] = [
            'employees' => [],
            'gross_total' => 0.0,
            'net_total' => 0.0,
            'released_count' => 0,
        ];
    }

    if (isValidUuid($personId)) {
        $runStatsById[$runId]['employees'][$personId] = true;
    }

    $runStatsById[$runId]['gross_total'] += (float)($item['gross_pay'] ?? 0);
    $runStatsById[$runId]['net_total'] += (float)($item['net_pay'] ?? 0);

    $itemId = cleanText($item['id'] ?? null) ?? '';
    if ($itemId !== '' && !empty($releasedByItemId[$itemId])) {
        $runStatsById[$runId]['released_count']++;
    }
}

foreach ($runRows as $run) {
    $runId = cleanText($run['id'] ?? null) ?? '';
    $periodId = cleanText($run['payroll_period_id'] ?? null) ?? '';
    if (!isValidUuid($runId) || !isValidUuid($periodId)) {
        continue;
    }

    if (!isset($periodStatsById[$periodId])) {
        $periodStatsById[$periodId] = [
            'run_count' => 0,
            'gross_total' => 0.0,
            'net_total' => 0.0,
            'employees' => [],
        ];
    }

    $runStats = $runStatsById[$runId] ?? [
        'employees' => [],
        'gross_total' => 0.0,
        'net_total' => 0.0,
        'released_count' => 0,
    ];

    $periodStatsById[$periodId]['run_count']++;
    $periodStatsById[$periodId]['gross_total'] += (float)$runStats['gross_total'];
    $periodStatsById[$periodId]['net_total'] += (float)$runStats['net_total'];
    foreach (array_keys((array)$runStats['employees']) as $personId) {
        $periodStatsById[$periodId]['employees'][$personId] = true;
    }
}

foreach ($periodRows as $period) {
    $periodId = cleanText($period['id'] ?? null) ?? '';
    if (!isValidUuid($periodId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($period['status'] ?? null) ?? 'open'));
    [$statusLabel, $statusClass] = $periodStatusPill($statusRaw);
    $stats = $periodStatsById[$periodId] ?? ['run_count' => 0, 'gross_total' => 0.0, 'net_total' => 0.0, 'employees' => []];

    if ($statusRaw === 'open') {
        $payrollMetrics['open_periods']++;
    }
    if ($statusRaw === 'processing') {
        $payrollMetrics['processing_periods']++;
    }

    $periodCode = cleanText($period['period_code'] ?? null) ?? 'Uncoded';
    $periodRange = formatDateTimeForPhilippines(cleanText($period['period_start'] ?? null), 'M d, Y')
        . ' - '
        . formatDateTimeForPhilippines(cleanText($period['period_end'] ?? null), 'M d, Y');

    $payrollPeriodRows[] = [
        'id' => $periodId,
        'period_code' => $periodCode,
        'period_range' => $periodRange,
        'payout_label' => formatDateTimeForPhilippines(cleanText($period['payout_date'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'run_count' => (int)($stats['run_count'] ?? 0),
        'employee_count' => count((array)($stats['employees'] ?? [])),
        'gross_total' => (float)($stats['gross_total'] ?? 0),
        'net_total' => (float)($stats['net_total'] ?? 0),
        'search_text' => strtolower(trim($periodCode . ' ' . $periodRange . ' ' . $statusLabel)),
    ];
}

foreach ($runRows as $run) {
    $runId = cleanText($run['id'] ?? null) ?? '';
    if (!isValidUuid($runId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($run['run_status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $runStatusPill($statusRaw);
    $runStats = $runStatsById[$runId] ?? ['employees' => [], 'gross_total' => 0.0, 'net_total' => 0.0, 'released_count' => 0];

    if (in_array($statusRaw, ['draft', 'computed', 'approved'], true)) {
        $payrollMetrics['active_runs']++;
    }
    if ($statusRaw === 'released') {
        $payrollMetrics['released_runs']++;
    }

    $officeName = cleanText($run['office']['office_name'] ?? null) ?? ($isAdminScope ? 'All Offices' : ($staffOfficeName ?? 'Scoped Office'));
    $periodCode = cleanText($run['period']['period_code'] ?? null) ?? 'Uncoded';

    $payrollRunRows[] = [
        'id' => $runId,
        'short_id' => strtoupper(substr(str_replace('-', '', $runId), 0, 8)),
        'period_code' => $periodCode,
        'office_name' => $officeName,
        'generated_label' => formatDateTimeForPhilippines(cleanText($run['generated_at'] ?? null), 'M d, Y'),
        'approved_label' => formatDateTimeForPhilippines(cleanText($run['approved_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'employee_count' => count((array)($runStats['employees'] ?? [])),
        'gross_total' => (float)($runStats['gross_total'] ?? 0),
        'net_total' => (float)($runStats['net_total'] ?? 0),
        'released_count' => (int)($runStats['released_count'] ?? 0),
        'search_text' => strtolower(trim($periodCode . ' ' . $officeName . ' ' . $statusLabel . ' ' . $runId)),
    ];
}
