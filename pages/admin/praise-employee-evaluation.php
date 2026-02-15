<?php
require_once __DIR__ . '/includes/praise-employee-evaluation/bootstrap.php';
require_once __DIR__ . '/includes/praise-employee-evaluation/actions.php';
require_once __DIR__ . '/includes/praise-employee-evaluation/data.php';

$pageTitle = 'Employee Evaluation Overview | Admin';
$activePage = 'praise-employee-evaluation.php';
$breadcrumbs = ['PRAISE', 'Employee Evaluation Overview'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
require __DIR__ . '/includes/praise-employee-evaluation/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
