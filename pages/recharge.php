<?php
$page_title = $lang === 'vi' ? 'Nạp Tiền' : 'Deposit';
require_once '../config/header.php';

// --- BANK CONFIG ---
define('BANK_NAME', 'ACB');
define('ACCOUNT_NUMBER', '5385268');
define('ACCOUNT_HOLDER', 'BUI DUC CONG');
define('BANK_ID_FOR_QR', '970416');

// --- FPAYAZ CONFIG ---
define('FPAYAZ_KEY', '90b64799b18aba1144da3a4eaa2eeefb');
define('FPAYAZ_REDIRECT_URL', 'https://kingcongstudio.com/international_deposit');
define('FPAYAZ_WEBHOOK_URL', 'https://kingcongstudio.com/auth/nganhang/webhookquocte.php');

// Get user info
$user_id = $user['id'];
$username = $user['taikhoan'];
$current_balance = $user['sodu'] ?? 0;
$deposit_code = $user['ma_giao_dich'];

// Calculate total deposits
$stmt = $mysqli->prepare("SELECT COALESCE(SUM(amount), 0) as total_deposited FROM Transactions WHERE user_id = ? AND type = 'deposit'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_deposited = $result->fetch_assoc()['total_deposited'] ?? 0;
$stmt->close();

// Get deposit transaction history (10 most recent)
$stmt = $mysqli->prepare("
    SELECT id, amount, content, created_at 
    FROM Transactions 
    WHERE user_id = ? AND type = 'deposit' 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
$transactions = [];
while ($row = $transactions_result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Determine member level and bonus
function getMemberLevel($total) {
    if ($total >= 1500) return ['level' => 'VIP', 'bonus' => 5, 'min' => 1500];
    if ($total >= 1000) return ['level' => 'FREQUENT', 'bonus' => 4, 'min' => 1000];
    if ($total >= 500) return ['level' => 'ELITE', 'bonus' => 3, 'min' => 500];
    if ($total >= 100) return ['level' => 'JUNIOR', 'bonus' => 2, 'min' => 100];
    return ['level' => 'NEWBIE', 'bonus' => 0, 'min' => 0];
}

$member_info = getMemberLevel($total_deposited);

// QR Code URL for local deposit
$qr_code_url = "https://api.vietqr.io/image/" . BANK_ID_FOR_QR . "-" . ACCOUNT_NUMBER . "-print.png?addInfo=" . urlencode($deposit_code) . "&accountName=" . urlencode(ACCOUNT_HOLDER);

// Multi-language
$texts = [
    'vi' => [
        'title' => 'Nạp Tiền Vào Tài Khoản',
        'subtitle' => 'Chọn phương thức nạp tiền phù hợp với bạn',
        'balance' => 'Số dư hiện tại',
        'member_level' => 'Hạng thành viên',
        'total_deposited' => 'Tổng tiền đã nạp',
        'deposit_bonus' => 'Tiền thưởng nạp',
        'choose_method' => 'Chọn phương thức thanh toán',
        'local_deposit' => 'Nạp Tiền Nội Địa',
        'local_desc' => 'Chuyển khoản qua ngân hàng Việt Nam',
        'international_deposit' => 'Nạp Tiền Quốc Tế',
        'international_desc' => 'Thanh toán bằng tiền điện tử (USDT)',
        'member_levels_title' => 'Bảng hạng thành viên',
        'show_details' => 'Xem chi tiết',
        'hide_details' => 'Thu gọn',
        'newbie' => 'Thành viên mới',
        'junior' => 'Thành viên trung cấp',
        'elite' => 'Thành viên cao cấp',
        'frequent' => 'Thành viên thường xuyên',
        'vip' => 'Thành viên VIP',
        // Local deposit texts
        'bank' => 'Ngân hàng',
        'account_number' => 'Số tài khoản',
        'account_holder' => 'Chủ tài khoản',
        'transfer_content' => 'Nội dung chuyển khoản',
        'copy' => 'Sao chép',
        'copied' => 'Đã sao chép!',
        'qr_title' => 'Quét mã QR để chuyển khoản',
        'qr_subtitle' => 'Mở app ngân hàng và quét mã QR này',
        'warning_title' => 'Lưu ý quan trọng',
        'warning_text' => 'Vui lòng điền chính xác nội dung chuyển khoản. Tiền sẽ tự động cộng sau 1-5 phút.',
        'instruction_title' => 'Hướng dẫn nạp tiền',
        'step1' => 'Mở ứng dụng ngân hàng',
        'step2' => 'Quét mã QR hoặc nhập thông tin',
        'step3' => 'Nhập số tiền cần nạp',
        'step4' => 'Kiểm tra nội dung',
        'step5' => 'Xác nhận chuyển khoản',
        'step6' => 'Chờ tiền tự động cộng vào tài khoản',
        'note_title' => 'Điều cần biết',
        'note1' => 'Không thay đổi nội dung chuyển khoản',
        'note2' => 'Số tiền tối thiểu: $1 USD',
        'note3' => 'Thời gian xử lý: 1-5 phút',
        'note4' => 'Liên hệ hỗ trợ nếu sau 10 phút chưa nhận được tiền',
        'bank_info' => 'Thông tin ngân hàng',
        // International deposit texts
        'int_title' => 'Nạp Tiền Quốc Tế (USD)',
        'int_subtitle' => 'Thanh toán an toàn qua cổng thanh toán tiền điện tử',
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
        'int_step1' => 'Nhập số tiền USD bạn muốn nạp vào tài khoản.',
        'int_step2' => 'Hệ thống tự động tính khuyến mãi theo cấp bậc của bạn.',
        'int_step3' => 'Nhấn nút "Tiến hành thanh toán" để được chuyển đến cổng thanh toán an toàn.',
        'int_step4' => 'Hoàn tất thanh toán bằng tiền điện tử (USDT TRC20, BEP20, v.v.).',
        'int_step5' => 'Số dư gốc + khuyến mãi sẽ tự động cộng vào tài khoản trong vài phút.',
        'int_note1' => 'Khuyến mãi được tính tự động và cộng ngay sau khi giao dịch thành công.',
        'int_note2' => 'Giao dịch được xử lý qua cổng thanh toán FPAYAZ, đảm bảo an toàn tuyệt đối.',
        'int_note3' => 'Nếu gặp sự cố, vui lòng liên hệ bộ phận hỗ trợ với mã giao dịch.',
        // History
        'history_title' => 'Lịch sử giao dịch',
        'no_history' => 'Chưa có giao dịch nào',
        'amount' => 'Số tiền',
        'time' => 'Thời gian',
        'description' => 'Mô tả',
    ],
    'en' => [
        'title' => 'Deposit to Account',
        'subtitle' => 'Choose your preferred deposit method',
        'balance' => 'Current Balance',
        'member_level' => 'Member Level',
        'total_deposited' => 'Total Deposited',
        'deposit_bonus' => 'Deposit Bonus',
        'choose_method' => 'Choose Payment Method',
        'local_deposit' => 'Local Deposit',
        'local_desc' => 'Bank transfer via Vietnamese banks',
        'international_deposit' => 'International Deposit',
        'international_desc' => 'Pay with cryptocurrency (USDT)',
        'member_levels_title' => 'Member Tier System',
        'show_details' => 'Show Details',
        'hide_details' => 'Hide Details',
        'newbie' => 'New Member',
        'junior' => 'Junior Member',
        'elite' => 'Elite Member',
        'frequent' => 'Frequent Member',
        'vip' => 'VIP Member',
        // Local deposit
        'bank' => 'Bank',
        'account_number' => 'Account Number',
        'account_holder' => 'Account Holder',
        'transfer_content' => 'Transfer Content',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'qr_title' => 'Scan QR to Transfer',
        'qr_subtitle' => 'Open banking app and scan this QR code',
        'warning_title' => 'Important Notice',
        'warning_text' => 'Please use exact transfer content. Money will be added automatically within 1-5 minutes.',
        'instruction_title' => 'How to Deposit',
        'step1' => 'Open your banking app',
        'step2' => 'Scan QR code or enter info',
        'step3' => 'Enter deposit amount',
        'step4' => 'Check transfer content',
        'step5' => 'Confirm transfer',
        'step6' => 'Wait for automatic credit',
        'note_title' => 'Important Info',
        'note1' => 'Do not change transfer content',
        'note2' => 'Minimum amount: $1 USD',
        'note3' => 'Processing time: 1-5 minutes',
        'note4' => 'Contact support if not received after 10 minutes',
        'bank_info' => 'Bank Information',
        // International
        'int_title' => 'International Deposit (USD)',
        'int_subtitle' => 'Secure payment via cryptocurrency payment gateway',
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
        'int_step1' => 'Enter the amount in USD you want to deposit into your account.',
        'int_step2' => 'System automatically calculates bonus based on your tier.',
        'int_step3' => 'Click "Proceed to Payment" button to be redirected to secure payment gateway.',
        'int_step4' => 'Complete payment using cryptocurrency (USDT TRC20, BEP20, etc.).',
        'int_step5' => 'Base amount + bonus will be automatically added to your account within minutes.',
        'int_note1' => 'Bonus is calculated automatically and added immediately after successful transaction.',
        'int_note2' => 'Transactions are processed through FPAYAZ payment gateway, ensuring absolute security.',
        'int_note3' => 'If you encounter any issues, please contact support with your transaction ID.',
        // History
        'history_title' => 'Transaction History',
        'no_history' => 'No transactions yet',
        'amount' => 'Amount',
        'time' => 'Time',
        'description' => 'Description',
    ]
];

$t = $texts[$lang];

require_once '../config/sidebar.php';
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --member-newbie: #94a3b8;
    --member-junior: #3b82f6;
    --member-elite: #8b5cf6;
    --member-frequent: #f59e0b;
    --member-vip: #ef4444;
}

.page-header {
    background: var(--primary-gradient);
    border-radius: 20px;
    padding: 35px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 10px 0;
    color: white !important;
    position: relative;
    z-index: 1;
}

.page-header p {
    font-size: 16px;
    opacity: 0.95;
    margin: 0;
    position: relative;
    z-index: 1;
}

/* Info Cards Row */
.info-cards-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--primary-gradient);
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.info-card-label {
    font-size: 13px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-card-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-color);
    margin-bottom: 8px;
}

