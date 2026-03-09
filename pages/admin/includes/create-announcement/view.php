<?php
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

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm flex gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Create Announcement</h2>
        <p class="text-sm text-slate-500 mt-1">Announcements are org-wide broadcast messages. This is the authoritative admin workflow for publishing and delivery reporting.</p>
    </header>

    <form id="createAnnouncementForm" action="create-announcement.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="publish_announcement">

        <div>
            <label class="text-slate-600">Broadcast Type</label>
            <input type="hidden" name="announcement_category" value="announcement">
            <input type="text" value="Announcement (Org-wide Broadcast)" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
        </div>

        <div>
            <label class="text-slate-600">Audience</label>
            <input type="hidden" name="audience" id="announcementAudience" value="all_users">
            <input type="text" value="All Active Users" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
        </div>

        <div>
            <label class="text-slate-600">Target Type</label>
            <input type="hidden" name="target_mode" id="announcementTargetMode" value="audience">
            <input type="text" value="Audience-Based (Org-wide)" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
        </div>

        <div id="announcementTargetEmployeesWrap" class="md:col-span-2 hidden">
            <label class="text-slate-600">Target Employees</label>
            <select name="target_employee_ids[]" id="announcementTargetEmployees" multiple size="8" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php foreach ((array)($announcementTargetEmployees ?? []) as $employeeOption): ?>
                    <?php
                    $employeeUserId = strtolower(trim((string)($employeeOption['user_id'] ?? '')));
                    $employeeLabel = trim((string)($employeeOption['label'] ?? 'Employee'));
                    if ($employeeUserId === '') {
                        continue;
                    }
                    ?>
                    <option value="<?= htmlspecialchars($employeeUserId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($employeeLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1">Hold Ctrl (Windows) or Command (Mac) to select multiple employees.</p>
        </div>

        <div id="announcementTargetGroupsWrap" class="md:col-span-2 hidden">
            <label class="text-slate-600">Target Employee Groups</label>
            <select name="target_group_ids[]" id="announcementTargetGroups" multiple size="8" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php foreach ((array)($announcementTargetGroups ?? []) as $groupOption): ?>
                    <?php
                    $groupId = strtolower(trim((string)($groupOption['office_id'] ?? '')));
                    $groupLabel = trim((string)($groupOption['label'] ?? 'Group'));
                    if ($groupId === '') {
                        continue;
                    }
                    ?>
                    <option value="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1">Select one or more divisions/offices to target all employees within those groups.</p>
        </div>

        <div id="announcementTargetRolesWrap" class="md:col-span-2 hidden">
            <label class="text-slate-600">Target Roles</label>
            <select name="target_role_keys[]" id="announcementTargetRoles" multiple size="8" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php foreach ((array)($announcementTargetRoles ?? []) as $roleOption): ?>
                    <?php
                    $roleKey = strtolower(trim((string)($roleOption['role_key'] ?? '')));
                    $roleLabel = trim((string)($roleOption['label'] ?? 'Role'));
                    if ($roleKey === '') {
                        continue;
                    }
                    ?>
                    <option value="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1">Use role targeting to send to one or more role groups regardless of office.</p>
        </div>

        <div>
            <label class="text-slate-600">Delivery Channel</label>
            <select name="delivery_channel" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="both">In-App + Email</option>
                <option value="in_app">In-App Only</option>
                <option value="email">Email Only</option>
            </select>
        </div>

        <div>
            <label class="text-slate-600">Link URL (Optional)</label>
            <input name="link_url" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="/hris-system/pages/employee/notifications.php">
        </div>

        <div class="md:col-span-2">
            <label class="text-slate-600">Title</label>
            <input name="announcement_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter announcement title" required>
        </div>

        <div class="md:col-span-2">
            <label class="text-slate-600">Content</label>
            <textarea name="announcement_body" rows="6" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Write your announcement message here..." required></textarea>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3">
            <button id="announcementPreviewButton" type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Preview</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Publish Announcement</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Delivery Snapshot</h2>
        <p class="text-sm text-slate-500 mt-1">Live totals based on the same published announcement activity shown on the dashboard.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-600">Announcements Published</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalPublished, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Recent publish actions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">In-App Delivered</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalInAppSent, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">Notification rows inserted</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-blue-50">
            <p class="text-xs uppercase tracking-wide text-blue-700">Email Delivered</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$totalEmailSent, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-slate-500 mt-1">SMTP send success count</p>
        </article>
    </div>
</section>

<div id="announcementPreviewModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-announcement-preview-close></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
                <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-slate-800">Announcement Preview</h3>
                                <button type="button" data-announcement-preview-close class="text-slate-500 hover:text-slate-700">✕</button>
                        </div>
                        <div class="p-6 space-y-4 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                        <span id="announcementPreviewCategory" class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-slate-100 text-slate-700">Announcement</span>
                                        <span id="announcementPreviewAudience" class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-blue-50 text-blue-700">All Active Users</span>
                                        <span id="announcementPreviewChannel" class="inline-flex items-center px-2.5 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700">In-App + Email</span>
                                </div>

                                <h4 id="announcementPreviewTitle" class="text-xl font-semibold text-slate-900">Announcement Title</h4>
                                <p id="announcementPreviewBody" class="whitespace-pre-wrap text-slate-700 leading-relaxed">Announcement content preview.</p>

                                <div id="announcementPreviewLinkWrap" class="hidden text-xs text-slate-600">
                                        <span class="font-medium text-slate-700">Link:</span>
                                        <span id="announcementPreviewLink"></span>
                                </div>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                                <button type="button" data-announcement-preview-close class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Back to Edit</button>
                                <button type="submit" form="createAnnouncementForm" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Confirm & Publish</button>
                        </div>
                </div>
        </div>
