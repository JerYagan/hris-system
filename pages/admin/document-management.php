<?php
$pageTitle = 'Document Management | Admin';
$activePage = 'document-management.php';
$breadcrumbs = ['Document Management'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Document Management</h1>
        <p class="text-sm text-slate-300 mt-2">Review uploaded records, maintain document lifecycle, and manage archive access.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve / Reject Uploaded Documents</h2>
        <p class="text-sm text-slate-500 mt-1">Validate newly uploaded files before they become available in official records.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Document Type</th>
                    <th class="text-left px-4 py-3">Submitted Date</th>
                    <th class="text-left px-4 py-3">File</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Service Record</td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Preview</button></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button type="button" class="px-3 py-1.5 rounded-md bg-emerald-700 text-white hover:bg-emerald-800">Approve</button>
                            <button type="button" class="px-3 py-1.5 rounded-md bg-rose-700 text-white hover:bg-rose-800">Reject</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Reyes</td>
                    <td class="px-4 py-3">Training Certificate</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Preview</button></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button type="button" class="px-3 py-1.5 rounded-md bg-emerald-700 text-white hover:bg-emerald-800">Approve</button>
                            <button type="button" class="px-3 py-1.5 rounded-md bg-rose-700 text-white hover:bg-rose-800">Reject</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View, Edit and Archive Documents</h2>
        <p class="text-sm text-slate-500 mt-1">Manage active records and move completed files to archive when needed.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Document ID</th>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Last Updated</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">DOC-2026-014</td>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Personal Data Sheet</td>
                    <td class="px-4 py-3">Feb 10, 2026</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View</button>
                            <button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Edit</button>
                            <button type="button" class="px-3 py-1.5 rounded-md bg-slate-900 text-white hover:bg-slate-800">Archive</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-3">DOC-2026-009</td>
                    <td class="px-4 py-3">Lea Ramos</td>
                    <td class="px-4 py-3">Eligibility Certificate</td>
                    <td class="px-4 py-3">Feb 09, 2026</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View</button>
                            <button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Edit</button>
                            <button type="button" class="px-3 py-1.5 rounded-md bg-slate-900 text-white hover:bg-slate-800">Archive</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Search Documents</h2>
        <p class="text-sm text-slate-500 mt-1">Find records quickly by document ID, employee name, or category.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Keyword</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by document ID, owner, or file name">
        </div>
        <div>
            <label class="text-slate-600">Category</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Categories</option>
                <option>Service Record</option>
                <option>Personal Data Sheet</option>
                <option>Training Certificate</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Status</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Statuses</option>
                <option>Active</option>
                <option>Pending Approval</option>
                <option>Archived</option>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-1">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Clear</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Search</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Edit Document Visibility</h2>
        <p class="text-sm text-slate-500 mt-1">Control which user groups can view specific document records.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Document</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>DOC-2026-014 - Personal Data Sheet</option>
                <option>DOC-2026-009 - Eligibility Certificate</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Visibility</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Admin and HR Staff</option>
                <option>Admin Only</option>
                <option>Admin, HR Staff, Employee</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Visibility Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add policy reason or access control notes"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Visibility</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Archive Documents</h2>
        <p class="text-sm text-slate-500 mt-1">Access archived records with reference details and archive metadata.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Document ID</th>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Archived Date</th>
                    <th class="text-left px-4 py-3">Archived By</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">ARC-2026-004</td>
                    <td class="px-4 py-3">Ramon Dela Torre</td>
                    <td class="px-4 py-3">Feb 05, 2026</td>
                    <td class="px-4 py-3">Admin User</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">ARC-2026-002</td>
                    <td class="px-4 py-3">Liza Mendoza</td>
                    <td class="px-4 py-3">Feb 02, 2026</td>
                    <td class="px-4 py-3">Admin User</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
