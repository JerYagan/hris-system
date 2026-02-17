<?php
require_once __DIR__ . '/includes/dashboard/bootstrap.php';
require_once __DIR__ . '/includes/dashboard/actions.php';
require_once __DIR__ . '/includes/dashboard/data.php';

$pageTitle = 'Dashboard | DA HRIS';
$activePage = 'dashboard.php';
$breadcrumbs = ['Dashboard'];

ob_start();

$escape = static function (mixed $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDateTime = static function (?string $dateTime): string {
  return formatDateTimeForPhilippines($dateTime, 'M j, Y · g:i A');
};
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Dashboard</h1>
  <p class="text-sm text-gray-500">Overview of your employment records and activities</p>
  <p class="text-sm text-daGreen mt-1 font-medium"><?= $escape((string)($dashboardWelcomeMessage ?? 'Welcome back!')) ?></p>
</div>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<!-- ================= SUMMARY CARDS ================= -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Attendance Today</p>

    <p class="text-2xl font-bold mt-1 <?= $escape($dashboardSummary['attendance_status_class'] ?? 'text-gray-700') ?>">
      <?= $escape($dashboardSummary['attendance_label'] ?? 'No log today') ?>
    </p>

    <p class="text-xs text-gray-500 mt-1">
      <?= $escape($dashboardSummary['attendance_detail'] ?? 'No attendance entry recorded yet.') ?>
    </p>

    <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full
               <?= $escape($dashboardSummary['attendance_badge_class'] ?? 'bg-gray-100 text-gray-700') ?>">
      <?= $escape($dashboardSummary['attendance_badge'] ?? 'No Attendance') ?>
    </span>
  </div>


  <!-- DOCUMENTS -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Pending Documents</p>
    <p class="text-2xl font-bold mt-1"><?= (int)($dashboardSummary['pending_documents_count'] ?? 0) ?></p>
    <p class="text-xs text-gray-400 mt-1"><?= $escape($dashboardSummary['pending_documents_detail'] ?? 'Awaiting HR approval') ?></p>
  </div>

  <!-- SUPPORT -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">Open Requests</p>
    <p class="text-2xl font-bold text-yellow-600 mt-1"><?= (int)($dashboardSummary['open_requests_count'] ?? 0) ?></p>
    <p class="text-xs text-gray-400 mt-1"><?= $escape($dashboardSummary['open_requests_detail'] ?? 'No pending requests') ?></p>
  </div>

  <!-- PRAISE -->
  <div class="bg-white rounded-xl shadow p-5">
    <p class="text-sm text-gray-500">PRAISE Status</p>
    <p class="text-2xl font-bold mt-1 <?= $escape($dashboardSummary['praise_status_class'] ?? 'text-gray-700') ?>"><?= $escape($dashboardSummary['praise_status'] ?? 'No active evaluation') ?></p>
    <p class="text-xs text-gray-400 mt-1"><?= $escape($dashboardSummary['praise_detail'] ?? 'No submitted cycle yet') ?></p>
  </div>

</div>

<!-- ================= MAIN GRID ================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- ANNOUNCEMENTS -->
  <section class="bg-white rounded-xl shadow p-6 lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold">Recent Announcements</h2>
      <a href="notifications.php" class="text-sm text-blue-600 hover:underline">
        View all
      </a>
    </div>

    <ul class="space-y-4 text-sm">
      <?php if (!empty($dashboardAnnouncements)): ?>
        <?php foreach ($dashboardAnnouncements as $announcement): ?>
          <?php
            $category = strtolower((string)($announcement['category'] ?? 'system'));
            $borderClass = match ($category) {
              'announcement' => 'border-green-500',
              'policy' => 'border-blue-500',
              'alert', 'system' => 'border-red-500',
              default => 'border-gray-400',
            };
          ?>
          <li class="border-l-4 pl-4 <?= $escape($borderClass) ?>">
            <p class="font-medium"><?= $escape($announcement['title'] ?? 'Update') ?></p>
            <p class="text-xs text-gray-500"><?= $escape($formatDateTime($announcement['created_at'] ?? null)) ?></p>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="border-l-4 border-gray-300 pl-4">
          <p class="font-medium">No announcements yet</p>
          <p class="text-xs text-gray-500">You’re all caught up.</p>
        </li>
      <?php endif; ?>
    </ul>
  </section>

  <!-- QUICK ACTIONS -->
  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">Quick Actions</h2>

    <div class="grid grid-cols-1 gap-3 text-sm">
      <a href="timekeeping.php?quick_action=create-leave" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
        <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">event_available</span>Create Leave Request</span>
        <span class="material-icons text-gray-400 text-base">arrow_forward</span>
      </a>
      <a href="document-management.php?quick_action=upload-document" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
        <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">upload_file</span>Upload Document</span>
        <span class="material-icons text-gray-400 text-base">arrow_forward</span>
      </a>
      <a href="personal-reports.php?quick_action=generate-report" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
        <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">description</span>Generate Report</span>
        <span class="material-icons text-gray-400 text-base">arrow_forward</span>
      </a>
      <a href="praise.php?quick_action=submit-self-evaluation" class="inline-flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50">
        <span class="inline-flex items-center gap-2"><span class="material-icons text-daGreen text-base">fact_check</span>Submit Self-Evaluation</span>
        <span class="material-icons text-gray-400 text-base">arrow_forward</span>
      </a>
    </div>
  </section>

</div>

<!-- ================= REQUESTS + ACTIVITY + TRAININGS ================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

  <section class="bg-white rounded-xl shadow p-6">
    <div class="flex items-center justify-between mb-4 gap-2">
      <h2 class="font-semibold">Upcoming Trainings</h2>
      <a href="learning-and-development.php" class="text-sm text-blue-600 hover:underline">View all</a>
    </div>

    <ul class="space-y-3 text-sm">
      <?php if (!empty($dashboardUpcomingTrainings)): ?>
        <?php foreach ($dashboardUpcomingTrainings as $training): ?>
          <li class="flex justify-between items-start gap-3">
            <div>
              <a href="learning-and-development.php" class="hover:underline font-medium">
                <?= $escape($training['title'] ?? 'Training Program') ?>
              </a>
              <p class="text-xs text-gray-500"><?= $escape(($training['date_label'] ?? '-') . ' · ' . ($training['provider'] ?? '-')) ?></p>
            </div>
            <span class="inline-flex min-w-[82px] justify-center px-2 py-1 text-xs rounded-full <?= $escape($training['status_class'] ?? 'bg-gray-100 text-gray-700') ?>">
              <?= $escape($training['status_label'] ?? 'Enrolled') ?>
            </span>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="flex justify-between items-center">
          <span>No upcoming trainings</span>
          <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">None</span>
        </li>
      <?php endif; ?>
    </ul>
  </section>

  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">Open Requests</h2>

    <ul class="space-y-3 text-sm">
      <?php if (!empty($dashboardOpenRequests)): ?>
        <?php foreach ($dashboardOpenRequests as $request): ?>
          <li class="flex justify-between items-center gap-3">
            <div>
              <a href="<?= $escape($request['link'] ?? 'timekeeping.php') ?>" class="hover:underline">
                <?= $escape($request['title'] ?? 'Request') ?>
              </a>
              <p class="text-xs text-gray-500"><?= $escape($request['meta'] ?? '-') ?></p>
            </div>
            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
              <?= $escape($request['status'] ?? 'Pending') ?>
            </span>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="flex justify-between items-center">
          <span>No pending requests</span>
          <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Clear</span>
        </li>
      <?php endif; ?>
    </ul>

    <a href="timekeeping.php" class="inline-block mt-4 text-sm text-blue-600 hover:underline">
      Go to Timekeeping Requests
    </a>
  </section>

  <section class="bg-white rounded-xl shadow p-6">
    <h2 class="font-semibold mb-4">Recent Activity</h2>

    <ul class="text-sm space-y-3">
      <?php if (!empty($dashboardRecentActivity)): ?>
        <?php foreach ($dashboardRecentActivity as $activity): ?>
          <li>
            <p><?= $escape($activity['title'] ?? 'Activity recorded') ?></p>
            <p class="text-xs text-gray-500"><?= $escape($formatDateTime($activity['created_at'] ?? null)) ?></p>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>
          <p>No recent activity yet</p>
          <p class="text-xs text-gray-500">Activity will appear once you perform module actions.</p>
        </li>
      <?php endif; ?>
    </ul>
  </section>

</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
