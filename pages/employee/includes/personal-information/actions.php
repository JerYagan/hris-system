<?php

require_once dirname(__DIR__, 3) . '/admin/includes/notifications/email.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!(bool)($employeeContextResolved ?? false)) {
    redirectWithState('error', (string)($employeeContextError ?? 'Employee context could not be resolved.'), 'personal-information.php');
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'personal-information.php');
}

$action = strtolower((string)cleanText($_POST['action'] ?? ''));
if (!in_array($action, ['update_profile', 'upload_profile_photo', 'submit_spouse_request', 'request_password_change_code', 'confirm_password_change_code', 'cancel_password_change_code'], true)) {
    redirectWithState('error', 'Unsupported personal information action.', 'personal-information.php');
}

if ($action === 'upload_profile_photo') {
    $photoFile = $_FILES['profile_photo'] ?? null;
    if (!is_array($photoFile) || (int)($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please choose a valid profile photo file.', 'personal-information.php');
    }

    $tmpName = (string)($photoFile['tmp_name'] ?? '');
    $sizeBytes = (int)($photoFile['size'] ?? 0);
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        redirectWithState('error', 'Uploaded profile photo is invalid.', 'personal-information.php');
    }

    if ($sizeBytes <= 0 || $sizeBytes > (3 * 1024 * 1024)) {
        redirectWithState('error', 'Profile photo must be less than or equal to 3 MB.', 'personal-information.php');
    }

    $mimeType = (string)(mime_content_type($tmpName) ?: '');
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $extension = $allowedMimeToExt[$mimeType] ?? null;
    if ($extension === null) {
        redirectWithState('error', 'Only JPG, PNG, and WEBP profile photos are allowed.', 'personal-information.php');
    }

    $beforePhotoResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,profile_photo_url&id=eq.' . rawurlencode((string)$employeePersonId) . '&limit=1',
        $headers
    );

    if (!isSuccessful($beforePhotoResponse) || empty((array)($beforePhotoResponse['data'] ?? []))) {
        redirectWithState('error', 'Unable to load profile before photo update.', 'personal-information.php');
    }

    $beforePhotoRow = (array)$beforePhotoResponse['data'][0];
    $oldPath = cleanText($beforePhotoRow['profile_photo_url'] ?? null);

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $profileDir = $storageRoot . '/profile-photos/' . $employeePersonId;
    if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
        redirectWithState('error', 'Unable to prepare profile photo storage.', 'personal-information.php');
    }

    $fileName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $relativePath = 'profile-photos/' . $employeePersonId . '/' . $fileName;
    $absolutePath = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        redirectWithState('error', 'Failed to save uploaded profile photo.', 'personal-information.php');
    }

    $photoUpdateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode((string)$employeePersonId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'profile_photo_url' => $relativePath,
            'updated_at' => gmdate('c'),
        ]
    );

    if (!isSuccessful($photoUpdateResponse)) {
        @unlink($absolutePath);
        redirectWithState('error', 'Unable to update profile photo reference.', 'personal-information.php');
    }

    $oldRelativePath = null;
    if ($oldPath !== null && $oldPath !== '') {
        $normalizedOldPath = ltrim($oldPath, '/');
        $storagePrefix = 'hris-system/storage/document/';
        if (str_starts_with($normalizedOldPath, $storagePrefix)) {
            $oldRelativePath = substr($normalizedOldPath, strlen($storagePrefix));
        } elseif (str_starts_with($oldPath, 'profile-photos/')) {
            $oldRelativePath = $oldPath;
        }
    }

    if ($oldRelativePath !== null && $oldRelativePath !== '') {
        $oldAbsolute = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $oldRelativePath);
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'personal_profile',
            'entity_id' => $employeePersonId,
            'action_name' => 'upload_profile_photo',
            'old_data' => ['profile_photo_url' => $oldPath],
            'new_data' => ['profile_photo_url' => $relativePath],
        ]]
    );

    unset($_SESSION['employee_topnav_cache']);
    redirectWithState('success', 'Profile photo updated successfully.', 'personal-information.php');
}

