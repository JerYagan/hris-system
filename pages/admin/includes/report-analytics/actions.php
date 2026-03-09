<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('reportAnalyticsAppendPagination')) {
    function reportAnalyticsAppendPagination(string $url, int $limit, int $offset): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'limit=' . max(1, $limit) . '&offset=' . max(0, $offset);
    }
}

if (!function_exists('reportAnalyticsFetchAll')) {
    function reportAnalyticsFetchAll(string $url, array $headers, int $batchSize = 1000, int $maxPages = 100): array
    {
        $rows = [];
        $offset = 0;
        $lastResponse = ['status' => 500, 'data' => [], 'raw' => ''];

        for ($page = 0; $page < $maxPages; $page++) {
            $lastResponse = apiRequest('GET', reportAnalyticsAppendPagination($url, $batchSize, $offset), $headers);
            if (!isSuccessful($lastResponse)) {
                return $lastResponse;
            }

            $batch = is_array($lastResponse['data'] ?? null)
                ? array_values((array)$lastResponse['data'])
                : [];

            $rows = array_merge($rows, $batch);
            if (count($batch) < $batchSize) {
                break;
            }

            $offset += $batchSize;
        }

        $lastResponse['data'] = $rows;
        return $lastResponse;
    }
}

if (!function_exists('reportResolveDateRange')) {
    function reportResolveDateRange(string $coverage, ?string $customStartDate, ?string $customEndDate, string $supabaseUrl, array $headers): array
    {
        $normalized = strtolower(trim($coverage));
        $today = gmdate('Y-m-d');

        if ($normalized === 'custom_range') {
            $from = trim((string)$customStartDate);
            $to = trim((string)$customEndDate);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new RuntimeException('Custom range requires valid start and end dates.');
            }
            if (strtotime($from) === false || strtotime($to) === false || strtotime($from) > strtotime($to)) {
                throw new RuntimeException('Custom range dates are invalid.');
            }

            return ['start_date' => $from, 'end_date' => $to];
        }

        if ($normalized === 'current_cutoff') {
            $cutoffResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/payroll_periods?select=period_start,period_end,status&status=in.(open,processing,posted)&order=period_end.desc&limit=1',
                $headers
            );

            if (!isSuccessful($cutoffResponse) || empty((array)($cutoffResponse['data'] ?? []))) {
                $fallbackResponse = apiRequest(
                    'GET',
                    $supabaseUrl . '/rest/v1/payroll_periods?select=period_start,period_end&order=period_end.desc&limit=1',
                    $headers
                );
                if (isSuccessful($fallbackResponse) && !empty((array)($fallbackResponse['data'] ?? []))) {
                    $period = (array)$fallbackResponse['data'][0];
                    return [
                        'start_date' => (string)($period['period_start'] ?? $today),
                        'end_date' => (string)($period['period_end'] ?? $today),
                    ];
                }

                return [
                    'start_date' => gmdate('Y-m-d', strtotime('-30 days')),
                    'end_date' => $today,
                ];
            }

            $period = (array)$cutoffResponse['data'][0];
            return [
                'start_date' => (string)($period['period_start'] ?? $today),
                'end_date' => (string)($period['period_end'] ?? $today),
            ];
        }

        $days = match ($normalized) {
            'quarterly' => 90,
            'monthly' => 30,
            default => 30,
        };

        return [
            'start_date' => gmdate('Y-m-d', strtotime('-' . $days . ' days')),
            'end_date' => $today,
        ];
    }
}

