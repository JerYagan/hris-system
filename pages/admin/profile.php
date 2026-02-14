<?php
$pageTitle = 'My Profile | Admin';
$activePage = 'profile.php';
$breadcrumbs = ['My Profile'];

ob_start();
?>

<section class="bg-white border rounded-xl p-6">
    <h1 class="text-xl font-semibold text-gray-800">My Profile</h1>
    <p class="text-sm text-gray-500 mt-2">Placeholder page for admin profile details and account preferences.</p>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
