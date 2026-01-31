<?php
if (session_status() === PHP_SESSION_NONE) {
    // 🔥 THAY ĐỔI: Tăng lên 30 ngày
    ini_set('session.cookie_lifetime', 2592000); // 30 days (thay vì 0)
    ini_set('session.gc_maxlifetime', 2592000);
    session_start();
}

// Danh sách các trang public (không cần đăng nhập)
$public_pages = [
    '/orders',
    '/orders/',
    '/products',
    '/products/',
    '/auth/login',
    '/auth/login/',
    '/auth/register',
    '/auth/register/',
    // AI Pages (cho xem giao diện, chức năng vẫn cần login)
    '/ai/tts',
    '/ai/tts/',
    '/ai/voice_cloning',
    '/ai/voice_cloning/',
    '/ai/dubbing',
    '/ai/dubbing/',
    '/ai/stt',
    '/ai/stt/',
    '/ai/music',
    '/ai/music/',
    '/ai/kingcong/text_to_speech',
    '/ai/kingcong/text_to_speech/',
    '/ai/kingcong/voice_cloning',
    '/ai/kingcong/voice_cloning/',
    '/api-docs',
    '/api-docs/',
    '/terms',
    '/terms/',
];

// Lấy đường dẫn hiện tại
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Kiểm tra xem trang hiện tại có phải là trang public không
$is_public_page = in_array($current_path, $public_pages);

// --- ✅ CHECK BẢO TRÌ (TRƯỚC KHI CHECK LOGIN) ---
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/remember_me.php'; // ✅ ĐÃ ĐÚNG

// 🔥 TỰ ĐỘNG ĐĂNG NHẬP NẾU CÓ REMEMBER TOKEN
if (!isset($_SESSION['Users'])) {
    $rememberMe = new RememberMe($mysqli);
    $rememberMe->validateToken(); // ✅ ĐÃ ĐÚNG
}

// Check bảo trì
$stmt_maintenance = $mysqli->prepare("SELECT is_active FROM maintenance_mode WHERE service_path = ? AND is_active = 1");
if ($stmt_maintenance) {
    $stmt_maintenance->bind_param("s", $current_path);
    $stmt_maintenance->execute();
    $maintenance_result = $stmt_maintenance->get_result();

    if ($maintenance_result->num_rows > 0) {
        header("Location: /baotri?path=" . urlencode($current_path));
        exit();
    }
    $stmt_maintenance->close();
}

// Check if user is logged in (chỉ kiểm tra nếu KHÔNG phải trang public)
if (!$is_public_page && !isset($_SESSION['Users'])) {
    header("Location: /auth/login");
    exit();
}

// Khởi tạo biến mặc định cho trang public
$user = null;
$display_name = 'Guest';
$is_logged_in = false;

// Nếu user đã đăng nhập, lấy thông tin
if (isset($_SESSION['Users'])) {
    $is_logged_in = true;
    $taikhoan = $_SESSION['Users'];

    $sql = "SELECT * FROM Users WHERE taikhoan = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $taikhoan);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // 🔥 THÊM: Xóa remember token khi user không tồn tại
        if (class_exists('RememberMe')) {
            $rememberMe = new RememberMe($mysqli);
            $rememberMe->deleteToken();
        }
        
        session_destroy();
        header("Location: /auth/login");
        exit();
    }

    $stmt->close();
    
    // TẢI THÔNG BÁO
    $notifications = [];
    $unread_count = 0;
    
    if ($user) {
        $user_id = $user['id'];
        
        $stmt_count = $mysqli->prepare("SELECT COUNT(id) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->bind_param("i", $user_id);
        $stmt_count->execute();
        $count_result = $stmt_count->get_result()->fetch_assoc();
        $unread_count = $count_result['unread_count'] ?? 0;
        $stmt_count->close();
        
        $stmt_list = $mysqli->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt_list->bind_param("i", $user_id);
        $stmt_list->execute();
        $notifications = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_list->close();
    }
    
    function time_ago($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y' => 'năm', 'm' => 'tháng', 'w' => 'tuần', 'd' => 'ngày', 'h' => 'giờ', 'i' => 'phút', 's' => 'giây');
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v;
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
    }

    if ($user['google_id']) {
        $display_name = trim($user['ho'] . ' ' . $user['ten']);
        if (empty($display_name)) {
            $display_name = $user['name'] ?: $user['taikhoan'];
        }
    } else {
        $display_name = $user['taikhoan'];
    }
}