.info-card-sub {
    font-size: 14px;
    color: var(--text-secondary);
}

/* Member Level Badge */
.member-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 16px;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.member-badge.newbie { background: var(--member-newbie); }
.member-badge.junior { background: var(--member-junior); }
.member-badge.elite { background: var(--member-elite); }
.member-badge.frequent { background: var(--member-frequent); }
.member-badge.vip { background: var(--member-vip); }

/* Member Levels Table - Collapsible */
.levels-table {
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.levels-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title i {
    color: #667eea;
    font-size: 26px;
}

.toggle-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.toggle-btn i {
    transition: transform 0.3s ease;
}

.toggle-btn.active i {
    transform: rotate(180deg);
}

.levels-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, margin-top 0.4s ease;
}

.levels-content.show {
    max-height: 600px;
    margin-top: 20px;
}

.levels-grid {
    display: grid;
    gap: 12px;
}

.level-row {
    display: grid;
    grid-template-columns: 40px 120px 1fr 1fr 100px;
    gap: 20px;
    align-items: center;
    padding: 18px 24px;
    background: var(--input-bg);
    border: 2px solid var(--border-color);
    border-radius: 14px;
    transition: all 0.3s ease;
}

.level-row:hover {
    border-color: #667eea;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
}

.level-row.current {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.level-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.level-icon.newbie { background: var(--member-newbie); }
.level-icon.junior { background: var(--member-junior); }
.level-icon.elite { background: var(--member-elite); }
.level-icon.frequent { background: var(--member-frequent); }
.level-icon.vip { background: var(--member-vip); }

.level-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color);
}

