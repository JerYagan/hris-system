<?php

include "config.php";

$uid = $_GET['uid'] ?? $_POST['uid'] ?? "";
$simulatedScannedAt = $_POST['simulated_scanned_at'] ?? $_GET['simulated_scanned_at'] ?? "";
$expectsJson = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
	|| str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

$respond = static function (int $statusCode, string $message, array $extra = []) use ($expectsJson): void {
	http_response_code($statusCode);

	if ($expectsJson) {
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array_merge([
			'success' => $statusCode >= 200 && $statusCode < 300,
			'message' => $message,
		], $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		exit;
	}

	echo $message;
	exit;
};

if(!$uid){
	$respond(422, 'No UID detected');
}

/* GET EMPLOYEE */

$url = $supabase_url . "employees?uid=eq.$uid&select=*";

$ch = curl_init($url);

curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$response = curl_exec($ch);

$emp = json_decode($response,true);

if(empty($emp)){

	$respond(404, 'Card not registered', [
		'uid' => $uid,
	]);

}

$employee = $emp[0];

$payload = [
	'device_code' => $hris_rfid_device_code,
	'card_uid' => $uid,
	'scanned_at' => trim((string)$simulatedScannedAt) !== '' ? trim((string)$simulatedScannedAt) : gmdate('c'),
	'legacy_employee_id' => $employee['employee_id'] ?? '',
	'legacy_name' => $employee['name'] ?? '',
	'legacy_source' => 'rfid_system/save_attendance.php',
];

$bridgeHeaders = [
	'Content-Type: application/json',
	'X-RFID-DEVICE-TOKEN: ' . $hris_rfid_device_token,
	'Accept: application/json',
];

$ch = curl_init($hris_rfid_endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $bridgeHeaders);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$bridgeRaw = curl_exec($ch);
$bridgeStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$bridgeError = curl_error($ch);
curl_close($ch);

$bridgeResponse = is_string($bridgeRaw) ? json_decode($bridgeRaw, true) : null;
if (!is_array($bridgeResponse)) {
	$bridgeResponse = [];
}

if ($bridgeStatus >= 200 && $bridgeStatus < 300 && !empty($bridgeResponse['success'])) {
	$respond($bridgeStatus, (string)($bridgeResponse['message'] ?? 'Saved'), [
		'uid' => $uid,
		'employee_id' => $employee['employee_id'] ?? '',
		'name' => $employee['name'] ?? '',
		'attendance_date' => (string)($bridgeResponse['attendance_date'] ?? ''),
		'action' => (string)($bridgeResponse['action'] ?? ''),
		'result_code' => (string)($bridgeResponse['result_code'] ?? ''),
		'scan_event_id' => (string)($bridgeResponse['scan_event_id'] ?? ''),
	]);
}

if ($bridgeError !== '') {
	$respond(502, 'RFID bridge failed: ' . $bridgeError, [
		'uid' => $uid,
	]);
}

if (!empty($bridgeResponse['message'])) {
	$respond($bridgeStatus > 0 ? $bridgeStatus : 422, (string)$bridgeResponse['message'], [
		'uid' => $uid,
		'employee_id' => $employee['employee_id'] ?? '',
		'name' => $employee['name'] ?? '',
		'attendance_date' => (string)($bridgeResponse['attendance_date'] ?? ''),
		'action' => (string)($bridgeResponse['action'] ?? ''),
		'result_code' => (string)($bridgeResponse['result_code'] ?? ''),
		'scan_event_id' => (string)($bridgeResponse['scan_event_id'] ?? ''),
	]);
}

$respond(502, 'RFID bridge failed', [
	'uid' => $uid,
]);

?>