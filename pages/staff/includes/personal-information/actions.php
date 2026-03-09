<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($csrfToken ?? null);

$action = cleanText($_POST['form_action'] ?? null) ?? '';
$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$staffOfficeIdForScope = cleanText($staffOfficeId ?? null) ?? '';

$resolveScopedEmployment = static function (string $employmentId, string $personId) use ($supabaseUrl, $headers): ?array {
    if (!isValidUuid($employmentId) || !isValidUuid($personId)) {
        return null;
    }

    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,separation_date,separation_reason'
        . '&id=eq.' . rawurlencode($employmentId)
        . '&person_id=eq.' . rawurlencode($personId)
        . '&is_current=eq.true&limit=1',
        $headers
    );

    $row = isSuccessful($response) ? ($response['data'][0] ?? null) : null;
    if (!is_array($row)) {
        return null;
    }

    return $row;
};

$syncAddressAndGovernmentIds = static function (string $targetPersonId) use ($supabaseUrl, $headers): void {
    $residential = [
        'house_no' => trim((string)(cleanText($_POST['residential_house_no'] ?? null) ?? '')),
        'street' => trim((string)(cleanText($_POST['residential_street'] ?? null) ?? '')),
        'subdivision' => trim((string)(cleanText($_POST['residential_subdivision'] ?? null) ?? '')),
        'barangay' => trim((string)(cleanText($_POST['residential_barangay'] ?? null) ?? '')),
        'city_municipality' => trim((string)(cleanText($_POST['residential_city_municipality'] ?? null) ?? '')),
        'province' => trim((string)(cleanText($_POST['residential_province'] ?? null) ?? '')),
        'zip_code' => trim((string)(cleanText($_POST['residential_zip_code'] ?? null) ?? '')),
    ];
    $permanent = [
        'house_no' => trim((string)(cleanText($_POST['permanent_house_no'] ?? null) ?? '')),
        'street' => trim((string)(cleanText($_POST['permanent_street'] ?? null) ?? '')),
        'subdivision' => trim((string)(cleanText($_POST['permanent_subdivision'] ?? null) ?? '')),
        'barangay' => trim((string)(cleanText($_POST['permanent_barangay'] ?? null) ?? '')),
        'city_municipality' => trim((string)(cleanText($_POST['permanent_city_municipality'] ?? null) ?? '')),
        'province' => trim((string)(cleanText($_POST['permanent_province'] ?? null) ?? '')),
        'zip_code' => trim((string)(cleanText($_POST['permanent_zip_code'] ?? null) ?? '')),
    ];

    $existingAddressesResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_addresses?select=id,address_type,is_primary'
        . '&person_id=eq.' . rawurlencode($targetPersonId)
        . '&address_type=in.(residential,permanent)&limit=10',
        $headers
    );

    $existingAddressByType = [];
    if (isSuccessful($existingAddressesResponse)) {
        foreach ((array)($existingAddressesResponse['data'] ?? []) as $addressRow) {
            $addressType = strtolower((string)(cleanText($addressRow['address_type'] ?? null) ?? ''));
            $addressId = cleanText($addressRow['id'] ?? null) ?? '';
            if ($addressType !== '' && isValidUuid($addressId)) {
                if (!isset($existingAddressByType[$addressType]) || (bool)($addressRow['is_primary'] ?? false)) {
                    $existingAddressByType[$addressType] = $addressId;
                }
            }
        }
    }

    foreach (['residential' => $residential, 'permanent' => $permanent] as $addressType => $payloadSource) {
        $hasValue = false;
        foreach ($payloadSource as $value) {
            if ($value !== '') {
                $hasValue = true;
                break;
            }
        }

        $existingAddressId = $existingAddressByType[$addressType] ?? '';
        if (!$hasValue && $existingAddressId === '') {
            continue;
        }

        $payload = [
            'person_id' => $targetPersonId,
            'address_type' => $addressType,
            'house_no' => $payloadSource['house_no'] !== '' ? $payloadSource['house_no'] : null,
            'street' => $payloadSource['street'] !== '' ? $payloadSource['street'] : null,
            'subdivision' => $payloadSource['subdivision'] !== '' ? $payloadSource['subdivision'] : null,
            'barangay' => $payloadSource['barangay'] !== '' ? $payloadSource['barangay'] : null,
            'city_municipality' => $payloadSource['city_municipality'] !== '' ? $payloadSource['city_municipality'] : null,
            'province' => $payloadSource['province'] !== '' ? $payloadSource['province'] : null,
            'zip_code' => $payloadSource['zip_code'] !== '' ? $payloadSource['zip_code'] : null,
            'country' => 'Philippines',
            'is_primary' => true,
        ];

        if (isValidUuid($existingAddressId)) {
            $response = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/person_addresses?id=eq.' . rawurlencode($existingAddressId),
                array_merge($headers, ['Prefer: return=minimal']),
                $payload
            );
        } else {
            $response = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/person_addresses',
                array_merge($headers, ['Prefer: return=minimal']),
                [$payload]
            );
        }

        if (!isSuccessful($response)) {
            redirectWithState('error', 'Failed to save ' . $addressType . ' address information.');
        }
    }

    $governmentIdInputs = [
        'umid' => trim((string)(cleanText($_POST['umid_no'] ?? null) ?? '')),
        'pagibig' => trim((string)(cleanText($_POST['pagibig_no'] ?? null) ?? '')),
        'philhealth' => trim((string)(cleanText($_POST['philhealth_no'] ?? null) ?? '')),
        'psn' => trim((string)(cleanText($_POST['psn_no'] ?? null) ?? '')),
        'tin' => trim((string)(cleanText($_POST['tin_no'] ?? null) ?? '')),
    ];

    $existingGovIdsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_government_ids?select=id,id_type'
        . '&person_id=eq.' . rawurlencode($targetPersonId)
        . '&id_type=in.(umid,pagibig,philhealth,psn,tin)&limit=20',
        $headers
    );

    $existingGovIdByType = [];
    if (isSuccessful($existingGovIdsResponse)) {
        foreach ((array)($existingGovIdsResponse['data'] ?? []) as $governmentIdRow) {
            $idType = strtolower((string)(cleanText($governmentIdRow['id_type'] ?? null) ?? ''));
            $id = cleanText($governmentIdRow['id'] ?? null) ?? '';
            if ($idType !== '' && isValidUuid($id)) {
                $existingGovIdByType[$idType] = $id;
            }
        }
    }

    foreach ($governmentIdInputs as $idType => $idValue) {
        $existingId = $existingGovIdByType[$idType] ?? '';

        if ($idValue === '' && !isValidUuid($existingId)) {
            continue;
        }

        $payload = [
            'person_id' => $targetPersonId,
            'id_type' => $idType,
            'id_value_encrypted' => $idValue !== '' ? $idValue : null,
        ];

        if (isValidUuid($existingId)) {
            $response = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . rawurlencode($existingId),
                array_merge($headers, ['Prefer: return=minimal']),
                $payload
            );
        } else {
            $response = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/person_government_ids',
                array_merge($headers, ['Prefer: return=minimal']),
                [$payload]
            );
        }

        if (!isSuccessful($response)) {
            redirectWithState('error', 'Failed to save government IDs.');
        }
    }
};

