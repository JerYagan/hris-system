<?php

$todayDate = gmdate('Y-m-d');
$weekStartDate = gmdate('Y-m-d', strtotime('-6 days'));

$dashboardChartSchedule = [
    'attendance_time_input' => '05:30',
    'recruitment_time_input' => '12:00',
    'attendance_time_label' => '5:30AM',
    'recruitment_time_label' => '12:00NN',
];

$formatDashboardChartTimeLabel = static function (string $timeValue): string {
    $normalized = trim($timeValue);
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $normalized)) {
        return $normalized;
    }

    [$hourRaw, $minuteRaw] = explode(':', $normalized);
    $hour = (int)$hourRaw;
    $minute = (int)$minuteRaw;

    if ($hour === 12 && $minute === 0) {
        return '12:00NN';
    }

    if ($hour === 0 && $minute === 0) {
        return '12:00MN';
    }

    $period = $hour >= 12 ? 'PM' : 'AM';
    $displayHour = $hour % 12;
    if ($displayHour === 0) {
        $displayHour = 12;
    }

    return $displayHour . ':' . str_pad((string)$minute, 2, '0', STR_PAD_LEFT) . $period;
};

$chartSettingsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/system_settings?select=setting_key,setting_value&setting_key=in.(dashboard_chart_attendance_time,dashboard_chart_recruitment_time)&limit=10',
    $headers
);

if (isSuccessful($chartSettingsResponse)) {
    $chartSettingsRows = (array)($chartSettingsResponse['data'] ?? []);
    foreach ($chartSettingsRows as $row) {
        $settingKey = trim((string)($row['setting_key'] ?? ''));
        if ($settingKey === '') {
            continue;
        }

        $rawValue = '';
        $settingValue = $row['setting_value'] ?? null;
        if (is_array($settingValue)) {
            $rawValue = trim((string)($settingValue['value'] ?? ''));
        } elseif (is_string($settingValue) || is_numeric($settingValue)) {
            $rawValue = trim((string)$settingValue);
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $rawValue)) {
            continue;
        }

        if ($settingKey === 'dashboard_chart_attendance_time') {
            $dashboardChartSchedule['attendance_time_input'] = $rawValue;
        }
        if ($settingKey === 'dashboard_chart_recruitment_time') {
            $dashboardChartSchedule['recruitment_time_input'] = $rawValue;
        }
    }
}

$dashboardChartSchedule['attendance_time_label'] = $formatDashboardChartTimeLabel($dashboardChartSchedule['attendance_time_input']);
$dashboardChartSchedule['recruitment_time_label'] = $formatDashboardChartTimeLabel($dashboardChartSchedule['recruitment_time_input']);

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_status&attendance_date=eq.' . $todayDate . '&limit=2000',
    $headers
);

$attendanceWeekResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_status,attendance_date&attendance_date=gte.' . $weekStartDate . '&attendance_date=lte.' . $todayDate . '&limit=12000',
    $headers
);

$leaveRequestsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/leave_requests?select=id,date_from,date_to,days_count,status,reason,created_at,person:people(first_name,surname),leave_type:leave_types(leave_name)&status=eq.pending&order=created_at.desc&limit=500',
    $headers
);

$notificationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/notifications?select=id,category,title,body,link_url,is_read,created_at&recipient_user_id=eq.' . $adminUserId . '&order=created_at.desc&limit=500',
    $headers
);

$jobPositionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,is_active&is_active=eq.true&limit=5000',
    $headers
);

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,is_current,employment_status,office:office_id(office_name)&is_current=eq.true&employment_status=eq.active&limit=5000',
    $headers
);

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_status&limit=5000',
    $headers
);

$timeAdjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id&status=eq.pending&limit=5000',
    $headers
);

$documentsPendingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id&document_status=eq.submitted&limit=5000',
    $headers
);

$announcementLogsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at,module_name,entity_name&module_name=eq.dashboard&entity_name=eq.announcements&action_name=in.(save_announcement_draft,queue_announcement)&order=created_at.desc&limit=200',
    $headers
);

