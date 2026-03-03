<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('applicantDecisionMap')) {
    function applicantDecisionMap(string $decision): ?array
    {
        $decisionKey = strtolower(trim($decision));

        if ($decisionKey === 'approve_for_next_stage') {
            return [
                'application_status' => 'shortlisted',
                'feedback_decision' => 'for_next_step',
                'notification_text' => 'Your application passed initial screening and is moving to the next stage.',
                'decision_label' => 'Approve for Next Stage',
            ];
        }

        if ($decisionKey === 'disqualify_application') {
            return [
                'application_status' => 'rejected',
                'feedback_decision' => 'rejected',
                'notification_text' => 'Your application did not pass initial screening.',
                'decision_label' => 'Disqualify Application',
            ];
        }

        if ($decisionKey === 'return_for_compliance') {
            return [
                'application_status' => 'screening',
                'feedback_decision' => 'on_hold',
                'notification_text' => 'Your application needs additional compliance before continuing screening.',
                'decision_label' => 'Return for Compliance',
            ];
        }

        return null;
    }
}

if (!function_exists('adminApplicantsLoadRecruitmentEmailTemplates')) {
    function adminApplicantsLoadRecruitmentEmailTemplates(string $supabaseUrl, array $headers): array
    {
        $defaults = [
            'passed' => [
                'subject' => 'Application Update: Passed Initial Screening',
                'body' => 'Hello {applicant_name},<br><br>Good news. You passed initial screening for <strong>{job_title}</strong>.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please wait for next instructions.',
            ],
            'failed' => [
                'subject' => 'Application Update: Not Qualified',
                'body' => 'Hello {applicant_name},<br><br>Thank you for applying to <strong>{job_title}</strong>.<br>Reference: <strong>{application_ref_no}</strong><br><br>Result: Not Qualified.<br>Remarks: {remarks}<br><br>We appreciate your interest.',
            ],
            'next_stage' => [
                'subject' => 'Application Update: Next Stage',
                'body' => 'Hello {applicant_name},<br><br>Your application for <strong>{job_title}</strong> has moved to the next stage.<br>Reference: <strong>{application_ref_no}</strong><br><br>Remarks: {remarks}<br><br>Please monitor your account and email for final review schedule and office signing instructions.',
            ],
        ];

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/') . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.email_templates') . '&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return $defaults;
        }

        $raw = $response['data'][0]['setting_value'] ?? null;
        $value = is_array($raw) && array_key_exists('value', $raw) ? $raw['value'] : $raw;
        if (!is_array($value)) {
            return $defaults;
        }

        foreach (['passed', 'failed', 'next_stage'] as $key) {
            $row = is_array($value[$key] ?? null) ? (array)$value[$key] : [];
            $subject = trim((string)($row['subject'] ?? ''));
            $body = trim((string)($row['body'] ?? ''));
            if ($subject !== '') {
                $defaults[$key]['subject'] = $subject;
            }
            if ($body !== '') {
                $defaults[$key]['body'] = $body;
            }
        }

        return $defaults;
    }
}

if (!function_exists('adminApplicantsIsValidUuid')) {
    function adminApplicantsIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('adminApplicantsCreateEmployeeAccount')) {
    function adminApplicantsCreateEmployeeAccount(
        string $email,
        string $fullName,
        ?string $officeId,
        string $adminUserId,
        string $supabaseUrl,
        array $headers,
        ?string &$createdUserId = null,
        ?string &$temporaryPassword = null
    ): array {
        $email = strtolower(trim($email));
        $fullName = trim($fullName);
        $officeId = trim((string)$officeId);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Valid email is required.'];
        }
        if ($fullName === '') {
            return ['ok' => false, 'message' => 'Full name is required for new account.'];
        }

        [$firstName, $surname] = splitFullName($fullName);
        $temporaryPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);

        $createAuth = apiRequest(
            'POST',
            $supabaseUrl . '/auth/v1/admin/users',
            $headers,
            [
                'email' => $email,
                'password' => $temporaryPassword,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $fullName,
                    'created_by_admin' => $adminUserId,
                    'source' => 'recruitment_add_as_employee',
                ],
            ]
        );

        if (!isSuccessful($createAuth)) {
            $raw = strtolower((string)($createAuth['raw'] ?? ''));
            if (str_contains($raw, 'already') || str_contains($raw, 'exists')) {
                return ['ok' => false, 'message' => 'Email already exists in authentication.'];
            }
            return ['ok' => false, 'message' => 'Failed to create authentication user.'];
        }

        $createdUserId = (string)($createAuth['data']['id'] ?? '');
        if (!adminApplicantsIsValidUuid($createdUserId)) {
            return ['ok' => false, 'message' => 'Invalid auth response when creating account.'];
        }

        $accountResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_accounts',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'id' => $createdUserId,
                'email' => $email,
                'account_status' => 'active',
                'email_verified_at' => gmdate('c'),
                'must_change_password' => true,
            ]]
        );

        if (!isSuccessful($accountResponse)) {
            return ['ok' => false, 'message' => 'Failed to create user account record.'];
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'user_id' => $createdUserId,
                'first_name' => $firstName,
                'surname' => $surname,
                'personal_email' => $email,
            ]]
        );

        $employeeRole = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
            $headers
        );
        $employeeRoleId = (string)($employeeRole['data'][0]['id'] ?? '');
        if (adminApplicantsIsValidUuid($employeeRoleId)) {
            $assignmentPayload = [
                'user_id' => $createdUserId,
                'role_id' => $employeeRoleId,
                'is_primary' => true,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => gmdate('c'),
            ];

            if ($officeId !== '' && adminApplicantsIsValidUuid($officeId)) {
                $assignmentPayload['office_id'] = $officeId;
            }

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/user_role_assignments',
                array_merge($headers, ['Prefer: return=minimal']),
                [$assignmentPayload]
            );
        }

        return ['ok' => true, 'message' => 'Account created successfully.'];
    }
}

