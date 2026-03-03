<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);

$dataLoadError = null;
$recentSupportInquiries = [];
$supportSearch = strtolower((string)cleanText($_GET['search'] ?? null));
$supportStatusFilter = strtolower((string)cleanText($_GET['status'] ?? null));
$supportCategoryFilter = strtolower((string)cleanText($_GET['category'] ?? null));
$supportCategoryOptions = [];
$supportPage = max(1, (int)($_GET['page'] ?? 1));
$supportPageSize = 10;

$statusClass = static function (string $status): string {
	return match (strtolower(trim($status))) {
		'resolved' => 'bg-emerald-100 text-emerald-800',
		'rejected' => 'bg-rose-100 text-rose-800',
		'forwarded_to_staff' => 'bg-blue-100 text-blue-800',
		'in_review' => 'bg-amber-100 text-amber-800',
		default => 'bg-slate-100 text-slate-700',
	};
};

if ($applicantUserId === '') {
	$dataLoadError = 'Applicant session is missing. Please login again.';
	return;
}

if (!isValidUuid($applicantUserId)) {
	$dataLoadError = 'Invalid applicant session context. Please login again.';
	return;
}

$supportHistoryResponse = apiRequest(
	'GET',
	$supabaseUrl
	. '/rest/v1/activity_logs?select=id,entity_id,action_name,new_data,created_at'
	. '&module_name=eq.support'
	. '&entity_name=eq.tickets'
	. '&order=created_at.asc&limit=2000',
	$headers
);

if (isSuccessful($supportHistoryResponse)) {
	$tickets = [];
	foreach ((array)($supportHistoryResponse['data'] ?? []) as $historyRowRaw) {
		$historyRow = (array)$historyRowRaw;
		$newData = (array)($historyRow['new_data'] ?? []);
		$ticketId = (string)($historyRow['entity_id'] ?? $newData['ticket_id'] ?? '');
		if ($ticketId === '') {
			continue;
		}

		$actionName = strtolower((string)($historyRow['action_name'] ?? ''));
		if ($actionName === 'submit_ticket' && (string)($newData['requester_user_id'] ?? '') === $applicantUserId) {
			$status = (string)($newData['status'] ?? 'submitted');
			$tickets[$ticketId] = [
				'id' => $ticketId,
				'subject' => (string)($newData['subject'] ?? 'Support Inquiry'),
				'message' => (string)($newData['message'] ?? ''),
				'category' => (string)($newData['category'] ?? 'general'),
				'status' => $status,
				'status_class' => $statusClass($status),
				'admin_notes' => '',
				'resolution_notes' => '',
				'staff_notes' => '',
				'attachment_name' => (string)($newData['attachment_name'] ?? ''),
				'attachment_path' => (string)($newData['attachment_path'] ?? ''),
				'created_at' => cleanText($historyRow['created_at'] ?? null),
				'updated_at' => cleanText($historyRow['created_at'] ?? null),
			];
			continue;
		}

		if ($actionName === 'admin_ticket_update' && isset($tickets[$ticketId])) {
			$status = (string)($newData['status'] ?? $tickets[$ticketId]['status']);
			$tickets[$ticketId]['status'] = $status;
			$tickets[$ticketId]['status_class'] = $statusClass($status);
			$tickets[$ticketId]['admin_notes'] = (string)($newData['admin_notes'] ?? $tickets[$ticketId]['admin_notes']);
			$tickets[$ticketId]['resolution_notes'] = (string)($newData['resolution_notes'] ?? $tickets[$ticketId]['resolution_notes']);
			$tickets[$ticketId]['updated_at'] = cleanText($historyRow['created_at'] ?? null);
			continue;
		}

		if ($actionName === 'staff_ticket_update' && isset($tickets[$ticketId])) {
			$tickets[$ticketId]['staff_notes'] = (string)($newData['staff_notes'] ?? $tickets[$ticketId]['staff_notes']);
			$tickets[$ticketId]['updated_at'] = cleanText($historyRow['created_at'] ?? null);
		}
	}

	$recentSupportInquiries = array_values($tickets);
	usort($recentSupportInquiries, static function (array $left, array $right): int {
		return strcmp((string)($right['updated_at'] ?? ''), (string)($left['updated_at'] ?? ''));
	});

	foreach ($recentSupportInquiries as $inquiry) {
		$categoryKey = strtolower((string)($inquiry['category'] ?? ''));
		if ($categoryKey !== '') {
			$supportCategoryOptions[$categoryKey] = true;
		}
	}

	$recentSupportInquiries = array_values(array_filter($recentSupportInquiries, static function (array $inquiry) use ($supportSearch, $supportStatusFilter, $supportCategoryFilter): bool {
		if ($supportStatusFilter !== '' && strtolower((string)($inquiry['status'] ?? '')) !== $supportStatusFilter) {
			return false;
		}

		if ($supportCategoryFilter !== '' && strtolower((string)($inquiry['category'] ?? '')) !== $supportCategoryFilter) {
			return false;
		}

		if ($supportSearch !== '') {
			$haystack = strtolower(
				(string)($inquiry['id'] ?? '') . ' '
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
	$recentSupportInquiries = array_slice($recentSupportInquiries, $offset, $supportPageSize + 1);
	$supportHasNextPage = count($recentSupportInquiries) > $supportPageSize;
	if ($supportHasNextPage) {
		array_pop($recentSupportInquiries);
	}

	$supportPagination = [
		'page' => $supportPage,
		'has_previous' => $supportPage > 1,
		'has_next' => $supportHasNextPage,
		'previous_page' => $supportPage > 1 ? $supportPage - 1 : null,
		'next_page' => $supportHasNextPage ? $supportPage + 1 : null,
	];
} else {
	$dataLoadError = 'Unable to load support inquiry history right now.';
}
