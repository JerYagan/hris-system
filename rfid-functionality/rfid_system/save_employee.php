<?php
session_start();
include "config.php";

if (!function_exists('legacyRfidSaveRedirect')) {
    function legacyRfidSaveRedirect(?string $personId = null, ?string $uid = null): void
    {
        $query = [];
        $personIdValue = trim((string)$personId);
        $uidValue = trim((string)$uid);

        if ($personIdValue !== '') {
            $query['person_id'] = $personIdValue;
        }
        if ($uidValue !== '') {
            $query['edit_uid'] = $uidValue;
        }

        $location = 'register.php';
        if ($query !== []) {
            $location .= '?' . http_build_query($query);
        }

        header('Location: ' . $location);
        exit;
    }
}

$uid = strtoupper(trim((string)($_POST['uid'] ?? '')));
$person_id = trim((string)($_POST['person_id'] ?? ''));
$employee_id = trim((string)($_POST['employee_id'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$birthday = trim((string)($_POST['birthday'] ?? ''));
$photo = '';

if($uid === '' || $employee_id === '' || $name === ''){
    $_SESSION['save_message'] = 'UID, employee ID, and name are required.';
    $_SESSION['save_message_type'] = 'error';
    legacyRfidSaveRedirect($person_id);
}

if(isset($_FILES['photo']) && $_FILES['photo']['tmp_name'] != ''){
    if(!is_dir('uploads')){
        mkdir('uploads', 0777, true);
    }

    $ext = pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = 'uploads/' . time() . '_' . basename($uid . ($ext !== '' ? '.' . $ext : ''));
    if(move_uploaded_file($_FILES['photo']['tmp_name'], $photoName)){
        $photo = $photoName;
    }
}

$checkUrl = $supabase_url . 'employees?uid=eq.' . rawurlencode($uid);
$checkCh = curl_init($checkUrl);
curl_setopt($checkCh, CURLOPT_HTTPHEADER, $headers);
curl_setopt($checkCh, CURLOPT_RETURNTRANSFER, true);
$checkResponse = curl_exec($checkCh);
$checkStatus = (int)curl_getinfo($checkCh, CURLINFO_HTTP_CODE);
$checkError = curl_error($checkCh);
curl_close($checkCh);

$existing = json_decode((string)$checkResponse, true);
if($checkError !== '' || $checkStatus < 200 || $checkStatus >= 300 || !is_array($existing)){
    $_SESSION['save_message'] = 'Unable to verify existing RFID employee record.' . ($checkResponse ? ' ' . trim((string)$checkResponse) : '');
    $_SESSION['save_message_type'] = 'error';
    legacyRfidSaveRedirect($person_id);
}

if(!empty($existing)){
    $updateData = [
        'employee_id' => $employee_id,
        'name' => $name,
        'birthday' => $birthday,
    ];
    if($person_id !== '') {
        $updateData['person_id'] = $person_id;
    }
    if($photo !== '') {
        $updateData['photo'] = $photo;
    }

    $updateCh = curl_init($supabase_url . 'employees?uid=eq.' . rawurlencode($uid));
    curl_setopt($updateCh, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($updateCh, CURLOPT_HTTPHEADER, array_merge($headers, ['Prefer: return=representation']));
    curl_setopt($updateCh, CURLOPT_POSTFIELDS, json_encode($updateData));
    curl_setopt($updateCh, CURLOPT_RETURNTRANSFER, true);
    $updateResponse = curl_exec($updateCh);
    $updateStatus = (int)curl_getinfo($updateCh, CURLINFO_HTTP_CODE);
    $updateError = curl_error($updateCh);
    curl_close($updateCh);

    if($updateError !== '' || $updateStatus < 200 || $updateStatus >= 300){
        $_SESSION['save_message'] = 'Employee update failed.' . ($updateResponse ? ' ' . trim((string)$updateResponse) : '');
        $_SESSION['save_message_type'] = 'error';
        legacyRfidSaveRedirect($person_id, $uid);
    }

    $_SESSION['save_message'] = 'Employee info updated successfully!';
    $_SESSION['save_message_type'] = 'success';
    legacyRfidSaveRedirect($person_id, $uid);
}

$insertData = [
    'uid' => $uid,
    'employee_id' => $employee_id,
    'name' => $name,
    'birthday' => $birthday,
];
if($photo !== '') {
    $insertData['photo'] = $photo;
}
if($person_id !== '') {
    $insertData['person_id'] = $person_id;
}

$insertCh = curl_init($supabase_url . 'employees');
curl_setopt($insertCh, CURLOPT_POST, true);
curl_setopt($insertCh, CURLOPT_HTTPHEADER, array_merge($headers, ['Prefer: return=representation']));
curl_setopt($insertCh, CURLOPT_POSTFIELDS, json_encode([$insertData]));
curl_setopt($insertCh, CURLOPT_RETURNTRANSFER, true);
$insertResponse = curl_exec($insertCh);
$insertStatus = (int)curl_getinfo($insertCh, CURLINFO_HTTP_CODE);
$insertError = curl_error($insertCh);
curl_close($insertCh);

if($insertError !== '' || $insertStatus < 200 || $insertStatus >= 300){
    $_SESSION['save_message'] = 'Employee registration failed.' . ($insertResponse ? ' ' . trim((string)$insertResponse) : '');
    $_SESSION['save_message_type'] = 'error';
    legacyRfidSaveRedirect($person_id);
}

$_SESSION['save_message'] = 'Employee registered successfully!';
$_SESSION['save_message_type'] = 'success';
legacyRfidSaveRedirect($person_id, $uid);
?>