if ($action === 'submit_spouse_request') {
    $toNullable = static function (mixed $value, int $maxLength = 255): ?string {
        $text = cleanText($value);
        if ($text === null) {
            return null;
        }

        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength);
        }

        return $text;
    };

    $spouseSurname = $toNullable($_POST['spouse_surname'] ?? null, 120);
    $spouseFirstName = $toNullable($_POST['spouse_first_name'] ?? null, 120);
    $spouseMiddleName = $toNullable($_POST['spouse_middle_name'] ?? null, 120);
    $spouseNameExtension = $toNullable($_POST['spouse_name_extension'] ?? null, 30);
    $spouseOccupation = $toNullable($_POST['spouse_occupation'] ?? null, 160);
    $spouseEmployerBusinessName = $toNullable($_POST['spouse_employer_business_name'] ?? null, 180);
    $spouseBusinessAddress = $toNullable($_POST['spouse_business_address'] ?? null, 200);
    $spouseTelephoneNo = $toNullable($_POST['spouse_telephone_no'] ?? null, 30);
    $requestNotes = $toNullable($_POST['request_notes'] ?? null, 300);

    if ($spouseSurname === null || $spouseFirstName === null) {
        redirectWithState('error', 'Spouse surname and first name are required for request submission.', 'personal-information.php');
    }

    $supportingFile = $_FILES['spouse_supporting_document'] ?? null;
    if (!is_array($supportingFile) || (int)($supportingFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please upload a supporting document for the spouse request.', 'personal-information.php');
    }

    $tmpName = (string)($supportingFile['tmp_name'] ?? '');
    $sizeBytes = (int)($supportingFile['size'] ?? 0);
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        redirectWithState('error', 'Uploaded supporting document is invalid.', 'personal-information.php');
    }

    if ($sizeBytes <= 0 || $sizeBytes > (10 * 1024 * 1024)) {
        redirectWithState('error', 'Supporting document must be less than or equal to 10 MB.', 'personal-information.php');
    }

    $originalName = (string)($supportingFile['name'] ?? 'supporting-document');
    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if ($extension === '' || !in_array($extension, $allowedExt, true)) {
        redirectWithState('error', 'Supporting document must be PDF, JPG, PNG, DOC, or DOCX.', 'personal-information.php');
    }

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $requestDir = $storageRoot . '/spouse-requests/' . $employeePersonId;
    if (!is_dir($requestDir) && !mkdir($requestDir, 0775, true) && !is_dir($requestDir)) {
        redirectWithState('error', 'Unable to prepare storage for supporting documents.', 'personal-information.php');
    }

    $fileName = 'spouse_request_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $relativePath = 'spouse-requests/' . $employeePersonId . '/' . $fileName;
    $absolutePath = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        redirectWithState('error', 'Failed to save supporting document.', 'personal-information.php');
    }

    $requestPayload = [
        'status' => 'pending_admin_approval',
        'spouse_surname' => $spouseSurname,
        'spouse_first_name' => $spouseFirstName,
        'spouse_middle_name' => $spouseMiddleName,
        'spouse_name_extension' => $spouseNameExtension,
        'spouse_occupation' => $spouseOccupation,
        'spouse_employer_business_name' => $spouseEmployerBusinessName,
        'spouse_business_address' => $spouseBusinessAddress,
        'spouse_telephone_no' => $spouseTelephoneNo,
        'request_notes' => $requestNotes,
        'supporting_document_name' => $originalName,
        'supporting_document_path' => $relativePath,
        'supporting_document_size' => $sizeBytes,
    ];

    $requestLogResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        $headers,
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'person_family_spouses_request',
            'entity_id' => $employeePersonId,
            'action_name' => 'submit_spouse_addition_request',
            'old_data' => null,
            'new_data' => $requestPayload,
        ]]
    );

    if (!isSuccessful($requestLogResponse)) {
        @unlink($absolutePath);
        redirectWithState('error', 'Unable to submit spouse request right now.', 'personal-information.php');
    }

    $adminRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.admin&limit=1',
        $headers
    );

    $adminRoleId = null;
    if (isSuccessful($adminRoleResponse) && !empty((array)($adminRoleResponse['data'] ?? []))) {
        $adminRoleId = cleanText($adminRoleResponse['data'][0]['id'] ?? null);
    }

    if ($adminRoleId !== null) {
        $adminAssignmentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=user_id'
            . '&role_id=eq.' . rawurlencode($adminRoleId)
            . '&expires_at=is.null&limit=200',
            $headers
        );

        if (isSuccessful($adminAssignmentsResponse)) {
            $notificationPayload = [];
            $recipientSet = [];
            foreach ((array)($adminAssignmentsResponse['data'] ?? []) as $assignmentRaw) {
                $assignment = (array)$assignmentRaw;
                $recipientId = cleanText($assignment['user_id'] ?? null);
                if ($recipientId === null || isset($recipientSet[$recipientId])) {
                    continue;
                }

                $recipientSet[$recipientId] = true;
                $notificationPayload[] = [
                    'recipient_user_id' => $recipientId,
                    'category' => 'personal_information',
                    'title' => 'Pending spouse entry request',
                    'body' => 'An employee submitted a spouse entry request requiring admin approval.',
                    'link_url' => '/hris-system/pages/admin/personal-information.php',
                ];
            }

            if (!empty($notificationPayload)) {
                apiRequest('POST', $supabaseUrl . '/rest/v1/notifications', $headers, $notificationPayload);
            }
        }
    }

    redirectWithState('success', 'Spouse entry request submitted and sent for admin approval.', 'personal-information.php');
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
        redirectWithState('error', 'Current, new, and confirm password fields are required.', 'personal-information.php');
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithState('error', 'New password and confirmation do not match.', 'personal-information.php');
    }

    if ($newPassword === $currentPassword) {
        redirectWithState('error', 'New password must be different from the current password.', 'personal-information.php');
    }

    $strengthError = $validateStrongPassword($newPassword);
    if ($strengthError !== null) {
        redirectWithState('error', $strengthError, 'personal-information.php');
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=eq.' . rawurlencode($employeeUserId) . '&limit=1',
        $headers
    );
    $accountRow = isSuccessful($accountLookup) ? (array)($accountLookup['data'][0] ?? []) : [];
    $email = strtolower(trim((string)($accountRow['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'A valid account email is required for password verification.', 'personal-information.php');
    }

    if (!$verifyCurrentPassword($email, $currentPassword)) {
        redirectWithState('error', 'Current password is incorrect.', 'personal-information.php');
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
        redirectWithState('error', 'Email verification is required but SMTP is not configured.', 'personal-information.php');
    }

    $verificationCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = time() + (10 * 60);

    $_SESSION['employee_profile_password_change'] = [
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
        unset($_SESSION['employee_profile_password_change']);
        redirectWithState('error', 'Unable to send verification code email. Please try again.', 'personal-information.php');
    }

    redirectWithState('success', 'Verification code sent to your email. Enter the code to complete password change.', 'personal-information.php');
}

if ($action === 'cancel_password_change_code') {
    unset($_SESSION['employee_profile_password_change']);
    redirectWithState('success', 'Pending password change verification was cancelled.', 'personal-information.php');
}

if ($action === 'confirm_password_change_code') {
    $enteredCode = trim((string)($_POST['verification_code'] ?? ''));
    $pending = (array)($_SESSION['employee_profile_password_change'] ?? []);

    if ($enteredCode === '' || !preg_match('/^[0-9]{6}$/', $enteredCode)) {
        redirectWithState('error', 'Enter a valid 6-digit verification code.', 'personal-information.php');
    }

    if (empty($pending)) {
        redirectWithState('error', 'No pending password change request. Request a new verification code.', 'personal-information.php');
    }

    $expiresAt = (int)($pending['expires_at'] ?? 0);
    if ($expiresAt <= time()) {
        unset($_SESSION['employee_profile_password_change']);
        redirectWithState('error', 'Verification code expired. Request a new code.', 'personal-information.php');
    }

    $attempts = (int)($pending['attempts'] ?? 0);
    $expectedHash = (string)($pending['code_hash'] ?? '');
    if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $enteredCode))) {
        $attempts++;
        if ($attempts >= 5) {
            unset($_SESSION['employee_profile_password_change']);
            redirectWithState('error', 'Too many invalid verification attempts. Request a new code.', 'personal-information.php');
        }

        $pending['attempts'] = $attempts;
        $_SESSION['employee_profile_password_change'] = $pending;
        redirectWithState('error', 'Invalid verification code.', 'personal-information.php');
    }

    $newPassword = (string)($pending['new_password'] ?? '');
    if ($newPassword === '') {
        unset($_SESSION['employee_profile_password_change']);
        redirectWithState('error', 'Pending password payload is invalid. Request a new code.', 'personal-information.php');
    }

    $resetResponse = apiRequest(
        'PUT',
        $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($employeeUserId),
        $headers,
        [
            'password' => $newPassword,
            'email_confirm' => true,
        ]
    );

    if (!isSuccessful($resetResponse)) {
        redirectWithState('error', 'Failed to update password. Please try again.', 'personal-information.php');
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($employeeUserId),
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
            'actor_user_id' => $employeeUserId,
            'module_name' => 'employee',
            'entity_name' => 'user_accounts',
            'entity_id' => $employeeUserId,
            'action_name' => 'change_password_with_email_verification',
            'old_data' => null,
            'new_data' => ['password_changed' => true],
            'ip_address' => clientIp(),
        ]]
    );

    unset($_SESSION['employee_profile_password_change']);
    redirectWithState('success', 'Password changed successfully.', 'personal-information.php');
}

