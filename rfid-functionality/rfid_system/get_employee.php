<?php

$url = $supabase_url . "employees?uid=eq.$uid";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

$emp = json_decode($response,true);

$result = [

"name"=>$emp[0]['name'],
"employee_id"=>$emp[0]['employee_id'],
"birthday"=>$emp[0]['birthday'],
"photo"=>$emp[0]['photo'],
"time_in"=>$time

];

echo json_encode($result);

?>