$attendanceRows = isSuccessful($attendanceResponse) ? (array)($attendanceResponse['data'] ?? []) : [];
$attendanceWeekRows = isSuccessful($attendanceWeekResponse) ? (array)($attendanceWeekResponse['data'] ?? []) : [];
$leaveRequestRowsRaw = isSuccessful($leaveRequestsResponse) ? (array)($leaveRequestsResponse['data'] ?? []) : [];
$notificationRowsRaw = isSuccessful($notificationsResponse) ? (array)($notificationsResponse['data'] ?? []) : [];
$jobPositionRows = isSuccessful($jobPositionsResponse) ? (array)($jobPositionsResponse['data'] ?? []) : [];
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];
$applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];
$pendingTimeAdjustmentRows = isSuccessful($timeAdjustmentsResponse) ? (array)($timeAdjustmentsResponse['data'] ?? []) : [];
$pendingDocumentRows = isSuccessful($documentsPendingResponse) ? (array)($documentsPendingResponse['data'] ?? []) : [];
$announcementRows = isSuccessful($announcementLogsResponse) ? (array)($announcementLogsResponse['data'] ?? []) : [];

$dataLoadError = null;
$responses = [
    ['label' => 'Attendance', 'response' => $attendanceResponse],
    ['label' => 'Attendance week', 'response' => $attendanceWeekResponse],
    ['label' => 'Pending leave', 'response' => $leaveRequestsResponse],
    ['label' => 'Notifications', 'response' => $notificationsResponse],
    ['label' => 'Job positions', 'response' => $jobPositionsResponse],
    ['label' => 'Employment records', 'response' => $employmentResponse],
    ['label' => 'Applications', 'response' => $applicationsResponse],
    ['label' => 'Time adjustments', 'response' => $timeAdjustmentsResponse],
    ['label' => 'Pending documents', 'response' => $documentsPendingResponse],
    ['label' => 'Announcement logs', 'response' => $announcementLogsResponse],
];

foreach ($responses as $entry) {
    $response = $entry['response'];
    if (isSuccessful($response)) {
        continue;
    }

    $piece = $entry['label'] . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $piece .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $piece) : $piece;
}

$attendanceCounts = [
    'present' => 0,
    'late' => 0,
    'absent' => 0,
    'leave' => 0,
    'holiday' => 0,
    'rest_day' => 0,
];

foreach ($attendanceRows as $entry) {
    $status = strtolower((string)($entry['attendance_status'] ?? ''));
    if (!array_key_exists($status, $attendanceCounts)) {
        continue;
    }

    $attendanceCounts[$status]++;
}

$presentToday = $attendanceCounts['present'] + $attendanceCounts['late'];
$absentToday = $attendanceCounts['absent'];
$attendanceAlerts = $attendanceCounts['late'] + $attendanceCounts['absent'];

$weeklyAbsenceSample = 0;
$weeklyAbsentCount = 0;
foreach ($attendanceWeekRows as $entry) {
    $status = strtolower((string)($entry['attendance_status'] ?? ''));
    if (!in_array($status, ['present', 'late', 'absent'], true)) {
        continue;
    }

    $weeklyAbsenceSample++;
    if ($status === 'absent') {
        $weeklyAbsentCount++;
    }
}

$absenceRateWeek = $weeklyAbsenceSample > 0
    ? round(($weeklyAbsentCount / $weeklyAbsenceSample) * 100, 2)
    : 0.0;

