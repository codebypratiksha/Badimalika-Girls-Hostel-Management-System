<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

require 'config.php';

$admin_id = $_SESSION['id'] ?? 0;

// Get admin registration date
$stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

$registered_on = $admin['created_at'] ?? null;

// If no registration date found, default to very old date so data will show
if (!$registered_on) {
    $registered_on = '1970-01-01 00:00:00';
}

// Count students registered BY THIS ADMIN only, after admin registration date
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE role='user' AND created_by = ? AND created_at >= ?");
$stmt->bind_param("is", $admin_id, $registered_on);
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total_users'] ?? 0;
$stmt->close();

// Count all rooms created after admin registration date (no admin filter)
$stmt = $conn->prepare("SELECT COUNT(*) as total_rooms FROM rooms WHERE created_at >= ?");
$stmt->bind_param("s", $registered_on);
$stmt->execute();
$result = $stmt->get_result();
$total_rooms = $result->fetch_assoc()['total_rooms'] ?? 0;
$stmt->close();

// Count payments marked Paid and after admin registered
$stmt = $conn->prepare("SELECT COUNT(*) as total_payments FROM payments WHERE status='Paid' AND payment_date >= ?");
$stmt->bind_param("s", $registered_on);
$stmt->execute();
$result = $stmt->get_result();
$total_payments = $result->fetch_assoc()['total_payments'] ?? 0;
$stmt->close();

// Count pending complaints created after admin registration
$stmt = $conn->prepare("SELECT COUNT(*) as pending_complaints FROM complaints WHERE status='Pending' AND created_at >= ?");
$stmt->bind_param("s", $registered_on);
$stmt->execute();
$result = $stmt->get_result();
$pending_complaints = $result->fetch_assoc()['pending_complaints'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <style>
        .dashboard-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #4d93f6;
            margin-bottom: 30px;
        }
        .stats-box {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .stat {
            background: #f7f9ff;
            border-radius: 8px;
            width: 180px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0 6px rgba(0,0,0,0.05);
        }
        .stat h3 {
            color: #4d93f6;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .stat p {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
        }
        .nav-links {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: #4d93f6;
            border: 1px solid #4d93f6;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: 0.3s ease;
        }
        .nav-links a:hover {
            background: #4d93f6;
            color: white;
        }
        .logout-btn {
            display: block;
            width: 100%;
            margin-top: 40px;
            padding: 12px;
            font-size: 18px;
            background: #4d93f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #3c7dd9;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h2>Welcome Admin, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>

    <div class="stats-box">
        <div class="stat">
            <h3>Students</h3>
            <p><?= $total_users ?></p>
        </div>
        <div class="stat">
            <h3>Rooms</h3>
            <p><?= $total_rooms ?></p>
        </div>
        <div class="stat">
            <h3>Payments</h3>
            <p><?= $total_payments ?></p>
        </div>
        <div class="stat">
            <h3>Complaints</h3>
            <p><?= $pending_complaints ?></p>
        </div>
    </div>

    <div class="nav-links">
        <a href="student_registration_form.php">Register Student</a>
        <a href="manage_rooms.php">Allocate Room</a>
        <a href="manage_payments.php">Manage Payments</a>
        <a href="manage_complaints.php">Handle Complaints</a>
        <a href="post_notice.php">Post Notices</a>
    </div>

    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
</div>

</body>
</html>
