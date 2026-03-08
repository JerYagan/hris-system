<?php
$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDateTime = static function (?string $dateTime): string {
    $formatted = formatDateTimeForPhilippines($dateTime, 'M j, Y · g:i A');
    if ($formatted === '-') {
        return $formatted;
    }

    return $formatted . ' PST';
};

$dashboardContentSection = (string)($dashboardContentSection ?? 'full');
$renderSummarySection = in_array($dashboardContentSection, ['full', 'summary'], true);
$renderSecondarySection = in_array($dashboardContentSection, ['full', 'secondary'], true);
?>

<?php if ($renderSummarySection): ?>
  <div class="mb-6 rounded-xl border border-green-100 bg-green-50/70 px-4 py-3">
    <p class="text-sm font-medium text-daGreen"><?= $escape((string)($dashboardWelcomeMessage ?? 'Welcome back!')) ?></p>
  </div>

  <?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      <?= $escape($dataLoadError) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
    <div class="bg-white rounded-xl shadow p-5">
      <p class="text-sm text-gray-500">Attendance Today</p>
      <p class="text-2xl font-bold mt-1 <?= $escape($dashboardSummary['attendance_status_class'] ?? 'text-gray-700') ?>">
        <?= $escape($dashboardSummary['attendance_label'] ?? 'No log today') ?>
      </p>
      <p class="text-xs text-gray-500 mt-1">
        <?= $escape($dashboardSummary['attendance_detail'] ?? 'No attendance entry recorded yet.') ?>
      </p>
      <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full <?= $escape($dashboardSummary['attendance_badge_class'] ?? 'bg-gray-100 text-gray-700') ?>">
        <?= $escape($dashboardSummary['attendance_badge'] ?? 'No Attendance') ?>
      </span>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
      <p class="text-sm text-gray-500">Pending Documents</p>
      <p class="text-2xl font-bold mt-1"><?= (int)($dashboardSummary['pending_documents_count'] ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= $escape($dashboardSummary['pending_documents_detail'] ?? 'Awaiting HR approval') ?></p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
      <p class="text-sm text-gray-500">Open Requests</p>
      <p class="text-2xl font-bold text-yellow-600 mt-1"><?= (int)($dashboardSummary['open_requests_count'] ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= $escape($dashboardSummary['open_requests_detail'] ?? 'No pending requests') ?></p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
      <p class="text-sm text-gray-500">Accumulated Leave/CTO Points</p>
      <div class="mt-3 grid grid-cols-3 gap-3 text-center">
        <div class="rounded-lg bg-sky-50 px-3 py-3">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">SL</p>
          <p class="mt-1 text-lg font-bold text-sky-800"><?= $escape(number_format((float)($dashboardSummary['leave_points']['sl'] ?? 0), 2)) ?></p>
        </div>
        <div class="rounded-lg bg-emerald-50 px-3 py-3">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">VL</p>
          <p class="mt-1 text-lg font-bold text-emerald-800"><?= $escape(number_format((float)($dashboardSummary['leave_points']['vl'] ?? 0), 2)) ?></p>
        </div>
        <div class="rounded-lg bg-amber-50 px-3 py-3">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">CTO</p>
          <p class="mt-1 text-lg font-bold text-amber-800"><?= $escape(number_format((float)($dashboardSummary['leave_points']['cto'] ?? 0), 2)) ?></p>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-3">Visual summary of accumulated admin-posted SL, VL, and CTO points. Logged leave entries do not reduce these totals.</p>
    </div>
  </div>
<?php endif; ?>

<?php if ($renderSecondarySection): ?>
  <div data-dashboard-secondary-top>
    <section class="bg-white rounded-xl shadow p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Recent Announcements</h2>
        <a href="notifications.php" class="text-sm text-blue-600 hover:underline">View all</a>
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
  </div>

  <div data-dashboard-secondary-bottom class="grid grid-cols-1 gap-6 lg:grid-cols-3">
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

    <a href="timekeeping.php" class="inline-block mt-4 text-sm text-blue-600 hover:underline">Go to Timekeeping Requests</a>
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
<?php endif; ?>