$pendingLeaveRows = [];
foreach ($leaveRequestRowsRaw as $entry) {
    $person = (array)($entry['person'] ?? []);
    $leaveType = (array)($entry['leave_type'] ?? []);

    $employeeName = trim(((string)($person['first_name'] ?? '')) . ' ' . ((string)($person['surname'] ?? '')));
    if ($employeeName === '') {
        $employeeName = 'Unknown employee';
    }

    $leaveTypeName = (string)($leaveType['leave_name'] ?? 'Leave');
    $daysCount = (float)($entry['days_count'] ?? 0);
    $daysLabel = rtrim(rtrim(number_format($daysCount, 2, '.', ''), '0'), '.');
    if ($daysLabel === '') {
        $daysLabel = '0';
    }

    $dateFromRaw = (string)($entry['date_from'] ?? '');
    $dateToRaw = (string)($entry['date_to'] ?? '');
    $dateRange = '-';
    if ($dateFromRaw !== '' && $dateToRaw !== '') {
        $dateRange = date('M d, Y', strtotime($dateFromRaw)) . ' - ' . date('M d, Y', strtotime($dateToRaw));
    }

    $statusRaw = strtolower((string)($entry['status'] ?? 'pending'));
    $statusLabel = ucfirst($statusRaw);
    $statusClass = 'bg-amber-100 text-amber-800';
    if ($statusRaw === 'approved') {
        $statusClass = 'bg-emerald-100 text-emerald-800';
    } elseif ($statusRaw === 'rejected') {
        $statusClass = 'bg-rose-100 text-rose-800';
    }

    $pendingLeaveRows[] = [
        'id' => (string)($entry['id'] ?? ''),
        'employee_name' => $employeeName,
        'leave_type' => $leaveTypeName,
        'days_label' => $daysLabel,
        'date_range' => $dateRange,
        'reason' => (string)($entry['reason'] ?? ''),
        'status_key' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => strtolower(trim($employeeName . ' ' . $leaveTypeName . ' ' . $dateRange . ' ' . ((string)($entry['reason'] ?? '')))),
    ];
}

$notificationsRows = [];
$unreadNotifications = 0;
$readNotifications = 0;
$highPriorityNotifications = 0;

foreach ($notificationRowsRaw as $entry) {
    $category = strtolower((string)($entry['category'] ?? 'general'));
    $isRead = (bool)($entry['is_read'] ?? false);

    if (!$isRead) {
        $unreadNotifications++;
        if (in_array($category, ['system', 'hr', 'payroll'], true)) {
            $highPriorityNotifications++;
        }
    }

    $statusLabel = $isRead ? 'Read' : 'Unread';
    $statusClass = $isRead ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800';

    $createdAtRaw = (string)($entry['created_at'] ?? '');
    $createdAtDisplay = $createdAtRaw !== '' ? date('M d, Y h:i A', strtotime($createdAtRaw)) : '-';

    $title = (string)($entry['title'] ?? 'Notification');
    $body = (string)($entry['body'] ?? '');

    $notificationsRows[] = [
        'id' => (string)($entry['id'] ?? ''),
        'category' => $category !== '' ? $category : 'general',
        'title' => $title,
        'body' => $body,
        'is_read' => $isRead,
        'status_label' => $statusLabel,
        'status_key' => strtolower($statusLabel),
        'status_class' => $statusClass,
        'created_at' => $createdAtDisplay,
        'search_text' => strtolower(trim($title . ' ' . $body . ' ' . $category . ' ' . $statusLabel)),
    ];
}

$approvedPositions = count($jobPositionRows);
$filledPositions = count($employmentRows);
$vacantPositions = max($approvedPositions - $filledPositions, 0);

$departmentCounts = [];
foreach ($employmentRows as $entry) {
    $office = (array)($entry['office'] ?? []);
    $officeName = trim((string)($office['office_name'] ?? 'Unassigned Office'));
    if ($officeName === '') {
        $officeName = 'Unassigned Office';
    }

    $departmentCounts[$officeName] = (int)($departmentCounts[$officeName] ?? 0) + 1;
}

arsort($departmentCounts);
$departmentRows = [];
foreach ($departmentCounts as $departmentName => $headcount) {
    $departmentRows[] = [
        'department' => (string)$departmentName,
        'headcount' => (int)$headcount,
        'search_text' => strtolower((string)$departmentName),
    ];
}

$pipelineCounts = [
    'registered' => 0,
    'screened' => 0,
    'shortlisted' => 0,
    'hired' => 0,
];

foreach ($applicationRows as $entry) {
    $status = strtolower((string)($entry['application_status'] ?? ''));

    if ($status === 'submitted') {
        $pipelineCounts['registered']++;
        continue;
    }

    if (in_array($status, ['screening', 'interview', 'offer'], true)) {
        $pipelineCounts['screened']++;
        continue;
    }

    if ($status === 'shortlisted') {
        $pipelineCounts['shortlisted']++;
        continue;
    }

    if ($status === 'hired') {
        $pipelineCounts['hired']++;
    }
}

