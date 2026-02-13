<?php
$pageTitle = 'Applications | DA HRIS';
$activePage = 'applications.php';
$breadcrumbs = ['My Applications'];

ob_start();
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        track_changes
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Application Tracking
        </h1>
        <p class="text-sm text-gray-500">
            Monitor the progress and status of your job application.
        </p>
    </div>
</div>

<!-- APPLICATION SUMMARY -->
<section class="bg-white border rounded-lg mb-8">
    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">
            description
        </span>
        <h2 class="text-lg font-semibold text-gray-800">
            Application Summary
        </h2>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div>
            <p class="text-gray-500">Position</p>
            <p class="font-medium text-gray-800">
                Administrative Aide
            </p>
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

<!-- APPLICATION PROGRESS -->
<section class="bg-white border rounded-lg mb-8">
    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">
            timeline
        </span>
        <h2 class="text-lg font-semibold text-gray-800">
            Application Progress
        </h2>
    </header>

    <div class="p-6">
        <ol class="relative border-l border-gray-200 ml-4 space-y-8">

            <!-- STEP 1 -->
            <li class="ml-6">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 bg-green-700 rounded-full">
                    <span class="material-symbols-outlined text-white text-sm">
                        task_alt
                    </span>
                </span>
                <h3 class="font-medium text-gray-800">
                    Application Submitted
                </h3>
                <p class="text-sm text-gray-600">
                    Your application was successfully submitted.
                </p>
                <span class="text-xs text-gray-500">
                    Feb 5, 2026
                </span>
            </li>

            <!-- STEP 2 -->
            <li class="ml-6">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 bg-green-700 rounded-full">
                    <span class="material-symbols-outlined text-white text-sm">
                        fact_check
                    </span>
                </span>
                <h3 class="font-medium text-gray-800">
                    Document & Qualification Review
                </h3>
                <p class="text-sm text-gray-600">
                    HR is reviewing your submitted documents and qualifications.
                </p>
                <span class="text-xs text-gray-500">
                    Feb 7, 2026
                </span>
            </li>

            <!-- STEP 3 (CURRENT) -->
            <li class="ml-6">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 bg-yellow-400 rounded-full">
                    <span class="material-symbols-outlined text-white text-sm">
                        hourglass_top
                    </span>
                </span>
                <h3 class="font-medium text-gray-800">
                    Under Evaluation
                </h3>
                <p class="text-sm text-gray-600">
                    Your application is currently under evaluation by HR.
                </p>
                <span class="inline-block mt-1 text-xs font-medium text-yellow-700">
                    In Progress
                </span>
            </li>

            <!-- STEP 4 (UPCOMING) -->
            <li class="ml-6 opacity-50">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 bg-gray-300 rounded-full">
                    <span class="material-symbols-outlined text-white text-sm">
                        how_to_reg
                    </span>
                </span>
                <h3 class="font-medium text-gray-700">
                    Final Decision
                </h3>
                <p class="text-sm text-gray-600">
                    You will be notified once a decision has been made.
                </p>
            </li>

        </ol>
    </div>
</section>

<!-- NOTES / FEEDBACK -->
<section class="bg-white border rounded-lg">
    <header class="px-6 py-4 border-b flex items-center gap-2">
        <span class="material-symbols-outlined text-green-700">
            notes
        </span>
        <h2 class="text-lg font-semibold text-gray-800">
            Remarks & Notifications
        </h2>
    </header>

    <div class="p-6 text-sm text-gray-600">
        <p>
            Please ensure that your contact details are active.  
            HR will notify you through the system once your application status changes.
        </p>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
