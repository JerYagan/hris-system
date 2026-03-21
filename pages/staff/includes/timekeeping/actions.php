<?php

require_once __DIR__ . '/../../../shared/lib/rfid-attendance.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';
$actorUserId = isValidUuid((string)($staffUserId ?? '')) ? (string)$staffUserId : null;

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$isPersonInScope = static function (string $personId) use ($supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    return isSuccessful($scopeResponse) && !empty($scopeResponse['data'][0]);
};

$notifyRequester = static function (string $recipientUserId, string $title, string $body) use ($supabaseUrl, $headers): void {
    if (!isValidUuid($recipientUserId)) {
        return;
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'recipient_user_id' => $recipientUserId,
            'category' => 'timekeeping',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/employee/timekeeping.php',
        ]]
    );
};

$notifyAdmins = static function (string $title, string $body) use ($supabaseUrl, $headers): void {
    $adminUserIdMap = fetchActiveRoleUserIdMap($supabaseUrl, $headers, 'admin');
    foreach (array_keys($adminUserIdMap) as $adminUserId) {
        if (!isValidUuid((string)$adminUserId)) {
            continue;
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'recipient_user_id' => (string)$adminUserId,
                'category' => 'timekeeping',
                'title' => $title,
                'body' => $body,
                'link_url' => '/hris-system/pages/admin/timekeeping.php',
            ]]
        );
    }
};

$writeActivityLog = static function (string $entityName, string $entityId, string $actionName, array $oldData, array $newData) use ($supabaseUrl, $headers, $actorUserId): void {
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $actorUserId,
            'module_name' => 'timekeeping',
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'action_name' => $actionName,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => clientIp(),
        ]]
    );
};

$resolvePersonByEmployeeCode = static function (string $employeeCode) use ($supabaseUrl, $headers): array {
    $normalizedCode = strtoupper(trim($employeeCode));
    if ($normalizedCode === '') {
        return [];
    }

    $personResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/people?select=id,first_name,surname,agency_employee_no,user_id'
        . '&agency_employee_no=eq.' . rawurlencode($normalizedCode)
        . '&limit=1',
        $headers
    );

    $person = rfidApiFirstRow($personResponse);
    if ($person === []) {
        return [];
    }

    $personId = cleanText($person['id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        return [];
    }

    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id,office:offices(office_name),position:job_positions(position_title)'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    $employment = rfidApiFirstRow($employmentResponse);
    if ($employment === []) {
        return [];
    }

    return [
        'person_id' => $personId,
        'employee_code' => $normalizedCode,
        'employee_name' => trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['surname'] ?? '')),
        'office_name' => cleanText($employment['office']['office_name'] ?? null) ?? 'Unassigned Division',
        'position_title' => cleanText($employment['position']['position_title'] ?? null) ?? 'Unassigned Position',
        'user_id' => cleanText($person['user_id'] ?? null),
    ];
};

$resolveCardRecordByUid = static function (?string $cardUid) use ($supabaseUrl, $headers): array {
    $normalizedUid = rfidNormalizeCardUid($cardUid);
    if ($normalizedUid === '') {
        return [];
    }

    $response = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/rfid_cards?select=id,person_id,card_uid,card_label,status,issued_at,deactivated_at'
        . '&card_uid=ilike.' . rawurlencode($normalizedUid)
        . '&limit=1',
        $headers
    );

    return rfidApiFirstRow($response);
};

$updateRfidCard = static function (string $cardId, array $payload) use ($supabaseUrl, $headers): array {
    if (!isValidUuid($cardId)) {
        return [];
    }

    $response = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/rfid_cards?id=eq.' . rawurlencode($cardId),
        array_merge($headers, ['Prefer: return=representation']),
        $payload
    );

    return rfidApiFirstRow($response);
};

$createRfidCard = static function (array $payload) use ($supabaseUrl, $headers): array {
    $response = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/rfid_cards',
        array_merge($headers, ['Prefer: return=representation']),
        [$payload]
    );

    return rfidApiFirstRow($response);
};

