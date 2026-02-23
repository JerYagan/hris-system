<?php

require __DIR__ . '/../pages/staff/includes/lib/common.php';

$barangaysPath = __DIR__ . '/../assets/psgc/barangays.json';
$zipJsonPath = __DIR__ . '/../assets/zip-codes.json';
$zipTxtPath = __DIR__ . '/../assets/zip-codes.txt';
$zipPath = is_file($zipJsonPath) ? $zipJsonPath : $zipTxtPath;

if (!is_file($barangaysPath)) {
    fwrite(STDERR, "Missing PSGC barangays file: assets/psgc/barangays.json\n");
    exit(1);
}

$barangays = json_decode((string)file_get_contents($barangaysPath), true);
if (!is_array($barangays)) {
    fwrite(STDERR, "Invalid JSON in assets/psgc/barangays.json\n");
    exit(1);
}

$zipLookup = loadZipCodeLookupFromFile($zipPath);
if (!is_array($zipLookup) || empty($zipLookup)) {
    fwrite(STDERR, "ZIP lookup source is empty.\n");
    exit(1);
}

$cityZipFallback = [];
foreach ($zipLookup as $cityKey => $barangaysByKey) {
    if (!is_array($barangaysByKey)) {
        continue;
    }

    $cityKey = normalizeZipLookupPart((string)$cityKey);
    if ($cityKey === '') {
        continue;
    }

    $zipCandidates = [];
    if (isset($barangaysByKey[$cityKey]) && is_array($barangaysByKey[$cityKey])) {
        foreach ($barangaysByKey[$cityKey] as $zipValue) {
            $zipCode = trim((string)$zipValue);
            if ($zipCode !== '') {
                $zipCandidates[$zipCode] = true;
            }
        }
    }

    if (empty($zipCandidates)) {
        foreach ($barangaysByKey as $zipList) {
            if (!is_array($zipList)) {
                continue;
            }
            foreach ($zipList as $zipValue) {
                $zipCode = trim((string)$zipValue);
                if ($zipCode !== '') {
                    $zipCandidates[$zipCode] = true;
                }
            }
        }
    }

    if (!empty($zipCandidates)) {
        $zipCodes = array_keys($zipCandidates);
        usort($zipCodes, static fn ($a, $b) => strnatcasecmp((string)$a, (string)$b));
        $cityZipFallback[$cityKey] = (string)$zipCodes[0];
    }
}

$updatedCount = 0;
foreach ($barangays as $index => $row) {
    if (!is_array($row)) {
        continue;
    }

    $cityKey = normalizeZipLookupPart((string)($row['citymun'] ?? ''));
    $barangayKey = normalizeZipLookupPart((string)($row['name'] ?? ''));
    if ($cityKey === '' || $barangayKey === '') {
        continue;
    }

    $zipCode = null;
    if (isset($zipLookup[$cityKey][$barangayKey]) && is_array($zipLookup[$cityKey][$barangayKey]) && !empty($zipLookup[$cityKey][$barangayKey])) {
        $zipList = array_values(array_filter(array_map(static fn ($value) => trim((string)$value), $zipLookup[$cityKey][$barangayKey]), static fn ($value) => $value !== ''));
        if (!empty($zipList)) {
            usort($zipList, static fn ($a, $b) => strnatcasecmp((string)$a, (string)$b));
            $zipCode = (string)$zipList[0];
        }
    }

    if ($zipCode === null || $zipCode === '') {
        $zipCode = (string)($cityZipFallback[$cityKey] ?? '');
    }

    if ($zipCode === '') {
        continue;
    }

    $currentZip = trim((string)($row['zip_code'] ?? ''));
    if ($currentZip !== $zipCode) {
        $barangays[$index]['zip_code'] = $zipCode;
        $updatedCount++;
    }
}

$encoded = json_encode($barangays, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($encoded) || $encoded === '') {
    fwrite(STDERR, "Failed to encode enriched barangays JSON.\n");
    exit(1);
}

if (file_put_contents($barangaysPath, $encoded) === false) {
    fwrite(STDERR, "Failed to write assets/psgc/barangays.json\n");
    exit(1);
}

echo "Enriched assets/psgc/barangays.json with zip_code values. Updated rows: {$updatedCount}\n";
