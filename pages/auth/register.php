<?php
$pageTitle = 'Sign Up | DA HRIS';

ob_start();
?>

<div class="w-full max-w-6xl bg-white rounded-xl shadow-lg p-8 my-4">

  <a href="login.php"
     class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600
            hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back to Login
  </a>

  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">
      Create an Account
    </h1>
    <p class="text-sm text-gray-600">
      Complete the required fields based on the Personal Data Sheet (CS Form No. 212).
    </p>
  </div>

  <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3
              text-sm text-blue-800 flex gap-2">
    <span class="material-icons text-sm">info</span>
    You may add multiple spouse entries when applicable to your religion or ethnicity.
  </div>

  <form class="space-y-8">

    <section class="border border-gray-200 rounded-xl p-5">
      <h2 class="text-lg font-bold text-gray-900 mb-4">I. Personal Information</h2>

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
        <div>
          <label class="block text-sm font-medium mb-1">Mobile No.</label>
          <input type="text" name="mobile" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">E-mail Address (if any)</label>
          <input type="email" name="email" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
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

    <section class="border border-gray-200 rounded-xl p-5">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">II. Family Background</h2>
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

    <section class="border border-gray-200 rounded-xl p-5">
      <div class="flex items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">III. Educational Background</h2>
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
    </section>

    <section class="border border-gray-200 rounded-xl p-5">
      <h2 class="text-lg font-bold text-gray-900 mb-4">Account Credentials</h2>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Role</label>
          <select name="account_role" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
            <option value="">Select role</option>
            <option>Employee</option>
            <option>Supervisor</option>
            <option>HR Officer</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Employee ID / Reference No.</label>
          <input type="text" name="employee_reference" required class="w-full px-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
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

      <div class="pt-4">
        <button
          type="submit"
          class="w-full bg-daGreen text-white py-3 rounded-lg
               font-semibold hover:bg-green-700 transition
               flex items-center justify-center gap-2">
          <span class="material-icons text-sm">person_add</span>
          Sign Up
        </button>
      </div>
    </section>

    <div class="text-xs text-gray-500 text-center">
      This registration form is patterned after the Personal Data Sheet and may require verification by HR.
    </div>

  </form>

  <p class="mt-6 text-sm text-center text-gray-600">
    Already have an account?
    <a href="login.php" class="text-daGreen font-medium hover:underline">Sign In</a>
  </p>

</div>

<script>
  const spouseList = document.getElementById('spouseList');
  const addSpouseBtn = document.getElementById('addSpouseBtn');
  let spouseIndex = 1;

  addSpouseBtn?.addEventListener('click', () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'spouse-item border border-gray-200 rounded-lg p-4';
    wrapper.innerHTML = `
      <div class="flex items-center justify-between mb-3">
        <p class="font-semibold text-sm text-gray-700">Spouse #${spouseIndex + 1}</p>
        <button type="button" class="remove-spouse text-sm text-daRed font-semibold">Remove</button>
      </div>
      <div class="grid md:grid-cols-4 gap-3">
        <input type="text" name="spouses[${spouseIndex}][surname]" placeholder="Spouse Surname" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][first_name]" placeholder="First Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][middle_name]" placeholder="Middle Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][extension]" placeholder="Name Extension" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][occupation]" placeholder="Occupation" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][employer]" placeholder="Employer/Business Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
        <input type="text" name="spouses[${spouseIndex}][telephone]" placeholder="Telephone No." class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <input type="text" name="spouses[${spouseIndex}][business_address]" placeholder="Business Address" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-4">
      </div>
    `;
    spouseList.appendChild(wrapper);
    spouseIndex += 1;

    wrapper.querySelector('.remove-spouse')?.addEventListener('click', () => {
      wrapper.remove();
    });
  });

  const childrenList = document.getElementById('childrenList');
  const addChildBtn = document.getElementById('addChildBtn');
  let childIndex = 1;

  addChildBtn?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'grid md:grid-cols-3 gap-3 child-item';
    row.innerHTML = `
      <input type="text" name="children[${childIndex}][name]" placeholder="Child Full Name" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen md:col-span-2">
      <input type="date" name="children[${childIndex}][birth_date]" class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    `;
    childrenList.appendChild(row);
    childIndex += 1;
  });

  const educationList = document.getElementById('educationList');
  const addEducationBtn = document.getElementById('addEducationBtn');
  let educationIndex = 1;

  addEducationBtn?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'education-item grid md:grid-cols-12 gap-3 border border-gray-200 rounded-lg p-3';
    row.innerHTML = `
      <select name="education[${educationIndex}][level]" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
        <option value="">Level</option>
        <option>Elementary</option>
        <option>Secondary</option>
        <option>Vocational/Trade Course</option>
        <option>College</option>
        <option>Graduate Studies</option>
      </select>
      <input type="text" name="education[${educationIndex}][school]" placeholder="Name of School" class="md:col-span-3 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][course]" placeholder="Degree/Course" class="md:col-span-2 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][from]" placeholder="From" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][to]" placeholder="To" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][highest_level]" placeholder="Highest Level" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][year_graduated]" placeholder="Year Graduated" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
      <input type="text" name="education[${educationIndex}][honors]" placeholder="Honors" class="md:col-span-1 w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-daGreen">
    `;
    educationList.appendChild(row);
    educationIndex += 1;
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/auth-layout.php';