if (!function_exists('reportDepartmentContext')) {
    function reportDepartmentContext(string $supabaseUrl, array $headers): array
    {
        $officesResponse = reportAnalyticsFetchAll(
            $supabaseUrl . '/rest/v1/offices?select=id,office_name',
            $headers
        );

        $employmentResponse = reportAnalyticsFetchAll(
            $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,is_current&is_current=eq.true',
            $headers
        );

        if (!isSuccessful($officesResponse) || !isSuccessful($employmentResponse)) {
            throw new RuntimeException('Failed to resolve division mapping for report export.');
        }

        $officeNameById = [];
        foreach ((array)($officesResponse['data'] ?? []) as $office) {
            $officeId = (string)($office['id'] ?? '');
            if ($officeId === '') {
                continue;
            }
            $officeNameById[$officeId] = (string)($office['office_name'] ?? '');
        }

        $personDepartmentById = [];
        foreach ((array)($employmentResponse['data'] ?? []) as $employment) {
            $personId = (string)($employment['person_id'] ?? '');
            if ($personId === '' || isset($personDepartmentById[$personId])) {
                continue;
            }

            $officeId = (string)($employment['office_id'] ?? '');
            $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? '');
        }

        return [
            'office_name_by_id' => $officeNameById,
            'person_department_by_id' => $personDepartmentById,
        ];
    }
}

if (!function_exists('reportDepartmentMatches')) {
    function reportDepartmentMatches(string $selectedDepartment, ?string $recordDepartment): bool
    {
        $selected = strtolower(trim($selectedDepartment));
        if ($selected === '' || $selected === 'all') {
            return true;
        }

        return strtolower(trim((string)$recordDepartment)) === $selected;
    }
}

