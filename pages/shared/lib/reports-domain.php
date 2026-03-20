<?php

if (!function_exists('reportServiceResolveDateRange')) {
    function reportServiceResolveDateRange(string $coverage, ?string $customStartDate, ?string $customEndDate, string $supabaseUrl, array $headers): array
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

if (!function_exists('reportServiceDepartmentMatches')) {
    function reportServiceDepartmentMatches(string $selectedDepartment, ?string $recordDepartment): bool
    {
        $selected = strtolower(trim($selectedDepartment));
        if ($selected === '' || $selected === 'all') {
            return true;
        }

        return strtolower(trim((string)$recordDepartment)) === $selected;
    }
}

if (!function_exists('reportServiceScopedEmploymentContext')) {
    function reportServiceScopedEmploymentContext(string $supabaseUrl, array $headers): array
    {
        $employmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/employment_records?select=person_id,office_id,is_current'
            . '&is_current=eq.true'
            . '&limit=5000',
            $headers
        );

        if (!isSuccessful($employmentResponse)) {
            throw new RuntimeException('Failed to resolve scoped employment context.');
        }

        $officesResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/offices?select=id,office_name&limit=1000',
            $headers
        );

        if (!isSuccessful($officesResponse)) {
            throw new RuntimeException('Failed to resolve office references.');
        }

        $officeNameById = [];
        foreach ((array)($officesResponse['data'] ?? []) as $office) {
            $officeId = cleanText($office['id'] ?? null) ?? '';
            if (!isValidUuid($officeId)) {
                continue;
            }

            $officeNameById[$officeId] = cleanText($office['office_name'] ?? null) ?? 'Unassigned Division';
        }

        $scopedPersonIds = [];
        $personDepartmentById = [];
        foreach ((array)($employmentResponse['data'] ?? []) as $employment) {
            $personId = cleanText($employment['person_id'] ?? null) ?? '';
            if (!isValidUuid($personId) || isset($personDepartmentById[$personId])) {
                continue;
            }

            $officeId = cleanText($employment['office_id'] ?? null) ?? '';
            $scopedPersonIds[$personId] = true;
            $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? 'Unassigned Division');
        }

        return [
            'scoped_person_ids' => array_keys($scopedPersonIds),
            'person_department_by_id' => $personDepartmentById,
            'office_name_by_id' => $officeNameById,
        ];
    }
}

if (!function_exists('reportServicePolicyHasNoLateMode')) {
    function reportServicePolicyHasNoLateMode(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                $normalizedKey = strtolower(trim((string)$nestedKey));
                if (in_array($normalizedKey, ['late_policy_mode', 'late_policy', 'policy_mode', 'mode'], true) && reportServicePolicyHasNoLateMode($nestedValue)) {
                    return true;
                }

                if (reportServicePolicyHasNoLateMode($nestedValue)) {
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
                return reportServicePolicyHasNoLateMode($decoded);
            }
        }

        return false;
    }
}

