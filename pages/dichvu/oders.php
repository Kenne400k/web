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
$servicesByCategory = [];

// Platform icons mapping
$platformIcons = [
    'Best Seller'    => 'bi-star-fill',
    'Youtube'        => 'bi-youtube',
    'Facebook'       => 'bi-facebook',
    'Twitter'        => 'bi-twitter',
    'Instagram'      => 'bi-instagram',
    'Telegram'       => 'bi-telegram',
    'Tiktok'         => 'bi-tiktok',
    'CoinMarketCap'  => 'bi-currency-bitcoin',
    'Bigo'           => 'bi-broadcast',
    'Shopee'         => 'bi-shop',
    'Reddit'         => 'bi-reddit',
    'Spotify'        => 'bi-spotify'
];

$platformColors = [
    'Best Seller'    => '#FFD700',
    'Youtube'        => '#FF0000',
    'Facebook'       => '#1877f2',
    'Twitter'        => '#1DA1F2',
    'Instagram'      => '#E4405F',
    'Telegram'       => '#0088cc',
    'Tiktok'         => '#000000',
    'CoinMarketCap'  => '#F7931A',
    'Bigo'           => '#FF6B6B',
    'Shopee'         => '#EE4D2D',
    'Reddit'         => '#FF4500',
    'Spotify'        => '#1DB954'
];

$platformOrder = [
    'Best Seller',
    'Youtube',
    'Facebook',
    'Twitter',
    'Instagram',
    'Telegram',
    'Tiktok',
    'CoinMarketCap',
    'Bigo',
    'Shopee',
    'Reddit',
    'Spotify'
];


if ($allServices && is_array($allServices)) {
    foreach ($allServices as &$service) { // Add reference to modify the array directly
        // [MODIFIED] Increase the rate by 20%
        $service['rate'] = floatval($service['rate']) * 1.2;
        
        $platform = $service['platform'] ?? 'Other';
        $category = $service['category'] ?? 'Other';
        
        // Group by platform
        if (!isset($servicesByPlatform[$platform])) {
            $servicesByPlatform[$platform] = [];
        }
        $servicesByPlatform[$platform][] = $service;
        
        // Group by platform and category
        if (!isset($servicesByCategory[$platform])) {
            $servicesByCategory[$platform] = [];
        }
        if (!isset($servicesByCategory[$platform][$category])) {
            $servicesByCategory[$platform][$category] = [];
        }
        $servicesByCategory[$platform][$category][] = $service;
    }
    unset($service); // Unset reference to avoid side-effects
}

