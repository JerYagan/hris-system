<?php
require_once __DIR__ . '/includes/auth-guard.php';

header('Location: dashboard.php', true, 302);
exit;
