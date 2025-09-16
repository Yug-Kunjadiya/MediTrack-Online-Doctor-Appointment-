<?php
require_once 'partials/user_header.php'; 
$base_url = "/meditrack";

if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    set_message("No doctor selected or invalid ID.", "danger");
    redirect($base_url . '/user/view_doctors.php');
}

$doctor_id = (int)$_GET['doctor_id'];
$user_id = $_SESSION['id'];

$stmt_doc = mysqli_prepare($conn, "SELECT id, name, specialization, profile_image, available_slots_json FROM doctors WHERE id = ?"); // Added profile_image
mysqli_stmt_bind_param($stmt_doc, "i", $doctor_id);
mysqli_stmt_execute($stmt_doc);
$result_doc = mysqli_stmt_get_result($stmt_doc);
$doctor = mysqli_fetch_assoc($result_doc);
mysqli_stmt_close($stmt_doc);

if (!$doctor) {
    set_message("Doctor not found.", "danger");
    redirect($base_url . '/user/view_doctors.php');
}

$doctor_profile_image_display_page = $base_url . '/assets/img/default_avatar.png';
if (!empty($doctor['profile_image']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doctor['profile_image'])) {
    $doctor_profile_image_display_page = $base_url . '/uploads/doctors/' . htmlspecialchars($doctor['profile_image']);
}


$available_slots_all_dates_raw = json_decode($doctor['available_slots_json'], true);
$doctor_master_availability = []; 

if (json_last_error() === JSON_ERROR_NONE && is_array($available_slots_all_dates_raw)) {
    foreach ($available_slots_all_dates_raw as $date_key => $time_slots_array) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_key) && is_array($time_slots_array)) {
            $normalized_slots_for_date = [];
            foreach ($time_slots_array as $time_val) {
                if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time_val)) {
                    $normalized_slots_for_date[] = $time_val;
                }
            }
            if (!empty($normalized_slots_for_date)) {
                sort($normalized_slots_for_date);
                $doctor_master_availability[$date_key] = array_unique($normalized_slots_for_date);
            }
        }
    }
}
ksort($doctor_master_availability);

$today_date_string = date("Y-m-d");
$upcoming_master_availability_for_js = [];
foreach ($doctor_master_availability as $date_str => $slots_array) {
    if ($date_str >= $today_date_string) {
        $upcoming_master_availability_for_js[$date_str] = $slots_array;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date_from_form = mysqli_real_escape_string($conn, $_POST['appointment_date']); 
    $appointment_time_from_form_raw = mysqli_real_escape_string($conn, $_POST['appointment_time']);

    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $appointment_time_from_form_raw)) {
        set_message("Invalid time format submitted. Please select a valid time.", "danger");
        redirect($_SERVER['REQUEST_URI']);
    }
    $appointment_time_for_db = $appointment_time_from_form_raw . ":00"; 

    $is_slot_in_master_list = false;
    if (isset($upcoming_master_availability_for_js[$appointment_date_from_form]) &&
        in_array($appointment_time_from_form_raw, $upcoming_master_availability_for_js[$appointment_date_from_form])) {
        $is_slot_in_master_list = true;
    }

    if ($is_slot_in_master_list) {
        $check_booked_sql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('pending', 'approved')";
        $stmt_check_booked = mysqli_prepare($conn, $check_booked_sql);
        mysqli_stmt_bind_param($stmt_check_booked, "iss", $doctor_id, $appointment_date_from_form, $appointment_time_for_db);
        mysqli_stmt_execute($stmt_check_booked);
        mysqli_stmt_store_result($stmt_check_booked);
        $is_slot_already_taken = (mysqli_stmt_num_rows($stmt_check_booked) > 0);
        mysqli_stmt_close($stmt_check_booked);

        if (!$is_slot_already_taken) {
            $insert_sql = "INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt_insert = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt_insert, "iiss", $user_id, $doctor_id, $appointment_date_from_form, $appointment_time_for_db);
            if (mysqli_stmt_execute($stmt_insert)) {
                set_message("Appointment requested for " . date("g:i A", strtotime($appointment_time_from_form_raw)) . " on " . date("M j, Y", strtotime($appointment_date_from_form)) . ". You'll be notified once approved.", "success");
                redirect($base_url . '/user/my_appointments.php');
            } else {
                set_message("Error booking appointment: " . mysqli_stmt_error($stmt_insert), "danger");
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            set_message("The selected time slot (" . date("g:i A", strtotime($appointment_time_from_form_raw)) . ") has just been booked. Please choose another.", "danger");
        }
    } else {
        set_message("The selected time slot is not available in the doctor's schedule. Please refresh and choose another.", "danger");
    }
}

