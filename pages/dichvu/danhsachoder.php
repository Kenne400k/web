<?php
$page_title = 'All Services';
require_once '../../config/header.php';

// Allow access to API config
define('ACCESS_ALLOWED', true);
require_once '../../config/api_config.php';

// Function to get services from API
function getServicesFromAPI() {
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
        return json_decode($response, true);
    }
    return false;
}

// Get all services
$allServices = getServicesFromAPI();
$servicesByPlatform = [];

// 🔥 Thứ tự ưu tiên hiển thị platforms
$platformOrder = [
    'Best Seller',
    'Youtube',
    'Facebook',
    'Instagram',
    'Tiktok',
    'Twitter',
    'Telegram',
    'Shopee',
    'Spotify',
    'Reddit',
    'Bigo',
    'CoinMarketCap'
];

// Platform icons mapping
$platformIcons = [
    'Best Seller'    => 'bi-star-fill',
    'Youtube'        => 'bi-youtube',
    'Facebook'       => 'bi-facebook',
    'Instagram'      => 'bi-instagram',
    'Tiktok'         => 'bi-tiktok',
    'Twitter'        => 'bi-twitter',
    'Telegram'       => 'bi-telegram',
    'Shopee'         => 'bi-shop',
    'Spotify'        => 'bi-spotify',
    'Reddit'         => 'bi-reddit',
    'Bigo'           => 'bi-broadcast',
    'CoinMarketCap'  => 'bi-currency-bitcoin'
];

$platformColors = [
    'Best Seller'    => '#FFD700',
    'Youtube'        => '#FF0000',
    'Facebook'       => '#1877f2',
    'Instagram'      => '#E4405F',
    'Tiktok'         => '#000000',
    'Twitter'        => '#1DA1F2',
    'Telegram'       => '#0088cc',
    'Shopee'         => '#EE4D2D',
    'Spotify'        => '#1DB954',
    'Reddit'         => '#FF4500',
    'Bigo'           => '#FF6B6B',
    'CoinMarketCap'  => '#F7931A'
];

// Nhóm services theo platform
if ($allServices && is_array($allServices)) {
    foreach ($allServices as &$service) { // Use reference to modify
        // [MODIFIED] Add 20% profit to the service rate
        $service['rate'] = floatval($service['rate']) * 1.2;

        $platform = $service['platform'] ?? 'Other';
        if (!isset($servicesByPlatform[$platform])) {
            $servicesByPlatform[$platform] = [];
        }
        $servicesByPlatform[$platform][] = $service;
    }
    unset($service); // Unset reference after loop
}

// 🎯 Sắp xếp lại theo thứ tự ưu tiên
$sortedServices = [];
foreach ($platformOrder as $platform) {
    if (isset($servicesByPlatform[$platform])) {
        $sortedServices[$platform] = $servicesByPlatform[$platform];
        unset($servicesByPlatform[$platform]);
    }
}

// Thêm các platform còn lại (nếu có) vào cuối
foreach ($servicesByPlatform as $platform => $services) {
    $sortedServices[$platform] = $services;
}

$servicesByPlatform = $sortedServices;

require_once '../../config/sidebar.php';
?>

