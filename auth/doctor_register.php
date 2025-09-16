<?php
require_once '../config/db.php';
$base_url = "/meditrack";

// Define constants for file upload
define('PROFILE_UPLOAD_DIR', __DIR__ . '/../uploads/doctors/');
define('PROFILE_MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('PROFILE_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    redirect($base_url . '/' . $_SESSION['role'] . '/index.php');
}

$name_val = ''; $email_val = ''; $specialization_val = ''; // For retaining form values on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_val = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email_val = mysqli_real_escape_string($conn, trim($_POST['email']));
    $specialization_val = mysqli_real_escape_string($conn, trim($_POST['specialization']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $profile_image_filename_to_save = null; // Initialize

    // Basic validation
    if (empty($name_val) || empty($email_val) || empty($specialization_val) || empty($password) || empty($confirm_password)) {
        set_message("All fields (except profile image) are required.", "danger");
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        set_message("Invalid email format.", "danger");
    } elseif (strlen($password) < 6) {
        set_message("Password must be at least 6 characters long.", "danger");
    } elseif ($password !== $confirm_password) {
        set_message("Passwords do not match.", "danger");
    } else {
        // Image Upload Handling
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['profile_image']['tmp_name'];
            $file_name_original = basename($_FILES['profile_image']['name']);
            $file_size = $_FILES['profile_image']['size'];
            $file_type = mime_content_type($file_tmp_path); // More reliable way to get MIME type
            $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

            if (!in_array($file_type, PROFILE_ALLOWED_TYPES)) {
                set_message("Invalid file type. Only JPG, PNG, GIF are allowed. (Detected: " . $file_type . ")", "danger");
            } elseif ($file_size > PROFILE_MAX_FILE_SIZE) {
                set_message("File size exceeds the limit of 2MB.", "danger");
            } else {
                if (!is_dir(PROFILE_UPLOAD_DIR)) {
                    mkdir(PROFILE_UPLOAD_DIR, 0775, true); // Create if not exists, make it writable
                }
                if (!is_writable(PROFILE_UPLOAD_DIR)) {
                    set_message("Upload directory is not writable. Please contact admin.", "danger");
                    error_log("Upload directory error (not writable): " . PROFILE_UPLOAD_DIR);
                } else {
                    $profile_image_filename_to_save = 'doc_' . uniqid('', true) . '.' . $file_ext;
                    $dest_path = PROFILE_UPLOAD_DIR . $profile_image_filename_to_save;
                    if (!move_uploaded_file($file_tmp_path, $dest_path)) {
                        set_message("Failed to move uploaded file. Error: " . $_FILES['profile_image']['error'], "danger");
                        $profile_image_filename_to_save = null; // Reset on failure
                    }
                }
            }
        } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['profile_image']['error'] != UPLOAD_ERR_OK) {
            set_message("Error uploading image: Code " . $_FILES['profile_image']['error'], "danger");
        }

        // Proceed with DB insertion only if no critical error message was set by image upload
        if (!isset($_SESSION['message']) || (isset($_SESSION['message']) && $_SESSION['message']['type'] !== 'danger') ) {
            $sql_check = "SELECT id FROM doctors WHERE email = ?";
            if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
                mysqli_stmt_bind_param($stmt_check, "s", $email_val);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    set_message("This email is already registered for a doctor.", "warning");
                    // If email exists and an image was uploaded, delete the temp uploaded image
                    if ($profile_image_filename_to_save && file_exists(PROFILE_UPLOAD_DIR . $profile_image_filename_to_save)) {
                        unlink(PROFILE_UPLOAD_DIR . $profile_image_filename_to_save);
                    }
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $initial_availability = json_encode([]);

                    $sql_insert = "INSERT INTO doctors (name, email, specialization, password, profile_image, role, available_slots_json) VALUES (?, ?, ?, ?, ?, 'doctor', ?)";
                    
                    if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                        mysqli_stmt_bind_param($stmt_insert, "ssssss", $name_val, $email_val, $specialization_val, $hashed_password, $profile_image_filename_to_save, $initial_availability);
                        if (mysqli_stmt_execute($stmt_insert)) {
                            set_message("Doctor registration successful! You can now login.", "success");
                            redirect($base_url . '/auth/login.php');
                        } else {
                            set_message("Database error: Could not register doctor. " . mysqli_stmt_error($stmt_insert), "danger");
                            if ($profile_image_filename_to_save && file_exists(PROFILE_UPLOAD_DIR . $profile_image_filename_to_save)) {
                                unlink(PROFILE_UPLOAD_DIR . $profile_image_filename_to_save);
                            }
                        }
                        mysqli_stmt_close($stmt_insert);
                    } else {
                         set_message("Database error: Could not prepare statement. " . mysqli_error($conn), "danger");
                    }
                }
                mysqli_stmt_close($stmt_check);
            } else {
                 set_message("Database error: Could not check email. " . mysqli_error($conn), "danger");
            }
        }
    }
}
require_once '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Doctor Registration</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo $base_url; ?>/auth/doctor_register.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name (e.g., Dr. John Doe)</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization_val); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text text-muted">Max 2MB. Allowed types: JPG, PNG, GIF.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Register as Doctor</button>
                    <p class="mt-3 text-center">Already have an account? <a href="<?php echo $base_url; ?>/auth/login.php">Login here</a>.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($conn) && $_SERVER["REQUEST_METHOD"] != "POST") { 
    mysqli_close($conn);
}
require_once '../includes/footer.php';
?>