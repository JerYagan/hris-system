<?php
ob_start();

// Simulated applicant state
$alreadyApplied = false;
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        work_outline
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Job Listings
        </h1>
        <p class="text-sm text-gray-500">
            Browse available job vacancies and apply online.
        </p>
    </div>
</div>

<!-- FILTER BAR (UI ONLY) -->
<section class="bg-white border rounded-lg mb-8">
    <div class="p-4 flex flex-wrap gap-3 items-center text-sm">

        <div class="flex items-center gap-2 border rounded-md px-3 py-2 w-full md:w-64">
            <span class="material-symbols-outlined text-gray-400 text-base">
                search
            </span>
            <input type="text"
                   placeholder="Search job title"
                   class="w-full outline-none text-gray-700">
        </div>

        <select class="border rounded-md px-3 py-2 text-gray-700">
            <option>All Categories</option>
            <option>Administrative</option>
            <option>Technical</option>
        </select>

        <select class="border rounded-md px-3 py-2 text-gray-700">
            <option>All Locations</option>
            <option>Central Office</option>
            <option>Regional Office</option>
        </select>

        <select class="border rounded-md px-3 py-2 text-gray-700">
            <option>Status</option>
            <option>Open</option>
            <option>Closing Soon</option>
            <option>Closed</option>
        </select>

    </div>
</section>

<!-- JOB CARDS -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- JOB CARD -->
    <article class="bg-white border rounded-lg p-6 flex flex-col justify-between">

        <!-- TOP -->
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Administrative Aide
                </h2>
                <p class="text-sm text-gray-500">
                    Agricultural Training Institute – Central Office
                </p>
            </div>

            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                Open
            </span>
        </div>

        <!-- META -->
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 mb-4">

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Employment Type
                </p>
                <p class="font-medium">
                    Contractual
                </p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Deadline
                </p>
                <p class="font-medium">
                    March 15, 2026
                </p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Salary Grade
                </p>
                <p class="font-medium">
                    SG 4
                </p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">
                    Job ID
                </p>
                <p class="font-medium">
                    DA-ATI-001
                </p>
            </div>

        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-between pt-4 border-t">

            <a href="job-view.php"
               class="inline-flex items-center gap-1 text-sm text-green-700 hover:underline">
                View Details
                <span class="material-symbols-outlined text-sm">
                    arrow_forward
                </span>
            </a>

            <?php if ($alreadyApplied): ?>
                <span class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-md bg-blue-100 text-blue-800">
                    <span class="material-symbols-outlined text-sm">
                        check_circle
                    </span>
                    Applied
                </span>
            <?php else: ?>
                <a href="apply.php"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">
                        edit_document
                    </span>
                    Apply
                </a>
            <?php endif; ?>

        </div>

    </article>

    <!-- CLOSING SOON -->
    <article class="bg-white border rounded-lg p-6 flex flex-col justify-between">

        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Training Specialist I
                </h2>
                <p class="text-sm text-gray-500">
                    ATI – Central Office
                </p>
            </div>

            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                Closing Soon
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm text-gray-700 mb-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Employment</p>
                <p class="font-medium">Permanent</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Deadline</p>
                <p class="font-medium">March 20, 2026</p>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t">
            <a href="job-view.php"
               class="text-sm text-green-700 hover:underline">
                View Details
            </a>
            <a href="apply.php"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                Apply
            </a>
        </div>

    </article>

    <!-- CLOSED -->
    <article class="bg-gray-50 border rounded-lg p-6 flex flex-col justify-between opacity-70">

        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Office Clerk
                </h2>
                <p class="text-sm text-gray-500">
                    Regional Office IV-A
                </p>
            </div>

            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                Closed
            </span>
        </div>

        <div class="text-sm text-gray-600 mb-4">
            Application period has ended.
        </div>

        <div class="flex items-center justify-end pt-4 border-t">
            <span class="text-sm text-gray-400">
                Applications Closed
            </span>
        </div>

    </article>

</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
