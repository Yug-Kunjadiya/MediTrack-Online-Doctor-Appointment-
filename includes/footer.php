<?php 
// Ensure $base_url is defined
if (!isset($base_url)) $base_url = "/meditrack"; 
?>
</div> <!-- This closes the .container.main-content-area started in includes/header.php -->
      <!-- This div should ONLY be closed here if it's a non-panel page. -->
      <!-- Panel pages will close their own main-content and dashboard-wrapper divs in their specific footers. -->

<footer class="footer mt-auto py-4 bg-dark text-light">
    <div class="container text-center">
        <p class="mb-0">© <?php echo date("Y"); ?> MediTrack. All Rights Reserved.</p>
        <p class="mb-0 small">Designed for better health access.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base_url; ?>/assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>