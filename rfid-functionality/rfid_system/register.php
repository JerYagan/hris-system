<?php
session_start();
include "config.php";

$person_id = trim((string)($_GET['person_id'] ?? $_POST['person_id'] ?? ''));
$saveMessage = trim((string)($_SESSION['save_message'] ?? ''));
$saveMessageType = trim((string)($_SESSION['save_message_type'] ?? 'success'));
unset($_SESSION['save_message']);
unset($_SESSION['save_message_type']);

/* -----------------------------
   CHECK IF EDIT MODE
-----------------------------*/
$edit_uid = $_GET['edit_uid'] ?? "";
$editEmp = null;

if($edit_uid){
    $url = $supabase_url . "employees?uid=eq.$edit_uid";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $resData = json_decode($res,true);
    if(!empty($resData)) $editEmp = $resData[0];
}

if(!$editEmp && $person_id !== ''){
    $serviceRoleKey = (string)(legacyEnvValue('SUPABASE_SERVICE_ROLE_KEY') ?? $api_key);
    $privilegedHeaders = [
        "apikey: $serviceRoleKey",
        "Authorization: Bearer $serviceRoleKey",
        'Content-Type: application/json',
    ];

    $personUrl = $supabase_url . 'people?select=id,first_name,surname,agency_employee_no,date_of_birth,profile_photo_url&id=eq.' . urlencode($person_id) . '&limit=1';
    $personCh = curl_init($personUrl);
    curl_setopt($personCh, CURLOPT_HTTPHEADER, $privilegedHeaders);
    curl_setopt($personCh, CURLOPT_RETURNTRANSFER, true);
    $personRes = curl_exec($personCh);
    curl_close($personCh);

    $personRows = json_decode($personRes, true);
    if(!empty($personRows) && is_array($personRows[0] ?? null)){
        $person = $personRows[0];
        $prefillName = trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['surname'] ?? ''));
        $profilePhoto = trim((string)($person['profile_photo_url'] ?? ''));
        if($profilePhoto !== '' && preg_match('#^https?://#i', $profilePhoto) !== 1 && !str_starts_with($profilePhoto, '/')){
            $profilePhoto = rtrim($appBaseUrl, '/') . '/storage/document/' . ltrim($profilePhoto, '/');
        }

        $editEmp = [
            'person_id' => $person_id,
            'uid' => '',
            'name' => $prefillName,
            'employee_id' => (string)($person['agency_employee_no'] ?? ''),
            'birthday' => (string)($person['date_of_birth'] ?? ''),
            'photo' => $profilePhoto,
        ];
    }
}

/* -----------------------------
   FETCH ALL EMPLOYEES
-----------------------------*/
$ch = curl_init($supabase_url . "employees?select=*&order=name.asc");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$allEmpResp = curl_exec($ch);
$allEmployees = json_decode($allEmpResp,true);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $editEmp ? "Edit Employee" : "Register Employee"; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
           
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #1f8f4e;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], input[type="file"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        button {
            background: #1f8f4e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #166937;
        }
        .flash-message {
            margin: 0 0 20px;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .flash-message.success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #166534;
        }
        .flash-message.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 14px;
        }
        th, td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #1f8f4e;
            color: white;
            border-radius: 6px;
        }
        tr:hover {
            background: #f1f8f4;
        }
        .btn-edit {
            padding: 5px 10px;
            border-radius: 6px;
            background: #2ecc71;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-edit:hover {
            background: #27ae60;
        }
        
    </style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="container">
    <h1><?php echo $editEmp ? "Edit Employee" : "Register New Employee"; ?></h1>

    <?php if($saveMessage !== ''): ?>
        <div class="flash-message <?php echo $saveMessageType === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($saveMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="save_employee.php" enctype="multipart/form-data">
        <input type="hidden" name="person_id" value="<?php echo htmlspecialchars((string)($editEmp['person_id'] ?? $person_id ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <div style="flex:1; min-width:200px;">
            <label>UID:</label>
            <input type="text" name="uid" value="<?php echo htmlspecialchars((string)($editEmp['uid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo !empty($editEmp['uid']) ? 'readonly' : ''; ?> required>

            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars((string)($editEmp['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>

            <label>Employee ID:</label>
            <input type="text" name="employee_id" value="<?php echo htmlspecialchars((string)($editEmp['employee_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>

            <label>Birthday:</label>
            <input type="date" name="birthday" value="<?php echo htmlspecialchars((string)($editEmp['birthday'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div style="flex:1; min-width:200px;">
            <label>Photo:</label>
            <input type="file" name="photo">
            <?php if(!empty($editEmp['photo'])): ?>
                <img src="<?php echo htmlspecialchars((string)$editEmp['photo'], ENT_QUOTES, 'UTF-8'); ?>" class="photo-preview">
            <?php else: ?>
                <img src="https://via.placeholder.com/100" class="photo-preview">
            <?php endif; ?>
        </div>
        <div style="width:100%; text-align:center;">
            <button type="submit"><?php echo $editEmp ? "Update Employee" : "Register Employee"; ?></button>
        </div>
    </form>

    <h2>Existing Employees</h2>
    <table>
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Employee ID</th>
            <th>UID</th>
            <th>Action</th>
        </tr>
        <?php foreach($allEmployees as $emp): ?>
        <tr>
            <td><img src="<?php echo htmlspecialchars((string)($emp['photo'] ?: 'https://via.placeholder.com/60'), ENT_QUOTES, 'UTF-8'); ?>" style="width:50px;height:50px;border-radius:50%;object-fit:cover;"></td>
            <td><?php echo htmlspecialchars((string)$emp['name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$emp['employee_id'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)$emp['uid'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><a class="btn-edit" href="register.php?edit_uid=<?php echo urlencode((string)$emp['uid']); ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>