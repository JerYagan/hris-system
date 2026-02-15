<?php

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current&is_current=eq.true&limit=2000',
    $headers
);

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,surname,middle_name&limit=5000',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&limit=500',
    $headers
);

$attendanceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/attendance_logs?select=id,attendance_date,attendance_status&order=attendance_date.desc&limit=3000',
    $headers
);

$payrollResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_items?select=id,gross_pay,net_pay,created_at,payroll_run:payroll_runs(payroll_period:payroll_periods(period_start,period_end,period_code))&order=created_at.desc&limit=5000',
    $headers
);

$employmentRecords = isSuccessful($employmentResponse) ? $employmentResponse['data'] : [];
$people = isSuccessful($peopleResponse) ? $peopleResponse['data'] : [];
$offices = isSuccessful($officesResponse) ? $officesResponse['data'] : [];
$attendanceLogs = isSuccessful($attendanceResponse) ? $attendanceResponse['data'] : [];
$payrollItems = isSuccessful($payrollResponse) ? $payrollResponse['data'] : [];

$dataLoadError = null;
if (!isSuccessful($employmentResponse)) {
    $dataLoadError = 'Employment query failed (HTTP ' . (int)($employmentResponse['status'] ?? 0) . ').';
}
if (!isSuccessful($peopleResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'People query failed (HTTP ' . (int)($peopleResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($officesResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Office query failed (HTTP ' . (int)($officesResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($attendanceResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Attendance query failed (HTTP ' . (int)($attendanceResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($payrollResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Payroll query failed (HTTP ' . (int)($payrollResponse['status'] ?? 0) . ').');
}

$currentEmploymentByPerson = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($currentEmploymentByPerson[$personId])) {
        continue;
    }
    $currentEmploymentByPerson[$personId] = $record;
}

$uniqueEmploymentRecords = array_values($currentEmploymentByPerson);

$totalEmployees = count($uniqueEmploymentRecords);
$activeCount = 0;
$onLeaveCount = 0;
$inactiveCount = 0;
$newHiresLast30Days = 0;

$officeNameById = [];
foreach ($offices as $office) {
    $officeId = (string)($office['id'] ?? '');
    if ($officeId === '') {
        continue;
    }
    $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Office');
}

$personNameById = [];
foreach ($people as $person) {
    $personId = (string)($person['id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $firstName = trim((string)($person['first_name'] ?? ''));
    $middleName = trim((string)($person['middle_name'] ?? ''));
    $surname = trim((string)($person['surname'] ?? ''));

    $displayName = trim($firstName . ' ' . $surname);
    if ($displayName === '' && $middleName !== '') {
        $displayName = $middleName;
    }
    if ($displayName === '') {
        $displayName = 'Employee';
    }

    $personNameById[$personId] = $displayName;
}

$departmentCounts = [];
$thirtyDaysAgo = strtotime('-30 days');
foreach ($uniqueEmploymentRecords as $record) {
    $status = strtolower((string)($record['employment_status'] ?? ''));
    if ($status === 'active') {
        $activeCount++;
    } elseif ($status === 'on_leave') {
        $onLeaveCount++;
    } else {
        $inactiveCount++;
    }

    $officeId = (string)($record['office_id'] ?? '');
    if ($officeId !== '') {
        $departmentCounts[$officeId] = (int)($departmentCounts[$officeId] ?? 0) + 1;
    }

    $hireDate = (string)($record['hire_date'] ?? '');
    if ($hireDate !== '' && strtotime($hireDate) >= $thirtyDaysAgo) {
        $newHiresLast30Days++;
    }
}

$topDepartmentLabel = 'No department data available.';
if (!empty($departmentCounts)) {
    arsort($departmentCounts);
    $topOfficeId = (string)array_key_first($departmentCounts);
    $topDepartmentCount = (int)($departmentCounts[$topOfficeId] ?? 0);
    $topDepartmentName = (string)($officeNameById[$topOfficeId] ?? 'Unassigned Office');
    $topDepartmentLabel = $topDepartmentName . ' - ' . $topDepartmentCount . ' Employee' . ($topDepartmentCount === 1 ? '' : 's');
}

$attendanceCurrent = ['total' => 0, 'compliant' => 0, 'late' => 0];
$attendancePrevious = ['total' => 0, 'compliant' => 0, 'late' => 0];
$now = time();
$currentWindowStart = strtotime('-30 days', $now);
$previousWindowStart = strtotime('-60 days', $now);

foreach ($attendanceLogs as $row) {
    $dateRaw = (string)($row['attendance_date'] ?? '');
    if ($dateRaw === '') {
        continue;
    }

    $dateTs = strtotime($dateRaw);
    if ($dateTs === false) {
        continue;
    }

    $status = strtolower((string)($row['attendance_status'] ?? ''));
    $isCompliant = in_array($status, ['present', 'late'], true);
    $isLate = $status === 'late';

    if ($dateTs >= $currentWindowStart) {
        $attendanceCurrent['total']++;
        if ($isCompliant) {
            $attendanceCurrent['compliant']++;
        }
        if ($isLate) {
            $attendanceCurrent['late']++;
        }
    } elseif ($dateTs >= $previousWindowStart && $dateTs < $currentWindowStart) {
        $attendancePrevious['total']++;
        if ($isCompliant) {
            $attendancePrevious['compliant']++;
        }
        if ($isLate) {
            $attendancePrevious['late']++;
        }
    }
}

$attendanceComplianceCurrent = $attendanceCurrent['total'] > 0
    ? round(($attendanceCurrent['compliant'] / $attendanceCurrent['total']) * 100, 1)
    : 0.0;
$attendanceCompliancePrevious = $attendancePrevious['total'] > 0
    ? round(($attendancePrevious['compliant'] / $attendancePrevious['total']) * 100, 1)
    : 0.0;

$payrollCurrent = ['gross' => 0.0, 'net' => 0.0];
$payrollPrevious = ['gross' => 0.0, 'net' => 0.0];
foreach ($payrollItems as $item) {
    $period = is_array($item['payroll_run']['payroll_period'] ?? null) ? (array)$item['payroll_run']['payroll_period'] : [];
    $periodEnd = (string)($period['period_end'] ?? '');
    $windowDate = $periodEnd !== '' ? $periodEnd : substr((string)($item['created_at'] ?? ''), 0, 10);
    if ($windowDate === '') {
        continue;
    }

    $createdTs = strtotime($windowDate);
    if ($createdTs === false) {
        continue;
    }

    $gross = (float)($item['gross_pay'] ?? 0);
    $net = (float)($item['net_pay'] ?? 0);

    if ($createdTs >= $currentWindowStart) {
        $payrollCurrent['gross'] += $gross;
        $payrollCurrent['net'] += $net;
    } elseif ($createdTs >= $previousWindowStart && $createdTs < $currentWindowStart) {
        $payrollPrevious['gross'] += $gross;
        $payrollPrevious['net'] += $net;
    }
}

$departmentsForFilter = array_values($officeNameById);
sort($departmentsForFilter);

$employmentStatusLabel = static function (string $status): string {
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return 'Unspecified';
    }

    return ucwords(str_replace('_', ' ', $normalized));
};

$employeeRows = [];
$employeeStatusFilters = [];
$employeeDepartmentFilters = [];

foreach ($uniqueEmploymentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $officeId = (string)($record['office_id'] ?? '');
    $statusRaw = (string)($record['employment_status'] ?? '');
    $statusLabel = $employmentStatusLabel($statusRaw);
    $hireDateRaw = (string)($record['hire_date'] ?? '');
    $hireDateLabel = $hireDateRaw !== '' ? date('M d, Y', strtotime($hireDateRaw)) : '-';

    $employeeName = (string)($personNameById[$personId] ?? 'Employee #' . ($personId !== '' ? substr($personId, 0, 8) : 'N/A'));
    $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Office');

    $employeeRows[] = [
        'person_id' => $personId,
        'name' => $employeeName,
        'department' => $departmentName,
        'status_label' => $statusLabel,
        'hire_date' => $hireDateLabel,
        'search_text' => strtolower(trim($employeeName . ' ' . $departmentName . ' ' . $statusLabel . ' ' . $hireDateLabel . ' ' . $personId)),
    ];

    $employeeStatusFilters[$statusLabel] = true;
    $employeeDepartmentFilters[$departmentName] = true;
}

usort($employeeRows, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

$employeeStatusFilters = array_keys($employeeStatusFilters);
sort($employeeStatusFilters);

$employeeDepartmentFilters = array_keys($employeeDepartmentFilters);
sort($employeeDepartmentFilters);
