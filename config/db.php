<?php
if (session_status() == PHP_SESSION_NONE) {
    ob_start(); // Start output buffering to prevent headers already sent errors
    session_start(); // Start session if not already started
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', 'root');     // Your MySQL password (default for XAMPP)
define('DB_NAME', 'meditrack_db');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME,3307);

if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}


// Helper function for displaying messages
// Inside db.php
function set_message($msg, $type = 'success', $append = false) {
    if ($append && isset($_SESSION['message']['text'])) {
        $_SESSION['message']['text'] .= "<br>" . $msg; // Append new message
        // Optionally update type if the new message is of a higher severity, e.g. danger overrides info
        if ($type === 'danger' || ($type === 'warning' && $_SESSION['message']['type'] !== 'danger')) {
            $_SESSION['message']['type'] = $type;
        }
    } else {
        $_SESSION['message'] = ['text' => $msg, 'type' => $type];
    }
}

function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['text']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
}

// Helper for redirection
function redirect($url) {
    // If the URL doesn't start with http, assume it's relative to the project root
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        // This needs to be adjusted based on where db.php is relative to the desired base URL
        // For now, assuming URLs passed are like 'admin/index.php' or '../auth/login.php'
        // A more robust solution might involve a base URL constant.
        // For simplicity, we'll rely on paths like '/meditrack/auth/login.php' or relative paths.
    }
    header("Location: " . $url);
    exit();
}


// Helper to check if user is logged in and has a specific role
function check_login($role_to_check = null) {
    $base_url = "/meditrack"; // Define your project's base URL if it's in a subdirectory

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        redirect($base_url . '/auth/login.php');
    }
    if ($role_to_check && (!isset($_SESSION['role']) || $_SESSION['role'] !== $role_to_check)) {
        set_message('Access Denied: You do not have permission to view this page.', 'danger');
        if (isset($_SESSION['role'])) {
            redirect($base_url . '/' . $_SESSION['role'] . '/index.php');
        } else {
            redirect($base_url . '/auth/login.php');
        }
    }
}

?>
