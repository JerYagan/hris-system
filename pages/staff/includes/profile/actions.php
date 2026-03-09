<?php

require_once dirname(__DIR__, 3) . '/admin/includes/notifications/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$action = cleanText($_POST['form_action'] ?? null) ?? '';

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.');
}

if (!in_array($action, ['update_staff_profile', 'upload_profile_photo', 'request_password_change_code', 'confirm_password_change_code', 'cancel_password_change_code'], true)) {
    redirectWithState('error', 'Unknown profile action.');
}

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
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=eq.' . rawurlencode($staffUserId) . '&limit=1',
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

    $_SESSION['staff_profile_password_change'] = [
        'code_hash' => hash('sha256', $verificationCode),
        'new_password' => $newPassword,
        'email' => $email,
        'expires_at' => $expiresAt,
        'attempts' => 0,
    ];

    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
    $safeExpiry = htmlspecialchars(formatUnixTimestampForPhilippines($expiresAt, 'M d, Y h:i A') . ' PST', ENT_QUOTES, 'UTF-8');
    $emailBody = '<p>Your DA-ATI HRIS password change verification code is:</p>'
        . '<p style="font-size:24px;font-weight:700;letter-spacing:2px;">' . $safeCode . '</p>'
        . '<p>This code expires on <strong>' . $safeExpiry . '</strong>.</p>'
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
        unset($_SESSION['staff_profile_password_change']);
        redirectWithState('error', 'Unable to send verification code email. Please try again.');
    }

    redirectWithState('success', 'Verification code sent to your email. Enter the code to complete password change.');
}

if ($action === 'cancel_password_change_code') {
    unset($_SESSION['staff_profile_password_change']);
    redirectWithState('success', 'Pending password change verification was cancelled.');
}

if ($action === 'confirm_password_change_code') {
    $enteredCode = trim((string)($_POST['verification_code'] ?? ''));
    $pending = (array)($_SESSION['staff_profile_password_change'] ?? []);

    if ($enteredCode === '' || !preg_match('/^[0-9]{6}$/', $enteredCode)) {
        redirectWithState('error', 'Enter a valid 6-digit verification code.');
    }

    if (empty($pending)) {
        redirectWithState('error', 'No pending password change request. Request a new verification code.');
    }

    $expiresAt = (int)($pending['expires_at'] ?? 0);
    if ($expiresAt <= time()) {
        unset($_SESSION['staff_profile_password_change']);
        redirectWithState('error', 'Verification code expired. Request a new code.');
    }

    $attempts = (int)($pending['attempts'] ?? 0);
    $expectedHash = (string)($pending['code_hash'] ?? '');
    if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $enteredCode))) {
        $attempts++;
        if ($attempts >= 5) {
            unset($_SESSION['staff_profile_password_change']);
            redirectWithState('error', 'Too many invalid verification attempts. Request a new code.');
        }

        $pending['attempts'] = $attempts;
        $_SESSION['staff_profile_password_change'] = $pending;
        redirectWithState('error', 'Invalid verification code.');
    }

    $newPassword = (string)($pending['new_password'] ?? '');
    if ($newPassword === '') {
        unset($_SESSION['staff_profile_password_change']);
        redirectWithState('error', 'Pending password payload is invalid. Request a new code.');
    }

    $resetResponse = apiRequest(
        'PUT',
        $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($staffUserId),
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
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($staffUserId),
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
            'actor_user_id' => $staffUserId,
            'module_name' => 'profile',
            'entity_name' => 'user_accounts',
            'entity_id' => $staffUserId,
            'action_name' => 'change_password_with_email_verification',
            'old_data' => null,
            'new_data' => ['password_changed' => true],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['staff_profile_password_change']);
    redirectWithState('success', 'Password changed successfully.');
}

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

    $beforePhotoResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,profile_photo_url&user_id=eq.' . rawurlencode($staffUserId) . '&limit=1',
        $headers
    );

    if (!isSuccessful($beforePhotoResponse) || empty((array)($beforePhotoResponse['data'] ?? []))) {
        redirectWithState('error', 'Unable to load profile before photo update.');
    }

    $beforePhotoRow = (array)$beforePhotoResponse['data'][0];
    $personId = cleanText($beforePhotoRow['id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Profile person record is invalid.');
    }

    $oldPath = cleanText($beforePhotoRow['profile_photo_url'] ?? null);

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
            'actor_user_id' => $staffUserId,
            'module_name' => 'profile',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'upload_profile_photo',
            'old_data' => ['profile_photo_url' => $oldPath],
            'new_data' => ['profile_photo_url' => $relativePath],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['staff_topnav_cache']);
    redirectWithState('success', 'Profile photo updated successfully.');
}

