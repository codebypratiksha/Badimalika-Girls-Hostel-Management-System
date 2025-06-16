<?php
session_start();
require 'config.php';

// Check if logged in user is student
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: login_form.php");
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

// Fetch all notices posted after user registration date
$query = "SELECT title, content, posted_on FROM notices WHERE posted_on >= ? ORDER BY posted_on DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $registration_date);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error fetching notices: " . $conn->error);
}

$notices = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Hostel Notices</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f7f9ff;
        padding: 20px;
        max-width: 700px;
        margin: auto;
    }
    h1 {
        color: #4d93f6;
        text-align: center;
    }
    .notice {
        background: #fff;
        padding: 15px 20px;
        margin: 15px 0;
        border-radius: 8px;
        box-shadow: 0 0 10px #ccc;
    }
    .notice h2 {
        margin-top: 0;
        color: #333;
    }
    .notice p {
        white-space: pre-wrap;
        color: #555;
    }
    .posted_on {
        font-size: 0.9em;
        color: #999;
        margin-top: 10px;
        text-align: right;
    }
    .back-btn {
        display: inline-block;
        background: #6c757d;
        color: #fff;
        padding: 10px 20px;
        margin-top: 20px;
        border-radius: 6px;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .back-btn:hover {
        background: #5a6268;
    }
</style>
</head>
<body>

<h1>Hostel Notices</h1>

<?php if (count($notices) > 0): ?>
    <?php foreach ($notices as $notice): ?>
        <div class="notice">
            <h2><?= htmlspecialchars($notice['title']) ?></h2>
            <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
            <div class="posted_on">Posted on: <?= date('F j, Y, g:i a', strtotime($notice['posted_on'])) ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No notices available at this time.</p>
<?php endif; ?>

<a href="dashboard_student.php" class="back-btn">Back to Dashboard</a>

</body>
</html>
