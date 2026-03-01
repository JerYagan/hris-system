<?php
$statusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    return match ($key) {
        'submitted' => ['Applied', 'bg-blue-100 text-blue-800'],
        'screening' => ['Verified', 'bg-indigo-100 text-indigo-800'],
        'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
        'shortlisted' => ['Evaluation', 'bg-violet-100 text-violet-800'],
        'offer' => ['For Approval', 'bg-cyan-100 text-cyan-800'],
        'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
        'rejected', 'withdrawn' => ['Rejected', 'bg-rose-100 text-rose-800'],
        default => ['Applied', 'bg-slate-100 text-slate-700'],
    };
};

$pipelineApplications = [];
$hiredApplications = [];
foreach ((array)$applications as $applicationRow) {
    $statusKey = strtolower(trim((string)($applicationRow['application_status'] ?? 'submitted')));
    $applicantUserId = strtolower(trim((string)($applicationRow['applicant']['user_id'] ?? '')));
    $isAlreadyEmployee = $applicantUserId !== '' && !empty($hasCurrentEmploymentByUserId[$applicantUserId]);

    if ($statusKey === 'hired') {
        if ($isAlreadyEmployee) {
            continue;
        }
        $hiredApplications[] = $applicationRow;
    } else {
        $pipelineApplications[] = $applicationRow;
    }
}

$feedbackDecisionLabel = static function (string $decision): string {
    return match (strtolower(trim($decision))) {
        'for_next_step' => 'For Approval',
        'on_hold' => 'Pending',
        'rejected' => 'Rejected',
        'hired' => 'Hired',
        default => '-',
    };
};

$progressRows = [];
foreach ($pipelineApplications as $application) {
    $applicationId = (string)($application['id'] ?? '');
    if ($applicationId === '') {
        continue;
    }

    $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
    $email = (string)($application['applicant']['email'] ?? '-');
    $position = (string)($application['job']['title'] ?? '-');
    $plantillaItemNo = trim((string)($application['job']['plantilla_item_no'] ?? ''));
    $statusRaw = (string)($application['application_status'] ?? 'submitted');
    [$statusLabel, $statusClass] = $statusPill($statusRaw);
    $applicantUserId = strtolower(trim((string)($application['applicant']['user_id'] ?? '')));
    $isAlreadyEmployee = $applicantUserId !== '' && !empty($hasCurrentEmploymentByUserId[$applicantUserId]);

    $submittedAt = (string)($application['submitted_at'] ?? '');
    $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';

    $latestInterview = $interviewMap[$applicationId] ?? null;
    $latestInterviewLabel = '-';
    if (is_array($latestInterview)) {
        $stage = ucfirst((string)($latestInterview['stage'] ?? ''));
        $scheduledAt = (string)($latestInterview['scheduled_at'] ?? '');
        $latestInterviewLabel = $stage;
        if ($scheduledAt !== '') {
            $latestInterviewLabel .= ' • ' . date('M d, Y', strtotime($scheduledAt));
        }
    }

    $interviewFeedbackLabel = '-';
    if (is_array($latestInterview)) {
        $interviewResult = trim((string)($latestInterview['result'] ?? ''));
        $interviewFeedbackLabel = $interviewResult !== '' ? ucfirst($interviewResult) : 'Pending';
    }

    $feedback = (array)($feedbackMap[$applicationId] ?? []);
    $feedbackDecision = $feedbackDecisionLabel((string)($feedback['decision'] ?? ''));
    if ($feedbackDecision !== '-') {
        $interviewFeedbackLabel .= ' • ' . $feedbackDecision;
    }

    $updatedAt = (string)($application['updated_at'] ?? $submittedAt);
    $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';

    $progressRows[] = [
        'application_id' => $applicationId,
        'applicant_name' => $fullName,
        'email' => $email,
        'posting' => $position,
        'plantilla_item_no' => $plantillaItemNo !== '' ? $plantillaItemNo : '-',
        'submitted_label' => $submittedLabel,
        'latest_interview_label' => $latestInterviewLabel,
        'interview_feedback_label' => $interviewFeedbackLabel,
        'status_raw' => strtolower(trim($statusRaw)),
        'status_label' => $statusLabel,
        'status_class' => $statusClass,
        'already_employee' => $isAlreadyEmployee,
        'updated_label' => $updatedLabel,
        'search_text' => strtolower(trim($fullName . ' ' . $position . ' ' . $plantillaItemNo . ' ' . $email . ' ' . $statusLabel . ' ' . $submittedLabel . ' ' . $latestInterviewLabel . ' ' . $interviewFeedbackLabel)),
    ];
}

