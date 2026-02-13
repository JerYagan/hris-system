<?php
$pageTitle = 'Personal Information | Staff';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Personal Information</h1>
    <p class="text-sm text-gray-500">Manage employee profiles, update records, and maintain employee status.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Employee Profiles</h2>
            <p class="text-sm text-gray-500">Profile list for employees managed by staff users.</p>
        </div>
        <button class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Add Employee</button>
    </header>

    <div class="px-6 py-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee Name</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">HR Officer</td>
                    <td class="px-4 py-3">Human Resources</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span></td>
                    <td class="px-4 py-3"><a href="#" class="text-green-700 hover:underline">Update</a></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">Admin Aide</td>
                    <td class="px-4 py-3">Administration</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">On Leave</span></td>
                    <td class="px-4 py-3"><a href="#" class="text-green-700 hover:underline">Update</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Add / Update Employee Information</h2>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Full Name</label>
            <input type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter employee full name">
        </div>
        <div>
            <label class="text-gray-600">Position</label>
            <input type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter position">
        </div>
        <div>
            <label class="text-gray-600">Department</label>
            <input type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter department">
        </div>
        <div>
            <label class="text-gray-600">Email Address</label>
            <input type="email" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter email address">
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Information</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Search Employee Records</h2>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <input type="text" placeholder="Search by name / ID" class="md:col-span-2 border rounded-md px-3 py-2">
        <select class="border rounded-md px-3 py-2">
            <option>All Departments</option>
            <option>Human Resources</option>
            <option>Administration</option>
        </select>
        <button class="px-4 py-2 rounded-md border text-gray-700 hover:bg-gray-50">Search</button>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Manage Employee Status</h2>
        <p class="text-sm text-gray-500 mt-1">Set employee status as Active, On Leave, or Resigned.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Current Status</th>
                    <th class="text-left px-4 py-3">Update Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Active</td>
                    <td class="px-4 py-3">
                        <select class="border rounded-md px-3 py-1.5">
                            <option>Active</option>
                            <option>On Leave</option>
                            <option>Resigned</option>
                        </select>
                    </td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Apply</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">John Cruz</td>
                    <td class="px-4 py-3">On Leave</td>
                    <td class="px-4 py-3">
                        <select class="border rounded-md px-3 py-1.5">
                            <option>On Leave</option>
                            <option>Active</option>
                            <option>Resigned</option>
                        </select>
                    </td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Apply</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
