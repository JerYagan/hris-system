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

$buildUuidV4 = static function (): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
};

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
$requestType = strtolower((string)($toNullable($_POST['request_type'] ?? null, 60) ?? 'other_profile_change'));

$allowedCategories = [
    'profile_change',
    'technical_issues',
    'account_management',
    'payroll_benefits',
    'training_development',
    'documents_records',
    'timekeeping_attendance',
    'other',
];
$allowedRequestTypes = ['name_change', 'marital_status_change', 'civil_status_update', 'contact_update', 'other_profile_change'];

if (!in_array($inquiryCategory, $allowedCategories, true) || $subject === null || $messageBody === null || !in_array($requestType, $allowedRequestTypes, true)) {
    redirectWithState('error', 'Support request requires valid category, request type, subject, and message.', 'support.php');
}

$attachmentFile = $_FILES['support_attachment'] ?? null;
if (!is_array($attachmentFile) || ((int)($attachmentFile['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_OK) {
    redirectWithState('error', 'Attachment is required for profile change support requests.', 'support.php');
}

$attachmentTmpPath = (string)($attachmentFile['tmp_name'] ?? '');
$attachmentOriginalName = trim((string)($attachmentFile['name'] ?? ''));
$attachmentSize = (int)($attachmentFile['size'] ?? 0);
if ($attachmentTmpPath === '' || !is_uploaded_file($attachmentTmpPath) || $attachmentOriginalName === '' || $attachmentSize <= 0) {
    redirectWithState('error', 'Uploaded attachment is invalid. Please try again.', 'support.php');
}

$maxAttachmentBytes = 5 * 1024 * 1024;
if ($attachmentSize > $maxAttachmentBytes) {
    redirectWithState('error', 'Attachment exceeds the 5MB limit.', 'support.php');
}

$attachmentExt = strtolower(pathinfo($attachmentOriginalName, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
if (!in_array($attachmentExt, $allowedExtensions, true)) {
    redirectWithState('error', 'Attachment file type is not allowed.', 'support.php');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$attachmentMime = $finfo ? (string)finfo_file($finfo, $attachmentTmpPath) : '';
if ($finfo) {
    finfo_close($finfo);
}

$allowedMimes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
if ($attachmentMime !== '' && !in_array($attachmentMime, $allowedMimes, true)) {
    redirectWithState('error', 'Attachment content type is not allowed.', 'support.php');
}

$ticketId = $buildUuidV4();
$storageRoot = dirname(__DIR__, 4) . '/storage/support/employee';
if (!is_dir($storageRoot) && !mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
    redirectWithState('error', 'Unable to prepare support attachment storage.', 'support.php');
}

$safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($attachmentOriginalName, PATHINFO_FILENAME)) ?: 'attachment';
$storedFilename = sprintf('%s_%s_%s.%s', $ticketId, date('YmdHis'), bin2hex(random_bytes(3)), $attachmentExt);
$storedAbsolutePath = $storageRoot . '/' . $storedFilename;
if (!move_uploaded_file($attachmentTmpPath, $storedAbsolutePath)) {
    redirectWithState('error', 'Unable to store support attachment.', 'support.php');
}

$attachmentRelativePath = 'storage/support/employee/' . $storedFilename;

$logResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    $headers,
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'support',
        'entity_name' => 'tickets',
        'entity_id' => $ticketId,
        'action_name' => 'submit_ticket',
        'new_data' => [
            'ticket_id' => $ticketId,
            'person_id' => $employeePersonId,
            'requester_user_id' => $employeeUserId,
            'requester_role' => 'employee',
            'category' => $inquiryCategory,
            'request_type' => $requestType,
            'subject' => $subject,
            'message' => $messageBody,
            'status' => 'submitted',
            'attachment_name' => $attachmentOriginalName,
            'attachment_stored_name' => $safeFilename,
            'attachment_path' => $attachmentRelativePath,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

if (!isSuccessful($logResponse)) {
    redirectWithState('error', 'Unable to submit support inquiry right now.', 'support.php');
}

$adminRoleIds = [];
$adminRolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=id,role_key&role_key=in.(admin)&limit=20',
    $headers
);

if (isSuccessful($adminRolesResponse)) {
    foreach ((array)($adminRolesResponse['data'] ?? []) as $roleRow) {
        $roleId = cleanText($roleRow['id'] ?? null);
        if ($roleId !== null) {
            $adminRoleIds[] = $roleId;
        }
    }
}

$adminUserIds = [];
if (!empty($adminRoleIds)) {
    $assignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id&role_id=in.' . rawurlencode('(' . implode(',', $adminRoleIds) . ')')
        . '&expires_at=is.null&limit=300',
        $headers
    );

    if (isSuccessful($assignmentsResponse)) {
        foreach ((array)($assignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null);
            if ($userId !== null && $userId !== $employeeUserId) {
                $adminUserIds[$userId] = true;
            }
        }
    }
}

if (!empty($adminUserIds)) {
    $notificationRows = [];
    foreach (array_keys($adminUserIds) as $recipientUserId) {
        $notificationRows[] = [
            'recipient_user_id' => $recipientUserId,
            'category' => 'support',
            'title' => 'New employee support ticket',
            'body' => 'Subject: ' . mb_substr($subject, 0, 120),
            'link_url' => '/hris-system/pages/admin/support.php?ticket_id=' . rawurlencode($ticketId),
            'is_read' => false,
        ];
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        $notificationRows
    );
}

redirectWithState('success', 'Support ticket submitted successfully.', 'support.php');