$queueRows = array_values(array_filter($progressRows, static function (array $row): bool {
    return in_array((string)($row['status_raw'] ?? ''), ['submitted', 'screening', 'interview', 'shortlisted', 'offer'], true);
}));

$perPage = 10;
$progressPage = max(1, (int)($_GET['progress_page'] ?? 1));
$progressTotalPages = max(1, (int)ceil(count($progressRows) / $perPage));
$progressPage = min($progressPage, $progressTotalPages);
$progressOffset = ($progressPage - 1) * $perPage;
$progressPageRows = array_slice($progressRows, $progressOffset, $perPage);

$queuePage = max(1, (int)($_GET['queue_page'] ?? 1));
$queueTotalPages = max(1, (int)ceil(count($queueRows) / $perPage));
$queuePage = min($queuePage, $queueTotalPages);
$queueOffset = ($queuePage - 1) * $perPage;
$queuePageRows = array_slice($queueRows, $queueOffset, $perPage);

$buildPaginationHref = static function (string $target, int $page) use ($progressPage, $queuePage): string {
    $params = [
        'progress_page' => $target === 'progress' ? $page : $progressPage,
        'queue_page' => $target === 'queue' ? $page : $queuePage,
    ];
    return 'applicant-tracking.php?' . http_build_query($params);
};
?>

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
        </div>
        <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Back to Applicants Registration</a>
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
                <option value="Applied">Applied</option>
                <option value="Verified">Verified</option>
                <option value="Interview">Interview</option>
                <option value="Evaluation">Evaluation</option>
                <option value="For Approval">For Approval</option>
                <option value="Hired">Hired</option>
                <option value="Rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="applicantTrackingTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Plantilla Number</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Latest Interview</th>
                    <th class="text-left px-4 py-3">Interview &amp; Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($progressPageRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="8">No application records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($progressPageRows as $row): ?>
                        <tr class="hover:bg-slate-100 transition-colors" data-track-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-track-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['posting'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['plantilla_item_no'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['submitted_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['latest_interview_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['interview_feedback_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center justify-center min-w-[105px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($row['already_employee'])): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Already Employee</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <?php if (!empty($row['already_employee'])): ?>
                                        <button type="button" disabled class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed"><span class="material-symbols-outlined text-[15px]">event</span>Schedule</button>
                                        <button type="button" disabled class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed"><span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status</button>
                                    <?php else: ?>
                                        <button type="button" data-track-schedule data-application-id="<?= htmlspecialchars((string)$row['application_id'], ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">event</span>Schedule</button>
                                        <button type="button" data-track-status-update data-application-id="<?= htmlspecialchars((string)$row['application_id'], ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-emerald-300 bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($progressTotalPages > 1): ?>
        <div class="px-6 pb-6 flex items-center justify-between text-sm">
            <p class="text-slate-500">Page <?= (int)$progressPage ?> of <?= (int)$progressTotalPages ?></p>
            <div class="flex items-center gap-2">
                <?php if ($progressPage > 1): ?>
                    <a href="<?= htmlspecialchars($buildPaginationHref('progress', $progressPage - 1), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</a>
                <?php endif; ?>
                <?php if ($progressPage < $progressTotalPages): ?>
                    <a href="<?= htmlspecialchars($buildPaginationHref('progress', $progressPage + 1), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Screening &amp; Next Steps Queue</h2>
        <p class="text-sm text-slate-500 mt-1">Applicants currently in active screening/evaluation flow.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Posting</th>
                    <th class="text-left px-4 py-3">Plantilla Number</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                    <th class="text-left px-4 py-3">Latest Interview</th>
                    <th class="text-left px-4 py-3">Interview &amp; Feedback</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Current Stage</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($queuePageRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="9">No screening queue records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queuePageRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['posting'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['plantilla_item_no'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['submitted_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['latest_interview_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['interview_feedback_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center justify-center min-w-[105px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($row['already_employee'])): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Already Employee</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <?php if (!empty($row['already_employee'])): ?>
                                        <button type="button" disabled class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed"><span class="material-symbols-outlined text-[15px]">event</span>Schedule</button>
                                        <button type="button" disabled class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed"><span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status</button>
                                    <?php else: ?>
                                        <button type="button" data-track-schedule data-application-id="<?= htmlspecialchars((string)$row['application_id'], ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">event</span>Schedule</button>
                                        <button type="button" data-track-status-update data-application-id="<?= htmlspecialchars((string)$row['application_id'], ENT_QUOTES, 'UTF-8') ?>" data-applicant-name="<?= htmlspecialchars((string)$row['applicant_name'], ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-emerald-300 bg-emerald-50/50 text-emerald-700 hover:bg-emerald-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">sync_alt</span>Update Status</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($queueTotalPages > 1): ?>
        <div class="px-6 pb-6 flex items-center justify-between text-sm">
            <p class="text-slate-500">Page <?= (int)$queuePage ?> of <?= (int)$queueTotalPages ?></p>
            <div class="flex items-center gap-2">
                <?php if ($queuePage > 1): ?>
                    <a href="<?= htmlspecialchars($buildPaginationHref('queue', $queuePage - 1), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</a>
                <?php endif; ?>
                <?php if ($queuePage < $queueTotalPages): ?>
                    <a href="<?= htmlspecialchars($buildPaginationHref('queue', $queuePage + 1), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Hired Applicants</h2>
        <p class="text-sm text-slate-500 mt-1">Convert hired applicants into employee records.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Plantilla Number</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($hiredApplications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No hired applicants available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hiredApplications as $application): ?>
                        <?php
                        $applicationId = (string)($application['id'] ?? '');
                        $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
                        $email = (string)($application['applicant']['email'] ?? '-');
                        $position = (string)($application['job']['title'] ?? '-');
                        $plantillaItemNo = trim((string)($application['job']['plantilla_item_no'] ?? ''));
                        $statusValue = (string)($application['application_status'] ?? 'hired');
                        [$statusLabel, $statusClass] = $statusPill($statusValue);
                        $applicantUserId = strtolower(trim((string)($application['applicant']['user_id'] ?? '')));
                        $isAlreadyEmployee = $applicantUserId !== '' && !empty($hasCurrentEmploymentByUserId[$applicantUserId]);
                        $updatedAt = (string)($application['updated_at'] ?? $application['submitted_at'] ?? '');
                        $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';
                        ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($plantillaItemNo !== '' ? $plantillaItemNo : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center justify-center min-w-[105px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isAlreadyEmployee): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Already Employee</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php if ($isAlreadyEmployee): ?>
                                    <button type="button" disabled class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed shadow-sm">
                                        <span class="material-symbols-outlined text-[16px]">check_circle</span>Already Employee
                                    </button>
                                <?php else: ?>
                                    <form action="applicant-tracking.php" method="post" class="inline-block">
                                        <input type="hidden" name="form_action" value="convert_hired_to_employee">
                                        <input type="hidden" name="application_id" value="<?= htmlspecialchars($applicationId, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">badge</span>Add as Employee
                                        </button>
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
                        <option value="shortlisted">Shortlisted</option>
                        <option value="interview">Interview</option>
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
