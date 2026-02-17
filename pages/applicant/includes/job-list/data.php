<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$today = date('Y-m-d');
$weekAhead = date('Y-m-d', strtotime('+7 days'));

$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = (int)($_GET['page_size'] ?? 10);
$allowedPageSizes = [10, 20, 30];
if (!in_array($pageSize, $allowedPageSizes, true)) {
	$pageSize = 10;
}

$filters = [
	'q' => cleanText($_GET['q'] ?? null) ?? '',
	'office' => cleanText($_GET['office'] ?? null) ?? '',
	'employment_type' => cleanText($_GET['employment_type'] ?? null) ?? '',
];

$offset = ($page - 1) * $pageSize;
$dataLoadError = null;

$openPositionsTotal = 0;
$closingThisWeekTotal = 0;
$jobsTotal = 0;
$totalPages = 1;
$hasPrevPage = false;
$hasNextPage = false;

$officeOptions = [];
$employmentTypeOptions = [];
$jobs = [];
$applicantProfileId = '';

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

if ($filters['office'] !== '' && !isValidUuid($filters['office'])) {
	$filters['office'] = '';
}

$officesResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&order=office_name.asc&limit=200',
	$headers
);

if (isSuccessful($officesResponse)) {
	$officeOptions = (array)($officesResponse['data'] ?? []);
} else {
	$dataLoadError = 'Failed to load office filters.';
}

$employmentTypesResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/job_positions?select=employment_classification&is_active=eq.true&limit=300',
	$headers
);

if (isSuccessful($employmentTypesResponse)) {
	$types = [];
	foreach ((array)($employmentTypesResponse['data'] ?? []) as $row) {
		$classification = cleanText($row['employment_classification'] ?? null);
		if ($classification !== null) {
			$types[$classification] = true;
		}
	}

	$employmentTypeOptions = array_keys($types);
	sort($employmentTypeOptions);
} else {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to load employment type filters.');
}

$applicantProfileResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

if (isSuccessful($applicantProfileResponse)) {
	$applicantProfileId = (string)(($applicantProfileResponse['data'][0]['id'] ?? ''));
} else {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to resolve applicant profile context.');
}

$postingFilters = [
	'posting_status=eq.published',
	'open_date=lte.' . $today,
	'close_date=gte.' . $today,
];

if ($filters['office'] !== '') {
	$postingFilters[] = 'office_id=eq.' . rawurlencode($filters['office']);
}

if ($filters['q'] !== '') {
	$search = str_replace(',', ' ', $filters['q']);
	$postingFilters[] = 'or=' . rawurlencode('(title.ilike.*' . $search . '*,description.ilike.*' . $search . '*)');
}

$positionIdsByEmploymentType = [];
if ($filters['employment_type'] !== '') {
	$positionFilterResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/job_positions?select=id&employment_classification=eq.'
		. rawurlencode($filters['employment_type'])
		. '&is_active=eq.true&limit=300',
		$headers
	);

	if (isSuccessful($positionFilterResponse)) {
		foreach ((array)($positionFilterResponse['data'] ?? []) as $positionRow) {
			$positionId = cleanText($positionRow['id'] ?? null);
			if ($positionId !== null) {
				$positionIdsByEmploymentType[] = $positionId;
			}
		}

		if (empty($positionIdsByEmploymentType)) {
			$jobs = [];
			$jobsTotal = 0;
			$openPositionsTotal = 0;
			$closingThisWeekTotal = 0;
			$totalPages = 1;
			$hasPrevPage = $page > 1;
			$hasNextPage = false;
			return;
		}

		$postingFilters[] = 'position_id=in.' . rawurlencode('(' . implode(',', $positionIdsByEmploymentType) . ')');
	} else {
		$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to apply employment type filter.');
	}
}

$jobSelect = 'id,title,description,qualifications,responsibilities,open_date,close_date,posting_status,plantilla_item_no,csc_reference_url,office:offices(id,office_name),position:job_positions(id,position_title,employment_classification,salary_grade)';
$baseJobsUrl = $supabaseUrl . '/rest/v1/job_postings?select=' . rawurlencode($jobSelect);
if (!empty($postingFilters)) {
	$baseJobsUrl .= '&' . implode('&', $postingFilters);
}


$jobsResponse = apiRequest(
	'GET',
	$baseJobsUrl . '&order=close_date.asc&offset=' . $offset . '&limit=' . $pageSize,
	$headers
);

