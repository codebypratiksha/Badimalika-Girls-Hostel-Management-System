<?php
session_start();
require 'config.php';

// Allow only admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

$message = "";

// Get room ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid room ID.");
}

$room_id = intval($_GET['id']);

// Fetch room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Room not found.");
}

$room = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if (isset($_POST['update_room'])) {
    $room_number = trim($_POST['room_number']);
    $capacity = intval($_POST['capacity']);
    $availability = trim($_POST['availability']);
    $notes = trim($_POST['notes']);

    if ($room_number === '' || $capacity <= 0 || $availability === '') {
        $message = "Please fill in all required fields.";
    } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $room_number)) {
        $message = "Room number can only contain letters, numbers, and dashes.";
    } else {
        $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, capacity = ?, availability = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sissi", $room_number, $capacity, $availability, $notes, $room_id);

        if ($stmt->execute()) {
            $message = "Room updated successfully.";
            // Refresh the data
            $room['room_number'] = $room_number;
            $room['capacity'] = $capacity;
            $room['availability'] = $availability;
            $room['notes'] = $notes;
        } else {
            $message = "Error updating room: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Room</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef3f8;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2a5dab;
        }
        .message {
            color: red;
            font-weight: bold;
            margin: 10px 0;
        }
        form label {
            margin-top: 10px;
            display: block;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            background-color: #2a5dab;
            color: white;
            font-weight: bold;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1f4686;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #2a5dab;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Room</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Room Number *</label>
        <input type="text" name="room_number" required value="<?= htmlspecialchars($room['room_number']) ?>" pattern="[a-zA-Z0-9\-]+" title="Only letters, numbers, and dashes allowed">

        <label>Capacity *</label>
        <input type="number" name="capacity" min="1" required value="<?= htmlspecialchars($room['capacity']) ?>">

        <label>Availability *</label>
        <select name="availability" required>
            <option value="">-- Select --</option>
            <option value="Available" <?= $room['availability'] === 'Available' ? 'selected' : '' ?>>Available</option>
            <option value="Occupied" <?= $room['availability'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"><?= htmlspecialchars($room['notes']) ?></textarea>

        <button type="submit" name="update_room">Update Room</button>
    </form>

    <a href="manage_rooms.php" class="back-link">← Back to Manage Rooms</a>
</div>
</body>
</html>
