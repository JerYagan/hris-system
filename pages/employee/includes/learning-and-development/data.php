<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$learningSummary = [
    'available_count' => 0,
    'enrolled_count' => 0,
    'completed_count' => 0,
];

$availableTrainingRows = [];
$takenTrainingRows = [];
$upcomingTrainingAlerts = [];
$certificateAlerts = [];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$todayDate = date('Y-m-d');

$formatDateLabel = static function (?string $startDate, ?string $endDate): string {
    $start = cleanText($startDate);
    $end = cleanText($endDate);

    if ($start === null) {
        return '-';
    }

    $startTs = strtotime($start);
    if ($startTs === false) {
        return '-';
    }

    $label = date('M j, Y', $startTs);
    if ($end !== null) {
        $endTs = strtotime($end);
        if ($endTs !== false && date('Y-m-d', $endTs) !== date('Y-m-d', $startTs)) {
            $label .= ' - ' . date('M j, Y', $endTs);
        }
    }

    return $label;
};

$attendanceBadge = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'completed' => ['Present', 'bg-green-100 text-green-800'],
        'failed' => ['Absent', 'bg-red-100 text-red-800'],
        'dropped' => ['Dropped', 'bg-gray-200 text-gray-700'],
        default => ['Pending', 'bg-blue-100 text-blue-800'],
    };
};

$enrollmentBadge = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'completed' => ['Completed', 'bg-green-100 text-green-800'],
        'failed' => ['Failed', 'bg-red-100 text-red-800'],
        'dropped' => ['Dropped', 'bg-gray-200 text-gray-700'],
        default => ['Enrolled', 'bg-blue-100 text-blue-800'],
    };
};

$programsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_programs?select=id,title,training_type,training_category,start_date,end_date,provider,venue,mode,status,schedule_time'
    . '&status=in.(planned,open,ongoing)'
    . '&start_date=gte.' . rawurlencode($todayDate)
    . '&order=start_date.asc&limit=500',
    $headers
);

$trainingSchemaHasExtendedFields = true;
if (!isSuccessful($programsResponse)) {
    $programsFallbackResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/training_programs?select=id,title,start_date,end_date,provider,mode,status'
        . '&status=in.(planned,open,ongoing)'
        . '&start_date=gte.' . rawurlencode($todayDate)
        . '&order=start_date.asc&limit=500',
        $headers
    );

    if (isSuccessful($programsFallbackResponse)) {
        $programsResponse = $programsFallbackResponse;
        $trainingSchemaHasExtendedFields = false;
    }
}

$enrollmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_enrollments?select=id,program_id,enrollment_status,certificate_url,updated_at,program:training_programs(id,title,training_type,training_category,start_date,end_date,provider,venue,mode,status,schedule_time)'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=1000',
    $headers
);

if (!isSuccessful($enrollmentsResponse)) {
    $enrollmentsFallbackResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/training_enrollments?select=id,program_id,enrollment_status,certificate_url,updated_at,program:training_programs(id,title,start_date,end_date,provider,mode,status)'
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&order=updated_at.desc&limit=1000',
        $headers
    );

    if (isSuccessful($enrollmentsFallbackResponse)) {
        $enrollmentsResponse = $enrollmentsFallbackResponse;
    }
}

$programRows = isSuccessful($programsResponse) ? (array)($programsResponse['data'] ?? []) : [];
$enrollmentRows = isSuccessful($enrollmentsResponse) ? (array)($enrollmentsResponse['data'] ?? []) : [];

if (!isSuccessful($programsResponse) && !isSuccessful($enrollmentsResponse)) {
    $dataLoadError = 'Unable to load learning and development data right now.';
}

