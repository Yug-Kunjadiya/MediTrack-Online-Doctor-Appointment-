<?php
// user/index.php
require_once 'partials/user_header.php'; 
$base_url = "/meditrack"; 
$user_id = $_SESSION['id'];

// --- PHP logic for dashboard data (existing) ---
$upcoming_sql = "SELECT a.*, d.name as doctor_name, d.specialization 
                 FROM appointments a 
                 JOIN doctors d ON a.doctor_id = d.id 
                 WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('pending', 'approved')
                 ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 3";
$stmt_upcoming = mysqli_prepare($conn, $upcoming_sql);
mysqli_stmt_bind_param($stmt_upcoming, "i", $user_id);
mysqli_stmt_execute($stmt_upcoming);
$upcoming_appointments_result = mysqli_stmt_get_result($stmt_upcoming);

$total_user_appointments_sql = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ?";
$stmt_total_appts = mysqli_prepare($conn, $total_user_appointments_sql);
mysqli_stmt_bind_param($stmt_total_appts, "i", $user_id);
mysqli_stmt_execute($stmt_total_appts);
$total_user_appointments = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total_appts))['total'];
mysqli_stmt_close($stmt_total_appts);
// --- End PHP logic ---
?>

<div class="page-header">
    <h1 class="h2">Patient Dashboard</h1>
</div>

<div class="alert alert-info shadow-soft" role="alert">
  <h4 class="alert-heading">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
  <p>Manage your appointments, find doctors, and take control of your health journey. We're here to help you connect with the care you need.</p>
  <hr>
  <p class="mb-0">Need to book a new appointment? <a href="<?php echo $base_url; ?>/user/view_doctors.php" class="alert-link">Find a Doctor now</a>.</p>
</div>


<div class="row g-4">
    <div class="col-md-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Upcoming Appointments</h4>
            </div>
            <div class="card-body">
                <?php if ($upcoming_appointments_result && mysqli_num_rows($upcoming_appointments_result) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($app = mysqli_fetch_assoc($upcoming_appointments_result)): ?>
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"> <?php echo htmlspecialchars($app['doctor_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($app['specialization']); ?>)</small></h6>
                                    <small class="text-muted"><?php echo date("D, M j", strtotime($app['appointment_date'])); ?></small>
                                </div>
                                <p class="mb-1">Time: <?php echo date("g:i A", strtotime($app['appointment_time'])); ?></p>
                                Status: <span class="badge bg-<?php echo $app['status'] == 'approved' ? 'success' : 'warning text-dark'; ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php 
                        $count_all_upcoming_sql = "SELECT COUNT(*) as total_upcoming FROM appointments WHERE user_id = ? AND appointment_date >= CURDATE() AND status IN ('pending', 'approved')";
                        $stmt_count_all = mysqli_prepare($conn, $count_all_upcoming_sql);
                        mysqli_stmt_bind_param($stmt_count_all, "i", $user_id);
                        mysqli_stmt_execute($stmt_count_all);
                        $total_upcoming_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count_all))['total_upcoming'];
                        mysqli_stmt_close($stmt_count_all);

                        if($total_upcoming_count > mysqli_num_rows($upcoming_appointments_result)): 
                    ?>
                        <a href="<?php echo $base_url; ?>/user/my_appointments.php" class="btn btn-sm btn-outline-primary mt-3 d-block">View All My Appointments</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-calendar-x-fill fs-1 text-muted mb-2"></i>
                        <p class="text-muted mb-0">You have no upcoming appointments.</p>
                        <a href="<?php echo $base_url; ?>/user/view_doctors.php" class="btn btn-success mt-3"><i class="bi bi-search-heart"></i> Book Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="bi bi-lightning-charge-fill me-2"></i>Quick Actions</h4>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <a href="<?php echo $base_url; ?>/user/view_doctors.php" class="btn btn-primary btn-lg mb-3 w-100 py-3 fs-5"><i class="bi bi-search-heart"></i> Find & Book Doctor</a>
                <a href="<?php echo $base_url; ?>/user/my_appointments.php" class="btn btn-info btn-lg w-100 py-3 fs-5 text-dark"><i class="bi bi-calendar-check"></i> My Appointments</a>
            </div>
        </div>
    </div>
</div>

<?php 
if($stmt_upcoming) mysqli_stmt_close($stmt_upcoming); 
?>
<?php require_once 'partials/user_footer.php'; ?>