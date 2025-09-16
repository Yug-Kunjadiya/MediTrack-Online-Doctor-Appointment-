<?php
// auth/admin_login.php
require_once '../config/db.php';
$base_url = "/meditrack"; 

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'admin') {
        redirect($base_url . '/admin/index.php');
    } else {
        redirect($base_url . '/' . $_SESSION['role'] . '/index.php');
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, email, password FROM admin WHERE email = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $hashed_password);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $db_email;
                        $_SESSION['role'] = 'admin';

                        set_message("Admin login successful!", "success");
                        redirect($base_url . '/admin/index.php');
                    } else {
                        set_message("Invalid email or password.", "danger");
                    }
                }
            } else {
                set_message("No admin account found with that email.", "danger");
            }
        } else {
            set_message("Oops! Something went wrong. Please try again later.", "danger");
        }
        mysqli_stmt_close($stmt);
    }
    // No redirect here if login fails, so connection will be closed by footer
}

require_once '../includes/header.php'; 
?>

<div class="row justify-content-center mt-4">  
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Admin Login</h4>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo $base_url; ?>/auth/admin_login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">Login</button>
                     <p class="mt-3 text-center small">Not an admin? <a href="<?php echo $base_url; ?>/auth/login.php">User/Doctor Login</a>.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($conn) && mysqli_ping($conn)) { // Close connection if it's still open
    mysqli_close($conn);
}
require_once '../includes/footer.php'; 
?>