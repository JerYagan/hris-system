<?php
$pageTitle = 'Applicant Registration | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Applicant Registration'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Applicant Registration</h1>
    <p class="text-sm text-gray-500">Manage incoming applicants from initial review to evaluation endorsement.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">View Applications</h2>
        <p class="text-sm text-gray-500 mt-1">Review submitted applications and initial details.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Position Applied</th>
                    <th class="text-left px-4 py-3">Date Submitted</th>
                    <th class="text-left px-4 py-3">Application Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Administrative Aide</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">For Verification</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Open</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Training Specialist I</td>
                    <td class="px-4 py-3">Feb 11, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">For Verification</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Open</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Verify Uploaded Credentials</h2>
        <p class="text-sm text-gray-500 mt-1">Check submitted requirements before forwarding for evaluation.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Credential</th>
                    <th class="text-left px-4 py-3">Upload Status</th>
                    <th class="text-left px-4 py-3">Verification</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Resume, Transcript, ID</td>
                    <td class="px-4 py-3">Complete</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Verified</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Review</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Resume, ID</td>
                    <td class="px-4 py-3">Incomplete</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span></td>
                    <td class="px-4 py-3"><button class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Request Missing Docs</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Forward for Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">Endorse verified applicants to evaluators with remarks.</p>
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
            <label class="text-gray-600">Evaluator Group</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select evaluator group</option>
                <option>HR Evaluation Team</option>
                <option>Department Panel</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-gray-600">Staff Remarks</label>
            <textarea rows="3" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Add endorsement notes or verification remarks"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Save Draft</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Forward Applicant</button>
        </div>
    </form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
