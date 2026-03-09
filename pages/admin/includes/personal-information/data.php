<?php

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no,profile_photo_url,created_at&order=created_at.desc&limit=1500',
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
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&is_active=eq.true&order=position_title.asc&limit=500',
    $headers
);

$accountsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status&limit=2000',
    $headers
);

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=id,user_id,role_id,is_primary,assigned_at&limit=5000',
    $headers
);

$rolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=id,role_key,role_name&limit=200',
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
$roleAssignmentRows = isSuccessful($roleAssignmentsResponse) ? (array)$roleAssignmentsResponse['data'] : [];
$roleRows = isSuccessful($rolesResponse) ? (array)$rolesResponse['data'] : [];
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
    ['user role assignments', $roleAssignmentsResponse],
    ['roles', $rolesResponse],
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
    $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
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

$roleById = [];
foreach ($roleRows as $roleRow) {
    $roleId = (string)($roleRow['id'] ?? '');
    if ($roleId === '') {
        continue;
    }

    $roleById[$roleId] = [
        'role_key' => strtolower(trim((string)($roleRow['role_key'] ?? ''))),
        'role_name' => trim((string)($roleRow['role_name'] ?? '')),
    ];
}

$primaryRoleByUserId = [];
foreach ($roleAssignmentRows as $assignmentRow) {
    $userId = (string)($assignmentRow['user_id'] ?? '');
    $roleId = (string)($assignmentRow['role_id'] ?? '');
    if ($userId === '' || $roleId === '' || !isset($roleById[$roleId])) {
        continue;
    }

    $currentPriority = isset($primaryRoleByUserId[$userId])
        ? (int)($primaryRoleByUserId[$userId]['priority'] ?? 99)
        : 99;
    $isPrimary = (bool)($assignmentRow['is_primary'] ?? false);
    $nextPriority = $isPrimary ? 1 : 2;

    if ($nextPriority > $currentPriority) {
        continue;
    }

    $primaryRoleByUserId[$userId] = [
        'priority' => $nextPriority,
        'role_key' => (string)$roleById[$roleId]['role_key'],
        'role_name' => (string)$roleById[$roleId]['role_name'],
    ];
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

$resolveProfilePhotoUrl = static function (?string $rawPath): string {
    $path = trim((string)$rawPath);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '/')) {
        return $path;
    }

    return '/hris-system/storage/document/' . ltrim($path, '/');
};

$employeeTableRows = [];
$employeesForSelect = [];
$staffAccountCandidates = [];
$departmentFilters = [];
$personNameByIdAll = [];

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
    $personNameByIdAll[$personId] = $fullName;

    $employment = $currentEmploymentByPerson[$personId] ?? null;
    $officeId = (string)($employment['office_id'] ?? '');
    $positionId = (string)($employment['position_id'] ?? '');
    $employmentStatus = strtolower((string)($employment['employment_status'] ?? 'inactive'));
    $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
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
    $profilePhotoUrl = $resolveProfilePhotoUrl((string)($person['profile_photo_url'] ?? ''));
    if ($employeeCode === '') {
        $employeeCode = 'EMP-' . strtoupper(substr(str_replace('-', '', $personId), 0, 6));
    }

    $personUserIdRaw = trim((string)($person['user_id'] ?? ''));
    $roleMeta = $personUserIdRaw !== '' ? ($primaryRoleByUserId[$personUserIdRaw] ?? null) : null;
    $roleKey = strtolower(trim((string)($roleMeta['role_key'] ?? '')));
    $roleName = trim((string)($roleMeta['role_name'] ?? ''));
    if ($roleName === '') {
        if ($roleKey === 'staff') {
            $roleName = 'Staff';
        } elseif ($roleKey === 'employee') {
            $roleName = 'Employee';
        } else {
            $roleName = 'Employee';
        }
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
        'profile_photo_url' => $profilePhotoUrl,
        'role_key' => $roleKey,
        'role_name' => $roleName,
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

    $personUserId = cleanText($person['user_id'] ?? null);
    if ($personUserId === null || !preg_match('/^[a-f0-9-]{36}$/i', $personUserId)) {
        $staffAccountCandidates[] = [
            'person_id' => $personId,
            'name' => $fullName,
            'employee_code' => $employeeCode,
            'email' => $email,
            'office_id' => $officeId,
            'office_name' => $departmentName,
        ];
    }
}

