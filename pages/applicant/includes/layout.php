<?php
// pages/applicant/includes/layout.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
</head>
<body class="bg-gray-100 text-gray-800">

<div id="mainContent" class="min-h-screen">
    <?php include __DIR__ . '/topnav.php'; ?>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <?php
            /**
             * Page content injector
             * Each applicant page defines $content
             */
            echo $content ?? '';
        ?>
    </main>
</div>

<script src="/hris-system/assets/js/script.js"></script>
<script src="/hris-system/assets/js/alert.js"></script>
</body>
</html>
