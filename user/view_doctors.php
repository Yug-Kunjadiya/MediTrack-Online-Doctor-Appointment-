<?php
require_once 'partials/user_header.php'; 
$base_url = "/meditrack";

// ... (PHP logic for fetching doctors - no changes needed here from previous full version) ...
$specialization_filter = isset($_GET['specialization']) ? mysqli_real_escape_string($conn, $_GET['specialization']) : '';
$doctor_name_filter = isset($_GET['doctor_name']) ? mysqli_real_escape_string($conn, trim($_GET['doctor_name'])) : '';

$sql = "SELECT id, name, specialization, profile_image FROM doctors"; 
$conditions = [];
$params = [];
$types = "";
if (!empty($specialization_filter)) { $conditions[] = "specialization = ?"; $params[] = $specialization_filter; $types .= "s"; }
if (!empty($doctor_name_filter)) { $conditions[] = "name LIKE ?"; $params[] = "%" . $doctor_name_filter . "%"; $types .= "s"; }
if (count($conditions) > 0) { $sql .= " WHERE " . implode(" AND ", $conditions); }
$sql .= " ORDER BY name ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$doctors_result = mysqli_stmt_get_result($stmt);
$spec_result = mysqli_query($conn, "SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization ASC");
$specializations_array = [];
while($spec_row = mysqli_fetch_assoc($spec_result)){ $specializations_array[] = $spec_row['specialization']; }
?>

<div class="page-header">
    <h1 class="h2"><i class="bi bi-search-heart me-2"></i>Find a Doctor</h1>
</div>

<form method="GET" action="<?php echo $base_url; ?>/user/view_doctors.php" class="mb-4 p-3 bg-white rounded shadow-sm">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label for="doctor_name" class="form-label">Doctor Name</label>
            <input type="text" class="form-control" id="doctor_name" name="doctor_name" value="<?php echo htmlspecialchars($doctor_name_filter); ?>" placeholder="e.g., Dr. Smith">
        </div>
        <div class="col-md-5">
            <label for="specialization" class="form-label">Specialization</label>
            <select name="specialization" id="specialization" class="form-select">
                <option value="">All Specializations</option>
                <?php foreach ($specializations_array as $spec): ?>
                <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialization_filter == $spec) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($spec); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
    </div>
</form>

<div class="find-doctor-page"> 
    <?php if ($doctors_result && mysqli_num_rows($doctors_result) > 0): ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3"> 
        <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
        <div class="col">
            <div class="card h-100 shadow-sm doctor-card"> 
                <div class="card-body d-flex flex-column text-center align-items-center">
                    <?php
                    $doc_list_image_path = $base_url . '/assets/img/default_avatar.png'; 
                    if (!empty($doctor['profile_image']) && file_exists(__DIR__ . '/../uploads/doctors/' . $doctor['profile_image'])) {
                        $doc_list_image_path = $base_url . '/uploads/doctors/' . htmlspecialchars($doctor['profile_image']);
                    }
                    ?>
                    <img src="<?php echo $doc_list_image_path; ?>?t=<?php echo time(); ?>" class="rounded-circle doctor-card-img mb-2" alt=" <?php echo htmlspecialchars($doctor['name']); ?>"> 
                    
                    <h5 class="card-title text-primary mt-1"><?php echo htmlspecialchars($doctor['name']); ?></h5> 
                    <p class="card-text text-muted mb-1"> 
                        <i class="bi bi-award-fill text-success me-1"></i> <!-- Changed icon -->
                        <?php echo htmlspecialchars($doctor['specialization']); ?>
                    </p>
                    <div id="avgRatingDoctorList_<?php echo $doctor['id']; ?>" class="mb-2 doctor-list-rating">
                        <small class="text-muted">Loading rating...</small> 
                    </div>
                    <div class="mt-auto w-100">
                         <a href="<?php echo $base_url; ?>/user/book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-success btn-sm w-100"><i class="bi bi-calendar-plus"></i> View & Book</a> 
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info mt-3">No doctors found matching your criteria. Try broadening your search.</div>
    <?php endif; ?>
</div>

<?php 
if($stmt) mysqli_stmt_close($stmt); 
?>

<script>
// JavaScript for fetching ratings (from previous version, assumed correct)
document.addEventListener('DOMContentLoaded', function() {
    function displayStarRatingList(rating) {
        let starsHTML = '';
        if (rating === null || rating === 0 || isNaN(rating)) { return '<small class="text-muted">(No ratings yet)</small>'; }
        const roundedRating = Math.round(rating * 2) / 2; 
        for (let i = 1; i <= 5; i++) {
            if (i <= roundedRating) { starsHTML += '<i class="bi bi-star-fill text-warning"></i>'; } 
            else if (i - 0.5 === roundedRating) { starsHTML += '<i class="bi bi-star-half text-warning"></i>'; } 
            else { starsHTML += '<i class="bi bi-star text-warning"></i>'; }
        }
        return starsHTML + ' <small>(' + parseFloat(rating).toFixed(1) + ')</small>';
    }
    const doctorRatingDivs = document.querySelectorAll('.doctor-list-rating');
    doctorRatingDivs.forEach(div => {
        const doctorId = div.id.split('_')[1]; 
        if (doctorId) {
            fetch(`<?php echo $base_url; ?>/user/get_doctor_reviews.php?doctor_id=${doctorId}`)
                .then(response => { if (!response.ok) { throw new Error('Network response error'); } return response.json(); })
                .then(data => {
                    if (data.success) { div.innerHTML = displayStarRatingList(data.average_rating); } 
                    else { div.innerHTML = '<small class="text-muted">(Rating N/A)</small>'; }
                })
                .catch(error => { div.innerHTML = '<small class="text-danger">(Error rating)</small>'; });
        }
    });
});
</script>

<?php require_once 'partials/user_footer.php'; ?>