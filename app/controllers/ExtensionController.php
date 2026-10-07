<?php
/* License: by cs.baguosps@gmail.com */
/**
 * ExtensionController - Halaman Download & Dokumentasi GriView Chrome Extension
 */
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/PlaceConfig.php';

class ExtensionController {

    private Store $storeModel;
    private PlaceConfig $configModel;

    public function __construct() {
        $this->storeModel = new Store();
        $this->configModel = new PlaceConfig();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        $stores = $this->storeModel->getAll();
        $placeConfig = $this->configModel->getAll();

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/layouts/navbar.php';
        require_once __DIR__ . '/../views/' . $view . '.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function index(): void {
        $stores = $this->storeModel->getAll();
        $zipPath = __DIR__ . '/../../assets/griview-chrome-extension.zip';
        $zipExists = file_exists($zipPath);
        $zipSize = $zipExists ? round(filesize($zipPath) / 1024, 1) : 0;
        $zipModified = $zipExists ? date('d F Y, H:i', filemtime($zipPath)) : '-';

        $this->render('extension/index', [
            'stores' => $stores,
            'zipExists' => $zipExists,
            'zipSize' => $zipSize,
            'zipModified' => $zipModified,
            'flash' => $_SESSION['flash'] ?? null
        ]);
        unset($_SESSION['flash']);
    }

    public function download(): void {
        $zipPath = __DIR__ . '/../../assets/griview-chrome-extension.zip';
        $extDir = __DIR__ . '/../../chrome-extension';

        // Jika file zip belum ada atau lebih tua dari folder extension, re-generate
        if (!file_exists($zipPath) && is_dir($extDir)) {
            $cmd = 'Compress-Archive -Path "' . $extDir . '\*" -DestinationPath "' . $zipPath . '" -Force';
            @exec("powershell -Command " . escapeshellarg($cmd));
        }

        if (file_exists($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="griview-chrome-extension.zip"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            readfile($zipPath);
            exit;
        }

        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Berkas paket ZIP ekstensi belum tersedia di server.'
        ];
        header('Location: ' . url('extension', 'index'));
        exit;
    }

