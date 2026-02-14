<?php
$pageTitle = 'PRAISE | Admin';
$activePage = 'praise.php';
$breadcrumbs = ['PRAISE'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">PRAISE</h1>
        <p class="text-sm text-slate-300 mt-2">Manage evaluation cycles, recognition workflows, and performance reporting.</p>
    </div>
</div>

<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <article class="bg-white border border-slate-200 rounded-2xl p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Evaluation</p>
        <h2 class="text-lg font-semibold text-slate-800 mt-2">Employee Evaluation Overview</h2>
        <p class="text-sm text-slate-500 mt-2">Set evaluation periods, approve supervisor ratings, and review performance results.</p>
        <a href="praise-employee-evaluation.php" class="mt-4 inline-flex px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open</a>
    </article>

    <article class="bg-white border border-slate-200 rounded-2xl p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Recognition</p>
        <h2 class="text-lg font-semibold text-slate-800 mt-2">Awards and Recognition</h2>
        <p class="text-sm text-slate-500 mt-2">Create categories, process nominations, and publish approved awardees.</p>
        <a href="praise-awards-recognition.php" class="mt-4 inline-flex px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open</a>
    </article>

    <article class="bg-white border border-slate-200 rounded-2xl p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Analytics</p>
        <h2 class="text-lg font-semibold text-slate-800 mt-2">Reports and Analytics</h2>
        <p class="text-sm text-slate-500 mt-2">View performance summaries and historical recognition records.</p>
        <a href="praise-reports-analytics.php" class="mt-4 inline-flex px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Open</a>
    </article>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