if (!function_exists('reportBuildDataset')) {
    function reportBuildDataset(string $reportType, string $coverage, string $department, array $dateRange, string $supabaseUrl, array $headers): array
    {
        $startDate = (string)($dateRange['start_date'] ?? gmdate('Y-m-d'));
        $endDate = (string)($dateRange['end_date'] ?? gmdate('Y-m-d'));
        $startDateTime = $startDate . 'T00:00:00Z';
        $endDateTime = $endDate . 'T23:59:59Z';
        $departmentContext = reportDepartmentContext($supabaseUrl, $headers);
        $personDepartmentById = (array)($departmentContext['person_department_by_id'] ?? []);
        $officeNameById = (array)($departmentContext['office_name_by_id'] ?? []);

        $reportPolicyHasNoLateMode = static function (mixed $value) use (&$reportPolicyHasNoLateMode): bool {
            if (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    $normalizedKey = strtolower(trim((string)$nestedKey));
                    if (in_array($normalizedKey, ['late_policy_mode', 'late_policy', 'policy_mode', 'mode'], true) && $reportPolicyHasNoLateMode($nestedValue)) {
                        return true;
                    }

                    if ($reportPolicyHasNoLateMode($nestedValue)) {
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
                    return $reportPolicyHasNoLateMode($decoded);
                }
            }

            return false;
        };

        $noLatePolicyApproved = false;
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
            if ($reportPolicyHasNoLateMode($candidate)) {
                $noLatePolicyApproved = true;
                break;
            }
        }

        if ($reportType === 'attendance') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/attendance_logs?select=person_id,attendance_date,attendance_status,late_minutes,hours_worked,source,person:people(first_name,surname)&attendance_date=gte.' . $startDate . '&attendance_date=lte.' . $endDate . '&order=attendance_date.desc',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch attendance data.');
            }

            $columns = $noLatePolicyApproved
                ? ['Employee', 'Attendance Date', 'Status', 'Hours Worked', 'Source']
                : ['Employee', 'Attendance Date', 'Status', 'Late Minutes', 'Hours Worked', 'Source'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $personId = (string)($item['person_id'] ?? '');
                if (!reportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
                $baseStatus = strtolower(trim((string)($item['attendance_status'] ?? '-')));
                $statusLabel = $noLatePolicyApproved && $baseStatus === 'late' ? 'present' : (string)($item['attendance_status'] ?? '-');

                if ($noLatePolicyApproved) {
                    $rows[] = [
                        $employeeName !== '' ? $employeeName : 'Unknown Employee',
                        (string)($item['attendance_date'] ?? '-'),
                        $statusLabel,
                        (string)($item['hours_worked'] ?? '0'),
                        (string)($item['source'] ?? '-'),
                    ];
                    continue;
                }

                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['attendance_date'] ?? '-'),
                    $statusLabel,
                    (string)($item['late_minutes'] ?? '0'),
                    (string)($item['hours_worked'] ?? '0'),
                    (string)($item['source'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'audit_logs') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/activity_logs?select=created_at,module_name,action_name,entity_name,ip_address,actor:user_accounts(email)&created_at=gte.' . $startDateTime . '&created_at=lte.' . $endDateTime . '&order=created_at.desc',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch audit log data.');
            }

            $columns = ['Timestamp', 'Module', 'Action', 'Entity', 'Actor', 'IP Address'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $rows[] = [
                    (string)($item['created_at'] ?? '-'),
                    (string)($item['module_name'] ?? '-'),
                    (string)($item['action_name'] ?? '-'),
                    (string)($item['entity_name'] ?? '-'),
                    (string)($item['actor']['email'] ?? '-'),
                    (string)($item['ip_address'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'payroll') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/payroll_items?select=person_id,gross_pay,net_pay,created_at,person:people(first_name,surname),payroll_run:payroll_runs(run_status,payroll_period:payroll_periods(period_start,period_end,period_code))&order=created_at.desc',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch payroll data.');
            }

            $columns = ['Employee', 'Gross Pay', 'Net Pay', 'Payroll Period'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $personId = (string)($item['person_id'] ?? '');
                if (!reportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $period = is_array($item['payroll_run']['payroll_period'] ?? null)
                    ? (array)$item['payroll_run']['payroll_period']
                    : [];
                $periodStart = (string)($period['period_start'] ?? '');
                $periodEnd = (string)($period['period_end'] ?? '');

                $windowDate = $periodEnd !== '' ? $periodEnd : substr((string)($item['created_at'] ?? ''), 0, 10);
                if ($windowDate === '' || $windowDate < $startDate || $windowDate > $endDate) {
                    continue;
                }

                $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
                $periodLabel = $periodStart !== '' && $periodEnd !== ''
                    ? ($periodStart . ' to ' . $periodEnd)
                    : (string)($period['period_code'] ?? '-');

                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['gross_pay'] ?? '0'),
                    (string)($item['net_pay'] ?? '0'),
                    $periodLabel,
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'performance') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/performance_evaluations?select=employee_person_id,final_rating,status,updated_at,employee:people(first_name,surname),cycle:performance_cycles(cycle_name)&updated_at=gte.' . $startDateTime . '&updated_at=lte.' . $endDateTime . '&order=updated_at.desc',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch performance data.');
            }

            $columns = ['Employee', 'Cycle', 'Final Rating', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $personId = (string)($item['employee_person_id'] ?? '');
                if (!reportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $employeeName = trim(((string)($item['employee']['first_name'] ?? '')) . ' ' . ((string)($item['employee']['surname'] ?? '')));
                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['cycle']['cycle_name'] ?? '-'),
                    (string)($item['final_rating'] ?? '-'),
                    (string)($item['status'] ?? '-'),
                    (string)($item['updated_at'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'documents') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/documents?select=title,owner_person_id,document_status,updated_at,category:document_categories(category_name),owner:people(first_name,surname)&updated_at=gte.' . $startDateTime . '&updated_at=lte.' . $endDateTime . '&order=updated_at.desc',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch documents data.');
            }

            $columns = ['Document', 'Owner', 'Category', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $personId = (string)($item['owner_person_id'] ?? '');
                if (!reportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $ownerName = trim(((string)($item['owner']['first_name'] ?? '')) . ' ' . ((string)($item['owner']['surname'] ?? '')));
                $rows[] = [
                    (string)($item['title'] ?? '-'),
                    $ownerName !== '' ? $ownerName : 'Unknown Owner',
                    (string)($item['category']['category_name'] ?? '-'),
                    (string)($item['document_status'] ?? '-'),
                    (string)($item['updated_at'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'employee_demographics') {
            $employmentResponse = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,is_current&is_current=eq.true',
                $headers
            );
            $peopleResponse = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/people?select=id,sex_at_birth,date_of_birth',
                $headers
            );

            if (!isSuccessful($employmentResponse) || !isSuccessful($peopleResponse)) {
                throw new RuntimeException('Failed to fetch employee demographics data.');
            }

            $peopleById = [];
            foreach ((array)$peopleResponse['data'] as $person) {
                $personId = (string)($person['id'] ?? '');
                if ($personId !== '') {
                    $peopleById[$personId] = (array)$person;
                }
            }

            $bucket = [];
            foreach ((array)$employmentResponse['data'] as $employment) {
                $personId = (string)($employment['person_id'] ?? '');
                $officeId = (string)($employment['office_id'] ?? '');
                $division = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
                if (!reportDepartmentMatches($department, $division)) {
                    continue;
                }

                if (!isset($bucket[$division])) {
                    $bucket[$division] = [
                        'total' => 0,
                        'male' => 0,
                        'female' => 0,
                        'unspecified' => 0,
                        'age_sum' => 0.0,
                        'age_count' => 0,
                    ];
                }

                $bucket[$division]['total']++;

                $sex = strtolower(trim((string)($peopleById[$personId]['sex_at_birth'] ?? '')));
                if ($sex === 'male') {
                    $bucket[$division]['male']++;
                } elseif ($sex === 'female') {
                    $bucket[$division]['female']++;
                } else {
                    $bucket[$division]['unspecified']++;
                }

                $dob = (string)($peopleById[$personId]['date_of_birth'] ?? '');
                $dobTs = $dob !== '' ? strtotime($dob) : false;
                if ($dobTs !== false) {
                    $age = floor((time() - $dobTs) / (365.25 * 24 * 60 * 60));
                    if ($age > 0) {
                        $bucket[$division]['age_sum'] += $age;
                        $bucket[$division]['age_count']++;
                    }
                }
            }

            ksort($bucket);
            $columns = ['Division', 'Total Employees', 'Male', 'Female', 'Unspecified', 'Average Age'];
            $rows = [];
            foreach ($bucket as $division => $counts) {
                $averageAge = (int)$counts['age_count'] > 0
                    ? number_format(((float)$counts['age_sum'] / (int)$counts['age_count']), 1)
                    : '0.0';

                $rows[] = [
                    (string)$division,
                    (string)($counts['total'] ?? 0),
                    (string)($counts['male'] ?? 0),
                    (string)($counts['female'] ?? 0),
                    (string)($counts['unspecified'] ?? 0),
                    (string)$averageAge,
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'turnover_rates') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/employment_records?select=office_id,employment_status,hire_date,separation_date,is_current',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch turnover data.');
            }

            $turnoverByDivision = [];
            foreach ((array)$response['data'] as $row) {
                $officeId = (string)($row['office_id'] ?? '');
                $division = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
                if (!reportDepartmentMatches($department, $division)) {
                    continue;
                }

                if (!isset($turnoverByDivision[$division])) {
                    $turnoverByDivision[$division] = [
                        'headcount' => 0,
                        'hires' => 0,
                        'separations' => 0,
                    ];
                }

                if ((bool)($row['is_current'] ?? false)) {
                    $turnoverByDivision[$division]['headcount']++;
                }

                $hireDate = (string)($row['hire_date'] ?? '');
                if ($hireDate !== '' && $hireDate >= $startDate && $hireDate <= $endDate) {
                    $turnoverByDivision[$division]['hires']++;
                }

                $separationDate = (string)($row['separation_date'] ?? '');
                if ($separationDate !== '' && $separationDate >= $startDate && $separationDate <= $endDate) {
                    $turnoverByDivision[$division]['separations']++;
                }
            }

            ksort($turnoverByDivision);
            $columns = ['Division', 'Headcount', 'Hires', 'Separations', 'Turnover Rate (%)'];
            $rows = [];
            foreach ($turnoverByDivision as $division => $metrics) {
                $headcount = max(1, (int)($metrics['headcount'] ?? 0));
                $separations = (int)($metrics['separations'] ?? 0);
                $turnoverRate = number_format(($separations / $headcount) * 100, 1);

                $rows[] = [
                    (string)$division,
                    (string)($metrics['headcount'] ?? 0),
                    (string)($metrics['hires'] ?? 0),
                    (string)$separations,
                    (string)$turnoverRate,
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'training_effectiveness') {
            $response = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/training_enrollments?select=*',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch training effectiveness data.');
            }

            $total = 0;
            $completed = 0;
            $failed = 0;
            $dropped = 0;
            foreach ((array)$response['data'] as $row) {
                $personId = (string)($row['employee_person_id'] ?? $row['person_id'] ?? $row['participant_person_id'] ?? '');
                if ($department !== 'all' && $personId !== '' && !reportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $status = strtolower(trim((string)($row['enrollment_status'] ?? '')));
                if ($status === '') {
                    continue;
                }

                $total++;
                if ($status === 'completed') {
                    $completed++;
                } elseif ($status === 'failed') {
                    $failed++;
                } elseif ($status === 'dropped') {
                    $dropped++;
                }
            }

            $completionRate = $total > 0 ? number_format(($completed / $total) * 100, 1) : '0.0';
            $effectivenessRate = $total > 0 ? number_format((($completed - $failed) / $total) * 100, 1) : '0.0';

            $columns = ['Metric', 'Value'];
            $rows = [
                ['Total Enrollments', (string)$total],
                ['Completed', (string)$completed],
                ['Failed', (string)$failed],
                ['Dropped', (string)$dropped],
                ['Completion Rate (%)', (string)$completionRate],
                ['Effectiveness Index (%)', (string)$effectivenessRate],
            ];

            return [$columns, $rows];
        }

        if ($reportType === 'activity_summary') {
            $activityResponse = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/activity_logs?select=actor_user_id,module_name,action_name,created_at&created_at=gte.' . $startDateTime . '&created_at=lte.' . $endDateTime . '&order=created_at.desc',
                $headers
            );
            $roleResponse = reportAnalyticsFetchAll(
                $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,is_primary,role:roles(role_key)&expires_at=is.null',
                $headers
            );

            if (!isSuccessful($activityResponse) || !isSuccessful($roleResponse)) {
                throw new RuntimeException('Failed to fetch activity summary data.');
            }

            $roleByUserId = [];
            foreach ((array)$roleResponse['data'] as $assignment) {
                $userId = strtolower(trim((string)($assignment['user_id'] ?? '')));
                if ($userId === '') {
                    continue;
                }
                $roleKey = strtolower(trim((string)($assignment['role']['role_key'] ?? '')));
                if ($roleKey === '') {
                    continue;
                }

                if (!isset($roleByUserId[$userId]) || (bool)($assignment['is_primary'] ?? false)) {
                    $roleByUserId[$userId] = $roleKey;
                }
            }

            $moduleCounts = [];
            foreach ((array)$activityResponse['data'] as $entry) {
                $moduleName = trim((string)($entry['module_name'] ?? ''));
                $moduleKey = $moduleName !== '' ? $moduleName : 'uncategorized';
                $actorUserId = strtolower(trim((string)($entry['actor_user_id'] ?? '')));
                $roleKey = (string)($roleByUserId[$actorUserId] ?? '');

                if (!isset($moduleCounts[$moduleKey])) {
                    $moduleCounts[$moduleKey] = ['admin' => 0, 'staff' => 0, 'total' => 0];
                }

                if ($roleKey === 'admin') {
                    $moduleCounts[$moduleKey]['admin']++;
                } elseif ($roleKey === 'staff') {
                    $moduleCounts[$moduleKey]['staff']++;
                }

                $moduleCounts[$moduleKey]['total']++;
            }

            uasort($moduleCounts, static function (array $left, array $right): int {
                return (int)$right['total'] <=> (int)$left['total'];
            });

            $columns = ['Module', 'Admin Activities', 'Staff Activities', 'Total Activities'];
            $rows = [];
            foreach ($moduleCounts as $moduleName => $counts) {
                $rows[] = [
                    ucwords(str_replace('_', ' ', (string)$moduleName)),
                    (string)($counts['admin'] ?? 0),
                    (string)($counts['staff'] ?? 0),
                    (string)($counts['total'] ?? 0),
                ];
            }

            return [$columns, $rows];
        }

        $response = reportAnalyticsFetchAll(
            $supabaseUrl . '/rest/v1/applications?select=application_ref_no,application_status,submitted_at,job:job_postings(title,office_id),applicant:applicant_profiles(full_name,email)&submitted_at=gte.' . $startDateTime . '&submitted_at=lte.' . $endDateTime . '&order=submitted_at.desc',
            $headers
        );

        if (!isSuccessful($response)) {
            throw new RuntimeException('Failed to fetch recruitment data.');
        }

        $columns = ['Reference No', 'Applicant', 'Email', 'Position', 'Status', 'Submitted At'];
        $rows = [];
        foreach ((array)$response['data'] as $item) {
            $jobOfficeId = (string)($item['job']['office_id'] ?? '');
            $jobDepartment = (string)($officeNameById[$jobOfficeId] ?? '');
            if (!reportDepartmentMatches($department, $jobDepartment)) {
                continue;
            }

            $rows[] = [
                (string)($item['application_ref_no'] ?? '-'),
                (string)($item['applicant']['full_name'] ?? '-'),
                (string)($item['applicant']['email'] ?? '-'),
                (string)($item['job']['title'] ?? '-'),
                (string)($item['application_status'] ?? '-'),
                (string)($item['submitted_at'] ?? '-'),
            ];
        }

        return [$columns, $rows];
    }
}

