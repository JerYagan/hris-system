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

        <a href="profile.php?edit=true" class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md border border-green-700 text-green-700 hover:bg-green-50">
            <span class="material-symbols-outlined text-sm">edit</span>
            Edit Profile
        </a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div>
            <p class="text-gray-500">Full Name</p>
            <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['display_name'] ?? 'Staff User'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div>
            <p class="text-gray-500">Email Address</p>
            <p class="font-medium text-gray-800"><?= htmlspecialchars((string)($profileSummary['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
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
</section>

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

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
