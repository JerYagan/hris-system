<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$payrollSummary = [
    'latest_period_label' => '-',
    'latest_net_pay' => 0.0,
    'latest_gross_pay' => 0.0,
    'latest_deductions' => 0.0,
];

$employeePayrollRows = [];
$payrollYears = [];

$payFrequencyDivisor = static function (?string $payFrequency): float {
    $frequency = strtolower(trim((string)$payFrequency));
    return match ($frequency) {
        'monthly' => 1.0,
        'weekly' => 4.0,
        default => 2.0,
    };
};

$resolveAdjustmentDescription = static function (array $adjustment): string {
    $description = trim((string)($adjustment['description'] ?? ''));
    $code = strtoupper(trim((string)($adjustment['adjustment_code'] ?? '')));

    if ($description !== '') {
        return $description;
    }

    if ($code === '') {
        return 'Payroll Entry';
    }

    if (strpos($code, 'SSS') !== false) {
        return 'SSS Contribution';
    }

    if (strpos($code, 'PAG') !== false || strpos($code, 'HDMF') !== false) {
        return 'Pag-IBIG Contribution';
    }

    if (strpos($code, 'PHILHEALTH') !== false || strpos($code, 'PHIC') !== false) {
        return 'PhilHealth Contribution';
    }

    if (strpos($code, 'TAX') !== false || strpos($code, 'WITHHOLD') !== false) {
        return 'Withholding Tax';
    }

    if (strpos($code, 'GOV') !== false || strpos($code, 'STAT') !== false) {
        return 'Government Contributions (SSS/Pag-IBIG/PhilHealth)';
    }

    return ucwords(strtolower(str_replace('_', ' ', $code)));
};

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$payrollItemsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_items?select=id,payroll_run_id,gross_pay,deductions_total,net_pay,created_at,updated_at,payroll_run:payroll_runs(run_status,payroll_period:payroll_periods(period_code,period_start,period_end,payout_date,status))'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc,created_at.desc&limit=400',
    $headers
);

if (!isSuccessful($payrollItemsResponse)) {
    $dataLoadError = 'Unable to load payroll history right now.';
    return;
}

$itemRows = (array)($payrollItemsResponse['data'] ?? []);
if (empty($itemRows)) {
    return;
}

$payrollItemIds = [];
foreach ($itemRows as $itemRaw) {
    $item = (array)$itemRaw;
    $itemId = (string)($item['id'] ?? '');
    if ($itemId !== '') {
        $payrollItemIds[] = $itemId;
    }
}

if (empty($payrollItemIds)) {
    return;
}

$compensationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employee_compensations?select=id,effective_from,effective_to,pay_frequency,tax_deduction,government_deductions,other_deductions'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=effective_from.desc&limit=200',
    $headers
);

$compensationRows = isSuccessful($compensationResponse)
    ? (array)($compensationResponse['data'] ?? [])
    : [];

$inClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $payrollItemIds));

$payslipsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payslips?select=id,payroll_item_id,payslip_no,pdf_storage_path,released_at,viewed_at,created_at'
    . '&payroll_item_id=in.(' . $inClause . ')'
    . '&order=created_at.desc&limit=400',
    $headers
);

$adjustmentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,adjustment_code,description,amount,created_at'
    . '&payroll_item_id=in.(' . $inClause . ')'
    . '&order=created_at.asc&limit=2000',
    $headers
);

$breakdownByPayrollItem = [];
$itemBreakdownResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
    . '&entity_name=eq.payroll_items'
    . '&action_name=eq.compute_item_breakdown'
    . '&entity_id=in.(' . $inClause . ')'
    . '&order=created_at.desc&limit=10000',
    $headers
);

if (isSuccessful($itemBreakdownResponse)) {
    foreach ((array)($itemBreakdownResponse['data'] ?? []) as $logRaw) {
        $logRow = (array)$logRaw;
        $itemId = (string)($logRow['entity_id'] ?? '');
        if ($itemId === '' || isset($breakdownByPayrollItem[$itemId])) {
            continue;
        }

        $newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];
        $earnings = is_array($newData['earnings'] ?? null) ? (array)$newData['earnings'] : [];
        $deductions = is_array($newData['deductions'] ?? null) ? (array)$newData['deductions'] : [];
        $attendance = is_array($newData['attendance_source'] ?? null) ? (array)$newData['attendance_source'] : [];

        $breakdownByPayrollItem[$itemId] = [
            'basic_pay' => (float)($earnings['basic_pay'] ?? 0),
            'cto_pay' => (float)($earnings['cto_pay'] ?? 0),
            'allowances_total' => (float)($earnings['allowances_total'] ?? 0),
            'statutory_deductions' => (float)($deductions['statutory_deductions'] ?? 0),
            'timekeeping_deductions' => (float)($deductions['timekeeping_deductions'] ?? 0),
            'absent_days' => (int)($attendance['absent_days'] ?? 0),
            'late_minutes' => (int)($attendance['late_minutes'] ?? 0),
            'undertime_hours' => (float)($attendance['undertime_hours'] ?? 0),
        ];
    }
}

