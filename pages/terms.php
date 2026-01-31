<?php
// Luôn bắt đầu session ở đầu file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Giả lập hàm getContent và $content để file có thể chạy độc lập ---
function getTermsContent($lang) {
    $texts = [
        'vi' => [
            'page_title' => 'Điều khoản & Điều kiện',
            'main_title' => 'Điều khoản & Điều kiện',
            'back_link' => 'Quay lại',

            'intro_1' => 'Trước khi đăng ký và mua sắm trên các trang web và ứng dụng hoạt động dưới tên KingCongStudio, người dùng nên xem lại thông tin sau đây.',
            'intro_2' => 'Người dùng được coi là đã đọc và đồng ý với các điều khoản này nếu họ đã thực hiện mua hàng hoặc đăng ký trên trang web hoặc ứng dụng mang thương hiệu KingCongStudio. Vui lòng không đăng ký KingCongStudio và các trang web và ứng dụng mang tên KingCongStudio, và không chấp thuận bất kỳ thỏa thuận nào, nếu bạn không chấp nhận và đọc các điều kiện này.',
            'intro_3' => 'Trước khi sử dụng https://kingcongstudio.com/, bạn nên xem lại Điều khoản & Điều kiện, trong đó có các quy tắc chung và nghĩa vụ pháp lý của trang web, cũng như các điều khoản, quy tắc và nghĩa vụ pháp lý được liệt kê dưới đây.',
            'intro_4' => 'KingCongStudio đề nghị người dùng truy cập trang thỏa thuận người dùng mỗi khi họ đăng nhập vào trang web.',
            'intro_5' => 'Vui lòng không chấp nhận thỏa thuận này và ngừng sử dụng trang web này nếu bạn không đồng ý với các điều khoản đã nêu.',
            'intro_6' => 'Bằng cách sử dụng trang web này và điền vào biểu mẫu thành viên với thông tin cá nhân của bạn, bạn được coi là đã đồng ý với các điều khoản này.',
            'intro_7' => 'KingCongStudio có quyền thay đổi, tổ chức lại và ngừng phát sóng bất kỳ dịch vụ, sản phẩm, điều khoản sử dụng và thông tin nào được trình bày trên trang web mà không cần thông báo trước trên trang web này và các phần mở rộng của nó.',
            'intro_8' => 'Trong các trường hợp lạm dụng bằng văn bản sau đây, ban quản trị trang web có quyền ngăn người dùng truy cập trang web và theo đuổi hành động pháp lý đối với cá nhân hoặc các cá nhân chịu trách nhiệm.',

            'general_title' => 'Chung',
            'general_p1' => 'Các mô tả dịch vụ mạng xã hội trên Bảng điều khiển KingCongStudio https://kingcongstudio.com/ có giá trị cho mỗi 1000 đơn hàng, và khách hàng đặt hàng nên biết rằng số lượng sẽ tăng tương ứng với số lượng đặt hàng.',
            'general_p2' => 'Mô tả dịch vụ cho mỗi dịch vụ chứa các giải thích khác nhau. Trước khi đặt hàng, mọi khách hàng đã đọc và đồng ý với tất cả các điều khoản của mô tả. KingCongStudio không hoàn tiền cho bất kỳ đơn hàng nào nếu phần giải thích không được đọc.',
            'general_p3' => 'KingCongStudio sẽ tự động hoàn lại số dư còn lại của đơn hàng nếu một phần của nó được hoàn thành hoặc bị hủy do sự cố dịch vụ.',

            'refund_title' => 'Chính sách hoàn tiền',
            'refund_p1' => 'Mọi số dư, tín dụng hoặc thanh toán được tải lên KingCongStudio và các ứng dụng của nó không thể được hoàn lại; tuy nhiên, KingCongStudio và các ứng dụng của nó sẽ thực hiện hoàn trả khi các nhà quản lý của họ cho là cần thiết. Số dư được tải lên KingCongStudio và các ứng dụng của nó hoặc các khoản thanh toán cho KingCongStudio và các ứng dụng của nó là độc quyền cho KingCongStudio và các ứng dụng của nó.',
            'refund_p2' => 'Instagram, Twitter, và Facebook, trong số những người khác. Chúng tôi không biết khi nào các nền tảng truyền thông xã hội có thể cập nhật; trong trường hợp giảm sút, không thể hoàn lại tiền trừ khi nhà cung cấp dịch vụ bồi thường.',
            'refund_p3' => 'KingCongStudio và các ứng dụng của nó có quyền xóa người theo dõi hoặc lượt thích đã gửi nếu phát hiện những lời lăng mạ hoặc lạm dụng đối với các nhà quản lý và nhân viên của KingCongStudio.',
            'refund_p4' => 'Tài khoản của những cá nhân thanh toán thay mặt cho một cá nhân khác bằng thẻ tín dụng bị đánh cắp hoặc các phương thức thanh toán khác thông qua KingCongStudio và các ứng dụng của nó sẽ bị đóng mà không cần giải thích.',
            'refund_p5' => 'Sau khi các đơn hàng đã được nhập vào hệ thống, yêu cầu hủy/hoàn tiền của bạn sẽ bị từ chối. Hệ thống sẽ tự động hoàn tiền nếu đơn hàng không được hoàn thành hoặc hoàn thành một phần.',
            'refund_p6' => 'Mọi khách hàng đăng ký ứng dụng KingCongStudio đều chấp nhận các điều khoản và điều kiện nói trên và từ bỏ mọi khiếu nại và khiếu nại pháp lý.',
            'refund_p7' => 'KingCongStudio có quyền sửa đổi các điều khoản dịch vụ mà không cần thông báo trước.',
            'closing' => 'Trân trọng,',
            'team_name' => 'Đội ngũ KingCongStudio'
        ],
        'en' => [
            'page_title' => 'Terms & Conditions',
            'main_title' => 'Terms & Conditions',
            'back_link' => 'Back',

            'intro_1' => 'Before registering and shopping on websites and applications operating under the names KingCongStudio, users should review the following information.',
            'intro_2' => 'Users are deemed to have read and agreed to these terms if they have made a purchase or registered on a KingCongStudio-branded website or application. Please do not register for KingCongStudio and the sites and applications bearing the KingCongStudio name, and do not approve any agreements, if you do not accept and read these conditions.',
            'intro_3' => 'Before using https://kingcongstudio.com/, it is recommended that you review the Term Conditions, which contains the site\'s general rules and legal obligations, as well as the terms, rules, and legal obligations listed below.',
            'intro_4' => 'KingCongStudio suggests that users visit the user agreement page everytime they log in to the website.',
            'intro_5' => 'Please do not accept this agreement and discontinue use of this website if you do not agree with the specified terms.',
            'intro_6' => 'By using this website and filling out the membership form with your personal information, you are deemed to have agreed to these terms.',
            'intro_7' => 'KingCongStudio reserves the right to change, reorganize, and cease broadcasting any services, products, terms of use, and information presented on the site without prior notice on this site and its extensions.',
            'intro_8' => 'In the following instances of written abuse, site administration reserves the right to prevent the user from accessing the site and to pursue legal action against the individual or individuals responsible.',

            'general_title' => 'General',
            'general_p1' => 'The social media service descriptions on KingCongStudio Panel https://kingcongstudio.com/ are valid for every 1000 orders, and customers who place orders should be aware that the numbers will increase proportionally to the order quantity.',
            'general_p2' => 'The service descriptions for each service contain varying explanations. Prior to placing an order, every customer has read and agreed to all of the description\'s terms. KingCongStudio does not provide refunds for any orders if the explanation section is not read.',
            'general_p3' => 'KingCongStudio will automatically refund the remaining balance of your order if a portion of it is completed or canceled due to a service issue.',

            'refund_title' => 'Refund Policy',
            'refund_p1' => 'Every balance, credit, or payment uploaded to KingCongStudio and its applications cannot be refunded; however, KingCongStudio and its applications will make repayments when deemed necessary by their managers. Balances uploaded to KingCongStudio and its apps or payments made to KingCongStudio and its apps are exclusive to KingCongStudio and its apps.',
            'refund_p2' => 'Instagram, Twitter, and Facebook, among others. We do not know when social media platforms can update; in the event of a decrease, refunds cannot be issued unless the service provider provides compensation.',
            'refund_p3' => 'KingCongStudio and its applications reserve the right to remove sent followers or likes if insults or abuse against KingCongStudio managers and employees are discovered.',
            'refund_p4' => 'Accounts of individuals who make payments on behalf of another individual using a stolen credit card or other payment methods through KingCongStudio and its applications will be closed without explanation.',
            'refund_p5' => 'After the orders have been entered into the system, your cancellation/refund request will be denied. The system will automatically issue a refund if the order is not completed or partially completed.',
            'refund_p6' => 'Every customer who registers for KingCongStudio applications accepts the aforementioned terms and conditions and waives all complaints and legal claims.',
            'refund_p7' => 'KingCongStudio reserves the right to modify the terms of service without prior notification.',
            'closing' => 'Warmest regards,',
            'team_name' => 'KingCongStudio Team'
        ]
    ];
    return $texts[$lang] ?? $texts['en'];
}
// --- Kết thúc phần giả lập ---


