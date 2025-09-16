<?php
require_once 'config/db.php'; 
require_once 'includes/header.php'; 
$base_url = "/meditrack";

// This number should ideally come from a config or admin setting
if(!defined('ADMIN_SUPPORT_PHONE_NUMBER')) define('ADMIN_SUPPORT_PHONE_NUMBER', '+11234567890'); 
if(!defined('ADMIN_SUPPORT_PHONE_DISPLAY')) define('ADMIN_SUPPORT_PHONE_DISPLAY', '+1 (123) 456-7890'); 
?>

<!-- Hero Section -->
<div class="hero-section p-5 mb-4 rounded-3 text-center">
    <div class="container-fluid py-5">
        <i class="bi bi-capsule-pill display-1 text-white mb-3"></i>
        <h1 class="display-4 fw-bold">Your Health, Our Priority</h1>
        <p class="col-lg-8 mx-auto fs-5 mb-4">
            Seamlessly book doctor appointments online. Find trusted specialists, check their availability, and manage your health journey with ease.
        </p>
        <?php if (!isset($_SESSION['loggedin'])): ?>
            <a href="<?php echo $base_url; ?>/auth/login.php" class="btn btn-primary btn-lg px-4 me-2" type="button"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <a href="<?php echo $base_url; ?>/auth/register.php" class="btn btn-secondary btn-lg px-4" type="button"><i class="bi bi-person-plus-fill"></i> Patient Sign Up</a>
        <?php else: ?>
             <a href="<?php echo $base_url; ?>/<?php echo htmlspecialchars($_SESSION['role']); ?>/index.php" class="btn btn-success btn-lg px-4" type="button"><i class="bi bi-speedometer2"></i> Go to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<!-- Feature Blocks -->
<div class="row align-items-md-stretch text-center mb-5">
    <div class="col-md-6 mb-4">
        <div class="h-100 p-5 bg-primary-soft rounded-3 feature-block"> 
            <i class="bi bi-people-fill display-3 text-primary mb-3"></i>
            <h2>For Patients</h2>
            <p>Find specialist doctors, view their real-time schedules, and book appointments in just a few clicks. Your health, simplified.</p>
            <a href="<?php echo $base_url; ?>/user/view_doctors.php" class="btn btn-primary" type="button">Find a Doctor <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="h-100 p-5 bg-secondary-soft rounded-3 feature-block"> 
             <i class="bi bi-clipboard2-pulse-fill display-3 text-secondary mb-3"></i>
            <h2>For Doctors</h2>
            <p>Streamline your practice. Manage appointments, set your availability, and connect with patients through our intuitive platform.</p>
            <a href="<?php echo $base_url; ?>/auth/doctor_register.php" class="btn btn-success" type="button">Join Our Network <i class="bi bi-arrow-right-short"></i></a>
        </div>
    </div>
</div>

<!-- How It Works Section (Example) -->
<div class="py-5 text-center">
    <h2 class="display-6 fw-bold mb-4">How MediTrack Works</h2>
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-soft h-100">
                <div class="card-body">
                    <i class="bi bi-search-heart display-4 text-primary mb-3"></i>
                    <h5 class="card-title">1. Find Your Doctor</h5>
                    <p class="card-text">Search by specialty, name, or location to find the right healthcare professional for you.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-soft h-100">
                <div class="card-body">
                    <i class="bi bi-calendar-check display-4 text-primary mb-3"></i>
                    <h5 class="card-title">2. Book an Appointment</h5>
                    <p class="card-text">View available time slots and book your appointment instantly online, anytime, anywhere.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-soft h-100">
                <div class="card-body">
                    <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                    <h5 class="card-title">3. Get Quality Care</h5>
                    <p class="card-text">Connect with experienced doctors and manage your health records securely in one place.</p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Support Call Section -->
<div class="row mt-4 mb-5">
    <div class="col-12 text-center">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-4 p-md-5">
                <i class="bi bi-headset display-4 text-info mb-3"></i>
                <h3 class="card-title">Need Help or Support?</h3>
                <p class="card-text lead text-muted">Our admin team is here to assist you. Click the button below to call our support line.</p>
                <a href="tel:<?php echo htmlspecialchars(ADMIN_SUPPORT_PHONE_NUMBER); ?>" class="btn btn-info btn-lg text-dark mt-2">
                    <i class="bi bi-telephone-outbound-fill"></i> Call Support: <?php echo htmlspecialchars(ADMIN_SUPPORT_PHONE_DISPLAY); ?>
                </a>
                <p class="mt-2 text-muted small">Standard call rates may apply.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>