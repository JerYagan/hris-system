<?php

$dashboardDataStage = (string)($dashboardDataStage ?? 'full');
$dashboardLoadSummary = in_array($dashboardDataStage, ['full', 'summary'], true);
$dashboardLoadApprovals = in_array($dashboardDataStage, ['full', 'approvals'], true);
$dashboardLoadNotifications = in_array($dashboardDataStage, ['full', 'notifications'], true);
$dashboardLoadAnnouncements = in_array($dashboardDataStage, ['full', 'announcements'], true);

$dashboardMetrics = [
    'active_employees' => 0,
    'pending_applications' => 0,
    'documents_for_verification' => 0,
    'payroll_tasks' => 0,
    'pending_timekeeping' => 0,
];

$dashboardAnnouncements = [];
$dashboardRoleNotifications = [];
$dashboardPendingApprovals = [];
$dashboardPendingApprovalsPerPage = 10;
$dashboardPendingApprovalsPage = max(1, (int)($_GET['approvals_page'] ?? 1));
$dashboardPendingApprovalsTotal = 0;
$dashboardPendingApprovalsTotalPages = 1;
$dashboardPendingApprovalsStart = 0;
$dataLoadError = null;

$staffDashboardQueryLimits = [
    'active_employment' => 1000,
    'employment_scope' => 1500,
    'job_postings' => 80,
    'applications' => 250,
    'documents' => 400,
    'payroll_runs' => 120,
    'timekeeping_queue' => 60,
    'notifications' => 40,
];

$staffRoleKeyNormalized = strtolower((string)($staffRoleKey ?? ''));
$isAdminDashboardScope = $staffRoleKeyNormalized === 'admin';
$scopeOfficeId = cleanText($staffOfficeId ?? null);
$officeFilter = (!$isAdminDashboardScope && $scopeOfficeId !== null && isValidUuid($scopeOfficeId))
    ? '&office_id=eq.' . rawurlencode($scopeOfficeId)
    : '';

$appendDashboardError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $piece = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $piece .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $piece) : $piece;
};

$needsPrimaryData = $dashboardLoadSummary || $dashboardLoadApprovals;

$employmentActiveRows = [];
$scopePersonIds = [];
$jobPostingRows = [];
$jobPostingIds = [];
$appRows = [];
$documentsRows = [];
$payrollRunRows = [];
$leaveRows = [];
$overtimeRows = [];
$adjustmentRows = [];
$pendingApplicationStatuses = ['submitted', 'screening', 'shortlisted', 'interview', 'offer'];

