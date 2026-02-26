<?php

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job:job_postings(title),applicant:applicant_profiles(full_name,email,mobile_no,current_address)&order=submitted_at.desc&limit=1000',
    $headers
);

$feedbackResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at&order=provided_at.desc&limit=1000',
    $headers
);

$statusHistoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_status_history?select=application_id,notes,created_at&order=created_at.desc&limit=2000',
    $headers
);

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_documents?select=application_id,document_type,file_name,file_url,uploaded_at&order=uploaded_at.desc&limit=4000',
    $headers
);

$applications = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];
$feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];
$statusHistoryRows = isSuccessful($statusHistoryResponse) ? (array)($statusHistoryResponse['data'] ?? []) : [];
$documentRows = isSuccessful($documentsResponse) ? (array)($documentsResponse['data'] ?? []) : [];

$feedbackMap = [];
foreach ($feedbackRows as $feedback) {
    $applicationId = (string)($feedback['application_id'] ?? '');
    if ($applicationId === '' || isset($feedbackMap[$applicationId])) {
        continue;
    }

    $feedbackMap[$applicationId] = [
        'decision' => (string)($feedback['decision'] ?? ''),
        'feedback_text' => (string)($feedback['feedback_text'] ?? ''),
        'provided_at' => (string)($feedback['provided_at'] ?? ''),
    ];
}

$basisMap = [];
foreach ($statusHistoryRows as $historyRow) {
    $applicationId = (string)($historyRow['application_id'] ?? '');
    if ($applicationId === '' || isset($basisMap[$applicationId])) {
        continue;
    }

    $notes = trim((string)($historyRow['notes'] ?? ''));
    $basisValue = '-';
    if ($notes !== '') {
        $parts = explode('|', $notes, 2);
        $basisValue = trim((string)($parts[0] ?? ''));
        if ($basisValue === '') {
            $basisValue = '-';
        }
    }

    $basisMap[$applicationId] = $basisValue;
}

$documentsByApplication = [];
$normalizeDocumentUrl = static function (string $rawUrl) use ($supabaseUrl): string {
    $url = trim($rawUrl);
    if ($url === '') {
        return '';
    }

    $localDocumentRoot = __DIR__ . '/../../../../storage/document';
    $resolveLocalDocumentPath = static function (string $rawPath) use ($localDocumentRoot): string {
        $normalized = str_replace('\\', '/', trim($rawPath));
        $normalized = preg_replace('#^https?://[^/]+/storage/v1/object/public/[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^storage/v1/object/public/[^/]+/#i', '', $normalized);
        $normalized = preg_replace('#^document/#i', '', ltrim((string)$normalized, '/'));

        $segments = array_values(array_filter(explode('/', (string)$normalized), static fn(string $segment): bool => $segment !== ''));
        if (empty($segments)) {
            return '';
        }

        $candidate = $localDocumentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, implode('/', $segments));
        if (is_file($candidate)) {
            return '/hris-system/storage/document/' . implode('/', array_map('rawurlencode', $segments));
        }

        $basename = end($segments);
        if (is_string($basename) && $basename !== '') {
            $basenameFile = $localDocumentRoot . DIRECTORY_SEPARATOR . $basename;
            if (is_file($basenameFile)) {
                return '/hris-system/storage/document/' . rawurlencode($basename);
            }
        }

        return '';
    };

    $resolvedLocal = $resolveLocalDocumentPath($url);
    if ($resolvedLocal !== '') {
        return $resolvedLocal;
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $url = ltrim($url, './');
    if (str_starts_with($url, '/')) {
        return $url;
    }

    if (str_starts_with($url, 'storage/v1/object/public/')) {
        $resolvedLocal = $resolveLocalDocumentPath($url);
        if ($resolvedLocal !== '') {
            return $resolvedLocal;
        }

        return rtrim((string)$supabaseUrl, '/') . '/' . $url;
    }

    if (str_starts_with($url, 'document/')) {
        $segments = array_values(array_filter(explode('/', $url), static fn(string $segment): bool => $segment !== ''));
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        return '/hris-system/storage/' . $encodedPath;
    }

    if (str_starts_with($url, 'storage/document/')) {
        $relative = preg_replace('#^storage/document/#', '', $url);
        $segments = array_values(array_filter(explode('/', (string)$relative), static fn(string $segment): bool => $segment !== ''));
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        return '/hris-system/storage/document/' . $encodedPath;
    }

    return '/hris-system/storage/document/' . rawurlencode($url);
};

