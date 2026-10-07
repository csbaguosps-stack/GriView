<?php
/* License: by cs.baguosps@gmail.com */
/**
 * AnalyticsController - Visualisasi data analitik ulasan bulanan & grafik statistik
 */
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/PlaceConfig.php';

class AnalyticsController {
    private Review $reviewModel;
    private Store $storeModel;
    private PlaceConfig $configModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->storeModel = new Store();
        $this->configModel = new PlaceConfig();
    }

    public function index(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_GET['reset'])) {
            unset($_SESSION['active_review_filter']);
            header('Location: ' . url('analytics', 'index'));
            exit;
        }

        // Ambil filter lengkap yang sinkron 100% dua arah dengan Audit Review
        $storeId     = $_GET['store_id']     ?? $_SESSION['active_review_filter']['store_id']     ?? 'all';
        $month       = $_GET['month']        ?? $_SESSION['active_review_filter']['month']        ?? 'all';
        $finding     = $_GET['finding']      ?? $_SESSION['active_review_filter']['finding']      ?? 'all';
        $rating      = $_GET['rating']       ?? $_SESSION['active_review_filter']['rating']       ?? 'all';
        $sentiment   = $_GET['sentiment']    ?? $_SESSION['active_review_filter']['sentiment']    ?? 'all';
        $replyStatus = $_GET['reply_status'] ?? $_SESSION['active_review_filter']['reply_status'] ?? 'all';
        $words       = $_GET['words']        ?? $_SESSION['active_review_filter']['words']        ?? 'all';
        $photos      = $_GET['photos']       ?? $_SESSION['active_review_filter']['photos']       ?? 'all';
        $search      = trim($_GET['search']  ?? ($_SESSION['active_review_filter']['search']      ?? ''));
        $sort        = $_GET['sort']         ?? $_SESSION['active_review_filter']['sort']         ?? 'date_desc';
        $page        = max(1, (int)($_GET['page'] ?? 1));
        $limit       = 15;

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

        // Stats & audit report yang 100% sinkron
        $stats = $this->reviewModel->getStats($filters);
        $summary = $stats['summary'] ?? $this->reviewModel->getAuditReport($filters)['summary'];
        $monthlyTrend = $this->reviewModel->getMonthlyTrend($storeId);
        $placeConfig = $this->configModel->getAll();

        // 5 Ulasan paling berbobot / terpanjang berdasarkan filter aktif
        $topWordReviews = $this->reviewModel->getAll(array_merge($filters, ['sort' => 'words_desc']), 1, 5);

        $viewData = [
            'storeId'         => $storeId,
            'selectedMonth'   => $month,
            'filters'         => $filters,
            'reviewFilters'   => $filters,
            'stores'          => $stores,
            'selectedStore'   => $selectedStore,
            'stats'           => $stats,
            'summary'         => $summary,
            'availableMonths' => $availableMonths,
            'monthlyTrend'    => $monthlyTrend,
            'placeConfig'     => $placeConfig,
            'topWordReviews'  => $topWordReviews,
            'reviews'         => $reviews,
            'totalReviews'    => $totalReviews,
            'page'            => $page,
            'limit'           => $limit,
            'totalPages'      => $totalPages,
            'flash'           => $_SESSION['flash'] ?? null
        ];
        unset($_SESSION['flash']);

        $this->render('analytics/index', $viewData);
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
