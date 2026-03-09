<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('isValidUuid')) {
    function isValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'create_staff_account' || $action === 'create_user_account') {
    redirectWithState('error', 'Account creation was removed from this module.');
}

if ($action === 'create_staff_account' || $action === 'create_user_account') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $email = strtolower((string)(cleanText($_POST['email'] ?? null) ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $roleKey = strtolower((string)(cleanText($_POST['role_key'] ?? null) ?? 'staff'));
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $notes = cleanText($_POST['account_notes'] ?? null);

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Select a valid employee profile.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Valid email is required for account creation.');
    }

    if (strlen($password) < 8) {
        redirectWithState('error', 'Password must be at least 8 characters.');
    }

    $allowedRoleKeys = ['employee', 'staff'];
    if (!in_array($roleKey, $allowedRoleKeys, true)) {
        redirectWithState('error', 'Invalid account role selected.');
    }

    if (!isValidUuid($officeId)) {
        redirectWithState('error', 'Select a valid division for this account.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,personal_email&id=eq.' . $personId . '&limit=1',
        $headers
    );

    if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
        redirectWithState('error', 'Employee profile not found.');
    }

    $personRow = (array)$personResponse['data'][0];
    $existingUserId = cleanText($personRow['user_id'] ?? null);
    if ($existingUserId !== null && isValidUuid($existingUserId)) {
        redirectWithState('error', 'This employee profile already has a linked user account.');
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,office_id,is_current&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );

    if (!isSuccessful($employmentResponse) || empty((array)($employmentResponse['data'] ?? []))) {
        redirectWithState('error', 'Assign division and position first so a current employment record exists.');
    }

    $employmentRow = (array)$employmentResponse['data'][0];
    $employmentOfficeId = cleanText($employmentRow['office_id'] ?? null);
    if ($employmentOfficeId !== null && isValidUuid($employmentOfficeId) && strcasecmp($employmentOfficeId, $officeId) !== 0) {
        redirectWithState('error', 'Selected division must match the employee\'s current employment division.');
    }

    $existingAccountResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id&email=eq.' . encodeFilter($email) . '&limit=1',
        $headers
    );

    if (isSuccessful($existingAccountResponse) && !empty((array)($existingAccountResponse['data'] ?? []))) {
        redirectWithState('error', 'Email is already in use by an existing account.');
    }

    $fullName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));

    $createAuthResponse = apiRequest(
        'POST',
        $supabaseUrl . '/auth/v1/admin/users',
        $headers,
        [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => [
                'full_name' => $fullName,
                'created_by_admin' => $adminUserId,
            ],
        ]
    );

    if (!isSuccessful($createAuthResponse)) {
        $raw = strtolower((string)($createAuthResponse['raw'] ?? ''));
        if (str_contains($raw, 'already') || str_contains($raw, 'exists')) {
            redirectWithState('error', 'Email already exists in authentication.');
        }
        redirectWithState('error', 'Failed to create authentication credentials for this account.');
    }

    $newUserId = (string)($createAuthResponse['data']['id'] ?? '');
    if (!isValidUuid($newUserId)) {
        redirectWithState('error', 'Authentication user was created but no valid user ID was returned.');
    }

    $accountResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/user_accounts',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'id' => $newUserId,
            'email' => $email,
            'account_status' => 'active',
            'email_verified_at' => gmdate('c'),
            'must_change_password' => true,
        ]]
    );

    if (!isSuccessful($accountResponse)) {
        redirectWithState('error', 'Authentication user created, but failed to create user account profile.');
    }

    $linkPersonResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'user_id' => $newUserId,
            'personal_email' => $email,
        ]
    );

    if (!isSuccessful($linkPersonResponse)) {
        redirectWithState('error', 'User account created, but failed to link account to employee profile.');
    }

    $roleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.' . $roleKey . '&limit=1',
        $headers
    );

    $roleId = (string)($roleResponse['data'][0]['id'] ?? '');
    if (!isValidUuid($roleId)) {
        redirectWithState('error', 'Selected role definition not found. Please contact system administrator.');
    }

    $roleAssignmentResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/user_role_assignments',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'user_id' => $newUserId,
            'role_id' => $roleId,
            'office_id' => $officeId,
            'is_primary' => true,
            'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
            'assigned_at' => gmdate('c'),
        ]]
    );

    if (!isSuccessful($roleAssignmentResponse)) {
        redirectWithState('error', 'User account was created, but failed to assign selected role.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'create_user_account',
            'old_data' => null,
            'new_data' => [
                'linked_user_id' => $newUserId,
                'email' => $email,
                'role_key' => $roleKey,
                'office_id' => $officeId,
                'notes' => $notes,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'User account created and linked successfully.');
}

if ($action === 'save_profile') {
    $profileAction = strtolower((string)(cleanText($_POST['profile_action'] ?? null) ?? 'edit'));
    $personId = cleanText($_POST['person_id'] ?? null);
    $firstNameInput = trim((string)(cleanText($_POST['first_name'] ?? null) ?? ''));
    $middleNameInput = trim((string)(cleanText($_POST['middle_name'] ?? null) ?? ''));
    $surnameInput = trim((string)(cleanText($_POST['surname'] ?? null) ?? ''));
    $nameExtensionInput = trim((string)(cleanText($_POST['name_extension'] ?? null) ?? ''));
    $dateOfBirthInput = trim((string)(cleanText($_POST['date_of_birth'] ?? null) ?? ''));
    $placeOfBirthInput = trim((string)(cleanText($_POST['place_of_birth'] ?? null) ?? ''));
    $sexAtBirthInput = strtolower(trim((string)(cleanText($_POST['sex_at_birth'] ?? null) ?? '')));
    $civilStatusInput = trim((string)(cleanText($_POST['civil_status'] ?? null) ?? ''));
    $heightMInput = trim((string)(cleanText($_POST['height_m'] ?? null) ?? ''));
    $weightKgInput = trim((string)(cleanText($_POST['weight_kg'] ?? null) ?? ''));
    $bloodTypeInput = trim((string)(cleanText($_POST['blood_type'] ?? null) ?? ''));
    $citizenshipInput = trim((string)(cleanText($_POST['citizenship'] ?? null) ?? ''));
    $dualCitizenshipCountryInput = trim((string)(cleanText($_POST['dual_citizenship_country'] ?? null) ?? ''));
    $telephoneNoInput = trim((string)(cleanText($_POST['telephone_no'] ?? null) ?? ''));
    $residentialHouseNoInput = trim((string)(cleanText($_POST['residential_house_no'] ?? null) ?? ''));
    $residentialStreetInput = trim((string)(cleanText($_POST['residential_street'] ?? null) ?? ''));
    $residentialSubdivisionInput = trim((string)(cleanText($_POST['residential_subdivision'] ?? null) ?? ''));
    $residentialBarangayInput = trim((string)(cleanText($_POST['residential_barangay'] ?? null) ?? ''));
    $residentialCityInput = trim((string)(cleanText($_POST['residential_city_municipality'] ?? null) ?? ''));
    $residentialProvinceInput = trim((string)(cleanText($_POST['residential_province'] ?? null) ?? ''));
    $residentialZipCodeInput = trim((string)(cleanText($_POST['residential_zip_code'] ?? null) ?? ''));
    $permanentHouseNoInput = trim((string)(cleanText($_POST['permanent_house_no'] ?? null) ?? ''));
    $permanentStreetInput = trim((string)(cleanText($_POST['permanent_street'] ?? null) ?? ''));
    $permanentSubdivisionInput = trim((string)(cleanText($_POST['permanent_subdivision'] ?? null) ?? ''));
    $permanentBarangayInput = trim((string)(cleanText($_POST['permanent_barangay'] ?? null) ?? ''));
    $permanentCityInput = trim((string)(cleanText($_POST['permanent_city_municipality'] ?? null) ?? ''));
    $permanentProvinceInput = trim((string)(cleanText($_POST['permanent_province'] ?? null) ?? ''));
    $permanentZipCodeInput = trim((string)(cleanText($_POST['permanent_zip_code'] ?? null) ?? ''));
    $umidNoInput = trim((string)(cleanText($_POST['umid_no'] ?? null) ?? ''));
    $pagibigNoInput = trim((string)(cleanText($_POST['pagibig_no'] ?? null) ?? ''));
    $philhealthNoInput = trim((string)(cleanText($_POST['philhealth_no'] ?? null) ?? ''));
    $psnNoInput = trim((string)(cleanText($_POST['psn_no'] ?? null) ?? ''));
    $tinNoInput = trim((string)(cleanText($_POST['tin_no'] ?? null) ?? ''));
    $agencyEmployeeNoInput = trim((string)(cleanText($_POST['agency_employee_no'] ?? null) ?? ''));
    $employeeNameInput = trim((string)(cleanText($_POST['employee_name'] ?? null) ?? ''));
    $email = strtolower((string)(cleanText($_POST['email'] ?? null) ?? ''));
    $mobileNo = (string)(cleanText($_POST['mobile_no'] ?? null) ?? '');

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

    $educationLevels = (array)($_POST['education_level'] ?? []);
    $educationSchoolNames = (array)($_POST['education_school_name'] ?? ($_POST['school_name'] ?? []));
    $educationDegreeCourses = (array)($_POST['education_course_degree'] ?? ($_POST['degree_course'] ?? []));
    $educationAttendanceFrom = (array)($_POST['education_period_from'] ?? ($_POST['attendance_from_year'] ?? []));
    $educationAttendanceTo = (array)($_POST['education_period_to'] ?? ($_POST['attendance_to_year'] ?? []));
    $educationHighestUnits = (array)($_POST['education_highest_level_units'] ?? ($_POST['highest_level_units_earned'] ?? []));
    $educationYearGraduated = (array)($_POST['education_year_graduated'] ?? ($_POST['year_graduated'] ?? []));
    $educationHonors = (array)($_POST['education_honors_received'] ?? ($_POST['scholarship_honors_received'] ?? []));

    $firstName = $firstNameInput;
    $surname = $surnameInput;
    if (($firstName === '' || $surname === '') && $employeeNameInput !== '') {
        [$parsedFirstName, $parsedSurname] = splitFullName($employeeNameInput);
        if ($firstName === '') {
            $firstName = trim((string)$parsedFirstName);
        }
        if ($surname === '') {
            $surname = trim((string)$parsedSurname);
        }
    }
    $employeeName = trim($firstName . ' ' . $middleNameInput . ' ' . $surname . ' ' . $nameExtensionInput);

    if (($profileAction === 'add' || $profileAction === 'edit') && ($firstName === '' || $surname === '')) {
        redirectWithState('error', 'First name and last name are required.');
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Please provide a valid email address.');
    }

    if ($mobileNo !== '' && !preg_match('/^\+?[0-9][0-9\s-]{6,19}$/', $mobileNo)) {
        redirectWithState('error', 'Please provide a valid contact number.');
    }

    if ($dateOfBirthInput !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirthInput)) {
        redirectWithState('error', 'Invalid date of birth format.');
    }

    if ($sexAtBirthInput !== '' && !in_array($sexAtBirthInput, ['male', 'female'], true)) {
        redirectWithState('error', 'Sex at birth must be either male or female.');
    }

    $heightM = null;
    if ($heightMInput !== '') {
        if (!is_numeric($heightMInput) || (float)$heightMInput < 0) {
            redirectWithState('error', 'Height must be a valid non-negative number.');
        }
        $heightM = (float)$heightMInput;
    }

    $weightKg = null;
    if ($weightKgInput !== '') {
        if (!is_numeric($weightKgInput) || (float)$weightKgInput < 0) {
            redirectWithState('error', 'Weight must be a valid non-negative number.');
        }
        $weightKg = (float)$weightKgInput;
    }

    $dualCitizenship = $dualCitizenshipCountryInput !== '';

    $syncAddressAndGovernmentIds = static function (string $targetPersonId) use (
        $supabaseUrl,
        $headers,
        $residentialHouseNoInput,
        $residentialStreetInput,
        $residentialSubdivisionInput,
        $residentialBarangayInput,
        $residentialCityInput,
        $residentialProvinceInput,
        $residentialZipCodeInput,
        $permanentHouseNoInput,
        $permanentStreetInput,
        $permanentSubdivisionInput,
        $permanentBarangayInput,
        $permanentCityInput,
        $permanentProvinceInput,
        $permanentZipCodeInput,
        $umidNoInput,
        $pagibigNoInput,
        $philhealthNoInput,
        $psnNoInput,
        $tinNoInput
    ): void {
        $existingAddressesResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_addresses?select=id,address_type&person_id=eq.' . $targetPersonId . '&address_type=in.(residential,permanent)&limit=10',
            $headers
        );

        $existingAddressByType = [];
        if (isSuccessful($existingAddressesResponse)) {
            foreach ((array)$existingAddressesResponse['data'] as $address) {
                $type = strtolower((string)($address['address_type'] ?? ''));
                $id = (string)($address['id'] ?? '');
                if ($type !== '' && $id !== '') {
                    $existingAddressByType[$type] = $id;
                }
            }
        }

        $addressRows = [
            'residential' => [
                'house_no' => $residentialHouseNoInput,
                'street' => $residentialStreetInput,
                'subdivision' => $residentialSubdivisionInput,
                'barangay' => $residentialBarangayInput,
                'city_municipality' => $residentialCityInput,
                'province' => $residentialProvinceInput,
                'zip_code' => $residentialZipCodeInput,
            ],
            'permanent' => [
                'house_no' => $permanentHouseNoInput,
                'street' => $permanentStreetInput,
                'subdivision' => $permanentSubdivisionInput,
                'barangay' => $permanentBarangayInput,
                'city_municipality' => $permanentCityInput,
                'province' => $permanentProvinceInput,
                'zip_code' => $permanentZipCodeInput,
            ],
        ];

        foreach ($addressRows as $addressType => $addressPayload) {
            $hasValue = false;
            foreach ($addressPayload as $value) {
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
                'house_no' => $addressPayload['house_no'] !== '' ? $addressPayload['house_no'] : null,
                'street' => $addressPayload['street'] !== '' ? $addressPayload['street'] : null,
                'subdivision' => $addressPayload['subdivision'] !== '' ? $addressPayload['subdivision'] : null,
                'barangay' => $addressPayload['barangay'] !== '' ? $addressPayload['barangay'] : null,
                'city_municipality' => $addressPayload['city_municipality'] !== '' ? $addressPayload['city_municipality'] : null,
                'province' => $addressPayload['province'] !== '' ? $addressPayload['province'] : null,
                'zip_code' => $addressPayload['zip_code'] !== '' ? $addressPayload['zip_code'] : null,
                'country' => 'Philippines',
                'is_primary' => true,
            ];

            if ($existingAddressId !== '') {
                $addressResponse = apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/person_addresses?id=eq.' . $existingAddressId,
                    array_merge($headers, ['Prefer: return=minimal']),
                    $payload
                );
            } else {
                $addressResponse = apiRequest(
                    'POST',
                    $supabaseUrl . '/rest/v1/person_addresses',
                    array_merge($headers, ['Prefer: return=minimal']),
                    [$payload]
                );
            }

            if (!isSuccessful($addressResponse)) {
                redirectWithState('error', 'Failed to save ' . $addressType . ' address.');
            }
        }

        $existingGovernmentIdsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type&person_id=eq.' . $targetPersonId . '&limit=20',
            $headers
        );

        $existingGovernmentIdByType = [];
        if (isSuccessful($existingGovernmentIdsResponse)) {
            foreach ((array)$existingGovernmentIdsResponse['data'] as $governmentIdRow) {
                $idType = strtolower((string)($governmentIdRow['id_type'] ?? ''));
                $id = (string)($governmentIdRow['id'] ?? '');
                if ($idType !== '' && $id !== '') {
                    $existingGovernmentIdByType[$idType] = $id;
                }
            }
        }

        $governmentIdsPayload = [
            'umid' => $umidNoInput,
            'pagibig' => $pagibigNoInput,
            'philhealth' => $philhealthNoInput,
            'psn' => $psnNoInput,
            'tin' => $tinNoInput,
        ];

        foreach ($governmentIdsPayload as $idType => $idValue) {
            $existingGovernmentId = $existingGovernmentIdByType[$idType] ?? '';
            if ($idValue === '' && $existingGovernmentId === '') {
                continue;
            }

            if ($existingGovernmentId !== '') {
                if ($idValue === '') {
                    $governmentIdResponse = apiRequest(
                        'DELETE',
                        $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . $existingGovernmentId,
                        array_merge($headers, ['Prefer: return=minimal'])
                    );
                } else {
                    $governmentIdResponse = apiRequest(
                        'PATCH',
                        $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . $existingGovernmentId,
                        array_merge($headers, ['Prefer: return=minimal']),
                        [
                            'id_value_encrypted' => $idValue,
                            'last4' => substr(preg_replace('/\D/', '', $idValue), -4),
                        ]
                    );
                }
            } else {
                $governmentIdResponse = apiRequest(
                    'POST',
                    $supabaseUrl . '/rest/v1/person_government_ids',
                    array_merge($headers, ['Prefer: return=minimal']),
                    [[
                        'person_id' => $targetPersonId,
                        'id_type' => $idType,
                        'id_value_encrypted' => $idValue,
                        'last4' => $idValue !== '' ? substr(preg_replace('/\D/', '', $idValue), -4) : null,
                    ]]
                );
            }

            if (!isSuccessful($governmentIdResponse)) {
                redirectWithState('error', 'Failed to save government ID (' . strtoupper($idType) . ').');
            }
        }
    };

    $syncFamilyAndEducationalBackground = static function (string $targetPersonId) use (
        $supabaseUrl,
        $headers,
        $spouseSurname,
        $spouseFirstName,
        $spouseMiddleName,
        $spouseExtensionName,
        $spouseOccupation,
        $spouseEmployerBusinessName,
        $spouseBusinessAddress,
        $spouseTelephoneNo,
        $fatherSurname,
        $fatherFirstName,
        $fatherMiddleName,
        $fatherExtensionName,
        $motherSurname,
        $motherFirstName,
        $motherMiddleName,
        $motherExtensionName,
        $childrenFullNames,
        $childrenBirthDates,
        $educationLevels,
        $educationSchoolNames,
        $educationDegreeCourses,
        $educationAttendanceFrom,
        $educationAttendanceTo,
        $educationHighestUnits,
        $educationYearGraduated,
        $educationHonors
    ): void {
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
            $supabaseUrl . '/rest/v1/person_family_spouses?select=id&person_id=eq.' . $targetPersonId . '&order=sequence_no.asc&limit=1',
            $headers
        );
        $existingSpouse = $existingSpouseResponse['data'][0] ?? null;
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
                    $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . (string)$existingSpouse['id'],
                    array_merge($headers, ['Prefer: return=minimal']),
                    $spousePayload
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
                $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . (string)$existingSpouse['id'],
                array_merge($headers, ['Prefer: return=minimal'])
            );

            if (!isSuccessful($spouseDeleteResponse)) {
                redirectWithState('error', 'Failed to clear spouse details.');
            }
        }

        $upsertParent = static function (string $parentType, array $payload) use ($targetPersonId, $supabaseUrl, $headers): void {
            $existingParentResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/person_parents?select=id&person_id=eq.' . $targetPersonId . '&parent_type=eq.' . $parentType . '&limit=1',
                $headers
            );
            $existingParent = $existingParentResponse['data'][0] ?? null;

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
                        $supabaseUrl . '/rest/v1/person_parents?id=eq.' . (string)$existingParent['id'],
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
                    $supabaseUrl . '/rest/v1/person_parents?id=eq.' . (string)$existingParent['id'],
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
            $supabaseUrl . '/rest/v1/person_family_children?person_id=eq.' . $targetPersonId,
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

        $allowedLevels = ['elementary', 'secondary', 'vocational_trade_course', 'college', 'graduate_studies'];
        $levelAliases = [
            'vocational' => 'vocational_trade_course',
            'vocational_trade' => 'vocational_trade_course',
            'graduate' => 'graduate_studies',
        ];
        $levelOrder = array_flip($allowedLevels);

        $recordCount = max(
            count($educationLevels),
            count($educationSchoolNames),
            count($educationDegreeCourses),
            count($educationAttendanceFrom),
            count($educationAttendanceTo),
            count($educationHighestUnits),
            count($educationYearGraduated),
            count($educationHonors)
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
            $level = strtolower(trim((string)(cleanText($educationLevels[$index] ?? null) ?? '')));
            if (isset($levelAliases[$level])) {
                $level = $levelAliases[$level];
            }
            if ($level === '' || !in_array($level, $allowedLevels, true)) {
                continue;
            }

            $schoolName = trim((string)(cleanText($educationSchoolNames[$index] ?? null) ?? ''));
            $degreeCourse = trim((string)(cleanText($educationDegreeCourses[$index] ?? null) ?? ''));
            $attendanceFromRaw = trim((string)(cleanText($educationAttendanceFrom[$index] ?? null) ?? ''));
            $attendanceToRaw = trim((string)(cleanText($educationAttendanceTo[$index] ?? null) ?? ''));
            $highestUnits = trim((string)(cleanText($educationHighestUnits[$index] ?? null) ?? ''));
            $yearGraduatedRaw = trim((string)(cleanText($educationYearGraduated[$index] ?? null) ?? ''));
            $honors = trim((string)(cleanText($educationHonors[$index] ?? null) ?? ''));

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
                || $honors !== '';

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
                'scholarship_honors_received' => $honors !== '' ? $honors : null,
                'sequence_no' => (int)($levelOrder[$level] ?? 0) + 1,
            ];
        }

        $deleteEducationalResponse = apiRequest(
            'DELETE',
            $supabaseUrl . '/rest/v1/person_educational_backgrounds?person_id=eq.' . $targetPersonId,
            array_merge($headers, ['Prefer: return=minimal'])
        );

        if (!isSuccessful($deleteEducationalResponse)) {
            redirectWithState('error', 'Failed to reset educational background records.');
        }

        if (!empty($validRecords)) {
            uasort($validRecords, static function (array $left, array $right): int {
                return (int)($left['sequence_no'] ?? 0) <=> (int)($right['sequence_no'] ?? 0);
            });

            $insertEducationalResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/person_educational_backgrounds',
                array_merge($headers, ['Prefer: return=minimal']),
                array_values($validRecords)
            );

            if (!isSuccessful($insertEducationalResponse)) {
                redirectWithState('error', 'Failed to save educational background records.');
            }
        }
    };

    if (!in_array($profileAction, ['add', 'edit', 'archive'], true)) {
        redirectWithState('error', 'Invalid profile action selected.');
    }

    if ($profileAction === 'add') {
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'first_name' => $firstName,
                'middle_name' => $middleNameInput !== '' ? $middleNameInput : null,
                'surname' => $surname,
                'name_extension' => $nameExtensionInput !== '' ? $nameExtensionInput : null,
                'date_of_birth' => $dateOfBirthInput !== '' ? $dateOfBirthInput : null,
                'place_of_birth' => $placeOfBirthInput !== '' ? $placeOfBirthInput : null,
                'sex_at_birth' => $sexAtBirthInput !== '' ? $sexAtBirthInput : null,
                'civil_status' => $civilStatusInput !== '' ? $civilStatusInput : null,
                'height_m' => $heightM,
                'weight_kg' => $weightKg,
                'blood_type' => $bloodTypeInput !== '' ? $bloodTypeInput : null,
                'citizenship' => $citizenshipInput !== '' ? $citizenshipInput : null,
                'dual_citizenship' => $dualCitizenship,
                'dual_citizenship_country' => $dualCitizenshipCountryInput !== '' ? $dualCitizenshipCountryInput : null,
                'telephone_no' => $telephoneNoInput !== '' ? $telephoneNoInput : null,
                'personal_email' => $email !== '' ? $email : null,
                'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
                'agency_employee_no' => $agencyEmployeeNoInput !== '' ? $agencyEmployeeNoInput : null,
            ]]
        );

        if (!isSuccessful($insertResponse)) {
            $insertRaw = strtolower(trim((string)($insertResponse['raw'] ?? '')));
            if ($insertRaw !== '') {
                if (str_contains($insertRaw, 'agency_employee_no') || str_contains($insertRaw, 'people_agency_employee_no_key')) {
                    redirectWithState('error', 'Failed to create employee profile. Agency employee number already exists.');
                }

                if (str_contains($insertRaw, 'personal_email') || str_contains($insertRaw, 'duplicate key')) {
                    redirectWithState('error', 'Failed to create employee profile. Email is already used by another employee profile.');
                }
            }

            redirectWithState('error', 'Failed to create employee profile. Please check unique fields and required values.');
        }

        $newPersonId = (string)($insertResponse['data'][0]['id'] ?? '');
        if ($newPersonId !== '') {
            $syncAddressAndGovernmentIds($newPersonId);
            $syncFamilyAndEducationalBackground($newPersonId);
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'people',
                'entity_id' => $newPersonId !== '' ? $newPersonId : null,
                'action_name' => 'add_profile',
                'old_data' => null,
                'new_data' => [
                    'first_name' => $firstName,
                    'middle_name' => $middleNameInput,
                    'surname' => $surname,
                    'name_extension' => $nameExtensionInput,
                    'date_of_birth' => $dateOfBirthInput,
                    'place_of_birth' => $placeOfBirthInput,
                    'sex_at_birth' => $sexAtBirthInput,
                    'civil_status' => $civilStatusInput,
                    'height_m' => $heightM,
                    'weight_kg' => $weightKg,
                    'blood_type' => $bloodTypeInput,
                    'citizenship' => $citizenshipInput,
                    'dual_citizenship' => $dualCitizenship,
                    'dual_citizenship_country' => $dualCitizenshipCountryInput,
                    'telephone_no' => $telephoneNoInput,
                    'employee_name' => $employeeName,
                    'email' => $email,
                    'mobile_no' => $mobileNo,
                    'agency_employee_no' => $agencyEmployeeNoInput,
                    'residential_address' => [
                        'house_no' => $residentialHouseNoInput,
                        'street' => $residentialStreetInput,
                        'subdivision' => $residentialSubdivisionInput,
                        'barangay' => $residentialBarangayInput,
                        'city_municipality' => $residentialCityInput,
                        'province' => $residentialProvinceInput,
                        'zip_code' => $residentialZipCodeInput,
                    ],
                    'permanent_address' => [
                        'house_no' => $permanentHouseNoInput,
                        'street' => $permanentStreetInput,
                        'subdivision' => $permanentSubdivisionInput,
                        'barangay' => $permanentBarangayInput,
                        'city_municipality' => $permanentCityInput,
                        'province' => $permanentProvinceInput,
                        'zip_code' => $permanentZipCodeInput,
                    ],
                    'government_ids' => [
                        'umid' => $umidNoInput,
                        'pagibig' => $pagibigNoInput,
                        'philhealth' => $philhealthNoInput,
                        'psn' => $psnNoInput,
                        'tin' => $tinNoInput,
                    ],
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Employee profile created successfully.');
    }

    if (!isValidUuid((string)$personId)) {
        redirectWithState('error', 'Please select a valid employee profile.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no&id=eq.' . $personId . '&limit=1',
        $headers
    );

    $personRow = $personResponse['data'][0] ?? null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile not found.');
    }

    if ($profileAction === 'edit') {
        $linkedUserId = (string)($personRow['user_id'] ?? '');
        if ($linkedUserId !== '' && $email !== '') {
            $accountEmailPatchResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $linkedUserId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'email' => $email,
                    'updated_at' => gmdate('c'),
                ]
            );

            if (!isSuccessful($accountEmailPatchResponse)) {
                redirectWithState('error', 'Failed to update linked user account email. The email may already be in use.');
            }
        }

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'first_name' => $firstName,
                'middle_name' => $middleNameInput !== '' ? $middleNameInput : null,
                'surname' => $surname,
                'name_extension' => $nameExtensionInput !== '' ? $nameExtensionInput : null,
                'date_of_birth' => $dateOfBirthInput !== '' ? $dateOfBirthInput : null,
                'place_of_birth' => $placeOfBirthInput !== '' ? $placeOfBirthInput : null,
                'sex_at_birth' => $sexAtBirthInput !== '' ? $sexAtBirthInput : null,
                'civil_status' => $civilStatusInput !== '' ? $civilStatusInput : null,
                'height_m' => $heightM,
                'weight_kg' => $weightKg,
                'blood_type' => $bloodTypeInput !== '' ? $bloodTypeInput : null,
                'citizenship' => $citizenshipInput !== '' ? $citizenshipInput : null,
                'dual_citizenship' => $dualCitizenship,
                'dual_citizenship_country' => $dualCitizenshipCountryInput !== '' ? $dualCitizenshipCountryInput : null,
                'telephone_no' => $telephoneNoInput !== '' ? $telephoneNoInput : null,
                'personal_email' => $email !== '' ? $email : null,
                'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
                'agency_employee_no' => $agencyEmployeeNoInput !== '' ? $agencyEmployeeNoInput : null,
                'updated_at' => gmdate('c'),
            ]
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update employee profile.');
        }

        $syncAddressAndGovernmentIds($personId);
        $syncFamilyAndEducationalBackground($personId);

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'people',
                'entity_id' => $personId,
                'action_name' => 'edit_profile',
                'old_data' => [
                    'first_name' => $personRow['first_name'] ?? null,
                    'middle_name' => $personRow['middle_name'] ?? null,
                    'surname' => $personRow['surname'] ?? null,
                    'name_extension' => $personRow['name_extension'] ?? null,
                    'date_of_birth' => $personRow['date_of_birth'] ?? null,
                    'place_of_birth' => $personRow['place_of_birth'] ?? null,
                    'sex_at_birth' => $personRow['sex_at_birth'] ?? null,
                    'civil_status' => $personRow['civil_status'] ?? null,
                    'height_m' => $personRow['height_m'] ?? null,
                    'weight_kg' => $personRow['weight_kg'] ?? null,
                    'blood_type' => $personRow['blood_type'] ?? null,
                    'citizenship' => $personRow['citizenship'] ?? null,
                    'dual_citizenship' => $personRow['dual_citizenship'] ?? null,
                    'dual_citizenship_country' => $personRow['dual_citizenship_country'] ?? null,
                    'telephone_no' => $personRow['telephone_no'] ?? null,
                    'personal_email' => $personRow['personal_email'] ?? null,
                    'mobile_no' => $personRow['mobile_no'] ?? null,
                    'agency_employee_no' => $personRow['agency_employee_no'] ?? null,
                ],
                'new_data' => [
                    'first_name' => $firstName,
                    'middle_name' => $middleNameInput,
                    'surname' => $surname,
                    'name_extension' => $nameExtensionInput,
                    'date_of_birth' => $dateOfBirthInput,
                    'place_of_birth' => $placeOfBirthInput,
                    'sex_at_birth' => $sexAtBirthInput,
                    'civil_status' => $civilStatusInput,
                    'height_m' => $heightM,
                    'weight_kg' => $weightKg,
                    'blood_type' => $bloodTypeInput,
                    'citizenship' => $citizenshipInput,
                    'dual_citizenship' => $dualCitizenship,
                    'dual_citizenship_country' => $dualCitizenshipCountryInput,
                    'telephone_no' => $telephoneNoInput,
                    'employee_name' => $employeeName,
                    'email' => $email,
                    'mobile_no' => $mobileNo,
                    'agency_employee_no' => $agencyEmployeeNoInput,
                    'residential_address' => [
                        'house_no' => $residentialHouseNoInput,
                        'street' => $residentialStreetInput,
                        'subdivision' => $residentialSubdivisionInput,
                        'barangay' => $residentialBarangayInput,
                        'city_municipality' => $residentialCityInput,
                        'province' => $residentialProvinceInput,
                        'zip_code' => $residentialZipCodeInput,
                    ],
                    'permanent_address' => [
                        'house_no' => $permanentHouseNoInput,
                        'street' => $permanentStreetInput,
                        'subdivision' => $permanentSubdivisionInput,
                        'barangay' => $permanentBarangayInput,
                        'city_municipality' => $permanentCityInput,
                        'province' => $permanentProvinceInput,
                        'zip_code' => $permanentZipCodeInput,
                    ],
                    'government_ids' => [
                        'umid' => $umidNoInput,
                        'pagibig' => $pagibigNoInput,
                        'philhealth' => $philhealthNoInput,
                        'psn' => $psnNoInput,
                        'tin' => $tinNoInput,
                    ],
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Employee profile updated successfully.');
    }

    $userId = (string)($personRow['user_id'] ?? '');
    if ($userId !== '') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $userId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'account_status' => 'archived',
                'lockout_until' => gmdate('c'),
            ]
        );
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?person_id=eq.' . $personId . '&is_current=eq.true',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_current' => false,
            'employment_status' => 'resigned',
            'separation_date' => gmdate('Y-m-d'),
            'separation_reason' => cleanText($_POST['profile_notes'] ?? null),
            'updated_at' => gmdate('c'),
        ]
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'archive_profile',
            'old_data' => [
                'user_id' => $personRow['user_id'] ?? null,
                'first_name' => $personRow['first_name'] ?? null,
                'surname' => $personRow['surname'] ?? null,
            ],
            'new_data' => [
                'account_status' => 'archived',
                'employment_status' => 'resigned',
                'reason' => cleanText($_POST['profile_notes'] ?? null),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee profile archived successfully.');
}

