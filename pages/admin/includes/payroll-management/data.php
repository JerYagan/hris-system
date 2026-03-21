<?php

require_once __DIR__ . '/../../../shared/lib/payroll-domain.php';

if (!function_exists('isValidUuid')) {
    function isValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

if (!function_exists('payrollSyncDefaultConfig')) {
    function payrollSyncDefaultConfig(): array
    {
        return payrollServiceDefaultSyncConfig();
    }
}

if (!function_exists('payrollManagementFormatPeriodLabel')) {
    function payrollManagementFormatPeriodLabel(array $period): string
    {
        $periodCode = (string)($period['period_code'] ?? 'PR');
        $periodStart = (string)($period['period_start'] ?? '');
        $periodEnd = (string)($period['period_end'] ?? '');

        if ($periodStart !== '' && $periodEnd !== '') {
            return date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd));
        }

        return $periodCode;
    }
}

if (!function_exists('payrollManagementAppendQueryError')) {
    function payrollManagementAppendQueryError(?string $currentError, string $label, array $response): string
    {
        $message = ucfirst($label) . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
        $raw = trim((string)($response['raw'] ?? ''));
        if ($raw !== '') {
            $message .= ' ' . $raw;
        }

        return $currentError ? ($currentError . ' ' . $message) : $message;
    }
}

if (!function_exists('payrollManagementResolveEffectiveRunStatus')) {
    function payrollManagementResolveEffectiveRunStatus(array $run, array $runHandoffById): string
    {
        $runId = (string)($run['id'] ?? '');
        $runStatusRaw = strtolower(trim((string)($run['run_status'] ?? 'draft')));
        $hasStaffSubmission = $runId !== '' && isset($runHandoffById[$runId]);

        if (!$hasStaffSubmission && $runStatusRaw === 'computed') {
            return 'pending_review';
        }

        return $runStatusRaw;
    }
}

$payrollManagementDataStage = (string)($payrollManagementDataStage ?? 'full');
$adminPayslipBreakdownPayload = null;
$salarySetupLogRows = [];
$payrollEstimateHistoryRows = [];
$payslipTableRows = [];
$generationPreviewByPeriod = [];
$generationPreviewRows = [];
$generationPreviewTotals = [
    'employee_count' => 0,
    'gross_pay' => 0.0,
    'net_pay' => 0.0,
];

if ($payrollManagementDataStage === 'secondary') {
    $payrollManagementLoadDeferredHistory = false;
    $payrollManagementLoadDeferredPayslips = false;
    $payrollManagementLoadDetailedItemPayload = false;
} else {
    $payrollManagementLoadDeferredHistory = true;
    $payrollManagementLoadDeferredPayslips = true;
    $payrollManagementLoadDetailedItemPayload = true;
}

if (in_array($payrollManagementDataStage, ['summary', 'overview'], true)) {
    $dataLoadError = null;
    $payrollSummary = [
        'current_cutoff_label' => 'No payroll period found',
        'current_cutoff_status' => 'No batch generated',
        'current_cutoff_employees' => 0,
        'current_cutoff_gross' => 0.0,
        'current_cutoff_net' => 0.0,
        'open_periods' => 0,
        'pending_batches' => 0,
        'ready_to_release' => 0,
    ];

    $summaryPeriodsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&order=period_end.desc&limit=36',
        $headers
    );
    $summaryRunsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,created_at,payroll_period:payroll_periods(period_code,period_start,period_end,status)&order=created_at.desc&limit=160',
        $headers
    );

    $summaryPeriodRows = isSuccessful($summaryPeriodsResponse) ? (array)($summaryPeriodsResponse['data'] ?? []) : [];
    $summaryRunRows = isSuccessful($summaryRunsResponse) ? (array)($summaryRunsResponse['data'] ?? []) : [];
    $summaryRunIds = [];
    foreach ($summaryRunRows as $summaryRunRow) {
        $runId = (string)($summaryRunRow['id'] ?? '');
        if ($runId !== '') {
            $summaryRunIds[] = $runId;
        }
    }

    $summaryRunHandoffById = [];
    if (!empty($summaryRunIds)) {
        $summaryRunLogsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
            . '&entity_name=eq.payroll_runs'
            . '&entity_id=in.' . rawurlencode('(' . implode(',', $summaryRunIds) . ')')
            . '&action_name=in.' . rawurlencode('(submit_batch_for_admin_approval,review_batch)')
            . '&order=created_at.desc&limit=600',
            $headers
        );

        if (isSuccessful($summaryRunLogsResponse)) {
            foreach ((array)($summaryRunLogsResponse['data'] ?? []) as $summaryRunLog) {
                $loggedRunId = (string)($summaryRunLog['entity_id'] ?? '');
                $actionName = strtolower(trim((string)($summaryRunLog['action_name'] ?? '')));
                if ($loggedRunId === '' || $actionName !== 'submit_batch_for_admin_approval' || isset($summaryRunHandoffById[$loggedRunId])) {
                    continue;
                }

                $summaryRunHandoffById[$loggedRunId] = true;
            }
        } else {
            $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll run activity', $summaryRunLogsResponse);
        }
    }

    foreach ($summaryPeriodRows as $summaryPeriodRow) {
        $status = strtolower(trim((string)($summaryPeriodRow['status'] ?? 'open')));
        if (in_array($status, ['open', 'processing', 'posted'], true)) {
            $payrollSummary['open_periods']++;
        }
    }

    foreach ($summaryRunRows as $summaryRunRow) {
        $effectiveStatus = payrollManagementResolveEffectiveRunStatus($summaryRunRow, $summaryRunHandoffById);
        if (in_array($effectiveStatus, ['pending_review', 'computed'], true)) {
            $payrollSummary['pending_batches']++;
        }
        if ($effectiveStatus === 'approved') {
            $payrollSummary['ready_to_release']++;
        }
    }

    if (!empty($summaryPeriodRows)) {
        $latestPeriod = (array)$summaryPeriodRows[0];
        $latestPeriodId = (string)($latestPeriod['id'] ?? '');
        $payrollSummary['current_cutoff_label'] = payrollManagementFormatPeriodLabel($latestPeriod);
        $payrollSummary['current_cutoff_status'] = ucfirst((string)($latestPeriod['status'] ?? 'open'));

        $latestRun = null;
        foreach ($summaryRunRows as $summaryRunRow) {
            if ((string)($summaryRunRow['payroll_period_id'] ?? '') !== $latestPeriodId) {
                continue;
            }
            $latestRun = $summaryRunRow;
            break;
        }

        if (is_array($latestRun)) {
            $payrollSummary['current_cutoff_status'] = ucwords(str_replace('_', ' ', payrollManagementResolveEffectiveRunStatus($latestRun, $summaryRunHandoffById)));
            $latestRunId = (string)($latestRun['id'] ?? '');
            $summaryItemsResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/payroll_items?select=person_id,gross_pay,net_pay&payroll_run_id=eq.' . $latestRunId . '&limit=1500',
                $headers
            );

            if (isSuccessful($summaryItemsResponse)) {
                $summaryPeople = [];
                foreach ((array)($summaryItemsResponse['data'] ?? []) as $summaryItemRow) {
                    $personId = (string)($summaryItemRow['person_id'] ?? '');
                    if ($personId !== '') {
                        $summaryPeople[$personId] = true;
                    }

                    $payrollSummary['current_cutoff_gross'] += (float)($summaryItemRow['gross_pay'] ?? 0);
                    $payrollSummary['current_cutoff_net'] += (float)($summaryItemRow['net_pay'] ?? 0);
                }

                $payrollSummary['current_cutoff_employees'] = count($summaryPeople);
            } else {
                $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll items', $summaryItemsResponse);
            }
        }
    }

    if (!isSuccessful($summaryPeriodsResponse)) {
        $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll periods', $summaryPeriodsResponse);
    }
    if (!isSuccessful($summaryRunsResponse)) {
        $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll runs', $summaryRunsResponse);
    }

    if ($payrollManagementDataStage === 'summary') {
        return;
    }
}

