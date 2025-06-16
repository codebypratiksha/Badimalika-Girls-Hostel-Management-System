<?php
session_start();
require 'config.php';

// Only admin access
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

// Validate user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);

// Confirm deletion with popup
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<script>
        if (confirm('Are you sure you want to delete this user and all related records?')) {
            window.location.href = 'delete_user.php?id=$user_id&confirm=yes';
        } else {
            window.location.href = 'manage_students.php';
        }
    </script>";
    exit();
}

// Delete from allocations (if any)
$stmt = $conn->prepare("DELETE FROM allocations WHERE student_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Delete from students table
$stmt = $conn->prepare("DELETE FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Delete from users table
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: manage_students.php?message=User+deleted+successfully");
    exit();
} else {
    $stmt->close();
    die("Error deleting user.");
}
?>