// --- Xử lý ngôn ngữ ---
$allowed_langs = ['en', 'vi'];
$default_lang = 'en'; 

$lang = isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs) 
        ? $_GET['lang'] 
        : (isset($_SESSION['lang']) ? $_SESSION['lang'] : $default_lang);

$_SESSION['lang'] = $lang;

// --- Xác định URL để quay lại ---
$back_url = '/auth/register'; // URL mặc định nếu không có referer an toàn
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $server_host = $_SERVER['HTTP_HOST'];
    
    // Chỉ chấp nhận referer từ cùng một domain để tăng bảo mật
    if ($referer_host == $server_host) {
        $back_url = $_SERVER['HTTP_REFERER'];
    }
}

$content = getTermsContent($lang);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>"> 
<head>
    <title><?php echo htmlspecialchars($content['page_title']); ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="/assets/media/logos/favicon.ico" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Light Mode - Sáng rõ ràng */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .content-container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 40px auto;
            padding: 50px;
            transition: all 0.3s ease;
        }

        body.light-mode .content-container {
            background-color: #ffffff;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        /* Text Colors - Tối mode */
        h1, h2 {
            color: #1a1a1a;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        body.light-mode h1,
        body.light-mode h2 {
            color: #2d3748;
            border-bottom-color: #4299e1;
        }
        
        p {
            margin-bottom: 1.25rem;
            color: #4a5568;
            line-height: 1.8;
            transition: all 0.3s ease;
        }

        body.light-mode p {
            color: #4a5568;
        }

        /* Header Controls */
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .back-link {
            color: #667eea !important;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: rgba(102, 126, 234, 0.1);
        }

        body.light-mode .back-link {
            color: #4299e1 !important;
            background-color: rgba(66, 153, 225, 0.1);
        }

        .back-link:hover {
            transform: translateX(-5px);
            background-color: rgba(102, 126, 234, 0.2);
        }
        
        /* Language Switcher */
        .lang-switcher a {
            color: #718096 !important;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        body.light-mode .lang-switcher a {
            color: #4a5568 !important;
        }

        .lang-switcher a:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea !important;
        }

        body.light-mode .lang-switcher a:hover {
            background-color: rgba(66, 153, 225, 0.1);
            color: #4299e1 !important;
        }

        .lang-switcher a.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        body.light-mode .lang-switcher a.active {
            background: linear-gradient(135deg, #4299e1 0%, #667eea 100%);
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.4);
        }

        .lang-separator {
            color: #cbd5e0;
            margin: 0 4px;
        }

        body.light-mode .lang-separator {
            color: #a0aec0;
        }

        /* Theme Toggle Button */
        #theme-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        body.light-mode #theme-toggle {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }

        #theme-toggle:hover {
            transform: scale(1.1) rotate(10deg);
        }

        #theme-toggle i {
            color: #ffffff;
            font-size: 1.3rem;
        }

        /* Closing Section */
        .closing-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(102, 126, 234, 0.2);
        }

        body.light-mode .closing-section {
            border-top-color: rgba(66, 153, 225, 0.2);
        }

        .closing-section p {
            color: #2d3748;
            font-size: 1.05rem;
        }

        body.light-mode .closing-section p {
            color: #2d3748;
        }

        .closing-section strong {
            color: #667eea;
            font-size: 1.1rem;
        }

        body.light-mode .closing-section strong {
            color: #4299e1;
        }
    </style>
