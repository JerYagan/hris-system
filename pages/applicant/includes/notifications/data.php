<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$filter = strtolower((string)(cleanText($_GET['filter'] ?? null) ?? 'all'));

$allowedFilters = ['all', 'unread', 'application', 'system'];
if (!in_array($filter, $allowedFilters, true)) {
	$filter = 'all';
}

$notificationStats = [
	'total' => 0,
	'unread' => 0,
	'recent' => 0,
];

$notifications = [];
$isFilterEmpty = false;

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$notificationsResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/notifications?select=id,category,title,body,link_url,is_read,read_at,created_at'
	. '&recipient_user_id=eq.' . rawurlencode($applicantUserId)
	. '&order=created_at.desc&limit=200',
	$headers
);

if (!isSuccessful($notificationsResponse)) {
	$dataLoadError = 'Failed to load notifications.';
	return;
}

$rawNotifications = (array)($notificationsResponse['data'] ?? []);
$now = time();

foreach ($rawNotifications as $notificationRow) {
	$createdAt = cleanText($notificationRow['created_at'] ?? null);
	$isRead = (bool)($notificationRow['is_read'] ?? false);
	$category = strtolower((string)($notificationRow['category'] ?? 'system'));

	$notificationStats['total']++;
	if (!$isRead) {
		$notificationStats['unread']++;
	}

	if ($createdAt !== null && (strtotime($createdAt) >= strtotime('-7 days', $now))) {
		$notificationStats['recent']++;
	}

	if ($filter === 'unread' && $isRead) {
		continue;
	}

	if ($filter === 'application' && $category !== 'application') {
		continue;
	}

	if ($filter === 'system' && $category !== 'system') {
		continue;
	}

	$notifications[] = [
		'id' => (string)($notificationRow['id'] ?? ''),
		'category' => $category,
		'title' => (string)($notificationRow['title'] ?? 'Notification'),
		'body' => (string)($notificationRow['body'] ?? ''),
		'link_url' => safeLocalLink(cleanText($notificationRow['link_url'] ?? null)),
		'is_read' => $isRead,
		'read_at' => cleanText($notificationRow['read_at'] ?? null),
		'created_at' => $createdAt,
	];
}

$isFilterEmpty = empty($notifications) && !empty($rawNotifications) && $filter !== 'all';

$categoryStyles = [
	'application' => [
		'icon' => 'assignment',
		'icon_color' => 'text-green-700',
	],
	'system' => [
		'icon' => 'info',
		'icon_color' => 'text-gray-500',
	],
	'support' => [
		'icon' => 'support_agent',
		'icon_color' => 'text-blue-600',
	],
	'default' => [
		'icon' => 'notifications',
		'icon_color' => 'text-gray-500',
	],
];
