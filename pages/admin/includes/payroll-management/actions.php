<?php

require_once __DIR__ . '/../notifications/email.php';

if (!function_exists('smtpConfigIsReady')) {
    function smtpConfigIsReady(array $smtpConfig, string $fromEmail): bool
    {
        return false;
    }
}

if (!function_exists('smtpSendTransactionalEmail')) {
    function smtpSendTransactionalEmail(array $smtpConfig, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        return [
            'status' => 500,
            'data' => [],
            'raw' => 'SMTP helper not loaded.',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('isValidUuid')) {
    function isValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('formatInFilterList')) {
    function formatInFilterList(array $ids): string
    {
        $valid = [];
        foreach ($ids as $id) {
            $candidate = strtolower(trim((string)$id));
            if (!isValidUuid($candidate)) {
                continue;
            }
            $valid[] = $candidate;
        }

        return implode(',', array_values(array_unique($valid)));
    }
}

if (!function_exists('payrollDateString')) {
    function payrollDateString(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }
}

if (!function_exists('payrollNormalizeCompensationRow')) {
    function payrollNormalizeCompensationRow(array $row): array
    {
        $monthlyRate = (float)($row['monthly_rate'] ?? 0);
        $allowanceTotal = max(0.0, (float)($row['allowance_total'] ?? 0));
        $basePay = isset($row['base_pay'])
            ? max(0.0, (float)$row['base_pay'])
            : max(0.0, $monthlyRate - $allowanceTotal);

        $row['base_pay'] = $basePay;
        $row['allowance_total'] = $allowanceTotal;
        $row['tax_deduction'] = max(0.0, (float)($row['tax_deduction'] ?? 0));
        $row['government_deductions'] = max(0.0, (float)($row['government_deductions'] ?? 0));
        $row['other_deductions'] = max(0.0, (float)($row['other_deductions'] ?? 0));

        return $row;
    }
}

if (!function_exists('payrollFetchCompensations')) {
    function payrollFetchCompensations(string $supabaseUrl, array $headers, string $querySuffix): array
    {
        $selectWithComponents = 'id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,created_at';
        $primaryResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/employee_compensations?select=' . $selectWithComponents . $querySuffix,
            $headers
        );

        $response = $primaryResponse;
        if (!isSuccessful($response)) {
            $selectLegacy = 'id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,created_at';
            $response = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/employee_compensations?select=' . $selectLegacy . $querySuffix,
                $headers
            );
        }

        if (!isSuccessful($response)) {
            return [
                'response' => $response,
                'rows' => [],
            ];
        }

        $rows = array_map('payrollNormalizeCompensationRow', (array)($response['data'] ?? []));

        return [
            'response' => $response,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('payrollCompensationColumnsMissing')) {
    function payrollCompensationColumnsMissing(array $response): bool
    {
        $raw = strtolower(trim((string)($response['raw'] ?? '')));
        if ($raw === '') {
            return false;
        }

        $hasColumnSignal = str_contains($raw, "column") || str_contains($raw, "schema cache") || str_contains($raw, "could not find");
        if (!$hasColumnSignal) {
            return false;
        }

        return str_contains($raw, 'base_pay')
            || str_contains($raw, 'allowance_total')
            || str_contains($raw, 'tax_deduction')
            || str_contains($raw, 'government_deductions')
            || str_contains($raw, 'other_deductions');
    }
}

if (!function_exists('payrollCompensationAppliesToPeriod')) {
    function payrollCompensationAppliesToPeriod(array $row, string $periodStart, string $periodEnd): bool
    {
        $effectiveFrom = payrollDateString((string)($row['effective_from'] ?? ''));
        if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
            return false;
        }

        $effectiveTo = payrollDateString((string)($row['effective_to'] ?? ''));
        if ($effectiveTo !== '' && $effectiveTo < $periodStart) {
            return false;
        }

        return true;
    }
}

if (!function_exists('payrollRefreshCompensationTimelineForPerson')) {
    function payrollRefreshCompensationTimelineForPerson(string $personId, string $supabaseUrl, array $headers): void
    {
        if (!isValidUuid($personId)) {
            return;
        }

        $remainingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/employee_compensations?select=id,effective_from,effective_to&person_id=eq.' . $personId . '&order=effective_from.asc,created_at.asc&limit=5000',
            $headers
        );

        if (!isSuccessful($remainingResponse)) {
            return;
        }

        $remainingRows = array_values(array_filter((array)($remainingResponse['data'] ?? []), static function (array $row): bool {
            return isValidUuid((string)($row['id'] ?? '')) && payrollDateString((string)($row['effective_from'] ?? '')) !== '';
        }));

        $remainingCount = count($remainingRows);
        for ($index = 0; $index < $remainingCount; $index++) {
            $row = $remainingRows[$index];
            $rowId = (string)($row['id'] ?? '');
            $nextRow = $remainingRows[$index + 1] ?? null;

            $targetEffectiveTo = null;
            if (is_array($nextRow)) {
                $nextFrom = payrollDateString((string)($nextRow['effective_from'] ?? ''));
                if ($nextFrom !== '') {
                    $targetEffectiveTo = gmdate('Y-m-d', strtotime($nextFrom . ' -1 day'));
                }
            }

            apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/employee_compensations?id=eq.' . $rowId,
                array_merge($headers, ['Prefer: return=minimal']),
                ['effective_to' => $targetEffectiveTo]
            );
        }
    }
}

if (!function_exists('payrollEnsureDirectory')) {
    function payrollEnsureDirectory(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0775, true);
        }
    }
}

