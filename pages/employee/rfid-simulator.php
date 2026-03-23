<?php

require_once __DIR__ . '/includes/timekeeping/bootstrap.php';
require_once __DIR__ . '/../shared/lib/rfid-attendance.php';

$pageTitle = 'RFID Simulator | DA HRIS';
$activePage = 'timekeeping.php';
$breadcrumbs = ['Timekeeping', 'RFID Simulator'];

$csrfToken = function_exists('ensureCsrfToken') ? ensureCsrfToken() : '';
$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);
$simulatorEnabled = rfidSimulatorEnabled($supabaseUrl, $headers);
$simulatorDeviceCode = rfidSimulatorDeviceCode();
$simulatorDeviceToken = rfidSimulatorDeviceToken();
$isJsonRequest = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
    || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

$emitSimulatorResponse = static function (int $statusCode, array $payload) use ($isJsonRequest): void {
    if ($isJsonRequest) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
};

if (!$simulatorEnabled) {
    $emitSimulatorResponse(403, [
        'success' => false,
        'state' => 'error',
        'title' => 'Simulator disabled',
        'message' => 'RFID simulator is disabled in this environment.',
    ]);
    redirectWithState('error', 'RFID simulator is disabled in this environment.', 'timekeeping.php');
}

$activeCard = rfidResolveActiveCardForPerson($supabaseUrl, $headers, $employeePersonId ?? null);
$activeCardUid = cleanText($activeCard['card_uid'] ?? null);

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
        $emitSimulatorResponse(419, [
            'success' => false,
            'state' => 'error',
            'title' => 'Invalid request token',
            'message' => 'Invalid request token. Please refresh and try again.',
        ]);
        redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'rfid-simulator.php');
    }

    $action = strtolower((string)cleanText($_POST['action'] ?? ''));
    if ($action !== 'simulate_rfid_tap') {
        $emitSimulatorResponse(400, [
            'success' => false,
            'state' => 'error',
            'title' => 'Unsupported action',
            'message' => 'Unsupported RFID simulator action.',
        ]);
        redirectWithState('error', 'Unsupported RFID simulator action.', 'rfid-simulator.php');
    }

    if ($activeCardUid === null) {
        $emitSimulatorResponse(409, [
            'success' => false,
            'state' => 'error',
            'title' => 'No assigned RFID card',
            'message' => 'No active RFID card is assigned to your account.',
        ]);
        redirectWithState('error', 'No active RFID card is assigned to your account.', 'rfid-simulator.php');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $tapTimeRaw = cleanText($_POST['simulated_scanned_at'] ?? null);
    try {
        $tapResult = rfidProcessAttendanceTap($supabaseUrl, $headers, [
            'card_uid' => $activeCardUid,
            'scanned_at' => $tapTimeRaw,
            'request_source' => 'device',
            'device_code' => $simulatorDeviceCode,
            'device_token' => $simulatorDeviceToken,
            'actor_user_id' => $employeeUserId,
            'raw_payload' => [
                'simulated_scanned_at' => $tapTimeRaw,
                'employee_person_id' => $employeePersonId,
                'simulator_device_code' => $simulatorDeviceCode,
                'simulation_mode' => 'device',
            ],
        ]);
    } catch (Throwable $throwable) {
        error_log(sprintf(
            '[HRIS][RFID][SIMULATOR] tap failed for employee_person_id=%s card_uid=%s error=%s',
            (string)$employeePersonId,
            (string)$activeCardUid,
            $throwable->getMessage()
        ));

        $emitSimulatorResponse(500, [
            'success' => false,
            'state' => 'error',
            'title' => 'Server error',
            'message' => 'The simulator tap failed due to a server error. Please try again.',
        ]);

        redirectWithState('error', 'The simulator tap failed due to a server error. Please try again.', 'rfid-simulator.php');
    }

    $resultMessage = (string)($tapResult['message'] ?? 'RFID simulation processed.');
    if ((bool)($tapResult['success'] ?? false)) {
        if (($tapResult['action'] ?? '') === 'time_in') {
            $resultMessage = 'RFID tap simulated successfully. Time-in is now visible in employee, staff, and admin attendance views.';
        } elseif (($tapResult['action'] ?? '') === 'time_out') {
            $resultMessage = 'RFID tap simulated successfully. Time-out is now visible in employee, staff, and admin attendance views.';
        }

        $emitSimulatorResponse(200, [
            'success' => true,
            'state' => 'success',
            'title' => 'Tap processed',
            'message' => $resultMessage,
            'action' => (string)($tapResult['action'] ?? ''),
            'result_code' => (string)($tapResult['result_code'] ?? ''),
            'attendance_date' => (string)($tapResult['attendance_date'] ?? ''),
            'processed_at' => formatDateTimeForPhilippines(gmdate('c'), 'M d, Y h:i:s A'),
        ]);

        redirectWithState('success', $resultMessage, 'rfid-simulator.php');
    }

    $emitSimulatorResponse((int)($tapResult['http_status'] ?? 422), [
        'success' => false,
        'state' => 'error',
        'title' => 'Tap rejected',
        'message' => $resultMessage,
        'action' => (string)($tapResult['action'] ?? ''),
        'result_code' => (string)($tapResult['result_code'] ?? ''),
        'attendance_date' => (string)($tapResult['attendance_date'] ?? ''),
        'processed_at' => formatDateTimeForPhilippines(gmdate('c'), 'M d, Y h:i:s A'),
    ]);

    redirectWithState('error', $resultMessage, 'rfid-simulator.php');
}

