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
$leaveBalanceEntries = [];
$leaveBalanceRows = [];
$postedLeavePointSummary = [
    'sl' => 0.0,
    'vl' => 0.0,
    'cto' => 0.0,
];
$usedLeavePointSummary = [
    'sl' => 0.0,
    'vl' => 0.0,
    'cto' => 0.0,
];
$leavePointSummary = [
    'sl' => 0.0,
    'vl' => 0.0,
    'cto' => 0.0,
];
$pendingLeavePointSummary = [
    'sl' => 0.0,
    'vl' => 0.0,
    'cto' => 0.0,
];
$leaveBalanceLastUpdatedAt = null;
$leaveBalanceRefreshUrl = 'timekeeping.php?partial=leave-balance';
$leaveTypeOptions = [];
$leaveTypeMetaById = [];
$leaveRequestRows = [];
$timeAdjustmentRows = [];
$overtimeRows = [];

$aggregateLeaveBalanceRows = static function (array $rows, array $approvedDeductionByTypeId = [], array $pendingDeductionByTypeId = []): array {
    $aggregated = [];

    foreach ($rows as $rowRaw) {
        $row = is_array($rowRaw) ? $rowRaw : [];
        $leaveTypeId = trim((string)($row['leave_type_id'] ?? ''));
        $leaveCode = trim((string)($row['leave_code'] ?? ''));
        $leaveName = trim((string)($row['leave_name'] ?? 'Leave Type'));
        $updatedAt = cleanText($row['updated_at'] ?? null);
        $key = $leaveTypeId !== '' ? $leaveTypeId : strtolower($leaveCode . '|' . $leaveName);

        if (!isset($aggregated[$key])) {
            $aggregated[$key] = [
                'leave_type_id' => $leaveTypeId,
                'leave_name' => $leaveName,
                'leave_code' => $leaveCode,
                'admin_posted_total' => 0.0,
                'used_credits' => 0.0,
                'remaining_credits' => 0.0,
                'pending_deduction' => 0.0,
                'projected_remaining' => 0.0,
                'updated_at' => $updatedAt,
            ];
        }

        $earnedCredits = (float)($row['earned_credits'] ?? 0);
        $usedCredits = (float)($row['used_credits'] ?? 0);
        $remainingCredits = (float)($row['remaining_credits'] ?? 0);

        $aggregated[$key]['admin_posted_total'] += max($earnedCredits, max(0, $usedCredits + $remainingCredits));

        if ($updatedAt !== null) {
            $existingUpdatedAt = cleanText($aggregated[$key]['updated_at'] ?? null);
            if ($existingUpdatedAt === null || strtotime($updatedAt) > strtotime($existingUpdatedAt)) {
                $aggregated[$key]['updated_at'] = $updatedAt;
            }
        }
    }

    foreach ($aggregated as &$row) {
        $approvedDeduction = (float)($approvedDeductionByTypeId[(string)($row['leave_type_id'] ?? '')] ?? 0);
        $pendingDeduction = (float)($pendingDeductionByTypeId[(string)($row['leave_type_id'] ?? '')] ?? 0);
        $adminPostedTotal = (float)($row['admin_posted_total'] ?? 0);

        $row['used_credits'] = $approvedDeduction;
        $row['remaining_credits'] = $adminPostedTotal - $approvedDeduction;
        $row['pending_deduction'] = $pendingDeduction;
        $row['projected_remaining'] = $adminPostedTotal - $approvedDeduction - $pendingDeduction;
    }
    unset($row);

    $sortPriority = ['sl' => 1, 'vl' => 2, 'cto' => 3];
    usort($aggregated, static function (array $left, array $right) use ($sortPriority): int {
        $leftCode = strtolower(trim((string)($left['leave_code'] ?? '')));
        $rightCode = strtolower(trim((string)($right['leave_code'] ?? '')));
        $leftPriority = $sortPriority[$leftCode] ?? 99;
        $rightPriority = $sortPriority[$rightCode] ?? 99;

        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        return strcasecmp((string)($left['leave_name'] ?? ''), (string)($right['leave_name'] ?? ''));
    });

    return array_values($aggregated);
};

$attendancePage = max(1, (int)($_GET['attendance_page'] ?? 1));
$attendancePageSize = 10;
$attendanceOffset = ($attendancePage - 1) * $attendancePageSize;
$attendanceHasPrev = $attendancePage > 1;
$attendanceHasNext = false;