if (!function_exists('adminApplicantsRenderTemplate')) {
    function adminApplicantsRenderTemplate(string $template, array $replacements): string
    {
        $output = $template;
        foreach ($replacements as $key => $value) {
            $output = str_replace('{' . $key . '}', (string)$value, $output);
        }

        return $output;
    }
}

if (!function_exists('adminApplicantsExtractStructuredInputs')) {
    function adminApplicantsExtractStructuredInputs(string $feedbackText): array
    {
        if ($feedbackText === '') {
            return [];
        }

        $decoded = json_decode($feedbackText, true);
        if (!is_array($decoded)) {
            return [];
        }

        return [
            'eligibility' => cleanText($decoded['eligibility'] ?? $decoded['eligibility_type'] ?? null),
            'education_level' => cleanText($decoded['education_level'] ?? $decoded['highest_education_level'] ?? null),
            'education_years' => $decoded['education_years'] ?? $decoded['years_in_college'] ?? null,
            'training_hours' => $decoded['training_hours'] ?? $decoded['hours_of_training'] ?? null,
            'experience_years' => $decoded['experience_years'] ?? $decoded['years_of_experience'] ?? null,
        ];
    }
}

if (!function_exists('adminApplicantsNormalizeEducationLevel')) {
    function adminApplicantsNormalizeEducationLevel(string $value): string
    {
        $key = strtolower(trim($value));

        return match ($key) {
            'elementary' => 'elementary',
            'secondary', 'highschool', 'high_school', 'high-school' => 'secondary',
            'vocational', 'trade', 'trade_course', 'vocational_trade_course', 'vocational/trade course', 'tvet' => 'vocational',
            'college', 'bachelor', 'bachelors', "bachelor's", "bachelor's degree" => 'college',
            'graduate', 'graduate_studies', 'masters', 'masteral', 'doctorate', 'phd' => 'graduate',
            default => 'college',
        };
    }
}

if (!function_exists('adminApplicantsEducationYearsToLevel')) {
    function adminApplicantsEducationYearsToLevel(float $years): string
    {
        if ($years >= 6) {
            return 'graduate';
        }
        if ($years >= 4) {
            return 'college';
        }
        if ($years >= 2) {
            return 'vocational';
        }
        if ($years >= 1) {
            return 'secondary';
        }

        return 'elementary';
    }
}

if (!function_exists('adminApplicantsEducationLevelRank')) {
    function adminApplicantsEducationLevelRank(string $level): int
    {
        return match (adminApplicantsNormalizeEducationLevel($level)) {
            'graduate' => 5,
            'college' => 4,
            'vocational' => 3,
            'secondary' => 2,
            default => 1,
        };
    }
}

if (!function_exists('adminApplicantsEstimateSignalInputs')) {
    function adminApplicantsEstimateSignalInputs(array $application, array $documents, array $interviews): array
    {
        $applicationStatus = strtolower((string)($application['application_status'] ?? 'submitted'));
        $applicant = is_array($application['applicant'] ?? null) ? (array)$application['applicant'] : [];

        $documentTypes = [];
        foreach ($documents as $document) {
            $key = strtolower((string)($document['document_type'] ?? ''));
            if ($key !== '') {
                $documentTypes[$key] = true;
            }
        }

        $hasEligibilityDoc = isset($documentTypes['eligibility']) || isset($documentTypes['license']) || isset($documentTypes['id']) || isset($documentTypes['certificate']);
        $eligibility = $hasEligibilityDoc ? 'career service sub professional' : 'n/a';

        $educationYears = (isset($documentTypes['transcript']) || isset($documentTypes['pds'])) ? 2.0 : 0.0;
        $trainingHours = isset($documentTypes['certificate']) ? 4.0 : 0.0;
        if (trim((string)($applicant['portfolio_url'] ?? '')) !== '') {
            $trainingHours += 2.0;
        }

        $experienceYears = 0.0;
        if (trim((string)($applicant['resume_url'] ?? '')) !== '') {
            $experienceYears += 1.0;
        }
        if (in_array($applicationStatus, ['screening', 'shortlisted', 'interview', 'offer', 'hired'], true)) {
            $experienceYears += 0.5;
        }
        if (in_array($applicationStatus, ['offer', 'hired'], true)) {
            $experienceYears += 0.5;
        }

        foreach ($interviews as $interview) {
            $result = strtolower(trim((string)($interview['result'] ?? '')));
            if (in_array($result, ['pass', 'passed', 'recommended', 'completed'], true)) {
                $experienceYears += 0.25;
                break;
            }
        }

        return [
            'eligibility' => $eligibility,
            'education_years' => $educationYears,
            'training_hours' => $trainingHours,
            'experience_years' => $experienceYears,
        ];
    }
}

