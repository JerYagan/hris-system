<?php

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current&is_current=eq.true&limit=2000',
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
    $supabaseUrl . '/rest/v1/payroll_items?select=id,gross_pay,net_pay,created_at&order=created_at.desc&limit=3000',
    $headers
);

$employmentRecords = isSuccessful($employmentResponse) ? $employmentResponse['data'] : [];
$offices = isSuccessful($officesResponse) ? $officesResponse['data'] : [];
$attendanceLogs = isSuccessful($attendanceResponse) ? $attendanceResponse['data'] : [];
$payrollItems = isSuccessful($payrollResponse) ? $payrollResponse['data'] : [];

$dataLoadError = null;
if (!isSuccessful($employmentResponse)) {
    $dataLoadError = 'Employment query failed (HTTP ' . (int)($employmentResponse['status'] ?? 0) . ').';
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

$totalEmployees = count($employmentRecords);
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

$departmentCounts = [];
$thirtyDaysAgo = strtotime('-30 days');
foreach ($employmentRecords as $record) {
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
    $createdAt = (string)($item['created_at'] ?? '');
    if ($createdAt === '') {
        continue;
    }

    $createdTs = strtotime($createdAt);
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
