<?php

require_once __DIR__ . '/../../../shared/lib/rfid-attendance.php';

$attendanceRows = [];
$leaveRequestRows = [];
$officialBusinessRequestRows = [];
$adjustmentRequestRows = [];
$rfidAssignedCardRows = [];
$rfidRecentEventRows = [];
$leaveTypeNameById = [];
$timekeepingMetrics = [
    'attendance_logs' => 0,
    'pending_leave' => 0,
    'pending_cto' => 0,
    'pending_official_business' => 0,
    'pending_adjustments' => 0,
    'active_rfid_cards' => 0,
    'rfid_event_failures' => 0,
];
$dataLoadError = null;

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    } else {
        $transportError = trim((string)($response['error'] ?? ''));
        if ($transportError !== '') {
            $message .= ' ' . $transportError;
        }
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};


$scopeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=person_id,employment_status,employment_type,person:people!employment_records_person_id_fkey(first_name,surname,user_id,agency_employee_no),office:offices(office_name),position:job_positions(position_title,employment_classification)'
    . '&is_current=eq.true'
    . '&limit=5000',
    $headers
);
$appendDataError('Employment scope', $scopeResponse);

$employmentScopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];
$scopedPersonMap = [];
$rfidEmployeeLookup = [];
$cosEmployeeRows = [];

foreach ($employmentScopeRows as $scopeRow) {
    $personId = cleanText($scopeRow['person_id'] ?? null) ?? '';
    $userId = cleanText($scopeRow['person']['user_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $firstName = cleanText($scopeRow['person']['first_name'] ?? null) ?? '';
    $surname = cleanText($scopeRow['person']['surname'] ?? null) ?? '';
    $employeeName = trim($firstName . ' ' . $surname);
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $employmentType = cleanText($scopeRow['employment_type'] ?? null) ?? '';
    $positionClassification = cleanText($scopeRow['position']['employment_classification'] ?? null) ?? '';
    $effectiveEmploymentMarker = $employmentType !== '' ? $employmentType : $positionClassification;

    $scopedPersonMap[$personId] = [
        'employee_name' => $employeeName,
        'office_name' => cleanText($scopeRow['office']['office_name'] ?? null) ?? 'Unassigned Division',
        'position_title' => cleanText($scopeRow['position']['position_title'] ?? null) ?? 'Unassigned Position',
        'employee_code' => strtoupper(trim((string)(cleanText($scopeRow['person']['agency_employee_no'] ?? null) ?? ''))),
        'employment_status' => timekeepingIsCosEmploymentStatus(
            cleanText($scopeRow['employment_status'] ?? null) ?? '',
            $effectiveEmploymentMarker
        )
            ? ($effectiveEmploymentMarker !== '' ? $effectiveEmploymentMarker : (cleanText($scopeRow['employment_status'] ?? null) ?? ''))
            : (cleanText($scopeRow['employment_status'] ?? null) ?? ''),
        'user_id' => $userId,
    ];

    $employeeCode = strtoupper(trim((string)(cleanText($scopeRow['person']['agency_employee_no'] ?? null) ?? '')));
    if ($employeeCode !== '') {
        $rfidEmployeeLookup[$employeeCode] = [
            'employee_name' => $employeeName,
            'office_name' => $scopedPersonMap[$personId]['office_name'],
            'position_title' => $scopedPersonMap[$personId]['position_title'],
            'person_id' => $personId,
        ];
    }
}

foreach ($scopedPersonMap as $personId => $employee) {
    if (!timekeepingIsCosEmploymentStatus((string)($employee['employment_status'] ?? ''))) {
        continue;
    }

    $cosEmployeeRows[$personId] = [
        'employee_name' => (string)($employee['employee_name'] ?? 'Unknown Employee'),
        'office_name' => (string)($employee['office_name'] ?? 'Unassigned Division'),
        'position_title' => (string)($employee['position_title'] ?? 'Unassigned Position'),
        'employment_status' => (string)($employee['employment_status'] ?? 'COS'),
        'latest_cos_status' => '-',
        'latest_cos_requested_label' => '-',
        'latest_cos_request_id' => '',
        'latest_cos_request_label' => 'COS Schedule Proposal',
        'latest_cos_window' => '-',
        'latest_cos_reason' => '-',
        'latest_cos_status_raw' => 'pending',
    ];
}

$personIds = array_keys($scopedPersonMap);
if (empty($personIds)) {
    return;
}

$personFilter = !empty($personIds)
    ? '&person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')')
    : '';

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out,attendance_status,source,created_at'
    . $personFilter
    . '&order=attendance_date.desc&limit=500',
    $headers
);
$appendDataError('Attendance logs', $attendanceResponse);
$attendanceLogs = isSuccessful($attendanceResponse) ? (array)($attendanceResponse['data'] ?? []) : [];

$leaveResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,person_id,leave_type_id,date_from,date_to,days_count,reason,status,created_at'
    . $personFilter
    . '&order=created_at.desc&limit=500',
    $headers
);
$appendDataError('Leave requests', $leaveResponse);
$leaveRows = isSuccessful($leaveResponse) ? (array)($leaveResponse['data'] ?? []) : [];

$overtimeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=id,person_id,overtime_date,start_time,end_time,hours_requested,reason,status,created_at'
    . $personFilter
    . '&order=created_at.desc&limit=500',
    $headers
);
$appendDataError('Overtime requests', $overtimeResponse);
$overtimeRows = isSuccessful($overtimeResponse) ? (array)($overtimeResponse['data'] ?? []) : [];

$adjustmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/time_adjustment_requests?select=id,person_id,attendance_log_id,requested_time_in,requested_time_out,reason,status,created_at'
    . $personFilter
    . '&order=created_at.desc&limit=500',
    $headers
);
$appendDataError('Time adjustment requests', $adjustmentResponse);
$adjustmentRows = isSuccessful($adjustmentResponse) ? (array)($adjustmentResponse['data'] ?? []) : [];

$leaveTypesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_types?select=id,leave_name&is_active=eq.true&limit=200',
    $headers
);
$appendDataError('Leave types', $leaveTypesResponse);
if (isSuccessful($leaveTypesResponse)) {
    foreach ((array)($leaveTypesResponse['data'] ?? []) as $leaveTypeRaw) {
        $leaveType = (array)$leaveTypeRaw;
        $leaveTypeId = cleanText($leaveType['id'] ?? null) ?? '';
        if ($leaveTypeId === '') {
            continue;
        }

        $leaveTypeNameById[$leaveTypeId] = (string)($leaveType['leave_name'] ?? 'Unassigned');
    }
}

$attendanceByAdjustmentLogId = [];
$adjustmentAttendanceLogIds = [];
foreach ($adjustmentRows as $adjustmentRaw) {
    $adjustmentRow = (array)$adjustmentRaw;
    $attendanceLogId = cleanText($adjustmentRow['attendance_log_id'] ?? null) ?? '';
    if ($attendanceLogId !== '') {
        $adjustmentAttendanceLogIds[$attendanceLogId] = true;
    }
}

if (!empty($adjustmentAttendanceLogIds)) {
    $attendanceAdjustmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/attendance_logs?select=id,attendance_date,time_in,time_out'
        . '&id=in.' . rawurlencode('(' . implode(',', array_keys($adjustmentAttendanceLogIds)) . ')')
        . '&limit=' . count($adjustmentAttendanceLogIds),
        $headers
    );
    $appendDataError('Adjustment attendance', $attendanceAdjustmentResponse);

    if (isSuccessful($attendanceAdjustmentResponse)) {
        foreach ((array)($attendanceAdjustmentResponse['data'] ?? []) as $attendanceRaw) {
            $attendanceRow = (array)$attendanceRaw;
            $attendanceId = cleanText($attendanceRow['id'] ?? null) ?? '';
            if ($attendanceId === '') {
                continue;
            }

            $attendanceByAdjustmentLogId[$attendanceId] = $attendanceRow;
        }
    }
}

$rfidCardsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/rfid_cards?select=id,person_id,card_uid,card_label,status,issued_at,deactivated_at'
    . '&order=issued_at.desc&limit=5000',
    $headers
);
$appendDataError('RFID cards', $rfidCardsResponse);
$rfidCardRows = isSuccessful($rfidCardsResponse) ? (array)($rfidCardsResponse['data'] ?? []) : [];

$employeesDirectoryResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employees?select=id,uid,employee_id,name,person_id,created_at'
    . '&limit=5000',
    $headers
);
$appendDataError('Employees directory', $employeesDirectoryResponse);
$employeesDirectoryRows = isSuccessful($employeesDirectoryResponse) ? (array)($employeesDirectoryResponse['data'] ?? []) : [];

$rfidDevicesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/rfid_devices?select=id,device_code,device_name,status,last_seen_at'
    . '&limit=200',
    $headers
);
$appendDataError('RFID devices', $rfidDevicesResponse);
$rfidDeviceRows = isSuccessful($rfidDevicesResponse) ? (array)($rfidDevicesResponse['data'] ?? []) : [];

$rfidEventsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/rfid_scan_events?select=id,person_id,device_id,card_uid,scanned_at,request_source,result_code,result_message,attendance_log_id'
    . '&order=scanned_at.desc&limit=200',
    $headers
);
$appendDataError('RFID scan events', $rfidEventsResponse);
$rfidEventRows = isSuccessful($rfidEventsResponse) ? (array)($rfidEventsResponse['data'] ?? []) : [];

$activeRfidCardByPersonId = [];
$employeesDirectoryByPersonId = [];

foreach ($employeesDirectoryRows as $employeeDirectoryRaw) {
    $employeeDirectory = (array)$employeeDirectoryRaw;
    $personId = cleanText($employeeDirectory['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $employeeName = cleanText($employeeDirectory['name'] ?? null) ?? '';
    if ($employeeName === '') {
        $employeeName = (string)($scopedPersonMap[$personId]['employee_name'] ?? 'Unknown Employee');
    }

    $employeeCode = strtoupper(trim((string)(cleanText($employeeDirectory['employee_id'] ?? null) ?? '')));
    if ($employeeCode === '') {
        $employeeCode = (string)($scopedPersonMap[$personId]['employee_code'] ?? '-');
    }

    $employeesDirectoryByPersonId[$personId] = [
        'employee_row_id' => cleanText($employeeDirectory['id'] ?? null) ?? '',
        'employee_name' => $employeeName,
        'employee_code' => $employeeCode,
        'uid' => cleanText($employeeDirectory['uid'] ?? null) ?? '',
        'created_at' => cleanText($employeeDirectory['created_at'] ?? null) ?? '',
        'office_name' => (string)($scopedPersonMap[$personId]['office_name'] ?? 'Unassigned Division'),
    ];
}

$approvedTravelByPersonDate = [];
$approvedTravelResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=person_id,overtime_date,reason,status'
    . $personFilter
    . '&status=eq.approved'
    . '&order=overtime_date.desc&limit=500',
    $headers
);
$appendDataError('Approved travel requests', $approvedTravelResponse);
if (isSuccessful($approvedTravelResponse)) {
    foreach ((array)($approvedTravelResponse['data'] ?? []) as $travelRaw) {
        $travelRow = (array)$travelRaw;
        $personId = cleanText($travelRow['person_id'] ?? null) ?? '';
        $travelDate = cleanText($travelRow['overtime_date'] ?? null) ?? '';
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

$specialRequestCreateMetaById = [];
$specialRequestStatusOverrideById = [];
$specialRequestIds = [];
foreach ($overtimeRows as $overtimeRaw) {
    $overtimeRow = (array)$overtimeRaw;
    $parsedRequest = timekeepingParseTaggedReason((string)($overtimeRow['reason'] ?? ''));
    if (($parsedRequest['is_special'] ?? false) === true) {
        $requestId = cleanText($overtimeRow['id'] ?? null) ?? '';
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
        . '&order=created_at.desc&limit=500',
        $headers
    );
    $appendDataError('Special request activity logs', $specialRequestLogsResponse);

    if (isSuccessful($specialRequestLogsResponse)) {
        foreach ((array)($specialRequestLogsResponse['data'] ?? []) as $logRaw) {
            $log = (array)$logRaw;
            $entityId = cleanText($log['entity_id'] ?? null) ?? '';
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

$attendancePill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'present' => ['Present', 'bg-emerald-50 text-emerald-700'],
        'late' => ['Late', 'bg-amber-50 text-amber-700'],
        'absent' => ['Absent', 'bg-rose-50 text-rose-700'],
        'leave' => ['Leave', 'bg-blue-50 text-blue-700'],
        'travel' => ['Approved Travel', 'bg-indigo-50 text-indigo-700'],
        'holiday' => ['Holiday', 'bg-indigo-50 text-indigo-700'],
        'rest_day' => ['Rest Day', 'bg-slate-100 text-slate-700'],
        default => ['Unknown', 'bg-slate-50 text-slate-700'],
    };
};

$requestPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved' => ['Approved', 'bg-emerald-50 text-emerald-700'],
        'rejected' => ['Rejected', 'bg-rose-50 text-rose-700'],
        'cancelled' => ['Cancelled', 'bg-slate-100 text-slate-700'],
        'needs_revision' => ['Needs Revision', 'bg-blue-50 text-blue-700'],
        default => ['Pending', 'bg-amber-50 text-amber-700'],
    };
};

foreach ($attendanceLogs as $log) {
    $personId = cleanText($log['person_id'] ?? null) ?? '';
    if (!isset($scopedPersonMap[$personId])) {
        continue;
    }

    $employee = $scopedPersonMap[$personId];
    $statusRaw = strtolower((string)(cleanText($log['attendance_status'] ?? null) ?? 'present'));
    $attendanceDateRaw = cleanText($log['attendance_date'] ?? null) ?? '';
    $hasApprovedTravel = isset($approvedTravelByPersonDate[$personId . '|' . $attendanceDateRaw]);
    if ($hasApprovedTravel && cleanText($log['time_in'] ?? null) === null && cleanText($log['time_out'] ?? null) === null) {
        $statusRaw = 'travel';
    }
    [$statusLabel, $statusClass] = $attendancePill($statusRaw);

    $attendanceRows[] = [
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'attendance_date_raw' => $attendanceDateRaw,
        'date_label' => formatDateTimeForPhilippines($attendanceDateRaw, 'M d, Y'),
        'time_in_label' => formatDateTimeForPhilippines(cleanText($log['time_in'] ?? null), 'h:i A'),
        'time_out_label' => formatDateTimeForPhilippines(cleanText($log['time_out'] ?? null), 'h:i A'),
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'source_label' => ucfirst(str_replace('_', ' ', strtolower((string)(cleanText($log['source'] ?? null) ?? '')))),
    ];
}

$rfidDeviceMap = [];
foreach ($rfidDeviceRows as $deviceRaw) {
    $device = (array)$deviceRaw;
    $deviceId = cleanText($device['id'] ?? null) ?? '';
    if (!isValidUuid($deviceId)) {
        continue;
    }

    $rfidDeviceMap[$deviceId] = [
        'device_name' => cleanText($device['device_name'] ?? null) ?? 'Unnamed Device',
        'device_code' => cleanText($device['device_code'] ?? null) ?? 'Unknown',
    ];
}

foreach ($rfidCardRows as $cardRaw) {
    $card = (array)$cardRaw;
    $personId = cleanText($card['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($card['status'] ?? null) ?? 'inactive'));
    if ($statusRaw !== 'active') {
        continue;
    }

    $timekeepingMetrics['active_rfid_cards']++;

    if (!isset($activeRfidCardByPersonId[$personId])) {
        $activeRfidCardByPersonId[$personId] = $card;
    }
}

$employeeRowsWithAssignedCards = [];
foreach ($employeesDirectoryRows as $employeeDirectoryRaw) {
    $employeeDirectory = (array)$employeeDirectoryRaw;
    $personId = cleanText($employeeDirectory['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $employeeName = cleanText($employeeDirectory['name'] ?? null) ?? 'Unknown Employee';
    $employeeCode = strtoupper(trim((string)(cleanText($employeeDirectory['employee_id'] ?? null) ?? '')));
    $employeeRowsWithAssignedCards[] = [
        'person_id' => $personId,
        'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
        'employee_code' => $employeeCode !== '' ? $employeeCode : '-',
        'office_name' => (string)($scopedPersonMap[$personId]['office_name'] ?? 'Unassigned Division'),
    ];
}

usort($employeeRowsWithAssignedCards, static function (array $left, array $right): int {
    return strcasecmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
});

foreach ($employeeRowsWithAssignedCards as $employee) {
    $personId = (string)($employee['person_id'] ?? '');
    $card = (array)($activeRfidCardByPersonId[$personId] ?? []);
    $employeeDirectory = $employeesDirectoryByPersonId[$personId] ?? [];
    $employeeUid = rfidNormalizeCardUid((string)($employeeDirectory['uid'] ?? ''));

    if ($card === [] && $employeeUid !== '') {
        $card = [
            'id' => 'virtual:' . $employeeUid,
            'person_id' => $personId,
            'card_uid' => $employeeUid,
            'card_label' => (string)($employee['employee_name'] ?? 'Employee UID'),
            'issued_at' => (string)($employeeDirectory['created_at'] ?? ''),
            'is_virtual' => true,
        ];
    }

    $hasActiveCard = $card !== [];

    $rfidAssignedCardRows[] = [
        'id' => cleanText($card['id'] ?? null) ?? '',
        'employee_row_id' => (string)($employeeDirectory['employee_row_id'] ?? ''),
        'person_id' => $personId,
        'employee_name' => (string)($employee['employee_name'] ?? 'Unknown Employee'),
        'employee_code' => (string)($employee['employee_code'] ?? '-'),
        'office_name' => (string)($employee['office_name'] ?? 'Unassigned Division'),
        'card_uid_masked' => $hasActiveCard ? rfidMaskCardUid((string)($card['card_uid'] ?? '')) : 'Unassigned',
        'issued_at_label' => $hasActiveCard
            ? formatDateTimeForPhilippines(cleanText($card['issued_at'] ?? null), 'M d, Y h:i A')
            : '-',
        'deactivated_at_label' => '-',
        'status_label' => $hasActiveCard ? 'Active' : 'No Card Assigned',
        'status_class' => $hasActiveCard ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700',
        'status_raw' => $hasActiveCard ? 'active' : 'unassigned',
        'is_virtual' => !empty($card['is_virtual']),
        'assignment_source' => !empty($card['is_virtual']) ? 'employees_uid' : 'rfid_cards',
    ];
}

foreach ($rfidEventRows as $eventRaw) {
    $event = (array)$eventRaw;
    $personId = cleanText($event['person_id'] ?? null) ?? '';
    if ($personId !== '' && !isset($scopedPersonMap[$personId])) {
        continue;
    }

    $resultCode = strtolower((string)(cleanText($event['result_code'] ?? null) ?? 'unknown'));
    $isSuccess = in_array($resultCode, ['time_in_logged', 'time_out_logged', 'duplicate_ignored', 'attendance_processed'], true);
    if (!$isSuccess) {
        $timekeepingMetrics['rfid_event_failures']++;
    }

    $deviceId = cleanText($event['device_id'] ?? null) ?? '';
    $device = $deviceId !== '' ? ($rfidDeviceMap[$deviceId] ?? null) : null;
    $employee = $personId !== '' ? ($scopedPersonMap[$personId] ?? null) : null;
    $requestSource = strtolower((string)(cleanText($event['request_source'] ?? null) ?? 'device'));
    $requestSourceLabel = match ($requestSource) {
        'employee_simulation' => 'Employee Simulation',
        default => 'Device',
    };

    $rfidRecentEventRows[] = [
        'scanned_at_label' => formatDateTimeForPhilippines(cleanText($event['scanned_at'] ?? null), 'M d, Y h:i A'),
        'employee_name' => is_array($employee) ? (string)($employee['employee_name'] ?? 'Unknown Employee') : 'Unmapped Card',
        'employee_code' => is_array($employee) ? (string)($employee['employee_code'] ?? '-') : '-',
        'card_uid_masked' => rfidMaskCardUid((string)($event['card_uid'] ?? '')),
        'request_source_label' => $requestSourceLabel,
        'device_label' => is_array($device)
            ? trim((string)($device['device_name'] ?? 'Unnamed Device') . ' (' . (string)($device['device_code'] ?? 'Unknown') . ')')
            : 'No Device',
        'result_label' => ucfirst(str_replace('_', ' ', $resultCode)),
        'result_class' => $isSuccess ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700',
        'result_message' => cleanText($event['result_message'] ?? null) ?? '-',
        'attendance_linked' => isValidUuid((string)(cleanText($event['attendance_log_id'] ?? null) ?? '')),
    ];
}

foreach ($leaveRows as $row) {
    $requestId = cleanText($row['id'] ?? null) ?? '';
    $personId = cleanText($row['person_id'] ?? null) ?? '';
    if (!isValidUuid($requestId) || !isset($scopedPersonMap[$personId])) {
        continue;
    }

    $employee = $scopedPersonMap[$personId];
    $statusRaw = strtolower((string)(cleanText($row['status'] ?? null) ?? 'pending'));
    [$statusLabel, $statusClass] = $requestPill($statusRaw);

    $leaveTypeId = cleanText($row['leave_type_id'] ?? null) ?? '';
    $leaveType = (string)($leaveTypeNameById[$leaveTypeId] ?? 'Unassigned');
    $isCtoLeave = stripos($leaveType, 'cto') !== false;

    if ($statusRaw === 'pending') {
        if ($isCtoLeave) {
            $timekeepingMetrics['pending_cto']++;
        } else {
            $timekeepingMetrics['pending_leave']++;
        }
    }

    $dateRange = formatDateTimeForPhilippines(cleanText($row['date_from'] ?? null), 'M d, Y')
        . ' - '
        . formatDateTimeForPhilippines(cleanText($row['date_to'] ?? null), 'M d, Y');
    $daysCount = (float)($row['days_count'] ?? 0);
    $reason = cleanText($row['reason'] ?? null) ?? '-';

    $leaveRequestRows[] = [
        'id' => $requestId,
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'leave_type' => $leaveType,
        'date_range' => $dateRange,
        'days_count' => number_format($daysCount, fmod($daysCount, 1.0) === 0.0 ? 0 : 2),
        'reason' => $reason,
        'requested_label' => formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => strtolower(trim($employee['employee_name'] . ' ' . $employee['office_name'] . ' ' . $leaveType . ' ' . $reason . ' ' . $statusLabel . ' ' . $dateRange)),
    ];
}

foreach ($overtimeRows as $row) {
    $requestId = cleanText($row['id'] ?? null) ?? '';
    $personId = cleanText($row['person_id'] ?? null) ?? '';
    if (!isValidUuid($requestId) || !isset($scopedPersonMap[$personId])) {
        continue;
    }

    $employee = $scopedPersonMap[$personId];
    $parsedRequest = timekeepingParseTaggedReason((string)($row['reason'] ?? ''));
    if (($parsedRequest['is_special'] ?? false) !== true) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($specialRequestStatusOverrideById[$requestId] ?? null) ?? cleanText($row['status'] ?? null) ?? 'pending'));
    [$statusLabel, $statusClass] = $requestPill($statusRaw);

    $reasonRaw = cleanText($row['reason'] ?? null) ?? '-';
    $reason = (string)($parsedRequest['clean_reason'] ?? $reasonRaw);
    if ($reason === '') {
        $reason = '-';
    }

    if ($statusRaw === 'pending') {
        $timekeepingMetrics['pending_official_business']++;
    }

    $startTime = formatDateTimeForPhilippines(cleanText($row['start_time'] ?? null), 'h:i A');
    $endTime = formatDateTimeForPhilippines(cleanText($row['end_time'] ?? null), 'h:i A');
    $createMeta = (array)($specialRequestCreateMetaById[$requestId] ?? []);
    $attachmentMeta = is_array($createMeta['attachment'] ?? null) ? (array)$createMeta['attachment'] : [];
    $destination = cleanText($createMeta['destination'] ?? null);
    $referenceNumber = cleanText($createMeta['reference_number'] ?? null);
    $attachmentPath = cleanText($attachmentMeta['relative_path'] ?? null);
    $attachmentName = cleanText($attachmentMeta['original_name'] ?? null);
    $detailParts = [];
    if ($destination !== null) {
        $detailParts[] = 'Destination: ' . $destination;
    }
    if ($referenceNumber !== null) {
        $detailParts[] = 'Ref: ' . $referenceNumber;
    }
    if ($attachmentName !== null) {
        $detailParts[] = 'Attachment: ' . $attachmentName;
    }

    $weekRangeLabel = cleanText($createMeta['week_range_label'] ?? null);
    $weeklyScheduleSummary = timekeepingFormatCosWeeklyScheduleSummary((array)($createMeta['weekly_schedule'] ?? []));
    if ($weekRangeLabel !== null) {
        $detailParts[] = 'Week: ' . $weekRangeLabel;
    }
    if ($weeklyScheduleSummary !== '') {
        $detailParts[] = 'Weekly Schedule: ' . $weeklyScheduleSummary;
    }

    $requestRow = [
        'id' => $requestId,
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'request_type' => (string)($parsedRequest['request_type'] ?? 'official_business'),
        'request_label' => (string)($parsedRequest['label'] ?? 'Special Request'),
        'overtime_date' => formatDateTimeForPhilippines(cleanText($row['overtime_date'] ?? null), 'M d, Y'),
        'time_window' => $startTime . ' - ' . $endTime,
        'hours_requested' => number_format((float)($row['hours_requested'] ?? 0), 2),
        'reason' => $reason,
        'detail_summary' => implode(' | ', $detailParts),
        'attachment_url' => $attachmentPath !== null ? systemAppPath($attachmentPath) : null,
        'attachment_name' => $attachmentName,
        'requested_label' => formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => strtolower(trim($employee['employee_name'] . ' ' . $employee['office_name'] . ' ' . ($parsedRequest['label'] ?? '') . ' ' . $reason . ' ' . implode(' ', $detailParts) . ' ' . $statusLabel)),
    ];

    if ((string)($requestRow['request_type'] ?? '') === 'cos_schedule' && isset($cosEmployeeRows[$personId])) {
        $cosEmployeeRows[$personId]['latest_cos_status'] = (string)$requestRow['status_label'];
        $cosEmployeeRows[$personId]['latest_cos_requested_label'] = (string)$requestRow['requested_label'];
        $cosEmployeeRows[$personId]['latest_cos_request_id'] = (string)$requestRow['id'];
        $cosEmployeeRows[$personId]['latest_cos_request_label'] = (string)$requestRow['request_label'];
        $cosEmployeeRows[$personId]['latest_cos_window'] = (string)$requestRow['time_window'];
        $cosEmployeeRows[$personId]['latest_cos_reason'] = trim((string)$requestRow['reason'] . (!empty($requestRow['detail_summary']) ? ' | ' . (string)$requestRow['detail_summary'] : ''));
        $cosEmployeeRows[$personId]['latest_cos_status_raw'] = (string)$requestRow['status_raw'];
    }

    $officialBusinessRequestRows[] = $requestRow;
}

$cosEmployeeRows = array_values($cosEmployeeRows);
usort($cosEmployeeRows, static function (array $left, array $right): int {
    return strcasecmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
});

foreach ($adjustmentRows as $row) {
    $requestId = cleanText($row['id'] ?? null) ?? '';
    $personId = cleanText($row['person_id'] ?? null) ?? '';
    if (!isValidUuid($requestId) || !isset($scopedPersonMap[$personId])) {
        continue;
    }

    $employee = $scopedPersonMap[$personId];
    $statusRaw = strtolower((string)(cleanText($row['status'] ?? null) ?? 'pending'));
    [$statusLabel, $statusClass] = $requestPill($statusRaw);

    if ($statusRaw === 'pending') {
        $timekeepingMetrics['pending_adjustments']++;
    }

    $reason = cleanText($row['reason'] ?? null) ?? '-';
    $requestedTimeIn = formatDateTimeForPhilippines(cleanText($row['requested_time_in'] ?? null), 'h:i A');
    $requestedTimeOut = formatDateTimeForPhilippines(cleanText($row['requested_time_out'] ?? null), 'h:i A');
    $attendance = $attendanceByAdjustmentLogId[(string)($row['attendance_log_id'] ?? '')] ?? [];
    $attendanceDate = formatDateTimeForPhilippines(cleanText($attendance['attendance_date'] ?? null), 'M d, Y');

    $adjustmentRequestRows[] = [
        'id' => $requestId,
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'attendance_date' => $attendanceDate,
        'requested_window' => $requestedTimeIn . ' - ' . $requestedTimeOut,
        'reason' => $reason,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => strtolower(trim($employee['employee_name'] . ' ' . $employee['office_name'] . ' ' . $reason . ' ' . $statusLabel . ' ' . $attendanceDate)),
    ];
}

$timekeepingMetrics['attendance_logs'] = count($attendanceRows);
