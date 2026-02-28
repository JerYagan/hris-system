<?php

require_once __DIR__ . '/../../../admin/includes/notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if (!function_exists('staffPayrollFormatInFilterList')) {
    function staffPayrollFormatInFilterList(array $ids): string
    {
        $valid = [];
        foreach ($ids as $id) {
            $candidate = strtolower(trim((string)$id));
            if (!isValidUuid($candidate)) {
                continue;
            }

            $valid[$candidate] = true;
        }

        return implode(',', array_keys($valid));
    }
}

if (!function_exists('staffPayrollDateString')) {
    function staffPayrollDateString(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }
}

if (!function_exists('staffPayrollIsoDateFromTimestamp')) {
    function staffPayrollIsoDateFromTimestamp(?string $value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return '';
        }

        return gmdate('Y-m-d', $timestamp);
    }
}

if (!function_exists('staffPayrollCompensationAppliesToPeriod')) {
    function staffPayrollCompensationAppliesToPeriod(array $row, string $periodStart): bool
    {
        $effectiveFrom = staffPayrollDateString((string)($row['effective_from'] ?? ''));
        if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
            return false;
        }

        $effectiveTo = staffPayrollDateString((string)($row['effective_to'] ?? ''));
        if ($effectiveTo !== '' && $effectiveTo < $periodStart) {
            return false;
        }

        return true;
    }
}

if (!function_exists('staffPayrollEnsureDirectory')) {
    function staffPayrollEnsureDirectory(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0775, true);
        }
    }
}

if (!function_exists('staffPayrollGeneratePayslipDocument')) {
    function staffPayrollGeneratePayslipDocument(array $payload): array
    {
        $projectRoot = (string)($payload['project_root'] ?? '');
        if ($projectRoot === '') {
            throw new RuntimeException('Project root is not configured for payslip generation.');
        }

        $payslipNo = (string)($payload['payslip_no'] ?? 'PAYSLIP');
        $employeeName = (string)($payload['employee_name'] ?? 'Employee');
        $periodLabel = (string)($payload['period_label'] ?? '-');
        $grossPay = (float)($payload['gross_pay'] ?? 0);
        $deductionsTotal = (float)($payload['deductions_total'] ?? 0);
        $netPay = (float)($payload['net_pay'] ?? 0);
        $basicPay = (float)($payload['basic_pay'] ?? 0);
        $overtimePay = (float)($payload['overtime_pay'] ?? 0);
        $allowancesTotal = (float)($payload['allowances_total'] ?? 0);
        $earningsLines = is_array($payload['earnings_lines'] ?? null) ? (array)$payload['earnings_lines'] : [];
        $deductionLines = is_array($payload['deduction_lines'] ?? null) ? (array)$payload['deduction_lines'] : [];
        $generatedAt = gmdate('Y-m-d H:i:s') . ' UTC';

        $exportsDir = $projectRoot . '/storage/payslips';
        staffPayrollEnsureDirectory($exportsDir);

        $baseFileName = strtolower(preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $payslipNo));
        if ($baseFileName === '' || $baseFileName === '-') {
            $baseFileName = 'payslip-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
        }

        if (empty($earningsLines)) {
            $earningsLines = [
                ['label' => 'Basic Pay', 'amount' => $basicPay],
                ['label' => 'CTO Pay', 'amount' => $overtimePay],
                ['label' => 'Allowances', 'amount' => $allowancesTotal],
            ];
        }

        if (empty($deductionLines)) {
            $deductionLines = [[
                'label' => 'Government Contributions (SSS/Pag-IBIG/PhilHealth) and Other Deductions',
                'amount' => $deductionsTotal,
            ]];
        }

        $renderRows = static function (array $rows): string {
            $htmlRows = '';
            foreach ($rows as $rowRaw) {
                $row = (array)$rowRaw;
                $label = trim((string)($row['label'] ?? $row['description'] ?? 'Entry'));
                if ($label === '') {
                    $label = 'Entry';
                }
                $amount = (float)($row['amount'] ?? 0);
                $htmlRows .= '<tr><th style="text-align:left; background:#f8fafc;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th><td>PHP ' . number_format($amount, 2) . '</td></tr>';
            }
            return $htmlRows;
        };

        $html = '<h2 style="font-family: Arial, sans-serif; margin-bottom: 8px;">Employee Payslip</h2>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Payslip No:</strong> ' . htmlspecialchars($payslipNo, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Employee:</strong> ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Period:</strong> ' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h3 style="font-family: Arial, sans-serif; margin: 14px 0 8px 0;">Earnings Breakdown</h3>'
            . '<table width="100%" cellspacing="0" cellpadding="8" border="1" style="border-collapse: collapse; margin-top: 8px; font-family: Arial, sans-serif; font-size: 12px;">'
            . $renderRows($earningsLines)
            . '<tr><th style="text-align:left; background:#f8fafc;">Gross Pay</th><td><strong>PHP ' . number_format($grossPay, 2) . '</strong></td></tr>'
            . '</table>'
            . '<h3 style="font-family: Arial, sans-serif; margin: 14px 0 8px 0;">Deduction Breakdown</h3>'
            . '<table width="100%" cellspacing="0" cellpadding="8" border="1" style="border-collapse: collapse; margin-top: 8px; font-family: Arial, sans-serif; font-size: 12px;">'
            . $renderRows($deductionLines)
            . '<tr><th style="text-align:left; background:#f8fafc;">Total Deductions</th><td><strong>PHP ' . number_format($deductionsTotal, 2) . '</strong></td></tr>'
            . '<tr><th style="text-align:left; background:#f8fafc;">Net Pay</th><td><strong>PHP ' . number_format($netPay, 2) . '</strong></td></tr>'
            . '</table>'
            . '<p style="font-family: Arial, sans-serif; font-size: 11px; color: #64748b; margin-top: 12px;">System generated on ' . htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') . '.</p>';

        $autoloadPath = $projectRoot . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (class_exists('Dompdf\\Dompdf')) {
            $absolutePath = $exportsDir . '/' . $baseFileName . '.pdf';
            $dompdfClass = 'Dompdf\\Dompdf';
            $dompdf = new $dompdfClass();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($absolutePath, $dompdf->output());

            return [
                'absolute_path' => $absolutePath,
                'storage_path' => '/hris-system/storage/payslips/' . basename($absolutePath),
            ];
        }

        $absolutePath = $exportsDir . '/' . $baseFileName . '.html';
        file_put_contents($absolutePath, '<!doctype html><html><head><meta charset="utf-8"><title>Payslip</title></head><body>' . $html . '</body></html>');

        return [
            'absolute_path' => $absolutePath,
            'storage_path' => '/hris-system/storage/payslips/' . basename($absolutePath),
        ];
    }
}

