<?php
  include __DIR__ . '/auth-guard.php';
  require_once __DIR__ . '/lib/employee-backend.php';

  // Defaults (safe for all pages)
  $pageTitle   = $pageTitle   ?? 'DA HRIS';
  $activePage  = $activePage  ?? '';
  $breadcrumbs = $breadcrumbs ?? [];

  $employeeTopnavDisplayName = 'Employee';
  $employeeTopnavRoleLabel = 'Employee';
  $employeeUnreadNotificationCount = 0;
  $employeeUnreadNotificationBadge = '0';
  $employeeTopnavPhotoUrl = null;
  $employeeTopnavInitials = 'EM';

  $resolveProfilePhotoUrl = static function (?string $rawPath): ?string {
    $path = trim((string)$rawPath);
    if ($path === '') {
      return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
      return $path;
    }

    if (str_starts_with($path, '/')) {
      return $path;
    }

    return '/hris-system/storage/document/' . ltrim($path, '/');
  };

  if (function_exists('employeeBackendContext')) {
    $topnavBackend = employeeBackendContext();
    $topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? '');
    $topnavEmployeeUserId = (string)($topnavBackend['employee_user_id'] ?? '');
    $topnavHeaders = (array)($topnavBackend['headers'] ?? []);

    $cacheTtlSeconds = 45;
    $topnavCache = (array)($_SESSION['employee_topnav_cache'] ?? []);
    $cacheUserId = (string)($topnavCache['user_id'] ?? '');
    $cacheTimestamp = (int)($topnavCache['cached_at'] ?? 0);
    $cacheIsFresh = $cacheUserId !== ''
      && $cacheUserId === $topnavEmployeeUserId
      && $cacheTimestamp > 0
      && (time() - $cacheTimestamp) <= $cacheTtlSeconds;

    if ($cacheIsFresh) {
      $employeeTopnavDisplayName = (string)($topnavCache['display_name'] ?? $employeeTopnavDisplayName);
      $employeeTopnavPhotoUrl = $resolveProfilePhotoUrl((string)($topnavCache['profile_photo_url'] ?? ''));
      $employeeUnreadNotificationCount = max(0, (int)($topnavCache['unread_count'] ?? 0));
    }

    if (!$cacheIsFresh && $topnavSupabaseUrl !== '' && $topnavEmployeeUserId !== '' && !empty($topnavHeaders) && function_exists('apiRequest') && function_exists('isSuccessful')) {
      $peopleResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/people?select=first_name,surname,profile_photo_url&user_id=eq.' . rawurlencode($topnavEmployeeUserId) . '&limit=1',
        $topnavHeaders
      );

      $profilePhotoPath = null;
      if (isSuccessful($peopleResponse) && !empty((array)($peopleResponse['data'] ?? []))) {
        $peopleRow = (array)$peopleResponse['data'][0];
        $firstName = trim((string)($peopleRow['first_name'] ?? ''));
        $surname = trim((string)($peopleRow['surname'] ?? ''));
        $profilePhotoPath = trim((string)($peopleRow['profile_photo_url'] ?? ''));

        $fullName = trim($firstName . ' ' . $surname);
        if ($fullName !== '') {
          $employeeTopnavDisplayName = $fullName;
        }
      }

      $unreadResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/') . '/rest/v1/notifications?select=id&recipient_user_id=eq.' . rawurlencode($topnavEmployeeUserId) . '&is_read=eq.false&limit=200',
        $topnavHeaders
      );

      if (isSuccessful($unreadResponse)) {
        $employeeUnreadNotificationCount = count((array)($unreadResponse['data'] ?? []));
      }

      $employeeTopnavPhotoUrl = $resolveProfilePhotoUrl($profilePhotoPath);
      $_SESSION['employee_topnav_cache'] = [
        'user_id' => $topnavEmployeeUserId,
        'display_name' => $employeeTopnavDisplayName,
        'profile_photo_url' => (string)($profilePhotoPath ?? ''),
        'unread_count' => $employeeUnreadNotificationCount,
        'cached_at' => time(),
      ];
    }
  }

  $nameParts = preg_split('/\s+/', trim($employeeTopnavDisplayName)) ?: [];
  $initials = '';
  foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part !== '') {
      $initials .= strtoupper(substr($part, 0, 1));
    }
  }
  if ($initials !== '') {
    $employeeTopnavInitials = $initials;
  }

  $employeeUnreadNotificationBadge = $employeeUnreadNotificationCount > 99
    ? '99+'
    : (string)$employeeUnreadNotificationCount;
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
  <?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script type="module" src="<?= htmlspecialchars((string)$pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
