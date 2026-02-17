<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$reportFilters = [
    'date_from' => cleanText($_GET['date_from'] ?? null),
    'date_to' => cleanText($_GET['date_to'] ?? null),
    'report_type' => strtolower((string)cleanText($_GET['report_type'] ?? null)),
    'status' => strtolower((string)cleanText($_GET['status'] ?? null)),
    'evaluation_quarter' => strtolower((string)cleanText($_GET['evaluation_quarter'] ?? null)),
];

$allowedReportTypes = ['attendance', 'payroll', 'performance', 'documents'];
$allowedStatuses = ['queued', 'processing', 'ready', 'failed'];
$allowedQuarters = ['q1', 'q2', 'q3', 'q4'];

if (!in_array($reportFilters['report_type'], array_merge([''], $allowedReportTypes), true)) {
    $reportFilters['report_type'] = '';
}
if (!in_array($reportFilters['status'], array_merge([''], $allowedStatuses), true)) {
    $reportFilters['status'] = '';
}
if (!in_array($reportFilters['evaluation_quarter'], array_merge([''], $allowedQuarters), true)) {
    $reportFilters['evaluation_quarter'] = '';
}

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return false;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

if ($reportFilters['date_from'] !== null && !$isValidDate($reportFilters['date_from'])) {
    $reportFilters['date_from'] = null;
}
if ($reportFilters['date_to'] !== null && !$isValidDate($reportFilters['date_to'])) {
    $reportFilters['date_to'] = null;
}
if ($reportFilters['date_from'] !== null && $reportFilters['date_to'] !== null && strtotime((string)$reportFilters['date_to']) < strtotime((string)$reportFilters['date_from'])) {
    [$reportFilters['date_from'], $reportFilters['date_to']] = [$reportFilters['date_to'], $reportFilters['date_from']];
}

$attendanceSummaryRows = [];
$payrollSummaryRows = [];
$performanceSummaryRows = [];
$generatedReportRows = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=attendance_date,attendance_status'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=attendance_date.desc&limit=1000',
    $headers
);

if (isSuccessful($attendanceResponse)) {
    $monthlyMap = [];
    foreach ((array)($attendanceResponse['data'] ?? []) as $attendanceRaw) {
        $attendance = (array)$attendanceRaw;
        $attendanceDate = cleanText($attendance['attendance_date'] ?? null);
        if ($attendanceDate === null) {
            continue;
        }

        $monthKey = date('Y-m', strtotime($attendanceDate));
        if (!isset($monthlyMap[$monthKey])) {
            $monthlyMap[$monthKey] = [
                'month_label' => date('F Y', strtotime($attendanceDate)),
                'present' => 0,
                'late' => 0,
                'absent' => 0,
            ];
        }

        $status = strtolower((string)($attendance['attendance_status'] ?? 'present'));
        if ($status === 'present') {
            $monthlyMap[$monthKey]['present']++;
        } elseif ($status === 'late') {
            $monthlyMap[$monthKey]['late']++;
        } elseif ($status === 'absent') {
            $monthlyMap[$monthKey]['absent']++;
        }
    }

    krsort($monthlyMap);
    $attendanceSummaryRows = array_slice(array_values($monthlyMap), 0, 12);
}

$payrollItemsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_items?select=id,gross_pay,deductions_total,net_pay,created_at,payroll_run:payroll_runs(payroll_period:payroll_periods(period_code,period_start,period_end))'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($payrollItemsResponse)) {
    foreach ((array)($payrollItemsResponse['data'] ?? []) as $itemRaw) {
        $item = (array)$itemRaw;
        $run = (array)($item['payroll_run'] ?? []);
        $period = (array)($run['payroll_period'] ?? []);

        $periodStart = (string)($period['period_start'] ?? '');
        $periodEnd = (string)($period['period_end'] ?? '');
        $periodCode = (string)($period['period_code'] ?? '');
        $periodLabel = ($periodStart !== '' && $periodEnd !== '')
            ? (date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd)))
            : ($periodCode !== '' ? $periodCode : 'Payroll Period');

        $payrollSummaryRows[] = [
            'period_label' => $periodLabel,
            'gross_pay' => (float)($item['gross_pay'] ?? 0),
            'deductions_total' => (float)($item['deductions_total'] ?? 0),
            'net_pay' => (float)($item['net_pay'] ?? 0),
        ];
    }
}

$performanceResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,final_rating,status,created_at,cycle:performance_cycles(cycle_name,period_start,period_end)'
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($performanceResponse)) {
    foreach ((array)($performanceResponse['data'] ?? []) as $evaluationRaw) {
        $evaluation = (array)$evaluationRaw;
        $cycle = (array)($evaluation['cycle'] ?? []);

        $cycleName = (string)($cycle['cycle_name'] ?? 'Performance Cycle');
        $periodStart = (string)($cycle['period_start'] ?? '');
        $periodEnd = (string)($cycle['period_end'] ?? '');
        $periodLabel = ($periodStart !== '' && $periodEnd !== '')
            ? (date('M Y', strtotime($periodStart)) . ' - ' . date('M Y', strtotime($periodEnd)))
            : $cycleName;

        $quarterTs = $periodStart !== '' ? strtotime($periodStart) : strtotime((string)($evaluation['created_at'] ?? ''));
        $quarterValue = '';
        $quarterLabel = '-';
        if ($quarterTs !== false) {
            $month = (int)date('n', $quarterTs);
            $quarterNumber = (int)ceil($month / 3);
            $quarterValue = 'q' . $quarterNumber;
            $quarterLabel = 'Q' . $quarterNumber . ' ' . date('Y', $quarterTs);
        }

        if ($reportFilters['evaluation_quarter'] !== '' && $quarterValue !== $reportFilters['evaluation_quarter']) {
            continue;
        }

        $performanceSummaryRows[] = [
            'quarter_value' => $quarterValue,
            'quarter_label' => $quarterLabel,
            'period_label' => $periodLabel,
            'cycle_name' => $cycleName,
            'final_rating' => cleanText($evaluation['final_rating'] ?? null) ?? '-',
            'status' => strtolower((string)($evaluation['status'] ?? 'draft')),
        ];
    }
}

$generatedReportsUrl = $supabaseUrl
    . '/rest/v1/generated_reports?select=id,report_type,file_format,status,filters_json,storage_path,generated_at,created_at'
    . '&requested_by=eq.' . rawurlencode((string)$employeeUserId)
    . '&order=created_at.desc&limit=200';

if (!empty($reportFilters['report_type'])) {
    $generatedReportsUrl .= '&report_type=eq.' . rawurlencode((string)$reportFilters['report_type']);
}
if (!empty($reportFilters['status'])) {
    $generatedReportsUrl .= '&status=eq.' . rawurlencode((string)$reportFilters['status']);
}
if (!empty($reportFilters['date_from'])) {
    $generatedReportsUrl .= '&created_at=gte.' . rawurlencode((string)$reportFilters['date_from'] . 'T00:00:00Z');
}
if (!empty($reportFilters['date_to'])) {
    $generatedReportsUrl .= '&created_at=lte.' . rawurlencode((string)$reportFilters['date_to'] . 'T23:59:59Z');
}

$generatedReportsResponse = apiRequest('GET', $generatedReportsUrl, $headers);
if (isSuccessful($generatedReportsResponse)) {
    foreach ((array)($generatedReportsResponse['data'] ?? []) as $reportRaw) {
        $report = (array)$reportRaw;
        $generatedReportRows[] = [
            'id' => (string)($report['id'] ?? ''),
            'report_type' => strtolower((string)($report['report_type'] ?? 'attendance')),
            'file_format' => strtolower((string)($report['file_format'] ?? 'pdf')),
            'status' => strtolower((string)($report['status'] ?? 'queued')),
            'storage_path' => cleanText($report['storage_path'] ?? null),
            'generated_at' => cleanText($report['generated_at'] ?? null),
            'created_at' => cleanText($report['created_at'] ?? null),
        ];
    }
} elseif ($dataLoadError === null) {
    $dataLoadError = 'Unable to load generated report history right now.';
}
