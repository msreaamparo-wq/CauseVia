<?php
session_start();
session_unset();
session_destroy();

// Usba ning path kung lahi imong folder name
header('Location: ../landingpage.php');
exit();

?>