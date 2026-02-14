<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

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
        $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant_profile_id,applicant:applicant_profiles(user_id)&id=eq.' . $applicationId . '&limit=1',
        $headers
    );

    $applicationRow = $applicationResponse['data'][0] ?? null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
    $applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');

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

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
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
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Interview scheduled successfully.');
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
        $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant:applicant_profiles(user_id)&id=eq.' . $applicationId . '&limit=1',
        $headers
    );

    $applicationRow = $applicationResponse['data'][0] ?? null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
    $applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');

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

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'applicant_tracking',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'update_status',
            'old_data' => ['application_status' => $oldStatus],
            'new_data' => ['application_status' => $newStatus, 'notes' => $notes],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Application status updated successfully.');
}

redirectWithState('error', 'Unknown applicant tracking action.');
