<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$profileData = [
	'person_id' => '',
	'full_name' => 'Applicant User',
	'email' => cleanText($_SESSION['user']['email'] ?? null) ?? '-',
	'mobile_no' => '-',
	'current_address' => '-',
	'profile_photo_url' => '',
	'profile_photo_public_url' => '',
	'training_hours_completed' => 0.0,
];

$profileSpouses = [];
$profileEducations = [];
$profileWorkExperiences = [];
$uploadedFiles = [];

$passwordChangeStatus = [
	'is_pending' => false,
	'expires_at' => '-',
	'email' => '',
];

$dataLoadError = null;

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$accountResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/user_accounts?select=email,mobile_no&id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$peopleResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,mobile_no,personal_email,profile_photo_url&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$applicantProfileResponse = apiRequest(
	'GET',
	$supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name,email,mobile_no,current_address,resume_url,portfolio_url,training_hours_completed&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
	$headers
);

$accountRow = isSuccessful($accountResponse)
	? (array)($accountResponse['data'][0] ?? [])
	: [];

$peopleRow = isSuccessful($peopleResponse)
	? (array)($peopleResponse['data'][0] ?? [])
	: [];

$applicantProfileRow = isSuccessful($applicantProfileResponse)
	? (array)($applicantProfileResponse['data'][0] ?? [])
	: [];

$firstName = trim((string)($peopleRow['first_name'] ?? ''));
$middleName = trim((string)($peopleRow['middle_name'] ?? ''));
$surname = trim((string)($peopleRow['surname'] ?? ''));
$peopleFullName = trim($firstName . ' ' . $middleName . ' ' . $surname);

if (empty($applicantProfileRow)) {
	$fallbackEmail = strtolower(trim((string)($peopleRow['personal_email'] ?? $accountRow['email'] ?? $_SESSION['user']['email'] ?? '')));
	$fallbackFullName = trim((string)($peopleFullName !== '' ? $peopleFullName : ($_SESSION['user']['name'] ?? 'Applicant User')));

	if ($fallbackEmail !== '') {
		$createApplicantProfileResponse = apiRequest(
			'POST',
			$supabaseUrl . '/rest/v1/applicant_profiles?on_conflict=user_id',
			array_merge($headers, ['Prefer: resolution=merge-duplicates,return=representation']),
			[[
				'user_id' => $applicantUserId,
				'full_name' => $fallbackFullName !== '' ? $fallbackFullName : 'Applicant User',
				'email' => $fallbackEmail,
				'mobile_no' => cleanText($peopleRow['mobile_no'] ?? $accountRow['mobile_no'] ?? null),
				'current_address' => null,
			]]
		);

		if (isSuccessful($createApplicantProfileResponse)) {
			$applicantProfileRow = (array)($createApplicantProfileResponse['data'][0] ?? []);
		} else {
			$dataLoadError = 'Failed to initialize applicant profile record.';
		}
	}
}

if (!isSuccessful($accountResponse)) {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Account query failed.');
}

if (!isSuccessful($peopleResponse)) {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'People query failed.');
}

if (!isSuccessful($applicantProfileResponse) && empty($applicantProfileRow)) {
	$dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Applicant profile query failed.');
}

$profileData['full_name'] = trim((string)($applicantProfileRow['full_name'] ?? ''));
if ($profileData['full_name'] === '') {
	$profileData['full_name'] = $peopleFullName !== '' ? $peopleFullName : (string)($_SESSION['user']['name'] ?? 'Applicant User');
}

$profileData['email'] = trim((string)($applicantProfileRow['email'] ?? ''));
if ($profileData['email'] === '') {
	$profileData['email'] = trim((string)($peopleRow['personal_email'] ?? $accountRow['email'] ?? $_SESSION['user']['email'] ?? '-'));
}

$profileData['mobile_no'] = trim((string)($applicantProfileRow['mobile_no'] ?? ''));
if ($profileData['mobile_no'] === '') {
	$profileData['mobile_no'] = trim((string)($peopleRow['mobile_no'] ?? $accountRow['mobile_no'] ?? '-'));
}

$profileData['current_address'] = trim((string)($applicantProfileRow['current_address'] ?? ''));
if ($profileData['current_address'] === '') {
	$profileData['current_address'] = '-';
}

$profileData['training_hours_completed'] = max(0.0, (float)($applicantProfileRow['training_hours_completed'] ?? 0));
$profileData['profile_photo_url'] = (string)($peopleRow['profile_photo_url'] ?? '');

if ($profileData['email'] === '') {
	$profileData['email'] = '-';
}

