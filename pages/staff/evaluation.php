<?php
$pageTitle = 'Evaluation Module | Staff';
$activePage = 'recruitment.php';
$breadcrumbs = ['Recruitment', 'Evaluation'];

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Evaluation (Decision Tree Algorithm)</h1>
    <p class="text-sm text-gray-500">Process applicant evaluations from data input to final HR endorsement.</p>
</div>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Input Applicant Data for Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">Provide applicant records and scoring inputs for system assessment.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Applicant</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Select applicant</option>
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Position</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Administrative Aide</option>
                <option>Training Specialist I</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Qualification Match (%)</label>
            <input type="number" min="0" max="100" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="0-100">
        </div>
        <div>
            <label class="text-gray-600">Interview Score</label>
            <input type="number" min="1" max="100" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="1-100">
        </div>

        <div class="lg:col-span-4 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Evaluation Input</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Generate System Recommendation</h2>
            <p class="text-sm text-gray-500 mt-1">Run rule-based decision support to classify applicant suitability.</p>
        </div>
        <button class="px-4 py-2 rounded-md bg-green-700 text-white text-sm hover:bg-green-800">Run Recommendation</button>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Applicant</p>
            <p class="font-medium text-gray-800 mt-2">Ana Dela Cruz</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">System Result</p>
            <p class="font-medium text-green-700 mt-2">Highly Recommended</p>
        </div>
        <div class="rounded-lg border p-4">
            <p class="text-xs uppercase text-gray-500">Confidence Score</p>
            <p class="font-medium text-gray-800 mt-2">92%</p>
        </div>
    </div>
</section>

<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Add HR Remarks</h2>
        <p class="text-sm text-gray-500 mt-1">Attach HR interpretation and justification for the recommendation.</p>
    </header>

    <form class="p-6 text-sm">
        <label class="text-gray-600">HR Remarks</label>
        <textarea rows="4" class="w-full mt-1 border rounded-md px-3 py-2" placeholder="Enter HR remarks and observations"></textarea>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Save Remarks</button>
        </div>
    </form>
</section>

<section class="bg-white border rounded-xl">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">Submit Final Evaluation</h2>
        <p class="text-sm text-gray-500 mt-1">Finalize and forward evaluation decision for approval workflow.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-gray-600">Final Decision</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Recommended</option>
                <option>For Further Review</option>
                <option>Not Recommended</option>
            </select>
        </div>
        <div>
            <label class="text-gray-600">Forward To</label>
            <select class="w-full mt-1 border rounded-md px-3 py-2">
                <option>Recruitment Head</option>
                <option>HR Manager</option>
            </select>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">Save Draft</button>
            <button class="px-5 py-2 rounded-md bg-green-700 text-white hover:bg-green-800">Submit Final Evaluation</button>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
