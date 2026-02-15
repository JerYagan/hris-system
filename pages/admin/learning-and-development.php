<?php
require_once __DIR__ . '/includes/learning-and-development/bootstrap.php';
require_once __DIR__ . '/includes/learning-and-development/actions.php';
require_once __DIR__ . '/includes/learning-and-development/data.php';

$pageTitle = 'Learning and Development | Admin';
$activePage = 'learning-and-development.php';
$breadcrumbs = ['Learning and Development'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/learning-and-development/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
