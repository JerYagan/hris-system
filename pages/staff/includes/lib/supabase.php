<?php

require_once __DIR__ . '/common.php';

if (!function_exists('staffSupabaseBootstrap')) {
    function staffSupabaseBootstrap(): array
    {
        return systemPrivilegedSupabaseConfig();
    }
}
