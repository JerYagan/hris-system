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

$action = (string)($_POST['form_action'] ?? '');

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
    $supabaseUrl . '/rest/v1/applications?select=id,application_status,applicant_profile_id,applicant:applicant_profiles(user_id,full_name)&id=eq.' . $applicationId . '&limit=1',
    $headers
);

$applicationRow = $applicationResponse['data'][0] ?? null;
if (!is_array($applicationRow)) {
    redirectWithState('error', 'Application record not found.');
}

$oldStatus = (string)($applicationRow['application_status'] ?? 'submitted');
$applicantUserId = (string)($applicationRow['applicant']['user_id'] ?? '');
$applicantName = (string)($applicationRow['applicant']['full_name'] ?? 'Applicant');

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
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Applicant screening decision saved successfully.');