if ($needsPrimaryData) {
    $employmentActiveResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,person_id&is_current=eq.true&employment_status=eq.active' . $officeFilter . '&limit=' . $staffDashboardQueryLimits['active_employment'],
        $headers
    );
    $appendDashboardError('Active employment', $employmentActiveResponse);
    $employmentActiveRows = isSuccessful($employmentActiveResponse) ? (array)($employmentActiveResponse['data'] ?? []) : [];
    $dashboardMetrics['active_employees'] = count($employmentActiveRows);

    $employmentScopeResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=person_id&is_current=eq.true' . $officeFilter . '&limit=' . $staffDashboardQueryLimits['employment_scope'],
        $headers
    );
    $appendDashboardError('Employment scope', $employmentScopeResponse);
    foreach ((array)(isSuccessful($employmentScopeResponse) ? ($employmentScopeResponse['data'] ?? []) : []) as $row) {
        $personId = cleanText($row['person_id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            continue;
        }

        $scopePersonIds[$personId] = true;
    }
    $scopePersonIds = array_keys($scopePersonIds);

    $jobPostingsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,updated_at,created_at' . $officeFilter . '&order=updated_at.desc&limit=' . $staffDashboardQueryLimits['job_postings'],
        $headers
    );
    $appendDashboardError('Job postings', $jobPostingsResponse);
    $jobPostingRows = isSuccessful($jobPostingsResponse) ? (array)($jobPostingsResponse['data'] ?? []) : [];

    foreach ($jobPostingRows as $row) {
        $postingId = cleanText($row['id'] ?? null);
        if ($postingId !== null && isValidUuid($postingId)) {
            $jobPostingIds[$postingId] = true;
        }
    }
    $jobPostingIds = array_keys($jobPostingIds);

    if (!empty($jobPostingIds)) {
        $applicationsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=id,application_status,submitted_at,updated_at,job_posting_id,applicant:applicant_profiles(full_name),job:job_postings(title)'
            . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $jobPostingIds) . ')')
            . '&order=updated_at.desc&limit=' . $staffDashboardQueryLimits['applications'],
            $headers
        );
        $appendDashboardError('Applications', $applicationsResponse);
        $appRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];
    }

    foreach ($appRows as $row) {
        $status = strtolower((string)($row['application_status'] ?? ''));
        if (in_array($status, $pendingApplicationStatuses, true)) {
            $dashboardMetrics['pending_applications']++;
        }
    }

    if (!empty($scopePersonIds)) {
        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/documents?select=id,owner_person_id,document_status,updated_at,title,owner:people(first_name,surname)'
            . '&owner_person_id=in.' . rawurlencode('(' . implode(',', $scopePersonIds) . ')')
            . '&document_status=eq.submitted&limit=' . $staffDashboardQueryLimits['documents'],
            $headers
        );
        $appendDashboardError('Documents', $documentsResponse);
        $documentsRows = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];
    }
    $dashboardMetrics['documents_for_verification'] = count($documentsRows);

    $payrollRunsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,generated_at,created_at' . $officeFilter . '&run_status=in.(draft,computed,approved)&order=created_at.desc&limit=' . $staffDashboardQueryLimits['payroll_runs'],
        $headers
    );
    $appendDashboardError('Payroll runs', $payrollRunsResponse);
    $payrollRunRows = isSuccessful($payrollRunsResponse) ? (array)($payrollRunsResponse['data'] ?? []) : [];
    $dashboardMetrics['payroll_tasks'] = count($payrollRunRows);

    if (!empty($scopePersonIds)) {
        $personFilter = '&person_id=in.' . rawurlencode('(' . implode(',', $scopePersonIds) . ')');

        $leaveResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/leave_requests?select=id,status,created_at,person:people(first_name,surname),leave_type:leave_types(leave_name)'
            . $personFilter
            . '&status=eq.pending&order=created_at.desc&limit=' . $staffDashboardQueryLimits['timekeeping_queue'],
            $headers
        );
        $appendDashboardError('Leave requests', $leaveResponse);
        $leaveRows = isSuccessful($leaveResponse) ? (array)($leaveResponse['data'] ?? []) : [];

        $overtimeResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/overtime_requests?select=id,status,created_at,person:people(first_name,surname)'
            . $personFilter
            . '&status=eq.pending&order=created_at.desc&limit=' . $staffDashboardQueryLimits['timekeeping_queue'],
            $headers
        );
        $appendDashboardError('Overtime requests', $overtimeResponse);
        $overtimeRows = isSuccessful($overtimeResponse) ? (array)($overtimeResponse['data'] ?? []) : [];

        $adjustmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/time_adjustment_requests?select=id,status,created_at,person:people(first_name,surname)'
            . $personFilter
            . '&status=eq.pending&order=created_at.desc&limit=' . $staffDashboardQueryLimits['timekeeping_queue'],
            $headers
        );
        $appendDashboardError('Time adjustments', $adjustmentResponse);
        $adjustmentRows = isSuccessful($adjustmentResponse) ? (array)($adjustmentResponse['data'] ?? []) : [];
    }

    $dashboardMetrics['pending_timekeeping'] = count($leaveRows) + count($overtimeRows) + count($adjustmentRows);
}

