<?php
require_once __DIR__ . '/includes/profile/bootstrap.php';
require_once __DIR__ . '/includes/profile/actions.php';
require_once __DIR__ . '/includes/profile/data.php';

$pageTitle = 'My Profile | Admin';
$activePage = 'profile.php';
$breadcrumbs = ['My Profile'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/profile/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
