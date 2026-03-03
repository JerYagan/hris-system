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
        redirectWithState('error', 'OB request and decision are required.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid OB decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,status,reason,person_id,person:people(user_id)&id=eq.' . $requestId . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'OB request not found.');
    }

    $reasonRaw = trim((string)($requestRow['reason'] ?? ''));
    if (preg_match('/^\[OB\]\s*/i', $reasonRaw) !== 1) {
        redirectWithState('error', 'Selected request is not tagged as an Official Business request.');
    }

    $oldStatus = strtolower((string)($requestRow['status'] ?? 'pending'));
    if ($isFinalDecision($oldStatus)) {
        redirectWithState('error', 'This OB request is locked after final decision.');
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
        redirectWithState('error', 'Failed to update OB request.');
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
                'title' => 'Official Business Request Updated',
                'body' => 'Your Official Business request was ' . $decision . '.',
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
        $notes
    );

    redirectWithState('success', 'Official Business request updated successfully.');
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
