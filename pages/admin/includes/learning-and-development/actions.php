<?php

require_once __DIR__ . '/../notifications/email.php';

if (!function_exists('smtpConfigIsReady')) {
    function smtpConfigIsReady(array $smtpConfig, string $fromEmail): bool
    {
        return false;
    }
}

if (!function_exists('smtpSendTransactionalEmail')) {
    function smtpSendTransactionalEmail(array $smtpConfig, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        return [
            'status' => 500,
            'data' => [],
            'raw' => 'SMTP helper not loaded.',
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if ($action === 'create_training') {
    $trainingType = cleanText($_POST['training_type'] ?? null) ?? '';
    $trainingCategory = cleanText($_POST['training_category'] ?? null) ?? '';
    $scheduleDate = cleanText($_POST['schedule_date'] ?? null) ?? '';
    $scheduleTime = cleanText($_POST['schedule_time'] ?? null) ?? '';
    $provider = cleanText($_POST['provider'] ?? null) ?? '';
    $venue = cleanText($_POST['venue'] ?? null) ?? '';
    $mode = strtolower((string)(cleanText($_POST['mode'] ?? null) ?? ''));
    $participantIdsInput = $_POST['participant_ids'] ?? [];

    if ($trainingType === '' || $trainingCategory === '' || $scheduleDate === '' || $scheduleTime === '' || $provider === '' || $venue === '' || $mode === '') {
        redirectWithState('error', 'Training type, category, schedule, provider, venue, and mode are required.');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduleDate)) {
        redirectWithState('error', 'Invalid schedule date format.');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
        redirectWithState('error', 'Invalid schedule time format.');
    }

    if (!in_array($mode, ['online', 'onsite', 'hybrid'], true)) {
        redirectWithState('error', 'Invalid training mode selected.');
    }

    $participantIds = [];
    $rawParticipantIds = is_array($participantIdsInput) ? $participantIdsInput : [$participantIdsInput];
    foreach ($rawParticipantIds as $participantId) {
        $value = cleanText($participantId) ?? '';
        if ($value === '') {
            continue;
        }

        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value)) {
            redirectWithState('error', 'One or more selected participants are invalid.');
        }

        $participantIds[$value] = $value;
    }

    $programCode = 'TRN-' . gmdate('YmdHis') . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $adminUserId), 0, 4));
    if (substr($programCode, -1) === '-') {
        $programCode .= strtoupper((string)random_int(100, 999));
    }

    $title = $trainingType . ' - ' . $trainingCategory;
    $fullSchedule = $scheduleDate . ' ' . $scheduleTime;

    $programPayloadWithExtendedFields = [
        'program_code' => $programCode,
        'title' => $title,
        'training_type' => $trainingType,
        'training_category' => $trainingCategory,
        'provider' => $provider,
        'venue' => $venue,
        'schedule_time' => $scheduleTime,
        'start_date' => $scheduleDate,
        'end_date' => $scheduleDate,
        'mode' => $mode,
        'status' => 'planned',
    ];

    $programPayloadBase = [
        'program_code' => $programCode,
        'title' => $title,
        'provider' => $provider,
        'start_date' => $scheduleDate,
        'end_date' => $scheduleDate,
        'mode' => $mode,
        'status' => 'planned',
    ];

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/training_programs',
        array_merge($headers, ['Prefer: return=representation']),
        [$programPayloadWithExtendedFields]
    );

    $usedSchemaFallback = false;
    if (!isSuccessful($insertResponse)) {
        $errorRaw = strtolower((string)($insertResponse['raw'] ?? ''));
        $missingExtendedColumn = str_contains($errorRaw, 'training_type')
            || str_contains($errorRaw, 'training_category')
            || str_contains($errorRaw, 'venue')
            || str_contains($errorRaw, 'schedule_time');

        if ($missingExtendedColumn) {
            $usedSchemaFallback = true;
            $insertResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/training_programs',
                array_merge($headers, ['Prefer: return=representation']),
                [$programPayloadBase]
            );
        }
    }

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to save training.');
    }

    $programId = (string)($insertResponse['data'][0]['id'] ?? '');
    if ($programId === '') {
        $programLookupResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/training_programs?select=id&program_code=eq.' . encodeFilter($programCode) . '&limit=1',
            $headers
        );

        $programId = (string)($programLookupResponse['data'][0]['id'] ?? '');
    }

    if ($programId === '') {
        redirectWithState('error', 'Training was created but could not be loaded for participant enrollment.');
    }

    $participantIdsList = array_values($participantIds);
    if (!empty($participantIdsList)) {
        $enrollmentRows = [];
        foreach ($participantIdsList as $participantId) {
            $enrollmentRows[] = [
                'program_id' => $programId,
                'person_id' => $participantId,
                'enrollment_status' => 'enrolled',
            ];
        }

        $enrollResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/training_enrollments?on_conflict=program_id,person_id',
            array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
            $enrollmentRows
        );

        if (!isSuccessful($enrollResponse)) {
            redirectWithState('error', 'Training was saved, but participant enrollment failed.');
        }
    }

    $peopleRows = [];
    if (!empty($participantIdsList)) {
        $peopleResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/people?select=id,user_id,first_name,surname,personal_email&id=in.(' . implode(',', $participantIdsList) . ')&limit=1000',
            $headers
        );
        $peopleRows = isSuccessful($peopleResponse) ? (array)($peopleResponse['data'] ?? []) : [];
    }

    $peopleById = [];
    foreach ($peopleRows as $person) {
        $personId = (string)($person['id'] ?? '');
        if ($personId === '') {
            continue;
        }
        $peopleById[$personId] = $person;
    }

    $emailAddressByUserId = [];
    if (!empty($participantIdsList)) {
        $userIds = [];
        foreach ($peopleRows as $person) {
            $candidateUserId = strtolower(trim((string)($person['user_id'] ?? '')));
            if (preg_match('/^[0-9a-fA-F-]{36}$/', $candidateUserId)) {
                $userIds[] = $candidateUserId;
            }
        }

        $userIds = array_values(array_unique($userIds));
        if (!empty($userIds)) {
            $usersResponse = apiRequest(
                'GET',
                $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=in.(' . implode(',', $userIds) . ')&limit=5000',
                $headers
            );

            if (isSuccessful($usersResponse)) {
                foreach ((array)($usersResponse['data'] ?? []) as $userRow) {
                    $userId = strtolower(trim((string)($userRow['id'] ?? '')));
                    $email = strtolower(trim((string)($userRow['email'] ?? '')));
                    if ($userId !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emailAddressByUserId[$userId] = $email;
                    }
                }
            }
        }
    }

    $notificationsQueued = 0;
    $notificationsFailed = 0;

    if (!empty($participantIdsList)) {
        $scheduleDateLabel = date('M d, Y', strtotime($scheduleDate));
        $scheduleTimeLabel = date('h:i A', strtotime('1970-01-01 ' . $scheduleTime));
        $notificationRows = [];

        foreach ($participantIdsList as $participantId) {
            $person = $peopleById[$participantId] ?? null;
            if (!is_array($person)) {
                continue;
            }

            $recipientUserId = strtolower(trim((string)($person['user_id'] ?? '')));
            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $recipientUserId)) {
                continue;
            }

            $notificationRows[$recipientUserId] = [
                'recipient_user_id' => $recipientUserId,
                'category' => 'learning_and_development',
                'title' => 'New Training Schedule',
                'body' => 'You are enrolled in ' . $trainingType . ' (' . $trainingCategory . ') on ' . $scheduleDateLabel . ' at ' . $scheduleTimeLabel . '.',
                'link_url' => '/hris-system/pages/employee/notifications.php',
            ];
        }

        if (!empty($notificationRows)) {
            $notificationPayload = array_values($notificationRows);
            $notifyResponse = apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                $notificationPayload
            );

            if (isSuccessful($notifyResponse)) {
                $notificationsQueued = count($notificationPayload);
            } else {
                $notificationsFailed = count($notificationPayload);
            }
        }
    }

    $emailsSent = 0;
    $emailsFailed = 0;

    $emailReady = smtpConfigIsReady($smtpConfig, $mailFrom);
    if ($emailReady && !empty($participantIdsList)) {
        $scheduleDateLabel = date('M d, Y', strtotime($scheduleDate));
        $scheduleTimeLabel = date('h:i A', strtotime('1970-01-01 ' . $scheduleTime));

        foreach ($participantIdsList as $participantId) {
            $person = $peopleById[$participantId] ?? null;
            if (!is_array($person)) {
                $emailsFailed++;
                continue;
            }

            $recipientUserId = strtolower(trim((string)($person['user_id'] ?? '')));
            $toEmail = $emailAddressByUserId[$recipientUserId] ?? '';
            if ($toEmail === '') {
                $toEmail = strtolower(trim((string)(cleanText($person['personal_email'] ?? null) ?? '')));
            }
            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $emailsFailed++;
                continue;
            }

            $firstName = (string)($person['first_name'] ?? '');
            $surname = (string)($person['surname'] ?? '');
            $fullName = trim($firstName . ' ' . $surname);

            $subject = 'New Training Schedule: ' . $title;
            $htmlContent = '<p>Hello ' . htmlspecialchars($fullName !== '' ? $fullName : 'Employee', ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>You have been enrolled in a training session.</p>'
                . '<p><strong>Type:</strong> ' . htmlspecialchars($trainingType, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Category:</strong> ' . htmlspecialchars($trainingCategory, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Schedule:</strong> ' . htmlspecialchars($scheduleDateLabel . ' ' . $scheduleTimeLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Provider:</strong> ' . htmlspecialchars($provider, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Venue:</strong> ' . htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Mode:</strong> ' . htmlspecialchars(ucfirst($mode), ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Please coordinate with your immediate supervisor if you have schedule conflicts.</p>';

            $emailResponse = smtpSendTransactionalEmail(
                $smtpConfig,
                $mailFrom,
                $mailFromName,
                $toEmail,
                $fullName,
                $subject,
                $htmlContent
            );

            if (isSuccessful($emailResponse)) {
                $emailsSent++;
            } else {
                $emailsFailed++;
            }
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'learning_and_development',
            'entity_name' => 'training_programs',
            'entity_id' => $programId,
            'action_name' => 'create_training',
            'old_data' => null,
            'new_data' => [
                'program_code' => $programCode,
                'training_type' => $trainingType,
                'training_category' => $trainingCategory,
                'schedule' => $fullSchedule,
                'provider' => $provider,
                'venue' => $venue,
                'mode' => $mode,
                'participants_count' => count($participantIdsList),
                'in_app_notifications_queued' => $notificationsQueued,
                'in_app_notifications_failed' => $notificationsFailed,
                'emails_sent' => $emailsSent,
                'emails_failed' => $emailsFailed,
                'schema_fallback_used' => $usedSchemaFallback,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if (empty($participantIdsList)) {
        $successMessage = 'Training saved. No participants were selected.';
    } else {
        $successMessage = 'Training saved and participants enrolled.';
        $successMessage .= ' In-app notifications queued: ' . $notificationsQueued . '.';
        if ($notificationsFailed > 0) {
            $successMessage .= ' Notification queue failures: ' . $notificationsFailed . '.';
        }
    }
    if (!$emailReady && !empty($participantIdsList)) {
        $successMessage .= ' Email notifications were skipped because mail settings are incomplete.';
    } elseif (!empty($participantIdsList)) {
        $successMessage .= ' Email notifications sent: ' . $emailsSent . '.';
        if ($emailsFailed > 0) {
            $successMessage .= ' Email failures: ' . $emailsFailed . '.';
        }
    }
    if ($usedSchemaFallback) {
        $successMessage .= ' Note: extended training fields are not present in current DB schema and were stored only in logs/email content.';
    }

    redirectWithState('success', $successMessage);
}

if ($action === 'update_training_attendance') {
    $enrollmentId = cleanText($_POST['enrollment_id'] ?? null) ?? '';
    $newStatus = strtolower((string)(cleanText($_POST['enrollment_status'] ?? null) ?? ''));

    if ($enrollmentId === '' || $newStatus === '') {
        redirectWithState('error', 'Enrollment and attendance status are required.');
    }

    if (!in_array($newStatus, ['enrolled', 'completed', 'failed', 'dropped'], true)) {
        redirectWithState('error', 'Invalid attendance status selected.');
    }

    $enrollmentResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/training_enrollments?select=id,enrollment_status,person_id,program_id&id=eq.' . $enrollmentId . '&limit=1',
        $headers
    );

    $enrollmentRow = $enrollmentResponse['data'][0] ?? null;
    if (!is_array($enrollmentRow)) {
        redirectWithState('error', 'Training enrollment record not found.');
    }

    $oldStatus = (string)($enrollmentRow['enrollment_status'] ?? 'enrolled');

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/training_enrollments?id=eq.' . $enrollmentId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'enrollment_status' => $newStatus,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update attendance status.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
            'module_name' => 'learning_and_development',
            'entity_name' => 'training_enrollments',
            'entity_id' => $enrollmentId,
            'action_name' => 'update_attendance_status',
            'old_data' => ['enrollment_status' => $oldStatus],
            'new_data' => ['enrollment_status' => $newStatus],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Attendance status updated successfully.');
}

if ($action !== '') {
    redirectWithState('error', 'Unknown learning and development action.');
}