if (!function_exists('payrollGeneratePayslipDocument')) {
    function payrollGeneratePayslipDocument(array $payload): array
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
        $generatedAt = gmdate('Y-m-d H:i:s') . ' UTC';

        $exportsDir = $projectRoot . '/storage/payslips';
        payrollEnsureDirectory($exportsDir);

        $baseFileName = strtolower(preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $payslipNo));
        if ($baseFileName === '' || $baseFileName === '-') {
            $baseFileName = 'payslip-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
        }

        $html = '<h2 style="font-family: Arial, sans-serif; margin-bottom: 8px;">Employee Payslip</h2>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Payslip No:</strong> ' . htmlspecialchars($payslipNo, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Employee:</strong> ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-family: Arial, sans-serif; font-size: 12px; margin: 4px 0;"><strong>Period:</strong> ' . htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<table width="100%" cellspacing="0" cellpadding="8" border="1" style="border-collapse: collapse; margin-top: 12px; font-family: Arial, sans-serif; font-size: 12px;">'
            . '<tr><th style="text-align:left; background:#f8fafc;">Gross Pay</th><td>PHP ' . number_format($grossPay, 2) . '</td></tr>'
            . '<tr><th style="text-align:left; background:#f8fafc;">Deductions</th><td>PHP ' . number_format($deductionsTotal, 2) . '</td></tr>'
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

if (!function_exists('payrollBuildUpcomingPeriods')) {
    function payrollBuildUpcomingPeriods(string $effectiveFrom, int $monthsAhead = 3): array
    {
        $effectiveDate = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveFrom, new DateTimeZone('UTC'));
        if (!$effectiveDate) {
            return [];
        }

        $firstMonth = $effectiveDate->modify('first day of this month');
        $rows = [];

        for ($index = 0; $index <= $monthsAhead; $index++) {
            $monthDate = $firstMonth->modify('+' . $index . ' month');
            $yearMonth = $monthDate->format('Y-m');
            $firstDay = $monthDate->format('Y-m-01');
            $fifteenth = $monthDate->format('Y-m-15');
            $sixteenth = $monthDate->format('Y-m-16');
            $monthEnd = $monthDate->format('Y-m-t');

            $rows[] = [
                'period_code' => $yearMonth . '-A',
                'period_start' => $firstDay,
                'period_end' => $fifteenth,
                'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $fifteenth, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
                'status' => 'open',
            ];

            if ($sixteenth <= $monthEnd) {
                $rows[] = [
                    'period_code' => $yearMonth . '-B',
                    'period_start' => $sixteenth,
                    'period_end' => $monthEnd,
                    'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $monthEnd, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
                    'status' => 'open',
                ];
            }
        }

        return array_values(array_filter($rows, static function (array $row) use ($effectiveFrom): bool {
            return (string)($row['period_start'] ?? '') >= $effectiveFrom;
        }));
    }
}

