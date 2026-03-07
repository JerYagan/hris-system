<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/payroll/bootstrap.php';

if (!(bool)($employeeContextResolved ?? false)) {
	http_response_code(403);
	exit('Employee context could not be resolved.');
}

$from = cleanText($_GET['from'] ?? null);
$to = cleanText($_GET['to'] ?? null);

$isValidDate = static function (?string $value): bool {
	if ($value === null || $value === '') {
		return true;
	}

	$timestamp = strtotime($value);
	return $timestamp !== false && date('Y-m-d', $timestamp) === $value;
};

if (!$isValidDate($from) || !$isValidDate($to)) {
	http_response_code(400);
	exit('Invalid payroll export date range.');
}

if ($from !== null && $to !== null && strtotime($from) > strtotime($to)) {
	[$from, $to] = [$to, $from];
}

$query = $supabaseUrl
	. '/rest/v1/payroll_items?select=id,gross_pay,deductions_total,net_pay,created_at,payroll_run:payroll_runs(payroll_period:payroll_periods(period_code,period_start,period_end))'
	. '&person_id=eq.' . rawurlencode((string)$employeePersonId)
	. '&order=created_at.desc&limit=500';

if ($from !== null) {
	$query .= '&created_at=gte.' . rawurlencode($from . 'T00:00:00Z');
}
if ($to !== null) {
	$query .= '&created_at=lte.' . rawurlencode($to . 'T23:59:59Z');
}

$payrollResponse = apiRequest('GET', $query, $headers);
if (!isSuccessful($payrollResponse)) {
	http_response_code(500);
	exit('Unable to load payroll rows for export.');
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

$escape = static function (mixed $value): string {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatCurrency = static function (float $value): string {
	return '₱ ' . number_format($value, 2);
};

$formatDate = static function (?string $value): string {
	if ($value === null || $value === '') {
		return '-';
	}

	$timestamp = strtotime($value);
	return $timestamp === false ? '-' : date('M j, Y', $timestamp);
};

$rowsHtml = '';
foreach ((array)($payrollResponse['data'] ?? []) as $itemRaw) {
	$item = (array)$itemRaw;
	$run = (array)($item['payroll_run'] ?? []);
	$period = (array)($run['payroll_period'] ?? []);
	$periodStart = cleanText($period['period_start'] ?? null);
	$periodEnd = cleanText($period['period_end'] ?? null);
	$periodCode = cleanText($period['period_code'] ?? null) ?? 'Payroll Period';

	$periodLabel = ($periodStart !== null && $periodEnd !== null)
		? (date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd)))
		: $periodCode;

	$rowsHtml .= '<tr>'
		. '<td>' . $escape($periodLabel) . '</td>'
		. '<td>' . $escape($formatCurrency((float)($item['gross_pay'] ?? 0))) . '</td>'
		. '<td>' . $escape($formatCurrency((float)($item['deductions_total'] ?? 0))) . '</td>'
		. '<td>' . $escape($formatCurrency((float)($item['net_pay'] ?? 0))) . '</td>'
		. '<td>' . $escape($formatDate(cleanText($item['created_at'] ?? null))) . '</td>'
		. '</tr>';
}

if ($rowsHtml === '') {
	$rowsHtml = '<tr><td colspan="5">No payroll rows found for the selected filters.</td></tr>';
}

$fileName = 'payroll-history-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo '<html><head><meta charset="UTF-8"></head><body>';
echo '<table border="0">';
echo '<tr><td colspan="5"><strong>Payroll History Report</strong></td></tr>';
echo '<tr><td colspan="5">Employee Name: ' . $escape($employeeName) . '</td></tr>';
echo '<tr><td colspan="5">Employee ID: ' . $escape($employeeCode) . '</td></tr>';
echo '<tr><td colspan="5">Generated On: ' . $escape(date('M j, Y g:i A')) . ' PST</td></tr>';
echo '<tr><td colspan="5">Coverage: ' . $escape($formatDate($from)) . ' to ' . $escape($formatDate($to)) . '</td></tr>';
echo '</table>';
echo '<br>';
echo '<table border="1">';
echo '<tr><th>Pay Period</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Created</th></tr>';
echo $rowsHtml;
echo '</table>';
echo '</body></html>';
exit;
