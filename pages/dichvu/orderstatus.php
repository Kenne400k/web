<?php

// ---- PHẦN HIỂN THỊ GIAO DIỆN BẮT ĐẦU TỪ ĐÂY ----
$page_title = 'Order Status';
require_once __DIR__ . '/../../config/header.php';

// Các phần còn lại của tệp giữ nguyên logic cũ
if (!defined('ACCESS_ALLOWED')) {
    define('ACCESS_ALLOWED', true);
}
if (!isset($conn)) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/api_config.php';
    $conn = $mysqli;
    $current_user_id = $user['id'];
}

if (!function_exists('checkOrderStatusFromAPI')) {
    function checkOrderStatusFromAPI($orderId) {
        $postData = [
            'key' => API_KEY,
            'action' => 'status',
            'order' => $orderId
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, API_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $response) {
            return json_decode($response, true);
        }
        return false;
    }
}

if (!function_exists('updateOrderInDB')) {
    // --- FUNCTION CẬP NHẬT VỚI LOGIC HOÀN TIỀN (DÙNG CHO TÌM KIẾM THỦ CÔNG) ---
    function updateOrderInDB($orderId, $apiData) {
        global $conn;
        
        $apiStatus = $apiData['status'] ?? 'Pending';
        $statusMap = [
            'Pending' => 'Pending', 'Processing' => 'Processing', 'In progress' => 'Processing',
            'In Progress' => 'Processing', 'Completed' => 'Completed', 'Partial' => 'Partial',
            'Canceled' => 'Canceled', 'Cancelled' => 'Canceled', 'Refunded' => 'Refunded'
        ];
        
        $newStatus = $statusMap[$apiStatus] ?? 'Pending';
        $startCount = isset($apiData['start_count']) ? intval($apiData['start_count']) : null;
        $remains = isset($apiData['remains']) ? intval($apiData['remains']) : null;
        
        $checkSql = "SELECT status, price, user_id, refunded FROM orders WHERE order_id = ?";
        $oldOrder = null;
        if ($stmt = $conn->prepare($checkSql)) {
            $stmt->bind_param('s', $orderId);
            $stmt->execute();
            $oldOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        if (!$oldOrder || !isset($oldOrder['refunded']) || $oldOrder['refunded'] == 1) {
            return false;
        }
        
        $oldStatus = $oldOrder['status'];
        $orderPrice = floatval($oldOrder['price']);
        $userId = $oldOrder['user_id'];
        
        $needRefund = false;
        if (in_array($oldStatus, ['Pending', 'Processing', 'In progress']) && in_array($newStatus, ['Canceled', 'Partial', 'Refunded'])) {
            $needRefund = true;
        }
        
        if ($oldStatus === $newStatus && !$needRefund) {
            return false;
        }

        $conn->begin_transaction();
        try {
            $updateOrderSql = "UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE order_id = ?";
            if ($stmt = $conn->prepare($updateOrderSql)) {
                $stmt->bind_param('siis', $newStatus, $startCount, $remains, $orderId);
                $stmt->execute();
                $stmt->close();
            }

            if ($needRefund && $orderPrice > 0) {
                $updateBalanceSql = "UPDATE Users SET sodu = sodu + ? WHERE id = ?";
                if ($stmt = $conn->prepare($updateBalanceSql)) {
                    $stmt->bind_param('di', $orderPrice, $userId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $transactionSql = "INSERT INTO Transactions (user_id, amount, type, content) VALUES (?, ?, 'refund', ?)";
                if ($stmt = $conn->prepare($transactionSql)) {
                    $description = "Hoàn tiền cho đơn hàng dịch vụ #" . $orderId . " (Trạng thái: " . $newStatus . ")";
                    $stmt->bind_param('ids', $userId, $orderPrice, $description);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $markRefundSql = "UPDATE orders SET refunded = 1 WHERE order_id = ?";
                if ($stmt = $conn->prepare($markRefundSql)) {
                    $stmt->bind_param('s', $orderId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Lỗi cập nhật/hoàn tiền cho đơn hàng {$orderId}: " . $e->getMessage());
            return false;
        }
    }
}
// Các hàm refill và logic xử lý form POST giữ nguyên...
// Function to refill single order
function refillOrder($orderId) {
    $postData = [
        'key' => API_KEY,
        'action' => 'refill',
        'order' => $orderId
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        return json_decode($response, true);
    }
    return false;
}

// Function to refill multiple orders
function refillMultipleOrders($orderIds) {
    $postData = [
        'key' => API_KEY,
        'action' => 'refill',
        'orders' => implode(',', $orderIds)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        return json_decode($response, true);
    }
    return false;
}

// Xử lý refill đơn hàng
$refillMessage = '';
$refillError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refill_orders'])) {
    $selectedOrders = isset($_POST['selected_orders']) ? $_POST['selected_orders'] : [];
    
    if (!empty($selectedOrders) && count($selectedOrders) <= 100) {
        $placeholders = implode(',', array_fill(0, count($selectedOrders), '?'));
        $sql = "SELECT order_id, status FROM orders WHERE order_id IN ($placeholders) AND user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $types = str_repeat('s', count($selectedOrders)) . 'i';
            $params = array_merge($selectedOrders, [$current_user_id]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $validOrders = [];
            $invalidOrders = [];
            
            while ($row = $result->fetch_assoc()) {
                if (in_array($row['status'], ['Canceled', 'Partial'])) {
                    $validOrders[] = $row['order_id'];
                } else {
                    $invalidOrders[] = $row['order_id'];
                }
            }
            $stmt->close();
            
            if (!empty($validOrders)) {
                if (count($validOrders) == 1) {
                    $apiResponse = refillOrder($validOrders[0]);
                } else {
                    $apiResponse = refillMultipleOrders($validOrders);
                }
                
                if ($apiResponse && isset($apiResponse['refill']) && $apiResponse['refill'] == '1') {
                    $refillMessage = $lang === 'vi' 
                        ? 'Đã gửi yêu cầu refill thành công cho ' . count($validOrders) . ' đơn hàng!' 
                        : 'Successfully sent refill request for ' . count($validOrders) . ' orders!';
                    
                    foreach ($validOrders as $orderId) {
                        $updateSql = "UPDATE orders SET status = 'Pending', updated_at = NOW() WHERE order_id = ?";
                        if ($updateStmt = $conn->prepare($updateSql)) {
                            $updateStmt->bind_param('s', $orderId);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }
                    }
                } else {
                    $refillError = $lang === 'vi' ? 'Không thể refill đơn hàng. Vui lòng thử lại sau.' : 'Unable to refill orders. Please try again later.';
                }
            }
            
            if (!empty($invalidOrders)) {
                $refillError .= ($refillError ? ' ' : '') . ($lang === 'vi' 
                    ? 'Một số đơn không thể refill vì không ở trạng thái lỗi.' 
                    : 'Some orders cannot be refilled because they are not in error status.');
            }
        }
    } elseif (count($selectedOrders) > 100) {
        $refillError = $lang === 'vi' ? 'Chỉ có thể refill tối đa 100 đơn hàng cùng lúc!' : 'Maximum 100 orders can be refilled at once!';
    } else {
        $refillError = $lang === 'vi' ? 'Vui lòng chọn ít nhất một đơn hàng để refill!' : 'Please select at least one order to refill!';
    }
}

// Xử lý tìm kiếm thủ công
$searchOrderId = '';
$searchResult = null;
$searchError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_order'])) {
    $searchOrderId = trim($_POST['order_id']);
    
    if (!empty($searchOrderId)) {
        $sql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('si', $searchOrderId, $current_user_id);
            $stmt->execute();
            $dbOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($dbOrder) {
                $apiData = checkOrderStatusFromAPI($searchOrderId);
                
                if ($apiData) {
                    updateOrderInDB($searchOrderId, $apiData);
                    // Tải lại dữ liệu sau khi cập nhật để có thông tin mới nhất
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('si', $searchOrderId, $current_user_id);
                    $stmt->execute();
                    $searchResult = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                } else {
                    $searchError = $lang === 'vi' ? 'Không thể kết nối đến API' : 'Unable to connect to API';
                }
            } else {
                $searchError = $lang === 'vi' ? 'Không tìm thấy đơn hàng hoặc đơn hàng không thuộc về bạn' : 'Order not found or does not belong to you';
            }
        }
    }
}
// Lấy danh sách, thống kê, và các hàm hiển thị giữ nguyên...
// Lấy danh sách đơn hàng của user
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
if ($stmt = $conn->prepare($countSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $totalOrders = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}
$totalPages = ceil($totalOrders / $perPage);

$sql = "SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";
$orders = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('iii', $current_user_id, $perPage, $offset);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$statsSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('Pending', 'Processing', 'In progress') THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('Canceled', 'Partial', 'Refunded') THEN 1 ELSE 0 END) as failed
             FROM orders WHERE user_id = ?";
$stats = ['total' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
if ($stmt = $conn->prepare($statsSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

function getStatusBadge($status, $lang = 'en') {
    $statusLower = strtolower(trim($status));
    
    $badges = [
        'completed' => [
            'color' => '#28a745', 
            'icon' => 'check-circle-fill', 
            'text' => $lang === 'vi' ? 'Hoàn thành' : 'Completed'
        ],
        'processing' => [
            'color' => '#17a2b8', 
            'icon' => 'arrow-repeat', 
            'text' => $lang === 'vi' ? 'Đang xử lý' : 'Processing'
        ],
        'in progress' => [
            'color' => '#007bff', 
            'icon' => 'clock-history', 
            'text' => $lang === 'vi' ? 'Đang thực hiện' : 'In Progress'
        ],
        'pending' => [
            'color' => '#ffc107', 
            'icon' => 'hourglass-split', 
            'text' => $lang === 'vi' ? 'Chờ xử lý' : 'Pending'
        ],
        'partial' => [
            'color' => '#fd7e14', 
            'icon' => 'exclamation-triangle', 
            'text' => $lang === 'vi' ? 'Hoàn thành một phần' : 'Partial'
        ],
        'canceled' => [
            'color' => '#dc3545', 
            'icon' => 'x-circle-fill', 
            'text' => $lang === 'vi' ? 'Đã hủy' : 'Canceled'
        ],
        'cancelled' => [
            'color' => '#dc3545', 
            'icon' => 'x-circle-fill', 
            'text' => $lang === 'vi' ? 'Đã hủy' : 'Canceled'
        ],
        'refunded' => [
            'color' => '#6c757d', 
            'icon' => 'arrow-counterclockwise', 
            'text' => $lang === 'vi' ? 'Đã hoàn tiền' : 'Refunded'
        ]
    ];
    
    return $badges[$statusLower] ?? [
        'color' => '#6c757d', 
        'icon' => 'question-circle', 
        'text' => $lang === 'vi' ? ucfirst($status) : ucfirst($status)
    ];
}

require_once '../../config/sidebar.php';
?>

<style>
    /* CSS giữ nguyên, chỉ thêm một chút cho cột link */
    .page-header {
        background: linear-gradient(135deg, #1877f2, #0d5dbf);
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 25px;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .page-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: white !important;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: var(--content-bg);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    .stat-info h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: var(--text-color);
    }
    .stat-info p {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0;
    }
    .search-card {
        background: var(--content-bg);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
    }
    .search-form {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .search-form input {
        flex: 1;
        min-width: 200px;
        padding: 12px 15px;
        border: 1px solid var(--input-border);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text-color);
    }
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        white-space: nowrap;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1877f2, #0d5dbf);
        color: white;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-warning {
        background: #ff9800;
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    .refill-section {
        background: var(--content-bg);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    .refill-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .selected-count {
        background: #1877f2;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .orders-table {
        background: var(--content-bg);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-color);
    }
    .table-header, .table-row {
        padding: 15px 20px;
        display: grid;
        /* THAY ĐỔI: Thêm 1 cột 1fr cho Link, tổng cộng 10 cột */
        grid-template-columns: 50px 120px 1fr 1fr 150px 80px 80px 80px 100px 100px;
        gap: 15px;
        align-items: center;
        border-top: 1px solid var(--border-color);
    }
    .table-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        font-weight: 600;
        border-top: none;
    }
    html.dark .table-header {
        background: linear-gradient(135deg, #2d3748, #1a202c);
    }
    .table-row:hover {
        background: var(--input-bg);
    }
    .table-row.error-order {
        border-left: 3px solid #fd7e14;
    }
    .table-row.refunded-order {
        border-left: 3px solid #6c757d;
        background-color: rgba(108, 117, 125, 0.05);
    }
    /* THAY ĐỔI: Style cho cột Link */
    .link-cell {
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .link-cell a {
        color: #1877f2;
        text-decoration: none;
    }
    .link-cell a:hover {
        text-decoration: underline;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }
    .checkbox-cell {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .checkbox-cell input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .checkbox-cell input[type="checkbox"]:disabled {
        cursor: not-allowed;
        opacity: 0.3;
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
    }
    .pagination a {
        padding: 8px 16px;
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-color);
        text-decoration: none;
    }
    .pagination a.active {
        background: #1877f2;
        color: white;
        border-color: #1877f2;
    }
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
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
    .update-indicator {
        display: none; /* Ẩn mặc định */
        padding: 8px 12px;
        background-color: #e9ecef;
        border-radius: 8px;
        font-size: 13px;
        color: #495057;
        margin-left: 10px;
        transition: opacity 0.3s;
    }
    html.dark .update-indicator {
        background-color: #2d3748;
        color: #a0aec0;
    }
    .update-indicator .spinner {
        width: 14px;
        height: 14px;
        border: 2px solid #ced4da;
        border-top-color: #1877f2;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: inline-block;
        margin-right: 8px;
        vertical-align: middle;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    @media (max-width: 1200px) { /* Điều chỉnh cho màn hình nhỏ hơn */
        .table-header, .table-row {
            grid-template-columns: 1fr; /* Chuyển về 1 cột */
            gap: 5px;
            padding: 10px;
        }
        .table-header {
            display: none; /* Ẩn header trên mobile */
        }
        .table-row > div {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .table-row > div:last-child {
            border-bottom: none;
        }
        .table-row > div::before { /* Thêm label cho mỗi cell */
            content: attr(data-label);
            font-weight: bold;
            margin-right: 10px;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-clipboard-check"></i> <?php echo $lang === 'vi' ? 'Đơn Hàng Của Tôi' : 'My Orders'; ?></h1>
    </div>

    <!-- Khối thống kê giữ nguyên -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <i class="bi bi-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total']; ?></h3>
                <p><?php echo $lang === 'vi' ? 'Tổng đơn' : 'Total Orders'; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['processing']; ?></h3>
                <p><?php echo $lang === 'vi' ? 'Đang xử lý' : 'Processing'; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['completed']; ?></h3>
                <p><?php echo $lang === 'vi' ? 'Hoàn thành' : 'Completed'; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['failed']; ?></h3>
                <p><?php echo $lang === 'vi' ? 'Lỗi/Huỷ/Hoàn tiền' : 'Failed/Canceled'; ?></p>
            </div>
        </div>
    </div>

    <div class="search-card">
        <form method="POST" class="search-form">
            <div style="flex: 1;">
                <label style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px; display: block;">
                    <?php echo $lang === 'vi' ? 'Tìm kiếm đơn hàng' : 'Search Order'; ?>
                </label>
                <input type="text" name="order_id" placeholder="<?php echo $lang === 'vi' ? 'Nhập Order ID' : 'Enter Order ID'; ?>" value="<?php echo htmlspecialchars($searchOrderId); ?>">
            </div>
            <button type="submit" name="search_order" class="btn btn-primary">
                <i class="bi bi-search"></i> <?php echo $lang === 'vi' ? 'Tìm kiếm' : 'Search'; ?>
            </button>        </form>
    </div>

    <!-- Các khối thông báo và form refill giữ nguyên -->
    <?php if ($refillMessage): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($refillMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($refillError): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($refillError); ?>
        </div>
    <?php endif; ?>

    <?php if ($searchError): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($searchError); ?>
        </div>
    <?php endif; ?>

    <?php if ($searchResult): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?php echo $lang === 'vi' ? 'Đã cập nhật trạng thái mới nhất từ API' : 'Updated with latest status from API'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="refillForm">
        <div class="refill-section">
            <div class="refill-info">
                <i class="bi bi-arrow-repeat" style="font-size: 24px; color: #ff9800;"></i>
                <div>
                    <strong><?php echo $lang === 'vi' ? 'Refill đơn hàng lỗi' : 'Refill Failed Orders'; ?></strong>
                    <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted);">
                        <?php echo $lang === 'vi' ? 'Chọn các đơn hàng bị lỗi (Canceled/Partial) để yêu cầu refill' : 'Select failed orders (Canceled/Partial) to request refill'; ?>
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="selected-count" id="selectedCount">0 <?php echo $lang === 'vi' ? 'đơn' : 'orders'; ?></span>
                <button type="submit" name="refill_orders" class="btn btn-warning" id="refillBtn" disabled>
                    <i class="bi bi-arrow-clockwise"></i> <?php echo $lang === 'vi' ? 'Refill đơn đã chọn' : 'Refill Selected'; ?>
                </button>
            </div>
        </div>

        <div class="orders-table">
            <div class="table-header">
                <div><input type="checkbox" id="selectAll" style="width: 18px; height: 18px; cursor: pointer;"></div>
                <div><?php echo $lang === 'vi' ? 'Order ID' : 'Order ID'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Dịch vụ' : 'Service'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Link' : 'Link'; ?></div> <!-- THAY ĐỔI: Thêm cột Link -->
                <div><?php echo $lang === 'vi' ? 'Trạng thái' : 'Status'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Số lượng' : 'Quantity'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Bắt đầu' : 'Start'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Còn lại' : 'Remains'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Giá' : 'Price'; ?></div>
                <div><?php echo $lang === 'vi' ? 'Ngày tạo' : 'Created'; ?></div>
            </div>

            <?php foreach ($orders as $order): ?>
                <?php 
                    $badge = getStatusBadge($order['status'], $lang); 
                    $isErrorOrder = in_array($order['status'], ['Canceled', 'Partial']);
                    $isRefunded = isset($order['refunded']) && $order['refunded'] == 1;
                    $rowClass = 'table-row';
                    if ($isRefunded) {
                        $rowClass .= ' refunded-order';
                    } elseif ($isErrorOrder) {
                        $rowClass .= ' error-order';
                    }
                ?>
                <div class="<?php echo $rowClass; ?>">
                    <div data-label="#" class="checkbox-cell">
                        <input type="checkbox" name="selected_orders[]" value="<?php echo htmlspecialchars($order['order_id']); ?>" class="order-checkbox" <?php echo !$isErrorOrder ? 'disabled' : ''; ?> data-error-order="<?php echo $isErrorOrder ? '1' : '0'; ?>">
                    </div>
                    <div data-label="Order ID" style="font-weight: 600; color: #1877f2;"><?php echo htmlspecialchars($order['order_id']); ?></div>
                    <div data-label="Service" style="font-size: 13px;"><?php echo htmlspecialchars($order['service_name']); ?></div>
                    <!-- THAY ĐỔI: Thêm ô chứa link -->
                    <div data-label="Link" class="link-cell">
                        <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" title="<?php echo htmlspecialchars($order['link']); ?>">
                            <?php echo htmlspecialchars($order['link']); ?>
                        </a>
                    </div>
                    <div data-label="Status">
                        <span class="status-badge" style="background-color: <?php echo $badge['color']; ?>;">
                            <i class="bi bi-<?php echo $badge['icon']; ?>"></i>
                            <?php echo $badge['text']; ?>
                        </span>
                    </div>
                    <div data-label="Quantity"><?php echo number_format($order['quantity']); ?></div>
                    <div data-label="Start" style="font-size: 13px; color: var(--text-muted);">
                        <?php echo $order['start_count'] !== null ? number_format($order['start_count']) : '-'; ?>
                    </div>
                    <div data-label="Remains" style="font-size: 13px; color: var(--text-muted);">
                        <?php echo $order['remains'] !== null ? number_format($order['remains']) : '-'; ?>
                    </div>
                    <div data-label="Price" style="font-weight: 600;"><?php echo '$' . number_format($order['price'], 2); ?></div>
                    <div data-label="Created" style="font-size: 12px; color: var(--text-muted);">
                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
    
    <!-- Phân trang giữ nguyên -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---- PHẦN XỬ LÝ REFILL CHECKBOX (GIỮ LẠI) ----
    const checkboxes = document.querySelectorAll('.order-checkbox:not([disabled])');
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectedCountSpan = document.getElementById('selectedCount');
    const refillBtn = document.getElementById('refillBtn');

    function updateSelectedCount() {
        // Đếm số checkbox đã được chọn (chỉ đếm những cái không bị vô hiệu hóa)
        const checkedBoxes = document.querySelectorAll('.order-checkbox:not([disabled]):checked');
        const count = checkedBoxes.length;
        
        selectedCountSpan.textContent = count + ' <?php echo $lang === 'vi' ? 'đơn' : 'orders'; ?>';
        refillBtn.disabled = count === 0; // Kích hoạt nút Refill nếu có chọn
        
        // Xử lý logic cho nút "Chọn tất cả"
        const enabledCheckboxes = document.querySelectorAll('.order-checkbox:not([disabled])');
        const checkedEnabledBoxes = document.querySelectorAll('.order-checkbox:not([disabled]):checked');
        
        if (enabledCheckboxes.length > 0) {
            selectAllCheckbox.checked = checkedEnabledBoxes.length === enabledCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedEnabledBoxes.length > 0 && checkedEnabledBoxes.length < enabledCheckboxes.length;
        } else {
            // Nếu không có đơn nào có thể refill, vô hiệu hóa nút "Chọn tất cả"
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.disabled = true;
        }
    }

    // Gán sự kiện cho nút "Chọn tất cả"
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            // Chỉ chọn/bỏ chọn những checkbox không bị vô hiệu hóa
            checkboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = isChecked;
                }
            });
            updateSelectedCount();
        });
    }

    // Gán sự kiện cho từng checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Gán sự kiện cho form Refill để kiểm tra lần cuối
    const refillForm = document.getElementById('refillForm');
    if (refillForm) {
        refillForm.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault(); // Ngăn form gửi đi
                alert('<?php echo $lang === 'vi' ? 'Vui lòng chọn ít nhất một đơn hàng!' : 'Please select at least one order!'; ?>');
            }
        });
    }
    
    // Gọi hàm này 1 lần khi tải trang để cập nhật trạng thái ban đầu
    updateSelectedCount();

    // ---- ĐÃ XÓA PHẦN TỰ ĐỘNG CẬP NHẬT (updateStatuses) ----
    // Cronjob trên server đang xử lý việc này.
    // Trang này không cần tự động gọi API nữa.

});
</script>