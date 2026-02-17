<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'personal-reports.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'personal-reports.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if ($action !== 'request_report') {
    redirectWithState('error', 'Unsupported personal report action.', 'personal-reports.php');
}

$reportType = strtolower((string)cleanText($_POST['report_type'] ?? ''));
$fileFormat = strtolower((string)cleanText($_POST['file_format'] ?? ''));
$dateFrom = cleanText($_POST['date_from'] ?? null);
$dateTo = cleanText($_POST['date_to'] ?? null);
$evaluationQuarter = strtolower((string)cleanText($_POST['evaluation_quarter'] ?? null));

$allowedReportTypes = ['attendance', 'payroll', 'performance', 'documents'];
$allowedFormats = ['pdf', 'csv', 'xlsx'];
$allowedQuarters = ['', 'q1', 'q2', 'q3', 'q4'];

if (!in_array($reportType, $allowedReportTypes, true) || !in_array($fileFormat, $allowedFormats, true)) {
    redirectWithState('error', 'Please select a valid report type and file format.', 'personal-reports.php');
}

if (!in_array($evaluationQuarter, $allowedQuarters, true)) {
    redirectWithState('error', 'Invalid evaluation quarter selected.', 'personal-reports.php');
}

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return true;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

if (!$isValidDate($dateFrom) || !$isValidDate($dateTo)) {
    redirectWithState('error', 'Invalid date filter value.', 'personal-reports.php');
}

if ($dateFrom !== null && $dateTo !== null && strtotime($dateTo) < strtotime($dateFrom)) {
    redirectWithState('error', 'Date-to cannot be earlier than date-from.', 'personal-reports.php');
}

$filters = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'evaluation_quarter' => $evaluationQuarter !== '' ? $evaluationQuarter : null,
    'source' => 'employee_self_service',
];

$insertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/generated_reports',
    $headers,
    [[
        'requested_by' => $employeeUserId,
        'report_type' => $reportType,
        'filters_json' => $filters,
        'file_format' => $fileFormat,
        'status' => 'queued',
    ]]
);

if (!isSuccessful($insertResponse)) {
    redirectWithState('error', 'Failed to queue report request.', 'personal-reports.php');
}

$newRow = (array)(((array)$insertResponse['data'])[0] ?? []);
apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'generated_reports',
        'entity_id' => (string)($newRow['id'] ?? null),
        'action_name' => 'request_personal_report',
        'new_data' => [
            'report_type' => $reportType,
            'file_format' => $fileFormat,
            'filters' => $filters,
        ],
    ]]
);

redirectWithState('success', 'Report request queued successfully.', 'personal-reports.php');
