<?php

$filterKeyword = strtolower(trim((string)(cleanText($_GET['keyword'] ?? null) ?? '')));
$filterDepartment = trim((string)(cleanText($_GET['department'] ?? null) ?? ''));
$filterStatusRaw = strtolower(trim((string)(cleanText($_GET['status'] ?? null) ?? '')));
$filterStatus = in_array($filterStatusRaw, ['active', 'inactive'], true) ? $filterStatusRaw : '';

$personalInfoDataStage = strtolower(trim((string)($personalInfoDataStage ?? 'full')));
$personalInfoSelectedPersonId = trim((string)($personalInfoSelectedPersonId ?? ''));
$personalInfoProfileRequestedTab = strtolower(trim((string)($personalInfoProfileRequestedTab ?? 'personal')));
$isPersonalInfoSelectedPerson = isValidUuid($personalInfoSelectedPersonId);
$isEmployeeProfileShellStage = $personalInfoDataStage === 'employee-profile-shell';
$isEmployeeProfileTabStage = $personalInfoDataStage === 'employee-profile-tab';
$loadShellQueues = in_array($personalInfoDataStage, ['full', 'shell'], true);
$loadEmployeeRegionData = in_array($personalInfoDataStage, ['full', 'employee-region', 'employee-profile-shell', 'employee-profile-tab'], true);
$loadAuditLogData = in_array($personalInfoDataStage, ['full', 'audit'], true);
$loadAssignmentLookupData = in_array($personalInfoDataStage, ['full', 'employee-region'], true);
$loadAddressGovernmentData = $loadEmployeeRegionData;
$loadWorkExperienceData = in_array($personalInfoDataStage, ['full', 'employee-region', 'employee-profile-shell', 'employee-profile-tab'], true);
$loadFamilyData = in_array($personalInfoDataStage, ['full', 'employee-region'], true)
    || ($isEmployeeProfileTabStage && $personalInfoProfileRequestedTab === 'family');
$loadEducationData = in_array($personalInfoDataStage, ['full', 'employee-region'], true)
    || ($isEmployeeProfileTabStage && $personalInfoProfileRequestedTab === 'education');
$loadEligibilityData = in_array($personalInfoDataStage, ['full', 'employee-region'], true);

$dataLoadError = null;

$adminPersonalInfoLimits = [
    'employment_records' => 1500,
    'related_records' => 3000,
    'actors' => 400,
    'logs' => 500,
    'offices' => 250,
    'positions' => 250,
    'role_assignments' => 3000,
];

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError === null ? $message : ($dataLoadError . ' ' . $message);
};

$formatAdminPersonalInfoTimestamp = static function (?string $value, string $format = 'M d, Y h:i A'): string {
    return function_exists('formatDateTimeForPhilippines')
        ? formatDateTimeForPhilippines($value, $format)
        : (($value !== null && $value !== '' && strtotime($value) !== false) ? date($format, strtotime($value)) : '-');
};

if (!function_exists('sanitizeUuidListForInFilter')) {
    function sanitizeUuidListForInFilter(array $values): string
    {
        $filtered = [];
        foreach ($values as $value) {
            $uuid = trim((string)$value);
            if ($uuid !== '' && isValidUuid($uuid)) {
                $filtered[$uuid] = true;
            }
        }

        return implode(',', array_map('rawurlencode', array_keys($filtered)));
    }
}

