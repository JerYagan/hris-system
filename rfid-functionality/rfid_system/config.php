<?php

$projectRoot = dirname(__DIR__, 2);
$envPath = $projectRoot . '/.env';

if (!function_exists('legacyEnvValue')) {
    function legacyEnvValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}

if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $trimmed = trim((string)$line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim((string)$value, " \t\n\r\0\x0B\"'");
            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$supabaseBaseUrl = rtrim((string)(legacyEnvValue('SUPABASE_URL') ?? 'https://dgciyvkzprevbharsvwj.supabase.co'), '/');
$supabase_url = $supabaseBaseUrl . '/rest/v1/';
$api_key = (string)(legacyEnvValue('SUPABASE_ANON_KEY') ?? 'sb_publishable_fOVQeSyUlufSQcWrjWMHMQ_ouILxfo_');

$appBaseUrl = rtrim((string)(legacyEnvValue('APP_BASE_URL') ?? 'http://localhost/hris-system'), '/');
$hris_rfid_endpoint = $appBaseUrl . '/api/rfid/tap.php';
$hris_rfid_device_code = (string)(legacyEnvValue('HRIS_RFID_BRIDGE_DEVICE_CODE') ?? 'SCANNER-ATI-HQ-01');
$hris_rfid_device_token = (string)(legacyEnvValue('HRIS_RFID_BRIDGE_DEVICE_TOKEN') ?? 'ATI-HRIS-RFID-2026-DEVICE-01-9f3d2f6c');

$headers = [
    "apikey: $api_key",
    "Authorization: Bearer $api_key",
    'Content-Type: application/json',
];

?>