if (!function_exists('staffSmtpSendEmailWithAttachment')) {
    function staffSmtpSendEmailWithAttachment(
        array $smtpConfig,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        string $attachmentPath,
        string $attachmentName
    ): array {
        adminMailEnsureAutoload();

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => 'PHPMailer dependency is not available. Run composer install.',
            ];
        }

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = (string)($smtpConfig['host'] ?? '');
            $mailer->Port = (int)($smtpConfig['port'] ?? 587);
            $mailer->SMTPAuth = ((string)($smtpConfig['auth'] ?? '1')) !== '0';
            $mailer->Username = (string)($smtpConfig['username'] ?? '');
            $mailer->Password = (string)($smtpConfig['password'] ?? '');

            $encryption = strtolower(trim((string)($smtpConfig['encryption'] ?? 'tls')));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mailer->SMTPSecure = '';
                $mailer->SMTPAutoTLS = false;
            }

            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom($fromEmail, $fromName !== '' ? $fromName : $fromEmail);
            $mailer->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlContent;
            $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlContent)));

            if ($attachmentPath !== '' && is_file($attachmentPath)) {
                $mailer->addAttachment($attachmentPath, $attachmentName !== '' ? $attachmentName : basename($attachmentPath));
            }

            $mailer->send();

            return [
                'status' => 200,
                'data' => ['provider' => 'smtp'],
                'raw' => 'SMTP send success',
            ];
        } catch (Throwable $error) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => $error->getMessage(),
            ];
        }
    }
}

$notifyUser = static function (string $recipientUserId, string $title, string $body) use ($supabaseUrl, $headers): void {
    if (!isValidUuid($recipientUserId)) {
        return;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $recipientUserId,
            'category' => 'payroll',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/employee/payroll.php',
        ]]
    );
};

$writeActivityLog = static function (string $entityName, string $entityId, string $actionName, array $oldData, array $newData) use ($supabaseUrl, $headers, $staffUserId): void {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'payroll_management',
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'action_name' => $actionName,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => clientIp(),
        ]]
    );
};

if ($action === 'export_payroll_csv') {
    $periodId = cleanText($_POST['period_id'] ?? null) ?? '';

    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Please select a valid payroll period for CSV export.');
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end'
        . '&id=eq.' . rawurlencode($periodId)
        . '&limit=1',
        $headers
    );

    $periodRow = isSuccessful($periodResponse) ? ($periodResponse['data'][0] ?? null) : null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Selected payroll period was not found.');
    }

    $periodCode = cleanText($periodRow['period_code'] ?? null) ?? 'PERIOD';

    $runsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_runs?select=id,payroll_period_id,generated_at,created_at'
        . '&payroll_period_id=eq.' . rawurlencode($periodId)
        . '&order=created_at.asc&limit=5000',
        $headers
    );

    if (!isSuccessful($runsResponse)) {
        redirectWithState('error', 'Failed to load payroll runs for export.');
    }

    $runRows = (array)($runsResponse['data'] ?? []);
    $runById = [];
    foreach ($runRows as $runRow) {
        $runId = cleanText($runRow['id'] ?? null) ?? '';
        if (!isValidUuid($runId)) {
            continue;
        }

        $runById[$runId] = [
            'generated_date' => staffPayrollIsoDateFromTimestamp(cleanText($runRow['generated_at'] ?? null)),
        ];
    }

    if (empty($runById)) {
        redirectWithState('error', 'No payroll runs match the selected export filters.');
    }

    $runIdFilter = staffPayrollFormatInFilterList(array_keys($runById));
    if ($runIdFilter === '') {
        redirectWithState('error', 'No valid payroll runs available for CSV export.');
    }

    $itemsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,basic_pay,overtime_pay,allowances_total,deductions_total,gross_pay,net_pay,person:people(id,first_name,middle_name,surname)'
        . '&payroll_run_id=in.' . rawurlencode('(' . $runIdFilter . ')')
        . '&limit=20000',
        $headers
    );

    if (!isSuccessful($itemsResponse)) {
        redirectWithState('error', 'Failed to load payroll items for CSV export.');
    }

    $itemRows = (array)($itemsResponse['data'] ?? []);
    if (empty($itemRows)) {
        redirectWithState('error', 'No payroll item records found for selected export filters.');
    }

    $exportRows = [];
    foreach ($itemRows as $itemRow) {
        $runId = cleanText($itemRow['payroll_run_id'] ?? null) ?? '';
        if (!isValidUuid($runId) || !isset($runById[$runId])) {
            continue;
        }

        $personId = cleanText($itemRow['person_id'] ?? null) ?? '';

        $personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
        $employeeName = trim(implode(' ', array_filter([
            cleanText($personRow['first_name'] ?? null),
            cleanText($personRow['middle_name'] ?? null),
            cleanText($personRow['surname'] ?? null),
        ])));

        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        $exportRows[] = [
            'period_code' => $periodCode,
            'run_id' => strtoupper(substr(str_replace('-', '', $runId), 0, 8)),
            'run_generated_date' => $runById[$runId]['generated_date'] !== '' ? $runById[$runId]['generated_date'] : '-',
            'employee_id' => $personId,
            'employee_name' => $employeeName,
            'basic_pay' => (float)($itemRow['basic_pay'] ?? 0),
            'overtime_pay' => (float)($itemRow['overtime_pay'] ?? 0),
            'allowances_total' => (float)($itemRow['allowances_total'] ?? 0),
            'deductions_total' => (float)($itemRow['deductions_total'] ?? 0),
            'gross_pay' => (float)($itemRow['gross_pay'] ?? 0),
            'net_pay' => (float)($itemRow['net_pay'] ?? 0),
        ];
    }

    if (empty($exportRows)) {
        redirectWithState('error', 'No payroll rows matched the selected CSV export filters.');
    }

    usort($exportRows, static function (array $left, array $right): int {
        $leftKey = strtolower((string)($left['employee_name'] ?? '') . ' ' . (string)($left['run_generated_date'] ?? ''));
        $rightKey = strtolower((string)($right['employee_name'] ?? '') . ' ' . (string)($right['run_generated_date'] ?? ''));
        return strcmp($leftKey, $rightKey);
    });

    $filename = 'payroll-export-' . preg_replace('/[^a-zA-Z0-9\-_]+/', '-', strtolower($periodCode)) . '-' . gmdate('Ymd-His') . '.csv';

    $writeActivityLog(
        'payroll_periods',
        $periodId,
        'export_payroll_csv',
        [],
        [
            'period_code' => $periodCode,
            'row_count' => count($exportRows),
        ]
    );

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        exit;
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'Period Code',
        'Run ID',
        'Run Generated Date',
        'Employee ID',
        'Employee Name',
        'Basic Pay',
        'CTO Pay (Policy: 0.00)',
        'Allowances',
        'Deductions',
        'Gross Pay',
        'Net Pay',
    ]);

    foreach ($exportRows as $row) {
        fputcsv($output, [
            (string)$row['period_code'],
            (string)$row['run_id'],
            (string)$row['run_generated_date'],
            (string)$row['employee_id'],
            (string)$row['employee_name'],
            number_format((float)$row['basic_pay'], 2, '.', ''),
            number_format((float)$row['overtime_pay'], 2, '.', ''),
            number_format((float)$row['allowances_total'], 2, '.', ''),
            number_format((float)$row['deductions_total'], 2, '.', ''),
            number_format((float)$row['gross_pay'], 2, '.', ''),
            number_format((float)$row['net_pay'], 2, '.', ''),
        ]);
    }

    fclose($output);
    exit;
}

