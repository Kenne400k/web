<?php
// =====================================================
// BỎ PHẦN START SESSION - header.php đã làm rồi
// =====================================================

// =====================================================
// INCLUDE HEADER TRƯỚC để có session và $user
// =====================================================
$page_title = 'Profile';
require_once __DIR__ . '/../config/header.php';
require_once __DIR__ . '/../config/database.php';

// =====================================================
// Kiểm tra $user đã được load từ header.php chưa
// =====================================================
if (!isset($user) || !isset($user['id'])) {
    // Nếu không có $user, header.php đã redirect rồi
    // Nhưng để chắc chắn, thêm exit
    exit('User not found');
}

$conn = $mysqli;
$current_user_id = (int)$user['id'];

// =====================================================
// XỬ LÝ CẬP NHẬT THÔNG TIN
// =====================================================
$updateMessage = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $ho = trim($_POST['ho'] ?? '');
    $ten = trim($_POST['ten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    
    if (empty($ten)) {
        $updateError = $lang === 'vi' ? 'Tên không được để trống!' : 'Name cannot be empty!';
    } else {
        $sql = "UPDATE Users SET ho = ?, ten = ?, sdt = ?, name = ? WHERE id = ?";
        $fullName = trim($ho . ' ' . $ten);
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ssssi', $ho, $ten, $sdt, $fullName, $current_user_id);
            
            if ($stmt->execute()) {
                $updateMessage = $lang === 'vi' ? 'Cập nhật thông tin thành công!' : 'Profile updated successfully!';
                
                // Cập nhật lại biến $user sau khi update
                $user['ho'] = $ho;
                $user['ten'] = $ten;
                $user['sdt'] = $sdt;
                $user['name'] = $fullName;
            } else {
                $updateError = $lang === 'vi' ? 'Có lỗi xảy ra. Vui lòng thử lại!' : 'An error occurred. Please try again!';
            }
            $stmt->close();
        }
    }
}

// =====================================================
// XỬ LÝ ĐỔI MẬT KHẨU
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($user['google_id'])) {
        $updateError = $lang === 'vi' 
            ? 'Tài khoản Google không thể đổi mật khẩu!' 
            : 'Google accounts cannot change password!';
    } elseif (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $updateError = $lang === 'vi' ? 'Vui lòng điền đầy đủ thông tin!' : 'Please fill in all fields!';
    } elseif ($newPassword !== $confirmPassword) {
        $updateError = $lang === 'vi' ? 'Mật khẩu mới không khớp!' : 'New passwords do not match!';
    } elseif (strlen($newPassword) < 6) {
        $updateError = $lang === 'vi' ? 'Mật khẩu phải có ít nhất 6 ký tự!' : 'Password must be at least 6 characters!';
    } else {
        $sql = "SELECT matkhau FROM Users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            $stmt->close();
            
            if ($userData && password_verify($currentPassword, $userData['matkhau'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE Users SET matkhau = ?, last_password_change = NOW() WHERE id = ?";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param('si', $hashedPassword, $current_user_id);
                    
                    if ($stmt->execute()) {
                        $updateMessage = $lang === 'vi' ? 'Đổi mật khẩu thành công!' : 'Password changed successfully!';
                    } else {
                        $updateError = $lang === 'vi' ? 'Có lỗi xảy ra. Vui lòng thử lại!' : 'An error occurred. Please try again!';
                    }
                    $stmt->close();
                }
            } else {
                $updateError = $lang === 'vi' ? 'Mật khẩu hiện tại không đúng!' : 'Current password is incorrect!';
            }
        }
    }
}

// =====================================================
// LẤY THỐNG KÊ USER
// =====================================================
$stats = [
    'total_orders' => 0,
    'total_spent' => 0,
    'completed_orders' => 0
];

$statsSql = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(price), 0) as total_spent,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders
             FROM orders WHERE user_id = ?";

