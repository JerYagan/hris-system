<?php
$pageTitle = 'Request Access | DA HRIS';

ob_start();
?>

<div class="w-full max-w-xl bg-white rounded-xl shadow p-8 my-4">

  <!-- BACK -->
  <a href="login.php"
     class="inline-flex items-center gap-2 mb-6 text-sm text-gray-600
            hover:text-daGreen transition font-medium">
    <span class="material-icons text-base">arrow_back</span>
    Back to Login
  </a>

  <!-- HEADER -->
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">
      Request System Access
    </h1>
    <p class="text-sm text-gray-600">
      Submit a request to gain access to the
      <span class="font-medium">DA-ATI Human Resource Information System</span>.
    </p>
  </div>

  <!-- INFO NOTICE -->
  <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3
              text-sm text-blue-800 flex gap-2">
    <span class="material-icons text-sm">info</span>
    Access requests are reviewed and approved by HR administrators.
  </div>

  <!-- FORM -->
  <form class="space-y-4">

    <!-- FULL NAME -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Full Name
      </label>
      <input
        type="text"
        required
        placeholder="Juan Dela Cruz"
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <!-- EMAIL -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Official Email Address
      </label>
      <input
        type="email"
        required
        placeholder="juan.delacruz@da.gov.ph"
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <!-- OFFICE -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Office / Unit
      </label>
      <input
        type="text"
        required
        placeholder="ATI – Training Division"
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <!-- ROLE -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Requested Role
      </label>
      <select
        required
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen">
        <option value="">Select role</option>
        <option>Employee</option>
        <option>Supervisor</option>
        <option>HR Officer</option>
      </select>
    </div>

    <!-- EMPLOYEE ID -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Employee ID / Reference No. <span class="text-gray-400">(optional)</span>
      </label>
      <input
        type="text"
        placeholder="Optional"
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen">
    </div>

    <!-- REASON -->
    <div>
      <label class="block text-sm font-medium mb-1">
        Reason for Access
      </label>
      <textarea
        rows="3"
        required
        placeholder="Briefly state the reason for requesting access"
        class="w-full px-4 py-2.5 border rounded-lg
               focus:outline-none focus:ring-2 focus:ring-daGreen"></textarea>
    </div>

    <!-- SUBMIT -->
    <button
      type="submit"
      class="w-full bg-daGreen text-white py-3 rounded-lg
             font-semibold hover:bg-daGreenLight transition
             flex items-center justify-center gap-2">
      <span class="material-icons text-sm">send</span>
      Submit Request
    </button>

  </form>

  <!-- FOOTER NOTE -->
  <p class="mt-6 text-xs text-gray-500 text-center">
    You will be notified via your official email once your request has been reviewed.
  </p>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/auth-layout.php';