.level-desc {
    font-size: 14px;
    color: var(--text-secondary);
}

.level-requirement {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-color);
}

.level-bonus {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 20px;
    font-weight: 700;
    font-size: 15px;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

/* Method Selection */
.method-section {
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.method-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.method-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    border: 2px solid var(--border-color);
    border-radius: 16px;
    padding: 28px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.method-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transition: left 0.5s ease;
}

.method-card:hover::before {
    left: 100%;
}

.method-card:hover {
    transform: translateY(-8px);
    border-color: #667eea;
    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.2);
}

.method-card.active {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.method-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-gradient);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.method-icon i {
    font-size: 28px;
    color: white;
}

.method-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 8px;
}

.method-desc {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.6;
}

.method-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #10b981;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
}

/* Deposit Forms Container */
.deposit-container {
    display: none;
    animation: fadeIn 0.4s ease;
}

.deposit-container.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== LOCAL DEPOSIT STYLES ===== */
.deposit-card {
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-title i {
    color: #10b981;
    font-size: 22px;
}

.deposit-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 25px;
}

.bank-info-grid {
    display: grid;
    gap: 15px;
    margin-bottom: 25px;
}

.bank-info-item {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.03));
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.bank-info-item:hover {
    border-color: #10b981;
    transform: translateX(5px);
}

