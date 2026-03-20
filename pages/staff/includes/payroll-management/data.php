<?php

$staffPayrollDataStage = (string)($staffPayrollDataStage ?? 'full');

if (!function_exists('staffPayrollAppendQueryError')) {
	function staffPayrollAppendQueryError(?string $currentError, string $label, array $response): string
	{
		$message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
		$raw = trim((string)($response['raw'] ?? ''));
		if ($raw !== '') {
			$message .= ' ' . $raw;
		}

		return $currentError ? ($currentError . ' ' . $message) : $message;
	}
}

if (!function_exists('staffPayrollRunStatusBadge')) {
	function staffPayrollRunStatusBadge(string $status): array
	{
		$key = strtolower(trim($status));
		return match ($key) {
			'pending_review' => ['Pending Review', 'bg-amber-100 text-amber-800'],
			'computed' => ['Computed', 'bg-violet-100 text-violet-800'],
			'approved' => ['Approved', 'bg-blue-100 text-blue-800'],
			'released' => ['Released', 'bg-emerald-100 text-emerald-800'],
			'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-800'],
			default => ['Draft', 'bg-amber-100 text-amber-800'],
		};
	}
}

if (!function_exists('staffPayrollFormatPeriodRange')) {
	function staffPayrollFormatPeriodRange(?string $periodStart, ?string $periodEnd, string $fallback = 'Uncoded'): string
	{
		$start = cleanText($periodStart) ?? '';
		$end = cleanText($periodEnd) ?? '';
		if ($start !== '' && $end !== '') {
			return formatDateTimeForPhilippines($start, 'M d, Y') . ' - ' . formatDateTimeForPhilippines($end, 'M d, Y');
		}

		return $fallback;
	}
}

