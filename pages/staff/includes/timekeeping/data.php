<?php

$attendanceRows = [];
$leaveRequestRows = [];
$overtimeRequestRows = [];
$adjustmentRequestRows = [];
$timekeepingMetrics = [
    'attendance_logs' => 0,
    'pending_leave' => 0,
    'pending_overtime' => 0,
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


$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$officeScopedFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$scopeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=person_id,person:people!employment_records_person_id_fkey(first_name,surname,user_id),office:offices(office_name)'
    . '&is_current=eq.true'
    . $officeScopedFilter
    . '&limit=5000',
    $headers
);
$appendDataError('Employment scope', $scopeResponse);

$employmentScopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];
$scopedPersonMap = [];

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
        'office_name' => cleanText($scopeRow['office']['office_name'] ?? null) ?? 'Unassigned Office',
        'user_id' => $userId,
    ];
}

$personIds = array_keys($scopedPersonMap);
if (empty($personIds) && !$isAdminScope) {
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
        'present' => ['Present', 'bg-emerald-100 text-emerald-800'],
        'late' => ['Late', 'bg-amber-100 text-amber-800'],
        'absent' => ['Absent', 'bg-rose-100 text-rose-800'],
        'leave' => ['Leave', 'bg-blue-100 text-blue-800'],
        'holiday' => ['Holiday', 'bg-indigo-100 text-indigo-800'],
        'rest_day' => ['Rest Day', 'bg-slate-200 text-slate-700'],
        default => ['Unknown', 'bg-slate-100 text-slate-700'],
    };
};

$requestPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'cancelled' => ['Cancelled', 'bg-slate-200 text-slate-700'],
        'needs_revision' => ['Needs Revision', 'bg-blue-100 text-blue-800'],
        default => ['Pending', 'bg-amber-100 text-amber-800'],
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

    if ($statusRaw === 'pending') {
        $timekeepingMetrics['pending_leave']++;
    }

    $leaveType = cleanText($row['leave_type']['leave_name'] ?? null) ?? 'Unassigned';
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

    if ($statusRaw === 'pending') {
        $timekeepingMetrics['pending_overtime']++;
    }

    $reason = cleanText($row['reason'] ?? null) ?? '-';
    $startTime = formatDateTimeForPhilippines(cleanText($row['start_time'] ?? null), 'h:i A');
    $endTime = formatDateTimeForPhilippines(cleanText($row['end_time'] ?? null), 'h:i A');

    $overtimeRequestRows[] = [
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
