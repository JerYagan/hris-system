<?php
require_once __DIR__ . '/includes/praise/bootstrap.php';
require_once __DIR__ . '/includes/praise/actions.php';
require_once __DIR__ . '/includes/praise/data.php';

$pageTitle = 'PRAISE | Admin';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/praise/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
