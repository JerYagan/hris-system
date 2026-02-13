<?php
$pageTitle = 'Job Details | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings', 'Job Details'];

ob_start();
?>

<!-- PAGE HEADER -->
<section class="mb-6 rounded-xl border bg-white p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="job-list.php" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Listings
            </a>

            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined text-green-700 text-4xl">work</span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Administrative Aide</h1>
                    <p class="text-sm text-gray-500">Agricultural Training Institute – Central Office</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-gray-50 px-4 py-3 text-sm">
            <p class="text-gray-500">Application Deadline</p>
            <p class="font-semibold text-gray-800">March 15, 2026</p>
            <p class="mt-1 inline-flex rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700">
                31 days remaining
            </p>
        </div>
    </div>
</section>

<!-- JOB SUMMARY -->
<section class="bg-white border rounded-xl mb-6">
    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="text-gray-500">Employment Type</p>
            <p class="font-medium text-gray-800">Contractual</p>
        </div>
        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="text-gray-500">Salary Grade</p>
            <p class="font-medium text-gray-800">SG 4</p>
        </div>
        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="text-gray-500">Division</p>
            <p class="font-medium text-gray-800">Human Resource Unit</p>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <section class="xl:col-span-2 bg-white border rounded-xl">
        <header class="px-6 py-4 border-b flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">description</span>
            <h2 class="text-lg font-semibold text-gray-800">Job Description</h2>
        </header>

        <div class="p-6 text-sm text-gray-700 space-y-3">
            <p>
                The Administrative Aide provides clerical and administrative support
                to ensure efficient office operations within the Agricultural Training Institute.
            </p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Prepare and maintain office documents and records</li>
                <li>Assist in data encoding and filing of HR documents</li>
                <li>Coordinate with staff regarding administrative concerns</li>
                <li>Perform other related duties as assigned</li>
            </ul>
        </div>
    </section>

    <section class="bg-white border rounded-xl">
        <header class="px-6 py-4 border-b flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">folder</span>
            <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
        </header>

        <div class="p-6 text-sm text-gray-700">
            <ul class="space-y-2">
                <li class="flex items-start gap-2"><span class="material-symbols-outlined text-base text-green-700">check_circle</span>Application Letter</li>
                <li class="flex items-start gap-2"><span class="material-symbols-outlined text-base text-green-700">check_circle</span>Updated Resume / Personal Data Sheet</li>
                <li class="flex items-start gap-2"><span class="material-symbols-outlined text-base text-green-700">check_circle</span>Transcript of Records or Diploma</li>
                <li class="flex items-start gap-2"><span class="material-symbols-outlined text-base text-green-700">check_circle</span>Valid Government ID</li>
            </ul>
        </div>
    </section>
</div>

<!-- QUALIFICATIONS -->
<section class="bg-white border rounded-xl mb-6">
    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">checklist</span>
        <h2 class="text-lg font-semibold text-gray-800">Qualifications</h2>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div class="rounded-lg border p-4">
            <p class="font-medium text-gray-800 mb-2">Minimum Requirements</p>
            <ul class="list-disc pl-5 space-y-1 text-gray-700">
                <li>High School Graduate or equivalent</li>
                <li>Basic computer literacy</li>
                <li>Good communication skills</li>
            </ul>
        </div>

        <div class="rounded-lg border p-4">
            <p class="font-medium text-gray-800 mb-2">Preferred Qualifications</p>
            <ul class="list-disc pl-5 space-y-1 text-gray-700">
                <li>Experience in clerical or administrative work</li>
                <li>Familiarity with government office procedures</li>
            </ul>
        </div>
    </div>
</section>

<!-- ACTIONS -->
<section class="bg-white border rounded-xl">
    <div class="p-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
        <p class="text-sm text-gray-600">
            Ensure all required documents are complete and up to date before submitting your application.
        </p>

        <div class="flex gap-3">
            <a href="job-list.php"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md border text-gray-700 hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Listings
            </a>

            <a href="apply.php"
               class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">edit_document</span>
                Apply for this Position
            </a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
