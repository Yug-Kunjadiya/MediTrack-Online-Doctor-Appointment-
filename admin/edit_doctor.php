<?php
require_once 'partials/admin_header.php';
$base_url = "/meditrack";

$doctor_id = null;
$name = '';
$specialization = '';
$email = '';
$available_slots_json = '[]'; // Default to empty JSON array for availability

// Check if editing an existing doctor
if (isset($_GET['id'])) {
    $doctor_id = mysqli_real_escape_string($conn, $_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM doctors WHERE id = '$doctor_id'");
    if ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        $specialization = $row['specialization'];
        $email = $row['email'];
        $available_slots_json = $row['available_slots_json'] ? $row['available_slots_json'] : '[]';
    } else {
        set_message("Doctor (ID: $doctor_id) not found.", "danger");
        redirect($base_url . '/admin/manage_doctors.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $specialization = mysqli_real_escape_string($conn, trim($_POST['specialization']));
    $email_posted = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password']; // Only update if provided
    $posted_available_slots_json = $_POST['available_slots_json'];

    // Basic validation
    if (empty($name) || empty($specialization) || empty($email_posted)) {
        set_message("Name, specialization, and email are required.", "danger");
    } elseif (!filter_var($email_posted, FILTER_VALIDATE_EMAIL)) {
        set_message("Invalid email format.", "danger");
    } else {
        // Validate JSON for available_slots
        json_decode($posted_available_slots_json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            set_message("Invalid JSON format for available slots. Please check example.", "danger");
        } else {
            $available_slots_json = $posted_available_slots_json; // Use validated JSON

            // Check if email is being changed or if it's a new doctor, then check for uniqueness
            $email_check_needed = true;
            if ($doctor_id && $email_posted === $email) { // Email not changed for existing doctor
                $email_check_needed = false;
            }

            $email_exists = false;
            if ($email_check_needed) {
                $sql_email_check = "SELECT id FROM doctors WHERE email = ? AND id != ?";
                $stmt_email_check = mysqli_prepare($conn, $sql_email_check);
                $current_id_for_check = $doctor_id ? $doctor_id : 0; // if new doctor, id is 0 (or any non-existing id)
                mysqli_stmt_bind_param($stmt_email_check, "si", $email_posted, $current_id_for_check);
                mysqli_stmt_execute($stmt_email_check);
                mysqli_stmt_store_result($stmt_email_check);
                if (mysqli_stmt_num_rows($stmt_email_check) > 0) {
                    $email_exists = true;
                }
                mysqli_stmt_close($stmt_email_check);
            }

            if ($email_exists) {
                set_message("This email address is already in use by another doctor.", "warning");
            } else {
                $email = $email_posted; // Update email variable with the posted one if it's fine

                if ($doctor_id) { // Update existing doctor
                    $sql = "UPDATE doctors SET name=?, specialization=?, email=?, available_slots_json=?";
                    $params = [$name, $specialization, $email, $available_slots_json];
                    $types = "ssss";

                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                             set_message("Password must be at least 6 characters long.", "danger");
                             $stmt = false; // Prevent execution
                        } else {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $sql .= ", password=?";
                            $params[] = $hashed_password;
                            $types .= "s";
                        }
                    }
                    if (!isset($stmt) || $stmt !== false) { // Proceed if password validation passed or no password change
                        $sql .= " WHERE id=?";
                        $params[] = $doctor_id;
                        $types .= "i";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                    }

                } else { // Add new doctor
                    if (empty($password)) {
                        set_message("Password is required for new doctors.", "danger");
                        $stmt = false; // Prevent execution
                    } elseif (strlen($password) < 6) {
                         set_message("Password must be at least 6 characters long for new doctor.", "danger");
                         $stmt = false; // Prevent execution
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO doctors (name, specialization, email, password, role, available_slots_json) VALUES (?, ?, ?, ?, 'doctor', ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "sssss", $name, $specialization, $email, $hashed_password, $available_slots_json);
                    }
                }

                if (isset($stmt) && $stmt && mysqli_stmt_execute($stmt)) {
                    set_message("Doctor information " . ($doctor_id ? "updated" : "added") . " successfully.", "success");
                    redirect($base_url . '/admin/manage_doctors.php');
                } elseif (isset($stmt) && $stmt === false) {
                    // Message already set by password/email validation
                } 
                 elseif (isset($stmt) && $stmt) { // Check if $stmt is not false before trying to get error
                    set_message("Error: " . mysqli_stmt_error($stmt), "danger");
                } elseif(!isset($stmt)) { // If $stmt was not even prepared due to some prior error
                     // The message for email existence or other validation would have been set
                }
                if (isset($stmt) && $stmt) mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<h1><?php echo $doctor_id ? 'Edit Doctor Details' : 'Add New Doctor'; ?></h1>
<hr>
<form action="<?php echo $base_url; ?>/admin/edit_doctor.php<?php echo $doctor_id ? '?id='.$doctor_id : ''; ?>" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="mb-3">
                <label for="specialization" class="form-label">Specialization</label>
                <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <?php echo $doctor_id ? '<small class="text-muted">(Leave blank to keep current)</small>' : '<small class="text-danger">*Required</small>'; ?></label>
                <input type="password" class="form-control" id="password" name="password" <?php echo !$doctor_id ? 'required' : ''; ?>>
            </div>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="available_slots_json" class="form-label">Available Time Slots (JSON Format)</label>
        <textarea class="form-control" id="available_slots_json" name="available_slots_json" rows="8"><?php echo htmlspecialchars($available_slots_json); ?></textarea>
        <small class="form-text text-muted">
            Example: <pre class="bg-light p-2 rounded mt-1">{"YYYY-MM-DD": ["HH:MM", "HH:MM"], "YYYY-MM-DD": ["HH:MM"]}<br>e.g., {"2024-08-10": ["09:00", "10:00", "11:30"], "2024-08-11": ["14:00", "15:00"]}</pre>
            Use 24-hour format for times. Ensure JSON is valid.
        </small>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?php echo $doctor_id ? 'Update Doctor' : 'Add Doctor'; ?></button>
    <a href="<?php echo $base_url; ?>/admin/manage_doctors.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once 'partials/admin_footer.php'; ?>