<?php
require_once __DIR__ . '/includes/settings/bootstrap.php';
require_once __DIR__ . '/includes/settings/actions.php';
require_once __DIR__ . '/includes/settings/data.php';

$pageTitle   = 'Settings | DA HRIS';
$activePage  = 'settings.php';
$breadcrumbs = ['Settings'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/settings/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y g:i A', $ts);
};

$allowedTabs = ['account', 'security', 'notifications', 'privacy'];
$initialTab = strtolower((string)($_GET['tab'] ?? 'account'));
if (!in_array($initialTab, $allowedTabs, true)) {
  $initialTab = 'account';
}
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Settings</h1>
  <p class="text-sm text-gray-500">Manage your account preferences, security handoff, and notifications.</p>
</div>

<?php if (!empty($message)): ?>
  <?php $alertClass = ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'; ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $escape($alertClass) ?>" aria-live="polite">
    <?= $escape($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" aria-live="polite">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<div class="bg-white border rounded-xl overflow-hidden flex min-h-[560px]" id="employeeSettingsPage" data-initial-tab="<?= $escape($initialTab) ?>">

  <aside class="w-64 border-r bg-gray-50 p-4 shrink-0">
    <nav class="space-y-1 text-sm">

      <button
        data-tab="account"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               bg-daGreen/10 text-daGreen font-medium">
        <span class="material-icons text-base">person</span>
        Account & Preferences
      </button>

      <button
        data-tab="security"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">security</span>
        Security
      </button>

      <button
        data-tab="notifications"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">notifications</span>
        Notifications Prefs
      </button>

      <button
        data-tab="privacy"
        class="settings-tab w-full flex items-center gap-3 px-3 py-2 rounded-lg
               hover:bg-gray-100">
        <span class="material-icons text-base">privacy_tip</span>
        Privacy
      </button>

    </nav>
  </aside>

  <section class="flex-1 p-8">

    <section data-tab-content="account">
      <div class="mb-6">
        <h2 class="text-lg font-semibold">Account & Preferences</h2>
        <p class="text-sm text-gray-500">Update safe account-level contact and display preferences.</p>
      </div>

      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
        <input type="hidden" name="action" value="update_account_preferences">

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-lg border bg-gray-50">
          <div>
            <p class="text-xs text-gray-500">Employee Name</p>
            <p class="font-medium text-sm mt-1"><?= $escape($employeeDisplayName ?? 'Employee') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-500">System Email</p>
            <p class="font-medium text-sm mt-1"><?= $escape($employeeEmail ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Username</p>
            <p class="font-medium text-sm mt-1"><?= $escape($employeeUsername !== '' ? $employeeUsername : '-') ?></p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1" for="settingsMobileNo">Mobile Number</label>
          <input id="settingsMobileNo" type="text" name="mobile_no" maxlength="30" class="w-full border rounded-lg px-3 py-2 text-sm" value="<?= $escape($employeeMobileNo ?? '') ?>" placeholder="09xxxxxxxxx">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1" for="settingsPersonalEmail">Personal Email</label>
          <input id="settingsPersonalEmail" type="email" name="personal_email" maxlength="120" class="w-full border rounded-lg px-3 py-2 text-sm" value="<?= $escape($employeePersonalEmail ?? '') ?>" placeholder="name@example.com">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1" for="settingsTimezone">Timezone</label>
          <select id="settingsTimezone" name="timezone" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="Asia/Manila" <?= ($settingsPreferences['timezone'] ?? 'Asia/Manila') === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila</option>
            <option value="UTC" <?= ($settingsPreferences['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1" for="settingsDateFormat">Date Format</label>
          <select id="settingsDateFormat" name="date_format" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="M j, Y" <?= ($settingsPreferences['date_format'] ?? 'M j, Y') === 'M j, Y' ? 'selected' : '' ?>>M j, Y</option>
            <option value="Y-m-d" <?= ($settingsPreferences['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>Y-m-d</option>
            <option value="m/d/Y" <?= ($settingsPreferences['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>m/d/Y</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1" for="settingsTheme">Theme Preference</label>
          <select id="settingsTheme" name="theme" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="system" <?= ($settingsPreferences['theme'] ?? 'system') === 'system' ? 'selected' : '' ?>>System</option>
            <option value="light" <?= ($settingsPreferences['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
            <option value="dark" <?= ($settingsPreferences['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
          </select>
        </div>

        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm font-semibold">Save Account Preferences</button>
        </div>
      </form>
    </section>

    <section data-tab-content="security" class="hidden">
      <div class="mb-6">
        <h2 class="text-lg font-semibold">Security</h2>
        <p class="text-sm text-gray-500">Password reset handoff and account security status.</p>
      </div>

      <div class="space-y-5">
        <div class="rounded-lg border p-4 bg-gray-50">
          <p class="text-xs text-gray-500">Last Login</p>
          <p class="font-medium mt-1"><?= $escape($formatDateTime($employeeLastLoginAt ?? '')) ?></p>
        </div>

        <?php if (!empty($mustChangePassword)): ?>
          <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900">
            Your account is flagged to change password on next password reset/login cycle.
          </div>
        <?php endif; ?>

        <div class="rounded-lg border p-4">
          <p class="font-medium">Password Reset Handoff</p>
          <p class="text-sm text-gray-600 mt-1">To reset your password, proceed to the secure forgot-password flow.</p>
          <a href="/hris-system/pages/auth/forgot-password.php" class="inline-flex items-center gap-2 mt-3 border px-4 py-2 rounded-lg text-sm">
            <span class="material-icons text-base">lock_reset</span>
            Go to Password Reset
          </a>
        </div>
      </div>
    </section>

    <section data-tab-content="notifications" class="hidden">
      <div class="mb-6">
        <h2 class="text-lg font-semibold">Notification Preferences</h2>
        <p class="text-sm text-gray-500">Control what types of in-app notifications you receive.</p>
      </div>

      <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $escape(ensureCsrfToken()) ?>">
        <input type="hidden" name="action" value="update_notification_preferences">

        <label class="flex justify-between items-center border-b pb-3">
          <span class="text-sm">System Alerts</span>
          <input type="checkbox" name="notify_system_alerts" <?= !empty($notificationPreferences['system_alerts']) ? 'checked' : '' ?>>
        </label>

        <label class="flex justify-between items-center border-b pb-3">
          <span class="text-sm">HR Announcements</span>
          <input type="checkbox" name="notify_hr_announcements" <?= !empty($notificationPreferences['hr_announcements']) ? 'checked' : '' ?>>
        </label>

        <label class="flex justify-between items-center pb-1">
          <span class="text-sm">Application &amp; Evaluation Updates</span>
          <input type="checkbox" name="notify_evaluation_updates" <?= !empty($notificationPreferences['evaluation_updates']) ? 'checked' : '' ?>>
        </label>

        <button type="submit" class="mt-2 bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Save Notification Preferences</button>
      </form>
    </section>

    <section data-tab-content="privacy" class="hidden">
      <div class="mb-6">
        <h2 class="text-lg font-semibold">Privacy & Data Protection</h2>
        <p class="text-sm text-gray-500">Transparency on how your data is handled.</p>
      </div>

      <div class="space-y-4 text-sm text-gray-700">
        <p>
          Your personal data is processed in compliance with the
          <strong>Data Privacy Act of 2012 (RA 10173)</strong>.
        </p>

        <p>
          Access to personal information is restricted to authorized
          personnel and is logged for audit purposes.
        </p>

        <a href="#" class="text-daGreen hover:underline">
          View data privacy policy
        </a>
      </div>
    </section>

  </section>

</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
