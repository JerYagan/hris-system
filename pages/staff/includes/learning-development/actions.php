<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

$allowedManagerRoles = ['staff', 'hr_officer', 'supervisor', 'admin'];
$isManager = in_array(strtolower((string)$staffRoleKey), $allowedManagerRoles, true);
$isAdminScope = strtolower((string)$staffRoleKey) === 'admin';
$officeScopeFilter = (!$isAdminScope && isValidUuid((string)$staffOfficeId))
    ? '&office_id=eq.' . rawurlencode((string)$staffOfficeId)
    : '';

$courseVirtualToDb = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'draft' => 'planned',
        'published' => 'open',
        'archived' => 'cancelled',
        default => 'planned',
    };
};

$courseDbToVirtual = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'planned' => 'draft',
        'open', 'ongoing' => 'published',
        'completed', 'cancelled' => 'archived',
        default => 'draft',
    };
};

$enrollmentVirtualToDb = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'pending' => 'enrolled',
        'approved' => 'completed',
        'rejected' => 'failed',
        default => 'enrolled',
    };
};

$enrollmentDbToVirtual = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'enrolled' => 'pending',
        'completed' => 'approved',
        'failed', 'dropped' => 'rejected',
        default => 'pending',
    };
};

$trainingAttendanceFromDb = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'completed' => 'present',
        'failed' => 'absent',
        'dropped' => 'dropped',
        default => 'enrolled',
    };
};

$trainingAttendanceToDb = static function (string $status): string {
    $normalized = strtolower(trim($status));
    return match ($normalized) {
        'present' => 'completed',
        'absent' => 'failed',
        'dropped' => 'dropped',
        default => 'enrolled',
    };
};

$formAction = cleanText($_POST['form_action'] ?? null) ?? '';

