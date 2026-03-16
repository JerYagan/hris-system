<?php

$todayDate = date('Y-m-d');

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

$buildEmployeeName = static function (array $row): string {
    $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['surname'] ?? ''));
    return $name !== '' ? $name : 'Unknown Employee';
};

$appendError = static function (?string $currentError, string $label, array $response): ?string {
    if (isSuccessful($response)) {
        return $currentError;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    return $currentError ? ($currentError . ' ' . $message) : $message;
};

$timekeepingQueryLimits = [
    'attendance_today' => 500,
    'attendance_history' => 300,
    'requests' => 250,
    'holidays' => 200,
    'recommendations' => 250,
    'role_assignments' => 5000,
    'employees' => 2500,
    'leave_types' => 200,
];

$attendanceTodayResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out,hours_worked,late_minutes,attendance_status,person:people(first_name,surname)'
    . '&attendance_date=eq.' . rawurlencode($todayDate)
    . '&order=attendance_date.desc,time_in.asc&limit=' . (int)$timekeepingQueryLimits['attendance_today'],
    $headers
);

$attendanceHistoryResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out,attendance_status,person:people(first_name,surname)'
    . '&order=attendance_date.desc,created_at.desc&limit=' . (int)$timekeepingQueryLimits['attendance_history'],
    $headers
);

$adjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/time_adjustment_requests?select=id,status,reason,reviewed_at,requested_time_in,requested_time_out,created_at,attendance_log_id,person:people(first_name,surname,user_id),attendance:attendance_logs(attendance_date,time_in,time_out)'
    . '&order=created_at.desc&limit=' . (int)$timekeepingQueryLimits['requests'],
    $headers
);

$leaveRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,date_from,date_to,days_count,status,reason,review_notes,reviewed_at,created_at,person:people(first_name,surname,user_id),leave_type:leave_types(leave_name)'
    . '&order=created_at.desc&limit=' . (int)$timekeepingQueryLimits['requests'],
    $headers
);

$ctoRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=id,overtime_date,start_time,end_time,hours_requested,reason,status,created_at,person:people(first_name,surname,user_id)'
    . '&order=created_at.desc&limit=' . (int)$timekeepingQueryLimits['requests'],
    $headers
);

$holidaysResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/holidays?select=id,holiday_date,holiday_name,holiday_type,office_id,created_at'
    . '&order=holiday_date.desc&limit=' . (int)$timekeepingQueryLimits['holidays'],
    $headers
);

$holidayPolicyResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.holiday_payroll_policy')
    . '&limit=1',
    $headers
);

$staffRecommendationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,actor_user_id,action_name,entity_name,entity_id,new_data,created_at,actor:user_accounts(email)'
    . '&module_name=eq.timekeeping'
    . '&action_name=in.(recommend_leave_request,recommend_overtime_request,recommend_ob_request,recommend_time_adjustment)'
    . '&order=created_at.desc&limit=' . (int)$timekeepingQueryLimits['recommendations'],
    $headers
);

$employeeRoleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)'
    . '&role.role_key=eq.employee'
    . '&expires_at=is.null'
    . '&limit=' . (int)$timekeepingQueryLimits['role_assignments'],
    $headers
);

$employeeOptionsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,user_id,agency_employee_no'
    . '&user_id=not.is.null'
    . '&order=surname.asc,first_name.asc&limit=' . (int)$timekeepingQueryLimits['employees'],
    $headers
);

$leaveTypeOptionsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_types?select=id,leave_name,leave_code,is_active'
    . '&is_active=eq.true'
    . '&order=leave_name.asc&limit=' . (int)$timekeepingQueryLimits['leave_types'],
    $headers
);

$dataLoadError = null;
$dataLoadError = $appendError($dataLoadError, 'Attendance (today)', $attendanceTodayResponse);
$dataLoadError = $appendError($dataLoadError, 'Attendance history', $attendanceHistoryResponse);
$dataLoadError = $appendError($dataLoadError, 'Adjustment', $adjustmentsResponse);
$dataLoadError = $appendError($dataLoadError, 'Leave', $leaveRequestsResponse);
$dataLoadError = $appendError($dataLoadError, 'CTO', $ctoRequestsResponse);
$dataLoadError = $appendError($dataLoadError, 'Holidays', $holidaysResponse);
$dataLoadError = $appendError($dataLoadError, 'Holiday payroll policy', $holidayPolicyResponse);
$dataLoadError = $appendError($dataLoadError, 'Staff recommendations', $staffRecommendationsResponse);
$dataLoadError = $appendError($dataLoadError, 'Employee role assignments', $employeeRoleAssignmentsResponse);
$dataLoadError = $appendError($dataLoadError, 'Employees', $employeeOptionsResponse);
$dataLoadError = $appendError($dataLoadError, 'Leave types', $leaveTypeOptionsResponse);

