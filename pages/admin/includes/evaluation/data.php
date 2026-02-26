<?php

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$criteria = evaluationLoadCriteria($supabaseUrl, $headers);
$positionCriteriaConfig = evaluationLoadPositionCriteriaConfig($supabaseUrl, $headers, $criteria);
$positionOptions = evaluationLoadPositionOptions($supabaseUrl, $headers);
$positionCriteriaOverrides = (array)($positionCriteriaConfig['position_overrides'] ?? []);
$dataset = evaluationBuildDataset($supabaseUrl, $headers);
$result = evaluationRunRuleEngine($dataset, $criteria, $positionCriteriaConfig);

$evaluationRows = (array)($result['rows'] ?? []);
$recommendationSummary = (array)($result['summary'] ?? [
    'qualified' => 0,
    'not_qualified' => 0,
    'total' => 0,
]);

$dataLoadError = null;
if (!empty($dataset['errors'])) {
    $dataLoadError = implode(' ', (array)$dataset['errors']);
}

$lastRunSnapshot = evaluationReadSetting($supabaseUrl, $headers, 'evaluation.rule_based.last_run');
$recommendationSnapshot = evaluationReadSetting($supabaseUrl, $headers, 'evaluation.rule_based.recommendations');

$lastRunAt = '';
if (is_array($lastRunSnapshot) && !empty($lastRunSnapshot['generated_at'])) {
    $timestamp = strtotime((string)$lastRunSnapshot['generated_at']);
    if ($timestamp !== false) {
        $lastRunAt = date('M d, Y h:i A', $timestamp);
    }
}

$lastRecommendationAt = '';
if (is_array($recommendationSnapshot) && !empty($recommendationSnapshot['generated_at'])) {
    $timestamp = strtotime((string)$recommendationSnapshot['generated_at']);
    if ($timestamp !== false) {
        $lastRecommendationAt = date('M d, Y h:i A', $timestamp);
    }
}
