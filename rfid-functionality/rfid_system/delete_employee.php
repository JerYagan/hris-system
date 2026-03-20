<?php

include "config.php";

$uid = $_GET['uid'];

$url = $supabase_url . "employees?uid=eq.$uid";

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_exec($ch);

header("Location: employees.php");

?>