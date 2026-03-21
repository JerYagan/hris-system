<?php

require_once __DIR__ . '/system-helpers.php';
require_once __DIR__ . '/../../employee/includes/lib/common.php';

if (!function_exists('rfidBuildUuidV4')) {
    function rfidBuildUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}

if (!function_exists('rfidNormalizeCardUid')) {
    function rfidNormalizeCardUid(?string $value): string
    {
        $raw = strtoupper(trim((string)$value));
        if ($raw === '') {
            return '';
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', $raw);
        return is_string($normalized) ? $normalized : '';
    }
}

if (!function_exists('rfidMaskCardUid')) {
    function rfidMaskCardUid(?string $value): string
    {
        $normalized = rfidNormalizeCardUid($value);
        if ($normalized === '') {
            return 'Unassigned';
        }

        $length = strlen($normalized);
        if ($length <= 4) {
            return str_repeat('*', max(0, $length - 1)) . substr($normalized, -1);
        }

        return substr($normalized, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($normalized, -2);
    }
}

if (!function_exists('rfidParseScannedAt')) {
    function rfidParseScannedAt(?string $rawValue): ?DateTimeImmutable
    {
        $timezone = new DateTimeZone('Asia/Manila');
        $raw = trim((string)$rawValue);

        if ($raw === '') {
            return new DateTimeImmutable('now', $timezone);
        }

        try {
            $parsed = new DateTimeImmutable($raw, $timezone);
            return $parsed->setTimezone($timezone);
        } catch (Throwable) {
            $timestamp = strtotime($raw);
            if ($timestamp === false) {
                return null;
            }

            return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
        }
    }
}

if (!function_exists('rfidAllowedTimestampSkewSeconds')) {
    function rfidAllowedTimestampSkewSeconds(): int
    {
        $raw = systemEnvValue('HRIS_RFID_DEVICE_TIMESTAMP_SKEW_SECONDS');
        if ($raw === null) {
            return 300;
        }

        $seconds = (int)$raw;
        return $seconds > 0 ? $seconds : 300;
    }
}

if (!function_exists('rfidSimulatorEnabled')) {
    function rfidSimulatorEnabled(string $supabaseUrl, array $headers): bool
    {
        $envValue = strtolower(trim((string)(systemEnvValue('HRIS_RFID_SIMULATOR_ENABLED') ?? '')));
        if (in_array($envValue, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($envValue, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/system_settings?select=setting_value'
            . '&setting_key=eq.' . rawurlencode('timekeeping.rfid_simulator_enabled')
            . '&limit=1',
            $headers
        );

        $settingValue = '';
        if (isSuccessful($response)) {
            $row = is_array($response['data'][0] ?? null) ? (array)$response['data'][0] : [];
            $rawValue = $row['setting_value'] ?? null;
            if (is_array($rawValue)) {
                $settingValue = strtolower(trim((string)($rawValue['value'] ?? $rawValue['enabled'] ?? '')));
            } else {
                $settingValue = strtolower(trim((string)$rawValue));
            }
        }

        if (in_array($settingValue, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
            return true;
        }

        if (in_array($settingValue, ['0', 'false', 'no', 'off', 'disabled'], true)) {
            return false;
        }

        $hostCandidates = [
            strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? ''))),
            strtolower(trim((string)($_SERVER['SERVER_NAME'] ?? ''))),
            strtolower(trim((string)parse_url((string)($_SERVER['REQUEST_SCHEME'] ?? '') . '://' . (string)($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST))),
        ];

        foreach ($hostCandidates as $host) {
            if ($host === '') {
                continue;
            }

            $normalizedHost = explode(':', $host, 2)[0];
            if (in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('rfidLateMinutesForTap')) {
    function rfidLateMinutesForTap(DateTimeImmutable $scannedAt): int
    {
        $reference = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $scannedAt->format('Y-m-d') . ' 09:00:00',
            new DateTimeZone('Asia/Manila')
        );

        if (!($reference instanceof DateTimeImmutable) || $scannedAt <= $reference) {
            return 0;
        }

        return (int)floor(($scannedAt->getTimestamp() - $reference->getTimestamp()) / 60);
    }
}

if (!function_exists('rfidVerifyDeviceToken')) {
    function rfidVerifyDeviceToken(?string $providedToken, ?string $storedHash): bool
    {
        $provided = trim((string)$providedToken);
        $stored = trim((string)$storedHash);
        if ($provided === '' || $stored === '') {
            return false;
        }

        if (hash_equals($stored, $provided)) {
            return true;
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $stored) === 1 && hash_equals($stored, hash('sha256', $provided))) {
            return true;
        }

        $passwordInfo = password_get_info($stored);
        if (($passwordInfo['algo'] ?? null) !== null) {
            return password_verify($provided, $stored);
        }

        return false;
    }
}

if (!function_exists('rfidSanitizeEventPayload')) {
    function rfidSanitizeEventPayload(array $payload): array
    {
        $sanitized = $payload;
        foreach (['device_token', 'token', 'authorization', 'Authorization'] as $key) {
            if (array_key_exists($key, $sanitized)) {
                $sanitized[$key] = '[redacted]';
            }
        }

        return $sanitized;
    }
}

if (!function_exists('rfidApiFirstRow')) {
    function rfidApiFirstRow(array $response): array
    {
        if (!isSuccessful($response)) {
            return [];
        }

        $rows = (array)($response['data'] ?? []);
        return isset($rows[0]) && is_array($rows[0]) ? (array)$rows[0] : [];
    }
}

if (!function_exists('rfidResolveActiveDevice')) {
    function rfidResolveActiveDevice(string $supabaseUrl, array $headers, ?string $deviceCode): array
    {
        $normalizedCode = trim((string)$deviceCode);
        if ($normalizedCode === '') {
            return [];
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/rfid_devices?select=id,device_code,device_name,device_token_hash,status,office_id'
            . '&device_code=eq.' . rawurlencode($normalizedCode)
            . '&status=eq.active'
            . '&limit=1',
            $headers
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidMarkDeviceSeen')) {
    function rfidMarkDeviceSeen(string $supabaseUrl, array $headers, ?string $deviceId): void
    {
        $id = cleanText($deviceId);
        if (!isValidUuid($id)) {
            return;
        }

        apiRequest(
            'PATCH',
            rtrim($supabaseUrl, '/') . '/rest/v1/rfid_devices?id=eq.' . rawurlencode((string)$id),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'last_seen_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
            ]
        );
    }
}

if (!function_exists('rfidResolvePersonProfile')) {
    function rfidResolvePersonProfile(string $supabaseUrl, array $headers, ?string $personId): array
    {
        $id = cleanText($personId);
        if (!isValidUuid($id)) {
            return [];
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/people?select=id,first_name,surname,agency_employee_no'
            . '&id=eq.' . rawurlencode((string)$id)
            . '&limit=1',
            $headers
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidResolveActiveCardByUid')) {
    function rfidResolveActiveCardByUid(string $supabaseUrl, array $headers, ?string $cardUid): array
    {
        $normalizedUid = rfidNormalizeCardUid($cardUid);
        if ($normalizedUid === '') {
            return [];
        }

        $cardResponse = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/rfid_cards?select=id,person_id,card_uid,card_label,status,issued_at'
            . '&card_uid=ilike.' . rawurlencode($normalizedUid)
            . '&status=eq.active'
            . '&limit=1',
            $headers
        );

        $card = rfidApiFirstRow($cardResponse);
        if ($card === []) {
            return [];
        }

        $person = rfidResolvePersonProfile($supabaseUrl, $headers, cleanText($card['person_id'] ?? null));
        if ($person !== []) {
            $card['person'] = $person;
        }

        return $card;
    }
}

if (!function_exists('rfidResolveActiveCardForPerson')) {
    function rfidResolveActiveCardForPerson(string $supabaseUrl, array $headers, ?string $personId): array
    {
        $id = cleanText($personId);
        if (!isValidUuid($id)) {
            return [];
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/rfid_cards?select=id,person_id,card_uid,card_label,status,issued_at'
            . '&person_id=eq.' . rawurlencode((string)$id)
            . '&status=eq.active'
            . '&limit=1',
            $headers
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidResolveLatestScanEvent')) {
    function rfidResolveLatestScanEvent(string $supabaseUrl, array $headers, string $cardUid): array
    {
        $normalizedUid = rfidNormalizeCardUid($cardUid);
        if ($normalizedUid === '') {
            return [];
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/rfid_scan_events?select=id,device_id,person_id,scanned_at,request_source'
            . '&card_uid=ilike.' . rawurlencode($normalizedUid)
            . '&order=scanned_at.desc'
            . '&limit=1',
            $headers
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidResolveAttendanceLogByDate')) {
    function rfidResolveAttendanceLogByDate(string $supabaseUrl, array $headers, string $personId, string $attendanceDate): array
    {
        if (!isValidUuid($personId) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)) {
            return [];
        }

        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/')
            . '/rest/v1/attendance_logs?select=id,person_id,attendance_date,time_in,time_out,hours_worked,late_minutes,attendance_status,source,rfid_scan_event_id,capture_device_id'
            . '&person_id=eq.' . rawurlencode($personId)
            . '&attendance_date=eq.' . rawurlencode($attendanceDate)
            . '&limit=1',
            $headers
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidCreateScanEvent')) {
    function rfidCreateScanEvent(string $supabaseUrl, array $headers, array $payload): array
    {
        $response = apiRequest(
            'POST',
            rtrim($supabaseUrl, '/') . '/rest/v1/rfid_scan_events',
            array_merge($headers, ['Prefer: return=representation']),
            [$payload]
        );

        return rfidApiFirstRow($response);
    }
}

if (!function_exists('rfidUpdateScanEventAttendanceLink')) {
    function rfidUpdateScanEventAttendanceLink(string $supabaseUrl, array $headers, ?string $scanEventId, ?string $attendanceLogId): void
    {
        $eventId = cleanText($scanEventId);
        $attendanceId = cleanText($attendanceLogId);
        if (!isValidUuid($eventId) || !isValidUuid($attendanceId)) {
            return;
        }

        apiRequest(
            'PATCH',
            rtrim($supabaseUrl, '/') . '/rest/v1/rfid_scan_events?id=eq.' . rawurlencode((string)$eventId),
            array_merge($headers, ['Prefer: return=minimal']),
            ['attendance_log_id' => $attendanceId]
        );
    }
}

if (!function_exists('rfidUpdateAttendanceScanLink')) {
    function rfidUpdateAttendanceScanLink(string $supabaseUrl, array $headers, ?string $attendanceLogId, ?string $scanEventId): void
    {
        $attendanceId = cleanText($attendanceLogId);
        $eventId = cleanText($scanEventId);
        if (!isValidUuid($attendanceId) || !isValidUuid($eventId)) {
            return;
        }

        apiRequest(
            'PATCH',
            rtrim($supabaseUrl, '/') . '/rest/v1/attendance_logs?id=eq.' . rawurlencode((string)$attendanceId),
            array_merge($headers, ['Prefer: return=minimal']),
            ['rfid_scan_event_id' => $eventId]
        );
    }
}

if (!function_exists('rfidActivityLog')) {
    function rfidActivityLog(string $supabaseUrl, array $headers, ?string $actorUserId, array $payload): void
    {
        $body = [[
            'actor_user_id' => isValidUuid(cleanText($actorUserId)) ? cleanText($actorUserId) : null,
            'module_name' => 'timekeeping',
            'entity_name' => (string)($payload['entity_name'] ?? 'attendance_logs'),
            'entity_id' => isValidUuid(cleanText($payload['entity_id'] ?? null)) ? cleanText($payload['entity_id'] ?? null) : null,
            'action_name' => (string)($payload['action_name'] ?? 'rfid_attendance_tap'),
            'old_data' => is_array($payload['old_data'] ?? null) ? (array)$payload['old_data'] : null,
            'new_data' => is_array($payload['new_data'] ?? null) ? (array)$payload['new_data'] : null,
        ]];

        apiRequest(
            'POST',
            rtrim($supabaseUrl, '/') . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            $body
        );
    }
}

if (!function_exists('rfidUpsertAttendanceFromTap')) {
    function rfidUpsertAttendanceFromTap(string $supabaseUrl, array $headers, array $context): array
    {
        $personId = (string)($context['person_id'] ?? '');
        $attendanceDate = (string)($context['attendance_date'] ?? '');
        $scannedAt = $context['scanned_at'] instanceof DateTimeImmutable
            ? $context['scanned_at']
            : rfidParseScannedAt(null);
        $captureDeviceId = cleanText($context['capture_device_id'] ?? null);
        $actorUserId = cleanText($context['actor_user_id'] ?? null);
        $existingLog = is_array($context['existing_log'] ?? null) ? (array)$context['existing_log'] : [];

        if (!$scannedAt instanceof DateTimeImmutable || !isValidUuid($personId) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)) {
            return [
                'success' => false,
                'http_status' => 422,
                'result_code' => 'invalid_context',
                'message' => 'Attendance tap context is invalid.',
            ];
        }

        $scannedAtIso = $scannedAt->format('c');
        $lateMinutes = rfidLateMinutesForTap($scannedAt);
        $status = $lateMinutes > 0 ? 'late' : 'present';

        if ($existingLog === []) {
            $payload = [
                'person_id' => $personId,
                'attendance_date' => $attendanceDate,
                'time_in' => $scannedAtIso,
                'attendance_status' => $status,
                'late_minutes' => $lateMinutes,
                'source' => 'rfid',
            ];
            if (isValidUuid($captureDeviceId)) {
                $payload['capture_device_id'] = $captureDeviceId;
            }
            if (isValidUuid($actorUserId)) {
                $payload['recorded_by'] = $actorUserId;
            }

            $response = apiRequest(
                'POST',
                rtrim($supabaseUrl, '/') . '/rest/v1/attendance_logs',
                array_merge($headers, ['Prefer: return=representation']),
                [$payload]
            );
            $savedLog = rfidApiFirstRow($response);
            if ($savedLog === []) {
                return [
                    'success' => false,
                    'http_status' => 500,
                    'result_code' => 'attendance_write_failed',
                    'message' => 'Unable to create the attendance record.',
                ];
            }

            return [
                'success' => true,
                'http_status' => 200,
                'result_code' => 'time_in_logged',
                'action' => 'time_in',
                'message' => 'Time-in logged successfully.',
                'attendance_log' => $savedLog,
            ];
        }

        $existingLogId = cleanText($existingLog['id'] ?? null) ?? '';
        $existingTimeIn = cleanText($existingLog['time_in'] ?? null);
        $existingTimeOut = cleanText($existingLog['time_out'] ?? null);
        $existingSource = cleanText($existingLog['source'] ?? null);

        if ($existingTimeIn === null) {
            $payload = [
                'time_in' => $scannedAtIso,
                'attendance_status' => $status,
                'late_minutes' => $lateMinutes,
            ];
            if ($existingSource === null) {
                $payload['source'] = 'rfid';
            }
            if (isValidUuid($captureDeviceId)) {
                $payload['capture_device_id'] = $captureDeviceId;
            }

            $response = apiRequest(
                'PATCH',
                rtrim($supabaseUrl, '/') . '/rest/v1/attendance_logs?id=eq.' . rawurlencode($existingLogId),
                array_merge($headers, ['Prefer: return=representation']),
                $payload
            );
            $savedLog = rfidApiFirstRow($response);
            if ($savedLog === []) {
                return [
                    'success' => false,
                    'http_status' => 500,
                    'result_code' => 'attendance_write_failed',
                    'message' => 'Unable to update time-in for the attendance record.',
                ];
            }

            return [
                'success' => true,
                'http_status' => 200,
                'result_code' => 'time_in_logged',
                'action' => 'time_in',
                'message' => 'Time-in logged successfully.',
                'attendance_log' => $savedLog,
            ];
        }

        if ($existingTimeOut === null) {
            $existingTimeInTs = strtotime($existingTimeIn);
            if ($existingTimeInTs !== false && $scannedAt->getTimestamp() <= $existingTimeInTs) {
                return [
                    'success' => false,
                    'http_status' => 409,
                    'result_code' => 'invalid_tap_sequence',
                    'message' => 'Tap time must be later than the existing time-in.',
                ];
            }

            $hoursWorked = 0.0;
            if ($existingTimeInTs !== false) {
                $hoursWorked = round(max(0, ($scannedAt->getTimestamp() - $existingTimeInTs) / 3600), 2);
            }

            $payload = [
                'time_out' => $scannedAtIso,
                'hours_worked' => $hoursWorked,
            ];
            if (isValidUuid($captureDeviceId)) {
                $payload['capture_device_id'] = $captureDeviceId;
            }

            $response = apiRequest(
                'PATCH',
                rtrim($supabaseUrl, '/') . '/rest/v1/attendance_logs?id=eq.' . rawurlencode($existingLogId),
                array_merge($headers, ['Prefer: return=representation']),
                $payload
            );
            $savedLog = rfidApiFirstRow($response);
            if ($savedLog === []) {
                return [
                    'success' => false,
                    'http_status' => 500,
                    'result_code' => 'attendance_write_failed',
                    'message' => 'Unable to update time-out for the attendance record.',
                ];
            }

            return [
                'success' => true,
                'http_status' => 200,
                'result_code' => 'time_out_logged',
                'action' => 'time_out',
                'message' => 'Time-out logged successfully.',
                'attendance_log' => $savedLog,
            ];
        }

        return [
            'success' => false,
            'http_status' => 409,
            'result_code' => 'attendance_already_complete',
            'message' => 'Attendance for the day is already complete.',
            'attendance_log' => $existingLog,
        ];
    }
}

if (!function_exists('rfidProcessAttendanceTap')) {
    function rfidProcessAttendanceTap(string $supabaseUrl, array $headers, array $payload): array
    {
        $requestSource = strtolower(trim((string)($payload['request_source'] ?? 'device')));
        if (!in_array($requestSource, ['device', 'employee_simulation'], true)) {
            $requestSource = 'device';
        }

        $deviceCode = cleanText($payload['device_code'] ?? null);
        $deviceToken = cleanText($payload['device_token'] ?? null);
        $actorUserId = cleanText($payload['actor_user_id'] ?? null);
        $employeePersonId = cleanText($payload['employee_person_id'] ?? null);
        $rawPayload = is_array($payload['raw_payload'] ?? null) ? (array)$payload['raw_payload'] : [];
        $cardUid = rfidNormalizeCardUid($payload['card_uid'] ?? null);
        $scannedAt = rfidParseScannedAt(cleanText($payload['scanned_at'] ?? null));

        if ($cardUid === '') {
            return [
                'success' => false,
                'http_status' => 422,
                'result_code' => 'invalid_card_uid',
                'message' => 'Card UID is required.',
            ];
        }

        if (!$scannedAt instanceof DateTimeImmutable) {
            return [
                'success' => false,
                'http_status' => 422,
                'result_code' => 'invalid_timestamp',
                'message' => 'Scanned timestamp is invalid.',
            ];
        }

        if ($requestSource === 'device') {
            $allowedSkewSeconds = rfidAllowedTimestampSkewSeconds();
            $serverNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
            $skewSeconds = abs($serverNow->getTimestamp() - $scannedAt->getTimestamp());
            if ($skewSeconds > $allowedSkewSeconds) {
                $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                    'device_id' => null,
                    'person_id' => null,
                    'card_uid' => $cardUid,
                    'scanned_at' => $scannedAt->format('c'),
                    'request_source' => 'device',
                    'result_code' => 'timestamp_out_of_range',
                    'result_message' => 'Device timestamp is outside the allowed skew window.',
                    'raw_payload' => rfidSanitizeEventPayload($rawPayload),
                ]);

                return [
                    'success' => false,
                    'http_status' => 422,
                    'result_code' => 'timestamp_out_of_range',
                    'message' => 'Device timestamp is outside the allowed skew window.',
                    'scan_event_id' => cleanText($event['id'] ?? null),
                ];
            }
        }

        $deviceId = null;
        $device = [];
        if ($requestSource === 'device') {
            $device = rfidResolveActiveDevice($supabaseUrl, $headers, $deviceCode);
            if ($device === []) {
                $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                    'device_id' => null,
                    'person_id' => null,
                    'card_uid' => $cardUid,
                    'scanned_at' => $scannedAt->format('c'),
                    'request_source' => 'device',
                    'result_code' => 'device_not_allowed',
                    'result_message' => 'Device is not registered or inactive.',
                    'raw_payload' => rfidSanitizeEventPayload($rawPayload),
                ]);

                return [
                    'success' => false,
                    'http_status' => 403,
                    'result_code' => 'device_not_allowed',
                    'message' => 'Device is not registered or inactive.',
                    'scan_event_id' => cleanText($event['id'] ?? null),
                ];
            }

            $deviceId = cleanText($device['id'] ?? null);
            if (!rfidVerifyDeviceToken($deviceToken, cleanText($device['device_token_hash'] ?? null))) {
                $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                    'device_id' => isValidUuid($deviceId) ? $deviceId : null,
                    'person_id' => null,
                    'card_uid' => $cardUid,
                    'scanned_at' => $scannedAt->format('c'),
                    'request_source' => 'device',
                    'result_code' => 'device_not_allowed',
                    'result_message' => 'Device authentication failed.',
                    'raw_payload' => rfidSanitizeEventPayload($rawPayload),
                ]);

                return [
                    'success' => false,
                    'http_status' => 403,
                    'result_code' => 'device_not_allowed',
                    'message' => 'Device authentication failed.',
                    'scan_event_id' => cleanText($event['id'] ?? null),
                ];
            }

            rfidMarkDeviceSeen($supabaseUrl, $headers, $deviceId);
        } elseif (!isValidUuid($employeePersonId)) {
            return [
                'success' => false,
                'http_status' => 403,
                'result_code' => 'employee_context_missing',
                'message' => 'Employee simulator requires a valid employee context.',
            ];
        }

        $card = rfidResolveActiveCardByUid($supabaseUrl, $headers, $cardUid);
        if ($card === []) {
            $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                'device_id' => isValidUuid($deviceId) ? $deviceId : null,
                'person_id' => null,
                'card_uid' => $cardUid,
                'scanned_at' => $scannedAt->format('c'),
                'request_source' => $requestSource,
                'result_code' => 'card_not_registered',
                'result_message' => 'Card UID is not assigned to an active employee card.',
                'raw_payload' => rfidSanitizeEventPayload($rawPayload),
            ]);

            return [
                'success' => false,
                'http_status' => 404,
                'result_code' => 'card_not_registered',
                'message' => 'Card UID is not assigned to an active employee card.',
                'scan_event_id' => cleanText($event['id'] ?? null),
            ];
        }

        $personId = cleanText($card['person_id'] ?? null);
        if (!isValidUuid($personId)) {
            return [
                'success' => false,
                'http_status' => 500,
                'result_code' => 'card_mapping_invalid',
                'message' => 'Card assignment is missing a valid employee reference.',
            ];
        }

        if ($requestSource === 'employee_simulation' && $employeePersonId !== $personId) {
            $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                'device_id' => null,
                'person_id' => $personId,
                'card_uid' => $cardUid,
                'scanned_at' => $scannedAt->format('c'),
                'request_source' => 'employee_simulation',
                'result_code' => 'card_not_owned_by_employee',
                'result_message' => 'The assigned RFID card does not belong to the logged-in employee.',
                'raw_payload' => rfidSanitizeEventPayload($rawPayload),
            ]);

            return [
                'success' => false,
                'http_status' => 403,
                'result_code' => 'card_not_owned_by_employee',
                'message' => 'The assigned RFID card does not belong to the logged-in employee.',
                'scan_event_id' => cleanText($event['id'] ?? null),
            ];
        }

        $latestEvent = rfidResolveLatestScanEvent($supabaseUrl, $headers, $cardUid);
        if ($latestEvent !== []) {
            $latestScannedAt = rfidParseScannedAt(cleanText($latestEvent['scanned_at'] ?? null));
            $latestRequestSource = strtolower(trim((string)($latestEvent['request_source'] ?? '')));
            $latestDeviceId = cleanText($latestEvent['device_id'] ?? null);
            $sameSource = $latestRequestSource === $requestSource;
            $sameDevice = $requestSource === 'device'
                ? ($latestDeviceId !== null && $deviceId !== null && $latestDeviceId === $deviceId)
                : true;

            if ($latestScannedAt instanceof DateTimeImmutable && $sameSource && $sameDevice) {
                $secondsSinceLatest = $scannedAt->getTimestamp() - $latestScannedAt->getTimestamp();
                if ($secondsSinceLatest >= 0 && $secondsSinceLatest <= 15) {
                    $event = rfidCreateScanEvent($supabaseUrl, $headers, [
                        'device_id' => isValidUuid($deviceId) ? $deviceId : null,
                        'person_id' => $personId,
                        'card_uid' => $cardUid,
                        'scanned_at' => $scannedAt->format('c'),
                        'request_source' => $requestSource,
                        'result_code' => 'duplicate_ignored',
                        'result_message' => 'Rapid duplicate tap ignored.',
                        'raw_payload' => rfidSanitizeEventPayload($rawPayload),
                    ]);

                    return [
                        'success' => true,
                        'http_status' => 200,
                        'result_code' => 'duplicate_ignored',
                        'action' => 'duplicate_ignored',
                        'message' => 'Rapid duplicate tap ignored.',
                        'employee_name' => trim((string)($card['person']['first_name'] ?? '') . ' ' . (string)($card['person']['surname'] ?? '')),
                        'scan_event_id' => cleanText($event['id'] ?? null),
                    ];
                }
            }
        }

        $attendanceDate = $scannedAt->format('Y-m-d');
        $existingLog = rfidResolveAttendanceLogByDate($supabaseUrl, $headers, $personId, $attendanceDate);
        $attendanceResult = rfidUpsertAttendanceFromTap($supabaseUrl, $headers, [
            'person_id' => $personId,
            'attendance_date' => $attendanceDate,
            'scanned_at' => $scannedAt,
            'capture_device_id' => $deviceId,
            'actor_user_id' => $actorUserId,
            'existing_log' => $existingLog,
        ]);

        $employeeName = trim((string)($card['person']['first_name'] ?? '') . ' ' . (string)($card['person']['surname'] ?? ''));
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        $attendanceLog = is_array($attendanceResult['attendance_log'] ?? null) ? (array)$attendanceResult['attendance_log'] : [];
        $attendanceLogId = cleanText($attendanceLog['id'] ?? null);

        $event = rfidCreateScanEvent($supabaseUrl, $headers, [
            'device_id' => isValidUuid($deviceId) ? $deviceId : null,
            'person_id' => $personId,
            'card_uid' => $cardUid,
            'attendance_log_id' => isValidUuid($attendanceLogId) ? $attendanceLogId : null,
            'scanned_at' => $scannedAt->format('c'),
            'request_source' => $requestSource,
            'result_code' => (string)($attendanceResult['result_code'] ?? 'attendance_processed'),
            'result_message' => (string)($attendanceResult['message'] ?? 'Attendance processed.'),
            'raw_payload' => rfidSanitizeEventPayload($rawPayload),
        ]);
        $scanEventId = cleanText($event['id'] ?? null);

        if (isValidUuid($attendanceLogId) && isValidUuid($scanEventId)) {
            rfidUpdateAttendanceScanLink($supabaseUrl, $headers, $attendanceLogId, $scanEventId);
            rfidUpdateScanEventAttendanceLink($supabaseUrl, $headers, $scanEventId, $attendanceLogId);
        }

        if ((bool)($attendanceResult['success'] ?? false)) {
            rfidActivityLog($supabaseUrl, $headers, $actorUserId, [
                'entity_name' => 'attendance_logs',
                'entity_id' => $attendanceLogId,
                'action_name' => 'rfid_attendance_tap',
                'new_data' => [
                    'person_id' => $personId,
                    'attendance_date' => $attendanceDate,
                    'result_code' => (string)($attendanceResult['result_code'] ?? ''),
                    'request_source' => $requestSource,
                    'card_uid_masked' => rfidMaskCardUid($cardUid),
                    'scan_event_id' => $scanEventId,
                ],
            ]);
        }

        return [
            'success' => (bool)($attendanceResult['success'] ?? false),
            'http_status' => (int)($attendanceResult['http_status'] ?? 200),
            'result_code' => (string)($attendanceResult['result_code'] ?? 'attendance_processed'),
            'action' => (string)($attendanceResult['action'] ?? ''),
            'message' => (string)($attendanceResult['message'] ?? 'Attendance processed.'),
            'employee_name' => $employeeName,
            'attendance_date' => $attendanceDate,
            'attendance_log_id' => $attendanceLogId,
            'scan_event_id' => $scanEventId,
        ];
    }
}