$isLateByApprovedPolicy = static function (?string $timeInValue): bool {
    $raw = trim((string)$timeInValue);
    if ($raw === '') {
        return false;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return false;
    }

    return date('H:i:s', $timestamp) >= '09:01:00';
};

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
        $isLateByPolicy = $isLateByApprovedPolicy((string)($row['time_in'] ?? ''));
        $rawStatus = strtolower((string)($row['attendance_status'] ?? 'present'));
        $displayStatus = $rawStatus;
        if ($isLateByPolicy && in_array($rawStatus, ['present', 'late'], true)) {
            $displayStatus = 'late';
        }

        $attendanceRows[] = [
            'id' => (string)($row['id'] ?? ''),
            'attendance_date' => (string)($row['attendance_date'] ?? ''),
            'time_in' => (string)($row['time_in'] ?? ''),
            'time_out' => (string)($row['time_out'] ?? ''),
            'hours_worked' => (float)($row['hours_worked'] ?? 0),
            'undertime_hours' => (float)($row['undertime_hours'] ?? 0),
            'late_minutes' => (int)($row['late_minutes'] ?? 0),
            'attendance_status' => $rawStatus,
            'display_status' => $displayStatus,
            'is_late_by_policy' => $isLateByPolicy,
            'source' => (string)($row['source'] ?? ''),
        ];
    }
}

$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$attendanceSummaryResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=attendance_status,attendance_date,time_in'
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
        $summaryRow = (array)$summaryRaw;
        $status = strtolower((string)($summaryRow['attendance_status'] ?? ''));
        $isLateByPolicy = $isLateByApprovedPolicy((string)($summaryRow['time_in'] ?? ''));
        if ($isLateByPolicy && in_array($status, ['present', 'late'], true)) {
            $status = 'late';
        }

        if ($status === 'present') {
            $attendanceSummary['present_days']++;
        } elseif ($status === 'late') {
            $attendanceSummary['late_days']++;
        } elseif ($status === 'leave') {
            $attendanceSummary['leave_days']++;
        }
    }
}

$leaveBalancesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_balances?select=id,year,earned_credits,used_credits,remaining_credits,updated_at,leave_type:leave_types(id,leave_name,leave_code)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=500',
    $headers
);

if (isSuccessful($leaveBalancesResponse)) {
    foreach ((array)($leaveBalancesResponse['data'] ?? []) as $balanceRaw) {
        $balance = (array)$balanceRaw;
        $leaveType = (array)($balance['leave_type'] ?? []);
        $leaveTypeId = (string)($leaveType['id'] ?? '');
        $updatedAt = cleanText($balance['updated_at'] ?? null);

        if ($updatedAt !== null) {
            if ($leaveBalanceLastUpdatedAt === null || strtotime($updatedAt) > strtotime((string)$leaveBalanceLastUpdatedAt)) {
                $leaveBalanceLastUpdatedAt = $updatedAt;
            }
        }

        $leaveBalanceEntries[] = [
            'leave_type_id' => $leaveTypeId,
            'leave_name' => (string)($leaveType['leave_name'] ?? 'Leave Type'),
            'leave_code' => (string)($leaveType['leave_code'] ?? ''),
            'earned_credits' => (float)($balance['earned_credits'] ?? 0),
            'used_credits' => (float)($balance['used_credits'] ?? 0),
            'remaining_credits' => (float)($balance['remaining_credits'] ?? 0),
            'pending_deduction' => 0.0,
            'projected_remaining' => (float)($balance['remaining_credits'] ?? 0),
            'updated_at' => $updatedAt,
        ];
    }

    $postedLeavePointSummary = resolveEmployeeLeavePointSummary($leaveBalanceEntries, 'admin_posted_total');
    $leavePointSummary = $postedLeavePointSummary;
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
        $leaveTypeMetaById[$leaveTypeId] = [
            'leave_name' => (string)($leaveType['leave_name'] ?? ''),
            'leave_code' => (string)($leaveType['leave_code'] ?? ''),
        ];
    }
}

$leaveRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,leave_type_id,date_from,date_to,days_count,reason,status,created_at,leave_type:leave_types(leave_name)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

$pendingDeductionByTypeId = [];
$approvedDeductionByTypeId = [];
$approvedPointRows = [];
$pendingPointRows = [];

