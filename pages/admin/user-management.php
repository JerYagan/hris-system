<?php
require_once __DIR__ . '/includes/user-management/bootstrap.php';
require_once __DIR__ . '/includes/user-management/actions.php';
require_once __DIR__ . '/includes/user-management/data.php';

$pageTitle = 'User Management | Admin';
$activePage = 'user-management.php';
$breadcrumbs = ['User Management'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/user-management/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
