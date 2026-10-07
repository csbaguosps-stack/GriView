<?php
/* License: by cs.baguosps@gmail.com */
$logoPath      = !empty($settings['app_logo']) && file_exists(ROOT_DIR . '/' . $settings['app_logo']) ? BASE_URL . '/' . $settings['app_logo'] . '?v=' . time() : null;
$faviconPath   = !empty($settings['app_favicon']) && file_exists(ROOT_DIR . '/' . $settings['app_favicon']) ? BASE_URL . '/' . $settings['app_favicon'] . '?v=' . time() : null;
$thumbnailPath = !empty($settings['app_thumbnail']) && file_exists(ROOT_DIR . '/' . $settings['app_thumbnail']) ? BASE_URL . '/' . $settings['app_thumbnail'] . '?v=' . time() : null;
$extIconPath   = file_exists(ROOT_DIR . '/chrome-extension/icons/icon-128.png') ? BASE_URL . '/chrome-extension/icons/icon-128.png?v=' . time() : null;
?>
<div class="container-fluid px-lg-4 py-4">

    <!-- Flash Alert Notification -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm mb-4 rounded-3 border" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill text-success' : 'bi-info-circle-fill text-primary' ?> fs-5 me-2.5"></i>
            <div class="fw-medium"><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold">
                    <i class="bi bi-sliders me-1"></i> Pengaturan Aplikasi
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    v<?= htmlspecialchars($settings['ext_version'] ?? '1.0.1') ?>
                </span>
            </div>
            <h3 class="fw-bold text-dark mt-1 mb-0">Pusat Branding & Konfigurasi Fungsi</h3>
            <p class="text-secondary small mb-0 mt-1">
                Kustomisasi logo, thumbnail, ikon ekstensi Chrome, serta preferensi alur kerja audit ulasan secara terpusat.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= url('settings', 'index', ['tab' => 'cleaner']) ?>" class="btn btn-outline-danger fw-semibold shadow-xs d-flex align-items-center gap-1.5" title="Buka menu pembersihan berkas sampah">
                <i class="bi bi-trash3-fill text-danger"></i>
                <span>Pembersih Sampah</span>
                <?php if (!empty($junkStats['total_count'])): ?>
                    <span class="badge bg-danger rounded-pill px-1.5 py-0.5" style="font-size: 0.72rem;"><?= $junkStats['total_count'] ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= url('settings', 'autoCompress') ?>" class="btn btn-outline-primary fw-semibold shadow-xs d-flex align-items-center gap-2" title="Jalankan kompresi otomatis (Level 9) untuk semua gambar dan paket ekstensi">
                <i class="bi bi-lightning-charge-fill text-primary"></i>
                <span>Auto-Kompres Aset</span>
            </a>
            <a href="<?= url('extension', 'download') ?>" class="btn btn-outline-success fw-semibold shadow-xs d-flex align-items-center gap-2" title="Unduh file ekstensi .ZIP terbaru">
                <i class="bi bi-file-earmark-zip-fill text-success"></i>
                <span>Download Ekstensi (<?= $zipSize ?> KB)</span>
            </a>
            <button type="button" class="btn btn-outline-secondary fw-semibold shadow-xs d-flex align-items-center gap-1.5" onclick="confirmResetDefaults()">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
        </div>
    </div>

    <?php 
    $isTabCleaner = ($activeTab ?? '') === 'cleaner';
    ?>

    <form action="<?= url('settings', 'save') ?>" method="POST" enctype="multipart/form-data" id="formSettings">
        
        <!-- Tab Navigasi Kategori -->
        <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 shadow-xs border" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $isTabCleaner ? '' : 'active' ?> fw-bold px-3 py-2 d-flex align-items-center gap-2 rounded-2" id="web-brand-tab" data-bs-toggle="pill" data-bs-target="#tab-web-brand" type="button" role="tab">
                    <i class="bi bi-palette text-primary"></i>
                    <span>Branding & Logo Web</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-3 py-2 d-flex align-items-center gap-2 rounded-2" id="ext-brand-tab" data-bs-toggle="pill" data-bs-target="#tab-ext-brand" type="button" role="tab">
                    <i class="bi bi-puzzle text-warning"></i>
                    <span>Ikon & Identitas Ekstensi</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-3 py-2 d-flex align-items-center gap-2 rounded-2" id="web-func-tab" data-bs-toggle="pill" data-bs-target="#tab-web-func" type="button" role="tab">
                    <i class="bi bi-gear-wide-connected text-success"></i>
                    <span>Fungsi & Fitur Web</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-3 py-2 d-flex align-items-center gap-2 rounded-2" id="ext-func-tab" data-bs-toggle="pill" data-bs-target="#tab-ext-func" type="button" role="tab">
                    <i class="bi bi-lightning-charge text-danger"></i>
                    <span>Fungsi Ekstensi Chrome</span>
                </button>
            </li>
            <li class="nav-item ms-md-auto" role="presentation">
                <button class="nav-link <?= $isTabCleaner ? 'active' : '' ?> fw-bold px-3 py-2 d-flex align-items-center gap-2 rounded-2" id="cleaner-tab" data-bs-toggle="pill" data-bs-target="#tab-cleaner" type="button" role="tab">
                    <i class="bi bi-trash3 text-danger"></i>
                    <span>Pembersihan Sampah</span>
                    <?php if (!empty($junkStats['total_count'])): ?>
                        <span class="badge bg-danger rounded-pill px-2 py-0.5" style="font-size: 0.68rem;"><?= $junkStats['total_count'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">0</span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">

            <!-- ========================================================= -->
            <!-- TAB 1: BRANDING & LOGO WEB -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= $isTabCleaner ? '' : 'show active' ?>" id="tab-web-brand" role="tabpanel">
                <div class="row g-4">
                    
                    <!-- Form Input Logo, Favicon, Thumbnail -->
                    <div class="col-12 col-xl-7">
                        <div class="card-custom p-4 mb-4">
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-image text-primary"></i> Aset Visual Website
                            </h5>
                            <p class="text-secondary small mb-4">
                                Unggah logo, favicon, dan thumbnail untuk mempersonalisasi tampilan web sesuai brand Anda.
                            </p>

                            <!-- 1. Logo Web -->
                            <div class="alert alert-info py-2 px-3 rounded-3 small mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
                                <div>
                                    <strong>Auto-Kompres Aktif:</strong> Semua gambar yang Anda unggah otomatis di-resize dan dikompresi ke Level 9 (optimal tanpa blur). Jika favicon browser belum berubah setelah disimpan, tekan <kbd>Ctrl</kbd> + <kbd>F5</kbd> untuk hard refresh cache Chrome.
                                </div>
                            </div>
                            <div class="border rounded-3 p-3 mb-3.5 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <label class="form-label fw-bold mb-0">Logo Utama Website (Navbar & Kop)</label>
                                        <div class="text-muted small">Tampil di sudut kiri navbar dan header laporan ekspor. Rekomendasi: PNG transparan, rasio horizontal.</div>
                                    </div>
                                    <?php if ($logoPath): ?>
                                        <a href="<?= url('settings', 'removeAsset', ['type' => 'logo']) ?>" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Hapus logo dan kembali ke ikon bawaan" onclick="return confirm('Hapus logo web kustom?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="app_logo" id="inputAppLogo" class="form-control" accept="image/png,image/jpeg,image/svg+xml,image/webp" onchange="previewImage(this, 'previewLogoImg')">
                                </div>
                            </div>

                            <!-- 2. Favicon Browser -->
                            <div class="border rounded-3 p-3 mb-3.5 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <label class="form-label fw-bold mb-0">Favicon Tab Browser</label>
                                        <div class="text-muted small">Ikon kecil yang muncul di tab browser pengguna (ukuran 32x32 atau 64x64 px). Format: ICO, PNG, SVG.</div>
                                    </div>
                                    <?php if ($faviconPath): ?>
                                        <a href="<?= url('settings', 'removeAsset', ['type' => 'favicon']) ?>" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Hapus favicon dan kembali ke ikon bawaan" onclick="return confirm('Hapus favicon kustom?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="app_favicon" id="inputAppFavicon" class="form-control" accept="image/x-icon,image/png,image/svg+xml" onchange="previewImage(this, 'previewFaviconImg')">
                                </div>
                            </div>

                            <!-- 3. Thumbnail / Og:Image (Social Share) -->
                            <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <label class="form-label fw-bold mb-0">Thumbnail Banner (Social Share / Og:Image)</label>
                                        <div class="text-muted small">Gambar kartu pratinjau saat tautan web dibagikan ke WhatsApp, LinkedIn, atau media sosial (1200x630 px).</div>
                                    </div>
                                    <?php if ($thumbnailPath): ?>
                                        <a href="<?= url('settings', 'removeAsset', ['type' => 'thumbnail']) ?>" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Hapus thumbnail" onclick="return confirm('Hapus thumbnail web?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="app_thumbnail" id="inputAppThumbnail" class="form-control" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'previewThumbnailImg')">
                                </div>
                            </div>
                        </div>

                        <!-- Teks & Identitas Web -->
                        <div class="card-custom p-4">
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-fonts text-primary"></i> Nama & Teks Identitas Web
                            </h5>
                            <p class="text-secondary small mb-3">
                                Atur nama aplikasi dan slogan yang muncul pada judul halaman dan navbar.
                            </p>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Aplikasi Web <span class="text-danger">*</span></label>
                                <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name'] ?? 'GriView') ?>" required>
                                <div class="form-text">Nama utama brand (contoh: <code>GriView</code>, <code>Bisnis Review Hub</code>).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Slogan / Tagline Web</label>
                                <input type="text" name="app_tagline" class="form-control" value="<?= htmlspecialchars($settings['app_tagline'] ?? 'Review audit & reputation workflow') ?>">
                                <div class="form-text">Deskripsi singkat di bawah nama brand pada navbar dan judul tab.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Grup Bisnis / Jaringan Usaha</label>
                                <input type="text" name="business_group" class="form-control" value="<?= htmlspecialchars($settings['business_group'] ?? 'Semua Cabang') ?>" placeholder="Contoh: Semua Cabang, Retail Group, dsb">
                                <div class="form-text">Nama kelompok cabang bisnis yang ditampilkan pada badge status profil (contoh: <code>Semua Cabang</code>, <code>Grup Bisnis</code>).</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold">Teks Footer Dokumen</label>
                                <input type="text" name="app_footer" class="form-control" value="<?= htmlspecialchars($settings['app_footer'] ?? 'GriView - Google Business Review Audit & Analytics') ?>">
                                <div class="form-text">Dicantumkan pada bagian catatan bawah berkas ekspor XLS.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Mockup Preview Column -->
                    <div class="col-12 col-xl-5">
                        <div class="card-custom p-4 mb-4">
                            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-eye-fill text-primary"></i> Pratinjau Langsung (Live Preview)
                            </h5>

                            <!-- Preview 1: Mockup Navbar Web -->
                            <div class="mb-4">
                                <div class="small fw-bold text-secondary text-uppercase mb-2">1. Pratinjau Navbar Website:</div>
                                <div class="p-3 bg-dark rounded-3 border d-flex align-items-center gap-2 shadow-sm text-white">
                                    <div id="previewLogoWrapper" class="d-flex align-items-center">
                                        <?php if ($logoPath): ?>
                                            <img src="<?= $logoPath ?>" id="previewLogoImg" alt="Logo" class="rounded" style="max-height: 38px; width: auto; object-fit: contain;">
                                        <?php else: ?>
                                            <img src="" id="previewLogoImg" alt="Logo" class="rounded d-none" style="max-height: 38px; width: auto; object-fit: contain;">
                                            <div id="previewLogoFallback" class="brand-icon shadow-xs">
                                                <i class="bi bi-geo-alt-fill"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="lh-1 fw-bold text-white"><?= htmlspecialchars($settings['app_name'] ?? 'GriView') ?></div>
                                        <small class="text-white-50" style="font-size: 0.72rem;"><?= htmlspecialchars($settings['app_tagline'] ?? 'Review audit & reputation workflow') ?></small>
                                    </div>
                                    <div class="ms-auto badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                        Audit Review
                                    </div>
                                </div>
                            </div>

                            <!-- Preview 2: Mockup Tab Browser -->
                            <div class="mb-4">
                                <div class="small fw-bold text-secondary text-uppercase mb-2">2. Pratinjau Tab Browser:</div>
                                <div class="bg-light p-2 rounded-3 border">
                                    <div class="bg-white rounded-2 px-3 py-2 border shadow-xs d-inline-flex align-items-center gap-2" style="max-width: 260px;">
                                        <?php if ($faviconPath): ?>
                                            <img src="<?= $faviconPath ?>" id="previewFaviconImg" alt="Favicon" style="width: 18px; height: 18px; object-fit: contain;">
                                        <?php else: ?>
                                            <img src="" id="previewFaviconImg" alt="Favicon" class="d-none" style="width: 18px; height: 18px; object-fit: contain;">
                                            <div id="previewFaviconFallback" style="width: 18px; height: 18px; background: #4f46e5; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px;">
                                                <i class="bi bi-geo-alt-fill"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="small fw-semibold text-truncate text-dark">
                                            <?= htmlspecialchars($settings['app_name'] ?? 'GriView') ?> - Audit
                                        </span>
                                        <i class="bi bi-x ms-auto text-muted"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview 3: Mockup Banner Thumbnail -->
                            <div>
                                <div class="small fw-bold text-secondary text-uppercase mb-2">3. Pratinjau Banner / Social Card:</div>
                                <div class="rounded-3 border overflow-hidden bg-white shadow-xs">
                                    <div style="height: 140px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; align-items: center; justify-content: center; overflow: hidden;" id="previewThumbnailContainer">
                                        <?php if ($thumbnailPath): ?>
                                            <img src="<?= $thumbnailPath ?>" id="previewThumbnailImg" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="" id="previewThumbnailImg" alt="Thumbnail" class="d-none" style="width: 100%; height: 100%; object-fit: cover;">
                                            <div id="previewThumbnailFallback" class="text-center text-white-50 p-3">
                                                <i class="bi bi-image fs-1 d-block mb-1 text-secondary"></i>
                                                <span class="small">Belum ada thumbnail (1200x630)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3">
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($settings['app_name'] ?? 'GriView') ?> — Review Audit Platform</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($settings['app_tagline'] ?? '') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: BRANDING & IKON EKSTENSI CHROME -->
            <!-- ========================================================= -->
            <div class="tab-pane fade" id="tab-ext-brand" role="tabpanel">
                <div class="row g-4">
                    <div class="col-12 col-xl-7">
                        <div class="card-custom p-4 mb-4">
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-puzzle text-warning"></i> Ikon & Informasi Ekstensi
                            </h5>
                            <p class="text-secondary small mb-4">
                                Saat Anda mengunggah gambar ikon baru, sistem akan otomatis menghasilkan ukuran standar (16px, 48px, 128px), memperbarui <code>manifest.json</code>, dan menyusun ulang berkas ZIP installer.
                            </p>

                            <!-- Upload Icon Ekstensi -->
                            <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <label class="form-label fw-bold mb-0">Unggah Ikon Ekstensi (PNG / JPG Persegi)</label>
                                        <div class="text-muted small">Rekomendasi: PNG persegi min. 128x128 px (transparan disarankan). Sistem otomatis meresize untuk toolbar Chrome.</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <a href="<?= url('settings', 'syncExtIcon') ?>" class="btn btn-sm btn-outline-warning text-dark fw-bold py-0.5 px-2" title="Samakan ikon ekstensi langsung dari Favicon / Logo Web">
                                            <i class="bi bi-magic text-warning me-1"></i> Samakan dari Favicon
                                        </a>
                                        <?php if ($extIconPath): ?>
                                            <a href="<?= url('settings', 'removeAsset', ['type' => 'ext_icon']) ?>" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Kembalikan ke ikon standar GriView" onclick="return confirm('Kembalikan ikon ekstensi ke bawaan?');">
                                                <i class="bi bi-arrow-counterclockwise"></i> Default
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="ext_icon" id="inputExtIcon" class="form-control" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'previewExtIconImg')">
                                </div>
                            </div>

                            <!-- Meta Ekstensi -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Ekstensi Chrome <span class="text-danger">*</span></label>
                                <input type="text" name="ext_name" class="form-control" value="<?= htmlspecialchars($settings['ext_name'] ?? 'GriView Review Audit') ?>" required>
                                <div class="form-text">Nama yang tampil pada daftar ekstensi di <code>chrome://extensions</code> dan toolbar.</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Versi Ekstensi</label>
                                    <input type="text" name="ext_version" class="form-control" value="<?= htmlspecialchars($settings['ext_version'] ?? '1.0.1') ?>" required>
                                    <div class="form-text">Format: <code>X.Y.Z</code></div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Deskripsi Singkat Ekstensi</label>
                                    <input type="text" name="ext_description" class="form-control" value="<?= htmlspecialchars($settings['ext_description'] ?? 'Audit hingga 1.000 ulasan Google Maps dengan scroll otomatis.') ?>">
                                    <div class="form-text">Penjelasan fungsi ekstensi pada toko / browser.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Paket ZIP -->
                        <div class="card-custom p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-zip text-success me-2"></i>Status Berkas Installer ZIP</h6>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Siap Diunduh</span>
                            </div>
                            <p class="small text-secondary mb-3">
                                File <code>assets/griview-chrome-extension.zip</code> selalu sinkron otomatis setiap kali Anda menekan tombol Simpan di bawah.
                            </p>
                            <div class="p-3 bg-light rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2 small mb-3">
                                <div>
                                    <div class="fw-semibold text-dark">Ukuran Berkas: <strong><?= $zipSize ?> KB</strong></div>
                                    <div class="text-muted">Terakhir Diperbarui: <?= htmlspecialchars($zipModified) ?></div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?= url('extension', 'download') ?>" class="btn btn-sm btn-success fw-semibold">
                                        <i class="bi bi-download me-1"></i> Unduh ZIP
                                    </a>
                                    <a href="<?= url('settings', 'rebuildZip') ?>" class="btn btn-sm btn-outline-primary fw-semibold" title="Paksa kompres ulang ZIP">
                                        <i class="bi bi-arrow-repeat me-1"></i> Re-pack ZIP
                                    </a>
                                </div>
                            </div>

                            <!-- Petunjuk Update ke Chrome -->
                            <div class="alert alert-warning py-3 px-3.5 rounded-3 border-warning-subtle bg-warning-subtle small mb-0">
                                <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-info-circle-fill text-warning fs-6"></i>
                                    <span>Kenapa Ikon Belum Berubah di Google Chrome?</span>
                                </div>
                                <p class="text-secondary mb-2">
                                    Google Chrome tidak memuat ulang file ekstensi dari folder secara otomatis. Untuk menerapkan ikon & nama terbaru:
                                </p>
                                <ol class="mb-2 ps-3 text-dark">
                                    <li>Buka tab browser baru dan ketik: <code>chrome://extensions</code></li>
                                    <li>Jika tombol merah <strong class="text-danger">"Errors"</strong> menyala, klik lalu tekan <strong>"Clear all"</strong> (sudah dibersihkan).</li>
                                    <li>Klik tombol <strong>Reload (🔄)</strong> pada kartu ekstensi <strong>GriView Review Audit</strong>.</li>
                                </ol>
                                <div class="text-success fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Ikon, nama, dan versi 1.0.1 akan langsung aktif seketika di Chrome!
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mockup Preview Ekstensi -->
                    <div class="col-12 col-xl-5">
                        <div class="card-custom p-4 mb-4">
                            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-laptop text-primary"></i> Pratinjau di Google Chrome
                            </h5>

                            <!-- Mockup 1: Toolbar Chrome -->
                            <div class="mb-4">
                                <div class="small fw-bold text-secondary text-uppercase mb-2">1. Ikon di Toolbar Browser:</div>
                                <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                                    <div class="bg-white border rounded px-3 py-1.5 flex-grow-1 small text-muted text-truncate d-flex align-items-center gap-2 shadow-xs">
                                        <i class="bi bi-lock-fill text-success" style="font-size: 11px;"></i>
                                        <span>https://www.google.com/maps/...</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 border-start ps-3">
                                        <div class="p-1.5 rounded-2 bg-white border shadow-xs d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="<?= htmlspecialchars($settings['ext_name'] ?? 'GriView') ?>">
                                            <?php if ($extIconPath): ?>
                                                <img src="<?= $extIconPath ?>" id="previewExtIconImg" alt="Ext Icon" style="width: 24px; height: 24px; object-fit: contain;">
                                            <?php else: ?>
                                                <img src="" id="previewExtIconImg" alt="Ext Icon" class="d-none" style="width: 24px; height: 24px; object-fit: contain;">
                                                <div id="previewExtIconFallback" style="width: 22px; height: 22px; background: #4f46e5; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: bold;">
                                                    G
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mockup 2: Kartu chrome://extensions -->
                            <div>
                                <div class="small fw-bold text-secondary text-uppercase mb-2">2. Kartu di chrome://extensions:</div>
                                <div class="p-3 bg-white rounded-3 border shadow-xs">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="p-2 rounded-3 bg-light border d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                            <?php if ($extIconPath): ?>
                                                <img src="<?= $extIconPath ?>" id="previewExtIconLarge" alt="Ext Icon Large" style="width: 38px; height: 38px; object-fit: contain;">
                                            <?php else: ?>
                                                <div style="width: 36px; height: 36px; background: #4f46e5; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; font-weight: bold;">
                                                    G
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($settings['ext_name'] ?? 'GriView Review Audit') ?> <span class="text-muted fw-normal small">v<?= htmlspecialchars($settings['ext_version'] ?? '1.0.1') ?></span></div>
                                            <div class="text-muted small mt-1" style="font-size: 0.8rem; line-height: 1.35;"><?= htmlspecialchars($settings['ext_description'] ?? 'Audit hingga 1.000 ulasan Google Maps.') ?></div>
                                            <div class="mt-2.5 d-flex align-items-center gap-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle small py-1 px-2">Aktif (Enabled)</span>
                                                <span class="badge bg-light text-secondary border small py-1 px-2">Manifest V3</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: FUNGSI & FITUR WEB -->
            <!-- ========================================================= -->
            <div class="tab-pane fade" id="tab-web-func" role="tabpanel">
                <div class="card-custom p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-gear-wide-connected text-success"></i> Konfigurasi Fitur & Preferensi Web
                    </h5>
                    <p class="text-secondary small mb-4">
                        Sesuaikan parameter pencarian, batas teks ulasan singkat, format ekspor dokumen, dan pengaturan domain produksi massal.
                    </p>

                    <div class="row g-4">
                        <!-- Default Scrape Limit -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jumlah Ulasan Bawaan saat Modal Audit Dibuka</label>
                            <select name="default_scrape_limit" class="form-select">
                                <option value="50"   <?= ($settings['default_scrape_limit'] ?? '') === '50'   ? 'selected' : '' ?>>50 Ulasan Terbaru</option>
                                <option value="100"  <?= ($settings['default_scrape_limit'] ?? '') === '100'  ? 'selected' : '' ?>>100 Ulasan Terbaru</option>
                                <option value="200"  <?= ($settings['default_scrape_limit'] ?? '') === '200'  ? 'selected' : '' ?>>200 Ulasan Terbaru</option>
                                <option value="500"  <?= ($settings['default_scrape_limit'] ?? '') === '500'  ? 'selected' : '' ?>>500 Ulasan Terbaru</option>
                                <option value="1000" <?= ($settings['default_scrape_limit'] ?? '1000') === '1000' ? 'selected' : '' ?>>Maksimal 1.000 Ulasan (Rekomendasi)</option>
                            </select>
                            <div class="form-text">Nilai terpilih otomatis saat Anda mengklik tombol "Tarik Review Google".</div>
                        </div>

                        <!-- Format Ekspor Default -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Format Ekspor Unduhan Bawaan</label>
                            <select name="default_export_format" class="form-select">
                                <option value="xls" <?= ($settings['default_export_format'] ?? 'xls') === 'xls' ? 'selected' : '' ?>>Microsoft Excel (.XLS Terformat & Berwarna)</option>
                                <option value="csv" <?= ($settings['default_export_format'] ?? '') === 'csv' ? 'selected' : '' ?>>Comma-Separated Values (.CSV Standar)</option>
                            </select>
                            <div class="form-text">Format dokumen yang diunduh saat menekan tombol Export cepat.</div>
                        </div>

                        <!-- Ambang Batas Ulasan Singkat -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ambang Batas Ulasan Singkat (Short Text Threshold)</label>
                            <div class="input-group">
                                <input type="number" name="short_text_threshold" class="form-control" value="<?= (int)($settings['short_text_threshold'] ?? 40) ?>" min="5" max="500">
                                <span class="input-group-text bg-light">karakter</span>
                            </div>
                            <div class="form-text">Ulasan dengan panjang teks di bawah angka ini akan otomatis ditandai sebagai temuan audit <strong>"Teks singkat"</strong>.</div>
                        </div>

                        <!-- Filter Foto Bawaan -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Default Filter Foto di Halaman Audit</label>
                            <select name="default_photo_filter" class="form-select">
                                <option value="all"       <?= ($settings['default_photo_filter'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Ulasan</option>
                                <option value="has_photo" <?= ($settings['default_photo_filter'] ?? '') === 'has_photo' ? 'selected' : '' ?>>Hanya Ulasan yang Memiliki Foto</option>
                                <option value="0"         <?= ($settings['default_photo_filter'] ?? '') === '0' ? 'selected' : '' ?>>Hanya Ulasan Tanpa Foto (0 foto)</option>
                            </select>
                            <div class="form-text">Filter aktif awal saat membuka halaman Audit Review atau Analisis.</div>
                        </div>

                        <!-- Custom Production Base URL -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <label class="form-label fw-bold mb-1">
                                    <i class="bi bi-globe me-1 text-primary"></i> Base URL Kustom untuk Produksi Massal (Opsional)
                                </label>
                                <input type="url" name="custom_base_url" class="form-control" placeholder="Contoh: https://griview.domainanda.com" value="<?= htmlspecialchars($settings['custom_base_url'] ?? '') ?>">
                                <div class="form-text">
                                    Biarkan kosong untuk menggunakan deteksi URL otomatis (<code><?= BASE_URL ?></code>). Isi kolom ini jika aplikasi berada di balik reverse proxy atau jika ingin memaksa domain produksi tertentu agar ekstensi Chrome selalu sinkron ke alamat tersebut.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: FUNGSI EKSTENSI CHROME -->
            <!-- ========================================================= -->
            <div class="tab-pane fade" id="tab-ext-func" role="tabpanel">
                <div class="card-custom p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge text-danger"></i> Perilaku & Kecepatan Audit Ekstensi
                    </h5>
                    <p class="text-secondary small mb-4">
                        Atur kecepatan pengguliran otomatis Google Maps dan otomatisasi pasca-audit untuk efisiensi maksimal tanpa terkena pembatasan rate limit Google.
                    </p>

                    <!-- Pilihan Kecepatan Scroll -->
                    <div class="mb-4">
                        <label class="form-label fw-bold d-block mb-2">Kecepatan Auto-Scroll Saat Membaca Ulasan:</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 d-block cursor-pointer bg-light-subtle h-100">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <input type="radio" name="ext_scroll_speed" value="fast" class="form-check-input mt-0" <?= ($settings['ext_scroll_speed'] ?? '') === 'fast' ? 'checked' : '' ?>>
                                        <strong class="text-dark">⚡ Cepat (Fast)</strong>
                                    </div>
                                    <div class="small text-muted ps-4">Jeda ~1.2 detik per gulir. Cocok untuk ulasan di bawah 200 dengan koneksi internet kencang.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 d-block cursor-pointer bg-light-subtle h-100 border-primary shadow-xs">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <input type="radio" name="ext_scroll_speed" value="normal" class="form-check-input mt-0" <?= ($settings['ext_scroll_speed'] ?? 'normal') === 'normal' ? 'checked' : '' ?>>
                                        <strong class="text-primary">⚖️ Seimbang (Rekomendasi)</strong>
                                    </div>
                                    <div class="small text-muted ps-4">Jeda ~2.0 detik per gulir. Paling stabil dan aman untuk mengaudit hingga 1.000 ulasan.</div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="border rounded-3 p-3 d-block cursor-pointer bg-light-subtle h-100">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <input type="radio" name="ext_scroll_speed" value="relaxed" class="form-check-input mt-0" <?= ($settings['ext_scroll_speed'] ?? '') === 'relaxed' ? 'checked' : '' ?>>
                                        <strong class="text-dark">🛡️ Santai & Aman (Relaxed)</strong>
                                    </div>
                                    <div class="small text-muted ps-4">Jeda ~3.2 detik per gulir. Pilihan paling aman untuk server Google yang sedang padat atau IP kantor bersama.</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Fitur Sakelar Otomatisasi (Switches) -->
                    <h6 class="fw-bold text-dark mb-3">Otomatisasi Pasca-Audit Ekstensi:</h6>
                    <div class="d-flex flex-column gap-3">
                        <!-- Auto Save -->
                        <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 border rounded-3 p-3 bg-light-subtle">
                            <div>
                                <label class="form-check-label fw-bold text-dark mb-0" for="switchAutoSave">
                                    Simpan Otomatis ke Database GriView Web (Auto-Save)
                                </label>
                                <div class="text-muted small">Begitu audit selesai, ulasan langsung dikirim dan disimpan ke database web tanpa perlu tombol simpan manual.</div>
                            </div>
                            <input class="form-check-input ms-3 fs-5" type="checkbox" name="ext_auto_save" id="switchAutoSave" value="1" <?= ($settings['ext_auto_save'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>

                        <!-- Auto Open Result -->
                        <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 border rounded-3 p-3 bg-light-subtle">
                            <div>
                                <label class="form-check-label fw-bold text-dark mb-0" for="switchAutoOpen">
                                    Otomatis Buka Tab Halaman Laporan Audit (Review Audit UI)
                                </label>
                                <div class="text-muted small">Ketika 1.000 ulasan selesai diaudit, tab Google Maps otomatis beralih ke halaman ringkasan audit.</div>
                            </div>
                            <input class="form-check-input ms-3 fs-5" type="checkbox" name="ext_auto_open_result" id="switchAutoOpen" value="1" <?= ($settings['ext_auto_open_result'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>

                        <!-- Auto Expand 'More' -->
                        <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 border rounded-3 p-3 bg-light-subtle">
                            <div>
                                <label class="form-check-label fw-bold text-dark mb-0" for="switchExpandMore">
                                    Otomatis Membuka Komentar Panjang ('Selengkapnya / More')
                                </label>
                                <div class="text-muted small">Ekstensi akan otomatis mengklik tautan selengkapnya agar isi ulasan tidak terpotong saat dihitung jumlah katanya.</div>
                            </div>
                            <input class="form-check-input ms-3 fs-5" type="checkbox" name="ext_auto_expand_more" id="switchExpandMore" value="1" <?= ($settings['ext_auto_expand_more'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>

                        <!-- Detect Photos -->
                        <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0 border rounded-3 p-3 bg-light-subtle">
                            <div>
                                <label class="form-check-label fw-bold text-dark mb-0" for="switchDetectPhotos">
                                    Deteksi Jumlah Foto Ulasan & Foto Profil Kontributor
                                </label>
                                <div class="text-muted small">Menghitung lampiran foto ulasan serta kontribusi foto reviewer untuk laporan audit lengkap.</div>
                            </div>
                            <input class="form-check-input ms-3 fs-5" type="checkbox" name="ext_detect_photos" id="switchDetectPhotos" value="1" <?= ($settings['ext_detect_photos'] ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 5: PEMBERSIHAN SAMPAH SISTEM (JUNK CLEANER) -->
            <!-- ========================================================= -->
            <div class="tab-pane fade <?= $isTabCleaner ? 'show active' : '' ?>" id="tab-cleaner" role="tabpanel">
                <div class="row g-4">
                    
                    <!-- Hero Status Pembersihan -->
                    <div class="col-12">
                        <div class="card-custom p-4 bg-white border">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom mb-4">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                        <i class="bi bi-trash3-fill text-danger"></i> Pembersih Berkas Sampah & Kapasitas Hosting
                                    </h5>
                                    <p class="text-secondary small mb-0">
                                        Bersihkan berkas ekspor Excel/CSV lama, file temporary penulisan atomik (<code>*.tmp</code>), dan riwayat log untuk membebaskan ruang penyimpanan server Anda.
                                    </p>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn btn-danger fw-bold shadow-xs d-flex align-items-center gap-2" onclick="confirmClearAllJunk()" <?= empty($junkStats['total_count']) ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash3-fill"></i>
                                        <span>Bersihkan Semua Sampah (<?= $junkStats['total_count'] ?> File)</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Statistik Sampah Terdeteksi -->
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 bg-light border text-center">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Total Sampah</div>
                                        <div class="fs-4 fw-bold <?= $junkStats['total_count'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= $junkStats['total_count'] ?> <span class="fs-6 fw-normal text-muted">berkas</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 bg-light border text-center">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Ruang Terpakai</div>
                                        <div class="fs-4 fw-bold text-dark">
                                            <?= $junkStats['total_size_mb'] > 0.5 ? $junkStats['total_size_mb'] . ' MB' : $junkStats['total_size_kb'] . ' KB' ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 bg-light border text-center">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Berkas Ekspor (.xls)</div>
                                        <div class="fs-4 fw-bold text-primary">
                                            <?= $junkStats['exports']['count'] ?> <span class="fs-6 fw-normal text-muted">(<?= $junkStats['exports']['size_kb'] ?> KB)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 rounded-3 bg-light border text-center">
                                        <div class="text-muted small fw-bold text-uppercase mb-1">Temp & Log</div>
                                        <div class="fs-4 fw-bold text-secondary">
                                            <?= $junkStats['temps']['count'] + $junkStats['logs']['count'] ?> <span class="fs-6 fw-normal text-muted">berkas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kategori 1: Berkas Ekspor Laporan Excel / CSV -->
                    <div class="col-12 col-xl-8">
                        <div class="card-custom p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="bi bi-file-earmark-spreadsheet-fill text-success me-1.5"></i> Berkas Ekspor Laporan (<code>app/data/exports/</code>)
                                    </h6>
                                    <div class="text-muted small">File Excel (.xls) dan CSV hasil audit ulasan yang pernah Anda buat dan tersimpan di server.</div>
                                </div>
                                <?php if ($junkStats['exports']['count'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmClearExports()">
                                        <i class="bi bi-trash me-1"></i> Hapus Semua Ekspor
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($junkStats['exports']['files'])): ?>
                                <div class="table-responsive border rounded-3">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Nama Berkas</th>
                                                <th>Ukuran</th>
                                                <th>Tanggal Dibuat</th>
                                                <th class="text-end pe-3">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($junkStats['exports']['files'] as $ef): ?>
                                                <tr>
                                                    <td class="ps-3 fw-semibold text-dark">
                                                        <i class="bi bi-file-earmark-excel text-success me-1"></i>
                                                        <?= htmlspecialchars($ef['name']) ?>
                                                    </td>
                                                    <td><?= $ef['size'] ?> KB</td>
                                                    <td class="text-muted"><?= htmlspecialchars($ef['time']) ?></td>
                                                    <td class="text-end pe-3">
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Hapus file ini" onclick="confirmDeleteFile('<?= htmlspecialchars(addslashes($ef['name'])) ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 rounded-3 bg-light border text-center text-muted small">
                                    <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                    Folder ekspor bersih! Tidak ada file laporan tersimpan yang membebani server.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kategori 2: Berkas Sementara (*.tmp) & Log (*.log) -->
                    <div class="col-12 col-xl-4">
                        <div class="card-custom p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-clock-history text-warning me-1.5"></i> File Temp & Log
                                </h6>
                                <?php if (($junkStats['temps']['count'] + $junkStats['logs']['count']) > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmClearTemps()">
                                        <i class="bi bi-trash"></i> Bersihkan
                                    </button>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-3">
                                File temporer penulisan data transaksi JSON dan file log aktivitas debugging.
                            </p>

                            <div class="p-3 rounded-3 bg-light border small mb-3">
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary">Berkas Temp (*.tmp):</span>
                                    <strong class="text-dark"><?= $junkStats['temps']['count'] ?> berkas (<?= $junkStats['temps']['size_kb'] ?> KB)</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary">Berkas Log (*.log):</span>
                                    <strong class="text-dark"><?= $junkStats['logs']['count'] ?> berkas (<?= $junkStats['logs']['size_kb'] ?> KB)</strong>
                                </div>
                            </div>

                            <div class="alert alert-info py-2 px-3 rounded-3 small mb-0">
                                <i class="bi bi-info-circle-fill text-info me-1"></i>
                                Menghapus file ini aman dan tidak akan merusak data ulasan maupun akun toko Anda.
                            </div>
                        </div>

                        <!-- Info Tips Server -->
                        <div class="card-custom p-3 bg-light-subtle border">
                            <div class="d-flex align-items-center gap-2 mb-1 text-dark fw-bold small">
                                <i class="bi bi-lightning-charge-fill text-warning"></i> Tips Maintenance Hosting
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.78rem;">
                                Jalankan pembersihan sampah ini secara berkala setelah selesai mengunduh laporan Excel bulanan agar kuota disk hosting Anda tetap ramping dan responsif.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /tab-content -->

        <!-- Sticky Bottom Save Bar -->
        <div class="card-custom p-3 mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 bg-white shadow-sm border border-primary-subtle">
            <div class="d-flex align-items-center gap-2 text-muted small">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span>Menyimpan akan otomatis memperbarui tampilan web dan memperbarui paket zip ekstensi.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('review', 'audit') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-xs d-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle fs-5"></i>
                    <span>Simpan & Terapkan Perubahan</span>
                </button>
            </div>
        </div>

    </form>
</div>

<script>
// Fungsi live preview image saat pengguna memilih file gambar
function previewImage(input, targetImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const targetImg = document.getElementById(targetImgId);
            if (targetImg) {
                targetImg.src = e.target.result;
                targetImg.classList.remove('d-none');
            }
            // Sembunyikan fallback jika ada
            if (targetImgId === 'previewLogoImg') {
                const fb = document.getElementById('previewLogoFallback');
                if (fb) fb.classList.add('d-none');
            } else if (targetImgId === 'previewFaviconImg') {
                const fb = document.getElementById('previewFaviconFallback');
                if (fb) fb.classList.add('d-none');
            } else if (targetImgId === 'previewThumbnailImg') {
                const fb = document.getElementById('previewThumbnailFallback');
                if (fb) fb.classList.add('d-none');
            } else if (targetImgId === 'previewExtIconImg') {
                const fb = document.getElementById('previewExtIconFallback');
                if (fb) fb.classList.add('d-none');
                const large = document.getElementById('previewExtIconLarge');
                if (large) large.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Konfirmasi reset pengaturan default
function confirmResetDefaults() {
    Swal.fire({
        title: 'Kembalikan ke Default?',
        text: 'Seluruh logo, ikon, nama, dan pengaturan akan dikembalikan ke konfigurasi standar bawaan GriView.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-arrow-counterclockwise me-1"></i> Ya, Reset Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('settings', 'reset') ?>';
        }
    });
}

// Konfirmasi bersihkan semua file sampah
function confirmClearAllJunk() {
    Swal.fire({
        title: 'Bersihkan Semua Berkas Sampah?',
        text: 'Seluruh file ekspor Excel lama, cache berkas sementara (*.tmp), dan file log akan dihapus dari server.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Ya, Bersihkan Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('settings', 'clearJunk', ['target' => 'all']) ?>';
        }
    });
}

// Konfirmasi hapus semua file ekspor
function confirmClearExports() {
    Swal.fire({
        title: 'Hapus Semua File Ekspor?',
        text: 'Semua file laporan (.xls, .csv) di folder app/data/exports akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i> Ya, Hapus Semua',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('settings', 'clearJunk', ['target' => 'exports']) ?>';
        }
    });
}

// Konfirmasi bersihkan file temp & log
function confirmClearTemps() {
    Swal.fire({
        title: 'Bersihkan Temp & Log?',
        text: 'Seluruh berkas sementara (*.tmp) dan file log (*.log) akan dibersihkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('settings', 'clearJunk', ['target' => 'temps']) ?>';
        }
    });
}

// Konfirmasi hapus single file ekspor
function confirmDeleteFile(filename) {
    Swal.fire({
        title: 'Hapus Berkas Ini?',
        text: filename,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('settings', 'clearJunk') ?>&file=' + encodeURIComponent(filename);
        }
    });
}
</script>
