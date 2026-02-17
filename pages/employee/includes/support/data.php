<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$supportSummary = [
    'total_inquiries' => 0,
    'high_priority' => 0,
    'recent_30_days' => 0,
];

$supportInquiries = [];
$supportPage = max(1, (int)($_GET['page'] ?? 1));
$supportPageSize = 10;

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$historyResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at'
    . '&actor_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&module_name=eq.employee'
    . '&entity_name=eq.support_inquiries'
    . '&action_name=eq.submit_support_inquiry'
    . '&order=created_at.desc'
    . '&limit=500',
    $headers
);

if (!isSuccessful($historyResponse)) {
    $dataLoadError = 'Unable to load support inquiry history right now.';
    return;
}

$rows = (array)($historyResponse['data'] ?? []);
$supportSummary['total_inquiries'] = count($rows);

$thirtyDaysAgo = strtotime('-30 days');

foreach ($rows as $rowRaw) {
    $row = (array)$rowRaw;
    $payload = (array)($row['new_data'] ?? []);
    $priority = strtolower((string)($payload['priority'] ?? 'normal'));
    $createdAt = (string)($row['created_at'] ?? '');

    if ($priority === 'high') {
        $supportSummary['high_priority']++;
    }

    $createdTs = strtotime($createdAt);
    if ($createdTs !== false && $createdTs >= $thirtyDaysAgo) {
        $supportSummary['recent_30_days']++;
    }

    $supportInquiries[] = [
        'id' => (string)($row['id'] ?? ''),
        'category' => (string)($payload['category'] ?? 'general'),
        'subject' => (string)($payload['subject'] ?? 'Support Inquiry'),
        'message' => (string)($payload['message'] ?? ''),
        'priority' => $priority,
        'status' => (string)($payload['status'] ?? 'submitted'),
        'created_at' => $createdAt,
    ];
}

$offset = ($supportPage - 1) * $supportPageSize;
$supportInquiries = array_slice($supportInquiries, $offset, $supportPageSize + 1);
$supportHasNextPage = count($supportInquiries) > $supportPageSize;
if ($supportHasNextPage) {
    array_pop($supportInquiries);
}

$supportPagination = [
    'page' => $supportPage,
    'has_previous' => $supportPage > 1,
    'has_next' => $supportHasNextPage,
    'previous_page' => $supportPage > 1 ? $supportPage - 1 : null,
    'next_page' => $supportHasNextPage ? $supportPage + 1 : null,
];
