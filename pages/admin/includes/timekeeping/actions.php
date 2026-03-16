<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

$isFinalDecision = static function (string $status): bool {
    return in_array(strtolower(trim($status)), ['approved', 'rejected', 'cancelled'], true);
};

$isLateByPolicy = static function (?string $timeValue): bool {
    $raw = trim((string)$timeValue);
    if ($raw === '') {
        return false;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return false;
    }

    return date('H:i:s', $timestamp) >= '09:01:00';
};

$matchesApprovedFlexiWindow = static function (?string $timeIn, ?string $timeOut): bool {
    $startRaw = trim((string)$timeIn);
    $endRaw = trim((string)$timeOut);
    if ($startRaw === '' || $endRaw === '') {
        return false;
    }

    $startTs = strtotime($startRaw);
    $endTs = strtotime($endRaw);
    if ($startTs === false || $endTs === false) {
        return false;
    }

    $startKey = date('H:i', $startTs);
    $endKey = date('H:i', $endTs);

    $approvedWindows = [
        '07:00|16:00',
        '08:00|17:00',
        '09:00|18:00',
    ];

    return in_array($startKey . '|' . $endKey, $approvedWindows, true);
};

$currentPstLabel = static function (): string {
    try {
        $pst = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
        return $pst->format('M d, Y h:i A') . ' PST';
    } catch (Throwable $exception) {
        return gmdate('M d, Y h:i A') . ' UTC';
    }
};

$notifyStaffFinalDecision = static function (
    string $entityName,
    string $entityId,
    string $recommendationAction,
    string $decision,
    ?string $notes,
    string $title,
    string $linkUrl,
    string $subjectLabel
) use ($supabaseUrl, $headers, $adminUserId, $currentPstLabel): void {
    if ($entityId === '' || $recommendationAction === '') {
        return;
    }

    $recommendationLogResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=actor_user_id,created_at'
        . '&entity_name=eq.' . rawurlencode($entityName)
        . '&entity_id=eq.' . rawurlencode($entityId)
        . '&action_name=eq.' . rawurlencode($recommendationAction)
        . '&order=created_at.desc&limit=1',
        $headers
    );

    if (!isSuccessful($recommendationLogResponse)) {
        return;
    }

    $recommendationLog = (array)(($recommendationLogResponse['data'] ?? [])[0] ?? []);
    $staffRecipientUserId = (string)($recommendationLog['actor_user_id'] ?? '');
    if ($staffRecipientUserId === '' || !isValidUuid($staffRecipientUserId) || $staffRecipientUserId === $adminUserId) {
        return;
    }

    $decisionLabel = str_replace('_', ' ', strtolower($decision));
    $notificationBody = 'Your forwarded ' . $subjectLabel . ' was ' . $decisionLabel . ' by Admin on ' . $currentPstLabel() . '.';
    $notesText = trim((string)$notes);
    if ($notesText !== '') {
        $notificationBody .= ' Remarks: ' . $notesText;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $staffRecipientUserId,
            'category' => 'timekeeping',
            'title' => $title,
            'body' => $notificationBody,
            'link_url' => $linkUrl,
        ]]
    );
};

