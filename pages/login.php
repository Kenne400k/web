<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <title>Đăng Nhập Tài Khoản</title>
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

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .image-section .overlay-text {
            position: relative;
            z-index: 2;
            color: var(--text-color);
            text-shadow: 0 2px 10px var(--shadow);
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
                                <a href="#" data-lang="en">EN</a>
                                <span class="mx-1" style="color: var(--text-secondary)">|</span>
                                <a href="#" data-lang="vi" class="active">VI</a>
                            </div>
                            <button id="theme-toggle" class="btn btn-icon w-40px h-40px">
                                <i class="bi bi-sun-fill fs-3 theme-icon-light d-none"></i>
                                <i class="bi bi-moon-stars-fill fs-3 theme-icon-dark"></i>
                            </button>
                        </div>

                        <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form">
                            <div class="text-center mb-11">
                                <h1 class="fw-bolder mb-3" data-translate="loginTitle">Đăng Nhập</h1>
                                <div class="text-gray-500 fw-semibold fs-6" data-translate="welcomeBack">Chào mừng trở lại!</div>
                            </div>

                            <div class="d-flex justify-content-center mb-10">
                                <div id="g_id_onload"
                                     data-client_id="189817716633-jt198qfpjmtno4hjmhm4fd4aqo5eo452.apps.googleusercontent.com"
                                     data-context="signin"
                                     data-ux_mode="popup"
                                     data-callback="handleCredentialResponse"
                                     data-auto_prompt="false">
                                </div>
                                <div class="g_id_signin"
                                     data-type="standard"
                                     data-shape="rectangular"
                                     data-theme="outline"
                                     data-text="signin_with"
                                     data-size="large"
                                     data-logo_alignment="left">
                                </div>
                            </div>

                            <div class="separator separator-content my-10">
                                <span class="w-125px text-gray-500 fw-semibold fs-7" data-translate="orWithAccount">Hoặc với tài khoản</span>
                            </div>
                            
                            <div id="status" class="mb-5 text-center"></div>

                            <div class="fv-row mb-8">
                                <input type="text" placeholder="Tài khoản" id="taikhoan" name="taikhoan" autocomplete="off" class="form-control" data-translate-placeholder="accountPlaceholder" />
                            </div>

                            <div class="fv-row mb-3">
                                <input placeholder="Mật khẩu" id="matkhau" name="password" type="password" autocomplete="off" class="form-control" data-translate-placeholder="passwordPlaceholder" />
                            </div>

                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <div></div>
                                <a href="#" class="link-primary" data-translate="forgotPassword">Quên Mật Khẩu?</a>
                            </div>

                            <div class="d-grid mb-10">
                                <button type="submit" id="login_btn" class="btn btn-primary">
                                    <span class="indicator-label" data-translate="loginButton">Đăng Nhập</span>
                                    <span class="indicator-progress" style="display:none;" data-translate="processing">Đang xử lý...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>

                            <div class="text-gray-500 text-center fw-semibold fs-6">
                                <span data-translate="noAccount">Chưa có tài khoản?</span>
                                <a href="/auth/register" class="link-primary fw-bold" data-translate="registerLink">Đăng Ký</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="d-none d-lg-flex flex-lg-row-fluid w-lg-50 order-1 order-lg-2 align-items-center justify-content-center image-section">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <img src="/assets/img/about3.svg" alt="Login Illustration" class="img-fluid mb-10" style="max-width: 400px; max-height: 60vh;">
                    <h1 class="overlay-text fs-2qx fw-bolder text-center mb-7">
                        KingCongStudio
                    </h1>
                    <div class="overlay-text fs-base text-center" data-translate="platformSlogan">
                        Nền tảng uy tín và chất lượng <br/> được hàng ngàn người tin dùng.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ jQuery FIRST -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- ✅ Toastr AFTER jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
    
    <!-- ✅ ONLY load plugins.bundle.js - SKIP scripts.bundle.js -->
    <script src="/assets/plugins/global/plugins.bundle.js"></script>
    
    <script>
    // ✅ Toastr Configuration
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

    // --- Google Sign-In Callback ---
    function handleCredentialResponse(response) {
        const id_token = response.credential;
        const currentLang = localStorage.getItem('language') || 'vi';
        const message = (currentLang === 'vi') 
            ? 'Đang xác thực với Google...' 
            : 'Authenticating with Google...';
        
        $('#status').html(`<div class="alert alert-info py-2">${message}</div>`);

        $.ajax({
            url: '/ajaxs/google-auth.php',
            type: 'POST',
            dataType: 'json',
            data: { id_token: id_token },
            success: function(res) {
                if (res.status === 'success') {
                    toastr.success(res.message || "Xác thực thành công!");
                    setTimeout(() => window.location.href = res.redirect_url, 1500);
                } else {
                    toastr.error(res.message || 'Lỗi không xác định.');
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = (currentLang === 'vi')
                    ? 'Lỗi kết nối tới server khi xác thực Google.'
                    : 'Connection error when authenticating with Google.';
                toastr.error(errorMsg);
            }
        });
    }

    // --- Standard Login & Theme Toggle & Language ---
    $(document).ready(function() {
        // --- LANGUAGE TRANSLATIONS ---
        const translations = {
            vi: {
                pageTitle: "Đăng Nhập Tài Khoản",
                loginTitle: "Đăng Nhập",
                welcomeBack: "Chào mừng trở lại!",
                orWithAccount: "Hoặc với tài khoản",
                accountPlaceholder: "Tài khoản",
                passwordPlaceholder: "Mật khẩu",
                forgotPassword: "Quên Mật Khẩu?",
                loginButton: "Đăng Nhập",
                processing: "Đang xử lý...",
                noAccount: "Chưa có tài khoản?",
                registerLink: "Đăng Ký",
                platformSlogan: "Nền tảng uy tín và chất lượng <br/> được hàng ngàn người tin dùng.",
                googleAuth: "Đang xác thực với Google...",
                googleAuthError: "Lỗi kết nối tới server khi xác thực Google.",
                validationError: "Vui lòng điền đầy đủ thông tin đăng nhập.",
                loginSuccess: "Đăng nhập thành công! Đang chuyển hướng...",
                loginError: "Có lỗi xảy ra, vui lòng thử lại.",
                connectionError: "Lỗi kết nối đến máy chủ. Vui lòng thử lại sau."
            },
            en: {
                pageTitle: "Account Login",
                loginTitle: "Sign In",
                welcomeBack: "Welcome back!",
                orWithAccount: "Or with an account",
                accountPlaceholder: "Username",
                passwordPlaceholder: "Password",
                forgotPassword: "Forgot Password?",
                loginButton: "Sign In",
                processing: "Processing...",
                noAccount: "Not a member yet?",
                registerLink: "Sign Up",
                platformSlogan: "A reliable and high-quality platform <br/> trusted by thousands of users.",
                googleAuth: "Authenticating with Google...",
                googleAuthError: "Connection error when authenticating with Google.",
                validationError: "Please fill in all login information.",
                loginSuccess: "Login successful! Redirecting...",
                loginError: "An error occurred, please try again.",
                connectionError: "Server connection error. Please try again later."
            }
        };

        let currentLang = 'vi';

        function setLanguage(lang) {
            if (!translations[lang]) return;
            currentLang = lang;
            localStorage.setItem('language', lang);
            document.documentElement.lang = lang;

            const langStrings = translations[lang];

            // Update page title
            document.title = langStrings.pageTitle;

            // Update elements with data-translate attribute
            $('[data-translate]').each(function() {
                const key = $(this).data('translate');
                if (langStrings[key]) {
                    $(this).html(langStrings[key]);
                }
            });

            // Update elements with data-translate-placeholder attribute
            $('[data-translate-placeholder]').each(function() {
                const key = $(this).data('translate-placeholder');
                if (langStrings[key]) {
                    $(this).attr('placeholder', langStrings[key]);
                }
            });
            
            // Update active class for language switcher
            $('.lang-switcher a').removeClass('active');
            $(`.lang-switcher a[data-lang="${lang}"]`).addClass('active');
        }

        // --- LANGUAGE INITIALIZATION ---
        const savedLang = localStorage.getItem('language') || 'vi';
        setLanguage(savedLang);

        $('.lang-switcher a').on('click', function(e) {
            e.preventDefault();
            const lang = $(this).data('lang');
            setLanguage(lang);
        });

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

        // --- LOGIN FORM HANDLER ---
        $('#kt_sign_in_form').on('submit', function(e) {
            e.preventDefault();

            const btn = $('#login_btn');
            const btnLabel = btn.find('.indicator-label');
            const btnProgress = btn.find('.indicator-progress');

            // Lấy dữ liệu form
            const data = {
                taikhoan: $('#taikhoan').val().trim(),
                matkhau: $('#matkhau').val()
            };

            // Validation
            if (!data.taikhoan || !data.matkhau) {
                toastr.error(translations[currentLang].validationError);
                return;
            }

            // Submit
            btn.prop('disabled', true);
            btnLabel.hide();
            btnProgress.show();

            $.ajax({
                url: '/ajaxs/login.php',
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || translations[currentLang].loginSuccess);
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else {
                        toastr.error(response.message || translations[currentLang].loginError);
                        btn.prop('disabled', false);
                        btnLabel.show();
                        btnProgress.hide();
                    }
                },
                error: function() {
                    toastr.error(translations[currentLang].connectionError, "System Error");
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