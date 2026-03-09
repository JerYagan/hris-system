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
if ($action === 'enroll_training') {
    redirectWithState('error', 'Training enrollment is managed by staff. Please contact HR staff for enrollment.', 'learning-and-development.php');
}

redirectWithState('error', 'Unsupported learning and development action.', 'learning-and-development.php');
