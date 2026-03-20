<?php
include "config.php";
date_default_timezone_set('Asia/Manila'); // Local timezone

$date = $_GET['date'] ?? "";
$uidFilter = $_GET['uid'] ?? "";

// Build Supabase query
$query = "select=*";
if($date != "") $query .= "&time_in=gte.$date";
if($uidFilter != "") $query .= "&uid=eq.$uidFilter";
$query .= "&order=time_in.asc";
$url = $supabase_url . "attendance_logs?$query";

// Fetch logs
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$logs = json_decode($response,true);
if(!is_array($logs)) $logs = [];

// Fetch all employees
$empUrl = $supabase_url . "employees?select=*";
$empCh = curl_init($empUrl);
curl_setopt($empCh, CURLOPT_HTTPHEADER, $headers);
curl_setopt($empCh, CURLOPT_RETURNTRANSFER, true);
$empResp = curl_exec($empCh);
$employees = json_decode($empResp,true);

$empMap = [];
foreach($employees as $e) $empMap[$e['uid']] = $e;

// Match employees to logs & convert UTC to local time
foreach($logs as $i=>$log){
    $uid = $log['uid'] ?? "";
    $logs[$i]['employee'] = $empMap[$uid] ?? [];

    if(isset($logs[$i]['time_in'])){
        $dt = new DateTime($logs[$i]['time_in'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $logs[$i]['time_in_local'] = $dt->format('Y-m-d H:i:s');
    } else $logs[$i]['time_in_local'] = "";
}

// Build summary
$summary = [];
foreach($logs as $log){
    $emp = $log['employee'] ?? [];
    $name = $emp['name'] ?? "Unknown";
    $emp_id = $emp['employee_id'] ?? "";
    $type = $log['tap_type'] ?? "";
    $time = $log['time_in_local'] ?? "";
    if(!$time) continue;

    if(!isset($summary[$name])){
        $summary[$name] = [
            "employee_id"=>$emp_id,
            "photo"=>$emp['photo'] ?? '',
            "LOGIN"=>"",
            "BREAK_START"=>"",
            "BREAK_END"=>"",
            "LOGOUT"=>""
        ];
    }

    if(isset($summary[$name][$type])) $summary[$name][$type] = date("h:i A", strtotime($time));
}

// Dashboard stats
$totalLogs = count($logs);
$today = date("Y-m-d");
$todayLogs = 0;
foreach($logs as $l) if(strpos($l['time_in_local'],$today)!==false) $todayLogs++;
?>
<!DOCTYPE html>
<html>
<head>
<title>Attendance Logs</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Container same as dashboard */
.container { max-width: 1200px; margin: 40px auto; padding: 20px; }

/* Dashboard stats */
.stats { display:flex; gap:20px; margin-bottom:30px; }
.stat { background:white; padding:20px 25px; border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.08); border-left:6px solid #1f8f4e; flex:1; text-align:center; }
.stat h3{ margin:0; color:#1f8f4e; font-size:28px; }
.stat p{ margin:5px 0 0; color:#555; }

/* Summary cards */
.summary{ display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px; }
.card{ background:white; border-radius:15px; padding:20px; width:200px; box-shadow:0 6px 15px rgba(0,0,0,0.08); text-align:center; transition:transform 0.2s; }
.card:hover{ transform:translateY(-5px);}
.card img{ width:60px; height:60px; border-radius:50%; margin-bottom:10px; object-fit:cover;}
.badge{ padding:4px 8px; border-radius:4px; color:white; font-size:12px; }
.login{background:#2ecc71;}
.break{background:#f39c12;}
.logout{background:#e74c3c;}

/* Table */
.table{ width:100%; border-collapse:collapse; background:white; box-shadow:0 6px 15px rgba(0,0,0,0.05);}
.table th{ background:#1f8f4e; color:white; padding:12px; text-align:center;}
.table td{ padding:10px; border-bottom:1px solid #eee; text-align:center;}
.table tr:hover{ background:#f1fdf5; }

/* Filter form */
form{ margin-bottom:25px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;}
form input[type="text"], form input[type="date"]{ padding:8px; border-radius:5px; border:1px solid #ccc; }

/* Popup */
#scanPopup{ position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); display:none; justify-content:center; align-items:center; z-index:999; }
.popup-card{ background:white; padding:30px; border-radius:12px; text-align:center; width:300px; box-shadow:0 6px 15px rgba(0,0,0,0.1);}
.popup-card img{ width:100px; height:100px; border-radius:50%; margin-bottom:10px; object-fit:cover;}
</style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="container">
<h1>Attendance Logs</h1>

<div class="stats">
    <div class="stat"><h3><?php echo $totalLogs; ?></h3><p>Total Logs</p></div>
    <div class="stat"><h3><?php echo $todayLogs; ?></h3><p>Today's Scans</p></div>
</div>

<form method="GET">
    <label>Date:</label>
    <input type="date" name="date" value="<?php echo $date; ?>">
    <label>UID:</label>
    <input type="text" name="uid" value="<?php echo htmlspecialchars($uidFilter); ?>">
    <button type="submit">Filter</button>
    <a href="attendance.php"><button type="button">Show All</button></a>
</form>

<h2>Employee Summary</h2>
<div class="summary" id="summaryCards">
<?php foreach($summary as $name=>$row): ?>
<div class="card" id="card-<?php echo htmlspecialchars($row['employee_id']); ?>">
<img src="<?php echo $row['photo'] ?: 'https://via.placeholder.com/60'; ?>">
<p><strong><?php echo $name; ?></strong></p>
<p>Login: <span class="badge login"><?php echo $row['LOGIN']; ?></span></p>
<p>Break Start: <span class="badge break"><?php echo $row['BREAK_START']; ?></span></p>
<p>Break End: <span class="badge break"><?php echo $row['BREAK_END']; ?></span></p>
<p>Logout: <span class="badge logout"><?php echo $row['LOGOUT']; ?></span></p>
</div>
<?php endforeach; ?>
</div>

<table class="table" id="attendanceTable">
<tr>
<th>Employee ID</th>
<th>Name</th>
<th>Date</th>
<th>Time</th>
<th>Status</th>
<th>UID</th>
</tr>
<?php foreach($logs as $log):
$emp = $log['employee'] ?? [];
$time = $log['time_in_local'] ?? "";
$dateDisplay = $time ? date("M d, Y", strtotime($time)) : "";
$timeDisplay = $time ? date("h:i A", strtotime($time)) : "";
$status = $log['tap_type'] ?? "";
$class="";
if($status=="LOGIN") $class="login";
elseif($status=="BREAK_START" || $status=="BREAK_END") $class="break";
elseif($status=="LOGOUT") $class="logout";
?>
<tr>
<td><?php echo $emp['employee_id'] ?? ''; ?></td>
<td><?php echo $emp['name'] ?? ''; ?></td>
<td><?php echo $dateDisplay; ?></td>
<td><?php echo $timeDisplay; ?></td>
<td><span class="badge <?php echo $class; ?>"><?php echo $status; ?></span></td>
<td><?php echo $log['uid'] ?? ''; ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div id="scanPopup">
<div class="popup-card">
<img id="popupPhoto">
<h2 id="popupName"></h2>
<p id="popupStatus"></p>
</div>
</div>

<script>
let lastTime="";
function formatLocalTime(dateString){
    if(!dateString) return {date:"", time:""};
    let d = new Date(dateString);
    return {date: d.toLocaleDateString(), time: d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})};
}

function loadLatestTap(){
    fetch("get_latest_tap.php")
    .then(res=>res.json())
    .then(data=>{
        if(!data.time || data.time===lastTime) return;
        lastTime=data.time;

        const table=document.getElementById("attendanceTable");
        const row=table.insertRow(1);

        const local = formatLocalTime(data.time);

        row.insertCell(0).innerText=data.employee_id||"";
        row.insertCell(1).innerText=data.name||"";
        row.insertCell(2).innerText=local.date;
        row.insertCell(3).innerText=local.time;
        row.insertCell(4).innerText=data.tap_type||"";
        row.insertCell(5).innerText=data.uid||"";

        document.getElementById("scanPopup").style.display="flex";
        document.getElementById("popupPhoto").src = data.photo || "https://via.placeholder.com/100";
        document.getElementById("popupName").innerText = data.name || "Unknown";
        document.getElementById("popupStatus").innerText = data.tap_type;
        setTimeout(()=>{ document.getElementById("scanPopup").style.display="none"; },3000);
    })
    .catch(err=>console.log(err));
}

setInterval(loadLatestTap,2000);
</script>
</body>
</html>