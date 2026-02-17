<?php

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
    'praise_status' => 'No active evaluation',
    'praise_status_class' => 'text-gray-700',
    'praise_detail' => 'No submitted cycle yet',
    'unread_notifications_count' => 0,
    'upcoming_leave_requests_count' => 0,
];

$dashboardWelcomeName = 'Employee';
$dashboardWelcomeMessage = 'Welcome back! You are all caught up for today.';

$dashboardAnnouncements = [];
$dashboardTasks = [];
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

$profileResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=first_name,surname'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (isSuccessful($profileResponse) && !empty((array)($profileResponse['data'] ?? []))) {
    $profileRow = (array)$profileResponse['data'][0];
    $firstName = cleanText($profileRow['first_name'] ?? null);
    $surname = cleanText($profileRow['surname'] ?? null);
    $fullName = trim((string)$firstName . ' ' . (string)$surname);
    if ($fullName !== '') {
        $dashboardWelcomeName = $fullName;
    }
}

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/attendance_logs?select=id,attendance_date,time_in,time_out,attendance_status'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&attendance_date=eq.' . rawurlencode($todayDate)
    . '&limit=1',
    $headers
);

$hasAttendanceToday = false;
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

    $hasAttendanceToday = true;
}

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,updated_at'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&document_status=eq.submitted'
    . '&order=updated_at.desc&limit=25',
    $headers
);

$pendingDocumentCount = 0;
if (isSuccessful($documentsResponse)) {
    $documentRows = (array)($documentsResponse['data'] ?? []);
    $pendingDocumentCount = count($documentRows);
    $dashboardSummary['pending_documents_count'] = $pendingDocumentCount;

    if ($pendingDocumentCount > 0) {
        $latestDocument = (array)$documentRows[0];
        $dashboardSummary['pending_documents_detail'] = 'Latest upload: ' . $formatDate(cleanText($latestDocument['updated_at'] ?? null));
    } else {
        $dashboardSummary['pending_documents_detail'] = 'No documents awaiting review';
    }
}

$leaveResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,date_from,date_to,status,created_at'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=eq.pending&order=created_at.desc&limit=10',
    $headers
);

$overtimeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/overtime_requests?select=id,overtime_date,status,created_at'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=eq.pending&order=created_at.desc&limit=10',
    $headers
);

$timeAdjustmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/time_adjustment_requests?select=id,status,created_at,attendance_log:attendance_logs!inner(attendance_date)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=eq.pending&order=created_at.desc&limit=10',
    $headers
);

$leaveRows = isSuccessful($leaveResponse) ? (array)($leaveResponse['data'] ?? []) : [];
$overtimeRows = isSuccessful($overtimeResponse) ? (array)($overtimeResponse['data'] ?? []) : [];
$timeAdjustmentRows = isSuccessful($timeAdjustmentResponse) ? (array)($timeAdjustmentResponse['data'] ?? []) : [];

foreach ($leaveRows as $leaveRowRaw) {
    $leaveRow = (array)$leaveRowRaw;
    $dashboardOpenRequests[] = [
        'title' => 'Leave Request',
        'meta' => $formatDate(cleanText($leaveRow['date_from'] ?? null)) . ' to ' . $formatDate(cleanText($leaveRow['date_to'] ?? null)),
        'status' => 'Pending',
        'created_at' => cleanText($leaveRow['created_at'] ?? null),
        'link' => 'timekeeping.php',
    ];
}

foreach ($overtimeRows as $overtimeRowRaw) {
    $overtimeRow = (array)$overtimeRowRaw;
    $dashboardOpenRequests[] = [
        'title' => 'Overtime Request',
        'meta' => $formatDate(cleanText($overtimeRow['overtime_date'] ?? null)),
        'status' => 'Pending',
        'created_at' => cleanText($overtimeRow['created_at'] ?? null),
        'link' => 'timekeeping.php',
    ];
}

foreach ($timeAdjustmentRows as $adjustmentRowRaw) {
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

$dashboardSummary['open_requests_count'] = count($dashboardOpenRequests);
$dashboardSummary['open_requests_detail'] = $dashboardSummary['open_requests_count'] > 0
    ? 'Awaiting supervisor/HR review'
    : 'No pending requests';

$dashboardOpenRequests = array_slice($dashboardOpenRequests, 0, 5);

$upcomingTrainingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_enrollments?select=id,enrollment_status,program:training_programs(title,start_date,end_date,provider,status)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&enrollment_status=in.(enrolled,completed)'
    . '&limit=300',
    $headers
);

