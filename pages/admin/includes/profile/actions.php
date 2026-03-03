<?php

require_once __DIR__ . '/../notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = (string)($_POST['form_action'] ?? '');

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

$allowedActions = [
    'update_profile_details',
    'update_account_preferences',
    'upload_profile_photo',
    'request_password_change_code',
    'confirm_password_change_code',
    'cancel_password_change_code',
];

if (!in_array($action, $allowedActions, true)) {
    redirectWithState('error', 'Unknown profile action.');
}

$ensureAdminPerson = static function () use ($supabaseUrl, $headers, $adminUserId): array {
    $personLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,first_name,surname,profile_photo_url&user_id=eq.' . rawurlencode($adminUserId) . '&limit=1',
        $headers
    );

    if (isSuccessful($personLookup) && !empty((array)($personLookup['data'] ?? []))) {
        return (array)$personLookup['data'][0];
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=email&id=eq.' . rawurlencode($adminUserId) . '&limit=1',
        $headers
    );
    $email = strtolower(trim((string)($accountLookup['data'][0]['email'] ?? '')));

    $firstName = 'Admin';
    $surname = 'User';
    if ($email !== '' && str_contains($email, '@')) {
        $localPart = (string)explode('@', $email)[0];
        $tokens = preg_split('/[._\-\s]+/', $localPart) ?: [];
        if (!empty($tokens)) {
            $firstName = ucfirst(strtolower((string)($tokens[0] ?? 'Admin')));
            $surname = ucfirst(strtolower((string)($tokens[1] ?? 'User')));
        }
    }

    $insertPayload = [[
        'user_id' => $adminUserId,
        'first_name' => $firstName,
        'surname' => $surname,
        'personal_email' => $email !== '' ? $email : null,
        'agency_employee_no' => 'AUTO-' . strtoupper(substr(str_replace('-', '', $adminUserId), 0, 8)),
        'citizenship' => 'Filipino',
    ]];

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: return=representation']),
        $insertPayload
    );

    if (!isSuccessful($insertResponse) || empty((array)($insertResponse['data'] ?? []))) {
        return [];
    }

    return (array)$insertResponse['data'][0];
};

$verifyCurrentPassword = static function (string $email, string $currentPassword) use ($supabaseUrl): bool {
    loadEnvFile(dirname(__DIR__, 4) . '/.env');
    $anonKey = trim((string)($_ENV['SUPABASE_ANON_KEY'] ?? $_SERVER['SUPABASE_ANON_KEY'] ?? ''));
    if ($anonKey === '' || trim($email) === '' || $currentPassword === '') {
        return false;
    }

    $ch = curl_init($supabaseUrl . '/auth/v1/token?grant_type=password');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $anonKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $currentPassword,
    ]));

    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status >= 200 && $status < 300;
};

$validateStrongPassword = static function (string $password): ?string {
    if (strlen($password) < 10) {
        return 'New password must be at least 10 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'New password must include at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'New password must include at least one lowercase letter.';
    }
    if (!preg_match('/\d/', $password)) {
        return 'New password must include at least one number.';
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return 'New password must include at least one special character.';
    }

    return null;
};

