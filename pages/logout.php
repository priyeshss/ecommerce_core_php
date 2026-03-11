<?php
session_start();
session_destroy(); // Destroys ALL session data including cart
header('Location: login.php');
exit();
?>