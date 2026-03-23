<?php

if (!function_exists('dashboardApiRequestWithHeaders')) {
    function dashboardApiRequestWithHeaders(string $method, string $url, array $headers, ?array $body = null, bool $noBody = false): array
    {
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            static function ($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $normalized = trim($headerLine);
                if ($normalized === '' || !str_contains($normalized, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $normalized, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);

                return $length;
            }
        );

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        if ($noBody) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $startedAt = microtime(true);
        $responseBody = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status' => $statusCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
            'error' => $error !== '' ? $error : null,
            'duration_ms' => $durationMs,
            'response_headers' => $responseHeaders,
        ];
    }
}

if (!function_exists('dashboardCountFromResponse')) {
    function dashboardCountFromResponse(array $response): ?int
    {
        $headers = (array)($response['response_headers'] ?? []);
        $contentRange = trim((string)($headers['content-range'] ?? ''));
        if ($contentRange !== '' && preg_match('#/(\d+)$#', $contentRange, $matches) === 1) {
            return (int)$matches[1];
        }

        if (isset($response['count']) && is_numeric($response['count'])) {
            return (int)$response['count'];
        }

        $rows = is_array($response['data'] ?? null) ? (array)$response['data'] : [];
        return $rows === [] ? 0 : count($rows);
    }
}

if (!function_exists('dashboardFetchCount')) {
    function dashboardFetchCount(string $url, array $headers): array
    {
        $countHeaders = array_merge($headers, ['Prefer: count=exact', 'Range: 0-0']);
        $headResponse = dashboardApiRequestWithHeaders('HEAD', $url, $countHeaders, null, true);
        $headResponse['count'] = dashboardCountFromResponse($headResponse);
        if (isSuccessful($headResponse) && $headResponse['count'] !== null) {
            return $headResponse;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $fallbackResponse = dashboardApiRequestWithHeaders(
            'GET',
            $url . $separator . 'limit=1&offset=0',
            array_merge($headers, ['Prefer: count=exact'])
        );
        $fallbackResponse['count'] = dashboardCountFromResponse($fallbackResponse);

        return $fallbackResponse;
    }
}

$dashboardDataStage = (string)($dashboardDataStage ?? 'full');
$dashboardLoadSummary = in_array($dashboardDataStage, ['full', 'summary'], true);
$dashboardLoadSecondary = in_array($dashboardDataStage, ['full', 'secondary'], true);
$dashboardDepartmentPageSize = 10;
$dashboardDepartmentPage = max(1, (int)($_GET['department_page'] ?? 1));
$dashboardDepartmentSearch = trim((string)($_GET['department_search'] ?? ''));
$dashboardDepartmentSearchNormalized = strtolower($dashboardDepartmentSearch);

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

$attendanceResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$attendanceWeekResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$leaveRequestsResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$jobPositionsResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$employmentResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$employmentCountResponse = ['status' => 200, 'data' => [], 'raw' => '', 'count' => 0];
$applicationsResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$pendingRecruitmentDecisionResponse = ['status' => 200, 'data' => [], 'raw' => '', 'count' => 0];
$timeAdjustmentsResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$documentsPendingResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$supportTicketLogsResponse = ['status' => 200, 'data' => [], 'raw' => ''];
$announcementLogsResponse = ['status' => 200, 'data' => [], 'raw' => ''];

if ($dashboardLoadSummary || $dashboardLoadSecondary) {
    $attendanceResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_status&attendance_date=eq.' . $todayDate . '&limit=1200',
        $headers
    );

    $attendanceWeekResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_status,attendance_date&attendance_date=gte.' . $weekStartDate . '&attendance_date=lte.' . $todayDate . '&limit=5000',
        $headers
    );
}

if ($dashboardLoadSummary) {
    $leaveRequestsResponse = dashboardFetchCount(
        $supabaseUrl . '/rest/v1/leave_requests?select=id&status=eq.pending',
        $headers
    );

    $timeAdjustmentsResponse = dashboardFetchCount(
        $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id&status=eq.pending',
        $headers
    );

    $documentsPendingResponse = dashboardFetchCount(
        $supabaseUrl . '/rest/v1/documents?select=id&document_status=eq.submitted',
        $headers
    );

    $supportTicketLogsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at&module_name=eq.support&entity_name=eq.tickets&order=created_at.asc&limit=5000',
        $headers
    );

    if (!$dashboardLoadSecondary) {
        $employmentCountResponse = dashboardFetchCount(
            $supabaseUrl . '/rest/v1/employment_records?select=id&is_current=eq.true&employment_status=eq.active',
            $headers
        );

        $pendingRecruitmentDecisionResponse = dashboardFetchCount(
            $supabaseUrl . '/rest/v1/applications?select=id&application_status=in.(submitted,screening,shortlisted,interview,offer)',
            $headers
        );
    }
}

