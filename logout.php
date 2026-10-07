<?php
session_start();
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session
header("Location: home/home.php"); // Redirect back to the home page
exit();
?>