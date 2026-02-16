<?php

$cyclesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_cycles?select=id,cycle_name,period_start,period_end,status,created_at&order=period_start.desc&limit=500',
    $headers
);

$evaluationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_evaluations?select=id,cycle_id,employee_person_id,evaluator_user_id,final_rating,remarks,status,updated_at,employee:employee_person_id(first_name,surname),evaluator:evaluator_user_id(email),cycle:cycle_id(cycle_name,period_start,period_end)&order=updated_at.desc&limit=1500',
    $headers
);

$cycles = isSuccessful($cyclesResponse) ? (array)($cyclesResponse['data'] ?? []) : [];
$evaluations = isSuccessful($evaluationsResponse) ? (array)($evaluationsResponse['data'] ?? []) : [];

$uniqueCycles = [];
$seenCycleKeys = [];
foreach ($cycles as $cycle) {
    $cycleKey = strtolower(trim(
        (string)($cycle['cycle_name'] ?? '')
        . '|' . (string)($cycle['period_start'] ?? '')
        . '|' . (string)($cycle['period_end'] ?? '')
    ));

    if ($cycleKey === '' || isset($seenCycleKeys[$cycleKey])) {
        continue;
    }

    $seenCycleKeys[$cycleKey] = true;
    $uniqueCycles[] = $cycle;
}
$cycles = $uniqueCycles;

if (!function_exists('employeeEvalStatusPill')) {
    function employeeEvalStatusPill(string $status): array
    {
        $key = strtolower(trim($status));
        if ($key === 'approved') {
            return ['Approved', 'bg-emerald-100 text-emerald-800'];
        }
        if ($key === 'submitted') {
            return ['Pending Approval', 'bg-amber-100 text-amber-800'];
        }
        if ($key === 'reviewed') {
            return ['Returned for Review', 'bg-blue-100 text-blue-800'];
        }

        return [ucfirst($key !== '' ? $key : 'draft'), 'bg-slate-100 text-slate-700'];
    }
}

$ratingsToApproveRows = [];
$overallRatingRows = [];
$outstandingCount = 0;
$verySatisfactoryCount = 0;
$needsCoachingCount = 0;
$seenPendingRatings = [];
$seenOverallRatings = [];
$approvedQuarterMap = [];

