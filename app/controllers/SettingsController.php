<?php
/* License: by cs.baguosps@gmail.com */
/**
 * SettingsController - Pengaturan Terpadu: Upload Thumbnail, Logo, Favicon & Fungsi Web & Ekstensi
 */
require_once __DIR__ . '/../models/PlaceConfig.php';

class SettingsController {
    private PlaceConfig $configModel;

    public function __construct() {
        $this->configModel = new PlaceConfig();
    }

    public function index(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $settings = $this->configModel->getAll();

        $zipPath = __DIR__ . '/../../assets/griview-chrome-extension.zip';
        $zipExists = file_exists($zipPath);
        $zipSize = $zipExists ? round(filesize($zipPath) / 1024, 1) : 0;
        $zipModified = $zipExists ? date('d F Y, H:i', filemtime($zipPath)) : '-';

        $junkStats = $this->scanJunkFiles();
        $activeTab = $_GET['tab'] ?? 'web-brand';

        $viewData = [
            'settings'    => $settings,
            'zipExists'   => $zipExists,
            'zipSize'     => $zipSize,
            'zipModified' => $zipModified,
            'junkStats'   => $junkStats,
            'activeTab'   => $activeTab,
            'flash'       => $_SESSION['flash'] ?? null,
        ];
        unset($_SESSION['flash']);

        $this->render('settings/index', $viewData);
    }

