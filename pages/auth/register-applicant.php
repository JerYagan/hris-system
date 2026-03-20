<?php
$pageTitle = 'Register | ATI HRIS Portal';

ob_start();
?>

<div class="w-full max-w-xl bg-white rounded-2xl shadow-lg p-8 my-6">
  <a href="login.php" class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600 hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back to Login
  </a>

  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Register</h1>
    <p class="text-sm text-gray-600">Create your applicant account to browse job postings and submit applications online.</p>
  </div>

  <?php if (isset($_GET['error'])): ?>
    <?php
      $errorCode = (string)($_GET['error'] ?? 'create_failed');
      $errorMessage = 'Registration failed. Please try again.';

      if ($errorCode === 'invalid_email') {
        $errorMessage = 'Please enter a valid email address.';
      } elseif ($errorCode === 'weak_password') {
        $errorMessage = 'Password must be at least 10 characters and include uppercase, lowercase, number, and special character.';
      } elseif ($errorCode === 'password_mismatch') {
        $errorMessage = 'Passwords do not match.';
      } elseif ($errorCode === 'missing_name') {
        $errorMessage = 'First name and surname are required.';
      } elseif ($errorCode === 'invalid_first_name') {
        $errorMessage = 'Enter a valid first name using letters, spaces, apostrophes, periods, or hyphens only.';
      } elseif ($errorCode === 'invalid_surname') {
        $errorMessage = 'Enter a valid surname using letters, spaces, apostrophes, periods, or hyphens only.';
      } elseif ($errorCode === 'invalid_mobile') {
        $errorMessage = 'Enter a valid Philippine mobile number using 09XXXXXXXXX or +639XXXXXXXXX.';
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

  <form action="register-applicant-handler.php" method="POST" class="space-y-5" novalidate>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium mb-1">First Name</label>
        <input type="text" id="registerFirstName" name="first_name" autocomplete="given-name" required minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Use letters, spaces, apostrophes, periods, or hyphens only." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Surname</label>
        <input type="text" id="registerSurname" name="surname" autocomplete="family-name" required minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Use letters, spaces, apostrophes, periods, or hyphens only." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Email Address</label>
      <input type="email" id="registerEmail" name="email" autocomplete="email" maxlength="120" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Mobile Number</label>
      <input type="text" id="registerMobile" name="mobile" autocomplete="tel" inputmode="tel" placeholder="09XXXXXXXXX" required pattern="(?:\+639\d{9}|09\d{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <p class="mt-1 text-xs text-gray-500">Use 09XXXXXXXXX or +639XXXXXXXXX.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" id="registerPassword" required minlength="10" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}" title="Use at least 10 characters with uppercase, lowercase, number, and special character." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <p class="mt-1 text-xs text-gray-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Confirm Password</label>
        <input type="password" name="confirm_password" id="registerConfirmPassword" required minlength="10" autocomplete="new-password" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </div>

    <button type="submit" class="w-full rounded-lg bg-daGreen px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition inline-flex items-center justify-center gap-2">
      <span class="material-icons text-base">person_add</span>
      Create Account
    </button>
  </form>
</div>

<script>
  const registerForm = document.querySelector('form');
  const firstNameInput = document.getElementById('registerFirstName');
  const surnameInput = document.getElementById('registerSurname');
  const emailInput = document.getElementById('registerEmail');
  const mobileInput = document.getElementById('registerMobile');
  const passwordInput = document.getElementById('registerPassword');
  const confirmPasswordInput = document.getElementById('registerConfirmPassword');
  const namePattern = /^[A-Za-z][A-Za-z\s'.-]*$/;
  const mobilePattern = /^(?:\+639\d{9}|09\d{9})$/;
  const emailPattern = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i;

  const setFieldValidity = (input, message) => {
    if (!input) {
      return true;
    }

    input.setCustomValidity(message);
    return message === '';
  };

  const validateNameField = (input, label) => {
    if (!input) {
      return true;
    }

    const value = input.value.trim();
    if (value === '') {
      return setFieldValidity(input, `${label} is required.`);
    }
    if (value.length < 2) {
      return setFieldValidity(input, `${label} must be at least 2 characters.`);
    }
    if (!namePattern.test(value)) {
      return setFieldValidity(input, `${label} may only contain letters, spaces, apostrophes, periods, and hyphens.`);
    }

    return setFieldValidity(input, '');
  };

  const validateEmailField = () => {
    if (!emailInput) {
      return true;
    }

    const value = emailInput.value.trim();
    if (value === '') {
      return setFieldValidity(emailInput, 'Email address is required.');
    }
    if (!emailPattern.test(value)) {
      return setFieldValidity(emailInput, 'Please enter a valid email address.');
    }

    return setFieldValidity(emailInput, '');
  };

  const validateMobileField = () => {
    if (!mobileInput) {
      return true;
    }

    const value = mobileInput.value.trim();
    if (value === '') {
      return setFieldValidity(mobileInput, 'Mobile number is required.');
    }
    if (!mobilePattern.test(value)) {
      return setFieldValidity(mobileInput, 'Use 09XXXXXXXXX or +639XXXXXXXXX.');
    }

    return setFieldValidity(mobileInput, '');
  };

  const setMismatchState = (hasMismatch) => {
    if (!passwordInput || !confirmPasswordInput) {
      return;
    }

    const mismatchClasses = ['border-red-500', 'focus:ring-red-500'];
    const normalClasses = ['focus:ring-daGreen'];

    [passwordInput, confirmPasswordInput].forEach((input) => {
      input.classList.remove(...mismatchClasses, ...normalClasses);
      input.classList.add(hasMismatch ? 'border-red-500' : 'focus:ring-daGreen');
      if (hasMismatch) {
        input.classList.add('focus:ring-red-500');
      }
    });

    confirmPasswordInput.setCustomValidity(hasMismatch ? 'Passwords do not match.' : '');
  };

  const validatePasswordMatch = () => {
    if (!passwordInput || !confirmPasswordInput) {
      return true;
    }

    const hasMismatch = passwordInput.value !== ''
      && confirmPasswordInput.value !== ''
      && passwordInput.value !== confirmPasswordInput.value;

    setMismatchState(hasMismatch);
    return !hasMismatch;
  };

  passwordInput?.addEventListener('input', validatePasswordMatch);
  confirmPasswordInput?.addEventListener('input', validatePasswordMatch);
  confirmPasswordInput?.addEventListener('blur', validatePasswordMatch);
  firstNameInput?.addEventListener('input', () => validateNameField(firstNameInput, 'First name'));
  surnameInput?.addEventListener('input', () => validateNameField(surnameInput, 'Surname'));
  emailInput?.addEventListener('input', validateEmailField);
  mobileInput?.addEventListener('input', validateMobileField);

  registerForm?.addEventListener('submit', (event) => {
    const isNameValid = validateNameField(firstNameInput, 'First name')
      && validateNameField(surnameInput, 'Surname');
    const isEmailValid = validateEmailField();
    const isMobileValid = validateMobileField();

    if (!validatePasswordMatch()) {
      confirmPasswordInput?.reportValidity();
      event.preventDefault();
      return;
    }

    if (!isNameValid) {
      firstNameInput?.reportValidity();
      event.preventDefault();
      return;
    }

    if (!isEmailValid) {
      emailInput?.reportValidity();
      event.preventDefault();
      return;
    }

    if (!isMobileValid) {
      mobileInput?.reportValidity();
      event.preventDefault();
      return;
    }

    if (!registerForm.checkValidity()) {
      registerForm.reportValidity();
      event.preventDefault();
    }
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
