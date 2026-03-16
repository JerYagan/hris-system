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
$specialTimekeepingRows = [];
$employeeIsCos = timekeepingIsCosEmploymentStatus($employeeEmploymentStatus ?? null);
$employeeResourceLinks = [
    'leave_card_view_url' => 'https://docs.google.com/spreadsheets/d/1fnUaDNmAleeF6NcWwg_jUUFKpip6uBiy/edit?usp=sharing&ouid=110457973112188470700&rtpof=true&sd=true',
    'official_business_template_url' => 'https://docs.google.com/document/d/1oF-k_14HArDNj3YxyIEOAQQwO2lTNUcy/edit',
    'application_for_leave_template_url' => 'https://docs.google.com/spreadsheets/d/1jEz7xOB82ndjYqf0teL7DUU0gePZlEjx/edit?gid=419957008#gid=419957008',
];
$configuredEmployeeLinks = systemSettingLinksMap(
    $supabaseUrl,
    $headers,
    [
        'employee_leave_card_url',
        'official_business_report_template_url',
        'application_for_leave_template_url',
    ]
);
$employeeLeaveCardUrl = (string)($configuredEmployeeLinks['employee_leave_card_url'] ?? $employeeResourceLinks['leave_card_view_url'] ?? '');
$officialBusinessTemplateUrl = (string)($configuredEmployeeLinks['official_business_report_template_url'] ?? $employeeResourceLinks['official_business_template_url'] ?? '');
$applicationForLeaveTemplateUrl = (string)($configuredEmployeeLinks['application_for_leave_template_url'] ?? $employeeResourceLinks['application_for_leave_template_url'] ?? '');
$ctoExpiryBucketRows = [];

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

$resolveCtoBucketMeta = static function (?string $dateValue, ?array $bucketMeta = null): ?array {
    $meta = is_array($bucketMeta) ? $bucketMeta : [];
    $bucketKey = strtolower(trim((string)($meta['bucket_key'] ?? '')));
    $bucketYear = (int)($meta['year'] ?? 0);
    $displayLabel = trim((string)($meta['display_label'] ?? ''));

    if ($bucketKey === '' || $bucketYear <= 0) {
        $timestamp = strtotime((string)$dateValue);
        if ($timestamp === false) {
            return null;
        }

        $month = (int)date('n', $timestamp);
        $bucketKey = $month <= 6 ? 'jan_jun' : 'jul_dec';
        $bucketYear = (int)date('Y', $timestamp);
        $displayLabel = ($month <= 6 ? 'JAN-JUN' : 'JULY-DEC') . ' ' . $bucketYear;
    }

    $bucketOrder = $bucketKey === 'jan_jun' ? 1 : 2;

    return [
        'key' => $bucketYear . '-' . $bucketKey,
        'bucket_key' => $bucketKey,
        'bucket_label' => $bucketKey === 'jan_jun' ? 'JAN-JUN' : 'JULY-DEC',
        'year' => $bucketYear,
        'display_label' => $displayLabel !== '' ? $displayLabel : (($bucketKey === 'jan_jun' ? 'JAN-JUN' : 'JULY-DEC') . ' ' . $bucketYear),
        'sort_key' => sprintf('%04d-%d', $bucketYear, $bucketOrder),
    ];
};

