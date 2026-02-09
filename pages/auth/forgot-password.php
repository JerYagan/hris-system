<?php
$pageTitle = 'Forgot Password | DA HRIS';

ob_start();
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

  <div class="w-full max-w-md bg-white rounded-xl shadow p-8">

    <a href="./login.php"
       class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600 hover:text-daGreen">
      <span class="material-icons text-base">arrow_back</span>
      Back to Login
    </a>

    <h1 class="text-2xl font-bold mb-2">Forgot Password</h1>
    <p class="text-sm text-gray-500 mb-6">
      Enter your registered email address. We will send you a link to reset your password.
    </p>

    <form action="forgot-password-sent.php" method="POST" class="space-y-4">

      <div>
        <label class="text-sm font-medium">Email Address</label>
        <input
          type="email"
          required
          class="w-full mt-1 border rounded-lg p-2"
          placeholder="employee@da.gov.ph">
      </div>

      <button
        type="submit"
        class="w-full bg-daGreen text-white py-2 rounded-lg text-sm">
        Send Reset Link
      </button>

    </form>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/auth-layout.php';
