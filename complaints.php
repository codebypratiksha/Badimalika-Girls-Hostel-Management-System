<?php
session_start();
require 'config.php';

// Only logged in users (students) can submit complaints
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: login_form.php");
    exit();
}

$message = '';

$user_id = $_SESSION['id'];

// Get user registration date from users table (assuming it is stored as created_at)
$stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($registration_date);
$stmt->fetch();
$stmt->close();

if (!$registration_date) {
    // fallback if registration date is missing - consider current date or null
    $registration_date = date('Y-m-d H:i:s');
}

if (isset($_POST['submit_complaint'])) {
    $complaint_text = trim($_POST['complaint']);

    if (!empty($complaint_text)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, complaint, status, created_at) VALUES (?, ?, 'Pending', NOW())");
        $stmt->bind_param("is", $user_id, $complaint_text);
        if ($stmt->execute()) {
            $message = "Complaint sent successfully.";
        } else {
            $message = "Failed to send complaint. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "Complaint cannot be empty.";
    }
}

// Fetch complaints submitted by this user **after registration date**
$stmt = $conn->prepare("SELECT complaint, status, reply, created_at FROM complaints WHERE user_id = ? AND created_at >= ? ORDER BY created_at DESC");
$stmt->bind_param("is", $user_id, $registration_date);
$stmt->execute();
$result = $stmt->get_result();
$complaints = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Submit Complaint</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: auto; background: #f0f4fb; }
        h2 { color: #4d93f6; }
        form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        textarea { width: 100%; height: 100px; padding: 10px; font-size: 16px; }
        button { background: #4d93f6; color: #fff; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #3b7eea; }
        .message { font-weight: bold; color: green; margin-bottom: 15px; }
        .complaint-list { margin-top: 30px; }
        .complaint-item { background: #fff; padding: 15px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 0 6px #ccc; }
        .status-pending { color: orange; font-weight: bold; }
        .status-resolved { color: green; font-weight: bold; }
        .reply { margin-top: 10px; padding: 10px; background: #e0f7e0; border-left: 4px solid green; white-space: pre-wrap; }
        .welcome-message { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; text-align: center; font-size: 18px; color: #555; margin-top: 40px; }
        .back-btn { margin-top: 20px; background: #6c757d; }
        .back-btn:hover { background: #5a6268; }
    </style>
</head>
<body>

<h2>Submit a Complaint</h2>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <textarea name="complaint" placeholder="Write your complaint here..." required></textarea><br>
    <button type="submit" name="submit_complaint">Send Complaint</button>
</form>

<div class="complaint-list">
    <h3>Your Complaints</h3>

    <?php if (!empty($complaints)): ?>
        <?php foreach ($complaints as $c): ?>
            <div class="complaint-item">
                <div><strong>Complaint:</strong> <?= nl2br(htmlspecialchars($c['complaint'])) ?></div>
                <div>
                    <strong>Status:</strong> 
                    <span class="<?= $c['status'] === 'Pending' ? 'status-pending' : 'status-resolved' ?>">
                        <?= htmlspecialchars($c['status']) ?>
                    </span>
                </div>
                <?php if (!empty($c['reply'])): ?>
                    <div class="reply"><strong>Admin Reply:</strong><br><?= nl2br(htmlspecialchars($c['reply'])) ?></div>
                <?php endif; ?>
                <div><small>Submitted on: <?= htmlspecialchars($c['created_at']) ?></small></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="welcome-message">
            You have no complaints submitted yet.<br>
            Feel free to send any complaints or feedback using the form above.
        </div>
    <?php endif; ?>

    <button class="back-btn" onclick="window.location.href='dashboard_student.php'">Back to Dashboard</button>
</div>

</body>
</html>
