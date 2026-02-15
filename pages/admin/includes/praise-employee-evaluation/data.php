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
    }
}
