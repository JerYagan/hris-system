<?php
$state = $state ?? null;
$message = $message ?? null;
$userManagementPartial = $userManagementPartial ?? '';
$employmentClassificationOptions = $employmentClassificationOptions ?? [];
$roles = $roles ?? [];
$offices = $offices ?? [];
$officesDirectory = $officesDirectory ?? [];
$positions = $positions ?? [];
$users = $users ?? [];
$modalUsers = $modalUsers ?? [];
$primaryRoleMap = $primaryRoleMap ?? [];
$userOfficeMap = $userOfficeMap ?? [];
$activeAdminUserIdSet = $activeAdminUserIdSet ?? [];
$activeAdminCount = $activeAdminCount ?? 0;
$activeAdminLimit = $activeAdminLimit ?? 3;
$newHireCandidates = $newHireCandidates ?? [];
$summaryCards = $summaryCards ?? [];

$renderSummaryCards = static function () use ($summaryCards): void {
    ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <?php foreach ($summaryCards as $card): ?>
            <article class="rounded-2xl border px-5 py-4 <?= htmlspecialchars((string)($card['tone'] ?? 'bg-white text-slate-700 border-slate-200'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] opacity-80"><?= htmlspecialchars((string)($card['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-3 text-2xl font-semibold"><?= htmlspecialchars((string)($card['value'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="material-symbols-outlined text-[24px] opacity-80"><?= htmlspecialchars((string)($card['icon'] ?? 'dashboard'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <?php
};

$renderQuickCreateForm = static function () use ($roles, $offices, $positions): void {
    ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Quick Create User</h2>
                <p class="text-sm text-slate-500 mt-1">Create an account with the minimum required identity fields. Role, credential, and archive workflows now load only when opened.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-open-user-management-modal="role" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                    Assign Role
                </button>
                <button type="button" data-open-user-management-modal="credential" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px]">vpn_key</span>
                    Credentials
                </button>
                <button type="button" data-open-user-management-modal="position" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px]">work</span>
                    Add Position
                </button>
                <button type="button" data-open-user-management-modal="department" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px]">apartment</span>
                    Add Division
                </button>
            </div>
        </header>
        <form id="quickCreateUserForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm" data-confirm-title="Create this user account?" data-confirm-text="This will create the selected account and assign the chosen role immediately." data-confirm-button-text="Create user" data-confirm-button-color="#0f172a">
            <input type="hidden" name="form_action" value="account">
            <input type="hidden" name="account_action" value="add">
            <div>
                <label class="text-slate-600">Full Name</label>
                <input type="text" name="full_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter full name" required>
            </div>
            <div>
                <label class="text-slate-600">Email Address</label>
                <input type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter email" required>
            </div>
            <div>
                <label class="text-slate-600">Password</label>
                <input type="password" name="password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Minimum 8 characters" minlength="8" required>
            </div>
            <div>
                <label class="text-slate-600">Role</label>
                <select id="quickCreateRoleSelect" name="role_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars((string)($role['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-role-key="<?= htmlspecialchars(strtolower((string)($role['role_key'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($role['role_name'] ?? $role['role_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-slate-500">Applicant accounts can be created immediately. Admin, Staff, and Employee accounts also require division and position so an employment record can be initialized correctly.</p>
            </div>
            <div>
                <label class="text-slate-600">Division</label>
                <select id="quickCreateOfficeSelect" name="office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">Select division</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-slate-600">Position</label>
                <select id="quickCreatePositionSelect" name="position_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">Select position</option>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= htmlspecialchars((string)($position['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($position['position_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2 xl:col-span-4 flex justify-end gap-3 mt-2">
                <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Create User</button>
            </div>
        </form>
    </section>
    <?php
};

$renderNewHireQueue = static function () use ($newHireCandidates): void {
    ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">New Hires Without Employee Accounts</h2>
                <p class="text-sm text-slate-500 mt-1">The first onboarding slice renders immediately so hiring handoff does not wait on the rest of user administration.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Showing latest 10 candidates</span>
        </header>

        <div class="px-6 pb-6 pt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Name</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-left px-4 py-3">Position</th>
                        <th class="text-left px-4 py-3">Division</th>
                        <th class="text-left px-4 py-3">Hired At</th>
                        <th class="text-left px-4 py-3">Application Ref</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($newHireCandidates)): ?>
                        <tr><td class="px-4 py-3 text-slate-500" colspan="7">No new hires pending employee account creation.</td></tr>
                    <?php else: ?>
                        <?php foreach ($newHireCandidates as $candidate): ?>
                            <?php
                            $candidateName = (string)($candidate['full_name'] ?? '-');
                            $candidateEmail = (string)($candidate['email'] ?? '');
                            $candidatePosition = (string)($candidate['position_title'] ?? '-');
                            $candidateDivision = (string)($candidate['division_name'] ?? '-');
                            $candidateHiredAt = (string)($candidate['hired_at'] ?? '-');
                            $candidateRef = (string)($candidate['application_ref_no'] ?? '-');
                            $candidateApplicationId = (string)($candidate['application_id'] ?? '');
                            $candidateOfficeId = (string)($candidate['office_id'] ?? '');
                            ?>
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($candidateName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($candidateEmail, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($candidatePosition, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($candidateDivision, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($candidateHiredAt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($candidateRef, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">
                                    <form action="user-management.php" method="POST" class="inline" data-confirm-title="Create employee account?" data-confirm-text="This will create the employee account, assign onboarding access, and send the generated credentials." data-confirm-button-text="Create account">
                                        <input type="hidden" name="form_action" value="onboard_new_hire">
                                        <input type="hidden" name="application_id" value="<?= htmlspecialchars($candidateApplicationId, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($candidateEmail, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="full_name" value="<?= htmlspecialchars($candidateName, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="office_id" value="<?= htmlspecialchars($candidateOfficeId, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="position_title" value="<?= htmlspecialchars($candidatePosition, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="division_name" value="<?= htmlspecialchars($candidateDivision, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm">
                                            <span class="material-symbols-outlined text-[15px]">person_add</span>
                                            Create Account
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
};

$renderUsersQueue = static function () use ($users, $primaryRoleMap, $activeAdminUserIdSet): void {
    ?>
    <section class="bg-white border border-slate-200 rounded-2xl mb-6">
        <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">User Accounts</h2>
                <p class="text-sm text-slate-500 mt-1">The first page of accounts renders up front. Role assignment, credential resets, and archive or delete actions hydrate only when you open them.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Showing latest 25 accounts</span>
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
                        <tr><td class="px-4 py-3 text-slate-500" colspan="7">No users found.</td></tr>
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
                            $personMobileNo = trim((string)($user['people']['mobile_no'] ?? ''));
                            $accountMobileNo = trim((string)($user['mobile_no'] ?? ''));
                            $mobileNo = $personMobileNo !== '' ? $personMobileNo : $accountMobileNo;
                            if ($mobileNo === '') {
                                $mobileNo = '-';
                            }
                            $primaryRole = (string)($primaryRoleMap[(string)($user['id'] ?? '')] ?? 'Unassigned');
                            $isAdminUser = isset($activeAdminUserIdSet[strtolower(trim((string)($user['id'] ?? '')))]);
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
                                    <div class="relative inline-block text-left" data-admin-action-scope>
                                        <button type="button" data-admin-action-menu-toggle aria-haspopup="menu" aria-expanded="false" class="admin-action-button">
                                            <span class="admin-action-button-label">
                                                <span class="material-symbols-outlined">more_horiz</span>
                                                Actions
                                            </span>
                                            <span class="material-symbols-outlined admin-action-chevron">expand_more</span>
                                        </button>
                                        <div data-admin-action-menu role="menu" class="admin-action-menu hidden w-52">
                                            <button type="button" data-action-menu-item data-action-target="manage-role" role="menuitem" class="admin-action-item">
                                                <span class="material-symbols-outlined">badge</span>
                                                Manage Role
                                            </button>
                                            <button type="button" data-action-menu-item data-action-target="credentials" role="menuitem" class="admin-action-item">
                                                <span class="material-symbols-outlined">vpn_key</span>
                                                Credentials
                                            </button>
                                            <button type="button" data-action-menu-item data-action-target="archive-account" role="menuitem" class="<?= $isAdminUser ? 'admin-action-item admin-action-item-disabled' : 'admin-action-item admin-action-item-danger' ?>" <?= $isAdminUser ? 'disabled title="Admin accounts cannot be archived from User Management."' : '' ?>>
                                                <span class="material-symbols-outlined">archive</span>
                                                Archive
                                            </button>
                                            <button type="button" data-action-menu-item data-action-target="delete-user" role="menuitem" class="<?= $isAdminUser ? 'admin-action-item admin-action-item-disabled' : 'admin-action-item admin-action-item-danger' ?>" <?= $isAdminUser ? 'disabled title="Admin accounts cannot be deleted from User Management."' : '' ?>>
                                                <span class="material-symbols-outlined">delete</span>
                                                Delete
                                            </button>
                                        </div>

                                        <button type="button" data-action-trigger="manage-role" data-open-user-management-modal="role" data-fill-role data-user-id="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="hidden">Role</button>
                                        <button type="button" data-action-trigger="credentials" data-open-user-management-modal="credential" data-fill-credential data-user-id="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="hidden">Credentials</button>
                                        <button type="button" data-action-trigger="archive-account" data-open-user-management-modal="archive" data-prepare-archive data-email="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-display-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" class="hidden">Archive</button>
                                        <button type="button" data-action-trigger="delete-user" data-open-user-management-modal="delete" data-prepare-delete data-user-id="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-email="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-display-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" class="hidden">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
};

$renderReferenceSection = static function () use ($positions, $roles, $officesDirectory, $activeAdminLimit): void {
    ?>
    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <article class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <header class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-2">
                <div class="inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-600">work</span>
                    <h3 class="text-sm font-semibold text-slate-800">Positions</h3>
                </div>
                <button type="button" data-open-user-management-modal="position" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Add Position
                </button>
            </header>
            <div class="max-h-72 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                        <tr><th class="text-left px-4 py-2">Position</th><th class="text-left px-4 py-2">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($positions)): ?>
                            <tr><td class="px-4 py-2.5 text-slate-500" colspan="2">No positions found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($positions as $position): ?>
                                <?php $isActive = (bool)($position['is_active'] ?? true); ?>
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-700"><?= htmlspecialchars((string)($position['position_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-2.5"><span class="px-2 py-0.5 text-[11px] rounded-full <?= $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <header class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-2">
                <div class="inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-600">badge</span>
                    <h3 class="text-sm font-semibold text-slate-800">Roles</h3>
                </div>
                <button type="button" data-open-user-management-modal="role" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Assign Role
                </button>
            </header>
            <p class="px-4 pt-3 text-xs text-slate-500">Assignable roles are policy-scoped. Active admin-role users are capped at <?= htmlspecialchars((string)$activeAdminLimit, ENT_QUOTES, 'UTF-8') ?>.</p>
            <div class="max-h-72 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                        <tr><th class="text-left px-4 py-2">Role</th><th class="text-left px-4 py-2">Key</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($roles)): ?>
                            <tr><td class="px-4 py-2.5 text-slate-500" colspan="2">No roles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-700"><?= htmlspecialchars((string)($role['role_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-2.5 text-slate-500"><?= htmlspecialchars((string)($role['role_key'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <header class="px-4 py-3 border-b border-slate-200 flex items-center justify-between gap-2">
                <div class="inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-600">apartment</span>
                    <h3 class="text-sm font-semibold text-slate-800">Divisions</h3>
                </div>
                <button type="button" data-open-user-management-modal="department" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Add Division
                </button>
            </header>
            <div class="max-h-72 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 sticky top-0">
                        <tr><th class="text-left px-4 py-2">Division</th><th class="text-left px-4 py-2">Code</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($officesDirectory)): ?>
                            <tr><td class="px-4 py-2.5 text-slate-500" colspan="2">No divisions found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($officesDirectory as $office): ?>
                                <tr>
                                    <td class="px-4 py-2.5 text-slate-700"><?= htmlspecialchars((string)($office['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-2.5 text-slate-500"><?= htmlspecialchars((string)($office['office_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
    <?php
};

$renderModalPartials = static function () use ($employmentClassificationOptions, $roles, $offices, $positions, $modalUsers, $userOfficeMap, $activeAdminUserIdSet, $activeAdminCount, $activeAdminLimit): void {
    ?>
    <div id="accountModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-modal-close="accountModal"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Archive User Account</h3>
                    <button type="button" data-modal-close="accountModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <form id="accountForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" data-confirm-title="Archive this user account?" data-confirm-text="This will archive the selected account and end its active role assignments." data-confirm-button-text="Archive account">
                    <input type="hidden" name="form_action" value="account">
                    <input type="hidden" name="account_action" value="archive">
                    <div>
                        <label class="text-slate-600">Account</label>
                        <input id="accountFullNameInput" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" placeholder="Selected user" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Email Address</label>
                        <input id="accountEmailInput" type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter official email" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Archive Notes</label>
                        <textarea name="account_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add reason for archiving this account"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                        <button type="button" data-modal-close="accountModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Archive Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteUserModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-modal-close="deleteUserModal"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Delete User</h3>
                    <button type="button" data-modal-close="deleteUserModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <form id="deleteUserForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm" data-confirm-title="Delete this user?" data-confirm-text="This permanently deletes the account and any removable linked records." data-confirm-button-text="Delete user" data-confirm-button-color="#dc2626">
                    <input type="hidden" name="form_action" value="delete_user">
                    <input id="deleteUserIdInput" type="hidden" name="user_id" value="">
                    <div>
                        <label class="text-slate-600">Account</label>
                        <input id="deleteUserNameInput" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Email Address</label>
                        <input id="deleteUserEmailInput" type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Delete Notes</label>
                        <textarea name="delete_notes" rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add reason for deleting this user"></textarea>
                    </div>
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">Delete is permanent. Users with retained recruitment or other protected records may still be blocked and should be archived instead.</p>
                    <div class="flex justify-end gap-3 mt-2">
                        <button type="button" data-modal-close="deleteUserModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="positionModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-modal-close="positionModal"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Add Position</h3>
                    <button type="button" data-modal-close="positionModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <form action="user-management.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm" data-confirm-title="Add this position?" data-confirm-text="This will save the new position and make it available for assignment." data-confirm-button-text="Save position">
                    <input type="hidden" name="form_action" value="add_position">
                    <div>
                        <label class="text-slate-600">Position Code</label>
                        <input type="text" name="position_code" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 uppercase" placeholder="e.g. HR_OFF_1" maxlength="20" pattern="[A-Za-z0-9][A-Za-z0-9\-_]{1,19}" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Position Title</label>
                        <input type="text" name="position_title" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter position title" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Employment Classification</label>
                        <select name="employment_classification" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <?php foreach ((array)$employmentClassificationOptions as $classificationValue => $classificationLabel): ?>
                                <option value="<?= htmlspecialchars((string)$classificationValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$classificationLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Salary Grade</label>
                        <input type="text" name="salary_grade" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Optional (e.g. SG-11)">
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="positionIsSupervisory" type="checkbox" name="is_supervisory" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                        <label for="positionIsSupervisory" class="text-slate-600">Supervisory position</label>
                    </div>
                    <div class="flex justify-end gap-3 mt-2">
                        <button type="button" data-modal-close="positionModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="departmentModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/60" data-modal-close="departmentModal"></div>
        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Add Division</h3>
                    <button type="button" data-modal-close="departmentModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>
                <form action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" data-confirm-title="Add this division?" data-confirm-text="This will save the division and make it available in user assignment flows." data-confirm-button-text="Save division">
                    <input type="hidden" name="form_action" value="add_department">
                    <div class="md:col-span-2">
                        <p class="text-xs text-slate-500">Division records are sourced directly from the offices directory so every configured office remains available in assignment flows.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Division Name</label>
                        <input type="text" name="office_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter division name" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Division Code</label>
                        <input type="text" name="office_code" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 uppercase" placeholder="e.g. HRD_MAIN" maxlength="20" pattern="[A-Za-z0-9][A-Za-z0-9\-_]{1,19}" required>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                        <button type="button" data-modal-close="departmentModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Division</button>
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
                <form id="roleForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm" data-confirm-title="Assign this role?" data-confirm-text="This will update the selected user's role assignment and effectivity details." data-confirm-button-text="Assign role">
                    <input type="hidden" name="form_action" value="role">
                    <div>
                        <label class="text-slate-600">User</label>
                        <select id="roleUserSelect" name="role_user_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select user</option>
                            <?php foreach ($modalUsers as $user): ?>
                                <?php
                                $firstName = (string)($user['people']['first_name'] ?? '');
                                $surname = (string)($user['people']['surname'] ?? '');
                                $displayName = trim($firstName . ' ' . $surname);
                                if ($displayName === '') {
                                    $displayName = (string)($user['email'] ?? 'Unknown User');
                                }
                                $userOfficeId = (string)($userOfficeMap[(string)($user['id'] ?? '')] ?? '');
                                $isAdminUser = isset($activeAdminUserIdSet[strtolower(trim((string)($user['id'] ?? '')))]);
                                ?>
                                <option value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-office-id="<?= htmlspecialchars($userOfficeId, ENT_QUOTES, 'UTF-8') ?>" data-is-admin="<?= $isAdminUser ? '1' : '0' ?>"><?= htmlspecialchars($displayName . ' (' . (string)($user['email'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Role</label>
                        <select id="roleSelect" name="role_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" data-active-admin-count="<?= htmlspecialchars((string)$activeAdminCount, ENT_QUOTES, 'UTF-8') ?>" data-active-admin-max="<?= htmlspecialchars((string)$activeAdminLimit, ENT_QUOTES, 'UTF-8') ?>" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars((string)($role['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-role-key="<?= htmlspecialchars(strtolower((string)($role['role_key'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($role['role_name'] ?? $role['role_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p id="roleAdminGuardHint" class="mt-1 text-xs text-slate-500">Assigning Admin is allowed only while there are fewer than <?= htmlspecialchars((string)$activeAdminLimit, ENT_QUOTES, 'UTF-8') ?> active admin-role users.</p>
                    </div>
                    <div>
                        <label class="text-slate-600">Division</label>
                        <select id="roleOfficeSelect" name="role_office_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            <option value="">Select division</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?= htmlspecialchars((string)($office['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($office['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Effectivity Date</label>
                        <input type="date" name="effectivity_date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-3 mt-2">
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
                <form id="credentialForm" action="user-management.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" data-confirm-title="Reset this user's password?" data-confirm-text="This will issue a temporary password and notify the user with change-password instructions." data-confirm-button-text="Reset password">
                    <input type="hidden" name="form_action" value="credential">
                    <div>
                        <label class="text-slate-600">User</label>
                        <select id="credentialUserSelect" name="credential_user_id" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="">Select user</option>
                            <?php foreach ($modalUsers as $user): ?>
                                <?php
                                $firstName = (string)($user['people']['first_name'] ?? '');
                                $surname = (string)($user['people']['surname'] ?? '');
                                $displayName = trim($firstName . ' ' . $surname);
                                if ($displayName === '') {
                                    $displayName = (string)($user['email'] ?? 'Unknown User');
                                }
                                $isAdminUser = isset($activeAdminUserIdSet[strtolower(trim((string)($user['id'] ?? '')))]);
                                ?>
                                <option value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-is-admin="<?= $isAdminUser ? '1' : '0' ?>"><?= htmlspecialchars($displayName . ' (' . (string)($user['email'] ?? '') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Credential Action</label>
                        <select id="credentialActionSelect" name="credential_action" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="reset_password">Reset Password</option>
                            <option value="unlock_account">Unlock Account</option>
                            <option value="disable_login">Disable Login Access</option>
                        </select>
                        <p id="credentialActionHelp" class="mt-1 text-xs text-slate-500">Reset Password is limited to Employee and Staff accounts. The temporary password is emailed with change-password instructions.</p>
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
    <?php
};

if ($userManagementPartial === 'reference') {
    $renderReferenceSection();
    return;
}

if ($userManagementPartial === 'modals') {
    $renderModalPartials();
    return;
}
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

<?php $renderSummaryCards(); ?>
<?php $renderQuickCreateForm(); ?>
<?php $renderNewHireQueue(); ?>
<?php $renderUsersQueue(); ?>

<section id="adminUserManagementAsyncRegion" data-admin-user-async-region data-base-url="user-management.php" class="bg-white border border-slate-200 rounded-2xl mb-6 overflow-hidden">
    <header class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Deferred Reference Workspace</h2>
            <p class="text-sm text-slate-500 mt-1">Reference lists and supporting setup tables no longer block the first paint of user queues.</p>
        </div>
        <button type="button" data-user-async-trigger="reference" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
            <span class="material-symbols-outlined text-[18px]">view_list</span>
            Load Reference Lists
        </button>
    </header>

    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/80 text-sm text-slate-600">
        Use this workspace only when you need the positions, roles, or divisions tables. Role, credential, archive, and delete flows are also lazy-loaded when opened.
    </div>

    <div data-user-async-loading class="hidden px-6 py-10">
        <div class="animate-pulse space-y-3">
            <div class="h-4 w-48 rounded bg-slate-200"></div>
            <div class="h-24 rounded-xl bg-slate-100"></div>
            <div class="h-24 rounded-xl bg-slate-100"></div>
        </div>
    </div>

    <div data-user-async-error class="hidden px-6 py-10 text-center">
        <p class="text-sm text-rose-600">Unable to load the user-management reference workspace.</p>
        <button type="button" data-user-async-retry class="mt-3 inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
            <span class="material-symbols-outlined text-[18px]">refresh</span>
            Retry
        </button>
    </div>

    <div data-user-async-empty class="px-6 py-10 text-center text-sm text-slate-500">
        Load the reference workspace only when you need supporting lists and setup tables.
    </div>

    <div data-user-async-content class="hidden px-6 py-6"></div>
</section>

<div id="adminUserManagementModalHost" data-base-url="user-management.php"></div>