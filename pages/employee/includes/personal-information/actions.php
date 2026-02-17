<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'personal-information.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'personal-information.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if (!in_array($action, ['update_profile', 'upload_profile_photo'], true)) {
    redirectWithState('error', 'Unsupported personal information action.', 'personal-information.php');
}

if ($action === 'upload_profile_photo') {
    $photoFile = $_FILES['profile_photo'] ?? null;
    if (!is_array($photoFile) || (int)($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please choose a valid profile photo file.', 'personal-information.php');
    }

    $tmpName = (string)($photoFile['tmp_name'] ?? '');
    $sizeBytes = (int)($photoFile['size'] ?? 0);
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        redirectWithState('error', 'Uploaded profile photo is invalid.', 'personal-information.php');
    }

    if ($sizeBytes <= 0 || $sizeBytes > (3 * 1024 * 1024)) {
        redirectWithState('error', 'Profile photo must be less than or equal to 3 MB.', 'personal-information.php');
    }

    $mimeType = (string)(mime_content_type($tmpName) ?: '');
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $extension = $allowedMimeToExt[$mimeType] ?? null;
    if ($extension === null) {
        redirectWithState('error', 'Only JPG, PNG, and WEBP profile photos are allowed.', 'personal-information.php');
    }

    $beforePhotoResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,profile_photo_url&id=eq.' . rawurlencode((string)$employeePersonId) . '&limit=1',
        $headers
    );

    if (!isSuccessful($beforePhotoResponse) || empty((array)($beforePhotoResponse['data'] ?? []))) {
        redirectWithState('error', 'Unable to load profile before photo update.', 'personal-information.php');
    }

    $beforePhotoRow = (array)$beforePhotoResponse['data'][0];
    $oldPath = cleanText($beforePhotoRow['profile_photo_url'] ?? null);

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $profileDir = $storageRoot . '/profile-photos/' . $employeePersonId;
    if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
        redirectWithState('error', 'Unable to prepare profile photo storage.', 'personal-information.php');
    }

    $fileName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $relativePath = 'profile-photos/' . $employeePersonId . '/' . $fileName;
    $absolutePath = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        redirectWithState('error', 'Failed to save uploaded profile photo.', 'personal-information.php');
    }

    $photoUpdateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode((string)$employeePersonId),
        $headers,
        [
            'profile_photo_url' => $relativePath,
        ]
    );

    if (!isSuccessful($photoUpdateResponse)) {
        @unlink($absolutePath);
        redirectWithState('error', 'Unable to update profile photo reference.', 'personal-information.php');
    }

    if ($oldPath !== null && str_starts_with($oldPath, 'profile-photos/')) {
        $oldAbsolute = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $oldPath);
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'personal_profile',
            'entity_id' => $employeePersonId,
            'action_name' => 'upload_profile_photo',
            'old_data' => ['profile_photo_url' => $oldPath],
            'new_data' => ['profile_photo_url' => $relativePath],
        ]]
    );

    unset($_SESSION['employee_topnav_cache']);
    redirectWithState('success', 'Profile photo updated successfully.', 'personal-information.php');
}

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        return mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return true;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

$upsertSingleById = static function (string $table, ?string $id, array $payload) use ($supabaseUrl, $headers): bool {
    if ($id !== null && isValidUuid($id)) {
        $response = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/' . $table . '?id=eq.' . rawurlencode($id),
            $headers,
            $payload
        );

        return isSuccessful($response);
    }

    $response = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/' . $table,
        $headers,
        $payload
    );

    return isSuccessful($response);
};

