<?php
require __DIR__ . '/../pages/staff/includes/lib/common.php';

$zip = loadZipCodeLookupFromFile(__DIR__ . '/../assets/zip-codes.json');

echo 'zipCities=' . count($zip) . PHP_EOL;
$pairs = 0;
foreach ($zip as $barangaysByCity) {
    if (is_array($barangaysByCity)) {
        $pairs += count($barangaysByCity);
    }
}
echo 'zipPairs=' . $pairs . PHP_EOL;

$barangays = json_decode((string)file_get_contents(__DIR__ . '/../assets/psgc/barangays.json'), true);
echo 'psgcBarangays=' . (is_array($barangays) ? count($barangays) : 0) . PHP_EOL;

$withZip = 0;
if (is_array($barangays)) {
    foreach ($barangays as $row) {
        if (is_array($row) && trim((string)($row['zip_code'] ?? '')) !== '') {
            $withZip++;
        }
    }
}
echo 'psgcBarangaysWithZip=' . $withZip . PHP_EOL;
