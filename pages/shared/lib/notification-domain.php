<?php

require_once __DIR__ . '/system-helpers.php';

if (!function_exists('notificationServiceFormatDateTime')) {
    function notificationServiceFormatDateTime(?string $dateTime): string
    {
        $value = trim((string)$dateTime);
        if ($value === '') {
            return '-';
        }

        $formatted = function_exists('formatDateTimeForPhilippines')
            ? formatDateTimeForPhilippines($value, 'M d, Y h:i A')
            : date('M d, Y h:i A', strtotime($value));

        return $formatted !== '-' ? ($formatted . ' PST') : '-';
    }
}

if (!function_exists('notificationServiceLoadSnapshot')) {
    function notificationServiceLoadSnapshot(string $supabaseUrl, array $headers, string $userId, array $options = []): array
    {
        $summary = systemTopnavFetchNotificationSummary($supabaseUrl, $headers, $userId, $options);
        $items = [];

        foreach ((array)($summary['notifications_preview'] ?? []) as $rowRaw) {
            $row = (array)$rowRaw;
            $createdAt = trim((string)($row['created_at'] ?? ''));
            $items[] = [
                'id' => (string)($row['id'] ?? ''),
                'title' => (string)($row['title'] ?? 'Notification'),
                'body' => (string)($row['body'] ?? 'No details available.'),
                'link_url' => (string)($row['link_url'] ?? ''),
                'category' => (string)($row['category'] ?? 'general'),
                'is_read' => (bool)($row['is_read'] ?? false),
                'created_at' => $createdAt,
                'created_at_label' => notificationServiceFormatDateTime($createdAt),
            ];
        }

        return [
            'unread_count' => max(0, (int)($summary['unread_count'] ?? 0)),
            'items' => $items,
        ];
    }
}