if ($action === 'review_profile_recommendation') {
    $recommendationLogId = (string)(cleanText($_POST['recommendation_log_id'] ?? null) ?? '');
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = trim((string)(cleanText($_POST['remarks'] ?? null) ?? ''));

    if (!isValidUuid($recommendationLogId) || !isValidUuid($personId)) {
        redirectWithState('error', 'Invalid recommendation reference selected for review.');
    }

    if (!in_array($decision, ['approve', 'reject'], true)) {
        redirectWithState('error', 'Select a valid recommendation decision.');
    }

    if ($decision === 'reject' && $remarks === '') {
        redirectWithState('error', 'Rejection remarks are required.');
    }

    $recommendationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,actor_user_id,entity_id,new_data,created_at'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&action_name=eq.recommend_employee_profile_update'
        . '&id=eq.' . $recommendationLogId
        . '&entity_id=eq.' . $personId
        . '&limit=1',
        $headers
    );

    $recommendationRow = $recommendationResponse['data'][0] ?? null;
    if (!is_array($recommendationRow)) {
        redirectWithState('error', 'Staff recommendation record was not found.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,personal_email,mobile_no,agency_employee_no&id=eq.' . $personId . '&limit=1',
        $headers
    );

    $personRow = $personResponse['data'][0] ?? null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile no longer exists.');
    }

    $newData = is_array($recommendationRow['new_data'] ?? null) ? (array)$recommendationRow['new_data'] : [];
    $recommendedProfile = is_array($newData['recommended_profile'] ?? null) ? (array)$newData['recommended_profile'] : [];
    $recommendedAddresses = is_array($newData['recommended_addresses'] ?? null) ? (array)$newData['recommended_addresses'] : [];
    $recommendedGovernmentIds = is_array($newData['recommended_government_ids'] ?? null) ? (array)$newData['recommended_government_ids'] : [];

    if ($decision === 'approve') {
        $profilePayload = [];
        $allowedProfileKeys = [
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
            'personal_email',
            'mobile_no',
            'agency_employee_no',
        ];

        foreach ($allowedProfileKeys as $allowedKey) {
            if (!array_key_exists($allowedKey, $recommendedProfile)) {
                continue;
            }

            $value = $recommendedProfile[$allowedKey];
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($value === '') {
                $value = null;
            }
            $profilePayload[$allowedKey] = $value;
        }

        if (!empty($profilePayload)) {
            $profilePayload['updated_at'] = gmdate('c');
            $profilePatchResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
                array_merge($headers, ['Prefer: return=minimal']),
                $profilePayload
            );

            if (!isSuccessful($profilePatchResponse)) {
                redirectWithState('error', 'Failed to apply approved profile changes.');
            }
        }

        $upsertAddress = static function (string $addressType, array $recommendedAddressRow) use ($supabaseUrl, $headers, $personId): void {
            $normalizedAddress = [
                'house_no' => trim((string)($recommendedAddressRow['house_no'] ?? '')),
                'street' => trim((string)($recommendedAddressRow['street'] ?? '')),
                'subdivision' => trim((string)($recommendedAddressRow['subdivision'] ?? '')),
                'barangay' => trim((string)($recommendedAddressRow['barangay'] ?? '')),
                'city_municipality' => trim((string)($recommendedAddressRow['city_municipality'] ?? '')),
                'province' => trim((string)($recommendedAddressRow['province'] ?? '')),
                'zip_code' => trim((string)($recommendedAddressRow['zip_code'] ?? '')),
            ];

            $hasAddressValue = false;
            foreach ($normalizedAddress as $value) {
                if ($value !== '') {
                    $hasAddressValue = true;
                    break;
                }
            }
            if (!$hasAddressValue) {
                return;
            }

            $existingAddressResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/person_addresses?select=id&person_id=eq.' . $personId . '&address_type=eq.' . rawurlencode($addressType) . '&limit=1',
                $headers
            );
            $existingAddressId = (string)($existingAddressResponse['data'][0]['id'] ?? '');

            $payload = [
                'person_id' => $personId,
                'address_type' => $addressType,
                'house_no' => $normalizedAddress['house_no'] !== '' ? $normalizedAddress['house_no'] : null,
                'street' => $normalizedAddress['street'] !== '' ? $normalizedAddress['street'] : null,
                'subdivision' => $normalizedAddress['subdivision'] !== '' ? $normalizedAddress['subdivision'] : null,
                'barangay' => $normalizedAddress['barangay'] !== '' ? $normalizedAddress['barangay'] : null,
                'city_municipality' => $normalizedAddress['city_municipality'] !== '' ? $normalizedAddress['city_municipality'] : null,
                'province' => $normalizedAddress['province'] !== '' ? $normalizedAddress['province'] : null,
                'zip_code' => $normalizedAddress['zip_code'] !== '' ? $normalizedAddress['zip_code'] : null,
                'country' => 'Philippines',
                'is_primary' => true,
            ];

            if ($existingAddressId !== '') {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/person_addresses?id=eq.' . $existingAddressId,
                    array_merge($headers, ['Prefer: return=minimal']),
                    array_merge($payload, ['updated_at' => gmdate('c')])
                );
                return;
            }

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/person_addresses',
                array_merge($headers, ['Prefer: return=minimal']),
                [$payload]
            );
        };

        if (is_array($recommendedAddresses['residential'] ?? null)) {
            $upsertAddress('residential', (array)$recommendedAddresses['residential']);
        }
        if (is_array($recommendedAddresses['permanent'] ?? null)) {
            $upsertAddress('permanent', (array)$recommendedAddresses['permanent']);
        }

        $existingGovernmentIdsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type&person_id=eq.' . $personId . '&limit=20',
            $headers
        );
        $existingGovernmentByType = [];
        if (isSuccessful($existingGovernmentIdsResponse)) {
            foreach ((array)($existingGovernmentIdsResponse['data'] ?? []) as $governmentIdRow) {
                $idType = strtolower((string)($governmentIdRow['id_type'] ?? ''));
                $id = (string)($governmentIdRow['id'] ?? '');
                if ($idType !== '' && $id !== '') {
                    $existingGovernmentByType[$idType] = $id;
                }
            }
        }

        foreach (['umid', 'pagibig', 'philhealth', 'psn', 'tin'] as $idType) {
            if (!array_key_exists($idType, $recommendedGovernmentIds)) {
                continue;
            }
            $idValue = trim((string)$recommendedGovernmentIds[$idType]);
            if ($idValue === '') {
                continue;
            }

            $payload = [
                'person_id' => $personId,
                'id_type' => $idType,
                'id_value_encrypted' => $idValue,
                'last4' => strlen($idValue) >= 4 ? substr($idValue, -4) : $idValue,
            ];

            $existingGovernmentId = $existingGovernmentByType[$idType] ?? '';
            if ($existingGovernmentId !== '') {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . $existingGovernmentId,
                    array_merge($headers, ['Prefer: return=minimal']),
                    array_merge($payload, ['updated_at' => gmdate('c')])
                );
            } else {
                apiRequest(
                    'POST',
                    $supabaseUrl . '/rest/v1/person_government_ids',
                    array_merge($headers, ['Prefer: return=minimal']),
                    [$payload]
                );
            }
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => $decision === 'approve'
                ? 'approve_employee_profile_recommendation'
                : 'reject_employee_profile_recommendation',
            'old_data' => [
                'recommendation_log_id' => $recommendationLogId,
                'submitted_at' => $recommendationRow['created_at'] ?? null,
            ],
            'new_data' => [
                'recommendation_log_id' => $recommendationLogId,
                'decision' => $decision,
                'remarks' => $remarks !== '' ? $remarks : null,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $employeeName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));
    $decisionVerb = $decision === 'approve' ? 'approved' : 'rejected';
    try {
        $decisionTimestampPst = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') . ' PST';
    } catch (Throwable $exception) {
        $decisionTimestampPst = gmdate('M d, Y h:i A') . ' UTC';
    }
    if ($adminUserId !== '' && isValidUuid($adminUserId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $adminUserId,
                'category' => 'employee_profile',
                'title' => 'Recommendation ' . ucfirst($decisionVerb),
                'body' => 'You ' . $decisionVerb . ' a staff recommendation for ' . ($employeeName !== '' ? $employeeName : 'an employee profile') . '.',
                'link_url' => '/hris-system/pages/admin/personal-information.php',
            ]]
        );
    }

    $staffUserId = (string)($recommendationRow['actor_user_id'] ?? '');
    if ($staffUserId !== '' && isValidUuid($staffUserId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $staffUserId,
                'category' => 'employee_profile',
                'title' => 'Recommendation ' . ucfirst($decisionVerb),
                'body' => 'Your employee profile recommendation for ' . ($employeeName !== '' ? $employeeName : 'an employee') . ' was ' . $decisionVerb . ' by Admin on ' . $decisionTimestampPst . '.' . ($remarks !== '' ? (' Remarks: ' . $remarks) : ''),
                'link_url' => '/hris-system/pages/staff/personal-information.php',
            ]]
        );
    }

    $employeeUserId = (string)($personRow['user_id'] ?? '');
    if ($employeeUserId !== '' && isValidUuid($employeeUserId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'employee_profile',
                'title' => 'Profile Recommendation ' . ucfirst($decisionVerb),
                'body' => 'An admin ' . $decisionVerb . ' the submitted profile recommendation for your record.' . ($remarks !== '' ? (' Remarks: ' . $remarks) : ''),
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    redirectWithState('success', $decision === 'approve'
        ? 'Staff recommendation approved and applied successfully.'
        : 'Staff recommendation rejected successfully.');
}

if ($action === 'review_spouse_request') {
    $requestLogId = (string)(cleanText($_POST['request_log_id'] ?? null) ?? '');
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = trim((string)(cleanText($_POST['remarks'] ?? null) ?? ''));

    if (!isValidUuid($requestLogId) || !isValidUuid($personId)) {
        redirectWithState('error', 'Invalid spouse request reference selected for review.');
    }

    if (!in_array($decision, ['approve', 'reject'], true)) {
        redirectWithState('error', 'Select a valid spouse request decision.');
    }

    if ($decision === 'reject' && $remarks === '') {
        redirectWithState('error', 'Rejection remarks are required for spouse requests.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,actor_user_id,entity_id,new_data,created_at'
        . '&module_name=eq.employee'
        . '&entity_name=eq.person_family_spouses_request'
        . '&action_name=eq.submit_spouse_addition_request'
        . '&id=eq.' . $requestLogId
        . '&entity_id=eq.' . $personId
        . '&limit=1',
        $headers
    );

    $requestRow = $requestResponse['data'][0] ?? null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Spouse request record was not found.');
    }

    $existingDecisionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,new_data'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.person_family_spouses_request'
        . '&action_name=in.(approve_spouse_addition_request,reject_spouse_addition_request)'
        . '&entity_id=eq.' . $personId
        . '&order=created_at.desc&limit=200',
        $headers
    );

    if (isSuccessful($existingDecisionResponse)) {
        foreach ((array)($existingDecisionResponse['data'] ?? []) as $decisionRowRaw) {
            $decisionRow = (array)$decisionRowRaw;
            $decisionData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
            if ((string)($decisionData['request_log_id'] ?? '') === $requestLogId) {
                redirectWithState('error', 'This spouse request has already been reviewed.');
            }
        }
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname&id=eq.' . $personId . '&limit=1',
        $headers
    );

    $personRow = $personResponse['data'][0] ?? null;
    if (!is_array($personRow)) {
        redirectWithState('error', 'Employee profile no longer exists.');
    }

    $requestData = is_array($requestRow['new_data'] ?? null) ? (array)$requestRow['new_data'] : [];

    if ($decision === 'approve') {
        $spouseSurname = trim((string)($requestData['spouse_surname'] ?? ''));
        $spouseFirstName = trim((string)($requestData['spouse_first_name'] ?? ''));
        if ($spouseSurname === '' || $spouseFirstName === '') {
            redirectWithState('error', 'Requested spouse entry is missing required name fields.');
        }

        $sequenceResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_family_spouses?select=sequence_no&person_id=eq.' . $personId . '&order=sequence_no.desc&limit=1',
            $headers
        );

        $nextSequenceNo = 1;
        if (isSuccessful($sequenceResponse) && !empty((array)($sequenceResponse['data'] ?? []))) {
            $currentMax = (int)($sequenceResponse['data'][0]['sequence_no'] ?? 0);
            $nextSequenceNo = max(1, $currentMax + 1);
        }

        $spouseInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_family_spouses',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'person_id' => $personId,
                'surname' => $spouseSurname,
                'first_name' => $spouseFirstName,
                'middle_name' => trim((string)($requestData['spouse_middle_name'] ?? '')) ?: null,
                'extension_name' => trim((string)($requestData['spouse_name_extension'] ?? '')) ?: null,
                'occupation' => trim((string)($requestData['spouse_occupation'] ?? '')) ?: null,
                'employer_business_name' => trim((string)($requestData['spouse_employer_business_name'] ?? '')) ?: null,
                'business_address' => trim((string)($requestData['spouse_business_address'] ?? '')) ?: null,
                'telephone_no' => trim((string)($requestData['spouse_telephone_no'] ?? '')) ?: null,
                'sequence_no' => $nextSequenceNo,
            ]]
        );

        if (!isSuccessful($spouseInsertResponse)) {
            redirectWithState('error', 'Failed to apply approved spouse entry request.');
        }
    }

    $decisionStatus = $decision === 'approve' ? 'approved' : 'rejected';
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'person_family_spouses_request',
            'entity_id' => $personId,
            'action_name' => $decision === 'approve'
                ? 'approve_spouse_addition_request'
                : 'reject_spouse_addition_request',
            'old_data' => [
                'request_log_id' => $requestLogId,
                'submitted_at' => $requestRow['created_at'] ?? null,
            ],
            'new_data' => [
                'request_log_id' => $requestLogId,
                'status' => $decisionStatus,
                'remarks' => $remarks !== '' ? $remarks : null,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $employeeUserId = (string)($personRow['user_id'] ?? '');
    $employeeName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));
    if ($employeeUserId !== '' && isValidUuid($employeeUserId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $employeeUserId,
                'category' => 'personal_information',
                'title' => 'Spouse entry request ' . ucfirst($decisionStatus),
                'body' => 'Your spouse entry request was ' . $decisionStatus . ' by Admin.' . ($remarks !== '' ? (' Remarks: ' . $remarks) : ''),
                'link_url' => '/hris-system/pages/employee/personal-information.php',
            ]]
        );
    }

    redirectWithState('success', $decision === 'approve'
        ? ('Spouse entry request approved for ' . ($employeeName !== '' ? $employeeName : 'employee') . '.')
        : ('Spouse entry request rejected for ' . ($employeeName !== '' ? $employeeName : 'employee') . '.'));
}

