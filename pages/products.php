<?php
$page_title = 'Products';
require_once '../config/header.php';

// Allow access to API config
define('ACCESS_ALLOWED', true);
require_once '../config/api_config.php';

$productImages = [
    // 🔹 CHECK NETWORK & EMAIL CMS & COPYRIGHT
    129 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/Check-Network---Email-CMS-20251107-095814-23f22fd5.png',

    // 🔹 KLING AI 2.6
    133 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai265sstand-20251210-095059-f3d48d67.png', // Kling 2.6 - Standard - 5s
    134 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai2610sstand-20251210-095330-de29fef4.png', // Kling 2.6 - Standard - 10s
    135 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai265spro-20251210-095416-95c6744b.png', // Kling 2.6 - Professional - 5s
    136 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai2610spro-20251210-095447-fdce83bd.png', // Kling 2.6 - Professional - 10s

    // 🔹 GOOGLE - VEO 3.1
    118 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/veo31-20251019-124409-1b05a844.png', // VEO 3.1 - Fast - 720p
    119 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/veo31-20251019-124409-1b05a844.png', // VEO 3.1 - Quality - 720p

    // 🔹 GOOGLE - NANO BANANA PRO
    130 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/-Nano-Banana-Pro-20251126-114808-b507c0ba.png', // Nano Banana Pro - 1k
    131 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/-Nano-Banana-Pro-20251126-114808-b507c0ba.png', // Nano Banana Pro - 2k
    132 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/-Nano-Banana-Pro-20251126-114808-b507c0ba.png', // Nano Banana Pro - 4k

    // 🔹 SORA 2 - PROFESSIONAL - NO WATERMARK
    106 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO5S720P-20251019-073218-de2b71d3.png',   // 720p - 5s
    107 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO5S1080P-20251019-073400-e00c3a6c.png',  // 1080p - 5s
    108 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO5S2K-20251019-073524-48658867.png',     // 2k - 5s
    109 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO10S720P-20251019-073621-488f733f.png',  // 720p - 10s
    110 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO10S1080P-20251019-073702-da95bdaf.png', // 1080p - 10s
    111 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO10S2K-20251019-073744-850ab75b.png',    // 2k - 10s
    112 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO15S720P-20251019-073815-df74859d.png',  // 720p - 15s
    113 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO15S1080P-20251019-073850-51dd7851.png', // 1080p - 15s
    114 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/SORA2PRO15S2K-20251019-073941-e48f673b.png',    // 2k - 15s

    // 🔹 V-FUSE 1.0
    120 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse6s720p-20251019-130151-2da98fe0.png',   // 720p - 6s
    121 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse6s1080p-20251019-130234-2c2aa06f.png',  // 1080p - 6s
    122 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse8s720p-20251019-130253-48412fa7.png',   // 720p - 8s
    123 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse8s1080p-20251019-130452-0f67f016.png',  // 1080p - 8s
    124 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse10s720p-20251019-130559-e84b34d4.png',  // 720p - 10s
    125 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse10s1080p-20251019-130640-03e6eafa.png', // 1080p - 10s
    126 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse12s720p-20251019-131219-9ae8c243.png',  // 720p - 12s
    127 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/vfuse12s1080p-20251019-131254-471ac107.png', // 1080p - 12s

    // 🔹 YOUTUBE PREMIUM
    81 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/youtubepremium1thang-20251013-073755-cfad7454.png', // 1 Tháng
    80 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/youtubepremium3thang-20251013-073444-d1c03a7a.png', // 3 Tháng
    55 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/youtubepremium6thang-20250909-083440-bf11bf67.png', // 6 Tháng

    // 🔹 ACCOUNT AI
    54 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/chatgpt1thang-20250909-090021-b9c7a437.png',  // Chat GPT Plus 1 Tháng
    31 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/chatgptteam-20250913-072103-00233bae.png',   // ChatGPT Business

    // 🔹 CANVA PRO
    58 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/canvapro1nam-20250911-130307-eb9d5e76.png',  // 12 tháng
    128 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/canvas-1-month-20251020-032018-858ebec8.png', // 1 tháng

    // 🔹 CAPCUT PRO
    59 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/capcutpro1thang-20250911-152114-5d472137.png', // 21 ngày

    // 🔹 AI VIDEO GENERATOR - SV1
    16 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/VEO2QUALITY-20250912-081930-e8b98f17.png', // Make Video VEO2 (Quality)
    17 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/VEO2FAST-20250912-082000-573bcb77.png',    // Make Video VEO2 (Fast)
    24 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/hailuoV26s768P-20250912-121017-65e97141.png',  // Hailuo V2 6s 768P
    25 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/hailuov26s1080p-20250912-121231-a5ffe4a9.png', // Hailuo V2 6s 1080P
    26 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/hailuoV210s768P-20250912-121401-4492e96c.png', // Hailuo V2 10s 768P
    27 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai165s-20250912-133718-e757a210.png',    // Kling 1.6 5s
    28 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai1610s-20250912-133559-31b5bd5f.png',   // Kling 1.6 10s
    29 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai215s-20250912-133230-708c7880.png',    // Kling 2.1 5s
    30 => 'http://cdn.whoispanel.com/product_thumbnail/imgs/klingai2110s-20250912-133436-3ac9fb06.png',   // Kling 2.1 10s

    // 🔹 AI VIDEO GENERATOR - SV2
    36 => 'https://i.ibb.co/ccpvs2sY/hailuo-ai-la-gi-1-1.jpg', // Hailuo V2 6s 768P
    38 => 'https://i.ibb.co/ccpvs2sY/hailuo-ai-la-gi-1-1.jpg', // Hailuo V2 10s 768P
    37 => 'https://i.ibb.co/ccpvs2sY/hailuo-ai-la-gi-1-1.jpg', // Hailuo V2 6s 1080P
    39 => 'https://i.ibb.co/4wfgV8sc/e6c52de53b8319a6.jpg',    // Fashion Try-On Videos

    // 🔹 AI PICTURE GENERATOR - SV1
    32 => 'https://i.ibb.co/ccpvs2sY/hailuo-ai-la-gi-1-1.jpg', // Hailuo 01
    33 => 'https://i.ibb.co/HfVhdjX8/subcat-3016.jpg',         // Kling Colors 2.1
    34 => 'https://i.ibb.co/HfVhdjX8/subcat-3016.jpg',         // Kling Colors 2.0
    35 => 'https://i.ibb.co/HfVhdjX8/subcat-3016.jpg',         // Kling Colors 1.5

    // 🔹 AI PICTURE GENERATOR - SV2
    42 => 'https://i.ibb.co/ccpvs2sY/hailuo-ai-la-gi-1-1.jpg',           // Hailuo AI
    41 => 'https://i.ibb.co/gMysXwwV/Chat-GPT-Image-20-14-09-24-thg-8-2025.png', // ChatGPT
    43 => 'https://i.ibb.co/LDbvbL0b/Chat-GPT-Image-19-50-27-24-thg-8-2025.png', // Photo To Art
    40 => 'https://i.ibb.co/MyPQqBTD/Chat-GPT-Image-20-18-15-24-thg-8-2025.png', // Fashion Try-On Image

    // 🔹 TẠO ẢNH QUẢNG CÁO SẢN PHẨM
    19 => 'https://i.ibb.co/MyPQqBTD/Chat-GPT-Image-20-18-15-24-thg-8-2025.png', // Fashion Try-On Image

    // 🔹 TẠO VIDEO QUẢNG CÁO SẢN PHẨM
    23 => 'https://i.ibb.co/4wfgV8sc/e6c52de53b8319a6.jpg', // Fashion Try-On Videos

    // 🔹 DEFAULT FALLBACK
    'default' => 'https://1dg.me/assets/media/logo_square.png?1731466820'
];