foreach ($documentRows as $documentRow) {
    $applicationId = (string)($documentRow['application_id'] ?? '');
    if ($applicationId === '') {
        continue;
    }

    if (!isset($documentsByApplication[$applicationId])) {
        $documentsByApplication[$applicationId] = [];
    }

    $normalizedUrl = $normalizeDocumentUrl((string)($documentRow['file_url'] ?? ''));
    $documentsByApplication[$applicationId][] = [
        'type' => strtolower(trim((string)($documentRow['document_type'] ?? 'other'))),
        'name' => trim((string)($documentRow['file_name'] ?? 'Unnamed Document')),
        'url' => $normalizedUrl,
        'download_url' => $normalizedUrl,
        'uploaded_at' => (string)($documentRow['uploaded_at'] ?? ''),
    ];
}

$applicationDocumentStatus = static function (string $applicationStatus): array {
    $key = strtolower(trim($applicationStatus));
    if (in_array($key, ['shortlisted', 'offer', 'hired'], true)) {
        return ['Verified', 'bg-emerald-100 text-emerald-800'];
    }
    if (in_array($key, ['rejected', 'withdrawn'], true)) {
        return ['Rejected', 'bg-rose-100 text-rose-800'];
    }

    return ['Pending', 'bg-amber-100 text-amber-800'];
};

$extractStructuredInputs = static function (string $feedbackText): array {
    if ($feedbackText === '') {
        return [];
    }

    $decoded = json_decode($feedbackText, true);
    if (!is_array($decoded)) {
        return [];
    }

    return [
        'eligibility' => cleanText($decoded['eligibility'] ?? $decoded['eligibility_type'] ?? null),
        'education_years' => $decoded['education_years'] ?? $decoded['years_in_college'] ?? null,
        'training_hours' => $decoded['training_hours'] ?? $decoded['hours_of_training'] ?? null,
        'experience_years' => $decoded['experience_years'] ?? $decoded['years_of_experience'] ?? null,
        'pds_summary' => cleanText($decoded['pds_summary'] ?? null),
        'career_experience' => cleanText($decoded['career_experience'] ?? $decoded['career_summary'] ?? null),
        'work_experience' => cleanText($decoded['work_experience'] ?? null),
    ];
};

$applicantProfileDataset = [];

$registeredApplicantsCount = count($applications);
$hiredCount = 0;
$pendingDecisionCount = 0;

if (!function_exists('applicantStatusPill')) {
    function applicantStatusPill(string $status): array
    {
        $key = strtolower(trim($status));
        return match ($key) {
            'submitted' => ['Applied', 'bg-blue-100 text-blue-800'],
            'screening' => ['Verified', 'bg-indigo-100 text-indigo-800'],
            'interview' => ['Interview', 'bg-amber-100 text-amber-800'],
            'shortlisted' => ['Evaluation', 'bg-violet-100 text-violet-800'],
            'offer' => ['For Approval', 'bg-cyan-100 text-cyan-800'],
            'hired' => ['Hired', 'bg-emerald-100 text-emerald-800'],
            'rejected', 'withdrawn' => ['Rejected', 'bg-rose-100 text-rose-800'],
            default => ['Applied', 'bg-slate-100 text-slate-700'],
        };
    }
}

if (!function_exists('feedbackDecisionLabel')) {
    function feedbackDecisionLabel(string $decision): string
    {
        $key = strtolower(trim($decision));

        if ($key === 'for_next_step') {
            return 'Approve for Next Stage';
        }

        if ($key === 'rejected') {
            return 'Disqualify Application';
        }

        if ($key === 'on_hold') {
            return 'Return for Compliance';
        }

        if ($key === 'hired') {
            return 'Approve for Next Stage';
        }

        return '-';
    }
}