<style>
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

    .services-container {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .platform-section {
        margin-bottom: 40px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
        background: var(--content-bg);
    }

    .platform-section:last-child {
        margin-bottom: 0;
    }

    .platform-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 20px;
        background: var(--input-bg);
        cursor: pointer;
        transition: all 0.3s ease;
        user-select: none;
    }

    .platform-header:hover {
        background: var(--hover-bg);
    }

    .platform-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 20px;
    }

    .platform-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin: 0;
    }

    .platform-count {
        margin-left: auto;
        padding: 4px 12px;
        background: var(--content-bg);
        border-radius: 20px;
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 600;
    }

    .toggle-icon {
        margin-left: 8px;
        font-size: 18px;
        color: var(--text-secondary);
        transition: transform 0.3s ease;
    }

    .toggle-icon.collapsed {
        transform: rotate(-90deg);
    }

    .platform-content {
        max-height: 5000px;
        overflow: hidden;
        transition: max-height 0.4s ease, opacity 0.3s ease;
        opacity: 1;
    }

    .platform-content.collapsed {
        max-height: 0;
        opacity: 0;
    }

    /* Table Styles */
    .services-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--content-bg);
    }

    .services-table thead {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    }

    .services-table th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap;
    }

    .services-table tbody tr {
        transition: all 0.2s;
        border-bottom: 1px solid var(--border-color);
    }

    .services-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
    }

    .services-table tbody tr:last-child {
        border-bottom: none;
    }

    .services-table td {
        padding: 16px 20px;
        font-size: 14px;
        color: var(--text-color);
        vertical-align: middle;
    }

    .service-id {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .star-icon {
        color: #94A3B8;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .star-icon:hover {
        color: #FFD700;
    }

    .id-badge {
        font-weight: 600;
        color: var(--text-color);
    }

    .service-name {
        line-height: 1.5;
        max-width: 500px;
    }

    .service-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }

    .price-cell {
        font-weight: 700;
        color: #667eea;
        white-space: nowrap;
    }

    .currency {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        margin-left: 2px;
    }

    .min-max-cell {
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .time-cell {
        color: var(--text-secondary);
        font-size: 13px;
        white-space: nowrap;
    }

    .btn-order {
        padding: 8px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        white-space: nowrap;
    }

    .btn-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .services-table th,
        .services-table td {
            padding: 12px 15px;
            font-size: 13px;
        }

        .service-name {
            max-width: 350px;
        }
    }

    @media (max-width: 768px) {
        .services-container {
            padding: 15px;
            border-radius: 8px;
        }

        .services-table {
            font-size: 12px;
        }

        .services-table th,
        .services-table td {
            padding: 10px 8px;
        }

        .platform-header {
            flex-wrap: wrap;
            padding: 12px 15px;
        }

        .service-name {
            font-size: 12px;
            max-width: 200px;
        }

        .btn-order {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Hide some columns on mobile */
        .services-table th:nth-child(4),
        .services-table td:nth-child(4),
        .services-table th:nth-child(5),
        .services-table td:nth-child(5) {
            display: none;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <?php echo $lang === 'vi' ? 'Tất Cả Dịch Vụ' : 'All Services'; ?>
    </h1>
    <p><?php echo $lang === 'vi' ? 'Danh sách đầy đủ các dịch vụ theo nền tảng' : 'Complete list of services by platform'; ?></p>
</div>

<!-- Services Container -->
<div class="services-container">
    <?php if (!empty($servicesByPlatform)): ?>
        <?php 
        $platformIndex = 0;
        foreach ($servicesByPlatform as $platform => $services): 
        ?>
        <div class="platform-section">
            <!-- Platform Header - Clickable -->
            <div class="platform-header" onclick="togglePlatform(<?php echo $platformIndex; ?>)">
                <div class="platform-icon" style="background: <?php echo $platformColors[$platform] ?? '#666'; ?>20; color: <?php echo $platformColors[$platform] ?? '#666'; ?>">
                    <i class="<?php echo $platformIcons[$platform] ?? 'bi-circle-fill'; ?>"></i>
                </div>
                <h3 class="platform-title"><?php echo htmlspecialchars($platform); ?></h3>
                <span class="platform-count"><?php echo count($services); ?> <?php echo $lang === 'vi' ? 'dịch vụ' : 'services'; ?></span>
                <i class="bi bi-chevron-down toggle-icon" id="toggle-icon-<?php echo $platformIndex; ?>"></i>
            </div>

            <!-- Platform Content - Collapsible -->
            <div class="platform-content" id="platform-content-<?php echo $platformIndex; ?>">
                <!-- Services Table -->
                <table class="services-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php echo $lang === 'vi' ? 'ID' : 'ID'; ?></th>
                            <th><?php echo $lang === 'vi' ? 'Dịch vụ' : 'Service'; ?></th>
                            <th style="width: 120px;"><?php echo $lang === 'vi' ? 'Giá' : 'Price'; ?></th>
                            <th style="width: 100px;"><?php echo $lang === 'vi' ? 'Min' : 'Min'; ?></th>
                            <th style="width: 100px;"><?php echo $lang === 'vi' ? 'Max' : 'Max'; ?></th>
                            <th style="width: 150px;"><?php echo $lang === 'vi' ? 'Thời gian TB' : 'Avg. Time'; ?></th>
                            <th style="width: 120px;"><?php echo $lang === 'vi' ? 'Mô tả' : 'Description'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <div class="service-id">
                                    <i class="bi bi-star star-icon"></i>
                                    <span class="id-badge"><?php echo htmlspecialchars($service['service']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="service-name">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                    <?php if (isset($service['refill']) && $service['refill']): ?>
                                    <span class="service-badge">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        <?php echo $lang === 'vi' ? 'Refill' : 'Refill'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="price-cell">
                                <?php echo number_format($service['rate'], 2, '.', ','); ?>
                                <span class="currency">USD</span>
                            </td>
                            <td class="min-max-cell">
                                <?php echo number_format($service['min'], 0, ',', '.'); ?>
                            </td>
                            <td class="min-max-cell">
                                <?php echo number_format($service['max'], 0, ',', '.'); ?>
                            </td>
                            <td class="time-cell">
                                <?php 
                                if (isset($service['average_time']) && !empty($service['average_time'])) {
                                    echo htmlspecialchars($service['average_time']);
                                } else {
                                    echo $lang === 'vi' ? 'Không có' : 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="/order?service=<?php echo htmlspecialchars($service['service']); ?>" 
                                   class="btn-order">
                                    <?php echo $lang === 'vi' ? 'Đặt ngay' : 'Order Now'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php 
        $platformIndex++;
        endforeach; 
        ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p><?php echo $lang === 'vi' ? 'Không có dịch vụ nào' : 'No services available'; ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePlatform(index) {
    const content = document.getElementById('platform-content-' + index);
    const icon = document.getElementById('toggle-icon-' + index);
    
    content.classList.toggle('collapsed');
    icon.classList.toggle('collapsed');
}
</script>

<?php require_once '../../config/footer.php'; ?>

