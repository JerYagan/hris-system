<?php
require_once dirname(__DIR__, 2) . '/auth/includes/auth-support.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    authStartSession();
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
    header('Location: ../auth/login.php');
    exit;
}
