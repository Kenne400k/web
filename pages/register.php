<?php
// Luôn bắt đầu session ở đầu file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// [AFFILIATE] Lưu mã giới thiệu vào Cookie (nếu có trên URL)
$ref_code_from_url = '';
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $ref_code_from_url = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ref']);
}
// Nhúng file cấu hình database và hàm getContent
// Giả định rằng file này tồn tại và hoạt động đúng cách
// require_once __DIR__ . '/../config/database.php';

// --- Giả lập hàm getContent và $content để file có thể chạy độc lập ---
function getContent($mysqli, $lang) {
    $texts = [
        'vi' => [
            'register_title' => 'Đăng Ký Tài Khoản',
            'site_title' => 'KingCongStudio',
            'register_subtitle' => 'Tạo tài khoản để bắt đầu',
            'or_divider' => 'Hoặc đăng ký với',
            'username_placeholder' => 'Tên đăng nhập',
            'email_placeholder' => 'Địa chỉ Email',
            'phone_placeholder' => 'Số điện thoại',
            'password_placeholder' => 'Mật khẩu',
            'confirm_password_placeholder' => 'Xác nhận lại Mật khẩu',
            'agree_terms' => 'Tôi đồng ý với',
            'terms_link' => 'Điều khoản Dịch vụ',
            'register_button' => 'Đăng Ký',
            'processing_button' => 'Đang xử lý...',
            'already_have_account' => 'Đã có tài khoản?',
            'login_link' => 'Đăng Nhập',
            'platform_slogan' => 'Nền tảng uy tín và chất lượng <br/> được hàng ngàn người tin dùng.',
            'referral_code_placeholder' => 'Mã giới thiệu (nếu có)',
            'referral_code_hint' => 'Nếu có mã giới thiệu',
            'referred_by_label' => 'Bạn được giới thiệu bởi',
            'referral_code_label' => 'Mã:',
        ],
        'en' => [
            'register_title' => 'Create Account',
            'site_title' => 'KingCongStudio',
            'register_subtitle' => 'Create an account to get started',
            'or_divider' => 'Or sign up with',
            'username_placeholder' => 'Username',
            'email_placeholder' => 'Email Address',
            'phone_placeholder' => 'Phone Number',
            'password_placeholder' => 'Password',
            'confirm_password_placeholder' => 'Confirm Password',
            'agree_terms' => 'I Agree to the',
            'terms_link' => 'Terms of Service',
            'register_button' => 'Sign Up',
            'processing_button' => 'Processing...',
            'already_have_account' => 'Already have an account?',
            'login_link' => 'Sign In',
            'platform_slogan' => 'A reliable and high-quality platform <br/> trusted by thousands of users.',
            'referral_code_placeholder' => 'Referral Code (optional)',
            'referral_code_hint' => 'Enter referral code to receive benefits',
            'referred_by_label' => 'You are referred by',
            'referral_code_label' => 'Code:',
        ]
    ];
    return $texts[$lang] ?? $texts['en'];
}
$mysqli = null; // Giả lập biến kết nối DB
// --- Kết thúc phần giả lập ---


// --- Xử lý ngôn ngữ ---
$allowed_langs = ['en', 'vi'];
$default_lang = 'en'; // Ngôn ngữ mặc định là tiếng Anh

// Ưu tiên: Lấy từ URL (?lang=...) -> Lấy từ Session -> Mặc định
$lang = isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs) 
        ? $_GET['lang'] 
        : (isset($_SESSION['lang']) ? $_SESSION['lang'] : $default_lang);

// Lưu ngôn ngữ đã chọn vào session để dùng cho các trang khác
$_SESSION['lang'] = $lang;

