<?php

require_once __DIR__ . '/common.php';

if (!function_exists('staffSupabaseBootstrap')) {
    function staffSupabaseBootstrap(): array
    {
        loadEnvFile(dirname(__DIR__, 4) . '/.env');

        $url = rtrim((string)($_ENV['SUPABASE_URL'] ?? $_SERVER['SUPABASE_URL'] ?? ''), '/');
        $serviceRoleKey = (string)($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? $_SERVER['SUPABASE_SERVICE_ROLE_KEY'] ?? '');

        $headers = [
            'apikey: ' . $serviceRoleKey,
            'Authorization: Bearer ' . $serviceRoleKey,
            'Content-Type: application/json',
        ];

        return [
            'url' => $url,
            'service_role_key' => $serviceRoleKey,
            'headers' => $headers,
        ];
    }
}