if ($action === 'update_profile') {
    $toNullableRequestValue = static function (mixed $value, int $maxLength = 255): ?string {
        $text = cleanText($value);
        if ($text === null) {
            return null;
        }

        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength);
        }

        return $text;
    };

    $isValidRequestDate = static function (?string $value): bool {
        if ($value === null || $value === '') {
            return true;
        }

        $ts = strtotime($value);
        return $ts !== false && date('Y-m-d', $ts) === $value;
    };

    $normalizeRequestNumber = static function (?string $value, string $label, float $maxValue, int $scale = 2): ?float {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));
        if (!is_numeric($normalized)) {
            redirectWithState('error', $label . ' must be numeric.', 'personal-information.php');
        }

        $number = round((float)$normalized, $scale);
        if ($number < 0) {
            redirectWithState('error', $label . ' cannot be negative.', 'personal-information.php');
        }

        if ($number > $maxValue) {
            redirectWithState('error', $label . ' is too large. Please enter a smaller value.', 'personal-information.php');
        }

        return $number;
    };

    $assertNoPlaceholderValue = static function (mixed $value, string $label) use (&$assertNoPlaceholderValue): void {
        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $assertNoPlaceholderValue($nestedValue, $label);
            }
            return;
        }

        if (strtolower(trim((string)$value)) === 'haugafia') {
            redirectWithState('error', $label . ' contains an invalid placeholder value.', 'personal-information.php');
        }
    };

    $addBusinessDays = static function (DateTimeImmutable $dateTime, int $businessDays, int $hour, int $minute): DateTimeImmutable {
        $cursor = $dateTime;
        $count = 0;
        while ($count < $businessDays) {
            $cursor = $cursor->modify('+1 day');
            $dayOfWeek = (int)$cursor->format('N');
            if ($dayOfWeek >= 6) {
                continue;
            }
            $count++;
        }

        return $cursor->setTime($hour, $minute, 0);
    };

    $accountLookupResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email,username&id=eq.' . rawurlencode($employeeUserId) . '&limit=1',
        $headers
    );

    $personLookupResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,first_name,middle_name,surname,name_extension,user_id,place_of_birth&id=eq.' . rawurlencode((string)$employeePersonId) . '&limit=1',
        $headers
    );

    $currentAddressLookupResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_addresses?select=address_type,barangay,city_municipality,zip_code'
        . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&limit=20',
        $headers
    );

    $accountRow = isSuccessful($accountLookupResponse) ? (array)($accountLookupResponse['data'][0] ?? []) : [];
    $personRow = isSuccessful($personLookupResponse) ? (array)($personLookupResponse['data'][0] ?? []) : [];
    $currentAddressByType = [];
    if (isSuccessful($currentAddressLookupResponse)) {
        foreach ((array)($currentAddressLookupResponse['data'] ?? []) as $addressRaw) {
            $addressRow = (array)$addressRaw;
            $addressType = strtolower((string)($addressRow['address_type'] ?? ''));
            if ($addressType === '') {
                $addressType = empty($currentAddressByType['residential']) ? 'residential' : 'permanent';
            }

            if (!isset($currentAddressByType[$addressType])) {
                $currentAddressByType[$addressType] = [
                    'barangay' => cleanText($addressRow['barangay'] ?? null) ?? '',
                    'city_municipality' => cleanText($addressRow['city_municipality'] ?? null) ?? '',
                    'zip_code' => cleanText($addressRow['zip_code'] ?? null) ?? '',
                ];
            }
        }
    }

    $normalizeLookupValue = static function (?string $value): string {
        return strtolower(trim((string)preg_replace('/\s+/', ' ', (string)$value)));
    };

    $lookupAssetRoot = dirname(__DIR__, 4) . '/assets';
    $placeOfBirthLabelByKey = [];
    $cityLabelByKey = [];
    $provinceByCityKey = [];
    $barangayLabelByCityKey = [];
    $zipByCityBarangayKey = [];

    $municipalitiesPath = $lookupAssetRoot . '/psgc/municipalities.json';
    if (is_file($municipalitiesPath)) {
        $municipalitiesRaw = file_get_contents($municipalitiesPath);
        $municipalitiesData = is_string($municipalitiesRaw) ? json_decode($municipalitiesRaw, true) : null;
        if (is_array($municipalitiesData)) {
            foreach ($municipalitiesData as $municipalityRaw) {
                $municipality = (array)$municipalityRaw;
                $cityName = cleanText($municipality['name'] ?? null);
                $provinceName = cleanText($municipality['province'] ?? null);
                if ($cityName === null || $cityName === '') {
                    continue;
                }

                $cityKey = $normalizeLookupValue($cityName);
                $canonicalPlace = $provinceName !== null && $provinceName !== ''
                    ? $cityName . ', ' . $provinceName
                    : $cityName;

                $cityLabelByKey[$cityKey] = $cityName;
                $placeOfBirthLabelByKey[$cityKey] = $canonicalPlace;
                $placeOfBirthLabelByKey[$normalizeLookupValue($canonicalPlace)] = $canonicalPlace;

                if ($provinceName !== null && $provinceName !== '') {
                    $provinceByCityKey[$cityKey] = $provinceName;
                }
            }
        }
    }

    $zipCodesPath = $lookupAssetRoot . '/zip-codes.json';
    if (is_file($zipCodesPath)) {
        $zipCodesRaw = file_get_contents($zipCodesPath);
        $zipCodesData = is_string($zipCodesRaw) ? json_decode($zipCodesRaw, true) : null;
        if (is_array($zipCodesData)) {
            foreach ($zipCodesData as $cityName => $barangayGroupRaw) {
                $cityKey = $normalizeLookupValue((string)$cityName);
                if ($cityKey === '') {
                    continue;
                }

                if (!isset($zipByCityBarangayKey[$cityKey])) {
                    $zipByCityBarangayKey[$cityKey] = [];
                }

                foreach ((array)$barangayGroupRaw as $barangayName => $zipListRaw) {
                    $barangayKey = $normalizeLookupValue((string)$barangayName);
                    if ($barangayKey === '') {
                        continue;
                    }

                    if (!isset($zipByCityBarangayKey[$cityKey][$barangayKey])) {
                        $zipByCityBarangayKey[$cityKey][$barangayKey] = [];
                    }

                    foreach ((array)$zipListRaw as $zipValue) {
                        $zipText = trim((string)$zipValue);
                        if ($zipText !== '' && !in_array($zipText, $zipByCityBarangayKey[$cityKey][$barangayKey], true)) {
                            $zipByCityBarangayKey[$cityKey][$barangayKey][] = $zipText;
                        }
                    }
                }
            }
        }
    }

    $barangaysPath = $lookupAssetRoot . '/psgc/barangays.json';
    if (is_file($barangaysPath)) {
        $barangaysRaw = file_get_contents($barangaysPath);
        $barangaysData = is_string($barangaysRaw) ? json_decode($barangaysRaw, true) : null;
        if (is_array($barangaysData)) {
            foreach ($barangaysData as $barangayRaw) {
                $barangay = (array)$barangayRaw;
                $barangayName = cleanText($barangay['name'] ?? null);
                $cityName = cleanText($barangay['citymun'] ?? null);
                $zipCode = cleanText($barangay['zip_code'] ?? null);

                if ($barangayName === null || $barangayName === '' || $cityName === null || $cityName === '') {
                    continue;
                }

                $cityKey = $normalizeLookupValue($cityName);
                $barangayKey = $normalizeLookupValue($barangayName);
                if (!isset($barangayLabelByCityKey[$cityKey])) {
                    $barangayLabelByCityKey[$cityKey] = [];
                }
                $barangayLabelByCityKey[$cityKey][$barangayKey] = $barangayName;

                if ($zipCode !== null && $zipCode !== '') {
                    if (!isset($zipByCityBarangayKey[$cityKey])) {
                        $zipByCityBarangayKey[$cityKey] = [];
                    }
                    if (!isset($zipByCityBarangayKey[$cityKey][$barangayKey])) {
                        $zipByCityBarangayKey[$cityKey][$barangayKey] = [];
                    }
                    if (!in_array($zipCode, $zipByCityBarangayKey[$cityKey][$barangayKey], true)) {
                        $zipByCityBarangayKey[$cityKey][$barangayKey][] = $zipCode;
                    }
                }
            }
        }
    }

    $firstName = $toNullableRequestValue($_POST['first_name'] ?? null, 120);
    $middleName = $toNullableRequestValue($_POST['middle_name'] ?? null, 120);
    $surname = $toNullableRequestValue($_POST['surname'] ?? null, 120);
    $nameExtension = $toNullableRequestValue($_POST['name_extension'] ?? null, 30);
    $dateOfBirth = $toNullableRequestValue($_POST['date_of_birth'] ?? null, 10);
    $placeOfBirth = $toNullableRequestValue($_POST['place_of_birth'] ?? null, 160);
    $sexAtBirth = $toNullableRequestValue($_POST['sex_at_birth'] ?? null, 12);
    $civilStatus = $toNullableRequestValue($_POST['civil_status'] ?? null, 40);
    $heightM = $toNullableRequestValue($_POST['height_m'] ?? null, 10);
    $weightKg = $toNullableRequestValue($_POST['weight_kg'] ?? null, 10);
    $bloodType = $toNullableRequestValue($_POST['blood_type'] ?? null, 10);
    $citizenship = $toNullableRequestValue($_POST['citizenship'] ?? null, 80);
    $dualCitizenshipCountry = $toNullableRequestValue($_POST['dual_citizenship_country'] ?? null, 80);
    $telephoneNo = $toNullableRequestValue($_POST['telephone_no'] ?? null, 30);
    $mobileNo = $toNullableRequestValue($_POST['mobile_no'] ?? null, 30);
    $personalEmail = $toNullableRequestValue($_POST['email'] ?? $_POST['personal_email'] ?? null, 200);
    $agencyEmployeeNo = $toNullableRequestValue($_POST['agency_employee_no'] ?? null, 80);

    $residentialRecommendation = [
        'house_no' => $toNullableRequestValue($_POST['residential_house_no'] ?? $_POST['house_no'] ?? null, 60) ?? '',
        'street' => $toNullableRequestValue($_POST['residential_street'] ?? $_POST['street'] ?? null, 160) ?? '',
        'subdivision' => $toNullableRequestValue($_POST['residential_subdivision'] ?? $_POST['subdivision'] ?? null, 160) ?? '',
        'barangay' => $toNullableRequestValue($_POST['residential_barangay'] ?? $_POST['barangay'] ?? null, 120) ?? '',
        'city_municipality' => $toNullableRequestValue($_POST['residential_city_municipality'] ?? $_POST['city_municipality'] ?? null, 120) ?? '',
        'province' => $toNullableRequestValue($_POST['residential_province'] ?? $_POST['province'] ?? null, 120) ?? '',
        'zip_code' => $toNullableRequestValue($_POST['residential_zip_code'] ?? $_POST['zip_code'] ?? null, 20) ?? '',
    ];

    $permanentSameAsResidential = isset($_POST['permanent_same_as_residential']) && (string)$_POST['permanent_same_as_residential'] === '1';
    $permanentRecommendation = [
        'house_no' => $toNullableRequestValue($_POST['permanent_house_no'] ?? null, 60) ?? '',
        'street' => $toNullableRequestValue($_POST['permanent_street'] ?? null, 160) ?? '',
        'subdivision' => $toNullableRequestValue($_POST['permanent_subdivision'] ?? null, 160) ?? '',
        'barangay' => $toNullableRequestValue($_POST['permanent_barangay'] ?? null, 120) ?? '',
        'city_municipality' => $toNullableRequestValue($_POST['permanent_city_municipality'] ?? null, 120) ?? '',
        'province' => $toNullableRequestValue($_POST['permanent_province'] ?? null, 120) ?? '',
        'zip_code' => $toNullableRequestValue($_POST['permanent_zip_code'] ?? null, 20) ?? '',
    ];

    if ($permanentSameAsResidential) {
        $permanentRecommendation = $residentialRecommendation;
    }

    $governmentRecommendation = [
        'umid' => $toNullableRequestValue($_POST['umid_no'] ?? null, 80) ?? '',
        'pagibig' => $toNullableRequestValue($_POST['pagibig_no'] ?? null, 80) ?? '',
        'philhealth' => $toNullableRequestValue($_POST['philhealth_no'] ?? null, 80) ?? '',
        'psn' => $toNullableRequestValue($_POST['psn_no'] ?? null, 80) ?? '',
        'tin' => $toNullableRequestValue($_POST['tin_no'] ?? null, 80) ?? '',
    ];

    $familyRecommendation = [
        'spouse_surname' => $toNullableRequestValue($_POST['spouse_surname'] ?? null, 120) ?? '',
        'spouse_first_name' => $toNullableRequestValue($_POST['spouse_first_name'] ?? null, 120) ?? '',
        'spouse_middle_name' => $toNullableRequestValue($_POST['spouse_middle_name'] ?? null, 120) ?? '',
        'spouse_extension_name' => $toNullableRequestValue($_POST['spouse_name_extension'] ?? $_POST['spouse_extension_name'] ?? null, 30) ?? '',
        'spouse_occupation' => $toNullableRequestValue($_POST['spouse_occupation'] ?? null, 160) ?? '',
        'spouse_employer_business_name' => $toNullableRequestValue($_POST['spouse_employer_business_name'] ?? null, 180) ?? '',
        'spouse_business_address' => $toNullableRequestValue($_POST['spouse_business_address'] ?? null, 200) ?? '',
        'spouse_telephone_no' => $toNullableRequestValue($_POST['spouse_telephone_no'] ?? null, 30) ?? '',
        'father_surname' => $toNullableRequestValue($_POST['father_surname'] ?? null, 120) ?? '',
        'father_first_name' => $toNullableRequestValue($_POST['father_first_name'] ?? null, 120) ?? '',
        'father_middle_name' => $toNullableRequestValue($_POST['father_middle_name'] ?? null, 120) ?? '',
        'father_extension_name' => $toNullableRequestValue($_POST['father_name_extension'] ?? $_POST['father_extension_name'] ?? null, 30) ?? '',
        'mother_surname' => $toNullableRequestValue($_POST['mother_surname'] ?? null, 120) ?? '',
        'mother_first_name' => $toNullableRequestValue($_POST['mother_first_name'] ?? null, 120) ?? '',
        'mother_middle_name' => $toNullableRequestValue($_POST['mother_middle_name'] ?? null, 120) ?? '',
        'mother_extension_name' => $toNullableRequestValue($_POST['mother_name_extension'] ?? $_POST['mother_extension_name'] ?? null, 30) ?? '',
    ];

    $childrenRecommendation = [];
    $childrenNames = (array)($_POST['children_full_name'] ?? []);
    $childrenBirthDates = (array)($_POST['children_birth_date'] ?? []);
    $childrenCount = max(count($childrenNames), count($childrenBirthDates));
    for ($index = 0; $index < $childrenCount; $index++) {
        $childName = $toNullableRequestValue($childrenNames[$index] ?? null, 180);
        $childBirthDate = $toNullableRequestValue($childrenBirthDates[$index] ?? null, 10);
        if ($childName === null && $childBirthDate === null) {
            continue;
        }

        if (!$isValidRequestDate($childBirthDate)) {
            redirectWithState('error', 'One of the child birth dates is invalid.', 'personal-information.php');
        }

        $childrenRecommendation[] = [
            'full_name' => $childName ?? '',
            'birth_date' => $childBirthDate ?? '',
        ];
    }

    $educationRecommendation = [];
    $educationLevels = (array)($_POST['education_level'] ?? []);
    $educationSchoolNames = (array)($_POST['education_school_name'] ?? []);
    $educationCourses = (array)($_POST['education_course_degree'] ?? []);
    $educationFrom = (array)($_POST['education_period_from'] ?? []);
    $educationTo = (array)($_POST['education_period_to'] ?? []);
    $educationUnits = (array)($_POST['education_highest_level_units'] ?? []);
    $educationYearGraduated = (array)($_POST['education_year_graduated'] ?? []);
    $educationHonors = (array)($_POST['education_honors_received'] ?? []);
    $educationCount = max(
        count($educationLevels),
        count($educationSchoolNames),
        count($educationCourses),
        count($educationFrom),
        count($educationTo),
        count($educationUnits),
        count($educationYearGraduated),
        count($educationHonors)
    );
    for ($index = 0; $index < $educationCount; $index++) {
        $educationLevel = $toNullableRequestValue($educationLevels[$index] ?? null, 30);
        $schoolName = $toNullableRequestValue($educationSchoolNames[$index] ?? null, 220);
        $degreeCourse = $toNullableRequestValue($educationCourses[$index] ?? null, 220);
        $attendanceFrom = $toNullableRequestValue($educationFrom[$index] ?? null, 15);
        $attendanceTo = $toNullableRequestValue($educationTo[$index] ?? null, 15);
        $highestUnits = $toNullableRequestValue($educationUnits[$index] ?? null, 140);
        $yearGraduated = $toNullableRequestValue($educationYearGraduated[$index] ?? null, 20);
        $honorsReceived = $toNullableRequestValue($educationHonors[$index] ?? null, 180);

        if ($educationLevel === null && $schoolName === null && $degreeCourse === null && $attendanceFrom === null && $attendanceTo === null && $highestUnits === null && $yearGraduated === null && $honorsReceived === null) {
            continue;
        }

        $educationRecommendation[] = [
            'education_level' => $educationLevel ?? '',
            'school_name' => $schoolName ?? '',
            'degree_course' => $degreeCourse ?? '',
            'attendance_from_year' => $attendanceFrom ?? '',
            'attendance_to_year' => $attendanceTo ?? '',
            'highest_level_units_earned' => $highestUnits ?? '',
            'year_graduated' => $yearGraduated ?? '',
            'scholarship_honors_received' => $honorsReceived ?? '',
        ];
    }

    if ($personalEmail !== null && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'Please provide a valid personal email address.', 'personal-information.php');
    }

    if ($sexAtBirth !== null && $sexAtBirth !== '' && !in_array($sexAtBirth, ['male', 'female'], true)) {
        redirectWithState('error', 'Sex at birth must be male or female.', 'personal-information.php');
    }

    if (!$isValidRequestDate($dateOfBirth)) {
        redirectWithState('error', 'Date of birth must be a valid date.', 'personal-information.php');
    }

    foreach ([$residentialRecommendation['zip_code'], $permanentRecommendation['zip_code']] as $zipCodeValue) {
        if ($zipCodeValue !== '' && !preg_match('/^\d{4}$/', $zipCodeValue)) {
            redirectWithState('error', 'ZIP code must be a valid 4-digit value.', 'personal-information.php');
        }
    }

    $currentPlaceOfBirth = cleanText($personRow['place_of_birth'] ?? null) ?? '';
    $placeOfBirthChanged = $normalizeLookupValue($placeOfBirth ?? '') !== $normalizeLookupValue($currentPlaceOfBirth);
    if ($placeOfBirthChanged && $placeOfBirth !== null && trim($placeOfBirth) !== '') {
        $canonicalPlaceOfBirth = $placeOfBirthLabelByKey[$normalizeLookupValue($placeOfBirth)] ?? null;
        if ($canonicalPlaceOfBirth !== null) {
            $placeOfBirth = $canonicalPlaceOfBirth;
        }
    }

    $validateAddressRecommendation = static function (array &$address, string $label, bool $shouldValidate) use ($normalizeLookupValue, $cityLabelByKey, $provinceByCityKey, $barangayLabelByCityKey, $zipByCityBarangayKey): void {
        $cityKey = $normalizeLookupValue($address['city_municipality'] ?? '');
        $barangayKey = $normalizeLookupValue($address['barangay'] ?? '');
        $zipCode = trim((string)($address['zip_code'] ?? ''));

        if ($cityKey === '') {
            return;
        }

        if (!$shouldValidate) {
            return;
        }

        if (isset($cityLabelByKey[$cityKey])) {
            $address['city_municipality'] = $cityLabelByKey[$cityKey];
        }
        if (isset($provinceByCityKey[$cityKey])) {
            $address['province'] = $provinceByCityKey[$cityKey];
        }

        if ($barangayKey === '') {
            return;
        }

        $barangayMap = $barangayLabelByCityKey[$cityKey] ?? [];
        if (isset($barangayMap[$barangayKey])) {
            $address['barangay'] = $barangayMap[$barangayKey];
        }

        $zipOptions = $zipByCityBarangayKey[$cityKey][$barangayKey] ?? [];
        if (empty($zipOptions)) {
            return;
        }

        if ($zipCode === '' && count($zipOptions) === 1) {
            $address['zip_code'] = (string)$zipOptions[0];
        }
    };

    $currentResidentialAddress = $currentAddressByType['residential'] ?? ['barangay' => '', 'city_municipality' => '', 'zip_code' => ''];
    $currentPermanentAddress = $currentAddressByType['permanent'] ?? ['barangay' => '', 'city_municipality' => '', 'zip_code' => ''];
    $residentialSelectionChanged = $normalizeLookupValue($residentialRecommendation['barangay'] ?? '') !== $normalizeLookupValue($currentResidentialAddress['barangay'] ?? '')
        || $normalizeLookupValue($residentialRecommendation['city_municipality'] ?? '') !== $normalizeLookupValue($currentResidentialAddress['city_municipality'] ?? '')
        || trim((string)($residentialRecommendation['zip_code'] ?? '')) !== trim((string)($currentResidentialAddress['zip_code'] ?? ''));
    $validateAddressRecommendation($residentialRecommendation, 'Residential address', $residentialSelectionChanged);
    if ($permanentSameAsResidential) {
        $permanentRecommendation = $residentialRecommendation;
    } else {
        $permanentSelectionChanged = $normalizeLookupValue($permanentRecommendation['barangay'] ?? '') !== $normalizeLookupValue($currentPermanentAddress['barangay'] ?? '')
            || $normalizeLookupValue($permanentRecommendation['city_municipality'] ?? '') !== $normalizeLookupValue($currentPermanentAddress['city_municipality'] ?? '')
            || trim((string)($permanentRecommendation['zip_code'] ?? '')) !== trim((string)($currentPermanentAddress['zip_code'] ?? ''));
        $validateAddressRecommendation($permanentRecommendation, 'Permanent address', $permanentSelectionChanged);
    }

    $assertNoPlaceholderValue([
        $firstName,
        $middleName,
        $surname,
        $nameExtension,
        $dateOfBirth,
        $placeOfBirth,
        $civilStatus,
        $bloodType,
        $citizenship,
        $dualCitizenshipCountry,
        $telephoneNo,
        $mobileNo,
        $personalEmail,
        $agencyEmployeeNo,
        $residentialRecommendation,
        $permanentRecommendation,
        $governmentRecommendation,
        $familyRecommendation,
        $childrenRecommendation,
        $educationRecommendation,
    ], 'Personal information request');

    $requestProfilePayload = [
        'first_name' => $firstName ?? (string)($personRow['first_name'] ?? ''),
        'middle_name' => $middleName,
        'surname' => $surname ?? (string)($personRow['surname'] ?? ''),
        'name_extension' => $nameExtension,
        'date_of_birth' => $dateOfBirth,
        'place_of_birth' => $placeOfBirth,
        'sex_at_birth' => $sexAtBirth,
        'civil_status' => $civilStatus,
        'height_m' => $normalizeRequestNumber($heightM, 'Height', 3.0),
        'weight_kg' => $normalizeRequestNumber($weightKg, 'Weight', 999.99),
        'blood_type' => $bloodType,
        'citizenship' => $citizenship,
        'dual_citizenship' => $dualCitizenshipCountry !== null && $dualCitizenshipCountry !== '',
        'dual_citizenship_country' => $dualCitizenshipCountry,
        'telephone_no' => $telephoneNo,
        'mobile_no' => $mobileNo,
        'personal_email' => $personalEmail,
        'agency_employee_no' => $agencyEmployeeNo,
    ];

    $employeeName = trim(implode(' ', array_filter([
        (string)($requestProfilePayload['first_name'] ?? ''),
        (string)($requestProfilePayload['middle_name'] ?? ''),
        (string)($requestProfilePayload['surname'] ?? ''),
        (string)($requestProfilePayload['name_extension'] ?? ''),
    ], static fn(string $part): bool => trim($part) !== '')));
    if ($employeeName === '') {
        $employeeName = trim(implode(' ', array_filter([
            (string)($personRow['first_name'] ?? ''),
            (string)($personRow['middle_name'] ?? ''),
            (string)($personRow['surname'] ?? ''),
            (string)($personRow['name_extension'] ?? ''),
        ], static fn(string $part): bool => trim($part) !== '')));
    }
    if ($employeeName === '') {
        $employeeName = 'Employee';
    }

    $manilaNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    $dueAt = $addBusinessDays($manilaNow, 5, 17, 0);
    $reminderWindowStartsAt = $addBusinessDays($manilaNow, 3, 9, 0);
    $dueAtIso = $dueAt->format(DATE_ATOM);
    $reminderAtIso = $reminderWindowStartsAt->format(DATE_ATOM);
    $dueAtLabel = function_exists('formatDateTimeForPhilippines')
        ? formatDateTimeForPhilippines($dueAtIso, 'M d, Y h:i A') . ' PST'
        : $dueAt->format('M d, Y h:i A') . ' PST';

    $adminRecipientIds = [];
    $adminRoleResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/roles?select=id&role_key=eq.admin&limit=1',
        $headers
    );

    $adminRoleId = isSuccessful($adminRoleResponse)
        ? cleanText($adminRoleResponse['data'][0]['id'] ?? null)
        : null;

    if ($adminRoleId !== null) {
        $adminAssignmentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=user_id'
            . '&role_id=eq.' . rawurlencode($adminRoleId)
            . '&expires_at=is.null&limit=200',
            $headers
        );

        if (isSuccessful($adminAssignmentsResponse)) {
            foreach ((array)($adminAssignmentsResponse['data'] ?? []) as $assignmentRaw) {
                $assignment = (array)$assignmentRaw;
                $recipientId = cleanText($assignment['user_id'] ?? null);
                if ($recipientId !== null && $recipientId !== '' && strcasecmp($recipientId, (string)$employeeUserId) !== 0) {
                    $adminRecipientIds[$recipientId] = true;
                }
            }
        }
    }

    $staffRecipientIds = [];
    $employmentScopeResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/employment_records?select=office_id&person_id=eq.' . rawurlencode((string)$employeePersonId) . '&is_current=eq.true&limit=1',
        $headers
    );
    $employeeOfficeId = isSuccessful($employmentScopeResponse)
        ? cleanText($employmentScopeResponse['data'][0]['office_id'] ?? null)
        : null;

    if ($employeeOfficeId !== null && $employeeOfficeId !== '') {
        $staffAssignmentsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/user_role_assignments?select=user_id,roles!inner(role_key)'
            . '&office_id=eq.' . rawurlencode($employeeOfficeId)
            . '&roles.role_key=in.(staff,hr_officer,supervisor)'
            . '&expires_at=is.null&limit=500',
            $headers
        );

        if (isSuccessful($staffAssignmentsResponse)) {
            foreach ((array)($staffAssignmentsResponse['data'] ?? []) as $assignmentRaw) {
                $assignment = (array)$assignmentRaw;
                $recipientId = cleanText($assignment['user_id'] ?? null);
                if ($recipientId !== null && $recipientId !== '' && strcasecmp($recipientId, (string)$employeeUserId) !== 0) {
                    $staffRecipientIds[$recipientId] = true;
                }
            }
        }
    }

    $notificationTargets = ['admin'];
    if (!empty($staffRecipientIds)) {
        $notificationTargets[] = 'staff';
    }

    $requestLogResponse = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=representation']),
        [[
            'actor_user_id' => $employeeUserId,
            'module_name' => 'personal_information',
            'entity_name' => 'people',
            'entity_id' => $employeePersonId,
            'action_name' => 'submit_employee_profile_update_request',
            'old_data' => [
                'submitted_by_email' => (string)($accountRow['email'] ?? ''),
                'submitted_by_username' => (string)($accountRow['username'] ?? ''),
            ],
            'new_data' => [
                'review_status' => 'pending_admin_review',
                'request_source' => 'employee',
                'submitted_at' => $manilaNow->format(DATE_ATOM),
                'request_due_at' => $dueAtIso,
                'reminder_window_starts_at' => $reminderAtIso,
                'notification_targets' => $notificationTargets,
                'notification_recipient_count' => count($adminRecipientIds) + count($staffRecipientIds),
                'submitted_by_email' => (string)($accountRow['email'] ?? ''),
                'submitted_by_username' => (string)($accountRow['username'] ?? ''),
                'recommended_profile' => $requestProfilePayload,
                'recommended_addresses' => [
                    'residential' => $residentialRecommendation,
                    'permanent' => $permanentRecommendation,
                ],
                'recommended_government_ids' => $governmentRecommendation,
                'recommended_family' => array_merge($familyRecommendation, [
                    'children' => $childrenRecommendation,
                ]),
                'recommended_educational_backgrounds' => $educationRecommendation,
            ],
            'ip_address' => clientIp(),
        ]]
    );

    if (!isSuccessful($requestLogResponse)) {
        redirectWithState('error', 'Unable to submit your personal information update request right now.', 'personal-information.php');
    }

    $notificationPayload = [];
    foreach (array_keys($adminRecipientIds) as $recipientId) {
        $notificationPayload[] = [
            'recipient_user_id' => $recipientId,
            'category' => 'employee_profile',
            'title' => 'Personal Information Update Request',
            'body' => $employeeName . ' submitted a personal information update request. Target completion: ' . $dueAtLabel . '.',
            'link_url' => '/hris-system/pages/admin/personal-information.php',
        ];
    }

    foreach (array_keys($staffRecipientIds) as $recipientId) {
        $notificationPayload[] = [
            'recipient_user_id' => $recipientId,
            'category' => 'employee_profile',
            'title' => 'Employee Personal Information Request',
            'body' => $employeeName . ' submitted a personal information request within your scope. Admin target completion: ' . $dueAtLabel . '.',
            'link_url' => '/hris-system/pages/staff/personal-information.php',
        ];
    }

    if (!empty($notificationPayload)) {
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/notifications',
            array_merge($headers, ['Prefer: return=minimal']),
            $notificationPayload
        );
    }

    redirectWithState('success', 'Your personal information request was submitted for review. Target completion: ' . $dueAtLabel . '.', 'personal-information.php');
}

