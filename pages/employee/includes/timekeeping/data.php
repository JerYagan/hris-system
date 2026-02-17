<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$employeeName = 'Employee';
$employeeCode = '-';

$attendanceSummary = [
    'month_label' => date('F Y'),
    'working_days' => 0,
    'present_days' => 0,
    'late_days' => 0,
    'leave_days' => 0,
];

$attendanceRows = [];
$leaveBalanceRows = [];
$leaveTypeOptions = [];
$leaveRequestRows = [];
$timeAdjustmentRows = [];
$overtimeRows = [];

$attendancePage = max(1, (int)($_GET['attendance_page'] ?? 1));
$attendancePageSize = 10;
$attendanceOffset = ($attendancePage - 1) * $attendancePageSize;
$attendanceHasPrev = $attendancePage > 1;
$attendanceHasNext = false;

$attendanceStatusFilter = strtolower((string)cleanText($_GET['attendance_status'] ?? null));
if (!in_array($attendanceStatusFilter, ['', 'present', 'late', 'absent', 'leave', 'holiday', 'rest_day'], true)) {
    $attendanceStatusFilter = '';
}

$attendanceFrom = cleanText($_GET['attendance_from'] ?? null);
$attendanceTo = cleanText($_GET['attendance_to'] ?? null);

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return false;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

if ($attendanceFrom !== null && !$isValidDate($attendanceFrom)) {
    $attendanceFrom = null;
}
if ($attendanceTo !== null && !$isValidDate($attendanceTo)) {
    $attendanceTo = null;
}
if ($attendanceFrom !== null && $attendanceTo !== null && strtotime($attendanceTo) < strtotime($attendanceFrom)) {
    [$attendanceFrom, $attendanceTo] = [$attendanceTo, $attendanceFrom];
}

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (isSuccessful($peopleResponse) && !empty((array)($peopleResponse['data'] ?? []))) {
    $person = (array)$peopleResponse['data'][0];
    $employeeName = trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['surname'] ?? ''));
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }
    $employeeCode = (string)($person['agency_employee_no'] ?? '-');
}

$attendanceUrl = $supabaseUrl
    . '/rest/v1/attendance_logs?select=id,attendance_date,time_in,time_out,hours_worked,undertime_hours,late_minutes,attendance_status,source'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=attendance_date.desc'
    . '&limit=' . ($attendancePageSize + 1)
    . '&offset=' . $attendanceOffset;

if ($attendanceStatusFilter !== '') {
    $attendanceUrl .= '&attendance_status=eq.' . rawurlencode($attendanceStatusFilter);
}
if ($attendanceFrom !== null) {
    $attendanceUrl .= '&attendance_date=gte.' . rawurlencode($attendanceFrom);
}
if ($attendanceTo !== null) {
    $attendanceUrl .= '&attendance_date=lte.' . rawurlencode($attendanceTo);
}

$attendanceResponse = apiRequest('GET', $attendanceUrl, $headers);
if (!isSuccessful($attendanceResponse)) {
    $dataLoadError = 'Unable to load attendance records right now.';
} else {
    $allAttendanceRows = (array)($attendanceResponse['data'] ?? []);
    if (count($allAttendanceRows) > $attendancePageSize) {
        $attendanceHasNext = true;
        $allAttendanceRows = array_slice($allAttendanceRows, 0, $attendancePageSize);
    }

    foreach ($allAttendanceRows as $attendanceRaw) {
        $row = (array)$attendanceRaw;
        $attendanceRows[] = [
            'id' => (string)($row['id'] ?? ''),
            'attendance_date' => (string)($row['attendance_date'] ?? ''),
            'time_in' => (string)($row['time_in'] ?? ''),
            'time_out' => (string)($row['time_out'] ?? ''),
            'hours_worked' => (float)($row['hours_worked'] ?? 0),
            'undertime_hours' => (float)($row['undertime_hours'] ?? 0),
            'late_minutes' => (int)($row['late_minutes'] ?? 0),
            'attendance_status' => strtolower((string)($row['attendance_status'] ?? 'present')),
            'source' => (string)($row['source'] ?? ''),
        ];
    }
}

$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$attendanceSummaryResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=attendance_status,attendance_date'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&attendance_date=gte.' . rawurlencode($currentMonthStart)
    . '&attendance_date=lte.' . rawurlencode($currentMonthEnd)
    . '&limit=500',
    $headers
);

if (isSuccessful($attendanceSummaryResponse)) {
    $summaryRows = (array)($attendanceSummaryResponse['data'] ?? []);
    $attendanceSummary['working_days'] = count($summaryRows);

    foreach ($summaryRows as $summaryRaw) {
        $status = strtolower((string)((array)$summaryRaw)['attendance_status'] ?? '');
        if ($status === 'present') {
            $attendanceSummary['present_days']++;
        } elseif ($status === 'late') {
            $attendanceSummary['late_days']++;
        } elseif ($status === 'leave') {
            $attendanceSummary['leave_days']++;
        }
    }
}

