<?php
// File: /config/force_update.php

// Bật hiển thị lỗi để dễ debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/image_updater.php';

echo "Bắt đầu quá trình cập nhật thủ công...\n\n";

$updater = new ProductImageUpdater();
$result = $updater->updateImages(); // Gọi thẳng hàm updateImages()

echo "\n-------------------------------------\n";

if ($result) {
    echo "✅ Quá trình cập nhật hoàn tất thành công!\n";
    echo "Vui lòng kiểm tra lại file productImages.php.\n";
} else {
    echo "❌ Quá trình cập nhật thất bại!\n";
    echo "Vui lòng kiểm tra file image_update.log để xem chi tiết lỗi.\n";
}
?>