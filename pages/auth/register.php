<?php
$pageTitle = 'Sign Up | DA HRIS';

ob_start();
?>

<div class="w-full max-w-6xl bg-white rounded-2xl shadow-lg p-8 my-4">
  <a href="login.php"
     class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600 hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back to Login
  </a>

  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Create an Account</h1>
    <p class="text-sm text-gray-600">A guided registration flow is easier to complete. Start with account setup, then continue with your profile details.</p>
    <p class="mt-2 text-xs text-gray-500">
      Applicant account only?
      <a href="register-applicant.php" class="text-daGreen font-medium hover:underline">Use quick applicant sign up</a>
    </p>
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
      } elseif ($errorCode === 'invalid_role') {
        $errorMessage = 'Please select a valid role.';
      } elseif ($errorCode === 'missing_name') {
        $errorMessage = 'First name and surname are required.';
      } elseif ($errorCode === 'email_exists') {
        $errorMessage = 'This email is already registered.';
      } elseif ($errorCode === 'agency_employee_no_exists') {
        $errorMessage = 'Agency Employee No. is already in use. Please enter a unique value.';
      } elseif ($errorCode === 'config') {
        $errorMessage = 'Registration is not configured. Check Supabase credentials.';
      }
    ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex gap-2">
      <span class="material-icons text-sm">error</span>
      <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 flex gap-2">
    <span class="material-icons text-sm">info</span>
    You may add multiple spouse entries when applicable to your religion or ethnicity.
  </div>

  <div id="draftRestoredNotice" class="hidden mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
    <span class="material-icons text-sm">check_circle</span>
    Draft restored. You can continue where you left off.
  </div>

  <div class="mb-8">
    <div class="flex flex-wrap items-center gap-3" id="stepIndicators">
      <div class="step-indicator flex items-center gap-2" data-step="0">
        <span class="step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-daGreen text-white">1</span>
        <span class="text-sm font-semibold text-gray-900">Account</span>
      </div>
      <span class="text-gray-300">—</span>
      <div class="step-indicator flex items-center gap-2" data-step="1">
        <span class="step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-gray-200 text-gray-600">2</span>
        <span class="text-sm font-semibold text-gray-500">Personal</span>
      </div>
      <span class="text-gray-300">—</span>
      <div class="step-indicator flex items-center gap-2" data-step="2">
        <span class="step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-gray-200 text-gray-600">3</span>
        <span class="text-sm font-semibold text-gray-500">Family</span>
      </div>
      <span class="text-gray-300">—</span>
      <div class="step-indicator flex items-center gap-2" data-step="3">
        <span class="step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-gray-200 text-gray-600">4</span>
        <span class="text-sm font-semibold text-gray-500">Education</span>
      </div>
    </div>
    <div class="w-full h-2 bg-gray-100 rounded-full mt-4 overflow-hidden">
      <div id="progressBar" class="h-full bg-daGreen transition-all duration-300" style="width: 25%"></div>
    </div>
  </div>

  <form class="space-y-6" id="registerForm" action="register-handler.php" method="POST">
    <section class="step-panel border border-gray-200 rounded-xl p-5" data-step="0">
      <h2 class="text-lg font-bold text-gray-900 mb-4">I. Account Credentials</h2>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Role</label>
          <select name="account_role" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Select role</option>
            <option value="employee">Employee</option>
            <option value="supervisor">Supervisor</option>
            <option value="hr_officer">HR Officer</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Employee ID / Reference No.</label>
          <input type="text" name="employee_reference" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">E-mail Address</label>
          <input type="email" name="email" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Mobile No.</label>
          <input type="text" name="mobile" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Password</label>
          <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Confirm Password</label>
          <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
      </div>
    </section>

    <section class="step-panel border border-gray-200 rounded-xl p-5 hidden" data-step="1">
      <h2 class="text-lg font-bold text-gray-900 mb-4">II. Personal Information</h2>

      <div class="grid md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Surname</label>
          <input type="text" name="surname" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">First Name</label>
          <input type="text" name="first_name" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Middle Name</label>
          <input type="text" name="middle_name" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Name Extension</label>
          <input type="text" name="name_extension" placeholder="JR, SR" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Date of Birth</label>
          <input type="date" name="date_of_birth" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Place of Birth</label>
          <input type="text" name="place_of_birth" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Sex at Birth</label>
          <select name="sex_at_birth" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Select</option>
            <option>Male</option>
            <option>Female</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Civil Status</label>
          <select name="civil_status" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Select</option>
            <option>Single</option>
            <option>Married</option>
            <option>Widowed</option>
            <option>Separated</option>
            <option>Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Height (m)</label>
          <input type="number" step="0.01" name="height_m" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Weight (kg)</label>
          <input type="number" step="0.01" name="weight_kg" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Blood Type</label>
          <input type="text" name="blood_type" placeholder="O+, A-, etc." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Citizenship</label>
          <input type="text" name="citizenship" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Dual Citizenship</label>
          <select name="dual_citizenship" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Select</option>
            <option>Yes</option>
            <option>No</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Country (if dual citizenship)</label>
          <input type="text" name="dual_country" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Telephone No.</label>
          <input type="text" name="telephone" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div>
          <h3 class="font-semibold text-gray-900 mb-3">Residential Address</h3>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="res_house" placeholder="House/Block/Lot No." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_street" placeholder="Street" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_subdivision" placeholder="Subdivision/Village" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_barangay" placeholder="Barangay" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_city" placeholder="City/Municipality" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_province" placeholder="Province" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="res_zip" placeholder="ZIP Code" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          </div>
        </div>

        <div>
          <h3 class="font-semibold text-gray-900 mb-3">Permanent Address</h3>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="perm_house" placeholder="House/Block/Lot No." class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_street" placeholder="Street" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_subdivision" placeholder="Subdivision/Village" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_barangay" placeholder="Barangay" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_city" placeholder="City/Municipality" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_province" placeholder="Province" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="perm_zip" placeholder="ZIP Code" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          </div>
        </div>
      </div>

      <div class="grid md:grid-cols-5 gap-4 mt-6">
        <div>
          <label class="block text-sm font-medium mb-1">UMID ID No.</label>
          <input type="text" name="umid" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">PAG-IBIG ID No.</label>
          <input type="text" name="pagibig" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">PHILHEALTH No.</label>
          <input type="text" name="philhealth" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">PhilSys Number (PSN)</label>
          <input type="text" name="psn" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">TIN No.</label>
          <input type="text" name="tin" class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-sm font-medium mb-1">Agency Employee No.</label>
        <input type="text" name="agency_employee_no" class="w-full md:w-1/3 px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      </div>
    </section>

    <section class="step-panel border border-gray-200 rounded-xl p-5 hidden" data-step="2">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">III. Family Background</h2>
        <button type="button" id="addSpouseBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-daGreen text-daGreen text-sm font-semibold hover:bg-green-50 transition">
          <span class="material-icons text-base">add</span>
          Add Spouse
        </button>
      </div>

      <div id="spouseList" class="space-y-4">
        <div class="spouse-item border border-gray-200 rounded-lg p-4">
          <p class="font-semibold text-sm text-gray-700 mb-3">Spouse #1</p>
          <div class="grid md:grid-cols-4 gap-3">
            <input type="text" name="spouses[0][surname]" placeholder="Spouse Surname" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][first_name]" placeholder="First Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][middle_name]" placeholder="Middle Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][extension]" placeholder="Name Extension" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][occupation]" placeholder="Occupation" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][employer]" placeholder="Employer/Business Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
            <input type="text" name="spouses[0][telephone]" placeholder="Telephone No." class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="spouses[0][business_address]" placeholder="Business Address" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-4">
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-gray-900 mb-3">Children</h3>
        <div id="childrenList" class="space-y-3">
          <div class="grid md:grid-cols-3 gap-3 child-item">
            <input type="text" name="children[0][name]" placeholder="Child Full Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
            <input type="date" name="children[0][birth_date]" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          </div>
        </div>
        <button type="button" id="addChildBtn" class="mt-3 inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-daGreen text-daGreen text-sm font-semibold hover:bg-green-50 transition">
          <span class="material-icons text-base">add</span>
          Add Child
        </button>
      </div>

      <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div>
          <h3 class="font-semibold text-gray-900 mb-3">Father</h3>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="father_surname" placeholder="Surname" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="father_first_name" placeholder="First Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="father_middle_name" placeholder="Middle Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="father_extension" placeholder="Name Extension" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          </div>
        </div>

        <div>
          <h3 class="font-semibold text-gray-900 mb-3">Mother’s Maiden Name</h3>
          <div class="grid grid-cols-1 gap-3">
            <input type="text" name="mother_surname" placeholder="Surname" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="mother_first_name" placeholder="First Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <input type="text" name="mother_middle_name" placeholder="Middle Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          </div>
        </div>
      </div>
    </section>

    <section class="step-panel border border-gray-200 rounded-xl p-5 hidden" data-step="3">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">IV. Educational Background</h2>
        <button type="button" id="addEducationBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-daGreen text-daGreen text-sm font-semibold hover:bg-green-50 transition">
          <span class="material-icons text-base">add</span>
          Add Education Row
        </button>
      </div>

      <div id="educationList" class="space-y-3">
        <div class="education-item grid md:grid-cols-12 gap-3 border border-gray-200 rounded-lg p-3">
          <select name="education[0][level]" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Level</option>
            <option>Elementary</option>
            <option>Secondary</option>
            <option>Vocational/Trade Course</option>
            <option>College</option>
            <option>Graduate Studies</option>
          </select>
          <input type="text" name="education[0][school]" placeholder="Name of School" class="md:col-span-3 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][course]" placeholder="Degree/Course" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][from]" placeholder="From" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][to]" placeholder="To" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][highest_level]" placeholder="Highest Level" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][year_graduated]" placeholder="Year Graduated" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
          <input type="text" name="education[0][honors]" placeholder="Honors" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
      </div>

      <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Review your details before submitting. HR may request supporting documents for verification.
      </div>
    </section>

    <div class="flex items-center justify-between gap-3 pt-2">
      <button type="button" id="prevStepBtn" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition hidden">
        Previous
      </button>
      <div class="flex-1"></div>
      <button type="button" id="nextStepBtn" class="px-5 py-2.5 rounded-lg bg-daGreen text-white font-semibold hover:bg-green-700 transition inline-flex items-center gap-2">
        Next
        <span class="material-icons text-base">arrow_forward</span>
      </button>
      <button type="submit" id="submitBtn" class="px-6 py-2.5 rounded-lg bg-daGreen text-white font-semibold hover:bg-green-700 transition hidden inline-flex items-center gap-2">
        <span class="material-icons text-base">person_add</span>
        Submit Registration
      </button>
    </div>

    <div class="text-xs text-gray-500 text-center pt-1">
      This registration form is patterned after the Personal Data Sheet and may require verification by HR.
    </div>
  </form>

  <p class="mt-6 text-sm text-center text-gray-600">
    Already have an account?
    <a href="login.php" class="text-daGreen font-medium hover:underline">Sign In</a>
  </p>
