<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

if (!function_exists('announcementIsValidUuid')) {
    function announcementIsValidUuid(string $value): bool
    {
        return (bool)preg_match('/^[a-f0-9-]{36}$/i', $value);
    }
}

$action = (string)($_POST['form_action'] ?? '');
if ($action === '') {
    return;
}

if ($action !== 'publish_announcement') {
    redirectWithState('error', 'Unknown create announcement action.');
}

$title = trim((string)(cleanText($_POST['announcement_title'] ?? null) ?? ''));
$body = trim((string)(cleanText($_POST['announcement_body'] ?? null) ?? ''));
$category = strtolower(trim((string)(cleanText($_POST['announcement_category'] ?? null) ?? 'system')));
$audience = strtolower(trim((string)(cleanText($_POST['audience'] ?? null) ?? 'all_users')));
$channel = strtolower(trim((string)(cleanText($_POST['delivery_channel'] ?? null) ?? 'both')));
$linkUrl = trim((string)(cleanText($_POST['link_url'] ?? null) ?? ''));

if ($title === '' || $body === '') {
    redirectWithState('error', 'Announcement title and body are required.');
}

$allowedCategories = ['system', 'hr', 'recruitment', 'payroll', 'announcement'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'announcement';
}

$allowedAudiences = ['all_users', 'admins', 'staff', 'employees', 'applicants'];
if (!in_array($audience, $allowedAudiences, true)) {
    redirectWithState('error', 'Invalid audience selected.');
}

$allowedChannels = ['in_app', 'email', 'both'];
if (!in_array($channel, $allowedChannels, true)) {
    redirectWithState('error', 'Invalid delivery channel selected.');
}

if ($linkUrl !== '' && !preg_match('#^(https?://|/)#i', $linkUrl)) {
    redirectWithState('error', 'Link URL must start with /, http://, or https://');
}

$accountsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status&account_status=in.(active,pending)&limit=10000',
    $headers
);

if (!isSuccessful($accountsResponse)) {
    redirectWithState('error', 'Failed to load user accounts for announcement delivery.');
}

$roleAssignmentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_key)&is_primary=eq.true&expires_at=is.null&limit=10000',
    $headers
);

$accounts = (array)($accountsResponse['data'] ?? []);
$roleAssignments = isSuccessful($roleAssignmentsResponse) ? (array)($roleAssignmentsResponse['data'] ?? []) : [];

$roleByUserId = [];
foreach ($roleAssignments as $assignment) {
    $userId = strtolower(trim((string)($assignment['user_id'] ?? '')));
    if ($userId === '') {
        continue;
    }
    $roleKey = strtolower(trim((string)($assignment['role']['role_key'] ?? '')));
    if ($roleKey !== '') {
        $roleByUserId[$userId] = $roleKey;
    }
}

$targetUsers = [];
foreach ($accounts as $account) {
    $userId = strtolower(trim((string)($account['id'] ?? '')));
    if (!announcementIsValidUuid($userId)) {
        continue;
    }

    $roleKey = (string)($roleByUserId[$userId] ?? '');
    $matchesAudience = false;
    if ($audience === 'all_users') {
        $matchesAudience = true;
    } elseif ($audience === 'admins') {
        $matchesAudience = $roleKey === 'admin' || $roleKey === 'hr_officer' || $roleKey === 'supervisor';
    } elseif ($audience === 'staff') {
        $matchesAudience = $roleKey === 'staff';
    } elseif ($audience === 'employees') {
        $matchesAudience = $roleKey === 'employee';
    } elseif ($audience === 'applicants') {
        $matchesAudience = $roleKey === 'applicant';
    }

    if (!$matchesAudience) {
        continue;
    }

    $targetUsers[$userId] = [
        'user_id' => $userId,
        'email' => strtolower(trim((string)($account['email'] ?? ''))),
        'role_key' => $roleKey,
    ];
}

$targets = array_values($targetUsers);
if (empty($targets)) {
    redirectWithState('error', 'No users matched the selected audience.');
}

