<?php
require_once dirname(__DIR__, 2) . '/auth/includes/auth-support.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    authStartSession();
}

$currentRole = strtolower((string)($_SESSION['user']['role_key'] ?? $_SESSION['user']['role'] ?? ''));

if (!isset($_SESSION['user']) || $currentRole !== 'applicant') {
    header('Location: /hris-system/pages/auth/login.php');
    exit;
}
