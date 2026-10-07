<?php /* License: by cs.baguosps@gmail.com */ ?>
<div class="container-fluid px-lg-4 py-4">

    <!-- Flash Notification -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- HERO SECTION: DOWNLOAD EXTENSION -->
    <div class="card-custom p-4 p-lg-5 mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #17191d 0%, #20242c 100%); color: #fff; border-radius: 18px;">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge px-3 py-1 fw-bold text-dark" style="background: #a6ec63; font-size: 0.78rem;">
                        <i class="bi bi-puzzle-fill me-1"></i> RESMI &bull; VERSI 1.0.0
                    </span>
                    <span class="badge bg-secondary bg-opacity-50 text-light border border-secondary px-3 py-1">
                        Manifest V3
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i> Auto-Sync Aktif
                    </span>
                </div>
                <h2 class="fw-bold mb-2 text-white display-6" style="font-weight: 800;">
                    GriView Chrome Extension
                </h2>
                <p class="text-white-50 lead mb-4" style="font-size: 1.05rem;">
                    Alat audit ulasan Google Maps otomatis. Mampu melakukan scroll otomatis hingga 1.000 ulasan, menghitung jumlah kata per ulasan, mendeteksi foto kontributor & foto review, serta <strong>langsung tersimpan otomatis ke database GriView Web</strong> dan menghasilkan file XLS yang siap di-download.
                </p>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="<?= url('extension', 'download') ?>" class="btn btn-lg px-4 py-3 fw-bold text-dark shadow d-inline-flex align-items-center gap-2" style="background: #a6ec63; border-color: #a6ec63; border-radius: 10px;">
                        <i class="bi bi-cloud-arrow-down-fill fs-4 text-dark"></i>
                        <span>Download Extension (.ZIP)</span>
                    </a>
                    <a href="<?= url('review', 'audit') ?>" class="btn btn-outline-light btn-lg px-4 py-3 fw-semibold d-inline-flex align-items-center gap-2" style="border-radius: 10px;">
                        <i class="bi bi-shield-check fs-5"></i>
                        <span>Buka Halaman Review Audit</span>
                    </a>
                </div>

                <div class="small text-white-50 mt-3 d-flex flex-wrap align-items-center gap-3">
                    <span><i class="bi bi-file-zip me-1"></i> Ukuran: <strong><?= $zipSize ?> KB</strong></span>
                    <span>&bull;</span>
                    <span><i class="bi bi-clock-history me-1"></i> Terakhir diperbarui: <?= $zipModified ?></span>
                    <span>&bull;</span>
                    <span><i class="bi bi-shield-lock me-1"></i> 100% Aman & Bebas Iklan</span>
                </div>
            </div>

            <div class="col-12 col-lg-4 text-center">
                <div class="p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="brand-icon mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2.2rem; background: #a6ec63; color: #19210f; border-radius: 18px;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-1">Audit Cepat Google Maps</h5>
                    <p class="text-white-50 small mb-3">
                        Tinggal pasang di Google Chrome, buka profil bisnis toko, lalu klik tombol "Audit Review".
                    </p>
                    <div class="p-2 rounded-3 bg-dark border border-secondary text-start small">
                        <div class="text-white-50" style="font-size: 0.72rem;">STATUS INTEGRASI WEB:</div>
                        <div class="text-success fw-bold d-flex align-items-center gap-1 mt-1">
                            <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span>
                            Terkoneksi ke <?= htmlspecialchars(parse_url(BASE_URL, PHP_URL_HOST) ?? 'Server') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PANDUAN CARA INSTALL: LANGKAH DEMI LANGKAH -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-book-half text-primary me-2"></i>Panduan Lengkap: Cara Install Extension di Google Chrome
                </h4>
                <p class="text-muted small mb-0">Ikuti 6 langkah mudah berikut untuk mengaktifkan ekstensi di peramban Chrome Anda.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">
                Estimasi Waktu: 1 Menit
            </span>
        </div>

        <div class="row g-4">
            <!-- Step 1 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">1</div>
                        <h6 class="fw-bold mb-0 text-dark">Download Berkas ZIP</h6>
                    </div>
                    <p class="small text-secondary mb-3">
                        Klik tombol <strong>Download Extension (.ZIP)</strong> di atas untuk mengunduh paket berkas <code>griview-chrome-extension.zip</code>.
                    </p>
                    <a href="<?= url('extension', 'download') ?>" class="btn btn-sm btn-outline-primary fw-semibold w-100">
                        <i class="bi bi-download me-1"></i> Download Sekarang
                    </a>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">2</div>
                        <h6 class="fw-bold mb-0 text-dark">Ekstrak Berkas ZIP</h6>
                    </div>
                    <p class="small text-secondary mb-0">
                        Klik kanan pada file <code>griview-chrome-extension.zip</code> yang telah diunduh, lalu pilih <strong>Ekstrak Semua (Extract All)</strong> ke folder di komputer Anda (misal ke <code>C:\griview-chrome-extension</code> atau folder Dokumen Anda).
                    </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">3</div>
                        <h6 class="fw-bold mb-0 text-dark">Buka Halaman Ekstensi</h6>
                    </div>
                    <p class="small text-secondary mb-2">
                        Buka Google Chrome, lalu salin dan buka alamat berikut pada tab baru:
                    </p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace bg-white" value="chrome://extensions/" readonly id="inputChromeUrl">
                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('chrome://extensions/'); alert('Alamat chrome://extensions/ disalin! Silakan paste di tab baru.');">
                            <i class="bi bi-clipboard"></i> Salin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">4</div>
                        <h6 class="fw-bold mb-0 text-dark">Aktifkan Developer Mode</h6>
                    </div>
                    <p class="small text-secondary mb-0">
                        Pada pojok kanan atas halaman <code>chrome://extensions/</code>, nyalakan tombol sakelar <strong>"Developer mode" (Mode Pengembang)</strong> hingga aktif berwarna biru.
                    </p>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">5</div>
                        <h6 class="fw-bold mb-0 text-dark">Klik "Load unpacked"</h6>
                    </div>
                    <p class="small text-secondary mb-0">
                        Klik tombol <strong>"Load unpacked" (Muat yang belum dibongkar)</strong> di pojok kiri atas, lalu arahkan ke folder hasil ekstrak tadi (folder yang berisi berkas <code>manifest.json</code>). Ekstensi akan langsung terpasang!
                    </p>
                </div>
            </div>

            <!-- Step 6 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-3 rounded-3 border bg-success-subtle border-success-subtle h-100 position-relative">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="badge bg-success text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: grid; place-items: center;">6</div>
                        <h6 class="fw-bold mb-0 text-dark">Buka Google Maps & Audit</h6>
                    </div>
                    <p class="small text-dark mb-0">
                        Buka profil bisnis Google Maps cabang Anda. Tombol <strong>"Audit Review"</strong> akan otomatis muncul. Begitu selesai di-scroll, <strong>data langsung tersimpan otomatis ke web GriView</strong>!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- FITUR UNGGULAN & DOKUMENTASI SISTEM -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card-custom p-4 h-100">
                <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-stars text-warning fs-4"></i>
                    <span>Kemampuan & Fitur Unggulan Ekstensi</span>
                </h5>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-3 small">
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong>Scroll Otomatis hingga 1.000 Ulasan:</strong> Mengumpulkan semua ulasan tanpa terkena pembatasan kuota resmi Google Places API (yang biasanya dibatasi hanya 5 ulasan).
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong>Penghitung Kata Otomatis (Word Counter):</strong> Menghitung kata per ulasan secara otomatis untuk mendeteksi review panjang, sedang, singkat, atau tanpa teks.
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong>Koreksi Cerdas Tanggal & Teks Tertukar:</strong> Mendeteksi dan membalik posisi teks secara otomatis jika Google merender baris tanggal di kolom ulasan.
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong>Deteksi Foto Kontributor & Foto Review:</strong> Mencatat jumlah kontribusi foto reviewer dan foto yang diunggah pada ulasan.
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0 mt-n1"></i>
                        <div>
                            <strong>Sinkronisasi Otomatis ke Database:</strong> Data hasil audit dikirim secara langsung ke GriView Web dan langsung tersimpan aman di database server Anda.
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card-custom p-4 h-100">
                <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-4"></i>
                    <span>Penyimpanan Otomatis & Format XLS</span>
                </h5>
                <p class="small text-secondary mb-3">
                    Setiap kali proses audit di extension selesai, GriView melakukan dua hal sekaligus secara otomatis:
                </p>
                <div class="p-3 rounded-3 bg-light border mb-3 small">
                    <div class="d-flex align-items-center gap-2 fw-bold text-dark mb-1">
                        <i class="bi bi-1-circle-fill text-primary"></i> Simpan ke Database Lokal
                    </div>
                    <div class="text-secondary ps-4">
                        Seluruh review masuk ke database tanpa duplikasi (idempotent), sehingga statistik audit langsung dapat diakses di menu <strong>Audit Review</strong> dan <strong>Semua Ulasan</strong>.
                    </div>
                </div>
                <div class="p-3 rounded-3 bg-light border small mb-3">
                    <div class="d-flex align-items-center gap-2 fw-bold text-dark mb-1">
                        <i class="bi bi-2-circle-fill text-success"></i> Buat Berkas XLS Otomatis
                    </div>
                    <div class="text-secondary ps-4">
                        Sistem otomatis menyusun dokumen spreadsheet <code>.XLS</code> lengkap dengan kop laporan resmi, tabel KPI audit, dan kolom detail setiap ulasan yang bisa di-download kapan saja.
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('review', 'audit') ?>" class="btn btn-outline-primary btn-sm fw-semibold w-100">
                        <i class="bi bi-shield-check me-1"></i> Lihat Data Audit Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
