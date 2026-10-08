<?php
/* License: by cs.baguosps@gmail.com */
require_once __DIR__ . '/../../models/Store.php';
$allStores = (new Store())->getAll();
$winseeStores = $allStores; // Backward compatibility
?>
<footer>
    <div class="container-fluid px-lg-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="text-secondary small d-flex align-items-center flex-wrap gap-2">
            <span><strong><?= APP_NAME ?></strong> &copy; <?= date('Y') ?> &mdash; Google Business Review Audit & Analytics</span>
            <span class="text-muted opacity-50 d-none d-md-inline">&bull;</span>
            <span class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-envelope-at text-primary"></i>
                <span class="text-muted">Support by:</span>
                <a href="mailto:cs.baguosps@gmail.com" class="text-primary text-decoration-none fw-semibold">cs.baguosps@gmail.com</a>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-secondary border">PHP <?= phpversion() ?></span>
            <span class="badge bg-light text-secondary border">Bootstrap 5.3</span>
            <span class="badge bg-light text-secondary border"><?= count($allStores) ?> Cabang Terdaftar</span>
            <a href="<?= url('extension', 'index') ?>" class="badge bg-dark text-white border border-secondary text-decoration-none">
                <i class="bi bi-puzzle me-1 text-warning"></i> Extension Chrome
            </a>
        </div>
    </div>
</footer>

