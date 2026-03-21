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
      } elseif ($errorCode === 'missing_first_name') {
        $errorMessage = 'First name is required.';
      } elseif ($errorCode === 'missing_surname') {
        $errorMessage = 'Surname is required.';
      } elseif ($errorCode === 'missing_email') {
        $errorMessage = 'Email address is required.';
      } elseif ($errorCode === 'missing_mobile') {
        $errorMessage = 'Mobile number is required.';
      } elseif ($errorCode === 'missing_password') {
        $errorMessage = 'Password is required.';
      } elseif ($errorCode === 'missing_confirm_password') {
        $errorMessage = 'Confirm Password is required.';
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
        $errorMessage = 'Registration email verification is not configured. Check Supabase and SMTP settings.';
      } elseif ($errorCode === 'send_failed') {
        $errorMessage = 'We could not send the registration verification code right now. Please try again.';
      } elseif ($errorCode === 'mfa_locked') {
        $errorMessage = 'Too many invalid verification attempts. Restart registration to request a new code.';
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
        <label class="block text-sm font-medium mb-1" for="registerFirstName">First Name <span class="text-red-600">*</span></label>
        <input type="text" id="registerFirstName" name="first_name" autocomplete="given-name" required minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Use letters, spaces, apostrophes, periods, or hyphens only." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="registerSurname">Surname <span class="text-red-600">*</span></label>
        <input type="text" id="registerSurname" name="surname" autocomplete="family-name" required minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Use letters, spaces, apostrophes, periods, or hyphens only." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="registerEmail">Email Address <span class="text-red-600">*</span></label>
      <input type="email" id="registerEmail" name="email" autocomplete="email" maxlength="120" required spellcheck="false" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <p class="mt-1 text-xs text-gray-500">Personal email providers such as Gmail, Yahoo, and Outlook are accepted.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="registerMobile">Mobile Number <span class="text-red-600">*</span></label>
      <input type="text" id="registerMobile" name="mobile" autocomplete="tel" inputmode="tel" placeholder="09XXXXXXXXX" required pattern="(?:\+639\d{9}|09\d{9})" title="Use 09XXXXXXXXX or +639XXXXXXXXX." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <p class="mt-1 text-xs text-gray-500">Use 09XXXXXXXXX or +639XXXXXXXXX.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium mb-1" for="registerPassword">Password <span class="text-red-600">*</span></label>
        <input type="password" name="password" id="registerPassword" required minlength="10" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}" title="Use at least 10 characters with uppercase, lowercase, number, and special character." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
          <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Password strength</p>
            <span id="passwordStrengthLabel" class="text-xs font-semibold text-slate-500">Not set</span>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
            <div id="passwordStrengthBar" class="h-full w-0 rounded-full bg-slate-300 transition-all duration-200"></div>
          </div>
          <ul class="mt-3 space-y-1 text-xs text-slate-600" id="passwordRulesList">
            <li id="passwordRuleLength">At least 10 characters</li>
            <li id="passwordRuleUpper">At least one uppercase letter</li>
            <li id="passwordRuleLower">At least one lowercase letter</li>
            <li id="passwordRuleNumber">At least one number</li>
            <li id="passwordRuleSpecial">At least one special character</li>
          </ul>
        </div>
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
  const passwordStrengthLabel = document.getElementById('passwordStrengthLabel');
  const passwordStrengthBar = document.getElementById('passwordStrengthBar');
  const passwordRuleLength = document.getElementById('passwordRuleLength');
  const passwordRuleUpper = document.getElementById('passwordRuleUpper');
  const passwordRuleLower = document.getElementById('passwordRuleLower');
  const passwordRuleNumber = document.getElementById('passwordRuleNumber');
  const passwordRuleSpecial = document.getElementById('passwordRuleSpecial');
  const namePattern = /^[A-Za-z][A-Za-z\s'.-]*$/;
  const mobilePattern = /^(?:\+639\d{9}|09\d{9})$/;
  const emailPattern = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i;

  const passwordRuleElements = {
    length: passwordRuleLength,
    upper: passwordRuleUpper,
    lower: passwordRuleLower,
    number: passwordRuleNumber,
    special: passwordRuleSpecial,
  };

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

  const getPasswordChecks = (value) => ({
    length: value.length >= 10,
    upper: /[A-Z]/.test(value),
    lower: /[a-z]/.test(value),
    number: /\d/.test(value),
    special: /[^A-Za-z0-9]/.test(value),
  });

  const paintPasswordRule = (element, isPassing) => {
    if (!element) {
      return;
    }

    element.classList.remove('text-slate-600', 'text-emerald-700');
    element.classList.add(isPassing ? 'text-emerald-700' : 'text-slate-600');
  };

  const updatePasswordStrength = () => {
    if (!passwordInput) {
      return 0;
    }

    const value = passwordInput.value;
    const checks = getPasswordChecks(value);
    const score = Object.values(checks).filter(Boolean).length;
    const strengthMap = [
      { label: 'Not set', width: '0%', barClass: 'bg-slate-300', textClass: 'text-slate-500' },
      { label: 'Weak', width: '25%', barClass: 'bg-red-500', textClass: 'text-red-600' },
      { label: 'Fair', width: '50%', barClass: 'bg-amber-500', textClass: 'text-amber-600' },
      { label: 'Good', width: '75%', barClass: 'bg-sky-500', textClass: 'text-sky-600' },
      { label: 'Strong', width: '100%', barClass: 'bg-emerald-500', textClass: 'text-emerald-600' },
    ];
    const state = value === '' ? strengthMap[0] : strengthMap[Math.min(score, 4)];

    Object.entries(passwordRuleElements).forEach(([key, element]) => {
      paintPasswordRule(element, Boolean(checks[key]));
    });

    if (passwordStrengthLabel) {
      passwordStrengthLabel.textContent = state.label;
      passwordStrengthLabel.className = `text-xs font-semibold ${state.textClass}`;
    }

    if (passwordStrengthBar) {
      passwordStrengthBar.style.width = state.width;
      passwordStrengthBar.className = `h-full rounded-full transition-all duration-200 ${state.barClass}`;
    }

    return score;
  };

  const validatePasswordField = () => {
    if (!passwordInput) {
      return true;
    }

    const value = passwordInput.value;
    const checks = getPasswordChecks(value);

    if (value === '') {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password is required.');
    }
    if (!checks.length) {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password must be at least 10 characters.');
    }
    if (!checks.upper) {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password must include at least one uppercase letter.');
    }
    if (!checks.lower) {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password must include at least one lowercase letter.');
    }
    if (!checks.number) {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password must include at least one number.');
    }
    if (!checks.special) {
      updatePasswordStrength();
      return setFieldValidity(passwordInput, 'Password must include at least one special character.');
    }

    updatePasswordStrength();
    return setFieldValidity(passwordInput, '');
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

  passwordInput?.addEventListener('input', () => {
    validatePasswordField();
    validatePasswordMatch();
  });
  confirmPasswordInput?.addEventListener('input', validatePasswordMatch);
  confirmPasswordInput?.addEventListener('blur', validatePasswordMatch);
  firstNameInput?.addEventListener('input', () => validateNameField(firstNameInput, 'First name'));
  surnameInput?.addEventListener('input', () => validateNameField(surnameInput, 'Surname'));
  emailInput?.addEventListener('input', validateEmailField);
  mobileInput?.addEventListener('input', validateMobileField);
  passwordInput?.addEventListener('blur', validatePasswordField);
  updatePasswordStrength();

  registerForm?.addEventListener('submit', (event) => {
    const isNameValid = validateNameField(firstNameInput, 'First name')
      && validateNameField(surnameInput, 'Surname');
    const isEmailValid = validateEmailField();
    const isMobileValid = validateMobileField();
    const isPasswordValid = validatePasswordField();

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

    if (!isPasswordValid) {
      passwordInput?.reportValidity();
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