if ($staffPayrollDataStage === 'summary') {
	$dataLoadError = null;
	$staffPayrollSummary = [
		'current_period_label' => 'No payroll period found',
		'current_period_status' => 'No run yet',
		'current_run_status_label' => 'No run yet',
		'current_period_employee_count' => 0,
		'current_period_gross_total' => 0.0,
		'current_period_net_total' => 0.0,
		'pending_admin_review_count' => 0,
		'approved_runs_count' => 0,
		'released_runs_count' => 0,
		'open_periods_count' => 0,
	];

	$summaryPeriodsResponse = apiRequest(
		'GET',
		$supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,period_start,period_end,status&order=period_end.desc&limit=48',
		$headers
	);
	$summaryRunsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,created_at,period:payroll_periods(period_code,period_start,period_end,status)'
		. '&order=created_at.desc&limit=320',
		$headers
	);

	$summaryPeriodRows = isSuccessful($summaryPeriodsResponse) ? (array)($summaryPeriodsResponse['data'] ?? []) : [];
	$summaryRunRows = isSuccessful($summaryRunsResponse) ? (array)($summaryRunsResponse['data'] ?? []) : [];

	if (!isSuccessful($summaryPeriodsResponse)) {
		$dataLoadError = staffPayrollAppendQueryError($dataLoadError, 'Payroll periods', $summaryPeriodsResponse);
	}
	if (!isSuccessful($summaryRunsResponse)) {
		$dataLoadError = staffPayrollAppendQueryError($dataLoadError, 'Payroll runs', $summaryRunsResponse);
	}

	$summaryRunIds = [];
	foreach ($summaryRunRows as $summaryRunRow) {
		$runId = cleanText($summaryRunRow['id'] ?? null) ?? '';
		if (isValidUuid($runId)) {
			$summaryRunIds[] = $runId;
		}
	}

	$recommendationByRunId = [];
	if (!empty($summaryRunIds)) {
		$runLogResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/activity_logs?select=entity_id,action_name,created_at'
			. '&entity_name=eq.payroll_runs'
			. '&entity_id=in.' . rawurlencode('(' . implode(',', $summaryRunIds) . ')')
			. '&action_name=eq.submit_batch_for_admin_approval'
			. '&order=created_at.desc&limit=1500',
			$headers
		);

		if (isSuccessful($runLogResponse)) {
			foreach ((array)($runLogResponse['data'] ?? []) as $logRow) {
				$runId = cleanText($logRow['entity_id'] ?? null) ?? '';
				if (isValidUuid($runId) && !isset($recommendationByRunId[$runId])) {
					$recommendationByRunId[$runId] = true;
				}
			}
		} else {
			$dataLoadError = staffPayrollAppendQueryError($dataLoadError, 'Payroll run handoff logs', $runLogResponse);
		}
	}

	foreach ($summaryPeriodRows as $periodRow) {
		$status = strtolower((string)(cleanText($periodRow['status'] ?? null) ?? 'open'));
		if (in_array($status, ['open', 'processing', 'posted'], true)) {
			$staffPayrollSummary['open_periods_count']++;
		}
	}

	$latestRunByPeriodId = [];
	foreach ($summaryRunRows as $summaryRunRow) {
		$runId = cleanText($summaryRunRow['id'] ?? null) ?? '';
		$periodId = cleanText($summaryRunRow['payroll_period_id'] ?? null) ?? '';
		if (!isValidUuid($runId) || !isValidUuid($periodId)) {
			continue;
		}

		$statusRaw = strtolower((string)(cleanText($summaryRunRow['run_status'] ?? null) ?? 'draft'));
		$effectiveStatus = (!$recommendationByRunId[$runId] && $statusRaw === 'computed') ? 'pending_review' : $statusRaw;

		if ($effectiveStatus === 'pending_review') {
			$staffPayrollSummary['pending_admin_review_count']++;
		} elseif ($effectiveStatus === 'approved') {
			$staffPayrollSummary['approved_runs_count']++;
		} elseif ($effectiveStatus === 'released') {
			$staffPayrollSummary['released_runs_count']++;
		}

		if (!isset($latestRunByPeriodId[$periodId])) {
			$latestRunByPeriodId[$periodId] = [
				'id' => $runId,
				'effective_status' => $effectiveStatus,
			];
		}
	}

	if (!empty($summaryPeriodRows)) {
		$latestPeriod = (array)$summaryPeriodRows[0];
		$latestPeriodId = cleanText($latestPeriod['id'] ?? null) ?? '';
		$periodCode = cleanText($latestPeriod['period_code'] ?? null) ?? 'Current Period';
		$staffPayrollSummary['current_period_label'] = staffPayrollFormatPeriodRange($latestPeriod['period_start'] ?? null, $latestPeriod['period_end'] ?? null, $periodCode);
		$staffPayrollSummary['current_period_status'] = ucwords((string)(cleanText($latestPeriod['status'] ?? null) ?? 'open'));

		$latestRun = is_array($latestRunByPeriodId[$latestPeriodId] ?? null) ? (array)$latestRunByPeriodId[$latestPeriodId] : null;
		if (is_array($latestRun)) {
			[$runStatusLabel] = staffPayrollRunStatusBadge((string)($latestRun['effective_status'] ?? 'draft'));
			$staffPayrollSummary['current_run_status_label'] = $runStatusLabel;
			$staffPayrollSummary['current_period_status'] = $runStatusLabel;

			$latestRunId = cleanText($latestRun['id'] ?? null) ?? '';
			if (isValidUuid($latestRunId)) {
				$summaryItemsResponse = apiRequest(
					'GET',
					$supabaseUrl . '/rest/v1/payroll_items?select=person_id,gross_pay,net_pay&payroll_run_id=eq.' . rawurlencode($latestRunId) . '&limit=2000',
					$headers
				);

				if (isSuccessful($summaryItemsResponse)) {
					$employeeIds = [];
					foreach ((array)($summaryItemsResponse['data'] ?? []) as $itemRow) {
						$personId = cleanText($itemRow['person_id'] ?? null) ?? '';
						if (isValidUuid($personId)) {
							$employeeIds[$personId] = true;
						}

						$staffPayrollSummary['current_period_gross_total'] += (float)($itemRow['gross_pay'] ?? 0);
						$staffPayrollSummary['current_period_net_total'] += (float)($itemRow['net_pay'] ?? 0);
					}

					$staffPayrollSummary['current_period_employee_count'] = count($employeeIds);
				} else {
					$dataLoadError = staffPayrollAppendQueryError($dataLoadError, 'Payroll items', $summaryItemsResponse);
				}
			}
		}
	}

	return;
}

$staffPayrollPageSize = 10;
$staffPayrollPeriodsPage = max(1, (int)($_GET['payroll_period_page'] ?? 1));
$staffPayrollAdjustmentsPage = max(1, (int)($_GET['salary_adjustment_page'] ?? 1));
$staffPayrollRunsPage = max(1, (int)($_GET['payroll_run_page'] ?? 1));

$staffPayrollBuildPageQuery = static function (int $page, int $pageSize): array {
	return [
		'limit' => $pageSize + 1,
		'offset' => max(0, ($page - 1) * $pageSize),
	];
};

$staffPayrollFinalizePagination = static function (array $rows, int $page, int $pageSize): array {
	$hasNext = count($rows) > $pageSize;
	if ($hasNext) {
		$rows = array_slice($rows, 0, $pageSize);
	}

	return [
		'rows' => array_values($rows),
		'page' => $page,
		'page_size' => $pageSize,
		'has_prev' => $page > 1,
		'has_next' => $hasNext,
	];
};

