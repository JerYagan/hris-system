<?php

if (!function_exists('legacyRfidRegistryFetchJson')) {
    function legacyRfidRegistryFetchJson(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('legacyRfidRegistryPhotoUrl')) {
    function legacyRfidRegistryPhotoUrl(?string $rawPath, string $appBaseUrl, bool $isHrisPhoto): string
    {
        $path = trim((string)$rawPath);
        if ($path === '') {
            return 'https://via.placeholder.com/150';
        }

        if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        if (!$isHrisPhoto) {
            return $path;
        }

        return rtrim($appBaseUrl, '/') . '/storage/document/' . ltrim($path, '/');
    }
}

if (!function_exists('legacyRfidRegistryDisplayName')) {
    function legacyRfidRegistryDisplayName(array $personRow, array $rfidRow): string
    {
        $legacyName = trim((string)($rfidRow['name'] ?? ''));
        if ($legacyName !== '') {
            return $legacyName;
        }

        $firstName = trim((string)($personRow['first_name'] ?? ''));
        $surname = trim((string)($personRow['surname'] ?? ''));
        $fullName = trim($firstName . ' ' . $surname);
        return $fullName !== '' ? $fullName : 'Unnamed Employee';
    }
}

if (!function_exists('legacyRfidRegistryBuildRoster')) {
    function legacyRfidRegistryBuildRoster(string $supabaseUrl, array $headers, string $apiKey, string $appBaseUrl): array
    {
        $privilegedKey = (string)(legacyEnvValue('SUPABASE_SERVICE_ROLE_KEY') ?? $apiKey);
        $privilegedHeaders = [
            "apikey: $privilegedKey",
            "Authorization: Bearer $privilegedKey",
            'Content-Type: application/json',
        ];

        $rfidEmployees = legacyRfidRegistryFetchJson(
            $supabaseUrl . 'employees?select=uid,employee_id,name,birthday,photo,person_id&order=name.asc',
            $headers
        );

        $rfidByPersonId = [];
        $rfidByEmployeeId = [];
        foreach ($rfidEmployees as $rfidEmployee) {
            if (!is_array($rfidEmployee)) {
                continue;
            }

            $personId = trim((string)($rfidEmployee['person_id'] ?? ''));
            $employeeId = strtoupper(trim((string)($rfidEmployee['employee_id'] ?? '')));

            if ($personId !== '') {
                $rfidByPersonId[$personId] = $rfidEmployee;
            }
            if ($employeeId !== '') {
                $rfidByEmployeeId[$employeeId] = $rfidEmployee;
            }
        }

        $employmentRows = legacyRfidRegistryFetchJson(
            $supabaseUrl . 'employment_records?select=person_id&is_current=eq.true&limit=5000',
            $privilegedHeaders
        );

        $personIds = [];
        foreach ($employmentRows as $employmentRow) {
            $personId = trim((string)($employmentRow['person_id'] ?? ''));
            if ($personId !== '') {
                $personIds[$personId] = true;
            }
        }

        $peopleRows = [];
        if ($personIds !== []) {
            foreach (array_chunk(array_keys($personIds), 100) as $chunk) {
                $inFilter = 'id=in.(' . implode(',', array_map('rawurlencode', $chunk)) . ')';
                $peopleRows = array_merge(
                    $peopleRows,
                    legacyRfidRegistryFetchJson(
                        $supabaseUrl
                        . 'people?select=id,first_name,surname,agency_employee_no,date_of_birth,profile_photo_url'
                        . '&' . $inFilter
                        . '&order=surname.asc,first_name.asc',
                        $privilegedHeaders
                    )
                );
            }
        }

        $employees = [];
        $seenLegacyUids = [];

        foreach ($peopleRows as $personRow) {
            if (!is_array($personRow)) {
                continue;
            }

            $personId = trim((string)($personRow['id'] ?? ''));
            $agencyEmployeeNo = strtoupper(trim((string)($personRow['agency_employee_no'] ?? '')));
            $rfidRow = $rfidByPersonId[$personId] ?? ($agencyEmployeeNo !== '' ? ($rfidByEmployeeId[$agencyEmployeeNo] ?? []) : []);
            $uid = strtoupper(trim((string)($rfidRow['uid'] ?? '')));

            if ($uid !== '') {
                $seenLegacyUids[$uid] = true;
            }

            $employees[] = [
                'person_id' => $personId,
                'uid' => $uid,
                'employee_id' => trim((string)($rfidRow['employee_id'] ?? $personRow['agency_employee_no'] ?? '')),
                'name' => legacyRfidRegistryDisplayName($personRow, $rfidRow),
                'birthday' => trim((string)($rfidRow['birthday'] ?? $personRow['date_of_birth'] ?? '')),
                'photo' => legacyRfidRegistryPhotoUrl(
                    (string)($rfidRow['photo'] ?? $personRow['profile_photo_url'] ?? ''),
                    $appBaseUrl,
                    trim((string)($rfidRow['photo'] ?? '')) === ''
                ),
                'photo_path' => (string)($rfidRow['photo'] ?? $personRow['profile_photo_url'] ?? ''),
                'source' => $uid !== '' ? 'hris_mapped_uid' : 'hris_employee',
            ];
        }

        foreach ($rfidEmployees as $rfidEmployee) {
            if (!is_array($rfidEmployee)) {
                continue;
            }

            $uid = strtoupper(trim((string)($rfidEmployee['uid'] ?? '')));
            if ($uid !== '' && isset($seenLegacyUids[$uid])) {
                continue;
            }

            $employees[] = [
                'person_id' => trim((string)($rfidEmployee['person_id'] ?? '')),
                'uid' => $uid,
                'employee_id' => trim((string)($rfidEmployee['employee_id'] ?? '')),
                'name' => trim((string)($rfidEmployee['name'] ?? '')) !== '' ? trim((string)($rfidEmployee['name'] ?? '')) : 'Unlinked RFID Employee',
                'birthday' => trim((string)($rfidEmployee['birthday'] ?? '')),
                'photo' => legacyRfidRegistryPhotoUrl((string)($rfidEmployee['photo'] ?? ''), $appBaseUrl, false),
                'photo_path' => (string)($rfidEmployee['photo'] ?? ''),
                'source' => trim((string)($rfidEmployee['person_id'] ?? '')) !== '' ? 'rfid_mapped' : 'rfid_unlinked',
            ];
        }

        usort($employees, static function (array $left, array $right): int {
            return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
        });

        return $employees;
    }
}

if (!function_exists('legacyRfidRegistryIndexByUid')) {
    function legacyRfidRegistryIndexByUid(array $employees): array
    {
        $map = [];
        foreach ($employees as $employee) {
            if (!is_array($employee)) {
                continue;
            }

            $uid = strtoupper(trim((string)($employee['uid'] ?? '')));
            if ($uid === '') {
                continue;
            }

            $map[$uid] = $employee;
        }

        return $map;
    }
}
