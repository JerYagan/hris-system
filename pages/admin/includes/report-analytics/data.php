<?php

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

if (!function_exists('reportAnalyticsApiRequestWithHeaders')) {
    function reportAnalyticsApiRequestWithHeaders(string $method, string $url, array $headers, ?array $body = null, bool $noBody = false): array
    {
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            static function ($curlHandle, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $normalized = trim($headerLine);
                if ($normalized === '' || !str_contains($normalized, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $normalized, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);

                return $length;
            }
        );

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        if ($noBody) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $startedAt = microtime(true);
        $responseBody = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
        curl_close($ch);

        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status' => $statusCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
            'error' => $error !== '' ? $error : null,
            'duration_ms' => $durationMs,
            'response_headers' => $responseHeaders,
        ];
    }
}

if (!function_exists('reportAnalyticsCountFromResponse')) {
    function reportAnalyticsCountFromResponse(array $response): ?int
    {
        $headers = (array)($response['response_headers'] ?? []);
        $contentRange = trim((string)($headers['content-range'] ?? ''));
        if ($contentRange !== '' && preg_match('#/(\d+)$#', $contentRange, $matches) === 1) {
            return (int)$matches[1];
        }

        if (isset($response['count']) && is_numeric($response['count'])) {
            return (int)$response['count'];
        }

        $rows = is_array($response['data'] ?? null) ? (array)$response['data'] : [];
        return $rows === [] ? 0 : count($rows);
    }
}

if (!function_exists('reportAnalyticsFetchCount')) {
    function reportAnalyticsFetchCount(string $url, array $headers): array
    {
        $countHeaders = array_merge($headers, ['Prefer: count=exact', 'Range: 0-0']);
        $headResponse = reportAnalyticsApiRequestWithHeaders('HEAD', $url, $countHeaders, null, true);
        $headResponse['count'] = reportAnalyticsCountFromResponse($headResponse);
        if (isSuccessful($headResponse) && $headResponse['count'] !== null) {
            return $headResponse;
        }

        $getResponse = reportAnalyticsApiRequestWithHeaders(
            'GET',
            reportAnalyticsAppendPagination($url, 1, 0),
            array_merge($headers, ['Prefer: count=exact'])
        );
        $getResponse['count'] = reportAnalyticsCountFromResponse($getResponse);

        return $getResponse;
    }
}

if (!function_exists('reportAnalyticsResponseHasMissingAttendanceDateColumn')) {
    function reportAnalyticsResponseHasMissingAttendanceDateColumn(array $response): bool
    {
        $raw = strtolower((string)($response['raw'] ?? ''));
        return (int)($response['status'] ?? 0) === 400
            && str_contains($raw, 'attendance_date')
            && str_contains($raw, 'does not exist');
    }
}

if (!function_exists('reportAnalyticsNormalizeAttendanceRows')) {
    function reportAnalyticsNormalizeAttendanceRows(array $rows, string $dateKey): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $item = (array)$row;
            if ($dateKey !== 'attendance_date') {
                $dateValue = trim((string)($item[$dateKey] ?? ''));
                $timestamp = strtotime($dateValue);
                $item['attendance_date'] = $timestamp === false ? ($dateValue !== '' ? $dateValue : '-') : gmdate('Y-m-d', $timestamp);
            }
            $normalized[] = $item;
        }

        return $normalized;
    }
}

$reportAnalyticsDataStage = (string)($reportAnalyticsDataStage ?? 'full');

