<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

redirectWithState('error', 'Staff evaluation is now read-only. Decision actions are unavailable.');

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$resolvedStaffOfficeId = cleanText($staffOfficeId ?? null) ?? '';

$isPersonInScope = static function (string $personId) use ($isAdminScope, $resolvedStaffOfficeId, $supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    if ($isAdminScope) {
        return true;
    }

    if (!isValidUuid($resolvedStaffOfficeId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&office_id=eq.' . rawurlencode($resolvedStaffOfficeId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    return isSuccessful($scopeResponse) && !empty($scopeResponse['data'][0]);
};

$notifyUser = static function (string $recipientUserId, string $title, string $body) use ($supabaseUrl, $headers): void {
    if (!isValidUuid($recipientUserId)) {
        return;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $recipientUserId,
            'category' => 'evaluation',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/staff/evaluation.php',
        ]]
    );
};

$canTransitionEvaluation = static function (string $oldStatus, string $newStatus): bool {
    $oldKey = strtolower(trim($oldStatus));
    $newKey = strtolower(trim($newStatus));

    if ($oldKey === '' || $newKey === '') {
        return false;
    }

    if ($oldKey === $newKey) {
        return true;
    }

    $rules = [
        'draft' => ['submitted'],
        'submitted' => ['reviewed', 'approved'],
        'reviewed' => ['submitted', 'approved'],
    ];

    return isset($rules[$oldKey]) && in_array($newKey, $rules[$oldKey], true);
};

