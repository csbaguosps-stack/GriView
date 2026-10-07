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

    // Handle tombol Balas Review Modal
    const replyButtons = document.querySelectorAll('.btn-reply-trigger');
    const replyModal = document.getElementById('modalReplyReview');
    const replyIdInput = document.getElementById('replyReviewId');
    const replyAuthorEl = document.getElementById('replyReviewAuthor');
    const replyRatingEl = document.getElementById('replyReviewRating');
    const replyTextEl = document.getElementById('replyReviewText');
    const replyTextInput = document.getElementById('replyTextInput');

    replyButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const author = this.dataset.author;
            const rating = parseInt(this.dataset.rating) || 5;
            const text = this.dataset.text || '(Tidak ada teks ulasan)';
            const currentReply = this.dataset.reply || '';

            if (replyIdInput) replyIdInput.value = id;
            if (replyAuthorEl) replyAuthorEl.textContent = author;
            if (replyTextEl) replyTextEl.textContent = '"' + text + '"';
            if (replyTextInput) replyTextInput.value = currentReply;

            if (replyRatingEl) {
                let stars = '';
                for (let i = 0; i < rating; i++) stars += '★';
                for (let i = rating; i < 5; i++) stars += '☆';
                replyRatingEl.textContent = stars + ' (' + rating + '/5)';
            }
        });
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