if (!isSuccessful($jobsResponse)) {
	$fallbackJobSelect = 'id,title,description,qualifications,responsibilities,open_date,close_date,posting_status,office:offices(id,office_name),position:job_positions(id,position_title,employment_classification,salary_grade)';
	$fallbackJobsUrl = $supabaseUrl . '/rest/v1/job_postings?select=' . rawurlencode($fallbackJobSelect);
	if (!empty($postingFilters)) {
		$fallbackJobsUrl .= '&' . implode('&', $postingFilters);
	}

	$jobsResponse = apiRequest(
		'GET',
		$fallbackJobsUrl . '&order=close_date.asc&offset=' . $offset . '&limit=' . $pageSize,
		$headers
	);
}

if (!isSuccessful($jobsResponse)) {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to load job postings.');
} else {
	$jobs = (array)($jobsResponse['data'] ?? []);
}

$countResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/job_postings?select=id&' . implode('&', $postingFilters) . '&limit=5000',
	$headers
);

if (isSuccessful($countResponse)) {
	$jobsTotal = count((array)($countResponse['data'] ?? []));
	$openPositionsTotal = $jobsTotal;
} else {
	$jobsTotal = count($jobs);
	$openPositionsTotal = $jobsTotal;
}

$closingSoonFilters = $postingFilters;
$closingSoonFilters[] = 'close_date=lte.' . $weekAhead;
$closingSoonResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/job_postings?select=id&' . implode('&', $closingSoonFilters) . '&limit=5000',
	$headers
);

if (isSuccessful($closingSoonResponse)) {
	$closingThisWeekTotal = count((array)($closingSoonResponse['data'] ?? []));
}

$appliedPostingMap = [];
if ($applicantProfileId !== '' && !empty($jobs)) {
	$jobIds = [];
	foreach ($jobs as $jobRow) {
		$jobId = cleanText($jobRow['id'] ?? null);
		if ($jobId !== null) {
			$jobIds[] = $jobId;
		}
	}

	if (!empty($jobIds)) {
		$applicationsResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/applications?select=job_posting_id&applicant_profile_id=eq.'
			. rawurlencode($applicantProfileId)
			. '&job_posting_id=in.' . rawurlencode('(' . implode(',', $jobIds) . ')')
			. '&limit=500',
			$headers
		);

		if (isSuccessful($applicationsResponse)) {
			foreach ((array)($applicationsResponse['data'] ?? []) as $applicationRow) {
				$postingId = cleanText($applicationRow['job_posting_id'] ?? null);
				if ($postingId !== null) {
					$appliedPostingMap[$postingId] = true;
				}
			}
		}
	}
}

$normalizedJobs = [];
foreach ($jobs as $jobRow) {
	$jobId = (string)($jobRow['id'] ?? '');
	$position = (array)($jobRow['position'] ?? []);
	$office = (array)($jobRow['office'] ?? []);
	$employmentType = trim((string)($position['employment_classification'] ?? ''));
	$closeDate = cleanText($jobRow['close_date'] ?? null);

	$deadlineDisplay = $closeDate ? date('F j, Y', strtotime($closeDate)) : '-';
	$daysRemaining = null;
	if ($closeDate) {
		$daysRemaining = (int)floor((strtotime($closeDate . ' 23:59:59') - time()) / 86400);
	}

	$normalizedJobs[] = [
		'id' => $jobId,
		'title' => (string)($jobRow['title'] ?? 'Untitled Position'),
		'office_name' => (string)($office['office_name'] ?? 'Office not specified'),
		'position_title' => (string)($position['position_title'] ?? ''),
		'employment_type' => $employmentType,
		'plantilla_item_no' => cleanText($jobRow['plantilla_item_no'] ?? null) ?? '-',
		'csc_reference_url' => cleanText($jobRow['csc_reference_url'] ?? null),
		'salary_grade' => cleanText($position['salary_grade'] ?? null) ?? '-',
		'description' => (string)($jobRow['description'] ?? ''),
		'close_date' => $closeDate,
		'deadline_display' => $deadlineDisplay,
		'days_remaining' => $daysRemaining,
		'already_applied' => isset($appliedPostingMap[$jobId]),
		'detail_url' => 'job-view.php?job_id=' . urlencode($jobId),
		'apply_url' => 'apply.php?job_id=' . urlencode($jobId),
		'reference_code' => strtoupper(substr(str_replace('-', '', $jobId), 0, 10)),
	];
}

$jobs = $normalizedJobs;

$totalPages = max(1, (int)ceil(($jobsTotal > 0 ? $jobsTotal : 1) / $pageSize));
if ($page > $totalPages) {
	$page = $totalPages;
}

$hasPrevPage = $page > 1;
$hasNextPage = ($page * $pageSize) < $jobsTotal;