// Xử lý ngôn ngữ
$allowed_langs = ['en', 'vi'];
$default_lang = 'vi';

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) {
    $lang = $_SESSION['lang'];
} else {
    $lang = $default_lang;
    $_SESSION['lang'] = $lang;
}

if (isset($mysqli) && function_exists('getContent')) {
    $content = getContent($mysqli, $lang);
} else {
    $content = [];
}

// ========== SEO CONFIG FOR PAGES ==========
$seo_config = [
    '/ai/tts' => [
        'title' => 'Text to Speech AI Online - Chuyển Văn Bản Thành Giọng Nói | KingCong Studio',
        'description' => 'Công cụ Text to Speech AI chất lượng cao. Chuyển văn bản thành giọng nói tiếng Việt tự nhiên với ElevenLabs, MiniMax. Hỗ trợ 100+ giọng đọc.',
        'keywords' => 'text to speech, tts online, chuyển văn bản thành giọng nói, AI voice, elevenlabs tiếng việt'
    ],
    '/ai/voice_cloning' => [
        'title' => 'Voice Cloning AI - Nhân Bản Giọng Nói Bằng AI | KingCong Studio',
        'description' => 'Nhân bản giọng nói bằng AI chỉ với vài giây audio. Clone voice chất lượng cao, giống 99% giọng gốc. Thử miễn phí ngay!',
        'keywords' => 'voice cloning, nhân bản giọng nói, clone voice, AI voice clone, tạo giọng nói AI'
    ],
    '/ai/dubbing' => [
        'title' => 'AI Dubbing - Lồng Tiếng Video Tự Động Bằng AI | KingCong Studio',
        'description' => 'Lồng tiếng video tự động bằng AI. Dịch và dubbing video sang tiếng Việt hoặc bất kỳ ngôn ngữ nào. Nhanh, chính xác, tự nhiên.',
        'keywords' => 'ai dubbing, lồng tiếng AI, dịch video, dubbing video, lồng tiếng tự động'
    ],
    '/ai/stt' => [
        'title' => 'Speech to Text AI - Chuyển Giọng Nói Thành Văn Bản | KingCong Studio',
        'description' => 'Chuyển giọng nói thành văn bản tự động với AI. Hỗ trợ tiếng Việt và đa ngôn ngữ. Độ chính xác cao, xử lý nhanh.',
        'keywords' => 'speech to text, chuyển giọng nói thành văn bản, stt online, nhận dạng giọng nói'
    ],
    '/ai/music' => [
        'title' => 'AI Music Generator - Tạo Nhạc Bằng AI | KingCong Studio',
        'description' => 'Tạo nhạc tự động bằng AI. Sáng tác nhạc nền, beat, melody chỉ với vài click. Không cần kinh nghiệm âm nhạc.',
        'keywords' => 'ai music, tạo nhạc AI, sáng tác nhạc tự động, ai music generator'
    ],
    '/ai/kingcong/text_to_speech' => [
        'title' => 'KingCong TTS - Text to Speech Tiếng Việt Chất Lượng Cao | KingCong Studio',
        'description' => 'KingCong TTS - Engine Text to Speech riêng của KingCong Studio. Giọng đọc tiếng Việt tự nhiên nhất, giá rẻ nhất thị trường.',
        'keywords' => 'kingcong tts, text to speech tiếng việt, giọng đọc AI việt nam'
    ],
    '/ai/kingcong/voice_cloning' => [
        'title' => 'KingCong Voice Clone - Nhân Bản Giọng Nói Giá Rẻ | KingCong Studio',
        'description' => 'Clone giọng nói với KingCong AI. Chất lượng cao, giá rẻ hơn ElevenLabs. Tạo giọng đọc của riêng bạn trong 30 giây.',
        'keywords' => 'kingcong voice clone, nhân bản giọng nói giá rẻ, clone voice việt nam'
    ],
    '/orders' => [
        'title' => 'Dịch Vụ Seeding Facebook, Tăng Like, Follow | KingCong Studio',
        'description' => 'Dịch vụ seeding Facebook, Instagram, TikTok, YouTube. Tăng like, follow, comment, view giá rẻ. Bảo hành, uy tín #1 Việt Nam.',
        'keywords' => 'seeding facebook, tăng like, tăng follow, dịch vụ marketing, smm panel việt nam'
    ],
    '/products' => [
        'title' => 'Mua Tài Khoản Premium Giá Rẻ | KingCong Studio',
        'description' => 'Mua tài khoản premium Netflix, Spotify, Canva, ChatGPT giá rẻ. Bảo hành, hỗ trợ 24/7. Giao hàng tự động.',
        'keywords' => 'mua tài khoản premium, netflix giá rẻ, spotify premium, canva pro'
    ],
    '/api-docs' => [
        'title' => 'API Documentation - Tích Hợp Text to Speech | KingCong Studio',
        'description' => 'Tài liệu API Text to Speech, Voice Cloning của KingCong Studio. Dễ dàng tích hợp vào ứng dụng của bạn.',
        'keywords' => 'tts api, voice api, text to speech api, kingcong api'
    ],
];

