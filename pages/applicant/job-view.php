<?php
ob_start();

/* ===== SIMULATED DATA (replace with DB later) ===== */
$jobId = 'DA-ATI-001';
$deadline = '2026-03-15';
$today = date('Y-m-d');

$isDeadlinePassed = $today > $deadline;
$alreadyApplied = false;
?>

<!-- PAGE HEADER -->
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">
        Job Details
    </h1>
    <p class="text-sm text-gray-500">
        Review the position information carefully before applying.
    </p>
</div>

<!-- ================= SKELETON LOADER ================= -->
<div id="jobSkeleton" class="space-y-6">

    <div class="h-32 bg-gray-200 rounded-lg animate-pulse"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="h-40 bg-gray-200 rounded-lg animate-pulse"></div>
            <div class="h-32 bg-gray-200 rounded-lg animate-pulse"></div>
            <div class="h-28 bg-gray-200 rounded-lg animate-pulse"></div>
        </div>
        <div class="h-48 bg-gray-200 rounded-lg animate-pulse"></div>
    </div>

</div>

<!-- ================= REAL CONTENT ================= -->
<div id="jobContent" class="hidden">

    <!-- JOB HERO CARD -->
    <section class="bg-white border rounded-lg p-6 mb-8">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

            <!-- LEFT -->
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold text-gray-800">
                        Administrative Aide
                    </h2>

                    <!-- BOOKMARK -->
                    <button id="bookmarkBtn"
                            class="flex items-center gap-1 text-sm text-gray-500 hover:text-green-700">
                        <span id="bookmarkIcon" class="material-symbols-outlined">
                            bookmark_border
                        </span>
                        <span class="hidden sm:inline">Save</span>
                    </button>
                </div>

                <p class="text-sm text-gray-500 mt-1">
                    Agricultural Training Institute – Central Office
                </p>

                <div class="flex flex-wrap gap-2 mt-4">

                    <?php if ($alreadyApplied): ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            Applied
                        </span>
                    <?php elseif ($isDeadlinePassed): ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800">
                            Closed
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                            Open
                        </span>
                    <?php endif; ?>

                </div>
            </div>

            <!-- RIGHT META -->
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Employment</p>
                    <p class="font-medium">Contractual</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Salary Grade</p>
                    <p class="font-medium">SG 4</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Job ID</p>
                    <p class="font-medium"><?= $jobId ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Deadline</p>
                    <p class="font-medium">March 15, 2026</p>
                </div>
            </div>

        </div>

    </section>

    <!-- CONTENT GRID -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- MAIN -->
        <div class="lg:col-span-2 space-y-6">

            <!-- DESCRIPTION -->
            <div class="bg-white border rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">
                    Job Description
                </h3>
                <p class="text-sm text-gray-700 mb-4">
                    The Administrative Aide provides clerical and administrative support
                    to ensure efficient office operations.
                </p>
                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                    <li>Prepare and maintain office records</li>
                    <li>Assist in data encoding and filing</li>
                    <li>Coordinate with staff on administrative matters</li>
                    <li>Perform other duties as assigned</li>
                </ul>
            </div>

            <!-- QUALIFICATIONS -->
            <div class="bg-white border rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">
                    Qualifications
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
                    <div>
                        <p class="font-medium mb-2">Minimum</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>High School Graduate</li>
                            <li>Basic computer skills</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-medium mb-2">Preferred</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Clerical experience</li>
                            <li>Government office exposure</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- DOCUMENTS -->
            <div class="bg-white border rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">
                    Required Documents
                </h3>
                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                    <li>Application Letter</li>
                    <li>Resume / PDS</li>
                    <li>Transcript or Diploma</li>
                    <li>Valid Government ID</li>
                </ul>
            </div>

        </div>

        <!-- ACTION SIDEBAR -->
        <aside class="space-y-4">

            <div class="bg-white border rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Ensure all documents are complete before applying.
                </p>

                <?php if ($alreadyApplied): ?>
                    <button disabled
                            class="w-full py-2 text-sm rounded-md bg-gray-300 text-gray-600">
                        Application Submitted
                    </button>

                <?php elseif ($isDeadlinePassed): ?>
                    <button disabled
                            class="w-full py-2 text-sm rounded-md bg-gray-300 text-gray-600">
                        Application Closed
                    </button>

                <?php else: ?>
                    <a href="apply.php"
                       class="block text-center py-2 text-sm rounded-md bg-green-700 text-white hover:bg-green-800">
                        Apply for this Position
                    </a>
                <?php endif; ?>

            </div>

            <a href="job-list.php"
               class="block text-center py-2 text-sm border rounded-md text-gray-700 hover:bg-gray-50">
                Back to Job Listings
            </a>

        </aside>

    </section>

</div>

<!-- ================= JS ================= -->
<script>
/* Skeleton swap */
window.addEventListener('load', () => {
    setTimeout(() => {
        document.getElementById('jobSkeleton')?.remove();
        document.getElementById('jobContent')?.classList.remove('hidden');
    }, 500);
});

/* Bookmark logic */
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('bookmarkBtn');
    const icon = document.getElementById('bookmarkIcon');
    const jobId = '<?= $jobId ?>';

    if (!btn || !icon) return;

    let saved = JSON.parse(localStorage.getItem('savedJobs')) || [];

    if (saved.includes(jobId)) {
        icon.textContent = 'bookmark';
        btn.classList.add('text-green-700');
    }

    btn.addEventListener('click', () => {
        if (saved.includes(jobId)) {
            saved = saved.filter(id => id !== jobId);
            icon.textContent = 'bookmark_border';
            btn.classList.remove('text-green-700');
        } else {
            saved.push(jobId);
            icon.textContent = 'bookmark';
            btn.classList.add('text-green-700');
        }
        localStorage.setItem('savedJobs', JSON.stringify(saved));
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