$attendanceLogs = [];
$adjustmentRequests = isSuccessful($adjustmentsResponse) ? (array)$adjustmentsResponse['data'] : [];
$leaveRequests = isSuccessful($leaveRequestsResponse) ? (array)$leaveRequestsResponse['data'] : [];
$ctoRequests = [];
$obRequests = [];
$leaveCtoRequests = [];
$holidayRows = isSuccessful($holidaysResponse) ? (array)$holidaysResponse['data'] : [];
$historyEntries = [];
$staffRecommendationRows = [];
$employeeOptions = [];
$leaveTypeOptions = [];
$approvedTravelByPersonDate = [];
$specialRequestCreateMetaById = [];
$specialRequestStatusOverrideById = [];

$employeeRoleUserIds = [];
if (isSuccessful($employeeRoleAssignmentsResponse)) {
    foreach ((array)$employeeRoleAssignmentsResponse['data'] as $assignmentRaw) {
        $assignment = (array)$assignmentRaw;
        $userId = trim((string)($assignment['user_id'] ?? ''));
        if ($userId !== '') {
            $employeeRoleUserIds[$userId] = true;
        }
    }
}

if (isSuccessful($employeeOptionsResponse)) {
    foreach ((array)$employeeOptionsResponse['data'] as $personRaw) {
        $person = (array)$personRaw;
        $personId = trim((string)($person['id'] ?? ''));
        $userId = trim((string)($person['user_id'] ?? ''));
        if ($personId === '') {
            continue;
        }
        if ($userId === '' || !isset($employeeRoleUserIds[$userId])) {
            continue;
        }

        $fullName = trim((string)($person['surname'] ?? '') . ', ' . (string)($person['first_name'] ?? ''));
        if ($fullName === '' || $fullName === ',') {
            $fullName = 'Unknown Employee';
        }

        $employeeCode = trim((string)($person['agency_employee_no'] ?? ''));
        $displayLabel = $employeeCode !== '' ? ($employeeCode . ' · ' . $fullName) : $fullName;

        $employeeOptions[] = [
            'id' => $personId,
            'label' => $displayLabel,
            'name' => $fullName,
            'employee_code' => $employeeCode,
            'user_id' => $userId,
        ];
    }
}

if (isSuccessful($leaveTypeOptionsResponse)) {
    foreach ((array)$leaveTypeOptionsResponse['data'] as $typeRaw) {
        $type = (array)$typeRaw;
        $typeId = trim((string)($type['id'] ?? ''));
        if ($typeId === '') {
            continue;
        }

        $leaveCode = strtolower(trim((string)($type['leave_code'] ?? '')));
        if (!in_array($leaveCode, ['sl', 'vl', 'cto'], true)) {
            continue;
        }

        $leaveName = (string)($type['leave_name'] ?? 'Leave');
        if ($leaveCode === 'cto') {
            $leaveName = 'Others';
        }

        $leaveTypeOptions[] = [
            'id' => $typeId,
            'leave_name' => $leaveName,
            'leave_code' => $leaveCode,
        ];
    }
}

$approvedTravelResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=person_id,overtime_date,reason,status'
    . '&status=eq.approved'
    . '&order=overtime_date.desc&limit=' . (int)$timekeepingQueryLimits['requests'],
    $headers
);

if (isSuccessful($approvedTravelResponse)) {
    foreach ((array)($approvedTravelResponse['data'] ?? []) as $travelRaw) {
        $travelRow = (array)$travelRaw;
        $personId = trim((string)($travelRow['person_id'] ?? ''));
        $travelDate = trim((string)($travelRow['overtime_date'] ?? ''));
        if ($personId === '' || $travelDate === '') {
            continue;
        }

        $parsedTravel = timekeepingParseTaggedReason((string)($travelRow['reason'] ?? ''));
        if (($parsedTravel['category'] ?? '') !== 'travel') {
            continue;
        }

        $approvedTravelByPersonDate[$personId . '|' . $travelDate] = (string)($parsedTravel['label'] ?? 'Approved Travel');
    }
}

