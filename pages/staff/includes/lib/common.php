<?php

require_once __DIR__ . '/../../../shared/lib/system-helpers.php';

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $envPath): void
    {
        if (!file_exists($envPath)) {
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

if (!function_exists('cleanText')) {
    function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string)$value);
        return $text === '' ? null : $text;
    }
}

if (!function_exists('isValidUuid')) {
    function isValidUuid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return (bool)preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            trim($value)
        );
    }
}

if (!function_exists('ensureCsrfToken')) {
    function ensureCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $token = $_SESSION['csrf_token'] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }
}

if (!function_exists('isValidCsrfToken')) {
    function isValidCsrfToken(?string $token): bool
    {
        if ($token === null || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('clientIp')) {
    function clientIp(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            $raw = $_SERVER[$key] ?? '';
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }

            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $raw);
                return trim((string)($parts[0] ?? ''));
            }

            return trim($raw);
        }

        return 'unknown';
    }
}

if (!function_exists('redirectWithState')) {
    function redirectWithState(string $state, string $message, ?string $path = null): never
    {
        $targetPath = cleanText($path);
        if ($targetPath === null) {
            $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            $targetPath = basename($requestPath !== '' ? $requestPath : 'dashboard.php');
        }

        if ($targetPath === '') {
            $targetPath = 'dashboard.php';
        }

        $query = http_build_query([
            'state' => $state,
            'message' => $message,
        ]);

        header('Location: ' . $targetPath . '?' . $query);
        exit;
    }
}

if (!function_exists('apiRequest')) {
    function apiRequest(string $method, string $url, array $headers, ?array $body = null): array
    {
        $attempts = strtoupper($method) === 'GET' ? 2 : 1;
        $attempt = 0;
        $lastResponse = [
            'status' => 0,
            'data' => [],
            'raw' => '',
            'error' => null,
        ];

        while ($attempt < $attempts) {
            $attempt++;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $raw = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                $decoded = [];
            }

            $lastResponse = [
                'status' => $status,
                'data' => $decoded,
                'raw' => (string)$raw,
                'error' => $error !== '' ? $error : null,
            ];

            $isTransientFailure = $status === 0 || $status >= 500;
            if ($attempt < $attempts && $isTransientFailure) {
                usleep(random_int(80, 180) * 1000);
                continue;
            }

            break;
        }

        return $lastResponse;
    }
}

if (!function_exists('isSuccessful')) {
    function isSuccessful(array $response): bool
    {
        $status = (int)($response['status'] ?? 0);
        return $status >= 200 && $status < 300;
    }
}

if (!function_exists('normalizeZipLookupPart')) {
    function normalizeZipLookupPart(?string $value): string
    {
        $text = strtolower(trim((string)$value));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }
}

if (!function_exists('buildZipLookupCandidatesFromLocation')) {
    function buildZipLookupCandidatesFromLocation(string $locationText): array
    {
        $normalized = trim((string)preg_replace('/\s+/', ' ', $locationText));
        if ($normalized === '') {
            return [];
        }

        $cleaned = trim((string)preg_replace('/\s*\(.*?\)\s*/', ' ', $normalized));
        $cleaned = trim((string)preg_replace('/\s+/', ' ', $cleaned));
        if ($cleaned === '') {
            return [];
        }

        $candidates = [];

        if (str_contains($cleaned, '|')) {
            $parts = array_values(array_filter(array_map('trim', explode('|', $cleaned)), static fn ($part) => $part !== ''));
            if (count($parts) >= 2) {
                $candidates[] = ['city' => $parts[0], 'barangay' => $parts[1]];
            }
        }

        if (str_contains($cleaned, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $cleaned)), static fn ($part) => $part !== ''));
            if (count($parts) >= 2) {
                $first = $parts[0];
                $second = $parts[1];
                $secondLower = strtolower($second);

                $looksLikeSupplement = str_starts_with($secondLower, 'incl')
                    || str_starts_with($secondLower, 'including')
                    || str_starts_with($secondLower, 'and ')
                    || str_contains($secondLower, 'village')
                    || str_contains($secondLower, 'highway')
                    || str_contains($secondLower, 'road')
                    || str_contains($secondLower, 'complex');

                if (!$looksLikeSupplement) {
                    $candidates[] = ['city' => $second, 'barangay' => $first];
                    $candidates[] = ['city' => $first, 'barangay' => $second];
                }
            }
        }

        if (empty($candidates)) {
            $cityLike = trim((string)preg_replace('/\s*\(.*?\)\s*/', ' ', $cleaned));
            $cityLike = trim((string)preg_replace('/\s+/', ' ', $cityLike));
            if ($cityLike !== '') {
                $candidates[] = ['city' => $cityLike, 'barangay' => $cityLike];
            }
        }

        return $candidates;
    }
}

