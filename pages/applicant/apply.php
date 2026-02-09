<?php
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        edit_document
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Job Application Form
        </h1>
        <p class="text-sm text-gray-500">
            Complete the form below to apply for the selected position.
        </p>
    </div>
</div>

<form action="#" method="POST" enctype="multipart/form-data" class="space-y-8">

    <!-- POSITION INFO -->
    <section class="bg-white border rounded-lg">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Position Information
            </h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <label class="text-gray-500">Position Applied For</label>
                <input type="text"
                       value="Administrative Aide"
                       readonly
                       class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-100">
            </div>

            <div>
                <label class="text-gray-500">Office / Department</label>
                <input type="text"
                       value="Agricultural Training Institute"
                       readonly
                       class="w-full mt-1 border rounded-md px-3 py-2 bg-gray-100">
            </div>
        </div>
    </section>

    <!-- EDUCATIONAL BACKGROUND -->
    <section class="bg-white border rounded-lg">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Educational Background
            </h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
                <label class="text-gray-500">Highest Educational Attainment</label>
                <select class="w-full mt-1 border rounded-md px-3 py-2">
                    <option>Select</option>
                    <option>High School Graduate</option>
                    <option>Senior High School Graduate</option>
                    <option>College Level</option>
                    <option>College Graduate</option>
                </select>
            </div>

            <div>
                <label class="text-gray-500">Course / Strand</label>
                <input type="text"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">School / Institution</label>
                <input type="text"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
        </div>
    </section>

    <!-- WORK EXPERIENCE -->
    <section class="bg-white border rounded-lg">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Work Experience
            </h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
                <label class="text-gray-500">Most Recent Position</label>
                <input type="text"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Company / Organization</label>
                <input type="text"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Years of Experience</label>
                <input type="number"
                       min="0"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>
        </div>
    </section>

    <!-- ADDITIONAL QUALIFICATIONS -->
    <section class="bg-white border rounded-lg">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Additional Qualifications
            </h2>
        </header>

        <div class="p-6 text-sm">
            <label class="text-gray-500">
                Certifications / Trainings (if any)
            </label>
            <textarea rows="3"
                      class="w-full mt-1 border rounded-md px-3 py-2"></textarea>
        </div>
    </section>

    <!-- DOCUMENT UPLOADS -->
    <section class="bg-white border rounded-lg">
        <header class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">
                Required Documents
            </h2>
        </header>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

            <div>
                <label class="text-gray-500">Application Letter</label>
                <input type="file"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Resume / Personal Data Sheet</label>
                <input type="file"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Transcript / Diploma</label>
                <input type="file"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

            <div>
                <label class="text-gray-500">Valid Government ID</label>
                <input type="file"
                       class="w-full mt-1 border rounded-md px-3 py-2">
            </div>

        </div>
    </section>

    <!-- DECLARATION -->
    <section class="bg-white border rounded-lg">
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

    <!-- ACTIONS -->
    <div class="flex justify-end gap-3">
        <a href="job-list.php"
           class="inline-flex items-center gap-1 px-4 py-2 text-sm border rounded-md text-gray-700 hover:bg-gray-50">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
            <span class="material-symbols-outlined text-sm">
                send
            </span>
            Submit Application
        </button>
    </div>

</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
