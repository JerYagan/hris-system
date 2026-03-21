<?php

require_once __DIR__ . '/system-helpers.php';

if (!function_exists('payrollServiceFormatInFilterList')) {
    function payrollServiceFormatInFilterList(array $ids): string
    {
        $valid = [];
        foreach ($ids as $id) {
            $candidate = strtolower(trim((string)$id));
            if (!function_exists('isValidUuid') || !isValidUuid($candidate)) {
                continue;
            }

            $valid[$candidate] = true;
        }

        return implode(',', array_keys($valid));
    }
}

if (!function_exists('payrollServiceDateString')) {
    function payrollServiceDateString(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }
}

if (!function_exists('payrollServiceIsoDateFromTimestamp')) {
    function payrollServiceIsoDateFromTimestamp(?string $value): string
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

if (!function_exists('payrollServiceMaskEmailAddress')) {
    function payrollServiceMaskEmailAddress(string $email): string
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '' || !str_contains($normalized, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $normalized, 2);
        $localLength = strlen($local);
        if ($localLength <= 2) {
            $maskedLocal = str_repeat('*', max(1, $localLength));
        } else {
            $maskedLocal = substr($local, 0, 1)
                . str_repeat('*', max(1, $localLength - 2))
                . substr($local, -1);
        }

        return $maskedLocal . '@' . $domain;
    }
}

if (!function_exists('payrollServiceSanitizeEmailError')) {
    function payrollServiceSanitizeEmailError(string $raw): string
    {
        $value = trim((string)preg_replace('/\s+/', ' ', $raw));
        if ($value === '') {
            return 'SMTP send failed';
        }

        $value = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $value);
        if (!is_string($value)) {
            return 'SMTP send failed';
        }

        return mb_substr($value, 0, 240);
    }
}

if (!function_exists('payrollServiceNormalizeCompensationRow')) {
    function payrollServiceNormalizeCompensationRow(array $row): array
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

if (!function_exists('payrollServiceCompensationAppliesToPeriod')) {
    function payrollServiceCompensationAppliesToPeriod(array $row, string $periodStart, ?string $periodEnd = null): bool
    {
        $effectiveFrom = payrollServiceDateString((string)($row['effective_from'] ?? ''));
        if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
            return false;
        }

        $effectiveTo = payrollServiceDateString((string)($row['effective_to'] ?? ''));
        if ($effectiveTo !== '' && $effectiveTo < $periodStart) {
            return false;
        }

        return true;
    }
}