if ($payrollManagementDataStage === 'batches') {
    $dataLoadError = null;
    $batchPageSize = 10;
    $rawBatchSearch = trim((string)cleanText($_GET['payroll_batch_search'] ?? null));
    $payrollBatchFilters = [
        'search_raw' => $rawBatchSearch,
        'search' => strtolower($rawBatchSearch),
        'status' => strtolower(trim((string)cleanText($_GET['payroll_batch_status'] ?? null))),
        'page' => max(1, (int)(cleanText($_GET['payroll_batch_page'] ?? null) ?? 1)),
    ];
    $payrollBatchPagination = [
        'page' => 1,
        'per_page' => $batchPageSize,
        'total_rows' => 0,
        'total_pages' => 1,
        'start' => 0,
        'end' => 0,
    ];
    $batchRows = [];
    $batchBreakdownByRun = [];

    $batchRunsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,approved_at,generated_at,created_at,payroll_period:payroll_periods(period_code,period_start,period_end,payout_date,status),office:offices(office_name)&order=created_at.desc&limit=180',
        $headers
    );

    $batchRunRows = isSuccessful($batchRunsResponse) ? (array)($batchRunsResponse['data'] ?? []) : [];
    $batchRunIds = [];
    foreach ($batchRunRows as $batchRunRow) {
        $runId = (string)($batchRunRow['id'] ?? '');
        if ($runId !== '') {
            $batchRunIds[] = $runId;
        }
    }

    $batchRunHandoffById = [];
    $batchRunReviewById = [];
    if (!empty($batchRunIds)) {
        $batchRunLogsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
            . '&entity_name=eq.payroll_runs'
            . '&entity_id=in.' . rawurlencode('(' . implode(',', $batchRunIds) . ')')
            . '&action_name=in.' . rawurlencode('(submit_batch_for_admin_approval,review_batch)')
            . '&order=created_at.desc&limit=900',
            $headers
        );

        if (isSuccessful($batchRunLogsResponse)) {
            foreach ((array)($batchRunLogsResponse['data'] ?? []) as $batchRunLog) {
                $loggedRunId = (string)($batchRunLog['entity_id'] ?? '');
                if ($loggedRunId === '') {
                    continue;
                }

                $actionName = strtolower(trim((string)($batchRunLog['action_name'] ?? '')));
                $newData = is_array($batchRunLog['new_data'] ?? null) ? (array)$batchRunLog['new_data'] : [];

                if ($actionName === 'submit_batch_for_admin_approval' && !isset($batchRunHandoffById[$loggedRunId])) {
                    $batchRunHandoffById[$loggedRunId] = [
                        'staff_recommendation' => (string)(cleanText($newData['staff_recommendation'] ?? null) ?? 'Recommend approval'),
                        'submitted_at' => (string)(cleanText($batchRunLog['created_at'] ?? null) ?? ''),
                    ];
                    continue;
                }

                if ($actionName === 'review_batch' && !isset($batchRunReviewById[$loggedRunId])) {
                    $batchRunReviewById[$loggedRunId] = [
                        'decision' => (string)(cleanText($newData['new_status'] ?? null) ?? cleanText($newData['status'] ?? null) ?? ''),
                        'review_notes' => (string)(cleanText($newData['notes'] ?? null) ?? cleanText($newData['comment'] ?? null) ?? ''),
                        'reviewed_at' => (string)(cleanText($batchRunLog['created_at'] ?? null) ?? ''),
                    ];
                }
            }
        } else {
            $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll run activity', $batchRunLogsResponse);
        }
    }

    $batchMetaRows = [];
    foreach ($batchRunRows as $batchRunRow) {
        $runId = (string)($batchRunRow['id'] ?? '');
        if ($runId === '') {
            continue;
        }

        $period = is_array($batchRunRow['payroll_period'] ?? null) ? (array)$batchRunRow['payroll_period'] : [];
        $periodCode = (string)($period['period_code'] ?? 'Uncoded Period');
        $periodLabel = payrollManagementFormatPeriodLabel($period);
        $effectiveStatus = payrollManagementResolveEffectiveRunStatus($batchRunRow, $batchRunHandoffById);
        $statusLabel = strtolower(str_replace('_', ' ', $effectiveStatus));
        $searchText = strtolower(trim($runId . ' ' . $periodCode . ' ' . $periodLabel . ' ' . $statusLabel));

        if ($payrollBatchFilters['status'] !== '' && $effectiveStatus !== $payrollBatchFilters['status']) {
            continue;
        }
        if ($payrollBatchFilters['search'] !== '' && !str_contains($searchText, $payrollBatchFilters['search'])) {
            continue;
        }

        $batchMetaRows[] = [
            'id' => $runId,
            'period_id' => (string)($batchRunRow['payroll_period_id'] ?? ''),
            'period_code' => $periodCode,
            'period_label' => $periodLabel,
            'status' => $effectiveStatus,
            'staff_recommendation' => (string)($batchRunHandoffById[$runId]['staff_recommendation'] ?? 'Not yet submitted by Staff'),
            'staff_submitted_at' => (string)($batchRunHandoffById[$runId]['submitted_at'] ?? ''),
            'admin_review_decision' => (string)($batchRunReviewById[$runId]['decision'] ?? ''),
            'admin_review_notes' => (string)($batchRunReviewById[$runId]['review_notes'] ?? ''),
            'admin_reviewed_at' => (string)($batchRunReviewById[$runId]['reviewed_at'] ?? ''),
        ];
    }

    $payrollBatchPagination['total_rows'] = count($batchMetaRows);
    $payrollBatchPagination['total_pages'] = max(1, (int)ceil($payrollBatchPagination['total_rows'] / $batchPageSize));
    $payrollBatchFilters['page'] = min($payrollBatchFilters['page'], $payrollBatchPagination['total_pages']);
    $payrollBatchPagination['page'] = $payrollBatchFilters['page'];
    $offset = ($payrollBatchFilters['page'] - 1) * $batchPageSize;
    $pagedBatchMetaRows = array_slice($batchMetaRows, $offset, $batchPageSize);
    $payrollBatchPagination['start'] = $payrollBatchPagination['total_rows'] === 0 ? 0 : ($offset + 1);
    $payrollBatchPagination['end'] = $payrollBatchPagination['total_rows'] === 0
        ? 0
        : min($offset + count($pagedBatchMetaRows), $payrollBatchPagination['total_rows']);

    $visibleRunIds = array_values(array_filter(array_map(static fn(array $row): string => (string)($row['id'] ?? ''), $pagedBatchMetaRows)));
    $visibleRunFilter = implode(',', $visibleRunIds);
    $batchItemsByRun = [];
    $batchItemBreakdownByItemId = [];
    $approvedAdjustmentByItemId = [];
    $runAdjustmentSummaryByRun = [];

    if (!empty($visibleRunIds)) {
        $batchItemsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,basic_pay,overtime_pay,allowances_total,gross_pay,deductions_total,net_pay,created_at,person:people(first_name,surname)&payroll_run_id=in.(' . $visibleRunFilter . ')&order=created_at.desc&limit=1600',
            $headers
        );

        $batchItemRows = isSuccessful($batchItemsResponse) ? (array)($batchItemsResponse['data'] ?? []) : [];
        if (!isSuccessful($batchItemsResponse)) {
            $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll items', $batchItemsResponse);
        }

        $visibleItemIds = [];
        foreach ($batchItemRows as $batchItemRow) {
            $runId = (string)($batchItemRow['payroll_run_id'] ?? '');
            $itemId = strtolower(trim((string)($batchItemRow['id'] ?? '')));
            if ($runId === '' || !isValidUuid($itemId)) {
                continue;
            }

            $batchItemsByRun[$runId][] = $batchItemRow;
            $visibleItemIds[$itemId] = true;
        }

        if (!empty($visibleItemIds)) {
            $visibleItemFilter = implode(',', array_keys($visibleItemIds));

            $batchBreakdownResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                . '&entity_name=eq.payroll_items'
                . '&action_name=eq.compute_item_breakdown'
                . '&entity_id=in.(' . $visibleItemFilter . ')'
                . '&order=created_at.desc&limit=2600',
                $headers
            );

            if (isSuccessful($batchBreakdownResponse)) {
                foreach ((array)($batchBreakdownResponse['data'] ?? []) as $breakdownLog) {
                    $itemId = strtolower(trim((string)($breakdownLog['entity_id'] ?? '')));
                    if (!isValidUuid($itemId) || isset($batchItemBreakdownByItemId[$itemId])) {
                        continue;
                    }

                    $newData = is_array($breakdownLog['new_data'] ?? null) ? (array)$breakdownLog['new_data'] : [];
                    $earnings = is_array($newData['earnings'] ?? null) ? (array)$newData['earnings'] : [];
                    $deductions = is_array($newData['deductions'] ?? null) ? (array)$newData['deductions'] : [];
                    $attendanceSource = is_array($newData['attendance_source'] ?? null) ? (array)$newData['attendance_source'] : [];

                    $batchItemBreakdownByItemId[$itemId] = [
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
            } else {
                $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll item breakdown', $batchBreakdownResponse);
            }

            $adjustmentsForItemsResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,adjustment_code,description,amount'
                . '&payroll_item_id=in.(' . $visibleItemFilter . ')'
                . '&limit=2600',
                $headers
            );

            if (isSuccessful($adjustmentsForItemsResponse)) {
                $adjustmentRowsForItems = (array)($adjustmentsForItemsResponse['data'] ?? []);
                $adjustmentIdsForItems = [];
                foreach ($adjustmentRowsForItems as $adjustmentRow) {
                    $adjustmentId = strtolower(trim((string)($adjustmentRow['id'] ?? '')));
                    if (isValidUuid($adjustmentId)) {
                        $adjustmentIdsForItems[$adjustmentId] = true;
                    }
                }

                $reviewStatusByAdjustmentId = [];
                $staffRecommendationByAdjustmentId = [];
                if (!empty($adjustmentIdsForItems)) {
                    $adjustmentFilter = implode(',', array_keys($adjustmentIdsForItems));

                    $adjustmentReviewResponse = apiRequest(
                        'GET',
                        $supabaseUrl
                        . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                        . '&entity_name=eq.payroll_adjustments'
                        . '&action_name=eq.review_payroll_adjustment'
                        . '&entity_id=in.(' . $adjustmentFilter . ')'
                        . '&order=created_at.desc&limit=2600',
                        $headers
                    );

                    if (isSuccessful($adjustmentReviewResponse)) {
                        foreach ((array)($adjustmentReviewResponse['data'] ?? []) as $reviewLog) {
                            $entityId = strtolower(trim((string)($reviewLog['entity_id'] ?? '')));
                            if (!isValidUuid($entityId) || isset($reviewStatusByAdjustmentId[$entityId])) {
                                continue;
                            }

                            $newData = is_array($reviewLog['new_data'] ?? null) ? (array)$reviewLog['new_data'] : [];
                            $reviewStatus = strtolower(trim((string)($newData['review_status'] ?? $newData['status_to'] ?? $newData['status'] ?? 'pending')));
                            if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
                                $reviewStatus = 'pending';
                            }

                            $reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
                        }
                    } else {
                        $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll adjustment reviews', $adjustmentReviewResponse);
                    }

                    $recommendationLogsResponse = apiRequest(
                        'GET',
                        $supabaseUrl
                        . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                        . '&entity_name=eq.payroll_adjustments'
                        . '&action_name=eq.recommend_payroll_adjustment'
                        . '&entity_id=in.(' . $adjustmentFilter . ')'
                        . '&order=created_at.desc&limit=2600',
                        $headers
                    );

                    if (isSuccessful($recommendationLogsResponse)) {
                        foreach ((array)($recommendationLogsResponse['data'] ?? []) as $recommendationLog) {
                            $entityId = strtolower(trim((string)($recommendationLog['entity_id'] ?? '')));
                            if (!isValidUuid($entityId) || isset($staffRecommendationByAdjustmentId[$entityId])) {
                                continue;
                            }

                            $newData = is_array($recommendationLog['new_data'] ?? null) ? (array)$recommendationLog['new_data'] : [];
                            $recommendationStatus = strtolower(trim((string)($newData['recommendation_status'] ?? '')));
                            if (!in_array($recommendationStatus, ['approved', 'rejected'], true)) {
                                continue;
                            }

                            $staffRecommendationByAdjustmentId[$entityId] = [
                                'recommendation_status' => $recommendationStatus,
                                'submitted_at' => (string)($recommendationLog['created_at'] ?? ''),
                                'notes' => (string)($newData['notes'] ?? ''),
                            ];
                        }
                    } else {
                        $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll adjustment recommendations', $recommendationLogsResponse);
                    }
                }

                $batchItemById = [];
                foreach ($batchItemRows as $batchItemRow) {
                    $itemId = strtolower(trim((string)($batchItemRow['id'] ?? '')));
                    if (isValidUuid($itemId)) {
                        $batchItemById[$itemId] = $batchItemRow;
                    }
                }

                foreach ($adjustmentRowsForItems as $adjustmentRow) {
                    $adjustmentId = strtolower(trim((string)($adjustmentRow['id'] ?? '')));
                    $itemId = strtolower(trim((string)($adjustmentRow['payroll_item_id'] ?? '')));
                    if (!isValidUuid($adjustmentId) || !isValidUuid($itemId)) {
                        continue;
                    }

                    $itemRow = is_array($batchItemById[$itemId] ?? null) ? (array)$batchItemById[$itemId] : [];
                    $runId = (string)($itemRow['payroll_run_id'] ?? '');
                    if ($runId === '') {
                        continue;
                    }

                    $reviewStatus = strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending'));
                    if ($reviewStatus === 'approved') {
                        if (!isset($approvedAdjustmentByItemId[$itemId])) {
                            $approvedAdjustmentByItemId[$itemId] = [
                                'adjustment_earnings' => 0.0,
                                'adjustment_deductions' => 0.0,
                            ];
                        }

                        $amount = (float)($adjustmentRow['amount'] ?? 0);
                        $type = strtolower(trim((string)($adjustmentRow['adjustment_type'] ?? 'deduction')));
                        if ($type === 'earning') {
                            $approvedAdjustmentByItemId[$itemId]['adjustment_earnings'] += $amount;
                        } else {
                            $approvedAdjustmentByItemId[$itemId]['adjustment_deductions'] += $amount;
                        }
                    }

                    $recommendationRow = $staffRecommendationByAdjustmentId[$adjustmentId] ?? null;
                    if (!is_array($recommendationRow)) {
                        continue;
                    }

                    if (!isset($runAdjustmentSummaryByRun[$runId])) {
                        $runAdjustmentSummaryByRun[$runId] = [
                            'submitted_count' => 0,
                            'pending_count' => 0,
                            'approved_count' => 0,
                            'rejected_count' => 0,
                            'rows' => [],
                        ];
                    }

                    $runAdjustmentSummaryByRun[$runId]['submitted_count']++;
                    if ($reviewStatus === 'approved') {
                        $runAdjustmentSummaryByRun[$runId]['approved_count']++;
                    } elseif ($reviewStatus === 'rejected') {
                        $runAdjustmentSummaryByRun[$runId]['rejected_count']++;
                    } else {
                        $runAdjustmentSummaryByRun[$runId]['pending_count']++;
                    }

                    $employeeName = trim(((string)($itemRow['person']['first_name'] ?? '')) . ' ' . ((string)($itemRow['person']['surname'] ?? '')));
                    if ($employeeName === '') {
                        $employeeName = 'Employee';
                    }

                    $adjustmentType = strtolower(trim((string)($adjustmentRow['adjustment_type'] ?? 'adjustment')));
                    if ($adjustmentType === '') {
                        $adjustmentType = 'adjustment';
                    }

                    $runAdjustmentSummaryByRun[$runId]['rows'][] = [
                        'id' => $adjustmentId,
                        'adjustment_code' => (string)($adjustmentRow['adjustment_code'] ?? 'Adjustment'),
                        'employee_name' => $employeeName,
                        'adjustment_type_label' => ucwords(str_replace('_', ' ', $adjustmentType)),
                        'description' => (string)($adjustmentRow['description'] ?? '-'),
                        'amount' => (float)($adjustmentRow['amount'] ?? 0),
                        'staff_recommendation' => (string)($recommendationRow['recommendation_status'] ?? ''),
                        'staff_recommendation_label' => formatDateTimeForPhilippines((string)($recommendationRow['submitted_at'] ?? ''), 'M d, Y h:i A'),
                        'staff_recommendation_notes' => (string)($recommendationRow['notes'] ?? ''),
                        'admin_status_raw' => $reviewStatus,
                        'admin_status_label' => ucwords($reviewStatus),
                    ];
                }
            } else {
                $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll adjustments', $adjustmentsForItemsResponse);
            }
        }
    }

    foreach ($pagedBatchMetaRows as $batchMetaRow) {
        $runId = (string)($batchMetaRow['id'] ?? '');
        $runItems = (array)($batchItemsByRun[$runId] ?? []);
        $employeeIds = [];
        $totalGross = 0.0;
        $totalNet = 0.0;
        $breakdownRows = [];

        foreach ($runItems as $item) {
            $personId = (string)($item['person_id'] ?? '');
            if ($personId !== '') {
                $employeeIds[$personId] = true;
            }

            $itemId = strtolower(trim((string)($item['id'] ?? '')));
            $breakdown = is_array($batchItemBreakdownByItemId[$itemId] ?? null) ? (array)$batchItemBreakdownByItemId[$itemId] : [];
            $approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
                ? (array)$approvedAdjustmentByItemId[$itemId]
                : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
            $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
            $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);
            $grossPayAdjusted = (float)($item['gross_pay'] ?? 0) + $adjustmentEarnings;
            $netPayAdjusted = (float)($item['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

            $totalGross += $grossPayAdjusted;
            $totalNet += $netPayAdjusted;

            $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
            if ($employeeName === '') {
                $employeeName = 'Unknown Employee';
            }

            $breakdownRows[] = [
                'employee_name' => $employeeName,
                'basic_pay' => (float)($item['basic_pay'] ?? ($breakdown['basic_pay'] ?? 0)),
                'cto_pay' => (float)($item['overtime_pay'] ?? ($breakdown['cto_pay'] ?? 0)),
                'allowances_total' => (float)($item['allowances_total'] ?? ($breakdown['allowances_total'] ?? 0)),
                'gross_pay' => $grossPayAdjusted,
                'net_pay' => $netPayAdjusted,
                'statutory_deductions' => (float)($breakdown['statutory_deductions'] ?? 0),
                'timekeeping_deductions' => (float)($breakdown['timekeeping_deductions'] ?? 0),
                'adjustment_deductions' => $adjustmentDeductions,
                'adjustment_earnings' => $adjustmentEarnings,
                'absent_days' => (int)($breakdown['absent_days'] ?? 0),
                'late_minutes' => (int)($breakdown['late_minutes'] ?? 0),
                'undertime_hours' => (float)($breakdown['undertime_hours'] ?? 0),
            ];
        }

        usort($breakdownRows, static function (array $left, array $right): int {
            return strcmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
        });

        $adjustmentSummary = is_array($runAdjustmentSummaryByRun[$runId] ?? null)
            ? (array)$runAdjustmentSummaryByRun[$runId]
            : ['submitted_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'rows' => []];

        $batchBreakdownByRun[$runId] = [
            'employee_count' => count($employeeIds),
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
            'rows' => $breakdownRows,
            'adjustment_summary' => [
                'submitted_count' => (int)($adjustmentSummary['submitted_count'] ?? 0),
                'pending_count' => (int)($adjustmentSummary['pending_count'] ?? 0),
                'approved_count' => (int)($adjustmentSummary['approved_count'] ?? 0),
                'rejected_count' => (int)($adjustmentSummary['rejected_count'] ?? 0),
            ],
            'adjustment_rows' => array_values((array)($adjustmentSummary['rows'] ?? [])),
        ];

        $batchRows[] = [
            'id' => $runId,
            'period_id' => (string)($batchMetaRow['period_id'] ?? ''),
            'period_code' => (string)($batchMetaRow['period_code'] ?? ''),
            'period_label' => (string)($batchMetaRow['period_label'] ?? ''),
            'status' => (string)($batchMetaRow['status'] ?? 'draft'),
            'employee_count' => count($employeeIds),
            'total_gross' => $totalGross,
            'total_net' => $totalNet,
            'staff_recommendation' => (string)($batchMetaRow['staff_recommendation'] ?? 'Not yet submitted by Staff'),
            'staff_submitted_at' => (string)($batchMetaRow['staff_submitted_at'] ?? ''),
            'admin_review_decision' => (string)($batchMetaRow['admin_review_decision'] ?? ''),
            'admin_review_notes' => (string)($batchMetaRow['admin_review_notes'] ?? ''),
            'admin_reviewed_at' => (string)($batchMetaRow['admin_reviewed_at'] ?? ''),
            'adjustment_submitted_count' => (int)($adjustmentSummary['submitted_count'] ?? 0),
            'adjustment_pending_count' => (int)($adjustmentSummary['pending_count'] ?? 0),
            'adjustment_approved_count' => (int)($adjustmentSummary['approved_count'] ?? 0),
            'adjustment_rejected_count' => (int)($adjustmentSummary['rejected_count'] ?? 0),
        ];
    }

    if (!isSuccessful($batchRunsResponse)) {
        $dataLoadError = payrollManagementAppendQueryError($dataLoadError, 'payroll runs', $batchRunsResponse);
    }

    return;
}

$normalizeCompensationRow = static function (array $row): array {
    return payrollServiceNormalizeCompensationRow($row);
};

$payrollManagementLimits = [
    'employees' => 1000,
    'compensations' => 2500,
    'periods' => 180,
    'runs' => 250,
    'items' => 2500,
    'payslips' => 2500,
    'activity_logs' => 4000,
    'adjustments' => 4000,
];

$employeesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employment_records?select=person_id,employment_status,employment_type,person:people!employment_records_person_id_fkey(id,user_id,first_name,surname),position:job_positions(employment_classification)&is_current=eq.true&limit=' . (int)$payrollManagementLimits['employees'],
    $headers
);

$compensationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,created_at&order=effective_from.desc,created_at.desc&limit=' . (int)$payrollManagementLimits['compensations'],
    $headers
);

if (!isSuccessful($compensationsResponse)) {
    $compensationsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employee_compensations?select=id,person_id,monthly_rate,pay_frequency,effective_from,effective_to,created_at&order=effective_from.desc,created_at.desc&limit=' . (int)$payrollManagementLimits['compensations'],
        $headers
    );
}

$periodsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,created_at&order=period_end.desc&limit=' . (int)$payrollManagementLimits['periods'],
    $headers
);

$runsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,approved_at,generated_at,created_at,payroll_period:payroll_periods(period_code,period_start,period_end,payout_date,status),office:offices(office_name)&order=created_at.desc&limit=' . (int)$payrollManagementLimits['runs'],
    $headers
);

$payrollSyncSettingsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/system_settings?select=setting_key,setting_value&setting_key=in.(' . rawurlencode('payroll.sync.config,payroll.sync.last_deduction_import') . ')&limit=10',
    $headers
);

$itemsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,basic_pay,overtime_pay,allowances_total,gross_pay,deductions_total,net_pay,created_at,person:people(first_name,surname)&order=created_at.desc&limit=' . (int)$payrollManagementLimits['items'],
    $headers
);

