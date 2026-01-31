<?php
$page_title = 'Order Service';
require_once '../config/header.php';
require_once '../config/database.php';
require_once '../config/api_config.php';
// require_once '../config/exchange_rate.php'; // Không cần thiết nữa

// Yếu tố tăng giá (1.20 tương đương với tăng 20%)
define('PRICE_MARKUP_FACTOR', 1.20);

// Get service ID from URL
$serviceId = isset($_GET['service']) ? intval($_GET['service']) : 0;

// Lấy tỷ giá hối đoái - KHÔNG CẦN NỮA
// $exchangeRate = getExchangeRate(); 

// Function to get service details
function getServiceDetails($serviceId) {
    $postData = [
        'key' => API_KEY,
        'action' => 'services'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $services = json_decode($response, true);
        if (is_array($services)) {
            foreach ($services as $service) {
                if ($service['service'] == $serviceId) {
                    return $service;
                }
            }
        }
    }
    return null;
}

// Function to place order
function placeOrder($serviceId, $link, $quantity = null, $comments = null, $list = null, $suggest = null, $search = null) {
    $postData = [
        'key' => API_KEY,
        'action' => 'add',
        'service' => $serviceId,
        'link' => $link
    ];

    if ($quantity !== null) $postData['quantity'] = $quantity;
    if ($comments !== null) $postData['comments'] = $comments;
    if ($list !== null) $postData['list'] = $list;
    if ($suggest !== null) $postData['suggest'] = $suggest;
    if ($search !== null) $postData['search'] = $search;

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

// Handle form submission
$orderResult = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $serviceId = intval($_POST['service']);
    $serviceType = $_POST['service_type'] ?? 'Default';
    $link = trim($_POST['link']);
    
    // Get service details for price calculation
    $service = getServiceDetails($serviceId);
    
    if (!$service) {
        $error = 'Service not found';
    } elseif (empty($link)) {
        $error = 'Please enter link';
    } else {
        $quantity = null;
        $comments = null;
        $list = null;
        $suggest = null;
        $search = null;
        
        // Rate từ API là USD per 1000
        $rateUSDPer1000 = floatval($service['rate']);
        
        // Áp dụng tăng giá 20%
        $markedUpRateUSDPer1000 = $rateUSDPer1000 * PRICE_MARKUP_FACTOR;
        
        // Giá per 1 đơn vị
        $rateUSDPerUnit = $markedUpRateUSDPer1000 / 1000;
        
        $totalPrice = 0;

        // Process based on service type
        switch ($serviceType) {
            case 'Default':
                $quantity = intval($_POST['quantity']);
                if ($quantity < $service['min'] || $quantity > $service['max']) {
                    $error = "Quantity must be between {$service['min']} and {$service['max']}";
                }
                // Tính: (rate per 1000 / 1000) * quantity
                $totalPrice = $rateUSDPerUnit * $quantity;
                break;

            case 'Package':
                // Package là giá cố định (rate per 1000)
                $totalPrice = $markedUpRateUSDPer1000;
                break;

            case 'Custom Comments':
                $quantity = intval($_POST['quantity']);
                $comments = trim($_POST['comments']);
                if ($quantity < $service['min'] || $quantity > $service['max']) {
                    $error = "Quantity must be between {$service['min']} and {$service['max']}";
                } elseif (empty($comments)) {
                    $error = 'Please enter comments list';
                }
                $totalPrice = $rateUSDPerUnit * $quantity;
                break;

            case 'Special (1DG)':
                $quantity = intval($_POST['quantity']);
                $list = trim($_POST['list']);
                if ($quantity < $service['min'] || $quantity > $service['max']) {
                    $error = "Quantity must be between {$service['min']} and {$service['max']}";
                } elseif (empty($list)) {
                    $error = 'Please enter list';
                }
                $totalPrice = $rateUSDPerUnit * $quantity;
                break;

            case 'Special 1 (1DG)':
                $quantity = intval($_POST['quantity']);
                $suggest = trim($_POST['suggest'] ?? '');
                $search = trim($_POST['search'] ?? '');
                
                if ($quantity < $service['min'] || $quantity > $service['max']) {
                    $error = "Quantity must be between {$service['min']} and {$service['max']}";
                } elseif (empty($suggest) && empty($search)) {
                    $error = 'Please enter suggest OR search keywords';
                }
                $totalPrice = $rateUSDPerUnit * $quantity;
                break;
        }

        // Check balance (Giả sử $user['sodu'] bây giờ là USD)
        if (!$error && $user['sodu'] < $totalPrice) {
            $error = "Insufficient balance! Required: $" . number_format($totalPrice, 2) . ". Current: $" . number_format($user['sodu'], 2);
        }

        // Place order if no errors
        if (!$error) {
            $result = placeOrder($serviceId, $link, $quantity, $comments, $list, $suggest, $search);
            
            if ($result && isset($result['order'])) {
                $orderResult = $result;
                
                // Save to database
                $mysqli->begin_transaction();
                
                try {
                    // Insert order
                    $stmt = $mysqli->prepare("
                        INSERT INTO orders 
                        (order_id, user_id, service_id, service_name, service_type, link, quantity, price, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                    ");
                    
                    $stmt->bind_param(
                        "siisssid",
                        $result['order'],
                        $user['id'],
                        $serviceId,
                        $service['name'],
                        $serviceType,
                        $link,
                        $quantity,
                        $totalPrice
                    );
                    $stmt->execute();
                    
                    // Update user balance
                    $updateBalance = $mysqli->prepare("UPDATE Users SET sodu = sodu - ? WHERE id = ?");
                    $updateBalance->bind_param("di", $totalPrice, $user['id']);
                    $updateBalance->execute();
                    
                    // Insert transaction
                    $transContent = 'Order: ' . $service['name'] . ' - Order ID: ' . $result['order'];
                    
                    $insertTrans = $mysqli->prepare("
                        INSERT INTO Transactions (user_id, amount, type, content) 
                        VALUES (?, ?, 'order', ?)
                    ");
                    $insertTrans->bind_param("ids", $user['id'], $totalPrice, $transContent);
                    $insertTrans->execute();
                    
                    $mysqli->commit();
                    
                    // Update user balance in session
                    $user['sodu'] -= $totalPrice;
                    
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $error = 'Error saving order: ' . $e->getMessage();
                    $orderResult = null;
                }
                
            } elseif ($result && isset($result['error'])) {
                $error = $result['error'];
            } else {
                $error = 'Error occurred while placing order';
            }
        }
    }
}

// Get service details if service ID is provided
$service = null;
if ($serviceId > 0) {
    $service = getServiceDetails($serviceId);
    if ($service) {
        // Rate từ API (USD per 1000) và áp dụng tăng giá
        $service['rate_usd_per_1000'] = floatval($service['rate']) * PRICE_MARKUP_FACTOR;
        
        // Giá per 1 đơn vị
        $service['rate_usd_per_unit'] = $service['rate_usd_per_1000'] / 1000;
        
        // Giữ để tương thích (không cần thiết nhưng để an toàn)
        $service['rate_usd'] = $service['rate_usd_per_1000'];
    }
}

require_once '../config/sidebar.php';
?>

<style>
    /* CSS giữ nguyên, không thay đổi */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    .page-header p {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }
    .order-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .service-info-card {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .service-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }
    .service-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: white;
        font-size: 24px;
    }
    .service-details h2 {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-color);
        margin: 0 0 5px 0;
    }
    .service-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #667eea;
        margin-left: 10px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .info-item {
        background-color: var(--input-bg);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }
    .info-label {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 5px;
        display: block;
    }
    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-color);
    }
    .balance-warning {
        background: linear-gradient(135deg, #fff3cd, #fff8e1);
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    html.dark .balance-warning {
        background: linear-gradient(135deg, #3d3417, #4a3f1e);
    }
    .balance-warning i {
        font-size: 24px;
        color: #ffc107;
    }
    .balance-info {
        flex: 1;
    }
    .balance-amount {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
    }
    .order-form-card {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-color);
        margin-bottom: 8px;
        display: block;
    }
    .form-label .required {
        color: #e74c3c;
        margin-left: 3px;
    }
    .form-input, .form-textarea {
        width: 100%;
        padding: 12px 15px;
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 14px;
        transition: all 0.2s;
        font-family: inherit;
    }
    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .form-help {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 5px;
    }
    .instruction-box {
        background: linear-gradient(135deg, #e3f2fd, #e8f4fd);
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
    }
    html.dark .instruction-box {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }
    .instruction-box p {
        margin: 0 0 8px 0;
        font-size: 13px;
        color: var(--text-color);
        line-height: 1.6;
    }
    .instruction-box p:last-child {
        margin-bottom: 0;
    }
    .cost-calculator {
        background: linear-gradient(135deg, #e8f4fd, #f0f9ff);
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }
    html.dark .cost-calculator {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }
    .cost-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .cost-row:last-child {
        margin-bottom: 0;
        padding-top: 8px;
        border-top: 1px solid rgba(102, 126, 234, 0.2);
        font-weight: 600;
        font-size: 16px;
    }
    .submit-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
    }
    .submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    html.dark .alert-success {
        background-color: #1e4620;
        border-color: #2d5f2f;
        color: #9fdf9f;
    }
    html.dark .alert-error {
        background-color: #4d1f1f;
        border-color: #6b2929;
        color: #f8b4b4;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #667eea;
        text-decoration: none;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .service-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="order-container">
    <a href="/orders" class="back-link">
        <i class="bi bi-arrow-left"></i>
        <?php echo $lang === 'vi' ? 'Quay lại danh sách dịch vụ' : 'Back to services'; ?>
    </a>

    <div class="page-header">
        <h1>
            <i class="bi bi-cart-plus"></i>
            <?php echo $lang === 'vi' ? 'Đặt Hàng' : 'Place Order'; ?>
        </h1>
        <p><?php echo $lang === 'vi' ? 'Điền thông tin để đặt hàng' : 'Fill in information to place order'; ?></p>
    </div>

    <!-- Đã xóa phần hiển thị tỷ giá -->

    <div class="balance-warning">
        <i class="bi bi-wallet2"></i>
        <div class="balance-info">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">
                <?php echo $lang === 'vi' ? 'Số dư hiện tại' : 'Current Balance'; ?>
            </div>
            <div class="balance-amount">
                <?php echo '$' . number_format($user['sodu'], 2); // Luôn hiển thị USD ?>
            </div>
        </div>
    </div>

    <?php if ($orderResult): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill" style="font-size: 20px; flex-shrink: 0;"></i>
        <div>
            <strong>Order placed successfully!</strong><br>
            Order ID: <strong><?php echo $orderResult['order']; ?></strong><br>
            <a href="/orderstatus">View orders</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 20px; flex-shrink: 0;"></i>
        <div>
            <strong>Error!</strong><br>
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($service): ?>
    <div class="service-info-card">
        <div class="service-header">
            <div class="service-icon">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="service-details">
                <h2>
                    <?php echo htmlspecialchars($service['name']); ?>
                    <span class="service-type-badge">
                        <i class="bi bi-tag-fill"></i>
                        <?php echo htmlspecialchars($service['type']); ?>
                    </span>
                </h2>
                <div style="font-size: 13px; color: var(--text-secondary);">
                    <i class="bi bi-folder"></i>
                    <?php echo htmlspecialchars($service['category'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-cash"></i>
                    Price / 1000
                </span>
                <div class="info-value">
                    <?php echo '$' . number_format($service['rate_usd_per_1000'], 4); // Luôn hiển thị USD ?>
                </div>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-tag"></i>
                    Price / unit
                </span>
                <div class="info-value">
                     <?php echo '$' . number_format($service['rate_usd_per_unit'], 6); // Luôn hiển thị USD ?>
                </div>
            </div>

            <?php if ($service['type'] !== 'Package'): ?>
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-arrow-down-circle"></i>
                    Minimum
                </span>
                <div class="info-value"><?php echo number_format($service['min']); ?></div>
            </div>

            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-arrow-up-circle"></i>
                    Maximum
                </span>
                <div class="info-value"><?php echo number_format($service['max']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="order-form-card">
        <form method="POST" id="orderForm">
            <input type="hidden" name="service" value="<?php echo $service['service']; ?>">
            <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($service['type']); ?>">

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-link-45deg"></i>
                    Link
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="link" 
                    class="form-input" 
                    placeholder="https://example.com/your-link"
                    required
                >
            </div>

            <?php if ($service['type'] === 'Default' || $service['type'] === 'Custom Comments' || $service['type'] === 'Special (1DG)' || $service['type'] === 'Special 1 (1DG)'): ?>
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-123"></i>
                    Quantity
                    <span class="required">*</span>
                </label>
                <input 
                    type="number" 
                    name="quantity" 
                    id="quantityInput"
                    class="form-input" 
                    min="<?php echo $service['min']; ?>"
                    max="<?php echo $service['max']; ?>"
                    value="<?php echo $service['min']; ?>"
                    required
                >
                <div class="form-help">
                    Min: <?php echo number_format($service['min']); ?> - Max: <?php echo number_format($service['max']); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($service['type'] === 'Custom Comments'): ?>
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-chat-left-text"></i>
                    Comments (one per line)
                    <span class="required">*</span>
                </label>
                <textarea name="comments" class="form-textarea" required></textarea>
            </div>
            <?php endif; ?>

            <?php if ($service['type'] === 'Special (1DG)'): ?>
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-list-ul"></i>
                    List
                    <span class="required">*</span>
                </label>
                <textarea name="list" class="form-textarea" required></textarea>
            </div>
            <?php endif; ?>

            <?php if ($service['type'] === 'Special 1 (1DG)'): ?>
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-lightbulb"></i>
                    Suggest
                </label>
                <textarea name="suggest" class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-search"></i>
                    Search
                </label>
                <textarea name="search" class="form-textarea"></textarea>
            </div>
            <div class="instruction-box">
                <p>Enter EITHER Suggest OR Search (not both)</p>
            </div>
            <?php endif; ?>

            <?php if ($service['type'] !== 'Package'): ?>
            <div class="cost-calculator">
                <h4 style="margin: 0 0 15px 0; font-size: 16px;">
                    <i class="bi bi-calculator"></i>
                    Cost Calculator
                </h4>
                <div class="cost-row">
                    <span>Price / unit:</span>
                    <span id="displayRate">
                        <?php echo '$' . number_format($service['rate_usd_per_unit'], 6); ?>
                    </span>
                </div>
                <div class="cost-row">
                    <span>Quantity:</span>
                    <span id="displayQuantity"><?php echo number_format($service['min']); ?></span>
                </div>
                <div class="cost-row">
                    <span><strong>Total:</strong></span>
                    <span id="totalCost" style="color: #667eea;">
                        <strong>
                            <?php 
                            // Tính initial total
                            $initialTotal = $service['rate_usd_per_unit'] * $service['min'];
                            echo '$' . number_format($initialTotal, 2);
                            ?>
                        </strong>
                    </span>
                </div>
            </div>
            <?php else: ?>
            <div class="cost-calculator">
                <h4 style="margin: 0 0 15px 0; font-size: 16px;">
                    <i class="bi bi-cash-stack"></i>
                    Package Price
                </h4>
                <div class="cost-row">
                    <span><strong>Total:</strong></span>
                    <span style="color: #667eea;">
                        <strong>
                            <?php echo '$' . number_format($service['rate_usd_per_1000'], 2); ?>
                        </strong>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" name="submit_order" class="submit-btn">
                <i class="bi bi-check-circle"></i>
                Place Order Now
            </button>
        </form>
    </div>

    <?php else: ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 20px;"></i>
        <div>
            <strong>Service not found!</strong><br>
            Please go back and select a service.
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đơn giá USD trên mỗi đơn vị
    const rate = <?php echo $service ? $service['rate_usd_per_unit'] : 0; ?>;

    const quantityInput = document.getElementById('quantityInput');
    const displayQuantity = document.getElementById('displayQuantity');
    const totalCost = document.getElementById('totalCost');

    // Hàm cập nhật giá tiền
    function calculateCost() {
        if (!quantityInput || !displayQuantity || !totalCost) return;

        const quantity = parseInt(quantityInput.value) || 0;
        displayQuantity.textContent = quantity.toLocaleString('en-US'); // Format số lượng

        const cost = rate * quantity;

        // Luôn hiển thị tiền USD
        totalCost.innerHTML = '<strong>$' + cost.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + '</strong>';
    }

    // Gắn sự kiện lắng nghe
    if (quantityInput) {
        quantityInput.addEventListener('input', calculateCost);
    }

    // Form validation
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const serviceType = document.querySelector('input[name="service_type"]').value;
            if (serviceType === 'Special 1 (1DG)') {
                const suggest = document.querySelector('textarea[name="suggest"]')?.value.trim() || '';
                const search = document.querySelector('textarea[name="search"]')?.value.trim() || '';
                if (!suggest && !search) {
                    e.preventDefault();
                    // Thay thế alert bằng một thông báo tốt hơn nếu có thể
                    alert('Please enter Suggest OR Search!'); 
                    return false;
                }
            }
        });
    }
    
    // Đã xóa toàn bộ code JS liên quan đến cập nhật tỷ giá
});
</script>

<?php require_once '../config/footer.php'; ?>
