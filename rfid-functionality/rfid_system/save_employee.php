<?php
session_start();
include "config.php";

// Get POST data
$uid = $_POST['uid'];
$employee_id = $_POST['employee_id'];
$name = $_POST['name'];
$birthday = $_POST['birthday'];
$photo = "";

// Handle photo upload
if(isset($_FILES['photo']) && $_FILES['photo']['tmp_name'] != ""){
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = "upload/".$uid.".".$ext;
    move_uploaded_file($_FILES['photo']['tmp_name'],$photoName);
    $photo = $photoName;
}

// 1️⃣ Check if employee already exists
$check_url = $supabase_url . "employees?uid=eq.$uid";
$ch = curl_init($check_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$existing = json_decode($response,true);

if(!empty($existing)){
    // 2️⃣ Update existing employee (PATCH)
    $update_data = [
        "employee_id"=>$employee_id,
        "name"=>$name,
        "birthday"=>$birthday
    ];
    if($photo != "") $update_data['photo'] = $photo;

    $ch = curl_init($supabase_url . "employees?uid=eq.$uid");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($update_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);

    $_SESSION['save_message'] = "Employee info updated successfully!";
}else{
    // 3️⃣ Insert new employee (POST)
    $insert_data = [
        "uid"=>$uid,
        "employee_id"=>$employee_id,
        "name"=>$name,
        "birthday"=>$birthday,
        "photo"=>$photo
    ];

    $ch = curl_init($supabase_url . "employees");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($insert_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);

    $_SESSION['save_message'] = "Employee registered successfully!";
}

// 4️⃣ Redirect back to register.php to show message + back button
header("Location: register.php");
exit;
?>