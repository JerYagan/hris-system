<?php
  // Defaults (safe for all pages)
  $pageTitle   = $pageTitle   ?? 'DA HRIS';
  $activePage  = $activePage  ?? '';
  $breadcrumbs = $breadcrumbs ?? [];
?>

<?php include __DIR__ . '/head.php'; ?>

<div class="flex min-h-screen relative">

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT WRAPPER -->
  <div
    id="mainContent"
    class="flex-1 flex flex-col transition-all duration-200 ease-in-out"
  >

    <!-- TOP NAV -->
    <?php include __DIR__ . '/topnav.php'; ?>

    <!-- PAGE CONTENT -->
    <main class="p-6">
      <!-- <?php include __DIR__ . '/breadcrumbs.php'; ?> -->
      <?= $content ?>
    </main>

  </div>
</div>

<!-- SIDEBAR TOGGLE SCRIPT (GLOBAL) -->
  <script src="./js/script.js"></script>
  <script src="./js/script.js"></script>
</body>
</html>