$isValidDate = static function (?string $value): bool {
    if ($value === null || $value === '') {
        return false;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

$extractApiErrorMessage = static function (array $response, string $fallback): string {
    $data = $response['data'] ?? null;
    if (is_array($data)) {
        $message = trim((string)($data['message'] ?? $data['error_description'] ?? $data['hint'] ?? ''));
        if ($message !== '') {
            return $message;
        }
    }

    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        return $raw;
    }

    $error = trim((string)($response['error'] ?? ''));
    if ($error !== '') {
        return $error;
    }

    return $fallback;
};

$resolveLeavePointBreakdown = static function (string $leaveTypeCode, string $leaveTypeName, float $points): array {
    $normalizedCode = strtolower(trim($leaveTypeCode));
    $normalizedName = strtolower(trim($leaveTypeName));
    $safePoints = max(0, $points);
    $breakdown = [
        'sl' => 0.0,
        'vl' => 0.0,
        'cto' => 0.0,
    ];

    if ($safePoints <= 0) {
        return $breakdown;
    }

    if ($normalizedCode === 'sl' || str_contains($normalizedName, 'sick')) {
        $breakdown['sl'] = $safePoints;
        return $breakdown;
    }

    if ($normalizedCode === 'vl' || str_contains($normalizedName, 'vacation')) {
        $breakdown['vl'] = $safePoints;
        return $breakdown;
    }

    if ($normalizedCode === 'cto' || str_contains($normalizedName, 'cto') || str_contains($normalizedName, 'compensatory')) {
        $breakdown['cto'] = $safePoints;
    }

    return $breakdown;
};

if ($action === 'log_employee_attendance') {
    $personId = cleanText($_POST['attendance_person_id'] ?? null) ?? '';
    $attendanceDate = cleanText($_POST['attendance_date'] ?? null) ?? '';
    $entryType = strtolower((string)(cleanText($_POST['attendance_entry_type'] ?? null) ?? ''));
    $attendanceTimeRaw = cleanText($_POST['attendance_time'] ?? null) ?? '';
    $reference = cleanText($_POST['attendance_reference'] ?? null);

    if (!isValidUuid($personId) || !$isValidDate($attendanceDate)) {
        redirectWithState('error', 'Employee and a valid attendance date are required.');
    }

    if (!in_array($entryType, ['time_in', 'time_out'], true)) {
        redirectWithState('error', 'Select whether you are logging a time-in or time-out entry.');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $attendanceTimeRaw)) {
        redirectWithState('error', 'Attendance time must use HH:MM format.');
    }

    $attendanceDateTime = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i',
        $attendanceDate . ' ' . $attendanceTimeRaw,
        new DateTimeZone('Asia/Manila')
    );

    if (!($attendanceDateTime instanceof DateTimeImmutable)) {
        redirectWithState('error', 'Invalid attendance date/time supplied.');
    }

    $attendanceTimestamp = $attendanceDateTime->format('c');
    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );

    $personRow = (array)(($personResponse['data'] ?? [])[0] ?? []);
    if (!isSuccessful($personResponse) || $personRow === []) {
        redirectWithState('error', 'Selected employee was not found.');
    }

    $existingLogResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/attendance_logs?select=id,time_in,time_out,attendance_status'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&attendance_date=eq.' . rawurlencode($attendanceDate)
        . '&order=created_at.desc&limit=1',
        $headers
    );

    if (!isSuccessful($existingLogResponse)) {
        redirectWithState('error', $extractApiErrorMessage($existingLogResponse, 'Failed to load the employee attendance record.'));
    }

    $existingLog = (array)(($existingLogResponse['data'] ?? [])[0] ?? []);
    $existingLogId = (string)($existingLog['id'] ?? '');
    $existingTimeIn = cleanText($existingLog['time_in'] ?? null);
    $existingTimeOut = cleanText($existingLog['time_out'] ?? null);

    $lateMinutes = 0;
    $lateReference = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $attendanceDate . ' 09:00:00',
        new DateTimeZone('Asia/Manila')
    );
    if ($lateReference instanceof DateTimeImmutable && $attendanceDateTime > $lateReference) {
        $lateMinutes = (int)floor(($attendanceDateTime->getTimestamp() - $lateReference->getTimestamp()) / 60);
    }

    if ($entryType === 'time_in') {
        if ($existingTimeIn !== null) {
            redirectWithState('error', 'A time-in entry already exists for this employee on the selected date.');
        }

        if ($existingTimeOut !== null && strtotime($existingTimeOut) <= strtotime($attendanceTimestamp)) {
            redirectWithState('error', 'Time-in must be earlier than the existing time-out entry.');
        }

        $attendancePayload = [
            'attendance_date' => $attendanceDate,
            'time_in' => $attendanceTimestamp,
            'attendance_status' => $lateMinutes > 0 ? 'late' : 'present',
            'late_minutes' => $lateMinutes,
            'source' => 'manual',
        ];

        if ($existingLogId !== '') {
            $saveResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/attendance_logs?id=eq.' . rawurlencode($existingLogId),
                array_merge($headers, ['Prefer: return=minimal']),
                $attendancePayload
            );
            $savedAttendanceId = $existingLogId;
        } else {
            $saveResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/attendance_logs',
                array_merge($headers, ['Prefer: return=representation']),
                [[
                    'person_id' => $personId,
                    'attendance_date' => $attendanceDate,
                    'time_in' => $attendanceTimestamp,
                    'attendance_status' => $lateMinutes > 0 ? 'late' : 'present',
                    'late_minutes' => $lateMinutes,
                    'source' => 'manual',
                ]]
            );
            $savedAttendanceId = (string)((($saveResponse['data'] ?? [])[0]['id'] ?? ''));
        }

        if (!isSuccessful($saveResponse)) {
            redirectWithState('error', $extractApiErrorMessage($saveResponse, 'Failed to log employee time-in.'));
        }
    } else {
        if ($existingLogId === '') {
            redirectWithState('error', 'Log a time-in entry first before adding a time-out.');
        }

        if ($existingTimeIn === null) {
            redirectWithState('error', 'The selected attendance record has no time-in yet. Log time-in first.');
        }

        if ($existingTimeOut !== null) {
            redirectWithState('error', 'A time-out entry already exists for this employee on the selected date.');
        }

        if (strtotime($attendanceTimestamp) <= strtotime($existingTimeIn)) {
            redirectWithState('error', 'Time-out must be later than the existing time-in entry.');
        }

        $hoursWorked = round((strtotime($attendanceTimestamp) - strtotime($existingTimeIn)) / 3600, 2);
        $saveResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/attendance_logs?id=eq.' . rawurlencode($existingLogId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'time_out' => $attendanceTimestamp,
                'hours_worked' => max(0, $hoursWorked),
            ]
        );
        $savedAttendanceId = $existingLogId;

        if (!isSuccessful($saveResponse)) {
            redirectWithState('error', $extractApiErrorMessage($saveResponse, 'Failed to log employee time-out.'));
        }
    }

    $employeeName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));
    if ($employeeName === '') {
        $employeeName = 'Selected employee';
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'timekeeping',
            'entity_name' => 'attendance_logs',
            'entity_id' => $savedAttendanceId !== '' ? $savedAttendanceId : null,
            'action_name' => 'log_employee_attendance',
            'new_data' => [
                'person_id' => $personId,
                'attendance_date' => $attendanceDate,
                'entry_type' => $entryType,
                'attendance_time' => $attendanceTimestamp,
                'reference' => $reference,
            ],
        ]]
    );

    redirectWithState(
        'success',
        ucfirst(str_replace('_', '-', $entryType)) . ' logged successfully for ' . $employeeName . '.'
    );
}