<!-- MODAL SYNC GOOGLE MAPS -->
<div class="modal fade" id="modalSyncGoogle" tabindex="-1" aria-labelledby="modalSyncGoogleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalSyncGoogleLabel">
                    <i class="bi bi-google text-primary me-2"></i>Tarik & Sinkronkan Ulasan Google Maps
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nav tabs -->
                <ul class="nav nav-pills nav-fill mb-3" id="syncTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="scraper-tab" data-bs-toggle="pill" data-bs-target="#tab-scraper" type="button" role="tab">
                            <i class="bi bi-box-arrow-up-right me-1 text-success"></i> Auto-Audit Ekstensi (Otomatis)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="import-tab" data-bs-toggle="pill" data-bs-target="#tab-import" type="button" role="tab">
                            <i class="bi bi-file-earmark-arrow-up me-1 text-primary"></i> Import CSV/JSON
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="syncTabsContent">
                    <!-- Tab 1: Auto-Audit Chrome Extension -->
                    <div class="tab-pane fade show active" id="tab-scraper" role="tabpanel">
                        <!-- Banner Live Status Ekstensi -->
                        <div id="extensionStatusBanner" class="alert alert-light border py-2.5 px-3 small d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 rounded-3 shadow-xs">
                            <div class="d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm text-primary" id="extStatusSpinner"></span>
                                <div id="extStatusLabel" class="text-secondary fw-medium">Memeriksa Chrome Extension...</div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap" id="extBannerButtons">
                                <button type="button" class="btn btn-sm btn-success fw-bold py-1 px-2.5 shadow-xs d-none" id="btnConfirmInstalledBanner" onclick="confirmExtensionInstalled()">
                                    <i class="bi bi-check-circle-fill me-1"></i> Saya Sudah Pasang
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold py-1 px-2 d-none" id="btnExtInstallGuide" onclick="showExtensionRequiredModal()">
                                    <i class="bi bi-puzzle me-1 text-warning"></i> Panduan / Download
                                </button>
                            </div>
                        </div>

                        <form id="formAutoAudit">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pilih Cabang / Lokasi Bisnis Tujuan</label>
                                <select name="store_id" class="form-select" id="selectScraperStore">
                                    <option value="" data-url="">Otomatis dari Nama Tempat Google Maps</option>
                                    <?php foreach ($winseeStores as $st): ?>
                                        <option value="<?= $st['id'] ?>" data-url="<?= htmlspecialchars($st['gmaps_url'] ?? '') ?>">
                                            <?= !empty($st['store_code']) ? '[' . htmlspecialchars($st['store_code']) . '] ' : '' ?><?= htmlspecialchars($st['store_name']) ?> (<?= htmlspecialchars($st['city']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Pilih cabang toko agar ulasan langsung terhubung ke profil store yang bersangkutan.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Link Google Maps atau Nama Tempat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
                                    <input type="text" name="gmaps_url" id="inputScraperUrl" class="form-control" placeholder="Contoh: https://maps.app.goo.gl/... atau Nama Tempat Bisnis" required>
                                    <button type="button" class="btn btn-outline-secondary" id="btnClearUrl" title="Hapus URL" style="display:none;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="urlHintText">Buka Google Maps, cari tempat/bisnis Anda, klik tombol <strong>Bagikan (Share)</strong> lalu salin linknya ke sini.</div>
                                <div id="urlDetectionStatus" class="mt-2" style="display:none;"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Jumlah Ulasan yang Diambil</label>
                                <select name="scrape_limit" class="form-select" id="selectScrapeLimit">
                                    <option value="50">50 Ulasan Terbaru</option>
                                    <option value="100">100 Ulasan Terbaru</option>
                                    <option value="200">200 Ulasan Terbaru</option>
                                    <option value="500">500 Ulasan Terbaru</option>
                                    <option value="1000" selected>Maksimal 1.000 Ulasan Terbaru</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top mt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-success fw-bold px-3 py-2 shadow-sm d-flex align-items-center gap-2" id="btnLaunchAutoAudit">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                    <span>Buka & Jalankan Audit Otomatis</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Import File CSV/JSON -->
                    <div class="tab-pane fade" id="tab-import" role="tabpanel">
                        <div class="alert alert-light border py-2 small mb-3">
                            <i class="bi bi-filetype-json me-1"></i> Unggah file hasil export ulasan Google Maps dari scraper (Outscraper, Apify, dll) atau backup JSON/CSV.
                        </div>
                        <form action="<?= url('google', 'importFile') ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pilih File (.json atau .csv)</label>
                                <input type="file" name="review_file" class="form-control" accept=".json,.csv" required>
                                <div class="form-text">Format kolom CSV: Nama Reviewer, Tanggal, Rating, Ulasan.</div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i> Unggah & Impor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PERINGATAN MODERN: EKSTENSI CHROME BELUM TERPASANG -->
<div class="modal fade" id="modalExtensionRequired" tabindex="-1" aria-labelledby="modalExtensionRequiredLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 text-white p-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-3 bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px;">
                        <i class="bi bi-puzzle-fill fs-2"></i>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark fw-bold mb-1 px-2.5 py-1">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Ekstensi Belum Terdeteksi
                        </span>
                        <h4 class="modal-title fw-bold mb-0 text-white" id="modalExtensionRequiredLabel">
                            Pasang Chrome Extension GriView Dulu Yuk!
                        </h4>
                        <div class="text-white-50 small mt-1">
                            Fitur <strong>Buka Tab & Jalankan Auto-Audit Otomatis</strong> membutuhkan ekstensi aktif di browser Anda.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold">
                                <i class="bi bi-magic fs-5"></i>
                                <span>Kelebihan Menggunakan Ekstensi:</span>
                            </div>
                            <ul class="list-unstyled small mb-0 text-secondary d-flex flex-column gap-2">
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                                    <span><strong>Auto Buka & Scraping Otomatis:</strong> Langsung mengekstrak ulasan Google Maps tanpa klik manual.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                                    <span><strong>Deteksi Ulasan Panjang:</strong> Membuka ulasan bertanda <em>"Lihat ulasan lengkap" / "more"</em> otomatis.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                                    <span><strong>Analisis & Perhitungan Kata:</strong> Menghitung kata per ulasan, bintang, foto, & balasan owner.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-0.5"></i>
                                    <span><strong>Sinkronisasi Instan:</strong> Data tersimpan otomatis ke database GriView dan siap diunduh Excel/XLS.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold">
                                <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                                <span>Cara Pasang Cepat (1 Menit):</span>
                            </div>
                            <ol class="small mb-0 text-secondary ps-3 d-flex flex-column gap-1.5">
                                <li>Klik tombol <strong>Download Ekstensi (.ZIP)</strong> di bawah.</li>
                                <li>Ekstrak file <code>griview-chrome-extension.zip</code> ke folder laptop Anda.</li>
                                <li>Buka tab Chrome: ketik <code>chrome://extensions</code> lalu tekan Enter.</li>
                                <li>Aktifkan toggle <strong>Developer mode</strong> di kanan atas.</li>
                                <li>Klik <strong>Load unpacked</strong> dan pilih folder hasil ekstrak tadi.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-3 border bg-light-subtle d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                        <span class="small text-muted" id="extensionModalStatusText">
                            Menunggu ekstensi terpasang di Chrome...
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-success fw-bold px-3 py-2 shadow-sm" id="btnConfirmAlreadyInstalledModal" onclick="confirmExtensionInstalled(true)">
                            <i class="bi bi-check-circle-fill me-1"></i> Saya Sudah Pasang, Lanjutkan
                        </button>
                        <a href="<?= url('extension', 'download') ?>" class="btn btn-outline-success fw-semibold px-3 py-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-download"></i>
                            <span>Download Ekstensi (.ZIP)</span>
                        </a>
                        <a href="<?= url('extension', 'index') ?>" target="_blank" class="btn btn-outline-primary fw-semibold px-3 py-2 d-flex align-items-center gap-1">
                            <i class="bi bi-book"></i>
                            <span>Panduan Lengkap</span>
                        </a>
                        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" id="btnCheckExtensionAgain">
                            <i class="bi bi-arrow-repeat me-1"></i> Periksa Lagi
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2.5 px-4 justify-content-between">
                <span class="small text-muted">
                    <i class="bi bi-shield-check text-success me-1"></i> Ekstensi aman, 100% lokal di browser, tanpa biaya Google Cloud.
                </span>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL TAMBAH REVIEW MANUAL -->
<div class="modal fade" id="modalAddReview" tabindex="-1" aria-labelledby="modalAddReviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalAddReviewLabel">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Tambah Ulasan Google Maps Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('review', 'addManual') ?>" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Cabang Bisnis <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-select" required>
                            <?php foreach ($winseeStores as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['store_name']) ?> (<?= htmlspecialchars($st['city']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Reviewer <span class="text-danger">*</span></label>
                        <input type="text" name="author_name" class="form-control" placeholder="Contoh: Rian Pratama" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Rating Bintang <span class="text-danger">*</span></label>
                            <select name="rating" class="form-select" required>
                                <option value="5">⭐⭐⭐⭐⭐ (5 Bintang)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Bintang)</option>
                                <option value="3">⭐⭐⭐ (3 Bintang)</option>
                                <option value="2">⭐⭐ (2 Bintang)</option>
                                <option value="1">⭐ (1 Bintang)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tipe Akun</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_local_guide" id="checkLocalGuide" value="1">
                                <label class="form-check-label fw-semibold" for="checkLocalGuide">
                                    🌟 Local Guide
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tanggal Review</label>
                            <input type="date" name="review_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Waktu</label>
                            <input type="time" name="review_time" class="form-control" value="<?= date('H:i') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Isi Ulasan Pengunjung</label>
                        <textarea name="review_text" rows="3" class="form-control" placeholder="Tuliskan komentar atau ulasan pengunjung..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggapan / Balasan Owner (Opsional)</label>
                        <textarea name="owner_reply" rows="2" class="form-control" placeholder="Tuliskan respon jika sudah ada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Ulasan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
// ============================================================
// Modal Sync Google: Auto-fill store & URL saat tombol "Tarik"
// ============================================================
(function () {
    // Simpan state store yang dipilih
    let _pendingStoreId  = null;
    let _pendingStoreUrl = null;

    // Tangkap klik tombol Tarik di mana pun di halaman
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-sync-store');
        if (!btn) return;
        _pendingStoreId  = btn.dataset.storeId || null;
        _pendingStoreUrl = btn.dataset.storeUrl || '';
    });

    // Saat modal mulai terbuka, isi field
    const modalEl = document.getElementById('modalSyncGoogle');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            if (_pendingStoreId === null) return;

            const sel = document.getElementById('selectScraperStore');
            if (sel) {
                sel.value = _pendingStoreId;
                // Trigger change agar URL ikut terisi
                sel.dispatchEvent(new Event('change'));
            }

            // Override URL jika ada dari tombol Tarik (gmaps_url store)
            const inp = document.getElementById('inputScraperUrl');
            if (inp && _pendingStoreUrl) {
                inp.value = _pendingStoreUrl;
                toggleClearBtn(_pendingStoreUrl);
            }

            // Reset agar klik navbar tidak memengaruhi
            _pendingStoreId  = null;
            _pendingStoreUrl = null;
        });
    }
})();