if ($profileData['mobile_no'] === '') {
	$profileData['mobile_no'] = '-';
}

$rawProfilePhotoPath = trim((string)$profileData['profile_photo_url']);
if ($rawProfilePhotoPath !== '') {
	if (preg_match('#^https?://#i', $rawProfilePhotoPath) === 1 || str_starts_with($rawProfilePhotoPath, '/')) {
		$profileData['profile_photo_public_url'] = $rawProfilePhotoPath;
	} else {
		$profileData['profile_photo_public_url'] = '/hris-system/storage/document/' . ltrim($rawProfilePhotoPath, '/');
	}
}

$pendingPasswordChange = (array)($_SESSION['applicant_profile_password_change'] ?? []);
$pendingExpiresAt = (int)($pendingPasswordChange['expires_at'] ?? 0);
if ($pendingExpiresAt > time()) {
	$passwordChangeStatus['is_pending'] = true;
	$passwordChangeStatus['expires_at'] = formatUnixTimestampForPhilippines($pendingExpiresAt, 'M d, Y h:i A') . ' PST';
	$passwordChangeStatus['email'] = (string)($pendingPasswordChange['email'] ?? '');
}

$personId = cleanText($peopleRow['id'] ?? null);
if ($personId !== null && isValidUuid($personId)) {
	$profileData['person_id'] = $personId;

	$spousesResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/person_family_spouses?select=id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no,sequence_no'
		. '&person_id=eq.' . rawurlencode($personId)
		. '&order=sequence_no.asc&limit=50',
		$headers
	);

	if (isSuccessful($spousesResponse)) {
		$profileSpouses = (array)($spousesResponse['data'] ?? []);
	}

	if (($profileShouldLoadDeferredSections ?? true) === true) {
		$educationsResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/person_educations?select=id,education_level,school_name,course_degree,period_from,period_to,highest_level_units,year_graduated,honors_received,sequence_no'
			. '&person_id=eq.' . rawurlencode($personId)
			. '&order=sequence_no.asc&limit=100',
			$headers
		);

		if (isSuccessful($educationsResponse)) {
			$profileEducations = (array)($educationsResponse['data'] ?? []);
		}

		$workExperienceResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/person_work_experiences?select=id,inclusive_date_from,inclusive_date_to,position_title,office_company,achievements,sequence_no'
			. '&person_id=eq.' . rawurlencode($personId)
			. '&order=sequence_no.asc&limit=100',
			$headers
		);

		if (isSuccessful($workExperienceResponse)) {
			$profileWorkExperiences = (array)($workExperienceResponse['data'] ?? []);
		}
	}
}

