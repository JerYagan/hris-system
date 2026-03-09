<?php if (($state ?? '') === 'success' && !empty($message)): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo cleanText($message); ?></div>
<?php elseif (($state ?? '') === 'error' && !empty($message)): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo cleanText($message); ?></div>
<?php endif; ?>

<div id="supportPageFeedback" class="hidden" data-state="<?php echo htmlspecialchars((string)($state ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-message="<?php echo htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo cleanText($dataLoadError); ?></div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Support Ticket Center</h2>
        <p class="text-sm text-slate-500 mt-1">Route, review, forward, and resolve employee and applicant support requests.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Submitted</p><p class="mt-2 text-2xl font-semibold text-slate-800"><?php echo (int)($ticketStatusCounts['submitted'] ?? 0); ?></p></div>
        <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">In Review</p><p class="mt-2 text-2xl font-semibold text-amber-700"><?php echo (int)($ticketStatusCounts['in_review'] ?? 0); ?></p></div>
        <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Forwarded</p><p class="mt-2 text-2xl font-semibold text-blue-700"><?php echo (int)($ticketStatusCounts['forwarded_to_staff'] ?? 0); ?></p></div>
        <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Resolved</p><p class="mt-2 text-2xl font-semibold text-emerald-700"><?php echo (int)($ticketStatusCounts['resolved'] ?? 0); ?></p></div>
        <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Rejected</p><p class="mt-2 text-2xl font-semibold text-rose-700"><?php echo (int)($ticketStatusCounts['rejected'] ?? 0); ?></p></div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <form method="GET" action="support.php" class="px-6 py-4 grid grid-cols-1 md:grid-cols-5 gap-3 md:items-end text-sm">
        <div>
            <label class="text-slate-600">Search</label>
            <input type="search" name="search" value="<?php echo htmlspecialchars((string)$ticketSearch, ENT_QUOTES, 'UTF-8'); ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Ticket ID, requester, subject">
        </div>
        <div>
            <label class="text-slate-600">Status</label>
            <select name="status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <?php foreach ((array)$supportStatusOptions as $statusOpt): ?>
                    <option value="<?php echo htmlspecialchars((string)$statusOpt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)$ticketStatusFilter === (string)$statusOpt ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)$statusOpt)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Requester Role</label>
            <select name="role" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Roles</option>
                <option value="employee" <?php echo $ticketRoleFilter === 'employee' ? 'selected' : ''; ?>>Employee</option>
                <option value="applicant" <?php echo $ticketRoleFilter === 'applicant' ? 'selected' : ''; ?>>Applicant</option>
            </select>
        </div>
        <div class="md:col-span-2 flex gap-2 md:justify-end">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-white hover:bg-slate-800">Apply Filters</button>
            <a href="support.php" class="rounded-md border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Ticket</th>
                    <th class="text-left px-4 py-3">Requester</th>
                    <th class="text-left px-4 py-3">Role</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Subject</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($ticketRowsPage)): ?>
                    <tr><td colspan="8" class="px-4 py-4 text-slate-500">No support tickets found for the selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($ticketRowsPage as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs"><?php echo htmlspecialchars((string)($row['ticket_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars((string)($row['requester_label'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars(ucfirst((string)($row['requester_role'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($row['category'] ?? 'general'))), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars((string)($row['subject'] ?? 'Support Ticket'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs <?php echo htmlspecialchars((string)($row['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($row['status'] ?? 'submitted'))), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars(!empty($row['updated_at']) ? date('M j, Y g:i A', strtotime((string)$row['updated_at'])) : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3">
                                <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="support.php?ticket_id=<?php echo rawurlencode((string)($row['ticket_id'] ?? '')); ?>">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($ticketsTotalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                <p>Page <?php echo (int)$ticketsPage; ?> of <?php echo (int)$ticketsTotalPages; ?> (<?php echo (int)$ticketsTotal; ?> tickets)</p>
                <div class="flex items-center gap-2">
                    <?php $baseQuery = ['search' => (string)$ticketSearch, 'status' => (string)$ticketStatusFilter, 'role' => (string)$ticketRoleFilter]; ?>
                    <?php if ((int)$ticketsPage > 1): ?>
                        <?php $prevQuery = $baseQuery; $prevQuery['page'] = (int)$ticketsPage - 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="support.php?<?php echo htmlspecialchars(http_build_query($prevQuery), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ((int)$ticketsPage < (int)$ticketsTotalPages): ?>
                        <?php $nextQuery = $baseQuery; $nextQuery['page'] = (int)$ticketsPage + 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="support.php?<?php echo htmlspecialchars(http_build_query($nextQuery), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($selectedTicket)): ?>
<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-2">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Manage Ticket</h3>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars((string)($selectedTicket['ticket_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <span class="px-2 py-1 rounded-full text-xs <?php echo htmlspecialchars((string)($selectedTicket['status_class'] ?? 'bg-slate-100 text-slate-700'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($selectedTicket['status'] ?? 'submitted'))), ENT_QUOTES, 'UTF-8'); ?></span>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div class="space-y-3">
            <div><p class="text-slate-500">Requester</p><p class="font-medium text-slate-800"><?php echo htmlspecialchars((string)($selectedTicket['requester_label'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars(ucfirst((string)($selectedTicket['requester_role'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?>)</p></div>
            <div><p class="text-slate-500">Category</p><p class="font-medium text-slate-800"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($selectedTicket['category'] ?? 'general'))), ENT_QUOTES, 'UTF-8'); ?></p></div>
            <div><p class="text-slate-500">Subject</p><p class="font-medium text-slate-800"><?php echo htmlspecialchars((string)($selectedTicket['subject'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p></div>
            <div><p class="text-slate-500">Message</p><p class="font-medium text-slate-800 whitespace-pre-line"><?php echo htmlspecialchars((string)($selectedTicket['message'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p></div>
            <?php if (!empty($selectedTicket['attachment_path'])): ?>
                <div><a class="text-sm text-emerald-700 hover:underline" target="_blank" rel="noopener" href="<?php echo htmlspecialchars('/hris-system/' . ltrim((string)$selectedTicket['attachment_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>">Open Attachment</a></div>
            <?php endif; ?>
        </div>

        <form id="adminSupportManageForm" method="POST" action="support.php?ticket_id=<?php echo rawurlencode((string)($selectedTicket['ticket_id'] ?? '')); ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(ensureCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="update_support_ticket">
            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars((string)($selectedTicket['ticket_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="requester_user_id" value="<?php echo htmlspecialchars((string)($selectedTicket['requester_user_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="requester_role" value="<?php echo htmlspecialchars((string)($selectedTicket['requester_role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <div>
                <label class="text-slate-600">Set Status</label>
                <select name="ticket_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <?php foreach ((array)$supportStatusOptions as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars((string)$statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)$selectedTicket['status'] === (string)$statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)$statusOption)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-slate-600">Forward to Staff (if forwarded)</label>
                <select name="forward_to_user_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">None</option>
                    <?php foreach ((array)$staffUsers as $staffUser): ?>
                        <option value="<?php echo htmlspecialchars((string)($staffUser['user_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string)($selectedTicket['forward_to_user_id'] ?? '') === (string)($staffUser['user_id'] ?? '') ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($staffUser['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-slate-600">Admin Notes</label>
                <textarea name="admin_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Internal or visible note to requester."><?php echo htmlspecialchars((string)($selectedTicket['admin_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div>
                <label class="text-slate-600">Resolution Notes</label>
                <textarea name="resolution_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Required for resolved/rejected statuses."><?php echo htmlspecialchars((string)($selectedTicket['resolution_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Save Ticket Update</button>
            </div>
        </form>
    </div>

    <?php if (!empty($selectedTicket['history']) && is_array($selectedTicket['history'])): ?>
        <div class="border-t border-slate-200 px-6 py-4">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Ticket Timeline</h4>
            <div class="space-y-3 text-sm">
                <?php foreach (array_reverse((array)$selectedTicket['history']) as $historyItem): ?>
                    <article class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars((string)($historyItem['action'] ?? 'Update'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars(!empty($historyItem['created_at']) ? date('M j, Y g:i A', strtotime((string)$historyItem['created_at'])) : '-', ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($historyItem['notes'])): ?><p class="mt-2 text-slate-700">Note: <?php echo htmlspecialchars((string)$historyItem['notes'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                        <?php if (!empty($historyItem['resolution'])): ?><p class="mt-1 text-slate-700">Resolution: <?php echo htmlspecialchars((string)$historyItem['resolution'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
