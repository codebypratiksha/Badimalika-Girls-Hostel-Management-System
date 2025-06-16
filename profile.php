<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch student info (without registered_by)
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch all vacant rooms (without filtering by admin)
$rooms = [];
$stmt = $conn->prepare("SELECT room_number, capacity, notes FROM rooms WHERE availability = 'Available'");
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Student Profile & Available Rooms</title>
    <style>
        /* same styling as before */
        body {
            font-family: Arial, sans-serif;
            background: #f7f9ff;
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        .profile-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 700px;
            width: 100%;
        }
        h2 {
            color: #4d93f6;
            margin-bottom: 20px;
        }
        .info-group {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
        }
        .room-list {
            margin-top: 30px;
        }
        .room-card {
            background: #f1f5ff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 5px solid #4d93f6;
        }
        .back-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4d93f6;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        .back-btn:hover {
            background-color: #377de5;
        }
    </style>
</head>
<body>

<div class="profile-box">
    <h2>Your Profile</h2>
    <div class="info-group"><span class="info-label">Name:</span> <?= htmlspecialchars($user['name']); ?></div>
    <div class="info-group"><span class="info-label">Email:</span> <?= htmlspecialchars($user['email']); ?></div>

    <h2 class="room-list">Vacant Rooms</h2>
    <?php if (!empty($rooms)): ?>
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <strong>Room Number:</strong> <?= htmlspecialchars($room['room_number']); ?><br>
                <strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']); ?><br>
                <strong>Notes:</strong> <?= htmlspecialchars($room['notes']); ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:red;">No available rooms at the moment.</p>
    <?php endif; ?>

    <button class="back-btn" onclick="window.location.href='dashboard_student.php'">Back to Dashboard</button>
</div>

</body>
</html>