$currentYear = (int)date('Y');
$leaveBalancesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_balances?select=id,year,earned_credits,used_credits,remaining_credits,leave_type:leave_types(id,leave_name,leave_code)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&year=eq.' . $currentYear
    . '&order=created_at.desc&limit=100',
    $headers
);

if (isSuccessful($leaveBalancesResponse)) {
    foreach ((array)($leaveBalancesResponse['data'] ?? []) as $balanceRaw) {
        $balance = (array)$balanceRaw;
        $leaveType = (array)($balance['leave_type'] ?? []);
        $leaveBalanceRows[] = [
            'leave_name' => (string)($leaveType['leave_name'] ?? 'Leave Type'),
            'leave_code' => (string)($leaveType['leave_code'] ?? ''),
            'earned_credits' => (float)($balance['earned_credits'] ?? 0),
            'used_credits' => (float)($balance['used_credits'] ?? 0),
            'remaining_credits' => (float)($balance['remaining_credits'] ?? 0),
        ];
    }
}

$leaveTypesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/leave_types?select=id,leave_name,leave_code&is_active=eq.true&order=leave_name.asc&limit=100',
    $headers
);

if (isSuccessful($leaveTypesResponse)) {
    foreach ((array)($leaveTypesResponse['data'] ?? []) as $leaveTypeRaw) {
        $leaveType = (array)$leaveTypeRaw;
        $leaveTypeId = (string)($leaveType['id'] ?? '');
        if ($leaveTypeId === '') {
            continue;
        }
        $leaveTypeOptions[] = [
            'id' => $leaveTypeId,
            'leave_name' => (string)($leaveType['leave_name'] ?? ''),
            'leave_code' => (string)($leaveType['leave_code'] ?? ''),
        ];
    }
}

$leaveRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,date_from,date_to,days_count,reason,status,created_at,leave_type:leave_types(leave_name)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($leaveRequestsResponse)) {
    foreach ((array)($leaveRequestsResponse['data'] ?? []) as $requestRaw) {
        $request = (array)$requestRaw;
        $leaveType = (array)($request['leave_type'] ?? []);

        $leaveRequestRows[] = [
            'id' => (string)($request['id'] ?? ''),
            'leave_name' => (string)($leaveType['leave_name'] ?? 'Leave'),
            'date_from' => (string)($request['date_from'] ?? ''),
            'date_to' => (string)($request['date_to'] ?? ''),
            'days_count' => (float)($request['days_count'] ?? 0),
            'reason' => (string)($request['reason'] ?? ''),
            'status' => strtolower((string)($request['status'] ?? 'pending')),
            'created_at' => (string)($request['created_at'] ?? ''),
        ];
    }
}

$timeAdjustmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/time_adjustment_requests?select=id,attendance_log_id,requested_time_in,requested_time_out,reason,status,created_at,attendance:attendance_logs(attendance_date,time_in,time_out)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($timeAdjustmentResponse)) {
    foreach ((array)($timeAdjustmentResponse['data'] ?? []) as $adjustmentRaw) {
        $adjustment = (array)$adjustmentRaw;
        $attendance = (array)($adjustment['attendance'] ?? []);

        $timeAdjustmentRows[] = [
            'id' => (string)($adjustment['id'] ?? ''),
            'attendance_log_id' => (string)($adjustment['attendance_log_id'] ?? ''),
            'attendance_date' => (string)($attendance['attendance_date'] ?? ''),
            'requested_time_in' => (string)($adjustment['requested_time_in'] ?? ''),
            'requested_time_out' => (string)($adjustment['requested_time_out'] ?? ''),
            'reason' => (string)($adjustment['reason'] ?? ''),
            'status' => strtolower((string)($adjustment['status'] ?? 'pending')),
            'created_at' => (string)($adjustment['created_at'] ?? ''),
        ];
    }
}

$overtimeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=id,overtime_date,start_time,end_time,hours_requested,reason,status,created_at'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($overtimeResponse)) {
    foreach ((array)($overtimeResponse['data'] ?? []) as $overtimeRaw) {
        $overtime = (array)$overtimeRaw;

        $overtimeRows[] = [
            'id' => (string)($overtime['id'] ?? ''),
            'overtime_date' => (string)($overtime['overtime_date'] ?? ''),
            'start_time' => (string)($overtime['start_time'] ?? ''),
            'end_time' => (string)($overtime['end_time'] ?? ''),
            'hours_requested' => (float)($overtime['hours_requested'] ?? 0),
            'reason' => (string)($overtime['reason'] ?? ''),
            'status' => strtolower((string)($overtime['status'] ?? 'pending')),
            'created_at' => (string)($overtime['created_at'] ?? ''),
        ];
    }
}
