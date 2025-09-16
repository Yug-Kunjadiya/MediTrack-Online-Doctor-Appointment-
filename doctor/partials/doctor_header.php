<?php
require_once __DIR__ . '/../../config/db.php';
check_login('doctor');
$base_url = "/meditrack";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Panel - MediTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
     <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .doctor-wrapper { display: flex; flex-grow: 1; padding-top: 56px; /* For fixed navbar */ }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top"> <!-- Doctor panel different color -->
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_url; ?>/doctor/index.php">MediTrack Doctor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNavbar" aria-controls="doctorNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="doctorNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3 text-light">
                             <?php echo htmlspecialchars($_SESSION['name']); ?> (<?php echo htmlspecialchars($_SESSION['specialization']); ?>)
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white" href="<?php echo $base_url; ?>/auth/logout.php">Logout <i class="bi bi-box-arrow-right"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="doctor-wrapper">
        <?php include_once __DIR__ . '/doctor_sidebar.php'; ?>
        <main class="doctor-content">
        <?php display_message(); ?>