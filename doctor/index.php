<?php
require_once 'partials/doctor_header.php'; 
$base_url = "/meditrack";
$doctor_id = $_SESSION['id'];

if (!defined('PROFILE_UPLOAD_DIR_DASH')) define('PROFILE_UPLOAD_DIR_DASH', __DIR__ . '/../uploads/doctors/');
if (!defined('PROFILE_MAX_FILE_SIZE_DASH')) define('PROFILE_MAX_FILE_SIZE_DASH', 2 * 1024 * 1024);
if (!defined('PROFILE_ALLOWED_TYPES_DASH')) define('PROFILE_ALLOWED_TYPES_DASH', ['image/jpeg', 'image/png', 'image/gif']);

// Fetch current doctor data including phone_number AND profile_image
// THIS IS THE QUERY CAUSING THE ERROR in screenshot 4 IF phone_number column is missing from SELECT.
// Ensure phone_number is indeed in your doctors table structure.
$sql_get_current_doctor = "SELECT name, specialization, email, profile_image, phone_number FROM doctors WHERE id = ?";
$stmt_get_doc_info = mysqli_prepare($conn, $sql_get_current_doctor);
if (!$stmt_get_doc_info) {
    // This would indicate a syntax error in the SQL before even checking columns
    die("Error preparing doctor info query: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt_get_doc_info, "i", $doctor_id);
mysqli_stmt_execute($stmt_get_doc_info);
$res_doc_info = mysqli_stmt_get_result($stmt_get_doc_info);
$doc_data_current = mysqli_fetch_assoc($res_doc_info);
mysqli_stmt_close($stmt_get_doc_info);

if (!$doc_data_current) {
    // Handle case where doctor data might not be found, though unlikely if logged in
    set_message("Could not retrieve your profile information.", "danger");
    $doc_data_current = ['profile_image' => null, 'phone_number' => '', 'name' => $_SESSION['name'], 'specialization' => $_SESSION['specialization'], 'email' => $_SESSION['email']];
}

// Update session variables if they are not set or differ from DB
$_SESSION['name'] = $doc_data_current['name']; // Make sure session reflects DB
$_SESSION['specialization'] = $doc_data_current['specialization'];
$_SESSION['email'] = $doc_data_current['email'];
$_SESSION['profile_image'] = $doc_data_current['profile_image'] ?? null;
$_SESSION['phone_number'] = $doc_data_current['phone_number'] ?? '';

$current_doctor_profile_image_db = $_SESSION['profile_image'];
$current_doctor_phone_number_db = $_SESSION['phone_number'];


// Image and Phone Update logic (from previous full file is correct)
// ... (Full image and phone update PHP logic from previous valid doctor/index.php) ...
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile_image'])) {
    if (isset($_FILES['new_profile_image']) && $_FILES['new_profile_image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['new_profile_image']['tmp_name'];
        $file_name_original = basename($_FILES['new_profile_image']['name']);
        $file_size = $_FILES['new_profile_image']['size'];
        $file_type = mime_content_type($file_tmp_path);
        $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
        if (!in_array($file_type, PROFILE_ALLOWED_TYPES_DASH)) { set_message("Invalid file type.", "danger"); } 
        elseif ($file_size > PROFILE_MAX_FILE_SIZE_DASH) { set_message("File size exceeds 2MB.", "danger"); } 
        else {
            if (!is_dir(PROFILE_UPLOAD_DIR_DASH)) mkdir(PROFILE_UPLOAD_DIR_DASH, 0775, true);
            if (!is_writable(PROFILE_UPLOAD_DIR_DASH)) { set_message("Upload dir not writable.", "danger"); } 
            else {
                $new_image_filename = 'doc_' . uniqid('', true) . '.' . $file_ext;
                $dest_path = PROFILE_UPLOAD_DIR_DASH . $new_image_filename;
                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    $old_image = $current_doctor_profile_image_db;
                    if ($old_image && file_exists(PROFILE_UPLOAD_DIR_DASH . $old_image)) { unlink(PROFILE_UPLOAD_DIR_DASH . $old_image); }
                    $sql_update_img = "UPDATE doctors SET profile_image = ? WHERE id = ?";
                    if ($stmt_update_img = mysqli_prepare($conn, $sql_update_img)) {
                        mysqli_stmt_bind_param($stmt_update_img, "si", $new_image_filename, $doctor_id);
                        mysqli_stmt_execute($stmt_update_img);
                        $_SESSION['profile_image'] = $new_image_filename; 
                        $current_doctor_profile_image_db = $new_image_filename; // Update page var
                        set_message("Profile image updated!", "success");
                        mysqli_stmt_close($stmt_update_img);
                    } else { unlink($dest_path); set_message("DB error updating image.", "danger");}
                } else { set_message("Failed to move image.", "danger");}
            }
        }
    } else { set_message("No image or upload error.", "info"); }
    redirect($base_url . '/doctor/index.php');
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_phone_number'])) {
    $new_phone_number = mysqli_real_escape_string($conn, trim($_POST['new_phone_number']));
    if (empty($new_phone_number) || !preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $new_phone_number)) {
        set_message("Invalid phone number.", "danger");
    } else {
        $sql_update_phone = "UPDATE doctors SET phone_number = ? WHERE id = ?";
        if ($stmt_update_phone = mysqli_prepare($conn, $sql_update_phone)) {
            mysqli_stmt_bind_param($stmt_update_phone, "si", $new_phone_number, $doctor_id);
            if (mysqli_stmt_execute($stmt_update_phone)) {
                $_SESSION['phone_number'] = $new_phone_number; 
                $current_doctor_phone_number_db = $new_phone_number; // Update page var
                set_message("Phone number updated!", "success");
            } else { set_message("DB error updating phone.", "danger"); }
            mysqli_stmt_close($stmt_update_phone);
        } else { set_message("DB prep error for phone.", "danger");}
    }
    redirect($base_url . '/doctor/index.php'); 
}