$syncFamilyBackground = static function (string $targetPersonId) use ($supabaseUrl, $headers): void {
    $spouseSurname = trim((string)(cleanText($_POST['spouse_surname'] ?? null) ?? ''));
    $spouseFirstName = trim((string)(cleanText($_POST['spouse_first_name'] ?? null) ?? ''));
    $spouseMiddleName = trim((string)(cleanText($_POST['spouse_middle_name'] ?? null) ?? ''));
    $spouseExtensionName = trim((string)(cleanText($_POST['spouse_extension_name'] ?? null) ?? ''));
    $spouseOccupation = trim((string)(cleanText($_POST['spouse_occupation'] ?? null) ?? ''));
    $spouseEmployerBusinessName = trim((string)(cleanText($_POST['spouse_employer_business_name'] ?? null) ?? ''));
    $spouseBusinessAddress = trim((string)(cleanText($_POST['spouse_business_address'] ?? null) ?? ''));
    $spouseTelephoneNo = trim((string)(cleanText($_POST['spouse_telephone_no'] ?? null) ?? ''));

    $fatherSurname = trim((string)(cleanText($_POST['father_surname'] ?? null) ?? ''));
    $fatherFirstName = trim((string)(cleanText($_POST['father_first_name'] ?? null) ?? ''));
    $fatherMiddleName = trim((string)(cleanText($_POST['father_middle_name'] ?? null) ?? ''));
    $fatherExtensionName = trim((string)(cleanText($_POST['father_extension_name'] ?? null) ?? ''));

    $motherSurname = trim((string)(cleanText($_POST['mother_surname'] ?? null) ?? ''));
    $motherFirstName = trim((string)(cleanText($_POST['mother_first_name'] ?? null) ?? ''));
    $motherMiddleName = trim((string)(cleanText($_POST['mother_middle_name'] ?? null) ?? ''));
    $motherExtensionName = trim((string)(cleanText($_POST['mother_extension_name'] ?? null) ?? ''));

    $childrenFullNames = (array)($_POST['children_full_name'] ?? []);
    $childrenBirthDates = (array)($_POST['children_birth_date'] ?? []);

    $validChildren = [];
    $childCount = max(count($childrenFullNames), count($childrenBirthDates));
    for ($index = 0; $index < $childCount; $index++) {
        $fullName = trim((string)(cleanText($childrenFullNames[$index] ?? null) ?? ''));
        $birthDate = trim((string)(cleanText($childrenBirthDates[$index] ?? null) ?? ''));

        if ($fullName === '' && $birthDate === '') {
            continue;
        }

        if ($fullName === '') {
            redirectWithState('error', 'Child full name is required when a child row is used.');
        }

        if ($birthDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            redirectWithState('error', 'Invalid child birth date format.');
        }

        $validChildren[] = [
            'full_name' => $fullName,
            'birth_date' => $birthDate !== '' ? $birthDate : null,
            'sequence_no' => count($validChildren) + 1,
        ];
    }

    $existingSpouseResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/person_family_spouses?select=id&person_id=eq.' . rawurlencode($targetPersonId) . '&order=sequence_no.asc&limit=1',
        $headers
    );
    $existingSpouse = isSuccessful($existingSpouseResponse) ? ($existingSpouseResponse['data'][0] ?? null) : null;

    $spousePayload = [
        'person_id' => $targetPersonId,
        'surname' => $spouseSurname !== '' ? $spouseSurname : null,
        'first_name' => $spouseFirstName !== '' ? $spouseFirstName : null,
        'middle_name' => $spouseMiddleName !== '' ? $spouseMiddleName : null,
        'extension_name' => $spouseExtensionName !== '' ? $spouseExtensionName : null,
        'occupation' => $spouseOccupation !== '' ? $spouseOccupation : null,
        'employer_business_name' => $spouseEmployerBusinessName !== '' ? $spouseEmployerBusinessName : null,
        'business_address' => $spouseBusinessAddress !== '' ? $spouseBusinessAddress : null,
        'telephone_no' => $spouseTelephoneNo !== '' ? $spouseTelephoneNo : null,
        'sequence_no' => 1,
    ];

    $hasSpouseData = false;
    foreach ($spousePayload as $key => $value) {
        if ($key === 'person_id' || $key === 'sequence_no') {
            continue;
        }
        if ($value !== null && $value !== '') {
            $hasSpouseData = true;
            break;
        }
    }

    if ($hasSpouseData) {
        if (is_array($existingSpouse) && isValidUuid((string)($existingSpouse['id'] ?? ''))) {
            $spouseSaveResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . rawurlencode((string)$existingSpouse['id']),
                array_merge($headers, ['Prefer: return=minimal']),
                array_merge($spousePayload, ['updated_at' => gmdate('c')])
            );
        } else {
            $spouseSaveResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/person_family_spouses',
                array_merge($headers, ['Prefer: return=minimal']),
                [$spousePayload]
            );
        }

        if (!isSuccessful($spouseSaveResponse)) {
            redirectWithState('error', 'Failed to save spouse details.');
        }
    } elseif (is_array($existingSpouse) && isValidUuid((string)($existingSpouse['id'] ?? ''))) {
        $spouseDeleteResponse = apiRequest(
            'DELETE',
            $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . rawurlencode((string)$existingSpouse['id']),
            array_merge($headers, ['Prefer: return=minimal'])
        );

        if (!isSuccessful($spouseDeleteResponse)) {
            redirectWithState('error', 'Failed to clear spouse details.');
        }
    }

    $upsertParent = static function (string $parentType, array $payload) use ($targetPersonId, $supabaseUrl, $headers): void {
        $existingParentResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_parents?select=id&person_id=eq.' . rawurlencode($targetPersonId) . '&parent_type=eq.' . rawurlencode($parentType) . '&limit=1',
            $headers
        );
        $existingParent = isSuccessful($existingParentResponse) ? ($existingParentResponse['data'][0] ?? null) : null;

        $hasParentData = false;
        foreach ($payload as $value) {
            if ($value !== null && $value !== '') {
                $hasParentData = true;
                break;
            }
        }

        if ($hasParentData) {
            $recordPayload = [
                'person_id' => $targetPersonId,
                'parent_type' => $parentType,
                'surname' => $payload['surname'] !== '' ? $payload['surname'] : null,
                'first_name' => $payload['first_name'] !== '' ? $payload['first_name'] : null,
                'middle_name' => $payload['middle_name'] !== '' ? $payload['middle_name'] : null,
                'extension_name' => $payload['extension_name'] !== '' ? $payload['extension_name'] : null,
            ];

            if (is_array($existingParent) && isValidUuid((string)($existingParent['id'] ?? ''))) {
                $parentSaveResponse = apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/person_parents?id=eq.' . rawurlencode((string)$existingParent['id']),
                    array_merge($headers, ['Prefer: return=minimal']),
                    $recordPayload
                );
            } else {
                $parentSaveResponse = apiRequest(
                    'POST',
                    $supabaseUrl . '/rest/v1/person_parents',
                    array_merge($headers, ['Prefer: return=minimal']),
                    [$recordPayload]
                );
            }

            if (!isSuccessful($parentSaveResponse)) {
                redirectWithState('error', 'Failed to save ' . $parentType . ' details.');
            }
        } elseif (is_array($existingParent) && isValidUuid((string)($existingParent['id'] ?? ''))) {
            $parentDeleteResponse = apiRequest(
                'DELETE',
                $supabaseUrl . '/rest/v1/person_parents?id=eq.' . rawurlencode((string)$existingParent['id']),
                array_merge($headers, ['Prefer: return=minimal'])
            );

            if (!isSuccessful($parentDeleteResponse)) {
                redirectWithState('error', 'Failed to clear ' . $parentType . ' details.');
            }
        }
    };

    $upsertParent('father', [
        'surname' => $fatherSurname,
        'first_name' => $fatherFirstName,
        'middle_name' => $fatherMiddleName,
        'extension_name' => $fatherExtensionName,
    ]);

    $upsertParent('mother', [
        'surname' => $motherSurname,
        'first_name' => $motherFirstName,
        'middle_name' => $motherMiddleName,
        'extension_name' => $motherExtensionName,
    ]);

    $deleteChildrenResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/person_family_children?person_id=eq.' . rawurlencode($targetPersonId),
        array_merge($headers, ['Prefer: return=minimal'])
    );
    if (!isSuccessful($deleteChildrenResponse)) {
        redirectWithState('error', 'Failed to reset children records.');
    }

    if (!empty($validChildren)) {
        $childrenPayload = array_map(static function (array $child) use ($targetPersonId): array {
            return [
                'person_id' => $targetPersonId,
                'full_name' => $child['full_name'],
                'birth_date' => $child['birth_date'],
                'sequence_no' => $child['sequence_no'],
            ];
        }, $validChildren);

        $saveChildrenResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_family_children',
            array_merge($headers, ['Prefer: return=minimal']),
            $childrenPayload
        );

        if (!isSuccessful($saveChildrenResponse)) {
            redirectWithState('error', 'Failed to save children records.');
        }
    }
};

