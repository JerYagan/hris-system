<?php
require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/includes/timekeeping/actions.php';
require_once __DIR__ . '/includes/timekeeping/data.php';

$pageTitle = 'Timekeeping | Admin';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/timekeeping/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
