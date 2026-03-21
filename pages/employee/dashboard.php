<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';

$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/dashboard/index.js';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$dashboardPartial = (string)($_GET['partial'] ?? '');
if (in_array($dashboardPartial, ['dashboard-summary', 'dashboard-secondary'], true)) {
  $dashboardDataStage = $dashboardPartial === 'dashboard-summary' ? 'summary' : 'secondary';
  $dashboardContentSection = $dashboardDataStage;
  require_once __DIR__ . '/includes/dashboard/data.php';
  header('Content-Type: text/html; charset=UTF-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  require __DIR__ . '/includes/dashboard/content.php';
  exit;
}

$dashboardDataStage = 'summary';
$dashboardContentSection = 'summary';
require_once __DIR__ . '/includes/dashboard/data.php';

ob_start();
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Dashboard</h1>
  <p class="text-sm text-gray-500">Overview of your employment records and activities</p>
</div>

<?php if ($message !== null && $message !== ''): ?>
  <?php $alertClass = $state === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>" aria-live="polite">
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<section
  id="employeeDashboardAsyncRegion"
  data-dashboard-summary-url="dashboard.php?partial=dashboard-summary"
  data-dashboard-secondary-url="dashboard.php?partial=dashboard-secondary"
  aria-busy="true"
>
  <div id="employeeDashboardSummarySkeleton" class="hidden space-y-6" aria-live="polite" role="status">
    <div class="rounded-xl border border-green-100 bg-green-50/70 px-4 py-3">
      <div class="h-4 w-72 max-w-full animate-pulse rounded bg-green-200/80"></div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <?php for ($index = 0; $index < 4; $index += 1): ?>
        <div class="rounded-xl bg-white p-5 shadow">
          <div class="h-4 w-24 animate-pulse rounded bg-gray-200"></div>
          <div class="mt-4 h-8 w-28 animate-pulse rounded bg-gray-200"></div>
          <div class="mt-3 h-3 w-full animate-pulse rounded bg-gray-200"></div>
          <div class="mt-2 h-3 w-3/4 animate-pulse rounded bg-gray-200"></div>
        </div>
      <?php endfor; ?>
    </div>

  </div>

  <div id="employeeDashboardSummaryError" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" aria-live="polite">
    <p class="font-medium">Dashboard summary could not be loaded.</p>
    <p class="mt-1 text-red-700">Please retry. If this keeps happening, reload the page.</p>
    <button type="button" id="employeeDashboardSummaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
      Retry dashboard summary
    </button>
  </div>

  <div id="employeeDashboardSummaryContent" data-summary-server-rendered="true">
    <?php require __DIR__ . '/includes/dashboard/content.php'; ?>
  </div>

  <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
    <div class="order-2 lg:order-1 lg:col-span-2">
      <div id="employeeDashboardSecondaryTopSkeleton" class="hidden" aria-live="polite" role="status">
        <div class="rounded-xl bg-white p-6 shadow">
          <div class="mb-4 h-5 w-40 animate-pulse rounded bg-gray-200"></div>
          <?php for ($index = 0; $index < 4; $index += 1): ?>
            <div class="mb-4 border-l-4 border-gray-200 pl-4">
              <div class="h-4 w-2/3 animate-pulse rounded bg-gray-200"></div>
              <div class="mt-2 h-3 w-32 animate-pulse rounded bg-gray-200"></div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <div id="employeeDashboardSecondaryError" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" aria-live="polite">
        <p class="font-medium">Secondary dashboard widgets could not be loaded.</p>
        <p class="mt-1 text-red-700">Summary cards are available. Retry to load announcements, requests, trainings, and activity.</p>
        <button type="button" id="employeeDashboardSecondaryRetry" class="mt-3 inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
          Retry secondary widgets
        </button>
      </div>

      <div id="employeeDashboardSecondaryTopContent" class="hidden"></div>
    </div>

    <section class="order-1 lg:order-2 rounded-xl bg-white p-6 shadow">
      <h2 class="font-semibold mb-4">Quick Actions</h2>

      <div class="grid grid-cols-1 gap-3 text-sm">
        <a href="timekeeping.php#leave-balance" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
          <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">visibility</span><?= !empty($employeeEmploymentStatus) && timekeepingIsCosEmploymentStatus($employeeEmploymentStatus) ? 'View CTO Tracking' : 'View Leave and CTO Points' ?></span>
          <span class="material-icons text-gray-400 text-base">arrow_forward</span>
        </a>
        <a href="document-management.php?quick_action=upload-document" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
          <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">upload_file</span>Upload Document</span>
          <span class="material-icons text-gray-400 text-base">arrow_forward</span>
        </a>
        <a href="personal-reports.php#available-downloads" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
          <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">description</span>Download Reports</span>
          <span class="material-icons text-gray-400 text-base">arrow_forward</span>
        </a>
      </div>
    </section>
  </div>

  <div class="mt-6">
    <div id="employeeDashboardSecondaryBottomSkeleton" class="hidden" aria-live="polite" role="status">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <?php for ($column = 0; $column < 3; $column += 1): ?>
          <div class="rounded-xl bg-white p-6 shadow">
            <div class="mb-4 h-5 w-36 animate-pulse rounded bg-gray-200"></div>
            <?php for ($row = 0; $row < 4; $row += 1): ?>
              <div class="mb-3 h-10 animate-pulse rounded bg-gray-200"></div>
            <?php endfor; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <div id="employeeDashboardSecondaryBottomContent" class="hidden grid grid-cols-3 gap-2"></div>
  </div>

  <noscript>
    <div class="mt-4 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
      JavaScript is required to load this optimized dashboard view.
    </div>
  </noscript>
</section>

<?php
$content = ob_get_clean();
include './includes/layout.php';
