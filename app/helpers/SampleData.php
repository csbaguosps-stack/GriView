<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Sample Data Generator untuk Demo Google Maps Reviews
 * Menyediakan data ulasan realistis bertema Optik & Kacamata (Optik Winsee)
 */
class SampleData {
    public static function getReviews() {
        return [
            [
                'google_review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUN6M3RPSkhREAE',
                'author_name' => 'Dimas Prasetyo',
                'author_photo_url' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80',
                'author_url' => 'https://maps.google.com/?cid=1001',
                'rating' => 5,
                'review_text' => 'Pelayanan Optik Winsee sangat memuaskan! Pemeriksaan mata teliti sekali menggunakan alat refraksi digital terbaru. Pilihan frame kacamata kekinian dan harganya sangat terjangkau. Pengerjaan lensa photocromic cuma 1 jam langsung jadi!',
                'review_time' => '2026-10-02 14:15:00',
                'sentiment' => 'positive',
                'is_local_guide' => 1,
                'review_language' => 'id',
                'review_photo_count' => 3,
                'reviewer_photo_count' => 12,
                'owner_reply' => 'Halo Kak Dimas! Terima kasih banyak atas ulasan bintang 5 dan kepercayaannya kepada Optik Winsee. Senang kacamata barunya cocok dan nyaman dipakai!',
                'owner_reply_time' => '2026-10-02 16:30:00'
            ],
            [
                'google_review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUN6NDVPMkhnEAE',
                'author_name' => 'Siti Nurhaliza',
                'author_photo_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80',
                'author_url' => 'https://maps.google.com/?cid=1002',
                'rating' => 5,
                'review_text' => 'Staf optik sangat ramah dan sabar membantu memilihkan bentuk frame yang sesuai dengan bentuk wajah saya. Tes refraksi minus dan silinder sangat akurat, tidak pusing saat kacamata pertama kali dicoba. Recommended banget!',
                'review_time' => '2026-09-24 10:20:00',
                'sentiment' => 'positive',
                'is_local_guide' => 1,
                'review_language' => 'id',
                'review_photo_count' => 1,
                'reviewer_photo_count' => 5,
                'owner_reply' => 'Terima kasih atas ulasan dan rekomendasinya Kak Siti! Semoga kacamatanya awet dan selalu mendukung aktivitas sehari-hari.',
                'owner_reply_time' => '2026-09-24 11:00:00'
            ],
            [
                'google_review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUN6MUpkS0pnEAE',
                'author_name' => 'Budi Santoso',
                'author_photo_url' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100&auto=format&fit=crop&q=80',
                'author_url' => 'https://maps.google.com/?cid=1003',
                'rating' => 4,
                'review_text' => 'Kualitas kacamata dan lensanya bagus, anti radiasi blue-ray bekerja dengan baik untuk kerja di depan laptop seharian. Cuma antrian saat weekend lumayan panjang karena ramai.',
                'review_time' => '2026-09-18 13:45:00',
                'sentiment' => 'positive',
                'is_local_guide' => 0,
                'review_language' => 'id',
                'review_photo_count' => 0,
                'reviewer_photo_count' => 0,
                'owner_reply' => 'Terima kasih masukannya Kak Budi! Kami akan terus meningkatkan kapasitas pelayanan agar waktu tunggu saat akhir pekan lebih singkat.',
                'owner_reply_time' => '2026-09-18 15:10:00'
            ],
            [
                'google_review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUN6N3JTTkpnEAE',
                'author_name' => 'Anisa Rahmawati',
                'author_photo_url' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&auto=format&fit=crop&q=80',
                'author_url' => 'https://maps.google.com/?cid=1004',
                'rating' => 2,
                'review_text' => 'Frame yang saya incar ternyata stok warnanya habis dan baru ada minggu depan. Mohon informasi ketersediaan stok di display toko lebih diperbarui.',
                'review_time' => '2026-08-12 18:20:00',
                'sentiment' => 'negative',
                'is_local_guide' => 0,
                'review_language' => 'id',
                'review_photo_count' => 2,
                'reviewer_photo_count' => 8,
                'owner_reply' => 'Mohon maaf atas ketidaknyamanannya Kak Anisa. Stok frame model tersebut sudah kami restock kembali. Kami siap memberikan penawaran khusus untuk kunjungan berikutnya.',
                'owner_reply_time' => '2026-08-13 09:00:00'
            ],
            [
                'google_review_id' => 'ChZDSUhNMG9nS0VJQ0FnSUN6OTlQbE5nEAE',
                'author_name' => 'Michael Christian Tan',
                'author_photo_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80',
                'author_url' => 'https://maps.google.com/?cid=1005',
                'rating' => 5,
                'review_text' => 'Best optical store in Bandung! Professional eye check, wide variety of titanium frames, and prompt service. The progressive lenses feel very natural from day one.',
                'review_time' => '2026-08-05 09:10:00',
                'sentiment' => 'positive',
                'is_local_guide' => 1,
                'review_language' => 'en',
                'review_photo_count' => 6,
                'reviewer_photo_count' => 24,
                'owner_reply' => 'Thank you so much Michael! Glad you had a great experience with our progressive lenses!',
                'owner_reply_time' => '2026-08-05 10:00:00'
            ]
        ];
    }
}
