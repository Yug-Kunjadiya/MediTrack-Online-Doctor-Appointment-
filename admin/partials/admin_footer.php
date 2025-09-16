<?php 
// admin/partials/admin_footer.php

// Define $base_url if not already set (though it should be by admin_header.php)
if (!isset($base_url)) $base_url = "/meditrack"; 

// Check if $conn is set, is an object, and the connection is still active before closing
if (isset($conn) && is_object($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}
?>
        </main> <!-- /dashboard-content -->
    </div> <!-- /dashboard-wrapper -->

<!-- 
    The global site footer (from includes/footer.php) is usually NOT included 
    again in panel footers if the panel footer handles script includes and body/html closure.
    If you have a separate global site footer for copyright etc., you might include it here,
    but typically panel footers are self-contained for panel assets.
-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base_url; ?>/assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>