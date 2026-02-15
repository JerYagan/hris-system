<?php

$programsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/training_programs?select=id,program_code,title,training_type,training_category,provider,venue,schedule_time,start_date,end_date,mode,status,updated_at&order=start_date.desc&limit=500',
    $headers
);

$trainingSchemaHasExtendedFields = true;
if (!isSuccessful($programsResponse)) {
    $programsFallbackResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/training_programs?select=id,program_code,title,provider,start_date,end_date,mode,status,updated_at&order=start_date.desc&limit=500',
        $headers
    );

    if (isSuccessful($programsFallbackResponse)) {
        $programsResponse = $programsFallbackResponse;
        $trainingSchemaHasExtendedFields = false;
    }
}

$enrollmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/training_enrollments?select=id,program_id,person_id,enrollment_status,score,created_at,updated_at,person:people(first_name,surname),program:training_programs(title,start_date,end_date,provider,mode,status)&order=updated_at.desc&limit=1500',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=office_name&is_active=eq.true&order=office_name.asc&limit=300',
    $headers
);

$participantOptionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=person_id,employment_status,person:people!employment_records_person_id_fkey(id,first_name,surname,user_id,personal_email),office:offices(office_name)&is_current=eq.true&employment_status=eq.active&limit=2000',
    $headers
);

$programs = isSuccessful($programsResponse) ? (array)($programsResponse['data'] ?? []) : [];
$enrollments = isSuccessful($enrollmentsResponse) ? (array)($enrollmentsResponse['data'] ?? []) : [];
$offices = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];
$participantRecords = isSuccessful($participantOptionsResponse) ? (array)($participantOptionsResponse['data'] ?? []) : [];

$participantOptionsNotice = null;
if (empty($participantRecords)) {
    $participantOptionsNotice = 'No active current employees found for participants. Update employment records first, then reopen this modal.';
}