if (!function_exists('payrollServiceIsCosEmploymentStatus')) {
    function payrollServiceIsCosEmploymentStatus(?string $employmentStatus, ?string $positionClassification = null): bool
    {
        foreach ([$employmentStatus, $positionClassification] as $candidate) {
            $normalized = strtolower(trim((string)$candidate));
            if ($normalized === '') {
                continue;
            }

            foreach (['contract of service', 'cos', 'contractual', 'job order', 'job_order', 'casual'] as $marker) {
                if (str_contains($normalized, $marker)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('payrollServicePrepareActivePeopleFromEmploymentRows')) {
    function payrollServicePrepareActivePeopleFromEmploymentRows(array $employmentRows): array
    {
        $activeRows = [];
        $personIds = [];
        $peopleById = [];

        foreach ($employmentRows as $employmentRowRaw) {
            $employmentRow = (array)$employmentRowRaw;
            $employmentStatus = trim((string)(cleanText($employmentRow['employment_status'] ?? null) ?? ''));
            $employmentStatusKey = strtolower($employmentStatus);
            $employmentType = trim((string)(cleanText($employmentRow['employment_type'] ?? null) ?? ''));
            $positionClassification = trim((string)(cleanText($employmentRow['position']['employment_classification'] ?? null) ?? ''));
            if (in_array($employmentStatusKey, ['inactive', 'separated', 'terminated', 'resigned', 'retired'], true)) {
                continue;
            }

            $personId = strtolower(trim((string)(cleanText($employmentRow['person_id'] ?? null) ?? '')));
            if (!function_exists('isValidUuid') || !isValidUuid($personId) || isset($peopleById[$personId])) {
                continue;
            }

            $personRow = is_array($employmentRow['person'] ?? null) ? (array)$employmentRow['person'] : [];
            if (!isset($personRow['id']) || !isValidUuid((string)$personRow['id'])) {
                $personRow['id'] = $personId;
            }

            if (array_key_exists('office_id', $employmentRow)) {
                $personRow['office_id'] = $employmentRow['office_id'];
            }

            $effectiveEmploymentMarker = $employmentType !== '' ? $employmentType : $positionClassification;
            $personRow['employment_status'] = payrollServiceIsCosEmploymentStatus($employmentStatus, $effectiveEmploymentMarker) && $effectiveEmploymentMarker !== ''
                ? $effectiveEmploymentMarker
                : $employmentStatus;
            $peopleById[$personId] = $personRow;
            $personIds[] = $personId;
            $activeRows[] = $employmentRow;
        }

        return [
            'rows' => $activeRows,
            'person_ids' => $personIds,
            'people_by_id' => $peopleById,
        ];
    }
}

if (!function_exists('payrollServiceGroupCompensationRowsByPerson')) {
    function payrollServiceGroupCompensationRowsByPerson(array $compensationRows): array
    {
        $grouped = [];
        foreach ($compensationRows as $compensationRowRaw) {
            $compensationRow = payrollServiceNormalizeCompensationRow((array)$compensationRowRaw);
            $personId = strtolower(trim((string)(cleanText($compensationRow['person_id'] ?? null) ?? '')));
            if (!function_exists('isValidUuid') || !isValidUuid($personId)) {
                continue;
            }

            if (!isset($grouped[$personId])) {
                $grouped[$personId] = [];
            }

            $grouped[$personId][] = $compensationRow;
        }

        return $grouped;
    }
}

if (!function_exists('payrollServiceResolveEffectiveCompensations')) {
    function payrollServiceResolveEffectiveCompensations(array $compensationByPerson, string $periodStart, ?string $periodEnd = null): array
    {
        $resolved = [];
        foreach ($compensationByPerson as $personId => $rows) {
            foreach ((array)$rows as $candidateCompensation) {
                $candidateRow = (array)$candidateCompensation;
                if (!payrollServiceCompensationAppliesToPeriod($candidateRow, $periodStart, $periodEnd)) {
                    continue;
                }

                $resolved[(string)$personId] = $candidateRow;
                break;
            }
        }

        return $resolved;
    }
}

if (!function_exists('payrollServiceLoadAttendanceStatsForPeople')) {
    function payrollServiceLoadAttendanceStatsForPeople(
        string $supabaseUrl,
        array $headers,
        array $personIds,
        string $periodStart,
        string $periodEnd
    ): array {
        $personFilter = payrollServiceFormatInFilterList($personIds);
        if ($personFilter === '') {
            return [
                'stats_by_person_id' => [],
                'attendance_log_count' => 0,
            ];
        }

        $attendanceStatsByPersonId = [];
        $attendanceLogCount = 0;
        $pageSize = 1000;
        $offset = 0;

        while (true) {
            $attendanceResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/attendance_logs?select=person_id,attendance_date,late_minutes,undertime_hours,attendance_status'
                . '&person_id=in.' . rawurlencode('(' . $personFilter . ')')
                . '&attendance_date=gte.' . $periodStart
                . '&attendance_date=lte.' . $periodEnd
                . '&limit=' . $pageSize
                . '&offset=' . $offset,
                $headers
            );

            if (!isSuccessful($attendanceResponse)) {
                throw new RuntimeException('Failed to load attendance logs for payroll deduction computation.');
            }

            $attendanceRows = (array)($attendanceResponse['data'] ?? []);
            foreach ($attendanceRows as $attendanceRowRaw) {
                $attendanceRow = (array)$attendanceRowRaw;
                $personId = strtolower(trim((string)(cleanText($attendanceRow['person_id'] ?? null) ?? '')));
                if (!function_exists('isValidUuid') || !isValidUuid($personId)) {
                    continue;
                }

                if (!isset($attendanceStatsByPersonId[$personId])) {
                    $attendanceStatsByPersonId[$personId] = [
                        'absent_days' => 0,
                        'late_minutes' => 0,
                        'undertime_hours' => 0.0,
                    ];
                }

                $attendanceLogCount++;
                $status = strtolower(trim((string)(cleanText($attendanceRow['attendance_status'] ?? null) ?? '')));
                if ($status === 'absent') {
                    $attendanceStatsByPersonId[$personId]['absent_days']++;
                }

                $attendanceStatsByPersonId[$personId]['late_minutes'] += max(0, (int)($attendanceRow['late_minutes'] ?? 0));
                $attendanceStatsByPersonId[$personId]['undertime_hours'] += max(0.0, (float)($attendanceRow['undertime_hours'] ?? 0));
            }

            if (count($attendanceRows) < $pageSize) {
                break;
            }

            $offset += $pageSize;
        }

        return [
            'stats_by_person_id' => $attendanceStatsByPersonId,
            'attendance_log_count' => $attendanceLogCount,
        ];
    }
}

if (!function_exists('payrollServiceBuildComputedPayrollItems')) {
    function payrollServiceBuildComputedPayrollItems(
        array $peopleById,
        array $effectiveCompensationByPerson,
        array $attendanceStatsByPersonId = [],
        array $options = []
    ): array {
        $importedDeductionsByPersonId = is_array($options['imported_deductions_by_person_id'] ?? null)
            ? (array)$options['imported_deductions_by_person_id']
            : [];
        $permanentTimekeepingSource = in_array((string)($options['permanent_timekeeping_source'] ?? 'attendance'), ['attendance', 'import'], true)
            ? (string)$options['permanent_timekeeping_source']
            : 'attendance';
        $cosTimekeepingSource = in_array((string)($options['cos_timekeeping_source'] ?? 'attendance'), ['attendance', 'import'], true)
            ? (string)$options['cos_timekeeping_source']
            : 'attendance';
        $importPeriodCode = (string)($options['import_period_code'] ?? '');
        $allowImportedStatutory = (bool)($options['allow_imported_statutory'] ?? false);
        $itemPayload = [];
        $skippedPeople = 0;
        $totalTimekeepingDeductions = 0.0;
        $totalStatutoryDeductions = 0.0;

        foreach ($peopleById as $personId => $personRowRaw) {
            $personRow = (array)$personRowRaw;
            $compensationRow = is_array($effectiveCompensationByPerson[$personId] ?? null)
                ? (array)$effectiveCompensationByPerson[$personId]
                : null;
            if (!is_array($compensationRow)) {
                $skippedPeople++;
                continue;
            }

            $employmentStatus = (string)($personRow['employment_status'] ?? '');
            $isCosEmployee = payrollServiceIsCosEmploymentStatus($employmentStatus);
            $importedRow = is_array($importedDeductionsByPersonId[$personId] ?? null)
                ? (array)$importedDeductionsByPersonId[$personId]
                : [];

            $monthlyRate = (float)($compensationRow['monthly_rate'] ?? 0);
            $allowanceMonthly = max(0.0, (float)($compensationRow['allowance_total'] ?? 0));
            $basePayMonthly = isset($compensationRow['base_pay'])
                ? max(0.0, (float)$compensationRow['base_pay'])
                : max(0.0, $monthlyRate - $allowanceMonthly);
            $taxMonthly = max(0.0, (float)($compensationRow['tax_deduction'] ?? 0));
            $governmentMonthly = max(0.0, (float)($compensationRow['government_deductions'] ?? 0));
            $otherMonthly = max(0.0, (float)($compensationRow['other_deductions'] ?? 0));
            $dailyRate = max(0.0, (float)($compensationRow['daily_rate'] ?? 0));
            $hourlyRate = max(0.0, (float)($compensationRow['hourly_rate'] ?? 0));
            $payFrequency = strtolower((string)($compensationRow['pay_frequency'] ?? 'semi_monthly'));
            if ($monthlyRate <= 0) {
                $skippedPeople++;
                continue;
            }

            if ($dailyRate <= 0) {
                $dailyRate = round($monthlyRate / 22, 2);
            }
            if ($hourlyRate <= 0) {
                $hourlyRate = round($dailyRate / 8, 2);
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
            $statutoryDeductions = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);

            $attendanceStats = is_array($attendanceStatsByPersonId[$personId] ?? null)
                ? (array)$attendanceStatsByPersonId[$personId]
                : [
                    'absent_days' => 0,
                    'late_minutes' => 0,
                    'undertime_hours' => 0.0,
                ];
            $lateHours = ((float)($attendanceStats['late_minutes'] ?? 0)) / 60;
            $undertimeHours = max(0.0, (float)($attendanceStats['undertime_hours'] ?? 0));
            $absentDays = max(0, (int)($attendanceStats['absent_days'] ?? 0));
            $attendanceBasedTimekeepingDeductions = round(max(0.0, ($absentDays * $dailyRate) + (($lateHours + $undertimeHours) * $hourlyRate)), 2);

            if ($allowImportedStatutory && $importedRow !== []) {
                $importedStatutory = round(
                    max(0.0, (float)($importedRow['statutory_deductions'] ?? 0))
                    + max(0.0, (float)($importedRow['other_deductions'] ?? 0)),
                    2
                );
                if ($importedStatutory > 0) {
                    $statutoryDeductions = $importedStatutory;
                }
            }

            $timekeepingSource = $isCosEmployee ? $cosTimekeepingSource : $permanentTimekeepingSource;
            $importedTimekeepingDeductions = round(max(0.0, (float)($importedRow['timekeeping_deductions'] ?? 0)), 2);
            $timekeepingDeductions = $timekeepingSource === 'import' && $importedTimekeepingDeductions > 0
                ? $importedTimekeepingDeductions
                : $attendanceBasedTimekeepingDeductions;

            $deductionsTotal = round($statutoryDeductions + $timekeepingDeductions, 2);
            $grossPay = round($basicPay + $allowancesTotal + $overtimePay, 2);
            $netPay = round($grossPay - $deductionsTotal, 2);

            $totalStatutoryDeductions += $statutoryDeductions;
            $totalTimekeepingDeductions += $timekeepingDeductions;

            $usedImportedStatutory = $allowImportedStatutory
                && $importedRow !== []
                && ((float)($importedRow['statutory_deductions'] ?? 0) > 0 || (float)($importedRow['other_deductions'] ?? 0) > 0);
            $attendancePolicy = $timekeepingSource === 'import'
                ? 'import_sheet'
                : ($isCosEmployee ? 'payroll_deduction' : 'leave_card');

            $itemPayload[] = [
                'person_id' => $personId,
                'compensation_id' => (string)($compensationRow['id'] ?? ''),
                'basic_pay' => $basicPay,
                'overtime_pay' => $overtimePay,
                'allowances_total' => $allowancesTotal,
                'statutory_deductions' => $statutoryDeductions,
                'timekeeping_deduction' => $timekeepingDeductions,
                'deductions_total' => $deductionsTotal,
                'gross_pay' => $grossPay,
                'net_pay' => $netPay,
                'daily_rate' => $dailyRate,
                'hourly_rate' => $hourlyRate,
                'attendance_metrics' => [
                    'absent_days' => $absentDays,
                    'late_minutes' => (int)($attendanceStats['late_minutes'] ?? 0),
                    'undertime_hours' => $undertimeHours,
                    'attendance_policy' => $attendancePolicy,
                    'employment_status' => $employmentStatus,
                    'is_cos_employee' => $isCosEmployee,
                    'imported_timekeeping_deductions' => $importedTimekeepingDeductions,
                ],
                'deduction_source' => [
                    'statutory' => $usedImportedStatutory ? 'import' : 'salary_setup',
                    'timekeeping' => $timekeepingSource,
                    'import_period_code' => $importPeriodCode,
                    'import_file_match' => (string)($importedRow['source_identifier'] ?? ''),
                ],
            ];
        }

        return [
            'item_payload' => $itemPayload,
            'skipped_people' => $skippedPeople,
            'total_timekeeping_deductions' => round($totalTimekeepingDeductions, 2),
            'total_statutory_deductions' => round($totalStatutoryDeductions, 2),
        ];
    }
}

if (!function_exists('payrollServiceEnsureDirectory')) {
    function payrollServiceEnsureDirectory(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0775, true);
        }
    }
}

if (!function_exists('payrollServiceDefaultSyncConfig')) {
    function payrollServiceDefaultSyncConfig(): array
    {
        return [
            'payroll_excel_url' => '',
            'payslip_excel_url' => '',
            'google_sheet_url' => '',
            'workflow_mode' => 'excel_to_google_sheet',
            'workflow_notes' => '',
            'permanent_timekeeping_source' => 'attendance',
            'cos_timekeeping_source' => 'import',
        ];
    }
}

if (!function_exists('payrollServiceLoadImportedDeductionsForPeriod')) {
    function payrollServiceLoadImportedDeductionsForPeriod(string $supabaseUrl, array $headers, string $periodId): array
    {
        if (!function_exists('isValidUuid') || !isValidUuid($periodId)) {
            return [
                'rows_by_person_id' => [],
                'summary' => null,
            ];
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=new_data,created_at'
            . '&entity_name=eq.payroll_deduction_imports'
            . '&entity_id=eq.' . rawurlencode($periodId)
            . '&action_name=eq.import_deduction_workbook'
            . '&order=created_at.desc&limit=1',
            $headers
        );

        if (!isSuccessful($response)) {
            return [
                'rows_by_person_id' => [],
                'summary' => null,
            ];
        }

        $row = $response['data'][0] ?? null;
        $newData = is_array($row['new_data'] ?? null) ? (array)$row['new_data'] : [];
        $importRows = is_array($newData['rows'] ?? null) ? (array)$newData['rows'] : [];

        $rowsByPersonId = [];
        foreach ($importRows as $importRowRaw) {
            $importRow = (array)$importRowRaw;
            $personId = strtolower(trim((string)($importRow['person_id'] ?? '')));
            if (!isValidUuid($personId)) {
                continue;
            }

            $rowsByPersonId[$personId] = [
                'statutory_deductions' => round(max(0.0, (float)($importRow['statutory_deductions'] ?? 0)), 2),
                'timekeeping_deductions' => round(max(0.0, (float)($importRow['timekeeping_deductions'] ?? 0)), 2),
                'other_deductions' => round(max(0.0, (float)($importRow['other_deductions'] ?? 0)), 2),
                'notes' => trim((string)($importRow['notes'] ?? '')),
                'source_identifier' => trim((string)($importRow['source_identifier'] ?? '')),
            ];
        }

        return [
            'rows_by_person_id' => $rowsByPersonId,
            'summary' => [
                'created_at' => (string)($row['created_at'] ?? ''),
                'imported_rows' => (int)($newData['imported_rows'] ?? count($rowsByPersonId)),
                'matched_rows' => (int)($newData['matched_rows'] ?? count($rowsByPersonId)),
                'unmatched_rows' => (int)($newData['unmatched_rows'] ?? 0),
                'file_name' => (string)($newData['file_name'] ?? ''),
            ],
        ];
    }
}

if (!function_exists('payrollServiceLoadLatestActivityStatus')) {
    function payrollServiceLoadLatestActivityStatus(
        string $supabaseUrl,
        array $headers,
        string $entityName,
        string $actionName,
        string $entityId,
        array $validStatuses,
        array $candidateFields
    ): ?string {
        if (!function_exists('isValidUuid') || !isValidUuid($entityId)) {
            return null;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=new_data,created_at'
            . '&entity_name=eq.' . rawurlencode($entityName)
            . '&action_name=eq.' . rawurlencode($actionName)
            . '&entity_id=eq.' . rawurlencode($entityId)
            . '&order=created_at.desc&limit=1',
            $headers
        );

        if (!isSuccessful($response) || empty((array)($response['data'] ?? []))) {
            return null;
        }

        $row = (array)$response['data'][0];
        $newData = is_array($row['new_data'] ?? null) ? (array)$row['new_data'] : [];
        foreach ($candidateFields as $field) {
            $candidate = strtolower(trim((string)(cleanText($newData[$field] ?? null) ?? '')));
            if (in_array($candidate, $validStatuses, true)) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('payrollServiceNotifyRoleAssignments')) {
    function payrollServiceNotifyRoleAssignments(
        string $supabaseUrl,
        array $headers,
        string $roleKey,
        string $category,
        string $title,
        string $body,
        string $linkUrl
    ): int {
        $assignmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=user_id,expires_at,role:roles!inner(role_key)'
            . '&role.role_key=eq.' . rawurlencode($roleKey)
            . '&limit=1000',
            $headers
        );

        if (!isSuccessful($assignmentResponse)) {
            return 0;
        }

        $nowTimestamp = time();
        $notifications = [];
        foreach ((array)($assignmentResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null) ?? '';
            if (!isValidUuid($userId)) {
                continue;
            }

            $expiresAt = cleanText($assignmentRow['expires_at'] ?? null);
            if ($expiresAt !== null) {
                $expiryTimestamp = strtotime($expiresAt);
                if ($expiryTimestamp !== false && $expiryTimestamp <= $nowTimestamp) {
                    continue;
                }
            }

            $notifications[] = [
                'recipient_user_id' => $userId,
                'category' => $category,
                'title' => $title,
                'body' => $body,
                'link_url' => $linkUrl,
            ];
        }

        if ($notifications === []) {
            return 0;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            $notifications
        );

        return count($notifications);
    }
}

if (!function_exists('payrollServiceFindPendingRecommendedAdjustmentCodesForRun')) {
    function payrollServiceFindPendingRecommendedAdjustmentCodesForRun(string $supabaseUrl, array $headers, string $runId): array
    {
        if (!function_exists('isValidUuid') || !isValidUuid($runId)) {
            return [];
        }

        $itemResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payroll_items?select=id'
            . '&payroll_run_id=eq.' . rawurlencode($runId)
            . '&limit=5000',
            $headers
        );

        if (!isSuccessful($itemResponse)) {
            throw new RuntimeException('Unable to validate salary adjustment reviews for this payroll batch. Please try again.');
        }

        $itemIds = [];
        foreach ((array)($itemResponse['data'] ?? []) as $itemRow) {
            $itemId = cleanText($itemRow['id'] ?? null) ?? '';
            if (isValidUuid($itemId)) {
                $itemIds[] = $itemId;
            }
        }

        if ($itemIds === []) {
            return [];
        }

        $itemIdFilter = payrollServiceFormatInFilterList($itemIds);
        $adjustmentResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payroll_adjustments?select=id,adjustment_code,payroll_item_id'
            . '&payroll_item_id=in.' . rawurlencode('(' . $itemIdFilter . ')')
            . '&limit=5000',
            $headers
        );

        if (!isSuccessful($adjustmentResponse)) {
            throw new RuntimeException('Unable to validate salary adjustment reviews for this payroll batch. Please try again.');
        }

        $adjustmentCodesById = [];
        foreach ((array)($adjustmentResponse['data'] ?? []) as $adjustmentRow) {
            $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
            if (!isValidUuid($adjustmentId)) {
                continue;
            }

            $adjustmentCodesById[$adjustmentId] = cleanText($adjustmentRow['adjustment_code'] ?? null) ?? 'Adjustment';
        }

        if ($adjustmentCodesById === []) {
            return [];
        }

        $adjustmentIdFilter = payrollServiceFormatInFilterList(array_keys($adjustmentCodesById));
        $recommendationResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.payroll_adjustments'
            . '&action_name=eq.recommend_payroll_adjustment'
            . '&entity_id=in.' . rawurlencode('(' . $adjustmentIdFilter . ')')
            . '&order=created_at.desc&limit=10000',
            $headers
        );

        if (!isSuccessful($recommendationResponse)) {
            throw new RuntimeException('Unable to validate salary adjustment reviews for this payroll batch. Please try again.');
        }

        $recommendedAdjustmentIds = [];
        foreach ((array)($recommendationResponse['data'] ?? []) as $recommendationRow) {
            $entityId = cleanText($recommendationRow['entity_id'] ?? null) ?? '';
            if (!isValidUuid($entityId) || isset($recommendedAdjustmentIds[$entityId])) {
                continue;
            }

            $newData = is_array($recommendationRow['new_data'] ?? null) ? (array)$recommendationRow['new_data'] : [];
            $recommendationStatus = strtolower((string)(cleanText($newData['recommendation_status'] ?? null) ?? ''));
            if (!in_array($recommendationStatus, ['approved', 'rejected'], true)) {
                continue;
            }

            $recommendedAdjustmentIds[$entityId] = true;
        }

        if ($recommendedAdjustmentIds === []) {
            return [];
        }

        $reviewIdFilter = payrollServiceFormatInFilterList(array_keys($recommendedAdjustmentIds));
        $reviewResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.payroll_adjustments'
            . '&action_name=eq.review_payroll_adjustment'
            . '&entity_id=in.' . rawurlencode('(' . $reviewIdFilter . ')')
            . '&order=created_at.desc&limit=10000',
            $headers
        );

        if (!isSuccessful($reviewResponse)) {
            throw new RuntimeException('Unable to validate salary adjustment reviews for this payroll batch. Please try again.');
        }

        $reviewStatusByAdjustmentId = [];
        foreach ((array)($reviewResponse['data'] ?? []) as $reviewRow) {
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

        $pendingCodes = [];
        foreach (array_keys($recommendedAdjustmentIds) as $adjustmentId) {
            $reviewStatus = strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending'));
            if ($reviewStatus === 'pending') {
                $pendingCodes[] = (string)($adjustmentCodesById[$adjustmentId] ?? 'Adjustment');
            }
        }

        return $pendingCodes;
    }
}

