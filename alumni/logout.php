<?php
session_start();
session_destroy();
header('Location: /APPLICATION/public/login.php');
exit();
?>
