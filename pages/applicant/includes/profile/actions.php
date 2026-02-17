<?php

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

$action = cleanText($_POST['action'] ?? null) ?? 'update_profile';

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

$fullName = cleanText($_POST['full_name'] ?? null);
$email = cleanText($_POST['email'] ?? null);
$mobileNo = cleanText($_POST['mobile_no'] ?? null);
$currentAddress = cleanText($_POST['current_address'] ?? null);

if ($fullName === null) {
    redirectWithState('error', 'Full name is required.', 'profile.php?edit=true');
}

if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithState('error', 'Please provide a valid email address.', 'profile.php?edit=true');
}

if ($mobileNo !== null && !preg_match('/^[0-9+()\-\s]{7,20}$/', $mobileNo)) {
    redirectWithState('error', 'Please provide a valid mobile number format.', 'profile.php?edit=true');
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

$peopleUpsertResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/people?on_conflict=user_id',
    array_merge($headers, ['Prefer: resolution=merge-duplicates,return=minimal']),
    [[
        'user_id' => $applicantUserId,
        'first_name' => $firstName,
        'surname' => $surname,
        'mobile_no' => $mobileNo,
        'personal_email' => strtolower($email),
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
        'email' => strtolower($email),
        'mobile_no' => $mobileNo,
        'current_address' => $currentAddress,
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
}

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
            'email' => strtolower($email),
            'mobile_no' => $mobileNo,
            'current_address' => $currentAddress,
            'spouse_entries' => count((array)($_POST['spouse_first_name'] ?? [])),
            'education_entries' => count((array)($_POST['education_level'] ?? [])),
        ],
        'ip_address' => $ipAddress,
    ]]
);

redirectWithState('success', 'Profile updated successfully.', 'profile.php');
