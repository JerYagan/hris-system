<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/apply/bootstrap.php';
require_once __DIR__ . '/includes/apply/data.php';

if (!function_exists('pdsClean')) {
    function pdsClean(mixed $value): string
    {
        $text = cleanText($value);
        return $text === null || $text === '' ? '-' : $text;
    }
}

if (!function_exists('pdsHtml')) {
    function pdsHtml(mixed $value): string
    {
        return htmlspecialchars(pdsClean($value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pdsDate')) {
    function pdsDate(mixed $value): string
    {
        $text = cleanText($value);
        if ($text === null || $text === '') {
            return '-';
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return pdsClean($text);
        }

        return date('F j, Y', $timestamp);
    }
}

if ($jobNotAvailable || !isValidUuid((string)($jobData['id'] ?? ''))) {
    redirectWithState('error', 'No valid job posting was selected for PDS export.', 'job-list.php');
}

$projectRoot = dirname(__DIR__, 2);
$autoloadPath = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoloadPath)) {
    redirectWithState('error', 'Export libraries are missing. Run: composer require dompdf/dompdf', 'apply.php?job_id=' . urlencode((string)$jobData['id']));
}

require_once $autoloadPath;

if (!class_exists('Dompdf\\Dompdf')) {
    redirectWithState('error', 'Dompdf is not available. Install it with composer require dompdf/dompdf', 'apply.php?job_id=' . urlencode((string)$jobData['id']));
}

$personRow = [];
$addressRows = [];
$spouseRows = [];
$educationRows = [];

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,surname,first_name,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,telephone_no,mobile_no,personal_email'
    . '&user_id=eq.' . rawurlencode($applicantUserId)
    . '&limit=1',
    $headers
);

$personId = null;
if (isSuccessful($personResponse) && !empty((array)($personResponse['data'] ?? []))) {
    $personRow = (array)$personResponse['data'][0];
    $resolvedPersonId = cleanText($personRow['id'] ?? null);
    if ($resolvedPersonId !== null && isValidUuid($resolvedPersonId)) {
        $personId = $resolvedPersonId;
    }
}

if ($personId !== null) {
    $addressesResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_addresses?select=address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,country,is_primary'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&order=created_at.asc&limit=20',
        $headers
    );

    if (isSuccessful($addressesResponse)) {
        $addressRows = (array)($addressesResponse['data'] ?? []);
    }

    $spouseResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_family_spouses?select=surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no,sequence_no'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&order=sequence_no.asc&limit=5',
        $headers
    );

    if (isSuccessful($spouseResponse)) {
        $spouseRows = (array)($spouseResponse['data'] ?? []);
    }

    $educationsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/person_educations?select=education_level,school_name,course_degree,period_from,period_to,highest_level_units,year_graduated,honors_received,sequence_no'
        . '&person_id=eq.' . rawurlencode($personId)
        . '&order=sequence_no.asc&limit=100',
        $headers
    );

    if (isSuccessful($educationsResponse)) {
        $educationRows = (array)($educationsResponse['data'] ?? []);
    }
}

$addressesByType = [
    'residential' => null,
    'permanent' => null,
];

foreach ($addressRows as $addressRow) {
    $type = strtolower((string)($addressRow['address_type'] ?? ''));
    if (!array_key_exists($type, $addressesByType)) {
        continue;
    }
    if ($addressesByType[$type] !== null) {
        continue;
    }
    $addressesByType[$type] = (array)$addressRow;
}

$formatAddress = static function (?array $row): string {
    if ($row === null) {
        return '-';
    }

    $parts = [];
    foreach (['house_no', 'street', 'subdivision', 'barangay', 'city_municipality', 'province', 'zip_code', 'country'] as $key) {
        $value = cleanText($row[$key] ?? null);
        if ($value !== null && $value !== '') {
            $parts[] = $value;
        }
    }

    return empty($parts) ? '-' : implode(', ', $parts);
};