$syncEducationalBackground = static function (string $targetPersonId) use ($supabaseUrl, $headers): void {
    $levels = (array)($_POST['education_level'] ?? []);
    $schoolNames = (array)($_POST['education_school_name'] ?? ($_POST['school_name'] ?? []));
    $degreeCourses = (array)($_POST['education_course_degree'] ?? ($_POST['degree_course'] ?? []));
    $attendanceFromYears = (array)($_POST['education_period_from'] ?? ($_POST['attendance_from_year'] ?? []));
    $attendanceToYears = (array)($_POST['education_period_to'] ?? ($_POST['attendance_to_year'] ?? []));
    $highestLevelsUnitsEarned = (array)($_POST['education_highest_level_units'] ?? ($_POST['highest_level_units_earned'] ?? []));
    $yearGraduatedValues = (array)($_POST['education_year_graduated'] ?? ($_POST['year_graduated'] ?? []));
    $scholarshipHonors = (array)($_POST['education_honors_received'] ?? ($_POST['scholarship_honors_received'] ?? []));

    $allowedLevels = ['elementary', 'secondary', 'vocational_trade_course', 'college', 'graduate_studies'];
    $levelOrder = array_flip($allowedLevels);
    $levelAliases = [
        'vocational' => 'vocational_trade_course',
        'vocational_trade' => 'vocational_trade_course',
        'vocational_trade_course' => 'vocational_trade_course',
        'graduate' => 'graduate_studies',
        'graduate_studies' => 'graduate_studies',
    ];

    $recordCount = max(
        count($levels),
        count($schoolNames),
        count($degreeCourses),
        count($attendanceFromYears),
        count($attendanceToYears),
        count($highestLevelsUnitsEarned),
        count($yearGraduatedValues),
        count($scholarshipHonors)
    );

    $parseYear = static function (string $value, string $label): ?int {
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^\d{4}$/', $value)) {
            redirectWithState('error', $label . ' must be a valid 4-digit year.');
        }

        $year = (int)$value;
        if ($year < 1900 || $year > 2100) {
            redirectWithState('error', $label . ' must be between 1900 and 2100.');
        }

        return $year;
    };

    $validRecords = [];
    for ($index = 0; $index < $recordCount; $index++) {
        $level = strtolower(trim((string)(cleanText($levels[$index] ?? null) ?? '')));
        if (isset($levelAliases[$level])) {
            $level = $levelAliases[$level];
        }
        if ($level === '' || !in_array($level, $allowedLevels, true)) {
            continue;
        }

        $schoolName = trim((string)(cleanText($schoolNames[$index] ?? null) ?? ''));
        $degreeCourse = trim((string)(cleanText($degreeCourses[$index] ?? null) ?? ''));
        $attendanceFromRaw = trim((string)(cleanText($attendanceFromYears[$index] ?? null) ?? ''));
        $attendanceToRaw = trim((string)(cleanText($attendanceToYears[$index] ?? null) ?? ''));
        $highestUnits = trim((string)(cleanText($highestLevelsUnitsEarned[$index] ?? null) ?? ''));
        $yearGraduatedRaw = trim((string)(cleanText($yearGraduatedValues[$index] ?? null) ?? ''));
        $scholarshipHonor = trim((string)(cleanText($scholarshipHonors[$index] ?? null) ?? ''));

        $attendanceFromYear = $parseYear($attendanceFromRaw, 'Period of attendance (from)');
        $attendanceToYear = $parseYear($attendanceToRaw, 'Period of attendance (to)');
        $yearGraduated = $parseYear($yearGraduatedRaw, 'Year graduated');

        if ($attendanceFromYear !== null && $attendanceToYear !== null && $attendanceToYear < $attendanceFromYear) {
            redirectWithState('error', 'Period of attendance (to) cannot be earlier than period of attendance (from).');
        }

        $hasValue = $schoolName !== ''
            || $degreeCourse !== ''
            || $attendanceFromYear !== null
            || $attendanceToYear !== null
            || $highestUnits !== ''
            || $yearGraduated !== null
            || $scholarshipHonor !== '';

        if (!$hasValue) {
            continue;
        }

        $validRecords[$level] = [
            'person_id' => $targetPersonId,
            'education_level' => $level,
            'school_name' => $schoolName !== '' ? $schoolName : null,
            'degree_course' => $degreeCourse !== '' ? $degreeCourse : null,
            'attendance_from_year' => $attendanceFromYear,
            'attendance_to_year' => $attendanceToYear,
            'highest_level_units_earned' => $highestUnits !== '' ? $highestUnits : null,
            'year_graduated' => $yearGraduated,
            'scholarship_honors_received' => $scholarshipHonor !== '' ? $scholarshipHonor : null,
            'sequence_no' => (int)($levelOrder[$level] ?? 0) + 1,
        ];
    }

    $deleteExistingResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/person_educational_backgrounds?person_id=eq.' . rawurlencode($targetPersonId),
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteExistingResponse)) {
        redirectWithState('error', 'Failed to reset educational background records.');
    }

    if (!empty($validRecords)) {
        uasort($validRecords, static function (array $left, array $right): int {
            return (int)($left['sequence_no'] ?? 0) <=> (int)($right['sequence_no'] ?? 0);
        });

        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_educational_backgrounds',
            array_merge($headers, ['Prefer: return=minimal']),
            array_values($validRecords)
        );

        if (!isSuccessful($insertResponse)) {
            redirectWithState('error', 'Failed to save educational background records.');
        }
    }
};

