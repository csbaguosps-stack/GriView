<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Model Review - Menangani data ulasan Google Maps via JSON
 */
require_once __DIR__ . '/../helpers/JsonDatabase.php';

class Review {

    public function __construct() {
        JsonDatabase::init();
    }

    // ─── Baca semua ulasan ───────────────────────────────────────────────────

    // ─── Word Counter Helper ────────────────────────────────────────────────
    public static function countWords(?string $text): int {
        if ($text === null) return 0;
        $clean = trim(strip_tags($text));
        if ($clean === '') return 0;
        return (int)preg_match_all('/[\p{L}\p{N}]+(?:[\'’\-][\p{L}\p{N}]+)*/u', $clean);
    }

    /**
     * Konversi string tanggal/waktu ulasan Google Maps (EN/ID) menjadi format MySQL datetime (Y-m-d H:i:s).
     *
     * Mendukung:
     * - Relative EN: "a week ago", "2 weeks ago", "a month ago", "2 months ago", "a year ago", "3 days ago", "13 hours ago", "yesterday", "just now"
     * - Relative ID: "seminggu lalu", "sebulan yang lalu", "2 bulan lalu", "setahun lalu", "3 hari lalu", "kemarin", "baru saja"
     * - Prefix: "Edited · a month ago", "Diedit · 2 minggu lalu"
     * - Kalender: "15 Agu 2024", "12 Okt 2023", "Oct 12, 2023", "2024-05-10"
     */
    public static function parseReviewDate(string $dateText, ?int $baseTimestamp = null): string {
        $baseTimestamp = $baseTimestamp ?: time();
        $raw = trim($dateText);
        if ($raw === '') {
            return date('Y-m-d H:i:s', $baseTimestamp);
        }

        // Bersihkan prefix seperti "Edited · ", "Diedit · ", "Edited ", "Diedit "
        $clean = preg_replace('/^(?:edited|diedit)[\s·•\-]*/iu', '', $raw);
        $clean = trim($clean, " \t\n\r\0\x0B·•-");

        // 1. Keyword instan
        if (preg_match('/^(?:just now|baru saja|hari ini|today)$/i', $clean)) {
            return date('Y-m-d H:i:s', $baseTimestamp);
        }
        if (preg_match('/^(?:yesterday|kemarin)$/i', $clean)) {
            return date('Y-m-d H:i:s', strtotime('-1 day', $baseTimestamp));
        }

        // 2. Relative waktu (Detik, Menit, Jam, Hari, Minggu, Bulan, Tahun)
        // Ganti "a / an / se-" sebelum satuan waktu dengan "1 "
        $normalized = preg_replace('/\b(?:an?|se)\s*(?=(?:second|minute|hour|day|week|month|year|detik|menit|jam|hari|minggu|bulan|tahun)\b)/i', '1 ', $clean);

        if (preg_match('/(\d+)\s*(?:second|detik)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} seconds", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:minute|menit)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} minutes", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:hour|jam)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} hours", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:day|hari)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} days", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:week|minggu)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} weeks", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:month|bulan)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} months", $baseTimestamp));
        }
        if (preg_match('/(\d+)\s*(?:year|tahun)/i', $normalized, $m)) {
            $n = (int)$m[1];
            return date('Y-m-d H:i:s', strtotime("-{$n} years", $baseTimestamp));
        }

        // 3. Tanggal Kalender (terjemahkan nama bulan Indonesia ke Inggris)
        $monthMap = [
            'Januari' => 'January', 'Jan' => 'Jan',
            'Februari' => 'February', 'Pebruari' => 'February', 'Feb' => 'Feb',
            'Maret' => 'March', 'Mar' => 'Mar',
            'April' => 'April', 'Apr' => 'Apr',
            'Mei' => 'May',
            'Juni' => 'June', 'Jun' => 'Jun',
            'Juli' => 'July', 'Jul' => 'Jul',
            'Agustus' => 'August', 'Agu' => 'Aug', 'Ags' => 'Aug',
            'September' => 'September', 'Sep' => 'Sep', 'Sept' => 'Sep',
            'Oktober' => 'October', 'Okt' => 'Oct',
            'November' => 'November', 'Nop' => 'Nov', 'Nov' => 'Nov',
            'Desember' => 'December', 'Des' => 'Dec'
        ];
        $translatedCalendar = preg_replace_callback('/\b([a-zA-Z]{3,9})\b/u', function ($match) use ($monthMap) {
            $word = ucfirst(strtolower($match[1]));
            return $monthMap[$word] ?? $match[1];
        }, $clean);

        $parsed = strtotime($translatedCalendar, $baseTimestamp);
        if ($parsed !== false && $parsed > 0) {
            return date('Y-m-d H:i:s', $parsed);
        }

        return date('Y-m-d H:i:s', $baseTimestamp);
    }

    // ─── Audit Data Enrichment Helper ────────────────────────────────────────
    public static function attachAuditData(array &$r): void {
        $findings = [];
        $rating = (int)($r['rating'] ?? 0);
        $text = trim(strip_tags((string)($r['review_text'] ?? '')));
        $reply = trim((string)($r['owner_reply'] ?? ''));

        if ($rating > 0 && $rating <= 2) {
            $findings[] = ['key' => 'priority', 'label' => 'Rating rendah (1-2★)', 'severity' => 'high'];
        }
        if ($reply === '') {
            $findings[] = ['key' => 'unanswered', 'label' => 'Belum dibalas', 'severity' => 'medium'];
        }
        if ($text === '') {
            $findings[] = ['key' => 'rating_only', 'label' => 'Tanpa teks', 'severity' => 'low'];
        } elseif (mb_strlen($text) < 40) {
            $findings[] = ['key' => 'short_text', 'label' => 'Teks singkat', 'severity' => 'low'];
        }

        $reviewTime = !empty($r['review_time']) ? strtotime($r['review_time']) : false;
        $replyTime = !empty($r['owner_reply_time']) ? strtotime($r['owner_reply_time']) : false;
        if ($reviewTime && $replyTime && $replyTime - $reviewTime > 48 * 60 * 60) {
            $findings[] = ['key' => 'slow_response', 'label' => 'Respons > 48 jam', 'severity' => 'medium'];
        }

        $r['audit_findings'] = $findings;
        $r['audit_status'] = empty($findings) ? 'clear' : (in_array('priority', array_column($findings, 'key'), true) ? 'priority' : 'attention');
        
        if (!isset($r['reviewer_photo_count'])) {
            $r['reviewer_photo_count'] = null;
        }
        if (!isset($r['review_photo_count'])) {
            $r['review_photo_count'] = 0;
        }
    }

    // ─── Baca semua ulasan ───────────────────────────────────────────────────

    private function loadAll(): array {
        $reviews = JsonDatabase::readJson(JsonDatabase::reviewsPath());
        foreach ($reviews as &$r) {
            $txt = trim($r['review_text'] ?? '');
            // Jika review_text isinya hanya string tanggal atau metadata reviewer
            if ($txt !== '' && (
                preg_match('/^(?:edited\s*|diedit\s*)?(?:a|an|\d+)\s*(?:seconds?|minutes?|hours?|days?|weeks?|months?|years?|detik|menit|jam|hari|minggu|bulan|tahun)\s*(?:ago|yang lalu|lalu)?$/i', $txt)
                || preg_match('/^(?:local\s*guide\s*[·,]?\s*)?\d+[\d,.]*\s*(?:reviews?|ulasan)$/i', $txt)
            )) {
                $r['review_text'] = '';
                $txt = '';
            }
            $r['word_count'] = self::countWords($txt);
            self::attachAuditData($r);
        }
        unset($r);
        return $reviews;
    }

    private function saveAll(array $reviews): bool {
        // Re-index array
        return JsonDatabase::writeJson(JsonDatabase::reviewsPath(), array_values($reviews));
    }

    // ─── Filter helper ───────────────────────────────────────────────────────

    private function applyFilters(array $reviews, array $filters): array {
        return array_filter($reviews, function ($r) use ($filters) {

            // Filter Bulan (YYYY-MM)
            if (!empty($filters['month']) && $filters['month'] !== 'all') {
                if (($r['review_month'] ?? '') !== $filters['month']) return false;
            }

            // Filter Tahun
            if (!empty($filters['year']) && $filters['year'] !== 'all') {
                $year = substr($r['review_time'] ?? '', 0, 4);
                if ($year !== $filters['year']) return false;
            }

            // Filter Rating
            if (!empty($filters['rating']) && is_numeric($filters['rating'])) {
                if ((int)($r['rating'] ?? 0) !== (int)$filters['rating']) return false;
            }

            // Filter Store
            if (!empty($filters['store_id']) && $filters['store_id'] !== 'all') {
                $sid = $filters['store_id'];
                if (($r['store_id'] ?? '') != $sid && ($r['store_code'] ?? '') != $sid) return false;
            }

            // Filter Sentimen
            if (!empty($filters['sentiment']) && $filters['sentiment'] !== 'all') {
                if (($r['sentiment'] ?? '') !== $filters['sentiment']) return false;
            }

            // Filter Status Balasan
            if (isset($filters['reply_status']) && $filters['reply_status'] !== 'all') {
                $hasReply = !empty($r['owner_reply']);
                if ($filters['reply_status'] === 'replied' && !$hasReply) return false;
                if ($filters['reply_status'] === 'unreplied' && $hasReply) return false;
            }

            // Filter Temuan Audit
            if (!empty($filters['finding']) && $filters['finding'] !== 'all') {
                if ($filters['finding'] === 'attention') {
                    if (empty($r['audit_findings'])) return false;
                } elseif ($filters['finding'] === 'clear') {
                    if (!empty($r['audit_findings'])) return false;
                } else {
                    $keys = array_column($r['audit_findings'] ?? [], 'key');
                    if (!in_array($filters['finding'], $keys, true)) return false;
                }
            }

            // Search text
            if (!empty($filters['search'])) {
                $q = mb_strtolower(trim($filters['search']));
                $haystack = mb_strtolower(
                    ($r['author_name'] ?? '') . ' ' .
                    ($r['review_text'] ?? '') . ' ' .
                    ($r['owner_reply'] ?? '')
                );
                if (strpos($haystack, $q) === false) return false;
            }

            // Filter Jumlah Kata
            if (isset($filters['words']) && $filters['words'] !== '' && $filters['words'] !== 'all') {
                $wc = (int)($r['word_count'] ?? self::countWords($r['review_text'] ?? ''));
                switch ($filters['words']) {
                    case 'none':   if ($wc !== 0) return false; break;
                    case '1_9':    if ($wc < 1 || $wc > 9) return false; break;
                    case '10_19':  if ($wc < 10 || $wc > 19) return false; break;
                    case '20_29':  if ($wc < 20 || $wc > 29) return false; break;
                    case '30plus': if ($wc < 30) return false; break;
                }
            }

            // Filter Jumlah Foto (mendukung foto ulasan maupun foto kontributor yang ditampilkan pada tabel)
            if (isset($filters['photos']) && $filters['photos'] !== '' && $filters['photos'] !== 'all') {
                $revPhotos = (int)($r['review_photo_count'] ?? 0);
                $usrPhotos = (int)($r['reviewer_photo_count'] ?? 0);
                $hasAny    = ($revPhotos > 0 || $usrPhotos > 0);

                switch ((string)$filters['photos']) {
                    case 'has_photo':
                    case 'with_photo':
                        if (!$hasAny) return false;
                        break;
                    case 'no_photo':
                    case '0':
                        // 0 foto / tanpa foto: baik foto ulasan maupun profil tidak ada
                        if ($hasAny) return false;
                        break;
                    case '1':
                        if ($revPhotos !== 1 && $usrPhotos !== 1) return false;
                        break;
                    case '2':
                        if ($revPhotos !== 2 && $usrPhotos !== 2) return false;
                        break;
                    case '3':
                        if ($revPhotos !== 3 && $usrPhotos !== 3) return false;
                        break;
                    case '4':
                        if ($revPhotos !== 4 && $usrPhotos !== 4) return false;
                        break;
                    case '5':
                        if ($revPhotos !== 5 && $usrPhotos !== 5) return false;
                        break;
                    case '5plus':
                        if ($revPhotos <= 5 && $usrPhotos <= 5) return false;
                        break;
                }
            }

            return true;
        });
    }

    private function sortReviews(array $reviews, string $sort): array {
        usort($reviews, function ($a, $b) use ($sort) {
            switch ($sort) {
                case 'words_desc':
                    $cmp = (int)($b['word_count'] ?? 0) - (int)($a['word_count'] ?? 0);
                    return $cmp !== 0 ? $cmp : strcmp($b['review_time'] ?? '', $a['review_time'] ?? '');
                case 'words_asc':
                    $cmp = (int)($a['word_count'] ?? 0) - (int)($b['word_count'] ?? 0);
                    return $cmp !== 0 ? $cmp : strcmp($b['review_time'] ?? '', $a['review_time'] ?? '');
                case 'rating_high':
                    $cmp = (int)($b['rating'] ?? 0) - (int)($a['rating'] ?? 0);
                    return $cmp !== 0 ? $cmp : strcmp($b['review_time'] ?? '', $a['review_time'] ?? '');
                case 'rating_low':
                    $cmp = (int)($a['rating'] ?? 0) - (int)($b['rating'] ?? 0);
                    return $cmp !== 0 ? $cmp : strcmp($b['review_time'] ?? '', $a['review_time'] ?? '');
                case 'date_asc':
                    return strcmp($a['review_time'] ?? '', $b['review_time'] ?? '');
                case 'date_desc':
                default:
                    return strcmp($b['review_time'] ?? '', $a['review_time'] ?? '');
            }
        });
        return $reviews;
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array {
        $all = $this->loadAll();
        $filtered = array_values($this->applyFilters($all, $filters));
        $sorted = $this->sortReviews($filtered, $filters['sort'] ?? 'date_desc');

        if ($limit > 0) {
            $offset = ($page - 1) * $limit;
            return array_slice($sorted, $offset, $limit);
        }
        return $sorted;
    }

    public function count(array $filters = []): int {
        $all = $this->loadAll();
        return count($this->applyFilters($all, $filters));
    }

    public function getAuditReport(array $filters = []): array {
        return self::buildAuditReport($this->getAll($filters, 1, 0));
    }

    public static function buildAuditReport(array $reviews): array {
        $summary = [
            'audited' => count($reviews),
            'attention' => 0,
            'priority' => 0,
            'unanswered' => 0,
            'rating_only' => 0,
            'short_text' => 0,
            'slow_response' => 0,
            'clear' => 0,
        ];

        foreach ($reviews as &$review) {
            if (!isset($review['audit_findings'])) {
                self::attachAuditData($review);
            }
            $findings = $review['audit_findings'] ?? [];
            $keys = array_column($findings, 'key');

            if (empty($findings)) {
                $summary['clear']++;
            } else {
                $summary['attention']++;
            }

            if (in_array('priority', $keys, true)) $summary['priority']++;
            if (in_array('unanswered', $keys, true)) $summary['unanswered']++;
            if (in_array('rating_only', $keys, true)) $summary['rating_only']++;
            if (in_array('short_text', $keys, true)) $summary['short_text']++;
            if (in_array('slow_response', $keys, true)) $summary['slow_response']++;
        }
        unset($review);

        return ['summary' => $summary, 'reviews' => array_values($reviews)];
    }

    public function getById(int $id): ?array {
        foreach ($this->loadAll() as $r) {
            if ((int)($r['id'] ?? 0) === $id) return $r;
        }
        return null;
    }

    public function getAvailableMonths($storeId = null): array {
        $all = $this->loadAll();
        if (!empty($storeId) && $storeId !== 'all') {
            $all = array_filter($all, function ($r) use ($storeId) {
                return ($r['store_id'] ?? '') == $storeId || ($r['store_code'] ?? '') == $storeId;
            });
        }

        $months = [];
        foreach ($all as $r) {
            $m = $r['review_month'] ?? '';
            if (!$m) continue;
            if (!isset($months[$m])) {
                $months[$m] = ['review_month' => $m, 'total_reviews' => 0, 'avg_rating' => 0, '_ratings' => []];
            }
            $months[$m]['total_reviews']++;
            $months[$m]['_ratings'][] = (int)($r['rating'] ?? 0);
        }

        foreach ($months as &$m) {
            $m['avg_rating'] = !empty($m['_ratings']) ? round(array_sum($m['_ratings']) / count($m['_ratings']), 2) : 0;
            unset($m['_ratings']);
        }

        // Sort descending
        usort($months, fn($a, $b) => strcmp($b['review_month'], $a['review_month']));
        return array_values($months);
    }

    public function getStats($filters = [], $storeId = null): array {
        $all = $this->loadAll();

        if (is_array($filters)) {
            $all = $this->applyFilters($all, $filters);
        } else {
            $month = $filters;
            $f = [];
            if (!empty($month) && $month !== 'all') {
                $f['month'] = $month;
            }
            if (!empty($storeId) && $storeId !== 'all') {
                $f['store_id'] = $storeId;
            }
            $all = $this->applyFilters($all, $f);
        }

        $all = array_values($all);
        $total    = count($all);
        $ratings  = array_column($all, 'rating');
        $avgRating = $total > 0 ? round(array_sum($ratings) / $total, 2) : 0.0;

        $star     = [1=>0,2=>0,3=>0,4=>0,5=>0];
        $sentiment = ['positive'=>0,'neutral'=>0,'negative'=>0];
        $replied  = 0; $unreplied = 0;

        $totalWords = 0;
        $withText   = 0;
        $maxWords   = 0;
        $wordBreakdown = [
            'long'   => 0, // > 30 kata
            'medium' => 0, // 10 - 30 kata
            'short'  => 0, // 1 - 9 kata
            'none'   => 0  // 0 kata
        ];

        foreach ($all as $r) {
            $s = (int)($r['rating'] ?? 0);
            if ($s >= 1 && $s <= 5) $star[$s]++;
            $sent = $r['sentiment'] ?? '';
            if (isset($sentiment[$sent])) $sentiment[$sent]++;
            if (!empty($r['owner_reply'])) $replied++; else $unreplied++;

            $wc = (int)($r['word_count'] ?? self::countWords($r['review_text'] ?? ''));
            $totalWords += $wc;
            if ($wc > 0) {
                $withText++;
                if ($wc > $maxWords) {
                    $maxWords = $wc;
                }
            }
            if ($wc > 30) {
                $wordBreakdown['long']++;
            } elseif ($wc >= 10) {
                $wordBreakdown['medium']++;
            } elseif ($wc >= 1) {
                $wordBreakdown['short']++;
            } else {
                $wordBreakdown['none']++;
            }
        }

        $replyRate = $total > 0 ? round(($replied / $total) * 100, 1) : 0;
        $avgWords  = $total > 0 ? round($totalWords / $total, 1) : 0.0;
        $avgWordsWithText = $withText > 0 ? round($totalWords / $withText, 1) : 0.0;

        $auditReport = self::buildAuditReport($all);
        $summary = $auditReport['summary'];

        return [
            'total'               => $total,
            'avg_rating'          => $avgRating,
            'star_5'              => $star[5],
            'star_4'              => $star[4],
            'star_3'              => $star[3],
            'star_2'              => $star[2],
            'star_1'              => $star[1],
            'positive'            => $sentiment['positive'],
            'neutral'             => $sentiment['neutral'],
            'negative'            => $sentiment['negative'],
            'replied'             => $replied,
            'unreplied'           => $unreplied,
            'reply_rate'          => $replyRate,
            'total_words'         => $totalWords,
            'avg_words'           => $avgWords,
            'avg_words_with_text' => $avgWordsWithText,
            'max_words'           => $maxWords,
            'with_text_count'     => $withText,
            'without_text_count'  => $total - $withText,
            'word_breakdown'      => $wordBreakdown,
            'attention'           => $summary['attention'],
            'priority'            => $summary['priority'],
            'audit_clear'         => $summary['clear'],
            'summary'             => $summary,
        ];
    }

    public function getMonthlyTrend($storeId = null): array {
        $all = $this->loadAll();
        if (!empty($storeId) && $storeId !== 'all') {
            $all = array_filter($all, function ($r) use ($storeId) {
                return ($r['store_id'] ?? '') == $storeId || ($r['store_code'] ?? '') == $storeId;
            });
        }
        $months = [];
        foreach ($all as $r) {
            $m = $r['review_month'] ?? '';
            if (!$m) continue;
            if (!isset($months[$m])) {
                $months[$m] = ['review_month' => $m, 'count' => 0, '_ratings' => [], 'positive_count' => 0, 'negative_count' => 0];
            }
            $months[$m]['count']++;
            $rating = (int)($r['rating'] ?? 0);
            $months[$m]['_ratings'][] = $rating;
            if ($rating >= 4) $months[$m]['positive_count']++;
            if ($rating <= 2) $months[$m]['negative_count']++;
        }

        foreach ($months as &$m) {
            $m['avg_rating'] = !empty($m['_ratings']) ? round(array_sum($m['_ratings']) / count($m['_ratings']), 2) : 0;
            unset($m['_ratings']);
        }

        ksort($months);
        return array_values($months);
    }

    public function updateReply(int $id, string $replyText): bool {
        $reviews = $this->loadAll();
        foreach ($reviews as &$r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $r['owner_reply']      = trim($replyText);
                $r['owner_reply_time'] = date('Y-m-d H:i:s');
                return $this->saveAll($reviews);
            }
        }
        return false;
    }

    public function delete(int $id): bool {
        $reviews = $this->loadAll();
        $new = array_filter($reviews, fn($r) => (int)($r['id'] ?? 0) !== $id);
        return $this->saveAll(array_values($new));
    }

    public function deleteMultiple(array $ids): int {
        if (empty($ids)) return 0;
        $idSet = array_flip(array_map('intval', $ids));
        $reviews = $this->loadAll();
        $initial = count($reviews);
        $new = array_filter($reviews, function ($r) use ($idSet) {
            $rid = (int)($r['id'] ?? 0);
            return !isset($idSet[$rid]);
        });
        $deleted = $initial - count($new);
        $this->saveAll(array_values($new));
        return $deleted;
    }

    public function deleteAll(): bool {
        return JsonDatabase::writeJson(JsonDatabase::reviewsPath(), []);
    }

    /**
     * Hapus ulasan berdasarkan filter yang aktif
     * Jika tidak ada filter aktif, hapus semua ulasan.
     */
    public function deleteByFilters(array $filters = []): int {
        $reviews = $this->loadAll();
        if (empty($reviews)) {
            return 0;
        }

        // Cek apakah ada filter yang aktif
        $hasActiveFilter = false;
        foreach (['month', 'year', 'rating', 'store_id', 'sentiment', 'reply_status', 'finding', 'words', 'photos', 'search'] as $k) {
            if (isset($filters[$k]) && $filters[$k] !== '' && $filters[$k] !== 'all') {
                $hasActiveFilter = true;
                break;
            }
        }

        // Jika tidak ada filter aktif sama sekali, hapus seluruh data
        if (!$hasActiveFilter) {
            $count = count($reviews);
            $this->deleteAll();
            return $count;
        }

        // Dapatkan ulasan yang cocok dengan filter
        $matching = $this->applyFilters($reviews, $filters);
        if (empty($matching)) {
            return 0;
        }

        // Kumpulkan identifier unik dari ulasan yang cocok
        $matchingIds = [];
        $matchingGids = [];
        foreach ($matching as $m) {
            if (isset($m['id'])) {
                $matchingIds[(int)$m['id']] = true;
            }
            if (!empty($m['google_review_id'])) {
                $matchingGids[$m['google_review_id']] = true;
            }
        }

        // Saring: hanya simpan ulasan yang TIDAK termasuk dalam ulasan yang cocok
        $initial = count($reviews);
        $remaining = array_filter($reviews, function ($r) use ($matchingIds, $matchingGids) {
            $id = isset($r['id']) ? (int)$r['id'] : null;
            $gid = $r['google_review_id'] ?? null;
            if ($id !== null && isset($matchingIds[$id])) {
                return false;
            }
            if ($gid !== null && isset($matchingGids[$gid])) {
                return false;
            }
            return true;
        });

        $deletedCount = $initial - count($remaining);
        $this->saveAll(array_values($remaining));
        return $deletedCount;
    }

    public function insertOrUpdate(array $data): bool {
        return $this->insertOrUpdateBatch([$data]) > 0;
    }

    /**
     * Simpan massal (Batch): Baca reviews.json 1x, proses di memori, tulis 1x.
     * Sangat cepat dan efisien untuk scraper (50 ulasan = 1x tulis file).
     */
    public function insertOrUpdateBatch(array $records): int {
        if (empty($records)) {
            return 0;
        }

        $reviews = $this->loadAll();

        // Buat index pencarian cepat berdasarkan google_review_id
        $indexed = [];
        $maxId   = 0;
        foreach ($reviews as $idx => $r) {
            $rid = (int)($r['id'] ?? 0);
            if ($rid > $maxId) {
                $maxId = $rid;
            }
            if (!empty($r['google_review_id'])) {
                $indexed[$r['google_review_id']] = $idx;
            }
        }

        $processedCount = 0;
        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        foreach ($records as $data) {
            $reviewTime = $data['review_time'] ?? $now;
            $month      = date('Y-m', strtotime($reviewTime));
            $rating     = (int)($data['rating'] ?? 5);
            $sentiment  = $data['sentiment'] ?? ($rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative'));
            $gid        = $data['google_review_id'] ?? ('MANUAL_' . uniqid());

            if (isset($indexed[$gid])) {
                // Update review yang sudah ada
                $idx = $indexed[$gid];
                $reviews[$idx]['store_id']             = $data['store_id'] ?? $reviews[$idx]['store_id'];
                $reviews[$idx]['store_code']           = $data['store_code'] ?? $reviews[$idx]['store_code'];
                $reviews[$idx]['place_name']           = $data['place_name'] ?? $reviews[$idx]['place_name'];
                $reviews[$idx]['author_name']          = $data['author_name'] ?? $reviews[$idx]['author_name'];
                $reviews[$idx]['author_photo_url']     = $data['author_photo_url'] ?? $reviews[$idx]['author_photo_url'];
                $reviews[$idx]['rating']               = $rating;
                $reviews[$idx]['review_text']          = $data['review_text'] ?? $reviews[$idx]['review_text'];
                $reviews[$idx]['word_count']           = isset($data['word_count']) && is_numeric($data['word_count']) ? (int)$data['word_count'] : self::countWords($reviews[$idx]['review_text'] ?? '');
                $reviews[$idx]['review_time']          = $reviewTime;
                $reviews[$idx]['review_month']         = $month;
                if (!empty($data['review_date_text'])) {
                    $reviews[$idx]['review_date_text'] = $data['review_date_text'];
                }
                if (isset($data['reviewer_photo_count'])) {
                    $reviews[$idx]['reviewer_photo_count'] = $data['reviewer_photo_count'];
                }
                if (isset($data['review_photo_count'])) {
                    $reviews[$idx]['review_photo_count']   = $data['review_photo_count'];
                }
                $reviews[$idx]['sentiment']            = $sentiment;
                $reviews[$idx]['owner_reply']          = $data['owner_reply'] ?? $reviews[$idx]['owner_reply'];
                $reviews[$idx]['owner_reply_time']     = $data['owner_reply_time'] ?? $reviews[$idx]['owner_reply_time'];
                $processedCount++;
            } else {
                // Tambah review baru
                $maxId++;
                $reviewText = $data['review_text'] ?? '';
                $wordCount  = isset($data['word_count']) && is_numeric($data['word_count']) ? (int)$data['word_count'] : self::countWords($reviewText);
                $new = [
                    'id'                   => $maxId,
                    'google_review_id'     => $gid,
                    'store_id'             => $data['store_id'] ?? null,
                    'store_code'           => $data['store_code'] ?? null,
                    'place_id'             => $data['place_id'] ?? DEFAULT_PLACE_ID,
                    'place_name'           => $data['place_name'] ?? DEFAULT_PLACE_NAME,
                    'author_name'          => $data['author_name'] ?? 'Pengguna Google',
                    'author_photo_url'     => $data['author_photo_url'] ?? null,
                    'author_url'           => $data['author_url'] ?? null,
                    'rating'               => $rating,
                    'review_text'          => $reviewText,
                    'word_count'           => $wordCount,
                    'review_time'          => $reviewTime,
                    'review_month'         => $month,
                    'review_date_text'     => $data['review_date_text'] ?? null,
                    'reviewer_photo_count' => $data['reviewer_photo_count'] ?? null,
                    'review_photo_count'   => $data['review_photo_count'] ?? 0,
                    'sentiment'            => $sentiment,
                    'is_local_guide'       => (int)($data['is_local_guide'] ?? 0),
                    'review_language'      => $data['review_language'] ?? 'id',
                    'owner_reply'          => $data['owner_reply'] ?? null,
                    'owner_reply_time'     => $data['owner_reply_time'] ?? null,
                    'created_at'           => $now,
                ];
                $reviews[] = $new;
                $indexed[$gid] = count($reviews) - 1;
                $processedCount++;
            }
        }

        // Tulis satu kali saja ke reviews.json
        $this->saveAll($reviews);
        return $processedCount;
    }
}