if (!function_exists('payrollServiceEnsureEmailHelpersLoaded')) {
    function payrollServiceEnsureEmailHelpersLoaded(): void
    {
        if (function_exists('hrisEmailDecorateHtml') && function_exists('hrisEmailBuildPlainText')) {
            return;
        }

        $emailHelperPath = systemProjectRoot() . '/pages/admin/includes/notifications/email.php';
        if (is_file($emailHelperPath)) {
            require_once $emailHelperPath;
        }
    }
}

if (!function_exists('payrollServiceSendEmailWithAttachment')) {
    function payrollServiceSendEmailWithAttachment(
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
        payrollServiceEnsureEmailHelpersLoaded();

        if (function_exists('adminMailEnsureAutoload')) {
            adminMailEnsureAutoload();
        }

        if ($attachmentPath === '' || !is_file($attachmentPath) || !is_readable($attachmentPath)) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => 'Payslip attachment file is missing or unreadable.',
            ];
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => 'PHPMailer dependency is not available. Run composer install to enable payslip attachments.',
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

            $renderedHtmlContent = function_exists('hrisEmailDecorateHtml')
                ? hrisEmailDecorateHtml($subject, $htmlContent, $fromName)
                : $htmlContent;
            $plainTextContent = function_exists('hrisEmailBuildPlainText')
                ? hrisEmailBuildPlainText($renderedHtmlContent)
                : trim(strip_tags($renderedHtmlContent));

            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom($fromEmail, $fromName !== '' ? $fromName : $fromEmail);
            $mailer->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $renderedHtmlContent;
            $mailer->AltBody = $plainTextContent;
            $mailer->addAttachment($attachmentPath, $attachmentName !== '' ? $attachmentName : basename($attachmentPath));
            $mailer->send();

            return [
                'status' => 200,
                'data' => ['provider' => 'smtp'],
                'raw' => 'SMTP send success',
            ];
        } catch (\Throwable $error) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => $error->getMessage(),
            ];
        }
    }
}

