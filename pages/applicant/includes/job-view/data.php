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
$requiredDocumentChecklist = [];
$requiredDocumentSummary = [
	'total_required' => 0,
	'fulfilled_required' => 0,
	'missing_required' => 0,
];

$jobData = [
	'id' => '',
	'title' => 'Job posting not found',
	'office_name' => 'N/A',
	'employment_type' => '-',
	'employment_type_label' => '-',
	'work_location' => '-',
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
	'criteria' => [
		'eligibility_required' => false,
		'eligibility_label' => 'None (Not Required)',
		'minimum_education_years' => 2,
		'minimum_training_hours' => 4,
		'minimum_experience_years' => 1,
	],
];

$criteriaGapMessage = null;

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
	'office_name' => (string)($office['office_name'] ?? 'Division not specified'),
	'employment_type' => (string)($position['employment_classification'] ?? '-'),
	'employment_type_label' => '-',
	'work_location' => '-',
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
	'criteria' => $jobData['criteria'],
];

$employmentTypeRaw = strtolower(trim((string)$jobData['employment_type']));
$jobData['employment_type_label'] = match ($employmentTypeRaw) {
	'regular', 'coterminous' => 'Full-time',
	'contractual', 'casual', 'job_order' => 'Contract',
	default => $jobData['employment_type'] !== '-' ? ucwords(str_replace('_', ' ', (string)$jobData['employment_type'])) : '-',
};

$descriptionLower = strtolower((string)$jobData['description']);
if (str_contains($descriptionLower, 'hybrid')) {
	$jobData['work_location'] = 'Hybrid';
} elseif (str_contains($descriptionLower, 'remote')) {
	$jobData['work_location'] = 'Remote';
} else {
	$jobData['work_location'] = 'On-site (' . (string)$jobData['office_name'] . ')';
}

$normalizeEligibilityOption = static function (string $value): string {
	$key = strtolower(trim($value));
	return match ($key) {
		'none', 'not_applicable', 'not applicable', 'n/a', 'na' => 'none',
		'csc', 'career service', 'career service sub professional' => 'csc',
		'prc' => 'prc',
		'csc_prc', 'csc,prc', 'csc, prc', 'csc/prc' => 'csc_prc',
		default => 'csc_prc',
	};
};

$resolveCriteriaByPosition = static function (string $positionId) use ($supabaseUrl, $headers, $normalizeEligibilityOption): array {
	$default = [
		'eligibility_option' => 'none',
		'minimum_education_years' => 2,
		'minimum_training_hours' => 4,
		'minimum_experience_years' => 1,
	];

	if ($positionId === '' || !isValidUuid($positionId)) {
		return $default;
	}

	$criteriaResponse = apiRequest(
		'GET',
		$supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.criteria') . '&limit=1',
		$headers
	);

	if (isSuccessful($criteriaResponse)) {
		$raw = $criteriaResponse['data'][0]['setting_value'] ?? null;
		$value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
		if (is_array($value)) {
			$default['eligibility_option'] = $normalizeEligibilityOption((string)($value['eligibility'] ?? 'none'));
			$default['minimum_education_years'] = max(0, (float)($value['minimum_education_years'] ?? 2));
			$default['minimum_training_hours'] = max(0, (float)($value['minimum_training_hours'] ?? 4));
			$default['minimum_experience_years'] = max(0, (float)($value['minimum_experience_years'] ?? 1));
		}
	}

	$positionCriteriaResponse = apiRequest(
		'GET',
		$supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.position_criteria') . '&limit=1',
		$headers
	);

	if (!isSuccessful($positionCriteriaResponse)) {
		return $default;
	}

	$raw = $positionCriteriaResponse['data'][0]['setting_value'] ?? null;
	$value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
	if (!is_array($value)) {
		return $default;
	}

	$overrides = is_array($value['position_overrides'] ?? null) ? (array)$value['position_overrides'] : [];
	$positionKey = strtolower(trim($positionId));
	$row = is_array($overrides[$positionKey] ?? null) ? (array)$overrides[$positionKey] : [];
	if (empty($row)) {
		return $default;
	}

	return [
		'eligibility_option' => $normalizeEligibilityOption((string)($row['eligibility'] ?? $default['eligibility_option'])),
		'minimum_education_years' => max(0, (float)($row['minimum_education_years'] ?? $default['minimum_education_years'])),
		'minimum_training_hours' => max(0, (float)($row['minimum_training_hours'] ?? $default['minimum_training_hours'])),
		'minimum_experience_years' => max(0, (float)($row['minimum_experience_years'] ?? $default['minimum_experience_years'])),
	];
};

