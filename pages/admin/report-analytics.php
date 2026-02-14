<?php
require_once __DIR__ . '/includes/report-analytics/bootstrap.php';
require_once __DIR__ . '/includes/report-analytics/actions.php';
require_once __DIR__ . '/includes/report-analytics/data.php';

$pageTitle = 'Report and Analytics | Admin';
$activePage = 'report-analytics.php';
$breadcrumbs = ['Report and Analytics'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/report-analytics/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
