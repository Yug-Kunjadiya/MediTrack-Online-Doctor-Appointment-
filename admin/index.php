<?php
// admin/index.php
require_once 'partials/admin_header.php'; // This includes db.php, session, nav, and starts <div class="dashboard-wrapper">

// --- PHP logic for dashboard data ---
$result_total_app = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments");
$total_appointments = $result_total_app ? mysqli_fetch_assoc($result_total_app)['total'] : 0;

$result_approved_app = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'approved'");
$approved_appointments = $result_approved_app ? mysqli_fetch_assoc($result_approved_app)['total'] : 0;

$result_cancelled_app = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'cancelled'");
$cancelled_appointments = $result_cancelled_app ? mysqli_fetch_assoc($result_cancelled_app)['total'] : 0;

$result_pending_app = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
$pending_appointments = $result_pending_app ? mysqli_fetch_assoc($result_pending_app)['total'] : 0;

$result_total_doc = mysqli_query($conn, "SELECT COUNT(*) as total FROM doctors");
$total_doctors = $result_total_doc ? mysqli_fetch_assoc($result_total_doc)['total'] : 0;

$result_total_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = $result_total_users ? mysqli_fetch_assoc($result_total_users)['total'] : 0;
// --- End PHP logic ---
?>

<div class="page-header">
    <h1 class="h2">Admin Dashboard</h1>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
    <div class="col">
        <div class="card admin-stat-card bg-primary h-100">
            <div class="card-body">
                <div class="stat-text">
                    <h5>Total Appointments</h5>
                    <div class="stat-number"><?php echo $total_appointments; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>/admin/view_appointments.php" class="card-footer text-white">
                View Details <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
     <div class="col">
        <div class="card admin-stat-card bg-success h-100">
            <div class="card-body">
                <div class="stat-text">
                    <h5>Approved</h5>
                    <div class="stat-number"><?php echo $approved_appointments; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
             <a href="<?php echo $base_url; ?>/admin/view_appointments.php?filter_status_appt=approved" class="card-footer text-white">
                View Details <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="card admin-stat-card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="stat-text">
                    <h5>Pending</h5>
                    <div class="stat-number"><?php echo $pending_appointments; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-plus"></i>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>/admin/view_appointments.php?filter_status_appt=pending" class="card-footer text-dark">
                View Details <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="card admin-stat-card bg-danger h-100">
            <div class="card-body">
                <div class="stat-text">
                    <h5>Cancelled</h5>
                    <div class="stat-number"><?php echo $cancelled_appointments; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>/admin/view_appointments.php?filter_status_appt=cancelled" class="card-footer text-white">
                View Details <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="card admin-stat-card bg-info h-100">
            <div class="card-body">
                 <div class="stat-text">
                    <h5>Total Doctors</h5>
                    <div class="stat-number"><?php echo $total_doctors; ?></div>
                 </div>
                <div class="stat-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>/admin/manage_doctors.php" class="card-footer text-white">
                Manage Doctors <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
    <div class="col">
        <div class="card admin-stat-card bg-secondary h-100"> 
            <div class="card-body">
                <div class="stat-text">
                    <h5>Total Patients</h5>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
            </div>
             <a href="<?php echo $base_url; ?>/admin/manage_users.php" class="card-footer text-white">
                Manage Patients <i class="bi bi-arrow-right-circle float-end"></i>
            </a>
        </div>
    </div>
</div>

<h3 class="system-management-title">System Management</h3>
<div class="list-group shadow-sm">
    <a href="<?php echo $base_url; ?>/admin/manage_doctors.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <div><i class="bi bi-heart-pulse-fill text-primary"></i>Manage Doctors</div>
        <span class="badge bg-primary rounded-pill"><?php echo $total_doctors; ?></span>
    </a>
    <a href="<?php echo $base_url; ?>/admin/manage_users.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <div><i class="bi bi-people-fill text-secondary"></i>Manage Patients</div>
        <span class="badge bg-secondary rounded-pill"><?php echo $total_users; ?></span>
    </a>
    <a href="<?php echo $base_url; ?>/admin/view_appointments.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
       <div><i class="bi bi-calendar-check-fill text-success"></i>View All Appointments</div>
       <span class="badge bg-success rounded-pill"><?php echo $total_appointments; ?></span>
    </a>
    <a href="<?php echo $base_url; ?>/admin/manage_invoices.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
       <div><i class="bi bi-receipt-cutoff text-info"></i>Manage Invoices</div>
       <?php 
            $result_total_invoices = mysqli_query($conn, "SELECT COUNT(*) as total FROM invoices");
            $total_invoices = $result_total_invoices ? mysqli_fetch_assoc($result_total_invoices)['total'] : 0;
       ?>
       <span class="badge bg-info rounded-pill"><?php echo $total_invoices; ?></span>
    </a>
</div>

<?php 
// REMOVED mysqli_close($conn); from here if it existed.
require_once 'partials/admin_footer.php'; 
?>