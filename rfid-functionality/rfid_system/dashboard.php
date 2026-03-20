<?php
date_default_timezone_set('Asia/Manila'); 
?>
<!DOCTYPE html>
<html>
<head>
<title>RFID Attendance Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Container matches other pages */
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    display: flex;
    justify-content: center;
}

/* Card styling using global card class */
.card {
    background: #fff;
    border-radius: 10px;
    padding: 30px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    text-align: center;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

/* Photo */
.photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin-bottom: 15px;
    object-fit: cover;
    border: 3px solid #0b7a3e; /* matches nav color */
}

/* Titles and info */
h1 {
    color: #0b7a3e; /* matches headings */
    margin-bottom: 20px;
}

h2.waiting {
    color: #555;
    font-style: italic;
}

.info {
    font-size: 16px;
    margin: 6px 0;
    color: #333;
}

.status {
    font-weight: bold;
    color: #0b7a3e; /* status color consistent */
    margin-top: 10px;
}
</style>
<script>
let lastUID = "";      
let lastTime = "";     

function formatLocalTime(utcString){
    if(!utcString) return "";
    const d = new Date(utcString);
    const options = { 
        year:'numeric', month:'short', day:'2-digit', 
        hour:'2-digit', minute:'2-digit', hour12:true 
    };
    return d.toLocaleString(undefined, options);
}

function loadLatestTap() {
    fetch("get_latest_tap.php")
    .then(res => res.json())
    .then(data => {
        const photo = document.getElementById("photo");
        const name = document.getElementById("name");
        const empid = document.getElementById("empid");
        const birthday = document.getElementById("birthday");
        const time = document.getElementById("time");
        const status = document.getElementById("status");

        const currentUID = data.uid || "";
        const currentTime = data.time || "";

        if(currentUID === "" || currentTime === "" || (currentUID === lastUID && currentTime === lastTime)){
            return; 
        }

        lastUID = currentUID;
        lastTime = currentTime;

        const localTime = formatLocalTime(currentTime);

        if(data.name === "CARD NOT REGISTERED"){
            photo.src = "https://via.placeholder.com/150";
            name.innerText = "Card Not Registered";
            empid.innerText = "UID: " + currentUID;
            birthday.innerText = "";
            time.innerText = "Time: " + localTime;
            status.innerText = "Please register this card!";
        } else {
            photo.src = data.photo || "https://via.placeholder.com/150";
            name.innerText = data.name;
            empid.innerText = "Employee ID: " + data.employee_id;
            birthday.innerText = "Birthday: " + data.birthday;
            time.innerText = "Time: " + localTime;
            status.innerText = "Status: " + data.tap_type;
        }
    })
    .catch(err => console.log(err));
}

setInterval(loadLatestTap, 2000);
</script>
</head>
<body>
<?php include "nav.php"; ?>

<div class="container">
    <div class="card">
        <h1>RFID Attendance Dashboard</h1>
        <img id="photo" class="photo" src="https://via.placeholder.com/150">
        <h2 id="name" class="waiting">Waiting for card...</h2>
        <p class="info" id="empid"></p>
        <p class="info" id="birthday"></p>
        <p class="info" id="time"></p>
        <p class="info status" id="status"></p>
    </div>
</div>
</body>
</html>