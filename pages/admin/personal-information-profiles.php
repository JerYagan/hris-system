<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';

$pageTitle = 'Staff & Employee Profiles | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information', 'Staff & Employee Profiles'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$personalInfoActionPath = 'personal-information-profiles.php';
$personalInfoProfilesPath = 'personal-information-profiles.php';
$personalInfoAuditPath = 'personal-information-audit-logs.php';
$personalInfoEmployeeRegionUrl = 'personal-information.php?partial=employee-region';
$personalInfoProfileSource = 'personal-information-profiles';
$personalInfoCurrentSection = 'profiles';
$personalInfoDataStage = 'employee-region';
$personalInfoShowProfileCards = true;

require_once __DIR__ . '/includes/personal-information/data.php';

$renderPersonalInfoEmployeeRegionOnly = true;

ob_start();
?>
<?php if ($state && $message): ?>
    <?php
    $alertClass = $state === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-red-200 bg-red-50 text-red-700';
    $icon = $state === 'success' ? 'check_circle' : 'error';
    ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm flex gap-2 <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>">
        <span class="material-symbols-outlined text-base"><?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($schemaSupportNotice)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">info</span>
        <span><?= htmlspecialchars((string)$schemaSupportNotice, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <div class="px-6 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
            <p class="text-sm text-slate-500 mt-1">Use the section menu to switch between request review and the full staff and employee directory.</p>
        </div>
        <nav class="flex flex-wrap items-center gap-2" aria-label="Personal information sections">
            <a href="personal-information.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Requests &amp; Overview</a>
            <a href="personal-information-profiles.php" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white">Staff &amp; Employee Profiles</a>
            <a href="personal-information-audit-logs.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Audit &amp; Logs</a>
        </nav>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Staff &amp; Employee Profiles</h2>
        <p class="text-sm text-slate-500 mt-1">Manage employee records and open complete profile pages from a dedicated sub-page.</p>
    </header>
</section>

<?php require __DIR__ . '/includes/personal-information/view.php'; ?>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
?>