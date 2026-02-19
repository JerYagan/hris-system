<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('staffAllowedActorRoles')) {
    function staffAllowedActorRoles(): array
    {
        return ['staff', 'hr_officer', 'supervisor', 'admin'];
    }
}

$currentRole = strtolower((string)($_SESSION['user']['role_key'] ?? $_SESSION['user']['role'] ?? ''));
$allowedRoles = staffAllowedActorRoles();

if (!isset($_SESSION['user']) || !in_array($currentRole, $allowedRoles, true)) {
    header('Location: /hris-system/pages/auth/login.php');
    exit;
}
