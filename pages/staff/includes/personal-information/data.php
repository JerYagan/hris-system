<?php

$filterKeyword = strtolower(trim((string)(cleanText($_GET['keyword'] ?? null) ?? '')));
$filterDepartment = trim((string)(cleanText($_GET['department'] ?? null) ?? ''));
$filterStatusRaw = strtolower(trim((string)(cleanText($_GET['status'] ?? null) ?? '')));
$filterStatus = in_array($filterStatusRaw, ['active', 'inactive'], true) ? $filterStatusRaw : '';

$dataLoadError = null;

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,hire_date,separation_reason,updated_at,person:people!employment_records_person_id_fkey(id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no),office:offices(office_name),position:job_positions(position_title)'
    . '&is_current=eq.true'
    . '&order=updated_at.desc&limit=3000',
    $headers
);
$appendDataError('Employment records', $employmentResponse);
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$personIds = [];
foreach ($employmentRows as $employmentRow) {
    $personId = cleanText($employmentRow['person_id'] ?? null) ?? cleanText(($employmentRow['person']['id'] ?? null)) ?? '';
    if (isValidUuid($personId)) {
        $personIds[] = $personId;
    }
}
$personIdFilter = sanitizeUuidListForInFilter($personIds);

$recommendationHistoryRows = [];
$personNameById = [];
foreach ($employmentRows as $employmentRow) {
    $personId = cleanText($employmentRow['person_id'] ?? null) ?? cleanText(($employmentRow['person']['id'] ?? null)) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $person = (array)($employmentRow['person'] ?? []);
    $firstName = trim((string)(cleanText($person['first_name'] ?? null) ?? ''));
    $middleName = trim((string)(cleanText($person['middle_name'] ?? null) ?? ''));
    $surname = trim((string)(cleanText($person['surname'] ?? null) ?? ''));
    $nameExtension = trim((string)(cleanText($person['name_extension'] ?? null) ?? ''));
    $fullName = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $nameExtension);
    $personNameById[$personId] = $fullName !== '' ? $fullName : 'Unknown Employee';
}

if ($personIdFilter !== '') {
    $recommendationsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,created_at,new_data,module_name,action_name,entity_name'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&action_name=eq.recommend_employee_profile_update'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=120',
        $headers
    );
    $appendDataError('Profile recommendations', $recommendationsResponse);
    $recommendationLogs = isSuccessful($recommendationsResponse) ? (array)($recommendationsResponse['data'] ?? []) : [];

    $actorUserIds = [];
    foreach ($recommendationLogs as $recommendationLog) {
        $actorUserId = cleanText($recommendationLog['actor_user_id'] ?? null) ?? '';
        if (isValidUuid($actorUserId)) {
            $actorUserIds[] = $actorUserId;
        }
    }

    $actorEmailById = [];
    $actorFilter = sanitizeUuidListForInFilter($actorUserIds);
    if ($actorFilter !== '') {
        $actorResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_accounts?select=id,email'
            . '&id=in.(' . $actorFilter . ')&limit=500',
            $headers
        );
        $appendDataError('Recommendation actors', $actorResponse);
        $actorRows = isSuccessful($actorResponse) ? (array)($actorResponse['data'] ?? []) : [];

        foreach ($actorRows as $actorRow) {
            $actorId = cleanText($actorRow['id'] ?? null) ?? '';
            if (!isValidUuid($actorId)) {
                continue;
            }

            $actorEmailById[$actorId] = cleanText($actorRow['email'] ?? null) ?? 'Staff';
        }
    }

    foreach ($recommendationLogs as $recommendationLog) {
        $entityId = cleanText($recommendationLog['entity_id'] ?? null) ?? '';
        if (!isValidUuid($entityId)) {
            continue;
        }

        $actorUserId = cleanText($recommendationLog['actor_user_id'] ?? null) ?? '';
        $staffLabel = isValidUuid($actorUserId)
            ? (string)($actorEmailById[$actorUserId] ?? 'Staff')
            : 'Staff';

        $newData = is_array($recommendationLog['new_data'] ?? null)
            ? (array)$recommendationLog['new_data']
            : [];
        $recommendedProfile = is_array($newData['recommended_profile'] ?? null)
            ? (array)$newData['recommended_profile']
            : [];
        $profileFieldCount = count($recommendedProfile);

        $recommendationHistoryRows[] = [
            'employee_name' => (string)($personNameById[$entityId] ?? 'Unknown Employee'),
            'submitted_by' => $staffLabel,
            'submitted_at_label' => formatDateTimeForPhilippines(cleanText($recommendationLog['created_at'] ?? null), 'M d, Y h:i A'),
            'status_label' => 'Pending Admin Action',
            'status_class' => 'bg-amber-100 text-amber-800',
            'summary' => $profileFieldCount > 0
                ? ($profileFieldCount . ' profile field(s) recommended for update')
                : 'Profile details recommended for update',
        ];
    }
}

