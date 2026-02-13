<?php
// Toggle edit mode (UI only for now)
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

$pageTitle = 'Profile | DA HRIS';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        account_circle
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Account Profile
        </h1>
        <p class="text-sm text-gray-500">
            View and manage your personal account information.
        </p>
    </div>
</div>

<?php if (!$editMode): ?>
<!-- ================= VIEW PROFILE ================= -->
<section class="bg-white border rounded-lg mb-8">

    <header class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">
            Personal Information
        </h2>

        <a href="profile.php?edit=true"
           class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md border border-green-700 text-green-700 hover:bg-green-50">
            <span class="material-symbols-outlined text-sm">
                edit
            </span>
            Update Information
        </a>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

        <div>
            <p class="text-gray-500">Full Name</p>
            <p class="font-medium text-gray-800">
                Juan Dela Cruz
            </p>
        </div>

        <div>
            <p class="text-gray-500">Email Address</p>
            <p class="font-medium text-gray-800">
                juan.delacruz@email.com
            </p>
        </div>

        <div>
            <p class="text-gray-500">Contact Number</p>
            <p class="font-medium text-gray-800">
                09XXXXXXXXX
            </p>
        </div>

        <div>
            <p class="text-gray-500">Address</p>
            <p class="font-medium text-gray-800">
                Quezon City, Philippines
            </p>
        </div>

    </div>

</section>

<?php else: ?>
<!-- ================= EDIT PROFILE ================= -->
<form action="#" method="POST" class="space-y-6">

    <section class="bg-white border rounded-lg">

        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Update Personal Information
            </h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

            <div>
                <label class="text-gray-500">Full Name</label>
                <input type="text"
                       value="Juan Dela Cruz"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Email Address</label>
                <input type="email"
                       value="juan.delacruz@email.com"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Contact Number</label>
                <input type="text"
                       value="09XXXXXXXXX"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Address</label>
                <input type="text"
                       value="Quezon City, Philippines"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

        </div>

    </section>

    <!-- CONFIRMATION -->
    <section class="bg-white border rounded-lg">
        <div class="p-6 text-sm">
            <p class="text-gray-600 mb-3">
                Please review your changes carefully before submitting.
            </p>

            <div class="flex justify-end gap-3">
                <a href="profile.php"
                   class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">
                        save
                    </span>
                    Save Changes
                </button>
            </div>
        </div>
    </section>

</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
