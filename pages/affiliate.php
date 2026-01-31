<?php
$page_title = 'Tiếp thị liên kết - Studio';
require_once '../config/header.php';
require_once '../config/sidebar.php';

// Get user info
$user_id = 0;
$user_ref_code = '';
$user_credits = 0;
$user_email = '';
$user_name = '';

if (isset($_SESSION['Users']) && isset($mysqli)) {
    $user_identity = $_SESSION['Users'];
    // Thêm bank_name, bank_account, bank_owner vào SELECT
$stmt = $mysqli->prepare("SELECT id, ref_code, credits, email, taikhoan, bank_name, bank_account, bank_owner FROM Users WHERE taikhoan = ? OR google_id = ?");
    $stmt->bind_param("ss", $user_identity, $user_identity);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $user_id = $result['id'];
        $user_ref_code = $result['ref_code'];
        $user_credits = $result['credits'] ?? 0;
        $user_email = $result['email'];
        $user_name = $result['taikhoan'];
        $bank_name = $result['bank_name'] ?? '';
    $bank_account = $result['bank_account'] ?? '';
    $bank_owner = $result['bank_owner'] ?? '';
    }
    $stmt->close();
}

// Get affiliate stats từ database
require_once '../config/affiliate_config.php';
$affiliate = new AffiliateSystem($mysqli);
$stats = $affiliate->getUserStats($user_id);
$balance = $affiliate->getWithdrawableBalance($user_id);

// Lấy số người giới thiệu thành công
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM Users WHERE referred_by = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_referrals = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>

<link rel="stylesheet" href="/pages/AI/css/affiliate.css?v=<?php echo time(); ?>">

<div class="affiliate-container">
    <!-- LEFT PANEL - Thông tin & Rút tiền -->
    <div class="info-panel">
        <!-- Số dư hoa hồng -->
        <div class="balance-card">
            <div class="balance-label">Doanh thu</div>
            <div class="balance-amount" id="availableBalance"><?php echo number_format($balance['available'], 0); ?> USD</div>
            
            <div class="balance-details">
                <div class="detail-item">
                    <span>Thông tin thanh toán</span>
                    <button class="btn-link" onclick="openPaymentInfo()">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn-edit" onclick="openPaymentInfo()" title="Sửa thông tin">
                    <i class="bi bi-pencil"></i>
                    <span>Sửa</span>
                </button>
                <button class="btn-withdraw" onclick="withdrawMoney()" title="Rút tiền">
                    <i class="bi bi-wallet2"></i>
                    <span>Rút tiền</span>
                </button>
            </div>
        </div>

        <!-- Tiếp thị liên kết -->
        <div class="referral-card">
            <div class="card-title">Tiếp thị liên kết</div>
            <div class="card-desc">
                Nhận 10% doanh thu chia sẻ của người được giới thiệu mua tín dụng cao cấp (mọi giao dịch). Trường hợp phát hiện làm dụng chúng tôi sẽ khóa các tài khoản liên quan.
            </div>
            <a href="#" class="learn-more">Bạn đã giới thiệu được <?php echo $total_referrals; ?> khách hàng tiềm năng.</a>
            
            <div class="referral-link-box">
                <label class="link-label">Liên kết tiếp thị</label>
                <div class="link-input-wrap">
                    <input 
                        type="text" 
                        id="refLink" 
                        class="link-input" 
                        value="https://kingcongstudio.com/auth/register?ref=<?php echo $user_ref_code; ?>"
                        readonly
                    >
                    <button class="btn-copy" onclick="copyRefLink()" title="Sao chép">
                        <i class="bi bi-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL - Lịch sử hoa hồng -->
    <div class="history-panel">
        <div class="history-header">
            <div class="history-tabs">
                <button class="tab-btn active" data-tab="commissions" onclick="switchTab('commissions')">
                    Lịch sử hoa hồng
                </button>
                <button class="tab-btn" data-tab="withdrawals" onclick="switchTab('withdrawals')">
                    Lịch sử rút tiền
                </button>
            </div>
        </div>

        <!-- Tab: Lịch sử hoa hồng -->
        <div class="tab-content active" id="tabCommissions">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Số tiền</th>
                        <th>Hoa hồng</th>
                    </tr>
                </thead>
                <tbody id="commissionsList">
                    <tr class="empty-row">
                        <td colspan="3">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="pagination" id="commissionsPagination">
                <span class="page-info">Trang 1 trên 0 (0 kết quả)</span>
                <div class="page-buttons">
                    <button class="btn-page" disabled>
                        <i class="bi bi-chevron-left"></i> Trước
                    </button>
                    <button class="btn-page" disabled>
                        Sau <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab: Lịch sử rút tiền -->
        <div class="tab-content" id="tabWithdrawals">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Số tiền</th>
                        <th>Hoa hồng</th>
                    </tr>
                </thead>
                <tbody id="withdrawalsList">
                    <tr class="empty-row">
                        <td colspan="3">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="pagination" id="withdrawalsPagination">
                <span class="page-info">Trang 1 trên 0 (0 kết quả)</span>
                <div class="page-buttons">
                    <button class="btn-page" disabled>
                        <i class="bi bi-chevron-left"></i> Trước
                    </button>
                    <button class="btn-page" disabled>
                        Sau <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thông tin thanh toán -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thông tin thanh toán</h3>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Vui lòng nhập thông tin ngân hàng để nhận tiền khi rút.</p>
            
            <div class="form-group">
    <label>Tên ngân hàng</label>
    <input type="text" id="bankName" class="form-input" placeholder="VD: Vietcombank" value="<?php echo htmlspecialchars($bank_name); ?>">
</div>

<div class="form-group">
    <label>Số tài khoản</label>
    <input type="text" id="bankAccount" class="form-input" placeholder="VD: 1234567890" value="<?php echo htmlspecialchars($bank_account); ?>">
</div>

<div class="form-group">
    <label>Tên chủ tài khoản</label>
    <input type="text" id="bankOwner" class="form-input" placeholder="VD: NGUYEN VAN A" value="<?php echo htmlspecialchars($bank_owner); ?>">
</div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closePaymentModal()">Hủy</button>
            <button class="btn-save" onclick="savePaymentInfo()">Lưu</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const userId = <?php echo $user_id; ?>;
    const refCode = '<?php echo $user_ref_code; ?>';
</script>
<script src="/pages/AI/js/affiliate.js?v=<?php echo time(); ?>"></script>

<?php require_once '../config/footer.php'; ?>