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
	'employment_type_label' => '-',
	'work_location' => '-',
	'plantilla_item_no' => '-',
	'csc_reference_url' => null,
	'close_date' => null,
	'deadline_display' => '-',
	'criteria' => [
		'eligibility_required' => false,
		'eligibility_label' => 'None (Not Required)',
		'minimum_education_years' => 2,
		'minimum_training_hours' => 4,
		'minimum_experience_years' => 1,
	],
];

$criteriaGapMessage = null;

$applicantProfile = [
	'id' => '',
	'full_name' => (string)($_SESSION['user']['name'] ?? 'Applicant User'),
	'email' => (string)($_SESSION['user']['email'] ?? '-'),
];

$educationLevelOptions = [
	['value' => 'elementary', 'label' => 'Elementary'],
	['value' => 'secondary', 'label' => 'Secondary'],
	['value' => 'vocational', 'label' => 'Vocational/Trade Course'],
	['value' => 'college', 'label' => 'College'],
	['value' => 'graduate', 'label' => 'Graduate Studies'],
];

$educationFormDefaults = [
	'education_attainment' => '',
	'course_strand' => '',
	'school_institution' => '',
];

$applyEducationEntries = [];
$applyWorkExperienceEntries = [];

$profileEducationEntries = [];
$profileWorkExperienceEntries = [];
$profileCompletionPrompt = null;
$profileQualificationSnapshot = [
	'education_years_estimate' => 0.0,
	'experience_years_estimate' => 0.0,
	'education_entries_count' => 0,
	'work_entries_count' => 0,
];
$criteriaEvaluation = [
	'recommendation' => 'Profile data unavailable for automatic evaluation.',
	'missing' => [],
	'met' => [],
	'criterion_statuses' => [],
	'profile_ready' => false,
];
$criteriaChecklistItems = [];
$trainingFormDefaults = [
	'training_hours_completed' => 0.0,
	'has_training_proof' => false,
	'training_proof_file_name' => '',
];

$requiredDocumentConfig = defaultRequiredDocumentConfig();
$requiredDocumentChecklist = [];
$requiredDocumentSummary = [
	'total_required' => 0,
	'fulfilled_required' => 0,
	'missing_required' => 0,
];
$pdsReferenceSheetUrl = 'https://docs.google.com/spreadsheets/d/1XYXyBVqEKuUqPsCHxkf5Xr6I8iL7jGOKxCO6ZHKL9Rg/edit?gid=1928756542#gid=1928756542';

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
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name,email,training_hours_completed&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

