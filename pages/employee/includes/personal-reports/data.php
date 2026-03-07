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
];

$allowedReportTypes = ['attendance', 'payroll', 'documents'];
$allowedStatuses = ['queued', 'processing', 'ready', 'failed'];

if (!in_array($reportFilters['report_type'], array_merge([''], $allowedReportTypes), true)) {
    $reportFilters['report_type'] = '';
}
if (!in_array($reportFilters['status'], array_merge([''], $allowedStatuses), true)) {
    $reportFilters['status'] = '';
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
$documentSummaryRows = [];
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

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,document_status,current_version_no,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=100',
    $headers
);

if (isSuccessful($documentsResponse)) {
    foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
        $document = (array)$documentRaw;
        $category = (array)($document['category'] ?? []);

        $documentSummaryRows[] = [
            'title' => cleanText($document['title'] ?? null) ?? 'Untitled Document',
            'category_name' => cleanText($category['category_name'] ?? null) ?? 'Others',
            'status' => strtolower((string)($document['document_status'] ?? 'draft')),
            'current_version_no' => (int)($document['current_version_no'] ?? 1),
            'updated_at' => cleanText($document['updated_at'] ?? null),
        ];
    }
}

$generatedReportsUrl = $supabaseUrl
    . '/rest/v1/generated_reports?select=id,report_type,file_format,status,filters_json,storage_path,generated_at,created_at'
    . '&requested_by=eq.' . rawurlencode((string)$employeeUserId)
    . '&report_type=in.(attendance,payroll,documents)'
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
    $statusMeta = static function (string $status): array {
        return match (strtolower(trim($status))) {
            'queued' => ['Queued', 'bg-blue-100 text-blue-700'],
            'processing' => ['Processing', 'bg-amber-100 text-amber-800'],
            'ready' => ['Ready', 'bg-emerald-100 text-emerald-700'],
            'failed' => ['Failed', 'bg-rose-100 text-rose-700'],
            default => ['Unknown', 'bg-slate-100 text-slate-700'],
        };
    };

    foreach ((array)($generatedReportsResponse['data'] ?? []) as $reportRaw) {
        $report = (array)$reportRaw;
        [$statusLabel, $statusClass] = $statusMeta((string)($report['status'] ?? 'queued'));
        $generatedReportRows[] = [
            'id' => (string)($report['id'] ?? ''),
            'report_type' => strtolower((string)($report['report_type'] ?? 'attendance')),
            'file_format' => strtolower((string)($report['file_format'] ?? 'pdf')),
            'status' => strtolower((string)($report['status'] ?? 'queued')),
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'storage_path' => cleanText($report['storage_path'] ?? null),
            'download_url' => !empty($report['storage_path']) && !empty($report['id'])
                ? ('/hris-system/pages/employee/download-generated-report.php?report_id=' . rawurlencode((string)$report['id']))
                : null,
            'generated_at' => cleanText($report['generated_at'] ?? null),
            'created_at' => cleanText($report['created_at'] ?? null),
        ];
    }
} elseif ($dataLoadError === null) {
    $dataLoadError = 'Unable to load generated report history right now.';
}
