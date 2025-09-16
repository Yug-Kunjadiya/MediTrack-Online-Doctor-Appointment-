// General site-wide JavaScript can go here.
// For example, more complex form validations or UI interactions.

document.addEventListener('DOMContentLoaded', function() {
    // Example: Add active class to sidebar nav links based on current URL
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.admin-sidebar .nav-link, .doctor-sidebar .nav-link, .user-sidebar .nav-link');
    
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});