<?php

require_once __DIR__ . '/../auth-guard.php';
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/supabase.php';

if (!function_exists('applicantBackendContext')) {
    function applicantBackendContext(): array
    {
        $supabase = applicantSupabaseBootstrap();

        return [
            'supabase_url' => (string)($supabase['url'] ?? ''),
            'service_role_key' => (string)($supabase['service_role_key'] ?? ''),
            'headers' => (array)($supabase['headers'] ?? []),
            'applicant_user_id' => (string)($_SESSION['user']['id'] ?? ''),
        ];
    }
}
