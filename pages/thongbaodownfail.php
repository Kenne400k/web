<?php
// Giả lập biến $lang nếu không có config/database.php (để chạy độc lập được)
// Bạn có thể bỏ comment dòng require bên dưới nếu cần biến từ hệ thống
// require_once __DIR__ . '/../config/database.php'; 
if (!isset($lang)) {
    $lang = 'vi'; // Mặc định là tiếng Việt
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'vi' ? 'Cập nhật thất bại' : 'Update Failed'; ?></title>
    <!-- Import Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Google -->
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f3f4f6;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --content-bg: #ffffff;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
        }

        .main-container {
            width: 100%;
            max-width: 1000px;
            padding: 20px;
        }

        .error-card {
            background: var(--content-bg);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        /* Phần thông báo lỗi phía trên */
        .error-header {
            background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
            padding: 40px 20px;
            border-bottom: 1px dashed #fecaca;
            position: relative;
        }

        .error-icon-wrapper {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: #ef4444;
            font-size: 40px;
            position: relative;
            animation: bounce 2s infinite;
        }

        .error-icon-wrapper::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid #ef4444;
            opacity: 0;
            animation: ripple 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }

        @keyframes ripple {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #b91c1c;
            margin-bottom: 10px;
        }

        .error-desc {
            font-size: 15px;
            color: #7f1d1d;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Phần Support Grid (Code cũ của bạn đã được tinh chỉnh CSS) */
        .support-body {
            padding: 40px;
        }

        .support-label {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 25px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            text-align: left;
        }
        
        .contact-card {
            background: #f9fafb;
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
            overflow: hidden;
        }

        .contact-card:hover {
            background: white;
            border-color: var(--hover-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transform: translateY(-5px);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        /* Custom Colors & Hover states */
        .card-facebook { --hover-color: #1877f2; }
        .card-facebook .contact-icon { background: linear-gradient(135deg, #1877f2, #3b5998); }
        
        .card-zalo { --hover-color: #0068ff; }
        .card-zalo .contact-icon { background: linear-gradient(135deg, #0068ff, #00a4ff); }
        
        .card-whatsapp { --hover-color: #25d366; }
        .card-whatsapp .contact-icon { background: linear-gradient(135deg, #25d366, #128c7e); }
        
        .card-telegram { --hover-color: #0088cc; }
        .card-telegram .contact-icon { background: linear-gradient(135deg, #0088cc, #2ca5e0); }
        
        .card-youtube { --hover-color: #ff0000; }
        .card-youtube .contact-icon { background: linear-gradient(135deg, #ff0000, #c4302b); }

        .contact-info {
            flex: 1;
            min-width: 0; /* Fix flex text overflow */
        }

        .contact-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 4px;
        }

        .contact-detail {
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Copy Button Styles */
        .copy-btn {
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: auto;
        }

        .copy-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .copy-btn.copied {
            background: #dcfce7;
            border-color: #86efac;
            color: #15803d;
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1f2937;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 9999;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
        }

        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .error-card { border-radius: 0; height: 100vh; display: flex; flex-direction: column; }
            .main-container { padding: 0; max-width: 100%; }
            .support-body { flex: 1; padding: 20px; }
            .contact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="error-card">
        <!-- Error / Warning Header -->
        <div class="error-header">
            <div class="error-icon-wrapper">
                <i class="bi bi-cloud-slash-fill"></i>
            </div>
            <h1 class="error-title">
                <?php echo $lang === 'vi' ? 'Quá Trình Tải Xuống Bị Gián Đoạn!' : 'Download Interrupted!'; ?>
            </h1>
            <p class="error-desc">
                <?php echo $lang === 'vi' 
                    ? 'Rất tiếc, tool không thể tự động tải xuống hoặc cập nhật file cần thiết. Điều này có thể do kết nối mạng hoặc server đang bảo trì.' 
                    : 'We are sorry, the tool could not automatically download or update the required files. This may be due to network issues or server maintenance.'; ?>
            </p>
        </div>

        <!-- Support Body -->
        <div class="support-body">
            <div class="support-label">
                <?php echo $lang === 'vi' ? 'Liên hệ hỗ trợ thủ công (24/7)' : 'Contact for manual support (24/7)'; ?>
            </div>

            <div class="contact-grid">
                <!-- Facebook -->
                <a href="https://www.facebook.com/61578186428817" target="_blank" class="contact-card card-facebook">
                    <div class="contact-icon"><i class="bi bi-facebook"></i></div>
                    <div class="contact-info">
                        <div class="contact-name">Facebook Page</div>
                        <div class="contact-detail">Admin Support</div>
                    </div>
                    <i class="bi bi-box-arrow-up-right text-muted"></i>
                </a>

                <!-- Zalo -->
                <div class="contact-card card-zalo" onclick="openLink('https://zalo.me/0353633663', event)">
                    <div class="contact-icon"><i class="bi bi-chat-dots-fill"></i></div>
                    <div class="contact-info">
                        <div class="contact-name">Zalo Support</div>
                        <div class="contact-detail">
                            0353.633.663
                        </div>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('0353633663', event)">
                        <?php echo $lang === 'vi' ? 'Copy' : 'Copy'; ?>
                    </button>
                </div>

                <!-- WhatsApp -->
                <div class="contact-card card-whatsapp" onclick="openWhatsApp('84353633663', event)">
                    <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
                    <div class="contact-info">
                        <div class="contact-name">WhatsApp</div>
                        <div class="contact-detail">+84 35 363 3663</div>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('+84353633663', event)">
                        <?php echo $lang === 'vi' ? 'Copy' : 'Copy'; ?>
                    </button>
                </div>

                <!-- Telegram -->
                <a href="https://t.me/buicongxd11" target="_blank" class="contact-card card-telegram">
                    <div class="contact-icon"><i class="bi bi-telegram"></i></div>
                    <div class="contact-info">
                        <div class="contact-name">Telegram</div>
                        <div class="contact-detail">@buicongxd11</div>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard('@buicongxd11', event)">
                        <?php echo $lang === 'vi' ? 'Copy' : 'Copy'; ?>
                    </button>
                </a>

                <!-- Youtube -->
                <a href="https://www.youtube.com/channel/UCgZ_H4Jc0voj6-QsENauJPQ" target="_blank" class="contact-card card-youtube">
                    <div class="contact-icon"><i class="bi bi-youtube"></i></div>
                    <div class="contact-info">
                        <div class="contact-name">Youtube Channel</div>
                        <div class="contact-detail"><?php echo $lang === 'vi' ? 'Xem hướng dẫn fix' : 'Watch tutorials'; ?></div>
                    </div>
                    <i class="bi bi-play-btn text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-notification" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toast-message"></span>
</div>

<script>
// Giữ nguyên logic JS của bạn vì nó đã hoạt động tốt
function openLink(url, event) {
    if (event && event.target.closest('.copy-btn')) return;
    window.open(url, '_blank');
}

function openWhatsApp(phoneNumber, event) {
    if (event && event.target.closest('.copy-btn')) return;
    const cleanNumber = phoneNumber.replace(/\D/g, '');
    window.open(`https://wa.me/${cleanNumber}`, '_blank');
}

function copyToClipboard(text, event) {
    event.stopPropagation();
    event.preventDefault();
    
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('.copy-btn');
        if (!btn) return;
        
        const originalHTML = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<i class="bi bi-check"></i>';
        
        showToast('<?php echo $lang === 'vi' ? 'Đã sao chép vào clipboard!' : 'Copied to clipboard!'; ?>');
        
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = originalHTML;
        }, 2000);
    }).catch(err => {
        showToast('Error copy!', true);
    });
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    if (isError) {
        toast.style.background = '#ef4444';
        toast.querySelector('i').className = 'bi bi-x-circle-fill';
    } else {
        toast.style.background = '#1f2937';
        toast.querySelector('i').className = 'bi bi-check-circle-fill';
    }
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>

</body>
</html>