if ($action === 'assign_rfid_card') {
    $employeeCode = strtoupper((string)(cleanText($_POST['employee_id'] ?? null) ?? ''));
    $cardUid = rfidNormalizeCardUid(cleanText($_POST['card_uid'] ?? null));
    $cardLabel = cleanText($_POST['card_label'] ?? null);

    if ($employeeCode === '') {
        redirectWithState('error', 'Employee ID is required for RFID assignment.');
    }

    if ($cardUid === '') {
        redirectWithState('error', 'RFID card UID is required.');
    }

    $employee = $resolvePersonByEmployeeCode($employeeCode);
    $personId = cleanText($employee['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId) || !$isPersonInScope($personId)) {
        redirectWithState('error', 'Employee was not found in the active staff scope.');
    }

    $existingCardForPerson = rfidResolveActiveCardForPerson($supabaseUrl, $headers, $personId);
    $existingCardByUid = $resolveCardRecordByUid($cardUid);
    $existingUidPersonId = cleanText($existingCardByUid['person_id'] ?? null) ?? '';
    $existingUidStatus = strtolower((string)(cleanText($existingCardByUid['status'] ?? null) ?? ''));

    if ($existingCardByUid !== [] && $existingUidStatus === 'active' && $existingUidPersonId !== $personId) {
        redirectWithState('error', 'RFID card UID is already assigned to another employee.');
    }

    $nowIso = gmdate('c');
    $existingPersonCardId = cleanText($existingCardForPerson['id'] ?? null) ?? '';
    $existingUidCardId = cleanText($existingCardByUid['id'] ?? null) ?? '';
    if (isValidUuid($existingPersonCardId) && $existingPersonCardId !== $existingUidCardId) {
        $updateRfidCard($existingPersonCardId, [
            'status' => 'replaced',
            'deactivated_at' => $nowIso,
            'updated_at' => $nowIso,
        ]);
    }

    $savedCard = [];
    $oldData = $existingCardByUid !== [] ? $existingCardByUid : $existingCardForPerson;
    if (isValidUuid($existingUidCardId)) {
        $savedCard = $updateRfidCard($existingUidCardId, [
            'person_id' => $personId,
            'card_uid' => $cardUid,
            'card_label' => $cardLabel,
            'status' => 'active',
            'issued_at' => $nowIso,
            'deactivated_at' => null,
            'updated_at' => $nowIso,
        ]);
    } else {
        $savedCard = $createRfidCard([
            'person_id' => $personId,
            'card_uid' => $cardUid,
            'card_label' => $cardLabel,
            'status' => 'active',
            'issued_at' => $nowIso,
        ]);
    }

    $savedCardId = cleanText($savedCard['id'] ?? null) ?? '';
    if (!isValidUuid($savedCardId)) {
        redirectWithState('error', 'Unable to save the RFID card assignment. Verify that the RFID schema migration is applied.');
    }

    $employeeName = trim((string)($employee['employee_name'] ?? 'Employee'));
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    $writeActivityLog(
        'rfid_cards',
        $savedCardId,
        'assign_rfid_card',
        is_array($oldData) ? $oldData : [],
        [
            'person_id' => $personId,
            'employee_code' => $employeeCode,
            'employee_name' => $employeeName,
            'card_uid_masked' => rfidMaskCardUid($cardUid),
            'card_label' => $cardLabel,
            'status' => 'active',
        ]
    );

    redirectWithState('success', 'RFID card assigned to ' . $employeeName . '.');
}

if ($action === 'deactivate_rfid_card') {
    $cardId = cleanText($_POST['card_id'] ?? null) ?? '';
    if (!isValidUuid($cardId)) {
        redirectWithState('error', 'Invalid RFID card selected.');
    }

    $cardResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/rfid_cards?select=id,person_id,card_uid,card_label,status'
        . '&id=eq.' . rawurlencode($cardId)
        . '&limit=1',
        $headers
    );
    $card = rfidApiFirstRow($cardResponse);
    $personId = cleanText($card['person_id'] ?? null) ?? '';
    if ($card === [] || !isValidUuid($personId) || !$isPersonInScope($personId)) {
        redirectWithState('error', 'RFID card was not found in the current staff scope.');
    }

    $savedCard = $updateRfidCard($cardId, [
        'status' => 'inactive',
        'deactivated_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ]);
    if ($savedCard === []) {
        redirectWithState('error', 'Unable to deactivate the RFID card.');
    }

    $writeActivityLog(
        'rfid_cards',
        $cardId,
        'deactivate_rfid_card',
        $card,
        [
            'status' => 'inactive',
            'card_uid_masked' => rfidMaskCardUid((string)($card['card_uid'] ?? '')),
        ]
    );

    redirectWithState('success', 'RFID card deactivated successfully.');
}

if ($action === 'staff_rfid_attendance_assist') {
    $employeeCode = strtoupper((string)(cleanText($_POST['employee_id'] ?? null) ?? ''));
    $scannedAt = cleanText($_POST['scanned_at'] ?? null);

    if ($employeeCode === '') {
        redirectWithState('error', 'Employee ID is required to process an RFID tap.');
    }

    $employee = $resolvePersonByEmployeeCode($employeeCode);
    $personId = cleanText($employee['person_id'] ?? null) ?? '';
    if (!isValidUuid($personId) || !$isPersonInScope($personId)) {
        redirectWithState('error', 'Employee was not found in the active staff scope.');
    }

    $activeCard = rfidResolveActiveCardForPerson($supabaseUrl, $headers, $personId);
    $cardUid = cleanText($activeCard['card_uid'] ?? null) ?? '';
    if ($cardUid === '') {
        redirectWithState('error', 'Employee has no active RFID card assignment yet.');
    }

    $result = rfidProcessAttendanceTap($supabaseUrl, $headers, [
        'request_source' => 'employee_simulation',
        'employee_person_id' => $personId,
        'actor_user_id' => $actorUserId,
        'card_uid' => $cardUid,
        'scanned_at' => $scannedAt,
        'raw_payload' => [
            'source_page' => 'staff_timekeeping',
            'source_action' => 'staff_rfid_attendance_assist',
            'employee_code' => $employeeCode,
        ],
    ]);

    if (!(bool)($result['success'] ?? false)) {
        redirectWithState('error', (string)($result['message'] ?? 'Unable to process the RFID tap.'));
    }

    $employeeName = trim((string)($employee['employee_name'] ?? 'Employee'));
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    $actionLabel = strtolower((string)($result['action'] ?? ''));
    $message = match ($actionLabel) {
        'time_in' => 'RFID time-in logged for ' . $employeeName . '.',
        'time_out' => 'RFID time-out logged for ' . $employeeName . '.',
        'duplicate_ignored' => 'Rapid duplicate RFID tap ignored for ' . $employeeName . '.',
        default => (string)($result['message'] ?? 'RFID tap processed successfully.'),
    };

    redirectWithState('success', $message);
}

if ($action === 'review_leave_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid leave request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid leave decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/leave_requests?select=id,person_id,status,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Leave request not found.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Leave request target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('leave_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid leave request transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        'Leave Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your leave request. Final approval will be done by admin.'
    );

    $notifyAdmins(
        'Leave Recommendation Pending Approval',
        'A staff recommendation was submitted for a leave request: ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'leave_requests',
        $requestId,
        'recommend_leave_request',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
        ]
    );

    redirectWithState('success', 'Leave recommendation submitted to admin for final approval.');
}

