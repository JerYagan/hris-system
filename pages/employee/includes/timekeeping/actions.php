<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'timekeeping.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'timekeeping.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if (!in_array($action, ['cancel_leave_request', 'create_time_adjustment_request', 'create_official_business_request', 'create_overtime_request'], true)) {
    redirectWithState('error', 'Unsupported timekeeping action.', 'timekeeping.php');
}

$manilaNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayManila = $manilaNow->format('Y-m-d');

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return false;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

if ($action === 'cancel_leave_request') {
    $leaveRequestId = $toNullable($_POST['leave_request_id'] ?? null, 36);
    $cancelReason = $toNullable($_POST['cancel_reason'] ?? null, 500);
    if (!isValidUuid($leaveRequestId)) {
        redirectWithState('error', 'Invalid leave request selected for cancellation.', 'timekeeping.php');
    }

    if ($cancelReason === null) {
        redirectWithState('error', 'Cancellation reason is required.', 'timekeeping.php');
    }

    $leaveRequestResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/leave_requests?select=id,status'
        . '&id=eq.' . rawurlencode((string)$leaveRequestId)
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($leaveRequestResponse) || empty((array)($leaveRequestResponse['data'] ?? []))) {
        redirectWithState('error', 'Leave request not found.', 'timekeeping.php');
    }

    $leaveRequestRow = (array)$leaveRequestResponse['data'][0];
    $currentStatus = strtolower((string)($leaveRequestRow['status'] ?? ''));
    if ($currentStatus !== 'pending') {
        redirectWithState('error', 'Only pending leave requests can be cancelled.', 'timekeeping.php');
    }

    $cancelResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/leave_requests?id=eq.' . rawurlencode((string)$leaveRequestId),
        $headers,
        [
            'status' => 'cancelled',
        ]
    );

    if (!isSuccessful($cancelResponse)) {
        redirectWithState('error', 'Unable to cancel leave request right now.', 'timekeeping.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'leave_requests',
            'entity_id' => $leaveRequestId,
            'action_name' => 'cancel_leave_request',
            'old_data' => ['status' => $currentStatus],
            'new_data' => [
                'status' => 'cancelled',
                'cancel_reason' => $cancelReason,
            ],
        ]]
    );

    redirectWithState('success', 'Leave request cancelled successfully.', 'timekeeping.php');
}

if ($action === 'create_time_adjustment_request') {
    $attendanceLogId = $toNullable($_POST['attendance_log_id'] ?? null, 36);
    $requestedTimeInRaw = $toNullable($_POST['requested_time_in'] ?? null, 5);
    $requestedTimeOutRaw = $toNullable($_POST['requested_time_out'] ?? null, 5);
    $reason = $toNullable($_POST['reason'] ?? null, 500);

    if (!isValidUuid($attendanceLogId) || $reason === null) {
        redirectWithState('error', 'Attendance log and reason are required for time adjustment.', 'timekeeping.php');
    }

    if ($requestedTimeInRaw === null && $requestedTimeOutRaw === null) {
        redirectWithState('error', 'Provide requested time-in and/or requested time-out.', 'timekeeping.php');
    }

    $attendanceResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out'
        . '&id=eq.' . rawurlencode((string)$attendanceLogId)
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($attendanceResponse) || empty((array)($attendanceResponse['data'] ?? []))) {
        redirectWithState('error', 'Attendance record not found or not owned by your account.', 'timekeeping.php');
    }

    $attendanceRow = (array)$attendanceResponse['data'][0];
    $attendanceDate = cleanText($attendanceRow['attendance_date'] ?? null);
    if ($attendanceDate === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)) {
        redirectWithState('error', 'Attendance date is missing for this record.', 'timekeeping.php');
    }

    if ($requestedTimeInRaw !== null && !preg_match('/^\d{2}:\d{2}$/', $requestedTimeInRaw)) {
        redirectWithState('error', 'Requested time-in must use HH:MM format.', 'timekeeping.php');
    }

    if ($requestedTimeOutRaw !== null && !preg_match('/^\d{2}:\d{2}$/', $requestedTimeOutRaw)) {
        redirectWithState('error', 'Requested time-out must use HH:MM format.', 'timekeeping.php');
    }

    $requestedTimeIn = null;
    if ($requestedTimeInRaw !== null) {
        $timeInDate = DateTimeImmutable::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $requestedTimeInRaw, new DateTimeZone('Asia/Manila'));
        if (!($timeInDate instanceof DateTimeImmutable)) {
            redirectWithState('error', 'Invalid requested time-in format.', 'timekeeping.php');
        }
        $requestedTimeIn = $timeInDate->format('c');
    }

    $requestedTimeOut = null;
    if ($requestedTimeOutRaw !== null) {
        $timeOutDate = DateTimeImmutable::createFromFormat('Y-m-d H:i', $attendanceDate . ' ' . $requestedTimeOutRaw, new DateTimeZone('Asia/Manila'));
        if (!($timeOutDate instanceof DateTimeImmutable)) {
            redirectWithState('error', 'Invalid requested time-out format.', 'timekeeping.php');
        }
        $requestedTimeOut = $timeOutDate->format('c');
    }

    if ($requestedTimeIn !== null && $requestedTimeOut !== null && strtotime($requestedTimeOut) <= strtotime($requestedTimeIn)) {
        redirectWithState('error', 'Requested time-out must be later than requested time-in.', 'timekeeping.php');
    }

    $existingResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/time_adjustment_requests?select=id,status'
        . '&attendance_log_id=eq.' . rawurlencode((string)$attendanceLogId)
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&status=eq.pending'
        . '&limit=1',
        $headers
    );

    if (isSuccessful($existingResponse) && !empty((array)($existingResponse['data'] ?? []))) {
        redirectWithState('error', 'A pending time adjustment request already exists for this attendance record.', 'timekeeping.php');
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/time_adjustment_requests',
        $headers,
        [[
            'person_id' => $employeePersonId,
            'attendance_log_id' => $attendanceLogId,
            'requested_time_in' => $requestedTimeIn,
            'requested_time_out' => $requestedTimeOut,
            'reason' => $reason,
            'status' => 'pending',
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to submit time adjustment request.', 'timekeeping.php');
    }

    $newRow = (array)(((array)$insertResponse['data'])[0] ?? []);
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'time_adjustment_requests',
            'entity_id' => (string)($newRow['id'] ?? null),
            'action_name' => 'create_time_adjustment_request',
            'new_data' => [
                'attendance_log_id' => $attendanceLogId,
                'requested_time_in' => $requestedTimeIn,
                'requested_time_out' => $requestedTimeOut,
            ],
        ]]
    );

    redirectWithState('success', 'Time adjustment request submitted successfully.', 'timekeeping.php');
}