if (!function_exists('normalizeZipLookupPart')) {
    function normalizeZipLookupPart(?string $value): string
    {
        $text = strtolower(trim((string)$value));
        if ($text === '') {
            return '';
        }

        $text = str_replace(['.', ',', "'"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }
}

$supportsCivilServiceEligibility = personalInfoTableExists($supabaseUrl, $headers, 'person_civil_service_eligibilities');
$supportsWorkExperience = personalInfoTableExists($supabaseUrl, $headers, 'person_work_experiences');

$schemaSupportNotes = [];
if (!$supportsCivilServiceEligibility) {
    $schemaSupportNotes[] = 'Civil service eligibility records are unavailable because the required Supabase table has not been deployed.';
}
if (!$supportsWorkExperience) {
    $schemaSupportNotes[] = 'Work experience records are unavailable because the required Supabase table has not been deployed.';
}
$schemaSupportNotice = !empty($schemaSupportNotes) ? implode(' ', $schemaSupportNotes) : null;

$employmentPersonFilter = $isPersonalInfoSelectedPerson
    ? '&person_id=eq.' . rawurlencode($personalInfoSelectedPersonId)
    : '';
$employmentLimit = $isPersonalInfoSelectedPerson ? 1 : $adminPersonalInfoLimits['employment_records'];

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,updated_at,person:people!employment_records_person_id_fkey(id,user_id,first_name,middle_name,surname,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no,profile_photo_url),office:offices(office_name),position:job_positions(position_title)'
    . '&is_current=eq.true'
    . $employmentPersonFilter
    . '&order=updated_at.desc&limit=' . $employmentLimit,
    $headers
);
$appendDataError('Employment records', $employmentResponse);
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$personIds = [];
$userIds = [];
$employmentIds = [];
$personNameById = [];
foreach ($employmentRows as $employmentRow) {
    $person = (array)($employmentRow['person'] ?? []);
    $personId = cleanText($employmentRow['person_id'] ?? null) ?? cleanText($person['id'] ?? null) ?? '';
    $userId = cleanText($person['user_id'] ?? null) ?? '';
    $employmentId = cleanText($employmentRow['id'] ?? null) ?? '';

    if (isValidUuid($personId)) {
        $personIds[] = $personId;
        $fullName = trim(implode(' ', array_filter([
            trim((string)(cleanText($person['first_name'] ?? null) ?? '')),
            trim((string)(cleanText($person['middle_name'] ?? null) ?? '')),
            trim((string)(cleanText($person['surname'] ?? null) ?? '')),
            trim((string)(cleanText($person['name_extension'] ?? null) ?? '')),
        ], static fn ($part): bool => $part !== '')));
        $personNameById[$personId] = $fullName !== '' ? $fullName : 'Unknown Employee';
    }

    if (isValidUuid($userId)) {
        $userIds[] = $userId;
    }

    if (isValidUuid($employmentId)) {
        $employmentIds[] = $employmentId;
    }
}

$personIdFilter = sanitizeUuidListForInFilter($personIds);
$userIdFilter = sanitizeUuidListForInFilter($userIds);
$employmentIdFilter = sanitizeUuidListForInFilter($employmentIds);

$roleKeyByUserId = [];
if ($loadEmployeeRegionData && $userIdFilter !== '') {
    $roleAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,is_primary,role:roles(role_key)'
        . '&user_id=in.(' . $userIdFilter . ')'
        . '&expires_at=is.null&limit=' . $adminPersonalInfoLimits['role_assignments'],
        $headers
    );
    $appendDataError('Role assignments', $roleAssignmentsResponse);

    if (isSuccessful($roleAssignmentsResponse)) {
        $fallbackRoleKeyByUserId = [];
        foreach ((array)($roleAssignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            $roleKey = strtolower(trim((string)(cleanText($assignmentRow['role']['role_key'] ?? null) ?? '')));
            if (!isValidUuid($userId) || $roleKey === '') {
                continue;
            }

            if (!isset($fallbackRoleKeyByUserId[$userId])) {
                $fallbackRoleKeyByUserId[$userId] = $roleKey;
            }

            if ((bool)($assignmentRow['is_primary'] ?? false)) {
                $roleKeyByUserId[$userId] = $roleKey;
            }
        }

        foreach ($fallbackRoleKeyByUserId as $userId => $roleKey) {
            if (!isset($roleKeyByUserId[$userId])) {
                $roleKeyByUserId[$userId] = $roleKey;
            }
        }
    }
}

$addressRows = [];
$governmentIdRows = [];
$spouseRows = [];
$parentRows = [];
$childrenRows = [];
$educationRows = [];
$eligibilityRows = [];
$workExperienceRows = [];
$recommendationHistoryRows = [];
$spouseRequestRows = [];
$personalInfoAuditRows = [];

if ($loadEmployeeRegionData && $personIdFilter !== '') {
    if ($loadAddressGovernmentData) {
    $addressesResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_addresses?select=id,person_id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,is_primary'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&address_type=in.(residential,permanent)&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Person addresses', $addressesResponse);
    $addressRows = isSuccessful($addressesResponse) ? (array)($addressesResponse['data'] ?? []) : [];

    $governmentIdsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_government_ids?select=id,person_id,id_type,id_value_encrypted'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&id_type=in.(umid,pagibig,philhealth,psn,tin)&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Government IDs', $governmentIdsResponse);
    $governmentIdRows = isSuccessful($governmentIdsResponse) ? (array)($governmentIdsResponse['data'] ?? []) : [];
    }

    if ($loadFamilyData) {
    $spouseResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_family_spouses?select=id,person_id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Spouse data', $spouseResponse);
    $spouseRows = isSuccessful($spouseResponse) ? (array)($spouseResponse['data'] ?? []) : [];

    $parentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_parents?select=id,person_id,parent_type,surname,first_name,middle_name,extension_name'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&parent_type=in.(father,mother)&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Parent data', $parentsResponse);
    $parentRows = isSuccessful($parentsResponse) ? (array)($parentsResponse['data'] ?? []) : [];

    $childrenResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_family_children?select=id,person_id,full_name,birth_date,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Children data', $childrenResponse);
    $childrenRows = isSuccessful($childrenResponse) ? (array)($childrenResponse['data'] ?? []) : [];
    }

    if ($loadEducationData) {
    $educationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_educations?select=id,person_id,education_level,school_name,course_degree,period_from,period_to,highest_level_units,year_graduated,honors_received,sequence_no'
        . '&person_id=in.(' . $personIdFilter . ')'
        . '&order=sequence_no.asc&limit=' . $adminPersonalInfoLimits['related_records'],
        $headers
    );
    $appendDataError('Educational background data', $educationResponse);
    $educationRows = isSuccessful($educationResponse) ? (array)($educationResponse['data'] ?? []) : [];
    }

    if ($supportsCivilServiceEligibility && $loadEligibilityData) {
        $eligibilityResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/person_civil_service_eligibilities?select=id,person_id,eligibility_name,rating,exam_date,exam_place,license_no,license_validity,sequence_no'
            . '&person_id=in.(' . $personIdFilter . ')'
            . '&order=sequence_no.asc&limit=' . $adminPersonalInfoLimits['related_records'],
            $headers
        );
        $appendDataError('Civil service eligibility data', $eligibilityResponse);
        $eligibilityRows = isSuccessful($eligibilityResponse) ? (array)($eligibilityResponse['data'] ?? []) : [];
    }

    if ($supportsWorkExperience && $loadWorkExperienceData) {
        $workExperienceResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/person_work_experiences?select=id,person_id,inclusive_date_from,inclusive_date_to,position_title,office_company,monthly_salary,salary_grade_step,appointment_status,is_government_service,separation_reason,achievements,sequence_no'
            . '&person_id=in.(' . $personIdFilter . ')'
            . '&order=sequence_no.asc&limit=' . $adminPersonalInfoLimits['related_records'],
            $headers
        );
        $appendDataError('Work experience data', $workExperienceResponse);
        $workExperienceRows = isSuccessful($workExperienceResponse) ? (array)($workExperienceResponse['data'] ?? []) : [];
    }
}

