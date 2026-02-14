<?php
require_once __DIR__ . '/includes/settings/bootstrap.php';
require_once __DIR__ . '/includes/settings/actions.php';
require_once __DIR__ . '/includes/settings/data.php';

$pageTitle = 'Settings | Admin';
$activePage = 'settings.php';
$breadcrumbs = ['Settings'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/settings/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
