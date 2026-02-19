<?php

$recruitmentRows = [];
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
$scopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$postingResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/job_postings?select=id,title,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title)'
    . $scopeFilter
    . '&order=updated_at.desc&limit=500',
    $headers
);
$appendDataError('Job postings', $postingResponse);
$postingRows = isSuccessful($postingResponse) ? (array)($postingResponse['data'] ?? []) : [];

$postingIds = [];
foreach ($postingRows as $posting) {
    $postingId = cleanText($posting['id'] ?? null);
    if ($postingId === null || !isValidUuid($postingId)) {
        continue;
    }
    $postingIds[] = $postingId;
}

$applicationCountByPosting = [];
if (!empty($postingIds)) {
    $applicationResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/applications?select=id,job_posting_id,application_status'
        . '&job_posting_id=in.' . rawurlencode('(' . implode(',', $postingIds) . ')')
        . '&limit=5000',
        $headers
    );
    $appendDataError('Applications', $applicationResponse);
    $applicationRows = isSuccessful($applicationResponse) ? (array)($applicationResponse['data'] ?? []) : [];

    foreach ($applicationRows as $application) {
        $postingId = cleanText($application['job_posting_id'] ?? null);
        if ($postingId === null || !isValidUuid($postingId)) {
            continue;
        }

        if (!isset($applicationCountByPosting[$postingId])) {
            $applicationCountByPosting[$postingId] = ['total' => 0, 'pending' => 0];
        }

        $applicationCountByPosting[$postingId]['total']++;
        $status = strtolower((string)(cleanText($application['application_status'] ?? null) ?? 'submitted'));
        if (in_array($status, ['submitted', 'screening', 'shortlisted', 'interview', 'offer'], true)) {
            $applicationCountByPosting[$postingId]['pending']++;
        }
    }
}

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'published' => ['Published', 'bg-emerald-100 text-emerald-800'],
        'closed' => ['Closed', 'bg-amber-100 text-amber-800'],
        'archived' => ['Archived', 'bg-slate-200 text-slate-700'],
        default => ['Draft', 'bg-blue-100 text-blue-800'],
    };
};

foreach ($postingRows as $posting) {
    $postingId = cleanText($posting['id'] ?? null) ?? '';
    if (!isValidUuid($postingId)) {
        continue;
    }

    $statusRaw = strtolower((string)(cleanText($posting['posting_status'] ?? null) ?? 'draft'));
    [$statusLabel, $statusClass] = $statusPill($statusRaw);

    $title = cleanText($posting['title'] ?? null) ?? 'Untitled Posting';
    $officeName = cleanText($posting['office']['office_name'] ?? null) ?? 'Unassigned Office';
    $positionTitle = cleanText($posting['position']['position_title'] ?? null) ?? 'Unassigned Position';
    $counts = $applicationCountByPosting[$postingId] ?? ['total' => 0, 'pending' => 0];

    $recruitmentRows[] = [
        'id' => $postingId,
        'title' => $title,
        'office_name' => $officeName,
        'position_title' => $positionTitle,
        'open_date_label' => formatDateTimeForPhilippines(cleanText($posting['open_date'] ?? null), 'M d, Y'),
        'close_date_label' => formatDateTimeForPhilippines(cleanText($posting['close_date'] ?? null), 'M d, Y'),
        'status_raw' => $statusRaw,
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'applications_total' => (int)($counts['total'] ?? 0),
        'applications_pending' => (int)($counts['pending'] ?? 0),
        'search_text' => strtolower(trim($title . ' ' . $officeName . ' ' . $positionTitle . ' ' . $statusLabel)),
    ];
}