$officeRows = [];
$positionRows = [];
if ($loadAssignmentLookupData) {
    $officesResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/offices?select=id,office_name,is_active&order=office_name.asc&limit=' . $adminPersonalInfoLimits['offices'],
        $headers
    );
    $appendDataError('Offices', $officesResponse);
    $officeRows = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];

    $positionsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=' . $adminPersonalInfoLimits['positions'],
        $headers
    );
    $appendDataError('Job positions', $positionsResponse);
    $positionRows = isSuccessful($positionsResponse) ? (array)($positionsResponse['data'] ?? []) : [];
}

$addressesByPerson = [];
foreach ($addressRows as $addressRow) {
    $personId = cleanText($addressRow['person_id'] ?? null) ?? '';
    $addressType = strtolower(trim((string)(cleanText($addressRow['address_type'] ?? null) ?? '')));
    if (!isValidUuid($personId) || !in_array($addressType, ['residential', 'permanent'], true)) {
        continue;
    }

    if (!isset($addressesByPerson[$personId])) {
        $addressesByPerson[$personId] = [];
    }

    $isPrimary = (bool)($addressRow['is_primary'] ?? false);
    if (!isset($addressesByPerson[$personId][$addressType]) || $isPrimary) {
        $addressesByPerson[$personId][$addressType] = [
            'house_no' => cleanText($addressRow['house_no'] ?? null) ?? '',
            'street' => cleanText($addressRow['street'] ?? null) ?? '',
            'subdivision' => cleanText($addressRow['subdivision'] ?? null) ?? '',
            'barangay' => cleanText($addressRow['barangay'] ?? null) ?? '',
            'city_municipality' => cleanText($addressRow['city_municipality'] ?? null) ?? '',
            'province' => cleanText($addressRow['province'] ?? null) ?? '',
            'zip_code' => cleanText($addressRow['zip_code'] ?? null) ?? '',
        ];
    }
}

$governmentIdsByPerson = [];
foreach ($governmentIdRows as $governmentIdRow) {
    $personId = cleanText($governmentIdRow['person_id'] ?? null) ?? '';
    $idType = strtolower(trim((string)(cleanText($governmentIdRow['id_type'] ?? null) ?? '')));
    if (!isValidUuid($personId) || $idType === '') {
        continue;
    }

    if (!isset($governmentIdsByPerson[$personId])) {
        $governmentIdsByPerson[$personId] = [];
    }

    $governmentIdsByPerson[$personId][$idType] = cleanText($governmentIdRow['id_value_encrypted'] ?? null) ?? '';
}

$spouseByPerson = [];
foreach ($spouseRows as $spouseRow) {
    $personId = cleanText($spouseRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId) || isset($spouseByPerson[$personId])) {
        continue;
    }

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

