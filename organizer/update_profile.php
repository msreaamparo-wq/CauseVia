<?php
// File: organizer/update_profile.php

require_once __DIR__ . '/../includes/db.php';

// 2. SESSION START
session_start();

// 3. CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    // Kung wala nay session, balik sa login (adjust path kung gikinahanglan, e.g., ../login.php)
    header("Location: ../login.php"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $batch_year = $_POST['batch_year'];
    $new_password = $_POST['password'];

    if (!empty($new_password)) {
        // With Password Update
        $sql = "UPDATE users 
                SET full_name = $1, username = $2, email = $3, batch_year = $4, password = $5 
                WHERE id = $6";
        $params = array($full_name, $username, $email, $batch_year, $new_password, $user_id);
    } else {
        // Without Password Update
        $sql = "UPDATE users 
                SET full_name = $1, username = $2, email = $3, batch_year = $4 
                WHERE id = $5";
        $params = array($full_name, $username, $email, $batch_year, $user_id);
    }

    $result = pg_query_params($dbconn, $sql, $params);

    // ... naa sa babaw ang imong saving code ...

if ($result) {
    // Kani nga logic: "Balik kung asa ka gikan"
    if (isset($_SERVER["HTTP_REFERER"])) {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    } else {
        // Fallback: Kung di siya kabalo asa gikan, adto sa dashboard (siguradoa sakto ang spelling)
        header("Location: orh_dashboard.php"); 
    }
    exit();
} else {
    echo "Error updating record: " . pg_last_error($dbconn);
}
}
?>