// Sort platforms
uksort($servicesByPlatform, function($a, $b) use ($platformOrder) {
    $posA = array_search($a, $platformOrder);
    $posB = array_search($b, $platformOrder);
    // If not found, push to the end
    $posA = $posA === false ? PHP_INT_MAX : $posA;
    $posB = $posB === false ? PHP_INT_MAX : $posB;
    return $posA <=> $posB;
});
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

    /* Quick Search Section */
    .quick-search-section {
        background-color: var(--content-bg);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        font-size: 20px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
        margin-bottom: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        position: relative;
        background: var(--content-bg);
        border-bottom: 1px solid var(--border-color);
        transition: all 0.3s;
    }

    .filter-group:first-child {
        border-radius: 12px 12px 0 0;
    }

    .filter-group:last-child {
        border-radius: 0 0 12px 12px;
        border-bottom: none;
    }

    .filter-group:hover {
        background: linear-gradient(to right, rgba(102, 126, 234, 0.03), transparent);
    }

    .filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        cursor: pointer;
        user-select: none;
    }

    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-color);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-label i {
        font-size: 18px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .filter-arrow {
        color: var(--text-secondary);
        transition: transform 0.3s;
        font-size: 20px;
    }

    .filter-group.expanded .filter-arrow {
        transform: rotate(180deg);
        color: #667eea;
    }

    .filter-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-group.expanded .filter-content {
        max-height: 500px;
    }

    .filter-inner {
        padding: 0 20px 16px 20px;
    }

    /* Search Box */
    .search-box {
        position: relative;
        margin-bottom: 12px;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        background: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 13px;
        transition: all 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: 16px;
        pointer-events: none;
    }

    /* Options List */
    .options-list {
        max-height: 300px;
        overflow-y: auto;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
    }

    .option-item {
        padding: 12px 15px;
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .option-item:last-child {
        border-bottom: none;
    }

    .option-item:hover {
        background: rgba(102, 126, 234, 0.1);
    }

    .option-item.selected {
        background: linear-gradient(to right, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
        color: #667eea;
        font-weight: 600;
    }

    .option-text {
        font-size: 13px;
        color: var(--text-color);
    }

    .option-item.selected .option-text {
        color: #667eea;
    }

    .option-check {
        color: #667eea;
        font-size: 18px;
        display: none;
    }

    .option-item.selected .option-check {
        display: block;
    }

    .option-count {
        font-size: 12px;
        color: var(--text-secondary);
        background: var(--content-bg);
        padding: 2px 8px;
        border-radius: 12px;
    }

    .option-item.selected .option-count {
        background: rgba(102, 126, 234, 0.2);
        color: #667eea;
    }

    /* Empty State */
    .empty-state {
        padding: 30px 15px;
        text-align: center;
        color: var(--text-secondary);
        font-size: 13px;
    }

    .empty-state i {
        font-size: 32px;
        margin-bottom: 10px;
        opacity: 0.3;
    }

    /* Scrollbar */
    .options-list::-webkit-scrollbar {
        width: 6px;
    }

    .options-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .options-list::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 3px;
    }

    .options-list::-webkit-scrollbar-thumb:hover {
        background: #667eea;
    }

    /* Platform Stats */
    .platform-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .stat-card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .stat-card.active {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    }

    .stat-icon {
        font-size: 28px;
        margin-bottom: 8px;
    }

    .stat-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 4px;
    }

    .stat-count {
        font-size: 12px;
        color: var(--text-secondary);
    }

    /* Info Box */
    .info-box {
        background: linear-gradient(135deg, #e8f4fd, #f0f9ff);
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    html.dark .info-box {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }

    .info-box p {
        margin: 0;
        font-size: 13px;
        color: var(--text-color);
        line-height: 1.6;
    }

    .info-box i {
        color: #667eea;
        margin-right: 8px;
    }

    /* Service Preview */
    .service-preview {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        margin-top: 15px;
        display: none;
    }

    .service-preview.active {
        display: block;
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .service-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .service-info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .service-info-label {
        font-size: 12px;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 600;
    }

    .service-info-value {
        font-size: 14px;
        color: var(--text-color);
        font-weight: 500;
    }

    .btn-order {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-order:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
        
        .platform-stats {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-icon {
            font-size: 24px;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <?php echo $lang === 'vi' ? 'Tất Cả Dịch Vụ' : 'All Services'; ?>
    </h1>
    <p><?php echo $lang === 'vi' ? 'Chọn nền tảng và dịch vụ bạn muốn sử dụng' : 'Select platform and service you want to use'; ?></p>
</div>

<!-- Quick Search Section -->
<div class="quick-search-section">
    <h3 class="section-title">
        <i class="bi bi-search"></i>
        <?php echo $lang === 'vi' ? 'Tìm kiếm nhanh' : 'Quick Search'; ?>
    </h3>
    
    <!-- Platform Stats -->
    <div class="platform-stats">
        <?php foreach ($servicesByPlatform as $platform => $services): ?>
        <div class="stat-card" data-platform="<?php echo htmlspecialchars($platform); ?>">
            <div class="stat-icon" style="color: <?php echo $platformColors[$platform] ?? '#666'; ?>">
                <i class="<?php echo $platformIcons[$platform] ?? 'bi-circle-fill'; ?>"></i>
            </div>
            <div class="stat-name"><?php echo htmlspecialchars($platform); ?></div>
            <div class="stat-count"><?php echo count($services); ?> <?php echo $lang === 'vi' ? 'dịch vụ' : 'services'; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="filter-grid" style="margin-top: 25px;">
        <!-- Platform Filter -->
        <div class="filter-group" id="platformGroup">
            <div class="filter-header" onclick="toggleFilter('platformGroup')">
                <label class="filter-label">
                    <i class="bi bi-app"></i>
                    <span id="platformLabel"><?php echo $lang === 'vi' ? 'Nền tảng' : 'Platform'; ?></span>
                </label>
                <i class="bi bi-chevron-down filter-arrow"></i>
            </div>
            <div class="filter-content">
                <div class="filter-inner">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="platformSearch"
                               placeholder="<?php echo $lang === 'vi' ? 'Tìm kiếm nền tảng...' : 'Search platform...'; ?>">
                    </div>
                    <div class="options-list" id="platformList">
                        <?php foreach ($servicesByPlatform as $platform => $services): ?>
                        <div class="option-item" data-value="<?php echo htmlspecialchars($platform); ?>">
                            <span class="option-text"><?php echo htmlspecialchars($platform); ?></span>
                            <span class="option-count"><?php echo count($services); ?></span>
                            <i class="bi bi-check-lg option-check"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="filter-group" id="categoryGroup">
            <div class="filter-header" onclick="toggleFilter('categoryGroup')">
                <label class="filter-label">
                    <i class="bi bi-folder"></i>
                    <span id="categoryLabel"><?php echo $lang === 'vi' ? 'Danh mục' : 'Category'; ?></span>
                </label>
                <i class="bi bi-chevron-down filter-arrow"></i>
            </div>
            <div class="filter-content">
                <div class="filter-inner">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="categorySearch"
                               placeholder="<?php echo $lang === 'vi' ? 'Tìm kiếm danh mục...' : 'Search category...'; ?>">
                    </div>
                    <div class="options-list" id="categoryList">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <div><?php echo $lang === 'vi' ? 'Vui lòng chọn nền tảng trước' : 'Please select platform first'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Filter -->
        <div class="filter-group" id="serviceGroup">
            <div class="filter-header" onclick="toggleFilter('serviceGroup')">
                <label class="filter-label">
                    <i class="bi bi-gear"></i>
                    <span id="serviceLabel"><?php echo $lang === 'vi' ? 'Dịch vụ' : 'Service'; ?></span>
                </label>
                <i class="bi bi-chevron-down filter-arrow"></i>
            </div>
            <div class="filter-content">
                <div class="filter-inner">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" 
                               class="search-input" 
                               id="serviceSearch"
                               placeholder="<?php echo $lang === 'vi' ? 'Tìm kiếm dịch vụ...' : 'Search service...'; ?>">
                    </div>
                    <div class="options-list" id="serviceList">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <div><?php echo $lang === 'vi' ? 'Vui lòng chọn danh mục trước' : 'Please select category first'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Preview -->
    <div class="service-preview" id="servicePreview">
        <div class="service-info">
            <div class="service-info-item">
                <span class="service-info-label"><?php echo $lang === 'vi' ? 'Tên dịch vụ' : 'Service Name'; ?></span>
                <span class="service-info-value" id="previewName">-</span>
            </div>
            <div class="service-info-item">
                <span class="service-info-label"><?php echo $lang === 'vi' ? 'Giá' : 'Price'; ?></span>
                <span class="service-info-value" id="previewRate">-</span>
            </div>
            <div class="service-info-item">
                <span class="service-info-label"><?php echo $lang === 'vi' ? 'Tối thiểu' : 'Minimum'; ?></span>
                <span class="service-info-value" id="previewMin">-</span>
            </div>
            <div class="service-info-item">
                <span class="service-info-label"><?php echo $lang === 'vi' ? 'Tối đa' : 'Maximum'; ?></span>
                <span class="service-info-value" id="previewMax">-</span>
            </div>
        </div>
        <button class="btn-order" id="btnGoToOrder">
            <i class="bi bi-cart-plus"></i>
            <?php echo $lang === 'vi' ? 'Đặt hàng ngay' : 'Order Now'; ?>
        </button>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <p>
            <i class="bi bi-info-circle-fill"></i>
            <?php echo $lang === 'vi' 
                ? 'Chọn nền tảng, danh mục và dịch vụ để xem chi tiết và đặt hàng' 
                : 'Select platform, category and service to view details and place order'; ?>
        </p>
    </div>
</div>

<script>
const servicesByCategory = <?php echo json_encode($servicesByCategory); ?>;
const platformList = document.getElementById('platformList');
const categoryList = document.getElementById('categoryList');
const serviceList = document.getElementById('serviceList');
const servicePreview = document.getElementById('servicePreview');
const btnGoToOrder = document.getElementById('btnGoToOrder');
const statCards = document.querySelectorAll('.stat-card');

// Search inputs
const platformSearch = document.getElementById('platformSearch');
const categorySearch = document.getElementById('categorySearch');
const serviceSearch = document.getElementById('serviceSearch');

// Labels
const platformLabel = document.getElementById('platformLabel');
const categoryLabel = document.getElementById('categoryLabel');
const serviceLabel = document.getElementById('serviceLabel');

let selectedPlatform = null;
let selectedCategory = null;
let currentService = null;

// Toggle filter accordion
function toggleFilter(groupId) {
    const group = document.getElementById(groupId);
    group.classList.toggle('expanded');
}

// Platform search
platformSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = platformList.querySelectorAll('.option-item');
    
    items.forEach(item => {
        const text = item.querySelector('.option-text').textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
});

// Category search
categorySearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = categoryList.querySelectorAll('.option-item');
    
    items.forEach(item => {
        const text = item.querySelector('.option-text').textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
});

// Service search
serviceSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = serviceList.querySelectorAll('.option-item');
    
    items.forEach(item => {
        const text = item.querySelector('.option-text').textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
});

// Platform stat cards click handler
statCards.forEach(card => {
    card.addEventListener('click', function() {
        const platform = this.dataset.platform;
        selectPlatform(platform);
    });
});

// Platform selection
platformList.addEventListener('click', function(e) {
    const item = e.target.closest('.option-item');
    if (!item) return;
    
    const platform = item.dataset.value;
    selectPlatform(platform);
});

function selectPlatform(platform) {
    selectedPlatform = platform;
    selectedCategory = null;
    currentService = null;
    
    // Update platform items
    platformList.querySelectorAll('.option-item').forEach(item => {
        item.classList.toggle('selected', item.dataset.value === platform);
    });
    
    // Update stat cards
    statCards.forEach(card => {
        card.classList.toggle('active', card.dataset.platform === platform);
    });
    
    // Update label
    platformLabel.textContent = platform;
    
    // Collapse platform and expand category
    document.getElementById('platformGroup').classList.remove('expanded');
    document.getElementById('categoryGroup').classList.add('expanded');
    
    // Load categories
    loadCategories(platform);
    
    // Clear service
    serviceList.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><div><?php echo $lang === 'vi' ? 'Vui lòng chọn danh mục trước' : 'Please select category first'; ?></div></div>';
    serviceLabel.textContent = '<?php echo $lang === 'vi' ? 'Dịch vụ' : 'Service'; ?>';
    
    // Hide preview
    servicePreview.classList.remove('active');
}

function loadCategories(platform) {
    categoryList.innerHTML = '';
    categoryLabel.textContent = '<?php echo $lang === 'vi' ? 'Danh mục' : 'Category'; ?>';
    categorySearch.value = '';
    
    if (servicesByCategory[platform]) {
        const categories = servicesByCategory[platform];
        
        Object.keys(categories).sort().forEach(category => {
            const item = document.createElement('div');
            item.className = 'option-item';
            item.dataset.value = category;
            item.innerHTML = `
                <span class="option-text">${category}</span>
                <span class="option-count">${categories[category].length}</span>
                <i class="bi bi-check-lg option-check"></i>
            `;
            categoryList.appendChild(item);
        });
    }
}

// Category selection
categoryList.addEventListener('click', function(e) {
    const item = e.target.closest('.option-item');
    if (!item) return;
    
    const category = item.dataset.value;
    selectCategory(category);
});

function selectCategory(category) {
    selectedCategory = category;
    currentService = null;
    
    // Update category items
    categoryList.querySelectorAll('.option-item').forEach(item => {
        item.classList.toggle('selected', item.dataset.value === category);
    });
    
    // Update label
    categoryLabel.textContent = category;
    
    // Collapse category and expand service
    document.getElementById('categoryGroup').classList.remove('expanded');
    document.getElementById('serviceGroup').classList.add('expanded');
    
    // Load services
    loadServices(selectedPlatform, category);
    
    // Hide preview
    servicePreview.classList.remove('active');
}

function loadServices(platform, category) {
    serviceList.innerHTML = '';
    serviceLabel.textContent = '<?php echo $lang === 'vi' ? 'Dịch vụ' : 'Service'; ?>';
    serviceSearch.value = '';
    
    if (servicesByCategory[platform] && servicesByCategory[platform][category]) {
        const services = servicesByCategory[platform][category];
        
        services.forEach(service => {
            const item = document.createElement('div');
            item.className = 'option-item';
            item.dataset.value = service.service;
            item.dataset.serviceData = JSON.stringify(service);
            item.innerHTML = `
                <span class="option-text">${service.name}</span>
                <span class="option-count">$${Number(service.rate).toFixed(2)}</span>
                <i class="bi bi-check-lg option-check"></i>
            `;
            serviceList.appendChild(item);
        });
    }
}

// Service selection
serviceList.addEventListener('click', function(e) {
    const item = e.target.closest('.option-item');
    if (!item || !item.dataset.serviceData) return;
    
    currentService = JSON.parse(item.dataset.serviceData);
    
    // Update service items
    serviceList.querySelectorAll('.option-item').forEach(i => {
        i.classList.toggle('selected', i.dataset.value === item.dataset.value);
    });
    
    // Update label
    serviceLabel.textContent = currentService.name;
    
    // Collapse service
    document.getElementById('serviceGroup').classList.remove('expanded');
    
    // Update preview
    document.getElementById('previewName').textContent = currentService.name;
    document.getElementById('previewRate').textContent = '$' + Number(currentService.rate).toFixed(2);
    document.getElementById('previewMin').textContent = Number(currentService.min).toLocaleString();
    document.getElementById('previewMax').textContent = Number(currentService.max).toLocaleString();
    
    servicePreview.classList.add('active');
});

// Order button click handler
btnGoToOrder.addEventListener('click', function() {
    if (currentService) {
        window.location.href = '/order?service=' + currentService.service;
    }
});
</script>

<?php require_once '../../config/footer.php'; ?>

