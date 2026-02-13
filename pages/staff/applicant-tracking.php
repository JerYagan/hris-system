<?php
$pageTitle = 'Applicant Tracking | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Tracking'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Tracking</h1>
    <p class="text-sm text-gray-500">Track applicant progress, update statuses, record interview outcomes, and prepare evaluation endorsements.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Monitor Application Progress</h2>
        <p class="text-sm text-gray-500 mt-1">View applicant pipeline status from submission to decision.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position</th>
                    <th class="text-left px-4 py-3">Current Stage</th>
                    <th class="text-left px-4 py-3">Last Update</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Administrative Aide</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Interview Scheduled</span></td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Training Specialist I</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">For Evaluation</span></td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Update Applicant Status</h2>
        <p class="text-sm text-gray-500 mt-1">Move applicants across recruitment stages with staff remarks.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Applicant</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select applicant</option>
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">New Status</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>For Screening</option>
                <option>Interview Scheduled</option>
                <option>For Evaluation</option>
                <option>For Final Decision</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-gray-600">Staff Notes</label>
            <textarea rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add status update notes"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Update Status</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Record Interview Results</h2>
        <p class="text-sm text-gray-500 mt-1">Capture interview outcomes and recommendations from interviewers.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Applicant</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select applicant</option>
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Interview Date</label>
            <input type="date" class="w-full mt-1 border rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-gray-600">Result</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Passed</option>
                <option>For Further Review</option>
                <option>Not Qualified</option>
            </select>
        </div>
        <div class="md:col-span-3">
            <label class="text-gray-600">Interview Remarks</label>
            <textarea rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add interview observations"></textarea>
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Interview Result</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Forward for Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">Submit shortlisted applicants for evaluator processing and final recommendation.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Applicant</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select applicant</option>
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Evaluator Team</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>HR Evaluation Team</option>
                <option>Department Panel</option>
            </select>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Save Draft</button>
            <button class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Forward to Evaluation</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
