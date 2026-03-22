<?php

require_once dirname(__DIR__, 3) . '/admin/includes/notifications/email.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'profile.php?edit=true');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'profile.php');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'profile.php');
}

$action = strtolower((string)(cleanText($_POST['action'] ?? null) ?? 'update_profile'));

if (!in_array($action, ['update_profile', 'replace_uploaded_file', 'upload_profile_photo', 'request_password_change_code', 'confirm_password_change_code', 'cancel_password_change_code'], true)) {
    redirectWithState('error', 'Unsupported profile action.', 'profile.php');
}

$deleteWorkExperiences = static function (string $personId) use ($supabaseUrl, $headers): array {
    $workLookupResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/person_work_experiences?select=id&person_id=eq.' . rawurlencode($personId) . '&limit=500',
        $headers
    );

    if (!isSuccessful($workLookupResponse)) {
        return $workLookupResponse;
    }

    $workIds = [];
    foreach ((array)($workLookupResponse['data'] ?? []) as $workRow) {
        $workId = cleanText($workRow['id'] ?? null) ?? '';
        if (isValidUuid($workId)) {
            $workIds[] = $workId;
        }
    }

    if ($workIds === []) {
        return ['status' => 204, 'data' => []];
    }

    return apiRequest(
        'DELETE',
        $supabaseUrl
        . '/rest/v1/person_work_experiences?id=in.' . rawurlencode('(' . implode(',', $workIds) . ')')
        . '&person_id=eq.' . rawurlencode($personId),
        array_merge($headers, ['Prefer: return=minimal'])
    );
};

$hasSubmittedWorkExperienceRows = static function (array $positions, array $companies, array $starts, array $ends, array $responsibilities): bool {
    $rowCount = max(count($positions), count($companies), count($starts), count($ends), count($responsibilities));
    for ($index = 0; $index < $rowCount; $index++) {
        $values = [
            cleanText($positions[$index] ?? null),
            cleanText($companies[$index] ?? null),
            cleanText($starts[$index] ?? null),
            cleanText($ends[$index] ?? null),
            cleanText($responsibilities[$index] ?? null),
        ];

        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }
    }

    return false;
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
        redirectWithState('error', 'Please choose a valid profile photo file.', 'profile.php');
    }

    $tmpName = (string)($photoFile['tmp_name'] ?? '');
    $sizeBytes = (int)($photoFile['size'] ?? 0);
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        redirectWithState('error', 'Uploaded profile photo is invalid.', 'profile.php');
    }

    if ($sizeBytes <= 0 || $sizeBytes > (3 * 1024 * 1024)) {
        redirectWithState('error', 'Profile photo must be less than or equal to 3 MB.', 'profile.php');
    }

    $mimeType = (string)(mime_content_type($tmpName) ?: '');
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $extension = $allowedMimeToExt[$mimeType] ?? null;
    if ($extension === null) {
        redirectWithState('error', 'Only JPG, PNG, and WEBP profile photos are allowed.', 'profile.php');
    }

    $beforePhotoResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/people?select=id,profile_photo_url&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
        $headers
    );

    if (!isSuccessful($beforePhotoResponse) || empty((array)($beforePhotoResponse['data'] ?? []))) {
        redirectWithState('error', 'Unable to load profile before photo update.', 'profile.php');
    }

    $beforePhotoRow = (array)$beforePhotoResponse['data'][0];
    $personId = cleanText($beforePhotoRow['id'] ?? null) ?? '';
    if (!isValidUuid($personId)) {
        redirectWithState('error', 'Profile person record is invalid.', 'profile.php');
    }

    $oldPath = cleanText($beforePhotoRow['profile_photo_url'] ?? null);

    $storageRoot = dirname(__DIR__, 4) . '/storage/document';
    $profileDir = $storageRoot . '/profile-photos/' . $personId;
    if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
        redirectWithState('error', 'Unable to prepare profile photo storage.', 'profile.php');
    }

    $fileName = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $relativePath = 'profile-photos/' . $personId . '/' . $fileName;
    $absolutePath = $storageRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        redirectWithState('error', 'Failed to save uploaded profile photo.', 'profile.php');
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
        redirectWithState('error', 'Unable to update profile photo reference.', 'profile.php');
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
            'actor_user_id' => $applicantUserId,
            'module_name' => 'applicant_profile',
            'entity_name' => 'people',
            'entity_id' => $personId,
            'action_name' => 'upload_profile_photo',
            'old_data' => ['profile_photo_url' => $oldPath],
            'new_data' => ['profile_photo_url' => $relativePath],
            'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
        ]]
    );

    unset($_SESSION['applicant_topnav_cache']);

    redirectWithState('success', 'Profile photo updated successfully.', 'profile.php');
}

