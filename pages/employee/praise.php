<?php
/**
 * Performance Management (PRAISE)
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/praise/bootstrap.php';
require_once __DIR__ . '/includes/praise/actions.php';
require_once __DIR__ . '/includes/praise/data.php';

$pageTitle = 'PRAISE | DA HRIS';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/praise/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y', $ts);
};

$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'approved', 'completed', 'open' => [ucwords(str_replace('_', ' ', $key)), 'bg-approved text-green-800'],
        'pending', 'submitted', 'ongoing' => [ucwords(str_replace('_', ' ', $key)), 'bg-pending text-yellow-800'],
        'rejected', 'failed', 'cancelled' => [ucwords(str_replace('_', ' ', $key)), 'bg-rejected text-red-800'],
        default => [ucwords(str_replace('_', ' ', ($key !== '' ? $key : 'draft'))), 'bg-gray-200 text-gray-700'],
    };
};
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Performance Management (PRAISE)</h1>
  <p class="text-sm text-gray-500">Employee self-view for nominations, evaluations, and training completion snapshot.</p>
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

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Employee</p>
    <p class="text-sm font-semibold mt-1"><?= $escape($employeeDisplayName) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Total Evaluations</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($praiseSummary['total_evaluations'] ?? 0)) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Latest Rating</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($praiseSummary['latest_rating'] ?? '-')) ?></p>
  </div>
  <div class="bg-white rounded-xl shadow p-4 border">
    <p class="text-xs text-gray-500">Approved Nominations</p>
    <p class="text-2xl font-bold mt-1"><?= $escape((string)($praiseSummary['approved_nominations'] ?? 0)) ?></p>
  </div>
</div>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
    <div>
      <h2 class="text-lg font-bold">Self <span class="text-daGreen">Evaluation</span></h2>
      <p class="text-sm text-gray-500">Submit your own performance assessment for currently open cycles.</p>
    </div>
  </div>

  <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5" id="selfEvaluationForm">
    <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
    <input type="hidden" name="action" value="submit_self_evaluation">

    <div class="md:col-span-2">
      <label for="selfCycleId" class="block text-xs font-semibold text-gray-600 mb-1">Performance Cycle</label>
      <select id="selfCycleId" name="cycle_id" class="w-full border rounded-lg px-3 py-2 text-sm" required>
        <option value="">Select open cycle</option>
        <?php foreach ($openPerformanceCycles as $cycle): ?>
          <option value="<?= $escape((string)($cycle['id'] ?? '')) ?>">
            <?= $escape((string)($cycle['cycle_name'] ?? 'Performance Cycle')) ?>
            (<?= $escape($formatDate((string)($cycle['period_start'] ?? ''))) ?> - <?= $escape($formatDate((string)($cycle['period_end'] ?? ''))) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="selfFinalRating" class="block text-xs font-semibold text-gray-600 mb-1">Self Rating (1.00–5.00)</label>
      <input id="selfFinalRating" type="number" name="final_rating" class="w-full border rounded-lg px-3 py-2 text-sm" min="1" max="5" step="0.01" placeholder="e.g. 4.25">
    </div>

    <div class="md:col-span-3">
      <label for="selfRemarks" class="block text-xs font-semibold text-gray-600 mb-1">Self Evaluation Comments</label>
      <textarea id="selfRemarks" name="remarks" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm" maxlength="2000" required placeholder="Describe your key accomplishments, impact, and improvement areas."></textarea>
      <div class="mt-1 text-xs text-gray-500" id="selfRemarksCounter">0 / 2000</div>
    </div>

    <div class="md:col-span-3 flex justify-end">
      <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm font-semibold">Submit Self Evaluation</button>
    </div>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Cycle</th>
          <th class="text-left py-3">Period</th>
          <th class="text-left py-3">Your Rating</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Submitted</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employeeSelfEvaluations)): ?>
          <tr><td class="py-3 text-gray-500" colspan="6">No self-evaluation submissions yet.</td></tr>
        <?php else: ?>
          <?php foreach ($employeeSelfEvaluations as $selfEvaluation): ?>
            <?php [$statusLabel, $statusClass] = $statusPill((string)($selfEvaluation['status'] ?? 'submitted')); ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($selfEvaluation['cycle_name'] ?? 'Performance Cycle')) ?></td>
              <td class="py-3"><?= $escape($formatDate($selfEvaluation['period_start'] ?? '')) ?> - <?= $escape($formatDate($selfEvaluation['period_end'] ?? '')) ?></td>
              <td class="py-3 font-medium"><?= $escape((string)($selfEvaluation['final_rating'] ?? '-')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3"><?= $escape($formatDate($selfEvaluation['created_at'] ?? '')) ?></td>
              <td class="py-3">
                <button type="button" data-open-detail data-detail-title="Self Evaluation Comments" data-detail-body="<?= $escape((string)($selfEvaluation['remarks'] ?? 'No comments.')) ?>" class="border px-3 py-1 rounded-lg text-xs">View Comments</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">Supervisor <span class="text-daGreen">Evaluations</span></h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Cycle</th>
          <th class="text-left py-3">Period</th>
          <th class="text-left py-3">Rating</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Evaluator</th>
          <th class="text-left py-3">Feedback</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employeeEvaluations)): ?>
          <tr><td class="py-3 text-gray-500" colspan="7">No evaluation records found.</td></tr>
        <?php else: ?>
          <?php foreach ($employeeEvaluations as $evaluation): ?>
            <?php
              [$statusLabel, $statusClass] = $statusPill((string)($evaluation['status'] ?? 'draft'));
              $remarks = (string)($evaluation['remarks'] ?? '');
            ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($evaluation['cycle_name'] ?? 'Performance Cycle')) ?></td>
              <td class="py-3"><?= $escape($formatDate($evaluation['period_start'] ?? '')) ?> - <?= $escape($formatDate($evaluation['period_end'] ?? '')) ?></td>
              <td class="py-3 font-medium"><?= $escape((string)($evaluation['final_rating'] ?? '-')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3"><?= $escape((string)($evaluation['evaluator_email'] ?? '-')) ?></td>
              <td class="py-3 text-xs text-gray-600 max-w-xs"><?= $escape((string)($evaluation['remarks_preview'] ?? 'No supervisor comments provided.')) ?></td>
              <td class="py-3">
                <button type="button" data-open-detail data-detail-title="Supervisor Feedback" data-detail-body="<?= $escape($remarks !== '' ? $remarks : 'No supervisor comments provided.') ?>" class="border px-3 py-1 rounded-lg text-xs">View Full</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-bold mb-4">PRAISE <span class="text-daGreen">Nominations</span></h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Award</th>
          <th class="text-left py-3">Cycle</th>
          <th class="text-left py-3">Submitted</th>
          <th class="text-left py-3">Status</th>
          <th class="text-left py-3">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employeeNominations)): ?>
          <tr><td class="py-3 text-gray-500" colspan="5">No PRAISE nominations yet.</td></tr>
        <?php else: ?>
          <?php foreach ($employeeNominations as $nomination): ?>
            <?php [$statusLabel, $statusClass] = $statusPill((string)($nomination['status'] ?? 'pending')); ?>
            <tr class="border-b">
              <td class="py-3">
                <p class="font-medium"><?= $escape((string)($nomination['award_name'] ?? 'PRAISE Award')) ?></p>
                <p class="text-xs text-gray-500"><?= $escape((string)($nomination['award_description'] ?? '')) ?></p>
              </td>
              <td class="py-3"><?= $escape((string)($nomination['cycle_name'] ?? 'General Cycle')) ?></td>
              <td class="py-3"><?= $escape($formatDate($nomination['created_at'] ?? '')) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3">
                <button type="button" data-open-detail data-detail-title="Nomination Justification" data-detail-body="<?= $escape((string)($nomination['justification'] ?? 'No details.')) ?>" class="border px-3 py-1 rounded-lg text-xs">View Justification</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="rounded-xl shadow p-6 mb-6 border-2 border-yellow-200 bg-yellow-50">
  <div class="flex items-center gap-3 mb-4">
    <span class="material-icons text-yellow-600">emoji_events</span>
    <h2 class="text-lg font-bold">Awards &amp; <span class="text-daGreen">Recognitions</span></h2>
  </div>
  <p class="text-sm text-gray-600 mb-4">Celebrating your approved PRAISE recognitions and achievements.</p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if (empty($employeeAwards)): ?>
      <div class="col-span-full rounded-lg border border-dashed border-yellow-300 bg-white/70 px-4 py-5 text-sm text-gray-600">
        No award recognitions yet. Approved PRAISE awards will appear here.
      </div>
    <?php else: ?>
      <?php foreach ($employeeAwards as $award): ?>
        <article class="rounded-lg border border-yellow-200 bg-white px-4 py-4">
          <div class="flex items-start justify-between gap-3">
            <h3 class="font-semibold text-gray-900"><?= $escape((string)($award['award_name'] ?? 'PRAISE Award')) ?></h3>
            <span class="text-xs text-yellow-700 font-semibold"><?= $escape($formatDate((string)($award['received_at'] ?? ''))) ?></span>
          </div>
          <p class="text-sm text-gray-600 mt-2"><?= $escape((string)($award['award_description'] ?? 'Recognition details are not available.')) ?></p>
          <p class="text-xs text-gray-500 mt-2">Cycle: <?= $escape((string)($award['cycle_name'] ?? 'General Cycle')) ?></p>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-4">Training <span class="text-daGreen">Completion Snapshot</span></h2>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b text-gray-500">
          <th class="text-left py-3">Program</th>
          <th class="text-left py-3">Schedule</th>
          <th class="text-left py-3">Mode</th>
          <th class="text-left py-3">Enrollment Status</th>
          <th class="text-left py-3">Score</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employeeTrainingCompletions)): ?>
          <tr><td class="py-3 text-gray-500" colspan="5">No training enrollments yet.</td></tr>
        <?php else: ?>
          <?php foreach ($employeeTrainingCompletions as $training): ?>
            <?php [$statusLabel, $statusClass] = $statusPill((string)($training['enrollment_status'] ?? 'enrolled')); ?>
            <tr class="border-b">
              <td class="py-3"><?= $escape((string)($training['program_title'] ?? 'Training Program')) ?></td>
              <td class="py-3"><?= $escape($formatDate($training['start_date'] ?? '')) ?> - <?= $escape($formatDate($training['end_date'] ?? '')) ?></td>
              <td class="py-3"><?= $escape(ucfirst((string)($training['mode'] ?? '-'))) ?></td>
              <td class="py-3"><span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span></td>
              <td class="py-3"><?= $escape((string)($training['score'] ?? '-')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div id="praiseDetailModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden" aria-hidden="true">
  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg max-h-[90vh] flex flex-col">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h2 id="praiseDetailTitle" class="text-lg font-semibold">Details</h2>
      <button type="button" data-close-detail><span class="material-icons">close</span></button>
    </div>
    <div class="px-6 py-5 overflow-y-auto">
      <p id="praiseDetailBody" class="text-sm text-gray-700 whitespace-pre-line"></p>
    </div>
    <div class="px-6 py-4 border-t flex justify-end">
      <button type="button" data-close-detail class="border px-4 py-2 rounded-lg text-sm">Close</button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