if (!function_exists('payrollEnsureUpcomingPeriods')) {
    function payrollEnsureUpcomingPeriods(string $effectiveFrom, string $supabaseUrl, array $headers): int
    {
        $candidatePeriods = payrollBuildUpcomingPeriods($effectiveFrom, 0);
        if (empty($candidatePeriods)) {
            return 0;
        }

        $periodsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/payroll_periods?select=period_code&limit=1000',
            $headers
        );

        if (!isSuccessful($periodsResponse)) {
            return 0;
        }

        $existingCodes = [];
        foreach ((array)($periodsResponse['data'] ?? []) as $row) {
            $code = strtoupper(trim((string)($row['period_code'] ?? '')));
            if ($code !== '') {
                $existingCodes[$code] = true;
            }
        }

        $insertPayload = [];
        foreach ($candidatePeriods as $period) {
            $code = strtoupper(trim((string)($period['period_code'] ?? '')));
            if ($code === '' || isset($existingCodes[$code])) {
                continue;
            }
            $insertPayload[] = $period;
            $existingCodes[$code] = true;
        }

        if (empty($insertPayload)) {
            return 0;
        }

        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payroll_periods',
            array_merge($headers, ['Prefer: return=representation']),
            $insertPayload
        );

        if (!isSuccessful($insertResponse)) {
            return 0;
        }

        return count((array)($insertResponse['data'] ?? []));
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'generate_payroll_batch') {
    if (!isValidUuid($adminUserId)) {
        redirectWithState('error', 'Current admin account is invalid for payroll batch generation.');
    }

    $periodId = cleanText($_POST['payroll_period_id'] ?? null) ?? '';
    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Please select a valid payroll period.');
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&id=eq.' . $periodId . '&limit=1',
        $headers
    );

    $periodRow = $periodResponse['data'][0] ?? null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Selected payroll period was not found.');
    }

    $periodStatus = strtolower((string)($periodRow['status'] ?? 'open'));
    if (!in_array($periodStatus, ['open', 'processing', 'posted'], true)) {
        redirectWithState('error', 'Payroll period is not eligible for batch generation.');
    }

    $periodStart = payrollDateString((string)($periodRow['period_start'] ?? ''));
    $periodEnd = payrollDateString((string)($periodRow['period_end'] ?? ''));
    if ($periodStart === '' || $periodEnd === '') {
        redirectWithState('error', 'Payroll period dates are invalid for batch generation.');
    }

    $existingRunResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period:payroll_periods(period_code)&payroll_period_id=eq.' . $periodId . '&run_status=neq.cancelled&order=created_at.desc&limit=1',
        $headers
    );

    if (!isSuccessful($existingRunResponse)) {
        redirectWithState('error', 'Failed to check existing payroll batches for selected period.');
    }

    $existingRun = $existingRunResponse['data'][0] ?? null;
    if (is_array($existingRun)) {
        $existingRunId = (string)($existingRun['id'] ?? '');
        $existingStatus = strtolower((string)($existingRun['run_status'] ?? 'draft'));
        redirectWithState('error', 'A payroll batch already exists for this period (' . $existingRunId . ', status: ' . $existingStatus . ').');
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id&is_current=eq.true&employment_status=eq.active&limit=5000',
        $headers
    );

    if (!isSuccessful($employmentResponse)) {
        redirectWithState('error', 'Failed to load active employment records for payroll generation.');
    }

    $compensationFetch = payrollFetchCompensations(
        $supabaseUrl,
        $headers,
        '&order=effective_from.desc,created_at.desc&limit=5000'
    );
    $compensationResponse = $compensationFetch['response'] ?? ['status' => 500, 'data' => [], 'raw' => 'Failed to load compensation rows.'];

    if (!isSuccessful($compensationResponse)) {
        redirectWithState('error', 'Failed to load active compensation records for payroll generation.');
    }

    $employmentRows = (array)($employmentResponse['data'] ?? []);
    $compensationRows = (array)($compensationFetch['rows'] ?? []);

    if (empty($employmentRows)) {
        redirectWithState('error', 'No active employees found for payroll generation.');
    }

    $latestCompensationByPerson = [];
    foreach ($compensationRows as $compensationRow) {
        $personId = strtolower(trim((string)($compensationRow['person_id'] ?? '')));
        if (!isValidUuid($personId) || isset($latestCompensationByPerson[$personId])) {
            continue;
        }

        if (!payrollCompensationAppliesToPeriod($compensationRow, $periodStart, $periodEnd)) {
            continue;
        }

        $latestCompensationByPerson[$personId] = $compensationRow;
    }

    $itemPayload = [];
    $skippedEmployees = 0;
    foreach ($employmentRows as $employmentRow) {
        $personId = strtolower(trim((string)($employmentRow['person_id'] ?? '')));
        if (!isValidUuid($personId)) {
            continue;
        }

        $compensationRow = $latestCompensationByPerson[$personId] ?? null;
        if (!is_array($compensationRow)) {
            $skippedEmployees++;
            continue;
        }

        $monthlyRate = (float)($compensationRow['monthly_rate'] ?? 0);
        $allowanceMonthly = max(0.0, (float)($compensationRow['allowance_total'] ?? 0));
        $basePayMonthly = isset($compensationRow['base_pay'])
            ? max(0.0, (float)$compensationRow['base_pay'])
            : max(0.0, $monthlyRate - $allowanceMonthly);
        $taxMonthly = max(0.0, (float)($compensationRow['tax_deduction'] ?? 0));
        $governmentMonthly = max(0.0, (float)($compensationRow['government_deductions'] ?? 0));
        $otherMonthly = max(0.0, (float)($compensationRow['other_deductions'] ?? 0));
        $payFrequency = strtolower((string)($compensationRow['pay_frequency'] ?? 'semi_monthly'));
        if ($monthlyRate <= 0) {
            $skippedEmployees++;
            continue;
        }

        $divisor = 1;
        if ($payFrequency === 'semi_monthly') {
            $divisor = 2;
        } elseif ($payFrequency === 'weekly') {
            $divisor = 4;
        }

        $basicPay = $basePayMonthly / $divisor;
        $allowancesTotal = $allowanceMonthly / $divisor;
        $deductionsTotal = ($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor;
        $basicPay = round($basicPay, 2);
        $allowancesTotal = round($allowancesTotal, 2);
        $overtimePay = 0.0;
        $deductionsTotal = round($deductionsTotal, 2);
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
        redirectWithState('error', 'No payroll items could be generated. Ensure active employees have salary setup records.');
    }

    $createRunResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/payroll_runs',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'payroll_period_id' => $periodId,
            'office_id' => null,
            'run_status' => 'computed',
            'generated_by' => $adminUserId,
            'generated_at' => gmdate('c'),
        ]]
    );

    if (!isSuccessful($createRunResponse)) {
        redirectWithState('error', 'Failed to create payroll batch.');
    }

    $runId = (string)($createRunResponse['data'][0]['id'] ?? '');
    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Payroll batch was created but run id is invalid.');
    }

    $payloadChunks = array_chunk($itemPayload, 200);
    $generatedCount = 0;
    foreach ($payloadChunks as $chunk) {
        $rows = [];
        foreach ($chunk as $entry) {
            $rows[] = [
                'payroll_run_id' => $runId,
                'person_id' => $entry['person_id'],
                'basic_pay' => $entry['basic_pay'],
                'overtime_pay' => $entry['overtime_pay'],
                'allowances_total' => $entry['allowances_total'],
                'deductions_total' => $entry['deductions_total'],
                'gross_pay' => $entry['gross_pay'],
                'net_pay' => $entry['net_pay'],
            ];
        }

        $insertItemsResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payroll_items',
            array_merge($headers, ['Prefer: return=representation']),
            $rows
        );

        if (!isSuccessful($insertItemsResponse)) {
            redirectWithState('error', 'Payroll batch created, but failed to insert payroll items. Please cancel batch ' . $runId . ' and retry.');
        }

        $generatedCount += count((array)($insertItemsResponse['data'] ?? []));
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_runs',
            'entity_id' => $runId,
            'action_name' => 'generate_batch',
            'old_data' => null,
            'new_data' => [
                'payroll_period_id' => $periodId,
                'generated_items' => $generatedCount,
                'skipped_employees' => $skippedEmployees,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $periodCode = (string)($periodRow['period_code'] ?? 'selected period');
    redirectWithState('success', 'Payroll batch generated for ' . $periodCode . '. Items created: ' . $generatedCount . ($skippedEmployees > 0 ? (', skipped: ' . $skippedEmployees) : '') . '.');
}