if ($action === 'review_payroll_run') {
    redirectWithState('error', 'Payroll run approval/cancellation is only available to Admin.');
}

if ($action === 'compute_monthly_payroll') {
    $periodId = cleanText($_POST['period_id'] ?? null) ?? '';
    $staffRecommendation = cleanText($_POST['staff_recommendation'] ?? null) ?? 'Recommend approval';
    if (trim($staffRecommendation) === '') {
        $staffRecommendation = 'Recommend approval';
    }
    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Invalid payroll period selected.');
    }

    $periodResponse = apiRequest(
        'GET',
		$supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&id=eq.' . rawurlencode($periodId) . '&limit=1',
        $headers
    );

    $periodRow = isSuccessful($periodResponse) ? ($periodResponse['data'][0] ?? null) : null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Payroll period not found.');
    }

    $periodStart = staffPayrollDateString((string)($periodRow['period_start'] ?? ''));
    $periodEnd = staffPayrollDateString((string)($periodRow['period_end'] ?? ''));
    if ($periodStart === '' || $periodEnd === '') {
        redirectWithState('error', 'Payroll period dates are invalid.');
    }

    $oldStatus = strtolower((string)(cleanText($periodRow['status'] ?? null) ?? 'open'));

    $existingRunResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id'
        . '&payroll_period_id=eq.' . rawurlencode($periodId)
        . '&run_status=neq.cancelled&order=created_at.desc&limit=1',
        $headers
    );

    if (!isSuccessful($existingRunResponse)) {
        redirectWithState('error', 'Failed to verify existing payroll run for selected period.');
    }

    $existingRun = $existingRunResponse['data'][0] ?? null;
    $payrollRunId = '';
    $existingRunStatus = null;
    if (is_array($existingRun)) {
        $payrollRunId = cleanText($existingRun['id'] ?? null) ?? '';
        $existingRunStatus = strtolower((string)(cleanText($existingRun['run_status'] ?? null) ?? 'draft'));

        if (!isValidUuid($payrollRunId)) {
            redirectWithState('error', 'Existing payroll run identifier is invalid.');
        }

        if (in_array($existingRunStatus, ['approved', 'released'], true)) {
            redirectWithState('error', 'Cannot recompute payroll for a run that is already approved or released.');
        }
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id,employment_status,person:people!employment_records_person_id_fkey(id,user_id,first_name,middle_name,surname)'
        . '&is_current=eq.true&limit=10000',
        $headers
    );

    if (!isSuccessful($employmentResponse)) {
        redirectWithState('error', 'Failed to load active employment records for payroll compute.');
    }

    $personIds = [];
    $peopleById = [];
    foreach ((array)($employmentResponse['data'] ?? []) as $employmentRow) {
        $employmentStatus = strtolower((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'active'));
        if ($employmentStatus !== 'active') {
            continue;
        }

        $personId = cleanText($employmentRow['person_id'] ?? null) ?? '';
        if (!isValidUuid($personId) || isset($peopleById[$personId])) {
            continue;
        }

        $personRow = is_array($employmentRow['person'] ?? null) ? (array)$employmentRow['person'] : [];
        if (!isset($personRow['id']) || !isValidUuid((string)$personRow['id'])) {
            $personRow['id'] = $personId;
        }

        $personIds[] = $personId;
        $peopleById[$personId] = $personRow;
    }

    if (empty($peopleById)) {
        redirectWithState('error', 'No active employee person records found for payroll compute.');
    }

    $personIdFilter = staffPayrollFormatInFilterList($personIds);
    if ($personIdFilter === '') {
        redirectWithState('error', 'No valid employee person IDs are available for payroll compute.');
    }

    $compensationsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,pay_frequency,created_at'
        . '&person_id=in.' . rawurlencode('(' . $personIdFilter . ')')
        . '&order=effective_from.desc,created_at.desc&limit=10000',
        $headers
    );

    if (!isSuccessful($compensationsResponse)) {
        redirectWithState('error', 'Failed to load employee compensation records for payroll compute.');
    }

    $compensationByPerson = [];
    foreach ((array)($compensationsResponse['data'] ?? []) as $compensationRow) {
        $personId = cleanText($compensationRow['person_id'] ?? null) ?? '';
        if (!isValidUuid($personId)) {
            continue;
        }

        if (!isset($compensationByPerson[$personId])) {
            $compensationByPerson[$personId] = [];
        }

        $compensationByPerson[$personId][] = (array)$compensationRow;
    }

    $itemPayload = [];
    foreach ($peopleById as $personId => $personRow) {
        $rows = (array)($compensationByPerson[$personId] ?? []);
        $effectiveCompensation = null;
        foreach ($rows as $candidateCompensation) {
            if (!staffPayrollCompensationAppliesToPeriod($candidateCompensation, $periodStart)) {
                continue;
            }
            $effectiveCompensation = (array)$candidateCompensation;
            break;
        }

        if (!is_array($effectiveCompensation)) {
            continue;
        }

        $monthlyRate = (float)($effectiveCompensation['monthly_rate'] ?? 0);
        $allowanceMonthly = max(0.0, (float)($effectiveCompensation['allowance_total'] ?? 0));
        $basePayMonthly = isset($effectiveCompensation['base_pay'])
            ? max(0.0, (float)$effectiveCompensation['base_pay'])
            : max(0.0, $monthlyRate - $allowanceMonthly);
        $taxMonthly = max(0.0, (float)($effectiveCompensation['tax_deduction'] ?? 0));
        $governmentMonthly = max(0.0, (float)($effectiveCompensation['government_deductions'] ?? 0));
        $otherMonthly = max(0.0, (float)($effectiveCompensation['other_deductions'] ?? 0));
        $payFrequency = strtolower((string)(cleanText($effectiveCompensation['pay_frequency'] ?? null) ?? 'semi_monthly'));

        if ($monthlyRate <= 0) {
            continue;
        }

        $divisor = 1;
        if ($payFrequency === 'semi_monthly') {
            $divisor = 2;
        } elseif ($payFrequency === 'weekly') {
            $divisor = 4;
        }

        $basicPay = round($basePayMonthly / $divisor, 2);
        $allowancesTotal = round($allowanceMonthly / $divisor, 2);
        $overtimePay = 0.0;
        $deductionsTotal = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);
        $grossPay = round($basicPay + $allowancesTotal + $overtimePay, 2);
        $netPay = round($grossPay - $deductionsTotal, 2);

        $itemPayload[] = [
            'person_id' => $personId,
            'basic_pay' => $basicPay,
            'overtime_pay' => $overtimePay,
            'allowances_total' => $allowancesTotal,
            'deductions_total' => $deductionsTotal,
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
        ];
    }

    if (empty($itemPayload)) {
        redirectWithState('error', 'No payroll items could be computed. Ensure employee compensation setup is available.');
    }

    $nowIso = gmdate('c');
    if (!isValidUuid($payrollRunId)) {
        $createRunPayload = [
            'payroll_period_id' => $periodId,
            'generated_by' => $staffUserId,
            'run_status' => 'computed',
            'generated_at' => $nowIso,
            'created_at' => $nowIso,
            'updated_at' => $nowIso,
        ];

        $createRunResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payroll_runs',
            array_merge($headers, ['Prefer: return=representation']),
            [$createRunPayload]
        );

        if (!isSuccessful($createRunResponse)) {
            redirectWithState('error', 'Failed to create payroll run for selected period.');
        }

        $createdRun = (array)($createRunResponse['data'][0] ?? []);
        $payrollRunId = cleanText($createdRun['id'] ?? null) ?? '';
        if (!isValidUuid($payrollRunId)) {
            redirectWithState('error', 'Created payroll run has an invalid identifier.');
        }
    }

    $upsertItemsPayload = [];
    foreach ($itemPayload as $row) {
        $upsertItemsPayload[] = [
            'payroll_run_id' => $payrollRunId,
            'person_id' => $row['person_id'],
            'basic_pay' => $row['basic_pay'],
            'overtime_pay' => $row['overtime_pay'],
            'allowances_total' => $row['allowances_total'],
            'deductions_total' => $row['deductions_total'],
            'gross_pay' => $row['gross_pay'],
            'net_pay' => $row['net_pay'],
            'updated_at' => $nowIso,
        ];
    }

    $upsertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/payroll_items?on_conflict=payroll_run_id,person_id',
        array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
        $upsertItemsPayload
    );

    if (!isSuccessful($upsertResponse)) {
        redirectWithState('error', 'Failed to save computed payroll items.');
    }

    $patchRunResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . rawurlencode($payrollRunId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'run_status' => 'computed',
            'generated_by' => $staffUserId,
            'generated_at' => $nowIso,
            'updated_at' => $nowIso,
        ]
    );

    if (!isSuccessful($patchRunResponse)) {
        redirectWithState('error', 'Payroll run status update failed after computation.');
    }

    if (canTransitionStatus('payroll_periods', $oldStatus, 'processing')) {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payroll_periods?id=eq.' . rawurlencode($periodId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'status' => 'processing',
                'updated_at' => $nowIso,
            ]
        );
    }

    $writeActivityLog(
        'payroll_periods',
        $periodId,
        'compute_monthly_payroll',
        ['status' => $oldStatus],
        [
            'status' => 'processing',
            'payroll_run_id' => $payrollRunId,
            'computed_employee_count' => count($upsertItemsPayload),
            'staff_recommendation' => $staffRecommendation,
        ]
    );

    $writeActivityLog(
        'payroll_runs',
        $payrollRunId,
        'submit_batch_for_admin_approval',
        ['run_status' => $existingRunStatus ?? 'draft'],
        [
            'run_status' => 'computed',
            'staff_recommendation' => $staffRecommendation,
            'submitted_at' => $nowIso,
        ]
    );

    $adminAssignmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,expires_at,role:roles!inner(role_key)'
        . '&role.role_key=eq.admin&limit=1000',
        $headers
    );

    if (isSuccessful($adminAssignmentResponse)) {
        $nowTimestamp = time();
        $adminNotifications = [];
        foreach ((array)($adminAssignmentResponse['data'] ?? []) as $assignmentRow) {
            $adminUserId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            if (!isValidUuid($adminUserId)) {
                continue;
            }

            $expiresAt = cleanText($assignmentRow['expires_at'] ?? null);
            if ($expiresAt !== null) {
                $expiryTimestamp = strtotime($expiresAt);
                if ($expiryTimestamp !== false && $expiryTimestamp <= $nowTimestamp) {
                    continue;
                }
            }

            $adminNotifications[] = [
                'recipient_user_id' => $adminUserId,
                'category' => 'payroll',
                'title' => 'Payroll Batch Ready for Approval',
                'body' => 'Staff submitted a computed payroll batch for period ' . (string)($periodRow['period_code'] ?? 'selected period') . '. Recommendation: ' . $staffRecommendation . '. Please review and apply final decision.',
                'link_url' => '/hris-system/pages/admin/payroll-management.php',
            ];
        }

        if (!empty($adminNotifications)) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                $adminNotifications
            );
        }
    }

    redirectWithState('success', 'Payroll computed successfully for ' . count($upsertItemsPayload) . ' employee(s).');
}

