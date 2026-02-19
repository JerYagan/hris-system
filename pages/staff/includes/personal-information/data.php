<?php

$filterKeywordInput = cleanText($_GET['keyword'] ?? null) ?? '';
$filterStatusInput = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));
$allowedStatusFilters = ['', 'active', 'on_leave', 'resigned', 'retired', 'terminated'];
if (!in_array($filterStatusInput, $allowedStatusFilters, true)) {
    $filterStatusInput = '';
}

$dataLoadError = null;
$employeeRows = [];

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};


$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$officeScopedFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,hire_date,updated_at,person:people!employment_records_person_id_fkey(id,first_name,middle_name,surname,name_extension,personal_email,mobile_no,user_id),office:offices(office_name),position:job_positions(position_title)'
    . '&is_current=eq.true'
    . $officeScopedFilter
    . '&order=updated_at.desc&limit=3000',
    $headers
);
$appendDataError('Employment records', $employmentResponse);
$employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'active' => ['Active', 'bg-emerald-100 text-emerald-800'],
        'on_leave' => ['On Leave', 'bg-amber-100 text-amber-800'],
        'resigned' => ['Resigned', 'bg-slate-200 text-slate-700'],
        'retired' => ['Retired', 'bg-indigo-100 text-indigo-700'],
        'terminated' => ['Terminated', 'bg-rose-100 text-rose-800'],
        default => ['Unknown', 'bg-slate-100 text-slate-700'],
    };
};

$keywordNeedle = strtolower(trim($filterKeywordInput));

foreach ($employmentRows as $employment) {
    $employmentId = cleanText($employment['id'] ?? null) ?? '';
    $person = (array)($employment['person'] ?? []);
    $personId = cleanText($employment['person_id'] ?? null) ?? cleanText($person['id'] ?? null) ?? '';

    if (!isValidUuid($employmentId) || !isValidUuid($personId)) {
        continue;
    }

    $firstName = cleanText($person['first_name'] ?? null) ?? '';
    $middleName = cleanText($person['middle_name'] ?? null) ?? '';
    $surname = cleanText($person['surname'] ?? null) ?? '';
    $nameExtension = cleanText($person['name_extension'] ?? null) ?? '';
    $employeeName = trim($firstName . ' ' . $middleName . ' ' . $surname . ' ' . $nameExtension);
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $statusRaw = strtolower((string)(cleanText($employment['employment_status'] ?? null) ?? 'active'));
    if ($filterStatusInput !== '' && $statusRaw !== $filterStatusInput) {
        continue;
    }

    $positionTitle = cleanText($employment['position']['position_title'] ?? null) ?? 'Unassigned Position';
    $officeName = cleanText($employment['office']['office_name'] ?? null) ?? 'Unassigned Office';
    $personalEmail = cleanText($person['personal_email'] ?? null) ?? '';
    $mobileNo = cleanText($person['mobile_no'] ?? null) ?? '';
    $personUserId = cleanText($person['user_id'] ?? null) ?? '';
    $searchText = strtolower(trim($employeeName . ' ' . $positionTitle . ' ' . $officeName . ' ' . $statusRaw . ' ' . $personalEmail . ' ' . $mobileNo));
    if ($keywordNeedle !== '' && !str_contains($searchText, $keywordNeedle)) {
        continue;
    }

    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $employeeRows[] = [
        'employment_id' => $employmentId,
        'person_id' => $personId,
        'employee_name' => $employeeName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'surname' => $surname,
        'name_extension' => $nameExtension,
        'position_title' => $positionTitle,
        'office_name' => $officeName,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'personal_email' => $personalEmail,
        'mobile_no' => $mobileNo,
        'hire_date_label' => formatDateTimeForPhilippines(cleanText($employment['hire_date'] ?? null), 'M d, Y'),
        'search_text' => $searchText,
    ];
}

$personalInfoMetrics = [
    'total' => count($employeeRows),
    'active' => 0,
    'on_leave' => 0,
    'separated' => 0,
];

foreach ($employeeRows as $row) {
    $status = (string)($row['status_raw'] ?? '');
    if ($status === 'active') {
        $personalInfoMetrics['active']++;
    } elseif ($status === 'on_leave') {
        $personalInfoMetrics['on_leave']++;
    } elseif (in_array($status, ['resigned', 'retired', 'terminated'], true)) {
        $personalInfoMetrics['separated']++;
    }
}