<?php
require_once '../config/db.php'; // Includes session_start()
check_login('user'); // Ensure only logged-in users can submit reviews

$base_url = "/meditrack";
$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['appointment_id']) || !isset($_POST['doctor_id']) || !isset($_POST['rating'])) {
        set_message("Missing required review data.", "danger");
        // Redirect back to where they came from, or a specific page.
        // Using HTTP_REFERER can be tricky, better to redirect to a known page.
        redirect($base_url . '/user/my_appointments.php');
    }

    $appointment_id = (int)$_POST['appointment_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $rating = (int)$_POST['rating'];
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        set_message("Invalid rating value. Must be between 1 and 5.", "danger");
        redirect($base_url . '/user/my_appointments.php'); // Or back to appointment details
    }

    // 1. Verify that the appointment belongs to the current user and is 'completed'.
    $sql_verify_app = "SELECT id FROM appointments WHERE id = ? AND user_id = ? AND status = 'completed'";
    $stmt_verify = mysqli_prepare($conn, $sql_verify_app);
    mysqli_stmt_bind_param($stmt_verify, "ii", $appointment_id, $user_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);

    if (mysqli_stmt_num_rows($stmt_verify) == 0) {
        set_message("You can only review your own completed appointments.", "warning");
        mysqli_stmt_close($stmt_verify);
        redirect($base_url . '/user/my_appointments.php');
    }
    mysqli_stmt_close($stmt_verify);

    // 2. Check if a review for this appointment already exists (due to UNIQUE KEY on appointment_id)
    $sql_check_existing = "SELECT id FROM reviews WHERE appointment_id = ?";
    $stmt_check_existing = mysqli_prepare($conn, $sql_check_existing);
    mysqli_stmt_bind_param($stmt_check_existing, "i", $appointment_id);
    mysqli_stmt_execute($stmt_check_existing);
    mysqli_stmt_store_result($stmt_check_existing);

    if (mysqli_stmt_num_rows($stmt_check_existing) > 0) {
        set_message("You have already submitted a review for this appointment.", "info");
        mysqli_stmt_close($stmt_check_existing);
        redirect($base_url . '/user/my_appointments.php');
    }
    mysqli_stmt_close($stmt_check_existing);

    // 3. Insert the review
    $sql_insert_review = "INSERT INTO reviews (appointment_id, user_id, doctor_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_insert = mysqli_prepare($conn, $sql_insert_review);
    mysqli_stmt_bind_param($stmt_insert, "iiiis", $appointment_id, $user_id, $doctor_id, $rating, $comment);

    if (mysqli_stmt_execute($stmt_insert)) {
        set_message("Thank you for your review!", "success");
    } else {
        set_message("Failed to submit review. Error: " . mysqli_stmt_error($stmt_insert), "danger");
    }
    mysqli_stmt_close($stmt_insert);
    mysqli_close($conn);
    redirect($base_url . '/user/my_appointments.php');

} else {
    // If accessed directly via GET, redirect
    set_message("Invalid request method.", "danger");
    redirect($base_url . '/user/index.php');
}
?>