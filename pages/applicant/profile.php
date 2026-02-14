<?php
// Toggle edit mode (UI only for now)
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

$pageTitle = 'Profile | DA HRIS';
$activePage = 'profile.php';
$breadcrumbs = $editMode ? ['Profile', 'Edit'] : ['Profile'];

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">account_circle</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Account Profile</h1>
                    <p class="mt-1 text-sm text-gray-600">View and manage your personal account information.</p>
                </div>
            </div>

            <?php if (!$editMode): ?>
                <a href="profile.php?edit=true" class="inline-flex items-center gap-1 rounded-md border border-green-700 px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                    <span class="material-symbols-outlined text-sm">edit</span>
                    Update Information
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!$editMode): ?>
<section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Full Name</p>
        <p class="mt-2 font-semibold text-gray-800">Juan Dela Cruz</p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Email Address</p>
        <p class="mt-2 font-semibold text-gray-800">juan.delacruz@email.com</p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Contact Number</p>
        <p class="mt-2 font-semibold text-gray-800">09XXXXXXXXX</p>
    </article>

    <article class="rounded-xl border bg-white p-5">
        <p class="text-xs uppercase tracking-wide text-gray-500">Address</p>
        <p class="mt-2 font-semibold text-gray-800">Quezon City, Philippines</p>
    </article>
</section>

<?php else: ?>
<form action="#" method="POST" class="space-y-6">
    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Update Personal Information</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-2">
            <div>
                <label class="text-gray-500">Full Name</label>
                <input type="text" value="Juan Dela Cruz" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Email Address</label>
                <input type="email" value="juan.delacruz@email.com" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Contact Number</label>
                <input type="text" value="09XXXXXXXXX" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Address</label>
                <input type="text" value="Quezon City, Philippines" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white p-6 text-sm">
        <p class="mb-3 text-gray-600">Please review your changes carefully before submitting.</p>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="profile.php" class="rounded-md border px-4 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
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
