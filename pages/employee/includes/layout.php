<?php
  require_once __DIR__ . '/../../shared/lib/system-helpers.php';
  include __DIR__ . '/auth-guard.php';
  require_once __DIR__ . '/lib/employee-backend.php';

  $shellContext = systemShellContext($pageTitle ?? null, 'DA HRIS', $activePage ?? '', $breadcrumbs ?? [], []);
  $pageTitle = $shellContext['page_title'];
  $activePage = $shellContext['active_page'];
  $breadcrumbs = $shellContext['breadcrumbs'];

  $employeeTopnavDisplayName = 'Employee';
  $employeeTopnavRoleLabel = 'Employee';
  $employeeUnreadNotificationCount = 0;
  $employeeUnreadNotificationBadge = '0';
  $employeeTopnavNotificationsPreview = [];
  $employeeTopnavCsrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
  $employeeTopnavPhotoUrl = null;
  $employeeTopnavInitials = 'EM';

  if (function_exists('employeeBackendContext')) {
    $topnavBackend = employeeBackendContext();
    $topnavSupabaseUrl = (string)($topnavBackend['supabase_url'] ?? '');
    $topnavEmployeeUserId = (string)($topnavBackend['employee_user_id'] ?? '');
    $topnavHeaders = (array)($topnavBackend['headers'] ?? []);

    $cacheTtlSeconds = 45;
    $topnavCache = (array)($_SESSION['employee_topnav_cache'] ?? []);
    $cacheIsFresh = systemTopnavCacheIsFresh($topnavCache, $topnavEmployeeUserId, $cacheTtlSeconds);

    if ($cacheIsFresh) {
      $employeeTopnavDisplayName = (string)($topnavCache['display_name'] ?? $employeeTopnavDisplayName);
      $employeeTopnavRoleLabel = (string)($topnavCache['display_role'] ?? $topnavCache['role_label'] ?? $employeeTopnavRoleLabel);
      $employeeTopnavPhotoUrl = systemTopnavResolveProfilePhotoUrl((string)($topnavCache['profile_photo_url'] ?? ''));
      $employeeUnreadNotificationCount = max(0, (int)($topnavCache['unread_count'] ?? 0));
      $employeeTopnavNotificationsPreview = (array)($topnavCache['notifications_preview'] ?? []);
    }

    if (!$cacheIsFresh && $topnavSupabaseUrl !== '' && $topnavEmployeeUserId !== '' && !empty($topnavHeaders) && function_exists('apiRequest') && function_exists('isSuccessful')) {
      $profileData = systemTopnavFetchPeopleProfile($topnavSupabaseUrl, $topnavHeaders, $topnavEmployeeUserId);
      $profilePhotoPath = (string)($profileData['profile_photo_path'] ?? '');
      if ((string)($profileData['display_name'] ?? '') !== '') {
        $employeeTopnavDisplayName = (string)$profileData['display_name'];
      }

      $employeeNotificationSince = '';
      $roleAssignedResponse = apiRequest(
        'GET',
        rtrim($topnavSupabaseUrl, '/')
          . '/rest/v1/user_role_assignments?select=assigned_at,roles!inner(role_key)'
          . '&user_id=eq.' . rawurlencode($topnavEmployeeUserId)
          . '&roles.role_key=eq.employee'
          . '&order=is_primary.desc&limit=1',
        $topnavHeaders
      );
      if (isSuccessful($roleAssignedResponse) && !empty((array)($roleAssignedResponse['data'] ?? []))) {
        $roleAssignedRow = (array)$roleAssignedResponse['data'][0];
        $employeeNotificationSince = trim((string)($roleAssignedRow['assigned_at'] ?? ''));
      }

      $notificationFilters = ['category=not.in.(application,recruitment)'];
      if ($employeeNotificationSince !== '') {
        $notificationFilters[] = 'created_at=gte.' . rawurlencode($employeeNotificationSince);
      }

      $notificationSummary = systemTopnavFetchNotificationSummary(
        $topnavSupabaseUrl,
        $topnavHeaders,
        $topnavEmployeeUserId,
        [
          'unread_filters' => $notificationFilters,
          'preview_filters' => $notificationFilters,
        ]
      );
      $employeeUnreadNotificationCount = (int)($notificationSummary['unread_count'] ?? 0);
      $employeeTopnavNotificationsPreview = (array)($notificationSummary['notifications_preview'] ?? []);

      $employeeTopnavPhotoUrl = systemTopnavResolveProfilePhotoUrl($profilePhotoPath);
      $_SESSION['employee_topnav_cache'] = systemTopnavCachePayload(
        $topnavEmployeeUserId,
        $employeeTopnavDisplayName,
        $employeeTopnavRoleLabel,
        $profilePhotoPath,
        $employeeUnreadNotificationCount,
        $employeeTopnavNotificationsPreview,
        ['role_label' => $employeeTopnavRoleLabel]
      );
    }
  }

  $employeeTopnavInitials = systemTopnavBuildInitials($employeeTopnavDisplayName, 'EM');

  $employeeUnreadNotificationBadge = $employeeUnreadNotificationCount > 99
    ? '99+'
    : (string)$employeeUnreadNotificationCount;
?>

<?php include __DIR__ . '/head.php'; ?>

<div class="flex min-h-screen relative">

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/sidebar.php'; ?>
  <button id="sidebarBackdrop" type="button" class="fixed inset-0 z-40 hidden bg-slate-950/55" aria-label="Close sidebar overlay"></button>

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
  <script src="<?= htmlspecialchars(systemAppPath('/assets/js/script.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script type="module" src="<?= htmlspecialchars((string)$pageScript, ENT_QUOTES, 'UTF-8') ?>" defer></script>
  <?php endforeach; ?>
  <?= systemRenderQaPerfConsoleScript() ?>
</body>
</html>
