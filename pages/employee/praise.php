<?php
/**
 * Performance Management (PRAISE)
 * DA-ATI HRIS
 */

$pageTitle = 'PRAISE | DA HRIS';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Performance Management (PRAISE)</h1>
  <p class="text-sm text-gray-500">
    Program on Awards and Incentives for Service Excellence
  </p>
</div>

<!-- PRAISE FLOW CARDS -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

  <!-- SELF EVALUATION -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex-1">
      <div class="flex items-center gap-3 mb-3">
        <span class="material-icons text-daGreen">assignment</span>
        <h2 class="font-semibold">Self-Evaluation</h2>
      </div>

      <p class="text-sm text-gray-600">
        Submit your self-assessment based on performance criteria defined by the agency.
      </p>
    </div>

    <div class="pt-4">
      <button
        data-open-self-eval
        class="w-full px-4 py-2 bg-daGreen text-white rounded-lg text-sm">
        Submit Self-Evaluation
      </button>
    </div>
  </div>

  <!-- SUPERVISOR EVALUATION -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex-1">
      <div class="flex items-center gap-3 mb-3">
        <span class="material-icons text-blue-600">rate_review</span>
        <h2 class="font-semibold">Supervisor Evaluation</h2>
      </div>

      <p class="text-sm text-gray-600">
        View performance evaluations submitted by your immediate supervisor.
      </p>
    </div>

    <div class="pt-4">
      <button
        data-open-supervisor-eval
        class="w-full px-4 py-2 border border-blue-600 text-blue-600 rounded-lg text-sm">
        View Evaluations
      </button>
    </div>
  </div>

  <!-- AWARDS -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex-1">
      <div class="flex items-center gap-3 mb-3">
        <span class="material-icons text-yellow-600">emoji_events</span>
        <h2 class="font-semibold">Awards and Recognition</h2>
      </div>

      <p class="text-sm text-gray-600">
        View commendations, incentives, and recognitions awarded under the PRAISE program.
      </p>
    </div>

    <div class="pt-4">
      <button
        data-open-awards
        class="w-full px-4 py-2 border border-yellow-600 text-yellow-700 rounded-lg text-sm">
        View Awards
      </button>
    </div>
  </div>

  <!-- INTERNAL RECRUITMENT -->
  <div class="bg-white rounded-xl shadow p-6 flex flex-col">
    <div class="flex-1">
      <div class="flex items-center gap-3 mb-3">
        <span class="material-icons text-purple-600">work</span>
        <h2 class="font-semibold">Internal Recruitment</h2>
      </div>

      <p class="text-sm text-gray-600">
        Explore internal job opportunities open for qualified employees.
      </p>
    </div>

    <div class="pt-4">
      <button
        class="w-full px-4 py-2 border border-purple-600 text-purple-600 rounded-lg text-sm">
        View Internal Job Opportunities
      </button>
    </div>
  </div>

</div>


<!-- INTERNAL APPLICATION STATUS -->
<div class="bg-white rounded-xl shadow">
  <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
      Internal Applicant <span class="text-daGreen">Status</span>
    </h2>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="px-6 py-3 text-left">Position</th>
          <th class="px-6 py-3 text-left">Office</th>
          <th class="px-6 py-3 text-left">Date Applied</th>
          <th class="px-6 py-3 text-left">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr>
          <td class="px-6 py-4">Administrative Officer II</td>
          <td class="px-6 py-4">HR Division</td>
          <td class="px-6 py-4">Jan 15, 2026</td>
          <td class="px-6 py-4">
            <span class="px-2 py-1 rounded-full text-xs bg-pending text-yellow-800">
              Under Review
            </span>
          </td>
        </tr>

        <tr>
          <td class="px-6 py-4">Planning Assistant</td>
          <td class="px-6 py-4">Planning Office</td>
          <td class="px-6 py-4">Nov 10, 2025</td>
          <td class="px-6 py-4">
            <span class="px-2 py-1 rounded-full text-xs bg-approved text-green-800">
              Shortlisted
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ================= MODALS ================= -->

<!-- SELF EVALUATION MODAL -->
<div id="selfEvalModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Self-Evaluation</h2>
      <button data-close-self-eval>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">
      <label class="block">
        <span class="text-gray-600">Work Quality (1–5)</span>
        <input type="number" min="1" max="5" class="w-full mt-1 border rounded-lg p-2">
      </label>

      <label class="block">
        <span class="text-gray-600">Productivity (1–5)</span>
        <input type="number" min="1" max="5" class="w-full mt-1 border rounded-lg p-2">
      </label>

      <label class="block">
        <span class="text-gray-600">Remarks</span>
        <textarea class="w-full mt-1 border rounded-lg p-2" rows="3"></textarea>
      </label>
    </div>

    <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
      <button data-close-self-eval class="border px-4 py-2 rounded-lg text-sm">
        Cancel
      </button>
      <button class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">
        Submit
      </button>
    </div>

  </div>
</div>

<!-- SUPERVISOR EVALUATION MODAL -->
<div id="supervisorEvalModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Supervisor Evaluation</h2>
      <button data-close-supervisor-eval>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-3 text-sm overflow-y-auto">
      <p><strong>Overall Rating:</strong> 4.7 / 5</p>
      <p><strong>Supervisor Remarks:</strong></p>
      <p class="text-gray-600">
        Demonstrates consistent professionalism and initiative.
      </p>
    </div>

    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button data-close-supervisor-eval class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<!-- AWARDS MODAL -->
<div id="awardsModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white w-full max-w-xl rounded-xl shadow-lg
              max-h-[90vh] flex flex-col">

    <div class="px-6 py-4 border-b flex justify-between items-center shrink-0">
      <h2 class="text-lg font-semibold">Awards & Recognition</h2>
      <button data-close-awards>
        <span class="material-icons">close</span>
      </button>
    </div>

    <div class="px-6 py-5 space-y-4 text-sm overflow-y-auto">

      <!-- AWARD ITEM -->
      <div class="flex items-start gap-4 p-4 border rounded-lg">
        <span class="material-icons text-yellow-600 text-3xl">
          emoji_events
        </span>

        <div class="flex-1">
          <p class="font-semibold">
            Best Employee Award
          </p>
          <p class="text-gray-500 text-xs">
            Awarded for outstanding overall performance
          </p>
        </div>

        <span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
          2025
        </span>
      </div>

      <!-- AWARD ITEM -->
      <div class="flex items-start gap-4 p-4 border rounded-lg">
        <span class="material-icons text-green-600 text-3xl">
          military_tech
        </span>

        <div class="flex-1">
          <p class="font-semibold">
            Service Excellence Award
          </p>
          <p class="text-gray-500 text-xs">
            Recognized for consistent quality service delivery
          </p>
        </div>

        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
          2024
        </span>
      </div>

    </div>


    <div class="px-6 py-4 border-t flex justify-end shrink-0">
      <button data-close-awards class="border px-4 py-2 rounded-lg text-sm">
        Close
      </button>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