if (isSuccessful($applicantProfileResponse) && !empty((array)($applicantProfileResponse['data'] ?? []))) {
	$profileRow = (array)$applicantProfileResponse['data'][0];
	$applicantProfile['id'] = (string)($profileRow['id'] ?? '');
	$applicantProfile['full_name'] = (string)($profileRow['full_name'] ?? $applicantProfile['full_name']);
	$applicantProfile['email'] = (string)($profileRow['email'] ?? $applicantProfile['email']);
	$trainingFormDefaults['training_hours_completed'] = max(0.0, (float)($profileRow['training_hours_completed'] ?? 0));
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
	$educationLevelToYears = static function (?string $value): float {
		$key = strtolower(trim((string)$value));
		return match ($key) {
			'graduate' => 6.0,
			'college' => 4.0,
			'vocational' => 2.0,
			'secondary', 'elementary' => 0.0,
			default => 0.0,
		};
	};

	$parseDate = static function (?string $value): ?DateTimeImmutable {
		$candidate = trim((string)$value);
		if ($candidate === '') {
			return null;
		}

		$date = DateTimeImmutable::createFromFormat('Y-m-d', $candidate);
		if ($date instanceof DateTimeImmutable) {
			return $date;
		}

		try {
			return new DateTimeImmutable($candidate);
		} catch (Throwable) {
			return null;
		}
	};

	$educationResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/person_educations?select=id,education_level,school_name,course_degree,year_graduated,sequence_no'
		. '&person_id=eq.' . rawurlencode($personId)
		. '&order=sequence_no.asc&limit=200',
		$headers
	);

	if (isSuccessful($educationResponse) && !empty((array)($educationResponse['data'] ?? []))) {
		$profileEducationEntries = (array)($educationResponse['data'] ?? []);
		$educationRow = [];
		$highestEducationYears = -1.0;

		foreach ($profileEducationEntries as $educationEntry) {
			$row = (array)$educationEntry;
			$years = $educationLevelToYears((string)($row['education_level'] ?? ''));
			if ($years >= $highestEducationYears) {
				$highestEducationYears = $years;
				$educationRow = $row;
			}
		}

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
		$profileQualificationSnapshot['education_years_estimate'] = max(0.0, $highestEducationYears);
		$profileQualificationSnapshot['education_entries_count'] = count($profileEducationEntries);

		foreach ($profileEducationEntries as $profileEducationEntry) {
			$row = (array)$profileEducationEntry;
			$applyEducationEntries[] = [
				'education_level' => strtolower((string)($row['education_level'] ?? '')),
				'school_name' => (string)(cleanText($row['school_name'] ?? null) ?? ''),
				'course_degree' => (string)(cleanText($row['course_degree'] ?? null) ?? ''),
				'year_graduated' => (string)(cleanText($row['year_graduated'] ?? null) ?? ''),
			];
		}
	}

	$workExperienceResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/person_work_experiences?select=id,inclusive_date_from,inclusive_date_to,position_title,office_company,achievements,sequence_no'
		. '&person_id=eq.' . rawurlencode($personId)
		. '&order=sequence_no.asc&limit=500',
		$headers
	);

	if (isSuccessful($workExperienceResponse)) {
		$profileWorkExperienceEntries = (array)($workExperienceResponse['data'] ?? []);
		$totalExperienceDays = 0;
		$todayDate = new DateTimeImmutable('today');

		foreach ($profileWorkExperienceEntries as $workEntry) {
			$row = (array)$workEntry;
			$fromDate = $parseDate(cleanText($row['inclusive_date_from'] ?? null));
			if (!$fromDate instanceof DateTimeImmutable) {
				continue;
			}

			$toDate = $parseDate(cleanText($row['inclusive_date_to'] ?? null));
			if (!$toDate instanceof DateTimeImmutable) {
				$toDate = $todayDate;
			}

			if ($toDate < $fromDate) {
				continue;
			}

			$totalExperienceDays += (int)$fromDate->diff($toDate)->days + 1;
		}

		$profileQualificationSnapshot['experience_years_estimate'] = round($totalExperienceDays / 365, 2);
		$profileQualificationSnapshot['work_entries_count'] = count($profileWorkExperienceEntries);

		foreach ($profileWorkExperienceEntries as $workEntry) {
			$row = (array)$workEntry;
			$applyWorkExperienceEntries[] = [
				'position_title' => (string)(cleanText($row['position_title'] ?? null) ?? ''),
				'company_name' => (string)(cleanText($row['office_company'] ?? null) ?? ''),
				'start_date' => (string)(cleanText($row['inclusive_date_from'] ?? null) ?? ''),
				'end_date' => (string)(cleanText($row['inclusive_date_to'] ?? null) ?? ''),
				'responsibilities' => (string)(cleanText($row['achievements'] ?? null) ?? ''),
			];
		}
	}
}

if (empty($applyEducationEntries)) {
	$applyEducationEntries[] = [
		'education_level' => '',
		'school_name' => '',
		'course_degree' => '',
		'year_graduated' => '',
	];
}

