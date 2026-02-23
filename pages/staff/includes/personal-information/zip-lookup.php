<?php

require_once __DIR__ . '/../lib/staff-backend.php';

header('Content-Type: application/json; charset=utf-8');

$bootstrap = staffModuleBootstrapContext();
$supabaseUrl = (string)($bootstrap['supabase_url'] ?? '');
$serviceRoleKey = (string)($bootstrap['service_role_key'] ?? '');
$headers = (array)($bootstrap['headers'] ?? []);
$staffContext = (array)($bootstrap['staff_context'] ?? []);
$staffContextIsValid = (bool)($staffContext['is_valid'] ?? false);

if ($supabaseUrl === '' || $serviceRoleKey === '' || empty($headers) || !$staffContextIsValid) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'zip' => null,
        'message' => 'Unauthorized',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$city = cleanText($_GET['city'] ?? null) ?? '';
$barangay = cleanText($_GET['barangay'] ?? null) ?? '';

if ($city === '' || $barangay === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'zip' => null,
        'message' => 'City and barangay are required.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$zipCodeJsonFilePath = __DIR__ . '/../../../../assets/zip-codes.json';
$zipCodeTxtFilePath = __DIR__ . '/../../../../assets/zip-codes.txt';
$zipCodeFilePath = is_file($zipCodeJsonFilePath) ? $zipCodeJsonFilePath : $zipCodeTxtFilePath;

$zip = resolveZipCodeByCityBarangay($city, $barangay, $zipCodeFilePath);

if ($zip === null) {
    $psgcMunicipalitiesPath = __DIR__ . '/../../../../assets/psgc/municipalities.json';
    $psgcBarangaysPath = __DIR__ . '/../../../../assets/psgc/barangays.json';

    $municipalityByCode = [];
    if (is_file($psgcMunicipalitiesPath)) {
        $decodedMunicipalities = json_decode((string)file_get_contents($psgcMunicipalitiesPath), true);
        if (is_array($decodedMunicipalities)) {
            foreach ($decodedMunicipalities as $municipalityRow) {
                if (!is_array($municipalityRow)) {
                    continue;
                }

                $code = trim((string)($municipalityRow['code'] ?? $municipalityRow['citymun'] ?? $municipalityRow['city_code'] ?? ''));
                $name = trim((string)($municipalityRow['name'] ?? $municipalityRow['citymun_name'] ?? $municipalityRow['city_municipality_name'] ?? ''));
                if ($code !== '' && $name !== '') {
                    $municipalityByCode[$code] = $name;
                }
            }
        }
    }

    if (is_file($psgcBarangaysPath)) {
        $decodedBarangays = json_decode((string)file_get_contents($psgcBarangaysPath), true);
        if (is_array($decodedBarangays)) {
            $cityVariants = buildZipLookupVariants($city, false);
            $barangayVariants = buildZipLookupVariants($barangay, true);

            $zipSet = [];
            foreach ($decodedBarangays as $barangayRow) {
                if (!is_array($barangayRow)) {
                    continue;
                }

                $barangayCityRef = trim((string)($barangayRow['citymun'] ?? $barangayRow['city_code'] ?? $barangayRow['city_municipality_name'] ?? ''));
                $barangayCityName = (string)($municipalityByCode[$barangayCityRef] ?? $barangayCityRef);
                $barangayName = (string)($barangayRow['name'] ?? $barangayRow['barangay_name'] ?? '');
                $zipCode = trim((string)($barangayRow['zip_code'] ?? ''));

                if ($barangayCityName === '' || $barangayName === '' || $zipCode === '') {
                    continue;
                }

                $cityKey = normalizeZipLookupPart($barangayCityName);
                $barangayKey = normalizeZipLookupPart($barangayName);
                if (!in_array($cityKey, $cityVariants, true) || !in_array($barangayKey, $barangayVariants, true)) {
                    continue;
                }

                $zipSet[$zipCode] = true;
            }

            if (count($zipSet) === 1) {
                $zip = (string)array_key_first($zipSet);
            }
        }
    }
}

echo json_encode([
    'ok' => true,
    'zip' => $zip,
], JSON_UNESCAPED_UNICODE);