$positionId = (string)($position['id'] ?? '');
$resolvedCriteria = $resolveCriteriaByPosition($positionId);
$eligibilityOption = (string)($resolvedCriteria['eligibility_option'] ?? 'none');
$jobData['criteria'] = [
	'eligibility_required' => $eligibilityOption !== 'none',
	'eligibility_label' => match ($eligibilityOption) {
		'csc' => 'CSC Eligibility Required',
		'prc' => 'PRC Eligibility Required',
		'csc_prc' => 'CSC or PRC Eligibility Required',
		default => 'None (Not Required)',
	},
	'minimum_education_years' => (float)$resolvedCriteria['minimum_education_years'],
	'minimum_training_hours' => (float)$resolvedCriteria['minimum_training_hours'],
	'minimum_experience_years' => (float)$resolvedCriteria['minimum_experience_years'],
];

$educationYearsEstimate = 0.0;
$educationResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$personId = isSuccessful($educationResponse) ? cleanText($educationResponse['data'][0]['id'] ?? null) : null;
if ($personId !== null && isValidUuid($personId)) {
	$personEducationResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/person_educations?select=education_level,sequence_no&person_id=eq.' . rawurlencode($personId)
		. '&order=sequence_no.desc&limit=1',
		$headers
	);

	if (isSuccessful($personEducationResponse) && !empty((array)($personEducationResponse['data'] ?? []))) {
		$level = strtolower((string)($personEducationResponse['data'][0]['education_level'] ?? ''));
		$educationYearsEstimate = match ($level) {
			'graduate' => 6.0,
			'college' => 4.0,
			'vocational' => 2.0,
			'secondary' => 0.0,
			'elementary' => 0.0,
			default => 0.0,
		};
	}
}

$criteriaGaps = [];
if ($educationYearsEstimate < (float)$jobData['criteria']['minimum_education_years']) {
	$criteriaGaps[] = 'Education';
}
if ((float)$jobData['criteria']['minimum_training_hours'] > 0) {
	$criteriaGaps[] = 'Training';
}
if ((float)$jobData['criteria']['minimum_experience_years'] > 0) {
	$criteriaGaps[] = 'Experience';
}
if ((bool)$jobData['criteria']['eligibility_required']) {
	$criteriaGaps[] = 'Eligibility';
}

if (!empty($criteriaGaps)) {
	$criteriaGapMessage = 'Potential missing criteria: ' . implode(', ', array_values(array_unique($criteriaGaps))) . '. Applications with missing criteria may be marked as Not Qualified during screening.';
}

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

	$uploadedDocumentsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_documents?select=id,document_type,file_name,mime_type,file_size_bytes,uploaded_at,application:applications!inner(applicant_profile_id)'
		. '&application.applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
		. '&order=uploaded_at.desc&limit=500',
		$headers
	);

	$uploadedDocumentByType = [];
	if (isSuccessful($uploadedDocumentsResponse)) {
		foreach ((array)($uploadedDocumentsResponse['data'] ?? []) as $uploadedDocumentRow) {
			$documentType = strtolower((string)($uploadedDocumentRow['document_type'] ?? ''));
			if ($documentType === '' || array_key_exists($documentType, $uploadedDocumentByType)) {
				continue;
			}

			$uploadedDocumentByType[$documentType] = [
				'file_name' => (string)($uploadedDocumentRow['file_name'] ?? 'Uploaded document'),
				'uploaded_at' => cleanText($uploadedDocumentRow['uploaded_at'] ?? null),
			];
		}
	}

	foreach ((array)$jobData['required_documents'] as $documentConfig) {
		$documentType = strtolower((string)($documentConfig['document_type'] ?? 'other'));
		$isRequired = (bool)($documentConfig['required'] ?? true);
		$matchedUpload = (array)($uploadedDocumentByType[$documentType] ?? []);
		$isFulfilled = !empty($matchedUpload);

		if ($isRequired) {
			$requiredDocumentSummary['total_required']++;
			if ($isFulfilled) {
				$requiredDocumentSummary['fulfilled_required']++;
			}
		}

		$requiredDocumentChecklist[] = [
			'key' => (string)($documentConfig['key'] ?? ''),
			'label' => (string)($documentConfig['label'] ?? 'Required document'),
			'document_type' => $documentType,
			'required' => $isRequired,
			'fulfilled' => $isFulfilled,
			'uploaded_file_name' => (string)($matchedUpload['file_name'] ?? ''),
			'uploaded_at' => cleanText($matchedUpload['uploaded_at'] ?? null),
		];
	}
}

if (empty($requiredDocumentChecklist)) {
	foreach ((array)$jobData['required_documents'] as $documentConfig) {
		$isRequired = (bool)($documentConfig['required'] ?? true);
		if ($isRequired) {
			$requiredDocumentSummary['total_required']++;
		}

		$requiredDocumentChecklist[] = [
			'key' => (string)($documentConfig['key'] ?? ''),
			'label' => (string)($documentConfig['label'] ?? 'Required document'),
			'document_type' => strtolower((string)($documentConfig['document_type'] ?? 'other')),
			'required' => $isRequired,
			'fulfilled' => false,
			'uploaded_file_name' => '',
			'uploaded_at' => null,
		];
	}
}

$requiredDocumentSummary['missing_required'] = max(
	0,
	(int)$requiredDocumentSummary['total_required'] - (int)$requiredDocumentSummary['fulfilled_required']
);

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
