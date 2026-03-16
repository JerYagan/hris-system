<?php

require_once __DIR__ . '/includes/auth-support.php';

authStartSession();

$pendingMfa = (array)($_SESSION[authPendingMfaSessionKey()] ?? []);
$mode = strtolower((string)($_GET['mode'] ?? ($pendingMfa['purpose'] ?? 'login')));

if (empty($pendingMfa)) {
    $redirectTarget = $mode === 'register' ? 'register-applicant.php?error=mfa_locked' : 'login.php?error=mfa_missing';
    header('Location: ' . $redirectTarget);
    exit;
}

$purpose = strtolower((string)($pendingMfa['purpose'] ?? $mode));
$maskedEmail = authMaskEmail((string)($pendingMfa['email'] ?? ''));
$expiresAt = (int)($pendingMfa['expires_at'] ?? 0);
$resendAvailableAt = (int)($pendingMfa['resend_available_at'] ?? 0);
$attemptLimit = (int)($pendingMfa['attempt_limit'] ?? 5);
$attemptsUsed = (int)($pendingMfa['attempts'] ?? 0);
$remainingAttempts = max(0, $attemptLimit - $attemptsUsed);
$cooldownRemaining = max(0, $resendAvailableAt - time());
$isExpired = $expiresAt > 0 && $expiresAt <= time();
$errorCode = strtolower((string)($_GET['error'] ?? ''));

$pageTitle = ($purpose === 'register' ? 'Register' : 'Login') . ' Verification | ATI HRIS Portal';
$heading = $purpose === 'register' ? 'Verify your registration' : 'Verify your sign-in';
$description = $purpose === 'register'
    ? 'Enter the one-time password sent to your email to complete your ATI HRIS Portal registration.'
    : 'Enter the one-time password sent to your email to finish signing in to ATI HRIS Portal.';

$statusState = 'info';
$statusMessage = '';
if (isset($_GET['sent']) || isset($_GET['resent'])) {
    $statusState = 'success';
    $statusMessage = 'A verification code was sent to ' . $maskedEmail . '.';
}

if ($errorCode === 'invalid_code') {
    $statusState = 'error';
    $statusMessage = 'Invalid verification code. Check your email and try again.';
} elseif ($errorCode === 'expired' || $isExpired) {
    $statusState = 'error';
    $statusMessage = 'This verification code has expired. Request a new code to continue.';
} elseif ($errorCode === 'cooldown') {
    $statusState = 'error';
    $statusMessage = 'You requested a code too recently. Wait for the resend timer, then try again.';
} elseif ($errorCode === 'config') {
  $statusState = 'error';
  $statusMessage = 'Email OTP delivery is not configured yet. Check the SMTP settings and sender email.';
} elseif ($errorCode === 'send_failed') {
  $statusState = 'error';
  $statusMessage = 'We could not resend the verification code right now. Please try again in a moment.';
}

ob_start();
?>

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 my-6">
  <a href="<?= htmlspecialchars($purpose === 'register' ? 'register-applicant.php' : 'login.php', ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600 hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back
  </a>

  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="text-sm text-gray-600"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <?php if ($statusMessage !== ''): ?>
    <?php
      $statusClasses = $statusState === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : ($statusState === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-sky-200 bg-sky-50 text-sky-700');
    ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm flex gap-2 <?= $statusClasses ?>">
      <span class="material-icons text-sm"><?= $statusState === 'success' ? 'check_circle' : 'info' ?></span>
      <span><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  <?php endif; ?>

  <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 space-y-2">
    <p><strong>Delivery channel:</strong> Email OTP</p>
    <p><strong>Sent to:</strong> <?= htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Expires:</strong> <?= htmlspecialchars(authFormatPhilippinesTimestamp($expiresAt), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Attempt limit:</strong> <?= htmlspecialchars((string)$attemptLimit, ENT_QUOTES, 'UTF-8') ?> tries</p>
    <p><strong>Remaining attempts:</strong> <?= htmlspecialchars((string)$remainingAttempts, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Fallback:</strong> <?= htmlspecialchars((string)($pendingMfa['fallback_behavior'] ?? authMfaConfig()['fallback_behavior']), ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <form action="mfa-verify-handler.php" method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Verification Code</label>
      <input type="text" name="verification_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="w-full px-4 py-2.5 border rounded-lg tracking-[0.35em] text-center text-lg focus:outline-none focus:ring-2 focus:ring-daGreen" placeholder="000000">
    </div>

    <button type="submit" class="w-full rounded-lg bg-daGreen px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition inline-flex items-center justify-center gap-2">
      <span class="material-icons text-base">verified_user</span>
      Verify Code
    </button>
  </form>

  <form action="mfa-resend.php" method="POST" class="mt-4">
    <button type="submit" <?= $cooldownRemaining > 0 ? 'disabled' : '' ?> class="w-full rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition <?= $cooldownRemaining > 0 ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'hover:bg-slate-50' ?> inline-flex items-center justify-center gap-2">
      <span class="material-icons text-base">refresh</span>
      <?= $cooldownRemaining > 0 ? 'Resend available in ' . $cooldownRemaining . 's' : 'Resend Code' ?>
    </button>
  </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';