$toNullable = static function (mixed $value, int $maxLength = 255): ?string {
    $text = cleanText($value);
    if ($text === null) {
        return null;
    }

    if (mb_strlen($text) > $maxLength) {
        return mb_substr($text, 0, $maxLength);
    }

    return $text;
};

$isValidDate = static function (?string $value): bool {
    if ($value === null) {
        return true;
    }

    $ts = strtotime($value);
    return $ts !== false && date('Y-m-d', $ts) === $value;
};

$extractApiErrorMessage = static function (array $response): ?string {
    $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
    $candidates = [
        $data['message'] ?? null,
        $data['error_description'] ?? null,
        $data['error'] ?? null,
        $data['details'] ?? null,
        $data['hint'] ?? null,
        $response['error'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        $text = cleanText($candidate);
        if ($text !== null) {
            return $text;
        }
    }

    return null;
};

$normalizeDecimalField = static function (?string $value, string $label, float $maxValue, int $scale = 2): ?float {
    if ($value === null) {
        return null;
    }

    $normalized = str_replace(',', '.', trim($value));
    if ($normalized === '') {
        return null;
    }

    if (!is_numeric($normalized)) {
        redirectWithState('error', $label . ' must be numeric.', 'personal-information.php');
    }

    $number = round((float)$normalized, $scale);
    if ($number < 0) {
        redirectWithState('error', $label . ' cannot be negative.', 'personal-information.php');
    }

    if ($number > $maxValue) {
        redirectWithState('error', $label . ' is too large. Please enter a smaller value.', 'personal-information.php');
    }

    return $number;
};

$upsertSingleById = static function (string $table, ?string $id, array $payload) use ($supabaseUrl, $headers): bool {
    if ($id !== null && isValidUuid($id)) {
        $response = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/' . $table . '?id=eq.' . rawurlencode($id),
            array_merge($headers, ['Prefer: return=minimal']),
            $payload
        );

        return isSuccessful($response);
    }

    $response = apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/' . $table,
        array_merge($headers, ['Prefer: return=minimal']),
        [$payload]
    );

    return isSuccessful($response);
};