.bank-info-label {
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bank-info-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-color);
}

.transfer-code-section {
    background: var(--success-gradient);
    border-radius: 14px;
    padding: 25px;
    text-align: center;
    margin-bottom: 25px;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

.transfer-code-label {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.transfer-code-display {
    font-size: 32px;
    font-weight: 800;
    color: white;
    letter-spacing: 3px;
    margin-bottom: 15px;
    font-family: 'Courier New', monospace;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    user-select: all;
}

.btn-copy {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-copy:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

.qr-section {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
    border-radius: 16px;
    border: 2px dashed #10b981;
}

.qr-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 8px;
}

.qr-subtitle {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.qr-image-container {
    background: white;
    padding: 15px;
    border-radius: 12px;
    display: inline-block;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.qr-section img {
    max-width: 280px;
    width: 100%;
    border-radius: 8px;
    display: block;
}

.alert-box {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
    border-left: 4px solid #ef4444;
    padding: 18px 20px;
    border-radius: 10px;
    display: flex;
    gap: 15px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.alert-box i {
    color: #ef4444;
    font-size: 22px;
    flex-shrink: 0;
    margin-top: 2px;
}

.alert-content h4 {
    font-size: 15px;
    font-weight: 700;
    color: #ef4444;
    margin: 0 0 5px 0;
}

.alert-content p {
    font-size: 14px;
    color: var(--text-color);
    margin: 0;
    line-height: 1.6;
}

.instructions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.instruction-card {
    background-color: var(--input-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 18px;
    transition: all 0.3s ease;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.instruction-card:hover {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), transparent);
    border-color: #10b981;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.instruction-number {
    width: 38px;
    height: 38px;
    background: var(--success-gradient);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.instruction-text {
    color: var(--text-color);
    font-size: 14px;
    line-height: 1.6;
    padding-top: 8px;
    font-weight: 500;
}

.notes-grid {
    display: grid;
    gap: 12px;
    margin-top: 15px;
}

.note-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px;
    background-color: var(--input-bg);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.note-item:hover {
    background: linear-gradient(to right, rgba(16, 185, 129, 0.05), transparent);
}

.note-item i {
    color: #10b981;
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 2px;
}

.note-item span {
    font-size: 14px;
    color: var(--text-color);
    line-height: 1.6;
}

/* ===== INTERNATIONAL DEPOSIT STYLES ===== */
.int-deposit-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 25px;
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
    background: var(--primary-gradient);
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
    background: var(--success-gradient);
    color: white;
    padding: 8px 18px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 16px;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

/* Transaction History */
.history-section {
    background-color: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-top: 30px;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.history-table thead {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
}

.history-table th {
    padding: 14px 18px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
}

.history-table td {
    padding: 16px 18px;
    font-size: 14px;
    color: var(--text-color);
    border-bottom: 1px solid var(--border-color);
}

.history-table tbody tr {
    transition: all 0.2s;
}

.history-table tbody tr:hover {
    background: linear-gradient(to right, rgba(16, 185, 129, 0.05), transparent);
}

.amount-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.1));
    border-radius: 20px;
    color: #10b981;
    font-weight: 700;
    font-size: 15px;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
}

.empty-history {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.empty-history i {
    font-size: 64px;
    opacity: 0.2;
    margin-bottom: 15px;
    color: #10b981;
}

.empty-history p {
    font-size: 15px;
    margin: 0;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 1200px) {
    .deposit-grid, .int-deposit-grid {
        grid-template-columns: 1fr;
    }
    
    .instructions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .info-cards-row {
        grid-template-columns: 1fr;
    }
    
    .method-grid {
        grid-template-columns: 1fr;
    }
    
    .level-row {
        grid-template-columns: 1fr;
        gap: 12px;
        text-align: center;
    }
    
    .level-icon {
        margin: 0 auto;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .info-card-value {
        font-size: 24px;
    }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>
        <i class="bi bi-wallet2"></i>
        <?php echo $t['title']; ?>
    </h1>
    <p><?php echo $t['subtitle']; ?></p>
</div>

<!-- Info Cards -->
<div class="info-cards-row">
    <div class="info-card">
        <div class="info-card-label">
            <i class="bi bi-wallet-fill"></i>
            <?php echo $t['balance']; ?>
        </div>
        <div class="info-card-value">$<?php echo number_format($current_balance, 2, '.', ','); ?></div>
    </div>
    
    <div class="info-card">
        <div class="info-card-label">
            <i class="bi bi-star-fill"></i>
            <?php echo $t['member_level']; ?>
        </div>
        <div class="info-card-value">
            <span class="member-badge <?php echo strtolower($member_info['level']); ?>">
                <i class="bi bi-award-fill"></i>
                <?php echo $member_info['level']; ?>
            </span>
        </div>
    </div>
    
    <div class="info-card">
        <div class="info-card-label">
            <i class="bi bi-graph-up-arrow"></i>
            <?php echo $t['total_deposited']; ?>
        </div>
        <div class="info-card-value">$<?php echo number_format($total_deposited, 2, '.', ','); ?></div>
        <div class="info-card-sub"><?php echo $t['deposit_bonus']; ?>: <?php echo $member_info['bonus']; ?>%</div>
    </div>
</div>

<!-- Member Levels Table - Collapsible -->
<div class="levels-table">
    <div class="levels-header" onclick="toggleLevels()">
        <h3 class="section-title">
            <i class="bi bi-trophy-fill"></i>
            <?php echo $t['member_levels_title']; ?>
        </h3>
        <button type="button" class="toggle-btn" id="toggleLevelsBtn">
            <span id="toggleLevelsText"><?php echo $t['show_details']; ?></span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    
    <div class="levels-content" id="levelsContent">
        <div class="levels-grid">
            <?php 
            $levels = [
                ['name' => 'NEWBIE', 'icon' => 'newbie', 'min' => 0, 'bonus' => 0, 'desc' => $t['newbie']],
                ['name' => 'JUNIOR', 'icon' => 'junior', 'min' => 100, 'bonus' => 2, 'desc' => $t['junior']],
                ['name' => 'ELITE', 'icon' => 'elite', 'min' => 500, 'bonus' => 3, 'desc' => $t['elite']],
                ['name' => 'FREQUENT', 'icon' => 'frequent', 'min' => 1000, 'bonus' => 4, 'desc' => $t['frequent']],
                ['name' => 'VIP', 'icon' => 'vip', 'min' => 1500, 'bonus' => 5, 'desc' => $t['vip']],
            ];
            
            foreach ($levels as $level): 
                $is_current = ($level['name'] === $member_info['level']);
            ?>
            <div class="level-row <?php echo $is_current ? 'current' : ''; ?>">
                <div class="level-icon <?php echo $level['icon']; ?>">
                    <?php if($is_current): ?>
                        <i class="bi bi-check"></i>
                    <?php endif; ?>
                </div>
                <div class="level-name"><?php echo $level['name']; ?></div>
                <div class="level-desc"><?php echo $level['desc']; ?></div>
                <div class="level-requirement">≥ $<?php echo number_format($level['min'], 0); ?></div>
                <div class="level-bonus">+<?php echo $level['bonus']; ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Payment Method Selection -->
<div class="method-section">
    <h3 class="section-title">
        <i class="bi bi-credit-card-fill"></i>
        <?php echo $t['choose_method']; ?>
    </h3>
    
    <div class="method-grid">
        <div class="method-card" id="method-local" onclick="selectMethod('local')">
            <div class="method-icon">
                <i class="bi bi-bank"></i>
            </div>
            <div class="method-title"><?php echo $t['local_deposit']; ?></div>
            <div class="method-desc"><?php echo $t['local_desc']; ?></div>
            <span class="method-badge">VND</span>
        </div>
        
        <div class="method-card" id="method-international" onclick="selectMethod('international')">
            <div class="method-icon">
                <i class="bi bi-globe-americas"></i>
            </div>
            <div class="method-title"><?php echo $t['international_deposit']; ?></div>
            <div class="method-desc"><?php echo $t['international_desc']; ?></div>
            <span class="method-badge">USD</span>
        </div>
    </div>
</div>

<!-- Local Deposit Form -->
<div class="deposit-container" id="local-deposit">
    <div class="deposit-grid">
        <!-- Left Column: Bank Info -->
        <div>
            <div class="deposit-card">
                <h3 class="card-title">
                    <i class="bi bi-bank2"></i>
                    <?php echo $t['bank_info']; ?>
                </h3>

                <div class="bank-info-grid">
                    <div class="bank-info-item">
                        <span class="bank-info-label"><?php echo $t['bank']; ?></span>
                        <span class="bank-info-value"><?php echo htmlspecialchars(BANK_NAME); ?></span>
                    </div>
                    <div class="bank-info-item">
                        <span class="bank-info-label"><?php echo $t['account_number']; ?></span>
                        <span class="bank-info-value"><?php echo htmlspecialchars(ACCOUNT_NUMBER); ?></span>
                    </div>
                    <div class="bank-info-item">
                        <span class="bank-info-label"><?php echo $t['account_holder']; ?></span>
                        <span class="bank-info-value"><?php echo htmlspecialchars(ACCOUNT_HOLDER); ?></span>
                    </div>
                </div>

                <div class="transfer-code-section">
                    <div class="transfer-code-label"><?php echo $t['transfer_content']; ?></div>
                    <div class="transfer-code-display" id="depositCode"><?php echo htmlspecialchars($deposit_code); ?></div>
                    <button class="btn-copy" id="copyBtn">
                        <i class="bi bi-clipboard-check"></i>
                        <span id="copyText"><?php echo $t['copy']; ?></span>
                    </button>
                </div>

                <div class="alert-box">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="alert-content">
                        <h4><?php echo $t['warning_title']; ?></h4>
                        <p><?php echo $t['warning_text']; ?></p>
                    </div>
                </div>

                <h3 class="card-title" style="margin-top: 25px;">
                    <i class="bi bi-list-check"></i>
                    <?php echo $t['instruction_title']; ?>
                </h3>
                <div class="instructions-grid">
                    <div class="instruction-card">
                        <div class="instruction-number">1</div>
                        <div class="instruction-text"><?php echo $t['step1']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">2</div>
                        <div class="instruction-text"><?php echo $t['step2']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">3</div>
                        <div class="instruction-text"><?php echo $t['step3']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">4</div>
                        <div class="instruction-text"><?php echo $t['step4']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">5</div>
                        <div class="instruction-text"><?php echo $t['step5']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">6</div>
                        <div class="instruction-text"><?php echo $t['step6']; ?></div>
                    </div>
                </div>

                <h3 class="card-title" style="margin-top: 25px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <?php echo $t['note_title']; ?>
                </h3>
                <div class="notes-grid">
                    <div class="note-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo $t['note1']; ?></span>
                    </div>
                    <div class="note-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo $t['note2']; ?></span>
                    </div>
                    <div class="note-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo $t['note3']; ?></span>
                    </div>
                    <div class="note-item">
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?php echo $t['note4']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: QR Code -->
        <div>
            <div class="deposit-card">
                <div class="qr-section">
                    <div class="qr-title"><?php echo $t['qr_title']; ?></div>
                    <div class="qr-subtitle"><?php echo $t['qr_subtitle']; ?></div>
                    <div class="qr-image-container">
                        <img src="<?php echo $qr_code_url; ?>" alt="QR Code">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- International Deposit Form -->
<div class="deposit-container" id="international-deposit">
    <div class="int-deposit-grid">
        <!-- Left Column: Deposit Form -->
        <div>
            <div class="deposit-card">
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

        <!-- Right Column: Bonus Tiers & Instructions -->
        <div>
            <div class="deposit-card">
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

            <!-- Instructions -->
            <div class="deposit-card">
                <h3 class="card-title">
                    <i class="bi bi-info-circle-fill"></i>
                    <?php echo $t['how_it_works']; ?>
                </h3>
                <div class="instructions-grid">
                    <div class="instruction-card">
                        <div class="instruction-number">1</div>
                        <div class="instruction-text"><?php echo $t['int_step1']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">2</div>
                        <div class="instruction-text"><?php echo $t['int_step2']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">3</div>
                        <div class="instruction-text"><?php echo $t['int_step3']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">4</div>
                        <div class="instruction-text"><?php echo $t['int_step4']; ?></div>
                    </div>
                    <div class="instruction-card">
                        <div class="instruction-number">5</div>
                        <div class="instruction-text"><?php echo $t['int_step5']; ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3 class="card-title">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $t['note_title']; ?>
                    </h3>
                    <div class="notes-grid">
                        <div class="note-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?php echo $t['int_note1']; ?></span>
                        </div>
                        <div class="note-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?php echo $t['int_note2']; ?></span>
                        </div>
                        <div class="note-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?php echo $t['int_note3']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="history-section">
    <h3 class="card-title">
        <i class="bi bi-clock-history"></i>
        <?php echo $t['history_title']; ?>
    </h3>

    <?php if (count($transactions) > 0): ?>
    <table class="history-table">
        <thead>
            <tr>
                <th><?php echo $t['time']; ?></th>
                <th><?php echo $t['amount']; ?></th>
                <th><?php echo $t['description']; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $trans): ?>
            <tr>
                <td data-label="<?php echo $t['time']; ?>">
                    <span class="time-badge">
                        <i class="bi bi-clock"></i>
                        <?php 
                        $date = new DateTime($trans['created_at']);
                        echo $date->format('d/m/Y H:i'); 
                        ?>
                    </span>
                </td>
                <td data-label="<?php echo $t['amount']; ?>">
                    <span class="amount-badge">
                        <i class="bi bi-plus-circle-fill"></i>
                        $<?php echo number_format($trans['amount'], 2, '.', ','); ?>
                    </span>
                </td>
                <td data-label="<?php echo $t['description']; ?>">
                    <?php echo htmlspecialchars($trans['content']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-history">
        <i class="bi bi-inbox"></i>
        <p><?php echo $t['no_history']; ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
// Toggle Member Levels
function toggleLevels() {
    const content = document.getElementById('levelsContent');
    const btn = document.getElementById('toggleLevelsBtn');
    const text = document.getElementById('toggleLevelsText');
    
    content.classList.toggle('show');
    btn.classList.toggle('active');
    
    if (content.classList.contains('show')) {
        text.textContent = '<?php echo $t['hide_details']; ?>';
    } else {
        text.textContent = '<?php echo $t['show_details']; ?>';
    }
}

// Select Payment Method
function selectMethod(method) {
    // Remove active from all methods
    document.querySelectorAll('.method-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Hide all deposit containers
    document.querySelectorAll('.deposit-container').forEach(container => {
        container.classList.remove('active');
    });
    
    // Activate selected method
    if (method === 'local') {
        document.getElementById('method-local').classList.add('active');
        document.getElementById('local-deposit').classList.add('active');
    } else if (method === 'international') {
        document.getElementById('method-international').classList.add('active');
        document.getElementById('international-deposit').classList.add('active');
    }
    
    // Smooth scroll to deposit form
    setTimeout(() => {
        const depositForm = method === 'local' ? 
            document.getElementById('local-deposit') : 
            document.getElementById('international-deposit');
        depositForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

// Copy Deposit Code (Local) - Use event delegation to handle dynamically loaded content
document.addEventListener('click', function(e) {
    if (e.target.closest('#copyBtn')) {
        const btn = e.target.closest('#copyBtn');
        const code = document.getElementById('depositCode').innerText;
        const copyText = btn.querySelector('#copyText');
        const icon = btn.querySelector('i');
        
        navigator.clipboard.writeText(code).then(() => {
            copyText.textContent = '<?php echo $t['copied']; ?>';
            icon.className = 'bi bi-check-circle-fill';
            btn.style.background = 'rgba(16, 185, 129, 0.3)';
            btn.style.borderColor = 'rgba(255, 255, 255, 0.6)';
            
            setTimeout(() => {
                copyText.textContent = '<?php echo $t['copy']; ?>';
                icon.className = 'bi bi-clipboard-check';
                btn.style.background = 'rgba(255, 255, 255, 0.2)';
                btn.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            }, 2000);
        }).catch(err => {
            console.error('Copy failed:', err);
            alert('<?php echo $lang === 'vi' ? 'Lỗi sao chép!' : 'Copy failed!'; ?>');
        });
    }
});

// International Deposit - Calculate Bonus
function calculateBonus(amount) {
    if (amount >= 1000) return 7;
    if (amount >= 500) return 6;
    if (amount >= 100) return 5;
    return 0;
}

// Highlight Active Bonus Tier
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

// Amount Input Handler (International)
document.getElementById('amount-input')?.addEventListener('input', function(e) {
    const amount = parseFloat(this.value) || 0;
    const calcBox = document.getElementById('calculation-box');
    
    if (amount > 0) {
        const bonusPercent = calculateBonus(amount);
        const bonusAmount = (amount * bonusPercent / 100);
        const totalAmount = amount + bonusAmount;
        
        calcBox.classList.add('active');
        
        document.getElementById('display-amount').textContent = ' + amount.toFixed(2);
        document.getElementById('display-bonus').innerHTML = '+ + bonusAmount.toFixed(2) + ' (<span id="bonus-percent">' + bonusPercent + '</span>%)';
        document.getElementById('display-total').textContent = ' + totalAmount.toFixed(2);
        
        highlightActiveTier(amount);
    } else {
        calcBox.classList.remove('active');
        document.querySelectorAll('.bonus-tier').forEach(tier => {
            tier.classList.remove('active');
        });
    }
});

// International Form Submit - Use event delegation
document.addEventListener('submit', function(e) {
    if (e.target.id === 'fpayaz-form') {
        const amountInput = document.getElementById('amount-input').value;
        const amount = parseFloat(amountInput);

        if (isNaN(amount) || amount < 1.00) {
            e.preventDefault();
            alert('<?php echo $lang === 'vi' ? 'Vui lòng nhập số tiền hợp lệ, tối thiểu $1.00 USD' : 'Please enter a valid amount, minimum is $1.00 USD'; ?>');
            return;
        }
        
        document.getElementById('form-amount').value = amount.toFixed(2);
    }
});

// Click Bonus Tier to Auto-fill Amount
document.querySelectorAll('.bonus-tier').forEach(tier => {
    tier.addEventListener('click', function() {
        const minAmount = this.getAttribute('data-min');
        const amountInput = document.getElementById('amount-input');
        if (amountInput) {
            amountInput.value = minAmount;
            amountInput.dispatchEvent(new Event('input'));
        }
    });
});

// Auto-select method from URL parameter
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const method = urlParams.get('method');
    if (method === 'local' || method === 'international') {
        selectMethod(method);
    }
});
</script>

<?php require_once '../config/footer.php'; ?>