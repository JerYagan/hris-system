<?php
include "config.php";
include "hris-registry.php";

header('Content-Type: application/json; charset=UTF-8');

$uid = strtoupper(trim((string)($_GET['uid'] ?? $_POST['uid'] ?? '')));
$time = trim((string)($_GET['time'] ?? $_POST['time'] ?? ''));

if ($uid === '') {
	echo json_encode([
		'name' => '',
		'employee_id' => '',
		'birthday' => '',
		'photo' => 'https://via.placeholder.com/150',
		'time_in' => $time,
	]);
	exit;
}

$employees = legacyRfidRegistryBuildRoster($supabase_url, $headers, $api_key, $appBaseUrl);
$emp = legacyRfidRegistryIndexByUid($employees)[$uid] ?? [];

echo json_encode([
	'name' => (string)($emp['name'] ?? ''),
	'employee_id' => (string)($emp['employee_id'] ?? ''),
	'birthday' => (string)($emp['birthday'] ?? ''),
	'photo' => (string)($emp['photo'] ?? 'https://via.placeholder.com/150'),
	'time_in' => $time,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>