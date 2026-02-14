<?php

$notificationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/notifications?select=id,category,title,body,link_url,is_read,read_at,created_at&recipient_user_id=eq.' . $adminUserId . '&order=created_at.desc&limit=1000',
    $headers
);

$adminAccountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=email&id=eq.' . $adminUserId . '&limit=1',
    $headers
);

$notifications = isSuccessful($notificationsResponse) ? (array)$notificationsResponse['data'] : [];
$adminEmail = '';
if (isSuccessful($adminAccountResponse)) {
    $adminEmail = strtolower((string)($adminAccountResponse['data'][0]['email'] ?? ''));
}

$dataLoadError = null;
if (!isSuccessful($notificationsResponse)) {
    $dataLoadError = 'Notifications query failed (HTTP ' . (int)($notificationsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($notificationsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $dataLoadError .= ' ' . $raw;
    }
}
if (!isSuccessful($adminAccountResponse)) {
    $accountError = 'Admin account query failed (HTTP ' . (int)($adminAccountResponse['status'] ?? 0) . ').';
    $raw = trim((string)($adminAccountResponse['raw'] ?? ''));
    if ($raw !== '') {
        $accountError .= ' ' . $raw;
    }
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $accountError) : $accountError;
}

$totalNotifications = count($notifications);
$unreadNotifications = 0;
$readNotifications = 0;
$categoryCounts = [];

foreach ($notifications as $row) {
    $isRead = (bool)($row['is_read'] ?? false);
    if ($isRead) {
        $readNotifications++;
    } else {
        $unreadNotifications++;
    }

    $category = strtolower(trim((string)($row['category'] ?? 'general')));
    if ($category === '') {
        $category = 'general';
    }
    $categoryCounts[$category] = (int)($categoryCounts[$category] ?? 0) + 1;
}

arsort($categoryCounts);
$topCategory = !empty($categoryCounts) ? (string)array_key_first($categoryCounts) : 'general';
$topCategoryCount = !empty($categoryCounts) ? (int)($categoryCounts[$topCategory] ?? 0) : 0;