if (!function_exists('learningStatusPill')) {
    function learningStatusPill(string $status): array
    {
        $key = strtolower(trim($status));
        if (in_array($key, ['completed', 'open'], true)) {
            return ['Completed', 'bg-emerald-100 text-emerald-800'];
        }
        if (in_array($key, ['ongoing', 'enrolled', 'planned'], true)) {
            return ['In Progress', 'bg-blue-100 text-blue-800'];
        }
        if (in_array($key, ['failed', 'cancelled'], true)) {
            return ['Needs Review', 'bg-amber-100 text-amber-800'];
        }
        if ($key === 'dropped') {
            return ['Dropped', 'bg-rose-100 text-rose-800'];
        }

        return [ucfirst($key !== '' ? $key : 'planned'), 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('learningAttendanceLabel')) {
    function learningAttendanceLabel(string $enrollmentStatus): array
    {
        $key = strtolower(trim($enrollmentStatus));
        if ($key === 'completed') {
            return ['Present', 'bg-emerald-100 text-emerald-800'];
        }
        if ($key === 'enrolled') {
            return ['Enrolled', 'bg-blue-100 text-blue-800'];
        }
        if ($key === 'failed') {
            return ['Absent', 'bg-amber-100 text-amber-800'];
        }
        if ($key === 'dropped') {
            return ['Dropped', 'bg-rose-100 text-rose-800'];
        }

        return ['Pending', 'bg-slate-100 text-slate-700'];
    }
}

if (!function_exists('learningHoursEarned')) {
    function learningHoursEarned(string $startDate, string $endDate, string $status): string
    {
        $key = strtolower(trim($status));
        if ($key !== 'completed') {
            return '0 hrs';
        }

        if ($startDate === '' || $endDate === '') {
            return '8 hrs';
        }

        $start = strtotime($startDate);
        $end = strtotime($endDate);
        if ($start === false || $end === false) {
            return '8 hrs';
        }

        $days = max(1, (int)floor(($end - $start) / 86400) + 1);
        return (string)($days * 8) . ' hrs';
    }
}

$departmentOptions = [];
foreach ($offices as $office) {
    $name = cleanText($office['office_name'] ?? null) ?? '';
    if ($name === '') {
        continue;
    }
    $departmentOptions[$name] = $name;
}

$trainingScheduleRows = [];
$participantCountByProgram = [];
$participantNamesByProgram = [];
foreach ($enrollments as $enrollment) {
    $programId = (string)($enrollment['program_id'] ?? '');
    if ($programId === '') {
        continue;
    }

    $participantName = trim(((string)($enrollment['person']['first_name'] ?? '')) . ' ' . ((string)($enrollment['person']['surname'] ?? '')));
    if ($participantName === '') {
        $participantName = 'Unknown Employee';
    }

    $participantCountByProgram[$programId] = (int)($participantCountByProgram[$programId] ?? 0) + 1;
    $participantNamesByProgram[$programId][] = $participantName;
}

foreach ($programs as $program) {
    $programId = (string)($program['id'] ?? '');
    $title = (string)($program['title'] ?? '-');
    $trainingType = (string)($program['training_type'] ?? '-');
    $trainingCategory = (string)($program['training_category'] ?? '-');
    $startDate = (string)($program['start_date'] ?? '');
    $scheduleTime = (string)($program['schedule_time'] ?? '');
    $provider = (string)($program['provider'] ?? 'Unassigned');
    $venue = (string)($program['venue'] ?? '-');
    $mode = ucfirst((string)($program['mode'] ?? '-'));
    $statusRaw = (string)($program['status'] ?? 'planned');
    [$statusLabel, $statusClass] = learningStatusPill($statusRaw);
    $programCode = (string)($program['program_code'] ?? '-');
    $endDate = (string)($program['end_date'] ?? '');

    if (!$trainingSchemaHasExtendedFields && str_contains($title, ' - ')) {
        $titleParts = explode(' - ', $title, 2);
        $parsedType = trim((string)($titleParts[0] ?? ''));
        $parsedCategory = trim((string)($titleParts[1] ?? ''));

        if ($trainingType === '-' && $parsedType !== '') {
            $trainingType = $parsedType;
        }
        if ($trainingCategory === '-' && $parsedCategory !== '') {
            $trainingCategory = $parsedCategory;
        }
    }

    $departmentOptions[$provider] = $provider;

    $scheduleLabel = $startDate !== '' ? date('M d, Y', strtotime($startDate)) : '-';
    if ($scheduleTime !== '') {
        $scheduleLabel .= ' ' . date('h:i A', strtotime('1970-01-01 ' . $scheduleTime));
    }

    $scheduleDateLabel = $startDate !== '' ? date('M d, Y', strtotime($startDate)) : '-';
    $scheduleTimeLabel = $scheduleTime !== '' ? date('h:i A', strtotime('1970-01-01 ' . $scheduleTime)) : '-';
    $endDateLabel = $endDate !== '' ? date('M d, Y', strtotime($endDate)) : '-';

    $participantsList = $participantNamesByProgram[$programId] ?? [];
    sort($participantsList);
    $participantsText = !empty($participantsList) ? implode(', ', $participantsList) : 'No participants enrolled yet.';

    $search = strtolower(trim($title . ' ' . $trainingType . ' ' . $trainingCategory . ' ' . $provider . ' ' . $venue . ' ' . $mode . ' ' . $statusLabel));

    $trainingScheduleRows[] = [
        'program_code' => $programCode,
        'title' => $title,
        'training_type' => $trainingType,
        'training_category' => $trainingCategory,
        'date_label' => $scheduleLabel,
        'schedule_date' => $scheduleDateLabel,
        'schedule_time' => $scheduleTimeLabel,
        'end_date' => $endDateLabel,
        'provider' => $provider,
        'venue' => $venue,
        'participants_list' => $participantsText,
        'participants' => (int)($participantCountByProgram[$programId] ?? 0),
        'mode' => $mode,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'search_text' => $search,
    ];
}

ksort($departmentOptions);
$departmentOptions = array_values($departmentOptions);

$participantOptions = [];
foreach ($participantRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($participantOptions[$personId])) {
        continue;
    }

    $firstName = (string)($record['person']['first_name'] ?? '');
    $surname = (string)($record['person']['surname'] ?? '');
    $fullName = trim($firstName . ' ' . $surname);
    if ($fullName === '') {
        $fullName = 'Employee';
    }

    $participantOptions[$personId] = [
        'person_id' => $personId,
        'name' => $fullName,
        'department' => (string)($record['office']['office_name'] ?? 'Unassigned Office'),
        'email' => (string)($record['person']['personal_email'] ?? ''),
    ];
}

$participantOptions = array_values($participantOptions);
usort($participantOptions, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

$trainingRecordRows = [];
$latestByPerson = [];
$attendanceRows = [];
$completedTrainings = 0;
$pendingValidations = 0;
$totalAttendanceBase = 0;

foreach ($enrollments as $enrollment) {
    $enrollmentId = (string)($enrollment['id'] ?? '');
    $personId = (string)($enrollment['person_id'] ?? '');
    $statusRaw = (string)($enrollment['enrollment_status'] ?? 'enrolled');

    if ($statusRaw === 'completed') {
        $completedTrainings++;
    }
    if ($statusRaw === 'enrolled') {
        $pendingValidations++;
    }
    if (in_array($statusRaw, ['enrolled', 'completed', 'failed', 'dropped'], true)) {
        $totalAttendanceBase++;
    }

    $personName = trim(((string)($enrollment['person']['first_name'] ?? '')) . ' ' . ((string)($enrollment['person']['surname'] ?? '')));
    if ($personName === '') {
        $personName = 'Unknown Employee';
    }

    $programTitle = (string)($enrollment['program']['title'] ?? '-');
    $startDate = (string)($enrollment['program']['start_date'] ?? '');
    $endDate = (string)($enrollment['program']['end_date'] ?? '');
    $updatedAt = (string)($enrollment['updated_at'] ?? $enrollment['created_at'] ?? '');

    [$attendanceLabel, $attendanceClass] = learningAttendanceLabel($statusRaw);
    $attendanceDate = $startDate !== '' ? date('M d, Y', strtotime($startDate)) : '-';

    $attendanceRows[] = [
        'enrollment_id' => $enrollmentId,
        'training' => $programTitle,
        'employee' => $personName,
        'date' => $attendanceDate,
        'status_raw' => $statusRaw,
        'status_label' => $attendanceLabel,
        'status_class' => $attendanceClass,
        'search_text' => strtolower(trim($programTitle . ' ' . $personName . ' ' . $attendanceDate . ' ' . $attendanceLabel)),
    ];

    if ($personId === '') {
        continue;
    }

    if (isset($latestByPerson[$personId])) {
        $existingUpdated = (string)($latestByPerson[$personId]['updated_at'] ?? '');
        if ($existingUpdated !== '' && $updatedAt !== '' && strtotime($existingUpdated) >= strtotime($updatedAt)) {
            continue;
        }
    }

    [$recordStatusLabel, $recordStatusClass] = learningStatusPill($statusRaw);

    $latestByPerson[$personId] = [
        'employee' => $personName,
        'training' => $programTitle,
        'completion_date' => $endDate !== '' ? date('M d, Y', strtotime($endDate)) : '-',
        'hours_earned' => learningHoursEarned($startDate, $endDate, $statusRaw),
        'status_label' => $recordStatusLabel,
        'status_class' => $recordStatusClass,
        'search_text' => strtolower(trim($personName . ' ' . $programTitle . ' ' . $recordStatusLabel)),
        'updated_at' => $updatedAt,
    ];
}

$trainingRecordRows = array_values($latestByPerson);
usort($trainingRecordRows, static function (array $left, array $right): int {
    return strcmp((string)$left['employee'], (string)$right['employee']);
});

$averageAttendance = $totalAttendanceBase > 0
    ? (int)round(($completedTrainings / $totalAttendanceBase) * 100)
    : 0;