if ($action === 'save_rule_based_criteria') {
    if (!$isAdminScope) {
        redirectWithState('error', 'Only admin can update rule-based criteria.');
    }

    $positionTitle = trim((string)(cleanText($_POST['position_title'] ?? null) ?? ''));
    if ($positionTitle === '') {
        redirectWithState('error', 'Position title is required.');
    }

    $requiredEligibility = strtolower(trim((string)(cleanText($_POST['required_eligibility'] ?? null) ?? 'career service sub professional')));
    if ($requiredEligibility === '') {
        $requiredEligibility = 'career service sub professional';
    }

    $requiredEducationYears = (float)(cleanText($_POST['required_education_years'] ?? null) ?? '2');
    $requiredTrainingHours = (float)(cleanText($_POST['required_training_hours'] ?? null) ?? '4');
    $requiredExperienceYears = (float)(cleanText($_POST['required_experience_years'] ?? null) ?? '1');
    $threshold = (float)(cleanText($_POST['threshold'] ?? null) ?? '75');

    $requiredEducationYears = max(0, min(20, $requiredEducationYears));
    $requiredTrainingHours = max(0, min(1000, $requiredTrainingHours));
    $requiredExperienceYears = max(0, min(60, $requiredExperienceYears));
    $threshold = max(0, min(100, $threshold));

    $weights = [
        'eligibility' => 25,
        'education' => 25,
        'training' => 25,
        'experience' => 25,
    ];

    $criteriaMap = staffApplicantEvaluationLoadCriteriaMap($supabaseUrl, $headers);
    if (!is_array($criteriaMap)) {
        $criteriaMap = [];
    }

    $positionKey = staffApplicantEvaluationNormalizePositionKey($positionTitle);
    $criteriaMap[$positionKey] = [
        'eligibility' => $requiredEligibility,
        'minimum_education_years' => $requiredEducationYears,
        'minimum_training_hours' => $requiredTrainingHours,
        'minimum_experience_years' => $requiredExperienceYears,
        'threshold' => $threshold,
        'weights' => $weights,
        'updated_at' => gmdate('c'),
    ];

    $saved = staffSystemSettingUpsertValue(
        $supabaseUrl,
        $headers,
        'evaluation.applicant.criteria_by_position_title',
        $criteriaMap,
        $staffUserId
    );

    if (!$saved) {
        redirectWithState('error', 'Failed to save rule-based criteria.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'evaluation',
            'entity_name' => 'system_settings',
            'entity_id' => null,
            'action_name' => 'save_rule_based_criteria',
            'old_data' => null,
            'new_data' => [
                'position_title' => $positionTitle,
                'criteria' => $criteriaMap[$positionKey],
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Rule-based criteria saved for ' . $positionTitle . '.');
}

if ($action === 'run_rule_based_evaluation') {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'evaluation',
            'entity_name' => 'applications',
            'entity_id' => null,
            'action_name' => 'run_rule_based_evaluation',
            'old_data' => null,
            'new_data' => [
                'triggered_at' => gmdate('c'),
                'triggered_by_role' => strtolower((string)($staffRoleKey ?? 'staff')),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Rule-based applicant evaluation run completed.');
}

if ($action === 'submit_applicant_final_evaluation') {
    $applicationId = cleanText($_POST['application_id'] ?? null) ?? '';
    $interviewResult = strtolower((string)(cleanText($_POST['interview_result'] ?? null) ?? ''));
    $interviewScoreInput = cleanText($_POST['interview_score'] ?? null);
    $hrRemarks = cleanText($_POST['hr_remarks'] ?? null) ?? '';
    $finalRecommendation = strtolower((string)(cleanText($_POST['final_recommendation'] ?? null) ?? ''));

    if (!isValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid applicant evaluation target.');
    }

    if (!in_array($interviewResult, ['pass', 'fail', 'pending'], true)) {
        redirectWithState('error', 'Invalid interview result selected.');
    }

    $interviewScore = null;
    if ($interviewScoreInput !== null && trim((string)$interviewScoreInput) !== '') {
        if (!is_numeric($interviewScoreInput)) {
            redirectWithState('error', 'Interview score must be numeric.');
        }

        $interviewScore = max(0.0, min(100.0, (float)$interviewScoreInput));
    }

    if ($hrRemarks === '') {
        redirectWithState('error', 'HR remarks are required.');
    }

    if (!in_array($finalRecommendation, ['recommend_for_approval', 'not_recommended'], true)) {
        redirectWithState('error', 'Invalid final recommendation selected.');
    }

    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,application_status,applicant:applicant_profiles(user_id,full_name),job:job_postings(title,office_id)'
        . '&id=eq.' . rawurlencode($applicationId)
        . '&limit=1',
        $headers
    );
    $applicationRow = isSuccessful($applicationResponse) ? ($applicationResponse['data'][0] ?? null) : null;
    if (!is_array($applicationRow)) {
        redirectWithState('error', 'Application record not found.');
    }

    $jobOfficeId = cleanText($applicationRow['job']['office_id'] ?? null) ?? '';
    if (!$isAdminScope && isValidUuid($resolvedStaffOfficeId) && isValidUuid($jobOfficeId) && strcasecmp($resolvedStaffOfficeId, $jobOfficeId) !== 0) {
        redirectWithState('error', 'Applicant is outside your division scope.');
    }

    $oldStatus = strtolower((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted'));
    if (!in_array($oldStatus, ['interview', 'offer'], true)) {
        redirectWithState('error', 'Final evaluation can only be submitted for interview/offer stage applications.');
    }

    $latestInterviewResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_interviews?select=id,result,score,remarks'
        . '&application_id=eq.' . rawurlencode($applicationId)
        . '&order=scheduled_at.desc&order=created_at.desc&limit=1',
        $headers
    );
    $latestInterviewRow = isSuccessful($latestInterviewResponse) ? ($latestInterviewResponse['data'][0] ?? null) : null;
    if (!is_array($latestInterviewRow)) {
        redirectWithState('error', 'No interview record found. Please schedule interview first.');
    }

    $interviewId = cleanText($latestInterviewRow['id'] ?? null) ?? '';
    if (!isValidUuid($interviewId)) {
        redirectWithState('error', 'Latest interview record is invalid.');
    }

    $interviewPatchPayload = [
        'result' => $interviewResult,
        'remarks' => $hrRemarks,
    ];
    if ($interviewScore !== null) {
        $interviewPatchPayload['score'] = round($interviewScore, 2);
    }

    $interviewPatchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/application_interviews?id=eq.' . rawurlencode($interviewId),
        array_merge($headers, ['Prefer: return=minimal']),
        $interviewPatchPayload
    );
    if (!isSuccessful($interviewPatchResponse)) {
        redirectWithState('error', 'Failed to record interview result.');
    }

    $feedbackDecision = $finalRecommendation === 'recommend_for_approval' ? 'for_next_step' : 'on_hold';
    $feedbackResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/application_feedback?on_conflict=application_id',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        [[
            'application_id' => $applicationId,
            'decision' => $feedbackDecision,
            'feedback_text' => $hrRemarks,
            'provided_by' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'provided_at' => gmdate('c'),
        ]]
    );
    if (!isSuccessful($feedbackResponse)) {
        redirectWithState('error', 'Failed to save HR remarks.');
    }

    $newStatus = 'offer';
    if (!canTransitionStatus('applications', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid status transition from ' . $oldStatus . ' to ' . $newStatus . '.');
    }

    $applicationPatchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'application_status' => $newStatus,
            'updated_at' => gmdate('c'),
        ]
    );
    if (!isSuccessful($applicationPatchResponse)) {
        redirectWithState('error', 'Failed to submit final evaluation for admin approval.');
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
            'notes' => 'Final applicant evaluation submitted for admin approval.',
        ]]
    );

    $applicantUserId = cleanText($applicationRow['applicant']['user_id'] ?? null) ?? '';
    $applicantName = trim((string)(cleanText($applicationRow['applicant']['full_name'] ?? null) ?? 'Applicant'));
    $positionTitle = trim((string)(cleanText($applicationRow['job']['title'] ?? null) ?? 'Applied Position'));

    if (isValidUuid($applicantUserId)) {
        $notifyUser(
            $applicantUserId,
            'Applicant Evaluation Submitted',
            'Your application for ' . $positionTitle . ' was submitted for admin approval.'
        );
    }

    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }
        if (strcasecmp((string)$adminUserId, (string)$staffUserId) === 0) {
            continue;
        }

        $notifyUser(
            (string)$adminUserId,
            'Applicant Final Evaluation For Approval',
            ($applicantName !== '' ? $applicantName : 'Applicant') . ' was endorsed for admin approval (' . $positionTitle . ').'
        );
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'evaluation',
            'entity_name' => 'applications',
            'entity_id' => $applicationId,
            'action_name' => 'submit_applicant_final_evaluation',
            'old_data' => [
                'application_status' => $oldStatus,
                'interview_result' => cleanText($latestInterviewRow['result'] ?? null),
                'interview_score' => cleanText($latestInterviewRow['score'] ?? null),
                'interview_remarks' => cleanText($latestInterviewRow['remarks'] ?? null),
            ],
            'new_data' => [
                'application_status' => $newStatus,
                'interview_result' => $interviewResult,
                'interview_score' => $interviewScore !== null ? round($interviewScore, 2) : null,
                'hr_remarks' => $hrRemarks,
                'final_recommendation' => $finalRecommendation,
                'feedback_decision' => $feedbackDecision,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Final applicant evaluation submitted for admin approval.');
}

