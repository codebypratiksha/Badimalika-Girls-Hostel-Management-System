<?php
session_start();
require 'config.php';

// Allow only admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $posted_on = date('Y-m-d H:i:s');

    if ($title && $content) {
        $stmt = $conn->prepare("INSERT INTO notices (title, content, posted_on) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $posted_on);
        if ($stmt->execute()) {
            $message = "Notice posted successfully.";
        } else {
            $message = "Error posting notice.";
        }
        $stmt->close();
    } else {
        $message = "Please fill in both title and content.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Notice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9ff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            color: #4d93f6;
            margin-top: 40px;
            text-align: center;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }
        button {
            margin-top: 20px;
            background-color: #4d93f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #377de5;
        }
        .message {
            font-weight: bold;
            color: green;
            margin-top: 15px;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4d93f6;
            text-decoration: none;
            text-align: center;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2>Post a New Notice</h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post">
    <label for="title">Notice Title:</label>
    <input type="text" id="title" name="title" required>

    <label for="content">Notice Content:</label>
    <textarea id="content" name="content" rows="6" required></textarea>

    <button type="submit">Post Notice</button>
</form>

<a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>

</body>
</html>
