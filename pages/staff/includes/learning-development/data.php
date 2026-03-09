<?php

$learningDataLoadError = null;

$appendLearningDataError = static function (string $label, array $response) use (&$learningDataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $learningDataLoadError = $learningDataLoadError ? ($learningDataLoadError . ' ' . $message) : $message;
};

$isAdminScope = strtolower((string)$staffRoleKey) === 'admin';
$officeScopedFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$courseDbToVirtual = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'planned' => 'draft',
        'open', 'ongoing' => 'published',
        'completed', 'cancelled' => 'archived',
        default => 'draft',
    };
};

$enrollmentDbToVirtual = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'enrolled' => 'pending',
        'completed' => 'approved',
        'failed', 'dropped' => 'rejected',
        default => 'pending',
    };
};

$trainingRecordStatusFromDb = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'completed' => 'Present',
        'failed' => 'Absent',
        'dropped' => 'Dropped',
        default => 'Enrolled',
    };
};

$formatTrainingDate = static function (string $value): string {
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($trimmed))->format('F j, Y');
    } catch (Throwable $exception) {
        return $trimmed;
    }
};

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
        . '/rest/v1/employment_records?select=person_id,office_id,is_current,person:people!employment_records_person_id_fkey(first_name,middle_name,surname)'
        . $officeScopedFilter
        . '&limit=5000',
    $headers
);
$appendLearningDataError('Employment records', $employmentResponse);
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname&limit=5000',
    $headers
);
$appendLearningDataError('People', $peopleResponse);
$peopleRows = isSuccessful($peopleResponse) ? (array)($peopleResponse['data'] ?? []) : [];

$peopleById = [];
foreach ($peopleRows as $peopleRow) {
    $personId = cleanText($peopleRow['id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $name = trim(
        (string)(cleanText($peopleRow['first_name'] ?? null) ?? '') . ' '
        . (string)(cleanText($peopleRow['middle_name'] ?? null) ?? '') . ' '
        . (string)(cleanText($peopleRow['surname'] ?? null) ?? '')
    );

    $peopleById[$personId] = $name !== '' ? $name : 'Unknown Employee';
}

$officeResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&limit=1000',
    $headers
);
$appendLearningDataError('Offices', $officeResponse);
$officeRows = isSuccessful($officeResponse) ? (array)($officeResponse['data'] ?? []) : [];

$officeNameById = [];
foreach ($officeRows as $officeRow) {
    $officeId = cleanText($officeRow['id'] ?? null) ?? '';
    if (!isValidUuid($officeId)) {
        continue;
    }

    $officeNameById[$officeId] = cleanText($officeRow['office_name'] ?? null) ?? 'Unassigned Division';
}