if ($action === 'save_salary_setup') {
    $personId = cleanText($_POST['person_id'] ?? null) ?? '';
    $basePay = (float)($_POST['base_pay'] ?? 0);
    $allowance = (float)($_POST['allowance_total'] ?? 0);
    $taxDeduction = (float)($_POST['tax_deduction'] ?? 0);
    $governmentDeduction = (float)($_POST['government_deductions'] ?? 0);
    $otherDeduction = (float)($_POST['other_deductions'] ?? 0);
    $payFrequency = strtolower((string)(cleanText($_POST['pay_frequency'] ?? null) ?? 'semi_monthly'));
    $effectiveFrom = cleanText($_POST['effective_from'] ?? null) ?? gmdate('Y-m-d');

    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Please select a valid employee.');
    }

    if ($basePay < 0 || $allowance < 0 || $taxDeduction < 0 || $governmentDeduction < 0 || $otherDeduction < 0) {
        redirectWithState('error', 'Salary amounts must be non-negative values.');
    }

    if (!in_array($payFrequency, ['monthly', 'semi_monthly', 'weekly'], true)) {
        redirectWithState('error', 'Invalid pay frequency selected.');
    }

    $monthlyRate = round($basePay + $allowance, 2);
    if ($monthlyRate <= 0) {
        redirectWithState('error', 'Computed monthly rate must be greater than zero.');
    }

    $allCompensationFetch = payrollFetchCompensations(
        $supabaseUrl,
        $headers,
        '&person_id=eq.' . $personId . '&order=effective_from.desc,created_at.desc&limit=5000'
    );
    $allCompensationResponse = $allCompensationFetch['response'] ?? ['status' => 500, 'data' => [], 'raw' => 'Failed to load compensation rows.'];

    if (!isSuccessful($allCompensationResponse)) {
        redirectWithState('error', 'Failed to load current compensation records.');
    }

    $compensationRows = (array)($allCompensationFetch['rows'] ?? []);
    $existingRow = null;
    $overlapRow = null;
    $nextFutureRow = null;

    foreach ($compensationRows as $row) {
        $rowId = strtolower(trim((string)($row['id'] ?? '')));
        $rowFrom = payrollDateString((string)($row['effective_from'] ?? ''));
        $rowTo = payrollDateString((string)($row['effective_to'] ?? ''));
        if (!isValidUuid($rowId) || $rowFrom === '') {
            continue;
        }

        if ($rowFrom === $effectiveFrom && $existingRow === null) {
            $existingRow = $row;
        }

        if ($rowFrom <= $effectiveFrom && ($rowTo === '' || $rowTo >= $effectiveFrom) && $overlapRow === null) {
            $overlapRow = $row;
        }

        if ($rowFrom > $effectiveFrom) {
            if ($nextFutureRow === null || $rowFrom < payrollDateString((string)($nextFutureRow['effective_from'] ?? '9999-12-31'))) {
                $nextFutureRow = $row;
            }
        }
    }

    $compensationId = '';
    if (is_array($existingRow) && isValidUuid((string)($existingRow['id'] ?? ''))) {
        $existingCompensationId = (string)$existingRow['id'];
        $updateResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/employee_compensations?id=eq.' . $existingCompensationId,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'monthly_rate' => $monthlyRate,
                'daily_rate' => round($monthlyRate / 22, 2),
                'hourly_rate' => round($monthlyRate / 22 / 8, 2),
                'base_pay' => round($basePay, 2),
                'allowance_total' => round($allowance, 2),
                'tax_deduction' => round($taxDeduction, 2),
                'government_deductions' => round($governmentDeduction, 2),
                'other_deductions' => round($otherDeduction, 2),
                'pay_frequency' => $payFrequency,
            ]
        );

        if (!isSuccessful($updateResponse)) {
            if (payrollCompensationColumnsMissing($updateResponse)) {
                redirectWithState('error', 'Payroll salary component columns are missing in Supabase. Apply the latest schema migration for employee_compensations first.');
            }
            redirectWithState('error', 'Failed to update salary setup for existing effective date.');
        }

        $compensationId = $existingCompensationId;
    } else {
        if (is_array($overlapRow) && isValidUuid((string)($overlapRow['id'] ?? ''))) {
            $overlapId = (string)$overlapRow['id'];
            $overlapCloseDate = gmdate('Y-m-d', strtotime($effectiveFrom . ' -1 day'));
            $closeResponse = apiRequest(
                'PATCH',
                $supabaseUrl . '/rest/v1/employee_compensations?id=eq.' . $overlapId,
                array_merge($headers, ['Prefer: return=minimal']),
                [
                    'effective_to' => $overlapCloseDate,
                ]
            );

            if (!isSuccessful($closeResponse)) {
                redirectWithState('error', 'Failed to adjust overlapping compensation record.');
            }
        }

        $insertPayload = [
            'person_id' => $personId,
            'effective_from' => $effectiveFrom,
            'monthly_rate' => $monthlyRate,
            'daily_rate' => round($monthlyRate / 22, 2),
            'hourly_rate' => round($monthlyRate / 22 / 8, 2),
            'base_pay' => round($basePay, 2),
            'allowance_total' => round($allowance, 2),
            'tax_deduction' => round($taxDeduction, 2),
            'government_deductions' => round($governmentDeduction, 2),
            'other_deductions' => round($otherDeduction, 2),
            'pay_frequency' => $payFrequency,
        ];

        if (is_array($nextFutureRow)) {
            $nextStart = payrollDateString((string)($nextFutureRow['effective_from'] ?? ''));
            if ($nextStart !== '') {
                $insertPayload['effective_to'] = gmdate('Y-m-d', strtotime($nextStart . ' -1 day'));
            }
        }

        $insertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/employee_compensations',
            array_merge($headers, ['Prefer: return=representation']),
            [$insertPayload]
        );

        if (!isSuccessful($insertResponse)) {
            if (payrollCompensationColumnsMissing($insertResponse)) {
                redirectWithState('error', 'Payroll salary component columns are missing in Supabase. Apply the latest schema migration for employee_compensations first.');
            }
            redirectWithState('error', 'Failed to save salary setup.');
        }

        $compensationId = (string)($insertResponse['data'][0]['id'] ?? '');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'employee_compensations',
            'entity_id' => $compensationId !== '' ? $compensationId : null,
            'action_name' => 'save_salary_setup',
            'old_data' => is_array($existingRow) ? [
                'monthly_rate' => $existingRow['monthly_rate'] ?? null,
                'base_pay' => $existingRow['base_pay'] ?? null,
                'allowance_total' => $existingRow['allowance_total'] ?? null,
                'tax_deduction' => $existingRow['tax_deduction'] ?? null,
                'government_deductions' => $existingRow['government_deductions'] ?? null,
                'other_deductions' => $existingRow['other_deductions'] ?? null,
                'pay_frequency' => $existingRow['pay_frequency'] ?? null,
                'effective_from' => $existingRow['effective_from'] ?? null,
            ] : null,
            'new_data' => [
                'person_id' => $personId,
                'base_pay' => $basePay,
                'allowance_total' => $allowance,
                'tax_deduction' => $taxDeduction,
                'government_deductions' => $governmentDeduction,
                'other_deductions' => $otherDeduction,
                'monthly_rate' => $monthlyRate,
                'pay_frequency' => $payFrequency,
                'effective_from' => $effectiveFrom,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $pendingPeriodsCreated = payrollEnsureUpcomingPeriods($effectiveFrom, $supabaseUrl, $headers);

    $today = gmdate('Y-m-d');
    $effectivityNote = $effectiveFrom > $today
        ? ' This setup is scheduled and will apply starting ' . $effectiveFrom . '.'
        : ' Changes apply to succeeding payroll computations.';

    $periodNote = $pendingPeriodsCreated > 0
        ? ' Pending payroll periods created: ' . $pendingPeriodsCreated . '.'
        : '';

    redirectWithState('success', 'Salary setup saved successfully.' . $effectivityNote . $periodNote);
}