// Function to translate Vietnamese text to English using simple mapping
function translateToEnglish($text) {
    // Common Vietnamese to English translations
    $translations = [
        // Common phrases
        'Nhập email của bạn vào đây' => 'Enter your email here',
        'không điền mật khẩu' => 'do not fill in password',
        'Nhập link' => 'Enter link',
        'Nhập URL' => 'Enter URL',
        'Nhập số lượng' => 'Enter quantity',
        'Chính chủ' => 'Owner Account',
        
        // Time periods
        'Tháng' => 'Months',
        'tháng' => 'months',
        'Năm' => 'Year',
        'năm' => 'year',
        'Ngày' => 'Days',
        'ngày' => 'days',
        'Tuần' => 'Weeks',
        'tuần' => 'weeks',
        
        // Common words
        'của bạn' => 'your',
        'vào đây' => 'here',
        'không' => 'do not',
        'điền' => 'fill',
        'mật khẩu' => 'password',
        'email' => 'email',
        'link' => 'link',
        'số lượng' => 'quantity',
        'tài khoản' => 'account',
        'Gói' => 'Package',
        'gói' => 'package',
        'Đăng ký' => 'Subscribe',
        'đăng ký' => 'subscribe',
    ];
    
    $translatedText = $text;
    
    // Replace Vietnamese phrases with English
    foreach ($translations as $vi => $en) {
        $translatedText = str_ireplace($vi, $en, $translatedText);
    }
    
    return $translatedText;
}

