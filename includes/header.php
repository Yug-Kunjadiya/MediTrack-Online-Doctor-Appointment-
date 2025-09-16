<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_url)) $base_url = "/meditrack"; 

if (!isset($conn) && file_exists(__DIR__ . '/../config/db.php')) { 
    require_once __DIR__ . '/../config/db.php';
}

// Determine if this is a landing/auth page vs a panel page
$is_landing_or_auth_page = false;
$current_script = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php', 'doctor_register.php', 'admin_login.php'];
if ($current_script == 'index.php' || in_array($current_script, $auth_pages)) {
    $is_landing_or_auth_page = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Online Doctor Appointment</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="<?php if ($is_landing_or_auth_page && $current_script == 'index.php') echo 'landing-page'; ?>">
<nav class="navbar navbar-expand-lg fixed-top shadow-sm bg-white main-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>/index.php">
            <i class="bi bi-heart-pulse-fill me-2"></i>MediTrack
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavMain" aria-controls="navbarNavMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavMain">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($_SESSION['role']); ?>/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/auth/login.php">User/Doctor Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/auth/register.php">Patient Register</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>/auth/doctor_register.php">Doctor Register</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0"> 
                        <a class="btn btn-outline-primary btn-sm" href="<?php echo $base_url; ?>/auth/admin_login.php">Admin Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php if ($is_landing_or_auth_page): ?>
<div class="container main-content-area"> 
    <?php display_message_if_available(); ?>
<?php else: ?>
    <div class="dashboard-wrapper"> 
    <?php /* display_message(); will be called in panel_header.php */ ?>
<?php endif; ?>
<?php
// Helper function to avoid repeating the display_message logic
function display_message_if_available() {
    if (function_exists('display_message')) {
        display_message(); 
    } elseif (isset($_SESSION['message'])) { 
        $message = $_SESSION['message'];
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['text']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['message']);
    }
}
?>