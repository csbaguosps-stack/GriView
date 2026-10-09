<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Konfigurasi Aplikasi Google Maps Review Viewer & Exporter
 * Architecture: MVC (Model-View-Controller)
 */

// Zona Waktu Default Aplikasi (WIB - Waktu Indonesia Barat / Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');
define('APP_TIMEZONE', 'Asia/Jakarta');

// Konfigurasi Database (Default: SQLite untuk kemudahan instalasi tanpa konfigurasi manual)
define('DB_DRIVER', 'sqlite'); // 'sqlite' atau 'mysql'
define('DB_SQLITE_PATH', __DIR__ . '/../data/reviews.sqlite');

// Jika menggunakan MySQL (opsional):
define('DB_HOST', 'localhost');
define('DB_NAME', 'griview_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Konfigurasi Bisnis / Tempat Default (Fallback)
define('DEFAULT_PLACE_NAME', 'GriView Business');
define('DEFAULT_PLACE_ADDRESS', '');
define('DEFAULT_PLACE_ID', '');
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: ''); // Ambil dari Environment Variable jika diperlukan

// Konfigurasi Aplikasi
define('APP_NAME', 'GriView');
define('APP_SUBTITLE', 'Google Business Review Audit & Analytics');
define('APP_VERSION', '1.0.6');
define('ROOT_DIR', dirname(__DIR__, 2)); // c:/xampp/htdocs/griview

// Deteksi Base URL secara otomatis (Mendukung localhost, subdomain seperti grivew.winseeoptik.com, HTTPS, Cloudflare, & Reverse Proxy)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], '"https"') !== false)
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$rawScript = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($rawScript));
if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
    $scriptDir = '';
}
$baseUrl = rtrim($protocol . $host . ($scriptDir ? '/' . ltrim($scriptDir, '/') : ''), '/');

// Cek jika pengguna menetapkan Base URL khusus di Pengaturan (misal CDN atau Domain Custom)
$settingsFile = __DIR__ . '/../data/settings.json';
if (file_exists($settingsFile)) {
    $rawSettings = @json_decode(file_get_contents($settingsFile), true);
    if (!empty($rawSettings['custom_base_url'])) {
        $baseUrl = rtrim($rawSettings['custom_base_url'], '/');
    }
}
define('BASE_URL', $baseUrl);

/**
 * Helper untuk membuat URL internal aplikasi
 */
function url($controller = 'review', $action = 'index', $params = []) {
    $query = array_merge(['c' => $controller, 'a' => $action], $params);
    return BASE_URL . '/index.php?' . http_build_query($query);
}

/**
 * Helper untuk format tanggal Indonesia
 */
function formatTanggalIndo($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    return "$hari $bulan $tahun, $jam WIB";
}

/**
 * Helper format nama bulan Indonesia (YYYY-MM)
 */
function formatBulanIndo($yearMonth) {
    if (!$yearMonth || $yearMonth === 'all') return 'Semua Periode';
    $parts = explode('-', $yearMonth);
    if (count($parts) !== 2) return $yearMonth;
    $bulanIndo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $bulan = $bulanIndo[$parts[1]] ?? $parts[1];
    return $bulan . ' ' . $parts[0];
}