if (!function_exists('reportServiceBuildDataset')) {
    function reportServiceBuildDataset(
        string $reportType,
        string $coverage,
        string $department,
        array $dateRange,
        string $supabaseUrl,
        array $headers
    ): array {
        $startDate = (string)($dateRange['start_date'] ?? gmdate('Y-m-d'));
        $endDate = (string)($dateRange['end_date'] ?? gmdate('Y-m-d'));
        $startDateTime = $startDate . 'T00:00:00Z';
        $endDateTime = $endDate . 'T23:59:59Z';

        $context = reportServiceScopedEmploymentContext($supabaseUrl, $headers);
        $scopedPersonIds = (array)($context['scoped_person_ids'] ?? []);
        $personDepartmentById = (array)($context['person_department_by_id'] ?? []);
        $officeNameById = (array)($context['office_name_by_id'] ?? []);

        $noLatePolicyApproved = false;
        $latePolicyModeResponse = apiRequest('GET', $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.late_policy_mode') . '&limit=1', $headers);
        $latePolicySettingResponse = apiRequest('GET', $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.late_policy') . '&limit=1', $headers);
        $holidayPolicyResponse = apiRequest('GET', $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode('timekeeping.holiday_payroll_policy') . '&limit=1', $headers);
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
            if (reportServicePolicyHasNoLateMode($candidate)) {
                $noLatePolicyApproved = true;
                break;
            }
        }

        $personInFilter = sanitizeUuidListForInFilter($scopedPersonIds);
        $personFilter = $personInFilter !== '' ? '&person_id=in.' . rawurlencode('(' . $personInFilter . ')') : '';

        if ($reportType === 'attendance') {
            $response = apiRequest('GET', $supabaseUrl . '/rest/v1/attendance_logs?select=person_id,attendance_date,attendance_status,late_minutes,hours_worked,source,person:people(first_name,surname)&attendance_date=gte.' . rawurlencode($startDate) . '&attendance_date=lte.' . rawurlencode($endDate) . $personFilter . '&order=attendance_date.desc&limit=5000', $headers);
            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch attendance data.');
            }

            $columns = $noLatePolicyApproved ? ['Employee', 'Attendance Date', 'Status', 'Hours Worked', 'Source'] : ['Employee', 'Attendance Date', 'Status', 'Late Minutes', 'Hours Worked', 'Source'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['person_id'] ?? null) ?? '';
                if (!reportServiceDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $employeeName = trim((string)(cleanText($item['person']['first_name'] ?? null) ?? '') . ' ' . (string)(cleanText($item['person']['surname'] ?? null) ?? ''));
                $baseStatus = strtolower(trim((string)(cleanText($item['attendance_status'] ?? null) ?? '-')));
                $statusLabel = $noLatePolicyApproved && $baseStatus === 'late' ? 'present' : (string)(cleanText($item['attendance_status'] ?? null) ?? '-');

                $rows[] = $noLatePolicyApproved
                    ? [
                        $employeeName !== '' ? $employeeName : 'Unknown Employee',
                        (string)(cleanText($item['attendance_date'] ?? null) ?? '-'),
                        $statusLabel,
                        (string)($item['hours_worked'] ?? '0'),
                        (string)(cleanText($item['source'] ?? null) ?? '-'),
                    ]
                    : [
                        $employeeName !== '' ? $employeeName : 'Unknown Employee',
                        (string)(cleanText($item['attendance_date'] ?? null) ?? '-'),
                        $statusLabel,
                        (string)($item['late_minutes'] ?? '0'),
                        (string)($item['hours_worked'] ?? '0'),
                        (string)(cleanText($item['source'] ?? null) ?? '-'),
                    ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'payroll') {
            $response = apiRequest('GET', $supabaseUrl . '/rest/v1/payroll_items?select=person_id,gross_pay,net_pay,created_at,person:people(first_name,surname),payroll_run:payroll_runs(office_id,payroll_period:payroll_periods(period_start,period_end,period_code))' . $personFilter . '&order=created_at.desc&limit=5000', $headers);
            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch payroll data.');
            }

            $columns = ['Employee', 'Gross Pay', 'Net Pay', 'Payroll Period'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['person_id'] ?? null) ?? '';
                if (!reportServiceDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $period = is_array($item['payroll_run']['payroll_period'] ?? null) ? (array)$item['payroll_run']['payroll_period'] : [];
                $periodEnd = cleanText($period['period_end'] ?? null) ?? '';
                $windowDate = $periodEnd !== '' ? $periodEnd : substr((string)(cleanText($item['created_at'] ?? null) ?? ''), 0, 10);
                if ($windowDate === '' || $windowDate < $startDate || $windowDate > $endDate) {
                    continue;
                }

                $employeeName = trim((string)(cleanText($item['person']['first_name'] ?? null) ?? '') . ' ' . (string)(cleanText($item['person']['surname'] ?? null) ?? ''));
                $periodStart = cleanText($period['period_start'] ?? null) ?? '';
                $periodLabel = $periodStart !== '' && $periodEnd !== '' ? ($periodStart . ' to ' . $periodEnd) : (string)(cleanText($period['period_code'] ?? null) ?? '-');

                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['gross_pay'] ?? '0'),
                    (string)($item['net_pay'] ?? '0'),
                    $periodLabel,
                ];
            }

            return [$columns, $rows];
        }

        $response = apiRequest('GET', $supabaseUrl . '/rest/v1/applications?select=application_ref_no,application_status,submitted_at,job:job_postings(title,office_id),applicant:applicant_profiles(full_name,email)&submitted_at=gte.' . rawurlencode($startDateTime) . '&submitted_at=lte.' . rawurlencode($endDateTime) . '&order=submitted_at.desc&limit=5000', $headers);
        if (!isSuccessful($response)) {
            throw new RuntimeException('Failed to fetch recruitment data.');
        }

        $columns = ['Reference No', 'Applicant', 'Email', 'Position', 'Status', 'Submitted At'];
        $rows = [];
        foreach ((array)($response['data'] ?? []) as $item) {
            $jobOfficeId = cleanText($item['job']['office_id'] ?? null) ?? '';
            $jobDepartment = (string)($officeNameById[$jobOfficeId] ?? '');
            if (!reportServiceDepartmentMatches($department, $jobDepartment)) {
                continue;
            }

            $rows[] = [
                (string)(cleanText($item['application_ref_no'] ?? null) ?? '-'),
                (string)(cleanText($item['applicant']['full_name'] ?? null) ?? '-'),
                (string)(cleanText($item['applicant']['email'] ?? null) ?? '-'),
                (string)(cleanText($item['job']['title'] ?? null) ?? '-'),
                (string)(cleanText($item['application_status'] ?? null) ?? '-'),
                (string)(cleanText($item['submitted_at'] ?? null) ?? '-'),
            ];
        }

        return [$columns, $rows];
    }
}

