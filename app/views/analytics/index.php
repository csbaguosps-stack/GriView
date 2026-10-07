<?php
/* License: by cs.baguosps@gmail.com */
$businessName = $selectedStore ? $selectedStore['store_name'] : DEFAULT_PLACE_NAME . ' (Semua ' . count($stores) . ' Cabang)';
$businessAddress = $selectedStore ? $selectedStore['address'] : 'Konsolidasi ulasan dari ' . count($stores) . ' cabang ' . DEFAULT_PLACE_NAME;

$findingOptions = [
    'all'           => 'Semua Temuan',
    'attention'     => '⚠️ Butuh Perhatian',
    'priority'      => '🔴 Rating Rendah (1-2★)',
    'unanswered'    => '🟡 Belum Dibalas',
    'short_text'    => 'Teks Singkat (<40 Karakter)',
    'rating_only'   => 'Tanpa Teks Ulasan',
    'slow_response' => 'Respons > 48 Jam',
    'clear'         => '✅ Lolos Audit',
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

    <!-- Header Section: Profil & Tombol Aksi (Sinkron dengan Audit Review) -->
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
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-arrow-repeat me-1"></i>Sinkron dengan Audit Review
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

            <!-- Action Buttons: Pintas Audit, Export XLS, Extension, Tarik Data -->
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('review', 'audit', $queryFilters) ?>" class="btn btn-outline-primary fw-semibold d-flex align-items-center gap-2" title="Buka data hasil filter langsung di Audit Review">
                    <i class="bi bi-shield-check text-primary"></i>
                    <span>Buka Audit Review</span>
                </a>
                <a href="<?= url('review', 'exportAuditXls', $queryFilters) ?>" class="btn btn-success-export d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                    <span>Export XLS Audit</span>
                </a>
                <a href="<?= url('review', 'exportXls', $queryFilters) ?>" class="btn btn-outline-success fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet fs-5"></i>
                    <span>Export XLS Rapi</span>
                </a>
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
                <i class="bi bi-graph-up fs-5"></i>
                <span class="fw-bold">Analisis & Tren Ulasan Google Maps</span>
                <span class="badge bg-primary text-white rounded-pill"><?= number_format($stats['total']) ?> Ulasan</span>
            </div>
            <?php if (($summary['attention'] ?? 0) > 0): ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-2 rounded-3">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= number_format($summary['attention']) ?> Butuh Perhatian
                </span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2 text-muted small">
            <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-arrow-repeat me-1 text-primary"></i> 100% Sinkron Dua Arah</span>
            <span>Menampilkan <strong><?= number_format($totalReviews) ?></strong> dari <strong><?= number_format($stats['total']) ?></strong> ulasan cocok</span>
        </div>
    </div>

    <!-- Ringkasan Metrik / KPI Grid (100% Identik & Singkron dengan Audit Review) -->
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
                <div class="stat-icon <?= ($summary['attention'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="stat-value <?= ($summary['attention'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($summary['attention'] ?? 0) ?></div>
                        <span class="text-muted small fw-bold">/ <?= number_format($stats['total']) ?></span>
                    </div>
                    <div class="stat-label">Butuh Perhatian (<?= $summary['priority'] ?? 0 ?> rating rendah)</div>
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
                    <div class="stat-value text-primary"><?= number_format($summary['unanswered'] ?? 0) ?></div>
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

    <!-- FILTER BAR TERPADU (Identik 100% & Singkron dengan Audit Review) -->
    <div class="filter-bar mb-4">
        <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-end">
            <input type="hidden" name="c" value="analytics">
            <input type="hidden" name="a" value="index">

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
                    <i class="bi bi-calendar3 me-1 text-primary"></i>Filter Bulan
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
                    <input type="text" name="search" class="form-control" placeholder="Cari reviewer / teks..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit" title="Cari"><i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- Tombol Reset Filter jika ada filter aktif -->
            <?php
            $hasActiveFilter = ($filters['store_id'] !== 'all') || ($filters['month'] !== 'all')
                || (($filters['finding'] ?? 'all') !== 'all') || (($filters['rating'] ?? 'all') !== 'all')
                || (($filters['sentiment'] ?? 'all') !== 'all') || (($filters['words'] ?? 'all') !== 'all')
                || (($filters['photos'] ?? 'all') !== 'all') || !empty($filters['search']);
            ?>
            <?php if ($hasActiveFilter): ?>
                <div class="col-12 mt-2">
                    <a href="<?= url('analytics', 'index', ['reset' => 1]) ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i> Reset Semua Filter
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Charts Row 1: Tren Bulanan & Proporsi Sentimen -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Tren Ulasan Bulanan (Line & Bar) -->
        <div class="col-12 col-lg-8">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Tren Jumlah Ulasan & Rating Bulanan</h5>
                        <small class="text-muted">Grafik tren historis ulasan Google Maps cabang terkait</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Lintas Waktu</span>
                </div>
                <div style="height: 310px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Distribusi Sentimen (Doughnut) -->
        <div class="col-12 col-lg-4">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Proporsi Sentimen</h5>
                        <small class="text-muted"><?= $filters['month'] !== 'all' ? formatBulanIndo($filters['month']) : 'Semua Periode' ?></small>
                    </div>
                    <span class="badge bg-light text-secondary border"><?= number_format($stats['total']) ?> Total</span>
                </div>
                <div style="height: 230px;" class="d-flex align-items-center justify-content-center">
                    <canvas id="sentimentChart"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                    <div>
                        <div class="fw-bold text-success fs-5"><?= $stats['positive'] ?></div>
                        <small class="text-muted fw-semibold">Positif</small>
                    </div>
                    <div>
                        <div class="fw-bold text-warning fs-5"><?= $stats['neutral'] ?></div>
                        <small class="text-muted fw-semibold">Netral</small>
                    </div>
                    <div>
                        <div class="fw-bold text-danger fs-5"><?= $stats['negative'] ?></div>
                        <small class="text-muted fw-semibold">Negatif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Distribusi Rating Bintang & Rangkuman Kinerja Bulanan -->
    <div class="row g-4 mb-4">
        <!-- Chart 3: Distribusi Bintang 1-5 -->
        <div class="col-12 col-lg-6">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Sebaran Rating Bintang (1 - 5★)</h5>
                        <small class="text-muted">Distribusi kepuasan pelanggan pada filter saat ini</small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">⭐ <?= number_format($stats['avg_rating'], 1) ?> / 5.0</span>
                </div>
                <div style="height: 270px;">
                    <canvas id="ratingBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabel Rangkuman Kinerja Bulanan -->
        <div class="col-12 col-lg-6">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Rangkuman Kinerja Bulanan</h5>
                        <small class="text-muted">Tersedia <?= count($availableMonths) ?> periode bulan terdata</small>
                    </div>
                    <span class="badge bg-light text-secondary border">Data Tersinkron</span>
                </div>
                <div class="table-responsive" style="max-height: 270px; overflow-y: auto;">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Periode Bulan</th>
                                <th class="text-center">Total Ulasan</th>
                                <th class="text-center">Rating Rata-rata</th>
                                <th class="text-end">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($availableMonths)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                                        Belum ada ulasan untuk cabang ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($availableMonths as $bm): ?>
                                    <?php $isActiveMonth = ($filters['month'] === $bm['review_month']); ?>
                                    <tr class="<?= $isActiveMonth ? 'table-primary bg-primary bg-opacity-10 fw-semibold' : '' ?>">
                                        <td>
                                            <a href="<?= url('analytics', 'index', array_merge($filters, ['month' => $bm['review_month']])) ?>" class="text-decoration-none d-inline-flex align-items-center gap-1.5 <?= $isActiveMonth ? 'fw-bold text-primary' : 'text-dark' ?>" title="Klik untuk memfilter ulasan bulan ini">
                                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                <strong><?= formatBulanIndo($bm['review_month']) ?></strong>
                                                <?php if ($isActiveMonth): ?>
                                                    <span class="badge bg-primary text-white ms-1" style="font-size: 0.68rem;">Filter Aktif</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <a href="<?= url('analytics', 'index', array_merge($filters, ['month' => $bm['review_month']])) ?>" class="badge <?= $isActiveMonth ? 'bg-primary text-white' : 'bg-light text-dark border' ?> text-decoration-none px-2 py-1">
                                                <?= $bm['total_reviews'] ?> ulasan
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                                ⭐ <?= $bm['avg_rating'] ?> / 5.0
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('review', 'exportXls', array_merge($filters, ['month' => $bm['review_month']])) ?>" class="btn btn-sm btn-outline-success py-0 px-2" title="Download XLS Bulan Ini">
                                                <i class="bi bi-file-earmark-excel"></i> XLS
                                            </a>
                                            <a href="<?= url('review', 'audit', array_merge($filters, ['month' => $bm['review_month']])) ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Buka di Audit Review">
                                                <i class="bi bi-shield-check"></i> Audit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Analisis Kedalaman Kata & Top Ulasan -->
    <div class="row g-4 mb-4">
        <!-- Card Statistik Kata & Sebaran -->
        <div class="col-12 col-lg-7">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">
                            <i class="bi bi-file-earmark-text text-primary me-2"></i>Statistik Kedalaman Ulasan (Hitung Kata)
                        </h5>
                        <p class="text-muted small mb-0">Setiap komentar ulasan diperiksa dan dihitung jumlah katanya secara otomatis</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        <?= number_format($stats['total_words'] ?? 0) ?> Total Kata
                    </span>
                </div>

                <!-- Mini KPI Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Rata-rata Kata</small>
                            <span class="fs-5 fw-bold text-primary"><?= $stats['avg_words'] ?? 0 ?></span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">semua ulasan</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Rata-rata Berteks</small>
                            <span class="fs-5 fw-bold text-success"><?= $stats['avg_words_with_text'] ?? 0 ?></span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">ulasan dgn teks</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Ulasan Terpanjang</small>
                            <span class="fs-5 fw-bold text-warning"><?= $stats['max_words'] ?? 0 ?></span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">kata maks</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Ulasan Berteks</small>
                            <span class="fs-5 fw-bold text-dark"><?= $stats['with_text_count'] ?? 0 ?></span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">dari <?= $stats['total'] ?? 0 ?></small>
                        </div>
                    </div>
                </div>

                <!-- Tabel Distribusi Kategori Panjang Kata -->
                <?php
                $wb = $stats['word_breakdown'] ?? ['long' => 0, 'medium' => 0, 'short' => 0, 'none' => 0];
                $totalCount = max(1, (int)($stats['total'] ?? 1));
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori Panjang Ulasan</th>
                                <th>Kriteria Kata</th>
                                <th class="text-center">Jumlah Ulasan</th>
                                <th class="text-center">Proporsi</th>
                                <th class="text-end">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">
                                        <i class="bi bi-body-text me-1"></i>Panjang
                                    </span>
                                    <span class="fw-semibold">Ulasan Mendalam</span>
                                </td>
                                <td><code>&gt; 30 kata</code></td>
                                <td class="text-center fw-bold"><?= $wb['long'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                            <div class="progress-bar bg-primary" style="width: <?= round(($wb['long'] / $totalCount) * 100) ?>%"></div>
                                        </div>
                                        <span><?= round(($wb['long'] / $totalCount) * 100) ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('review', 'audit', array_merge($filters, ['sort' => 'words_desc'])) ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                        Audit
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle me-1">
                                        <i class="bi bi-chat-left-text me-1"></i>Sedang
                                    </span>
                                    <span class="fw-semibold">Ulasan Standar</span>
                                </td>
                                <td><code>10 - 30 kata</code></td>
                                <td class="text-center fw-bold"><?= $wb['medium'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                            <div class="progress-bar bg-info" style="width: <?= round(($wb['medium'] / $totalCount) * 100) ?>%"></div>
                                        </div>
                                        <span><?= round(($wb['medium'] / $totalCount) * 100) ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('review', 'audit', array_merge($filters, ['sort' => 'words_desc'])) ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                        Audit
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-muted border me-1">
                                        <i class="bi bi-chat-text me-1"></i>Singkat
                                    </span>
                                    <span class="fw-semibold">Ulasan Ringkas</span>
                                </td>
                                <td><code>1 - 9 kata</code></td>
                                <td class="text-center fw-bold"><?= $wb['short'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                            <div class="progress-bar bg-secondary" style="width: <?= round(($wb['short'] / $totalCount) * 100) ?>%"></div>
                                        </div>
                                        <span><?= round(($wb['short'] / $totalCount) * 100) ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('review', 'audit', array_merge($filters, ['finding' => 'short_text'])) ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                        Audit
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border me-1">
                                        <i class="bi bi-star me-1"></i>Bintang Saja
                                    </span>
                                    <span class="fw-semibold">Tanpa Teks</span>
                                </td>
                                <td><code>0 kata</code></td>
                                <td class="text-center fw-bold"><?= $wb['none'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                            <div class="progress-bar bg-warning" style="width: <?= round(($wb['none'] / $totalCount) * 100) ?>%"></div>
                                        </div>
                                        <span><?= round(($wb['none'] / $totalCount) * 100) ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('review', 'audit', array_merge($filters, ['finding' => 'rating_only'])) ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                        Audit
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ulasan Terpanjang & Paling Berbobot -->
        <div class="col-12 col-lg-5">
            <div class="card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Ulasan Terpanjang & Berbobot</h5>
                        <small class="text-muted">Top 5 ulasan berbobot hasil filter aktif</small>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Top Words</span>
                </div>

                <div class="d-flex flex-column gap-2" style="max-height: 380px; overflow-y: auto;">
                    <?php if (empty($topWordReviews)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-chat-square-text fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada ulasan teks untuk kriteria filter ini.
                        </div>
                    <?php else: ?>
                        <?php foreach ($topWordReviews as $twr): ?>
                            <?php
                            $wc = (int)($twr['word_count'] ?? Review::countWords($twr['review_text'] ?? ''));
                            $txt = $twr['review_text'] ?? '';
                            $isLong = mb_strlen($txt) > 120;
                            ?>
                            <div class="p-2 border rounded bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <strong class="small text-truncate" style="max-width: 140px;"><?= htmlspecialchars($twr['author_name'] ?? 'Pengguna') ?></strong>
                                        <span class="text-warning small" style="font-size: 0.75rem;">
                                            <?= str_repeat('★', max(1, min(5, (int)($twr['rating'] ?? 5)))) ?>
                                        </span>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" style="font-size: 0.75rem;">
                                        <?= $wc ?> kata
                                    </span>
                                </div>
                                <div class="small text-secondary review-expand-wrapper">
                                    <?php if ($isLong): ?>
                                        <span class="short-text"><?= nl2br(htmlspecialchars(mb_substr($txt, 0, 120))) ?>...</span>
                                        <span class="full-text d-none"><?= nl2br(htmlspecialchars($txt)) ?></span>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none fw-semibold" style="font-size: 0.75rem;" onclick="
                                            const wrap = this.closest('.review-expand-wrapper');
                                            const isExp = wrap.querySelector('.short-text').classList.contains('d-none');
                                            if (isExp) {
                                                wrap.querySelector('.short-text').classList.remove('d-none');
                                                wrap.querySelector('.full-text').classList.add('d-none');
                                                this.textContent = '... Lihat ulasan lengkap (more)';
                                            } else {
                                                wrap.querySelector('.short-text').classList.add('d-none');
                                                wrap.querySelector('.full-text').classList.remove('d-none');
                                                this.textContent = 'Tutup ulasan';
                                            }
                                        ">... Lihat ulasan lengkap (more)</button>
                                    <?php else: ?>
                                        <span><?= !empty($txt) ? nl2br(htmlspecialchars($txt)) : '<em class="text-muted">Hanya rating bintang</em>' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TOOLBAR TANDAI & HAPUS (Bulk Actions - Analytics) -->
    <form id="form-bulk-delete-analytics" method="POST" action="<?= url('review', 'deleteMultiple') ?>">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" id="bulk-toolbar-analytics">
            <!-- Info jumlah terpilih -->
            <div class="d-flex align-items-center gap-2">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="checkAllAnalytics" title="Pilih semua yang tampil">
                    <label class="form-check-label fw-semibold small text-muted" for="checkAllAnalytics">Pilih Semua Halaman Ini</label>
                </div>
                <span id="selected-count-badge-analytics" class="badge bg-primary-subtle text-primary border border-primary-subtle d-none px-2 py-1">
                    <span id="selected-count-analytics">0</span> dipilih
                </span>
            </div>

            <!-- Aksi Hapus -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" id="btn-delete-selected-analytics" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 d-none" onclick="confirmBulkDeleteAnalytics()">
                    <i class="bi bi-trash-fill"></i>
                    <span>Hapus yang Dipilih (<span class="selected-count-inline-analytics">0</span>)</span>
                </button>
                <?php
                $hasActiveFiltersAnalytics = false;
                $filterKeysAnalytics = ['store_id', 'month', 'year', 'rating', 'sentiment', 'reply_status', 'finding', 'words', 'photos', 'search'];
                foreach ($filterKeysAnalytics as $k) {
                    if (!empty($filters[$k]) && $filters[$k] !== 'all') {
                        $hasActiveFiltersAnalytics = true;
                        break;
                    }
                }
                ?>
                <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="confirmDeleteAllAnalytics()">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= $hasActiveFiltersAnalytics ? 'Hapus Data Terfilter' : 'Hapus Semua Data' ?> (<?= number_format($totalReviews) ?>)</span>
                </button>
            </div>
        </div>

    <!-- TABEL DATA ULASAN LENGKAP (Sinkron 100% dengan Filter & Audit Review) -->
    <div class="card-custom overflow-hidden mb-4" id="section-reviews-data">
        <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 bg-light bg-opacity-75">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-chat-left-text-fill text-primary"></i>
                        <span>Daftar Data Ulasan Google Maps</span>
                    </h5>
                    <span class="badge bg-primary text-white">
                        <?= number_format($totalReviews) ?> Ulasan Cocok
                    </span>
                    <?php if ($filters['month'] !== 'all'): ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                            <i class="bi bi-calendar3 me-1"></i>Periode: <?= formatBulanIndo($filters['month']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($selectedStore): ?>
                        <span class="badge bg-light text-secondary border">
                            <i class="bi bi-shop me-1 text-primary"></i><?= htmlspecialchars($selectedStore['store_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <small class="text-muted">
                    Menampilkan ulasan nyata untuk <strong><?= $selectedStore ? htmlspecialchars($selectedStore['store_name']) : 'Semua Cabang Toko (' . count($stores) . ' Cabang)' ?></strong>
                    <?= $filters['month'] !== 'all' ? 'pada periode <strong>' . formatBulanIndo($filters['month']) . '</strong>' : '(Semua Periode)' ?>.
                </small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Tombol Buka di Audit & Export XLS -->
                <a href="<?= url('review', 'audit', $queryFilters) ?>" class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1.5" title="Buka data ini lengkap di Audit Review">
                    <i class="bi bi-shield-check text-primary"></i>
                    <span>Buka di Audit Review</span>
                </a>
                <a href="<?= url('review', 'exportXls', $queryFilters) ?>" class="btn btn-sm btn-success-export d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    <span>Export XLS</span>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="checkAllInTableAnalytics" class="form-check-input" title="Pilih semua baris">
                        </th>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="min-width: 160px;">Cabang Toko</th>
                        <th style="min-width: 170px;">Reviewer</th>
                        <th style="min-width: 120px;">Rating & Sentimen</th>
                        <th style="min-width: 280px;">Isi Ulasan & Respon Owner</th>
                        <th style="width: 90px;" class="text-center">Kata</th>
                        <th style="width: 90px;" class="text-center">Foto</th>
                        <th style="min-width: 150px;">Status Audit</th>
                        <th style="width: 90px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted py-4">
                                    <i class="bi bi-chat-square-dots display-4 d-block mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold text-dark">Tidak ada ulasan yang cocok dengan kriteria filter</h6>
                                    <p class="small mb-3">Cobalah mengubah filter temuan audit, rating, bulan, kata, foto, atau reset filter.</p>
                                    <a href="<?= url('analytics', 'index', ['reset' => 1]) ?>" class="btn btn-sm btn-outline-primary">Reset Semua Filter</a>
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
                            $photoCount = (int)($rev['review_photo_count'] ?? 0);
                            $findings = $rev['audit_findings'] ?? [];
                            $isLong = mb_strlen($rawText) > 130 || $wordCount > 20;
                            $shortSnippet = $isLong ? mb_substr($rawText, 0, 120) . '...' : $rawText;
                        ?>
                            <tr>
                                <!-- Checkbox -->
                                <td class="text-center">
                                    <input type="checkbox" name="review_ids[]" value="<?= $rev['id'] ?>" class="form-check-input row-checkbox-analytics">
                                </td>

                                <!-- No -->
                                <td class="text-center text-muted fw-bold small"><?= $startNo++ ?></td>

                                <!-- Cabang Store -->
                                <td>
                                    <div class="fw-semibold text-dark small">
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
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 140px;">
                                                <?= htmlspecialchars($rev['author_name'] ?? 'Pengguna Google') ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.73rem;">
                                                <?= date('d M Y, H:i', strtotime($rev['review_time'] ?? 'now')) ?>
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
                                    <?php if (empty($rawText)): ?>
                                        <span class="text-muted fst-italic small">
                                            <i class="bi bi-dash-circle me-1"></i>Hanya rating bintang tanpa ulasan teks.
                                        </span>
                                    <?php elseif ($isLong): ?>
                                        <div class="review-text-wrapper small">
                                            <span class="short-text text-dark"><?= nl2br(htmlspecialchars($shortSnippet)) ?></span>
                                            <span class="full-text text-dark d-none"><?= nl2br(htmlspecialchars($rawText)) ?></span>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none fw-semibold" style="font-size: 0.75rem;" onclick="
                                                const wrap = this.closest('.review-text-wrapper');
                                                const isExp = wrap.querySelector('.short-text').classList.contains('d-none');
                                                if (isExp) {
                                                    wrap.querySelector('.short-text').classList.remove('d-none');
                                                    wrap.querySelector('.full-text').classList.add('d-none');
                                                    this.textContent = '... Lihat ulasan lengkap (more)';
                                                } else {
                                                    wrap.querySelector('.short-text').classList.add('d-none');
                                                    wrap.querySelector('.full-text').classList.remove('d-none');
                                                    this.textContent = 'Tutup ulasan';
                                                }
                                            ">... Lihat ulasan lengkap (more)</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-dark small lh-sm">
                                            <?= nl2br(htmlspecialchars($rawText)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Tanggapan Owner -->
                                    <?php if ($hasReply): ?>
                                        <div class="owner-reply-box mt-2 p-2 rounded-2 bg-light border-start border-3 border-primary small">
                                            <div class="d-flex align-items-center gap-1 text-primary fw-bold" style="font-size: 0.75rem;">
                                                <i class="bi bi-reply-fill"></i> Respon Owner:
                                                <?php if (!empty($rev['owner_reply_time'])): ?>
                                                    <span class="text-muted fw-normal" style="font-size: 0.7rem;">(<?= date('d M Y', strtotime($rev['owner_reply_time'])) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-secondary mt-1" style="font-size: 0.8rem;">
                                                <?= nl2br(htmlspecialchars($rev['owner_reply'])) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1">
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;">
                                                <i class="bi bi-clock-history me-1"></i>Belum Dibalas Owner
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Jumlah Kata -->
                                <td class="text-center" style="white-space: nowrap;">
                                    <?php if ($wordCount > 30): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><?= $wordCount ?> kata</span>
                                    <?php elseif ($wordCount >= 20): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold"><?= $wordCount ?> kata</span>
                                    <?php elseif ($wordCount >= 10): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= $wordCount ?> kata</span>
                                    <?php elseif ($wordCount > 0): ?>
                                        <span class="badge bg-light text-muted border"><?= $wordCount ?> kata</span>
                                    <?php else: ?>
                                        <span class="text-muted small">0 kata</span>
                                    <?php endif; ?>
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

                                <!-- Status Audit -->
                                <td>
                                    <?php if (empty($findings)): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                            <i class="bi bi-check-circle me-1"></i>Lolos Audit
                                        </span>
                                    <?php else: ?>
                                        <div class="d-flex flex-column gap-1">
                                            <?php foreach ($findings as $fd): ?>
                                                <?php
                                                $sev = $fd['severity'] ?? 'medium';
                                                $badgeClass = ($sev === 'high') ? 'bg-danger-subtle text-danger border-danger-subtle' : (($sev === 'medium') ? 'bg-warning-subtle text-dark border-warning-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle');
                                                ?>
                                                <span class="badge <?= $badgeClass ?> border text-start" style="font-size: 0.68rem;">
                                                    <i class="bi bi-shield-exclamation me-1"></i><?= htmlspecialchars($fd['label']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('review', 'audit', array_merge($queryFilters, ['highlight' => $rev['id']])) ?>#row-audit-<?= $rev['id'] ?>" class="btn btn-outline-primary" title="Buka di Audit Review">
                                            <i class="bi bi-shield-check"></i>
                                        </a>
                                        <a href="<?= url('review', 'delete', ['id' => $rev['id']]) ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini?');"
                                           title="Hapus Ulasan">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 bg-light bg-opacity-50">
                <div class="small text-muted">
                    Menampilkan ulasan ke-<strong><?= ($page - 1) * $limit + 1 ?></strong> hingga <strong><?= min($totalReviews, $page * $limit) ?></strong> dari total <strong><?= number_format($totalReviews) ?></strong> ulasan cocok
                </div>
                <nav aria-label="Navigasi Halaman Ulasan">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('analytics', 'index', array_merge($queryFilters, ['page' => $page - 1])) ?>">Sebelumnya</a>
                        </li>
                        <?php 
                        $pStart = max(1, $page - 2);
                        $pEnd = min($totalPages, $page + 2);
                        for ($p = $pStart; $p <= $pEnd; $p++): 
                        ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('analytics', 'index', array_merge($queryFilters, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('analytics', 'index', array_merge($queryFilters, ['page' => $page + 1])) ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    </form><!-- /form-bulk-delete-analytics -->

</div>

<!-- Modal Konfirmasi Hapus Data (Analytics) -->
<div class="modal fade" id="modalDeleteAllAnalytics" tabindex="-1" aria-labelledby="modalDeleteAllAnalyticsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalDeleteAllAnalyticsLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $hasActiveFiltersAnalytics ? 'Hapus Ulasan Sesuai Filter' : 'Hapus Semua Data Ulasan' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-flex gap-3 align-items-start mb-3">
                    <i class="bi bi-shield-x fs-3 text-danger flex-shrink-0"></i>
                    <div>
                        <strong>Tindakan Tidak Dapat Dibatalkan!</strong><br>
                        <?php if ($hasActiveFiltersAnalytics): ?>
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
                        <i class="bi bi-trash-fill me-1"></i> Ya, Hapus <?= $hasActiveFiltersAnalytics ? 'Data Terfilter' : 'Semua Data' ?> (<?= number_format($totalReviews) ?> Ulasan)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Chart ke JavaScript -->
<?php
// Persiapkan data chart
$trendLabels = [];
$trendCounts = [];
$trendRatings = [];

foreach ($monthlyTrend as $t) {
    $trendLabels[] = formatBulanIndo($t['review_month']);
    $trendCounts[] = (int)$t['count'];
    $trendRatings[] = (float)$t['avg_rating'];
}
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Tren Bulanan
    const ctxTrend = document.getElementById('monthlyTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: <?= json_encode($trendLabels) ?>,
                datasets: [
                    {
                        type: 'line',
                        label: 'Rating Rata-rata (Skala 1-5)',
                        data: <?= json_encode($trendRatings) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderWidth: 3,
                        pointRadius: 5,
                        yAxisID: 'yRating'
                    },
                    {
                        type: 'bar',
                        label: 'Jumlah Ulasan',
                        data: <?= json_encode($trendCounts) ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.75)',
                        borderRadius: 6,
                        yAxisID: 'yCount'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yCount: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah Ulasan' }
                    },
                    yRating: {
                        type: 'linear',
                        position: 'right',
                        min: 1,
                        max: 5,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Rating Rata-rata' }
                    }
                }
            }
        });
    }

    // 2. Chart Proporsi Sentimen
    const ctxSentiment = document.getElementById('sentimentChart');
    if (ctxSentiment) {
        new Chart(ctxSentiment, {
            type: 'doughnut',
            data: {
                labels: ['Positif', 'Netral', 'Negatif'],
                datasets: [{
                    data: [<?= (int)$stats['positive'] ?>, <?= (int)$stats['neutral'] ?>, <?= (int)$stats['negative'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 3. Chart Sebaran Bintang (1 - 5)
    const ctxRating = document.getElementById('ratingBarChart');
    if (ctxRating) {
        new Chart(ctxRating, {
            type: 'bar',
            data: {
                labels: ['5 Bintang ★', '4 Bintang ★', '3 Bintang ★', '2 Bintang ★', '1 Bintang ★'],
                datasets: [{
                    label: 'Jumlah Ulasan',
                    data: [
                        <?= (int)$stats['star_5'] ?>,
                        <?= (int)$stats['star_4'] ?>,
                        <?= (int)$stats['star_3'] ?>,
                        <?= (int)$stats['star_2'] ?>,
                        <?= (int)$stats['star_1'] ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#34d399',
                        '#fbbf24',
                        '#f97316',
                        '#ef4444'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 4. Checkbox & Bulk Actions Logic (Analytics)
    const checkAllAnalytics       = document.getElementById('checkAllAnalytics');
    const checkAllTableAnalytics  = document.getElementById('checkAllInTableAnalytics');
    const rowCheckboxesAnalytics  = () => document.querySelectorAll('.row-checkbox-analytics');
    const countBadgeAnalytics     = document.getElementById('selected-count-badge-analytics');
    const countSpanAnalytics      = document.getElementById('selected-count-analytics');
    const countInlinesAnalytics   = document.querySelectorAll('.selected-count-inline-analytics');
    const btnDelSelectedAnalytics = document.getElementById('btn-delete-selected-analytics');

    function updateBulkUIAnalytics() {
        const checked = document.querySelectorAll('.row-checkbox-analytics:checked').length;
        const total   = rowCheckboxesAnalytics().length;
        if (countSpanAnalytics) countSpanAnalytics.textContent = checked;
        countInlinesAnalytics.forEach(el => el.textContent = checked);

        if (checked > 0) {
            if (countBadgeAnalytics) countBadgeAnalytics.classList.remove('d-none');
            if (btnDelSelectedAnalytics) btnDelSelectedAnalytics.classList.remove('d-none');
        } else {
            if (countBadgeAnalytics) countBadgeAnalytics.classList.add('d-none');
            if (btnDelSelectedAnalytics) btnDelSelectedAnalytics.classList.add('d-none');
        }

        const allChecked = checked === total && total > 0;
        if (checkAllAnalytics) checkAllAnalytics.checked = allChecked;
        if (checkAllTableAnalytics) checkAllTableAnalytics.checked = allChecked;

        rowCheckboxesAnalytics().forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) tr.classList.toggle('table-warning', cb.checked);
        });
    }

    function toggleAllAnalytics(checked) {
        rowCheckboxesAnalytics().forEach(cb => { cb.checked = checked; });
        if (checkAllAnalytics) checkAllAnalytics.checked = checked;
        if (checkAllTableAnalytics) checkAllTableAnalytics.checked = checked;
        updateBulkUIAnalytics();
    }

    if (checkAllAnalytics) checkAllAnalytics.addEventListener('change', () => toggleAllAnalytics(checkAllAnalytics.checked));
    if (checkAllTableAnalytics) checkAllTableAnalytics.addEventListener('change', () => toggleAllAnalytics(checkAllTableAnalytics.checked));

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox-analytics')) updateBulkUIAnalytics();
    });
});

function confirmBulkDeleteAnalytics() {
    const checked = document.querySelectorAll('.row-checkbox-analytics:checked');
    const count = checked.length;
    if (count === 0) {
        alert('Pilih minimal 1 ulasan terlebih dahulu!');
        return;
    }
    if (confirm(`Anda akan menghapus ${count} ulasan yang ditandai.\n\nTindakan ini TIDAK DAPAT dibatalkan. Lanjutkan?`)) {
        document.getElementById('form-bulk-delete-analytics').submit();
    }
}

function confirmDeleteAllAnalytics() {
    const modalEl = document.getElementById('modalDeleteAllAnalytics');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>