$parentsByPerson = [];
foreach ($parentRows as $parentRow) {
    $personId = cleanText($parentRow['person_id'] ?? null) ?? '';
    $parentType = strtolower(trim((string)(cleanText($parentRow['parent_type'] ?? null) ?? '')));
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

$educationByPerson = [];
foreach ($educationRows as $educationRow) {
    $personId = cleanText($educationRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $mappedEducationRow = mapPersonEducationRowToAdmin((array)$educationRow);
    if (!is_array($mappedEducationRow)) {
        continue;
    }

    if (!isset($educationByPerson[$personId])) {
        $educationByPerson[$personId] = [];
    }

    $educationByPerson[$personId][] = $mappedEducationRow;
}

$eligibilityByPerson = [];
foreach ($eligibilityRows as $eligibilityRow) {
    $personId = cleanText($eligibilityRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    if (!isset($eligibilityByPerson[$personId])) {
        $eligibilityByPerson[$personId] = [];
    }

    $eligibilityByPerson[$personId][] = [
        'id' => cleanText($eligibilityRow['id'] ?? null) ?? '',
        'eligibility_name' => cleanText($eligibilityRow['eligibility_name'] ?? null) ?? '',
        'rating' => cleanText($eligibilityRow['rating'] ?? null) ?? '',
        'exam_date' => cleanText($eligibilityRow['exam_date'] ?? null) ?? '',
        'exam_place' => cleanText($eligibilityRow['exam_place'] ?? null) ?? '',
        'license_no' => cleanText($eligibilityRow['license_no'] ?? null) ?? '',
        'license_validity' => cleanText($eligibilityRow['license_validity'] ?? null) ?? '',
        'sequence_no' => (int)($eligibilityRow['sequence_no'] ?? 0),
    ];
}

$workExperiencesByPerson = [];
foreach ($workExperienceRows as $workExperienceRow) {
    $personId = cleanText($workExperienceRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    if (!isset($workExperiencesByPerson[$personId])) {
        $workExperiencesByPerson[$personId] = [];
    }

    $workExperiencesByPerson[$personId][] = [
        'id' => cleanText($workExperienceRow['id'] ?? null) ?? '',
        'inclusive_date_from' => cleanText($workExperienceRow['inclusive_date_from'] ?? null) ?? '',
        'inclusive_date_to' => cleanText($workExperienceRow['inclusive_date_to'] ?? null) ?? '',
        'position_title' => cleanText($workExperienceRow['position_title'] ?? null) ?? '',
        'office_company' => cleanText($workExperienceRow['office_company'] ?? null) ?? '',
        'monthly_salary' => isset($workExperienceRow['monthly_salary']) ? (string)$workExperienceRow['monthly_salary'] : '',
        'salary_grade_step' => cleanText($workExperienceRow['salary_grade_step'] ?? null) ?? '',
        'appointment_status' => cleanText($workExperienceRow['appointment_status'] ?? null) ?? '',
        'is_government_service' => $workExperienceRow['is_government_service'] ?? null,
        'separation_reason' => cleanText($workExperienceRow['separation_reason'] ?? null) ?? '',
        'achievements' => cleanText($workExperienceRow['achievements'] ?? null) ?? '',
        'sequence_no' => (int)($workExperienceRow['sequence_no'] ?? 0),
    ];
}

$actorLabelById = [];
$pendingRecommendationRequestIds = [];
$allActorIds = [];
$auditLogRows = [];

if ($loadShellQueues && $personIdFilter !== '') {
    $recommendationLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,created_at,new_data,action_name,entity_name'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&action_name=in.(recommend_employee_profile_update,submit_employee_profile_update_request)'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=' . $adminPersonalInfoLimits['logs'],
        $headers
    );
    $appendDataError('Pending profile requests', $recommendationLogsResponse);
    $recommendationLogs = isSuccessful($recommendationLogsResponse) ? (array)($recommendationLogsResponse['data'] ?? []) : [];

    $reviewedLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,new_data'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&action_name=in.(approve_employee_profile_recommendation,reject_employee_profile_recommendation)'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=' . $adminPersonalInfoLimits['logs'],
        $headers
    );
    $appendDataError('Reviewed profile requests', $reviewedLogsResponse);

    $reviewedRequestIds = [];
    if (isSuccessful($reviewedLogsResponse)) {
        foreach ((array)($reviewedLogsResponse['data'] ?? []) as $reviewLog) {
            $newData = is_array($reviewLog['new_data'] ?? null) ? (array)$reviewLog['new_data'] : [];
            $requestId = trim((string)($newData['recommendation_log_id'] ?? ''));
            if ($requestId !== '') {
                $reviewedRequestIds[$requestId] = true;
            }
        }
    }

    foreach ($recommendationLogs as $recommendationLog) {
        $requestId = cleanText($recommendationLog['id'] ?? null) ?? '';
        $personId = cleanText($recommendationLog['entity_id'] ?? null) ?? '';
        if (!isValidUuid($requestId) || !isValidUuid($personId) || isset($reviewedRequestIds[$requestId])) {
            continue;
        }

        $pendingRecommendationRequestIds[] = $requestId;

        $actorUserId = cleanText($recommendationLog['actor_user_id'] ?? null) ?? '';
        if (isValidUuid($actorUserId)) {
            $allActorIds[$actorUserId] = true;
        }

        $newData = is_array($recommendationLog['new_data'] ?? null) ? (array)$recommendationLog['new_data'] : [];
        $recommendedProfile = is_array($newData['recommended_profile'] ?? null) ? (array)$newData['recommended_profile'] : [];
        $recommendedAddresses = is_array($newData['recommended_addresses'] ?? null) ? (array)$newData['recommended_addresses'] : [];
        $recommendedGovernmentIds = is_array($newData['recommended_government_ids'] ?? null) ? (array)$newData['recommended_government_ids'] : [];
        $recommendedFamily = is_array($newData['recommended_family'] ?? null) ? (array)$newData['recommended_family'] : [];
        $recommendedEducation = is_array($newData['recommended_educational_backgrounds'] ?? null) ? (array)$newData['recommended_educational_backgrounds'] : [];

        $proposedChanges = [];
        foreach ($recommendedProfile as $field => $value) {
            $label = ucwords(str_replace('_', ' ', (string)$field));
            $renderedValue = is_array($value) ? (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '') : trim((string)$value);
            if ($renderedValue !== '') {
                $proposedChanges[] = ['label' => $label, 'value' => $renderedValue];
            }
        }

        foreach (['residential' => 'Residential Address', 'permanent' => 'Permanent Address'] as $addressType => $addressLabel) {
            $addressRow = is_array($recommendedAddresses[$addressType] ?? null) ? (array)$recommendedAddresses[$addressType] : [];
            $parts = [];
            foreach (['house_no', 'street', 'subdivision', 'barangay', 'city_municipality', 'province', 'zip_code'] as $fieldName) {
                $fieldValue = trim((string)($addressRow[$fieldName] ?? ''));
                if ($fieldValue !== '') {
                    $parts[] = $fieldValue;
                }
            }
            if (!empty($parts)) {
                $proposedChanges[] = ['label' => $addressLabel, 'value' => implode(', ', $parts)];
            }
        }

        foreach ($recommendedGovernmentIds as $field => $value) {
            $renderedValue = trim((string)$value);
            if ($renderedValue !== '') {
                $proposedChanges[] = ['label' => strtoupper((string)$field) . ' No.', 'value' => $renderedValue];
            }
        }

        foreach ($recommendedFamily as $field => $value) {
            if ($field === 'children' && is_array($value)) {
                $childLabels = [];
                foreach ($value as $childRow) {
                    if (!is_array($childRow)) {
                        continue;
                    }
                    $childName = trim((string)($childRow['full_name'] ?? ''));
                    $childBirthDate = trim((string)($childRow['birth_date'] ?? ''));
                    if ($childName !== '' || $childBirthDate !== '') {
                        $childLabels[] = trim($childName . ($childBirthDate !== '' ? (' (' . $childBirthDate . ')') : ''));
                    }
                }
                if (!empty($childLabels)) {
                    $proposedChanges[] = ['label' => 'Children', 'value' => implode('; ', $childLabels)];
                }
                continue;
            }

            $renderedValue = trim((string)$value);
            if ($renderedValue !== '') {
                $proposedChanges[] = ['label' => ucwords(str_replace('_', ' ', (string)$field)), 'value' => $renderedValue];
            }
        }

        foreach ($recommendedEducation as $educationRow) {
            if (!is_array($educationRow)) {
                continue;
            }

            $levelLabel = ucwords(str_replace('_', ' ', (string)($educationRow['education_level'] ?? 'Education')));
            $school = trim((string)($educationRow['school_name'] ?? ''));
            $degree = trim((string)($educationRow['degree_course'] ?? ''));
            $years = trim((string)($educationRow['attendance_from_year'] ?? ''));
            $yearTo = trim((string)($educationRow['attendance_to_year'] ?? ''));
            $valueParts = array_values(array_filter([$school, $degree, trim($years . ($yearTo !== '' ? ('-' . $yearTo) : ''))], static fn ($part): bool => $part !== ''));
            if (!empty($valueParts)) {
                $proposedChanges[] = ['label' => $levelLabel, 'value' => implode(' | ', $valueParts)];
            }
        }

        $summary = !empty($proposedChanges)
            ? count($proposedChanges) . ' change(s) pending review'
            : 'Personal information request pending review';

        $submittedAtRaw = cleanText($recommendationLog['created_at'] ?? null) ?? '';
        $dueAtRaw = cleanText($newData['request_due_at'] ?? null) ?? '';
        $dueAtLabel = '-';
        $statusLabel = 'Pending';
        $statusClass = 'bg-amber-100 text-amber-800';
        if ($dueAtRaw !== '' && strtotime($dueAtRaw) !== false) {
            $dueAtLabel = $formatAdminPersonalInfoTimestamp($dueAtRaw) . ' PST';
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
            $dueAtDate = new DateTimeImmutable($dueAtRaw);
            if ($dueAtDate < $now) {
                $statusLabel = 'Overdue';
                $statusClass = 'bg-rose-100 text-rose-800';
            }
        }

        $recommendationHistoryRows[] = [
            'recommendation_log_id' => $requestId,
            'person_id' => $personId,
            'employee_name' => (string)($personNameById[$personId] ?? 'Unknown Employee'),
            'submitted_by' => $actorUserId,
            'submitted_at_label' => $formatAdminPersonalInfoTimestamp($submittedAtRaw),
            'submitted_at_date' => $submittedAtRaw !== '' && strtotime($submittedAtRaw) !== false ? date('Y-m-d', strtotime($submittedAtRaw)) : '',
            'due_at_label' => $dueAtLabel,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'summary' => $summary,
            'proposed_changes' => $proposedChanges,
            'search_text' => strtolower(trim(((string)($personNameById[$personId] ?? '')) . ' ' . $summary)),
        ];
    }

    $spouseRequestResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,created_at,new_data'
        . '&module_name=eq.employee'
        . '&entity_name=eq.person_family_spouses_request'
        . '&action_name=eq.submit_spouse_addition_request'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=' . $adminPersonalInfoLimits['logs'],
        $headers
    );
    $appendDataError('Spouse entry requests', $spouseRequestResponse);
    $spouseRequestLogs = isSuccessful($spouseRequestResponse) ? (array)($spouseRequestResponse['data'] ?? []) : [];

    $spouseDecisionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,new_data'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.person_family_spouses_request'
        . '&action_name=in.(approve_spouse_addition_request,reject_spouse_addition_request)'
        . '&entity_id=in.(' . $personIdFilter . ')'
        . '&order=created_at.desc&limit=' . $adminPersonalInfoLimits['logs'],
        $headers
    );
    $appendDataError('Reviewed spouse requests', $spouseDecisionResponse);

    $reviewedSpouseRequestIds = [];
    if (isSuccessful($spouseDecisionResponse)) {
        foreach ((array)($spouseDecisionResponse['data'] ?? []) as $decisionRow) {
            $newData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
            $requestId = trim((string)($newData['request_log_id'] ?? ''));
            if ($requestId !== '') {
                $reviewedSpouseRequestIds[$requestId] = true;
            }
        }
    }

    foreach ($spouseRequestLogs as $requestLog) {
        $requestId = cleanText($requestLog['id'] ?? null) ?? '';
        $personId = cleanText($requestLog['entity_id'] ?? null) ?? '';
        if (!isValidUuid($requestId) || !isValidUuid($personId) || isset($reviewedSpouseRequestIds[$requestId])) {
            continue;
        }

        $actorUserId = cleanText($requestLog['actor_user_id'] ?? null) ?? '';
        if (isValidUuid($actorUserId)) {
            $allActorIds[$actorUserId] = true;
        }

        $requestData = is_array($requestLog['new_data'] ?? null) ? (array)$requestLog['new_data'] : [];
        $spouseName = trim(implode(' ', array_filter([
            trim((string)($requestData['spouse_first_name'] ?? '')),
            trim((string)($requestData['spouse_middle_name'] ?? '')),
            trim((string)($requestData['spouse_surname'] ?? '')),
            trim((string)($requestData['spouse_name_extension'] ?? '')),
        ], static fn ($part): bool => $part !== '')));

        $spouseRequestRows[] = [
            'request_log_id' => $requestId,
            'person_id' => $personId,
            'employee_name' => (string)($personNameById[$personId] ?? 'Unknown Employee'),
            'spouse_name' => $spouseName !== '' ? $spouseName : 'Spouse Entry Request',
            'request_notes' => trim((string)($requestData['request_notes'] ?? '')),
            'attachment_url' => trim((string)($requestData['attachment_url'] ?? '')),
            'attachment_name' => trim((string)($requestData['attachment_name'] ?? '')),
            'submitted_by' => $actorUserId,
            'submitted_at_label' => $formatAdminPersonalInfoTimestamp(cleanText($requestLog['created_at'] ?? null) ?? ''),
        ];
    }
}