if ($action === 'log_leave_from_card') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $leaveTypeId = cleanText($_POST['leave_type_id'] ?? null) ?? '';
    $dateFrom = cleanText($_POST['date_from'] ?? null) ?? '';
    $dateTo = cleanText($_POST['date_to'] ?? null) ?? '';
    $reference = cleanText($_POST['reference'] ?? null);
    $slPoints = max(0, (float)($_POST['sl_points'] ?? 0));
    $vlPoints = max(0, (float)($_POST['vl_points'] ?? 0));
    $ctoPoints = max(0, (float)($_POST['cto_points'] ?? 0));

    if (!isValidUuid($personId) || !isValidUuid($leaveTypeId) || !$isValidDate($dateFrom) || !$isValidDate($dateTo)) {
        redirectWithState('error', 'Employee, leave type, and valid date range are required.');
    }

    if (strtotime($dateTo) < strtotime($dateFrom)) {
        redirectWithState('error', 'Leave end date cannot be earlier than leave start date.');
    }

    $fromYear = (int)date('Y', strtotime($dateFrom));
    $toYear = (int)date('Y', strtotime($dateTo));
    if ($fromYear !== $toYear) {
        redirectWithState('error', 'Please log leave per year. Split cross-year leave card entries into separate records.');
    }

    $fromTs = strtotime($dateFrom . ' 00:00:00');
    $toTs = strtotime($dateTo . ' 00:00:00');
    if ($fromTs === false || $toTs === false) {
        redirectWithState('error', 'Invalid date range for leave days computation.');
    }

    $daysCount = (float)(int)floor(($toTs - $fromTs) / 86400) + 1.0;
    if ($daysCount <= 0) {
        redirectWithState('error', 'Leave days must be greater than zero.');
    }

    if (($slPoints + $vlPoints + $ctoPoints) <= 0) {
        redirectWithState('error', 'Enter at least one SL, VL, or CTO point value to add for this employee.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );
    $personRow = (array)(($personResponse['data'] ?? [])[0] ?? []);
    if (!isSuccessful($personResponse) || $personRow === []) {
        redirectWithState('error', 'Selected employee was not found.');
    }

    $leaveTypeResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_types?select=id,leave_name,leave_code,is_active&id=eq.' . rawurlencode($leaveTypeId) . '&limit=1',
        $headers
    );
    $leaveTypeRow = (array)(($leaveTypeResponse['data'] ?? [])[0] ?? []);
    $isActiveRaw = strtolower(trim((string)($leaveTypeRow['is_active'] ?? 'false')));
    $isActive = in_array($isActiveRaw, ['1', 'true', 't', 'yes'], true);
    if (!isSuccessful($leaveTypeResponse) || $leaveTypeRow === [] || !$isActive) {
        redirectWithState('error', 'Selected leave type is invalid or inactive.');
    }

    $leaveTypeName = (string)($leaveTypeRow['leave_name'] ?? 'Leave');
    $leaveTypeCode = (string)($leaveTypeRow['leave_code'] ?? '');
    $ctoBucketMeta = null;
    if ($ctoPoints > 0) {
        $fromMonth = (int)date('n', strtotime($dateFrom));
        $fromYearLabel = (int)date('Y', strtotime($dateFrom));
        $halfKey = $fromMonth <= 6 ? 'jan_jun' : 'jul_dec';
        $halfLabel = $fromMonth <= 6 ? 'JAN-JUN' : 'JULY-DEC';
        $ctoBucketMeta = [
            'bucket_key' => $halfKey,
            'bucket_label' => $halfLabel,
            'year' => $fromYearLabel,
            'display_label' => $halfLabel . ' ' . $fromYearLabel,
        ];
    }
    $leavePointBreakdown = [
        'sl' => round($slPoints, 2),
        'vl' => round($vlPoints, 2),
        'cto' => round($ctoPoints, 2),
    ];

    $overlapResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/leave_requests?select=id,date_from,date_to,status'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&leave_type_id=eq.' . rawurlencode($leaveTypeId)
        . '&status=in.(pending,approved)'
        . '&order=created_at.desc&limit=200',
        $headers
    );
    if (isSuccessful($overlapResponse)) {
        foreach ((array)($overlapResponse['data'] ?? []) as $existingRaw) {
            $existing = (array)$existingRaw;
            $existingFrom = cleanText($existing['date_from'] ?? null);
            $existingTo = cleanText($existing['date_to'] ?? null);
            if ($existingFrom === null || $existingTo === null) {
                continue;
            }

            $hasOverlap = strtotime($dateFrom) <= strtotime($existingTo) && strtotime($dateTo) >= strtotime($existingFrom);
            if ($hasOverlap) {
                redirectWithState('error', 'A pending/approved leave entry already overlaps this leave-card date range.');
            }
        }
    }

    $reason = 'Logged from submitted leave card template.';
    if ($reference !== null && $reference !== '') {
        $reason .= ' Reference: ' . $reference;
    }

    $pointSummaryParts = [];
    foreach (['sl' => 'SL', 'vl' => 'VL', 'cto' => 'CTO'] as $pointKey => $pointLabel) {
        $pointValue = (float)($leavePointBreakdown[$pointKey] ?? 0);
        if ($pointValue <= 0) {
            continue;
        }

        $pointSummaryParts[] = $pointLabel . ': ' . number_format($pointValue, 2);
    }

    if (!empty($pointSummaryParts)) {
        $reason .= ' Points logged: ' . implode(', ', $pointSummaryParts) . '.';
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/leave_requests',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'person_id' => $personId,
            'leave_type_id' => $leaveTypeId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'days_count' => $daysCount,
            'reason' => $reason,
            'status' => 'approved',
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
            'review_notes' => 'Admin logged leave based on submitted leave card template.',
            'updated_at' => gmdate('c'),
        ]]
    );

    $newLeave = (array)(($insertResponse['data'] ?? [])[0] ?? []);
    if (!isSuccessful($insertResponse) || $newLeave === []) {
        redirectWithState('error', 'Failed to log leave card entry.');
    }

    $allLeaveTypesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_types?select=id,leave_name,leave_code,is_active&is_active=eq.true&limit=200',
        $headers
    );

    if (!isSuccessful($allLeaveTypesResponse)) {
        redirectWithState('error', 'Leave entry was saved, but the accumulated leave types could not be resolved.');
    }

    $bucketTypeByKey = [];
    foreach ((array)($allLeaveTypesResponse['data'] ?? []) as $typeRaw) {
        $type = (array)$typeRaw;
        $typeId = trim((string)($type['id'] ?? ''));
        if ($typeId === '') {
            continue;
        }

        $bucket = '';
        $candidateBreakdown = $resolveLeavePointBreakdown(
            (string)($type['leave_code'] ?? ''),
            (string)($type['leave_name'] ?? ''),
            1
        );
        foreach ($candidateBreakdown as $candidateKey => $candidateValue) {
            if ((float)$candidateValue > 0) {
                $bucket = $candidateKey;
                break;
            }
        }

        if ($bucket !== '' && !isset($bucketTypeByKey[$bucket])) {
            $bucketTypeByKey[$bucket] = [
                'id' => $typeId,
                'leave_name' => (string)($type['leave_name'] ?? 'Leave'),
                'leave_code' => (string)($type['leave_code'] ?? ''),
            ];
        }
    }

    $now = gmdate('c');
    foreach ($leavePointBreakdown as $pointKey => $pointValue) {
        $normalizedPointValue = max(0, (float)$pointValue);
        if ($normalizedPointValue <= 0) {
            continue;
        }

        $bucketType = $bucketTypeByKey[$pointKey] ?? null;
        if (!is_array($bucketType) || !isValidUuid((string)($bucketType['id'] ?? ''))) {
            redirectWithState('error', 'Leave entry was saved, but the ' . strtoupper($pointKey) . ' balance type is not configured.');
        }

        $bucketLeaveTypeId = (string)$bucketType['id'];
        $balanceResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/leave_balances?select=id,earned_credits,used_credits,remaining_credits'
            . '&person_id=eq.' . rawurlencode($personId)
            . '&leave_type_id=eq.' . rawurlencode($bucketLeaveTypeId)
            . '&year=eq.' . $fromYear
            . '&limit=1',
            $headers
        );

        if (!isSuccessful($balanceResponse)) {
            redirectWithState('error', 'Leave entry was saved, but failed to load the ' . strtoupper($pointKey) . ' balance record.');
        }

        $existingBalance = (array)(($balanceResponse['data'] ?? [])[0] ?? []);
        if ($existingBalance !== []) {
            $earnedCredits = (float)($existingBalance['earned_credits'] ?? 0) + $normalizedPointValue;
            $remainingCredits = (float)($existingBalance['remaining_credits'] ?? 0) + $normalizedPointValue;

            $updateBalanceResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/leave_balances?id=eq.' . rawurlencode((string)($existingBalance['id'] ?? '')),
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'earned_credits' => $earnedCredits,
                    'remaining_credits' => $remainingCredits,
                    'updated_at' => $now,
                ]
            );

            if (!isSuccessful($updateBalanceResponse)) {
                redirectWithState('error', 'Leave entry was saved, but failed to update the ' . strtoupper($pointKey) . ' balance.');
            }
        } else {
            $insertBalanceResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/leave_balances',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'person_id' => $personId,
                    'leave_type_id' => $bucketLeaveTypeId,
                    'year' => $fromYear,
                    'earned_credits' => $normalizedPointValue,
                    'used_credits' => 0,
                    'remaining_credits' => $normalizedPointValue,
                    'updated_at' => $now,
                ]]
            );

            if (!isSuccessful($insertBalanceResponse)) {
                redirectWithState('error', 'Leave entry was saved, but failed to create the ' . strtoupper($pointKey) . ' balance.');
            }
        }
    }

    $personUserId = (string)($personRow['user_id'] ?? '');
    if ($personUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $personUserId,
                'category' => 'timekeeping',
                'title' => 'Leave Logged from Leave Card',
                'body' => 'An approved leave entry was logged by Admin based on your submitted leave card template.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'timekeeping',
            'entity_name' => 'leave_requests',
            'entity_id' => (string)($newLeave['id'] ?? null),
            'action_name' => 'log_leave_from_card',
            'new_data' => [
                'person_id' => $personId,
                'leave_type_id' => $leaveTypeId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'days_count' => $daysCount,
                'leave_type_name' => $leaveTypeName,
                'leave_point_breakdown' => $leavePointBreakdown,
                'cto_bucket' => $ctoBucketMeta,
            ],
        ]]
    );

    redirectWithState('success', 'Leave card entry logged successfully and accumulated SL/VL/CTO points were updated.');
}

