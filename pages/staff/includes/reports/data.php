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

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current,person:people!employment_records_person_id_fkey(first_name,surname,middle_name)'
    . '&is_current=eq.true'
    . '&limit=5000',
    $headers
);
$appendReportDataError('Employment records', $employmentResponse);
$employmentRecords = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&limit=1000',
    $headers
);
$appendReportDataError('Offices', $officesResponse);
$offices = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];

$latePolicyModeResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.late_policy_mode') . '&limit=1',
    $headers
);
$latePolicySettingResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.late_policy') . '&limit=1',
    $headers
);
$holidayPolicyResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.holiday_payroll_policy') . '&limit=1',
    $headers
);

$officeNameById = [];
foreach ($offices as $office) {
    $officeId = cleanText($office['id'] ?? null) ?? '';
    if (!isValidUuid($officeId)) {
        continue;
    }

    $officeNameById[$officeId] = cleanText($office['office_name'] ?? null) ?? 'Unassigned Division';
}

$divisionFilterOptions = array_values(array_unique(array_filter(
    array_values($officeNameById),
    static fn (mixed $officeName): bool => trim((string)$officeName) !== ''
)));
sort($divisionFilterOptions);

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
$personDepartmentById = [];

$staffReportsPolicyHasNoLateMode = static function (mixed $value) use (&$staffReportsPolicyHasNoLateMode): bool {
    if (is_array($value)) {
        foreach ($value as $nestedKey => $nestedValue) {
            $normalizedKey = strtolower(trim((string)$nestedKey));
            if (in_array($normalizedKey, ['late_policy_mode', 'late_policy', 'policy_mode', 'mode'], true) && $staffReportsPolicyHasNoLateMode($nestedValue)) {
                return true;
            }

            if ($staffReportsPolicyHasNoLateMode($nestedValue)) {
                return true;
            }
        }

        return false;
    }

    $raw = strtolower(trim((string)$value));
    if ($raw === '') {
        return false;
    }

    if (in_array($raw, ['no_late', 'no-late', 'no late'], true)) {
        return true;
    }

    if (str_contains($raw, 'no_late') || str_contains($raw, 'no-late') || str_contains($raw, 'no late')) {
        return true;
    }

    if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
        $decoded = json_decode((string)$value, true);
        if (is_array($decoded)) {
            return $staffReportsPolicyHasNoLateMode($decoded);
        }
    }

    return false;
};

$noLatePolicyApproved = false;
$policyCandidates = [];
if (isSuccessful($latePolicyModeResponse) && !empty((array)($latePolicyModeResponse['data'] ?? []))) {
    $policyCandidates[] = $latePolicyModeResponse['data'][0]['setting_value'] ?? null;
}
if (isSuccessful($latePolicySettingResponse) && !empty((array)($latePolicySettingResponse['data'] ?? []))) {
    $policyCandidates[] = $latePolicySettingResponse['data'][0]['setting_value'] ?? null;
}
if (isSuccessful($holidayPolicyResponse) && !empty((array)($holidayPolicyResponse['data'] ?? []))) {
    $policyCandidates[] = $holidayPolicyResponse['data'][0]['setting_value'] ?? null;
}
foreach ($policyCandidates as $candidate) {
    if ($staffReportsPolicyHasNoLateMode($candidate)) {
        $noLatePolicyApproved = true;
        break;
    }
}

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
    $departmentName = $officeNameById[$officeId] ?? 'Unassigned Division';
    $personDepartmentById[$personId] = $departmentName;

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

$topDepartmentLabel = 'No division data available.';
if (!empty($departmentCounts)) {
    arsort($departmentCounts);
    $topOfficeId = (string)array_key_first($departmentCounts);
    $topDepartmentCount = (int)($departmentCounts[$topOfficeId] ?? 0);
    $topDepartmentName = (string)($officeNameById[$topOfficeId] ?? 'Unassigned Division');
    $topDepartmentLabel = $topDepartmentName . ' - ' . $topDepartmentCount . ' Employee' . ($topDepartmentCount === 1 ? '' : 's');
}