$nameExtension = $toNullable($_POST['name_extension'] ?? null, 30);
$firstName = $toNullable($_POST['first_name'] ?? null, 120);
$surname = $toNullable($_POST['surname'] ?? null, 120);
$sexAtBirth = $toNullable($_POST['sex_at_birth'] ?? null, 12);
$civilStatus = $toNullable($_POST['civil_status'] ?? null, 40);
$heightM = $toNullable($_POST['height_m'] ?? null, 10);
$weightKg = $toNullable($_POST['weight_kg'] ?? null, 10);
$bloodType = $toNullable($_POST['blood_type'] ?? null, 10);
$umidNo = $toNullable($_POST['umid_no'] ?? null, 80);
$pagibigNo = $toNullable($_POST['pagibig_no'] ?? null, 80);
$philhealthNo = $toNullable($_POST['philhealth_no'] ?? null, 80);
$psnNo = $toNullable($_POST['psn_no'] ?? null, 80);
$tinNo = $toNullable($_POST['tin_no'] ?? null, 80);
$agencyEmployeeNo = $toNullable($_POST['agency_employee_no'] ?? null, 80);
$citizenship = $toNullable($_POST['citizenship'] ?? null, 80);
$dualCitizenship = (($toNullable($_POST['dual_citizenship'] ?? null, 1) ?? '0') === '1');
$dualCitizenshipCountry = $toNullable($_POST['dual_citizenship_country'] ?? null, 80);

