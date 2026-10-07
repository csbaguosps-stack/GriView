<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Model PlaceConfig / AppSettings - Pengaturan Branding, Logo/Thumbnail, dan Fungsi Web & Ekstensi
 */
require_once __DIR__ . '/../helpers/JsonDatabase.php';

class PlaceConfig {

    public function __construct() {
        JsonDatabase::init();
    }

    private function loadSettings(): array {
        return JsonDatabase::readJson(JsonDatabase::settingsPath());
    }

    private function saveSettings(array $settings): bool {
        return JsonDatabase::writeJson(JsonDatabase::settingsPath(), $settings);
    }

    public function getAll(): array {
        $s = $this->loadSettings();

        // Cek keberadaan file upload fisik
        $logoPath      = file_exists(__DIR__ . '/../../assets/uploads/logo.png')      ? 'assets/uploads/logo.png' : ($s['app_logo'] ?? '');
        $faviconPath   = file_exists(__DIR__ . '/../../assets/uploads/favicon.png')   ? 'assets/uploads/favicon.png' : ($s['app_favicon'] ?? '');
        $thumbnailPath = file_exists(__DIR__ . '/../../assets/uploads/thumbnail.png') ? 'assets/uploads/thumbnail.png' : ($s['app_thumbnail'] ?? '');
        $extIconPath   = file_exists(__DIR__ . '/../../chrome-extension/icons/icon-128.png') ? 'chrome-extension/icons/icon-128.png' : ($s['ext_icon'] ?? '');

        return [
            // ─── Branding Web ───
            'app_name'             => $s['app_name']             ?? 'GriView',
            'app_tagline'          => $s['app_tagline']          ?? 'Review audit & reputation workflow',
            'app_logo'             => $logoPath,
            'app_favicon'          => $faviconPath,
            'app_thumbnail'        => $thumbnailPath,
            'app_footer'           => $s['app_footer']           ?? 'GriView - Google Business Review Audit & Analytics',
            'business_group'       => $s['business_group']       ?? 'Semua Cabang',

            // ─── Branding & Ikon Ekstensi Chrome ───
            'ext_name'             => $s['ext_name']             ?? 'GriView Review Audit',
            'ext_version'          => $s['ext_version']          ?? '1.0.1',
            'ext_description'      => $s['ext_description']      ?? 'Audit hingga 1.000 ulasan Google Maps dengan scroll otomatis.',
            'ext_icon'             => $extIconPath,

            // ─── Fungsi Web (Web Functions) ───
            'default_scrape_limit' => (string)($s['default_scrape_limit'] ?? '1000'),
            'default_photo_filter' => (string)($s['default_photo_filter'] ?? 'all'),
            'short_text_threshold' => (int)($s['short_text_threshold'] ?? 40),
            'default_export_format'=> (string)($s['default_export_format'] ?? 'xls'),
            'custom_base_url'      => (string)($s['custom_base_url'] ?? ''),

            // ─── Fungsi Ekstensi (Extension Functions) ───
            'ext_scroll_speed'     => (string)($s['ext_scroll_speed'] ?? 'normal'), // 'fast' | 'normal' | 'relaxed'
            'ext_auto_save'        => (string)($s['ext_auto_save'] ?? '1'),
            'ext_auto_open_result' => (string)($s['ext_auto_open_result'] ?? '1'),
            'ext_detect_photos'    => (string)($s['ext_detect_photos'] ?? '1'),
            'ext_auto_expand_more' => (string)($s['ext_auto_expand_more'] ?? '1'),

            // ─── Legacy / Compatibility ───
            'place_name'           => $s['place_name']     ?? DEFAULT_PLACE_NAME,
            'place_address'        => $s['place_address']  ?? DEFAULT_PLACE_ADDRESS,
            'place_id'             => $s['place_id']       ?? DEFAULT_PLACE_ID,
            'google_api_key'       => $s['google_api_key'] ?? GOOGLE_MAPS_API_KEY,
        ];
    }

    public function get(string $key, $default = null) {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    public function set(string $key, $value): bool {
        $s = $this->loadSettings();
        $s[$key] = $value;
        return $this->saveSettings($s);
    }

    public function updateAll(array $data): bool {
        $s = $this->loadSettings();
        foreach ($data as $k => $v) {
            $s[$k] = $v;
        }
        return $this->saveSettings($s);
    }

    public function resetDefaults(): bool {
        $defaults = [
            'app_name'             => 'GriView',
            'app_tagline'          => 'Review audit & reputation workflow',
            'app_logo'             => '',
            'app_favicon'          => '',
            'app_thumbnail'        => '',
            'app_footer'           => 'GriView - Google Business Review Audit & Analytics',
            'business_group'       => 'Semua Cabang',
            'ext_name'             => 'GriView Review Audit',
            'ext_version'          => '1.0.1',
            'ext_description'      => 'Audit hingga 1.000 ulasan Google Maps dengan scroll otomatis.',
            'ext_icon'             => '',
            'default_scrape_limit' => '1000',
            'default_photo_filter' => 'all',
            'short_text_threshold' => 40,
            'default_export_format'=> 'xls',
            'custom_base_url'      => '',
            'ext_scroll_speed'     => 'normal',
            'ext_auto_save'        => '1',
            'ext_auto_open_result' => '1',
            'ext_detect_photos'    => '1',
            'ext_auto_expand_more' => '1',
            'place_name'           => DEFAULT_PLACE_NAME,
            'place_address'        => DEFAULT_PLACE_ADDRESS,
            'place_id'             => DEFAULT_PLACE_ID,
            'google_api_key'       => GOOGLE_MAPS_API_KEY
        ];
        return $this->saveSettings($defaults);
    }
}