// ============================================================
// Dropdown Cabang → Auto-fill URL dari data-url (gmaps_url store)
// ============================================================
(function () {
    const sel    = document.getElementById('selectScraperStore');
    const inp    = document.getElementById('inputScraperUrl');
    const btnClr = document.getElementById('btnClearUrl');
    const hint   = document.getElementById('urlHintText');

    if (!sel || !inp) return;

    function toggleClearBtn(val) {
        if (btnClr) btnClr.style.display = val ? 'inline-flex' : 'none';
    }

    // Ekspos fungsi agar bisa dipanggil dari IIFE lain
    window.toggleClearBtn = toggleClearBtn;

    sel.addEventListener('change', function () {
        const selectedOpt = this.options[this.selectedIndex];
        const storeUrl    = selectedOpt ? (selectedOpt.dataset.url || '') : '';

        if (storeUrl) {
            inp.value = storeUrl;
            inp.classList.remove('is-invalid');
            inp.classList.add('is-valid');
            if (hint) hint.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i><strong>URL otomatis diisi</strong> dari data cabang terdaftar. Anda bisa menggantinya jika perlu.';
        } else {
            inp.value = '';
            inp.classList.remove('is-valid');
            if (hint) hint.innerHTML = 'Buka Google Maps, cari tempat/bisnis Anda, klik tombol <strong>Bagikan (Share)</strong> lalu salin linknya ke sini.';
        }
        toggleClearBtn(storeUrl);
    });

    // Tombol X untuk hapus URL dan kembali ke mode manual
    if (btnClr) {
        btnClr.addEventListener('click', function () {
            inp.value = '';
            inp.classList.remove('is-valid', 'is-invalid');
            if (hint) hint.innerHTML = 'Buka Google Maps, cari tempat/bisnis Anda, klik tombol <strong>Bagikan (Share)</strong> lalu salin linknya ke sini.';
            toggleClearBtn('');
            inp.focus();
        });
    }

    // Reset saat modal ditutup
    const modalEl = document.getElementById('modalSyncGoogle');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            sel.value = '';
            inp.value = '';
            inp.classList.remove('is-valid', 'is-invalid');
            if (hint) hint.innerHTML = 'Buka Google Maps, cari tempat/bisnis Anda, klik tombol <strong>Bagikan (Share)</strong> lalu salin linknya ke sini.';
            toggleClearBtn('');
        });
    }
})();


