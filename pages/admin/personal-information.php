<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';

$pageTitle = 'Personal Information | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$personalInfoActionPath = 'personal-information.php';
$personalInfoProfilesPath = 'personal-information-profiles.php';
$personalInfoAuditPath = 'personal-information-audit-logs.php';
$personalInfoEmployeeRegionUrl = $personalInfoActionPath . '?partial=employee-region';
$personalInfoProfileSource = 'personal-information';
$personalInfoCurrentSection = 'workspace';
$personalInfoShowProfileCards = false;

$personalInfoPartial = trim((string)($_GET['partial'] ?? ''));
$personalInfoDataStage = $personalInfoPartial === 'employee-region' ? 'employee-region' : 'shell';

require_once __DIR__ . '/includes/personal-information/data.php';

if ($personalInfoPartial === 'employee-region') {
	$renderPersonalInfoEmployeeRegionOnly = true;
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	require __DIR__ . '/includes/personal-information/view.php';
	exit;
}

ob_start();
require __DIR__ . '/includes/personal-information/view.php';
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
