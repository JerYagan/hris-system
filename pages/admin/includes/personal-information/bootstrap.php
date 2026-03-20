<?php

require_once __DIR__ . '/../lib/admin-backend.php';

$backend = adminBackendContext();
$supabaseUrl = (string)($backend['supabase_url'] ?? '');
$serviceRoleKey = (string)($backend['service_role_key'] ?? '');
$headers = (array)($backend['headers'] ?? []);
$adminUserId = (string)($backend['admin_user_id'] ?? '');

if ($supabaseUrl === '' || $serviceRoleKey === '') {
    redirectWithState('error', 'Supabase credentials are missing. Check your .env file.');
}

if (!function_exists('decodeSupabaseErrorBody')) {
    function decodeSupabaseErrorBody(array $response): array
    {
        $raw = trim((string)($response['raw'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('isSupabaseMissingTableResponse')) {
    function isSupabaseMissingTableResponse(array $response, string $tableName = ''): bool
    {
        if ((int)($response['status'] ?? 0) !== 404) {
            return false;
        }

        $decoded = decodeSupabaseErrorBody($response);
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

if (!function_exists('personalInfoTableExists')) {
    function personalInfoTableExists(string $supabaseUrl, array $headers, string $tableName): bool
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

        $cache[$cacheKey] = !isSupabaseMissingTableResponse($response, 'public.' . $tableName);
        return $cache[$cacheKey];
    }
}

if (!function_exists('mapAdminEducationLevelToSchema')) {
    function mapAdminEducationLevelToSchema(string $educationLevel): string
    {
        $normalized = strtolower(trim($educationLevel));
        return match ($normalized) {
            'elementary' => 'elementary',
            'secondary' => 'secondary',
            'vocational_trade_course', 'vocational' => 'vocational',
            'college' => 'college',
            'graduate_studies', 'graduate' => 'graduate',
            default => '',
        };
    }
}

if (!function_exists('mapSchemaEducationLevelToAdmin')) {
    function mapSchemaEducationLevelToAdmin(string $educationLevel): string
    {
        $normalized = strtolower(trim($educationLevel));
        return match ($normalized) {
            'elementary' => 'elementary',
            'secondary' => 'secondary',
            'vocational' => 'vocational_trade_course',
            'college' => 'college',
            'graduate' => 'graduate_studies',
            default => '',
        };
    }
}

if (!function_exists('buildPersonEducationPayload')) {
    function buildPersonEducationPayload(array $educationRow, string $personId, int $sequenceNo): ?array
    {
        $schemaLevel = mapAdminEducationLevelToSchema((string)($educationRow['education_level'] ?? ''));
        if ($schemaLevel === '') {
            return null;
        }

        $schoolName = trim((string)($educationRow['school_name'] ?? ''));
        $courseDegree = trim((string)($educationRow['degree_course'] ?? ($educationRow['course_degree'] ?? '')));
        $periodFrom = trim((string)($educationRow['attendance_from_year'] ?? ($educationRow['period_from'] ?? '')));
        $periodTo = trim((string)($educationRow['attendance_to_year'] ?? ($educationRow['period_to'] ?? '')));
        $highestLevelUnits = trim((string)($educationRow['highest_level_units_earned'] ?? ($educationRow['highest_level_units'] ?? '')));
        $yearGraduated = trim((string)($educationRow['year_graduated'] ?? ''));
        $honorsReceived = trim((string)($educationRow['scholarship_honors_received'] ?? ($educationRow['honors_received'] ?? '')));

        $hasValue = $schoolName !== ''
            || $courseDegree !== ''
            || $periodFrom !== ''
            || $periodTo !== ''
            || $highestLevelUnits !== ''
            || $yearGraduated !== ''
            || $honorsReceived !== '';

        if (!$hasValue) {
            return null;
        }

        return [
            'person_id' => $personId,
            'education_level' => $schemaLevel,
            'school_name' => $schoolName !== '' ? $schoolName : null,
            'course_degree' => $courseDegree !== '' ? $courseDegree : null,
            'period_from' => $periodFrom !== '' ? $periodFrom : null,
            'period_to' => $periodTo !== '' ? $periodTo : null,
            'highest_level_units' => $highestLevelUnits !== '' ? $highestLevelUnits : null,
            'year_graduated' => $yearGraduated !== '' ? $yearGraduated : null,
            'honors_received' => $honorsReceived !== '' ? $honorsReceived : null,
            'sequence_no' => $sequenceNo,
        ];
    }
}

if (!function_exists('mapPersonEducationRowToAdmin')) {
    function mapPersonEducationRowToAdmin(array $educationRow): ?array
    {
        $adminLevel = mapSchemaEducationLevelToAdmin((string)($educationRow['education_level'] ?? ''));
        if ($adminLevel === '') {
            return null;
        }

        return [
            'id' => (string)($educationRow['id'] ?? ''),
            'education_level' => $adminLevel,
            'school_name' => (string)($educationRow['school_name'] ?? ''),
            'degree_course' => (string)($educationRow['course_degree'] ?? ''),
            'attendance_from_year' => (string)($educationRow['period_from'] ?? ''),
            'attendance_to_year' => (string)($educationRow['period_to'] ?? ''),
            'highest_level_units_earned' => (string)($educationRow['highest_level_units'] ?? ''),
            'year_graduated' => (string)($educationRow['year_graduated'] ?? ''),
            'scholarship_honors_received' => (string)($educationRow['honors_received'] ?? ''),
            'sequence_no' => (int)($educationRow['sequence_no'] ?? 1),
        ];
    }
}