if (!function_exists('adminApplicantsNormalizeEligibilityTokens')) {
    function adminApplicantsNormalizeEligibilityTokens(string $value): array
    {
        $normalized = str_replace(['/', '|'], ',', strtolower(trim($value)));
        $parts = preg_split('/\s*,\s*/', $normalized) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || in_array($part, ['n/a', 'na', 'none', 'not applicable', 'not_applicable'], true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }
}

if (!function_exists('adminApplicantsEligibilityMatchesAny')) {
    function adminApplicantsEligibilityMatchesAny(string $requiredEligibility, string $actualEligibility): bool
    {
        $requiredTokens = adminApplicantsNormalizeEligibilityTokens($requiredEligibility);
        if (empty($requiredTokens)) {
            return true;
        }

        $actualKey = strtolower(trim($actualEligibility));
        if ($actualKey === '' || in_array($actualKey, ['n/a', 'na', 'none'], true)) {
            return false;
        }

        foreach ($requiredTokens as $requiredToken) {
            if ($actualKey === $requiredToken || str_contains($actualKey, $requiredToken) || str_contains($requiredToken, $actualKey)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('adminApplicantsNormalizeEligibilityOption')) {
    function adminApplicantsNormalizeEligibilityOption(string $value): string
    {
        $key = strtolower(trim($value));
        return match ($key) {
            'none', 'not_applicable', 'not applicable', 'n/a', 'na', '' => 'none',
            'csc', 'career service', 'career service sub professional' => 'csc',
            'prc' => 'prc',
            'csc_prc', 'csc,prc', 'csc, prc', 'csc/prc' => 'csc_prc',
            default => 'csc_prc',
        };
    }
}

if (!function_exists('adminApplicantsEligibilityOptionMatches')) {
    function adminApplicantsEligibilityOptionMatches(string $requiredOption, string $actualOption): bool
    {
        $required = adminApplicantsNormalizeEligibilityOption($requiredOption);
        $actual = adminApplicantsNormalizeEligibilityOption($actualOption);

        if ($required === 'none') {
            return true;
        }

        return match ($required) {
            'csc' => in_array($actual, ['csc', 'csc_prc'], true),
            'prc' => in_array($actual, ['prc', 'csc_prc'], true),
            default => in_array($actual, ['csc', 'prc', 'csc_prc'], true),
        };
    }
}

if (!function_exists('adminApplicantsEligibilityOptionFromDocuments')) {
    function adminApplicantsEligibilityOptionFromDocuments(array $documents): string
    {
        $hasCsc = false;
        $hasPrc = false;

        foreach ($documents as $document) {
            $documentType = strtolower(trim((string)($document['document_type'] ?? '')));
            $fileName = strtolower(trim((string)($document['file_name'] ?? '')));

            if (
                $documentType === 'eligibility'
                || str_contains($fileName, 'csc')
                || str_contains($fileName, 'career service')
                || str_contains($fileName, 'eligibility')
            ) {
                $hasCsc = true;
            }

            if (
                $documentType === 'license'
                || str_contains($fileName, 'prc')
                || str_contains($fileName, 'board')
                || str_contains($fileName, 'licensure')
            ) {
                $hasPrc = true;
            }
        }

        if ($hasCsc && $hasPrc) {
            return 'csc_prc';
        }
        if ($hasCsc) {
            return 'csc';
        }
        if ($hasPrc) {
            return 'prc';
        }

        return 'none';
    }
}

if (!function_exists('adminApplicantsMissingCriteria')) {
    function adminApplicantsMissingCriteria(string $supabaseUrl, array $headers, array $applicationRow): array
    {
        $applicationId = trim((string)($applicationRow['id'] ?? ''));
        if ($applicationId === '') {
            return [];
        }

        $defaults = [
            'eligibility_option' => 'none',
            'minimum_education_level' => 'vocational',
            'minimum_education_years' => 2.0,
            'minimum_training_hours' => 4.0,
            'minimum_experience_years' => 1.0,
        ];

        $criteriaResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('evaluation.rule_based.criteria') . '&limit=1',
            $headers
        );
        $criteriaRaw = isSuccessful($criteriaResponse)
            ? ($criteriaResponse['data'][0]['setting_value'] ?? null)
            : null;
        $criteria = is_array($criteriaRaw) && array_key_exists('value', $criteriaRaw)
            ? (array)($criteriaRaw['value'] ?? [])
            : (array)$criteriaRaw;

        $requiredEligibilityOption = adminApplicantsNormalizeEligibilityOption((string)($criteria['eligibility'] ?? $defaults['eligibility_option']));
        $requiredEducationYearsDefault = (float)($criteria['minimum_education_years'] ?? $defaults['minimum_education_years']);
        $requiredEducationLevel = adminApplicantsNormalizeEducationLevel((string)($criteria['minimum_education_level'] ?? adminApplicantsEducationYearsToLevel($requiredEducationYearsDefault)));
        $requiredTrainingHours = (float)($criteria['minimum_training_hours'] ?? $defaults['minimum_training_hours']);
        $requiredExperienceYears = (float)($criteria['minimum_experience_years'] ?? $defaults['minimum_experience_years']);

        $job = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];
        $positionId = strtolower(trim((string)($job['position_id'] ?? '')));
        if ($positionId !== '') {
            $positionCriteriaResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('recruitment.position_criteria') . '&limit=1',
                $headers
            );

            $positionCriteriaRaw = isSuccessful($positionCriteriaResponse)
                ? ($positionCriteriaResponse['data'][0]['setting_value'] ?? null)
                : null;
            $positionCriteria = is_array($positionCriteriaRaw) && array_key_exists('value', $positionCriteriaRaw)
                ? (array)($positionCriteriaRaw['value'] ?? [])
                : (array)$positionCriteriaRaw;

            $overrides = is_array($positionCriteria['position_overrides'] ?? null)
                ? (array)$positionCriteria['position_overrides']
                : [];
            $override = is_array($overrides[$positionId] ?? null) ? (array)$overrides[$positionId] : [];

            if (!empty($override)) {
                $requiredEligibilityOption = adminApplicantsNormalizeEligibilityOption((string)($override['eligibility'] ?? $requiredEligibilityOption));
                $overrideEducationYears = (float)($override['minimum_education_years'] ?? $requiredEducationYearsDefault);
                $requiredEducationLevel = adminApplicantsNormalizeEducationLevel((string)($override['minimum_education_level'] ?? adminApplicantsEducationYearsToLevel($overrideEducationYears)));
                $requiredTrainingHours = (float)($override['minimum_training_hours'] ?? $requiredTrainingHours);
                $requiredExperienceYears = (float)($override['minimum_experience_years'] ?? $requiredExperienceYears);
            }
        }

        $feedbackResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_feedback?select=feedback_text&application_id=eq.' . rawurlencode($applicationId) . '&order=provided_at.desc&limit=1',
            $headers
        );
        $feedbackText = isSuccessful($feedbackResponse)
            ? trim((string)($feedbackResponse['data'][0]['feedback_text'] ?? ''))
            : '';

        $documentsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_documents?select=document_type,file_name&application_id=eq.' . rawurlencode($applicationId) . '&limit=500',
            $headers
        );
        $documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_interviews?select=result&application_id=eq.' . rawurlencode($applicationId) . '&order=scheduled_at.asc&limit=200',
            $headers
        );
        $interviews = isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [];

        $structured = adminApplicantsExtractStructuredInputs($feedbackText);
        $signals = adminApplicantsEstimateSignalInputs($applicationRow, $documents, $interviews);

        $structuredEligibility = strtolower(trim((string)($structured['eligibility'] ?? '')));
        $structuredEligibilityOption = $structuredEligibility !== ''
            ? adminApplicantsNormalizeEligibilityOption($structuredEligibility)
            : 'none';
        $documentEligibilityOption = adminApplicantsEligibilityOptionFromDocuments($documents);
        $actualEligibilityOption = $structuredEligibilityOption !== 'none'
            ? $structuredEligibilityOption
            : $documentEligibilityOption;

        $applicant = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
        $actualTrainingHours = (float)($applicant['training_hours_completed'] ?? 0);

        $personId = '';
        $applicantUserId = trim((string)($applicant['user_id'] ?? ''));
        if ($applicantUserId !== '') {
            $peopleResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
                $headers
            );
            if (isSuccessful($peopleResponse)) {
                $personId = strtolower(trim((string)($peopleResponse['data'][0]['id'] ?? '')));
            }
        }

        $profileEducationLevel = '';
        $profileExperienceYears = 0.0;
        if ($personId !== '') {
            $educationResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/person_educations?select=education_level&person_id=eq.' . rawurlencode($personId) . '&limit=500',
                $headers
            );

            if (isSuccessful($educationResponse)) {
                foreach ((array)($educationResponse['data'] ?? []) as $educationRow) {
                    $level = adminApplicantsNormalizeEducationLevel((string)($educationRow['education_level'] ?? ''));
                    if (
                        $profileEducationLevel === ''
                        || adminApplicantsEducationLevelRank($level) > adminApplicantsEducationLevelRank($profileEducationLevel)
                    ) {
                        $profileEducationLevel = $level;
                    }
                }
            }

            $workResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/person_work_experiences?select=inclusive_date_from,inclusive_date_to&person_id=eq.' . rawurlencode($personId) . '&limit=1000',
                $headers
            );

            if (isSuccessful($workResponse)) {
                $todayDate = new DateTimeImmutable('today');
                $totalDays = 0;
                foreach ((array)($workResponse['data'] ?? []) as $workRow) {
                    $fromRaw = trim((string)($workRow['inclusive_date_from'] ?? ''));
                    if ($fromRaw === '') {
                        continue;
                    }

                    try {
                        $fromDate = new DateTimeImmutable($fromRaw);
                    } catch (Throwable) {
                        continue;
                    }

                    $toRaw = trim((string)($workRow['inclusive_date_to'] ?? ''));
                    if ($toRaw !== '') {
                        try {
                            $toDate = new DateTimeImmutable($toRaw);
                        } catch (Throwable) {
                            $toDate = $todayDate;
                        }
                    } else {
                        $toDate = $todayDate;
                    }

                    if ($toDate < $fromDate) {
                        continue;
                    }

                    $totalDays += (int)$fromDate->diff($toDate)->days + 1;
                }

                $profileExperienceYears = round($totalDays / 365, 2);
            }
        }

        $educationLevelInput = trim((string)($structured['education_level'] ?? ''));
        $educationYears = (float)($structured['education_years'] ?? $signals['education_years'] ?? 0);
        $trainingHours = $actualTrainingHours > 0
            ? $actualTrainingHours
            : (float)($structured['training_hours'] ?? $signals['training_hours'] ?? 0);
        $experienceYears = $profileExperienceYears > 0
            ? $profileExperienceYears
            : (float)($structured['experience_years'] ?? $signals['experience_years'] ?? 0);
        $educationLevel = $educationLevelInput !== ''
            ? adminApplicantsNormalizeEducationLevel($educationLevelInput)
            : ($profileEducationLevel !== ''
                ? $profileEducationLevel
                : adminApplicantsEducationYearsToLevel($educationYears));

        $missing = [];
        if (!adminApplicantsEligibilityOptionMatches($requiredEligibilityOption, $actualEligibilityOption)) {
            $missing[] = 'eligibility';
        }
        if (adminApplicantsEducationLevelRank($educationLevel) < adminApplicantsEducationLevelRank($requiredEducationLevel)) {
            $missing[] = 'education';
        }
        if ($trainingHours < $requiredTrainingHours) {
            $missing[] = 'training';
        }
        if ($experienceYears < $requiredExperienceYears) {
            $missing[] = 'experience';
        }

        return $missing;
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'convert_hired_to_employee') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';

    if ($applicationId === '' || !adminApplicantsIsValidUuid($applicationId)) {
        redirectWithState('error', 'Valid hired application is required for employee conversion.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/applications?select=id,application_status,submitted_at,application_ref_no,job:job_postings(id,title,office_id,position_id),applicant_profile_id,applicant:applicant_profiles(id,user_id,full_name,email),feedback:application_feedback(feedback_text,provided_at)&id=eq.' . $applicationId . '&limit=1',
        $headers
    );

    $applicationRow = $applicationResponse['data'][0] ?? null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found for conversion.');
    }

    $applicationStatus = strtolower(trim((string)($applicationRow['application_status'] ?? '')));
    if ($applicationStatus !== 'hired') {
        redirectWithState('error', 'Only applications with status Hired can be converted to employee.');
    }

    $applicantProfile = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
    $job = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];

    $applicantName = trim((string)($applicantProfile['full_name'] ?? ''));
    $applicantEmail = strtolower(trim((string)($applicantProfile['email'] ?? '')));
    $applicantUserId = trim((string)($applicantProfile['user_id'] ?? ''));
    $officeId = trim((string)($job['office_id'] ?? ''));
    $positionId = trim((string)($job['position_id'] ?? ''));
    $jobTitle = trim((string)($job['title'] ?? ''));

    if ($applicantName === '' || $officeId === '' || $positionId === '') {
        redirectWithState('error', 'Application is missing required applicant or job linkage data.');
    }

    if (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Applicant email is required to create employee login credentials.');
    }

    [$firstName, $surname] = splitFullName($applicantName);
    if ($firstName === '' || $surname === '') {
        redirectWithState('error', 'Unable to split applicant full name for employee profile creation.');
    }

    $officeName = 'Assigned Division';
    if (adminApplicantsIsValidUuid($officeId)) {
        $officeResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/offices?select=office_name&id=eq.' . rawurlencode($officeId) . '&limit=1',
            $headers
        );
        if (isSuccessful($officeResponse)) {
            $officeNameRaw = trim((string)($officeResponse['data'][0]['office_name'] ?? ''));
            if ($officeNameRaw !== '') {
                $officeName = $officeNameRaw;
            }
        }
    }

    $feedbackRows = (array)($applicationRow['feedback'] ?? []);
    $feedbackText = '';
    if (!empty($feedbackRows)) {
        $feedbackText = trim((string)($feedbackRows[0]['feedback_text'] ?? ''));
    }

    $decodedFeedback = json_decode($feedbackText, true);
    $structured = is_array($decodedFeedback) ? $decodedFeedback : [];
    $pdsSummary = trim((string)($structured['pds_summary'] ?? ''));
    $careerExperience = trim((string)($structured['career_experience'] ?? ($structured['career_summary'] ?? '')));
    $workExperience = trim((string)($structured['work_experience'] ?? ''));
    $educationYears = $structured['education_years'] ?? ($structured['years_in_college'] ?? null);
    $experienceYears = $structured['experience_years'] ?? ($structured['years_of_experience'] ?? null);

    $existingPersonId = '';
    if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        $existingPersonResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id,user_id,personal_email,first_name,surname&personal_email=eq.' . encodeFilter($applicantEmail) . '&limit=1',
            $headers
        );

        $existingPersonRow = $existingPersonResponse['data'][0] ?? null;
        if (is_array($existingPersonRow)) {
            $existingPersonId = trim((string)($existingPersonRow['id'] ?? ''));
        }
    }

    $personId = $existingPersonId;
    if ($personId === '') {
        $personInsertPayload = [[
            'first_name' => $firstName,
            'surname' => $surname,
            'personal_email' => $applicantEmail !== '' ? $applicantEmail : null,
            'user_id' => adminApplicantsIsValidUuid($applicantUserId) ? $applicantUserId : null,
            'mobile_no' => cleanText($structured['mobile_no'] ?? null),
            'telephone_no' => cleanText($structured['telephone_no'] ?? null),
            'place_of_birth' => cleanText($structured['place_of_birth'] ?? null),
            'civil_status' => cleanText($structured['civil_status'] ?? null),
            'citizenship' => cleanText($structured['citizenship'] ?? null),
        ]];

        $personInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: return=representation']),
            $personInsertPayload
        );

        if (!isSuccessful($personInsertResponse)) {
            redirectWithState('error', 'Failed to create employee profile from hired applicant.');
        }

        $personId = trim((string)($personInsertResponse['data'][0]['id'] ?? ''));
        if ($personId === '') {
            redirectWithState('error', 'Employee profile was created but no person ID was returned.');
        }
    } else {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'first_name' => $firstName,
                'surname' => $surname,
                'personal_email' => $applicantEmail !== '' ? $applicantEmail : null,
                'updated_at' => gmdate('c'),
            ]
        );
    }

    $employeeRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
        $headers
    );
    $employeeRoleId = trim((string)($employeeRoleResponse['data'][0]['id'] ?? ''));
    if (!adminApplicantsIsValidUuid($employeeRoleId)) {
        redirectWithState('error', 'Employee role configuration is missing.');
    }

    $applicantRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.applicant&limit=1',
        $headers
    );
    $applicantRoleId = trim((string)($applicantRoleResponse['data'][0]['id'] ?? ''));

    $newUserId = null;
    $temporaryPassword = null;
    $accountCreated = false;
    $accountActivated = false;

    $targetUserId = '';
    if (adminApplicantsIsValidUuid($applicantUserId)) {
        $targetUserId = $applicantUserId;
    } else {
        $existingAccountResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id&email=eq.' . encodeFilter($applicantEmail) . '&limit=1',
            $headers
        );

        if (isSuccessful($existingAccountResponse)) {
            $targetUserId = trim((string)($existingAccountResponse['data'][0]['id'] ?? ''));
        }
    }

    if (adminApplicantsIsValidUuid($targetUserId)) {
        $temporaryPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);
        $authUpdateResponse = apiRequest(
            'PUT',
            $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($targetUserId),
            $headers,
            [
                'email' => $applicantEmail,
                'password' => $temporaryPassword,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $applicantName,
                    'updated_by_admin' => $adminUserId,
                    'source' => 'recruitment_add_as_employee',
                ],
            ]
        );

        if (!isSuccessful($authUpdateResponse)) {
            redirectWithState('error', 'Failed to activate employee login credentials for this applicant.');
        }

        $upsertAccountResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_accounts',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'id' => $targetUserId,
                'email' => $applicantEmail,
                'account_status' => 'active',
                'email_verified_at' => gmdate('c'),
                'must_change_password' => true,
            ]]
        );

        if (!isSuccessful($upsertAccountResponse)) {
            redirectWithState('error', 'Failed to activate account record for this applicant.');
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . rawurlencode($targetUserId) . '&is_primary=eq.true',
            array_merge($headers, ['Prefer: return=minimal']),
            ['is_primary' => false]
        );

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/user_role_assignments',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'user_id' => $targetUserId,
                'role_id' => $employeeRoleId,
                'office_id' => adminApplicantsIsValidUuid($officeId) ? $officeId : null,
                'assigned_by' => $adminUserId !== '' ? $adminUserId : null,
                'assigned_at' => gmdate('c'),
                'is_primary' => true,
                'expires_at' => null,
            ]]
        );

        if (adminApplicantsIsValidUuid($applicantRoleId)) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . rawurlencode($targetUserId)
                . '&role_id=eq.' . rawurlencode($applicantRoleId)
                . '&expires_at=is.null',
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'is_primary' => false,
                    'expires_at' => gmdate('c'),
                ]
            );
        }

        $newUserId = $targetUserId;
        $accountActivated = true;
    } else {
        $createAccountResult = adminApplicantsCreateEmployeeAccount(
            $applicantEmail,
            $applicantName,
            $officeId,
            $adminUserId,
            $supabaseUrl,
            $headers,
            $newUserId,
            $temporaryPassword
        );

        if (!(bool)($createAccountResult['ok'] ?? false)) {
            redirectWithState('error', (string)($createAccountResult['message'] ?? 'Failed to create employee account from hired applicant.'));
        }
        $accountCreated = true;
    }

    if (adminApplicantsIsValidUuid((string)$newUserId) && $personId !== '') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'user_id' => $newUserId,
                'personal_email' => $applicantEmail,
                'updated_at' => gmdate('c'),
            ]
        );
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=id,is_current,office_id,position_id,employment_status&person_id=eq.' . $personId . '&is_current=eq.true&limit=1',
        $headers
    );
    $currentEmployment = $employmentResponse['data'][0] ?? null;

    $employmentCreated = false;
    if (!is_array($currentEmployment)) {
        $employmentInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/employment_records',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'person_id' => $personId,
                'office_id' => $officeId,
                'position_id' => $positionId,
                'hire_date' => gmdate('Y-m-d'),
                'employment_status' => 'active',
                'is_current' => true,
            ]]
        );

        if (!isSuccessful($employmentInsertResponse)) {
            redirectWithState('error', 'Employee profile created but failed to initialize employment record.');
        }
        $employmentCreated = true;
    }

    if ($pdsSummary !== '') {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/application_feedback?on_conflict=application_id',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            [[
                'application_id' => $applicationId,
                'decision' => 'hired',
                'feedback_text' => json_encode([
                    'pds_summary' => $pdsSummary,
                    'career_experience' => $careerExperience,
                    'work_experience' => $workExperience,
                    'education_years' => is_numeric($educationYears) ? (float)$educationYears : null,
                    'experience_years' => is_numeric($experienceYears) ? (float)$experienceYears : null,
                    'converted_person_id' => $personId,
                    'converted_at' => gmdate('c'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'provided_by' => $adminUserId,
                'provided_at' => gmdate('c'),
            ]]
        );
    }

    $categoryResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&limit=1000',
        $headers
    );

    $categoryRows = isSuccessful($categoryResponse) ? (array)($categoryResponse['data'] ?? []) : [];
    $categoryIdByKey = [];
    foreach ($categoryRows as $categoryRow) {
        $categoryName = trim((string)($categoryRow['category_name'] ?? ''));
        $categoryId = trim((string)($categoryRow['id'] ?? ''));
        if ($categoryName === '' || $categoryId === '') {
            continue;
        }

        $normalizedKey = strtolower(preg_replace('/[^a-z0-9]+/', '', $categoryName));
        if ($normalizedKey !== '') {
            $categoryIdByKey[$normalizedKey] = $categoryId;
        }
    }

    $required201Categories = [
        'PDS',
        'SSS',
        'Pag-IBIG',
        'PhilHealth',
        'NBI',
        "Mayor's Permit",
        'Medical',
        'Drug Test',
        'Health Card',
        'Cedula',
        'Resume/CV',
    ];

    $existingDocsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id,category_id&owner_person_id=eq.' . $personId . '&limit=2000',
        $headers
    );
    $existingDocs = isSuccessful($existingDocsResponse) ? (array)($existingDocsResponse['data'] ?? []) : [];
    $existingCategoryIds = [];
    foreach ($existingDocs as $existingDoc) {
        $existingCategoryId = trim((string)($existingDoc['category_id'] ?? ''));
        if ($existingCategoryId !== '') {
            $existingCategoryIds[$existingCategoryId] = true;
        }
    }

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $initializedDir = $storageRoot . '/initialized/' . $personId;
    if (!is_dir($initializedDir) && !mkdir($initializedDir, 0775, true) && !is_dir($initializedDir)) {
        redirectWithState('error', 'Employee profile created but failed to initialize local 201 storage folder.');
    }

    $initializedDocs = 0;
    foreach ($required201Categories as $requiredCategoryName) {
        $requiredKey = strtolower(preg_replace('/[^a-z0-9]+/', '', $requiredCategoryName));
        $categoryId = (string)($categoryIdByKey[$requiredKey] ?? '');
        if ($categoryId === '' || isset($existingCategoryIds[$categoryId])) {
            continue;
        }

        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/', '-', $requiredCategoryName), '-'));
        if ($slug === '') {
            $slug = 'document';
        }

        $fileName = $slug . '-placeholder.txt';
        $localPath = $initializedDir . '/' . $fileName;
        $fileBody = "201 file placeholder initialized by Admin conversion on " . gmdate('c') . "\nCategory: " . $requiredCategoryName . "\nPerson ID: " . $personId . "\n";
        file_put_contents($localPath, $fileBody);
        $storagePath = 'initialized/' . $personId . '/' . $fileName;

        $documentInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/documents',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'owner_person_id' => $personId,
                'category_id' => $categoryId,
                'title' => $requiredCategoryName . ' - ' . $applicantName,
                'description' => 'Auto-initialized 201 file entry from hired applicant conversion.',
                'storage_bucket' => 'local_documents',
                'storage_path' => $storagePath,
                'current_version_no' => 1,
                'document_status' => 'draft',
                'uploaded_by' => $adminUserId,
            ]]
        );

        if (!isSuccessful($documentInsertResponse)) {
            continue;
        }

        $documentId = trim((string)($documentInsertResponse['data'][0]['id'] ?? ''));
        if ($documentId === '') {
            continue;
        }

        $mimeType = (string)(mime_content_type($localPath) ?: 'text/plain');
        $sizeBytes = (int)(filesize($localPath) ?: 0);
        $checksum = (string)(hash_file('sha256', $localPath) ?: '');

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/document_versions',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'document_id' => $documentId,
                'version_no' => 1,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
                'checksum_sha256' => $checksum,
                'storage_path' => $storagePath,
                'uploaded_by' => $adminUserId,
            ]]
        );

        $initializedDocs++;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'applicants',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'convert_hired_to_employee',
            'old_data' => [
                'application_status' => $applicationStatus,
            ],
            'new_data' => [
                'person_id' => $personId,
                'job_title' => $jobTitle,
                'employment_created' => $employmentCreated,
                'initialized_201_documents' => $initializedDocs,
                'application_ref_no' => (string)($applicationRow['application_ref_no'] ?? ''),
                'employee_user_id' => $newUserId,
                'account_created' => $accountCreated,
                'account_activated' => $accountActivated,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $mailResultStatus = 'skipped';
    if ($temporaryPassword !== null && $temporaryPassword !== '' && smtpConfigIsReady($smtpConfig, $mailFrom)) {
        $safeFullName = htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $safePositionTitle = htmlspecialchars($jobTitle !== '' ? $jobTitle : 'Employee', ENT_QUOTES, 'UTF-8');
        $safeOfficeName = htmlspecialchars($officeName, ENT_QUOTES, 'UTF-8');

        $welcomeHtml = '<p>Dear ' . $safeFullName . ',</p>'
            . '<p>Welcome to DA-ATI HRIS. Your employee account has been created.</p>'
            . '<p><strong>Login Credentials</strong><br>'
            . 'Email: ' . $safeEmail . '<br>'
            . 'Temporary Password: ' . $safePassword . '</p>'
            . '<p>Please sign in and change your password immediately.</p>'
            . '<p>Assigned Position: ' . $safePositionTitle . '<br>'
            . 'Division: ' . $safeOfficeName . '</p>'
            . '<p>Welcome aboard!</p>'
            . '<p>— DA-ATI HRIS Admin</p>';

        $mailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $applicantEmail,
            $applicantName,
            'Welcome to DA-ATI HRIS - Employee Account Created',
            $welcomeHtml
        );

        $mailResultStatus = isSuccessful($mailResponse) ? 'sent' : 'failed';
    }

    $successMessage = 'Hired applicant converted to employee profile successfully. 201 files initialized: ' . $initializedDocs . '.';
    if ($mailResultStatus === 'sent') {
        $successMessage .= ' Credential email sent.';
    } elseif ($mailResultStatus === 'failed') {
        $successMessage .= ' Account updated but credential email failed to send.';
    } else {
        $successMessage .= ' Account updated but credential email skipped (SMTP not configured).';
    }

    redirectWithState('success', $successMessage);
}