if ($action === 'assign_department_position') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';

    if (!isValidUuid($personId) || !isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Employee, division, and position are required.');
    }

    $currentEmployment = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,office_id,position_id,employment_status&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );

    $currentRow = $currentEmployment['data'][0] ?? null;
    if (is_array($currentRow) && isValidUuid((string)($currentRow['id'] ?? ''))) {
        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/employment_records?id=eq.' . (string)$currentRow['id'],
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'office_id' => $officeId,
                'position_id' => $positionId,
                'updated_at' => gmdate('c'),
            ]
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update employee assignment.');
        }
    } else {
        $createResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/employment_records',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'person_id' => $personId,
                'office_id' => $officeId,
                'position_id' => $positionId,
                'hire_date' => gmdate('Y-m-d'),
                'employment_status' => 'active',
                'is_current' => true,
            ]]
        );

        if (!isSuccessful($createResponse)) {
            redirectWithState('error', 'Failed to create employee assignment record.');
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => null,
            'action_name' => 'assign_department_position',
            'old_data' => is_array($currentRow)
                ? [
                    'office_id' => $currentRow['office_id'] ?? null,
                    'position_id' => $currentRow['position_id'] ?? null,
                    'employment_status' => $currentRow['employment_status'] ?? null,
                ]
                : null,
            'new_data' => [
                'person_id' => $personId,
                'office_id' => $officeId,
                'position_id' => $positionId,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Division and position assignment updated.');
}

if ($action === 'update_employee_status') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? 'active'));
    $statusSpecification = cleanText($_POST['status_specification'] ?? null);

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee.');
    }

    if (!in_array($newStatus, ['active', 'inactive'], true)) {
        redirectWithState('error', 'Invalid status selected.');
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,employment_status,is_current&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );

    $employmentRow = $employmentResponse['data'][0] ?? null;
    if (!is_array($employmentRow)) {
        redirectWithState('error', 'No active employment record found for selected employee.');
    }

    $mappedStatus = $newStatus === 'active' ? 'active' : 'resigned';
    $patchPayload = [
        'employment_status' => $mappedStatus,
        'updated_at' => gmdate('c'),
    ];

    if ($mappedStatus !== 'active') {
        $patchPayload['separation_date'] = gmdate('Y-m-d');
        $patchPayload['separation_reason'] = $statusSpecification;
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?id=eq.' . (string)$employmentRow['id'],
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update employee status.');
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'personal_information',
        'employment_records',
        (string)($employmentRow['id'] ?? ''),
        'update_employee_status',
        (string)($employmentRow['employment_status'] ?? ''),
        $mappedStatus,
        $statusSpecification
    );

    redirectWithState('success', 'Employee status updated successfully.');
}