if ($action === 'create_overtime_request') {
    redirectWithState('error', 'Overtime filing has been replaced by CTO and Official Business requests.', 'timekeeping.php');
}

if ($action === 'create_official_business_request') {
    $overtimeDate = $toNullable($_POST['ob_date'] ?? null, 10);
    $startTime = $toNullable($_POST['time_out'] ?? null, 8);
    $endTime = $toNullable($_POST['time_in'] ?? null, 8);
    $hoursRequestedRaw = $toNullable($_POST['hours_requested'] ?? null, 10);
    $reason = $toNullable($_POST['reason'] ?? null, 500);

    if (!$isValidDate($overtimeDate) || $startTime === null || $endTime === null || $hoursRequestedRaw === null || $reason === null) {
        redirectWithState('error', 'Official business request requires date, time-out/time-in, hours, and reason.', 'timekeeping.php');
    }

    if (strtotime($overtimeDate) < strtotime($todayManila)) {
        redirectWithState('error', 'Official business date cannot be in the past.', 'timekeeping.php');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        redirectWithState('error', 'Time-out and time-in must use HH:MM format.', 'timekeeping.php');
    }

    if (strtotime($overtimeDate . ' ' . $endTime) <= strtotime($overtimeDate . ' ' . $startTime)) {
        redirectWithState('error', 'Time-in must be later than time-out.', 'timekeeping.php');
    }

    $hoursRequested = (float)$hoursRequestedRaw;
    if ($hoursRequested <= 0 || $hoursRequested > 24) {
        redirectWithState('error', 'Official business hours must be greater than 0 and not more than 24.', 'timekeeping.php');
    }

    $duplicateResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/overtime_requests?select=id,status,reason'
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&overtime_date=eq.' . rawurlencode($overtimeDate)
        . '&status=in.(pending,approved)'
        . '&limit=1',
        $headers
    );

    if (isSuccessful($duplicateResponse) && !empty((array)($duplicateResponse['data'] ?? []))) {
        foreach ((array)($duplicateResponse['data'] ?? []) as $duplicateRaw) {
            $duplicateRow = (array)$duplicateRaw;
            $duplicateReason = strtolower(trim((string)($duplicateRow['reason'] ?? '')));
            if (str_starts_with($duplicateReason, '[ob]')) {
                redirectWithState('error', 'You already have a pending/approved official business request on this date.', 'timekeeping.php');
            }
        }
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/overtime_requests',
        $headers,
        [[
            'person_id' => $employeePersonId,
            'overtime_date' => $overtimeDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'hours_requested' => $hoursRequested,
            'reason' => '[OB] ' . $reason,
            'status' => 'pending',
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to submit official business request.', 'timekeeping.php');
    }

    $newRow = (array)(((array)$insertResponse['data'])[0] ?? []);
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'official_business_requests',
            'entity_id' => (string)($newRow['id'] ?? null),
            'action_name' => 'create_official_business_request',
            'new_data' => [
                'official_business_date' => $overtimeDate,
                'hours_requested' => $hoursRequested,
            ],
        ]]
    );

    redirectWithState('success', 'Official business request submitted successfully.', 'timekeeping.php');
}
