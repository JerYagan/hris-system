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

if (!function_exists('systemNormalizeResourceLink')) {
    function systemNormalizeResourceLink(?string $value): ?string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw) === 1) {
            return $raw;
        }

        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        return systemAppPath($raw);
    }
}

if (!function_exists('systemSettingLinksMap')) {
    function systemSettingLinksMap(string $supabaseUrl, array $headers, array $settingKeys): array
    {
        $result = [];
        $normalizedKeys = [];

        foreach ($settingKeys as $settingKey) {
            $key = trim((string)$settingKey);
            if ($key === '') {
                continue;
            }

            $normalizedKeys[$key] = true;
            $result[$key] = null;
        }

        if ($supabaseUrl === '' || empty($headers) || $normalizedKeys === []) {
            return $result;
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/system_settings?select=setting_key,setting_value'
            . '&setting_key=in.' . rawurlencode('(' . implode(',', array_keys($normalizedKeys)) . ')')
            . '&limit=' . count($normalizedKeys),
            $headers
        );

        if (!isSuccessful($response)) {
            return $result;
        }

        foreach ((array)($response['data'] ?? []) as $rowRaw) {
            $row = (array)$rowRaw;
            $key = trim((string)($row['setting_key'] ?? ''));
            if ($key === '' || !array_key_exists($key, $result)) {
                continue;
            }

            $settingValue = $row['setting_value'] ?? null;
            $resolvedValue = null;

            if (is_array($settingValue)) {
                foreach (['url', 'link', 'path', 'value'] as $candidateKey) {
                    $candidateValue = trim((string)($settingValue[$candidateKey] ?? ''));
                    if ($candidateValue !== '') {
                        $resolvedValue = $candidateValue;
                        break;
                    }
                }
            } else {
                $candidateValue = trim((string)$settingValue);
                if ($candidateValue !== '') {
                    $resolvedValue = $candidateValue;
                }
            }

            $result[$key] = systemNormalizeResourceLink($resolvedValue);
        }

        return $result;
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

if (!function_exists('formatUnixTimestampForPhilippines')) {
    function formatUnixTimestampForPhilippines(int|string|null $timestamp, string $format = 'M j, Y g:i A'): string
    {
        if ($timestamp === null || $timestamp === '' || !is_numeric($timestamp)) {
            return '-';
        }

        $integerTimestamp = (int)$timestamp;
        if ($integerTimestamp <= 0) {
            return '-';
        }

        return formatDateTimeForPhilippines(gmdate('c', $integerTimestamp), $format);
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

if (!function_exists('timekeepingRequestTypeCatalog')) {
    function timekeepingRequestTypeCatalog(): array
    {
        return [
            'official_business' => [
                'label' => 'Official Business',
                'tag' => '[OB]',
                'category' => 'official_business',
                'requires_cos' => false,
                'requires_attachment' => false,
                'max_end_time' => null,
            ],
            'cos_schedule' => [
                'label' => 'COS Flexible Schedule',
                'tag' => '[COS]',
                'category' => 'schedule',
                'requires_cos' => true,
                'requires_attachment' => false,
                'max_end_time' => '22:00',
            ],
            'travel_order' => [
                'label' => 'Travel Order',
                'tag' => '[TO]',
                'category' => 'travel',
                'requires_cos' => false,
                'requires_attachment' => true,
                'max_end_time' => null,
            ],
            'travel_abroad' => [
                'label' => 'Travel Abroad',
                'tag' => '[TA]',
                'category' => 'travel',
                'requires_cos' => false,
                'requires_attachment' => true,
                'max_end_time' => null,
            ],
        ];
    }
}

if (!function_exists('timekeepingRequestTypeMeta')) {
    function timekeepingRequestTypeMeta(?string $requestType): array
    {
        $key = strtolower(trim((string)$requestType));
        $catalog = timekeepingRequestTypeCatalog();

        return $catalog[$key] ?? [
            'label' => 'Special Request',
            'tag' => '[REQ]',
            'category' => 'other',
            'requires_cos' => false,
            'requires_attachment' => false,
            'max_end_time' => null,
        ];
    }
}

if (!function_exists('timekeepingBuildTaggedReason')) {
    function timekeepingBuildTaggedReason(string $requestType, string $reason): string
    {
        $meta = timekeepingRequestTypeMeta($requestType);
        $tag = trim((string)($meta['tag'] ?? '[REQ]'));
        $cleanReason = trim($reason);

        return $tag . ($cleanReason !== '' ? ' ' . $cleanReason : '');
    }
}

if (!function_exists('timekeepingParseTaggedReason')) {
    function timekeepingParseTaggedReason(?string $reason): array
    {
        $rawReason = trim((string)$reason);
        $catalog = timekeepingRequestTypeCatalog();

        foreach ($catalog as $requestType => $meta) {
            $tag = preg_quote((string)($meta['tag'] ?? ''), '/');
            if ($tag === '') {
                continue;
            }

            if (preg_match('/^' . $tag . '\s*/i', $rawReason) === 1) {
                return [
                    'request_type' => $requestType,
                    'label' => (string)($meta['label'] ?? 'Special Request'),
                    'category' => (string)($meta['category'] ?? 'other'),
                    'tag' => (string)($meta['tag'] ?? ''),
                    'is_special' => true,
                    'clean_reason' => trim((string)preg_replace('/^' . $tag . '\s*/i', '', $rawReason)),
                ];
            }
        }

        return [
            'request_type' => 'legacy_cto',
            'label' => 'CTO (Legacy)',
            'category' => 'cto',
            'tag' => '',
            'is_special' => false,
            'clean_reason' => $rawReason,
        ];
    }
}

if (!function_exists('timekeepingIsCosEmploymentStatus')) {
    function timekeepingIsCosEmploymentStatus(?string $employmentStatus): bool
    {
        $normalized = strtolower(trim((string)$employmentStatus));
        if ($normalized === '') {
            return false;
        }

        foreach (['contract of service', 'cos', 'contractual', 'job order', 'job_order', 'casual'] as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }
}
