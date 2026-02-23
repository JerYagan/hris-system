<?php

$payrollPeriodRows = [];
$payrollRunRows = [];
$payrollAdjustmentRows = [];
$salaryAdjustmentCreateRows = [];
$computePreviewByPeriod = [];
$generatePreviewByRun = [];
$dataLoadError = null;

$appendDataError = static function (string $label, array $response) use (&$dataLoadError): void {
	if (isSuccessful($response)) {
		return;
	}

	$message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
	$raw = trim((string)($response['raw'] ?? ''));
	if ($raw !== '') {
		$message .= ' ' . $raw;
	}

	$dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $message) : $message;
};

$periodStatusPill = static function (string $status): array {
	$key = strtolower(trim($status));
	return match ($key) {
		'processing' => ['Processing', 'bg-amber-100 text-amber-800'],
		'posted' => ['Posted', 'bg-blue-100 text-blue-800'],
		'closed' => ['Closed', 'bg-emerald-100 text-emerald-800'],
		default => ['Open', 'bg-violet-100 text-violet-800'],
	};
};

$runStatusPill = static function (string $status): array {
	$key = strtolower(trim($status));
	return match ($key) {
		'computed' => ['Computed', 'bg-violet-100 text-violet-800'],
		'approved' => ['Approved', 'bg-blue-100 text-blue-800'],
		'released' => ['Released', 'bg-emerald-100 text-emerald-800'],
		'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-800'],
		default => ['Draft', 'bg-amber-100 text-amber-800'],
	};
};

$adjustmentStatusPill = static function (string $status): array {
	$key = strtolower(trim($status));
	return match ($key) {
		'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
		'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
		default => ['Pending', 'bg-amber-100 text-amber-800'],
	};
};

$periodsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,payout_date,status,updated_at'
	. '&order=period_end.desc&limit=500',
	$headers
);
$appendDataError('Payroll periods', $periodsResponse);
$periodRows = isSuccessful($periodsResponse) ? (array)($periodsResponse['data'] ?? []) : [];

$runsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,generated_by,generated_at,approved_at,created_at,period:payroll_periods(period_code,period_start,period_end,status)'
	. '&order=created_at.desc&limit=1500',
	$headers
);
$appendDataError('Payroll runs', $runsResponse);
$runRows = isSuccessful($runsResponse) ? (array)($runsResponse['data'] ?? []) : [];

$runById = [];
foreach ($runRows as $runRow) {
	$runId = cleanText($runRow['id'] ?? null) ?? '';
	if (!isValidUuid($runId)) {
		continue;
	}

	$runById[$runId] = (array)$runRow;
}

$employeeRoleUserIds = [];
$employeeRoleAssignmentResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/user_role_assignments?select=user_id,expires_at,role:roles!inner(role_key)'
	. '&role.role_key=eq.employee&limit=10000',
	$headers
);
$appendDataError('Employee role assignments', $employeeRoleAssignmentResponse);
$employeeRoleQuerySuccessful = isSuccessful($employeeRoleAssignmentResponse);

if ($employeeRoleQuerySuccessful) {
	$nowTimestamp = time();
	$assignmentRows = (array)($employeeRoleAssignmentResponse['data'] ?? []);
	foreach ($assignmentRows as $assignmentRow) {
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

		$employeeRoleUserIds[$userId] = true;
	}
}

$formatUuidInFilter = static function (array $ids): string {
	$validIds = [];
	foreach ($ids as $id) {
		$candidate = strtolower(trim((string)$id));
		if (!isValidUuid($candidate)) {
			continue;
		}
		$validIds[$candidate] = true;
	}

	return implode(',', array_keys($validIds));
};

$employeePeopleRows = [];
$employeePeopleByPersonId = [];
$employeeRoleUserIdList = array_keys($employeeRoleUserIds);
$employeeUserFilter = $formatUuidInFilter($employeeRoleUserIdList);

