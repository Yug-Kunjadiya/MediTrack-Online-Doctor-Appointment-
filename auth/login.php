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
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $role_from_form = mysqli_real_escape_string($conn, $_POST['role']);

    $sql = "";
    $is_doctor_login = false;

    if ($role_from_form == 'user') {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    } elseif ($role_from_form == 'doctor') {
        $sql = "SELECT id, name, email, password, role, specialization, profile_image FROM doctors WHERE email = ?"; // Added profile_image
        $is_doctor_login = true;
    } else {
        set_message("Invalid role selected.", "danger");
        redirect($base_url . '/auth/login.php');
    }

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                $id = null; $name = null; $db_email = null; $hashed_password = null; $db_role = null;
                $specialization = null; $profile_image = null; // For doctor

                if ($is_doctor_login) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $hashed_password, $db_role, $specialization, $profile_image);
                } else {
                    mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $hashed_password, $db_role);
                }
                
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_regenerate_id(true);

                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $db_email;
                        $_SESSION['role'] = $db_role;

                        if ($db_role == 'doctor') {
                            $_SESSION['specialization'] = $specialization;
                            $_SESSION['profile_image'] = $profile_image; // Store profile image in session
                        }

                        set_message("Logged in successfully as " . ucfirst($db_role) . "!", "success");
                        redirect($base_url . '/' . $db_role . '/index.php');
                    } else {
                        set_message("Invalid email or password.", "danger");
                    }
                } else {
                     set_message("Error fetching user data.", "danger");
                }
            } else {
                set_message("No account found with that email for the selected role.", "danger");
            }
        } else {
            set_message("Oops! Login query execution failed. " . mysqli_stmt_error($stmt), "danger");
        }
        mysqli_stmt_close($stmt);
    } else {
        set_message("Oops! Login statement preparation failed. " . mysqli_error($conn), "danger");
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
                    <div class="mb-3">
                        <label for="role" class="form-label">Login as:</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : ''; ?>>Patient</option>
                            <option value="doctor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                        </select>
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