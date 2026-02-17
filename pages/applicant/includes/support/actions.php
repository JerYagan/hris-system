<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'support.php?tab=contact');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'support.php?tab=contact');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'support.php?tab=contact');
}

$action = cleanText($_POST['action'] ?? null) ?? 'submit_support';
if ($action !== 'submit_support') {
    redirectWithState('error', 'Unsupported support action.', 'support.php?tab=contact');
}

$subject = cleanText($_POST['subject'] ?? null);
$supportMessage = cleanText($_POST['message'] ?? null);

if ($subject === null || mb_strlen($subject) < 5) {
    redirectWithState('error', 'Please provide a subject with at least 5 characters.', 'support.php?tab=contact');
}

if (mb_strlen($subject) > 150) {
    redirectWithState('error', 'Subject is too long. Please keep it under 150 characters.', 'support.php?tab=contact');
}

if ($supportMessage === null || mb_strlen($supportMessage) < 10) {
    redirectWithState('error', 'Please provide a message with at least 10 characters.', 'support.php?tab=contact');
}

if (mb_strlen($supportMessage) > 3000) {
    redirectWithState('error', 'Message is too long. Please keep it under 3000 characters.', 'support.php?tab=contact');
}

$supportLogResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $applicantUserId,
        'module_name' => 'applicant_support',
        'entity_name' => 'support_inquiry',
        'entity_id' => null,
        'action_name' => 'submit_support',
        'old_data' => null,
        'new_data' => [
            'subject' => $subject,
            'message' => $supportMessage,
            'source_page' => 'support_contact',
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

if (!isSuccessful($supportLogResponse)) {
    redirectWithState('error', 'Failed to submit your support request. Please try again.', 'support.php?tab=contact');
}

$roleIds = [];
$rolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=id,role_key&role_key=in.(admin,staff,hr)&limit=50',
    $headers
);

if (isSuccessful($rolesResponse)) {
    foreach ((array)($rolesResponse['data'] ?? []) as $roleRow) {
        $roleId = cleanText($roleRow['id'] ?? null);
        if ($roleId !== null) {
            $roleIds[] = $roleId;
        }
    }
}

$hrUserIds = [];
if (!empty($roleIds)) {
    $assignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id&role_id=in.' . rawurlencode('(' . implode(',', $roleIds) . ')')
        . '&expires_at=is.null&limit=500',
        $headers
    );

    if (isSuccessful($assignmentsResponse)) {
        foreach ((array)($assignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null);
            if ($userId !== null && $userId !== $applicantUserId) {
                $hrUserIds[$userId] = true;
            }
        }
    }
}

if (!empty($hrUserIds)) {
    $notificationRows = [];
    foreach (array_keys($hrUserIds) as $recipientUserId) {
        $notificationRows[] = [
            'recipient_user_id' => $recipientUserId,
            'category' => 'support',
            'title' => 'New applicant support inquiry',
            'body' => 'Subject: ' . mb_substr($subject, 0, 120),
            'link_url' => '/hris-system/pages/admin/notifications.php',
            'is_read' => false,
        ];
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        $notificationRows
    );
}

redirectWithState('success', 'Your message has been sent to HR support.', 'support.php?tab=contact');
