<?php

$dashboardDataStage = (string)($dashboardDataStage ?? 'full');
$dashboardLoadSummary = in_array($dashboardDataStage, ['full', 'summary'], true);
$dashboardLoadSecondary = in_array($dashboardDataStage, ['full', 'secondary'], true);

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$dashboardSummary = [
    'attendance_label' => 'No log today',
    'attendance_status_class' => 'text-gray-700',
    'attendance_detail' => 'No attendance entry recorded yet.',
    'attendance_badge' => 'No Attendance',
    'attendance_badge_class' => 'bg-gray-100 text-gray-700',
    'pending_documents_count' => 0,
    'pending_documents_detail' => 'Awaiting HR approval',
    'open_requests_count' => 0,
    'open_requests_detail' => 'No pending requests',
    'unread_notifications_count' => 0,
    'upcoming_leave_requests_count' => 0,
    'leave_points' => [
        'sl' => 0.0,
        'vl' => 0.0,
        'cto' => 0.0,
    ],
];

$dashboardWelcomeName = 'Employee';
$dashboardWelcomeMessage = 'Welcome back! You are all caught up for today.';

$dashboardAnnouncements = [];
$dashboardOpenRequests = [];
$dashboardRecentActivity = [];
$dashboardUpcomingTrainings = [];

$formatDate = static function (?string $dateTime): string {
    if ($dateTime === null || $dateTime === '') {
        return '-';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return '-';
    }

    return date('M j, Y', $timestamp);
};

$formatDateTime = static function (?string $dateTime): string {
    if ($dateTime === null || $dateTime === '') {
        return '-';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return '-';
    }

    return date('M j, Y \· g:i A', $timestamp);
};

$todayDate = date('Y-m-d');
$dashboardCacheTtlSeconds = 30;
$dashboardCacheScopeKey = implode('|', [$employeeUserId, (string)$employeePersonId, $todayDate]);
$readDashboardCache = static function (string $bucket) use ($dashboardCacheScopeKey, $dashboardCacheTtlSeconds): ?array {
    $cacheStore = isset($_SESSION['employee_dashboard_cache']) && is_array($_SESSION['employee_dashboard_cache'])
        ? (array)$_SESSION['employee_dashboard_cache']
        : [];
    $cacheEntry = isset($cacheStore[$dashboardCacheScopeKey][$bucket]) && is_array($cacheStore[$dashboardCacheScopeKey][$bucket])
        ? (array)$cacheStore[$dashboardCacheScopeKey][$bucket]
        : [];

    $cachedAt = (int)($cacheEntry['cached_at'] ?? 0);
    $payload = isset($cacheEntry['payload']) && is_array($cacheEntry['payload'])
        ? (array)$cacheEntry['payload']
        : [];

    if ($cachedAt <= 0 || (time() - $cachedAt) > $dashboardCacheTtlSeconds || empty($payload)) {
        return null;
    }

    return $payload;
};
$writeDashboardCache = static function (string $bucket, array $payload) use ($dashboardCacheScopeKey): void {
    if (!isset($_SESSION['employee_dashboard_cache']) || !is_array($_SESSION['employee_dashboard_cache'])) {
        $_SESSION['employee_dashboard_cache'] = [];
    }

    $_SESSION['employee_dashboard_cache'][$dashboardCacheScopeKey][$bucket] = [
        'cached_at' => time(),
        'payload' => $payload,
    ];
};