if ($action === 'review_overtime_request') {
    redirectWithState('error', 'Legacy CTO overtime recommendation is disabled. Review CTO through Leave/CTO Requests only.');
}

if ($action === 'review_ob_request') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid special timekeeping request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid special timekeeping decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/overtime_requests?select=id,person_id,status,reason,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Special timekeeping request not found.');
    }

    $parsedRequest = timekeepingParseTaggedReason((string)($requestRow['reason'] ?? ''));
    if (($parsedRequest['is_special'] ?? false) !== true) {
        redirectWithState('error', 'Only tagged special timekeeping requests can use this review flow.');
    }

    $requestLabel = (string)($parsedRequest['label'] ?? 'Special timekeeping request');
    $requestLabelLower = strtolower($requestLabel);

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', $requestLabel . ' target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('overtime_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid ' . $requestLabelLower . ' transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        $requestLabel . ' Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your ' . $requestLabelLower . '. Final approval will be done by admin.'
    );

    $notifyAdmins(
        $requestLabel . ' Recommendation Pending Approval',
        'A staff recommendation was submitted for a ' . $requestLabelLower . ': ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'overtime_requests',
        $requestId,
        'recommend_ob_request',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
            'request_type' => (string)($parsedRequest['request_type'] ?? 'official_business'),
            'request_label' => $requestLabel,
        ]
    );

    redirectWithState('success', $requestLabel . ' recommendation submitted to admin for final approval.');
}

if ($action === 'review_time_adjustment') {
    $requestId = cleanText($_POST['request_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $notes = cleanText($_POST['notes'] ?? null);

    if (!isValidUuid($requestId)) {
        redirectWithState('error', 'Invalid adjustment request selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'needs_revision'], true)) {
        redirectWithState('error', 'Invalid adjustment decision selected.');
    }

    $requestResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/time_adjustment_requests?select=id,person_id,status,attendance_log_id,requested_time_in,requested_time_out,person:people(user_id)&id=eq.' . rawurlencode($requestId) . '&limit=1',
        $headers
    );

    $requestRow = isSuccessful($requestResponse) ? ($requestResponse['data'][0] ?? null) : null;
    if (!is_array($requestRow)) {
        redirectWithState('error', 'Time adjustment request not found.');
    }

    $personId = cleanText($requestRow['person_id'] ?? null) ?? '';
    $recipientUserId = cleanText($requestRow['person']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($personId)) {
        redirectWithState('error', 'Adjustment request target is invalid or no longer active.');
    }

    $oldStatus = strtolower((string)(cleanText($requestRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('time_adjustment_requests', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid adjustment transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $notifyRequester(
        $recipientUserId,
        'Time Adjustment Recommendation Submitted',
        'A staff recommendation (' . str_replace('_', ' ', $decision) . ') was submitted for your time adjustment request. Final approval will be done by admin.'
    );

    $notifyAdmins(
        'Time Adjustment Recommendation Pending Approval',
        'A staff recommendation was submitted for a time adjustment request: ' . str_replace('_', ' ', $decision) . '. Please review for final decision.'
    );

    $writeActivityLog(
        'time_adjustment_requests',
        $requestId,
        'recommend_time_adjustment',
        ['status' => $oldStatus],
        [
            'status' => $oldStatus,
            'recommended_status' => $decision,
            'notes' => $notes,
            'submitted_for_admin_approval' => true,
        ]
    );

    redirectWithState('success', 'Time adjustment recommendation submitted to admin for final approval.');
}

redirectWithState('error', 'Unknown timekeeping action.');