$payslipsResponse = [
    'status' => 200,
    'data' => [],
    'raw' => '',
];
if ($payrollManagementLoadDeferredPayslips) {
    $payslipsResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,pdf_storage_path,released_at,created_at&order=created_at.desc&limit=' . (int)$payrollManagementLimits['payslips'],
        $headers
    );
}

$employmentRecords = isSuccessful($employeesResponse) ? (array)$employeesResponse['data'] : [];
$compensationRows = isSuccessful($compensationsResponse)
    ? array_map($normalizeCompensationRow, (array)$compensationsResponse['data'])
    : [];
$periodRows = isSuccessful($periodsResponse) ? (array)$periodsResponse['data'] : [];
$runRows = isSuccessful($runsResponse) ? (array)$runsResponse['data'] : [];
$itemRows = isSuccessful($itemsResponse) ? (array)$itemsResponse['data'] : [];
$payslipRows = isSuccessful($payslipsResponse) ? (array)$payslipsResponse['data'] : [];

$payrollSyncConfig = payrollSyncDefaultConfig();
$payrollLastDeductionImport = null;
if (isSuccessful($payrollSyncSettingsResponse)) {
    foreach ((array)($payrollSyncSettingsResponse['data'] ?? []) as $settingRow) {
        $settingKey = trim((string)($settingRow['setting_key'] ?? ''));
        $settingValue = $settingRow['setting_value'] ?? null;

        if ($settingKey === 'payroll.sync.config' && is_array($settingValue)) {
            $payrollSyncConfig = array_merge($payrollSyncConfig, $settingValue);
            continue;
        }

        if ($settingKey === 'payroll.sync.last_deduction_import' && is_array($settingValue)) {
            $payrollLastDeductionImport = $settingValue;
        }
    }
}

$payrollSourceLinksConfirmed = trim((string)($payrollSyncConfig['payroll_excel_url'] ?? '')) !== ''
    && trim((string)($payrollSyncConfig['payslip_excel_url'] ?? '')) !== '';
$payrollRuleSummary = [
    'permanent_timekeeping_source' => (string)($payrollSyncConfig['permanent_timekeeping_source'] ?? 'attendance'),
    'cos_timekeeping_source' => (string)($payrollSyncConfig['cos_timekeeping_source'] ?? 'import'),
];

$employeeRoleUserIds = [];
$employmentUserIds = [];
foreach ($employmentRecords as $record) {
    $person = is_array($record['person'] ?? null) ? (array)$record['person'] : [];
    $userId = strtolower(trim((string)($person['user_id'] ?? '')));
    if (!isValidUuid($userId)) {
        continue;
    }

    $employmentUserIds[$userId] = true;
}

if (!empty($employmentUserIds)) {
    $roleAssignmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles!user_role_assignments_role_id_fkey(role_key)&user_id=in.(' . implode(',', array_keys($employmentUserIds)) . ')&limit=' . (int)$payrollManagementLimits['compensations'],
        $headers
    );

    if (isSuccessful($roleAssignmentResponse)) {
        foreach ((array)($roleAssignmentResponse['data'] ?? []) as $assignment) {
            $assignmentUserId = strtolower(trim((string)($assignment['user_id'] ?? '')));
            if (!isValidUuid($assignmentUserId)) {
                continue;
            }

            $rolePayload = is_array($assignment['role'] ?? null) ? (array)$assignment['role'] : [];
            $roleKey = strtolower(trim((string)($rolePayload['role_key'] ?? '')));
            if ($roleKey !== 'employee') {
                continue;
            }

            $employeeRoleUserIds[$assignmentUserId] = true;
        }
    }
}

$itemBreakdownByItemId = [];
$itemIdsForBreakdown = [];
if ($payrollManagementLoadDetailedItemPayload) {
    foreach ($itemRows as $itemRow) {
        $itemId = strtolower(trim((string)($itemRow['id'] ?? '')));
        if (!isValidUuid($itemId)) {
            continue;
        }
        $itemIdsForBreakdown[$itemId] = true;
    }
}

if ($payrollManagementLoadDetailedItemPayload && !empty($itemIdsForBreakdown)) {
    $breakdownFilter = implode(',', array_keys($itemIdsForBreakdown));
    $itemBreakdownResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
        . '&entity_name=eq.payroll_items'
        . '&action_name=eq.compute_item_breakdown'
        . '&entity_id=in.(' . $breakdownFilter . ')'
        . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['activity_logs'],
        $headers
    );

    if (isSuccessful($itemBreakdownResponse)) {
        foreach ((array)($itemBreakdownResponse['data'] ?? []) as $breakdownLog) {
            $itemId = strtolower(trim((string)($breakdownLog['entity_id'] ?? '')));
            if (!isValidUuid($itemId) || isset($itemBreakdownByItemId[$itemId])) {
                continue;
            }

            $newData = is_array($breakdownLog['new_data'] ?? null) ? (array)$breakdownLog['new_data'] : [];
            $earnings = is_array($newData['earnings'] ?? null) ? (array)$newData['earnings'] : [];
            $deductions = is_array($newData['deductions'] ?? null) ? (array)$newData['deductions'] : [];
            $attendanceSource = is_array($newData['attendance_source'] ?? null) ? (array)$newData['attendance_source'] : [];

            $itemBreakdownByItemId[$itemId] = [
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
    } else {
        $raw = trim((string)($itemBreakdownResponse['raw'] ?? ''));
        $message = 'Payroll item breakdown query failed (HTTP ' . (int)($itemBreakdownResponse['status'] ?? 0) . ').';
        if ($raw !== '') {
            $message .= ' ' . $raw;
        }
        $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
    }
}

$approvedAdjustmentByItemId = [];
if ($payrollManagementLoadDetailedItemPayload && !empty($itemIdsForBreakdown)) {
    $adjustmentsForItemsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,amount'
        . '&payroll_item_id=in.(' . implode(',', array_keys($itemIdsForBreakdown)) . ')'
        . '&limit=' . (int)$payrollManagementLimits['adjustments'],
        $headers
    );

    if (isSuccessful($adjustmentsForItemsResponse)) {
        $adjustmentRowsForItems = (array)($adjustmentsForItemsResponse['data'] ?? []);
        $adjustmentIdsForItems = [];
        foreach ($adjustmentRowsForItems as $adjustmentRow) {
            $adjustmentId = strtolower(trim((string)($adjustmentRow['id'] ?? '')));
            if (!isValidUuid($adjustmentId)) {
                continue;
            }
            $adjustmentIdsForItems[$adjustmentId] = true;
        }

        $reviewStatusByAdjustmentId = [];
        if (!empty($adjustmentIdsForItems)) {
            $adjustmentFilter = implode(',', array_keys($adjustmentIdsForItems));
            $adjustmentReviewResponse = apiRequest(
                'GET',
                $supabaseUrl
                . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
                . '&entity_name=eq.payroll_adjustments'
                . '&action_name=eq.review_payroll_adjustment'
                . '&entity_id=in.(' . $adjustmentFilter . ')'
                . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['activity_logs'],
                $headers
            );

            if (isSuccessful($adjustmentReviewResponse)) {
                foreach ((array)($adjustmentReviewResponse['data'] ?? []) as $reviewLog) {
                    $entityId = strtolower(trim((string)($reviewLog['entity_id'] ?? '')));
                    if (!isValidUuid($entityId) || isset($reviewStatusByAdjustmentId[$entityId])) {
                        continue;
                    }

                    $newData = is_array($reviewLog['new_data'] ?? null) ? (array)$reviewLog['new_data'] : [];
                    $reviewStatus = strtolower(trim((string)($newData['review_status'] ?? $newData['status_to'] ?? $newData['status'] ?? 'pending')));
                    if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
                        $reviewStatus = 'pending';
                    }

                    $reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
                }
            }
        }

        foreach ($adjustmentRowsForItems as $adjustmentRow) {
            $adjustmentId = strtolower(trim((string)($adjustmentRow['id'] ?? '')));
            if (!isValidUuid($adjustmentId)) {
                continue;
            }

            $itemId = strtolower(trim((string)($adjustmentRow['payroll_item_id'] ?? '')));
            if (!isValidUuid($itemId)) {
                continue;
            }

            $reviewStatus = strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending'));
            if ($reviewStatus !== 'approved') {
                continue;
            }

            if (!isset($approvedAdjustmentByItemId[$itemId])) {
                $approvedAdjustmentByItemId[$itemId] = [
                    'adjustment_earnings' => 0.0,
                    'adjustment_deductions' => 0.0,
                ];
            }

            $amount = (float)($adjustmentRow['amount'] ?? 0);
            $type = strtolower(trim((string)($adjustmentRow['adjustment_type'] ?? 'deduction')));
            if ($type === 'earning') {
                $approvedAdjustmentByItemId[$itemId]['adjustment_earnings'] += $amount;
            } else {
                $approvedAdjustmentByItemId[$itemId]['adjustment_deductions'] += $amount;
            }
        }
    }
}