if ($action === 'save_profile') {
    $profileAction = strtolower((string)(cleanText($_POST['profile_action'] ?? null) ?? 'edit'));
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $employmentId = cleanText($_POST['employment_id'] ?? null) ?? '';
    $recommendationNotes = trim((string)(cleanText($_POST['profile_recommendation_notes'] ?? null) ?? ''));

    if ($profileAction !== 'edit') {
        redirectWithState('error', 'Unsupported profile action.');
    }

    $firstName = trim((string)(cleanText($_POST['first_name'] ?? null) ?? ''));
    $surname = trim((string)(cleanText($_POST['surname'] ?? null) ?? ''));
    if ($firstName === '' || $surname === '') {
        redirectWithState('error', 'First name and surname are required.');
    }

    $email = strtolower(trim((string)(cleanText($_POST['email'] ?? null) ?? '')));
    if ($email === '') {
        redirectWithState('error', 'Email address is required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Please enter a valid email address.');
    }

    $mobileNo = trim((string)(cleanText($_POST['mobile_no'] ?? null) ?? ''));
    if ($mobileNo === '') {
        redirectWithState('error', 'Mobile number is required.');
    }
    if (!preg_match('/^\+?[0-9][0-9\s-]{6,19}$/', $mobileNo)) {
        redirectWithState('error', 'Please enter a valid mobile number.');
    }

    $requiredFieldMap = [
        'date_of_birth' => 'Date of birth',
        'place_of_birth' => 'Place of birth',
        'civil_status' => 'Civil status',
        'blood_type' => 'Blood type',
        'residential_barangay' => 'Residential barangay',
        'residential_city_municipality' => 'Residential city/municipality',
        'residential_province' => 'Residential province',
        'residential_zip_code' => 'Residential ZIP code',
        'permanent_barangay' => 'Permanent barangay',
        'permanent_city_municipality' => 'Permanent city/municipality',
        'permanent_province' => 'Permanent province',
        'permanent_zip_code' => 'Permanent ZIP code',
    ];

    foreach ($requiredFieldMap as $fieldName => $label) {
        $fieldValue = trim((string)(cleanText($_POST[$fieldName] ?? null) ?? ''));
        if ($fieldValue === '') {
            redirectWithState('error', $label . ' is required before submitting recommendation.');
        }
    }

    $dateOfBirthInput = trim((string)(cleanText($_POST['date_of_birth'] ?? null) ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirthInput)) {
        redirectWithState('error', 'Date of birth must be a valid date.');
    }

    $zipFields = [
        'residential_zip_code' => trim((string)(cleanText($_POST['residential_zip_code'] ?? null) ?? '')),
        'permanent_zip_code' => trim((string)(cleanText($_POST['permanent_zip_code'] ?? null) ?? '')),
    ];
    foreach ($zipFields as $zipLabel => $zipValue) {
        if (!preg_match('/^\d{4}$/', $zipValue)) {
            redirectWithState('error', str_replace('_', ' ', ucfirst($zipLabel)) . ' must be a valid 4-digit ZIP code.');
        }
    }

    $employmentRow = $resolveScopedEmployment($employmentId, $personId);
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'Employee scope validation failed.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no'
        . '&id=eq.' . rawurlencode($personId)
        . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile not found.');
    }

    $heightMInput = trim((string)(cleanText($_POST['height_m'] ?? null) ?? ''));
    $heightM = null;
    if ($heightMInput !== '') {
        if (!is_numeric($heightMInput) || (float)$heightMInput < 0) {
            redirectWithState('error', 'Height must be a valid non-negative number.');
        }
        $heightM = (float)$heightMInput;
    }

    $weightKgInput = trim((string)(cleanText($_POST['weight_kg'] ?? null) ?? ''));
    $weightKg = null;
    if ($weightKgInput !== '') {
        if (!is_numeric($weightKgInput) || (float)$weightKgInput < 0) {
            redirectWithState('error', 'Weight must be a valid non-negative number.');
        }
        $weightKg = (float)$weightKgInput;
    }

    $sexAtBirth = strtolower(trim((string)(cleanText($_POST['sex_at_birth'] ?? null) ?? '')));
    if ($sexAtBirth !== '' && !in_array($sexAtBirth, ['male', 'female'], true)) {
        redirectWithState('error', 'Sex at birth must be either male or female.');
    }

    $patchPayload = [
        'first_name' => $firstName,
        'middle_name' => ($middleName = trim((string)(cleanText($_POST['middle_name'] ?? null) ?? ''))) !== '' ? $middleName : null,
        'surname' => $surname,
        'name_extension' => ($nameExtension = trim((string)(cleanText($_POST['name_extension'] ?? null) ?? ''))) !== '' ? $nameExtension : null,
        'date_of_birth' => ($dateOfBirth = trim((string)(cleanText($_POST['date_of_birth'] ?? null) ?? ''))) !== '' ? $dateOfBirth : null,
        'place_of_birth' => ($placeOfBirth = trim((string)(cleanText($_POST['place_of_birth'] ?? null) ?? ''))) !== '' ? $placeOfBirth : null,
        'sex_at_birth' => $sexAtBirth !== '' ? $sexAtBirth : null,
        'civil_status' => ($civilStatus = trim((string)(cleanText($_POST['civil_status'] ?? null) ?? ''))) !== '' ? $civilStatus : null,
        'height_m' => $heightM,
        'weight_kg' => $weightKg,
        'blood_type' => ($bloodType = trim((string)(cleanText($_POST['blood_type'] ?? null) ?? ''))) !== '' ? $bloodType : null,
        'citizenship' => ($citizenship = trim((string)(cleanText($_POST['citizenship'] ?? null) ?? ''))) !== '' ? $citizenship : null,
        'dual_citizenship' => ($dualCountry = trim((string)(cleanText($_POST['dual_citizenship_country'] ?? null) ?? ''))) !== '',
        'dual_citizenship_country' => $dualCountry !== '' ? $dualCountry : null,
        'telephone_no' => ($telephoneNo = trim((string)(cleanText($_POST['telephone_no'] ?? null) ?? ''))) !== '' ? $telephoneNo : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'personal_email' => $email !== '' ? $email : null,
        'agency_employee_no' => ($agencyEmployeeNo = trim((string)(cleanText($_POST['agency_employee_no'] ?? null) ?? ''))) !== '' ? $agencyEmployeeNo : null,
        'updated_at' => gmdate('c'),
    ];

    $normalizeComparableValue = static function ($value): string {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return rtrim(rtrim(sprintf('%.8F', (float)$value), '0'), '.');
        }

        return trim((string)$value);
    };

    $comparableProfileFields = [
        'first_name',
        'middle_name',
        'surname',
        'name_extension',
        'date_of_birth',
        'place_of_birth',
        'sex_at_birth',
        'civil_status',
        'height_m',
        'weight_kg',
        'blood_type',
        'citizenship',
        'dual_citizenship',
        'dual_citizenship_country',
        'telephone_no',
        'mobile_no',
        'personal_email',
        'agency_employee_no',
    ];

    $recommendedProfileChanges = [];
    foreach ($comparableProfileFields as $fieldName) {
        if (!array_key_exists($fieldName, $patchPayload)) {
            continue;
        }

        $oldValue = $personRow[$fieldName] ?? null;
        $newValue = $patchPayload[$fieldName] ?? null;
        if ($normalizeComparableValue($oldValue) === $normalizeComparableValue($newValue)) {
            continue;
        }

        $recommendedProfileChanges[$fieldName] = [
            'old' => $oldValue,
            'new' => $newValue,
        ];
    }

    $residentialRecommendation = [
        'house_no' => trim((string)(cleanText($_POST['residential_house_no'] ?? null) ?? '')),
        'street' => trim((string)(cleanText($_POST['residential_street'] ?? null) ?? '')),
        'subdivision' => trim((string)(cleanText($_POST['residential_subdivision'] ?? null) ?? '')),
        'barangay' => trim((string)(cleanText($_POST['residential_barangay'] ?? null) ?? '')),
        'city_municipality' => trim((string)(cleanText($_POST['residential_city_municipality'] ?? null) ?? '')),
        'province' => trim((string)(cleanText($_POST['residential_province'] ?? null) ?? '')),
        'zip_code' => trim((string)(cleanText($_POST['residential_zip_code'] ?? null) ?? '')),
    ];
    $permanentRecommendation = [
        'house_no' => trim((string)(cleanText($_POST['permanent_house_no'] ?? null) ?? '')),
        'street' => trim((string)(cleanText($_POST['permanent_street'] ?? null) ?? '')),
        'subdivision' => trim((string)(cleanText($_POST['permanent_subdivision'] ?? null) ?? '')),
        'barangay' => trim((string)(cleanText($_POST['permanent_barangay'] ?? null) ?? '')),
        'city_municipality' => trim((string)(cleanText($_POST['permanent_city_municipality'] ?? null) ?? '')),
        'province' => trim((string)(cleanText($_POST['permanent_province'] ?? null) ?? '')),
        'zip_code' => trim((string)(cleanText($_POST['permanent_zip_code'] ?? null) ?? '')),
    ];

    $governmentRecommendation = [
        'umid' => trim((string)(cleanText($_POST['umid_no'] ?? null) ?? '')),
        'pagibig' => trim((string)(cleanText($_POST['pagibig_no'] ?? null) ?? '')),
        'philhealth' => trim((string)(cleanText($_POST['philhealth_no'] ?? null) ?? '')),
        'psn' => trim((string)(cleanText($_POST['psn_no'] ?? null) ?? '')),
        'tin' => trim((string)(cleanText($_POST['tin_no'] ?? null) ?? '')),
    ];

    $familyRecommendation = [
        'spouse_surname' => trim((string)(cleanText($_POST['spouse_surname'] ?? null) ?? '')),
        'spouse_first_name' => trim((string)(cleanText($_POST['spouse_first_name'] ?? null) ?? '')),
        'spouse_middle_name' => trim((string)(cleanText($_POST['spouse_middle_name'] ?? null) ?? '')),
        'spouse_extension_name' => trim((string)(cleanText($_POST['spouse_extension_name'] ?? null) ?? '')),
        'spouse_occupation' => trim((string)(cleanText($_POST['spouse_occupation'] ?? null) ?? '')),
        'spouse_employer_business_name' => trim((string)(cleanText($_POST['spouse_employer_business_name'] ?? null) ?? '')),
        'spouse_business_address' => trim((string)(cleanText($_POST['spouse_business_address'] ?? null) ?? '')),
        'spouse_telephone_no' => trim((string)(cleanText($_POST['spouse_telephone_no'] ?? null) ?? '')),
        'father_surname' => trim((string)(cleanText($_POST['father_surname'] ?? null) ?? '')),
        'father_first_name' => trim((string)(cleanText($_POST['father_first_name'] ?? null) ?? '')),
        'father_middle_name' => trim((string)(cleanText($_POST['father_middle_name'] ?? null) ?? '')),
        'father_extension_name' => trim((string)(cleanText($_POST['father_extension_name'] ?? null) ?? '')),
        'mother_surname' => trim((string)(cleanText($_POST['mother_surname'] ?? null) ?? '')),
        'mother_first_name' => trim((string)(cleanText($_POST['mother_first_name'] ?? null) ?? '')),
        'mother_middle_name' => trim((string)(cleanText($_POST['mother_middle_name'] ?? null) ?? '')),
        'mother_extension_name' => trim((string)(cleanText($_POST['mother_extension_name'] ?? null) ?? '')),
    ];

    $childrenRecommendation = [];
    $childrenFullNames = (array)($_POST['children_full_name'] ?? []);
    $childrenBirthDates = (array)($_POST['children_birth_date'] ?? []);
    $childrenCount = max(count($childrenFullNames), count($childrenBirthDates));
    for ($index = 0; $index < $childrenCount; $index++) {
        $childName = trim((string)(cleanText($childrenFullNames[$index] ?? null) ?? ''));
        $childBirthDate = trim((string)(cleanText($childrenBirthDates[$index] ?? null) ?? ''));
        if ($childName === '' && $childBirthDate === '') {
            continue;
        }

        $childrenRecommendation[] = [
            'full_name' => $childName,
            'birth_date' => $childBirthDate,
        ];
    }

    $educationRecommendation = [];
    $levels = (array)($_POST['education_level'] ?? []);
    $schoolNames = (array)($_POST['education_school_name'] ?? ($_POST['school_name'] ?? []));
    $degreeCourses = (array)($_POST['education_course_degree'] ?? ($_POST['degree_course'] ?? []));
    $attendanceFromYears = (array)($_POST['education_period_from'] ?? ($_POST['attendance_from_year'] ?? []));
    $attendanceToYears = (array)($_POST['education_period_to'] ?? ($_POST['attendance_to_year'] ?? []));
    $highestLevelsUnitsEarned = (array)($_POST['education_highest_level_units'] ?? ($_POST['highest_level_units_earned'] ?? []));
    $yearGraduatedValues = (array)($_POST['education_year_graduated'] ?? ($_POST['year_graduated'] ?? []));
    $scholarshipHonors = (array)($_POST['education_honors_received'] ?? ($_POST['scholarship_honors_received'] ?? []));
    $educationCount = max(count($levels), count($schoolNames), count($degreeCourses), count($attendanceFromYears), count($attendanceToYears), count($highestLevelsUnitsEarned), count($yearGraduatedValues), count($scholarshipHonors));
    for ($index = 0; $index < $educationCount; $index++) {
        $level = trim((string)(cleanText($levels[$index] ?? null) ?? ''));
        $schoolName = trim((string)(cleanText($schoolNames[$index] ?? null) ?? ''));
        $degreeCourse = trim((string)(cleanText($degreeCourses[$index] ?? null) ?? ''));
        $attendanceFrom = trim((string)(cleanText($attendanceFromYears[$index] ?? null) ?? ''));
        $attendanceTo = trim((string)(cleanText($attendanceToYears[$index] ?? null) ?? ''));
        $highestUnits = trim((string)(cleanText($highestLevelsUnitsEarned[$index] ?? null) ?? ''));
        $yearGraduated = trim((string)(cleanText($yearGraduatedValues[$index] ?? null) ?? ''));
        $honors = trim((string)(cleanText($scholarshipHonors[$index] ?? null) ?? ''));

        if ($level === '' && $schoolName === '' && $degreeCourse === '' && $attendanceFrom === '' && $attendanceTo === '' && $highestUnits === '' && $yearGraduated === '' && $honors === '') {
            continue;
        }

        $educationRecommendation[] = [
            'education_level' => $level,
            'school_name' => $schoolName,
            'degree_course' => $degreeCourse,
            'attendance_from_year' => $attendanceFrom,
            'attendance_to_year' => $attendanceTo,
            'highest_level_units_earned' => $highestUnits,
            'year_graduated' => $yearGraduated,
            'scholarship_honors_received' => $honors,
        ];
    }

    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => (string)$adminUserId,
                'category' => 'employee_profile',
                'title' => 'Employee Profile Recommendation',
                'body' => 'A staff recommendation was submitted to update profile information for ' . $firstName . ' ' . $surname . '. Please review for final approval.'
                    . ($recommendationNotes !== '' ? (' Notes: ' . $recommendationNotes) : ''),
                'link_url' => '/hris-system/pages/admin/personal-information.php',
            ]]
        );
    }

    $employeeUserId = cleanText($personRow['user_id'] ?? null) ?? '';
    if (isValidUuid($employeeUserId) && strcasecmp($employeeUserId, (string)$staffUserId) !== 0) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'employee_profile',
                'title' => 'Profile Update Recommendation Submitted',
                'body' => 'A profile update recommendation was submitted for your record. Final approval will be handled by admin.',
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'recommend_employee_profile_update',
            'old_data' => [
                'first_name' => cleanText($personRow['first_name'] ?? null),
                'middle_name' => cleanText($personRow['middle_name'] ?? null),
                'surname' => cleanText($personRow['surname'] ?? null),
                'name_extension' => cleanText($personRow['name_extension'] ?? null),
                'personal_email' => cleanText($personRow['personal_email'] ?? null),
                'mobile_no' => cleanText($personRow['mobile_no'] ?? null),
            ],
            'new_data' => [
                'recommended_profile' => $patchPayload,
                'recommended_profile_changes' => $recommendedProfileChanges,
                'recommended_addresses' => [
                    'residential' => $residentialRecommendation,
                    'permanent' => $permanentRecommendation,
                ],
                'recommended_government_ids' => $governmentRecommendation,
                'recommended_family' => array_merge($familyRecommendation, [
                    'children' => $childrenRecommendation,
                ]),
                'recommended_educational_backgrounds' => $educationRecommendation,
                'recommendation_notes' => $recommendationNotes,
                'submitted_for_admin_approval' => true,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee profile update recommendation submitted to admin for approval.');
}

