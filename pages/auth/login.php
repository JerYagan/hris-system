<?php
$pageTitle = 'Login | DA ATI';

ob_start();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<!-- LOGIN CARD -->
<div class="max-w-5xl w-full bg-white rounded-xl shadow-lg overflow-hidden grid md:grid-cols-2 my-4">

  <!-- LEFT: CAROUSEL / BRAND -->
  <div class="hidden md:block relative">
    <div class="swiper loginSwiper h-full">
      <div class="swiper-wrapper">
        <div class="swiper-slide relative">
          <img
            src="https://picsum.photos/seed/da-login-1/1200/1000"
            alt="Department of Agriculture operations"
            class="absolute inset-0 w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-daGreen/75"></div>
          <div class="relative z-10 p-10 text-white h-full flex flex-col justify-end">
            <h2 class="text-3xl font-bold mb-3">
              Agricultural Training Institute
            </h2>
            <p class="text-sm opacity-90 max-w-sm">
              Human Resource Information System.
              Secure access for authorized personnel.
            </p>
          </div>
        </div>

        <div class="swiper-slide relative">
          <img
            src="https://picsum.photos/seed/da-login-2/1200/1000"
            alt="Public service and workforce"
            class="absolute inset-0 w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-daGreen/75"></div>
          <div class="relative z-10 p-10 text-white h-full flex flex-col justify-end">
            <h2 class="text-3xl font-bold mb-3">
              Responsive Public Service
            </h2>
            <p class="text-sm opacity-90 max-w-sm">
              Enabling transparent and efficient HR operations across DA offices.
            </p>
          </div>
        </div>

        <div class="swiper-slide relative">
          <img
            src="https://picsum.photos/seed/da-login-3/1200/1000"
            alt="Agriculture and sustainability"
            class="absolute inset-0 w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-daGreen/75"></div>
          <div class="relative z-10 p-10 text-white h-full flex flex-col justify-end">
            <h2 class="text-3xl font-bold mb-3">
              One DA, One Digital Portal
            </h2>
            <p class="text-sm opacity-90 max-w-sm">
              Strengthening workforce support through secure and modern systems.
            </p>
          </div>
        </div>
      </div>
      <div class="swiper-pagination"></div>
    </div>
  </div>

  <!-- RIGHT: LOGIN FORM -->
  <div class="p-10">

    <a href="../../index.html"
       class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600
              hover:text-daGreen transition font-medium">
      <span class="material-icons text-base">arrow_back</span>
      Back
    </a>

    <hr class="mb-6 border-gray-200">

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        Sign in to your account
      </h1>
      <p class="text-sm text-gray-600">
        Use your official DA credentials to continue.
      </p>
    </div>

    <?php if (isset($_GET['logout'])): ?>
      <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3
                  text-sm text-emerald-700 flex gap-2">
        <span class="material-icons text-sm">check_circle</span>
        You have been logged out.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3
                  text-sm text-emerald-700 flex gap-2">
        <span class="material-icons text-sm">check_circle</span>
        Registration successful. You can now sign in.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <?php
        $errorCode = (string)($_GET['error'] ?? 'invalid');
        $errorMessage = 'Invalid email or password.';

        if ($errorCode === 'inactive') {
          $errorMessage = 'Your account is not active. Please contact an administrator.';
        } elseif ($errorCode === 'role') {
          $errorMessage = 'No active role is assigned to this account.';
        } elseif ($errorCode === 'config') {
          $errorMessage = 'Authentication is not configured. Check SUPABASE credentials.';
        }
      ?>
      <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3
                  text-sm text-red-700 flex gap-2">
        <span class="material-icons text-sm">error</span>
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form
      action="login-handler.php"
      method="POST"
      class="space-y-6">

      <!-- EMAIL -->
      <div>
        <label class="block text-sm font-medium mb-1">
          Email address
        </label>
        <div class="relative">
          <span class="material-icons absolute left-3 top-2.5 text-gray-400">
            email
          </span>
          <input
            type="email"
            name="email"
            placeholder="name@da.gov.ph"
            class="w-full pl-10 pr-4 py-2.5 border rounded-md
                   focus:outline-none focus:ring-2 focus:ring-daGreen"
            required>
        </div>
      </div>

      <!-- PASSWORD -->
      <div>
        <label class="block text-sm font-medium mb-1">
          Password
        </label>
        <div class="relative">
          <span class="material-icons absolute left-3 top-2.5 text-gray-400">
            lock
          </span>
          <input
            id="password"
            type="password"
            name="password"
            placeholder="••••••••"
            class="w-full pl-10 pr-12 py-2.5 border rounded-md
                   focus:outline-none focus:ring-2 focus:ring-daGreen"
            required>

          <button type="button"
                  id="togglePassword"
                  class="absolute right-3 top-2.5 text-gray-400">
            <span class="material-icons text-base">visibility</span>
          </button>
        </div>
      </div>

      <!-- OPTIONS -->
      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2">
          <input type="checkbox" class="rounded border-gray-300">
          Remember me
        </label>

        <a href="forgot-password.php"
           class="text-daGreen hover:underline">
          Forgot password?
        </a>
      </div>

      <!-- SUBMIT -->
      <button
        type="submit"
        class="w-full bg-daGreen text-white py-3 rounded-md font-semibold
               hover:bg-daGreenLight transition flex items-center justify-center gap-2">
        <span class="material-icons text-sm">login</span>
        Sign In
      </button>

    </form>

    <div class="mt-6 text-sm text-center text-gray-600 space-y-2">
      <p>
        Don’t have an applicant account?
        <a href="register-applicant.php" class="text-daGreen font-medium hover:underline">Sign Up as Applicant</a>
      </p>
      <p class="text-xs text-gray-500">
        Employee/Staff registration:
        <a href="register.php" class="text-daGreen hover:underline">Open full registration form</a>
      </p>
    </div>

  </div>
</div>

<script>
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const loginError = document.getElementById("loginError");

  togglePassword?.addEventListener("click", () => {
    const isHidden = passwordInput.type === "password";
    passwordInput.type = isHidden ? "text" : "password";
    togglePassword.innerHTML = `<span class="material-icons text-base">
      ${isHidden ? "visibility_off" : "visibility"}
    </span>`;
  });

  // Demo only
  document.querySelector("form")?.addEventListener("submit", e => {
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  new Swiper(".loginSwiper", {
    loop: true,
    speed: 700,
    autoplay: {
      delay: 3500,
      disableOnInteraction: false,
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