if ($action !== 'save_applicant_decision') {
    redirectWithState('error', 'Unknown applicants action.');
}

$applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
$decision = cleanText($_POST['decision'] ?? null) ?? '';
$decisionDate = cleanText($_POST['decision_date'] ?? null) ?? '';
$basis = cleanText($_POST['basis'] ?? null) ?? '';
$remarks = cleanText($_POST['remarks'] ?? null) ?? '';

if ($applicationId === '' || $decision === '' || $decisionDate === '' || $basis === '') {
    redirectWithState('error', 'Application, decision, decision date, and basis are required.');
}

$decisionConfig = applicantDecisionMap($decision);
if ($decisionConfig === null) {
    redirectWithState('error', 'Invalid screening decision selected.');
}

$applicationResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant_profile_id,application_ref_no,job:job_postings(title,position_id),applicant:applicant_profiles(user_id,full_name,email,resume_url,portfolio_url,training_hours_completed)&id=eq.' . $applicationId . '&limit=1',
    $headers
);

$applicationRow = $applicationResponse['data'][0] ?? null;
if (!is_array($applicationRow)) {
    redirectWithState('error', 'Application record not found.');
}

$oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
$applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');
$applicantName = (string)($applicationRow['applicant']['full_name'] ?? 'Applicant');
$applicantEmail = strtolower(trim((string)($applicationRow['applicant']['email'] ?? '')));
$applicationRefNo = trim((string)($applicationRow['application_ref_no'] ?? ''));
$jobTitle = trim((string)($applicationRow['job']['title'] ?? ''));

