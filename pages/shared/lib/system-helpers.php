<?php

if (!function_exists('formatDateTimeForPhilippines')) {
    function formatDateTimeForPhilippines(?string $dateTime, string $format = 'M j, Y g:i A'): string
    {
        $value = is_string($dateTime) ? trim($dateTime) : '';
        if ($value === '') {
            return '-';
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Throwable) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return '-';
            }
            $date = (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'));
        }

        return $date
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format($format);
    }
}

if (!function_exists('formatNotificationCategoryLabel')) {
    function formatNotificationCategoryLabel(?string $category): string
    {
        $raw = is_string($category) ? trim($category) : '';
        if ($raw === '') {
            return 'General';
        }

        $key = strtolower($raw);
        $normalized = str_replace(['-', '_'], ' ', $key);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if (str_contains($normalized, 'learning') && str_contains($normalized, 'development')) {
            return 'Learning and Development';
        }
        if (str_contains($normalized, 'system')) {
            return 'System Alert';
        }
        if (str_contains($normalized, 'hr')) {
            return 'HR Announcement';
        }
        if (str_contains($normalized, 'application')) {
            return 'Application Update';
        }

        return ucwords($normalized !== '' ? $normalized : 'general');
    }
}
