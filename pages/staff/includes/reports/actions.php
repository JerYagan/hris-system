<?php

require_once dirname(__DIR__, 3) . '/shared/lib/reports-domain.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$action = cleanText($_POST['form_action'] ?? null) ?? '';
if ($action !== 'export_report') {
    logStaffSecurityEvent(
        $supabaseUrl,
        $headers,
        $staffUserId,
        'reports',
        'unknown_action_attempt',
        [
            'form_action' => $action,
        ]
    );
    redirectWithState('error', 'Unknown reports action.');
}

$reportType = strtolower((string)(cleanText($_POST['report_type'] ?? null) ?? ''));
$coverage = cleanText($_POST['coverage'] ?? null) ?? 'current_cutoff';
$fileFormat = strtolower((string)(cleanText($_POST['file_format'] ?? null) ?? 'pdf'));
$department = cleanText($_POST['department_filter'] ?? null) ?? 'all';
$customStartDate = cleanText($_POST['custom_start_date'] ?? null);
$customEndDate = cleanText($_POST['custom_end_date'] ?? null);

$allowedTypes = ['attendance', 'payroll', 'recruitment'];
if (!in_array($reportType, $allowedTypes, true)) {
    redirectWithState('error', 'Invalid report type selected.');
}

$allowedFormats = ['pdf', 'xlsx', 'csv'];
if (!in_array($fileFormat, $allowedFormats, true)) {
    redirectWithState('error', 'Invalid export format selected.');
}

$allowedCoverages = ['current_cutoff', 'monthly', 'quarterly', 'custom_range'];
if (!in_array(strtolower(trim((string)$coverage)), $allowedCoverages, true)) {
    redirectWithState('error', 'Invalid coverage selected.');
}

$projectRoot = dirname(__DIR__, 4);

try {
    $export = reportServiceHandleExport(
        $reportType,
        (string)$coverage,
        $fileFormat,
        $department,
        $customStartDate,
        $customEndDate,
        $supabaseUrl,
        $headers,
        $staffUserId,
        $projectRoot
    );

    $absolutePath = (string)($export['absolute_path'] ?? '');
    $fileName = (string)($export['file_name'] ?? 'report.bin');
    $mimeType = (string)($export['mime_type'] ?? 'application/octet-stream');

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . (string)filesize($absolutePath));
    header('Pragma: public');
    header('Cache-Control: must-revalidate');
    readfile($absolutePath);
    exit;
} catch (Throwable $exception) {
    redirectWithState('error', 'Failed to generate report file: ' . $exception->getMessage());
}
