<?php
// auth/register.php
require_once '../config/db.php'; // For $conn, set_message, redirect, session_start
$base_url = "/meditrack";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    redirect($base_url . '/' . $_SESSION['role'] . '/index.php');
}

$name_val = ''; $email_val = ''; // To retain values on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_val = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email_val = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name_val) || empty($email_val) || empty($password) || empty($confirm_password)) {
        set_message("All fields are required.", "danger");
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        set_message("Invalid email format.", "danger");
    } elseif (strlen($password) < 6) {
        set_message("Password must be at least 6 characters long.", "danger");
    } elseif ($password !== $confirm_password) {
        set_message("Passwords do not match.", "danger");
    } else {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "s", $email_val);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                set_message("This email is already registered as a patient.", "warning");
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_insert = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
                if ($stmt_insert = mysqli_prepare($conn, $sql_insert)) {
                    mysqli_stmt_bind_param($stmt_insert, "sss", $name_val, $email_val, $hashed_password);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        set_message("Registration successful! You can now login.", "success");
                        redirect($base_url . '/auth/login.php');
                    } else {
                        set_message("Oops! Something went wrong. Please try again later.", "danger");
                    }
                    mysqli_stmt_close($stmt_insert);
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    }
    // No mysqli_close($conn) here if we redirect, as messages need session
}

// Include the global header which links style.css (providing body padding-top)
require_once '../includes/header.php'; 
?>

<div class="row justify-content-center mt-4">  
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Patient Registration</h4>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo $base_url; ?>/auth/register.php" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?php echo htmlspecialchars($name_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">Register</button>
                    <p class="mt-3 text-center small">Already have an account? <a href="<?php echo $base_url; ?>/auth/login.php">Login here</a>.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($conn) && $_SERVER["REQUEST_METHOD"] != "POST") { // Close if not POST action that might redirect
    mysqli_close($conn);
}
require_once '../includes/footer.php'; 
?>