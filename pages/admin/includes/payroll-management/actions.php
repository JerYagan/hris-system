<?php

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

$action = (string)($_POST['form_action'] ?? '');

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

    $currentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employee_compensations?select=id,monthly_rate,pay_frequency,effective_from,effective_to&person_id=eq.' . $personId . '&effective_to=is.null&order=effective_from.desc&limit=1',
        $headers
    );

    if (!isSuccessful($currentResponse)) {
        redirectWithState('error', 'Failed to load current compensation record.');
    }

    $existingRow = $currentResponse['data'][0] ?? null;

    if (is_array($existingRow) && isValidUuid((string)($existingRow['id'] ?? ''))) {
        $effectiveTo = gmdate('Y-m-d', strtotime($effectiveFrom . ' -1 day'));
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/employee_compensations?id=eq.' . (string)$existingRow['id'],
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'effective_to' => $effectiveTo,
            ]
        );
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/employee_compensations',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'person_id' => $personId,
            'effective_from' => $effectiveFrom,
            'monthly_rate' => $monthlyRate,
            'daily_rate' => round($monthlyRate / 22, 2),
            'hourly_rate' => round($monthlyRate / 22 / 8, 2),
            'pay_frequency' => $payFrequency,
        ]]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to save salary setup.');
    }

    $compensationId = (string)($insertResponse['data'][0]['id'] ?? '');

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

    redirectWithState('success', 'Salary setup saved successfully.');
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

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,run_status,payroll_period_id,payroll_period:payroll_periods(period_code,period_start,period_end)&id=eq.' . $runId . '&limit=1',
        $headers
    );

    $runRow = $runResponse['data'][0] ?? null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll run not found.');
    }

    $itemResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_items?select=id,person_id,net_pay,person:people(first_name,surname,user_id)&payroll_run_id=eq.' . $runId . '&limit=5000',
        $headers
    );

    if (!isSuccessful($itemResponse)) {
        redirectWithState('error', 'Failed to load payroll items for release.');
    }

    $items = (array)($itemResponse['data'] ?? []);
    if (empty($items)) {
        redirectWithState('error', 'No payroll items found for the selected batch.');
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

    $allPayslipIds = formatInFilterList(array_values(array_map(static fn(array $row): string => (string)($row['id'] ?? ''), array_values($existingPayslips))));
    if ($allPayslipIds !== '') {
        apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/payslips?id=in.(' . $allPayslipIds . ')',
            array_merge($headers, ['Prefer: return=minimal']),
            ['released_at' => gmdate('c')]
        );
    }

    apiRequest(
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
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                $notifications
            );
            $notifications = [];
        }
    }

    if (!empty($notifications)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            $notifications
        );
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
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Payslips released successfully for selected payroll batch.');
}

redirectWithState('error', 'Unknown payroll action.');
