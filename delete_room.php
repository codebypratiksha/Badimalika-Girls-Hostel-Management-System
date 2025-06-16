<?php
session_start();
require 'config.php';

// Only admin allowed
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

// Validate room ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid room ID.");
}

$room_id = intval($_GET['id']);

// Check if room has any allocations
$stmt = $conn->prepare("SELECT COUNT(*) AS total_allocations FROM allocations WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_allocations = $row['total_allocations'];
$stmt->close();

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($total_allocations > 0 && !$confirm) {
    // Show confirmation prompt if allocated
    echo "<script>
        if (confirm('This room is currently allocated. Are you sure you want to delete it?')) {
            window.location.href = 'delete_room.php?id=$room_id&confirm=yes';
        } else {
            window.location.href = 'manage_rooms.php';
        }
    </script>";
    exit();
}

// Proceed with deletion
$stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: manage_rooms.php?message=Room+deleted+successfully");
    exit();
} else {
    $stmt->close();
    die("Failed to delete room.");
}
?>
