<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

if (!function_exists('staffReportResolveDateRange')) {
    function staffReportResolveDateRange(string $coverage, ?string $customStartDate, ?string $customEndDate, string $supabaseUrl, array $headers): array
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

if (!function_exists('staffReportDepartmentMatches')) {
    function staffReportDepartmentMatches(string $selectedDepartment, ?string $recordDepartment): bool
    {
        $selected = strtolower(trim($selectedDepartment));
        if ($selected === '' || $selected === 'all') {
            return true;
        }

        return strtolower(trim((string)$recordDepartment)) === $selected;
    }
}

if (!function_exists('staffReportScopedEmploymentContext')) {
    function staffReportScopedEmploymentContext(string $supabaseUrl, array $headers, bool $isAdminScope, ?string $staffOfficeId): array
    {
        $officeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
            ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
            : '';

        $employmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/employment_records?select=person_id,office_id,is_current'
            . '&is_current=eq.true'
            . $officeFilter
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

            $officeNameById[$officeId] = cleanText($office['office_name'] ?? null) ?? 'Unassigned Office';
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
            $personDepartmentById[$personId] = (string)($officeNameById[$officeId] ?? 'Unassigned Office');
        }

        return [
            'scoped_person_ids' => array_keys($scopedPersonIds),
            'person_department_by_id' => $personDepartmentById,
            'office_name_by_id' => $officeNameById,
        ];
    }
}

