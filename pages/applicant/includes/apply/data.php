<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$jobNotAvailable = false;

$jobId = cleanText($_GET['job_id'] ?? $_POST['job_id'] ?? null);
$today = date('Y-m-d');

$jobData = [
	'id' => '',
	'title' => '-',
	'office_name' => '-',
	'employment_type' => '-',
	'plantilla_item_no' => '-',
	'csc_reference_url' => null,
	'close_date' => null,
	'deadline_display' => '-',
];

$applicantProfile = [
	'id' => '',
	'full_name' => (string)($_SESSION['user']['name'] ?? 'Applicant User'),
	'email' => (string)($_SESSION['user']['email'] ?? '-'),
];

$educationOptions = [
	'High School Graduate',
	'Senior High School Graduate',
	'College Level',
	'College Graduate',
	'Post Graduate',
];

$educationFormDefaults = [
	'education_attainment' => '',
	'course_strand' => '',
	'school_institution' => '',
];

$requiredDocumentConfig = defaultRequiredDocumentConfig();
$pdsReferenceSheetUrl = 'https://csc.gov.ph/career/job/4897591';

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
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name,email&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

if (isSuccessful($applicantProfileResponse) && !empty((array)($applicantProfileResponse['data'] ?? []))) {
	$profileRow = (array)$applicantProfileResponse['data'][0];
	$applicantProfile['id'] = (string)($profileRow['id'] ?? '');
	$applicantProfile['full_name'] = (string)($profileRow['full_name'] ?? $applicantProfile['full_name']);
	$applicantProfile['email'] = (string)($profileRow['email'] ?? $applicantProfile['email']);
}

$personResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$personId = isSuccessful($personResponse)
	? cleanText($personResponse['data'][0]['id'] ?? null)
	: null;

if ($personId !== null && isValidUuid($personId)) {
	$educationResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/person_educations?select=education_level,school_name,course_degree,year_graduated,sequence_no'
		. '&person_id=eq.' . rawurlencode($personId)
		. '&order=sequence_no.asc&limit=1',
		$headers
	);

	if (isSuccessful($educationResponse) && !empty((array)($educationResponse['data'] ?? []))) {
		$educationRow = (array)$educationResponse['data'][0];
		$level = strtolower((string)($educationRow['education_level'] ?? ''));
		$levelLabelMap = [
			'elementary' => 'High School Graduate',
			'secondary' => 'Senior High School Graduate',
			'vocational' => 'College Level',
			'college' => 'College Graduate',
			'graduate' => 'Post Graduate',
		];

		$educationFormDefaults['education_attainment'] = (string)($levelLabelMap[$level] ?? '');
		$educationFormDefaults['course_strand'] = (string)(cleanText($educationRow['course_degree'] ?? null) ?? '');
		$educationFormDefaults['school_institution'] = (string)(cleanText($educationRow['school_name'] ?? null) ?? '');
	}
}

if ($jobId === null) {
	$jobNotAvailable = true;
	$dataLoadError = 'No job was selected. Please choose a posting from job listings.';
	return;
}

if (!isValidUuid($jobId)) {
	$jobNotAvailable = true;
	$dataLoadError = 'Invalid job reference provided.';
	return;
}


$jobQueryWithPhase17Fields = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/job_postings?select=id,title,open_date,close_date,posting_status,plantilla_item_no,csc_reference_url,required_documents,office:offices(office_name),position:job_positions(employment_classification)'
	. '&id=eq.' . rawurlencode($jobId)
	. '&posting_status=eq.published'
	. '&open_date=lte.' . $today
	. '&close_date=gte.' . $today
	. '&limit=1',
	$headers
);

$jobResponse = $jobQueryWithPhase17Fields;

if (!isSuccessful($jobResponse)) {
	$jobResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/job_postings?select=id,title,open_date,close_date,posting_status,office:offices(office_name),position:job_positions(employment_classification)'
		. '&id=eq.' . rawurlencode($jobId)
		. '&posting_status=eq.published'
		. '&open_date=lte.' . $today
		. '&close_date=gte.' . $today
		. '&limit=1',
		$headers
	);
}

if (!isSuccessful($jobResponse) || empty((array)($jobResponse['data'] ?? []))) {
	$jobNotAvailable = true;
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'This posting is unavailable or no longer accepting applications.');
	return;
}

$jobRow = (array)$jobResponse['data'][0];
$jobData['id'] = (string)($jobRow['id'] ?? '');
$jobData['title'] = (string)($jobRow['title'] ?? '-');
$jobData['office_name'] = (string)($jobRow['office']['office_name'] ?? '-');
$jobData['employment_type'] = (string)($jobRow['position']['employment_classification'] ?? '-');
$jobData['plantilla_item_no'] = cleanText($jobRow['plantilla_item_no'] ?? null) ?? '-';
$jobData['csc_reference_url'] = cleanText($jobRow['csc_reference_url'] ?? null);
$requiredDocumentConfig = normalizeRequiredDocumentConfig($jobRow['required_documents'] ?? null);
$jobData['close_date'] = cleanText($jobRow['close_date'] ?? null);
$jobData['deadline_display'] = $jobData['close_date'] ? date('F j, Y', strtotime((string)$jobData['close_date'])) : '-';

$existingApplicationResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/applications?select=id,application_ref_no'
	. '&applicant_profile_id=eq.' . rawurlencode((string)$applicantProfile['id'])
	. '&job_posting_id=eq.' . rawurlencode((string)$jobData['id'])
	. '&limit=1',
	$headers
);

$existingApplication = null;
if (isSuccessful($existingApplicationResponse) && !empty((array)($existingApplicationResponse['data'] ?? []))) {
	$existingApplication = (array)$existingApplicationResponse['data'][0];
}
