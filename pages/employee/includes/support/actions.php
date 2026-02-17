<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'support.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'support.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if ($action !== 'submit_support_inquiry') {
    redirectWithState('error', 'Unsupported support action.', 'support.php');
}

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$inquiryCategory = strtolower((string)($toNullable($_POST['inquiry_category'] ?? null, 40) ?? ''));
$subject = $toNullable($_POST['subject'] ?? null, 150);
$messageBody = $toNullable($_POST['message'] ?? null, 3000);
$priority = strtolower((string)($toNullable($_POST['priority'] ?? null, 20) ?? 'normal'));

$allowedCategories = ['general', 'account', 'payroll', 'timekeeping', 'documents', 'technical'];
$allowedPriorities = ['low', 'normal', 'high'];

if (!in_array($inquiryCategory, $allowedCategories, true) || $subject === null || $messageBody === null || !in_array($priority, $allowedPriorities, true)) {
    redirectWithState('error', 'Support inquiry requires valid category, subject, message, and priority.', 'support.php');
}

$logResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'support_inquiries',
        'action_name' => 'submit_support_inquiry',
        'new_data' => [
            'person_id' => $employeePersonId,
            'category' => $inquiryCategory,
            'subject' => $subject,
            'message' => $messageBody,
            'priority' => $priority,
            'status' => 'submitted',
        ],
    ]]
);

if (!isSuccessful($logResponse)) {
    redirectWithState('error', 'Unable to submit support inquiry right now.', 'support.php');
}

redirectWithState('success', 'Support inquiry submitted successfully.', 'support.php');