$residentialAddressId = $toNullable($_POST['address_id'] ?? null, 36);
$houseNo = $toNullable($_POST['residential_house_no'] ?? $_POST['house_no'] ?? null, 60);
$street = $toNullable($_POST['residential_street'] ?? $_POST['street'] ?? null, 160);
$subdivision = $toNullable($_POST['residential_subdivision'] ?? $_POST['subdivision'] ?? null, 160);
$barangay = $toNullable($_POST['residential_barangay'] ?? $_POST['barangay'] ?? null, 120);
$cityMunicipality = $toNullable($_POST['residential_city_municipality'] ?? $_POST['city_municipality'] ?? null, 120);
$province = $toNullable($_POST['residential_province'] ?? $_POST['province'] ?? null, 120);
$zipCode = $toNullable($_POST['residential_zip_code'] ?? $_POST['zip_code'] ?? null, 20);

$permanentAddressId = $toNullable($_POST['permanent_address_id'] ?? null, 36);
$permanentHouseNo = $toNullable($_POST['permanent_house_no'] ?? null, 60);
$permanentStreet = $toNullable($_POST['permanent_street'] ?? null, 160);
$permanentSubdivision = $toNullable($_POST['permanent_subdivision'] ?? null, 160);
$permanentBarangay = $toNullable($_POST['permanent_barangay'] ?? null, 120);
$permanentCityMunicipality = $toNullable($_POST['permanent_city_municipality'] ?? null, 120);
$permanentProvince = $toNullable($_POST['permanent_province'] ?? null, 120);
$permanentZipCode = $toNullable($_POST['permanent_zip_code'] ?? null, 20);
$permanentSameAsResidential = isset($_POST['permanent_same_as_residential']) && (string)$_POST['permanent_same_as_residential'] === '1';