$dataLoadError = null;
$responseChecks = [
    ['employees', $employeesResponse],
    ['compensation', $compensationsResponse],
    ['payroll periods', $periodsResponse],
    ['payroll runs', $runsResponse],
    ['payroll items', $itemsResponse],
    ['payslips', $payslipsResponse],
];

if (isSuccessful($periodsResponse) && empty($periodRows)) {
    $effectiveMonths = [];
    foreach ($compensationRows as $row) {
        $effectiveFrom = trim((string)($row['effective_from'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
            continue;
        }

        $effectiveMonths[substr($effectiveFrom, 0, 7)] = true;
    }

    if (empty($effectiveMonths)) {
        $effectiveMonths[gmdate('Y-m')] = true;
    }

    ksort($effectiveMonths);

    $payload = [];
    foreach (array_keys($effectiveMonths) as $yearMonth) {
        $monthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $yearMonth . '-01', new DateTimeZone('UTC'));
        if (!$monthDate) {
            continue;
        }

        $firstDay = $monthDate->format('Y-m-01');
        $fifteenth = $monthDate->format('Y-m-15');
        $sixteenth = $monthDate->format('Y-m-16');
        $monthEnd = $monthDate->format('Y-m-t');

        $payload[] = [
            'period_code' => $yearMonth . '-A',
            'period_start' => $firstDay,
            'period_end' => $fifteenth,
            'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $fifteenth, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
            'status' => 'open',
        ];

        if ($sixteenth <= $monthEnd) {
            $payload[] = [
                'period_code' => $yearMonth . '-B',
                'period_start' => $sixteenth,
                'period_end' => $monthEnd,
                'payout_date' => DateTimeImmutable::createFromFormat('!Y-m-d', $monthEnd, new DateTimeZone('UTC'))->modify('+5 day')->format('Y-m-d'),
                'status' => 'open',
            ];
        }
    }

    if (!empty($payload)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/payroll_periods',
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );

        $periodsResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,created_at&order=period_end.desc&limit=' . (int)$payrollManagementLimits['periods'],
            $headers
        );

        if (isSuccessful($periodsResponse)) {
            $periodRows = (array)($periodsResponse['data'] ?? []);
        }
    }
}

foreach ($responseChecks as [$label, $response]) {
    if (isSuccessful($response)) {
        continue;
    }

    $message = ucfirst((string)$label) . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
}

$employeeMap = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($employeeMap[$personId])) {
        continue;
    }

    $personUserId = strtolower(trim((string)($record['person']['user_id'] ?? '')));
    if (!isValidUuid($personUserId) || !isset($employeeRoleUserIds[$personUserId])) {
        continue;
    }

    $firstName = trim((string)($record['person']['first_name'] ?? ''));
    $surname = trim((string)($record['person']['surname'] ?? ''));
    $displayName = trim($firstName . ' ' . $surname);
    if ($displayName === '') {
        $displayName = 'Unknown Employee';
    }

    $employeeMap[$personId] = [
        'id' => $personId,
        'name' => $displayName,
    ];
}

$latestCompensationByPerson = [];
foreach ($compensationRows as $row) {
    $personId = (string)($row['person_id'] ?? '');
    if ($personId === '' || isset($latestCompensationByPerson[$personId])) {
        continue;
    }
    $latestCompensationByPerson[$personId] = $row;
}

$setupCompensationByPerson = [];
$todayDate = gmdate('Y-m-d');
foreach ($compensationRows as $row) {
    $personId = (string)($row['person_id'] ?? '');
    if ($personId === '' || isset($setupCompensationByPerson[$personId])) {
        continue;
    }

    $effectiveFrom = (string)($row['effective_from'] ?? '');
    $effectiveTo = (string)($row['effective_to'] ?? '');
    if ($effectiveFrom === '' || $effectiveFrom > $todayDate) {
        continue;
    }
    if ($effectiveTo !== '' && $effectiveTo < $todayDate) {
        continue;
    }

    $setupCompensationByPerson[$personId] = $row;
}

foreach ($latestCompensationByPerson as $personId => $row) {
    if (!isset($setupCompensationByPerson[$personId])) {
        $setupCompensationByPerson[$personId] = $row;
    }
}

$employeesForSetup = array_values($employeeMap);
usort($employeesForSetup, static function (array $left, array $right): int {
    return strcmp((string)$left['name'], (string)$right['name']);
});

$employmentStatusByPerson = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    if ($personId === '' || isset($employmentStatusByPerson[$personId])) {
        continue;
    }

    $employmentStatus = strtolower(trim((string)($record['employment_status'] ?? 'active')));
    $employmentType = strtolower(trim((string)($record['employment_type'] ?? '')));
    $positionClassification = strtolower(trim((string)($record['position']['employment_classification'] ?? '')));
    $effectiveEmploymentMarker = $employmentType !== '' ? $employmentType : $positionClassification;
    $employmentStatusByPerson[$personId] = payrollServiceIsCosEmploymentStatus($employmentStatus, $effectiveEmploymentMarker) && $effectiveEmploymentMarker !== ''
        ? $effectiveEmploymentMarker
        : $employmentStatus;
}

$employeePickerRows = [];
foreach ($employeesForSetup as $employee) {
    $personId = (string)($employee['id'] ?? '');
    if ($personId === '') {
        continue;
    }

    $compensation = $setupCompensationByPerson[$personId] ?? null;
    $employeePickerRows[] = [
        'person_id' => $personId,
        'name' => (string)($employee['name'] ?? 'Unknown Employee'),
        'status' => (string)($employmentStatusByPerson[$personId] ?? 'active'),
        'monthly_rate' => is_array($compensation) ? (float)($compensation['monthly_rate'] ?? 0) : 0.0,
        'pay_frequency' => is_array($compensation) ? (string)($compensation['pay_frequency'] ?? 'semi_monthly') : 'semi_monthly',
    ];
}

$activeEmploymentPersonIds = [];
foreach ($employmentRecords as $record) {
    $personId = (string)($record['person_id'] ?? '');
    $employmentStatus = strtolower(trim((string)($record['employment_status'] ?? 'active')));
    if ($personId === '' || $employmentStatus !== 'active') {
        continue;
    }

    $activeEmploymentPersonIds[$personId] = true;
}

$generationPreviewRows = [];
$generationPreviewTotals = [
    'employee_count' => 0,
    'gross_pay' => 0.0,
    'net_pay' => 0.0,
];

foreach ($employeesForSetup as $employee) {
    $personId = (string)($employee['id'] ?? '');
    if ($personId === '' || !isset($activeEmploymentPersonIds[$personId])) {
        continue;
    }

    $compensation = $setupCompensationByPerson[$personId] ?? null;
    if (!is_array($compensation)) {
        continue;
    }

    $monthlyRate = (float)($compensation['monthly_rate'] ?? 0);
    $allowanceMonthly = max(0.0, (float)($compensation['allowance_total'] ?? 0));
    $basePayMonthly = isset($compensation['base_pay'])
        ? max(0.0, (float)$compensation['base_pay'])
        : max(0.0, $monthlyRate - $allowanceMonthly);
    $taxMonthly = max(0.0, (float)($compensation['tax_deduction'] ?? 0));
    $governmentMonthly = max(0.0, (float)($compensation['government_deductions'] ?? 0));
    $otherMonthly = max(0.0, (float)($compensation['other_deductions'] ?? 0));
    if ($monthlyRate <= 0) {
        continue;
    }

    $payFrequency = strtolower((string)($compensation['pay_frequency'] ?? 'semi_monthly'));
    $divisor = 1;
    if ($payFrequency === 'semi_monthly') {
        $divisor = 2;
    } elseif ($payFrequency === 'weekly') {
        $divisor = 4;
    }

    $basicPay = round($basePayMonthly / $divisor, 2);
    $allowancesTotal = round($allowanceMonthly / $divisor, 2);
    $deductionsTotal = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);
    $grossPay = round($basicPay + $allowancesTotal, 2);
    $netPay = round($grossPay - $deductionsTotal, 2);

    $generationPreviewRows[] = [
        'person_id' => $personId,
        'employee_name' => (string)($employee['name'] ?? 'Unknown Employee'),
        'pay_frequency' => $payFrequency,
        'gross_pay' => $grossPay,
        'net_pay' => $netPay,
    ];

    $generationPreviewTotals['employee_count']++;
    $generationPreviewTotals['gross_pay'] += $grossPay;
    $generationPreviewTotals['net_pay'] += $netPay;
}

$periodById = [];
foreach ($periodRows as $period) {
    $periodId = (string)($period['id'] ?? '');
    if ($periodId === '') {
        continue;
    }
    $periodById[$periodId] = $period;
}

$runById = [];
$batchRows = [];
$batchBreakdownByRun = [];
$itemsByRun = [];
$runIds = [];
foreach ($itemRows as $item) {
    $runId = (string)($item['payroll_run_id'] ?? '');
    if ($runId === '') {
        continue;
    }
    if (!isset($itemsByRun[$runId])) {
        $itemsByRun[$runId] = [];
    }
    $itemsByRun[$runId][] = $item;
}

foreach ($runRows as $run) {
    $runId = (string)($run['id'] ?? '');
    if ($runId === '') {
        continue;
    }

    $runById[$runId] = $run;
    $runIds[] = $runId;
}