$enrolledProgramIds = [];
foreach ($enrollmentRows as $enrollmentRaw) {
    $enrollment = (array)$enrollmentRaw;
    $programId = cleanText($enrollment['program_id'] ?? null);
    if ($programId !== null) {
        $enrolledProgramIds[$programId] = true;
    }

    $statusRaw = strtolower((string)($enrollment['enrollment_status'] ?? 'enrolled'));
    [$attendanceLabel, $attendanceClass] = $attendanceBadge($statusRaw);
    [$enrollmentLabel, $enrollmentClass] = $enrollmentBadge($statusRaw);

    $program = (array)($enrollment['program'] ?? []);
    $title = (string)($program['title'] ?? 'Training Program');
    $trainingType = (string)($program['training_type'] ?? '-');
    $trainingCategory = (string)($program['training_category'] ?? '-');
    $provider = (string)($program['provider'] ?? '-');
    $startDate = cleanText($program['start_date'] ?? null);
    $endDate = cleanText($program['end_date'] ?? null);
    $certificateUrl = cleanText($enrollment['certificate_url'] ?? null);

    if (!$trainingSchemaHasExtendedFields && str_contains($title, ' - ')) {
        $parts = explode(' - ', $title, 2);
        if ($trainingType === '-' && !empty($parts[0])) {
            $trainingType = trim((string)$parts[0]);
        }
        if ($trainingCategory === '-' && !empty($parts[1])) {
            $trainingCategory = trim((string)$parts[1]);
        }
    }

    $takenTrainingRows[] = [
        'title' => $title,
        'training_type' => $trainingType,
        'training_category' => $trainingCategory,
        'date_label' => $formatDateLabel($startDate, $endDate),
        'provider' => $provider,
        'attendance_label' => $attendanceLabel,
        'attendance_class' => $attendanceClass,
        'enrollment_status_raw' => $statusRaw,
        'enrollment_status_label' => $enrollmentLabel,
        'enrollment_status_class' => $enrollmentClass,
        'certificate_url' => $certificateUrl,
        'search_text' => strtolower(trim($title . ' ' . $trainingType . ' ' . $trainingCategory . ' ' . $provider . ' ' . $attendanceLabel . ' ' . $enrollmentLabel)),
    ];

    $learningSummary['enrolled_count']++;
    if ($statusRaw === 'completed') {
        $learningSummary['completed_count']++;
    }

    if ($startDate !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) === 1 && $startDate >= $todayDate && $statusRaw === 'enrolled') {
        $upcomingTrainingAlerts[] = [
            'title' => $title,
            'meta' => $formatDateLabel($startDate, $endDate) . ' · ' . $provider,
        ];
    }

    if ($statusRaw === 'completed') {
        $certificateAlerts[] = [
            'title' => $title,
            'meta' => 'Completion recorded',
            'certificate_url' => $certificateUrl,
        ];
    }
}

foreach ($programRows as $programRaw) {
    $program = (array)$programRaw;

    $programId = cleanText($program['id'] ?? null);
    if ($programId === null) {
        continue;
    }

    $title = (string)($program['title'] ?? 'Training Program');
    $trainingType = (string)($program['training_type'] ?? '-');
    $trainingCategory = (string)($program['training_category'] ?? '-');
    $provider = (string)($program['provider'] ?? '-');
    $startDate = cleanText($program['start_date'] ?? null);
    $endDate = cleanText($program['end_date'] ?? null);
    $statusRaw = strtolower((string)($program['status'] ?? 'planned'));

    if (!$trainingSchemaHasExtendedFields && str_contains($title, ' - ')) {
        $parts = explode(' - ', $title, 2);
        if ($trainingType === '-' && !empty($parts[0])) {
            $trainingType = trim((string)$parts[0]);
        }
        if ($trainingCategory === '-' && !empty($parts[1])) {
            $trainingCategory = trim((string)$parts[1]);
        }
    }

    $isEnrolled = isset($enrolledProgramIds[$programId]);
    if (!$isEnrolled) {
        $learningSummary['available_count']++;
    }

    $availableTrainingRows[] = [
        'program_id' => $programId,
        'title' => $title,
        'training_type' => $trainingType,
        'training_category' => $trainingCategory,
        'date_label' => $formatDateLabel($startDate, $endDate),
        'provider' => $provider,
        'status_raw' => $statusRaw,
        'is_enrolled' => $isEnrolled,
        'search_text' => strtolower(trim($title . ' ' . $trainingType . ' ' . $trainingCategory . ' ' . $provider . ' ' . $statusRaw)),
    ];
}

usort($takenTrainingRows, static function (array $left, array $right): int {
    return strcmp((string)$left['title'], (string)$right['title']);
});

$upcomingTrainingAlerts = array_slice($upcomingTrainingAlerts, 0, 3);
$certificateAlerts = array_slice($certificateAlerts, 0, 5);
