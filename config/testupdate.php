<?php
/**
 * Script test để chạy update trực tiếp và xem lỗi
 * Đặt file này cùng thư mục với image_updater.php
 * Chạy: php test_update.php
 */

// Bật hiển thị lỗi
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "🧪 Testing ProductImageUpdater...\n";
echo "=================================\n\n";

// Load class
require_once __DIR__ . '/image_updater.php';

$updater = new ProductImageUpdater();

// Kiểm tra các file
echo "📁 Checking files...\n";
echo "- Cache file: " . (__DIR__ . '/productImages.php') . "\n";
echo "- Cache exists: " . (file_exists(__DIR__ . '/productImages.php') ? '✅ YES' : '❌ NO') . "\n";
echo "- Lock file: " . (__DIR__ . '/productImages.lock') . "\n";
echo "- Lock exists: " . (file_exists(__DIR__ . '/productImages.lock') ? '⚠️  YES (remove it!)' : '✅ NO') . "\n";
echo "- Log file: " . (__DIR__ . '/image_update.log') . "\n\n";

// Xóa lock nếu có
if (file_exists(__DIR__ . '/productImages.lock')) {
    unlink(__DIR__ . '/productImages.lock');
    echo "🗑️  Removed old lock file\n\n";
}

// Kiểm tra needsUpdate
echo "⏰ Checking if update needed...\n";
if ($updater->needsUpdate()) {
    echo "✅ Update is needed\n\n";
} else {
    echo "⚠️  Cache is still fresh, forcing update...\n\n";
}

// Chạy update trực tiếp
echo "🚀 Running update NOW (direct call)...\n";
echo "=================================\n\n";

$result = $updater->updateImages(true);

echo "\n=================================\n";
if ($result) {
    echo "✅ Update completed successfully!\n";
} else {
    echo "❌ Update failed! Check log file.\n";
}

// Hiển thị log
echo "\n📋 Last 20 lines from log:\n";
echo "=================================\n";
$logFile = __DIR__ . '/image_update.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    echo implode('', $lastLines);
} else {
    echo "No log file found.\n";
}

// Hiển thị một vài ảnh từ cache
echo "\n📸 Sample images from cache:\n";
echo "=================================\n";
$images = $updater->getImages();
$count = 0;
foreach ($images as $id => $url) {
    echo "[$id] => " . $url . "\n";
    if (++$count >= 5) break;
}
echo "... Total: " . count($images) . " images\n";