$reportAnalyticsTurnoverStartDate = date('Y-m-d', strtotime('-365 days'));
$reportAnalyticsRollingWindowStartDate = date('Y-m-d', strtotime('-60 days'));
$reportAnalyticsEmploymentHistoryFilter = rawurlencode('(is_current.eq.true,hire_date.gte.' . $reportAnalyticsTurnoverStartDate . ',separation_date.gte.' . $reportAnalyticsTurnoverStartDate . ')');
$reportAnalyticsActivityWindowStart = rawurlencode(gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days')));

if ($reportAnalyticsDataStage === 'summary') {
    $thirtyDaysAgoDate = date('Y-m-d', strtotime('-30 days'));

    $employmentSummaryResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,employment_status,hire_date&is_current=eq.true',
        $headers
    );
    $officesResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/offices?select=id,office_name',
        $headers
    );

    $attendanceCountResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/attendance_logs?select=id&attendance_date=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate),
        $headers
    );
    if (reportAnalyticsResponseHasMissingAttendanceDateColumn($attendanceCountResponse)) {
        $attendanceCountResponse = reportAnalyticsFetchCount(
            $supabaseUrl . '/rest/v1/attendance_logs?select=id&created_at=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate . 'T00:00:00Z'),
            $headers
        );
    }
    $payrollCountResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/payroll_items?select=id&created_at=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate . 'T00:00:00Z'),
        $headers
    );
    $recruitmentSubmittedResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/applications?select=id&application_status=eq.submitted',
        $headers
    );
    $recruitmentHiredResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/applications?select=id&application_status=eq.hired',
        $headers
    );
    $exportedReportsTotalResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/generated_reports?select=id',
        $headers
    );
    $exportedReportsReadyResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/generated_reports?select=id&status=eq.ready',
        $headers
    );
    $performanceCompletedResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/performance_evaluations?select=id&status=in.(completed,approved,published,finalized)',
        $headers
    );
    $auditLogsCountResponse = reportAnalyticsFetchCount(
        $supabaseUrl . '/rest/v1/activity_logs?select=id&created_at=gte.' . $reportAnalyticsActivityWindowStart,
        $headers
    );

    $dataLoadError = null;
    $summaryResponses = [
        ['label' => 'Employment office', 'response' => $employmentSummaryResponse],
        ['label' => 'Division', 'response' => $officesResponse],
        ['label' => 'Attendance count', 'response' => $attendanceCountResponse],
        ['label' => 'Payroll count', 'response' => $payrollCountResponse],
        ['label' => 'Recruitment submitted count', 'response' => $recruitmentSubmittedResponse],
        ['label' => 'Recruitment hired count', 'response' => $recruitmentHiredResponse],
        ['label' => 'Exported reports total count', 'response' => $exportedReportsTotalResponse],
        ['label' => 'Exported reports ready count', 'response' => $exportedReportsReadyResponse],
        ['label' => 'Performance completed count', 'response' => $performanceCompletedResponse],
        ['label' => 'Audit log count', 'response' => $auditLogsCountResponse],
    ];

    foreach ($summaryResponses as $entry) {
        $response = (array)$entry['response'];
        if (isSuccessful($response)) {
            continue;
        }

        $piece = $entry['label'] . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
        $raw = trim((string)($response['raw'] ?? ''));
        if ($raw !== '') {
            $piece .= ' ' . $raw;
        }
        $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $piece) : $piece;
    }

    $officeNameById = [];
    foreach ((array)($officesResponse['data'] ?? []) as $office) {
        $officeId = (string)($office['id'] ?? '');
        if ($officeId === '') {
            continue;
        }

        $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
    }

    $currentEmploymentByPerson = [];
    foreach ((array)($employmentSummaryResponse['data'] ?? []) as $record) {
        $personId = (string)($record['person_id'] ?? '');
        if ($personId === '' || isset($currentEmploymentByPerson[$personId])) {
            continue;
        }

        $currentEmploymentByPerson[$personId] = (array)$record;
    }

    $totalEmployees = count($currentEmploymentByPerson);
    $activeCount = 0;
    $onLeaveCount = 0;
    $inactiveCount = 0;
    $newHiresLast30Days = 0;

    foreach ($currentEmploymentByPerson as $record) {
        $employmentStatus = strtolower(trim((string)($record['employment_status'] ?? '')));
        if ($employmentStatus === 'active') {
            $activeCount += 1;
        } elseif ($employmentStatus === 'on_leave') {
            $onLeaveCount += 1;
        } else {
            $inactiveCount += 1;
        }

        $hireDate = trim((string)($record['hire_date'] ?? ''));
        if ($hireDate !== '' && $hireDate >= $thirtyDaysAgoDate) {
            $newHiresLast30Days += 1;
        }
    }

    $departmentCounts = [];
    foreach ($currentEmploymentByPerson as $record) {
        $officeId = (string)($record['office_id'] ?? '');
        if ($officeId === '') {
            continue;
        }

        $departmentCounts[$officeId] = (int)($departmentCounts[$officeId] ?? 0) + 1;
    }

    $topDepartmentLabel = 'No division data available.';
    if ($departmentCounts !== []) {
        arsort($departmentCounts);
        $topOfficeId = (string)array_key_first($departmentCounts);
        $topDepartmentCount = (int)($departmentCounts[$topOfficeId] ?? 0);
        $topDepartmentName = (string)($officeNameById[$topOfficeId] ?? 'Unassigned Division');
        $topDepartmentLabel = $topDepartmentName . ' - ' . $topDepartmentCount . ' Employee' . ($topDepartmentCount === 1 ? '' : 's');
    }

    $crossModuleKpis = [
        'attendance_logs' => reportAnalyticsCountFromResponse($attendanceCountResponse) ?? 0,
        'payroll_items' => reportAnalyticsCountFromResponse($payrollCountResponse) ?? 0,
        'recruitment_submitted' => reportAnalyticsCountFromResponse($recruitmentSubmittedResponse) ?? 0,
        'recruitment_hired' => reportAnalyticsCountFromResponse($recruitmentHiredResponse) ?? 0,
        'exported_reports_total' => reportAnalyticsCountFromResponse($exportedReportsTotalResponse) ?? 0,
        'exported_reports_ready' => reportAnalyticsCountFromResponse($exportedReportsReadyResponse) ?? 0,
        'performance_completed' => reportAnalyticsCountFromResponse($performanceCompletedResponse) ?? 0,
        'audit_logs_30_days' => reportAnalyticsCountFromResponse($auditLogsCountResponse) ?? 0,
    ];

    return;
}