$specialRequestIds = [];
if (isSuccessful($ctoRequestsResponse)) {
    foreach ((array)$ctoRequestsResponse['data'] as $requestRaw) {
        $request = (array)$requestRaw;
        $parsedRequest = timekeepingParseTaggedReason((string)($request['reason'] ?? ''));
        if (($parsedRequest['is_special'] ?? false) !== true) {
            continue;
        }

        $requestId = trim((string)($request['id'] ?? ''));
        if ($requestId !== '') {
            $specialRequestIds[] = $requestId;
        }
    }
}

if (!empty($specialRequestIds)) {
    $specialRequestLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
        . '&module_name=eq.timekeeping'
        . '&entity_name=eq.overtime_requests'
        . '&entity_id=in.' . rawurlencode('(' . implode(',', array_values(array_unique($specialRequestIds))) . ')')
        . '&action_name=in.' . rawurlencode('(create_official_business_request,create_cos_schedule_request,create_travel_order_request,create_travel_abroad_request,review_ob,review_ob_revision)')
        . '&order=created_at.desc&limit=' . (int)$timekeepingQueryLimits['recommendations'],
        $headers
    );

    if (isSuccessful($specialRequestLogsResponse)) {
        foreach ((array)($specialRequestLogsResponse['data'] ?? []) as $logRaw) {
            $log = (array)$logRaw;
            $entityId = trim((string)($log['entity_id'] ?? ''));
            if ($entityId === '') {
                continue;
            }

            $actionName = strtolower((string)($log['action_name'] ?? ''));
            $newData = is_array($log['new_data'] ?? null) ? (array)$log['new_data'] : [];
            if (str_starts_with($actionName, 'create_') && !isset($specialRequestCreateMetaById[$entityId])) {
                $specialRequestCreateMetaById[$entityId] = $newData;
            }

            $statusTo = strtolower((string)($newData['status_to'] ?? $newData['status'] ?? ''));
            if (($actionName === 'review_ob' || $actionName === 'review_ob_revision') && $statusTo === 'needs_revision') {
                $specialRequestStatusOverrideById[$entityId] = 'needs_revision';
            }
        }
    }
}

$attendanceSummaryToday = [
    'present' => 0,
    'late' => 0,
    'absent' => 0,
    'total' => 0,
    'date_label' => date('M d, Y', strtotime($todayDate)),
];

if (isSuccessful($attendanceTodayResponse)) {
    foreach ((array)$attendanceTodayResponse['data'] as $attendanceRowRaw) {
        $attendanceRow = (array)$attendanceRowRaw;
        $employeeName = $buildEmployeeName((array)($attendanceRow['person'] ?? []));
        $personId = trim((string)($attendanceRow['person_id'] ?? ''));
        $rawStatus = strtolower((string)($attendanceRow['attendance_status'] ?? 'present'));
        $displayStatus = $rawStatus;
        if ($isLateByPolicy((string)($attendanceRow['time_in'] ?? '')) && in_array($rawStatus, ['present', 'late'], true)) {
            $displayStatus = 'late';
        }
        $attendanceDate = (string)($attendanceRow['attendance_date'] ?? '');
        if ($personId !== '' && isset($approvedTravelByPersonDate[$personId . '|' . $attendanceDate]) && trim((string)($attendanceRow['time_in'] ?? '')) === '' && trim((string)($attendanceRow['time_out'] ?? '')) === '') {
            $displayStatus = 'travel';
        }

        if ($displayStatus === 'present') {
            $attendanceSummaryToday['present']++;
        } elseif ($displayStatus === 'late') {
            $attendanceSummaryToday['late']++;
        } elseif ($displayStatus === 'absent') {
            $attendanceSummaryToday['absent']++;
        }

        $attendanceSummaryToday['total']++;

        $attendanceLogs[] = [
            'id' => (string)($attendanceRow['id'] ?? ''),
            'employee_name' => $employeeName,
            'attendance_date' => $attendanceDate,
            'time_in' => (string)($attendanceRow['time_in'] ?? ''),
            'time_out' => (string)($attendanceRow['time_out'] ?? ''),
            'hours_worked' => (float)($attendanceRow['hours_worked'] ?? 0),
            'late_minutes' => (int)($attendanceRow['late_minutes'] ?? 0),
            'attendance_status' => $rawStatus,
            'display_status' => $displayStatus,
        ];
    }
}