if (!function_exists('payrollServiceGeneratePayslipDocument')) {
    function payrollServiceGeneratePayslipDocument(array $payload): array
    {
        payrollServiceEnsureEmailHelpersLoaded();

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
        $ctoPay = (float)($payload['cto_pay'] ?? $payload['overtime_pay'] ?? 0);
        $allowancesTotal = (float)($payload['allowances_total'] ?? 0);
        $statutoryDeductions = (float)($payload['statutory_deductions'] ?? 0);
        $timekeepingDeductions = (float)($payload['timekeeping_deductions'] ?? 0);
        $adjustmentDeductions = (float)($payload['adjustment_deductions'] ?? 0);
        $adjustmentEarnings = (float)($payload['adjustment_earnings'] ?? 0);
        $lateMinutes = (int)($payload['late_minutes'] ?? 0);
        $undertimeHours = (float)($payload['undertime_hours'] ?? 0);
        $absentDays = (int)($payload['absent_days'] ?? 0);
        $earningsLines = is_array($payload['earnings_lines'] ?? null) ? (array)$payload['earnings_lines'] : [];
        $deductionLines = is_array($payload['deduction_lines'] ?? null) ? (array)$payload['deduction_lines'] : [];
        $generatedAt = function_exists('hrisEmailFormatPhilippinesDateTime')
            ? hrisEmailFormatPhilippinesDateTime(gmdate('c'))
            : gmdate('Y-m-d H:i:s');

        $exportsDir = $projectRoot . '/storage/payslips';
        payrollServiceEnsureDirectory($exportsDir);

        $baseFileName = strtolower((string)preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $payslipNo));
        if ($baseFileName === '' || $baseFileName === '-') {
            $baseFileName = 'payslip-' . gmdate('Ymd-His') . '-' . substr(bin2hex(random_bytes(6)), 0, 12);
        }

        if ($earningsLines === []) {
            $earningsLines = [
                ['label' => 'Basic Pay', 'amount' => $basicPay],
                ['label' => 'CTO Leave UT w/ Pay', 'amount' => $ctoPay],
                ['label' => 'Allowances', 'amount' => $allowancesTotal],
            ];
            if ($adjustmentEarnings > 0) {
                $earningsLines[] = ['label' => 'Approved Adjustment Earnings', 'amount' => $adjustmentEarnings];
            }
        }

        if ($deductionLines === []) {
            $deductionLines = [
                ['label' => 'Withholding Tax', 'amount' => $statutoryDeductions],
                ['label' => 'Timekeeping Deductions', 'amount' => $timekeepingDeductions],
                ['label' => 'Adjustment Deductions', 'amount' => $adjustmentDeductions],
            ];
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
            . '<table width="100%" cellspacing="0" cellpadding="8" border="1" style="border-collapse: collapse; margin-top: 12px; font-family: Arial, sans-serif; font-size: 12px;">'
            . $renderRows($deductionLines)
            . '<tr><th style="text-align:left; background:#f8fafc;">Total Deductions</th><td><strong>PHP ' . number_format($deductionsTotal, 2) . '</strong></td></tr>'
            . '<tr><th style="text-align:left; background:#f8fafc;">Net Pay</th><td><strong>PHP ' . number_format($netPay, 2) . '</strong></td></tr>'
            . '</table>';

        if ($absentDays > 0 || $lateMinutes > 0 || $undertimeHours > 0) {
            $html .= '<p style="font-family: Arial, sans-serif; font-size: 11px; color: #334155; margin-top: 8px;">'
                . 'Attendance source: ' . (string)$absentDays . ' absent day(s), ' . (string)$lateMinutes . ' late minute(s), '
                . number_format($undertimeHours, 2) . ' undertime hour(s).</p>';
        }

        $html .= '<p style="font-family: Arial, sans-serif; font-size: 11px; color: #64748b; margin-top: 12px;">System generated on ' . htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') . '.</p>';

        $autoloadPath = $projectRoot . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            throw new RuntimeException('Dompdf dependency is not available. Run composer install to enable PDF payslip generation.');
        }

        $absolutePath = $exportsDir . '/' . $baseFileName . '.pdf';
        $dompdfClass = 'Dompdf\\Dompdf';
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($absolutePath, $dompdf->output());

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new RuntimeException('Generated payslip PDF is missing or unreadable after rendering.');
        }

        return [
            'absolute_path' => $absolutePath,
            'storage_path' => systemAppPath('/storage/payslips/' . basename($absolutePath)),
        ];
    }
}

if (!function_exists('payrollServiceLoadApprovedAdjustmentsForItems')) {
    function payrollServiceLoadApprovedAdjustmentsForItems(string $supabaseUrl, array $headers, array $itemIds): array
    {
        $itemIdFilter = payrollServiceFormatInFilterList($itemIds);
        if ($itemIdFilter === '') {
            return [];
        }

        $adjustmentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,amount'
            . '&payroll_item_id=in.' . rawurlencode('(' . $itemIdFilter . ')')
            . '&limit=10000',
            $headers
        );

        if (!isSuccessful($adjustmentsResponse)) {
            return [];
        }

        $adjustmentRows = (array)($adjustmentsResponse['data'] ?? []);
        $adjustmentIds = [];
        foreach ($adjustmentRows as $adjustmentRow) {
            $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
            if (isValidUuid($adjustmentId)) {
                $adjustmentIds[] = $adjustmentId;
            }
        }

        $reviewStatusByAdjustmentId = [];
        $adjustmentIdFilter = payrollServiceFormatInFilterList($adjustmentIds);
        if ($adjustmentIdFilter !== '') {
            $adjustmentReviewResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                . '&entity_name=eq.payroll_adjustments'
                . '&action_name=eq.review_payroll_adjustment'
                . '&entity_id=in.' . rawurlencode('(' . $adjustmentIdFilter . ')')
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

        $approvedAdjustmentByItemId = [];
        foreach ($adjustmentRows as $adjustmentRow) {
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

        return $approvedAdjustmentByItemId;
    }
}

if (!function_exists('payrollServiceLoadItemBreakdownByItemIds')) {
    function payrollServiceLoadItemBreakdownByItemIds(string $supabaseUrl, array $headers, array $itemIds): array
    {
        $itemIdFilter = payrollServiceFormatInFilterList($itemIds);
        if ($itemIdFilter === '') {
            return [];
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.payroll_items'
            . '&action_name=eq.compute_item_breakdown'
            . '&entity_id=in.' . rawurlencode('(' . $itemIdFilter . ')')
            . '&order=created_at.desc&limit=10000',
            $headers
        );

        if (!isSuccessful($response)) {
            return [];
        }

        $breakdownByItemId = [];
        foreach ((array)($response['data'] ?? []) as $logRow) {
            $itemId = cleanText($logRow['entity_id'] ?? null) ?? '';
            if (!isValidUuid($itemId) || isset($breakdownByItemId[$itemId])) {
                continue;
            }

            $newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];
            $earnings = is_array($newData['earnings'] ?? null) ? (array)$newData['earnings'] : [];
            $deductions = is_array($newData['deductions'] ?? null) ? (array)$newData['deductions'] : [];
            $attendanceSource = is_array($newData['attendance_source'] ?? null) ? (array)$newData['attendance_source'] : [];

            $breakdownByItemId[$itemId] = [
                'basic_pay' => (float)($earnings['basic_pay'] ?? 0),
                'cto_pay' => (float)($earnings['cto_pay'] ?? 0),
                'allowances_total' => (float)($earnings['allowances_total'] ?? 0),
                'statutory_deductions' => (float)($deductions['statutory_deductions'] ?? 0),
                'timekeeping_deductions' => (float)($deductions['timekeeping_deductions'] ?? 0),
                'adjustment_deductions' => (float)($deductions['adjustment_deductions'] ?? 0),
                'adjustment_earnings' => (float)($deductions['adjustment_earnings'] ?? 0),
                'absent_days' => (int)($attendanceSource['absent_days'] ?? 0),
                'late_minutes' => (int)($attendanceSource['late_minutes'] ?? 0),
                'undertime_hours' => (float)($attendanceSource['undertime_hours'] ?? 0),
            ];
        }

        return $breakdownByItemId;
    }
}

