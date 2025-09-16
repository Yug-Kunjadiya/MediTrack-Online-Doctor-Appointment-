<?php $base_url = "/meditrack"; ?>
<aside class="doctor-sidebar bg-dark text-light">
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/doctor/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/doctor/view_appointments.php"><i class="bi bi-calendar-week"></i> My Appointments</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/doctor/manage_availability.php"><i class="bi bi-clock-history"></i> Manage Availability</a>
        </li>
        <hr class="text-secondary">
        <li class="nav-item mt-auto">
            <a class="nav-link" href="<?php echo $base_url; ?>/index.php" target="_blank"><i class="bi bi-house"></i> Visit Main Site</a>
        </li>
    </ul>
</aside>