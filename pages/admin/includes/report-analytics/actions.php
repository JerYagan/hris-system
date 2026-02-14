<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('reportCoverageStartDate')) {
    function reportCoverageStartDate(string $coverage): string
    {
        $normalized = strtolower(trim($coverage));
        $days = match ($normalized) {
            'quarterly' => 90,
            'custom_range' => 30,
            'monthly' => 30,
            default => 30,
        };

        return gmdate('Y-m-d', strtotime('-' . $days . ' days'));
    }
}

if (!function_exists('reportBuildDataset')) {
    function reportBuildDataset(string $reportType, string $coverage, string $supabaseUrl, array $headers): array
    {
        $startDate = reportCoverageStartDate($coverage);

        if ($reportType === 'attendance') {
            $response = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/attendance_logs?select=attendance_date,attendance_status,late_minutes,hours_worked,source,person:people(first_name,surname)&attendance_date=gte.' . $startDate . '&order=attendance_date.desc&limit=2000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch attendance data.');
            }

            $columns = ['Employee', 'Attendance Date', 'Status', 'Late Minutes', 'Hours Worked', 'Source'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['attendance_date'] ?? '-'),
                    (string)($item['attendance_status'] ?? '-'),
                    (string)($item['late_minutes'] ?? '0'),
                    (string)($item['hours_worked'] ?? '0'),
                    (string)($item['source'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'payroll') {
            $response = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/payroll_items?select=person:people(first_name,surname),gross_pay,net_pay,created_at&created_at=gte.' . $startDate . 'T00:00:00Z&order=created_at.desc&limit=2000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch payroll data.');
            }

            $columns = ['Employee', 'Gross Pay', 'Net Pay', 'Created At'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
                $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
                $rows[] = [
                    $employeeName !== '' ? $employeeName : 'Unknown Employee',
                    (string)($item['gross_pay'] ?? '0'),
                    (string)($item['net_pay'] ?? '0'),
                    (string)($item['created_at'] ?? '-'),
                ];
            }

            return [$columns, $rows];
        }

        if ($reportType === 'performance') {
            $response = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/performance_evaluations?select=final_rating,status,updated_at,employee:people(first_name,surname),cycle:performance_cycles(cycle_name)&updated_at=gte.' . $startDate . 'T00:00:00Z&order=updated_at.desc&limit=2000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch performance data.');
            }

            $columns = ['Employee', 'Cycle', 'Final Rating', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
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
            $response = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/documents?select=title,document_status,updated_at,category:document_categories(category_name),owner:people(first_name,surname)&updated_at=gte.' . $startDate . 'T00:00:00Z&order=updated_at.desc&limit=2000',
                $headers
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to fetch documents data.');
            }

            $columns = ['Document', 'Owner', 'Category', 'Status', 'Updated At'];
            $rows = [];
            foreach ((array)$response['data'] as $item) {
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

        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/applications?select=application_ref_no,application_status,submitted_at,job:job_postings(title),applicant:applicant_profiles(full_name,email)&submitted_at=gte.' . $startDate . 'T00:00:00Z&order=submitted_at.desc&limit=2000',
            $headers
        );

        if (!isSuccessful($response)) {
            throw new RuntimeException('Failed to fetch recruitment data.');
        }

        $columns = ['Reference No', 'Applicant', 'Email', 'Position', 'Status', 'Submitted At'];
        $rows = [];
        foreach ((array)$response['data'] as $item) {
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

    $allowedTypes = ['attendance', 'payroll', 'performance', 'documents', 'recruitment'];
    if (!in_array($reportType, $allowedTypes, true)) {
        redirectWithState('error', 'Invalid report type selected.');
    }

    $allowedFormats = ['pdf', 'xlsx', 'csv'];
    if (!in_array($fileFormat, $allowedFormats, true)) {
        redirectWithState('error', 'Invalid export format selected.');
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
        [$columns, $rows] = reportBuildDataset($reportType, $coverage, $supabaseUrl, $headers);

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
