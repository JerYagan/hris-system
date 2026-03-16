<?php

require_once __DIR__ . '/includes/auth-support.php';

authStartSession();

$pageTitle = 'Reset Password | ATI HRIS Portal';

ob_start();

$pendingReset = (array)($_SESSION['forgot_password_reset'] ?? []);
$hasPendingReset = !empty($pendingReset) && (int)($pendingReset['expires_at'] ?? 0) > time();
$errorCode = (string)($_GET['error'] ?? '');
$errorMessage = '';

if ($errorCode === 'missing_request') {
  $errorMessage = 'No pending password reset request was found. Request a new code first.';
} elseif ($errorCode === 'invalid_code') {
  $errorMessage = 'Invalid verification code.';
} elseif ($errorCode === 'expired') {
  $errorMessage = 'Your verification code has expired. Request a new one.';
} elseif ($errorCode === 'attempts') {
  $errorMessage = 'Too many invalid verification attempts. Request a new code.';
} elseif ($errorCode === 'password_mismatch') {
  $errorMessage = 'Passwords do not match.';
} elseif ($errorCode === 'weak_password') {
  $errorMessage = 'Use at least 10 characters with uppercase, lowercase, number, and special character.';
} elseif ($errorCode === 'reset_failed') {
  $errorMessage = 'Unable to update the password right now. Please try again.';
}
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

  <div class="w-full max-w-md bg-white rounded-xl shadow p-8">

    <h1 class="text-2xl font-bold mb-2">Reset Password</h1>
    <p class="text-sm text-gray-500 mb-6">
      Enter the verification code from your email, then create a new password.
    </p>

    <?php if (isset($_GET['sent'])): ?>
      <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        If the email address is registered, a verification code has been sent.
      </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if (!$hasPendingReset): ?>
      <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Request a reset code first before creating a new password.
      </div>
    <?php endif; ?>

    <form action="reset-success.php" method="POST" class="space-y-4">

      <div>
        <label class="text-sm font-medium">Verification Code</label>
        <input
          type="text"
          name="verification_code"
          inputmode="numeric"
          pattern="[0-9]{6}"
          maxlength="6"
          required
          class="w-full mt-1 border rounded-lg p-2"
          placeholder="Enter 6-digit code">
      </div>

      <div>
        <label class="text-sm font-medium">New Password</label>
        <input
          type="password"
          id="resetPassword"
          name="password"
          minlength="10"
          autocomplete="new-password"
          pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}"
          required
          class="w-full mt-1 border rounded-lg p-2">
        <p class="mt-1 text-xs text-gray-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
      </div>

      <div>
        <label class="text-sm font-medium">Confirm New Password</label>
        <input
          type="password"
          id="resetConfirmPassword"
          name="confirm_password"
          minlength="10"
          autocomplete="new-password"
          required
          class="w-full mt-1 border rounded-lg p-2">
      </div>

      <button
        type="submit"
        class="w-full bg-daGreen text-white py-2 rounded-lg text-sm">
        Reset Password
      </button>

      <a href="forgot-password.php" class="block text-center text-sm text-daGreen hover:underline">
        Request a new code
      </a>

    </form>

  </div>
</div>

<script>
  document.querySelector('form')?.addEventListener('submit', (event) => {
    const passwordInput = document.getElementById('resetPassword');
    const confirmPasswordInput = document.getElementById('resetConfirmPassword');

    if (!passwordInput || !confirmPasswordInput) {
      return;
    }

    if (passwordInput.value !== confirmPasswordInput.value) {
      confirmPasswordInput.setCustomValidity('Passwords do not match.');
      confirmPasswordInput.reportValidity();
      event.preventDefault();
      return;
    }

    confirmPasswordInput.setCustomValidity('');
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