$spouse = !empty($spouseRows) ? (array)$spouseRows[0] : [];

$educationByLevel = [
    'elementary' => [],
    'secondary' => [],
    'vocational' => [],
    'college' => [],
    'graduate' => [],
];

foreach ($educationRows as $educationRow) {
    $level = strtolower((string)($educationRow['education_level'] ?? ''));
    if (!array_key_exists($level, $educationByLevel) || !empty($educationByLevel[$level])) {
        continue;
    }
    $educationByLevel[$level] = (array)$educationRow;
}

$fullName = trim(implode(' ', array_filter([
    cleanText($personRow['surname'] ?? null),
    cleanText($personRow['first_name'] ?? null),
    cleanText($personRow['middle_name'] ?? null),
    cleanText($personRow['name_extension'] ?? null),
], static fn ($value) => is_string($value) && $value !== '')));

if ($fullName === '') {
    $fullName = (string)($applicantProfile['full_name'] ?? 'Applicant User');
}

$html = '<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;color:#111;margin:0}
.page{padding:18px}
.title{font-size:12px;font-weight:700;text-align:center;line-height:1.35}
.subtitle{font-size:10px;text-align:center;margin-top:2px}
.form-tag{font-size:9px;text-align:right;margin-bottom:6px}
.section{margin-top:10px}
.section-title{background:#e5e7eb;border:1px solid #111;padding:4px 6px;font-weight:700;font-size:10px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #111;padding:4px 5px;vertical-align:top}
th{font-weight:700;background:#f8fafc;text-align:left}
.label{width:24%;font-weight:700;background:#f8fafc}
.note{font-size:9px;line-height:1.35;margin-top:8px}
</style></head><body><div class="page">';

$html .= '<div class="form-tag">CSC Form No. 212 (Revised 2025)</div>';
$html .= '<div class="title">PERSONAL DATA SHEET</div>';
$html .= '<div class="subtitle">Republic of the Philippines - Department of Agriculture</div>';
$html .= '<div class="subtitle">Applicant Copy</div>';

$html .= '<div class="section"><div class="section-title">I. Personal Information</div>';
$html .= '<table>';
$html .= '<tr><td class="label">Full Name</td><td>' . pdsHtml($fullName) . '</td><td class="label">Date of Birth</td><td>' . pdsHtml(pdsDate($personRow['date_of_birth'] ?? null)) . '</td></tr>';
$html .= '<tr><td class="label">Place of Birth</td><td>' . pdsHtml($personRow['place_of_birth'] ?? null) . '</td><td class="label">Sex</td><td>' . pdsHtml($personRow['sex_at_birth'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Civil Status</td><td>' . pdsHtml($personRow['civil_status'] ?? null) . '</td><td class="label">Citizenship</td><td>' . pdsHtml($personRow['citizenship'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Height (m)</td><td>' . pdsHtml($personRow['height_m'] ?? null) . '</td><td class="label">Weight (kg)</td><td>' . pdsHtml($personRow['weight_kg'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Blood Type</td><td>' . pdsHtml($personRow['blood_type'] ?? null) . '</td><td class="label">Telephone / Mobile</td><td>' . pdsHtml(($personRow['telephone_no'] ?? '-') . ' / ' . ($personRow['mobile_no'] ?? ($applicantProfile['mobile_no'] ?? '-'))) . '</td></tr>';
$html .= '<tr><td class="label">Email Address</td><td colspan="3">' . pdsHtml($personRow['personal_email'] ?? ($applicantProfile['email'] ?? '-')) . '</td></tr>';
$html .= '<tr><td class="label">Residential Address</td><td colspan="3">' . pdsHtml($formatAddress($addressesByType['residential'])) . '</td></tr>';
$html .= '<tr><td class="label">Permanent Address</td><td colspan="3">' . pdsHtml($formatAddress($addressesByType['permanent'])) . '</td></tr>';
$html .= '</table></div>';

$html .= '<div class="section"><div class="section-title">II. Family Background</div>';
$html .= '<table>';
$html .= '<tr><td class="label">Spouse Name</td><td>' . pdsHtml(trim(implode(' ', array_filter([
    cleanText($spouse['surname'] ?? null),
    cleanText($spouse['first_name'] ?? null),
    cleanText($spouse['middle_name'] ?? null),
    cleanText($spouse['extension_name'] ?? null),
], static fn ($value) => is_string($value) && $value !== '')))) . '</td></tr>';
$html .= '<tr><td class="label">Spouse Occupation</td><td>' . pdsHtml($spouse['occupation'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Employer / Business</td><td>' . pdsHtml($spouse['employer_business_name'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Business Address / Tel.</td><td>' . pdsHtml(trim((string)($spouse['business_address'] ?? '-')) . ' / ' . trim((string)($spouse['telephone_no'] ?? '-'))) . '</td></tr>';
$html .= '</table></div>';

$html .= '<div class="section"><div class="section-title">III. Educational Background</div>';
$html .= '<table><thead><tr><th style="width:16%">Level</th><th style="width:28%">Name of School</th><th style="width:22%">Course / Degree</th><th style="width:14%">Period (From-To)</th><th style="width:10%">Year Graduated</th><th style="width:10%">Honors</th></tr></thead><tbody>';

$educationLabels = [
    'elementary' => 'Elementary',
    'secondary' => 'Secondary',
    'vocational' => 'Vocational',
    'college' => 'College',
    'graduate' => 'Graduate Studies',
];

foreach ($educationLabels as $levelKey => $levelLabel) {
    $row = (array)($educationByLevel[$levelKey] ?? []);
    $periodFrom = pdsClean($row['period_from'] ?? null);
    $periodTo = pdsClean($row['period_to'] ?? null);
    $period = ($periodFrom === '-' && $periodTo === '-') ? '-' : trim($periodFrom . ' - ' . $periodTo, ' -');

    $html .= '<tr>';
    $html .= '<td>' . pdsHtml($levelLabel) . '</td>';
    $html .= '<td>' . pdsHtml($row['school_name'] ?? null) . '</td>';
    $html .= '<td>' . pdsHtml($row['course_degree'] ?? null) . '</td>';
    $html .= '<td>' . pdsHtml($period) . '</td>';
    $html .= '<td>' . pdsHtml($row['year_graduated'] ?? null) . '</td>';
    $html .= '<td>' . pdsHtml($row['honors_received'] ?? null) . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table></div>';

$html .= '<div class="section"><div class="section-title">IV. Position Applied For</div>';
$html .= '<table>';
$html .= '<tr><td class="label">Position Title</td><td>' . pdsHtml($jobData['title'] ?? null) . '</td><td class="label">Office / Department</td><td>' . pdsHtml($jobData['office_name'] ?? null) . '</td></tr>';
$html .= '<tr><td class="label">Plantilla Item No.</td><td>' . pdsHtml($jobData['plantilla_item_no'] ?? null) . '</td><td class="label">Application Date</td><td>' . pdsHtml(date('F j, Y')) . '</td></tr>';
$html .= '</table></div>';

$html .= '<p class="note"><strong>Declaration:</strong> I declare under oath that this Personal Data Sheet has been accomplished in good faith, verified by me, and to the best of my knowledge and belief, is true and correct pursuant to the Civil Service Commission requirements.</p>';
$html .= '<p class="note">Generated by DA HRIS on ' . pdsHtml(date('F j, Y g:i A')) . '.</p>';

$html .= '</div></body></html>';

$dompdfClass = 'Dompdf\\Dompdf';
$optionsClass = 'Dompdf\\Options';
$options = new $optionsClass();
$options->set('isRemoteEnabled', false);

$dompdf = new $dompdfClass($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$safeApplicantName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$fullName) ?: 'applicant';
$filename = 'PDS_CSC_Form_212_Revised_2025_' . $safeApplicantName . '_' . date('Ymd_His') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;
