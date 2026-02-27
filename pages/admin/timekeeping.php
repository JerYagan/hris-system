<?php
require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';

if (($_GET['export'] ?? '') === 'attendance_today_csv') {
	$fileDate = date('Ymd');
	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="attendance-today-' . $fileDate . '.csv"');

	$output = fopen('php://output', 'wb');
	if ($output !== false) {
		fputcsv($output, ['Employee', 'Date', 'Time In', 'Time Out', 'Status']);
		foreach ($attendanceLogs as $log) {
			fputcsv($output, [
				(string)($log['employee_name'] ?? ''),
				(string)($log['attendance_date'] ?? ''),
				(string)($log['time_in'] ?? ''),
				(string)($log['time_out'] ?? ''),
				(string)($log['display_status'] ?? ''),
			]);
		}
		fclose($output);
	}

	exit;
}

$pageTitle = 'Timekeeping | Admin';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/timekeeping/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