$sendInApp = $channel === 'in_app' || $channel === 'both';
$sendEmail = $channel === 'email' || $channel === 'both';

$inAppSent = 0;
$inAppFailed = 0;
if ($sendInApp) {
    $notificationRows = [];
    foreach ($targets as $target) {
        $notificationRows[] = [
            'recipient_user_id' => (string)$target['user_id'],
            'category' => $category,
            'title' => $title,
            'body' => $body,
            'link_url' => $linkUrl !== '' ? $linkUrl : null,
        ];
    }

    $notificationResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/notifications',
        array_merge($headers, ['Prefer: return=minimal']),
        $notificationRows
    );

    if (isSuccessful($notificationResponse)) {
        $inAppSent = count($notificationRows);
    } else {
        $inAppFailed = count($notificationRows);
    }
}

$emailSent = 0;
$emailFailed = 0;
$emailSkipped = 0;
$emailErrorSamples = [];

$emailChannelConfigured = smtpConfigIsReady($smtpConfig, $mailFrom);
if ($sendEmail) {
    if ($emailChannelConfigured) {
        foreach ($targets as $target) {
            $toEmail = strtolower(trim((string)($target['email'] ?? '')));
            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $emailSkipped++;
                continue;
            }

            $htmlContent = '<p>Hello,</p>'
                . '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</p>'
                . ($linkUrl !== '' ? '<p><a href="' . htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') . '">View details</a></p>' : '')
                . '<p>— DA-ATI HRIS</p>';

            $emailResponse = smtpSendTransactionalEmail(
                $smtpConfig,
                $mailFrom,
                $mailFromName,
                $toEmail,
                $toEmail,
                $title,
                $htmlContent
            );

            if (isSuccessful($emailResponse)) {
                $emailSent++;
            } else {
                $emailFailed++;
                if (count($emailErrorSamples) < 5) {
                    $emailErrorSamples[] = [
                        'email' => $toEmail,
                        'error' => trim((string)($emailResponse['raw'] ?? 'SMTP delivery failed')),
                    ];
                }
            }
        }
    } else {
        $emailSkipped = count($targets);
    }
}

$deliverySummary = [
    'audience' => $audience,
    'channel' => $channel,
    'targeted_users' => count($targets),
    'in_app_sent' => $inAppSent,
    'in_app_failed' => $inAppFailed,
    'email_sent' => $emailSent,
    'email_failed' => $emailFailed,
    'email_skipped' => $emailSkipped,
    'smtp_configured' => $emailChannelConfigured,
    'email_error_samples' => $emailErrorSamples,
];

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $adminUserId !== '' ? $adminUserId : null,
        'module_name' => 'create_announcement',
        'entity_name' => 'announcements',
        'entity_id' => null,
        'action_name' => 'publish_announcement',
        'old_data' => null,
        'new_data' => [
            'title' => $title,
            'body' => $body,
            'category' => $category,
            'link_url' => $linkUrl !== '' ? $linkUrl : null,
            'delivery_summary' => $deliverySummary,
        ],
        'ip_address' => clientIp(),
    ]]
);

$messageParts = ['Announcement published.'];
if ($sendInApp) {
    $messageParts[] = 'In-app: ' . $inAppSent . ' sent' . ($inAppFailed > 0 ? (', ' . $inAppFailed . ' failed') : '') . '.';
}
if ($sendEmail) {
    if (!$emailChannelConfigured) {
        $messageParts[] = 'Email: SMTP not configured.';
    } else {
        $messageParts[] = 'Email: ' . $emailSent . ' sent' . ($emailFailed > 0 ? (', ' . $emailFailed . ' failed') : '') . ($emailSkipped > 0 ? (', ' . $emailSkipped . ' skipped') : '') . '.';
    }
}

$hasAnyDelivery = $inAppSent > 0 || $emailSent > 0;
if (!$hasAnyDelivery) {
    redirectWithState('error', implode(' ', $messageParts));
}

redirectWithState('success', implode(' ', $messageParts));
