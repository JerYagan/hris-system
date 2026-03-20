<?php

require_once __DIR__ . '/common.php';

if (!function_exists('employeeSupabaseBootstrap')) {
    function employeeSupabaseBootstrap(): array
    {
        return systemPrivilegedSupabaseConfig();
    }
}
