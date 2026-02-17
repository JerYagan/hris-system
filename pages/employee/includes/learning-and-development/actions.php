<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'learning-and-development.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'learning-and-development.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if ($action !== 'enroll_training') {
    redirectWithState('error', 'Unsupported learning and development action.', 'learning-and-development.php');
}

$programId = cleanText($_POST['program_id'] ?? null);
if ($programId === null || !isValidUuid($programId)) {
    redirectWithState('error', 'Invalid training program reference.', 'learning-and-development.php');
}

$programResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_programs?select=id,title,start_date,status'
    . '&id=eq.' . rawurlencode($programId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($programResponse) || empty((array)($programResponse['data'] ?? []))) {
    redirectWithState('error', 'Training program not found.', 'learning-and-development.php');
}

$programRow = (array)$programResponse['data'][0];
$programTitle = cleanText($programRow['title'] ?? null) ?? 'Training Program';
$programStartDate = cleanText($programRow['start_date'] ?? null) ?? '';
$programStatus = strtolower((string)($programRow['status'] ?? 'planned'));
$todayDate = date('Y-m-d');

if (!in_array($programStatus, ['planned', 'open', 'ongoing'], true)) {
    redirectWithState('error', 'This training is no longer open for enrollment.', 'learning-and-development.php');
}

if ($programStartDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $programStartDate) === 1 && $programStartDate < $todayDate) {
    redirectWithState('error', 'Enrollment is closed for past training schedules.', 'learning-and-development.php');
}

$existingEnrollmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/training_enrollments?select=id,enrollment_status'
    . '&program_id=eq.' . rawurlencode($programId)
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (isSuccessful($existingEnrollmentResponse) && !empty((array)($existingEnrollmentResponse['data'] ?? []))) {
    redirectWithState('success', 'You are already enrolled in this training.', 'learning-and-development.php');
}

$insertEnrollmentResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/training_enrollments',
    $headers,
    [[
        'program_id' => $programId,
        'person_id' => $employeePersonId,
        'enrollment_status' => 'enrolled',
    ]]
);

if (!isSuccessful($insertEnrollmentResponse)) {
    redirectWithState('error', 'Unable to enroll in the selected training right now.', 'learning-and-development.php');
}

$trainingDateLabel = '-';
if ($programStartDate !== '') {
    $scheduleTs = strtotime($programStartDate);
    if ($scheduleTs !== false) {
        $trainingDateLabel = date('M j, Y', $scheduleTs);
    }
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/notifications',
    $headers,
    [[
        'recipient_user_id' => $employeeUserId,
        'category' => 'learning_and_development',
        'title' => 'Upcoming Training Enrollment',
        'body' => 'You are enrolled in ' . $programTitle . ' scheduled on ' . $trainingDateLabel . '.',
        'link_url' => '/hris-system/pages/employee/learning-and-development.php',
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'learning_and_development',
        'entity_name' => 'training_enrollments',
        'entity_id' => $programId,
        'action_name' => 'enroll_training_program',
        'new_data' => [
            'program_id' => $programId,
            'program_title' => $programTitle,
            'start_date' => $programStartDate,
        ],
    ]]
);

if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['employee_topnav_cache']);
}

redirectWithState('success', 'Training enrollment submitted successfully.', 'learning-and-development.php');
