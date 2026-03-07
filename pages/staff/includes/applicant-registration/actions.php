<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

redirectWithState('error', 'Staff applicant registration is now read-only. Decision actions are unavailable.');

if (!in_array($action, ['registration_decision', 'save_applicant_decision'], true)) {
    redirectWithState('error', 'Unknown applicant registration action.');
}

$applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
$decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? cleanText($_POST['new_status'] ?? null) ?? ''));
$decisionDate = cleanText($_POST['decision_date'] ?? null) ?? gmdate('Y-m-d');
$decisionBasis = cleanText($_POST['basis'] ?? null) ?? '';
$decisionNotes = cleanText($_POST['remarks'] ?? null) ?? cleanText($_POST['decision_notes'] ?? null);

if (!isValidUuid($applicationId)) {
    redirectWithState('error', 'Invalid application selected.');
}

if (!in_array($decision, ['approve_for_next_stage', 'disqualify_application', 'return_for_compliance', 'forward_for_evaluation', 'reject_application', 'shortlisted', 'rejected', 'screening'], true)) {
    redirectWithState('error', 'Invalid decision selected.');
}

$decisionMap = [
    'approve_for_next_stage' => [
        'notification' => 'Your application passed initial screening and is moving to the next stage.',
        'label' => 'Approve for next stage',
    ],
    'disqualify_application' => [
        'status' => 'rejected',
        'notification' => 'Your application did not pass initial screening.',
        'label' => 'Disqualify application',
    ],
    'return_for_compliance' => [
        'status' => 'screening',
        'notification' => 'Your application needs additional compliance before continuing screening.',
        'label' => 'Return for compliance',
    ],
    'forward_for_evaluation' => [
        'notification' => 'Your application has been forwarded for evaluation.',
        'label' => 'Forward for evaluation',
    ],
    'shortlisted' => [
        'notification' => 'Your application has been forwarded for evaluation.',
        'label' => 'Forward for evaluation',
    ],
    'reject_application' => [
        'status' => 'rejected',
        'notification' => 'Your application was not selected for evaluation.',
        'label' => 'Reject application',
    ],
    'rejected' => [
        'status' => 'rejected',
        'notification' => 'Your application was not selected for evaluation.',
        'label' => 'Reject application',
    ],
    'screening' => [
        'status' => 'screening',
        'notification' => 'Your application needs additional compliance before continuing screening.',
        'label' => 'Return for compliance',
    ],
];

$resolvedDecision = $decisionMap[$decision] ?? null;
if (!is_array($resolvedDecision)) {
    redirectWithState('error', 'Invalid decision selected.');
}

$newStatus = (string)($resolvedDecision['status'] ?? 'submitted');

$applicationResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_status,job_posting_id,applicant:applicant_profiles(full_name,user_id)&id=eq.' . rawurlencode($applicationId) . '&limit=1',
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

$oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
$nextStatusMap = [
    'submitted' => 'screening',
    'screening' => 'shortlisted',
    'shortlisted' => 'interview',
    'interview' => 'offer',
    'offer' => 'hired',
];

$approvalDecisions = ['approve_for_next_stage', 'forward_for_evaluation', 'shortlisted'];
if (in_array($decision, $approvalDecisions, true)) {
    $newStatus = $nextStatusMap[$oldStatus] ?? '';
    if ($newStatus === '') {
        redirectWithState('error', 'Cannot approve application from current status: ' . $oldStatus . '.');
    }
} elseif (in_array($decision, ['return_for_compliance', 'screening'], true)) {
    $newStatus = 'screening';
} else {
    $newStatus = (string)($resolvedDecision['status'] ?? 'submitted');
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
    redirectWithState('error', 'Failed to apply registration decision.');
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
        'notes' => trim(($decisionBasis !== '' ? $decisionBasis : 'Decision recorded') . ($decisionNotes ? ' | ' . $decisionNotes : '')),
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_feedback?on_conflict=application_id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'application_id' => $applicationId,
        'decision' => $newStatus === 'shortlisted' ? 'for_next_step' : 'rejected',
        'feedback_text' => $decisionNotes !== null && trim($decisionNotes) !== ''
            ? $decisionNotes
            : ($decisionBasis !== '' ? $decisionBasis : (string)($resolvedDecision['label'] ?? 'Decision recorded')),
        'provided_by' => $staffUserId,
        'provided_at' => $decisionDate . 'T00:00:00Z',
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/notifications',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'recipient_user_id' => $applicantUserId,
        'category' => 'recruitment',
        'title' => 'Application Verification Update',
        'body' => (string)($resolvedDecision['notification'] ?? ('Your application is now marked as ' . ucwords(str_replace('_', ' ', $newStatus)) . '.')),
        'link_url' => '/hris-system/pages/applicant/applications.php',
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'applicant_registration',
        'entity_name' => 'applications',
        'entity_id' => $applicationId,
        'action_name' => 'registration_decision',
        'old_data' => ['application_status' => $oldStatus],
        'new_data' => [
            'application_status' => $newStatus,
            'decision' => (string)($resolvedDecision['label'] ?? $decision),
            'basis' => $decisionBasis,
            'notes' => $decisionNotes,
            'decision_date' => $decisionDate,
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Applicant registration decision saved.');