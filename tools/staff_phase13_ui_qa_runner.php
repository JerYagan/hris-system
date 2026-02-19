<?php

declare(strict_types=1);

function printCheck(string $id, bool $pass, string $detail): void
{
    echo sprintf("[%s] %s - %s\n", $pass ? 'PASS' : 'FAIL', $id, $detail);
}

function readText(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $contents = file_get_contents($path);
    return is_string($contents) ? $contents : '';
}

$root = dirname(__DIR__);
$staffRoot = $root . '/pages/staff';
$assetsRoot = $root . '/assets/js/staff';

$pages = [
    'dashboard.php' => ['module' => 'dashboard', 'requires_localized_js' => false],
    'personal-information.php' => ['module' => 'personal-information', 'requires_localized_js' => true],
    'document-management.php' => ['module' => 'document-management', 'requires_localized_js' => true],
    'recruitment.php' => ['module' => 'recruitment', 'requires_localized_js' => true],
    'applicant-tracking.php' => ['module' => 'applicant-tracking', 'requires_localized_js' => true],
    'applicant-registration.php' => ['module' => 'applicant-registration', 'requires_localized_js' => true],
    'timekeeping.php' => ['module' => 'timekeeping', 'requires_localized_js' => true],
    'payroll-management.php' => ['module' => 'payroll-management', 'requires_localized_js' => true],
    'evaluation.php' => ['module' => 'evaluation', 'requires_localized_js' => true],
    'praise.php' => ['module' => 'praise', 'requires_localized_js' => true],
    'reports.php' => ['module' => 'reports', 'requires_localized_js' => true],
    'notifications.php' => ['module' => 'notifications', 'requires_localized_js' => true],
    'profile.php' => ['module' => 'profile', 'requires_localized_js' => true],
];

$hasFailures = false;
$checkedPages = 0;
$checkedScripts = 0;
$checkedActions = 0;

foreach ($pages as $pageFile => $meta) {
    $module = (string)$meta['module'];
    $pagePath = $staffRoot . '/' . $pageFile;
    $pageText = readText($pagePath);

    $pageExists = $pageText !== '';
    printCheck(
        'PAGE_PRESENT_' . strtoupper(str_replace(['-', '.php'], ['_', ''], $pageFile)),
        $pageExists,
        $pageExists ? $pageFile . ' is present.' : 'Missing file: ' . $pageFile
    );
    if (!$pageExists) {
        $hasFailures = true;
        continue;
    }

    $checkedPages++;

    $bootstrapPath = "'/includes/{$module}/bootstrap.php'";
    $hasBootstrapInclude = str_contains($pageText, $bootstrapPath);
    printCheck(
        'BOOTSTRAP_INCLUDE_' . strtoupper(str_replace('-', '_', $module)),
        $hasBootstrapInclude,
        $hasBootstrapInclude ? 'Bootstrap include is wired.' : 'Missing bootstrap include for module.'
    );
    if (!$hasBootstrapInclude) {
        $hasFailures = true;
    }

    $bootstrapFilePath = $staffRoot . '/includes/' . $module . '/bootstrap.php';
    $bootstrapFileExists = is_file($bootstrapFilePath);
    printCheck(
        'BOOTSTRAP_FILE_' . strtoupper(str_replace('-', '_', $module)),
        $bootstrapFileExists,
        $bootstrapFileExists ? 'Bootstrap file exists.' : 'Missing bootstrap file.'
    );
    if (!$bootstrapFileExists) {
        $hasFailures = true;
    }

    if ((bool)$meta['requires_localized_js'] === true) {
        $scriptInclude = '../../assets/js/staff/' . $module . '/index.js" defer';
        $hasScriptInclude = str_contains($pageText, $scriptInclude);
        printCheck(
            'SCRIPT_INCLUDE_' . strtoupper(str_replace('-', '_', $module)),
            $hasScriptInclude,
            $hasScriptInclude ? 'Localized JS include is present with defer.' : 'Missing localized JS include/defer.'
        );
        if (!$hasScriptInclude) {
            $hasFailures = true;
        }

        $scriptPath = $assetsRoot . '/' . $module . '/index.js';
        $scriptExists = is_file($scriptPath);
        printCheck(
            'SCRIPT_FILE_' . strtoupper(str_replace('-', '_', $module)),
            $scriptExists,
            $scriptExists ? 'Localized JS file exists.' : 'Missing localized JS file.'
        );
        if (!$scriptExists) {
            $hasFailures = true;
        }

        $checkedScripts++;
    }

    $actionsPath = $staffRoot . '/includes/' . $module . '/actions.php';
    $actionsText = readText($actionsPath);

    $actionFileExists = $actionsText !== '';
    printCheck(
        'ACTIONS_FILE_' . strtoupper(str_replace('-', '_', $module)),
        $actionFileExists,
        $actionFileExists ? 'Actions file exists.' : 'Missing actions file.'
    );
    if (!$actionFileExists) {
        $hasFailures = true;
        continue;
    }

    $hasPostGate = str_contains($actionsText, "\$_SERVER['REQUEST_METHOD']") && str_contains($actionsText, 'POST');
    printCheck(
        'ACTIONS_POST_GATE_' . strtoupper(str_replace('-', '_', $module)),
        $hasPostGate,
        $hasPostGate ? 'POST gate is present.' : 'Missing POST method gate.'
    );
    if (!$hasPostGate) {
        $hasFailures = true;
    }

    $hasCsrfGate = str_contains($actionsText, 'requireStaffPostWithCsrf')
        || str_contains($actionsText, "isValidCsrfToken(\$_POST['csrf_token']");
    printCheck(
        'ACTIONS_CSRF_GATE_' . strtoupper(str_replace('-', '_', $module)),
        $hasCsrfGate,
        $hasCsrfGate ? 'CSRF gate is present.' : 'Missing CSRF validation gate.'
    );
    if (!$hasCsrfGate) {
        $hasFailures = true;
    }

    $checkedActions++;
}

printCheck('SUMMARY_PAGES_CHECKED', true, (string)$checkedPages);
printCheck('SUMMARY_LOCALIZED_SCRIPTS_CHECKED', true, (string)$checkedScripts);
printCheck('SUMMARY_ACTIONS_CHECKED', true, (string)$checkedActions);

exit($hasFailures ? 1 : 0);
