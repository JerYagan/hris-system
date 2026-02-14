<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">User Management</h1>
        <p class="text-sm text-slate-300 mt-2">Manage user accounts, assign access roles, and control login credentials.</p>
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
            <h2 class="text-lg font-semibold text-slate-800">User Accounts</h2>
            <p class="text-sm text-slate-500 mt-1">View users, filter by status, and trigger account actions from the modal.</p>
        </div>
        <button type="button" data-modal-open="accountModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Add / Archive Account</button>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <input id="usersSearchInput" type="search" class="w-full md:w-80 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search user, email, or status">
        <select id="usersStatusFilter" class="border border-slate-300 rounded-md px-3 py-2 text-sm">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Suspended">Suspended</option>
            <option value="Disabled">Disabled</option>
            <option value="Archived">Archived</option>
            <option value="Pending">Pending</option>
        </select>
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table id="usersTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">User</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Mobile</th>
                    <th class="text-left px-4 py-3">Primary Role</th>
                    <th class="text-left px-4 py-3">Account Status</th>
                    <th class="text-left px-4 py-3">Created Date</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($users)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $firstName = (string)($user['people']['first_name'] ?? '');
                        $surname = (string)($user['people']['surname'] ?? '');
                        $displayName = trim($firstName . ' ' . $surname);
                        if ($displayName === '') {
                            $displayName = (string)($user['email'] ?? 'Unknown User');
                        }
                        [$statusLabel, $statusClass] = toStatusPill((string)($user['account_status'] ?? 'pending'));
                        $mobileNo = trim((string)($user['mobile_no'] ?? '-'));
                        if ($mobileNo === '') {
                            $mobileNo = '-';
                        }
                        $primaryRole = (string)($primaryRoleMap[(string)($user['id'] ?? '')] ?? 'Unassigned');
                        $created = (string)($user['created_at'] ?? '');
                        $createdLabel = $created !== '' ? date('M d, Y', strtotime($created)) : '-';
                        $searchText = strtolower(trim($displayName . ' ' . (string)($user['email'] ?? '') . ' ' . $mobileNo . ' ' . $primaryRole . ' ' . $statusLabel . ' ' . $createdLabel));
                        ?>
                        <tr data-user-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-user-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)($user['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($mobileNo, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($primaryRole, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3" data-status="<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-fill-role data-user-id="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Role</button>
                                    <button type="button" data-fill-credential data-user-id="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Credentials</button>
                                    <button type="button" data-prepare-archive data-email="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-display-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" class="px-2.5 py-1.5 text-xs rounded-md border border-rose-300 text-rose-700 hover:bg-rose-50">Archive</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="accountModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="accountModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Add / Archive User Account</h3>
                <button type="button" data-modal-close="accountModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="accountForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="account">
                <div>
                    <label class="text-slate-600">Full Name</label>
                    <input id="accountFullNameInput" type="text" name="full_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter full name">
                </div>
                <div>
                    <label class="text-slate-600">Action</label>
                    <select id="accountActionSelect" name="account_action" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="add">Add User Account</option>
                        <option value="archive">Archive User Account</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Email Address</label>
                    <input id="accountEmailInput" type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter official email" required>
                </div>
                <div>
                    <label class="text-slate-600">Department</label>
                    <select name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                        <option value="">Select office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Account Notes</label>
                    <textarea name="account_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add onboarding details or archival reason"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="accountModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="roleModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="roleModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Assign Role</h3>
                <button type="button" data-modal-close="roleModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="roleForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <input type="hidden" name="form_action" value="role">
                <div>
                    <label class="text-slate-600">User</label>
                    <select id="roleUserSelect" name="role_user_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select user</option>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $firstName = (string)($user['people']['first_name'] ?? '');
                            $surname = (string)($user['people']['surname'] ?? '');
                            $displayName = trim($firstName . ' ' . $surname);
                            if ($displayName === '') {
                                $displayName = (string)($user['email'] ?? 'Unknown User');
                            }
                            ?>
                            <option value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($displayName . ' (' . (string)($user['email'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Role</label>
                    <select name="role_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars((string)($role['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)($role['role_name'] ?? $role['role_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Effectivity Date</label>
                    <input type="date" name="effectivity_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
                <div class="md:col-span-3 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="roleModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="credentialModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="credentialModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Manage Login Credentials</h3>
                <button type="button" data-modal-close="credentialModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="credentialForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="credential">
                <div>
                    <label class="text-slate-600">User</label>
                    <select id="credentialUserSelect" name="credential_user_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="">Select user</option>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $firstName = (string)($user['people']['first_name'] ?? '');
                            $surname = (string)($user['people']['surname'] ?? '');
                            $displayName = trim($firstName . ' ' . $surname);
                            if ($displayName === '') {
                                $displayName = (string)($user['email'] ?? 'Unknown User');
                            }
                            ?>
                            <option value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($displayName . ' (' . (string)($user['email'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Credential Action</label>
                    <select name="credential_action" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="reset_password">Reset Password</option>
                        <option value="unlock_account">Unlock Account</option>
                        <option value="disable_login">Disable Login Access</option>
                    </select>
                </div>
                <div>
                    <label class="text-slate-600">Temporary Password (if reset)</label>
                    <input type="text" name="temporary_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter temporary password">
                </div>
                <div>
                    <label class="text-slate-600">Effective Until</label>
                    <input type="date" name="effective_until" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Credential Notes</label>
                    <textarea name="credential_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add reason for credential update"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="credentialModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