    public function save(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('settings', 'index'));
            exit;
        }

        $uploadDir = realpath(__DIR__ . '/../../assets') . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $iconDir = realpath(__DIR__ . '/../../chrome-extension') . DIRECTORY_SEPARATOR . 'icons';
        if (!is_dir($iconDir)) {
            @mkdir($iconDir, 0777, true);
        }

        $settings = $this->configModel->getAll();
        $messages = [];

        // 1. Teks Branding Web
        $settings['app_name']    = trim($_POST['app_name'] ?? 'GriView');
        if (empty($settings['app_name'])) $settings['app_name'] = 'GriView';
        $settings['app_tagline'] = trim($_POST['app_tagline'] ?? 'Review audit & reputation workflow');
        $settings['business_group'] = trim($_POST['business_group'] ?? 'Semua Cabang');
        if (empty($settings['business_group'])) $settings['business_group'] = 'Semua Cabang';
        $settings['app_footer']  = trim($_POST['app_footer'] ?? 'GriView - Google Business Review Audit & Analytics');

        // 2. Teks Branding Ekstensi Chrome
        $settings['ext_name']        = trim($_POST['ext_name'] ?? 'GriView Review Audit');
        if (empty($settings['ext_name'])) $settings['ext_name'] = 'GriView Review Audit';
        $settings['ext_version']     = trim($_POST['ext_version'] ?? '1.0.1');
        if (empty($settings['ext_version'])) $settings['ext_version'] = '1.0.1';
        $settings['ext_description'] = trim($_POST['ext_description'] ?? 'Audit hingga 1.000 ulasan Google Maps dengan scroll otomatis.');

        // 3. Pengaturan Fungsi Web (Web Functions)
        $settings['default_scrape_limit'] = trim($_POST['default_scrape_limit'] ?? '1000');
        $settings['default_photo_filter'] = trim($_POST['default_photo_filter'] ?? 'all');
        $settings['short_text_threshold'] = max(5, min(500, (int)($_POST['short_text_threshold'] ?? 40)));
        $settings['default_export_format']= in_array($_POST['default_export_format'] ?? '', ['xls', 'csv'], true) ? $_POST['default_export_format'] : 'xls';
        $settings['custom_base_url']      = trim($_POST['custom_base_url'] ?? '');

        // 4. Pengaturan Fungsi Ekstensi (Extension Functions)
        $settings['ext_scroll_speed']     = in_array($_POST['ext_scroll_speed'] ?? '', ['fast', 'normal', 'relaxed'], true) ? $_POST['ext_scroll_speed'] : 'normal';
        $settings['ext_auto_save']        = !empty($_POST['ext_auto_save']) ? '1' : '0';
        $settings['ext_auto_open_result'] = !empty($_POST['ext_auto_open_result']) ? '1' : '0';
        $settings['ext_detect_photos']    = !empty($_POST['ext_detect_photos']) ? '1' : '0';
        $settings['ext_auto_expand_more'] = !empty($_POST['ext_auto_expand_more']) ? '1' : '0';

        // 5. Upload Logo Web
        if (!empty($_FILES['app_logo']['tmp_name']) && is_uploaded_file($_FILES['app_logo']['tmp_name'])) {
            $dest = $uploadDir . DIRECTORY_SEPARATOR . 'logo.png';
            if ($this->processUploadedImage($_FILES['app_logo']['tmp_name'], $dest, 400, 120)) {
                $settings['app_logo'] = 'assets/uploads/logo.png';
                $messages[] = 'Logo Web berhasil diperbarui.';
            }
        }

        // 6. Upload Favicon Web
        if (!empty($_FILES['app_favicon']['tmp_name']) && is_uploaded_file($_FILES['app_favicon']['tmp_name'])) {
            $dest = $uploadDir . DIRECTORY_SEPARATOR . 'favicon.png';
            if ($this->processUploadedImage($_FILES['app_favicon']['tmp_name'], $dest, 64, 64)) {
                $settings['app_favicon'] = 'assets/uploads/favicon.png';
                $messages[] = 'Favicon Browser berhasil diperbarui.';
            }
        }

        // 7. Upload Thumbnail Web (Social Share / Og:Image)
        if (!empty($_FILES['app_thumbnail']['tmp_name']) && is_uploaded_file($_FILES['app_thumbnail']['tmp_name'])) {
            $dest = $uploadDir . DIRECTORY_SEPARATOR . 'thumbnail.png';
            if ($this->processUploadedImage($_FILES['app_thumbnail']['tmp_name'], $dest, 1200, 630)) {
                $settings['app_thumbnail'] = 'assets/uploads/thumbnail.png';
                $messages[] = 'Thumbnail / Banner Web berhasil diperbarui.';
            }
        }

        // 8. Upload Ikon Ekstensi Chrome
        if (!empty($_FILES['ext_icon']['tmp_name']) && is_uploaded_file($_FILES['ext_icon']['tmp_name'])) {
            $src = $_FILES['ext_icon']['tmp_name'];
            $destMain = $uploadDir . DIRECTORY_SEPARATOR . 'ext_icon.png';
            if ($this->processUploadedImage($src, $destMain, 256, 256)) {
                $settings['ext_icon'] = 'assets/uploads/ext_icon.png';
                // Buat 16, 48, 128 untuk folder ekstensi
                $this->processUploadedImage($src, $iconDir . DIRECTORY_SEPARATOR . 'icon-16.png', 16, 16);
                $this->processUploadedImage($src, $iconDir . DIRECTORY_SEPARATOR . 'icon-48.png', 48, 48);
                $this->processUploadedImage($src, $iconDir . DIRECTORY_SEPARATOR . 'icon-128.png', 128, 128);
                $messages[] = 'Ikon Ekstensi Chrome (16px, 48px, 128px) berhasil dibuat & diperbarui.';
            }
        }

        // Simpan semua ke model
        $this->configModel->updateAll($settings);

        // Update manifest.json ekstensi Chrome
        $this->updateExtensionManifest(
            $settings['ext_name'],
            $settings['ext_description'],
            $settings['ext_version']
        );

        // Repack file zip ekstensi agar selalu sinkron
        $this->repackExtensionZip();

        $infoMsg = !empty($messages) ? implode(' ', $messages) . ' ' : '';
        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => '🎉 ' . $infoMsg . 'Seluruh pengaturan branding web, fungsi, dan paket ekstensi Chrome (.ZIP) telah diperbarui!'
        ];

        header('Location: ' . url('settings', 'index'));
        exit;
    }

    public function removeAsset(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $type = $_GET['type'] ?? '';
        $uploadDir = realpath(__DIR__ . '/../../assets/uploads');
        $settings = $this->configModel->getAll();

        switch ($type) {
            case 'logo':
                if (file_exists($uploadDir . '/logo.png')) @unlink($uploadDir . '/logo.png');
                $settings['app_logo'] = '';
                $msg = 'Logo web dihapus (kembali ke ikon default).';
                break;
            case 'favicon':
                if (file_exists($uploadDir . '/favicon.png')) @unlink($uploadDir . '/favicon.png');
                $settings['app_favicon'] = '';
                $msg = 'Favicon web dihapus (kembali ke ikon default).';
                break;
            case 'thumbnail':
                if (file_exists($uploadDir . '/thumbnail.png')) @unlink($uploadDir . '/thumbnail.png');
                $settings['app_thumbnail'] = '';
                $msg = 'Thumbnail web dihapus.';
                break;
            case 'ext_icon':
                if (file_exists($uploadDir . '/ext_icon.png')) @unlink($uploadDir . '/ext_icon.png');
                $settings['ext_icon'] = '';
                // Generate kembali ikon standar
                $this->generateDefaultExtensionIcons();
                $this->repackExtensionZip();
                $msg = 'Ikon ekstensi dikembalikan ke standar GriView.';
                break;
            default:
                $msg = 'Aset tidak ditemukan.';
        }

        $this->configModel->updateAll($settings);
        $_SESSION['flash'] = ['type' => 'info', 'message' => $msg];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    public function reset(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->configModel->resetDefaults();
        $this->generateDefaultExtensionIcons();
        $this->updateExtensionManifest('GriView Review Audit', 'Audit hingga 1.000 ulasan Google Maps dengan scroll otomatis.', '1.0.1');
        $this->repackExtensionZip();

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Pengaturan telah berhasil dikembalikan ke standar bawaan GriView.'
        ];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    public function rebuildZip(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->repackExtensionZip();
        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Paket Chrome Extension (.ZIP) berhasil di-repack dan diperbarui!'
        ];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    /**
     * Otomatis sinkronkan ikon ekstensi dari favicon/logo web yang sudah diupload
     */
    public function syncExtIcon(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $favPath = ROOT_DIR . '/assets/uploads/favicon.png';
        $logoPath = ROOT_DIR . '/assets/uploads/logo.png';
        $srcPath = file_exists($favPath) ? $favPath : (file_exists($logoPath) ? $logoPath : null);

        if (!$srcPath) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Belum ada Favicon atau Logo Web yang diunggah untuk disinkronkan ke ekstensi.'
            ];
            header('Location: ' . url('settings', 'index'));
            exit;
        }

        $iconDir = ROOT_DIR . '/chrome-extension/icons';
        if (!is_dir($iconDir)) @mkdir($iconDir, 0777, true);

        // Generate dan auto-kompres 16px, 48px, 128px
        $this->processUploadedImage($srcPath, $iconDir . '/icon-16.png', 16, 16);
        $this->processUploadedImage($srcPath, $iconDir . '/icon-48.png', 48, 48);
        $this->processUploadedImage($srcPath, $iconDir . '/icon-128.png', 128, 128);

        // Copy juga ke assets/uploads/ext_icon.png
        $this->processUploadedImage($srcPath, ROOT_DIR . '/assets/uploads/ext_icon.png', 256, 256);

        $settings = $this->configModel->getAll();
        $settings['ext_icon'] = 'assets/uploads/ext_icon.png';
        $this->configModel->updateAll($settings);

        $this->updateExtensionManifest($settings['ext_name'], $settings['ext_description'], $settings['ext_version']);
        $this->repackExtensionZip();

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => '⚡ Sukses! Ikon Ekstensi Chrome (16px, 48px, 128px) telah diselaraskan dengan Favicon Web, dikompres otomatis (Level 9), dan ZIP installer telah diperbarui.'
        ];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    /**
     * Auto-kompres semua gambar web & ekstensi ke tingkat kompresi maksimal
     */
    public function autoCompress(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $uploadDir = ROOT_DIR . '/assets/uploads';
        $iconDir   = ROOT_DIR . '/chrome-extension/icons';
        $filesCount = 0;

        // Kompres logo
        if (file_exists($uploadDir . '/logo.png')) {
            $this->processUploadedImage($uploadDir . '/logo.png', $uploadDir . '/logo.png', 400, 120);
            $filesCount++;
        }

        // Kompres favicon
        if (file_exists($uploadDir . '/favicon.png')) {
            $this->processUploadedImage($uploadDir . '/favicon.png', $uploadDir . '/favicon.png', 64, 64);
            $filesCount++;
        }

        // Kompres thumbnail
        if (file_exists($uploadDir . '/thumbnail.png')) {
            $this->processUploadedImage($uploadDir . '/thumbnail.png', $uploadDir . '/thumbnail.png', 1200, 630);
            $filesCount++;
        }

        // Kompres ekstensi icon
        foreach ([16, 48, 128] as $sz) {
            $iconFile = $iconDir . "/icon-{$sz}.png";
            if (file_exists($iconFile)) {
                $this->processUploadedImage($iconFile, $iconFile, $sz, $sz);
                $filesCount++;
            }
        }

        // Re-pack zip ekstensi
        $this->repackExtensionZip();

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => "⚡ Auto-kompresi berhasil! {$filesCount} aset gambar telah dioptimalkan ke kompresi tertinggi (Level 9) dan file ZIP ekstensi telah diperbarui."
        ];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    /**
     * Bersihkan berkas sampah: file ekspor lama (.xls), berkas sementara (*.tmp), dan log
     */
    public function clearJunk(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $target = $_GET['target'] ?? 'all';
        $fileToDelete = $_GET['file'] ?? '';
        $deletedCount = 0;
        $freedBytes = 0;

        $exportsDir = ROOT_DIR . '/app/data/exports';
        $dirsToScan = [ROOT_DIR . '/app/data', ROOT_DIR . '/assets/uploads', ROOT_DIR];

        // 1. Hapus satu berkas spesifik jika diminta
        if (!empty($fileToDelete)) {
            $safeName = basename($fileToDelete);
            $targetFile = $exportsDir . '/' . $safeName;
            if (file_exists($targetFile) && is_file($targetFile) && $safeName !== '.gitkeep') {
                $sz = filesize($targetFile);
                if (@unlink($targetFile)) {
                    $deletedCount++;
                    $freedBytes += $sz;
                }
            }
        } else {
            // 2. Hapus berkas ekspor laporan (.xls, .csv)
            if ($target === 'all' || $target === 'exports') {
                if (is_dir($exportsDir)) {
                    foreach (scandir($exportsDir) as $f) {
                        if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
                        $fp = $exportsDir . '/' . $f;
                        if (is_file($fp)) {
                            $sz = filesize($fp);
                            if (@unlink($fp)) {
                                $deletedCount++;
                                $freedBytes += $sz;
                            }
                        }
                    }
                }
            }

            // 3. Hapus berkas sementara (*.tmp)
            if ($target === 'all' || $target === 'temps') {
                foreach ($dirsToScan as $d) {
                    if (is_dir($d)) {
                        foreach (glob($d . '/*.tmp') ?: [] as $fp) {
                            if (is_file($fp)) {
                                $sz = filesize($fp);
                                if (@unlink($fp)) {
                                    $deletedCount++;
                                    $freedBytes += $sz;
                                }
                            }
                        }
                    }
                }
            }

            // 4. Hapus berkas log (*.log)
            if ($target === 'all' || $target === 'logs') {
                foreach ($dirsToScan as $d) {
                    if (is_dir($d)) {
                        foreach (glob($d . '/*.log') ?: [] as $fp) {
                            if (is_file($fp)) {
                                $sz = filesize($fp);
                                if (@unlink($fp)) {
                                    $deletedCount++;
                                    $freedBytes += $sz;
                                }
                            }
                        }
                    }
                }
            }
        }

        $freedFormatted = $freedBytes > 1048576 
            ? round($freedBytes / 1048576, 2) . ' MB' 
            : round($freedBytes / 1024, 1) . ' KB';

        if ($deletedCount > 0) {
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => "🧹 Pembersihan sukses! {$deletedCount} berkas sampah berhasil dihapus ({$freedFormatted} ruang disk dibebaskan)."
            ];
        } else {
            $_SESSION['flash'] = [
                'type'    => 'info',
                'message' => '✨ Sistem sudah bersih! Tidak ada berkas sampah yang perlu dihapus saat ini.'
            ];
        }

        header('Location: ' . url('settings', 'index', ['tab' => 'cleaner']));
        exit;
    }

    /**
     * Memindai direktori untuk menghitung berkas sampah (exports, temp, logs)
     */
    private function scanJunkFiles(): array {
        $exportsDir = ROOT_DIR . '/app/data/exports';
        $exportFiles = [];
        $exportSize = 0;
        if (is_dir($exportsDir)) {
            foreach (scandir($exportsDir) as $f) {
                if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
                $fp = $exportsDir . '/' . $f;
                if (is_file($fp)) {
                    $sz = filesize($fp);
                    $exportSize += $sz;
                    $exportFiles[] = [
                        'name' => $f,
                        'size' => round($sz / 1024, 1),
                        'time' => date('d M Y, H:i', filemtime($fp)),
                    ];
                }
            }
        }

        $tempFiles = [];
        $tempSize = 0;
        $dirsToScan = [ROOT_DIR . '/app/data', ROOT_DIR . '/assets/uploads', ROOT_DIR];
        foreach ($dirsToScan as $d) {
            if (is_dir($d)) {
                foreach (glob($d . '/*.tmp') ?: [] as $tf) {
                    if (is_file($tf)) {
                        $sz = filesize($tf);
                        $tempSize += $sz;
                        $tempFiles[] = [
                            'name' => basename($tf),
                            'size' => round($sz / 1024, 1),
                            'time' => date('d M Y, H:i', filemtime($tf)),
                        ];
                    }
                }
            }
        }

        $logFiles = [];
        $logSize = 0;
        foreach ($dirsToScan as $d) {
            if (is_dir($d)) {
                foreach (glob($d . '/*.log') ?: [] as $lf) {
                    if (is_file($lf)) {
                        $sz = filesize($lf);
                        $logSize += $sz;
                        $logFiles[] = [
                            'name' => basename($lf),
                            'size' => round($sz / 1024, 1),
                            'time' => date('d M Y, H:i', filemtime($lf)),
                        ];
                    }
                }
            }
        }

        $totalBytes = $exportSize + $tempSize + $logSize;
        $totalCount = count($exportFiles) + count($tempFiles) + count($logFiles);

        return [
            'exports'       => [
                'count'   => count($exportFiles),
                'size_kb' => round($exportSize / 1024, 1),
                'files'   => $exportFiles,
            ],
            'temps'         => [
                'count'   => count($tempFiles),
                'size_kb' => round($tempSize / 1024, 1),
                'files'   => $tempFiles,
            ],
            'logs'          => [
                'count'   => count($logFiles),
                'size_kb' => round($logSize / 1024, 1),
                'files'   => $logFiles,
            ],
            'total_count'   => $totalCount,
            'total_size_kb' => round($totalBytes / 1024, 1),
            'total_size_mb' => round($totalBytes / (1024 * 1024), 2),
        ];
    }

    // ─── Helper Internal ───

    private function processUploadedImage(string $sourcePath, string $targetPath, int $maxW, int $maxH): bool {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            // Coba copy langsung jika format tidak terbaca getimagesize (misal svg)
            return @copy($sourcePath, $targetPath);
        }

        $mime = $info['mime'] ?? '';
        $srcImg = null;

        switch ($mime) {
            case 'image/png':
                $srcImg = @imagecreatefrompng($sourcePath);
                break;
            case 'image/jpeg':
                $srcImg = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImg = @imagecreatefromwebp($sourcePath);
                }
                break;
            case 'image/x-icon':
            case 'image/vnd.microsoft.icon':
                return @copy($sourcePath, $targetPath);
        }

        if (!$srcImg) {
            return @copy($sourcePath, $targetPath);
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);

        // Jika ukuran persegi sama (seperti favicon / ext icon)
        if ($maxW === $maxH) {
            $targetW = $maxW;
            $targetH = $maxH;
        } else {
            // Skala proporsional
            $ratio = min($maxW / max(1, $origW), $maxH / max(1, $origH));
            $targetW = max(1, (int)round($origW * min(1, $ratio)));
            $targetH = max(1, (int)round($origH * min(1, $ratio)));
        }

        $dstImg = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
        imagefill($dstImg, 0, 0, $transparent);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        $saved = imagepng($dstImg, $targetPath, 9);
        imagedestroy($srcImg);
        imagedestroy($dstImg);
        return $saved;
    }

    private function generateDefaultExtensionIcons(): void {
        $iconDir = realpath(__DIR__ . '/../../chrome-extension') . DIRECTORY_SEPARATOR . 'icons';
        if (!is_dir($iconDir)) @mkdir($iconDir, 0777, true);

        $sizes = [16, 48, 128];
        foreach ($sizes as $size) {
            $img = imagecreatetruecolor($size, $size);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);

            $bg = imagecolorallocate($img, 79, 70, 229); // indigo #4f46e5
            $radius = max(2, (int)round($size * 0.22));

            imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $bg);
            imagefilledellipse($img, $size - $radius, $radius, $radius * 2, $radius * 2, $bg);
            imagefilledellipse($img, $radius, $size - $radius, $radius * 2, $radius * 2, $bg);
            imagefilledellipse($img, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $bg);
            imagefilledrectangle($img, $radius, 0, $size - $radius, $size, $bg);
            imagefilledrectangle($img, 0, $radius, $size, $size - $radius, $bg);

            $white = imagecolorallocate($img, 255, 255, 255);
            if ($size >= 48) {
                imagefilledellipse($img, (int)($size / 2), (int)($size * 0.42), (int)($size * 0.45), (int)($size * 0.45), $white);
                imagefilledellipse($img, (int)($size / 2), (int)($size * 0.42), (int)($size * 0.22), (int)($size * 0.22), $bg);
                $points = [
                    (int)($size * 0.35), (int)($size * 0.52),
                    (int)($size * 0.65), (int)($size * 0.52),
                    (int)($size * 0.50), (int)($size * 0.82),
                ];
                imagefilledpolygon($img, $points, $white);
            } else {
                imagefilledellipse($img, (int)($size / 2), (int)($size / 2), (int)($size * 0.6), (int)($size * 0.6), $white);
            }

            imagepng($img, $iconDir . DIRECTORY_SEPARATOR . "icon-{$size}.png");
            imagedestroy($img);
        }
    }

    private function updateExtensionManifest(string $name, string $description, string $version): void {
        $manifestPath = __DIR__ . '/../../chrome-extension/manifest.json';
        if (!file_exists($manifestPath)) return;
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($manifest)) return;

        $manifest['name'] = $name;
        $manifest['description'] = $description;
        $manifest['version'] = $version;
        if (isset($manifest['action'])) {
            $manifest['action']['default_title'] = $name;
        }
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function repackExtensionZip(): void {
        $zipPath = ROOT_DIR . '/assets/griview-chrome-extension.zip';
        $extDir  = ROOT_DIR . '/chrome-extension';

        // 1. Prioritas Utama: PHP ZipArchive (standar pada Linux cPanel / Cloud / VPS)
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($extDir) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                return;
            }
        }

        // 2. Fallback Windows (XAMPP lokal)
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'Compress-Archive -Path "' . $extDir . '\*" -DestinationPath "' . $zipPath . '" -CompressionLevel Optimal -Force';
            @exec("powershell -NoProfile -Command " . escapeshellarg($cmd));
            return;
        }

        // 3. Fallback Linux / Unix shell command
        if (function_exists('exec')) {
            $cmd = 'cd ' . escapeshellarg($extDir) . ' && zip -r -q ' . escapeshellarg($zipPath) . ' .';
            @exec($cmd);
        }
    }

    private function render(string $viewPath, array $data = []): void {
        extract($data);
        $contentView = __DIR__ . '/../views/' . $viewPath . '.php';
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/layouts/navbar.php';
        require_once $contentView;
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
