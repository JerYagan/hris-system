<?php

$dataLoadError = null;
$ticketRows = [];
$selectedTicket = null;
$currentStaffUserId = (string)($staffUserId ?? '');

$supportSummary = [
    'assigned_total' => 0,
    'forwarded_pending' => 0,
    'updated_today' => 0,
];

$statusClass = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'resolved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'forwarded_to_staff' => 'bg-blue-100 text-blue-800',
        'in_review' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
};

$ticketLogsResponse = apiRequest(
    'GET',
    rtrim($supabaseUrl, '/')
    . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,action_name,new_data,created_at,module_name,entity_name'
    . '&module_name=eq.support&entity_name=eq.tickets&order=created_at.asc&limit=5000',
    $headers
);

if (!isSuccessful($ticketLogsResponse)) {
    $dataLoadError = 'Unable to load forwarded support tickets right now.';
    return;
}

$ticketsById = [];
$requesterUserIds = [];
$todayDate = gmdate('Y-m-d');

foreach ((array)($ticketLogsResponse['data'] ?? []) as $logRowRaw) {
    $logRow = (array)$logRowRaw;
    $payload = (array)($logRow['new_data'] ?? []);
    $ticketId = (string)($logRow['entity_id'] ?? $payload['ticket_id'] ?? '');
    if ($ticketId === '') {
        continue;
    }

    $actionName = strtolower((string)($logRow['action_name'] ?? ''));
    $createdAt = (string)($logRow['created_at'] ?? '');

    if ($actionName === 'submit_ticket') {
        $requesterUserId = (string)($payload['requester_user_id'] ?? '');
        if ($requesterUserId !== '') {
            $requesterUserIds[$requesterUserId] = true;
        }

        $status = (string)($payload['status'] ?? 'submitted');
        $ticketsById[$ticketId] = [
            'ticket_id' => $ticketId,
            'requester_user_id' => $requesterUserId,
            'requester_role' => (string)($payload['requester_role'] ?? ''),
            'requester_label' => $requesterUserId,
            'category' => (string)($payload['category'] ?? 'general'),
            'request_type' => (string)($payload['request_type'] ?? ''),
            'subject' => (string)($payload['subject'] ?? 'Support Ticket'),
            'message' => (string)($payload['message'] ?? ''),
            'status' => $status,
            'status_class' => $statusClass($status),
            'admin_notes' => '',
            'resolution_notes' => '',
            'forward_to_user_id' => '',
            'last_staff_update_type' => '',
            'last_staff_notes' => '',
            'attachment_name' => (string)($payload['attachment_name'] ?? ''),
            'attachment_path' => (string)($payload['attachment_path'] ?? ''),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'history' => [[
                'created_at' => $createdAt,
                'action' => 'Submitted',
                'actor' => (string)($logRow['actor_user_id'] ?? ''),
                'notes' => '',
                'resolution' => '',
            ]],
        ];
        continue;
    }

    if ($actionName === 'admin_ticket_update' && isset($ticketsById[$ticketId])) {
        $status = (string)($payload['status'] ?? $ticketsById[$ticketId]['status']);
        $ticketsById[$ticketId]['status'] = $status;
        $ticketsById[$ticketId]['status_class'] = $statusClass($status);
        $ticketsById[$ticketId]['admin_notes'] = (string)($payload['admin_notes'] ?? $ticketsById[$ticketId]['admin_notes']);
        $ticketsById[$ticketId]['resolution_notes'] = (string)($payload['resolution_notes'] ?? $ticketsById[$ticketId]['resolution_notes']);
        $ticketsById[$ticketId]['forward_to_user_id'] = (string)($payload['forward_to_user_id'] ?? $ticketsById[$ticketId]['forward_to_user_id']);
        $ticketsById[$ticketId]['updated_at'] = $createdAt;
        $ticketsById[$ticketId]['history'][] = [
            'created_at' => $createdAt,
            'action' => 'Admin Updated: ' . strtoupper(str_replace('_', ' ', $status)),
            'actor' => (string)($logRow['actor_user_id'] ?? ''),
            'notes' => (string)($payload['admin_notes'] ?? ''),
            'resolution' => (string)($payload['resolution_notes'] ?? ''),
        ];
        continue;
    }

    if ($actionName === 'staff_ticket_update' && isset($ticketsById[$ticketId])) {
        $staffUpdateType = (string)($payload['staff_update_type'] ?? 'progress_update');
        $staffNotes = (string)($payload['staff_notes'] ?? '');
        $ticketsById[$ticketId]['updated_at'] = $createdAt;
        $ticketsById[$ticketId]['last_staff_update_type'] = $staffUpdateType;
        $ticketsById[$ticketId]['last_staff_notes'] = $staffNotes;
        $ticketsById[$ticketId]['history'][] = [
            'created_at' => $createdAt,
            'action' => 'Staff Update: ' . ucwords(str_replace('_', ' ', $staffUpdateType)),
            'actor' => (string)($logRow['actor_user_id'] ?? ''),
            'notes' => $staffNotes,
            'resolution' => '',
        ];
    }
}

