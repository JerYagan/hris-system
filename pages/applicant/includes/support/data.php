<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$recentSupportInquiries = [];

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$supportHistoryResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/activity_logs?select=id,action_name,new_data,created_at'
	. '&actor_user_id=eq.' . rawurlencode($applicantUserId)
	. '&module_name=eq.applicant_support'
	. '&action_name=eq.submit_support'
	. '&order=created_at.desc&limit=5',
	$headers
);

if (isSuccessful($supportHistoryResponse)) {
	foreach ((array)($supportHistoryResponse['data'] ?? []) as $historyRow) {
		$newData = (array)($historyRow['new_data'] ?? []);
		$recentSupportInquiries[] = [
			'id' => (string)($historyRow['id'] ?? ''),
			'subject' => (string)($newData['subject'] ?? 'Support Inquiry'),
			'message' => (string)($newData['message'] ?? ''),
			'created_at' => cleanText($historyRow['created_at'] ?? null),
		];
	}
} else {
	$dataLoadError = 'Unable to load support inquiry history right now.';
}
