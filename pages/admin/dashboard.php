<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';
require_once __DIR__ . '/includes/dashboard/data.php';

$pageTitle = 'Admin Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Admin Dashboard'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/dashboard/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