if ($action === 'review_time_adjustment') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($requestId === '' || $decision === '') {
        redirectWithState('error', 'Adjustment request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid adjustment decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id,status,person_id,attendance_log_id,requested_time_in,requested_time_out,person:people(user_id)&id=eq.' . $requestId . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Time adjustment request not found.');
    }

    $oldStatus = strtolower((string)($requestRow['status'] ?? 'pending'));
    if ($isFinalDecision($oldStatus)) {
        redirectWithState('error', 'This time adjustment request is locked after final decision. Rejected requests require a new submission.');
    }

    $attendanceLogId = (string)($requestRow['attendance_log_id'] ?? '');
    $requestedTimeIn = cleanText($requestRow['requested_time_in'] ?? null);
    $requestedTimeOut = cleanText($requestRow['requested_time_out'] ?? null);

    if ($decision === 'approved') {
        $effectiveTimeIn = $requestedTimeIn;
        $effectiveTimeOut = $requestedTimeOut;

        if ($attendanceLogId !== '' && ($effectiveTimeIn === null || $effectiveTimeIn === '' || $effectiveTimeOut === null || $effectiveTimeOut === '')) {
            $attendanceResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/attendance_logs?select=id,time_in,time_out&id=eq.' . rawurlencode($attendanceLogId) . '&limit=1',
                $headers
            );
            $attendanceRow = $attendanceResponse['data'][0] ?? null;
            if (is_array($attendanceRow)) {
                if ($effectiveTimeIn === null || $effectiveTimeIn === '') {
                    $effectiveTimeIn = cleanText($attendanceRow['time_in'] ?? null);
                }
                if ($effectiveTimeOut === null || $effectiveTimeOut === '') {
                    $effectiveTimeOut = cleanText($attendanceRow['time_out'] ?? null);
                }
            }
        }

        if (!$matchesApprovedFlexiWindow($effectiveTimeIn, $effectiveTimeOut)) {
            redirectWithState('error', 'Approved adjustments must match the supported flexi windows only: 7AM-4PM, 8AM-5PM, or 9AM-6PM.');
        }
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?id=eq.' . $requestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update adjustment request.');
    }

    if ($decision === 'approved' && $attendanceLogId !== '' && ($requestedTimeIn || $requestedTimeOut)) {
        $attendancePatch = [];
        if ($requestedTimeIn) {
            $attendancePatch['time_in'] = $requestedTimeIn;
            if ($isLateByPolicy($requestedTimeIn)) {
                $attendancePatch['attendance_status'] = 'late';

                $lateReference = strtotime(substr($requestedTimeIn, 0, 10) . ' 09:00:00');
                $lateActual = strtotime($requestedTimeIn);
                if ($lateReference !== false && $lateActual !== false && $lateActual > $lateReference) {
                    $attendancePatch['late_minutes'] = (int)floor(($lateActual - $lateReference) / 60);
                }
            } else {
                $attendancePatch['attendance_status'] = 'present';
                $attendancePatch['late_minutes'] = 0;
            }
        }
        if ($requestedTimeOut) {
            $attendancePatch['time_out'] = $requestedTimeOut;
        }

        if (!empty($attendancePatch)) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/attendance_logs?id=eq.' . $attendanceLogId,
                array_merge($headers, ['Prefer: return=minimal']),
                $attendancePatch
            );
        }
    }

    $recipientUserId = (string)($requestRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => 'Time Adjustment Request Updated',
                'body' => 'Your time adjustment request was marked as ' . str_replace('_', ' ', $decision) . '.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    $notifyStaffFinalDecision(
        'time_adjustment_requests',
        $requestId,
        'recommend_time_adjustment',
        $decision,
        $notes,
        'Time Adjustment Recommendation Reviewed',
        '/hris-system/pages/staff/timekeeping.php',
        'time adjustment recommendation'
    );

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'timekeeping',
        'time_adjustment_requests',
        $requestId,
        'review_adjustment',
        $oldStatus,
        $decision,
        $notes,
        [
            'attendance_log_id' => $attendanceLogId !== '' ? $attendanceLogId : null,
        ]
    );

    redirectWithState('success', 'Time adjustment request updated successfully.');
}

