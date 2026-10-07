<?php
/* License: by cs.baguosps@gmail.com */
/**
 * ExcelHelper - Generator Dokumen XLS & CSV yang Rapi, Profesional, dan Terformat
 */
require_once __DIR__ . '/../config/config.php';

class ExcelHelper {

    /**
     * Export data ulasan ke format .XLS yang rapi dengan Kop Laporan, KPI Summary, dan Styling Tabel
     */
    public static function exportXls(array $reviews, array $stats, array $meta = []): void {
        $placeName = $meta['place_name'] ?? DEFAULT_PLACE_NAME;
        $placeAddress = $meta['place_address'] ?? DEFAULT_PLACE_ADDRESS;
        $periodName = $meta['period_name'] ?? 'Semua Periode';
        $exportTime = date('d F Y, H:i') . ' WIB';
        $filename = 'Laporan_Ulasan_GoogleMaps_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $periodName) . '_' . date('Ymd_His') . '.xls';

        // Headers HTTP untuk download file Excel
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // Output UTF-8 BOM agar terbaca sempurna di Microsoft Excel
        echo "\xEF\xBB\xBF";

        // Template HTML / XML yang didukung penuh oleh MS Excel, Google Sheets, & LibreOffice
        ?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Ulasan Google Maps</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:FitToPage/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
        }
        .title-header {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
            text-align: left;
            padding-bottom: 4px;
        }
        .subtitle {
            font-size: 11pt;
            color: #475569;
            font-weight: 600;
        }
        .meta-info {
            font-size: 9.5pt;
            color: #64748b;
        }
        .kpi-table {
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .kpi-label {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
            padding: 6px 10px;
            color: #334155;
        }
        .kpi-value {
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            padding: 6px 10px;
            background-color: #ffffff;
        }
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }
        .th-main {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #1e3a8a;
            padding: 8px;
            height: 32px;
        }
        .td-cell {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .wrap-text {
            mso-number-format: "\@";
            white-space: normal;
            word-wrap: break-word;
        }
        .row-alt { background-color: #f8fafc; }
        .row-even { background-color: #ffffff; }
        .badge-positive {
            background-color: #dcfce7;
            color: #166534;
            font-weight: bold;
            text-align: center;
        }
        .badge-neutral {
            background-color: #fef9c3;
            color: #854d0e;
            font-weight: bold;
            text-align: center;
        }
        .badge-negative {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            text-align: center;
        }
        .star-rating {
            font-weight: bold;
            color: #d97706;
            text-align: center;
        }
        .footer-note {
            font-size: 8.5pt;
            color: #94a3b8;
            font-style: italic;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <!-- KOP LAPORAN -->
    <table>
        <tr>
            <td colspan="13" class="title-header">LAPORAN ANALISIS & ULASAN GOOGLE MAPS</td>
        </tr>
        <tr>
            <td colspan="13" class="subtitle"><?= htmlspecialchars($placeName) ?></td>
        </tr>
        <tr>
            <td colspan="13" class="meta-info">Alamat: <?= htmlspecialchars($placeAddress) ?></td>
        </tr>
        <tr>
            <td colspan="13" class="meta-info">
                Periode Laporan: <strong><?= htmlspecialchars($periodName) ?></strong> &nbsp;|&nbsp; 
                Waktu Export: <?= htmlspecialchars($exportTime) ?> &nbsp;|&nbsp; 
                Total Ulasan Tersaring: <strong><?= count($reviews) ?> ulasan</strong>
            </td>
        </tr>
        <tr><td colspan="13"></td></tr>
    </table>

    <!-- RINGKASAN METRIK / KPI BOX -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-label">Total Ulasan</td>
            <td class="kpi-label">Rating Rata-rata</td>
            <td class="kpi-label">Bintang 5 ★</td>
            <td class="kpi-label">Bintang 4 ★</td>
            <td class="kpi-label">Bintang 3 ★</td>
            <td class="kpi-label">Bintang 2 ★</td>
            <td class="kpi-label">Bintang 1 ★</td>
            <td class="kpi-label">Sentimen Positif</td>
            <td class="kpi-label">Sentimen Negatif</td>
            <td class="kpi-label">Tingkat Balasan</td>
        </tr>
        <tr>
            <td class="kpi-value"><?= $stats['total'] ?></td>
            <td class="kpi-value" style="color: #b45309;"><?= number_format($stats['avg_rating'], 1) ?> / 5.0</td>
            <td class="kpi-value" style="color: #15803d;"><?= $stats['star_5'] ?></td>
            <td class="kpi-value" style="color: #16a34a;"><?= $stats['star_4'] ?></td>
            <td class="kpi-value" style="color: #ca8a04;"><?= $stats['star_3'] ?></td>
            <td class="kpi-value" style="color: #ea580c;"><?= $stats['star_2'] ?></td>
            <td class="kpi-value" style="color: #dc2626;"><?= $stats['star_1'] ?></td>
            <td class="kpi-value" style="color: #166534;"><?= $stats['positive'] ?></td>
            <td class="kpi-value" style="color: #991b1b;"><?= $stats['negative'] ?></td>
            <td class="kpi-value" style="color: #1d4ed8;"><?= $stats['reply_rate'] ?>%</td>
        </tr>
        <tr><td colspan="10"></td></tr>
    </table>

    <!-- TABEL UTAMA ULASAN -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="th-main" style="width: 40px;">No</th>
                <th class="th-main" style="width: 180px;">Cabang Store</th>
                <th class="th-main" style="width: 130px;">Waktu Review</th>
                <th class="th-main" style="width: 100px;">Bulan</th>
                <th class="th-main" style="width: 170px;">Nama Reviewer</th>
                <th class="th-main" style="width: 100px;">Tipe Akun</th>
                <th class="th-main" style="width: 110px;">Rating</th>
                <th class="th-main" style="width: 90px;">Sentimen</th>
                <th class="th-main" style="width: 400px;">Isi Ulasan Pengunjung</th>
                <th class="th-main" style="width: 85px;">Jumlah Kata</th>
                <th class="th-main" style="width: 100px;">Status Balasan</th>
                <th class="th-main" style="width: 130px;">Waktu Balasan</th>
                <th class="th-main" style="width: 360px;">Tanggapan / Balasan Owner</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="13" class="td-cell text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada data ulasan yang cocok dengan kriteria filter.
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $no = 1;
                foreach ($reviews as $rev): 
                    $rowClass = ($no % 2 === 0) ? 'row-alt' : 'row-even';
                    $sentiment = strtolower($rev['sentiment'] ?? '');
                    $sentimentBadge = 'badge-neutral';
                    $sentimentText = 'Netral';
                    if ($sentiment === 'positive' || $rev['rating'] >= 4) {
                        $sentimentBadge = 'badge-positive';
                        $sentimentText = 'Positif';
                    } elseif ($sentiment === 'negative' || $rev['rating'] <= 2) {
                        $sentimentBadge = 'badge-negative';
                        $sentimentText = 'Negatif';
                    }

                    $starSymbols = str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5 - (int)$rev['rating']);
                    $hasReplied = !empty($rev['owner_reply']);
                    $statusBalasan = $hasReplied ? 'Sudah Dibalas' : 'Belum Dibalas';
                    $statusBalasanStyle = $hasReplied ? 'color: #15803d; font-weight: bold;' : 'color: #94a3b8;';

                    // Normalisasi teks & tanggal
                    $cleanText = trim($rev['review_text'] ?? '');
                    $cleanTime = trim($rev['review_time'] ?? '');
                    $isDateOnly = preg_match('/^(?:edited\s*|diedit\s*)?(?:a|an|\d+)\s*(?:seconds?|minutes?|hours?|days?|weeks?|months?|years?|detik|menit|jam|hari|minggu|bulan|tahun)\s*(?:ago|yang lalu|lalu)?$/i', $cleanText);
                    if ($isDateOnly && strlen($cleanText) <= 40) {
                        $cleanText = '';
                    }
                    if (strlen($cleanTime) > 40 && empty($cleanText)) {
                        $cleanText = $cleanTime;
                        $cleanTime = date('Y-m-d H:i:s');
                    }
                    $wordCountVal = (int)($rev['word_count'] ?? Review::countWords($cleanText));
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="td-cell text-center"><?= $no++ ?></td>
                    <td class="td-cell text-left">
                        <strong><?= htmlspecialchars($rev['place_name'] ?? 'Winsee Optik') ?></strong>
                    </td>
                    <td class="td-cell text-center" style="mso-number-format: 'yyyy\-mm\-dd hh:mm';">
                        <?= !empty($cleanTime) && strtotime($cleanTime) ? date('d/m/Y H:i', strtotime($cleanTime)) : htmlspecialchars($cleanTime) ?>
                    </td>
                    <td class="td-cell text-center">
                        <?= formatBulanIndo($rev['review_month']) ?>
                    </td>
                    <td class="td-cell text-left">
                        <strong><?= htmlspecialchars($rev['author_name']) ?></strong>
                    </td>
                    <td class="td-cell text-center">
                        <?= (!empty($rev['is_local_guide'])) ? '🌟 Local Guide' : 'Reguler' ?>
                    </td>
                    <td class="td-cell text-center star-rating">
                        <?= $starSymbols ?> (<?= $rev['rating'] ?>.0)
                    </td>
                    <td class="td-cell <?= $sentimentBadge ?>">
                        <?= $sentimentText ?>
                    </td>
                    <td class="td-cell text-left wrap-text">
                        <?= !empty($cleanText) ? nl2br(htmlspecialchars($cleanText)) : '- (Hanya memberikan rating bintang)' ?>
                    </td>
                    <td class="td-cell text-center" style="font-weight: bold; color: #1e3a8a;">
                        <?= $wordCountVal ?> kata
                    </td>
                    <td class="td-cell text-center" style="<?= $statusBalasanStyle ?>">
                        <?= $statusBalasan ?>
                    </td>
                    <td class="td-cell text-center">
                        <?= $hasReplied && !empty($rev['owner_reply_time']) ? date('d/m/Y H:i', strtotime($rev['owner_reply_time'])) : '-' ?>
                    </td>
                    <td class="td-cell text-left wrap-text" style="color: #334155; font-style: <?= $hasReplied ? 'normal' : 'italic' ?>;">
                        <?= $hasReplied ? nl2br(htmlspecialchars($rev['owner_reply'])) : '- Belum ada balasan -' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table>
        <tr><td colspan="13"></td></tr>
        <tr>
            <td colspan="13" class="footer-note">
                Dokumen ini diexport secara otomatis oleh GriView - Sistem Manajemen & Laporan Ulasan Google Maps pada <?= date('d/m/Y H:i:s') ?> WIB.
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Export data ulasan ke format CSV terstandar
     */
    public static function exportCsv(array $reviews, array $meta = []): void {
        $periodName = $meta['period_name'] ?? 'Semua';
        $filename = 'Ulasan_GoogleMaps_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $periodName) . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        // Headers
        fputcsv($output, [
            'No',
            'Cabang Store',
            'ID Ulasan',
            'Tanggal & Waktu',
            'Bulan (Periode)',
            'Nama Reviewer',
            'Tipe Reviewer',
            'Rating',
            'Sentimen',
            'Isi Ulasan',
            'Jumlah Kata',
            'Status Balasan',
            'Waktu Balasan',
            'Balasan Owner'
        ], ';');

        $no = 1;
        foreach ($reviews as $r) {
            $hasReplied = !empty($r['owner_reply']);
            $wordCount = (int)($r['word_count'] ?? Review::countWords($r['review_text'] ?? ''));
            fputcsv($output, [
                $no++,
                $r['place_name'] ?? 'Winsee Optik',
                $r['google_review_id'] ?? '',
                $r['review_time'],
                formatBulanIndo($r['review_month']),
                $r['author_name'],
                (!empty($r['is_local_guide'])) ? 'Local Guide' : 'Reguler',
                $r['rating'],
                ucfirst($r['sentiment'] ?? ''),
                $r['review_text'] ?? '',
                $wordCount,
                $hasReplied ? 'Sudah Dibalas' : 'Belum Dibalas',
                $r['owner_reply_time'] ?? '',
                $r['owner_reply'] ?? ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export data review hasil audit ke format .XLS yang rapi
     */
    public static function exportAuditXls(array $reviews, array $summary, array $meta = [], bool $returnContent = false): ?string {
        $placeName = $meta['place_name'] ?? DEFAULT_PLACE_NAME;
        $placeAddress = $meta['place_address'] ?? DEFAULT_PLACE_ADDRESS;
        $exportTime = date('d F Y, H:i') . ' WIB';
        $filename = 'Review_Audit_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $placeName) . '_' . date('Ymd_His') . '.xls';

        ob_start();
        echo "\xEF\xBB\xBF";
        ?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Review Audit</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:FitToPage/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #1e293b; }
        .title-header { font-size: 16pt; font-weight: bold; color: #1e3a8a; text-align: left; }
        .subtitle { font-size: 13pt; font-weight: bold; color: #334155; }
        .meta-info { font-size: 9.5pt; color: #64748b; }
        .kpi-table { margin-top: 10px; margin-bottom: 15px; border-collapse: collapse; }
        .kpi-label { background-color: #f1f5f9; color: #475569; font-size: 8.5pt; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; padding: 6px; }
        .kpi-value { background-color: #ffffff; font-size: 14pt; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; padding: 8px; }
        .data-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        .th-main { background-color: #1e3a8a; color: #ffffff; font-weight: bold; font-size: 9.5pt; text-align: center; border: 1px solid #172554; padding: 8px 6px; vertical-align: middle; }
        .td-cell { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 9pt; vertical-align: top; mso-number-format: "\@"; }
        .td-center { text-align: center; }
        .td-num { text-align: center; mso-number-format: "0"; }
        .row-alt { background-color: #f8fafc; }
        .badge-priority { color: #dc2626; font-weight: bold; }
        .badge-clear { color: #16a34a; font-weight: bold; }
        .footer-note { font-size: 8.5pt; color: #94a3b8; font-style: italic; padding-top: 10px; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="10" class="title-header">LAPORAN AUDIT ULASAN GOOGLE MAPS</td></tr>
        <tr><td colspan="10" class="subtitle"><?= htmlspecialchars($placeName) ?></td></tr>
        <tr><td colspan="10" class="meta-info">Alamat: <?= htmlspecialchars($placeAddress) ?></td></tr>
        <tr>
            <td colspan="10" class="meta-info">
                Waktu Audit / Export: <?= htmlspecialchars($exportTime) ?> &nbsp;|&nbsp; 
                Total Ulasan Diperiksa: <strong><?= count($reviews) ?> review</strong>
            </td>
        </tr>
        <tr><td colspan="10"></td></tr>
    </table>

    <table class="kpi-table">
        <tr>
            <td class="kpi-label">Total Diperiksa</td>
            <td class="kpi-label">Prioritas (Rating 1-2)</td>
            <td class="kpi-label">Belum Dibalas</td>
            <td class="kpi-label">Lolos Pemeriksaan</td>
            <td class="kpi-label">Rating Rata-rata</td>
        </tr>
        <tr>
            <td class="kpi-value"><?= number_format($summary['audited'] ?? count($reviews)) ?></td>
            <td class="kpi-value" style="color: #dc2626;"><?= number_format($summary['priority'] ?? 0) ?></td>
            <td class="kpi-value" style="color: #2563eb;"><?= number_format($summary['unanswered'] ?? 0) ?></td>
            <td class="kpi-value" style="color: #16a34a;"><?= number_format($summary['clear'] ?? 0) ?></td>
            <td class="kpi-value" style="color: #ca8a04;">
                <?php
                $ratings = array_filter(array_column($reviews, 'rating'));
                $avgR = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 5.0;
                echo $avgR . ' / 5.0';
                ?>
            </td>
        </tr>
        <tr><td colspan="5"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="th-main" style="width: 45px;">No</th>
                <th class="th-main" style="width: 170px;">Nama Reviewer</th>
                <th class="th-main" style="width: 130px;">Waktu Review</th>
                <th class="th-main" style="width: 65px;">Rating</th>
                <th class="th-main" style="width: 110px;">Foto Kontributor</th>
                <th class="th-main" style="width: 100px;">Foto Review</th>
                <th class="th-main" style="width: 420px;">Komentar Review</th>
                <th class="th-main" style="width: 85px;">Jumlah Kata</th>
                <th class="th-main" style="width: 320px;">Balasan Pemilik</th>
                <th class="th-main" style="width: 160px;">Temuan Audit</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($reviews as $idx => $r):
                $isAlt = ($idx % 2 !== 0);
                $findings = [];
                if (!empty($r['audit_findings']) && is_array($r['audit_findings'])) {
                    $findings = array_column($r['audit_findings'], 'label');
                }
                $findingText = !empty($findings) ? implode(', ', $findings) : 'Lolos pemeriksaan';
                $wc = (int)($r['word_count'] ?? (class_exists('Review') ? Review::countWords($r['review_text'] ?? '') : 0));
                $rating = (int)($r['rating'] ?? 0);
                $reviewerPhoto = isset($r['reviewer_photo_count']) && is_numeric($r['reviewer_photo_count']) ? $r['reviewer_photo_count'] . ' foto' : 'Tidak tersedia';
                $reviewPhoto = isset($r['review_photo_count']) && is_numeric($r['review_photo_count']) ? $r['review_photo_count'] . ' foto' : 'Tidak tersedia';
            ?>
            <tr class="<?= $isAlt ? 'row-alt' : '' ?>">
                <td class="td-cell td-num"><?= $no++ ?></td>
                <td class="td-cell"><strong><?= htmlspecialchars($r['author_name'] ?? 'Pengguna Google') ?></strong></td>
                <td class="td-cell td-center">
                    <?= htmlspecialchars(function_exists('formatTanggalIndo') ? formatTanggalIndo($r['review_time'] ?? '') : ($r['review_time'] ?? '')) ?>
                    <?php if (!empty($r['review_date_text'])): ?>
                        <br><span style="color:#64748b; font-size: 8pt;">(<?= htmlspecialchars($r['review_date_text']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="td-cell td-center" style="color:#d97706;"><?= $rating ?> ★</td>
                <td class="td-cell td-center"><?= htmlspecialchars($reviewerPhoto) ?></td>
                <td class="td-cell td-center"><?= htmlspecialchars($reviewPhoto) ?></td>
                <td class="td-cell"><?= htmlspecialchars($r['review_text'] ?: 'Hanya memberikan rating') ?></td>
                <td class="td-cell td-num"><?= $wc ?></td>
                <td class="td-cell"><?= htmlspecialchars($r['owner_reply'] ?: 'Belum dibalas') ?></td>
                <td class="td-cell <?= empty($findings) ? 'badge-clear' : 'badge-priority' ?>"><?= htmlspecialchars($findingText) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <table>
        <tr>
            <td colspan="10" class="footer-note">
                Dokumen hasil audit ulasan Google Business Profile &copy; <?= date('Y') ?> GriView &mdash; Lisensi by cs.baguosps@gmail.com
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        $content = ob_get_clean();

        if ($returnContent) {
            return $content;
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