$booked_slots_db_query = "SELECT appointment_date, appointment_time FROM appointments WHERE doctor_id = ? AND status IN ('pending', 'approved')";
$stmt_booked_db = mysqli_prepare($conn, $booked_slots_db_query);
mysqli_stmt_bind_param($stmt_booked_db, "i", $doctor_id);
mysqli_stmt_execute($stmt_booked_db);
$result_booked_db = mysqli_stmt_get_result($stmt_booked_db);

$already_booked_slots_for_js = []; 
while ($row = mysqli_fetch_assoc($result_booked_db)) {
    $time_hh_mm = date("H:i", strtotime($row['appointment_time']));
    $already_booked_slots_for_js[$row['appointment_date']][] = $time_hh_mm;
}
mysqli_stmt_close($stmt_booked_db);
?>

<div class="row">
    <div class="col-md-4 text-center">
        <img src="<?php echo $doctor_profile_image_display_page; ?>?t=<?php echo time(); ?>" alt=" <?php echo htmlspecialchars($doctor['name']); ?>" class="img-fluid rounded-circle mb-3" style="width: 180px; height: 180px; object-fit: cover; border: 3px solid #007bff;">
        <h3><?php echo htmlspecialchars($doctor['name']); ?></h3>
        <p class="lead text-muted"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
        <hr>
        <h4>Average Rating:</h4>
        <div id="doctorProfileAverageRating_<?php echo $doctor['id']; ?>" class="h3">
            <span class="text-warning placeholder col-6"></span> 
        </div>
        <small id="doctorProfileRatingCount_<?php echo $doctor['id']; ?>" class="placeholder col-4"></small>
    </div>
    <div class="col-md-8">
        <h1><i class="bi bi-calendar-plus"></i> Book Appointment</h1>
        <p class="lead">Select an available date and time slot below.</p>
        <hr>

        <?php if (empty($upcoming_master_availability_for_js)): ?>
            <div class="alert alert-warning"> <?php echo htmlspecialchars($doctor['name']); ?> currently has no available slots published. Please check back later.</div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="bookingForm" action="<?php echo $base_url; ?>/user/book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" method="POST">
                    <input type="hidden" name="book_appointment" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="appointment_date_select" class="form-label fw-bold">Select Date:</label>
                            <select class="form-select form-select-lg" id="appointment_date_select" name="appointment_date" required>
                                <option value="">-- Select a Date --</option>
                                <?php foreach ($upcoming_master_availability_for_js as $date_val => $slots_val_array): ?>
                                    <?php if (!empty($slots_val_array)): ?>
                                    <option value="<?php echo htmlspecialchars($date_val); ?>"><?php echo date("l, F j, Y", strtotime($date_val)); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="appointment_time_select" class="form-label fw-bold">Select Time Slot:</label>
                            <select class="form-select form-select-lg" id="appointment_time_select" name="appointment_time" required disabled>
                                <option value="">-- Select a Date First --</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 mt-3" id="submitBookingBtn" disabled><i class="bi bi-check-circle-fill"></i> Request Appointment</button>
                    <a href="<?php echo $base_url; ?>/user/view_doctors.php" class="btn btn-outline-secondary w-100 mt-2">Cancel and Go Back</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h3 class="mb-3"><i class="bi bi-chat-square-quote-fill"></i> Patient Reviews for  <?php echo htmlspecialchars($doctor['name']); ?></h3>
        <div id="doctorReviewsContainer_<?php echo $doctor['id']; ?>" class="list-group">
            <div class="text-center p-3" id="loadingReviewsPlaceholder_<?php echo $doctor['id']; ?>">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading reviews...</span>
                </div>
                <p class="mt-2">Loading reviews...</p>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateSelectEl = document.getElementById('appointment_date_select');
    const timeSelectEl = document.getElementById('appointment_time_select');
    const submitBtnEl = document.getElementById('submitBookingBtn');
    
    const doctorUpcomingMasterSlots = <?php echo json_encode($upcoming_master_availability_for_js); ?>;
    const alreadyTakenSlots = <?php echo json_encode($already_booked_slots_for_js); ?>;

    dateSelectEl.addEventListener('change', function() {
        const selectedDateString = this.value;
        timeSelectEl.innerHTML = '<option value="">-- Select a Time --</option>'; 
        timeSelectEl.disabled = true;
        submitBtnEl.disabled = true;

        if (selectedDateString && doctorUpcomingMasterSlots[selectedDateString]) {
            const masterSlotsForThisDate = doctorUpcomingMasterSlots[selectedDateString]; 
            const takenSlotsForThisDate = (alreadyTakenSlots[selectedDateString]) ? alreadyTakenSlots[selectedDateString] : [];
            
            let hasTrulyAvailableSlots = false;
            masterSlotsForThisDate.forEach(function(masterSlotTime) { 
                if (!takenSlotsForThisDate.includes(masterSlotTime)) {
                    const option = document.createElement('option');
                    option.value = masterSlotTime; 
                    const tempDateForDisplay = new Date(selectedDateString + 'T' + masterSlotTime);
                    option.textContent = tempDateForDisplay.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                    timeSelectEl.appendChild(option);
                    hasTrulyAvailableSlots = true;
                }
            });

            if (hasTrulyAvailableSlots) {
                timeSelectEl.disabled = false;
            } else {
                 timeSelectEl.innerHTML = '<option value="">-- No available slots for this date --</option>';
            }
        }
    });

    timeSelectEl.addEventListener('change', function() {
        if (this.value) { 
            submitBtnEl.disabled = false;
        } else {
            submitBtnEl.disabled = true;
        }
    });

    const doctorIdForReview = <?php echo $doctor_id; ?>;
    const avgRatingDiv = document.getElementById('doctorProfileAverageRating_' + doctorIdForReview);
    const ratingCountSpan = document.getElementById('doctorProfileRatingCount_' + doctorIdForReview);
    const reviewsContainer = document.getElementById('doctorReviewsContainer_' + doctorIdForReview);
    const loadingPlaceholder = document.getElementById('loadingReviewsPlaceholder_' + doctorIdForReview);

    function displayStarRating(rating, forAverage = false) {
        let starsHTML = '';
        const roundedRating = Math.round(rating * 2) / 2; 
        for (let i = 1; i <= 5; i++) {
            if (i <= roundedRating) {
                starsHTML += '<i class="bi bi-star-fill text-warning"></i>';
            } else if (i - 0.5 === roundedRating) {
                starsHTML += '<i class="bi bi-star-half text-warning"></i>';
            } else {
                starsHTML += '<i class="bi bi-star text-warning"></i>';
            }
        }
        return starsHTML + (forAverage && rating > 0 ? ' <span class="fw-bold">' + parseFloat(rating).toFixed(1) + '</span>' : '');
    }

    function loadDoctorReviewsAndRating(docId) {
        fetch(`<?php echo $base_url; ?>/user/get_doctor_reviews.php?doctor_id=${docId}`)
            .then(response => response.json())
            .then(data => {
                if(loadingPlaceholder) loadingPlaceholder.style.display = 'none'; 
                if(reviewsContainer) reviewsContainer.innerHTML = ''; 

                if (data.success) {
                    if(avgRatingDiv) avgRatingDiv.innerHTML = displayStarRating(data.average_rating, true);
                    if(ratingCountSpan) ratingCountSpan.textContent = `(${data.total_reviews} review${data.total_reviews !== 1 ? 's' : ''})`;

                    if (data.reviews.length > 0) {
                        data.reviews.forEach(review => {
                            const reviewElement = `
                                <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 shadow-sm">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-person-fill"></i> ${review.user_name}</h6>
                                        <small class="text-muted">${review.created_at}</small>
                                    </div>
                                    <div class="mb-1">${displayStarRating(review.rating)}</div>
                                    <p class="mb-1 fst-italic">"${review.comment || 'No comment provided.'}"</p>
                                </div>
                            `;
                            if(reviewsContainer) reviewsContainer.insertAdjacentHTML('beforeend', reviewElement);
                        });
                    } else {
                        if(reviewsContainer) reviewsContainer.innerHTML = '<p class="text-muted text-center p-3">No reviews yet for this doctor.</p>';
                    }
                } else {
                    if(avgRatingDiv) avgRatingDiv.innerHTML = displayStarRating(0) + ' N/A';
                    if(ratingCountSpan) ratingCountSpan.textContent = '(0 reviews)';
                    if(reviewsContainer) reviewsContainer.innerHTML = `<p class="text-danger text-center p-3">Could not load reviews: ${data.message || 'Unknown error'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error fetching reviews:', error);
                if(loadingPlaceholder) loadingPlaceholder.style.display = 'none';
                if(reviewsContainer) reviewsContainer.innerHTML = '<p class="text-danger text-center p-3">Error loading reviews. Please try again later.</p>';
            });
    }
    loadDoctorReviewsAndRating(doctorIdForReview); 
});
</script>

<?php 
require_once 'partials/user_footer.php'; 
?>