if ($action === 'resolve_duplicate_profile') {
    $sourcePersonId = (string)(cleanText($_POST['source_person_id'] ?? null) ?? '');
    $targetPersonId = (string)(cleanText($_POST['target_person_id'] ?? null) ?? '');
    $sourceEmployeeName = trim((string)(cleanText($_POST['source_employee_name'] ?? null) ?? ''));
    $resolutionMode = strtolower((string)(cleanText($_POST['resolution_mode'] ?? null) ?? 'merge'));
    $resolutionNotes = trim((string)(cleanText($_POST['resolution_notes'] ?? null) ?? ''));

    if (!isValidUuid($sourcePersonId)) {
        redirectWithState('error', 'Please select a valid duplicate source profile.');
    }

    if (!in_array($resolutionMode, ['merge', 'delete'], true)) {
        redirectWithState('error', 'Invalid duplicate resolution mode.');
    }

    if ($resolutionMode === 'merge') {
        if (!isValidUuid($targetPersonId)) {
            redirectWithState('error', 'Please select a valid target profile to keep for merge.');
        }
        if (strcasecmp($sourcePersonId, $targetPersonId) === 0) {
            redirectWithState('error', 'Source and target profiles must be different.');
        }
    }

    $sourceResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,personal_email,mobile_no,agency_employee_no&id=eq.' . $sourcePersonId . '&limit=1',
        $headers
    );
    $sourceRow = $sourceResponse['data'][0] ?? null;
    if (!is_array($sourceRow)) {
        redirectWithState('error', 'Duplicate source profile was not found.');
    }

    $targetRow = null;
    if ($resolutionMode === 'merge') {
        $targetResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,personal_email,mobile_no,agency_employee_no&id=eq.' . $targetPersonId . '&limit=1',
            $headers
        );
        $targetRow = $targetResponse['data'][0] ?? null;
        if (!is_array($targetRow)) {
            redirectWithState('error', 'Target profile to keep was not found.');
        }

        $targetHasCurrentEmployment = false;
        $targetEmploymentResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/employment_records?select=id&person_id=eq.' . $targetPersonId . '&is_current=eq.true&limit=1',
            $headers
        );
        if (isSuccessful($targetEmploymentResponse) && !empty((array)($targetEmploymentResponse['data'] ?? []))) {
            $targetHasCurrentEmployment = true;
        }

        $sourceCurrentEmploymentResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,is_current&person_id=eq.' . $sourcePersonId . '&is_current=eq.true&limit=1',
            $headers
        );
        $sourceCurrentEmploymentId = (string)($sourceCurrentEmploymentResponse['data'][0]['id'] ?? '');

        $tablesToReassign = [
            ['table' => 'person_family_spouses', 'column' => 'person_id'],
            ['table' => 'person_parents', 'column' => 'person_id'],
            ['table' => 'person_family_children', 'column' => 'person_id'],
            ['table' => 'person_educational_backgrounds', 'column' => 'person_id'],
            ['table' => 'person_civil_service_eligibilities', 'column' => 'person_id'],
            ['table' => 'person_work_experiences', 'column' => 'person_id'],
            ['table' => 'employment_records', 'column' => 'person_id'],
        ];

        foreach ($tablesToReassign as $tableMeta) {
            $tableName = (string)$tableMeta['table'];
            $columnName = (string)$tableMeta['column'];
            $patchResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/' . $tableName . '?' . $columnName . '=eq.' . $sourcePersonId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    $columnName => $targetPersonId,
                    'updated_at' => gmdate('c'),
                ]
            );

            if (!isSuccessful($patchResponse)) {
                redirectWithState('error', 'Failed to move related records from duplicate source profile.');
            }
        }

        if ($targetHasCurrentEmployment && $sourceCurrentEmploymentId !== '') {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/employment_records?id=eq.' . $sourceCurrentEmploymentId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'is_current' => false,
                    'employment_status' => 'resigned',
                    'separation_reason' => 'Duplicate profile merged to another employee record.',
                    'separation_date' => gmdate('Y-m-d'),
                    'updated_at' => gmdate('c'),
                ]
            );
        }

        $sourceAddressesResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_addresses?select=id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code&person_id=eq.' . $sourcePersonId . '&limit=20',
            $headers
        );
        $targetAddressesResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_addresses?select=id,address_type&person_id=eq.' . $targetPersonId . '&limit=20',
            $headers
        );

        $targetAddressByType = [];
        if (isSuccessful($targetAddressesResponse)) {
            foreach ((array)$targetAddressesResponse['data'] as $targetAddressRow) {
                $type = strtolower((string)($targetAddressRow['address_type'] ?? ''));
                if ($type !== '') {
                    $targetAddressByType[$type] = (string)($targetAddressRow['id'] ?? '');
                }
            }
        }

        if (isSuccessful($sourceAddressesResponse)) {
            foreach ((array)$sourceAddressesResponse['data'] as $sourceAddressRow) {
                $sourceAddressId = (string)($sourceAddressRow['id'] ?? '');
                $addressType = strtolower((string)($sourceAddressRow['address_type'] ?? ''));
                if ($sourceAddressId === '' || $addressType === '') {
                    continue;
                }

                if (!isset($targetAddressByType[$addressType]) || $targetAddressByType[$addressType] === '') {
                    apiRequest(
                        'PATCH',
                        $supabaseUrl . '/rest/v1/person_addresses?id=eq.' . $sourceAddressId,
                        array_merge($headers, ['Prefer: return=minimal']),
                        [
                            'person_id' => $targetPersonId,
                            'updated_at' => gmdate('c'),
                        ]
                    );
                    $targetAddressByType[$addressType] = $sourceAddressId;
                } else {
                    apiRequest(
                        'DELETE',
                        $supabaseUrl . '/rest/v1/person_addresses?id=eq.' . $sourceAddressId,
                        array_merge($headers, ['Prefer: return=minimal'])
                    );
                }
            }
        }

        $sourceGovIdsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type,id_value_encrypted,last4&person_id=eq.' . $sourcePersonId . '&limit=20',
            $headers
        );
        $targetGovIdsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type&person_id=eq.' . $targetPersonId . '&limit=20',
            $headers
        );

        $targetGovIdByType = [];
        if (isSuccessful($targetGovIdsResponse)) {
            foreach ((array)$targetGovIdsResponse['data'] as $targetGovRow) {
                $idType = strtolower((string)($targetGovRow['id_type'] ?? ''));
                if ($idType !== '') {
                    $targetGovIdByType[$idType] = (string)($targetGovRow['id'] ?? '');
                }
            }
        }

        if (isSuccessful($sourceGovIdsResponse)) {
            foreach ((array)$sourceGovIdsResponse['data'] as $sourceGovRow) {
                $sourceGovId = (string)($sourceGovRow['id'] ?? '');
                $sourceGovType = strtolower((string)($sourceGovRow['id_type'] ?? ''));
                if ($sourceGovId === '' || $sourceGovType === '') {
                    continue;
                }

                if (!isset($targetGovIdByType[$sourceGovType]) || $targetGovIdByType[$sourceGovType] === '') {
                    apiRequest(
                        'PATCH',
                        $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . $sourceGovId,
                        array_merge($headers, ['Prefer: return=minimal']),
                        [
                            'person_id' => $targetPersonId,
                            'updated_at' => gmdate('c'),
                        ]
                    );
                    $targetGovIdByType[$sourceGovType] = $sourceGovId;
                } else {
                    apiRequest(
                        'DELETE',
                        $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . $sourceGovId,
                        array_merge($headers, ['Prefer: return=minimal'])
                    );
                }
            }
        }

        $sourceUserId = (string)($sourceRow['user_id'] ?? '');
        $targetUserId = (string)($targetRow['user_id'] ?? '');
        if ($sourceUserId !== '' && $targetUserId === '') {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/people?id=eq.' . $targetPersonId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'user_id' => $sourceUserId,
                    'updated_at' => gmdate('c'),
                ]
            );

            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . $sourceUserId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'updated_at' => gmdate('c'),
                ]
            );
        }
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/employment_records?person_id=eq.' . $sourcePersonId . '&is_current=eq.true',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_current' => false,
            'employment_status' => 'resigned',
            'separation_reason' => $resolutionMode === 'merge'
                ? 'Duplicate profile merged into another employee record.'
                : 'Duplicate profile removed by admin duplicate resolution.',
            'separation_date' => gmdate('Y-m-d'),
            'updated_at' => gmdate('c'),
        ]
    );

    $sourceUserIdForArchive = (string)($sourceRow['user_id'] ?? '');
    if ($sourceUserIdForArchive !== '') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $sourceUserIdForArchive,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'account_status' => 'archived',
                'lockout_until' => gmdate('c'),
                'updated_at' => gmdate('c'),
            ]
        );
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . $sourcePersonId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'personal_email' => null,
            'mobile_no' => null,
            'agency_employee_no' => null,
            'updated_at' => gmdate('c'),
        ]
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $sourcePersonId,
            'action_name' => $resolutionMode === 'merge' ? 'merge_duplicate_profile' : 'delete_duplicate_profile',
            'old_data' => [
                'source_person_id' => $sourcePersonId,
                'source_employee_name' => $sourceEmployeeName,
                'source_personal_email' => $sourceRow['personal_email'] ?? null,
                'source_mobile_no' => $sourceRow['mobile_no'] ?? null,
                'source_agency_employee_no' => $sourceRow['agency_employee_no'] ?? null,
            ],
            'new_data' => [
                'resolution_mode' => $resolutionMode,
                'target_person_id' => $resolutionMode === 'merge' ? $targetPersonId : null,
                'notes' => $resolutionNotes !== '' ? $resolutionNotes : null,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', $resolutionMode === 'merge'
        ? 'Duplicate profile merged and archived successfully.'
        : 'Duplicate profile archived successfully.');
}

