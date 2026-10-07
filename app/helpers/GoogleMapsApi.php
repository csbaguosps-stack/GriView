<?php
/* License: by cs.baguosps@gmail.com */
/**
 * GoogleMapsApi - Service untuk integrasi Google Places API & Import Data Ulasan
 */
require_once __DIR__ . '/../config/config.php';

class GoogleMapsApi {

    /**
     * Mengambil detail tempat dan ulasan dari Google Places API Details
     */
    public static function fetchPlaceReviews(string $apiKey, string $placeId): array {
        if (empty($apiKey) || empty($placeId)) {
            return [
                'success' => false,
                'message' => 'API Key dan Place ID Google Maps harus diisi.'
            ];
        }

        // 1. Coba endpoint Google Places Details (Legacy)
        $urlLegacy = "https://maps.googleapis.com/maps/api/place/details/json?" . http_build_query([
            'place_id' => $placeId,
            'fields' => 'name,formatted_address,rating,user_ratings_total,reviews',
            'language' => 'id',
            'key' => $apiKey
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlLegacy);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        // 2. Jika legacy belum diaktifkan atau gagal karena butuh Places API (New), coba endpoint v1 (New API)
        if (!$data || (isset($data['status']) && $data['status'] !== 'OK')) {
            $urlNew = "https://places.googleapis.com/v1/places/" . urlencode($placeId) . "?languageCode=id&key=" . urlencode($apiKey);
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $urlNew);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'X-Goog-FieldMask: id,displayName,formattedAddress,rating,userRatingCount,reviews',
                'Content-Type: application/json'
            ]);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            $responseNew = curl_exec($ch2);
            curl_close($ch2);

            $dataNew = json_decode($responseNew, true);
            if ($dataNew && !isset($dataNew['error']) && (isset($dataNew['displayName']) || isset($dataNew['reviews']))) {
                // Parse format Places API (New)
                $reviewsNew = [];
                if (!empty($dataNew['reviews'])) {
                    foreach ($dataNew['reviews'] as $rn) {
                        $author = $rn['authorAttribution']['displayName'] ?? 'Pengguna Google';
                        $authorPhoto = $rn['authorAttribution']['photoUri'] ?? null;
                        $authorUrl = $rn['authorAttribution']['uri'] ?? null;
                        $text = $rn['text']['text'] ?? $rn['originalText']['text'] ?? '';
                        $rating = (int)($rn['rating'] ?? 5);
                        $time = !empty($rn['publishTime']) ? date('Y-m-d H:i:s', strtotime($rn['publishTime'])) : date('Y-m-d H:i:s');
                        $sentiment = $rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative');

                        $reviewsNew[] = [
                            'google_review_id' => 'GNEW_' . md5($author . $time),
                            'place_id' => $placeId,
                            'place_name' => $dataNew['displayName']['text'] ?? DEFAULT_PLACE_NAME,
                            'author_name' => $author,
                            'author_photo_url' => $authorPhoto,
                            'author_url' => $authorUrl,
                            'rating' => $rating,
                            'review_text' => $text,
                            'word_count' => Review::countWords($text),
                            'review_time' => $time,
                            'sentiment' => $sentiment,
                            'is_local_guide' => 0,
                            'review_language' => 'id',
                            'owner_reply' => null,
                            'owner_reply_time' => null
                        ];
                    }
                }

                return [
                    'success' => true,
                    'place_name' => $dataNew['displayName']['text'] ?? DEFAULT_PLACE_NAME,
                    'place_address' => $dataNew['formattedAddress'] ?? '',
                    'overall_rating' => $dataNew['rating'] ?? null,
                    'total_ratings' => $dataNew['userRatingCount'] ?? count($reviewsNew),
                    'reviews' => $reviewsNew,
                    'count' => count($reviewsNew)
                ];
            }