$addressRows = [];
$governmentIdRows = [];
$spouseRows = [];
$parentRows = [];
$childrenRows = [];
$educationalBackgroundRows = [];
if ($personIdFilter !== '') {
    $addressesResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_addresses?select=id,person_id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,is_primary'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&address_type=in.(residential,permanent)&limit=6000',
        $headers
    );
    $appendDataError('Person addresses', $addressesResponse);
    $addressRows = isSuccessful($addressesResponse) ? (array)($addressesResponse['data'] ?? []) : [];

    $governmentIdsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_government_ids?select=id,person_id,id_type,id_value_encrypted'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&id_type=in.(umid,pagibig,philhealth,psn,tin)&limit=6000',
        $headers
    );
    $appendDataError('Government IDs', $governmentIdsResponse);
    $governmentIdRows = isSuccessful($governmentIdsResponse) ? (array)($governmentIdsResponse['data'] ?? []) : [];

    $spouseResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_family_spouses?select=id,person_id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=6000',
        $headers
    );
    $appendDataError('Spouse data', $spouseResponse);
    $spouseRows = isSuccessful($spouseResponse) ? (array)($spouseResponse['data'] ?? []) : [];

    $parentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_parents?select=id,person_id,parent_type,surname,first_name,middle_name,extension_name'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&parent_type=in.(father,mother)&limit=6000',
        $headers
    );
    $appendDataError('Parent data', $parentsResponse);
    $parentRows = isSuccessful($parentsResponse) ? (array)($parentsResponse['data'] ?? []) : [];

    $childrenResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_family_children?select=id,person_id,full_name,birth_date,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=12000',
        $headers
    );
    $appendDataError('Children data', $childrenResponse);
    $childrenRows = isSuccessful($childrenResponse) ? (array)($childrenResponse['data'] ?? []) : [];

    $educationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_educational_backgrounds?select=id,person_id,education_level,school_name,degree_course,attendance_from_year,attendance_to_year,highest_level_units_earned,year_graduated,scholarship_honors_received,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=12000',
        $headers
    );
    $appendDataError('Educational backgrounds', $educationResponse);
    $educationalBackgroundRows = isSuccessful($educationResponse) ? (array)($educationResponse['data'] ?? []) : [];
}

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,is_active&order=office_name.asc&limit=500',
    $headers
);
$appendDataError('Offices', $officesResponse);
$officeRows = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=500',
    $headers
);
$appendDataError('Job positions', $positionsResponse);
$positionRows = isSuccessful($positionsResponse) ? (array)($positionsResponse['data'] ?? []) : [];

$addressesByPerson = [];
foreach ($addressRows as $address) {
    $personId = cleanText($address['person_id'] ?? null) ?? '';
    $addressType = strtolower((string)(cleanText($address['address_type'] ?? null) ?? ''));
    if (!isValidUuid($personId) || !in_array($addressType, ['residential', 'permanent'], true)) {
        continue;
    }

    $isPrimary = (bool)($address['is_primary'] ?? false);
    if (!isset($addressesByPerson[$personId])) {
        $addressesByPerson[$personId] = [];
    }

    if (!isset($addressesByPerson[$personId][$addressType]) || $isPrimary) {
        $addressesByPerson[$personId][$addressType] = [
            'house_no' => cleanText($address['house_no'] ?? null) ?? '',
            'street' => cleanText($address['street'] ?? null) ?? '',
            'subdivision' => cleanText($address['subdivision'] ?? null) ?? '',
            'barangay' => cleanText($address['barangay'] ?? null) ?? '',
            'city_municipality' => cleanText($address['city_municipality'] ?? null) ?? '',
            'province' => cleanText($address['province'] ?? null) ?? '',
            'zip_code' => cleanText($address['zip_code'] ?? null) ?? '',
        ];
    }
}