if ($formAction === 'create_learning_course') {
    redirectWithState('error', 'Training creation is managed by Admin. Use admin Learning and Development to create trainings.', 'learning-development.php');

    $courseTitle = cleanText($_POST['course_title'] ?? null) ?? '';
    $courseDescription = cleanText($_POST['course_description'] ?? null) ?? '';
    $courseStatus = strtolower(cleanText($_POST['course_status'] ?? null) ?? 'draft');
    $courseProvider = cleanText($_POST['course_provider'] ?? null) ?? '';
    $courseVenue = cleanText($_POST['course_venue'] ?? null) ?? '';
    $courseMode = strtolower(cleanText($_POST['course_mode'] ?? null) ?? 'onsite');
    $courseStartDate = cleanText($_POST['course_start_date'] ?? null) ?? '';
    $courseEndDate = cleanText($_POST['course_end_date'] ?? null) ?? '';

    if ($courseTitle === '') {
        redirectWithState('error', 'Course title is required.', 'learning-development.php');
    }

    if (!in_array($courseStatus, ['draft', 'published', 'archived'], true)) {
        $courseStatus = 'draft';
    }
    if (!in_array($courseMode, ['onsite', 'online', 'hybrid'], true)) {
        $courseMode = 'onsite';
    }

    $programCode = strtoupper('TRN-' . gmdate('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6));
    $today = gmdate('Y-m-d');
    $startDate = $courseStartDate !== '' ? $courseStartDate : $today;
    $endDate = $courseEndDate !== '' ? $courseEndDate : $startDate;
    if ($endDate < $startDate) {
        $endDate = $startDate;
    }
    $courseStatusDb = $courseVirtualToDb($courseStatus);

    $payload = [[
        'program_code' => $programCode,
        'title' => $courseTitle,
        'training_type' => 'General',
        'training_category' => 'General',
        'provider' => $courseProvider !== '' ? $courseProvider : ($courseDescription !== '' ? $courseDescription : null),
        'venue' => $courseVenue !== '' ? $courseVenue : 'TBD',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'mode' => $courseMode,
        'status' => $courseStatusDb,
    ]];

    $createResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/training_programs',
        array_merge($headers, ['Prefer: return=representation']),
        $payload
    );

    if (!isSuccessful($createResponse)) {
        redirectWithState('error', 'Unable to create course. Ensure learning module tables are available.', 'learning-development.php');
    }

    $courseId = cleanText($createResponse['data'][0]['id'] ?? null);
    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'learning_development',
            'entity_name' => 'learning_courses',
            'entity_id' => $courseId,
            'action_name' => 'create_learning_course',
            'new_data' => [
                'title' => $courseTitle,
                'program_code' => $programCode,
                'status' => $courseStatus,
                'provider' => $courseProvider,
                'venue' => $courseVenue,
                'mode' => $courseMode,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Course created successfully.', 'learning-development.php');
}

if ($formAction === 'update_course_status') {
    redirectWithState('error', 'Training status updates are managed by Admin.', 'learning-development.php');

    $courseId = cleanText($_POST['course_id'] ?? null) ?? '';
    $newStatus = strtolower(cleanText($_POST['new_status'] ?? null) ?? '');
    if (!isValidUuid($courseId) || $newStatus === '') {
        redirectWithState('error', 'Invalid course status update request.', 'learning-development.php');
    }

    $courseResponse = apiRequest(
        'GET',
        $supabaseUrl
            . '/rest/v1/training_programs?select=id,title,status'
            . '&id=eq.' . rawurlencode($courseId)
            . '&limit=1',
        $headers
    );

    if (!isSuccessful($courseResponse) || empty((array)($courseResponse['data'] ?? []))) {
        redirectWithState('error', 'Course not found.', 'learning-development.php');
    }

    $course = (array)$courseResponse['data'][0];
    $oldStatusDb = strtolower((string)(cleanText($course['status'] ?? null) ?? 'planned'));
    $oldStatus = $courseDbToVirtual($oldStatusDb);
    if (!canTransitionStatus('learning_courses', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid course status transition.', 'learning-development.php');
    }

    $newStatusDb = $courseVirtualToDb($newStatus);

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/training_programs?id=eq.' . rawurlencode($courseId),
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'status' => $newStatusDb,
        ]]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Unable to update course status.', 'learning-development.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'learning_development',
            'entity_name' => 'learning_courses',
            'entity_id' => $courseId,
            'action_name' => 'update_course_status',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $newStatus],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Course status updated successfully.', 'learning-development.php');
}

if ($formAction === 'create_learning_enrollment') {
    redirectWithState('error', 'Employee enrollment is now managed by Admin Learning and Development.', 'learning-development.php');
}

