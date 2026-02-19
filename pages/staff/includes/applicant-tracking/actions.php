<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if ($action !== 'update_application_status') {
    redirectWithState('error', 'Unknown applicant tracking action.');
}

$applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
$newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
$statusNotes = cleanText($_POST['status_notes'] ?? null);

if (!isValidUuid($applicationId)) {
    redirectWithState('error', 'Invalid application selected.');
}

$allowedStatuses = ['submitted', 'screening', 'shortlisted', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'];
if (!in_array($newStatus, $allowedStatuses, true)) {
    redirectWithState('error', 'Invalid application status selected.');
}

$applicationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=id,application_status,job_posting_id,applicant:applicant_profiles(full_name,user_id,email)'
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

$applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null);
if ($applicantUserId === null || !isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Application has invalid applicant account reference.');
}

if (!userHasActiveRoleAssignment($supabaseUrl, $headers, $applicantUserId, 'applicant')) {
    redirectWithState('error', 'Application target is not an active applicant account.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
if (!$isAdminScope) {
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
        redirectWithState('error', 'You are not allowed to update applications outside your office scope.');
    }
}

$oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
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
        'changed_by' => $staffUserId,
        'notes' => $statusNotes,
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/notifications',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'recipient_user_id' => $applicantUserId,
        'category' => 'recruitment',
        'title' => 'Application Status Updated',
        'body' => 'Your application status is now ' . ucwords(str_replace('_', ' ', $newStatus)) . '.',
        'link_url' => '/hris-system/pages/applicant/applications.php',
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'applicant_tracking',
        'entity_name' => 'applications',
        'entity_id' => $applicationId,
        'action_name' => 'update_application_status',
        'old_data' => ['application_status' => $oldStatus],
        'new_data' => ['application_status' => $newStatus, 'notes' => $statusNotes],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Application status updated to ' . $newStatus . '.');