<?php
$pageTitle = 'Applicants | Admin';
$activePage = 'applicants.php';
$breadcrumbs = ['Recruitment', 'Applicants'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Applicants</h1>
        <p class="text-sm text-slate-300 mt-2">Review registered applicants, finalize screening decisions, and validate system recommendations.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 flex items-center justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-slate-800">Need to manage interview movement and status progression?</p>
        <p class="text-xs text-slate-500 mt-1">Use Applicant Tracking for progress updates and Evaluation for rule-based recommendations.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="applicant-tracking.php" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open Applicant Tracking</a>
        <a href="evaluation.php" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Open Evaluation</a>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Applicant Overview</h2>
        <p class="text-sm text-slate-500 mt-1">Admin review flow for applicant screening decisions and recommendation validation.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-500 tracking-wide">Registered Applicants</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">46</p>
            <p class="text-xs text-slate-600 mt-1">Across active recruitment postings</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700 tracking-wide">Approved</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">21</p>
            <p class="text-xs text-slate-600 mt-1">Qualified for next recruitment stage</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700 tracking-wide">Pending Decision</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">8</p>
            <p class="text-xs text-slate-600 mt-1">Requires admin final screening action</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Registered Applicants</h2>
        <p class="text-sm text-slate-500 mt-1">Review applicants by posting, submission date, and screening status.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Applied Position</th>
                    <th class="text-left px-4 py-3">Date Submitted</th>
                    <th class="text-left px-4 py-3">Initial Screening</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Administrative Aide VI</td>
                    <td class="px-4 py-3">Feb 13, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">For Review</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Open Profile</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">IT Officer I</td>
                    <td class="px-4 py-3">Feb 12, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Verified</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Open Profile</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Lea Ramos</td>
                    <td class="px-4 py-3">Training Specialist I</td>
                    <td class="px-4 py-3">Feb 11, 2026</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Incomplete Docs</span></td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Open Profile</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve / Disqualify Applications</h2>
        <p class="text-sm text-slate-500 mt-1">Record admin screening decision with basis and remarks for audit trail.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Applicant</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Decision</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Approve for Next Stage</option>
                <option>Disqualify Application</option>
                <option>Return for Compliance</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Decision Date</label>
            <input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="text-slate-600">Basis</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Meets Minimum Qualification Standards</option>
                <option>Incomplete Documentary Requirements</option>
                <option>Did Not Meet Required Eligibility</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Admin Remarks</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="State summary of findings and justification for screening decision"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Decision</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View System Recommendation</h2>
        <p class="text-sm text-slate-500 mt-1">Review automated recommendation outputs and compare with admin decision.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">System Recommendation</th>
                    <th class="text-left px-4 py-3">Confidence</th>
                    <th class="text-left px-4 py-3">Admin Decision</th>
                    <th class="text-left px-4 py-3">Alignment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Recommend Proceed</span></td>
                    <td class="px-4 py-3">92%</td>
                    <td class="px-4 py-3">Approve for Next Stage</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Match</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Recommend Further Review</span></td>
                    <td class="px-4 py-3">74%</td>
                    <td class="px-4 py-3">Approve for Next Stage</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Override</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Lea Ramos</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-rose-100 text-rose-800">Recommend Disqualify</span></td>
                    <td class="px-4 py-3">89%</td>
                    <td class="px-4 py-3">Return for Compliance</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Partial Match</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
