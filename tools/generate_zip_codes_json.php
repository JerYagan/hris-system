<?php

require __DIR__ . '/../pages/staff/includes/lib/common.php';

$txtPath = __DIR__ . '/../assets/zip-codes.txt';
$jsonPath = __DIR__ . '/../assets/zip-codes.json';

$lookup = loadZipCodeLookupFromFile($txtPath);

if (empty($lookup)) {
    fwrite(STDERR, "No ZIP lookup records parsed from zip-codes.txt\n");
    exit(1);
}

$written = file_put_contents(
    $jsonPath,
    json_encode($lookup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

if ($written === false) {
    fwrite(STDERR, "Failed to write assets/zip-codes.json\n");
    exit(1);
}

echo "Generated assets/zip-codes.json ({$written} bytes)\n";