if (!function_exists('staffReportBuildDataset')) {
    function staffReportBuildDataset(
        string $reportType,
        string $coverage,
        string $department,
        array $dateRange,
        string $supabaseUrl,
        array $headers,
        bool $isAdminScope,
        ?string $staffOfficeId
    ): array {
        $startDate = (string)($dateRange['start_date'] ?? gmdate('Y-m-d'));
        $endDate = (string)($dateRange['end_date'] ?? gmdate('Y-m-d'));
        $startDateTime = $startDate . 'T00:00:00Z';
        $endDateTime = $endDate . 'T23:59:59Z';

        $context = staffReportScopedEmploymentContext($supabaseUrl, $headers, $isAdminScope, $staffOfficeId);
        $scopedPersonIds = (array)($context['scoped_person_ids'] ?? []);
        $personDepartmentById = (array)($context['person_department_by_id'] ?? []);
        $officeNameById = (array)($context['office_name_by_id'] ?? []);

        if (empty($scopedPersonIds) && !$isAdminScope) {
            return [
                ['Notice'],
                [['No scoped records available for your office.']],
            ];
        }

        $personInFilter = sanitizeUuidListForInFilter($scopedPersonIds);
        $personFilter = $personInFilter !== ''
            ? '&person_id=in.' . rawurlencode('(' . $personInFilter . ')')
            : '';

        if ($reportType === 'attendance') {
            $response = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/attendance_logs?select=person_id,attendance_date,attendance_status,late_minutes,hours_worked,source,person:people(first_name,surname)'
                . '&attendance_date=gte.' . rawurlencode($startDate)
                . '&attendance_date=lte.' . rawurlencode($endDate)
                . $personFilter
                . '&order=attendance_date.desc&limit=5000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch attendance data.');
            }

            $columns = ['Employee', 'Attendance Date', 'Status', 'Late Minutes', 'Hours Worked', 'Source'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['person_id'] ?? null) ?? '';
                if (!staffReportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $employeeName = trim(
                    (string)(cleanText($item['person']['first_name'] ?? null) ?? '')
                    . ' '
                    . (string)(cleanText($item['person']['surname'] ?? null) ?? '')
                );

                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)(cleanText($item['attendance_date'] ?? null) ?? '-'),
                    (string)(cleanText($item['attendance_status'] ?? null) ?? '-'),
                    (string)($item['late_minutes'] ?? '0'),
                    (string)($item['hours_worked'] ?? '0'),
                    (string)(cleanText($item['source'] ?? null) ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'payroll') {
            $response = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/payroll_items?select=person_id,gross_pay,net_pay,created_at,person:people(first_name,surname),payroll_run:payroll_runs(office_id,payroll_period:payroll_periods(period_start,period_end,period_code))'
                . $personFilter
                . '&order=created_at.desc&limit=5000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch payroll data.');
            }

            $columns = ['Employee', 'Gross Pay', 'Net Pay', 'Payroll Period'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['person_id'] ?? null) ?? '';
                if (!staffReportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $runOfficeId = cleanText($item['payroll_run']['office_id'] ?? null) ?? '';
                if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strcasecmp($runOfficeId, (string)$staffOfficeId) !== 0) {
                    continue;
                }

                $period = is_array($item['payroll_run']['payroll_period'] ?? null)
                    ? (array)$item['payroll_run']['payroll_period']
                    : [];

                $periodEnd = cleanText($period['period_end'] ?? null) ?? '';
                $windowDate = $periodEnd !== '' ? $periodEnd : substr((string)(cleanText($item['created_at'] ?? null) ?? ''), 0, 10);
                if ($windowDate === '' || $windowDate < $startDate || $windowDate > $endDate) {
                    continue;
                }

                $employeeName = trim(
                    (string)(cleanText($item['person']['first_name'] ?? null) ?? '')
                    . ' '
                    . (string)(cleanText($item['person']['surname'] ?? null) ?? '')
                );

                $periodStart = cleanText($period['period_start'] ?? null) ?? '';
                $periodLabel = $periodStart !== '' && $periodEnd !== ''
                    ? ($periodStart . ' to ' . $periodEnd)
                    : (string)(cleanText($period['period_code'] ?? null) ?? '-');

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
            $response = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/performance_evaluations?select=employee_person_id,final_rating,status,updated_at,employee:people(first_name,surname),cycle:performance_cycles(cycle_name)'
                . '&updated_at=gte.' . rawurlencode($startDateTime)
                . '&updated_at=lte.' . rawurlencode($endDateTime)
                . '&order=updated_at.desc&limit=5000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch performance data.');
            }

            $columns = ['Employee', 'Cycle', 'Final Rating', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['employee_person_id'] ?? null) ?? '';
                if ($personId !== '' && !in_array($personId, $scopedPersonIds, true)) {
                    continue;
                }

                if (!staffReportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $employeeName = trim(
                    (string)(cleanText($item['employee']['first_name'] ?? null) ?? '')
                    . ' '
                    . (string)(cleanText($item['employee']['surname'] ?? null) ?? '')
                );

                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)(cleanText($item['cycle']['cycle_name'] ?? null) ?? '-'),
                    (string)($item['final_rating'] ?? '-'),
                    (string)(cleanText($item['status'] ?? null) ?? '-'),
                    (string)(cleanText($item['updated_at'] ?? null) ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'documents') {
            $response = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/documents?select=title,owner_person_id,document_status,updated_at,category:document_categories(category_name),owner:people(first_name,surname)'
                . '&updated_at=gte.' . rawurlencode($startDateTime)
                . '&updated_at=lte.' . rawurlencode($endDateTime)
                . '&order=updated_at.desc&limit=5000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch documents data.');
            }

            $columns = ['Document', 'Owner', 'Category', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)($response['data'] ?? []) as $item) {
                $personId = cleanText($item['owner_person_id'] ?? null) ?? '';
                if ($personId !== '' && !in_array($personId, $scopedPersonIds, true)) {
                    continue;
                }

                if (!staffReportDepartmentMatches($department, $personDepartmentById[$personId] ?? null)) {
                    continue;
                }

                $ownerName = trim(
                    (string)(cleanText($item['owner']['first_name'] ?? null) ?? '')
                    . ' '
                    . (string)(cleanText($item['owner']['surname'] ?? null) ?? '')
                );

                $rows[] = [
                    (string)(cleanText($item['title'] ?? null) ?? '-'),
                    $ownerName !== '' ? $ownerName : 'Unknown Owner',
                    (string)(cleanText($item['category']['category_name'] ?? null) ?? '-'),
                    (string)(cleanText($item['document_status'] ?? null) ?? '-'),
                    (string)(cleanText($item['updated_at'] ?? null) ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=application_ref_no,application_status,submitted_at,job:job_postings(title,office_id),applicant:applicant_profiles(full_name,email)'
            . '&submitted_at=gte.' . rawurlencode($startDateTime)
            . '&submitted_at=lte.' . rawurlencode($endDateTime)
            . '&order=submitted_at.desc&limit=5000',
            $headers
        );

        if (!isSuccessful($response)) {
            throw new RuntimeException('Failed to fetch recruitment data.');
        }

        $columns = ['Reference No', 'Applicant', 'Email', 'Position', 'Status', 'Submitted At'];
        $rows = [];
        foreach ((array)($response['data'] ?? []) as $item) {
            $jobOfficeId = cleanText($item['job']['office_id'] ?? null) ?? '';
            if (!$isAdminScope && isValidUuid((string)$staffOfficeId) && strcasecmp($jobOfficeId, (string)$staffOfficeId) !== 0) {
                continue;
            }

            $jobDepartment = (string)($officeNameById[$jobOfficeId] ?? '');
            if (!staffReportDepartmentMatches($department, $jobDepartment)) {
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

if (!function_exists('staffReportWriteSpreadsheet')) {
    function staffReportWriteSpreadsheet(string $fileFormat, array $columns, array $rows, string $filePath): void
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

if (!function_exists('staffReportWritePdf')) {
    function staffReportWritePdf(string $title, array $columns, array $rows, string $filePath): void
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

if (!function_exists('staffReportPatchStatus')) {
    function staffReportPatchStatus(string $supabaseUrl, array $headers, string $reportId, string $status, ?string $storagePath = null): void
    {
        if (!isValidUuid($reportId)) {
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
            $supabaseUrl . '/rest/v1/generated_reports?id=eq.' . rawurlencode($reportId),
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );
    }
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';
if ($action !== 'export_report') {
    logStaffSecurityEvent(
        $supabaseUrl,
        $headers,
        $staffUserId,
        'reports',
        'unknown_action_attempt',
        [
            'form_action' => $action,
        ]
    );
    redirectWithState('error', 'Unknown reports action.');
}

$reportType = strtolower((string)(cleanText($_POST['report_type'] ?? null) ?? ''));
$coverage = cleanText($_POST['coverage'] ?? null) ?? 'current_cutoff';
$fileFormat = strtolower((string)(cleanText($_POST['file_format'] ?? null) ?? 'pdf'));
$department = cleanText($_POST['department_filter'] ?? null) ?? 'all';
$customStartDate = cleanText($_POST['custom_start_date'] ?? null);
$customEndDate = cleanText($_POST['custom_end_date'] ?? null);

$allowedTypes = ['attendance', 'payroll', 'performance', 'documents', 'recruitment'];
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
    $dateRange = staffReportResolveDateRange((string)$coverage, $customStartDate, $customEndDate, $supabaseUrl, $headers);
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

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';

$insertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/generated_reports',
    array_merge($headers, ['Prefer: return=representation']),
    [[
        'requested_by' => isValidUuid($staffUserId) ? $staffUserId : null,
        'report_type' => $reportType,
        'filters_json' => [
            'coverage' => $coverage,
            'department_filter' => $department,
            'start_date' => (string)($dateRange['start_date'] ?? ''),
            'end_date' => (string)($dateRange['end_date'] ?? ''),
            'scope' => $isAdminScope ? 'admin' : 'office',
            'office_id' => $isAdminScope ? null : $staffOfficeId,
        ],
        'file_format' => $fileFormat,
        'status' => 'queued',
    ]]
);

if (!isSuccessful($insertResponse)) {
    redirectWithState('error', 'Failed to queue report export request.');
}

$reportId = cleanText($insertResponse['data'][0]['id'] ?? null) ?? '';

try {
    [$columns, $rows] = staffReportBuildDataset(
        $reportType,
        (string)$coverage,
        $department,
        $dateRange,
        $supabaseUrl,
        $headers,
        $isAdminScope,
        $staffOfficeId
    );

    $exportsDir = $projectRoot . '/storage/reports';
    if (!is_dir($exportsDir)) {
        mkdir($exportsDir, 0775, true);
    }

    $baseFileName = 'staff-report-' . $reportType . '-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $extension = $fileFormat === 'xlsx' ? 'xlsx' : ($fileFormat === 'csv' ? 'csv' : 'pdf');
    $fileName = $baseFileName . '.' . $extension;
    $absolutePath = $exportsDir . '/' . $fileName;
    $storagePath = 'storage/reports/' . $fileName;

    $title = strtoupper($reportType) . ' REPORT';
    if ($fileFormat === 'pdf') {
        staffReportWritePdf($title, $columns, $rows, $absolutePath);
    } else {
        staffReportWriteSpreadsheet($fileFormat, $columns, $rows, $absolutePath);
    }

    staffReportPatchStatus($supabaseUrl, $headers, $reportId, 'ready', $storagePath);

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
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
                'start_date' => (string)($dateRange['start_date'] ?? ''),
                'end_date' => (string)($dateRange['end_date'] ?? ''),
                'storage_path' => $storagePath,
                'row_count' => count($rows),
                'scope' => $isAdminScope ? 'admin' : 'office',
                'office_id' => $isAdminScope ? null : $staffOfficeId,
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
    staffReportPatchStatus($supabaseUrl, $headers, $reportId, 'failed', null);
    redirectWithState('error', 'Failed to generate report file: ' . $exception->getMessage());
}
