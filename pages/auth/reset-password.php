<?php
$pageTitle = 'Reset Password | DA HRIS';

ob_start();
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

  <div class="w-full max-w-md bg-white rounded-xl shadow p-8">

    <h1 class="text-2xl font-bold mb-2">Reset Password</h1>
    <p class="text-sm text-gray-500 mb-6">
      Please enter your new password below.
    </p>

    <form action="reset-success.php" method="POST" class="space-y-4">

      <div>
        <label class="text-sm font-medium">New Password</label>
        <input
          type="password"
          required
          class="w-full mt-1 border rounded-lg p-2">
      </div>

      <div>
        <label class="text-sm font-medium">Confirm New Password</label>
        <input
          type="password"
          required
          class="w-full mt-1 border rounded-lg p-2">
      </div>

      <button
        type="submit"
        class="w-full bg-daGreen text-white py-2 rounded-lg text-sm">
        Reset Password
      </button>

    </form>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/auth-layout.php';
