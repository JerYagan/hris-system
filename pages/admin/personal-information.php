<?php
$pageTitle = 'Personal Information | Admin';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Personal Information</h1>
        <p class="text-sm text-slate-300 mt-2">Manage employee records, profile lifecycle, assignments, and status updates.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Search Employee with Filter</h2>
        <p class="text-sm text-slate-500 mt-1">Locate records quickly using name, department, status, and position filters.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div class="md:col-span-2">
            <label class="text-slate-600">Keyword</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee ID or name">
        </div>
        <div>
            <label class="text-slate-600">Department</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Departments</option>
                <option>Human Resource Division</option>
                <option>Management Information Systems</option>
                <option>Training Division</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Status</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Statuses</option>
                <option>Active</option>
                <option>Inactive</option>
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
        <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
        <p class="text-sm text-slate-500 mt-1">Maintain complete and updated personal details for all employee profiles.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Profiles</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">248</p>
            <p class="text-xs text-slate-600 mt-1">Across all active departments</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Complete Records</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">221</p>
            <p class="text-xs text-slate-600 mt-1">Profiles with full requirements</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase tracking-wide text-amber-700">Needs Update</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">27</p>
            <p class="text-xs text-slate-600 mt-1">Missing key personal details</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Profiles</h2>
        <p class="text-sm text-slate-500 mt-1">Central workspace for record viewing, editing, assignment, and employee status controls.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Active Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2">232</p>
            <p class="text-xs text-slate-500 mt-1">Profiles currently assigned and active</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase text-slate-500">Inactive Employees</p>
            <p class="text-xl font-semibold text-slate-800 mt-2">16</p>
            <p class="text-xs text-slate-500 mt-1">Archived, resigned, or suspended records</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View All Employee Records</h2>
        <p class="text-sm text-slate-500 mt-1">Review all employee profiles with position, department, and current status.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee ID</th>
                    <th class="text-left px-4 py-3">Full Name</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">EMP-0012</td>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Human Resource Division</td>
                    <td class="px-4 py-3">HR Assistant</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Active</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Open</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">EMP-0047</td>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Management Information Systems</td>
                    <td class="px-4 py-3">IT Officer I</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Active</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Open</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Add / Edit / Archive Employee Profile</h2>
        <p class="text-sm text-slate-500 mt-1">Create new records, modify profile details, or archive inactive employees.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee Name</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter full name">
        </div>
        <div>
            <label class="text-slate-600">Action</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Add Profile</option>
                <option>Edit Profile</option>
                <option>Archive Profile</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Email Address</label>
            <input type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter email">
        </div>
        <div>
            <label class="text-slate-600">Contact Number</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter mobile number">
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Profile Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add profile changes, archive reason, or admin notes"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Clear</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Profile</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Assign Department and Position</h2>
        <p class="text-sm text-slate-500 mt-1">Set or update official assignment details for each employee profile.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Department</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Human Resource Division</option>
                <option>Management Information Systems</option>
                <option>Training Division</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Position</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>HR Assistant</option>
                <option>IT Officer I</option>
                <option>Training Specialist I</option>
            </select>
        </div>
        <div class="md:col-span-3 flex justify-end mt-1">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Manage Employee Status (Active / Inactive)</h2>
        <p class="text-sm text-slate-500 mt-1">Update employment status with remarks for deactivation, transfer, or reactivation.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">New Status</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Active</option>
                <option>Inactive</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Status Specification</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Indicate reason (resigned, retired, on leave, reassigned, etc.)"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Status</button>
        </div>
    </form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