if ($action === 'upload_profile_photo') {
    $photoFile = $_FILES['profile_photo'] ?? null;
    if (!is_array($photoFile) || (int)($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please choose a valid profile photo file.');
    }

    $tmpName = (string)($photoFile['tmp_name'] ?? '');
    $sizeBytes = (int)($photoFile['size'] ?? 0);
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        redirectWithState('error', 'Uploaded profile photo is invalid.');
    }

    if ($sizeBytes <= 0 || $sizeBytes > (3 * 1024 * 1024)) {
        redirectWithState('error', 'Profile photo must be less than or equal to 3 MB.');
    }

    $mimeType = (string)(mime_content_type($tmpName) ?: '');
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $extension = $allowedMimeToExt[$mimeType] ?? null;
    if ($extension === null) {
        redirectWithState('error', 'Only JPG, PNG, and WEBP profile photos are allowed.');
    }

    $personRow = $ensureAdminPerson();
    $personId = cleanText($personRow['id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Unable to resolve your profile record for photo upload.');
    }

    $oldPath = cleanText($personRow['profile_photo_url'] ?? null);

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $profileDir = $storageRoot . '/profile-photos/' . $personId;
    if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
        redirectWithState('error', 'Unable to prepare profile photo storage.');
    }

    $fileName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $relativePath = 'profile-photos/' . $personId . '/' . $fileName;
    $absolutePath = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        redirectWithState('error', 'Failed to save uploaded profile photo.');
    }

    $photoUpdateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'profile_photo_url' => $relativePath,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($photoUpdateResponse)) {
        @unlink($absolutePath);
        redirectWithState('error', 'Unable to update profile photo reference.');
    }

    if ($oldPath !== null && str_starts_with($oldPath, 'profile-photos/')) {
        $oldAbsolute = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $oldPath);
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'upload_profile_photo',
            'old_data' => ['profile_photo_url' => $oldPath],
            'new_data' => ['profile_photo_url' => $relativePath],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['admin_topnav_cache']);

    redirectWithState('success', 'Profile photo updated successfully.');
}

if ($action === 'request_password_change_code') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        redirectWithState('error', 'Current, new, and confirm password fields are required.');
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithState('error', 'New password and confirmation do not match.');
    }

    if ($newPassword === $currentPassword) {
        redirectWithState('error', 'New password must be different from the current password.');
    }

    $strengthError = $validateStrongPassword($newPassword);
    if ($strengthError !== null) {
        redirectWithState('error', $strengthError);
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=eq.' . rawurlencode($adminUserId) . '&limit=1',
        $headers
    );
    $accountRow = isSuccessful($accountLookup) ? (array)($accountLookup['data'][0] ?? []) : [];
    $email = strtolower(trim((string)($accountRow['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'A valid account email is required for password verification.');
    }

    if (!$verifyCurrentPassword($email, $currentPassword)) {
        redirectWithState('error', 'Current password is incorrect.');
    }

    $smtpConfig = [
        'host' => (string)($_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? '')),
        'port' => (int)($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? 587)),
        'username' => (string)($_ENV['SMTP_USERNAME'] ?? ($_SERVER['SMTP_USERNAME'] ?? '')),
        'password' => (string)($_ENV['SMTP_PASSWORD'] ?? ($_SERVER['SMTP_PASSWORD'] ?? '')),
        'encryption' => (string)($_ENV['SMTP_ENCRYPTION'] ?? ($_SERVER['SMTP_ENCRYPTION'] ?? 'tls')),
        'auth' => (string)($_ENV['SMTP_AUTH'] ?? ($_SERVER['SMTP_AUTH'] ?? '1')),
    ];
    $mailFrom = (string)($_ENV['MAIL_FROM'] ?? ($_SERVER['MAIL_FROM'] ?? ''));
    $mailFromName = (string)($_ENV['MAIL_FROM_NAME'] ?? ($_SERVER['MAIL_FROM_NAME'] ?? 'DA-ATI HRIS'));

    $resolvedMail = resolveSmtpMailConfig($supabaseUrl, $headers, $smtpConfig, $mailFrom, $mailFromName);
    $smtpConfig = (array)($resolvedMail['smtp'] ?? $smtpConfig);
    $mailFrom = (string)($resolvedMail['from'] ?? $mailFrom);
    $mailFromName = (string)($resolvedMail['from_name'] ?? $mailFromName);

    if (!smtpConfigIsReady($smtpConfig, $mailFrom)) {
        redirectWithState('error', 'Email verification is required but SMTP is not configured.');
    }

    $verificationCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = time() + (10 * 60);

    $_SESSION['admin_profile_password_change'] = [
        'code_hash' => hash('sha256', $verificationCode),
        'new_password' => $newPassword,
        'email' => $email,
        'expires_at' => $expiresAt,
        'attempts' => 0,
    ];

    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
    $safeExpiry = htmlspecialchars(date('M d, Y h:i A', $expiresAt), ENT_QUOTES, 'UTF-8');
    $emailBody = '<p>Your DA-ATI HRIS password change verification code is:</p>'
        . '<p style="font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</p>'
        . '<p>This code expires on <strong>' . $safeExpiry . '</strong> (server time).</p>'
        . '<p>If you did not request a password change for ' . $safeEmail . ', ignore this email and contact support.</p>';

    $mailResponse = smtpSendTransactionalEmail(
        $smtpConfig,
        $mailFrom,
        $mailFromName,
        $email,
        $email,
        'DA-ATI HRIS Password Change Verification Code',
        $emailBody
    );

    if (!isSuccessful($mailResponse)) {
        unset($_SESSION['admin_profile_password_change']);
        redirectWithState('error', 'Unable to send verification code email. Please try again.');
    }

    redirectWithState('success', 'Verification code sent to your email. Enter the code to complete password change.');
}

