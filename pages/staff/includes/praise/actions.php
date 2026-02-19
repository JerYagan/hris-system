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

$isPersonInScope = static function (string $personId) use ($isAdminScope, $resolvedStaffOfficeId, $supabaseUrl, $headers): bool {
    if (!isValidUuid($personId)) {
        return false;
    }

    if ($isAdminScope) {
        return true;
    }

    if (!isValidUuid($resolvedStaffOfficeId)) {
        return false;
    }

    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&office_id=eq.' . rawurlencode($resolvedStaffOfficeId)
        . '&is_current=eq.true'
        . '&limit=1',
        $headers
    );

    return isSuccessful($scopeResponse) && !empty($scopeResponse['data'][0]);
};

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

$canTransitionNomination = static function (string $oldStatus, string $newStatus): bool {
    $oldKey = strtolower(trim($oldStatus));
    $newKey = strtolower(trim($newStatus));

    if ($oldKey === '' || $newKey === '') {
        return false;
    }

    if ($oldKey === $newKey) {
        return true;
    }

    $rules = [
        'pending' => ['approved', 'rejected', 'cancelled'],
    ];

    return isset($rules[$oldKey]) && in_array($newKey, $rules[$oldKey], true);
};

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

    $nominationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/praise_nominations?select=id,status,nominee_person_id,nominated_by_user_id,award:award_id(award_name),nominee:nominee_person_id(first_name,surname,user_id)'
        . '&id=eq.' . rawurlencode($nominationId)
        . '&limit=1',
        $headers
    );

    $nominationRow = isSuccessful($nominationResponse) ? ($nominationResponse['data'][0] ?? null) : null;
    if (!is_array($nominationRow)) {
        redirectWithState('error', 'Nomination record not found.');
    }

    $nomineePersonId = cleanText($nominationRow['nominee_person_id'] ?? null) ?? '';
    $nomineeUserId = cleanText($nominationRow['nominee']['user_id'] ?? null) ?? '';

    if (!$isPersonInScope($nomineePersonId)) {
        redirectWithState('error', 'Nomination is outside your office scope.');
    }

    $oldStatus = strtolower((string)(cleanText($nominationRow['status'] ?? null) ?? 'pending'));
    if (!$canTransitionNomination($oldStatus, $decision)) {
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

redirectWithState('error', 'Unknown PRAISE action.');
