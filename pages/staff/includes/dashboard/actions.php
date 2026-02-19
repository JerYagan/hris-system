<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

requireStaffPostWithCsrf($_POST['csrf_token'] ?? null);

redirectWithState('error', 'Unknown dashboard action.', 'dashboard.php');