if ($employeeRoleQuerySuccessful && $employeeUserFilter !== '') {
	$employeePeopleResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/people?select=id,user_id,first_name,middle_name,surname'
		. '&user_id=in.' . rawurlencode('(' . $employeeUserFilter . ')')
		. '&limit=10000',
		$headers
	);
	$appendDataError('Employee people records', $employeePeopleResponse);

	if (isSuccessful($employeePeopleResponse)) {
		$employeePeopleRows = (array)($employeePeopleResponse['data'] ?? []);
		foreach ($employeePeopleRows as $personRow) {
			$personId = cleanText($personRow['id'] ?? null) ?? '';
			if (!isValidUuid($personId)) {
				continue;
			}

			$employeePeopleByPersonId[$personId] = (array)$personRow;
		}
	}
}

$employeeCompensationByPerson = [];
if (!empty($employeePeopleByPersonId)) {
	$personFilter = $formatUuidInFilter(array_keys($employeePeopleByPersonId));
	if ($personFilter !== '') {
		$compensationResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,pay_frequency,created_at'
			. '&person_id=in.' . rawurlencode('(' . $personFilter . ')')
			. '&order=effective_from.desc,created_at.desc&limit=10000',
			$headers
		);
		$appendDataError('Employee compensation records', $compensationResponse);

		if (isSuccessful($compensationResponse)) {
			$compensationRows = (array)($compensationResponse['data'] ?? []);
			foreach ($compensationRows as $compensationRow) {
				$personId = cleanText($compensationRow['person_id'] ?? null) ?? '';
				if (!isValidUuid($personId)) {
					continue;
				}

				if (!isset($employeeCompensationByPerson[$personId])) {
					$employeeCompensationByPerson[$personId] = [];
				}

				$employeeCompensationByPerson[$personId][] = (array)$compensationRow;
			}
		}
	}
}

$resolveCompensationForPeriod = static function (array $rows, string $periodStart): ?array {
	if ($periodStart === '') {
		return null;
	}

	foreach ($rows as $row) {
		$effectiveFrom = cleanText($row['effective_from'] ?? null) ?? '';
		if ($effectiveFrom === '' || $effectiveFrom > $periodStart) {
			continue;
		}

		$effectiveTo = cleanText($row['effective_to'] ?? null);
		if ($effectiveTo !== null && $effectiveTo !== '' && $effectiveTo < $periodStart) {
			continue;
		}

		return (array)$row;
	}

	return null;
};

$calculateEstimatedNetByCompensation = static function (array $compensation): float {
	$monthlyRate = (float)($compensation['monthly_rate'] ?? 0);
	$allowanceMonthly = max(0.0, (float)($compensation['allowance_total'] ?? 0));
	$basePayMonthly = isset($compensation['base_pay'])
		? max(0.0, (float)$compensation['base_pay'])
		: max(0.0, $monthlyRate - $allowanceMonthly);
	$taxMonthly = max(0.0, (float)($compensation['tax_deduction'] ?? 0));
	$governmentMonthly = max(0.0, (float)($compensation['government_deductions'] ?? 0));
	$otherMonthly = max(0.0, (float)($compensation['other_deductions'] ?? 0));
	$payFrequency = strtolower((string)(cleanText($compensation['pay_frequency'] ?? null) ?? 'semi_monthly'));

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

	return round($grossPay - $deductionsTotal, 2);
};

$runIds = [];
foreach ($runRows as $run) {
	$runId = cleanText($run['id'] ?? null);
	if ($runId === null || !isValidUuid($runId)) {
		continue;
	}
	$runIds[] = $runId;
}

$itemRows = [];
if (!empty($runIds)) {
	$itemsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,gross_pay,net_pay,deductions_total,created_at,person:people(id,user_id,first_name,middle_name,surname)'
		. '&payroll_run_id=in.' . rawurlencode('(' . implode(',', $runIds) . ')')
		. '&limit=5000',
		$headers
	);
	$appendDataError('Payroll items', $itemsResponse);
	$itemRows = isSuccessful($itemsResponse) ? (array)($itemsResponse['data'] ?? []) : [];
}

