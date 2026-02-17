<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    return;
}

if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
    redirectWithState('error', 'Invalid request token. Please refresh and try again.', 'apply.php');
}

if ($applicantUserId === '') {
    redirectWithState('error', 'Applicant session is missing. Please login again.', 'apply.php');
}

if (!isValidUuid($applicantUserId)) {
    redirectWithState('error', 'Invalid applicant session context. Please login again.', 'apply.php');
}

$jobId = cleanText($_POST['job_id'] ?? null);
$consent = cleanText($_POST['consent_declaration'] ?? null);

$educationAttainment = cleanText($_POST['education_attainment'] ?? null);
$courseStrand = cleanText($_POST['course_strand'] ?? null);
$schoolInstitution = cleanText($_POST['school_institution'] ?? null);
$recentPosition = cleanText($_POST['recent_position'] ?? null);
$companyOrganization = cleanText($_POST['company_organization'] ?? null);
$yearsExperienceRaw = cleanText($_POST['years_experience'] ?? null);
$certificationsTrainings = cleanText($_POST['certifications_trainings'] ?? null);

$allowedMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
];

$maxFileSizeBytes = 5 * 1024 * 1024;

$requiredUploads = [];

$returnPath = 'apply.php' . ($jobId !== null ? ('?job_id=' . urlencode($jobId)) : '');

if ($jobId === null) {
    redirectWithState('error', 'Missing job reference. Please choose a posting first.', 'job-list.php');
}

if (!isValidUuid($jobId)) {
    redirectWithState('error', 'Invalid job reference. Please choose a posting again.', 'job-list.php');
}

if ($consent !== '1') {
    redirectWithState('error', 'You must confirm the declaration before submitting.', $returnPath);
}

$yearsExperience = null;
if ($yearsExperienceRaw !== null) {
    if (!is_numeric($yearsExperienceRaw) || (float)$yearsExperienceRaw < 0) {
        redirectWithState('error', 'Years of experience must be a valid non-negative number.', $returnPath);
    }
    $yearsExperience = (float)$yearsExperienceRaw;
}

$applicantProfileResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applicant_profiles?select=id,full_name,email&user_id=eq.' . rawurlencode($applicantUserId) . '&limit=1',
    $headers
);

if (!isSuccessful($applicantProfileResponse) || empty((array)($applicantProfileResponse['data'] ?? []))) {
    redirectWithState('error', 'Applicant profile is missing. Please update your profile first.', 'profile.php?edit=true');
}

$applicantProfileId = (string)($applicantProfileResponse['data'][0]['id'] ?? '');
if (!isValidUuid($applicantProfileId)) {
    redirectWithState('error', 'Applicant profile context could not be resolved.', 'profile.php?edit=true');
}

$today = date('Y-m-d');
$jobResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/job_postings?select=id,title,open_date,close_date,posting_status,required_documents'
    . '&id=eq.' . rawurlencode($jobId)
    . '&posting_status=eq.published'
    . '&open_date=lte.' . $today
    . '&close_date=gte.' . $today
    . '&limit=1',
    $headers
);

if (!isSuccessful($jobResponse)) {
    $jobResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/job_postings?select=id,title,open_date,close_date,posting_status'
        . '&id=eq.' . rawurlencode($jobId)
        . '&posting_status=eq.published'
        . '&open_date=lte.' . $today
        . '&close_date=gte.' . $today
        . '&limit=1',
        $headers
    );
}

if (!isSuccessful($jobResponse) || empty((array)($jobResponse['data'] ?? []))) {
    redirectWithState('error', 'This job posting is not open for applications.', 'job-list.php');
}

$jobRow = (array)($jobResponse['data'][0] ?? []);
$requiredDocumentsConfig = normalizeRequiredDocumentConfig($jobRow['required_documents'] ?? null);

foreach ($requiredDocumentsConfig as $documentConfig) {
    if (!((bool)($documentConfig['required'] ?? true))) {
        continue;
    }

    $inputName = (string)($documentConfig['key'] ?? '');
    if ($inputName === '') {
        continue;
    }

    $requiredUploads[$inputName] = [
        'document_type' => (string)($documentConfig['document_type'] ?? 'other'),
        'label' => (string)($documentConfig['label'] ?? 'Required document'),
    ];
}

if (empty($requiredUploads)) {
    foreach (defaultRequiredDocumentConfig() as $documentConfig) {
        if (!((bool)($documentConfig['required'] ?? true))) {
            continue;
        }

        $inputName = (string)($documentConfig['key'] ?? '');
        if ($inputName === '') {
            continue;
        }

        $requiredUploads[$inputName] = [
            'document_type' => (string)($documentConfig['document_type'] ?? 'other'),
            'label' => (string)($documentConfig['label'] ?? 'Required document'),
        ];
    }
}

$duplicateCheckResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applications?select=id'
    . '&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
    . '&job_posting_id=eq.' . rawurlencode($jobId)
    . '&limit=1',
    $headers
);

if (isSuccessful($duplicateCheckResponse) && !empty((array)($duplicateCheckResponse['data'] ?? []))) {
    redirectWithState('error', 'You already submitted an application for this posting.', 'job-view.php?job_id=' . urlencode($jobId));
}

