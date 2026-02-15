<?php

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no,created_at&order=created_at.desc&limit=1500',
    $headers
);

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,hire_date,separation_reason,created_at&order=created_at.desc&limit=2500',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,is_active&order=office_name.asc&limit=500',
    $headers
);

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=500',
    $headers
);

$accountsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status&limit=2000',
    $headers
);

$addressesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_addresses?select=id,person_id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,country,is_primary&limit=6000',
    $headers
);

$governmentIdsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_government_ids?select=id,person_id,id_type,id_value_encrypted,last4&limit=6000',
    $headers
);

$spousesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_family_spouses?select=id,person_id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no,sequence_no&order=sequence_no.asc&limit=4000',
    $headers
);

$childrenResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_family_children?select=id,person_id,full_name,birth_date,sequence_no&order=sequence_no.asc&limit=8000',
    $headers
);

$parentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_parents?select=id,person_id,parent_type,surname,first_name,middle_name,extension_name&limit=4000',
    $headers
);

$civilServiceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_civil_service_eligibilities?select=id,person_id,eligibility_name,rating,exam_date,exam_place,license_no,license_validity,sequence_no&order=sequence_no.asc&limit=5000',
    $headers
);

$workExperienceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_work_experiences?select=id,person_id,inclusive_date_from,inclusive_date_to,position_title,office_company,monthly_salary,salary_grade_step,appointment_status,is_government_service,separation_reason,achievements,sequence_no&order=sequence_no.asc&limit=7000',
    $headers
);

$educationalBackgroundResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_educational_backgrounds?select=id,person_id,education_level,school_name,degree_course,attendance_from_year,attendance_to_year,highest_level_units_earned,year_graduated,scholarship_honors_received,sequence_no&order=sequence_no.asc&limit=9000',
    $headers
);

$peopleRows = isSuccessful($peopleResponse) ? (array)$peopleResponse['data'] : [];
$employmentRows = isSuccessful($employmentResponse) ? (array)$employmentResponse['data'] : [];
$officeRows = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];
$positionRows = isSuccessful($positionsResponse) ? (array)$positionsResponse['data'] : [];
$accountRows = isSuccessful($accountsResponse) ? (array)$accountsResponse['data'] : [];
$addressRows = isSuccessful($addressesResponse) ? (array)$addressesResponse['data'] : [];
$governmentIdRows = isSuccessful($governmentIdsResponse) ? (array)$governmentIdsResponse['data'] : [];
$spouseRows = isSuccessful($spousesResponse) ? (array)$spousesResponse['data'] : [];
$childrenRows = isSuccessful($childrenResponse) ? (array)$childrenResponse['data'] : [];
$parentRows = isSuccessful($parentsResponse) ? (array)$parentsResponse['data'] : [];
$civilServiceRows = isSuccessful($civilServiceResponse) ? (array)$civilServiceResponse['data'] : [];
$workExperienceRows = isSuccessful($workExperienceResponse) ? (array)$workExperienceResponse['data'] : [];
$educationalBackgroundRows = isSuccessful($educationalBackgroundResponse) ? (array)$educationalBackgroundResponse['data'] : [];

$filterKeywordInput = (string)(cleanText($_GET['keyword'] ?? null) ?? '');
$filterDepartment = (string)(cleanText($_GET['department'] ?? null) ?? '');
$filterStatusInput = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));

$filterKeyword = strtolower(trim($filterKeywordInput));
$filterStatus = in_array($filterStatusInput, ['active', 'inactive'], true) ? $filterStatusInput : '';

$dataLoadError = null;
$responseChecks = [
    ['people', $peopleResponse],
    ['employment records', $employmentResponse],
    ['offices', $officesResponse],
    ['job positions', $positionsResponse],
    ['user accounts', $accountsResponse],
    ['person addresses', $addressesResponse],
    ['government ids', $governmentIdsResponse],
    ['spouses', $spousesResponse],
    ['children', $childrenResponse],
    ['parents', $parentsResponse],
    ['civil service eligibilities', $civilServiceResponse],
    ['work experiences', $workExperienceResponse],
    ['educational backgrounds', $educationalBackgroundResponse],
];

foreach ($responseChecks as [$label, $response]) {
    if (isSuccessful($response)) {
        continue;
    }

    $message = ucfirst((string)$label) . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
}