if (empty($applyWorkExperienceEntries)) {
	$applyWorkExperienceEntries[] = [
		'position_title' => '',
		'company_name' => '',
		'start_date' => '',
		'end_date' => '',
		'responsibilities' => '',
	];
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
	. '/rest/v1/job_postings?select=id,title,position_id,open_date,close_date,posting_status,plantilla_item_no,csc_reference_url,required_documents,description,office:offices(office_name),position:job_positions(id,employment_classification)'
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
		. '/rest/v1/job_postings?select=id,title,position_id,open_date,close_date,posting_status,description,office:offices(office_name),position:job_positions(id,employment_classification)'
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
$employmentTypeRaw = strtolower(trim((string)$jobData['employment_type']));
$jobData['employment_type_label'] = match ($employmentTypeRaw) {
	'regular', 'coterminous' => 'Full-time',
	'contractual', 'casual', 'job_order' => 'Contract',
	default => $jobData['employment_type'] !== '-' ? ucwords(str_replace('_', ' ', (string)$jobData['employment_type'])) : '-',
};

$descriptionLower = strtolower((string)($jobRow['description'] ?? ''));
if (str_contains($descriptionLower, 'hybrid')) {
	$jobData['work_location'] = 'Hybrid';
} elseif (str_contains($descriptionLower, 'remote')) {
	$jobData['work_location'] = 'Remote';
} else {
	$jobData['work_location'] = 'On-site (' . (string)$jobData['office_name'] . ')';
}

$jobData['plantilla_item_no'] = cleanText($jobRow['plantilla_item_no'] ?? null) ?? '-';
$jobData['csc_reference_url'] = cleanText($jobRow['csc_reference_url'] ?? null);
$requiredDocumentConfig = normalizeRequiredDocumentConfig($jobRow['required_documents'] ?? null);
$uploadedDocumentByType = [];
$uploadedEligibilityDocument = null;
$uploadedTrainingProofDocument = null;

$isValidEligibilityFilename = static function (?string $fileName): bool {
	$name = strtolower(trim((string)$fileName));
	if ($name === '') {
		return false;
	}

	return str_contains($name, 'csc')
		|| str_contains($name, 'prc')
		|| str_contains($name, 'eligibility');
};

$isValidTrainingProofFilename = static function (?string $fileName): bool {
	$name = strtolower(trim((string)$fileName));
	if ($name === '') {
		return false;
	}

	return str_contains($name, 'training')
		|| str_contains($name, 'seminar')
		|| str_contains($name, 'workshop')
		|| str_contains($name, 'certificate')
		|| str_contains($name, 'cert');
};

if (isValidUuid((string)$applicantProfile['id'])) {
	$uploadedDocumentsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/application_documents?select=id,document_type,file_name,mime_type,file_size_bytes,uploaded_at,application:applications!inner(applicant_profile_id)'
		. '&application.applicant_profile_id=eq.' . rawurlencode((string)$applicantProfile['id'])
		. '&order=uploaded_at.desc&limit=500',
		$headers
	);

	if (isSuccessful($uploadedDocumentsResponse)) {
		foreach ((array)($uploadedDocumentsResponse['data'] ?? []) as $uploadedDocumentRow) {
			$fileName = (string)($uploadedDocumentRow['file_name'] ?? 'Uploaded document');
			$documentType = strtolower((string)($uploadedDocumentRow['document_type'] ?? ''));

			if ($uploadedEligibilityDocument === null
				&& in_array($documentType, ['certificate', 'id', 'other'], true)
				&& $isValidEligibilityFilename($fileName)
			) {
				$uploadedEligibilityDocument = [
					'file_name' => $fileName,
					'uploaded_at' => cleanText($uploadedDocumentRow['uploaded_at'] ?? null),
				];
			}

			if ($uploadedTrainingProofDocument === null
				&& in_array($documentType, ['certificate', 'other'], true)
				&& $isValidTrainingProofFilename($fileName)
			) {
				$uploadedTrainingProofDocument = [
					'file_name' => $fileName,
					'uploaded_at' => cleanText($uploadedDocumentRow['uploaded_at'] ?? null),
				];
			}

			if ($documentType === '' || array_key_exists($documentType, $uploadedDocumentByType)) {
				continue;
			}

			$uploadedDocumentByType[$documentType] = [
				'file_name' => $fileName,
				'uploaded_at' => cleanText($uploadedDocumentRow['uploaded_at'] ?? null),
			];
		}
	}
}

