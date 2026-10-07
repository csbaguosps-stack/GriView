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
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->storeModel->delete($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Store berhasil dihapus dari sistem.'];
        }
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