            // Jika keduanya gagal, laporkan error
            return [
                'success' => false,
                'message' => 'Google API Error: ' . ($data['error_message'] ?? $data['status'] ?? $dataNew['error']['message'] ?? 'Periksa API Key dan Place ID Anda')
            ];
        }

        $result = $data['result'] ?? [];
        $reviews = [];
        if (!empty($result['reviews'])) {
            foreach ($result['reviews'] as $r) {
                $rating = (int)($r['rating'] ?? 5);
                $time = isset($r['time']) ? date('Y-m-d H:i:s', $r['time']) : date('Y-m-d H:i:s');
                $sentiment = $rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative');

                $revText = $r['text'] ?? '';
                $reviews[] = [
                    'google_review_id' => 'G_' . md5(($r['author_name'] ?? '') . ($r['time'] ?? time())),
                    'place_id' => $placeId,
                    'place_name' => $result['name'] ?? DEFAULT_PLACE_NAME,
                    'author_name' => $r['author_name'] ?? 'Pengguna Google',
                    'author_photo_url' => $r['profile_photo_url'] ?? null,
                    'author_url' => $r['author_url'] ?? null,
                    'rating' => $rating,
                    'review_text' => $revText,
                    'word_count' => Review::countWords($revText),
                    'review_time' => $time,
                    'sentiment' => $sentiment,
                    'is_local_guide' => 0,
                    'review_language' => $r['language'] ?? 'id',
                    'owner_reply' => null,
                    'owner_reply_time' => null
                ];
            }
        }

        return [
            'success' => true,
            'place_name' => $result['name'] ?? '',
            'place_address' => $result['formatted_address'] ?? '',
            'overall_rating' => $result['rating'] ?? null,
            'total_ratings' => $result['user_ratings_total'] ?? 0,
            'reviews' => $reviews,
            'count' => count($reviews)
        ];
    }

    /**
     * Mencari Place ID berdasarkan nama tempat menggunakan Find Place API
     */
    public static function findPlaceId(string $apiKey, string $query): array {
        if (empty($apiKey) || empty($query)) {
            return ['success' => false, 'message' => 'API Key dan Nama Tempat diperlukan.'];
        }

        $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json?" . http_build_query([
            'input' => $query,
            'inputtype' => 'textquery',
            'fields' => 'place_id,name,formatted_address',
            'key' => $apiKey
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data && isset($data['candidates'][0])) {
            return [
                'success' => true,
                'place_id' => $data['candidates'][0]['place_id'],
                'name' => $data['candidates'][0]['name'] ?? '',
                'address' => $data['candidates'][0]['formatted_address'] ?? ''
            ];
        }

        return ['success' => false, 'message' => $data['error_message'] ?? 'Tempat tidak ditemukan.'];
    }

    /**
     * Import reviews dari file JSON
     */
    public static function parseJsonFile(string $filePath): array {
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['success' => false, 'message' => 'Format file JSON tidak valid.'];
        }

        // Tangani format beragam: jika berupa array objek ulasan langsung atau dibungkus dalam field "reviews"
        $rawList = isset($data['reviews']) ? $data['reviews'] : $data;
        if (!is_array($rawList)) {
            return ['success' => false, 'message' => 'Daftar ulasan tidak ditemukan di dalam JSON.'];
        }

        $imported = [];
        foreach ($rawList as $item) {
            if (!isset($item['author_name']) && !isset($item['name']) && !isset($item['reviewer_name'])) {
                continue;
            }
            $authorName = $item['author_name'] ?? $item['name'] ?? $item['reviewer_name'] ?? 'Pengulas';
            $rating = (int)($item['rating'] ?? $item['stars'] ?? 5);
            $text = $item['review_text'] ?? $item['text'] ?? $item['comment'] ?? '';
            $timeRaw = $item['review_time'] ?? $item['time'] ?? $item['date'] ?? date('Y-m-d H:i:s');
            
            $timestamp = is_numeric($timeRaw) ? $timeRaw : strtotime($timeRaw);
            $reviewTime = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');

            $imported[] = [
                'google_review_id' => $item['google_review_id'] ?? $item['id'] ?? ('JSON_' . md5($authorName . $reviewTime)),
                'author_name' => $authorName,
                'author_photo_url' => $item['author_photo_url'] ?? $item['profile_photo_url'] ?? null,
                'author_url' => $item['author_url'] ?? null,
                'rating' => $rating,
                'review_text' => $text,
                'review_time' => $reviewTime,
                'sentiment' => $item['sentiment'] ?? ($rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative')),
                'is_local_guide' => !empty($item['is_local_guide']) ? 1 : 0,
                'owner_reply' => $item['owner_reply'] ?? $item['reply'] ?? null,
                'owner_reply_time' => $item['owner_reply_time'] ?? null
            ];
        }

        return ['success' => true, 'reviews' => $imported, 'count' => count($imported)];
    }
}