// Fetch pending appointments (existing logic)
$pending_sql = "SELECT a.id, a.appointment_date, a.appointment_time, u.name as patient_name, u.email as patient_email 
                FROM appointments a JOIN users u ON a.user_id = u.id 
                WHERE a.doctor_id = ? AND a.status = 'pending' ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 5";
$stmt_pending = mysqli_prepare($conn, $pending_sql);
mysqli_stmt_bind_param($stmt_pending, "i", $doctor_id);
mysqli_stmt_execute($stmt_pending);
$pending_appointments_result = mysqli_stmt_get_result($stmt_pending);

// Fetch today's appointments (existing logic)
$today_sql = "SELECT a.id, a.appointment_date, a.appointment_time, u.name as patient_name 
              FROM appointments a JOIN users u ON a.user_id = u.id 
              WHERE a.doctor_id = ? AND a.status = 'approved' AND a.appointment_date = CURDATE() ORDER BY a.appointment_time ASC";
$stmt_today = mysqli_prepare($conn, $today_sql);
mysqli_stmt_bind_param($stmt_today, "i", $doctor_id);
mysqli_stmt_execute($stmt_today);
$today_appointments_result = mysqli_stmt_get_result($stmt_today);
?>

<div class="page-header">  
    <h1 class="h2">Doctor Dashboard</h1>
</div>

