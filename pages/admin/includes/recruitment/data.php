<?php

$postingsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,posting_status,open_date,close_date,updated_at,office:offices(office_name)&order=updated_at.desc&limit=300',
    $headers
);

$applicationsCountResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=job_posting_id',
    array_merge($headers, ['Prefer: count=exact'])
);

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_status,submitted_at,job:job_postings(title),applicant:applicant_profiles(full_name,email)&order=submitted_at.desc&limit=300',
    $headers
);

$postings = isSuccessful($postingsResponse) ? $postingsResponse['data'] : [];
$applications = isSuccessful($applicationsResponse) ? $applicationsResponse['data'] : [];

$applicationCountsByPosting = [];
if (!empty($applicationsCountResponse['data']) && is_array($applicationsCountResponse['data'])) {
    foreach ($applicationsCountResponse['data'] as $appRow) {
        $postingId = (string)($appRow['job_posting_id'] ?? '');
        if ($postingId === '') {
            continue;
        }
        $applicationCountsByPosting[$postingId] = (int)($applicationCountsByPosting[$postingId] ?? 0) + 1;
    }
}

$applicantStatusCounts = [
    'registered' => 0,
    'approved' => 0,
    'pending' => 0,
];

foreach ($applications as $application) {
    $applicantStatusCounts['registered']++;

    $status = strtolower((string)($application['application_status'] ?? 'submitted'));
    if (in_array($status, ['offer', 'hired', 'shortlisted'], true)) {
        $applicantStatusCounts['approved']++;
    }
    if (in_array($status, ['submitted', 'screening', 'interview'], true)) {
        $applicantStatusCounts['pending']++;
    }
}