if ($action === 'create_salary_adjustment') {
    $payrollItemId = cleanText($_POST['payroll_item_id'] ?? null) ?? '';
    $adjustmentType = strtolower((string)(cleanText($_POST['adjustment_type'] ?? null) ?? 'deduction'));
    $adjustmentCodeInput = cleanText($_POST['adjustment_code'] ?? null) ?? '';
    $recommendationStatus = strtolower((string)(cleanText($_POST['recommendation_status'] ?? null) ?? 'draft'));
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $amountRaw = (string)(cleanText($_POST['amount'] ?? null) ?? '');
    $amount = is_numeric($amountRaw) ? (float)$amountRaw : 0.0;

    if (!isValidUuid($payrollItemId)) {
        redirectWithState('error', 'Please select a valid payroll item for the salary adjustment.');
    }

    if (!in_array($adjustmentType, ['earning', 'deduction'], true)) {
        redirectWithState('error', 'Invalid salary adjustment type selected.');
    }

    if ($description === '') {
        redirectWithState('error', 'Salary adjustment description is required.');
    }

    if ($amount <= 0) {
        redirectWithState('error', 'Salary adjustment amount must be greater than zero.');
    }

    if (!in_array($recommendationStatus, ['draft', 'approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid recommendation selection for salary adjustment.');
    }

    $adjustmentCode = strtoupper(trim($adjustmentCodeInput));
    $allowedAdjustmentCodes = ['ABSENCE', 'LATE', 'UNDERTIME', 'ALLOWANCE', 'BONUS', 'CORRECTION', 'OTHER'];
    if (!in_array($adjustmentCode, $allowedAdjustmentCodes, true)) {
        redirectWithState('error', 'Please select a valid adjustment code.');
    }

    $itemResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,run:payroll_runs(id,run_status,payroll_period_id,period:payroll_periods(period_code))'
        . '&id=eq.' . rawurlencode($payrollItemId)
        . '&limit=1',
        $headers
    );

    $itemRow = isSuccessful($itemResponse) ? ($itemResponse['data'][0] ?? null) : null;
    if (!is_array($itemRow)) {
        redirectWithState('error', 'Selected payroll item was not found.');
    }

    $runRow = is_array($itemRow['run'] ?? null) ? (array)$itemRow['run'] : [];
    $runStatus = strtolower((string)(cleanText($runRow['run_status'] ?? null) ?? 'draft'));
    if (in_array($runStatus, ['released', 'cancelled'], true)) {
        redirectWithState('error', 'Cannot create salary adjustment for released or cancelled payroll runs.');
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/payroll_adjustments',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'payroll_item_id' => $payrollItemId,
            'adjustment_type' => $adjustmentType,
            'adjustment_code' => $adjustmentCode,
            'description' => $description,
            'amount' => $amount,
            'created_at' => gmdate('c'),
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        $rawError = trim((string)($insertResponse['raw'] ?? ''));
        $errorMessage = 'Failed to create salary adjustment entry.';
        if ($rawError !== '') {
            $errorMessage .= ' ' . $rawError;
        }

        redirectWithState('error', $errorMessage);
    }

    $insertedRow = (array)($insertResponse['data'][0] ?? []);
    $adjustmentId = cleanText($insertedRow['id'] ?? null) ?? '';

    if (isValidUuid($adjustmentId)) {
        $writeActivityLog(
            'payroll_adjustments',
            $adjustmentId,
            'create_payroll_adjustment',
            [],
            [
                'payroll_item_id' => $payrollItemId,
                'adjustment_type' => $adjustmentType,
                'adjustment_code' => $adjustmentCode,
                'description' => $description,
                'amount' => $amount,
                'review_status' => 'pending',
            ]
        );

        if (in_array($recommendationStatus, ['approved', 'rejected'], true)) {
            $writeActivityLog(
                'payroll_adjustments',
                $adjustmentId,
                'recommend_payroll_adjustment',
                ['recommendation_status' => 'pending'],
                ['recommendation_status' => $recommendationStatus, 'notes' => '', 'submitted_to_admin' => true]
            );

            $adminAssignmentResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/user_role_assignments?select=user_id,expires_at,role:roles!inner(role_key)'
                . '&role.role_key=eq.admin&limit=1000',
                $headers
            );

            if (isSuccessful($adminAssignmentResponse)) {
                $nowTimestamp = time();
                $adminNotifications = [];
                foreach ((array)($adminAssignmentResponse['data'] ?? []) as $assignmentRow) {
                    $adminUserId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
                    if (!isValidUuid($adminUserId)) {
                        continue;
                    }

                    $expiresAt = cleanText($assignmentRow['expires_at'] ?? null);
                    if ($expiresAt !== null) {
                        $expiryTimestamp = strtotime($expiresAt);
                        if ($expiryTimestamp !== false && $expiryTimestamp <= $nowTimestamp) {
                            continue;
                        }
                    }

                    $adminNotifications[] = [
                        'recipient_user_id' => $adminUserId,
                        'category' => 'payroll',
                        'title' => 'Salary Adjustment Recommendation Submitted',
                        'body' => 'Staff recommended ' . $recommendationStatus . ' for ' . $adjustmentCode . '. Please apply final decision in payroll adjustment review.',
                        'link_url' => '/hris-system/pages/admin/payroll-management.php',
                    ];
                }

                if (!empty($adminNotifications)) {
                    apiRequest(
                        'POST',
                        $supabaseUrl . '/rest/v1/notifications',
                        array_merge($headers, ['Prefer: return=minimal']),
                        $adminNotifications
                    );
                }
            }
        }
    }

    if ($recommendationStatus === 'draft') {
        redirectWithState('success', 'Salary adjustment created as draft. You can submit recommendation to Admin from the table.');
    }

    redirectWithState('success', 'Salary adjustment created and recommendation submitted to Admin for final approval.');
}

