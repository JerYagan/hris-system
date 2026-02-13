<?php
$pageTitle = 'Notifications | Staff';
$activePage = 'notifications.php';
$breadcrumbs = ['Notifications'];

ob_start();
?>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Staff Notifications</h1>
        <p class="text-sm text-gray-500 mt-1">Recent updates for recruitment, records, attendance, and payroll tasks.</p>
    </header>

    <div class="divide-y text-sm">
        <article class="p-5 bg-green-50">
            <h2 class="font-medium text-gray-800">New application requires evaluation</h2>
            <p class="text-gray-600 mt-1">Applicant for Administrative Aide was forwarded for staff review.</p>
        </article>
        <article class="p-5">
            <h2 class="font-medium text-gray-800">Document verification pending</h2>
            <p class="text-gray-600 mt-1">5 employee credentials require signature validation.</p>
        </article>
        <article class="p-5">
            <h2 class="font-medium text-gray-800">Payroll export completed</h2>
            <p class="text-gray-600 mt-1">February payroll report is ready in export history.</p>
        </article>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