if ($stmt = $conn->prepare($statsSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
}

// =====================================================
// LẤY LỊCH SỬ GIAO DỊCH GẦN ĐÂY
// =====================================================
$recentTransactions = [];
$transSql = "SELECT * FROM Transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
if ($stmt = $conn->prepare($transSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================
function getVal($array, $key, $default = '') {
    return $array[$key] ?? $default;
}

function getIntVal($array, $key, $default = 0) {
    return isset($array[$key]) ? (int)$array[$key] : $default;
}

function getFloatVal($array, $key, $default = 0.0) {
    return isset($array[$key]) ? (float)$array[$key] : $default;
}

// =====================================================
// INCLUDE SIDEBAR
// =====================================================
require_once '../config/sidebar.php';
?>

<style>
    .profile-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Profile Header Card */
    .profile-header-card {
        background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid #333;
        position: relative;
        overflow: hidden;
    }

    html:not(.dark) .profile-header-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
    }

    html:not(.dark) .profile-header-card,
    html:not(.dark) .profile-header-card .profile-header-info,
    html:not(.dark) .profile-header-card .badge-item,
    html:not(.dark) .profile-header-card .profile-balance-card {
        color: #1a1a1a;
    }

    .profile-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 25px;
        position: relative;
        z-index: 1;
    }

    .profile-avatar-wrapper {
        position: relative;
    }

    .profile-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .profile-header-info {
        flex: 1;
        color: white;
    }

    .profile-header-name {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .profile-header-email {
        font-size: 15px;
        opacity: 0.9;
        margin-bottom: 12px;
    }

    .profile-badges {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .badge-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .profile-balance-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        min-width: 200px;
    }

    .balance-label-header {
        font-size: 13px;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .balance-amount-header {
        font-size: 32px;
        font-weight: 700;
        color: white;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.blue,
    .stat-icon.green,
    .stat-icon.orange,
    .stat-icon.purple {
        background: #1a1a1a;
        border: 1px solid #333;
        color: white;
    }

    html:not(.dark) .stat-icon.blue,
    html:not(.dark) .stat-icon.green,
    html:not(.dark) .stat-icon.orange,
    html:not(.dark) .stat-icon.purple {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        color: #1a1a1a;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-color);
    }

    /* Main Grid Layout */
    .profile-main-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 30px;
    }

    .profile-left-column {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .profile-right-column {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    /* Card Styles */
    .card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }

    .card-header i {
        font-size: 20px;
        color: var(--text-color);
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin: 0;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .form-label .required {
        color: #dc3545;
    }

    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--input-border);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text-color);
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-input:focus {
        outline: none;
        border-color: #555;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
    }

    html:not(.dark) .form-input:focus {
        border-color: #999;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }

    .form-input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    /* Button Styles */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #1a1a1a;
        border: 1px solid #333;
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        background: #2d2d2d;
    }

    html:not(.dark) .btn-primary {
        background: #1a1a1a;
        border: 1px solid #1a1a1a;
    }

    html:not(.dark) .btn-primary:hover {
        background: #333;
    }

    .btn-full {
        width: 100%;
        justify-content: center;
    }

    /* Alert Styles */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
    }

    .alert i {
        font-size: 18px;
    }

    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    /* Transaction List */
    .transaction-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .transaction-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: var(--input-bg);
        border-radius: 10px;
        transition: all 0.3s;
    }

    .transaction-item:hover {
        background: var(--border-color);
    }

    .transaction-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .transaction-icon.deposit,
    .transaction-icon.order,
    .transaction-icon.refund,
    .transaction-icon.product_order {
        background: #1a1a1a;
        border: 1px solid #333;
        color: white;
    }

    html:not(.dark) .transaction-icon.deposit,
    html:not(.dark) .transaction-icon.order,
    html:not(.dark) .transaction-icon.refund,
    html:not(.dark) .transaction-icon.product_order {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        color: #1a1a1a;
    }

    .transaction-details {
        flex: 1;
        min-width: 0;
    }

    .transaction-type {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 4px;
        font-size: 14px;
    }

    .transaction-content {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .transaction-date {
        font-size: 12px;
        color: var(--text-muted);
    }

    .transaction-amount {
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .amount-positive {
        color: #28a745;
    }

    .amount-negative {
        color: #dc3545;
    }

    /* Account Info Card */
    .info-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: var(--input-bg);
        border-radius: 10px;
        margin-bottom: 12px;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #1a1a1a;
        border: 1px solid #333;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    html:not(.dark) .info-icon {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        color: #1a1a1a;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-color);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 48px;
        opacity: 0.3;
        margin-bottom: 15px;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    /* Password Indicator */
    .password-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #856404;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .profile-main-grid {
            grid-template-columns: 1fr;
        }

        .profile-right-column {
            order: -1;
        }
    }

    @media (max-width: 768px) {
        .profile-wrapper {
            padding: 15px;
        }

        .profile-header-card {
            padding: 20px;
        }

        .profile-header-content {
            flex-direction: column;
            text-align: center;
        }

        .profile-balance-card {
            width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .profile-header-name {
            font-size: 22px;
            justify-content: center;
        }

        .profile-badges {
            justify-content: center;
        }
    }
</style>

<div class="profile-wrapper">
    <!-- Alerts -->
    <?php if ($updateMessage): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> 
            <span><?php echo htmlspecialchars($updateMessage); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($updateError): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle-fill"></i> 
            <span><?php echo htmlspecialchars($updateError); ?></span>
        </div>
    <?php endif; ?>

    <!-- Profile Header Card -->
    <div class="profile-header-card">
        <div class="profile-header-content">
            <div class="profile-avatar-wrapper">
                <?php 
                $userName = getVal($user, 'name', 'User');
                $avatarUrl = getVal($user, 'avatar', 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&size=200');
                ?>
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                     alt="Avatar" 
                     class="profile-avatar-large">
            </div>
            
            <div class="profile-header-info">
                <div class="profile-header-name">
                    <?php echo htmlspecialchars($userName); ?>
                    <?php if (getVal($user, 'role') === 'admin'): ?>
                        <i class="bi bi-patch-check-fill" style="color: #ffd700;"></i>
                    <?php endif; ?>
                </div>
                
                <div class="profile-header-email">
                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars(getVal($user, 'email', 'No email')); ?>
                </div>
                
                <div class="profile-badges">
                    <?php if (getVal($user, 'role') === 'admin'): ?>
                        <span class="badge-item">
                            <i class="bi bi-shield-check"></i> Administrator
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty(getVal($user, 'google_id'))): ?>
                        <span class="badge-item">
                            <i class="bi bi-google"></i> Google Account
                        </span>
                    <?php endif; ?>
                    
                    <span class="badge-item">
                        <i class="bi bi-calendar-check"></i> 
                        <?php echo $lang === 'vi' ? 'Tham gia' : 'Member since'; ?> 
                        <?php 
                        $userTime = getVal($user, 'time', date('Y-m-d H:i:s'));
                        echo date('M Y', strtotime($userTime)); 
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="profile-balance-card">
                <div class="balance-label-header">
                    <?php echo $lang === 'vi' ? 'Số dư tài khoản' : 'Account Balance'; ?>
                </div>
                <div class="balance-amount-header">
                    $<?php echo number_format(getFloatVal($user, 'sodu', 0), 4); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Tổng đơn hàng' : 'Total Orders'; ?></div>
                <div class="stat-value"><?php echo getIntVal($stats, 'total_orders', 0); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Đã hoàn thành' : 'Completed'; ?></div>
                <div class="stat-value"><?php echo getIntVal($stats, 'completed_orders', 0); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Tổng chi tiêu' : 'Total Spent'; ?></div>
                <div class="stat-value">$<?php echo number_format(getFloatVal($stats, 'total_spent', 0), 2); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Trung bình/đơn' : 'Avg per Order'; ?></div>
                <div class="stat-value">
                    <?php 
                    $totalOrders = getIntVal($stats, 'total_orders', 0);
                    $totalSpent = getFloatVal($stats, 'total_spent', 0);
                    echo '$' . ($totalOrders > 0 ? number_format($totalSpent / $totalOrders, 2) : '0.00');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="profile-main-grid">
        <!-- Left Column -->
        <div class="profile-left-column">
            <!-- Edit Profile Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square"></i>
                    <h3 class="card-title">
                        <?php echo $lang === 'vi' ? 'Chỉnh sửa thông tin' : 'Edit Profile'; ?>
                    </h3>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $lang === 'vi' ? 'Họ' : 'Last Name'; ?>
                            </label>
                            <input type="text" 
                                   name="ho" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars(getVal($user, 'ho', '')); ?>"
                                   placeholder="<?php echo $lang === 'vi' ? 'Nhập họ' : 'Enter last name'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $lang === 'vi' ? 'Tên' : 'First Name'; ?> 
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="ten" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars(getVal($user, 'ten', '')); ?>" 
                                   placeholder="<?php echo $lang === 'vi' ? 'Nhập tên' : 'Enter first name'; ?>"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-telephone"></i> 
                            <?php echo $lang === 'vi' ? 'Số điện thoại' : 'Phone Number'; ?>
                        </label>
                        <input type="tel" 
                               name="sdt" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars(getVal($user, 'sdt', '')); ?>"
                               placeholder="<?php echo $lang === 'vi' ? 'Nhập số điện thoại' : 'Enter phone number'; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-envelope"></i> 
                            <?php echo $lang === 'vi' ? 'Email' : 'Email Address'; ?>
                        </label>
                        <input type="email" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars(getVal($user, 'email', '')); ?>" 
                               disabled>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary btn-full">
                        <i class="bi bi-check-circle"></i> 
                        <?php echo $lang === 'vi' ? 'Cập nhật thông tin' : 'Update Profile'; ?>
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
            <?php if (empty(getVal($user, 'google_id'))): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-lock"></i>
                        <h3 class="card-title">
                            <?php echo $lang === 'vi' ? 'Đổi mật khẩu' : 'Change Password'; ?>
                        </h3>
                    </div>

                    <div class="password-indicator">
                        <i class="bi bi-info-circle"></i>
                        <span><?php echo $lang === 'vi' ? 'Mật khẩu phải có ít nhất 6 ký tự' : 'Password must be at least 6 characters'; ?></span>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $lang === 'vi' ? 'Mật khẩu hiện tại' : 'Current Password'; ?>
                                <span class="required">*</span>
                            </label>
                            <input type="password" 
                                   name="current_password" 
                                   class="form-input" 
                                   placeholder="<?php echo $lang === 'vi' ? 'Nhập mật khẩu hiện tại' : 'Enter current password'; ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $lang === 'vi' ? 'Mật khẩu mới' : 'New Password'; ?>
                                <span class="required">*</span>
                            </label>
                            <input type="password" 
                                   name="new_password" 
                                   class="form-input" 
                                   placeholder="<?php echo $lang === 'vi' ? 'Nhập mật khẩu mới' : 'Enter new password'; ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $lang === 'vi' ? 'Xác nhận mật khẩu mới' : 'Confirm New Password'; ?>
                                <span class="required">*</span>
                            </label>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="form-input" 
                                   placeholder="<?php echo $lang === 'vi' ? 'Nhập lại mật khẩu mới' : 'Re-enter new password'; ?>"
                                   required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary btn-full">
                            <i class="bi bi-key"></i> 
                            <?php echo $lang === 'vi' ? 'Đổi mật khẩu' : 'Change Password'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="profile-right-column">
            <!-- Account Info Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i>
                    <h3 class="card-title">
                        <?php echo $lang === 'vi' ? 'Thông tin tài khoản' : 'Account Information'; ?>
                    </h3>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label"><?php echo $lang === 'vi' ? 'ID người dùng' : 'User ID'; ?></div>
                        <div class="info-value">#<?php echo str_pad((string)getIntVal($user, 'id', 0), 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>

                <?php if (!empty(getVal($user, 'ma_giao_dich'))): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="bi bi-key"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label"><?php echo $lang === 'vi' ? 'Mã giao dịch' : 'Transaction Code'; ?></div>
                            <div class="info-value"><?php echo htmlspecialchars(getVal($user, 'ma_giao_dich', '')); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label"><?php echo $lang === 'vi' ? 'Ngày tham gia' : 'Member Since'; ?></div>
                        <div class="info-value">
                            <?php 
                            $userTime = getVal($user, 'time', date('Y-m-d H:i:s'));
                            echo date('d/m/Y', strtotime($userTime)); 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label"><?php echo $lang === 'vi' ? 'Vai trò' : 'Role'; ?></div>
                        <div class="info-value">
                            <?php 
                            $userRole = getVal($user, 'role', 'user');
                            echo $userRole === 'admin' 
                                ? ($lang === 'vi' ? 'Quản trị viên' : 'Administrator')
                                : ($lang === 'vi' ? 'Người dùng' : 'User'); 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label"><?php echo $lang === 'vi' ? 'Loại tài khoản' : 'Account Type'; ?></div>
                        <div class="info-value">
                            <?php echo !empty(getVal($user, 'google_id'))
                                ? 'Google OAuth'
                                : ($lang === 'vi' ? 'Đăng ký thường' : 'Regular'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i>
                    <h3 class="card-title">
                        <?php echo $lang === 'vi' ? 'Giao dịch gần đây' : 'Recent Transactions'; ?>
                    </h3>
                </div>
                
                <?php if (empty($recentTransactions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p><?php echo $lang === 'vi' ? 'Chưa có giao dịch nào' : 'No transactions yet'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="transaction-list">
                        <?php foreach ($recentTransactions as $trans): ?>
                            <div class="transaction-item">
                                <div class="transaction-icon <?php echo htmlspecialchars(getVal($trans, 'type', 'order')); ?>">
                                    <?php 
                                    $icons = [
                                        'deposit' => 'bi-arrow-down-circle',
                                        'order' => 'bi-cart-check',
                                        'refund' => 'bi-arrow-counterclockwise',
                                        'product_order' => 'bi-cart-check'
                                    ];
                                    $transType = getVal($trans, 'type', 'order');
                                    $iconClass = $icons[$transType] ?? 'bi-circle';
                                    echo '<i class="bi ' . $iconClass . '"></i>';
                                    ?>
                                </div>
                                
                                <div class="transaction-details">
                                    <div class="transaction-type">
                                        <?php 
                                        $typeLabels = [
                                            'deposit' => $lang === 'vi' ? 'Nạp tiền' : 'Deposit',
                                            'order' => $lang === 'vi' ? 'Đặt hàng' : 'Order',
                                            'refund' => $lang === 'vi' ? 'Hoàn tiền' : 'Refund',
                                            'product_order' => $lang === 'vi' ? 'Đặt sản phẩm' : 'Product Order'
                                        ];
                                        $transType = getVal($trans, 'type', 'order');
                                        echo $typeLabels[$transType] ?? ucfirst($transType);
                                        ?>
                                    </div>
                                    <div class="transaction-content" title="<?php echo htmlspecialchars(getVal($trans, 'content', '')); ?>">
                                        <?php echo htmlspecialchars(getVal($trans, 'content', 'No description')); ?>
                                    </div>
                                    <div class="transaction-date">
                                        <i class="bi bi-clock"></i> 
                                        <?php 
                                        $createdAt = getVal($trans, 'created_at', date('Y-m-d H:i:s'));
                                        echo date('d/m/Y H:i', strtotime($createdAt)); 
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="transaction-amount <?php echo getVal($trans, 'type') === 'deposit' ? 'amount-positive' : 'amount-negative'; ?>">
                                    <?php 
                                    $transType = getVal($trans, 'type', 'order');
                                    echo $transType === 'deposit' ? '+' : '-'; 
                                    ?>$<?php echo number_format(getFloatVal($trans, 'amount', 0), 4); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>