if ($formAction === 'update_training_attendance') {
    if (!$isManager) {
        redirectWithState('error', 'Only HR officers and supervisors can update attendance status.', 'learning-development.php');
    }

    $enrollmentId = cleanText($_POST['enrollment_id'] ?? null) ?? '';
    $attendanceStatus = strtolower(cleanText($_POST['attendance_status'] ?? null) ?? '');
    $attendanceNote = cleanText($_POST['attendance_note'] ?? null) ?? '';

    if (!isValidUuid($enrollmentId) || !in_array($attendanceStatus, ['enrolled', 'present', 'absent', 'dropped'], true)) {
        redirectWithState('error', 'Invalid attendance update request.', 'learning-development.php');
    }

    $enrollmentResponse = apiRequest(
        'GET',
        $supabaseUrl
            . '/rest/v1/training_enrollments?select=id,enrollment_status,program_id,person_id'
            . '&id=eq.' . rawurlencode($enrollmentId)
            . '&limit=1',
        $headers
    );

    if (!isSuccessful($enrollmentResponse) || empty((array)($enrollmentResponse['data'] ?? []))) {
        redirectWithState('error', 'Training record not found.', 'learning-development.php');
    }

    $enrollment = (array)$enrollmentResponse['data'][0];
    $programId = cleanText($enrollment['program_id'] ?? null) ?? '';
    $enrollmentPersonId = cleanText($enrollment['person_id'] ?? null) ?? '';
    if (!$isAdminScope && isValidUuid($enrollmentPersonId)) {
        $employmentScopeResponse = apiRequest(
            'GET',
            $supabaseUrl
                . '/rest/v1/employment_records?select=id'
                . '&person_id=eq.' . rawurlencode($enrollmentPersonId)
                . '&is_current=eq.true'
                . $officeScopeFilter
                . '&limit=1',
            $headers
        );

        if (!isSuccessful($employmentScopeResponse) || empty((array)($employmentScopeResponse['data'] ?? []))) {
            redirectWithState('error', 'Training record is outside your division scope.', 'learning-development.php');
        }
    } elseif (!$isAdminScope) {
        redirectWithState('error', 'Training record is outside your division scope.', 'learning-development.php');
    }

    $oldStatusDb = strtolower((string)(cleanText($enrollment['enrollment_status'] ?? null) ?? 'enrolled'));
    $oldAttendance = $trainingAttendanceFromDb($oldStatusDb);

    $allowedAttendanceTransitions = [
        'enrolled' => ['enrolled', 'present', 'absent', 'dropped'],
        'present' => ['present', 'absent', 'dropped'],
        'absent' => ['absent', 'present', 'dropped'],
        'dropped' => ['dropped'],
    ];
    $allowedNext = $allowedAttendanceTransitions[$oldAttendance] ?? [];
    if (!in_array($attendanceStatus, $allowedNext, true)) {
        redirectWithState('error', 'Invalid attendance status transition.', 'learning-development.php');
    }

    $newStatusDb = $trainingAttendanceToDb($attendanceStatus);

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/training_enrollments?id=eq.' . rawurlencode($enrollmentId),
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'enrollment_status' => $newStatusDb,
        ]]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Unable to update attendance status.', 'learning-development.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'learning_development',
            'entity_name' => 'learning_enrollments',
            'entity_id' => $enrollmentId,
            'action_name' => 'update_training_attendance',
            'old_data' => ['attendance_status' => $oldAttendance],
            'new_data' => ['attendance_status' => $attendanceStatus, 'attendance_note' => $attendanceNote, 'program_id' => $programId],
            'ip_address' => clientIp(),
        ]]
    );

    if (isValidUuid($enrollmentPersonId)) {
        $personResponse = apiRequest(
            'GET',
            $supabaseUrl
                . '/rest/v1/people?select=id,user_id,first_name,surname'
                . '&id=eq.' . rawurlencode($enrollmentPersonId)
                . '&limit=1',
            $headers
        );

        $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
        $recipientUserId = cleanText($personRow['user_id'] ?? null) ?? '';
        if (isValidUuid($recipientUserId) && strcasecmp($recipientUserId, (string)$staffUserId) !== 0) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'recipient_user_id' => $recipientUserId,
                    'category' => 'learning_development',
                    'title' => 'Training Attendance Updated',
                    'body' => 'Your attendance status has been updated to ' . ucfirst($attendanceStatus) . '.',
                    'link_url' => '/hris-system/pages/employee/learning-and-development.php',
                ]]
            );
        }
    }

    redirectWithState('success', 'Attendance status updated successfully.', 'learning-development.php');
}

