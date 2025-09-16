<?php
session_start();
$_SESSION = array(); // Unset all session variables
session_destroy(); // Destroy the session

// Set a success message (optional, if you have a message system on login page)
// session_start(); // Need to start again to set a message for the next page
// $_SESSION['message'] = ['text' => 'You have been logged out successfully.', 'type' => 'success'];

header("location: /meditrack/index.php"); // Redirect to homepage or login page
exit;
?>