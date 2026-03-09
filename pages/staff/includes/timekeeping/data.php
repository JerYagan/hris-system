<?php

$attendanceRows = [];
$leaveRequestRows = [];
$officialBusinessRequestRows = [];
$adjustmentRequestRows = [];
$timekeepingMetrics = [
    'attendance_logs' => 0,
    'pending_leave' => 0,
    'pending_cto' => 0,
    'pending_official_business' => 0,
    'pending_adjustments' => 0,
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
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};


$scopeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=person_id,person:people!employment_records_person_id_fkey(first_name,surname,user_id,agency_employee_no),office:offices(office_name),position:job_positions(position_title)'
    . '&is_current=eq.true'
    . '&limit=5000',
    $headers
);
$appendDataError('Employment scope', $scopeResponse);

$employmentScopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];
$scopedPersonMap = [];
$rfidEmployeeLookup = [];

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

    $scopedPersonMap[$personId] = [
        'employee_name' => $employeeName,
        'office_name' => cleanText($scopeRow['office']['office_name'] ?? null) ?? 'Unassigned Division',
        'position_title' => cleanText($scopeRow['position']['position_title'] ?? null) ?? 'Unassigned Position',
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
    . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out,attendance_status,created_at'
    . $personFilter
    . '&order=attendance_date.desc&limit=500',
    $headers
);
$appendDataError('Attendance logs', $attendanceResponse);
$attendanceLogs = isSuccessful($attendanceResponse) ? (array)($attendanceResponse['data'] ?? []) : [];

$leaveResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,person_id,date_from,date_to,days_count,reason,status,created_at,leave_type:leave_types(leave_name)'
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
    . '/rest/v1/time_adjustment_requests?select=id,person_id,attendance_log_id,requested_time_in,requested_time_out,reason,status,created_at,attendance:attendance_logs(attendance_date,time_in,time_out)'
    . $personFilter
    . '&order=created_at.desc&limit=500',
    $headers
);
$appendDataError('Time adjustment requests', $adjustmentResponse);
$adjustmentRows = isSuccessful($adjustmentResponse) ? (array)($adjustmentResponse['data'] ?? []) : [];

$attendancePill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'present' => ['Present', 'bg-emerald-50 text-emerald-700'],
        'late' => ['Late', 'bg-amber-50 text-amber-700'],
        'absent' => ['Absent', 'bg-rose-50 text-rose-700'],
        'leave' => ['Leave', 'bg-blue-50 text-blue-700'],
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
    [$statusLabel, $statusClass] = $attendancePill($statusRaw);

    $attendanceRows[] = [
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'attendance_date_raw' => cleanText($log['attendance_date'] ?? null) ?? '',
        'date_label' => formatDateTimeForPhilippines(cleanText($log['attendance_date'] ?? null), 'M d, Y'),
        'time_in_label' => formatDateTimeForPhilippines(cleanText($log['time_in'] ?? null), 'h:i A'),
        'time_out_label' => formatDateTimeForPhilippines(cleanText($log['time_out'] ?? null), 'h:i A'),
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
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

    $leaveType = cleanText($row['leave_type']['leave_name'] ?? null) ?? 'Unassigned';
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
    $statusRaw = strtolower((string)(cleanText($row['status'] ?? null) ?? 'pending'));
    [$statusLabel, $statusClass] = $requestPill($statusRaw);

    $reasonRaw = cleanText($row['reason'] ?? null) ?? '-';
    $isOfficialBusiness = preg_match('/^\[OB\]\s*/i', $reasonRaw) === 1;
    $reason = $isOfficialBusiness
        ? trim((string)preg_replace('/^\[OB\]\s*/i', '', $reasonRaw))
        : $reasonRaw;
    if ($reason === '') {
        $reason = '-';
    }

    if ($statusRaw === 'pending') {
        if ($isOfficialBusiness) {
            $timekeepingMetrics['pending_official_business']++;
        }
    }

    $startTime = formatDateTimeForPhilippines(cleanText($row['start_time'] ?? null), 'h:i A');
    $endTime = formatDateTimeForPhilippines(cleanText($row['end_time'] ?? null), 'h:i A');

    $requestRow = [
        'id' => $requestId,
        'employee_name' => $employee['employee_name'],
        'office_name' => $employee['office_name'],
        'overtime_date' => formatDateTimeForPhilippines(cleanText($row['overtime_date'] ?? null), 'M d, Y'),
        'time_window' => $startTime . ' - ' . $endTime,
        'hours_requested' => number_format((float)($row['hours_requested'] ?? 0), 2),
        'reason' => $reason,
        'requested_label' => formatDateTimeForPhilippines(cleanText($row['created_at'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => strtolower(trim($employee['employee_name'] . ' ' . $employee['office_name'] . ' ' . $reason . ' ' . $statusLabel)),
    ];

    if ($isOfficialBusiness) {
        $officialBusinessRequestRows[] = $requestRow;
    }
}

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
    $attendanceDate = formatDateTimeForPhilippines(cleanText($row['attendance']['attendance_date'] ?? null), 'M d, Y');

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
