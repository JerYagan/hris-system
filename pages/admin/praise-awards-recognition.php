<?php
$pageTitle = 'Awards and Recognition | Admin';
$activePage = 'praise-awards-recognition.php';
$breadcrumbs = ['PRAISE', 'Awards and Recognition'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Awards and Recognition</h1>
        <p class="text-sm text-slate-300 mt-2">Create award categories, approve nominations, and publish official awardees.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Create Award Categories</h2>
        <p class="text-sm text-slate-500 mt-1">Set up annual or periodic award tracks for employee recognition.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Category Name</label>
            <input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. Employee of the Quarter">
        </div>
        <div>
            <label class="text-slate-600">Category Period</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Quarterly</option>
                <option>Semi-Annual</option>
                <option>Annual</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Criteria Notes</label>
            <textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Define eligibility and selection criteria"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Category</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Nominations</h2>
        <p class="text-sm text-slate-500 mt-1">Review submitted nominations and finalize candidate list.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Nominee</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Nominated By</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Employee of the Quarter</td>
                    <td class="px-4 py-3">HR Division</td>
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

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Publish Awardees</h2>
            <p class="text-sm text-slate-500 mt-1">Release approved awardee list for official recognition announcements.</p>
        </div>
        <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Publish Awardees</button>
    </header>

    <div class="p-6 text-sm text-slate-600">
        Publishing actions should trigger announcement posting and notification workflows from your backend process.
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
