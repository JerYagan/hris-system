<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';

$pageTitle = 'Staff Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/staff/dashboard/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$dashboardPartial = (string)($_GET['partial'] ?? '');
if (in_array($dashboardPartial, ['dashboard-summary', 'dashboard-approvals', 'dashboard-notifications', 'dashboard-announcements'], true)) {
    $dashboardDataStage = match ($dashboardPartial) {
        'dashboard-summary' => 'summary',
        'dashboard-approvals' => 'approvals',
        'dashboard-notifications' => 'notifications',
        default => 'announcements',
    };
    $dashboardContentSection = $dashboardDataStage;
    require_once __DIR__ . '/includes/dashboard/data.php';
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    require __DIR__ . '/includes/dashboard/content.php';
    exit;
}

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Staff Dashboard</h1>
    <p class="text-sm text-gray-500">Operational summary, pending approvals, and role-relevant notifications.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section
    id="staffDashboardAsyncRegion"
    data-dashboard-summary-url="dashboard.php?partial=dashboard-summary"
    data-dashboard-approvals-url="dashboard.php?partial=dashboard-approvals"
    data-dashboard-notifications-url="dashboard.php?partial=dashboard-notifications"
    data-dashboard-announcements-url="dashboard.php?partial=dashboard-announcements"
>
    <div id="staffDashboardSummarySkeleton" class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 xl:grid-cols-4" aria-live="polite" role="status">
        <?php for ($index = 0; $index < 4; $index += 1): ?>
            <div class="bg-white border rounded-xl p-5">
                <div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-4 h-8 w-16 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-full animate-pulse rounded bg-slate-200"></div>
            </div>
        <?php endfor; ?>
    </div>

    <div id="staffDashboardSummaryError" class="hidden mb-8 rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800" aria-live="polite">
        <p class="font-medium">Dashboard summary could not be loaded.</p>
        <p class="mt-1">Retry to load the summary cards.</p>
        <button type="button" id="staffDashboardSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Retry summary</button>
    </div>

    <div id="staffDashboardSummaryContent" class="hidden mb-8"></div>

    <section class="bg-white border rounded-xl p-6 mb-8">
        <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-gray-800">Pending Approvals</h2>
            <p class="text-xs text-gray-500">First page loads on demand. Use the queue links for the full module views.</p>
        </div>

        <div id="staffDashboardApprovalsSkeleton" class="overflow-x-auto rounded-lg border border-slate-200" aria-live="polite" role="status">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Request</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Submitted At</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php for ($index = 0; $index < 5; $index += 1): ?>
                        <tr>
                            <?php for ($column = 0; $column < 6; $column += 1): ?>
                                <td class="px-4 py-3"><div class="h-4 animate-pulse rounded bg-slate-200"></div></td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div id="staffDashboardApprovalsError" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800" aria-live="polite">
            <p class="font-medium">Pending approvals could not be loaded.</p>
            <p class="mt-1">Retry to load the first page of approval work.</p>
            <button type="button" id="staffDashboardApprovalsRetry" class="mt-3 inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Retry approvals</button>
        </div>

        <div id="staffDashboardApprovalsContent" class="hidden"></div>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="min-w-0">
            <div id="staffDashboardNotificationsSkeleton" class="bg-white border rounded-xl p-6" aria-live="polite" role="status">
                <div class="h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-5 space-y-4">
                    <?php for ($row = 0; $row < 4; $row += 1): ?>
                        <div class="border-l-4 border-slate-200 pl-4">
                            <div class="h-4 w-2/3 animate-pulse rounded bg-slate-200"></div>
                            <div class="mt-2 h-3 w-1/2 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div id="staffDashboardNotificationsError" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800" aria-live="polite">
                <p class="font-medium">Notifications could not be loaded.</p>
                <p class="mt-1">Retry to load your role-specific notifications.</p>
                <button type="button" id="staffDashboardNotificationsRetry" class="mt-3 inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Retry notifications</button>
            </div>

            <div id="staffDashboardNotificationsContent" class="hidden"></div>
        </div>

        <div class="min-w-0">
            <div id="staffDashboardAnnouncementsSkeleton" class="bg-white border rounded-xl p-6" aria-live="polite" role="status">
                <div class="h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                <div class="mt-5 space-y-4">
                    <?php for ($row = 0; $row < 4; $row += 1): ?>
                        <div class="border-l-4 border-slate-200 pl-4">
                            <div class="h-4 w-2/3 animate-pulse rounded bg-slate-200"></div>
                            <div class="mt-2 h-3 w-1/2 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div id="staffDashboardAnnouncementsError" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800" aria-live="polite">
                <p class="font-medium">Announcements could not be loaded.</p>
                <p class="mt-1">Retry to load the announcement feed.</p>
                <button type="button" id="staffDashboardAnnouncementsRetry" class="mt-3 inline-flex items-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Retry announcements</button>
            </div>

            <div id="staffDashboardAnnouncementsContent" class="hidden"></div>
        </div>
    </section>

    <noscript>
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            JavaScript is required to load this optimized dashboard view.
        </div>
    </noscript>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
