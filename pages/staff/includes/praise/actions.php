<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';
requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

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
            'category' => 'praise',
            'title' => $title,
            'body' => $body,
            'link_url' => '/hris-system/pages/staff/praise.php',
        ]]
    );
};

$loadNomination = static function (string $nominationId) use ($supabaseUrl, $headers): ?array {
    if (!isValidUuid($nominationId)) {
        return null;
    }

    $nominationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/praise_nominations?select=id,status,justification,nominee_person_id,nominated_by_user_id,award:award_id(award_name),nominee:nominee_person_id(first_name,surname,user_id)'
        . '&id=eq.' . rawurlencode($nominationId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($nominationResponse)) {
        return null;
    }

    $nominationRow = $nominationResponse['data'][0] ?? null;
    return is_array($nominationRow) ? $nominationRow : null;
};

if ($action === 'create_praise_nomination') {
    $awardId = cleanText($_POST['award_id'] ?? null) ?? '';
    $nomineePersonId = cleanText($_POST['nominee_person_id'] ?? null) ?? '';
    $cycleId = cleanText($_POST['cycle_id'] ?? null);
    $justification = cleanText($_POST['justification'] ?? null) ?? '';

    if (!isValidUuid($awardId)) {
        redirectWithState('error', 'Please select a valid PRAISE award category.');
    }

    if (!isValidUuid($nomineePersonId)) {
        redirectWithState('error', 'Please select a valid employee nominee.');
    }

    if ($cycleId !== null && $cycleId !== '' && !isValidUuid($cycleId)) {
        redirectWithState('error', 'Please select a valid evaluation cycle.');
    }

    if ($justification === '') {
        redirectWithState('error', 'Nomination justification is required.');
    }

    $employeeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person:people!employment_records_person_id_fkey(id,first_name,surname,user_id)'
        . '&person_id=eq.' . rawurlencode($nomineePersonId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    $employeeRow = isSuccessful($employeeResponse) ? ($employeeResponse['data'][0] ?? null) : null;
    if (!is_array($employeeRow)) {
        redirectWithState('error', 'Selected nominee is not an active employee record.');
    }

    $awardResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/praise_awards?select=id,award_name,is_active'
        . '&id=eq.' . rawurlencode($awardId)
        . '&limit=1',
        $headers
    );
    $awardRow = isSuccessful($awardResponse) ? ($awardResponse['data'][0] ?? null) : null;
    if (!is_array($awardRow)) {
        redirectWithState('error', 'Selected award category was not found.');
    }

    if (!(bool)($awardRow['is_active'] ?? false)) {
        redirectWithState('error', 'Selected award category is inactive.');
    }

    $insertPayload = [
        'award_id' => $awardId,
        'nominee_person_id' => $nomineePersonId,
        'nominated_by_user_id' => $staffUserId,
        'justification' => $justification,
        'status' => 'pending',
    ];

    if (isValidUuid((string)$cycleId)) {
        $insertPayload['cycle_id'] = $cycleId;
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/praise_nominations',
        array_merge($headers, ['Prefer: return=representation']),
        [$insertPayload]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to submit employee nomination.');
    }

    $nominationId = cleanText($insertResponse['data'][0]['id'] ?? null) ?? null;
    $awardName = cleanText($awardRow['award_name'] ?? null) ?? 'PRAISE Award';
    $nomineeName = trim(
        (string)(cleanText($employeeRow['person']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($employeeRow['person']['surname'] ?? null) ?? '')
    );
    if ($nomineeName === '') {
        $nomineeName = 'Employee';
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'praise',
            'entity_name' => 'praise_nominations',
            'entity_id' => $nominationId,
            'action_name' => 'create_praise_nomination',
            'old_data' => null,
            'new_data' => [
                'award_id' => $awardId,
                'award_name' => $awardName,
                'nominee_person_id' => $nomineePersonId,
                'nominee_name' => $nomineeName,
                'cycle_id' => isValidUuid((string)$cycleId) ? $cycleId : null,
                'status' => 'pending',
                'justification' => $justification,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $nomineeUserId = cleanText($employeeRow['person']['user_id'] ?? null) ?? '';
    if (isValidUuid($nomineeUserId) && strcasecmp($nomineeUserId, $staffUserId) !== 0) {
        $notifyUser(
            $nomineeUserId,
            'PRAISE Nomination Submitted',
            'You were nominated for ' . $awardName . '. Check the PRAISE module for updates.'
        );
    }

    redirectWithState('success', 'Employee nomination submitted successfully.');
}

if ($action === 'review_praise_nomination') {
    $nominationId = cleanText($_POST['nomination_id'] ?? null) ?? '';
    $decision = strtolower((string)(cleanText($_POST['decision'] ?? null) ?? ''));
    $remarks = cleanText($_POST['remarks'] ?? null);

    if (!isValidUuid($nominationId)) {
        redirectWithState('error', 'Invalid nomination selected.');
    }

    if (!in_array($decision, ['approved', 'rejected', 'cancelled'], true)) {
        redirectWithState('error', 'Invalid nomination decision selected.');
    }

    $nominationRow = $loadNomination($nominationId);
    if (!is_array($nominationRow)) {
        redirectWithState('error', 'Nomination record not found.');
    }

    $nomineeUserId = cleanText($nominationRow['nominee']['user_id'] ?? null) ?? '';

    $oldStatus = strtolower((string)(cleanText($nominationRow['status'] ?? null) ?? 'pending'));
    if (!canTransitionStatus('praise_nominations', $oldStatus, $decision)) {
        redirectWithState('error', 'Invalid nomination transition from ' . $oldStatus . ' to ' . $decision . '.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/praise_nominations?id=eq.' . rawurlencode($nominationId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'status' => $decision,
            'reviewed_by' => $staffUserId,
            'reviewed_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update nomination decision.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'praise',
            'entity_name' => 'praise_nominations',
            'entity_id' => $nominationId,
            'action_name' => 'review_praise_nomination',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $decision, 'remarks' => $remarks],
            'ip_address' => clientIp(),
        ]]
    );

    $awardName = cleanText($nominationRow['award']['award_name'] ?? null) ?? 'PRAISE Award';
    $nomineeName = trim(
        (string)(cleanText($nominationRow['nominee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($nominationRow['nominee']['surname'] ?? null) ?? '')
    );
    if ($nomineeName === '') {
        $nomineeName = 'employee nominee';
    }

    $nominatorUserId = cleanText($nominationRow['nominated_by_user_id'] ?? null) ?? '';
    if (isValidUuid($nominatorUserId) && strcasecmp($nominatorUserId, $staffUserId) !== 0) {
        $notifyUser(
            $nominatorUserId,
            'Nomination Decision Updated',
            'Your ' . $awardName . ' nomination for ' . $nomineeName . ' was marked as ' . str_replace('_', ' ', $decision) . '.'
        );
    }

    if (isValidUuid($nomineeUserId) && strcasecmp($nomineeUserId, $staffUserId) !== 0) {
        $notifyUser(
            $nomineeUserId,
            'PRAISE Nomination Status Updated',
            'Your nomination for ' . $awardName . ' was marked as ' . str_replace('_', ' ', $decision) . '.'
        );
    }

    redirectWithState('success', 'Nomination decision updated to ' . $decision . '.');
}

if ($action === 'publish_praise_awardee') {
    $nominationId = cleanText($_POST['nomination_id'] ?? null) ?? '';

    if (!isValidUuid($nominationId)) {
        redirectWithState('error', 'Invalid nomination selected for publishing.');
    }

    $nominationRow = $loadNomination($nominationId);
    if (!is_array($nominationRow)) {
        redirectWithState('error', 'Nomination record not found.');
    }

    $status = strtolower((string)(cleanText($nominationRow['status'] ?? null) ?? 'pending'));
    if ($status !== 'approved') {
        redirectWithState('error', 'Only approved nominations can be published.');
    }

    $awardName = cleanText($nominationRow['award']['award_name'] ?? null) ?? 'PRAISE Award';
    $nomineeName = trim(
        (string)(cleanText($nominationRow['nominee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($nominationRow['nominee']['surname'] ?? null) ?? '')
    );
    if ($nomineeName === '') {
        $nomineeName = 'Employee';
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $staffUserId,
            'module_name' => 'praise',
            'entity_name' => 'praise_nominations',
            'entity_id' => $nominationId,
            'action_name' => 'publish_praise_awardee',
            'old_data' => ['status' => 'approved'],
            'new_data' => [
                'status' => 'approved',
                'award_name' => $awardName,
                'awardee' => $nomineeName,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    $nomineeUserId = cleanText($nominationRow['nominee']['user_id'] ?? null) ?? '';
    if (isValidUuid($nomineeUserId) && strcasecmp($nomineeUserId, $staffUserId) !== 0) {
        $notifyUser(
            $nomineeUserId,
            'PRAISE Award Published',
            'Congratulations! Your ' . $awardName . ' recognition has been published.'
        );
    }

    redirectWithState('success', 'Awardee published successfully.');
}

redirectWithState('error', 'Unknown PRAISE action.');
