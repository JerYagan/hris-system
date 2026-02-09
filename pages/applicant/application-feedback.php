<?php
ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        fact_check
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Application Feedback
        </h1>
        <p class="text-sm text-gray-500">
            Official recruitment decision for your application.
        </p>
    </div>
</div>

<!-- APPLICATION SUMMARY -->
<section class="bg-white border rounded-lg mb-8">
    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            Application Summary
        </h2>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div>
            <p class="text-gray-500">Position</p>
            <p class="font-medium text-gray-800">Administrative Aide</p>
        </div>
        <div>
            <p class="text-gray-500">Office / Department</p>
            <p class="font-medium text-gray-800">
                Agricultural Training Institute
            </p>
        </div>
        <div>
            <p class="text-gray-500">Date Applied</p>
            <p class="font-medium text-gray-800">
                February 5, 2026
            </p>
        </div>
    </div>
</section>

<!-- ================= ACCEPTED STATE ================= -->
<section class="bg-green-50 border border-green-200 rounded-lg mb-8">

    <div class="p-6 flex gap-4">
        <span class="material-symbols-outlined text-green-700 text-3xl">
            task_alt
        </span>

        <div>
            <h2 class="text-xl font-semibold text-green-800">
                Congratulations!
            </h2>
            <p class="text-sm text-green-700 mt-1">
                You have been <strong>ACCEPTED</strong> for the position.
            </p>
        </div>
    </div>

    <div class="px-6 pb-6 text-sm text-green-800 space-y-2">
        <p>
            After careful evaluation of your qualifications and submitted documents,
            we are pleased to inform you that you have been selected for the position
            of <strong>Administrative Aide</strong>.
        </p>
        <p>
            Further instructions regarding onboarding and requirements will be
            communicated through this system.
        </p>
    </div>

</section>

<!-- ================= REJECTED STATE ================= -->
<section class="bg-red-50 border border-red-200 rounded-lg mb-8">

    <div class="p-6 flex gap-4">
        <span class="material-symbols-outlined text-red-700 text-3xl">
            cancel
        </span>

        <div>
            <h2 class="text-xl font-semibold text-red-800">
                Application Not Successful
            </h2>
            <p class="text-sm text-red-700 mt-1">
                We regret to inform you that your application was not selected.
            </p>
        </div>
    </div>

    <div class="px-6 pb-6 text-sm text-red-800 space-y-2">
        <p>
            After careful assessment, your application did not meet the current
            requirements for this position.
        </p>
        <p>
            We encourage you to apply for future vacancies that match your
            qualifications.
        </p>
    </div>

</section>

<!-- REMARKS -->
<section class="bg-white border rounded-lg mb-8">
    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">
            notes
        </span>
        <h2 class="text-lg font-semibold text-gray-800">
            Remarks
        </h2>
    </header>

    <div class="p-6 text-sm text-gray-700">
        <p>
            Decision was based on the overall evaluation of qualifications,
            experience, and submitted requirements.
        </p>
    </div>
</section>

<!-- ACTIONS -->
<div class="flex justify-end gap-3">

    <a href="job-list.php"
       class="inline-flex items-center gap-1 px-4 py-2 text-sm border rounded-md text-gray-700 hover:bg-gray-50">
        View Other Jobs
    </a>

    <a href="applications.php"
       class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
        View Application Status
    </a>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
