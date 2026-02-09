<?php
// pages/applicant/includes/layout.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main content area -->
    <div class="flex flex-col flex-1">

        <!-- Top Navigation -->
        <?php include __DIR__ . '/topnav.php'; ?>

        <!-- Page Content -->
        <main class="flex-1 p-6">
            <?php
                /**
                 * Page content injector
                 * Each applicant page defines $content
                 */
                echo $content ?? '';
            ?>
        </main>

    </div>
</div>

<script src="../../../assets/js/applicant/script.js"></script>
<script src="../../../assets/js/applicant/alert.js"></script>
</body>
</html>
