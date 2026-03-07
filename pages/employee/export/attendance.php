<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/timekeeping/bootstrap.php';

if (!(bool)($employeeContextResolved ?? false)) {
	http_response_code(403);
	exit('Employee context could not be resolved.');
}

$from = cleanText($_GET['from'] ?? null) ?? '';
$to = cleanText($_GET['to'] ?? null) ?? '';

$isValidDate = static function (string $value): bool {
	$timestamp = strtotime($value);
	return $timestamp !== false && date('Y-m-d', $timestamp) === $value;
};

if (!$isValidDate($from) || !$isValidDate($to)) {
	http_response_code(400);
	exit('A valid attendance export date range is required.');
}

if (strtotime($from) > strtotime($to)) {
	[$from, $to] = [$to, $from];
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
		h1{font-size:20px;margin:0 0 8px;}
		.meta{margin-bottom:18px;line-height:1.6;}
		.meta strong{display:inline-block;min-width:110px;}
		table{width:100%;border-collapse:collapse;margin-top:12px;}
		th,td{border:1px solid #cbd5e1;padding:8px 10px;text-align:left;vertical-align:top;}
		th{background:#e2e8f0;font-weight:700;}
		.empty{text-align:center;color:#64748b;padding:18px 10px;}
		.signature{margin-top:48px;width:100%;}
		.signature td{border:none;padding:0 18px 0 0;vertical-align:bottom;}
		.line{margin-top:38px;border-top:1px solid #334155;height:0;}
		.sig-label{margin-top:6px;font-size:11px;color:#475569;}
	</style>
</head>
<body>
	<h1>Attendance Record Export</h1>
	<div class="meta">
		<div><strong>Employee Name:</strong> ' . $escape($employeeName) . '</div>
		<div><strong>Employee ID:</strong> ' . $escape($employeeCode) . '</div>
		<div><strong>Date Range:</strong> ' . $escape($formatDate($from)) . ' to ' . $escape($formatDate($to)) . '</div>
		<div><strong>Generated On:</strong> ' . $escape(date('M j, Y g:i A')) . ' PST</div>
	</div>

	<table>
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
				<div class="sig-label">Employee Signature</div>
			</td>
			<td>
				<div class="line"></div>
				<div class="sig-label">Date Signed</div>
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

$fileName = 'attendance-record-' . $from . '-to-' . $to . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename=' . $fileName);
echo $dompdf->output();
