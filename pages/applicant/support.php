<?php
require_once __DIR__ . '/includes/support/bootstrap.php';
require_once __DIR__ . '/includes/support/actions.php';
require_once __DIR__ . '/includes/support/data.php';

$csrfToken = ensureCsrfToken();

$tab = $_GET['tab'] ?? 'menu';

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

<?php if (!empty($message)): ?>
<section class="mb-6 rounded-xl border px-4 py-3 text-sm <?= ($state ?? '') === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
    <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
</section>
<?php endif; ?>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="rounded-xl border bg-gradient-to-r from-green-50 via-white to-green-50 p-4 sm:p-5">
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

    <form action="support.php?tab=contact" method="POST">
        <input type="hidden" name="action" value="submit_support">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="space-y-4 p-4 text-sm sm:p-6">
            <div>
                <label class="text-gray-500">Subject</label>
                <input type="text" name="subject" required minlength="5" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="e.g. Application Status Inquiry">
            </div>

            <div>
                <label class="text-gray-500">Message</label>
                <textarea rows="4" name="message" required minlength="10" class="mt-1 w-full rounded-md border px-3 py-2" placeholder="Type your message here..."></textarea>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
                <a href="support.php" class="rounded-md border px-4 py-2 text-center text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">send</span>
                    Send Message
                </button>
            </div>
        </div>
    </form>

    <div class="border-t bg-gray-50 p-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Recent support inquiries</h3>
        <?php if (empty($recentSupportInquiries)): ?>
            <p class="text-sm text-gray-600">No prior support messages found.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentSupportInquiries as $inquiry): ?>
                    <article class="rounded-lg border bg-white p-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars((string)$inquiry['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars((string)$inquiry['message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($inquiry['created_at'])): ?>
                            <p class="mt-2 text-xs text-gray-500"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string)$inquiry['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
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