if ($dashboardLoadApprovals) {
    $approvalRows = [];

    foreach ($documentsRows as $row) {
        $owner = (array)($row['owner'] ?? []);
        $ownerName = trim(((string)($owner['first_name'] ?? '')) . ' ' . ((string)($owner['surname'] ?? '')));
        if ($ownerName === '') {
            $ownerName = 'Unknown Owner';
        }

        $approvalRows[] = [
            'request' => cleanText($row['title'] ?? null) ?: 'Document Submission',
            'owner' => $ownerName,
            'module' => 'Document Management',
            'status_label' => 'Submitted',
            'status_class' => 'bg-amber-100 text-amber-800',
            'created_at' => cleanText($row['updated_at'] ?? null),
            'action_url' => 'document-management.php?status=submitted',
        ];
    }

    foreach ($payrollRunRows as $row) {
        $runStatus = strtolower((string)($row['run_status'] ?? 'draft'));
        $approvalRows[] = [
            'request' => 'Payroll Run #' . strtoupper(substr((string)($row['id'] ?? ''), 0, 8)),
            'owner' => 'Payroll Queue',
            'module' => 'Payroll',
            'status_label' => ucwords(str_replace('_', ' ', $runStatus)),
            'status_class' => 'bg-indigo-100 text-indigo-800',
            'created_at' => cleanText($row['generated_at'] ?? null) ?: cleanText($row['created_at'] ?? null),
            'action_url' => 'payroll-management.php?status=pending',
        ];
    }

    foreach ($appRows as $row) {
        $status = strtolower((string)($row['application_status'] ?? 'submitted'));
        if (!in_array($status, $pendingApplicationStatuses, true)) {
            continue;
        }

        $approvalRows[] = [
            'request' => cleanText($row['job']['title'] ?? null) ?: 'Applicant Tracking Record',
            'owner' => cleanText($row['applicant']['full_name'] ?? null) ?: 'Applicant',
            'module' => 'Recruitment',
            'status_label' => ucwords(str_replace('_', ' ', $status)),
            'status_class' => 'bg-sky-100 text-sky-800',
            'created_at' => cleanText($row['updated_at'] ?? null) ?: cleanText($row['submitted_at'] ?? null),
            'action_url' => 'applicant-tracking.php?status=pending',
        ];
    }

    foreach ($leaveRows as $row) {
        $person = (array)($row['person'] ?? []);
        $ownerName = trim(((string)($person['first_name'] ?? '')) . ' ' . ((string)($person['surname'] ?? '')));
        if ($ownerName === '') {
            $ownerName = 'Unknown Employee';
        }

        $leaveType = cleanText($row['leave_type']['leave_name'] ?? null) ?: 'Leave';
        $approvalRows[] = [
            'request' => $leaveType . ' Request',
            'owner' => $ownerName,
            'module' => 'Timekeeping',
            'status_label' => 'Pending',
            'status_class' => 'bg-yellow-100 text-yellow-800',
            'created_at' => cleanText($row['created_at'] ?? null),
            'action_url' => 'timekeeping.php?tab=leave&status=pending',
        ];
    }

    foreach ($overtimeRows as $row) {
        $person = (array)($row['person'] ?? []);
        $ownerName = trim(((string)($person['first_name'] ?? '')) . ' ' . ((string)($person['surname'] ?? '')));
        if ($ownerName === '') {
            $ownerName = 'Unknown Employee';
        }

        $approvalRows[] = [
            'request' => 'Overtime Request',
            'owner' => $ownerName,
            'module' => 'Timekeeping',
            'status_label' => 'Pending',
            'status_class' => 'bg-yellow-100 text-yellow-800',
            'created_at' => cleanText($row['created_at'] ?? null),
            'action_url' => 'timekeeping.php?tab=overtime&status=pending',
        ];
    }

    foreach ($adjustmentRows as $row) {
        $person = (array)($row['person'] ?? []);
        $ownerName = trim(((string)($person['first_name'] ?? '')) . ' ' . ((string)($person['surname'] ?? '')));
        if ($ownerName === '') {
            $ownerName = 'Unknown Employee';
        }

        $approvalRows[] = [
            'request' => 'Time Adjustment Request',
            'owner' => $ownerName,
            'module' => 'Timekeeping',
            'status_label' => 'Pending',
            'status_class' => 'bg-yellow-100 text-yellow-800',
            'created_at' => cleanText($row['created_at'] ?? null),
            'action_url' => 'timekeeping.php?tab=adjustments&status=pending',
        ];
    }

    usort($approvalRows, static function (array $left, array $right): int {
        $leftTime = strtotime((string)($left['created_at'] ?? '')) ?: 0;
        $rightTime = strtotime((string)($right['created_at'] ?? '')) ?: 0;
        return $rightTime <=> $leftTime;
    });

    $dashboardPendingApprovalsTotal = count($approvalRows);
    $dashboardPendingApprovalsTotalPages = max(1, (int)ceil($dashboardPendingApprovalsTotal / $dashboardPendingApprovalsPerPage));
    if ($dashboardPendingApprovalsPage > $dashboardPendingApprovalsTotalPages) {
        $dashboardPendingApprovalsPage = $dashboardPendingApprovalsTotalPages;
    }

    $dashboardPendingApprovalsStart = ($dashboardPendingApprovalsPage - 1) * $dashboardPendingApprovalsPerPage;
    $dashboardPendingApprovals = array_slice($approvalRows, $dashboardPendingApprovalsStart, $dashboardPendingApprovalsPerPage);
}