if ($loadAuditLogData) {
    $auditLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,created_at,module_name,entity_name,action_name,old_data,new_data'
        . '&module_name=eq.personal_information'
        . '&order=created_at.desc&limit=' . $adminPersonalInfoLimits['logs'],
        $headers
    );
    $appendDataError('Personal information audit logs', $auditLogsResponse);
    $auditLogRows = isSuccessful($auditLogsResponse) ? (array)($auditLogsResponse['data'] ?? []) : [];

    foreach ($auditLogRows as $auditLogRow) {
        $actorUserId = cleanText($auditLogRow['actor_user_id'] ?? null) ?? '';
        if (isValidUuid($actorUserId)) {
            $allActorIds[$actorUserId] = true;
        }
    }
}

if (!empty($allActorIds)) {
    $actorFilter = sanitizeUuidListForInFilter(array_keys($allActorIds));
    if ($actorFilter !== '') {
        $actorResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id,username,email&id=in.(' . $actorFilter . ')&limit=' . $adminPersonalInfoLimits['actors'],
            $headers
        );
        $appendDataError('Request actors', $actorResponse);
        if (isSuccessful($actorResponse)) {
            foreach ((array)($actorResponse['data'] ?? []) as $actorRow) {
                $actorId = cleanText($actorRow['id'] ?? null) ?? '';
                if (!isValidUuid($actorId)) {
                    continue;
                }

                $actorLabelById[$actorId] = (string)(cleanText($actorRow['username'] ?? null) ?? cleanText($actorRow['email'] ?? null) ?? 'User');
            }
        }
    }
}

