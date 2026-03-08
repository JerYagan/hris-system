<?php

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}

if (!function_exists('systemEnvValue')) {
    function systemEnvValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (!function_exists('systemNormalizeBasePath')) {
    function systemNormalizeBasePath(?string $path): string
    {
        $value = trim((string)$path);
        if ($value === '' || $value === '/') {
            return '';
        }

        $normalized = '/' . trim(str_replace('\\', '/', $value), '/');
        return $normalized === '/' ? '' : $normalized;
    }
}

if (!function_exists('systemAppBasePath')) {
    function systemAppBasePath(): string
    {
        static $cachedBasePath = null;
        if ($cachedBasePath !== null) {
            return $cachedBasePath;
        }

        $configuredUrl = systemEnvValue('APP_BASE_URL');
        if ($configuredUrl !== null) {
            $parsedPath = parse_url($configuredUrl, PHP_URL_PATH);
            if (is_string($parsedPath)) {
                return $cachedBasePath = systemNormalizeBasePath($parsedPath);
            }
        }

        $candidates = [
            (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH),
            (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            (string)($_SERVER['PHP_SELF'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $normalizedCandidate = str_replace('\\', '/', trim($candidate));
            if ($normalizedCandidate === '') {
                continue;
            }

            foreach (['/pages/', '/api/', '/storage/', '/assets/'] as $marker) {
                $markerPosition = strpos($normalizedCandidate, $marker);
                if ($markerPosition === false) {
                    continue;
                }

                return $cachedBasePath = systemNormalizeBasePath(substr($normalizedCandidate, 0, $markerPosition));
            }
        }

        return $cachedBasePath = '/hris-system';
    }
}

if (!function_exists('systemAppPath')) {
    function systemAppPath(string $path = ''): string
    {
        $basePath = systemAppBasePath();
        $suffix = '/' . ltrim($path, '/');
        if ($suffix === '/') {
            $suffix = '';
        }

        return ($basePath === '' ? '' : $basePath) . $suffix;
    }
}

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
