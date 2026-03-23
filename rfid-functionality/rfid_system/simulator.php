<?php
include "config.php";
include "hris-registry.php";

date_default_timezone_set('Asia/Manila');

$localBridgeUrl = $appBaseUrl . '/rfid-functionality/rfid_system/save_attendance.php';

$message = '';
$messageState = 'idle';
$resultMeta = '';
$selectedUid = trim((string)($_POST['uid'] ?? $_GET['uid'] ?? ''));
$simulatedScannedAt = trim((string)($_POST['simulated_scanned_at'] ?? ''));

$employees = array_values(array_filter(
    legacyRfidRegistryBuildRoster($supabase_url, $headers, $api_key, $appBaseUrl),
    static fn (array $employee): bool => trim((string)($employee['uid'] ?? '')) !== ''
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = trim((string)($_POST['uid'] ?? ''));
    $simulatedScannedAt = trim((string)($_POST['simulated_scanned_at'] ?? ''));

    if ($uid === '') {
        $messageState = 'error';
        $message = 'Select or enter a UID to simulate a tap.';
    } else {
        $bridgePayload = http_build_query([
            'uid' => $uid,
            'simulated_scanned_at' => $simulatedScannedAt,
        ]);

        $bridgeHeaders = [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $bridgeCh = curl_init($localBridgeUrl);
        curl_setopt($bridgeCh, CURLOPT_POST, true);
        curl_setopt($bridgeCh, CURLOPT_HTTPHEADER, $bridgeHeaders);
        curl_setopt($bridgeCh, CURLOPT_POSTFIELDS, $bridgePayload);
        curl_setopt($bridgeCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($bridgeCh, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($bridgeCh, CURLOPT_TIMEOUT, 20);

        $bridgeRaw = curl_exec($bridgeCh);
        $bridgeStatus = (int)curl_getinfo($bridgeCh, CURLINFO_HTTP_CODE);
        $bridgeError = curl_error($bridgeCh);
        curl_close($bridgeCh);

        $bridgeJson = json_decode((string)$bridgeRaw, true);
        if (!is_array($bridgeJson)) {
            $bridgeJson = [];
        }

        if ($bridgeError !== '') {
            $messageState = 'error';
            $message = 'Simulator bridge failed: ' . $bridgeError;
        } else {
            $message = (string)($bridgeJson['message'] ?? ('Unexpected response (' . $bridgeStatus . ')'));
            $messageState = !empty($bridgeJson['success']) ? 'success' : 'error';

            $metaParts = [];
            if (!empty($bridgeJson['name'])) {
                $metaParts[] = 'Employee: ' . (string)$bridgeJson['name'];
            }
            if (!empty($bridgeJson['employee_id'])) {
                $metaParts[] = 'Employee ID: ' . (string)$bridgeJson['employee_id'];
            }
            if (!empty($bridgeJson['action'])) {
                $metaParts[] = 'Action: ' . str_replace('_', ' ', (string)$bridgeJson['action']);
            }
            if (!empty($bridgeJson['attendance_date'])) {
                $metaParts[] = 'Attendance date: ' . (string)$bridgeJson['attendance_date'];
            }
            if (!empty($bridgeJson['result_code'])) {
                $metaParts[] = 'Result: ' . (string)$bridgeJson['result_code'];
            }
            if (!empty($bridgeJson['scan_event_id'])) {
                $metaParts[] = 'Scan event: ' . (string)$bridgeJson['scan_event_id'];
            }

            $resultMeta = implode(' | ', $metaParts);
        }
    }
}

$defaultTime = $simulatedScannedAt !== ''
    ? $simulatedScannedAt
    : (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html>
<head>
    <title>RFID Tap Simulator</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .container { max-width: 960px; margin: 40px auto; padding: 20px; }
        .panel { background: white; border-radius: 14px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); padding: 24px; }
        .hero { margin-bottom: 24px; }
        .hero h1 { margin: 0; color: #1f8f4e; }
        .hero p { margin-top: 8px; color: #4b5563; }
        .feedback { border-radius: 12px; padding: 16px 18px; margin-bottom: 20px; }
        .feedback.idle { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; }
        .feedback.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .feedback.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #334155; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; }
        .actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 18px; }
        button { background: #1f8f4e; color: white; border: none; border-radius: 10px; padding: 11px 16px; font-weight: 700; cursor: pointer; }
        button:hover { background: #166937; }
        .hint { font-size: 12px; color: #64748b; margin-top: 6px; }
        .meta { font-size: 12px; opacity: 0.85; margin-top: 8px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-top: 22px; }
        .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
        .summary-card strong { display: block; color: #0f172a; margin-bottom: 6px; }
    </style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="container">
    <div class="hero">
        <h1>RFID Tap Simulator</h1>
        <p>Simulate a scanner tap inside the legacy RFID folder while still sending the final attendance write into the HRIS RFID endpoint.</p>
    </div>

    <div class="feedback <?php echo htmlspecialchars($messageState, ENT_QUOTES, 'UTF-8'); ?>">
        <strong><?php echo $messageState === 'success' ? 'Tap processed' : ($messageState === 'error' ? 'Tap failed' : 'Ready to simulate'); ?></strong>
        <div><?php echo htmlspecialchars($message !== '' ? $message : 'Select a registered UID and submit a simulated scanner tap.', ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if ($resultMeta !== ''): ?>
            <div class="meta"><?php echo htmlspecialchars($resultMeta, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <form method="post">
            <div class="grid">
                <div>
                    <label for="uid">Registered UID</label>
                    <select id="uid" name="uid">
                        <option value="">Select an employee UID</option>
                        <?php foreach ($employees as $employee): ?>
                            <?php
                            $uid = trim((string)($employee['uid'] ?? ''));
                            if ($uid === '') {
                                continue;
                            }
                            $employeeName = trim((string)($employee['name'] ?? 'Unnamed Employee'));
                            $employeeId = trim((string)($employee['employee_id'] ?? ''));
                            $label = $employeeName . ($employeeId !== '' ? ' [' . $employeeId . ']' : '') . ' - ' . $uid;
                            ?>
                            <option value="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedUid === $uid ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">This uses the local RFID employee registry as the UID source.</div>
                </div>

                <div>
                    <label for="simulatedScannedAt">Simulated tap time</label>
                    <input id="simulatedScannedAt" name="simulated_scanned_at" type="datetime-local" value="<?php echo htmlspecialchars($defaultTime, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="hint">Leave as current Manila time or simulate a specific scanner timestamp.</div>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Simulate Tap</button>
            </div>
        </form>

        <div class="summary">
            <div class="summary-card">
                <strong>Bridge path</strong>
                Legacy simulator -> save_attendance.php -> HRIS /api/rfid/tap.php
            </div>
            <div class="summary-card">
                <strong>Device code</strong>
                <?php echo htmlspecialchars($hris_rfid_device_code, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="summary-card">
                <strong>Write owner</strong>
                HRIS attendance and RFID event tables
            </div>
        </div>
    </div>
</div>

</body>
</html>