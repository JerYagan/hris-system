<?php
require_once __DIR__ . '/includes/applicants/bootstrap.php';
require_once __DIR__ . '/includes/applicants/actions.php';
require_once __DIR__ . '/includes/applicants/data.php';

$pageTitle = 'Applicants | Admin';
$activePage = 'applicants.php';
$breadcrumbs = ['Recruitment', 'Applicants'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/applicants/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
