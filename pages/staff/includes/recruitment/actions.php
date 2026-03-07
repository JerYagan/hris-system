<?php

require_once dirname(__DIR__, 3) . '/admin/includes/notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

redirectWithState('error', 'Staff recruitment pages are now read-only. Decision actions are unavailable.');

if (!function_exists('staffRecruitmentNotify')) {
    function staffRecruitmentNotify(array $headers, string $supabaseUrl, string $recipientUserId, string $title, string $body): void
    {
        if (!isValidUuid($recipientUserId)) {
            return;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'recruitment',
                'title' => $title,
                'body' => $body,
                'link_url' => '/hris-system/pages/staff/recruitment.php',
            ]]
        );
    }
}

if (!function_exists('staffRecruitmentSplitName')) {
    function staffRecruitmentSplitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($part): bool => $part !== ''));
        if (count($parts) === 0) {
            return ['first_name' => 'Applicant', 'surname' => 'User'];
        }
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'surname' => 'User'];
        }

        return [
            'first_name' => $parts[0],
            'surname' => $parts[count($parts) - 1],
        ];
    }
}

if (!function_exists('staffRecruitmentEnsureEmployeeAccount')) {
    function staffRecruitmentEnsureEmployeeAccount(
        string $supabaseUrl,
        array $headers,
        string $applicantUserId,
        string $applicantProfileId,
        string $email,
        string $fullName,
        string $actorUserId
    ): array {
        $normalizedEmail = strtolower(trim($email));
        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'message' => 'Applicant email is invalid for account creation.',
            ];
        }

        $resolvedUserId = isValidUuid($applicantUserId) ? $applicantUserId : '';
        if ($resolvedUserId !== '') {
            $existingAccountResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/user_accounts?select=id&id=eq.' . rawurlencode($resolvedUserId) . '&limit=1',
                $headers
            );

            if (!isSuccessful($existingAccountResponse) || empty((array)($existingAccountResponse['data'] ?? []))) {
                $resolvedUserId = '';
            }
        }

        $tempPassword = 'Temp#' . substr(bin2hex(random_bytes(6)), 0, 10);
        $isNewAccount = false;

        if ($resolvedUserId === '') {
            $createAuth = apiRequest(
                'POST',
                $supabaseUrl . '/auth/v1/admin/users',
                $headers,
                [
                    'email' => $normalizedEmail,
                    'password' => $tempPassword,
                    'email_confirm' => true,
                    'user_metadata' => [
                        'full_name' => $fullName,
                        'created_by_admin' => $actorUserId,
                    ],
                ]
            );

            if (!isSuccessful($createAuth)) {
                $raw = strtolower((string)($createAuth['raw'] ?? ''));
                if (str_contains($raw, 'already') || str_contains($raw, 'exists')) {
                    return [
                        'ok' => false,
                        'message' => 'Applicant email already exists in authentication but is not linked. Resolve account linkage first.',
                    ];
                }

                return [
                    'ok' => false,
                    'message' => 'Failed to create authentication account for hired applicant.',
                ];
            }

            $resolvedUserId = (string)($createAuth['data']['id'] ?? '');
            if (!isValidUuid($resolvedUserId)) {
                return [
                    'ok' => false,
                    'message' => 'Invalid auth response while creating applicant employee account.',
                ];
            }

            $createAccount = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/user_accounts',
                array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
                [[
                    'id' => $resolvedUserId,
                    'email' => $normalizedEmail,
                    'account_status' => 'active',
                    'email_verified_at' => gmdate('c'),
                    'must_change_password' => true,
                ]]
            );

            if (!isSuccessful($createAccount)) {
                return [
                    'ok' => false,
                    'message' => 'Failed to create user account row for hired applicant.',
                ];
            }

            if (isValidUuid($applicantProfileId)) {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/applicant_profiles?id=eq.' . rawurlencode($applicantProfileId),
                    array_merge($headers, ['Prefer: return=minimal']),
                    [
                        'user_id' => $resolvedUserId,
                        'email' => $normalizedEmail,
                    ]
                );
            }

            $isNewAccount = true;
        } else {
            $resetAuth = apiRequest(
                'PUT',
                $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($resolvedUserId),
                $headers,
                ['password' => $tempPassword]
            );

            if (!isSuccessful($resetAuth)) {
                return [
                    'ok' => false,
                    'message' => 'Failed to reset hired applicant credentials for onboarding email.',
                ];
            }

            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($resolvedUserId),
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'email' => $normalizedEmail,
                    'account_status' => 'active',
                    'failed_login_count' => 0,
                    'lockout_until' => null,
                    'must_change_password' => true,
                ]
            );
        }

        return [
            'ok' => true,
            'user_id' => $resolvedUserId,
            'temporary_password' => $tempPassword,
            'is_new_account' => $isNewAccount,
        ];
    }
}

