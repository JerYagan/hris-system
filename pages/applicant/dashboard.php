<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';
require_once __DIR__ . '/includes/dashboard/data.php';

$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];
$retryUrl = htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? 'dashboard.php'), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= $retryUrl ?>" class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs text-amber-800 hover:bg-amber-100">Retry</a>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($profileCompletionReminder['show_modal'])): ?>
<div id="profileCompletionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-lg rounded-xl border bg-white p-5 shadow-xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Complete Your Profile Before Applying</h2>
                <p class="mt-1 text-sm text-gray-600">Your profile is missing required background details needed for automatic qualification checks.</p>
            </div>
            <button type="button" id="closeProfileCompletionModal" class="rounded-md border px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">Close</button>
        </div>

        <div class="mt-4 space-y-2 text-sm">
            <p class="rounded-md border px-3 py-2 <?= ((int)($profileCompletionReminder['education_entries'] ?? 0) > 0) ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
                Education Entries: <?= (int)($profileCompletionReminder['education_entries'] ?? 0) ?>
            </p>
            <p class="rounded-md border px-3 py-2 <?= ((int)($profileCompletionReminder['work_entries'] ?? 0) > 0) ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
                Work Experience Entries: <?= (int)($profileCompletionReminder['work_entries'] ?? 0) ?>
            </p>
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" id="laterProfileCompletionModal" class="inline-flex items-center rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Remind Me Later</button>
            <a href="profile.php?edit=true" class="inline-flex items-center rounded-md bg-green-700 px-3 py-2 text-sm font-medium text-white hover:bg-green-800">Go to Profile</a>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('profileCompletionModal');
    if (!modal) {
        return;
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    var closeButton = document.getElementById('closeProfileCompletionModal');
    var laterButton = document.getElementById('laterProfileCompletionModal');

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }
    if (laterButton) {
        laterButton.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>
<?php endif; ?>

<!-- HERO -->
<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="overflow-hidden rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="inline-flex rounded-lg bg-green-700 p-2.5 text-white">
                    <span class="material-symbols-outlined text-3xl">emoji_people</span>
                </div>
                <div>
                    <p class="text-sm text-green-700 font-medium">Welcome back, <?= htmlspecialchars((string)$dashboardData['first_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <h1 class="mt-1 text-xl font-semibold text-gray-800 sm:text-2xl">Your Recruitment Journey</h1>
                    <p class="mt-2 max-w-2xl text-sm text-gray-600">
                        Track your application, discover opportunities, and stay updated with HR announcements in one place.
                    </p>
                    <div class="mt-4 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                        <span class="material-symbols-outlined text-xs">hourglass_top</span>
                        <?= htmlspecialchars((string)$dashboardData['status_badge'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>

            <div class="grid w-full grid-cols-2 gap-3 text-sm sm:w-auto sm:min-w-[260px]">
                <div class="rounded-xl border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$dashboardData['active_applications'] ?></p>
                    <p class="text-xs text-gray-500">Active Application</p>
                </div>
                <div class="rounded-xl border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800"><?= (int)$dashboardData['open_jobs'] ?></p>
                    <p class="text-xs text-gray-500">Open Jobs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SNAPSHOT CARDS -->
<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-500">
            <span class="material-symbols-outlined text-[16px]">work</span>
            Position Applied
        </div>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)$dashboardData['position_applied'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-500">
            <span class="material-symbols-outlined text-[16px]">flag</span>
            Current Stage
        </div>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)$dashboardData['current_stage'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-500">
            <span class="material-symbols-outlined text-[16px]">calendar_today</span>
            Date Applied
        </div>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)$dashboardData['date_applied'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-slate-500">
            <span class="material-symbols-outlined text-[16px]">update</span>
            Latest Update
        </div>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars((string)$dashboardData['latest_update'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>
</section>

<!-- APPLICATION PROGRESS -->
<section class="mb-6">
    <div class="rounded-xl border bg-white">
        <header class="flex flex-col gap-2 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-700">route</span>
                <h2 class="text-lg font-semibold text-gray-800">Application Progress</h2>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <?php if (empty($dashboardTimelineItems)): ?>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                    <p class="font-medium text-slate-800">No Applications Yet</p>
                    <p class="mt-1 text-sm text-slate-600">Start by browsing open positions and submitting your first application.</p>
                    <a href="job-list.php" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-green-700 hover:underline">
                        Browse Open Jobs
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="w-full max-w-full overflow-hidden">
                    <ol class="flex w-full items-start">
                        <?php foreach ($dashboardTimelineItems as $index => $timelineItem): ?>
                            <?php
                            $timelineState = (string)($timelineItem['state'] ?? 'upcoming');
                            $isLastItem = $index === (count($dashboardTimelineItems) - 1);
                            $dotClass = 'border-slate-300 bg-white text-slate-400';
                            $lineClass = 'bg-slate-200';
                            $titleClass = 'text-slate-700';
                            $iconName = 'radio_button_unchecked';

                            if ($timelineState === 'completed') {
                                $dotClass = 'border-green-700 bg-green-700 text-white';
                                $lineClass = 'bg-green-600';
                                $titleClass = 'text-green-800';
                                $iconName = 'check';
                            } elseif ($timelineState === 'current') {
                                $dotClass = 'border-green-700 bg-white text-green-700';
                                $lineClass = 'bg-slate-200';
                                $titleClass = 'text-green-800';
                                $iconName = 'schedule';
                            }
                            ?>
                            <li class="min-w-0 flex-1">
                                <div class="flex items-center">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm <?= $dotClass ?>">
                                        <span class="material-symbols-outlined text-base"><?= $iconName ?></span>
                                    </span>
                                    <?php if (!$isLastItem): ?>
                                        <span class="mx-2 h-1 flex-1 rounded-full <?= $lineClass ?>"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-3 pr-2 text-sm font-semibold leading-5 <?= $titleClass ?>"><?= htmlspecialchars((string)($timelineItem['title'] ?? 'Application Step'), ENT_QUOTES, 'UTF-8') ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- LATEST UPDATES -->
<section class="mb-8">
    <aside class="rounded-xl border bg-white">
        <header class="flex flex-col gap-2 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <h3 class="text-lg font-semibold text-gray-800">Latest Updates</h3>
            <a href="notifications.php" class="text-sm text-green-700 hover:underline">View inbox</a>
        </header>

        <?php if (empty($dashboardRecentNotifications)): ?>
            <div class="p-6 text-sm text-gray-600">No recent updates yet.</div>
        <?php else: ?>
            <div class="divide-y">
                <?php foreach ($dashboardRecentNotifications as $index => $item): ?>
                    <article class="p-5 <?= $index === 0 ? 'bg-green-50' : '' ?>">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined mt-0.5 text-green-700">assignment</span>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars((string)$item['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <span class="text-xs text-gray-500"><?= !empty($item['created_at']) ? htmlspecialchars(date('M j, Y', strtotime((string)$item['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars((string)$item['body'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($item['link_url'])): ?>
                                    <a href="<?= htmlspecialchars((string)$item['link_url'], ENT_QUOTES, 'UTF-8') ?>" class="mt-2 inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                                        Open update
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>

<!-- ACTIONS + UPDATES -->
<section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h3 class="font-semibold text-gray-800">Quick Actions</h3>
        </header>

        <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
            <a href="job-list.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">list_alt</span>
                </div>
                <p class="font-medium text-gray-800">Browse Jobs</p>
                <p class="mt-1 text-sm text-gray-500">See available vacancies</p>
            </a>

            <a href="apply.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">edit_document</span>
                </div>
                <p class="font-medium text-gray-800">Submit Application</p>
                <p class="mt-1 text-sm text-gray-500">Apply for a new position</p>
            </a>

            <a href="applications.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">folder_shared</span>
                </div>
                <p class="font-medium text-gray-800">Track Application</p>
                <p class="mt-1 text-sm text-gray-500">View status and milestones</p>
            </a>

            <a href="notifications.php" class="group rounded-xl border bg-white p-4 transition hover:border-green-600 hover:bg-green-50/40">
                <div class="mb-2 inline-flex rounded-lg bg-green-50 p-2 text-green-700">
                    <span class="material-symbols-outlined">notifications</span>
                </div>
                <p class="font-medium text-gray-800">Open Notifications</p>
                <p class="mt-1 text-sm text-gray-500">Read important updates</p>
            </a>
        </div>
    </div>

    <div class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h3 class="font-semibold text-gray-800">Application Help</h3>
        </header>
        <div class="space-y-3 p-6 text-sm text-gray-600">
            <p>Need help improving your profile or attachments before your next application?</p>
            <div class="flex flex-wrap gap-2">
                <a href="profile.php?edit=true" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="material-symbols-outlined text-sm">person</span>
                    Update Profile
                </a>
                <a href="support.php" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <span class="material-symbols-outlined text-sm">support_agent</span>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
