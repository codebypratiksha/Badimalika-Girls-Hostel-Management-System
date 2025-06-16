<?php
require 'config.php';
session_start();

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user';

    $permanent_address = $_POST['permanent_address'];
    $phone = $_POST['phone'];
    $guardian_name = $_POST['guardian'];
    $emergency_contact = $_POST['emergency_contact'];
    $occupation_status = $_POST['occupation_status'];
    $occupation_company_name = $_POST['company'] ?? null;
    $institution_name = $_POST['institution'] ?? null;

    $room_id = $_POST['room_id'];
    $allocation_start = $_POST['allocation_start'];
    $allocation_end = $_POST['allocation_end'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            $stmt2 = $conn->prepare("INSERT INTO students (user_id, permanent_address, phone, guardian_name, emergency_contact, occupation_status, occupation_company_name, institution_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("isssssss", $user_id, $permanent_address, $phone, $guardian_name, $emergency_contact, $occupation_status, $occupation_company_name, $institution_name);
            $stmt2->execute();

            $stmt3 = $conn->prepare("INSERT INTO allocations (student_id, room_id, allocation_start, allocation_end, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt3->bind_param("iiss", $user_id, $room_id, $allocation_start, $allocation_end);
            $stmt3->execute();

            // Mark room as unavailable
            $conn->query("UPDATE rooms SET availability = 'Unavailable' WHERE id = $room_id");

            $success = "Student registered and room allocated successfully!";
        } else {
            $errors[] = "Failed to create user.";
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Register Student</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background:linear-gradient(to right, #e2e2e2, #c9d6ff);
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            margin: 30px 0;
            color: #333;
        }

        form {
            max-width: 600px;
            margin: auto;
            padding: 30px;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: 500;
            color: #444;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 5px;
            background: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #45a049;
        }

        .success {
            color: green;
            text-align: center;
            font-weight: bold;
        }

        .error {
            color: red;
            text-align: center;
            font-weight: bold;
        }

        #job_fields, #student_fields {
            margin-top: 10px;
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
    <script>
        function toggleFields() {
            let status = document.getElementById("occupation_status").value;
            document.getElementById("job_fields").style.display = (status === 'job') ? 'block' : 'none';
            document.getElementById("student_fields").style.display = (status === 'student') ? 'block' : 'none';
        }
    </script>
</head>
<body>
<a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>
<h2>Admin: Register New Student</h2>

<?php
if ($success) echo "<p class='success'>$success</p>";
if (!empty($errors)) {
    foreach ($errors as $error) echo "<p class='error'>$error</p>";
}
?>

<form method="POST">
    <label>Full Name</label>
    <input type="text" name="name" required />

    <label>Email</label>
    <input type="email" name="email" required />

    <label>Password</label>
    <input type="password" name="password" required />

    <label>Permanent Address</label>
    <input type="text" name="permanent_address" required />

    <label>Phone Number</label>
    <input type="text" name="phone" required />

    <label>Guardian's Name</label>
    <input type="text" name="guardian" required />

    <label>Emergency Contact</label>
    <input type="text" name="emergency_contact" required />

    <label>Occupation Status</label>
    <select name="occupation_status" id="occupation_status" onchange="toggleFields()" required>
        <option value="">Select</option>
        <option value="job">Job</option>
        <option value="student">Student</option>
        <option value="unemployed">Unemployed</option>
    </select>

    <div id="job_fields" style="display:none;">
        <label>Company Name</label>
        <input type="text" name="company" />
    </div>

    <div id="student_fields" style="display:none;">
        <label>Institution Name</label>
        <input type="text" name="institution" />
    </div>

    <label>Allocate Room</label>
    <select name="room_id" required>
        <option value="">Select a room</option>
        <?php
        $result = $conn->query("SELECT id, room_number FROM rooms WHERE availability = 'Available'");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['id']}'>Room #{$row['room_number']}</option>";
        }
        ?>
    </select>

    <label>Allocation Start Date</label>
    <input type="date" name="allocation_start" required />

    <label>Allocation End Date</label>
    <input type="date" name="allocation_end" required />

    <button type="submit">Register Student</button>
</form>

</body>
</html>
