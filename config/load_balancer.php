<?php
class LoadBalancer {
    private $conn;
    private $ai33_weight = 80;
    private $ai84_weight = 20;
    
    const AI33_ENDPOINT = 'https://api.ai33.pro';
    const AI84_ENDPOINT = 'https://api.ai84.pro';
    const KINGCONG_ENDPOINT = 'https://app.maziao.com/api';
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function selectServer() {
        $ai33_status = $this->getServerStatus('ai33');
        $ai84_status = $this->getServerStatus('ai84');
        
        // 🔥 ĐÃ SỬA: Đổi writeToLog thành error_log (Hàm chuẩn của PHP)
        error_log("🎲 LOAD BALANCER DEBUG:");
        error_log("AI33 Maintenance: " . ($ai33_status['is_maintenance'] ? 'YES' : 'NO'));
        error_log("AI84 Maintenance: " . ($ai84_status['is_maintenance'] ? 'YES' : 'NO'));
        
        // Cả 2 bảo trì
        if ($ai33_status['is_maintenance'] && $ai84_status['is_maintenance']) {
            error_log("❌ Both servers in maintenance");
            return null;
        }
        
        // ai33 bảo trì → dùng ai84
        if ($ai33_status['is_maintenance']) {
            error_log("⚠️ AI33 maintenance → Force AI84");
            return 'ai84'; 
        }
        
        // ai84 bảo trì → dùng ai33
        if ($ai84_status['is_maintenance']) {
            error_log("⚠️ AI84 maintenance → Force AI33");
            return 'ai33';
        }
        
        // Cả 2 đều ok → phân bổ theo tỷ lệ
        $rand = mt_rand(1, 100);
        $selected = ($rand <= $this->ai33_weight) ? 'ai33' : 'ai84';
        
        error_log("✅ Random: $rand/100 | Weight: {$this->ai33_weight}/30 → Selected: $selected");
        
        return $selected;
    }
    
