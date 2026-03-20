<?php
include "config.php";

/* -------------------------
   GET LAST TAP
-------------------------*/
$url = $supabase_url . "attendance_logs?select=uid,time_in,tap_type&order=id.desc&limit=1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

/* -------------------------
   EMPTY TABLE
-------------------------*/
if(empty($data) || !isset($data[0]['uid'])){
    echo json_encode([
        "uid" => "",
        "name" => "No Tap Yet",
        "employee_id" => "",
        "birthday" => "",
        "photo" => "https://via.placeholder.com/150",
        "time" => "",
        "tap_type" => ""
    ]);
    exit;
}

/* -------------------------
   GET LATEST TAP
-------------------------*/
$latest = $data[0];
$uid = $latest['uid'] ?? "";
$time = $latest['time_in'] ?? "";
$tap_type = $latest['tap_type'] ?? "";

/* -------------------------
   GET EMPLOYEE INFO
-------------------------*/
$url2 = $supabase_url . "employees?uid=eq." . $uid;

$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

$response2 = curl_exec($ch2);
$emp = json_decode($response2, true);

/* -------------------------
   CARD NOT REGISTERED
-------------------------*/
if(empty($emp) || !isset($emp[0]['name'])){
    echo json_encode([
        "uid" => $uid,
        "name" => "CARD NOT REGISTERED",
        "employee_id" => $uid,
        "birthday" => "",
        "photo" => "https://via.placeholder.com/150",
        "time" => $time,
        "tap_type" => $tap_type
    ]);
    exit;
}

/* -------------------------
   RETURN FULL INFO
-------------------------*/
$result = [
    "uid" => $uid,
    "name" => $emp[0]['name'],
    "employee_id" => $emp[0]['employee_id'],
    "birthday" => $emp[0]['birthday'],
    "photo" => $emp[0]['photo'],
    "time" => $time,
    "tap_type" => $tap_type
];

echo json_encode($result);
?>