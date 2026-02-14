<?php
$pageTitle = 'Evaluation | Admin';
$activePage = 'evaluation.php';
$breadcrumbs = ['Recruitment', 'Evaluation'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Evaluation (Rule-Based Algorithm)</h1>
        <p class="text-sm text-slate-300 mt-2">Configure criteria, run rule-based evaluation, and generate system recommendations.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Configure Evaluation Criteria</h2>
        <p class="text-sm text-slate-500 mt-1">Define scoring thresholds for education, experience, exam score, and interview rating.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Education Requirement</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Bachelor's Degree (Related Field)</option>
                <option>Any Bachelor's Degree</option>
                <option>Master's Degree Preferred</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Minimum Relevant Experience (Years)</label>
            <input type="number" min="0" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="2">
        </div>
        <div>
            <label class="text-slate-600">Minimum Exam Score (%)</label>
            <input type="number" min="0" max="100" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="75">
        </div>
        <div>
            <label class="text-slate-600">Minimum Interview Rating (1-5)</label>
            <input type="number" min="1" max="5" step="0.1" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="3.5">
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Rule Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add conditions for pass/fail, tie-breaking, and manual override scenarios"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Criteria</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Run Rule-Based Evaluation</h2>
            <p class="text-sm text-slate-500 mt-1">Apply criteria to applicants and compute qualification outcomes.</p>
        </div>
        <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Run Evaluation</button>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Applicant</th>
                    <th class="text-left px-4 py-3">Education</th>
                    <th class="text-left px-4 py-3">Experience</th>
                    <th class="text-left px-4 py-3">Exam</th>
                    <th class="text-left px-4 py-3">Interview</th>
                    <th class="text-left px-4 py-3">Rule Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Meets</td>
                    <td class="px-4 py-3">3 yrs</td>
                    <td class="px-4 py-3">84%</td>
                    <td class="px-4 py-3">4.2</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Pass</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Meets</td>
                    <td class="px-4 py-3">2 yrs</td>
                    <td class="px-4 py-3">73%</td>
                    <td class="px-4 py-3">3.8</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Conditional</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Lea Ramos</td>
                    <td class="px-4 py-3">Meets</td>
                    <td class="px-4 py-3">1 yr</td>
                    <td class="px-4 py-3">70%</td>
                    <td class="px-4 py-3">3.3</td>
                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-rose-100 text-rose-800">Fail</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Generate System Recommendations</h2>
        <p class="text-sm text-slate-500 mt-1">Produce recommendation output based on the latest rule evaluation run.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-2">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Recommended for Shortlist</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">12</p>
            <p class="text-xs text-slate-600 mt-1">Passed all rule thresholds</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Needs Manual Review</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">5</p>
            <p class="text-xs text-slate-600 mt-1">Borderline based on one criterion</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-rose-50">
            <p class="text-xs uppercase text-rose-700">Not Recommended</p>
            <p class="text-2xl font-bold text-slate-800 mt-2">9</p>
            <p class="text-xs text-slate-600 mt-1">Did not satisfy required rules</p>
        </article>
    </div>

    <div class="px-6 pb-6 flex justify-end">
        <button type="button" class="px-5 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Generate Recommendations</button>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