$employeeByPersonId = [];
$employeeOptions = [];
foreach ($employmentRows as $employmentRow) {
    $personId = cleanText($employmentRow['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $person = (array)($employmentRow['person'] ?? []);
    $name = trim(
        (string)(cleanText($person['first_name'] ?? null) ?? '') . ' '
        . (string)(cleanText($person['middle_name'] ?? null) ?? '') . ' '
        . (string)(cleanText($person['surname'] ?? null) ?? '')
    );

    $officeId = cleanText($employmentRow['office_id'] ?? null) ?? '';
    $department = $officeNameById[$officeId] ?? 'Unassigned Division';

    $isCurrent = filter_var($employmentRow['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (isset($employeeByPersonId[$personId])) {
        $existingCurrent = (bool)($employeeByPersonId[$personId]['is_current'] ?? false);
        if ($existingCurrent || !$isCurrent) {
            continue;
        }
    }

    $employeeByPersonId[$personId] = [
        'name' => $name !== '' ? $name : 'Unknown Employee',
        'department' => $department,
        'is_current' => $isCurrent,
    ];

    $employeeOptions[] = [
        'person_id' => $personId,
        'name' => $name !== '' ? $name : 'Unknown Employee',
        'department' => $department,
    ];
}

usort($employeeOptions, static fn (array $a, array $b): int => strcmp((string)$a['name'], (string)$b['name']));

$courseResponse = apiRequest(
    'GET',
    $supabaseUrl
        . '/rest/v1/training_programs?select=id,title,provider,training_type,training_category,venue,start_date,end_date,mode,status,created_at,updated_at'
        . '&order=updated_at.desc.nullslast,created_at.desc'
        . '&limit=5000',
    $headers
);
$appendLearningDataError('Training programs', $courseResponse);
$courseRecords = isSuccessful($courseResponse) ? (array)($courseResponse['data'] ?? []) : [];

$courseById = [];
$courseRows = [];
$courseEnrolleesByCourseId = [];
$courseStatusFilters = [];
$courseCounts = [
    'total' => 0,
    'draft' => 0,
    'published' => 0,
    'archived' => 0,
];

foreach ($courseRecords as $courseRecord) {
    $courseId = cleanText($courseRecord['id'] ?? null) ?? '';
    if (!isValidUuid($courseId)) {
        continue;
    }

    $title = cleanText($courseRecord['title'] ?? null) ?? 'Untitled Course';
    $provider = cleanText($courseRecord['provider'] ?? null) ?? '';
    $trainingType = cleanText($courseRecord['training_type'] ?? null) ?? '';
    $trainingCategory = cleanText($courseRecord['training_category'] ?? null) ?? '';
    $venue = cleanText($courseRecord['venue'] ?? null) ?? '';
    $startDate = cleanText($courseRecord['start_date'] ?? null) ?? '';
    $endDate = cleanText($courseRecord['end_date'] ?? null) ?? '';
    $mode = cleanText($courseRecord['mode'] ?? null) ?? '';
    $statusDb = strtolower((string)(cleanText($courseRecord['status'] ?? null) ?? 'planned'));
    $statusRaw = $courseDbToVirtual($statusDb);
    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));

    if ($statusRaw === 'draft') {
        continue;
    }

    $formattedStartDate = $formatTrainingDate($startDate);
    $formattedEndDate = $formatTrainingDate($endDate);
    $dateLabel = $formattedStartDate !== '' ? $formattedStartDate : ($formattedEndDate !== '' ? $formattedEndDate : '-');

    $description = trim(implode(' | ', array_filter([
        $provider !== '' ? 'Provider: ' . $provider : '',
        $trainingType !== '' ? 'Type: ' . $trainingType : '',
        $trainingCategory !== '' ? 'Category: ' . $trainingCategory : '',
        $venue !== '' ? 'Venue: ' . $venue : '',
        $mode !== '' ? 'Mode: ' . $mode : '',
        $dateLabel !== '' && $dateLabel !== '-' ? 'Schedule: ' . $dateLabel : '',
    ])));

    $courseById[$courseId] = [
        'title' => $title,
        'provider' => $provider,
        'training_type' => $trainingType,
        'training_category' => $trainingCategory,
        'venue' => $venue,
        'mode' => $mode,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'schedule_label' => $dateLabel,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
    ];

    $courseRows[] = [
        'id' => $courseId,
        'title' => $title,
        'description' => $description,
        'category' => $trainingCategory !== '' ? $trainingCategory : '-',
        'schedule' => $dateLabel,
        'provider' => $provider !== '' ? $provider : '-',
        'training_type' => $trainingType !== '' ? $trainingType : '-',
        'location' => $venue !== '' ? $venue : '-',
        'mode' => $mode !== '' ? ucfirst($mode) : '-',
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'participants' => 0,
        'updated_at' => formatDateTimeForPhilippines((string)(cleanText($courseRecord['updated_at'] ?? null) ?? cleanText($courseRecord['created_at'] ?? null) ?? ''), 'M d, Y g:i A'),
        'search_text' => strtolower(trim($title . ' ' . $description . ' ' . $statusLabel)),
    ];

    $courseCounts['total']++;
    if (isset($courseCounts[$statusRaw])) {
        $courseCounts[$statusRaw]++;
    }

    $courseStatusFilters[$statusLabel] = true;
}

$courseStatusFilters = array_keys($courseStatusFilters);
sort($courseStatusFilters);

$enrollmentResponse = apiRequest(
    'GET',
    $supabaseUrl
        . '/rest/v1/training_enrollments?select=id,program_id,person_id,enrollment_status,score,certificate_url,created_at,updated_at'
        . '&order=updated_at.desc.nullslast,created_at.desc'
        . '&limit=5000',
    $headers
);
$appendLearningDataError('Training enrollments', $enrollmentResponse);
$enrollmentRecords = isSuccessful($enrollmentResponse) ? (array)($enrollmentResponse['data'] ?? []) : [];

$courseEnrollmentCounts = [];
$enrollmentRows = [];
$enrollmentStatusFilters = [];
$enrollmentCounts = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];

