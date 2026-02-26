<?php

require_once __DIR__ . '/../../../admin/includes/notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if (!function_exists('staffApplicantTrackingNotify')) {
    function staffApplicantTrackingNotify(array $headers, string $supabaseUrl, string $recipientUserId, string $title, string $body, string $linkUrl): void
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
                'link_url' => $linkUrl,
            ]]
        );
    }
}

if (!function_exists('staffApplicantTrackingSplitName')) {
    function staffApplicantTrackingSplitName(string $fullName): array
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

if (!function_exists('staffApplicantTrackingBuildUtcTimestamp')) {
    function staffApplicantTrackingBuildUtcTimestamp(string $dateValue, string $timeValue): ?string
    {
        $date = trim($dateValue);
        $time = trim($timeValue);
        if ($date === '' || $time === '') {
            return null;
        }

        $manilaTz = new DateTimeZone('Asia/Manila');
        $timeFormats = ['Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d g:i A', 'Y-m-d g:iA'];
        foreach ($timeFormats as $format) {
            $dateTime = DateTimeImmutable::createFromFormat($format, $date . ' ' . $time, $manilaTz);
            if ($dateTime instanceof DateTimeImmutable) {
                return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
            }
        }

        return null;
    }
}

if (!function_exists('staffApplicantTrackingFormatManilaDateTime')) {
    function staffApplicantTrackingFormatManilaDateTime(string $utcIso): string
    {
        $raw = trim($utcIso);
        if ($raw === '') {
            return '-';
        }

        try {
            $dateTime = new DateTimeImmutable($raw, new DateTimeZone('UTC'));
            return $dateTime->setTimezone(new DateTimeZone('Asia/Manila'))->format('M d, Y h:i A') . ' (UTC+8)';
        } catch (Throwable $_error) {
            return '-';
        }
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
$resolvedMailConfig = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
$smtpConfig = (array)($resolvedMailConfig['smtp'] ?? $smtpConfig);
$mailFrom = (string)($resolvedMailConfig['from'] ?? $mailFrom);
$mailFromName = (string)($resolvedMailConfig['from_name'] ?? $mailFromName);

if ($action === 'schedule_interview') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $interviewStage = strtolower((string)(cleanText($_POST['interview_stage'] ?? null) ?? ''));
    $interviewMode = strtolower((string)(cleanText($_POST['interview_mode'] ?? null) ?? ''));
    $interviewDate = cleanText($_POST['interview_date'] ?? null) ?? '';
    $interviewTime = cleanText($_POST['interview_time'] ?? null) ?? '';
    $notes = cleanText($_POST['schedule_notes'] ?? null);

    if (!isValidUuid($applicationId) || $interviewStage === '' || $interviewMode === '' || $interviewDate === '' || $interviewTime === '') {
        redirectWithState('error', 'Complete interview schedule details are required.');
    }

    if (!in_array($interviewStage, ['hr', 'technical', 'final'], true)) {
        redirectWithState('error', 'Invalid interview stage selected.');
    }

    if (!in_array($interviewMode, ['onsite', 'online', 'phone'], true)) {
        redirectWithState('error', 'Invalid interview mode selected.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,applicant:applicant_profiles(user_id,full_name,email),job:job_postings(title,office:offices(office_name))'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );

    $applicationRow = isSuccessful($applicationResponse) ? ($applicationResponse['data'][0] ?? null) : null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
    if (!canTransitionStatus('applications', $oldStatus, 'interview')) {
        redirectWithState('error', 'Invalid status transition from ' . $oldStatus . ' to interview.');
    }

    $scheduledAt = staffApplicantTrackingBuildUtcTimestamp($interviewDate, $interviewTime);
    if ($scheduledAt === null) {
        redirectWithState('error', 'Invalid interview schedule format. Please provide a valid date and time.');
    }
    $insertInterviewResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_interviews',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'interview_stage' => $interviewStage,
            'scheduled_at' => $scheduledAt,
            'interview_mode' => $interviewMode,
            'interviewer_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'result' => 'pending',
            'remarks' => $notes,
        ]]
    );

    if (!isSuccessful($insertInterviewResponse)) {
        redirectWithState('error', 'Failed to create interview schedule.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'application_status' => 'interview',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update application status after scheduling interview.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_status_history',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'old_status' => $oldStatus,
            'new_status' => 'interview',
            'changed_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'notes' => $notes ?: 'Interview scheduled',
        ]]
    );

    $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null) ?? '';
    $applicantName = trim((string)(cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant'));
    $applicantEmail = strtolower(trim((string)(cleanText($applicationRow['applicant']['email'] ?? null) ?? '')));
    $postingTitle = trim((string)(cleanText($applicationRow['job']['title'] ?? null) ?? 'Applied Position'));
    $officeName = trim((string)(cleanText($applicationRow['job']['office']['office_name'] ?? null) ?? 'TBD Venue'));
    if (isValidUuid($applicantUserId)) {
        staffApplicantTrackingNotify(
            $headers,
            $supabaseUrl,
            $applicantUserId,
            'Interview Scheduled',
            'Your application interview schedule has been updated.',
            '/hris-system/pages/applicant/applications.php'
        );
    }

    $emailStatus = [
        'recipient' => $applicantEmail !== '' ? $applicantEmail : null,
        'status' => 'not_sent',
        'message' => 'Email not sent (recipient email unavailable or SMTP not configured).',
        'error' => null,
    ];

    if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL) && smtpConfigIsReady($smtpConfig, $mailFrom)) {
        $stageLabel = ucfirst($interviewStage);
        $modeLabel = ucfirst($interviewMode);
        $scheduleLabel = staffApplicantTrackingFormatManilaDateTime($scheduledAt);
        $subject = 'Interview Schedule Update - ' . $stageLabel . ' Interview';
        $htmlContent = '<p>Hello ' . htmlspecialchars($applicantName !== '' ? $applicantName : 'Applicant', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Your interview has been scheduled by our recruitment team.</p>'
            . '<p><strong>Position:</strong> ' . htmlspecialchars($postingTitle, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Stage:</strong> ' . htmlspecialchars($stageLabel, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Mode:</strong> ' . htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Schedule:</strong> ' . htmlspecialchars($scheduleLabel, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Venue:</strong> ' . htmlspecialchars($officeName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Please check your applicant portal for any additional instructions.</p>';

        $emailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $applicantEmail,
            $applicantName,
            $subject,
            $htmlContent
        );

        if (isSuccessful($emailResponse)) {
            $emailStatus['status'] = 'sent';
            $emailStatus['message'] = 'Interview schedule email sent to ' . $applicantEmail . '.';
        } else {
            $emailStatus['status'] = 'failed';
            $emailStatus['message'] = 'Interview schedule email failed for ' . $applicantEmail . '.';
            $emailStatus['error'] = trim((string)($emailResponse['raw'] ?? ''));
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'applicant_tracking',
            'entity_name' => 'application_interviews',
            'entity_id' => $applicationId,
            'action_name' => 'schedule_interview',
            'old_data' => ['application_status' => $oldStatus],
            'new_data' => [
                'application_status' => 'interview',
                'interview_stage' => $interviewStage,
                'interview_mode' => $interviewMode,
                'scheduled_at' => $scheduledAt,
                'notes' => $notes,
                'venue' => $officeName,
                'email_delivery' => $emailStatus,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Interview scheduled successfully. ' . (string)($emailStatus['message'] ?? ''));
}

if ($action === 'update_status' || $action === 'update_application_status') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $statusNotes = cleanText($_POST['status_notes'] ?? null);

    if (!isValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid application selected.');
    }

    $allowedStatuses = ['submitted', 'screening', 'shortlisted', 'interview', 'offer', 'rejected', 'withdrawn'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        redirectWithState('error', 'Invalid application status selected.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,applicant:applicant_profiles(user_id,full_name,email),job:job_postings(title)'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );
    $applicationRow = isSuccessful($applicationResponse) ? ($applicationResponse['data'][0] ?? null) : null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null);
    if ($applicantUserId === null || !isValidUuid($applicantUserId)) {
        redirectWithState('error', 'Application has invalid applicant account reference.');
    }

    $oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
    if ($oldStatus === $newStatus) {
        redirectWithState('success', 'Application status is already ' . ucwords(str_replace('_', ' ', $newStatus)) . '.');
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
        redirectWithState('error', 'Failed to update application status.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_status_history',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'notes' => $statusNotes,
        ]]
    );

    $feedbackDecision = match ($newStatus) {
        'hired' => 'hired',
        'rejected' => 'rejected',
        'withdrawn' => 'on_hold',
        default => 'for_next_step',
    };

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_feedback?on_conflict=application_id',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'application_id' => $applicationId,
            'decision' => $feedbackDecision,
            'feedback_text' => ($statusNotes !== null && trim($statusNotes) !== '')
                ? trim((string)$statusNotes)
                : ('Status updated to ' . ucwords(str_replace('_', ' ', $newStatus))),
            'provided_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'provided_at' => gmdate('c'),
        ]]
    );

    staffApplicantTrackingNotify(
        $headers,
        $supabaseUrl,
        $applicantUserId,
        'Application Status Updated',
        'Your application status is now ' . ucwords(str_replace('_', ' ', $newStatus)) . '.',
        '/hris-system/pages/applicant/applications.php'
    );

    $applicantName = trim((string)(cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant'));
    $applicantEmail = strtolower(trim((string)(cleanText($applicationRow['applicant']['email'] ?? null) ?? '')));
    $postingTitle = trim((string)(cleanText($applicationRow['job']['title'] ?? null) ?? 'Applied Position'));
    $statusLabel = ucwords(str_replace('_', ' ', $newStatus));

    $emailStatus = [
        'recipient' => $applicantEmail !== '' ? $applicantEmail : null,
        'status' => 'not_sent',
        'message' => 'Email not sent (recipient email unavailable or SMTP not configured).',
        'error' => null,
    ];

    if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL) && smtpConfigIsReady($smtpConfig, $mailFrom)) {
        $subject = 'Application Status Update - ' . $statusLabel;
        $htmlContent = '<p>Hello ' . htmlspecialchars($applicantName !== '' ? $applicantName : 'Applicant', ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Your application status has been updated.</p>'
            . '<p><strong>Position:</strong> ' . htmlspecialchars($postingTitle, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>New Status:</strong> ' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</p>'
            . (($statusNotes !== null && trim($statusNotes) !== '')
                ? '<p><strong>Remarks:</strong> ' . htmlspecialchars((string)$statusNotes, ENT_QUOTES, 'UTF-8') . '</p>'
                : '')
            . '<p>You may review your application details in the applicant portal.</p>';

        $emailResponse = smtpSendTransactionalEmail(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $applicantEmail,
            $applicantName,
            $subject,
            $htmlContent
        );

        if (isSuccessful($emailResponse)) {
            $emailStatus['status'] = 'sent';
            $emailStatus['message'] = 'Status update email sent to ' . $applicantEmail . '.';
        } else {
            $emailStatus['status'] = 'failed';
            $emailStatus['message'] = 'Status update email failed for ' . $applicantEmail . '.';
            $emailStatus['error'] = trim((string)($emailResponse['raw'] ?? ''));
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'applicant_tracking',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'update_application_status',
            'old_data' => ['application_status' => $oldStatus],
            'new_data' => ['application_status' => $newStatus, 'notes' => $statusNotes, 'email_delivery' => $emailStatus],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Application status updated to ' . ucwords(str_replace('_', ' ', $newStatus)) . '. ' . (string)($emailStatus['message'] ?? ''));
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

    $applicantRow = is_array($applicationRow['applicant'] ?? null) ? (array)$applicationRow['applicant'] : [];
    $applicantUserId = cleanText($applicantRow['user_id'] ?? null) ?? '';
    $fullName = cleanText($applicantRow['full_name'] ?? null) ?? 'Applicant User';
    $email = cleanText($applicantRow['email'] ?? null);

    if (!isValidUuid($applicantUserId)) {
        redirectWithState('error', 'Applicant account is invalid for employee creation.');
    }

    if (!userHasActiveRoleAssignment($supabaseUrl, $headers, $applicantUserId, 'applicant')) {
        redirectWithState('error', 'Selected account is not an active applicant role.');
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
        $nameParts = staffApplicantTrackingSplitName($fullName);
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

    staffApplicantTrackingNotify(
        $headers,
        $supabaseUrl,
        $applicantUserId,
        'Employment Profile Created',
        'You have been added as an employee from your hired application for ' . $postingTitle . '.',
        '/hris-system/pages/employee/dashboard.php'
    );

    if (isValidUuid((string)$staffUserId)) {
        staffApplicantTrackingNotify(
            $headers,
            $supabaseUrl,
            (string)$staffUserId,
            'Applicant Converted to Employee',
            $fullName . ' has been successfully added as an employee.',
            '/hris-system/pages/staff/applicant-tracking.php'
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'applicant_tracking',
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

redirectWithState('error', 'Unknown applicant tracking action.');