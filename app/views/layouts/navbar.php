<?php
/* License: by cs.baguosps@gmail.com */
require_once __DIR__ . '/../../models/Store.php';
require_once __DIR__ . '/../../models/PlaceConfig.php';
$navbarStoreCount = count((new Store())->getAll());
$currentController = $_GET['c'] ?? 'review';
$currentAction = $_GET['a'] ?? $_GET['action'] ?? 'audit';
$globalAppConfig = (new PlaceConfig())->getAll();
$navAppName = htmlspecialchars($globalAppConfig['app_name'] ?? APP_NAME);
$navAppTagline = htmlspecialchars($globalAppConfig['app_tagline'] ?? APP_SUBTITLE);
$logoFile = ROOT_DIR . '/' . ($globalAppConfig['app_logo'] ?? '');
$hasNavLogo = !empty($globalAppConfig['app_logo']) && file_exists($logoFile);
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('review', 'audit') ?>">
            <?php if ($hasNavLogo): ?>
                <img src="<?= BASE_URL . '/' . $globalAppConfig['app_logo'] . '?v=' . filemtime($logoFile) ?>" alt="Logo" class="rounded shadow-xs" style="max-height: 38px; width: auto; object-fit: contain;">
            <?php else: ?>
                <div class="brand-icon">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="lh-1 fw-bold"><?= $navAppName ?></div>
                <small class="text-muted fw-normal" style="font-size: 0.72rem; letter-spacing: 0.3px;"><?= $navAppTagline ?></small>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'review' ? 'active' : '' ?>" href="<?= url('review', 'audit') ?>">
                        <i class="bi bi-shield-check me-1"></i> Audit Review
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'store' ? 'active' : '' ?>" href="<?= url('store', 'index') ?>">
                        <i class="bi bi-shop me-1"></i> Cabang Store (<?= $navbarStoreCount ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'analytics' ? 'active' : '' ?>" href="<?= url('analytics', 'index') ?>">
                        <i class="bi bi-graph-up me-1"></i> Analisis & Tren
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'extension' ? 'active' : '' ?>" href="<?= url('extension', 'index') ?>">
                        <i class="bi bi-puzzle me-1"></i> Extension Chrome
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'settings' ? 'active' : '' ?>" href="<?= url('settings', 'index') ?>">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <!-- Tombol Tarik Google Maps Modal -->
                <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSyncGoogle">
                    <i class="bi bi-google"></i>
                    <span>Tarik Review Google</span>
                </button>

                <!-- Tombol Tambah Review Manual Modal -->
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAddReview">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Ulasan</span>
                </button>
            </div>
        </div>
    </div>
</nav>