if ($action === 'request_password_change_code') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        redirectWithState('error', 'Current, new, and confirm password fields are required.', 'profile.php');
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithState('error', 'New password and confirmation do not match.', 'profile.php');
    }

    if ($newPassword === $currentPassword) {
        redirectWithState('error', 'New password must be different from the current password.', 'profile.php');
    }

    $strengthError = $validateStrongPassword($newPassword);
    if ($strengthError !== null) {
        redirectWithState('error', $strengthError, 'profile.php');
    }

    $accountLookup = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
        $headers
    );
    $accountRow = isSuccessful($accountLookup) ? (array)($accountLookup['data'][0] ?? []) : [];
    $email = strtolower(trim((string)($accountRow['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithState('error', 'A valid account email is required for password verification.', 'profile.php');
    }

    if (!$verifyCurrentPassword($email, $currentPassword)) {
        redirectWithState('error', 'Current password is incorrect.', 'profile.php');
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
        redirectWithState('error', 'Email verification is required but SMTP is not configured.', 'profile.php');
    }

    $verificationCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = time() + (10 * 60);

    $_SESSION['applicant_profile_password_change'] = [
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
        unset($_SESSION['applicant_profile_password_change']);
        redirectWithState('error', 'Unable to send verification code email. Please try again.', 'profile.php');
    }

    redirectWithState('success', 'Verification code sent to your email. Enter the code to complete password change.', 'profile.php?password_modal=verify');
}

if ($action === 'cancel_password_change_code') {
    unset($_SESSION['applicant_profile_password_change']);
    redirectWithState('success', 'Pending password change verification was cancelled.', 'profile.php');
}

if ($action === 'confirm_password_change_code') {
    $enteredCode = trim((string)($_POST['verification_code'] ?? ''));
    $pending = (array)($_SESSION['applicant_profile_password_change'] ?? []);

    if ($enteredCode === '' || !preg_match('/^[0-9]{6}$/', $enteredCode)) {
        redirectWithState('error', 'Enter a valid 6-digit verification code.', 'profile.php?password_modal=verify');
    }

    if (empty($pending)) {
        redirectWithState('error', 'No pending password change request. Request a new verification code.', 'profile.php?password_modal=request');
    }

    $expiresAt = (int)($pending['expires_at'] ?? 0);
    if ($expiresAt <= time()) {
        unset($_SESSION['applicant_profile_password_change']);
        redirectWithState('error', 'Verification code expired. Request a new code.', 'profile.php?password_modal=request');
    }

    $attempts = (int)($pending['attempts'] ?? 0);
    $expectedHash = (string)($pending['code_hash'] ?? '');
    if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $enteredCode))) {
        $attempts++;
        if ($attempts >= 5) {
            unset($_SESSION['applicant_profile_password_change']);
            redirectWithState('error', 'Too many invalid verification attempts. Request a new code.', 'profile.php?password_modal=request');
        }

        $pending['attempts'] = $attempts;
        $_SESSION['applicant_profile_password_change'] = $pending;
        redirectWithState('error', 'Invalid verification code.', 'profile.php?password_modal=verify');
    }

    $newPassword = (string)($pending['new_password'] ?? '');
    if ($newPassword === '') {
        unset($_SESSION['applicant_profile_password_change']);
        redirectWithState('error', 'Pending password payload is invalid. Request a new code.', 'profile.php?password_modal=request');
    }

    $resetResponse = apiRequest(
        'PUT',
        $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($applicantUserId),
        $headers,
        [
            'password' => $newPassword,
            'email_confirm' => true,
        ]
    );

    if (!isSuccessful($resetResponse)) {
        redirectWithState('error', 'Failed to update password. Please try again.', 'profile.php');
    }

    apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/user_accounts?id=eq.' . rawurlencode($applicantUserId),
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
            'actor_user_id' => $applicantUserId,
            'module_name' => 'applicant_profile',
            'entity_name' => 'user_accounts',
            'entity_id' => $applicantUserId,
            'action_name' => 'change_password_with_email_verification',
            'old_data' => null,
            'new_data' => ['password_changed' => true],
            'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
        ]]
    );

    unset($_SESSION['applicant_profile_password_change']);
    redirectWithState('success', 'Password changed successfully.', 'profile.php');
}