$assignedCardLabel = cleanText($activeCard['card_label'] ?? null) ?? 'Active assigned card';
$assignedCardMasked = rfidMaskCardUid($activeCardUid);
$employeeDisplayName = cleanText($employeeContext['display_name'] ?? null) ?? 'Employee';
$simulatorDefaultTime = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d\TH:i');
$feedbackState = $state === 'success' ? 'success' : ($state === 'error' ? 'error' : 'idle');
$feedbackTitle = $state === 'success' ? 'Last simulated tap' : ($state === 'error' ? 'Tap needs attention' : 'Awaiting tap');
$feedbackMessage = $message ?? 'Submit a simulated device tap to see whether your RFID card was processed as time-in, time-out, or ignored as a duplicate.';
$feedbackMeta = $state ? ('Updated ' . formatDateTimeForPhilippines(gmdate('c'), 'M d, Y h:i:s A')) : 'No simulator tap has been processed in this session yet.';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

ob_start();
?>

<div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">RFID Simulator</h1>
        <p class="mt-1 text-sm text-gray-500">Simulate a device-authenticated RFID tap for your assigned card using the same flow used by the scanner endpoint.</p>
    </div>
    <a href="timekeeping.php" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        <span class="material-symbols-outlined text-base">schedule</span>
        Back to Timekeeping
    </a>
</div>

