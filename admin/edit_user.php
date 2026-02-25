<?php
require_once 'partials/admin_header.php';
$base_url = "/meditrack";

$user_id = null;
$name = '';
$email = '';

// Check if editing an existing user
if (isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['id']);
    $result = mysqli_query($conn, "SELECT id, name, email FROM users WHERE id = '$user_id' AND role='user'");
    if ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        $email = $row['email'];
    } else {
        set_message("Patient (ID: $user_id) not found.", "danger");
        redirect($base_url . '/admin/manage_users.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_posted = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email_posted = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password']; // Only update if provided

    // Basic validation
    if (empty($name_posted) || empty($email_posted)) {
        set_message("Name and email are required.", "danger");
    } elseif (!filter_var($email_posted, FILTER_VALIDATE_EMAIL)) {
        set_message("Invalid email format.", "danger");
    } else {
        // Check if email is being changed or if it's a new user, then check for uniqueness
        $email_check_needed = true;
        if ($user_id && $email_posted === $email) { // Email not changed for existing user
            $email_check_needed = false;
        }

        $email_exists = false;
        if ($email_check_needed) {
            $sql_email_check = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_email_check = mysqli_prepare($conn, $sql_email_check);
            $current_id_for_check = $user_id ? $user_id : 0; 
            mysqli_stmt_bind_param($stmt_email_check, "si", $email_posted, $current_id_for_check);
            mysqli_stmt_execute($stmt_email_check);
            mysqli_stmt_store_result($stmt_email_check);
            if (mysqli_stmt_num_rows($stmt_email_check) > 0) {
                $email_exists = true;
            }
            mysqli_stmt_close($stmt_email_check);
        }

        if ($email_exists) {
            set_message("This email address is already in use by another patient.", "warning");
        } else {
            $name = $name_posted; // Update name
            $email = $email_posted; // Update email if check passed

            if ($user_id) { // Update existing user
                $sql = "UPDATE users SET name=?, email=?";
                $params = [$name, $email];
                $types = "ss";

                if (!empty($password)) {
                    if (strlen($password) < 6) {
                         set_message("Password must be at least 6 characters long.", "danger");
                         $stmt = false; 
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql .= ", password=?";
                        $params[] = $hashed_password;
                        $types .= "s";
                    }
                }
                if (!isset($stmt) || $stmt !== false) {
                    $sql .= " WHERE id=? AND role='user'";
                    $params[] = $user_id;
                    $types .= "i";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                }
            } else { // Add new user
                if (empty($password)) {
                    set_message("Password is required for new patients.", "danger");
                    $stmt = false; 
                } elseif (strlen($password) < 6) {
                     set_message("Password must be at least 6 characters long for new patient.", "danger");
                     $stmt = false; 
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
                }
            }

            if (isset($stmt) && $stmt && mysqli_stmt_execute($stmt)) {
                set_message("Patient information " . ($user_id ? "updated" : "added") . " successfully.", "success");
                redirect($base_url . '/admin/manage_users.php');
            } elseif (isset($stmt) && $stmt === false) {
                // Message already set
            } elseif (isset($stmt) && $stmt) {
                set_message("Error: " . mysqli_stmt_error($stmt), "danger");
            }
            if (isset($stmt) && $stmt) mysqli_stmt_close($stmt);
        }
    }
}
?>

<h1><?php echo $user_id ? 'Edit Patient Details' : 'Add New Patient'; ?></h1>
<hr>
<form action="<?php echo $base_url; ?>/admin/edit_user.php<?php echo $user_id ? '?id='.$user_id : ''; ?>" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="password" class="form-label">Password <?php echo $user_id ? '<small class="text-muted">(Leave blank to keep current)</small>' : '<small class="text-danger">*Required</small>'; ?></label>
                <input type="password" class="form-control" id="password" name="password" <?php echo !$user_id ? 'required' : ''; ?>>
            </div>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?php echo $user_id ? 'Update Patient' : 'Add Patient'; ?></button>
    <a href="<?php echo $base_url; ?>/admin/manage_users.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once 'partials/admin_footer.php'; ?>