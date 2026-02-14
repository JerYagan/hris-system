<?php
$pageTitle = 'Submit Application | DA HRIS';
$activePage = 'apply.php';
$breadcrumbs = ['Submit Application'];

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">edit_document</span>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Job Application Form</h1>
                <p class="text-sm text-gray-500">Complete the details below to submit your application.</p>
            </div>
        </div>

        <a href="job-list.php" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Back to Jobs
        </a>
    </div>
</section>

<form action="#" method="POST" enctype="multipart/form-data" class="space-y-6">
    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Position Information</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-2">
            <div>
                <label class="text-gray-500">Position Applied For</label>
                <input type="text" value="Administrative Aide" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Office / Department</label>
                <input type="text" value="Agricultural Training Institute" readonly class="mt-1 w-full rounded-md border bg-gray-100 px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Educational Background</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-3">
            <div>
                <label class="text-gray-500">Highest Educational Attainment</label>
                <select class="mt-1 w-full rounded-md border px-3 py-2">
                    <option>Select</option>
                    <option>High School Graduate</option>
                    <option>Senior High School Graduate</option>
                    <option>College Level</option>
                    <option>College Graduate</option>
                </select>
            </div>

            <div>
                <label class="text-gray-500">Course / Strand</label>
                <input type="text" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">School / Institution</label>
                <input type="text" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Work Experience</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-3">
            <div>
                <label class="text-gray-500">Most Recent Position</label>
                <input type="text" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Company / Organization</label>
                <input type="text" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Years of Experience</label>
                <input type="number" min="0" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Additional Qualifications</h2>
        </header>

        <div class="p-6 text-sm">
            <label class="text-gray-500">Certifications / Trainings (if any)</label>
            <textarea rows="3" class="mt-1 w-full rounded-md border px-3 py-2"></textarea>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <header class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800">Required Documents</h2>
        </header>

        <div class="grid grid-cols-1 gap-6 p-6 text-sm md:grid-cols-2">
            <div>
                <label class="text-gray-500">Application Letter</label>
                <input type="file" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Resume / Personal Data Sheet</label>
                <input type="file" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Transcript / Diploma</label>
                <input type="file" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Valid Government ID</label>
                <input type="file" class="mt-1 w-full rounded-md border px-3 py-2">
            </div>
        </div>
    </section>

    <section class="rounded-xl border bg-white">
        <div class="p-6 text-sm">
            <label class="flex items-start gap-2">
                <input type="checkbox" class="mt-1">
                <span class="text-gray-700">
                    I hereby certify that the information provided is true and correct
                    to the best of my knowledge. I understand that any false information
                    may result in disqualification.
                </span>
            </label>
        </div>
    </section>

    <section class="sticky bottom-0 z-20 rounded-xl border bg-white/95 p-4 backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="job-list.php" class="inline-flex items-center justify-center gap-1 rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </a>

            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
                <span class="material-symbols-outlined text-sm">send</span>
                Submit Application
            </button>
        </div>
    </section>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