if ($action === 'delete_salary_setup') {
    $setupId = cleanText($_POST['setup_id'] ?? null) ?? '';
    if (!isValidUuid($setupId)) {
        redirectWithState('error', 'Invalid salary setup selected for deletion.');
    }

    $targetResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,pay_frequency&id=eq.' . $setupId . '&limit=1',
        $headers
    );

    $targetRow = $targetResponse['data'][0] ?? null;
    if (!is_array($targetRow)) {
        redirectWithState('error', 'Salary setup record was not found.');
    }

    $personId = strtolower(trim((string)($targetRow['person_id'] ?? '')));
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Salary setup record has invalid employee information.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/employee_compensations?id=eq.' . $setupId,
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete salary setup record.');
    }

    payrollRefreshCompensationTimelineForPerson($personId, $supabaseUrl, $headers);

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'employee_compensations',
            'entity_id' => $setupId,
            'action_name' => 'delete_salary_setup',
            'old_data' => $targetRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Salary setup deleted and compensation timeline refreshed.');
}

if ($action === 'delete_salary_setup_bulk') {
    $submittedIds = $_POST['setup_ids'] ?? [];
    if (!is_array($submittedIds) || empty($submittedIds)) {
        redirectWithState('error', 'Select at least one salary setup to delete.');
    }

    $setupIds = [];
    foreach ($submittedIds as $submittedId) {
        $candidate = strtolower(trim((string)$submittedId));
        if (!isValidUuid($candidate)) {
            continue;
        }
        $setupIds[] = $candidate;
    }
    $setupIds = array_values(array_unique($setupIds));

    if (empty($setupIds)) {
        redirectWithState('error', 'No valid salary setup IDs were provided for deletion.');
    }

    $setupFilter = formatInFilterList($setupIds);
    if ($setupFilter === '') {
        redirectWithState('error', 'Unable to prepare selected salary setups for deletion.');
    }

    $targetResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,pay_frequency&id=in.(' . $setupFilter . ')&limit=5000',
        $headers
    );

    if (!isSuccessful($targetResponse)) {
        redirectWithState('error', 'Failed to load selected salary setup records.');
    }

    $targetRows = array_values(array_filter((array)($targetResponse['data'] ?? []), static function (array $row): bool {
        return isValidUuid((string)($row['id'] ?? ''));
    }));

    if (empty($targetRows)) {
        redirectWithState('error', 'Selected salary setup records were not found.');
    }

    $targetIds = array_values(array_unique(array_map(static function (array $row): string {
        return strtolower((string)($row['id'] ?? ''));
    }, $targetRows)));

    $deleteFilter = formatInFilterList($targetIds);
    if ($deleteFilter === '') {
        redirectWithState('error', 'Unable to prepare selected salary setup IDs for deletion.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/employee_compensations?id=in.(' . $deleteFilter . ')',
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete selected salary setup records.');
    }

    $affectedPersonIds = [];
    $activityRows = [];
    foreach ($targetRows as $targetRow) {
        $setupId = strtolower((string)($targetRow['id'] ?? ''));
        $personId = strtolower(trim((string)($targetRow['person_id'] ?? '')));
        if (isValidUuid($personId)) {
            $affectedPersonIds[$personId] = true;
        }

        $activityRows[] = [
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'employee_compensations',
            'entity_id' => $setupId !== '' ? $setupId : null,
            'action_name' => 'delete_salary_setup_bulk',
            'old_data' => $targetRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ];
    }

    foreach (array_keys($affectedPersonIds) as $personId) {
        payrollRefreshCompensationTimelineForPerson($personId, $supabaseUrl, $headers);
    }

    if (!empty($activityRows)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            $activityRows
        );
    }

    $deletedCount = count($targetIds);
    redirectWithState('success', 'Deleted ' . $deletedCount . ' salary setup ' . ($deletedCount === 1 ? 'entry' : 'entries') . ' and refreshed compensation timelines.');
}