$middleName = $toNullable($_POST['middle_name'] ?? null, 120);
$nameExtension = $toNullable($_POST['name_extension'] ?? null, 30);
$dateOfBirth = $toNullable($_POST['date_of_birth'] ?? null, 10);
$placeOfBirth = $toNullable($_POST['place_of_birth'] ?? null, 160);
$sexAtBirth = $toNullable($_POST['sex_at_birth'] ?? null, 12);
$civilStatus = $toNullable($_POST['civil_status'] ?? null, 40);
$heightM = $toNullable($_POST['height_m'] ?? null, 10);
$weightKg = $toNullable($_POST['weight_kg'] ?? null, 10);
$bloodType = $toNullable($_POST['blood_type'] ?? null, 10);
$umidNo = $toNullable($_POST['umid_no'] ?? null, 80);
$pagibigNo = $toNullable($_POST['pagibig_no'] ?? null, 80);
$philhealthNo = $toNullable($_POST['philhealth_no'] ?? null, 80);
$psnNo = $toNullable($_POST['psn_no'] ?? null, 80);
$tinNo = $toNullable($_POST['tin_no'] ?? null, 80);
$agencyEmployeeNo = $toNullable($_POST['agency_employee_no'] ?? null, 80);
$citizenship = $toNullable($_POST['citizenship'] ?? null, 80);
$dualCitizenship = (($toNullable($_POST['dual_citizenship'] ?? null, 1) ?? '0') === '1');
$dualCitizenshipCountry = $toNullable($_POST['dual_citizenship_country'] ?? null, 80);

$residentialAddressId = $toNullable($_POST['address_id'] ?? null, 36);
$houseNo = $toNullable($_POST['house_no'] ?? null, 60);
$street = $toNullable($_POST['street'] ?? null, 160);
$subdivision = $toNullable($_POST['subdivision'] ?? null, 160);
$barangay = $toNullable($_POST['barangay'] ?? null, 120);
$cityMunicipality = $toNullable($_POST['city_municipality'] ?? null, 120);
$province = $toNullable($_POST['province'] ?? null, 120);
$zipCode = $toNullable($_POST['zip_code'] ?? null, 20);

$permanentAddressId = $toNullable($_POST['permanent_address_id'] ?? null, 36);
$permanentHouseNo = $toNullable($_POST['permanent_house_no'] ?? null, 60);
$permanentStreet = $toNullable($_POST['permanent_street'] ?? null, 160);
$permanentSubdivision = $toNullable($_POST['permanent_subdivision'] ?? null, 160);
$permanentBarangay = $toNullable($_POST['permanent_barangay'] ?? null, 120);
$permanentCityMunicipality = $toNullable($_POST['permanent_city_municipality'] ?? null, 120);
$permanentProvince = $toNullable($_POST['permanent_province'] ?? null, 120);
$permanentZipCode = $toNullable($_POST['permanent_zip_code'] ?? null, 20);
$permanentSameAsResidential = isset($_POST['permanent_same_as_residential']) && (string)$_POST['permanent_same_as_residential'] === '1';

$telephoneNo = $toNullable($_POST['telephone_no'] ?? null, 30);
$mobileNo = $toNullable($_POST['mobile_no'] ?? null, 30);
$personalEmail = $toNullable($_POST['personal_email'] ?? null, 200);

$spouseId = $toNullable($_POST['spouse_id'] ?? null, 36);
$spouseSurname = $toNullable($_POST['spouse_surname'] ?? null, 120);
$spouseFirstName = $toNullable($_POST['spouse_first_name'] ?? null, 120);
$spouseNameExtension = $toNullable($_POST['spouse_name_extension'] ?? null, 30);
$spouseMiddleName = $toNullable($_POST['spouse_middle_name'] ?? null, 120);
$spouseOccupation = $toNullable($_POST['spouse_occupation'] ?? null, 160);
$spouseEmployerBusinessName = $toNullable($_POST['spouse_employer_business_name'] ?? null, 180);
$spouseBusinessAddress = $toNullable($_POST['spouse_business_address'] ?? null, 200);
$spouseTelephoneNo = $toNullable($_POST['spouse_telephone_no'] ?? null, 30);

$fatherId = $toNullable($_POST['father_id'] ?? null, 36);
$fatherSurname = $toNullable($_POST['father_surname'] ?? null, 120);
$fatherFirstName = $toNullable($_POST['father_first_name'] ?? null, 120);
$fatherNameExtension = $toNullable($_POST['father_name_extension'] ?? null, 30);
$fatherMiddleName = $toNullable($_POST['father_middle_name'] ?? null, 120);

$motherId = $toNullable($_POST['mother_id'] ?? null, 36);
$motherSurname = $toNullable($_POST['mother_surname'] ?? null, 120);
$motherFirstName = $toNullable($_POST['mother_first_name'] ?? null, 120);
$motherMiddleName = $toNullable($_POST['mother_middle_name'] ?? null, 120);

