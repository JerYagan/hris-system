<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)(cleanText($_POST['form_action'] ?? null) ?? '');
if ($action === '') {
    return;
}

if ($action === 'save_evaluation_criteria') {
    $educationRequirement = strtolower((string)(cleanText($_POST['education_requirement'] ?? null) ?? 'related_bachelor'));
    $minimumExperienceYears = (int)(cleanText($_POST['minimum_experience_years'] ?? null) ?? '2');
    $minimumExamScore = (float)(cleanText($_POST['minimum_exam_score'] ?? null) ?? '75');
    $minimumInterviewRating = (float)(cleanText($_POST['minimum_interview_rating'] ?? null) ?? '3.5');
    $ruleNotes = trim((string)(cleanText($_POST['rule_notes'] ?? null) ?? ''));

    if (!in_array($educationRequirement, ['related_bachelor', 'any_bachelor', 'masters_preferred'], true)) {
        redirectWithState('error', 'Invalid education requirement selected.');
    }

    if ($minimumExperienceYears < 0 || $minimumExperienceYears > 30) {
        redirectWithState('error', 'Minimum experience years must be between 0 and 30.');
    }

    if ($minimumExamScore < 0 || $minimumExamScore > 100) {
        redirectWithState('error', 'Minimum exam score must be between 0 and 100.');
    }

    if ($minimumInterviewRating < 1 || $minimumInterviewRating > 5) {
        redirectWithState('error', 'Minimum interview rating must be between 1 and 5.');
    }

    $criteria = [
        'education_requirement' => $educationRequirement,
        'minimum_experience_years' => $minimumExperienceYears,
        'minimum_exam_score' => $minimumExamScore,
        'minimum_interview_rating' => $minimumInterviewRating,
        'rule_notes' => $ruleNotes,
        'updated_at' => gmdate('c'),
    ];

    $saved = evaluationUpsertSetting($supabaseUrl, $headers, 'evaluation.rule_based.criteria', $criteria, $adminUserId);
    if (!$saved) {
        redirectWithState('error', 'Failed to save evaluation criteria.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'evaluation',
            'entity_name' => 'system_settings',
            'entity_id' => null,
            'action_name' => 'save_rule_based_criteria',
            'old_data' => null,
            'new_data' => $criteria,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Evaluation criteria saved successfully.');
}

if ($action === 'run_rule_evaluation') {
    $criteria = evaluationLoadCriteria($supabaseUrl, $headers);
    $dataset = evaluationBuildDataset($supabaseUrl, $headers);

    if (!empty($dataset['errors'])) {
        redirectWithState('error', implode(' ', (array)$dataset['errors']));
    }

    $result = evaluationRunRuleEngine($dataset, $criteria);
    $summary = (array)($result['summary'] ?? []);

    $snapshot = [
        'generated_at' => gmdate('c'),
        'criteria' => $criteria,
        'summary' => $summary,
    ];

    $saved = evaluationUpsertSetting($supabaseUrl, $headers, 'evaluation.rule_based.last_run', $snapshot, $adminUserId);
    if (!$saved) {
        redirectWithState('error', 'Rule-based evaluation ran but failed to persist run snapshot.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'evaluation',
            'entity_name' => 'applications',
            'entity_id' => null,
            'action_name' => 'run_rule_evaluation',
            'old_data' => null,
            'new_data' => $snapshot,
            'ip_address' => clientIp(),
        ]]
    );

    $total = (int)($summary['total'] ?? 0);
    redirectWithState('success', 'Rule-based evaluation completed for ' . $total . ' application(s).');
}

if ($action === 'generate_system_recommendations') {
    $criteria = evaluationLoadCriteria($supabaseUrl, $headers);
    $dataset = evaluationBuildDataset($supabaseUrl, $headers);

    if (!empty($dataset['errors'])) {
        redirectWithState('error', implode(' ', (array)$dataset['errors']));
    }

    $result = evaluationRunRuleEngine($dataset, $criteria);
    $rows = (array)($result['rows'] ?? []);
    $summary = (array)($result['summary'] ?? []);

    $recommendationRows = [];
    foreach ($rows as $row) {
        $recommendationRows[] = [
            'application_id' => (string)($row['application_id'] ?? ''),
            'application_ref_no' => (string)($row['application_ref_no'] ?? '-'),
            'applicant_name' => (string)($row['applicant_name'] ?? '-'),
            'job_title' => (string)($row['job_title'] ?? '-'),
            'rule_result' => (string)($row['rule_result'] ?? 'Fail'),
            'recommendation' => (string)($row['recommendation'] ?? 'Not Recommended'),
        ];
    }

    $snapshot = [
        'generated_at' => gmdate('c'),
        'criteria' => $criteria,
        'summary' => $summary,
        'recommendations' => $recommendationRows,
    ];

    $saved = evaluationUpsertSetting($supabaseUrl, $headers, 'evaluation.rule_based.recommendations', $snapshot, $adminUserId);
    if (!$saved) {
        redirectWithState('error', 'Failed to persist generated recommendations.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'evaluation',
            'entity_name' => 'applications',
            'entity_id' => null,
            'action_name' => 'generate_system_recommendations',
            'old_data' => null,
            'new_data' => [
                'generated_at' => $snapshot['generated_at'],
                'summary' => $summary,
                'recommendations_count' => count($recommendationRows),
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $count = count($recommendationRows);
    redirectWithState('success', 'Generated recommendations for ' . $count . ' application(s).');
}

redirectWithState('error', 'Unknown evaluation action.');
