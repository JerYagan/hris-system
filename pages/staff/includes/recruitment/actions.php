<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

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
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    if (!isValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid hired applicant selection.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,job_posting_id,applicant:applicant_profiles(full_name,email,user_id),job:job_postings(id,office_id,position_id,title)'
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
    $applicantUserId = cleanText($applicantRow['user_id'] ?? null) ?? '';
    $fullName = cleanText($applicantRow['full_name'] ?? null) ?? 'Applicant User';
    $email = cleanText($applicantRow['email'] ?? null);

    if (!isValidUuid($applicantUserId)) {
        redirectWithState('error', 'Applicant account is invalid for employee creation.');
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,user_id,surname,first_name'
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
            ]]
        );

        if (!isSuccessful($insertPeopleResponse)) {
            redirectWithState('error', 'Failed to create person record for hired applicant.');
        }

        $personId = cleanText($insertPeopleResponse['data'][0]['id'] ?? null) ?? '';
        if (!isValidUuid($personId)) {
            redirectWithState('error', 'Created person record is invalid.');
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
        if (!is_array($existingRoleAssignment)) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/user_role_assignments',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'user_id' => $applicantUserId,
                    'role_id' => $employeeRoleId,
                    'office_id' => $officeId,
                    'assigned_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
                    'is_primary' => false,
                ]]
            );
        }
    }

    staffRecruitmentNotify(
        $headers,
        $supabaseUrl,
        $applicantUserId,
        'Employment Profile Created',
        'You have been added as an employee from your hired application for ' . $postingTitle . '.'
    );

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
                'office_id' => $officeId,
                'position_id' => $positionId,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Hired applicant has been added as employee successfully.');
}

if ($action === 'unarchive_job_posting') {
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
        . '/rest/v1/applications?select=id,application_status,job_posting_id,applicant:applicant_profiles(user_id,full_name)'
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
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Applicant screening decision saved successfully.');
}

if ($action !== 'update_posting_status') {
    redirectWithState('error', 'Unknown recruitment action.');
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