$pendingRecruitmentDecisionCount = 0;
foreach ($applicationRows as $entry) {
    $status = strtolower((string)($entry['application_status'] ?? ''));
    if (in_array($status, ['submitted', 'screening', 'shortlisted', 'interview', 'offer'], true)) {
        $pendingRecruitmentDecisionCount++;
    }
}

$draftAnnouncementCount = 0;
$queuedAnnouncementCount = 0;
$latestAnnouncementTitle = 'No saved drafts yet';
$latestAnnouncementTimestamp = 'Save a draft to populate this section.';
$announcementDraftBody = '';

foreach ($announcementRows as $index => $entry) {
    $actionName = (string)($entry['action_name'] ?? '');
    if ($actionName === 'save_announcement_draft') {
        $draftAnnouncementCount++;
    }
    if ($actionName === 'queue_announcement') {
        $queuedAnnouncementCount++;
    }

    if ($index !== 0) {
        continue;
    }

    $newData = $entry['new_data'] ?? [];
    if (!is_array($newData)) {
        $newData = [];
    }

    $latestAnnouncementTitle = (string)($newData['title'] ?? $latestAnnouncementTitle);
    $announcementDraftBody = (string)($newData['body'] ?? '');

    $createdAtRaw = (string)($entry['created_at'] ?? '');
    if ($createdAtRaw !== '') {
        $latestAnnouncementTimestamp = 'Saved ' . date('M d, Y h:i A', strtotime($createdAtRaw));
    }
}

$dashboardSummary = [
    'attendance_alerts' => $attendanceAlerts,
    'pending_time_adjustments' => count($pendingTimeAdjustmentRows),
    'pending_leave_requests' => count($pendingLeaveRows),
    'pending_recruitment_decisions' => $pendingRecruitmentDecisionCount,
    'draft_announcements' => $draftAnnouncementCount,
    'pending_documents' => count($pendingDocumentRows),
    'unread_notifications' => $unreadNotifications,
    'high_priority_notifications' => $highPriorityNotifications,
    'total_employees' => count($employmentRows),
    'on_leave_today' => (int)($attendanceCounts['leave'] ?? 0),
    'absence_rate_week' => $absenceRateWeek,
    'present_today' => $presentToday,
    'absent_today' => $absentToday,
    'latest_announcement_title' => $latestAnnouncementTitle,
    'latest_announcement_timestamp' => $latestAnnouncementTimestamp,
    'latest_announcement_body' => $announcementDraftBody,
    'queued_announcements' => $queuedAnnouncementCount,
    'approved_positions' => $approvedPositions,
    'filled_positions' => $filledPositions,
    'vacant_positions' => $vacantPositions,
];

$pipelineChart = [
    'title' => 'Recruitment Pipeline - ' . date('M d, Y'),
    'subtitle' => '(Auto-updated ' . date('M d, Y') . ' at ' . $dashboardChartSchedule['recruitment_time_label'] . ')',
    'labels' => ['Registered', 'Screened', 'Shortlisted', 'Hired'],
    'values' => [
        $pipelineCounts['registered'],
        $pipelineCounts['screened'],
        $pipelineCounts['shortlisted'],
        $pipelineCounts['hired'],
    ],
    'updated_at' => date('M d, Y h:i A'),
];

$attendanceStatusChart = [
    'title' => 'Attendance Summary - ' . date('M d, Y'),
    'subtitle' => '(Auto-updated ' . date('M d, Y') . ' at ' . $dashboardChartSchedule['attendance_time_label'] . ')',
    'labels' => ['Present', 'Late', 'Absent', 'Leave', 'Holiday', 'Rest Day'],
    'values' => [
        (int)($attendanceCounts['present'] ?? 0),
        (int)($attendanceCounts['late'] ?? 0),
        (int)($attendanceCounts['absent'] ?? 0),
        (int)($attendanceCounts['leave'] ?? 0),
        (int)($attendanceCounts['holiday'] ?? 0),
        (int)($attendanceCounts['rest_day'] ?? 0),
    ],
    'updated_at' => date('M d, Y h:i A'),
];
