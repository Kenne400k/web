<?php
// Tự động cập nhật trạng thái khi vào trang
$page_title = 'Product Orders Status';
require_once __DIR__ . '/../../config/header.php';
require_once __DIR__ . '/../../config/database.php';
define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/api_config.php';

$conn = $mysqli;
$current_user_id = $user['id'];

// Kiểm tra xem có cần cập nhật không (chỉ cập nhật khi không phải là POST request)
$shouldAutoUpdate = $_SERVER['REQUEST_METHOD'] !== 'POST';
    
function checkProductOrderStatusFromAPI($orderId) {
    $postData = [
        'key' => API_KEY,
        'action' => 'product_order_status',
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


function updateProductOrderInDB($orderId, $apiData) {
    global $conn, $current_user_id;
    
    $apiStatus = $apiData['status'] ?? 'Pending';
    $statusMap = [
        'Pending' => 'Pending',
        'Processing' => 'Processing',
        'In progress' => 'Processing',
        'In Progress' => 'Processing',
        'Completed' => 'Completed',
        'Partial' => 'Partial',
        'Canceled' => 'Canceled',
        'Cancelled' => 'Canceled'
    ];
    
    $newStatus = $statusMap[$apiStatus] ?? 'Pending';
    $result = isset($apiData['result']) ? $apiData['result'] : null;
    $remains = isset($apiData['remains']) ? intval($apiData['remains']) : 0;
    
    // Lấy thông tin đơn hàng hiện tại để kiểm tra trạng thái cũ
    $checkSql = "SELECT status, price, user_id, refunded FROM product_orders WHERE order_id = ?";
    $oldOrder = null;
    if ($stmt = $conn->prepare($checkSql)) {
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $oldOrder = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Nếu không tìm thấy đơn hàng hoặc đã hoàn tiền rồi thì return
    if (!$oldOrder || $oldOrder['refunded'] == 1) {
        return false;
    }
    
    $oldStatus = $oldOrder['status'];
    $orderPrice = floatval($oldOrder['price']);
    $userId = $oldOrder['user_id'];
    
    // Kiểm tra nếu trạng thái thay đổi từ Pending/Processing sang Canceled/Partial
    $needRefund = false;
    if (in_array($oldStatus, ['Pending', 'Processing']) && in_array($newStatus, ['Canceled', 'Partial'])) {
        $needRefund = true;
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Cập nhật đơn hàng
        if ($result !== null) {
            $sql = "UPDATE product_orders SET 
                    status = ?, 
                    remains = ?,
                    result = ?,
                    updated_at = NOW() 
                    WHERE order_id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                if (is_array($result)) {
                    $result = json_encode($result);
                }
                $stmt->bind_param('siss', $newStatus, $remains, $result, $orderId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $sql = "UPDATE product_orders SET 
                    status = ?, 
                    remains = ?,
                    updated_at = NOW() 
                    WHERE order_id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('sis', $newStatus, $remains, $orderId);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Nếu cần hoàn tiền
        if ($needRefund && $orderPrice > 0) {
            $updateBalanceSql = "UPDATE Users SET sodu = sodu + ? WHERE id = ?";
            if ($stmt = $conn->prepare($updateBalanceSql)) {
                $stmt->bind_param('di', $orderPrice, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            $transactionSql = "INSERT INTO Transactions (user_id, amount, type, content, created_at) 
                              VALUES (?, ?, 'refund', ?, NOW())";
            if ($stmt = $conn->prepare($transactionSql)) {
                $description = "Hoàn tiền đơn hàng #" . $orderId . " - Trạng thái: " . $newStatus;
                $stmt->bind_param('ids', $userId, $orderPrice, $description);
                $stmt->execute();
                $stmt->close();
            }
            
            $markRefundSql = "UPDATE product_orders SET refunded = 1 WHERE order_id = ?";
            if ($stmt = $conn->prepare($markRefundSql)) {
                $stmt->bind_param('s', $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            $logSql = "INSERT INTO activity_logs (user_id, action, details, created_at) 
                      VALUES (?, 'auto_refund', ?, NOW())";
            if ($stmt = $conn->prepare($logSql)) {
                $details = json_encode([
                    'order_id' => $orderId,
                    'amount' => $orderPrice,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Auto refund due to order cancellation/failure'
                ]);
                $stmt->bind_param('is', $userId, $details);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Refund error for order {$orderId}: " . $e->getMessage());
        return false;
    }
}

// Function để lấy result từ API
function getProductOrderResult($orderId) {
    $postData = [
        'key' => API_KEY,
        'action' => 'result_product',
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

// Function kiểm tra loại media
function detectMediaType($url) {
    $url = strtolower(trim($url));
    
    // Image extensions
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'];
    // Video extensions
    $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv', 'm3u8'];
    
    // Check by extension
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    
    if (in_array($ext, $imageExts)) return 'image';
    if (in_array($ext, $videoExts)) return 'video';
    
    // Check by URL patterns
    if (preg_match('/(\.jpg|\.jpeg|\.png|\.gif|\.webp)/i', $url)) return 'image';
    if (preg_match('/(\.mp4|\.webm|\.m3u8)/i', $url)) return 'video';
    
    // Check by domain patterns
    if (preg_match('/(imgur|imgbb|i\.imgur|cdn|image|img|photo)/i', $url)) return 'image';
    if (preg_match('/(video|stream|play|watch|youtube|vimeo)/i', $url)) return 'video';
    
    // Check if URL is valid
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return 'link';
    }
    
    return 'text';
}

// Xử lý tìm kiếm thủ công
$searchOrderId = '';
$searchResult = null;
$searchError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_order'])) {
    $searchOrderId = trim($_POST['order_id']);
    
    if (!empty($searchOrderId)) {
        $sql = "SELECT * FROM product_orders WHERE order_id = ? AND user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('si', $searchOrderId, $current_user_id);
            $stmt->execute();
            $dbOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($dbOrder) {
                $apiData = checkProductOrderStatusFromAPI($searchOrderId);
                
                if ($apiData && !isset($apiData['error'])) {
                    updateProductOrderInDB($searchOrderId, $apiData);
                    $searchResult = array_merge($dbOrder, $apiData);
                } else {
                    $searchError = $lang === 'vi' ? 'Không thể kết nối đến API' : 'Unable to connect to API';
                }
            } else {
                $searchError = $lang === 'vi' ? 'Không tìm thấy đơn hàng hoặc đơn hàng không thuộc về bạn' : 'Order not found or does not belong to you';
            }
        }
    }
}

// Xử lý xem kết quả
$viewResultOrderId = '';
$resultData = null;
$resultError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_result'])) {
    $viewResultOrderId = trim($_POST['result_order_id']);
    
    if (!empty($viewResultOrderId)) {
        $sql = "SELECT * FROM product_orders WHERE order_id = ? AND user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('si', $viewResultOrderId, $current_user_id);
            $stmt->execute();
            $dbOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($dbOrder) {
                $apiResult = getProductOrderResult($viewResultOrderId);
                
                if ($apiResult && isset($apiResult['result'])) {
                    $resultData = $apiResult['result'];
                } else {
                    $resultError = $lang === 'vi' ? 'Không thể lấy kết quả từ API' : 'Unable to get result from API';
                }
            } else {
                $resultError = $lang === 'vi' ? 'Đơn hàng không tồn tại' : 'Order not found';
            }
        }
    }
}

// Lấy danh sách đơn hàng của user
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) as total FROM product_orders WHERE user_id = ?";
if ($stmt = $conn->prepare($countSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $totalOrders = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}
$totalPages = ceil($totalOrders / $perPage);

$sql = "SELECT * FROM product_orders 
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

// Tự động cập nhật
$updatedCount = 0;
if ($shouldAutoUpdate && !empty($orders)) {
    foreach ($orders as &$order) {
        if (in_array($order['status'], ['Pending', 'Processing'])) {
            $apiData = checkProductOrderStatusFromAPI($order['order_id']);
            
            if ($apiData && !isset($apiData['error'])) {
                $updated = updateProductOrderInDB($order['order_id'], $apiData);
                
                if ($updated) {
                    $updatedCount++;
                }
                
                $order['status'] = $apiData['status'] ?? $order['status'];
                $order['remains'] = $apiData['remains'] ?? $order['remains'];
                $order['result'] = $apiData['result'] ?? $order['result'];
            }
            
            usleep(100000);
        }
    }
    unset($order);
}

$statsSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('Pending', 'Processing') THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('Canceled', 'Partial') THEN 1 ELSE 0 END) as failed
             FROM product_orders WHERE user_id = ?";
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
        ]
    ];
    
    return $badges[$statusLower] ?? [
        'color' => '#6c757d', 
        'icon' => 'question-circle', 
        'text' => ucfirst($status)
    ];
}

require_once '../../config/sidebar.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
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
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-download {
        background: linear-gradient(135deg, #f093fb, #f5576c);
        color: white;
        margin-top: 10px;
        width: 100%;
        justify-content: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    .orders-table {
        background: var(--content-bg);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-color);
    }

    .table-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 15px 20px;
        font-weight: 600;
        display: grid;
        grid-template-columns: 120px 1fr 150px 100px 100px 150px 100px;
        gap: 15px;
        align-items: center;
    }

    html.dark .table-header {
        background: linear-gradient(135deg, #2d3748, #1a202c);
    }

    .table-row {
        padding: 15px 20px;
        display: grid;
        grid-template-columns: 120px 1fr 150px 100px 100px 150px 100px;
        gap: 15px;
        align-items: center;
        border-top: 1px solid var(--border-color);
        transition: background 0.2s;
    }

    .table-row:hover {
        background: var(--input-bg);
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
        background: #667eea;
        color: white;
        border-color: #667eea;
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

    .alert-info {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        overflow-y: auto;
    }

    .modal-content {
        background-color: var(--content-bg);
        margin: 2% auto;
        padding: 0;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .modal-header h2 {
        margin: 0;
        color: white !important;
        font-size: 20px;
    }

    .close {
        color: white;
        font-size: 32px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        transition: transform 0.2s;
    }

    .close:hover {
        transform: scale(1.2);
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 100px);
    }

    .result-item {
        background: var(--input-bg);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid var(--border-color);
    }

    .result-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .result-item-number {
        font-weight: bold;
        color: #667eea;
        font-size: 16px;
    }

    .result-media-container {
        margin: 15px 0;
        border-radius: 8px;
        overflow: hidden;
        background: #000;
    }

    .result-media-container img {
        width: 100%;
        height: auto;
        display: block;
        max-height: 500px;
        object-fit: contain;
    }

    .result-media-container video {
        width: 100%;
        height: auto;
        display: block;
        max-height: 500px;
    }

    .result-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #667eea;
        text-decoration: none;
        font-family: monospace;
        word-break: break-all;
        padding: 8px 12px;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 6px;
        transition: all 0.3s;
    }

    .result-link:hover {
        background: rgba(102, 126, 234, 0.2);
        transform: translateX(5px);
    }

    .result-text {
        font-family: monospace;
        font-size: 14px;
        color: var(--text-color);
        word-break: break-all;
        padding: 10px;
        background: rgba(0,0,0,0.3);
        border-radius: 6px;
    }

    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .media-grid-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: #000;
        cursor: pointer;
        transition: transform 0.3s;
    }

    .media-grid-item:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .media-grid-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }

    .media-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .media-grid-item:hover .media-overlay {
        opacity: 1;
    }

    .media-overlay i {
        font-size: 48px;
        color: white;
    }

    .download-all-btn {
        position: sticky;
        top: 0;
        z-index: 10;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .table-header, .table-row {
            grid-template-columns: 1fr;
        }
        
        .search-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-form input {
            min-width: 100%;
        }

        .modal-content {
            width: 95%;
            margin: 5% auto;
        }

        .media-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-box-seam"></i> <?php echo $lang === 'vi' ? 'Đơn Hàng Sản Phẩm' : 'Product Orders'; ?></h1>
    </div>

    <?php if ($shouldAutoUpdate && $updatedCount > 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <?php echo $lang === 'vi' ? "Đã tự động cập nhật {$updatedCount} đơn hàng từ API" : "Auto updated {$updatedCount} orders from API"; ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <i class="bi bi-box"></i>
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
                <p><?php echo $lang === 'vi' ? 'Lỗi/Huỷ' : 'Failed/Canceled'; ?></p>
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
            </button>
        </form>
    </div>

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

    <?php if ($resultError): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($resultError); ?>
        </div>
    <?php endif; ?>

    <div class="orders-table">
        <div class="table-header">
            <div><?php echo $lang === 'vi' ? 'Order ID' : 'Order ID'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Sản phẩm' : 'Product'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Trạng thái' : 'Status'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Số lượng' : 'Quantity'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Còn lại' : 'Remains'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Giá' : 'Price'; ?></div>
            <div><?php echo $lang === 'vi' ? 'Thao tác' : 'Action'; ?></div>
        </div>

        <?php foreach ($orders as $order): ?>
            <?php $badge = getStatusBadge($order['status'], $lang); ?>
            <div class="table-row">
                <div style="font-weight: 600; color: #667eea;"><?php echo htmlspecialchars($order['order_id']); ?></div>
                <div style="font-size: 13px;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div>
                    <span class="status-badge" style="background-color: <?php echo $badge['color']; ?>;">
                        <i class="bi bi-<?php echo $badge['icon']; ?>"></i>
                        <?php echo $badge['text']; ?>
                    </span>
                </div>
                <div><?php echo number_format($order['quantity']); ?></div>
                <div style="font-size: 13px; color: var(--text-muted);">
                    <?php echo number_format($order['remains']); ?>
                </div>
                <div style="font-weight: 600;"><?php echo number_format($order['price'], 2); ?> $</div>
                <div>
                    <?php if ($order['status'] === 'Completed' && $order['result']): ?>
                        <button class="btn btn-info" style="padding: 6px 12px; font-size: 12px;" onclick="viewResult('<?php echo htmlspecialchars($order['order_id']); ?>')">
                            <i class="bi bi-eye"></i> <?php echo $lang === 'vi' ? 'Xem' : 'View'; ?>
                        </button>
                    <?php else: ?>
                        <span style="font-size: 12px; color: var(--text-muted);">-</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

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

<!-- Modal để hiển thị kết quả -->
<div id="resultModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="bi bi-file-text"></i> <?php echo $lang === 'vi' ? 'Kết Quả Đơn Hàng' : 'Order Result'; ?></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="resultContent">
            <div style="text-align: center; padding: 20px;">
                <i class="bi bi-arrow-repeat" style="font-size: 32px; animation: spin 1s linear infinite;"></i>
                <p><?php echo $lang === 'vi' ? 'Đang tải...' : 'Loading...'; ?></p>
            </div>
        </div>
    </div>
</div>

<script>
// ===== SMART MEDIA VIEWER =====
function detectMediaType(url) {
    const urlLower = url.toLowerCase().trim();
    
    // Image extensions
    const imageExts = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg', '.ico'];
    // Video extensions  
    const videoExts = ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.mkv', '.flv', '.m3u8'];
    
    // Check extensions
    for (let ext of imageExts) {
        if (urlLower.includes(ext)) return 'image';
    }
    for (let ext of videoExts) {
        if (urlLower.includes(ext)) return 'video';
    }
    
    // Check domain patterns
    if (/(imgur|imgbb|i\.imgur|cdn.*image|.*\/img\/|photo|picture)/i.test(urlLower)) return 'image';
    if (/(video|stream|\.mp4|\.webm|watch|youtube|vimeo)/i.test(urlLower)) return 'video';
    
    // Check if valid URL
    try {
        new URL(url);
        return 'link';
    } catch {
        return 'text';
    }
}

function createMediaElement(url, index) {
    const type = detectMediaType(url);
    const sanitizedUrl = url.trim();
    
    let html = '<div class="result-item">';
    html += '<div class="result-item-header">';
    html += `<span class="result-item-number">#${index + 1}</span>`;
    html += '</div>';
    
    if (type === 'image') {
        html += '<div class="result-media-container">';
        html += `<img src="${sanitizedUrl}" alt="Result ${index + 1}" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'100\\' height=\\'100\\'%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' fill=\\'%23999\\'%3EImage Error%3C/text%3E%3C/svg%3E';">`;
        html += '</div>';
        html += `<a href="${sanitizedUrl}" download="result_${index + 1}" target="_blank" class="btn btn-download">`;
        html += '<i class="bi bi-download"></i> <?php echo $lang === 'vi' ? 'Tải xuống ảnh' : 'Download Image'; ?>';
        html += '</a>';
        
    } else if (type === 'video') {
        html += '<div class="result-media-container">';
        html += `<video controls preload="metadata">`;
        html += `<source src="${sanitizedUrl}" type="video/mp4">`;
        html += 'Your browser does not support the video tag.';
        html += '</video>';
        html += '</div>';
        html += `<a href="${sanitizedUrl}" download="result_${index + 1}" target="_blank" class="btn btn-download">`;
        html += '<i class="bi bi-download"></i> <?php echo $lang === 'vi' ? 'Tải xuống video' : 'Download Video'; ?>';
        html += '</a>';
        
    } else if (type === 'link') {
        html += `<a href="${sanitizedUrl}" target="_blank" class="result-link">`;
        html += '<i class="bi bi-link-45deg"></i>';
        html += `<span>${sanitizedUrl}</span>`;
        html += '</a>';
        html += `<a href="${sanitizedUrl}" download="result_${index + 1}" target="_blank" class="btn btn-download">`;
        html += '<i class="bi bi-download"></i> <?php echo $lang === 'vi' ? 'Tải xuống' : 'Download'; ?>';
        html += '</a>';
        
    } else {
        html += `<div class="result-text">${sanitizedUrl}</div>`;
    }
    
    html += '</div>';
    return html;
}

function downloadAll(urls) {
    if (!urls || urls.length === 0) {
        alert('<?php echo $lang === 'vi' ? 'Không có file để tải' : 'No files to download'; ?>');
        return;
    }
    
    urls.forEach((url, index) => {
        setTimeout(() => {
            const a = document.createElement('a');
            a.href = url;
            a.download = `result_${index + 1}`;
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }, index * 500); // Delay 500ms between downloads
    });
}

// ===== XỬ LÝ MODAL KẾT QUẢ =====
function viewResult(orderId) {
    const modal = document.getElementById('resultModal');
    const resultContent = document.getElementById('resultContent');
    
    modal.style.display = 'block';
    resultContent.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-arrow-repeat" style="font-size: 24px; animation: spin 1s linear infinite;"></i><p><?php echo $lang === 'vi' ? 'Đang tải kết quả...' : 'Loading result...'; ?></p></div>';
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const orderInput = document.createElement('input');
    orderInput.type = 'hidden';
    orderInput.name = 'result_order_id';
    orderInput.value = orderId;
    
    const submitInput = document.createElement('input');
    submitInput.type = 'hidden';
    submitInput.name = 'view_result';
    submitInput.value = '1';
    
    form.appendChild(orderInput);
    form.appendChild(submitInput);
    document.body.appendChild(form);
    form.submit();
}

function closeModal() {
    document.getElementById('resultModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('resultModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// ===== HIỂN THỊ KẾT QUẢ NẾU CÓ =====
<?php if ($resultData): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('resultModal');
    const resultContent = document.getElementById('resultContent');
    
    modal.style.display = 'block';
    
    let html = '<div style="padding: 10px;">';
    
    <?php if (is_array($resultData) && count($resultData) > 0): ?>
        const results = <?php echo json_encode($resultData); ?>;
        
        // Add Download All button
        html += '<button onclick="downloadAll(' + JSON.stringify(results) + ')" class="btn btn-download download-all-btn">';
        html += '<i class="bi bi-download"></i> <?php echo $lang === 'vi' ? 'Tải tất cả (' . count($resultData) . ' files)' : 'Download All (' . count($resultData) . ' files)'; ?>';
        html += '</button>';
        
        // Display each result with smart media detection
        results.forEach((item, index) => {
            html += createMediaElement(item, index);
        });
    <?php else: ?>
        html += '<div class="alert alert-error">';
        html += '<i class="bi bi-exclamation-circle"></i> <?php echo $lang === 'vi' ? 'Không có dữ liệu' : 'No data available'; ?>';
        html += '</div>';
    <?php endif; ?>
    
    html += '</div>';
    resultContent.innerHTML = html;
});
<?php endif; ?>

// ===== TỰ ĐỘNG RELOAD TRANG NẾU CÓ ĐƠN ĐANG XỬ LÝ =====
<?php 
$hasProcessing = false;
foreach ($orders as $order) {
    if (in_array($order['status'], ['Pending', 'Processing'])) {
        $hasProcessing = true;
        break;
    }
}

if ($hasProcessing): 
?>
let autoReloadTimer;
let userInteracted = false;

function startAutoReload() {
    autoReloadTimer = setTimeout(function() {
        if (!userInteracted) {
            console.log('Auto reloading page to update orders...');
            window.location.reload();
        }
    }, 30000); // 30 seconds
}

function cancelAutoReload() {
    userInteracted = true;
    clearTimeout(autoReloadTimer);
}

window.addEventListener('load', startAutoReload);

['click', 'scroll', 'keypress', 'mousemove'].forEach(function(event) {
    document.addEventListener(event, cancelAutoReload, { once: true });
});

// AJAX Auto Update
function ajaxUpdateOrders() {
    console.log('AJAX updating orders...');
    
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const newTable = doc.querySelector('.orders-table');
        const currentTable = document.querySelector('.orders-table');
        if (newTable && currentTable) {
            currentTable.innerHTML = newTable.innerHTML;
        }
        
        const newStats = doc.querySelectorAll('.stat-info h3');
        const currentStats = document.querySelectorAll('.stat-info h3');
        if (newStats.length === currentStats.length) {
            newStats.forEach((stat, index) => {
                currentStats[index].textContent = stat.textContent;
            });
        }
    })
    .catch(error => {
        console.error('AJAX update failed:', error);
    });
}

setInterval(ajaxUpdateOrders, 45000);
<?php endif; ?>

console.log('%c🎬 Smart Media Viewer Active', 'color: #667eea; font-weight: bold; font-size: 14px;');
console.log('Auto-detect: Images, Videos, Links');
console.log('Download: Individual & Bulk');
</script>

<?php require_once __DIR__ . '/../../config/footer.php'; ?>