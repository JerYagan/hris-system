<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$selectedApplicationId = cleanText($_GET['application_id'] ?? null);
$selectedApplicationId = isValidUuid($selectedApplicationId) ? $selectedApplicationId : null;
$statusFilter = strtolower((string)(cleanText($_GET['status'] ?? null) ?? 'all'));

$applications = [];
$selectedApplication = null;
$applicationTimeline = [];

$applicationStats = [
	'total' => 0,
	'submitted' => 0,
	'in_progress' => 0,
	'finalized' => 0,
];

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$applicantProfileResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

if (!isSuccessful($applicantProfileResponse) || empty((array)($applicantProfileResponse['data'] ?? []))) {
	$dataLoadError = 'Applicant profile was not found. Please update your profile first.';
	return;
}

$applicantProfileId = (string)($applicantProfileResponse['data'][0]['id'] ?? '');
if ($applicantProfileId === '') {
	$dataLoadError = 'Applicant profile context could not be resolved.';
	return;
}

$applicationSelect = 'id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,job:job_postings(id,title,close_date,office:offices(office_name))';
$applicationsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/applications?select=' . rawurlencode($applicationSelect)
	. '&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
	. '&order=submitted_at.desc&limit=200',
	$headers
);

if (!isSuccessful($applicationsResponse)) {
	$dataLoadError = 'Failed to load your applications.';
	return;
}

$feedbackByApplication = [];
$applicationIds = [];
foreach ((array)($applicationsResponse['data'] ?? []) as $applicationRow) {
	$applicationId = cleanText($applicationRow['id'] ?? null);
	if (isValidUuid($applicationId)) {
		$applicationIds[] = $applicationId;
	}
}

if (!empty($applicationIds)) {
	$feedbackResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at'
		. '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
		. '&limit=200',
		$headers
	);

	if (isSuccessful($feedbackResponse)) {
		foreach ((array)($feedbackResponse['data'] ?? []) as $feedbackRow) {
			$feedbackApplicationId = (string)($feedbackRow['application_id'] ?? '');
			if ($feedbackApplicationId !== '') {
				$feedbackByApplication[$feedbackApplicationId] = (array)$feedbackRow;
			}
		}
	}
}

$rawApplications = (array)($applicationsResponse['data'] ?? []);
foreach ($rawApplications as $row) {
	$applicationId = (string)($row['id'] ?? '');
	$applicationStatus = strtolower((string)($row['application_status'] ?? 'submitted'));
	$jobRow = (array)($row['job'] ?? []);
	$officeRow = (array)($jobRow['office'] ?? []);

	$isFinalized = in_array($applicationStatus, ['hired', 'rejected', 'withdrawn'], true);
	$isSubmitted = $applicationStatus === 'submitted';

	$applicationStats['total']++;
	if ($isSubmitted) {
		$applicationStats['submitted']++;
	}
	if ($isFinalized) {
		$applicationStats['finalized']++;
	} else {
		$applicationStats['in_progress']++;
	}

	if ($statusFilter !== 'all') {
		if ($statusFilter === 'in_progress' && $isFinalized) {
			continue;
		}
		if ($statusFilter === 'finalized' && !$isFinalized) {
			continue;
		}
		if ($statusFilter !== 'in_progress' && $statusFilter !== 'finalized' && $applicationStatus !== $statusFilter) {
			continue;
		}
	}

	$applications[] = [
		'id' => $applicationId,
		'reference_no' => (string)($row['application_ref_no'] ?? '-'),
		'status' => $applicationStatus,
		'submitted_at' => cleanText($row['submitted_at'] ?? null),
		'updated_at' => cleanText($row['updated_at'] ?? null),
		'job_id' => (string)($row['job_posting_id'] ?? ''),
		'job_title' => (string)($jobRow['title'] ?? 'Untitled Position'),
		'office_name' => (string)($officeRow['office_name'] ?? 'Office not specified'),
		'close_date' => cleanText($jobRow['close_date'] ?? null),
		'has_feedback' => isset($feedbackByApplication[$applicationId]),
		'feedback_decision' => (string)($feedbackByApplication[$applicationId]['decision'] ?? ''),
	];
}

if (!empty($applications)) {
	if ($selectedApplicationId !== null) {
		foreach ($applications as $applicationRow) {
			if ((string)$applicationRow['id'] === $selectedApplicationId) {
				$selectedApplication = $applicationRow;
				break;
			}
		}
	}

	if ($selectedApplication === null) {
		$selectedApplication = $applications[0];
	}
}

if ($selectedApplication !== null) {
	$historyResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_status_history?select=id,old_status,new_status,notes,created_at'
		. '&application_id=eq.' . rawurlencode((string)$selectedApplication['id'])
		. '&order=created_at.asc&limit=200',
		$headers
	);

	if (isSuccessful($historyResponse)) {
		foreach ((array)($historyResponse['data'] ?? []) as $historyRow) {
			$applicationTimeline[] = [
				'id' => (string)($historyRow['id'] ?? ''),
				'status' => strtolower((string)($historyRow['new_status'] ?? 'submitted')),
				'notes' => cleanText($historyRow['notes'] ?? null) ?? 'Status updated.',
				'created_at' => cleanText($historyRow['created_at'] ?? null),
			];
		}
	}

	if (empty($applicationTimeline)) {
		$applicationTimeline[] = [
			'id' => 'submitted-' . (string)$selectedApplication['id'],
			'status' => strtolower((string)($selectedApplication['status'] ?? 'submitted')),
			'notes' => 'Application submitted by applicant.',
			'created_at' => cleanText($selectedApplication['submitted_at'] ?? null),
		];
	}
}

$statusMeta = [
	'submitted' => ['label' => 'Submitted', 'badge' => 'bg-blue-100 text-blue-700', 'timeline' => 'bg-blue-700 text-white'],
	'screening' => ['label' => 'Screening', 'badge' => 'bg-yellow-100 text-yellow-700', 'timeline' => 'bg-yellow-500 text-white'],
	'shortlisted' => ['label' => 'Shortlisted', 'badge' => 'bg-indigo-100 text-indigo-700', 'timeline' => 'bg-indigo-600 text-white'],
	'interview' => ['label' => 'Interview', 'badge' => 'bg-purple-100 text-purple-700', 'timeline' => 'bg-purple-600 text-white'],
	'offer' => ['label' => 'Offer', 'badge' => 'bg-emerald-100 text-emerald-700', 'timeline' => 'bg-emerald-600 text-white'],
	'hired' => ['label' => 'Hired', 'badge' => 'bg-green-100 text-green-700', 'timeline' => 'bg-green-700 text-white'],
	'rejected' => ['label' => 'Rejected', 'badge' => 'bg-rose-100 text-rose-700', 'timeline' => 'bg-rose-600 text-white'],
	'withdrawn' => ['label' => 'Withdrawn', 'badge' => 'bg-gray-100 text-gray-700', 'timeline' => 'bg-gray-500 text-white'],
];
