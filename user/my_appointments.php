<?php
require_once 'partials/user_header.php';
$base_url = "/meditrack";
$user_id = $_SESSION['id'];

// --- START: Auto-complete past approved appointments for this user ---
$sql_auto_complete = "UPDATE appointments 
                      SET status = 'completed' 
                      WHERE user_id = ? 
                      AND status = 'approved' 
                      AND CONCAT(appointment_date, ' ', appointment_time) < ?";
if ($stmt_auto_complete = mysqli_prepare($conn, $sql_auto_complete)) {
    $current_server_time_for_compare = date('Y-m-d H:i:s');
    mysqli_stmt_bind_param($stmt_auto_complete, "is", $user_id, $current_server_time_for_compare);
    if (!mysqli_stmt_execute($stmt_auto_complete)) {
        error_log("Error auto-completing appointments for user $user_id: " . mysqli_stmt_error($stmt_auto_complete));
    }
    mysqli_stmt_close($stmt_auto_complete);
} else {
    error_log("Error preparing auto-complete statement for user $user_id: " . mysqli_error($conn));
}
// --- END: Auto-complete past approved appointments ---


if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $app_id_to_cancel = (int)$_GET['id'];
    $check_sql = "SELECT status FROM appointments WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved')";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "ii", $app_id_to_cancel, $user_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) == 1) {
        $cancel_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $stmt_cancel = mysqli_prepare($conn, $cancel_sql);
        mysqli_stmt_bind_param($stmt_cancel, "i", $app_id_to_cancel);
        if (mysqli_stmt_execute($stmt_cancel)) {
            set_message("Appointment (ID: $app_id_to_cancel) cancelled successfully.", "success");
        } else {
            set_message("Error cancelling appointment: " . mysqli_error($conn), "danger");
        }
        mysqli_stmt_close($stmt_cancel);
    } else {
        set_message("Appointment not found or cannot be cancelled.", "warning");
    }
    mysqli_stmt_close($stmt_check);
    redirect($base_url . '/user/my_appointments.php');
}

// Fetch user's appointments, check if already reviewed, and potentially invoice info
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.doctor_id,
               d.name as doctor_name, d.specialization as doctor_specialization,
               (SELECT COUNT(*) FROM reviews r WHERE r.appointment_id = a.id) as review_count,
               inv.id as invoice_id, inv.invoice_uid, inv.status as invoice_status /* Alias inv.status */
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN invoices inv ON inv.appointment_id = a.id /* LEFT JOIN to get appt even if no invoice */
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$appointments_result = mysqli_stmt_get_result($stmt);
?>

