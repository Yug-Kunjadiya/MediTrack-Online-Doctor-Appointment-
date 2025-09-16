<?php
require_once 'partials/admin_header.php'; // This includes db.php, session, nav, and starts <div class="dashboard-wrapper"> and <main class="dashboard-content">
// $base_url is already defined in admin_header.php

// Handle Delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $doc_id_to_delete = mysqli_real_escape_string($conn, $_GET['id']);
    // Before deleting doctor, consider deleting their profile image if it exists
    $sql_get_img = "SELECT profile_image FROM doctors WHERE id = '$doc_id_to_delete'";
    $img_res = mysqli_query($conn, $sql_get_img);
    if ($img_row = mysqli_fetch_assoc($img_res)) {
        if (!empty($img_row['profile_image']) && file_exists(__DIR__ . '/../uploads/doctors/' . $img_row['profile_image'])) {
            unlink(__DIR__ . '/../uploads/doctors/' . $img_row['profile_image']);
        }
    }

    $sql_delete = "DELETE FROM doctors WHERE id = '$doc_id_to_delete'";
    if (mysqli_query($conn, $sql_delete)) {
        set_message("Doctor (ID: $doc_id_to_delete) deleted successfully.", "success");
    } else {
        set_message("Error deleting doctor: " . mysqli_error($conn), "danger");
    }
    redirect($base_url . '/admin/manage_doctors.php'); // Redirect to clear GET params
}

// Fetch all doctors
$doctors_result = mysqli_query($conn, "SELECT id, name, specialization, email, phone_number FROM doctors ORDER BY name ASC");
?>

<!-- Content for manage_doctors.php directly inside <main class="dashboard-content"> -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h2 mb-0">Manage Doctors</h1>
        <a href="<?php echo $base_url; ?>/admin/edit_doctor.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Add New Doctor
        </a>
    </div>
</div>

<?php if ($doctors_result && mysqli_num_rows($doctors_result) > 0): ?>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">  
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doctor['id']); ?></td>
                        <td> <?php echo htmlspecialchars($doctor['name']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['phone_number'] ?? 'N/A'); ?></td>
                        <td class="text-center">
                            <a href="<?php echo $base_url; ?>/admin/edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-primary me-1" title="Edit Doctor">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="<?php echo $base_url; ?>/admin/manage_doctors.php?action=delete&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-danger" title="Delete Doctor" onclick="return confirm('Are you sure you want to delete  <?php echo htmlspecialchars($doctor['name']); ?>? This may also affect related appointments.');">
                                <i class="bi bi-trash-fill"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info mt-3">No doctors found. <a href="<?php echo $base_url; ?>/admin/edit_doctor.php" class="alert-link">Add one now!</a></div>
<?php endif; ?>
<!-- End of content for manage_doctors.php -->

<?php 
// No mysqli_close($conn) here, it's handled by admin_footer.php
require_once 'partials/admin_footer.php'; // This closes </main> and </div><!-- /dashboard-wrapper --> etc.
?>