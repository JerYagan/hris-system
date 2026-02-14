<?php
$pageTitle = 'Recruitment | Admin';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Recruitment Management</h1>
        <p class="text-sm text-slate-300 mt-2">Manage hiring posts, application periods, and screening officer assignments.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Job Listings</h2>
            <p class="text-sm text-slate-500 mt-1">Overview of open and archived postings for all hiring departments.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="applicants.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Applicants</a>
            <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
            <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Create New Listing</button>
        </div>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Applicants</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Last Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Administrative Aide VI</td>
                    <td class="px-4 py-3">Human Resource Division</td>
                    <td class="px-4 py-3">18</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Open</span></td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">IT Officer I</td>
                    <td class="px-4 py-3">Management Information Systems</td>
                    <td class="px-4 py-3">12</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Closing Soon</span></td>
                    <td class="px-4 py-3">Feb 11, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Records Officer II</td>
                    <td class="px-4 py-3">Records Unit</td>
                    <td class="px-4 py-3">0</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Archived</span></td>
                    <td class="px-4 py-3">Feb 10, 2026</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Create / Edit / Archive Job Posts</h2>
        <p class="text-sm text-slate-500 mt-1">Publish new job posts, revise posting details, or archive filled plantilla slots.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Position Title</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter position title">
        </div>
        <div>
            <label class="text-slate-600">Department</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter department">
        </div>
        <div>
            <label class="text-slate-600">Employment Type</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Permanent</option>
                <option>Contractual</option>
                <option>Job Order</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Action</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Create New Post</option>
                <option>Edit Existing Post</option>
                <option>Archive Post</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Qualification Summary</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add education, eligibility, training, and experience requirements"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Clear</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Job Post</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Set Application Periods</h2>
        <p class="text-sm text-slate-500 mt-1">Configure opening and closing windows for each active posting.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Job Post</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Administrative Aide VI</option>
                <option>IT Officer I</option>
                <option>Training Specialist I</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Application Start</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Application End</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Set Period</button>
        </div>
    </form>

    <div class="px-6 pb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="rounded-lg border border-slate-200 p-4 bg-amber-50">
                <p class="text-xs uppercase text-amber-700">Closing in 2 days</p>
                <p class="font-medium text-slate-800 mt-2">IT Officer I</p>
                <p class="text-slate-600 mt-1">End Date: Feb 16, 2026</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-emerald-50">
                <p class="text-xs uppercase text-emerald-700">Ongoing</p>
                <p class="font-medium text-slate-800 mt-2">Administrative Aide VI</p>
                <p class="text-slate-600 mt-1">End Date: Feb 22, 2026</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs uppercase text-slate-600">Upcoming</p>
                <p class="font-medium text-slate-800 mt-2">Training Specialist I</p>
                <p class="text-slate-600 mt-1">Start Date: Feb 20, 2026</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Assign Screening Officer</h2>
        <p class="text-sm text-slate-500 mt-1">Designate responsible HR staff for first-level screening and applicant validation.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Job Post</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Administrative Aide VI</option>
                <option>IT Officer I</option>
                <option>Training Specialist I</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Screening Officer</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Maria Santos - HR Officer IV</option>
                <option>John Reyes - HR Officer III</option>
                <option>Aileen Cruz - HR Associate</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Assignment Note</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add screening scope, special instructions, or deadlines"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign Officer</button>
        </div>
    </form>

    <div class="px-6 pb-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Job Post</th>
                    <th class="text-left px-4 py-3">Assigned Officer</th>
                    <th class="text-left px-4 py-3">Assigned Date</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Administrative Aide VI</td>
                    <td class="px-4 py-3">Maria Santos</td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Assigned</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">IT Officer I</td>
                    <td class="px-4 py-3">John Reyes</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">In Progress</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