foreach ($evaluations as $evaluation) {
    $evaluationId = (string)($evaluation['id'] ?? '');
    $employeeName = trim(((string)($evaluation['employee']['first_name'] ?? '')) . ' ' . ((string)($evaluation['employee']['surname'] ?? '')));
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $supervisor = (string)($evaluation['evaluator']['email'] ?? 'Unassigned');
    $ratingValue = (float)($evaluation['final_rating'] ?? 0);
    $ratingLabel = $ratingValue > 0 ? number_format($ratingValue, 2) . ' / 5.00' : '-';
    $statusRaw = (string)($evaluation['status'] ?? 'draft');
    [$statusLabel, $statusClass] = employeeEvalStatusPill($statusRaw);
    $cycleName = (string)($evaluation['cycle']['cycle_name'] ?? '-');

    if (in_array(strtolower($statusRaw), ['submitted', 'reviewed', 'approved'], true)) {
        $pendingKey = strtolower(trim($employeeName . '|' . $supervisor . '|' . $cycleName . '|' . $statusRaw));
        if (isset($seenPendingRatings[$pendingKey])) {
            continue;
        }
        $seenPendingRatings[$pendingKey] = true;

        $ratingsToApproveRows[] = [
            'evaluation_id' => $evaluationId,
            'employee' => $employeeName,
            'supervisor' => $supervisor,
            'rating_label' => $ratingLabel,
            'cycle_name' => $cycleName,
            'status_raw' => strtolower($statusRaw),
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'search_text' => strtolower(trim($employeeName . ' ' . $supervisor . ' ' . $cycleName . ' ' . $ratingLabel . ' ' . $statusLabel)),
        ];
    }

    if (strtolower($statusRaw) === 'approved' && $ratingValue > 0) {
        $overallKey = strtolower(trim($employeeName . '|' . $cycleName));
        if (isset($seenOverallRatings[$overallKey])) {
            continue;
        }
        $seenOverallRatings[$overallKey] = true;

        if ($ratingValue >= 4.5) {
            $band = 'Outstanding';
            $outstandingCount++;
        } elseif ($ratingValue >= 3.5) {
            $band = 'Very Satisfactory';
            $verySatisfactoryCount++;
        } else {
            $band = 'Needs Coaching';
            $needsCoachingCount++;
        }

        $overallRatingRows[] = [
            'employee' => $employeeName,
            'cycle_name' => $cycleName,
            'rating_label' => $ratingLabel,
            'band' => $band,
        ];

        $cyclePeriodEnd = (string)($evaluation['cycle']['period_end'] ?? '');
        $cyclePeriodStart = (string)($evaluation['cycle']['period_start'] ?? '');
        $quarterDateSource = $cyclePeriodEnd !== '' ? $cyclePeriodEnd : ($cyclePeriodStart !== '' ? $cyclePeriodStart : (string)($evaluation['updated_at'] ?? ''));

        $quarterTimestamp = strtotime($quarterDateSource);
        if ($quarterTimestamp === false) {
            $quarterTimestamp = strtotime((string)($evaluation['updated_at'] ?? ''));
        }

        if ($quarterTimestamp !== false) {
            $year = (int)date('Y', $quarterTimestamp);
            $month = (int)date('n', $quarterTimestamp);
            $quarter = (int)floor(($month - 1) / 3) + 1;
            $quarterKey = $year . '-Q' . $quarter;

            if (!isset($approvedQuarterMap[$quarterKey])) {
                $approvedQuarterMap[$quarterKey] = [
                    'quarter_label' => 'Q' . $quarter . ' ' . $year,
                    'approved_employees' => [],
                    'ratings' => [],
                    'latest_cycle' => $cycleName,
                    'sort_year' => $year,
                    'sort_quarter' => $quarter,
                ];
            }

            $approvedQuarterMap[$quarterKey]['approved_employees'][$employeeName] = true;
            $approvedQuarterMap[$quarterKey]['ratings'][] = $ratingValue;
            $approvedQuarterMap[$quarterKey]['latest_cycle'] = $cycleName !== '' ? $cycleName : $approvedQuarterMap[$quarterKey]['latest_cycle'];
        }
    }
}

$approvedPerQuarterRows = [];
foreach ($approvedQuarterMap as $quarterData) {
    $employees = array_keys((array)($quarterData['approved_employees'] ?? []));
    sort($employees);

    $ratings = (array)($quarterData['ratings'] ?? []);
    $averageRating = 0;
    if (!empty($ratings)) {
        $averageRating = array_sum($ratings) / max(1, count($ratings));
    }

    $approvedPerQuarterRows[] = [
        'quarter_label' => (string)($quarterData['quarter_label'] ?? '-'),
        'approved_count' => count($employees),
        'average_rating_label' => $averageRating > 0 ? number_format($averageRating, 2) . ' / 5.00' : '-',
        'latest_cycle' => (string)($quarterData['latest_cycle'] ?? '-'),
        'employee_preview' => empty($employees) ? '-' : implode(', ', array_slice($employees, 0, 5)) . (count($employees) > 5 ? ' +' . (count($employees) - 5) . ' more' : ''),
        'sort_year' => (int)($quarterData['sort_year'] ?? 0),
        'sort_quarter' => (int)($quarterData['sort_quarter'] ?? 0),
    ];
}

usort($approvedPerQuarterRows, static function (array $left, array $right): int {
    if ((int)$left['sort_year'] !== (int)$right['sort_year']) {
        return (int)$right['sort_year'] <=> (int)$left['sort_year'];
    }
    return (int)$right['sort_quarter'] <=> (int)$left['sort_quarter'];
});