$applicantProfileId = cleanText($applicantProfileRow['id'] ?? null);
if ($applicantProfileId !== null && isValidUuid($applicantProfileId)) {
	$applicationsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/applications?select=id,application_ref_no,submitted_at,job:job_postings(title)&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
		. '&order=submitted_at.desc&limit=300',
		$headers
	);

	if (isSuccessful($applicationsResponse)) {
		$applicationRows = (array)($applicationsResponse['data'] ?? []);
		$applicationMeta = [];
		$applicationIds = [];

		foreach ($applicationRows as $applicationRow) {
			$applicationId = cleanText($applicationRow['id'] ?? null);
			if ($applicationId === null || !isValidUuid($applicationId)) {
				continue;
			}

			$applicationIds[] = $applicationId;
			$applicationMeta[$applicationId] = [
				'application_ref_no' => (string)($applicationRow['application_ref_no'] ?? '-'),
				'job_title' => (string)($applicationRow['job']['title'] ?? 'Untitled Position'),
			];
		}

		if (!empty($applicationIds)) {
			$documentsResponse = apiRequest(
				'GET',
				$supabaseUrl
				. '/rest/v1/application_documents?select=id,application_id,document_type,file_url,file_name,mime_type,file_size_bytes,uploaded_at'
				. '&application_id=in.' . rawurlencode('(' . implode(',', $applicationIds) . ')')
				. '&order=uploaded_at.desc&limit=1000',
				$headers
			);

			if (isSuccessful($documentsResponse)) {
				foreach ((array)($documentsResponse['data'] ?? []) as $documentRow) {
					$applicationId = (string)($documentRow['application_id'] ?? '');
					$meta = (array)($applicationMeta[$applicationId] ?? []);

					$uploadedFiles[] = [
						'id' => (string)($documentRow['id'] ?? ''),
						'application_id' => $applicationId,
						'application_ref_no' => (string)($meta['application_ref_no'] ?? '-'),
						'job_title' => (string)($meta['job_title'] ?? 'Untitled Position'),
						'document_type' => (string)($documentRow['document_type'] ?? 'other'),
						'file_url' => (string)($documentRow['file_url'] ?? ''),
						'view_url' => 'applicant-document.php?document_id=' . rawurlencode((string)($documentRow['id'] ?? '')),
						'file_name' => (string)($documentRow['file_name'] ?? 'document'),
						'mime_type' => (string)($documentRow['mime_type'] ?? ''),
						'file_size_bytes' => (int)($documentRow['file_size_bytes'] ?? 0),
						'uploaded_at' => cleanText($documentRow['uploaded_at'] ?? null),
					];
				}
			}
		}

		$loginHistoryResponse = apiRequest(
			'GET',
			$supabaseUrl . '/rest/v1/login_audit_logs?select=id,event_type,auth_provider,ip_address,user_agent,created_at&user_id=eq.' . rawurlencode($applicantUserId) . '&order=created_at.desc&limit=500',
			$headers
		);

		$loginHistoryRowsRaw = isSuccessful($loginHistoryResponse) ? (array)($loginHistoryResponse['data'] ?? []) : [];

		$resolveDeviceLabel = static function (string $userAgent): string {
			$agent = strtolower(trim($userAgent));
			if ($agent === '' || $agent === '-') {
				return 'Unknown Device';
			}

			if (str_contains($agent, 'bot') || str_contains($agent, 'spider') || str_contains($agent, 'crawler')) {
				return 'Bot / Script';
			}

			if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
				return 'Tablet';
			}

			if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
				return 'Mobile';
			}

			return 'Desktop';
		};

		$loginSearchQuery = strtolower(trim((string)($_GET['login_search'] ?? '')));
		$loginEventFilter = trim((string)($_GET['login_event'] ?? ''));
		$loginDeviceFilter = trim((string)($_GET['login_device'] ?? ''));
		$loginPage = max(1, (int)($_GET['login_page'] ?? 1));
		$loginPerPage = 10;

		$loginEventOptions = [];
		$loginDeviceOptions = [];

		$loginHistoryRows = [];
		foreach ($loginHistoryRowsRaw as $entry) {
			$eventType = (string)($entry['event_type'] ?? 'unknown');
			$eventLabel = ucwords(str_replace('_', ' ', $eventType));
			$createdAt = (string)($entry['created_at'] ?? '');
			$userAgent = (string)($entry['user_agent'] ?? '-');
			$deviceLabel = $resolveDeviceLabel($userAgent);

			if ($eventLabel !== '') {
				$loginEventOptions[$eventLabel] = true;
			}
			$loginDeviceOptions[$deviceLabel] = true;

			$loginHistoryRows[] = [
				'event_label' => $eventLabel,
				'auth_provider' => (string)($entry['auth_provider'] ?? 'password'),
				'ip_address' => (string)($entry['ip_address'] ?? 'unknown'),
				'user_agent' => $userAgent,
				'device_label' => $deviceLabel,
				'created_at' => $createdAt !== '' ? formatDateTimeForPhilippines($createdAt, 'M d, Y h:i A') . ' PST' : '-',
				'search_text' => strtolower(trim($eventLabel . ' ' . ((string)($entry['auth_provider'] ?? '')) . ' ' . ((string)($entry['ip_address'] ?? '')) . ' ' . $userAgent . ' ' . $deviceLabel)),
			];
		}

		$loginHistoryRowsFiltered = array_values(array_filter(
			$loginHistoryRows,
			static function (array $row) use ($loginSearchQuery, $loginEventFilter, $loginDeviceFilter): bool {
				if ($loginEventFilter !== '' && (string)($row['event_label'] ?? '') !== $loginEventFilter) {
					return false;
				}

				if ($loginDeviceFilter !== '' && (string)($row['device_label'] ?? '') !== $loginDeviceFilter) {
					return false;
				}

				if ($loginSearchQuery !== '' && !str_contains((string)($row['search_text'] ?? ''), $loginSearchQuery)) {
					return false;
				}

				return true;
			}
		));

		$loginHistoryTotal = count($loginHistoryRowsFiltered);
		$loginTotalPages = max(1, (int)ceil($loginHistoryTotal / $loginPerPage));
		$loginPage = min($loginPage, $loginTotalPages);
		$loginOffset = ($loginPage - 1) * $loginPerPage;
		$loginHistoryRows = array_slice($loginHistoryRowsFiltered, $loginOffset, $loginPerPage);

		$loginEventOptions = array_keys($loginEventOptions);
		sort($loginEventOptions);
		$loginDeviceOptions = array_keys($loginDeviceOptions);
		sort($loginDeviceOptions);
	}
}
