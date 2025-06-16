<?php
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login / Register</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>

<div class="container">
    <!-- Login Form -->
    <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
        <form action="login_register.php" method="post" autocomplete="off">
            <h2>Login</h2>
            <?= showError($errors['login']); ?>

            <input type="text" style="display:none" aria-hidden="true" />
            <input type="password" style="display:none" aria-hidden="true" />

            <input type="email" name="login_email" placeholder="Email" required />
            <input type="password" name="login_password" placeholder="Password" required />
            <button type="submit" name="login">Login</button>
            <p>Don't have an account? <a href="#" onclick="showForm('register-form'); return false;">Register</a></p>
        </form>
    </div>

    <!-- Register Form (Admin Only) -->
    <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
        <form action="login_register.php" method="post" autocomplete="off">
            <h2>Admin Register</h2>
            <?= showError($errors['register']); ?>

            <input type="text" style="display:none" aria-hidden="true" />
            <input type="password" style="display:none" aria-hidden="true" />

            <input type="text" name="register_name" placeholder="Name" required />
            <input type="email" name="register_email" placeholder="Email" required />
            <input type="password" name="register_password" placeholder="Password" required />
            <input type="hidden" name="role" value="admin" />
            <button type="submit" name="register">Register</button>
            <p>Already have an account? <a href="#" onclick="showForm('login-form'); return false;">Login</a></p>
        </form>
    </div>
</div>

<script>
function showForm(formId) {
    document.querySelectorAll('.form-box').forEach(f => f.classList.remove('active'));
    document.getElementById(formId).classList.add('active');
}
</script>

</body>
</html>
