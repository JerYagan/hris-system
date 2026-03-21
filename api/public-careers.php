<?php

require_once dirname(__DIR__) . '/pages/auth/includes/auth-support.php';
require_once dirname(__DIR__) . '/pages/shared/lib/system-helpers.php';

header('Content-Type: application/json; charset=UTF-8');

$supabase = systemPrivilegedSupabaseConfig();
$supabaseUrl = rtrim((string)($supabase['url'] ?? ''), '/');
$serviceRoleKey = trim((string)($supabase['service_role_key'] ?? ''));

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
$headers = (array)($supabase['headers'] ?? []);

$select = 'id,title,description,open_date,close_date,plantilla_item_no,office:offices!inner(office_name,is_active),position:job_positions!inner(position_title,employment_classification,is_active)';
$response = authHttpJsonRequest(
    'GET',
    $supabaseUrl
        . '/rest/v1/job_postings?select=' . rawurlencode($select)
        . '&posting_status=eq.published'
        . '&office.is_active=eq.true'
        . '&position.is_active=eq.true'
        . '&open_date=lte.' . rawurlencode($today)
        . '&close_date=gte.' . rawurlencode($today)
        . '&order=close_date.asc'
        . '&limit=3',
    $headers
);

$items = [];
$requestSucceeded = (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300);
if ($requestSucceeded) {
    foreach ((array)($response['data'] ?? []) as $row) {
        $title = trim((string)($row['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $description = trim((string)($row['description'] ?? ''));
        if ($description === '') {
            $description = 'Please log in to view the full posting details and application requirements.';
        }

        $office = is_array($row['office'] ?? null) ? (array)$row['office'] : [];
        $position = is_array($row['position'] ?? null) ? (array)$row['position'] : [];

        $items[] = [
            'id' => (string)($row['id'] ?? ''),
            'title' => $title,
            'position_title' => trim((string)($position['position_title'] ?? '')),
            'office_name' => trim((string)($office['office_name'] ?? '')),
            'employment_type' => trim((string)($position['employment_classification'] ?? '')),
            'description' => $description,
            'open_date' => trim((string)($row['open_date'] ?? '')),
            'close_date' => trim((string)($row['close_date'] ?? '')),
            'plantilla_item_no' => trim((string)($row['plantilla_item_no'] ?? '')),
        ];
    }
}

echo json_encode([
    'success' => $requestSucceeded,
    'message' => $requestSucceeded ? '' : 'Unable to load live career postings right now.',
    'items' => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;