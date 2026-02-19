<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';
$resolvedStaffOfficeId = cleanText($staffOfficeId ?? null) ?? '';

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

if ($action === 'update_payroll_period_status') {
    $periodId = cleanText($_POST['period_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $notes = cleanText($_POST['status_notes'] ?? null);

    if (!isValidUuid($periodId)) {
        redirectWithState('error', 'Invalid payroll period selected.');
    }

    if (!in_array($newStatus, ['open', 'processing', 'posted', 'closed'], true)) {
        redirectWithState('error', 'Invalid payroll period status selected.');
    }

    $periodResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_periods?select=id,period_code,status&id=eq.' . rawurlencode($periodId) . '&limit=1',
        $headers
    );

    $periodRow = isSuccessful($periodResponse) ? ($periodResponse['data'][0] ?? null) : null;
    if (!is_array($periodRow)) {
        redirectWithState('error', 'Payroll period not found.');
    }

    if (!$isAdminScope) {
        if (!isValidUuid($resolvedStaffOfficeId)) {
            redirectWithState('error', 'Payroll office scope is missing.');
        }

        $scopeResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/payroll_runs?select=id'
            . '&payroll_period_id=eq.' . rawurlencode($periodId)
            . '&office_id=eq.' . rawurlencode($resolvedStaffOfficeId)
            . '&limit=1',
            $headers
        );

        $scopeRow = isSuccessful($scopeResponse) ? ($scopeResponse['data'][0] ?? null) : null;
        if (!is_array($scopeRow)) {
            redirectWithState('error', 'You cannot update payroll periods outside your office scope.');
        }
    }

    $oldStatus = strtolower((string)(cleanText($periodRow['status'] ?? null) ?? 'open'));
    if (!canTransitionStatus('payroll_periods', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid payroll period transition from ' . $oldStatus . ' to ' . $newStatus . '.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/payroll_periods?id=eq.' . rawurlencode($periodId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $newStatus,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update payroll period status.');
    }

    $writeActivityLog(
        'payroll_periods',
        $periodId,
        'update_payroll_period_status',
        ['status' => $oldStatus],
        ['status' => $newStatus, 'notes' => $notes]
    );

    redirectWithState('success', 'Payroll period status updated to ' . $newStatus . '.');
}

if ($action === 'update_payroll_run_status') {
    $runId = cleanText($_POST['run_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['new_status'] ?? null) ?? ''));
    $notes = cleanText($_POST['status_notes'] ?? null);

    if (!isValidUuid($runId)) {
        redirectWithState('error', 'Invalid payroll run selected.');
    }

    if (!in_array($newStatus, ['draft', 'computed', 'approved', 'released', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid payroll run status selected.');
    }

    $runResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/payroll_runs?select=id,office_id,run_status,generated_by,payroll_period_id&id=eq.' . rawurlencode($runId) . '&limit=1',
        $headers
    );

    $runRow = isSuccessful($runResponse) ? ($runResponse['data'][0] ?? null) : null;
    if (!is_array($runRow)) {
        redirectWithState('error', 'Payroll run not found.');
    }

    $runOfficeId = cleanText($runRow['office_id'] ?? null) ?? '';
    if (!$isAdminScope) {
        if (!isValidUuid($resolvedStaffOfficeId) || !isValidUuid($runOfficeId) || strcasecmp($runOfficeId, $resolvedStaffOfficeId) !== 0) {
            redirectWithState('error', 'You cannot update payroll runs outside your office scope.');
        }
    }

    $oldStatus = strtolower((string)(cleanText($runRow['run_status'] ?? null) ?? 'draft'));
    if (!canTransitionStatus('payroll_runs', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid payroll run transition from ' . $oldStatus . ' to ' . $newStatus . '.');
    }

    $patchPayload = [
        'run_status' => $newStatus,
        'updated_at' => gmdate('c'),
    ];

    if ($newStatus === 'approved') {
        $patchPayload['approved_by'] = $staffUserId;
        $patchPayload['approved_at'] = gmdate('c');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/payroll_runs?id=eq.' . rawurlencode($runId),
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update payroll run status.');
    }

    $generatedBy = cleanText($runRow['generated_by'] ?? null) ?? '';
    if (isValidUuid($generatedBy) && strcasecmp($generatedBy, $staffUserId) !== 0) {
        $notifyUser(
            $generatedBy,
            'Payroll Run Status Updated',
            'A payroll run you generated was marked as ' . str_replace('_', ' ', $newStatus) . '.'
        );
    }

    $writeActivityLog(
        'payroll_runs',
        $runId,
        'update_payroll_run_status',
        ['run_status' => $oldStatus],
        ['run_status' => $newStatus, 'notes' => $notes]
    );

    redirectWithState('success', 'Payroll run status updated to ' . $newStatus . '.');
}

redirectWithState('error', 'Unknown payroll management action.');
