<?php
/**
 * ProductImageUpdater v3.0 - SmartMatch Token Weighted
 * ✅ Lấy toàn bộ ảnh sản phẩm trên web (không theo cấu trúc)
 * ✅ Tự động ghép với danh sách sản phẩm API theo tên
 * ✅ Ghi log + cache ảnh sản phẩm
 */

class ProductImageUpdater
{
    // --- CẤU HÌNH ---
    private $targetUrl = 'https://1dg.me/products/';
    private $apiUrl = 'https://1dg.me/api/v2';
    private $apiKey = '6ac97250c4f4d408e086095e5ddd02ff';
    private $defaultImg = 'https://1dg.me/assets/media/logo_square.png?1731466820';
    private $updateInterval = 3600; // 1 giờ

    // --- HỆ THỐNG ---
    private $cacheFile;
    private $lockFile;
    private $logFile;

    // ===============================
    // === CHUẨN HÓA TOKEN ===
    // ===============================
    private function normalizeTokens($str)
    {
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]+/', ' ', $str);
        return array_filter(explode(' ', trim($str)));
    }

    // ===============================
    // === THUẬT TOÁN SMART MATCH ===
    // ===============================
    private function smartMatchImage($productName, $images)
    {
        $weights = [
            'sora' => 10, 'veo2' => 10, 'veo3' => 10, 'hailuo' => 10, 'kling' => 10,
            'youtube' => 10, 'capcut' => 10, 'canva' => 10, 'chatgpt' => 10, 'googleai' => 10,
            'portrait' => 3, '916' => 3, '169' => 3,
            '720p' => 4, '1080p' => 4, '2k' => 4, '768p' => 4,
            '5s' => 5, '6s' => 5, '10s' => 5,
            'fast' => 3, 'quality' => 3, 'pro' => 3, 'ultra' => 3,
            '1thang' => 3, '3thang' => 3, '6thang' => 3, '12thang' => 3, '1nam' => 3
        ];

        $productTokens = $this->normalizeTokens($productName);
        $best = ['url' => $this->defaultImg, 'score' => 0];

        foreach ($images as $img) {
            $imgTokens = $this->normalizeTokens(basename($img));
            $score = 0;
            foreach ($productTokens as $p) {
                foreach ($imgTokens as $i) {
                    if ($p === $i) {
                        $score += $weights[$p] ?? 2;
                    }
                }
            }
            if ($score > $best['score']) {
                $best = ['url' => $img, 'score' => $score];
            }
        }
        return $best;
    }

    // ===============================
    // === KHỞI TẠO ===
    // ===============================
    public function __construct()
    {
        $this->cacheFile = __DIR__ . '/productImages.php';
        $this->lockFile = __DIR__ . '/productImages.lock';
        $this->logFile = __DIR__ . '/image_update.log';

        if (!file_exists($this->cacheFile)) {
            $this->log("🆕 Cache file not found, initializing...");
            $this->initCacheFile();
        }
    }

    // ===============================
    // === GHI LOG ===
    // ===============================
    private function log($msg)
    {
        $time = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$time] $msg\n", FILE_APPEND);
        if (php_sapi_name() === 'cli') echo "$msg\n";
    }

    // ===============================
    // === KHỞI TẠO CACHE ===
    // ===============================
    private function initCacheFile()
    {
        $default = [
            'default' => $this->defaultImg,
            'logo' => 'https://1dg.me/assets/media/logo.png?1731466820',
            'logo_square' => $this->defaultImg,
        ];
        $code = "<?php\n// Initialized: " . date('Y-m-d H:i:s') . "\nreturn " . var_export($default, true) . ";\n";
        file_put_contents($this->cacheFile, $code);
    }

    // ===============================
    // === LẤY CACHE HIỆN TẠI ===
    // ===============================
    public function getImages()
    {
        if (!file_exists($this->cacheFile)) $this->initCacheFile();
        $images = @include $this->cacheFile;
        return is_array($images) ? $images : ['default' => $this->defaultImg];
    }

    // ===============================
    // === KIỂM TRA CÓ CẦN UPDATE KHÔNG ===
    // ===============================
    public function needsUpdate()
    {
        if (!file_exists($this->cacheFile)) return true;
        $lastModified = @filemtime($this->cacheFile) ?: 0;
        return (time() - $lastModified) > $this->updateInterval;
    }

    // ===============================
    // === CURL FETCH ===
    // ===============================
    private function fetch($url, $post = null)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15
        ]);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $code !== 200) {
            $this->log("❌ CURL Error: $err | HTTP: $code");
            return false;
        }
        return $res;
    }

    // ===============================
    // === LẤY SẢN PHẨM TỪ API ===
    // ===============================
    private function fetchProducts()
    {
        $this->log("🔍 Lấy danh sách sản phẩm từ API...");
        $res = $this->fetch($this->apiUrl, ['key' => $this->apiKey, 'action' => 'products']);
        return $res ? json_decode($res, true) : [];
    }

    // ===============================
    // === CẬP NHẬT ẢNH ===
    // ===============================
    public function updateImages()
    {
        $this->log("=== 🚀 Bắt đầu quá trình cập nhật ảnh (v3.0) ===");

        if (file_exists($this->lockFile) && (time() - filemtime($this->lockFile)) < 300) {
            $this->log("⚠️ Đang có tiến trình khác chạy, hủy bỏ.");
            return;
        }
        touch($this->lockFile);

        try {
            // 🧩 1. Lấy toàn bộ ảnh
            $this->log("🕸 Đang tải HTML từ {$this->targetUrl}...");
            $html = $this->fetch($this->targetUrl);
            if (!$html) throw new Exception("Không tải được nội dung trang.");

            preg_match_all('/https?:\/\/[^\s"\'<>]+\.(jpg|jpeg|png|webp|gif)/i', $html, $m);
            $imgs = array_values(array_unique(array_filter($m[0], fn($u) =>
                !preg_match('/logo|icon|favicon/i', $u)
            )));
            if (empty($imgs)) throw new Exception("Không tìm thấy ảnh nào.");

            $this->log("✅ Đã lấy được " . count($imgs) . " ảnh từ website.");

            // 🧩 2. Lấy danh sách sản phẩm
            $products = $this->fetchProducts();
            if (empty($products)) throw new Exception("Không lấy được danh sách sản phẩm.");
            $this->log("✅ Có " . count($products) . " sản phẩm từ API.");

            // 🧠 3. Ghép ảnh
            $map = [];
            foreach ($products as $p) {
                $id = $p['product'];
                $name = $p['name'] ?? 'Unknown';
                $best = $this->smartMatchImage($name, $imgs);
                $map[$id] = $best['url'];
                $icon = $best['score'] >= 8 ? '✅' : ($best['score'] >= 4 ? '⚠️' : '❌');
                $this->log("$icon [{$id}] {$name} → " . basename($best['url']) . " (score: {$best['score']})");
            }

            // 🧾 4. Lưu kết quả
            $map['default'] = $this->defaultImg;
            $map['logo'] = 'https://1dg.me/assets/media/logo.png?1731466820';
            $map['logo_square'] = $this->defaultImg;

            ksort($map, SORT_NUMERIC);
            $output = "<?php\n// Auto-generated: " . date('Y-m-d H:i:s') .
                "\nreturn " . var_export($map, true) . ";\n";
            file_put_contents($this->cacheFile, $output);

            $this->log("✅ Đã lưu xong: {$this->cacheFile}");
            $this->log("📦 Tổng cộng: " . count($map) . " mục.");
        } catch (Exception $e) {
            $this->log("❌ Lỗi: " . $e->getMessage());
        } finally {
            if (file_exists($this->lockFile)) unlink($this->lockFile);
            $this->log("=== 🏁 Hoàn tất cập nhật ảnh ===");
        }
    }
}
