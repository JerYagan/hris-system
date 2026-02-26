<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)(cleanText($_POST['form_action'] ?? null) ?? '');
if ($action === '') {
    return;
}

if ($action === 'save_evaluation_criteria') {
    $eligibilityOption = evaluationNormalizeEligibilityOption((string)(cleanText($_POST['required_eligibility'] ?? null) ?? 'csc_prc'));
    $eligibility = evaluationEligibilityOptionToRequirement($eligibilityOption);
    $minimumEducationYears = (float)(cleanText($_POST['minimum_education_years'] ?? null) ?? '2');
    $minimumTrainingHours = (float)(cleanText($_POST['minimum_training_hours'] ?? null) ?? '4');
    $minimumExperienceYears = (float)(cleanText($_POST['minimum_experience_years'] ?? null) ?? '1');
    $threshold = (float)(cleanText($_POST['threshold'] ?? null) ?? '75');
    $ruleNotes = trim((string)(cleanText($_POST['rule_notes'] ?? null) ?? ''));

    if ($eligibility === '') {
        redirectWithState('error', 'Required eligibility is required.');
    }

    if ($minimumEducationYears < 0 || $minimumEducationYears > 20) {
        redirectWithState('error', 'Minimum education years must be between 0 and 20.');
    }

    if ($minimumTrainingHours < 0 || $minimumTrainingHours > 1000) {
        redirectWithState('error', 'Minimum training hours must be between 0 and 1000.');
    }

    if ($minimumExperienceYears < 0 || $minimumExperienceYears > 60) {
        redirectWithState('error', 'Minimum experience years must be between 0 and 60.');
    }

    if ($threshold < 0 || $threshold > 100) {
        redirectWithState('error', 'Scoring threshold must be between 0 and 100.');
    }

    $criteria = [
        'eligibility' => $eligibility,
        'minimum_education_years' => $minimumEducationYears,
        'minimum_training_hours' => $minimumTrainingHours,
        'minimum_experience_years' => $minimumExperienceYears,
        'threshold' => $threshold,
        'weights' => [
            'eligibility' => 25,
            'education' => 25,
            'training' => 25,
            'experience' => 25,
        ],
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

if ($action === 'save_position_criteria') {
    $positionId = trim((string)(cleanText($_POST['position_id'] ?? null) ?? ''));
    $eligibilityOption = evaluationNormalizeEligibilityOption((string)(cleanText($_POST['position_required_eligibility'] ?? null) ?? 'csc_prc'));
    $minimumEducationYears = (float)(cleanText($_POST['position_minimum_education_years'] ?? null) ?? '2');
    $minimumTrainingHours = (float)(cleanText($_POST['position_minimum_training_hours'] ?? null) ?? '4');
    $minimumExperienceYears = (float)(cleanText($_POST['position_minimum_experience_years'] ?? null) ?? '1');

    if ($positionId === '' || !preg_match('/^[a-f0-9-]{36}$/i', $positionId)) {
        redirectWithState('error', 'Select a valid position before saving criteria.');
    }

    if ($minimumEducationYears < 0 || $minimumEducationYears > 20) {
        redirectWithState('error', 'Minimum education years must be between 0 and 20.');
    }

    if ($minimumTrainingHours < 0 || $minimumTrainingHours > 1000) {
        redirectWithState('error', 'Minimum training hours must be between 0 and 1000.');
    }

    if ($minimumExperienceYears < 0 || $minimumExperienceYears > 60) {
        redirectWithState('error', 'Minimum experience years must be between 0 and 60.');
    }

    $globalCriteria = evaluationLoadCriteria($supabaseUrl, $headers);
    $positionCriteriaConfig = evaluationLoadPositionCriteriaConfig($supabaseUrl, $headers, $globalCriteria);
    $positionOverrides = (array)($positionCriteriaConfig['position_overrides'] ?? []);
    $normalizedPositionId = strtolower($positionId);

    $positionOverrides[$normalizedPositionId] = [
        'eligibility' => $eligibilityOption,
        'minimum_education_years' => $minimumEducationYears,
        'minimum_training_hours' => $minimumTrainingHours,
        'minimum_experience_years' => $minimumExperienceYears,
        'updated_at' => gmdate('c'),
        'updated_by' => $adminUserId,
    ];

    $payload = [
        'position_overrides' => $positionOverrides,
        'updated_at' => gmdate('c'),
    ];

    $saved = evaluationUpsertSetting($supabaseUrl, $headers, 'recruitment.position_criteria', $payload, $adminUserId);
    if (!$saved) {
        redirectWithState('error', 'Failed to save position-specific criteria.');
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
            'action_name' => 'save_position_criteria',
            'old_data' => null,
            'new_data' => [
                'position_id' => $positionId,
                'criteria' => $positionOverrides[$normalizedPositionId],
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Position-specific criteria saved successfully.');
}

if ($action === 'run_rule_evaluation') {
    $criteria = evaluationLoadCriteria($supabaseUrl, $headers);
    $positionCriteriaConfig = evaluationLoadPositionCriteriaConfig($supabaseUrl, $headers, $criteria);
    $dataset = evaluationBuildDataset($supabaseUrl, $headers);

    if (!empty($dataset['errors'])) {
        redirectWithState('error', implode(' ', (array)$dataset['errors']));
    }

    $result = evaluationRunRuleEngine($dataset, $criteria, $positionCriteriaConfig);
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
    $positionCriteriaConfig = evaluationLoadPositionCriteriaConfig($supabaseUrl, $headers, $criteria);
    $dataset = evaluationBuildDataset($supabaseUrl, $headers);

    if (!empty($dataset['errors'])) {
        redirectWithState('error', implode(' ', (array)$dataset['errors']));
    }

    $result = evaluationRunRuleEngine($dataset, $criteria, $positionCriteriaConfig);
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
            'total_score' => (int)($row['total_score'] ?? 0),
            'threshold' => (int)($row['threshold'] ?? 75),
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
