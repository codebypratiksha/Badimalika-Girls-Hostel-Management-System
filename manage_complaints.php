<?php
session_start();
require 'config.php';

// Only admin can access this page
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

$message = '';

// Handle reply submission from admin
if (isset($_POST['reply_submit'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $reply = trim($_POST['reply']);

    if (!empty($reply)) {
        $stmt = $conn->prepare("UPDATE complaints SET reply = ?, status = 'Resolved' WHERE id = ?");
        $stmt->bind_param("si", $reply, $complaint_id);

        if ($stmt->execute()) {
            $message = "Reply sent and complaint marked as resolved.";
        } else {
            $message = "Failed to send reply. Please try again.";
            // Optional: Log error: error_log($stmt->error);
        }
        $stmt->close();
    } else {
        $message = "Reply cannot be empty.";
    }
}

// Fetch complaints with student names
$sql = "SELECT c.id, u.name AS student_name, c.complaint, c.status, c.reply, c.created_at 
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Complaints - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f0f4fb; }
        h2 { color: #4d93f6; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; vertical-align: top; }
        th { background: #4d93f6; color: #fff; }
        textarea { width: 100%; height: 80px; padding: 8px; font-size: 14px; }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 6px 14px;
            border: none;
            border-radius: 5px;
            margin-top: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .reply-text {
            background: #e6ffe6;
            padding: 10px;
            border-left: 4px solid green;
            white-space: pre-wrap;
            margin-top: 6px;
        }
        .status-pending { color: orange; font-weight: bold; }
        .status-resolved { color: green; font-weight: bold; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4d93f6;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2>Manage Complaints</h2>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Student Name</th>
            <th>Complaint</th>
            <th>Status</th>
            <th>Admin Reply</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['complaint'])) ?><br><small><em>Submitted on: <?= htmlspecialchars($row['created_at']) ?></em></small></td>
                <td>
                    <span class="<?= $row['status'] === 'Pending' ? 'status-pending' : 'status-resolved' ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($row['reply'])): ?>
                        <div class="reply-text"><?= nl2br(htmlspecialchars($row['reply'])) ?></div>
                    <?php else: ?>
                        <em>No reply yet</em>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['status'] === 'Pending'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="complaint_id" value="<?= $row['id'] ?>">
                            <textarea name="reply" placeholder="Write your reply here..." required></textarea><br>
                            <button type="submit" name="reply_submit" class="submit-btn">Send Reply</button>
                        </form>
                    <?php else: ?>
                        <em>Replied</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">No complaints found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>

</body>
</html>
