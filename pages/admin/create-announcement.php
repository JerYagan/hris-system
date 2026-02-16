<?php
require_once __DIR__ . '/includes/create-announcement/bootstrap.php';
require_once __DIR__ . '/includes/create-announcement/actions.php';
require_once __DIR__ . '/includes/create-announcement/data.php';

$pageTitle = 'Create Announcement | Admin';
$activePage = 'create-announcement.php';
$breadcrumbs = ['Account', 'Create Announcement'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/create-announcement/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