if ($action === 'recommend_salary_adjustment') {
    $adjustmentId = cleanText($_POST['adjustment_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['review_notes'] ?? null);

    if (!isValidUuid($adjustmentId)) {
        redirectWithState('error', 'Invalid salary adjustment selected.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid salary adjustment recommendation.');
    }

    $adjustmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_code,item:payroll_items(id,person_id,person:people(id,user_id))'
        . '&id=eq.' . rawurlencode($adjustmentId)
        . '&limit=1',
        $headers
    );

    $adjustmentRow = isSuccessful($adjustmentResponse) ? ($adjustmentResponse['data'][0] ?? null) : null;
    if (!is_array($adjustmentRow)) {
        redirectWithState('error', 'Salary adjustment not found.');
    }

    $adminReviewStatus = 'pending';
    $lastAdminDecisionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=new_data,created_at'
        . '&entity_name=eq.payroll_adjustments'
        . '&action_name=eq.review_payroll_adjustment'
        . '&entity_id=eq.' . rawurlencode($adjustmentId)
        . '&order=created_at.desc&limit=1',
        $headers
    );

    if (isSuccessful($lastAdminDecisionResponse) && !empty((array)($lastAdminDecisionResponse['data'] ?? []))) {
        $lastLogRow = (array)$lastAdminDecisionResponse['data'][0];
        $newData = is_array($lastLogRow['new_data'] ?? null) ? (array)$lastLogRow['new_data'] : [];
        $adminReviewStatus = strtolower((string)(cleanText($newData['review_status'] ?? null) ?? cleanText($newData['status_to'] ?? null) ?? cleanText($newData['status'] ?? null) ?? 'pending'));
        if (!in_array($adminReviewStatus, ['pending', 'approved', 'rejected'], true)) {
            $adminReviewStatus = 'pending';
        }
    }

    if ($adminReviewStatus !== 'pending') {
        redirectWithState('error', 'This salary adjustment has already been finalized by Admin.');
    }

    $previousRecommendation = 'pending';
    $lastRecommendationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=new_data,created_at'
        . '&entity_name=eq.payroll_adjustments'
        . '&action_name=eq.recommend_payroll_adjustment'
        . '&entity_id=eq.' . rawurlencode($adjustmentId)
        . '&order=created_at.desc&limit=1',
        $headers
    );

    if (isSuccessful($lastRecommendationResponse) && !empty((array)($lastRecommendationResponse['data'] ?? []))) {
        $lastLogRow = (array)$lastRecommendationResponse['data'][0];
        $newData = is_array($lastLogRow['new_data'] ?? null) ? (array)$lastLogRow['new_data'] : [];
        $previousRecommendation = strtolower((string)(cleanText($newData['recommendation_status'] ?? null) ?? 'pending'));
        if (!in_array($previousRecommendation, ['pending', 'approved', 'rejected'], true)) {
            $previousRecommendation = 'pending';
        }
    }

    $writeActivityLog(
        'payroll_adjustments',
        $adjustmentId,
        'recommend_payroll_adjustment',
        ['recommendation_status' => $previousRecommendation],
        ['recommendation_status' => $decision, 'notes' => $notes, 'submitted_to_admin' => true]
    );

    $adminAssignmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id,expires_at,role:roles!inner(role_key)'
        . '&role.role_key=eq.admin&limit=1000',
        $headers
    );

    if (isSuccessful($adminAssignmentResponse)) {
        $nowTimestamp = time();
        $adminNotifications = [];
        $adjustmentCode = cleanText($adjustmentRow['adjustment_code'] ?? null) ?? 'Salary adjustment';
        foreach ((array)($adminAssignmentResponse['data'] ?? []) as $assignmentRow) {
            $adminUserId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            if (!isValidUuid($adminUserId)) {
                continue;
            }

            $expiresAt = cleanText($assignmentRow['expires_at'] ?? null);
            if ($expiresAt !== null) {
                $expiryTimestamp = strtotime($expiresAt);
                if ($expiryTimestamp !== false && $expiryTimestamp <= $nowTimestamp) {
                    continue;
                }
            }

            $adminNotifications[] = [
                'recipient_user_id' => $adminUserId,
                'category' => 'payroll',
                'title' => 'Salary Adjustment Recommendation Submitted',
                'body' => 'Staff recommended ' . $decision . ' for ' . $adjustmentCode . '. Please apply final decision in payroll adjustment review.',
                'link_url' => '/hris-system/pages/admin/payroll-management.php',
            ];
        }

        if (!empty($adminNotifications)) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                $adminNotifications
            );
        }
    }

    redirectWithState('success', 'Salary adjustment recommendation submitted to Admin for final approval.');
}