$attendanceCurrent = ['total' => 0, 'compliant' => 0, 'late' => 0];
$attendancePrevious = ['total' => 0, 'compliant' => 0, 'late' => 0];
$payrollCurrent = ['gross' => 0.0, 'net' => 0.0];
$payrollPrevious = ['gross' => 0.0, 'net' => 0.0];

$attendanceLogs = [];
$payrollItems = [];
$timekeepingRows = [];
$payrollSummaryRows = [];
$recruitmentMetricRows = [];

$timekeepingStatusFilters = [];
$timekeepingDepartmentFilters = [];
$timekeepingSourceFilters = [];

$payrollStatusFilters = [];
$payrollDepartmentFilters = [];
$payrollPeriodFilters = [];

$recruitmentStatusFilters = [];
$recruitmentDepartmentFilters = [];
$recruitmentPositionFilters = [];

$attendancePresentCount = 0;
$attendanceLateCount = 0;
$attendanceAbsentCount = 0;

$payrollProcessedCount = 0;
$payrollTotalGross = 0.0;
$payrollTotalNet = 0.0;

$recruitmentSubmittedCount = 0;
$recruitmentInterviewCount = 0;
$recruitmentHiredCount = 0;
$recruitmentRejectedCount = 0;

$toDateKey = static function (?string $value): string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return '';
    }

    return gmdate('Y-m-d', $timestamp);
};

$formatStatusLabel = static function (?string $status): string {
    $normalized = strtolower(trim((string)$status));
    if ($normalized === '') {
        return 'Unspecified';
    }

    return ucwords(str_replace('_', ' ', $normalized));
};

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
        . '/rest/v1/payroll_items?select=id,person_id,gross_pay,net_pay,created_at,payroll_run:payroll_runs(run_status,office_id,payroll_period:payroll_periods(period_start,period_end,period_code)),person:people(first_name,surname,middle_name)'
        . $personFilter
        . '&order=created_at.desc&limit=5000',
        $headers
    );
    $appendReportDataError('Payroll items', $payrollResponse);
    $payrollItems = isSuccessful($payrollResponse) ? (array)($payrollResponse['data'] ?? []) : [];

    foreach ($payrollItems as $item) {
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

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=application_ref_no,application_status,submitted_at,updated_at,job:job_postings(title,office_id),applicant:applicant_profiles(full_name,email)'
    . '&order=submitted_at.desc&limit=5000',
    $headers
);
$appendReportDataError('Applications', $applicationsResponse);
$applicationRows = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];

$attendanceComplianceCurrent = $attendanceCurrent['total'] > 0
    ? round(($attendanceCurrent['compliant'] / $attendanceCurrent['total']) * 100, 1)
    : 0.0;
$attendanceCompliancePrevious = $attendancePrevious['total'] > 0
    ? round(($attendancePrevious['compliant'] / $attendancePrevious['total']) * 100, 1)
    : 0.0;

