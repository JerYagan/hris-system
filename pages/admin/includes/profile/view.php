<?php if ($state && $message): ?>
    <?php $alertClass = $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Profile Information</h2>
            <p class="text-sm text-slate-500 mt-1">Core account identity and role scope.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" data-modal-open="profileDetailsModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Edit Profile</button>
            <button type="button" data-modal-open="profilePreferencesModal" class="px-4 py-2 rounded-md border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Account Preferences</button>
            <button type="button" data-modal-open="profilePasswordRequestModal" class="px-4 py-2 rounded-md border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Change Password</button>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 gap-6 lg:grid-cols-2 text-sm">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start gap-4">
                <?php if (!empty($profileSummary['resolved_profile_photo_url'])): ?>
                    <img src="<?= htmlspecialchars((string)$profileSummary['resolved_profile_photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Admin profile photo" class="h-24 w-24 rounded-full border border-slate-200 object-cover">
                <?php else: ?>
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-slate-200 text-xl font-semibold text-slate-700">
                        <?= htmlspecialchars(strtoupper(substr((string)($profileSummary['first_name'] ?? 'A'), 0, 1) . substr((string)($profileSummary['surname'] ?? 'D'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <div class="min-w-0 flex-1">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Full Name</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800"><?= htmlspecialchars((string)$profileSummary['display_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mt-3 text-xs uppercase tracking-wide text-slate-500">Official Email</p>
                    <p class="mt-1 break-all font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['email'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-5" id="adminProfilePhotoForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="upload_profile_photo">
                <input id="adminProfilePhotoInput" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" required>

                <button type="button" data-trigger-file="adminProfilePhotoInput" class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                    <span class="material-symbols-outlined text-[18px]">upload</span>
                    Select and Upload Photo
                </button>
                <p id="adminProfilePhotoFilename" class="mt-2 text-xs text-slate-500">No file selected.</p>
                <p class="mt-1 text-xs text-slate-500">Persistent photo stored locally. Accepted: JPG, PNG, WEBP (max 3MB).</p>
            </form>
        </article>

        <article class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-600">Account Information</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div><p class="text-slate-500">Role</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['role_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Personal Email</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['personal_email'] !== '' ? $profileSummary['personal_email'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Mobile Number</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['mobile_no'] !== '' ? $profileSummary['mobile_no'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Username</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['username'] !== '' ? $profileSummary['username'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Division Scope</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['office_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Account Status</p><p class="font-medium"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$profileSummary['account_status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$profileSummary['account_status'], ENT_QUOTES, 'UTF-8') ?></span></p></div>
                <div><p class="text-slate-500">Last Login</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['last_login_at'], ENT_QUOTES, 'UTF-8') ?></p></div>
                <div><p class="text-slate-500">Member Since</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['member_since'], ENT_QUOTES, 'UTF-8') ?></p></div>
                <div class="md:col-span-2"><p class="text-slate-500">Verification / Recovery Method</p><p class="font-medium text-slate-800">Email verification via your active account email is required for password recovery and password changes.</p></div>
            </div>
        </article>
    </div>
</section>

<div id="adminPhotoPreviewModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-close-photo-preview="adminPhotoPreviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-800">Profile Photo Preview</h3>
                <button type="button" data-close-photo-preview="adminPhotoPreviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-5">
                <img id="adminProfilePhotoPreviewImage" src="" alt="Selected profile preview" class="hidden h-64 w-full rounded-lg border border-slate-200 object-contain">
                <p id="adminProfilePhotoPreviewEmpty" class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Choose a file first to preview it.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close-photo-preview="adminPhotoPreviewModal" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" id="adminConfirmPhotoUpload" class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Confirm and Upload</button>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Login Activity</h2>
        <p class="text-sm text-slate-500 mt-1">Track recent authentication events for your account.</p>
    </header>

    <form method="GET" action="profile.php" class="px-6 pb-3 pt-4 grid grid-cols-1 gap-3 md:grid-cols-4 md:items-end md:gap-4">
        <div class="w-full">
            <label class="text-sm text-slate-600" for="profileLoginSearch">Search Activity</label>
            <input id="profileLoginSearch" name="login_search" value="<?= htmlspecialchars((string)$loginSearchQuery, ENT_QUOTES, 'UTF-8') ?>" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by event, provider, IP, or device">
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="profileLoginEventFilter">Event Type</label>
            <select id="profileLoginEventFilter" name="login_event" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Events</option>
                <?php foreach ((array)$loginEventOptions as $eventOption): ?>
                    <option value="<?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginEventFilter === (string)$eventOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="profileLoginDeviceFilter">Device</label>
            <select id="profileLoginDeviceFilter" name="login_device" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Devices</option>
                <?php foreach ((array)$loginDeviceOptions as $deviceOption): ?>
                    <option value="<?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginDeviceFilter === (string)$deviceOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 md:justify-end">
            <button type="submit" class="mt-6 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Apply</button>
            <a href="profile.php" class="mt-6 rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="p-6 overflow-x-auto">
        <table id="profileLoginHistoryTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">IP Address</th>
                    <th class="text-left px-4 py-3">Device</th>
                    <th class="text-left px-4 py-3">User Agent</th>
                    <th class="text-left px-4 py-3">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($loginHistoryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="6">No login activity available.</td></tr>
                <?php else: ?>
                    <?php foreach ($loginHistoryRows as $row): ?>
                        <tr data-profile-login-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-profile-login-event="<?= htmlspecialchars((string)$row['event_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['event_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['auth_provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['device_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['user_agent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($loginTotalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                <p>Showing page <?= (int)($loginPage ?? 1) ?> of <?= (int)($loginTotalPages ?? 1) ?> (<?= (int)($loginHistoryTotal ?? 0) ?> total)</p>
                <div class="flex items-center gap-2">
                    <?php
                    $baseQuery = [
                        'login_search' => (string)$loginSearchQuery,
                        'login_event' => (string)$loginEventFilter,
                        'login_device' => (string)$loginDeviceFilter,
                    ];
                    ?>
                    <?php if ((int)$loginPage > 1): ?>
                        <?php $prevQuery = $baseQuery; $prevQuery['login_page'] = (int)$loginPage - 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="profile.php?<?= htmlspecialchars(http_build_query($prevQuery), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ((int)$loginPage < (int)$loginTotalPages): ?>
                        <?php $nextQuery = $baseQuery; $nextQuery['login_page'] = (int)$loginPage + 1; ?>
                        <a class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50" href="profile.php?<?= htmlspecialchars(http_build_query($nextQuery), ENT_QUOTES, 'UTF-8') ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="profileDetailsModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="profileDetailsModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Edit Profile Details</h3><button type="button" data-modal-close="profileDetailsModal" class="text-slate-500 hover:text-slate-700">✕</button></div>
            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_profile_details">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div><label class="text-slate-600">First Name</label><input type="text" name="first_name" value="<?= htmlspecialchars((string)$profileSummary['first_name'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                <div><label class="text-slate-600">Middle Name</label><input type="text" name="middle_name" value="<?= htmlspecialchars((string)$profileSummary['middle_name'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Surname</label><input type="text" name="surname" value="<?= htmlspecialchars((string)$profileSummary['surname'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
                <div><label class="text-slate-600">Name Extension</label><input type="text" name="name_extension" value="<?= htmlspecialchars((string)$profileSummary['name_extension'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Mobile Number</label><input type="text" name="mobile_no" value="<?= htmlspecialchars((string)$profileSummary['mobile_no'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Personal Email</label><input type="email" name="personal_email" value="<?= htmlspecialchars((string)$profileSummary['personal_email'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2"><button type="button" data-modal-close="profileDetailsModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button></div>
            </form>
        </div>
    </div>
</div>

<div id="profilePreferencesModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="profilePreferencesModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Update Account Preferences</h3><button type="button" data-modal-close="profilePreferencesModal" class="text-slate-500 hover:text-slate-700">✕</button></div>
            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_account_preferences">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div><label class="text-slate-600">Username</label><input type="text" name="username" value="<?= htmlspecialchars((string)$profileSummary['username'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Set a preferred username"></div>
                <div><label class="text-slate-600">Mobile Number</label><input type="text" name="mobile_no" value="<?= htmlspecialchars((string)$profileSummary['mobile_no'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Password changes use email code verification via your active account email.</div>
                <div class="flex justify-end gap-3 mt-2"><button type="button" data-modal-close="profilePreferencesModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Preferences</button></div>
            </form>
        </div>
    </div>
</div>

<div id="profilePasswordRequestModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="profilePasswordRequestModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Change Password (Email Verification)</h3>
                <button type="button" data-modal-close="profilePasswordRequestModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm" id="profilePasswordRequestForm">
                <input type="hidden" name="form_action" value="request_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div>
                    <label class="text-slate-600">Current Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="profileCurrentPasswordInput" name="current_password" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="profileCurrentPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show current password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="text-slate-600">New Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="profileNewPasswordInput" name="new_password" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="profileNewPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show new password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div id="profilePasswordStrengthBar" class="h-2 w-0 rounded-full bg-slate-300 transition-all duration-150"></div>
                        </div>
                        <p id="profilePasswordStrengthText" class="mt-1 text-xs text-slate-500">Strength: Enter a new password</p>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
                </div>

                <div>
                    <label class="text-slate-600">Confirm New Password</label>
                    <div class="relative mt-1">
                        <input type="password" id="profileConfirmPasswordInput" name="confirm_new_password" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-12" required>
                        <button type="button" data-password-toggle="profileConfirmPasswordInput" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 hover:text-slate-700" aria-label="Show confirm password">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                    </div>
                    <p id="profilePasswordMatchIndicator" class="mt-2 text-xs text-slate-500">Enter and confirm your new password.</p>
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="profilePasswordRequestModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Send Verification Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="profilePasswordVerifyModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="profilePasswordVerifyModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Verify Email Code</h3>
                <button type="button" data-modal-close="profilePasswordVerifyModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="confirm_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <?php if (!empty($passwordChangeStatus['is_pending'])): ?>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Verification code sent to <strong><?= htmlspecialchars((string)$passwordChangeStatus['email'], ENT_QUOTES, 'UTF-8') ?></strong>. Expires at <?= htmlspecialchars((string)$passwordChangeStatus['expires_at'], ENT_QUOTES, 'UTF-8') ?>.
                    </div>
                <?php else: ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        No pending verification code was found. Send a new code first.
                    </div>
                <?php endif; ?>

                <?php if (!empty($passwordChangeStatus['is_pending']) && $state && $message && $state === 'error'): ?>
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="text-slate-600">Verification Code</label>
                    <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter 6-digit code" required>
                </div>

                <div class="flex justify-between gap-3 mt-2">
                    <button type="submit" name="form_action" value="cancel_password_change_code" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel Pending Request</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Verify and Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('adminProfilePhotoInput');
    const fileNameLabel = document.getElementById('adminProfilePhotoFilename');
    const photoForm = document.getElementById('adminProfilePhotoForm');
    const photoPreviewImage = document.getElementById('adminProfilePhotoPreviewImage');
    const photoPreviewEmpty = document.getElementById('adminProfilePhotoPreviewEmpty');
    const photoPreviewModal = document.getElementById('adminPhotoPreviewModal');
    const confirmPhotoUploadButton = document.getElementById('adminConfirmPhotoUpload');

    document.querySelectorAll('[data-trigger-file="adminProfilePhotoInput"]').forEach((button) => {
        button.addEventListener('click', () => fileInput?.click());
    });

    if (fileInput && fileNameLabel) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            fileNameLabel.textContent = file ? file.name : 'No file selected.';

            if (!photoPreviewImage || !photoPreviewEmpty) {
                return;
            }

            if (!file) {
                photoPreviewImage.classList.add('hidden');
                photoPreviewImage.removeAttribute('src');
                photoPreviewEmpty.classList.remove('hidden');
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                photoPreviewImage.src = String(reader.result || '');
                photoPreviewImage.classList.remove('hidden');
                photoPreviewEmpty.classList.add('hidden');
                photoPreviewModal?.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    confirmPhotoUploadButton?.addEventListener('click', () => {
        if (!photoForm || !fileInput || !(fileInput.files && fileInput.files[0])) {
            return;
        }
        photoForm.submit();
    });

    document.querySelectorAll('[data-close-photo-preview="adminPhotoPreviewModal"]').forEach((button) => {
        button.addEventListener('click', () => {
            if (photoPreviewModal) {
                photoPreviewModal.classList.add('hidden');
            }
        });
    });

    const currentPasswordInput = document.getElementById('profileCurrentPasswordInput');
    const passwordInput = document.getElementById('profileNewPasswordInput');
    const confirmPasswordInput = document.getElementById('profileConfirmPasswordInput');
    const strengthBar = document.getElementById('profilePasswordStrengthBar');
    const strengthText = document.getElementById('profilePasswordStrengthText');
    const passwordMatchIndicator = document.getElementById('profilePasswordMatchIndicator');

    const scorePassword = (value) => {
        let score = 0;
        if (value.length >= 10) score += 1;
        if (/[A-Z]/.test(value)) score += 1;
        if (/[a-z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^a-zA-Z0-9]/.test(value)) score += 1;
        return score;
    };

    const applyStrengthUi = (score) => {
        if (!strengthBar || !strengthText) return;

        const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
        const labels = [
            'Strength: Enter a new password',
            'Strength: Very Weak',
            'Strength: Weak',
            'Strength: Fair',
            'Strength: Good',
            'Strength: Strong'
        ];
        const classes = ['bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600'];

        strengthBar.style.width = widths[score] || '0%';
        strengthBar.classList.remove('bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600');
        strengthBar.classList.add(classes[score] || 'bg-slate-300');
        strengthText.textContent = labels[score] || labels[0];
    };

    if (passwordInput) {
        applyStrengthUi(0);
        passwordInput.addEventListener('input', () => {
            const value = passwordInput.value || '';
            const score = value.length === 0 ? 0 : scorePassword(value);
            applyStrengthUi(score);
            updatePasswordMatchUi();
        });
    }

    const updatePasswordMatchUi = () => {
        if (!(passwordInput instanceof HTMLInputElement) || !(confirmPasswordInput instanceof HTMLInputElement) || !passwordMatchIndicator) {
            return;
        }

        const newPassword = passwordInput.value || '';
        const confirmPassword = confirmPasswordInput.value || '';

        passwordMatchIndicator.classList.remove('text-slate-500', 'text-emerald-700', 'text-rose-700');
        confirmPasswordInput.classList.remove('border-emerald-400', 'border-rose-400');

        if (newPassword === '' && confirmPassword === '') {
            passwordMatchIndicator.textContent = 'Enter and confirm your new password.';
            passwordMatchIndicator.classList.add('text-slate-500');
            return;
        }

        if (confirmPassword === '') {
            passwordMatchIndicator.textContent = 'Confirm your new password to check if it matches.';
            passwordMatchIndicator.classList.add('text-slate-500');
            return;
        }

        if (newPassword === confirmPassword) {
            passwordMatchIndicator.textContent = 'Passwords match.';
            passwordMatchIndicator.classList.add('text-emerald-700');
            confirmPasswordInput.classList.add('border-emerald-400');
            return;
        }

        passwordMatchIndicator.textContent = 'Passwords do not match.';
        passwordMatchIndicator.classList.add('text-rose-700');
        confirmPasswordInput.classList.add('border-rose-400');
    };

    if (confirmPasswordInput instanceof HTMLInputElement) {
        confirmPasswordInput.addEventListener('input', updatePasswordMatchUi);
        updatePasswordMatchUi();
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const inputId = button.getAttribute('data-password-toggle');
            if (!inputId) {
                return;
            }

            const input = document.getElementById(inputId);
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const nextType = input.type === 'password' ? 'text' : 'password';
            input.type = nextType;

            const icon = button.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = nextType === 'password' ? 'visibility' : 'visibility_off';
            }

            const label = nextType === 'password' ? 'Show' : 'Hide';
            button.setAttribute('aria-label', `${label} ${input.name.replaceAll('_', ' ')}`);
        });
    });

    const requestModal = document.getElementById('profilePasswordRequestModal');
    const verifyModal = document.getElementById('profilePasswordVerifyModal');
    const hasPendingCode = <?= !empty($passwordChangeStatus['is_pending']) ? 'true' : 'false' ?>;
    const state = <?= json_encode((string)($state ?? '')) ?>;
    const message = <?= json_encode((string)($message ?? '')) ?>;
    const lowerMessage = (message || '').toLowerCase();
    const shouldAutoOpenVerify = hasPendingCode && (
        (state === 'success' && lowerMessage.includes('verification code sent'))
        || state === 'error'
    );

    if (shouldAutoOpenVerify && verifyModal) {
        requestModal?.classList.add('hidden');
        verifyModal.classList.remove('hidden');
    }
});
</script>