$scopedItemRows = [];
foreach ($itemRows as $itemRow) {
	$personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
	$userId = cleanText($personRow['user_id'] ?? null) ?? '';

	if (
		$employeeRoleQuerySuccessful
		&& !empty($employeeRoleUserIds)
		&& (!isValidUuid($userId) || !isset($employeeRoleUserIds[$userId]))
	) {
		continue;
	}

	$scopedItemRows[] = (array)$itemRow;
}

$itemRows = $scopedItemRows;

$itemIds = [];
foreach ($itemRows as $item) {
	$itemId = cleanText($item['id'] ?? null);
	if ($itemId === null || !isValidUuid($itemId)) {
		continue;
	}
	$itemIds[] = $itemId;
}

$releasedByItemId = [];
if (!empty($itemIds)) {
	$payslipResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payslips?select=payroll_item_id,released_at'
		. '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
		. '&limit=5000',
		$headers
	);
	$appendDataError('Payslips', $payslipResponse);
	$payslipRows = isSuccessful($payslipResponse) ? (array)($payslipResponse['data'] ?? []) : [];

	foreach ($payslipRows as $payslip) {
		$itemId = cleanText($payslip['payroll_item_id'] ?? null) ?? '';
		if (!isValidUuid($itemId)) {
			continue;
		}

		$releasedByItemId[$itemId] = cleanText($payslip['released_at'] ?? null) !== null;
	}
}

$runStatsById = [];
$periodStatsById = [];

foreach ($itemRows as $item) {
	$runId = cleanText($item['payroll_run_id'] ?? null) ?? '';
	if (!isValidUuid($runId)) {
		continue;
	}

	$personId = cleanText($item['person_id'] ?? null) ?? '';
	if (!isset($runStatsById[$runId])) {
		$runStatsById[$runId] = [
			'employees' => [],
			'gross_total' => 0.0,
			'net_total' => 0.0,
			'released_count' => 0,
		];
	}

	if (isValidUuid($personId)) {
		$runStatsById[$runId]['employees'][$personId] = true;
	}

	$runStatsById[$runId]['gross_total'] += (float)($item['gross_pay'] ?? 0);
	$runStatsById[$runId]['net_total'] += (float)($item['net_pay'] ?? 0);

	$itemId = cleanText($item['id'] ?? null) ?? '';
	if ($itemId !== '' && !empty($releasedByItemId[$itemId])) {
		$runStatsById[$runId]['released_count']++;
	}
}

foreach ($runRows as $run) {
	$runId = cleanText($run['id'] ?? null) ?? '';
	$periodId = cleanText($run['payroll_period_id'] ?? null) ?? '';
	if (!isValidUuid($runId) || !isValidUuid($periodId)) {
		continue;
	}

	if (!isset($periodStatsById[$periodId])) {
		$periodStatsById[$periodId] = [
			'run_count' => 0,
			'employees' => [],
		];
	}

	$runStats = $runStatsById[$runId] ?? [
		'employees' => [],
		'gross_total' => 0.0,
		'net_total' => 0.0,
		'released_count' => 0,
	];

	$periodStatsById[$periodId]['run_count']++;
	foreach (array_keys((array)$runStats['employees']) as $personId) {
		$periodStatsById[$periodId]['employees'][$personId] = true;
	}
}

