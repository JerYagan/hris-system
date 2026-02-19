<?php

$reportDataLoadError = null;

$appendReportDataError = static function (string $label, array $response) use (&$reportDataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $reportDataLoadError = $reportDataLoadError ? ($reportDataLoadError . ' ' . $message) : $message;
};

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$officeScopedFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current,person:people!employment_records_person_id_fkey(first_name,surname,middle_name)'
    . '&is_current=eq.true'
    . $officeScopedFilter
    . '&limit=5000',
    $headers
);
$appendReportDataError('Employment records', $employmentResponse);
$employmentRecords = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&limit=1000',
    $headers
);
$appendReportDataError('Offices', $officesResponse);
$offices = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];

$officeNameById = [];
foreach ($offices as $office) {
    $officeId = cleanText($office['id'] ?? null) ?? '';
    if (!isValidUuid($officeId)) {
        continue;
    }

    $officeNameById[$officeId] = cleanText($office['office_name'] ?? null) ?? 'Unassigned Office';
}

$currentEmploymentByPerson = [];
foreach ($employmentRecords as $record) {
    $personId = cleanText($record['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId) || isset($currentEmploymentByPerson[$personId])) {
        continue;
    }

    $currentEmploymentByPerson[$personId] = $record;
}

$uniqueEmploymentRecords = array_values($currentEmploymentByPerson);
$personIds = array_keys($currentEmploymentByPerson);

$totalEmployees = count($uniqueEmploymentRecords);
$activeCount = 0;
$onLeaveCount = 0;
$inactiveCount = 0;
$newHiresLast30Days = 0;
$departmentCounts = [];
$thirtyDaysAgo = strtotime('-30 days');

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
    $personId = cleanText($record['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $person = (array)($record['person'] ?? []);
    $firstName = cleanText($person['first_name'] ?? null) ?? '';
    $middleName = cleanText($person['middle_name'] ?? null) ?? '';
    $surname = cleanText($person['surname'] ?? null) ?? '';
    $employeeName = trim($firstName . ' ' . $middleName . ' ' . $surname);
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $officeId = cleanText($record['office_id'] ?? null) ?? '';
    $departmentName = $officeNameById[$officeId] ?? 'Unassigned Office';

    $statusRaw = strtolower((string)(cleanText($record['employment_status'] ?? null) ?? 'inactive'));
    $statusLabel = $employmentStatusLabel($statusRaw);

    if ($statusRaw === 'active') {
        $activeCount++;
    } elseif ($statusRaw === 'on_leave') {
        $onLeaveCount++;
    } else {
        $inactiveCount++;
    }

    if ($officeId !== '') {
        $departmentCounts[$officeId] = (int)($departmentCounts[$officeId] ?? 0) + 1;
    }

    $hireDateRaw = cleanText($record['hire_date'] ?? null) ?? '';
    if ($hireDateRaw !== '' && strtotime($hireDateRaw) !== false && strtotime($hireDateRaw) >= $thirtyDaysAgo) {
        $newHiresLast30Days++;
    }

    $hireDateLabel = $hireDateRaw !== '' ? formatDateTimeForPhilippines($hireDateRaw, 'M d, Y') : '-';
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
    return strcmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
});

$employeeStatusFilters = array_keys($employeeStatusFilters);
sort($employeeStatusFilters);

$employeeDepartmentFilters = array_keys($employeeDepartmentFilters);
sort($employeeDepartmentFilters);

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
$payrollCurrent = ['gross' => 0.0, 'net' => 0.0];
$payrollPrevious = ['gross' => 0.0, 'net' => 0.0];

if (!empty($personIds)) {
    $personFilter = '&person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')');
    $now = time();
    $currentWindowStart = strtotime('-30 days', $now);
    $previousWindowStart = strtotime('-60 days', $now);

    $attendanceResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,attendance_status'
        . $personFilter
        . '&order=attendance_date.desc&limit=5000',
        $headers
    );
    $appendReportDataError('Attendance logs', $attendanceResponse);
    $attendanceLogs = isSuccessful($attendanceResponse) ? (array)($attendanceResponse['data'] ?? []) : [];

    foreach ($attendanceLogs as $row) {
        $dateRaw = cleanText($row['attendance_date'] ?? null) ?? '';
        if ($dateRaw === '') {
            continue;
        }

        $dateTs = strtotime($dateRaw);
        if ($dateTs === false) {
            continue;
        }

        $status = strtolower((string)(cleanText($row['attendance_status'] ?? null) ?? ''));
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

    $payrollResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_items?select=id,person_id,gross_pay,net_pay,created_at,payroll_run:payroll_runs(office_id,payroll_period:payroll_periods(period_start,period_end,period_code))'
        . $personFilter
        . '&order=created_at.desc&limit=5000',
        $headers
    );
    $appendReportDataError('Payroll items', $payrollResponse);
    $payrollItems = isSuccessful($payrollResponse) ? (array)($payrollResponse['data'] ?? []) : [];

    foreach ($payrollItems as $item) {
        $runOfficeId = cleanText($item['payroll_run']['office_id'] ?? null) ?? '';
        if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strcasecmp($runOfficeId, (string)$staffOfficeId) !== 0) {
            continue;
        }

        $period = is_array($item['payroll_run']['payroll_period'] ?? null)
            ? (array)$item['payroll_run']['payroll_period']
            : [];

        $periodEnd = cleanText($period['period_end'] ?? null) ?? '';
        $windowDate = $periodEnd !== '' ? $periodEnd : substr((string)(cleanText($item['created_at'] ?? null) ?? ''), 0, 10);
        if ($windowDate === '') {
            continue;
        }

        $windowTs = strtotime($windowDate);
        if ($windowTs === false) {
            continue;
        }

        $gross = (float)($item['gross_pay'] ?? 0);
        $net = (float)($item['net_pay'] ?? 0);

        if ($windowTs >= $currentWindowStart) {
            $payrollCurrent['gross'] += $gross;
            $payrollCurrent['net'] += $net;
        } elseif ($windowTs >= $previousWindowStart && $windowTs < $currentWindowStart) {
            $payrollPrevious['gross'] += $gross;
            $payrollPrevious['net'] += $net;
        }
    }
}

$attendanceComplianceCurrent = $attendanceCurrent['total'] > 0
    ? round(($attendanceCurrent['compliant'] / $attendanceCurrent['total']) * 100, 1)
    : 0.0;
$attendanceCompliancePrevious = $attendancePrevious['total'] > 0
    ? round(($attendancePrevious['compliant'] / $attendancePrevious['total']) * 100, 1)
    : 0.0;

$departmentsForFilter = array_values($employeeDepartmentFilters);

$dataLoadError = $reportDataLoadError;
