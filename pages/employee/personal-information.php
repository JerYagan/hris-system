<?php
/**
 * Employee Personal Information
 * DA-ATI HRIS
 */

$pageTitle   = 'Personal Information | DA HRIS';
$activePage  = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Personal Information</h1>
  <p class="text-sm text-gray-500">
    Manage your personal information
  </p>
</div>

<!-- PERSONAL PROFILE -->
<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">
      Personal <span class="text-daGreen">Profile</span>
    </h2>

    <button
      data-open-profile
      class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">
      Edit Profile
    </button>
  </div>

  <div class="grid md:grid-cols-4 gap-4 text-sm">
    <div>
      <label class="text-gray-500">First Name</label>
      <input disabled value="Employee" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Last Name</label>
      <input disabled value="One" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Email Address</label>
      <input disabled value="employeeone@ati.com" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Phone Number</label>
      <input disabled value="+63912345678" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div class="md:col-span-2">
      <label class="text-gray-500">Address</label>
      <input disabled value="Elliptical Road, Diliman, Quezon City"
             class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Date of Birth</label>
      <input disabled value="1996-11-25" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>
  </div>
</section>

<!-- PERSONAL DOCUMENTS -->
<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">
      Personal <span class="text-daGreen">Documents</span>
    </h2>

    <button
      data-open-upload
      class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">
      Upload Documents
    </button>
  </div>

  <div class="space-y-4 text-sm">

    <!-- DOCUMENT ITEM -->
    <div class="flex items-center justify-between border rounded-lg px-4 py-3">
      <div class="flex items-center gap-3">
        <span class="material-icons text-gray-400">description</span>
        <div>
          <p class="font-medium">Driver’s License</p>
          <p class="text-xs text-gray-500">Uploaded on 2024-01-05</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="px-3 py-1 rounded-full bg-approved text-green-800">Approved</span>
        <button class="border px-4 py-1 rounded-lg text-sm">View</button>
      </div>
    </div>

    <div class="flex items-center justify-between border rounded-lg px-4 py-3">
      <div class="flex items-center gap-3">
        <span class="material-icons text-gray-400">description</span>
        <div>
          <p class="font-medium">Personal Data Sheet</p>
          <p class="text-xs text-gray-500">Uploaded on 2024-01-05</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="px-3 py-1 rounded-full bg-pending text-yellow-800">Pending</span>
        <button class="border px-4 py-1 rounded-lg text-sm">View</button>
      </div>
    </div>

  </div>
</section>

<!-- EMPLOYMENT DETAILS -->
<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-6">
    Employment <span class="text-daGreen">Details</span>
  </h2>

  <div class="grid md:grid-cols-4 gap-4 text-sm">
    <div>
      <label class="text-gray-500">Employee ID</label>
      <input disabled value="EMP-2024-001" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Job Title</label>
      <input disabled value="Administrative Officer II" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Division</label>
      <input disabled value="Human Resources Division" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>

    <div>
      <label class="text-gray-500">Supervisor</label>
      <input disabled value="Marisa Galgo" class="w-full mt-1 p-2 bg-gray-100 rounded-lg">
    </div>
  </div>
</section>

<!-- ================= MODALS ================= -->

<!-- EDIT PROFILE MODAL -->
<div
  id="profileModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Edit Profile</h2>
      <button data-close-profile>
        <span class="material-icons">close</span>
      </button>
    </div>

    <form class="grid grid-cols-2 gap-4 text-sm">
      <input class="border rounded-lg p-2 col-span-1" placeholder="First Name">
      <input class="border rounded-lg p-2 col-span-1" placeholder="Last Name">
      <input class="border rounded-lg p-2 col-span-2" placeholder="Email">
      <input class="border rounded-lg p-2 col-span-2" placeholder="Address">

      <div class="col-span-2 flex justify-end gap-3 pt-4">
        <button type="button" data-close-profile class="border px-4 py-2 rounded-lg">
          Cancel
        </button>
        <button class="bg-daGreen text-white px-4 py-2 rounded-lg">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- UPLOAD DOCUMENT MODAL -->
<div
  id="uploadModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Upload Document</h2>
      <button data-close-upload>
        <span class="material-icons">close</span>
      </button>
    </div>

    <form class="space-y-4 text-sm">
      <input class="w-full border rounded-lg p-2" placeholder="Document Name">
      <select class="w-full border rounded-lg p-2">
        <option>Personal</option>
        <option>Medical</option>
        <option>Government ID</option>
      </select>
      <input type="file" class="w-full">

      <div class="flex justify-end gap-3 pt-4">
        <button type="button" data-close-upload class="border px-4 py-2 rounded-lg">
          Cancel
        </button>
        <button class="bg-daGreen text-white px-4 py-2 rounded-lg">
          Upload
        </button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
