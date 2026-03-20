<?php

require_once __DIR__ . '/../notifications/email.php';
require_once __DIR__ . '/../../../shared/lib/payroll-domain.php';

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

if (!function_exists('payrollSmtpSendEmailWithAttachment')) {
    function payrollSmtpSendEmailWithAttachment(
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
        $delegate = 'payrollServiceSendEmailWithAttachment';
        return $delegate($smtpConfig, $fromEmail, $fromName, $toEmail, $toName, $subject, $htmlContent, $attachmentPath, $attachmentName);
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
        $delegate = 'payrollServiceFormatInFilterList';
        return $delegate($ids);
    }
}

if (!function_exists('payrollDateString')) {
    function payrollDateString(string $value): string
    {
        $delegate = 'payrollServiceDateString';
        return $delegate($value);
    }
}

if (!function_exists('payrollMaskEmailAddress')) {
    function payrollMaskEmailAddress(string $email): string
    {
        $delegate = 'payrollServiceMaskEmailAddress';
        return $delegate($email);
    }
}

if (!function_exists('payrollSanitizeEmailError')) {
    function payrollSanitizeEmailError(string $raw): string
    {
        $delegate = 'payrollServiceSanitizeEmailError';
        return $delegate($raw);
    }
}

if (!function_exists('payrollNormalizeCompensationRow')) {
    function payrollNormalizeCompensationRow(array $row): array
    {
        $delegate = 'payrollServiceNormalizeCompensationRow';
        return $delegate($row);
    }
}

if (!function_exists('payrollFetchCompensations')) {
    function payrollFetchCompensations(string $supabaseUrl, array $headers, string $querySuffix): array
    {
        $selectWithComponents = 'id,person_id,monthly_rate,daily_rate,hourly_rate,pay_frequency,effective_from,effective_to,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,created_at';
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
        $delegate = 'payrollServiceCompensationAppliesToPeriod';
        return $delegate($row, $periodStart, $periodEnd);
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
        $delegate = 'payrollServiceEnsureDirectory';
        $delegate($dirPath);
    }
}

if (!function_exists('payrollSyncDefaultConfig')) {
    function payrollSyncDefaultConfig(): array
    {
        $delegate = 'payrollServiceDefaultSyncConfig';
        return $delegate();
    }
}

if (!function_exists('payrollGetSystemSettingValue')) {
    function payrollGetSystemSettingValue(string $supabaseUrl, array $headers, string $settingKey, mixed $defaultValue = null): mixed
    {
        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/system_settings?select=setting_value&setting_key=eq.' . rawurlencode($settingKey) . '&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return $defaultValue;
        }

        $row = $response['data'][0] ?? null;
        if (!is_array($row) || !array_key_exists('setting_value', $row)) {
            return $defaultValue;
        }

        return $row['setting_value'];
    }
}

if (!function_exists('payrollUpsertSystemSettings')) {
    function payrollUpsertSystemSettings(string $supabaseUrl, array $headers, array $rows): bool
    {
        if (empty($rows)) {
            return true;
        }

        $response = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/system_settings?on_conflict=setting_key',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            $rows
        );

        return isSuccessful($response);
    }
}

if (!function_exists('payrollNormalizeSheetHeader')) {
    function payrollNormalizeSheetHeader(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized);
        return trim((string)$normalized, '_');
    }
}

if (!function_exists('payrollSpreadsheetCellValue')) {
    function payrollSpreadsheetCellValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $normalizedKey = payrollNormalizeSheetHeader((string)$key);
            if ($normalizedKey !== '' && array_key_exists($normalizedKey, $row)) {
                return $row[$normalizedKey];
            }
        }

        return null;
    }
}

if (!function_exists('payrollSpreadsheetMoney')) {
    function payrollSpreadsheetMoney(mixed $value): float
    {
        if (is_numeric($value)) {
            return round(max(0.0, (float)$value), 2);
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $raw);
        if (!is_string($normalized) || $normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return round(max(0.0, (float)$normalized), 2);
    }
}

if (!function_exists('payrollNormalizeEmployeeLookupKey')) {
    function payrollNormalizeEmployeeLookupKey(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized);
        return trim((string)$normalized);
    }
}

if (!function_exists('payrollIsCosEmploymentStatus')) {
    function payrollIsCosEmploymentStatus(?string $employmentStatus): bool
    {
        $delegate = 'payrollServiceIsCosEmploymentStatus';
        return $delegate($employmentStatus);
    }
}