    /**
     * Lấy trạng thái server
     */
    public function getServerStatus($server_name) {
        $stmt = $this->conn->prepare("
            SELECT is_maintenance, maintenance_note 
            FROM server_status 
            WHERE server_name = ?
        ");
        $stmt->bind_param("s", $server_name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ?: ['is_maintenance' => 0, 'maintenance_note' => null];
    }
    
/**
 * 🎯 HÀM MỚI: Chọn server PHÙ HỢP dựa trên service
 * Thay thế selectServer() cũ
 */
public function getAvailableServer($service_type = null) {
    error_log("🎯 getAvailableServer() | Service: $service_type");

    // 🎯 KINGCONG: Server riêng biệt (không dùng ai33/ai84)
    if ($service_type === 'kingcong') {
        $kingcong_status = $this->getServerStatus('kingcong');
        if ($kingcong_status['is_maintenance']) {
            error_log("❌ KingCong server in maintenance");
            return null;
        }
        $has_key = $this->hasKeyForService('kingcong', 'kingcong');
        if (!$has_key) {
            error_log("❌ No KingCong keys available");
            return null;
        }
        error_log("✅ Selected: KingCong");
        return 'kingcong';
    }

    // Bước 1: Lấy trạng thái server (cho elevenlabs/minimax)
    $ai33_status = $this->getServerStatus('ai33');
    $ai84_status = $this->getServerStatus('ai84');
    
    $ai33_maintenance = $ai33_status['is_maintenance'];
    $ai84_maintenance = $ai84_status['is_maintenance'];
    
    // Bước 2: Check key availability (nếu có service cụ thể)
    $ai33_has_key = true;
    $ai84_has_key = true;
    
    if ($service_type) {
        $ai33_has_key = $this->hasKeyForService('ai33', $service_type);
        $ai84_has_key = $this->hasKeyForService('ai84', $service_type);
        
        error_log("AI33: Maintenance=" . ($ai33_maintenance ? 'YES' : 'NO') . 
                  ", Has $service_type=" . ($ai33_has_key ? 'YES' : 'NO'));
        error_log("AI84: Maintenance=" . ($ai84_maintenance ? 'YES' : 'NO') . 
                  ", Has $service_type=" . ($ai84_has_key ? 'YES' : 'NO'));
    }
    
    // Bước 3: Tính điểm khả dụng
    $ai33_available = !$ai33_maintenance && $ai33_has_key;
    $ai84_available = !$ai84_maintenance && $ai84_has_key;
    
    // Bước 4: Logic chọn server
    
    // Cả 2 đều unavailable
    if (!$ai33_available && !$ai84_available) {
        error_log("❌ No available server for service: $service_type");
        return null;
    }
    
    // Chỉ AI33 available
    if ($ai33_available && !$ai84_available) {
        error_log("✅ Selected: AI33 (only available)");
        return 'ai33';
    }
    
    // Chỉ AI84 available
    if (!$ai33_available && $ai84_available) {
        error_log("✅ Selected: AI84 (only available)");
        return 'ai84';
    }
    
    // Cả 2 đều available → Phân bổ theo tỷ lệ
    $rand = mt_rand(1, 100);
    $selected = ($rand <= $this->ai33_weight) ? 'ai33' : 'ai84';
    
    error_log("✅ Both available → Random: $rand/100 | Weight: {$this->ai33_weight}/20 → Selected: $selected");
    
    return $selected;
}

/**
 * 🔍 Check xem server có key cho service không
 */
private function hasKeyForService($server_type, $service_type) {
    $sql = "
        SELECT COUNT(*) as count
        FROM api_keys_pool 
        WHERE server_type = ? 
        AND is_active = 1
    ";
    
    // Thêm điều kiện service
    if ($service_type === 'elevenlabs') {
        $sql .= " AND elevenlabs_enabled = 1";
    } elseif ($service_type === 'minimax') {
        $sql .= " AND minimax_enabled = 1";
    } elseif ($service_type === 'kingcong') {
        $sql .= " AND kingcong_enabled = 1";
    }

    $sql .= " LIMIT 1";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $server_type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] > 0;
}

/**
 * 🔄 CẬP NHẬT getAvailableApiKey() - HỖ TRỢ KIỂM TRA CREDITS
 */
public function getAvailableApiKey($server_type, $service_type = null, $required_credits = 0) {
    error_log("🔑 getAvailableApiKey() | Server: $server_type | Service: $service_type | Required: $required_credits");

    // Kiểm tra server có bảo trì không (double-check)
    $server_status = $this->getServerStatus($server_type);
    if ($server_status['is_maintenance']) {
        error_log("❌ Server $server_type is in maintenance");
        return null;
    }

    // Làm sạch request cũ
    $this->cleanupOldRequests($server_type);

    // Build query - LẤY TẤT CẢ KEYS, SẮP XẾP THEO CREDITS GIẢM DẦN
    $sql = "
        SELECT id, api_key, current_load, max_concurrent, requests_per_minute, last_request_time,
               COALESCE(cached_credits, 999999999) as cached_credits,
               credits_updated_at
        FROM api_keys_pool
        WHERE server_type = ?
        AND is_active = 1
    ";

    // Thêm điều kiện service nếu có
    if ($service_type === 'elevenlabs') {
        $sql .= " AND elevenlabs_enabled = 1";
    } elseif ($service_type === 'minimax') {
        $sql .= " AND minimax_enabled = 1";
    } elseif ($service_type === 'kingcong') {
        $sql .= " AND kingcong_enabled = 1";
    }

    // 🔥 SẮP XẾP THEO CREDITS GIẢM DẦN (key nhiều credits nhất lên đầu)
    $sql .= " ORDER BY cached_credits DESC, current_load ASC, last_request_time ASC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $server_type);
    $stmt->execute();
    $all_keys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($all_keys)) {
        error_log("❌ No available key on $server_type for $service_type");
        return null;
    }

    // 🔥 DUYỆT QUA TỪNG KEY, KIỂM TRA CREDITS
    foreach ($all_keys as $key_data) {
        // Kiểm tra rate limit
        if ($this->isRateLimited($key_data)) {
            error_log("⏳ Key ID {$key_data['id']} rate limited, skip...");
            continue;
        }

        // Kiểm tra concurrent limit
        if ($key_data['current_load'] >= $key_data['max_concurrent']) {
            error_log("⏳ Key ID {$key_data['id']} at max load, skip...");
            continue;
        }

        // 🔥 KIỂM TRA CREDITS NẾU CẦN
        if ($required_credits > 0) {
            // Refresh credits nếu cache quá cũ (> 5 phút)
            $cache_age = time() - strtotime($key_data['credits_updated_at'] ?? '2000-01-01');
            if ($cache_age > 300) {
                $fresh_credits = $this->fetchAndCacheCredits($key_data['id'], $key_data['api_key'], $server_type);
                if ($fresh_credits !== null) {
                    $key_data['cached_credits'] = $fresh_credits;
                }
            }

            // Check đủ credits không
            if ($key_data['cached_credits'] < $required_credits) {
                error_log("💰 Key ID {$key_data['id']} không đủ credits ({$key_data['cached_credits']} < $required_credits), skip...");
                continue;
            }
        }

        error_log("✅ Selected Key ID {$key_data['id']} | Credits: {$key_data['cached_credits']}");
        return $key_data;
    }

    error_log("❌ Không có key nào đủ credits ($required_credits) trên $server_type");
    return null;
}

/**
 * 🔥 LẤY CREDITS TỪ API VÀ CACHE VÀO DATABASE
 */
private function fetchAndCacheCredits($key_id, $api_key, $server_type) {
    $credits = null;
    $url = '';
    $headers = [];

    try {
        if ($server_type === 'kingcong') {
            $url = 'https://app.maziao.com/api/auth/me';
            $headers = [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ];
        } elseif ($server_type === 'ai33') {
            $url = 'https://api.ai33.pro/v1/credits';
            $headers = [
                'xi-api-key: ' . $api_key,
                'Content-Type: application/json'
            ];
        } elseif ($server_type === 'ai84') {
            $url = 'https://api.ai84.pro/v1/credits';
            $headers = [
                'xi-api-key: ' . $api_key,
                'Content-Type: application/json'
            ];
        } else {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);

            if ($server_type === 'kingcong') {
                $credits = $data['data']['credits'] ?? null;
            } else {
                $credits = $data['credits'] ?? null;
            }
        }

        if ($credits !== null) {
            $stmt = $this->conn->prepare("
                UPDATE api_keys_pool
                SET cached_credits = ?, credits_updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("di", $credits, $key_id);
            $stmt->execute();
            $stmt->close();

            error_log("Cached credits for Key ID $key_id: $credits");
        }

    } catch (Exception $e) {
        error_log("Error fetching credits for Key ID $key_id: " . $e->getMessage());
    }

    return $credits;
}

/**
 * 🔥 REFRESH TẤT CẢ CREDITS (CHẠY BẰNG CRON)
 */
public function refreshAllCredits() {
    $stmt = $this->conn->prepare("
        SELECT id, api_key, server_type
        FROM api_keys_pool
        WHERE is_active = 1
    ");
    $stmt->execute();
    $keys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo "Starting refresh for " . count($keys) . " keys...\n\n";

    $updated = 0;
    foreach ($keys as $key) {
        echo "Processing Key ID: {$key['id']}...\n";
        $credits = $this->fetchAndCacheCreditsDebug($key['id'], $key['api_key'], $key['server_type']);
        if ($credits !== null) $updated++;
        usleep(200000);
    }

    return $updated;
}

/**
 * 🔥 VERSION DEBUG - IN RA CHI TIẾT
 */
public function fetchAndCacheCreditsDebug($key_id, $api_key, $server_type) {
    $credits = null;
    $url = '';
    $headers = [];

    try {
        if ($server_type === 'kingcong') {
            // Maziao: Bearer token, endpoint /api/auth/me
            $url = 'https://app.maziao.com/api/auth/me';
            $headers = [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ];
        } elseif ($server_type === 'ai33') {
            // AI33: xi-api-key header, endpoint /v1/credits
            $url = 'https://api.ai33.pro/v1/credits';
            $headers = [
                'xi-api-key: ' . $api_key,
                'Content-Type: application/json'
            ];
        } elseif ($server_type === 'ai84') {
            // AI84: xi-api-key header, endpoint /v1/credits
            $url = 'https://api.ai84.pro/v1/credits';
            $headers = [
                'xi-api-key: ' . $api_key,
                'Content-Type: application/json'
            ];
        } else {
            echo "   [!] Unknown server_type: $server_type\n";
            return null;
        }

        echo "   [Key $key_id] $server_type -> $url\n";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        echo "      HTTP: $http_code\n";
        if ($curl_error) echo "      cURL Error: $curl_error\n";
        echo "      Response: " . substr($response, 0, 300) . "\n";

        if ($http_code === 200) {
            $data = json_decode($response, true);

            // Parse credits theo từng provider
            if ($server_type === 'kingcong') {
                // Maziao: data.credits
                $credits = $data['data']['credits'] ?? null;
            } else {
                // AI33/AI84: credits trực tiếp
                $credits = $data['credits'] ?? null;
            }

            echo "      Credits: " . ($credits ?? 'NULL') . "\n";
        }

        // Cập nhật DB nếu có credits
        if ($credits !== null) {
            $stmt = $this->conn->prepare("
                UPDATE api_keys_pool
                SET cached_credits = ?, credits_updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("di", $credits, $key_id);
            $stmt->execute();
            $stmt->close();
            echo "      -> Saved!\n";
        }

    } catch (Exception $e) {
        echo "      Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
    return $credits;
}
    
    /**
     * Lấy key tiếp theo
     */
    private function getNextAvailableKey($server_type, $service_type, $exclude_id) {
        $sql = "
            SELECT id, api_key, current_load, max_concurrent, requests_per_minute, last_request_time
            FROM api_keys_pool 
            WHERE server_type = ? 
            AND is_active = 1 
            AND id != ?
        ";
        
        if ($service_type === 'elevenlabs') {
            $sql .= " AND elevenlabs_enabled = 1";
        } elseif ($service_type === 'minimax') {
            $sql .= " AND minimax_enabled = 1";
        } elseif ($service_type === 'kingcong') {
            $sql .= " AND kingcong_enabled = 1";
        }

        $sql .= " ORDER BY current_load ASC, last_request_time ASC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $server_type, $exclude_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            return null;
        }

        if ($this->isRateLimited($result) || $result['current_load'] >= $result['max_concurrent']) {
            return null;
        }

        return $result;
    }
    
    /**
     * Đẩy task vào hàng chờ
     */
    public function addToQueue($task_id, $user_id, $service_type, $task_data = null) {
        $task_data_json = $task_data ? json_encode($task_data) : null;
        
        $stmt = $this->conn->prepare("
            INSERT INTO task_queue (task_id, user_id, service_type, task_data, status)
            VALUES (?, ?, ?, ?, 'waiting')
            ON DUPLICATE KEY UPDATE status = 'waiting'
        ");
        $stmt->bind_param("siss", $task_id, $user_id, $service_type, $task_data_json);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
/**
 * Kiểm tra có key nào khả dụng không (PHIÊN BẢN MỚI)
 */
public function hasAvailableKey($service_type) {
    $server = $this->getAvailableServer($service_type); // ← Dùng hàm mới
    
    if (!$server) {
        return false;
    }
    
    $api_key = $this->getAvailableApiKey($server, $service_type);
    
    return $api_key !== null;
}
    
    /**
     * Đánh dấu server bảo trì
     */
    public function setServerMaintenance($server_name, $is_maintenance, $note = null) {
        $stmt = $this->conn->prepare("
            UPDATE server_status 
            SET is_maintenance = ?,
                maintenance_note = ?,
                started_at = IF(? = 1, NOW(), started_at),
                ended_at = IF(? = 0, NOW(), NULL)
            WHERE server_name = ?
        ");
        $stmt->bind_param("issis", $is_maintenance, $note, $is_maintenance, $is_maintenance, $server_name);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Bật/tắt service cho API key
     */
    public function toggleServiceForKey($api_key_id, $service_type, $enabled) {
        $service_column = $service_type . '_enabled';
        
        $stmt = $this->conn->prepare("
            UPDATE api_keys_pool 
            SET {$service_column} = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $enabled, $api_key_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // ===== CÁC HÀM CŨ (GIỮ NGUYÊN) =====
    
    private function isRateLimited($key_data) {
        if (!$key_data['last_request_time']) return false;
        
        $last_time = strtotime($key_data['last_request_time']);
        $now = time();
        $diff = $now - $last_time;
        
        if ($diff < 60) {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM task_distribution 
                WHERE api_key_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            
            $stmt->bind_param("i", $key_data['id']);
            $stmt->execute();
            $count_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            return $count_result['count'] >= $key_data['requests_per_minute'];
        }
        
        return false;
    }
    
    public function increaseLoad($api_key_id) {
        $stmt = $this->conn->prepare("
            UPDATE api_keys_pool 
            SET current_load = current_load + 1, last_request_time = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $api_key_id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function decreaseLoad($api_key_id) {
        $stmt = $this->conn->prepare("
            UPDATE api_keys_pool 
            SET current_load = GREATEST(0, current_load - 1)
            WHERE id = ?
        ");
        $stmt->bind_param("i", $api_key_id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function getApiKeyById($api_key_id) {
        $stmt = $this->conn->prepare("
            SELECT api_key, server_type 
            FROM api_keys_pool 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->bind_param("i", $api_key_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
    
    private function cleanupOldRequests($server_type) {
        $stmt = $this->conn->prepare("
            UPDATE api_keys_pool 
            SET current_load = (
                SELECT COUNT(*) 
                FROM task_distribution 
                WHERE api_key_id = api_keys_pool.id 
                AND status IN ('pending', 'processing')
            )
            WHERE server_type = ?
        ");
        $stmt->bind_param("s", $server_type);
        $stmt->execute();
        $stmt->close();
    }
    
    public function recordTaskDistribution($task_id, $user_id, $server_type, $api_key_id) {
        $stmt = $this->conn->prepare("
            INSERT INTO task_distribution (task_id, user_id, server_type, api_key_id, status)
            VALUES (?, ?, ?, ?, 'processing')
        ");
        $stmt->bind_param("sisi", $task_id, $user_id, $server_type, $api_key_id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function updateTaskStatus($task_id, $status) {
        $completed_at = ($status === 'done' || $status === 'failed') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $this->conn->prepare("
            UPDATE task_distribution 
            SET status = ?, completed_at = ?
            WHERE task_id = ?
        ");
        $stmt->bind_param("sss", $status, $completed_at, $task_id);
        $stmt->execute();
        $stmt->close();
        
        if ($status === 'done' || $status === 'failed') {
            $stmt = $this->conn->prepare("
                SELECT api_key_id FROM task_distribution WHERE task_id = ?
            ");
            $stmt->bind_param("s", $task_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($result) {
                $this->decreaseLoad($result['api_key_id']);
            }
        }
    }
    
    public function getEndpoint($server_type) {
        if ($server_type === 'ai33') {
            return self::AI33_ENDPOINT;
        } elseif ($server_type === 'ai84') {
            return self::AI84_ENDPOINT;
        } elseif ($server_type === 'kingcong') {
            return self::KINGCONG_ENDPOINT;
        }
        return self::AI33_ENDPOINT; // fallback
    }
    
    public function getSystemStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                server_type,
                COUNT(*) as total_keys,
                SUM(is_active) as active_keys,
                SUM(elevenlabs_enabled) as elevenlabs_keys,
                SUM(minimax_enabled) as minimax_keys,
                SUM(current_load) as total_load,
                AVG(current_load) as avg_load
            FROM api_keys_pool
            GROUP BY server_type
        ");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }
}
?>