if ($action === 'review_leave_request') {
    $leaveRequestId = cleanText($_POST['leave_request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($leaveRequestId === '' || $decision === '') {
        redirectWithState('error', 'Leave request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid leave decision selected.');
    }

    $leaveResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_requests?select=id,status,person_id,person:people(user_id)&id=eq.' . $leaveRequestId . '&limit=1',
        $headers
    );

    $leaveRow = $leaveResponse['data'][0] ?? null;
    if (!is_array($leaveRow)) {
        redirectWithState('error', 'Leave request not found.');
    }

    $oldStatus = strtolower((string)($leaveRow['status'] ?? 'pending'));
    if ($isFinalDecision($oldStatus)) {
        redirectWithState('error', 'This leave request is locked after final decision. Rejected requests cannot be modified.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/leave_requests?id=eq.' . $leaveRequestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $adminUserId !== '' ? $adminUserId : null,
            'reviewed_at' => gmdate('c'),
            'review_notes' => $notes,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update leave request.');
    }

    $recipientUserId = (string)($leaveRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => 'Leave Request Updated',
                'body' => 'Your leave request was ' . $decision . '.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    $notifyStaffFinalDecision(
        'leave_requests',
        $leaveRequestId,
        'recommend_leave_request',
        $decision,
        $notes,
        'Leave Recommendation Reviewed',
        '/hris-system/pages/staff/timekeeping.php',
        'leave recommendation'
    );

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'timekeeping',
        'leave_requests',
        $leaveRequestId,
        'review_leave',
        $oldStatus,
        $decision,
        $notes
    );

    redirectWithState('success', 'Leave request updated successfully.');
}