$telephoneNo = $toNullable($_POST['telephone_no'] ?? null, 30);
$mobileNo = $toNullable($_POST['mobile_no'] ?? null, 30);
$personalEmail = $toNullable($_POST['email'] ?? $_POST['personal_email'] ?? null, 200);

$fatherId = $toNullable($_POST['father_id'] ?? null, 36);
$fatherSurname = $toNullable($_POST['father_surname'] ?? null, 120);
$fatherFirstName = $toNullable($_POST['father_first_name'] ?? null, 120);
$fatherNameExtension = $toNullable($_POST['father_name_extension'] ?? null, 30);
$fatherMiddleName = $toNullable($_POST['father_middle_name'] ?? null, 120);

$motherId = $toNullable($_POST['mother_id'] ?? null, 36);
$motherSurname = $toNullable($_POST['mother_surname'] ?? null, 120);
$motherFirstName = $toNullable($_POST['mother_first_name'] ?? null, 120);
$motherMiddleName = $toNullable($_POST['mother_middle_name'] ?? null, 120);
$motherNameExtension = $toNullable($_POST['mother_name_extension'] ?? null, 30);

$childrenNames = (array)($_POST['children_full_name'] ?? []);
$childrenBirthDates = (array)($_POST['children_birth_date'] ?? []);

$educationLevels = (array)($_POST['education_level'] ?? []);
$educationSchoolNames = (array)($_POST['education_school_name'] ?? []);
$educationCourses = (array)($_POST['education_course_degree'] ?? []);
$educationFrom = (array)($_POST['education_period_from'] ?? []);
$educationTo = (array)($_POST['education_period_to'] ?? []);
$educationUnits = (array)($_POST['education_highest_level_units'] ?? []);
$educationYearGraduated = (array)($_POST['education_year_graduated'] ?? []);
$educationHonors = (array)($_POST['education_honors_received'] ?? []);

if ($personalEmail !== null && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid personal email address.', 'personal-information.php');
}

if ($sexAtBirth !== null && !in_array($sexAtBirth, ['male', 'female'], true)) {
    redirectWithState('error', 'Sex at birth must be male or female.', 'personal-information.php');
}

$heightMValue = $normalizeDecimalField($heightM, 'Height', 3.0);
$weightKgValue = $normalizeDecimalField($weightKg, 'Weight', 999.99);

$beforeResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($beforeResponse) || empty((array)($beforeResponse['data'] ?? []))) {
    redirectWithState('error', 'Unable to load profile before update. Please try again.', 'personal-information.php');
}

$beforeData = (array)$beforeResponse['data'][0];
$beforeMiddleName = cleanText($beforeData['middle_name'] ?? null);
$beforeDateOfBirth = cleanText($beforeData['date_of_birth'] ?? null);
$beforePlaceOfBirth = cleanText($beforeData['place_of_birth'] ?? null);

$requestedMiddleName = $toNullable($_POST['middle_name'] ?? null, 120);
$requestedDateOfBirth = $toNullable($_POST['date_of_birth'] ?? null, 10);
$requestedPlaceOfBirth = $toNullable($_POST['place_of_birth'] ?? null, 160);

if ($requestedDateOfBirth !== null && !$isValidDate($requestedDateOfBirth)) {
    redirectWithState('error', 'Date of birth must be a valid date.', 'personal-information.php');
}

$resolveFirstTimeSensitiveValue = static function (?string $currentValue, ?string $requestedValue, string $fieldLabel): ?string {
    $current = cleanText($currentValue);
    $requested = cleanText($requestedValue);

    if ($current !== null && $current !== '') {
        if ($requested !== null && $requested !== '' && $requested !== $current) {
            redirectWithState('error', $fieldLabel . ' can only be updated once from Personal Information. Submit a support ticket for further changes.', 'personal-information.php');
        }

        return $current;
    }

    return $requested;
};

$middleName = $resolveFirstTimeSensitiveValue($beforeMiddleName, $requestedMiddleName, 'Middle name');
$dateOfBirth = $resolveFirstTimeSensitiveValue($beforeDateOfBirth, $requestedDateOfBirth, 'Date of birth');
$placeOfBirth = $resolveFirstTimeSensitiveValue($beforePlaceOfBirth, $requestedPlaceOfBirth, 'Place of birth');

$peoplePayload = [
    'first_name' => $firstName ?? (string)($beforeData['first_name'] ?? ''),
    'surname' => $surname ?? (string)($beforeData['surname'] ?? ''),
    'middle_name' => $middleName,
    'name_extension' => $nameExtension,
    'date_of_birth' => $dateOfBirth,
    'place_of_birth' => $placeOfBirth,
    'sex_at_birth' => $sexAtBirth,
    'civil_status' => $civilStatus,
    'height_m' => $heightMValue,
    'weight_kg' => $weightKgValue,
    'blood_type' => $bloodType,
    'citizenship' => $citizenship,
    'dual_citizenship' => $dualCitizenship,
    'dual_citizenship_country' => $dualCitizenship ? $dualCitizenshipCountry : null,
    'telephone_no' => $telephoneNo,
    'mobile_no' => $mobileNo,
    'personal_email' => $personalEmail,
    'agency_employee_no' => $agencyEmployeeNo,
    'updated_at' => gmdate('c'),
];

$updateHeaders = array_merge($headers, ['Prefer: return=minimal']);

$peopleUpdateResponse = apiRequest(
    'PATCH',
    $supabaseUrl . '/rest/v1/people?id=eq.' . rawurlencode((string)$employeePersonId),
    $updateHeaders,
    $peoplePayload
);

if (!isSuccessful($peopleUpdateResponse)) {
    $apiErrorMessage = $extractApiErrorMessage($peopleUpdateResponse);
    redirectWithState('error', $apiErrorMessage !== null ? 'Failed to update profile information: ' . $apiErrorMessage : 'Failed to update profile information. Please try again.', 'personal-information.php');
}

$residentialPayload = [
    'person_id' => $employeePersonId,
    'address_type' => 'residential',
    'house_no' => $houseNo,
    'street' => $street,
    'subdivision' => $subdivision,
    'barangay' => $barangay,
    'city_municipality' => $cityMunicipality,
    'province' => $province,
    'zip_code' => $zipCode,
    'country' => 'Philippines',
    'is_primary' => true,
];

