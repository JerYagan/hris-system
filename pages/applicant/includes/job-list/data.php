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

$jobListCacheTtl = 300;
$jobListFilterCacheKey = 'applicant_job_list_filter_metadata_v3';
$jobListProfileCacheKey = 'applicant_job_list_profile_' . $applicantUserId;
$nowTimestamp = time();

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

$sessionAvailable = session_status() === PHP_SESSION_ACTIVE;
$cachedFilterMetadata = $sessionAvailable ? ($_SESSION[$jobListFilterCacheKey] ?? null) : null;
if (
	is_array($cachedFilterMetadata)
	&& (int)($cachedFilterMetadata['expires_at'] ?? 0) > $nowTimestamp
	&& isset($cachedFilterMetadata['office_options'], $cachedFilterMetadata['employment_type_options'])
) {
	$officeOptions = is_array($cachedFilterMetadata['office_options']) ? $cachedFilterMetadata['office_options'] : [];
	$employmentTypeOptions = is_array($cachedFilterMetadata['employment_type_options']) ? $cachedFilterMetadata['employment_type_options'] : [];
} else {
	$officesResponse = apiRequest(
		'GET',
		$supabaseUrl . '/rest/v1/offices?select=id,office_name,office_type&is_active=eq.true&order=office_name.asc&limit=500',
		$headers
	);

	if (isSuccessful($officesResponse)) {
		$officeOptionsById = [];
		foreach ((array)($officesResponse['data'] ?? []) as $officeRow) {
			$officeId = cleanText($officeRow['id'] ?? null);
			$officeName = cleanText($officeRow['office_name'] ?? null);

			if ($officeId === null || $officeName === null) {
				continue;
			}

			$officeOptionsById[$officeId] = [
				'id' => $officeId,
				'office_name' => $officeName,
			];
		}

		$officeOptions = array_values($officeOptionsById);
		usort(
			$officeOptions,
			static fn (array $left, array $right): int => strnatcasecmp(
				(string)($left['office_name'] ?? ''),
				(string)($right['office_name'] ?? '')
			)
		);
	} else {
		$dataLoadError = 'Failed to load division filters.';
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
		natcasesort($employmentTypeOptions);
		$employmentTypeOptions = array_values($employmentTypeOptions);
	} else {
		$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to load employment type filters.');
	}

	if ($sessionAvailable && !empty($officeOptions) && !empty($employmentTypeOptions)) {
		$_SESSION[$jobListFilterCacheKey] = [
			'expires_at' => $nowTimestamp + $jobListCacheTtl,
			'office_options' => $officeOptions,
			'employment_type_options' => $employmentTypeOptions,
		];
	}
}

$cachedApplicantProfile = $sessionAvailable ? ($_SESSION[$jobListProfileCacheKey] ?? null) : null;
if (
	is_array($cachedApplicantProfile)
	&& (int)($cachedApplicantProfile['expires_at'] ?? 0) > $nowTimestamp
	&& is_string($cachedApplicantProfile['id'] ?? null)
) {
	$applicantProfileId = (string)$cachedApplicantProfile['id'];
} else {
	$applicantProfileResponse = apiRequest(
		'GET',
		$supabaseUrl . '/rest/v1/applicant_profiles?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
		$headers
	);

	if (isSuccessful($applicantProfileResponse)) {
		$applicantProfileId = (string)(($applicantProfileResponse['data'][0]['id'] ?? ''));
		if ($sessionAvailable && $applicantProfileId !== '') {
			$_SESSION[$jobListProfileCacheKey] = [
				'expires_at' => $nowTimestamp + $jobListCacheTtl,
				'id' => $applicantProfileId,
			];
		}
	} else {
		$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to resolve applicant profile context.');
	}
}

$fetchExactCount = static function (string $url) use ($headers): ?int {
	$response = apiRequest(
		'GET',
		$url,
		array_merge($headers, ['Prefer: count=exact'])
	);

	if (!isSuccessful($response)) {
		return null;
	}

	$exactCount = getSupabaseExactCount($response);
	if ($exactCount !== null) {
		return $exactCount;
	}

	return count((array)($response['data'] ?? []));
};

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

$countQueryBase = $supabaseUrl . '/rest/v1/job_postings?select=id';
if (!empty($postingFilters)) {
	$countQueryBase .= '&' . implode('&', $postingFilters);
}

$jobsTotalCount = $fetchExactCount($countQueryBase . '&limit=1');
if ($jobsTotalCount !== null) {
	$jobsTotal = $jobsTotalCount;
	$openPositionsTotal = $jobsTotalCount;
} else {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Failed to load job totals.');
}

$closingSoonFilters = $postingFilters;
$closingSoonFilters[] = 'close_date=lte.' . $weekAhead;
$closingSoonQueryBase = $supabaseUrl . '/rest/v1/job_postings?select=id';
if (!empty($closingSoonFilters)) {
	$closingSoonQueryBase .= '&' . implode('&', $closingSoonFilters);
}

$closingSoonCount = $fetchExactCount($closingSoonQueryBase . '&limit=1');
if ($closingSoonCount !== null) {
	$closingThisWeekTotal = $closingSoonCount;
}

$totalPages = max(1, (int)ceil(($jobsTotal > 0 ? $jobsTotal : 1) / $pageSize));
if ($page > $totalPages) {
	$page = $totalPages;
}

$offset = ($page - 1) * $pageSize;

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
		'office_name' => (string)($office['office_name'] ?? 'Division not specified'),
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

$hasPrevPage = $page > 1;
$hasNextPage = ($page * $pageSize) < $jobsTotal;
