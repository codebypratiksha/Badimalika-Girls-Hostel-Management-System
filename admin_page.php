<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .box {
            background: #f2f2f2;
            padding: 30px;
            margin: 80px auto;
            text-align: center;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        .box span {
            color: #0077cc;
            font-weight: bold;
        }
        .box button {
            margin: 10px 10px 0;
            padding: 10px 20px;
            background-color: #0077cc;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .box button:hover {
            background-color: #005fa3;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>Welcome, <span><?= htmlspecialchars($_SESSION['name']); ?></span></h1>
    <p>This is an <span>admin</span> page</p>

    <!-- New Go to Dashboard button -->
    <button onclick="window.location.href='dashboard_admin.php'">Go to Dashboard</button>

    <!-- Logout button -->
    <button onclick="window.location.href='logout.php'">Logout</button>
</div>

</body>
</html>
