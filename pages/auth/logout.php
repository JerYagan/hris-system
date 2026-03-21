<?php

require_once __DIR__ . '/includes/auth-support.php';

authStartSession();

$_SESSION = [];
authSyncRememberMeCookie(false);
authSyncPersistentLoginCookie(false);

if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => (string)($params['path'] ?? '/'),
    'domain' => (string)($params['domain'] ?? ''),
    'secure' => (bool)($params['secure'] ?? false),
    'httponly' => (bool)($params['httponly'] ?? true),
    'samesite' => (string)($params['samesite'] ?? 'Lax'),
  ]);
}

session_destroy();

header('Location: login.php?logout=1');
exit;