// ============================================================
// GriView Chrome Extension Detection & Auto-Audit Controller
// ============================================================
(function () {
    // 1. Cek instan dari DOM, window, localStorage, dan sessionStorage
    window.isGriViewExtensionInstalled = function () {
        try {
            if (document.documentElement && document.documentElement.getAttribute('data-griview-extension') === 'installed') return true;
            if (window.__GRIVIEW_EXTENSION_INSTALLED__ === true) return true;
            if (localStorage.getItem('griview_extension_active') === '1') return true;
            if (localStorage.getItem('griview_user_confirmed_installed') === '1') return true;
            if (sessionStorage.getItem('griview_extension_active') === '1') return true;
        } catch (e) {}
        return false;
    };

    // 2. Fungsi konfirmasi manual jika ekstensi sudah terpasang
    window.confirmExtensionInstalled = function (openSyncModal = false) {
        try {
            localStorage.setItem('griview_user_confirmed_installed', '1');
            sessionStorage.setItem('griview_extension_active', '1');
            window.__GRIVIEW_EXTENSION_INSTALLED__ = true;
            if (document.documentElement) {
                document.documentElement.setAttribute('data-griview-extension', 'installed');
            }
        } catch (e) {}

        refreshExtensionStatusUI();

        if (openSyncModal) {
            const reqModalEl = document.getElementById('modalExtensionRequired');
            if (reqModalEl) {
                const bsReq = bootstrap.Modal.getInstance(reqModalEl);
                if (bsReq) bsReq.hide();
            }
            const syncModalEl = document.getElementById('modalSyncGoogle');
            if (syncModalEl) {
                const bsSync = bootstrap.Modal.getOrCreateInstance(syncModalEl);
                bsSync.show();
            }
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Ekstensi Terverifikasi! Siap auto-audit.',
            showConfirmButton: false,
            timer: 2000
        });
    };

    // 3. Cek asinkron dengan ping-pong handshake
    window.checkGriViewExtensionInstalled = function (timeout = 350) {
        return new Promise((resolve) => {
            if (window.isGriViewExtensionInstalled()) {
                return resolve(true);
            }
            let resolved = false;
            let timer = null;

            function onPong(event) {
                if (event.data && (event.data.type === 'GRIVIEW_PONG_EXTENSION' || event.data.installed)) {
                    window.removeEventListener('message', onPong);
                    if (timer) clearTimeout(timer);
                    resolved = true;
                    window.__GRIVIEW_EXTENSION_INSTALLED__ = true;
                    try {
                        localStorage.setItem('griview_extension_active', '1');
                    } catch (e) {}
                    resolve(true);
                }
            }

            window.addEventListener('message', onPong);
            window.postMessage({ type: 'GRIVIEW_PING_EXTENSION' }, '*');

            timer = setTimeout(() => {
                if (!resolved) {
                    window.removeEventListener('message', onPong);
                    resolve(window.isGriViewExtensionInstalled());
                }
            }, timeout);
        });
    };

    // 4. Update UI Banner Status Ekstensi di Modal Sync
    async function refreshExtensionStatusUI() {
        const banner     = document.getElementById('extensionStatusBanner');
        const label      = document.getElementById('extStatusLabel');
        const spinner    = document.getElementById('extStatusSpinner');
        const btnConfirm = document.getElementById('btnConfirmInstalledBanner');
        const btnGuide   = document.getElementById('btnExtInstallGuide');
        if (!banner || !label) return;

        const isInstalled = await window.checkGriViewExtensionInstalled(250);
        if (spinner) spinner.classList.add('d-none');

        if (isInstalled) {
            banner.className = 'alert alert-success py-2 px-3 small d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 rounded-3 border border-success-subtle bg-success-subtle';
            label.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Chrome Extension Aktif:</span> <span class="text-dark">Siap membuka tab & auto-audit ulasan secara otomatis.</span>';
            if (btnConfirm) btnConfirm.classList.add('d-none');
            if (btnGuide) btnGuide.classList.add('d-none');
        } else {
            banner.className = 'alert alert-warning py-2.5 px-3 small d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 rounded-3 border border-warning-subtle bg-warning-subtle';
            label.innerHTML = `
                <div>
                    <div class="fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Ekstensi Belum Terdeteksi Otomatis</div>
                    <div class="text-muted small">Jika baru dipasang di <code>chrome://extensions</code>, tekan <strong>F5</strong>, atau konfirmasi di samping jika sudah aktif:</div>
                </div>
            `;
            if (btnConfirm) btnConfirm.classList.remove('d-none');
            if (btnGuide) btnGuide.classList.remove('d-none');
        }
    }
    window.refreshExtensionStatusUI = refreshExtensionStatusUI;

    // 5. Buka Popup Modal Peringatan Modern jika ekstensi belum ada
    window.showExtensionRequiredModal = function () {
        const syncModalEl = document.getElementById('modalSyncGoogle');
        if (syncModalEl) {
            const bsSync = bootstrap.Modal.getInstance(syncModalEl);
            if (bsSync) bsSync.hide();
        }

        const reqModalEl = document.getElementById('modalExtensionRequired');
        if (reqModalEl) {
            const bsReq = bootstrap.Modal.getOrCreateInstance(reqModalEl);
            bsReq.show();
        }
    };

    // Cache data resolusi link Google Maps
    window.__griviewResolvedMapsData = null;

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    // Resolusi link Google Maps via endpoint AJAX PHP
    async function resolveGoogleMapsUrl(inputVal) {
        const raw = (inputVal || '').trim();
        if (!raw) return null;

        try {
            const endpoint = '<?= url("extension", "resolveUrl") ?>&url=' + encodeURIComponent(raw);
            const res = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return null;
            const data = await res.json();
            if (data && data.success) {
                window.__griviewResolvedMapsData = data;
                return data;
            }
        } catch (e) {
            console.warn('Gagal meresolusi URL Google Maps:', e);
        }
        return null;
    }

    // 6. Susun URL auto audit
    function buildAutoAuditUrl(inputVal, storeName, resolvedData, limit = 1000) {
        let raw = (inputVal || '').trim();
        if (!raw && storeName) raw = storeName.trim();
        if (!raw) return null;

        const limitVal = Math.min(1000, Math.max(1, Number(limit) || 1000));
        const resolved = resolvedData || window.__griviewResolvedMapsData;

        // Jika sudah teresolusi dan memiliki search_url dengan place_name / review_hash:
        if (resolved) {
            if (resolved.search_url) {
                try {
                    const u = new URL(resolved.search_url);
                    u.searchParams.set('griview_auto_audit', '1');
                    u.searchParams.set('griview_limit', String(limitVal));
                    return u.toString();
                } catch (e) {
                    const sep = resolved.search_url.includes('?') ? '&' : '?';
                    return resolved.search_url + sep + 'griview_auto_audit=1&griview_limit=' + limitVal;
                }
            }
            if (resolved.place_name) {
                const hash = resolved.review_hash || (resolved.cid ? '#lrd=' + resolved.cid + ',1,,,' : '');
                return 'https://www.google.com/search?q=' + encodeURIComponent(resolved.place_name) + '&hl=en-us&griview_auto_audit=1&griview_limit=' + limitVal + hash;
            }
            if (resolved.final_url) {
                try {
                    const u = new URL(resolved.final_url);
                    u.searchParams.set('griview_auto_audit', '1');
                    u.searchParams.set('griview_limit', String(limitVal));
                    return u.toString();
                } catch (e) {
                    const sep = resolved.final_url.includes('?') ? '&' : '?';
                    return resolved.final_url + sep + 'griview_auto_audit=1&griview_limit=' + limitVal + '#griview_auto_audit=1';
                }
            }
        }

        if (/^https?:\/\//i.test(raw)) {
            try {
                const parsed = new URL(raw);
                parsed.searchParams.set('griview_auto_audit', '1');
                parsed.searchParams.set('griview_limit', String(limitVal));
                parsed.hash = '#griview_auto_audit=1';
                return parsed.toString();
            } catch (e) {
                return raw + (raw.includes('?') ? '&' : '?') + 'griview_auto_audit=1&griview_limit=' + limitVal + '#griview_auto_audit=1';
            }
        }
        return 'https://www.google.com/search?q=' + encodeURIComponent(raw) + '&griview_auto_audit=1&griview_limit=' + limitVal;
    }

    // Tracking & Pembaruan Tampilan Tab Awal saat Auto-Audit Berjalan & Selesai
    window.__griviewActiveAudit = null;
    window.__griviewAuditCompletedShown = false;
    window.__griviewPollTimer = null;

    function updateAuditProgress(info) {
        if (!info || window.__griviewAuditCompletedShown) return;
        const currentCount = Number(info.count || 0);
        const maxReviews = Number(info.maxReviews || window.__griviewActiveAudit?.maxReviews || 1000);
        const percent = Math.min(100, Math.max(5, Math.round((currentCount / Math.max(1, maxReviews)) * 100)));

        if (window.__griviewActiveAudit) {
            window.__griviewActiveAudit.lastCount = currentCount;
            if (info.placeName) window.__griviewActiveAudit.placeName = info.placeName;
        }

        const barEl = document.getElementById('swalAuditProgressBar');
        if (barEl) {
            barEl.style.width = percent + '%';
            barEl.setAttribute('aria-valuenow', percent);
        }
        const countBadge = document.getElementById('swalAuditCountBadge');
        if (countBadge) {
            countBadge.textContent = `${currentCount.toLocaleString()} / ${maxReviews.toLocaleString()}`;
        }
        const percentText = document.getElementById('swalAuditPercentText');
        if (percentText) {
            percentText.textContent = `${percent}%`;
        }
        const statusText = document.getElementById('swalAuditStatusText');
        if (statusText && info.message) {
            statusText.textContent = info.message;
        }
    }

    function handleAuditFinished(info) {
        if (window.__griviewAuditCompletedShown) return;
        window.__griviewAuditCompletedShown = true;

        if (window.__griviewPollTimer) {
            clearInterval(window.__griviewPollTimer);
            window.__griviewPollTimer = null;
        }

        const placeName = info?.placeName || info?.place_name || window.__griviewActiveAudit?.placeName || 'Google Maps';
        const count = Number(info?.count ?? info?.saved_count ?? window.__griviewActiveAudit?.lastCount ?? 0);
        const auditUrl = info?.auditUrl || info?.audit_url || '<?= url("review", "audit") ?>';
        const downloadUrl = info?.downloadUrl || info?.download_url || '<?= url("review", "downloadAuditXls") ?>';

        // Ganti tampilan di tab awal dengan notifikasi sukses & ringkasan hasil
        Swal.fire({
            icon: 'success',
            title: 'Auto-Audit Selesai! 🎉',
            html: `
                <div class="text-start">
                    <div class="alert alert-success border-success-subtle d-flex align-items-center gap-3 p-3 mb-3 rounded-3 shadow-xs">
                        <i class="bi bi-check-circle-fill fs-3 text-success flex-shrink-0"></i>
                        <div>
                            <strong class="text-success d-block fs-6">Audit Berhasil Diselesaikan!</strong>
                            <span class="small text-secondary">Data ulasan telah otomatis tersimpan ke database GriView dan laporan XLS siap diunduh.</span>
                        </div>
                    </div>

                    <div class="card border rounded-3 p-3 mb-3 bg-light-subtle shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="text-secondary small">Nama Tempat:</span>
                            <strong class="text-dark">${escapeHtml(placeName)}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="text-secondary small">Total Ulasan Diambil:</span>
                            <span class="badge bg-primary fs-6 px-3 py-1.5 shadow-xs">${count > 0 ? count.toLocaleString() + ' Ulasan' : 'Selesai'}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">Status Sinkronisasi:</span>
                            <span class="badge bg-success text-white px-2.5 py-1">
                                <i class="bi bi-check-all me-1"></i> Tersinkron ke GriView
                            </span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-3 bg-light border small text-muted mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill text-primary fs-5 flex-shrink-0"></i>
                        <span>Pilih <strong>Buka Halaman Audit</strong> untuk melihat analisis lengkap, atau <strong>Download XLS</strong> untuk membuka di Excel.</span>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="bi bi-shield-check me-1"></i> Buka Halaman Audit',
            confirmButtonColor: '#0d6efd',
            denyButtonText: '<i class="bi bi-file-earmark-excel-fill me-1"></i> Download File XLS',
            denyButtonColor: '#198754',
            cancelButtonText: 'Selesai & Muat Ulang',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = auditUrl;
            } else if (result.isDenied) {
                window.location.href = downloadUrl;
            } else {
                window.location.reload();
            }
        });
    }

    // Dengarkan sinyal progres & selesai dari Chrome Extension bridge
    window.addEventListener('message', function (e) {
        if (!e.data || typeof e.data !== 'object') return;
        if (e.data.type === 'GRIVIEW_AUDIT_PROGRESS') {
            updateAuditProgress(e.data);
        } else if (e.data.type === 'GRIVIEW_AUDIT_COMPLETED' || e.data.type === 'GRIVIEW_AUDIT_SYNCED') {
            handleAuditFinished(e.data);
        }
    });

    // 7. Buka 1 tab baru dan jalankan auto-audit
    function launchAutoAuditTab(targetUrl, auditMeta) {
        // Tutup modal sync
        const syncModalEl = document.getElementById('modalSyncGoogle');
        if (syncModalEl) {
            const bsSync = bootstrap.Modal.getInstance(syncModalEl);
            if (bsSync) bsSync.hide();
        }

        const targetLimit = Number(auditMeta?.maxReviews || 1000);
        const placeName = auditMeta?.placeName || 'Google Maps';

        window.__griviewActiveAudit = {
            startTime: Date.now(),
            placeName: placeName,
            maxReviews: targetLimit,
            lastCount: 0
        };
        window.__griviewAuditCompletedShown = false;

        // Buka TEPAT 1 TAB BARU saja (reuse jika sudah ada tab audit terbuka)
        if (window.__griviewTargetAuditWindow && !window.__griviewTargetAuditWindow.closed) {
            window.__griviewTargetAuditWindow.location.href = targetUrl;
            window.__griviewTargetAuditWindow.focus();
        } else {
            window.__griviewTargetAuditWindow = window.open(targetUrl, '_blank');
        }

        // Tampilkan feedback modern ke user di tab awal dengan status dinamis
        Swal.fire({
            icon: 'info',
            title: 'Membuka Tab & Menjalankan Auto-Audit! 🚀',
            html: `
                <div class="text-start">
                    <p class="mb-2">Tab Google Maps/Search telah dibuka di tab baru dan <strong>Review Audit sedang berjalan otomatis</strong> via Chrome Extension.</p>

                    <!-- Live Progress Box di Tab Awal -->
                    <div id="swalAuditLiveProgressBox" class="p-3 rounded-3 bg-light border mb-2.5">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="small fw-semibold text-secondary d-flex align-items-center gap-2">
                                <span class="spinner-grow spinner-grow-sm text-primary" role="status"></span>
                                <span id="swalAuditStatusText">Sedang mengumpulkan ulasan di tab baru...</span>
                            </span>
                            <span id="swalAuditCountBadge" class="badge bg-primary px-2.5 py-1">0 / ${targetLimit.toLocaleString()}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="swalAuditProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 10%;"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1.5 small text-muted">
                            <span>Target: <strong class="text-dark">${targetLimit.toLocaleString()} Ulasan</strong></span>
                            <span id="swalAuditPercentText">0%</span>
                        </div>
                    </div>

                    <div class="alert alert-light border py-2 px-3 small text-muted mb-2">
                        <i class="bi bi-magic text-primary me-1"></i>
                        Tampilan modal ini akan <strong>otomatis berubah</strong> begitu proses audit selesai atau dihentikan.
                    </div>
                    <p class="small text-muted mb-0">Setelah selesai, data otomatis tersimpan dan muncul di halaman <strong>Ulasan</strong> dan <strong>Audit</strong>.</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-shield-check me-1"></i> Buka Halaman Audit',
            confirmButtonColor: '#0d6efd',
            cancelButtonText: 'Tetap di Sini (Biarkan Berjalan)',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= url("review", "audit") ?>';
            }
        });

        // Polling fallback: Periksa status sync ke backend setiap 1.5 detik
        if (window.__griviewPollTimer) clearInterval(window.__griviewPollTimer);
        const startTime = Date.now();
        window.__griviewPollTimer = setInterval(async () => {
            if (window.__griviewAuditCompletedShown) {
                clearInterval(window.__griviewPollTimer);
                return;
            }
            try {
                const res = await fetch('<?= BASE_URL ?>/index.php?c=review&a=checkSyncStatus');
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.has_export) {
                        const exportTime = (data.saved_timestamp ? data.saved_timestamp * 1000 : 0);
                        if (exportTime >= startTime - 3000) {
                            handleAuditFinished(data);
                        }
                    }
                }
            } catch (err) {}
        }, 1500);
    }

    // 8. Eksekusi Auto-Audit: Buka Tab Baru & Jalankan Audit (Debounced: 1 Tab Saja)
    let isLaunchingAutoAudit = false;
    async function executeAutoAudit() {
        if (isLaunchingAutoAudit) return;
        isLaunchingAutoAudit = true;
        setTimeout(() => { isLaunchingAutoAudit = false; }, 3000);

        const btnAuto = document.getElementById('btnLaunchAutoAudit');
        const origBtnHtml = btnAuto ? btnAuto.innerHTML : '';
        if (btnAuto) {
            btnAuto.disabled = true;
            setTimeout(() => { if (btnAuto) btnAuto.disabled = false; }, 3000);
        }
        const inp = document.getElementById('inputScraperUrl');
        const sel = document.getElementById('selectScraperStore');
        let urlVal = (inp ? inp.value : '').trim();
        const selectedStoreName = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
        const selectedStoreId = sel ? sel.value : '';
        const scrapeLimit = Number(document.getElementById('selectScrapeLimit')?.value) || 1000;

        if (!urlVal && !selectedStoreName) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Cabang atau Isi Link',
                text: 'Silakan pilih Cabang Toko atau masukkan Link Google Maps / nama tempat.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // Jika URL belum sempat di-resolve, jalankan resolusi cepat backend
        let resolved = window.__griviewResolvedMapsData;
        if (!resolved && /^https?:\/\//i.test(urlVal)) {
            if (btnAuto) {
                btnAuto.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span>Menghubungkan Google Maps...';
            }
            resolved = await resolveGoogleMapsUrl(urlVal);
            if (btnAuto) {
                btnAuto.innerHTML = origBtnHtml;
            }
        }

        const targetPlaceName = resolved?.place_name || (!/^https?:\/\//i.test(urlVal) ? urlVal : selectedStoreName);
        const targetCid = resolved?.cid || '';
        const targetReviewHash = resolved?.review_hash || (targetCid ? '#lrd=' + targetCid + ',1,,,' : '');
        const targetUrl = buildAutoAuditUrl(urlVal, selectedStoreName, resolved, scrapeLimit);
        if (!targetUrl) return;

        const auditMeta = {
            placeName: targetPlaceName,
            maxReviews: scrapeLimit,
            storeId: selectedStoreId || (resolved?.matched_store?.id || '')
        };

        // Persenjatai ekstensi Chrome (ARM) via bridge postMessage
        try {
            window.postMessage({
                type: 'GRIVIEW_ARM_AUDIT',
                placeName: targetPlaceName,
                cid: targetCid,
                reviewHash: targetReviewHash,
                url: targetUrl,
                maxReviews: scrapeLimit,
                storeId: auditMeta.storeId
            }, '*');
        } catch (e) {}

        // Cek ekstensi Chrome
        const isInstalled = await window.checkGriViewExtensionInstalled(250);

        if (isInstalled) {
            // Sudah terpasang, langsung buka!
            launchAutoAuditTab(targetUrl, auditMeta);
        } else {
            // Belum terdeteksi: berikan opsi ramah agar tidak memblokir user yang sudah pasang
            Swal.fire({
                icon: 'question',
                title: 'Buka Tab & Jalankan Auto-Audit?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Sistem belum mendeteksi skrip bridge ekstensi secara otomatis pada tab ini.</p>
                        <div class="alert alert-light border p-3 small mb-3">
                            <strong class="text-dark">Apakah Anda sudah memasang Chrome Extension GriView?</strong>
                            <div class="mt-1 text-secondary">
                                • <strong>Sudah:</strong> Klik tombol hijau di bawah untuk langsung membuka tab baru.<br>
                                • <strong>Belum:</strong> Klik tombol panduan/download untuk memasangnya terlebih dahulu.
                            </div>
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-lightbulb text-warning me-1"></i> Tips: Jika ekstensi baru saja di-load, klik reload (🔄) di <code>chrome://extensions</code> lalu refresh halaman web ini (tekan F5).
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="bi bi-box-arrow-up-right me-1"></i> Tetap Buka Tab & Jalankan Audit',
                confirmButtonColor: '#198754',
                denyButtonText: '<i class="bi bi-puzzle me-1"></i> Download / Panduan',
                denyButtonColor: '#0d6efd',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#6c757d'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.confirmExtensionInstalled();
                    launchAutoAuditTab(targetUrl, auditMeta);
                } else if (res.isDenied) {
                    window.showExtensionRequiredModal();
                }
            });
        }
    }

    // Live detector input URL Google Maps
    const inputScraper = document.getElementById('inputScraperUrl');
    const statusBox = document.getElementById('urlDetectionStatus');
    const btnClear = document.getElementById('btnClearUrl');
    const selStore = document.getElementById('selectScraperStore');

    let resolveDebounceTimer = null;
    function handleUrlInputChanged() {
        const val = (inputScraper ? inputScraper.value : '').trim();
        if (btnClear) btnClear.style.display = val ? 'block' : 'none';

        if (!val) {
            if (statusBox) statusBox.style.display = 'none';
            window.__griviewResolvedMapsData = null;
            return;
        }

        clearTimeout(resolveDebounceTimer);
        const isUrl = /^https?:\/\//i.test(val);

        if (isUrl && statusBox) {
            statusBox.style.display = 'block';
            statusBox.innerHTML = '<span class="badge bg-light text-secondary border py-1.5 px-2.5 rounded-pill"><span class="spinner-border spinner-border-sm me-1.5 text-primary"></span>Mendeteksi tempat dari link Google Maps...</span>';
        }

        resolveDebounceTimer = setTimeout(async () => {
            if (!val) return;
            const data = await resolveGoogleMapsUrl(val);
            if (!statusBox) return;

            if (data && data.place_name) {
                let html = '<span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-2.5 rounded-pill shadow-xs"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Terdeteksi: <strong class="text-dark">' + escapeHtml(data.place_name) + '</strong></span>';
                if (data.matched_store) {
                    html += ' <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1.5 px-2 rounded-pill ms-1"><i class="bi bi-shop me-1"></i> Cabang: ' + escapeHtml(data.matched_store.store_name) + '</span>';
                    if (selStore && data.matched_store.id) {
                        selStore.value = String(data.matched_store.id);
                    }
                }
                statusBox.innerHTML = html;
                statusBox.style.display = 'block';
            } else if (isUrl) {
                statusBox.innerHTML = '<span class="badge bg-light text-muted border py-1.5 px-2.5 rounded-pill"><i class="bi bi-link-45deg me-1"></i> Link Google Maps siap dibuka</span>';
                statusBox.style.display = 'block';
            } else {
                statusBox.style.display = 'none';
            }
        }, 400);
    }

    if (inputScraper) {
        inputScraper.addEventListener('input', handleUrlInputChanged);
        inputScraper.addEventListener('paste', () => setTimeout(handleUrlInputChanged, 50));
    }

    if (btnClear) {
        btnClear.addEventListener('click', () => {
            if (inputScraper) {
                inputScraper.value = '';
                inputScraper.focus();
            }
            handleUrlInputChanged();
        });
    }

    // Pasang listener tombol Auto-Audit utama
    const btnAutoAudit = document.getElementById('btnLaunchAutoAudit');
    if (btnAutoAudit) {
        btnAutoAudit.addEventListener('click', function (e) {
            e.preventDefault();
            executeAutoAudit();
        });
    }

    // Tombol Cek Ulang di modal peringatan ekstensi
    const btnRecheck = document.getElementById('btnCheckExtensionAgain');
    if (btnRecheck) {
        btnRecheck.addEventListener('click', async function () {
            btnRecheck.disabled = true;
            btnRecheck.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memeriksa...';
            const isInstalled = await window.checkGriViewExtensionInstalled(500);
            btnRecheck.disabled = false;
            btnRecheck.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Periksa Lagi';

            const modalStatus = document.getElementById('extensionModalStatusText');
            if (isInstalled) {
                if (modalStatus) modalStatus.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Ekstensi aktif terdeteksi!</span>';
                Swal.fire({
                    icon: 'success',
                    title: 'Ekstensi Terdeteksi! 🎉',
                    text: 'GriView Chrome Extension telah berhasil aktif. Anda sekarang dapat menjalankan review audit otomatis.',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Lanjutkan Tarik Data'
                }).then(() => {
                    const reqModalEl = document.getElementById('modalExtensionRequired');
                    if (reqModalEl) {
                        const bsReq = bootstrap.Modal.getInstance(reqModalEl);
                        if (bsReq) bsReq.hide();
                    }
                    const syncModalEl = document.getElementById('modalSyncGoogle');
                    if (syncModalEl) {
                        const bsSync = bootstrap.Modal.getOrCreateInstance(syncModalEl);
                        bsSync.show();
                    }
                    refreshExtensionStatusUI();
                });
            } else {
                if (modalStatus) modalStatus.textContent = 'Ekstensi belum terdeteksi. Pastikan Developer Mode aktif & folder telah dimuat.';
                Swal.fire({
                    icon: 'info',
                    title: 'Belum Terdeteksi Otomatis',
                    html: `
                        <div class="text-start">
                            <p class="mb-2">Jika Anda sudah memasang folder ekstensi di <code>chrome://extensions</code>:</p>
                            <ol class="small text-muted ps-3 mb-0">
                                <li>Klik tombol <strong>Reload (🔄)</strong> pada kartu ekstensi di <code>chrome://extensions</code>.</li>
                                <li>Refresh halaman GriView ini (tekan <strong>F5</strong>).</li>
                                <li>Atau klik tombol <strong>"Saya Sudah Pasang"</strong> di banner.</li>
                            </ol>
                        </div>
                    `,
                    confirmButtonColor: '#0d6efd'
                });
            }
        });
    }

    // Listener event siap dari bridge ekstensi
    window.addEventListener('GriViewExtensionReady', function () {
        window.__GRIVIEW_EXTENSION_INSTALLED__ = true;
        refreshExtensionStatusUI();
    });

    // Saat modal sync dibuka, update status ekstensi
    const syncModalEl = document.getElementById('modalSyncGoogle');
    if (syncModalEl) {
        syncModalEl.addEventListener('show.bs.modal', function () {
            refreshExtensionStatusUI();
        });
    }

    const form = document.getElementById('formAutoAudit');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            executeAutoAudit();
        });
    }
})();
</script>

</body>
</html>
