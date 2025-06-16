<?php
session_start();
require 'config.php';

// Login Logic
if (isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = trim($_POST['login_password']);

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: user_page.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['login_error'] = "User not found.";
    }

    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}

// Admin Registration Only
if (isset($_POST['register'])) {
    $name = trim($_POST['register_name']);
    $email = trim($_POST['register_email']);
    $password = password_hash(trim($_POST['register_password']), PASSWORD_DEFAULT);
    $role = 'admin'; // Fixed role

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Email already registered.";
        $_SESSION['active_form'] = 'register';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Create user base folders
            $baseDir = "users/user_$user_id";
            $folders = ["dashboard", "messages", "profile"];
            foreach ($folders as $folder) {
                $path = "$baseDir/$folder";
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                    file_put_contents("$path/index.php", "<?php // Silence is golden ?>");
                }
            }

            // Create admin folders
            $adminBase = "admins/admin_$user_id";
            $adminFolders = ["uploads", "notices", "complaints", "payments"];
            foreach ($adminFolders as $folder) {
                $path = "$adminBase/$folder";
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                    file_put_contents("$path/index.php", "<?php // Silence is golden ?>");
                }
            }

            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['register_error'] = "Registration failed. Try again.";
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: index.php");
    exit();
}
?>
