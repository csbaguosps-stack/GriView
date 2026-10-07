<?php
/* License: by cs.baguosps@gmail.com */
/**
 * StoreController - Manajemen 13 Cabang/Store Winsee Optik
 */
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/Review.php';

class StoreController {
    private Store $storeModel;
    private Review $reviewModel;

    public function __construct() {
        $this->storeModel = new Store();
        $this->reviewModel = new Review();
    }

    public function index(): void {
        $selectedMonth = $_GET['month'] ?? 'all';
        $stores = $this->storeModel->getStoresWithStats($selectedMonth);
        $availableMonths = $this->reviewModel->getAvailableMonths();

        $viewData = [
            'stores' => $stores,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $availableMonths,
            'flash' => $_SESSION['flash'] ?? null
        ];
        unset($_SESSION['flash']);

        $this->render('stores/index', $viewData);
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['store_id'] ?? 0);
            $data = [
                'store_code' => trim($_POST['store_code'] ?? ''),
                'store_name' => trim($_POST['store_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? 'Bandung'),
                'gmaps_url' => trim($_POST['gmaps_url'] ?? ''),
                'place_id' => trim($_POST['place_id'] ?? '')
            ];

            if (empty($data['store_name'])) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nama store wajib diisi.'];
                header('Location: ' . url('store', 'index'));
                exit;
            }

            if ($id > 0) {
                $this->storeModel->update($id, $data);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data store berhasil diperbarui!'];
            } else {
                $this->storeModel->create($data);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Store baru berhasil ditambahkan!'];
            }
        }
        header('Location: ' . url('store', 'index'));
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            $this->storeModel->delete($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Cabang store berhasil dihapus dari sistem.'];
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: ' . url('store', 'index'));
        exit;
    }

    /**
     * Hapus batch cabang store yang ditandai
     */
    public function deleteBatch(): void {
        $ids = $_POST['store_ids'] ?? [];
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array)$ids));

        if (!empty($ids)) {
            $deletedCount = $this->storeModel->deleteBatch($ids);
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => "Sebanyak {$deletedCount} cabang store berhasil dihapus dari sistem."
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => 'Tidak ada cabang store yang dipilih untuk dihapus.'
            ];
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => count($ids)]);
            exit;
        }

        header('Location: ' . url('store', 'index'));
        exit;
    }

    /**
     * Hapus SEMUA cabang store sekaligus
     */
    public function deleteAll(): void {
        $this->storeModel->deleteAll();
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Semua data cabang store berhasil dikosongkan.'
        ];

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ' . url('store', 'index'));
        exit;
    }

    /**
     * Muat ulang data cabang contoh (demo)
     */
    public function seedSample(): void {
        $count = $this->storeModel->seedSamples();
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => "Berhasil memuat {$count} cabang contoh."
        ];
        header('Location: ' . url('store', 'index'));
        exit;
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