if (!function_exists('payrollServiceComputeAdjustedFigures')) {
    function payrollServiceComputeAdjustedFigures(array $itemRow, array $approvedAdjustmentByItemId): array
    {
        $itemId = cleanText($itemRow['id'] ?? null) ?? '';
        $adjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
            ? (array)$approvedAdjustmentByItemId[$itemId]
            : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];

        $adjustmentEarnings = (float)($adjustment['adjustment_earnings'] ?? 0);
        $adjustmentDeductions = (float)($adjustment['adjustment_deductions'] ?? 0);

        return [
            'adjustment_earnings' => $adjustmentEarnings,
            'adjustment_deductions' => $adjustmentDeductions,
            'gross_pay' => (float)($itemRow['gross_pay'] ?? 0) + $adjustmentEarnings,
            'deductions_total' => (float)($itemRow['deductions_total'] ?? 0) + $adjustmentDeductions,
            'net_pay' => (float)($itemRow['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions,
        ];
    }
}

if (!function_exists('payrollServiceResolveUserEmailMap')) {
    function payrollServiceResolveUserEmailMap(string $supabaseUrl, array $headers, array $userIds): array
    {
        $userFilter = payrollServiceFormatInFilterList($userIds);
        if ($userFilter === '') {
            return [];
        }

        $usersResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . $userFilter . ')&limit=5000',
            $headers
        );

        if (!isSuccessful($usersResponse)) {
            return [];
        }

        $emailAddressByUserId = [];
        foreach ((array)($usersResponse['data'] ?? []) as $userRow) {
            $userId = strtolower(trim((string)($userRow['id'] ?? '')));
            $email = strtolower(trim((string)($userRow['email'] ?? '')));
            if ($userId !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailAddressByUserId[$userId] = $email;
            }
        }

        return $emailAddressByUserId;
    }
}

if (!function_exists('payrollServiceEnsurePayslipRecords')) {
    function payrollServiceEnsurePayslipRecords(
        string $supabaseUrl,
        array $headers,
        array $itemIds,
        string $periodCode,
        string $runId,
        string $timestamp,
        bool $markReleased,
        string $numberingMode = 'run_short'
    ): array {
        $normalizedItemIds = [];
        foreach ($itemIds as $itemIdRaw) {
            $itemId = strtolower(trim((string)$itemIdRaw));
            if (isValidUuid($itemId)) {
                $normalizedItemIds[] = $itemId;
            }
        }

        $itemFilter = payrollServiceFormatInFilterList($normalizedItemIds);
        if ($itemFilter === '') {
            throw new RuntimeException('Invalid payroll item identifiers.');
        }

        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,released_at,pdf_storage_path'
            . '&payroll_item_id=in.(' . $itemFilter . ')&limit=5000',
            $headers
        );

        if (!isSuccessful($existingResponse)) {
            throw new RuntimeException('Failed to load existing payslip records.');
        }

        $existingByItemId = [];
        foreach ((array)($existingResponse['data'] ?? []) as $row) {
            $itemId = strtolower(trim((string)($row['payroll_item_id'] ?? '')));
            if (isValidUuid($itemId)) {
                $existingByItemId[$itemId] = (array)$row;
            }
        }

        $runShort = strtoupper(substr(str_replace('-', '', $runId), 0, 8));
        $dateStamp = gmdate('Ymd', strtotime($timestamp) ?: time());
        $insertPayload = [];
        foreach ($normalizedItemIds as $itemId) {
            if (!isset($existingByItemId[$itemId])) {
                $itemShort = strtoupper(substr(str_replace('-', '', $itemId), 0, 8));
                $payslipNo = $numberingMode === 'period_code'
                    ? strtoupper($periodCode) . '-' . $dateStamp . '-' . $itemShort
                    : 'PS-' . $runShort . '-' . $itemShort;

                $row = [
                    'payroll_item_id' => $itemId,
                    'payslip_no' => $payslipNo,
                ];
                if ($markReleased) {
                    $row['released_at'] = $timestamp;
                }
                $insertPayload[] = $row;
                continue;
            }

            if ($markReleased && cleanText($existingByItemId[$itemId]['released_at'] ?? null) === null) {
                apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/payslips?payroll_item_id=eq.' . rawurlencode($itemId),
                    array_merge($headers, ['Prefer: return=minimal']),
                    ['released_at' => $timestamp]
                );
            }
        }

        if ($insertPayload !== []) {
            $insertResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/payslips',
                array_merge($headers, ['Prefer: return=minimal']),
                $insertPayload
            );

            if (!isSuccessful($insertResponse)) {
                throw new RuntimeException('Failed to create payslip records.');
            }
        }

        $currentPayslipResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,released_at,pdf_storage_path'
            . '&payroll_item_id=in.(' . $itemFilter . ')&limit=5000',
            $headers
        );

        if (!isSuccessful($currentPayslipResponse)) {
            throw new RuntimeException('Payslips were created but could not be reloaded for document generation.');
        }

        $resolvedByItemId = [];
        foreach ((array)($currentPayslipResponse['data'] ?? []) as $row) {
            $itemId = strtolower(trim((string)(cleanText($row['payroll_item_id'] ?? null) ?? '')));
            if (isValidUuid($itemId)) {
                $resolvedByItemId[$itemId] = (array)$row;
            }
        }

        $missingPayslipItemIds = array_values(array_diff($normalizedItemIds, array_keys($resolvedByItemId)));
        if ($missingPayslipItemIds !== []) {
            throw new RuntimeException('Failed to prepare payslip records for ' . count($missingPayslipItemIds) . ' payroll item(s).');
        }

        return $resolvedByItemId;
    }
}

