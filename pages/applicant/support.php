<?php
ob_start();

$tab = $_GET['tab'] ?? 'menu';
$sent = isset($_GET['sent']) && $_GET['sent'] === 'true';
?>

<!-- PAGE HEADER -->
<div class="mb-8 flex items-start gap-4">
    <span class="material-symbols-outlined text-green-700 text-4xl">
        help
    </span>
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Help & Support
        </h1>
        <p class="text-sm text-gray-500">
            Get assistance regarding your application or system usage.
        </p>
    </div>
</div>

<?php if ($tab === 'menu'): ?>
<!-- ================= SUPPORT MENU ================= -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <a href="support.php?tab=contact"
       class="bg-white border rounded-lg p-6 hover:border-green-600 transition">
        <span class="material-symbols-outlined text-green-700 mb-2">
            mail
        </span>
        <h2 class="font-medium text-gray-800">
            Contact HR
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            Send a message directly to the HR department.
        </p>
    </a>

    <a href="support.php?tab=faq"
       class="bg-white border rounded-lg p-6 hover:border-green-600 transition">
        <span class="material-symbols-outlined text-green-700 mb-2">
            quiz
        </span>
        <h2 class="font-medium text-gray-800">
            Frequently Asked Questions
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            View common questions about the recruitment process.
        </p>
    </a>

    <a href="support.php?tab=helpdesk"
       class="bg-white border rounded-lg p-6 hover:border-green-600 transition">
        <span class="material-symbols-outlined text-green-700 mb-2">
            support_agent
        </span>
        <h2 class="font-medium text-gray-800">
            System Helpdesk
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            Learn how to use the recruitment system.
        </p>
    </a>

</section>

<?php elseif ($tab === 'contact'): ?>
<!-- ================= CONTACT HR ================= -->
<section class="bg-white border rounded-lg">

    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            Contact HR Department
        </h2>
    </header>

    <?php if ($sent): ?>
        <div class="p-6 bg-green-50 text-green-800 text-sm">
            Your message has been successfully sent to the HR department.
        </div>
    <?php endif; ?>

    <form action="support.php?tab=contact&sent=true" method="POST">
        <div class="p-6 space-y-4 text-sm">

            <div>
                <label class="text-gray-500">Subject</label>
                <input type="text"
                       class="w-full mt-1 border rounded-md px-3 py-2"
                       placeholder="e.g. Application Status Inquiry">
            </div>

            <div>
                <label class="text-gray-500">Message</label>
                <textarea rows="4"
                          class="w-full mt-1 border rounded-md px-3 py-2"
                          placeholder="Type your message here..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="support.php"
                   class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-md bg-green-700 text-white hover:bg-green-800">
                    <span class="material-symbols-outlined text-sm">
                        send
                    </span>
                    Send Message
                </button>
            </div>

        </div>
    </form>

</section>

<?php elseif ($tab === 'faq'): ?>
<!-- ================= FAQ ================= -->
<section class="bg-white border rounded-lg">

    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            Frequently Asked Questions
        </h2>
    </header>

    <div class="p-6 text-sm space-y-4">

        <div>
            <p class="font-medium text-gray-800">
                How do I track my application?
            </p>
            <p class="text-gray-600">
                You may view your application status through the Application Tracking page.
            </p>
        </div>

        <div>
            <p class="font-medium text-gray-800">
                Can I edit my application after submission?
            </p>
            <p class="text-gray-600">
                No. Once submitted, applications are locked for evaluation.
            </p>
        </div>

        <div>
            <p class="font-medium text-gray-800">
                What documents are required?
            </p>
            <p class="text-gray-600">
                Required documents are listed on the Job Details page.
            </p>
        </div>

    </div>

    <div class="px-6 py-3 border-t bg-gray-50 text-right">
        <a href="support.php" class="text-sm text-green-700 hover:underline">
            Back to Support Menu
        </a>
    </div>

</section>

<?php elseif ($tab === 'helpdesk'): ?>
<!-- ================= SYSTEM HELPDESK ================= -->
<section class="bg-white border rounded-lg">

    <header class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            System Helpdesk
        </h2>
    </header>

    <div class="p-6 text-sm text-gray-700 space-y-3">
        <p>
            This recruitment system allows applicants to browse job listings,
            submit applications, track application status, and receive feedback.
        </p>
        <p>
            If you encounter technical issues, please contact the HR department
            using the Contact HR option.
        </p>
    </div>

    <div class="px-6 py-3 border-t bg-gray-50 text-right">
        <a href="support.php" class="text-sm text-green-700 hover:underline">
            Back to Support Menu
        </a>
    </div>

</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
