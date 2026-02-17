<?php
require_once __DIR__ . '/includes/learning-and-development/bootstrap.php';
require_once __DIR__ . '/includes/learning-and-development/actions.php';
require_once __DIR__ . '/includes/learning-and-development/data.php';

$pageTitle = 'Learning and Development | DA HRIS';
$activePage = 'learning-and-development.php';
$breadcrumbs = ['Learning and Development'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/learning-and-development/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Learning and Development</h1>
  <p class="text-sm text-gray-500">View available training programs, enroll, and track your completion records.</p>
</div>

<?php if (!empty($message)): ?>
  <?php $alertClass = ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $escape($alertClass) ?>" aria-live="polite">
    <?= $escape($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" aria-live="polite">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 text-sm">
  <article class="bg-white border rounded-lg p-4">
    <p class="text-gray-500">Available Trainings</p>
    <p class="text-2xl font-semibold mt-1"><?= (int)($learningSummary['available_count'] ?? 0) ?></p>
  </article>
  <article class="bg-white border rounded-lg p-4">
    <p class="text-gray-500">My Enrollments</p>
    <p class="text-2xl font-semibold mt-1"><?= (int)($learningSummary['enrolled_count'] ?? 0) ?></p>
  </article>
  <article class="bg-white border rounded-lg p-4">
    <p class="text-gray-500">Completed Trainings</p>
    <p class="text-2xl font-semibold mt-1"><?= (int)($learningSummary['completed_count'] ?? 0) ?></p>
  </article>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
  <section class="bg-white border rounded-lg p-5">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div>
        <h2 class="text-lg font-semibold">Upcoming Training Notifications</h2>
        <p class="text-sm text-gray-500">Your next scheduled trainings.</p>
      </div>
      <a href="notifications.php" class="text-sm text-blue-600 hover:underline">View Notifications</a>
    </div>

    <ul class="space-y-3 text-sm">
      <?php if (empty($upcomingTrainingAlerts)): ?>
        <li class="rounded-lg border border-dashed border-gray-300 px-4 py-3 text-gray-500">No upcoming training schedules.</li>
      <?php else: ?>
        <?php foreach ($upcomingTrainingAlerts as $alert): ?>
          <li class="rounded-lg border px-4 py-3">
            <p class="font-medium"><?= $escape($alert['title'] ?? 'Training') ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= $escape($alert['meta'] ?? '-') ?></p>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </section>

  <section class="bg-white border rounded-lg p-5">
    <div class="mb-4">
      <h2 class="text-lg font-semibold">Certificates</h2>
      <p class="text-sm text-gray-500">Certificate links for attended trainings.</p>
    </div>

    <ul class="space-y-3 text-sm">
      <?php if (empty($certificateAlerts)): ?>
        <li class="rounded-lg border border-dashed border-gray-300 px-4 py-3 text-gray-500">No certificates available yet.</li>
      <?php else: ?>
        <?php foreach ($certificateAlerts as $certificate): ?>
          <li class="rounded-lg border px-4 py-3 flex items-center justify-between gap-3">
            <div>
              <p class="font-medium"><?= $escape($certificate['title'] ?? 'Training') ?></p>
              <p class="text-xs text-gray-500 mt-1"><?= $escape($certificate['meta'] ?? '-') ?></p>
            </div>
            <?php if (!empty($certificate['certificate_url'])): ?>
              <a href="<?= $escape((string)$certificate['certificate_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline">View</a>
            <?php else: ?>
              <span class="text-xs text-gray-400">Pending</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </section>
</div>

<section class="mb-8">
  <div class="flex flex-wrap gap-3 items-end mb-3">
    <div class="flex-1 min-w-[220px]">
      <label class="block text-sm text-gray-600 mb-1">Search Available Trainings</label>
      <input id="lndAvailableSearch" type="search" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Search title, type, category, provider">
    </div>
    <div class="w-full sm:w-52">
      <label class="block text-sm text-gray-600 mb-1">Program Status</label>
      <select id="lndAvailableStatus" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">All</option>
        <option value="planned">Planned</option>
        <option value="open">Open</option>
        <option value="ongoing">Ongoing</option>
      </select>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Title</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-left">Category</th>
          <th class="px-4 py-3 text-left">Date</th>
          <th class="px-4 py-3 text-left">Provider</th>
          <th class="px-4 py-3 text-left">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($availableTrainingRows)): ?>
          <tr><td colspan="6" class="px-4 py-3 text-gray-500">No available trainings found.</td></tr>
        <?php else: ?>
          <?php foreach ($availableTrainingRows as $training): ?>
            <tr data-lnd-available-row data-search="<?= $escape((string)($training['search_text'] ?? '')) ?>" data-status="<?= $escape((string)($training['status_raw'] ?? '')) ?>">
              <td class="px-4 py-3"><?= $escape((string)($training['title'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['training_type'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['training_category'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['date_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['provider'] ?? '-')) ?></td>
              <td class="px-4 py-3">
                <?php if (!empty($training['is_enrolled'])): ?>
                  <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Enrolled</span>
                <?php else: ?>
                  <form method="post" action="learning-and-development.php" class="inline-flex">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                    <input type="hidden" name="action" value="enroll_training">
                    <input type="hidden" name="program_id" value="<?= $escape((string)($training['program_id'] ?? '')) ?>">
                    <button type="submit" class="inline-flex min-w-[96px] justify-center bg-daGreen text-white px-3 py-1.5 rounded text-xs">Enroll</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section>
  <div class="flex flex-wrap gap-3 items-end mb-3">
    <div class="flex-1 min-w-[220px]">
      <label class="block text-sm text-gray-600 mb-1">Search My Trainings</label>
      <input id="lndTakenSearch" type="search" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Search title, status, attendance">
    </div>
    <div class="w-full sm:w-52">
      <label class="block text-sm text-gray-600 mb-1">Enrollment Status</label>
      <select id="lndTakenStatus" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">All</option>
        <option value="enrolled">Enrolled</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
        <option value="dropped">Dropped</option>
      </select>
    </div>
  </div>

  <div class="bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-4 py-3 text-left">Training Title</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-left">Category</th>
          <th class="px-4 py-3 text-left">Date</th>
          <th class="px-4 py-3 text-left">Provider</th>
          <th class="px-4 py-3 text-left">Location Status</th>
          <th class="px-4 py-3 text-left">Enrollment</th>
          <th class="px-4 py-3 text-left">Certificate</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php if (empty($takenTrainingRows)): ?>
          <tr><td colspan="8" class="px-4 py-3 text-gray-500">No enrolled trainings yet.</td></tr>
        <?php else: ?>
          <?php foreach ($takenTrainingRows as $training): ?>
            <tr data-lnd-taken-row data-search="<?= $escape((string)($training['search_text'] ?? '')) ?>" data-status="<?= $escape((string)($training['enrollment_status_raw'] ?? '')) ?>">
              <td class="px-4 py-3"><?= $escape((string)($training['title'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['training_type'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['training_category'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['date_label'] ?? '-')) ?></td>
              <td class="px-4 py-3"><?= $escape((string)($training['provider'] ?? '-')) ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($training['attendance_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
                  <?= $escape((string)($training['attendance_label'] ?? 'Pending')) ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($training['enrollment_status_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
                  <?= $escape((string)($training['enrollment_status_label'] ?? 'Enrolled')) ?>
                </span>
              </td>
              <td class="px-4 py-3">
                <?php if (!empty($training['certificate_url'])): ?>
                  <a href="<?= $escape((string)$training['certificate_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline">View Certificate</a>
                <?php else: ?>
                  <span class="text-xs text-gray-400">Not available</span>
                <?php endif; ?>
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
include './includes/layout.php';
