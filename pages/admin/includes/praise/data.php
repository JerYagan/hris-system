<?php

$cyclesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_cycles?select=id,cycle_name,period_start,period_end,status,created_at&order=period_start.desc&limit=500',
    $headers
);

$evaluationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_evaluations?select=id,cycle_id,employee_person_id,evaluator_user_id,final_rating,remarks,status,updated_at,employee:employee_person_id(first_name,surname),evaluator:evaluator_user_id(email),cycle:cycle_id(cycle_name)&order=updated_at.desc&limit=1500',
    $headers
);

$awardsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/praise_awards?select=id,award_code,award_name,description,criteria,is_active,created_at&order=created_at.desc&limit=500',
    $headers
);

$nominationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/praise_nominations?select=id,award_id,nominee_person_id,nominated_by_user_id,cycle_id,justification,status,reviewed_at,award:award_id(award_name),nominee:nominee_person_id(first_name,surname),nominator:nominated_by_user_id(email),cycle:cycle_id(cycle_name)&order=created_at.desc&limit=1500',
    $headers
);

$cycles = isSuccessful($cyclesResponse) ? (array)($cyclesResponse['data'] ?? []) : [];
$evaluations = isSuccessful($evaluationsResponse) ? (array)($evaluationsResponse['data'] ?? []) : [];
$awards = isSuccessful($awardsResponse) ? (array)($awardsResponse['data'] ?? []) : [];
$nominations = isSuccessful($nominationsResponse) ? (array)($nominationsResponse['data'] ?? []) : [];

if (!function_exists('praiseEvalStatusPill')) {
    function praiseEvalStatusPill(string $status): array
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

if (!function_exists('praiseNominationStatusPill')) {
    function praiseNominationStatusPill(string $status): array
    {
        $key = strtolower(trim($status));
        if ($key === 'approved') {
            return ['Approved', 'bg-emerald-100 text-emerald-800'];
        }
        if ($key === 'rejected') {
            return ['Rejected', 'bg-rose-100 text-rose-800'];
        }
        return ['Pending', 'bg-amber-100 text-amber-800'];
    }
}

$evaluationCycleOptions = [];
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

foreach ($cycles as $cycle) {
    $cycleId = (string)($cycle['id'] ?? '');
    $cycleName = (string)($cycle['cycle_name'] ?? '');
    if ($cycleId === '' || $cycleName === '') {
        continue;
    }
    $evaluationCycleOptions[] = ['id' => $cycleId, 'name' => $cycleName];
}

$ratingsToApproveRows = [];
$overallRatingRows = [];
$outstandingCount = 0;
$verySatisfactoryCount = 0;
$needsCoachingCount = 0;
$seenRatingRows = [];
$seenOverallRows = [];

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
    [$statusLabel, $statusClass] = praiseEvalStatusPill($statusRaw);
    $cycleName = (string)($evaluation['cycle']['cycle_name'] ?? '-');

    if (in_array(strtolower($statusRaw), ['submitted', 'reviewed', 'approved'], true)) {
        $rowKey = strtolower(trim($employeeName . '|' . $supervisor . '|' . $cycleName . '|' . $statusRaw));
        if (isset($seenRatingRows[$rowKey])) {
            continue;
        }
        $seenRatingRows[$rowKey] = true;

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
        if (isset($seenOverallRows[$overallKey])) {
            continue;
        }
        $seenOverallRows[$overallKey] = true;

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
            'search_text' => strtolower(trim($employeeName . ' ' . $cycleName . ' ' . $ratingLabel . ' ' . $band)),
        ];
    }
}

$awardCategoryRows = [];
foreach ($awards as $award) {
    $awardId = (string)($award['id'] ?? '');
    $awardName = (string)($award['award_name'] ?? '-');
    $awardCategoryRows[] = [
        'award_id' => $awardId,
        'award_name' => $awardName,
        'award_code' => (string)($award['award_code'] ?? '-'),
        'description' => (string)($award['description'] ?? ''),
        'criteria' => (string)($award['criteria'] ?? '-'),
        'is_active' => (bool)($award['is_active'] ?? false),
        'created_at' => (string)($award['created_at'] ?? ''),
    ];
}

$nominationApprovalRows = [];
$publishAwardeeRows = [];
$recognitionHistoryRows = [];
$seenPendingNominations = [];
$seenPublishAwardees = [];