if ($action === 'save_family_background') {
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee for Section II update.');
    }

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
        $supabaseUrl . '/rest/v1/person_family_spouses?select=id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no&person_id=eq.' . $personId . '&order=sequence_no.asc&limit=1',
        $headers
    );
    $existingSpouse = $existingSpouseResponse['data'][0] ?? null;
    $spousePayload = [
        'person_id' => $personId,
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
                $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . (string)$existingSpouse['id'],
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
            $supabaseUrl . '/rest/v1/person_family_spouses?id=eq.' . (string)$existingSpouse['id'],
            array_merge($headers, ['Prefer: return=minimal'])
        );

        if (!isSuccessful($spouseDeleteResponse)) {
            redirectWithState('error', 'Failed to clear spouse details.');
        }
    }

    $upsertParent = static function (string $parentType, array $payload) use ($personId, $supabaseUrl, $headers): void {
        $existingParentResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_parents?select=id,surname,first_name,middle_name,extension_name&person_id=eq.' . $personId . '&parent_type=eq.' . $parentType . '&limit=1',
            $headers
        );
        $existingParent = $existingParentResponse['data'][0] ?? null;

        $hasParentData = false;
        foreach ($payload as $value) {
            if ($value !== null && $value !== '') {
                $hasParentData = true;
                break;
            }
        }

        if ($hasParentData) {
            $recordPayload = [
                'person_id' => $personId,
                'parent_type' => $parentType,
                'surname' => $payload['surname'] !== '' ? $payload['surname'] : null,
                'first_name' => $payload['first_name'] !== '' ? $payload['first_name'] : null,
                'middle_name' => $payload['middle_name'] !== '' ? $payload['middle_name'] : null,
                'extension_name' => $payload['extension_name'] !== '' ? $payload['extension_name'] : null,
            ];

            if (is_array($existingParent) && isValidUuid((string)($existingParent['id'] ?? ''))) {
                $parentSaveResponse = apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/person_parents?id=eq.' . (string)$existingParent['id'],
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
                $supabaseUrl . '/rest/v1/person_parents?id=eq.' . (string)$existingParent['id'],
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
        $supabaseUrl . '/rest/v1/person_family_children?person_id=eq.' . $personId,
        array_merge($headers, ['Prefer: return=minimal'])
    );
    if (!isSuccessful($deleteChildrenResponse)) {
        redirectWithState('error', 'Failed to reset children records.');
    }

    if (!empty($validChildren)) {
        $childrenPayload = array_map(static function (array $child) use ($personId): array {
            return [
                'person_id' => $personId,
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

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'family_background',
            'entity_id' => $personId,
            'action_name' => 'save_family_background',
            'old_data' => null,
            'new_data' => [
                'spouse' => [
                    'surname' => $spouseSurname,
                    'first_name' => $spouseFirstName,
                    'middle_name' => $spouseMiddleName,
                    'extension_name' => $spouseExtensionName,
                    'occupation' => $spouseOccupation,
                    'employer_business_name' => $spouseEmployerBusinessName,
                    'business_address' => $spouseBusinessAddress,
                    'telephone_no' => $spouseTelephoneNo,
                ],
                'father' => [
                    'surname' => $fatherSurname,
                    'first_name' => $fatherFirstName,
                    'middle_name' => $fatherMiddleName,
                    'extension_name' => $fatherExtensionName,
                ],
                'mother' => [
                    'surname' => $motherSurname,
                    'first_name' => $motherFirstName,
                    'middle_name' => $motherMiddleName,
                    'extension_name' => $motherExtensionName,
                ],
                'children_count' => count($validChildren),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Section II family background updated successfully.');
}

if ($action === 'save_educational_background') {
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee for Section III update.');
    }

    $levels = (array)($_POST['education_level'] ?? []);
    $schoolNames = (array)($_POST['school_name'] ?? []);
    $degreeCourses = (array)($_POST['degree_course'] ?? []);
    $attendanceFromYears = (array)($_POST['attendance_from_year'] ?? []);
    $attendanceToYears = (array)($_POST['attendance_to_year'] ?? []);
    $highestLevelsUnitsEarned = (array)($_POST['highest_level_units_earned'] ?? []);
    $yearGraduatedValues = (array)($_POST['year_graduated'] ?? []);
    $scholarshipHonors = (array)($_POST['scholarship_honors_received'] ?? []);

    $allowedLevels = ['elementary', 'secondary', 'vocational_trade_course', 'college', 'graduate_studies'];
    $levelOrder = array_flip($allowedLevels);

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

    $validRecords = [];

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

    for ($index = 0; $index < $recordCount; $index++) {
        $level = strtolower(trim((string)(cleanText($levels[$index] ?? null) ?? '')));
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
            'person_id' => $personId,
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
        $supabaseUrl . '/rest/v1/person_educational_backgrounds?person_id=eq.' . $personId,
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

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'person_educational_backgrounds',
            'entity_id' => $personId,
            'action_name' => 'save_educational_background',
            'old_data' => null,
            'new_data' => [
                'records_count' => count($validRecords),
                'levels' => array_keys($validRecords),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Section III educational background updated successfully.');
}

if ($action === 'save_civil_service_eligibility') {
    $eligibilityAction = strtolower((string)(cleanText($_POST['eligibility_action'] ?? null) ?? 'add'));
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    $eligibilityId = (string)(cleanText($_POST['eligibility_id'] ?? null) ?? '');

    if (!in_array($eligibilityAction, ['add', 'edit', 'delete'], true)) {
        redirectWithState('error', 'Invalid eligibility action selected.');
    }

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee for eligibility update.');
    }

    if (($eligibilityAction === 'edit' || $eligibilityAction === 'delete') && !isValidUuid($eligibilityId)) {
        redirectWithState('error', 'Please select a valid eligibility record.');
    }

    $existingEligibility = null;
    if ($eligibilityAction === 'edit' || $eligibilityAction === 'delete') {
        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_civil_service_eligibilities?select=id,person_id,eligibility_name,rating,exam_date,exam_place,license_no,license_validity,sequence_no&id=eq.' . $eligibilityId . '&person_id=eq.' . $personId . '&limit=1',
            $headers
        );

        $existingEligibility = $existingResponse['data'][0] ?? null;
        if (!is_array($existingEligibility)) {
            redirectWithState('error', 'Eligibility record not found for selected employee.');
        }
    }

    if ($eligibilityAction === 'delete') {
        $deleteResponse = apiRequest(
            'DELETE',
            $supabaseUrl . '/rest/v1/person_civil_service_eligibilities?id=eq.' . $eligibilityId . '&person_id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal'])
        );

        if (!isSuccessful($deleteResponse)) {
            redirectWithState('error', 'Failed to delete eligibility record.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'person_civil_service_eligibilities',
                'entity_id' => $eligibilityId,
                'action_name' => 'delete_civil_service_eligibility',
                'old_data' => $existingEligibility,
                'new_data' => null,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Civil service eligibility removed successfully.');
    }

    $eligibilityName = trim((string)(cleanText($_POST['eligibility_name'] ?? null) ?? ''));
    $rating = trim((string)(cleanText($_POST['rating'] ?? null) ?? ''));
    $examDate = trim((string)(cleanText($_POST['exam_date'] ?? null) ?? ''));
    $examPlace = trim((string)(cleanText($_POST['exam_place'] ?? null) ?? ''));
    $licenseNo = trim((string)(cleanText($_POST['license_no'] ?? null) ?? ''));
    $licenseValidity = trim((string)(cleanText($_POST['license_validity'] ?? null) ?? ''));
    $sequenceNo = (int)($_POST['sequence_no'] ?? 1);

    if ($eligibilityName === '') {
        redirectWithState('error', 'Eligibility name is required.');
    }

    if ($examDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate)) {
        redirectWithState('error', 'Invalid examination date format.');
    }

    if ($licenseValidity !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $licenseValidity)) {
        redirectWithState('error', 'Invalid license validity date format.');
    }

    if ($examDate !== '' && $licenseValidity !== '' && $licenseValidity < $examDate) {
        redirectWithState('error', 'License validity date cannot be earlier than exam date.');
    }

    if ($sequenceNo < 1) {
        $sequenceNo = 1;
    }

    $payload = [
        'person_id' => $personId,
        'eligibility_name' => $eligibilityName,
        'rating' => $rating !== '' ? $rating : null,
        'exam_date' => $examDate !== '' ? $examDate : null,
        'exam_place' => $examPlace !== '' ? $examPlace : null,
        'license_no' => $licenseNo !== '' ? $licenseNo : null,
        'license_validity' => $licenseValidity !== '' ? $licenseValidity : null,
        'sequence_no' => $sequenceNo,
    ];

    if ($eligibilityAction === 'add') {
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_civil_service_eligibilities',
            array_merge($headers, ['Prefer: return=representation']),
            [$payload]
        );

        if (!isSuccessful($insertResponse)) {
            redirectWithState('error', 'Failed to create civil service eligibility record.');
        }

        $newEligibilityId = (string)($insertResponse['data'][0]['id'] ?? '');

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'person_civil_service_eligibilities',
                'entity_id' => $newEligibilityId !== '' ? $newEligibilityId : null,
                'action_name' => 'add_civil_service_eligibility',
                'old_data' => null,
                'new_data' => $payload,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Civil service eligibility added successfully.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/person_civil_service_eligibilities?id=eq.' . $eligibilityId . '&person_id=eq.' . $personId,
        array_merge($headers, ['Prefer: return=minimal']),
        array_merge($payload, ['updated_at' => gmdate('c')])
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update civil service eligibility record.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'person_civil_service_eligibilities',
            'entity_id' => $eligibilityId,
            'action_name' => 'edit_civil_service_eligibility',
            'old_data' => $existingEligibility,
            'new_data' => $payload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Civil service eligibility updated successfully.');
}

if ($action === 'save_work_experience') {
    $workAction = strtolower((string)(cleanText($_POST['work_experience_action'] ?? null) ?? 'add'));
    $personId = (string)(cleanText($_POST['person_id'] ?? null) ?? '');
    $workExperienceId = (string)(cleanText($_POST['work_experience_id'] ?? null) ?? '');

    if (!in_array($workAction, ['add', 'edit', 'delete'], true)) {
        redirectWithState('error', 'Invalid work experience action selected.');
    }

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee for work experience update.');
    }

    if (($workAction === 'edit' || $workAction === 'delete') && !isValidUuid($workExperienceId)) {
        redirectWithState('error', 'Please select a valid work experience record.');
    }

    $existingWorkExperience = null;
    if ($workAction === 'edit' || $workAction === 'delete') {
        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/person_work_experiences?select=id,person_id,inclusive_date_from,inclusive_date_to,position_title,office_company,monthly_salary,salary_grade_step,appointment_status,is_government_service,separation_reason,achievements,sequence_no&id=eq.' . $workExperienceId . '&person_id=eq.' . $personId . '&limit=1',
            $headers
        );

        $existingWorkExperience = $existingResponse['data'][0] ?? null;
        if (!is_array($existingWorkExperience)) {
            redirectWithState('error', 'Work experience record not found for selected employee.');
        }
    }

    if ($workAction === 'delete') {
        $deleteResponse = apiRequest(
            'DELETE',
            $supabaseUrl . '/rest/v1/person_work_experiences?id=eq.' . $workExperienceId . '&person_id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal'])
        );

        if (!isSuccessful($deleteResponse)) {
            redirectWithState('error', 'Failed to delete work experience record.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'person_work_experiences',
                'entity_id' => $workExperienceId,
                'action_name' => 'delete_work_experience',
                'old_data' => $existingWorkExperience,
                'new_data' => null,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Work experience removed successfully.');
    }

    $inclusiveDateFrom = trim((string)(cleanText($_POST['inclusive_date_from'] ?? null) ?? ''));
    $inclusiveDateTo = trim((string)(cleanText($_POST['inclusive_date_to'] ?? null) ?? ''));
    $positionTitle = trim((string)(cleanText($_POST['position_title'] ?? null) ?? ''));
    $officeCompany = trim((string)(cleanText($_POST['office_company'] ?? null) ?? ''));
    $monthlySalaryRaw = trim((string)(cleanText($_POST['monthly_salary'] ?? null) ?? ''));
    $salaryGradeStep = trim((string)(cleanText($_POST['salary_grade_step'] ?? null) ?? ''));
    $appointmentStatus = trim((string)(cleanText($_POST['appointment_status'] ?? null) ?? ''));
    $isGovernmentServiceRaw = strtolower(trim((string)(cleanText($_POST['is_government_service'] ?? null) ?? '')));
    $separationReason = trim((string)(cleanText($_POST['separation_reason'] ?? null) ?? ''));
    $achievements = trim((string)(cleanText($_POST['achievements'] ?? null) ?? ''));
    $sequenceNo = (int)($_POST['sequence_no'] ?? 1);

    if ($inclusiveDateFrom === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $inclusiveDateFrom)) {
        redirectWithState('error', 'Inclusive date (from) is required and must be valid.');
    }

    if ($inclusiveDateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $inclusiveDateTo)) {
        redirectWithState('error', 'Inclusive date (to) must be a valid date.');
    }

    if ($inclusiveDateTo !== '' && $inclusiveDateTo < $inclusiveDateFrom) {
        redirectWithState('error', 'Inclusive date (to) cannot be earlier than inclusive date (from).');
    }

    if ($positionTitle === '' || $officeCompany === '') {
        redirectWithState('error', 'Position title and office/company are required.');
    }

    $monthlySalary = null;
    if ($monthlySalaryRaw !== '') {
        if (!is_numeric($monthlySalaryRaw)) {
            redirectWithState('error', 'Monthly salary must be a valid number.');
        }

        $monthlySalary = (float)$monthlySalaryRaw;
        if ($monthlySalary < 0) {
            redirectWithState('error', 'Monthly salary cannot be negative.');
        }
    }

    if ($sequenceNo < 1) {
        $sequenceNo = 1;
    }

    $isGovernmentService = null;
    if ($isGovernmentServiceRaw === 'true') {
        $isGovernmentService = true;
    } elseif ($isGovernmentServiceRaw === 'false') {
        $isGovernmentService = false;
    }

    $payload = [
        'person_id' => $personId,
        'inclusive_date_from' => $inclusiveDateFrom,
        'inclusive_date_to' => $inclusiveDateTo !== '' ? $inclusiveDateTo : null,
        'position_title' => $positionTitle,
        'office_company' => $officeCompany,
        'monthly_salary' => $monthlySalary,
        'salary_grade_step' => $salaryGradeStep !== '' ? $salaryGradeStep : null,
        'appointment_status' => $appointmentStatus !== '' ? $appointmentStatus : null,
        'is_government_service' => $isGovernmentService,
        'separation_reason' => $separationReason !== '' ? $separationReason : null,
        'achievements' => $achievements !== '' ? $achievements : null,
        'sequence_no' => $sequenceNo,
    ];

    if ($workAction === 'add') {
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_work_experiences',
            array_merge($headers, ['Prefer: return=representation']),
            [$payload]
        );

        if (!isSuccessful($insertResponse)) {
            redirectWithState('error', 'Failed to add work experience record.');
        }

        $newWorkExperienceId = (string)($insertResponse['data'][0]['id'] ?? '');

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'personal_information',
                'entity_name' => 'person_work_experiences',
                'entity_id' => $newWorkExperienceId !== '' ? $newWorkExperienceId : null,
                'action_name' => 'add_work_experience',
                'old_data' => null,
                'new_data' => $payload,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Work experience added successfully.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/person_work_experiences?id=eq.' . $workExperienceId . '&person_id=eq.' . $personId,
        array_merge($headers, ['Prefer: return=minimal']),
        array_merge($payload, ['updated_at' => gmdate('c')])
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update work experience record.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'person_work_experiences',
            'entity_id' => $workExperienceId,
            'action_name' => 'edit_work_experience',
            'old_data' => $existingWorkExperience,
            'new_data' => $payload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Work experience updated successfully.');
}

redirectWithState('error', 'Unknown personal information action.');