if ($action === 'cancel_password_change_code') {
    unset($_SESSION['admin_profile_password_change']);
    redirectWithState('success', 'Pending password change verification was cancelled.');
}

if ($action === 'confirm_password_change_code') {
    $enteredCode = trim((string)($_POST['verification_code'] ?? ''));
    $pending = (array)($_SESSION['admin_profile_password_change'] ?? []);

    if ($enteredCode === '' || !preg_match('/^[0-9]{6}$/', $enteredCode)) {
        redirectWithState('error', 'Enter a valid 6-digit verification code.');
    }

    if (empty($pending)) {
        redirectWithState('error', 'No pending password change request. Request a new verification code.');
    }

    $expiresAt = (int)($pending['expires_at'] ?? 0);
    if ($expiresAt <= time()) {
        unset($_SESSION['admin_profile_password_change']);
        redirectWithState('error', 'Verification code expired. Request a new code.');
    }

    $attempts = (int)($pending['attempts'] ?? 0);
    $expectedHash = (string)($pending['code_hash'] ?? '');
    if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $enteredCode))) {
        $attempts++;
        if ($attempts >= 5) {
            unset($_SESSION['admin_profile_password_change']);
            redirectWithState('error', 'Too many invalid verification attempts. Request a new code.');
        }

        $pending['attempts'] = $attempts;
        $_SESSION['admin_profile_password_change'] = $pending;
        redirectWithState('error', 'Invalid verification code.');
    }

    $newPassword = (string)($pending['new_password'] ?? '');
    if ($newPassword === '') {
        unset($_SESSION['admin_profile_password_change']);
        redirectWithState('error', 'Pending password payload is invalid. Request a new code.');
    }

    $resetResponse = apiRequest(
        'PUT',
        $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($adminUserId),
        $headers,
        [
            'password' => $newPassword,
            'email_confirm' => true,
        ]
    );

    if (!isSuccessful($resetResponse)) {
        redirectWithState('error', 'Failed to update password. Please try again.');
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($adminUserId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'must_change_password' => false,
            'failed_login_count' => 0,
            'lockout_until' => null,
            'updated_at' => gmdate('c'),
        ]
    );

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'user_accounts',
            'entity_id' => $adminUserId,
            'action_name' => 'change_password_with_email_verification',
            'old_data' => null,
            'new_data' => ['password_changed' => true],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['admin_profile_password_change']);
    redirectWithState('success', 'Password changed successfully.');
}