$childrenNames = (array)($_POST['children_full_name'] ?? []);
$childrenBirthDates = (array)($_POST['children_birth_date'] ?? []);

$educationLevels = (array)($_POST['education_level'] ?? []);
$educationSchoolNames = (array)($_POST['education_school_name'] ?? []);
$educationCourses = (array)($_POST['education_course_degree'] ?? []);
$educationFrom = (array)($_POST['education_period_from'] ?? []);
$educationTo = (array)($_POST['education_period_to'] ?? []);
$educationUnits = (array)($_POST['education_highest_level_units'] ?? []);
$educationYearGraduated = (array)($_POST['education_year_graduated'] ?? []);
$educationHonors = (array)($_POST['education_honors_received'] ?? []);

if ($personalEmail !== null && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid personal email address.', 'personal-information.php');
}

if (!$isValidDate($dateOfBirth)) {
    redirectWithState('error', 'Date of birth format is invalid.', 'personal-information.php');
}

if ($dateOfBirth !== null && strtotime($dateOfBirth) > time()) {
    redirectWithState('error', 'Date of birth cannot be in the future.', 'personal-information.php');
}

if ($sexAtBirth !== null && !in_array($sexAtBirth, ['male', 'female'], true)) {
    redirectWithState('error', 'Sex at birth must be male or female.', 'personal-information.php');
}

if ($heightM !== null && !is_numeric($heightM)) {
    redirectWithState('error', 'Height must be numeric.', 'personal-information.php');
}

if ($weightKg !== null && !is_numeric($weightKg)) {
    redirectWithState('error', 'Weight must be numeric.', 'personal-information.php');
}

$beforeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($beforeResponse) || empty((array)($beforeResponse['data'] ?? []))) {
    redirectWithState('error', 'Unable to load profile before update. Please try again.', 'personal-information.php');
}

$beforeData = (array)$beforeResponse['data'][0];

$peoplePayload = [
    'first_name' => (string)($beforeData['first_name'] ?? ''),
    'surname' => (string)($beforeData['surname'] ?? ''),
    'middle_name' => $middleName,
    'name_extension' => $nameExtension,
    'date_of_birth' => $dateOfBirth,
    'place_of_birth' => $placeOfBirth,
    'sex_at_birth' => $sexAtBirth,
    'civil_status' => $civilStatus,
    'height_m' => $heightM === null ? null : (float)$heightM,
    'weight_kg' => $weightKg === null ? null : (float)$weightKg,
    'blood_type' => $bloodType,
    'citizenship' => $citizenship,
    'dual_citizenship' => $dualCitizenship,
    'dual_citizenship_country' => $dualCitizenship ? $dualCitizenshipCountry : null,
    'telephone_no' => $telephoneNo,
    'mobile_no' => $mobileNo,
    'personal_email' => $personalEmail,
    'agency_employee_no' => $agencyEmployeeNo,
];

$updateHeaders = $headers;
$updateHeaders[] = 'Prefer: return=representation';

$peopleUpdateResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode((string)$employeePersonId),
    $updateHeaders,
    $peoplePayload
);

if (!isSuccessful($peopleUpdateResponse)) {
    redirectWithState('error', 'Failed to update profile information. Please try again.', 'personal-information.php');
}

$residentialPayload = [
    'person_id' => $employeePersonId,
    'address_type' => 'residential',
    'house_no' => $houseNo,
    'street' => $street,
    'subdivision' => $subdivision,
    'barangay' => $barangay,
    'city_municipality' => $cityMunicipality,
    'province' => $province,
    'zip_code' => $zipCode,
    'country' => 'Philippines',
    'is_primary' => true,
];

if (!$upsertSingleById('person_addresses', $residentialAddressId, $residentialPayload)) {
    redirectWithState('error', 'Failed to save residential address.', 'personal-information.php');
}

if ($permanentSameAsResidential) {
    $permanentHouseNo = $houseNo;
    $permanentStreet = $street;
    $permanentSubdivision = $subdivision;
    $permanentBarangay = $barangay;
    $permanentCityMunicipality = $cityMunicipality;
    $permanentProvince = $province;
    $permanentZipCode = $zipCode;
}

