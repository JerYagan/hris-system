<?php

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current&is_current=eq.true&limit=2000',
    $headers
);

$peopleResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,surname,middle_name,sex_at_birth,date_of_birth,civil_status&limit=5000',
    $headers
);

$employmentAllResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,separation_date,is_current&limit=10000',
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

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id,document_status,updated_at&order=updated_at.desc&limit=5000',
    $headers
);

$performanceResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/performance_evaluations?select=id,status,updated_at&order=updated_at.desc&limit=5000',
    $headers
);

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_status,submitted_at&order=submitted_at.desc&limit=5000',
    $headers
);

$auditLogsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/activity_logs?select=id,actor_user_id,module_name,action_name,created_at,actor:user_accounts(email)&order=created_at.desc&limit=2000',
    $headers
);

$trainingEnrollmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/training_enrollments?select=*&order=created_at.desc&limit=5000',
    $headers
);

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,is_primary,role:roles(role_key)&expires_at=is.null&limit=5000',
    $headers
);

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

$employmentRecords = isSuccessful($employmentResponse) ? $employmentResponse['data'] : [];
$people = isSuccessful($peopleResponse) ? $peopleResponse['data'] : [];
$employmentAllRecords = isSuccessful($employmentAllResponse) ? $employmentAllResponse['data'] : [];
$offices = isSuccessful($officesResponse) ? $officesResponse['data'] : [];
$attendanceLogs = isSuccessful($attendanceResponse) ? $attendanceResponse['data'] : [];
$payrollItems = isSuccessful($payrollResponse) ? $payrollResponse['data'] : [];
$documents = isSuccessful($documentsResponse) ? $documentsResponse['data'] : [];
$performanceEvaluations = isSuccessful($performanceResponse) ? $performanceResponse['data'] : [];
$applications = isSuccessful($applicationsResponse) ? $applicationsResponse['data'] : [];
$auditLogs = isSuccessful($auditLogsResponse) ? $auditLogsResponse['data'] : [];
$trainingEnrollments = isSuccessful($trainingEnrollmentsResponse) ? $trainingEnrollmentsResponse['data'] : [];
$roleAssignments = isSuccessful($roleAssignmentsResponse) ? $roleAssignmentsResponse['data'] : [];