if ($action === 'update_profile_details') {
    $firstName = cleanText($_POST['first_name'] ?? null) ?? '';
    $middleName = cleanText($_POST['middle_name'] ?? null);
    $surname = cleanText($_POST['surname'] ?? null) ?? '';
    $nameExtension = cleanText($_POST['name_extension'] ?? null);
    $mobileNo = cleanText($_POST['mobile_no'] ?? null);
    $personalEmail = cleanText($_POST['personal_email'] ?? null);

    if ($firstName === '' || $surname === '') {
        redirectWithState('error', 'First name and surname are required.');
    }

    $personLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,mobile_no,personal_email&user_id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $personRow = $personLookup['data'][0] ?? null;

    $newPayload = [
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'surname' => $surname,
        'name_extension' => $nameExtension,
        'mobile_no' => $mobileNo,
        'personal_email' => $personalEmail,
    ];

    if (is_array($personRow) && !empty($personRow['id'])) {
        $personId = (string)$personRow['id'];

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/people?id=eq.' . $personId,
            array_merge($headers, ['Prefer: return=minimal']),
            $newPayload
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update profile details.');
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId,
                'module_name' => 'profile',
                'entity_name' => 'people',
                'entity_id' => $personId,
                'action_name' => 'update_profile_details',
                'old_data' => [
                    'first_name' => (string)($personRow['first_name'] ?? ''),
                    'middle_name' => $personRow['middle_name'] ?? null,
                    'surname' => (string)($personRow['surname'] ?? ''),
                    'name_extension' => $personRow['name_extension'] ?? null,
                    'mobile_no' => $personRow['mobile_no'] ?? null,
                    'personal_email' => $personRow['personal_email'] ?? null,
                ],
                'new_data' => $newPayload,
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Profile details updated successfully.');
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=email&id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $accountRow = $accountLookup['data'][0] ?? [];

    $insertPayload = array_merge($newPayload, [
        'user_id' => $adminUserId,
        'agency_employee_no' => 'AUTO-' . strtoupper(substr(str_replace('-', '', $adminUserId), 0, 8)),
        'citizenship' => 'Filipino',
        'telephone_no' => null,
        'date_of_birth' => null,
        'place_of_birth' => null,
        'sex_at_birth' => null,
        'civil_status' => null,
    ]);

    if (($insertPayload['personal_email'] ?? null) === null) {
        $insertPayload['personal_email'] = cleanText($accountRow['email'] ?? null);
    }

    $insertResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: return=representation']),
        [$insertPayload]
    );

    if (!isSuccessful($insertResponse)) {
        redirectWithState('error', 'Failed to initialize profile details.');
    }

    $createdPerson = $insertResponse['data'][0] ?? [];
    $createdPersonId = (string)($createdPerson['id'] ?? '');

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'people',
            'entity_id' => $createdPersonId !== '' ? $createdPersonId : null,
            'action_name' => 'create_profile_details',
            'old_data' => null,
            'new_data' => $insertPayload,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Profile details created successfully.');
}

if ($action === 'update_account_preferences') {
    $username = cleanText($_POST['username'] ?? null);
    $mobileNo = cleanText($_POST['mobile_no'] ?? null);

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,username,mobile_no&id=eq.' . $adminUserId . '&limit=1',
        $headers
    );

    $accountRow = $accountLookup['data'][0] ?? null;
    if (!is_array($accountRow)) {
        redirectWithState('error', 'Account record not found.');
    }

    $patchResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . $adminUserId,
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'username' => $username,
            'mobile_no' => $mobileNo,
        ]
    );

    if (!isSuccessful($patchResponse)) {
        redirectWithState('error', 'Failed to update account preferences.');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'profile',
            'entity_name' => 'user_accounts',
            'entity_id' => $adminUserId,
            'action_name' => 'update_account_preferences',
            'old_data' => [
                'username' => $accountRow['username'] ?? null,
                'mobile_no' => $accountRow['mobile_no'] ?? null,
            ],
            'new_data' => [
                'username' => $username,
                'mobile_no' => $mobileNo,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Account preferences updated successfully.');
}