$assignedTickets = array_values(array_filter($ticketsById, static function (array $ticket) use ($currentStaffUserId): bool {
    return (string)($ticket['forward_to_user_id'] ?? '') === $currentStaffUserId;
}));

if (!empty($requesterUserIds)) {
    $requesterIds = array_keys($requesterUserIds);

    $requesterLabels = [];
    $requesterUsersResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/user_accounts?select=id,email,username&id=in.' . rawurlencode('(' . implode(',', $requesterIds) . ')') . '&limit=1000',
        $headers
    );

    if (isSuccessful($requesterUsersResponse)) {
        foreach ((array)($requesterUsersResponse['data'] ?? []) as $row) {
            $uid = cleanText($row['id'] ?? null);
            if ($uid === null) {
                continue;
            }
            $requesterLabels[$uid] = trim((string)($row['username'] ?? ''));
            if ($requesterLabels[$uid] === '') {
                $requesterLabels[$uid] = (string)($row['email'] ?? $uid);
            }
        }
    }

    $requesterPeopleResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/people?select=user_id,first_name,surname&user_id=in.' . rawurlencode('(' . implode(',', $requesterIds) . ')') . '&limit=1000',
        $headers
    );

    if (isSuccessful($requesterPeopleResponse)) {
        foreach ((array)($requesterPeopleResponse['data'] ?? []) as $row) {
            $uid = cleanText($row['user_id'] ?? null);
            if ($uid === null) {
                continue;
            }
            $fullName = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['surname'] ?? ''));
            if ($fullName !== '') {
                $requesterLabels[$uid] = $fullName;
            }
        }
    }

    $requesterApplicantResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/applicant_profiles?select=user_id,full_name&user_id=in.' . rawurlencode('(' . implode(',', $requesterIds) . ')') . '&limit=1000',
        $headers
    );

    if (isSuccessful($requesterApplicantResponse)) {
        foreach ((array)($requesterApplicantResponse['data'] ?? []) as $row) {
            $uid = cleanText($row['user_id'] ?? null);
            if ($uid === null) {
                continue;
            }
            $fullName = trim((string)($row['full_name'] ?? ''));
            if ($fullName !== '') {
                $requesterLabels[$uid] = $fullName;
            }
        }
    }

    foreach ($assignedTickets as $index => $ticket) {
        $uid = (string)($ticket['requester_user_id'] ?? '');
        if ($uid !== '' && isset($requesterLabels[$uid])) {
            $assignedTickets[$index]['requester_label'] = $requesterLabels[$uid];
        }
    }
}

usort($assignedTickets, static function (array $left, array $right): int {
    return strcmp((string)($right['updated_at'] ?? ''), (string)($left['updated_at'] ?? ''));
});

$supportSummary['assigned_total'] = count($assignedTickets);
foreach ($assignedTickets as $ticket) {
    if (strtolower((string)($ticket['status'] ?? '')) === 'forwarded_to_staff') {
        $supportSummary['forwarded_pending']++;
    }

    $updatedAt = (string)($ticket['updated_at'] ?? '');
    if ($updatedAt !== '' && str_starts_with($updatedAt, $todayDate)) {
        $supportSummary['updated_today']++;
    }
}

$ticketStatusFilter = strtolower((string)cleanText($_GET['status'] ?? null));
$ticketRoleFilter = strtolower((string)cleanText($_GET['role'] ?? null));
$ticketSearch = strtolower((string)cleanText($_GET['search'] ?? null));

$filteredTickets = array_values(array_filter($assignedTickets, static function (array $row) use ($ticketStatusFilter, $ticketRoleFilter, $ticketSearch): bool {
    if ($ticketStatusFilter !== '' && strtolower((string)($row['status'] ?? '')) !== $ticketStatusFilter) {
        return false;
    }

    if ($ticketRoleFilter !== '' && strtolower((string)($row['requester_role'] ?? '')) !== $ticketRoleFilter) {
        return false;
    }

    if ($ticketSearch !== '') {
        $haystack = strtolower(
            (string)($row['ticket_id'] ?? '') . ' '
            . (string)($row['requester_label'] ?? '') . ' '
            . (string)($row['subject'] ?? '') . ' '
            . (string)($row['message'] ?? '')
        );
        if (!str_contains($haystack, $ticketSearch)) {
            return false;
        }
    }

    return true;
}));

$ticketsPage = max(1, (int)($_GET['page'] ?? 1));
$ticketsPageSize = 10;
$ticketsOffset = ($ticketsPage - 1) * $ticketsPageSize;
$ticketsTotal = count($filteredTickets);
$ticketsTotalPages = max(1, (int)ceil($ticketsTotal / $ticketsPageSize));
$ticketRowsPage = array_slice($filteredTickets, $ticketsOffset, $ticketsPageSize);

$selectedTicketId = (string)cleanText($_GET['ticket_id'] ?? null);
if ($selectedTicketId !== '') {
    foreach ($assignedTickets as $ticketRow) {
        if ((string)($ticketRow['ticket_id'] ?? '') === $selectedTicketId) {
            $selectedTicket = $ticketRow;
            break;
        }
    }
}

$ticketRows = $assignedTickets;