if (!$upsertSingleById('person_addresses', $residentialAddressId, $residentialPayload)) {
    redirectWithState('error', 'Failed to save residential address.', 'personal-information.php');
}

if ($permanentSameAsResidential) {
    $permanentHouseNo = $houseNo;
    $permanentStreet = $street;
    $permanentSubdivision = $subdivision;
    $permanentBarangay = $barangay;
    $permanentCityMunicipality = $cityMunicipality;
    $permanentProvince = $province;
    $permanentZipCode = $zipCode;
}

$permanentPayload = [
    'person_id' => $employeePersonId,
    'address_type' => 'permanent',
    'house_no' => $permanentHouseNo,
    'street' => $permanentStreet,
    'subdivision' => $permanentSubdivision,
    'barangay' => $permanentBarangay,
    'city_municipality' => $permanentCityMunicipality,
    'province' => $permanentProvince,
    'zip_code' => $permanentZipCode,
    'country' => 'Philippines',
    'is_primary' => false,
];

if (!$upsertSingleById('person_addresses', $permanentAddressId, $permanentPayload)) {
    redirectWithState('error', 'Failed to save permanent address.', 'personal-information.php');
}

$existingGovIdResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/person_government_ids?select=id,id_type&person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$govIdMap = [];
if (isSuccessful($existingGovIdResponse)) {
    foreach ((array)($existingGovIdResponse['data'] ?? []) as $rowRaw) {
        $row = (array)$rowRaw;
        $govIdMap[(string)($row['id_type'] ?? '')] = (string)($row['id'] ?? '');
    }
}

$govIdValues = [
    'umid' => $umidNo,
    'pagibig' => $pagibigNo,
    'philhealth' => $philhealthNo,
    'psn' => $psnNo,
    'tin' => $tinNo,
];

foreach ($govIdValues as $idType => $idValue) {
    $existingId = cleanText($govIdMap[$idType] ?? null);

    if ($idValue === null) {
        if ($existingId !== null && isValidUuid($existingId)) {
            apiRequest(
                'DELETE',
                $supabaseUrl . '/rest/v1/person_government_ids?id=eq.' . rawurlencode($existingId),
                $headers
            );
        }
        continue;
    }

    $payload = [
        'person_id' => $employeePersonId,
        'id_type' => $idType,
        'id_value_encrypted' => $idValue,
    ];

    if (!$upsertSingleById('person_government_ids', $existingId, $payload)) {
        redirectWithState('error', 'Failed to save government ID details.', 'personal-information.php');
    }
}

$parentsToSave = [
    [
        'id' => $fatherId,
        'parent_type' => 'father',
        'surname' => $fatherSurname,
        'first_name' => $fatherFirstName,
        'middle_name' => $fatherMiddleName,
        'extension_name' => $fatherNameExtension,
    ],
    [
        'id' => $motherId,
        'parent_type' => 'mother',
        'surname' => $motherSurname,
        'first_name' => $motherFirstName,
        'middle_name' => $motherMiddleName,
        'extension_name' => $motherNameExtension,
    ],
];

foreach ($parentsToSave as $parentData) {
    $hasParentData = ($parentData['surname'] !== null || $parentData['first_name'] !== null || $parentData['middle_name'] !== null || $parentData['extension_name'] !== null);

    if ($hasParentData) {
        $payload = [
            'person_id' => $employeePersonId,
            'parent_type' => $parentData['parent_type'],
            'surname' => $parentData['surname'],
            'first_name' => $parentData['first_name'],
            'middle_name' => $parentData['middle_name'],
            'extension_name' => $parentData['extension_name'],
        ];

        if (!$upsertSingleById('person_parents', $parentData['id'], $payload)) {
            redirectWithState('error', 'Failed to save parent information.', 'personal-information.php');
        }
    } elseif ($parentData['id'] !== null && isValidUuid((string)$parentData['id'])) {
        apiRequest('DELETE', $supabaseUrl . '/rest/v1/person_parents?id=eq.' . rawurlencode((string)$parentData['id']), $headers);
    }
}

apiRequest(
    'DELETE',
    $supabaseUrl . '/rest/v1/person_family_children?person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$childrenPayload = [];
foreach ($childrenNames as $index => $childNameRaw) {
    $childName = $toNullable($childNameRaw, 180);
    $childBirthDate = $toNullable($childrenBirthDates[$index] ?? null, 10);

    if ($childName === null && $childBirthDate === null) {
        continue;
    }

    if ($childName === null) {
        redirectWithState('error', 'Child full name is required when adding a child row.', 'personal-information.php');
    }

    if (!$isValidDate($childBirthDate)) {
        redirectWithState('error', 'One of the child birth dates is invalid.', 'personal-information.php');
    }

    $childrenPayload[] = [
        'person_id' => $employeePersonId,
        'full_name' => $childName,
        'birth_date' => $childBirthDate,
        'sequence_no' => count($childrenPayload) + 1,
    ];
}

if (!empty($childrenPayload)) {
    $childrenInsertResponse = apiRequest('POST', $supabaseUrl . '/rest/v1/person_family_children', array_merge($headers, ['Prefer: return=minimal']), $childrenPayload);
    if (!isSuccessful($childrenInsertResponse)) {
        redirectWithState('error', 'Failed to save children records.', 'personal-information.php');
    }
}

apiRequest(
    'DELETE',
    $supabaseUrl . '/rest/v1/person_educations?person_id=eq.' . rawurlencode((string)$employeePersonId),
    $headers
);

$allowedEducationLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
$educationPayload = [];
foreach ($educationLevels as $index => $levelRaw) {
    $level = strtolower((string)$toNullable($levelRaw, 30));
    $schoolName = $toNullable($educationSchoolNames[$index] ?? null, 220);
    $courseDegree = $toNullable($educationCourses[$index] ?? null, 220);
    $periodFrom = $toNullable($educationFrom[$index] ?? null, 15);
    $periodTo = $toNullable($educationTo[$index] ?? null, 15);
    $highestLevelUnits = $toNullable($educationUnits[$index] ?? null, 140);
    $yearGraduated = $toNullable($educationYearGraduated[$index] ?? null, 20);
    $honorsReceived = $toNullable($educationHonors[$index] ?? null, 180);

    $hasAny = ($level !== '' || $schoolName !== null || $courseDegree !== null || $periodFrom !== null || $periodTo !== null || $highestLevelUnits !== null || $yearGraduated !== null || $honorsReceived !== null);
    if (!$hasAny) {
        continue;
    }

    if ($level === '' || !in_array($level, $allowedEducationLevels, true)) {
        redirectWithState('error', 'One of the educational background rows has an invalid level.', 'personal-information.php');
    }

    $educationPayload[] = [
        'person_id' => $employeePersonId,
        'education_level' => $level,
        'school_name' => $schoolName,
        'course_degree' => $courseDegree,
        'period_from' => $periodFrom,
        'period_to' => $periodTo,
        'highest_level_units' => $highestLevelUnits,
        'year_graduated' => $yearGraduated,
        'honors_received' => $honorsReceived,
        'sequence_no' => count($educationPayload) + 1,
    ];
}

if (!empty($educationPayload)) {
    $educationInsertResponse = apiRequest('POST', $supabaseUrl . '/rest/v1/person_educations', array_merge($headers, ['Prefer: return=minimal']), $educationPayload);
    if (!isSuccessful($educationInsertResponse)) {
        redirectWithState('error', 'Failed to save educational background.', 'personal-information.php');
    }
}

$afterResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

$afterData = [];
if (isSuccessful($afterResponse) && !empty((array)($afterResponse['data'] ?? []))) {
    $afterData = (array)$afterResponse['data'][0];
}

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $employeeUserId,
        'module_name' => 'employee',
        'entity_name' => 'personal_profile',
        'entity_id' => $employeePersonId,
        'action_name' => 'update_profile',
        'old_data' => $beforeData,
        'new_data' => $afterData,
    ]]
);

redirectWithState('success', 'Profile information updated successfully.', 'personal-information.php');
