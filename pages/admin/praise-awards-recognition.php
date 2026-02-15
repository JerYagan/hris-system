<?php
require_once __DIR__ . '/includes/praise-awards-recognition/bootstrap.php';
require_once __DIR__ . '/includes/praise-awards-recognition/actions.php';
require_once __DIR__ . '/includes/praise-awards-recognition/data.php';

$pageTitle = 'Awards and Recognition | Admin';
$activePage = 'praise-awards-recognition.php';
$breadcrumbs = ['PRAISE', 'Awards and Recognition'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/praise-awards-recognition/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
