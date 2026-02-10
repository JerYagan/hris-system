<?php
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">
        Submit Job Application
    </h1>
    <p class="text-sm text-gray-500">
        Please complete all sections below. Your progress is saved automatically.
    </p>
</div>

<!-- ================= STEP INDICATOR (VISUAL ONLY) ================= -->
<div class="mb-8 bg-white border rounded-lg p-4">
    <ol class="flex items-center justify-between text-xs text-gray-500">
        <li class="flex items-center gap-2 text-green-700 font-medium">
            <span class="w-6 h-6 rounded-full bg-green-700 text-white flex items-center justify-center text-xs">1</span>
            Personal
        </li>
        <li class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs">2</span>
            Education
        </li>
        <li class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs">3</span>
            Experience
        </li>
        <li class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs">4</span>
            Documents
        </li>
        <li class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs">5</span>
            Submit
        </li>
    </ol>
</div>

<form id="applicationForm" class="space-y-8">

<!-- ================= PERSONAL ================= -->
<section class="bg-white border rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        Personal Information
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

        <div>
            <label class="text-xs uppercase tracking-wide text-gray-500">
                Full Name
            </label>
            <input data-field="fullname"
                   type="text"
                   class="mt-1 w-full border rounded-md px-3 py-2"
                   placeholder="Juan Dela Cruz">
            <p class="text-xs text-gray-400 mt-1">As shown on valid ID</p>
        </div>

        <div>
            <label class="text-xs uppercase tracking-wide text-gray-500">
                Email Address
            </label>
            <input data-field="email"
                   type="email"
                   class="mt-1 w-full border rounded-md px-3 py-2"
                   placeholder="name@email.com">
            <p class="text-xs text-gray-400 mt-1">Used for notifications</p>
        </div>

    </div>
</section>

<!-- ================= EDUCATION ================= -->
<section class="bg-white border rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        Educational Background
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <select data-field="education"
                class="border rounded-md px-3 py-2">
            <option value="">Highest Attainment</option>
            <option>High School Graduate</option>
            <option>Senior High Graduate</option>
            <option>College Graduate</option>
        </select>

        <input data-field="course"
               type="text"
               class="border rounded-md px-3 py-2"
               placeholder="Course / Strand">

        <input data-field="school"
               type="text"
               class="border rounded-md px-3 py-2"
               placeholder="School / Institution">
    </div>
</section>

<!-- ================= EXPERIENCE ================= -->
<section class="bg-white border rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        Work Experience
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <input data-field="position"
               type="text"
               class="border rounded-md px-3 py-2"
               placeholder="Latest Position">

        <input data-field="organization"
               type="text"
               class="border rounded-md px-3 py-2"
               placeholder="Organization">

        <input data-field="years"
               type="number"
               min="0"
               class="border rounded-md px-3 py-2"
               placeholder="Years">
    </div>
</section>

<!-- ================= DOCUMENTS ================= -->
<section class="bg-white border rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">
        Required Documents
    </h3>

    <p class="text-sm text-gray-500 mb-4">
        Uploaded documents will be checked automatically.
    </p>

    <ul class="space-y-3 text-sm">
        <li class="flex justify-between">
            Application Letter
            <span class="doc-status text-gray-400">Pending</span>
        </li>
        <li class="flex justify-between">
            Resume / PDS
            <span class="doc-status text-gray-400">Pending</span>
        </li>
        <li class="flex justify-between">
            Transcript / Diploma
            <span class="doc-status text-gray-400">Pending</span>
        </li>
        <li class="flex justify-between">
            Government ID
            <span class="doc-status text-gray-400">Pending</span>
        </li>
    </ul>
</section>

<!-- ================= DECLARATION ================= -->
<section class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-sm">
    <label class="flex gap-3">
        <input type="checkbox" required>
        <span>
            I certify that all information provided is true and correct.
        </span>
    </label>
</section>

<!-- ================= ACTION ================= -->
<div class="flex justify-end">
    <button type="submit"
            class="px-6 py-3 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
        Submit Application
    </button>
</div>

</form>

<!-- ================= SUCCESS MODAL ================= -->
<div id="successModal"
     class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">
            Application Submitted
        </h2>
        <p class="text-sm text-gray-600 mb-6">
            Your application has been successfully submitted. You may now track its status.
        </p>
        <div class="text-right">
            <a href="applications.php"
               class="px-4 py-2 bg-green-700 text-white rounded-md text-sm">
                Go to Application Tracking
            </a>
        </div>
    </div>
</div>

<!-- ================= JS ================= -->
<script>
/* AUTO-SAVE DRAFT */
document.querySelectorAll('[data-field]').forEach(input => {
    const key = input.dataset.field;
    input.value = localStorage.getItem(key) || '';
    input.addEventListener('input', () => {
        localStorage.setItem(key, input.value);
    });
});

/* SUBMIT HANDLER */
document.getElementById('applicationForm').addEventListener('submit', e => {
    e.preventDefault();
    document.getElementById('successModal').classList.remove('hidden');
    localStorage.clear();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