foreach ($recommendationHistoryRows as &$recommendationRow) {
    $submittedById = (string)($recommendationRow['submitted_by'] ?? '');
    $recommendationRow['submitted_by'] = $submittedById !== ''
        ? (string)($actorLabelById[$submittedById] ?? 'Employee')
        : 'Employee';
    $recommendationRow['search_text'] = strtolower(trim(
        (string)($recommendationRow['employee_name'] ?? '')
        . ' ' . (string)($recommendationRow['submitted_by'] ?? '')
        . ' ' . (string)($recommendationRow['summary'] ?? '')
    ));
}
unset($recommendationRow);

foreach ($spouseRequestRows as &$spouseRequestRow) {
    $submittedById = (string)($spouseRequestRow['submitted_by'] ?? '');
    $spouseRequestRow['submitted_by'] = $submittedById !== ''
        ? (string)($actorLabelById[$submittedById] ?? 'Employee')
        : 'Employee';
}
unset($spouseRequestRow);

if ($loadAuditLogData) {
    $resolveAuditPersonId = static function (array $auditLogRow): string {
        $entityName = strtolower(trim((string)(cleanText($auditLogRow['entity_name'] ?? null) ?? '')));
        $entityId = cleanText($auditLogRow['entity_id'] ?? null) ?? '';
        if ($entityName === 'people' && isValidUuid($entityId)) {
            return $entityId;
        }

        $newData = is_array($auditLogRow['new_data'] ?? null) ? (array)$auditLogRow['new_data'] : [];
        $oldData = is_array($auditLogRow['old_data'] ?? null) ? (array)$auditLogRow['old_data'] : [];
        foreach ([$newData, $oldData] as $candidate) {
            $personId = cleanText($candidate['person_id'] ?? null) ?? cleanText($candidate['source_person_id'] ?? null) ?? '';
            if (isValidUuid($personId)) {
                return $personId;
            }
        }

        return '';
    };

    $describeAuditAction = static function (string $actionName): array {
        $normalized = strtolower(trim($actionName));

        return match ($normalized) {
            'recommend_employee_profile_update', 'submit_employee_profile_update_request' => ['Profile Change Request', 'bg-amber-100 text-amber-800'],
            'approve_employee_profile_recommendation', 'approve_spouse_addition_request' => ['Approved', 'bg-emerald-100 text-emerald-800'],
            'reject_employee_profile_recommendation', 'reject_spouse_addition_request' => ['Rejected', 'bg-rose-100 text-rose-800'],
            'save_family_background', 'save_educational_background', 'add_civil_service_eligibility', 'edit_civil_service_eligibility', 'delete_civil_service_eligibility', 'add_work_experience', 'edit_work_experience', 'delete_work_experience' => ['Historical Direct Update', 'bg-slate-100 text-slate-700'],
            default => [ucwords(str_replace('_', ' ', $normalized !== '' ? $normalized : 'activity logged')), 'bg-slate-100 text-slate-700'],
        };
    };

    foreach ($auditLogRows as $auditLogRow) {
        $actionName = cleanText($auditLogRow['action_name'] ?? null) ?? '';
        $personId = $resolveAuditPersonId((array)$auditLogRow);
        [$actionLabel, $actionClass] = $describeAuditAction($actionName);

        $newData = is_array($auditLogRow['new_data'] ?? null) ? (array)$auditLogRow['new_data'] : [];
        $notes = trim((string)(cleanText($newData['remarks'] ?? null) ?? cleanText($newData['recommendation_notes'] ?? null) ?? cleanText($newData['request_notes'] ?? null) ?? ''));
        $employeeName = $personId !== ''
            ? (string)($personNameById[$personId] ?? 'Unknown Employee')
            : 'System Record';
        $actorUserId = cleanText($auditLogRow['actor_user_id'] ?? null) ?? '';

        $personalInfoAuditRows[] = [
            'log_id' => cleanText($auditLogRow['id'] ?? null) ?? '',
            'created_at_label' => $formatAdminPersonalInfoTimestamp(cleanText($auditLogRow['created_at'] ?? null) ?? ''),
            'created_at_raw' => cleanText($auditLogRow['created_at'] ?? null) ?? '',
            'actor_label' => $actorUserId !== '' ? (string)($actorLabelById[$actorUserId] ?? 'System User') : 'System User',
            'employee_name' => $employeeName,
            'entity_name' => cleanText($auditLogRow['entity_name'] ?? null) ?? '',
            'action_name' => $actionName,
            'action_label' => $actionLabel,
            'action_class' => $actionClass,
            'notes' => $notes,
        ];
    }
}