if (!function_exists('payrollReadWorkbookRows')) {
    function payrollReadWorkbookRows(string $absolutePath): array
    {
        $autoloadPath = dirname(__DIR__, 4) . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            throw new RuntimeException('PhpSpreadsheet dependency is not available. Run composer install to enable payroll workbook imports.');
        }

        $ioFactoryClass = 'PhpOffice\\PhpSpreadsheet\\IOFactory';
        $spreadsheet = $ioFactoryClass::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        $headerRow = null;
        $parsedRows = [];
        foreach ($rawRows as $row) {
            $cells = array_map(static fn($value): string => trim((string)$value), (array)$row);
            $hasValues = count(array_filter($cells, static fn(string $value): bool => $value !== '')) > 0;
            if (!$hasValues) {
                continue;
            }

            if ($headerRow === null) {
                $headerRow = array_map('payrollNormalizeSheetHeader', $cells);
                continue;
            }

            $normalizedRow = [];
            foreach ($headerRow as $index => $headerKey) {
                if ($headerKey === '') {
                    continue;
                }
                $normalizedRow[$headerKey] = $row[$index] ?? null;
            }

            $parsedRows[] = $normalizedRow;
        }

        return $parsedRows;
    }
}

if (!function_exists('payrollGeneratePayslipDocument')) {
    function payrollGeneratePayslipDocument(array $payload): array
    {
        $delegate = 'payrollServiceGeneratePayslipDocument';
        return $delegate($payload);
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

if ($action === 'save_payroll_sync_settings') {
    $config = payrollSyncDefaultConfig();
    $config['payroll_excel_url'] = trim((string)(cleanText($_POST['payroll_excel_url'] ?? null) ?? ''));
    $config['payslip_excel_url'] = trim((string)(cleanText($_POST['payslip_excel_url'] ?? null) ?? ''));
    $config['google_sheet_url'] = trim((string)(cleanText($_POST['google_sheet_url'] ?? null) ?? ''));
    $config['workflow_notes'] = trim((string)(cleanText($_POST['workflow_notes'] ?? null) ?? ''));

    $permanentTimekeepingSource = strtolower(trim((string)(cleanText($_POST['permanent_timekeeping_source'] ?? null) ?? 'attendance')));
    $cosTimekeepingSource = strtolower(trim((string)(cleanText($_POST['cos_timekeeping_source'] ?? null) ?? 'import')));

    if (!in_array($permanentTimekeepingSource, ['attendance', 'import'], true) || !in_array($cosTimekeepingSource, ['attendance', 'import'], true)) {
        redirectWithState('error', 'Invalid payroll rule source selected.');
    }

    $config['permanent_timekeeping_source'] = $permanentTimekeepingSource;
    $config['cos_timekeeping_source'] = $cosTimekeepingSource;

    $nowIso = gmdate('c');
    $saved = payrollUpsertSystemSettings(
        $supabaseUrl,
        $headers,
        [[
            'setting_key' => 'payroll.sync.config',
            'setting_value' => $config,
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => $nowIso,
        ]]
    );

    if (!$saved) {
        redirectWithState('error', 'Failed to save payroll sync configuration.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'system_settings',
            'entity_id' => 'payroll.sync.config',
            'action_name' => 'save_payroll_sync_settings',
            'old_data' => null,
            'new_data' => $config,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Payroll source links and deduction rules saved successfully.');
}

if ($action === 'import_payroll_deduction_workbook') {
    $periodId = trim((string)(cleanText($_POST['period_id'] ?? null) ?? ''));
    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Please select a valid payroll period for deduction import.');
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end&id=eq.' . rawurlencode($periodId) . '&limit=1',
        $headers
    );
    $periodRow = $periodResponse['data'][0] ?? null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Selected payroll period was not found for deduction import.');
    }

    $uploadedFile = $_FILES['deduction_workbook'] ?? null;
    if (!is_array($uploadedFile) || (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please upload a valid payroll deduction workbook.');
    }

    $originalName = trim((string)($uploadedFile['name'] ?? 'payroll-deductions.xlsx'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
        redirectWithState('error', 'Payroll deduction import only accepts .xlsx, .xls, or .csv files.');
    }

    $importsDir = dirname(__DIR__, 4) . '/storage/reports/payroll-imports';
    payrollEnsureDirectory($importsDir);
    $storedBaseName = 'deduction-import-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(5)), 0, 10) . '.' . $extension;
    $storedAbsolutePath = $importsDir . '/' . $storedBaseName;
    if (!move_uploaded_file((string)($uploadedFile['tmp_name'] ?? ''), $storedAbsolutePath)) {
        redirectWithState('error', 'Failed to store the uploaded payroll deduction workbook.');
    }

    try {
        $worksheetRows = payrollReadWorkbookRows($storedAbsolutePath);
    } catch (RuntimeException $error) {
        redirectWithState('error', $error->getMessage());
    } catch (Throwable $error) {
        redirectWithState('error', 'Failed to read the payroll deduction workbook. Check the file format and try again.');
    }

    if (empty($worksheetRows)) {
        redirectWithState('error', 'Payroll deduction workbook is empty or has no readable rows.');
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id,employment_status,person:people!employment_records_person_id_fkey(id,first_name,middle_name,surname)'
        . '&is_current=eq.true&limit=10000',
        $headers
    );
    if (!isSuccessful($employmentResponse)) {
        redirectWithState('error', 'Failed to load active employees for payroll deduction matching.');
    }

    $lookup = [];
    foreach ((array)($employmentResponse['data'] ?? []) as $employmentRow) {
        $personId = strtolower(trim((string)($employmentRow['person_id'] ?? '')));
        if (!isValidUuid($personId)) {
            continue;
        }

        $employmentStatus = trim((string)(cleanText($employmentRow['employment_status'] ?? null) ?? ''));
        $statusKey = strtolower($employmentStatus);
        if (in_array($statusKey, ['inactive', 'separated', 'terminated', 'resigned', 'retired'], true)) {
            continue;
        }

        $person = is_array($employmentRow['person'] ?? null) ? (array)$employmentRow['person'] : [];
        $employeeName = trim(
            ((string)($person['first_name'] ?? ''))
            . ' '
            . ((string)($person['middle_name'] ?? ''))
            . ' '
            . ((string)($person['surname'] ?? ''))
        );
        $shortCode = strtoupper(substr(str_replace('-', '', $personId), 0, 6));

        $keys = [
            strtolower($personId),
            strtolower($shortCode),
            payrollNormalizeEmployeeLookupKey($employeeName),
        ];

        foreach ($keys as $key) {
            if ($key === '' || isset($lookup[$key])) {
                continue;
            }

            $lookup[$key] = [
                'person_id' => $personId,
                'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown Employee',
                'employment_status' => $employmentStatus,
                'is_cos_employee' => payrollIsCosEmploymentStatus($employmentStatus),
            ];
        }
    }

    $periodCode = trim((string)($periodRow['period_code'] ?? ''));
    $aggregatedRows = [];
    $unmatchedIdentifiers = [];
    $matchedRows = 0;
    $skippedRows = 0;

    foreach ($worksheetRows as $worksheetRow) {
        $row = (array)$worksheetRow;
        $identifierRaw = payrollSpreadsheetCellValue($row, ['employee_identifier', 'employee_id', 'person_id', 'employee_name', 'employee', 'name']);
        $identifier = trim((string)$identifierRaw);
        if ($identifier === '') {
            $skippedRows++;
            continue;
        }

        $rowPeriodCode = strtoupper(trim((string)payrollSpreadsheetCellValue($row, ['period_code', 'payroll_period', 'cutoff_period'])));
        if ($rowPeriodCode !== '' && $periodCode !== '' && $rowPeriodCode !== strtoupper($periodCode)) {
            $skippedRows++;
            continue;
        }

        $lookupKey = payrollNormalizeEmployeeLookupKey($identifier);
        $matchedEmployee = $lookup[strtolower($identifier)] ?? $lookup[strtolower(str_replace('-', '', $identifier))] ?? $lookup[$lookupKey] ?? null;
        if (!is_array($matchedEmployee)) {
            $unmatchedIdentifiers[$identifier] = true;
            continue;
        }

        $personId = (string)($matchedEmployee['person_id'] ?? '');
        if (!isValidUuid($personId)) {
            continue;
        }

        if (!isset($aggregatedRows[$personId])) {
            $aggregatedRows[$personId] = [
                'person_id' => $personId,
                'employee_name' => (string)($matchedEmployee['employee_name'] ?? 'Unknown Employee'),
                'employment_status' => (string)($matchedEmployee['employment_status'] ?? ''),
                'is_cos_employee' => (bool)($matchedEmployee['is_cos_employee'] ?? false),
                'statutory_deductions' => 0.0,
                'timekeeping_deductions' => 0.0,
                'other_deductions' => 0.0,
                'notes' => '',
                'source_identifier' => $identifier,
            ];
        }

        $aggregatedRows[$personId]['statutory_deductions'] += payrollSpreadsheetMoney(payrollSpreadsheetCellValue($row, ['statutory_deductions', 'statutory', 'recurring_deductions']));
        $aggregatedRows[$personId]['timekeeping_deductions'] += payrollSpreadsheetMoney(payrollSpreadsheetCellValue($row, ['timekeeping_deductions', 'attendance_deductions', 'leave_late_absence_deductions']));
        $aggregatedRows[$personId]['other_deductions'] += payrollSpreadsheetMoney(payrollSpreadsheetCellValue($row, ['other_deductions', 'other', 'additional_deductions']));

        $rowNotes = trim((string)payrollSpreadsheetCellValue($row, ['notes', 'remarks', 'comment']));
        if ($rowNotes !== '') {
            $aggregatedRows[$personId]['notes'] = $aggregatedRows[$personId]['notes'] !== ''
                ? ($aggregatedRows[$personId]['notes'] . '; ' . $rowNotes)
                : $rowNotes;
        }

        $matchedRows++;
    }

    if (empty($aggregatedRows)) {
        redirectWithState('error', 'No deduction rows matched active employees for the selected payroll period.');
    }

    foreach ($aggregatedRows as &$aggregatedRow) {
        $aggregatedRow['statutory_deductions'] = round((float)$aggregatedRow['statutory_deductions'], 2);
        $aggregatedRow['timekeeping_deductions'] = round((float)$aggregatedRow['timekeeping_deductions'], 2);
        $aggregatedRow['other_deductions'] = round((float)$aggregatedRow['other_deductions'], 2);
    }
    unset($aggregatedRow);

    $summary = [
        'period_id' => $periodId,
        'period_code' => $periodCode,
        'file_name' => $originalName,
        'stored_file_path' => '/hris-system/storage/reports/payroll-imports/' . $storedBaseName,
        'imported_rows' => count($worksheetRows),
        'matched_rows' => $matchedRows,
        'unmatched_rows' => count($unmatchedIdentifiers),
        'skipped_rows' => $skippedRows,
        'rows' => array_values($aggregatedRows),
    ];

    $logResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_deduction_imports',
            'entity_id' => $periodId,
            'action_name' => 'import_deduction_workbook',
            'old_data' => null,
            'new_data' => $summary,
            'ip_address' => clientIp(),
        ]]
    );

    if (!isSuccessful($logResponse)) {
        redirectWithState('error', 'Payroll deduction workbook was read, but the import log could not be stored.');
    }

    payrollUpsertSystemSettings(
        $supabaseUrl,
        $headers,
        [[
            'setting_key' => 'payroll.sync.last_deduction_import',
            'setting_value' => [
                'period_id' => $periodId,
                'period_code' => $periodCode,
                'file_name' => $originalName,
                'stored_file_path' => '/hris-system/storage/reports/payroll-imports/' . $storedBaseName,
                'matched_rows' => $matchedRows,
                'unmatched_rows' => count($unmatchedIdentifiers),
                'skipped_rows' => $skippedRows,
                'imported_at' => gmdate('c'),
                'sample_unmatched_identifiers' => array_slice(array_keys($unmatchedIdentifiers), 0, 5),
            ],
            'updated_by' => $adminUserId !== '' ? $adminUserId : null,
            'updated_at' => gmdate('c'),
        ]]
    );

    redirectWithState('success', 'Payroll deduction workbook imported successfully for ' . ($periodCode !== '' ? $periodCode : 'the selected period') . '. Matched rows: ' . $matchedRows . '.');
}

if ($action === 'generate_payroll_batch') {
    $actionReason = cleanText($_POST['action_reason'] ?? null) ?? '';
    if (trim($actionReason) === '') {
        $actionReason = 'Routine payroll batch generation';
    }

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

    $syncConfigRaw = payrollGetSystemSettingValue($supabaseUrl, $headers, 'payroll.sync.config', payrollSyncDefaultConfig());
    $syncConfig = is_array($syncConfigRaw) ? array_merge(payrollSyncDefaultConfig(), $syncConfigRaw) : payrollSyncDefaultConfig();
    $permanentTimekeepingSource = in_array((string)($syncConfig['permanent_timekeeping_source'] ?? 'attendance'), ['attendance', 'import'], true)
        ? (string)$syncConfig['permanent_timekeeping_source']
        : 'attendance';
    $cosTimekeepingSource = in_array((string)($syncConfig['cos_timekeeping_source'] ?? 'import'), ['attendance', 'import'], true)
        ? (string)$syncConfig['cos_timekeeping_source']
        : 'import';

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=person_id,office_id,employment_status&is_current=eq.true&limit=5000',
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

    $employmentPrepared = payrollServicePrepareActivePeopleFromEmploymentRows((array)($employmentResponse['data'] ?? []));
    $employmentRows = (array)($employmentPrepared['rows'] ?? []);
    $peopleById = (array)($employmentPrepared['people_by_id'] ?? []);
    $compensationRows = (array)($compensationFetch['rows'] ?? []);

    $periodCode = (string)($periodRow['period_code'] ?? '');
    $importedDeductionsPayload = payrollServiceLoadImportedDeductionsForPeriod($supabaseUrl, $headers, $periodId);
    $importedDeductionsByPersonId = (array)($importedDeductionsPayload['rows_by_person_id'] ?? []);

    if (empty($peopleById)) {
        redirectWithState('error', 'No active employees found for payroll generation.');
    }

    $compensationByPerson = payrollServiceGroupCompensationRowsByPerson($compensationRows);
    $latestCompensationByPerson = payrollServiceResolveEffectiveCompensations($compensationByPerson, $periodStart, $periodEnd);

    $attendancePersonIds = array_keys($latestCompensationByPerson);
    try {
        $attendancePayload = payrollServiceLoadAttendanceStatsForPeople(
            $supabaseUrl,
            $headers,
            $attendancePersonIds,
            $periodStart,
            $periodEnd
        );
    } catch (RuntimeException $exception) {
        redirectWithState('error', $exception->getMessage());
    }

    $attendanceStatsByPersonId = (array)($attendancePayload['stats_by_person_id'] ?? []);
    $attendanceLogCount = (int)($attendancePayload['attendance_log_count'] ?? 0);

    $computeResult = payrollServiceBuildComputedPayrollItems(
        $peopleById,
        $latestCompensationByPerson,
        $attendanceStatsByPersonId,
        [
            'imported_deductions_by_person_id' => $importedDeductionsByPersonId,
            'permanent_timekeeping_source' => $permanentTimekeepingSource,
            'cos_timekeeping_source' => $cosTimekeepingSource,
            'import_period_code' => $periodCode,
            'allow_imported_statutory' => true,
        ]
    );

    $itemPayload = (array)($computeResult['item_payload'] ?? []);
    $skippedEmployees = (int)($computeResult['skipped_people'] ?? 0);
    $totalTimekeepingDeductions = (float)($computeResult['total_timekeeping_deductions'] ?? 0);
    $totalStatutoryDeductions = (float)($computeResult['total_statutory_deductions'] ?? 0);

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

    try {
        $persistResult = payrollServicePersistComputedPayrollItems(
            $runId,
            $itemPayload,
            $supabaseUrl,
            $headers,
            $adminUserId,
            $periodStart,
            $periodEnd,
            clientIp()
        );
    } catch (RuntimeException $exception) {
        redirectWithState('error', $exception->getMessage() . ' Please cancel batch ' . $runId . ' and retry.');
    }

    $generatedCount = (int)($persistResult['count'] ?? 0);

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
                'reason' => $actionReason,
                'source_inputs' => [
                    'attendance_log_count' => $attendanceLogCount,
                    'compensation_rows_count' => count($latestCompensationByPerson),
                    'employment_rows_count' => count($employmentRows),
                    'imported_deduction_rows_count' => count($importedDeductionsByPersonId),
                ],
                'computation_breakdown_totals' => [
                    'timekeeping_deductions' => round($totalTimekeepingDeductions, 2),
                    'statutory_deductions' => round($totalStatutoryDeductions, 2),
                ],
                'policy' => [
                    'permanent_timekeeping_source' => $permanentTimekeepingSource,
                    'cos_timekeeping_source' => $cosTimekeepingSource,
                ],
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

if ($action === 'delete_payroll_batch_bulk') {
    $submittedIds = $_POST['run_ids'] ?? [];
    if (!is_array($submittedIds) || empty($submittedIds)) {
        redirectWithState('error', 'Select at least one payroll batch to delete.');
    }

    $runIds = [];
    foreach ($submittedIds as $submittedId) {
        $candidate = strtolower(trim((string)$submittedId));
        if (!isValidUuid($candidate)) {
            continue;
        }
        $runIds[] = $candidate;
    }
    $runIds = array_values(array_unique($runIds));

    if (empty($runIds)) {
        redirectWithState('error', 'No valid payroll batch IDs were provided for deletion.');
    }

    $runFilter = formatInFilterList($runIds);
    if ($runFilter === '') {
        redirectWithState('error', 'Unable to prepare selected payroll batches for deletion.');
    }

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id,payroll_period:payroll_periods(period_code)&id=in.(' . $runFilter . ')&limit=5000',
        $headers
    );

    if (!isSuccessful($runResponse)) {
        redirectWithState('error', 'Failed to load selected payroll batches.');
    }

    $runRows = array_values(array_filter((array)($runResponse['data'] ?? []), static function (array $row): bool {
        return isValidUuid((string)($row['id'] ?? ''));
    }));

    if (empty($runRows)) {
        redirectWithState('error', 'Selected payroll batches were not found.');
    }

    $targetIds = array_values(array_unique(array_map(static function (array $row): string {
        return strtolower((string)($row['id'] ?? ''));
    }, $runRows)));

    $deleteFilter = formatInFilterList($targetIds);
    if ($deleteFilter === '') {
        redirectWithState('error', 'Unable to prepare selected payroll batch IDs for deletion.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/payroll_runs?id=in.(' . $deleteFilter . ')',
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete selected payroll batches.');
    }

    $activityRows = [];
    foreach ($runRows as $runRow) {
        $runId = strtolower((string)($runRow['id'] ?? ''));
        $activityRows[] = [
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_runs',
            'entity_id' => $runId !== '' ? $runId : null,
            'action_name' => 'delete_batch_bulk',
            'old_data' => $runRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ];
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
    redirectWithState('success', 'Deleted ' . $deletedCount . ' payroll ' . ($deletedCount === 1 ? 'batch' : 'batches') . ' successfully.');
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

if ($action === 'delete_payroll_period_bulk') {
    $submittedIds = $_POST['period_ids'] ?? [];
    if (!is_array($submittedIds) || empty($submittedIds)) {
        redirectWithState('error', 'Select at least one payroll period to delete.');
    }

    $periodIds = [];
    foreach ($submittedIds as $submittedId) {
        $candidate = strtolower(trim((string)$submittedId));
        if (!isValidUuid($candidate)) {
            continue;
        }
        $periodIds[] = $candidate;
    }
    $periodIds = array_values(array_unique($periodIds));

    if (empty($periodIds)) {
        redirectWithState('error', 'No valid payroll period IDs were provided for deletion.');
    }

    $periodFilter = formatInFilterList($periodIds);
    if ($periodFilter === '') {
        redirectWithState('error', 'Unable to prepare selected payroll periods for deletion.');
    }

    $periodCountResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id&limit=5000',
        $headers
    );

    if (isSuccessful($periodCountResponse)) {
        $periodCount = count((array)($periodCountResponse['data'] ?? []));
        if ($periodCount <= count($periodIds)) {
            redirectWithState('error', 'Cannot delete all payroll periods. Keep at least one payroll period in the system.');
        }
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&id=in.(' . $periodFilter . ')&limit=5000',
        $headers
    );

    if (!isSuccessful($periodResponse)) {
        redirectWithState('error', 'Failed to load selected payroll periods.');
    }

    $periodRows = array_values(array_filter((array)($periodResponse['data'] ?? []), static function (array $row): bool {
        return isValidUuid((string)($row['id'] ?? ''));
    }));

    if (empty($periodRows)) {
        redirectWithState('error', 'Selected payroll periods were not found.');
    }

    $targetIds = array_values(array_unique(array_map(static function (array $row): string {
        return strtolower((string)($row['id'] ?? ''));
    }, $periodRows)));

    if (!empty($targetIds) && count($targetIds) >= 1) {
        $runsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id&payroll_period_id=in.(' . formatInFilterList($targetIds) . ')&limit=5000',
            $headers
        );

        if (!isSuccessful($runsResponse)) {
            redirectWithState('error', 'Failed to validate selected payroll period dependencies.');
        }

        $existingRuns = (array)($runsResponse['data'] ?? []);
        if (!empty($existingRuns)) {
            redirectWithState('error', 'Cannot delete selected payroll periods because one or more already have payroll batches. Delete related batches first.');
        }
    }

    $deleteFilter = formatInFilterList($targetIds);
    if ($deleteFilter === '') {
        redirectWithState('error', 'Unable to prepare selected payroll period IDs for deletion.');
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/payroll_periods?id=in.(' . $deleteFilter . ')',
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete selected payroll periods.');
    }

    $activityRows = [];
    foreach ($periodRows as $periodRow) {
        $periodId = strtolower((string)($periodRow['id'] ?? ''));
        $activityRows[] = [
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'payroll_management',
            'entity_name' => 'payroll_periods',
            'entity_id' => $periodId !== '' ? $periodId : null,
            'action_name' => 'delete_period_bulk',
            'old_data' => $periodRow,
            'new_data' => null,
            'ip_address' => clientIp(),
        ];
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
    redirectWithState('success', 'Deleted ' . $deletedCount . ' payroll ' . ($deletedCount === 1 ? 'period' : 'periods') . ' successfully.');
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

    if (trim((string)$notes) === '') {
        redirectWithState('error', 'Decision reason is required for final payroll batch approval/rejection.');
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

    if ($decision === 'approved') {
        $pendingCodes = payrollServiceFindPendingRecommendedAdjustmentCodesForRun($supabaseUrl, $headers, $runId);
        if (!empty($pendingCodes)) {
            $previewCodes = array_slice($pendingCodes, 0, 3);
            $suffix = count($pendingCodes) > 3
                ? ' +' . (count($pendingCodes) - 3) . ' more'
                : '';
            redirectWithState(
                'error',
                'Cannot approve this payroll batch yet. There are ' . count($pendingCodes)
                . ' staff-submitted salary adjustment recommendation(s) pending admin review in this batch: '
                . implode(', ', $previewCodes) . $suffix . '.'
            );
        }
    }

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

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'payroll_management',
        'payroll_runs',
        $runId,
        'review_batch',
        $oldStatus,
        $decision,
        $notes
    );

    $handoffLogResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=actor_user_id,created_at'
        . '&entity_name=eq.payroll_runs'
        . '&entity_id=eq.' . $runId
        . '&action_name=eq.submit_batch_for_admin_approval'
        . '&order=created_at.desc&limit=1',
        $headers
    );

    if (isSuccessful($handoffLogResponse)) {
        $handoffLog = (array)(($handoffLogResponse['data'] ?? [])[0] ?? []);
        $staffRecipientId = cleanText($handoffLog['actor_user_id'] ?? null) ?? '';
        if (isValidUuid($staffRecipientId)) {
            $decisionLabel = $decision === 'approved' ? 'approved' : 'returned/cancelled';
            try {
                $decisionTimestampPst = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M d, Y h:i A') . ' PST';
            } catch (Throwable $exception) {
                $decisionTimestampPst = gmdate('M d, Y h:i A') . ' UTC';
            }
            $staffDecisionBody = 'Payroll batch ' . $runId . ' has been ' . $decisionLabel . ' by Admin on ' . $decisionTimestampPst . '.';
            $notesText = trim((string)$notes);
            if ($notesText !== '') {
                $staffDecisionBody .= ' Remarks: ' . $notesText;
            }
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'recipient_user_id' => $staffRecipientId,
                    'category' => 'payroll',
                    'title' => 'Payroll Batch Reviewed by Admin',
                    'body' => $staffDecisionBody,
                    'link_url' => '/hris-system/pages/staff/payroll-management.php',
                ]]
            );
        }
    }

    redirectWithState('success', 'Payroll batch updated successfully.');
}

