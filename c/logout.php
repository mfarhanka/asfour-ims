<?php
/* c/logout.php - Client Logout */
session_start();

// Clear all client session data
unset($_SESSION['client_id']);
unset($_SESSION['client_username']);
unset($_SESSION['client_name']);

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>