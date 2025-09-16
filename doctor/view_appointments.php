<?php
require_once 'partials/doctor_header.php';
$base_url = "/meditrack";
$doctor_id = $_SESSION['id'];

// --- Function to create an invoice ---
function create_invoice_for_appointment($conn, $appointment_id, $user_id, $doctor_id, $default_amount = 500.00, $currency = 'INR') { // CHANGED: Default amount and currency
    // Check if invoice already exists for this appointment to prevent duplicates
    $check_sql = "SELECT id FROM invoices WHERE appointment_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "i", $appointment_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        return true; // Invoice already exists, consider it success for this flow
    }
    mysqli_stmt_close($stmt_check);

    $invoice_uid = 'INV-' . strtoupper(uniqid()) . '-' . $appointment_id; 
    $status = 'unpaid';
    $due_date = date('Y-m-d', strtotime('+7 days'));


    $sql_insert_invoice = "INSERT INTO invoices (appointment_id, user_id, doctor_id, amount, currency, status, invoice_uid, created_at, due_date) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert_invoice);
    if (!$stmt_insert) {
        error_log("Invoice prepare error: " . mysqli_error($conn));
        return false;
    }
    mysqli_stmt_bind_param($stmt_insert, "iiidssss", $appointment_id, $user_id, $doctor_id, $default_amount, $currency, $status, $invoice_uid, $due_date);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
        return true;
    } else {
        error_log("Invoice execute error: " . mysqli_stmt_error($stmt_insert));
        mysqli_stmt_close($stmt_insert);
        return false;
    }
}
// --- End Function to create an invoice ---


// --- START: Auto-complete past approved appointments for this doctor ---
$sql_auto_complete_doc = "UPDATE appointments 
                          SET status = 'completed' 
                          WHERE doctor_id = ? 
                          AND status = 'approved' 
                          AND CONCAT(appointment_date, ' ', appointment_time) < ?";
if ($stmt_auto_complete_doc = mysqli_prepare($conn, $sql_auto_complete_doc)) {
    $current_server_time_for_compare = date('Y-m-d H:i:s');
    mysqli_stmt_bind_param($stmt_auto_complete_doc, "is", $doctor_id, $current_server_time_for_compare);
    if (!mysqli_stmt_execute($stmt_auto_complete_doc)) {
        error_log("Error auto-completing appointments for doctor $doctor_id: " . mysqli_stmt_error($stmt_auto_complete_doc));
    }
    mysqli_stmt_close($stmt_auto_complete_doc);
} else {
     error_log("Error preparing auto-complete statement for doctor $doctor_id: " . mysqli_error($conn));
}
// --- END: Auto-complete past approved appointments ---


if (isset($_GET['action']) && isset($_GET['id'])) {
    $app_id_to_update = (int)$_GET['id'];
    $action = $_GET['action']; 
    $new_status = '';

    switch ($action) {
        case 'approve': $new_status = 'approved'; break;
        case 'cancel': $new_status = 'cancelled'; break;
        case 'complete': $new_status = 'completed'; break;
        default: set_message("Invalid action.", "danger"); break;
    }

    if (!empty($new_status)) {
        $user_id_for_invoice = null;
        if ($new_status == 'approved') {
            $get_user_sql = "SELECT user_id FROM appointments WHERE id = ? AND doctor_id = ?";
            $stmt_get_user = mysqli_prepare($conn, $get_user_sql);
            mysqli_stmt_bind_param($stmt_get_user, "ii", $app_id_to_update, $doctor_id);
            mysqli_stmt_execute($stmt_get_user);
            $res_get_user = mysqli_stmt_get_result($stmt_get_user);
            if($row_user = mysqli_fetch_assoc($res_get_user)) {
                $user_id_for_invoice = $row_user['user_id'];
            }
            mysqli_stmt_close($stmt_get_user);
        }
        
        $check_sql = "SELECT id FROM appointments WHERE id = ? AND doctor_id = ?";
        $stmt_check = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt_check, "ii", $app_id_to_update, $doctor_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) == 1) {
            $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt_update, "si", $new_status, $app_id_to_update);
            if (mysqli_stmt_execute($stmt_update)) {
                $current_message = "Appointment (ID: $app_id_to_update) status updated to '$new_status'.";
                $current_message_type = "success";
                
                if ($new_status == 'approved' && $user_id_for_invoice !== null) {
                    // Pass new default amount and INR currency to the function
                    if (create_invoice_for_appointment($conn, $app_id_to_update, $user_id_for_invoice, $doctor_id, 500.00, 'INR')) { // Pass INR and example amount
                        $current_message .= " Invoice generated (INR 500.00).";
                    } else {
                        $current_message .= " Failed to generate invoice.";
                        $current_message_type = "warning";
                    }
                }
                set_message($current_message, $current_message_type);

            } else {
                set_message("Error updating appointment: " . mysqli_error($conn), "danger");
            }
            mysqli_stmt_close($stmt_update);
        } else {
            set_message("Appointment not found or you don't have permission to modify it.", "warning");
        }
        mysqli_stmt_close($stmt_check);
        redirect($base_url . '/doctor/view_appointments.php' . (isset($_GET['filter_status']) ? '?filter_status='.$_GET['filter_status'] : ''));
    } else {
        if(empty($_SESSION['message'])) set_message("Invalid action specified.", "danger");
        redirect($base_url . '/doctor/view_appointments.php');
    }
}

