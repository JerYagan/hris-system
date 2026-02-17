<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$employeeDisplayName = 'Employee';
$praiseSummary = [
    'total_evaluations' => 0,
    'latest_rating' => '-',
    'total_nominations' => 0,
    'approved_nominations' => 0,
    'completed_trainings' => 0,
];

$employeeEvaluations = [];
$employeeSelfEvaluations = [];
$employeeNominations = [];
$employeeAwards = [];
$employeeTrainingCompletions = [];
$openPerformanceCycles = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (isSuccessful($personResponse) && !empty((array)($personResponse['data'] ?? []))) {
    $person = (array)$personResponse['data'][0];
    $employeeDisplayName = trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['surname'] ?? ''));
    if ($employeeDisplayName === '') {
        $employeeDisplayName = 'Employee';
    }
}

$evaluationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,final_rating,remarks,status,created_at,cycle:performance_cycles(cycle_name,period_start,period_end,status),evaluator:user_accounts(email)'
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($evaluationResponse)) {
    foreach ((array)($evaluationResponse['data'] ?? []) as $evaluationRaw) {
        $evaluation = (array)$evaluationRaw;
        $cycle = (array)($evaluation['cycle'] ?? []);
        $evaluator = (array)($evaluation['evaluator'] ?? []);
        $remarks = (string)($evaluation['remarks'] ?? '');
        $remarksPreview = trim($remarks) !== '' ? mb_substr(trim($remarks), 0, 160) : 'No supervisor comments provided.';
        if (mb_strlen(trim($remarks)) > 160) {
            $remarksPreview .= '…';
        }

        $employeeEvaluations[] = [
            'id' => (string)($evaluation['id'] ?? ''),
            'cycle_name' => (string)($cycle['cycle_name'] ?? 'Performance Cycle'),
            'period_start' => (string)($cycle['period_start'] ?? ''),
            'period_end' => (string)($cycle['period_end'] ?? ''),
            'cycle_status' => strtolower((string)($cycle['status'] ?? 'draft')),
            'final_rating' => cleanText($evaluation['final_rating'] ?? null),
            'remarks' => $remarks,
            'remarks_preview' => $remarksPreview,
            'status' => strtolower((string)($evaluation['status'] ?? 'draft')),
            'evaluator_email' => (string)($evaluator['email'] ?? ''),
            'created_at' => (string)($evaluation['created_at'] ?? ''),
        ];
    }
}

$selfEvaluationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,final_rating,remarks,status,created_at,cycle_id,cycle:performance_cycles(cycle_name,period_start,period_end,status)'
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&evaluator_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($selfEvaluationResponse)) {
    foreach ((array)($selfEvaluationResponse['data'] ?? []) as $selfEvalRaw) {
        $selfEval = (array)$selfEvalRaw;
        $cycle = (array)($selfEval['cycle'] ?? []);

        $employeeSelfEvaluations[] = [
            'id' => (string)($selfEval['id'] ?? ''),
            'cycle_id' => (string)($selfEval['cycle_id'] ?? ''),
            'cycle_name' => (string)($cycle['cycle_name'] ?? 'Performance Cycle'),
            'period_start' => (string)($cycle['period_start'] ?? ''),
            'period_end' => (string)($cycle['period_end'] ?? ''),
            'cycle_status' => strtolower((string)($cycle['status'] ?? 'draft')),
            'final_rating' => cleanText($selfEval['final_rating'] ?? null),
            'remarks' => (string)($selfEval['remarks'] ?? ''),
            'status' => strtolower((string)($selfEval['status'] ?? 'submitted')),
            'created_at' => (string)($selfEval['created_at'] ?? ''),
        ];
    }
}

$openCyclesResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_cycles?select=id,cycle_name,period_start,period_end,status'
    . '&status=eq.open'
    . '&order=period_start.desc&limit=20',
    $headers
);