$needsUpdateCount = max(0, $totalProfiles - $completeRecords);

$departmentFilterOptions = array_keys($departmentFilters);
sort($departmentFilterOptions);

usort($employeesForSelect, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

usort($staffAccountCandidates, static function (array $left, array $right): int {
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

$spouseRequestRows = [];
if (!empty($personNameByIdAll)) {
    $spouseRequestResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,actor_user_id,entity_id,created_at,new_data'
        . '&module_name=eq.employee'
        . '&entity_name=eq.person_family_spouses_request'
        . '&action_name=eq.submit_spouse_addition_request'
        . '&order=created_at.desc&limit=300',
        $headers
    );

    if (isSuccessful($spouseRequestResponse)) {
        $requestRows = (array)($spouseRequestResponse['data'] ?? []);
        $requestIdSet = [];
        $actorIdSet = [];

        foreach ($requestRows as $requestRowRaw) {
            $requestRow = (array)$requestRowRaw;
            $requestId = (string)($requestRow['id'] ?? '');
            if ($requestId !== '') {
                $requestIdSet[$requestId] = true;
            }

            $actorId = (string)($requestRow['actor_user_id'] ?? '');
            if ($actorId !== '' && preg_match('/^[a-f0-9-]{36}$/i', $actorId)) {
                $actorIdSet[$actorId] = true;
            }
        }

        $decisionByRequestId = [];
        $decisionResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=id,created_at,new_data,action_name'
            . '&module_name=eq.personal_information'
            . '&entity_name=eq.person_family_spouses_request'
            . '&action_name=in.(approve_spouse_addition_request,reject_spouse_addition_request)'
            . '&order=created_at.desc&limit=600',
            $headers
        );

        if (isSuccessful($decisionResponse)) {
            foreach ((array)($decisionResponse['data'] ?? []) as $decisionRaw) {
                $decisionRow = (array)$decisionRaw;
                $decisionData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
                $sourceRequestId = (string)($decisionData['request_log_id'] ?? '');
                if ($sourceRequestId === '' || !isset($requestIdSet[$sourceRequestId]) || isset($decisionByRequestId[$sourceRequestId])) {
                    continue;
                }

                $decisionByRequestId[$sourceRequestId] = [
                    'status' => strtolower((string)($decisionData['status'] ?? '')),
                    'remarks' => (string)($decisionData['remarks'] ?? ''),
                    'reviewed_at' => (string)($decisionRow['created_at'] ?? ''),
                ];
            }
        }

        $actorEmailById = [];
        if (!empty($actorIdSet)) {
            $actorFilter = implode(',', array_map('rawurlencode', array_keys($actorIdSet)));
            $actorResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . $actorFilter . ')&limit=500',
                $headers
            );

            if (isSuccessful($actorResponse)) {
                foreach ((array)($actorResponse['data'] ?? []) as $actorRaw) {
                    $actorRow = (array)$actorRaw;
                    $actorId = (string)($actorRow['id'] ?? '');
                    if ($actorId === '') {
                        continue;
                    }
                    $actorEmailById[$actorId] = (string)($actorRow['email'] ?? 'Employee');
                }
            }
        }

        foreach ($requestRows as $requestRowRaw) {
            $requestRow = (array)$requestRowRaw;
            $requestId = (string)($requestRow['id'] ?? '');
            $personId = (string)($requestRow['entity_id'] ?? '');

            if ($requestId === '' || $personId === '' || !isset($personNameByIdAll[$personId]) || isset($decisionByRequestId[$requestId])) {
                continue;
            }

            $newData = is_array($requestRow['new_data'] ?? null) ? (array)$requestRow['new_data'] : [];
            $submittedAtRaw = (string)($requestRow['created_at'] ?? '');
            $submittedAt = $submittedAtRaw !== '' ? strtotime($submittedAtRaw) : false;
            $actorId = (string)($requestRow['actor_user_id'] ?? '');
            $submittedBy = $actorEmailById[$actorId] ?? 'Employee';
            $supportingDocumentPath = trim((string)($newData['supporting_document_path'] ?? ''));
            $supportingDocumentUrl = $supportingDocumentPath !== ''
                ? '/hris-system/storage/document/' . ltrim($supportingDocumentPath, '/')
                : '';

            $spouseRequestRows[] = [
                'request_log_id' => $requestId,
                'person_id' => $personId,
                'employee_name' => (string)$personNameByIdAll[$personId],
                'submitted_by' => $submittedBy,
                'submitted_at_label' => $submittedAt ? date('M d, Y h:i A', $submittedAt) : '-',
                'submitted_at_date' => $submittedAt ? date('Y-m-d', $submittedAt) : '',
                'spouse_name' => trim((string)($newData['spouse_first_name'] ?? '') . ' ' . (string)($newData['spouse_surname'] ?? '')),
                'request_notes' => (string)($newData['request_notes'] ?? ''),
                'attachment_name' => (string)($newData['supporting_document_name'] ?? ''),
                'attachment_url' => $supportingDocumentUrl,
            ];
        }
    }
}