$permanentPayload = [
    'person_id' => $employeePersonId,
    'address_type' => 'permanent',
    'house_no' => $permanentHouseNo,
    'street' => $permanentStreet,
    'subdivision' => $permanentSubdivision,
    'barangay' => $permanentBarangay,
    'city_municipality' => $permanentCityMunicipality,
    'province' => $permanentProvince,
    'zip_code' => $permanentZipCode,
    'country' => 'Philippines',
    'is_primary' => false,
];

if (!$upsertSingleById('person_addresses', $permanentAddressId, $permanentPayload)) {
    redirectWithState('error', 'Failed to save permanent address.', 'personal-information.php');
}

$existingGovIdResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type&person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$govIdMap = [];
if (isSuccessful($existingGovIdResponse)) {
    foreach ((array)($existingGovIdResponse['data'] ?? []) as $rowRaw) {
        $row = (array)$rowRaw;
        $govIdMap[(string)($row['id_type'] ?? '')] = (string)($row['id'] ?? '');
    }
}

$govIdValues = [
    'umid' => $umidNo,
    'pagibig' => $pagibigNo,
    'philhealth' => $philhealthNo,
    'psn' => $psnNo,
    'tin' => $tinNo,
];

foreach ($govIdValues as $idType => $idValue) {
    $existingId = cleanText($govIdMap[$idType] ?? null);

    if ($idValue === null) {
        if ($existingId !== null && isValidUuid($existingId)) {
            apiRequest(
                'DELETE',
                $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . rawurlencode($existingId),
                $headers
            );
        }
        continue;
    }

    $payload = [
        'person_id' => $employeePersonId,
        'id_type' => $idType,
        'id_value_encrypted' => $idValue,
    ];

    if (!$upsertSingleById('person_government_ids', $existingId, $payload)) {
        redirectWithState('error', 'Failed to save government ID details.', 'personal-information.php');
    }
}

$spousePayload = [
    'person_id' => $employeePersonId,
    'surname' => $spouseSurname,
    'first_name' => $spouseFirstName,
    'middle_name' => $spouseMiddleName,
    'extension_name' => $spouseNameExtension,
    'occupation' => $spouseOccupation,
    'employer_business_name' => $spouseEmployerBusinessName,
    'business_address' => $spouseBusinessAddress,
    'telephone_no' => $spouseTelephoneNo,
    'sequence_no' => 1,
];

$hasSpouseData = ($spouseSurname !== null || $spouseFirstName !== null || $spouseMiddleName !== null || $spouseOccupation !== null || $spouseEmployerBusinessName !== null || $spouseBusinessAddress !== null || $spouseTelephoneNo !== null);
if ($hasSpouseData) {
    if (!$upsertSingleById('person_family_spouses', $spouseId, $spousePayload)) {
        redirectWithState('error', 'Failed to save spouse information.', 'personal-information.php');
    }
} elseif ($spouseId !== null && isValidUuid($spouseId)) {
    apiRequest('DELETE', $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . rawurlencode($spouseId), $headers);
}

$parentsToSave = [
    [
        'id' => $fatherId,
        'parent_type' => 'father',
        'surname' => $fatherSurname,
        'first_name' => $fatherFirstName,
        'middle_name' => $fatherMiddleName,
        'extension_name' => $fatherNameExtension,
    ],
    [
        'id' => $motherId,
        'parent_type' => 'mother',
        'surname' => $motherSurname,
        'first_name' => $motherFirstName,
        'middle_name' => $motherMiddleName,
        'extension_name' => null,
    ],
];

foreach ($parentsToSave as $parentData) {
    $hasParentData = ($parentData['surname'] !== null || $parentData['first_name'] !== null || $parentData['middle_name'] !== null || $parentData['extension_name'] !== null);

    if ($hasParentData) {
        $payload = [
            'person_id' => $employeePersonId,
            'parent_type' => $parentData['parent_type'],
            'surname' => $parentData['surname'],
            'first_name' => $parentData['first_name'],
            'middle_name' => $parentData['middle_name'],
            'extension_name' => $parentData['extension_name'],
        ];

        if (!$upsertSingleById('person_parents', $parentData['id'], $payload)) {
            redirectWithState('error', 'Failed to save parent information.', 'personal-information.php');
        }
    } elseif ($parentData['id'] !== null && isValidUuid((string)$parentData['id'])) {
        apiRequest('DELETE', $supabaseUrl . '/rest/v1/person_parents?id=eq.' . rawurlencode((string)$parentData['id']), $headers);
    }
}

