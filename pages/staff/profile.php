<?php
require_once __DIR__ . '/includes/profile/bootstrap.php';
require_once __DIR__ . '/includes/profile/actions.php';
require_once __DIR__ . '/includes/profile/data.php';

$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$pageTitle = 'My Profile | Staff';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
    <p class="text-sm text-gray-500">View and manage your staff account information.</p>
</div>

<?php if ($state && $message): ?>
<div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if ($dataLoadError): ?>
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if (!$editMode): ?>
<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Profile Information</h2>

        <div class="flex items-center gap-2">
            <button type="button" data-modal-open="staffPasswordRequestModal" class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined text-sm">password</span>
                Change Password
            </button>
            <a href="profile.php?edit=true" class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md border border-green-700 text-green-700 hover:bg-green-50">
                <span class="material-symbols-outlined text-sm">edit</span>
                Edit Profile
            </a>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 gap-6 lg:grid-cols-2 text-sm">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start gap-4">
                <?php if (!empty($profileSummary['resolved_profile_photo_url'])): ?>
                    <img src="<?= htmlspecialchars((string)$profileSummary['resolved_profile_photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Staff profile photo" class="h-24 w-24 rounded-full object-cover border border-slate-200">
                <?php else: ?>
                    <div class="h-24 w-24 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-2xl font-semibold">
                        <?= htmlspecialchars(strtoupper(substr((string)($profileSummary['first_name'] ?? 'S'), 0, 1) . substr((string)($profileSummary['surname'] ?? 'T'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Full Name</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800"><?= htmlspecialchars((string)($profileSummary['display_name'] ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mt-3 text-xs uppercase tracking-wide text-gray-500">Email Address</p>
                    <p class="mt-1 break-all font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-5" id="staffProfilePhotoForm">
                <input type="hidden" name="form_action" value="upload_profile_photo">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input id="staffProfilePhotoInput" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" required>

                <button type="button" data-trigger-file="staffProfilePhotoInput" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-[18px]">upload</span>
                    Select and Upload Photo
                </button>
                <p id="staffProfilePhotoFilename" class="mt-2 text-xs text-slate-500">No file selected.</p>
                <p class="mt-1 text-xs text-gray-500">Accepted: JPG, PNG, WEBP (max 3MB).</p>
            </form>
        </article>

        <article class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Staff Information</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <p class="text-gray-500">Role</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['role_name'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Contact Number</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['mobile_no'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Office</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['office_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Account Status</p>
                    <p class="font-medium <?= htmlspecialchars((string)($profileSummary['account_status_class'] ?? 'text-gray-700'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($profileSummary['account_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Last Login</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['last_login'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Member Since</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['member_since'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-gray-800">Login Activity</h2>
        <p class="text-sm text-slate-500 mt-1">Recent authentication events on your account.</p>
    </header>

    <form method="GET" action="profile.php" class="px-6 pb-3 pt-4 grid grid-cols-1 gap-3 md:grid-cols-4 md:items-end md:gap-4">
        <div class="w-full">
            <label class="text-sm text-slate-600" for="staffLoginSearch">Search Activity</label>
            <input id="staffLoginSearch" name="login_search" value="<?= htmlspecialchars((string)$loginSearchQuery, ENT_QUOTES, 'UTF-8') ?>" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by event, provider, IP, or device">
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="staffLoginEventFilter">Event Type</label>
            <select id="staffLoginEventFilter" name="login_event" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Events</option>
                <?php foreach ((array)$loginEventOptions as $eventOption): ?>
                    <option value="<?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginEventFilter === (string)$eventOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$eventOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full">
            <label class="text-sm text-slate-600" for="staffLoginDeviceFilter">Device</label>
            <select id="staffLoginDeviceFilter" name="login_device" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Devices</option>
                <?php foreach ((array)$loginDeviceOptions as $deviceOption): ?>
                    <option value="<?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$loginDeviceFilter === (string)$deviceOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$deviceOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 md:justify-end">
            <button type="submit" class="mt-6 rounded-md bg-green-700 px-4 py-2 text-sm text-white hover:bg-green-800">Apply</button>
            <a href="profile.php" class="mt-6 rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
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
                        <tr>
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
                    <?php $baseQuery = ['login_search' => (string)$loginSearchQuery, 'login_event' => (string)$loginEventFilter, 'login_device' => (string)$loginDeviceFilter]; ?>
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

<div id="staffPhotoPreviewModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-close-photo-preview="staffPhotoPreviewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-800">Profile Photo Preview</h3>
                <button type="button" data-close-photo-preview="staffPhotoPreviewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-5">
                <img id="staffProfilePhotoPreviewImage" src="" alt="Selected profile preview" class="hidden h-64 w-full rounded-lg border border-slate-200 object-contain">
                <p id="staffProfilePhotoPreviewEmpty" class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Choose a file first to preview it.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close-photo-preview="staffPhotoPreviewModal" class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" id="staffConfirmPhotoUpload" class="rounded-md bg-green-700 px-4 py-2 text-sm text-white hover:bg-green-800">Confirm and Upload</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="staffPasswordRequestModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="staffPasswordRequestModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Change Password (Email Verification)</h3>
                <button type="button" data-modal-close="staffPasswordRequestModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm" id="staffPasswordRequestForm">
                <input type="hidden" name="form_action" value="request_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div>
                    <label class="text-slate-600">Current Password</label>
                    <input type="password" name="current_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>

                <div>
                    <label class="text-slate-600">New Password</label>
                    <input type="password" id="staffNewPasswordInput" name="new_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                    <div class="mt-2">
                        <div class="h-2 w-full rounded-full bg-slate-200">
                            <div id="staffPasswordStrengthBar" class="h-2 w-0 rounded-full bg-slate-300 transition-all duration-150"></div>
                        </div>
                        <p id="staffPasswordStrengthText" class="mt-1 text-xs text-slate-500">Strength: Enter a new password</p>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
                </div>

                <div>
                    <label class="text-slate-600">Confirm New Password</label>
                    <input type="password" name="confirm_new_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>

                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    After sending the verification code, you will immediately proceed to the code verification modal.
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="staffPasswordRequestModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Send Verification Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="staffPasswordVerifyModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="staffPasswordVerifyModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Verify Email Code</h3>
                <button type="button" data-modal-close="staffPasswordVerifyModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>

            <form action="profile.php" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm">
                <input type="hidden" name="form_action" value="confirm_password_change_code">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

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
                    <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Verify and Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<form action="profile.php?edit=true" method="POST" class="space-y-6" id="staffProfileForm">
    <input type="hidden" name="form_action" value="update_staff_profile">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <section class="bg-white border rounded-xl">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Edit Profile Information</h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <label class="text-gray-500">First Name</label>
                <input name="first_name" type="text" value="<?= htmlspecialchars((string)($profileSummary['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2" required>
            </div>
            <div>
                <label class="text-gray-500">Middle Name</label>
                <input name="middle_name" type="text" value="<?= htmlspecialchars((string)($profileSummary['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Surname</label>
                <input name="surname" type="text" value="<?= htmlspecialchars((string)($profileSummary['surname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2" required>
            </div>
            <div>
                <label class="text-gray-500">Name Extension</label>
                <input name="name_extension" type="text" value="<?= htmlspecialchars((string)($profileSummary['name_extension'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Username</label>
                <input name="username" type="text" value="<?= htmlspecialchars((string)($profileSummary['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Email Address</label>
                <input name="personal_email" type="email" value="<?= htmlspecialchars((string)($profileSummary['personal_email'] ?: ($profileSummary['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Contact Number</label>
                <input name="mobile_no" type="text" value="<?= htmlspecialchars((string)($profileSummary['mobile_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Office</label>
                <input type="text" value="<?= htmlspecialchars((string)($profileSummary['office_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-50" disabled>
            </div>
        </div>
    </section>

    <section class="bg-white border rounded-xl">
        <div class="p-6 flex justify-end gap-3">
            <a href="profile.php" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">save</span>
                Save Changes
            </button>
        </div>
    </section>
</form>
<?php endif; ?>

<script src="../../assets/js/staff/profile/index.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-modal-open');
            if (!targetId) return;
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-modal-close');
            if (!targetId) return;
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    const profilePhotoInput = document.getElementById('staffProfilePhotoInput');
    const profilePhotoFilename = document.getElementById('staffProfilePhotoFilename');
    const photoForm = document.getElementById('staffProfilePhotoForm');
    const photoPreviewImage = document.getElementById('staffProfilePhotoPreviewImage');
    const photoPreviewEmpty = document.getElementById('staffProfilePhotoPreviewEmpty');
    const photoPreviewModal = document.getElementById('staffPhotoPreviewModal');
    const confirmPhotoUploadButton = document.getElementById('staffConfirmPhotoUpload');
    document.querySelectorAll('[data-trigger-file="staffProfilePhotoInput"]').forEach((button) => {
        button.addEventListener('click', () => profilePhotoInput?.click());
    });
    if (profilePhotoInput && profilePhotoFilename) {
        profilePhotoInput.addEventListener('change', () => {
            const file = profilePhotoInput.files && profilePhotoInput.files[0] ? profilePhotoInput.files[0] : null;
            profilePhotoFilename.textContent = file ? file.name : 'No file selected.';

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
        if (!photoForm || !profilePhotoInput || !(profilePhotoInput.files && profilePhotoInput.files[0])) {
            return;
        }
        photoForm.submit();
    });

    document.querySelectorAll('[data-close-photo-preview="staffPhotoPreviewModal"]').forEach((button) => {
        button.addEventListener('click', () => {
            if (photoPreviewModal) {
                photoPreviewModal.classList.add('hidden');
            }
        });
    });

    const passwordInput = document.getElementById('staffNewPasswordInput');
    const strengthBar = document.getElementById('staffPasswordStrengthBar');
    const strengthText = document.getElementById('staffPasswordStrengthText');

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
        });
    }

    const requestModal = document.getElementById('staffPasswordRequestModal');
    const verifyModal = document.getElementById('staffPasswordVerifyModal');
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

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