if ($formAction === 'review_learning_enrollment') {
    if (!$isManager) {
        redirectWithState('error', 'Only HR officers and supervisors can review enrollments.', 'learning-development.php');
    }

    $enrollmentId = cleanText($_POST['enrollment_id'] ?? null) ?? '';
    $newStatus = strtolower(cleanText($_POST['new_status'] ?? null) ?? '');
    $reviewNote = cleanText($_POST['review_note'] ?? null) ?? '';

    if (!isValidUuid($enrollmentId) || $newStatus === '') {
        redirectWithState('error', 'Invalid enrollment review request.', 'learning-development.php');
    }

    $enrollmentResponse = apiRequest(
        'GET',
        $supabaseUrl
            . '/rest/v1/training_enrollments?select=id,enrollment_status,program_id,person_id'
            . '&id=eq.' . rawurlencode($enrollmentId)
            . '&limit=1',
        $headers
    );

    if (!isSuccessful($enrollmentResponse) || empty((array)($enrollmentResponse['data'] ?? []))) {
        redirectWithState('error', 'Enrollment record not found.', 'learning-development.php');
    }

    $enrollment = (array)$enrollmentResponse['data'][0];
    $programId = cleanText($enrollment['program_id'] ?? null) ?? '';
    $enrollmentPersonId = cleanText($enrollment['person_id'] ?? null) ?? '';
    if (!$isAdminScope && isValidUuid($enrollmentPersonId)) {
        $employmentScopeResponse = apiRequest(
            'GET',
            $supabaseUrl
                . '/rest/v1/employment_records?select=id'
                . '&person_id=eq.' . rawurlencode($enrollmentPersonId)
                . '&is_current=eq.true'
                . $officeScopeFilter
                . '&limit=1',
            $headers
        );

        if (!isSuccessful($employmentScopeResponse) || empty((array)($employmentScopeResponse['data'] ?? []))) {
            redirectWithState('error', 'Enrollment is outside your division scope.', 'learning-development.php');
        }
    } elseif (!$isAdminScope) {
        redirectWithState('error', 'Enrollment is outside your division scope.', 'learning-development.php');
    }

    $oldStatusDb = strtolower((string)(cleanText($enrollment['enrollment_status'] ?? null) ?? 'enrolled'));
    $oldStatus = $enrollmentDbToVirtual($oldStatusDb);
    if (!canTransitionStatus('learning_enrollments', $oldStatus, $newStatus)) {
        redirectWithState('error', 'Invalid enrollment status transition.', 'learning-development.php');
    }

    $newStatusDb = $enrollmentVirtualToDb($newStatus);

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/training_enrollments?id=eq.' . rawurlencode($enrollmentId),
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'enrollment_status' => $newStatusDb,
        ]]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Unable to update enrollment status.', 'learning-development.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => isValidUuid((string)$staffUserId) ? $staffUserId : null,
            'module_name' => 'learning_development',
            'entity_name' => 'learning_enrollments',
            'entity_id' => $enrollmentId,
            'action_name' => 'review_learning_enrollment',
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $newStatus, 'review_note' => $reviewNote, 'program_id' => $programId],
            'ip_address' => clientIp(),
        ]]
    );

    if (isValidUuid($enrollmentPersonId)) {
        $personResponse = apiRequest(
            'GET',
            $supabaseUrl
                . '/rest/v1/people?select=id,user_id,first_name,surname'
                . '&id=eq.' . rawurlencode($enrollmentPersonId)
                . '&limit=1',
            $headers
        );

        $personRow = isSuccessful($personResponse) ? ($personResponse['data'][0] ?? null) : null;
        $recipientUserId = cleanText($personRow['user_id'] ?? null) ?? '';
        $employeeName = is_array($personRow)
            ? trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''))
            : 'Employee';
        if ($employeeName === '') {
            $employeeName = 'Employee';
        }

        if (isValidUuid($recipientUserId) && strcasecmp($recipientUserId, (string)$staffUserId) !== 0) {
            $decisionLabel = $newStatus === 'approved' ? 'approved' : 'rejected';
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/notifications',
                array_merge($headers, ['Prefer: return=minimal']),
                [[
                    'recipient_user_id' => $recipientUserId,
                    'category' => 'learning_development',
                    'title' => 'Training Enrollment Decision',
                    'body' => $employeeName . ', your training enrollment was ' . $decisionLabel . '.',
                    'link_url' => '/hris-system/pages/employee/learning-and-development.php',
                ]]
            );
        }
    }

    redirectWithState('success', 'Enrollment decision saved successfully.', 'learning-development.php');
}
