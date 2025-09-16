<?php
require_once 'partials/admin_header.php';
$base_url = "/meditrack";

// --- Function to create an invoice (Copied here for admin use, or could be in a global functions file) ---
function create_invoice_for_appointment_admin($conn, $appointment_id, $user_id, $doctor_id, $default_amount = 500.00, $currency = 'INR') { // CHANGED: Default amount and currency
    $check_sql = "SELECT id FROM invoices WHERE appointment_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "i", $appointment_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        return true; 
    }
    mysqli_stmt_close($stmt_check);

    $invoice_uid = 'INV-' . strtoupper(uniqid()) . '-' . $appointment_id; 
    $status = 'unpaid';
    $due_date = date('Y-m-d', strtotime('+7 days'));

    $sql_insert_invoice = "INSERT INTO invoices (appointment_id, user_id, doctor_id, amount, currency, status, invoice_uid, created_at, due_date) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert_invoice);
    if (!$stmt_insert) {
        error_log("Admin Invoice prepare error: " . mysqli_error($conn));
        return false;
    }
    mysqli_stmt_bind_param($stmt_insert, "iiidssss", $appointment_id, $user_id, $doctor_id, $default_amount, $currency, $status, $invoice_uid, $due_date);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
        return true;
    } else {
        error_log("Admin Invoice execute error: " . mysqli_stmt_error($stmt_insert));
        mysqli_stmt_close($stmt_insert);
        return false;
    }
}
// --- End Function ---

// --- START: Auto-complete ALL past approved appointments (Admin view) ---
$sql_auto_complete_admin = "UPDATE appointments 
                            SET status = 'completed' 
                            WHERE status = 'approved' 
                            AND CONCAT(appointment_date, ' ', appointment_time) < ?";
if ($stmt_auto_complete_admin = mysqli_prepare($conn, $sql_auto_complete_admin)) {
    $current_server_time_for_compare_admin = date('Y-m-d H:i:s');
    mysqli_stmt_bind_param($stmt_auto_complete_admin, "s", $current_server_time_for_compare_admin);
    if (!mysqli_stmt_execute($stmt_auto_complete_admin)) {
        error_log("Error auto-completing all appointments (admin): " . mysqli_stmt_error($stmt_auto_complete_admin));
    }
    mysqli_stmt_close($stmt_auto_complete_admin);
} else {
    error_log("Error preparing auto-complete statement (admin): " . mysqli_error($conn));
}
// --- END: Auto-complete ALL past approved appointments ---


if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $app_id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);
    $allowed_statuses = ['pending', 'approved', 'cancelled', 'completed'];

    if (in_array($new_status, $allowed_statuses)) {
        // Fetch user_id and doctor_id for invoice creation if approving
        $user_id_for_invoice = null;
        $doctor_id_for_invoice = null;
        if ($new_status == 'approved') {
            $get_ids_sql = "SELECT user_id, doctor_id FROM appointments WHERE id = ?";
            $stmt_get_ids = mysqli_prepare($conn, $get_ids_sql);
            mysqli_stmt_bind_param($stmt_get_ids, "i", $app_id);
            mysqli_stmt_execute($stmt_get_ids);
            $res_get_ids = mysqli_stmt_get_result($stmt_get_ids);
            if($row_ids = mysqli_fetch_assoc($res_get_ids)) {
                $user_id_for_invoice = $row_ids['user_id'];
                $doctor_id_for_invoice = $row_ids['doctor_id'];
            }
            mysqli_stmt_close($stmt_get_ids);
        }

        $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_update, "si", $new_status, $app_id);
        if (mysqli_stmt_execute($stmt_update)) {
            $current_message = "Appointment ID $app_id status updated to '$new_status'.";
            $current_message_type = "success";

            if ($new_status == 'approved' && $user_id_for_invoice !== null && $doctor_id_for_invoice !== null) {
                if (create_invoice_for_appointment_admin($conn, $app_id, $user_id_for_invoice, $doctor_id_for_invoice, 500.00, 'INR')) { // Pass INR
                     $current_message .= " Invoice generated (INR 500.00).";
                } else {
                     $current_message .= " Failed to generate invoice.";
                     $current_message_type = "warning";
                }
            }
            set_message($current_message, $current_message_type);
        } else {
            set_message("Error updating appointment status: " . mysqli_error($conn), "danger");
        }
        mysqli_stmt_close($stmt_update);
        redirect($base_url . '/admin/view_appointments.php' . (isset($_GET['filter_status_appt']) ? '?filter_status='.$_GET['filter_status_appt'] : ''));
    } else {
        set_message("Invalid status provided.", "danger");
        redirect($base_url . '/admin/view_appointments.php');
    }
}