if (isSuccessful($openCyclesResponse)) {
    foreach ((array)($openCyclesResponse['data'] ?? []) as $cycleRaw) {
        $cycle = (array)$cycleRaw;
        $openPerformanceCycles[] = [
            'id' => (string)($cycle['id'] ?? ''),
            'cycle_name' => (string)($cycle['cycle_name'] ?? 'Performance Cycle'),
            'period_start' => (string)($cycle['period_start'] ?? ''),
            'period_end' => (string)($cycle['period_end'] ?? ''),
            'status' => strtolower((string)($cycle['status'] ?? 'open')),
        ];
    }
}

$nominationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/praise_nominations?select=id,justification,status,created_at,updated_at,reviewed_at,award:praise_awards(id,award_name,description),cycle:performance_cycles(cycle_name)'
    . '&nominee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=50',
    $headers
);

if (isSuccessful($nominationResponse)) {
    foreach ((array)($nominationResponse['data'] ?? []) as $nominationRaw) {
        $nomination = (array)$nominationRaw;
        $award = (array)($nomination['award'] ?? []);
        $cycle = (array)($nomination['cycle'] ?? []);

        $status = strtolower((string)($nomination['status'] ?? 'pending'));

        $employeeNominations[] = [
            'id' => (string)($nomination['id'] ?? ''),
            'award_id' => (string)($award['id'] ?? ''),
            'award_name' => (string)($award['award_name'] ?? 'PRAISE Award'),
            'award_description' => (string)($award['description'] ?? ''),
            'cycle_name' => (string)($cycle['cycle_name'] ?? 'General Cycle'),
            'justification' => (string)($nomination['justification'] ?? ''),
            'status' => $status,
            'created_at' => (string)($nomination['created_at'] ?? ''),
            'reviewed_at' => (string)($nomination['reviewed_at'] ?? ''),
            'updated_at' => (string)($nomination['updated_at'] ?? ''),
        ];

        if ($status === 'approved') {
            $praiseSummary['approved_nominations']++;

            $receivedAt = cleanText($nomination['reviewed_at'] ?? null)
                ?? cleanText($nomination['updated_at'] ?? null)
                ?? cleanText($nomination['created_at'] ?? null);

            $employeeAwards[] = [
                'award_id' => (string)($award['id'] ?? ''),
                'award_name' => (string)($award['award_name'] ?? 'PRAISE Award'),
                'award_description' => (string)($award['description'] ?? ''),
                'cycle_name' => (string)($cycle['cycle_name'] ?? 'General Cycle'),
                'received_at' => $receivedAt,
            ];
        }
    }
}

$trainingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_enrollments?select=id,enrollment_status,score,updated_at,program:training_programs(title,start_date,end_date,mode,status)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=50',
    $headers
);

if (isSuccessful($trainingResponse)) {
    foreach ((array)($trainingResponse['data'] ?? []) as $trainingRaw) {
        $training = (array)$trainingRaw;
        $program = (array)($training['program'] ?? []);
        $enrollmentStatus = strtolower((string)($training['enrollment_status'] ?? 'enrolled'));

        $employeeTrainingCompletions[] = [
            'id' => (string)($training['id'] ?? ''),
            'program_title' => (string)($program['title'] ?? 'Training Program'),
            'start_date' => (string)($program['start_date'] ?? ''),
            'end_date' => (string)($program['end_date'] ?? ''),
            'mode' => (string)($program['mode'] ?? ''),
            'program_status' => strtolower((string)($program['status'] ?? 'planned')),
            'enrollment_status' => $enrollmentStatus,
            'score' => cleanText($training['score'] ?? null),
            'updated_at' => (string)($training['updated_at'] ?? ''),
        ];

        if ($enrollmentStatus === 'completed') {
            $praiseSummary['completed_trainings']++;
        }
    }
}

$praiseSummary['total_evaluations'] = count($employeeEvaluations);
$praiseSummary['total_nominations'] = count($employeeNominations);
if (!empty($employeeEvaluations)) {
    $latest = (array)$employeeEvaluations[0];
    $latestRating = cleanText($latest['final_rating'] ?? null);
    $praiseSummary['latest_rating'] = $latestRating !== null ? $latestRating : '-';
}