$psgcMunicipalitiesPath = __DIR__ . '/../../../../psgc/municipalities.json';
$psgcBarangaysPath = __DIR__ . '/../../../../psgc/barangays.json';

$psgcMunicipalityRows = [];
$psgcBarangayRows = [];
$municipalityNameByCode = [];
$addressCityDisplayByKey = [];
$provinceOptionsMap = [];

if ($loadAssignmentLookupData && is_file($psgcMunicipalitiesPath)) {
    $decodedMunicipalities = json_decode((string)file_get_contents($psgcMunicipalitiesPath), true);
    if (is_array($decodedMunicipalities)) {
        $psgcMunicipalityRows = $decodedMunicipalities;
    }
}

if ($loadAssignmentLookupData && is_file($psgcBarangaysPath)) {
    $decodedBarangays = json_decode((string)file_get_contents($psgcBarangaysPath), true);
    if (is_array($decodedBarangays)) {
        $psgcBarangayRows = $decodedBarangays;
    }
}

foreach ($psgcMunicipalityRows as $municipalityRow) {
    if (!is_array($municipalityRow)) {
        continue;
    }

    $cityCode = trim((string)($municipalityRow['code'] ?? $municipalityRow['citymun'] ?? $municipalityRow['city_code'] ?? ''));
    $cityDisplay = trim((string)($municipalityRow['name'] ?? $municipalityRow['citymun_name'] ?? $municipalityRow['city_municipality_name'] ?? ''));
    $provinceDisplay = trim((string)($municipalityRow['province'] ?? ''));
    if ($cityCode !== '' && $cityDisplay !== '') {
        $municipalityNameByCode[$cityCode] = $cityDisplay;
    }

    $cityKey = normalizeZipLookupPart($cityDisplay);
    if ($cityKey !== '') {
        $addressCityDisplayByKey[$cityKey] = $cityDisplay;
    }
    if ($provinceDisplay !== '') {
        $provinceOptionsMap[$provinceDisplay] = true;
    }
}

$addressBarangayDisplayByCityKey = [];
foreach ($psgcBarangayRows as $barangayRow) {
    if (!is_array($barangayRow)) {
        continue;
    }

    $cityRef = trim((string)($barangayRow['citymun'] ?? $barangayRow['city_code'] ?? $barangayRow['city_municipality_name'] ?? ''));
    $cityDisplay = trim((string)($municipalityNameByCode[$cityRef] ?? $cityRef));
    $barangayDisplay = trim((string)($barangayRow['name'] ?? ''));
    $cityKey = normalizeZipLookupPart($cityDisplay);
    $barangayKey = normalizeZipLookupPart($barangayDisplay);
    if ($cityKey === '' || $barangayKey === '') {
        continue;
    }

    if (!isset($addressCityDisplayByKey[$cityKey])) {
        $addressCityDisplayByKey[$cityKey] = $cityDisplay;
    }

    if (!isset($addressBarangayDisplayByCityKey[$cityKey])) {
        $addressBarangayDisplayByCityKey[$cityKey] = [];
    }

    if (!isset($addressBarangayDisplayByCityKey[$cityKey][$barangayKey])) {
        $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] = $barangayDisplay;
    }
}

foreach ($addressRows as $addressRow) {
    $cityDisplay = trim((string)(cleanText($addressRow['city_municipality'] ?? null) ?? ''));
    $barangayDisplay = trim((string)(cleanText($addressRow['barangay'] ?? null) ?? ''));
    $provinceDisplay = trim((string)(cleanText($addressRow['province'] ?? null) ?? ''));
    $cityKey = normalizeZipLookupPart($cityDisplay);
    $barangayKey = normalizeZipLookupPart($barangayDisplay);

    if ($cityKey !== '') {
        $addressCityDisplayByKey[$cityKey] = $addressCityDisplayByKey[$cityKey] ?? $cityDisplay;
    }
    if ($cityKey !== '' && $barangayKey !== '') {
        if (!isset($addressBarangayDisplayByCityKey[$cityKey])) {
            $addressBarangayDisplayByCityKey[$cityKey] = [];
        }
        $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] = $addressBarangayDisplayByCityKey[$cityKey][$barangayKey] ?? $barangayDisplay;
    }
    if ($provinceDisplay !== '') {
        $provinceOptionsMap[$provinceDisplay] = true;
    }
}

