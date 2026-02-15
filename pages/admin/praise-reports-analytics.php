<?php
require_once __DIR__ . '/includes/praise-reports-analytics/bootstrap.php';
require_once __DIR__ . '/includes/praise-reports-analytics/data.php';

$pageTitle = 'PRAISE Reports and Analytics | Admin';
$activePage = 'praise-reports-analytics.php';
$breadcrumbs = ['PRAISE', 'Reports and Analytics'];

ob_start();
require __DIR__ . '/includes/praise-reports-analytics/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