if ($action === 'delete_payroll_batch') {
    $runId = cleanText($_POST['run_id'] ?? null) ?? '';
    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Invalid payroll batch selected for deletion.');
    }

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id,payroll_period:payroll_periods(period_code)&id=eq.' . $runId . '&limit=1',
        $headers
    );

    $runRow = $runResponse['data'][0] ?? null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll batch was not found.');
    }

    $runStatus = strtolower(trim((string)($runRow['run_status'] ?? 'draft')));
    if ($runStatus === 'released') {
        redirectWithState('error', 'Released payroll batches cannot be deleted.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . $runId,
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete payroll batch.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_runs',
            'entity_id' => $runId,
            'action_name' => 'delete_batch',
            'old_data' => $runRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ]]
    );

    $periodCode = (string)($runRow['payroll_period']['period_code'] ?? 'selected period');
    redirectWithState('success', 'Payroll batch for ' . $periodCode . ' was deleted successfully.');
}

if ($action === 'delete_payroll_period') {
    $periodId = cleanText($_POST['period_id'] ?? null) ?? '';
    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Invalid payroll period selected for deletion.');
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&id=eq.' . $periodId . '&limit=1',
        $headers
    );

    $periodRow = $periodResponse['data'][0] ?? null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Payroll period was not found.');
    }

    $periodCountResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id&limit=2',
        $headers
    );

    if (isSuccessful($periodCountResponse)) {
        $periodCount = count((array)($periodCountResponse['data'] ?? []));
        if ($periodCount <= 1) {
            redirectWithState('error', 'Cannot delete the last remaining payroll period.');
        }
    }

    $runsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status&payroll_period_id=eq.' . $periodId . '&limit=1',
        $headers
    );

    if (!isSuccessful($runsResponse)) {
        redirectWithState('error', 'Failed to validate payroll period dependencies.');
    }

    $existingRun = $runsResponse['data'][0] ?? null;
    if (is_array($existingRun)) {
        $runStatus = strtolower((string)($existingRun['run_status'] ?? 'draft'));
        redirectWithState('error', 'Cannot delete this payroll period because it already has a payroll batch (status: ' . $runStatus . '). Delete related batches first.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/payroll_periods?id=eq.' . $periodId,
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete payroll period.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_periods',
            'entity_id' => $periodId,
            'action_name' => 'delete_period',
            'old_data' => $periodRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ]]
    );

    $periodCode = (string)($periodRow['period_code'] ?? 'selected period');
    redirectWithState('success', 'Payroll period ' . $periodCode . ' was deleted successfully.');
}