$addressCityOptions = array_values($addressCityDisplayByKey);
sort($addressCityOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressBarangayOptionsMap = [];
foreach ($addressBarangayDisplayByCityKey as $barangaysByKey) {
    foreach ($barangaysByKey as $barangayDisplay) {
        $barangayOptionsKey = trim((string)$barangayDisplay);
        if ($barangayOptionsKey !== '') {
            $addressBarangayOptionsMap[$barangayOptionsKey] = true;
        }
    }
}

$addressBarangayOptions = array_keys($addressBarangayOptionsMap);
sort($addressBarangayOptions, SORT_NATURAL | SORT_FLAG_CASE);

$addressProvinceOptions = array_keys($provinceOptionsMap);
sort($addressProvinceOptions, SORT_NATURAL | SORT_FLAG_CASE);

$statusPill = static function (string $status): array {
    $normalized = strtolower(trim($status));
    if ($normalized === 'active') {
        return ['Active', 'bg-emerald-100 text-emerald-800', 'active'];
    }

    return ['Inactive', 'bg-amber-100 text-amber-800', 'inactive'];
};

$employeeTableRows = [];
$departmentFilters = [];
$totalProfiles = 0;
$completeRecords = 0;
$activeEmployees = 0;
$inactiveEmployees = 0;

foreach ($officeRows as $officeRow) {
    $officeName = cleanText($officeRow['office_name'] ?? null) ?? '';
    if ($officeName !== '') {
        $departmentFilters[$officeName] = true;
    }
}

foreach ($employmentRows as $employmentRow) {
    $employmentId = cleanText($employmentRow['id'] ?? null) ?? '';
    $person = (array)($employmentRow['person'] ?? []);
    $personId = cleanText($employmentRow['person_id'] ?? null) ?? cleanText($person['id'] ?? null) ?? '';
    if (!isValidUuid($employmentId) || !isValidUuid($personId)) {
        continue;
    }

    $totalProfiles++;

    $firstName = trim((string)(cleanText($person['first_name'] ?? null) ?? ''));
    $middleName = trim((string)(cleanText($person['middle_name'] ?? null) ?? ''));
    $surname = trim((string)(cleanText($person['surname'] ?? null) ?? ''));
    $nameExtension = trim((string)(cleanText($person['name_extension'] ?? null) ?? ''));
    $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $surname, $nameExtension], static fn ($part): bool => $part !== '')));
    if ($fullName === '') {
        $fullName = 'Unknown Employee';
    }

    $officeName = cleanText($employmentRow['office']['office_name'] ?? null) ?? 'Unassigned Division';
    $positionName = cleanText($employmentRow['position']['position_title'] ?? null) ?? 'Unassigned Position';
    if ($officeName !== 'Unassigned Division') {
        $departmentFilters[$officeName] = true;
    }

    $statusRaw = strtolower(trim((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'inactive')));
    [$statusLabel, $statusClass, $statusBucket] = $statusPill($statusRaw);
    if ($statusBucket === 'active') {
        $activeEmployees++;
    } else {
        $inactiveEmployees++;
    }

    $email = cleanText($person['personal_email'] ?? null) ?? '';
    $mobile = cleanText($person['mobile_no'] ?? null) ?? '';
    if ($officeName !== 'Unassigned Division' && $positionName !== 'Unassigned Position' && ($email !== '' || $mobile !== '')) {
        $completeRecords++;
    }

    $telephone = cleanText($person['telephone_no'] ?? null) ?? '';
    $agencyEmployeeNo = cleanText($person['agency_employee_no'] ?? null) ?? '';
    $employeeCode = $agencyEmployeeNo !== '' ? $agencyEmployeeNo : ('EMP-' . strtoupper(substr(str_replace('-', '', $personId), 0, 6)));

    $roleKey = strtolower(trim((string)($roleKeyByUserId[(string)(cleanText($person['user_id'] ?? null) ?? '')] ?? 'employee')));

    $searchText = strtolower(trim(implode(' ', array_filter([
        $employeeCode,
        $fullName,
        $email,
        $mobile,
        $telephone,
        $officeName,
        $positionName,
        $statusLabel,
        $roleKey,
    ], static fn ($part): bool => $part !== ''))));

    if (!$loadEmployeeRegionData) {
        continue;
    }

    if ($filterDepartment !== '' && strcasecmp($officeName, $filterDepartment) !== 0) {
        continue;
    }
    if ($filterStatus !== '' && $statusBucket !== $filterStatus) {
        continue;
    }

    $resolvedProfilePhotoUrl = systemTopnavResolveProfilePhotoUrl(cleanText($person['profile_photo_url'] ?? null));
    if ($filterKeyword !== '' && !str_contains($searchText, $filterKeyword)) {
        continue;
    }

    $residential = (array)($addressesByPerson[$personId]['residential'] ?? []);
    $permanent = (array)($addressesByPerson[$personId]['permanent'] ?? []);
    $governmentIds = (array)($governmentIdsByPerson[$personId] ?? []);

    $employeeTableRows[] = [
        'employment_id' => $employmentId,
        'person_id' => $personId,
        'office_id' => cleanText($employmentRow['office_id'] ?? null) ?? '',
        'position_id' => cleanText($employmentRow['position_id'] ?? null) ?? '',
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
        'role_key' => $roleKey,
        'profile_photo_url' => $resolvedProfilePhotoUrl ?? '',
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
        'children' => array_values((array)($childrenByPerson[$personId] ?? [])),
        'educational_backgrounds' => array_values((array)($educationByPerson[$personId] ?? [])),
        'civil_service_eligibilities' => array_values((array)($eligibilityByPerson[$personId] ?? [])),
        'work_experiences' => array_values((array)($workExperiencesByPerson[$personId] ?? [])),
    ];
}

$departmentFilterOptions = array_keys($departmentFilters);
sort($departmentFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);

$placeOfBirthOptionsMap = [];
foreach ($employeeTableRows as $employeeRow) {
    $placeOfBirth = trim((string)($employeeRow['place_of_birth'] ?? ''));
    if ($placeOfBirth !== '') {
        $placeOfBirthOptionsMap[$placeOfBirth] = true;
    }
}
foreach ($addressCityOptions as $cityOption) {
    $cityValue = trim((string)$cityOption);
    if ($cityValue !== '') {
        $placeOfBirthOptionsMap[$cityValue] = true;
    }
}

$placeOfBirthOptions = array_keys($placeOfBirthOptionsMap);
sort($placeOfBirthOptions, SORT_NATURAL | SORT_FLAG_CASE);

$civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced', 'Annulled'];
$bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

$needsUpdateCount = max(0, $totalProfiles - $completeRecords);