// Function to translate product data
function translateProductData($product, $lang) {
    if ($lang === 'en') {
        // Translate params descriptions
        if (isset($product['params']) && is_array($product['params'])) {
            foreach ($product['params'] as &$param) {
                if (isset($param['description'])) {
                    $param['description'] = translateToEnglish($param['description']);
                }
                if (isset($param['name'])) {
                    // Translate common param names
                    $paramNameTranslations = [
                        'yêu cầu' => 'required',
                        'văn bản' => 'text',
                        'liên kết' => 'link',
                    ];
                    
                    foreach ($paramNameTranslations as $vi => $en) {
                        $param['name'] = str_ireplace($vi, $en, $param['name']);
                    }
                }
            }
        }
        
        // Translate product name
        if (isset($product['name'])) {
            $product['name'] = translateToEnglish($product['name']);
        }
    }
    
    return $product;
}

// Function to get products from API
function getProductsFromAPI() {
    $postData = [
        'key' => API_KEY,
        'action' => 'products'
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

// Get all products
$allProducts = getProductsFromAPI();
$productsByCategory = [];

if ($allProducts && is_array($allProducts)) {
    foreach ($allProducts as $product) {
        // Translate product data if language is English
        $product = translateProductData($product, $lang);
        
        $category = $product['category'] ?? 'Other';
        if (!isset($productsByCategory[$category])) {
            $productsByCategory[$category] = [];
        }
        $productsByCategory[$category][] = $product;
    }
}

require_once '../config/sidebar.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 25px;
        color: white;
        border: 1px solid #333;
    }

    html:not(.dark) .page-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        color: #1a1a1a;
        border: 1px solid #dee2e6;
    }

    .page-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: white !important;
    }

    html:not(.dark) .page-header h1 {
        color: #1a1a1a !important;
    }

    .page-header p {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .category-section {
        margin-bottom: 40px;
    }

    .category-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border-color);
    }

    .category-icon {
        width: 40px;
        height: 40px;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }

    html:not(.dark) .category-icon {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        color: #1a1a1a;
    }

    .category-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-color);
        margin: 0;
    }

    .category-count {
        font-size: 14px;
        color: var(--text-secondary);
        background: var(--input-bg);
        padding: 4px 12px;
        border-radius: 12px;
        margin-left: auto;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .product-card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        border-color: #555;
    }

    html:not(.dark) .product-card:hover {
        border-color: #aaa;
    }

    .product-image {
        width: 100%;
        height: 140px;
        object-fit: cover;
        background: #f5f7fa;
    }

    html.dark .product-image {
        background: #2d3748;
    }

    .product-body {
        padding: 12px;
    }

    .product-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-color);
        margin: 0 0 8px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.3;
        min-height: 34px;
    }

    .product-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 11px;
        color: var(--text-secondary);
    }

    .product-price {
        font-size: 16px;
        font-weight: 700;
        color: #e5e5e5;
        margin: 8px 0;
    }

    html:not(.dark) .product-price {
        color: #1a1a1a;
    }

    .product-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 10px;
        font-weight: 600;
    }

    .status-in-stock {
        background: #d4edda;
        color: #155724;
    }

    .status-out-stock {
        background: #f8d7da;
        color: #721c24;
    }

    html.dark .status-in-stock {
        background: #1e4620;
        color: #9fdf9f;
    }

    html.dark .status-out-stock {
        background: #4d1f1f;
        color: #f8b4b4;
    }

    .product-type-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 3px 8px;
        background: rgba(0,0,0,0.7);
        color: white;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .btn-order {
        width: 100%;
        padding: 8px;
        background: #1a1a1a;
        color: white;
        border: 1px solid #333;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 8px;
    }

    .btn-order:hover:not(:disabled) {
        background: #2d2d2d;
        transform: translateY(-1px);
    }

    .btn-order:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    html:not(.dark) .btn-order {
        background: #1a1a1a;
        border: 1px solid #1a1a1a;
    }

    html:not(.dark) .btn-order:hover:not(:disabled) {
        background: #333;
    }

    .filter-bar {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        background: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 14px;
    }

    .search-input:focus {
        outline: none;
        border-color: #555;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
    }

    html:not(.dark) .search-input:focus {
        border-color: #999;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
    }

    .filter-select {
        padding: 10px 15px;
        background: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 14px;
        cursor: pointer;
    }

    .filter-select:focus {
        outline: none;
        border-color: #555;
    }

    html:not(.dark) .filter-select:focus {
        border-color: #999;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 64px;
        opacity: 0.3;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .filter-bar {
            flex-direction: column;
        }

        .search-box {
            width: 100%;
        }
        
        .product-image {
            height: 120px;
        }
    }