if ($dashboardLoadSecondary) {
    $jobPositionsResponse = dashboardFetchCount(
        $supabaseUrl . '/rest/v1/job_positions?select=id&is_active=eq.true',
        $headers
    );

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,is_current,employment_status,office:office_id(office_name)&is_current=eq.true&employment_status=eq.active&limit=2500',
        $headers
    );

    $applicationsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/applications?select=id,application_status&limit=2500',
        $headers
    );

    $announcementLogsResponse = fetchPublishedAnnouncementLogs($supabaseUrl, $headers, 60);
}
$attendanceRows = isSuccessful($attendanceResponse) ? (array)($attendanceResponse['data'] ?? []) : [];
$attendanceWeekRows = isSuccessful($attendanceWeekResponse) ? (array)($attendanceWeekResponse['data'] ?? []) : [];
$leaveRequestRowsRaw = isSuccessful($leaveRequestsResponse) ? (array)($leaveRequestsResponse['data'] ?? []) : [];
$jobPositionRows = isSuccessful($jobPositionsResponse) ? (array)($jobPositionsResponse['data'] ?? []) : [];
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];
$applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];
$pendingTimeAdjustmentRows = isSuccessful($timeAdjustmentsResponse) ? (array)($timeAdjustmentsResponse['data'] ?? []) : [];
$pendingDocumentRows = isSuccessful($documentsPendingResponse) ? (array)($documentsPendingResponse['data'] ?? []) : [];
$supportTicketLogRows = isSuccessful($supportTicketLogsResponse) ? (array)($supportTicketLogsResponse['data'] ?? []) : [];
$announcementLogRows = isSuccessful($announcementLogsResponse) ? (array)($announcementLogsResponse['data'] ?? []) : [];
$announcementMetrics = buildPublishedAnnouncementMetrics($announcementLogRows);

