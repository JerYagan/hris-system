<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$notificationSummary = [
    'total' => 0,
    'unread' => 0,
    'read' => 0,
];
$notifications = [];

$selectedCategory = strtolower((string)(cleanText($_GET['category'] ?? null) ?? 'all'));
$selectedStatus = strtolower((string)(cleanText($_GET['status'] ?? null) ?? 'all'));
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;

$allowedCategories = ['all', 'system', 'hr', 'application', 'learning_and_development', 'general'];
$allowedStatuses = ['all', 'unread', 'read'];

if (!in_array($selectedCategory, $allowedCategories, true)) {
    $selectedCategory = 'all';
}

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$summaryResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/notifications?select=id,is_read,category'
    . '&recipient_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&limit=500',
    $headers
);

if (!isSuccessful($summaryResponse)) {
    $dataLoadError = 'Unable to load notifications right now. Please try again later.';
    return;
}

$allRows = (array)($summaryResponse['data'] ?? []);
$notificationSummary['total'] = count($allRows);
foreach ($allRows as $summaryRaw) {
    $summary = (array)$summaryRaw;
    $isRead = (bool)($summary['is_read'] ?? false);
    if ($isRead) {
        $notificationSummary['read']++;
    } else {
        $notificationSummary['unread']++;
    }
}

$queryParts = [
    'select=id,category,title,body,link_url,is_read,read_at,created_at',
    'recipient_user_id=eq.' . rawurlencode((string)$employeeUserId),
    'order=created_at.desc',
];

if ($selectedStatus === 'read') {
    $queryParts[] = 'is_read=eq.true';
} elseif ($selectedStatus === 'unread') {
    $queryParts[] = 'is_read=eq.false';
}

if ($selectedCategory !== 'all') {
    if ($selectedCategory === 'system') {
        $queryParts[] = 'category=ilike.*system*';
    } elseif ($selectedCategory === 'hr') {
        $queryParts[] = 'category=ilike.*hr*';
    } elseif ($selectedCategory === 'application') {
        $queryParts[] = 'category=ilike.*application*';
    } elseif ($selectedCategory === 'learning_and_development') {
        $queryParts[] = 'category=ilike.*learning*development*';
    } else {
        $queryParts[] = 'category=ilike.*general*';
    }
}

$offset = ($currentPage - 1) * $pageSize;
$queryParts[] = 'offset=' . $offset;
$queryParts[] = 'limit=' . ($pageSize + 1);

$listResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/notifications?' . implode('&', $queryParts),
    $headers
);

if (!isSuccessful($listResponse)) {
    $dataLoadError = 'Unable to load notifications list right now. Please try again later.';
    return;
}

$rows = (array)($listResponse['data'] ?? []);
$hasNextPage = count($rows) > $pageSize;
if ($hasNextPage) {
    array_pop($rows);
}

foreach ($rows as $notificationRaw) {
    $notification = (array)$notificationRaw;
    $notifications[] = [
        'id' => (string)($notification['id'] ?? ''),
        'category' => (string)($notification['category'] ?? 'General'),
        'title' => (string)($notification['title'] ?? 'Notification'),
        'body' => (string)($notification['body'] ?? ''),
        'link_url' => cleanText($notification['link_url'] ?? null),
        'is_read' => (bool)($notification['is_read'] ?? false),
        'read_at' => (string)($notification['read_at'] ?? ''),
        'created_at' => (string)($notification['created_at'] ?? ''),
    ];
}

$hasPreviousPage = $currentPage > 1;
$notificationPagination = [
    'page' => $currentPage,
    'page_size' => $pageSize,
    'has_next' => $hasNextPage,
    'has_previous' => $hasPreviousPage,
    'next_page' => $hasNextPage ? $currentPage + 1 : null,
    'previous_page' => $hasPreviousPage ? $currentPage - 1 : null,
];
