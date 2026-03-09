<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'convert_hired_to_employee') {
    require __DIR__ . '/../applicants/actions.php';
    return;
}

if ($action === 'schedule_interview') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $interviewStage = strtolower((string)(cleanText($_POST['interview_stage'] ?? null) ?? ''));
    $interviewMode = strtolower((string)(cleanText($_POST['interview_mode'] ?? null) ?? ''));
    $interviewDate = cleanText($_POST['interview_date'] ?? null) ?? '';
    $interviewTime = cleanText($_POST['interview_time'] ?? null) ?? '';
    $notes = cleanText($_POST['schedule_notes'] ?? null);

    if ($applicationId === '' || $interviewStage === '' || $interviewMode === '' || $interviewDate === '' || $interviewTime === '') {
        redirectWithState('error', 'Complete interview schedule details are required.');
    }

    if (!in_array($interviewStage, ['hr', 'technical', 'final'], true)) {
        redirectWithState('error', 'Invalid interview stage selected.');
    }

    if (!in_array($interviewMode, ['onsite', 'online', 'phone'], true)) {
        redirectWithState('error', 'Invalid interview mode selected.');
    }

    $scheduledAt = $interviewDate . 'T' . $interviewTime . ':00Z';

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant_profile_id,applicant:applicant_profiles(user_id,full_name,email)&id=eq.' . $applicationId . '&limit=1',
        $headers
    );

    $applicationRow = $applicationResponse['data'][0] ?? null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
    $applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');
    $applicantName = trim((string)($applicationRow['applicant']['full_name'] ?? 'Applicant'));
    $applicantEmail = strtolower(trim((string)($applicationRow['applicant']['email'] ?? '')));

    if (preg_match('/^[a-f0-9-]{36}$/i', $applicantUserId)) {
        $personLookupResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
            $headers
        );

        $personId = (string)($personLookupResponse['data'][0]['id'] ?? '');
        $employmentCheckResponse = ['status' => 200, 'data' => []];
        if ($personId !== '') {
            $employmentCheckResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/employment_records?select=id&person_id=eq.' . rawurlencode($personId) . '&is_current=eq.true&limit=1',
                $headers
            );
        }

        if (isSuccessful($employmentCheckResponse) && !empty((array)($employmentCheckResponse['data'] ?? []))) {
            redirectWithState('error', 'Applicant is already added as an employee. Further actions are disabled.');
        }
    }

    $insertInterview = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_interviews',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'interview_stage' => $interviewStage,
            'scheduled_at' => $scheduledAt,
            'interview_mode' => $interviewMode,
            'interviewer_user_id' => $adminUserId,
            'result' => 'pending',
            'remarks' => $notes,
        ]]
    );

    if (!isSuccessful($insertInterview)) {
        redirectWithState('error', 'Failed to create interview schedule.');
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/applications?id=eq.' . $applicationId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'application_status' => 'interview',
            'updated_at' => gmdate('c'),
        ]
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_status_history',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'application_id' => $applicationId,
            'old_status' => $oldStatus,
            'new_status' => 'interview',
            'changed_by' => $adminUserId,
            'notes' => $notes ?? 'Interview scheduled',
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
                'title' => 'Interview Scheduled',
                'body' => 'Your application interview schedule has been updated.',
                'link_url' => '/hris-system/pages/applicant/applications.php',
            ]]
        );
    }

    $emailStatusMessage = 'Email not sent (recipient email unavailable).';
    $emailDeliveryStatus = 'not_sent';
    $emailDeliveryError = null;
    if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        if (smtpConfigIsReady($smtpConfig, $mailFrom)) {
            $stageLabel = ucfirst($interviewStage);
            $modeLabel = ucfirst($interviewMode);
            $scheduleLabel = hrisEmailFormatPhilippinesDateTime($scheduledAt);
            $subject = 'Interview Scheduled - ' . $stageLabel . ' Stage';
            $htmlContent = '<p>Hello ' . htmlspecialchars($applicantName !== '' ? $applicantName : 'Applicant', ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your interview has been scheduled.</p>'
                . '<p><strong>Stage:</strong> ' . htmlspecialchars($stageLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Mode:</strong> ' . htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Schedule:</strong> ' . htmlspecialchars($scheduleLabel, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Please check your applicant portal for additional instructions and confirm that you can attend on time.</p>';

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
                $emailStatusMessage = 'Email sent to ' . $applicantEmail . '.';
                $emailDeliveryStatus = 'sent';
            } else {
                $emailStatusMessage = 'Email delivery failed to ' . $applicantEmail . '.';
                $emailDeliveryStatus = 'failed';
                $emailDeliveryError = trim((string)($emailResponse['raw'] ?? ''));
            }
        } else {
            $emailStatusMessage = 'Email not sent to ' . $applicantEmail . ' (SMTP not configured).';
            $emailDeliveryStatus = 'smtp_not_configured';
        }
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'applicant_tracking',
        'applications',
        $applicationId,
        'schedule_interview',
        $oldStatus,
        'interview',
        $notes,
        [
            'interview_stage' => $interviewStage,
            'interview_mode' => $interviewMode,
            'scheduled_at' => $scheduledAt,
            'email_delivery' => [
                'recipient' => $applicantEmail !== '' ? $applicantEmail : null,
                'status' => $emailDeliveryStatus,
                'message' => $emailStatusMessage,
                'error' => $emailDeliveryError,
            ],
        ]
    );

    redirectWithState('success', 'Interview scheduled successfully. ' . $emailStatusMessage);
}