$runHandoffById = [];
$runReviewById = [];
if (!empty($runIds)) {
    $runLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
        . '&entity_name=eq.payroll_runs'
        . '&entity_id=in.' . rawurlencode('(' . implode(',', $runIds) . ')')
        . '&action_name=in.' . rawurlencode('(submit_batch_for_admin_approval,review_batch)')
            . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['activity_logs'],
        $headers
    );

    if (isSuccessful($runLogsResponse)) {
        foreach ((array)($runLogsResponse['data'] ?? []) as $runLog) {
            $loggedRunId = (string)($runLog['entity_id'] ?? '');
            if ($loggedRunId === '') {
                continue;
            }

            $actionName = strtolower(trim((string)($runLog['action_name'] ?? '')));
            $newData = is_array($runLog['new_data'] ?? null) ? (array)$runLog['new_data'] : [];

            if ($actionName === 'submit_batch_for_admin_approval' && !isset($runHandoffById[$loggedRunId])) {
                $runHandoffById[$loggedRunId] = [
                    'staff_recommendation' => (string)(cleanText($newData['staff_recommendation'] ?? null) ?? 'Recommend approval'),
                    'submitted_at' => (string)(cleanText($runLog['created_at'] ?? null) ?? ''),
                ];
            }

            if ($actionName === 'review_batch' && !isset($runReviewById[$loggedRunId])) {
                $runReviewById[$loggedRunId] = [
                    'decision' => (string)(cleanText($newData['new_status'] ?? null) ?? cleanText($newData['status'] ?? null) ?? ''),
                    'review_notes' => (string)(cleanText($newData['notes'] ?? null) ?? cleanText($newData['comment'] ?? null) ?? ''),
                    'reviewed_at' => (string)(cleanText($runLog['created_at'] ?? null) ?? ''),
                ];
            }
        }
    }
}

foreach ($runRows as $run) {
    $runId = (string)($run['id'] ?? '');
    if ($runId === '') {
        continue;
    }

    $hasStaffSubmission = isset($runHandoffById[$runId]);
    $runStatusRaw = strtolower(trim((string)($run['run_status'] ?? 'draft')));
    $effectiveStatus = (!$hasStaffSubmission && $runStatusRaw === 'computed') ? 'pending_review' : $runStatusRaw;

    $period = is_array($run['payroll_period'] ?? null) ? $run['payroll_period'] : [];
    $periodCode = (string)($period['period_code'] ?? 'Uncoded Period');
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : $periodCode;

    $runItems = (array)($itemsByRun[$runId] ?? []);
    $employeeIds = [];
    $totalGross = 0.0;
    $totalNet = 0.0;
    $breakdownRows = [];
    foreach ($runItems as $item) {
        $personId = (string)($item['person_id'] ?? '');
        if ($personId !== '') {
            $employeeIds[$personId] = true;
        }

        $itemId = strtolower(trim((string)($item['id'] ?? '')));
        $breakdown = is_array($itemBreakdownByItemId[$itemId] ?? null) ? (array)$itemBreakdownByItemId[$itemId] : [];
        $approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
            ? (array)$approvedAdjustmentByItemId[$itemId]
            : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
        $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
        $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);
        $grossPayAdjusted = (float)($item['gross_pay'] ?? 0) + $adjustmentEarnings;
        $netPayAdjusted = (float)($item['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

        $totalGross += $grossPayAdjusted;
        $totalNet += $netPayAdjusted;

        $attendanceSource = is_array($breakdown['attendance_source'] ?? null) ? (array)$breakdown['attendance_source'] : [];
        $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
        if ($employeeName === '') {
            $employeeName = 'Unknown Employee';
        }

        $breakdownRows[] = [
            'employee_name' => $employeeName,
            'basic_pay' => (float)($item['basic_pay'] ?? ($breakdown['basic_pay'] ?? 0)),
            'cto_pay' => (float)($item['overtime_pay'] ?? ($breakdown['cto_pay'] ?? 0)),
            'allowances_total' => (float)($item['allowances_total'] ?? ($breakdown['allowances_total'] ?? 0)),
            'gross_pay' => $grossPayAdjusted,
            'net_pay' => $netPayAdjusted,
            'statutory_deductions' => (float)($breakdown['statutory_deductions'] ?? 0),
            'timekeeping_deductions' => (float)($breakdown['timekeeping_deductions'] ?? 0),
            'adjustment_deductions' => $adjustmentDeductions,
            'adjustment_earnings' => $adjustmentEarnings,
            'absent_days' => (int)($attendanceSource['absent_days'] ?? 0),
            'late_minutes' => (int)($attendanceSource['late_minutes'] ?? 0),
            'undertime_hours' => (float)($attendanceSource['undertime_hours'] ?? 0),
        ];
    }

    usort($breakdownRows, static function (array $left, array $right): int {
        return strcmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
    });

    $batchBreakdownByRun[$runId] = [
        'employee_count' => count($employeeIds),
        'total_gross' => $totalGross,
        'total_net' => $totalNet,
        'rows' => $breakdownRows,
    ];

    $batchRows[] = [
        'id' => $runId,
        'period_id' => (string)($run['payroll_period_id'] ?? ''),
        'period_code' => $periodCode,
        'period_label' => $periodLabel,
        'status' => $effectiveStatus,
        'employee_count' => count($employeeIds),
        'total_gross' => $totalGross,
        'total_net' => $totalNet,
        'staff_recommendation' => $hasStaffSubmission
            ? (string)($runHandoffById[$runId]['staff_recommendation'] ?? 'Recommend approval')
            : 'Not yet submitted by Staff',
        'staff_submitted_at' => (string)($runHandoffById[$runId]['submitted_at'] ?? ''),
        'admin_review_decision' => (string)($runReviewById[$runId]['decision'] ?? ''),
        'admin_review_notes' => (string)($runReviewById[$runId]['review_notes'] ?? ''),
        'admin_reviewed_at' => (string)($runReviewById[$runId]['reviewed_at'] ?? ''),
        'generated_at' => (string)($run['generated_at'] ?? ''),
    ];
}

$latestBatchByPeriod = [];
foreach ($batchRows as $row) {
    $periodId = (string)($row['period_id'] ?? '');
    if ($periodId === '' || isset($latestBatchByPeriod[$periodId])) {
        continue;
    }
    $latestBatchByPeriod[$periodId] = $row;
}

if ($payrollManagementLoadDeferredHistory) {
    foreach ($periodRows as $period) {
        $periodId = (string)($period['id'] ?? '');
        if ($periodId === '') {
            continue;
        }

        $periodCode = (string)($period['period_code'] ?? 'PR');
        $periodStart = (string)($period['period_start'] ?? '');
        $periodEnd = (string)($period['period_end'] ?? '');
        $periodLabel = $periodStart !== '' && $periodEnd !== ''
            ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
            : $periodCode;

        $batch = $latestBatchByPeriod[$periodId] ?? null;
        $payrollEstimateHistoryRows[] = [
            'period_id' => $periodId,
            'period_code' => $periodCode,
            'period_label' => $periodLabel,
            'status' => (string)($period['status'] ?? 'open'),
            'estimated_gross' => is_array($batch) ? (float)($batch['total_gross'] ?? 0) : 0.0,
            'estimated_net' => is_array($batch) ? (float)($batch['total_net'] ?? 0) : 0.0,
            'employee_count' => is_array($batch) ? (int)($batch['employee_count'] ?? 0) : 0,
        ];
    }
}

$periodsWithExistingRun = [];
foreach ($runRows as $run) {
    $periodId = (string)($run['payroll_period_id'] ?? '');
    $runStatus = strtolower(trim((string)($run['run_status'] ?? 'draft')));
    if ($periodId === '' || $runStatus === 'cancelled') {
        continue;
    }

    $periodsWithExistingRun[$periodId] = true;
}

$generationPeriodOptions = array_values(array_filter($periodRows, static function (array $period) use ($periodsWithExistingRun): bool {
    $periodId = (string)($period['id'] ?? '');
    $status = strtolower((string)($period['status'] ?? 'open'));

    if (!in_array($status, ['open', 'processing', 'posted'], true)) {
        return false;
    }

    if ($periodId !== '' && isset($periodsWithExistingRun[$periodId])) {
        return false;
    }

    return true;
}));

$buildGenerationPreviewForPeriod = static function (string $periodStart, string $periodEnd) use ($activeEmploymentPersonIds, $employeeMap, $compensationRows): array {
    $compensationByPerson = [];
    foreach ($compensationRows as $row) {
        $personId = (string)($row['person_id'] ?? '');
        if ($personId === '' || !isset($activeEmploymentPersonIds[$personId]) || isset($compensationByPerson[$personId])) {
            continue;
        }

        $effectiveFrom = (string)($row['effective_from'] ?? '');
        $effectiveTo = (string)($row['effective_to'] ?? '');
        if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
            continue;
        }
        if ($effectiveTo !== '' && $effectiveTo < $periodStart) {
            continue;
        }

        $compensationByPerson[$personId] = $row;
    }

    $rows = [];
    $totals = [
        'employee_count' => 0,
        'gross_pay' => 0.0,
        'net_pay' => 0.0,
    ];

    foreach ($activeEmploymentPersonIds as $personId => $_active) {
        $compensation = $compensationByPerson[$personId] ?? null;
        if (!is_array($compensation)) {
            continue;
        }

        $monthlyRate = (float)($compensation['monthly_rate'] ?? 0);
        $allowanceMonthly = max(0.0, (float)($compensation['allowance_total'] ?? 0));
        $basePayMonthly = isset($compensation['base_pay'])
            ? max(0.0, (float)$compensation['base_pay'])
            : max(0.0, $monthlyRate - $allowanceMonthly);
        $taxMonthly = max(0.0, (float)($compensation['tax_deduction'] ?? 0));
        $governmentMonthly = max(0.0, (float)($compensation['government_deductions'] ?? 0));
        $otherMonthly = max(0.0, (float)($compensation['other_deductions'] ?? 0));
        if ($monthlyRate <= 0) {
            continue;
        }

        $payFrequency = strtolower((string)($compensation['pay_frequency'] ?? 'semi_monthly'));
        $divisor = 1;
        if ($payFrequency === 'semi_monthly') {
            $divisor = 2;
        } elseif ($payFrequency === 'weekly') {
            $divisor = 4;
        }

        $basicPay = round($basePayMonthly / $divisor, 2);
        $allowancesTotal = round($allowanceMonthly / $divisor, 2);
        $deductionsTotal = round(($taxMonthly + $governmentMonthly + $otherMonthly) / $divisor, 2);
        $grossPay = round($basicPay + $allowancesTotal, 2);
        $netPay = round($grossPay - $deductionsTotal, 2);

        $rows[] = [
            'person_id' => $personId,
            'employee_name' => (string)($employeeMap[$personId]['name'] ?? 'Unknown Employee'),
            'pay_frequency' => $payFrequency,
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
        ];

        $totals['employee_count']++;
        $totals['gross_pay'] += $grossPay;
        $totals['net_pay'] += $netPay;
    }

    usort($rows, static function (array $left, array $right): int {
        return strcmp((string)($left['employee_name'] ?? ''), (string)($right['employee_name'] ?? ''));
    });

    return [
        'rows' => $rows,
        'totals' => $totals,
    ];
};

