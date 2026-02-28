<?php

$notificationsDataLoadError = null;

$appendNotificationsError = static function (string $label, array $response) use (&$notificationsDataLoadError): void {
    if (isSuccessful($response)) {
        return;
    }

    $message = $label . ' query failed (HTTP ' . (int)($response['status'] ?? 0) . ').';
    $raw = trim((string)($response['raw'] ?? ''));
    if ($raw !== '') {
        $message .= ' ' . $raw;
    }

    $notificationsDataLoadError = $notificationsDataLoadError
        ? ($notificationsDataLoadError . ' ' . $message)
        : $message;
};

$notificationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/notifications?select=id,category,title,body,link_url,is_read,read_at,created_at'
    . '&recipient_user_id=eq.' . rawurlencode($staffUserId)
    . '&order=created_at.desc&limit=1000',
    $headers
);
$appendNotificationsError('Notifications', $notificationsResponse);
$notifications = isSuccessful($notificationsResponse) ? (array)($notificationsResponse['data'] ?? []) : [];

foreach ($notifications as $index => $notificationRow) {
    if (!is_array($notificationRow)) {
        continue;
    }

    $category = strtolower(trim((string)(cleanText($notificationRow['category'] ?? null) ?? '')));
    if ($category !== 'payroll') {
        continue;
    }

    $linkUrl = cleanText($notificationRow['link_url'] ?? null) ?? '';
    $linkLower = strtolower($linkUrl);
    if (
        $linkUrl === ''
        || str_contains($linkLower, '/pages/admin/payroll-management.php')
        || str_contains($linkLower, '/pages/employee/payroll.php')
    ) {
        $notificationRow['link_url'] = '/hris-system/pages/staff/payroll-management.php';
    }

    $notifications[$index] = $notificationRow;
}

$recentActivityResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,module_name,entity_name,entity_id,action_name,new_data,created_at'
    . '&actor_user_id=eq.' . rawurlencode($staffUserId)
    . '&order=created_at.desc&limit=300',
    $headers
);
$appendNotificationsError('Audit logs', $recentActivityResponse);
$recentAuditRows = isSuccessful($recentActivityResponse) ? (array)($recentActivityResponse['data'] ?? []) : [];

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

    $category = strtolower(trim((string)(cleanText($row['category'] ?? null) ?? 'general')));
    if ($category === '') {
        $category = 'general';
    }

    $categoryCounts[$category] = (int)($categoryCounts[$category] ?? 0) + 1;
}

arsort($categoryCounts);
$topCategory = !empty($categoryCounts) ? (string)array_key_first($categoryCounts) : 'general';
$topCategoryCount = !empty($categoryCounts) ? (int)($categoryCounts[$topCategory] ?? 0) : 0;

$auditCount = count($recentAuditRows);
$auditTodayCount = 0;
$todayDate = gmdate('Y-m-d');

foreach ($recentAuditRows as $row) {
    $createdAt = cleanText($row['created_at'] ?? null) ?? '';
    if ($createdAt === '') {
        continue;
    }

    $createdDate = substr($createdAt, 0, 10);
    if ($createdDate === $todayDate) {
        $auditTodayCount++;
    }
}

$dataLoadError = $notificationsDataLoadError;
