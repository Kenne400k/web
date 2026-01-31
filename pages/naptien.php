<?php
$page_title = $lang === 'vi' ? 'Nạp Tiền' : 'Deposit';
require_once '../config/header.php'; 

// --- BANK INFORMATION ---
$bank_name = "ACB";
$account_number = "5385268";
$account_holder = "BUI DUC CONG";
$bank_id_for_qr = "970416";
// ------------------------------------

// Get user info from database
$user_id = $user['id'];
$stmt = $mysqli->prepare("
    SELECT sodu, ma_giao_dich,
           COALESCE((SELECT SUM(amount) FROM Transactions WHERE user_id = ? AND type = 'deposit'), 0) as total_deposit
    FROM Users WHERE id = ?
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$current_balance = $userData['sodu'] ?? 0;
$deposit_code = $userData['ma_giao_dich'];
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

// Create QR code link
$qr_code_url = "https://api.vietqr.io/image/{$bank_id_for_qr}-{$account_number}-print.png?addInfo=" . urlencode($deposit_code) . "&accountName=" . urlencode($account_holder);

// Multi-language
$texts = [
    'vi' => [
        'title' => 'Nạp Tiền Vào Tài Khoản',
        'subtitle' => 'Chuyển khoản nhanh chóng và an toàn với QR Code',
        'bank' => 'Ngân hàng',
        'account_number' => 'Số tài khoản',
        'account_holder' => 'Chủ tài khoản',
        'transfer_content' => 'Nội dung chuyển khoản',
        'your_code' => 'Mã giao dịch của bạn',
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
        'note2' => 'Số tiền tối thiểu: $1 USD = 28.000VND',
        'note3' => 'Thời gian xử lý: 1-5 phút',
        'note4' => 'Liên hệ hỗ trợ nếu sau 10 phút chưa nhận được tiền',
        'balance' => 'Số dư hiện tại',
        'history_title' => 'Lịch sử giao dịch',
        'no_history' => 'Chưa có giao dịch nào',
        'amount' => 'Số tiền',
        'time' => 'Thời gian',
        'description' => 'Mô tả',
        'bank_info' => 'Thông tin ngân hàng',
        'support_hint' => 'Nếu gặp vấn đề, vui lòng liên hệ hỗ trợ với mã giao dịch của bạn',
        'member_rank' => 'Hạng Thành Viên',
        'your_rank' => 'Hạng của bạn',
        'view_ranks' => 'Xem chi tiết',
        'modal_title' => 'Hạng Thành Viên',
        'total_deposited' => 'Tổng Tiền Nạp',
        'bonus_rate' => '% Tiền Thưởng',
        'close' => 'Đóng'
    ],
    'en' => [
        'title' => 'Deposit to Account',
        'subtitle' => 'Fast and secure transfer with QR Code',
        'bank' => 'Bank',
        'account_number' => 'Account Number',
        'account_holder' => 'Account Holder',
        'transfer_content' => 'Transfer Content',
        'your_code' => 'Your Transaction Code',
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
        'note2' => 'Minimum amount: $1 USD = 28.000VND',
        'note3' => 'Processing time: 1-5 minutes',
        'note4' => 'Contact support if not received after 10 minutes',
        'balance' => 'Current Balance',
        'history_title' => 'Transaction History',
        'no_history' => 'No transactions yet',
        'amount' => 'Amount',
        'time' => 'Time',
        'description' => 'Description',
        'bank_info' => 'Bank Information',
        'support_hint' => 'If you have issues, contact support with your transaction code',
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
    :root {
        --deposit-primary: #10b981;
        --deposit-primary-dark: #059669;
        --deposit-secondary: #34d399;
    }

    .page-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
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

    /* Balance Card - Floating */
    .balance-card {
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 16px;
        padding: 25px 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .balance-card::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .balance-label {
        font-size: 14px;
        opacity: 0.95;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }

    .balance-amount {
        font-size: 42px;
        font-weight: 800;
        letter-spacing: 1px;
        position: relative;
        z-index: 1;
    }

    /* Rank Badge on Balance Card */
    .rank-badge-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
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
}

.rank-percent {
    opacity: 0.8;
    font-size: 12px;
}


    .rank-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .rank-badge i {
        font-size: 16px;
    }

    .btn-view-ranks {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
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
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
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
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        background: linear-gradient(135deg, #10b981, #059669);
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
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
        border-radius: 10px;
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

    .ranks-table tbody tr {
        transition: all 0.2s;
    }

    .ranks-table tbody tr:hover {
        background: linear-gradient(to right, rgba(16, 185, 129, 0.05), transparent);
    }

    .ranks-table tbody tr.current-rank {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.1));
        border-left: 4px solid #10b981;
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
        background: linear-gradient(135deg, #10b981, #059669);
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
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15));
        color: #10b981;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 16px;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    /* Main Grid Layout */
    .deposit-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 25px;
        margin-bottom: 30px;
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
        color: var(--deposit-primary);
        font-size: 22px;
    }

    /* QR Code Section - Featured */
    .qr-section {
        text-align: center;
        padding: 30px;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
        border-radius: 16px;
        border: 2px dashed var(--deposit-primary);
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

    /* Bank Info Grid */
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
        border-color: var(--deposit-primary);
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

    /* Transfer Code Section - Prominent */
    .transfer-code-section {
        background: linear-gradient(135deg, #10b981, #059669);
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

    .btn-copy:active {
        transform: scale(0.98);
    }

    /* Alert Box */
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

    /* Instructions */
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
        border-color: var(--deposit-primary);
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }

    .instruction-number {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #10b981, #059669);
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

    /* Notes Section */
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
        color: var(--deposit-primary);
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .note-item span {
        font-size: 14px;
        color: var(--text-color);
        line-height: 1.6;
    }

    /* Transaction History */
    .history-section {
        background-color: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
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
        color: var(--deposit-primary);
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
        color: var(--deposit-primary);
    }

    .empty-history p {
        font-size: 15px;
        margin: 0;
        font-weight: 500;
    }

    /* Info Hint */
    .info-hint {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-left: 4px solid #3b82f6;
        padding: 15px 18px;
        border-radius: 10px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        font-size: 13px;
        color: var(--text-color);
        line-height: 1.6;
    }

    html.dark .info-hint {
        background: linear-gradient(135deg, #1e3a5f, #2d4a6f);
    }

    .info-hint i {
        color: #3b82f6;
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .deposit-grid {
            grid-template-columns: 1fr;
        }
        
        .instructions-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .page-header {
            padding: 20px;
        }
        
        .page-header h1 {
            font-size: 22px;
        }
        
        .balance-amount {
            font-size: 32px;
        }
        
        .transfer-code-display {
            font-size: 24px;
            letter-spacing: 2px;
        }
        
        .deposit-card {
            padding: 20px;
        }
        
        .qr-section {
            padding: 20px;
        }
        
        .qr-section img {
            max-width: 240px;
        }

        .history-table {
            font-size: 13px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
        }

        .modal-content {
            width: 95%;
            max-height: 90vh;
        }

        .modal-body {
            padding: 20px;
        }

        .ranks-table {
            font-size: 12px;
        }

        .ranks-table th,
        .ranks-table td {
            padding: 12px 10px;
        }

        .rank-name-cell {
            font-size: 14px;
        }
    }

    @media (max-width: 576px) {
        .bank-info-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .history-table {
            display: block;
            overflow-x: auto;
        }

        .history-table thead {
            display: none;
        }

        .history-table tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
        }

        .history-table td {
            display: block;
            text-align: right;
            padding: 8px 0;
            border: none;
        }

        .history-table td::before {
            content: attr(data-label);
            float: left;
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
        }

        .rank-badge-container {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
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

<!-- Balance Card with Rank -->
<div class="balance-card">
    <div class="balance-label"><?php echo $t['balance']; ?></div>
    <div class="balance-amount">$<?php echo number_format($current_balance, 2, '.', ','); ?></div>
    
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

</div>

<!-- Main Grid -->
<div class="deposit-grid">
    <!-- Left Column: Bank Info -->
    <div>
        <div class="deposit-card">
            <h3 class="card-title">
                <i class="bi bi-bank2"></i>
                <?php echo $t['bank_info']; ?>
            </h3>

            <!-- Bank Information Grid -->
            <div class="bank-info-grid">
                <div class="bank-info-item">
                    <span class="bank-info-label"><?php echo $t['bank']; ?></span>
                    <span class="bank-info-value"><?php echo htmlspecialchars($bank_name); ?></span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label"><?php echo $t['account_number']; ?></span>
                    <span class="bank-info-value"><?php echo htmlspecialchars($account_number); ?></span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label"><?php echo $t['account_holder']; ?></span>
                    <span class="bank-info-value"><?php echo htmlspecialchars($account_holder); ?></span>
                </div>
            </div>

            <!-- Transfer Code Section -->
            <div class="transfer-code-section">
                <div class="transfer-code-label"><?php echo $t['transfer_content']; ?></div>
                <div class="transfer-code-display" id="depositCode"><?php echo htmlspecialchars($deposit_code); ?></div>
                <button class="btn-copy" id="copyBtn">
                    <i class="bi bi-clipboard-check"></i>
                    <span id="copyText"><?php echo $t['copy']; ?></span>
                </button>
            </div>

            <!-- Warning Alert -->
            <div class="alert-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div class="alert-content">
                    <h4><?php echo $t['warning_title']; ?></h4>
                    <p><?php echo $t['warning_text']; ?></p>
                </div>
            </div>

            <!-- Instructions -->
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

            <!-- Notes -->
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

            <!-- Info Hint -->
            <div class="info-hint" style="margin-top: 20px;">
                <i class="bi bi-lightbulb-fill"></i>
                <p><?php echo $t['support_hint']; ?></p>
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

<!-- Rank Modal -->
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
// Copy button functionality
document.getElementById('copyBtn').addEventListener('click', function() {
    const code = document.getElementById('depositCode').innerText;
    const copyText = document.getElementById('copyText');
    const icon = this.querySelector('i');
    
    navigator.clipboard.writeText(code).then(() => {
        copyText.textContent = '<?php echo $t['copied']; ?>';
        icon.className = 'bi bi-check-circle-fill';
        this.style.background = 'rgba(16, 185, 129, 0.3)';
        this.style.borderColor = 'rgba(255, 255, 255, 0.6)';
        
        setTimeout(() => {
            copyText.textContent = '<?php echo $t['copy']; ?>';
            icon.className = 'bi bi-clipboard-check';
            this.style.background = 'rgba(255, 255, 255, 0.2)';
            this.style.borderColor = 'rgba(255, 255, 255, 0.3)';
        }, 2000);
    }).catch(err => {
        console.error('Copy failed:', err);
        alert('<?php echo $lang === 'vi' ? 'Lỗi sao chép!' : 'Copy failed!'; ?>');
    });
});

// Modal functions
function openRankModal() {
    document.getElementById('rankModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeRankModal(event) {
    if (!event || event.target.id === 'rankModal') {
        document.getElementById('rankModal').classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRankModal();
    }
});
</script>

<script>
// Auto refresh balance every 10 seconds
setInterval(() => {
  fetch(window.location.href)
    .then(r => r.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newBalance = doc.querySelector('.balance-amount')?.textContent;
      if (newBalance) {
        document.querySelector('.balance-amount').textContent = newBalance;
      }
    })
    .catch(err => console.error('Balance refresh error:', err));
}, 10000); // 10s
</script>


<?php require_once '../config/footer.php'; ?>