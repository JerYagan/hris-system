<?php

$praiseAwardRows = [];
$praiseNominationRows = [];
$praiseMetrics = [
    'active_awards' => 0,
    'pending_nominations' => 0,
    'approved_nominations' => 0,
    'rejected_nominations' => 0,
];
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


$isAdminScope = strtolower((string)($staffRoleKey ?? '')) === 'admin';

$personScopeMap = [];
if (!$isAdminScope && isValidUuid((string)$staffOfficeId)) {
    $scopeResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=person_id'
        . '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
        . '&is_current=eq.true'
        . '&limit=5000',
        $headers
    );

    $appendDataError('PRAISE scope', $scopeResponse);
    $scopeRows = isSuccessful($scopeResponse) ? (array)($scopeResponse['data'] ?? []) : [];

    foreach ($scopeRows as $scopeRow) {
        $personId = cleanText($scopeRow['person_id'] ?? null);
        if ($personId === null || !isValidUuid($personId)) {
            continue;
        }

        $personScopeMap[$personId] = true;
    }
}

$awardsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/praise_awards?select=id,award_code,award_name,description,criteria,is_active,updated_at'
    . '&order=updated_at.desc&limit=1000',
    $headers
);
$appendDataError('PRAISE awards', $awardsResponse);
$awardRows = isSuccessful($awardsResponse) ? (array)($awardsResponse['data'] ?? []) : [];

foreach ($awardRows as $award) {
    $awardId = cleanText($award['id'] ?? null) ?? '';
    if (!isValidUuid($awardId)) {
        continue;
    }

    $isActive = (bool)($award['is_active'] ?? false);
    if ($isActive) {
        $praiseMetrics['active_awards']++;
    }

    $praiseAwardRows[] = [
        'id' => $awardId,
        'award_code' => cleanText($award['award_code'] ?? null) ?? '-',
        'award_name' => cleanText($award['award_name'] ?? null) ?? 'Unnamed Award',
        'description' => cleanText($award['description'] ?? null) ?? '-',
        'criteria' => cleanText($award['criteria'] ?? null) ?? '-',
        'is_active' => $isActive,
        'status_label' => $isActive ? 'Active' : 'Inactive',
        'status_class' => $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700',
    ];
}

$nominationEndpoint = $supabaseUrl
    . '/rest/v1/praise_nominations?select=id,award_id,nominee_person_id,nominated_by_user_id,cycle_id,justification,status,reviewed_at,created_at,award:award_id(award_name),nominee:nominee_person_id(first_name,surname,user_id),nominator:nominated_by_user_id(email),cycle:cycle_id(cycle_name)'
    . '&order=created_at.desc&limit=2000';

if (!$isAdminScope) {
    $personIds = array_keys($personScopeMap);
    if (empty($personIds)) {
        $nominationEndpoint .= '&id=is.null';
    } else {
        $nominationEndpoint .= '&nominee_person_id=in.' . rawurlencode('(' . implode(',', $personIds) . ')');
    }
}

$nominationsResponse = apiRequest('GET', $nominationEndpoint, $headers);
$appendDataError('PRAISE nominations', $nominationsResponse);
$nominationRows = isSuccessful($nominationsResponse) ? (array)($nominationsResponse['data'] ?? []) : [];

$nominationStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['Rejected', 'bg-rose-100 text-rose-800'],
        'cancelled' => ['Cancelled', 'bg-slate-200 text-slate-700'],
        default => ['Pending', 'bg-amber-100 text-amber-800'],
    };
};

foreach ($nominationRows as $nomination) {
    $nominationId = cleanText($nomination['id'] ?? null) ?? '';
    if (!isValidUuid($nominationId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($nomination['status'] ?? null) ?? 'pending'));
    [$statusLabel, $statusClass] = $nominationStatusPill($statusRaw);

    if ($statusRaw === 'pending') {
        $praiseMetrics['pending_nominations']++;
    }
    if ($statusRaw === 'approved') {
        $praiseMetrics['approved_nominations']++;
    }
    if ($statusRaw === 'rejected') {
        $praiseMetrics['rejected_nominations']++;
    }

    $nomineeName = trim(
        (string)(cleanText($nomination['nominee']['first_name'] ?? null) ?? '')
        . ' '
        . (string)(cleanText($nomination['nominee']['surname'] ?? null) ?? '')
    );
    if ($nomineeName === '') {
        $nomineeName = 'Unknown Nominee';
    }

    $awardName = cleanText($nomination['award']['award_name'] ?? null) ?? '-';
    $cycleName = cleanText($nomination['cycle']['cycle_name'] ?? null) ?? 'Unassigned Cycle';
    $nominatedBy = cleanText($nomination['nominator']['email'] ?? null) ?? '-';
    $justification = cleanText($nomination['justification'] ?? null) ?? '-';

    $praiseNominationRows[] = [
        'id' => $nominationId,
        'nominee_name' => $nomineeName,
        'award_name' => $awardName,
        'cycle_name' => $cycleName,
        'nominated_by' => $nominatedBy,
        'justification' => $justification,
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'submitted_label' => formatDateTimeForPhilippines(cleanText($nomination['created_at'] ?? null), 'M d, Y'),
        'search_text' => strtolower(trim($nomineeName . ' ' . $awardName . ' ' . $cycleName . ' ' . $nominatedBy . ' ' . $statusLabel . ' ' . $justification)),
    ];
}
