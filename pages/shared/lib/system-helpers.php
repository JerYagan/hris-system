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

if (!function_exists('systemLoadEnvFile')) {
    function systemLoadEnvFile(string $envPath): void
    {
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim((string)$value);
            $value = trim($value, "\"'");

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('systemProjectRoot')) {
    function systemProjectRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}

if (!function_exists('systemLoadProjectEnv')) {
    function systemLoadProjectEnv(): void
    {
        systemLoadEnvFile(systemProjectRoot() . '/.env');
    }
}

if (!function_exists('systemPrivilegedSupabaseHeaders')) {
    function systemPrivilegedSupabaseHeaders(?string $serviceRoleKey): array
    {
        $key = trim((string)$serviceRoleKey);
        if ($key === '') {
            return [];
        }

        return [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ];
    }
}

if (!function_exists('systemPrivilegedSupabaseConfig')) {
    function systemPrivilegedSupabaseConfig(): array
    {
        systemLoadProjectEnv();

        $url = rtrim((string)(systemEnvValue('SUPABASE_URL') ?? ''), '/');
        $serviceRoleKey = (string)(systemEnvValue('SUPABASE_SERVICE_ROLE_KEY') ?? '');
        $headers = systemPrivilegedSupabaseHeaders($serviceRoleKey);

        return [
            'url' => $url,
            'service_role_key' => $serviceRoleKey,
            'headers' => $headers,
            'is_configured' => $url !== '' && $serviceRoleKey !== '' && $headers !== [],
        ];
    }
}

if (!function_exists('systemTopnavCacheIsFresh')) {
    function systemTopnavCacheIsFresh(array $cache, string $userId, int $ttlSeconds = 45): bool
    {
        $cacheUserId = trim((string)($cache['user_id'] ?? ''));
        $cacheTimestamp = (int)($cache['cached_at'] ?? 0);

        return $cacheUserId !== ''
            && $userId !== ''
            && $cacheUserId === $userId
            && $cacheTimestamp > 0
            && (time() - $cacheTimestamp) <= max(1, $ttlSeconds);
    }
}

if (!function_exists('systemTopnavResolveProfilePhotoUrl')) {
    function systemTopnavResolveProfilePhotoUrl(?string $rawPath): ?string
    {
        $path = trim((string)$rawPath);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            $appRootPath = parse_url(systemAppPath('/'), PHP_URL_PATH);
            $storagePrefix = rtrim((string)$appRootPath, '/') . '/storage/document/';
            $pathOnly = (string)(parse_url($path, PHP_URL_PATH) ?? '');

            if ($storagePrefix !== '/storage/document/' && str_starts_with($pathOnly, $storagePrefix)) {
                $relativePath = substr($pathOnly, strlen($storagePrefix));
                $resolved = systemResolveLocalStorageReference('document', $relativePath);
                return $resolved !== null ? $path : null;
            }

            if (str_starts_with($pathOnly, '/storage/document/')) {
                $relativePath = substr($pathOnly, strlen('/storage/document/'));
                $resolved = systemResolveLocalStorageReference('document', $relativePath);
                return $resolved !== null ? $path : null;
            }

            return $path;
        }

        $normalized = str_replace('\\', '/', ltrim($path, '/'));
        if (str_starts_with($normalized, 'storage/document/')) {
            $normalized = substr($normalized, strlen('storage/document/'));
        }

        $segments = array_values(array_filter(explode('/', $normalized), static fn(string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return null;
        }

        $resolved = systemResolveLocalStorageReference('document', implode('/', $segments));
        if ($resolved === null) {
            return null;
        }

        return systemAppPath('/storage/document/' . implode('/', array_map('rawurlencode', $segments)));
    }
}

if (!function_exists('systemResolveLocalStorageReference')) {
    function systemResolveLocalStorageReference(string $storageArea, ?string $rawPath): ?array
    {
        $normalizedArea = trim($storageArea, " \/\\");
        $normalizedPath = str_replace('\\', '/', trim((string)$rawPath));
        $normalizedPath = ltrim($normalizedPath, '/');
        if ($normalizedArea === '' || $normalizedPath === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $normalizedPath), static fn(string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..'));
        if ($segments === []) {
            return null;
        }

        $storageRoot = realpath(dirname(__DIR__, 3) . '/storage/' . $normalizedArea);
        if ($storageRoot === false) {
            return null;
        }

        $candidatePath = $storageRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $candidateRealPath = realpath($candidatePath);
        if ($candidateRealPath === false) {
            return null;
        }

        $storageRootNormalized = str_replace('\\', '/', $storageRoot);
        $candidateNormalized = str_replace('\\', '/', $candidateRealPath);
        if (!str_starts_with($candidateNormalized, $storageRootNormalized . '/')) {
            return null;
        }

        return [
            'full_path' => $candidateRealPath,
            'relative_path' => implode('/', $segments),
        ];
    }
}

if (!function_exists('systemTopnavBuildInitials')) {
    function systemTopnavBuildInitials(?string $displayName, string $fallback = 'NA'): string
    {
        $normalizedName = preg_replace('/\s+/', ' ', trim((string)$displayName));
        if ($normalizedName === '' || $normalizedName === null) {
            return strtoupper(substr($fallback, 0, 2));
        }

        $parts = array_values(array_filter(explode(' ', $normalizedName), static fn(string $part): bool => $part !== ''));
        if ($parts === []) {
            return strtoupper(substr($fallback, 0, 2));
        }

        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : strtoupper(substr($fallback, 0, 2));
    }
}

if (!function_exists('systemShellContext')) {
    function systemShellContext(?string $pageTitle, string $defaultTitle, ?string $activePage = '', array $breadcrumbs = [], array $defaultBreadcrumbs = ['Dashboard']): array
    {
        $resolvedTitle = trim((string)$pageTitle) !== '' ? trim((string)$pageTitle) : $defaultTitle;
        $resolvedActivePage = trim((string)$activePage);
        $resolvedBreadcrumbs = $breadcrumbs !== [] ? $breadcrumbs : $defaultBreadcrumbs;

        if ($resolvedBreadcrumbs === []) {
            $resolvedBreadcrumbs = ['Dashboard'];
        }

        $pageSlugSource = $resolvedActivePage !== ''
            ? $resolvedActivePage
            : (string)($resolvedBreadcrumbs[0] ?? 'dashboard');
        $pageSlug = strtolower((string)pathinfo($pageSlugSource, PATHINFO_FILENAME));

        return [
            'page_title' => $resolvedTitle,
            'active_page' => $resolvedActivePage,
            'breadcrumbs' => $resolvedBreadcrumbs,
            'page_slug' => $pageSlug,
        ];
    }
}

if (!function_exists('systemTopnavCachePayload')) {
    function systemTopnavCachePayload(string $userId, string $displayName, ?string $displayRole, ?string $profilePhotoPath, int $unreadCount, array $notificationsPreview, array $extra = []): array
    {
        $payload = [
            'user_id' => trim($userId),
            'display_name' => trim($displayName),
            'profile_photo_url' => trim((string)$profilePhotoPath),
            'unread_count' => max(0, $unreadCount),
            'notifications_preview' => $notificationsPreview,
            'cached_at' => time(),
        ];

        $normalizedDisplayRole = trim((string)$displayRole);
        if ($normalizedDisplayRole !== '') {
            $payload['display_role'] = $normalizedDisplayRole;
        }

        foreach ($extra as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            $payload[$key] = $value;
        }

        return $payload;
    }
}

if (!function_exists('systemSupabaseRestUrl')) {
    function systemSupabaseRestUrl(string $supabaseUrl, string $resource, string $select, array $filters = [], ?string $order = null, ?int $limit = null): string
    {
        $baseUrl = rtrim($supabaseUrl, '/');
        $queryParts = ['select=' . $select];

        foreach ($filters as $filter) {
            $normalizedFilter = trim((string)$filter);
            if ($normalizedFilter === '') {
                continue;
            }

            $queryParts[] = $normalizedFilter;
        }

        if ($order !== null && trim($order) !== '') {
            $queryParts[] = 'order=' . trim($order);
        }

        if ($limit !== null && $limit > 0) {
            $queryParts[] = 'limit=' . $limit;
        }

        return $baseUrl . '/rest/v1/' . ltrim($resource, '/') . '?' . implode('&', $queryParts);
    }
}

if (!function_exists('systemQaPerfEnabled')) {
    function systemQaPerfEnabled(): bool
    {
        $queryValue = strtolower(trim((string)($_GET['qa_perf'] ?? '')));
        if (in_array($queryValue, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        $enabled = strtolower(trim((string)(systemEnvValue('HRIS_QA_PERF_CONSOLE') ?? '0')));
        return in_array($enabled, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('systemQaPerfTrackApiRequest')) {
    function systemQaPerfTrackApiRequest(string $role, string $method, string $url, int $statusCode, int $durationMs, mixed $data, ?string $error = null): void
    {
        if (!systemQaPerfEnabled()) {
            return;
        }

        $query = [];
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $path = (string)parse_url($url, PHP_URL_PATH);
        $normalizedPath = trim($path, '/');
        $segments = $normalizedPath === ''
            ? []
            : array_values(array_filter(explode('/', $normalizedPath), static fn ($segment) => $segment !== ''));

        $resource = $path !== '' ? $path : 'unknown';
        $queryType = match (strtoupper($method)) {
            'GET' => 'select',
            'POST' => 'insert',
            'PATCH', 'PUT' => 'update',
            'DELETE' => 'delete',
            default => strtolower(trim($method)),
        };

        $segmentCount = count($segments);
        if ($segmentCount >= 2 && $segments[$segmentCount - 2] === 'rpc') {
            $resource = 'rpc/' . $segments[$segmentCount - 1];
            $queryType = 'rpc';
        } elseif ($segmentCount > 0) {
            $resource = $segments[$segmentCount - 1];
        }

        $limit = isset($query['limit']) ? (int)$query['limit'] : null;
        $selectRaw = trim((string)($query['select'] ?? ''));
        $selectColumns = $selectRaw === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $selectRaw)), static fn ($column) => $column !== ''));
        $selectCount = count($selectColumns);
        $filterKeys = [];
        foreach (array_keys($query) as $queryKey) {
            if (in_array($queryKey, ['select', 'order', 'limit', 'offset'], true)) {
                continue;
            }

            $filterKeys[] = (string)$queryKey;
        }

        $filterCount = count($filterKeys);
        $shapeParts = [];
        if ($selectCount > 0) {
            $shapeParts[] = 'cols=' . $selectCount;
        }
        if ($filterCount > 0) {
            $shapeParts[] = 'filters=' . $filterCount;
        }
        if (isset($query['order']) && trim((string)$query['order']) !== '') {
            $shapeParts[] = 'order';
        }
        if ($limit !== null && $limit > 0) {
            $shapeParts[] = 'limit=' . $limit;
        }

        $queryShape = trim($queryType . ' ' . $resource);
        if ($shapeParts !== []) {
            $queryShape .= ' [' . implode(', ', $shapeParts) . ']';
        }

        $rows = 0;
        if (is_array($data)) {
            $rows = array_is_list($data)
                ? count($data)
                : (is_array($data['data'] ?? null) ? count((array)$data['data']) : count($data));
        }

        $requests = isset($GLOBALS['__hris_qa_perf_requests']) && is_array($GLOBALS['__hris_qa_perf_requests'])
            ? $GLOBALS['__hris_qa_perf_requests']
            : [];
        $requests[] = [
            'role' => strtolower(trim($role)),
            'method' => strtoupper($method),
            'endpoint' => $path,
            'resource' => $resource,
            'query_type' => $queryType,
            'status' => $statusCode,
            'duration_ms' => $durationMs,
            'rows' => $rows,
            'limit' => $limit,
            'select_count' => $selectCount,
            'filter_count' => $filterCount,
            'query_shape' => $queryShape,
            'error' => $error !== null && $error !== '' ? $error : '',
        ];
        $GLOBALS['__hris_qa_perf_requests'] = $requests;
    }
}

if (!function_exists('systemQaPerfConsolePayload')) {
    function systemQaPerfConsolePayload(): array
    {
        $requests = isset($GLOBALS['__hris_qa_perf_requests']) && is_array($GLOBALS['__hris_qa_perf_requests'])
            ? array_values($GLOBALS['__hris_qa_perf_requests'])
            : [];
        $totalRequestMs = 0;
        $highLimitReads = [];

        foreach ($requests as $request) {
            $totalRequestMs += (int)($request['duration_ms'] ?? 0);
            $limit = isset($request['limit']) ? (int)$request['limit'] : 0;
            if ($limit >= 5000) {
                $highLimitReads[] = $request;
            }
        }

        return [
            'enabled' => systemQaPerfEnabled(),
            'page' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'requests' => $requests,
            'request_count' => count($requests),
            'total_request_ms' => $totalRequestMs,
            'high_limit_reads' => $highLimitReads,
        ];
    }
}

if (!function_exists('systemRenderQaPerfConsoleScript')) {
    function systemRenderQaPerfConsoleScript(): string
    {
        if (!systemQaPerfEnabled()) {
            return '';
        }

        $payload = json_encode(systemQaPerfConsolePayload(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (!is_string($payload) || $payload === '') {
            return '';
        }

        $script = <<<'HTML'
<script>
(function () {
    const server = __PAYLOAD__;
    const qaState = window.__hrisQaPerf = window.__hrisQaPerf || {};
    qaState.page = server.page || location.pathname;
    qaState.sections = Array.isArray(qaState.sections) ? qaState.sections : [];

    const nav = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]) || null;
    const paintEntries = (performance.getEntriesByType && performance.getEntriesByType('paint')) || [];
    const fp = paintEntries.find(function (entry) { return entry.name === 'first-paint'; });
    const fcp = paintEntries.find(function (entry) { return entry.name === 'first-contentful-paint'; });
    let reportTimer = 0;

    const normalizeDuration = function (value) {
        const number = Number(value);
        return Number.isFinite(number) ? Math.round(number) : null;
    };

    const normalizeSection = function (detail) {
        const sectionName = String(detail && (detail.section || detail.name) || '').trim();
        if (sectionName === '') {
            return null;
        }

        return {
            page: String(detail && detail.page || qaState.page || location.pathname),
            section: sectionName,
            status: String(detail && detail.status || 'success'),
            fetch_ms: normalizeDuration(detail && (detail.fetch_ms ?? detail.fetchMs)),
            display_ms: normalizeDuration(detail && (detail.display_ms ?? detail.displayMs ?? detail && (detail.fetch_ms ?? detail.fetchMs))),
            detail: String(detail && detail.detail || '').trim(),
            source: String(detail && detail.source || 'client'),
            url: String(detail && detail.url || '').trim(),
        };
    };

    const upsertSection = function (detail) {
        const section = normalizeSection(detail);
        if (!section) {
            return null;
        }

        const existingIndex = qaState.sections.findIndex(function (entry) {
            return entry.page === section.page && entry.section === section.section;
        });

        if (existingIndex >= 0) {
            qaState.sections.splice(existingIndex, 1, section);
        } else {
            qaState.sections.push(section);
        }

        return section;
    };

    qaState.markSection = upsertSection;

    const printReport = function (reason) {
        const metrics = [
            { metric: 'first_paint_ms', value: fp ? Math.round(fp.startTime) : null },
            { metric: 'first_contentful_paint_ms', value: fcp ? Math.round(fcp.startTime) : null },
            { metric: 'dom_interactive_ms', value: nav ? Math.round(nav.domInteractive) : null },
            { metric: 'dom_content_loaded_ms', value: nav ? Math.round(nav.domContentLoadedEventEnd) : null },
            { metric: 'load_event_end_ms', value: nav ? Math.round(nav.loadEventEnd) : null },
            { metric: 'rough_tti_ms', value: nav ? Math.round(nav.domInteractive) : null },
            { metric: 'server_request_count', value: server.request_count || 0 },
            { metric: 'server_total_request_ms', value: server.total_request_ms || 0 },
        ];

        const header = '[HRIS QA PERF] ' + (server.page || location.pathname) + (reason ? ' (' + reason + ')' : '');
        console.groupCollapsed(header);
        console.table(metrics);

        if (Array.isArray(server.requests) && server.requests.length) {
            console.table(server.requests.map(function (request) {
                return {
                    role: request.role,
                    method: request.method,
                    resource: request.resource,
                    query_type: request.query_type,
                    status: request.status,
                    duration_ms: request.duration_ms,
                    rows: request.rows,
                    limit: request.limit,
                    select_count: request.select_count,
                    filter_count: request.filter_count,
                    query_shape: request.query_shape,
                    error: request.error || '',
                };
            }));
        }

        if (Array.isArray(qaState.sections) && qaState.sections.length) {
            console.table(qaState.sections.map(function (section) {
                return {
                    section: section.section,
                    status: section.status,
                    fetch_ms: section.fetch_ms,
                    display_ms: section.display_ms,
                    detail: section.detail,
                    source: section.source,
                };
            }));
        }

        if (Array.isArray(server.high_limit_reads) && server.high_limit_reads.length) {
            console.warn('High-limit server reads during page load', server.high_limit_reads);
        }

        console.groupEnd();
    };

    const scheduleReport = function (reason) {
        if (document.readyState !== 'complete') {
            return;
        }

        window.clearTimeout(reportTimer);
        reportTimer = window.setTimeout(function () {
            printReport(reason);
        }, 120);
    };

    document.addEventListener('hris:qa-perf-section', function (event) {
        const section = upsertSection(event.detail || {});
        if (!section) {
            return;
        }

        console.info('[HRIS QA PERF][section]', {
            section: section.section,
            status: section.status,
            fetch_ms: section.fetch_ms,
            display_ms: section.display_ms,
            detail: section.detail,
        });
        scheduleReport('section-update');
    });

    if (document.readyState === 'complete') {
        window.setTimeout(function () {
            printReport('page-load');
        }, 0);
        return;
    }

    window.addEventListener('load', function () {
        printReport('page-load');
    }, { once: true });
})();
</script>
HTML;

        return str_replace('__PAYLOAD__', $payload, $script);
    }
}

if (!function_exists('systemTopnavFetchPeopleProfile')) {
    function systemTopnavFetchPeopleProfile(string $supabaseUrl, array $headers, string $userId): array
    {
        $result = [
            'first_name' => '',
            'surname' => '',
            'display_name' => '',
            'profile_photo_path' => '',
            'profile_photo_url' => null,
        ];

        if ($supabaseUrl === '' || $headers === [] || !isValidUuid($userId) || !function_exists('apiRequest') || !function_exists('isSuccessful')) {
            return $result;
        }

        $response = apiRequest(
            'GET',
            systemSupabaseRestUrl(
                $supabaseUrl,
                'people',
                'first_name,surname,profile_photo_url',
                ['user_id=eq.' . rawurlencode($userId)],
                null,
                1
            ),
            $headers
        );

        if (!isSuccessful($response) || empty((array)($response['data'] ?? []))) {
            return $result;
        }

        $row = (array)$response['data'][0];
        $result['first_name'] = trim((string)($row['first_name'] ?? ''));
        $result['surname'] = trim((string)($row['surname'] ?? ''));
        $result['display_name'] = trim($result['first_name'] . ' ' . $result['surname']);
        $result['profile_photo_path'] = trim((string)($row['profile_photo_url'] ?? ''));
        $result['profile_photo_url'] = systemTopnavResolveProfilePhotoUrl($result['profile_photo_path']);

        return $result;
    }
}

if (!function_exists('systemTopnavFetchNotificationSummary')) {
    function systemTopnavFetchNotificationSummary(string $supabaseUrl, array $headers, string $userId, array $options = []): array
    {
        $result = [
            'unread_count' => 0,
            'notifications_preview' => [],
        ];

        if ($supabaseUrl === '' || $headers === [] || !isValidUuid($userId) || !function_exists('apiRequest') || !function_exists('isSuccessful')) {
            return $result;
        }

        $unreadFilters = (array)($options['unread_filters'] ?? []);
        array_unshift($unreadFilters, 'recipient_user_id=eq.' . rawurlencode($userId), 'is_read=eq.false');

        $previewFilters = (array)($options['preview_filters'] ?? []);
        array_unshift($previewFilters, 'recipient_user_id=eq.' . rawurlencode($userId));

        $unreadSelect = (string)($options['unread_select'] ?? 'id');
        $previewSelect = (string)($options['preview_select'] ?? 'id,title,body,link_url,is_read,created_at,category');
        $unreadLimit = (int)($options['unread_limit'] ?? 200);
        $previewLimit = (int)($options['preview_limit'] ?? 8);
        $previewOrder = trim((string)($options['preview_order'] ?? 'created_at.desc'));

        $unreadResponse = apiRequest(
            'GET',
            systemSupabaseRestUrl($supabaseUrl, 'notifications', $unreadSelect, $unreadFilters, null, $unreadLimit),
            $headers
        );

        if (isSuccessful($unreadResponse)) {
            $result['unread_count'] = count((array)($unreadResponse['data'] ?? []));
        }

        $previewResponse = apiRequest(
            'GET',
            systemSupabaseRestUrl($supabaseUrl, 'notifications', $previewSelect, $previewFilters, $previewOrder, $previewLimit),
            $headers
        );

        if (isSuccessful($previewResponse)) {
            $result['notifications_preview'] = array_values((array)($previewResponse['data'] ?? []));
        }

        return $result;
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

if (!function_exists('systemTailwindThemeConfig')) {
    function systemTailwindThemeConfig(): array
    {
        return [
            'theme' => [
                'extend' => [
                    'colors' => [
                        'daGreen' => '#1B5E20',
                        'approved' => '#B7F7A3',
                        'pending' => '#F9F871',
                        'rejected' => '#FF9A9A',
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('systemRenderTailwindConfigScript')) {
    function systemRenderTailwindConfigScript(): string
    {
        $configJson = json_encode(systemTailwindThemeConfig(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($configJson) || $configJson === '') {
            $configJson = '{}';
        }

        return "<script>\n  tailwind.config = {$configJson};\n</script>";
    }
}

if (!function_exists('systemRenderHeadAssets')) {
    function systemRenderHeadAssets(array $options = []): string
    {
        $title = trim((string)($options['title'] ?? 'DA HRIS'));
        $title = $title !== '' ? $title : 'DA HRIS';

        $loadSweetAlert = (bool)($options['sweetalert'] ?? false);
        $loadFlatpickr = (bool)($options['flatpickr'] ?? false);
        $loadChartJs = (bool)($options['chart_js'] ?? false);
        $loadMaterialIcons = (bool)($options['material_icons'] ?? false);
        $loadMaterialSymbols = (bool)($options['material_symbols'] ?? true);

        $markup = [
            '<meta charset="UTF-8" />',
            '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>',
            '<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
            '<script src="https://cdn.tailwindcss.com"></script>',
        ];

        if ($loadSweetAlert) {
            $markup[] = '<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        }

        if ($loadMaterialIcons) {
            $markup[] = '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
        }

        if ($loadMaterialSymbols) {
            $markup[] = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />';
        }

        if ($loadChartJs) {
            $markup[] = '<script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        }

        if ($loadFlatpickr) {
            $markup[] = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">';
            $markup[] = '<script defer src="https://cdn.jsdelivr.net/npm/flatpickr"></script>';
        }

        $markup[] = '<link rel="icon" type="image/png" sizes="32x32" href="' . htmlspecialchars(systemAppPath('/assets/images/favicon.png'), ENT_QUOTES, 'UTF-8') . '">';
        $markup[] = '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars(systemAppPath('/assets/images/favicon.ico'), ENT_QUOTES, 'UTF-8') . '">';
        $markup[] = '<link rel="apple-touch-icon" sizes="180x180" href="' . htmlspecialchars(systemAppPath('/assets/images/apple-touch-icon.png'), ENT_QUOTES, 'UTF-8') . '">';
        $markup[] = '<link rel="stylesheet" href="' . htmlspecialchars(systemAppPath('/global.css'), ENT_QUOTES, 'UTF-8') . '">';

        if ($loadMaterialSymbols) {
            $markup[] = '<style>.material-symbols-outlined { font-variation-settings: \"wght\" 400; }</style>';
        }

        $markup[] = systemRenderTailwindConfigScript();

        return implode("\n", $markup);
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
