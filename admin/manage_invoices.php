<?php
require_once 'partials/admin_header.php'; // Includes db.php, session_start(), check_login('admin')
$base_url = "/meditrack";

// --- Function to create an invoice (Copied here for admin use if admin can approve appointments, or should be in a shared functions file) ---
// This function is relevant if admin action leads to NEW invoice creation from this page, not typically for just managing existing ones.
// For this page, we mostly focus on updating status and viewing.
/*
function create_invoice_for_appointment_admin($conn, $appointment_id, $user_id, $doctor_id, $default_amount = 500.00, $currency = 'INR') {
    // ... (full function as provided before if needed for actions on this page) ...
}
*/
// --- End Function ---


// Handle Invoice Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_invoice_status'])) {
    $invoice_id_to_update = (int)$_POST['invoice_id'];
    $new_invoice_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    // Assuming payment_details is a new field you might add to the invoices table
    $payment_details_posted = isset($_POST['payment_details']) ? trim($_POST['payment_details']) : null;
    
    $allowed_invoice_statuses = ['unpaid', 'paid', 'cancelled'];

    if (in_array($new_invoice_status, $allowed_invoice_statuses)) {
        // If payment_details field exists in your 'invoices' table:
        // $sql_update = "UPDATE invoices SET status = ?, payment_details = ? WHERE id = ?";
        // $stmt_update_inv = mysqli_prepare($conn, $sql_update);
        // mysqli_stmt_bind_param($stmt_update_inv, "ssi", $new_invoice_status, $payment_details_posted, $invoice_id_to_update);
        
        // If no payment_details field, or you handle it separately:
        $sql_update = "UPDATE invoices SET status = ? WHERE id = ?";
        $stmt_update_inv = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update_inv, "si", $new_invoice_status, $invoice_id_to_update);


        if (mysqli_stmt_execute($stmt_update_inv)) {
            set_message("Invoice (ID: $invoice_id_to_update) status updated to '$new_invoice_status'.", "success");
        } else {
            set_message("Error updating invoice status: " . mysqli_stmt_error($stmt_update_inv), "danger");
        }
        mysqli_stmt_close($stmt_update_inv);
    } else {
        set_message("Invalid invoice status provided.", "danger");
    }
    redirect($base_url . '/admin/manage_invoices.php' . (isset($_GET['filter_status']) ? '?filter_status='.$_GET['filter_status'] : ''));
}


// Fetch all invoices with patient and doctor names
$filter_invoice_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$sql_invoices = "SELECT 
                    i.id as invoice_id, i.invoice_uid, i.amount, i.currency, i.status as invoice_status, 
                    i.created_at as invoice_created_at, i.due_date, i.payment_details, /* Added payment_details */
                    a.id as appointment_id, a.appointment_date,
                    u.name as patient_name, 
                    d.name as doctor_name
                 FROM invoices i
                 JOIN appointments a ON i.appointment_id = a.id
                 JOIN users u ON i.user_id = u.id
                 JOIN doctors d ON i.doctor_id = d.id";

if (!empty($filter_invoice_status)) {
    $sql_invoices .= " WHERE i.status = ?";
}
$sql_invoices .= " ORDER BY i.created_at DESC";

