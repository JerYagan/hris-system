<?php
$pageTitle = 'Job Listings | DA HRIS';
$activePage = 'job-list.php';
$breadcrumbs = ['Job Listings'];

ob_start();

// Simulated applicant state
$alreadyApplied = false;
?>

<!-- PAGE HEADER -->
<section class="mb-6 rounded-xl border bg-white p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined text-green-700 text-4xl">
                work_outline
            </span>
            <div>
                <p class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 mb-2">
                    Recruitment Openings
                </p>
                <h1 class="text-2xl font-semibold text-gray-800">
                    Job Listings
                </h1>
                <p class="text-sm text-gray-500">
                    Browse available vacancies and review requirements before applying.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-sm">
            <div class="rounded-lg border bg-gray-50 px-4 py-3 text-center">
                <p class="font-semibold text-gray-800">12</p>
                <p class="text-xs text-gray-500">Open Positions</p>
            </div>
            <div class="rounded-lg border bg-gray-50 px-4 py-3 text-center">
                <p class="font-semibold text-gray-800">3</p>
                <p class="text-xs text-gray-500">Closing This Week</p>
            </div>
        </div>
    </div>
</section>

<!-- FILTERS -->
<section class="bg-white border rounded-xl mb-6">
    <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <label class="block text-gray-500 mb-1">Search Position</label>
            <input type="text"
                   placeholder="Search job title"
                   class="w-full outline-none text-gray-700">
        </div>

        <div>
            <label class="block text-gray-500 mb-1">Office / Department</label>
            <select class="w-full border rounded-md px-3 py-2 focus:ring-1 focus:ring-green-600 focus:outline-none">
                <option>All</option>
                <option>Agricultural Training Institute</option>
                <option>Regional Office</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-500 mb-1">Employment Type</label>
            <select class="w-full border rounded-md px-3 py-2 focus:ring-1 focus:ring-green-600 focus:outline-none">
                <option>All</option>
                <option>Permanent</option>
                <option>Contractual</option>
                <option>Job Order</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button class="w-full bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800">
                Apply Filters
            </button>
        </div>
    </div>
</section>

<!-- JOB LIST TABLE -->
<section class="bg-white border rounded-xl overflow-hidden">
    <header class="px-6 py-4 border-b flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-green-700">
                list_alt
            </span>
            <h2 class="text-lg font-semibold text-gray-800">
                Available Positions
            </h2>
        </div>
        <p class="text-xs text-gray-500">Updated today</p>
    </header>

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

            <tbody class="divide-y">
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">Administrative Aide</p>
                        <p class="text-xs text-gray-500">Item No. ATI-2026-001</p>
                    </td>
                    <td class="px-6 py-4">Agricultural Training Institute</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                            Contractual
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-700">March 15, 2026</td>
                    <td class="px-6 py-4">
                        <a href="job-view.php"
                           class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-green-700 hover:bg-green-50">
                            View Details
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </td>
                </tr>

                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">Training Specialist I</p>
                        <p class="text-xs text-gray-500">Item No. ATI-2026-014</p>
                    </td>
                    <td class="px-6 py-4">ATI – Central Office</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                            Permanent
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-700">March 20, 2026</td>
                    <td class="px-6 py-4">
                        <a href="job-view.php"
                           class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-green-700 hover:bg-green-50">
                            View Details
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 py-3 border-t bg-gray-50 text-sm text-gray-600">
        Showing 2 out of 12 available job vacancies
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