if ($action === 'review_performance_evaluation') {
    $evaluationId = cleanText($_POST['evaluation_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = cleanText($_POST['remarks'] ?? null);

    if (!isValidUuid($evaluationId)) {
        redirectWithState('error', 'Invalid performance evaluation selected.');
    }

    if (!in_array($decision, ['submitted', 'reviewed', 'approved'], true)) {
        redirectWithState('error', 'Invalid evaluation decision selected.');
    }

    $evaluationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/performance_evaluations?select=id,status,employee_person_id,evaluator_user_id,cycle:cycle_id(cycle_name),employee:employee_person_id(first_name,surname,user_id)'
        . '&id=eq.' . rawurlencode($evaluationId)
        . '&limit=1',
        $headers
    );

    $evaluationRow = isSuccessful($evaluationResponse) ? ($evaluationResponse['data'][0] ?? null) : null;
    if (!is_array($evaluationRow)) {
        redirectWithState('error', 'Performance evaluation record not found.');
    }

    $employeePersonId = cleanText($evaluationRow['employee_person_id'] ?? null) ?? '';
    $employeeUserId = cleanText($evaluationRow['employee']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($employeePersonId)) {
        redirectWithState('error', 'Performance evaluation is outside your division scope.');
    }

    $oldStatus = strtolower((string)(cleanText($evaluationRow['status'] ?? null) ?? 'draft'));
    if (!$canTransitionEvaluation($oldStatus, $decision)) {
        redirectWithState('error', 'Invalid evaluation transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/performance_evaluations?id=eq.' . rawurlencode($evaluationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'remarks' => $remarks,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update performance evaluation decision.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'evaluation',
            'entity_name' => 'performance_evaluations',
            'entity_id' => $evaluationId,
            'action_name' => 'review_performance_evaluation',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'remarks' => $remarks],
            'ip_address' => clientIp(),
        ]]
    );

    $employeeName = trim(
        (string)(cleanText($evaluationRow['employee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($evaluationRow['employee']['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    $cycleName = cleanText($evaluationRow['cycle']['cycle_name'] ?? null) ?? 'current cycle';

    $evaluatorUserId = cleanText($evaluationRow['evaluator_user_id'] ?? null) ?? '';
    if (isValidUuid($evaluatorUserId) && strcasecmp($evaluatorUserId, $staffUserId) !== 0) {
        $notifyUser(
            $evaluatorUserId,
            'Performance Evaluation Updated',
            'Evaluation for ' . $employeeName . ' in ' . $cycleName . ' was marked as ' . str_replace('_', ' ', $decision) . '.'
        );
    }

    redirectWithState('success', 'Performance evaluation updated to ' . $decision . '.');
}

redirectWithState('error', 'Unknown evaluation action.');