$staffPayrollPeriodsPagination = [
	'page' => $staffPayrollPeriodsPage,
	'page_size' => $staffPayrollPageSize,
	'has_prev' => false,
	'has_next' => false,
];
$staffPayrollAdjustmentsPagination = [
	'page' => $staffPayrollAdjustmentsPage,
	'page_size' => $staffPayrollPageSize,
	'has_prev' => false,
	'has_next' => false,
];
$staffPayrollRunsPagination = [
	'page' => $staffPayrollRunsPage,
	'page_size' => $staffPayrollPageSize,
	'has_prev' => false,
	'has_next' => false,
];

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
		'pending_review' => ['Pending Review', 'bg-amber-100 text-amber-800'],
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
	. '&order=period_end.desc'
	. '&limit=' . (int)$staffPayrollBuildPageQuery($staffPayrollPeriodsPage, $staffPayrollPageSize)['limit']
	. '&offset=' . (int)$staffPayrollBuildPageQuery($staffPayrollPeriodsPage, $staffPayrollPageSize)['offset'],
	$headers
);
$appendDataError('Payroll periods', $periodsResponse);
$periodRows = isSuccessful($periodsResponse) ? (array)($periodsResponse['data'] ?? []) : [];
$staffPayrollPeriodsPagination = $staffPayrollFinalizePagination($periodRows, $staffPayrollPeriodsPage, $staffPayrollPageSize);
$periodRows = (array)($staffPayrollPeriodsPagination['rows'] ?? []);

$runPageQuery = $staffPayrollBuildPageQuery($staffPayrollRunsPage, $staffPayrollPageSize);
$runsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/payroll_runs?select=id,payroll_period_id,run_status,generated_by,generated_at,approved_at,created_at,period:payroll_periods(period_code,period_start,period_end,status)'
	. '&order=created_at.desc'
	. '&limit=' . (int)$runPageQuery['limit']
	. '&offset=' . (int)$runPageQuery['offset'],
	$headers
);
$appendDataError('Payroll runs', $runsResponse);
$runRows = isSuccessful($runsResponse) ? (array)($runsResponse['data'] ?? []) : [];
$staffPayrollRunsPagination = $staffPayrollFinalizePagination($runRows, $staffPayrollRunsPage, $staffPayrollPageSize);
$runRows = (array)($staffPayrollRunsPagination['rows'] ?? []);

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

$employmentPeopleResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/employment_records?select=person_id,employment_status,person:people!employment_records_person_id_fkey(id,user_id,first_name,middle_name,surname)'
	. '&is_current=eq.true&limit=10000',
	$headers
);
$appendDataError('Employee employment records', $employmentPeopleResponse);

if (isSuccessful($employmentPeopleResponse)) {
	foreach ((array)($employmentPeopleResponse['data'] ?? []) as $employmentRow) {
		$employmentStatus = strtolower((string)(cleanText($employmentRow['employment_status'] ?? null) ?? 'active'));
		if ($employmentStatus !== 'active') {
			continue;
		}

		$personId = cleanText($employmentRow['person_id'] ?? null) ?? '';
		if (!isValidUuid($personId)) {
			continue;
		}

		$personRow = is_array($employmentRow['person'] ?? null) ? (array)$employmentRow['person'] : [];
		if (!isset($personRow['id']) || !isValidUuid((string)$personRow['id'])) {
			$personRow['id'] = $personId;
		}

		$employeePeopleByPersonId[$personId] = $personRow;
	}
}