foreach ($attendanceLogs as $row) {
    $personId = cleanText($row['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $person = is_array($row['person'] ?? null) ? (array)$row['person'] : [];
    $employeeName = trim(
        (string)(cleanText($person['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($person['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $departmentName = (string)($personDepartmentById[$personId] ?? 'Unassigned Division');
    $statusRaw = cleanText($row['attendance_status'] ?? null) ?? '';
    $statusLabel = $formatStatusLabel($statusRaw);
    $dateRaw = cleanText($row['attendance_date'] ?? null) ?? '';
    $dateKey = $toDateKey($dateRaw);

    $timekeepingRows[] = [
        'date_key' => $dateKey,
        'date_label' => $dateKey !== '' ? formatDateTimeForPhilippines($dateKey, 'M d, Y') : '-',
        'employee_name' => $employeeName,
        'department' => $departmentName,
        'status_label' => $statusLabel,
        'late_minutes' => (string)((int)($row['late_minutes'] ?? 0)),
        'hours_worked' => number_format((float)($row['hours_worked'] ?? 0), 2),
        'source_label' => cleanText($row['source'] ?? null) ?? '-',
        'search_text' => strtolower(trim($employeeName . ' ' . $departmentName . ' ' . $statusLabel . ' ' . ($dateKey !== '' ? $dateKey : ''))),
    ];

    $timekeepingDepartmentFilters[$departmentName] = true;
    $timekeepingStatusFilters[$statusLabel] = true;
    $sourceLabel = cleanText($row['source'] ?? null) ?? 'Unspecified';
    $timekeepingSourceFilters[$sourceLabel] = true;

    $normalizedStatus = strtolower(trim($statusRaw));
    if ($normalizedStatus === 'present') {
        $attendancePresentCount++;
    } elseif ($normalizedStatus === 'late') {
        $attendanceLateCount++;
    } elseif (in_array($normalizedStatus, ['absent', 'no_show'], true)) {
        $attendanceAbsentCount++;
    }
}

usort($timekeepingRows, static function (array $left, array $right): int {
    return strcmp((string)($right['date_key'] ?? ''), (string)($left['date_key'] ?? ''));
});

foreach ($payrollItems as $item) {
    $personId = cleanText($item['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        continue;
    }

    $person = is_array($item['person'] ?? null) ? (array)$item['person'] : [];
    $employeeName = trim(
        (string)(cleanText($person['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($person['middle_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($person['surname'] ?? null) ?? '')
    );
    if ($employeeName === '') {
        $employeeName = 'Unknown Employee';
    }

    $run = is_array($item['payroll_run'] ?? null) ? (array)$item['payroll_run'] : [];
    $period = is_array($run['payroll_period'] ?? null) ? (array)$run['payroll_period'] : [];
    $runStatusLabel = $formatStatusLabel(cleanText($run['run_status'] ?? null) ?? 'draft');

    $periodStartRaw = cleanText($period['period_start'] ?? null) ?? '';
    $periodEndRaw = cleanText($period['period_end'] ?? null) ?? '';
    $periodStartKey = $toDateKey($periodStartRaw);
    $periodEndKey = $toDateKey($periodEndRaw);
    $periodCode = cleanText($period['period_code'] ?? null) ?? 'Uncoded Period';
    $periodLabel = ($periodStartKey !== '' && $periodEndKey !== '')
        ? (formatDateTimeForPhilippines($periodStartKey, 'M d, Y') . ' - ' . formatDateTimeForPhilippines($periodEndKey, 'M d, Y'))
        : $periodCode;

    $runOfficeId = cleanText($run['office_id'] ?? null) ?? '';
    $departmentName = (string)($personDepartmentById[$personId] ?? ($officeNameById[$runOfficeId] ?? 'Unassigned Division'));

    $grossPay = (float)($item['gross_pay'] ?? 0);
    $netPay = (float)($item['net_pay'] ?? 0);

    $payrollSummaryRows[] = [
        'date_key' => $periodEndKey !== '' ? $periodEndKey : $periodStartKey,
        'period_label' => $periodLabel,
        'period_code' => $periodCode,
        'employee_name' => $employeeName,
        'department' => $departmentName,
        'run_status' => $runStatusLabel,
        'gross_pay' => $grossPay,
        'gross_label' => '₱' . number_format($grossPay, 2),
        'net_pay' => $netPay,
        'net_label' => '₱' . number_format($netPay, 2),
        'search_text' => strtolower(trim($employeeName . ' ' . $departmentName . ' ' . $periodCode . ' ' . $periodLabel . ' ' . $runStatusLabel)),
    ];

    $payrollProcessedCount++;
    $payrollTotalGross += $grossPay;
    $payrollTotalNet += $netPay;

    $payrollDepartmentFilters[$departmentName] = true;
    $payrollStatusFilters[$runStatusLabel] = true;
    $payrollPeriodFilters[$periodCode] = true;
}

usort($payrollSummaryRows, static function (array $left, array $right): int {
    return strcmp((string)($right['date_key'] ?? ''), (string)($left['date_key'] ?? ''));
});

foreach ($applicationRows as $item) {
    $job = is_array($item['job'] ?? null) ? (array)$item['job'] : [];
    $applicant = is_array($item['applicant'] ?? null) ? (array)$item['applicant'] : [];
    $jobOfficeId = cleanText($job['office_id'] ?? null) ?? '';
    $departmentName = (string)($officeNameById[$jobOfficeId] ?? 'Unassigned Division');

    $statusRaw = cleanText($item['application_status'] ?? null) ?? '';
    $statusLabel = $formatStatusLabel($statusRaw);
    $submittedAtRaw = cleanText($item['submitted_at'] ?? null) ?? '';
    $submittedDateKey = $toDateKey($submittedAtRaw);
    $positionTitle = cleanText($job['title'] ?? null) ?? '-';

    $recruitmentMetricRows[] = [
        'date_key' => $submittedDateKey,
        'submitted_label' => $submittedDateKey !== '' ? formatDateTimeForPhilippines($submittedDateKey, 'M d, Y') : '-',
        'reference_no' => cleanText($item['application_ref_no'] ?? null) ?? '-',
        'applicant_name' => cleanText($applicant['full_name'] ?? null) ?? '-',
        'position_title' => $positionTitle,
        'department' => $departmentName,
        'status_label' => $statusLabel,
        'search_text' => strtolower(trim(
            (string)(cleanText($item['application_ref_no'] ?? null) ?? '')
            . ' ' . (string)(cleanText($applicant['full_name'] ?? null) ?? '')
            . ' ' . $positionTitle
            . ' ' . $departmentName
            . ' ' . $statusLabel
            . ' ' . $submittedDateKey
        )),
    ];

    $recruitmentDepartmentFilters[$departmentName] = true;
    $recruitmentStatusFilters[$statusLabel] = true;
    $recruitmentPositionFilters[$positionTitle] = true;

    $normalizedStatus = strtolower(trim($statusRaw));
    if ($normalizedStatus === 'submitted') {
        $recruitmentSubmittedCount++;
    }
    if (in_array($normalizedStatus, ['interview', 'interview_scheduled', 'interviewed'], true)) {
        $recruitmentInterviewCount++;
    }
    if ($normalizedStatus === 'hired') {
        $recruitmentHiredCount++;
    }
    if ($normalizedStatus === 'rejected') {
        $recruitmentRejectedCount++;
    }
}

usort($recruitmentMetricRows, static function (array $left, array $right): int {
    return strcmp((string)($right['date_key'] ?? ''), (string)($left['date_key'] ?? ''));
});

$timekeepingStatusFilters = array_values(array_keys($timekeepingStatusFilters));
sort($timekeepingStatusFilters);

$timekeepingDepartmentFilters = array_values(array_keys($timekeepingDepartmentFilters));
sort($timekeepingDepartmentFilters);

$timekeepingSourceFilters = array_values(array_keys($timekeepingSourceFilters));
sort($timekeepingSourceFilters);

$payrollStatusFilters = array_values(array_keys($payrollStatusFilters));
sort($payrollStatusFilters);

$payrollDepartmentFilters = array_values(array_keys($payrollDepartmentFilters));
sort($payrollDepartmentFilters);

$payrollPeriodFilters = array_values(array_keys($payrollPeriodFilters));
sort($payrollPeriodFilters);

$recruitmentStatusFilters = array_values(array_keys($recruitmentStatusFilters));
sort($recruitmentStatusFilters);

$recruitmentDepartmentFilters = array_values(array_keys($recruitmentDepartmentFilters));
sort($recruitmentDepartmentFilters);

$recruitmentPositionFilters = array_values(array_keys($recruitmentPositionFilters));
sort($recruitmentPositionFilters);

$departmentsForFilter = $divisionFilterOptions;

$dataLoadError = $reportDataLoadError;
