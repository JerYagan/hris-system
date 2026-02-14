<?php
require_once __DIR__ . '/includes/document-management/bootstrap.php';
require_once __DIR__ . '/includes/document-management/actions.php';
require_once __DIR__ . '/includes/document-management/data.php';

$pageTitle = 'Document Management | Admin';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/document-management/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
