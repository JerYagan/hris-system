<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$pdsEducationLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];

$employeeProfile = [
    'person_id' => (string)($employeePersonId ?? ''),
    'first_name' => '',
    'last_name' => '',
    'middle_name' => '',
    'name_extension' => '',
    'sex_at_birth' => '',
    'height_m' => '',
    'weight_kg' => '',
    'blood_type' => '',
    'citizenship' => '',
    'dual_citizenship' => '0',
    'dual_citizenship_country' => '',
    'telephone_no' => '',
    'personal_email' => '',
    'mobile_no' => '',
    'date_of_birth' => '',
    'profile_photo_url' => '',
    'profile_photo_public_url' => '',
    'place_of_birth' => '',
    'civil_status' => '',
    'agency_employee_no' => '',
    'address_id' => '',
    'house_no' => '',
    'street' => '',
    'subdivision' => '',
    'barangay' => '',
    'city_municipality' => '',
    'province' => '',
    'zip_code' => '',
    'country' => 'Philippines',
    'permanent_address_id' => '',
    'permanent_house_no' => '',
    'permanent_street' => '',
    'permanent_subdivision' => '',
    'permanent_barangay' => '',
    'permanent_city_municipality' => '',
    'permanent_province' => '',
    'permanent_zip_code' => '',
    'permanent_country' => 'Philippines',
    'permanent_same_as_residential' => false,
    'umid_no' => '',
    'pagibig_no' => '',
    'philhealth_no' => '',
    'psn_no' => '',
    'tin_no' => '',
    'address_line' => '-',
    'employment_position_title' => (string)($employeePositionTitle ?? ''),
    'employment_office_name' => (string)($employeeOfficeName ?? ''),
    'employment_status' => (string)($employeeEmploymentStatus ?? ''),
    'supervisor_name' => '',
];

$employeeSpouse = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
    'occupation' => '',
    'employer_business_name' => '',
    'business_address' => '',
    'telephone_no' => '',
];

$employeeFather = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
];

$employeeMother = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
];

$employeeChildren = [];
$employeeEducationRows = [];
$employeeDocuments = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no,profile_photo_url'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
    $dataLoadError = 'Unable to load your profile information at the moment.';
    return;
}

$personRow = (array)$personResponse['data'][0];
$employeeProfile['first_name'] = (string)($personRow['first_name'] ?? '');
$employeeProfile['last_name'] = (string)($personRow['surname'] ?? '');
$employeeProfile['middle_name'] = (string)($personRow['middle_name'] ?? '');
$employeeProfile['name_extension'] = (string)($personRow['name_extension'] ?? '');
$employeeProfile['sex_at_birth'] = (string)($personRow['sex_at_birth'] ?? '');
$employeeProfile['height_m'] = (string)($personRow['height_m'] ?? '');
$employeeProfile['weight_kg'] = (string)($personRow['weight_kg'] ?? '');
$employeeProfile['blood_type'] = (string)($personRow['blood_type'] ?? '');
$employeeProfile['citizenship'] = (string)($personRow['citizenship'] ?? '');
$employeeProfile['dual_citizenship'] = (bool)($personRow['dual_citizenship'] ?? false) ? '1' : '0';
$employeeProfile['dual_citizenship_country'] = (string)($personRow['dual_citizenship_country'] ?? '');
$employeeProfile['telephone_no'] = (string)($personRow['telephone_no'] ?? '');
$employeeProfile['personal_email'] = (string)($personRow['personal_email'] ?? '');
$employeeProfile['mobile_no'] = (string)($personRow['mobile_no'] ?? '');
$employeeProfile['date_of_birth'] = (string)($personRow['date_of_birth'] ?? '');
$employeeProfile['profile_photo_url'] = (string)($personRow['profile_photo_url'] ?? '');
$employeeProfile['place_of_birth'] = (string)($personRow['place_of_birth'] ?? '');
$employeeProfile['civil_status'] = (string)($personRow['civil_status'] ?? '');
$employeeProfile['agency_employee_no'] = (string)($personRow['agency_employee_no'] ?? '');