if (!function_exists('staffRecruitmentDecisionMap')) {
    function staffRecruitmentDecisionMap(string $decision): ?array
    {
        $decisionKey = strtolower(trim($decision));

        if ($decisionKey === 'approve_for_next_stage') {
            return [
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

if (!function_exists('staffRecruitmentLoadEmailTemplates')) {
    function staffRecruitmentLoadEmailTemplates(string $supabaseUrl, array $headers): array
    {
        $defaults = [
            'submitted' => ['subject' => '', 'body' => ''],
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

        foreach (['submitted', 'passed', 'failed', 'next_stage'] as $key) {
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

if (!function_exists('staffRecruitmentRenderTemplate')) {
    function staffRecruitmentRenderTemplate(string $template, array $replacements): string
    {
        $rendered = $template;
        foreach ($replacements as $key => $value) {
            $rendered = str_replace('{' . $key . '}', (string)$value, $rendered);
        }

        return $rendered;
    }
}

if (!function_exists('staffRecruitmentExtractStructuredInputs')) {
    function staffRecruitmentExtractStructuredInputs(string $feedbackText): array
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
            'education_years' => $decoded['education_years'] ?? $decoded['years_in_college'] ?? null,
            'training_hours' => $decoded['training_hours'] ?? $decoded['hours_of_training'] ?? null,
            'experience_years' => $decoded['experience_years'] ?? $decoded['years_of_experience'] ?? null,
        ];
    }
}

if (!function_exists('staffRecruitmentEstimateSignalInputs')) {
    function staffRecruitmentEstimateSignalInputs(array $application, array $documents, array $interviews): array
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

if (!function_exists('staffRecruitmentNormalizeEligibilityTokens')) {
    function staffRecruitmentNormalizeEligibilityTokens(string $value): array
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

if (!function_exists('staffRecruitmentEligibilityMatchesAny')) {
    function staffRecruitmentEligibilityMatchesAny(string $requiredEligibility, string $actualEligibility): bool
    {
        $requiredTokens = staffRecruitmentNormalizeEligibilityTokens($requiredEligibility);
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

if (!function_exists('staffRecruitmentDetermineMissingCriteria')) {
    function staffRecruitmentDetermineMissingCriteria(string $supabaseUrl, array $headers, array $applicationRow): array
    {
        $applicationId = cleanText($applicationRow['id'] ?? null) ?? '';
        if (!isValidUuid($applicationId)) {
            return [];
        }

        $job = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];
        $positionTitle = cleanText($job['title'] ?? null) ?? '';
        $criteria = function_exists('staffApplicantEvaluationResolveCriteria')
            ? staffApplicantEvaluationResolveCriteria($supabaseUrl, $headers, $positionTitle)
            : [
                'eligibility' => 'career service sub professional',
                'minimum_education_years' => 2,
                'minimum_training_hours' => 4,
                'minimum_experience_years' => 1,
            ];

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
            $supabaseUrl . '/rest/v1/application_documents?select=document_type&application_id=eq.' . rawurlencode($applicationId) . '&limit=500',
            $headers
        );
        $documents = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

        $interviewsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/application_interviews?select=result&application_id=eq.' . rawurlencode($applicationId) . '&order=scheduled_at.asc&limit=200',
            $headers
        );
        $interviews = isSuccessful($interviewsResponse) ? (array)($interviewsResponse['data'] ?? []) : [];

        $structured = staffRecruitmentExtractStructuredInputs($feedbackText);
        $signals = staffRecruitmentEstimateSignalInputs($applicationRow, $documents, $interviews);

        $eligibilityInput = strtolower(trim((string)($structured['eligibility'] ?? $signals['eligibility'] ?? 'n/a')));
        $educationYears = (float)($structured['education_years'] ?? $signals['education_years'] ?? 0);
        $trainingHours = (float)($structured['training_hours'] ?? $signals['training_hours'] ?? 0);
        $experienceYears = (float)($structured['experience_years'] ?? $signals['experience_years'] ?? 0);

        $requiredEligibility = (string)($criteria['eligibility'] ?? 'career service sub professional');
        $requiredEducationYears = (float)($criteria['minimum_education_years'] ?? 2);
        $requiredTrainingHours = (float)($criteria['minimum_training_hours'] ?? 4);
        $requiredExperienceYears = (float)($criteria['minimum_experience_years'] ?? 1);

        $missing = [];
        if (!staffRecruitmentEligibilityMatchesAny($requiredEligibility, $eligibilityInput)) {
            $missing[] = 'eligibility';
        }
        if ($educationYears < $requiredEducationYears) {
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

if ($action === 'create_job_posting') {
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $employmentType = strtolower((string)(cleanText($_POST['employment_type'] ?? null) ?? ''));
    $requiredDocumentsRaw = $_POST['required_documents'] ?? [];
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if ($title === '' || $description === '' || $officeId === '' || $positionId === '' || $openDate === '' || $closeDate === '') {
        redirectWithState('error', 'Title, office, position, description, open date, and close date are required.');
    }

    if (!isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Selected office or position is invalid.');
    }

    if (!in_array($employmentType, ['permanent', 'contractual'], true)) {
        redirectWithState('error', 'Please select a valid employment type.');
    }

    $positionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_positions?select=id,employment_classification'
        . '&id=eq.' . rawurlencode($positionId)
        . '&limit=1',
        $headers
    );
    $positionRow = isSuccessful($positionResponse) ? ($positionResponse['data'][0] ?? null) : null;
    if (!is_array($positionRow)) {
        redirectWithState('error', 'Selected position was not found.');
    }

    $classification = strtolower((string)(cleanText($positionRow['employment_classification'] ?? null) ?? ''));
    $classificationType = in_array($classification, ['regular', 'coterminous'], true) ? 'permanent' : 'contractual';
    if ($classificationType !== $employmentType) {
        redirectWithState('error', 'Selected position employment type does not match your chosen employment type.');
    }

    $allowedRequirementKeys = [
        'pds' => 'PDS',
        'wes' => 'WES',
        'eligibility_csc_prc' => 'Eligibility (CSC/PRC)',
        'transcript_of_records' => 'Transcript of Records',
    ];

    $requiredDocumentKeys = [];
    if (is_array($requiredDocumentsRaw)) {
        foreach ($requiredDocumentsRaw as $requirementKey) {
            $key = strtolower(trim((string)$requirementKey));
            if ($key === '' || !isset($allowedRequirementKeys[$key])) {
                continue;
            }

            $requiredDocumentKeys[$key] = $allowedRequirementKeys[$key];
        }
    }

    if (empty($requiredDocumentKeys)) {
        $requiredDocumentKeys = $allowedRequirementKeys;
    }

    if (!in_array($postingStatus, ['draft', 'published', 'closed'], true)) {
        $postingStatus = 'draft';
    }

    if (strtotime($closeDate) < strtotime($openDate)) {
        redirectWithState('error', 'Close date must be on or after open date.');
    }

    $insertPayload = [[
        'office_id' => $officeId,
        'position_id' => $positionId,
        'title' => $title,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'required_documents' => array_values($requiredDocumentKeys),
        'posting_status' => $postingStatus,
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'published_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
    ]];

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/job_postings',
        array_merge($headers, ['Prefer: return=representation']),
        $insertPayload
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to create job posting.');
    }

    $createdPostingId = cleanText($insertResponse['data'][0]['id'] ?? null) ?? '';
    if (isValidUuid($createdPostingId)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
                'module_name' => 'recruitment',
                'entity_name' => 'job_postings',
                'entity_id' => $createdPostingId,
                'action_name' => 'create_job_posting',
                'old_data' => null,
                'new_data' => $insertPayload[0],
                'ip_address' => clientIp(),
            ]]
        );
    }

    $recipientUserIds = [];
    $assignmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)'
        . '&office_id=eq.' . rawurlencode($officeId)
        . '&role.role_key=in.' . rawurlencode('(staff,hr_officer,supervisor,admin)')
        . '&limit=5000',
        $headers
    );

    if (isSuccessful($assignmentResponse)) {
        foreach ((array)($assignmentResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            if (!isValidUuid($userId)) {
                continue;
            }
            $recipientUserIds[strtolower($userId)] = $userId;
        }
    }

    if (empty($recipientUserIds) && isValidUuid((string)$staffUserId)) {
        $recipientUserIds[strtolower((string)$staffUserId)] = (string)$staffUserId;
    }

    foreach (array_values($recipientUserIds) as $recipientUserId) {
        staffRecruitmentNotify(
            $headers,
            $supabaseUrl,
            (string)$recipientUserId,
            'New Job Posting Created',
            'A new job posting has been created: ' . $title . '.'
        );
    }

    redirectWithState('success', 'Job posting created successfully.');
}

if ($action === 'add_hired_applicant_as_employee') {
    if (strtolower((string)($staffRoleKey ?? '')) !== 'admin') {
        redirectWithState('error', 'Final hiring conversion is Admin-only.');
    }

    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid hired applicant selection.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,job_posting_id,applicant:applicant_profiles(id,full_name,email,user_id,mobile_no,current_address),job:job_postings(id,office_id,position_id,title)'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );
    $applicationRow = isSuccessful($applicationResponse) ? ($applicationResponse['data'][0] ?? null) : null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Hired application record not found.');
    }

    $statusRaw = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
    if ($statusRaw !== 'hired') {
        redirectWithState('error', 'Only hired applicants can be added as employees.');
    }

    $jobRow = is_array($applicationRow['job'] ?? null) ? (array)$applicationRow['job'] : [];
    $officeId = cleanText($jobRow['office_id'] ?? null) ?? '';
    $positionId = cleanText($jobRow['position_id'] ?? null) ?? '';
    $postingTitle = cleanText($jobRow['title'] ?? null) ?? 'Job Posting';
    if (!isValidUuid($officeId) || !isValidUuid($positionId)) {
        redirectWithState('error', 'Selected hired applicant has incomplete posting assignment.');
    }

    $isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
    if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strtolower((string)$staffOfficeId) !== strtolower($officeId)) {
        redirectWithState('error', 'You are not allowed to add employees outside your office scope.');
    }

    $applicantRow = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
    $applicantProfileId = cleanText($applicantRow['id'] ?? null) ?? '';
    $applicantUserId = cleanText($applicantRow['user_id'] ?? null) ?? '';
    $fullName = cleanText($applicantRow['full_name'] ?? null) ?? 'Applicant User';
    $email = cleanText($applicantRow['email'] ?? null);
    $mobileNo = cleanText($applicantRow['mobile_no'] ?? null);

    $ensureAccount = staffRecruitmentEnsureEmployeeAccount(
        $supabaseUrl,
        $headers,
        $applicantUserId,
        $applicantProfileId,
        (string)$email,
        $fullName,
        (string)$staffUserId
    );

    if (!(bool)($ensureAccount['ok'] ?? false)) {
        redirectWithState('error', (string)($ensureAccount['message'] ?? 'Unable to provision employee account.'));
    }

    $applicantUserId = (string)($ensureAccount['user_id'] ?? '');
    $temporaryPassword = (string)($ensureAccount['temporary_password'] ?? '');
    $isNewAccount = (bool)($ensureAccount['is_new_account'] ?? false);

    if (!isValidUuid($applicantUserId)) {
        redirectWithState('error', 'Applicant account is invalid for employee creation.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,user_id,surname,first_name,personal_email,mobile_no'
        . '&user_id=eq.' . rawurlencode($applicantUserId)
        . '&limit=1',
        $headers
    );
    $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;

    $personId = '';
    if (is_array($personRow)) {
        $personId = cleanText($personRow['id'] ?? null) ?? '';
    }

    if (!isValidUuid($personId)) {
        $nameParts = staffRecruitmentSplitName($fullName);
        $insertPeopleResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/people',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'user_id' => $applicantUserId,
                'surname' => (string)($nameParts['surname'] ?? 'User'),
                'first_name' => (string)($nameParts['first_name'] ?? 'Applicant'),
                'personal_email' => $email,
                'mobile_no' => $mobileNo,
            ]]
        );

        if (!isSuccessful($insertPeopleResponse)) {
            redirectWithState('error', 'Failed to create person record for hired applicant.');
        }

        $personId = cleanText($insertPeopleResponse['data'][0]['id'] ?? null) ?? '';
        if (!isValidUuid($personId)) {
            redirectWithState('error', 'Created person record is invalid.');
        }
    } else {
        $existingPersonEmail = cleanText($personRow['personal_email'] ?? null) ?? '';
        $existingPersonMobile = cleanText($personRow['mobile_no'] ?? null) ?? '';
        $peoplePatch = [];
        if ($existingPersonEmail === '' && $email !== null && $email !== '') {
            $peoplePatch['personal_email'] = $email;
        }
        if ($existingPersonMobile === '' && $mobileNo !== null && $mobileNo !== '') {
            $peoplePatch['mobile_no'] = $mobileNo;
        }
        if (!empty($peoplePatch)) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
                array_merge($headers, ['Prefer: return=minimal']),
                $peoplePatch
            );
        }
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person_id,is_current'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&is_current=eq.true&limit=1',
        $headers
    );
    $existingEmployment = isSuccessful($employmentResponse) ? ($employmentResponse['data'][0] ?? null) : null;

    if (is_array($existingEmployment)) {
        redirectWithState('success', 'Applicant is already registered as an active employee.');
    }

    $insertEmploymentResponse = apiRequest(
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

    if (!isSuccessful($insertEmploymentResponse)) {
        redirectWithState('error', 'Failed to create employment record for hired applicant.');
    }

    $employmentId = cleanText($insertEmploymentResponse['data'][0]['id'] ?? null) ?? '';

    $employeeRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.employee&limit=1',
        $headers
    );
    $employeeRoleRow = isSuccessful($employeeRoleResponse) ? ($employeeRoleResponse['data'][0] ?? null) : null;
    $employeeRoleId = is_array($employeeRoleRow) ? (cleanText($employeeRoleRow['id'] ?? null) ?? '') : '';

    if (isValidUuid($employeeRoleId)) {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/user_role_assignments?user_id=eq.' . rawurlencode($applicantUserId) . '&is_primary=eq.true',
            array_merge($headers, ['Prefer: return=minimal']),
            ['is_primary' => false]
        );

        $roleAssignmentCheckResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=id'
            . '&user_id=eq.' . rawurlencode($applicantUserId)
            . '&role_id=eq.' . rawurlencode($employeeRoleId)
            . '&limit=1',
            $headers
        );

        $existingRoleAssignment = isSuccessful($roleAssignmentCheckResponse) ? ($roleAssignmentCheckResponse['data'][0] ?? null) : null;
        if (is_array($existingRoleAssignment)) {
            $assignmentId = cleanText($existingRoleAssignment['id'] ?? null) ?? '';
            if (isValidUuid($assignmentId)) {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/user_role_assignments?id=eq.' . rawurlencode($assignmentId),
                    array_merge($headers, ['Prefer: return=minimal']),
                    [
                        'office_id' => $officeId,
                        'is_primary' => true,
                        'assigned_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
                        'assigned_at' => gmdate('c'),
                        'expires_at' => null,
                    ]
                );
            }
        } else {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/user_role_assignments',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'user_id' => $applicantUserId,
                    'role_id' => $employeeRoleId,
                    'office_id' => $officeId,
                    'assigned_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
                    'assigned_at' => gmdate('c'),
                    'is_primary' => true,
                ]]
            );
        }
    }

    $smtpConfig = [
        'host' => cleanText($_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? null)) ?? '',
        'port' => (int)(cleanText($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? null)) ?? '587'),
        'username' => cleanText($_ENV['SMTP_USERNAME'] ?? ($_SERVER['SMTP_USERNAME'] ?? null)) ?? '',
        'password' => (string)($_ENV['SMTP_PASSWORD'] ?? ($_SERVER['SMTP_PASSWORD'] ?? '')),
        'encryption' => strtolower((string)(cleanText($_ENV['SMTP_ENCRYPTION'] ?? ($_SERVER['SMTP_ENCRYPTION'] ?? null)) ?? 'tls')),
        'auth' => (string)(cleanText($_ENV['SMTP_AUTH'] ?? ($_SERVER['SMTP_AUTH'] ?? null)) ?? '1'),
    ];
    $mailFrom = cleanText($_ENV['MAIL_FROM'] ?? ($_SERVER['MAIL_FROM'] ?? null)) ?? '';
    $mailFromName = cleanText($_ENV['MAIL_FROM_NAME'] ?? ($_SERVER['MAIL_FROM_NAME'] ?? null)) ?? 'DA HRIS';
    $resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
    $smtpConfig = (array)($resolvedMail['smtp'] ?? $smtpConfig);
    $mailFrom = (string)($resolvedMail['from'] ?? $mailFrom);
    $mailFromName = (string)($resolvedMail['from_name'] ?? $mailFromName);

    $welcomeEmailStatus = 'skipped';
    if (smtpConfigIsReady($smtpConfig, $mailFrom) && $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $safePosting = htmlspecialchars($postingTitle, ENT_QUOTES, 'UTF-8');

        $welcomeHtml = '<p>Hello ' . $safeName . ',</p>'
            . '<p>Welcome to DA-ATI HRIS. Your employee access is now active.</p>'
            . '<p><strong>Account Credentials</strong><br>'
            . 'Email: ' . $safeEmail . '<br>'
            . 'Temporary Password: ' . $safePassword . '</p>'
            . '<p>Please sign in and change your password immediately.</p>'
            . '<p>Hiring Source: ' . $safePosting . '</p>'
            . '<p>— DA-ATI HRIS</p>';

        $welcomeMailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            (string)$email,
            $fullName,
            'Welcome to DA-ATI HRIS - Employee Account Credentials',
            $welcomeHtml
        );

        $welcomeEmailStatus = isSuccessful($welcomeMailResponse) ? 'sent' : 'failed';
    }

    staffRecruitmentNotify(
        $headers,
        $supabaseUrl,
        $applicantUserId,
        'Employment Profile Created',
        'You have been added as an employee from your hired application for ' . $postingTitle . '.'
    );

    $adminAssignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,role:roles!inner(role_key)'
        . '&role.role_key=eq.admin'
        . '&is_primary=eq.true'
        . '&expires_at=is.null'
        . '&limit=2000',
        $headers
    );

    $adminRecipientIds = [];
    if (isSuccessful($adminAssignmentsResponse)) {
        foreach ((array)($adminAssignmentsResponse['data'] ?? []) as $assignmentRow) {
            $recipientUserId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            if (isValidUuid($recipientUserId)) {
                $adminRecipientIds[strtolower($recipientUserId)] = $recipientUserId;
            }
        }
    }

    if (empty($adminRecipientIds) && isValidUuid((string)$staffUserId)) {
        $adminRecipientIds[strtolower((string)$staffUserId)] = (string)$staffUserId;
    }

    foreach (array_values($adminRecipientIds) as $recipientUserId) {
        staffRecruitmentNotify(
            $headers,
            $supabaseUrl,
            (string)$recipientUserId,
            'Employee account onboarding completed',
            'Hired applicant ' . $fullName . ' was added as employee. Credentials email: ' . $welcomeEmailStatus . '.'
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'employment_records',
            'entity_id' => isValidUuid($employmentId) ? $employmentId : null,
            'action_name' => 'add_hired_applicant_as_employee',
            'old_data' => null,
            'new_data' => [
                'application_id' => $applicationId,
                'person_id' => $personId,
                'user_id' => $applicantUserId,
                'office_id' => $officeId,
                'position_id' => $positionId,
                'new_account_created' => $isNewAccount,
                'welcome_email_status' => $welcomeEmailStatus,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Hired applicant has been added as employee successfully.');
}

if ($action === 'unarchive_job_posting') {
    if (strtolower((string)($staffRoleKey ?? '')) !== 'admin') {
        redirectWithState('error', 'Job status controls are Admin-only.');
    }

    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';
    if (!isValidUuid($postingId)) {
        redirectWithState('error', 'Invalid archived posting selected.');
    }

    $isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
    $scopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
        ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        : '';

    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status&id=eq.' . rawurlencode($postingId) . $scopeFilter . '&limit=1',
        $headers
    );

    $postingRow = isSuccessful($postingResponse) ? ($postingResponse['data'][0] ?? null) : null;
    if (!is_array($postingRow)) {
        redirectWithState('error', 'Archived posting not found or outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($postingRow['posting_status'] ?? null) ?? 'draft'));
    if ($oldStatus !== 'archived') {
        redirectWithState('error', 'Only archived postings can be restored from this section.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/job_postings?id=eq.' . rawurlencode($postingId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'posting_status' => 'draft',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to unarchive job posting.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $postingId,
            'action_name' => 'unarchive_job_posting',
            'old_data' => ['posting_status' => 'archived'],
            'new_data' => ['posting_status' => 'draft'],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Job posting has been restored to active listings.');
}

if ($action === 'save_applicant_decision') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $decision = cleanText($_POST['decision'] ?? null) ?? '';
    $decisionDate = cleanText($_POST['decision_date'] ?? null) ?? '';
    $basis = cleanText($_POST['basis'] ?? null) ?? '';
    $remarks = cleanText($_POST['remarks'] ?? null) ?? '';

    if (!isValidUuid($applicationId) || $decision === '' || $decisionDate === '' || $basis === '') {
        redirectWithState('error', 'Application, decision, decision date, and basis are required.');
    }

    $decisionConfig = staffRecruitmentDecisionMap($decision);
    if ($decisionConfig === null) {
        redirectWithState('error', 'Invalid screening decision selected.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_ref_no,application_status,job_posting_id,applicant:applicant_profiles(user_id,full_name,email,resume_url,portfolio_url),job:job_postings(title,position_id)'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );
    $applicationRow = isSuccessful($applicationResponse) ? ($applicationResponse['data'][0] ?? null) : null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $jobPostingId = cleanText($applicationRow['job_posting_id'] ?? null) ?? '';
    if (!isValidUuid($jobPostingId)) {
        redirectWithState('error', 'Application has invalid posting reference.');
    }

    $isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
    if (!$isAdminScope && isValidUuid((string)$staffOfficeId)) {
        $scopeResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/job_postings?select=id,office_id'
            . '&id=eq.' . rawurlencode($jobPostingId)
            . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
            . '&limit=1',
            $headers
        );
        $scopeRow = isSuccessful($scopeResponse) ? ($scopeResponse['data'][0] ?? null) : null;
        if (!is_array($scopeRow)) {
            redirectWithState('error', 'You are not allowed to process applications outside your office scope.');
        }
    }

    $oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
    $autoRejectedForMissingCriteria = false;
    $missingCriteria = [];
    $autoRejectReason = '';

    if ($decision === 'approve_for_next_stage') {
        $missingCriteria = staffRecruitmentDetermineMissingCriteria($supabaseUrl, $headers, $applicationRow);
        if (!empty($missingCriteria)) {
            $autoRejectedForMissingCriteria = true;
            $decision = 'disqualify_application';
            $decisionConfig = staffRecruitmentDecisionMap($decision) ?? $decisionConfig;
            $autoRejectReason = 'Missing criteria: ' . implode(', ', $missingCriteria);
            $remarks = $remarks !== ''
                ? $autoRejectReason . ' | ' . $remarks
                : $autoRejectReason;
        }
    }

    $nextStatusMap = [
        'submitted' => 'screening',
        'screening' => 'shortlisted',
        'shortlisted' => 'interview',
        'interview' => 'offer',
        'offer' => 'hired',
    ];

    if ($decision === 'approve_for_next_stage') {
        $newStatus = $nextStatusMap[$oldStatus] ?? '';
        if ($newStatus === '') {
            redirectWithState('error', 'Cannot approve application from current status: ' . $oldStatus . '.');
        }
    } else {
        $newStatus = (string)($decisionConfig['application_status'] ?? 'submitted');
    }

    if ($newStatus === 'hired' && strtolower((string)($staffRoleKey ?? '')) !== 'admin') {
        redirectWithState('error', 'Final hiring decision is Admin-only.');
    }

    if (!canTransitionStatus('applications', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid status transition from ' . $oldStatus . ' to ' . $newStatus . '.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'application_status' => $newStatus,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to save applicant screening decision.');
    }

    $notes = trim($basis . ($remarks !== '' ? ' | ' . $remarks : ''));

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_status_history',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
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
            'provided_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'provided_at' => $decisionDate . 'T00:00:00Z',
        ]]
    );

    $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null) ?? '';
    if (isValidUuid($applicantUserId)) {
        staffRecruitmentNotify(
            $headers,
            $supabaseUrl,
            $applicantUserId,
            'Application Screening Decision',
            (string)($decisionConfig['notification_text'] ?? 'Your application has a new screening update.')
        );
    }

    $applicantName = cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant';
    $applicantEmail = strtolower((string)(cleanText($applicationRow['applicant']['email'] ?? null) ?? ''));
    $applicationRefNo = cleanText($applicationRow['application_ref_no'] ?? null) ?? '';
    $jobTitle = cleanText($applicationRow['job']['title'] ?? null) ?? 'Job Posting';

    $emailTemplateKey = '';
    if ($decision === 'disqualify_application') {
        $emailTemplateKey = 'failed';
    } elseif ($decision === 'approve_for_next_stage') {
        $emailTemplateKey = $oldStatus === 'submitted' ? 'passed' : 'next_stage';
    }

    if ($emailTemplateKey !== '' && $applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        $smtpConfig = [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'auth' => '1',
        ];
        $mailFrom = '';
        $mailFromName = 'DA HRIS';
        $resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
        $smtpResolved = (array)($resolvedMail['smtp'] ?? []);
        $mailFromResolved = (string)($resolvedMail['from'] ?? '');
        $mailFromNameResolved = (string)($resolvedMail['from_name'] ?? 'DA HRIS');

        if (smtpConfigIsReady($smtpResolved, $mailFromResolved)) {
            $templates = staffRecruitmentLoadEmailTemplates($supabaseUrl, $headers);
            $subjectTemplate = (string)($templates[$emailTemplateKey]['subject'] ?? 'Application Update');
            $bodyTemplate = (string)($templates[$emailTemplateKey]['body'] ?? '');
            $replacements = [
                'applicant_name' => $applicantName,
                'job_title' => $jobTitle,
                'application_ref_no' => $applicationRefNo,
                'remarks' => $remarks !== '' ? $remarks : $basis,
            ];

            $emailResponse = smtpSendTransactionalEmail(
                $smtpResolved,
                $mailFromResolved,
                $mailFromNameResolved,
                $applicantEmail,
                $applicantName,
                staffRecruitmentRenderTemplate($subjectTemplate, $replacements),
                staffRecruitmentRenderTemplate($bodyTemplate, $replacements)
            );

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/activity_logs',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
                    'module_name' => 'recruitment',
                    'entity_name' => 'applications',
                    'entity_id' => $applicationId,
                    'action_name' => 'email_' . $emailTemplateKey . '_notification',
                    'old_data' => null,
                    'new_data' => [
                        'recipient_email' => $applicantEmail,
                        'status_code' => (int)($emailResponse['status'] ?? 0),
                        'template_key' => $emailTemplateKey,
                    ],
                    'ip_address' => clientIp(),
                ]]
            );
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'save_applicant_decision',
            'old_data' => ['application_status' => $oldStatus],
            'new_data' => [
                'application_status' => $newStatus,
                'decision' => $decisionConfig['decision_label'],
                'decision_date' => $decisionDate,
                'basis' => $basis,
                'remarks' => $remarks,
                'auto_rejected_for_missing_criteria' => $autoRejectedForMissingCriteria,
                'missing_criteria' => $missingCriteria,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if ($autoRejectedForMissingCriteria) {
        redirectWithState('success', 'Application automatically marked as failed. ' . $autoRejectReason . '.');
    }

    redirectWithState('success', 'Applicant screening decision saved successfully.');
}

if ($action !== 'update_posting_status') {
    redirectWithState('error', 'Unknown recruitment action.');
}

if (strtolower((string)($staffRoleKey ?? '')) !== 'admin') {
    redirectWithState('error', 'Job status controls are Admin-only.');
}

$postingId = cleanText($_POST['posting_id'] ?? null) ?? '';
$newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
$statusNotes = cleanText($_POST['status_notes'] ?? null);

if (!isValidUuid($postingId)) {
    redirectWithState('error', 'Invalid job posting selected.');
}

if (!in_array($newStatus, ['draft', 'published', 'closed', 'archived'], true)) {
    redirectWithState('error', 'Invalid posting status selected.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$scopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status,office_id&id=eq.' . rawurlencode($postingId) . $scopeFilter . '&limit=1',
    $headers
);

$postingRow = isSuccessful($postingResponse) ? ($postingResponse['data'][0] ?? null) : null;
if (!is_array($postingRow)) {
    redirectWithState('error', 'Posting not found or outside your office scope.');
}

$oldStatus = strtolower((string)(cleanText($postingRow['posting_status'] ?? null) ?? 'draft'));

$canTransitionPosting = static function (string $old, string $new): bool {
    if ($old === $new) {
        return true;
    }

    $rules = [
        'draft' => ['published', 'archived'],
        'published' => ['closed', 'archived'],
        'closed' => ['archived'],
        'archived' => ['draft'],
    ];

    return isset($rules[$old]) && in_array($new, $rules[$old], true);
};

if (!$canTransitionPosting($oldStatus, $newStatus)) {
    redirectWithState('error', 'Invalid posting transition from ' . $oldStatus . ' to ' . $newStatus . '.');
}

$patchResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/job_postings?id=eq.' . rawurlencode($postingId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'posting_status' => $newStatus,
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($patchResponse)) {
    redirectWithState('error', 'Failed to update posting status.');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'recruitment',
        'entity_name' => 'job_postings',
        'entity_id' => $postingId,
        'action_name' => 'update_posting_status',
        'old_data' => ['posting_status' => $oldStatus],
        'new_data' => ['posting_status' => $newStatus, 'notes' => $statusNotes],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Job posting status updated to ' . $newStatus . '.');