// ========== BREADCRUMB CONFIG ==========
$breadcrumb_config = [
    '/ai/tts' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Công cụ AI', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Text to Speech', 'url' => '/ai/tts']
    ],
    '/ai/voice_cloning' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Công cụ AI', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Voice Cloning', 'url' => '/ai/voice_cloning']
    ],
    '/ai/dubbing' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Công cụ AI', 'url' => '/ai/kingcong/billing'],
        ['name' => 'AI Dubbing', 'url' => '/ai/dubbing']
    ],
    '/ai/stt' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Công cụ AI', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Speech to Text', 'url' => '/ai/stt']
    ],
    '/ai/music' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Công cụ AI', 'url' => '/ai/kingcong/billing'],
        ['name' => 'AI Music', 'url' => '/ai/music']
    ],
    '/ai/kingcong/text_to_speech' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Server KingCong', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Text to Speech', 'url' => '/ai/kingcong/text_to_speech']
    ],
    '/ai/kingcong/voice_cloning' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Server KingCong', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Voice Cloning', 'url' => '/ai/kingcong/voice_cloning']
    ],
    '/ai/kingcong/billing' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Server KingCong', 'url' => '/ai/kingcong/billing'],
        ['name' => 'Mua Tín Dụng', 'url' => '/ai/kingcong/billing']
    ],
    '/orders' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Dịch Vụ Seeding', 'url' => '/orders']
    ],
    '/products' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'Sản Phẩm', 'url' => '/products']
    ],
    '/api-docs' => [
        ['name' => 'Trang chủ', 'url' => '/'],
        ['name' => 'API Documentation', 'url' => '/api-docs']
    ],
];

// Get SEO data for current page
$current_seo = $seo_config[$current_path] ?? null;
$seo_title = $current_seo['title'] ?? (isset($page_title) ? $page_title . ' | KingCong Studio' : 'KingCong Studio - AI Voice Platform');
$seo_description = $current_seo['description'] ?? 'KingCong Studio - Nền tảng AI Voice #1 Việt Nam. Text to Speech, Voice Cloning, Dubbing với công nghệ AI tiên tiến.';
$seo_keywords = $current_seo['keywords'] ?? 'kingcong studio, text to speech, voice cloning, ai voice';

