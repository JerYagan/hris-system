<?php
include "config.php";

$url = $supabase_url . "employees?select=*&order=name.asc";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$employees = json_decode($response,true);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Container consistent with dashboard/attendance */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #1f8f4e;
            margin-bottom: 30px;
        }

        /* Table styling */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            font-size: 14px;
        }

        .table th {
            background: #1f8f4e;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .table tr:hover {
            background: #f1fdf5;
        }

        /* Profile photo */
        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Buttons */
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-edit {
            background: #2ecc71;
            color: white;
        }

        .btn-edit:hover {
            background: #27ae60;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        
    </style>
</head>

<body>
<?php include "nav.php"; ?>

<div class="container">
    <h1>Registered Employees</h1>

    <table class="table">
        <tr>
            <th>Photo</th>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Birthday</th>
            <th>UID</th>
            <th>Action</th>
        </tr>

        <?php foreach($employees as $emp): ?>
        <tr>
            <td>
                <img class="profile-img" src="<?php echo $emp['photo'] ?: 'https://via.placeholder.com/60'; ?>">
            </td>
            <td><?php echo $emp['employee_id']; ?></td>
            <td><?php echo $emp['name']; ?></td>
            <td><?php echo $emp['birthday']; ?></td>
            <td><?php echo $emp['uid']; ?></td>
            <td>
                <a class="btn-edit" href="register.php?edit_uid=<?php echo $emp['uid']; ?>">Edit</a>
                <a class="btn-delete" href="delete_employee.php?uid=<?php echo $emp['uid']; ?>" onclick="return confirm('Delete this employee?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>