if (isSuccessful($attendanceHistoryResponse)) {
    foreach ((array)$attendanceHistoryResponse['data'] as $attendanceRowRaw) {
        $attendanceRow = (array)$attendanceRowRaw;
        $employeeName = $buildEmployeeName((array)($attendanceRow['person'] ?? []));
        $personId = trim((string)($attendanceRow['person_id'] ?? ''));
        $rawStatus = strtolower((string)($attendanceRow['attendance_status'] ?? 'present'));
        $displayStatus = $rawStatus;
        if ($isLateByPolicy((string)($attendanceRow['time_in'] ?? '')) && in_array($rawStatus, ['present', 'late'], true)) {
            $displayStatus = 'late';
        }
        $attendanceDate = (string)($attendanceRow['attendance_date'] ?? '');
        if ($personId !== '' && isset($approvedTravelByPersonDate[$personId . '|' . $attendanceDate]) && trim((string)($attendanceRow['time_in'] ?? '')) === '') {
            $displayStatus = 'travel';
        }

        $historyEntries[] = [
            'sort_ts' => strtotime($attendanceDate . ' 00:00:00') ?: 0,
            'employee_name' => $employeeName,
            'entry_type' => 'Attendance',
            'entry_date' => $attendanceDate,
            'summary' => 'Status: ' . ucfirst(str_replace('_', ' ', $displayStatus)),
            'status' => ucfirst(str_replace('_', ' ', $displayStatus)),
        ];
    }
}

if (isSuccessful($ctoRequestsResponse)) {
    foreach ((array)$ctoRequestsResponse['data'] as $requestRaw) {
        $request = (array)$requestRaw;
        $employeeName = $buildEmployeeName((array)($request['person'] ?? []));
        $reasonRaw = trim((string)($request['reason'] ?? ''));
        $parsedRequest = timekeepingParseTaggedReason($reasonRaw);
        $isSpecialRequest = (bool)($parsedRequest['is_special'] ?? false);
        $cleanReason = (string)($parsedRequest['clean_reason'] ?? $reasonRaw);
        if ($cleanReason === '') {
            $cleanReason = '-';
        }
        $requestId = (string)($request['id'] ?? '');
        $createMeta = (array)($specialRequestCreateMetaById[$requestId] ?? []);
        $attachmentMeta = is_array($createMeta['attachment'] ?? null) ? (array)$createMeta['attachment'] : [];
        $destination = cleanText($createMeta['destination'] ?? null);
        $referenceNumber = cleanText($createMeta['reference_number'] ?? null);
        $attachmentPath = cleanText($attachmentMeta['relative_path'] ?? null);
        $attachmentName = cleanText($attachmentMeta['original_name'] ?? null);
        $statusRaw = strtolower((string)($request['status'] ?? 'pending'));
        if (isset($specialRequestStatusOverrideById[$requestId])) {
            $statusRaw = (string)$specialRequestStatusOverrideById[$requestId];
        }
        $requestRow = [
            'id' => $requestId,
            'employee_name' => $employeeName,
            'overtime_date' => (string)($request['overtime_date'] ?? ''),
            'start_time' => (string)($request['start_time'] ?? ''),
            'end_time' => (string)($request['end_time'] ?? ''),
            'hours_requested' => (float)($request['hours_requested'] ?? 0),
            'reason' => $cleanReason,
            'status' => $statusRaw,
            'created_at' => (string)($request['created_at'] ?? ''),
            'user_id' => (string)($request['person']['user_id'] ?? ''),
            'request_type' => (string)($parsedRequest['request_type'] ?? 'legacy_cto'),
            'request_label' => (string)($parsedRequest['label'] ?? 'CTO (Legacy)'),
            'detail_summary' => trim(implode(' | ', array_filter([
                $destination !== null ? 'Destination: ' . $destination : '',
                $referenceNumber !== null ? 'Ref: ' . $referenceNumber : '',
                $attachmentName !== null ? 'Attachment: ' . $attachmentName : '',
            ]))),
            'attachment_url' => $attachmentPath !== null ? systemAppPath($attachmentPath) : null,
            'attachment_name' => $attachmentName,
        ];

        if ($isSpecialRequest) {
            $obRequests[] = $requestRow;
            $historyEntries[] = [
                'sort_ts' => strtotime((string)($requestRow['created_at'] ?? '')) ?: 0,
                'employee_name' => $employeeName,
                'entry_type' => (string)($requestRow['request_label'] ?? 'Special Request'),
                'entry_date' => (string)$requestRow['overtime_date'],
                'summary' => $cleanReason,
                'status' => ucfirst(str_replace('_', ' ', (string)$requestRow['status'])),
            ];
            continue;
        }

        $ctoRequests[] = $requestRow;
        $historyEntries[] = [
            'sort_ts' => strtotime((string)($requestRow['created_at'] ?? '')) ?: 0,
            'employee_name' => $employeeName,
            'entry_type' => 'CTO',
            'entry_date' => (string)$requestRow['overtime_date'],
            'summary' => $cleanReason,
            'status' => ucfirst(str_replace('_', ' ', (string)$requestRow['status'])),
        ];
    }
}