if ($action === 'update_status') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $notes = cleanText($_POST['status_notes'] ?? null);

    if ($applicationId === '' || $newStatus === '') {
        redirectWithState('error', 'Application and new status are required.');
    }

    $allowedStatuses = ['submitted', 'screening', 'shortlisted', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        redirectWithState('error', 'Invalid status selected.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant:applicant_profiles(user_id,full_name,email)&id=eq.' . $applicationId . '&limit=1',
        $headers
    );

    $applicationRow = $applicationResponse['data'][0] ?? null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
    $applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');
    $applicantName = trim((string)($applicationRow['applicant']['full_name'] ?? 'Applicant'));
    $applicantEmail = strtolower(trim((string)($applicationRow['applicant']['email'] ?? '')));

    if (preg_match('/^[a-f0-9-]{36}$/i', $applicantUserId)) {
        $personLookupResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
            $headers
        );

        $personId = (string)($personLookupResponse['data'][0]['id'] ?? '');
        $employmentCheckResponse = ['status' => 200, 'data' => []];
        if ($personId !== '') {
            $employmentCheckResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/employment_records?select=id&person_id=eq.' . rawurlencode($personId) . '&is_current=eq.true&limit=1',
                $headers
            );
        }

        if (isSuccessful($employmentCheckResponse) && !empty((array)($employmentCheckResponse['data'] ?? []))) {
            redirectWithState('error', 'Applicant is already added as an employee. Further actions are disabled.');
        }
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/applications?id=eq.' . $applicationId,
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
            'changed_by' => $adminUserId,
            'notes' => $notes,
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
                'title' => 'Application Status Updated',
                'body' => 'Your application status is now ' . ucfirst($newStatus) . '.',
                'link_url' => '/hris-system/pages/applicant/applications.php',
            ]]
        );
    }

    $emailStatusMessage = 'Email not sent (recipient email unavailable).';
    $emailDeliveryStatus = 'not_sent';
    $emailDeliveryError = null;
    if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
        if (smtpConfigIsReady($smtpConfig, $mailFrom)) {
            $statusLabel = ucfirst($newStatus);
            $subject = 'Application Status Updated - ' . $statusLabel;
            $htmlContent = '<p>Hello ' . htmlspecialchars($applicantName !== '' ? $applicantName : 'Applicant', ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your application status has been updated.</p>'
                . '<p><strong>New Status:</strong> ' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</p>'
                . ($notes !== null && trim($notes) !== '' ? '<p><strong>Remarks:</strong> ' . htmlspecialchars((string)$notes, ENT_QUOTES, 'UTF-8') . '</p>' : '')
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
                $emailStatusMessage = 'Email sent to ' . $applicantEmail . '.';
                $emailDeliveryStatus = 'sent';
            } else {
                $emailStatusMessage = 'Email delivery failed to ' . $applicantEmail . '.';
                $emailDeliveryStatus = 'failed';
                $emailDeliveryError = trim((string)($emailResponse['raw'] ?? ''));
            }
        } else {
            $emailStatusMessage = 'Email not sent to ' . $applicantEmail . ' (SMTP not configured).';
            $emailDeliveryStatus = 'smtp_not_configured';
        }
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'applicant_tracking',
        'applications',
        $applicationId,
        'update_status',
        $oldStatus,
        $newStatus,
        $notes,
        [
            'email_delivery' => [
                'recipient' => $applicantEmail !== '' ? $applicantEmail : null,
                'status' => $emailDeliveryStatus,
                'message' => $emailStatusMessage,
                'error' => $emailDeliveryError,
            ],
        ]
    );

    redirectWithState('success', 'Application status updated successfully. ' . $emailStatusMessage);
}

redirectWithState('error', 'Unknown applicant tracking action.');