if ($action === 'replace_uploaded_file') {
    $documentId = cleanText($_POST['document_id'] ?? null);

    if ($documentId === null || !isValidUuid($documentId)) {
        redirectWithState('error', 'Invalid document reference.', 'profile.php');
    }

    if (!isset($_FILES['replacement_file']) || (int)($_FILES['replacement_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectWithState('error', 'Please choose a replacement file before submitting.', 'profile.php');
    }

    $maxFileSizeBytes = 5 * 1024 * 1024;
    $fileSize = (int)($_FILES['replacement_file']['size'] ?? 0);
    if ($fileSize <= 0 || $fileSize > $maxFileSizeBytes) {
        redirectWithState('error', 'Replacement file exceeds the 5MB upload limit.', 'profile.php');
    }

    $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    $tmpPath = (string)($_FILES['replacement_file']['tmp_name'] ?? '');
    $detectedMimeType = detectUploadedMimeType($tmpPath);
    $mimeType = $detectedMimeType ?? (string)($_FILES['replacement_file']['type'] ?? '');
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        redirectWithState('error', 'Invalid replacement file type. Allowed: PDF, DOC, DOCX, JPG, PNG.', 'profile.php');
    }

    $applicantProfileResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/applicant_profiles?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
        $headers
    );

    $applicantProfileId = isSuccessful($applicantProfileResponse)
        ? cleanText($applicantProfileResponse['data'][0]['id'] ?? null)
        : null;

    if ($applicantProfileId === null || !isValidUuid($applicantProfileId)) {
        redirectWithState('error', 'Applicant profile could not be resolved for file update.', 'profile.php');
    }

    $documentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/application_documents?select=id,application_id,document_type,file_name,application:applications(id,applicant_profile_id)'
        . '&id=eq.' . rawurlencode($documentId)
        . '&limit=1',
        $headers
    );

    if (!isSuccessful($documentResponse) || empty((array)($documentResponse['data'] ?? []))) {
        redirectWithState('error', 'Document record not found.', 'profile.php');
    }

    $documentRow = (array)($documentResponse['data'][0] ?? []);
    $applicationRef = (array)($documentRow['application'] ?? []);
    $ownerApplicantProfileId = cleanText($applicationRef['applicant_profile_id'] ?? null);

    if ($ownerApplicantProfileId === null || $ownerApplicantProfileId !== $applicantProfileId) {
        redirectWithState('error', 'You are not allowed to modify this file.', 'profile.php');
    }

    $applicationId = cleanText($documentRow['application_id'] ?? null);
    if ($applicationId === null || !isValidUuid($applicationId)) {
        redirectWithState('error', 'Invalid application context for this file.', 'profile.php');
    }

    $documentType = strtolower((string)(cleanText($documentRow['document_type'] ?? null) ?? 'other'));
    if (!in_array($documentType, ['resume', 'pds', 'transcript', 'certificate', 'id', 'other'], true)) {
        $documentType = 'other';
    }

    $safeFileName = normalizeUploadFilename((string)($_FILES['replacement_file']['name'] ?? 'replacement-file'));
    $storagePath = 'applications/' . $applicantUserId . '/' . $applicationId . '/' . $documentType . '/' . date('YmdHis') . '-replace-' . $safeFileName;

    $uploadResponse = uploadFileToSupabaseStorage(
        $supabaseUrl,
        $serviceRoleKey,
        'hris-applications',
        $storagePath,
        $tmpPath,
        $mimeType
    );

    if (!isSuccessful($uploadResponse)) {
        redirectWithState('error', 'Failed to upload replacement file.', 'profile.php');
    }

    $updateResponse = apiRequest(
        'PATCH',
        $supabaseUrl . '/rest/v1/application_documents?id=eq.' . rawurlencode($documentId),
        array_merge($headers, ['Prefer: return=minimal']),
        [
            'file_url' => rtrim($supabaseUrl, '/') . '/storage/v1/object/hris-applications/' . $storagePath,
            'file_name' => $safeFileName,
            'mime_type' => $mimeType,
            'file_size_bytes' => $fileSize,
            'uploaded_at' => date('c'),
        ]
    );

    if (!isSuccessful($updateResponse)) {
        redirectWithState('error', 'Replacement file uploaded but metadata update failed.', 'profile.php');
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $applicantUserId,
            'module_name' => 'applicant_profile',
            'entity_name' => 'application_documents',
            'entity_id' => $documentId,
            'action_name' => 'replace_uploaded_file',
            'old_data' => null,
            'new_data' => [
                'application_id' => $applicationId,
                'document_type' => $documentType,
                'file_name' => $safeFileName,
            ],
            'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
        ]]
    );

    redirectWithState('success', 'Uploaded file has been replaced successfully.', 'profile.php');
}

