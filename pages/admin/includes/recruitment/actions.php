<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('recruitmentIsValidUuid')) {
    function recruitmentIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'create_job_posting') {
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if ($title === '' || $description === '' || $officeId === '' || $positionId === '' || $openDate === '' || $closeDate === '') {
        redirectWithState('error', 'Title, office, position, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId) || !recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected office or position is invalid.');
    }

    if (!in_array($postingStatus, ['draft', 'published', 'closed'], true)) {
        $postingStatus = 'draft';
    }

    if (strtotime($closeDate) < strtotime($openDate)) {
        redirectWithState('error', 'Close date must be on or after open date.');
    }

    $insertPayload = [[
        'office_id' => $officeId,
        'position_id' => $positionId,
        'title' => $title,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'posting_status' => $postingStatus,
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'published_by' => $adminUserId !== '' ? $adminUserId : null,
    ]];

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/job_postings',
        array_merge($headers, ['Prefer: return=representation']),
        $insertPayload
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to create job posting.');
    }

    $createdPostingId = (string)($insertResponse['data'][0]['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $createdPostingId !== '' ? $createdPostingId : null,
            'action_name' => 'create_job_posting',
            'old_data' => null,
            'new_data' => $insertPayload[0],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Job posting created successfully.');
}

if ($action === 'edit_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';
    $title = cleanText($_POST['title'] ?? null) ?? '';
    $officeId = cleanText($_POST['office_id'] ?? null) ?? '';
    $positionId = cleanText($_POST['position_id'] ?? null) ?? '';
    $description = cleanText($_POST['description'] ?? null) ?? '';
    $qualifications = cleanText($_POST['qualifications'] ?? null);
    $responsibilities = cleanText($_POST['responsibilities'] ?? null);
    $openDate = cleanText($_POST['open_date'] ?? null) ?? '';
    $closeDate = cleanText($_POST['close_date'] ?? null) ?? '';
    $postingStatus = strtolower((string)(cleanText($_POST['posting_status'] ?? null) ?? 'draft'));

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    if ($title === '' || $description === '' || $officeId === '' || $positionId === '' || $openDate === '' || $closeDate === '') {
        redirectWithState('error', 'Title, office, position, description, open date, and close date are required.');
    }

    if (!recruitmentIsValidUuid($officeId) || !recruitmentIsValidUuid($positionId)) {
        redirectWithState('error', 'Selected office or position is invalid.');
    }

    if (!in_array($postingStatus, ['draft', 'published', 'closed', 'archived'], true)) {
        $postingStatus = 'draft';
    }

    if (strtotime($closeDate) < strtotime($openDate)) {
        redirectWithState('error', 'Close date must be on or after open date.');
    }

    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id,position_id,description,qualifications,responsibilities,posting_status,open_date,close_date&id=eq.' . $postingId . '&limit=1',
        $headers
    );

    $postingRow = $postingResponse['data'][0] ?? null;
    if (!is_array($postingRow)) {
        redirectWithState('error', 'Job posting record not found.');
    }

    $patchPayload = [
        'title' => $title,
        'office_id' => $officeId,
        'position_id' => $positionId,
        'description' => $description,
        'qualifications' => $qualifications,
        'responsibilities' => $responsibilities,
        'posting_status' => $postingStatus,
        'open_date' => $openDate,
        'close_date' => $closeDate,
        'updated_at' => gmdate('c'),
    ];

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/job_postings?id=eq.' . $postingId,
        array_merge($headers, ['Prefer: return=minimal']),
        $patchPayload
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update job posting.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $postingId,
            'action_name' => 'edit_job_posting',
            'old_data' => [
                'title' => (string)($postingRow['title'] ?? ''),
                'office_id' => (string)($postingRow['office_id'] ?? ''),
                'position_id' => (string)($postingRow['position_id'] ?? ''),
                'description' => (string)($postingRow['description'] ?? ''),
                'posting_status' => (string)($postingRow['posting_status'] ?? ''),
                'open_date' => (string)($postingRow['open_date'] ?? ''),
                'close_date' => (string)($postingRow['close_date'] ?? ''),
            ],
            'new_data' => $patchPayload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Job posting updated successfully.');
}

if ($action === 'archive_job_posting') {
    $postingId = cleanText($_POST['posting_id'] ?? null) ?? '';

    if (!recruitmentIsValidUuid($postingId)) {
        redirectWithState('error', 'Invalid job posting selected.');
    }

    $postingResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status&id=eq.' . $postingId . '&limit=1',
        $headers
    );

    $postingRow = $postingResponse['data'][0] ?? null;
    if (!is_array($postingRow)) {
        redirectWithState('error', 'Job posting record not found.');
    }

    if (strtolower((string)($postingRow['posting_status'] ?? '')) === 'archived') {
        redirectWithState('success', 'Job posting is already archived.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/job_postings?id=eq.' . $postingId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'posting_status' => 'archived',
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to archive job posting.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'recruitment',
            'entity_name' => 'job_postings',
            'entity_id' => $postingId,
            'action_name' => 'archive_job_posting',
            'old_data' => ['posting_status' => (string)($postingRow['posting_status'] ?? '')],
            'new_data' => ['posting_status' => 'archived'],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Job posting archived successfully.');
}

if ($action !== '') {
    redirectWithState('error', 'Unknown recruitment action.');
}
