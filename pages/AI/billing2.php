<?php
$page_title = 'Mua Credits 2 - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

// Lấy thông tin User
$user_identity = $_SESSION['Users'];

// [UPDATE] Lấy đủ 3 cột
$stmt = $mysqli->prepare("SELECT sodu, credits, credits2 FROM Users WHERE taikhoan = ? OR google_id = ?");
$stmt->bind_param("ss", $user_identity, $user_identity);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$usd_balance = $user['sodu'] ?? 0.00;
$credit_balance = $user['credits'] ?? 0;
$credit2_balance = $user['credits2'] ?? 0; 
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<style>
    :root {
        --bg-main: #0b0b0b;
        --bg-card: #1c1c1c;
        --border-color: #333;
        --text-primary: #fff;
        --text-secondary: #aaa;
        --accent-purple: #a855f7;
        --accent-green: #4ade80; /* Màu Credits 1 */
        --accent-blue: #3b82f6;  /* Màu Credits 2 */
    }
    
    body {
        background: var(--bg-main);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
    }
    
    .billing-wrap {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* ========== STATS GRID ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 Cột */
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 20px;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card h3 {
        margin: 0 0 8px 0;
        font-size: 11px;
        color: #888;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-card .value {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
    }
    
    .stat-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 32px;
        opacity: 0.08;
    }

    /* ========== PRODUCT CARD ========== */
    .product-card {
        position: relative;
        padding: 40px;
        text-align: center;
        background: transparent;
        border-radius: 24px;
        overflow: hidden;
        z-index: 0;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    
    /* Gradient Border - Chuyển sang tông xanh dương cho Credits 2 */
    .product-card::before {
        content: '';
        position: absolute;
        inset: -150%;
        background: conic-gradient(
            from 0deg,
            #1a1a2e 0deg,
            #3b82f6 45deg,    /* Blue */
            #60a5fa 90deg,
            #93c5fd 135deg,
            #2563eb 180deg,
            #3b82f6 225deg,
            #60a5fa 270deg,
            #1d4ed8 315deg,
            #1a1a2e 360deg
        );
        animation: spin 8s linear infinite;
        z-index: -2;
    }
    
    .product-card::after {
        content: '';
        position: absolute;
        inset: 2px;
        background: #0f0f0f;
        border-radius: 22px;
        z-index: -1;
    }
    
    .product-card > * {
        position: relative;
        z-index: 2;
    }
    
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
    
    .card-badge {
        background: linear-gradient(135deg, #3b82f6, #2563eb); /* Xanh dương */
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        position: absolute;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        letter-spacing: 0.5px;
        z-index: 9999;
    }

    .main-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
        margin-top: 16px;
    }
    
    .sub-desc {
        font-size: 13px;
        color: #888;
        margin-bottom: 30px;
    }

    /* ========== CALCULATOR BOX ========== */
    .calc-box {
        background: #131313;
        border: 1px solid #333;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    
    .control-row {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        margin-bottom: 20px;
    }
    
    .btn-calc {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 1px solid #555;
        background: transparent;
        color: #fff;
        font-size: 22px;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        user-select: none;
    }
    
    .btn-calc:hover:not(:disabled) {
        background: #333;
        border-color: var(--accent-blue);
        color: var(--accent-blue);
        transform: scale(1.05);
    }
    
    .btn-calc:active {
        transform: scale(0.95);
    }
    
    .btn-calc:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .qty-display {
        font-size: 56px;
        font-weight: 800;
        color: #fff;
        min-width: 120px;
        text-align: center;
    }
    
    .qty-label {
        font-size: 12px;
        color: #666;
        display: block;
        margin-top: -8px;
        text-align: center;
    }

    /* ========== USAGE ESTIMATION ========== */
    .usage-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        text-align: left;
        margin-top: 20px;
        border-top: 1px solid #333;
        padding-top: 20px;
    }
    
    .usage-item {
        font-size: 13px;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .usage-item i {
        color: var(--accent-blue);
        width: 18px;
        text-align: center;
        font-size: 16px;
    }
    
    .usage-val {
        font-weight: 700;
        color: #fff;
    }
    
    .usage-note {
        color: #ff9f9f;
        grid-column: 1 / -1;
        font-size: 11px;
        margin-top: 8px;
        font-style: italic;
    }

    /* ========== PRICE DISPLAY ========== */
    .result-row {
        display: flex;
        justify-content: space-between;
        padding-top: 20px;
        border-top: 1px solid #333;
        margin-top: 12px;
    }
    
    .res-item {
        text-align: left;
    }
    
    .res-item.right {
        text-align: right;
    }
    
    .res-label {
        font-size: 12px;
        color: #888;
        display: block;
        margin-bottom: 6px;
    }
    
    .res-value {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
    }
    
    /* Màu riêng cho Credits 2 */
    .res-value.credits2 {
        color: var(--accent-blue); 
    }
    
    .res-value.price {
        color: #facc15;
    }
    
    .bonus-tag {
        background: #22c55e;
        color: #000;
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 4px;
        vertical-align: middle;
        margin-left: 8px;
        font-weight: 700;
        display: none;
    }

    /* ========== FEATURES LIST ========== */
    .features-list {
        text-align: left;
        margin-top: 30px;
        padding: 24px;
        background: #131313;
        border-radius: 16px;
        border: 1px solid #333;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 14px;
        color: #ddd;
    }
    
    .feature-item:last-child {
        margin-bottom: 0;
    }
    
    .feature-item i {
        color: #fff;
        background: #000;
        border-radius: 50%;
        padding: 4px;
        font-size: 10px;
        border: 1px solid #fff;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* ========== CHECKOUT BUTTON ========== */
    .btn-checkout {
        width: 100%;
        padding: 18px;
        border-radius: 12px;
        border: none;
        /* Gradient Xanh */
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        margin-top: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-checkout:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(59, 130, 246, 0.6);
    }
    
    .btn-checkout:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .checkout-hint {
        margin-top: 16px;
        font-size: 11px;
        color: #666;
        font-style: italic;
    }

    /* ========== MODAL ========== */
    .custom-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 99999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(8px);
    }
    
    .modal-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 32px;
        border-radius: 20px;
        width: 90%;
        max-width: 420px;
        text-align: center;
        animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    @keyframes popIn {
        from {
            transform: scale(0.8);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    .modal-icon {
        font-size: 56px;
        margin-bottom: 16px;
        display: block;
    }
    
    .modal-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 12px;
    }
    
    .modal-text {
        font-size: 14px;
        color: #bbb;
        margin-bottom: 28px;
        line-height: 1.6;
    }
    
    .modal-text b {
        color: #fff;
    }
    
    .modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 24px;
    }
    
    .btn-confirm {
        background: var(--accent-blue);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    
    .btn-confirm:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
    }
    
    .btn-cancel {
        background: #333;
        color: white;
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    
    .btn-cancel:hover {
        background: #444;
    }

    .btn-success-ok {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 30px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: 0.2s;
    }
    
    .btn-success-ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr; /* Mobile về lại 1 cột */
        }
        
        .product-card {
            padding: 30px 20px;
        }
        
        .calc-box {
            padding: 20px;
        }
        
        .control-row {
            gap: 20px;
        }
        
        .qty-display {
            font-size: 48px;
        }
        
        .usage-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="billing-wrap">
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Số dư ví (USD)</h3>
            <div class="value">$<span id="currentBalance"><?php echo number_format($usd_balance, 2); ?></span></div>
            <i class="bi bi-wallet2 stat-icon"></i>
        </div>
        <div class="stat-card">
            <h3>Credits (server 1)</h3>
            <div class="value" style="color: #4ade80;"><?php echo number_format($credit_balance); ?></div>
            <i class="bi bi-lightning-charge-fill stat-icon" style="color: #4ade80;"></i>
        </div>
        <div class="stat-card" style="border-color: #3b82f6;">
            <h3>Credits (server 2)</h3>
            <div class="value" style="color: #3b82f6;"><?php echo number_format($credit2_balance); ?></div>
            <i class="bi bi-stars stat-icon" style="color: #3b82f6;"></i>
        </div>
    </div>

    <div class="product-card">
        <span class="card-badge">GÓI TÍN DỤNG CAO CẤP SERVER 2</span>
        <div class="main-title">Mua Credits (server AI 2)</div>
        <div class="sub-desc">Credits (server 2) dùng riêng, không liên quan đến Credits (server 1).</div>
        <div class="sub-desc">Sử dụng cho Text-to-Speech</div>

        <div class="calc-box">
            <div class="control-row">
                <button class="btn-calc" onclick="updateQty(-1)">
                    <i class="bi bi-dash"></i>
                </button>
                <div>
                    <div class="qty-display" id="qty">1</div>
                    <span class="qty-label">Gói (Max 21)</span>
                </div>
                <button class="btn-calc" id="btnPlus" onclick="updateQty(1, this)">
                    <i class="bi bi-plus"></i>
                </button>
            </div>

            <div class="result-row">
                <div class="res-item">
                    <span class="res-label">Tổng Credits 2 nhận:</span>
                    <div style="display:flex; align-items:center;">
                        <span class="res-value credits2" id="totalCredits">1,000,000</span>
                        <span class="bonus-tag" id="bonusTag">+0% Bonus</span>
                    </div>
                </div>
                <div class="res-item right">
                    <span class="res-label">Thành tiền (USD):</span>
                    <span class="res-value price">$<span id="totalPrice">6.00</span></span>
                </div>
            </div>

            <div class="usage-grid">
                <div class="usage-item">
                    <i class="bi bi-mic"></i> ≈ <span class="usage-val" id="us-tts">1,700</span> phút TTS
                </div>
                <div class="usage-note">
                    * Số liệu ước tính khi dùng Credits 2
                </div>
            </div>
        </div>

        <div class="features-list">
                        <div class="feature-item">
                <i class="bi bi-check-lg"></i> Nhân bản giọng <b>không giới hạn</b>
            </div>
            <div class="feature-item">
                <i class="bi bi-check-lg"></i> Chạy nhiệm vụ cùng lúc
            </div>
            <div class="feature-item">
                <i class="bi bi-check-lg"></i> Truy cập API
            </div>
            <div class="feature-item">
                <i class="bi bi-check-lg"></i> Không xếp hàng giờ cao điểm
            </div>
        </div>

        <button class="btn-checkout" onclick="openConfirmModal()">
            <i class="bi bi-wallet-fill"></i>
            <span>THANH TOÁN MUA CREDITS SERVER 2</span>
        </button>
        
        <div class="checkout-hint">
            * Nhấn dấu "+" càng nhiều thì càng rẻ (Bonus cộng thẳng vào Credits 2)
        </div>
    </div>

</div>

<div id="confirmModal" class="custom-modal">
    <div class="modal-box">
        <div class="modal-icon">🤔</div>
        <div class="modal-title">Xác nhận thanh toán? (server 2)</div>
        <div class="modal-text">
            Bạn sẽ dùng <b>$<span id="modalPrice">6.00</span></b> từ ví để mua:<br>
            <b style="font-size:18px; color:#3b82f6;"><span id="modalCredits">1,000,000</span> Credits 2</b><br><br>
            Số dư tiền sau mua: <b>$<span id="modalBalance">---</span></b>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="$('#confirmModal').fadeOut()">Hủy</button>
            <button class="btn-confirm" onclick="processPayment()">Xác nhận</button>
        </div>
    </div>
</div>

<div id="successModal" class="custom-modal">
    <div class="modal-box">
        <div class="modal-icon">🎉</div>
        <div class="modal-title" style="color:#3b82f6">Thành công!</div>
        <div class="modal-text" id="successMsg">
            Đã cộng Credits 2 vào tài khoản của bạn.
        </div>
        <button class="btn-success-ok" onclick="location.reload()">TUYỆT VỜI</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // ========== PRICING CONFIG ==========
    const BASE_PRICE = 6;           
    const PRICE_INCREMENT = 5;      
    const CREDITS_PER_UNIT = 1000000;
    const MAX_QTY = 21;

    // Base usage cho 1M credits
    const BASE_USAGE = {
        tts: 1700,
        songs: 834,
        video: 667,
        proxy: 800,
        img: 2000
    };

    let currentQty = 1;
    const userBalance = <?php echo $usd_balance; ?>;

    function calculatePrice(qty) {
        return BASE_PRICE + (qty - 1) * PRICE_INCREMENT;
    }

    function updateQty(change, btnElement) {
        let newQty = currentQty + change;
        
        if (newQty < 1) return;
        if (newQty > MAX_QTY) {
            alert("Gói tối đa cho phép là 21!");
            return;
        }
        
        if (change > 0) {
            if(btnElement) triggerBtnConfetti(btnElement);
            else triggerBtnConfetti(document.getElementById('btnPlus'));
        }

        currentQty = newQty;
        render();
    }

    function render() {
        let totalPrice = calculatePrice(currentQty);
        let bonusPercent = (currentQty - 1) * 5;
        if (bonusPercent < 0) bonusPercent = 0;

        let baseCredits = currentQty * CREDITS_PER_UNIT;
        let totalCredits = baseCredits * (1 + bonusPercent/100);

        // Update DOM
        $('#qty').text(currentQty);
        $('#totalPrice').text(totalPrice.toFixed(2));
        $('#totalCredits').text(Math.floor(totalCredits).toLocaleString('en-US'));
        
        if(bonusPercent > 0) {
            $('#bonusTag').text(`+${bonusPercent}% Bonus`).show();
        } else {
            $('#bonusTag').hide();
        }

        // Update usage estimation
        $('#us-tts').text((currentQty * BASE_USAGE.tts).toLocaleString('en-US'));
        $('#us-songs').text((currentQty * BASE_USAGE.songs).toLocaleString('en-US'));
        $('#us-video').text((currentQty * BASE_USAGE.video).toLocaleString('en-US'));
        $('#us-proxy').text((currentQty * BASE_USAGE.proxy).toLocaleString('en-US'));
        $('#us-img').text((currentQty * BASE_USAGE.img).toLocaleString('en-US'));

        $('#btnPlus').prop('disabled', currentQty >= MAX_QTY);
    }

    function triggerBtnConfetti(element) {
        if (!element) return;
        const rect = element.getBoundingClientRect();
        const x = (rect.left + rect.width / 2) / window.innerWidth;
        const y = (rect.top + rect.height / 2) / window.innerHeight;

        confetti({
            particleCount: 25,
            spread: 40,
            startVelocity: 20,
            origin: { x: x, y: y },
            // Đổi màu confetti sang tone xanh/vàng
            colors: ['#3b82f6', '#ffffff', '#facc15'],
            disableForReducedMotion: true,
            scalar: 0.7,
            zIndex: 99999
        });
    }

    function triggerSuccessConfetti() {
        var duration = 2 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100000 };

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    function openConfirmModal() {
        let totalPrice = calculatePrice(currentQty);
        let bonusPercent = (currentQty - 1) * 5;
        let totalCredits = (currentQty * CREDITS_PER_UNIT) * (1 + bonusPercent/100);

        if (userBalance < totalPrice) {
            alert(`Số dư không đủ! Bạn cần $${totalPrice.toFixed(2)} nhưng chỉ có $${userBalance.toFixed(2)}`);
            return;
        }

        $('#modalPrice').text(totalPrice.toFixed(2));
        $('#modalCredits').text(Math.floor(totalCredits).toLocaleString('en-US'));
        $('#modalBalance').text((userBalance - totalPrice).toFixed(2));
        
        $('#confirmModal').css('display', 'flex').hide().fadeIn();
    }

    function processPayment() {
        $('.btn-confirm').prop('disabled', true).text('Đang xử lý...');
        
        $.post('../../ajaxs/billing2.php', {
            action: 'buy_credits_v2', // <--- ĐỔI TÊN ACTION ĐỂ KHÔNG BỊ TRÙNG
            quantity: currentQty
        }, function(res) {
            if(res.status === 'success') {
                $('#confirmModal').fadeOut();
                $('#successMsg').html(`<b>${res.message}</b>`);
                $('#successModal').css('display', 'flex').hide().fadeIn();
                triggerSuccessConfetti();
            } else {
                alert(res.message);
                $('.btn-confirm').prop('disabled', false).text('Xác nhận');
                $('#confirmModal').fadeOut();
            }
        }, 'json').fail(function() {
            alert('Lỗi kết nối server!');
            $('.btn-confirm').prop('disabled', false).text('Xác nhận');
        });
    }
    
    // Init
    render();
</script>

<?php require_once '../../config/footer.php'; ?>