$payslipByPayrollItem = [];
if (isSuccessful($payslipsResponse)) {
    foreach ((array)($payslipsResponse['data'] ?? []) as $payslipRaw) {
        $payslip = (array)$payslipRaw;
        $payrollItemId = (string)($payslip['payroll_item_id'] ?? '');
        if ($payrollItemId === '' || isset($payslipByPayrollItem[$payrollItemId])) {
            continue;
        }
        $payslipByPayrollItem[$payrollItemId] = $payslip;
    }
}

$adjustmentsByPayrollItem = [];
$reviewStatusByAdjustmentId = [];
$rawAdjustmentRows = isSuccessful($adjustmentsResponse)
    ? (array)($adjustmentsResponse['data'] ?? [])
    : [];

$adjustmentIds = [];
foreach ($rawAdjustmentRows as $adjustmentRaw) {
    $adjustment = (array)$adjustmentRaw;
    $adjustmentId = (string)($adjustment['id'] ?? '');
    if ($adjustmentId !== '') {
        $adjustmentIds[] = $adjustmentId;
    }
}

if (!empty($adjustmentIds)) {
    $adjustmentInClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $adjustmentIds));
    $adjustmentReviewResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
        . '&entity_name=eq.payroll_adjustments'
        . '&action_name=eq.review_payroll_adjustment'
        . '&entity_id=in.(' . $adjustmentInClause . ')'
        . '&order=created_at.desc&limit=10000',
        $headers
    );

    if (isSuccessful($adjustmentReviewResponse)) {
        foreach ((array)($adjustmentReviewResponse['data'] ?? []) as $reviewRaw) {
            $review = (array)$reviewRaw;
            $adjustmentId = (string)($review['entity_id'] ?? '');
            if ($adjustmentId === '' || isset($reviewStatusByAdjustmentId[$adjustmentId])) {
                continue;
            }

            $newData = is_array($review['new_data'] ?? null) ? (array)$review['new_data'] : [];
            $status = strtolower((string)(
                cleanText($newData['review_status'] ?? null)
                ?? cleanText($newData['status_to'] ?? null)
                ?? cleanText($newData['status'] ?? null)
                ?? 'pending'
            ));

            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $status = 'pending';
            }

            $reviewStatusByAdjustmentId[$adjustmentId] = $status;
        }
    }
}

foreach ($rawAdjustmentRows as $adjustmentRaw) {
    $adjustment = (array)$adjustmentRaw;
    $adjustmentId = (string)($adjustment['id'] ?? '');
    if ($adjustmentId === '') {
        continue;
    }

    if (strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending')) !== 'approved') {
        continue;
    }

    $payrollItemId = (string)($adjustment['payroll_item_id'] ?? '');
    if ($payrollItemId === '') {
        continue;
    }

    if (!isset($adjustmentsByPayrollItem[$payrollItemId])) {
        $adjustmentsByPayrollItem[$payrollItemId] = [];
    }

    $adjustmentsByPayrollItem[$payrollItemId][] = [
        'id' => $adjustmentId,
        'adjustment_type' => strtolower((string)($adjustment['adjustment_type'] ?? 'deduction')),
        'adjustment_code' => (string)($adjustment['adjustment_code'] ?? ''),
        'description' => $resolveAdjustmentDescription($adjustment),
        'amount' => (float)($adjustment['amount'] ?? 0),
    ];
}