<style>
/* Star Rating CSS for Modal Input */
.star-rating-input-container { 
    text-align: center; 
}
.star-rating-input {
    display: inline-block; 
    unicode-bidi: bidi-override; 
    direction: rtl; 
    font-size: 0; 
}
.star-rating-input > input[type="radio"] { display: none; }
.star-rating-input > label {
    display: inline-block;
    position: relative;
    font-size: 2.8rem; 
    color: #ddd;    
    cursor: pointer;
    padding: 0 2px; 
    transition: color 0.2s ease-in-out;
}
.star-rating-input > label:hover,
.star-rating-input > label:hover ~ label { color: #ffc107; }
.star-rating-input > input[type="radio"]:checked ~ label { color: #ffc107; }
</style>

<h1><i class="bi bi-calendar-check"></i> My Appointments</h1>
<hr>

<?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
<div class="accordion" id="appointmentsAccordion">
    
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingUpcoming">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpcoming" aria-expanded="true" aria-controls="collapseUpcoming">
                Upcoming & Pending Appointments
            </button>
        </h2>
        <div id="collapseUpcoming" class="accordion-collapse collapse show" aria-labelledby="headingUpcoming" data-bs-parent="#appointmentsAccordion">
            <div class="accordion-body">
                <div class="list-group">
                    <?php 
                    $has_upcoming = false;
                    // Need to seek to 0 if we iterate over $appointments_result multiple times
                    mysqli_data_seek($appointments_result, 0); 
                    while ($app = mysqli_fetch_assoc($appointments_result)): 
                        if ($app['status'] == 'pending' || ($app['status'] == 'approved' && $app['appointment_date'] >= date("Y-m-d"))):
                        $has_upcoming = true;
                    ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 shadow-sm rounded">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 text-primary"> <?php echo htmlspecialchars($app['doctor_name']); ?></h5>
                            <small class="text-muted"><?php echo date("D, M j, Y", strtotime($app['appointment_date'])); ?></small>
                        </div>
                        <p class="mb-1">Specialization: <?php echo htmlspecialchars($app['doctor_specialization']); ?></p>
                        <p class="mb-1">Time: <?php echo date("g:i A", strtotime($app['appointment_time'])); ?></p>
                        <p class="mb-1">Status: 
                            <span class="badge bg-<?php echo $app['status'] == 'approved' ? 'success' : 'warning text-dark'; ?>">
                                <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                            </span>
                        </p>
                        <div class="mt-2">
                            <?php if ($app['status'] == 'approved'): ?>
                                <a href="<?php echo $base_url; ?>/chat.php?appointment_id=<?php echo $app['id']; ?>&with_id=<?php echo $app['doctor_id']; ?>&with_role=doctor" class="btn btn-sm btn-info me-2">
                                    <i class="bi bi-chat-dots-fill"></i> Chat with  <?php echo htmlspecialchars($app['doctor_name']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($app['status'] == 'pending' || $app['status'] == 'approved'): ?>
                                <a href="?action=cancel&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                    <i class="bi bi-x-circle"></i> Cancel Appointment
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php // Display Invoice Info for Approved Upcoming Appointments
                        if ($app['status'] == 'approved' && !empty($app['invoice_id'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <strong class="me-2">Invoice:</strong> <?php echo htmlspecialchars($app['invoice_uid']); ?> 
                                (Status: <span class="badge bg-<?php echo $app['invoice_status'] == 'paid' ? 'success' : ($app['invoice_status'] == 'unpaid' ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($app['invoice_status'])); ?>
                                </span>)
                                <a href="<?php echo $base_url; ?>/generate_invoice_pdf.php?invoice_id=<?php echo $app['invoice_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> Download Invoice
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; endwhile; ?>
                    <?php if (!$has_upcoming): ?>
                        <p class="text-muted">No upcoming or pending appointments.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPast">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePast" aria-expanded="false" aria-controls="collapsePast">
                Past & Cancelled Appointments
            </button>
        </h2>
        <div id="collapsePast" class="accordion-collapse collapse" aria-labelledby="headingPast" data-bs-parent="#appointmentsAccordion">
            <div class="accordion-body">
                 <div class="list-group">
                    <?php 
                    $has_past = false;
                    mysqli_data_seek($appointments_result, 0); // Reset pointer for second loop
                    while ($app = mysqli_fetch_assoc($appointments_result)): 
                        // Condition for past appointments
                        if ($app['status'] == 'completed' || $app['status'] == 'cancelled' || ($app['status'] == 'approved' && $app['appointment_date'] < date("Y-m-d"))): 
                        $has_past = true;
                        $display_status = ($app['status'] == 'approved' && $app['appointment_date'] < date("Y-m-d")) ? 'completed' : $app['status'];
                    ?>
                     <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 shadow-sm rounded bg-light">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 text-muted"> <?php echo htmlspecialchars($app['doctor_name']); ?></h5>
                            <small class="text-muted"><?php echo date("D, M j, Y", strtotime($app['appointment_date'])); ?></small>
                        </div>
                        <p class="mb-1">Specialization: <?php echo htmlspecialchars($app['doctor_specialization']); ?></p>
                        <p class="mb-1">Time: <?php echo date("g:i A", strtotime($app['appointment_time'])); ?></p>
                        <p class="mb-0">Status: 
                             <span class="badge bg-<?php echo $display_status == 'completed' ? 'info text-dark' : 'danger'; ?>">
                                <?php echo ucfirst(htmlspecialchars($display_status)); ?>
                             </span>
                        </p>
                        <?php if ($display_status == 'completed'): ?>
                            <?php if ($app['review_count'] == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning mt-2" 
                                        data-bs-toggle="modal" data-bs-target="#reviewModal"
                                        data-appointment-id="<?php echo $app['id']; ?>"
                                        data-doctor-id="<?php echo $app['doctor_id']; ?>"
                                        data-doctor-name="<?php echo htmlspecialchars($app['doctor_name']); ?>">
                                    <i class="bi bi-star-half"></i> Leave a Review
                                </button>
                            <?php else: ?>
                                <span class="badge bg-success mt-2"><i class="bi bi-check-circle-fill"></i> Reviewed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php // Display Invoice Info for Completed Appointments
                        if ($display_status == 'completed' && !empty($app['invoice_id'])): ?>
                            <div class="mt-2 pt-2 border-top">
                                <strong class="me-2">Invoice:</strong> <?php echo htmlspecialchars($app['invoice_uid']); ?> 
                                (Status: <span class="badge bg-<?php echo $app['invoice_status'] == 'paid' ? 'success' : ($app['invoice_status'] == 'unpaid' ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($app['invoice_status'])); ?>
                                </span>)
                                <a href="<?php echo $base_url; ?>/generate_invoice_pdf.php?invoice_id=<?php echo $app['invoice_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> Download Invoice
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; endwhile; ?>
                    <?php if (!$has_past): ?>
                        <p class="text-muted">No past or cancelled appointments found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info mt-3">You have no appointments booked yet. <a href="<?php echo $base_url; ?>/user/view_doctors.php">Find a doctor and book one!</a></div>
<?php endif; ?>


<!-- Review Modal (already provided, kept for completeness) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?php echo $base_url; ?>/user/submit_review.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="reviewModalLabel">Leave a Review for  <span id="modalDoctorName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="appointment_id" id="modalAppointmentId">
          <input type="hidden" name="doctor_id" id="modalDoctorId">
          
          <div class="mb-3 star-rating-input-container">
            <label class="form-label d-block mb-2"><strong>Your Rating:</strong></label>
            <div class="star-rating-input">
                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars">★</label>
                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">★</label>
                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">★</label>
                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">★</label>
                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">★</label>
            </div>
          </div>

          <div class="mb-3">
            <label for="comment" class="form-label"><strong>Your Comments (Optional):</strong></label>
            <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Share your experience..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Review</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var appointmentId = button.getAttribute('data-appointment-id');
            var doctorId = button.getAttribute('data-doctor-id');
            var doctorName = button.getAttribute('data-doctor-name');

            var modalTitleDoctorNameSpan = reviewModal.querySelector('#modalDoctorName');
            var modalAppointmentIdInput = reviewModal.querySelector('#modalAppointmentId');
            var modalDoctorIdInput = reviewModal.querySelector('#modalDoctorId');

            if(modalTitleDoctorNameSpan) modalTitleDoctorNameSpan.textContent = doctorName;
            if(modalAppointmentIdInput) modalAppointmentIdInput.value = appointmentId;
            if(modalDoctorIdInput) modalDoctorIdInput.value = doctorId;

            reviewModal.querySelectorAll('.star-rating-input input[type="radio"]').forEach(radio => radio.checked = false);
            var commentTextarea = reviewModal.querySelector('#comment');
            if(commentTextarea) commentTextarea.value = '';
        });
    }
});
</script>

<?php 
if($stmt) mysqli_stmt_close($stmt); 
?>
<?php require_once 'partials/user_footer.php'; ?>