<?php
require_once __DIR__ . '/includes/applicant-tracking/bootstrap.php';
require_once __DIR__ . '/includes/applicant-tracking/actions.php';
require_once __DIR__ . '/includes/applicant-tracking/data.php';

$pageTitle = 'Applicant Tracking | Admin';
$activePage = 'applicant-tracking.php';
$breadcrumbs = ['Recruitment', 'Applicant Tracking'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/applicant-tracking/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