$rawProfilePhotoPath = trim((string)$employeeProfile['profile_photo_url']);
if ($rawProfilePhotoPath !== '') {
    if (preg_match('#^https?://#i', $rawProfilePhotoPath) === 1 || str_starts_with($rawProfilePhotoPath, '/')) {
        $employeeProfile['profile_photo_public_url'] = $rawProfilePhotoPath;
    } else {
        $employeeProfile['profile_photo_public_url'] = '/hris-system/storage/document/' . ltrim($rawProfilePhotoPath, '/');
    }
}

$addressResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_addresses?select=id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,country,is_primary'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=is_primary.desc,created_at.desc&limit=20',
    $headers
);

if (isSuccessful($addressResponse)) {
    foreach ((array)($addressResponse['data'] ?? []) as $addressRaw) {
        $addressRow = (array)$addressRaw;
        $addressType = strtolower((string)($addressRow['address_type'] ?? ''));

        if ($addressType === 'residential' || ($addressType === '' && $employeeProfile['address_id'] === '')) {
            $employeeProfile['address_id'] = (string)($addressRow['id'] ?? '');
            $employeeProfile['house_no'] = (string)($addressRow['house_no'] ?? '');
            $employeeProfile['street'] = (string)($addressRow['street'] ?? '');
            $employeeProfile['subdivision'] = (string)($addressRow['subdivision'] ?? '');
            $employeeProfile['barangay'] = (string)($addressRow['barangay'] ?? '');
            $employeeProfile['city_municipality'] = (string)($addressRow['city_municipality'] ?? '');
            $employeeProfile['province'] = (string)($addressRow['province'] ?? '');
            $employeeProfile['zip_code'] = (string)($addressRow['zip_code'] ?? '');
            $employeeProfile['country'] = (string)($addressRow['country'] ?? 'Philippines');
        }

        if ($addressType === 'permanent') {
            $employeeProfile['permanent_address_id'] = (string)($addressRow['id'] ?? '');
            $employeeProfile['permanent_house_no'] = (string)($addressRow['house_no'] ?? '');
            $employeeProfile['permanent_street'] = (string)($addressRow['street'] ?? '');
            $employeeProfile['permanent_subdivision'] = (string)($addressRow['subdivision'] ?? '');
            $employeeProfile['permanent_barangay'] = (string)($addressRow['barangay'] ?? '');
            $employeeProfile['permanent_city_municipality'] = (string)($addressRow['city_municipality'] ?? '');
            $employeeProfile['permanent_province'] = (string)($addressRow['province'] ?? '');
            $employeeProfile['permanent_zip_code'] = (string)($addressRow['zip_code'] ?? '');
            $employeeProfile['permanent_country'] = (string)($addressRow['country'] ?? 'Philippines');
        }
    }

    $parts = [
        cleanText($employeeProfile['house_no']),
        cleanText($employeeProfile['street']),
        cleanText($employeeProfile['subdivision']),
        cleanText($employeeProfile['barangay']),
        cleanText($employeeProfile['city_municipality']),
        cleanText($employeeProfile['province']),
        cleanText($employeeProfile['zip_code']),
        cleanText($employeeProfile['country']),
    ];

    $addressParts = [];
    foreach ($parts as $part) {
        if ($part !== null) {
            $addressParts[] = $part;
        }
    }

    $employeeProfile['address_line'] = !empty($addressParts) ? implode(', ', $addressParts) : '-';

    $residentialComparable = [
        $employeeProfile['house_no'],
        $employeeProfile['street'],
        $employeeProfile['subdivision'],
        $employeeProfile['barangay'],
        $employeeProfile['city_municipality'],
        $employeeProfile['province'],
        $employeeProfile['zip_code'],
        $employeeProfile['country'],
    ];
    $permanentComparable = [
        $employeeProfile['permanent_house_no'],
        $employeeProfile['permanent_street'],
        $employeeProfile['permanent_subdivision'],
        $employeeProfile['permanent_barangay'],
        $employeeProfile['permanent_city_municipality'],
        $employeeProfile['permanent_province'],
        $employeeProfile['permanent_zip_code'],
        $employeeProfile['permanent_country'],
    ];

    $employeeProfile['permanent_same_as_residential'] = $residentialComparable === $permanentComparable;
}

$govIdResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_government_ids?select=id,id_type,id_value_encrypted'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=20',
    $headers
);

if (isSuccessful($govIdResponse)) {
    foreach ((array)($govIdResponse['data'] ?? []) as $govIdRaw) {
        $govId = (array)$govIdRaw;
        $type = strtolower((string)($govId['id_type'] ?? ''));
        $value = (string)($govId['id_value_encrypted'] ?? '');

        if ($type === 'umid') {
            $employeeProfile['umid_no'] = $value;
        } elseif ($type === 'pagibig') {
            $employeeProfile['pagibig_no'] = $value;
        } elseif ($type === 'philhealth') {
            $employeeProfile['philhealth_no'] = $value;
        } elseif ($type === 'psn') {
            $employeeProfile['psn_no'] = $value;
        } elseif ($type === 'tin') {
            $employeeProfile['tin_no'] = $value;
        }
    }
}

$spouseResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_family_spouses?select=id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=sequence_no.asc,created_at.asc&limit=1',
    $headers
);

if (isSuccessful($spouseResponse) && !empty((array)($spouseResponse['data'] ?? []))) {
    $spouseRow = (array)$spouseResponse['data'][0];
    $employeeSpouse = [
        'id' => (string)($spouseRow['id'] ?? ''),
        'surname' => (string)($spouseRow['surname'] ?? ''),
        'first_name' => (string)($spouseRow['first_name'] ?? ''),
        'middle_name' => (string)($spouseRow['middle_name'] ?? ''),
        'extension_name' => (string)($spouseRow['extension_name'] ?? ''),
        'occupation' => (string)($spouseRow['occupation'] ?? ''),
        'employer_business_name' => (string)($spouseRow['employer_business_name'] ?? ''),
        'business_address' => (string)($spouseRow['business_address'] ?? ''),
        'telephone_no' => (string)($spouseRow['telephone_no'] ?? ''),
    ];
}

$parentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_parents?select=id,parent_type,surname,first_name,middle_name,extension_name'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=5',
    $headers
);

if (isSuccessful($parentsResponse)) {
    foreach ((array)($parentsResponse['data'] ?? []) as $parentRaw) {
        $parent = (array)$parentRaw;
        $mapped = [
            'id' => (string)($parent['id'] ?? ''),
            'surname' => (string)($parent['surname'] ?? ''),
            'first_name' => (string)($parent['first_name'] ?? ''),
            'middle_name' => (string)($parent['middle_name'] ?? ''),
            'extension_name' => (string)($parent['extension_name'] ?? ''),
        ];

        if ((string)($parent['parent_type'] ?? '') === 'father') {
            $employeeFather = $mapped;
        }
        if ((string)($parent['parent_type'] ?? '') === 'mother') {
            $employeeMother = $mapped;
        }
    }
}

$childrenResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_family_children?select=id,full_name,birth_date,sequence_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=sequence_no.asc,created_at.asc&limit=200',
    $headers
);

if (isSuccessful($childrenResponse)) {
    foreach ((array)($childrenResponse['data'] ?? []) as $childRaw) {
        $child = (array)$childRaw;
        $employeeChildren[] = [
            'id' => (string)($child['id'] ?? ''),
            'full_name' => (string)($child['full_name'] ?? ''),
            'birth_date' => (string)($child['birth_date'] ?? ''),
        ];
    }
}

$educationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_educations?select=id,education_level,school_name,course_degree,period_from,period_to,highest_level_units,year_graduated,honors_received,sequence_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=education_level.asc,sequence_no.asc,created_at.asc&limit=400',
    $headers
);

if (isSuccessful($educationResponse)) {
    foreach ((array)($educationResponse['data'] ?? []) as $educationRaw) {
        $education = (array)$educationRaw;
        $employeeEducationRows[] = [
            'id' => (string)($education['id'] ?? ''),
            'education_level' => strtolower((string)($education['education_level'] ?? '')),
            'school_name' => (string)($education['school_name'] ?? ''),
            'course_degree' => (string)($education['course_degree'] ?? ''),
            'period_from' => (string)($education['period_from'] ?? ''),
            'period_to' => (string)($education['period_to'] ?? ''),
            'highest_level_units' => (string)($education['highest_level_units'] ?? ''),
            'year_graduated' => (string)($education['year_graduated'] ?? ''),
            'honors_received' => (string)($education['honors_received'] ?? ''),
            'sequence_no' => (int)($education['sequence_no'] ?? 1),
        ];
    }
}

if (empty($employeeEducationRows)) {
    foreach ($pdsEducationLevels as $level) {
        $employeeEducationRows[] = [
            'id' => '',
            'education_level' => $level,
            'school_name' => '',
            'course_degree' => '',
            'period_from' => '',
            'period_to' => '',
            'highest_level_units' => '',
            'year_graduated' => '',
            'honors_received' => '',
            'sequence_no' => 1,
        ];
    }
}

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,employment_status,office:offices(office_name),position:job_positions(position_title),supervisor:people!employment_records_immediate_supervisor_person_id_fkey(first_name,surname)'
    . '&id=eq.' . rawurlencode((string)$employeeEmploymentId)
    . '&limit=1',
    $headers
);

if (isSuccessful($employmentResponse) && !empty((array)($employmentResponse['data'] ?? []))) {
    $employmentRow = (array)$employmentResponse['data'][0];
    $officeRow = (array)($employmentRow['office'] ?? []);
    $positionRow = (array)($employmentRow['position'] ?? []);
    $supervisorRow = (array)($employmentRow['supervisor'] ?? []);

    $employeeProfile['employment_status'] = (string)($employmentRow['employment_status'] ?? $employeeProfile['employment_status']);
    $employeeProfile['employment_office_name'] = (string)($officeRow['office_name'] ?? $employeeProfile['employment_office_name']);
    $employeeProfile['employment_position_title'] = (string)($positionRow['position_title'] ?? $employeeProfile['employment_position_title']);

    $supervisorFirstName = cleanText($supervisorRow['first_name'] ?? null);
    $supervisorLastName = cleanText($supervisorRow['surname'] ?? null);
    if ($supervisorFirstName !== null || $supervisorLastName !== null) {
        $employeeProfile['supervisor_name'] = trim(((string)$supervisorFirstName . ' ' . (string)$supervisorLastName));
    }
}

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,storage_path,current_version_no,created_at,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&document_status=neq.archived'
    . '&order=updated_at.desc&limit=25',
    $headers
);

if (isSuccessful($documentsResponse)) {
    foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
        $document = (array)$documentRaw;
        $category = (array)($document['category'] ?? []);

        $employeeDocuments[] = [
            'id' => (string)($document['id'] ?? ''),
            'title' => (string)($document['title'] ?? 'Untitled Document'),
            'description' => (string)($document['description'] ?? ''),
            'document_status' => strtolower((string)($document['document_status'] ?? 'draft')),
            'storage_path' => (string)($document['storage_path'] ?? ''),
            'current_version_no' => (int)($document['current_version_no'] ?? 1),
            'created_at' => cleanText($document['created_at'] ?? null),
            'updated_at' => cleanText($document['updated_at'] ?? null),
            'category_name' => (string)($category['category_name'] ?? 'Uncategorized'),
        ];
    }
}
