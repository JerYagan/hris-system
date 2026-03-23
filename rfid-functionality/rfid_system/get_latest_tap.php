<?php
include "config.php";
include "hris-registry.php";

$mapResultCodeToTapType = static function (?string $resultCode): string {
   $normalized = strtoupper(trim((string)$resultCode));
   return match ($normalized) {
      'TIME_IN_LOGGED' => 'LOGIN',
      'TIME_OUT_LOGGED' => 'LOGOUT',
      'DUPLICATE_IGNORED' => 'DUPLICATE',
      'ATTENDANCE_ALREADY_COMPLETE' => 'COMPLETE',
      default => $normalized,
   };
};

/* -------------------------
   GET LAST TAP
-------------------------*/
$url = $supabase_url . "rfid_scan_events?select=card_uid,scanned_at,result_code&order=created_at.desc&limit=1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

/* -------------------------
   EMPTY TABLE
-------------------------*/
if(empty($data) || !isset($data[0]['card_uid'])){
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
$uid = strtoupper(trim((string)($latest['card_uid'] ?? "")));
$time = $latest['scanned_at'] ?? "";
$tap_type = $mapResultCodeToTapType($latest['result_code'] ?? "");

$employeeRoster = legacyRfidRegistryBuildRoster($supabase_url, $headers, $api_key, $appBaseUrl);
$employeesByUid = legacyRfidRegistryIndexByUid($employeeRoster);
$employee = $employeesByUid[$uid] ?? [];

/* -------------------------
   CARD NOT REGISTERED
-------------------------*/
if($employee === []){
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
   "name" => $employee['name'] ?? '',
   "employee_id" => $employee['employee_id'] ?? '',
   "birthday" => $employee['birthday'] ?? '',
   "photo" => $employee['photo'] ?? '',
    "time" => $time,
    "tap_type" => $tap_type
];

echo json_encode($result);
?>