$trainingHoursRaw = cleanText($_POST['training_hours_completed'] ?? $_GET['training_hours_completed'] ?? null);
if ($trainingHoursRaw !== null && is_numeric($trainingHoursRaw) && (float)$trainingHoursRaw >= 0) {
	$trainingFormDefaults['training_hours_completed'] = (float)$trainingHoursRaw;
}
$trainingFormDefaults['has_training_proof'] = is_array($uploadedTrainingProofDocument);
$trainingFormDefaults['training_proof_file_name'] = (string)($uploadedTrainingProofDocument['file_name'] ?? '');

$jobData['close_date'] = cleanText($jobRow['close_date'] ?? null);
$jobData['deadline_display'] = $jobData['close_date'] ? date('F j, Y', strtotime((string)$jobData['close_date'])) : '-';

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

$positionId = (string)($jobRow['position_id'] ?? ($jobRow['position']['id'] ?? ''));
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

if ((bool)$jobData['criteria']['eligibility_required']) {
	$hasEligibilityDocumentInChecklist = false;
	foreach ($requiredDocumentConfig as $documentConfig) {
		if ((string)($documentConfig['key'] ?? '') === 'eligibility_document') {
			$hasEligibilityDocumentInChecklist = true;
			break;
		}
	}

	if (!$hasEligibilityDocumentInChecklist) {
		$requiredDocumentConfig[] = [
			'key' => 'eligibility_document',
			'label' => 'CSC/PRC Eligibility Document',
			'document_type' => 'certificate',
			'required' => true,
		];
	}
}

$requiredDocumentChecklist = [];
$requiredDocumentSummary = [
	'total_required' => 0,
	'fulfilled_required' => 0,
	'missing_required' => 0,
];