if ($action === 'review_salary_adjustment') {
    $adjustmentId = cleanText($_POST['adjustment_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($adjustmentId)) {
        redirectWithState('error', 'Invalid salary adjustment selected.');
    }

    if (!in_array($decision, ['approved', 'rejected'], true)) {
        redirectWithState('error', 'Invalid salary adjustment decision selected.');
    }

    $adjustmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_adjustments?select=id,adjustment_code,payroll_item_id,item:payroll_items(id,person_id,person:people(id,user_id))'
        . '&id=eq.' . $adjustmentId
        . '&limit=1',
        $headers
    );

    $adjustmentRow = $adjustmentResponse['data'][0] ?? null;
    if (!is_array($adjustmentRow)) {
        redirectWithState('error', 'Salary adjustment not found.');
    }

    $previousStatus = payrollServiceLoadLatestActivityStatus(
        $supabaseUrl,
        $headers,
        'payroll_adjustments',
        'review_payroll_adjustment',
        $adjustmentId,
        ['pending', 'approved', 'rejected'],
        ['review_status', 'status_to', 'status']
    ) ?? 'pending';

    if ($previousStatus !== 'pending') {
        redirectWithState('error', 'This salary adjustment has already been reviewed.');
    }

    logStatusTransition(
        $supabaseUrl,
        $headers,
        $adminUserId,
        'payroll_management',
        'payroll_adjustments',
        $adjustmentId,
        'review_payroll_adjustment',
        $previousStatus,
        $decision,
        $notes
    );

    $personRow = is_array($adjustmentRow['item']['person'] ?? null) ? (array)$adjustmentRow['item']['person'] : [];
    $recipientUserId = cleanText($personRow['user_id'] ?? null) ?? '';
    if (isValidUuid($recipientUserId)) {
        $adjustmentCode = cleanText($adjustmentRow['adjustment_code'] ?? null) ?? 'Salary adjustment';
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => $recipientUserId,
                'category' => 'payroll',
                'title' => 'Salary Adjustment Reviewed',
                'body' => $adjustmentCode . ' has been ' . $decision . ' by Admin.',
                'link_url' => '/hris-system/pages/employee/payroll.php',
            ]]
        );
    }

    redirectWithState('success', 'Salary adjustment marked as ' . $decision . '.');
}

