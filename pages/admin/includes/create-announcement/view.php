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
        <p class="text-sm text-slate-500 mt-1">Send announcements to selected user groups through in-app notifications, email, or both.</p>
    </header>

    <form id="createAnnouncementForm" action="create-announcement.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="publish_announcement">

        <div>
            <label class="text-slate-600">Announcement Category</label>
            <select name="announcement_category" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="announcement">Announcement</option>
                <option value="system">System</option>
                <option value="hr">HR</option>
                <option value="recruitment">Recruitment</option>
                <option value="payroll">Payroll</option>
            </select>
        </div>

        <div>
            <label class="text-slate-600">Audience</label>
            <select name="audience" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="all_users">All Active Users</option>
                <option value="admins">Admins / HR / Supervisors</option>
                <option value="staff">Staff</option>
                <option value="employees">Employees</option>
                <option value="applicants">Applicants</option>
            </select>
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
        <p class="text-sm text-slate-500 mt-1">Live totals based on recent published announcements.</p>
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
                }
            };
            return map;
        };

        const labels = toLabel();

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
            const channel = form.querySelector('[name="delivery_channel"]')?.value || 'both';
            const link = form.querySelector('[name="link_url"]')?.value?.trim() || '';

            previewTitle.textContent = title !== '' ? title : 'Untitled Announcement';
            previewBody.textContent = body !== '' ? body : 'No content provided.';
            previewCategory.textContent = labels.announcement_category[category] || 'Announcement';
            previewAudience.textContent = labels.audience[audience] || 'All Active Users';
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
        <p class="text-sm text-slate-500 mt-1">Audit trail of recent publish actions and delivery counts.</p>
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