$autoRejectedForMissingCriteria = false;
$missingCriteria = [];
$autoRejectReason = '';
if ($decision === 'approve_for_next_stage') {
    $missingCriteria = adminApplicantsMissingCriteria($supabaseUrl, $headers, $applicationRow);
    if (!empty($missingCriteria)) {
        $autoRejectedForMissingCriteria = true;
        $decision = 'disqualify_application';
        $decisionConfig = applicantDecisionMap($decision) ?? $decisionConfig;
        $autoRejectReason = 'Missing criteria: ' . implode(', ', $missingCriteria);
        $remarks = $remarks !== '' ? ($autoRejectReason . ' | ' . $remarks) : $autoRejectReason;
    }
}

$patchResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/applications?id=eq.' . $applicationId,
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'application_status' => $decisionConfig['application_status'],
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($patchResponse)) {
    redirectWithState('error', 'Failed to save applicant decision.');
}

$notes = trim($basis . ($remarks !== '' ? ' | ' . $remarks : ''));

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_status_history',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'application_id' => $applicationId,
        'old_status' => $oldStatus,
        'new_status' => $decisionConfig['application_status'],
        'changed_by' => $adminUserId,
        'notes' => $notes,
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_feedback?on_conflict=application_id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'application_id' => $applicationId,
        'decision' => $decisionConfig['feedback_decision'],
        'feedback_text' => $remarks !== '' ? $remarks : $basis,
        'provided_by' => $adminUserId,
        'provided_at' => $decisionDate . 'T00:00:00Z',
    ]]
);

