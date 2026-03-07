<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/document-management/bootstrap.php';

if (!(bool)($employeeContextResolved ?? false)) {
    http_response_code(403);
    exit('Employee context could not be resolved.');
}

$status = strtolower((string)(cleanText($_GET['status'] ?? null) ?? ''));
$allowedStatuses = ['', 'draft', 'submitted', 'approved', 'rejected', 'needs_revision', 'archived'];
if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    exit('Invalid document status filter.');
}

$query = $supabaseUrl
    . '/rest/v1/documents?select=id,title,document_status,current_version_no,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=500';

if ($status !== '') {
    $query .= '&document_status=eq.' . rawurlencode($status);
}

$documentsResponse = apiRequest('GET', $query, $headers);
if (!isSuccessful($documentsResponse)) {
    http_response_code(500);
    exit('Unable to load document rows for export.');
}

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=first_name,surname,agency_employee_no'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

$personRow = (array)(($personResponse['data'] ?? [])[0] ?? []);
$employeeName = trim((string)($personRow['first_name'] ?? '') . ' ' . (string)($personRow['surname'] ?? ''));
if ($employeeName === '') {
    $employeeName = 'Employee';
}
$employeeCode = cleanText($personRow['agency_employee_no'] ?? null) ?? '-';

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? '-' : date('M j, Y g:i A', $timestamp);
};

$rowsHtml = '';
foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
    $document = (array)$documentRaw;
    $category = (array)($document['category'] ?? []);

    $rowsHtml .= '<tr>'
        . '<td>' . $escape(cleanText($document['title'] ?? null) ?? 'Untitled Document') . '</td>'
        . '<td>' . $escape(cleanText($category['category_name'] ?? null) ?? 'Others') . '</td>'
        . '<td>' . $escape(ucwords(str_replace('_', ' ', strtolower((string)($document['document_status'] ?? 'draft'))))) . '</td>'
        . '<td>' . $escape('v' . (string)((int)($document['current_version_no'] ?? 1))) . '</td>'
        . '<td>' . $escape($formatDate(cleanText($document['updated_at'] ?? null))) . '</td>'
        . '</tr>';
}

if ($rowsHtml === '') {
    $rowsHtml = '<tr><td colspan="5">No document rows found for the selected filters.</td></tr>';
}

$fileName = 'document-status-report-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo '<html><head><meta charset="UTF-8"></head><body>';
echo '<table border="0">';
echo '<tr><td colspan="5"><strong>Document Status Report</strong></td></tr>';
echo '<tr><td colspan="5">Employee Name: ' . $escape($employeeName) . '</td></tr>';
echo '<tr><td colspan="5">Employee ID: ' . $escape($employeeCode) . '</td></tr>';
echo '<tr><td colspan="5">Generated On: ' . $escape(date('M j, Y g:i A')) . ' PST</td></tr>';
echo '<tr><td colspan="5">Status Filter: ' . $escape($status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'All Statuses') . '</td></tr>';
echo '</table>';
echo '<br>';
echo '<table border="1">';
echo '<tr><th>Document</th><th>Category</th><th>Status</th><th>Version</th><th>Updated</th></tr>';
echo $rowsHtml;
echo '</table>';
echo '</body></html>';
exit;