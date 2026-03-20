<?php
include __DIR__ . '/auth-guard.php';

$shellContext = systemShellContext($pageTitle ?? null, 'Admin | DA HRIS', $activePage ?? '', $breadcrumbs ?? [], ['Dashboard']);
$pageTitle = $shellContext['page_title'];
$activePage = $shellContext['active_page'];
$breadcrumbs = $shellContext['breadcrumbs'];
$pageSlug = $shellContext['page_slug'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="admin-shell text-gray-800" data-role="admin" data-page="<?= htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') ?>">

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

<script type="module" src="<?= htmlspecialchars(systemAppPath('/assets/js/bootstrap.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script type="module" src="<?= htmlspecialchars((string)$pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endforeach; ?>
<?= systemRenderQaPerfConsoleScript() ?>
</body>
</html>
