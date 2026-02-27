<?php
require_once '../config/db.php'; 
$base_url = "/meditrack";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['role'])) {
        redirect($base_url . '/' . $_SESSION['role'] . '/index.php');
    } else {
        redirect($base_url . '/index.php');
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $logged_in = false;

    // --- Try users (patient) table first ---
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            $id = $name = $db_email = $hashed_password = $db_role = null;
            mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $hashed_password, $db_role);
            mysqli_stmt_fetch($stmt);
            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['id']       = $id;
                $_SESSION['name']     = $name;
                $_SESSION['email']    = $db_email;
                $_SESSION['role']     = $db_role;
                set_message("Logged in successfully as Patient!", "success");
                $logged_in = true;
                mysqli_stmt_close($stmt);
                redirect($base_url . '/user/index.php');
            }
        }
        mysqli_stmt_close($stmt);
    }

    // --- Try doctors table ---
    if (!$logged_in) {
        $stmt2 = mysqli_prepare($conn, "SELECT id, name, email, password, role, specialization, profile_image FROM doctors WHERE email = ?");
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, "s", $email);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);
            if (mysqli_stmt_num_rows($stmt2) == 1) {
                $id = $name = $db_email = $hashed_password = $db_role = $specialization = $profile_image = null;
                mysqli_stmt_bind_result($stmt2, $id, $name, $db_email, $hashed_password, $db_role, $specialization, $profile_image);
                mysqli_stmt_fetch($stmt2);
                if (password_verify($password, $hashed_password)) {
                    session_regenerate_id(true);
                    $_SESSION['loggedin']       = true;
                    $_SESSION['id']             = $id;
                    $_SESSION['name']           = $name;
                    $_SESSION['email']          = $db_email;
                    $_SESSION['role']           = $db_role;
                    $_SESSION['specialization'] = $specialization;
                    $_SESSION['profile_image']  = $profile_image;
                    set_message("Logged in successfully as Doctor!", "success");
                    $logged_in = true;
                    mysqli_stmt_close($stmt2);
                    redirect($base_url . '/doctor/index.php');
                }
            }
            mysqli_stmt_close($stmt2);
        }
    }

    if (!$logged_in) {
        set_message("Invalid email or password. Please check your credentials and try again.", "danger");
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white"><h4 class="mb-0">User/Doctor Login</h4></div>
            <div class="card-body">
                <form action="<?php echo $base_url; ?>/auth/login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
                    <p class="mt-3 text-center">Don't have an account? <a href="<?php echo $base_url; ?>/auth/register.php">Register as Patient</a> or <a href="<?php echo $base_url; ?>/auth/doctor_register.php">Register as Doctor</a>.</p>
                    <p class="mt-1 text-center">Admin? <a href="<?php echo $base_url; ?>/auth/admin_login.php">Admin Login</a></p>
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