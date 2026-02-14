<?php
require_once __DIR__ . '/includes/recruitment/bootstrap.php';
require_once __DIR__ . '/includes/recruitment/actions.php';
require_once __DIR__ . '/includes/recruitment/data.php';

$pageTitle = 'Recruitment | Admin';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/recruitment/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