$filter_status_appt = isset($_GET['filter_status_appt']) ? mysqli_real_escape_string($conn, $_GET['filter_status_appt']) : '';
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
               u.name as patient_name, u.email as patient_email, 
               d.name as doctor_name, d.specialization as doctor_specialization
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN doctors d ON a.doctor_id = d.id";

if (!empty($filter_status_appt)) {
    $sql .= " WHERE a.status = ?";
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($filter_status_appt)) {
    mysqli_stmt_bind_param($stmt, "s", $filter_status_appt);
}
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>All Appointments</h1>
</div>

<form method="GET" action="<?php echo $base_url; ?>/admin/view_appointments.php" class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <label for="filter_status_appt" class="form-label">Filter by Appointment Status:</label>
            <select name="filter_status_appt" id="filter_status_appt" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo ($filter_status_appt == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo ($filter_status_appt == 'approved') ? 'selected' : ''; ?>>Approved</option>
                <option value="cancelled" <?php echo ($filter_status_appt == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                <option value="completed" <?php echo ($filter_status_appt == 'completed') ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>

<?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($app = mysqli_fetch_assoc($appointments_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($app['id']); ?></td>
                <td><?php echo htmlspecialchars($app['patient_name']); ?><br><small><?php echo htmlspecialchars($app['patient_email']); ?></small></td>
                <td> <?php echo htmlspecialchars($app['doctor_name']); ?><br><small><?php echo htmlspecialchars($app['doctor_specialization']); ?></small></td>
                <td><?php echo date("D, M j, Y", strtotime($app['appointment_date'])); ?></td>
                <td><?php echo date("g:i A", strtotime($app['appointment_time'])); ?></td>
                <td>
                    <span class="badge bg-<?php 
                        switch ($app['status']) {
                            case 'approved': echo 'success'; break;
                            case 'pending': echo 'warning text-dark'; break;
                            case 'cancelled': echo 'danger'; break;
                            case 'completed': echo 'info text-dark'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></span>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo $app['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo $app['id']; ?>">
                            <?php if ($app['status'] == 'pending'): ?>
                                <li><a class="dropdown-item" href="?action=update&id=<?php echo $app['id']; ?>&status=approved&filter_status_appt=<?php echo $filter_status_appt; ?>" onclick="return confirm('Approve this appointment? An invoice will be generated.');">Approve</a></li>
                            <?php endif; ?>
                            <?php if ($app['status'] == 'pending' || $app['status'] == 'approved'): ?>
                                <li><a class="dropdown-item" href="?action=update&id=<?php echo $app['id']; ?>&status=cancelled&filter_status_appt=<?php echo $filter_status_appt; ?>" onclick="return confirm('Cancel this appointment?');">Cancel</a></li>
                            <?php endif; ?>
                             <?php if ($app['status'] == 'approved'): ?>
                                <li><a class="dropdown-item" href="?action=update&id=<?php echo $app['id']; ?>&status=completed&filter_status_appt=<?php echo $filter_status_appt; ?>" onclick="return confirm('Mark as completed?');">Mark Completed</a></li>
                            <?php endif; ?>
                             <?php if (empty($app['status']) || !in_array($app['status'], ['pending', 'approved'])): ?>
                                <li><a class="dropdown-item text-muted disabled" href="#">No actions available</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info mt-3">No appointments found<?php echo !empty($filter_status_appt) ? " with status '" . htmlspecialchars($filter_status_appt) . "'" : ""; ?>.</div>
<?php endif; ?>

<?php mysqli_stmt_close($stmt); ?>
<?php require_once 'partials/admin_footer.php'; ?>