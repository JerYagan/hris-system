<?php
$pageTitle = $pageTitle ?? 'Admin | DA HRIS';
$activePage = $activePage ?? '';
$breadcrumbs = $breadcrumbs ?? ['Dashboard'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="admin-shell text-gray-800">

<div class="flex min-h-screen">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="sidebarOverlay" class="fixed inset-0 bg-gray-900/55 opacity-0 pointer-events-none transition-opacity duration-200 z-30"></div>

    <div id="mainContent" class="flex flex-col flex-1 transition-all duration-200 ease-in-out">
        <?php include __DIR__ . '/topnav.php'; ?>

        <main class="flex-1 p-6">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="./js/script.js"></script>
</body>
</html>