<section id="rfidLiveFeedback" data-state="<?= htmlspecialchars($feedbackState, ENT_QUOTES, 'UTF-8') ?>" class="mb-6 rounded-xl border px-5 py-4 shadow-sm <?= $feedbackState === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : ($feedbackState === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-sky-200 bg-sky-50 text-sky-900') ?>">
    <div class="flex items-start gap-3">
        <span id="rfidLiveFeedbackIcon" class="material-symbols-outlined text-xl <?= $feedbackState === 'success' ? 'text-emerald-700' : ($feedbackState === 'error' ? 'text-rose-700' : 'text-sky-700') ?>"><?= $feedbackState === 'success' ? 'check_circle' : ($feedbackState === 'error' ? 'error' : 'radar') ?></span>
        <div class="min-w-0 flex-1">
            <p id="rfidLiveFeedbackTitle" class="font-semibold"><?= htmlspecialchars($feedbackTitle, ENT_QUOTES, 'UTF-8') ?></p>
            <p id="rfidLiveFeedbackMessage" class="mt-1 text-sm"><?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <p id="rfidLiveFeedbackMeta" class="mt-2 text-xs opacity-80"><?= htmlspecialchars($feedbackMeta, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
</section>

<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-xl text-amber-700">warning</span>
        <div>
            <p class="font-semibold">Controlled testing tool only</p>
            <p class="mt-1">This simulator is intended for QA, onboarding, and controlled validation. It must remain feature-gated in production to avoid bypassing physical attendance controls.</p>
        </div>
    </div>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <article class="rounded-xl border bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Employee</p>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars($employeeDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Assigned RFID Card</p>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars($assignedCardMasked, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($assignedCardLabel, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Simulated Device</p>
        <p class="mt-2 text-base font-semibold text-slate-800"><?= htmlspecialchars($simulatorDeviceCode, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mt-1 text-xs text-slate-500">This simulator now uses the same device-authenticated RFID path as a scanner tap.</p>
    </article>
</section>

<section class="rounded-xl border bg-white shadow-sm">
    <header class="border-b px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-800">Simulate RFID Tap</h2>
        <p class="mt-1 text-sm text-slate-500">The backend will process this like a real scanner tap, including device validation, scan-event logging, and attendance write rules.</p>
    </header>

    <div class="p-6">
        <?php if ($activeCardUid === null): ?>
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                No active RFID card is assigned to your account yet. Staff must enroll your RFID card before this simulator can be used.
            </div>
        <?php else: ?>
            <form id="rfidSimulatorForm" method="post" action="rfid-simulator.php" class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="simulate_rfid_tap">

                <div>
                    <label class="text-slate-600" for="simulatedScannedAt">Simulated Tap Time</label>
                    <input id="simulatedScannedAt" name="simulated_scanned_at" type="datetime-local" value="<?= htmlspecialchars($simulatorDefaultTime, ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Leave as current Manila time or simulate a specific tap timestamp.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Simulator Rules</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>Uses device code <?= htmlspecialchars($simulatorDeviceCode, ENT_QUOTES, 'UTF-8') ?>.</li>
                        <li>First valid tap fills time-in.</li>
                        <li>Second valid tap fills time-out.</li>
                        <li>Rapid duplicate taps are ignored.</li>
                    </ul>
                </div>

                <div class="md:col-span-2 flex flex-wrap items-center justify-end gap-3">
                    <a href="timekeeping.php" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                    <button id="rfidSimulatorSubmit" type="submit" class="inline-flex items-center gap-2 rounded-lg bg-daGreen px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span class="material-symbols-outlined text-base">contactless</span>
                        <span id="rfidSimulatorSubmitLabel">Simulate Tap</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
(() => {
    const form = document.getElementById('rfidSimulatorForm');
    const feedback = document.getElementById('rfidLiveFeedback');
    const feedbackIcon = document.getElementById('rfidLiveFeedbackIcon');
    const feedbackTitle = document.getElementById('rfidLiveFeedbackTitle');
    const feedbackMessage = document.getElementById('rfidLiveFeedbackMessage');
    const feedbackMeta = document.getElementById('rfidLiveFeedbackMeta');
    const submitButton = document.getElementById('rfidSimulatorSubmit');
    const submitLabel = document.getElementById('rfidSimulatorSubmitLabel');

    if (!(form instanceof HTMLFormElement) || !feedback || !feedbackIcon || !feedbackTitle || !feedbackMessage || !feedbackMeta) {
        return;
    }

    const applyFeedbackState = (state, title, message, meta) => {
        const containerClasses = {
            idle: ['border-sky-200', 'bg-sky-50', 'text-sky-900'],
            processing: ['border-amber-200', 'bg-amber-50', 'text-amber-900'],
            success: ['border-emerald-200', 'bg-emerald-50', 'text-emerald-900'],
            error: ['border-rose-200', 'bg-rose-50', 'text-rose-900'],
        };
        const iconClasses = {
            idle: ['text-sky-700'],
            processing: ['text-amber-700'],
            success: ['text-emerald-700'],
            error: ['text-rose-700'],
        };
        const icons = {
            idle: 'radar',
            processing: 'hourglass_top',
            success: 'check_circle',
            error: 'error',
        };

        Object.values(containerClasses).flat().forEach((className) => feedback.classList.remove(className));
        Object.values(iconClasses).flat().forEach((className) => feedbackIcon.classList.remove(className));

        (containerClasses[state] || containerClasses.idle).forEach((className) => feedback.classList.add(className));
        (iconClasses[state] || iconClasses.idle).forEach((className) => feedbackIcon.classList.add(className));

        feedback.dataset.state = state;
        feedbackIcon.textContent = icons[state] || icons.idle;
        feedbackTitle.textContent = title;
        feedbackMessage.textContent = message;
        feedbackMeta.textContent = meta;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
            return;
        }

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = true;
        }
        if (submitLabel) {
            submitLabel.textContent = 'Processing Tap...';
        }

        applyFeedbackState(
            'processing',
            'Waiting for tap result',
            'Your simulated RFID tap is being processed now through the device-authenticated RFID endpoint flow.',
            'Submitting to the HRIS attendance service...'
        );

        let responseStatus = null;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            responseStatus = response.status;

            const rawResponse = await response.text();
            let payload = null;

            try {
                payload = JSON.parse(rawResponse);
            } catch (parseError) {
                payload = null;
            }

            if (!payload || typeof payload !== 'object') {
                const compactResponse = rawResponse.replace(/\s+/g, ' ').trim();
                throw new Error(compactResponse !== ''
                    ? 'Unexpected response (' + response.status + '): ' + compactResponse.slice(0, 220)
                    : 'Unexpected empty response (' + response.status + ').');
            }

            const isSuccess = Boolean(payload.success);
            const title = payload.title || (isSuccess ? 'Tap processed' : 'Tap rejected');
            const message = payload.message || (isSuccess ? 'RFID tap processed successfully.' : 'RFID tap could not be processed.');
            const metaParts = [];
            if (payload.action) {
                metaParts.push('Action: ' + String(payload.action).replaceAll('_', ' '));
            }
            if (payload.attendance_date) {
                metaParts.push('Attendance date: ' + payload.attendance_date);
            }
            if (payload.processed_at) {
                metaParts.push('Processed: ' + payload.processed_at);
            }

            applyFeedbackState(
                isSuccess ? 'success' : 'error',
                title,
                message,
                metaParts.length > 0 ? metaParts.join(' | ') : 'No additional details returned.'
            );
        } catch (error) {
            applyFeedbackState(
                'error',
                'Tap request failed',
                error instanceof Error ? error.message : 'The simulator could not reach the HRIS attendance service. Try again.',
                responseStatus !== null
                    ? 'Request could not be processed cleanly by the server.'
                    : 'Network or parsing error while waiting for live tap feedback.'
            );
        } finally {
            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = false;
            }
            if (submitLabel) {
                submitLabel.textContent = 'Simulate Tap';
            }
        }
    });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';