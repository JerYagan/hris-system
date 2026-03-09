<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$selectedApplicationId = cleanText($_GET['application_id'] ?? null);
$selectedApplicationId = isValidUuid($selectedApplicationId) ? $selectedApplicationId : null;
$isFilterEmpty = false;

$applications = [];
$selectedApplication = null;
$feedbackRecord = null;

$applicationStatus = 'pending';
$applicationStatusLabel = 'Pending Review';
$decisionTitle = 'Decision Pending';
$decisionMessage = 'Your application is still under final review by HR.';
$decisionBody = 'Thank you for your patience. A final decision will be posted in your applicant portal soon.';
$decisionPanelClass = 'border-yellow-200 bg-yellow-50';
$decisionIcon = 'hourglass_top';
$remarks = 'Decision will appear here once finalized.';

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
	$dataLoadError = 'Applicant profile was not found. Please complete your profile first.';
	return;
}

$applicantProfileId = (string)($applicantProfileResponse['data'][0]['id'] ?? '');
if ($applicantProfileId === '') {
	$dataLoadError = 'Applicant profile context could not be resolved.';
	return;
}

$applicationSelect = 'id,application_ref_no,application_status,submitted_at,updated_at,job_posting_id,job:job_postings(id,title,office:offices(office_name))';
$applicationsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/applications?select=' . rawurlencode($applicationSelect)
	. '&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
	. '&order=submitted_at.desc&limit=200',
	$headers
);

if (!isSuccessful($applicationsResponse)) {
	$dataLoadError = 'Failed to load your applications for feedback.';
	return;
}

foreach ((array)($applicationsResponse['data'] ?? []) as $applicationRow) {
	$jobRow = (array)($applicationRow['job'] ?? []);
	$officeRow = (array)($jobRow['office'] ?? []);

	$applications[] = [
		'id' => (string)($applicationRow['id'] ?? ''),
		'reference_no' => (string)($applicationRow['application_ref_no'] ?? '-'),
		'status' => strtolower((string)($applicationRow['application_status'] ?? 'submitted')),
		'submitted_at' => cleanText($applicationRow['submitted_at'] ?? null),
		'job_title' => (string)($jobRow['title'] ?? 'Untitled Position'),
		'office_name' => (string)($officeRow['office_name'] ?? 'Division not specified'),
	];
}

if (!empty($applications)) {
	if ($selectedApplicationId !== null) {
		$isFilterEmpty = true;
		foreach ($applications as $applicationItem) {
			if ((string)$applicationItem['id'] === $selectedApplicationId) {
				$selectedApplication = $applicationItem;
				$isFilterEmpty = false;
				break;
			}
		}
	}

	if ($selectedApplication === null) {
		$selectedApplication = $applications[0];
	}
}

if ($selectedApplication !== null) {
	$feedbackResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at'
		. '&application_id=eq.' . rawurlencode((string)$selectedApplication['id'])
		. '&order=provided_at.desc'
		. '&limit=1',
		$headers
	);

	if (isSuccessful($feedbackResponse) && !empty((array)($feedbackResponse['data'] ?? []))) {
		$feedbackRecord = (array)$feedbackResponse['data'][0];
	}
}

