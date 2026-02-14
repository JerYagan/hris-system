<?php
$postingPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if ($key === 'published') {
        return ['Open', 'bg-emerald-100 text-emerald-800'];
    }
    if ($key === 'closed') {
        return ['Closed', 'bg-amber-100 text-amber-800'];
    }
    if ($key === 'archived') {
        return ['Archived', 'bg-slate-200 text-slate-700'];
    }

    return ['Draft', 'bg-blue-100 text-blue-800'];
};

$appStatusPill = static function (string $status): array {
    $key = strtolower(trim($status));
    if (in_array($key, ['hired', 'offer', 'shortlisted'], true)) {
        return ['Approved', 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['interview'], true)) {
        return ['For Interview', 'bg-blue-100 text-blue-800'];
    }
    if (in_array($key, ['submitted', 'screening'], true)) {
        return ['Pending', 'bg-amber-100 text-amber-800'];
    }

    return [ucfirst($key), 'bg-slate-100 text-slate-700'];
};
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Recruitment Management</h1>
        <p class="text-sm text-slate-300 mt-2">Manage hiring posts, application periods, and screening officer assignments.</p>
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
            <h2 class="text-lg font-semibold text-slate-800">Job Listings</h2>
            <p class="text-sm text-slate-500 mt-1">Overview of open and archived postings for all hiring departments.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Applicants</a>
            <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
        </div>
    </header>

    <div class="p-6 overflow-x-auto">
        <table id="recruitmentPostingsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Applicants</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Remarks</th>
                    <th class="text-left px-4 py-3">Last Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($postings)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="6">No job postings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($postings as $posting): ?>
                        <?php
                        $postingId = (string)($posting['id'] ?? '');
                        $title = (string)($posting['title'] ?? '-');
                        $department = (string)($posting['office']['office_name'] ?? 'Unassigned Office');
                        $totalApplicants = (int)($applicationCountsByPosting[$postingId] ?? 0);
                        [$statusLabel, $statusClass] = $postingPill((string)($posting['posting_status'] ?? 'draft'));
                        $closeDate = (string)($posting['close_date'] ?? '');
                        $remark = $closeDate !== '' ? 'Application ends ' . date('M d, Y', strtotime($closeDate)) : 'No closing date set.';
                        $updatedAt = (string)($posting['updated_at'] ?? '');
                        $updatedLabel = $updatedAt !== '' ? date('M d, Y', strtotime($updatedAt)) : '-';
                        ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$totalApplicants, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($remark, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Registered Applicants</h2>
        <p class="text-sm text-slate-500 mt-1">Search all registered applicants and click status boxes to view matching records.</p>
    </header>

    <div class="p-6 border-b border-slate-200 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Applicants</label>
            <input id="recruitmentApplicantsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by applicant name, position, or email">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="recruitmentApplicantsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="For Interview">For Interview</option>
            </select>
        </div>
    </div>

    <div class="px-6 pt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs uppercase text-slate-600">Registered Applicants</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['registered'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">View all submitted applications.</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-emerald-50">
                <p class="text-xs uppercase text-emerald-700">Approved</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['approved'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">Applicants marked as qualified.</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-amber-50">
                <p class="text-xs uppercase text-amber-700">Pending Decisions</p>
                <p class="text-2xl font-semibold text-slate-800 mt-2"><?= htmlspecialchars((string)$applicantStatusCounts['pending'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-600 mt-1">Applicants awaiting final decision.</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" data-app-status-chip="" class="px-3 py-1.5 text-xs rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50">All</button>
            <button type="button" data-app-status-chip="Approved" class="px-3 py-1.5 text-xs rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-50">Approved</button>
            <button type="button" data-app-status-chip="Pending" class="px-3 py-1.5 text-xs rounded-full border border-amber-300 text-amber-700 hover:bg-amber-50">Pending</button>
            <button type="button" data-app-status-chip="For Interview" class="px-3 py-1.5 text-xs rounded-full border border-blue-300 text-blue-700 hover:bg-blue-50">For Interview</button>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="recruitmentApplicantsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No application records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <?php
                        $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
                        $email = (string)($application['applicant']['email'] ?? '-');
                        $position = (string)($application['job']['title'] ?? '-');
                        [$statusLabel, $statusClass] = $appStatusPill((string)($application['application_status'] ?? 'submitted'));
                        $submittedAt = (string)($application['submitted_at'] ?? '');
                        $submittedLabel = $submittedAt !== '' ? date('M d, Y', strtotime($submittedAt)) : '-';
                        ?>
                        <?php $rowSearch = strtolower(trim($fullName . ' ' . $position . ' ' . $email . ' ' . $statusLabel . ' ' . $submittedLabel)); ?>
                        <tr data-app-search="<?= htmlspecialchars($rowSearch, ENT_QUOTES, 'UTF-8') ?>" data-app-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="inline-flex items-center justify-center min-w-[110px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($submittedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