// Get breadcrumb for current page
$current_breadcrumb = $breadcrumb_config[$current_path] ?? [['name' => 'Trang chủ', 'url' => '/']];
$canonical_url = 'https://kingcongstudio.com' . $current_path;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Analytics (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7ZWLNX6E9J"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-7ZWLNX6E9J');
    </script>

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <meta name="title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
    <meta name="author" content="KingCong Studio">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:site_name" content="KingCong Studio">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image" content="https://kingcongstudio.com/banner.png">
    <meta property="og:locale" content="vi_VN">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="twitter:image" content="https://kingcongstudio.com/banner.png">

    <!-- Favicon -->
    <link rel="shortcut icon" href="/assets/media/logos/Kingkong.jpg">
    <link rel="apple-touch-icon" href="/assets/media/logos/Kingkong.jpg">

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebPage",
      "@id": "<?php echo htmlspecialchars($canonical_url); ?>#webpage",
      "name": "<?php echo htmlspecialchars($seo_title); ?>",
      "description": "<?php echo htmlspecialchars($seo_description); ?>",
      "url": "<?php echo htmlspecialchars($canonical_url); ?>",
      "isPartOf": {
        "@type": "WebSite",
        "@id": "https://kingcongstudio.com/#website",
        "name": "KingCong Studio",
        "url": "https://kingcongstudio.com"
      },
      "breadcrumb": {"@id": "<?php echo htmlspecialchars($canonical_url); ?>#breadcrumb"}
    },
    {
      "@type": "BreadcrumbList",
      "@id": "<?php echo htmlspecialchars($canonical_url); ?>#breadcrumb",
      "itemListElement": [
<?php
$breadcrumb_items = [];
foreach ($current_breadcrumb as $index => $item) {
    $breadcrumb_items[] = '        {
          "@type": "ListItem",
          "position": ' . ($index + 1) . ',
          "name": "' . htmlspecialchars($item['name']) . '",
          "item": "https://kingcongstudio.com' . htmlspecialchars($item['url']) . '"
        }';
}
echo implode(",\n", $breadcrumb_items);
?>

      ]
    },
    {
      "@type": "Organization",
      "@id": "https://kingcongstudio.com/#organization",
      "name": "KingCong Studio",
      "url": "https://kingcongstudio.com",
      "logo": {
        "@type": "ImageObject",
        "url": "https://kingcongstudio.com/assets/media/logos/Kingkong.jpg"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+84353633663",
        "contactType": "customer service",
        "areaServed": "VN",
        "availableLanguage": ["Vietnamese", "English"]
      },
      "sameAs": [
        "https://www.facebook.com/61578186428817",
        "https://www.youtube.com/channel/UCgZ_H4Jc0voj6-QsENauJPQ",
        "https://t.me/buicongxd11"
      ]
    }
  ]
}
</script>

    <!-- ⚡ CRITICAL: Script này PHẢI chạy TRƯỚC KHI render HTML để tránh flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.className = savedTheme;
        })();
    </script>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/config/css/header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/config/css/thongbao.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/config/css/dashboard-darkmode-sync.css?v=<?php echo time(); ?>">
    
    <script>
/* =====================================================
   JAVASCRIPT - SIDEBAR TOGGLE BUTTON
   Thêm vào script hiện tại
   ===================================================== */

// ========== SIDEBAR TOGGLE BUTTON ==========
const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebarToggleBtn) {
    sidebarToggleBtn.addEventListener('click', function() {
        // Toggle sidebar
        if (sidebar) sidebar.classList.toggle('active');
        if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        
        // ✅ Lưu trạng thái sidebar
        const isActive = sidebar ? sidebar.classList.contains('active') : false;
        localStorage.setItem('sidebarState', isActive ? 'open' : 'closed');
    });
}

// ✅ Khôi phục trạng thái sidebar khi load trang (chỉ trên mobile)
if (window.innerWidth <= 1024) {
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'open' && sidebar) {
        sidebar.classList.add('active');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
    }
}

// Đóng sidebar khi click overlay
if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
        if (sidebar) sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        localStorage.setItem('sidebarState', 'closed');
    });
}


/* =====================================================
   FULL JAVASCRIPT - COMPLETE VERSION VỚI SIDEBAR TOGGLE
   ===================================================== */