</div>

<script>
  const registerForm = document.getElementById('registerForm');
  const panels = Array.from(document.querySelectorAll('.step-panel'));
  const indicators = Array.from(document.querySelectorAll('.step-indicator'));
  const progressBar = document.getElementById('progressBar');
  const nextStepBtn = document.getElementById('nextStepBtn');
  const prevStepBtn = document.getElementById('prevStepBtn');
  const submitBtn = document.getElementById('submitBtn');
  const draftRestoredNotice = document.getElementById('draftRestoredNotice');

  let currentStep = 0;

  const showStep = (stepIndex) => {
    panels.forEach((panel, index) => {
      panel.classList.toggle('hidden', index !== stepIndex);
    });

    indicators.forEach((indicator, index) => {
      const badge = indicator.querySelector('.step-badge');
      const label = indicator.querySelector('span:last-child');

      if (index <= stepIndex) {
        badge.className = 'step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-daGreen text-white';
        label.className = 'text-sm font-semibold text-gray-900';
      } else {
        badge.className = 'step-badge w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-gray-200 text-gray-600';
        label.className = 'text-sm font-semibold text-gray-500';
      }
    });

    const progress = ((stepIndex + 1) / panels.length) * 100;
    progressBar.style.width = `${progress}%`;

    prevStepBtn.classList.toggle('hidden', stepIndex === 0);
    nextStepBtn.classList.toggle('hidden', stepIndex === panels.length - 1);
    submitBtn.classList.toggle('hidden', stepIndex !== panels.length - 1);
  };

  const validateCurrentStep = () => {
    const currentPanel = panels[currentStep];
    const requiredFields = Array.from(currentPanel.querySelectorAll('input[required], select[required], textarea[required]'));

    for (const field of requiredFields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }

    const password = registerForm.querySelector('input[name="password"]');
    const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');

    if (currentStep === 0 && password && confirmPassword && password.value !== confirmPassword.value) {
      confirmPassword.setCustomValidity('Passwords do not match.');
      confirmPassword.reportValidity();
      return false;
    }

    if (confirmPassword) {
      confirmPassword.setCustomValidity('');
    }

    return true;
  };

  nextStepBtn?.addEventListener('click', () => {
    if (!validateCurrentStep()) {
      return;
    }

    if (currentStep < panels.length - 1) {
      currentStep += 1;
      showStep(currentStep);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  prevStepBtn?.addEventListener('click', () => {
    if (currentStep > 0) {
      currentStep -= 1;
      showStep(currentStep);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  const draftStorageKey = 'da-hris-register-draft-v1';

  const spouseList = document.getElementById('spouseList');
  const addSpouseBtn = document.getElementById('addSpouseBtn');
  let spouseIndex = 1;

  const childrenList = document.getElementById('childrenList');
  const addChildBtn = document.getElementById('addChildBtn');
  let childIndex = 1;

  const educationList = document.getElementById('educationList');
  const addEducationBtn = document.getElementById('addEducationBtn');
  let educationIndex = 1;

  const saveDraft = () => {
    if (!registerForm) {
      return;
    }

    const sensitiveFields = new Set([
      'password',
      'confirm_password',
      'umid',
      'pagibig',
      'philhealth',
      'psn',
      'tin',
    ]);

    const values = {};
    const fields = registerForm.querySelectorAll('input[name], select[name], textarea[name]');

    fields.forEach((field) => {
      if (sensitiveFields.has(field.name)) {
        return;
      }
      values[field.name] = field.value;
    });

    const draft = {
      values,
      currentStep,
      updatedAt: new Date().toISOString(),
    };

    localStorage.setItem(draftStorageKey, JSON.stringify(draft));
  };

  const clearDraft = () => {
    localStorage.removeItem(draftStorageKey);
  };

  const debounce = (callback, delay = 250) => {
    let timeoutId;
    return (...args) => {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(() => callback(...args), delay);
    };
  };

  const addSpouseRow = (index) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'spouse-item border border-gray-200 rounded-lg p-4';

    const titleHtml = index === 0
      ? `<p class="font-semibold text-sm text-gray-700 mb-3">Spouse #1</p>`
      : `<div class="flex items-center justify-between mb-3">
          <p class="font-semibold text-sm text-gray-700">Spouse #${index + 1}</p>
          <button type="button" class="remove-spouse text-sm text-daRed font-semibold">Remove</button>
        </div>`;

    wrapper.innerHTML = `
      ${titleHtml}
      <div class="grid md:grid-cols-4 gap-3">
        <input type="text" name="spouses[${index}][surname]" placeholder="Spouse Surname" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][first_name]" placeholder="First Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][middle_name]" placeholder="Middle Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][extension]" placeholder="Name Extension" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][occupation]" placeholder="Occupation" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][employer]" placeholder="Employer/Business Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
        <input type="text" name="spouses[${index}][telephone]" placeholder="Telephone No." class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${index}][business_address]" placeholder="Business Address" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-4">
      </div>
    `;

    const removeBtn = wrapper.querySelector('.remove-spouse');
    removeBtn?.addEventListener('click', () => {
      wrapper.remove();
      saveDraft();
    });

    spouseList.appendChild(wrapper);
  };

  const addChildRow = (index) => {
    const row = document.createElement('div');
    row.className = 'grid md:grid-cols-3 gap-3 child-item';
    row.innerHTML = `
      <input type="text" name="children[${index}][name]" placeholder="Child Full Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
      <input type="date" name="children[${index}][birth_date]" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    `;

    childrenList.appendChild(row);
  };

  const addEducationRow = (index) => {
    const row = document.createElement('div');
    row.className = 'education-item grid md:grid-cols-12 gap-3 border border-gray-200 rounded-lg p-3';
    row.innerHTML = `
      <select name="education[${index}][level]" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <option value="">Level</option>
        <option>Elementary</option>
        <option>Secondary</option>
        <option>Vocational/Trade Course</option>
        <option>College</option>
        <option>Graduate Studies</option>
      </select>
      <input type="text" name="education[${index}][school]" placeholder="Name of School" class="md:col-span-3 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][course]" placeholder="Degree/Course" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][from]" placeholder="From" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][to]" placeholder="To" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][highest_level]" placeholder="Highest Level" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][year_graduated]" placeholder="Year Graduated" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${index}][honors]" placeholder="Honors" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    `;

    educationList.appendChild(row);
  };

  addSpouseBtn?.addEventListener('click', () => {
    addSpouseRow(spouseIndex);
    spouseIndex += 1;
    saveDraft();
  });

  addChildBtn?.addEventListener('click', () => {
    addChildRow(childIndex);
    childIndex += 1;
    saveDraft();
  });

  addEducationBtn?.addEventListener('click', () => {
    addEducationRow(educationIndex);
    educationIndex += 1;
    saveDraft();
  });

  const restoreDraft = () => {
    const rawDraft = localStorage.getItem(draftStorageKey);
    if (!rawDraft || !registerForm) {
      return;
    }

    try {
      const parsedDraft = JSON.parse(rawDraft);
      const values = parsedDraft?.values || {};

      const spouseIndexes = Object.keys(values)
        .map((key) => key.match(/^spouses\[(\d+)\]/))
        .filter(Boolean)
        .map((match) => Number(match[1]));
      const childIndexes = Object.keys(values)
        .map((key) => key.match(/^children\[(\d+)\]/))
        .filter(Boolean)
        .map((match) => Number(match[1]));
      const educationIndexes = Object.keys(values)
        .map((key) => key.match(/^education\[(\d+)\]/))
        .filter(Boolean)
        .map((match) => Number(match[1]));

      const maxSpouse = spouseIndexes.length ? Math.max(...spouseIndexes) : 0;
      const maxChild = childIndexes.length ? Math.max(...childIndexes) : 0;
      const maxEducation = educationIndexes.length ? Math.max(...educationIndexes) : 0;

      for (let index = 1; index <= maxSpouse; index += 1) {
        addSpouseRow(index);
      }
      for (let index = 1; index <= maxChild; index += 1) {
        addChildRow(index);
      }
      for (let index = 1; index <= maxEducation; index += 1) {
        addEducationRow(index);
      }

      spouseIndex = maxSpouse + 1;
      childIndex = maxChild + 1;
      educationIndex = maxEducation + 1;

      Object.entries(values).forEach(([name, value]) => {
        const field = registerForm.elements.namedItem(name);
        if (!field) {
          return;
        }

        if (field instanceof RadioNodeList) {
          field.value = value;
          return;
        }

        field.value = value;
      });

      if (Number.isInteger(parsedDraft.currentStep)) {
        currentStep = Math.max(0, Math.min(parsedDraft.currentStep, panels.length - 1));
      }

      draftRestoredNotice?.classList.remove('hidden');
    } catch (error) {
      localStorage.removeItem(draftStorageKey);
    }
  };

  const debouncedSaveDraft = debounce(saveDraft, 250);
  registerForm?.addEventListener('input', debouncedSaveDraft);
  registerForm?.addEventListener('change', debouncedSaveDraft);

  registerForm?.addEventListener('submit', (event) => {
    if (!validateCurrentStep()) {
      event.preventDefault();
      return;
    }

    clearDraft();
  });

  restoreDraft();
  showStep(currentStep);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