if ($dashboardLoadSummary) {
    $summaryCache = $readDashboardCache('summary');

    if ($summaryCache !== null) {
        $dashboardSummary = array_replace_recursive($dashboardSummary, (array)($summaryCache['dashboard_summary'] ?? []));
        $dashboardWelcomeName = (string)($summaryCache['dashboard_welcome_name'] ?? $dashboardWelcomeName);
        $dashboardWelcomeMessage = (string)($summaryCache['dashboard_welcome_message'] ?? $dashboardWelcomeMessage);
    } else {
        $contextDisplayName = cleanText($employeeContext['display_name'] ?? null);
        if ($contextDisplayName !== null) {
            $dashboardWelcomeName = $contextDisplayName;
        }

        $summaryBatchRequests = [
            'attendance' => [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/attendance_logs?select=id,attendance_date,time_in,time_out,attendance_status'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&attendance_date=eq.' . rawurlencode($todayDate)
                    . '&limit=1',
            ],
            'documents' => [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/documents?select=id,updated_at'
                    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&document_status=eq.submitted'
                    . '&order=updated_at.desc&limit=25',
            ],
            'leave_balances' => [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/leave_balances?select=earned_credits,used_credits,remaining_credits,leave_type:leave_types(leave_name,leave_code)'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&order=updated_at.desc&limit=500',
            ],
            'upcoming_leave' => [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/leave_requests?select=id'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&status=in.(pending,approved)'
                    . '&date_from=gte.' . rawurlencode($todayDate)
                    . '&order=date_from.asc&limit=200',
            ],
            'notifications_summary' => [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/notifications?select=is_read'
                    . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
                    . '&category=in.(announcement,system,hr,employee_profile,learning_and_development,payroll,timekeeping,documents,general)'
                    . (trim((string)($employeeRoleAssignedAt ?? '')) !== '' ? ('&created_at=gte.' . rawurlencode((string)$employeeRoleAssignedAt)) : '')
                    . '&order=created_at.desc&limit=50',
            ],
        ];

        $openRequestsCache = $readDashboardCache('open_requests');
        if ($openRequestsCache === null) {
            $summaryBatchRequests['leave_count'] = [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/leave_requests?select=id'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&status=eq.pending&limit=10',
            ];
            $summaryBatchRequests['overtime_count'] = [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/overtime_requests?select=id'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&status=eq.pending&limit=10',
            ];
            $summaryBatchRequests['time_adjustment_count'] = [
                'method' => 'GET',
                'url' => $supabaseUrl
                    . '/rest/v1/time_adjustment_requests?select=id'
                    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                    . '&status=eq.pending&limit=10',
            ];
        }

        $summaryResponses = function_exists('apiRequestBatch')
            ? apiRequestBatch($summaryBatchRequests, $headers)
            : [];

        $attendanceResponse = (array)($summaryResponses['attendance'] ?? []);

        if (isSuccessful($attendanceResponse) && !empty((array)($attendanceResponse['data'] ?? []))) {
            $attendanceRow = (array)$attendanceResponse['data'][0];
            $attendanceStatus = strtolower((string)($attendanceRow['attendance_status'] ?? 'present'));
            $timeInValue = cleanText($attendanceRow['time_in'] ?? null);
            $timeOutValue = cleanText($attendanceRow['time_out'] ?? null);

            $statusLabelMap = [
                'present' => 'Present',
                'late' => 'Late',
                'absent' => 'Absent',
                'leave' => 'On Leave',
                'holiday' => 'Holiday',
                'rest_day' => 'Rest Day',
            ];

            $statusClassMap = [
                'present' => 'text-green-600',
                'late' => 'text-yellow-600',
                'absent' => 'text-red-600',
                'leave' => 'text-blue-600',
                'holiday' => 'text-purple-600',
                'rest_day' => 'text-gray-700',
            ];

            $dashboardSummary['attendance_label'] = (string)($statusLabelMap[$attendanceStatus] ?? ucwords(str_replace('_', ' ', $attendanceStatus)));
            $dashboardSummary['attendance_status_class'] = (string)($statusClassMap[$attendanceStatus] ?? 'text-gray-700');

            if ($timeInValue !== null && $timeOutValue === null) {
                $dashboardSummary['attendance_detail'] = 'Time in: ' . $formatDateTime($timeInValue);
                $dashboardSummary['attendance_badge'] = 'On Shift';
                $dashboardSummary['attendance_badge_class'] = 'bg-green-100 text-green-800';
            } elseif ($timeInValue !== null && $timeOutValue !== null) {
                $dashboardSummary['attendance_detail'] = 'Time out: ' . $formatDateTime($timeOutValue);
                $dashboardSummary['attendance_badge'] = 'Completed Shift';
                $dashboardSummary['attendance_badge_class'] = 'bg-blue-100 text-blue-800';
            } elseif ($attendanceStatus === 'absent') {
                $dashboardSummary['attendance_detail'] = 'No attendance punches for today.';
                $dashboardSummary['attendance_badge'] = 'Action Needed';
                $dashboardSummary['attendance_badge_class'] = 'bg-red-100 text-red-800';
            } else {
                $dashboardSummary['attendance_detail'] = 'Attendance date: ' . $formatDate((string)($attendanceRow['attendance_date'] ?? $todayDate));
                $dashboardSummary['attendance_badge'] = 'Recorded';
                $dashboardSummary['attendance_badge_class'] = 'bg-gray-100 text-gray-700';
            }
        }

        $documentsResponse = (array)($summaryResponses['documents'] ?? []);
        $leaveBalanceResponse = (array)($summaryResponses['leave_balances'] ?? []);

        if (isSuccessful($leaveBalanceResponse)) {
            $dashboardSummary['leave_points'] = resolveEmployeeLeavePointSummary((array)($leaveBalanceResponse['data'] ?? []), 'admin_posted_total');
        }

        if (isSuccessful($documentsResponse)) {
            $documentRows = (array)($documentsResponse['data'] ?? []);
            $dashboardSummary['pending_documents_count'] = count($documentRows);

            if (!empty($documentRows)) {
                $latestDocument = (array)$documentRows[0];
                $dashboardSummary['pending_documents_detail'] = 'Latest upload: ' . $formatDate(cleanText($latestDocument['updated_at'] ?? null));
            } else {
                $dashboardSummary['pending_documents_detail'] = 'No documents awaiting review';
            }
        }

        if ($openRequestsCache !== null) {
            $cachedOpenRequests = isset($openRequestsCache['dashboard_open_requests']) && is_array($openRequestsCache['dashboard_open_requests'])
                ? (array)$openRequestsCache['dashboard_open_requests']
                : [];
            $dashboardSummary['open_requests_count'] = count($cachedOpenRequests);
            $dashboardSummary['open_requests_detail'] = $dashboardSummary['open_requests_count'] > 0
                ? 'Awaiting supervisor/HR review'
                : 'No pending requests';
        } else {
            $dashboardSummary['open_requests_count'] = count((array)(($summaryResponses['leave_count'] ?? [])['data'] ?? []))
                + count((array)(($summaryResponses['overtime_count'] ?? [])['data'] ?? []))
                + count((array)(($summaryResponses['time_adjustment_count'] ?? [])['data'] ?? []));
            $dashboardSummary['open_requests_detail'] = $dashboardSummary['open_requests_count'] > 0
                ? 'Awaiting supervisor/HR review'
                : 'No pending requests';
        }

        $upcomingLeaveResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/leave_requests?select=id'
            . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
            . '&status=in.(pending,approved)'
            . '&date_from=gte.' . rawurlencode($todayDate)
            . '&order=date_from.asc&limit=200',
            $headers
        );

        if (isSuccessful($upcomingLeaveResponse)) {
            $dashboardSummary['upcoming_leave_requests_count'] = count((array)($upcomingLeaveResponse['data'] ?? []));
        }

        $notificationsSummaryResponse = (array)($summaryResponses['notifications_summary'] ?? []);

        if (isSuccessful($notificationsSummaryResponse)) {
            foreach ((array)($notificationsSummaryResponse['data'] ?? []) as $notificationRaw) {
                $notification = (array)$notificationRaw;
                if (!(bool)($notification['is_read'] ?? false)) {
                    $dashboardSummary['unread_notifications_count']++;
                }
            }
        }

        $unreadLabel = ((int)$dashboardSummary['unread_notifications_count'] === 1)
            ? '1 pending notification'
            : ((int)$dashboardSummary['unread_notifications_count'] . ' pending notifications');

        $leaveLabel = ((int)$dashboardSummary['upcoming_leave_requests_count'] === 1)
            ? '1 upcoming leave request'
            : ((int)$dashboardSummary['upcoming_leave_requests_count'] . ' upcoming leave requests');

        $dashboardWelcomeMessage = 'Welcome back, ' . $dashboardWelcomeName . '! You have ' . $unreadLabel . ' and ' . $leaveLabel . '.';

        $writeDashboardCache('summary', [
            'dashboard_summary' => $dashboardSummary,
            'dashboard_welcome_name' => $dashboardWelcomeName,
            'dashboard_welcome_message' => $dashboardWelcomeMessage,
        ]);
    }
}

