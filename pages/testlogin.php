<?php
// File: /test_remember_me.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    // Test với session ngắn hạn (2 phút = 120 giây)
    ini_set('session.cookie_lifetime', 120);
    ini_set('session.gc_maxlifetime', 120);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/remember_me.php';

echo "<h1>🧪 Test Remember Me Token</h1>";
echo "<style>
    body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;}
    .info-box{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #0f0;border-radius:4px;}
    .warn-box{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #ff0;border-radius:4px;}
    .error-box{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #f00;border-radius:4px;}
    code{background:#000;padding:2px 8px;border-radius:3px;color:#0ff;}
    table{border-collapse:collapse;width:100%;margin:15px 0;}
    th,td{padding:10px;text-align:left;border:1px solid #444;}
    th{background:#333;font-weight:bold;}
</style>";

// ============================================
// AUTO LOGIN NẾU CÓ TOKEN
// ============================================
if (!isset($_SESSION['Users'])) {
    echo "<div class='warn-box'>";
    echo "<p>🔍 <strong>Không có session, đang kiểm tra Remember Token...</strong></p>";
    
    $rememberMe = new RememberMe($mysqli);
    $validated = $rememberMe->validateToken();
    
    if ($validated) {
        echo "<p>✅ <strong style='color:#0f0;'>Token hợp lệ! Tự động đăng nhập thành công!</strong></p>";
        echo "<p>👤 User: <strong>{$_SESSION['Users']}</strong></p>";
    } else {
        echo "<p>❌ <strong style='color:#f00;'>Không có token hoặc token không hợp lệ</strong></p>";
    }
    echo "</div>";
}

// ============================================
// THÔNG TIN SESSION CHI TIẾT
// ============================================
echo "<hr>";
echo "<h2>📊 Thông tin Session</h2>";

echo "<table>";
echo "<tr><th>Session Property</th><th>Value</th></tr>";

// Session Status
$session_status = session_status();
$status_text = [
    PHP_SESSION_DISABLED => '❌ DISABLED',
    PHP_SESSION_NONE => '⚠️ NONE',
    PHP_SESSION_ACTIVE => '✅ ACTIVE'
];
echo "<tr><td><strong>Session Status</strong></td><td>" . ($status_text[$session_status] ?? 'Unknown') . "</td></tr>";

// Session ID
echo "<tr><td><strong>Session ID</strong></td><td><code>" . session_id() . "</code></td></tr>";

// Session Name
echo "<tr><td><strong>Session Name</strong></td><td><code>" . session_name() . "</code></td></tr>";

// Session Save Path
echo "<tr><td><strong>Save Path</strong></td><td><code>" . session_save_path() . "</code></td></tr>";

// Cookie Lifetime
echo "<tr><td><strong>Cookie Lifetime</strong></td><td>" . ini_get('session.cookie_lifetime') . " seconds</td></tr>";

// GC Max Lifetime
echo "<tr><td><strong>GC Max Lifetime</strong></td><td>" . ini_get('session.gc_maxlifetime') . " seconds</td></tr>";

// Cookie Domain
echo "<tr><td><strong>Cookie Domain</strong></td><td>" . (ini_get('session.cookie_domain') ?: '<em>default</em>') . "</td></tr>";

// Cookie Path
echo "<tr><td><strong>Cookie Path</strong></td><td>" . ini_get('session.cookie_path') . "</td></tr>";

// Cookie Secure
echo "<tr><td><strong>Cookie Secure</strong></td><td>" . (ini_get('session.cookie_secure') ? '✅ Yes (HTTPS only)' : '❌ No') . "</td></tr>";

// Cookie HttpOnly
echo "<tr><td><strong>Cookie HttpOnly</strong></td><td>" . (ini_get('session.cookie_httponly') ? '✅ Yes' : '❌ No') . "</td></tr>";

// Cookie SameSite
echo "<tr><td><strong>Cookie SameSite</strong></td><td>" . (ini_get('session.cookie_samesite') ?: '<em>none</em>') . "</td></tr>";

echo "</table>";

// ============================================
// SESSION DATA
// ============================================
echo "<h2>📦 Session Data</h2>";

if (empty($_SESSION)) {
    echo "<div class='error-box'><p>❌ <strong>Session rỗng</strong></p></div>";
} else {
    echo "<table>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        $display_value = is_array($value) ? '<pre>' . print_r($value, true) . '</pre>' : htmlspecialchars($value);
        echo "<tr><td><code>$key</code></td><td>$display_value</td></tr>";
    }
    echo "</table>";
}

// ============================================
// LOGIN STATUS
// ============================================
echo "<hr>";
echo "<h2>🔐 Login Status</h2>";

if (isset($_SESSION['Users'])) {
    echo "<div class='info-box'>";
    echo "<p>✅ <strong style='color:#0f0;'>ĐÃ ĐĂNG NHẬP</strong></p>";
    echo "<p>👤 Username: <strong>{$_SESSION['Users']}</strong></p>";
    echo "</div>";
} else {
    echo "<div class='error-box'>";
    echo "<p>❌ <strong style='color:#f00;'>CHƯA ĐĂNG NHẬP</strong></p>";
    echo "</div>";
}

// ============================================
// COOKIE INFORMATION
// ============================================
echo "<h2>🍪 Cookie Information</h2>";

echo "<table>";
echo "<tr><th>Cookie Name</th><th>Status</th><th>Value Preview</th></tr>";

// Session Cookie
$phpsessid = $_COOKIE['PHPSESSID'] ?? null;
echo "<tr>";
echo "<td><strong>PHPSESSID</strong><br><small>Session Cookie</small></td>";
echo "<td>" . ($phpsessid ? '✅ Có' : '❌ Không') . "</td>";
echo "<td>" . ($phpsessid ? '<code>' . substr($phpsessid, 0, 20) . '...</code>' : '-') . "</td>";
echo "</tr>";

// Remember Cookie
$remember_cookie = $_COOKIE['remember_ai84'] ?? null;
echo "<tr>";
echo "<td><strong>remember_ai84</strong><br><small>Remember Me Token</small></td>";
echo "<td>" . ($remember_cookie ? '✅ Có' : '❌ Không') . "</td>";
echo "<td>";
if ($remember_cookie) {
    $parts = explode(':', $remember_cookie);
    echo "<code>Selector: " . ($parts[0] ?? 'N/A') . "</code><br>";
    echo "<code>Token: " . (isset($parts[1]) ? substr($parts[1], 0, 10) . '...' : 'N/A') . "</code>";
} else {
    echo "-";
}
echo "</td>";
echo "</tr>";

echo "</table>";

// ============================================
// ALL COOKIES
// ============================================
if (!empty($_COOKIE)) {
    echo "<h3>📋 All Cookies</h3>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Value (first 50 chars)</th></tr>";
    foreach ($_COOKIE as $name => $value) {
        echo "<tr>";
        echo "<td><code>$name</code></td>";
        echo "<td><code>" . htmlspecialchars(substr($value, 0, 50)) . (strlen($value) > 50 ? '...' : '') . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ============================================
// DATABASE TOKEN INFO
// ============================================
echo "<hr>";
echo "<h2>💾 Database Token Info</h2>";

if (isset($_SESSION['Users'])) {
    $stmt = $mysqli->prepare("SELECT u.id FROM Users u WHERE u.taikhoan = ? LIMIT 1");
    $stmt->bind_param("s", $_SESSION['Users']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['id'];
        
        $stmt_token = $mysqli->prepare("
            SELECT selector, expires_at, created_at, last_used_at 
            FROM remember_tokens 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt_token->bind_param("i", $user_id);
        $stmt_token->execute();
        $token_result = $stmt_token->get_result();
        
        if ($token_result->num_rows > 0) {
            echo "<div class='info-box'>";
            echo "<p>✅ <strong style='color:#0f0;'>Có " . $token_result->num_rows . " token trong database</strong></p>";
            echo "</div>";
            
            echo "<table>";
            echo "<tr><th>Selector</th><th>Created</th><th>Last Used</th><th>Expires</th><th>Status</th></tr>";
            
            while ($token = $token_result->fetch_assoc()) {
                $now = new DateTime();
                $expires = new DateTime($token['expires_at']);
                $diff = $now->diff($expires);
                
                $status = '';
                if ($diff->invert) {
                    $status = "<span style='color:#f00;'>⚠️ Đã hết hạn</span>";
                } else {
                    $status = "<span style='color:#0f0;'>✅ Còn {$diff->days}d {$diff->h}h {$diff->i}m</span>";
                }
                
                echo "<tr>";
                echo "<td><code>" . htmlspecialchars(substr($token['selector'], 0, 12)) . "...</code></td>";
                echo "<td>" . date('d/m/Y H:i:s', strtotime($token['created_at'])) . "</td>";
                echo "<td>" . ($token['last_used_at'] ? date('d/m/Y H:i:s', strtotime($token['last_used_at'])) : '-') . "</td>";
                echo "<td>" . date('d/m/Y H:i:s', strtotime($token['expires_at'])) . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='error-box'>";
            echo "<p>❌ <strong style='color:#f00;'>Không có token trong database</strong></p>";
            echo "</div>";
        }
        $stmt_token->close();
    }
    $stmt->close();
} else {
    echo "<div class='warn-box'>";
    echo "<p>⚠️ Chưa đăng nhập, không thể kiểm tra token</p>";
    echo "</div>";
}

// ============================================
// SERVER INFO
// ============================================
echo "<hr>";
echo "<h2>🖥️ Server Info</h2>";

echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";
echo "<tr><td><strong>PHP Version</strong></td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td><strong>Server Software</strong></td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Server Time</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "<tr><td><strong>Timezone</strong></td><td>" . date_default_timezone_get() . "</td></tr>";
echo "<tr><td><strong>User Agent</strong></td><td>" . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>Remote IP</strong></td><td>" . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>HTTPS</strong></td><td>" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "</table>";

// ============================================
// HƯỚNG DẪN TEST
// ============================================
echo "<hr>";
echo "<h2>📖 Hướng dẫn Test</h2>";

echo "<div class='info-box'>";
echo "<h3>🧪 Test Flow:</h3>";
echo "<ol style='line-height:1.8;'>";
echo "<li><strong>Đăng nhập bình thường</strong> trên trang chính với tích <strong>Remember Me</strong></li>";
echo "<li>Vào trang này: <code>https://ai84.pro/test_remember_me.php</code></li>";
echo "<li>Kiểm tra:<ul>";
echo "<li>✅ Session Status = <strong>ACTIVE</strong></li>";
echo "<li>✅ Login Status = <strong>ĐÃ ĐĂNG NHẬP</strong></li>";
echo "<li>✅ Cookie <strong>PHPSESSID</strong> = Có</li>";
echo "<li>✅ Cookie <strong>remember_ai84</strong> = Có</li>";
echo "<li>✅ Database Token = Có</li>";
echo "</ul></li>";
echo "<li><strong style='color:#ff0;'>ĐỢI 2 PHÚT</strong> (để session hết hạn)</li>";
echo "<li><strong>Refresh trang này (F5)</strong></li>";
echo "<li>Kết quả mong đợi:<ul>";
echo "<li>❌ Session sẽ mất (vì đã hết hạn)</li>";
echo "<li>✅ Nhưng sẽ thấy message: <strong style='color:#0f0;'>\"Token hợp lệ! Tự động đăng nhập thành công!\"</strong></li>";
echo "<li>✅ Login Status lại trở thành <strong>ĐÃ ĐĂNG NHẬP</strong></li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<div class='warn-box'>";
echo "<h3>⚙️ Test Cases:</h3>";
echo "<ul style='line-height:1.8;'>";
echo "<li><strong>Test 1:</strong> Session timeout → Auto login bằng token</li>";
echo "<li><strong>Test 2:</strong> Đóng browser → Mở lại → Vẫn đăng nhập</li>";
echo "<li><strong>Test 3:</strong> Xóa cookie PHPSESSID → F5 → Auto login</li>";
echo "<li><strong>Test 4:</strong> Xóa cookie remember_ai84 → F5 → Phải login lại</li>";
echo "</ul>";
echo "</div>";

// ============================================
// ACTIONS
// ============================================
echo "<hr>";
echo "<h2>🎮 Actions</h2>";

echo "<p>";
echo "<a href='/config/logout.php' style='display:inline-block;padding:10px 20px;background:#f00;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;'>🚪 Logout</a> ";
echo "<a href='?' style='display:inline-block;padding:10px 20px;background:#0a0;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;'>🔄 Refresh</a> ";
echo "<a href='javascript:location.reload(true)' style='display:inline-block;padding:10px 20px;background:#00a;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;'>🔃 Hard Refresh</a>";
echo "</p>";

// ============================================
// DEBUG: RAW $_SERVER
// ============================================
echo "<hr>";
echo "<details>";
echo "<summary style='cursor:pointer;font-size:18px;font-weight:bold;'>🔧 Debug: Raw \$_SERVER (Click to expand)</summary>";
echo "<pre style='background:#000;padding:15px;overflow:auto;max-height:400px;'>";
print_r($_SERVER);
echo "</pre>";
echo "</details>";

$mysqli->close();

echo "<p style='text-align:center;color:#666;margin-top:30px;'>Last updated: " . date('Y-m-d H:i:s') . "</p>";
?>