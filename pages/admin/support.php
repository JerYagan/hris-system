<?php
require_once __DIR__ . '/includes/support/bootstrap.php';
require_once __DIR__ . '/includes/support/actions.php';
require_once __DIR__ . '/includes/support/data.php';

$pageTitle = 'Support | Admin';
$activePage = 'support.php';
$breadcrumbs = ['Support'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/support/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
