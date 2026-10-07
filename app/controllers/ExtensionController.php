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
}
