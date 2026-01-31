<?php
// File: /config/auth_middleware.php
// Dùng để bảo vệ các trang tools

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/remember_me.php';

class AuthMiddleware {
    private $mysqli;
    private $current_user = null;
    
    public function __construct($db_connection) {
        $this->mysqli = $db_connection;
    }
    
    /**
     * Kiểm tra user đã đăng nhập chưa
     * @return bool
     */
    public function isAuthenticated() {
        // Method 1: Check session
        if (isset($_SESSION['Users'])) {
            return $this->validateSessionUser($_SESSION['Users']);
        }
        
        // Method 2: Check API key in session
        if (isset($_SESSION['api_key'])) {
            return $this->validateApiKey($_SESSION['api_key']);
        }
        
        // Method 3: Check Remember Me cookie
        $rememberMe = new RememberMe($this->mysqli);
        if ($rememberMe->validateToken()) {
            return true;
        }
        
        // Method 4: Check API key in header (for API calls)
        $headers = getallheaders();
        if (isset($headers['X-API-Key']) || isset($headers['Authorization'])) {
            $api_key = $headers['X-API-Key'] ?? str_replace('Bearer ', '', $headers['Authorization'] ?? '');
            return $this->validateApiKey($api_key);
        }
        
        return false;
    }
    
    /**
     * Validate session user
     */
    private function validateSessionUser($username) {
        $stmt = $this->mysqli->prepare("
            SELECT `id`, `taikhoan`, `email`, `status`, `api_key` 
            FROM `Users` 
            WHERE `taikhoan` = ? OR `google_id` = ?
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $this->current_user = $result->fetch_assoc();
            
            // Check banned
            if ($this->current_user['status'] === 'banned') {
                $this->logout();
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate API key
     */
    private function validateApiKey($api_key) {
        if (empty($api_key) || !preg_match('/^kingcongstudio_[a-zA-Z0-9_]+$/', $api_key)) {
            return false;
        }
        
        $stmt = $this->mysqli->prepare("
            SELECT `id`, `taikhoan`, `email`, `status`, `api_key` 
            FROM `Users` 
            WHERE `api_key` = ?
        ");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $this->current_user = $result->fetch_assoc();
            
            // Check banned
            if ($this->current_user['status'] === 'banned') {
                return false;
            }
            
            // Set session nếu chưa có
            if (!isset($_SESSION['Users'])) {
                $_SESSION['Users'] = $this->current_user['taikhoan'];
                $_SESSION['api_key'] = $api_key;
                $_SESSION['login_method'] = 'apikey';
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Require authentication - redirect nếu chưa đăng nhập
     */
    public function requireAuth($redirect_url = '/pages/tools-login.php') {
        if (!$this->isAuthenticated()) {
            header('Location: ' . $redirect_url);
            exit();
        }
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->current_user;
    }
    
    /**
     * Get user ID
     */
    public function getUserId() {
        $user = $this->getCurrentUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Get API key
     */
    public function getApiKey() {
        $user = $this->getCurrentUser();
        return $user ? $user['api_key'] : null;
    }
    
    /**
     * Logout
     */
    public function logout() {
        // Xóa Remember Me token
        $rememberMe = new RememberMe($this->mysqli);
        $rememberMe->deleteToken();
        
        // Xóa session
        $_SESSION = [];
        
        // Xóa session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

// =================== GLOBAL HELPER ===================
function requireAuth($redirect_url = '/pages/tools-login.php') {
    global $mysqli;
    $auth = new AuthMiddleware($mysqli);
    $auth->requireAuth($redirect_url);
    return $auth;
}

function getCurrentUser() {
    global $mysqli;
    $auth = new AuthMiddleware($mysqli);
    return $auth->getCurrentUser();
}

function getUserApiKey() {
    global $mysqli;
    $auth = new AuthMiddleware($mysqli);
    return $auth->getApiKey();
}
?>