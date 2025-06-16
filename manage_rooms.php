<?php
session_start();
require 'config.php';

// Allow only admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

$message = "";

// Validate room number
function validate_room_number($room_number) {
    return preg_match('/^[a-zA-Z0-9\-]+$/', $room_number);
}

// Add Room
if (isset($_POST['add_room'])) {
    $room_number = trim($_POST['room_number']);
    $capacity = intval($_POST['capacity']);
    $availability = trim($_POST['availability']);
    $notes = trim($_POST['notes']);

    if ($room_number === '' || $capacity <= 0 || $availability === '') {
        $message = "Please fill in all required fields.";
    } elseif (!validate_room_number($room_number)) {
        $message = "Room number can only contain letters, numbers, and dashes.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $stmt->bind_param("s", $room_number);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Room number already exists.";
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO rooms (room_number, capacity, availability, notes, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("siss", $room_number, $capacity, $availability, $notes);
            if ($stmt->execute()) {
                $message = "Room added successfully.";
            } else {
                $message = "Error adding room: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch Rooms
$rooms_result = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");

// Fetch Active Allocations
$allocations_map = [];
$alloc_res = $conn->query("
    SELECT a.room_id, u.name, a.student_id 
    FROM allocations a 
    JOIN users u ON a.student_id = u.id 
    WHERE a.status = 'Active'
    ORDER BY a.created_at DESC
");
while ($row = $alloc_res->fetch_assoc()) {
    if (!isset($allocations_map[$row['room_id']])) {
        $allocations_map[$row['room_id']] = [
            'name' => $row['name'],
            'student_id' => $row['student_id']
        ];
    }
}

// Fetch Unassigned Students (students with no active allocation)
$unassigned_students = [];
$unassigned_res = $conn->query("
    SELECT u.id, u.name 
    FROM users u
    WHERE u.role = 'student' 
      AND u.id NOT IN (
          SELECT student_id FROM allocations WHERE status = 'Active'
      )
    ORDER BY u.name ASC
");
while ($row = $unassigned_res->fetch_assoc()) {
    $unassigned_students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f8fb;
            padding: 40px;
        }
        .container {
            width: 90%;
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2, h3 {
            color: #2a5dab;
        }
        .message {
            color: red;
            font-weight: bold;
            margin: 15px 0;
        }
        form label {
            display: block;
            margin-top: 10px;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #2a5dab;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1f4686;
        }
        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #2a5dab;
            color: white;
        }
        a.action-btn {
            color: #2a5dab;
            text-decoration: none;
            font-weight: bold;
            margin: 0 5px;
        }
        a.action-btn:hover {
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2a5dab;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 99;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background: white;
            margin: 15% auto;
            padding: 20px;
            width: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.25);
            text-align: center;
        }
        .modal-content button {
            margin: 10px;
        }

        /* Unassigned students list */
        .unassigned-students {
            margin-top: 40px;
        }
        .unassigned-students ul {
            list-style-type: disc;
            padding-left: 20px;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fafafa;
        }
        .unassigned-students li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Manage Rooms</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Room Form -->
    <form method="post">
        <h3>Add New Room</h3>
        <label>Room Number *</label>
        <input type="text" name="room_number" required pattern="[a-zA-Z0-9\-]+" title="Only letters, numbers, and dashes allowed">

        <label>Capacity *</label>
        <input type="number" name="capacity" min="1" required>

        <label>Availability *</label>
        <select name="availability" required>
            <option value="">-- Select --</option>
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
            <option value="Maintenance">Maintenance</option>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"></textarea>

        <button type="submit" name="add_room">Add Room</button>
    </form>

    <!-- Room Table -->
    <h3>Room List</h3>
    <table>
        <thead>
        <tr>
            <th>Room No.</th>
            <th>Capacity</th>
            <th>Availability</th>
            <th>Assigned To</th>
            <th>Actions</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rooms_result->num_rows > 0): ?>
            <?php while ($room = $rooms_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                    <td><?= htmlspecialchars($room['capacity']) ?></td>
                    <td>
                        <?= isset($allocations_map[$room['id']]) ? 'Occupied' : htmlspecialchars($room['availability']) ?>
                    </td>
                    <td>
                        <?= isset($allocations_map[$room['id']]) ? htmlspecialchars($allocations_map[$room['id']]['name']) : 'Unassigned' ?>
                    </td>
                    <td>
                        <a class="action-btn" href="edit_room.php?id=<?= $room['id'] ?>">Edit</a>
                        |
                        <a class="action-btn delete-link" 
                           href="#" 
                           data-room-id="<?= $room['id'] ?>" 
                           data-student-id="<?= isset($allocations_map[$room['id']]) ? $allocations_map[$room['id']]['student_id'] : '' ?>">
                           Delete
                        </a>
                    </td>
                    <td><?= htmlspecialchars($room['notes']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No rooms available.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>


    <a class="back-link" href="dashboard_admin.php">&larr; Back to Dashboard</a>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <p id="modalText">Are you sure you want to delete this room?</p>
        <button id="confirmDeleteBtn">Yes, Delete</button>
        <button id="cancelDeleteBtn">Cancel</button>
    </div>
</div>

<script>
    const deleteLinks = document.querySelectorAll('.delete-link');
    const modal = document.getElementById('deleteModal');
    const modalText = document.getElementById('modalText');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const cancelBtn = document.getElementById('cancelDeleteBtn');

    let selectedRoomId = null;
    let selectedStudentId = null;

    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            selectedRoomId = this.getAttribute('data-room-id');
            selectedStudentId = this.getAttribute('data-student-id');

            if (selectedStudentId) {
                modalText.textContent = "This room is currently assigned to a student. Are you sure you want to delete it?";
            } else {
                modalText.textContent = "Are you sure you want to delete this room?";
            }

            modal.style.display = 'block';
        });
    });

    confirmBtn.addEventListener('click', () => {
        // Redirect to delete script
        window.location.href = `delete_room.php?id=${selectedRoomId}`;
    });

    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside modal content
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>
