<?php
/* License: by cs.baguosps@gmail.com */
/**
 * JsonDatabase - Helper untuk membaca & menulis data dari/ke file JSON
 * Menggantikan PDO/SQLite sepenuhnya.
 * 
 * File JSON disimpan di: app/data/
 *   - reviews.json   : array ulasan
 *   - stores.json    : array toko/cabang
 *   - settings.json  : key-value pengaturan
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SampleData.php';

class JsonDatabase {

    // ─── Path file JSON ─────────────────────────────────────────────────────

    public static function reviewsPath(): string {
        return __DIR__ . '/../data/reviews.json';
    }

    public static function storesPath(): string {
        return __DIR__ . '/../data/stores.json';
    }

    public static function settingsPath(): string {
        return __DIR__ . '/../data/settings.json';
    }

    // ─── Low-level read/write ────────────────────────────────────────────────

    public static function readJson(string $path): array {
        if (!file_exists($path)) {
            return [];
        }
        $content = file_get_contents($path);
        if (!$content) return [];
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public static function writeJson(string $path, array $data): bool {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        // Tulis ke file temp dulu, lalu atomic rename
        $tmp = $path . '.tmp';
        $ok = file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($ok === false) return false;
        return rename($tmp, $path);
    }

    // ─── Inisialisasi data awal ──────────────────────────────────────────────

    public static function init(): void {
        require_once __DIR__ . '/SampleData.php';

        // Init stores jika file belum ada sama sekali
        if (!file_exists(self::storesPath())) {
            self::writeJson(self::storesPath(), []);
        }

        // Init reviews jika berkas belum ada
        if (!file_exists(self::reviewsPath())) {
            self::writeJson(self::reviewsPath(), []);
        }

        // Init settings jika belum ada
        if (!file_exists(self::settingsPath())) {
            self::seedSettings();
        }
    }

    // ─── Seed Data ───────────────────────────────────────────────────────────

    public static function seedStores(): void {
        $stores = [
            ['id' => 1,  'store_code' => '111',                    'store_name' => 'Dr.Optik',                                  'address' => 'Jl. Pasar Baru No.111, Kota Jakarta Pusat, DKI Jakarta 10710',                                                          'city' => 'Jakarta Pusat', 'gmaps_url' => 'https://maps.app.goo.gl/dYPFGiCsNYN8AHiD7',                'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2,  'store_code' => '10489718802746643196',   'store_name' => 'Optik Winsee - Braga Bandung',              'address' => 'Jl. Braga No.32, Braga, Kec. Sumur Bandung, Kota Bandung, Jawa Barat 40111',                                              'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Braga+Bandung',       'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3,  'store_code' => '08106395695221243650',   'store_name' => 'Optik Winsee - Dewi Sartika Bandung',       'address' => 'Jl. Dewi Sartika Nomor 16, Kelurahan Balonggede, Kecamatan Regol, Kota Bandung, Jawa Barat 40251',                          'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Dewi+Sartika+Bandung', 'place_id' => '',          'created_at' => date('Y-m-d H:i:s')],
            ['id' => 4,  'store_code' => '1586517686641',          'store_name' => 'Optik Winsee - Festival Citylink Bandung',  'address' => 'Mall Festival Citylink Lt. LG, Jl. Peta No. 241, Suka Asih, Kec. Bojongloa Kaler, Kota Bandung 40232',                      'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Festival+Citylink+Bandung', 'place_id' => '',     'created_at' => date('Y-m-d H:i:s')],
            ['id' => 5,  'store_code' => 'WNS-05',                 'store_name' => 'Optik Winsee - Cihampelas Walk (Ciwalk)',   'address' => 'Cihampelas Walk, Jl. Cihampelas No.160, Cipaganti, Coblong, Kota Bandung 40131',                                            'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Ciwalk+Bandung',      'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 6,  'store_code' => 'WNS-06',                 'store_name' => 'Optik Winsee - Dago Bandung',               'address' => 'Jl. Ir. H. Juanda No. 88, Dago, Coblong, Kota Bandung, Jawa Barat 40132',                                                  'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Dago+Bandung',        'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 7,  'store_code' => 'WNS-07',                 'store_name' => 'Optik Winsee - Buah Batu Bandung',          'address' => 'Jl. Buah Batu No. 142, Turangga, Lengkong, Kota Bandung, Jawa Barat 40265',                                                 'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Buah+Batu+Bandung',   'place_id' => '',          'created_at' => date('Y-m-d H:i:s')],
            ['id' => 8,  'store_code' => 'WNS-08',                 'store_name' => 'Optik Winsee - Cimahi',                     'address' => 'Jl. Jend. H. Amir Machmud No. 230, Cigugur Tengah, Cimahi Tengah, Kota Cimahi 40522',                                        'city' => 'Cimahi',         'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Cimahi',              'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 9,  'store_code' => 'WNS-09',                 'store_name' => 'Optik Winsee - Kopo Bandung',               'address' => 'Jl. Raya Kopo No. 340, Babakan Asih, Bojongloa Kaler, Kota Bandung, Jawa Barat 40232',                                       'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Kopo+Bandung',        'place_id' => '',           'created_at' => date('Y-m-d H:i:s')],
            ['id' => 10, 'store_code' => 'WNS-10',                 'store_name' => 'Optik Winsee - Antapani Bandung',           'address' => 'Jl. Terusan Jakarta No. 78, Antapani Kulon, Antapani, Kota Bandung, Jawa Barat 40291',                                        'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Antapani+Bandung',    'place_id' => '',          'created_at' => date('Y-m-d H:i:s')],
            ['id' => 11, 'store_code' => 'WNS-11',                 'store_name' => 'Optik Winsee - Ubertos (Ujungberung)',      'address' => 'Ujungberung Town Square Lt. GF, Jl. A.H. Nasution No.46A, Pakemitan, Cinambo, Kota Bandung 40293',                            'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Ubertos+Bandung',     'place_id' => '',          'created_at' => date('Y-m-d H:i:s')],
            ['id' => 12, 'store_code' => 'WNS-12',                 'store_name' => 'Optik Winsee - Summarecon Mall Bandung',    'address' => 'Summarecon Mall Bandung Lt. GF No. 12, Cisaranten Kidul, Gedebage, Kota Bandung 40294',                                       'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+Summarecon+Mall+Bandung', 'place_id' => '',       'created_at' => date('Y-m-d H:i:s')],
            ['id' => 13, 'store_code' => 'WNS-13',                 'store_name' => 'Optik Winsee - 23 Paskal Bandung',          'address' => '23 Paskal Shopping Center Lt. 1, Jl. Pasir Kaliki No. 25-27, Kebon Jeruk, Andir, Kota Bandung 40181',                         'city' => 'Bandung',        'gmaps_url' => 'https://maps.google.com/?q=Optik+Winsee+23+Paskal+Bandung',   'place_id' => '',          'created_at' => date('Y-m-d H:i:s')],
        ];
        self::writeJson(self::storesPath(), $stores);
    }

    public static function seedReviews(): void {
        require_once __DIR__ . '/../models/Review.php';
        $samples = SampleData::getReviews();
        $stores = self::readJson(self::storesPath());
        $storeCount = count($stores);

        $reviews = [];
        $id = 1;
        foreach ($samples as $idx => $r) {
            $month = date('Y-m', strtotime($r['review_time']));
            $assignedStore = ($storeCount > 0) ? $stores[$idx % $storeCount] : null;
            $text = $r['review_text'] ?? '';

            $reviews[] = [
                'id'                => $id++,
                'google_review_id'  => $r['google_review_id'],
                'store_id'          => $assignedStore['id'] ?? null,
                'store_code'        => $assignedStore['store_code'] ?? null,
                'place_id'          => DEFAULT_PLACE_ID,
                'place_name'        => $assignedStore['store_name'] ?? DEFAULT_PLACE_NAME,
                'author_name'       => $r['author_name'],
                'author_photo_url'  => $r['author_photo_url'] ?? null,
                'author_url'        => $r['author_url'] ?? null,
                'rating'            => (int)$r['rating'],
                'review_text'       => $text,
                'word_count'        => Review::countWords($text),
                'review_time'       => $r['review_time'],
                'review_month'      => $month,
                'sentiment'         => $r['sentiment'],
                'is_local_guide'    => (int)($r['is_local_guide'] ?? 0),
                'review_language'   => $r['review_language'] ?? 'id',
                'owner_reply'       => $r['owner_reply'] ?? null,
                'owner_reply_time'  => $r['owner_reply_time'] ?? null,
                'created_at'        => date('Y-m-d H:i:s'),
            ];
        }
        self::writeJson(self::reviewsPath(), $reviews);
    }

    public static function seedSettings(): void {
        $settings = [
            'place_name'     => DEFAULT_PLACE_NAME,
            'place_address'  => DEFAULT_PLACE_ADDRESS,
            'place_id'       => DEFAULT_PLACE_ID,
            'google_api_key' => GOOGLE_MAPS_API_KEY,
            'business_group' => 'Semua Cabang',
        ];
        self::writeJson(self::settingsPath(), $settings);
    }

    // ─── Auto-increment ID helper ────────────────────────────────────────────

    public static function nextId(array $records): int {
        if (empty($records)) return 1;
        return max(array_column($records, 'id')) + 1;
    }
}