if (!function_exists('reportServiceBuildDatasetWithFallback')) {
    function reportServiceBuildDatasetWithFallback(string $reportType, string $coverage, string $department, array $dateRange, string $supabaseUrl, array $headers): array
    {
        [$columns, $rows] = reportServiceBuildDataset($reportType, $coverage, $department, $dateRange, $supabaseUrl, $headers);
        if (!empty($rows) || strtolower(trim($coverage)) === 'custom_range') {
            return [$columns, $rows, $dateRange];
        }

        $today = gmdate('Y-m-d');
        $fallbackRanges = [
            ['start_date' => gmdate('Y-m-d', strtotime('-30 days')), 'end_date' => $today],
            ['start_date' => gmdate('Y-m-d', strtotime('-90 days')), 'end_date' => $today],
            ['start_date' => gmdate('Y-m-d', strtotime('-365 days')), 'end_date' => $today],
        ];

        foreach ($fallbackRanges as $fallbackRange) {
            [$fallbackColumns, $fallbackRows] = reportServiceBuildDataset($reportType, 'custom_range', $department, $fallbackRange, $supabaseUrl, $headers);
            if (!empty($fallbackRows)) {
                return [$fallbackColumns, $fallbackRows, $fallbackRange];
            }
        }

        $unboundedRange = ['start_date' => '1970-01-01', 'end_date' => $today];
        [$unboundedColumns, $unboundedRows] = reportServiceBuildDataset($reportType, 'custom_range', $department, $unboundedRange, $supabaseUrl, $headers);
        if (!empty($unboundedRows)) {
            return [$unboundedColumns, $unboundedRows, $unboundedRange];
        }

        return [$columns, $rows, $dateRange];
    }
}

if (!function_exists('reportServiceWriteSpreadsheet')) {
    function reportServiceWriteSpreadsheet(string $fileFormat, array $columns, array $rows, string $filePath): void
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

        $writer = $fileFormat === 'csv'
            ? new PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet)
            : new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}