foreach ($periodRows as $period) {
	$periodId = cleanText($period['id'] ?? null) ?? '';
	if (!isValidUuid($periodId)) {
		continue;
	}

	$statusRaw = strtolower((string)(cleanText($period['status'] ?? null) ?? 'open'));
	[$statusLabel, $statusClass] = $periodStatusPill($statusRaw);
	$stats = $periodStatsById[$periodId] ?? ['run_count' => 0, 'employees' => []];

	$periodCode = cleanText($period['period_code'] ?? null) ?? 'Uncoded';
	$periodStart = cleanText($period['period_start'] ?? null) ?? '';
	$periodRange = formatDateTimeForPhilippines(cleanText($period['period_start'] ?? null), 'M d, Y')
		. ' - '
		. formatDateTimeForPhilippines(cleanText($period['period_end'] ?? null), 'M d, Y');

	$computePreviewRows = [];
	if (!empty($employeePeopleByPersonId) && $periodStart !== '') {
		foreach ($employeePeopleByPersonId as $personId => $personRow) {
			$compensationRows = (array)($employeeCompensationByPerson[$personId] ?? []);
			$effectiveCompensation = $resolveCompensationForPeriod($compensationRows, $periodStart);
			if (!is_array($effectiveCompensation)) {
				continue;
			}

			$fullName = trim(implode(' ', array_filter([
				cleanText($personRow['first_name'] ?? null),
				cleanText($personRow['middle_name'] ?? null),
				cleanText($personRow['surname'] ?? null),
			])));
			if ($fullName === '') {
				$fullName = 'Employee';
			}

			$computePreviewRows[] = [
				'person_id' => $personId,
				'employee_name' => $fullName,
				'estimated_net' => $calculateEstimatedNetByCompensation($effectiveCompensation),
			];
		}
	}

	usort($computePreviewRows, static function (array $left, array $right): int {
		return strcmp(
			strtolower((string)($left['employee_name'] ?? '')),
			strtolower((string)($right['employee_name'] ?? ''))
		);
	});

	$computePreviewByPeriod[$periodId] = [
		'period_code' => $periodCode,
		'period_range' => $periodRange,
		'employees' => $computePreviewRows,
		'employee_count' => count($computePreviewRows),
	];

	$payrollPeriodRows[] = [
		'id' => $periodId,
		'period_code' => $periodCode,
		'period_range' => $periodRange,
		'payout_label' => formatDateTimeForPhilippines(cleanText($period['payout_date'] ?? null), 'M d, Y'),
		'status_raw' => $statusRaw,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'run_count' => (int)($stats['run_count'] ?? 0),
		'employee_count' => count((array)($stats['employees'] ?? [])),
		'eligible_employee_count' => count($computePreviewRows),
		'search_text' => strtolower(trim($periodCode . ' ' . $periodRange . ' ' . $statusLabel)),
	];
}

foreach ($runRows as $run) {
	$runId = cleanText($run['id'] ?? null) ?? '';
	if (!isValidUuid($runId)) {
		continue;
	}

	$statusRaw = strtolower((string)(cleanText($run['run_status'] ?? null) ?? 'draft'));
	[$statusLabel, $statusClass] = $runStatusPill($statusRaw);
	$runStats = $runStatsById[$runId] ?? ['employees' => [], 'gross_total' => 0.0, 'net_total' => 0.0, 'released_count' => 0];

	$periodCode = cleanText($run['period']['period_code'] ?? null) ?? 'Uncoded';

	$generateRows = [];
	foreach ($itemRows as $itemRow) {
		$itemRunId = cleanText($itemRow['payroll_run_id'] ?? null) ?? '';
		if ($itemRunId !== $runId) {
			continue;
		}

		$personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
		$fullName = trim(implode(' ', array_filter([
			cleanText($personRow['first_name'] ?? null),
			cleanText($personRow['middle_name'] ?? null),
			cleanText($personRow['surname'] ?? null),
		])));
		if ($fullName === '') {
			$fullName = 'Employee';
		}

		$generateRows[] = [
			'employee_name' => $fullName,
			'gross_pay' => (float)($itemRow['gross_pay'] ?? 0),
			'net_pay' => (float)($itemRow['net_pay'] ?? 0),
		];
	}

	usort($generateRows, static function (array $left, array $right): int {
		return strcmp(
			strtolower((string)($left['employee_name'] ?? '')),
			strtolower((string)($right['employee_name'] ?? ''))
		);
	});

	$generatePreviewByRun[$runId] = [
		'run_short_id' => strtoupper(substr(str_replace('-', '', $runId), 0, 8)),
		'period_code' => $periodCode,
		'employees' => $generateRows,
		'employee_count' => count($generateRows),
	];

	$payrollRunRows[] = [
		'id' => $runId,
		'short_id' => strtoupper(substr(str_replace('-', '', $runId), 0, 8)),
		'period_code' => $periodCode,
		'generated_label' => formatDateTimeForPhilippines(cleanText($run['generated_at'] ?? null), 'M d, Y'),
		'approved_label' => formatDateTimeForPhilippines(cleanText($run['approved_at'] ?? null), 'M d, Y'),
		'status_raw' => $statusRaw,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'can_generate' => $statusRaw === 'approved',
		'employee_count' => count((array)($runStats['employees'] ?? [])),
		'gross_total' => (float)($runStats['gross_total'] ?? 0),
		'net_total' => (float)($runStats['net_total'] ?? 0),
		'released_count' => (int)($runStats['released_count'] ?? 0),
		'search_text' => strtolower(trim($periodCode . ' ' . $statusLabel . ' ' . $runId)),
	];
}

