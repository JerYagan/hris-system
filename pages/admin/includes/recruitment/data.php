<?php

$postingsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_postings?select=id,title,office_id,position_id,description,qualifications,responsibilities,posting_status,open_date,close_date,updated_at,office:offices(office_name),position:job_positions(position_title)&order=updated_at.desc&limit=300',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name&is_active=eq.true&order=office_name.asc&limit=500',
    $headers
);

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title&is_active=eq.true&order=position_title.asc&limit=500',
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
$officeOptions = isSuccessful($officesResponse) ? (array)($officesResponse['data'] ?? []) : [];
$positionOptions = isSuccessful($positionsResponse) ? (array)($positionsResponse['data'] ?? []) : [];

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
