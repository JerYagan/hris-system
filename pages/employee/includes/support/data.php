<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;

$supportSummary = [
    'total_inquiries' => 0,
    'open_tickets' => 0,
    'recent_30_days' => 0,
];

$supportInquiries = [];
$supportPage = max(1, (int)($_GET['page'] ?? 1));
$supportPageSize = 10;
$supportSearch = strtolower((string)cleanText($_GET['search'] ?? null));
$supportStatusFilter = strtolower((string)cleanText($_GET['status'] ?? null));
$supportCategoryFilter = strtolower((string)cleanText($_GET['category'] ?? null));
$supportCategoryOptions = [];

$statusClass = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'resolved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'forwarded_to_staff' => 'bg-blue-100 text-blue-800',
        'in_review' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
};

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$historyResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,action_name,new_data,created_at'
    . '&module_name=eq.support'
    . '&entity_name=eq.tickets'
    . '&order=created_at.asc'
    . '&limit=2000',
    $headers
);

if (!isSuccessful($historyResponse)) {
    $dataLoadError = 'Unable to load support inquiry history right now.';
    return;
}

$rows = (array)($historyResponse['data'] ?? []);
$tickets = [];

$thirtyDaysAgo = strtotime('-30 days');

foreach ($rows as $rowRaw) {
    $row = (array)$rowRaw;
    $payload = (array)($row['new_data'] ?? []);
    $ticketId = (string)($row['entity_id'] ?? $payload['ticket_id'] ?? '');
    if ($ticketId === '') {
        continue;
    }

    $requesterUserId = (string)($payload['requester_user_id'] ?? '');
    $actionName = strtolower((string)($row['action_name'] ?? ''));
    $createdAt = (string)($row['created_at'] ?? '');

    if ($actionName === 'submit_ticket' && $requesterUserId === (string)$employeeUserId) {
        $tickets[$ticketId] = [
            'ticket_id' => $ticketId,
            'category' => (string)($payload['category'] ?? 'profile_change'),
            'request_type' => (string)($payload['request_type'] ?? 'other_profile_change'),
            'subject' => (string)($payload['subject'] ?? 'Support Ticket'),
            'message' => (string)($payload['message'] ?? ''),
            'status' => (string)($payload['status'] ?? 'submitted'),
            'status_class' => $statusClass((string)($payload['status'] ?? 'submitted')),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'admin_notes' => '',
            'resolution_notes' => '',
            'staff_notes' => '',
            'attachment_name' => (string)($payload['attachment_name'] ?? ''),
            'attachment_path' => (string)($payload['attachment_path'] ?? ''),
        ];
        continue;
    }

    if ($actionName === 'admin_ticket_update' && isset($tickets[$ticketId])) {
        $status = (string)($payload['status'] ?? $tickets[$ticketId]['status']);
        $tickets[$ticketId]['status'] = $status;
        $tickets[$ticketId]['status_class'] = $statusClass($status);
        $tickets[$ticketId]['updated_at'] = $createdAt;
        $tickets[$ticketId]['admin_notes'] = (string)($payload['admin_notes'] ?? $tickets[$ticketId]['admin_notes']);
        $tickets[$ticketId]['resolution_notes'] = (string)($payload['resolution_notes'] ?? $tickets[$ticketId]['resolution_notes']);
        continue;
    }

    if ($actionName === 'staff_ticket_update' && isset($tickets[$ticketId])) {
        $tickets[$ticketId]['updated_at'] = $createdAt;
        $tickets[$ticketId]['staff_notes'] = (string)($payload['staff_notes'] ?? $tickets[$ticketId]['staff_notes']);
    }
}

$supportInquiries = array_values($tickets);
usort($supportInquiries, static function (array $left, array $right): int {
    return strcmp((string)($right['updated_at'] ?? ''), (string)($left['updated_at'] ?? ''));
});

$supportSummary['total_inquiries'] = count($supportInquiries);
foreach ($supportInquiries as $inquiry) {
    $categoryKey = strtolower((string)($inquiry['category'] ?? ''));
    if ($categoryKey !== '') {
        $supportCategoryOptions[$categoryKey] = true;
    }

    $status = strtolower((string)($inquiry['status'] ?? 'submitted'));
    if (in_array($status, ['submitted', 'in_review', 'forwarded_to_staff'], true)) {
        $supportSummary['open_tickets']++;
    }

    $createdTs = strtotime((string)($inquiry['created_at'] ?? ''));
    if ($createdTs !== false && $createdTs >= $thirtyDaysAgo) {
        $supportSummary['recent_30_days']++;
    }
}

$supportInquiries = array_values(array_filter($supportInquiries, static function (array $inquiry) use ($supportSearch, $supportStatusFilter, $supportCategoryFilter): bool {
    if ($supportStatusFilter !== '' && strtolower((string)($inquiry['status'] ?? '')) !== $supportStatusFilter) {
        return false;
    }

    if ($supportCategoryFilter !== '' && strtolower((string)($inquiry['category'] ?? '')) !== $supportCategoryFilter) {
        return false;
    }

    if ($supportSearch !== '') {
        $haystack = strtolower(
            (string)($inquiry['ticket_id'] ?? '') . ' '
            . (string)($inquiry['subject'] ?? '') . ' '
            . (string)($inquiry['message'] ?? '') . ' '
            . (string)($inquiry['category'] ?? '')
        );

        if (!str_contains($haystack, $supportSearch)) {
            return false;
        }
    }

    return true;
}));

$supportCategoryOptions = array_keys($supportCategoryOptions);
sort($supportCategoryOptions);

$offset = ($supportPage - 1) * $supportPageSize;
$supportInquiries = array_slice($supportInquiries, $offset, $supportPageSize + 1);
$supportHasNextPage = count($supportInquiries) > $supportPageSize;
if ($supportHasNextPage) {
    array_pop($supportInquiries);
}

$supportPagination = [
    'page' => $supportPage,
    'has_previous' => $supportPage > 1,
    'has_next' => $supportHasNextPage,
    'previous_page' => $supportPage > 1 ? $supportPage - 1 : null,
    'next_page' => $supportHasNextPage ? $supportPage + 1 : null,
];
