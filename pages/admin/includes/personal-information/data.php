<?php

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,mobile_no,personal_email,agency_employee_no,date_of_birth,created_at&order=created_at.desc&limit=1500',
    $headers
);

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,position_id,employment_status,is_current,hire_date,separation_reason,created_at&order=created_at.desc&limit=2500',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,is_active&order=office_name.asc&limit=500',
    $headers
);

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=500',
    $headers
);

$accountsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status&limit=2000',
    $headers
);

$peopleRows = isSuccessful($peopleResponse) ? (array)$peopleResponse['data'] : [];
$employmentRows = isSuccessful($employmentResponse) ? (array)$employmentResponse['data'] : [];
$officeRows = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];
$positionRows = isSuccessful($positionsResponse) ? (array)$positionsResponse['data'] : [];
$accountRows = isSuccessful($accountsResponse) ? (array)$accountsResponse['data'] : [];

$filterKeywordInput = (string)(cleanText($_GET['keyword'] ?? null) ?? '');
$filterDepartment = (string)(cleanText($_GET['department'] ?? null) ?? '');
$filterStatusInput = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));

$filterKeyword = strtolower(trim($filterKeywordInput));
$filterStatus = in_array($filterStatusInput, ['active', 'inactive'], true) ? $filterStatusInput : '';

$dataLoadError = null;
$responseChecks = [
    ['people', $peopleResponse],
    ['employment records', $employmentResponse],
    ['offices', $officesResponse],
    ['job positions', $positionsResponse],
    ['user accounts', $accountsResponse],
];

foreach ($responseChecks as [$label, $response]) {
    if (isSuccessful($response)) {
        continue;
    }

    $message = ucfirst((string)$label) . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
}

$officeNameById = [];
foreach ($officeRows as $office) {
    $officeId = (string)($office['id'] ?? '');
    if ($officeId === '') {
        continue;
    }
    $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Department');
}

$positionNameById = [];
foreach ($positionRows as $position) {
    $positionId = (string)($position['id'] ?? '');
    if ($positionId === '') {
        continue;
    }
    $positionNameById[$positionId] = (string)($position['position_title'] ?? 'Unassigned Position');
}

$accountByUserId = [];
foreach ($accountRows as $account) {
    $userId = (string)($account['id'] ?? '');
    if ($userId === '') {
        continue;
    }
    $accountByUserId[$userId] = [
        'email' => (string)($account['email'] ?? ''),
        'account_status' => (string)($account['account_status'] ?? 'pending'),
    ];
}

$currentEmploymentByPerson = [];
foreach ($employmentRows as $employment) {
    $personId = (string)($employment['person_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $isCurrent = (bool)($employment['is_current'] ?? false);
    if ($isCurrent || !isset($currentEmploymentByPerson[$personId])) {
        $currentEmploymentByPerson[$personId] = $employment;
    }
}

$employeeTableRows = [];
$employeesForSelect = [];
$departmentFilters = [];

$totalProfiles = count($peopleRows);
$completeRecords = 0;
$activeEmployees = 0;
$inactiveEmployees = 0;

foreach ($peopleRows as $person) {
    $personId = (string)($person['id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $firstName = trim((string)($person['first_name'] ?? ''));
    $surname = trim((string)($person['surname'] ?? ''));
    $fullName = trim($firstName . ' ' . $surname);
    if ($fullName === '') {
        $fullName = 'Unknown Employee';
    }

    $employment = $currentEmploymentByPerson[$personId] ?? null;
    $officeId = (string)($employment['office_id'] ?? '');
    $positionId = (string)($employment['position_id'] ?? '');
    $employmentStatus = strtolower((string)($employment['employment_status'] ?? 'inactive'));
    $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Department');
    $positionName = (string)($positionNameById[$positionId] ?? 'Unassigned Position');

    $statusLabel = $employmentStatus === 'active' ? 'Active' : 'Inactive';
    if ($employmentStatus === 'active') {
        $activeEmployees++;
    } else {
        $inactiveEmployees++;
    }

    $email = (string)($accountByUserId[(string)($person['user_id'] ?? '')]['email'] ?? ($person['personal_email'] ?? ''));
    $mobile = (string)($person['mobile_no'] ?? '');
    $employeeCode = (string)($person['agency_employee_no'] ?? '');
    if ($employeeCode === '') {
        $employeeCode = 'EMP-' . strtoupper(substr(str_replace('-', '', $personId), 0, 6));
    }

    $isComplete = $email !== '' && $mobile !== '' && !empty($employment);
    if ($isComplete) {
        $completeRecords++;
    }

    $departmentFilters[$departmentName] = true;

    $searchText = strtolower(trim($employeeCode . ' ' . $fullName . ' ' . $departmentName . ' ' . $positionName . ' ' . $statusLabel . ' ' . $email));

    $employeeTableRows[] = [
        'person_id' => $personId,
        'employee_code' => $employeeCode,
        'full_name' => $fullName,
        'department' => $departmentName,
        'position' => $positionName,
        'status_label' => $statusLabel,
        'status_raw' => $employmentStatus,
        'email' => $email,
        'mobile' => $mobile,
        'search_text' => $searchText,
    ];

    $employeesForSelect[] = [
        'person_id' => $personId,
        'name' => $fullName,
        'employee_code' => $employeeCode,
    ];
}

$needsUpdateCount = max(0, $totalProfiles - $completeRecords);

$departmentFilterOptions = array_keys($departmentFilters);
sort($departmentFilterOptions);

usort($employeesForSelect, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

if ($filterKeyword !== '' || $filterDepartment !== '' || $filterStatus !== '') {
    $employeeTableRows = array_values(array_filter($employeeTableRows, static function (array $row) use ($filterKeyword, $filterDepartment, $filterStatus): bool {
        $matchesKeyword = true;
        $matchesDepartment = true;
        $matchesStatus = true;

        if ($filterKeyword !== '') {
            $haystack = strtolower((string)($row['search_text'] ?? ''));
            $matchesKeyword = str_contains($haystack, $filterKeyword);
        }

        if ($filterDepartment !== '') {
            $matchesDepartment = strcasecmp((string)($row['department'] ?? ''), $filterDepartment) === 0;
        }

        if ($filterStatus !== '') {
            $matchesStatus = strtolower((string)($row['status_raw'] ?? '')) === $filterStatus;
        }

        return $matchesKeyword && $matchesDepartment && $matchesStatus;
    }));
}

$filteredProfileCount = count($employeeTableRows);
