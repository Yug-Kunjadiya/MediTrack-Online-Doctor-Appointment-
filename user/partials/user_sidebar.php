<?php $base_url = "/meditrack"; ?>
<aside class="user-sidebar dashboard-sidebar bg-dark text-light"> <!-- Consistent dark sidebar -->
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/user/index.php"><i class="bi bi-layout-text-sidebar-reverse"></i> Dashboard</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/user/view_doctors.php"><i class="bi bi-search-heart"></i> Find a Doctor</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="<?php echo $base_url; ?>/user/my_appointments.php"><i class="bi bi-calendar-check"></i> My Appointments</a>
        </li>
        <hr class="text-secondary">
        <li class="nav-item mt-auto">
            <a class="nav-link" href="<?php echo $base_url; ?>/index.php" target="_blank"><i class="bi bi-house"></i> Visit Main Site</a>
        </li>
    </ul>
</aside>