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

$trainingHistoryPreviewRows = array_slice($trainingHistoryRows ?? [], 0, 3);
$hasMoreTrainingHistory = count($trainingHistoryRows ?? []) > 3;
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Learning and Development</h1>
  <p class="text-sm text-gray-500">View trainings in one workspace and switch between available programs and your enrollments.</p>
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
      <h2 class="text-lg font-semibold">Training History</h2>
      <p class="text-sm text-gray-500">Recent outcomes from your enrolled trainings.</p>
    </div>

    <ul class="space-y-3 text-sm">
      <?php if (empty($trainingHistoryPreviewRows)): ?>
        <li class="rounded-lg border border-dashed border-gray-300 px-4 py-3 text-gray-500">No training history available yet.</li>
      <?php else: ?>
        <?php foreach ($trainingHistoryPreviewRows as $history): ?>
          <li class="rounded-lg border px-4 py-3 flex items-center justify-between gap-3">
            <div>
              <p class="font-medium"><?= $escape($history['title'] ?? 'Training') ?></p>
              <p class="text-xs text-gray-500 mt-1"><?= $escape(($history['date_label'] ?? '-') . ' · ' . ($history['provider'] ?? '-')) ?></p>
            </div>
            <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($history['status_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
              <?= $escape((string)($history['status_label'] ?? 'Enrolled')) ?>
            </span>
          </li>
        <?php endforeach; ?>
        <?php if ($hasMoreTrainingHistory): ?>
          <li>
            <button id="openTrainingHistoryModal" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100">Show More</button>
          </li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </section>
</div>

<section class="mb-8 bg-white border rounded-2xl p-4 md:p-6">
  <div class="mb-5">
    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1" role="tablist" aria-label="Training views">
      <button type="button" id="lndViewAvailableTab" data-lnd-tab="available" class="px-3 py-1.5 rounded-md text-sm font-medium bg-white text-gray-900 shadow-sm" aria-selected="true">Available Trainings</button>
      <button type="button" id="lndViewEnrolledTab" data-lnd-tab="enrolled" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900" aria-selected="false">My Enrollments</button>
    </div>
  </div>

<section id="lndTabAvailablePanel" data-lnd-tab-panel="available" class="mb-2">
  <div class="flex flex-wrap gap-3 items-end mb-4">
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

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="lndAvailableCardsContainer">
    <?php if (empty($availableTrainingRows)): ?>
      <article class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-sm text-gray-500">No active trainings are available right now.</article>
    <?php else: ?>
      <?php foreach ($availableTrainingRows as $training): ?>
        <article
          data-lnd-available-card
          data-lnd-training-card
          data-search="<?= $escape((string)($training['search_text'] ?? '')) ?>"
          data-status="<?= $escape((string)($training['status_raw'] ?? '')) ?>"
          data-title="<?= $escape((string)($training['title'] ?? '-')) ?>"
          data-provider="<?= $escape((string)($training['provider'] ?? '-')) ?>"
          data-type="<?= $escape((string)($training['training_type'] ?? '-')) ?>"
          data-category="<?= $escape((string)($training['training_category'] ?? '-')) ?>"
          data-schedule="<?= $escape((string)($training['date_label'] ?? '-')) ?>"
          data-program-status="<?= $escape((string)($training['status_raw'] ?? '-')) ?>"
          data-enrollment-status="<?= $escape((string)($training['enrollment_status_label'] ?? 'Not Yet Enrolled')) ?>"
          data-attendance="<?= $escape((string)($training['attendance_label'] ?? 'Pending')) ?>"
          tabindex="0"
          role="button"
          aria-label="View training details"
          class="bg-gray-50 border border-gray-200 rounded-xl p-5 shadow-sm cursor-pointer transition duration-200 hover:border-blue-200 hover:shadow-md hover:-translate-y-0.5"
        >
          <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-900 leading-snug"><?= $escape((string)($training['title'] ?? '-')) ?></h3>
            <span class="inline-flex min-w-[112px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($training['enrollment_status_class'] ?? 'bg-slate-100 text-slate-700')) ?>">
              <?= $escape((string)($training['enrollment_status_label'] ?? 'Not Yet Enrolled')) ?>
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1"><?= $escape((string)($training['provider'] ?? '-')) ?></p>

          <dl class="mt-4 space-y-2 text-xs text-gray-600">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Type</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['training_type'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Category</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['training_category'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Schedule</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['date_label'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Program Status</dt>
              <dd class="font-medium text-gray-700 text-right capitalize"><?= $escape((string)($training['status_raw'] ?? '-')) ?></dd>
            </div>
          </dl>

          <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
            <span class="text-xs text-gray-500"><?= $escape((string)($training['cta_message'] ?? 'Enrollment is managed by Admin and HR staff.')) ?></span>
            <button type="button" data-lnd-view-details class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100">View Details</button>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="lndAvailableFilterEmpty" class="hidden bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-sm text-gray-500">No available trainings match your search or filter criteria.</div>

  <div class="px-1 pt-4 flex items-center justify-between gap-3 text-xs text-gray-600">
    <p id="lndAvailablePageInfo">Page 1 of 1</p>
    <div class="flex items-center gap-2">
      <button id="lndAvailablePrevPage" type="button" class="px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
      <button id="lndAvailableNextPage" type="button" class="px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
    </div>
  </div>
</section>

<section id="lndTabEnrolledPanel" data-lnd-tab-panel="enrolled" class="hidden">
  <div class="flex flex-wrap gap-3 items-end mb-4">
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

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="lndTakenCardsContainer">
    <?php if (empty($takenTrainingRows)): ?>
      <article class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-sm text-gray-500">No enrolled trainings yet.</article>
    <?php else: ?>
      <?php foreach ($takenTrainingRows as $training): ?>
        <article
          data-lnd-taken-card
          data-lnd-training-card
          data-search="<?= $escape((string)($training['search_text'] ?? '')) ?>"
          data-status="<?= $escape((string)($training['enrollment_status_raw'] ?? '')) ?>"
          data-title="<?= $escape((string)($training['title'] ?? '-')) ?>"
          data-provider="<?= $escape((string)($training['provider'] ?? '-')) ?>"
          data-type="<?= $escape((string)($training['training_type'] ?? '-')) ?>"
          data-category="<?= $escape((string)($training['training_category'] ?? '-')) ?>"
          data-schedule="<?= $escape((string)($training['date_label'] ?? '-')) ?>"
          data-program-status="<?= $escape((string)($training['enrollment_status_label'] ?? 'Enrolled')) ?>"
          data-enrollment-status="<?= $escape((string)($training['enrollment_status_label'] ?? 'Enrolled')) ?>"
          data-attendance="<?= $escape((string)($training['attendance_label'] ?? 'Pending')) ?>"
          tabindex="0"
          role="button"
          aria-label="View training details"
          class="bg-gray-50 border border-gray-200 rounded-xl p-5 shadow-sm cursor-pointer transition duration-200 hover:border-blue-200 hover:shadow-md hover:-translate-y-0.5"
        >
          <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-900 leading-snug"><?= $escape((string)($training['title'] ?? '-')) ?></h3>
            <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($training['enrollment_status_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
              <?= $escape((string)($training['enrollment_status_label'] ?? 'Enrolled')) ?>
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1"><?= $escape((string)($training['provider'] ?? '-')) ?></p>

          <dl class="mt-4 space-y-2 text-xs text-gray-600">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Type</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['training_type'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Category</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['training_category'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Schedule</dt>
              <dd class="font-medium text-gray-700 text-right"><?= $escape((string)($training['date_label'] ?? '-')) ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500">Attendance</dt>
              <dd>
                <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($training['attendance_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
                  <?= $escape((string)($training['attendance_label'] ?? 'Pending')) ?>
                </span>
              </dd>
            </div>
          </dl>

          <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
            <span class="text-xs text-gray-500">Track your attendance and completion status.</span>
            <button type="button" data-lnd-view-details class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100">View Details</button>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="lndTakenFilterEmpty" class="hidden bg-gray-50 border border-dashed border-gray-300 rounded-xl p-5 text-sm text-gray-500">No enrolled trainings match your search/filter criteria.</div>

  <div class="px-1 pt-4 flex items-center justify-between gap-3 text-xs text-gray-600">
    <p id="lndTakenPageInfo">Page 1 of 1</p>
    <div class="flex items-center gap-2">
      <button id="lndTakenPrevPage" type="button" class="px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
      <button id="lndTakenNextPage" type="button" class="px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
    </div>
  </div>
</section>
</section>

<div id="trainingHistoryModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-gray-900/60" data-training-history-modal-close></div>
  <div class="relative min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Training History</h3>
          <p class="text-xs text-gray-500 mt-0.5">Full record of your completed and enrolled trainings.</p>
        </div>
        <button type="button" data-training-history-modal-close class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="p-5 text-sm max-h-[70vh] overflow-y-auto">
        <ul class="space-y-3">
          <?php if (empty($trainingHistoryRows)): ?>
            <li class="rounded-lg border border-dashed border-gray-300 px-4 py-3 text-gray-500">No training history available yet.</li>
          <?php else: ?>
            <?php foreach ($trainingHistoryRows as $history): ?>
              <li class="rounded-lg border px-4 py-3 flex items-center justify-between gap-3">
                <div>
                  <p class="font-medium"><?= $escape($history['title'] ?? 'Training') ?></p>
                  <p class="text-xs text-gray-500 mt-1"><?= $escape(($history['date_label'] ?? '-') . ' · ' . ($history['provider'] ?? '-')) ?></p>
                </div>
                <span class="inline-flex min-w-[96px] justify-center px-2 py-1 text-xs rounded-full <?= $escape((string)($history['status_class'] ?? 'bg-gray-100 text-gray-700')) ?>">
                  <?= $escape((string)($history['status_label'] ?? 'Enrolled')) ?>
                </span>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>

      <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
        <button type="button" data-training-history-modal-close class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="lndTrainingDetailsModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-gray-900/60" data-lnd-modal-close></div>
  <div class="relative min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-xl bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Training Details</h3>
          <p class="text-xs text-gray-500 mt-0.5">Review complete training information.</p>
        </div>
        <button type="button" data-lnd-modal-close class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="p-5 text-sm space-y-4">
        <div>
          <p id="lndDetailsTitle" class="text-base font-semibold text-gray-900">-</p>
          <p id="lndDetailsProvider" class="text-xs text-gray-500 mt-1">-</p>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Type</dt>
            <dd id="lndDetailsType" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Category</dt>
            <dd id="lndDetailsCategory" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Schedule</dt>
            <dd id="lndDetailsSchedule" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Program Status</dt>
            <dd id="lndDetailsProgramStatus" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Enrollment</dt>
            <dd id="lndDetailsEnrollment" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <dt class="text-gray-500">Attendance</dt>
            <dd id="lndDetailsAttendance" class="text-gray-800 font-medium mt-1">-</dd>
          </div>
        </dl>
      </div>

      <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
        <button type="button" data-lnd-modal-close class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
