<?php /* License: by cs.baguosps@gmail.com */ ?>
<div class="container-fluid px-lg-4 py-4">

    <!-- Flash Alert -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-patch-check-fill me-1"></i> Google Profil Bisnis Terverifikasi
                    </span>
                    <span class="badge bg-light text-secondary border">
                        Grup: <strong>winseeoptik</strong>
                    </span>
                    <span class="badge bg-primary text-white">
                        <?= count($stores) ?> Store Terdaftar
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">Data Cabang & Store Winsee Optik</h3>
                <p class="text-muted small mb-0">
                    Kelola data <?= count($stores) ?> cabang optik, pantau ulasan per lokasi, dan sinkronkan reputasi Google Maps setiap cabang.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('review', 'exportXls', ['store_id' => 'all']) ?>" class="btn btn-success-export d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                    <span>Export XLS <?= count($stores) ?> Cabang</span>
                </a>
                <a href="<?= url('review', 'audit') ?>" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-shield-check"></i>
                    <span>Audit Review Ulasan</span>
                </a>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddStore">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Cabang</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Petunjuk Cara Mendata dari Google Pengelola Profil Bisnis -->
    <div class="card-custom p-3 mb-4 bg-light border">
        <div class="d-flex align-items-start gap-3">
            <div class="p-2 bg-primary text-white rounded-3 shadow-sm">
                <i class="bi bi-lightbulb-fill fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-dark">Panduan Singkat: Cara Mendata Ulasan dari Google Pengelola Profil Bisnis</h6>
                <div class="small text-secondary">
                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <div class="p-2 bg-white rounded border h-100">
                                <span class="badge bg-primary text-white mb-1">Langkah 1</span>
                                <div class="fw-semibold text-dark">Buka Google Profil Bisnis</div>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                                    Di dashboard Anda (seperti screenshot), cari cabang yang ingin ditarik ulasannya, lalu klik tombol <strong>"Lihat profil"</strong> berlogo G di sebelah kanan.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 bg-white rounded border h-100">
                                <span class="badge bg-primary text-white mb-1">Langkah 2</span>
                                <div class="fw-semibold text-dark">Salin Link Google Maps</div>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                                    Setelah profil toko terbuka di tab baru, klik <strong>Bagikan / Share</strong> atau salin URL dari bilah alamat browser.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 bg-white rounded border h-100">
                                <span class="badge bg-primary text-white mb-1">Langkah 3</span>
                                <div class="fw-semibold text-dark">Klik Tombol "Tarik" di Sini</div>
                                <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                                    Klik tombol <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-arrow-repeat text-primary"></i> Tarik</span> pada baris cabang di bawah, tempel link, dan jalankan Auto-Audit Ekstensi!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Stores -->
    <div class="card-custom overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 140px;">Kode Toko</th>
                        <th style="width: 260px;">Nama Bisnis / Cabang</th>
                        <th>Alamat Lokasi & Kota</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 140px;" class="text-center">Ulasan & Rating</th>
                        <th style="width: 220px;" class="text-end">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($stores as $s): 
                        $totalRev = $s['total_reviews'] ?? 0;
                        $avgRating = $s['avg_rating'] ?? 5.0;
                    ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                        
                        <!-- Kode Toko -->
                        <td>
                            <code class="text-dark bg-light px-2 py-1 rounded border small fw-bold">
                                <?= htmlspecialchars($s['store_code'] ?: '-') ?>
                            </code>
                        </td>

                        <!-- Nama Store -->
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($s['store_name']) ?></div>
                            <span class="badge bg-light text-secondary border small mt-1">
                                <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($s['city'] ?: 'Bandung') ?>
                            </span>
                        </td>

                        <!-- Alamat -->
                        <td>
                            <small class="text-muted d-block" style="max-width: 380px; line-height: 1.4;">
                                <?= htmlspecialchars($s['address'] ?: 'Alamat belum diatur') ?>
                            </small>
                            <?php if (!empty($s['gmaps_url'])): ?>
                                <a href="<?= htmlspecialchars($s['gmaps_url']) ?>" target="_blank" rel="noopener" class="small text-primary text-decoration-none mt-1 d-inline-block">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Lihat di Google Maps
                                </a>
                            <?php endif; ?>
                        </td>

                        <!-- Status Google Profil Bisnis -->
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                <i class="bi bi-check-circle-fill me-1"></i>Terverifikasi
                            </span>
                        </td>

                        <!-- Total Review & Rating -->
                        <td class="text-center">
                            <div class="fw-bold text-dark"><?= number_format($totalRev) ?> Ulasan</div>
                            <div class="small text-warning fw-bold">
                                ⭐ <?= number_format($avgRating, 1) ?> / 5.0
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <!-- Tombol Tarik Review Cabang Ini -->
                                <button type="button" class="btn btn-outline-warning text-dark btn-sync-store" 
                                        data-store-id="<?= $s['id'] ?>"
                                        data-store-name="<?= htmlspecialchars($s['store_name']) ?>"
                                        data-store-url="<?= htmlspecialchars($s['gmaps_url'] ?? '') ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalSyncGoogle"
                                        title="Tarik Ulasan Google Maps Cabang Ini">
                                    <i class="bi bi-arrow-repeat text-primary"></i> Tarik
                                </button>

                                <!-- Tombol Lihat Ulasan Store ini -->
                                <a href="<?= url('review', 'audit', ['store_id' => $s['id']]) ?>" class="btn btn-outline-primary" title="Lihat Audit Ulasan Cabang Ini">
                                    <i class="bi bi-shield-check"></i> Audit
                                </a>

                                <!-- Tombol Export XLS Cabang ini -->
                                <a href="<?= url('review', 'exportXls', ['store_id' => $s['id']]) ?>" class="btn btn-outline-success" title="Download XLS Cabang Ini">
                                    <i class="bi bi-file-earmark-excel"></i> XLS
                                </a>

                                <!-- Dropdown More -->
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                                    <li>
                                        <button class="dropdown-item btn-edit-store" 
                                                data-id="<?= $s['id'] ?>"
                                                data-code="<?= htmlspecialchars($s['store_code']) ?>"
                                                data-name="<?= htmlspecialchars($s['store_name']) ?>"
                                                data-address="<?= htmlspecialchars($s['address']) ?>"
                                                data-city="<?= htmlspecialchars($s['city']) ?>"
                                                data-url="<?= htmlspecialchars($s['gmaps_url']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalAddStore">
                                            <i class="bi bi-pencil me-2 text-primary"></i>Edit Cabang
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?= url('store', 'delete', ['id' => $s['id']]) ?>" onclick="return confirm('Hapus cabang ini dari daftar?');">
                                            <i class="bi bi-trash me-2"></i>Hapus Cabang
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah / Edit Store -->
<div class="modal fade" id="modalAddStore" tabindex="-1" aria-labelledby="modalAddStoreLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalAddStoreLabel">
                    <i class="bi bi-shop text-primary me-2"></i>Kelola Data Cabang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('store', 'save') ?>" method="POST">
                <input type="hidden" name="store_id" id="formStoreId" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Toko (Google Business)</label>
                        <input type="text" name="store_code" id="formStoreCode" class="form-control" placeholder="Contoh: 111, 10489718802746643196">
                        <div class="form-text">Kode unik toko seperti yang tertera pada Google Profil Bisnis.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Bisnis / Cabang <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" id="formStoreName" class="form-control" placeholder="Contoh: Optik Winsee - Braga Bandung" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Kota / Wilayah</label>
                            <input type="text" name="city" id="formStoreCity" class="form-control" placeholder="Bandung, Jakarta, dll">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Link Google Maps (Opsional)</label>
                            <input type="text" name="gmaps_url" id="formStoreUrl" class="form-control" placeholder="https://maps.app.goo.gl/...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Lengkap</label>
                        <textarea name="address" id="formStoreAddress" rows="2" class="form-control" placeholder="Jl. Braga No.32, Braga, Kec. Sumur Bandung..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Simpan Data Cabang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editBtns = document.querySelectorAll('.btn-edit-store');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('formStoreId').value = this.dataset.id;
            document.getElementById('formStoreCode').value = this.dataset.code;
            document.getElementById('formStoreName').value = this.dataset.name;
            document.getElementById('formStoreCity').value = this.dataset.city;
            document.getElementById('formStoreUrl').value = this.dataset.url;
            document.getElementById('formStoreAddress').value = this.dataset.address;
            document.getElementById('modalAddStoreLabel').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Cabang';
        });
    });

    // Auto-fill modal sync sudah ditangani oleh handler global di footer.php (show.bs.modal event)

    const addModal = document.getElementById('modalAddStore');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('formStoreId').value = '0';
            document.getElementById('formStoreCode').value = '';
            document.getElementById('formStoreName').value = '';
            document.getElementById('formStoreCity').value = '';
            document.getElementById('formStoreUrl').value = '';
            document.getElementById('formStoreAddress').value = '';
            document.getElementById('modalAddStoreLabel').innerHTML = '<i class="bi bi-shop text-primary me-2"></i>Tambah Data Cabang Baru';
        });
    }
});
</script>
