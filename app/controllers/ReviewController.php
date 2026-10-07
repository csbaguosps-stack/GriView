<?php
/* License: by cs.baguosps@gmail.com */
/**
 * ReviewController - Mengatur alur data ulasan, filter bulanan, tampilan, dan export XLS
 */
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/PlaceConfig.php';
require_once __DIR__ . '/../helpers/ExcelHelper.php';

class ReviewController {
    private Review $reviewModel;
    private Store $storeModel;
    private PlaceConfig $configModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->storeModel = new Store();
        $this->configModel = new PlaceConfig();
    }

    /**
     * Halaman Utama - Sekarang disatukan langsung ke Audit Review
     */
    public function index(): void {
        $params = $_GET;
        $params['c'] = 'review';
        $params['a'] = 'audit';
        header('Location: ' . url('review', 'audit', $params));
        exit;
    }


    public function audit(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_GET['reset'])) {
            unset($_SESSION['active_review_filter']);
        }

        // Ambil filter dari request atau session (tersinkron 100% dengan Analytics)
        $storeId     = $_GET['store_id']     ?? $_SESSION['active_review_filter']['store_id']     ?? 'all';
        $month       = $_GET['month']        ?? $_SESSION['active_review_filter']['month']        ?? 'all';
        $rating      = $_GET['rating']       ?? $_SESSION['active_review_filter']['rating']       ?? 'all';
        $sentiment   = $_GET['sentiment']    ?? $_SESSION['active_review_filter']['sentiment']    ?? 'all';
        $replyStatus = $_GET['reply_status'] ?? $_SESSION['active_review_filter']['reply_status'] ?? 'all';
        $finding     = $_GET['finding']      ?? $_SESSION['active_review_filter']['finding']      ?? 'all';
        $words       = $_GET['words']        ?? $_SESSION['active_review_filter']['words']        ?? 'all';
        $photos      = $_GET['photos']       ?? $_SESSION['active_review_filter']['photos']       ?? 'all';
        $search      = trim($_GET['search']  ?? ($_SESSION['active_review_filter']['search']      ?? ''));
        $sort        = $_GET['sort']         ?? $_SESSION['active_review_filter']['sort']         ?? 'date_desc';
        $viewMode    = $_GET['view']         ?? 'table';
        $page        = max(1, (int)($_GET['page'] ?? 1));
        $limit       = 20;

        // Simpan state filter ke session
        $_SESSION['active_review_filter'] = [
            'store_id'     => $storeId,
            'month'        => $month,
            'rating'       => $rating,
            'sentiment'    => $sentiment,
            'reply_status' => $replyStatus,
            'finding'      => $finding,
            'words'        => $words,
            'photos'       => $photos,
            'search'       => $search,
            'sort'         => $sort
        ];

        $filters = [
            'store_id'     => $storeId,
            'month'        => $month,
            'rating'       => $rating,
            'sentiment'    => $sentiment,
            'reply_status' => $replyStatus,
            'finding'      => $finding,
            'words'        => $words,
            'photos'       => $photos,
            'search'       => $search,
            'sort'         => $sort
        ];

        $reviews = $this->reviewModel->getAll($filters, $page, $limit);
        $totalReviews = $this->reviewModel->count($filters);
        $totalPages = max(1, (int)ceil($totalReviews / $limit));

        $stores = $this->storeModel->getAll();
        $selectedStore = ($storeId !== 'all') ? $this->storeModel->getById((int)$storeId) : null;
        $availableMonths = $this->reviewModel->getAvailableMonths($storeId);
        $stats = $this->reviewModel->getStats($filters);
        $summary = $stats['summary'] ?? $this->reviewModel->getAuditReport($filters)['summary'];
        $placeConfig = $this->configModel->getAll();

        $lastAuditExport = file_exists(__DIR__ . '/../data/last_audit_export.json')
            ? json_decode(file_get_contents(__DIR__ . '/../data/last_audit_export.json'), true)
            : null;

        $viewData = [
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'page' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'filters' => $filters,
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            'availableMonths' => $availableMonths,
            'stats' => $stats,
            'summary' => $summary,
            'placeConfig' => $placeConfig,
            'viewMode' => $viewMode,
            'lastAuditExport' => $lastAuditExport,
            'activeTab' => 'audit',
            'flash' => $_SESSION['flash'] ?? null
        ];
        unset($_SESSION['flash']);

        $this->render('audit/index', $viewData);
    }

    /**
     * Endpoint API Sinkronisasi Otomatis dari Chrome Extension
     * Menerima ulasan hasil audit Google Maps, menyimpan ke database lokal,
     * dan otomatis menghasilkan berkas XLS hasil audit yang siap di-download.
     */
    public function syncAudit(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data || empty($data['reviews'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Data review kosong atau format JSON tidak valid.'
            ]);
            exit;
        }

        $placeName = trim($data['place_name'] ?? DEFAULT_PLACE_NAME);
        $placeAddress = trim($data['address'] ?? DEFAULT_PLACE_ADDRESS);
        $reviews = (array)$data['reviews'];

        // Cocokkan store ID dari stores.json
        $allStores = $this->storeModel->getAll();
        $targetStoreId = null;
        $targetStore = null;

        foreach ($allStores as $s) {
            if (mb_stripos($placeName, $s['store_name']) !== false || mb_stripos($s['store_name'], $placeName) !== false) {
                $targetStoreId = $s['id'];
                $targetStore = $s;
                break;
            }
        }

        if (!$targetStoreId && !empty($placeAddress)) {
            foreach ($allStores as $s) {
                if (!empty($s['address']) && mb_stripos($placeAddress, $s['address']) !== false) {
                    $targetStoreId = $s['id'];
                    $targetStore = $s;
                    break;
                }
            }
        }

        if (!$targetStoreId && !empty($allStores)) {
            $targetStoreId = $allStores[0]['id'];
            $targetStore = $allStores[0];
        }

        // Pastikan zona waktu selalu Asia/Jakarta (WIB)
        date_default_timezone_set('Asia/Jakarta');

        // Ambil waktu saat ini (WIB) atau dari client browser
        $baseTimestamp = time();
        if (!empty($data['client_time']) && is_numeric($data['client_time'])) {
            $cTs = (int)$data['client_time'];
            if ($cTs > 1000000000000) {
                $cTs = (int)round($cTs / 1000);
            }
            if ($cTs > 1000000000 && $cTs < 2500000000) {
                $baseTimestamp = $cTs;
            }
        }

        $savedAtStr = date('Y-m-d H:i:s', $baseTimestamp);
        if (!empty($data['client_saved_at']) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', trim($data['client_saved_at']))) {
            $savedAtStr = trim($data['client_saved_at']);
        }

        $records = [];
        $now = $savedAtStr;

        foreach ($reviews as $rev) {
            $author = trim($rev['author'] ?? 'Pengguna Google');
            $dateText = trim($rev['date'] ?? '');
            $text = trim($rev['text'] ?? '');
            $rating = (int)($rev['rating'] ?? 5);
            $reply = trim($rev['reply'] ?? '');
            $wc = isset($rev['wordCount']) && is_numeric($rev['wordCount']) 
                ? (int)$rev['wordCount'] 
                : Review::countWords($text);

            $calculatedTime = Review::parseReviewDate($dateText, $baseTimestamp);
            $reviewMonth = date('Y-m', strtotime($calculatedTime));

            $hash = md5($author . '|' . $dateText . '|' . mb_substr($text, 0, 40));
            $googleReviewId = 'GMAPS_' . $hash;

            $records[] = [
                'google_review_id' => $googleReviewId,
                'store_id' => $targetStoreId,
                'store_code' => $targetStore['store_code'] ?? null,
                'place_name' => $placeName,
                'place_id' => $targetStore['place_id'] ?? DEFAULT_PLACE_ID,
                'author_name' => $author,
                'rating' => $rating,
                'review_text' => $text,
                'word_count' => $wc,
                'reviewer_photo_count' => $rev['reviewerPhotoCount'] ?? null,
                'review_photo_count' => $rev['reviewPhotoCount'] ?? null,
                'review_time' => $calculatedTime,
                'review_month' => $reviewMonth,
                'review_date_text' => $dateText,
                'sentiment' => $rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative'),
                'owner_reply' => $reply ?: null,
                'owner_reply_time' => $reply ? $now : null
            ];
        }

        $savedCount = $this->reviewModel->insertOrUpdateBatch($records);

        $auditReport = $this->reviewModel->getAuditReport(['store_id' => $targetStoreId ?: 'all']);
        $summary = $auditReport['summary'];

        $exportDir = dirname(__DIR__) . '/data/exports';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $placeName);
        $fileTimestamp = date('Ymd_His', strtotime($savedAtStr));
        $filename = 'Review_Audit_' . $safeName . '_' . $fileTimestamp . '.xls';
        $filepath = str_replace('\\', '/', $exportDir . '/' . $filename);

        $meta = [
            'place_name' => $placeName,
            'place_address' => $placeAddress,
            'period_name' => 'Semua Periode'
        ];

        $xlsContent = ExcelHelper::exportAuditXls($auditReport['reviews'], $summary, $meta, true);
        file_put_contents($filepath, $xlsContent);

        $exportMeta = [
            'job_id' => $data['job_id'] ?? uniqid('audit_'),
            'place_name' => $placeName,
            'store_id' => $targetStoreId,
            'review_count' => count($records),
            'saved_count' => $savedCount,
            'saved_at' => $savedAtStr,
            'filename' => $filename,
            'filepath' => 'exports/' . $filename,
            'download_url' => url('review', 'downloadAuditXls')
        ];
        file_put_contents(dirname(__DIR__) . '/data/last_audit_export.json', json_encode($exportMeta, JSON_PRETTY_PRINT));

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => "<strong>Audit Google Maps Berhasil Disimpan Otomatis!</strong> {$savedCount} ulasan dari <strong>{$placeName}</strong> tersimpan rapi ke sistem dan file XLS siap di-download.",
            'action_url' => url('review', 'downloadAuditXls'),
            'action_text' => 'Download File XLS Audit'
        ];

        echo json_encode([
            'status' => 'success',
            'message' => "{$savedCount} ulasan berhasil disimpan otomatis ke GriView!",
            'saved_count' => $savedCount,
            'audit_url' => BASE_URL . '/index.php?c=review&a=audit',
            'download_xls_url' => BASE_URL . '/index.php?c=review&a=downloadAuditXls',
            'filename' => $filename
        ]);
        exit;
    }

    /**
     * Download langsung berkas XLS audit otomatis terakhir
     */
    public function downloadAuditXls(): void {
        $metaFile = dirname(__DIR__) . '/data/last_audit_export.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $targetPath = null;
            if (!empty($meta['filepath']) && file_exists($meta['filepath'])) {
                $targetPath = $meta['filepath'];
            } elseif (!empty($meta['filename'])) {
                $candidate = dirname(__DIR__) . '/data/exports/' . basename($meta['filename']);
                if (file_exists($candidate)) {
                    $targetPath = $candidate;
                }
            } elseif (!empty($meta['filepath'])) {
                $candidate = dirname(__DIR__) . '/data/' . ltrim($meta['filepath'], '/\\');
                if (file_exists($candidate)) {
                    $targetPath = $candidate;
                }
            }

            if ($targetPath && file_exists($targetPath)) {
                header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . basename($targetPath) . '"');
                header('Content-Length: ' . filesize($targetPath));
                header('Cache-Control: max-age=0');
                header('Pragma: public');
                readfile($targetPath);
                exit;
            }
        }

        $this->exportAuditXls();
    }

    /**
     * Export ulasan hasil audit ke format XLS
     */
    public function exportAuditXls(): void {
        $storeId   = $_GET['store_id']   ?? $_SESSION['active_review_filter']['store_id']   ?? 'all';
        $month     = $_GET['month']      ?? $_SESSION['active_review_filter']['month']      ?? 'all';
        $search    = trim($_GET['search'] ?? ($_SESSION['active_review_filter']['search']   ?? ''));
        $finding   = $_GET['finding']    ?? $_SESSION['active_review_filter']['finding']    ?? 'all';
        $rating    = $_GET['rating']     ?? $_SESSION['active_review_filter']['rating']     ?? 'all';
        $sentiment = $_GET['sentiment']  ?? $_SESSION['active_review_filter']['sentiment']  ?? 'all';
        $words     = $_GET['words']      ?? $_SESSION['active_review_filter']['words']      ?? 'all';
        $photos    = $_GET['photos']     ?? $_SESSION['active_review_filter']['photos']     ?? 'all';

        $filters = [
            'store_id'  => $storeId,
            'month'     => $month,
            'search'    => $search,
            'finding'   => $finding,
            'rating'    => $rating,
            'sentiment' => $sentiment,
            'words'     => $words,
            'photos'    => $photos,
            'sort'      => 'date_desc'
        ];
        $report = $this->reviewModel->getAuditReport($filters);
        $allReviews = $report['reviews'];
        $summary = $report['summary'];

        if ($finding === 'attention') {
            $allReviews = array_values(array_filter($allReviews, fn($r) => !empty($r['audit_findings'])));
        } elseif ($finding !== 'all') {
            $allReviews = array_values(array_filter($allReviews, function ($r) use ($finding) {
                return in_array($finding, array_column($r['audit_findings'] ?? [], 'key'), true);
            }));
        }

        $selectedStore = ($storeId !== 'all') ? $this->storeModel->getById((int)$storeId) : null;
        $meta = [
            'place_name' => $selectedStore ? $selectedStore['store_name'] : DEFAULT_PLACE_NAME . ' (Semua Cabang)',
            'place_address' => $selectedStore ? $selectedStore['address'] : DEFAULT_PLACE_ADDRESS,
            'period_name' => $month !== 'all' ? formatBulanIndo($month) : 'Semua Periode'
        ];

        ExcelHelper::exportAuditXls($allReviews, $summary, $meta);
    }

    /**
     * Endpoint JSON: Kembalikan flash session saat ini (untuk AJAX scraper)
     */
    public function getFlash(): void {
        header('Content-Type: application/json');
        $flash = $_SESSION['flash'] ?? null;
        if ($flash) {
            unset($_SESSION['flash']);
            echo json_encode($flash);
        } else {
            echo json_encode(['type' => 'success', 'message' => 'Scraping selesai dijalankan.']);
        }
        exit;
    }

    /**
     * Export ulasan ke format XLS (Excel) yang rapi
     */
    public function exportXls(): void {
        $storeId     = $_GET['store_id']     ?? $_SESSION['active_review_filter']['store_id']     ?? 'all';
        $month       = $_GET['month']        ?? $_SESSION['active_review_filter']['month']        ?? 'all';
        $rating      = $_GET['rating']       ?? $_SESSION['active_review_filter']['rating']       ?? 'all';
        $sentiment   = $_GET['sentiment']    ?? $_SESSION['active_review_filter']['sentiment']    ?? 'all';
        $replyStatus = $_GET['reply_status'] ?? $_SESSION['active_review_filter']['reply_status'] ?? 'all';
        $finding     = $_GET['finding']      ?? $_SESSION['active_review_filter']['finding']      ?? 'all';
        $words       = $_GET['words']        ?? $_SESSION['active_review_filter']['words']        ?? 'all';
        $photos      = $_GET['photos']       ?? $_SESSION['active_review_filter']['photos']       ?? 'all';
        $search      = trim($_GET['search']  ?? ($_SESSION['active_review_filter']['search']      ?? ''));
        $sort        = $_GET['sort']         ?? $_SESSION['active_review_filter']['sort']         ?? 'date_desc';

        $filters = [
            'store_id'     => $storeId,
            'month'        => $month,
            'rating'       => $rating,
            'sentiment'    => $sentiment,
            'reply_status' => $replyStatus,
            'finding'      => $finding,
            'words'        => $words,
            'photos'       => $photos,
            'search'       => $search,
            'sort'         => $sort
        ];

        // Ambil semua data tanpa pagination
        $reviews = $this->reviewModel->getAll($filters, 1, 10000);
        $stats = $this->reviewModel->getStats($filters);
        $placeConfig = $this->configModel->getAll();

        $selectedStore = ($storeId !== 'all') ? $this->storeModel->getById((int)$storeId) : null;
        $allStores = $this->storeModel->getAll();
        $storeCount = count($allStores);
        $brandName = DEFAULT_PLACE_NAME;
        $cities = array_unique(array_filter(array_column($allStores, 'city')));
        $cityStr = !empty($cities) ? implode(', ', $cities) : 'Indonesia';
        $placeName = $selectedStore ? $selectedStore['store_name'] : $brandName . ' (Semua ' . $storeCount . ' Cabang)';
        $placeAddress = $selectedStore ? $selectedStore['address'] : $storeCount . ' Cabang (' . $cityStr . ')';

        $meta = [
            'place_name' => $placeName,
            'place_address' => $placeAddress,
            'period_name' => ($month !== 'all') ? formatBulanIndo($month) : 'Semua Periode'
        ];

        ExcelHelper::exportXls($reviews, $stats, $meta);
    }

    /**
     * Export data ulasan ke format CSV
     */
    public function exportCsv(): void {
        $storeId     = $_GET['store_id']     ?? $_SESSION['active_review_filter']['store_id']     ?? 'all';
        $month       = $_GET['month']        ?? $_SESSION['active_review_filter']['month']        ?? 'all';
        $rating      = $_GET['rating']       ?? $_SESSION['active_review_filter']['rating']       ?? 'all';
        $sentiment   = $_GET['sentiment']    ?? $_SESSION['active_review_filter']['sentiment']    ?? 'all';
        $replyStatus = $_GET['reply_status'] ?? $_SESSION['active_review_filter']['reply_status'] ?? 'all';
        $finding     = $_GET['finding']      ?? $_SESSION['active_review_filter']['finding']      ?? 'all';
        $words       = $_GET['words']        ?? $_SESSION['active_review_filter']['words']        ?? 'all';
        $photos      = $_GET['photos']       ?? $_SESSION['active_review_filter']['photos']       ?? 'all';
        $search      = trim($_GET['search']  ?? ($_SESSION['active_review_filter']['search']      ?? ''));
        $sort        = $_GET['sort']         ?? $_SESSION['active_review_filter']['sort']         ?? 'date_desc';

        $filters = [
            'store_id'     => $storeId,
            'month'        => $month,
            'rating'       => $rating,
            'sentiment'    => $sentiment,
            'reply_status' => $replyStatus,
            'finding'      => $finding,
            'words'        => $words,
            'photos'       => $photos,
            'search'       => $search,
            'sort'         => $sort
        ];

        $reviews = $this->reviewModel->getAll($filters, 1, 10000);
        $meta = [
            'period_name' => ($month !== 'all') ? formatBulanIndo($month) : 'Semua'
        ];

        ExcelHelper::exportCsv($reviews, $meta);
    }

    /**
     * Balas ulasan pengunjung (Owner Reply)
     */
    public function reply(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['review_id'] ?? 0);
            $replyText = trim($_POST['reply_text'] ?? '');

            if ($id > 0 && !empty($replyText)) {
                $this->reviewModel->updateReply($id, $replyText);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Balasan owner berhasil disimpan dan diperbarui!'
                ];
            } else {
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'message' => 'Teks balasan tidak boleh kosong.'
                ];
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? url('review', 'audit');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Hapus ulasan
     */
    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->reviewModel->delete($id);
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Ulasan berhasil dihapus dari sistem.'
            ];
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? url('review', 'audit');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Hapus ulasan yang dipilih (Bulk/Multiple Delete)
     */
    public function deleteMultiple(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $_POST['review_ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $deletedCount = $this->reviewModel->deleteMultiple($ids);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => "Berhasil menghapus {$deletedCount} ulasan yang dipilih."
                ];
            } else {
                $_SESSION['flash'] = [
                    'type' => 'warning',
                    'message' => 'Tidak ada ulasan yang dipilih untuk dihapus.'
                ];
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? url('review', 'audit');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Hapus ulasan berdasarkan filter yang dipilih (atau semua jika tanpa filter)
     */
    public function deleteAll(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filters = [
                'store_id'     => $_POST['store_id'] ?? $_SESSION['active_review_filter']['store_id'] ?? 'all',
                'month'        => $_POST['month'] ?? $_SESSION['active_review_filter']['month'] ?? 'all',
                'rating'       => $_POST['rating'] ?? $_SESSION['active_review_filter']['rating'] ?? 'all',
                'sentiment'    => $_POST['sentiment'] ?? $_SESSION['active_review_filter']['sentiment'] ?? 'all',
                'reply_status' => $_POST['reply_status'] ?? $_SESSION['active_review_filter']['reply_status'] ?? 'all',
                'finding'      => $_POST['finding'] ?? $_SESSION['active_review_filter']['finding'] ?? 'all',
                'words'        => $_POST['words'] ?? $_SESSION['active_review_filter']['words'] ?? 'all',
                'photos'       => $_POST['photos'] ?? $_SESSION['active_review_filter']['photos'] ?? 'all',
                'search'       => trim($_POST['search'] ?? ($_SESSION['active_review_filter']['search'] ?? ''))
            ];

            $hasActiveFilter = false;
            foreach ($filters as $k => $v) {
                if ($v !== '' && $v !== 'all') {
                    $hasActiveFilter = true;
                    break;
                }
            }

            $deletedCount = $this->reviewModel->deleteByFilters($filters);

            if ($deletedCount > 0) {
                if ($hasActiveFilter) {
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'message' => "Sebanyak <strong>{$deletedCount} ulasan</strong> berdasarkan filter yang dipilih berhasil dihapus dari sistem."
                    ];
                } else {
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'message' => "Semua data ulasan ({$deletedCount} ulasan) berhasil dihapus dari sistem."
                    ];
                }
            } else {
                $_SESSION['flash'] = [
                    'type' => 'warning',
                    'message' => 'Tidak ada data ulasan yang cocok dengan filter untuk dihapus.'
                ];
            }

            // Sinkronkan metadata last_audit_export.json
            $metaFile = dirname(__DIR__) . '/data/last_audit_export.json';
            if (file_exists($metaFile)) {
                $remainingTotal = $this->reviewModel->count();
                if ($remainingTotal === 0) {
                    @unlink($metaFile);
                } else {
                    $meta = json_decode(file_get_contents($metaFile), true);
                    if ($meta) {
                        $meta['review_count'] = $remainingTotal;
                        $meta['saved_count'] = min((int)($meta['saved_count'] ?? $remainingTotal), $remainingTotal);
                        file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
                    }
                }
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? url('review', 'audit');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Tambah ulasan secara manual
     */
    public function addManual(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authorName = trim($_POST['author_name'] ?? '');
            $rating = (int)($_POST['rating'] ?? 5);
            $reviewText = trim($_POST['review_text'] ?? '');
            $reviewDate = $_POST['review_date'] ?? date('Y-m-d');
            $reviewTime = $_POST['review_time'] ?? date('H:i');
            $isLocalGuide = !empty($_POST['is_local_guide']) ? 1 : 0;
            $ownerReply = trim($_POST['owner_reply'] ?? '');

            if (!empty($authorName)) {
                $fullDateTime = $reviewDate . ' ' . $reviewTime . ':00';
                $sentiment = ($rating >= 4) ? 'positive' : (($rating == 3) ? 'neutral' : 'negative');

                $storeId = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;
                $placeName = DEFAULT_PLACE_NAME;
                $storeCode = null;
                if ($storeId) {
                    $st = $this->storeModel->getById($storeId);
                    if ($st) {
                        $placeName = $st['store_name'];
                        $storeCode = $st['store_code'];
                    }
                }

                $data = [
                    'google_review_id' => 'MANUAL_' . uniqid(),
                    'store_id' => $storeId,
                    'store_code' => $storeCode,
                    'place_name' => $placeName,
                    'author_name' => $authorName,
                    'rating' => $rating,
                    'review_text' => $reviewText,
                    'review_time' => $fullDateTime,
                    'sentiment' => $sentiment,
                    'is_local_guide' => $isLocalGuide,
                    'owner_reply' => !empty($ownerReply) ? $ownerReply : null,
                    'owner_reply_time' => !empty($ownerReply) ? date('Y-m-d H:i:s') : null
                ];

                $this->reviewModel->insertOrUpdate($data);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => "Ulasan baru berhasil ditambahkan untuk {$placeName}!"
                ];
            } else {
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'message' => 'Nama reviewer wajib diisi.'
                ];
            }
        }
        header('Location: ' . url('review', 'audit'));
        exit;
    }

    /**
     * Reset ke data demo ulasan lengkap
     */
    public function resetDemo(): void {
        require_once __DIR__ . '/../helpers/JsonDatabase.php';
        // Hapus semua ulasan
        $this->reviewModel->deleteAll();
        // Re-seed dari SampleData
        JsonDatabase::seedReviews();
        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Data ulasan demo berhasil dimuat ulang!'
        ];
        header('Location: ' . url('review', 'audit'));
        exit;
    }

    /**
     * Helper render view dengan template layout
     */
    private function render(string $viewPath, array $data = []): void {
        extract($data);
        $contentView = __DIR__ . '/../views/' . $viewPath . '.php';
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/layouts/navbar.php';
        require_once $contentView;
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
