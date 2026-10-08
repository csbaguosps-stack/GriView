/* License: by cs.baguosps@gmail.com */
/**
 * GriView - Frontend Interactivity Script
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });


    // Auto dismiss alert setelah 6 detik
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000);
    });

});
// Loading state & AJAX scraping handled in footer.php inline script