foreach ($enrollmentRecords as $enrollmentRecord) {
    $enrollmentId = cleanText($enrollmentRecord['id'] ?? null) ?? '';
    $courseId = cleanText($enrollmentRecord['program_id'] ?? null) ?? '';
    $personId = cleanText($enrollmentRecord['person_id'] ?? null) ?? '';

    if (!isValidUuid($enrollmentId) || !isValidUuid($courseId)) {
        continue;
    }

    if (!isset($courseById[$courseId])) {
        continue;
    }

    $statusDb = strtolower((string)(cleanText($enrollmentRecord['enrollment_status'] ?? null) ?? 'enrolled'));
    $statusRaw = $enrollmentDbToVirtual($statusDb);
    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));
    $courseCategory = (string)($courseById[$courseId]['training_category'] ?? '-');
    $courseType = (string)($courseById[$courseId]['training_type'] ?? '-');
    $courseProvider = (string)($courseById[$courseId]['provider'] ?? '-');
    $courseLocation = (string)($courseById[$courseId]['venue'] ?? '-');
    $courseDate = (string)($courseById[$courseId]['schedule_label'] ?? '-');
    $trainingRecordStatus = $trainingRecordStatusFromDb($statusDb);
    $score = cleanText($enrollmentRecord['score'] ?? null) ?? '';
    $certificateUrl = cleanText($enrollmentRecord['certificate_url'] ?? null) ?? '';
    $notes = $score !== '' ? ('Score: ' . $score) : ($certificateUrl !== '' ? 'Certificate available' : '-');

    $employee = $employeeByPersonId[$personId] ?? [
        'name' => (string)($peopleById[$personId] ?? 'Unknown Employee'),
        'department' => 'Unassigned Division',
    ];
    $enrollmentRows[] = [
        'id' => $enrollmentId,
        'course_id' => $courseId,
        'person_id' => $personId,
        'course_title' => (string)$courseById[$courseId]['title'],
        'course_type' => $courseType,
        'course_category' => $courseCategory,
        'course_date' => $courseDate,
        'provider' => $courseProvider,
        'location' => $courseLocation,
        'employee_name' => (string)$employee['name'],
        'department' => (string)$employee['department'],
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'attendance_status' => $trainingRecordStatus,
        'notes' => $notes,
        'updated_at' => formatDateTimeForPhilippines((string)(cleanText($enrollmentRecord['updated_at'] ?? null) ?? cleanText($enrollmentRecord['created_at'] ?? null) ?? ''), 'M d, Y g:i A'),
        'search_text' => strtolower(trim((string)$employee['name'] . ' ' . (string)$employee['department'] . ' ' . (string)$courseById[$courseId]['title'] . ' ' . $courseType . ' ' . $courseCategory . ' ' . $courseProvider . ' ' . $courseLocation . ' ' . $courseDate . ' ' . $trainingRecordStatus . ' ' . $notes)),
    ];

    $courseEnrolleesByCourseId[$courseId][] = [
        'person_id' => $personId,
        'employee_name' => (string)$employee['name'],
        'department' => (string)$employee['department'],
        'status_label' => $trainingRecordStatus,
        'profile_url' => 'personal-information.php?person_id=' . rawurlencode($personId),
    ];

    $courseEnrollmentCounts[$courseId] = (int)($courseEnrollmentCounts[$courseId] ?? 0) + 1;
    $enrollmentCounts['total']++;
    if (isset($enrollmentCounts[$statusRaw])) {
        $enrollmentCounts[$statusRaw]++;
    }

    $enrollmentStatusFilters[$trainingRecordStatus] = true;
}

foreach ($courseRows as &$courseRow) {
    $courseRow['enrollment_count'] = (int)($courseEnrollmentCounts[$courseRow['id']] ?? 0);
    $courseRow['participants'] = (int)($courseEnrollmentCounts[$courseRow['id']] ?? 0);
}
unset($courseRow);

$enrollableCourseOptions = array_values(array_filter(
    $courseRows,
    static fn (array $courseRow): bool => strtolower((string)($courseRow['status_raw'] ?? '')) === 'published'
));

$enrollmentStatusFilters = array_keys($enrollmentStatusFilters);
sort($enrollmentStatusFilters);

$dataLoadError = $learningDataLoadError;
