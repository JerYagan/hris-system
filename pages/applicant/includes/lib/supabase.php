<?php

require_once __DIR__ . '/common.php';

if (!function_exists('applicantSupabaseBootstrap')) {
    function applicantSupabaseBootstrap(): array
    {
        return systemPrivilegedSupabaseConfig();
    }
}
