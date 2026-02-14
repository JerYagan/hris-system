<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';
require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Personal Information | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/personal-information/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
