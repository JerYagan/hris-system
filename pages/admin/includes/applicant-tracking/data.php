<?php

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job:job_postings(title),applicant:applicant_profiles(full_name,email,user_id)&order=updated_at.desc&limit=500',
    $headers
);

$recentInterviewsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_interviews?select=application_id,scheduled_at,interview_stage,interviewer:user_accounts(email)&order=scheduled_at.desc&limit=1000',
    $headers
);

$applications = isSuccessful($applicationsResponse) ? $applicationsResponse['data'] : [];
$recentInterviews = isSuccessful($recentInterviewsResponse) ? $recentInterviewsResponse['data'] : [];

$interviewMap = [];
foreach ($recentInterviews as $interview) {
    $applicationId = (string)($interview['application_id'] ?? '');
    if ($applicationId === '' || isset($interviewMap[$applicationId])) {
        continue;
    }

    $interviewMap[$applicationId] = [
        'scheduled_at' => (string)($interview['scheduled_at'] ?? ''),
        'stage' => (string)($interview['interview_stage'] ?? ''),
        'interviewer' => (string)($interview['interviewer']['email'] ?? ''),
    ];
}
