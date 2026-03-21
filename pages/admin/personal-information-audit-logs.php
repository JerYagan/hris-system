<?php
require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';

$pageTitle = 'Personal Information Audit Logs | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information', 'Audit & Logs'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$personalInfoActionPath = 'personal-information.php';
$personalInfoProfilesPath = 'personal-information-profiles.php';
$personalInfoAuditPath = 'personal-information-audit-logs.php';
$personalInfoCurrentSection = 'audit';
$personalInfoDataStage = 'audit';

require_once __DIR__ . '/includes/personal-information/data.php';

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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <div class="px-6 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
            <p class="text-sm text-slate-500 mt-1">Review request history, approvals, and historical profile activity performed by staff and admin users.</p>
        </div>
        <nav class="flex flex-wrap items-center gap-2" aria-label="Personal information sections">
            <a href="personal-information.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Requests &amp; Overview</a>
            <a href="personal-information-profiles.php" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Staff &amp; Employee Profiles</a>
            <a href="personal-information-audit-logs.php" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white">Audit &amp; Logs</a>
        </nav>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Profile Change Audit Trail</h2>
        <p class="text-sm text-slate-500 mt-1">This log shows submitted profile-change requests, approval decisions, and historical direct-write actions previously performed from admin tools.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm table-fixed">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 w-[16%]">Date</th>
                    <th class="text-left px-4 py-3 w-[16%]">Updated By</th>
                    <th class="text-left px-4 py-3 w-[16%]">Approved By</th>
                    <th class="text-left px-4 py-3 w-[18%]">Employee</th>
                    <th class="text-left px-4 py-3 w-[14%]">Action</th>
                    <th class="text-left px-4 py-3 w-[20%]">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($personalInfoAuditRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No profile audit records are available yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($personalInfoAuditRows as $auditRow): ?>
                        <tr>
                            <td class="px-4 py-3 align-top text-slate-600"><?= htmlspecialchars((string)($auditRow['created_at_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top text-slate-700"><?= htmlspecialchars((string)($auditRow['updated_by_label'] ?? $auditRow['actor_label'] ?? 'System User'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top text-slate-700"><?= htmlspecialchars((string)($auditRow['approved_by_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top text-slate-700"><?= htmlspecialchars((string)($auditRow['employee_name'] ?? 'Unknown Employee'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs <?= htmlspecialchars((string)($auditRow['action_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($auditRow['action_label'] ?? 'Activity Logged'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                <div class="space-y-1">
                                    <p><?= htmlspecialchars((string)($auditRow['notes'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500">Entity: <?= htmlspecialchars((string)($auditRow['entity_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();

include __DIR__ . '/includes/layout.php';
?>