$seenCreateRows = [];
foreach ($itemRows as $item) {
	$itemId = cleanText($item['id'] ?? null) ?? '';
	$runId = cleanText($item['payroll_run_id'] ?? null) ?? '';
	if (!isValidUuid($itemId) || !isValidUuid($runId) || isset($seenCreateRows[$itemId])) {
		continue;
	}

	$runRow = is_array($runById[$runId] ?? null) ? (array)$runById[$runId] : [];
	$runStatus = strtolower((string)(cleanText($runRow['run_status'] ?? null) ?? 'draft'));
	if (in_array($runStatus, ['released', 'cancelled'], true)) {
		continue;
	}

	$periodRow = is_array($runRow['period'] ?? null) ? (array)$runRow['period'] : [];
	$periodCode = cleanText($periodRow['period_code'] ?? null) ?? '-';

	$personRow = is_array($item['person'] ?? null) ? (array)$item['person'] : [];
	$fullName = trim(implode(' ', array_filter([
		cleanText($personRow['first_name'] ?? null),
		cleanText($personRow['middle_name'] ?? null),
		cleanText($personRow['surname'] ?? null),
	])));
	if ($fullName === '') {
		$fullName = 'Employee';
	}

	$runShortId = strtoupper(substr(str_replace('-', '', $runId), 0, 8));
	$salaryAdjustmentCreateRows[] = [
		'item_id' => $itemId,
		'employee_name' => $fullName,
		'period_code' => $periodCode,
		'run_short_id' => $runShortId,
		'label' => $fullName . ' • ' . $periodCode . ' • RUN ' . $runShortId,
	];

	$seenCreateRows[$itemId] = true;
}

usort($salaryAdjustmentCreateRows, static function (array $left, array $right): int {
	$leftKey = strtolower(trim((string)($left['employee_name'] ?? '') . ' ' . (string)($left['period_code'] ?? '') . ' ' . (string)($left['run_short_id'] ?? '')));
	$rightKey = strtolower(trim((string)($right['employee_name'] ?? '') . ' ' . (string)($right['period_code'] ?? '') . ' ' . (string)($right['run_short_id'] ?? '')));

	return strcmp($leftKey, $rightKey);
});

$adjustmentsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,adjustment_code,description,amount,created_at,item:payroll_items(id,payroll_run_id,person_id,person:people(id,user_id,first_name,middle_name,surname),run:payroll_runs(id,payroll_period_id,period:payroll_periods(period_code)))'
	. '&order=created_at.desc&limit=3000',
	$headers
);
$appendDataError('Payroll adjustments', $adjustmentsResponse);
$adjustmentRows = isSuccessful($adjustmentsResponse) ? (array)($adjustmentsResponse['data'] ?? []) : [];

