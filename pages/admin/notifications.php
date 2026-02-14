<?php
require_once __DIR__ . '/includes/notifications/bootstrap.php';
require_once __DIR__ . '/includes/notifications/actions.php';
require_once __DIR__ . '/includes/notifications/data.php';

$pageTitle = 'Notifications | Admin';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/notifications/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