foreach ($leaveRequests as $leaveRowRaw) {
    $leaveRow = (array)$leaveRowRaw;
    $employeeName = $buildEmployeeName((array)($leaveRow['person'] ?? []));
    $historyEntries[] = [
        'sort_ts' => strtotime((string)($leaveRow['created_at'] ?? '')) ?: 0,
        'employee_name' => $employeeName,
        'entry_type' => 'Leave',
        'entry_date' => (string)($leaveRow['date_from'] ?? ''),
        'summary' => (string)($leaveRow['reason'] ?? 'Leave request submitted'),
        'status' => ucfirst(str_replace('_', ' ', strtolower((string)($leaveRow['status'] ?? 'pending')))),
    ];
}

foreach ($leaveRequests as $leaveRaw) {
    $leave = (array)$leaveRaw;
    $statusRaw = strtolower((string)($leave['status'] ?? 'pending'));
    $leaveTypeName = trim((string)($leave['leave_type']['leave_name'] ?? 'Unassigned'));
    $isCtoLeave = stripos($leaveTypeName, 'cto') !== false;
    $employeeName = $buildEmployeeName((array)($leave['person'] ?? []));

    $leaveCtoRequests[] = [
        'id' => (string)($leave['id'] ?? ''),
        'request_source' => 'leave_requests',
        'request_type_label' => $isCtoLeave ? 'CTO' : 'Leave',
        'employee_name' => $employeeName,
        'leave_type' => $leaveTypeName,
        'date_label' => (string)($leave['date_from'] ?? '') . ' - ' . (string)($leave['date_to'] ?? ''),
        'window' => '',
        'hours_requested' => null,
        'reason' => (string)($leave['reason'] ?? '-'),
        'status_raw' => $statusRaw,
        'status_label' => ucfirst(str_replace('_', ' ', $statusRaw)),
    ];
}

foreach ($ctoRequests as $request) {
    $statusRaw = strtolower((string)($request['status'] ?? 'pending'));

    $leaveCtoRequests[] = [
        'id' => (string)($request['id'] ?? ''),
        'request_source' => 'overtime_requests',
        'request_type_label' => 'CTO (Legacy)',
        'employee_name' => (string)($request['employee_name'] ?? 'Unknown Employee'),
        'leave_type' => 'CTO',
        'date_label' => (string)($request['overtime_date'] ?? ''),
        'window' => trim((string)($request['start_time'] ?? '')) . ' - ' . trim((string)($request['end_time'] ?? '')),
        'hours_requested' => (float)($request['hours_requested'] ?? 0),
        'reason' => (string)($request['reason'] ?? '-'),
        'status_raw' => $statusRaw,
        'status_label' => ucfirst(str_replace('_', ' ', $statusRaw)),
    ];
}

usort($leaveCtoRequests, static function (array $left, array $right): int {
    $leftTs = strtotime((string)($left['date_label'] ?? '')) ?: 0;
    $rightTs = strtotime((string)($right['date_label'] ?? '')) ?: 0;
    return $rightTs <=> $leftTs;
});

