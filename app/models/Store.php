<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Model Store - Menangani data cabang & lokasi bisnis via JSON
 */
require_once __DIR__ . '/../helpers/JsonDatabase.php';

class Store {

    public function __construct() {
        JsonDatabase::init();
    }

    private function loadAll(): array {
        return JsonDatabase::readJson(JsonDatabase::storesPath());
    }

    private function saveAll(array $stores): bool {
        return JsonDatabase::writeJson(JsonDatabase::storesPath(), array_values($stores));
    }

    public function getAll(): array {
        $stores = $this->loadAll();
        usort($stores, fn($a, $b) => (int)($a['id'] ?? 0) - (int)($b['id'] ?? 0));
        return $stores;
    }

    public function getById(int $id): ?array {
        foreach ($this->loadAll() as $s) {
            if ((int)($s['id'] ?? 0) === $id) return $s;
        }
        return null;
    }

    public function getByCode(string $code): ?array {
        foreach ($this->loadAll() as $s) {
            if (($s['store_code'] ?? '') === $code) return $s;
        }
        return null;
    }

    /**
     * Mengambil daftar store lengkap dengan statistik ulasan dan rating
     */
    public function getStoresWithStats(?string $month = null): array {
        require_once __DIR__ . '/Review.php';
        $stores = $this->getAll();
        $reviewModel = new Review();

        foreach ($stores as &$store) {
            $sid = $store['id'];
            $filters = ['store_id' => $sid];
            if (!empty($month) && $month !== 'all') {
                $filters['month'] = $month;
            }

            // Ambil semua ulasan untuk store ini
            $allReviews = $reviewModel->getAll($filters, 1, 0);
            $total = count($allReviews);

            $ratings = array_column($allReviews, 'rating');
            $avgRating = $total > 0 ? round(array_sum($ratings) / $total, 2) : null;

            $positiveCount = count(array_filter($allReviews, fn($r) => (int)($r['rating'] ?? 0) >= 4));
            $negativeCount = count(array_filter($allReviews, fn($r) => (int)($r['rating'] ?? 0) <= 2));
            $repliedCount  = count(array_filter($allReviews, fn($r) => !empty($r['owner_reply'])));

            $store['total_reviews']   = $total;
            $store['avg_rating']      = $avgRating;
            $store['positive_count']  = $positiveCount;
            $store['negative_count']  = $negativeCount;
            $store['replied_count']   = $repliedCount;
        }

        return $stores;
    }

    public function create(array $data): int {
        $stores = $this->loadAll();
        $newId = JsonDatabase::nextId($stores);
        $new = [
            'id'         => $newId,
            'store_code' => $data['store_code'] ?? uniqid('STR_'),
            'store_name' => $data['store_name'],
            'address'    => $data['address'] ?? '',
            'city'       => $data['city'] ?? '',
            'gmaps_url'  => $data['gmaps_url'] ?? '',
            'place_id'   => $data['place_id'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $stores[] = $new;
        $this->saveAll($stores);
        return $newId;
    }

    public function update(int $id, array $data): bool {
        $stores = $this->loadAll();
        foreach ($stores as &$s) {
            if ((int)($s['id'] ?? 0) === $id) {
                $s['store_code'] = $data['store_code'] ?? $s['store_code'];
                $s['store_name'] = $data['store_name'] ?? $s['store_name'];
                $s['address']    = $data['address']    ?? $s['address'];
                $s['city']       = $data['city']        ?? $s['city'];
                $s['gmaps_url']  = $data['gmaps_url']  ?? $s['gmaps_url'];
                $s['place_id']   = $data['place_id']   ?? $s['place_id'];
                return $this->saveAll($stores);
            }
        }
        return false;
    }

    public function delete(int $id): bool {
        $stores = $this->loadAll();
        $new = array_filter($stores, fn($s) => (int)($s['id'] ?? 0) !== $id);
        return $this->saveAll(array_values($new));
    }

    /**
     * Hapus beberapa cabang sekaligus berdasarkan array ID
     */
    public function deleteBatch(array $ids): int {
        $ids = array_map('intval', $ids);
        if (empty($ids)) return 0;

        $stores = $this->loadAll();
        $initialCount = count($stores);
        $new = array_filter($stores, fn($s) => !in_array((int)($s['id'] ?? 0), $ids, true));
        $this->saveAll(array_values($new));
        return $initialCount - count($new);
    }

    /**
     * Hapus semua cabang store dari database
     */
    public function deleteAll(): bool {
        return $this->saveAll([]);
    }

    /**
     * Muat ulang data cabang contoh (opsional oleh user)
     */
    public function seedSamples(): int {
        JsonDatabase::seedStores();
        return count($this->getAll());
    }
}