if (!function_exists('recommendationFromStatus')) {
    function recommendationFromStatus(string $status): array
    {
        $key = strtolower(trim($status));

        if (in_array($key, ['shortlisted', 'offer', 'hired'], true)) {
            return ['Recommend Proceed', '92%', 'bg-emerald-100 text-emerald-800', 'Approve for Next Stage'];
        }

        if (in_array($key, ['rejected', 'withdrawn'], true)) {
            return ['Recommend Disqualify', '89%', 'bg-rose-100 text-rose-800', 'Disqualify Application'];
        }

        if ($key === 'interview') {
            return ['Recommend Proceed', '78%', 'bg-emerald-100 text-emerald-800', 'Approve for Next Stage'];
        }

        return ['Recommend Further Review', '74%', 'bg-blue-100 text-blue-800', 'Return for Compliance'];
    }
}

foreach ($applications as $application) {
    $statusValue = strtolower((string)($application['application_status'] ?? 'submitted'));

    if ($statusValue === 'hired') {
        $hiredCount++;
    }

    if (in_array($statusValue, ['submitted', 'screening', 'interview', 'shortlisted', 'offer'], true)) {
        $pendingDecisionCount++;
    }

    $applicationId = (string)($application['id'] ?? '');
    $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
    $feedback = $feedbackMap[$applicationId] ?? null;

    $feedbackText = (string)($feedback['feedback_text'] ?? '');
    $structuredInputs = $extractStructuredInputs($feedbackText);
    $documents = (array)($documentsByApplication[$applicationId] ?? []);
    [$documentStatusLabel, $documentStatusClass] = $applicationDocumentStatus($statusValue);

    foreach ($documents as $index => $document) {
        $documentUploadedAt = trim((string)($document['uploaded_at'] ?? ''));
        $documents[$index]['uploaded_label'] = $documentUploadedAt !== ''
            ? date('M d, Y', strtotime($documentUploadedAt))
            : '-';
        $documents[$index]['status_label'] = $documentStatusLabel;
        $documents[$index]['status_class'] = $documentStatusClass;
    }

    $requiredDocumentState = [
        'pds' => false,
        'wes' => false,
        'eligibility' => false,
        'transcript' => false,
    ];

    foreach ($documents as $document) {
        $documentType = strtolower((string)($document['type'] ?? ''));
        if ($documentType === 'pds') {
            $requiredDocumentState['pds'] = true;
        }
        if ($documentType === 'transcript') {
            $requiredDocumentState['transcript'] = true;
            $requiredDocumentState['wes'] = true;
        }
        if (in_array($documentType, ['id', 'certificate', 'eligibility', 'license'], true)) {
            $requiredDocumentState['eligibility'] = true;
        }
    }

    $applicantProfileDataset[$applicationId] = [
        'application_ref_no' => (string)($application['application_ref_no'] ?? '-'),
        'applicant_mobile' => (string)($application['applicant']['mobile_no'] ?? '-'),
        'applicant_address' => (string)($application['applicant']['current_address'] ?? '-'),
        'pds_summary' => (string)($structuredInputs['pds_summary'] ?? ''),
        'career_experience' => (string)($structuredInputs['career_experience'] ?? ''),
        'work_experience' => (string)($structuredInputs['work_experience'] ?? ''),
        'eligibility' => (string)($structuredInputs['eligibility'] ?? ''),
        'education_years' => is_numeric($structuredInputs['education_years'] ?? null) ? (float)$structuredInputs['education_years'] : null,
        'training_hours' => is_numeric($structuredInputs['training_hours'] ?? null) ? (float)$structuredInputs['training_hours'] : null,
        'experience_years' => is_numeric($structuredInputs['experience_years'] ?? null) ? (float)$structuredInputs['experience_years'] : null,
        'required_docs' => $requiredDocumentState,
        'documents' => $documents,
    ];
}
