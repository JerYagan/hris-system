<?php
$pageTitle = 'Document Management | Staff';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Document Management</h1>
    <p class="text-sm text-gray-500">Manage employee credential uploads, verification progress, and digital signature checks.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Upload / Verify Employee Documents</h2>
        <p class="text-sm text-gray-500 mt-1">Receive submitted files and mark each document as verified or rejected.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Employee</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select employee</option>
                <option>Maria Santos</option>
                <option>John Cruz</option>
            </select>
        </div>

        <div>
            <label class="text-gray-600">Document Type</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select document type</option>
                <option>Employment Contract</option>
                <option>Government ID</option>
                <option>Medical Clearance</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="text-gray-600">Upload Document</label>
            <input type="file" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Mark as Rejected</button>
            <button type="button" class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Mark as Verified</button>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Document Verification Status</h2>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Document</th>
                    <th class="text-left px-4 py-3">Submitted Date</th>
                    <th class="text-left px-4 py-3">Verification Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Employment Contract</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Verified</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">Government ID</td>
                    <td class="px-4 py-3">Feb 11, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending Review</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Validate Digital Signature</h2>
        <p class="text-sm text-gray-500 mt-1">Run digital signature verification for critical employee files.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="md:col-span-2">
            <label class="text-gray-600">Signed Document</label>
            <input type="file" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>

        <div>
            <label class="text-gray-600">Signature Type</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>PKI Signature</option>
                <option>System Signature</option>
            </select>
        </div>

        <div class="md:col-span-3 flex items-center justify-between mt-2">
            <p class="text-gray-500">Last validation result: <span class="text-green-700 font-medium">Valid Signature</span></p>
            <button class="px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Validate Signature</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