</head>
<body id="kt_body" class="app-blank">
    
    <div class="content-container">
        <div class="header-controls">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="back-link">
                <i class="bi bi-arrow-left me-2"></i><?php echo htmlspecialchars($content['back_link']); ?>
            </a>
            <div class="d-flex align-items-center">
                <div class="me-3 lang-switcher">
                    <a href="?lang=en" class="<?php if($lang == 'en') echo 'active'; ?>">EN</a>
                    <span class="lang-separator">|</span>
                    <a href="?lang=vi" class="<?php if($lang == 'vi') echo 'active'; ?>">VI</a>
                </div>
                <button id="theme-toggle" class="btn">
                    <i class="bi bi-sun-fill theme-icon-light"></i>
                    <i class="bi bi-moon-stars-fill theme-icon-dark d-none"></i>
                </button>
            </div>
        </div>

        <h1><?php echo htmlspecialchars($content['main_title']); ?></h1>
        
        <p><?php echo $content['intro_1']; ?></p>
        <p><?php echo $content['intro_2']; ?></p>
        <p><?php echo $content['intro_3']; ?></p>
        <p><?php echo $content['intro_4']; ?></p>
        <p><?php echo $content['intro_5']; ?></p>
        <p><?php echo $content['intro_6']; ?></p>
        <p><?php echo $content['intro_7']; ?></p>
        <p><?php echo $content['intro_8']; ?></p>

        <h2><?php echo htmlspecialchars($content['general_title']); ?></h2>
        <p><?php echo $content['general_p1']; ?></p>
        <p><?php echo $content['general_p2']; ?></p>
        <p><?php echo $content['general_p3']; ?></p>

        <h2><?php echo htmlspecialchars($content['refund_title']); ?></h2>
        <p><?php echo $content['refund_p1']; ?></p>
        <p><?php echo $content['refund_p2']; ?></p>
        <p><?php echo $content['refund_p3']; ?></p>
        <p><?php echo $content['refund_p4']; ?></p>
        <p><?php echo $content['refund_p5']; ?></p>
        <p><?php echo $content['refund_p6']; ?></p>
        <p><?php echo $content['refund_p7']; ?></p>

        <div class="closing-section">
            <p>
                <?php echo htmlspecialchars($content['closing']); ?><br>
                <strong><?php echo htmlspecialchars($content['team_name']); ?></strong>
            </p>
        </div>
    </div>

    <script>
       document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const body = document.body;
            const lightIcon = document.querySelector('.theme-icon-light');
            const darkIcon = document.querySelector('.theme-icon-dark');

            const applyTheme = (theme) => {
                if (theme === 'light') {
                    body.classList.add('light-mode');
                    lightIcon.classList.add('d-none');
                    darkIcon.classList.remove('d-none');
                } else {
                    body.classList.remove('light-mode');
                    lightIcon.classList.remove('d-none');
                    darkIcon.classList.add('d-none');
                }
            };

            // Mặc định là dark mode
            let savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            themeToggle.addEventListener('click', function() {
                let newTheme = body.classList.contains('light-mode') ? 'dark' : 'light';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });
       });
    </script>
</body>
</html>

