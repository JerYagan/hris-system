<?php

$awardsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/praise_awards?select=id,award_code,award_name,description,criteria,is_active,created_at&order=created_at.desc&limit=500',
    $headers
);

$nominationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/praise_nominations?select=id,award_id,nominee_person_id,nominated_by_user_id,cycle_id,justification,status,reviewed_at,award:award_id(award_name),nominee:nominee_person_id(first_name,surname),nominator:nominated_by_user_id(email),cycle:cycle_id(cycle_name)&order=created_at.desc&limit=1500',
    $headers
);

$awards = isSuccessful($awardsResponse) ? (array)($awardsResponse['data'] ?? []) : [];
$nominations = isSuccessful($nominationsResponse) ? (array)($nominationsResponse['data'] ?? []) : [];

$awardCategoryRows = [];
foreach ($awards as $award) {
    $awardCategoryRows[] = [
        'award_id' => (string)($award['id'] ?? ''),
        'award_name' => (string)($award['award_name'] ?? '-'),
        'award_code' => (string)($award['award_code'] ?? '-'),
        'description' => (string)($award['description'] ?? ''),
        'criteria' => (string)($award['criteria'] ?? '-'),
        'is_active' => (bool)($award['is_active'] ?? false),
        'created_at' => (string)($award['created_at'] ?? ''),
    ];
}

$nominationApprovalRows = [];
$publishAwardeeRows = [];
$seenPendingNominations = [];
$seenPublishAwardees = [];

foreach ($nominations as $nomination) {
    $nominationId = (string)($nomination['id'] ?? '');
    $awardName = (string)($nomination['award']['award_name'] ?? '-');
    $cycleName = (string)($nomination['cycle']['cycle_name'] ?? '-');
    $nomineeName = trim(((string)($nomination['nominee']['first_name'] ?? '')) . ' ' . ((string)($nomination['nominee']['surname'] ?? '')));
    if ($nomineeName === '') {
        $nomineeName = 'Unknown Employee';
    }

    $nominatedBy = (string)($nomination['nominator']['email'] ?? 'N/A');
    $statusRaw = strtolower((string)($nomination['status'] ?? 'pending'));
    $rowKey = strtolower(trim($nomineeName . '|' . $awardName . '|' . $cycleName));

    if ($statusRaw === 'pending') {
        if (isset($seenPendingNominations[$rowKey])) {
            continue;
        }
        $seenPendingNominations[$rowKey] = true;

        $nominationApprovalRows[] = [
            'nomination_id' => $nominationId,
            'nominee' => $nomineeName,
            'award_name' => $awardName,
            'nominated_by' => $nominatedBy,
            'cycle_name' => $cycleName,
            'search_text' => strtolower(trim($nomineeName . ' ' . $awardName . ' ' . $nominatedBy . ' ' . $cycleName)),
        ];
    }

    if ($statusRaw === 'approved') {
        if (isset($seenPublishAwardees[$rowKey])) {
            continue;
        }
        $seenPublishAwardees[$rowKey] = true;

        $reviewedAt = (string)($nomination['reviewed_at'] ?? '');
        $publishedLabel = $reviewedAt !== '' ? date('M d, Y', strtotime($reviewedAt)) : '-';

        $publishAwardeeRows[] = [
            'nomination_id' => $nominationId,
            'nominee' => $nomineeName,
            'award_name' => $awardName,
            'cycle_name' => $cycleName,
            'published_date' => $publishedLabel,
        ];
    }
}
