<?php

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_date,time_in,time_out,hours_worked,late_minutes,attendance_status,person:people(first_name,surname)&order=attendance_date.desc&limit=500',
    $headers
);

$adjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id,status,reason,requested_time_in,requested_time_out,created_at,attendance_log_id,person:people(first_name,surname,user_id),attendance:attendance_logs(attendance_date,time_in,time_out)&order=created_at.desc&limit=500',
    $headers
);

$leaveRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/leave_requests?select=id,date_from,date_to,days_count,status,reason,created_at,person:people(first_name,surname,user_id),leave_type:leave_types(leave_name)&order=created_at.desc&limit=500',
    $headers
);

$attendanceLogs = isSuccessful($attendanceResponse) ? $attendanceResponse['data'] : [];
$adjustmentRequests = isSuccessful($adjustmentsResponse) ? $adjustmentsResponse['data'] : [];
$leaveRequests = isSuccessful($leaveRequestsResponse) ? $leaveRequestsResponse['data'] : [];

$dataLoadError = null;
if (!isSuccessful($attendanceResponse)) {
    $dataLoadError = 'Attendance query failed (HTTP ' . (int)($attendanceResponse['status'] ?? 0) . ').';
    $raw = trim((string)($attendanceResponse['raw'] ?? ''));
    if ($raw !== '') {
        $dataLoadError .= ' ' . $raw;
    }
}

if (!isSuccessful($adjustmentsResponse)) {
    $adjustError = 'Adjustment query failed (HTTP ' . (int)($adjustmentsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($adjustmentsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $adjustError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $adjustError) : $adjustError;
}

if (!isSuccessful($leaveRequestsResponse)) {
    $leaveError = 'Leave query failed (HTTP ' . (int)($leaveRequestsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($leaveRequestsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $leaveError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $leaveError) : $leaveError;
}