$generationPreviewByPeriod = [];
foreach ($generationPeriodOptions as $period) {
    $periodId = (string)($period['id'] ?? '');
    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    if ($periodId === '' || $periodStart === '' || $periodEnd === '') {
        continue;
    }

    $generationPreviewByPeriod[$periodId] = $buildGenerationPreviewForPeriod($periodStart, $periodEnd);
}

if ($payrollManagementLoadDeferredHistory) {
    foreach ($payrollEstimateHistoryRows as &$historyRow) {
        $historyPeriodId = (string)($historyRow['period_id'] ?? '');
        if ($historyPeriodId === '') {
            continue;
        }

        $hasBatchValues = ((int)($historyRow['employee_count'] ?? 0) > 0)
            || ((float)($historyRow['estimated_gross'] ?? 0) > 0)
            || ((float)($historyRow['estimated_net'] ?? 0) > 0);
        if ($hasBatchValues) {
            continue;
        }

        $preview = $generationPreviewByPeriod[$historyPeriodId] ?? null;
        if (!is_array($preview)) {
            continue;
        }

        $previewTotals = (array)($preview['totals'] ?? []);
        $historyRow['employee_count'] = (int)($previewTotals['employee_count'] ?? 0);
        $historyRow['estimated_gross'] = (float)($previewTotals['gross_pay'] ?? 0);
        $historyRow['estimated_net'] = (float)($previewTotals['net_pay'] ?? 0);
    }
    unset($historyRow);
}

if (!empty($generationPeriodOptions)) {
    $defaultPeriodId = (string)($generationPeriodOptions[0]['id'] ?? '');
    $defaultPreview = is_array($generationPreviewByPeriod[$defaultPeriodId] ?? null) ? $generationPreviewByPeriod[$defaultPeriodId] : null;
    if (is_array($defaultPreview)) {
        $generationPreviewRows = (array)($defaultPreview['rows'] ?? []);
        $generationPreviewTotals = (array)($defaultPreview['totals'] ?? ['employee_count' => 0, 'gross_pay' => 0.0, 'net_pay' => 0.0]);
    }
}

$currentCutoffLabel = 'No payroll period found';
$currentCutoffEmployeeCount = 0;
$currentCutoffGross = 0.0;
$currentCutoffNet = 0.0;

