<?php

include "config.php";

$uid = $_GET['uid'] ?? "";

if(!$uid){
echo "No UID detected";
exit;
}

/* GET EMPLOYEE */

$url = $supabase_url . "employees?uid=eq.$uid&select=*";

$ch = curl_init($url);

curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$response = curl_exec($ch);

$emp = json_decode($response,true);

if(empty($emp)){

echo "Card not registered";
exit;

}

$employee = $emp[0];

$employee_id = $employee['employee_id'];
$name = $employee['name'];

/* GET LAST TAP TODAY */

$today = date("Y-m-d");

$url = $supabase_url .
"attendance_logs?uid=eq.$uid&time=gte.$today&order=time.desc&limit=1";

$ch = curl_init($url);

curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$response = curl_exec($ch);

$last = json_decode($response,true);

/* DETERMINE TAP TYPE */

$tap_type = "LOGIN";

if(!empty($last)){

$prev = $last[0]['tap_type'];

switch($prev){

case "LOGIN":
$tap_type = "BREAK_START";
break;

case "BREAK_START":
$tap_type = "BREAK_END";
break;

case "BREAK_END":
$tap_type = "LOGOUT";
break;

case "LOGOUT":
$tap_type = "LOGIN";
break;

}

}

/* INSERT ATTENDANCE */

$data = [

"uid"=>$uid,
"employee_id"=>$employee_id,
"name"=>$name,
"time"=>date("Y-m-d H:i:s"),
"tap_type"=>$tap_type

];

$ch = curl_init($supabase_url . "attendance_logs");

curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

curl_exec($ch);

echo "Saved";

?>