if ($reportAnalyticsDataStage === 'workforce') {
    $employmentResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,employment_status,hire_date,is_current&is_current=eq.true',
        $headers
    );
    $peopleResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/people?select=id,first_name,surname,middle_name',
        $headers
    );
    $officesResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/offices?select=id,office_name',
        $headers
    );

    $employmentRecords = isSuccessful($employmentResponse) ? (array)$employmentResponse['data'] : [];
    $people = isSuccessful($peopleResponse) ? (array)$peopleResponse['data'] : [];
    $offices = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];

    $dataLoadError = null;
    if (!isSuccessful($employmentResponse)) {
        $dataLoadError = 'Employment query failed (HTTP ' . (int)($employmentResponse['status'] ?? 0) . ').';
    }
    if (!isSuccessful($peopleResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'People query failed (HTTP ' . (int)($peopleResponse['status'] ?? 0) . ').');
    }
    if (!isSuccessful($officesResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Division query failed (HTTP ' . (int)($officesResponse['status'] ?? 0) . ').');
    }

    $officeNameById = [];
    foreach ($offices as $office) {
        $officeId = (string)($office['id'] ?? '');
        if ($officeId === '') {
            continue;
        }

        $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
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

    $currentEmploymentByPerson = [];
    foreach ($employmentRecords as $record) {
        $personId = (string)($record['person_id'] ?? '');
        if ($personId === '' || isset($currentEmploymentByPerson[$personId])) {
            continue;
        }

        $currentEmploymentByPerson[$personId] = (array)$record;
    }

    $uniqueEmploymentRecords = array_values($currentEmploymentByPerson);
    $employmentStatusLabel = static function (string $status): string {
        $normalized = strtolower(trim($status));
        if ($normalized === '') {
            return 'Unspecified';
        }

        return ucwords(str_replace('_', ' ', $normalized));
    };

    $activeCount = 0;
    $onLeaveCount = 0;
    $inactiveCount = 0;
    $departmentCounts = [];
    $employeeRows = [];
    $employeeStatusFilters = [];
    $employeeDepartmentFilters = [];

    foreach ($uniqueEmploymentRecords as $record) {
        $personId = (string)($record['person_id'] ?? '');
        $officeId = (string)($record['office_id'] ?? '');
        $statusRaw = strtolower(trim((string)($record['employment_status'] ?? '')));
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

        $statusLabel = $employmentStatusLabel($statusRaw);
        $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
        $employeeName = (string)($personNameById[$personId] ?? 'Employee #' . ($personId !== '' ? substr($personId, 0, 8) : 'N/A'));
        $hireDateRaw = (string)($record['hire_date'] ?? '');
        $hireDateLabel = $hireDateRaw !== '' ? date('M d, Y', strtotime($hireDateRaw)) : '-';

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

    $divisionHeadcountChartRows = [];
    foreach ($departmentCounts as $officeId => $count) {
        $divisionHeadcountChartRows[] = [
            'label' => (string)($officeNameById[(string)$officeId] ?? 'Unassigned Division'),
            'value' => (int)$count,
        ];
    }
    usort($divisionHeadcountChartRows, static function (array $left, array $right): int {
        return (int)$right['value'] <=> (int)$left['value'];
    });
    $divisionHeadcountChartRows = array_slice($divisionHeadcountChartRows, 0, 8);

    $reportAnalyticsChartPayloadJson = json_encode(
        [
            'employeeStatus' => [
                'labels' => ['Active', 'On Leave', 'Inactive'],
                'values' => [(int)$activeCount, (int)$onLeaveCount, (int)$inactiveCount],
            ],
            'divisionHeadcount' => [
                'labels' => array_map(static fn (array $row): string => (string)$row['label'], $divisionHeadcountChartRows),
                'values' => array_map(static fn (array $row): int => (int)$row['value'], $divisionHeadcountChartRows),
            ],
        ],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return;
}

if ($reportAnalyticsDataStage === 'demographics') {
    $employmentResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,is_current&is_current=eq.true',
        $headers
    );
    $peopleResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/people?select=id,sex_at_birth,date_of_birth',
        $headers
    );
    $officesResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/offices?select=id,office_name',
        $headers
    );

    $employmentRecords = isSuccessful($employmentResponse) ? (array)$employmentResponse['data'] : [];
    $people = isSuccessful($peopleResponse) ? (array)$peopleResponse['data'] : [];
    $offices = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];

    $dataLoadError = null;
    if (!isSuccessful($employmentResponse)) {
        $dataLoadError = 'Employment query failed (HTTP ' . (int)($employmentResponse['status'] ?? 0) . ').';
    }
    if (!isSuccessful($peopleResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'People query failed (HTTP ' . (int)($peopleResponse['status'] ?? 0) . ').');
    }
    if (!isSuccessful($officesResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Division query failed (HTTP ' . (int)($officesResponse['status'] ?? 0) . ').');
    }

    $officeNameById = [];
    foreach ($offices as $office) {
        $officeId = (string)($office['id'] ?? '');
        if ($officeId === '') {
            continue;
        }

        $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
    }

    $peopleById = [];
    foreach ($people as $personRow) {
        $personId = (string)($personRow['id'] ?? '');
        if ($personId === '') {
            continue;
        }
        $peopleById[$personId] = (array)$personRow;
    }

    $currentEmploymentByPerson = [];
    foreach ($employmentRecords as $record) {
        $personId = (string)($record['person_id'] ?? '');
        if ($personId === '' || isset($currentEmploymentByPerson[$personId])) {
            continue;
        }

        $currentEmploymentByPerson[$personId] = (array)$record;
    }

    $demographicsByDivision = [];
    foreach ($currentEmploymentByPerson as $personId => $record) {
        $officeId = (string)($record['office_id'] ?? '');
        $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');

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

    $demographicsChartRows = $demographicsByDivisionRows;
    usort($demographicsChartRows, static function (array $left, array $right): int {
        return (int)$right['total'] <=> (int)$left['total'];
    });
    $demographicsChartRows = array_slice($demographicsChartRows, 0, 8);

    $reportAnalyticsChartPayloadJson = json_encode(
        [
            'demographicsByDivision' => [
                'labels' => array_map(static fn (array $row): string => (string)$row['division'], $demographicsChartRows),
                'male' => array_map(static fn (array $row): int => (int)$row['male'], $demographicsChartRows),
                'female' => array_map(static fn (array $row): int => (int)$row['female'], $demographicsChartRows),
                'unspecified' => array_map(static fn (array $row): int => (int)$row['unspecified'], $demographicsChartRows),
            ],
        ],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return;
}

if ($reportAnalyticsDataStage === 'turnover') {
    $turnoverWindowStart = strtotime('-365 days');
    $employmentAllResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,hire_date,separation_date,is_current&or=' . $reportAnalyticsEmploymentHistoryFilter,
        $headers
    );
    $officesResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/offices?select=id,office_name',
        $headers
    );
    $trainingEnrollmentsResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/training_enrollments?select=person_id,enrollment_status',
        $headers
    );

    $employmentAllRecords = isSuccessful($employmentAllResponse) ? (array)$employmentAllResponse['data'] : [];
    $offices = isSuccessful($officesResponse) ? (array)$officesResponse['data'] : [];
    $trainingEnrollments = isSuccessful($trainingEnrollmentsResponse) ? (array)$trainingEnrollmentsResponse['data'] : [];

    $dataLoadError = null;
    if (!isSuccessful($employmentAllResponse)) {
        $dataLoadError = 'Employment history query failed (HTTP ' . (int)($employmentAllResponse['status'] ?? 0) . ').';
    }
    if (!isSuccessful($officesResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Division query failed (HTTP ' . (int)($officesResponse['status'] ?? 0) . ').');
    }
    if (!isSuccessful($trainingEnrollmentsResponse)) {
        $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Training enrollments query failed (HTTP ' . (int)($trainingEnrollmentsResponse['status'] ?? 0) . ').');
    }

    $officeNameById = [];
    foreach ($offices as $office) {
        $officeId = (string)($office['id'] ?? '');
        if ($officeId === '') {
            continue;
        }
        $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
    }

    $personDepartmentById = [];
    foreach ($employmentAllRecords as $record) {
        if (!(bool)($record['is_current'] ?? false)) {
            continue;
        }

        $personId = (string)($record['person_id'] ?? '');
        if ($personId === '' || isset($personDepartmentById[$personId])) {
            continue;
        }

        $officeId = (string)($record['office_id'] ?? '');
        $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
    }

    $turnoverTrainingByDivision = [];
    foreach ($employmentAllRecords as $employmentRow) {
        $officeId = (string)($employmentRow['office_id'] ?? '');
        $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
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
        $divisionName = (string)($personDepartmentById[$personId] ?? 'Unassigned Division');
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
            'division' => (string)($divisionRow['division'] ?? 'Unassigned Division'),
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

    $turnoverTrainingChartRows = $turnoverTrainingRows;
    usort($turnoverTrainingChartRows, static function (array $left, array $right): int {
        return (int)$right['headcount'] <=> (int)$left['headcount'];
    });
    $turnoverTrainingChartRows = array_slice($turnoverTrainingChartRows, 0, 8);

    $reportAnalyticsChartPayloadJson = json_encode(
        [
            'turnoverTraining' => [
                'labels' => array_map(static fn (array $row): string => (string)$row['division'], $turnoverTrainingChartRows),
                'hires' => array_map(static fn (array $row): int => (int)$row['hires_365'], $turnoverTrainingChartRows),
                'separations' => array_map(static fn (array $row): int => (int)$row['separations_365'], $turnoverTrainingChartRows),
                'turnoverRate' => array_map(static fn (array $row): float => (float)$row['turnover_rate'], $turnoverTrainingChartRows),
                'trainingCompletionRate' => array_map(static fn (array $row): float => (float)$row['training_completion_rate'], $turnoverTrainingChartRows),
            ],
        ],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return;
}

$employmentResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,employment_status,hire_date,is_current&is_current=eq.true',
    $headers
);

$peopleResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/people?select=id,first_name,surname,middle_name,sex_at_birth,date_of_birth',
    $headers
);

$employmentAllResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/employment_records?select=id,person_id,office_id,hire_date,separation_date,is_current&or=' . $reportAnalyticsEmploymentHistoryFilter,
    $headers
);

$officesResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/offices?select=id,office_name',
    $headers
);

$attendanceResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/attendance_logs?select=attendance_date,attendance_status&attendance_date=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate),
    $headers
);
if (reportAnalyticsResponseHasMissingAttendanceDateColumn($attendanceResponse)) {
    $attendanceResponse = reportAnalyticsFetchAll(
        $supabaseUrl . '/rest/v1/attendance_logs?select=created_at,attendance_status&created_at=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate . 'T00:00:00Z'),
        $headers
    );
    if (isSuccessful($attendanceResponse)) {
        $attendanceResponse['data'] = reportAnalyticsNormalizeAttendanceRows((array)($attendanceResponse['data'] ?? []), 'created_at');
    }
}

$payrollResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/payroll_items?select=gross_pay,net_pay,created_at,payroll_run:payroll_runs(payroll_period:payroll_periods(period_end))&created_at=gte.' . rawurlencode($reportAnalyticsRollingWindowStartDate . 'T00:00:00Z'),
    $headers
);

$generatedReportsResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/generated_reports?select=status,report_type,file_format,created_at,generated_at,storage_path',
    $headers
);

$performanceResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/performance_evaluations?select=status',
    $headers
);

$applicationsResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/applications?select=application_status',
    $headers
);

$auditLogsResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/activity_logs?select=id,actor_user_id,module_name,action_name,created_at,actor:user_accounts(email)&created_at=gte.' . $reportAnalyticsActivityWindowStart . '&order=created_at.desc',
    $headers
);

$trainingEnrollmentsResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/training_enrollments?select=person_id,enrollment_status',
    $headers
);

$roleAssignmentsResponse = reportAnalyticsFetchAll(
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,is_primary,role:roles(role_key)&expires_at=is.null',
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
$generatedReports = isSuccessful($generatedReportsResponse) ? $generatedReportsResponse['data'] : [];
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
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Division query failed (HTTP ' . (int)($officesResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($attendanceResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Attendance query failed (HTTP ' . (int)($attendanceResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($payrollResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Payroll query failed (HTTP ' . (int)($payrollResponse['status'] ?? 0) . ').');
}
if (!isSuccessful($generatedReportsResponse)) {
    $dataLoadError = trim(($dataLoadError ? $dataLoadError . ' ' : '') . 'Generated reports query failed (HTTP ' . (int)($generatedReportsResponse['status'] ?? 0) . ').');
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
    $officeNameById[$officeId] = (string)($office['office_name'] ?? 'Unassigned Division');
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
    $topDepartmentName = (string)($officeNameById[$topOfficeId] ?? 'Unassigned Division');
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

$exportedReportsReadyCount = 0;
foreach ($generatedReports as $generatedReportRaw) {
    $status = strtolower(trim((string)($generatedReportRaw['status'] ?? '')));
    if ($status === 'ready') {
        $exportedReportsReadyCount++;
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
    'exported_reports_total' => count($generatedReports),
    'exported_reports_ready' => $exportedReportsReadyCount,
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

    $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
}

$demographicsByDivision = [];
foreach ($uniqueEmploymentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $officeId = (string)($record['office_id'] ?? '');
    $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');

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
    $divisionName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
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
    $divisionName = (string)($personDepartmentById[$personId] ?? 'Unassigned Division');
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
        'division' => (string)($divisionRow['division'] ?? 'Unassigned Division'),
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
    $departmentName = (string)($officeNameById[$officeId] ?? 'Unassigned Division');

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

$divisionHeadcountChartRows = [];
foreach ($departmentCounts as $officeId => $count) {
    $divisionHeadcountChartRows[] = [
        'label' => (string)($officeNameById[(string)$officeId] ?? 'Unassigned Division'),
        'value' => (int)$count,
    ];
}
$divisionHeadcountChartRows = array_slice($divisionHeadcountChartRows, 0, 8);

$demographicsChartRows = $demographicsByDivisionRows;
usort($demographicsChartRows, static function (array $left, array $right): int {
    return (int)$right['total'] <=> (int)$left['total'];
});
$demographicsChartRows = array_slice($demographicsChartRows, 0, 8);

$turnoverTrainingChartRows = $turnoverTrainingRows;
usort($turnoverTrainingChartRows, static function (array $left, array $right): int {
    return (int)$right['headcount'] <=> (int)$left['headcount'];
});
$turnoverTrainingChartRows = array_slice($turnoverTrainingChartRows, 0, 8);

$activityChartRows = array_slice($activityBreakdownRows, 0, 8);

$reportAnalyticsChartPayload = [
    'employeeStatus' => [
        'labels' => ['Active', 'On Leave', 'Inactive'],
        'values' => [(int)$activeCount, (int)$onLeaveCount, (int)$inactiveCount],
    ],
    'divisionHeadcount' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['label'], $divisionHeadcountChartRows),
        'values' => array_map(static fn (array $row): int => (int)$row['value'], $divisionHeadcountChartRows),
    ],
    'demographicsByDivision' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['division'], $demographicsChartRows),
        'male' => array_map(static fn (array $row): int => (int)$row['male'], $demographicsChartRows),
        'female' => array_map(static fn (array $row): int => (int)$row['female'], $demographicsChartRows),
        'unspecified' => array_map(static fn (array $row): int => (int)$row['unspecified'], $demographicsChartRows),
    ],
    'turnoverTraining' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['division'], $turnoverTrainingChartRows),
        'hires' => array_map(static fn (array $row): int => (int)$row['hires_365'], $turnoverTrainingChartRows),
        'separations' => array_map(static fn (array $row): int => (int)$row['separations_365'], $turnoverTrainingChartRows),
        'turnoverRate' => array_map(static fn (array $row): float => (float)$row['turnover_rate'], $turnoverTrainingChartRows),
        'trainingCompletionRate' => array_map(static fn (array $row): float => (float)$row['training_completion_rate'], $turnoverTrainingChartRows),
    ],
    'activityByModule' => [
        'labels' => array_map(static fn (array $row): string => (string)$row['module_name'], $activityChartRows),
        'admin' => array_map(static fn (array $row): int => (int)$row['admin'], $activityChartRows),
        'staff' => array_map(static fn (array $row): int => (int)$row['staff'], $activityChartRows),
    ],
];

$reportAnalyticsChartPayloadJson = json_encode(
    $reportAnalyticsChartPayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