if ($dashboardLoadSecondary) {
    $secondaryCache = $readDashboardCache('secondary');
    if ($secondaryCache !== null) {
        $dashboardAnnouncements = (array)($secondaryCache['dashboard_announcements'] ?? []);
        $dashboardOpenRequests = (array)($secondaryCache['dashboard_open_requests'] ?? []);
        $dashboardRecentActivity = (array)($secondaryCache['dashboard_recent_activity'] ?? []);
        $dashboardUpcomingTrainings = (array)($secondaryCache['dashboard_upcoming_trainings'] ?? []);
    } else {
        $openRequestsCache = $readDashboardCache('open_requests');
        if ($openRequestsCache !== null) {
            $dashboardOpenRequests = (array)($openRequestsCache['dashboard_open_requests'] ?? []);
        } else {
            $openRequestResponses = function_exists('apiRequestBatch')
                ? apiRequestBatch([
                    'leave' => [
                        'method' => 'GET',
                        'url' => $supabaseUrl
                            . '/rest/v1/leave_requests?select=id,date_from,date_to,status,created_at'
                            . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                            . '&status=eq.pending&order=created_at.desc&limit=10',
                    ],
                    'overtime' => [
                        'method' => 'GET',
                        'url' => $supabaseUrl
                            . '/rest/v1/overtime_requests?select=id,overtime_date,status,created_at'
                            . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                            . '&status=eq.pending&order=created_at.desc&limit=10',
                    ],
                    'time_adjustment' => [
                        'method' => 'GET',
                        'url' => $supabaseUrl
                            . '/rest/v1/time_adjustment_requests?select=id,status,created_at,attendance_log:attendance_logs!inner(attendance_date)'
                            . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                            . '&status=eq.pending&order=created_at.desc&limit=10',
                    ],
                ], $headers)
                : [];

            foreach ((array)(($openRequestResponses['leave'] ?? [])['data'] ?? []) as $leaveRowRaw) {
                $leaveRow = (array)$leaveRowRaw;
                $dashboardOpenRequests[] = [
                    'title' => 'Leave Request',
                    'meta' => $formatDate(cleanText($leaveRow['date_from'] ?? null)) . ' to ' . $formatDate(cleanText($leaveRow['date_to'] ?? null)),
                    'status' => 'Pending',
                    'created_at' => cleanText($leaveRow['created_at'] ?? null),
                    'link' => 'timekeeping.php',
                ];
            }

            foreach ((array)(($openRequestResponses['overtime'] ?? [])['data'] ?? []) as $overtimeRowRaw) {
                $overtimeRow = (array)$overtimeRowRaw;
                $dashboardOpenRequests[] = [
                    'title' => 'Overtime Request',
                    'meta' => $formatDate(cleanText($overtimeRow['overtime_date'] ?? null)),
                    'status' => 'Pending',
                    'created_at' => cleanText($overtimeRow['created_at'] ?? null),
                    'link' => 'timekeeping.php',
                ];
            }

            foreach ((array)(($openRequestResponses['time_adjustment'] ?? [])['data'] ?? []) as $adjustmentRowRaw) {
                $adjustmentRow = (array)$adjustmentRowRaw;
                $attendanceLog = (array)($adjustmentRow['attendance_log'] ?? []);
                $dashboardOpenRequests[] = [
                    'title' => 'Time Adjustment Request',
                    'meta' => $formatDate(cleanText($attendanceLog['attendance_date'] ?? null)),
                    'status' => 'Pending',
                    'created_at' => cleanText($adjustmentRow['created_at'] ?? null),
                    'link' => 'timekeeping.php',
                ];
            }

            usort($dashboardOpenRequests, static function (array $a, array $b): int {
                $aTs = strtotime((string)($a['created_at'] ?? ''));
                $bTs = strtotime((string)($b['created_at'] ?? ''));
                return $bTs <=> $aTs;
            });

            $dashboardOpenRequests = array_slice($dashboardOpenRequests, 0, 5);
            $writeDashboardCache('open_requests', [
                'dashboard_open_requests' => $dashboardOpenRequests,
            ]);
        }

        $secondaryResponses = function_exists('apiRequestBatch')
            ? apiRequestBatch([
                'trainings' => [
                    'method' => 'GET',
                    'url' => $supabaseUrl
                        . '/rest/v1/training_enrollments?select=id,enrollment_status,program:training_programs(title,start_date,end_date,provider,status)'
                        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
                        . '&enrollment_status=in.(enrolled,completed)'
                        . '&limit=300',
                ],
                'notifications' => [
                    'method' => 'GET',
                    'url' => $supabaseUrl
                        . '/rest/v1/notifications?select=id,title,body,category,created_at,link_url,is_read'
                        . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
                        . '&category=in.(announcement,system,hr,employee_profile,learning_and_development,payroll,timekeeping,documents,general)'
                        . (trim((string)($employeeRoleAssignedAt ?? '')) !== '' ? ('&created_at=gte.' . rawurlencode((string)$employeeRoleAssignedAt)) : '')
                        . '&order=created_at.desc&limit=5',
                ],
                'activity' => [
                    'method' => 'GET',
                    'url' => $supabaseUrl
                        . '/rest/v1/activity_logs?select=id,module_name,entity_name,action_name,created_at'
                        . '&actor_user_id=eq.' . rawurlencode((string)$employeeUserId)
                        . '&order=created_at.desc&limit=5',
                ],
            ], $headers)
            : [];

        $upcomingTrainingResponse = (array)($secondaryResponses['trainings'] ?? []);

        if (isSuccessful($upcomingTrainingResponse)) {
            $upcomingRows = [];

            foreach ((array)($upcomingTrainingResponse['data'] ?? []) as $enrollmentRaw) {
                $enrollment = (array)$enrollmentRaw;
                $program = (array)($enrollment['program'] ?? []);
                $startDate = cleanText($program['start_date'] ?? null);

                if ($startDate === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) !== 1 || $startDate < $todayDate) {
                    continue;
                }

                $statusRaw = strtolower((string)($enrollment['enrollment_status'] ?? 'enrolled'));
                $statusLabel = $statusRaw === 'completed' ? 'Completed' : 'Enrolled';
                $statusClass = $statusRaw === 'completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';

                $upcomingRows[] = [
                    'title' => cleanText($program['title'] ?? null) ?? 'Training Program',
                    'provider' => cleanText($program['provider'] ?? null) ?? 'Provider not set',
                    'start_date' => $startDate,
                    'date_label' => $formatDate($startDate),
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                ];
            }

            usort($upcomingRows, static function (array $a, array $b): int {
                return strcmp((string)($a['start_date'] ?? ''), (string)($b['start_date'] ?? ''));
            });

            $dashboardUpcomingTrainings = array_slice($upcomingRows, 0, 3);
        }

        $notificationsResponse = (array)($secondaryResponses['notifications'] ?? []);

        if (isSuccessful($notificationsResponse)) {
            foreach ((array)($notificationsResponse['data'] ?? []) as $notificationRaw) {
                $notification = (array)$notificationRaw;
                $dashboardAnnouncements[] = [
                    'title' => (string)($notification['title'] ?? 'Update'),
                    'body' => (string)($notification['body'] ?? ''),
                    'category' => strtolower((string)($notification['category'] ?? 'system')),
                    'created_at' => cleanText($notification['created_at'] ?? null),
                    'link_url' => cleanText($notification['link_url'] ?? null),
                    'is_read' => (bool)($notification['is_read'] ?? false),
                ];
            }
        }

        $activityResponse = (array)($secondaryResponses['activity'] ?? []);

        if (isSuccessful($activityResponse)) {
            foreach ((array)($activityResponse['data'] ?? []) as $activityRaw) {
                $activity = (array)$activityRaw;

                $action = cleanText($activity['action_name'] ?? null);
                $entity = cleanText($activity['entity_name'] ?? null);
                $module = cleanText($activity['module_name'] ?? null);

                $activityTitle = 'Activity recorded';
                if ($action !== null && $entity !== null) {
                    $activityTitle = ucwords(str_replace('_', ' ', $action)) . ' ' . ucwords(str_replace('_', ' ', $entity));
                } elseif ($action !== null) {
                    $activityTitle = ucwords(str_replace('_', ' ', $action));
                } elseif ($module !== null) {
                    $activityTitle = ucwords(str_replace('_', ' ', $module)) . ' update';
                }

                $dashboardRecentActivity[] = [
                    'title' => $activityTitle,
                    'created_at' => cleanText($activity['created_at'] ?? null),
                ];
            }
        }

        $writeDashboardCache('secondary', [
            'dashboard_announcements' => $dashboardAnnouncements,
            'dashboard_open_requests' => $dashboardOpenRequests,
            'dashboard_recent_activity' => $dashboardRecentActivity,
            'dashboard_upcoming_trainings' => $dashboardUpcomingTrainings,
        ]);
    }
}
