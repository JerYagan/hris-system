<?php
$pageTitle = 'Email Sent | DA HRIS';

ob_start();
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">

  <div class="w-full max-w-md bg-white rounded-xl shadow p-8 text-center">

    <span class="material-icons text-daGreen text-4xl mb-4">
      mark_email_read
    </span>

    <h1 class="text-xl font-bold mb-2">Check your email</h1>
    <p class="text-sm text-gray-500 mb-6">
      If the email address is registered, a password reset link has been sent.
    </p>

    <a href="./login.php"
       class="inline-block bg-daGreen text-white px-6 py-2 rounded-lg text-sm">
      Back to Login
    </a>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
