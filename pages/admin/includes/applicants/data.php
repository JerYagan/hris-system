<?php

$applicationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/applications?select=id,application_ref_no,application_status,submitted_at,updated_at,job:job_postings(title),applicant:applicant_profiles(full_name,email)&order=submitted_at.desc&limit=1000',
    $headers
);

$feedbackResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/application_feedback?select=application_id,decision,feedback_text,provided_at&order=provided_at.desc&limit=1000',
    $headers
);

$applications = isSuccessful($applicationsResponse) ? (array)($applicationsResponse['data'] ?? []) : [];
$feedbackRows = isSuccessful($feedbackResponse) ? (array)($feedbackResponse['data'] ?? []) : [];

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

$registeredApplicantsCount = count($applications);
$approvedCount = 0;
$pendingDecisionCount = 0;
$recommendationRows = [];

if (!function_exists('applicantStatusPill')) {
    function applicantStatusPill(string $status): array
    {
        $key = strtolower(trim($status));

        if (in_array($key, ['shortlisted', 'offer', 'hired'], true)) {
            return ['Verified', 'bg-emerald-100 text-emerald-800'];
        }

        if (in_array($key, ['submitted', 'screening', 'interview'], true)) {
            return ['For Review', 'bg-blue-100 text-blue-800'];
        }

        if (in_array($key, ['rejected', 'withdrawn'], true)) {
            return ['Disqualified', 'bg-rose-100 text-rose-800'];
        }

        return ['For Review', 'bg-slate-100 text-slate-700'];
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

    if (in_array($statusValue, ['shortlisted', 'offer', 'hired'], true)) {
        $approvedCount++;
    }

    if (in_array($statusValue, ['submitted', 'screening', 'interview'], true)) {
        $pendingDecisionCount++;
    }

    $applicationId = (string)($application['id'] ?? '');
    $fullName = (string)($application['applicant']['full_name'] ?? 'Unknown Applicant');
    $recommendation = recommendationFromStatus($statusValue);

    $feedback = $feedbackMap[$applicationId] ?? null;
    $adminDecision = is_array($feedback) ? feedbackDecisionLabel((string)($feedback['decision'] ?? '')) : '-';

    $alignment = 'Pending';
    $alignmentClass = 'bg-slate-200 text-slate-700';

    if ($adminDecision !== '-') {
        if ($adminDecision === $recommendation[3]) {
            $alignment = 'Match';
            $alignmentClass = 'bg-emerald-100 text-emerald-800';
        } else {
            $alignment = 'Override';
            $alignmentClass = 'bg-amber-100 text-amber-800';
        }
    }

    $recommendationRows[] = [
        'applicant_name' => $fullName,
        'system_recommendation' => $recommendation[0],
        'confidence' => $recommendation[1],
        'recommendation_class' => $recommendation[2],
        'admin_decision' => $adminDecision,
        'alignment' => $alignment,
        'alignment_class' => $alignmentClass,
    ];
}
