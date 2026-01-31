<?php
$page_title = $lang === 'vi' ? 'Nạp Tiền Quốc Tế' : 'International Deposit';
require_once '../config/header.php';

// --- CẤU HÌNH FPAYAZ CỦA BẠN ---
define('FPAYAZ_KEY', '90b64799b18aba1144da3a4eaa2eeefb');
define('FPAYAZ_REDIRECT_URL', 'https://kingcongstudio.com/international_deposit');
define('FPAYAZ_WEBHOOK_URL', 'https://kingcongstudio.com/auth/nganhang/webhookquocte.php');
// ------------------------------------

// Lấy thông tin user hiện tại
$user_id = $user['id'];
$username = $user['taikhoan'];
$current_balance = $user['sodu'] ?? 0;

// Lấy tổng tiền nạp (tạm thời, sẽ xử lý backend sau)
$stmt = $mysqli->prepare("SELECT COALESCE(SUM(amount), 0) as total_deposit FROM Transactions WHERE user_id = ? AND type = 'deposit'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();
$total_deposit = $userData['total_deposit'] ?? 0;

// Define membership ranks
$ranks = [
    'NEWBIE' => ['min' => 0, 'bonus' => 0, 'color' => '#6b7280'],
    'JUNIOR' => ['min' => 100, 'bonus' => 2, 'color' => '#3b82f6'],
    'ELITE' => ['min' => 500, 'bonus' => 3, 'color' => '#8b5cf6'],
    'FREQUENT' => ['min' => 1000, 'bonus' => 4, 'color' => '#f59e0b'],
    'VIP' => ['min' => 1500, 'bonus' => 5, 'color' => '#ef4444']
];

// Determine current rank
$current_rank = $user['rank'] ?? 'NEWBIE';


// Đa ngôn ngữ
$texts = [
    'vi' => [
        'title' => 'Nạp Tiền Quốc Tế (USD)',
        'subtitle' => 'Thanh toán an toàn qua cổng thanh toán tiền điện tử',
        'current_balance' => 'Số dư hiện tại',
        'deposit_amount' => 'Số tiền muốn nạp',
        'enter_amount_usd' => 'Nhập số tiền (USD)',
        'bonus' => 'Khuyến mãi',
        'total_received' => 'Tổng nhận được',
        'proceed_to_payment' => 'Tiến hành thanh toán',
        'bonus_title' => 'Chương trình khuyến mãi',
        'bonus_subtitle' => 'Nạp càng nhiều - Nhận càng nhiều!',
        'tier1' => 'Nạp từ $100',
        'tier1_bonus' => 'Tặng 5%',
        'tier2' => 'Nạp từ $500',
        'tier2_bonus' => 'Tặng 6%',
        'tier3' => 'Nạp từ $1,000',
        'tier3_bonus' => 'Tặng 7%',
        'example' => 'Ví dụ',
        'how_it_works' => 'Cách hoạt động',
        'step1' => 'Nhập số tiền USD bạn muốn nạp vào tài khoản.',
        'step2' => 'Hệ thống tự động tính khuyến mãi theo cấp bậc của bạn.',
        'step3' => 'Nhấn nút "Tiến hành thanh toán" để được chuyển đến cổng thanh toán an toàn.',
        'step4' => 'Hoàn tất thanh toán bằng tiền điện tử (USDT TRC20, BEP20, v.v.).',
        'step5' => 'Số dư gốc + khuyến mãi sẽ tự động cộng vào tài khoản trong vài phút.',
        'note_title' => 'Lưu ý quan trọng',
        'note1' => 'Khuyến mãi được tính tự động và cộng ngay sau khi giao dịch thành công.',
        'note2' => 'Giao dịch được xử lý qua cổng thanh toán FPAYAZ, đảm bảo an toàn tuyệt đối.',
        'note3' => 'Nếu gặp sự cố, vui lòng liên hệ bộ phận hỗ trợ với mã giao dịch.',
        'member_rank' => 'Hạng Thành Viên',
        'your_rank' => 'Hạng của bạn',
        'view_ranks' => 'Xem chi tiết',
        'modal_title' => 'Hạng Thành Viên',
        'total_deposited' => 'Tổng Tiền Nạp',
        'bonus_rate' => '% Tiền Thưởng',
        'close' => 'Đóng'
    ],
    'en' => [
        'title' => 'International Deposit (USD)',
        'subtitle' => 'Secure payment via cryptocurrency payment gateway',
        'current_balance' => 'Current Balance',
        'deposit_amount' => 'Amount to Deposit',
        'enter_amount_usd' => 'Enter Amount (USD)',
        'bonus' => 'Bonus',
        'total_received' => 'Total Received',
        'proceed_to_payment' => 'Proceed to Payment',
        'bonus_title' => 'Bonus Program',
        'bonus_subtitle' => 'Deposit More - Receive More!',
        'tier1' => 'Deposit from $100',
        'tier1_bonus' => 'Get 5%',
        'tier2' => 'Deposit from $500',
        'tier2_bonus' => 'Get 6%',
        'tier3' => 'Deposit from $1,000',
        'tier3_bonus' => 'Get 7%',
        'example' => 'Example',
        'how_it_works' => 'How It Works',
        'step1' => 'Enter the amount in USD you want to deposit into your account.',
        'step2' => 'System automatically calculates bonus based on your tier.',
        'step3' => 'Click "Proceed to Payment" button to be redirected to secure payment gateway.',
        'step4' => 'Complete payment using cryptocurrency (USDT TRC20, BEP20, etc.).',
        'step5' => 'Base amount + bonus will be automatically added to your account within minutes.',
        'note_title' => 'Important Notes',
        'note1' => 'Bonus is calculated automatically and added immediately after successful transaction.',
        'note2' => 'Transactions are processed through FPAYAZ payment gateway, ensuring absolute security.',
        'note3' => 'If you encounter any issues, please contact support with your transaction ID.',
        'member_rank' => 'Member Rank',
        'your_rank' => 'Your Rank',
        'view_ranks' => 'View Details',
        'modal_title' => 'Membership Ranks',
        'total_deposited' => 'Total Deposited',
        'bonus_rate' => 'Bonus Rate',
        'close' => 'Close'
    ]
];

$t = $texts[$lang];

require_once '../config/sidebar.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    .page-header h1 { 
        font-size: 28px; 
        font-weight: 700; 
        margin: 0 0 8px 0; 
        color: white !important;
        position: relative;
        z-index: 1;
    }
    .page-header p { 
        font-size: 15px; 
        opacity: 0.95; 
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .deposit-card {
        background-color: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .deposit-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .balance-display {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 14px;
        padding: 25px;
        margin-bottom: 25px;
        color: white;
        text-align: center;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    .rank-badge-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 15px;
        margin-bottom: 25px; /* Added margin */
        padding-top: 15px;
        border-top: 1px solid rgba(102, 126, 234, 0.2);
        position: relative;
        z-index: 1;
    }
    .rank-info-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rank-label {
        opacity: 0.9;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-color);
    }
    .rank-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(102, 126, 234, 0.2);
        backdrop-filter: blur(10px);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid rgba(102, 126, 234, 0.3);
        color: var(--text-color);
    }
    .rank-badge i {
        font-size: 16px;
    }
    .rank-percent {
        opacity: 0.8;
        font-size: 12px;
    }
    .btn-view-ranks {
        background: rgba(102, 126, 234, 0.2);
        backdrop-filter: blur(10px);
        color: var(--text-color);
        border: 1px solid rgba(102, 126, 234, 0.3);
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-view-ranks:hover {
        background: rgba(102, 126, 234, 0.3);
        border-color: rgba(102, 126, 234, 0.5);
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    .modal-overlay.active {
        display: flex;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(50px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-content {
        background: var(--content-bg);
        border-radius: 20px;
        width: 90%;
        max-width: 600px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 25px 30px;
        border-radius: 20px 20px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .modal-header h2 {
        font-size: 24px;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 30px;
    }

    .ranks-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ranks-table thead {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
    }

    .ranks-table th {
        padding: 16px 20px;
        text-align: left;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border-color);
    }

    .ranks-table th:last-child {
        text-align: center;
    }

    .ranks-table td {
        padding: 18px 20px;
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
    }

    .ranks-table tbody tr.current-rank {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.1));
        border-left: 4px solid #667eea;
    }

    .rank-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 16px;
        color: var(--text-color);
    }

    .rank-color-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .current-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 8px;
    }

    .deposit-amount {
        font-weight: 700;
        color: var(--text-color);
        font-size: 15px;
    }

    .bonus-percentage {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.15));
        color: #667eea;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 16px;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    .balance-label {
        font-size: 14px;
        opacity: 0.95;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 500;
    }
    .balance-amount {
        font-size: 38px;
        font-weight: 800;
        letter-spacing: 1px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 10px;
    }
    .form-control {
        width: 100%;
        padding: 14px 18px;
        background-color: var(--input-bg);
        border: 2px solid var(--input-border);
        border-radius: 12px;
        color: var(--text-color);
        font-size: 18px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    
    .calculation-box {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border: 2px dashed #667eea;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        display: none;
    }
    .calculation-box.active {
        display: block;
        animation: slideDown 0.3s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .calc-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        font-size: 15px;
    }
    .calc-label {
        color: var(--text-secondary);
        font-weight: 500;
    }
    .calc-value {
        color: var(--text-color);
        font-weight: 700;
        font-size: 16px;
    }
    .calc-bonus {
        color: #10b981 !important;
        font-size: 18px !important;
    }
    .calc-total {
        border-top: 2px solid var(--border-color);
        margin-top: 10px;
        padding-top: 15px;
    }
    .calc-total .calc-value {
        color: #667eea;
        font-size: 24px !important;
    }
    
    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 700;
        font-size: 17px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
    }
    .btn-submit:active {
        transform: translateY(0);
    }
    
    .bonus-section {
        margin-bottom: 30px;
    }
    .bonus-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .bonus-header h3 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 8px;
    }
    .bonus-header p {
        color: var(--text-secondary);
        font-size: 14px;
    }
    
    .bonus-tiers {
        display: grid;
        gap: 15px;
    }
    .bonus-tier {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .bonus-tier::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.5s ease;
    }
    .bonus-tier:hover::before {
        left: 100%;
    }
    .bonus-tier:hover {
        border-color: #667eea;
        transform: translateX(5px);
    }
    .bonus-tier.active {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }
    .tier-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .tier-info h4 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin: 0 0 5px 0;
    }
    .tier-info p {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0;
    }
    .tier-badge {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 8px 18px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }
    
    .instructions-list {
        display: grid;
        gap: 15px;
        margin-top: 20px;
    }
    .instruction-item {
        display: flex;
        gap: 15px;
        align-items: flex-start;
        padding: 15px;
        background-color: var(--input-bg);
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .instruction-item:hover {
        background-color: rgba(102, 126, 234, 0.08);
        transform: translateX(5px);
    }
    .instruction-number {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    }
    .instruction-text {
        color: var(--text-color);
        font-size: 14px;
        line-height: 1.6;
        padding-top: 7px;
    }
    
    .note-list {
        list-style: none;
        padding: 0;
        margin: 15px 0 0 0;
    }
    .note-list li {
        display: flex;
        align-items: flex-start;
        margin-bottom: 12px;
        font-size: 14px;
        color: var(--text-color);
        line-height: 1.6;
    }
    .note-list li i {
        color: #10b981;
        margin-right: 10px;
        margin-top: 3px;
        font-size: 16px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title i {
        color: #667eea;
        font-size: 22px;
    }
</style>

<div class="page-header">
    <h1><i class="bi bi-globe-americas"></i> <?php echo $t['title']; ?></h1>
    <p><?php echo $t['subtitle']; ?></p>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="deposit-card h-100">
            <div class="balance-display">
                <div class="balance-label"><?php echo $t['current_balance']; ?></div>
                <div class="balance-amount">$<?php echo number_format($current_balance, 2, '.', ','); ?></div>
            </div>

            <div class="rank-badge-container">
                <div class="rank-info-group">
                    <span class="rank-label"><?php echo $t['your_rank']; ?>:</span>
                    <span class="rank-badge">
                        <i class="bi bi-star-fill"></i>
                        <?php echo $current_rank; ?>
                        <span class="rank-percent">• <?php echo $ranks[$current_rank]['bonus']; ?>%</span>
                    </span>
                    <button class="btn-view-ranks" onclick="openRankModal()">
                        <i class="bi bi-list-ul"></i>
                        <?php echo $t['view_ranks']; ?>
                    </button>
                </div>
            </div>

            <form id="fpayaz-form" action="https://fpayaz.com/checkout" method="post">
                <input type="hidden" name="KEY" value="<?php echo FPAYAZ_KEY; ?>">
                <input type="hidden" name="AMOUNT" id="form-amount" value="">
                <input type="hidden" name="BAGGAGE_FIELDS" value="ACCOUNT_ID ACCOUNT_USER">
                <input type="hidden" name="ACCOUNT_ID" value="<?php echo htmlspecialchars($user_id); ?>">
                <input type="hidden" name="ACCOUNT_USER" value="<?php echo htmlspecialchars($username); ?>">
                <input type="hidden" name="REDIRECT_URL" value="<?php echo FPAYAZ_REDIRECT_URL; ?>">
                
                <div class="form-group">
                    <label for="amount-input"><?php echo $t['deposit_amount']; ?></label>
                    <input type="number" id="amount-input" class="form-control" placeholder="100.00" step="0.01" min="1.00" required>
                </div>

                <div class="calculation-box" id="calculation-box">
                    <div class="calc-row">
                        <span class="calc-label"><?php echo $t['deposit_amount']; ?>:</span>
                        <span class="calc-value" id="display-amount">$0.00</span>
                    </div>
                    <div class="calc-row">
                        <span class="calc-label"><?php echo $t['bonus']; ?>:</span>
                        <span class="calc-value calc-bonus" id="display-bonus">+$0.00 (<span id="bonus-percent">0</span>%)</span>
                    </div>
                    <div class="calc-row calc-total">
                        <span class="calc-label" style="font-size: 17px; font-weight: 700;"><?php echo $t['total_received']; ?>:</span>
                        <span class="calc-value" id="display-total">$0.00</span>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-shield-lock-fill"></i>
                    <?php echo $t['proceed_to_payment']; ?>
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="deposit-card mb-4">
            <div class="bonus-section">
                <div class="bonus-header">
                    <h3><i class="bi bi-gift-fill" style="color: #f59e0b;"></i> <?php echo $t['bonus_title']; ?></h3>
                    <p><?php echo $t['bonus_subtitle']; ?></p>
                </div>
                
                <div class="bonus-tiers">
                    <div class="bonus-tier" data-min="100" data-bonus="5">
                        <div class="tier-content">
                            <div class="tier-info">
                                <h4><?php echo $t['tier1']; ?></h4>
                                <p><?php echo $t['example']; ?>: $100 → $105</p>
                            </div>
                            <div class="tier-badge"><?php echo $t['tier1_bonus']; ?></div>
                        </div>
                    </div>
                    
                    <div class="bonus-tier" data-min="500" data-bonus="6">
                        <div class="tier-content">
                            <div class="tier-info">
                                <h4><?php echo $t['tier2']; ?></h4>
                                <p><?php echo $t['example']; ?>: $500 → $530</p>
                            </div>
                            <div class="tier-badge"><?php echo $t['tier2_bonus']; ?></div>
                        </div>
                    </div>
                    
                    <div class="bonus-tier" data-min="1000" data-bonus="7">
                        <div class="tier-content">
                            <div class="tier-info">
                                <h4><?php echo $t['tier3']; ?></h4>
                                <p><?php echo $t['example']; ?>: $1,000 → $1,070</p>
                            </div>
                            <div class="tier-badge"><?php echo $t['tier3_bonus']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="deposit-card">
            <h3 class="section-title">
                <i class="bi bi-info-circle-fill"></i>
                <?php echo $t['how_it_works']; ?>
            </h3>
            <div class="instructions-list">
                <div class="instruction-item">
                    <div class="instruction-number">1</div>
                    <div class="instruction-text"><?php echo $t['step1']; ?></div>
                </div>
                <div class="instruction-item">
                    <div class="instruction-number">2</div>
                    <div class="instruction-text"><?php echo $t['step2']; ?></div>
                </div>
                <div class="instruction-item">
                    <div class="instruction-number">3</div>
                    <div class="instruction-text"><?php echo $t['step3']; ?></div>
                </div>
                <div class="instruction-item">
                    <div class="instruction-number">4</div>
                    <div class="instruction-text"><?php echo $t['step4']; ?></div>
                </div>
                <div class="instruction-item">
                    <div class="instruction-number">5</div>
                    <div class="instruction-text"><?php echo $t['step5']; ?></div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3 class="section-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $t['note_title']; ?>
                </h3>
                <ul class="note-list">
                    <li><i class="bi bi-check-circle-fill"></i><?php echo $t['note1']; ?></li>
                    <li><i class="bi bi-check-circle-fill"></i><?php echo $t['note2']; ?></li>
                    <li><i class="bi bi-check-circle-fill"></i><?php echo $t['note3']; ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="rankModal" onclick="closeRankModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>
                <i class="bi bi-trophy-fill"></i>
                <?php echo $t['modal_title']; ?>
            </h2>
            <button class="modal-close" onclick="closeRankModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="modal-body">
            <table class="ranks-table">
                <thead>
                    <tr>
                        <th><?php echo $t['member_rank']; ?></th>
                        <th><?php echo $t['total_deposited']; ?></th>
                        <th><?php echo $t['bonus_rate']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranks as $rank_name => $rank_data): ?>
                    <tr class="<?php echo $rank_name === $current_rank ? 'current-rank' : ''; ?>">
                        <td>
                            <div class="rank-name-cell">
                                <span class="rank-color-dot" style="background: <?php echo $rank_data['color']; ?>"></span>
                                <?php echo $rank_name; ?>
                                <?php if ($rank_name === $current_rank): ?>
                                    <span class="current-badge"><?php echo $lang === 'vi' ? 'Hiện tại' : 'Current'; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="deposit-amount">
                                <?php 
                                echo $rank_data['min'] > 0 
                                    ? '>= $' . number_format($rank_data['min']) 
                                    : '>= $0'; 
                                ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="bonus-percentage"><?php echo $rank_data['bonus']; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
// Modal functions
function openRankModal() {
    document.getElementById('rankModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRankModal(event) {
    // Check if the click is on the overlay itself, not its children
    if (!event || event.target.id === 'rankModal') {
        document.getElementById('rankModal').classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('rankModal');
        if (modal.classList.contains('active')) {
            closeRankModal();
        }
    }
});

// Hàm tính khuyến mãi theo cấp bậc
function calculateBonus(amount) {
    if (amount >= 1000) return 7;
    if (amount >= 500) return 6;
    if (amount >= 100) return 5;
    return 0;
}

// Hàm highlight tier đang active
function highlightActiveTier(amount) {
    document.querySelectorAll('.bonus-tier').forEach(tier => {
        tier.classList.remove('active');
    });
    
    let activeTier = null;
    if (amount >= 1000) activeTier = document.querySelector('[data-min="1000"]');
    else if (amount >= 500) activeTier = document.querySelector('[data-min="500"]');
    else if (amount >= 100) activeTier = document.querySelector('[data-min="100"]');
    
    if (activeTier) activeTier.classList.add('active');
}

// Xử lý khi người dùng nhập số tiền
document.getElementById('amount-input').addEventListener('input', function(e) {
    const amount = parseFloat(this.value) || 0;
    const calcBox = document.getElementById('calculation-box');
    
    if (amount > 0) {
        const bonusPercent = calculateBonus(amount);
        const bonusAmount = (amount * bonusPercent / 100);
        const totalAmount = amount + bonusAmount;
        
        // Hiển thị calculation box
        calcBox.classList.add('active');
        
        // Cập nhật giá trị
        document.getElementById('display-amount').textContent = '$' + amount.toFixed(2);
        document.getElementById('display-bonus').innerHTML = '+$' + bonusAmount.toFixed(2) + ' (<span id="bonus-percent">' + bonusPercent + '</span>%)';
        document.getElementById('display-total').textContent = '$' + totalAmount.toFixed(2);
        
        // Highlight tier đang active
        highlightActiveTier(amount);
    } else {
        calcBox.classList.remove('active');
        document.querySelectorAll('.bonus-tier').forEach(tier => {
            tier.classList.remove('active');
        });
    }
});

// Xử lý khi submit form
document.getElementById('fpayaz-form').addEventListener('submit', function(e) {
    const amountInput = document.getElementById('amount-input').value;
    const amount = parseFloat(amountInput);

    // Kiểm tra số tiền hợp lệ
    if (isNaN(amount) || amount < 1.00) {
        e.preventDefault();
        alert('<?php echo $lang === 'vi' ? 'Vui lòng nhập số tiền hợp lệ, tối thiểu $1.00 USD' : 'Please enter a valid amount, minimum is $1.00 USD'; ?>');
        return;
    }
    
    // Gán giá trị đã được làm tròn vào trường ẩn 'AMOUNT'
    document.getElementById('form-amount').value = amount.toFixed(2);
});

// Click vào bonus tier để tự động điền số tiền
document.querySelectorAll('.bonus-tier').forEach(tier => {
    tier.addEventListener('click', function() {
        const minAmount = this.getAttribute('data-min');
        document.getElementById('amount-input').value = minAmount;
        document.getElementById('amount-input').dispatchEvent(new Event('input'));
    });
});
</script>

<?php require_once '../config/footer.php'; ?>