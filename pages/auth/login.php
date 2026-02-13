<?php
$pageTitle = 'Login | DA HRIS';

ob_start();
?>

<!-- LOGIN CARD -->
<div class="max-w-5xl w-full bg-white rounded-xl shadow-lg overflow-hidden grid md:grid-cols-2 my-4">

  <!-- LEFT: IMAGE / BRAND -->
  <div class="hidden md:block relative">
    <img
      src="../../assets/images/hero-img.jpg"
      alt="Department of Agriculture"
      class="absolute inset-0 w-full h-full object-cover"
    />

    <div class="absolute inset-0 bg-daGreen/70"></div>

    <div class="relative z-10 p-10 text-white h-full flex flex-col justify-end">
      <h2 class="text-3xl font-bold mb-3">
        Department of Agriculture
      </h2>
      <p class="text-sm opacity-90 max-w-sm">
        Human Resource Information System  
        Secure access for authorized personnel.
      </p>
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

    <?php if (isset($_GET['error'])): ?>
      <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3
                  text-sm text-red-700 flex gap-2">
        <span class="material-icons text-sm">error</span>
        Invalid email or password.
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

    <div class="mt-6 text-sm text-center text-gray-600">
      Don’t have an account?
      <a href="request-access.php"
         class="text-daGreen font-medium hover:underline">
        Request access
      </a>
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

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
