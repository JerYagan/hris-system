<?php
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

$pageTitle = 'My Profile | Staff';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
    <p class="text-sm text-gray-500">View and manage your staff account information.</p>
</div>

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
            <p class="font-medium text-gray-800">Staff User</p>
        </div>
        <div>
            <p class="text-gray-500">Email Address</p>
            <p class="font-medium text-gray-800">staff.user@da.gov.ph</p>
        </div>
        <div>
            <p class="text-gray-500">Role</p>
            <p class="font-medium text-gray-800">HR Staff</p>
        </div>
        <div>
            <p class="text-gray-500">Contact Number</p>
            <p class="font-medium text-gray-800">09XXXXXXXXX</p>
        </div>
        <div>
            <p class="text-gray-500">Office</p>
            <p class="font-medium text-gray-800">Human Resources Office</p>
        </div>
        <div>
            <p class="text-gray-500">Account Status</p>
            <p class="font-medium text-green-700">Active</p>
        </div>
    </div>
</section>

<?php else: ?>
<form action="#" method="POST" class="space-y-6">
    <section class="bg-white border rounded-xl">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Edit Profile Information</h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <label class="text-gray-500">Full Name</label>
                <input type="text" value="Staff User" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Email Address</label>
                <input type="email" value="staff.user@da.gov.ph" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Contact Number</label>
                <input type="text" value="09XXXXXXXXX" class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="text-gray-500">Office</label>
                <input type="text" value="Human Resources Office" class="w-full mt-1 border rounded-md px-3 py-2">
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

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
