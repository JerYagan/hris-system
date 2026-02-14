<?php
$tab = $_GET['tab'] ?? 'menu';
$sent = isset($_GET['sent']) && $_GET['sent'] === 'true';

$tabLabelMap = [
    'menu' => 'Support',
    'contact' => 'Contact HR',
    'faq' => 'FAQ',
    'helpdesk' => 'Helpdesk',
];

$pageTitle = 'Support | DA HRIS';
$activePage = 'support.php';
$breadcrumbs = ['Support'];

if (isset($tabLabelMap[$tab]) && $tab !== 'menu') {
    $breadcrumbs[] = $tabLabelMap[$tab];
}

ob_start();
?>

<section class="mb-6 rounded-2xl border bg-white p-6 sm:p-7">
    <div class="rounded-2xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-5 sm:p-6">
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined rounded-xl bg-green-700 p-2 text-3xl text-white">help</span>
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Help & Support</h1>
                <p class="mt-1 text-sm text-gray-600">Get assistance about your application status and system usage.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($tab === 'menu'): ?>
<section class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <a href="support.php?tab=contact" class="rounded-xl border bg-white p-6 transition hover:border-green-600">
        <span class="material-symbols-outlined mb-2 text-green-700">mail</span>
        <h2 class="font-medium text-gray-800">Contact HR</h2>
        <p class="mt-1 text-sm text-gray-500">Send a message directly to the HR department.</p>
    </a>

    <a href="support.php?tab=faq" class="rounded-xl border bg-white p-6 transition hover:border-green-600">
        <span class="material-symbols-outlined mb-2 text-green-700">quiz</span>
        <h2 class="font-medium text-gray-800">Frequently Asked Questions</h2>
        <p class="mt-1 text-sm text-gray-500">View common questions about the recruitment process.</p>
    </a>

    <a href="support.php?tab=helpdesk" class="rounded-xl border bg-white p-6 transition hover:border-green-600">
        <span class="material-symbols-outlined mb-2 text-green-700">support_agent</span>
        <h2 class="font-medium text-gray-800">System Helpdesk</h2>
        <p class="mt-1 text-sm text-gray-500">Learn how to use the recruitment system.</p>
    </a>
</section>

<?php elseif ($tab === 'contact'): ?>
<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Contact HR Department</h2>
    </header>

    <?php if ($sent): ?>
        <div class="bg-green-50 p-6 text-sm text-green-800">Your message has been successfully sent to the HR department.</div>
    <?php endif; ?>

    <form action="support.php?tab=contact&sent=true" method="POST">
        <div class="space-y-4 p-6 text-sm">
            <div>
                <label class="text-gray-500">Subject</label>
                <input type="text" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="e.g. Application Status Inquiry">
            </div>

            <div>
                <label class="text-gray-500">Message</label>
                <textarea rows="4" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="Type your message here..."></textarea>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="support.php" class="rounded-md border px-4 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">send</span>
                    Send Message
                </button>
            </div>
        </div>
    </form>
</section>

<?php elseif ($tab === 'faq'): ?>
<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Frequently Asked Questions</h2>
    </header>

    <div class="space-y-4 p-6 text-sm">
        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="font-medium text-gray-800">How do I track my application?</p>
            <p class="mt-1 text-gray-600">You may view your application status through the Application Tracking page.</p>
        </div>

        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="font-medium text-gray-800">Can I edit my application after submission?</p>
            <p class="mt-1 text-gray-600">No. Once submitted, applications are locked for evaluation.</p>
        </div>

        <div class="rounded-lg border bg-gray-50 p-4">
            <p class="font-medium text-gray-800">What documents are required?</p>
            <p class="mt-1 text-gray-600">Required documents are listed on the Job Details page.</p>
        </div>
    </div>

    <div class="border-t bg-gray-50 px-6 py-3 text-right">
        <a href="support.php" class="text-sm text-green-700 hover:underline">Back to Support Menu</a>
    </div>
</section>

<?php elseif ($tab === 'helpdesk'): ?>
<section class="rounded-xl border bg-white">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">System Helpdesk</h2>
    </header>

    <div class="space-y-3 p-6 text-sm text-gray-700">
        <p>
            This recruitment system allows applicants to browse job listings,
            submit applications, track application status, and receive feedback.
        </p>
        <p>
            If you encounter technical issues, please contact the HR department
            using the Contact HR option.
        </p>
    </div>

    <div class="border-t bg-gray-50 px-6 py-3 text-right">
        <a href="support.php" class="text-sm text-green-700 hover:underline">Back to Support Menu</a>
    </div>
</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