$dashboardUpcomingTrainingCount = 0;
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

    $dashboardUpcomingTrainingCount = count($upcomingRows);
    $dashboardUpcomingTrainings = array_slice($upcomingRows, 0, 3);
}

$praiseResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,status,updated_at,cycle:performance_cycles(cycle_name)'
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=1',
    $headers
);

if (isSuccessful($praiseResponse) && !empty((array)($praiseResponse['data'] ?? []))) {
    $evaluationRow = (array)$praiseResponse['data'][0];
    $evaluationStatus = strtolower((string)($evaluationRow['status'] ?? 'draft'));
    $evaluationStatusMap = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'reviewed' => 'Under Review',
        'approved' => 'Approved',
    ];
    $evaluationClassMap = [
        'draft' => 'text-gray-700',
        'submitted' => 'text-blue-600',
        'reviewed' => 'text-yellow-600',
        'approved' => 'text-green-600',
    ];

    $dashboardSummary['praise_status'] = (string)($evaluationStatusMap[$evaluationStatus] ?? ucwords($evaluationStatus));
    $dashboardSummary['praise_status_class'] = (string)($evaluationClassMap[$evaluationStatus] ?? 'text-gray-700');
    $dashboardSummary['praise_detail'] = 'Updated: ' . $formatDate(cleanText($evaluationRow['updated_at'] ?? null));

    $cycle = (array)($evaluationRow['cycle'] ?? []);
    $cycleName = cleanText($cycle['cycle_name'] ?? null);
    if ($cycleName !== null) {
        $dashboardSummary['praise_detail'] = $cycleName . ' · ' . $dashboardSummary['praise_detail'];
    }
}

$notificationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/notifications?select=id,title,body,category,created_at,link_url,is_read'
    . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&order=created_at.desc&limit=5',
    $headers
);

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

        if (!(bool)($notification['is_read'] ?? false)) {
            $dashboardSummary['unread_notifications_count']++;
        }
    }
}

$upcomingLeaveResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/leave_requests?select=id,date_from,status'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=in.(pending,approved)'
    . '&date_from=gte.' . rawurlencode($todayDate)
    . '&order=date_from.asc&limit=200',
    $headers
);

if (isSuccessful($upcomingLeaveResponse)) {
    $dashboardSummary['upcoming_leave_requests_count'] = count((array)($upcomingLeaveResponse['data'] ?? []));
}

$unreadLabel = ((int)$dashboardSummary['unread_notifications_count'] === 1)
    ? '1 pending notification'
    : ((int)$dashboardSummary['unread_notifications_count'] . ' pending notifications');

$leaveLabel = ((int)$dashboardSummary['upcoming_leave_requests_count'] === 1)
    ? '1 upcoming leave request'
    : ((int)$dashboardSummary['upcoming_leave_requests_count'] . ' upcoming leave requests');

$dashboardWelcomeMessage = 'Welcome back, ' . $dashboardWelcomeName . '! You have ' . $unreadLabel . ' and ' . $leaveLabel . '.';

$activityResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,module_name,entity_name,action_name,created_at'
    . '&actor_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&order=created_at.desc&limit=5',
    $headers
);

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

if (!$hasAttendanceToday) {
    $dashboardTasks[] = [
        'title' => 'Check today\'s attendance log',
        'status' => 'Pending',
        'badge_class' => 'bg-yellow-100 text-yellow-800',
        'link' => 'timekeeping.php',
    ];
}

if ($pendingDocumentCount > 0) {
    $dashboardTasks[] = [
        'title' => 'Monitor submitted documents',
        'status' => 'For Review',
        'badge_class' => 'bg-red-100 text-red-800',
        'link' => 'document-management.php',
    ];
}

if ($dashboardSummary['open_requests_count'] > 0) {
    $dashboardTasks[] = [
        'title' => 'Track pending leave/time requests',
        'status' => 'Pending',
        'badge_class' => 'bg-yellow-100 text-yellow-800',
        'link' => 'timekeeping.php',
    ];
}

if (empty($dashboardTasks)) {
    $dashboardTasks[] = [
        'title' => 'No pending tasks',
        'status' => 'Up to date',
        'badge_class' => 'bg-green-100 text-green-800',
        'link' => 'dashboard.php',
    ];
}