</div>

<script>
(() => {
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('createAnnouncementForm');
        const previewButton = document.getElementById('announcementPreviewButton');
        const previewModal = document.getElementById('announcementPreviewModal');
        const previewTitle = document.getElementById('announcementPreviewTitle');
        const previewBody = document.getElementById('announcementPreviewBody');
        const previewCategory = document.getElementById('announcementPreviewCategory');
        const previewAudience = document.getElementById('announcementPreviewAudience');
        const previewChannel = document.getElementById('announcementPreviewChannel');
        const targetModeSelect = document.getElementById('announcementTargetMode');
        const audienceSelect = document.getElementById('announcementAudience');
        const targetEmployeesWrap = document.getElementById('announcementTargetEmployeesWrap');
        const targetGroupsWrap = document.getElementById('announcementTargetGroupsWrap');
        const targetRolesWrap = document.getElementById('announcementTargetRolesWrap');
        const targetEmployees = document.getElementById('announcementTargetEmployees');
        const targetGroups = document.getElementById('announcementTargetGroups');
        const targetRoles = document.getElementById('announcementTargetRoles');
        const previewLinkWrap = document.getElementById('announcementPreviewLinkWrap');
        const previewLink = document.getElementById('announcementPreviewLink');
        const closeButtons = document.querySelectorAll('[data-announcement-preview-close]');

        if (!form || !previewButton || !previewModal) {
            return;
        }

        const toLabel = (value) => {
            const map = {
                announcement_category: {
                    announcement: 'Announcement',
                    system: 'System',
                    hr: 'HR',
                    recruitment: 'Recruitment',
                    payroll: 'Payroll'
                },
                audience: {
                    all_users: 'All Active Users',
                    admins: 'Admins / HR / Supervisors',
                    staff: 'Staff',
                    employees: 'Employees',
                    applicants: 'Applicants'
                },
                delivery_channel: {
                    both: 'In-App + Email',
                    in_app: 'In-App Only',
                    email: 'Email Only'
                },
                target_mode: {
                    audience: 'Audience-Based',
                    employee: 'Specific Employee(s)',
                    group: 'Employee Group',
                    role: 'Specific Role(s)'
                }
            };
            return map;
        };

        const labels = toLabel();

        const setSelectEnabled = (element, enabled) => {
            if (!element) {
                return;
            }
            element.disabled = !enabled;
            if (!enabled) {
                Array.from(element.options || []).forEach((option) => {
                    option.selected = false;
                });
            }
        };

        const updateTargetMode = () => {
            const mode = targetModeSelect?.value || 'audience';

            if (targetEmployeesWrap) {
                targetEmployeesWrap.classList.toggle('hidden', mode !== 'employee');
            }
            if (targetGroupsWrap) {
                targetGroupsWrap.classList.toggle('hidden', mode !== 'group');
            }
            if (targetRolesWrap) {
                targetRolesWrap.classList.toggle('hidden', mode !== 'role');
            }

            if (audienceSelect) {
                audienceSelect.disabled = mode !== 'audience';
            }

            setSelectEnabled(targetEmployees, mode === 'employee');
            setSelectEnabled(targetGroups, mode === 'group');
            setSelectEnabled(targetRoles, mode === 'role');
        };

        updateTargetMode();
        if (targetModeSelect) {
            targetModeSelect.addEventListener('change', updateTargetMode);
        }

        const openModal = () => {
            previewModal.classList.remove('hidden');
            previewModal.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            previewModal.classList.add('hidden');
            previewModal.setAttribute('aria-hidden', 'true');
        };

        previewButton.addEventListener('click', () => {
            const title = form.querySelector('[name="announcement_title"]')?.value?.trim() || '';
            const body = form.querySelector('[name="announcement_body"]')?.value?.trim() || '';
            const category = form.querySelector('[name="announcement_category"]')?.value || 'announcement';
            const audience = form.querySelector('[name="audience"]')?.value || 'all_users';
            const targetMode = form.querySelector('[name="target_mode"]')?.value || 'audience';
            const channel = form.querySelector('[name="delivery_channel"]')?.value || 'both';
            const link = form.querySelector('[name="link_url"]')?.value?.trim() || '';

            previewTitle.textContent = title !== '' ? title : 'Untitled Announcement';
            previewBody.textContent = body !== '' ? body : 'No content provided.';
            previewCategory.textContent = labels.announcement_category[category] || 'Announcement';
            previewAudience.textContent = targetMode === 'audience'
                ? (labels.audience[audience] || 'All Active Users')
                : (labels.target_mode[targetMode] || 'Targeted');
            previewChannel.textContent = labels.delivery_channel[channel] || 'In-App + Email';

            if (link !== '') {
                previewLink.textContent = link;
                previewLinkWrap.classList.remove('hidden');
            } else {
                previewLink.textContent = '';
                previewLinkWrap.classList.add('hidden');
            }

            openModal();
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !previewModal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
})();
</script>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Recent Announcement Activity</h2>
        <p class="text-sm text-slate-500 mt-1">Audit trail of recent publish actions and delivery counts used as the shared announcement source of truth.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Audience</th>
                    <th class="text-left px-4 py-3">Channel</th>
                    <th class="text-left px-4 py-3">Targets</th>
                    <th class="text-left px-4 py-3">In-App</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Sent By</th>
                    <th class="text-left px-4 py-3">Created At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($announcementRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="9">No published announcements yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($announcementRows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['audience'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['channel'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['targeted_users'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['in_app_sent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['email_sent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['actor_email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
