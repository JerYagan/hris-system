<?php

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job:job_postings(title,plantilla_item_no),applicant:applicant_profiles(full_name,email,user_id)&order=updated_at.desc&limit=500',
    $headers
);

$recentInterviewsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_interviews?select=application_id,scheduled_at,interview_stage,result,remarks,interviewer:user_accounts(email)&order=scheduled_at.desc&limit=1000',
    $headers
);

$feedbackResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at&order=provided_at.desc&limit=1000',
    $headers
);

$applications = isSuccessful($applicationsResponse) ? $applicationsResponse['data'] : [];
$recentInterviews = isSuccessful($recentInterviewsResponse) ? $recentInterviewsResponse['data'] : [];
$feedbackRows = isSuccessful($feedbackResponse) ? $feedbackResponse['data'] : [];

$applicantUserIds = [];
foreach ((array)$applications as $application) {
    $userId = strtolower(trim((string)($application['applicant']['user_id'] ?? '')));
    if ($userId !== '' && preg_match('/^[a-f0-9-]{36}$/i', $userId)) {
        $applicantUserIds[$userId] = $userId;
    }
}

$hasCurrentEmploymentByUserId = [];
if (!empty($applicantUserIds)) {
    $employmentResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/employment_records?select=id,person:people!employment_records_person_id_fkey(user_id),is_current'
        . '&is_current=eq.true&limit=5000',
        $headers
    );

    $employmentRows = isSuccessful($employmentResponse) ? (array)($employmentResponse['data'] ?? []) : [];
    foreach ($employmentRows as $employmentRow) {
        $userId = strtolower(trim((string)($employmentRow['person']['user_id'] ?? '')));
        if ($userId === '' || !isset($applicantUserIds[$userId])) {
            continue;
        }

        $hasCurrentEmploymentByUserId[$userId] = true;
    }
}

$interviewMap = [];
foreach ($recentInterviews as $interview) {
    $applicationId = (string)($interview['application_id'] ?? '');
    if ($applicationId === '' || isset($interviewMap[$applicationId])) {
        continue;
    }

    $interviewMap[$applicationId] = [
        'scheduled_at' => (string)($interview['scheduled_at'] ?? ''),
        'stage' => (string)($interview['interview_stage'] ?? ''),
        'result' => (string)($interview['result'] ?? ''),
        'remarks' => (string)($interview['remarks'] ?? ''),
        'interviewer' => (string)($interview['interviewer']['email'] ?? ''),
    ];
}

$feedbackMap = [];
foreach ((array)$feedbackRows as $feedback) {
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