/* =====================================================
   JAVASCRIPT - SIDEBAR TOGGLE CẢ DESKTOP & MOBILE
   ===================================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== SIDEBAR TOGGLE BUTTON ==========
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleBtn && sidebar) {
        
        // ✅ Load saved state (desktop: default mở, mobile: default đóng)
        const isDesktop = window.innerWidth > 1024;
        const savedState = localStorage.getItem('sidebarState');
        
        if (savedState === 'closed') {
            sidebar.classList.remove('active');
        } else if (savedState === 'open') {
            sidebar.classList.add('active');
        } else {
            // Default: Desktop mở, Mobile đóng
            if (isDesktop) {
                sidebar.classList.add('active');
            } else {
                sidebar.classList.remove('active');
            }
        }
        
        // ✅ Click toggle button
        sidebarToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Overlay chỉ trên mobile
            if (window.innerWidth <= 1024 && sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            
            // Lưu trạng thái
            const isActive = sidebar.classList.contains('active');
            localStorage.setItem('sidebarState', isActive ? 'open' : 'closed');
        });
    }

    // ✅ Click overlay để đóng (chỉ mobile)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            localStorage.setItem('sidebarState', 'closed');
        });
    }
    
    // ✅ Xử lý khi resize window
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const isDesktop = window.innerWidth > 1024;
            
            if (isDesktop) {
                // Desktop: Ẩn overlay
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        }, 250);
    });
    
    
    // ========== THEME SYSTEM ========== (giữ nguyên code cũ)
    const themeToggle = document.getElementById('theme-toggle');
    const themeDropdown = document.getElementById('theme-dropdown');
    const html = document.documentElement;
    
    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    function applyTheme(theme) {
        let actualTheme = theme;
        if (theme === 'system') {
            actualTheme = getSystemTheme();
        }
        
        requestAnimationFrame(() => {
            if (actualTheme === 'dark') {
                html.classList.remove('light');
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
                html.classList.add('light');
            }
        });
        
        updateThemeButtonIcon(theme);
        updateThemeCheckmarks(theme);
        localStorage.setItem('theme', theme);
    }
    
    function updateThemeButtonIcon(theme) {
        const lightIcon = document.querySelector('.theme-icon-light');
        const darkIcon = document.querySelector('.theme-icon-dark');
        const systemIcon = document.querySelector('.theme-icon-system');
        
        if (lightIcon) lightIcon.classList.remove('active');
        if (darkIcon) darkIcon.classList.remove('active');
        if (systemIcon) systemIcon.classList.remove('active');
        
        if (theme === 'light' && lightIcon) {
            lightIcon.classList.add('active');
        } else if (theme === 'dark' && darkIcon) {
            darkIcon.classList.add('active');
        } else if (theme === 'system' && systemIcon) {
            systemIcon.classList.add('active');
        }
    }
    
    function updateThemeCheckmarks(theme) {
        const checkLight = document.getElementById('check-light');
        const checkDark = document.getElementById('check-dark');
        const checkSystem = document.getElementById('check-system');
        
        if (checkLight) checkLight.classList.toggle('d-none', theme !== 'light');
        if (checkDark) checkDark.classList.toggle('d-none', theme !== 'dark');
        if (checkSystem) checkSystem.classList.toggle('d-none', theme !== 'system');
    }
    
    let savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    
    if (themeToggle && themeDropdown) {
        themeToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const languageDropdown = document.getElementById('language-dropdown');
            if (languageDropdown) languageDropdown.classList.remove('show');
            themeDropdown.classList.toggle('show');
        });
    }
    
    const themeItems = document.querySelectorAll('[data-theme]');
    themeItems.forEach(item => {
        item.addEventListener('click', function() {
            const selectedTheme = this.getAttribute('data-theme');
            applyTheme(selectedTheme);
            if (themeDropdown) themeDropdown.classList.remove('show');
        });
    });
    
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'system') {
            requestAnimationFrame(() => applyTheme('system'));
        }
    });
    
    // ========== LANGUAGE DROPDOWN ==========
    const languageToggle = document.getElementById('language-toggle');
    const languageDropdown = document.getElementById('language-dropdown');
    
    if (languageToggle && languageDropdown) {
        languageToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (themeDropdown) themeDropdown.classList.remove('show');
            languageDropdown.classList.toggle('show');
        });
    }
    
    // ========== NOTIFICATION DROPDOWN ==========
    const notiToggle = document.getElementById('notification-toggle');
    const notiDropdown = document.getElementById('notification-dropdown');

    if (notiToggle && notiDropdown) {
        notiToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            if (languageDropdown) languageDropdown.classList.remove('show');
            if (themeDropdown) themeDropdown.classList.remove('show');
            notiDropdown.classList.toggle('show');
        });
    }
    
    // ========== CLOSE DROPDOWNS ==========
    document.addEventListener('click', function(e) {
        const languageContainer = document.getElementById('language-dropdown-container');
        const themeContainer = document.getElementById('theme-dropdown-container');
        const notiContainer = document.getElementById('notification-container');
        
        if (languageContainer && !languageContainer.contains(e.target)) {
            if (languageDropdown) languageDropdown.classList.remove('show');
        }
        
        if (themeContainer && !themeContainer.contains(e.target)) {
            if (themeDropdown) themeDropdown.classList.remove('show');
        }
        
        if (notiContainer && !notiContainer.contains(e.target)) {
            if (notiDropdown) notiDropdown.classList.remove('show');
        }
    });
});

// Preload script
(function() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'system') {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.className = systemTheme;
    } else {
        document.documentElement.className = savedTheme;
    }
})();
    </script>
</head>
<!-- NOTIFICATION POPUP -->
<div class="notification-popup-overlay" id="notificationOverlay"></div>
<div class="notification-popup" id="notificationPopup">
    <button class="notification-popup-close" onclick="closeNotificationPopup()">×</button>
    <img src="/assets/media/notifications/popup-image.svg" alt="Thông báo" class="notification-popup-image">
    <div class="notification-popup-buttons">
        <a href="https://zalo.me/g/rjrdno008" target="_blank" class="notification-popup-btn" style="text-decoration: none; text-align: center; display: flex; align-items: center; justify-content: center;">
            Nhóm Zalo
        </a>
        <button class="notification-popup-btn" onclick="hideFor2Hours()">
            Ẩn 2 giờ
        </button>
    </div>
</div>

<script>
// ===== NOTIFICATION POPUP SYSTEM =====
document.addEventListener('DOMContentLoaded', function() {
    const POPUP_EXPIRY_HOURS = 2;
    const STORAGE_KEY = 'notification_popup_auto_hidden_time'; // ✅ Đổi tên key để phân biệt
    
    function showNotificationPopup() {
        const overlay = document.getElementById('notificationOverlay');
        const popup = document.getElementById('notificationPopup');
        
        if (overlay && popup) {
            // Force reflow để animation hoạt động
            overlay.offsetHeight;
            popup.offsetHeight;
            
            overlay.classList.add('show');
            popup.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            console.log('✅ Popup hiển thị thành công');
        } else {
            console.error('❌ Không tìm thấy popup elements:', {
                overlay: !!overlay,
                popup: !!popup
            });
        }
    }
    
    function hideNotificationPopup() {
        const overlay = document.getElementById('notificationOverlay');
        const popup = document.getElementById('notificationPopup');
        
        if (overlay && popup) {
            popup.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            console.log('✅ Popup đã đóng');
        }
    }
    
    // ✅ CHỈ KIỂM TRA CHO AUTO-SHOW (không dùng cho button click)
    function shouldAutoShowPopup() {
        const hiddenTime = localStorage.getItem(STORAGE_KEY);
        
        if (!hiddenTime) {
            return true; // Chưa từng ẩn → hiện
        }
        
        const hiddenDate = new Date(parseInt(hiddenTime));
        const now = new Date();
        const hoursPassed = (now - hiddenDate) / (1000 * 60 * 60);
        
        return hoursPassed >= POPUP_EXPIRY_HOURS; // Đã quá 2 giờ → hiện
    }
    
    // ✅ Button "Xong" - chỉ đóng popup (KHÔNG lưu thời gian)
    window.closeNotificationPopup = function() {
        console.log('🔵 closeNotificationPopup called');
        hideNotificationPopup();
    };
    
    // ✅ Button "Ẩn 2 giờ" - đóng và lưu thời gian (CHỈ ẢNH HƯỞNG AUTO-SHOW)
    window.hideFor2Hours = function() {
        console.log('🔵 hideFor2Hours called');
        hideNotificationPopup();
        localStorage.setItem(STORAGE_KEY, Date.now().toString());
        console.log('⏰ Đã lưu thời gian ẩn auto-show');
    };
    
    // ✅ HIỆN POPUP KHI CLICK NÚT THÔNG BÁO - LUÔN HIỆN (BỎ QUA THỜI GIAN ẨN)
    window.showNotificationFromButton = function() {
        console.log('🔵 showNotificationFromButton called - FORCE SHOW');
        showNotificationPopup(); // ✅ Luôn hiện, không check shouldAutoShowPopup()
    };
    
    // ✅ Click overlay để đóng
    const overlay = document.getElementById('notificationOverlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeNotificationPopup();
        });
    }
    
    // ✅ ESC key để đóng popup
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const popup = document.getElementById('notificationPopup');
            if (popup && popup.classList.contains('show')) {
                closeNotificationPopup();
            }
        }
    });
    if (shouldAutoShowPopup()) {
        setTimeout(() => {
            showNotificationPopup();
            console.log('⏰ Auto-show popup (chưa bị ẩn hoặc đã hết 2 giờ)');
        }, 800);
    } else {
        const hiddenTime = localStorage.getItem(STORAGE_KEY);
        const hiddenDate = new Date(parseInt(hiddenTime));
        const now = new Date();
        const minutesLeft = Math.ceil((POPUP_EXPIRY_HOURS * 60) - ((now - hiddenDate) / (1000 * 60)));
        console.log(`⏰ Auto-show bị ẩn, còn ${minutesLeft} phút`);
    }
});
</script>
<body>
    <div class="dashboard-wrapper">