if (!function_exists('payrollServiceGeneratePayslipDocumentsForItems')) {
    function payrollServiceGeneratePayslipDocumentsForItems(
        array $itemRows,
        array $payslipsByItemId,
        array $approvedAdjustmentByItemId,
        array $breakdownByItemId,
        string $projectRoot,
        string $periodLabel,
        string $periodCode,
        string $supabaseUrl,
        array $headers,
        string $timestamp,
        bool $includeDetailedDeductionLines,
        bool $markReleased,
        bool $continueOnError
    ): array {
        $documentsByItemId = [];
        $resolvedPayslipsByItemId = $payslipsByItemId;
        $failedCount = 0;
        $lastError = null;

        foreach ($itemRows as $itemRowRaw) {
            $itemRow = (array)$itemRowRaw;
            $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
            if (!isValidUuid($itemId) || !isset($resolvedPayslipsByItemId[$itemId])) {
                continue;
            }

            $payslipRow = (array)$resolvedPayslipsByItemId[$itemId];
            $payslipId = (string)($payslipRow['id'] ?? '');
            if (!isValidUuid($payslipId)) {
                continue;
            }

            $person = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
            $employeeName = trim(implode(' ', array_filter([
                cleanText($person['first_name'] ?? null),
                cleanText($person['middle_name'] ?? null),
                cleanText($person['surname'] ?? null),
            ])));
            if ($employeeName === '') {
                $employeeName = 'Employee';
            }

            $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? strtoupper($periodCode);
            $breakdown = is_array($breakdownByItemId[$itemId] ?? null) ? (array)$breakdownByItemId[$itemId] : [];
            $adjustedFigures = payrollServiceComputeAdjustedFigures($itemRow, $approvedAdjustmentByItemId);
            $statutoryDeductions = (float)($breakdown['statutory_deductions'] ?? 0);
            $timekeepingDeductions = (float)($breakdown['timekeeping_deductions'] ?? 0);
            $adjustmentDeductions = (float)($adjustedFigures['adjustment_deductions'] ?? 0);
            $adjustmentEarnings = (float)($adjustedFigures['adjustment_earnings'] ?? 0);
            if (!isset($breakdownByItemId[$itemId])) {
                $statutoryDeductions = max(0.0, (float)($itemRow['deductions_total'] ?? 0));
                $timekeepingDeductions = 0.0;
            }

            try {
                $document = payrollServiceGeneratePayslipDocument([
                    'project_root' => $projectRoot,
                    'payslip_no' => $payslipNo,
                    'employee_name' => $employeeName,
                    'period_label' => $periodLabel,
                    'basic_pay' => (float)($itemRow['basic_pay'] ?? 0),
                    'overtime_pay' => (float)($itemRow['overtime_pay'] ?? 0),
                    'cto_pay' => (float)($itemRow['overtime_pay'] ?? 0),
                    'allowances_total' => (float)($itemRow['allowances_total'] ?? 0),
                    'statutory_deductions' => $statutoryDeductions,
                    'timekeeping_deductions' => $timekeepingDeductions,
                    'adjustment_deductions' => $adjustmentDeductions,
                    'adjustment_earnings' => $adjustmentEarnings,
                    'absent_days' => (int)($breakdown['absent_days'] ?? 0),
                    'late_minutes' => (int)($breakdown['late_minutes'] ?? 0),
                    'undertime_hours' => (float)($breakdown['undertime_hours'] ?? 0),
                    'gross_pay' => (float)($adjustedFigures['gross_pay'] ?? 0),
                    'deductions_total' => (float)($adjustedFigures['deductions_total'] ?? 0),
                    'net_pay' => (float)($adjustedFigures['net_pay'] ?? 0),
                    'earnings_lines' => [
                        ['label' => 'Basic Pay', 'amount' => (float)($itemRow['basic_pay'] ?? 0)],
                        ['label' => 'CTO Leave UT w/ Pay', 'amount' => (float)($itemRow['overtime_pay'] ?? 0)],
                        ['label' => 'Allowances', 'amount' => (float)($itemRow['allowances_total'] ?? 0)],
                        ['label' => 'Approved Adjustment Earnings', 'amount' => $adjustmentEarnings],
                    ],
                    'deduction_lines' => $includeDetailedDeductionLines
                        ? [
                            ['label' => 'Statutory / Government Contributions (SSS/Pag-IBIG/PhilHealth)', 'amount' => $statutoryDeductions],
                            ['label' => 'Timekeeping Deductions', 'amount' => $timekeepingDeductions],
                            ['label' => 'Adjustment Deductions', 'amount' => $adjustmentDeductions],
                        ]
                        : [
                            ['label' => 'Government Contributions (SSS/Pag-IBIG/PhilHealth) and Other Deductions', 'amount' => (float)($itemRow['deductions_total'] ?? 0)],
                            ['label' => 'Approved Adjustment Deductions', 'amount' => $adjustmentDeductions],
                        ],
                ]);

                $storagePath = cleanText($document['storage_path'] ?? null);
                $absolutePath = cleanText($document['absolute_path'] ?? null);
                $patchData = [];
                if ($markReleased) {
                    $patchData['released_at'] = $timestamp;
                }
                if ($storagePath !== null && $storagePath !== '') {
                    $patchData['pdf_storage_path'] = $storagePath;
                }
                if ($patchData === []) {
                    throw new RuntimeException('Generated payslip output is missing storage metadata.');
                }

                $patchResponse = apiRequest(
                    'PATCH',
                    $supabaseUrl . '/rest/v1/payslips?id=eq.' . $payslipId,
                    array_merge($headers, ['Prefer: return=minimal']),
                    $patchData
                );

                if (!isSuccessful($patchResponse)) {
                    throw new RuntimeException('Failed to save generated payslip PDF path to payslip record.');
                }

                if ($markReleased) {
                    $resolvedPayslipsByItemId[$itemId]['released_at'] = $timestamp;
                }
                if ($storagePath !== null && $storagePath !== '') {
                    $resolvedPayslipsByItemId[$itemId]['pdf_storage_path'] = $storagePath;
                }

                $documentsByItemId[$itemId] = [
                    'absolute_path' => $absolutePath !== null ? $absolutePath : '',
                    'storage_path' => $storagePath !== null ? $storagePath : '',
                ];
            } catch (Throwable $throwable) {
                $failedCount++;
                $lastError = $throwable->getMessage();
                if (!$continueOnError) {
                    throw new RuntimeException('Failed to generate payslip document: ' . $throwable->getMessage());
                }
            }
        }

        return [
            'documents_by_item_id' => $documentsByItemId,
            'payslips_by_item_id' => $resolvedPayslipsByItemId,
            'failed_count' => $failedCount,
            'last_error' => $lastError,
        ];
    }
}

if (!function_exists('payrollServiceSendPayslipEmails')) {
    function payrollServiceSendPayslipEmails(
        array $itemRows,
        array $payslipsByItemId,
        array $approvedAdjustmentByItemId,
        array $documentByItemId,
        array $emailAddressByUserId,
        array $smtpConfig,
        string $mailFrom,
        string $mailFromName,
        string $periodCode,
        string $runId,
        string $actorUserId,
        string $ipAddress,
        string $supabaseUrl,
        array $headers,
        string $maskFunction,
        string $sanitizeErrorFunction,
        string $releaseReason = '',
        string $deliveryMode = 'immediate'
    ): array {
        $smtpEncryption = strtolower(trim((string)($smtpConfig['encryption'] ?? 'tls')));
        $smtpAuthEnabled = ((string)($smtpConfig['auth'] ?? '1')) !== '0';
        $emailsAttempted = 0;
        $emailsSent = 0;
        $emailsFailed = 0;
        $emailErrorSamples = [];
        $emailAttemptLogs = [];

        $flushLogs = static function (array $logs) use ($supabaseUrl, $headers): void {
            if ($logs === []) {
                return;
            }

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/activity_logs',
                array_merge($headers, ['Prefer: return=minimal']),
                $logs
            );
        };

        foreach ($itemRows as $itemRowRaw) {
            $itemRow = (array)$itemRowRaw;
            $person = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
            $recipientUserId = strtolower(trim((string)($person['user_id'] ?? '')));
            if (!isValidUuid($recipientUserId)) {
                continue;
            }

            $recipientEmail = (string)($emailAddressByUserId[$recipientUserId] ?? '');
            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $payrollItemId = strtolower(trim((string)($itemRow['id'] ?? '')));
            $payslipRow = is_array($payslipsByItemId[$payrollItemId] ?? null) ? (array)$payslipsByItemId[$payrollItemId] : [];
            $payslipNo = cleanText($payslipRow['payslip_no'] ?? null) ?? strtoupper($periodCode);
            $payslipId = strtolower(trim((string)($payslipRow['id'] ?? '')));
            if (!isValidUuid($payslipId)) {
                continue;
            }

            $employeeName = trim(implode(' ', array_filter([
                cleanText($person['first_name'] ?? null),
                cleanText($person['middle_name'] ?? null),
                cleanText($person['surname'] ?? null),
            ])));
            if ($employeeName === '') {
                $employeeName = 'Employee';
            }

            $emailsAttempted++;
            $adjustedFigures = payrollServiceComputeAdjustedFigures($itemRow, $approvedAdjustmentByItemId);
            $subject = 'Payslip Released - ' . strtoupper($periodCode);
            $html = '<p>Hi ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your payslip for payroll period <strong>' . htmlspecialchars(strtoupper($periodCode), ENT_QUOTES, 'UTF-8') . '</strong> is now released.</p>'
                . '<p>Payslip No: <strong>' . htmlspecialchars($payslipNo, ENT_QUOTES, 'UTF-8') . '</strong><br>'
                . 'Net Pay: <strong>PHP ' . number_format((float)($adjustedFigures['net_pay'] ?? 0), 2) . '</strong></p>'
                . '<p>You may view details in your employee payroll page.</p>';

            $attachmentPath = cleanText($documentByItemId[$payrollItemId]['absolute_path'] ?? null) ?? '';
            $attachmentName = ($payslipNo !== '' ? $payslipNo : 'payslip') . '.pdf';
            $emailResponse = payrollServiceSendEmailWithAttachment(
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

            $maskedRecipient = function_exists($maskFunction)
                ? $maskFunction($recipientEmail)
                : payrollServiceMaskEmailAddress($recipientEmail);

            if (isSuccessful($emailResponse)) {
                $emailsSent++;
                $emailAttemptLogs[] = [
                    'actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
                    'module_name' => 'payroll_management',
                    'entity_name' => 'payslips',
                    'entity_id' => $payslipId,
                    'action_name' => 'send_payslip_email_attempt',
                    'old_data' => null,
                    'new_data' => [
                        'payroll_run_id' => $runId,
                        'payroll_item_id' => $payrollItemId,
                        'payslip_no' => $payslipNo,
                        'delivery_mode' => $deliveryMode,
                        'release_reason' => $releaseReason,
                        'recipient_masked' => $maskedRecipient,
                        'status' => 'sent',
                        'smtp_encryption' => $smtpEncryption,
                        'smtp_auth' => $smtpAuthEnabled,
                    ],
                    'ip_address' => $ipAddress,
                ];
            } else {
                $emailsFailed++;
                $sanitizedError = function_exists($sanitizeErrorFunction)
                    ? $sanitizeErrorFunction((string)($emailResponse['raw'] ?? 'SMTP send failed'))
                    : payrollServiceSanitizeEmailError((string)($emailResponse['raw'] ?? 'SMTP send failed'));
                if (count($emailErrorSamples) < 3) {
                    $emailErrorSamples[] = $sanitizedError;
                }

                $emailAttemptLogs[] = [
                    'actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
                    'module_name' => 'payroll_management',
                    'entity_name' => 'payslips',
                    'entity_id' => $payslipId,
                    'action_name' => 'send_payslip_email_attempt',
                    'old_data' => null,
                    'new_data' => [
                        'payroll_run_id' => $runId,
                        'payroll_item_id' => $payrollItemId,
                        'payslip_no' => $payslipNo,
                        'delivery_mode' => $deliveryMode,
                        'release_reason' => $releaseReason,
                        'recipient_masked' => $maskedRecipient,
                        'status' => 'failed',
                        'smtp_encryption' => $smtpEncryption,
                        'smtp_auth' => $smtpAuthEnabled,
                        'error' => $sanitizedError,
                    ],
                    'ip_address' => $ipAddress,
                ];
            }

            if (count($emailAttemptLogs) >= 100) {
                $flushLogs($emailAttemptLogs);
                $emailAttemptLogs = [];
            }
        }

        $flushLogs($emailAttemptLogs);

        return [
            'attempted' => $emailsAttempted,
            'sent' => $emailsSent,
            'failed' => $emailsFailed,
            'error_samples' => $emailErrorSamples,
            'smtp_encryption' => $smtpEncryption,
            'smtp_auth' => $smtpAuthEnabled,
        ];
    }
}