$employeeCompensationByPerson = [];
if (!empty($employeePeopleByPersonId)) {
	$personFilter = $formatUuidInFilter(array_keys($employeePeopleByPersonId));
	$visiblePeriodStarts = array_values(array_filter(array_map(
		static fn(array $periodRow): string => (string)(cleanText($periodRow['period_start'] ?? null) ?? ''),
		$periodRows
	)));
	$earliestVisiblePeriodStart = !empty($visiblePeriodStarts) ? min($visiblePeriodStarts) : '';
	$latestVisiblePeriodStart = !empty($visiblePeriodStarts) ? max($visiblePeriodStarts) : '';
	if ($personFilter !== '') {
		$compensationUrl = $supabaseUrl
			. '/rest/v1/employee_compensations?select=id,person_id,effective_from,effective_to,monthly_rate,base_pay,allowance_total,tax_deduction,government_deductions,other_deductions,pay_frequency,created_at'
			. '&person_id=in.' . rawurlencode('(' . $personFilter . ')');
		if ($latestVisiblePeriodStart !== '') {
			$compensationUrl .= '&effective_from=lte.' . rawurlencode($latestVisiblePeriodStart);
		}
		if ($earliestVisiblePeriodStart !== '') {
			$compensationUrl .= '&or=' . rawurlencode('(effective_to.is.null,effective_to.gte.' . $earliestVisiblePeriodStart . ')');
		}
		$compensationUrl .= '&order=effective_from.desc,created_at.desc&limit=10000';

		$compensationResponse = apiRequest(
			'GET',
			$compensationUrl,
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

$recommendationByRunId = [];
$adminDecisionByRunId = [];
if (!empty($runIds)) {
	$runLogResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at'
		. '&entity_name=eq.payroll_runs'
		. '&entity_id=in.' . rawurlencode('(' . implode(',', $runIds) . ')')
		. '&action_name=in.' . rawurlencode('(submit_batch_for_admin_approval,review_batch)')
		. '&order=created_at.desc&limit=10000',
		$headers
	);
	$appendDataError('Payroll run handoff logs', $runLogResponse);

	if (isSuccessful($runLogResponse)) {
		foreach ((array)($runLogResponse['data'] ?? []) as $logRow) {
			$runId = cleanText($logRow['entity_id'] ?? null) ?? '';
			if (!isValidUuid($runId)) {
				continue;
			}

			$actionName = strtolower((string)(cleanText($logRow['action_name'] ?? null) ?? ''));
			$newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];

			if ($actionName === 'submit_batch_for_admin_approval' && !isset($recommendationByRunId[$runId])) {
				$recommendationByRunId[$runId] = [
					'text' => cleanText($newData['staff_recommendation'] ?? null) ?? 'Recommend approval',
					'submitted_at' => cleanText($logRow['created_at'] ?? null) ?? '',
				];
			}

			if ($actionName === 'review_batch' && !isset($adminDecisionByRunId[$runId])) {
				$decisionRaw = strtolower((string)(cleanText($newData['status'] ?? null) ?? (cleanText($newData['new_status'] ?? null) ?? '')));
				if (!in_array($decisionRaw, ['approved', 'cancelled', 'rejected', 'needs_revision'], true)) {
					$decisionRaw = 'reviewed';
				}

				$adminDecisionByRunId[$runId] = [
					'decision' => $decisionRaw,
					'notes' => cleanText($newData['notes'] ?? null) ?? (cleanText($newData['comment'] ?? null) ?? ''),
					'decided_at' => cleanText($logRow['created_at'] ?? null) ?? '',
				];
			}
		}
	}
}

$itemRows = [];
if (!empty($runIds)) {
	$itemsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payroll_items?select=id,payroll_run_id,person_id,basic_pay,overtime_pay,allowances_total,gross_pay,net_pay,deductions_total,created_at,person:people(id,user_id,first_name,middle_name,surname)'
		. '&payroll_run_id=in.' . rawurlencode('(' . implode(',', $runIds) . ')')
		. '&limit=5000',
		$headers
	);
	$appendDataError('Payroll items', $itemsResponse);
	$itemRows = isSuccessful($itemsResponse) ? (array)($itemsResponse['data'] ?? []) : [];
}