    /**
     * AJAX Endpoint: Menyelesaikan link Google Maps (short link / full URL)
     * Mengikuti HTTP 302 redirect, mengekstrak nama tempat, CID, dan mencocokkan cabang.
     */
    public function resolveUrl(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $input = trim($_REQUEST['url'] ?? '');
        if (empty($input)) {
            echo json_encode([
                'success' => false,
                'message' => 'Parameter URL atau nama tempat belum diisi.'
            ]);
            exit;
        }

        $allStores = $this->storeModel->getAll();
        $isUrl = (bool) preg_match('#^https?://#i', $input);

        // Jika bukan URL, anggap sebagai query nama tempat
        if (!$isUrl) {
            $matchedStore = $this->findMatchingStore($input, $allStores);
            echo json_encode([
                'success' => true,
                'is_url' => false,
                'original_input' => $input,
                'place_name' => $input,
                'cid' => '',
                'review_hash' => '',
                'final_url' => 'https://www.google.com/search?q=' . urlencode($input) . '&griview_auto_audit=1',
                'search_url' => 'https://www.google.com/search?q=' . urlencode($input) . '&griview_auto_audit=1',
                'matched_store' => $matchedStore
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Resolusi link via cURL HEAD request terlebih dahulu (sangat cepat ~400ms)
        $finalUrl = $input;
        $ch = curl_init($input);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if (!empty($effectiveUrl)) {
            $finalUrl = $effectiveUrl;
        }

        // Ekstraksi nama tempat dari pola URL canonical Google Maps (/place/Nama+Tempat/)
        $placeName = '';
        if (preg_match('#/(?:maps/)?place/([^/@?]+)#i', $finalUrl, $m)) {
            $placeName = urldecode(str_replace('+', ' ', $m[1]));
        }

        // Jika belum dapat dari URL, coba request GET singkat untuk membaca tag <title>
        if (empty($placeName)) {
            $ch = curl_init($input);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            $html = curl_exec($ch);
            $effectiveUrl2 = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            if (!empty($effectiveUrl2)) {
                $finalUrl = $effectiveUrl2;
            }

            if (preg_match('#/(?:maps/)?place/([^/@?]+)#i', $finalUrl, $m)) {
                $placeName = urldecode(str_replace('+', ' ', $m[1]));
            } elseif (preg_match('/<title>(.*?)<\/title>/i', (string)$html, $m)) {
                $t = html_entity_decode(trim($m[1]));
                $t = preg_replace('/\s*-\s*Google Maps.*$/i', '', $t);
                if (!empty($t) && !preg_match('/^google maps$/i', $t)) {
                    $placeName = $t;
                }
            }
        }

        // Ekstraksi Hex CID jika tersedia di URL data parameter: !1s(0x...:0x...)
        $cid = '';
        if (preg_match('#!1s(0x[0-9a-f]+:0x[0-9a-f]+)#i', $finalUrl, $m)) {
            $cid = $m[1];
        } elseif (preg_match('#[?&]cid=(\d+)#i', $finalUrl, $m)) {
            $cid = $m[1];
        }

        $reviewHash = !empty($cid) ? "#lrd={$cid},1,,," : '';

        // Tentukan query pencarian Google Search optimal
        $searchQuery = !empty($placeName) ? $placeName : $input;
        $searchUrl = 'https://www.google.com/search?q=' . urlencode($searchQuery) . '&hl=en-us&griview_auto_audit=1' . $reviewHash;

        // Cocokkan ke daftar cabang toko
        $matchedStore = $this->findMatchingStore($placeName ?: $input, $allStores);

        echo json_encode([
            'success' => true,
            'is_url' => true,
            'original_input' => $input,
            'final_url' => $finalUrl,
            'place_name' => $placeName,
            'cid' => $cid,
            'review_hash' => $reviewHash,
            'search_url' => $searchUrl,
            'matched_store' => $matchedStore
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Cocokkan nama tempat hasil resolusi dengan data cabang Winsee di database
     */
    private function findMatchingStore(string $text, array $stores): ?array {
        if (empty($text) || empty($stores)) return null;

        $cleanText = strtolower(preg_replace('/[^a-z0-9]/i', '', $text));

        // 1. Coba pencocokan presisi cabang (kata kunci unik seperti braga, dago, ciwalk, dsb)
        foreach ($stores as $st) {
            $sName = strtolower($st['store_name'] ?? '');
            $sCity = strtolower($st['city'] ?? '');
            $sCode = strtolower($st['store_code'] ?? '');

            // Ekstrak kata pembeda setelah tanda '-' jika ada (misal: "Optik Winsee - Braga Bandung" -> "Braga")
            $parts = explode('-', $st['store_name'] ?? '');
            $branchKeyword = trim(end($parts));
            $branchClean = strtolower(preg_replace('/[^a-z0-9]/i', '', $branchKeyword));

            if (!empty($branchClean) && strlen($branchClean) >= 4 && str_contains($cleanText, $branchClean)) {
                return $st;
            }

            // Cek nama lengkap
            $cleanStoreName = strtolower(preg_replace('/[^a-z0-9]/i', '', $sName));
            if (!empty($cleanStoreName) && (str_contains($cleanText, $cleanStoreName) || str_contains($cleanStoreName, $cleanText))) {
                return $st;
            }

            // Cek jika kode toko sama
            if (!empty($sCode) && strlen($sCode) >= 3 && str_contains($cleanText, $sCode)) {
                return $st;
            }
        }

        // 2. Cek kecocokan berdasarkan kota jika unik
        foreach ($stores as $st) {
            $cleanCity = strtolower(preg_replace('/[^a-z0-9]/i', '', $st['city'] ?? ''));
            if (!empty($cleanCity) && strlen($cleanCity) >= 5 && str_contains($cleanText, $cleanCity)) {
                return $st;
            }
        }

        return null;
    }
}