foreach ($requiredUploads as $inputName => $documentConfig) {
    if (!isset($_FILES[$inputName]) || (int)($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $missingLabel = (string)($documentConfig['label'] ?? 'a required document');
        redirectWithState('error', 'Please upload: ' . $missingLabel . '.', $returnPath);
    }

    $fileSize = (int)($_FILES[$inputName]['size'] ?? 0);
    if ($fileSize <= 0 || $fileSize > $maxFileSizeBytes) {
        redirectWithState('error', 'One or more files exceed the 5MB upload limit.', $returnPath);
    }

    $detectedMimeType = detectUploadedMimeType((string)($_FILES[$inputName]['tmp_name'] ?? ''));
    $mimeType = $detectedMimeType ?? (string)($_FILES[$inputName]['type'] ?? '');
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        redirectWithState('error', 'Invalid file type detected. Allowed types: PDF, DOC, DOCX, JPG, PNG.', $returnPath);
    }
}

$applicationRefNo = 'APP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 6));

$applicationCreatePayload = [[
    'applicant_profile_id' => $applicantProfileId,
    'job_posting_id' => $jobId,
    'application_ref_no' => $applicationRefNo,
    'application_status' => 'submitted',
]];

$applicationCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/applications',
    array_merge($headers, ['Prefer: return=representation']),
    $applicationCreatePayload
);

if (!isSuccessful($applicationCreateResponse)) {
    redirectWithState('error', 'Failed to create application record.', $returnPath);
}

$applicationId = (string)($applicationCreateResponse['data'][0]['id'] ?? '');
if (!isValidUuid($applicationId)) {
    redirectWithState('error', 'Application was created but could not be resolved.', $returnPath);
}

$historyCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_status_history',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'application_id' => $applicationId,
        'old_status' => null,
        'new_status' => 'submitted',
        'changed_by' => null,
        'notes' => 'Application submitted by applicant.',
    ]]
);

if (!isSuccessful($historyCreateResponse)) {
    apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
    redirectWithState('error', 'Failed to create application timeline record.', $returnPath);
}

$documentRows = [];
$timestampPrefix = date('YmdHis');

foreach ($requiredUploads as $inputName => $documentConfig) {
    $tmpPath = (string)($_FILES[$inputName]['tmp_name'] ?? '');
    $originalName = (string)($_FILES[$inputName]['name'] ?? 'document');
    $safeFileName = normalizeUploadFilename($originalName);
    $documentType = (string)($documentConfig['document_type'] ?? 'other');
    $storagePath = 'applications/' . $applicantUserId . '/' . $applicationId . '/' . $documentType . '/' . $timestampPrefix . '-' . $safeFileName;
    $mimeType = detectUploadedMimeType($tmpPath) ?? (string)($_FILES[$inputName]['type'] ?? 'application/octet-stream');

    $uploadResponse = uploadFileToSupabaseStorage(
        $supabaseUrl,
        $serviceRoleKey,
        'hris-applications',
        $storagePath,
        $tmpPath,
        $mimeType
    );

    if (!isSuccessful($uploadResponse)) {
        apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
        redirectWithState('error', 'Failed to upload one or more required documents.', $returnPath);
    }

    $documentRows[] = [
        'application_id' => $applicationId,
        'document_type' => $documentType,
        'file_url' => rtrim($supabaseUrl, '/') . '/storage/v1/object/hris-applications/' . $storagePath,
        'file_name' => $safeFileName,
        'mime_type' => $mimeType,
        'file_size_bytes' => (int)($_FILES[$inputName]['size'] ?? 0),
    ];
}

$documentsCreateResponse = apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/application_documents',
    array_merge($headers, ['Prefer: return=minimal']),
    $documentRows
);

if (!isSuccessful($documentsCreateResponse)) {
    apiRequest('DELETE', $supabaseUrl . '/rest/v1/applications?id=eq.' . rawurlencode($applicationId), $headers);
    redirectWithState('error', 'Application submission failed while saving documents.', $returnPath);
}

$notes = [
    'education_attainment' => $educationAttainment,
    'course_strand' => $courseStrand,
    'school_institution' => $schoolInstitution,
    'recent_position' => $recentPosition,
    'company_organization' => $companyOrganization,
    'years_experience' => $yearsExperience,
    'certifications_trainings' => $certificationsTrainings,
];

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/activity_logs',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'actor_user_id' => $applicantUserId,
        'module_name' => 'applications',
        'entity_name' => 'applications',
        'entity_id' => $applicationId,
        'action_name' => 'submit_application',
        'old_data' => null,
        'new_data' => [
            'application_ref_no' => $applicationRefNo,
            'job_posting_id' => $jobId,
            'notes' => $notes,
        ],
        'ip_address' => cleanText($_SERVER['REMOTE_ADDR'] ?? null),
    ]]
);

apiRequest(
    'POST',
    $supabaseUrl . '/rest/v1/notifications',
    array_merge($headers, ['Prefer: return=minimal']),
    [[
        'recipient_user_id' => $applicantUserId,
        'category' => 'application',
        'title' => 'Application submitted successfully',
        'body' => 'Reference ' . $applicationRefNo . ' has been submitted and is now under review.',
        'link_url' => 'applications.php',
        'is_read' => false,
    ]]
);

redirectWithState('success', 'Application submitted successfully. Reference: ' . $applicationRefNo, 'applications.php');