$firstName = trim((string)(cleanText($_POST['first_name'] ?? null) ?? ''));
$middleName = trim((string)(cleanText($_POST['middle_name'] ?? null) ?? ''));
$surname = trim((string)(cleanText($_POST['surname'] ?? null) ?? ''));
$nameExtension = trim((string)(cleanText($_POST['name_extension'] ?? null) ?? ''));
$personalEmail = strtolower(trim((string)(cleanText($_POST['personal_email'] ?? null) ?? '')));
$mobileNo = trim((string)(cleanText($_POST['mobile_no'] ?? null) ?? ''));
$username = trim((string)(cleanText($_POST['username'] ?? null) ?? ''));

if ($firstName === '' || $surname === '') {
    redirectWithState('error', 'First name and surname are required.');
}

if ($personalEmail !== '' && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid personal email.');
}

if ($mobileNo !== '' && !preg_match('/^\+?[0-9][0-9\s-]{6,19}$/', $mobileNo)) {
    redirectWithState('error', 'Please provide a valid contact number.');
}

$accountLookup = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,username,mobile_no&id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$accountRow = isSuccessful($accountLookup) ? ($accountLookup['data'][0] ?? null) : null;
if (!is_array($accountRow)) {
    redirectWithState('error', 'Account record not found.');
}

$personLookup = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,personal_email,mobile_no&user_id=eq.' . rawurlencode($staffUserId) . '&limit=1',
    $headers
);
$personRow = isSuccessful($personLookup) ? ($personLookup['data'][0] ?? null) : null;

$personPayload = [
    'first_name' => $firstName,
    'middle_name' => $middleName !== '' ? $middleName : null,
    'surname' => $surname,
    'name_extension' => $nameExtension !== '' ? $nameExtension : null,
    'personal_email' => $personalEmail !== '' ? $personalEmail : null,
    'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
    'updated_at' => gmdate('c'),
];

$personId = null;
if (is_array($personRow) && isValidUuid((string)($personRow['id'] ?? ''))) {
    $personId = (string)$personRow['id'];
    $personPatch = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal']),
        $personPayload
    );

    if (!isSuccessful($personPatch)) {
        redirectWithState('error', 'Failed to update personal profile details.');
    }
} else {
    $insertPayload = [
        'user_id' => $staffUserId,
        'first_name' => $firstName,
        'middle_name' => $middleName !== '' ? $middleName : null,
        'surname' => $surname,
        'name_extension' => $nameExtension !== '' ? $nameExtension : null,
        'personal_email' => $personalEmail !== '' ? $personalEmail : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'agency_employee_no' => 'AUTO-' . strtoupper(substr(str_replace('-', '', $staffUserId), 0, 8)),
        'citizenship' => 'Filipino',
    ];

    $personInsert = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/people',
        array_merge($headers, ['Prefer: return=representation']),
        [$insertPayload]
    );

    if (!isSuccessful($personInsert)) {
        redirectWithState('error', 'Failed to initialize profile details.');
    }

    $personId = cleanText($personInsert['data'][0]['id'] ?? null);
}

$accountPatch = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($staffUserId),
    array_merge($headers, ['Prefer: return=minimal']),
    [
        'username' => $username !== '' ? $username : null,
        'mobile_no' => $mobileNo !== '' ? $mobileNo : null,
        'updated_at' => gmdate('c'),
    ]
);

if (!isSuccessful($accountPatch)) {
    redirectWithState('error', 'Profile was updated, but failed to save account preferences.');
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $staffUserId,
        'module_name' => 'profile',
        'entity_name' => 'user_accounts',
        'entity_id' => $staffUserId,
        'action_name' => 'update_staff_profile',
        'old_data' => [
            'username' => cleanText($accountRow['username'] ?? null),
            'mobile_no' => cleanText($accountRow['mobile_no'] ?? null),
            'person_first_name' => cleanText($personRow['first_name'] ?? null),
            'person_middle_name' => cleanText($personRow['middle_name'] ?? null),
            'person_surname' => cleanText($personRow['surname'] ?? null),
            'person_name_extension' => cleanText($personRow['name_extension'] ?? null),
            'person_personal_email' => cleanText($personRow['personal_email'] ?? null),
            'person_mobile_no' => cleanText($personRow['mobile_no'] ?? null),
        ],
        'new_data' => [
            'username' => $username,
            'mobile_no' => $mobileNo,
            'person_id' => $personId,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'surname' => $surname,
            'name_extension' => $nameExtension,
            'personal_email' => $personalEmail,
        ],
        'ip_address' => clientIp(),
    ]]
);

redirectWithState('success', 'Profile details updated successfully.');