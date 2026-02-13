<?php
$pageTitle = 'Recruitment | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Recruitment</h1>
    <p class="text-sm text-gray-500">Manage job listings, vacancy updates, deadlines, and archival of filled positions.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Applicant Registration</h2>
            <p class="text-sm text-gray-500 mt-1">View applications, verify uploaded credentials, and forward qualified applicants for evaluation.</p>
        </div>
        <a href="applicant-registration.php" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Open Module</a>
    </header>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Applicant Tracking</h2>
            <p class="text-sm text-gray-500 mt-1">Monitor applicant progress, update statuses, record interview results, and forward for evaluation.</p>
        </div>
        <a href="applicant-tracking.php" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Open Module</a>
    </header>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Evaluation (Decision Tree Algorithm)</h2>
            <p class="text-sm text-gray-500 mt-1">Input applicant data, generate recommendation, add HR remarks, and submit final evaluation.</p>
        </div>
        <a href="evaluation.php" class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Open Module</a>
    </header>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Job Listings</h2>
            <p class="text-sm text-gray-500 mt-1">Overview of active vacancies and recruitment publication status.</p>
        </div>
        <button class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Create Vacancy</button>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Open Date</th>
                    <th class="text-left px-4 py-3">Deadline</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Administrative Aide</td>
                    <td class="px-4 py-3">Human Resources</td>
                    <td class="px-4 py-3">Feb 1, 2026</td>
                    <td class="px-4 py-3">Feb 28, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Open</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Training Specialist I</td>
                    <td class="px-4 py-3">Training Division</td>
                    <td class="px-4 py-3">Feb 5, 2026</td>
                    <td class="px-4 py-3">Mar 3, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Open</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Create / Edit Job Vacancies</h2>
        <p class="text-sm text-gray-500 mt-1">Add new vacancies or update existing listing details.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Position Title</label>
            <input type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter position title">
        </div>
        <div>
            <label class="text-gray-600">Department</label>
            <input type="text" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter department">
        </div>
        <div>
            <label class="text-gray-600">Opening Date</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Application Deadline</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Vacancy</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Application Deadlines</h2>
        <p class="text-sm text-gray-500 mt-1">Track upcoming and closing application schedules.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Closing in 3 days</p>
            <p class="font-medium text-gray-800 mt-2">Administrative Aide</p>
            <p class="text-gray-500 mt-1">Deadline: Feb 28, 2026</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Closing in 8 days</p>
            <p class="font-medium text-gray-800 mt-2">Training Specialist I</p>
            <p class="text-gray-500 mt-1">Deadline: Mar 3, 2026</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Upcoming posting</p>
            <p class="font-medium text-gray-800 mt-2">HR Assistant</p>
            <p class="text-gray-500 mt-1">Opens: Mar 5, 2026</p>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Archive Filled Positions</h2>
        <p class="text-sm text-gray-500 mt-1">Move completed recruitment postings into archive records.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Department</th>
                    <th class="text-left px-4 py-3">Filled Date</th>
                    <th class="text-left px-4 py-3">Archive</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Office Clerk</td>
                    <td class="px-4 py-3">Administration</td>
                    <td class="px-4 py-3">Feb 10, 2026</td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Archive</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Records Officer</td>
                    <td class="px-4 py-3">Records Unit</td>
                    <td class="px-4 py-3">Feb 7, 2026</td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md bg-green-700 text-white hover:bg-green-800">Archive</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