$allocateCtoUsageToBuckets = static function (array &$buckets, float $points, string $targetField): void {
    if ($points <= 0 || $buckets === []) {
        return;
    }

    uasort($buckets, static function (array $left, array $right): int {
        return strcmp((string)($left['sort_key'] ?? ''), (string)($right['sort_key'] ?? ''));
    });

    $remaining = $points;
    foreach ($buckets as &$bucket) {
        $available = max(0.0, (float)($bucket['posted_points'] ?? 0) - (float)($bucket['used_points'] ?? 0) - (float)($bucket['pending_points'] ?? 0));
        if ($targetField === 'used_points') {
            $available = max(0.0, (float)($bucket['posted_points'] ?? 0) - (float)($bucket['used_points'] ?? 0));
        }

        if ($available <= 0) {
            continue;
        }

        $allocated = min($available, $remaining);
        $bucket[$targetField] = (float)($bucket[$targetField] ?? 0) + $allocated;
        $remaining -= $allocated;

        if ($remaining <= 0.0001) {
            break;
        }
    }
    unset($bucket);

    if ($remaining > 0.0001) {
        $legacyKey = 'legacy-unmapped';
        if (!isset($buckets[$legacyKey])) {
            $buckets[$legacyKey] = [
                'key' => $legacyKey,
                'bucket_key' => 'legacy',
                'bucket_label' => 'LEGACY',
                'year' => 0,
                'display_label' => 'Legacy / Unmapped',
                'sort_key' => '9999-9',
                'posted_points' => 0.0,
                'used_points' => 0.0,
                'pending_points' => 0.0,
            ];
        }

        $buckets[$legacyKey][$targetField] = (float)($buckets[$legacyKey][$targetField] ?? 0) + $remaining;
    }
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
if (!in_array($attendanceStatusFilter, ['', 'present', 'late', 'absent', 'leave', 'holiday', 'rest_day', 'travel'], true)) {
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

$approvedTravelLabelsByDate = [];
$approvedTravelResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=overtime_date,reason,status,start_time,end_time'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=eq.approved'
    . '&order=overtime_date.desc&limit=200',
    $headers
);

if (isSuccessful($approvedTravelResponse)) {
    foreach ((array)($approvedTravelResponse['data'] ?? []) as $travelRaw) {
        $travelRow = (array)$travelRaw;
        $parsedRequest = timekeepingParseTaggedReason((string)($travelRow['reason'] ?? ''));
        if (($parsedRequest['category'] ?? '') !== 'travel') {
            continue;
        }

        $travelDate = (string)($travelRow['overtime_date'] ?? '');
        if ($travelDate === '') {
            continue;
        }

        $approvedTravelLabelsByDate[$travelDate] = (string)($parsedRequest['label'] ?? 'Approved Travel');
    }
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
        $attendanceDate = (string)($row['attendance_date'] ?? '');
        $hasApprovedTravel = isset($approvedTravelLabelsByDate[$attendanceDate]);
        if ($isLateByPolicy && in_array($rawStatus, ['present', 'late'], true)) {
            $displayStatus = 'late';
        }
        if ($hasApprovedTravel && trim((string)($row['time_in'] ?? '')) === '' && trim((string)($row['time_out'] ?? '')) === '') {
            $displayStatus = 'travel';
        }

        $attendanceRows[] = [
            'id' => (string)($row['id'] ?? ''),
            'attendance_date' => $attendanceDate,
            'time_in' => (string)($row['time_in'] ?? ''),
            'time_out' => (string)($row['time_out'] ?? ''),
            'hours_worked' => (float)($row['hours_worked'] ?? 0),
            'undertime_hours' => (float)($row['undertime_hours'] ?? 0),
            'late_minutes' => (int)($row['late_minutes'] ?? 0),
            'attendance_status' => $rawStatus,
            'display_status' => $displayStatus,
            'is_late_by_policy' => $isLateByPolicy,
            'travel_label' => $hasApprovedTravel ? (string)$approvedTravelLabelsByDate[$attendanceDate] : '',
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
        $summaryDate = (string)($summaryRow['attendance_date'] ?? '');
        if ($isLateByPolicy && in_array($status, ['present', 'late'], true)) {
            $status = 'late';
        }
        if (isset($approvedTravelLabelsByDate[$summaryDate]) && trim((string)($summaryRow['time_in'] ?? '')) === '') {
            $status = 'travel';
        }

        if ($status === 'present') {
            $attendanceSummary['present_days']++;
        } elseif ($status === 'late') {
            $attendanceSummary['late_days']++;
        } elseif (in_array($status, ['leave', 'travel'], true)) {
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
$leaveRequestIds = [];

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

        $leaveRequestId = (string)($request['id'] ?? '');
        if ($leaveRequestId !== '') {
            $leaveRequestIds[] = $leaveRequestId;
        }
    }
}

$ctoExpiryBuckets = [];
if (!empty($leaveRequestIds)) {
    $leaveRequestIdFilter = implode(',', array_values(array_unique(array_filter($leaveRequestIds, static fn ($id): bool => $id !== ''))));
    if ($leaveRequestIdFilter !== '') {
        $leaveCardLogResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.leave_requests'
            . '&action_name=eq.log_leave_from_card'
            . '&entity_id=in.' . rawurlencode('(' . $leaveRequestIdFilter . ')')
            . '&order=created_at.desc&limit=1000',
            $headers
        );

        if (isSuccessful($leaveCardLogResponse)) {
            foreach ((array)($leaveCardLogResponse['data'] ?? []) as $logRaw) {
                $log = (array)$logRaw;
                $newData = is_array($log['new_data'] ?? null) ? (array)$log['new_data'] : [];
                if ((string)($newData['person_id'] ?? '') !== (string)$employeePersonId) {
                    continue;
                }

                $pointBreakdown = is_array($newData['leave_point_breakdown'] ?? null) ? (array)$newData['leave_point_breakdown'] : [];
                $ctoPoints = max(0.0, (float)($pointBreakdown['cto'] ?? 0));
                if ($ctoPoints <= 0) {
                    continue;
                }

                $bucketMeta = $resolveCtoBucketMeta(
                    (string)($newData['date_from'] ?? ''),
                    is_array($newData['cto_bucket'] ?? null) ? (array)$newData['cto_bucket'] : null
                );
                if (!is_array($bucketMeta)) {
                    continue;
                }

                $bucketKey = (string)($bucketMeta['key'] ?? '');
                if ($bucketKey === '') {
                    continue;
                }

                if (!isset($ctoExpiryBuckets[$bucketKey])) {
                    $ctoExpiryBuckets[$bucketKey] = array_merge($bucketMeta, [
                        'posted_points' => 0.0,
                        'used_points' => 0.0,
                        'pending_points' => 0.0,
                    ]);
                }

                $ctoExpiryBuckets[$bucketKey]['posted_points'] += $ctoPoints;
            }
        }
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

    $overtimeRequestRows = (array)($overtimeResponse['data'] ?? []);
    $specialRequestIds = [];
    foreach ($overtimeRequestRows as $candidateRaw) {
        $candidate = (array)$candidateRaw;
        $parsedReason = timekeepingParseTaggedReason((string)($candidate['reason'] ?? ''));
        if (($parsedReason['is_special'] ?? false) === true) {
            $requestId = (string)($candidate['id'] ?? '');
            if ($requestId !== '') {
                $specialRequestIds[] = $requestId;
            }
        }
    }

    $specialRequestCreateMetaById = [];
    $specialRequestTimelineById = [];
    $specialRequestStatusOverrideById = [];

    if (!empty($specialRequestIds)) {
        $activityResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
            . '&module_name=eq.timekeeping'
            . '&entity_name=eq.overtime_requests'
            . '&entity_id=in.' . rawurlencode('(' . implode(',', array_values(array_unique($specialRequestIds))) . ')')
            . '&action_name=in.' . rawurlencode('(create_official_business_request,create_cos_schedule_request,create_travel_order_request,create_travel_abroad_request,recommend_ob_request,review_ob,review_ob_revision)')
            . '&order=created_at.desc&limit=500',
            $headers
        );

        if (isSuccessful($activityResponse)) {
            foreach ((array)($activityResponse['data'] ?? []) as $logRaw) {
                $log = (array)$logRaw;
                $entityId = (string)($log['entity_id'] ?? '');
                if ($entityId === '') {
                    continue;
                }

                $actionName = strtolower((string)($log['action_name'] ?? ''));
                $newData = is_array($log['new_data'] ?? null) ? (array)$log['new_data'] : [];
                $createdAt = formatDateTimeForPhilippines((string)($log['created_at'] ?? ''), 'M d, Y h:i A');

                if (str_starts_with($actionName, 'create_') && !isset($specialRequestCreateMetaById[$entityId])) {
                    $specialRequestCreateMetaById[$entityId] = $newData;
                }

                if ($actionName === 'recommend_ob_request') {
                    $recommendedStatus = ucfirst(str_replace('_', ' ', strtolower((string)($newData['recommended_status'] ?? 'pending'))));
                    $specialRequestTimelineById[$entityId][] = 'Staff recommended ' . $recommendedStatus . ' on ' . $createdAt;
                    continue;
                }

                if ($actionName === 'review_ob' || $actionName === 'review_ob_revision') {
                    $statusTo = strtolower((string)($newData['status_to'] ?? $newData['status'] ?? 'pending'));
                    if ($statusTo === 'needs_revision') {
                        $specialRequestStatusOverrideById[$entityId] = 'needs_revision';
                        $specialRequestTimelineById[$entityId][] = 'Admin requested revision on ' . $createdAt;
                    } else {
                        $specialRequestTimelineById[$entityId][] = 'Admin ' . ucfirst(str_replace('_', ' ', $statusTo)) . ' on ' . $createdAt;
                    }
                    continue;
                }

                if (str_starts_with($actionName, 'create_')) {
                    $specialRequestTimelineById[$entityId][] = 'Submitted on ' . $createdAt;
                }
            }
        }
    }

    foreach ($overtimeRequestRows as $overtimeRaw) {
        $overtime = (array)$overtimeRaw;
        $rawReason = (string)($overtime['reason'] ?? '');
        $parsedRequest = timekeepingParseTaggedReason($rawReason);
        $isSpecialRequest = (bool)($parsedRequest['is_special'] ?? false);

        if (!$isSpecialRequest) {
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

        $requestId = (string)($overtime['id'] ?? '');
        $createMeta = (array)($specialRequestCreateMetaById[$requestId] ?? []);
        $statusOverride = cleanText($specialRequestStatusOverrideById[$requestId] ?? null);
        $displayStatus = strtolower($statusOverride ?? (string)($overtime['status'] ?? 'pending'));
        $displayReason = (string)($parsedRequest['clean_reason'] ?? $rawReason);
        $requestLabel = (string)($parsedRequest['label'] ?? 'Special Request');
        $timelineEntries = array_reverse((array)($specialRequestTimelineById[$requestId] ?? []));
        $detailParts = [];
        $destination = cleanText($createMeta['destination'] ?? null);
        $referenceNumber = cleanText($createMeta['reference_number'] ?? null);
        $attachmentMeta = is_array($createMeta['attachment'] ?? null) ? (array)$createMeta['attachment'] : [];
        $attachmentPath = cleanText($attachmentMeta['relative_path'] ?? null);
        $attachmentName = cleanText($attachmentMeta['original_name'] ?? null);

        if ($destination !== null) {
            $detailParts[] = 'Destination: ' . $destination;
        }
        if ($referenceNumber !== null) {
            $detailParts[] = 'Ref: ' . $referenceNumber;
        }
        if ($attachmentName !== null) {
            $detailParts[] = 'Attachment: ' . $attachmentName;
        }

        $requestRow = [
            'id' => $requestId,
            'overtime_date' => (string)($overtime['overtime_date'] ?? ''),
            'start_time' => (string)($overtime['start_time'] ?? ''),
            'end_time' => (string)($overtime['end_time'] ?? ''),
            'hours_requested' => (float)($overtime['hours_requested'] ?? 0),
            'reason' => $displayReason,
            'status' => $displayStatus,
            'created_at' => (string)($overtime['created_at'] ?? ''),
            'request_type' => (string)($parsedRequest['request_type'] ?? 'official_business'),
            'request_label' => $requestLabel,
            'request_category' => (string)($parsedRequest['category'] ?? 'official_business'),
            'destination' => $destination,
            'reference_number' => $referenceNumber,
            'attachment_path' => $attachmentPath,
            'attachment_url' => $attachmentPath !== null ? systemAppPath($attachmentPath) : null,
            'attachment_name' => $attachmentName,
            'detail_summary' => implode(' | ', $detailParts),
            'timeline_summary' => implode(' | ', $timelineEntries),
        ];

        $overtimeRows[] = $requestRow;
        $specialTimekeepingRows[] = $requestRow;
    }
}

$usedLeavePointSummary = resolveEmployeeLeavePointSummary($approvedPointRows, 'points');
$pendingLeavePointSummary = resolveEmployeeLeavePointSummary($pendingPointRows, 'points');

$ctoPostedTotal = (float)($postedLeavePointSummary['cto'] ?? 0.0);
if ($ctoPostedTotal > 0 && $ctoExpiryBuckets === []) {
    $currentYear = (int)date('Y');
    foreach (['jan_jun' => 'JAN-JUN', 'jul_dec' => 'JULY-DEC'] as $bucketKey => $bucketLabel) {
        $derivedKey = $currentYear . '-' . $bucketKey;
        $ctoExpiryBuckets[$derivedKey] = [
            'key' => $derivedKey,
            'bucket_key' => $bucketKey,
            'bucket_label' => $bucketLabel,
            'year' => $currentYear,
            'display_label' => $bucketLabel . ' ' . $currentYear,
            'sort_key' => sprintf('%04d-%d', $currentYear, $bucketKey === 'jan_jun' ? 1 : 2),
            'posted_points' => 0.0,
            'used_points' => 0.0,
            'pending_points' => 0.0,
        ];
    }
}

if ($ctoPostedTotal > 0) {
    $bucketedPostedTotal = 0.0;
    foreach ($ctoExpiryBuckets as $bucket) {
        $bucketedPostedTotal += (float)($bucket['posted_points'] ?? 0);
    }

    $unmappedPosted = max(0.0, $ctoPostedTotal - $bucketedPostedTotal);
    if ($unmappedPosted > 0.0001) {
        $legacyKey = 'legacy-unmapped';
        if (!isset($ctoExpiryBuckets[$legacyKey])) {
            $ctoExpiryBuckets[$legacyKey] = [
                'key' => $legacyKey,
                'bucket_key' => 'legacy',
                'bucket_label' => 'LEGACY',
                'year' => 0,
                'display_label' => 'Legacy / Unmapped',
                'sort_key' => '9999-9',
                'posted_points' => 0.0,
                'used_points' => 0.0,
                'pending_points' => 0.0,
            ];
        }

        $ctoExpiryBuckets[$legacyKey]['posted_points'] += $unmappedPosted;
    }
}

$allocateCtoUsageToBuckets($ctoExpiryBuckets, (float)($usedLeavePointSummary['cto'] ?? 0.0), 'used_points');
$allocateCtoUsageToBuckets($ctoExpiryBuckets, (float)($pendingLeavePointSummary['cto'] ?? 0.0), 'pending_points');

if ($ctoExpiryBuckets !== []) {
    uasort($ctoExpiryBuckets, static function (array $left, array $right): int {
        return strcmp((string)($left['sort_key'] ?? ''), (string)($right['sort_key'] ?? ''));
    });

    foreach ($ctoExpiryBuckets as $bucket) {
        $postedPoints = (float)($bucket['posted_points'] ?? 0.0);
        $usedPoints = (float)($bucket['used_points'] ?? 0.0);
        $pendingPoints = (float)($bucket['pending_points'] ?? 0.0);
        $remainingPoints = max(0.0, $postedPoints - $usedPoints);
        $projectedRemaining = max(0.0, $remainingPoints - $pendingPoints);

        $ctoExpiryBucketRows[] = [
            'display_label' => (string)($bucket['display_label'] ?? 'CTO Bucket'),
            'bucket_label' => (string)($bucket['bucket_label'] ?? ''),
            'year' => (int)($bucket['year'] ?? 0),
            'posted_points' => $postedPoints,
            'used_points' => $usedPoints,
            'pending_points' => $pendingPoints,
            'remaining_points' => $remainingPoints,
            'projected_remaining' => $projectedRemaining,
        ];
    }
}

if (!empty($leaveBalanceEntries)) {
    $leaveBalanceRows = $aggregateLeaveBalanceRows($leaveBalanceEntries, $approvedDeductionByTypeId, $pendingDeductionByTypeId);
    $leavePointSummary = $postedLeavePointSummary;
}
