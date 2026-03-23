<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/../../shared/lib/export-branding.php';

if (!(bool)($employeeContextResolved ?? false)) {
	http_response_code(403);
	exit('Employee context could not be resolved.');
}

$from = cleanText($_GET['from'] ?? null) ?? '';
$to = cleanText($_GET['to'] ?? null) ?? '';
$reportScope = strtolower((string)(cleanText($_GET['report_scope'] ?? null) ?? 'date_range'));
$year = (int)(cleanText($_GET['year'] ?? null) ?? '0');
$disposition = strtolower((string)(cleanText($_GET['disposition'] ?? null) ?? 'attachment'));

$isValidDate = static function (string $value): bool {
	$timestamp = strtotime($value);
	return $timestamp !== false && date('Y-m-d', $timestamp) === $value;
};

if ($reportScope === 'yearly') {
	if ($year < 2000 || $year > 2100) {
		http_response_code(400);
		exit('A valid attendance report year is required.');
	}

	$from = sprintf('%04d-01-01', $year);
	$to = sprintf('%04d-12-31', $year);
} elseif (!$isValidDate($from) || !$isValidDate($to)) {
	http_response_code(400);
	exit('A valid attendance export date range is required.');
}

if (strtotime($from) > strtotime($to)) {
	[$from, $to] = [$to, $from];
}

if (!in_array($disposition, ['attachment', 'inline'], true)) {
	$disposition = 'attachment';
}

$projectRoot = dirname(__DIR__, 3);
$autoloadPath = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoloadPath)) {
	http_response_code(500);
	exit('Export libraries are missing.');
}

require_once $autoloadPath;

if (!class_exists('Dompdf\\Dompdf')) {
	http_response_code(500);
	exit('Dompdf is not available.');
}

$personResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/people?select=first_name,surname,agency_employee_no'
	. '&id=eq.' . rawurlencode((string)$employeePersonId)
	. '&limit=1',
	$headers
);

$personRow = (array)(($personResponse['data'] ?? [])[0] ?? []);
$employeeName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));
if ($employeeName === '') {
	$employeeName = 'Employee';
}
$employeeCode = cleanText($personRow['agency_employee_no'] ?? null) ?? '-';
$reportTitle = $reportScope === 'yearly' ? 'Yearly Attendance Report' : 'Attendance Record Export';

$attendanceResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/attendance_logs?select=attendance_date,time_in,time_out,hours_worked,late_minutes,undertime_hours,attendance_status,source'
	. '&person_id=eq.' . rawurlencode((string)$employeePersonId)
	. '&attendance_date=gte.' . rawurlencode($from)
	. '&attendance_date=lte.' . rawurlencode($to)
	. '&order=attendance_date.asc&limit=1000',
	$headers
);

if (!isSuccessful($attendanceResponse)) {
	http_response_code(500);
	exit('Unable to load attendance records for export.');
}

$attendanceRows = (array)($attendanceResponse['data'] ?? []);

$formatDate = static function (?string $value): string {
	if ($value === null || $value === '') {
		return '-';
	}

	$timestamp = strtotime($value);
	return $timestamp === false ? '-' : date('M j, Y', $timestamp);
};

$formatTime = static function (?string $value): string {
	if ($value === null || $value === '') {
		return '-';
	}

	$timestamp = strtotime($value);
	return $timestamp === false ? '-' : date('g:i A', $timestamp);
};

$escape = static function (mixed $value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$reportCoverageLabel = $reportScope === 'yearly'
	? ('January to December ' . date('Y', strtotime($from)))
	: ($formatDate($from) . ' to ' . $formatDate($to));

$rowsHtml = '';
if (empty($attendanceRows)) {
	$rowsHtml = '<tr><td colspan="7" class="empty">No attendance records found for the selected period.</td></tr>';
} else {
	foreach ($attendanceRows as $attendanceRaw) {
		$row = (array)$attendanceRaw;
		$status = ucwords(str_replace('_', ' ', strtolower((string)($row['attendance_status'] ?? 'present'))));
		$rowsHtml .= '<tr>'
			. '<td>' . $escape($formatDate((string)($row['attendance_date'] ?? ''))) . '</td>'
			. '<td>' . $escape($formatTime((string)($row['time_in'] ?? ''))) . '</td>'
			. '<td>' . $escape($formatTime((string)($row['time_out'] ?? ''))) . '</td>'
			. '<td>' . $escape(number_format((float)($row['hours_worked'] ?? 0), 2)) . '</td>'
			. '<td>' . $escape((string)($row['late_minutes'] ?? 0)) . '</td>'
			. '<td>' . $escape(number_format((float)($row['undertime_hours'] ?? 0), 2)) . '</td>'
			. '<td>' . $escape($status) . '</td>'
			. '</tr>';
	}
}

$html = '<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Attendance Report</title>
	<style>
		body{font-family:DejaVu Sans, Arial, sans-serif;color:#0f172a;font-size:12px;margin:24px;}
		.report-table{width:100%;border-collapse:separate;border-spacing:0;margin-top:14px;}
		.report-table th,.report-table td{padding:8px 10px;text-align:left;vertical-align:top;}
		.report-table thead th{background:#e2e8f0;font-weight:700;border-bottom:1px solid #cbd5e1;}
		.report-table tbody td{border-bottom:1px solid #e2e8f0;}
		.empty{text-align:center;color:#64748b;padding:18px 10px;}
		.signature{margin-top:48px;width:100%;}
		.signature td{border:none;padding:0 18px 0 0;vertical-align:bottom;}
		.line{margin-top:38px;border-top:1px solid #334155;height:0;}
		.sig-label{margin-top:6px;font-size:11px;color:#475569;}
		.sig-sub{margin-top:2px;font-size:10px;color:#64748b;}
	</style>
</head>
<body>
	' . exportBrandingBuildPdfHeaderHtml($projectRoot, $reportTitle, [
		'Employee Name: ' . $employeeName,
		'Employee ID: ' . $employeeCode,
		'Coverage: ' . $reportCoverageLabel,
		'Generated On: ' . date('M j, Y g:i A') . ' PST',
	]) . '

	<table class="report-table">
		<thead>
			<tr>
				<th>Date</th>
				<th>Time In</th>
				<th>Time Out</th>
				<th>Hours Worked</th>
				<th>Late Minutes</th>
				<th>Undertime Hours</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>' . $rowsHtml . '</tbody>
	</table>

	<table class="signature">
		<tr>
			<td>
				<div class="line"></div>
				<div class="sig-label">Employee Acknowledgment / Signature</div>
			</td>
			<td>
				<div class="line"></div>
				<div class="sig-label">Date Signed</div>
			</td>
			<td>
				<div class="line"></div>
				<div class="sig-label">HR Head Signature Over Printed Name</div>
				<div class="sig-sub">For review / certification</div>
			</td>
		</tr>
	</table>
</body>
</html>';

$optionsClass = 'Dompdf\\Options';
$dompdfClass = 'Dompdf\\Dompdf';
$options = class_exists($optionsClass) ? new $optionsClass() : null;
if ($options instanceof Dompdf\Options) {
	$options->set('isRemoteEnabled', false);
	$options->set('isHtml5ParserEnabled', true);
}

$dompdf = $options instanceof Dompdf\Options ? new $dompdfClass($options) : new $dompdfClass();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$fileName = $reportScope === 'yearly'
	? ('attendance-report-' . date('Y', strtotime($from)) . '.pdf')
	: ('attendance-record-' . $from . '-to-' . $to . '.pdf');
header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename=' . $fileName);
echo $dompdf->output();