if ($applicantUserId !== '') {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $applicantUserId,
            'category' => 'recruitment',
            'title' => 'Application Screening Decision',
            'body' => $decisionConfig['notification_text'],
            'link_url' => '/hris-system/pages/applicant/applications.php',
        ]]
    );
}

$emailDeliveryStatus = 'not_sent';
$emailDeliveryDetails = '';

if ($decision === 'approve_for_next_stage' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
    if (smtpConfigIsReady($smtpConfig, $mailFrom)) {
        $templates = adminApplicantsLoadRecruitmentEmailTemplates($supabaseUrl, $headers);
        $templateKey = $oldStatus === 'submitted' ? 'passed' : 'next_stage';
        $subjectTemplate = (string)($templates[$templateKey]['subject'] ?? 'Application Update: Next Stage');
        $bodyTemplate = (string)($templates[$templateKey]['body'] ?? '');
        $replacements = [
            'applicant_name' => $applicantName,
            'job_title' => $jobTitle !== '' ? $jobTitle : 'the position you applied for',
            'application_ref_no' => $applicationRefNo,
            'remarks' => $remarks !== '' ? $remarks : $basis,
        ];

        $emailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $applicantEmail,
            $applicantName,
            adminApplicantsRenderTemplate($subjectTemplate, $replacements),
            adminApplicantsRenderTemplate($bodyTemplate, $replacements)
        );

        if (isSuccessful($emailResponse)) {
            $emailDeliveryStatus = 'sent';
        } else {
            $emailDeliveryStatus = 'failed';
            $emailDeliveryDetails = trim((string)($emailResponse['raw'] ?? ''));
        }
    } else {
        $emailDeliveryStatus = 'skipped_smtp_not_configured';
        $emailDeliveryDetails = 'SMTP config missing.';
    }
}

