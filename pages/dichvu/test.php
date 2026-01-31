<?php
// File test để debug order status
require_once __DIR__ . '/../../config/database.php';
define('ACCESS_ALLOWED', true);
require_once __DIR__ . '/../../config/api_config.php';

$orderId = '31882869'; // Order ID cần test

// Test 1: Gọi API
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

echo "<h2>🔍 DEBUG ORDER: {$orderId}</h2>";

echo "<h3>1. API Response:</h3>";
echo "<pre>";
echo "HTTP Code: {$httpCode}\n";
echo "Raw Response: " . $response . "\n";
$apiData = json_decode($response, true);
echo "Decoded Data: ";
print_r($apiData);
echo "</pre>";

// Test 2: Kiểm tra trong database
$conn = $mysqli;
$checkSql = "SELECT order_id, status, start_count, remains, created_at, updated_at FROM orders WHERE order_id = ?";
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo "<h3>2. Database Current Status:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}

// Test 3: Thử update
if ($apiData && isset($apiData['status'])) {
    echo "<h3>3. Status Mapping:</h3>";
    echo "<pre>";
    
    $apiStatus = $apiData['status'];
    $statusMap = [
        'Pending' => 'Pending', 
        'Processing' => 'Processing', 
        'In progress' => 'Processing',
        'In Progress' => 'Processing', 
        'Completed' => 'Completed', 
        'Partial' => 'Partial',
        'Canceled' => 'Canceled', 
        'Cancelled' => 'Canceled', 
        'Refunded' => 'Refunded'
    ];
    
    $newStatus = $statusMap[$apiStatus] ?? 'Pending';
    
    echo "API Status: {$apiStatus}\n";
    echo "Mapped Status: {$newStatus}\n";
    echo "Start Count: " . ($apiData['start_count'] ?? 'N/A') . "\n";
    echo "Remains: " . ($apiData['remains'] ?? 'N/A') . "\n";
    echo "</pre>";
    
    // Test update
    echo "<h3>4. Test Update Query:</h3>";
    $startCount = isset($apiData['start_count']) ? intval($apiData['start_count']) : null;
    $remains = isset($apiData['remains']) ? intval($apiData['remains']) : null;
    
    $updateOrderSql = "UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE order_id = ?";
    if ($stmt = $conn->prepare($updateOrderSql)) {
        $stmt->bind_param('siis', $newStatus, $startCount, $remains, $orderId);
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        echo "<pre>";
        echo "Update Success: " . ($success ? 'YES' : 'NO') . "\n";
        echo "Affected Rows: {$affectedRows}\n";
        echo "</pre>";
        
        if ($success && $affectedRows > 0) {
            echo "<p style='color: green; font-weight: bold;'>✅ Update thành công!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Update thất bại hoặc không có thay đổi!</p>";
        }
    }
    
    // Check lại database
    if ($stmt = $conn->prepare($checkSql)) {
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo "<h3>5. Database After Update:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ API không trả về data hợp lệ!</p>";
}
?>