$stmt_invoices = mysqli_prepare($conn, $sql_invoices);
if (!$stmt_invoices) {
    // Handle error, e.g., log it and display a generic message
    error_log("Error preparing invoices query: " . mysqli_error($conn));
    set_message("Error fetching invoices. Please try again.", "danger");
    $invoices_result = false; // Set to false so the 'else' block for no invoices is triggered
} else {
    if (!empty($filter_invoice_status)) {
        mysqli_stmt_bind_param($stmt_invoices, "s", $filter_invoice_status);
    }
    mysqli_stmt_execute($stmt_invoices);
    $invoices_result = mysqli_stmt_get_result($stmt_invoices);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 pt-3">
    <h1><i class="bi bi-receipt"></i> Manage Invoices</h1>
</div>

<form method="GET" action="<?php echo $base_url; ?>/admin/manage_invoices.php" class="mb-4 p-3 bg-light rounded shadow-sm">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="filter_status" class="form-label">Filter by Invoice Status:</label>
            <select name="filter_status" id="filter_status" class="form-select">
                <option value="">All Statuses</option>
                <option value="unpaid" <?php echo ($filter_invoice_status == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                <option value="paid" <?php echo ($filter_invoice_status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="cancelled" <?php echo ($filter_invoice_status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="<?php echo $base_url; ?>/admin/manage_invoices.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </div>
</form>

<?php if ($invoices_result && mysqli_num_rows($invoices_result) > 0): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Invoice UID</th>
                <th>Appt. ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Issued</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($inv = mysqli_fetch_assoc($invoices_result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($inv['invoice_uid']); ?></td>
                <td><?php echo htmlspecialchars($inv['appointment_id']); ?></td>
                <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
                <td> <?php echo htmlspecialchars($inv['doctor_name']); ?></td>
                <td><?php echo htmlspecialchars($inv['currency']) . ' ' . number_format($inv['amount'], 2); ?></td>
                <td>
                    <span class="badge fs-6 bg-<?php 
                        switch ($inv['invoice_status']) {
                            case 'paid': echo 'success'; break;
                            case 'unpaid': echo 'warning text-dark'; break;
                            case 'cancelled': echo 'danger'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst(htmlspecialchars($inv['invoice_status'])); ?></span>
                </td>
                <td><?php echo date("M j, Y", strtotime($inv['invoice_created_at'])); ?></td>
                <td><?php echo $inv['due_date'] ? date("M j, Y", strtotime($inv['due_date'])) : 'N/A'; ?></td>
                <td>
                    <a href="<?php echo $base_url; ?>/generate_invoice_pdf.php?invoice_id=<?php echo $inv['invoice_id']; ?>" target="_blank" class="btn btn-sm btn-info me-1" title="View/Download PDF for Invoice #<?php echo htmlspecialchars($inv['invoice_uid']); ?>"><i class="bi bi-file-earmark-pdf"></i></a>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateInvoiceStatusModal" 
                            data-invoice-id="<?php echo $inv['invoice_id']; ?>" 
                            data-current-status="<?php echo $inv['invoice_status']; ?>"
                            data-invoice-uid="<?php echo htmlspecialchars($inv['invoice_uid']); ?>"
                            data-payment-details="<?php echo htmlspecialchars($inv['payment_details'] ?? ''); ?>">
                        <i class="bi bi-pencil-square"></i> Edit Status
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php elseif ($invoices_result === false) : // This means query prep failed ?>
    <div class="alert alert-danger mt-3">Could not retrieve invoice data at this time. Please try again later or contact support.</div>
<?php else: ?>
<div class="alert alert-info mt-3">No invoices found<?php echo !empty($filter_invoice_status) ? " with status '" . htmlspecialchars($filter_invoice_status) . "'" : ""; ?>.</div>
<?php endif; ?>

<!-- Modal for Updating Invoice Status -->
<div class="modal fade" id="updateInvoiceStatusModal" tabindex="-1" aria-labelledby="updateInvoiceStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?php echo $base_url; ?>/admin/manage_invoices.php<?php echo !empty($filter_invoice_status) ? '?filter_status='.$filter_invoice_status : ''; ?>" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="updateInvoiceStatusModalLabel">Update Invoice Status for #<span id="modalInvoiceUid"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="invoice_id" id="modalInvoiceId">
          <div class="mb-3">
            <label for="modalNewStatus" class="form-label">New Status:</label>
            <select class="form-select" name="new_status" id="modalNewStatus" required>
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
          </div>
           <div class="mb-3">
            <label for="payment_details_admin" class="form-label">Payment Details/Notes (Optional):</label>
            <textarea class="form-control" name="payment_details" id="payment_details_admin_input" rows="3" placeholder="e.g., Paid via Card ending 1234, Txn ID: ..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="update_invoice_status" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var updateStatusModal = document.getElementById('updateInvoiceStatusModal');
    if (updateStatusModal) {
        updateStatusModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var invoiceId = button.getAttribute('data-invoice-id');
            var currentStatus = button.getAttribute('data-current-status');
            var invoiceUid = button.getAttribute('data-invoice-uid');
            var paymentDetails = button.getAttribute('data-payment-details');


            var modalInvoiceIdInput = updateStatusModal.querySelector('#modalInvoiceId');
            var modalInvoiceUidSpan = updateStatusModal.querySelector('#modalInvoiceUid');
            var modalNewStatusSelect = updateStatusModal.querySelector('#modalNewStatus');
            var modalPaymentDetailsTextarea = updateStatusModal.querySelector('#payment_details_admin_input');

            if(modalInvoiceIdInput) modalInvoiceIdInput.value = invoiceId;
            if(modalInvoiceUidSpan) modalInvoiceUidSpan.textContent = invoiceUid;
            if(modalNewStatusSelect) modalNewStatusSelect.value = currentStatus;
            if(modalPaymentDetailsTextarea) modalPaymentDetailsTextarea.value = paymentDetails || ''; 
        });
    }
});
</script>

<?php 
if($stmt_invoices && $invoices_result) mysqli_stmt_close($stmt_invoices);
// mysqli_close($conn) is handled by admin_footer.php
?>
<?php require_once 'partials/admin_footer.php'; ?>