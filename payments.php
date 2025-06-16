<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Get user's registration date
$stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($registration_date);
$stmt->fetch();
$stmt->close();

if (!$registration_date) {
    $registration_date = date('Y-m-d H:i:s'); // fallback to current date if missing
}

// Fetch the latest allocation for the student (order by allocation_start DESC)
$stmt = $conn->prepare("
    SELECT allocation_start, allocation_end 
    FROM allocations 
    WHERE student_id = ? 
    ORDER BY allocation_start DESC 
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$due_message = "No active room allocation found.";

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $allocation_start = new DateTime($row['allocation_start']);
    $allocation_end = new DateTime($row['allocation_end']);
    $registration_dt = new DateTime($registration_date);
    $today = new DateTime();

    // Ignore allocations that ended before registration date
    if ($allocation_end < $registration_dt) {
        $due_message = "No active room allocation found after your registration date.";
    } else {
        // Effective start = later of allocation_start or registration_date
        $effective_start = $allocation_start > $registration_dt ? $allocation_start : $registration_dt;

        if ($today < $effective_start) {
            // Before allocation starts, no payment due yet
            $due_message = "Your payment due starts on " . $effective_start->format('Y-m-d') . ". No payment due yet.";
        } else {
            // Calculate days remaining until allocation_end from today
            $interval = $today->diff($allocation_end);
            $days_remaining = (int)$interval->format('%r%a');

            if ($days_remaining > 5) {
                $due_message = "✅ No payment due yet. You have $days_remaining day(s) remaining.";
            } elseif ($days_remaining >= 0 && $days_remaining <= 5) {
                $due_message = "🔔 Your payment is due in $days_remaining day(s)! Please prepare to pay.";
            } else {
                // days_remaining < 0 means overdue
                $due_message = "⚠️ Your payment is overdue by " . abs($days_remaining) . " day(s). Please pay immediately.";
            }
        }
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payment Due Notice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9ff;
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        .payment-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        h2 {
            color: #4d93f6;
            margin-bottom: 20px;
        }
        .alert {
            font-size: 18px;
            padding: 20px;
            background-color: #f1f5ff;
            border-left: 5px solid #4d93f6;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .back-btn {
            margin-top: 10px;
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

<div class="payment-box">
    <h2>Hello, <?= htmlspecialchars($_SESSION['name']); ?></h2>
    <div class="alert">
        <?= $due_message; ?>
    </div>
    <button class="back-btn" onclick="window.location.href='dashboard_student.php'">Back to Dashboard</button>
</div>

</body>
</html>
