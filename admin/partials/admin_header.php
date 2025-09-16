<?php
// admin/partials/admin_header.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($base_url)) $base_url = "/meditrack"; // Define if not set by including page

// db.php should be included for $conn and functions
if (!isset($conn) && file_exists(__DIR__ . '/../../config/db.php')) {
    require_once __DIR__ . '/../../config/db.php';
}
check_login('admin'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MediTrack</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm admin-navbar">  
        <div class="container-fluid"> 
            <a class="navbar-brand" href="<?php echo $base_url; ?>/admin/index.php">
                <i class="bi bi-shield-lock-fill me-2"></i>MediTrack Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarOffcanvas" aria-controls="adminSidebarOffcanvas" aria-label="Toggle navigation">
                 <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminTopNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="navbar-text me-3 text-light"> 
                            Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="<?php echo $base_url; ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper"> 
        <?php include_once __DIR__ . '/admin_sidebar.php'; // The admin-specific sidebar ?>
        <main class="dashboard-content"> 
        <?php 
            if (function_exists('display_message')) {
                display_message(); 
            }
        ?>