if (!function_exists('reportServiceWritePdf')) {
    function reportServiceWritePdf(string $title, array $columns, array $rows, string $filePath, array $exportMeta = []): void
    {
        $coverage = (string)($exportMeta['coverage'] ?? '-');
        $department = (string)($exportMeta['department_filter'] ?? 'all');
        $startDate = (string)($exportMeta['start_date'] ?? '-');
        $endDate = (string)($exportMeta['end_date'] ?? '-');
        $rowCount = (int)($exportMeta['row_count'] ?? count($rows));

        $html = '<h2 style="font-family: Arial, sans-serif; margin-bottom: 8px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<p style="font-family: Arial, sans-serif; font-size: 11px; color: #334155; margin: 0 0 10px 0;">'
            . 'Coverage: ' . htmlspecialchars($coverage, ENT_QUOTES, 'UTF-8')
            . ' | Division: ' . htmlspecialchars($department, ENT_QUOTES, 'UTF-8')
            . ' | Date Range: ' . htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8')
            . ' | Rows: ' . $rowCount
            . '</p>';
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

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        file_put_contents($filePath, $dompdf->output());
    }
}

if (!function_exists('reportServicePatchStatus')) {
    function reportServicePatchStatus(string $supabaseUrl, array $headers, string $reportId, string $status, ?string $storagePath = null): void
    {
        if (!isValidUuid($reportId)) {
            return;
        }

        $payload = ['status' => $status, 'generated_at' => gmdate('c')];
        if ($storagePath !== null) {
            $payload['storage_path'] = $storagePath;
        }

        apiRequest('PATCH', $supabaseUrl . '/rest/v1/generated_reports?id=eq.' . rawurlencode($reportId), array_merge($headers, ['Prefer: return=minimal']), $payload);
    }
}

if (!function_exists('reportServiceEnsureExportDependencies')) {
    function reportServiceEnsureExportDependencies(string $projectRoot, string $fileFormat): void
    {
        $autoloadPath = $projectRoot . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new RuntimeException('Export libraries are missing. Run: composer require dompdf/dompdf phpoffice/phpspreadsheet');
        }

        require_once $autoloadPath;

        if ($fileFormat === 'pdf' && !class_exists('Dompdf\\Dompdf')) {
            throw new RuntimeException('Dompdf is not available. Install it with composer require dompdf/dompdf');
        }

        if (in_array($fileFormat, ['xlsx', 'csv'], true) && !class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            throw new RuntimeException('PhpSpreadsheet is not available. Install it with composer require phpoffice/phpspreadsheet');
        }
    }
}

if (!function_exists('reportServiceQueueExportRequest')) {
    function reportServiceQueueExportRequest(string $supabaseUrl, array $headers, string $requestedBy, string $reportType, string $coverage, string $department, array $dateRange, string $fileFormat): string
    {
        $insertResponse = apiRequest('POST', $supabaseUrl . '/rest/v1/generated_reports', array_merge($headers, ['Prefer: return=representation']), [[
            'requested_by' => isValidUuid($requestedBy) ? $requestedBy : null,
            'report_type' => $reportType,
            'filters_json' => [
                'coverage' => $coverage,
                'department_filter' => $department,
                'start_date' => (string)($dateRange['start_date'] ?? ''),
                'end_date' => (string)($dateRange['end_date'] ?? ''),
                'scope' => 'organization',
                'office_id' => null,
            ],
            'file_format' => $fileFormat,
            'status' => 'queued',
        ]]);

        if (!isSuccessful($insertResponse)) {
            throw new RuntimeException('Failed to queue report export request.');
        }

        return cleanText($insertResponse['data'][0]['id'] ?? null) ?? '';
    }
}