if (($action === 'update_profile') && (string)($_POST['deferred_sections_ready'] ?? '0') !== '1') {
    redirectWithState('error', 'Please wait for the education and work experience sections to finish loading before saving.', 'profile.php?edit=true');
}

$fullName = cleanText($_POST['full_name'] ?? null);
$email = cleanText($_POST['email'] ?? null);
$mobileNo = cleanText($_POST['mobile_no'] ?? null);
$currentAddress = cleanText($_POST['current_address'] ?? null);
$trainingHoursRaw = cleanText($_POST['training_hours_completed'] ?? null);
$dateOfBirth = trim((string)(cleanText($_POST['date_of_birth'] ?? null) ?? ''));
$placeOfBirth = cleanText($_POST['place_of_birth'] ?? null);
$sexAtBirth = strtolower(trim((string)(cleanText($_POST['sex_at_birth'] ?? null) ?? '')));
$civilStatus = cleanText($_POST['civil_status'] ?? null);
$citizenshipStatus = strtolower(trim((string)(cleanText($_POST['citizenship_status'] ?? null) ?? 'filipino')));
$citizenshipAcquisition = strtolower(trim((string)(cleanText($_POST['citizenship_acquisition'] ?? null) ?? '')));
$dualCitizenshipCountry = cleanText($_POST['dual_citizenship_country'] ?? null);

$normalizedEmail = strtolower(trim((string)$email));

$trainingHoursCompleted = 0.0;
if ($trainingHoursRaw !== null && $trainingHoursRaw !== '') {
    if (!is_numeric($trainingHoursRaw) || (float)$trainingHoursRaw < 0) {
        redirectWithState('error', 'Training hours must be a valid non-negative number.', 'profile.php?edit=true');
    }

    $trainingHoursCompleted = (float)$trainingHoursRaw;
}

if ($fullName === null) {
    redirectWithState('error', 'Full name is required.', 'profile.php?edit=true');
}

if ($email === null || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid email address.', 'profile.php?edit=true');
}

if ($mobileNo !== null && !preg_match('/^[0-9+()\-\s]{7,20}$/', $mobileNo)) {
    redirectWithState('error', 'Please provide a valid mobile number format.', 'profile.php?edit=true');
}

if ($dateOfBirth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
    redirectWithState('error', 'Date of birth must be a valid date.', 'profile.php?edit=true');
}

if ($sexAtBirth !== '' && !in_array($sexAtBirth, ['male', 'female'], true)) {
    redirectWithState('error', 'Sex must be either male or female.', 'profile.php?edit=true');
}

if (!in_array($citizenshipStatus, ['filipino', 'dual'], true)) {
    $citizenshipStatus = 'filipino';
}

if ($citizenshipAcquisition !== '' && !in_array($citizenshipAcquisition, ['birth', 'naturalization'], true)) {
    redirectWithState('error', 'Citizenship acquisition must be either by birth or by naturalization.', 'profile.php?edit=true');
}

$citizenshipLabel = $citizenshipStatus === 'dual' ? 'Dual Citizenship' : 'Filipino';
if ($citizenshipAcquisition === 'birth') {
    $citizenshipLabel .= ' - By Birth';
} elseif ($citizenshipAcquisition === 'naturalization') {
    $citizenshipLabel .= ' - By Naturalization';
}

$nameParts = preg_split('/\s+/', trim($fullName)) ?: [];
$firstName = trim((string)($nameParts[0] ?? ''));
$surname = trim((string)($nameParts[count($nameParts) - 1] ?? ''));

if ($firstName === '') {
    redirectWithState('error', 'Unable to parse the first name from your full name.', 'profile.php?edit=true');
}

if ($surname === '') {
    $surname = $firstName;
}

$existingAccountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email&id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
    $headers
);

