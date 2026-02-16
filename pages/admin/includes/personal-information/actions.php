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
            redirectWithState('error', 'Failed to create employee profile.');
        }

        $newPersonId = (string)($insertResponse['data'][0]['id'] ?? '');
        if ($newPersonId !== '') {
            $syncAddressAndGovernmentIds($newPersonId);
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

if ($action === 'assign_department_position') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';

    if (!isValidUuid($personId) || !isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Employee, department, and position are required.');
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

    redirectWithState('success', 'Department and position assignment updated.');
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

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'personal_information',
            'entity_name' => 'employment_records',
            'entity_id' => (string)($employmentRow['id'] ?? ''),
            'action_name' => 'update_employee_status',
            'old_data' => ['employment_status' => $employmentRow['employment_status'] ?? null],
            'new_data' => [
                'employment_status' => $mappedStatus,
                'status_specification' => $statusSpecification,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Employee status updated successfully.');
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
