<?php
session_start();
if(isset($_SESSION['register_message'])){
    echo "<div style='color:red; margin-bottom:10px'>" . $_SESSION['register_message'] . "</div>";
    unset($_SESSION['register_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CauseVia - Register</title>
</head>
<body>
    <h2>Register</h2>
    <form method="POST" action="../auth/register_process.php">
        <input type="text" name="full_name" placeholder="Full Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