foreach ($requiredDocumentConfig as $documentConfig) {
	$documentType = strtolower((string)($documentConfig['document_type'] ?? 'other'));
	$isRequired = (bool)($documentConfig['required'] ?? true);
	$isEligibilityDoc = (string)($documentConfig['key'] ?? '') === 'eligibility_document';
	$matchedUpload = $isEligibilityDoc
		? (array)($uploadedEligibilityDocument ?? [])
		: (array)($uploadedDocumentByType[$documentType] ?? []);
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

$requiredDocumentSummary['missing_required'] = max(
	0,
	(int)$requiredDocumentSummary['total_required'] - (int)$requiredDocumentSummary['fulfilled_required']
);

$criteriaMissing = [];
$criteriaMet = [];

$educationMinimum = (float)($jobData['criteria']['minimum_education_years'] ?? 0);
$experienceMinimum = (float)($jobData['criteria']['minimum_experience_years'] ?? 0);
$trainingMinimum = (float)($jobData['criteria']['minimum_training_hours'] ?? 0);
$educationYearsEstimate = (float)($profileQualificationSnapshot['education_years_estimate'] ?? 0.0);
$experienceYearsEstimate = (float)($profileQualificationSnapshot['experience_years_estimate'] ?? 0.0);
$trainingHoursCompleted = max(0.0, (float)($trainingFormDefaults['training_hours_completed'] ?? 0.0));
$hasTrainingProof = (bool)($trainingFormDefaults['has_training_proof'] ?? false);
$hasProfileEducation = ((int)($profileQualificationSnapshot['education_entries_count'] ?? 0)) > 0;
$hasProfileWork = ((int)($profileQualificationSnapshot['work_entries_count'] ?? 0)) > 0;
$experienceRequired = $experienceMinimum > 0;
$profileReadyForAutoEvaluation = $hasProfileEducation && (!$experienceRequired || $hasProfileWork);

if (!$hasProfileEducation) {
	$criteriaMissing[] = 'Education';
	$criteriaEvaluation['criterion_statuses']['education'] = 'No education entry found in your profile.';
} elseif ($educationYearsEstimate < $educationMinimum) {
	$criteriaMissing[] = 'Education';
	$criteriaEvaluation['criterion_statuses']['education'] = 'You meet some profile data requirements but your current education does not meet the minimum years configured for this posting.';
} else {
	$criteriaMet[] = 'Education';
	$criteriaEvaluation['criterion_statuses']['education'] = 'Education requirement appears satisfied from your profile.';
}

if (!$experienceRequired) {
	$criteriaMet[] = 'Experience';
	$criteriaEvaluation['criterion_statuses']['experience'] = 'No work experience minimum for this posting.';
} elseif (!$hasProfileWork) {
	$criteriaMissing[] = 'Experience';
	$criteriaEvaluation['criterion_statuses']['experience'] = 'No work experience entry found in your profile.';
} elseif ($experienceYearsEstimate < $experienceMinimum) {
	$criteriaMissing[] = 'Experience';
	$criteriaEvaluation['criterion_statuses']['experience'] = 'You have work experience on file but it is below the required years for this posting.';
} else {
	$criteriaMet[] = 'Experience';
	$criteriaEvaluation['criterion_statuses']['experience'] = 'Experience requirement appears satisfied from your profile.';
}

if ((bool)($jobData['criteria']['eligibility_required'] ?? false)) {
	$hasEligibilityHint = $uploadedEligibilityDocument !== null;

	if ($hasEligibilityHint) {
		$criteriaMet[] = 'Eligibility';
		$criteriaEvaluation['criterion_statuses']['eligibility'] = 'Eligibility requirement appears supported by your uploaded documents.';
	} else {
		$criteriaMissing[] = 'Eligibility';
		$criteriaEvaluation['criterion_statuses']['eligibility'] = 'Eligibility appears required; upload valid CSC/PRC supporting proof in the checklist below.';
	}
} else {
	$criteriaMet[] = 'Eligibility';
	$criteriaEvaluation['criterion_statuses']['eligibility'] = 'No eligibility requirement for this posting.';
}

if ($trainingMinimum > 0) {
	if ($trainingHoursCompleted < $trainingMinimum || !$hasTrainingProof) {
		$criteriaMissing[] = 'Training';
		$criteriaEvaluation['criterion_statuses']['training'] = 'Training requirement needs at least ' . rtrim(rtrim(number_format($trainingMinimum, 2, '.', ''), '0'), '.') . ' hour(s) and supporting training certificate/proof.';
	} else {
		$criteriaMet[] = 'Training';
		$criteriaEvaluation['criterion_statuses']['training'] = 'Training requirement appears satisfied by submitted hours and proof document.';
	}
} else {
	$criteriaMet[] = 'Training';
	$criteriaEvaluation['criterion_statuses']['training'] = 'No training-hour minimum for this posting.';
}

if (!$profileReadyForAutoEvaluation) {
	$profileCompletionPrompt = $experienceRequired
		? 'Complete your profile education and work experience entries so the system can automatically evaluate your qualifications before submission.'
		: 'Complete your profile education entries so the system can automatically evaluate your qualifications before submission.';
}

$uniqueMissing = array_values(array_unique($criteriaMissing));
$uniqueMet = array_values(array_unique($criteriaMet));

$criteriaEvaluation['missing'] = $uniqueMissing;
$criteriaEvaluation['met'] = $uniqueMet;
$criteriaEvaluation['profile_ready'] = $profileReadyForAutoEvaluation;

$criteriaOrder = ['eligibility', 'education', 'training', 'experience'];
foreach ($criteriaOrder as $criteriaKey) {
	$criteriaLabel = ucfirst($criteriaKey);
	$criteriaChecklistItems[] = [
		'key' => $criteriaKey,
		'label' => $criteriaLabel,
		'met' => !in_array($criteriaLabel, $uniqueMissing, true),
		'message' => (string)($criteriaEvaluation['criterion_statuses'][$criteriaKey] ?? ''),
	];
}

if (empty($uniqueMissing)) {
	$criteriaEvaluation['recommendation'] = 'You are fully qualified based on currently available profile and uploaded document signals. Final confirmation remains subject to screening validation.';
	$criteriaGapMessage = null;
} else {
	$criteriaEvaluation['recommendation'] = 'You currently have potential gaps: ' . implode(', ', $uniqueMissing) . '. Update your profile and uploads to improve your recommendation before submitting.';
	$criteriaGapMessage = $criteriaEvaluation['recommendation'];
}

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
