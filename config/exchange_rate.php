<?php
/**
 * Exchange Rate API Handler
 * Lấy tỷ giá USD to VND và áp dụng lợi nhuận
 */

// --- CẤU HÌNH LỢI NHUẬN ---
// Đặt phần trăm lợi nhuận bạn muốn thêm vào giá gốc.
// Ví dụ: 20 nghĩa là tăng giá lên 20%.
$profit_margin_percentage = 20; // << BẠN CÓ THỂ THAY ĐỔI SỐ NÀY

// Cache tỷ giá trong session để tránh gọi API liên tục
function getExchangeRate() {
    // ... (Toàn bộ hàm này giữ nguyên, không cần thay đổi) ...
    if (isset($_SESSION['exchange_rate']) && 
        isset($_SESSION['exchange_rate_time']) && 
        (time() - $_SESSION['exchange_rate_time']) < 3600) {
        return $_SESSION['exchange_rate'];
    }
    
    $rate = fetchExchangeRate();
    
    if ($rate) {
        $_SESSION['exchange_rate'] = $rate;
        $_SESSION['exchange_rate_time'] = time();
        return $rate;
    }
    
    return 25000;
}

function fetchExchangeRate() {
    // ... (Toàn bộ hàm này giữ nguyên, không cần thay đổi) ...
    $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['rates']['VND'])) {
            return $data['rates']['VND'];
        }
    }
    
    return fetchVietcombankRate();
}

function fetchVietcombankRate() {
    // ... (Toàn bộ hàm này giữ nguyên, không cần thay đổi) ...
    $apiUrl = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx?b=10';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        try {
            $xml = simplexml_load_string($response);
            if ($xml) {
                foreach ($xml->Exrate as $rate) {
                    if ((string)$rate['CurrencyCode'] === 'USD') {
                        $sell = str_replace(',', '', (string)$rate['Sell']);
                        return floatval($sell);
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore error
        }
    }
    
    return 25000;
}

/**
 * Chuyển đổi giá từ USD sang VND và ÁP DỤNG LỢI NHUẬN
 */
function convertUSDtoVND($usdAmount) {
    global $profit_margin_percentage; // Lấy biến lợi nhuận đã cấu hình ở trên

    // 1. Lấy tỷ giá gốc
    $rate = getExchangeRate();
    
    // 2. Quy đổi ra giá gốc VND
    $baseVndAmount = $usdAmount * $rate;
    
    // 3. Cộng thêm % lợi nhuận vào giá gốc để ra giá bán cuối cùng
    $finalVndAmount = $baseVndAmount * (1 + ($profit_margin_percentage / 100));
    
    return $finalVndAmount;
}

/**
 * Chuyển đổi giá từ VND sang USD (hàm này giữ nguyên)
 */
function convertVNDtoUSD($vndAmount) {
    // Lưu ý: Hàm này chỉ quy đổi theo tỷ giá gốc, không tính lợi nhuận ngược.
    // Phù hợp để hiển thị cho người dùng biết số dư của họ tương đương bao nhiêu USD.
    $rate = getExchangeRate();
    if ($rate == 0) return 0; // Tránh lỗi chia cho 0
    return $vndAmount / $rate;
}

/**
 * Format giá theo ngôn ngữ (hàm này giữ nguyên)
 */
function formatPrice($price, $lang = 'vi') {
    // ... (Toàn bộ hàm này giữ nguyên, không cần thay đổi) ...
    if ($lang === 'vi') {
        return number_format($price, 0, ',', '.') . ' VND';
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}