if ($action === 'assign_department_position') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $employmentId = cleanText($_POST['employment_id'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $recommendationNotes = trim((string)(cleanText($_POST['recommendation_notes'] ?? null) ?? ''));

    if (!isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Select a valid division and position.');
    }

    if ($recommendationNotes === '') {
        redirectWithState('error', 'Recommendation notes are required for division/position recommendations.');
    }

    $employmentRow = $resolveScopedEmployment($employmentId, $personId);
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'Employee scope validation failed.');
    }

    $oldData = [
        'office_id' => cleanText($employmentRow['office_id'] ?? null),
        'position_id' => cleanText($employmentRow['position_id'] ?? null),
    ];

    $recommendedOfficeName = '';
    $officeResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/offices?select=office_name&id=eq.' . rawurlencode($officeId) . '&limit=1',
        $headers
    );
    if (isSuccessful($officeResponse) && is_array($officeResponse['data'][0] ?? null)) {
        $recommendedOfficeName = cleanText($officeResponse['data'][0]['office_name'] ?? null) ?? '';
    }

    $recommendedPositionTitle = '';
    $positionResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_positions?select=position_title&id=eq.' . rawurlencode($positionId) . '&limit=1',
        $headers
    );
    if (isSuccessful($positionResponse) && is_array($positionResponse['data'][0] ?? null)) {
        $recommendedPositionTitle = cleanText($positionResponse['data'][0]['position_title'] ?? null) ?? '';
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
    $employeeUserId = cleanText($personRow['user_id'] ?? null) ?? '';
    $employeeName = is_array($personRow)
        ? trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''))
        : 'Employee';
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    if (isValidUuid($employeeUserId) && strcasecmp($employeeUserId, (string)$staffUserId) !== 0) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'employee_profile',
                'title' => 'Division/Position Recommendation Submitted',
                'body' => 'A recommendation was submitted to update your division/position assignment. Notes: ' . $recommendationNotes,
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => (string)$adminUserId,
                'category' => 'employee_profile',
                'title' => 'Division/Position Recommendation',
                'body' => 'Recommendation for ' . $employeeName . ': set division to ' . ($recommendedOfficeName !== '' ? $recommendedOfficeName : 'selected division') . ' and position to ' . ($recommendedPositionTitle !== '' ? $recommendedPositionTitle : 'selected position') . '. Notes: ' . $recommendationNotes,
                'link_url' => '/hris-system/pages/admin/personal-information.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => $employmentId,
            'action_name' => 'recommend_department_position',
            'old_data' => $oldData,
            'new_data' => [
                'employment_status' => strtolower((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'inactive')),
                'recommended_office_id' => $officeId,
                'recommended_position_id' => $positionId,
                'recommended_office_name' => $recommendedOfficeName,
                'recommended_position_title' => $recommendedPositionTitle,
                'recommendation_notes' => $recommendationNotes,
                'submitted_for_admin_approval' => true,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Division/position recommendation submitted to admin for approval.');
}