if ($action === 'review_payroll_batch') {
    $runId = cleanText($_POST['run_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? 'approved'));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Invalid payroll batch selected.');
    }

    if (!in_array($decision, ['approved', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid payroll batch decision selected.');
    }

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id&id=eq.' . $runId . '&limit=1',
        $headers
    );

    $runRow = $runResponse['data'][0] ?? null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll batch not found.');
    }

    $oldStatus = strtolower((string)($runRow['run_status'] ?? 'draft'));

    $patchPayload = [
        'run_status' => $decision,
        'updated_at' => gmdate('c'),
    ];

    if ($decision === 'approved') {
        $patchPayload['approved_by'] = $adminUserId !== '' ? $adminUserId : null;
        $patchPayload['approved_at'] = gmdate('c');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . $runId,
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update payroll batch.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_runs',
            'entity_id' => $runId,
            'action_name' => 'review_batch',
            'old_data' => ['run_status' => $oldStatus],
            'new_data' => ['run_status' => $decision, 'notes' => $notes],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Payroll batch updated successfully.');
}

if ($action === 'release_payslips') {
    $runId = cleanText($_POST['payroll_run_id'] ?? null) ?? '';
    $recipientGroup = cleanText($_POST['recipient_group'] ?? null) ?? 'all_active';
    $deliveryMode = cleanText($_POST['delivery_mode'] ?? null) ?? 'immediate';

    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Please select a valid payroll batch to release.');
    }

    if (!in_array($recipientGroup, ['all_active'], true)) {
        redirectWithState('error', 'Selected recipient group is not yet supported for payroll email release.');
    }

    if (!in_array(strtolower($deliveryMode), ['immediate'], true)) {
        redirectWithState('error', 'Scheduled payroll email sending is not yet enabled. Use Send Immediately.');
    }

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id,payroll_period:payroll_periods(period_code,period_start,period_end)&id=eq.' . $runId . '&limit=1',
        $headers
    );

    $runRow = $runResponse['data'][0] ?? null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll run not found.');
    }

    $runStatus = strtolower((string)($runRow['run_status'] ?? 'draft'));
    if ($runStatus === 'released') {
        redirectWithState('success', 'Selected payroll batch is already released.');
    }

    if (!in_array($runStatus, ['approved', 'computed'], true)) {
        redirectWithState('error', 'Only approved or computed payroll batches can be released.');
    }

    $itemResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_items?select=id,person_id,gross_pay,deductions_total,net_pay,person:people(first_name,surname,user_id)&payroll_run_id=eq.' . $runId . '&limit=5000',
        $headers
    );

    if (!isSuccessful($itemResponse)) {
        redirectWithState('error', 'Failed to load payroll items for release.');
    }

    $items = (array)($itemResponse['data'] ?? []);
    if (empty($items)) {
        redirectWithState('error', 'No payroll items found for the selected batch.');
    }

    $shouldSendEmails = strtolower($deliveryMode) === 'immediate';
    if ($shouldSendEmails && !smtpConfigIsReady($smtpConfig, $mailFrom)) {
        redirectWithState('error', 'SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, and MAIL_FROM are required for SMTP email sending.');
    }

    $emailAddressByUserId = [];
    if ($shouldSendEmails) {
        $userIds = [];
        foreach ($items as $item) {
            $candidateUserId = strtolower(trim((string)($item['person']['user_id'] ?? '')));
            if (isValidUuid($candidateUserId)) {
                $userIds[] = $candidateUserId;
            }
        }

        $userInFilter = formatInFilterList($userIds);
        if ($userInFilter !== '') {
            $usersResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . $userInFilter . ')&limit=5000',
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
    }

    $itemIds = array_values(array_filter(array_map(static fn(array $row): string => (string)($row['id'] ?? ''), $items), 'is_string'));
    $inFilter = formatInFilterList($itemIds);
    if ($inFilter === '') {
        redirectWithState('error', 'Invalid payroll item identifiers.');
    }

    $existingPayslipsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,released_at&payroll_item_id=in.(' . $inFilter . ')&limit=5000',
        $headers
    );

    if (!isSuccessful($existingPayslipsResponse)) {
        redirectWithState('error', 'Failed to load existing payslip records.');
    }

    $existingPayslips = [];
    foreach ((array)$existingPayslipsResponse['data'] as $row) {
        $key = (string)($row['payroll_item_id'] ?? '');
        if ($key === '') {
            continue;
        }
        $existingPayslips[$key] = $row;
    }

    $periodCode = (string)($runRow['payroll_period']['period_code'] ?? 'PR');
    $newPayslipPayload = [];
    foreach ($items as $item) {
        $payrollItemId = (string)($item['id'] ?? '');
        if (!isValidUuid($payrollItemId)) {
            continue;
        }

        if (isset($existingPayslips[$payrollItemId])) {
            continue;
        }

        $newPayslipPayload[] = [
            'payroll_item_id' => $payrollItemId,
            'payslip_no' => strtoupper($periodCode) . '-' . gmdate('Ymd') . '-' . strtoupper(substr(str_replace('-', '', $payrollItemId), 0, 8)),
        ];
    }

    if (!empty($newPayslipPayload)) {
        $insertPayslips = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payslips',
            array_merge($headers, ['Prefer: return=representation']),
            $newPayslipPayload
        );

        if (!isSuccessful($insertPayslips)) {
            redirectWithState('error', 'Failed to create payslip records.');
        }

        foreach ((array)$insertPayslips['data'] as $row) {
            $key = (string)($row['payroll_item_id'] ?? '');
            if ($key === '') {
                continue;
            }
            $existingPayslips[$key] = $row;
        }
    }

    $periodStartRaw = cleanText($runRow['payroll_period']['period_start'] ?? null) ?? '';
    $periodEndRaw = cleanText($runRow['payroll_period']['period_end'] ?? null) ?? '';
    $periodLabel = ($periodStartRaw !== '' && $periodEndRaw !== '')
        ? (date('M d, Y', strtotime($periodStartRaw)) . ' - ' . date('M d, Y', strtotime($periodEndRaw)))
        : strtoupper($periodCode);

    foreach ($items as $item) {
        $payrollItemId = strtolower(trim((string)($item['id'] ?? '')));
        if ($payrollItemId === '' || !isset($existingPayslips[$payrollItemId])) {
            continue;
        }

        $payslipRow = (array)$existingPayslips[$payrollItemId];
        $payslipId = (string)($payslipRow['id'] ?? '');
        $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? strtoupper($periodCode);
        if (!isValidUuid($payslipId)) {
            continue;
        }

        $firstName = cleanText($item['person']['first_name'] ?? null) ?? '';
        $surname = cleanText($item['person']['surname'] ?? null) ?? '';
        $employeeName = trim($firstName . ' ' . $surname);
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        try {
            $document = payrollGeneratePayslipDocument([
                'project_root' => dirname(__DIR__, 4),
                'payslip_no' => $payslipNo,
                'employee_name' => $employeeName,
                'period_label' => $periodLabel,
                'gross_pay' => (float)($item['gross_pay'] ?? 0),
                'deductions_total' => (float)($item['deductions_total'] ?? 0),
                'net_pay' => (float)($item['net_pay'] ?? 0),
            ]);

            $storagePath = cleanText($document['storage_path'] ?? null);
            if ($storagePath) {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/payslips?id=eq.' . $payslipId,
                    array_merge($headers, ['Prefer: return=minimal']),
                    ['pdf_storage_path' => $storagePath]
                );
                $existingPayslips[$payrollItemId]['pdf_storage_path'] = $storagePath;
            }
        } catch (Throwable $throwable) {
        }
    }

    $allPayslipIds = formatInFilterList(array_values(array_map(static fn(array $row): string => (string)($row['id'] ?? ''), array_values($existingPayslips))));
    if ($allPayslipIds !== '') {
        $releasePayslipResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payslips?id=in.(' . $allPayslipIds . ')',
            array_merge($headers, ['Prefer: return=minimal']),
            ['released_at' => gmdate('c')]
        );

        if (!isSuccessful($releasePayslipResponse)) {
            redirectWithState('error', 'Failed to update payslip release timestamps.');
        }
    }

    $runReleaseResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . $runId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'run_status' => 'released',
            'updated_at' => gmdate('c'),
            'approved_by' => $adminUserId !== '' ? $adminUserId : null,
            'approved_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($runReleaseResponse)) {
        redirectWithState('error', 'Failed to mark payroll run as released.');
    }

    $periodIdForRelease = strtolower(trim((string)($runRow['payroll_period_id'] ?? '')));
    if (isValidUuid($periodIdForRelease)) {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payroll_periods?id=eq.' . $periodIdForRelease,
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'status' => 'closed',
                'updated_at' => gmdate('c'),
            ]
        );
    }

    $notifications = [];
    foreach ($items as $item) {
        $recipientUserId = (string)($item['person']['user_id'] ?? '');
        if (!isValidUuid($recipientUserId)) {
            continue;
        }

        $notifications[] = [
            'recipient_user_id' => $recipientUserId,
            'category' => 'payroll',
            'title' => 'Payslip Released',
            'body' => 'Your payslip for ' . strtoupper($periodCode) . ' is now available.',
            'link_url' => '/hris-system/pages/employee/payroll.php',
        ];

        if (count($notifications) >= 200) {
            $notifyResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                $notifications
            );

            if (!isSuccessful($notifyResponse)) {
                redirectWithState('error', 'Payroll released but failed to queue some employee notifications.');
            }
            $notifications = [];
        }
    }

    if (!empty($notifications)) {
        $notifyResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            $notifications
        );

        if (!isSuccessful($notifyResponse)) {
            redirectWithState('error', 'Payroll released but failed to queue employee notifications.');
        }
    }

    $emailsAttempted = 0;
    $emailsSent = 0;
    $emailsFailed = 0;
    $emailErrorSamples = [];

    if ($shouldSendEmails) {
        foreach ($items as $item) {
            $recipientUserId = strtolower(trim((string)($item['person']['user_id'] ?? '')));
            if (!isValidUuid($recipientUserId)) {
                continue;
            }

            $recipientEmail = $emailAddressByUserId[$recipientUserId] ?? '';
            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $payrollItemId = strtolower(trim((string)($item['id'] ?? '')));
            $payslipRow = $existingPayslips[$payrollItemId] ?? null;
            $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? strtoupper($periodCode);

            $firstName = cleanText($item['person']['first_name'] ?? null) ?? '';
            $surname = cleanText($item['person']['surname'] ?? null) ?? '';
            $employeeName = trim($firstName . ' ' . $surname);
            if ($employeeName === '') {
                $employeeName = 'Employee';
            }

            $emailsAttempted++;

            $subject = 'Payslip Released - ' . strtoupper($periodCode);
            $html = '<p>Hi ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your payslip for payroll period <strong>' . htmlspecialchars(strtoupper($periodCode), ENT_QUOTES, 'UTF-8') . '</strong> is now released.</p>'
                . '<p>Payslip No: <strong>' . htmlspecialchars($payslipNo, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                . 'Net Pay: <strong>PHP ' . number_format((float)($item['net_pay'] ?? 0), 2) . '</strong></p>'
                . '<p>You may view details in your employee payroll page.</p>';

            $emailResponse = smtpSendTransactionalEmail(
                $smtpConfig,
                $mailFrom,
                $mailFromName,
                $recipientEmail,
                $employeeName,
                $subject,
                $html
            );

            if (isSuccessful($emailResponse)) {
                $emailsSent++;
            } else {
                $emailsFailed++;
                if (count($emailErrorSamples) < 3) {
                    $emailErrorSamples[] = trim((string)($emailResponse['raw'] ?? 'SMTP send failed'));
                }
            }
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payslips',
            'entity_id' => null,
            'action_name' => 'release_payslips',
            'old_data' => ['run_status' => $runRow['run_status'] ?? null],
            'new_data' => [
                'payroll_run_id' => $runId,
                'recipient_group' => $recipientGroup,
                'delivery_mode' => $deliveryMode,
                'released_count' => count($items),
                'email_attempted' => $emailsAttempted,
                'email_sent' => $emailsSent,
                'email_failed' => $emailsFailed,
                'email_error_samples' => $emailErrorSamples,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if ($shouldSendEmails) {
        $message = 'Payslips released successfully. Email sent: ' . $emailsSent . ', failed: ' . $emailsFailed . '.';
        if ($emailsFailed > 0 && !empty($emailErrorSamples)) {
            $message .= ' Sample error: ' . $emailErrorSamples[0];
        }
        redirectWithState('success', $message);
    }

    redirectWithState('success', 'Payslips released successfully for selected payroll batch.');
}

redirectWithState('error', 'Unknown payroll action.');