foreach ($nominations as $nomination) {
    $nominationId = (string)($nomination['id'] ?? '');
    $awardName = (string)($nomination['award']['award_name'] ?? '-');
    $cycleName = (string)($nomination['cycle']['cycle_name'] ?? '-');
    $nomineeName = trim(((string)($nomination['nominee']['first_name'] ?? '')) . ' ' . ((string)($nomination['nominee']['surname'] ?? '')));
    if ($nomineeName === '') {
        $nomineeName = 'Unknown Employee';
    }

    $nominatedBy = (string)($nomination['nominator']['email'] ?? 'N/A');
    $statusRaw = strtolower((string)($nomination['status'] ?? 'pending'));
    [$statusLabel, $statusClass] = praiseNominationStatusPill($statusRaw);

    $searchText = strtolower(trim($nomineeName . ' ' . $awardName . ' ' . $nominatedBy . ' ' . $cycleName . ' ' . $statusLabel));
    $rowKey = strtolower(trim($nomineeName . '|' . $awardName . '|' . $cycleName));

    if ($statusRaw === 'pending') {
        if (isset($seenPendingNominations[$rowKey])) {
            continue;
        }
        $seenPendingNominations[$rowKey] = true;

        $nominationApprovalRows[] = [
            'nomination_id' => $nominationId,
            'nominee' => $nomineeName,
            'award_name' => $awardName,
            'nominated_by' => $nominatedBy,
            'cycle_name' => $cycleName,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'search_text' => $searchText,
        ];
    }

    if ($statusRaw === 'approved') {
        if (isset($seenPublishAwardees[$rowKey])) {
            continue;
        }
        $seenPublishAwardees[$rowKey] = true;

        $reviewedAt = (string)($nomination['reviewed_at'] ?? '');
        $publishedLabel = $reviewedAt !== '' ? date('M d, Y', strtotime($reviewedAt)) : '-';

        $publishAwardeeRows[] = [
            'nomination_id' => $nominationId,
            'nominee' => $nomineeName,
            'award_name' => $awardName,
            'cycle_name' => $cycleName,
            'published_date' => $publishedLabel,
            'search_text' => strtolower(trim($nomineeName . ' ' . $awardName . ' ' . $cycleName . ' ' . $publishedLabel)),
        ];

        $recognitionHistoryRows[] = [
            'cycle_name' => $cycleName,
            'award_name' => $awardName,
            'awardee' => $nomineeName,
            'published_date' => $publishedLabel,
            'search_text' => strtolower(trim($cycleName . ' ' . $awardName . ' ' . $nomineeName . ' ' . $publishedLabel)),
        ];
    }
}

$performanceSummaryRows = [];
foreach ($cycles as $cycle) {
    $cycleId = (string)($cycle['id'] ?? '');
    $cycleName = (string)($cycle['cycle_name'] ?? '-');

    $cycleEvaluations = array_values(array_filter($evaluations, static function (array $evaluation) use ($cycleId): bool {
        return (string)($evaluation['cycle_id'] ?? '') === $cycleId;
    }));

    if (empty($cycleEvaluations)) {
        $performanceSummaryRows[] = [
            'period' => $cycleName,
            'evaluated_employees' => 0,
            'average_rating' => '-',
            'top_category' => 'No Data',
        ];
        continue;
    }

    $approvedRatings = [];
    $approvedEmployees = [];
    $bandCounts = ['Outstanding' => 0, 'Very Satisfactory' => 0, 'Needs Coaching' => 0];

    foreach ($cycleEvaluations as $evaluation) {
        if (strtolower((string)($evaluation['status'] ?? '')) !== 'approved') {
            continue;
        }

        $rating = (float)($evaluation['final_rating'] ?? 0);
        if ($rating <= 0) {
            continue;
        }

        $employeeId = (string)($evaluation['employee_person_id'] ?? '');
        if ($employeeId !== '') {
            $approvedEmployees[$employeeId] = true;
        }

        $approvedRatings[] = $rating;

        if ($rating >= 4.5) {
            $bandCounts['Outstanding']++;
        } elseif ($rating >= 3.5) {
            $bandCounts['Very Satisfactory']++;
        } else {
            $bandCounts['Needs Coaching']++;
        }
    }

    $averageRating = !empty($approvedRatings)
        ? number_format(array_sum($approvedRatings) / count($approvedRatings), 2) . ' / 5.00'
        : '-';

    if (empty($approvedRatings)) {
        $topCategory = 'No Data';
    } else {
        arsort($bandCounts);
        $topCategory = (string)array_key_first($bandCounts);
    }

    $performanceSummaryRows[] = [
        'period' => $cycleName,
        'evaluated_employees' => count($approvedEmployees),
        'average_rating' => $averageRating,
        'top_category' => $topCategory,
    ];
}
