<?php
session_start();
require 'config.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' OR username='$username'");
    
    if (mysqli_num_rows($check) > 0) {
        $error = "Username or email already exists!";
    } else {
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if (mysqli_query($conn, $sql)) {
            $success = "Registration successful! Please login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - InkGuard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <h1>🛡️InkGuard</h1>
        <h2>Create Account</h2>

        <?php if($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>
</body>
</html>