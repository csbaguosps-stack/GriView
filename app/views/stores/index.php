<?php 
/* License: by cs.baguosps@gmail.com */
require_once __DIR__ . '/../../models/PlaceConfig.php';
$appConfig = (new PlaceConfig())->getAll();
?>
<div class="container-fluid px-lg-4 py-4">

    <!-- Flash Alert -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-patch-check-fill me-1"></i> Google Profil Bisnis Terverifikasi
                    </span>
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-buildings me-1 text-primary"></i> Grup: <strong><?= htmlspecialchars($appConfig['business_group'] ?? 'Semua Cabang') ?></strong>
                    </span>
                    <span class="badge bg-primary text-white">
                        <?= count($stores) ?> Lokasi Cabang Terdaftar
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">Data Cabang & Lokasi Bisnis</h3>
                <p class="text-muted small mb-0">
                    Kelola data cabang dan lokasi bisnis Anda, pantau reputasi ulasan per lokasi, dan sinkronkan ulasan Google Maps secara otomatis.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php if (!empty($stores)): ?>
                    <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-1.5 fw-semibold shadow-xs" id="btnDeleteAllStores" title="Hapus semua cabang store">
                        <i class="bi bi-trash3"></i>
                        <span>Hapus Semua</span>
                    </button>
                    <a href="<?= url('review', 'exportXls', ['store_id' => 'all']) ?>" class="btn btn-success-export d-flex align-items-center gap-2 fw-semibold">
                        <i class="bi bi-file-earmark-excel-fill fs-5"></i>
                        <span>Export XLS (<?= count($stores) ?>)</span>
                    </a>
                <?php else: ?>
                    <a href="<?= url('store', 'seedSample') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1.5 fw-semibold" title="Muat cabang contoh untuk simulasi">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Muat Cabang Contoh</span>
                    </a>
                <?php endif; ?>
                <a href="<?= url('review', 'audit') ?>" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-shield-check"></i>
                    <span>Audit Review Ulasan</span>
                </a>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddStore">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Cabang</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Batch Action Toolbar (Muncul jika ada cabang yang dicentang) -->
    <div id="batchActionBar" class="card-custom p-3 mb-3 bg-primary-subtle border border-primary-subtle d-flex flex-wrap justify-content-between align-items-center gap-2 shadow-xs" style="display: none !important;">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white px-2.5 py-1.5 fs-6" id="selectedBadgeCount">0</span>
            <span class="fw-bold text-dark">Cabang Store Ditandai</span>
            <span class="text-secondary small d-none d-sm-inline">• Pilih tindakan serentak untuk cabang yang dicentang</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-secondary bg-white fw-semibold" id="btnUncheckAll">
                <i class="bi bi-x-circle me-1"></i> Batal Pilihan
            </button>
            <button type="button" class="btn btn-sm btn-danger fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnDeleteSelected">
                <i class="bi bi-trash-fill"></i>
                <span>Hapus Cabang Ditandai</span>
            </button>
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
                                    Di dashboard Anda, cari cabang yang ingin ditarik ulasannya, lalu klik tombol <strong>"Lihat profil"</strong> berlogo G di sebelah kanan.
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
        <?php if (empty($stores)): ?>
            <div class="p-5 text-center">
                <div class="mb-3">
                    <div class="p-3 bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-secondary shadow-xs" style="width: 72px; height: 72px;">
                        <i class="bi bi-shop fs-1 text-primary"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Belum Ada Data Cabang Store Terdaftar</h5>
                <p class="text-muted small mb-4" style="max-width: 520px; margin: 0 auto;">
                    Semua cabang store saat ini kosong. Anda dapat menambahkan cabang bisnis Anda sendiri, menarik ulasan otomatis dari link Google Maps, atau memuat cabang contoh untuk simulasi.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary fw-semibold d-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddStore">
                        <i class="bi bi-plus-lg"></i>
                        <span>Tambah Cabang Baru</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary fw-semibold d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalSyncGoogle">
                        <i class="bi bi-google"></i>
                        <span>Tarik dari Google Maps</span>
                    </button>
                    <a href="<?= url('store', 'seedSample') ?>" class="btn btn-outline-secondary fw-semibold d-flex align-items-center gap-1.5">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Muat Cabang Contoh</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 44px;" class="text-center">
                                <input type="checkbox" id="checkAllStores" class="form-check-input" title="Tandai Semua Cabang">
                            </th>
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
                        <tr id="row-store-<?= $s['id'] ?>">
                            <!-- Checkbox Tandai -->
                            <td class="text-center">
                                <input type="checkbox" name="selected_stores[]" value="<?= $s['id'] ?>" class="form-check-input check-store-item" title="Tandai cabang <?= htmlspecialchars($s['store_name']) ?>">
                            </td>

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
                                    <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($s['city'] ?: '-') ?>
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
                                            <button type="button" class="dropdown-item text-danger btn-delete-single-store"
                                                    data-id="<?= $s['id'] ?>"
                                                    data-name="<?= htmlspecialchars($s['store_name']) ?>">
                                                <i class="bi bi-trash me-2"></i>Hapus Cabang
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
                        <label class="form-label fw-semibold">Kode Toko (Google Business / Internal)</label>
                        <input type="text" name="store_code" id="formStoreCode" class="form-control" placeholder="Contoh: STR-01, 10489718802746643196">
                        <div class="form-text">Kode unik toko seperti pada Google Profil Bisnis atau sistem internal Anda.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Bisnis / Cabang <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" id="formStoreName" class="form-control" placeholder="Contoh: Cabang Utama - Jakarta Pusat" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Kota / Wilayah</label>
                            <input type="text" name="city" id="formStoreCity" class="form-control" placeholder="Jakarta, Bandung, Surabaya, dll">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Link Google Maps (Opsional)</label>
                            <input type="text" name="gmaps_url" id="formStoreUrl" class="form-control" placeholder="https://maps.app.goo.gl/...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Lengkap</label>
                        <textarea name="address" id="formStoreAddress" rows="2" class="form-control" placeholder="Jl. Sudirman No. 123, Kelurahan..."></textarea>
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
    // 1. Edit Store Modal Populator
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

    // 2. Tandai Semua (Check All) & Batch Toolbar
    const checkAll = document.getElementById('checkAllStores');
    const itemChecks = document.querySelectorAll('.check-store-item');
    const batchBar = document.getElementById('batchActionBar');
    const badgeCount = document.getElementById('selectedBadgeCount');
    const btnUncheck = document.getElementById('btnUncheckAll');
    const btnDeleteSel = document.getElementById('btnDeleteSelected');
    const btnDeleteAll = document.getElementById('btnDeleteAllStores');

    function updateBatchBar() {
        const checked = document.querySelectorAll('.check-store-item:checked');
        const count = checked.length;
        if (badgeCount) badgeCount.textContent = count;
        if (batchBar) {
            batchBar.style.setProperty('display', count > 0 ? 'flex' : 'none', 'important');
        }
        if (checkAll && itemChecks.length > 0) {
            checkAll.checked = (count > 0 && count === itemChecks.length);
            checkAll.indeterminate = (count > 0 && count < itemChecks.length);
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            itemChecks.forEach(ch => { ch.checked = checkAll.checked; });
            updateBatchBar();
        });
    }

    itemChecks.forEach(ch => {
        ch.addEventListener('change', updateBatchBar);
    });

    if (btnUncheck) {
        btnUncheck.addEventListener('click', function () {
            itemChecks.forEach(ch => { ch.checked = false; });
            if (checkAll) checkAll.checked = false;
            updateBatchBar();
        });
    }

    // 3. Hapus Cabang Ditandai (Batch Delete)
    if (btnDeleteSel) {
        btnDeleteSel.addEventListener('click', function () {
            const checked = Array.from(document.querySelectorAll('.check-store-item:checked')).map(c => c.value);
            if (!checked.length) return;

            Swal.fire({
                title: 'Hapus Cabang Ditandai?',
                html: `Apakah Anda yakin ingin menghapus <strong>${checked.length} cabang store</strong> yang ditandai dari sistem?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="bi bi-trash-fill me-1"></i> Ya, Hapus ${checked.length} Cabang`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '<?= url("store", "deleteBatch") ?>';
                    checked.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'store_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }

    // 4. Hapus SEMUA Cabang Store Sekaligus
    if (btnDeleteAll) {
        btnDeleteAll.addEventListener('click', function () {
            Swal.fire({
                title: 'Hapus SEMUA Cabang Store?',
                text: 'Tindakan ini akan mengosongkan seluruh daftar cabang store. Anda dapat menambahkan cabang bisnis Anda sendiri kapan saja.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Ya, Kosongkan Semua Store',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= url("store", "deleteAll") ?>';
                }
            });
        });
    }

    // 5. Hapus Cabang Tunggal (Single Delete with SweetAlert2)
    const singleDeleteBtns = document.querySelectorAll('.btn-delete-single-store');
    singleDeleteBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const storeId = this.dataset.id;
            const storeName = this.dataset.name;
            Swal.fire({
                title: 'Hapus Cabang Ini?',
                html: `Apakah Anda yakin ingin menghapus cabang <strong>${storeName}</strong> dari daftar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= url("store", "delete") ?>&id=' + storeId;
                }
            });
        });
    });
});
</script>