foreach ($adjustmentRequests as $adjustmentRowRaw) {
    $adjustmentRow = (array)$adjustmentRowRaw;
    $employeeName = $buildEmployeeName((array)($adjustmentRow['person'] ?? []));
    $historyEntries[] = [
        'sort_ts' => strtotime((string)($adjustmentRow['created_at'] ?? '')) ?: 0,
        'employee_name' => $employeeName,
        'entry_type' => 'Time Adjustment',
        'entry_date' => (string)($adjustmentRow['attendance']['attendance_date'] ?? ''),
        'summary' => (string)($adjustmentRow['reason'] ?? 'Time adjustment submitted'),
        'status' => ucfirst(str_replace('_', ' ', strtolower((string)($adjustmentRow['status'] ?? 'pending')))),
    ];
}

usort($historyEntries, static function (array $left, array $right): int {
    return (int)($right['sort_ts'] ?? 0) <=> (int)($left['sort_ts'] ?? 0);
});

$leaveLookup = [];
foreach ($leaveRequests as $leaveRowRaw) {
    $leaveRow = (array)$leaveRowRaw;
    $id = (string)($leaveRow['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $employeeName = $buildEmployeeName((array)($leaveRow['person'] ?? []));
    $leaveType = (string)($leaveRow['leave_type']['leave_name'] ?? 'Unassigned');
    $dateRange = (string)($leaveRow['date_from'] ?? '') . ' - ' . (string)($leaveRow['date_to'] ?? '');
    $leaveLookup[$id] = [
        'id' => $id,
        'employee_name' => $employeeName,
        'status' => strtolower((string)($leaveRow['status'] ?? 'pending')),
        'status_label' => ucfirst(str_replace('_', ' ', strtolower((string)($leaveRow['status'] ?? 'pending')))),
        'leave_type' => $leaveType,
        'date_range' => $dateRange,
        'reason' => (string)($leaveRow['reason'] ?? '-'),
    ];
}

$adjustmentLookup = [];
foreach ($adjustmentRequests as $requestRaw) {
    $request = (array)$requestRaw;
    $id = (string)($request['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $employeeName = $buildEmployeeName((array)($request['person'] ?? []));
    $requestedWindow = trim((string)($request['requested_time_in'] ?? '')) . ' - ' . trim((string)($request['requested_time_out'] ?? ''));
    $adjustmentLookup[$id] = [
        'id' => $id,
        'employee_name' => $employeeName,
        'status' => strtolower((string)($request['status'] ?? 'pending')),
        'status_label' => ucfirst(str_replace('_', ' ', strtolower((string)($request['status'] ?? 'pending')))),
        'requested_window' => $requestedWindow,
        'reason' => (string)($request['reason'] ?? '-'),
    ];
}

$ctoLookup = [];
foreach ($ctoRequests as $request) {
    $id = (string)($request['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $ctoLookup[$id] = [
        'id' => $id,
        'employee_name' => (string)($request['employee_name'] ?? 'Unknown Employee'),
        'status' => strtolower((string)($request['status'] ?? 'pending')),
        'status_label' => ucfirst(str_replace('_', ' ', strtolower((string)($request['status'] ?? 'pending')))),
        'window' => trim((string)($request['start_time'] ?? '')) . ' - ' . trim((string)($request['end_time'] ?? '')),
    ];
}

$obLookup = [];
foreach ($obRequests as $request) {
    $id = (string)($request['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $obLookup[$id] = [
        'id' => $id,
        'employee_name' => (string)($request['employee_name'] ?? 'Unknown Employee'),
        'status' => strtolower((string)($request['status'] ?? 'pending')),
        'status_label' => ucfirst(str_replace('_', ' ', strtolower((string)($request['status'] ?? 'pending')))),
        'window' => trim((string)($request['start_time'] ?? '')) . ' - ' . trim((string)($request['end_time'] ?? '')),
        'request_label' => (string)($request['request_label'] ?? 'Special Request'),
    ];
}

if (isSuccessful($staffRecommendationsResponse)) {
    foreach ((array)$staffRecommendationsResponse['data'] as $logRaw) {
        $log = (array)$logRaw;
        $entityName = strtolower((string)($log['entity_name'] ?? ''));
        $entityId = (string)($log['entity_id'] ?? '');
        if ($entityId === '') {
            continue;
        }

        $newData = (array)($log['new_data'] ?? []);
        $recommendedStatus = strtolower((string)($newData['recommended_status'] ?? 'pending'));
        $notes = trim((string)($newData['notes'] ?? ''));

        $requestType = '';
        $employeeName = 'Unknown Employee';
        $currentStatus = 'pending';
        $currentStatusLabel = 'Pending';
        $actionType = '';
        $window = '';
        $leaveType = '';
        $dateRange = '';
        $reason = '';

        if ($entityName === 'leave_requests' && isset($leaveLookup[$entityId])) {
            $item = $leaveLookup[$entityId];
            $requestType = 'Leave';
            $employeeName = (string)$item['employee_name'];
            $currentStatus = (string)$item['status'];
            $currentStatusLabel = (string)$item['status_label'];
            $actionType = 'leave';
            $leaveType = (string)$item['leave_type'];
            $dateRange = (string)$item['date_range'];
            $reason = (string)$item['reason'];
        } elseif ($entityName === 'time_adjustment_requests' && isset($adjustmentLookup[$entityId])) {
            $item = $adjustmentLookup[$entityId];
            $requestType = 'Time Adjustment';
            $employeeName = (string)$item['employee_name'];
            $currentStatus = (string)$item['status'];
            $currentStatusLabel = (string)$item['status_label'];
            $actionType = 'adjustment';
            $window = (string)$item['requested_window'];
        } elseif ($entityName === 'overtime_requests') {
            if (isset($obLookup[$entityId])) {
                $item = $obLookup[$entityId];
                $requestType = (string)($item['request_label'] ?? 'Special Request');
                $employeeName = (string)$item['employee_name'];
                $currentStatus = (string)$item['status'];
                $currentStatusLabel = (string)$item['status_label'];
                $actionType = 'ob';
                $window = (string)$item['window'];
            } elseif (isset($ctoLookup[$entityId])) {
                $item = $ctoLookup[$entityId];
                $requestType = 'CTO';
                $employeeName = (string)$item['employee_name'];
                $currentStatus = (string)$item['status'];
                $currentStatusLabel = (string)$item['status_label'];
                $actionType = 'cto';
                $window = (string)$item['window'];
            }
        }

        if ($actionType === '') {
            continue;
        }

        $isFinal = in_array($currentStatus, ['approved', 'rejected', 'cancelled'], true);
        $staffRecommendationRows[] = [
            'log_id' => (string)($log['id'] ?? ''),
            'submitted_at' => (string)($log['created_at'] ?? ''),
            'staff_actor' => (string)($log['actor']['email'] ?? 'Staff User'),
            'request_type' => $requestType,
            'entity_id' => $entityId,
            'employee_name' => $employeeName,
            'recommended_status' => $recommendedStatus,
            'recommended_status_label' => ucfirst(str_replace('_', ' ', $recommendedStatus !== '' ? $recommendedStatus : 'pending')),
            'notes' => $notes,
            'current_status' => $currentStatus,
            'current_status_label' => $currentStatusLabel,
            'action_type' => $actionType,
            'window' => $window,
            'leave_type' => $leaveType,
            'date_range' => $dateRange,
            'reason' => $reason,
            'is_final' => $isFinal,
        ];
    }
}

$holidayPayrollPolicy = [
    'paid_handling' => 'policy_based',
    'apply_to_regular' => true,
    'apply_to_special' => false,
    'apply_to_local' => false,
    'include_suspension' => true,
];

if (isSuccessful($holidayPolicyResponse)) {
    $policyPayload = (array)($holidayPolicyResponse['data'][0]['setting_value'] ?? []);
    if (!empty($policyPayload)) {
        $holidayPayrollPolicy['paid_handling'] = in_array((string)($policyPayload['paid_handling'] ?? 'policy_based'), ['policy_based', 'always_paid', 'always_unpaid'], true)
            ? (string)$policyPayload['paid_handling']
            : 'policy_based';
        $holidayPayrollPolicy['apply_to_regular'] = (bool)($policyPayload['apply_to_regular'] ?? true);
        $holidayPayrollPolicy['apply_to_special'] = (bool)($policyPayload['apply_to_special'] ?? false);
        $holidayPayrollPolicy['apply_to_local'] = (bool)($policyPayload['apply_to_local'] ?? false);
        $holidayPayrollPolicy['include_suspension'] = (bool)($policyPayload['include_suspension'] ?? true);
    }
}
