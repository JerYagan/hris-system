<?php
require_once __DIR__ . '/includes/payroll-management/bootstrap.php';
require_once __DIR__ . '/includes/payroll-management/actions.php';
require_once __DIR__ . '/includes/payroll-management/data.php';

$pageTitle = 'Payroll Management | Admin';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/payroll-management/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
