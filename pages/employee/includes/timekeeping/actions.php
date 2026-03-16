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
if (!in_array($action, ['cancel_leave_request', 'create_time_adjustment_request', 'create_official_business_request', 'create_cos_schedule_request', 'create_travel_order_request', 'create_travel_abroad_request', 'create_overtime_request'], true)) {
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

$employeeIsCos = timekeepingIsCosEmploymentStatus($employeeEmploymentStatus ?? null);

$specialRequestActionMap = [
    'create_official_business_request' => 'official_business',
    'create_cos_schedule_request' => 'cos_schedule',
    'create_travel_order_request' => 'travel_order',
    'create_travel_abroad_request' => 'travel_abroad',
];

$buildUuidV4 = static function (): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
};

$storeSpecialAttachment = static function (string $requestType) use ($buildUuidV4): ?array {
    $uploadedFile = $_FILES['supporting_attachment'] ?? null;
    if (!is_array($uploadedFile)) {
        return null;
    }

    $uploadError = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Unable to upload the supporting attachment.', 'timekeeping.php');
    }

    $tmpPath = (string)($uploadedFile['tmp_name'] ?? '');
    $originalName = trim((string)($uploadedFile['name'] ?? ''));
    $fileSize = (int)($uploadedFile['size'] ?? 0);
    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $originalName === '' || $fileSize <= 0) {
        redirectWithState('error', 'Uploaded attachment is invalid. Please try again.', 'timekeeping.php');
    }

    if ($fileSize > (10 * 1024 * 1024)) {
        redirectWithState('error', 'Attachment exceeds the 10MB limit.', 'timekeeping.php');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($extension, $allowedExtensions, true)) {
        redirectWithState('error', 'Attachment file type is not allowed.', 'timekeeping.php');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if ($mimeType !== '' && !in_array($mimeType, $allowedMimes, true)) {
        redirectWithState('error', 'Attachment content type is not allowed.', 'timekeeping.php');
    }

    $storageRoot = dirname(__DIR__, 4) . '/storage/document/timekeeping/' . $requestType;
    if (!is_dir($storageRoot) && !mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
        redirectWithState('error', 'Unable to prepare request attachment storage.', 'timekeeping.php');
    }

    $safeBaseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'attachment';
    $storedFilename = sprintf('%s_%s_%s.%s', $buildUuidV4(), date('YmdHis'), bin2hex(random_bytes(3)), $extension);
    $storedAbsolutePath = $storageRoot . '/' . $storedFilename;
    if (!move_uploaded_file($tmpPath, $storedAbsolutePath)) {
        redirectWithState('error', 'Unable to store the supporting attachment.', 'timekeeping.php');
    }

    return [
        'original_name' => $originalName,
        'safe_name' => $safeBaseName,
        'stored_name' => $storedFilename,
        'relative_path' => 'storage/document/timekeeping/' . $requestType . '/' . $storedFilename,
        'mime_type' => $mimeType,
        'size' => $fileSize,
    ];
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

if (isset($specialRequestActionMap[$action])) {
    $requestType = (string)$specialRequestActionMap[$action];
    $requestMeta = timekeepingRequestTypeMeta($requestType);
    $requestLabel = (string)($requestMeta['label'] ?? 'Special Request');

    if (($requestMeta['requires_cos'] ?? false) && !$employeeIsCos) {
        redirectWithState('error', $requestLabel . ' is available only to COS employees.', 'timekeeping.php');
    }

    $overtimeDate = $toNullable($_POST['request_date'] ?? null, 10);
    $startTime = $toNullable($_POST['start_time'] ?? null, 8);
    $endTime = $toNullable($_POST['end_time'] ?? null, 8);
    $hoursRequestedRaw = $toNullable($_POST['hours_requested'] ?? null, 10);
    $reason = $toNullable($_POST['reason'] ?? null, 500);
    $destination = $toNullable($_POST['destination'] ?? null, 255);
    $referenceNumber = $toNullable($_POST['reference_number'] ?? null, 120);

    if (!$isValidDate($overtimeDate) || $startTime === null || $endTime === null || $hoursRequestedRaw === null || $reason === null) {
        redirectWithState('error', $requestLabel . ' requires date, schedule window, hours, and reason.', 'timekeeping.php');
    }

    if (strtotime($overtimeDate) < strtotime($todayManila)) {
        redirectWithState('error', $requestLabel . ' date cannot be in the past.', 'timekeeping.php');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        redirectWithState('error', 'Schedule start/end time must use HH:MM format.', 'timekeeping.php');
    }

    if (strtotime($overtimeDate . ' ' . $endTime) <= strtotime($overtimeDate . ' ' . $startTime)) {
        redirectWithState('error', 'End time must be later than start time.', 'timekeeping.php');
    }

    $maxEndTime = cleanText($requestMeta['max_end_time'] ?? null);
    if ($maxEndTime !== null && strcmp($endTime, $maxEndTime) > 0) {
        redirectWithState('error', $requestLabel . ' cannot extend beyond ' . $maxEndTime . '.', 'timekeeping.php');
    }

    $hoursRequested = (float)$hoursRequestedRaw;
    if ($hoursRequested <= 0 || $hoursRequested > 24) {
        redirectWithState('error', $requestLabel . ' hours must be greater than 0 and not more than 24.', 'timekeeping.php');
    }

    if (($requestMeta['category'] ?? '') === 'travel' && $destination === null) {
        redirectWithState('error', $requestLabel . ' requires destination or coverage details.', 'timekeeping.php');
    }

    $attachmentMeta = $storeSpecialAttachment($requestType);
    if (($requestMeta['requires_attachment'] ?? false) && $attachmentMeta === null) {
        redirectWithState('error', $requestLabel . ' requires a supporting attachment.', 'timekeeping.php');
    }

    $duplicateResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/overtime_requests?select=id,status,reason'
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&overtime_date=eq.' . rawurlencode($overtimeDate)
        . '&status=in.(pending,approved)'
        . '&limit=20',
        $headers
    );

    if (isSuccessful($duplicateResponse) && !empty((array)($duplicateResponse['data'] ?? []))) {
        foreach ((array)($duplicateResponse['data'] ?? []) as $duplicateRaw) {
            $duplicateRow = (array)$duplicateRaw;
            $parsedDuplicate = timekeepingParseTaggedReason((string)($duplicateRow['reason'] ?? ''));
            if ((string)($parsedDuplicate['request_type'] ?? '') === $requestType) {
                redirectWithState('error', 'You already have a pending/approved ' . strtolower($requestLabel) . ' request on this date.', 'timekeeping.php');
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
            'reason' => timekeepingBuildTaggedReason($requestType, $reason),
            'status' => 'pending',
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to submit ' . strtolower($requestLabel) . '.', 'timekeeping.php');
    }

    $newRow = (array)(((array)$insertResponse['data'])[0] ?? []);
    $requestId = (string)($newRow['id'] ?? '');
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'timekeeping',
            'entity_name' => 'overtime_requests',
            'entity_id' => $requestId,
            'action_name' => $action,
            'new_data' => [
                'request_type' => $requestType,
                'request_label' => $requestLabel,
                'request_category' => (string)($requestMeta['category'] ?? 'other'),
                'request_date' => $overtimeDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hours_requested' => $hoursRequested,
                'reason' => $reason,
                'destination' => $destination,
                'reference_number' => $referenceNumber,
                'is_cos_employee' => $employeeIsCos,
                'attachment' => $attachmentMeta,
            ],
        ]]
    );

    redirectWithState('success', $requestLabel . ' submitted successfully.', 'timekeeping.php');
}