if ($action === 'generate_payslip_run') {
    $runId = cleanText($_POST['run_id'] ?? null) ?? '';
    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Invalid payroll run selected.');
    }

    $runResponse = apiRequest(
        'GET',
		$supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,generated_by,payroll_period_id,payroll_period:payroll_periods(period_code,period_start,period_end)&id=eq.' . rawurlencode($runId) . '&limit=1',
        $headers
    );

    $runRow = isSuccessful($runResponse) ? ($runResponse['data'][0] ?? null) : null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll run not found.');
    }

    $oldStatus = strtolower((string)(cleanText($runRow['run_status'] ?? null) ?? 'draft'));
    if ($oldStatus !== 'approved') {
        redirectWithState('error', 'Payslips can only be generated for payroll runs in Approved status.');
    }

    $itemsResponse = apiRequest(
        'GET',
        $supabaseUrl
		. '/rest/v1/payroll_items?select=id,payroll_run_id,gross_pay,deductions_total,net_pay,person:people(first_name,middle_name,surname,user_id)'
        . '&payroll_run_id=eq.' . rawurlencode($runId)
        . '&limit=5000',
        $headers
    );

    if (!isSuccessful($itemsResponse)) {
        redirectWithState('error', 'Unable to load payroll items for the selected run.');
    }

    $itemRows = (array)($itemsResponse['data'] ?? []);
    if (empty($itemRows)) {
        redirectWithState('error', 'No payroll items found for this run.');
    }

    $itemIds = [];
    foreach ($itemRows as $itemRow) {
        $itemId = cleanText($itemRow['id'] ?? null) ?? '';
        if (!isValidUuid($itemId)) {
            continue;
        }
        $itemIds[] = $itemId;
    }

    if (empty($itemIds)) {
        redirectWithState('error', 'No valid payroll items found for this run.');
    }

    $approvedAdjustmentByItemId = [];
    $adjustmentsForItemsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,amount'
        . '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
        . '&limit=10000',
        $headers
    );

    if (isSuccessful($adjustmentsForItemsResponse)) {
        $adjustmentRowsForItems = (array)($adjustmentsForItemsResponse['data'] ?? []);
        $adjustmentIdsForItems = [];
        foreach ($adjustmentRowsForItems as $adjustmentRow) {
            $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
            if (!isValidUuid($adjustmentId)) {
                continue;
            }
            $adjustmentIdsForItems[$adjustmentId] = true;
        }

        $reviewStatusByAdjustmentId = [];
        if (!empty($adjustmentIdsForItems)) {
            $adjustmentReviewResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                . '&entity_name=eq.payroll_adjustments'
                . '&action_name=eq.review_payroll_adjustment'
                . '&entity_id=in.' . rawurlencode('(' . implode(',', array_keys($adjustmentIdsForItems)) . ')')
                . '&order=created_at.desc&limit=10000',
                $headers
            );

            if (isSuccessful($adjustmentReviewResponse)) {
                foreach ((array)($adjustmentReviewResponse['data'] ?? []) as $reviewRow) {
                    $entityId = cleanText($reviewRow['entity_id'] ?? null) ?? '';
                    if (!isValidUuid($entityId) || isset($reviewStatusByAdjustmentId[$entityId])) {
                        continue;
                    }

                    $newData = is_array($reviewRow['new_data'] ?? null) ? (array)$reviewRow['new_data'] : [];
                    $reviewStatus = strtolower((string)(cleanText($newData['review_status'] ?? null) ?? cleanText($newData['status_to'] ?? null) ?? cleanText($newData['status'] ?? null) ?? 'pending'));
                    if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
                        $reviewStatus = 'pending';
                    }

                    $reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
                }
            }
        }

        foreach ($adjustmentRowsForItems as $adjustmentRow) {
            $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
            if (!isValidUuid($adjustmentId)) {
                continue;
            }

            if (strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending')) !== 'approved') {
                continue;
            }

            $itemId = cleanText($adjustmentRow['payroll_item_id'] ?? null) ?? '';
            if (!isValidUuid($itemId)) {
                continue;
            }

            if (!isset($approvedAdjustmentByItemId[$itemId])) {
                $approvedAdjustmentByItemId[$itemId] = [
                    'adjustment_earnings' => 0.0,
                    'adjustment_deductions' => 0.0,
                ];
            }

            $amount = (float)($adjustmentRow['amount'] ?? 0);
            $type = strtolower((string)(cleanText($adjustmentRow['adjustment_type'] ?? null) ?? 'deduction'));
            if ($type === 'earning') {
                $approvedAdjustmentByItemId[$itemId]['adjustment_earnings'] += $amount;
            } else {
                $approvedAdjustmentByItemId[$itemId]['adjustment_deductions'] += $amount;
            }
        }
    }

    $computeAdjustedPayrollFigures = static function (array $itemRow) use ($approvedAdjustmentByItemId): array {
        $itemId = cleanText($itemRow['id'] ?? null) ?? '';
        $adjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
            ? (array)$approvedAdjustmentByItemId[$itemId]
            : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];

        $adjustmentEarnings = (float)($adjustment['adjustment_earnings'] ?? 0);
        $adjustmentDeductions = (float)($adjustment['adjustment_deductions'] ?? 0);
        $grossPay = (float)($itemRow['gross_pay'] ?? 0) + $adjustmentEarnings;
        $deductionsTotal = (float)($itemRow['deductions_total'] ?? 0) + $adjustmentDeductions;
        $netPay = (float)($itemRow['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

        return [
            'adjustment_earnings' => $adjustmentEarnings,
            'adjustment_deductions' => $adjustmentDeductions,
            'gross_pay' => $grossPay,
            'deductions_total' => $deductionsTotal,
            'net_pay' => $netPay,
        ];
    };

    $smtpConfig = [
        'host' => cleanText($_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? null)) ?? '',
        'port' => (int)(cleanText($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? null)) ?? '587'),
        'username' => cleanText($_ENV['SMTP_USERNAME'] ?? ($_SERVER['SMTP_USERNAME'] ?? null)) ?? '',
        'password' => (string)($_ENV['SMTP_PASSWORD'] ?? ($_SERVER['SMTP_PASSWORD'] ?? '')),
        'encryption' => strtolower((string)(cleanText($_ENV['SMTP_ENCRYPTION'] ?? ($_SERVER['SMTP_ENCRYPTION'] ?? null)) ?? 'tls')),
        'auth' => (string)(cleanText($_ENV['SMTP_AUTH'] ?? ($_SERVER['SMTP_AUTH'] ?? null)) ?? '1'),
    ];

    $mailFrom = cleanText($_ENV['MAIL_FROM'] ?? ($_SERVER['MAIL_FROM'] ?? null)) ?? '';
    $mailFromName = cleanText($_ENV['MAIL_FROM_NAME'] ?? ($_SERVER['MAIL_FROM_NAME'] ?? null)) ?? 'DA HRIS';
    $resolvedMailConfig = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
    $smtpConfig = (array)($resolvedMailConfig['smtp'] ?? $smtpConfig);
    $mailFrom = (string)($resolvedMailConfig['from'] ?? $mailFrom);
    $mailFromName = (string)($resolvedMailConfig['from_name'] ?? $mailFromName);

    if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
        redirectWithState('error', 'SMTP settings are not ready. Configure SMTP host, port, credentials, and sender email first.');
    }

    $existingPayslipRows = [];
    $existingResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,released_at'
        . '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
        . '&limit=5000',
        $headers
    );

    if (isSuccessful($existingResponse)) {
        $existingPayslipRows = (array)($existingResponse['data'] ?? []);
    }

    $existingByItemId = [];
    foreach ($existingPayslipRows as $existingRow) {
        $existingItemId = strtolower(trim((string)(cleanText($existingRow['payroll_item_id'] ?? null) ?? '')));
        if (!isValidUuid($existingItemId)) {
            continue;
        }

        $existingByItemId[$existingItemId] = (array)$existingRow;
    }

    $nowIso = gmdate('c');
    $runShort = strtoupper(substr(str_replace('-', '', $runId), 0, 8));
    $insertPayload = [];

    foreach ($itemIds as $itemId) {
        if (!isset($existingByItemId[$itemId])) {
            $itemShort = strtoupper(substr(str_replace('-', '', $itemId), 0, 8));
            $insertPayload[] = [
                'payroll_item_id' => $itemId,
                'payslip_no' => 'PS-' . $runShort . '-' . $itemShort,
                'created_at' => $nowIso,
                'released_at' => $nowIso,
            ];
            continue;
        }

        $existingReleasedAt = cleanText($existingByItemId[$itemId]['released_at'] ?? null);
        if ($existingReleasedAt === null) {
            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/payslips?payroll_item_id=eq.' . rawurlencode($itemId),
                array_merge($headers, ['Prefer: return=minimal']),
                ['released_at' => $nowIso]
            );
        }
    }

    $currentPayslipResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,released_at,pdf_storage_path'
        . '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
        . '&limit=5000',
        $headers
    );

    if (!isSuccessful($currentPayslipResponse)) {
        redirectWithState('error', 'Payslips were created but could not be reloaded for document generation.');
    }

    $existingByItemId = [];
    foreach ((array)($currentPayslipResponse['data'] ?? []) as $existingRow) {
        $existingItemId = strtolower(trim((string)(cleanText($existingRow['payroll_item_id'] ?? null) ?? '')));
        if (!isValidUuid($existingItemId)) {
            continue;
        }

        $existingByItemId[$existingItemId] = (array)$existingRow;
    }

    if (!empty($insertPayload)) {
        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payslips',
            array_merge($headers, ['Prefer: return=minimal']),
            $insertPayload
        );

        if (!isSuccessful($insertResponse)) {
            redirectWithState('error', 'Failed to generate payslips for this run.');
        }
    }

    $periodCode = (string)($runRow['payroll_period']['period_code'] ?? 'PR');
    $periodStartRaw = cleanText($runRow['payroll_period']['period_start'] ?? null) ?? '';
    $periodEndRaw = cleanText($runRow['payroll_period']['period_end'] ?? null) ?? '';
    $periodLabel = ($periodStartRaw !== '' && $periodEndRaw !== '')
        ? (date('M d, Y', strtotime($periodStartRaw)) . ' - ' . date('M d, Y', strtotime($periodEndRaw)))
        : strtoupper($periodCode);

    $emailAddressByUserId = [];
    $userIds = [];
    foreach ($itemRows as $itemRow) {
        $userId = strtolower(trim((string)($itemRow['person']['user_id'] ?? '')));
        if (isValidUuid($userId)) {
            $userIds[] = $userId;
        }
    }

    $userFilter = staffPayrollFormatInFilterList($userIds);
    if ($userFilter !== '') {
        $usersResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . $userFilter . ')&limit=5000',
            $headers
        );

        if (isSuccessful($usersResponse)) {
            foreach ((array)($usersResponse['data'] ?? []) as $userRow) {
                $userId = strtolower(trim((string)($userRow['id'] ?? '')));
                $email = strtolower(trim((string)($userRow['email'] ?? '')));
                if ($userId !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailAddressByUserId[$userId] = $email;
                }
            }
        }
    }

    $documentByItemId = [];
    foreach ($itemRows as $itemRow) {
        $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
        if ($itemId === '' || !isset($existingByItemId[$itemId])) {
            continue;
        }

        $payslipRow = (array)$existingByItemId[$itemId];
        $payslipId = (string)($payslipRow['id'] ?? '');
        if (!isValidUuid($payslipId)) {
            continue;
        }

        $firstName = cleanText($itemRow['person']['first_name'] ?? null) ?? '';
        $middleName = cleanText($itemRow['person']['middle_name'] ?? null) ?? '';
        $surname = cleanText($itemRow['person']['surname'] ?? null) ?? '';
        $employeeName = trim(implode(' ', array_filter([$firstName, $middleName, $surname])));
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? ('PS-' . $runShort . '-' . strtoupper(substr(str_replace('-', '', $itemId), 0, 8)));

        try {
            $adjustedFigures = $computeAdjustedPayrollFigures($itemRow);

            $document = staffPayrollGeneratePayslipDocument([
                'project_root' => dirname(__DIR__, 4),
                'payslip_no' => $payslipNo,
                'employee_name' => $employeeName,
                'period_label' => $periodLabel,
                'basic_pay' => (float)($itemRow['basic_pay'] ?? 0),
                'overtime_pay' => (float)($itemRow['overtime_pay'] ?? 0),
                'allowances_total' => (float)($itemRow['allowances_total'] ?? 0),
                'gross_pay' => (float)($adjustedFigures['gross_pay'] ?? 0),
                'deductions_total' => (float)($adjustedFigures['deductions_total'] ?? 0),
                'net_pay' => (float)($adjustedFigures['net_pay'] ?? 0),
                'earnings_lines' => [
                    ['label' => 'Basic Pay', 'amount' => (float)($itemRow['basic_pay'] ?? 0)],
                    ['label' => 'CTO Pay', 'amount' => (float)($itemRow['overtime_pay'] ?? 0)],
                    ['label' => 'Allowances', 'amount' => (float)($itemRow['allowances_total'] ?? 0)],
                    ['label' => 'Approved Adjustment Earnings', 'amount' => (float)($adjustedFigures['adjustment_earnings'] ?? 0)],
                ],
                'deduction_lines' => [
                    [
                        'label' => 'Government Contributions (SSS/Pag-IBIG/PhilHealth) and Other Deductions',
                        'amount' => (float)($itemRow['deductions_total'] ?? 0),
                    ],
                    [
                        'label' => 'Approved Adjustment Deductions',
                        'amount' => (float)($adjustedFigures['adjustment_deductions'] ?? 0),
                    ],
                ],
            ]);

            $storagePath = cleanText($document['storage_path'] ?? null);
            $absolutePath = cleanText($document['absolute_path'] ?? null);

            $patchData = ['released_at' => $nowIso];
            if ($storagePath !== null && $storagePath !== '') {
                $patchData['pdf_storage_path'] = $storagePath;
            }

            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/payslips?id=eq.' . rawurlencode($payslipId),
                array_merge($headers, ['Prefer: return=minimal']),
                $patchData
            );

            $existingByItemId[$itemId]['released_at'] = $nowIso;
            if ($storagePath !== null && $storagePath !== '') {
                $existingByItemId[$itemId]['pdf_storage_path'] = $storagePath;
            }

            $documentByItemId[$itemId] = [
                'absolute_path' => ($absolutePath !== null ? $absolutePath : ''),
                'storage_path' => ($storagePath !== null ? $storagePath : ''),
            ];
        } catch (Throwable $throwable) {
            redirectWithState('error', 'Failed to generate payslip document: ' . $throwable->getMessage());
        }
    }

    if ($oldStatus !== 'released' && canTransitionStatus('payroll_runs', $oldStatus, 'released')) {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . rawurlencode($runId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'run_status' => 'released',
                'updated_at' => $nowIso,
            ]
        );
    }

    $periodId = strtolower(trim((string)($runRow['payroll_period_id'] ?? '')));
    if (isValidUuid($periodId)) {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payroll_periods?id=eq.' . rawurlencode($periodId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'status' => 'closed',
                'updated_at' => $nowIso,
            ]
        );
    }

    $emailsAttempted = 0;
    $emailsSent = 0;
    $emailsFailed = 0;
    $emailErrorSamples = [];

    foreach ($itemRows as $itemRow) {
        $userId = strtolower(trim((string)($itemRow['person']['user_id'] ?? '')));
        if (!isValidUuid($userId)) {
            continue;
        }

        $recipientEmail = $emailAddressByUserId[$userId] ?? '';
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
        $payslipRow = is_array($existingByItemId[$itemId] ?? null) ? (array)$existingByItemId[$itemId] : [];
        $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? ('PS-' . $runShort . '-' . strtoupper(substr(str_replace('-', '', $itemId), 0, 8)));

        $firstName = cleanText($itemRow['person']['first_name'] ?? null) ?? '';
        $middleName = cleanText($itemRow['person']['middle_name'] ?? null) ?? '';
        $surname = cleanText($itemRow['person']['surname'] ?? null) ?? '';
        $employeeName = trim(implode(' ', array_filter([$firstName, $middleName, $surname])));
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        $subject = 'Payslip Released - ' . strtoupper($periodCode);
            $adjustedFigures = $computeAdjustedPayrollFigures($itemRow);
        $html = '<p>Hi ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Your payslip for payroll period <strong>' . htmlspecialchars(strtoupper($periodCode), ENT_QUOTES, 'UTF-8') . '</strong> is now released.</p>'
            . '<p>Payslip No: <strong>' . htmlspecialchars($payslipNo, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                . 'Net Pay: <strong>PHP ' . number_format((float)($adjustedFigures['net_pay'] ?? 0), 2) . '</strong></p>'
            . '<p>You may view details in your employee payroll page.</p>';

        $attachmentPath = cleanText($documentByItemId[$itemId]['absolute_path'] ?? null) ?? '';
        $attachmentName = ($payslipNo !== '' ? $payslipNo : 'payslip') . '.pdf';

        $emailsAttempted++;
        $emailResponse = staffSmtpSendEmailWithAttachment(
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $recipientEmail,
            $employeeName,
            $subject,
            $html,
            $attachmentPath,
            $attachmentName
        );

        if (isSuccessful($emailResponse)) {
            $emailsSent++;
        } else {
            $emailsFailed++;
            if (count($emailErrorSamples) < 3) {
                $emailErrorSamples[] = trim((string)($emailResponse['raw'] ?? 'SMTP send failed'));
            }
        }

        $notifyUser(
            $userId,
            'Payslip Released',
            'Your payslip for ' . strtoupper($periodCode) . ' is now available.'
        );
    }

    $writeActivityLog(
        'payroll_runs',
        $runId,
        'generate_payslip_run',
        ['run_status' => $oldStatus],
        [
            'run_status' => 'released',
            'generated_payslips' => count($itemIds),
            'email_attempted' => $emailsAttempted,
            'email_sent' => $emailsSent,
            'email_failed' => $emailsFailed,
            'email_error_samples' => $emailErrorSamples,
        ]
    );

    $message = 'Payslips generated successfully for the selected run. Email sent: ' . $emailsSent . ', failed: ' . $emailsFailed . '.';
    if ($emailsFailed > 0 && !empty($emailErrorSamples)) {
        $message .= ' Sample error: ' . $emailErrorSamples[0];
    }

    redirectWithState('success', $message);
}

redirectWithState('error', 'Unknown payroll management action.');
