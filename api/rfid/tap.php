<?php

require_once dirname(__DIR__, 2) . '/pages/shared/lib/rfid-attendance.php';

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'result_code' => 'method_not_allowed',
        'message' => 'Use POST for RFID tap requests.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$supabase = systemPrivilegedSupabaseConfig();
$supabaseUrl = rtrim((string)($supabase['url'] ?? ''), '/');
$headers = (array)($supabase['headers'] ?? []);

if ($supabaseUrl === '' || $headers === []) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'result_code' => 'server_not_configured',
        'message' => 'Supabase credentials are missing.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$rawBody = file_get_contents('php://input');
$decodedBody = is_string($rawBody) && trim($rawBody) !== '' ? json_decode($rawBody, true) : null;
$payload = is_array($decodedBody) ? $decodedBody : $_POST;
if (!is_array($payload)) {
    $payload = [];
}

$deviceTokenHeader = trim((string)($_SERVER['HTTP_X_RFID_DEVICE_TOKEN'] ?? ''));
$result = rfidProcessAttendanceTap($supabaseUrl, $headers, [
    'device_code' => cleanText($payload['device_code'] ?? null),
    'device_token' => cleanText($payload['device_token'] ?? null) ?? ($deviceTokenHeader !== '' ? $deviceTokenHeader : null),
    'card_uid' => cleanText($payload['card_uid'] ?? null),
    'scanned_at' => cleanText($payload['scanned_at'] ?? null),
    'request_source' => 'device',
    'raw_payload' => is_array($payload) ? $payload : [],
]);

http_response_code((int)($result['http_status'] ?? 200));
echo json_encode([
    'success' => (bool)($result['success'] ?? false),
    'result_code' => (string)($result['result_code'] ?? ''),
    'action' => (string)($result['action'] ?? ''),
    'message' => (string)($result['message'] ?? ''),
    'employee_name' => (string)($result['employee_name'] ?? ''),
    'attendance_date' => (string)($result['attendance_date'] ?? ''),
    'attendance_log_id' => (string)($result['attendance_log_id'] ?? ''),
    'scan_event_id' => (string)($result['scan_event_id'] ?? ''),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;