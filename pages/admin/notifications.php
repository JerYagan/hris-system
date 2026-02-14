<?php
$pageTitle = 'Notifications | Admin';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];

ob_start();
?>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Admin Notifications</h1>
        <p class="text-sm text-gray-500 mt-1">Recent system and module updates for the admin role.</p>
    </header>

    <div class="divide-y text-sm">
        <article class="p-5 bg-green-50">
            <h2 class="font-medium text-gray-800">New leave requests need approval</h2>
            <p class="text-gray-600 mt-1">There are pending requests awaiting admin review.</p>
        </article>
        <article class="p-5">
            <h2 class="font-medium text-gray-800">Payroll run is ready for checking</h2>
            <p class="text-gray-600 mt-1">Current payroll cycle is available in payroll management.</p>
        </article>
        <article class="p-5">
            <h2 class="font-medium text-gray-800">User access request submitted</h2>
            <p class="text-gray-600 mt-1">A new staff account request is pending in user management.</p>
        </article>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
