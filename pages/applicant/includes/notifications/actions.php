<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'notifications.php');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'notifications.php');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'notifications.php');
}

$action = cleanText($_POST['action'] ?? null);
if ($action === null) {
    redirectWithState('error', 'Invalid notifications action.', 'notifications.php');
}

$readAt = date('c');

if ($action === 'mark_read') {
    $notificationId = cleanText($_POST['notification_id'] ?? null);
    if ($notificationId === null || !isValidUuid($notificationId)) {
        redirectWithState('error', 'Notification reference is missing.', 'notifications.php');
    }

    $markReadResponse = apiRequest(
        'PATCH',
        $supabaseUrl
        . '/rest/v1/notifications?id=eq.' . rawurlencode($notificationId)
        . '&recipient_user_id=eq.' . rawurlencode($applicantUserId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => $readAt,
        ]
    );

    if (!isSuccessful($markReadResponse)) {
        redirectWithState('error', 'Failed to mark notification as read.', 'notifications.php');
    }

    unset($_SESSION['applicant_topnav_cache']);

    redirectWithState('success', 'Notification marked as read.', 'notifications.php');
}

if ($action === 'mark_all_read') {
    $markAllResponse = apiRequest(
        'PATCH',
        $supabaseUrl
        . '/rest/v1/notifications?recipient_user_id=eq.' . rawurlencode($applicantUserId)
        . '&is_read=eq.false',
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'is_read' => true,
            'read_at' => $readAt,
        ]
    );

    if (!isSuccessful($markAllResponse)) {
        redirectWithState('error', 'Failed to mark all notifications as read.', 'notifications.php');
    }

    unset($_SESSION['applicant_topnav_cache']);

    redirectWithState('success', 'All notifications are marked as read.', 'notifications.php');
}

redirectWithState('error', 'Unsupported notifications action.', 'notifications.php');