$dataLoadError = null;
$responses = [
    ['label' => 'Attendance', 'response' => $attendanceResponse],
    ['label' => 'Attendance week', 'response' => $attendanceWeekResponse],
    ['label' => 'Pending leave', 'response' => $leaveRequestsResponse],
    ['label' => 'Job positions', 'response' => $jobPositionsResponse],
    ['label' => 'Employment records', 'response' => $employmentResponse],
    ['label' => 'Employment summary', 'response' => $employmentCountResponse],
    ['label' => 'Applications', 'response' => $applicationsResponse],
    ['label' => 'Pending recruitment decisions', 'response' => $pendingRecruitmentDecisionResponse],
    ['label' => 'Time adjustments', 'response' => $timeAdjustmentsResponse],
    ['label' => 'Pending documents', 'response' => $documentsPendingResponse],
    ['label' => 'Support tickets', 'response' => $supportTicketLogsResponse],
    ['label' => 'Published announcements', 'response' => $announcementLogsResponse],
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

$pendingLeaveRequestCount = dashboardCountFromResponse($leaveRequestsResponse) ?? count($leaveRequestRowsRaw);
$unreadNotifications = 0;
$highPriorityNotifications = 0;

$approvedPositions = dashboardCountFromResponse($jobPositionsResponse) ?? count($jobPositionRows);
$filledPositions = $dashboardLoadSecondary
    ? count($employmentRows)
    : (dashboardCountFromResponse($employmentCountResponse) ?? 0);
$vacantPositions = max($approvedPositions - $filledPositions, 0);

$departmentCounts = [];
foreach ($employmentRows as $entry) {
    $office = (array)($entry['office'] ?? []);
    $officeName = trim((string)($office['office_name'] ?? 'Unassigned Division'));
    if ($officeName === '') {
        $officeName = 'Unassigned Division';
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

if ($dashboardDepartmentSearchNormalized !== '') {
    $departmentRows = array_values(array_filter(
        $departmentRows,
        static fn(array $row): bool => strpos((string)($row['search_text'] ?? ''), $dashboardDepartmentSearchNormalized) !== false
    ));
}

$dashboardDepartmentTotalRows = count($departmentRows);
$dashboardDepartmentTotalPages = max(1, (int)ceil($dashboardDepartmentTotalRows / $dashboardDepartmentPageSize));
if ($dashboardDepartmentPage > $dashboardDepartmentTotalPages) {
    $dashboardDepartmentPage = $dashboardDepartmentTotalPages;
}

$dashboardDepartmentOffset = ($dashboardDepartmentPage - 1) * $dashboardDepartmentPageSize;
$departmentRows = array_slice($departmentRows, $dashboardDepartmentOffset, $dashboardDepartmentPageSize);
$dashboardDepartmentPagination = [
    'page' => $dashboardDepartmentPage,
    'page_size' => $dashboardDepartmentPageSize,
    'total_rows' => $dashboardDepartmentTotalRows,
    'total_pages' => $dashboardDepartmentTotalPages,
    'from' => $dashboardDepartmentTotalRows > 0 ? ($dashboardDepartmentOffset + 1) : 0,
    'to' => $dashboardDepartmentTotalRows > 0 ? min($dashboardDepartmentOffset + $dashboardDepartmentPageSize, $dashboardDepartmentTotalRows) : 0,
    'search' => $dashboardDepartmentSearch,
    'has_prev' => $dashboardDepartmentPage > 1,
    'has_next' => $dashboardDepartmentPage < $dashboardDepartmentTotalPages,
    'prev_page' => max(1, $dashboardDepartmentPage - 1),
    'next_page' => min($dashboardDepartmentTotalPages, $dashboardDepartmentPage + 1),
];

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
if ($dashboardLoadSecondary) {
    foreach ($applicationRows as $entry) {
        $status = strtolower((string)($entry['application_status'] ?? ''));
        if (in_array($status, ['submitted', 'screening', 'shortlisted', 'interview', 'offer'], true)) {
            $pendingRecruitmentDecisionCount++;
        }
    }
} else {
    $pendingRecruitmentDecisionCount = dashboardCountFromResponse($pendingRecruitmentDecisionResponse) ?? 0;
}

$supportTicketsById = [];
foreach ($supportTicketLogRows as $entry) {
    $payload = is_array($entry['new_data'] ?? null) ? (array)$entry['new_data'] : [];
    $ticketId = trim((string)($entry['entity_id'] ?? $payload['ticket_id'] ?? ''));
    if ($ticketId === '') {
        continue;
    }

    $actionName = strtolower(trim((string)($entry['action_name'] ?? '')));
    if ($actionName === 'submit_ticket') {
        $supportTicketsById[$ticketId] = strtolower(trim((string)($payload['status'] ?? 'submitted')));
        continue;
    }

    if ($actionName === 'admin_ticket_update' && isset($supportTicketsById[$ticketId])) {
        $supportTicketsById[$ticketId] = strtolower(trim((string)($payload['status'] ?? $supportTicketsById[$ticketId])));
    }
}

$pendingSupportTicketCount = 0;
foreach ($supportTicketsById as $status) {
    if (in_array($status, ['submitted', 'in_review', 'forwarded_to_staff'], true)) {
        $pendingSupportTicketCount++;
    }
}

$dashboardSummary = [
    'attendance_alerts' => $attendanceAlerts,
    'pending_time_adjustments' => dashboardCountFromResponse($timeAdjustmentsResponse) ?? count($pendingTimeAdjustmentRows),
    'pending_leave_requests' => $pendingLeaveRequestCount,
    'pending_recruitment_decisions' => $pendingRecruitmentDecisionCount,
    'total_applicants_to_review' => $pendingRecruitmentDecisionCount,
    'pending_documents' => dashboardCountFromResponse($documentsPendingResponse) ?? count($pendingDocumentRows),
    'pending_support_tickets' => $pendingSupportTicketCount,
    'unread_notifications' => $unreadNotifications,
    'high_priority_notifications' => $highPriorityNotifications,
    'total_employees' => $filledPositions,
    'on_leave_today' => (int)($attendanceCounts['leave'] ?? 0),
    'absence_rate_week' => $absenceRateWeek,
    'present_today' => $presentToday,
    'absent_today' => $absentToday,
    'published_announcements' => (int)($announcementMetrics['total_published'] ?? 0),
    'announcement_in_app_delivered' => (int)($announcementMetrics['total_in_app_sent'] ?? 0),
    'announcement_email_delivered' => (int)($announcementMetrics['total_email_sent'] ?? 0),
    'latest_announcement_title' => (string)($announcementMetrics['latest_title'] ?? 'No published announcements yet'),
    'latest_announcement_timestamp' => (string)($announcementMetrics['latest_timestamp'] ?? 'Use Create Announcement to publish the first broadcast.'),
    'latest_announcement_body' => (string)($announcementMetrics['latest_body'] ?? ''),
    'latest_announcement_targets' => (int)($announcementMetrics['latest_targeted_users'] ?? 0),
    'latest_announcement_channel' => (string)($announcementMetrics['latest_channel'] ?? 'Not yet published'),
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

$plantillaChart = [
    'title' => 'Plantilla Distribution - ' . date('M d, Y'),
    'subtitle' => '(Auto-updated ' . date('M d, Y') . ')',
    'labels' => ['Filled Positions', 'Vacant Positions'],
    'values' => [
        (int)$filledPositions,
        (int)$vacantPositions,
    ],
    'updated_at' => date('M d, Y h:i A'),
];