$scopedItemRows = [];
foreach ($itemRows as $itemRow) {
	$personId = cleanText($itemRow['person_id'] ?? null) ?? '';

	if (
		!empty($employeePeopleByPersonId)
		&& (!isValidUuid($personId) || !isset($employeePeopleByPersonId[$personId]))
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

$approvedAdjustmentByItemId = [];
if (!empty($itemIds)) {
	$adjustmentsForItemsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,amount'
		. '&payroll_item_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
		. '&limit=10000',
		$headers
	);
	$appendDataError('Payroll adjustments for payroll items', $adjustmentsForItemsResponse);
	$adjustmentRowsForItems = isSuccessful($adjustmentsForItemsResponse) ? (array)($adjustmentsForItemsResponse['data'] ?? []) : [];

	$adjustmentIdsForItems = [];
	foreach ($adjustmentRowsForItems as $adjustmentRow) {
		$adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
		if (!isValidUuid($adjustmentId)) {
			continue;
		}

		$adjustmentIdsForItems[] = $adjustmentId;
	}

	$reviewStatusByAdjustmentId = [];
	if (!empty($adjustmentIdsForItems)) {
		$adjustmentReviewResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
			. '&entity_name=eq.payroll_adjustments'
			. '&action_name=eq.review_payroll_adjustment'
			. '&entity_id=in.' . rawurlencode('(' . implode(',', $adjustmentIdsForItems) . ')')
			. '&order=created_at.desc&limit=10000',
			$headers
		);
		$appendDataError('Payroll adjustment reviews for payroll items', $adjustmentReviewResponse);
		$adjustmentReviewRows = isSuccessful($adjustmentReviewResponse) ? (array)($adjustmentReviewResponse['data'] ?? []) : [];

		foreach ($adjustmentReviewRows as $reviewRow) {
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

	foreach ($adjustmentRowsForItems as $adjustmentRow) {
		$adjustmentId = cleanText($adjustmentRow['id'] ?? null) ?? '';
		if (!isValidUuid($adjustmentId)) {
			continue;
		}

		$itemId = cleanText($adjustmentRow['payroll_item_id'] ?? null) ?? '';
		if (!isValidUuid($itemId)) {
			continue;
		}

		if (strtolower((string)($reviewStatusByAdjustmentId[$adjustmentId] ?? 'pending')) !== 'approved') {
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

$itemBreakdownByItemId = [];
if (!empty($itemIds)) {
	$itemBreakdownResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
		. '&entity_name=eq.payroll_items'
		. '&action_name=eq.compute_item_breakdown'
		. '&entity_id=in.' . rawurlencode('(' . implode(',', $itemIds) . ')')
		. '&order=created_at.desc&limit=10000',
		$headers
	);
	$appendDataError('Payroll item breakdown logs', $itemBreakdownResponse);

	if (isSuccessful($itemBreakdownResponse)) {
		foreach ((array)($itemBreakdownResponse['data'] ?? []) as $breakdownLog) {
			$itemId = cleanText($breakdownLog['entity_id'] ?? null) ?? '';
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
				'adjustment_earnings' => (float)($earnings['adjustment_earnings'] ?? 0),
				'absent_days' => (int)($attendanceSource['absent_days'] ?? 0),
				'late_minutes' => (int)($attendanceSource['late_minutes'] ?? 0),
				'undertime_hours' => (float)($attendanceSource['undertime_hours'] ?? 0),
				'attendance_source' => $attendanceSource,
			];
		}
	}
}

$periodStatsById = [];
$visiblePeriodIds = [];
foreach ($periodRows as $periodRow) {
	$periodId = cleanText($periodRow['id'] ?? null) ?? '';
	if (isValidUuid($periodId)) {
		$visiblePeriodIds[] = $periodId;
	}
}

if (!empty($visiblePeriodIds)) {
	$periodRunsResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/payroll_runs?select=id,payroll_period_id'
		. '&payroll_period_id=in.' . rawurlencode('(' . implode(',', $visiblePeriodIds) . ')')
		. '&limit=1000',
		$headers
	);
	$appendDataError('Payroll period run stats', $periodRunsResponse);
	$periodRunRows = isSuccessful($periodRunsResponse) ? (array)($periodRunsResponse['data'] ?? []) : [];

	$periodRunIds = [];
	$periodIdByRunId = [];
	foreach ($periodRunRows as $periodRunRow) {
		$runId = cleanText($periodRunRow['id'] ?? null) ?? '';
		$periodId = cleanText($periodRunRow['payroll_period_id'] ?? null) ?? '';
		if (!isValidUuid($runId) || !isValidUuid($periodId)) {
			continue;
		}

		$periodRunIds[] = $runId;
		$periodIdByRunId[$runId] = $periodId;
		if (!isset($periodStatsById[$periodId])) {
			$periodStatsById[$periodId] = [
				'run_count' => 0,
				'employees' => [],
			];
		}
		$periodStatsById[$periodId]['run_count']++;
	}

	if (!empty($periodRunIds)) {
		$periodItemsResponse = apiRequest(
			'GET',
			$supabaseUrl
			. '/rest/v1/payroll_items?select=payroll_run_id,person_id'
			. '&payroll_run_id=in.' . rawurlencode('(' . implode(',', $periodRunIds) . ')')
			. '&limit=5000',
			$headers
		);
		$appendDataError('Payroll period employee stats', $periodItemsResponse);
		$periodItemRows = isSuccessful($periodItemsResponse) ? (array)($periodItemsResponse['data'] ?? []) : [];

		foreach ($periodItemRows as $periodItemRow) {
			$runId = cleanText($periodItemRow['payroll_run_id'] ?? null) ?? '';
			$personId = cleanText($periodItemRow['person_id'] ?? null) ?? '';
			$periodId = $periodIdByRunId[$runId] ?? '';
			if (!isValidUuid($periodId) || !isValidUuid($personId)) {
				continue;
			}

			$periodStatsById[$periodId]['employees'][$personId] = true;
		}
	}
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
$batchBreakdownByRun = [];

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
	$itemId = cleanText($item['id'] ?? null) ?? '';
	$approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
		? (array)$approvedAdjustmentByItemId[$itemId]
		: ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
	$adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
	$adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);

	$runStatsById[$runId]['gross_total'] += $adjustmentEarnings;
	$runStatsById[$runId]['net_total'] += (float)($item['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

	$itemId = cleanText($item['id'] ?? null) ?? '';
	if ($itemId !== '' && !empty($releasedByItemId[$itemId])) {
		$runStatsById[$runId]['released_count']++;
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
	$hasStaffSubmission = isset($recommendationByRunId[$runId]);
	$effectiveStatus = (!$hasStaffSubmission && $statusRaw === 'computed') ? 'pending_review' : $statusRaw;
	[$statusLabel, $statusClass] = $runStatusPill($effectiveStatus);
	$runStats = $runStatsById[$runId] ?? ['employees' => [], 'gross_total' => 0.0, 'net_total' => 0.0, 'released_count' => 0];

	$periodCode = cleanText($run['period']['period_code'] ?? null) ?? 'Uncoded';
	$periodStart = cleanText($run['period']['period_start'] ?? null) ?? '';
	$periodEnd = cleanText($run['period']['period_end'] ?? null) ?? '';
	$periodLabel = ($periodStart !== '' && $periodEnd !== '')
		? (formatDateTimeForPhilippines($periodStart, 'M d, Y') . ' - ' . formatDateTimeForPhilippines($periodEnd, 'M d, Y'))
		: $periodCode;

	$generateRows = [];
	$breakdownRows = [];
	$breakdownEmployeeIds = [];
	$breakdownTotalGross = 0.0;
	$breakdownTotalNet = 0.0;
	foreach ($itemRows as $itemRow) {
		$itemRunId = cleanText($itemRow['payroll_run_id'] ?? null) ?? '';
		if ($itemRunId !== $runId) {
			continue;
		}

		$personId = cleanText($itemRow['person_id'] ?? null) ?? '';
		if (isValidUuid($personId)) {
			$breakdownEmployeeIds[$personId] = true;
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

		$itemId = cleanText($itemRow['id'] ?? null) ?? '';
		$approvedAdjustment = is_array($approvedAdjustmentByItemId[$itemId] ?? null)
			? (array)$approvedAdjustmentByItemId[$itemId]
			: ['adjustment_earnings' => 0.0, 'adjustment_deductions' => 0.0];
		$adjustmentEarnings = (float)($approvedAdjustment['adjustment_earnings'] ?? 0);
		$adjustmentDeductions = (float)($approvedAdjustment['adjustment_deductions'] ?? 0);
		$grossPayAdjusted = (float)($itemRow['gross_pay'] ?? 0) + $adjustmentEarnings;
		$netPayAdjusted = (float)($itemRow['net_pay'] ?? 0) + $adjustmentEarnings - $adjustmentDeductions;

		$breakdown = is_array($itemBreakdownByItemId[$itemId] ?? null) ? (array)$itemBreakdownByItemId[$itemId] : [];
		$statutoryDeductions = (float)($breakdown['statutory_deductions'] ?? 0);
		$timekeepingDeductions = (float)($breakdown['timekeeping_deductions'] ?? 0);
		if (!isset($itemBreakdownByItemId[$itemId])) {
			$statutoryDeductions = max(0.0, (float)($itemRow['deductions_total'] ?? 0));
			$timekeepingDeductions = 0.0;
		}

		$generateRows[] = [
			'employee_name' => $fullName,
			'gross_pay' => $grossPayAdjusted,
			'net_pay' => $netPayAdjusted,
		];

		$breakdownRows[] = [
			'employee_name' => $fullName,
			'basic_pay' => (float)($itemRow['basic_pay'] ?? ($breakdown['basic_pay'] ?? 0)),
			'cto_pay' => (float)($itemRow['overtime_pay'] ?? ($breakdown['cto_pay'] ?? 0)),
			'allowances_total' => (float)($itemRow['allowances_total'] ?? ($breakdown['allowances_total'] ?? 0)),
			'gross_pay' => $grossPayAdjusted,
			'net_pay' => $netPayAdjusted,
			'statutory_deductions' => $statutoryDeductions,
			'timekeeping_deductions' => $timekeepingDeductions,
			'adjustment_deductions' => $adjustmentDeductions,
			'adjustment_earnings' => $adjustmentEarnings,
			'absent_days' => (int)($breakdown['absent_days'] ?? 0),
			'late_minutes' => (int)($breakdown['late_minutes'] ?? 0),
			'undertime_hours' => (float)($breakdown['undertime_hours'] ?? 0),
		];

		$breakdownTotalGross += $grossPayAdjusted;
		$breakdownTotalNet += $netPayAdjusted;
	}

	usort($generateRows, static function (array $left, array $right): int {
		return strcmp(
			strtolower((string)($left['employee_name'] ?? '')),
			strtolower((string)($right['employee_name'] ?? ''))
		);
	});

	usort($breakdownRows, static function (array $left, array $right): int {
		return strcmp(
			strtolower((string)($left['employee_name'] ?? '')),
			strtolower((string)($right['employee_name'] ?? ''))
		);
	});

	$batchBreakdownByRun[$runId] = [
		'employee_count' => count($breakdownEmployeeIds),
		'total_gross' => $breakdownTotalGross,
		'total_net' => $breakdownTotalNet,
		'rows' => $breakdownRows,
	];

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
		'period_label' => $periodLabel,
		'generated_label' => formatDateTimeForPhilippines(cleanText($run['generated_at'] ?? null), 'M d, Y'),
		'approved_label' => formatDateTimeForPhilippines(cleanText($run['approved_at'] ?? null), 'M d, Y'),
		'status_raw' => $effectiveStatus,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'can_generate' => $statusRaw === 'approved',
		'employee_count' => count((array)($runStats['employees'] ?? [])),
		'gross_total' => (float)($runStats['gross_total'] ?? 0),
		'net_total' => (float)($runStats['net_total'] ?? 0),
		'released_count' => (int)($runStats['released_count'] ?? 0),
		'staff_recommendation' => (string)(($recommendationByRunId[$runId]['text'] ?? 'Not yet submitted by Staff')),
		'staff_submitted_label' => formatDateTimeForPhilippines(($recommendationByRunId[$runId]['submitted_at'] ?? null), 'M d, Y h:i A'),
		'admin_decision' => (string)($adminDecisionByRunId[$runId]['decision'] ?? ''),
		'admin_decision_notes' => (string)($adminDecisionByRunId[$runId]['notes'] ?? ''),
		'admin_decision_label' => formatDateTimeForPhilippines(($adminDecisionByRunId[$runId]['decided_at'] ?? null), 'M d, Y h:i A'),
		'search_text' => strtolower(trim(
			$periodCode . ' '
			. $statusLabel . ' '
			. $runId . ' '
			. (string)($recommendationByRunId[$runId]['text'] ?? '') . ' '
			. (string)($adminDecisionByRunId[$runId]['decision'] ?? '')
		)),
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


$adjustmentsPageQuery = $staffPayrollBuildPageQuery($staffPayrollAdjustmentsPage, $staffPayrollPageSize);
$adjustmentsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/payroll_adjustments?select=id,payroll_item_id,adjustment_type,adjustment_code,description,amount,created_at,item:payroll_items(id,payroll_run_id,person_id,person:people(id,user_id,first_name,middle_name,surname),run:payroll_runs(id,payroll_period_id,period:payroll_periods(period_code)))'
	. '&order=created_at.desc'
	. '&limit=' . (int)$adjustmentsPageQuery['limit']
	. '&offset=' . (int)$adjustmentsPageQuery['offset'],
	$headers
);
$appendDataError('Payroll adjustments', $adjustmentsResponse);
$adjustmentRows = isSuccessful($adjustmentsResponse) ? (array)($adjustmentsResponse['data'] ?? []) : [];
$staffPayrollAdjustmentsPagination = $staffPayrollFinalizePagination($adjustmentRows, $staffPayrollAdjustmentsPage, $staffPayrollPageSize);
$adjustmentRows = (array)($staffPayrollAdjustmentsPagination['rows'] ?? []);

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
		$reviewStatus = strtolower((string)(cleanText($newData['review_status'] ?? null) ?? cleanText($newData['status_to'] ?? null) ?? cleanText($newData['status'] ?? null) ?? 'pending'));
		if (!in_array($reviewStatus, ['pending', 'approved', 'rejected'], true)) {
			$reviewStatus = 'pending';
		}

		$reviewStatusByAdjustmentId[$entityId] = $reviewStatus;
	}
}

$staffRecommendationByAdjustmentId = [];
if (!empty($adjustmentIds)) {
	$recommendationLogResponse = apiRequest(
		'GET',
		$supabaseUrl
		. '/rest/v1/activity_logs?select=entity_id,new_data,created_at'
		. '&entity_name=eq.payroll_adjustments'
		. '&action_name=eq.recommend_payroll_adjustment'
		. '&entity_id=in.' . rawurlencode('(' . implode(',', $adjustmentIds) . ')')
		. '&order=created_at.desc'
		. '&limit=5000',
		$headers
	);
	$appendDataError('Payroll adjustment recommendations', $recommendationLogResponse);
	$recommendationLogRows = isSuccessful($recommendationLogResponse) ? (array)($recommendationLogResponse['data'] ?? []) : [];

	foreach ($recommendationLogRows as $logRow) {
		$entityId = cleanText($logRow['entity_id'] ?? null) ?? '';
		if (!isValidUuid($entityId) || isset($staffRecommendationByAdjustmentId[$entityId])) {
			continue;
		}

		$newData = is_array($logRow['new_data'] ?? null) ? (array)$logRow['new_data'] : [];
		$recommendationStatus = strtolower((string)(cleanText($newData['recommendation_status'] ?? null) ?? ''));
		if (!in_array($recommendationStatus, ['approved', 'rejected'], true)) {
			$recommendationStatus = '';
		}

		$staffRecommendationByAdjustmentId[$entityId] = [
			'recommendation_status' => $recommendationStatus,
			'notes' => cleanText($newData['notes'] ?? null) ?? '',
			'submitted_at' => cleanText($logRow['created_at'] ?? null) ?? '',
		];
	}
}

$runAdjustmentSummaryByRun = [];
foreach ($adjustmentRows as $adjustment) {
	$adjustmentId = cleanText($adjustment['id'] ?? null) ?? '';
	if (!isValidUuid($adjustmentId)) {
		continue;
	}

	$itemRow = is_array($adjustment['item'] ?? null) ? (array)$adjustment['item'] : [];
	$runId = cleanText($itemRow['payroll_run_id'] ?? null) ?? '';
	if (!isValidUuid($runId)) {
		continue;
	}
	$personRow = is_array($itemRow['person'] ?? null) ? (array)$itemRow['person'] : [];
	$personId = cleanText($itemRow['person_id'] ?? null) ?? '';
	if (
		!empty($employeePeopleByPersonId)
		&& (!isValidUuid($personId) || !isset($employeePeopleByPersonId[$personId]))
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
	$recommendationRow = is_array($staffRecommendationByAdjustmentId[$adjustmentId] ?? null)
		? (array)$staffRecommendationByAdjustmentId[$adjustmentId]
		: [];
	$isSubmittedToAdmin = !empty($recommendationRow);
	[$statusLabel, $statusClass] = $adjustmentStatusPill($reviewStatus);

	$adjustmentCode = cleanText($adjustment['adjustment_code'] ?? null) ?? 'Adjustment';
	$periodCode = cleanText($periodRow['period_code'] ?? null) ?? '-';
	$description = cleanText($adjustment['description'] ?? null) ?? '-';
	$typeLabel = ucwords(str_replace('_', ' ', (string)(cleanText($adjustment['adjustment_type'] ?? null) ?? 'adjustment')));

	$payrollAdjustmentRows[] = [
		'id' => $adjustmentId,
		'run_id' => $runId,
		'adjustment_code' => $adjustmentCode,
		'employee_name' => $fullName,
		'period_code' => $periodCode,
		'adjustment_type_label' => $typeLabel,
		'description' => $description,
		'amount' => (float)($adjustment['amount'] ?? 0),
		'status_raw' => $reviewStatus,
		'status_label' => $statusLabel,
		'status_class' => $statusClass,
		'is_submitted_to_admin' => $isSubmittedToAdmin,
		'staff_recommendation' => (string)($recommendationRow['recommendation_status'] ?? ''),
		'staff_recommendation_label' => formatDateTimeForPhilippines(($recommendationRow['submitted_at'] ?? null), 'M d, Y h:i A'),
		'created_label' => formatDateTimeForPhilippines(cleanText($adjustment['created_at'] ?? null), 'M d, Y'),
		'search_text' => strtolower(trim($adjustmentCode . ' ' . $fullName . ' ' . $periodCode . ' ' . $description . ' ' . $statusLabel)),
	];

	if ($isSubmittedToAdmin) {
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
			'adjustment_code' => $adjustmentCode,
			'employee_name' => $fullName,
			'adjustment_type_label' => $typeLabel,
			'description' => $description,
			'amount' => (float)($adjustment['amount'] ?? 0),
			'staff_recommendation' => (string)($recommendationRow['recommendation_status'] ?? ''),
			'staff_recommendation_label' => formatDateTimeForPhilippines(($recommendationRow['submitted_at'] ?? null), 'M d, Y h:i A'),
			'staff_recommendation_notes' => (string)($recommendationRow['notes'] ?? ''),
			'admin_status_raw' => $reviewStatus,
			'admin_status_label' => $statusLabel,
		];
	}
}

$payrollAdjustmentRows = array_values($payrollAdjustmentRows);

foreach ($payrollRunRows as &$payrollRunRow) {
	$runId = cleanText($payrollRunRow['id'] ?? null) ?? '';
	$summary = is_array($runAdjustmentSummaryByRun[$runId] ?? null)
		? (array)$runAdjustmentSummaryByRun[$runId]
		: ['submitted_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0, 'rows' => []];

	$payrollRunRow['adjustment_submitted_count'] = (int)($summary['submitted_count'] ?? 0);
	$payrollRunRow['adjustment_pending_count'] = (int)($summary['pending_count'] ?? 0);
	$payrollRunRow['adjustment_approved_count'] = (int)($summary['approved_count'] ?? 0);
	$payrollRunRow['adjustment_rejected_count'] = (int)($summary['rejected_count'] ?? 0);
}
unset($payrollRunRow);

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
