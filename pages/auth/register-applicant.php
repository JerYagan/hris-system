<?php
$pageTitle = 'Applicant Sign Up | DA HRIS';

ob_start();
?>

<div class="w-full max-w-xl bg-white rounded-2xl shadow-lg p-8 my-6">
  <a href="login.php" class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600 hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back to Login
  </a>

  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Create Applicant Account</h1>
    <p class="text-sm text-gray-600">Sign up as an applicant to browse jobs and submit your application online.</p>
  </div>

  <?php if (isset($_GET['error'])): ?>
    <?php
      $errorCode = (string)($_GET['error'] ?? 'create_failed');
      $errorMessage = 'Registration failed. Please try again.';

      if ($errorCode === 'invalid_email') {
        $errorMessage = 'Please enter a valid email address.';
      } elseif ($errorCode === 'weak_password') {
        $errorMessage = 'Password must be at least 8 characters.';
      } elseif ($errorCode === 'password_mismatch') {
        $errorMessage = 'Passwords do not match.';
      } elseif ($errorCode === 'missing_name') {
        $errorMessage = 'First name and surname are required.';
      } elseif ($errorCode === 'role_missing') {
        $errorMessage = 'Applicant role is missing from the system configuration.';
      } elseif ($errorCode === 'email_exists') {
        $errorMessage = 'This email is already registered.';
      } elseif ($errorCode === 'config') {
        $errorMessage = 'Registration is not configured. Check Supabase credentials.';
      }
    ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex gap-2">
      <span class="material-icons text-sm">error</span>
      <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form action="register-applicant-handler.php" method="POST" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium mb-1">First Name</label>
        <input type="text" name="first_name" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Surname</label>
        <input type="text" name="surname" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Email Address</label>
      <input type="email" name="email" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Mobile Number</label>
      <input type="text" name="mobile" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </div>

    <button type="submit" class="w-full rounded-lg bg-daGreen px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition inline-flex items-center justify-center gap-2">
      <span class="material-icons text-base">person_add</span>
      Create Applicant Account
    </button>
  </form>

  <div class="mt-6 text-sm text-center text-gray-600">
    Need employee/staff registration?
    <a href="register.php" class="text-daGreen font-medium hover:underline">Use full registration form</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
