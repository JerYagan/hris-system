<?php

if (!function_exists('fetchPublishedAnnouncementLogs')) {
    function fetchPublishedAnnouncementLogs(string $supabaseUrl, array $headers, int $limit = 100): array
    {
        $safeLimit = max(1, min($limit, 500));

        return apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/activity_logs?select=id,action_name,new_data,created_at,actor_user_id,actor:user_accounts(email)&module_name=eq.create_announcement&entity_name=eq.announcements&action_name=eq.publish_announcement&order=created_at.desc&limit=' . $safeLimit,
            $headers
        );
    }
}

if (!function_exists('buildPublishedAnnouncementMetrics')) {
    function buildPublishedAnnouncementMetrics(array $announcementLogs): array
    {
        $rows = [];
        $totalPublished = 0;
        $totalInAppSent = 0;
        $totalEmailSent = 0;
        $latestTitle = 'No published announcements yet';
        $latestTimestamp = 'Use Create Announcement to publish the first broadcast.';
        $latestBody = '';
        $latestTargetedUsers = 0;
        $latestChannel = 'Not yet published';

        $audienceLabels = [
            'all_users' => 'All Active Users',
            'admins' => 'Admins / HR / Supervisors',
            'staff' => 'Staff',
            'employees' => 'Employees',
            'applicants' => 'Applicants',
        ];

        $channelLabels = [
            'both' => 'In-App + Email',
            'in_app' => 'In-App Only',
            'email' => 'Email Only',
        ];

        $formatAnnouncementTimestamp = static function (string $value): string {
            if ($value === '') {
                return '-';
            }

            if (function_exists('formatDateTimeForPhilippines')) {
                return formatDateTimeForPhilippines($value, 'M d, Y h:i A') . ' PST';
            }

            return date('M d, Y h:i A', strtotime($value));
        };

        foreach ($announcementLogs as $index => $log) {
            $payload = (array)($log['new_data'] ?? []);
            $delivery = (array)($payload['delivery_summary'] ?? []);

            $inAppSent = (int)($delivery['in_app_sent'] ?? 0);
            $emailSent = (int)($delivery['email_sent'] ?? 0);
            $targetedUsers = (int)($delivery['targeted_users'] ?? 0);
            $channelKey = strtolower(trim((string)($delivery['channel'] ?? 'both')));
            $audienceKey = strtolower(trim((string)($delivery['audience'] ?? 'all_users')));
            $createdAtRaw = (string)($log['created_at'] ?? '');

            $totalPublished++;
            $totalInAppSent += $inAppSent;
            $totalEmailSent += $emailSent;

            $createdAtLabel = $formatAnnouncementTimestamp($createdAtRaw);
            $channelLabel = $channelLabels[$channelKey] ?? ucwords(str_replace('_', ' ', $channelKey));
            $audienceLabel = $audienceLabels[$audienceKey] ?? ucwords(str_replace('_', ' ', $audienceKey));

            if ($index === 0) {
                $latestTitle = (string)($payload['title'] ?? $latestTitle);
                $latestBody = (string)($payload['body'] ?? '');
                $latestTargetedUsers = $targetedUsers;
                $latestChannel = $channelLabel;
                if ($createdAtRaw !== '') {
                    $latestTimestamp = 'Published ' . $formatAnnouncementTimestamp($createdAtRaw);
                }
            }

            $rows[] = [
                'title' => (string)($payload['title'] ?? 'Untitled Announcement'),
                'category' => ucfirst((string)($payload['category'] ?? 'announcement')),
                'audience' => $audienceLabel,
                'channel' => $channelLabel,
                'targeted_users' => $targetedUsers,
                'in_app_sent' => $inAppSent,
                'email_sent' => $emailSent,
                'created_at' => $createdAtLabel,
                'actor_email' => (string)($log['actor']['email'] ?? '-'),
            ];
        }

        return [
            'rows' => $rows,
            'total_published' => $totalPublished,
            'total_in_app_sent' => $totalInAppSent,
            'total_email_sent' => $totalEmailSent,
            'latest_title' => $latestTitle,
            'latest_timestamp' => $latestTimestamp,
            'latest_body' => $latestBody,
            'latest_targeted_users' => $latestTargetedUsers,
            'latest_channel' => $latestChannel,
        ];
    }
}