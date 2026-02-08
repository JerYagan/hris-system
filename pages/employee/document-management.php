<?php
/**
 * Employee Document Management
 * DA-ATI HRIS
 */

$pageTitle = 'Document Management | DA HRIS';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];

/**
 * Capture page-specific content
 */
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
  <h1 class="text-2xl font-bold">Document Management</h1>
  <p class="text-sm text-gray-500">
    Manage your documents with ease
  </p>
</div>

<!-- SEARCH + ACTION BAR -->
<div class="bg-white rounded-xl shadow p-6 flex flex-wrap gap-4 items-center justify-between mb-6">

  <!-- SEARCH -->
  <div class="relative flex-1 min-w-[250px]">
    <span class="material-icons absolute left-3 top-2.5 text-gray-400 text-sm">
      search
    </span>
    <input type="text" placeholder="Search documents..." class="w-full pl-9 pr-4 py-2 bg-gray-100 rounded-lg text-sm">
  </div>

  <!-- FILTER -->
  <select class="px-4 py-2 bg-gray-100 rounded-lg text-sm">
    <option>All Categories</option>
    <option>Leave</option>
    <option>Evaluation Report</option>
    <option>Certificates</option>
  </select>

  <!-- ACTION -->
<button
  data-open-upload
  class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">
  Upload Documents
</button>

</div>

<!-- DOCUMENT TABLE -->
<div class="bg-white rounded-xl shadow p-6">

  <h2 class="text-lg font-bold mb-6">
    Uploaded <span class="text-daGreen">Documents</span>
  </h2>

  <table class="w-full text-sm">
    <thead>
      <tr class="border-b text-gray-500">
        <th class="text-left py-3">Document Name</th>
        <th class="text-left py-3">Category</th>
        <th class="text-left py-3">Date Uploaded</th>
        <th class="text-left py-3">Status</th>
        <th class="text-left py-3">Action</th>
      </tr>
    </thead>

    <tbody>

      <!-- ROW -->
      <tr class="border-b">
        <td class="py-3">PerformanceReview_AntonioR.pdf</td>
        <td class="py-3">Evaluation Report</td>
        <td class="py-3">2025-08-13</td>
        <td class="py-3">
          <span class="px-3 py-1 rounded-full bg-pending text-yellow-800">
            Pending
          </span>
        </td>
        <td class="py-3 relative">
          <button data-action-toggle class="p-2 rounded-full hover:bg-gray-100">
            <span class="material-icons text-gray-500">more_vert</span>
          </button>


          <div class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg hidden action-menu z-20">

            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex gap-2">
              <span class="material-icons text-sm">visibility</span> View
            </button>

            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex gap-2">
              <span class="material-icons text-sm">upload_file</span> Re-upload
            </button>

            <button class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 flex gap-2">
              <span class="material-icons text-sm">cancel</span> Withdraw
            </button>

          </div>

        </td>
      </tr>

      <!-- ROW -->
      <tr class="border-b">
        <td class="py-3">Leave_Request.pdf</td>
        <td class="py-3">Leave</td>
        <td class="py-3">2025-07-06</td>
        <td class="py-3">
          <span class="px-3 py-1 rounded-full bg-approved text-green-800">
            Approved
          </span>
        </td>
        <td class="py-3 relative">
          <button data-action-toggle class="p-2 rounded-full hover:bg-gray-100">
            <span class="material-icons text-gray-500">more_vert</span>
          </button>


          <div class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg hidden action-menu z-20">

            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex gap-2">
              <span class="material-icons text-sm">visibility</span> View
            </button>

            <button class="w-full px-4 py-2 text-left hover:bg-gray-100 flex gap-2">
              <span class="material-icons text-sm">upload_file</span> Re-upload
            </button>

            <button class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 flex gap-2">
              <span class="material-icons text-sm">cancel</span> Withdraw
            </button>

          </div>

        </td>
      </tr>

    </tbody>
  </table>
</div>

<!-- UPLOAD MODAL -->
<div
  id="uploadModal"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">

  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold">Upload Document</h2>
      <button data-close-upload class="text-gray-400 hover:text-gray-600">
        <span class="material-icons">close</span>
      </button>
    </div>

    <!-- FORM (frontend-only for now) -->
    <form class="space-y-4">

      <div>
        <label class="text-sm font-medium">Document Name</label>
        <input
          type="text"
          class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"
          placeholder="e.g. Leave Request"
        >
      </div>

      <div>
        <label class="text-sm font-medium">Category</label>
        <select class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
          <option>Leave</option>
          <option>Evaluation Report</option>
          <option>Certificates</option>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium">Upload File</label>
        <input
          type="file"
          class="w-full mt-1 text-sm"
        >
      </div>

      <!-- ACTIONS -->
      <div class="flex justify-end gap-3 pt-4">
        <button
          type="button"
          data-close-upload
          class="px-4 py-2 text-sm rounded-lg border">
          Cancel
        </button>
        <button
          type="submit"
          class="px-4 py-2 text-sm rounded-lg bg-daGreen text-white">
          Upload
        </button>
      </div>

    </form>

  </div>
</div>

<?php
/**
 * Inject into global layout
 */
$content = ob_get_clean();
include '../../includes/layout.php';