foreach ($itemRows as $index => $itemRaw) {
    $item = (array)$itemRaw;
    $itemId = (string)($item['id'] ?? '');
    if ($itemId === '') {
        continue;
    }

    $run = (array)($item['payroll_run'] ?? []);
    $period = (array)($run['payroll_period'] ?? []);

    $periodStart = (string)($period['period_start'] ?? '');
    $periodEnd = (string)($period['period_end'] ?? '');
    $periodCode = (string)($period['period_code'] ?? '');
    $periodLabel = ($periodStart !== '' && $periodEnd !== '')
        ? (date('M d, Y', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd)))
        : ($periodCode !== '' ? $periodCode : 'Payroll Period');

    $payslip = (array)($payslipByPayrollItem[$itemId] ?? []);
    $releasedAt = cleanText($payslip['released_at'] ?? null);
    $status = $releasedAt !== null ? 'released' : 'pending';

    $adjustments = (array)($adjustmentsByPayrollItem[$itemId] ?? []);
    $itemBreakdown = is_array($breakdownByPayrollItem[$itemId] ?? null)
        ? (array)$breakdownByPayrollItem[$itemId]
        : [];

    $baseGrossPay = (float)($item['gross_pay'] ?? 0);
    $baseTotalDeductions = (float)($item['deductions_total'] ?? 0);
    $baseNetPay = (float)($item['net_pay'] ?? 0);

    $adjustmentEarnings = 0.0;
    $adjustmentDeductions = 0.0;
    foreach ($adjustments as $adjustment) {
        if ((string)($adjustment['adjustment_type'] ?? '') === 'earning') {
            $adjustmentEarnings += (float)($adjustment['amount'] ?? 0);
        } else {
            $adjustmentDeductions += (float)($adjustment['amount'] ?? 0);
        }
    }

    $grossPay = $baseGrossPay + $adjustmentEarnings;
    $totalDeductions = $baseTotalDeductions + $adjustmentDeductions;
    $netPay = $baseNetPay + $adjustmentEarnings - $adjustmentDeductions;

    $earnings = [];
    $basicPay = (float)($itemBreakdown['basic_pay'] ?? 0);
    $ctoPay = (float)($itemBreakdown['cto_pay'] ?? 0);
    $allowancesTotal = (float)($itemBreakdown['allowances_total'] ?? 0);

    if ($basicPay > 0) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'BASIC_PAY',
            'description' => 'Basic Pay',
            'amount' => $basicPay,
        ];
    }
    if ($ctoPay > 0) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'CTO_PAY',
            'description' => 'CTO Leave UT w/ Pay',
            'amount' => $ctoPay,
        ];
    }
    if ($allowancesTotal > 0) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'ALLOWANCES',
            'description' => 'Allowances',
            'amount' => $allowancesTotal,
        ];
    }
    if ($adjustmentEarnings > 0) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'APPROVED_ADJUSTMENTS_EARNINGS',
            'description' => 'Approved Adjustment Earnings',
            'amount' => $adjustmentEarnings,
        ];
    }
    if (empty($earnings)) {
        $earnings[] = [
            'adjustment_type' => 'earning',
            'adjustment_code' => 'GROSS',
            'description' => 'Gross Pay',
            'amount' => $grossPay,
        ];
    }

    $deductions = [];
    $statutoryDeductions = (float)($itemBreakdown['statutory_deductions'] ?? 0);
    $timekeepingDeductions = (float)($itemBreakdown['timekeeping_deductions'] ?? 0);
    if ($statutoryDeductions > 0) {
        $deductions[] = [
            'adjustment_type' => 'deduction',
            'adjustment_code' => 'STATUTORY_DEDUCTIONS',
            'description' => 'Statutory / Government Contributions (SSS/Pag-IBIG/PhilHealth)',
            'amount' => $statutoryDeductions,
        ];
    }
    if ($timekeepingDeductions > 0) {
        $deductions[] = [
            'adjustment_type' => 'deduction',
            'adjustment_code' => 'TIMEKEEPING_DEDUCTIONS',
            'description' => 'Timekeeping Deductions (Late/Undertime/Absence)',
            'amount' => $timekeepingDeductions,
        ];
    }
    if ($adjustmentDeductions > 0) {
        $deductions[] = [
            'adjustment_type' => 'deduction',
            'adjustment_code' => 'APPROVED_ADJUSTMENTS_DEDUCTIONS',
            'description' => 'Approved Adjustment Deductions',
            'amount' => $adjustmentDeductions,
        ];
    }

    if (empty($deductions)) {
        $periodStartDate = $periodStart !== '' ? date('Y-m-d', strtotime($periodStart)) : '';
        $matchedCompensation = null;

        if ($periodStartDate !== '' && !empty($compensationRows)) {
            foreach ($compensationRows as $compensationRaw) {
                $compensation = (array)$compensationRaw;
                $effectiveFrom = trim((string)($compensation['effective_from'] ?? ''));
                $effectiveTo = trim((string)($compensation['effective_to'] ?? ''));

                if ($effectiveFrom === '' || $effectiveFrom > $periodStartDate) {
                    continue;
                }

                if ($effectiveTo !== '' && $effectiveTo < $periodStartDate) {
                    continue;
                }

                $matchedCompensation = $compensation;
                break;
            }
        }

        if (is_array($matchedCompensation)) {
            $divisor = $payFrequencyDivisor((string)($matchedCompensation['pay_frequency'] ?? 'semi_monthly'));
            $withholding = round(max(0.0, (float)($matchedCompensation['tax_deduction'] ?? 0)) / $divisor, 2);
            $government = round(max(0.0, (float)($matchedCompensation['government_deductions'] ?? 0)) / $divisor, 2);
            $other = round(max(0.0, (float)($matchedCompensation['other_deductions'] ?? 0)) / $divisor, 2);

            if ($withholding > 0) {
                $deductions[] = [
                    'adjustment_type' => 'deduction',
                    'adjustment_code' => 'WITHHOLDING_TAX',
                    'description' => 'Withholding Tax',
                    'amount' => $withholding,
                ];
            }

            if ($government > 0) {
                $deductions[] = [
                    'adjustment_type' => 'deduction',
                    'adjustment_code' => 'GOVERNMENT_CONTRIBUTIONS',
                    'description' => 'Government Contributions (SSS/Pag-IBIG/PhilHealth)',
                    'amount' => $government,
                ];
            }

            if ($other > 0) {
                $deductions[] = [
                    'adjustment_type' => 'deduction',
                    'adjustment_code' => 'OTHER_DEDUCTIONS',
                    'description' => 'Other Deductions',
                    'amount' => $other,
                ];
            }
        }

        if (empty($deductions)) {
            $deductions[] = [
                'adjustment_type' => 'deduction',
                'adjustment_code' => 'DEDUCTIONS',
                'description' => 'Total Deductions',
                'amount' => $totalDeductions,
            ];
        }
    }

    if (!empty($itemBreakdown)) {
        $attendanceSummary = sprintf(
            'Leave Card Remarks: Absence impact %d day(s); Late/Undertime %d minute(s) / %.2f hour(s)',
            (int)($itemBreakdown['absent_days'] ?? 0),
            (int)($itemBreakdown['late_minutes'] ?? 0),
            (float)($itemBreakdown['undertime_hours'] ?? 0)
        );
        $deductions[] = [
            'adjustment_type' => 'deduction',
            'adjustment_code' => 'ATTENDANCE_IMPACT',
            'description' => $attendanceSummary,
            'amount' => 0,
        ];
    }

    $yearSource = $periodEnd !== '' ? $periodEnd : ($periodStart !== '' ? $periodStart : (string)($item['created_at'] ?? ''));
    $year = (int)date('Y', strtotime($yearSource));
    if ($year > 1900) {
        $payrollYears[$year] = $year;
    }

    $employeePayrollRows[] = [
        'item_id' => $itemId,
        'period_label' => $periodLabel,
        'period_year' => $year > 1900 ? (string)$year : '',
        'gross_pay' => $grossPay,
        'deductions_total' => $totalDeductions,
        'net_pay' => $netPay,
        'status' => $status,
        'run_status' => strtolower((string)($run['run_status'] ?? 'draft')),
        'payslip_id' => (string)($payslip['id'] ?? ''),
        'payslip_no' => (string)($payslip['payslip_no'] ?? '-'),
        'pdf_storage_path' => cleanText($payslip['pdf_storage_path'] ?? null),
        'released_at' => $releasedAt,
        'viewed_at' => cleanText($payslip['viewed_at'] ?? null),
        'created_at' => (string)($item['created_at'] ?? ''),
        'earnings' => $earnings,
        'deductions' => $deductions,
    ];

    if ($index === 0) {
        $payrollSummary['latest_period_label'] = $periodLabel;
        $payrollSummary['latest_net_pay'] = $netPay;
        $payrollSummary['latest_gross_pay'] = $grossPay;
        $payrollSummary['latest_deductions'] = $totalDeductions;
    }
}

if (!empty($payrollYears)) {
    rsort($payrollYears);
}
