<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;

$dashboardData = [
	'first_name' => 'Applicant',
	'active_applications' => 0,
	'open_jobs' => 0,
	'position_applied' => '-',
	'current_stage' => '-',
	'date_applied' => '-',
	'latest_update' => '-',
	'status_badge' => 'No active application',
];

$dashboardRecentNotifications = [];
$dashboardProgressItems = [];
$latestApplicationStatus = 'submitted';
$latestApplicationId = null;

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$sessionName = trim((string)($_SESSION['user']['name'] ?? ''));
if ($sessionName !== '') {
	$nameParts = preg_split('/\s+/', $sessionName) ?: [];
	$dashboardData['first_name'] = (string)($nameParts[0] ?? 'Applicant');
}

$applicantProfileResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$applicantProfileId = '';
if (isSuccessful($applicantProfileResponse) && !empty((array)($applicantProfileResponse['data'] ?? []))) {
	$profileRow = (array)$applicantProfileResponse['data'][0];
	$applicantProfileId = (string)($profileRow['id'] ?? '');

	$fullName = trim((string)($profileRow['full_name'] ?? ''));
	if ($fullName !== '') {
		$nameParts = preg_split('/\s+/', $fullName) ?: [];
		$dashboardData['first_name'] = (string)($nameParts[0] ?? $dashboardData['first_name']);
	}
}

$today = date('Y-m-d');
$openJobsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/job_postings?select=id&posting_status=eq.published&open_date=lte.' . $today
	. '&close_date=gte.' . $today
	. '&limit=5000',
	$headers
);

if (isSuccessful($openJobsResponse)) {
	$dashboardData['open_jobs'] = count((array)($openJobsResponse['data'] ?? []));
}

if ($applicantProfileId !== '') {
	$applicationsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/applications?select=id,application_status,submitted_at,updated_at,job:job_postings(title)&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
		. '&order=submitted_at.desc&limit=100',
		$headers
	);

	if (isSuccessful($applicationsResponse)) {
		$applicationRows = (array)($applicationsResponse['data'] ?? []);

		$activeCount = 0;
		foreach ($applicationRows as $applicationRow) {
			$status = strtolower((string)($applicationRow['application_status'] ?? 'submitted'));
			if (!in_array($status, ['hired', 'rejected', 'withdrawn'], true)) {
				$activeCount++;
			}
		}

		$dashboardData['active_applications'] = $activeCount;

		if (!empty($applicationRows)) {
			$latestApplication = (array)$applicationRows[0];
			$latestApplicationId = cleanText($latestApplication['id'] ?? null);
			$latestStatus = strtolower((string)($latestApplication['application_status'] ?? 'submitted'));
			$latestApplicationStatus = $latestStatus;
			$statusLabelMap = [
				'submitted' => 'Submitted',
				'screening' => 'Qualification Review',
				'shortlisted' => 'Shortlisted',
				'interview' => 'Interview Stage',
				'offer' => 'Offer Stage',
				'hired' => 'Hired',
				'rejected' => 'Not Selected',
				'withdrawn' => 'Withdrawn',
			];

			$dashboardData['position_applied'] = (string)($latestApplication['job']['title'] ?? '-');
			$dashboardData['current_stage'] = (string)($statusLabelMap[$latestStatus] ?? ucwords($latestStatus));
			$dashboardData['status_badge'] = 'Current status: ' . $dashboardData['current_stage'];

			$submittedAt = cleanText($latestApplication['submitted_at'] ?? null);
			if ($submittedAt !== null) {
				$dashboardData['date_applied'] = date('M j, Y', strtotime($submittedAt));
			}

			$updatedAt = cleanText($latestApplication['updated_at'] ?? null);
			if ($updatedAt !== null) {
				$dashboardData['latest_update'] = date('M j, Y', strtotime($updatedAt));
			}
		}
	}
}

$statusLabelMap = [
	'submitted' => 'Application Submitted',
	'screening' => 'Document & Qualification Review',
	'shortlisted' => 'Shortlisted',
	'interview' => 'Interview Stage',
	'offer' => 'Offer Stage',
	'hired' => 'Hiring Completed',
	'rejected' => 'Application Not Successful',
	'withdrawn' => 'Application Withdrawn',
];

if ($latestApplicationId !== null && isValidUuid($latestApplicationId)) {
	$historyResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_status_history?select=id,new_status,notes,created_at'
		. '&application_id=eq.' . rawurlencode($latestApplicationId)
		. '&order=created_at.asc&limit=200',
		$headers
	);

	if (isSuccessful($historyResponse) && !empty((array)($historyResponse['data'] ?? []))) {
		$historyRows = (array)($historyResponse['data'] ?? []);
		$lastHistoryIndex = count($historyRows) - 1;

		foreach ($historyRows as $index => $historyRow) {
			$statusKey = strtolower((string)($historyRow['new_status'] ?? 'submitted'));
			$dashboardProgressItems[] = [
				'title' => (string)($statusLabelMap[$statusKey] ?? ucwords(str_replace('_', ' ', $statusKey))),
				'state' => $index === $lastHistoryIndex ? 'current' : 'completed',
				'notes' => cleanText($historyRow['notes'] ?? null),
				'created_at' => cleanText($historyRow['created_at'] ?? null),
			];
		}
	}
}

if (empty($dashboardProgressItems)) {
	$dashboardProgressItems[] = [
		'title' => (string)($statusLabelMap[$latestApplicationStatus] ?? ucwords(str_replace('_', ' ', $latestApplicationStatus))),
		'state' => 'current',
		'notes' => null,
		'created_at' => null,
	];
}

$notificationsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/notifications?select=id,title,body,link_url,created_at,category'
	. '&recipient_user_id=eq.' . rawurlencode($applicantUserId)
	. '&order=created_at.desc&limit=3',
	$headers
);

if (isSuccessful($notificationsResponse)) {
	foreach ((array)($notificationsResponse['data'] ?? []) as $notificationRow) {
		$dashboardRecentNotifications[] = [
			'title' => (string)($notificationRow['title'] ?? 'Update'),
			'body' => (string)($notificationRow['body'] ?? ''),
			'created_at' => cleanText($notificationRow['created_at'] ?? null),
			'link_url' => safeLocalLink(cleanText($notificationRow['link_url'] ?? null)),
			'category' => strtolower((string)($notificationRow['category'] ?? 'system')),
		];
	}
}