if (!function_exists('reportServiceHandleExport')) {
    function reportServiceHandleExport(string $reportType, string $coverage, string $fileFormat, string $department, ?string $customStartDate, ?string $customEndDate, string $supabaseUrl, array $headers, string $staffUserId, string $projectRoot): array
    {
        $dateRange = reportServiceResolveDateRange($coverage, $customStartDate, $customEndDate, $supabaseUrl, $headers);
        reportServiceEnsureExportDependencies($projectRoot, $fileFormat);
        $reportId = reportServiceQueueExportRequest($supabaseUrl, $headers, $staffUserId, $reportType, $coverage, $department, $dateRange, $fileFormat);

        try {
            [$columns, $rows, $resolvedDateRange] = reportServiceBuildDatasetWithFallback($reportType, $coverage, $department, $dateRange, $supabaseUrl, $headers);

            $exportsDir = $projectRoot . '/storage/reports';
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0775, true);
            }

            $baseFileName = 'staff-report-' . $reportType . '-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(8)), 0, 8);
            $extension = $fileFormat === 'xlsx' ? 'xlsx' : ($fileFormat === 'csv' ? 'csv' : 'pdf');
            $fileName = $baseFileName . '.' . $extension;
            $absolutePath = $exportsDir . '/' . $fileName;
            $storagePath = 'storage/reports/' . $fileName;

            $titleMap = [
                'attendance' => 'ATTENDANCE REPORT',
                'payroll' => 'PAYROLL REPORT',
                'performance' => 'PERFORMANCE REPORT',
                'documents' => 'DOCUMENTS REPORT',
                'recruitment' => 'RECRUITMENT REPORT',
                'training_completion' => 'TRAINING COMPLETION REPORT',
                'hired_applicants' => 'HIRED APPLICANTS REPORT',
            ];
            $title = (string)($titleMap[$reportType] ?? (strtoupper($reportType) . ' REPORT'));

            if ($fileFormat === 'pdf') {
                reportServiceWritePdf($title, $columns, $rows, $absolutePath, [
                    'coverage' => $coverage,
                    'department_filter' => $department,
                    'start_date' => (string)($resolvedDateRange['start_date'] ?? ''),
                    'end_date' => (string)($resolvedDateRange['end_date'] ?? ''),
                    'row_count' => count($rows),
                ]);
            } else {
                reportServiceWriteSpreadsheet($fileFormat, $columns, $rows, $absolutePath);
            }

            reportServicePatchStatus($supabaseUrl, $headers, $reportId, 'ready', $storagePath);

            apiRequest('POST', $supabaseUrl . '/rest/v1/activity_logs', array_merge($headers, ['Prefer: return=minimal']), [[
                'actor_user_id' => isValidUuid($staffUserId) ? $staffUserId : null,
                'module_name' => 'reports',
                'entity_name' => 'generated_reports',
                'entity_id' => isValidUuid($reportId) ? $reportId : null,
                'action_name' => 'export_report',
                'old_data' => null,
                'new_data' => [
                    'report_type' => $reportType,
                    'coverage' => $coverage,
                    'file_format' => $fileFormat,
                    'department_filter' => $department,
                    'start_date' => (string)($resolvedDateRange['start_date'] ?? ''),
                    'end_date' => (string)($resolvedDateRange['end_date'] ?? ''),
                    'storage_path' => $storagePath,
                    'row_count' => count($rows),
                    'scope' => 'organization',
                    'office_id' => null,
                ],
                'ip_address' => clientIp(),
            ]]);

            if (!is_file($absolutePath)) {
                throw new RuntimeException('Generated file was not created.');
            }

            $mimeType = match ($extension) {
                'pdf' => 'application/pdf',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'text/csv',
            };

            return [
                'report_id' => $reportId,
                'absolute_path' => $absolutePath,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
            ];
        } catch (Throwable $exception) {
            reportServicePatchStatus($supabaseUrl, $headers, $reportId, 'failed', null);
            throw $exception;
        }
    }
}