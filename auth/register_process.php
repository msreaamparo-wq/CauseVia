<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['register_message'] = "Email already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $hashed_password])) {
            $_SESSION['register_message'] = "Registration successful! You can now <a href='../public/login.php'>login</a>.";
        } else {
            $_SESSION['register_message'] = "Registration failed. Please try again.";
        }
    }
}

// Redirect back to register page
header("Location: ../public/register.php");
exit;
?>
