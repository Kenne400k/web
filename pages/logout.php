<?php
// THÊM NGAY ĐẦU FILE ĐỂ DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// BẮT BUỘC: Start session trước khi thao tác
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/remember_me.php'; // 🔥 THÊM

// ================================================
// LOGGING LOGOUT ACTIVITY
// ================================================
$logFile = __DIR__ . '/../logs/logout.log'; // 🔥 SỬA: Chuyển vào folder logs
$log_dir = dirname($logFile);

// Tạo folder logs nếu chưa có
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$sessionInfo = "Session ID: " . session_id() . 
               ", Users: " . ($_SESSION['Users'] ?? 'NULL') . 
               ", UserID: " . ($_SESSION['UserID'] ?? 'NULL');
$logMessage = "[{$timestamp}] LOGOUT - {$sessionInfo} - IP: " . 
              ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . PHP_EOL;

file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

// ================================================
// 🔥 XÓA REMEMBER ME TOKEN TRONG DATABASE
// ================================================
try {
    $rememberMe = new RememberMe($mysqli);
    $rememberMe->deleteToken(); // Xóa token trong DB + cookie
    
    file_put_contents($logFile, "[{$timestamp}] ✅ Remember token deleted" . PHP_EOL, FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    file_put_contents($logFile, "[{$timestamp}] ⚠️ Error deleting token: " . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ================================================
// XÓA TẤT CẢ SESSION DATA
// ================================================
$_SESSION = array(); // Clear toàn bộ session array

// ================================================
// XÓA SESSION COOKIE TRÊN BROWSER
// ================================================
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// ================================================
// XÓA CÁC COOKIES KHÁC (Legacy + Remember Me)
// ================================================
$cookies_to_delete = [
    'remember_token',      // Cookie cũ (nếu có)
    'auto_login',          // Cookie cũ (nếu có)
    'user_preferences',    // Cookie cũ (nếu có)
    'remember_ai84',       // 🔥 Cookie Remember Me mới
];

foreach ($cookies_to_delete as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie(
            $cookie_name,
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
// DESTROY SESSION HOÀN TOÀN
// ================================================
session_destroy();

// ================================================
// LOG SUCCESSFUL LOGOUT
// ================================================
file_put_contents($logFile, "[{$timestamp}] ✅ LOGOUT SUCCESS - Session destroyed, all cookies cleared" . PHP_EOL, FILE_APPEND | LOCK_EX);

// ================================================
// ĐÓNG DATABASE CONNECTION
// ================================================
if (isset($mysqli) && $mysqli->ping()) {
    $mysqli->close();
}

// ================================================
// REDIRECT VỚI CACHE BUSTING
// ================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: /auth/login?logout=1&t=" . time());
exit();
?>