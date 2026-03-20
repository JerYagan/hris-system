<?php

require_once __DIR__ . '/common.php';

if (!function_exists('adminSupabaseBootstrap')) {
    function adminSupabaseBootstrap(): array
    {
        return systemPrivilegedSupabaseConfig();
    }
}