</style>

<div class="page-header">
    <h1>
        <i class="bi bi-box-seam"></i>
        <?php echo $lang === 'vi' ? 'Sản Phẩm' : 'Products'; ?>
    </h1>
    <p><?php echo $lang === 'vi' ? 'Khám phá và đặt mua sản phẩm số của chúng tôi' : 'Explore and order our digital products'; ?></p>
</div>

<div class="filter-bar">
    <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input 
            type="text" 
            class="search-input" 
            id="searchInput"
            placeholder="<?php echo $lang === 'vi' ? 'Tìm kiếm sản phẩm...' : 'Search products...'; ?>"
        >
    </div>
    <select class="filter-select" id="categoryFilter">
        <option value=""><?php echo $lang === 'vi' ? 'Tất cả danh mục' : 'All categories'; ?></option>
        <?php foreach ($productsByCategory as $category => $products): ?>
        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
        <?php endforeach; ?>
    </select>
    <select class="filter-select" id="statusFilter">
        <option value=""><?php echo $lang === 'vi' ? 'Tất cả trạng thái' : 'All status'; ?></option>
        <option value="In stock"><?php echo $lang === 'vi' ? 'Còn hàng' : 'In stock'; ?></option>
        <option value="Out of stock"><?php echo $lang === 'vi' ? 'Hết hàng' : 'Out of stock'; ?></option>
    </select>
</div>

<div id="productsContainer">
    <?php if ($productsByCategory): ?>
        <?php foreach ($productsByCategory as $category => $products): ?>
        <div class="category-section" data-category="<?php echo htmlspecialchars($category); ?>">
            <div class="category-header">
                <div class="category-icon">
                    <i class="bi bi-folder-fill"></i>
                </div>
                <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                <span class="category-count"><?php echo count($products); ?> <?php echo $lang === 'vi' ? 'sản phẩm' : 'products'; ?></span>
            </div>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card" 
                     data-product-id="<?php echo $product['product']; ?>"
                     data-status="<?php echo htmlspecialchars($product['status']); ?>"
                     data-name="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <span class="product-type-badge"><?php echo htmlspecialchars($product['type']); ?></span>
                    
                    <img 
                        src="<?php echo $productImages[$product['product']] ?? 'https://1dg.me/assets/media/logo_square.png?1731466820'; ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="product-image"
                        onerror="this.src='https://1dg.me/assets/media/logo_square.png?1731466820'"
                    >
                    
                    <div class="product-body">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <div class="product-info">
                            <span><?php echo $lang === 'vi' ? 'Min' : 'Min'; ?>: <?php echo number_format($product['min']); ?></span>
                            <span><?php echo $lang === 'vi' ? 'Max' : 'Max'; ?>: <?php echo number_format($product['max']); ?></span>
                        </div>

                        <div class="product-price">
                            $<?php echo rtrim(rtrim(number_format($product['rate'] * 1.2, 6, '.', ''), '0'), '.'); ?>
                        </div>

                        <span class="product-status <?php echo $product['status'] === 'In stock' ? 'status-in-stock' : 'status-out-stock'; ?>">
                            <i class="bi <?php echo $product['status'] === 'In stock' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                            <?php echo $product['status'] === 'In stock' ? ($lang === 'vi' ? 'Còn hàng' : 'In stock') : ($lang === 'vi' ? 'Hết hàng' : 'Out of stock'); ?>
                        </span>

                        <button 
                            class="btn-order" 
                            onclick="orderProduct(<?php echo $product['product']; ?>)"
                            <?php echo $product['status'] !== 'In stock' ? 'disabled' : ''; ?>
                        >
                            <i class="bi bi-cart-plus"></i>
                            <?php echo $lang === 'vi' ? 'Đặt hàng' : 'Order'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p><?php echo $lang === 'vi' ? 'Không có sản phẩm nào' : 'No products available'; ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');

function filterProducts() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;
    const selectedStatus = statusFilter.value;

    document.querySelectorAll('.category-section').forEach(section => {
        const category = section.dataset.category;
        let visibleCount = 0;

        if (selectedCategory && category !== selectedCategory) {
            section.style.display = 'none';
            return;
        }

        section.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const status = card.dataset.status;

            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = !selectedStatus || status === selectedStatus;

            if (matchesSearch && matchesStatus) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        section.style.display = visibleCount > 0 ? 'block' : 'none';
    });
}

searchInput.addEventListener('input', filterProducts);
categoryFilter.addEventListener('change', filterProducts);
statusFilter.addEventListener('change', filterProducts);

function orderProduct(productId) {
    window.location.href = '/product_order?product=' + productId;
}
</script>

<?php require_once '../config/footer.php'; ?>