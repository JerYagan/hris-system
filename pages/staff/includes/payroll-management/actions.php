<?php

require_once __DIR__ . '/../../../admin/includes/notifications/email.php';
require_once __DIR__ . '/../../../shared/lib/payroll-domain.php';

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
        $delegate = 'payrollServiceFormatInFilterList';
        return $delegate($ids);
    }
}

if (!function_exists('staffPayrollDateString')) {
    function staffPayrollDateString(string $value): string
    {
        $delegate = 'payrollServiceDateString';
        return $delegate($value);
    }
}

if (!function_exists('staffPayrollMaskEmailAddress')) {
    function staffPayrollMaskEmailAddress(string $email): string
    {
        $delegate = 'payrollServiceMaskEmailAddress';
        return $delegate($email);
    }
}

if (!function_exists('staffPayrollSanitizeEmailError')) {
    function staffPayrollSanitizeEmailError(string $raw): string
    {
        $delegate = 'payrollServiceSanitizeEmailError';
        return $delegate($raw);
    }
}

if (!function_exists('staffPayrollIsoDateFromTimestamp')) {
    function staffPayrollIsoDateFromTimestamp(?string $value): string
    {
        $delegate = 'payrollServiceIsoDateFromTimestamp';
        return $delegate($value);
    }
}

if (!function_exists('staffPayrollCompensationAppliesToPeriod')) {
    function staffPayrollCompensationAppliesToPeriod(array $row, string $periodStart): bool
    {
        $delegate = 'payrollServiceCompensationAppliesToPeriod';
        return $delegate($row, $periodStart);
    }
}

if (!function_exists('staffPayrollIsCosEmploymentStatus')) {
    function staffPayrollIsCosEmploymentStatus(?string $employmentStatus): bool
    {
        $delegate = 'payrollServiceIsCosEmploymentStatus';
        return $delegate($employmentStatus);
    }
}

if (!function_exists('staffPayrollEnsureDirectory')) {
    function staffPayrollEnsureDirectory(string $dirPath): void
    {
        $delegate = 'payrollServiceEnsureDirectory';
        $delegate($dirPath);
    }
}

