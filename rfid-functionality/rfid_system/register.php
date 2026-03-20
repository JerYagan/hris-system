<?php
include "config.php";

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

/* -----------------------------
   HANDLE FORM SUBMISSION
-----------------------------*/
if($_SERVER['REQUEST_METHOD'] === "POST"){

    $uid = $_POST['uid'] ?? "";
    $name = $_POST['name'] ?? "";
    $employee_id = $_POST['employee_id'] ?? "";
    $birthday = $_POST['birthday'] ?? "";
    $photo = $_FILES['photo'] ?? null;

    /* -----------------------------
       HANDLE PHOTO UPLOAD
    -----------------------------*/
    $photo_url = "";
    if($photo && $photo['tmp_name']){
        $targetDir = "uploads/";
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filename = basename($photo['name']);
        $targetFile = $targetDir . time() . "_" . $filename;

        if(move_uploaded_file($photo['tmp_name'], $targetFile)){
            $photo_url = $targetFile;
        }
    }

    /* -----------------------------
       CHECK IF UID EXISTS
    -----------------------------*/
    $url_check = $supabase_url . "employees?uid=eq.$uid";
    $ch = curl_init($url_check);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response,true);

    if(!empty($data) && isset($data[0]['uid'])){
        // UID exists → UPDATE
        $updateData = [
            "name" => $name,
            "employee_id" => $employee_id,
            "birthday" => $birthday,
        ];
        if($photo_url) $updateData['photo'] = $photo_url;

        $ch2 = curl_init($supabase_url . "employees?uid=eq.$uid");
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($updateData));
        curl_exec($ch2);
        curl_close($ch2);

        $message = "Employee Updated Successfully ✅";

    } else {
        // UID does not exist → INSERT
        $insertData = [
            "uid" => $uid,
            "name" => $name,
            "employee_id" => $employee_id,
            "birthday" => $birthday,
            "photo" => $photo_url
        ];

        $ch3 = curl_init($supabase_url . "employees");
        curl_setopt($ch3, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
        curl_setopt($ch3, CURLOPT_POST, true);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_POSTFIELDS, json_encode([$insertData]));
        curl_exec($ch3);
        curl_close($ch3);

        $message = "Employee Registered Successfully ✅";
    }

    // Show confirmation and redirect
    echo "<script>alert('$message'); window.location='register.php';</script>";
    exit;
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

    <form method="POST" enctype="multipart/form-data">
        <div style="flex:1; min-width:200px;">
            <label>UID:</label>
            <input type="text" name="uid" value="<?php echo $editEmp['uid'] ?? ''; ?>" <?php echo $editEmp ? 'readonly' : ''; ?> required>

            <label>Name:</label>
            <input type="text" name="name" value="<?php echo $editEmp['name'] ?? ''; ?>" required>

            <label>Employee ID:</label>
            <input type="text" name="employee_id" value="<?php echo $editEmp['employee_id'] ?? ''; ?>" required>

            <label>Birthday:</label>
            <input type="date" name="birthday" value="<?php echo $editEmp['birthday'] ?? ''; ?>">
        </div>
        <div style="flex:1; min-width:200px;">
            <label>Photo:</label>
            <input type="file" name="photo">
            <?php if(!empty($editEmp['photo'])): ?>
                <img src="<?php echo $editEmp['photo']; ?>" class="photo-preview">
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
            <td><img src="<?php echo $emp['photo'] ?: 'https://via.placeholder.com/60'; ?>" style="width:50px;height:50px;border-radius:50%;object-fit:cover;"></td>
            <td><?php echo $emp['name']; ?></td>
            <td><?php echo $emp['employee_id']; ?></td>
            <td><?php echo $emp['uid']; ?></td>
            <td><a class="btn-edit" href="register.php?edit_uid=<?php echo $emp['uid']; ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>