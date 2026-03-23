<?php
include "config.php";

if (!function_exists('legacyRfidFetchJson')) {
    function legacyRfidFetchJson(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('legacyRfidEmployeePhotoUrl')) {
    function legacyRfidEmployeePhotoUrl(?string $rawPath, string $appBaseUrl, bool $isHrisPhoto): string
    {
        $path = trim((string)$rawPath);
        if ($path === '') {
            return 'https://via.placeholder.com/60';
        }

        if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        if (!$isHrisPhoto) {
            return $path;
        }

        return rtrim($appBaseUrl, '/') . '/storage/document/' . ltrim($path, '/');
    }
}

if (!function_exists('legacyRfidEmployeeName')) {
    function legacyRfidEmployeeName(array $personRow, array $rfidRow): string
    {
        $legacyName = trim((string)($rfidRow['name'] ?? ''));
        if ($legacyName !== '') {
            return $legacyName;
        }

        $firstName = trim((string)($personRow['first_name'] ?? ''));
        $surname = trim((string)($personRow['surname'] ?? ''));
        $fullName = trim($firstName . ' ' . $surname);
        return $fullName !== '' ? $fullName : 'Unnamed Employee';
    }
}

$privilegedKey = (string)(legacyEnvValue('SUPABASE_SERVICE_ROLE_KEY') ?? $api_key);
$privilegedHeaders = [
    "apikey: $privilegedKey",
    "Authorization: Bearer $privilegedKey",
    'Content-Type: application/json',
];

$rfidEmployees = legacyRfidFetchJson(
    $supabase_url . 'employees?select=uid,employee_id,name,birthday,photo,person_id&order=name.asc',
    $headers
);

$rfidByPersonId = [];
$rfidByEmployeeId = [];
foreach ($rfidEmployees as $rfidEmployee) {
    if (!is_array($rfidEmployee)) {
        continue;
    }

    $personId = trim((string)($rfidEmployee['person_id'] ?? ''));
    $employeeId = strtoupper(trim((string)($rfidEmployee['employee_id'] ?? '')));

    if ($personId !== '') {
        $rfidByPersonId[$personId] = $rfidEmployee;
    }
    if ($employeeId !== '') {
        $rfidByEmployeeId[$employeeId] = $rfidEmployee;
    }
}

$employmentRows = legacyRfidFetchJson(
    $supabase_url . 'employment_records?select=person_id&is_current=eq.true&limit=5000',
    $privilegedHeaders
);

$personIds = [];
foreach ($employmentRows as $employmentRow) {
    $personId = trim((string)($employmentRow['person_id'] ?? ''));
    if ($personId !== '') {
        $personIds[$personId] = true;
    }
}

$peopleRows = [];
if ($personIds !== []) {
    $chunks = array_chunk(array_keys($personIds), 100);
    foreach ($chunks as $chunk) {
        $inFilter = 'id=in.(' . implode(',', array_map('rawurlencode', $chunk)) . ')';
        $peopleRows = array_merge(
            $peopleRows,
            legacyRfidFetchJson(
                $supabase_url
                . 'people?select=id,first_name,surname,agency_employee_no,date_of_birth,profile_photo_url'
                . '&' . $inFilter
                . '&order=surname.asc,first_name.asc',
                $privilegedHeaders
            )
        );
    }
}

$employees = [];
$seenLegacyUids = [];

foreach ($peopleRows as $personRow) {
    if (!is_array($personRow)) {
        continue;
    }

    $personId = trim((string)($personRow['id'] ?? ''));
    $agencyEmployeeNo = strtoupper(trim((string)($personRow['agency_employee_no'] ?? '')));
    $rfidRow = $rfidByPersonId[$personId] ?? ($agencyEmployeeNo !== '' ? ($rfidByEmployeeId[$agencyEmployeeNo] ?? []) : []);
    $uid = trim((string)($rfidRow['uid'] ?? ''));

    if ($uid !== '') {
        $seenLegacyUids[$uid] = true;
    }

    $employees[] = [
        'photo_url' => legacyRfidEmployeePhotoUrl(
            (string)($rfidRow['photo'] ?? $personRow['profile_photo_url'] ?? ''),
            $appBaseUrl,
            trim((string)($rfidRow['photo'] ?? '')) === ''
        ),
        'employee_id' => trim((string)($rfidRow['employee_id'] ?? $personRow['agency_employee_no'] ?? '')),
        'name' => legacyRfidEmployeeName($personRow, $rfidRow),
        'birthday' => trim((string)($rfidRow['birthday'] ?? $personRow['date_of_birth'] ?? '')),
        'uid' => $uid,
        'status_label' => $uid !== '' ? 'UID Registered' : 'No UID Yet',
        'status_class' => $uid !== '' ? 'status-active' : 'status-pending',
        'action_href' => $uid !== ''
            ? 'register.php?edit_uid=' . urlencode($uid)
            : 'register.php?person_id=' . urlencode($personId),
        'action_label' => $uid !== '' ? 'Edit' : 'Register UID',
    ];
}

foreach ($rfidEmployees as $rfidEmployee) {
    if (!is_array($rfidEmployee)) {
        continue;
    }

    $uid = trim((string)($rfidEmployee['uid'] ?? ''));
    if ($uid !== '' && isset($seenLegacyUids[$uid])) {
        continue;
    }

    $isLinked = trim((string)($rfidEmployee['person_id'] ?? '')) !== '';

    $employees[] = [
        'photo_url' => legacyRfidEmployeePhotoUrl((string)($rfidEmployee['photo'] ?? ''), $appBaseUrl, false),
        'employee_id' => trim((string)($rfidEmployee['employee_id'] ?? '')),
        'name' => trim((string)($rfidEmployee['name'] ?? '')) !== '' ? trim((string)($rfidEmployee['name'] ?? '')) : 'Unlinked RFID Employee',
        'birthday' => trim((string)($rfidEmployee['birthday'] ?? '')),
        'uid' => $uid,
        'status_label' => $isLinked ? 'UID Registered' : 'RFID Only / Unlinked',
        'status_class' => $isLinked ? 'status-active' : 'status-unlinked',
        'action_href' => $uid !== '' ? 'register.php?edit_uid=' . urlencode($uid) : 'register.php',
        'action_label' => $uid !== '' ? 'Edit' : 'Register UID',
    ];
}

usort($employees, static function (array $left, array $right): int {
    return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #1f8f4e;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            font-size: 14px;
        }

        .table th {
            background: #1f8f4e;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .table tr:hover {
            background: #f1fdf5;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-edit {
            background: #2ecc71;
            color: white;
        }

        .btn-edit:hover {
            background: #27ae60;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-unlinked {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
<?php include "nav.php"; ?>

<div class="container">
    <h1>Employees In System</h1>

    <table class="table">
        <tr>
            <th>Photo</th>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Birthday</th>
            <th>UID</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php foreach($employees as $emp): ?>
        <tr>
            <td>
                <img class="profile-img" src="<?php echo htmlspecialchars((string)$emp['photo_url'], ENT_QUOTES, 'UTF-8'); ?>">
            </td>
            <td><?php echo htmlspecialchars((string)($emp['employee_id'] !== '' ? $emp['employee_id'] : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$emp['name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($emp['birthday'] !== '' ? $emp['birthday'] : 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($emp['uid'] !== '' ? $emp['uid'] : 'Not assigned'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <span class="status-badge <?php echo htmlspecialchars((string)$emp['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string)$emp['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>
            <td>
                <a class="btn-edit" href="<?php echo htmlspecialchars((string)$emp['action_href'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$emp['action_label'], ENT_QUOTES, 'UTF-8'); ?></a>
                <?php if (($emp['uid'] ?? '') !== ''): ?>
                    <a class="btn-delete" href="delete_employee.php?uid=<?php echo urlencode((string)$emp['uid']); ?>" onclick="return confirm('Delete this employee?')">Delete</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>