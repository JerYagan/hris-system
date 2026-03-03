<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'support.php?tab=contact');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'support.php?tab=contact');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'support.php?tab=contact');
}

$action = cleanText($_POST['action'] ?? null) ?? 'submit_support';
if ($action !== 'submit_support_ticket') {
    redirectWithState('error', 'Unsupported support action.', 'support.php?tab=contact');
}

$buildUuidV4 = static function (): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
};

$subject = cleanText($_POST['subject'] ?? null);
$supportMessage = cleanText($_POST['message'] ?? null);
$category = strtolower((string)(cleanText($_POST['inquiry_category'] ?? null) ?? 'general'));

$allowedCategories = ['general', 'application_status', 'documents', 'technical', 'other'];
if (!in_array($category, $allowedCategories, true)) {
    redirectWithState('error', 'Please choose a valid category.', 'support.php?tab=contact');
}

if ($subject === null || mb_strlen($subject) < 5) {
    redirectWithState('error', 'Please provide a subject with at least 5 characters.', 'support.php?tab=contact');
}

if (mb_strlen($subject) > 150) {
    redirectWithState('error', 'Subject is too long. Please keep it under 150 characters.', 'support.php?tab=contact');
}

if ($supportMessage === null || mb_strlen($supportMessage) < 10) {
    redirectWithState('error', 'Please provide a message with at least 10 characters.', 'support.php?tab=contact');
}

if (mb_strlen($supportMessage) > 3000) {
    redirectWithState('error', 'Message is too long. Please keep it under 3000 characters.', 'support.php?tab=contact');
}

$ticketId = $buildUuidV4();
$attachmentRelativePath = null;
$attachmentOriginalName = null;
$attachmentMime = null;
$attachmentSize = null;

$attachmentFile = $_FILES['support_attachment'] ?? null;
if (is_array($attachmentFile) && ((int)($attachmentFile['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
    $attachmentTmpPath = (string)($attachmentFile['tmp_name'] ?? '');
    $attachmentOriginalName = trim((string)($attachmentFile['name'] ?? ''));
    $attachmentSize = (int)($attachmentFile['size'] ?? 0);

    if ($attachmentTmpPath === '' || !is_uploaded_file($attachmentTmpPath) || $attachmentOriginalName === '' || $attachmentSize <= 0) {
        redirectWithState('error', 'Uploaded attachment is invalid. Please try again.', 'support.php?tab=contact');
    }

    $maxAttachmentBytes = 5 * 1024 * 1024;
    if ($attachmentSize > $maxAttachmentBytes) {
        redirectWithState('error', 'Attachment exceeds the 5MB limit.', 'support.php?tab=contact');
    }

    $attachmentExt = strtolower(pathinfo($attachmentOriginalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($attachmentExt, $allowedExtensions, true)) {
        redirectWithState('error', 'Attachment file type is not allowed.', 'support.php?tab=contact');
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
        redirectWithState('error', 'Attachment content type is not allowed.', 'support.php?tab=contact');
    }

    $storageRoot = dirname(__DIR__, 4) . '/storage/support/applicant';
    if (!is_dir($storageRoot) && !mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
        redirectWithState('error', 'Unable to prepare support attachment storage.', 'support.php?tab=contact');
    }

    $storedFilename = sprintf('%s_%s_%s.%s', $ticketId, date('YmdHis'), bin2hex(random_bytes(3)), $attachmentExt);
    $storedAbsolutePath = $storageRoot . '/' . $storedFilename;
    if (!move_uploaded_file($attachmentTmpPath, $storedAbsolutePath)) {
        redirectWithState('error', 'Unable to store support attachment.', 'support.php?tab=contact');
    }

    $attachmentRelativePath = 'storage/support/applicant/' . $storedFilename;
}

$supportLogResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $applicantUserId,
        'module_name' => 'support',
        'entity_name' => 'tickets',
        'entity_id' => $ticketId,
        'action_name' => 'submit_ticket',
        'old_data' => null,
        'new_data' => [
            'ticket_id' => $ticketId,
            'requester_user_id' => $applicantUserId,
            'requester_role' => 'applicant',
            'category' => $category,
            'subject' => $subject,
            'message' => $supportMessage,
            'status' => 'submitted',
            'source_page' => 'support_contact',
            'attachment_name' => $attachmentOriginalName,
            'attachment_path' => $attachmentRelativePath,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

if (!isSuccessful($supportLogResponse)) {
    redirectWithState('error', 'Failed to submit your support request. Please try again.', 'support.php?tab=contact');
}

$roleIds = [];
$rolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=id,role_key&role_key=in.(admin)&limit=50',
    $headers
);

if (isSuccessful($rolesResponse)) {
    foreach ((array)($rolesResponse['data'] ?? []) as $roleRow) {
        $roleId = cleanText($roleRow['id'] ?? null);
        if ($roleId !== null) {
            $roleIds[] = $roleId;
        }
    }
}

$hrUserIds = [];
if (!empty($roleIds)) {
    $assignmentsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/user_role_assignments?select=user_id&role_id=in.' . rawurlencode('(' . implode(',', $roleIds) . ')')
        . '&expires_at=is.null&limit=500',
        $headers
    );

    if (isSuccessful($assignmentsResponse)) {
        foreach ((array)($assignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null);
            if ($userId !== null && $userId !== $applicantUserId) {
                $hrUserIds[$userId] = true;
            }
        }
    }
}

if (!empty($hrUserIds)) {
    $notificationRows = [];
    foreach (array_keys($hrUserIds) as $recipientUserId) {
        $notificationRows[] = [
            'recipient_user_id' => $recipientUserId,
            'category' => 'support',
            'title' => 'New applicant support inquiry',
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

redirectWithState('success', 'Your message has been sent to HR support.', 'support.php?tab=contact');