$statusConfig = static function (string $statusKey): array {
	return match ($statusKey) {
		'hired' => [
			'label' => 'Hired',
			'title' => 'You Are Hired',
			'message' => 'Your application has been approved for hiring.',
			'body' => 'Please monitor your notifications and email for onboarding instructions and next steps.',
			'panel' => 'border-green-200 bg-green-50',
			'icon' => 'badge',
		],
		'offer' => [
			'label' => 'For Approval',
			'title' => 'Application Advanced',
			'message' => 'Your application has reached the offer or final approval stage.',
			'body' => 'Please keep monitoring your notifications for final instructions and schedule updates.',
			'panel' => 'border-emerald-200 bg-emerald-50',
			'icon' => 'task_alt',
		],
		'shortlisted' => [
			'label' => 'Shortlisted',
			'title' => 'Application Shortlisted',
			'message' => 'Your application passed screening and moved to the next recruitment stage.',
			'body' => 'Please monitor your notifications for interview schedules and further instructions.',
			'panel' => 'border-emerald-200 bg-emerald-50',
			'icon' => 'task_alt',
		],
		'interview' => [
			'label' => 'Interview',
			'title' => 'Interview Stage Ongoing',
			'message' => 'Your application is currently in the interview stage.',
			'body' => 'Please review your interview schedule details and monitor your notifications for updates.',
			'panel' => 'border-sky-200 bg-sky-50',
			'icon' => 'event',
		],
		'screening' => [
			'label' => 'Screening',
			'title' => 'Application Under Screening',
			'message' => 'Your application is being reviewed by the recruitment team.',
			'body' => 'Please wait for further updates. Additional instructions will appear here once posted.',
			'panel' => 'border-amber-200 bg-amber-50',
			'icon' => 'hourglass_top',
		],
		'submitted' => [
			'label' => 'Submitted',
			'title' => 'Application Submitted',
			'message' => 'Your application was received and is waiting for screening.',
			'body' => 'Thank you for applying. Please monitor this page and your notifications for updates.',
			'panel' => 'border-blue-200 bg-blue-50',
			'icon' => 'outgoing_mail',
		],
		'rejected' => [
			'label' => 'Rejected',
			'title' => 'Application Not Successful',
			'message' => 'Your application was not selected.',
			'body' => 'Thank you for your interest. We encourage you to apply for future opportunities that match your qualifications.',
			'panel' => 'border-red-200 bg-red-50',
			'icon' => 'cancel',
		],
		'withdrawn' => [
			'label' => 'Withdrawn',
			'title' => 'Application Withdrawn',
			'message' => 'This application has been withdrawn and is now closed.',
			'body' => 'No further action is required for this application unless you apply again to a new posting.',
			'panel' => 'border-slate-200 bg-slate-50',
			'icon' => 'cancel',
		],
		default => [
			'label' => 'Pending Review',
			'title' => 'Decision Pending',
			'message' => 'Your application is still under final review by HR.',
			'body' => 'Thank you for your patience. A final decision will be posted in your applicant portal soon.',
			'panel' => 'border-yellow-200 bg-yellow-50',
			'icon' => 'hourglass_top',
		],
	};
};

$selectedStatus = strtolower((string)($selectedApplication['status'] ?? 'pending'));
$selectedDecision = strtolower((string)($feedbackRecord['decision'] ?? ''));

if ($feedbackRecord !== null) {
	$remarks = cleanText($feedbackRecord['feedback_text'] ?? null) ?? 'No additional remarks were provided.';
}

if ($selectedDecision === 'on_hold' && in_array($selectedStatus, ['submitted', 'screening'], true)) {
	$applicationStatus = 'on_hold';
	$applicationStatusLabel = 'Return for Compliance';
	$decisionTitle = 'Additional Compliance Required';
	$decisionMessage = 'Your application needs additional compliance before screening can continue.';
	$decisionBody = 'Please review the remarks below and comply with the requested requirements to proceed.';
	$decisionPanelClass = 'border-amber-200 bg-amber-50';
	$decisionIcon = 'assignment_late';
} else {
	$statusPresentation = $statusConfig($selectedStatus);
	$applicationStatus = $selectedStatus;
	$applicationStatusLabel = (string)($statusPresentation['label'] ?? 'Pending Review');
	$decisionTitle = (string)($statusPresentation['title'] ?? $decisionTitle);
	$decisionMessage = (string)($statusPresentation['message'] ?? $decisionMessage);
	$decisionBody = (string)($statusPresentation['body'] ?? $decisionBody);
	$decisionPanelClass = (string)($statusPresentation['panel'] ?? $decisionPanelClass);
	$decisionIcon = (string)($statusPresentation['icon'] ?? $decisionIcon);

	if ($feedbackRecord === null && in_array($selectedStatus, ['shortlisted', 'interview', 'offer', 'hired'], true)) {
		$remarks = 'Final written feedback has not yet been posted. Please monitor this page and your notifications for updates.';
	} elseif ($feedbackRecord === null && in_array($selectedStatus, ['rejected', 'withdrawn'], true)) {
		$remarks = 'No additional feedback was posted for this application.';
	}
}
