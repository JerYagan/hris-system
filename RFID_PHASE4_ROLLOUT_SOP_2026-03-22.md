# RFID Phase 4 Rollout SOP

Date: 2026-03-22

## Purpose

This SOP covers the production-hardening tasks for RFID attendance rollout in the HRIS:

- deploy the RFID schema safely
- seed and rotate scanner credentials
- enable or disable the employee RFID simulator deliberately
- validate scanner traffic before office-wide rollout
- handle lost cards, failed scans, and scanner replacement

## Required Configuration

Set these environment variables in the HRIS deployment environment:

- `HRIS_RFID_SIMULATOR_ENABLED=0`
- `HRIS_RFID_DEVICE_TIMESTAMP_SKEW_SECONDS=300`

Notes:

- Keep the simulator disabled in production by default.
- Increase the timestamp skew window only if scanner clocks are unreliable and cannot be stabilized through NTP.

Optional system setting:

- `timekeeping.rfid_simulator_enabled`

Use the system setting only for deliberate QA enablement. The environment variable should remain the production default control.

## Database Rollout

1. Apply [revisions/SUPABASE_MIGRATION_RFID_ATTENDANCE_2026-03-22.sql](revisions/SUPABASE_MIGRATION_RFID_ATTENDANCE_2026-03-22.sql).
2. Verify that these tables exist:
   - `rfid_devices`
   - `rfid_cards`
   - `rfid_scan_events`
3. Verify that `attendance_logs.source` accepts `rfid`.
4. Verify that `attendance_logs.capture_device_id` exists.
5. Verify that `attendance_logs.rfid_scan_event_id` exists.

## Device Provisioning

For each scanner:

1. Create a unique `device_code`.
2. Generate a unique long random device token.
3. Store only the hashed token in `rfid_devices.device_token_hash`.
4. Set device `status = active` only after the scanner is physically deployed and tested.
5. Record office, location label, and custodian.

Recommended naming:

- `SCANNER-ATI-HQ-01`
- `SCANNER-ATI-HQ-02`
- `SCANNER-ATI-REGION3-01`

## Firmware Deployment

The firmware sample in [rfid-functionality/Arduino_Code.txt](rfid-functionality/Arduino_Code.txt) now assumes:

- taps are posted to `/api/rfid/tap.php`
- the scanner sends `device_code`
- the scanner sends `X-RFID-DEVICE-TOKEN`
- the scanner no longer writes directly to Supabase

Before flashing a real scanner:

1. Replace Wi-Fi credentials.
2. Replace the HRIS endpoint URL.
3. Replace `RFID_DEVICE_CODE`.
4. Replace `RFID_DEVICE_TOKEN`.
5. Confirm the device clock can sync through NTP.

## Pilot Validation

Use one office or one scanner first.

Run this sequence:

1. Assign an active RFID card to one employee.
2. Seed one active RFID device.
3. Tap once and verify `time_in` is created.
4. Tap again and verify `time_out` is created.
5. Tap rapidly and verify the event is logged as `duplicate_ignored`.
6. Send an invalid token and verify the event is rejected.
7. Send an unknown card and verify the event is rejected.
8. Check staff timekeeping for assignment and recent event visibility.
9. Check admin timekeeping for device and scan-event visibility.
10. Check employee timekeeping for the resulting attendance row.

## Simulator Control

The employee simulator is now feature-gated.

Rules:

- Production default: disabled
- QA / onboarding: enable only temporarily
- Disable again immediately after validation

Enable paths:

1. Set `HRIS_RFID_SIMULATOR_ENABLED=1`, or
2. Set system setting `timekeeping.rfid_simulator_enabled` to a truthy value

Disable paths:

1. Set `HRIS_RFID_SIMULATOR_ENABLED=0`, or
2. Remove the system setting override

## Lost Card Procedure

1. Staff deactivates the active RFID card assignment immediately.
2. Staff assigns a replacement card.
3. Staff records any affected attendance correction through existing timekeeping tools.
4. Admin reviews scan history if abuse is suspected.

## Scanner Replacement Procedure

1. Mark the old `rfid_devices` row as inactive.
2. Generate a new token for the replacement scanner.
3. Register the replacement scanner with a new or reused `device_code` as appropriate.
4. Flash the new firmware config.
5. Run the pilot validation sequence again.

## Failed Scan Triage

Use `rfid_scan_events.result_code` first.

Common responses:

- `device_not_allowed`: invalid or inactive device
- `card_not_registered`: card not enrolled to an active employee
- `duplicate_ignored`: repeated tap inside the debounce window
- `attendance_already_complete`: both `time_in` and `time_out` already present for the day
- `timestamp_out_of_range`: device clock skew exceeds the allowed window