if (!function_exists('reportWriteSpreadsheet')) {
    function reportWriteSpreadsheet(string $fileFormat, array $columns, array $rows, string $filePath): void
    {
        $spreadsheetClass = 'PhpOffice\\PhpSpreadsheet\\Spreadsheet';
        $spreadsheet = new $spreadsheetClass();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $columnName) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $columnName);
        }

        $rowCursor = 2;
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $sheet->setCellValueByColumnAndRow($index + 1, $rowCursor, (string)$value);
            }
            $rowCursor++;
        }

        if ($fileFormat === 'csv') {
            $csvWriterClass = 'PhpOffice\\PhpSpreadsheet\\Writer\\Csv';
            $writer = new $csvWriterClass($spreadsheet);
        } else {
            $xlsxWriterClass = 'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';
            $writer = new $xlsxWriterClass($spreadsheet);
        }

        $writer->save($filePath);
    }
}

if (!function_exists('reportWritePdf')) {
    function reportWritePdf(string $title, array $columns, array $rows, string $filePath): void
    {
        $html = '<h2 style="font-family: Arial, sans-serif; margin-bottom: 10px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<table width="100%" cellspacing="0" cellpadding="6" border="1" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">';
        $html .= '<thead><tr>';
        foreach ($columns as $columnName) {
            $html .= '<th style="background:#f8fafc; text-align:left;">' . htmlspecialchars((string)$columnName, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if (empty($rows)) {
            $html .= '<tr><td colspan="' . count($columns) . '">No records available.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        $dompdfClass = 'Dompdf\\Dompdf';
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        file_put_contents($filePath, $dompdf->output());
    }
}

if (!function_exists('reportPatchStatus')) {
    function reportPatchStatus(string $supabaseUrl, array $headers, string $reportId, string $status, ?string $storagePath = null): void
    {
        if ($reportId === '') {
            return;
        }

        $payload = [
            'status' => $status,
            'generated_at' => gmdate('c'),
        ];

        if ($storagePath !== null) {
            $payload['storage_path'] = $storagePath;
        }

        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/generated_reports?id=eq.' . $reportId,
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'export_report') {
    $reportType = strtolower((string)(cleanText($_POST['report_type'] ?? null) ?? ''));
    $coverage = cleanText($_POST['coverage'] ?? null) ?? 'current_cutoff';
    $fileFormat = strtolower((string)(cleanText($_POST['file_format'] ?? null) ?? 'pdf'));
    $department = cleanText($_POST['department_filter'] ?? null) ?? 'all';
    $customStartDate = cleanText($_POST['custom_start_date'] ?? null);
    $customEndDate = cleanText($_POST['custom_end_date'] ?? null);

    $allowedTypes = ['attendance', 'payroll', 'performance', 'documents', 'recruitment', 'audit_logs', 'employee_demographics', 'turnover_rates', 'training_effectiveness', 'activity_summary'];
    if (!in_array($reportType, $allowedTypes, true)) {
        redirectWithState('error', 'Invalid report type selected.');
    }

    $allowedFormats = ['pdf', 'xlsx', 'csv'];
    if (!in_array($fileFormat, $allowedFormats, true)) {
        redirectWithState('error', 'Invalid export format selected.');
    }

    $allowedCoverages = ['current_cutoff', 'monthly', 'quarterly', 'custom_range'];
    if (!in_array(strtolower(trim((string)$coverage)), $allowedCoverages, true)) {
        redirectWithState('error', 'Invalid coverage selected.');
    }

    try {
        $dateRange = reportResolveDateRange((string)$coverage, $customStartDate, $customEndDate, $supabaseUrl, $headers);
    } catch (Throwable $throwable) {
        redirectWithState('error', $throwable->getMessage());
    }

    $projectRoot = dirname(__DIR__, 4);
    $autoloadPath = $projectRoot . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        redirectWithState('error', 'Export libraries are missing. Run: composer require dompdf/dompdf phpoffice/phpspreadsheet');
    }

    require_once $autoloadPath;

    if ($fileFormat === 'pdf' && !class_exists('Dompdf\\Dompdf')) {
        redirectWithState('error', 'Dompdf is not available. Install it with composer require dompdf/dompdf');
    }

    if (in_array($fileFormat, ['xlsx', 'csv'], true) && !class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        redirectWithState('error', 'PhpSpreadsheet is not available. Install it with composer require phpoffice/phpspreadsheet');
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/generated_reports',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'requested_by' => $adminUserId !== '' ? $adminUserId : null,
            'report_type' => $reportType,
            'filters_json' => [
                'coverage' => $coverage,
                'department_filter' => $department,
                'start_date' => (string)($dateRange['start_date'] ?? ''),
                'end_date' => (string)($dateRange['end_date'] ?? ''),
            ],
            'file_format' => $fileFormat,
            'status' => 'queued',
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to queue report export request.');
    }

    $reportId = (string)($insertResponse['data'][0]['id'] ?? '');

    try {
        [$columns, $rows] = reportBuildDataset($reportType, $coverage, $department, $dateRange, $supabaseUrl, $headers);

        $exportsDir = $projectRoot . '/storage/reports';
        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0775, true);
        }

        $baseFileName = 'report-' . $reportType . '-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(8)), 0, 8);
        $extension = $fileFormat === 'xlsx' ? 'xlsx' : ($fileFormat === 'csv' ? 'csv' : 'pdf');
        $fileName = $baseFileName . '.' . $extension;
        $absolutePath = $exportsDir . '/' . $fileName;
        $storagePath = 'storage/reports/' . $fileName;

        $title = strtoupper($reportType) . ' REPORT';
        if ($fileFormat === 'pdf') {
            reportWritePdf($title, $columns, $rows, $absolutePath);
        } else {
            reportWriteSpreadsheet($fileFormat, $columns, $rows, $absolutePath);
        }

        reportPatchStatus($supabaseUrl, $headers, $reportId, 'ready', $storagePath);

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
                'module_name' => 'report_analytics',
                'entity_name' => 'generated_reports',
                'entity_id' => $reportId !== '' ? $reportId : null,
                'action_name' => 'export_report',
                'old_data' => null,
                'new_data' => [
                    'report_type' => $reportType,
                    'coverage' => $coverage,
                    'file_format' => $fileFormat,
                    'department_filter' => $department,
                    'start_date' => (string)($dateRange['start_date'] ?? ''),
                    'end_date' => (string)($dateRange['end_date'] ?? ''),
                    'storage_path' => $storagePath,
                    'row_count' => count($rows),
                ],
                'ip_address' => clientIp(),
            ]]
        );

        if (!is_file($absolutePath)) {
            throw new RuntimeException('Generated file was not created.');
        }

        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'text/csv',
        };

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . (string)filesize($absolutePath));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');
        readfile($absolutePath);
        exit;
    } catch (Throwable $exception) {
        reportPatchStatus($supabaseUrl, $headers, $reportId, 'failed', null);
        redirectWithState('error', 'Failed to generate report file: ' . $exception->getMessage());
    }
}

redirectWithState('error', 'Unknown report analytics action.');