if (isSuccessful($leaveRequestsResponse)) {
    foreach ((array)($leaveRequestsResponse['data'] ?? []) as $requestRaw) {
        $request = (array)$requestRaw;
        $leaveType = (array)($request['leave_type'] ?? []);
        $leaveStatus = strtolower((string)($request['status'] ?? 'pending'));
        $leaveTypeId = (string)($request['leave_type_id'] ?? '');
        $daysCount = (float)($request['days_count'] ?? 0);
        $leaveName = (string)($leaveType['leave_name'] ?? ($leaveTypeMetaById[$leaveTypeId]['leave_name'] ?? 'Leave'));
        $leaveCode = (string)($leaveType['leave_code'] ?? ($leaveTypeMetaById[$leaveTypeId]['leave_code'] ?? ''));

        if ($leaveStatus === 'pending' && $leaveTypeId !== '' && $daysCount > 0) {
            $pendingDeductionByTypeId[$leaveTypeId] = ($pendingDeductionByTypeId[$leaveTypeId] ?? 0.0) + $daysCount;
            $pendingPointRows[] = [
                'leave_type_id' => $leaveTypeId,
                'leave_name' => $leaveName,
                'leave_code' => $leaveCode,
                'points' => $daysCount,
            ];
        } elseif ($leaveStatus === 'approved' && $leaveTypeId !== '' && $daysCount > 0) {
            $approvedDeductionByTypeId[$leaveTypeId] = ($approvedDeductionByTypeId[$leaveTypeId] ?? 0.0) + $daysCount;
            $approvedPointRows[] = [
                'leave_type_id' => $leaveTypeId,
                'leave_name' => $leaveName,
                'leave_code' => $leaveCode,
                'points' => $daysCount,
            ];
        }

        $leaveRequestRows[] = [
            'id' => (string)($request['id'] ?? ''),
            'leave_type_id' => $leaveTypeId,
            'leave_name' => $leaveName,
            'date_from' => (string)($request['date_from'] ?? ''),
            'date_to' => (string)($request['date_to'] ?? ''),
            'days_count' => $daysCount,
            'reason' => (string)($request['reason'] ?? ''),
            'status' => $leaveStatus,
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
    $ctoBalanceTypeIds = [];
    foreach ($leaveBalanceEntries as $balanceRow) {
        $leaveCode = strtolower(trim((string)($balanceRow['leave_code'] ?? '')));
        $leaveName = strtolower(trim((string)($balanceRow['leave_name'] ?? '')));
        if ($leaveCode === 'cto' || str_contains($leaveName, 'cto') || str_contains($leaveName, 'compensatory')) {
            $ctoBalanceTypeIds[] = (string)($balanceRow['leave_type_id'] ?? '');
        }
    }
    foreach ($leaveTypeMetaById as $leaveTypeId => $leaveTypeMeta) {
        $leaveCode = strtolower(trim((string)($leaveTypeMeta['leave_code'] ?? '')));
        $leaveName = strtolower(trim((string)($leaveTypeMeta['leave_name'] ?? '')));
        if ($leaveCode === 'cto' || str_contains($leaveName, 'cto') || str_contains($leaveName, 'compensatory')) {
            $ctoBalanceTypeIds[] = (string)$leaveTypeId;
        }
    }
    $ctoBalanceTypeIds = array_values(array_unique(array_filter($ctoBalanceTypeIds, static fn ($id): bool => $id !== '')));
    $primaryCtoTypeId = $ctoBalanceTypeIds[0] ?? '';

    foreach ((array)($overtimeResponse['data'] ?? []) as $overtimeRaw) {
        $overtime = (array)$overtimeRaw;
        $rawReason = (string)($overtime['reason'] ?? '');
        $isOfficialBusiness = preg_match('/^\[OB\]\s*/i', $rawReason) === 1;

        if (!$isOfficialBusiness) {
            $requestStatus = strtolower((string)($overtime['status'] ?? 'pending'));
            $hoursRequested = (float)($overtime['hours_requested'] ?? 0);
            if ($requestStatus === 'pending' && $hoursRequested > 0) {
                if ($primaryCtoTypeId !== '') {
                    $pendingDeductionByTypeId[$primaryCtoTypeId] = ($pendingDeductionByTypeId[$primaryCtoTypeId] ?? 0.0) + $hoursRequested;
                }
                $pendingPointRows[] = [
                    'leave_type_id' => $primaryCtoTypeId,
                    'leave_name' => 'CTO',
                    'leave_code' => 'cto',
                    'points' => $hoursRequested,
                ];
            } elseif ($requestStatus === 'approved' && $hoursRequested > 0) {
                if ($primaryCtoTypeId !== '') {
                    $approvedDeductionByTypeId[$primaryCtoTypeId] = ($approvedDeductionByTypeId[$primaryCtoTypeId] ?? 0.0) + $hoursRequested;
                }
                $approvedPointRows[] = [
                    'leave_type_id' => $primaryCtoTypeId,
                    'leave_name' => 'CTO',
                    'leave_code' => 'cto',
                    'points' => $hoursRequested,
                ];
            }
            continue;
        }

        $displayReason = preg_replace('/^\[OB\]\s*/i', '', $rawReason) ?? $rawReason;

        $overtimeRows[] = [
            'id' => (string)($overtime['id'] ?? ''),
            'overtime_date' => (string)($overtime['overtime_date'] ?? ''),
            'start_time' => (string)($overtime['start_time'] ?? ''),
            'end_time' => (string)($overtime['end_time'] ?? ''),
            'hours_requested' => (float)($overtime['hours_requested'] ?? 0),
            'reason' => $displayReason,
            'status' => strtolower((string)($overtime['status'] ?? 'pending')),
            'created_at' => (string)($overtime['created_at'] ?? ''),
        ];
    }
}

$usedLeavePointSummary = resolveEmployeeLeavePointSummary($approvedPointRows, 'points');
$pendingLeavePointSummary = resolveEmployeeLeavePointSummary($pendingPointRows, 'points');

if (!empty($leaveBalanceEntries)) {
    $leaveBalanceRows = $aggregateLeaveBalanceRows($leaveBalanceEntries, $approvedDeductionByTypeId, $pendingDeductionByTypeId);
    $leavePointSummary = $postedLeavePointSummary;
}
