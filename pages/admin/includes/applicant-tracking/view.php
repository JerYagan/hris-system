<?php
$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if (in_array($key, ['hired', 'offer', 'shortlisted'], true)) {
        return [ucfirst($key), 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['submitted', 'screening', 'interview'], true)) {
        return [ucfirst($key), 'bg-amber-100 text-amber-800'];
    }
    if (in_array($key, ['rejected', 'withdrawn'], true)) {
        return [ucfirst($key), 'bg-rose-100 text-rose-800'];
    }
    return [ucfirst($key !== '' ? $key : 'submitted'), 'bg-slate-100 text-slate-700'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Applicant Tracking</h1>
        <p class="text-sm text-slate-300 mt-2">Monitor application progress, schedule interviews, and update applicant status using modal actions.</p>
    </div>
</div>

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

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Application Progress</h2>
            <p class="text-sm text-slate-500 mt-1">Track applicants and apply scheduling/status updates via modal actions.</p>
        </div>
        <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Back to Applicants</a>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Applicants</label>
            <input id="applicantTrackingSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by name, position, or email">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="applicantTrackingStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Submitted">Submitted</option>
                <option value="Screening">Screening</option>
                <option value="Interview">Interview</option>
                <option value="Shortlisted">Shortlisted</option>
                <option value="Offer">Offer</option>
                <option value="Hired">Hired</option>
                <option value="Rejected">Rejected</option>
                <option value="Withdrawn">Withdrawn</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="applicantTrackingTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Last Interview</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No application records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <?php
                        $applicationId = (string)($application['id'] ?? '');
                        $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
                        $email = (string)($application['applicant']['email'] ?? '-');
                        $position = (string)($application['job']['title'] ?? '-');
                        $statusValue = (string)($application['application_status'] ?? 'submitted');
                        [$statusLabel, $statusClass] = $statusPill($statusValue);

                        $latestInterview = $interviewMap[$applicationId] ?? null;
                        $interviewLabel = '-';
                        if (is_array($latestInterview)) {
                            $stage = ucfirst((string)($latestInterview['stage'] ?? ''));
                            $scheduledAt = (string)($latestInterview['scheduled_at'] ?? '');
                            $interviewLabel = $stage;
                            if ($scheduledAt !== '') {
                                $interviewLabel .= ' • ' . date('M d, Y', strtotime($scheduledAt));
                            }
                        }

                        $updatedAt = (string)($application['updated_at'] ?? $application['submitted_at'] ?? '');
                        $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';

                        $searchText = strtolower(trim($fullName . ' ' . $position . ' ' . $email . ' ' . $statusLabel . ' ' . $updatedLabel));
                        ?>
                        <tr data-track-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-track-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[105px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($interviewLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-track-schedule data-application-id="<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Schedule</button>
                                    <button type="button" data-track-status-update data-application-id="<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Update Status</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="scheduleInterviewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="scheduleInterviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Schedule Interview</h3>
                <button type="button" data-modal-close="scheduleInterviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="scheduleInterviewForm" action="applicant-tracking.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="schedule_interview">
                <input type="hidden" id="scheduleApplicationId" name="application_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Applicant</label>
                    <input id="scheduleApplicantName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Interview Stage</label>
                    <select name="interview_stage" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="hr">HR Interview</option>
                        <option value="technical">Technical Interview</option>
                        <option value="final">Final Interview</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Interview Mode</label>
                    <select name="interview_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="onsite">Onsite</option>
                        <option value="online">Online</option>
                        <option value="phone">Phone</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Interview Date</label>
                    <input type="date" name="interview_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Interview Time</label>
                    <input type="time" name="interview_time" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Scheduling Notes</label>
                    <textarea name="schedule_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add panel details, instructions, or reschedule reason."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="scheduleInterviewModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="updateStatusModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="updateStatusModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Update Application Status</h3>
                <button type="button" data-modal-close="updateStatusModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="updateStatusForm" action="applicant-tracking.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_status">
                <input type="hidden" id="statusApplicationId" name="application_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Applicant</label>
                    <input id="statusApplicantName" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="statusCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">New Status</label>
                    <select name="new_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="submitted">Submitted</option>
                        <option value="screening">Screening</option>
                        <option value="interview">Interview</option>
                        <option value="shortlisted">Shortlisted</option>
                        <option value="offer">Offer</option>
                        <option value="hired">Hired</option>
                        <option value="rejected">Rejected</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Remarks</label>
                    <textarea name="status_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add decision rationale or update details."></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="updateStatusModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
