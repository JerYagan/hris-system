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
		'office_name' => (string)($officeRow['office_name'] ?? 'Office not specified'),
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
		. '&limit=1',
		$headers
	);

	if (isSuccessful($feedbackResponse) && !empty((array)($feedbackResponse['data'] ?? []))) {
		$feedbackRecord = (array)$feedbackResponse['data'][0];
	}
}

if ($feedbackRecord !== null) {
	$decision = strtolower((string)($feedbackRecord['decision'] ?? ''));
	$remarks = cleanText($feedbackRecord['feedback_text'] ?? null) ?? 'No additional remarks were provided.';

	if (in_array($decision, ['for_next_step', 'hired'], true)) {
		$applicationStatus = 'accepted';
		$decisionTitle = 'Congratulations!';
		$decisionMessage = 'You have been accepted for the next stage of recruitment.';
		$decisionBody = 'Please monitor your notifications for onboarding instructions and schedule details.';
		$decisionPanelClass = 'border-green-200 bg-green-50';
		$decisionIcon = 'task_alt';
	} elseif ($decision === 'rejected') {
		$applicationStatus = 'rejected';
		$decisionTitle = 'Application Not Successful';
		$decisionMessage = 'We regret to inform you that your application was not selected.';
		$decisionBody = 'Thank you for your interest. We encourage you to apply for future opportunities that match your qualifications.';
		$decisionPanelClass = 'border-red-200 bg-red-50';
		$decisionIcon = 'cancel';
	} else {
		$applicationStatus = 'pending';
		$decisionTitle = 'Decision Pending';
		$decisionMessage = 'Your application is still under final review by HR.';
		$decisionBody = 'Thank you for your patience. A final decision will be posted in your applicant portal soon.';
		$decisionPanelClass = 'border-yellow-200 bg-yellow-50';
		$decisionIcon = 'hourglass_top';
	}
} elseif ($selectedApplication !== null) {
	$status = strtolower((string)($selectedApplication['status'] ?? 'submitted'));
	if (in_array($status, ['hired', 'offer', 'shortlisted', 'interview'], true)) {
		$applicationStatus = 'accepted';
		$decisionTitle = 'Application In Progress';
		$decisionMessage = 'Your application has moved forward in the recruitment process.';
		$decisionBody = 'Final written feedback has not yet been posted. Please monitor this page and your notifications.';
		$decisionPanelClass = 'border-green-200 bg-green-50';
		$decisionIcon = 'task_alt';
	} elseif (in_array($status, ['rejected', 'withdrawn'], true)) {
		$applicationStatus = 'rejected';
		$decisionTitle = 'Application Closed';
		$decisionMessage = 'This application is now closed.';
		$decisionBody = 'No additional feedback was posted for this application.';
		$decisionPanelClass = 'border-red-200 bg-red-50';
		$decisionIcon = 'cancel';
	}
}