if (!isSuccessful($existingAccountResponse) || empty((array)($existingAccountResponse['data'] ?? []))) {
    redirectWithState('error', 'Unable to load your account details before saving the profile.', 'profile.php?edit=true');
}

$existingAccountRow = (array)($existingAccountResponse['data'][0] ?? []);
$existingAccountEmail = strtolower(trim((string)($existingAccountRow['email'] ?? '')));
$emailChanged = $existingAccountEmail !== '' && $existingAccountEmail !== $normalizedEmail;

if ($emailChanged) {
    $authEmailUpdateResponse = apiRequest(
        'PUT',
        $supabaseUrl . '/auth/v1/admin/users/' . rawurlencode($applicantUserId),
        $headers,
        [
            'email' => $normalizedEmail,
            'email_confirm' => true,
        ]
    );

    if (!isSuccessful($authEmailUpdateResponse)) {
        $authError = strtolower((string)($authEmailUpdateResponse['raw'] ?? ''));
        if (str_contains($authError, 'already') || str_contains($authError, 'exists') || str_contains($authError, 'duplicate')) {
            redirectWithState('error', 'That email address is already in use by another account.', 'profile.php?edit=true');
        }

        redirectWithState('error', 'Failed to update your account email. Profile changes were not saved.', 'profile.php?edit=true');
    }
}

$userAccountUpsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/user_accounts?on_conflict=id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'id' => $applicantUserId,
        'email' => $normalizedEmail,
        'updated_at' => gmdate('c'),
    ]]
);

if (!isSuccessful($userAccountUpsertResponse)) {
    redirectWithState('error', 'Failed to keep your account email in sync. Please try again.', 'profile.php?edit=true');
}

$peopleUpsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/people?on_conflict=user_id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'user_id' => $applicantUserId,
        'first_name' => $firstName,
        'surname' => $surname,
        'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
        'place_of_birth' => $placeOfBirth,
        'sex_at_birth' => $sexAtBirth !== '' ? $sexAtBirth : null,
        'civil_status' => $civilStatus,
        'citizenship' => $citizenshipLabel,
        'dual_citizenship' => $citizenshipStatus === 'dual',
        'dual_citizenship_country' => $citizenshipStatus === 'dual' ? $dualCitizenshipCountry : null,
        'mobile_no' => $mobileNo,
        'personal_email' => $normalizedEmail,
        'updated_at' => gmdate('c'),
    ]]
);

if (!isSuccessful($peopleUpsertResponse)) {
    redirectWithState('error', 'Failed to update personal profile details.', 'profile.php?edit=true');
}

$applicantProfileUpsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/applicant_profiles?on_conflict=user_id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'user_id' => $applicantUserId,
        'full_name' => $fullName,
        'email' => $normalizedEmail,
        'mobile_no' => $mobileNo,
        'current_address' => $currentAddress,
        'training_hours_completed' => $trainingHoursCompleted,
    ]]
);

if (!isSuccessful($applicantProfileUpsertResponse)) {
    redirectWithState('error', 'Failed to update applicant profile details.', 'profile.php?edit=true');
}

$personLookupResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/people?select=id&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
    $headers
);

$personId = isSuccessful($personLookupResponse)
    ? (string)($personLookupResponse['data'][0]['id'] ?? '')
    : '';

