<?php

$evaluationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_evaluations?select=id,cycle_id,final_rating,status,cycle:cycle_id(cycle_name)&order=created_at.desc&limit=5000',
    $headers
);

$nominationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/praise_nominations?select=id,status,reviewed_at,award:award_id(award_name),nominee:nominee_person_id(first_name,surname),cycle:cycle_id(cycle_name)&status=eq.approved&order=reviewed_at.desc&limit=2000',
    $headers
);

$evaluations = isSuccessful($evaluationsResponse) ? (array)($evaluationsResponse['data'] ?? []) : [];
$approvedNominations = isSuccessful($nominationsResponse) ? (array)($nominationsResponse['data'] ?? []) : [];

$performanceBuckets = [];

foreach ($evaluations as $evaluation) {
    $cycleName = trim((string)($evaluation['cycle']['cycle_name'] ?? ''));
    $period = $cycleName !== '' ? $cycleName : 'Unassigned Cycle';

    if (!isset($performanceBuckets[$period])) {
        $performanceBuckets[$period] = [
            'period' => $period,
            'count' => 0,
            'rating_sum' => 0.0,
            'rating_count' => 0,
            'bands' => [
                'Outstanding' => 0,
                'Very Satisfactory' => 0,
                'Satisfactory' => 0,
                'Needs Improvement' => 0,
            ],
        ];
    }

    $performanceBuckets[$period]['count']++;

    $finalRating = (float)($evaluation['final_rating'] ?? 0);
    if ($finalRating > 0) {
        $performanceBuckets[$period]['rating_sum'] += $finalRating;
        $performanceBuckets[$period]['rating_count']++;

        if ($finalRating >= 4.5) {
            $performanceBuckets[$period]['bands']['Outstanding']++;
        } elseif ($finalRating >= 3.5) {
            $performanceBuckets[$period]['bands']['Very Satisfactory']++;
        } elseif ($finalRating >= 2.5) {
            $performanceBuckets[$period]['bands']['Satisfactory']++;
        } else {
            $performanceBuckets[$period]['bands']['Needs Improvement']++;
        }
    }
}

$performanceReportRows = [];
foreach ($performanceBuckets as $bucket) {
    $average = $bucket['rating_count'] > 0 ? ($bucket['rating_sum'] / $bucket['rating_count']) : 0;

    arsort($bucket['bands']);
    $topCategory = (string)array_key_first($bucket['bands']);
    if (($bucket['bands'][$topCategory] ?? 0) <= 0) {
        $topCategory = 'N/A';
    }

    $performanceReportRows[] = [
        'period' => $bucket['period'],
        'evaluated_employees' => (int)$bucket['count'],
        'average_rating' => $average > 0 ? number_format($average, 2) . ' / 5.00' : 'N/A',
        'top_category' => $topCategory,
        'search_text' => strtolower(trim($bucket['period'] . ' ' . $topCategory)),
    ];
}

usort($performanceReportRows, static fn(array $left, array $right): int => strcmp((string)$right['period'], (string)$left['period']));

$recognitionHistoryRows = [];
$seenRecognitionRows = [];
foreach ($approvedNominations as $nomination) {
    $cycleName = (string)($nomination['cycle']['cycle_name'] ?? 'Unassigned Cycle');
    $awardName = (string)($nomination['award']['award_name'] ?? '-');
    $awardee = trim(((string)($nomination['nominee']['first_name'] ?? '')) . ' ' . ((string)($nomination['nominee']['surname'] ?? '')));
    if ($awardee === '') {
        $awardee = 'Unknown Employee';
    }

    $reviewedAt = (string)($nomination['reviewed_at'] ?? '');
    $publishedDate = $reviewedAt !== '' ? date('M d, Y', strtotime($reviewedAt)) : '-';
    $recognitionKey = strtolower(trim($cycleName . '|' . $awardName . '|' . $awardee . '|' . $publishedDate));
    if (isset($seenRecognitionRows[$recognitionKey])) {
        continue;
    }
    $seenRecognitionRows[$recognitionKey] = true;

    $recognitionHistoryRows[] = [
        'cycle_name' => $cycleName,
        'award_name' => $awardName,
        'awardee' => $awardee,
        'published_date' => $publishedDate,
        'search_text' => strtolower(trim($cycleName . ' ' . $awardName . ' ' . $awardee)),
    ];
}
