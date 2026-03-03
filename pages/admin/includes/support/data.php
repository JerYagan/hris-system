<?php

$dataLoadError = null;
$ticketRows = [];
$ticketStatusCounts = [
    'submitted' => 0,
    'in_review' => 0,
    'forwarded_to_staff' => 0,
    'resolved' => 0,
    'rejected' => 0,
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

$staffUsers = [];
$roleMap = [];

$rolesResponse = apiRequest(
    'GET',
    rtrim($supabaseUrl, '/') . '/rest/v1/roles?select=id,role_key&role_key=in.(staff,admin)&limit=50',
    $headers
);

if (isSuccessful($rolesResponse)) {
    foreach ((array)($rolesResponse['data'] ?? []) as $roleRow) {
        $roleId = cleanText($roleRow['id'] ?? null);
        $roleKey = strtolower((string)cleanText($roleRow['role_key'] ?? null));
        if ($roleId !== null && $roleKey !== '') {
            $roleMap[$roleId] = $roleKey;
        }
    }
}

$assignmentUsers = [];
if (!empty($roleMap)) {
    $assignmentsResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/user_role_assignments?select=user_id,role_id&expires_at=is.null&limit=1000',
        $headers
    );

    if (isSuccessful($assignmentsResponse)) {
        foreach ((array)($assignmentsResponse['data'] ?? []) as $assignmentRow) {
            $userId = cleanText($assignmentRow['user_id'] ?? null);
            $roleId = cleanText($assignmentRow['role_id'] ?? null);
            if ($userId === null || $roleId === null || !isset($roleMap[$roleId])) {
                continue;
            }
            $roleKey = $roleMap[$roleId];
            $assignmentUsers[$userId][$roleKey] = true;
        }
    }
}

$userMeta = [];
if (!empty($assignmentUsers)) {
    $userIds = array_keys($assignmentUsers);
    $usersResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/user_accounts?select=id,email,username&id=in.' . rawurlencode('(' . implode(',', $userIds) . ')') . '&limit=1000',
        $headers
    );

    if (isSuccessful($usersResponse)) {
        foreach ((array)($usersResponse['data'] ?? []) as $userRow) {
            $userId = cleanText($userRow['id'] ?? null);
            if ($userId === null) {
                continue;
            }
            $userMeta[$userId] = [
                'email' => (string)($userRow['email'] ?? '-'),
                'username' => (string)($userRow['username'] ?? ''),
                'full_name' => '',
            ];
        }
    }

    $peopleResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/people?select=user_id,first_name,surname&user_id=in.' . rawurlencode('(' . implode(',', $userIds) . ')') . '&limit=1000',
        $headers
    );

    if (isSuccessful($peopleResponse)) {
        foreach ((array)($peopleResponse['data'] ?? []) as $peopleRow) {
            $userId = cleanText($peopleRow['user_id'] ?? null);
            if ($userId === null) {
                continue;
            }
            $fullName = trim((string)($peopleRow['first_name'] ?? '') . ' ' . (string)($peopleRow['surname'] ?? ''));
            if ($fullName !== '') {
                if (!isset($userMeta[$userId])) {
                    $userMeta[$userId] = ['email' => '-', 'username' => '', 'full_name' => ''];
                }
                $userMeta[$userId]['full_name'] = $fullName;
            }
        }
    }

    foreach ($assignmentUsers as $userId => $roles) {
        if (!isset($roles['staff'])) {
            continue;
        }
        $meta = $userMeta[$userId] ?? ['email' => '-', 'username' => '', 'full_name' => ''];
        $labelName = trim((string)($meta['full_name'] ?? ''));
        if ($labelName === '') {
            $labelName = trim((string)($meta['username'] ?? ''));
        }
        if ($labelName === '') {
            $labelName = (string)($meta['email'] ?? $userId);
        }
        $staffUsers[] = [
            'user_id' => $userId,
            'label' => $labelName . ' (' . (string)($meta['email'] ?? '-') . ')',
        ];
    }
}

$ticketLogsResponse = apiRequest(
    'GET',
    rtrim($supabaseUrl, '/') . '/rest/v1/activity_logs?select=id,entity_id,actor_user_id,action_name,new_data,created_at,module_name,entity_name&module_name=eq.support&entity_name=eq.tickets&order=created_at.asc&limit=5000',
    $headers
);

if (!isSuccessful($ticketLogsResponse)) {
    $dataLoadError = 'Unable to load support tickets right now.';
    return;
}

$requesterUserIds = [];
$ticketsById = [];

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
        $ticketsById[$ticketId]['history'][] = [
            'created_at' => $createdAt,
            'action' => 'Staff Update: ' . ucwords(str_replace('_', ' ', $staffUpdateType)),
            'actor' => (string)($logRow['actor_user_id'] ?? ''),
            'notes' => $staffNotes,
            'resolution' => '',
        ];
    }
}

if (!empty($requesterUserIds)) {
    $requesterIds = array_keys($requesterUserIds);

    $requesterUsersResponse = apiRequest(
        'GET',
        rtrim($supabaseUrl, '/') . '/rest/v1/user_accounts?select=id,email,username&id=in.' . rawurlencode('(' . implode(',', $requesterIds) . ')') . '&limit=1000',
        $headers
    );

    $requesterLabels = [];
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

    foreach ($ticketsById as $ticketId => $ticket) {
        $uid = (string)($ticket['requester_user_id'] ?? '');
        if ($uid !== '' && isset($requesterLabels[$uid])) {
            $ticketsById[$ticketId]['requester_label'] = $requesterLabels[$uid];
        }
    }
}

$ticketRows = array_values($ticketsById);
usort($ticketRows, static function (array $left, array $right): int {
    return strcmp((string)($right['updated_at'] ?? ''), (string)($left['updated_at'] ?? ''));
});

foreach ($ticketRows as $row) {
    $status = strtolower((string)($row['status'] ?? 'submitted'));
    if (isset($ticketStatusCounts[$status])) {
        $ticketStatusCounts[$status]++;
    }
}

$ticketStatusFilter = strtolower((string)cleanText($_GET['status'] ?? null));
$ticketRoleFilter = strtolower((string)cleanText($_GET['role'] ?? null));
$ticketSearch = strtolower((string)cleanText($_GET['search'] ?? null));

$filteredTickets = array_values(array_filter($ticketRows, static function (array $row) use ($ticketStatusFilter, $ticketRoleFilter, $ticketSearch): bool {
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
$selectedTicket = null;
if ($selectedTicketId !== '') {
    foreach ($ticketRows as $ticketRow) {
        if ((string)($ticketRow['ticket_id'] ?? '') === $selectedTicketId) {
            $selectedTicket = $ticketRow;
            break;
        }
    }
}