if ($action === 'update_employee_status') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $employmentId = cleanText($_POST['employment_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $statusSpecification = trim((string)(cleanText($_POST['status_specification'] ?? null) ?? ''));

    if (!in_array($newStatus, ['active', 'inactive'], true)) {
        redirectWithState('error', 'Invalid status selected.');
    }

    if ($statusSpecification === '') {
        redirectWithState('error', 'Recommendation notes are required for status recommendations.');
    }

    $employmentRow = $resolveScopedEmployment($employmentId, $personId);
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'Employee scope validation failed.');
    }

    $oldStatus = strtolower((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'inactive'));
    $oldSeparationDate = cleanText($employmentRow['separation_date'] ?? null);
    $oldSeparationReason = cleanText($employmentRow['separation_reason'] ?? null);

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . rawurlencode($personId) . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
    $employeeUserId = cleanText($personRow['user_id'] ?? null) ?? '';
    $employeeName = is_array($personRow)
        ? trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''))
        : 'Employee';
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    if (isValidUuid($employeeUserId) && strcasecmp($employeeUserId, (string)$staffUserId) !== 0) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'employee_profile',
                'title' => 'Employment Status Recommendation Submitted',
                'body' => 'A status recommendation (' . ucfirst($newStatus) . ') was submitted for your employment profile. Final approval will be done by admin.',
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => (string)$adminUserId,
                'category' => 'employee_profile',
                'title' => 'Employee Status Recommendation',
                'body' => 'Recommendation for ' . $employeeName . ': set status to ' . ucfirst($newStatus) . '. Review for final approval.',
                'link_url' => '/hris-system/pages/admin/personal-information.php',
            ]]
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => $employmentId,
            'action_name' => 'recommend_employee_status',
            'old_data' => [
                'employment_status' => $oldStatus,
                'separation_date' => $oldSeparationDate,
                'separation_reason' => $oldSeparationReason,
            ],
            'new_data' => [
                'employment_status' => $oldStatus,
                'recommended_status' => $newStatus,
                'status_specification' => $statusSpecification,
                'submitted_for_admin_approval' => true,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Status recommendation for ' . $employeeName . ' was submitted to admin.');
}

redirectWithState('error', 'Unknown personal information action.');