$officeNameById = [];
foreach ($officeRows as $office) {
    $officeId = (string)($office['id'] ?? '');
    if ($officeId === '') {
        continue;
    }
    $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Department');
}

$positionNameById = [];
foreach ($positionRows as $position) {
    $positionId = (string)($position['id'] ?? '');
    if ($positionId === '') {
        continue;
    }
    $positionNameById[$positionId] = (string)($position['position_title'] ?? 'Unassigned Position');
}

$accountByUserId = [];
foreach ($accountRows as $account) {
    $userId = (string)($account['id'] ?? '');
    if ($userId === '') {
        continue;
    }
    $accountByUserId[$userId] = [
        'email' => (string)($account['email'] ?? ''),
        'account_status' => (string)($account['account_status'] ?? 'pending'),
    ];
}

$currentEmploymentByPerson = [];
foreach ($employmentRows as $employment) {
    $personId = (string)($employment['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $isCurrent = (bool)($employment['is_current'] ?? false);
    if ($isCurrent || !isset($currentEmploymentByPerson[$personId])) {
        $currentEmploymentByPerson[$personId] = $employment;
    }
}

$addressesByPerson = [];
foreach ($addressRows as $address) {
    $personId = (string)($address['person_id'] ?? '');
    $addressType = strtolower((string)($address['address_type'] ?? ''));

    if ($personId === '' || !in_array($addressType, ['residential', 'permanent'], true)) {
        continue;
    }

    if (!isset($addressesByPerson[$personId])) {
        $addressesByPerson[$personId] = [];
    }

    $isPrimary = (bool)($address['is_primary'] ?? false);
    if (!isset($addressesByPerson[$personId][$addressType]) || $isPrimary) {
        $addressesByPerson[$personId][$addressType] = [
            'id' => (string)($address['id'] ?? ''),
            'house_no' => (string)($address['house_no'] ?? ''),
            'street' => (string)($address['street'] ?? ''),
            'subdivision' => (string)($address['subdivision'] ?? ''),
            'barangay' => (string)($address['barangay'] ?? ''),
            'city_municipality' => (string)($address['city_municipality'] ?? ''),
            'province' => (string)($address['province'] ?? ''),
            'zip_code' => (string)($address['zip_code'] ?? ''),
            'country' => (string)($address['country'] ?? 'Philippines'),
        ];
    }
}

$governmentIdsByPerson = [];
foreach ($governmentIdRows as $governmentId) {
    $personId = (string)($governmentId['person_id'] ?? '');
    $idType = strtolower((string)($governmentId['id_type'] ?? ''));
    if ($personId === '' || $idType === '') {
        continue;
    }

    if (!isset($governmentIdsByPerson[$personId])) {
        $governmentIdsByPerson[$personId] = [];
    }

    $governmentIdsByPerson[$personId][$idType] = (string)($governmentId['id_value_encrypted'] ?? '');
}

$spouseByPerson = [];
foreach ($spouseRows as $spouse) {
    $personId = (string)($spouse['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    if (!isset($spouseByPerson[$personId])) {
        $spouseByPerson[$personId] = [
            'surname' => (string)($spouse['surname'] ?? ''),
            'first_name' => (string)($spouse['first_name'] ?? ''),
            'middle_name' => (string)($spouse['middle_name'] ?? ''),
            'extension_name' => (string)($spouse['extension_name'] ?? ''),
            'occupation' => (string)($spouse['occupation'] ?? ''),
            'employer_business_name' => (string)($spouse['employer_business_name'] ?? ''),
            'business_address' => (string)($spouse['business_address'] ?? ''),
            'telephone_no' => (string)($spouse['telephone_no'] ?? ''),
        ];
    }
}

$childrenByPerson = [];
foreach ($childrenRows as $child) {
    $personId = (string)($child['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }
    if (!isset($childrenByPerson[$personId])) {
        $childrenByPerson[$personId] = [];
    }

    $childrenByPerson[$personId][] = [
        'full_name' => (string)($child['full_name'] ?? ''),
        'birth_date' => (string)($child['birth_date'] ?? ''),
        'sequence_no' => (int)($child['sequence_no'] ?? 1),
    ];
}

$parentsByPerson = [];
foreach ($parentRows as $parent) {
    $personId = (string)($parent['person_id'] ?? '');
    $parentType = strtolower((string)($parent['parent_type'] ?? ''));

    if ($personId === '' || !in_array($parentType, ['father', 'mother'], true)) {
        continue;
    }

    if (!isset($parentsByPerson[$personId])) {
        $parentsByPerson[$personId] = [];
    }

    $parentsByPerson[$personId][$parentType] = [
        'surname' => (string)($parent['surname'] ?? ''),
        'first_name' => (string)($parent['first_name'] ?? ''),
        'middle_name' => (string)($parent['middle_name'] ?? ''),
        'extension_name' => (string)($parent['extension_name'] ?? ''),
    ];
}

$civilServiceByPerson = [];
foreach ($civilServiceRows as $eligibility) {
    $personId = (string)($eligibility['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    if (!isset($civilServiceByPerson[$personId])) {
        $civilServiceByPerson[$personId] = [];
    }

    $civilServiceByPerson[$personId][] = [
        'id' => (string)($eligibility['id'] ?? ''),
        'eligibility_name' => (string)($eligibility['eligibility_name'] ?? ''),
        'rating' => (string)($eligibility['rating'] ?? ''),
        'exam_date' => (string)($eligibility['exam_date'] ?? ''),
        'exam_place' => (string)($eligibility['exam_place'] ?? ''),
        'license_no' => (string)($eligibility['license_no'] ?? ''),
        'license_validity' => (string)($eligibility['license_validity'] ?? ''),
        'sequence_no' => (int)($eligibility['sequence_no'] ?? 1),
    ];
}

$workExperienceByPerson = [];
foreach ($workExperienceRows as $experience) {
    $personId = (string)($experience['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    if (!isset($workExperienceByPerson[$personId])) {
        $workExperienceByPerson[$personId] = [];
    }

    $workExperienceByPerson[$personId][] = [
        'id' => (string)($experience['id'] ?? ''),
        'inclusive_date_from' => (string)($experience['inclusive_date_from'] ?? ''),
        'inclusive_date_to' => (string)($experience['inclusive_date_to'] ?? ''),
        'position_title' => (string)($experience['position_title'] ?? ''),
        'office_company' => (string)($experience['office_company'] ?? ''),
        'monthly_salary' => isset($experience['monthly_salary']) ? (string)$experience['monthly_salary'] : '',
        'salary_grade_step' => (string)($experience['salary_grade_step'] ?? ''),
        'appointment_status' => (string)($experience['appointment_status'] ?? ''),
        'is_government_service' => isset($experience['is_government_service']) ? (bool)$experience['is_government_service'] : null,
        'separation_reason' => (string)($experience['separation_reason'] ?? ''),
        'achievements' => (string)($experience['achievements'] ?? ''),
        'sequence_no' => (int)($experience['sequence_no'] ?? 1),
    ];
}

$educationalBackgroundByPerson = [];
foreach ($educationalBackgroundRows as $educationRow) {
    $personId = (string)($educationRow['person_id'] ?? '');
    $educationLevel = strtolower((string)($educationRow['education_level'] ?? ''));
    if ($personId === '' || $educationLevel === '') {
        continue;
    }

    if (!isset($educationalBackgroundByPerson[$personId])) {
        $educationalBackgroundByPerson[$personId] = [];
    }

    $educationalBackgroundByPerson[$personId][$educationLevel] = [
        'id' => (string)($educationRow['id'] ?? ''),
        'education_level' => $educationLevel,
        'school_name' => (string)($educationRow['school_name'] ?? ''),
        'degree_course' => (string)($educationRow['degree_course'] ?? ''),
        'attendance_from_year' => isset($educationRow['attendance_from_year']) ? (string)$educationRow['attendance_from_year'] : '',
        'attendance_to_year' => isset($educationRow['attendance_to_year']) ? (string)$educationRow['attendance_to_year'] : '',
        'highest_level_units_earned' => (string)($educationRow['highest_level_units_earned'] ?? ''),
        'year_graduated' => isset($educationRow['year_graduated']) ? (string)$educationRow['year_graduated'] : '',
        'scholarship_honors_received' => (string)($educationRow['scholarship_honors_received'] ?? ''),
        'sequence_no' => (int)($educationRow['sequence_no'] ?? 1),
    ];
}

$employeeTableRows = [];
$employeesForSelect = [];
$departmentFilters = [];

$totalProfiles = count($peopleRows);
$completeRecords = 0;
$activeEmployees = 0;
$inactiveEmployees = 0;

foreach ($peopleRows as $person) {
    $personId = (string)($person['id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $firstName = trim((string)($person['first_name'] ?? ''));
    $middleName = trim((string)($person['middle_name'] ?? ''));
    $surname = trim((string)($person['surname'] ?? ''));
    $nameExtension = trim((string)($person['name_extension'] ?? ''));
    $fullName = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $nameExtension);
    if ($fullName === '') {
        $fullName = 'Unknown Employee';
    }

    $employment = $currentEmploymentByPerson[$personId] ?? null;
    $officeId = (string)($employment['office_id'] ?? '');
    $positionId = (string)($employment['position_id'] ?? '');
    $employmentStatus = strtolower((string)($employment['employment_status'] ?? 'inactive'));
    $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Department');
    $positionName = (string)($positionNameById[$positionId] ?? 'Unassigned Position');

    $statusLabel = $employmentStatus === 'active' ? 'Active' : 'Inactive';
    if ($employmentStatus === 'active') {
        $activeEmployees++;
    } else {
        $inactiveEmployees++;
    }

    $email = (string)($accountByUserId[(string)($person['user_id'] ?? '')]['email'] ?? ($person['personal_email'] ?? ''));
    $mobile = (string)($person['mobile_no'] ?? '');
    $telephoneNo = (string)($person['telephone_no'] ?? '');
    $dateOfBirth = (string)($person['date_of_birth'] ?? '');
    $placeOfBirth = (string)($person['place_of_birth'] ?? '');
    $sexAtBirth = (string)($person['sex_at_birth'] ?? '');
    $civilStatus = (string)($person['civil_status'] ?? '');
    $heightM = isset($person['height_m']) ? (string)$person['height_m'] : '';
    $weightKg = isset($person['weight_kg']) ? (string)$person['weight_kg'] : '';
    $bloodType = (string)($person['blood_type'] ?? '');
    $citizenship = (string)($person['citizenship'] ?? '');
    $dualCitizenship = isset($person['dual_citizenship']) ? (bool)$person['dual_citizenship'] : false;
    $dualCitizenshipCountry = (string)($person['dual_citizenship_country'] ?? '');

    $residentialAddress = $addressesByPerson[$personId]['residential'] ?? [];
    $permanentAddress = $addressesByPerson[$personId]['permanent'] ?? [];
    $personGovernmentIds = $governmentIdsByPerson[$personId] ?? [];
    $spouse = $spouseByPerson[$personId] ?? [];
    $children = $childrenByPerson[$personId] ?? [];
    $parents = $parentsByPerson[$personId] ?? [];
    $father = $parents['father'] ?? [];
    $mother = $parents['mother'] ?? [];
    $educationalBackground = $educationalBackgroundByPerson[$personId] ?? [];
    $employeeCode = (string)($person['agency_employee_no'] ?? '');
    if ($employeeCode === '') {
        $employeeCode = 'EMP-' . strtoupper(substr(str_replace('-', '', $personId), 0, 6));
    }

    $isComplete = $email !== '' && $mobile !== '' && !empty($employment);
    if ($isComplete) {
        $completeRecords++;
    }

    $departmentFilters[$departmentName] = true;

    $searchText = strtolower(trim($employeeCode . ' ' . $fullName . ' ' . $departmentName . ' ' . $positionName . ' ' . $statusLabel . ' ' . $email . ' ' . $mobile));

    $employeeTableRows[] = [
        'person_id' => $personId,
        'employee_code' => $employeeCode,
        'full_name' => $fullName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'surname' => $surname,
        'name_extension' => $nameExtension,
        'date_of_birth' => $dateOfBirth,
        'place_of_birth' => $placeOfBirth,
        'sex_at_birth' => $sexAtBirth,
        'civil_status' => $civilStatus,
        'height_m' => $heightM,
        'weight_kg' => $weightKg,
        'blood_type' => $bloodType,
        'citizenship' => $citizenship,
        'dual_citizenship' => $dualCitizenship,
        'dual_citizenship_country' => $dualCitizenshipCountry,
        'telephone_no' => $telephoneNo,
        'residential_house_no' => (string)($residentialAddress['house_no'] ?? ''),
        'residential_street' => (string)($residentialAddress['street'] ?? ''),
        'residential_subdivision' => (string)($residentialAddress['subdivision'] ?? ''),
        'residential_barangay' => (string)($residentialAddress['barangay'] ?? ''),
        'residential_city_municipality' => (string)($residentialAddress['city_municipality'] ?? ''),
        'residential_province' => (string)($residentialAddress['province'] ?? ''),
        'residential_zip_code' => (string)($residentialAddress['zip_code'] ?? ''),
        'permanent_house_no' => (string)($permanentAddress['house_no'] ?? ''),
        'permanent_street' => (string)($permanentAddress['street'] ?? ''),
        'permanent_subdivision' => (string)($permanentAddress['subdivision'] ?? ''),
        'permanent_barangay' => (string)($permanentAddress['barangay'] ?? ''),
        'permanent_city_municipality' => (string)($permanentAddress['city_municipality'] ?? ''),
        'permanent_province' => (string)($permanentAddress['province'] ?? ''),
        'permanent_zip_code' => (string)($permanentAddress['zip_code'] ?? ''),
        'umid_no' => (string)($personGovernmentIds['umid'] ?? ''),
        'pagibig_no' => (string)($personGovernmentIds['pagibig'] ?? ''),
        'philhealth_no' => (string)($personGovernmentIds['philhealth'] ?? ''),
        'psn_no' => (string)($personGovernmentIds['psn'] ?? ''),
        'tin_no' => (string)($personGovernmentIds['tin'] ?? ''),
        'spouse_surname' => (string)($spouse['surname'] ?? ''),
        'spouse_first_name' => (string)($spouse['first_name'] ?? ''),
        'spouse_middle_name' => (string)($spouse['middle_name'] ?? ''),
        'spouse_extension_name' => (string)($spouse['extension_name'] ?? ''),
        'spouse_occupation' => (string)($spouse['occupation'] ?? ''),
        'spouse_employer_business_name' => (string)($spouse['employer_business_name'] ?? ''),
        'spouse_business_address' => (string)($spouse['business_address'] ?? ''),
        'spouse_telephone_no' => (string)($spouse['telephone_no'] ?? ''),
        'father_surname' => (string)($father['surname'] ?? ''),
        'father_first_name' => (string)($father['first_name'] ?? ''),
        'father_middle_name' => (string)($father['middle_name'] ?? ''),
        'father_extension_name' => (string)($father['extension_name'] ?? ''),
        'mother_surname' => (string)($mother['surname'] ?? ''),
        'mother_first_name' => (string)($mother['first_name'] ?? ''),
        'mother_middle_name' => (string)($mother['middle_name'] ?? ''),
        'mother_extension_name' => (string)($mother['extension_name'] ?? ''),
        'children' => $children,
        'department' => $departmentName,
        'position' => $positionName,
        'status_label' => $statusLabel,
        'status_raw' => $employmentStatus,
        'email' => $email,
        'mobile' => $mobile,
        'agency_employee_no' => $employeeCode,
        'search_text' => $searchText,
        'civil_service_eligibilities' => $civilServiceByPerson[$personId] ?? [],
        'work_experiences' => $workExperienceByPerson[$personId] ?? [],
        'educational_backgrounds' => $educationalBackground,
    ];

    $employeesForSelect[] = [
        'person_id' => $personId,
        'name' => $fullName,
        'employee_code' => $employeeCode,
    ];
}

$needsUpdateCount = max(0, $totalProfiles - $completeRecords);

$departmentFilterOptions = array_keys($departmentFilters);
sort($departmentFilterOptions);

usort($employeesForSelect, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

if ($filterKeyword !== '' || $filterDepartment !== '' || $filterStatus !== '') {
    $employeeTableRows = array_values(array_filter($employeeTableRows, static function (array $row) use ($filterKeyword, $filterDepartment, $filterStatus): bool {
        $matchesKeyword = true;
        $matchesDepartment = true;
        $matchesStatus = true;

        if ($filterKeyword !== '') {
            $haystack = strtolower((string)($row['search_text'] ?? ''));
            $matchesKeyword = str_contains($haystack, $filterKeyword);
        }

        if ($filterDepartment !== '') {
            $matchesDepartment = strcasecmp((string)($row['department'] ?? ''), $filterDepartment) === 0;
        }

        if ($filterStatus !== '') {
            $matchesStatus = strtolower((string)($row['status_raw'] ?? '')) === $filterStatus;
        }

        return $matchesKeyword && $matchesDepartment && $matchesStatus;
    }));
}

$filteredProfileCount = count($employeeTableRows);