$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
               u.id as patient_user_id, u.name as patient_name, u.email as patient_email
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        WHERE a.doctor_id = ?";
$params = [$doctor_id];
$types = "i";

if (!empty($filter_status)) {
    $sql .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-calendar-week"></i> My Appointments</h1>
</div>

<form method="GET" action="<?php echo $base_url; ?>/doctor/view_appointments.php" class="mb-4 p-3 bg-light rounded shadow-sm">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
             <label for="filter_status" class="form-label">Filter by Status:</label>
            <select name="filter_status" id="filter_status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo ($filter_status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                <option value="cancelled" <?php echo ($filter_status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                <option value="completed" <?php echo ($filter_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
         <div class="col-md-2">
            <a href="<?php echo $base_url; ?>/doctor/view_appointments.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
        </div>
    </div>
</form>

<?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Appt. ID</th>
                <th>Patient Name</th>
                <th>Patient Email</th>
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
                <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                <td><?php echo htmlspecialchars($app['patient_email']); ?></td>
                <td><?php echo date("D, M j, Y", strtotime($app['appointment_date'])); ?></td>
                <td><?php echo date("g:i A", strtotime($app['appointment_time'])); ?></td>
                <td>
                    <span class="badge fs-6 bg-<?php 
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
                    <?php if ($app['status'] == 'approved'): ?>
                        <a href="<?php echo $base_url; ?>/chat.php?appointment_id=<?php echo $app['id']; ?>&with_id=<?php echo $app['patient_user_id']; ?>&with_role=user" class="btn btn-sm btn-info me-1" title="Chat with Patient <?php echo htmlspecialchars($app['patient_name']); ?>">
                            <i class="bi bi-chat-dots-fill"></i> Chat
                        </a>
                    <?php endif; ?>
                    <?php if ($app['status'] == 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $app['id']; ?>&filter_status=<?php echo $filter_status; ?>" class="btn btn-sm btn-success me-1" title="Approve" onclick="return confirm('Approve this appointment? An invoice will be generated.');"><i class="bi bi-check-lg"></i></a>
                        <a href="?action=cancel&id=<?php echo $app['id']; ?>&filter_status=<?php echo $filter_status; ?>" class="btn btn-sm btn-danger" title="Cancel" onclick="return confirm('Cancel this appointment request?');"><i class="bi bi-x-lg"></i></a>
                    <?php elseif ($app['status'] == 'approved'): ?>
                        <a href="?action=complete&id=<?php echo $app['id']; ?>&filter_status=<?php echo $filter_status; ?>" class="btn btn-sm btn-secondary me-1 text-dark" title="Mark as Completed" onclick="return confirm('Mark this appointment as completed?');"><i class="bi bi-check2-all"></i></a>
                        <a href="?action=cancel&id=<?php echo $app['id']; ?>&filter_status=<?php echo $filter_status; ?>" class="btn btn-sm btn-warning text-dark" title="Cancel Approved Appointment" onclick="return confirm('Cancel this approved appointment? The patient will need to be informed.');"><i class="bi bi-calendar-x"></i></a>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No actions</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info mt-3">No appointments found<?php echo !empty($filter_status) ? " with status '" . htmlspecialchars($filter_status) . "'" : ""; ?>.</div>
<?php endif; ?>

<?php mysqli_stmt_close($stmt); ?>
<?php require_once 'partials/doctor_footer.php'; ?>