if (!function_exists('staffPayrollGeneratePayslipDocument')) {
    function staffPayrollGeneratePayslipDocument(array $payload): array
    {
        $delegate = 'payrollServiceGeneratePayslipDocument';
        return $delegate($payload);
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
        $delegate = 'payrollServiceSendEmailWithAttachment';
        return $delegate($smtpConfig, $fromEmail, $fromName, $toEmail, $toName, $subject, $htmlContent, $attachmentPath, $attachmentName);
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
    $exportReason = cleanText($_POST['export_reason'] ?? null) ?? '';

    try {
        $export = payrollServicePrepareCsvExport($periodId, $supabaseUrl, $headers);
    } catch (RuntimeException $exception) {
        redirectWithState('error', $exception->getMessage());
    }

    $writeActivityLog(
        'payroll_periods',
        (string)$export['period_id'],
        'export_payroll_csv',
        [],
        array_filter([
            'period_code' => (string)$export['period_code'],
            'row_count' => count((array)$export['rows']),
            'reason' => trim($exportReason) !== '' ? $exportReason : null,
            'includes_breakdown' => true,
        ], static fn($value): bool => $value !== null)
    );

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . (string)$export['file_name'] . '"');
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
        'CTO Leave UT w/ Pay (Policy: 0.00)',
        'Allowances',
        'Statutory Deductions',
        'Timekeeping Deductions',
        'Approved Adjustment Earnings',
        'Approved Adjustment Deductions',
        'Total Deductions',
        'Gross Pay',
        'Net Pay',
        'Absent Days',
        'Late Minutes',
        'Undertime Hours',
        'Attendance Impact Notes',
    ]);

    foreach ((array)$export['rows'] as $row) {
        $attendanceRemarks = ((int)$row['absent_days'] > 0)
            ? ('Absence impact: ' . (string)((int)$row['absent_days']) . ' day(s)')
            : 'No absence impact';
        fputcsv($output, [
            (string)$row['period_code'],
            (string)$row['run_id'],
            (string)$row['run_generated_date'],
            (string)$row['employee_id'],
            (string)$row['employee_name'],
            number_format((float)$row['basic_pay'], 2, '.', ''),
            number_format((float)$row['overtime_pay'], 2, '.', ''),
            number_format((float)$row['allowances_total'], 2, '.', ''),
            number_format((float)$row['statutory_deductions'], 2, '.', ''),
            number_format((float)$row['timekeeping_deductions'], 2, '.', ''),
            number_format((float)$row['adjustment_earnings'], 2, '.', ''),
            number_format((float)$row['adjustment_deductions'], 2, '.', ''),
            number_format((float)$row['deductions_total'], 2, '.', ''),
            number_format((float)$row['gross_pay'], 2, '.', ''),
            number_format((float)$row['net_pay'], 2, '.', ''),
            (string)((int)$row['absent_days']),
            (string)((int)$row['late_minutes']),
            number_format((float)$row['undertime_hours'], 2, '.', ''),
            $attendanceRemarks,
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
    $staffRecommendation = cleanText($_POST['staff_recommendation'] ?? null) ?? '';
    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Invalid payroll period selected.');
    }

    if (trim($staffRecommendation) === '') {
        redirectWithState('error', 'Recommendation reason is required before submitting payroll compute results to Admin.');
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
        . '/rest/v1/employment_records?select=person_id,employment_status,employment_type,person:people!employment_records_person_id_fkey(id,user_id,first_name,middle_name,surname),position:job_positions(employment_classification)'
        . '&is_current=eq.true&limit=10000',
        $headers
    );

    if (!isSuccessful($employmentResponse)) {
        redirectWithState('error', 'Failed to load active employment records for payroll compute.');
    }

    $employmentPrepared = payrollServicePrepareActivePeopleFromEmploymentRows((array)($employmentResponse['data'] ?? []));
    $personIds = (array)($employmentPrepared['person_ids'] ?? []);
    $peopleById = (array)($employmentPrepared['people_by_id'] ?? []);

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
        . '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,daily_rate,hourly_rate,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,pay_frequency,created_at'
        . '&person_id=in.' . rawurlencode('(' . $personIdFilter . ')')
        . '&order=effective_from.desc,created_at.desc&limit=10000',
        $headers
    );

    if (!isSuccessful($compensationsResponse)) {
        redirectWithState('error', 'Failed to load employee compensation records for payroll compute.');
    }

    $compensationByPerson = payrollServiceGroupCompensationRowsByPerson((array)($compensationsResponse['data'] ?? []));
    $effectiveCompensationByPerson = payrollServiceResolveEffectiveCompensations($compensationByPerson, $periodStart);

    try {
        $attendancePayload = payrollServiceLoadAttendanceStatsForPeople(
            $supabaseUrl,
            $headers,
            $personIds,
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
        $effectiveCompensationByPerson,
        $attendanceStatsByPersonId
    );

    $itemPayload = (array)($computeResult['item_payload'] ?? []);
    $totalTimekeepingDeductions = (float)($computeResult['total_timekeeping_deductions'] ?? 0);
    $totalStatutoryDeductions = (float)($computeResult['total_statutory_deductions'] ?? 0);

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

    try {
        $persistResult = payrollServicePersistComputedPayrollItems(
            $payrollRunId,
            $itemPayload,
            $supabaseUrl,
            $headers,
            $staffUserId,
            $periodStart,
            $periodEnd,
            clientIp(),
            [
                'mode' => 'upsert',
                'timestamp' => $nowIso,
            ]
        );
    } catch (RuntimeException $exception) {
        redirectWithState('error', $exception->getMessage());
    }

    $persistedCount = (int)($persistResult['count'] ?? 0);

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
            'computed_employee_count' => $persistedCount,
            'staff_recommendation' => $staffRecommendation,
            'source_inputs' => [
                'attendance_log_count' => $attendanceLogCount,
                'compensation_rows_count' => count($compensationsResponse['data'] ?? []),
                'employment_rows_count' => count($peopleById),
            ],
            'computation_breakdown_totals' => [
                'timekeeping_deductions' => round($totalTimekeepingDeductions, 2),
                'statutory_deductions' => round($totalStatutoryDeductions, 2),
            ],
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
            'reason' => $staffRecommendation,
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

    redirectWithState('success', 'Payroll computed successfully for ' . $persistedCount . ' employee(s).');
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

            payrollServiceNotifyRoleAssignments(
                $supabaseUrl,
                $headers,
                'admin',
                'payroll',
                'Salary Adjustment Recommendation Submitted',
                'Staff recommended ' . $recommendationStatus . ' for ' . $adjustmentCode . '. Please apply final decision in payroll adjustment review.',
                '/hris-system/pages/admin/payroll-management.php'
            );
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

    $adminReviewStatus = payrollServiceLoadLatestActivityStatus(
        $supabaseUrl,
        $headers,
        'payroll_adjustments',
        'review_payroll_adjustment',
        $adjustmentId,
        ['pending', 'approved', 'rejected'],
        ['review_status', 'status_to', 'status']
    ) ?? 'pending';

    if ($adminReviewStatus !== 'pending') {
        redirectWithState('error', 'This salary adjustment has already been finalized by Admin.');
    }

    $previousRecommendation = payrollServiceLoadLatestActivityStatus(
        $supabaseUrl,
        $headers,
        'payroll_adjustments',
        'recommend_payroll_adjustment',
        $adjustmentId,
        ['pending', 'approved', 'rejected'],
        ['recommendation_status']
    ) ?? 'pending';

    $writeActivityLog(
        'payroll_adjustments',
        $adjustmentId,
        'recommend_payroll_adjustment',
        ['recommendation_status' => $previousRecommendation],
        ['recommendation_status' => $decision, 'notes' => $notes, 'submitted_to_admin' => true]
    );

    $adjustmentCode = cleanText($adjustmentRow['adjustment_code'] ?? null) ?? 'Salary adjustment';
    payrollServiceNotifyRoleAssignments(
        $supabaseUrl,
        $headers,
        'admin',
        'payroll',
        'Salary Adjustment Recommendation Submitted',
        'Staff recommended ' . $decision . ' for ' . $adjustmentCode . '. Please apply final decision in payroll adjustment review.',
        '/hris-system/pages/admin/payroll-management.php'
    );

    redirectWithState('success', 'Salary adjustment recommendation submitted to Admin for final approval.');
}

if ($action === 'generate_payslip_run') {
    $runId = cleanText($_POST['run_id'] ?? null) ?? '';
    $releaseReason = cleanText($_POST['release_reason'] ?? null) ?? '';
    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Invalid payroll run selected.');
    }

    if (trim($releaseReason) === '') {
        redirectWithState('error', 'Release reason is required for payroll send audit logging.');
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
    if (!in_array($oldStatus, ['approved', 'released'], true)) {
        redirectWithState('error', 'Payslips can only be generated for payroll runs in Approved or Released status.');
    }

    $itemsResponse = apiRequest(
        'GET',
        $supabaseUrl
		. '/rest/v1/payroll_items?select=id,payroll_run_id,basic_pay,overtime_pay,allowances_total,gross_pay,deductions_total,net_pay,person:people(first_name,middle_name,surname,user_id)'
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

    $approvedAdjustmentByItemId = payrollServiceLoadApprovedAdjustmentsForItems($supabaseUrl, $headers, $itemIds);

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

    $smtpEncryption = strtolower(trim((string)($smtpConfig['encryption'] ?? 'tls')));
    $smtpAuthEnabled = ((string)($smtpConfig['auth'] ?? '1')) !== '0';
    if (!in_array($smtpEncryption, ['tls', 'starttls', 'ssl'], true)) {
        redirectWithState('error', 'Secure SMTP encryption must be TLS/STARTTLS or SSL before sending payslips.');
    }
    if (!$smtpAuthEnabled) {
        redirectWithState('error', 'SMTP authentication must be enabled before sending payslips.');
    }

    $nowIso = gmdate('c');
    try {
        $existingByItemId = payrollServiceEnsurePayslipRecords(
            $supabaseUrl,
            $headers,
            $itemIds,
            (string)($runRow['payroll_period']['period_code'] ?? 'PR'),
            $runId,
            $nowIso,
            true,
            'run_short'
        );
    } catch (Throwable $throwable) {
        redirectWithState('error', $throwable->getMessage());
    }

    $periodCode = (string)($runRow['payroll_period']['period_code'] ?? 'PR');
    $periodStartRaw = cleanText($runRow['payroll_period']['period_start'] ?? null) ?? '';
    $periodEndRaw = cleanText($runRow['payroll_period']['period_end'] ?? null) ?? '';
    $periodLabel = ($periodStartRaw !== '' && $periodEndRaw !== '')
        ? (date('M d, Y', strtotime($periodStartRaw)) . ' - ' . date('M d, Y', strtotime($periodEndRaw)))
        : strtoupper($periodCode);

    $userIds = [];
    foreach ($itemRows as $itemRow) {
        $userId = strtolower(trim((string)($itemRow['person']['user_id'] ?? '')));
        if (isValidUuid($userId)) {
            $userIds[] = $userId;
        }
    }

    $emailAddressByUserId = payrollServiceResolveUserEmailMap($supabaseUrl, $headers, $userIds);

    try {
        $documentResult = payrollServiceGeneratePayslipDocumentsForItems(
            $itemRows,
            $existingByItemId,
            $approvedAdjustmentByItemId,
            [],
            dirname(__DIR__, 4),
            $periodLabel,
            $periodCode,
            $supabaseUrl,
            $headers,
            $nowIso,
            false,
            true,
            false
        );
    } catch (Throwable $throwable) {
        redirectWithState('error', $throwable->getMessage());
    }

    $existingByItemId = (array)($documentResult['payslips_by_item_id'] ?? $existingByItemId);
    $documentByItemId = (array)($documentResult['documents_by_item_id'] ?? []);

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
    $emailResult = payrollServiceSendPayslipEmails(
        $itemRows,
        $existingByItemId,
        $approvedAdjustmentByItemId,
        $documentByItemId,
        $emailAddressByUserId,
        $smtpConfig,
        $mailFrom,
        $mailFromName,
        $periodCode,
        $runId,
        $staffUserId,
        clientIp(),
        $supabaseUrl,
        $headers,
        'staffPayrollMaskEmailAddress',
        'staffPayrollSanitizeEmailError',
        $releaseReason,
        'immediate'
    );

    $emailsAttempted = (int)($emailResult['attempted'] ?? 0);
    $emailsSent = (int)($emailResult['sent'] ?? 0);
    $emailsFailed = (int)($emailResult['failed'] ?? 0);
    $emailErrorSamples = (array)($emailResult['error_samples'] ?? []);
    $smtpEncryption = (string)($emailResult['smtp_encryption'] ?? $smtpEncryption);
    $smtpAuthEnabled = (bool)($emailResult['smtp_auth'] ?? $smtpAuthEnabled);

    foreach ($itemRows as $itemRow) {
        $userId = strtolower(trim((string)($itemRow['person']['user_id'] ?? '')));
        $recipientEmail = $emailAddressByUserId[$userId] ?? '';
        if (!isValidUuid($userId) || $recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            continue;
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
            'smtp_encryption' => $smtpEncryption,
            'smtp_auth' => $smtpAuthEnabled,
            'release_reason' => $releaseReason,
        ]
    );

    $message = 'Payslips generated successfully for the selected run. Email sent: ' . $emailsSent . ', failed: ' . $emailsFailed . '.';
    if ($emailsFailed > 0 && !empty($emailErrorSamples)) {
        $message .= ' Sample error: ' . $emailErrorSamples[0];
    }

    redirectWithState('success', $message);
}

redirectWithState('error', 'Unknown payroll management action.');
