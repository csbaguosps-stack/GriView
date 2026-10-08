<?php
/* License: by cs.baguosps@gmail.com */
$businessName = $selectedStore ? $selectedStore['store_name'] : DEFAULT_PLACE_NAME . ' (Semua ' . count($stores) . ' Cabang)';
$businessAddress = $selectedStore ? $selectedStore['address'] : 'Konsolidasi ulasan dari ' . count($stores) . ' cabang ' . DEFAULT_PLACE_NAME;
$findingOptions = [
    'all' => 'Semua Temuan',
    'attention' => '⚠️ Butuh Perhatian',
    'priority' => '🔴 Rating Rendah (1-2★)',
    'unanswered' => '🟡 Belum Dibalas',
    'short_text' => 'Teks Singkat (<40 Karakter)',
    'rating_only' => 'Tanpa Teks Ulasan',
    'slow_response' => 'Respons > 48 Jam',
    'clear' => '✅ Lolos Audit',
];
$queryFilters = $filters;
?>
<div class="container-fluid px-lg-4 py-4">

    <!-- Flash Notification -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning') ?> alert-dismissible fade show d-flex align-items-start shadow-sm mb-4 border-0 border-start border-4 <?= $flash['type'] === 'danger' ? 'border-danger' : ($flash['type'] === 'success' ? 'border-success' : 'border-warning') ?>" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill text-success' : ($flash['type'] === 'danger' ? 'bi-x-octagon-fill text-danger' : 'bi-exclamation-triangle-fill text-warning') ?> fs-4 me-3 mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <div class="lh-base"><?= $flash['message'] ?></div>
                <?php if (!empty($flash['action_url'])): ?>
                    <div class="mt-3">
                        <a href="<?= $flash['action_url'] ?>" class="btn btn-sm btn-success fw-bold px-3 py-2 shadow-sm text-white d-inline-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-excel-fill"></i> <?= htmlspecialchars($flash['action_text'] ?? 'Download File XLS Sekarang') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Auto-Sync Info Banner jika ada data hasil sinkronisasi Chrome Extension -->
    <?php if (!empty($lastAuditExport)): ?>
        <div class="alert alert-light border border-success-subtle bg-success-subtle bg-opacity-10 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between p-3 mb-4 rounded-3 shadow-sm gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="bi bi-cloud-check-fill fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <span>Data Audit Google Maps Tersimpan Otomatis ke Database</span>
                        <span class="badge bg-success text-white" style="font-size: 0.72rem;">Auto-Saved</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Profil: <strong><?= htmlspecialchars($lastAuditExport['place_name'] ?? '-') ?></strong> &bull; 
                        Total: <strong><?= number_format($lastAuditExport['review_count'] ?? 0) ?> ulasan</strong> &bull; 
                        Tersimpan pada: <?= htmlspecialchars($lastAuditExport['saved_at'] ?? '-') ?><?= !empty($lastAuditExport['saved_at']) && strpos($lastAuditExport['saved_at'], 'WIB') === false ? ' WIB' : '' ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('review', 'downloadAuditXls') ?>" class="btn btn-sm btn-success fw-bold text-white shadow-sm d-inline-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-file-earmark-excel-fill"></i> Download File XLS Otomatis
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Section: Place Info & Export Actions (Harmonized with Semua Ulasan) -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-patch-check-fill me-1"></i> Google Profil Bisnis Terverifikasi
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        Grup: <strong><?= htmlspecialchars($placeConfig['business_group'] ?? 'Semua Cabang') ?></strong>
                    </span>
                    <?php if ($selectedStore): ?>
                        <span class="badge bg-light text-secondary border">
                            Kode Toko: <strong><?= htmlspecialchars($selectedStore['store_code'] ?: '-') ?></strong>
                        </span>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                            <i class="bi bi-geo-alt me-1"></i>Cabang Terpilih
                        </span>
                    <?php else: ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                            <?= count($stores) ?> Cabang Terdata
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-shield-check me-1"></i>Review Intelligence & Audit
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    <?= htmlspecialchars($businessName) ?>
                </h3>
                <p class="text-muted small mb-0">
                    <i class="bi bi-pin-map me-1 text-danger"></i>
                    <?= htmlspecialchars($businessAddress) ?>
                </p>
            </div>

            <!-- Action Buttons: Export XLS Audit, XLS Rapi, Extension Chrome -->
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('analytics', 'index', $queryFilters) ?>" class="btn btn-outline-primary fw-semibold d-flex align-items-center gap-2" title="Buka grafik analisis & tren ulasan">
                    <i class="bi bi-graph-up text-primary"></i>
                    <span>Analisis & Tren</span>
                </a>
                <a href="<?= url('review', 'exportAuditXls', $queryFilters) ?>" class="btn btn-success-export d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                    <span>Export XLS Audit</span>
                </a>
                <a href="<?= url('review', 'exportXls', $queryFilters) ?>" class="btn btn-outline-success fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet fs-5"></i>
                    <span>Export XLS Rapi</span>
                </a>
                <?php if (!empty($lastAuditExport)): ?>
                    <a href="<?= url('review', 'downloadAuditXls') ?>" class="btn btn-outline-dark fw-semibold d-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Download XLS Terakhir</span>
                    </a>
                <?php endif; ?>
                <a href="<?= url('extension', 'index') ?>" class="btn btn-outline-secondary fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-puzzle"></i>
                    <span>Extension Chrome</span>
                </a>
                <button type="button" class="btn btn-outline-primary fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalSyncGoogle">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Tarik Google Maps</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Status Data & Metrik Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 rounded-3 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-5"></i>
                <span class="fw-bold">Audit Review Google Maps</span>
                <span class="badge bg-primary text-white rounded-pill"><?= number_format($stats['total']) ?> Ulasan</span>
            </div>
            <?php if (($summary['attention'] ?? 0) > 0): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-2 rounded-3">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= number_format($summary['attention']) ?> Butuh Perhatian
                </span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2 text-muted small">
            <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-arrow-repeat me-1 text-primary"></i> Data Tersinkronisasi Otomatis</span>
            <span>Menampilkan <strong><?= number_format($totalReviews) ?></strong> dari <strong><?= number_format($stats['total']) ?></strong> ulasan</span>
        </div>
    </div>

    <!-- Ringkasan Metrik / KPI Grid (100% Identik & Singkron dengan Halaman Semua Ulasan) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Ulasan -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-chat-heart-fill"></i>
                </div>
                <div>
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="stat-label">Total Ulasan (<?= ($filters['month'] !== 'all') ? formatBulanIndo($filters['month']) : 'Semua Periode' ?>)</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Rating Rata-rata -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="stat-value text-warning"><?= number_format($stats['avg_rating'], 1) ?></div>
                        <span class="text-muted fw-bold">/ 5.0</span>
                    </div>
                    <div class="stat-label">Rating Rata-rata Google</div>
                </div>
            </div>
        </div>

        <!-- Card 3: Butuh Perhatian (Audit) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="stat-value text-danger"><?= number_format($summary['attention']) ?></div>
                        <span class="text-muted small fw-bold">/ <?= number_format($stats['total']) ?></span>
                    </div>
                    <div class="stat-label">Butuh Perhatian (<?= $summary['priority'] ?> rating rendah)</div>
                </div>
            </div>
        </div>

        <!-- Card 4: Respon Toko & Belum Dibalas -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-reply-all-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= number_format($summary['unanswered']) ?></div>
                    <div class="stat-label">Belum Dibalas (<?= $stats['reply_rate'] ?>% direspon)</div>
                </div>
            </div>
        </div>

        <!-- Card 5: Analisis Kata Ulasan -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div>
                    <div class="stat-value text-success">~<?= $stats['avg_words'] ?></div>
                    <div class="stat-label">Rata-rata Kata (Total: <?= number_format($stats['total_words']) ?> kata)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER BAR TERPADU -->
    <div class="filter-bar mb-3">
        <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-end" id="form-filter-audit">
            <input type="hidden" name="c" value="review">
            <input type="hidden" name="a" value="audit">

            <!-- Filter Cabang Toko -->
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-shop me-1 text-primary"></i>Cabang Toko
                </label>
                <select name="store_id" class="form-select fw-semibold" onchange="this.form.submit()">
                    <option value="all" <?= ($filters['store_id'] === 'all') ? 'selected' : '' ?>>🏢 Semua Cabang (<?= count($stores) ?> Store)</option>
                    <?php foreach ($stores as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= ($filters['store_id'] == $st['id']) ? 'selected' : '' ?>>
                            <?= !empty($st['store_code']) ? '[' . htmlspecialchars($st['store_code']) . '] ' : '' ?><?= htmlspecialchars($st['store_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Periode Bulan -->
            <div class="col-12 col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-calendar3 me-1 text-primary"></i>Bulan
                </label>
                <select name="month" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $filters['month'] === 'all' ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach ($availableMonths as $m): ?>
                        <option value="<?= $m['review_month'] ?>" <?= $filters['month'] === $m['review_month'] ? 'selected' : '' ?>>
                            <?= formatBulanIndo($m['review_month']) ?> (<?= $m['total_reviews'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Temuan Audit -->
            <div class="col-12 col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-shield-check me-1 text-danger"></i>Temuan Audit
                </label>
                <select name="finding" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($findingOptions as $fKey => $fLabel): ?>
                        <option value="<?= htmlspecialchars($fKey) ?>" <?= ($filters['finding'] ?? 'all') === $fKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Rating -->
            <div class="col-6 col-md-1">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-star me-1 text-warning"></i>Rating
                </label>
                <select name="rating" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= ($filters['rating'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="5" <?= ($filters['rating'] ?? '') === '5' ? 'selected' : '' ?>>5★</option>
                    <option value="4" <?= ($filters['rating'] ?? '') === '4' ? 'selected' : '' ?>>4★</option>
                    <option value="3" <?= ($filters['rating'] ?? '') === '3' ? 'selected' : '' ?>>3★</option>
                    <option value="2" <?= ($filters['rating'] ?? '') === '2' ? 'selected' : '' ?>>2★</option>
                    <option value="1" <?= ($filters['rating'] ?? '') === '1' ? 'selected' : '' ?>>1★</option>
                </select>
            </div>

            <!-- Filter Sentimen -->
            <div class="col-6 col-md-1">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-emoji-smile me-1 text-success"></i>Sentimen
                </label>
                <select name="sentiment" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= ($filters['sentiment'] ?? 'all') === 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="positive" <?= ($filters['sentiment'] ?? '') === 'positive' ? 'selected' : '' ?>>Positif</option>
                    <option value="neutral" <?= ($filters['sentiment'] ?? '') === 'neutral' ? 'selected' : '' ?>>Netral</option>
                    <option value="negative" <?= ($filters['sentiment'] ?? '') === 'negative' ? 'selected' : '' ?>>Negatif</option>
                </select>
            </div>

            <!-- Filter Jumlah Kata -->
            <div class="col-6 col-md-1">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-file-text me-1 text-secondary"></i>Kata
                </label>
                <select name="words" class="form-select" onchange="this.form.submit()">
                    <option value="all"   <?= ($filters['words'] ?? 'all') === 'all'   ? 'selected' : '' ?>>Semua</option>
                    <option value="none"  <?= ($filters['words'] ?? '') === 'none'   ? 'selected' : '' ?>>0 kata</option>
                    <option value="1_9"   <?= ($filters['words'] ?? '') === '1_9'    ? 'selected' : '' ?>>1–9 kata</option>
                    <option value="10_19" <?= ($filters['words'] ?? '') === '10_19'  ? 'selected' : '' ?>>10–19 kata</option>
                    <option value="20_29" <?= ($filters['words'] ?? '') === '20_29'  ? 'selected' : '' ?>>20–29 kata</option>
                    <option value="30plus"<?= ($filters['words'] ?? '') === '30plus' ? 'selected' : '' ?>>30 - 1000/lebih kata</option>
                </select>
            </div>

            <!-- Filter Jumlah Foto -->
            <div class="col-6 col-md-1">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-image me-1 text-info"></i>Foto
                </label>
                <select name="photos" class="form-select" onchange="this.form.submit()">
                    <option value="all"       <?= ($filters['photos'] ?? 'all') === 'all'       ? 'selected' : '' ?>>Semua</option>
                    <option value="has_photo" <?= ($filters['photos'] ?? '') === 'has_photo'   ? 'selected' : '' ?>>Ada Foto</option>
                    <option value="0"         <?= ($filters['photos'] ?? '') === '0'           ? 'selected' : '' ?>>0 foto</option>
                    <option value="1"         <?= ($filters['photos'] ?? '') === '1'           ? 'selected' : '' ?>>1 foto</option>
                    <option value="2"         <?= ($filters['photos'] ?? '') === '2'           ? 'selected' : '' ?>>2 foto</option>
                    <option value="3"         <?= ($filters['photos'] ?? '') === '3'           ? 'selected' : '' ?>>3 foto</option>
                    <option value="4"         <?= ($filters['photos'] ?? '') === '4'           ? 'selected' : '' ?>>4 foto</option>
                    <option value="5"         <?= ($filters['photos'] ?? '') === '5'           ? 'selected' : '' ?>>5 foto</option>
                    <option value="5plus"     <?= ($filters['photos'] ?? '') === '5plus'       ? 'selected' : '' ?>>Lebih dari 5</option>
                </select>
            </div>

            <!-- Cari Kata Kunci -->
            <div class="col-12 col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">
                    <i class="bi bi-search me-1 text-secondary"></i>Cari
                </label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Reviewer / teks..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit" title="Cari"><i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- Tombol Reset Filter -->
            <?php
            $hasActiveFilter = ($filters['store_id'] !== 'all') || ($filters['month'] !== 'all')
                || (($filters['finding'] ?? 'all') !== 'all') || (($filters['rating'] ?? 'all') !== 'all')
                || (($filters['sentiment'] ?? 'all') !== 'all') || (($filters['words'] ?? 'all') !== 'all')
                || (($filters['photos'] ?? 'all') !== 'all') || !empty($filters['search']);
            ?>
            <?php if ($hasActiveFilter): ?>
                <div class="col-12 mt-1">
                    <a href="<?= url('review', 'audit', ['reset' => 1]) ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i> Reset Semua Filter
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- TOOLBAR TANDAI & HAPUS (Bulk Actions) -->
    <form id="form-bulk-delete" method="POST" action="<?= url('review', 'deleteMultiple') ?>">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" id="bulk-toolbar">
            <!-- Info jumlah terpilih -->
            <div class="d-flex align-items-center gap-2">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="checkAll" title="Pilih semua yang tampil">
                    <label class="form-check-label fw-semibold small text-muted" for="checkAll">Pilih Semua Halaman Ini</label>
                </div>
                <span id="selected-count-badge" class="badge bg-primary-subtle text-primary border border-primary-subtle d-none px-2 py-1">
                    <span id="selected-count">0</span> dipilih
                </span>
            </div>

            <!-- Aksi Hapus -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" id="btn-delete-selected" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 d-none" onclick="confirmBulkDelete()">
                    <i class="bi bi-trash-fill"></i>
                    <span>Hapus yang Dipilih (<span class="selected-count-inline">0</span>)</span>
                </button>
                <?php
                $hasActiveFilters = false;
                $filterKeys = ['store_id', 'month', 'year', 'rating', 'sentiment', 'reply_status', 'finding', 'words', 'photos', 'search'];
                foreach ($filterKeys as $k) {
                    if (!empty($filters[$k]) && $filters[$k] !== 'all') {
                        $hasActiveFilters = true;
                        break;
                    }
                }
                ?>
                <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="confirmDeleteAll()">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= $hasActiveFilters ? 'Hapus Data Terfilter' : 'Hapus Semua Data' ?> (<?= number_format($totalReviews) ?>)</span>
                </button>
            </div>
        </div>

    <!-- TABEL HASIL AUDIT -->
    <div class="card-custom overflow-hidden mb-4">
        <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 bg-light bg-opacity-50">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-table text-primary"></i>
                    <span>Tabel Temuan Audit Ulasan</span>
                </h5>
                <small class="text-muted">Centang ulasan untuk ditandai, lalu pilih aksi hapus dari toolbar atas.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm">
                    Total: <strong><?= number_format($totalReviews) ?></strong> ulasan cocok
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0" id="audit-table">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="checkAllInTable" class="form-check-input" title="Pilih semua baris">
                        </th>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="min-width: 160px;">Cabang Store</th>
                        <th style="min-width: 170px;">Reviewer</th>
                        <th style="min-width: 110px;">Rating & Sentimen</th>
                        <th style="min-width: 280px;">Isi Ulasan & Tanggapan Owner</th>
                        <th style="width: 100px;" class="text-center">Kata</th>
                        <th style="width: 100px;" class="text-center">Foto</th>
                        <th style="min-width: 160px;">Temuan Audit</th>
                        <th style="width: 90px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted py-4">
                                    <i class="bi bi-shield-x display-4 d-block mb-3 text-secondary"></i>
                                    <h6 class="fw-bold text-dark">Tidak ada ulasan yang cocok dengan kriteria audit</h6>
                                    <p class="small mb-3">Cobalah mengubah filter atau reset semua kriteria pencarian.</p>
                                    <a href="<?= url('review', 'audit', ['reset' => 1]) ?>" class="btn btn-sm btn-outline-primary">Reset Filter Audit</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $startNo = ($page - 1) * $limit + 1;
                        foreach ($reviews as $rev): 
                            $hasReply = !empty($rev['owner_reply']);
                            $sentiment = $rev['sentiment'] ?? ($rev['rating'] >= 4 ? 'positive' : ($rev['rating'] == 3 ? 'neutral' : 'negative'));
                            $rawText = trim($rev['review_text'] ?? '');
                            $wordCount = (int)($rev['word_count'] ?? Review::countWords($rawText));
                            $charCount = mb_strlen($rawText);
                            $photoCount = (int)($rev['review_photo_count'] ?? 0);
                            $findings = $rev['audit_findings'] ?? [];
                        ?>
                            <tr id="row-audit-<?= $rev['id'] ?>" class="audit-row">
                                <!-- Checkbox -->
                                <td class="text-center">
                                    <input type="checkbox" name="review_ids[]" value="<?= $rev['id'] ?>" class="form-check-input row-checkbox">
                                </td>

                                <!-- No -->
                                <td class="text-center text-muted fw-bold small"><?= $startNo++ ?></td>

                                <!-- Cabang Store -->
                                <td>
                                    <div class="fw-semibold text-dark small" title="<?= htmlspecialchars($rev['place_name'] ?? DEFAULT_PLACE_NAME) ?>">
                                        <i class="bi bi-shop text-primary me-1"></i><?= htmlspecialchars($rev['place_name'] ?? DEFAULT_PLACE_NAME) ?>
                                    </div>
                                    <?php if (!empty($rev['store_code'])): ?>
                                        <small class="badge bg-light text-secondary border mt-1" style="font-size: 0.68rem;">
                                            Kode: <?= htmlspecialchars($rev['store_code']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <!-- Reviewer -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($rev['author_photo_url'])): ?>
                                            <img src="<?= htmlspecialchars($rev['author_photo_url']) ?>" alt="Avatar" class="avatar-img" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($rev['author_name']) ?>&background=e2e8f0&color=475569';">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?= strtoupper(substr($rev['author_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 130px;">
                                                <?= htmlspecialchars($rev['author_name'] ?? 'Pengguna Google') ?>
                                            </div>
                                            <div class="text-muted small d-flex flex-wrap align-items-center gap-1 mt-0.5" style="font-size: 0.73rem;">
                                                <?php if (!empty($rev['review_date_text'])): ?>
                                                    <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 500;" title="Waktu asli Google Maps"><?= htmlspecialchars($rev['review_date_text']) ?></span>
                                                <?php endif; ?>
                                                <span><?= date('d M Y, H:i', strtotime($rev['review_time'] ?? 'now')) ?></span>
                                            </div>
                                            <?php if (!empty($rev['is_local_guide'])): ?>
                                                <span class="badge-local-guide d-inline-block mt-1">★ Local Guide</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rating & Sentimen -->
                                <td>
                                    <div class="star-rating-box">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= ($rev['rating'] ?? 5) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="small fw-bold text-muted"><?= $rev['rating'] ?? 5 ?>.0</span>
                                        <?php if ($sentiment === 'positive'): ?>
                                            <span class="badge badge-sentiment-positive py-0 px-1" style="font-size: 0.68rem;">Positif</span>
                                        <?php elseif ($sentiment === 'neutral'): ?>
                                            <span class="badge badge-sentiment-neutral py-0 px-1" style="font-size: 0.68rem;">Netral</span>
                                        <?php else: ?>
                                            <span class="badge badge-sentiment-negative py-0 px-1" style="font-size: 0.68rem;">Negatif</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Isi Ulasan & Tanggapan Owner -->
                                <td>
                                    <?php
                                    $isLong = mb_strlen($rawText) > 130 || $wordCount > 20;
                                    $shortSnippet = $isLong ? mb_substr($rawText, 0, 120) . '...' : $rawText;
                                    ?>
                                    <?php if (empty($rawText)): ?>
                                        <span class="text-muted fst-italic small">
                                            <i class="bi bi-dash-circle me-1"></i>Review hanya memberi rating bintang tanpa komentar teks.
                                        </span>
                                    <?php elseif ($isLong): ?>
                                        <div class="comment-container mb-2">
                                            <div class="comment-short-text text-secondary" style="white-space: pre-line;">
                                                <?= nl2br(htmlspecialchars($shortSnippet)) ?>
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none fw-semibold btn-expand-row-comment" data-id="<?= $rev['id'] ?>" style="font-size: 0.78rem;">
                                                    ... Lihat ulasan lengkap (more)
                                                </button>
                                            </div>
                                            <div class="comment-full-text text-secondary d-none" style="white-space: pre-line;">
                                                <?= nl2br(htmlspecialchars($rawText)) ?>
                                                <div class="mt-1 d-flex align-items-center gap-2">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                        <i class="bi bi-check2-circle me-1"></i>Ulasan utuh: <strong><?= $wordCount ?> kata</strong>
                                                    </span>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none btn-collapse-row-comment" data-id="<?= $rev['id'] ?>" style="font-size: 0.78rem;">
                                                        <i class="bi bi-chevron-up small"></i> Tutup ulasan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-secondary mb-2" style="white-space: pre-line;">
                                            <?= nl2br(htmlspecialchars($rawText)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Tanggapan Owner -->
                                    <?php if ($hasReply): ?>
                                        <div class="owner-reply-card">
                                            <div class="owner-reply-title">
                                                <i class="bi bi-reply-fill"></i> Balasan Pemilik:
                                                <span class="text-muted fw-normal ms-auto small">
                                                    <?= !empty($rev['owner_reply_time']) ? date('d/m/Y H:i', strtotime($rev['owner_reply_time'])) : '' ?>
                                                </span>
                                            </div>
                                            <div class="text-dark small">
                                                <?= nl2br(htmlspecialchars($rev['owner_reply'])) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border small">
                                            <i class="bi bi-clock me-1"></i>Belum dibalas
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Jumlah Kata -->
                                <td class="text-center" style="white-space: nowrap;">
                                    <?php
                                    if ($wordCount > 30) {
                                        $wcBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                    } elseif ($wordCount >= 20) {
                                        $wcBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                    } elseif ($wordCount >= 10) {
                                        $wcBadgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                    } elseif ($wordCount > 0) {
                                        $wcBadgeClass = 'bg-light text-muted border';
                                    } else {
                                        $wcBadgeClass = 'bg-secondary-subtle text-secondary border';
                                    }
                                    ?>
                                    <span class="badge <?= $wcBadgeClass ?> px-2 py-1 fw-bold d-inline-block" title="<?= $wordCount ?> kata">
                                        <i class="bi bi-file-text me-1"></i><?= $wordCount ?>
                                    </span>
                                    <div class="text-muted small mt-1" style="font-size: 0.72rem;"><?= $charCount > 0 ? $charCount . ' chr' : '0 chr' ?></div>
                                </td>

                                <!-- Foto -->
                                <td class="text-center" style="white-space: nowrap;">
                                    <?php if ($photoCount > 0): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 fw-bold" title="<?= $photoCount ?> foto ulasan">
                                            <i class="bi bi-image me-1"></i><?= $photoCount ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                    <?php $profilePhotos = (int)($rev['reviewer_photo_count'] ?? 0); ?>
                                    <div class="text-muted small mt-1" style="font-size: 0.72rem;" title="Foto profil kontributor">
                                        <?= $profilePhotos > 0 ? '👤 ' . $profilePhotos : '' ?>
                                    </div>
                                </td>

                                <!-- Temuan Audit -->
                                <td>
                                    <?php if (empty($findings)): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check2-circle me-1"></i>Lolos Audit
                                        </span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($findings as $af): ?>
                                                <span class="badge <?= $af['severity'] === 'high' ? 'bg-danger-subtle text-danger border border-danger-subtle' : ($af['severity'] === 'medium' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border') ?>" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars($af['label']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <a href="<?= url('review', 'delete', ['id' => $rev['id']]) ?>" 
                                       class="btn btn-sm btn-outline-danger shadow-xs px-2.5 py-1" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini dari sistem?');"
                                       title="Hapus Ulasan">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Footer -->
        <div class="p-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="text-muted small">
                Menampilkan <strong><?= count($reviews) ?></strong> dari <strong><?= number_format($totalReviews) ?></strong> ulasan hasil audit
            </span>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigasi Halaman Audit">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('review', 'audit', array_merge($queryFilters, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php
                        $pStart = max(1, $page - 2);
                        $pEnd   = min($totalPages, $page + 2);
                        if ($pStart > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= url('review', 'audit', array_merge($queryFilters, ['page' => 1])) ?>">1</a></li>
                            <?php if ($pStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $pStart; $p <= $pEnd; $p++): ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('review', 'audit', array_merge($queryFilters, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($pEnd < $totalPages): ?>
                            <?php if ($pEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= url('review', 'audit', array_merge($queryFilters, ['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('review', 'audit', array_merge($queryFilters, ['page' => $page + 1])) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    </form><!-- /form-bulk-delete -->

    <!-- Catatan & Panduan -->
    <div class="alert alert-light border small text-muted d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-info-circle text-primary fs-5 flex-shrink-0"></i>
        <div>
            Sistem Audit Review memeriksa setiap ulasan pelanggan secara komprehensif (rating rendah, belum dibalas, ulasan singkat, tanpa teks, respons lambat). Semua data ulasan tersimpan secara terpusat di database dan siap di-export ke format Excel/XLS.
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Data (Audit) -->
<div class="modal fade" id="modalDeleteAll" tabindex="-1" aria-labelledby="modalDeleteAllLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalDeleteAllLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $hasActiveFilters ? 'Hapus Ulasan Sesuai Filter' : 'Hapus Semua Data Ulasan' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-flex gap-3 align-items-start mb-3">
                    <i class="bi bi-shield-x fs-3 text-danger flex-shrink-0"></i>
                    <div>
                        <strong>Tindakan Tidak Dapat Dibatalkan!</strong><br>
                        <?php if ($hasActiveFilters): ?>
                            Sebanyak <strong><?= number_format($totalReviews) ?> ulasan</strong> yang cocok dengan filter yang Anda pilih akan dihapus permanen dari database. Ulasan di luar filter yang dipilih akan tetap aman.
                        <?php else: ?>
                            Seluruh <strong><?= number_format($totalReviews) ?> ulasan</strong> di sistem akan dihapus permanen dari database JSON. Data yang sudah dihapus tidak dapat dipulihkan kembali.
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small mb-0">Pastikan Anda sudah mengunduh file XLS sebelum menghapus data.</p>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal, Kembali</button>
                <form method="POST" action="<?= url('review', 'deleteAll') ?>" class="d-inline">
                    <input type="hidden" name="store_id" value="<?= htmlspecialchars($filters['store_id'] ?? 'all') ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($filters['month'] ?? 'all') ?>">
                    <input type="hidden" name="rating" value="<?= htmlspecialchars($filters['rating'] ?? 'all') ?>">
                    <input type="hidden" name="sentiment" value="<?= htmlspecialchars($filters['sentiment'] ?? 'all') ?>">
                    <input type="hidden" name="reply_status" value="<?= htmlspecialchars($filters['reply_status'] ?? 'all') ?>">
                    <input type="hidden" name="finding" value="<?= htmlspecialchars($filters['finding'] ?? 'all') ?>">
                    <input type="hidden" name="words" value="<?= htmlspecialchars($filters['words'] ?? 'all') ?>">
                    <input type="hidden" name="photos" value="<?= htmlspecialchars($filters['photos'] ?? 'all') ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-trash-fill me-1"></i> Ya, Hapus <?= $hasActiveFilters ? 'Data Terfilter' : 'Semua Data' ?> (<?= number_format($totalReviews) ?> Ulasan)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/* License: by cs.baguosps@gmail.com */
document.addEventListener('DOMContentLoaded', function () {
    // Handler pembukaan ulasan panjang (more)
    document.addEventListener('click', function(e) {
        const expandBtn = e.target.closest('.btn-expand-row-comment');
        if (expandBtn) {
            const container = expandBtn.closest('.comment-container');
            if (container) {
                container.querySelector('.comment-short-text').classList.add('d-none');
                container.querySelector('.comment-full-text').classList.remove('d-none');
            }
        }
        const collapseBtn = e.target.closest('.btn-collapse-row-comment');
        if (collapseBtn) {
            const container = collapseBtn.closest('.comment-container');
            if (container) {
                container.querySelector('.comment-short-text').classList.remove('d-none');
                container.querySelector('.comment-full-text').classList.add('d-none');
            }
        }
    });

    // Checkbox logic
    const checkAll       = document.getElementById('checkAll');
    const checkAllTable  = document.getElementById('checkAllInTable');
    const rowCheckboxes  = () => document.querySelectorAll('.row-checkbox');
    const countBadge     = document.getElementById('selected-count-badge');
    const countSpan      = document.getElementById('selected-count');
    const countInlines   = document.querySelectorAll('.selected-count-inline');
    const btnDelSelected = document.getElementById('btn-delete-selected');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.row-checkbox:checked').length;
        const total   = rowCheckboxes().length;
        countSpan.textContent = checked;
        countInlines.forEach(el => el.textContent = checked);

        if (checked > 0) {
            countBadge.classList.remove('d-none');
            btnDelSelected.classList.remove('d-none');
        } else {
            countBadge.classList.add('d-none');
            btnDelSelected.classList.add('d-none');
        }

        const allChecked = checked === total && total > 0;
        if (checkAll) checkAll.checked = allChecked;
        if (checkAllTable) checkAllTable.checked = allChecked;

        // Highlight baris yang dipilih
        rowCheckboxes().forEach(cb => {
            cb.closest('tr').classList.toggle('table-warning', cb.checked);
        });
    }

    function toggleAll(checked) {
        rowCheckboxes().forEach(cb => { cb.checked = checked; });
        if (checkAll) checkAll.checked = checked;
        if (checkAllTable) checkAllTable.checked = checked;
        updateBulkUI();
    }

    if (checkAll) checkAll.addEventListener('change', () => toggleAll(checkAll.checked));
    if (checkAllTable) checkAllTable.addEventListener('change', () => toggleAll(checkAllTable.checked));

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) updateBulkUI();
    });
});

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const count = checked.length;
    if (count === 0) {
        alert('Pilih minimal 1 ulasan terlebih dahulu!');
        return;
    }
    if (confirm(`Anda akan menghapus ${count} ulasan yang ditandai.\n\nTindakan ini TIDAK DAPAT dibatalkan. Lanjutkan?`)) {
        document.getElementById('form-bulk-delete').submit();
    }
}

function confirmDeleteAll() {
    const modal = new bootstrap.Modal(document.getElementById('modalDeleteAll'));
    modal.show();
}
</script>
