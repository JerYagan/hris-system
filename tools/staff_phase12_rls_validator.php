<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key === '') {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function request(string $method, string $url, array $headers): array
{
    $exec = static function (bool $verifySsl) use ($method, $url, $headers): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'raw' => (string)$raw,
            'status' => $status,
            'error' => $error,
        ];
    };

    $attempt = $exec(true);
    if (($attempt['status'] ?? 0) === 0 && str_contains(strtolower((string)($attempt['error'] ?? '')), 'ssl certificate')) {
        $attempt = $exec(false);
    }

    return [
        'raw' => (string)($attempt['raw'] ?? ''),
        'status' => (int)($attempt['status'] ?? 0),
        'error' => (string)($attempt['error'] ?? ''),
    ];
}

function printCheck(string $id, bool $pass, string $detail): void
{
    echo sprintf("[%s] %s - %s\n", $pass ? 'PASS' : 'FAIL', $id, $detail);
}

function normalizeSql(string $value): string
{
    return strtolower(str_replace(["\r", "\n", "\t"], ' ', $value));
}

$root = dirname(__DIR__);
$sqlFiles = [
    $root . '/SUPABASE_SCHEMA.sql',
    $root . '/RLS_PHASE2_EMPLOYEE_HARDENING.sql',
];

$combinedSql = '';
foreach ($sqlFiles as $file) {
    if (!is_file($file)) {
        printCheck('SQL_FILE_PRESENT', false, 'Missing SQL file: ' . basename($file));
        exit(2);
    }

    $content = file_get_contents($file);
    if ($content === false) {
        printCheck('SQL_FILE_READ', false, 'Unable to read SQL file: ' . basename($file));
        exit(2);
    }

    $combinedSql .= "\n" . $content;
}

$sql = normalizeSql($combinedSql);
$hasFailures = false;

$checks = [
    'RLS_NOTIFICATIONS_ENABLED' => 'alter table public.notifications enable row level security;',
    'RLS_GENERATED_REPORTS_ENABLED' => 'alter table public.generated_reports enable row level security;',
    'POLICY_NOTIFICATIONS_SELF_SELECT' => 'create policy notifications_self_select on public.notifications',
    'POLICY_NOTIFICATIONS_SELF_UPDATE' => 'create policy notifications_self_update on public.notifications',
    'POLICY_GENERATED_REPORTS_EMPLOYEE_SCOPE' => 'create policy generated_reports_employee_scope on public.generated_reports',
];

foreach ($checks as $id => $needle) {
    $pass = str_contains($sql, normalizeSql($needle));
    printCheck($id, $pass, $pass ? 'Found expected SQL clause.' : 'Missing expected SQL clause.');
    if (!$pass) {
        $hasFailures = true;
    }
}

loadEnv($root . '/.env');

$baseUrl = rtrim((string)($_ENV['SUPABASE_URL'] ?? ''), '/');
$anonKey = (string)($_ENV['SUPABASE_ANON_KEY'] ?? '');
$serviceKey = (string)($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '');

if ($baseUrl === '' || $anonKey === '' || $serviceKey === '') {
    printCheck('HTTP_PRECHECK', false, 'Missing SUPABASE_URL/SUPABASE_ANON_KEY/SUPABASE_SERVICE_ROLE_KEY in .env');
    exit(2);
}

$anonHeaders = [
    'apikey: ' . $anonKey,
    'Content-Type: application/json',
];

$serviceHeaders = [
    'apikey: ' . $serviceKey,
    'Authorization: Bearer ' . $serviceKey,
    'Content-Type: application/json',
];

$anonNotifications = request('GET', $baseUrl . '/rest/v1/notifications?select=id&limit=1', $anonHeaders);
$anonNotificationsDenied = in_array($anonNotifications['status'], [401, 403], true);
printCheck(
    'HTTP_ANON_NOTIFICATIONS_DENIED',
    $anonNotificationsDenied,
    'HTTP ' . $anonNotifications['status']
);
if (!$anonNotificationsDenied) {
    $hasFailures = true;
}

$anonReports = request('GET', $baseUrl . '/rest/v1/generated_reports?select=id&limit=1', $anonHeaders);
$anonReportsDenied = in_array($anonReports['status'], [401, 403], true);
printCheck(
    'HTTP_ANON_GENERATED_REPORTS_DENIED',
    $anonReportsDenied,
    'HTTP ' . $anonReports['status']
);
if (!$anonReportsDenied) {
    $hasFailures = true;
}

$serviceNotifications = request('GET', $baseUrl . '/rest/v1/notifications?select=id&limit=1', $serviceHeaders);
$serviceNotificationsAllowed = $serviceNotifications['status'] >= 200 && $serviceNotifications['status'] < 300;
printCheck(
    'HTTP_SERVICE_NOTIFICATIONS_ALLOWED',
    $serviceNotificationsAllowed,
    'HTTP ' . $serviceNotifications['status']
);
if (!$serviceNotificationsAllowed) {
    $hasFailures = true;
}

$serviceReports = request('GET', $baseUrl . '/rest/v1/generated_reports?select=id&limit=1', $serviceHeaders);
$serviceReportsAllowed = $serviceReports['status'] >= 200 && $serviceReports['status'] < 300;
printCheck(
    'HTTP_SERVICE_GENERATED_REPORTS_ALLOWED',
    $serviceReportsAllowed,
    'HTTP ' . $serviceReports['status']
);
if (!$serviceReportsAllowed) {
    $hasFailures = true;
}

exit($hasFailures ? 1 : 0);
