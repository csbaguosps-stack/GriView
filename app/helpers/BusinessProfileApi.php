<?php
/* License: by cs.baguosps@gmail.com */
/**
 * Server-side OAuth client for Google Business Profile reviews.
 */
class BusinessProfileApi {
    private const TOKEN_PATH = __DIR__ . '/../data/business_profile_token.json';
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/business.manage';

    public static function isConfigured(): bool {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function isConnected(): bool {
        $token = self::readToken();
        return !empty($token['refresh_token']);
    }

    public static function authorizationUrl(string $redirectUri, string $state): string {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => self::clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    public static function connect(string $code, string $redirectUri): void {
        $response = self::postForm(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (empty($response['refresh_token'])) {
            if (!self::isConnected()) {
                throw new RuntimeException('Google tidak mengirim refresh token. Cabut izin aplikasi di akun Google, lalu hubungkan kembali.');
            }
            return;
        }

        self::writeToken([
            'refresh_token' => $response['refresh_token'],
            'created_at' => time(),
        ]);
    }

    public static function disconnect(): void {
        if (is_file(self::TOKEN_PATH)) {
            @unlink(self::TOKEN_PATH);
        }
    }

    /** Return all reviews for the configured Place ID using Business Profile pagination. */
    public static function fetchAllReviews(string $placeId): array {
        if ($placeId === '') {
            throw new RuntimeException('Place ID belum diisi pada Pengaturan.');
        }

        $accessToken = self::accessToken();
        $accounts = self::getAllPages('https://mybusinessaccountmanagement.googleapis.com/v1/accounts', $accessToken, 'accounts');
        $matchedLocation = null;

        foreach ($accounts as $account) {
            $accountName = $account['name'] ?? '';
            if ($accountName === '') {
                continue;
            }

            $locations = self::getAllPages(
                'https://mybusinessbusinessinformation.googleapis.com/v1/' . $accountName . '/locations',
                $accessToken,
                'locations',
                ['readMask' => 'name,title,storeCode,metadata']
            );

            foreach ($locations as $location) {
                if (($location['metadata']['placeId'] ?? '') === $placeId) {
                    $matchedLocation = $location;
                    break 2;
                }
            }
        }

        if (!$matchedLocation) {
            throw new RuntimeException('Place ID tidak ditemukan pada akun Google yang terhubung. Pastikan akun ini adalah pemilik/pengelola lokasi yang terverifikasi.');
        }

        $locationName = $matchedLocation['name'] ?? '';
        if ($locationName === '') {
            throw new RuntimeException('Google Business Profile mengembalikan lokasi tanpa resource name.');
        }

        $reviews = self::getAllPages(
            'https://mybusiness.googleapis.com/v4/' . $locationName . '/reviews',
            $accessToken,
            'reviews',
            ['pageSize' => 50, 'orderBy' => 'updateTime desc']
        );

        return [
            'place_name' => $matchedLocation['title'] ?? 'Google Business Profile',
            'place_id' => $placeId,
            'total_review_count' => count($reviews),
            'reviews' => array_map(static function (array $review) use ($placeId, $matchedLocation): array {
                $ratingMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
                $rating = $ratingMap[$review['starRating'] ?? 'FIVE'] ?? 5;
                $reviewer = $review['reviewer'] ?? [];
                $reviewTime = $review['createTime'] ?? $review['updateTime'] ?? date(DATE_ATOM);

                return [
                    'google_review_id' => $review['reviewId'] ?? hash('sha256', json_encode($review)),
                    'place_id' => $placeId,
                    'place_name' => $matchedLocation['title'] ?? 'Google Business Profile',
                    'author_name' => $reviewer['displayName'] ?? 'Pengguna Google',
                    'author_photo_url' => $reviewer['profilePhotoUrl'] ?? null,
                    'rating' => $rating,
                    'review_text' => $review['comment'] ?? '',
                    'review_time' => date('Y-m-d H:i:s', strtotime($reviewTime) ?: time()),
                    'sentiment' => $rating >= 4 ? 'positive' : ($rating === 3 ? 'neutral' : 'negative'),
                    'is_local_guide' => 0,
                    'review_language' => 'id',
                    'owner_reply' => $review['reviewReply']['comment'] ?? null,
                    'owner_reply_time' => !empty($review['reviewReply']['updateTime'])
                        ? date('Y-m-d H:i:s', strtotime($review['reviewReply']['updateTime']) ?: time())
                        : null,
                ];
            }, $reviews),
        ];
    }

    private static function getAllPages(string $url, string $accessToken, string $resultKey, array $params = []): array {
        $all = [];
        $pageToken = null;
        $seenTokens = [];

        do {
            $query = $params;
            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }
            $pageUrl = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
            $page = self::request($pageUrl, $accessToken);
            $all = array_merge($all, $page[$resultKey] ?? []);
            $nextToken = $page['nextPageToken'] ?? null;
            if (!$nextToken || isset($seenTokens[$nextToken])) {
                break;
            }
            $seenTokens[$nextToken] = true;
            $pageToken = $nextToken;
        } while (true);

        return $all;
    }

    private static function accessToken(): string {
        $token = self::readToken();
        if (empty($token['refresh_token'])) {
            throw new RuntimeException('Google Business Profile belum terhubung. Hubungkan akun Google terlebih dahulu.');
        }

        $response = self::postForm(self::TOKEN_URL, [
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Gagal memperbarui token Google: ' . ($response['error_description'] ?? $response['error'] ?? 'respons token tidak valid.'));
        }
        return $response['access_token'];
    }

    private static function request(string $url, string $accessToken): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif di server.');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('Koneksi Google Business Profile gagal: ' . $error);
        }
        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            $message = $data['error']['message'] ?? 'Respons Google tidak valid.';
            throw new RuntimeException("Google Business Profile API ({$status}): {$message}");
        }
        return $data;
    }

    private static function postForm(string $url, array $data): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif di server.');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('Koneksi OAuth Google gagal: ' . $error);
        }
        $response = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($response)) {
            $message = $response['error_description'] ?? $response['error'] ?? 'Respons OAuth tidak valid.';
            throw new RuntimeException("OAuth Google ({$status}): {$message}");
        }
        return $response;
    }

    private static function clientId(): string {
        return trim((string)getenv('GRIVIEW_GOOGLE_OAUTH_CLIENT_ID'));
    }

    private static function clientSecret(): string {
        return trim((string)getenv('GRIVIEW_GOOGLE_OAUTH_CLIENT_SECRET'));
    }

    private static function readToken(): array {
        if (!is_file(self::TOKEN_PATH)) {
            return [];
        }
        $token = json_decode((string)file_get_contents(self::TOKEN_PATH), true);
        return is_array($token) ? $token : [];
    }

    private static function writeToken(array $token): void {
        $directory = dirname(self::TOKEN_PATH);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('Folder app/data tidak dapat ditulis untuk menyimpan token OAuth.');
        }
        $temporaryPath = self::TOKEN_PATH . '.tmp';
        $written = file_put_contents(
            $temporaryPath,
            json_encode($token, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        if ($written === false || !rename($temporaryPath, self::TOKEN_PATH)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Token OAuth gagal disimpan. Periksa izin folder app/data.');
        }
        @chmod(self::TOKEN_PATH, 0600);
    }
}