$personNameById = [];
foreach ($employeeTableRows as $row) {
    $personId = (string)($row['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $personNameById[$personId] = (string)($row['full_name'] ?? 'Unknown Employee');
}

$recommendationHistoryRows = [];
if (!empty($personNameById)) {
    $personIdFilter = implode(',', array_map(static fn(string $id): string => rawurlencode($id), array_keys($personNameById)));
    $recommendationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,created_at,new_data,module_name,action_name,entity_name'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&action_name=eq.recommend_employee_profile_update'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=200',
        $headers
    );

    if (isSuccessful($recommendationResponse)) {
        $recommendationRows = (array)($recommendationResponse['data'] ?? []);

        $decisionResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at'
            . '&module_name=eq.personal_information'
            . '&entity_name=eq.people'
            . '&action_name=in.(approve_employee_profile_recommendation,reject_employee_profile_recommendation)'
            . '&entity_id=in.(' . $personIdFilter . ')'
            . '&order=created_at.desc&limit=500',
            $headers
        );

        $reviewDecisionByRecommendationId = [];
        if (isSuccessful($decisionResponse)) {
            foreach ((array)($decisionResponse['data'] ?? []) as $decisionRow) {
                $decisionData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
                $sourceRecommendationId = (string)($decisionData['recommendation_log_id'] ?? '');
                if ($sourceRecommendationId === '' || isset($reviewDecisionByRecommendationId[$sourceRecommendationId])) {
                    continue;
                }
                $reviewDecisionByRecommendationId[$sourceRecommendationId] = [
                    'decision' => (string)($decisionData['decision'] ?? ''),
                    'reviewed_at' => (string)($decisionRow['created_at'] ?? ''),
                ];
            }
        }

        $actorIds = [];
        foreach ($recommendationRows as $recommendationRow) {
            $actorId = (string)($recommendationRow['actor_user_id'] ?? '');
            if ($actorId !== '' && preg_match('/^[a-f0-9-]{36}$/i', $actorId)) {
                $actorIds[$actorId] = true;
            }
        }

        $actorEmailById = [];
        if (!empty($actorIds)) {
            $actorFilter = implode(',', array_map('rawurlencode', array_keys($actorIds)));
            $actorResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . $actorFilter . ')&limit=500',
                $headers
            );

            if (isSuccessful($actorResponse)) {
                foreach ((array)($actorResponse['data'] ?? []) as $actorRow) {
                    $actorId = (string)($actorRow['id'] ?? '');
                    if ($actorId === '') {
                        continue;
                    }
                    $actorEmailById[$actorId] = (string)($actorRow['email'] ?? 'Staff');
                }
            }
        }

        foreach ($recommendationRows as $recommendationRow) {
            $recommendationLogId = (string)($recommendationRow['id'] ?? '');
            if ($recommendationLogId === '' || isset($reviewDecisionByRecommendationId[$recommendationLogId])) {
                continue;
            }

            $entityId = (string)($recommendationRow['entity_id'] ?? '');
            if ($entityId === '' || !isset($personNameById[$entityId])) {
                continue;
            }

            $newData = is_array($recommendationRow['new_data'] ?? null) ? (array)$recommendationRow['new_data'] : [];
            $recommendedProfile = is_array($newData['recommended_profile'] ?? null) ? (array)$newData['recommended_profile'] : [];
            $recommendedAddresses = is_array($newData['recommended_addresses'] ?? null) ? (array)$newData['recommended_addresses'] : [];
            $recommendedGovernmentIds = is_array($newData['recommended_government_ids'] ?? null) ? (array)$newData['recommended_government_ids'] : [];
            $recommendedFamily = is_array($newData['recommended_family'] ?? null) ? (array)$newData['recommended_family'] : [];
            $recommendedEducation = is_array($newData['recommended_educational_backgrounds'] ?? null) ? (array)$newData['recommended_educational_backgrounds'] : [];
            $profileFieldCount = count($recommendedProfile);
            $addressFieldCount = 0;
            foreach ($recommendedAddresses as $addressRow) {
                if (!is_array($addressRow)) {
                    continue;
                }
                foreach ($addressRow as $value) {
                    if (trim((string)$value) !== '') {
                        $addressFieldCount++;
                    }
                }
            }
            $governmentFieldCount = 0;
            foreach ($recommendedGovernmentIds as $value) {
                if (trim((string)$value) !== '') {
                    $governmentFieldCount++;
                }
            }
            $familyFieldCount = 0;
            foreach ($recommendedFamily as $value) {
                if (is_array($value)) {
                    $familyFieldCount += count($value);
                    continue;
                }
                if (trim((string)$value) !== '') {
                    $familyFieldCount++;
                }
            }
            $educationCount = count($recommendedEducation);
            $summaryFragments = [];
            if ($profileFieldCount > 0) {
                $summaryFragments[] = $profileFieldCount . ' profile field(s)';
            }
            if ($addressFieldCount > 0) {
                $summaryFragments[] = $addressFieldCount . ' address detail(s)';
            }
            if ($governmentFieldCount > 0) {
                $summaryFragments[] = $governmentFieldCount . ' government ID detail(s)';
            }
            if ($familyFieldCount > 0) {
                $summaryFragments[] = $familyFieldCount . ' family detail(s)';
            }
            if ($educationCount > 0) {
                $summaryFragments[] = $educationCount . ' educational entry(ies)';
            }

            $actorId = (string)($recommendationRow['actor_user_id'] ?? '');
            $submittedBy = $actorEmailById[$actorId] ?? 'Staff';
            $submittedAtRaw = (string)($recommendationRow['created_at'] ?? '');
            $submittedAt = $submittedAtRaw !== '' ? strtotime($submittedAtRaw) : false;
            $submittedAtDate = $submittedAt ? date('Y-m-d', $submittedAt) : '';
            $summaryText = !empty($summaryFragments)
                ? implode(', ', $summaryFragments) . ' recommended for review'
                : 'Profile details recommended for update';
            $searchText = strtolower(trim(implode(' ', [
                $personNameById[$entityId],
                $submittedBy,
                $summaryText,
            ])));

            $recommendationHistoryRows[] = [
                'recommendation_log_id' => $recommendationLogId,
                'person_id' => $entityId,
                'employee_name' => $personNameById[$entityId],
                'submitted_by' => $submittedBy,
                'submitted_at_label' => $submittedAt ? date('M d, Y h:i A', $submittedAt) : '-',
                'submitted_at_date' => $submittedAtDate,
                'status_label' => 'Pending Admin Action',
                'status_class' => 'bg-amber-100 text-amber-800',
                'summary' => $summaryText,
                'proposed_changes' => [
                    'recommended_profile' => $recommendedProfile,
                    'recommended_addresses' => $recommendedAddresses,
                    'recommended_government_ids' => $recommendedGovernmentIds,
                    'recommended_family' => $recommendedFamily,
                    'recommended_educational_backgrounds' => $recommendedEducation,
                ],
                'search_text' => $searchText,
            ];
        }
    }
}

$civilStatusDefaultOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced', 'Annulled'];
$bloodTypeDefaultOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

$placeOfBirthOptionsMap = [];
$civilStatusOptionsMap = array_fill_keys($civilStatusDefaultOptions, true);
$bloodTypeOptionsMap = array_fill_keys($bloodTypeDefaultOptions, true);
$addressCityOptionsMap = [];
$addressProvinceOptionsMap = [];
$addressBarangayOptionsMap = [];

foreach ($employeeTableRows as $row) {
    $placeOfBirthValue = trim((string)($row['place_of_birth'] ?? ''));
    if ($placeOfBirthValue !== '') {
        $placeOfBirthOptionsMap[$placeOfBirthValue] = true;
    }

    $civilStatusValue = trim((string)($row['civil_status'] ?? ''));
    if ($civilStatusValue !== '') {
        $civilStatusOptionsMap[$civilStatusValue] = true;
    }

    $bloodTypeValue = trim((string)($row['blood_type'] ?? ''));
    if ($bloodTypeValue !== '') {
        $bloodTypeOptionsMap[$bloodTypeValue] = true;
    }

    $residentialCityValue = trim((string)($row['residential_city_municipality'] ?? ''));
    if ($residentialCityValue !== '') {
        $addressCityOptionsMap[$residentialCityValue] = true;
    }
    $permanentCityValue = trim((string)($row['permanent_city_municipality'] ?? ''));
    if ($permanentCityValue !== '') {
        $addressCityOptionsMap[$permanentCityValue] = true;
    }

    $residentialProvinceValue = trim((string)($row['residential_province'] ?? ''));
    if ($residentialProvinceValue !== '') {
        $addressProvinceOptionsMap[$residentialProvinceValue] = true;
    }
    $permanentProvinceValue = trim((string)($row['permanent_province'] ?? ''));
    if ($permanentProvinceValue !== '') {
        $addressProvinceOptionsMap[$permanentProvinceValue] = true;
    }

    $residentialBarangayValue = trim((string)($row['residential_barangay'] ?? ''));
    if ($residentialBarangayValue !== '') {
        $addressBarangayOptionsMap[$residentialBarangayValue] = true;
    }
    $permanentBarangayValue = trim((string)($row['permanent_barangay'] ?? ''));
    if ($permanentBarangayValue !== '') {
        $addressBarangayOptionsMap[$permanentBarangayValue] = true;
    }
}

$placeOfBirthOptions = array_keys($placeOfBirthOptionsMap);
sort($placeOfBirthOptions, SORT_NATURAL | SORT_FLAG_CASE);

$civilStatusOptions = array_keys($civilStatusOptionsMap);
sort($civilStatusOptions, SORT_NATURAL | SORT_FLAG_CASE);

$bloodTypeOptions = array_keys($bloodTypeOptionsMap);
sort($bloodTypeOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressCityOptions = array_keys($addressCityOptionsMap);
sort($addressCityOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressProvinceOptions = array_keys($addressProvinceOptionsMap);
sort($addressProvinceOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressBarangayOptions = array_keys($addressBarangayOptionsMap);
sort($addressBarangayOptions, SORT_NATURAL | SORT_FLAG_CASE);