if ($action === 'review_cto_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($requestId === '' || $decision === '') {
        redirectWithState('error', 'CTO request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid CTO decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,status,reason,person_id,person:people(user_id)&id=eq.' . $requestId . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'CTO request not found.');
    }

    $oldStatus = strtolower((string)($requestRow['status'] ?? 'pending'));
    if ($isFinalDecision($oldStatus)) {
        redirectWithState('error', 'This CTO request is locked after final decision.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/overtime_requests?id=eq.' . $requestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'approved_by' => $adminUserId !== '' ? $adminUserId : null,
            'approved_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update CTO request.');
    }

    $recipientUserId = (string)($requestRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => 'CTO Request Updated',
                'body' => 'Your CTO request was ' . $decision . '.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    $notifyStaffFinalDecision(
        'overtime_requests',
        $requestId,
        'recommend_ob_request',
        $decision,
        $notes,
        'Official Business Recommendation Reviewed',
        '/hris-system/pages/staff/timekeeping.php',
        'official business recommendation'
    );

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'timekeeping',
        'overtime_requests',
        $requestId,
        'review_cto',
        $oldStatus,
        $decision,
        $notes
    );

    redirectWithState('success', 'CTO request updated successfully.');
}

if ($action === 'review_ob_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if ($requestId === '' || $decision === '') {
        redirectWithState('error', 'Special request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid special request decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,status,reason,person_id,person:people(user_id)&id=eq.' . $requestId . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Special request not found.');
    }

    $parsedRequest = timekeepingParseTaggedReason((string)($requestRow['reason'] ?? ''));
    if (($parsedRequest['is_special'] ?? false) !== true) {
        redirectWithState('error', 'Selected request is not a tagged special timekeeping request.');
    }

    $requestLabel = (string)($parsedRequest['label'] ?? 'Special request');
    $requestLabelLower = strtolower($requestLabel);

    $oldStatus = strtolower((string)($requestRow['status'] ?? 'pending'));
    if ($isFinalDecision($oldStatus)) {
        redirectWithState('error', 'This ' . $requestLabelLower . ' is locked after final decision.');
    }

    $persistedDecision = $decision === 'needs_revision' ? 'cancelled' : $decision;

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/overtime_requests?id=eq.' . $requestId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $persistedDecision,
            'approved_by' => $adminUserId !== '' ? $adminUserId : null,
            'approved_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update ' . $requestLabelLower . '.');
    }

    $recipientUserId = (string)($requestRow['person']['user_id'] ?? '');
    if ($recipientUserId !== '') {
        $decisionLabel = $decision === 'needs_revision' ? 'returned for revision' : $decision;
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'timekeeping',
                'title' => $requestLabel . ' Updated',
                'body' => 'Your ' . $requestLabelLower . ' was ' . $decisionLabel . '.',
                'link_url' => '/hris-system/pages/employee/timekeeping.php',
            ]]
        );
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'timekeeping',
        'overtime_requests',
        $requestId,
        'review_ob',
        $oldStatus,
        $decision,
        $notes,
        ['persisted_status' => $persistedDecision, 'request_type' => (string)($parsedRequest['request_type'] ?? 'official_business')]
    );

    $notifyStaffFinalDecision(
        'overtime_requests',
        $requestId,
        'recommend_ob_request',
        $decision,
        $notes,
        $requestLabel . ' Recommendation Reviewed',
        '/hris-system/pages/staff/timekeeping.php',
        $requestLabelLower . ' recommendation'
    );

    redirectWithState('success', $requestLabel . ' updated successfully.');
}

