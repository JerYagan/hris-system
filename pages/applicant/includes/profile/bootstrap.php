<?php

require_once __DIR__ . '/../lib/applicant-backend.php';

$backend = applicantBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$applicantUserId = (string)($backend['applicant_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

if (!function_exists('decodeApplicantProfileSupabaseErrorBody')) {
    function decodeApplicantProfileSupabaseErrorBody(array $response): array
    {
        $raw = trim((string)($response['raw'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('isApplicantProfileMissingTableResponse')) {
    function isApplicantProfileMissingTableResponse(array $response, string $tableName = ''): bool
    {
        if ((int)($response['status'] ?? 0) !== 404) {
            return false;
        }

        $decoded = decodeApplicantProfileSupabaseErrorBody($response);
        $code = strtoupper((string)($decoded['code'] ?? ''));
        $message = strtolower((string)($decoded['message'] ?? ''));
        $raw = strtolower(trim((string)($response['raw'] ?? '')));
        $normalizedTableName = strtolower(trim($tableName));

        if ($code === 'PGRST205') {
            return true;
        }

        if ($normalizedTableName !== '' && (str_contains($message, $normalizedTableName) || str_contains($raw, $normalizedTableName))) {
            return true;
        }

        return str_contains($message, 'could not find the table') || str_contains($raw, 'could not find the table');
    }
}

if (!function_exists('applicantProfileTableExists')) {
    function applicantProfileTableExists(string $supabaseUrl, array $headers, string $tableName): bool
    {
        static $cache = [];

        $cacheKey = $supabaseUrl . '|' . strtolower($tableName);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $response = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/' . $tableName . '?select=id&limit=1',
            $headers
        );

        $cache[$cacheKey] = !isApplicantProfileMissingTableResponse($response, 'public.' . $tableName);
        return $cache[$cacheKey];
    }
}