if (isValidUuid($personId)) {
    $existingSpouseDelete = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/person_family_spouses?person_id=eq.' . rawurlencode($personId),
        $headers
    );

    if (!isSuccessful($existingSpouseDelete)) {
        redirectWithState('error', 'Failed to refresh spouse background records.', 'profile.php?edit=true');
    }

    $spouseFirstNames = (array)($_POST['spouse_first_name'] ?? []);
    $spouseSurnames = (array)($_POST['spouse_surname'] ?? []);
    $spouseMiddleNames = (array)($_POST['spouse_middle_name'] ?? []);
    $spouseExtensions = (array)($_POST['spouse_extension_name'] ?? []);
    $spouseOccupations = (array)($_POST['spouse_occupation'] ?? []);
    $spouseEmployers = (array)($_POST['spouse_employer'] ?? []);
    $spouseAddresses = (array)($_POST['spouse_business_address'] ?? []);
    $spouseTelephones = (array)($_POST['spouse_telephone_no'] ?? []);

    $spouseRows = [];
    $spouseCount = max(
        count($spouseFirstNames),
        count($spouseSurnames),
        count($spouseMiddleNames),
        count($spouseExtensions),
        count($spouseOccupations),
        count($spouseEmployers),
        count($spouseAddresses),
        count($spouseTelephones)
    );

    for ($index = 0; $index < $spouseCount; $index++) {
        $first = cleanText($spouseFirstNames[$index] ?? null);
        $surname = cleanText($spouseSurnames[$index] ?? null);
        $middle = cleanText($spouseMiddleNames[$index] ?? null);
        $extension = cleanText($spouseExtensions[$index] ?? null);
        $occupation = cleanText($spouseOccupations[$index] ?? null);
        $employer = cleanText($spouseEmployers[$index] ?? null);
        $businessAddress = cleanText($spouseAddresses[$index] ?? null);
        $telephone = cleanText($spouseTelephones[$index] ?? null);

        if ($first === null && $surname === null && $occupation === null && $employer === null) {
            continue;
        }

        $spouseRows[] = [
            'person_id' => $personId,
            'surname' => $surname,
            'first_name' => $first,
            'middle_name' => $middle,
            'extension_name' => $extension,
            'occupation' => $occupation,
            'employer_business_name' => $employer,
            'business_address' => $businessAddress,
            'telephone_no' => $telephone,
            'sequence_no' => count($spouseRows) + 1,
        ];
    }

    if (!empty($spouseRows)) {
        $spouseInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_family_spouses',
            array_merge($headers, ['Prefer: return=minimal']),
            $spouseRows
        );

        if (!isSuccessful($spouseInsertResponse)) {
            redirectWithState('error', 'Failed to save spouse background entries.', 'profile.php?edit=true');
        }
    }

    $existingEducationDelete = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/person_educations?person_id=eq.' . rawurlencode($personId),
        $headers
    );

    if (!isSuccessful($existingEducationDelete)) {
        redirectWithState('error', 'Failed to refresh education background records.', 'profile.php?edit=true');
    }

    $educationLevels = (array)($_POST['education_level'] ?? []);
    $educationSchools = (array)($_POST['education_school_name'] ?? []);
    $educationCourses = (array)($_POST['education_course_degree'] ?? []);
    $educationFrom = (array)($_POST['education_period_from'] ?? []);
    $educationTo = (array)($_POST['education_period_to'] ?? []);
    $educationUnits = (array)($_POST['education_units'] ?? []);
    $educationYear = (array)($_POST['education_year_graduated'] ?? []);
    $educationHonors = (array)($_POST['education_honors'] ?? []);

    $allowedLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
    $educationRows = [];
    $educationCount = max(
        count($educationLevels),
        count($educationSchools),
        count($educationCourses),
        count($educationFrom),
        count($educationTo),
        count($educationUnits),
        count($educationYear),
        count($educationHonors)
    );

    for ($index = 0; $index < $educationCount; $index++) {
        $level = strtolower((string)(cleanText($educationLevels[$index] ?? null) ?? ''));
        $school = cleanText($educationSchools[$index] ?? null);
        $course = cleanText($educationCourses[$index] ?? null);
        $periodFrom = cleanText($educationFrom[$index] ?? null);
        $periodTo = cleanText($educationTo[$index] ?? null);
        $units = cleanText($educationUnits[$index] ?? null);
        $yearGraduated = cleanText($educationYear[$index] ?? null);
        $honors = cleanText($educationHonors[$index] ?? null);

        if ($level === '' && $school === null && $course === null && $yearGraduated === null) {
            continue;
        }

        if (!in_array($level, $allowedLevels, true)) {
            $level = 'college';
        }

        if (in_array($level, ['elementary', 'secondary'], true)) {
            $course = null;
        }

        $educationRows[] = [
            'person_id' => $personId,
            'education_level' => $level,
            'school_name' => $school,
            'course_degree' => $course,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'highest_level_units' => $units,
            'year_graduated' => $yearGraduated,
            'honors_received' => $honors,
            'sequence_no' => count($educationRows) + 1,
        ];
    }

    if (!empty($educationRows)) {
        $educationInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_educations',
            array_merge($headers, ['Prefer: return=minimal']),
            $educationRows
        );

        if (!isSuccessful($educationInsertResponse)) {
            redirectWithState('error', 'Failed to save education background entries.', 'profile.php?edit=true');
        }
    }

    $workPositionEntries = (array)($_POST['work_position_title_entry'] ?? []);
    $workCompanyEntries = (array)($_POST['work_company_name_entry'] ?? []);
    $workStartEntries = (array)($_POST['work_start_date_entry'] ?? []);
    $workEndEntries = (array)($_POST['work_end_date_entry'] ?? []);
    $workResponsibilityEntries = (array)($_POST['work_responsibilities_entry'] ?? []);

    $supportsWorkExperience = function_exists('applicantProfileTableExists')
        ? applicantProfileTableExists($supabaseUrl, $headers, 'person_work_experiences')
        : true;
    if (!$supportsWorkExperience && $hasSubmittedWorkExperienceRows(
        $workPositionEntries,
        $workCompanyEntries,
        $workStartEntries,
        $workEndEntries,
        $workResponsibilityEntries
    )) {
        redirectWithState('error', 'Work experience entries cannot be saved because the required table is not available in the current deployment.', 'profile.php?edit=true');
    }

    if ($supportsWorkExperience) {
        $existingWorkDelete = $deleteWorkExperiences($personId);

        if (!isSuccessful($existingWorkDelete)) {
            redirectWithState('error', 'Failed to refresh work experience records.', 'profile.php?edit=true');
        }
    }

    $workRows = [];
    $workCount = max(
        count($workPositionEntries),
        count($workCompanyEntries),
        count($workStartEntries),
        count($workEndEntries),
        count($workResponsibilityEntries)
    );

    for ($index = 0; $index < $workCount; $index++) {
        $positionTitle = cleanText($workPositionEntries[$index] ?? null);
        $companyName = cleanText($workCompanyEntries[$index] ?? null);
        $startDate = cleanText($workStartEntries[$index] ?? null);
        $endDate = cleanText($workEndEntries[$index] ?? null);
        $responsibilities = cleanText($workResponsibilityEntries[$index] ?? null);

        if ($positionTitle === null && $companyName === null && $startDate === null && $responsibilities === null) {
            continue;
        }

        if ($startDate === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            redirectWithState('error', 'Each work experience entry requires a valid start date.', 'profile.php?edit=true');
        }

        if ($endDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            redirectWithState('error', 'Work experience end date must be a valid date.', 'profile.php?edit=true');
        }

        if ($endDate !== null && $endDate < $startDate) {
            redirectWithState('error', 'Work experience end date cannot be earlier than start date.', 'profile.php?edit=true');
        }

        $workRows[] = [
            'person_id' => $personId,
            'inclusive_date_from' => $startDate,
            'inclusive_date_to' => $endDate,
            'position_title' => $positionTitle,
            'office_company' => $companyName,
            'achievements' => $responsibilities,
            'sequence_no' => count($workRows) + 1,
        ];
    }

    if ($supportsWorkExperience && !empty($workRows)) {
        $workInsertResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/person_work_experiences',
            array_merge($headers, ['Prefer: return=minimal']),
            $workRows
        );

        if (!isSuccessful($workInsertResponse)) {
            redirectWithState('error', 'Failed to save work experience entries.', 'profile.php?edit=true');
        }
    }
}