if ($action === 'save_holiday_config') {
    $holidayDate = cleanText($_POST['holiday_date'] ?? null);
    $holidayName = cleanText($_POST['holiday_name'] ?? null);
    $holidayType = strtolower((string)(cleanText($_POST['holiday_type'] ?? null) ?? 'regular'));

    $paidHandling = strtolower((string)(cleanText($_POST['paid_handling'] ?? null) ?? 'policy_based'));
    $applyToRegular = isset($_POST['apply_to_regular']);
    $applyToSpecial = isset($_POST['apply_to_special']);
    $applyToLocal = isset($_POST['apply_to_local']);
    $includeSuspension = isset($_POST['include_suspension']);

    if (!in_array($holidayType, ['regular', 'special', 'local'], true)) {
        redirectWithState('error', 'Invalid holiday type selected.');
    }

    if (!in_array($paidHandling, ['policy_based', 'always_paid', 'always_unpaid'], true)) {
        redirectWithState('error', 'Invalid payroll paid-handling option selected.');
    }

    if ($holidayDate && $holidayName) {
        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/holidays?select=id'
            . '&holiday_date=eq.' . rawurlencode((string)$holidayDate)
            . '&office_id=is.null&limit=1',
            $headers
        );

        $existingHolidayId = (string)($existingResponse['data'][0]['id'] ?? '');
        if ($existingHolidayId !== '') {
            $holidaySaveResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/holidays?id=eq.' . $existingHolidayId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType,
                ]
            );
        } else {
            $holidaySaveResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/holidays',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'holiday_date' => $holidayDate,
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType,
                    'office_id' => null,
                ]]
            );
        }

        if (!isSuccessful($holidaySaveResponse)) {
            redirectWithState('error', 'Failed to save holiday record.');
        }
    }

    $policyPayload = [
        'paid_handling' => $paidHandling,
        'apply_to_regular' => $applyToRegular,
        'apply_to_special' => $applyToSpecial,
        'apply_to_local' => $applyToLocal,
        'include_suspension' => $includeSuspension,
        'updated_at' => gmdate('c'),
    ];

    $policyResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/system_settings?on_conflict=setting_key',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'setting_key' => 'timekeeping.holiday_payroll_policy',
            'setting_value' => $policyPayload,
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => gmdate('c'),
        ]]
    );

    if (!isSuccessful($policyResponse)) {
        redirectWithState('error', 'Holiday payroll policy saved, but failed to store policy settings.');
    }

    redirectWithState('success', 'Holiday and payroll paid-handling configuration saved successfully.');
}

redirectWithState('error', 'Unknown timekeeping action.');