if (!function_exists('payrollServicePrepareCsvExport')) {
    function payrollServicePrepareCsvExport(string $periodId, string $supabaseUrl, array $headers): array
    {
        if (!function_exists('isValidUuid') || !isValidUuid($periodId)) {
            throw new RuntimeException('Please select a valid payroll period for CSV export.');
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
            throw new RuntimeException('Selected payroll period was not found.');
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
            throw new RuntimeException('Failed to load payroll runs for export.');
        }

        $runById = [];
        foreach ((array)($runsResponse['data'] ?? []) as $runRow) {
            $runId = cleanText($runRow['id'] ?? null) ?? '';
            if (!function_exists('isValidUuid') || !isValidUuid($runId)) {
                continue;
            }

            $runById[$runId] = [
                'generated_date' => payrollServiceIsoDateFromTimestamp(cleanText($runRow['generated_at'] ?? null)),
            ];
        }

        if ($runById === []) {
            throw new RuntimeException('No payroll runs match the selected export filters.');
        }

        $runIdFilter = payrollServiceFormatInFilterList(array_keys($runById));
        if ($runIdFilter === '') {
            throw new RuntimeException('No valid payroll runs available for CSV export.');
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
            throw new RuntimeException('Failed to load payroll items for CSV export.');
        }

        $itemRows = (array)($itemsResponse['data'] ?? []);
        if ($itemRows === []) {
            throw new RuntimeException('No payroll item records found for selected export filters.');
        }

        $itemIds = [];
        foreach ($itemRows as $itemRow) {
            $itemId = cleanText($itemRow['id'] ?? null) ?? '';
            if (function_exists('isValidUuid') && isValidUuid($itemId)) {
                $itemIds[] = $itemId;
            }
        }

        $itemIdFilter = payrollServiceFormatInFilterList($itemIds);
        $breakdownByItemId = [];
        if ($itemIdFilter !== '') {
            $itemBreakdownResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                . '&entity_name=eq.payroll_items'
                . '&action_name=eq.compute_item_breakdown'
                . '&entity_id=in.' . rawurlencode('(' . $itemIdFilter . ')')
                . '&order=created_at.desc&limit=10000',
                $headers
            );

            if (isSuccessful($itemBreakdownResponse)) {
                foreach ((array)($itemBreakdownResponse['data'] ?? []) as $logRow) {
                    $entityId = cleanText($logRow['entity_id'] ?? null) ?? '';
                    if ((function_exists('isValidUuid') && !isValidUuid($entityId)) || isset($breakdownByItemId[$entityId])) {
                        continue;
                    }

                    $newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];
                    $deductions = is_array($newData['deductions'] ?? null) ? (array)$newData['deductions'] : [];
                    $attendanceSource = is_array($newData['attendance_source'] ?? null) ? (array)$newData['attendance_source'] : [];

                    $breakdownByItemId[$entityId] = [
                        'statutory_deductions' => (float)($deductions['statutory_deductions'] ?? 0),
                        'timekeeping_deductions' => (float)($deductions['timekeeping_deductions'] ?? 0),
                        'absent_days' => (int)($attendanceSource['absent_days'] ?? 0),
                        'late_minutes' => (int)($attendanceSource['late_minutes'] ?? 0),
                        'undertime_hours' => (float)($attendanceSource['undertime_hours'] ?? 0),
                    ];
                }
            }
        }

        $approvedAdjustmentByItemId = [];
        if ($itemIdFilter !== '') {
            $adjustmentsResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,amount'
                . '&payroll_item_id=in.' . rawurlencode('(' . $itemIdFilter . ')')
                . '&limit=10000',
                $headers
            );

            if (isSuccessful($adjustmentsResponse)) {
                $adjustmentRows = (array)($adjustmentsResponse['data'] ?? []);
                $adjustmentIds = [];
                foreach ($adjustmentRows as $adjustmentRow) {
                    $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
                    if (function_exists('isValidUuid') && isValidUuid($adjustmentId)) {
                        $adjustmentIds[] = $adjustmentId;
                    }
                }

                $reviewStatusByAdjustmentId = [];
                $adjustmentIdFilter = payrollServiceFormatInFilterList($adjustmentIds);
                if ($adjustmentIdFilter !== '') {
                    $reviewLogResponse = apiRequest(
                        'GET',
                        $supabaseUrl
                        . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                        . '&entity_name=eq.payroll_adjustments'
                        . '&action_name=eq.review_payroll_adjustment'
                        . '&entity_id=in.' . rawurlencode('(' . $adjustmentIdFilter . ')')
                        . '&order=created_at.desc&limit=10000',
                        $headers
                    );

                    if (isSuccessful($reviewLogResponse)) {
                        foreach ((array)($reviewLogResponse['data'] ?? []) as $reviewRow) {
                            $entityId = cleanText($reviewRow['entity_id'] ?? null) ?? '';
                            if ((function_exists('isValidUuid') && !isValidUuid($entityId)) || isset($reviewStatusByAdjustmentId[$entityId])) {
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

                foreach ($adjustmentRows as $adjustmentRow) {
                    $adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
                    if (function_exists('isValidUuid') && !isValidUuid($adjustmentId)) {
                        continue;
                    }

                    if (strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending')) !== 'approved') {
                        continue;
                    }

                    $itemId = cleanText($adjustmentRow['payroll_item_id'] ?? null) ?? '';
                    if (function_exists('isValidUuid') && !isValidUuid($itemId)) {
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
        }

        $exportRows = [];
        foreach ($itemRows as $itemRow) {
            $itemId = cleanText($itemRow['id'] ?? null) ?? '';
            $runId = cleanText($itemRow['payroll_run_id'] ?? null) ?? '';
            if ((function_exists('isValidUuid') && !isValidUuid($runId)) || !isset($runById[$runId])) {
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

            $baseDeductionsTotal = (float)($itemRow['deductions_total'] ?? 0);
            $baseGrossPay = (float)($itemRow['gross_pay'] ?? 0);
            $baseNetPay = (float)($itemRow['net_pay'] ?? 0);

            $breakdown = is_array($breakdownByItemId[$itemId] ?? null)
                ? (array)$breakdownByItemId[$itemId]
                : [];

            $approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
                ? (array)$approvedAdjustmentByItemId[$itemId]
                : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];

            $statutoryDeductions = (float)($breakdown['statutory_deductions'] ?? $baseDeductionsTotal);
            $timekeepingDeductions = (float)($breakdown['timekeeping_deductions'] ?? 0.0);
            $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0.0);
            $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0.0);

            $exportRows[] = [
                'period_code' => $periodCode,
                'run_id' => strtoupper(substr(str_replace('-', '', $runId), 0, 8)),
                'run_generated_date' => $runById[$runId]['generated_date'] !== '' ? $runById[$runId]['generated_date'] : '-',
                'employee_id' => $personId,
                'employee_name' => $employeeName,
                'basic_pay' => (float)($itemRow['basic_pay'] ?? 0),
                'overtime_pay' => (float)($itemRow['overtime_pay'] ?? 0),
                'allowances_total' => (float)($itemRow['allowances_total'] ?? 0),
                'statutory_deductions' => $statutoryDeductions,
                'timekeeping_deductions' => $timekeepingDeductions,
                'adjustment_earnings' => $adjustmentEarnings,
                'adjustment_deductions' => $adjustmentDeductions,
                'deductions_total' => $baseDeductionsTotal + $adjustmentDeductions,
                'gross_pay' => $baseGrossPay + $adjustmentEarnings,
                'net_pay' => $baseNetPay + $adjustmentEarnings - $adjustmentDeductions,
                'absent_days' => (int)($breakdown['absent_days'] ?? 0),
                'late_minutes' => (int)($breakdown['late_minutes'] ?? 0),
                'undertime_hours' => (float)($breakdown['undertime_hours'] ?? 0),
            ];
        }

        if ($exportRows === []) {
            throw new RuntimeException('No payroll rows matched the selected CSV export filters.');
        }

        usort($exportRows, static function (array $left, array $right): int {
            $leftKey = strtolower((string)($left['employee_name'] ?? '') . ' ' . (string)($left['run_generated_date'] ?? ''));
            $rightKey = strtolower((string)($right['employee_name'] ?? '') . ' ' . (string)($right['run_generated_date'] ?? ''));
            return strcmp($leftKey, $rightKey);
        });

        return [
            'period_id' => $periodId,
            'period_code' => $periodCode,
            'file_name' => 'payroll-export-' . preg_replace('/[^a-zA-Z0-9\-_]+/', '-', strtolower($periodCode)) . '-' . gmdate('Ymd-His') . '.csv',
            'rows' => $exportRows,
        ];
    }
}

if (!function_exists('payrollServicePersistComputedPayrollItems')) {
    function payrollServicePersistComputedPayrollItems(
        string $payrollRunId,
        array $itemPayload,
        string $supabaseUrl,
        array $headers,
        string $actorUserId,
        string $periodStart,
        string $periodEnd,
        string $ipAddress,
        array $options = []
    ): array {
        if (!function_exists('isValidUuid') || !isValidUuid($payrollRunId)) {
            throw new RuntimeException('Payroll run identifier is invalid for payroll item persistence.');
        }

        if ($itemPayload === []) {
            return [
                'count' => 0,
                'item_ids_by_person_id' => [],
            ];
        }

        $mode = strtolower(trim((string)($options['mode'] ?? 'insert')));
        $timestamp = trim((string)($options['timestamp'] ?? ''));
        $itemPayloadByPersonId = [];
        foreach ($itemPayload as $sourceRow) {
            $personId = strtolower(trim((string)($sourceRow['person_id'] ?? '')));
            if (!isValidUuid($personId)) {
                continue;
            }

            $itemPayloadByPersonId[$personId] = (array)$sourceRow;
        }

        $flushLogs = static function (array $logPayload) use ($supabaseUrl, $headers): void {
            if ($logPayload === []) {
                return;
            }

            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/activity_logs',
                array_merge($headers, ['Prefer: return=minimal']),
                $logPayload
            );
        };

        $buildBreakdownLog = static function (string $itemId, string $personId, array $source) use ($actorUserId, $payrollRunId, $periodStart, $periodEnd, $ipAddress): array {
            $attendanceMetrics = is_array($source['attendance_metrics'] ?? null) ? (array)$source['attendance_metrics'] : [];
            $deductions = [
                'statutory_deductions' => (float)($source['statutory_deductions'] ?? 0),
                'timekeeping_deductions' => (float)($source['timekeeping_deduction'] ?? 0),
                'adjustment_deductions' => 0.0,
                'adjustment_earnings' => 0.0,
                'total_deductions' => (float)($source['deductions_total'] ?? 0),
            ];

            if (is_array($source['deduction_source'] ?? null) && $source['deduction_source'] !== []) {
                $deductions['source'] = (array)$source['deduction_source'];
            }

            return [
                'actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
                'module_name' => 'payroll_management',
                'entity_name' => 'payroll_items',
                'entity_id' => $itemId,
                'action_name' => 'compute_item_breakdown',
                'old_data' => null,
                'new_data' => [
                    'payroll_run_id' => $payrollRunId,
                    'person_id' => $personId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'compensation_id' => (string)($source['compensation_id'] ?? ''),
                    'daily_rate' => (float)($source['daily_rate'] ?? 0),
                    'hourly_rate' => (float)($source['hourly_rate'] ?? 0),
                    'earnings' => [
                        'basic_pay' => (float)($source['basic_pay'] ?? 0),
                        'cto_pay' => (float)($source['overtime_pay'] ?? 0),
                        'allowances_total' => (float)($source['allowances_total'] ?? 0),
                        'gross_pay' => (float)($source['gross_pay'] ?? 0),
                    ],
                    'deductions' => $deductions,
                    'attendance_source' => [
                        'absent_days' => (int)($attendanceMetrics['absent_days'] ?? 0),
                        'late_minutes' => (int)($attendanceMetrics['late_minutes'] ?? 0),
                        'undertime_hours' => (float)($attendanceMetrics['undertime_hours'] ?? 0),
                        'attendance_policy' => (string)($attendanceMetrics['attendance_policy'] ?? 'leave_card'),
                        'employment_status' => (string)($attendanceMetrics['employment_status'] ?? ''),
                        'is_cos_employee' => (bool)($attendanceMetrics['is_cos_employee'] ?? false),
                        'imported_timekeeping_deductions' => (float)($attendanceMetrics['imported_timekeeping_deductions'] ?? 0),
                    ],
                    'net_pay' => (float)($source['net_pay'] ?? 0),
                ],
                'ip_address' => $ipAddress,
            ];
        };

        $itemIdsByPersonId = [];
        $persistedCount = 0;
        $logPayload = [];

        if ($mode === 'upsert') {
            $rows = [];
            foreach ($itemPayload as $row) {
                $rows[] = [
                    'payroll_run_id' => $payrollRunId,
                    'person_id' => $row['person_id'],
                    'basic_pay' => $row['basic_pay'],
                    'overtime_pay' => $row['overtime_pay'],
                    'allowances_total' => $row['allowances_total'],
                    'deductions_total' => $row['deductions_total'],
                    'gross_pay' => $row['gross_pay'],
                    'net_pay' => $row['net_pay'],
                    'updated_at' => $timestamp !== '' ? $timestamp : gmdate('c'),
                ];
            }

            $response = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/payroll_items?on_conflict=payroll_run_id,person_id',
                array_merge($headers, ['Prefer: resolution=merge-duplicates,return=representation']),
                $rows
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Failed to save computed payroll items.');
            }

            foreach ((array)($response['data'] ?? []) as $itemRow) {
                $personId = strtolower(trim((string)($itemRow['person_id'] ?? '')));
                $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
                if (!isValidUuid($personId) || !isValidUuid($itemId)) {
                    continue;
                }

                $itemIdsByPersonId[$personId] = $itemId;
                $source = (array)($itemPayloadByPersonId[$personId] ?? []);
                if ($source !== []) {
                    $logPayload[] = $buildBreakdownLog($itemId, $personId, $source);
                }
            }

            $flushLogs($logPayload);

            return [
                'count' => count((array)($response['data'] ?? [])),
                'item_ids_by_person_id' => $itemIdsByPersonId,
            ];
        }

        foreach (array_chunk($itemPayload, 200) as $chunk) {
            $rows = [];
            foreach ($chunk as $entry) {
                $rows[] = [
                    'payroll_run_id' => $payrollRunId,
                    'person_id' => $entry['person_id'],
                    'basic_pay' => $entry['basic_pay'],
                    'overtime_pay' => $entry['overtime_pay'],
                    'allowances_total' => $entry['allowances_total'],
                    'deductions_total' => $entry['deductions_total'],
                    'gross_pay' => $entry['gross_pay'],
                    'net_pay' => $entry['net_pay'],
                ];
            }

            $response = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/payroll_items',
                array_merge($headers, ['Prefer: return=representation']),
                $rows
            );

            if (!isSuccessful($response)) {
                throw new RuntimeException('Payroll batch created, but failed to insert payroll items.');
            }

            foreach ((array)($response['data'] ?? []) as $itemRow) {
                $personId = strtolower(trim((string)($itemRow['person_id'] ?? '')));
                $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
                if (!isValidUuid($personId) || !isValidUuid($itemId)) {
                    continue;
                }

                $itemIdsByPersonId[$personId] = $itemId;
                $source = (array)($itemPayloadByPersonId[$personId] ?? []);
                if ($source === []) {
                    continue;
                }

                $logPayload[] = $buildBreakdownLog($itemId, $personId, $source);
                if (count($logPayload) >= 200) {
                    $flushLogs($logPayload);
                    $logPayload = [];
                }
            }

            $persistedCount += count((array)($response['data'] ?? []));
        }

        $flushLogs($logPayload);

        return [
            'count' => $persistedCount,
            'item_ids_by_person_id' => $itemIdsByPersonId,
        ];
    }
}