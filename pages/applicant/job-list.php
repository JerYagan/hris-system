<?php
$pageTitle = 'Job Listings | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings'];

ob_start();

// Simulated applicant state
$alreadyApplied = false;
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">work_outline</span>
                <div>
                    <p class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">Recruitment Openings</p>
                    <h1 class="mt-2 text-2xl font-semibold text-gray-800">Find Your Next Opportunity</h1>
                    <p class="mt-1 text-sm text-gray-600">Browse vacancies, compare deadlines, and open full job details before applying.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Open Positions</p>
                </div>
                <div class="rounded-lg border bg-white px-4 py-3 text-center">
                    <p class="font-semibold text-gray-800">3</p>
                    <p class="text-xs text-gray-500">Closing This Week</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border bg-white p-5">
    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
        <div>
            <label class="mb-1 block text-gray-500">Search Position</label>
            <input type="text" placeholder="Search job title" class="w-full rounded-md border px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-green-600">
        </div>

        <div>
            <label class="mb-1 block text-gray-500">Office / Department</label>
            <select class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-600">
                <option>All</option>
                <option>Agricultural Training Institute</option>
                <option>Regional Office</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-gray-500">Employment Type</label>
            <select class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-600">
                <option>All</option>
                <option>Permanent</option>
                <option>Contractual</option>
                <option>Job Order</option>
            </select>
        </div>

        <div class="flex items-end">
            <button class="w-full rounded-md bg-green-700 px-4 py-2 text-white hover:bg-green-800">Apply Filters</button>
        </div>
    </div>
</section>

<section class="rounded-xl border bg-white">
    <header class="flex items-center justify-between border-b px-6 py-4">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">list_alt</span>
            <h2 class="text-lg font-semibold text-gray-800">Available Positions</h2>
        </div>
        <p class="text-xs text-gray-500">Updated today</p>
    </header>

    <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-2">
        <article class="flex h-full flex-col rounded-xl border bg-gray-50 p-5">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">Administrative Aide</h3>
                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">Contractual</span>
                </div>
                <p class="mt-1 text-sm text-gray-600">Agricultural Training Institute – Central Office</p>
                <p class="mt-1 text-xs text-gray-500">Item No. ATI-2026-001</p>

                <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1 rounded-full border bg-white px-2.5 py-1">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        Deadline: March 15, 2026
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border bg-white px-2.5 py-1">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        ATI Central Office
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="job-view.php" class="inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                    View Details
                </a>
                <a href="apply.php" class="inline-flex items-center gap-1 rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">edit_document</span>
                    Apply Now
                </a>
            </div>
        </article>

        <article class="flex h-full flex-col rounded-xl border p-5">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-800">Training Specialist I</h3>
                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">Permanent</span>
                </div>
                <p class="mt-1 text-sm text-gray-600">ATI – Central Office</p>
                <p class="mt-1 text-xs text-gray-500">Item No. ATI-2026-014</p>

                <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1 rounded-full border bg-white px-2.5 py-1">
                        <span class="material-symbols-outlined text-sm">schedule</span>
                        Deadline: March 20, 2026
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="job-view.php" class="inline-flex items-center gap-1 rounded-md border px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                    View Details
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </article>
    </div>

    <div class="border-t bg-gray-50 px-6 py-3 text-sm text-gray-600">
        Showing 2 out of 12 available job vacancies
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