if (!function_exists('loadZipCodeLookupFromFile')) {
    function loadZipCodeLookupFromFile(string $filePath): array
    {
        static $cache = [];

        $resolvedPath = trim($filePath);
        if ($resolvedPath === '') {
            return [];
        }

        if (array_key_exists($resolvedPath, $cache)) {
            return $cache[$resolvedPath];
        }

        if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
            $cache[$resolvedPath] = [];
            return $cache[$resolvedPath];
        }

        $lookup = [];
        $extension = strtolower((string)pathinfo($resolvedPath, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            $rawJson = file_get_contents($resolvedPath);
            $decoded = is_string($rawJson) ? json_decode($rawJson, true) : null;

            if (is_array($decoded)) {
                if (array_is_list($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!is_array($entry)) {
                            continue;
                        }

                        $cityKey = normalizeZipLookupPart((string)($entry['city'] ?? $entry['city_municipality'] ?? ''));
                        $barangayKey = normalizeZipLookupPart((string)($entry['barangay'] ?? ''));
                        $zipCode = trim((string)($entry['zip'] ?? $entry['zip_code'] ?? ''));
                        if ($cityKey === '' || $barangayKey === '' || $zipCode === '') {
                            continue;
                        }

                        if (!isset($lookup[$cityKey])) {
                            $lookup[$cityKey] = [];
                        }
                        if (!isset($lookup[$cityKey][$barangayKey])) {
                            $lookup[$cityKey][$barangayKey] = [];
                        }
                        $lookup[$cityKey][$barangayKey][$zipCode] = true;
                    }
                } else {
                    foreach ($decoded as $city => $barangays) {
                        $cityKey = normalizeZipLookupPart((string)$city);
                        if ($cityKey === '' || !is_array($barangays)) {
                            continue;
                        }

                        foreach ($barangays as $barangay => $zipValues) {
                            $barangayKey = normalizeZipLookupPart((string)$barangay);
                            if ($barangayKey === '') {
                                continue;
                            }

                            $zipList = is_array($zipValues) ? $zipValues : [$zipValues];
                            foreach ($zipList as $zipValue) {
                                $zipCode = trim((string)$zipValue);
                                if ($zipCode === '') {
                                    continue;
                                }

                                if (!isset($lookup[$cityKey])) {
                                    $lookup[$cityKey] = [];
                                }
                                if (!isset($lookup[$cityKey][$barangayKey])) {
                                    $lookup[$cityKey][$barangayKey] = [];
                                }
                                $lookup[$cityKey][$barangayKey][$zipCode] = true;
                            }
                        }
                    }
                }
            }
        } else {
            $lines = file($resolvedPath, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                $cache[$resolvedPath] = [];
                return $cache[$resolvedPath];
            }

            foreach ($lines as $line) {
                $trimmed = trim((string)$line);
                if ($trimmed === '') {
                    continue;
                }

                $zipCode = '';
                $candidates = [];

                if (preg_match('/^(\d{4})\s*:\s*(.+)$/u', $trimmed, $matches)) {
                    $zipCode = trim((string)($matches[1] ?? ''));
                    $locationText = trim((string)($matches[2] ?? ''));
                    if ($locationText !== '') {
                        $candidates = buildZipLookupCandidatesFromLocation($locationText);
                    }
                } elseif (preg_match('/^(.+?)\|(.+?)\|\s*(\d{4})$/u', $trimmed, $matches)) {
                    $zipCode = trim((string)($matches[3] ?? ''));
                    $candidates[] = [
                        'city' => trim((string)($matches[1] ?? '')),
                        'barangay' => trim((string)($matches[2] ?? '')),
                    ];
                } elseif (preg_match('/^(.+?),\s*(.+?),\s*(\d{4})$/u', $trimmed, $matches)) {
                    $zipCode = trim((string)($matches[3] ?? ''));
                    $candidates[] = [
                        'city' => trim((string)($matches[1] ?? '')),
                        'barangay' => trim((string)($matches[2] ?? '')),
                    ];
                }

                if ($zipCode === '' || empty($candidates)) {
                    continue;
                }

                foreach ($candidates as $candidate) {
                    $cityKey = normalizeZipLookupPart((string)($candidate['city'] ?? ''));
                    $barangayKey = normalizeZipLookupPart((string)($candidate['barangay'] ?? ''));
                    if ($cityKey === '' || $barangayKey === '') {
                        continue;
                    }

                    if (!isset($lookup[$cityKey])) {
                        $lookup[$cityKey] = [];
                    }
                    if (!isset($lookup[$cityKey][$barangayKey])) {
                        $lookup[$cityKey][$barangayKey] = [];
                    }

                    $lookup[$cityKey][$barangayKey][$zipCode] = true;
                }
            }
        }

        foreach ($lookup as $cityKey => $barangays) {
            foreach ($barangays as $barangayKey => $zipSet) {
                $zipList = array_keys($zipSet);
                sort($zipList, SORT_NATURAL | SORT_FLAG_CASE);
                $lookup[$cityKey][$barangayKey] = $zipList;
            }
        }

        $cache[$resolvedPath] = $lookup;
        return $cache[$resolvedPath];
    }
}