$governmentIdsByPerson = [];
foreach ($governmentIdRows as $governmentId) {
    $personId = cleanText($governmentId['person_id'] ?? null) ?? '';
    $idType = strtolower((string)(cleanText($governmentId['id_type'] ?? null) ?? ''));
    if (!isValidUuid($personId) || $idType === '') {
        continue;
    }

    if (!isset($governmentIdsByPerson[$personId])) {
        $governmentIdsByPerson[$personId] = [];
    }

    $governmentIdsByPerson[$personId][$idType] = cleanText($governmentId['id_value_encrypted'] ?? null) ?? '';
}

$spouseByPerson = [];
foreach ($spouseRows as $spouseRow) {
    $personId = cleanText($spouseRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    if (!isset($spouseByPerson[$personId])) {
        $spouseByPerson[$personId] = [
            'surname' => cleanText($spouseRow['surname'] ?? null) ?? '',
            'first_name' => cleanText($spouseRow['first_name'] ?? null) ?? '',
            'middle_name' => cleanText($spouseRow['middle_name'] ?? null) ?? '',
            'extension_name' => cleanText($spouseRow['extension_name'] ?? null) ?? '',
            'occupation' => cleanText($spouseRow['occupation'] ?? null) ?? '',
            'employer_business_name' => cleanText($spouseRow['employer_business_name'] ?? null) ?? '',
            'business_address' => cleanText($spouseRow['business_address'] ?? null) ?? '',
            'telephone_no' => cleanText($spouseRow['telephone_no'] ?? null) ?? '',
        ];
    }
}

$parentsByPerson = [];
foreach ($parentRows as $parentRow) {
    $personId = cleanText($parentRow['person_id'] ?? null) ?? '';
    $parentType = strtolower((string)(cleanText($parentRow['parent_type'] ?? null) ?? ''));
    if (!isValidUuid($personId) || !in_array($parentType, ['father', 'mother'], true)) {
        continue;
    }

    if (!isset($parentsByPerson[$personId])) {
        $parentsByPerson[$personId] = [];
    }

    $parentsByPerson[$personId][$parentType] = [
        'surname' => cleanText($parentRow['surname'] ?? null) ?? '',
        'first_name' => cleanText($parentRow['first_name'] ?? null) ?? '',
        'middle_name' => cleanText($parentRow['middle_name'] ?? null) ?? '',
        'extension_name' => cleanText($parentRow['extension_name'] ?? null) ?? '',
    ];
}

$childrenByPerson = [];
foreach ($childrenRows as $childRow) {
    $personId = cleanText($childRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    if (!isset($childrenByPerson[$personId])) {
        $childrenByPerson[$personId] = [];
    }

    $childrenByPerson[$personId][] = [
        'full_name' => cleanText($childRow['full_name'] ?? null) ?? '',
        'birth_date' => cleanText($childRow['birth_date'] ?? null) ?? '',
        'sequence_no' => (int)($childRow['sequence_no'] ?? 0),
    ];
}

$educationalBackgroundByPerson = [];
foreach ($educationalBackgroundRows as $educationRow) {
    $personId = cleanText($educationRow['person_id'] ?? null) ?? '';
    $educationLevel = strtolower((string)(cleanText($educationRow['education_level'] ?? null) ?? ''));
    if (!isValidUuid($personId) || $educationLevel === '') {
        continue;
    }

    if (!isset($educationalBackgroundByPerson[$personId])) {
        $educationalBackgroundByPerson[$personId] = [];
    }

    $educationalBackgroundByPerson[$personId][] = [
        'education_level' => $educationLevel,
        'school_name' => cleanText($educationRow['school_name'] ?? null) ?? '',
        'degree_course' => cleanText($educationRow['degree_course'] ?? null) ?? '',
        'attendance_from_year' => isset($educationRow['attendance_from_year']) ? (string)$educationRow['attendance_from_year'] : '',
        'attendance_to_year' => isset($educationRow['attendance_to_year']) ? (string)$educationRow['attendance_to_year'] : '',
        'highest_level_units_earned' => cleanText($educationRow['highest_level_units_earned'] ?? null) ?? '',
        'year_graduated' => isset($educationRow['year_graduated']) ? (string)$educationRow['year_graduated'] : '',
        'scholarship_honors_received' => cleanText($educationRow['scholarship_honors_received'] ?? null) ?? '',
        'sequence_no' => (int)($educationRow['sequence_no'] ?? 0),
    ];
}

$zipCodeJsonFilePath = __DIR__ . '/../../../../assets/zip-codes.json';
$zipCodeTxtFilePath = __DIR__ . '/../../../../assets/zip-codes.txt';
$zipCodeFilePath = is_file($zipCodeJsonFilePath) ? $zipCodeJsonFilePath : $zipCodeTxtFilePath;
$addressZipByCityBarangay = loadZipCodeLookupFromFile($zipCodeFilePath);

$psgcMunicipalitiesPath = __DIR__ . '/../../../../assets/psgc/municipalities.json';
$psgcBarangaysPath = __DIR__ . '/../../../../assets/psgc/barangays.json';

$psgcMunicipalityRows = [];
if (is_file($psgcMunicipalitiesPath)) {
    $decodedMunicipalities = json_decode((string)file_get_contents($psgcMunicipalitiesPath), true);
    if (is_array($decodedMunicipalities)) {
        $psgcMunicipalityRows = $decodedMunicipalities;
    }
}

$psgcMunicipalityNameByCode = [];
foreach ($psgcMunicipalityRows as $municipalityRow) {
    if (!is_array($municipalityRow)) {
        continue;
    }

    $municipalityCode = trim((string)($municipalityRow['code'] ?? $municipalityRow['citymun'] ?? $municipalityRow['city_code'] ?? ''));
    $municipalityName = trim((string)($municipalityRow['name'] ?? $municipalityRow['citymun_name'] ?? $municipalityRow['city_municipality_name'] ?? ''));
    if ($municipalityCode !== '' && $municipalityName !== '') {
        $psgcMunicipalityNameByCode[$municipalityCode] = $municipalityName;
    }
}

$psgcBarangayRows = [];
if (is_file($psgcBarangaysPath)) {
    $decodedBarangays = json_decode((string)file_get_contents($psgcBarangaysPath), true);
    if (is_array($decodedBarangays)) {
        $psgcBarangayRows = $decodedBarangays;
    }
}

foreach ($addressRows as $addressRow) {
    $city = normalizeZipLookupPart((string)(cleanText($addressRow['city_municipality'] ?? null) ?? ''));
    $barangay = normalizeZipLookupPart((string)(cleanText($addressRow['barangay'] ?? null) ?? ''));
    $zipCode = trim((string)(cleanText($addressRow['zip_code'] ?? null) ?? ''));

    if ($city === '' || $barangay === '' || $zipCode === '') {
        continue;
    }

    if (!isset($addressZipByCityBarangay[$city])) {
        $addressZipByCityBarangay[$city] = [];
    }
    if (!isset($addressZipByCityBarangay[$city][$barangay])) {
        $addressZipByCityBarangay[$city][$barangay] = [];
    }

    if (!in_array($zipCode, $addressZipByCityBarangay[$city][$barangay], true)) {
        $addressZipByCityBarangay[$city][$barangay][] = $zipCode;
        sort($addressZipByCityBarangay[$city][$barangay], SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$cityLevelZipByCity = [];
foreach ($addressZipByCityBarangay as $cityKey => $barangaysByKey) {
    if (!is_array($barangaysByKey)) {
        continue;
    }

    $cityKeyNormalized = normalizeZipLookupPart((string)$cityKey);
    if ($cityKeyNormalized === '') {
        continue;
    }

    $citySpecificZipList = [];
    if (isset($barangaysByKey[$cityKeyNormalized]) && is_array($barangaysByKey[$cityKeyNormalized])) {
        $citySpecificZipList = array_values(array_filter(array_map(static fn ($value) => trim((string)$value), $barangaysByKey[$cityKeyNormalized]), static fn ($value) => $value !== ''));
    }

    if (!empty($citySpecificZipList)) {
        usort($citySpecificZipList, static fn ($a, $b) => strnatcasecmp((string)$a, (string)$b));
        $cityLevelZipByCity[$cityKeyNormalized] = (string)$citySpecificZipList[0];
        continue;
    }

    $allZipCandidates = [];
    foreach ($barangaysByKey as $zipList) {
        if (!is_array($zipList)) {
            continue;
        }

        foreach ($zipList as $zipValue) {
            $zipCode = trim((string)$zipValue);
            if ($zipCode !== '') {
                $allZipCandidates[$zipCode] = true;
            }
        }
    }

    $allZipCodes = array_keys($allZipCandidates);
    if (!empty($allZipCodes)) {
        usort($allZipCodes, static fn ($a, $b) => strnatcasecmp((string)$a, (string)$b));
        $cityLevelZipByCity[$cityKeyNormalized] = (string)$allZipCodes[0];
    }
}

foreach ($psgcBarangayRows as $barangayRow) {
    if (!is_array($barangayRow)) {
        continue;
    }

    $barangayCityRef = trim((string)($barangayRow['citymun'] ?? $barangayRow['city_code'] ?? $barangayRow['city_municipality_name'] ?? ''));
    $barangayCityDisplay = (string)($psgcMunicipalityNameByCode[$barangayCityRef] ?? $barangayCityRef);
    $cityKey = normalizeZipLookupPart($barangayCityDisplay);
    $barangayKey = normalizeZipLookupPart((string)($barangayRow['name'] ?? ''));
    $psgcZipCode = trim((string)($barangayRow['zip_code'] ?? ''));
    if ($cityKey === '' || $barangayKey === '') {
        continue;
    }

    $cityZipCode = $psgcZipCode !== '' ? $psgcZipCode : (string)($cityLevelZipByCity[$cityKey] ?? '');
    if ($cityZipCode === '') {
        continue;
    }

    if (!isset($addressZipByCityBarangay[$cityKey])) {
        $addressZipByCityBarangay[$cityKey] = [];
    }
    if (!isset($addressZipByCityBarangay[$cityKey][$barangayKey])) {
        $addressZipByCityBarangay[$cityKey][$barangayKey] = [];
    }
    if (!in_array($cityZipCode, $addressZipByCityBarangay[$cityKey][$barangayKey], true)) {
        $addressZipByCityBarangay[$cityKey][$barangayKey][] = $cityZipCode;
        sort($addressZipByCityBarangay[$cityKey][$barangayKey], SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$addressCityDisplayByKey = [];
$addressBarangayDisplayByCityKey = [];
$provinceOptionsMap = [];

foreach ($psgcMunicipalityRows as $municipalityRow) {
    if (!is_array($municipalityRow)) {
        continue;
    }

    $cityDisplay = trim((string)($municipalityRow['name'] ?? ''));
    $provinceDisplay = trim((string)($municipalityRow['province'] ?? ''));
    $cityKey = normalizeZipLookupPart($cityDisplay);
    if ($cityKey !== '' && !isset($addressCityDisplayByKey[$cityKey])) {
        $addressCityDisplayByKey[$cityKey] = $cityDisplay;
    }
    if ($provinceDisplay !== '') {
        $provinceOptionsMap[$provinceDisplay] = true;
    }
}

foreach ($psgcBarangayRows as $barangayRow) {
    if (!is_array($barangayRow)) {
        continue;
    }

    $barangayCityRef = trim((string)($barangayRow['citymun'] ?? $barangayRow['city_code'] ?? $barangayRow['city_municipality_name'] ?? ''));
    $cityDisplay = trim((string)($psgcMunicipalityNameByCode[$barangayCityRef] ?? $barangayCityRef));
    $barangayDisplay = trim((string)($barangayRow['name'] ?? ''));
    $cityKey = normalizeZipLookupPart($cityDisplay);
    $barangayKey = normalizeZipLookupPart($barangayDisplay);

    if ($cityKey !== '' && !isset($addressCityDisplayByKey[$cityKey])) {
        $addressCityDisplayByKey[$cityKey] = $cityDisplay;
    }

    if ($cityKey !== '' && $barangayKey !== '') {
        if (!isset($addressBarangayDisplayByCityKey[$cityKey])) {
            $addressBarangayDisplayByCityKey[$cityKey] = [];
        }
        if (!isset($addressBarangayDisplayByCityKey[$cityKey][$barangayKey])) {
            $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] = $barangayDisplay;
        }
    }
}

foreach ($addressRows as $addressRow) {
    $cityDisplay = trim((string)(cleanText($addressRow['city_municipality'] ?? null) ?? ''));
    $barangayDisplay = trim((string)(cleanText($addressRow['barangay'] ?? null) ?? ''));
    $provinceDisplay = trim((string)(cleanText($addressRow['province'] ?? null) ?? ''));
    $cityKey = normalizeZipLookupPart($cityDisplay);
    $barangayKey = normalizeZipLookupPart($barangayDisplay);

    if ($cityKey !== '' && !isset($addressCityDisplayByKey[$cityKey])) {
        $addressCityDisplayByKey[$cityKey] = $cityDisplay !== '' ? $cityDisplay : ucwords($cityKey);
    }

    if ($cityKey !== '' && $barangayKey !== '') {
        if (!isset($addressBarangayDisplayByCityKey[$cityKey])) {
            $addressBarangayDisplayByCityKey[$cityKey] = [];
        }
        if (!isset($addressBarangayDisplayByCityKey[$cityKey][$barangayKey])) {
            $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] = $barangayDisplay !== '' ? $barangayDisplay : ucwords($barangayKey);
        }
    }

    if ($provinceDisplay !== '') {
        $provinceOptionsMap[$provinceDisplay] = true;
    }
}

foreach ($addressZipByCityBarangay as $cityKey => $barangaysByKey) {
    $cityKey = normalizeZipLookupPart((string)$cityKey);
    if ($cityKey === '') {
        continue;
    }

    if (!isset($addressCityDisplayByKey[$cityKey])) {
        $addressCityDisplayByKey[$cityKey] = ucwords($cityKey);
    }

    if (!is_array($barangaysByKey)) {
        continue;
    }

    if (!isset($addressBarangayDisplayByCityKey[$cityKey])) {
        $addressBarangayDisplayByCityKey[$cityKey] = [];
    }

    foreach ($barangaysByKey as $barangayKey => $zipCodes) {
        $barangayKey = normalizeZipLookupPart((string)$barangayKey);
        if ($barangayKey === '') {
            continue;
        }

        if (!isset($addressBarangayDisplayByCityKey[$cityKey][$barangayKey])) {
            $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] = ucwords($barangayKey);
        }
    }
}

$addressCityOptions = array_values($addressCityDisplayByKey);
sort($addressCityOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressBarangaysByCity = [];
$addressBarangayOptionsMap = [];
foreach ($addressBarangayDisplayByCityKey as $cityKey => $barangaysByKey) {
    $barangayOptions = array_values($barangaysByKey);
    sort($barangayOptions, SORT_NATURAL | SORT_FLAG_CASE);
    $addressBarangaysByCity[$cityKey] = $barangayOptions;

    foreach ($barangayOptions as $barangayOption) {
        $addressBarangayOptionsMap[$barangayOption] = true;
    }
}

$addressBarangayOptions = array_keys($addressBarangayOptionsMap);
sort($addressBarangayOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressProvinceOptions = array_keys($provinceOptionsMap);
sort($addressProvinceOptions, SORT_NATURAL | SORT_FLAG_CASE);

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'active') {
        return ['Active', 'bg-emerald-100 text-emerald-800', 'active'];
    }

    return ['Inactive', 'bg-amber-100 text-amber-800', 'inactive'];
};

$employeeTableRows = [];
$departmentFilters = [];

foreach ($officeRows as $officeRow) {
    $officeName = cleanText($officeRow['office_name'] ?? null) ?? '';
    if ($officeName !== '') {
        $departmentFilters[$officeName] = true;
    }
}

$totalProfiles = 0;
$completeRecords = 0;
$activeEmployees = 0;
$inactiveEmployees = 0;

foreach ($employmentRows as $employment) {
    $employmentId = cleanText($employment['id'] ?? null) ?? '';
    $person = (array)($employment['person'] ?? []);
    $personId = cleanText($employment['person_id'] ?? null) ?? cleanText($person['id'] ?? null) ?? '';

    if (!isValidUuid($employmentId) || !isValidUuid($personId)) {
        continue;
    }

    $totalProfiles++;

    $firstName = trim((string)(cleanText($person['first_name'] ?? null) ?? ''));
    $middleName = trim((string)(cleanText($person['middle_name'] ?? null) ?? ''));
    $surname = trim((string)(cleanText($person['surname'] ?? null) ?? ''));
    $nameExtension = trim((string)(cleanText($person['name_extension'] ?? null) ?? ''));
    $fullName = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $nameExtension);
    if ($fullName === '') {
        $fullName = 'Unknown Employee';
    }

    $officeName = cleanText($employment['office']['office_name'] ?? null) ?? 'Unassigned Department';
    $positionName = cleanText($employment['position']['position_title'] ?? null) ?? 'Unassigned Position';
    if ($officeName !== 'Unassigned Department') {
        $departmentFilters[$officeName] = true;
    }

    $statusRaw = strtolower((string)(cleanText($employment['employment_status'] ?? null) ?? 'inactive'));
    [$statusLabel, $statusClass, $statusBucket] = $statusPill($statusRaw);

    if ($statusBucket === 'active') {
        $activeEmployees++;
    } else {
        $inactiveEmployees++;
    }

    if ($officeName !== 'Unassigned Department' && $positionName !== 'Unassigned Position') {
        $completeRecords++;
    }

    if ($filterDepartment !== '' && strcasecmp($officeName, $filterDepartment) !== 0) {
        continue;
    }
    if ($filterStatus !== '' && $statusBucket !== $filterStatus) {
        continue;
    }

    $email = cleanText($person['personal_email'] ?? null) ?? '';
    $mobile = cleanText($person['mobile_no'] ?? null) ?? '';
    $telephone = cleanText($person['telephone_no'] ?? null) ?? '';
    $agencyEmployeeNo = cleanText($person['agency_employee_no'] ?? null) ?? '';
    $employeeCode = $agencyEmployeeNo !== ''
        ? $agencyEmployeeNo
        : ('EMP-' . strtoupper(substr(str_replace('-', '', $personId), 0, 6)));

    $searchText = strtolower(trim(implode(' ', [
        $employeeCode,
        $fullName,
        $email,
        $mobile,
        $telephone,
        $officeName,
        $positionName,
        $statusLabel,
    ])));

    if ($filterKeyword !== '' && !str_contains($searchText, $filterKeyword)) {
        continue;
    }

    $residential = (array)($addressesByPerson[$personId]['residential'] ?? []);
    $permanent = (array)($addressesByPerson[$personId]['permanent'] ?? []);
    $governmentIds = (array)($governmentIdsByPerson[$personId] ?? []);

    $employeeTableRows[] = [
        'employment_id' => $employmentId,
        'person_id' => $personId,
        'employee_code' => $employeeCode,
        'full_name' => $fullName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'surname' => $surname,
        'name_extension' => $nameExtension,
        'email' => $email,
        'mobile' => $mobile,
        'department' => $officeName,
        'position' => $positionName,
        'status_raw' => $statusBucket,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => $searchText,

        'date_of_birth' => cleanText($person['date_of_birth'] ?? null) ?? '',
        'place_of_birth' => cleanText($person['place_of_birth'] ?? null) ?? '',
        'sex_at_birth' => cleanText($person['sex_at_birth'] ?? null) ?? '',
        'civil_status' => cleanText($person['civil_status'] ?? null) ?? '',
        'height_m' => isset($person['height_m']) ? (string)$person['height_m'] : '',
        'weight_kg' => isset($person['weight_kg']) ? (string)$person['weight_kg'] : '',
        'blood_type' => cleanText($person['blood_type'] ?? null) ?? '',
        'citizenship' => cleanText($person['citizenship'] ?? null) ?? '',
        'dual_citizenship_country' => cleanText($person['dual_citizenship_country'] ?? null) ?? '',
        'telephone_no' => $telephone,
        'agency_employee_no' => $agencyEmployeeNo,

        'residential_house_no' => cleanText($residential['house_no'] ?? null) ?? '',
        'residential_street' => cleanText($residential['street'] ?? null) ?? '',
        'residential_subdivision' => cleanText($residential['subdivision'] ?? null) ?? '',
        'residential_barangay' => cleanText($residential['barangay'] ?? null) ?? '',
        'residential_city_municipality' => cleanText($residential['city_municipality'] ?? null) ?? '',
        'residential_province' => cleanText($residential['province'] ?? null) ?? '',
        'residential_zip_code' => cleanText($residential['zip_code'] ?? null) ?? '',
        'permanent_house_no' => cleanText($permanent['house_no'] ?? null) ?? '',
        'permanent_street' => cleanText($permanent['street'] ?? null) ?? '',
        'permanent_subdivision' => cleanText($permanent['subdivision'] ?? null) ?? '',
        'permanent_barangay' => cleanText($permanent['barangay'] ?? null) ?? '',
        'permanent_city_municipality' => cleanText($permanent['city_municipality'] ?? null) ?? '',
        'permanent_province' => cleanText($permanent['province'] ?? null) ?? '',
        'permanent_zip_code' => cleanText($permanent['zip_code'] ?? null) ?? '',

        'umid_no' => cleanText($governmentIds['umid'] ?? null) ?? '',
        'pagibig_no' => cleanText($governmentIds['pagibig'] ?? null) ?? '',
        'philhealth_no' => cleanText($governmentIds['philhealth'] ?? null) ?? '',
        'psn_no' => cleanText($governmentIds['psn'] ?? null) ?? '',
        'tin_no' => cleanText($governmentIds['tin'] ?? null) ?? '',

        'spouse_surname' => cleanText($spouseByPerson[$personId]['surname'] ?? null) ?? '',
        'spouse_first_name' => cleanText($spouseByPerson[$personId]['first_name'] ?? null) ?? '',
        'spouse_middle_name' => cleanText($spouseByPerson[$personId]['middle_name'] ?? null) ?? '',
        'spouse_extension_name' => cleanText($spouseByPerson[$personId]['extension_name'] ?? null) ?? '',
        'spouse_occupation' => cleanText($spouseByPerson[$personId]['occupation'] ?? null) ?? '',
        'spouse_employer_business_name' => cleanText($spouseByPerson[$personId]['employer_business_name'] ?? null) ?? '',
        'spouse_business_address' => cleanText($spouseByPerson[$personId]['business_address'] ?? null) ?? '',
        'spouse_telephone_no' => cleanText($spouseByPerson[$personId]['telephone_no'] ?? null) ?? '',
        'father_surname' => cleanText($parentsByPerson[$personId]['father']['surname'] ?? null) ?? '',
        'father_first_name' => cleanText($parentsByPerson[$personId]['father']['first_name'] ?? null) ?? '',
        'father_middle_name' => cleanText($parentsByPerson[$personId]['father']['middle_name'] ?? null) ?? '',
        'father_extension_name' => cleanText($parentsByPerson[$personId]['father']['extension_name'] ?? null) ?? '',
        'mother_surname' => cleanText($parentsByPerson[$personId]['mother']['surname'] ?? null) ?? '',
        'mother_first_name' => cleanText($parentsByPerson[$personId]['mother']['first_name'] ?? null) ?? '',
        'mother_middle_name' => cleanText($parentsByPerson[$personId]['mother']['middle_name'] ?? null) ?? '',
        'mother_extension_name' => cleanText($parentsByPerson[$personId]['mother']['extension_name'] ?? null) ?? '',
        'children' => (array)($childrenByPerson[$personId] ?? []),
        'educational_backgrounds' => (array)($educationalBackgroundByPerson[$personId] ?? []),
    ];
}

$departmentFilterOptions = array_keys($departmentFilters);
sort($departmentFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);

$placeOfBirthOptionsMap = [];
foreach ($employeeTableRows as $employeeTableRow) {
    $placeOfBirthValue = trim((string)($employeeTableRow['place_of_birth'] ?? ''));
    if ($placeOfBirthValue !== '') {
        $placeOfBirthOptionsMap[$placeOfBirthValue] = true;
    }
}
$placeOfBirthOptions = array_keys($placeOfBirthOptionsMap);

foreach ($addressCityOptions as $addressCityOption) {
    $normalizedCityOption = trim((string)$addressCityOption);
    if ($normalizedCityOption !== '') {
        $placeOfBirthOptionsMap[$normalizedCityOption] = true;
    }
}

$placeOfBirthOptions = array_keys($placeOfBirthOptionsMap);
sort($placeOfBirthOptions, SORT_NATURAL | SORT_FLAG_CASE);

$civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced', 'Annulled'];
$bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

$needsUpdateCount = max(0, $totalProfiles - $completeRecords);

$assignmentOfficeOptions = [];
foreach ($officeRows as $officeRow) {
    $officeId = cleanText($officeRow['id'] ?? null) ?? '';
    $officeName = cleanText($officeRow['office_name'] ?? null) ?? '';
    if (!isValidUuid($officeId) || $officeName === '') {
        continue;
    }

    $assignmentOfficeOptions[] = [
        'id' => $officeId,
        'office_name' => $officeName,
    ];
}

$assignmentPositionOptions = [];
foreach ($positionRows as $positionRow) {
    $positionId = cleanText($positionRow['id'] ?? null) ?? '';
    $positionTitle = cleanText($positionRow['position_title'] ?? null) ?? '';
    if (!isValidUuid($positionId) || $positionTitle === '') {
        continue;
    }

    $assignmentPositionOptions[] = [
        'id' => $positionId,
        'position_title' => $positionTitle,
    ];
}