<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require 'config.php';

$user_id = $_SESSION['id'];

// Fetch user registration date
$stmt = $conn->prepare("SELECT created_at, name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_created_at = null;
$user_name = "Student";
if ($row = $result->fetch_assoc()) {
    $user_created_at = $row['created_at'];
    $user_name = $row['name'];
}
$stmt->close();

// Safety fallback if no created_at found, set to very old date
if (!$user_created_at) {
    $user_created_at = '1970-01-01 00:00:00';
}

// Fetch latest allocated room info (ignore created_at filter to fix issue)
$room = null;
$stmt = $conn->prepare("SELECT r.room_number FROM allocations a 
                        JOIN rooms r ON a.room_id = r.id 
                        WHERE a.student_id = ? 
                        ORDER BY a.allocation_start DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
}
$stmt->close();

// Latest payment info paid AFTER user registration
$payment = null;
$stmt = $conn->prepare("SELECT amount, status, date_paid FROM payments 
                        WHERE user_id = ? AND date_paid >= ? 
                        ORDER BY date_paid DESC LIMIT 1");
$stmt->bind_param("is", $user_id, $user_created_at);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $payment = $result->fetch_assoc();
}
$stmt->close();

// Complaints status created AFTER user registration
$pending = 0;
$replied = 0;
$stmt = $conn->prepare("SELECT 
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                        SUM(CASE WHEN status != 'Pending' THEN 1 ELSE 0 END) AS replied
                        FROM complaints 
                        WHERE user_id = ? AND created_at >= ?");
$stmt->bind_param("is", $user_id, $user_created_at);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pending = $row['pending'] ?? 0;
    $replied = $row['replied'] ?? 0;
}
$stmt->close();

// Count recent hostel notices (added in last 7 days and after user registration date)
$new_notices = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count 
                        FROM notices 
                        WHERE created_at >= GREATEST(NOW() - INTERVAL 7 DAY, ?)");
$stmt->bind_param("s", $user_created_at);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $new_notices = $row['count'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Dashboard</title>
<style>
    body {
        background: #f7f9ff;
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }
    .dashboard-box {
        background: #fff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        text-align: center;
        width: 100%;
        max-width: 600px;
    }
    h1 {
        color: #4d93f6;
        margin-bottom: 20px;
    }
    .feature {
        background: #f1f5ff;
        border: 1px solid #e0e7ff;
        padding: 18px;
        margin: 10px 0;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }
    .feature:hover {
        background: #e0eaff;
        color: #4d93f6;
        border-color: #4d93f6;
    }
    .logout-btn {
        background: #4d93f6;
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 6px;
        margin-top: 20px;
        font-size: 16px;
        cursor: pointer;
    }
    .logout-btn:hover {
        background: #3b7eea;
    }
    .notification-bell {
        color: red;
        font-size: 18px;
        margin-left: 5px;
    }
</style>
</head>
<body>

<div class="dashboard-box">
    <h1>Hello, <?= htmlspecialchars($user_name); ?>!</h1>
    <p>You are logged in as a <strong>student</strong>.</p>

    <div class="feature" onclick="window.location.href='profile.php'">
        <span>Profile & Room Info</span>
        <strong><?= $room ? "Room #" . htmlspecialchars($room['room_number']) : "Not Assigned" ?></strong>
    </div>

    <div class="feature" onclick="window.location.href='payments.php'">
        <span>Payment Details</span>
        <strong><?= $payment ? htmlspecialchars($payment['status']) : "No Payments" ?></strong>
    </div>

    <div class="feature" onclick="window.location.href='complaints.php'">
        <span>
            Complaints & Feedback 
            <?php if ($replied > 0): ?>
                <span class="notification-bell">🔔</span>
            <?php endif; ?>
        </span>
        <strong>
            <?= $pending ?> Pending<?= $replied > 0 ? " / $replied Replied" : "" ?>
        </strong>
    </div>

    <div class="feature" onclick="window.location.href='notices.php'">
        <span>
            Hostel Notices
            <?php if ($new_notices > 0): ?>
                <span class="notification-bell">🔔</span>
            <?php endif; ?>
        </span>
        <strong><?= $new_notices ?> </strong>
    </div>

    <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
</div>

</body>
</html>