if ($decision === 'disqualify_application' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
    if (smtpConfigIsReady($smtpConfig, $mailFrom)) {
        $templates = adminApplicantsLoadRecruitmentEmailTemplates($supabaseUrl, $headers);
        $subjectTemplate = (string)($templates['failed']['subject'] ?? 'Application Update: Not Qualified');
        $bodyTemplate = (string)($templates['failed']['body'] ?? '');
        $replacements = [
            'applicant_name' => $applicantName,
            'job_title' => $jobTitle !== '' ? $jobTitle : 'the position you applied for',
            'application_ref_no' => $applicationRefNo,
            'remarks' => $remarks !== '' ? $remarks : $basis,
        ];

        $emailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $applicantEmail,
            $applicantName,
            adminApplicantsRenderTemplate($subjectTemplate, $replacements),
            adminApplicantsRenderTemplate($bodyTemplate, $replacements)
        );

        if (isSuccessful($emailResponse)) {
            $emailDeliveryStatus = 'sent';
            $emailDeliveryDetails = 'Failed criteria email sent.';
        } else {
            $emailDeliveryStatus = 'failed';
            $emailDeliveryDetails = trim((string)($emailResponse['raw'] ?? ''));
        }
    } else {
        $emailDeliveryStatus = 'skipped_smtp_not_configured';
        $emailDeliveryDetails = 'SMTP config missing.';
    }
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $adminUserId,
        'module_name' => 'applicants',
        'entity_name' => 'applications',
        'entity_id' => $applicationId,
        'action_name' => 'screening_decision',
        'old_data' => ['application_status' => $oldStatus],
        'new_data' => [
            'application_status' => $decisionConfig['application_status'],
            'decision' => $decisionConfig['decision_label'],
            'decision_date' => $decisionDate,
            'basis' => $basis,
            'remarks' => $remarks,
            'applicant_name' => $applicantName,
            'auto_rejected_for_missing_criteria' => $autoRejectedForMissingCriteria,
            'missing_criteria' => $missingCriteria,
            'next_stage_email_status' => $emailDeliveryStatus,
            'next_stage_email_details' => $emailDeliveryDetails,
        ],
        'ip_address' => clientIp(),
    ]]
);

$successMessage = 'Applicant screening decision saved successfully.';
if ($autoRejectedForMissingCriteria) {
    $successMessage = 'Application automatically marked as failed. ' . $autoRejectReason . '.';
}
if ($decision === 'approve_for_next_stage') {
    if ($emailDeliveryStatus === 'sent') {
        $successMessage .= ' Next-stage email notice sent to applicant.';
    } elseif ($emailDeliveryStatus === 'failed') {
        $successMessage .= ' Next-stage email delivery failed; check SMTP settings and logs.';
    } elseif ($emailDeliveryStatus === 'skipped_smtp_not_configured') {
        $successMessage .= ' Next-stage email skipped because SMTP is not configured.';
    }
}

if ($decision === 'disqualify_application') {
    if ($emailDeliveryStatus === 'sent') {
        $successMessage .= ' Failed-notice email sent to applicant.';
    } elseif ($emailDeliveryStatus === 'failed') {
        $successMessage .= ' Failed-notice email delivery failed; check SMTP settings and logs.';
    } elseif ($emailDeliveryStatus === 'skipped_smtp_not_configured') {
        $successMessage .= ' Failed-notice email skipped because SMTP is not configured.';
    }
}

redirectWithState('success', $successMessage);