// Lấy toàn bộ nội dung text từ database dựa trên ngôn ngữ đã chọn
$content = getContent($mysqli, $lang);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" class="dark"> 
<head>
    <title><?php echo htmlspecialchars($content['register_title']); ?> - <?php echo htmlspecialchars($content['site_title']); ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="/assets/media/logos/favicon.ico" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        :root {
            --page-bg: #F5F7FA;
            --form-bg: #FFFFFF;
            --text-color: #1E293B;
            --text-secondary: #64748B;
            --input-bg: #F8FAFC;
            --input-border: #E2E8F0;
            --input-focus: #3B82F6;
            --primary-color: #3B82F6;
            --primary-hover: #2563EB;
            --shadow: rgba(0, 0, 0, 0.1);
            --image-section-bg: #EFF6FF;
        }
        
        html.dark {
            --page-bg: #0F172A;
            --form-bg: #1E293B;
            --text-color: #F1F5F9;
            --text-secondary: #94A3B8;
            --input-bg: #334155;
            --input-border: #475569;
            --input-focus: #60A5FA;
            --primary-color: #3B82F6;
            --primary-hover: #2563EB;
            --shadow: rgba(0, 0, 0, 0.5);
            --image-section-bg: #1E293B;
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background-color: var(--page-bg);
            font-family: 'Inter', sans-serif;
        }

        .auth-container {
            min-height: 100vh;
        }

        .form-section-container {
            background-color: var(--form-bg);
            box-shadow: 0 10px 30px var(--shadow);
        }

        .image-section {
            background: linear-gradient(135deg, var(--image-section-bg) 0%, var(--page-bg) 100%);
            position: relative;
            overflow: hidden;
        }

        .image-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.1) rotate(180deg); opacity: 0.8; }
        }

        .image-section img {
            position: relative;
            z-index: 1;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 20px 40px var(--shadow));
        }

        .image-section .overlay-text {
            position: relative;
            z-index: 2;
            color: var(--text-color);
            text-shadow: 0 2px 10px var(--shadow);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .form-container {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: var(--text-color) !important;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .text-gray-500 {
            color: var(--text-secondary) !important;
        }

        .form-control, .form-select {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-color);
            padding: 12px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--input-focus);
            color: var(--text-color);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        #theme-toggle {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        #theme-toggle:hover {
            transform: rotate(180deg) scale(1.1);
            border-color: var(--primary-color);
        }

        .theme-icon-light, .theme-icon-dark {
            color: var(--text-color);
        }

        .lang-switcher a {
            color: var(--text-secondary) !important;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .lang-switcher a:hover {
            background-color: var(--input-bg);
            color: var(--text-color) !important;
        }

        .lang-switcher a.active {
            color: var(--primary-color) !important;
            background-color: rgba(59, 130, 246, 0.1);
            font-weight: 600;
        }

        .separator-content {
            color: var(--text-secondary);
        }

        .separator.separator-content .separator-line {
            border-color: var(--input-border);
        }

        .form-check-input {
            border: 2px solid var(--input-border);
            background-color: var(--input-bg);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: var(--text-color);
        }

        .link-primary {
            color: var(--primary-color) !important;
            transition: all 0.3s ease;
        }

        .link-primary:hover {
            color: var(--primary-hover) !important;
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            border: none;
            animation: slideInDown 0.4s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .g_id_signin {
            border-radius: 10px !important;
            transition: all 0.3s ease;
        }

        .g_id_signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--shadow);
        }

        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--page-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--input-border);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        /* Loading spinner animation */
        .spinner-border {
            animation: spin 0.75s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body id="kt_body" class="app-blank">
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid auth-container">
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1 form-section-container">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10 form-container">
                        <div class="d-flex justify-content-end align-items-center mb-10">
                            <div class="me-3 lang-switcher">
                                <a href="?lang=en" class="<?php if($lang == 'en') echo 'active'; ?>">EN</a>
                                <span class="mx-1" style="color: var(--text-secondary)">|</span>
                                <a href="?lang=vi" class="<?php if($lang == 'vi') echo 'active'; ?>">VI</a>
                            </div>
                            <button id="theme-toggle" class="btn btn-icon w-40px h-40px">
                                <i class="bi bi-sun-fill fs-3 theme-icon-light d-none"></i>
                                <i class="bi bi-moon-stars-fill fs-3 theme-icon-dark"></i>
                            </button>
                        </div>
                        <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form">
                            <div class="text-center mb-11">
                                <h1 class="fw-bolder mb-3"><?php echo htmlspecialchars($content['register_title']); ?></h1>
                                <div class="text-gray-500 fw-semibold fs-6"><?php echo $content['register_subtitle']; ?></div>
                            </div>
                            
                            <div class="d-flex justify-content-center mb-10">
                                <div id="g_id_onload" data-client_id="189817716633-jt198qfpjmtno4hjmhm4fd4aqo5eo452.apps.googleusercontent.com" data-context="signup" data-ux_mode="popup" data-callback="handleCredentialResponse" data-auto_prompt="false"></div>
                                <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline" data-text="signup_with" data-size="large" data-logo_alignment="left"></div>
                            </div>
                            <div class="separator separator-content my-10"><span class="w-125px text-gray-500 fw-semibold fs-7"><?php echo htmlspecialchars($content['or_divider']); ?></span></div>
                            <div id="status" class="mb-5 text-center"></div>
                            <div class="fv-row mb-8">
                                <input type="text" placeholder="<?php echo htmlspecialchars($content['username_placeholder']); ?>" id="taikhoan" name="taikhoan" class="form-control" required />
                            </div>
                            <div class="fv-row mb-8">
                                <input type="email" placeholder="<?php echo htmlspecialchars($content['email_placeholder']); ?>" id="email" name="email" class="form-control" required/>
                            </div>
                            <div class="fv-row mb-8">
                                <input type="tel" placeholder="<?php echo htmlspecialchars($content['phone_placeholder']); ?>" id="sdt" name="sdt" class="form-control" required/>
                            </div>
                            <div class="fv-row mb-8">
                                <input placeholder="<?php echo htmlspecialchars($content['password_placeholder']); ?>" id="password" name="password" type="password" class="form-control" required />
                            </div>
                            <div class="fv-row mb-8">
                                <input placeholder="<?php echo htmlspecialchars($content['confirm_password_placeholder']); ?>" id="password2" name="password2" type="password" class="form-control" required />
                            </div>

                            <!-- ✅ Ô NHẬP MÃ GIỚI THIỆU -->
                            <div class="fv-row mb-8">
                                <input 
                                    type="text" 
                                    placeholder="<?php echo htmlspecialchars($content['referral_code_placeholder']); ?>" 
                                    id="ref_code" 
                                    name="ref_code" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($ref_code_from_url); ?>"
                                    maxlength="20"
                                    style="text-transform: uppercase;"
                                />
                                <div class="form-text text-muted mt-2" style="font-size: 12px;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?php echo $content['referral_code_hint']; ?>
                                </div>
                            </div>

                            <div class="fv-row mb-8">
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="toc" value="1"/>
                                    <span class="form-check-label fw-semibold fs-6 ms-1">
                                        <?php echo $content['agree_terms']; ?>
                                        <a href="/terms" class="ms-1 link-primary"><?php echo htmlspecialchars($content['terms_link']); ?></a>
                                    </span>
                                </label>
                            </div>
                            
                            <!-- ✅ Alert hiển thị mã giới thiệu -->
                            <?php if (!empty($ref_code_from_url)): ?>
                                <div class="alert alert-success py-3 mb-5" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people-fill fs-2 me-3 text-success"></i> 
                                        <div>
                                            <div class="fw-bold">Bạn được giới thiệu bởi</div>
                                            <div class="text-muted">Mã: <strong class="text-success"><?php echo htmlspecialchars($ref_code_from_url); ?></strong></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid mb-10">
                                <button type="submit" id="register_btn" class="btn btn-primary">
                                    <span class="indicator-label"><?php echo htmlspecialchars($content['register_button']); ?></span>
                                    <span class="indicator-progress" style="display:none;"><?php echo $content['processing_button']; ?><span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                            <div class="text-gray-500 text-center fw-semibold fs-6">
                                <?php echo $content['already_have_account']; ?>
                                <a href="/auth/login" class="link-primary fw-bold"><?php echo htmlspecialchars($content['login_link']); ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-none d-lg-flex flex-lg-row-fluid w-lg-50 order-1 order-lg-2 align-items-center justify-content-center image-section">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <img src="/assets/img/8.png" alt="Register Illustration" class="img-fluid mb-10" style="max-width: 400px; max-height: 60vh;">
                    <h1 class="overlay-text fs-2qx fw-bolder text-center mb-7">
                        KingCongStudio
                    </h1>
                    <div class="overlay-text fs-base text-center">
                        <?php echo $content['platform_slogan']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
    <script src="/assets/plugins/global/plugins.bundle.js"></script>
    
    <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        const currentLang = '<?php echo htmlspecialchars($lang); ?>';
        const translations = {
            vi: {
                googleAuth: "Đang xác thực với Google...",
                googleAuthError: "Lỗi kết nối tới server khi xác thực Google.",
                googleUnknownError: "Lỗi không xác định.",
                validationRequired: "Vui lòng điền đầy đủ thông tin bắt buộc.",
                validationUsernameLength: "Tên đăng nhập phải có ít nhất 3 ký tự.",
                validationEmailInvalid: "Định dạng email không hợp lệ.",
                validationPhoneInvalid: "Số điện thoại không hợp lệ (phải có 10 số, bắt đầu bằng 0).",
                validationPasswordLength: "Mật khẩu phải có ít nhất 8 ký tự.",
                validationPasswordMismatch: "Mật khẩu nhập lại không khớp.",
                validationAgreeTerms: "Bạn phải đồng ý với các điều khoản.",
                refCodeInvalid: "Mã giới thiệu không hợp lệ (4-20 ký tự chữ/số)",
                registerSuccess: "Đăng ký thành công! Đang chuyển hướng...",
                registerError: "Có lỗi xảy ra, vui lòng thử lại.",
                connectionError: "Lỗi kết nối đến máy chủ. Vui lòng thử lại sau.",
                systemError: "Lỗi Hệ Thống"
            },
            en: {
                googleAuth: "Authenticating with Google...",
                googleAuthError: "Connection error when authenticating with Google.",
                googleUnknownError: "An unknown error occurred.",
                validationRequired: "Please fill in all required fields.",
                validationUsernameLength: "Username must be at least 3 characters long.",
                validationEmailInvalid: "Invalid email format.",
                validationPhoneInvalid: "Invalid phone number (must be 10 digits, starting with 0).",
                validationPasswordLength: "Password must be at least 8 characters long.",
                validationPasswordMismatch: "The passwords do not match.",
                validationAgreeTerms: "You must agree to the terms of service.",
                refCodeInvalid: "Invalid referral code (4-20 alphanumeric characters)",
                registerSuccess: "Registration successful! Redirecting...",
                registerError: "An error occurred, please try again.",
                connectionError: "Could not connect to the server. Please try again later.",
                systemError: "System Error"
            }
        };

        // --- GOOGLE SIGN-IN CALLBACK ---
        function handleCredentialResponse(response) {
            $('#status').html(`<div class="alert alert-info py-2">${translations[currentLang].googleAuth}</div>`);
            $.ajax({
                url: '/ajaxs/google-auth.php',
                type: 'POST',
                dataType: 'json',
                data: { id_token: response.credential },
                success: function(res) {
                    if (res.status === 'success') {
                        toastr.success(res.message || "Authentication successful!");
                        setTimeout(() => window.location.href = res.redirect_url, 1500);
                    } else {
                        toastr.error(res.message || translations[currentLang].googleUnknownError);
                    }
                },
                error: function() {
                    toastr.error(translations[currentLang].googleAuthError);
                }
            });
        }

       $(document).ready(function() {
            // --- THEME TOGGLER ---
            const themeToggle = $('#theme-toggle');
            const html = $('html');
            const applyTheme = (theme) => {
                if (theme === 'dark') {
                    html.addClass('dark');
                    $('.theme-icon-light').removeClass('d-none');
                    $('.theme-icon-dark').addClass('d-none');
                } else {
                    html.removeClass('dark');
                    $('.theme-icon-light').addClass('d-none');
                    $('.theme-icon-dark').removeClass('d-none');
                }
            };
            let savedTheme = localStorage.getItem('theme') || 'dark';
            applyTheme(savedTheme);

            themeToggle.on('click', function() {
                let newTheme = html.hasClass('dark') ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });
            
            // --- REGISTER FORM HANDLER ---
            $('#kt_sign_up_form').on('submit', function(e) {
                e.preventDefault();

                const btn = $('#register_btn');
                const btnLabel = btn.find('.indicator-label');
                const btnProgress = btn.find('.indicator-progress');

                const data = {
                    taikhoan: $('#taikhoan').val().trim(),
                    email: $('#email').val().trim(),
                    sdt: $('#sdt').val().trim(),
                    password: $('#password').val(),
                    password2: $('#password2').val(),
                    ref_code: $('#ref_code').val().trim().toUpperCase()
                };

                // --- VALIDATION ---
                if (!data.taikhoan || !data.email || !data.sdt || !data.password || !data.password2) {
                    toastr.error(translations[currentLang].validationRequired); 
                    return;
                }
                
                if (data.taikhoan.length < 3) {
                    toastr.error(translations[currentLang].validationUsernameLength); 
                    return;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
                    toastr.error(translations[currentLang].validationEmailInvalid); 
                    return;
                }
                
                if (!/^(0\d{9})$/.test(data.sdt)) {
                    toastr.error(translations[currentLang].validationPhoneInvalid); 
                    return;
                }
                
                if (data.password.length < 8) {
                     toastr.error(translations[currentLang].validationPasswordLength); 
                     return;
                }
                
                if (data.password !== data.password2) {
                    toastr.error(translations[currentLang].validationPasswordMismatch); 
                    return;
                }
                
                if (data.ref_code && !/^[A-Z0-9]{4,20}$/.test(data.ref_code)) {
                    toastr.error(translations[currentLang].refCodeInvalid); 
                    return;
                }
                
                if (!$('input[name="toc"]').is(':checked')) {
                    toastr.error(translations[currentLang].validationAgreeTerms); 
                    return;
                }

                // --- SUBMIT ---
                btn.prop('disabled', true);
                btnLabel.hide();
                btnProgress.show();

                $.ajax({
                    url: '/ajaxs/register.php',
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || translations[currentLang].registerSuccess);
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            toastr.error(response.message || translations[currentLang].registerError);
                            btn.prop('disabled', false);
                            btnLabel.show();
                            btnProgress.hide();
                        }
                    },
                    error: function() {
                        toastr.error(translations[currentLang].connectionError, translations[currentLang].systemError);
                        btn.prop('disabled', false);
                        btnLabel.show();
                        btnProgress.hide();
                    }
                });
            });
       });
    </script>
</body>
</html>