if (!empty($periodRows)) {
    $latestPeriod = $periodRows[0];
    $latestPeriodId = (string)($latestPeriod['id'] ?? '');
    $periodCode = (string)($latestPeriod['period_code'] ?? 'Current Cutoff');
    $periodStart = (string)($latestPeriod['period_start'] ?? '');
    $periodEnd = (string)($latestPeriod['period_end'] ?? '');

    $currentCutoffLabel = $periodStart !== '' && $periodEnd !== ''
        ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        : $periodCode;

    $personCounter = [];
    foreach ($itemRows as $item) {
        $runId = (string)($item['payroll_run_id'] ?? '');
        if ($runId === '') {
            continue;
        }

        $run = $runById[$runId] ?? null;
        if (!is_array($run) || (string)($run['payroll_period_id'] ?? '') !== $latestPeriodId) {
            continue;
        }

        $personId = (string)($item['person_id'] ?? '');
        if ($personId !== '') {
            $personCounter[$personId] = true;
        }

        $itemId = strtolower(trim((string)($item['id'] ?? '')));
        $approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
            ? (array)$approvedAdjustmentByItemId[$itemId]
            : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
        $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
        $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);

        $currentCutoffGross += (float)($item['gross_pay'] ?? 0) + $adjustmentEarnings;
        $currentCutoffNet += (float)($item['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;
    }

    $currentCutoffEmployeeCount = count($personCounter);
}

if ($currentCutoffEmployeeCount === 0 && !empty($periodRows)) {
    $latestPeriod = $periodRows[0];
    $latestPeriodId = (string)($latestPeriod['id'] ?? '');
    $previewForLatest = is_array($generationPreviewByPeriod[$latestPeriodId] ?? null)
        ? (array)$generationPreviewByPeriod[$latestPeriodId]
        : null;

    if (is_array($previewForLatest)) {
        $latestTotals = (array)($previewForLatest['totals'] ?? []);
        $currentCutoffEmployeeCount = (int)($latestTotals['employee_count'] ?? 0);
        $currentCutoffGross = (float)($latestTotals['gross_pay'] ?? 0);
        $currentCutoffNet = (float)($latestTotals['net_pay'] ?? 0);
    }
}

$payslipByPayrollItem = [];
if ($payrollManagementLoadDeferredPayslips) {
    foreach ($payslipRows as $row) {
        $payrollItemId = (string)($row['payroll_item_id'] ?? '');
        if ($payrollItemId === '' || isset($payslipByPayrollItem[$payrollItemId])) {
            continue;
        }
        $payslipByPayrollItem[$payrollItemId] = $row;
    }

    foreach ($itemRows as $item) {
        $itemId = (string)($item['id'] ?? '');
        $runId = (string)($item['payroll_run_id'] ?? '');
        if ($itemId === '' || $runId === '') {
            continue;
        }

        $run = $runById[$runId] ?? null;
        if (!is_array($run)) {
            continue;
        }

        $period = is_array($run['payroll_period'] ?? null) ? $run['payroll_period'] : [];
        $periodStart = (string)($period['period_start'] ?? '');
        $periodEnd = (string)($period['period_end'] ?? '');
        $periodLabel = $periodStart !== '' && $periodEnd !== ''
            ? date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
            : (string)($period['period_code'] ?? 'Uncoded Period');

        $employeeName = trim(((string)($item['person']['first_name'] ?? '')) . ' ' . ((string)($item['person']['surname'] ?? '')));
        if ($employeeName === '') {
            $employeeName = 'Unknown Employee';
        }

        $payslip = $payslipByPayrollItem[$itemId] ?? null;
        $releasedAt = cleanText($payslip['released_at'] ?? null);
        $status = $releasedAt ? 'released' : 'pending';
        $approvedAdjustment = is_array($approvedAdjustmentByItemId[strtolower($itemId)] ?? null)
            ? (array)$approvedAdjustmentByItemId[strtolower($itemId)]
            : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
        $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);
        $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
        $netPayAdjusted = (float)($item['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

        $payslipTableRows[] = [
            'item_id' => $itemId,
            'employee_name' => $employeeName,
            'period_label' => $periodLabel,
            'net_pay' => $netPayAdjusted,
            'status' => $status,
            'payslip_no' => (string)($payslip['payslip_no'] ?? '-'),
            'pdf_storage_path' => cleanText($payslip['pdf_storage_path'] ?? null),
        ];
    }
}

$releaseEligibleRuns = array_values(array_filter($batchRows, static function (array $row): bool {
    $status = strtolower((string)($row['status'] ?? 'draft'));
    return $status === 'approved';
}));

$adjustmentStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'approved') {
        return ['Approved', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'rejected') {
        return ['Rejected', 'bg-rose-100 text-rose-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};

$payrollAdjustmentRows = [];
$runAdjustmentSummaryByRun = [];
$adjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_adjustments?select=id,adjustment_type,adjustment_code,description,amount,created_at,item:payroll_items(id,payroll_run_id,person_id,person:people(id,first_name,middle_name,surname),run:payroll_runs(id,payroll_period_id,period:payroll_periods(period_code)))'
    . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['adjustments'],
    $headers
);

if (isSuccessful($adjustmentsResponse)) {
    $adjustmentRows = (array)($adjustmentsResponse['data'] ?? []);
    $adjustmentIds = [];
    foreach ($adjustmentRows as $adjustment) {
        $adjustmentId = strtolower(trim((string)($adjustment['id'] ?? '')));
        if (!isValidUuid($adjustmentId)) {
            continue;
        }
        $adjustmentIds[$adjustmentId] = true;
    }

    $reviewStatusByAdjustmentId = [];
    $staffRecommendationByAdjustmentId = [];
    if (!empty($adjustmentIds)) {
        $adjustmentFilter = implode(',', array_keys($adjustmentIds));

        $reviewLogsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.payroll_adjustments'
            . '&action_name=eq.review_payroll_adjustment'
            . '&entity_id=in.(' . $adjustmentFilter . ')'
            . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['activity_logs'],
            $headers
        );

        if (isSuccessful($reviewLogsResponse)) {
            foreach ((array)($reviewLogsResponse['data'] ?? []) as $reviewLog) {
                $entityId = strtolower(trim((string)($reviewLog['entity_id'] ?? '')));
                if (!isValidUuid($entityId) || isset($reviewStatusByAdjustmentId[$entityId])) {
                    continue;
                }

                $newData = is_array($reviewLog['new_data'] ?? null) ? (array)$reviewLog['new_data'] : [];
                $reviewStatus = strtolower(trim((string)($newData['review_status'] ?? $newData['status_to'] ?? $newData['status'] ?? 'pending')));
                if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
                    $reviewStatus = 'pending';
                }

                $reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
            }
        }

        $recommendationLogsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
            . '&entity_name=eq.payroll_adjustments'
            . '&action_name=eq.recommend_payroll_adjustment'
            . '&entity_id=in.(' . $adjustmentFilter . ')'
            . '&order=created_at.desc&limit=' . (int)$payrollManagementLimits['activity_logs'],
            $headers
        );

        if (isSuccessful($recommendationLogsResponse)) {
            foreach ((array)($recommendationLogsResponse['data'] ?? []) as $recommendationLog) {
                $entityId = strtolower(trim((string)($recommendationLog['entity_id'] ?? '')));
                if (!isValidUuid($entityId) || isset($staffRecommendationByAdjustmentId[$entityId])) {
                    continue;
                }

                $newData = is_array($recommendationLog['new_data'] ?? null) ? (array)$recommendationLog['new_data'] : [];
                $recommendationStatus = strtolower(trim((string)($newData['recommendation_status'] ?? '')));
                if (!in_array($recommendationStatus, ['approved', 'rejected'], true)) {
                    continue;
                }

                $staffRecommendationByAdjustmentId[$entityId] = [
                    'recommendation_status' => $recommendationStatus,
                    'submitted_at' => (string)($recommendationLog['created_at'] ?? ''),
                    'notes' => (string)($newData['notes'] ?? ''),
                ];
            }
        }
    }

    foreach ($adjustmentRows as $adjustment) {
        $adjustmentId = strtolower(trim((string)($adjustment['id'] ?? '')));
        if (!isValidUuid($adjustmentId)) {
            continue;
        }

        $recommendationRow = $staffRecommendationByAdjustmentId[$adjustmentId] ?? null;
        if (!is_array($recommendationRow)) {
            continue;
        }

        $itemRow = is_array($adjustment['item'] ?? null) ? (array)$adjustment['item'] : [];
        $runId = trim((string)($itemRow['payroll_run_id'] ?? ''));
        if (!isValidUuid($runId)) {
            continue;
        }
        $personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
        $runRow = is_array($itemRow['run'] ?? null) ? (array)$itemRow['run'] : [];
        $periodRow = is_array($runRow['period'] ?? null) ? (array)$runRow['period'] : [];

        $employeeName = trim(implode(' ', array_filter([
            trim((string)($personRow['first_name'] ?? '')),
            trim((string)($personRow['middle_name'] ?? '')),
            trim((string)($personRow['surname'] ?? '')),
        ])));
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        $reviewStatus = (string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending');
        [$statusLabel, $statusClass] = $adjustmentStatusPill($reviewStatus);

        $adjustmentType = strtolower(trim((string)($adjustment['adjustment_type'] ?? 'adjustment')));
        if ($adjustmentType === '') {
            $adjustmentType = 'adjustment';
        }

        $payrollAdjustmentRows[] = [
            'id' => $adjustmentId,
            'run_id' => $runId,
            'adjustment_code' => (string)($adjustment['adjustment_code'] ?? 'Adjustment'),
            'employee_name' => $employeeName,
            'period_code' => (string)($periodRow['period_code'] ?? '-'),
            'adjustment_type_label' => ucwords(str_replace('_', ' ', $adjustmentType)),
            'description' => (string)($adjustment['description'] ?? '-'),
            'amount' => (float)($adjustment['amount'] ?? 0),
            'status_raw' => $reviewStatus,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'staff_recommendation' => (string)($recommendationRow['recommendation_status'] ?? ''),
            'staff_recommendation_label' => formatDateTimeForPhilippines((string)($recommendationRow['submitted_at'] ?? ''), 'M d, Y h:i A'),
            'staff_recommendation_notes' => (string)($recommendationRow['notes'] ?? ''),
        ];

        if (!isset($runAdjustmentSummaryByRun[$runId])) {
            $runAdjustmentSummaryByRun[$runId] = [
                'submitted_count' => 0,
                'pending_count' => 0,
                'approved_count' => 0,
                'rejected_count' => 0,
                'rows' => [],
            ];
        }

        $runAdjustmentSummaryByRun[$runId]['submitted_count']++;
        if ($reviewStatus === 'approved') {
            $runAdjustmentSummaryByRun[$runId]['approved_count']++;
        } elseif ($reviewStatus === 'rejected') {
            $runAdjustmentSummaryByRun[$runId]['rejected_count']++;
        } else {
            $runAdjustmentSummaryByRun[$runId]['pending_count']++;
        }

        $runAdjustmentSummaryByRun[$runId]['rows'][] = [
            'id' => $adjustmentId,
            'adjustment_code' => (string)($adjustment['adjustment_code'] ?? 'Adjustment'),
            'employee_name' => $employeeName,
            'adjustment_type_label' => ucwords(str_replace('_', ' ', $adjustmentType)),
            'description' => (string)($adjustment['description'] ?? '-'),
            'amount' => (float)($adjustment['amount'] ?? 0),
            'staff_recommendation' => (string)($recommendationRow['recommendation_status'] ?? ''),
            'staff_recommendation_label' => formatDateTimeForPhilippines((string)($recommendationRow['submitted_at'] ?? ''), 'M d, Y h:i A'),
            'staff_recommendation_notes' => (string)($recommendationRow['notes'] ?? ''),
            'admin_status_raw' => $reviewStatus,
            'admin_status_label' => $statusLabel,
        ];
    }

    foreach ($batchRows as &$batchRow) {
        $runId = (string)($batchRow['id'] ?? '');
        $summary = is_array($runAdjustmentSummaryByRun[$runId] ?? null)
            ? (array)$runAdjustmentSummaryByRun[$runId]
            : ['submitted_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'rows' => []];

        $batchRow['adjustment_submitted_count'] = (int)($summary['submitted_count'] ?? 0);
        $batchRow['adjustment_pending_count'] = (int)($summary['pending_count'] ?? 0);
        $batchRow['adjustment_approved_count'] = (int)($summary['approved_count'] ?? 0);
        $batchRow['adjustment_rejected_count'] = (int)($summary['rejected_count'] ?? 0);
    }
    unset($batchRow);

    foreach ($batchBreakdownByRun as $runId => &$breakdownRow) {
        $summary = is_array($runAdjustmentSummaryByRun[$runId] ?? null)
            ? (array)$runAdjustmentSummaryByRun[$runId]
            : ['submitted_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'rows' => []];

        $breakdownRow['adjustment_summary'] = [
            'submitted_count' => (int)($summary['submitted_count'] ?? 0),
            'pending_count' => (int)($summary['pending_count'] ?? 0),
            'approved_count' => (int)($summary['approved_count'] ?? 0),
            'rejected_count' => (int)($summary['rejected_count'] ?? 0),
        ];
        $breakdownRow['adjustment_rows'] = array_values((array)($summary['rows'] ?? []));
    }
    unset($breakdownRow);
} else {
    $raw = trim((string)($adjustmentsResponse['raw'] ?? ''));
    $message = 'Payroll adjustments query failed (HTTP ' . (int)($adjustmentsResponse['status'] ?? 0) . ').';
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
}

if ($payrollManagementLoadDeferredHistory) {
    $todayDateTs = strtotime(gmdate('Y-m-d'));
    foreach ($compensationRows as $row) {
        $setupId = (string)($row['id'] ?? '');
        $personId = (string)($row['person_id'] ?? '');
        if ($setupId === '' || $personId === '') {
            continue;
        }

        $effectiveFrom = (string)($row['effective_from'] ?? '');
        $effectiveTo = (string)($row['effective_to'] ?? '');
        $fromTs = $effectiveFrom !== '' ? strtotime($effectiveFrom) : false;
        $toTs = $effectiveTo !== '' ? strtotime($effectiveTo) : false;

        $timelineStatus = 'current';
        if ($fromTs !== false && $fromTs > $todayDateTs) {
            $timelineStatus = 'scheduled';
        } elseif ($toTs !== false && $toTs < $todayDateTs) {
            $timelineStatus = 'ended';
        }

        $salarySetupLogRows[] = [
            'id' => $setupId,
            'person_id' => $personId,
            'employee_name' => (string)($employeeMap[$personId]['name'] ?? ('Employee #' . strtoupper(substr(str_replace('-', '', $personId), 0, 6)))),
            'monthly_rate' => (float)($row['monthly_rate'] ?? 0),
            'pay_frequency' => (string)($row['pay_frequency'] ?? 'semi_monthly'),
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'status' => $timelineStatus,
        ];
    }
}

if ($payrollManagementDataStage === 'payslip-detail') {
    $targetItemId = strtolower(trim((string)($_GET['item_id'] ?? '')));
    if (isValidUuid($targetItemId)) {
        foreach ($payslipTableRows as $row) {
            if (strtolower((string)($row['item_id'] ?? '')) !== $targetItemId) {
                continue;
            }

            $breakdown = (array)($itemBreakdownByItemId[$targetItemId] ?? []);
            $approvedAdjustment = is_array($approvedAdjustmentByItemId[$targetItemId] ?? null)
                ? (array)$approvedAdjustmentByItemId[$targetItemId]
                : ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
            $statusLabel = strtolower((string)($row['status'] ?? 'pending')) === 'released' ? 'Released' : 'Pending';

            $statutoryDeductions = (float)($breakdown['statutory_deductions'] ?? 0);
            $timekeepingDeductions = (float)($breakdown['timekeeping_deductions'] ?? 0);
            $adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);
            $adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
            $grossPay = 0.0;
            $deductionsTotal = 0.0;

            foreach ($itemRows as $item) {
                if (strtolower((string)($item['id'] ?? '')) !== $targetItemId) {
                    continue;
                }

                $grossPay = (float)($item['gross_pay'] ?? 0) + $adjustmentEarnings;
                $deductionsTotal = (float)($item['deductions_total'] ?? 0) + $adjustmentDeductions;
                break;
            }

            $adminPayslipBreakdownPayload = [
                'employee_name' => (string)($row['employee_name'] ?? '-'),
                'period_label' => (string)($row['period_label'] ?? '-'),
                'payslip_no' => (string)($row['payslip_no'] ?? '-'),
                'status_label' => $statusLabel,
                'basic_pay' => (float)($breakdown['basic_pay'] ?? 0),
                'cto_pay' => (float)($breakdown['cto_pay'] ?? 0),
                'allowances_total' => (float)($breakdown['allowances_total'] ?? 0),
                'gross_pay' => $grossPay,
                'statutory_deductions' => $statutoryDeductions,
                'timekeeping_deductions' => $timekeepingDeductions,
                'adjustment_deductions' => $adjustmentDeductions,
                'adjustment_earnings' => $adjustmentEarnings,
                'absent_days' => (int)($breakdown['absent_days'] ?? 0),
                'late_minutes' => (int)($breakdown['late_minutes'] ?? 0),
                'undertime_hours' => (float)($breakdown['undertime_hours'] ?? 0),
                'deductions_total' => $deductionsTotal,
                'net_pay' => (float)($row['net_pay'] ?? 0),
            ];
            break;
        }
    }

    return;
}