$adjustmentIds = [];
foreach ($adjustmentRows as $adjustment) {
	$adjustmentId = cleanText($adjustment['id'] ?? null);
	if ($adjustmentId === null || !isValidUuid($adjustmentId)) {
		continue;
	}
	$adjustmentIds[] = $adjustmentId;
}

$reviewStatusByAdjustmentId = [];
if (!empty($adjustmentIds)) {
	$logResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
		. '&entity_name=eq.payroll_adjustments'
		. '&action_name=eq.review_payroll_adjustment'
		. '&entity_id=in.' . rawurlencode('(' . implode(',', $adjustmentIds) . ')')
		. '&order=created_at.desc'
		. '&limit=5000',
		$headers
	);
	$appendDataError('Payroll adjustment reviews', $logResponse);
	$logRows = isSuccessful($logResponse) ? (array)($logResponse['data'] ?? []) : [];

	foreach ($logRows as $logRow) {
		$entityId = cleanText($logRow['entity_id'] ?? null) ?? '';
		if (!isValidUuid($entityId) || isset($reviewStatusByAdjustmentId[$entityId])) {
			continue;
		}

		$newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];
		$reviewStatus = strtolower((string)(cleanText($newData['review_status'] ?? null) ?? 'pending'));
		if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
			$reviewStatus = 'pending';
		}

		$reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
	}
}

foreach ($adjustmentRows as $adjustment) {
	$adjustmentId = cleanText($adjustment['id'] ?? null) ?? '';
	if (!isValidUuid($adjustmentId)) {
		continue;
	}

	$itemRow = is_array($adjustment['item'] ?? null) ? (array)$adjustment['item'] : [];
	$personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
	$personUserId = cleanText($personRow['user_id'] ?? null) ?? '';
	if (
		$employeeRoleQuerySuccessful
		&& !empty($employeeRoleUserIds)
		&& (!isValidUuid($personUserId) || !isset($employeeRoleUserIds[$personUserId]))
	) {
		continue;
	}

	$runRow = is_array($itemRow['run'] ?? null) ? (array)$itemRow['run'] : [];
	$periodRow = is_array($runRow['period'] ?? null) ? (array)$runRow['period'] : [];

	$fullName = trim(implode(' ', array_filter([
		cleanText($personRow['first_name'] ?? null),
		cleanText($personRow['middle_name'] ?? null),
		cleanText($personRow['surname'] ?? null),
	])));

	if ($fullName === '') {
		$fullName = 'Employee';
	}

	$reviewStatus = $reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending';
	[$statusLabel, $statusClass] = $adjustmentStatusPill($reviewStatus);

	$adjustmentCode = cleanText($adjustment['adjustment_code'] ?? null) ?? 'Adjustment';
	$periodCode = cleanText($periodRow['period_code'] ?? null) ?? '-';
	$description = cleanText($adjustment['description'] ?? null) ?? '-';
	$typeLabel = ucwords(str_replace('_', ' ', (string)(cleanText($adjustment['adjustment_type'] ?? null) ?? 'adjustment')));

	$payrollAdjustmentRows[] = [
		'id' => $adjustmentId,
		'adjustment_code' => $adjustmentCode,
		'employee_name' => $fullName,
		'period_code' => $periodCode,
		'adjustment_type_label' => $typeLabel,
		'description' => $description,
		'amount' => (float)($adjustment['amount'] ?? 0),
		'status_raw' => $reviewStatus,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'created_label' => formatDateTimeForPhilippines(cleanText($adjustment['created_at'] ?? null), 'M d, Y'),
		'search_text' => strtolower(trim($adjustmentCode . ' ' . $fullName . ' ' . $periodCode . ' ' . $description . ' ' . $statusLabel)),
	];
}
