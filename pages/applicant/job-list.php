<?php
ob_start();
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
            Browse available job vacancies and view position details.
        </p>
    </div>
</div>

<!-- FILTERS -->
<section class="bg-white border rounded-lg mb-6">
    <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">

        <div>
            <label class="block text-gray-500 mb-1">Search Position</label>
            <input type="text"
                   placeholder="e.g. Administrative Aide"
                   class="w-full border rounded-md px-3 py-2 focus:ring-1 focus:ring-green-600 focus:outline-none">
        </div>

        <div>
            <label class="block text-gray-500 mb-1">Office / Department</label>
            <select class="w-full border rounded-md px-3 py-2">
                <option>All</option>
                <option>Agricultural Training Institute</option>
                <option>Regional Office</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-500 mb-1">Employment Type</label>
            <select class="w-full border rounded-md px-3 py-2">
                <option>All</option>
                <option>Permanent</option>
                <option>Contractual</option>
                <option>Job Order</option>
            </select>
        </div>

        <div class="flex items-end">
            <button class="w-full bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800">
                Apply Filters
            </button>
        </div>

    </div>
</section>

<!-- JOB LIST TABLE -->
<section class="bg-white border rounded-lg">

    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">
            list_alt
        </span>
        <h2 class="text-lg font-semibold text-gray-800">
            Available Positions
        </h2>
    </header>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-medium">Position</th>
                    <th class="px-6 py-3 text-left font-medium">Office / Department</th>
                    <th class="px-6 py-3 text-left font-medium">Employment Type</th>
                    <th class="px-6 py-3 text-left font-medium">Closing Date</th>
                    <th class="px-6 py-3 text-left font-medium">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">

                <!-- ROW -->
                <tr>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        Administrative Aide
                    </td>
                    <td class="px-6 py-4">
                        Agricultural Training Institute
                    </td>
                    <td class="px-6 py-4">
                        Contractual
                    </td>
                    <td class="px-6 py-4">
                        March 15, 2026
                    </td>
                    <td class="px-6 py-4">
                        <a href="job-view.php"
                           class="inline-flex items-center gap-1 text-green-700 hover:underline">
                            View Details
                            <span class="material-symbols-outlined text-sm">
                                arrow_forward
                            </span>
                        </a>
                    </td>
                </tr>

                <!-- ROW -->
                <tr>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        Training Specialist I
                    </td>
                    <td class="px-6 py-4">
                        ATI – Central Office
                    </td>
                    <td class="px-6 py-4">
                        Permanent
                    </td>
                    <td class="px-6 py-4">
                        March 20, 2026
                    </td>
                    <td class="px-6 py-4">
                        <a href="job-view.php"
                           class="inline-flex items-center gap-1 text-green-700 hover:underline">
                            View Details
                            <span class="material-symbols-outlined text-sm">
                                arrow_forward
                            </span>
                        </a>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="px-6 py-3 border-t bg-gray-50 text-sm text-gray-600">
        Showing available job vacancies
    </div>

</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
