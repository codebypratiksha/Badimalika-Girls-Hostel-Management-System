<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: dashboard_admin.php");
            } elseif ($user['role'] === 'student') {
                header("Location: dashboard_student.php");
            } else {
                echo "Unknown role.";
            }
            exit();
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "No user found.";
    }
}
?>
