<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$jobId = cleanText($_GET['job_id'] ?? null);
$today = date('Y-m-d');

$dataLoadError = null;
$jobNotFound = false;
$isDeadlinePassed = false;
$alreadyApplied = false;
$canApply = false;

$jobData = [
	'id' => '',
	'title' => 'Job posting not found',
	'office_name' => 'N/A',
	'employment_type' => '-',
	'salary_grade' => '-',
	'plantilla_item_no' => '-',
	'csc_reference_url' => null,
	'deadline_display' => '-',
	'close_date' => null,
	'open_date' => null,
	'description' => '',
	'qualifications' => '',
	'responsibilities' => '',
	'reference_code' => '-',
	'required_documents' => defaultRequiredDocumentConfig(),
];

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	$jobNotFound = true;
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	$jobNotFound = true;
	return;
}

if ($jobId === null) {
	$jobNotFound = true;
	$dataLoadError = 'Missing job reference.';
	return;
}

if (!isValidUuid($jobId)) {
	$jobNotFound = true;
	$dataLoadError = 'Invalid job reference.';
	return;
}

$jobSelect = 'id,title,description,qualifications,responsibilities,open_date,close_date,posting_status,plantilla_item_no,csc_reference_url,required_documents,office:offices(id,office_name),position:job_positions(id,position_title,employment_classification,salary_grade)';
$jobResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/job_postings?select=' . rawurlencode($jobSelect)
	. '&id=eq.' . rawurlencode($jobId)
	. '&posting_status=eq.published'
	. '&limit=1',
	$headers
);

if (!isSuccessful($jobResponse)) {
	$fallbackSelect = 'id,title,description,qualifications,responsibilities,open_date,close_date,posting_status,office:offices(id,office_name),position:job_positions(id,position_title,employment_classification,salary_grade)';
	$jobResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/job_postings?select=' . rawurlencode($fallbackSelect)
		. '&id=eq.' . rawurlencode($jobId)
		. '&posting_status=eq.published'
		. '&limit=1',
		$headers
	);
}

if (!isSuccessful($jobResponse)) {
	$jobNotFound = true;
	$dataLoadError = 'Failed to load job details.';
	return;
}

$jobRow = (array)($jobResponse['data'][0] ?? []);
if (empty($jobRow)) {
	$jobNotFound = true;
	$dataLoadError = 'The requested job is not available.';
	return;
}

$position = (array)($jobRow['position'] ?? []);
$office = (array)($jobRow['office'] ?? []);
$closeDate = cleanText($jobRow['close_date'] ?? null);
$openDate = cleanText($jobRow['open_date'] ?? null);

$jobData = [
	'id' => (string)($jobRow['id'] ?? ''),
	'title' => (string)($jobRow['title'] ?? 'Untitled Position'),
	'office_name' => (string)($office['office_name'] ?? 'Office not specified'),
	'employment_type' => (string)($position['employment_classification'] ?? '-'),
	'salary_grade' => cleanText($position['salary_grade'] ?? null) ?? '-',
	'plantilla_item_no' => cleanText($jobRow['plantilla_item_no'] ?? null) ?? '-',
	'csc_reference_url' => cleanText($jobRow['csc_reference_url'] ?? null),
	'deadline_display' => $closeDate ? date('F j, Y', strtotime($closeDate)) : '-',
	'close_date' => $closeDate,
	'open_date' => $openDate,
	'description' => (string)($jobRow['description'] ?? ''),
	'qualifications' => (string)($jobRow['qualifications'] ?? ''),
	'responsibilities' => (string)($jobRow['responsibilities'] ?? ''),
	'reference_code' => strtoupper(substr(str_replace('-', '', (string)($jobRow['id'] ?? '')), 0, 10)),
	'required_documents' => normalizeRequiredDocumentConfig($jobRow['required_documents'] ?? null),
];

if ($closeDate !== null) {
	$isDeadlinePassed = $closeDate < $today;
}

$applicantProfileResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$applicantProfileId = '';
if (isSuccessful($applicantProfileResponse)) {
	$applicantProfileId = (string)(($applicantProfileResponse['data'][0]['id'] ?? ''));
}

if ($applicantProfileId !== '') {
	$applicationCheckResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/applications?select=id&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
		. '&job_posting_id=eq.' . rawurlencode((string)$jobData['id'])
		. '&limit=1',
		$headers
	);

	if (isSuccessful($applicationCheckResponse)) {
		$alreadyApplied = !empty((array)($applicationCheckResponse['data'] ?? []));
	}
}

$canApply = !$jobNotFound && !$isDeadlinePassed && !$alreadyApplied;

$qualificationList = [];
if ($jobData['qualifications'] !== '') {
	$qualificationList = preg_split('/\r\n|\r|\n|;/', (string)$jobData['qualifications']) ?: [];
}

$responsibilityList = [];
if ($jobData['responsibilities'] !== '') {
	$responsibilityList = preg_split('/\r\n|\r|\n|;/', (string)$jobData['responsibilities']) ?: [];
}

$qualificationList = array_values(array_filter(array_map(static fn($item) => trim((string)$item), $qualificationList)));
$responsibilityList = array_values(array_filter(array_map(static fn($item) => trim((string)$item), $responsibilityList)));

if (empty($qualificationList) && $jobData['qualifications'] !== '') {
	$qualificationList = [(string)$jobData['qualifications']];
}

if (empty($responsibilityList) && $jobData['responsibilities'] !== '') {
	$responsibilityList = [(string)$jobData['responsibilities']];
}

$daysRemaining = null;
if (!empty($jobData['close_date'])) {
	$daysRemaining = (int)floor((strtotime((string)$jobData['close_date'] . ' 23:59:59') - time()) / 86400);
}
