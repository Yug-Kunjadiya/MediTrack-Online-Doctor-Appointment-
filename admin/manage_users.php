<?php
require_once 'partials/admin_header.php'; // This includes db.php, session, nav, and starts dashboard layout
// $base_url is defined in admin_header.php

// Handle Delete User action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Before deleting user, consider related data. 
    // Appointments have ON DELETE CASCADE for user_id, so they should be handled.
    // Reviews also have ON DELETE CASCADE for user_id.
    // Messages would need manual cleanup or ON DELETE SET NULL/CASCADE if foreign keys were strict.
    // Invoices also have ON DELETE CASCADE.

    $sql_delete = "DELETE FROM users WHERE id = ? AND role='user'"; // Ensure only patients are deleted here
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $user_id_to_delete);

    if (mysqli_stmt_execute($stmt_delete)) {
        if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
            set_message("Patient (ID: $user_id_to_delete) deleted successfully.", "success");
        } else {
            set_message("Patient not found or already deleted.", "warning");
        }
    } else {
        set_message("Error deleting patient: " . mysqli_stmt_error($stmt_delete), "danger");
    }
    mysqli_stmt_close($stmt_delete);
    redirect($base_url . '/admin/manage_users.php'); // Redirect to clear GET params
}

// Fetch all users (patients)
$users_result = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role='user' ORDER BY name ASC");
?>

<!-- Content for manage_users.php directly inside <main class="dashboard-content"> -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h2 mb-0">Manage Patients</h1>
        <a href="<?php echo $base_url; ?>/admin/edit_user.php" class="btn btn-success">
            <i class="bi bi-person-plus-fill me-2"></i>Add New Patient
        </a>
    </div>
</div>

<?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="text-center">
                            <a href="<?php echo $base_url; ?>/admin/edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary me-1" title="Edit Patient">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="<?php echo $base_url; ?>/admin/manage_users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Delete Patient" onclick="return confirm('Are you sure you want to delete patient <?php echo htmlspecialchars(addslashes($user['name'])); ?>? This will also delete their appointments and reviews.');">
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
<?php elseif ($users_result === false): ?>
    <div class="alert alert-danger mt-3">Error fetching patient data: <?php echo mysqli_error($conn); ?></div>
<?php else: ?>
<div class="alert alert-info mt-3">No patients found. <a href="<?php echo $base_url; ?>/admin/edit_user.php" class="alert-link">Add one now!</a></div>
<?php endif; ?>
<!-- End of content for manage_users.php -->

<?php 
// CRITICAL: Ensure there is NO mysqli_close($conn); call before this line in this file.
// The connection will be handled by admin_footer.php

if($users_result) mysqli_free_result($users_result); // Free result set if it was created

require_once 'partials/admin_footer.php'; // This closes </main>, </div><!-- /dashboard-wrapper -->, and conditionally closes $conn
?>