If `timestamp_out_of_range` appears:

1. Check NTP connectivity on the scanner.
2. Confirm the scanner clock is synchronized.
3. Only widen `HRIS_RFID_DEVICE_TIMESTAMP_SKEW_SECONDS` if the network cannot consistently support timely clock sync.

## Rollback

If a pilot office fails validation:

1. Set affected devices to `inactive`.
2. Keep manual attendance fallback active.
3. Leave `attendance_logs` intact.
4. Review `rfid_scan_events` and `activity_logs` before making code or firmware adjustments.

## Minimum Go-Live Criteria

Do not expand beyond pilot until all of these are true:

- migration applied successfully
- at least one scanner passes the pilot validation sequence
- staff can replace lost cards without page errors
- admin can review failed scans and device status
- simulator remains disabled in production after testing
- no direct Supabase writes remain in deployed firmware

## Verification Note

Based on the code in [rfid-functionality/Arduino_Code.txt](rfid-functionality/Arduino_Code.txt), the RFID device flow is correctly pointed at the HRIS endpoint instead of writing directly to Supabase. The server-side tap path in [api/rfid/tap.php](api/rfid/tap.php) and [pages/shared/lib/rfid-attendance.php](pages/shared/lib/rfid-attendance.php) is capable of turning a valid scanner tap into `attendance_logs` rows and `rfid_scan_events` records.

What was verified from this environment:

- A scanner-shaped POST to the local endpoint returned structured JSON, so the endpoint is live and accepts device traffic.
- A POST without a provisioned device returned `403 device_not_allowed`, which confirms the request reached the device-validation branch.
- A POST with an old timestamp returned `422 timestamp_out_of_range`, which confirms timestamp validation is active for real scanner traffic.
- The attendance write logic exists and will create `time_in`, then `time_out`, then reject rapid duplicates once the device and card are valid.

What is not fully verified here:

- No physical scanner was available, so card-reader hardware behavior, Wi-Fi stability, and real UID reads were not tested in this workspace.
- The sample under [rfid-functionality/rfid_system](rfid-functionality/rfid_system) is legacy proof-of-concept code that writes directly to Supabase and should not be treated as the live integration path.

## Setup still required before a real scanner can log employee attendance:

1. Apply [revisions/SUPABASE_MIGRATION_RFID_ATTENDANCE_2026-03-22.sql](revisions/SUPABASE_MIGRATION_RFID_ATTENDANCE_2026-03-22.sql).
2. Insert an active row in `rfid_devices` with the same `device_code` used by the firmware.
3. Generate a real scanner secret and store only its hash in `rfid_devices.device_token_hash`.
4. Assign an active RFID card UID to the employee in `rfid_cards`.
5. Point the firmware to a real HRIS URL that the device can reach on the network.
6. If the scanner will call a local XAMPP host instead of a public HTTPS deployment, adjust the firmware transport accordingly. The current sample uses `WiFiClientSecure` and an `https://.../api/rfid/tap.php` endpoint, so plain local `http://localhost/...` is not a drop-in deployment target for the ESP8266.
7. Ensure the scanner can reach NTP or omit `scanned_at`; otherwise the server may reject taps with `timestamp_out_of_range`.
8. Flash the firmware with real Wi-Fi credentials, endpoint URL, `RFID_DEVICE_CODE`, and `RFID_DEVICE_TOKEN`.

Conclusion:

The HRIS side is ready to accept and process real RFID scanner taps, but a real device will not log employee attendance until the device record, token, card assignment, reachable endpoint, and network/TLS details are set up correctly.

## Timezone Verification

RFID tap time is normalized to Philippines time on the HRIS side.

Verified behavior:

- The firmware sample sends `scanned_at` in UTC ISO 8601 format with a trailing `Z`.
- The server parses that value through `rfidParseScannedAt()` and converts it to `Asia/Manila` before any attendance logic is applied.
- `attendance_date`, `time_in`, `time_out`, duplicate detection, and late-minutes logic all use the Manila-normalized timestamp.
- If the device omits `scanned_at`, the server falls back to `now` in `Asia/Manila`.

Practical outcome:

- A tap such as `2026-03-22T00:30:00Z` is stored as `2026-03-22T08:30:00+08:00`.
- A tap such as `2026-03-22T16:01:00Z` crosses the local midnight boundary and is stored under `2026-03-23` in Philippines time.

Operational caution:

- The main mismatch risk is not UTC versus Philippines conversion. That conversion is already handled.
- The real risk is an unsynchronized device clock. If the scanner clock is wrong, the server can still reject the tap with `timestamp_out_of_range` or place it on the wrong local date if the sent timestamp itself is wrong.