if (!function_exists('findUniqueZipCodeByCityBarangay')) {
    function findUniqueZipCodeByCityBarangay(string $city, string $barangay, string $filePath): ?string
    {
        $cityKey = normalizeZipLookupPart($city);
        $barangayKey = normalizeZipLookupPart($barangay);
        if ($cityKey === '' || $barangayKey === '') {
            return null;
        }

        $lookup = loadZipCodeLookupFromFile($filePath);
        $zipList = $lookup[$cityKey][$barangayKey] ?? [];
        if (!is_array($zipList) || count($zipList) !== 1) {
            return null;
        }

        $zipCode = trim((string)$zipList[0]);
        return $zipCode !== '' ? $zipCode : null;
    }
}

if (!function_exists('buildZipLookupVariants')) {
    function buildZipLookupVariants(?string $value, bool $isBarangay = false): array
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return [];
        }

        $variants = [];
        $queue = [$raw];

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (!is_string($current)) {
                continue;
            }

            $normalized = normalizeZipLookupPart($current);
            if ($normalized === '' || isset($variants[$normalized])) {
                continue;
            }

            $variants[$normalized] = true;

            $withoutParens = trim((string)preg_replace('/\s*\(.*?\)\s*/u', ' ', $normalized));
            $withoutParens = trim((string)preg_replace('/\s+/', ' ', $withoutParens));
            if ($withoutParens !== '' && !isset($variants[$withoutParens])) {
                $queue[] = $withoutParens;
            }

            $withoutSuffix = trim((string)preg_replace('/\s*,\s*.*$/u', '', $normalized));
            if ($withoutSuffix !== '' && !isset($variants[$withoutSuffix])) {
                $queue[] = $withoutSuffix;
            }

            if ($isBarangay) {
                $withoutBarangayPrefix = trim((string)preg_replace('/^(barangay|brgy\.?|brg\.?|bgy\.?)\s+/u', '', $normalized));
                if ($withoutBarangayPrefix !== '' && !isset($variants[$withoutBarangayPrefix])) {
                    $queue[] = $withoutBarangayPrefix;
                }
            } else {
                $withoutCityPrefix = trim((string)preg_replace('/^(city|city of|municipality|municipality of|mun\.?|municipio de)\s+/u', '', $normalized));
                if ($withoutCityPrefix !== '' && !isset($variants[$withoutCityPrefix])) {
                    $queue[] = $withoutCityPrefix;
                }

                $withoutCitySuffix = trim((string)preg_replace('/\s+city$/u', '', $normalized));
                if ($withoutCitySuffix !== '' && !isset($variants[$withoutCitySuffix])) {
                    $queue[] = $withoutCitySuffix;
                }
            }
        }

        return array_keys($variants);
    }
}

if (!function_exists('resolveZipCodeByCityBarangay')) {
    function resolveZipCodeByCityBarangay(string $city, string $barangay, string $filePath): ?string
    {
        $lookup = loadZipCodeLookupFromFile($filePath);
        if (empty($lookup)) {
            return null;
        }

        $cityVariants = buildZipLookupVariants($city, false);
        $barangayVariants = buildZipLookupVariants($barangay, true);
        if (empty($cityVariants) || empty($barangayVariants)) {
            return null;
        }

        $exactZipSet = [];
        foreach ($cityVariants as $cityKey) {
            $barangays = $lookup[$cityKey] ?? null;
            if (!is_array($barangays)) {
                continue;
            }

            foreach ($barangayVariants as $barangayKey) {
                $zipList = $barangays[$barangayKey] ?? null;
                if (!is_array($zipList)) {
                    continue;
                }

                foreach ($zipList as $zipValue) {
                    $zipCode = trim((string)$zipValue);
                    if ($zipCode !== '') {
                        $exactZipSet[$zipCode] = true;
                    }
                }
            }
        }

        if (count($exactZipSet) === 1) {
            return (string)array_key_first($exactZipSet);
        }

        if (!empty($exactZipSet)) {
            return null;
        }

        $cityZipSet = [];
        foreach ($cityVariants as $cityKey) {
            $barangays = $lookup[$cityKey] ?? null;
            if (!is_array($barangays)) {
                continue;
            }

            foreach ($barangays as $zipList) {
                if (!is_array($zipList)) {
                    continue;
                }

                foreach ($zipList as $zipValue) {
                    $zipCode = trim((string)$zipValue);
                    if ($zipCode !== '') {
                        $cityZipSet[$zipCode] = true;
                    }
                }
            }
        }

        if (count($cityZipSet) === 1) {
            return (string)array_key_first($cityZipSet);
        }

        return null;
    }
}
