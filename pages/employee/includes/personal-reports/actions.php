<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

redirectWithState('success', 'Reports can now be downloaded directly from My Reports.', 'personal-reports.php');