$_SESSION['user']['email'] = $normalizedEmail;
$_SESSION['user']['name'] = $fullName;

unset($_SESSION['applicant_topnav_cache']);

$ipAddress = cleanText($_SERVER['REMOTE_ADDR'] ?? null);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $applicantUserId,
        'module_name' => 'applicant_profile',
        'entity_name' => 'people',
        'entity_id' => $personId !== '' ? $personId : null,
        'action_name' => 'update_profile',
        'old_data' => null,
        'new_data' => [
            'full_name' => $fullName,
            'email' => $normalizedEmail,
            'mobile_no' => $mobileNo,
            'current_address' => $currentAddress,
            'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
            'place_of_birth' => $placeOfBirth,
            'sex_at_birth' => $sexAtBirth !== '' ? $sexAtBirth : null,
            'civil_status' => $civilStatus,
            'citizenship' => $citizenshipLabel,
            'dual_citizenship_country' => $citizenshipStatus === 'dual' ? $dualCitizenshipCountry : null,
            'spouse_entries' => count((array)($_POST['spouse_first_name'] ?? [])),
            'education_entries' => count((array)($_POST['education_level'] ?? [])),
            'work_entries' => count((array)($_POST['work_position_title_entry'] ?? [])),
        ],
        'ip_address' => $ipAddress,
    ]]
);

redirectWithState('success', 'Profile updated successfully.', 'profile.php');