if ($action === 'release_payslips') {
    $runId = cleanText($_POST['payroll_run_id'] ?? null) ?? '';
    $recipientGroup = cleanText($_POST['recipient_group'] ?? null) ?? 'all_active';
    $deliveryMode = cleanText($_POST['delivery_mode'] ?? null) ?? 'immediate';
    $releaseReason = cleanText($_POST['release_reason'] ?? null) ?? '';

    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Please select a valid payroll batch to release.');
    }

    if (trim($releaseReason) === '') {
        redirectWithState('error', 'Release reason is required for payroll send audit logging.');
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

    if ($runStatus !== 'approved') {
        redirectWithState('error', 'Only approved payroll batches can be released.');
    }

    $itemResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_items?select=id,person_id,basic_pay,overtime_pay,allowances_total,gross_pay,deductions_total,net_pay,person:people(first_name,surname,user_id)&payroll_run_id=eq.' . $runId . '&limit=5000',
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

    $smtpEncryption = strtolower(trim((string)($smtpConfig['encryption'] ?? 'tls')));
    $smtpAuthEnabled = ((string)($smtpConfig['auth'] ?? '1')) !== '0';
    if ($shouldSendEmails && !in_array($smtpEncryption, ['tls', 'starttls', 'ssl'], true)) {
        redirectWithState('error', 'Secure SMTP encryption must be TLS/STARTTLS or SSL before sending payslips.');
    }

    if ($shouldSendEmails && !$smtpAuthEnabled) {
        redirectWithState('error', 'SMTP authentication must be enabled before sending payslips.');
    }

    $recipientUserIds = [];
    foreach ($items as $item) {
        $candidateUserId = strtolower(trim((string)($item['person']['user_id'] ?? '')));
        if (isValidUuid($candidateUserId)) {
            $recipientUserIds[] = $candidateUserId;
        }
    }

    $emailAddressByUserId = $shouldSendEmails
        ? payrollServiceResolveUserEmailMap($supabaseUrl, $headers, $recipientUserIds)
        : [];

    $itemIds = [];
    foreach ($items as $item) {
        $payrollItemId = strtolower(trim((string)($item['id'] ?? '')));
        if (isValidUuid($payrollItemId)) {
            $itemIds[] = $payrollItemId;
        }
    }

    if ($itemIds === []) {
        redirectWithState('error', 'Invalid payroll item identifiers.');
    }
    $periodCode = (string)($runRow['payroll_period']['period_code'] ?? 'PR');
    $periodStartRaw = cleanText($runRow['payroll_period']['period_start'] ?? null) ?? '';
    $periodEndRaw = cleanText($runRow['payroll_period']['period_end'] ?? null) ?? '';
    $periodLabel = ($periodStartRaw !== '' && $periodEndRaw !== '')
        ? (date('M d, Y', strtotime($periodStartRaw)) . ' - ' . date('M d, Y', strtotime($periodEndRaw)))
        : strtoupper($periodCode);

    $nowIso = gmdate('c');
    $approvedAdjustmentByItemId = payrollServiceLoadApprovedAdjustmentsForItems($supabaseUrl, $headers, $itemIds);
    $itemBreakdownByItemId = payrollServiceLoadItemBreakdownByItemIds($supabaseUrl, $headers, $itemIds);

    try {
        $existingPayslips = payrollServiceEnsurePayslipRecords(
            $supabaseUrl,
            $headers,
            $itemIds,
            $periodCode,
            $runId,
            $nowIso,
            false,
            'period_code'
        );
    } catch (Throwable $throwable) {
        redirectWithState('error', $throwable->getMessage());
    }

    $documentResult = payrollServiceGeneratePayslipDocumentsForItems(
        $items,
        $existingPayslips,
        $approvedAdjustmentByItemId,
        $itemBreakdownByItemId,
        dirname(__DIR__, 4),
        $periodLabel,
        $periodCode,
        $supabaseUrl,
        $headers,
        $nowIso,
        true,
        false,
        true
    );

    $existingPayslips = (array)($documentResult['payslips_by_item_id'] ?? $existingPayslips);
    $documentByItemId = (array)($documentResult['documents_by_item_id'] ?? []);
    $documentGenerationFailed = (int)($documentResult['failed_count'] ?? 0);

    if ($documentGenerationFailed > 0) {
        redirectWithState('error', 'Failed to generate ' . $documentGenerationFailed . ' payslip document(s). Release aborted to keep payslip view and generated files consistent.');
    }

    $allPayslipIds = formatInFilterList(array_values(array_map(static fn(array $row): string => (string)($row['id'] ?? ''), array_values($existingPayslips))));
    if ($allPayslipIds !== '') {
        $releasePayslipResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payslips?id=in.(' . $allPayslipIds . ')',
            array_merge($headers, ['Prefer: return=minimal']),
            ['released_at' => $nowIso]
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
            'updated_at' => $nowIso,
            'approved_by' => $adminUserId !== '' ? $adminUserId : null,
            'approved_at' => $nowIso,
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
                'updated_at' => $nowIso,
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
        $emailResult = payrollServiceSendPayslipEmails(
            $items,
            $existingPayslips,
            $approvedAdjustmentByItemId,
            $documentByItemId,
            $emailAddressByUserId,
            $smtpConfig,
            $mailFrom,
            $mailFromName,
            $periodCode,
            $runId,
            $adminUserId,
            clientIp(),
            $supabaseUrl,
            $headers,
            'payrollMaskEmailAddress',
            'payrollSanitizeEmailError',
            $releaseReason,
            $deliveryMode
        );

        $emailsAttempted = (int)($emailResult['attempted'] ?? 0);
        $emailsSent = (int)($emailResult['sent'] ?? 0);
        $emailsFailed = (int)($emailResult['failed'] ?? 0);
        $emailErrorSamples = (array)($emailResult['error_samples'] ?? []);
        $smtpEncryption = (string)($emailResult['smtp_encryption'] ?? $smtpEncryption);
        $smtpAuthEnabled = (bool)($emailResult['smtp_auth'] ?? $smtpAuthEnabled);
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
                'release_reason' => $releaseReason,
                'released_count' => count($items),
                'email_attempted' => $emailsAttempted,
                'email_sent' => $emailsSent,
                'email_failed' => $emailsFailed,
                'email_error_samples' => $emailErrorSamples,
                'smtp_encryption' => $smtpEncryption,
                'smtp_auth' => $smtpAuthEnabled,
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
