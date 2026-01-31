<?php
$page_title = 'Support';
require_once __DIR__ . '/../config/header.php';
require_once __DIR__ . '/../config/database.php';
require_once '../config/sidebar.php';
?>

<style>
    .support-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .page-header {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(128,128,128,0.05) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 1;
        color: var(--text-color);
    }

    .page-header p {
        font-size: 15px;
        color: var(--text-muted);
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
    }

    .contact-card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        cursor: pointer;

        opacity: 0;
        transform: translateY(20px);
        animation: fadeInFromBottom 0.5s ease-out forwards;
    }

    .contact-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(128,128,128,0.08), transparent);
        transition: left 0.5s;
    }

    .contact-card:hover::before {
        left: 100%;
    }

    .contact-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        border-color: var(--text-muted);
    }

    .contact-card:active {
        transform: translateY(-5px) scale(0.98);
    }

    .contact-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        flex-shrink: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
        background: var(--text-color);
        color: var(--content-bg);
    }

    .contact-icon::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 16px;
        background: inherit;
        filter: blur(10px);
        opacity: 0;
        transition: opacity 0.3s;
        z-index: -1;
    }

    .contact-card:hover .contact-icon {
        transform: scale(1.15) rotate(5deg);
    }

    .contact-card:hover .contact-icon::after {
        opacity: 0.4;
    }

    .contact-info {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .contact-label {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .contact-label .badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 12px;
        background: var(--text-color);
        color: var(--content-bg);
        font-weight: 600;
    }

    .contact-value {
        font-size: 14px;
        color: var(--text-muted);
        word-break: break-word;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .copy-btn {
        background: rgba(128, 128, 128, 0.1);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11px;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.3s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .copy-btn:hover {
        background: rgba(128, 128, 128, 0.2);
        border-color: var(--text-muted);
        transform: scale(1.05);
    }

    .copy-btn.copied {
        background: rgba(128, 128, 128, 0.2);
        border-color: var(--text-color);
        color: var(--text-color);
    }

    .action-hint {
        position: absolute;
        bottom: 10px;
        right: 15px;
        font-size: 11px;
        color: var(--text-muted);
        opacity: 0;
        transition: opacity 0.3s;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .contact-card:hover .action-hint {
        opacity: 1;
    }

    /* Staggered Animation */
    .contact-card:nth-child(1) { animation-delay: 0.1s; }
    .contact-card:nth-child(2) { animation-delay: 0.2s; }
    .contact-card:nth-child(3) { animation-delay: 0.3s; }
    .contact-card:nth-child(4) { animation-delay: 0.4s; }
    .contact-card:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeInFromBottom {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Toast Notification */
    .toast-notification {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: var(--text-color);
        color: var(--content-bg);
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 12px;
        transform: translateX(400px);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 9999;
        font-size: 14px;
        font-weight: 500;
    }

    .toast-notification.show {
        transform: translateX(0);
    }

    .toast-notification i {
        font-size: 20px;
    }

    @media (max-width: 768px) {
        .support-wrapper {
            padding: 15px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 24px;
        }
        .contact-grid {
            grid-template-columns: 1fr;
        }
        .contact-card {
            padding: 20px;
        }
        .contact-icon {
            width: 60px;
            height: 60px;
            font-size: 28px;
        }
        .toast-notification {
            bottom: 20px;
            right: 20px;
            left: 20px;
            transform: translateY(200px);
        }
        .toast-notification.show {
            transform: translateY(0);
        }
    }
</style>

<div class="support-wrapper">
    <div class="page-header">
        <h1>
            <i class="bi bi-headset"></i>
            <?php echo $lang === 'vi' ? 'Trung tâm Hỗ trợ' : 'Support Center'; ?>
        </h1>
        <p><?php echo $lang === 'vi' ? 'Nhấn vào để mở ứng dụng hoặc sao chép thông tin liên hệ.' : 'Click to open app or copy contact information.'; ?></p>
    </div>

    <div class="contact-grid">

        <a href="https://www.facebook.com/61578186428817" target="_blank" class="contact-card">
            <div class="contact-icon"><i class="bi bi-facebook"></i></div>
            <div class="contact-info">
                <div class="contact-label">
                    Facebook
                    <span class="badge"><?php echo $lang === 'vi' ? 'Mở App' : 'Open App'; ?></span>
                </div>
                <div class="contact-value">facebook.com/61578186428817</div>
            </div>
            <div class="action-hint">
                <i class="bi bi-box-arrow-up-right"></i>
                <?php echo $lang === 'vi' ? 'Nhấn để mở' : 'Click to open'; ?>
            </div>
        </a>

        <div class="contact-card" onclick="openLink('https://zalo.me/0353633663', event)">
            <div class="contact-icon"><i class="bi bi-chat-dots-fill"></i></div>
            <div class="contact-info">
                <div class="contact-label">
                    Zalo
                    <span class="badge"><?php echo $lang === 'vi' ? 'Mở App' : 'Open App'; ?></span>
                </div>
                <div class="contact-value">
                    0353633663
                    <button class="copy-btn" onclick="copyToClipboard('0353633663', event)">
                        <i class="bi bi-clipboard"></i>
                        <span class="copy-text"><?php echo $lang === 'vi' ? 'Sao chép' : 'Copy'; ?></span>
                    </button>
                </div>
            </div>
            <div class="action-hint">
                <i class="bi bi-box-arrow-up-right"></i>
                <?php echo $lang === 'vi' ? 'Nhấn để mở' : 'Click to open'; ?>
            </div>
        </div>

        <div class="contact-card" onclick="openWhatsApp('84353633663', event)">
            <div class="contact-icon"><i class="bi bi-whatsapp"></i></div>
            <div class="contact-info">
                <div class="contact-label">
                    WhatsApp
                    <span class="badge"><?php echo $lang === 'vi' ? 'Mở App' : 'Open App'; ?></span>
                </div>
                <div class="contact-value">
                    +84 35 363 3663
                    <button class="copy-btn" onclick="copyToClipboard('+84353633663', event)">
                        <i class="bi bi-clipboard"></i>
                        <span class="copy-text"><?php echo $lang === 'vi' ? 'Sao chép' : 'Copy'; ?></span>
                    </button>
                </div>
            </div>
            <div class="action-hint">
                <i class="bi bi-box-arrow-up-right"></i>
                <?php echo $lang === 'vi' ? 'Nhấn để mở' : 'Click to open'; ?>
            </div>
        </div>

        <a href="https://t.me/buicongxd11" target="_blank" class="contact-card">
            <div class="contact-icon"><i class="bi bi-telegram"></i></div>
            <div class="contact-info">
                <div class="contact-label">
                    Telegram
                    <span class="badge"><?php echo $lang === 'vi' ? 'Mở App' : 'Open App'; ?></span>
                </div>
                <div class="contact-value">
                    @buicongxd11
                    <button class="copy-btn" onclick="copyToClipboard('@buicongxd11', event)">
                        <i class="bi bi-clipboard"></i>
                        <span class="copy-text"><?php echo $lang === 'vi' ? 'Sao chép' : 'Copy'; ?></span>
                    </button>
                </div>
            </div>
            <div class="action-hint">
                <i class="bi bi-box-arrow-up-right"></i>
                <?php echo $lang === 'vi' ? 'Nhấn để mở' : 'Click to open'; ?>
            </div>
        </a>

        <a href="https://www.youtube.com/channel/UCgZ_H4Jc0voj6-QsENauJPQ" target="_blank" class="contact-card">
            <div class="contact-icon"><i class="bi bi-youtube"></i></div>
            <div class="contact-info">
                <div class="contact-label">
                    Youtube Channel
                    <span class="badge"><?php echo $lang === 'vi' ? 'Mở App' : 'Open App'; ?></span>
                </div>
                <div class="contact-value"><?php echo $lang === 'vi' ? 'Xem kênh của chúng tôi' : 'Watch our channel'; ?></div>
            </div>
            <div class="action-hint">
                <i class="bi bi-box-arrow-up-right"></i>
                <?php echo $lang === 'vi' ? 'Nhấn để mở' : 'Click to open'; ?>
            </div>
        </a>

    </div>
</div>

<div class="toast-notification" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toast-message"></span>
</div>

<script>
function openLink(url, event) {
    if (event && event.target.closest('.copy-btn')) {
        return;
    }
    window.open(url, '_blank');
}

function openWhatsApp(phoneNumber, event) {
    if (event && event.target.closest('.copy-btn')) {
        return;
    }
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
        btn.innerHTML = '<i class="bi bi-check-lg"></i> <span><?php echo $lang === 'vi' ? 'Đã sao chép!' : 'Copied!'; ?></span>';

        showToast('<?php echo $lang === 'vi' ? 'Đã sao chép vào clipboard!' : 'Copied to clipboard!'; ?>');

        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = originalHTML;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('<?php echo $lang === 'vi' ? 'Không thể sao chép!' : 'Failed to copy!'; ?>', true);
    });
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');

    toastMessage.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
