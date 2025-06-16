<?php
session_start();
require 'config.php'; // Ensure DB is included if you later want to fetch details

// Check if the user is logged in and is a normal user (not admin)
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: login_form.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Dashboard</title>
<link rel="stylesheet" href="style.css" />
<style>
    body {
        background: #f7f9ff;
        font-family: Arial, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
    }
    .box {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    h1 {
        color: #4d93f6;
        margin-bottom: 10px;
    }
    p {
        font-size: 18px;
        margin: 10px 0;
    }
    span {
        font-weight: bold;
        color: #4d93f6;
    }
    button {
        background-color: #4d93f6;
        color: white;
        border: none;
        padding: 10px 20px;
        margin: 10px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
    }
    button:hover {
        background-color: #377de5;
    }
</style>
</head>
<body>

<div class="box">
    <h1>Welcome, <a href="dashboard_student.php" style="color:#4d93f6; text-decoration:none;">
        <?= htmlspecialchars($_SESSION['name']); ?>
    </a></h1>

    <p>You are logged in as a <span>user</span>. Your account was registered by the admin.</p>


    <button onclick="window.location.href='logout.php'">Logout</button>
</div>

</body>
</html>