<div class="row g-4">
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>My Profile</h5>
            </div>
            <div class="card-body text-center d-flex flex-column align-items-center">
                <?php
                $image_path_display = $base_url . '/assets/img/default_avatar.svg'; 
                $actual_image_filename = $current_doctor_profile_image_db;
                if (!empty($actual_image_filename) && file_exists(PROFILE_UPLOAD_DIR_DASH . $actual_image_filename)) {
                    $image_path_display = $base_url . '/uploads/doctors/' . htmlspecialchars($actual_image_filename);
                }
                ?>
                <img src="<?php echo $image_path_display; ?>?t=<?php echo time(); ?>" alt=" <?php echo htmlspecialchars($_SESSION['name']); ?>" class="img-fluid rounded-circle mb-3" style="width: 160px; height: 160px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.2);">
                <h4 class="mt-2"> <?php echo htmlspecialchars($_SESSION['name']); ?></h4>
                <p class="text-muted mb-1"><?php echo htmlspecialchars($_SESSION['specialization']); ?></p>
                <p class="text-muted small mb-1"><i class="bi bi-envelope-fill me-1"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p class="text-muted small mb-3">
                    <i class="bi bi-telephone-fill me-1"></i> 
                    <?php echo !empty($current_doctor_phone_number_db) ? htmlspecialchars($current_doctor_phone_number_db) : 'Not set'; ?>
                </p>
                
                <div class="mt-auto w-100">
                    <button type="button" class="btn btn-primary btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#updateProfileImageModal">
                        <i class="bi bi-camera-fill"></i> Update Profile Image
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#updatePhoneNumberModal">
                        <i class="bi bi-pencil-fill"></i> Update Phone Number
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="alert alert-success shadow-soft" role="alert">
          <h4 class="alert-heading">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
          <p>This is your dedicated dashboard to manage patient appointments, update your availability, and oversee your schedule efficiently.</p>
        </div>
       
         <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-bell-fill me-2"></i>Pending Requests</h5>
                    </div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <?php if ($pending_appointments_result && mysqli_num_rows($pending_appointments_result) > 0): ?>
                            <ul class="list-group list-group-flush">
                            <?php while ($app = mysqli_fetch_assoc($pending_appointments_result)): ?>
                                <li class="list-group-item small">
                                    <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong><br>
                                    <?php echo date("D, M j", strtotime($app['appointment_date'])); ?> @ <?php echo date("g:i A", strtotime($app['appointment_time'])); ?><br>
                                    <a href="<?php echo $base_url; ?>/doctor/view_appointments.php?action=approve&id=<?php echo $app['id']; ?>" class="badge bg-success text-decoration-none me-1">Approve</a>
                                    <a href="<?php echo $base_url; ?>/doctor/view_appointments.php?action=cancel&id=<?php echo $app['id']; ?>" class="badge bg-danger text-decoration-none">Cancel</a>
                                </li>
                            <?php endwhile; ?>
                            </ul>
                            <?php 
                            $stmt_pending_count = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = ? AND status = 'pending'");
                            mysqli_stmt_bind_param($stmt_pending_count, 'i', $doctor_id);
                            mysqli_stmt_execute($stmt_pending_count);
                            $pending_count_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pending_count));
                            mysqli_stmt_close($stmt_pending_count);
                            if((int)($pending_count_row['cnt'] ?? 0) > 5): ?>
                                <a href="<?php echo $base_url; ?>/doctor/view_appointments.php?filter_status=pending" class="btn btn-sm btn-outline-secondary mt-2 d-block">View All Pending</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No pending requests.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-day me-2"></i>Today's Schedule</h5>
                    </div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <?php if ($today_appointments_result && mysqli_num_rows($today_appointments_result) > 0): ?>
                             <ul class="list-group list-group-flush">
                            <?php while ($app = mysqli_fetch_assoc($today_appointments_result)): ?>
                                <li class="list-group-item small">
                                    <strong><?php echo htmlspecialchars($app['patient_name']); ?></strong> at <?php echo date("g:i A", strtotime($app['appointment_time'])); ?>
                                </li>
                            <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No appointments for today.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="mb-3">Quick Management Links</h4>
            <div class="d-grid gap-2 d-md-flex">
                <a href="<?php echo $base_url; ?>/doctor/manage_availability.php" class="btn btn-primary btn-lg flex-fill"><i class="bi bi-clock-history me-2"></i>Manage Availability</a>
                <a href="<?php echo $base_url; ?>/doctor/view_appointments.php" class="btn btn-secondary btn-lg flex-fill"><i class="bi bi-calendar-week me-2"></i>View All Appointments</a>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="updateProfileImageModal" tabindex="-1"> <div class="modal-dialog modal-dialog-centered"> <div class="modal-content"> <form method="POST" action="<?php echo $base_url; ?>/doctor/index.php" enctype="multipart/form-data"> <div class="modal-header"><h5 class="modal-title">Update Profile Image</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div> <div class="modal-body"><div class="mb-3"><label for="new_profile_image_input" class="form-label">Choose image:</label><input type="file" class="form-control" id="new_profile_image_input" name="new_profile_image" accept="image/*" required></div><div class="text-center"><img id="imagePreview" src="<?php echo $image_path_display; ?>?t=<?php echo time();?>" alt="Preview" class="img-thumbnail" style="max-width:200px; max-height:200px;"></div></div> <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="update_profile_image" class="btn btn-primary">Upload</button></div></form></div></div></div>
<div class="modal fade" id="updatePhoneNumberModal" tabindex="-1"> <div class="modal-dialog modal-dialog-centered"> <div class="modal-content"> <form method="POST" action="<?php echo $base_url; ?>/doctor/index.php"> <div class="modal-header"><h5 class="modal-title">Update Phone Number</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div> <div class="modal-body"><div class="mb-3"><label for="new_phone_number_input" class="form-label">New Phone:</label><input type="tel" class="form-control" id="new_phone_number_input" name="new_phone_number" value="<?php echo htmlspecialchars($current_doctor_phone_number_db); ?>" required></div></div> <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="update_phone_number" class="btn btn-primary">Save Phone</button></div></form></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newProfileImageInput = document.getElementById('new_profile_image_input');
    const imagePreview = document.getElementById('imagePreview');
    const initialImagePathForJS = document.querySelector('.card-body.text-center img.rounded-circle').src;

    if (newProfileImageInput && imagePreview) {
        newProfileImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = function() { imagePreview.src = reader.result; }
                reader.readAsDataURL(file);
            } else { imagePreview.src = initialImagePathForJS; }
        });
    }
    var imageModalElement = document.getElementById('updateProfileImageModal');
    if(imageModalElement) {
        imageModalElement.addEventListener('hidden.bs.modal', function () {
            if(newProfileImageInput) newProfileImageInput.value = null; 
            if(imagePreview) imagePreview.src = initialImagePathForJS; 
        });
    }
});
</script>

<?php 
if($stmt_pending) mysqli_stmt_close($stmt_pending); 
if($stmt_today) mysqli_stmt_close($stmt_today); 
?>
<?php require_once 'partials/doctor_footer.php'; ?>