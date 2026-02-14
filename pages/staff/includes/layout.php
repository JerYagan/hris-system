<?php
include __DIR__ . '/auth-guard.php';

$pageTitle = $pageTitle ?? 'Staff | DA HRIS';
$activePage = $activePage ?? '';
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div id="mainContent" class="flex flex-col flex-1 transition-all duration-200 ease-in-out">
        <?php include __DIR__ . '/topnav.php'; ?>

        <main class="flex-1 p-6">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="../../assets/js/script.js"></script>
<script src="../../assets/js/alert.js"></script>
</body>
</html>
