<?php

require_once dirname(__DIR__) . '/pages/auth/includes/auth-support.php';

header('Content-Type: application/json; charset=UTF-8');

$rootDir = dirname(__DIR__);
authLoadEnvFileIfPresent($rootDir . DIRECTORY_SEPARATOR . '.env');

$supabaseUrl = rtrim((string)(authEnvValue('SUPABASE_URL') ?? ''), '/');
$serviceRoleKey = authEnvValue('SUPABASE_SERVICE_ROLE_KEY');

if ($supabaseUrl === '' || !$serviceRoleKey) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Supabase credentials are missing.',
        'items' => [],
    ]);
    exit;
}

$today = date('Y-m-d');
$headers = [
    'Content-Type: application/json',
    'apikey: ' . $serviceRoleKey,
    'Authorization: Bearer ' . $serviceRoleKey,
];

$select = 'id,title,description,open_date,close_date,plantilla_item_no';
$response = authHttpJsonRequest(
    'GET',
    $supabaseUrl
        . '/rest/v1/job_postings?select=' . rawurlencode($select)
        . '&posting_status=eq.published'
        . '&open_date=lte.' . rawurlencode($today)
        . '&close_date=gte.' . rawurlencode($today)
        . '&order=close_date.asc'
        . '&limit=3',
    $headers
);

$items = [];
if (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300) {
    foreach ((array)($response['data'] ?? []) as $row) {
        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $description = trim((string)($row['description'] ?? ''));
        if ($description === '') {
            $description = 'Please log in to view the full posting details and application requirements.';
        }

        $items[] = [
            'id' => (string)($row['id'] ?? ''),
            'title' => $title,
            'description' => $description,
            'open_date' => trim((string)($row['open_date'] ?? '')),
            'close_date' => trim((string)($row['close_date'] ?? '')),
            'plantilla_item_no' => trim((string)($row['plantilla_item_no'] ?? '')),
        ];
    }
}

echo json_encode([
    'success' => true,
    'items' => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;