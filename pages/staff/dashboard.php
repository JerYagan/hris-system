<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';
require_once __DIR__ . '/includes/dashboard/data.php';

$pageTitle = 'Staff Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
    <p class="text-sm text-gray-500">Overview of HR operations, recruitment pipeline, records, and reporting tasks.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars($dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Employees</p>
        <p class="text-2xl font-bold text-green-700 mt-1"><?= (int)($dashboardMetrics['active_employees'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Current active records in your scope</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Pending Applications</p>
        <p class="text-2xl font-bold text-blue-700 mt-1"><?= (int)($dashboardMetrics['pending_applications'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">For screening and interview</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Documents for Verification</p>
        <p class="text-2xl font-bold text-yellow-700 mt-1"><?= (int)($dashboardMetrics['documents_for_verification'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Awaiting validation</p>
    </div>

    <div class="bg-white border rounded-xl p-5">
        <p class="text-sm text-gray-500">Payroll Tasks</p>
        <p class="text-2xl font-bold text-purple-700 mt-1"><?= (int)($dashboardMetrics['payroll_tasks'] ?? 0) ?></p>
        <p class="text-xs text-gray-500 mt-1">Draft/computed/approved runs</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <section class="bg-white border rounded-xl p-6 xl:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Recruitment & HR Updates</h2>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View all</a>
        </div>

        <ul class="space-y-4 text-sm">
            <?php if (empty($dashboardRecruitmentUpdates)): ?>
                <li class="border-l-4 border-slate-300 pl-4">
                    <p class="font-medium text-gray-800">No recruitment updates available</p>
                    <p class="text-xs text-gray-500">Dashboard data will appear once transactions are available.</p>
                </li>
            <?php else: ?>
                <?php foreach ($dashboardRecruitmentUpdates as $item): ?>
                    <li class="border-l-4 <?= htmlspecialchars((string)($item['accent'] ?? 'border-slate-300'), ENT_QUOTES, 'UTF-8') ?> pl-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($item['title'] ?? 'Update'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string)($item['meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6">
        <h2 class="font-semibold text-gray-800 mb-4">My Tasks</h2>

        <ul class="space-y-3 text-sm">
            <?php foreach ($dashboardTasks as $task): ?>
                <li class="flex justify-between items-center gap-3">
                    <a href="<?= htmlspecialchars((string)($task['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="hover:underline">
                        <?= htmlspecialchars((string)($task['label'] ?? 'Task'), ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-xs text-gray-500">(<?= (int)($task['count'] ?? 0) ?>)</span>
                    </a>
                    <span class="text-xs px-2 py-1 rounded-full <?= htmlspecialchars((string)($task['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($task['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6 xl:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Pending Approvals</h2>
            <a href="timekeeping.php?status=pending" class="text-sm text-green-700 hover:underline">Open timekeeping</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Request</th>
                        <th class="text-left px-4 py-3 font-medium">Owner</th>
                        <th class="text-left px-4 py-3 font-medium">Module</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($dashboardPendingApprovals)): ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-500" colspan="4">No pending approvals found in your current scope.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dashboardPendingApprovals as $row): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="<?= htmlspecialchars((string)($row['action_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="text-green-700 hover:underline">
                                        <?= htmlspecialchars((string)($row['request'] ?? 'Request'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['owner'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['module'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['status_label'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <section class="bg-white border rounded-xl p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Recent Activity</h2>

        <ul class="space-y-3 text-sm text-gray-700">
            <?php if (empty($dashboardRecentActivity)): ?>
                <li>
                    <p>No recent activity yet.</p>
                    <p class="text-xs text-gray-500">Actions you perform will appear here.</p>
                </li>
            <?php else: ?>
                <?php foreach ($dashboardRecentActivity as $activity): ?>
                    <li>
                        <p><?= htmlspecialchars((string)($activity['title'] ?? 'Activity'), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string)($activity['meta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </section>

    <section class="bg-white border rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">HR Shortcuts</h2>
            <a href="reports.php" class="text-sm text-green-700 hover:underline">Generate reports</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <a href="personal-information.php" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-green-700">badge</span>
                Employee Profiles
            </a>
            <a href="document-management.php?status=submitted" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-blue-700">description</span>
                Document Management
            </a>
            <a href="recruitment.php?status=pending" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-purple-700">person_search</span>
                Recruitment
            </a>
            <a href="payroll-management.php?status=pending" class="flex items-center gap-3 border rounded-lg p-3 hover:bg-gray-50">
                <span class="material-symbols-outlined text-yellow-700">download</span>
                Payroll Tasks
            </a>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
