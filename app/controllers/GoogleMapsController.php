<?php
/* License: by cs.baguosps@gmail.com */
/**
 * GoogleMapsController - Menangani sinkronisasi live Google Maps API & Web Scraping
 * Dilengkapi dengan background process scraping & progress status real-time
 */
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/PlaceConfig.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../helpers/JsonDatabase.php';
require_once __DIR__ . '/../helpers/GoogleMapsApi.php';
require_once __DIR__ . '/../helpers/BusinessProfileApi.php';

class GoogleMapsController {
    private Review $reviewModel;
    private PlaceConfig $configModel;
    private Store $storeModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->configModel = new PlaceConfig();
        $this->storeModel  = new Store();
    }

    public function connectBusinessProfile(): void {
        if (!BusinessProfileApi::isConfigured()) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'OAuth belum dikonfigurasi. Atur GRIVIEW_GOOGLE_OAUTH_CLIENT_ID dan GRIVIEW_GOOGLE_OAUTH_CLIENT_SECRET pada environment server.'
            ];
            header('Location: ' . url('settings', 'index'));
            exit;
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['business_profile_oauth_state'] = $state;
        header('Location: ' . BusinessProfileApi::authorizationUrl(url('google', 'businessProfileCallback'), $state));
        exit;
    }

    public function businessProfileCallback(): void {
        $expectedState = $_SESSION['business_profile_oauth_state'] ?? '';
        unset($_SESSION['business_profile_oauth_state']);

        if (!empty($_GET['error'])) {
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => 'Google membatalkan OAuth: ' . htmlspecialchars((string)$_GET['error'])
            ];
        } elseif ($expectedState === '' || !hash_equals($expectedState, (string)($_GET['state'] ?? ''))) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'State OAuth tidak cocok. Mulai proses hubungkan Google lagi.'];
        } elseif (empty($_GET['code'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Google tidak mengirim authorization code.'];
        } else {
            try {
                BusinessProfileApi::connect((string)$_GET['code'], url('google', 'businessProfileCallback'));
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Google Business Profile berhasil dihubungkan. Anda dapat menarik seluruh review dari lokasi terverifikasi.'
                ];
            } catch (Throwable $error) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'OAuth Google gagal: ' . htmlspecialchars($error->getMessage())];
            }
        }

        header('Location: ' . url('settings', 'index'));
        exit;
    }

    public function disconnectBusinessProfile(): void {
        BusinessProfileApi::disconnect();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Token Google Business Profile lokal sudah dihapus.'];
        header('Location: ' . url('settings', 'index'));
        exit;
    }

    public function syncBusinessProfile(): void {
        $placeId = trim((string)$this->configModel->get('place_id', DEFAULT_PLACE_ID));
        try {
            $result = BusinessProfileApi::fetchAllReviews($placeId);
            if (empty($result['reviews'])) {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Lokasi ditemukan, tetapi API tidak mengembalikan review. Pastikan profil terverifikasi dan review tersedia.'];
                header('Location: ' . url('settings', 'index'));
                exit;
            }

            $batch = $result['reviews'];
            $importedCount = $this->reviewModel->insertOrUpdateBatch($batch);
            $this->configModel->set('place_name', $result['place_name']);

            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => "Business Profile selesai disinkronkan: {$importedCount} dari {$result['total_review_count']} review diimpor untuk {$result['place_name']}."
            ];
        } catch (Throwable $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sinkronisasi Business Profile gagal: ' . htmlspecialchars($error->getMessage())];
        }

        header('Location: ' . url('settings', 'index'));
        exit;
    }

    /**
     * AJAX Endpoint: Memulai proses Scraping Python di latar belakang (Background Process)
     */
    public function startScrape(): void {
        header('Content-Type: application/json; charset=utf-8');

        $url     = trim($_POST['gmaps_url'] ?? '');
        $limitInput = strtolower(trim((string)($_POST['scrape_limit'] ?? '50')));
        $limit   = $limitInput === 'all' ? 1000 : max(10, min(1000, (int)$limitInput));
        $storeId = !empty($_POST['store_id']) && is_numeric($_POST['store_id']) ? (int)$_POST['store_id'] : null;

        if (empty($url)) {
            echo json_encode([
                'success' => false,
                'message' => 'Harap masukkan Link Google Maps atau nama tempat bisnis.'
            ]);
            exit;
        }

        $jobId      = 'job_' . time() . '_' . bin2hex(random_bytes(4));
        $dataDir    = realpath(__DIR__ . '/../data');
        if ($dataDir === false || !is_writable($dataDir)) {
            echo json_encode([
                'success' => false,
                'message' => 'Folder app/data tidak dapat ditulis oleh PHP. Atur izin folder sebelum menjalankan scraping.'
            ]);
            exit;
        }
        $outputJson = $dataDir . DIRECTORY_SEPARATOR . 'scraper_result_' . $jobId . '.json';
        $configFile = $dataDir . DIRECTORY_SEPARATOR . 'scraper_cfg_' . $jobId . '.json';
        $statusFile = $dataDir . DIRECTORY_SEPARATOR . 'scraper_status_' . $jobId . '.json';
        $logFile    = $dataDir . DIRECTORY_SEPARATOR . 'scraper_log_' . $jobId . '.txt';
        $selectedStore = $storeId ? $this->storeModel->getById($storeId) : null;

        // Tulis inisialisasi file status
        file_put_contents($statusFile, json_encode([
            'job_id'     => $jobId,
            'status'     => 'running',
            'progress'   => 0,
            'target'     => $limit,
            'message'    => 'Menunggu worker Python dimulai...',
            'place_name' => 'Google Maps',
            'updated_at' => time()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Tulis konfigurasi scraper
        file_put_contents($configFile, json_encode([
            'url'         => $url,
            'limit'       => $limit,
            'output'      => $outputJson,
            'status_file' => $statusFile,
            'log_file'    => $logFile,
            'store_id'    => $storeId,
            'place_name'  => $selectedStore['store_name'] ?? null,
            'headless'    => true
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $configuredPython = getenv('GRIVIEW_PYTHON');
        $pythonExec = $configuredPython ?: (PHP_OS_FAMILY === 'Windows' ? 'C:\\Python314\\python.exe' : 'python3');
        if (!$configuredPython && PHP_OS_FAMILY === 'Windows' && !file_exists($pythonExec)) {
            $pythonExec = 'python';
        }
        $scriptPath = realpath(__DIR__ . '/../../scraper/gmaps_scraper.py');
        if ($scriptPath === false || !file_exists($scriptPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Scraper Python telah dinonaktifkan dan digantikan oleh Ekstensi Chrome GriView Auto-Audit.'
            ]);
            exit;
        }

        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (!function_exists('popen') || !function_exists('pclose') || in_array('popen', $disabledFunctions, true) || in_array('pclose', $disabledFunctions, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Hosting menonaktifkan popen sehingga proses Python tidak dapat dijalankan. Gunakan VPS/hosting yang mengizinkan proses background dan Selenium.'
            ]);
            exit;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'start /B "" ' . escapeshellarg($pythonExec) . ' ' .
                   escapeshellarg($scriptPath) . ' --config ' . escapeshellarg($configFile) .
                   ' > ' . escapeshellarg($logFile) . ' 2>&1';
        } else {
            $cmd = 'nohup ' . escapeshellarg($pythonExec) . ' ' .
                   escapeshellarg($scriptPath) . ' --config ' . escapeshellarg($configFile) .
                   ' > ' . escapeshellarg($logFile) . ' 2>&1 &';
        }

        $process = @popen($cmd, 'r');
        if ($process === false) {
            @unlink($configFile);
            @unlink($statusFile);
            echo json_encode([
                'success' => false,
                'message' => 'PHP gagal menjalankan proses scraper. Hosting mungkin membatasi proses background atau executable Python.'
            ]);
            exit;
        }
        pclose($process);

        echo json_encode([
            'success'  => true,
            'job_id'   => $jobId,
            'store_id' => $storeId,
            'target'   => $limit,
            'message'  => 'Proses scraping telah dimulai di latar belakang.'
        ]);
        exit;
    }

    /**
     * AJAX Endpoint: Polling Status Proses Scraping
     */
    public function scrapeStatus(): void {
        header('Content-Type: application/json; charset=utf-8');

        $jobId = trim($_GET['job_id'] ?? '');
        if (empty($jobId) || !preg_match('/^[a-zA-Z0-9_]+$/', $jobId)) {
            echo json_encode(['status' => 'error', 'message' => 'Job ID tidak valid.']);
            exit;
        }

        $dataDir    = realpath(__DIR__ . '/../data');
        $statusFile = $dataDir . DIRECTORY_SEPARATOR . 'scraper_status_' . $jobId . '.json';
        $outputJson = $dataDir . DIRECTORY_SEPARATOR . 'scraper_result_' . $jobId . '.json';
        $configFile = $dataDir . DIRECTORY_SEPARATOR . 'scraper_cfg_' . $jobId . '.json';
        $logFile    = $dataDir . DIRECTORY_SEPARATOR . 'scraper_log_' . $jobId . '.txt';

        if (!file_exists($statusFile)) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'File status scraper tidak ditemukan. Proses Python mungkin gagal dijalankan; periksa instalasi Python, Selenium, dan Chrome.'
            ]);
            exit;
        }

        $statusData = json_decode(file_get_contents($statusFile), true);
        if (!$statusData) {
            echo json_encode(['status' => 'error', 'message' => 'Status scraper rusak atau tidak dapat dibaca. Jalankan ulang scraping dan periksa izin folder app/data.']);
            exit;
        }

        $statusAge = time() - (int)($statusData['updated_at'] ?? time());
        $logOutput = file_exists($logFile) ? trim((string)@file_get_contents($logFile)) : '';

        if (($statusData['status'] ?? '') === 'running' && ($statusData['message'] ?? '') === 'Menunggu worker Python dimulai...' && $statusAge > 30) {
            $detail = $logOutput !== '' ? substr($logOutput, -1800) : 'Log worker kosong.';
            @unlink($outputJson);
            @unlink($configFile);
            @unlink($statusFile);
            @unlink($logFile);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Worker Python tidak memberi tanda mulai dalam 30 detik. Periksa path Python, izin Apache menjalankan proses background, dan izin tulis app/data. ' . $detail
            ]);
            exit;
        }

        if (($statusData['status'] ?? '') === 'running' && $statusAge > 180) {
            $detail = $logOutput !== '' ? ' Detail log: ' . substr($logOutput, -1800) : '';
            $lastStage = !empty($statusData['message']) ? ' Tahap terakhir: ' . $statusData['message'] . '.' : '';
            @unlink($outputJson);
            @unlink($configFile);
            @unlink($statusFile);
            @unlink($logFile);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Scraper tidak merespons selama lebih dari 3 menit.' . $lastStage . ' Periksa Python, Selenium, Chrome/ChromeDriver, koneksi internet, dan batas proses hosting.' . $detail
            ]);
            exit;
        }

        // Jika selesai, simpan ulasan ke database JSON menggunakan batch insert
        if (($statusData['status'] ?? '') === 'completed') {
            $importedCount = 0;
            $storeId = null;

            // Baca config untuk dapatkan store_id & URL
            $cfg = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
            $storeId = $cfg['store_id'] ?? null;
            $gmapsUrl = $cfg['url'] ?? '';

            if (file_exists($outputJson)) {
                $scraped = json_decode(file_get_contents($outputJson), true);
                if (is_array($scraped) && !empty($scraped)) {
                    $storeCode  = null;
                    $targetName = null;

                    if ($storeId) {
                        $storeData = $this->storeModel->getById($storeId);
                        if ($storeData) {
                            $targetName = $storeData['store_name'];
                            $storeCode  = $storeData['store_code'];
                            if (empty($storeData['gmaps_url']) && !empty($gmapsUrl)) {
                                $this->storeModel->update($storeId, array_merge($storeData, ['gmaps_url' => $gmapsUrl]));
                            }
                        }
                    }

                    // Susun batch ulasan
                    $batch = [];
                    foreach ($scraped as $r) {
                        $rText = trim($r['text'] ?? '');
                        if (preg_match('/^(?:edited\s*|diedit\s*)?(?:a|an|\d+)\s*(?:seconds?|minutes?|hours?|days?|weeks?|months?|years?|detik|menit|jam|hari|minggu|bulan|tahun)\s*(?:ago|yang lalu|lalu)?$/i', $rText)) {
                            $rText = '';
                        }
                        $batch[] = [
                            'google_review_id' => $r['id'] ?? ('SCRAPE_' . uniqid()),
                            'store_id'         => $storeId,
                            'store_code'       => $storeCode,
                            'place_name'       => $targetName ?? ($r['place_name'] ?? ($statusData['place_name'] ?? DEFAULT_PLACE_NAME)),
                            'author_name'      => $r['author'] ?? 'Pengguna Google',
                            'author_photo_url' => $r['photo'] ?? null,
                            'rating'           => (int)($r['rating'] ?? 5),
                            'review_text'      => $rText,
                            'word_count'       => (int)($r['word_count'] ?? Review::countWords($rText)),
                            'review_time'      => $r['time'] ?? date('Y-m-d H:i:s'),
                            'sentiment'        => $r['sentiment'] ?? 'positive',
                            'is_local_guide'   => (int)($r['is_guide'] ?? 0),
                            'owner_reply'      => $r['reply'] ?? null,
                        ];
                    }

                    // Simpan seluruhnya dalam 1x tulis file
                    $importedCount = $this->reviewModel->insertOrUpdateBatch($batch);
                }
                @unlink($outputJson);
            }

            @unlink($configFile);
            @unlink($statusFile);
            @unlink($logFile);

            if ($importedCount < 1) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Tidak ada ulasan yang berhasil disimpan. Pastikan link mengarah ke profil Google Maps yang benar dan memiliki ulasan publik. Jika halaman ulasan kosong, Google Maps mungkin meminta login/verifikasi atau akses dibatasi; periksa juga izin tulis folder app/data.'
                ]);
                exit;
            }

            $xlsParams = $storeId ? ['store_id' => $storeId] : [];
            $xlsUrl = url('review', 'exportXls', $xlsParams);

            echo json_encode([
                'status'     => 'completed',
                'count'      => $importedCount,
                'target'     => $statusData['target'] ?? 50,
                'place_name' => $statusData['place_name'] ?? DEFAULT_PLACE_NAME,
                'store_id'   => $storeId,
                'xls_url'    => $xlsUrl,
                'message'    => "Web Scraping Berhasil! Sebanyak <strong>{$importedCount} ulasan</strong> berhasil ditarik dan langsung disimpan ke database JSON."
            ]);
            exit;
        }

        // Jika status error
        if (($statusData['status'] ?? '') === 'error') {
            @unlink($outputJson);
            @unlink($configFile);
            @unlink($statusFile);
            @unlink($logFile);

            echo json_encode([
                'status'  => 'error',
                'message' => $statusData['message'] ?? 'Scraping terhenti karena kendala sistem.'
            ]);
            exit;
        }

        // Sedang berjalan
        echo json_encode($statusData);
        exit;
    }

    /**
     * Web Scraping Sinkron (Fallback Form Tradisional)
     */
    public function scrapePython(): void {
        $url   = trim($_POST['gmaps_url'] ?? '');
        $limit = max(10, min(1000, (int)($_POST['scrape_limit'] ?? 50)));

        if (empty($url)) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Harap masukkan Link Google Maps atau nama tempat bisnis.'
            ];
            header('Location: ' . url('review', 'audit'));
            exit;
        }

        $scriptPath = realpath(__DIR__ . '/../../scraper/gmaps_scraper.py');
        if ($scriptPath === false || !file_exists($scriptPath)) {
            $_SESSION['flash'] = [
                'type'    => 'info',
                'message' => 'Scraper backend Python telah digantikan oleh Ekstensi Chrome GriView Auto-Audit.'
            ];
            header('Location: ' . url('review', 'audit'));
            exit;
        }

        $outputJson = __DIR__ . '/../data/scraper_result_' . time() . '_' . uniqid() . '.json';
        $configFile = __DIR__ . '/../data/scraper_cfg_' . time() . '_' . uniqid() . '.json';

        file_put_contents($configFile, json_encode([
            'url'      => $url,
            'limit'    => $limit,
            'output'   => $outputJson,
            'store_id' => $storeId,
            'headless' => true
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $cmd = escapeshellarg($pythonExec) . ' ' .
               escapeshellarg($scriptPath) . ' --config ' .
               escapeshellarg($configFile);

        set_time_limit(300);
        $output    = [];
        $returnVar = 0;
        exec($cmd . ' 2>&1', $output, $returnVar);

        $importedCount = 0;
        if (file_exists($outputJson)) {
            $scraped = json_decode(file_get_contents($outputJson), true);
            if (is_array($scraped) && !empty($scraped)) {
                $storeCode  = null;
                $targetName = null;
                if ($storeId) {
                    $storeData  = $this->storeModel->getById($storeId);
                    if ($storeData) {
                        $targetName = $storeData['store_name'];
                        $storeCode  = $storeData['store_code'];
                        if (empty($storeData['gmaps_url'])) {
                            $this->storeModel->update($storeId, array_merge($storeData, ['gmaps_url' => $url]));
                        }
                    }
                }

                $batch = [];
                foreach ($scraped as $r) {
                    $batch[] = [
                        'google_review_id' => $r['id'] ?? ('SCRAPE_' . uniqid()),
                        'store_id'         => $storeId,
                        'store_code'       => $storeCode,
                        'place_name'       => $targetName ?? ($r['place_name'] ?? DEFAULT_PLACE_NAME),
                        'author_name'      => $r['author'] ?? 'Pengguna Google',
                        'author_photo_url' => $r['photo'] ?? null,
                        'rating'           => (int)($r['rating'] ?? 5),
                        'review_text'      => $r['text'] ?? '',
                        'review_time'      => $r['time'] ?? date('Y-m-d H:i:s'),
                        'sentiment'        => $r['sentiment'] ?? 'positive',
                        'is_local_guide'   => (int)($r['is_guide'] ?? 0),
                        'owner_reply'      => $r['reply'] ?? null,
                    ];
                }
                $importedCount = $this->reviewModel->insertOrUpdateBatch($batch);
            }
            @unlink($outputJson);
        }
        @unlink($configFile);

        if ($importedCount > 0) {
            $xlsUrl = url('review', 'exportXls', $storeId ? ['store_id' => $storeId] : []);
            $_SESSION['flash'] = [
                'type'        => 'success',
                'message'     => "Web Scraping Berhasil! Sebanyak <strong>{$importedCount} ulasan</strong> berhasil ditarik dan langsung disimpan ke database JSON.",
                'action_url'  => $xlsUrl,
                'action_text' => 'Download File XLS Sekarang'
            ];
        } else {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => "<strong>Tidak Berhasil: 0 Ulasan Tersimpan ke Database JSON!</strong><br><br>" .
                             "<strong>Penjelasan:</strong> Scraper tidak menemukan ulasan baru pada link Google Maps yang dimasukkan.<br>" .
                             "<strong>Solusi:</strong> Pastikan Link Google Maps yang dimasukkan valid dan mengarah langsung ke tempat bisnis yang memiliki ulasan publik."
            ];
        }

        header('Location: ' . url('review', 'audit'));
        exit;
    }

    /**
     * Sinkronisasi Ulasan Langsung dari Google Places API
     */
    public function syncApi(): void {
        $apiKey  = trim($_POST['api_key'] ?? $this->configModel->get('google_api_key', ''));
        $placeId = trim($_POST['place_id'] ?? $this->configModel->get('place_id', ''));

        if (empty($apiKey) || empty($placeId)) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => '<strong>Gagal:</strong> API Key dan Place ID Google Maps harus diisi untuk sinkronisasi live API. Jika belum ada API Key, gunakan fitur <strong>Scraper Python (100% Gratis)</strong>.'
            ];
            header('Location: ' . url('review', 'audit'));
            exit;
        }

        $this->configModel->set('google_api_key', $apiKey);
        $this->configModel->set('place_id', $placeId);

        $result = GoogleMapsApi::fetchPlaceReviews($apiKey, $placeId);

        if (!$result['success']) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => '<strong>Gagal Mengambil Ulasan dari Google API:</strong> ' . htmlspecialchars($result['message'])
            ];
            header('Location: ' . url('review', 'audit'));
            exit;
        }

        if (!empty($result['place_name'])) {
            $this->configModel->set('place_name', $result['place_name']);
        }
        if (!empty($result['place_address'])) {
            $this->configModel->set('place_address', $result['place_address']);
        }

        // Simpan massal
        $batch = [];
        foreach ($result['reviews'] as $rev) {
            $rev['place_id']   = $placeId;
            $rev['place_name'] = $result['place_name'] ?? DEFAULT_PLACE_NAME;
            $batch[] = $rev;
        }
        $importedCount = $this->reviewModel->insertOrUpdateBatch($batch);

        if ($importedCount > 0) {
            $xlsUrl = url('review', 'exportXls', ['place_id' => $placeId]);
            $_SESSION['flash'] = [
                'type'        => 'success',
                'message'     => "Berhasil menyinkronkan <strong>{$importedCount} ulasan</strong> terbaru dari Google Maps untuk <strong>" . htmlspecialchars($result['place_name']) . "</strong> langsung ke database JSON!",
                'action_url'  => $xlsUrl,
                'action_text' => 'Download File XLS Sekarang'
            ];
        } else {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => "<strong>Sinkronisasi Tidak Berhasil: 0 Ulasan Diexport ke Database JSON!</strong><br><br>" .
                             "<strong>Kenapa ada info {$result['total_ratings']} ulasan tetapi tidak ke export?</strong><br>" .
                             "Angka <strong>" . number_format($result['total_ratings'], 0, ',', '.') . "</strong> adalah jumlah total rating publik yang tercatat pada profil Google Maps tempat <em>'" . htmlspecialchars($result['place_name']) . "'</em>. Namun, <strong>Google Cloud Places API (New)</strong> saat ini membatasi akun API publik sehingga Google <strong>TIDAK mengirimkan teks isi ulasan</strong> (kuota array ulasan = 0 dari Google API).<br><br>" .
                             "<strong>Solusi Pasti:</strong> Gunakan tab <strong>'Scraper Python (100% Gratis)'</strong> di modal ini. Masukkan Link Google Maps bisnis Anda, dan sistem akan langsung membuka browser untuk menarik seluruh teks ulasan ke database JSON tanpa dibatasi oleh API Google Cloud."
            ];
        }
        header('Location: ' . url('review', 'audit'));
        exit;
    }

    /**
     * Import ulasan dari file JSON atau CSV
     */
    public function importFile(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['review_file'])) {
            $file = $_FILES['review_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengunggah file. Kode error: ' . $file['error']];
                header('Location: ' . url('review', 'audit'));
                exit;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext === 'json') {
                $result = GoogleMapsApi::parseJsonFile($file['tmp_name']);
                if ($result['success']) {
                    $importedCount = $this->reviewModel->insertOrUpdateBatch($result['reviews']);
                    if ($importedCount > 0) {
                        $_SESSION['flash'] = [
                            'type'        => 'success',
                            'message'     => "Berhasil mengimpor <strong>{$importedCount} ulasan</strong> dari file JSON ke database!",
                            'action_url'  => url('review', 'exportXls'),
                            'action_text' => 'Download File XLS Sekarang'
                        ];
                    } else {
                        $_SESSION['flash'] = [
                            'type'    => 'danger',
                            'message' => 'Tidak berhasil: 0 ulasan yang berhasil diimpor dari file JSON tersebut.'
                        ];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => $result['message']];
                }
            } elseif ($ext === 'csv') {
                $importedCount = $this->parseAndImportCsv($file['tmp_name']);
                if ($importedCount > 0) {
                    $_SESSION['flash'] = [
                        'type'        => 'success',
                        'message'     => "Berhasil mengimpor <strong>{$importedCount} ulasan</strong> dari file CSV ke database JSON!",
                        'action_url'  => url('review', 'exportXls'),
                        'action_text' => 'Download File XLS Sekarang'
                    ];
                } else {
                    $_SESSION['flash'] = [
                        'type'    => 'danger',
                        'message' => 'Tidak berhasil: 0 ulasan yang berhasil diimpor dari file CSV tersebut.'
                    ];
                }
            } else {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Format file tidak didukung. Harap unggah file .json atau .csv.'];
            }
        }
        header('Location: ' . url('review', 'audit'));
        exit;
    }

    private function parseAndImportCsv(string $filePath): int {
        $handle = fopen($filePath, 'r');
        if (!$handle) return 0;

        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 2000, $delimiter);
        if (!$headers) {
            fclose($handle);
            return 0;
        }

        $colName = null; $colRating = null; $colText = null;
        $colTime = null; $colReply = null;  $colGuide = null;

        foreach ($headers as $idx => $h) {
            $hLower = strtolower(trim($h));
            if ($colName   === null && preg_match('/(author|reviewer|name|nama|user|pengulas)/i', $hLower))   $colName   = $idx;
            elseif ($colRating === null && preg_match('/(rating|star|bintang|score|nilai)/i', $hLower))       $colRating = $idx;
            elseif ($colText   === null && preg_match('/(review|text|comment|ulasan|pesan|snippet|content)/i', $hLower)) $colText = $idx;
            elseif ($colTime   === null && preg_match('/(time|date|tanggal|waktu|publish|created)/i', $hLower)) $colTime = $idx;
            elseif ($colReply  === null && preg_match('/(reply|response|balasan|tanggapan)/i', $hLower))      $colReply  = $idx;
            elseif ($colGuide  === null && preg_match('/(guide|level)/i', $hLower))                           $colGuide  = $idx;
        }

        $colName   = $colName   ?? 3;
        $colRating = $colRating ?? 5;
        $colText   = $colText   ?? 7;
        $colTime   = $colTime   ?? 1;

        $batch = [];

        while (($row = fgetcsv($handle, 4000, $delimiter)) !== false) {
            if (empty($row) || !isset($row[0])) continue;

            $name = trim($row[$colName] ?? $row[0] ?? 'Pengguna Google');
            if (empty($name)) continue;

            $ratingRaw = $row[$colRating] ?? 5;
            $rating = 5;
            if (is_numeric($ratingRaw)) {
                $rating = min(5, max(1, (int)$ratingRaw));
            } elseif (preg_match('/(\d)/', $ratingRaw, $m)) {
                $rating = (int)$m[1];
            }

            $text    = trim($row[$colText] ?? '');
            $timeRaw = $row[$colTime] ?? date('Y-m-d H:i:s');
            $timestamp = strtotime($timeRaw);
            $time    = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
            $reply   = ($colReply !== null && !empty($row[$colReply])) ? trim($row[$colReply]) : null;
            $isGuide = ($colGuide !== null && !empty($row[$colGuide])) ? 1 : 0;

            $batch[] = [
                'google_review_id' => 'CSV_' . md5($name . $time . uniqid()),
                'author_name'      => $name,
                'rating'           => $rating,
                'review_text'      => $text,
                'review_time'      => $time,
                'sentiment'        => ($rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative')),
                'is_local_guide'   => $isGuide,
                'owner_reply'      => $reply,
                'owner_reply_time' => $reply ? date('Y-m-d H:i:s') : null
            ];
        }
        fclose($handle);

        return $this->reviewModel->insertOrUpdateBatch($batch);
    }
}