apiRequest(
    'DELETE',
    $supabaseUrl . '/rest/v1/person_family_children?person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$childrenPayload = [];
foreach ($childrenNames as $index => $childNameRaw) {
    $childName = $toNullable($childNameRaw, 180);
    $childBirthDate = $toNullable($childrenBirthDates[$index] ?? null, 10);

    if ($childName === null && $childBirthDate === null) {
        continue;
    }

    if ($childName === null) {
        redirectWithState('error', 'Child full name is required when adding a child row.', 'personal-information.php');
    }

    if (!$isValidDate($childBirthDate)) {
        redirectWithState('error', 'One of the child birth dates is invalid.', 'personal-information.php');
    }

    $childrenPayload[] = [
        'person_id' => $employeePersonId,
        'full_name' => $childName,
        'birth_date' => $childBirthDate,
        'sequence_no' => count($childrenPayload) + 1,
    ];
}

if (!empty($childrenPayload)) {
    $childrenInsertResponse = apiRequest('POST', $supabaseUrl . '/rest/v1/person_family_children', $headers, $childrenPayload);
    if (!isSuccessful($childrenInsertResponse)) {
        redirectWithState('error', 'Failed to save children records.', 'personal-information.php');
    }
}

apiRequest(
    'DELETE',
    $supabaseUrl . '/rest/v1/person_educations?person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$allowedEducationLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
$educationPayload = [];
foreach ($educationLevels as $index => $levelRaw) {
    $level = strtolower((string)$toNullable($levelRaw, 30));
    $schoolName = $toNullable($educationSchoolNames[$index] ?? null, 220);
    $courseDegree = $toNullable($educationCourses[$index] ?? null, 220);
    $periodFrom = $toNullable($educationFrom[$index] ?? null, 15);
    $periodTo = $toNullable($educationTo[$index] ?? null, 15);
    $highestLevelUnits = $toNullable($educationUnits[$index] ?? null, 140);
    $yearGraduated = $toNullable($educationYearGraduated[$index] ?? null, 20);
    $honorsReceived = $toNullable($educationHonors[$index] ?? null, 180);

    $hasAny = ($level !== '' || $schoolName !== null || $courseDegree !== null || $periodFrom !== null || $periodTo !== null || $highestLevelUnits !== null || $yearGraduated !== null || $honorsReceived !== null);
    if (!$hasAny) {
        continue;
    }

    if ($level === '' || !in_array($level, $allowedEducationLevels, true)) {
        redirectWithState('error', 'One of the educational background rows has an invalid level.', 'personal-information.php');
    }

    $educationPayload[] = [
        'person_id' => $employeePersonId,
        'education_level' => $level,
        'school_name' => $schoolName,
        'course_degree' => $courseDegree,
        'period_from' => $periodFrom,
        'period_to' => $periodTo,
        'highest_level_units' => $highestLevelUnits,
        'year_graduated' => $yearGraduated,
        'honors_received' => $honorsReceived,
        'sequence_no' => count($educationPayload) + 1,
    ];
}

if (!empty($educationPayload)) {
    $educationInsertResponse = apiRequest('POST', $supabaseUrl . '/rest/v1/person_educations', $headers, $educationPayload);
    if (!isSuccessful($educationInsertResponse)) {
        redirectWithState('error', 'Failed to save educational background.', 'personal-information.php');
    }
}

$afterResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

$afterData = [];
if (isSuccessful($afterResponse) && !empty((array)($afterResponse['data'] ?? []))) {
    $afterData = (array)$afterResponse['data'][0];
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'personal_profile',
        'entity_id' => $employeePersonId,
        'action_name' => 'update_profile',
        'old_data' => $beforeData,
        'new_data' => $afterData,
    ]
);

redirectWithState('success', 'Profile information updated successfully.', 'personal-information.php');
