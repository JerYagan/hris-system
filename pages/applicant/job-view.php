<?php
$pageTitle = 'Job Details | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings', 'Job Details'];

ob_start();

/* ===== SIMULATED DATA (replace with DB later) ===== */
$jobId = 'DA-ATI-001';
$deadline = '2026-03-15';
$today = date('Y-m-d');

$isDeadlinePassed = $today > $deadline;
$alreadyApplied = false;
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="job-list.php" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to listings
            </a>

            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">work</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Administrative Aide</h1>
                    <p class="text-sm text-gray-500">Agricultural Training Institute – Central Office</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 font-medium text-green-700">Contractual</span>
                        <span class="inline-flex rounded-full border bg-white px-2.5 py-1 text-gray-600">Salary Grade 4</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border bg-gray-50 px-4 py-3 text-sm">
            <p class="text-gray-500">Application Deadline</p>
            <p class="font-semibold text-gray-800">March 15, 2026</p>
            <p class="mt-1 inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700">31 days remaining</p>
        </div>
    </div>
</section>

<section class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-6 py-4">
                <span class="material-symbols-outlined text-green-700">description</span>
                <h2 class="text-lg font-semibold text-gray-800">Job Description</h2>
            </header>

            <div class="space-y-3 p-6 text-sm text-gray-700">
                <p>
                    The Administrative Aide provides clerical and administrative support
                    to ensure efficient office operations within the Agricultural Training Institute.
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Prepare and maintain office documents and records</li>
                    <li>Assist in data encoding and filing of HR documents</li>
                    <li>Coordinate with staff regarding administrative concerns</li>
                    <li>Perform other related duties as assigned</li>
                </ul>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-6 py-4">
                <span class="material-symbols-outlined text-green-700">checklist</span>
                <h2 class="text-lg font-semibold text-gray-800">Qualifications</h2>
            </header>

            <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-2">
                <div>
                    <p class="mb-2 font-medium text-gray-800">Minimum Requirements</p>
                    <ul class="list-disc space-y-1 pl-5 text-gray-700">
                        <li>High School Graduate or equivalent</li>
                        <li>Basic computer literacy</li>
                        <li>Good communication skills</li>
                    </ul>
                </div>

                <div>
                    <p class="mb-2 font-medium text-gray-800">Preferred Qualifications</p>
                    <ul class="list-disc space-y-1 pl-5 text-gray-700">
                        <li>Experience in clerical or administrative work</li>
                        <li>Familiarity with government office procedures</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <header class="flex items-center gap-2 border-b px-6 py-4">
                <span class="material-symbols-outlined text-green-700">folder</span>
                <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
            </header>

            <div class="p-6 text-sm text-gray-700">
                <ul class="list-disc space-y-1 pl-5">
                    <li>Application Letter</li>
                    <li>Updated Resume / Personal Data Sheet</li>
                    <li>Transcript of Records or Diploma</li>
                    <li>Valid Government ID</li>
                </ul>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-xl border bg-white">
            <header class="border-b px-6 py-4">
                <h3 class="font-semibold text-gray-800">Position Snapshot</h3>
            </header>
            <div class="space-y-3 p-6 text-sm">
                <div>
                    <p class="text-gray-500">Office / Department</p>
                    <p class="font-medium text-gray-800">Agricultural Training Institute</p>
                </div>
                <div>
                    <p class="text-gray-500">Reference ID</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($jobId, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Deadline</p>
                    <p class="font-medium text-gray-800">March 15, 2026</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border bg-white">
            <div class="p-6">
                <p class="text-sm text-gray-600">Please ensure all required documents are complete before submission.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="apply.php" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                        <span class="material-symbols-outlined text-sm">edit_document</span>
                        Apply for this Position
                    </a>
                    <a href="job-list.php" class="inline-flex items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        Back to Listings
                    </a>
                </div>
            </div>
        </section>
    </aside>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
