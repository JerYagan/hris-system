<?php
require_once __DIR__ . '/includes/evaluation/bootstrap.php';
require_once __DIR__ . '/includes/evaluation/actions.php';
require_once __DIR__ . '/includes/evaluation/data.php';

$pageTitle = 'Evaluation | Admin';
$activePage = 'evaluation.php';
$breadcrumbs = ['Recruitment', 'Evaluation'];

ob_start();
require __DIR__ . '/includes/evaluation/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
