<?php
// File: /config/remember_me.php

class RememberMe {
    private $mysqli;
    private $cookie_name = 'remember_ai84';
    private $cookie_lifetime = 2592000; // 30 ngày
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    // ================================================
    // TẠO REMEMBER TOKEN (KHI ĐĂNG NHẬP)
    // ================================================
    public function createToken($user_id) {
        // Tạo selector và validator ngẫu nhiên
        $selector = bin2hex(random_bytes(16)); // 32 ký tự
        $validator = bin2hex(random_bytes(32)); // 64 ký tự
        
        // Hash validator trước khi lưu DB (bảo mật)
        $token_hash = hash('sha256', $validator);
        
        // Thời gian hết hạn
        $expires_at = date('Y-m-d H:i:s', time() + $this->cookie_lifetime);
        
        // Xóa token cũ của user này (nếu có)
        $stmt_delete = $this->mysqli->prepare("DELETE FROM `remember_tokens` WHERE `user_id` = ?");
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // Lưu token mới vào DB
        $stmt = $this->mysqli->prepare(
            "INSERT INTO `remember_tokens` (`user_id`, `selector`, `token`, `expires_at`) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isss", $user_id, $selector, $token_hash, $expires_at);
        
        if ($stmt->execute()) {
            // Gửi cookie về browser (selector:validator)
            $cookie_value = $selector . ':' . $validator;
            
            setcookie(
                $this->cookie_name,
                $cookie_value,
                [
                    'expires' => time() + $this->cookie_lifetime,
                    'path' => '/',
                    'domain' => '', // Auto-detect domain
                    'secure' => true, // HTTPS only
                    'httponly' => true, // Chặn JS đọc cookie
                    'samesite' => 'Lax' // CSRF protection
                ]
            );
            
            $stmt->close();
            return true;
        }
        
        $stmt->close();
        return false;
    }
    
    // ================================================
    // XÁC THỰC REMEMBER TOKEN (KHI VÀO TRANG)
    // ================================================
    public function validateToken() {
        // Kiểm tra cookie có tồn tại không
        if (!isset($_COOKIE[$this->cookie_name])) {
            return false;
        }
        
        $cookie_value = $_COOKIE[$this->cookie_name];
        
        // Tách selector và validator
        $parts = explode(':', $cookie_value);
        if (count($parts) !== 2) {
            $this->deleteToken();
            return false;
        }
        
        list($selector, $validator) = $parts;
        
        // Lấy token từ DB theo selector
        $stmt = $this->mysqli->prepare(
            "SELECT `user_id`, `token`, `expires_at` 
             FROM `remember_tokens` 
             WHERE `selector` = ? 
             LIMIT 1"
        );
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $this->deleteToken();
            return false;
        }
        
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // Kiểm tra hết hạn
        if (strtotime($row['expires_at']) < time()) {
            $this->deleteTokenBySelector($selector);
            $this->deleteToken();
            return false;
        }
        
        // Verify token hash
        $token_hash = hash('sha256', $validator);
        
        if (!hash_equals($row['token'], $token_hash)) {
            // Token không khớp → Có thể bị đánh cắp
            $this->deleteAllUserTokens($row['user_id']);
            $this->deleteToken();
            return false;
        }
        
        // ✅ TOKEN HỢP LỆ → Lấy thông tin user
        $stmt_user = $this->mysqli->prepare(
            "SELECT `id`, `taikhoan`, `status` 
             FROM `Users` 
             WHERE `id` = ? 
             LIMIT 1"
        );
        $stmt_user->bind_param("i", $row['user_id']);
        $stmt_user->execute();
        $user_result = $stmt_user->get_result();
        
        if ($user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();
            $stmt_user->close();
            
            // Kiểm tra tài khoản có bị ban không
            if ($user['status'] === 'banned') {
                $this->deleteToken();
                return false;
            }
            
            // ✅ TỰ ĐỘNG ĐĂNG NHẬP
            $_SESSION['Users'] = $user['taikhoan'];
            session_regenerate_id(true);
            
            // Refresh token (tạo token mới thay token cũ)
            $this->createToken($user['id']);
            
            return true;
        }
        
        $stmt_user->close();
        return false;
    }
    
    // ================================================
    // XÓA TOKEN (KHI LOGOUT)
    // ================================================
    public function deleteToken() {
        if (isset($_COOKIE[$this->cookie_name])) {
            $cookie_value = $_COOKIE[$this->cookie_name];
            $parts = explode(':', $cookie_value);
            
            if (count($parts) === 2) {
                $selector = $parts[0];
                $this->deleteTokenBySelector($selector);
            }
            
            // Xóa cookie
            setcookie(
                $this->cookie_name,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
    }
    
    // ================================================
    // HELPER: XÓA TOKEN THEO SELECTOR
    // ================================================
    private function deleteTokenBySelector($selector) {
        $stmt = $this->mysqli->prepare("DELETE FROM `remember_tokens` WHERE `selector` = ?");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $stmt->close();
    }
    
    // ================================================
    // HELPER: XÓA TẤT CẢ TOKEN CỦA USER (BẢO MẬT)
    // ================================================
    private function deleteAllUserTokens($user_id) {
        $stmt = $this->mysqli->prepare("DELETE FROM `remember_tokens` WHERE `user_id` = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // ================================================
    // DỌN DẸP TOKEN HẾT HẠN (CRONJOB)
    // ================================================
    public function cleanExpiredTokens() {
        $this->mysqli->query("DELETE FROM `remember_tokens` WHERE `expires_at` < NOW()");
    }
}
?>