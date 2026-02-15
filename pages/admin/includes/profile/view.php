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
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div><p class="text-slate-500">Full Name</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['display_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Role</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['role_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Official Email</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['email'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Personal Email</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['personal_email'] !== '' ? $profileSummary['personal_email'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Mobile Number</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['mobile_no'] !== '' ? $profileSummary['mobile_no'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Username</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)($profileSummary['username'] !== '' ? $profileSummary['username'] : 'Not set'), ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Office Scope</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['office_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Account Status</p><p class="font-medium"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$profileSummary['account_status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$profileSummary['account_status'], ENT_QUOTES, 'UTF-8') ?></span></p></div>
        <div><p class="text-slate-500">Last Login</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['last_login_at'], ENT_QUOTES, 'UTF-8') ?></p></div>
        <div><p class="text-slate-500">Member Since</p><p class="font-medium text-slate-800"><?= htmlspecialchars((string)$profileSummary['member_since'], ENT_QUOTES, 'UTF-8') ?></p></div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Login Activity</h2>
        <p class="text-sm text-slate-500 mt-1">Track recent authentication events for your account.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="profileLoginSearch">Search Activity</label>
            <input id="profileLoginSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by event, provider, IP, or user agent">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600" for="profileLoginEventFilter">Event Type</label>
            <select id="profileLoginEventFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Events</option>
                <option value="Login Success">Login Success</option>
                <option value="Login Failed">Login Failed</option>
                <option value="Logout">Logout</option>
                <option value="Password Reset">Password Reset</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="profileLoginHistoryTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">IP Address</th>
                    <th class="text-left px-4 py-3">User Agent</th>
                    <th class="text-left px-4 py-3">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($loginHistoryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="5">No login activity available.</td></tr>
                <?php else: ?>
                    <?php foreach ($loginHistoryRows as $row): ?>
                        <tr data-profile-login-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-profile-login-event="<?= htmlspecialchars((string)$row['event_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['event_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['auth_provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['user_agent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="profileDetailsModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="profileDetailsModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-800">Edit Profile Details</h3><button type="button" data-modal-close="profileDetailsModal" class="text-slate-500 hover:text-slate-700">✕</button></div>
            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_profile_details">
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
                <div><label class="text-slate-600">Username</label><input type="text" name="username" value="<?= htmlspecialchars((string)$profileSummary['username'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Set a preferred username"></div>
                <div><label class="text-slate-600">Mobile Number</label><input type="text" name="mobile_no" value="<?= htmlspecialchars((string)$profileSummary['mobile_no'], ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Password changes remain under your authentication settings flow.</div>
                <div class="flex justify-end gap-3 mt-2"><button type="button" data-modal-close="profilePreferencesModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Preferences</button></div>
            </form>
        </div>
    </div>
</div>