$dataLoadError = null;
if (!isSuccessful($employmentResponse)) {
    $dataLoadError = 'Employment query failed (HTTP ' . (int)($employmentResponse['status'] ?? 0) . ').';
}
if (!isSuccessful($peopleResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'People query failed (HTTP ' . (int)($peopleResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($employmentAllResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Employment history query failed (HTTP ' . (int)($employmentAllResponse['status'] ?? 0) . ').');
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
if (!isSuccessful($documentsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Documents query failed (HTTP ' . (int)($documentsResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($performanceResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Performance query failed (HTTP ' . (int)($performanceResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($applicationsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Applications query failed (HTTP ' . (int)($applicationsResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($auditLogsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Audit log query failed (HTTP ' . (int)($auditLogsResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($trainingEnrollmentsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Training enrollments query failed (HTTP ' . (int)($trainingEnrollmentsResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($roleAssignmentsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Role assignments query failed (HTTP ' . (int)($roleAssignmentsResponse['status'] ?? 0) . ').');
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

$topDepartmentLabel = 'No division data available.';
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

$reportsPolicyHasNoLateMode = static function (mixed $value) use (&$reportsPolicyHasNoLateMode): bool {
    if (is_array($value)) {
        foreach ($value as $nestedKey => $nestedValue) {
            $normalizedKey = strtolower(trim((string)$nestedKey));
            if (in_array($normalizedKey, ['late_policy_mode', 'late_policy', 'policy_mode', 'mode'], true) && $reportsPolicyHasNoLateMode($nestedValue)) {
                return true;
            }

            if ($reportsPolicyHasNoLateMode($nestedValue)) {
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
            return $reportsPolicyHasNoLateMode($decoded);
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
    if ($reportsPolicyHasNoLateMode($candidate)) {
        $noLatePolicyApproved = true;
        break;
    }
}

$documentPendingCount = 0;
foreach ($documents as $documentRaw) {
    $status = strtolower(trim((string)($documentRaw['document_status'] ?? '')));
    if (in_array($status, ['pending', 'submitted', 'for_review', 'needs_revision'], true)) {
        $documentPendingCount++;
    }
}

$performanceCompletedCount = 0;
foreach ($performanceEvaluations as $evaluationRaw) {
    $status = strtolower(trim((string)($evaluationRaw['status'] ?? '')));
    if (in_array($status, ['completed', 'approved', 'published', 'finalized'], true)) {
        $performanceCompletedCount++;
    }
}

$recruitmentSubmittedCount = 0;
$recruitmentHiredCount = 0;
foreach ($applications as $applicationRaw) {
    $status = strtolower(trim((string)($applicationRaw['application_status'] ?? '')));
    if ($status === 'submitted') {
        $recruitmentSubmittedCount++;
    }
    if ($status === 'hired') {
        $recruitmentHiredCount++;
    }
}

$auditLogsLast30Days = 0;
$auditWindowStartTs = strtotime('-30 days');
foreach ($auditLogs as $auditLogRaw) {
    $createdAt = (string)($auditLogRaw['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    if ($createdTs !== false && $createdTs >= $auditWindowStartTs) {
        $auditLogsLast30Days++;
    }
}

$crossModuleKpis = [
    'attendance_logs' => count($attendanceLogs),
    'payroll_items' => count($payrollItems),
    'recruitment_submitted' => $recruitmentSubmittedCount,
    'recruitment_hired' => $recruitmentHiredCount,
    'documents_total' => count($documents),
    'documents_pending' => $documentPendingCount,
    'performance_completed' => $performanceCompletedCount,
    'audit_logs_30_days' => $auditLogsLast30Days,
];

$peopleById = [];
foreach ($people as $personRow) {
    $personId = (string)($personRow['id'] ?? '');
    if ($personId === '') {
        continue;
    }
    $peopleById[$personId] = (array)$personRow;
}

$demographics = [
    'male' => 0,
    'female' => 0,
    'unspecified' => 0,
    'average_age' => 0.0,
    'total' => 0,
];
$ageAccumulator = 0.0;
$ageCount = 0;
foreach ($uniqueEmploymentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || !isset($peopleById[$personId])) {
        $demographics['unspecified']++;
        $demographics['total']++;
        continue;
    }

    $sexRaw = strtolower(trim((string)($peopleById[$personId]['sex_at_birth'] ?? '')));
    if ($sexRaw === 'male') {
        $demographics['male']++;
    } elseif ($sexRaw === 'female') {
        $demographics['female']++;
    } else {
        $demographics['unspecified']++;
    }

    $dob = (string)($peopleById[$personId]['date_of_birth'] ?? '');
    $dobTs = $dob !== '' ? strtotime($dob) : false;
    if ($dobTs !== false) {
        $age = floor((time() - $dobTs) / (365.25 * 24 * 60 * 60));
        if ($age > 0) {
            $ageAccumulator += $age;
            $ageCount++;
        }
    }

    $demographics['total']++;
}
$demographics['average_age'] = $ageCount > 0 ? round($ageAccumulator / $ageCount, 1) : 0.0;

$currentHeadcount = max(1, count($uniqueEmploymentRecords));
$turnoverWindowStart = strtotime('-365 days');
$separationCount = 0;
foreach ($employmentAllRecords as $employmentRow) {
    $separationDate = (string)($employmentRow['separation_date'] ?? '');
    $separationTs = $separationDate !== '' ? strtotime($separationDate) : false;
    if ($separationTs !== false && $separationTs >= $turnoverWindowStart) {
        $separationCount++;
    }
}
$turnoverRate = round(($separationCount / $currentHeadcount) * 100, 1);

$trainingTotal = 0;
$trainingCompleted = 0;
$trainingFailed = 0;
$trainingDropped = 0;
foreach ($trainingEnrollments as $enrollmentRow) {
    $status = strtolower(trim((string)($enrollmentRow['enrollment_status'] ?? '')));
    if ($status === '') {
        continue;
    }
    $trainingTotal++;
    if ($status === 'completed') {
        $trainingCompleted++;
    } elseif ($status === 'failed') {
        $trainingFailed++;
    } elseif ($status === 'dropped') {
        $trainingDropped++;
    }
}
$trainingCompletionRate = $trainingTotal > 0 ? round(($trainingCompleted / $trainingTotal) * 100, 1) : 0.0;

$roleKeyByUserId = [];
foreach ($roleAssignments as $assignmentRow) {
    $userId = strtolower(trim((string)($assignmentRow['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }
    $roleKey = strtolower(trim((string)($assignmentRow['role']['role_key'] ?? '')));
    if ($roleKey === '') {
        continue;
    }

    if (!isset($roleKeyByUserId[$userId]) || (bool)($assignmentRow['is_primary'] ?? false)) {
        $roleKeyByUserId[$userId] = $roleKey;
    }
}

$activityByModule = [];
$adminActivityCount30Days = 0;
$staffActivityCount30Days = 0;
foreach ($auditLogs as $auditLogRow) {
    $createdAt = (string)($auditLogRow['created_at'] ?? '');
    $createdTs = $createdAt !== '' ? strtotime($createdAt) : false;
    if ($createdTs === false || $createdTs < $auditWindowStartTs) {
        continue;
    }

    $moduleName = trim((string)($auditLogRow['module_name'] ?? ''));
    $moduleKey = $moduleName !== '' ? $moduleName : 'uncategorized';
    $actorUserId = strtolower(trim((string)($auditLogRow['actor_user_id'] ?? '')));
    $roleKey = (string)($roleKeyByUserId[$actorUserId] ?? '');

    if (!isset($activityByModule[$moduleKey])) {
        $activityByModule[$moduleKey] = ['admin' => 0, 'staff' => 0, 'total' => 0];
    }

    if ($roleKey === 'admin') {
        $activityByModule[$moduleKey]['admin']++;
        $adminActivityCount30Days++;
    } elseif ($roleKey === 'staff') {
        $activityByModule[$moduleKey]['staff']++;
        $staffActivityCount30Days++;
    }

    $activityByModule[$moduleKey]['total']++;
}

$activityBreakdownRows = [];
foreach ($activityByModule as $moduleName => $counts) {
    $activityBreakdownRows[] = [
        'module_name' => ucwords(str_replace('_', ' ', (string)$moduleName)),
        'admin' => (int)($counts['admin'] ?? 0),
        'staff' => (int)($counts['staff'] ?? 0),
        'total' => (int)($counts['total'] ?? 0),
    ];
}
usort($activityBreakdownRows, static function (array $left, array $right): int {
    return (int)$right['total'] <=> (int)$left['total'];
});

$advancedAdminAnalytics = [
    'demographics_male' => (int)$demographics['male'],
    'demographics_female' => (int)$demographics['female'],
    'demographics_unspecified' => (int)$demographics['unspecified'],
    'demographics_average_age' => (float)$demographics['average_age'],
    'turnover_rate_annual' => (float)$turnoverRate,
    'separations_annual' => (int)$separationCount,
    'training_completion_rate' => (float)$trainingCompletionRate,
    'training_completed' => (int)$trainingCompleted,
    'training_failed' => (int)$trainingFailed,
    'training_dropped' => (int)$trainingDropped,
    'admin_activity_30_days' => (int)$adminActivityCount30Days,
    'staff_activity_30_days' => (int)$staffActivityCount30Days,
];

$personDepartmentById = [];
foreach ($uniqueEmploymentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $officeId = (string)($record['office_id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? 'Unassigned Office');
}

$demographicsByDivision = [];
foreach ($uniqueEmploymentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $officeId = (string)($record['office_id'] ?? '');
    $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Office');

    if (!isset($demographicsByDivision[$divisionName])) {
        $demographicsByDivision[$divisionName] = [
            'division' => $divisionName,
            'total' => 0,
            'male' => 0,
            'female' => 0,
            'unspecified' => 0,
            'age_sum' => 0.0,
            'age_count' => 0,
        ];
    }

    $demographicsByDivision[$divisionName]['total']++;

    $person = (array)($peopleById[$personId] ?? []);
    $sex = strtolower(trim((string)($person['sex_at_birth'] ?? '')));
    if ($sex === 'male') {
        $demographicsByDivision[$divisionName]['male']++;
    } elseif ($sex === 'female') {
        $demographicsByDivision[$divisionName]['female']++;
    } else {
        $demographicsByDivision[$divisionName]['unspecified']++;
    }

    $dob = (string)($person['date_of_birth'] ?? '');
    $dobTs = $dob !== '' ? strtotime($dob) : false;
    if ($dobTs !== false) {
        $age = floor((time() - $dobTs) / (365.25 * 24 * 60 * 60));
        if ($age > 0) {
            $demographicsByDivision[$divisionName]['age_sum'] += $age;
            $demographicsByDivision[$divisionName]['age_count']++;
        }
    }
}

$demographicsByDivisionRows = [];
foreach ($demographicsByDivision as $divisionName => $counts) {
    $averageAge = (int)$counts['age_count'] > 0
        ? round(((float)$counts['age_sum'] / (int)$counts['age_count']), 1)
        : 0.0;

    $demographicsByDivisionRows[] = [
        'division' => (string)$divisionName,
        'total' => (int)($counts['total'] ?? 0),
        'male' => (int)($counts['male'] ?? 0),
        'female' => (int)($counts['female'] ?? 0),
        'unspecified' => (int)($counts['unspecified'] ?? 0),
        'average_age' => $averageAge,
        'search_text' => strtolower(trim((string)$divisionName . ' ' . (string)($counts['total'] ?? 0) . ' ' . (string)($counts['male'] ?? 0) . ' ' . (string)($counts['female'] ?? 0))),
    ];
}
usort($demographicsByDivisionRows, static function (array $left, array $right): int {
    return strcmp((string)$left['division'], (string)$right['division']);
});

$turnoverTrainingByDivision = [];
foreach ($employmentAllRecords as $employmentRow) {
    $officeId = (string)($employmentRow['office_id'] ?? '');
    $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Office');
    if (!isset($turnoverTrainingByDivision[$divisionName])) {
        $turnoverTrainingByDivision[$divisionName] = [
            'division' => $divisionName,
            'headcount' => 0,
            'hires_365' => 0,
            'separations_365' => 0,
            'training_total' => 0,
            'training_completed' => 0,
        ];
    }

    if ((bool)($employmentRow['is_current'] ?? false)) {
        $turnoverTrainingByDivision[$divisionName]['headcount']++;
    }

    $hireDate = (string)($employmentRow['hire_date'] ?? '');
    $hireTs = $hireDate !== '' ? strtotime($hireDate) : false;
    if ($hireTs !== false && $hireTs >= $turnoverWindowStart) {
        $turnoverTrainingByDivision[$divisionName]['hires_365']++;
    }

    $separationDate = (string)($employmentRow['separation_date'] ?? '');
    $separationTs = $separationDate !== '' ? strtotime($separationDate) : false;
    if ($separationTs !== false && $separationTs >= $turnoverWindowStart) {
        $turnoverTrainingByDivision[$divisionName]['separations_365']++;
    }
}

foreach ($trainingEnrollments as $enrollmentRow) {
    $personId = (string)($enrollmentRow['employee_person_id'] ?? $enrollmentRow['person_id'] ?? $enrollmentRow['participant_person_id'] ?? '');
    $divisionName = (string)($personDepartmentById[$personId] ?? 'Unassigned Office');
    if (!isset($turnoverTrainingByDivision[$divisionName])) {
        $turnoverTrainingByDivision[$divisionName] = [
            'division' => $divisionName,
            'headcount' => 0,
            'hires_365' => 0,
            'separations_365' => 0,
            'training_total' => 0,
            'training_completed' => 0,
        ];
    }

    $status = strtolower(trim((string)($enrollmentRow['enrollment_status'] ?? '')));
    if ($status === '') {
        continue;
    }

    $turnoverTrainingByDivision[$divisionName]['training_total']++;
    if ($status === 'completed') {
        $turnoverTrainingByDivision[$divisionName]['training_completed']++;
    }
}

$turnoverTrainingRows = [];
foreach ($turnoverTrainingByDivision as $divisionRow) {
    $headcount = max(1, (int)($divisionRow['headcount'] ?? 0));
    $separations = (int)($divisionRow['separations_365'] ?? 0);
    $trainingTotalByDivision = (int)($divisionRow['training_total'] ?? 0);
    $trainingCompletedByDivision = (int)($divisionRow['training_completed'] ?? 0);
    $turnoverRateByDivision = round(($separations / $headcount) * 100, 1);
    $trainingRateByDivision = $trainingTotalByDivision > 0
        ? round(($trainingCompletedByDivision / $trainingTotalByDivision) * 100, 1)
        : 0.0;

    $turnoverTrainingRows[] = [
        'division' => (string)($divisionRow['division'] ?? 'Unassigned Office'),
        'headcount' => (int)($divisionRow['headcount'] ?? 0),
        'hires_365' => (int)($divisionRow['hires_365'] ?? 0),
        'separations_365' => $separations,
        'turnover_rate' => $turnoverRateByDivision,
        'training_completion_rate' => $trainingRateByDivision,
        'search_text' => strtolower(trim((string)($divisionRow['division'] ?? '') . ' ' . (string)($divisionRow['headcount'] ?? 0) . ' ' . (string)$turnoverRateByDivision . ' ' . (string)$trainingRateByDivision)),
    ];
}
usort($turnoverTrainingRows, static function (array $left, array $right): int {
    return strcmp((string)$left['division'], (string)$right['division']);
});

$activityRoleLabel = static function (string $roleKey): string {
    $normalized = strtolower(trim($roleKey));
    if ($normalized === 'admin') {
        return 'Admin';
    }
    if ($normalized === 'staff') {
        return 'Staff';
    }
    if ($normalized === '') {
        return 'Unknown';
    }

    return ucwords(str_replace('_', ' ', $normalized));
};

$activityLogRows = [];
$activityRoleFilters = [];
$activityModuleFilters = [];
foreach ($auditLogs as $auditLogRow) {
    $createdAtRaw = (string)($auditLogRow['created_at'] ?? '');
    $createdAtTs = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;
    $createdAtLabel = $createdAtTs !== false ? date('M d, Y h:i A', $createdAtTs) : '-';
    $moduleRaw = trim((string)($auditLogRow['module_name'] ?? ''));
    $moduleLabel = $moduleRaw !== '' ? ucwords(str_replace('_', ' ', $moduleRaw)) : 'Uncategorized';
    $actionRaw = trim((string)($auditLogRow['action_name'] ?? ''));
    $actionLabel = $actionRaw !== '' ? ucwords(str_replace('_', ' ', $actionRaw)) : 'Unknown Action';
    $actorUserId = strtolower(trim((string)($auditLogRow['actor_user_id'] ?? '')));
    $roleKey = (string)($roleKeyByUserId[$actorUserId] ?? '');
    $roleLabel = $activityRoleLabel($roleKey);
    $actorEmail = trim((string)($auditLogRow['actor']['email'] ?? ''));

    $activityLogRows[] = [
        'created_at' => $createdAtLabel,
        'module_label' => $moduleLabel,
        'module_key' => strtolower($moduleLabel),
        'action_label' => $actionLabel,
        'role_label' => $roleLabel,
        'role_key' => strtolower($roleLabel),
        'actor_email' => $actorEmail !== '' ? $actorEmail : '-',
        'search_text' => strtolower(trim($createdAtLabel . ' ' . $moduleLabel . ' ' . $actionLabel . ' ' . $roleLabel . ' ' . $actorEmail)),
    ];

    $activityRoleFilters[$roleLabel] = true;
    $activityModuleFilters[$moduleLabel] = true;
}

$activityRoleFilters = array_keys($activityRoleFilters);
sort($activityRoleFilters);

$activityModuleFilters = array_keys($activityModuleFilters);
sort($activityModuleFilters);

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