if ($dashboardLoadNotifications) {
    $notificationsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,title,body,link_url,category,is_read,created_at&recipient_user_id=eq.' . rawurlencode($staffUserId) . '&order=created_at.desc&limit=' . $staffDashboardQueryLimits['notifications'],
        $headers
    );
    $appendDashboardError('Notifications', $notificationsResponse);
    $notificationRows = isSuccessful($notificationsResponse) ? (array)($notificationsResponse['data'] ?? []) : [];

    foreach ($notificationRows as $row) {
        $category = strtolower((string)($row['category'] ?? ''));
        if ($category === 'announcement') {
            continue;
        }

        $title = cleanText($row['title'] ?? null) ?: 'Notification';
        $body = cleanText($row['body'] ?? null) ?: 'No additional details provided.';
        $linkUrl = cleanText($row['link_url'] ?? null) ?: 'notifications.php';
        $createdAt = cleanText($row['created_at'] ?? null);
        $isRead = (bool)($row['is_read'] ?? false);

        $dashboardRoleNotifications[] = [
            'title' => $title,
            'body' => $body,
            'category' => $category !== '' ? ucwords(str_replace(['_', '-'], ' ', $category)) : 'Notification',
            'link_url' => $linkUrl,
            'meta' => formatDateTimeForPhilippines($createdAt, 'M d, Y · h:i A'),
            'status_label' => $isRead ? 'Read' : 'New',
            'status_class' => $isRead ? 'bg-slate-100 text-slate-700' : 'bg-blue-100 text-blue-700',
        ];
    }
}

if ($dashboardLoadAnnouncements) {
    $announcementsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/notifications?select=id,title,is_read,created_at&recipient_user_id=eq.' . rawurlencode($staffUserId) . '&category=eq.announcement&order=created_at.desc&limit=' . $staffDashboardQueryLimits['notifications'],
        $headers
    );
    $appendDashboardError('Announcements', $announcementsResponse);
    $announcementRows = isSuccessful($announcementsResponse) ? (array)($announcementsResponse['data'] ?? []) : [];

    foreach ($announcementRows as $row) {
        $title = cleanText($row['title'] ?? null) ?: 'Announcement';
        $createdAt = cleanText($row['created_at'] ?? null);
        $isRead = (bool)($row['is_read'] ?? false);

        $dashboardAnnouncements[] = [
            'title' => $title,
            'meta' => formatDateTimeForPhilippines($createdAt, 'M d, Y · h:i A'),
            'status_label' => $isRead ? 'Read' : 'New',
            'status_class' => $isRead ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-700',
        ];
    }
}
