<?php

$announcementLogsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at,actor_user_id,actor:user_accounts(email)&module_name=eq.create_announcement&entity_name=eq.announcements&action_name=eq.publish_announcement&order=created_at.desc&limit=100',
    $headers
);

$announcementLogs = isSuccessful($announcementLogsResponse) ? (array)($announcementLogsResponse['data'] ?? []) : [];

$dataLoadError = null;
if (!isSuccessful($announcementLogsResponse)) {
    $dataLoadError = 'Announcement logs query failed (HTTP ' . (int)($announcementLogsResponse['status'] ?? 0) . ').';
    $raw = trim((string)($announcementLogsResponse['raw'] ?? ''));
    if ($raw !== '') {
        $dataLoadError .= ' ' . $raw;
    }
}

$announcementRows = [];
$totalPublished = 0;
$totalInAppSent = 0;
$totalEmailSent = 0;

foreach ($announcementLogs as $log) {
    $payload = (array)($log['new_data'] ?? []);
    $delivery = (array)($payload['delivery_summary'] ?? []);

    $inAppSent = (int)($delivery['in_app_sent'] ?? 0);
    $emailSent = (int)($delivery['email_sent'] ?? 0);
    $targetedUsers = (int)($delivery['targeted_users'] ?? 0);

    $totalPublished++;
    $totalInAppSent += $inAppSent;
    $totalEmailSent += $emailSent;

    $createdAt = (string)($log['created_at'] ?? '');
    $createdAtLabel = $createdAt !== '' ? date('M d, Y h:i A', strtotime($createdAt)) : '-';

    $announcementRows[] = [
        'title' => (string)($payload['title'] ?? 'Untitled Announcement'),
        'category' => ucfirst((string)($payload['category'] ?? 'announcement')),
        'audience' => ucfirst(str_replace('_', ' ', (string)($delivery['audience'] ?? 'all_users'))),
        'channel' => strtoupper((string)($delivery['channel'] ?? 'both')),
        'targeted_users' => $targetedUsers,
        'in_app_sent' => $inAppSent,
        'email_sent' => $emailSent,
        'created_at' => $createdAtLabel,
        'actor_email' => (string)($log['actor']['email'] ?? '-'),
    ];
}
