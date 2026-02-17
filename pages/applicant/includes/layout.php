<?php
// pages/applicant/includes/layout.php
include __DIR__ . '/auth-guard.php';

$uxSkeletonPages = [
    'dashboard.php',
    'job-list.php',
    'job-view.php',
    'apply.php',
    'applications.php',
    'application-feedback.php',
    'notifications.php',
    'profile.php',
];

$currentActivePage = (string)($activePage ?? basename((string)($_SERVER['PHP_SELF'] ?? '')));
$enableUxSkeleton = in_array($currentActivePage, $uxSkeletonPages, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <?php if ($enableUxSkeleton): ?>
        <style>
            @keyframes applicant-skeleton-pulse {
                0%, 100% { opacity: 0.35; }
                50% { opacity: 0.75; }
            }

            .applicant-skeleton-pulse {
                animation: applicant-skeleton-pulse 1.2s ease-in-out infinite;
            }

            @media (prefers-reduced-motion: reduce) {
                .applicant-skeleton-pulse {
                    animation: none;
                }
            }
        </style>
    <?php endif; ?>
</head>
<body class="bg-gray-100 text-gray-800">

<div id="mainContent" class="min-h-screen">
    <?php include __DIR__ . '/topnav.php'; ?>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <?php if ($enableUxSkeleton): ?>
            <section id="applicantPageSkeleton" aria-busy="true" aria-live="polite" class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
                <div class="space-y-4">
                    <div class="h-6 w-1/3 rounded bg-gray-200 applicant-skeleton-pulse"></div>
                    <div class="h-4 w-2/3 rounded bg-gray-200 applicant-skeleton-pulse"></div>
                    <div class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="h-20 rounded-xl bg-gray-200 applicant-skeleton-pulse"></div>
                        <div class="h-20 rounded-xl bg-gray-200 applicant-skeleton-pulse"></div>
                        <div class="h-20 rounded-xl bg-gray-200 applicant-skeleton-pulse"></div>
                        <div class="h-20 rounded-xl bg-gray-200 applicant-skeleton-pulse"></div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div id="applicantPageContent" class="<?= $enableUxSkeleton ? 'hidden' : '' ?>">
        <?php
            /**
             * Page content injector
             * Each applicant page defines $content
             */
            echo $content ?? '';
        ?>
        </div>
    </main>
</div>

<script src="/hris-system/assets/js/script.js"></script>
<script src="/hris-system/assets/js/alert.js"></script>
<?php if ($enableUxSkeleton): ?>
<script>
    (function () {
        const skeleton = document.getElementById('applicantPageSkeleton');
        const content = document.getElementById('applicantPageContent');
        if (!skeleton || !content) {
            return;
        }

        const minimumDisplayMs = 300;
        const startedAt = (window.performance && typeof window.performance.now === 'function') ? window.performance.now() : 0;

        window.addEventListener('DOMContentLoaded', function () {
            const elapsed = (window.performance && typeof window.performance.now === 'function') ? (window.performance.now() - startedAt) : minimumDisplayMs;
            const remaining = Math.max(0, minimumDisplayMs - elapsed);

            window.setTimeout(function () {
                skeleton.classList.add('hidden');
                skeleton.setAttribute('aria-busy', 'false');
                content.classList.remove('hidden');
            }, remaining);
        });
    })();
</script>
<?php endif; ?>
</body>
</html>
