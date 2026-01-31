<?php
$page_title = 'Mua Proxy - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

// Get user credits & Check API Key
$user_credits = 0;
$has_api_key = false;

if (isset($_SESSION['Users']) && isset($mysqli)) {
    $user_identity = $_SESSION['Users'];
    $stmt = $mysqli->prepare("SELECT credits, apikey FROM Users WHERE taikhoan = ? OR google_id = ?");
    $stmt->bind_param("ss", $user_identity, $user_identity);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $user_credits = $result['credits'] ?? 0;
        if (!empty($result['apikey'])) {
            $has_api_key = true;
        }
    }
    $stmt->close();
}
?>
<style>
/* ========================================
   NÚT LÀM MỚI
   ======================================== */
.btn-refresh {
    background: transparent;
    border: 1px solid #333;
    color: #888;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
    margin-left: auto;
}

.btn-refresh:hover {
    border-color: #667eea;
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.btn-refresh:active {
    transform: scale(0.95);
}

.btn-refresh.spinning i {
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.history-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.btn-check-all,
.bulk-actions {
    flex-shrink: 0;
}

.btn-refresh {
    margin-left: auto;
}

/* ========================================
   DELETE MODAL
   ======================================== */
.modal-overlay-delete {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: fadeIn 0.2s;
}

.modal-content-delete {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 16px;
    padding: 32px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    animation: slideUp 0.3s;
}

.modal-header-delete {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #333;
}

.modal-header-delete h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.modal-body-delete {
    margin-bottom: 24px;
    color: #ccc;
    line-height: 1.6;
}

.modal-body-delete p {
    margin: 0 0 8px 0;
    font-size: 15px;
}

.modal-body-delete strong {
    color: #ef4444;
    font-weight: 600;
}

.modal-actions-delete {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-cancel-delete,
.btn-confirm-delete-action {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel-delete {
    background: transparent;
    border: 1px solid #444;
    color: #aaa;
}

.btn-cancel-delete:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #666;
    color: #fff;
}

.btn-confirm-delete-action {
    background: #ef4444;
    color: #fff;
}

.btn-confirm-delete-action:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-confirm-delete-action:active {
    transform: translateY(0);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
/* Wrapper để giữ vị trí menu */
.copy-wrapper {
    position: relative;
    display: inline-block;
}

/* Menu chọn Copy */
.copy-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 5px); /* Đẩy xuống dưới nút 1 chút */
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    width: 130px;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
    
    /* Mặc định ẩn */
    opacity: 0;
    visibility: hidden;
    transform: translateY(-5px);
    transition: all 0.2s ease;
}

/* Khi có class show thì hiện lên */
.copy-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Từng dòng lựa chọn */
.copy-option {
    padding: 10px 12px;
    font-size: 12px;
    color: #ccc;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    gap: 8px;
}

.copy-option:last-child {
    border-bottom: none;
}

.copy-option:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.copy-option i {
    font-size: 14px;
    color: #fbbf24; /* Màu vàng icon */
}</style>
<link rel="stylesheet" href="/pages/AI/css/proxy.css?v=<?php echo time(); ?>">

<div class="dub-container">
    <!-- LEFT PANEL -->
    <div class="settings-panel">
        <div class="panel-header">
            <div class="panel-title">
                <i class="bi bi-shield-lock-fill"></i>
                Mua Proxy
            </div>
            <div class="credits-badge">
                <i class="bi bi-lightning-charge-fill"></i>
                <span id="userCredits"><?php echo number_format($user_credits); ?></span>
            </div>
        </div>

        <div class="alert-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Lưu ý quan trọng:</strong>
                <ul>
                    <li>Proxy hy hữu có thể hết hạn sớm hơn dự kiến</li>
                    <li>Nếu số lượng trả ra không đủ, bạn sẽ được hoàn.</li>
                    <li>Proxy hỗ trợ SOCKS5 và HTTP.</li>
                    <li>Proxy không giới hạn Băng thông.</li>
                    <li>Proxy là proxy dân cư, độ tin cậy cao.</li>
                    <li>Proxy sống tối đa 30 phút.</li>
                </ul>
            </div>
        </div>

        <!-- TTL Selection -->
        <!-- TTL Selection - ĐẶT PHÍA DƯỚI Quantity -->
<div class="form-group">
    <label class="lang-label">
        Số lượng Proxy <span class="required">*</span>
    </label>
    <div class="quantity-control">
        <button class="btn-qty" onclick="updateQuantity(-1)">
            <i class="bi bi-dash"></i>
        </button>
        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="100" onchange="updateCost()">
        <button class="btn-qty" onclick="updateQuantity(1)">
            <i class="bi bi-plus"></i>
        </button>
    </div>
    <div class="qty-hint">Tối thiểu 1, tối đa 100 proxy</div>
</div>

<!-- TTL Selection - ĐẶT SAU Quantity để dropdown không bị đè -->
<div class="form-group" style="z-index: 50;">
    <label class="lang-label">
        Thời gian sống (TTL) <span class="required">*</span>
    </label>
    <div class="custom-dropdown" id="dropdownTTL">
    <div class="dropdown-trigger" onclick="toggleDropdown('dropdownTTL')">
        <span id="ttlLabel">> 5 phút (1.250 credits)</span>
        <i class="bi bi-chevron-down"></i>
    </div>
    
    <div class="dropdown-menu" id="ttlList">
        <div class="dropdown-item selected" onclick="selectTTL(5, '> 5 phút (1.250 credits)', this)">
            > 5 phút (1.250 credits)
        </div>
        
        <div class="dropdown-item" onclick="selectTTL(10, '> 10 phút (2.500 credits)', this)">
            > 10 phút (2.500 credits)
        </div>
    </div>
</div>
    <input type="hidden" id="ttlVal" value="5">
</div>
        <!-- Cost Display -->
        <div class="cost-display">
            <div class="cost-row">
                <span class="cost-label">Chi phí dự tính:</span>
                <span class="cost-value" id="estimatedCost">1,250</span>
                <span class="cost-unit">credits</span>
            </div>
            <div class="cost-breakdown">
                <span id="costBreakdown">1,250 credits × 1 proxy</span>
            </div>
        </div>

        <!-- Purchase Button -->
        <button onclick="purchaseProxy()" id="btnPurchase" class="btn-dub">
            <i class="bi bi-cart-check-fill"></i>
            <span>Mua Proxy</span>
        </button>
    </div>

    <!-- RIGHT PANEL -->
    <div class="history-panel">
        <div class="history-header">
    <div class="history-actions">
        <!-- Nút Chọn tất cả (giữ nguyên) -->
        <button class="btn-check-all" onclick="toggleCheckAll()" id="btnCheckAll">
            <i class="bi bi-square"></i>
            <span>Chọn tất cả (<span id="selectedCount">0</span>)</span>
        </button>
        
        <!-- Bulk Actions (giữ nguyên) -->
        <div class="bulk-actions" id="bulkActions">
            <div class="separator"></div>
            
            <button class="btn-bulk" onclick="bulkCopyByType('HTTP')" title="Copy HTTP">
                <i class="bi bi-globe"></i>
                <span>HTTP</span>
            </button>

            <button class="btn-bulk" onclick="bulkCopyByType('SOCKS5')" title="Copy SOCKS5">
                <i class="bi bi-hdd-network"></i>
                <span>SOCKS5</span>
            </button>

            <button class="btn-bulk delete" onclick="bulkDelete()" title="Xóa">
                <i class="bi bi-trash"></i>
                <span>Xóa</span>
            </button>
        </div>
        
        <!-- 🔥 NÚT MỚI: LÀM MỚI -->
        <button class="btn-refresh" onclick="refreshHistory()" id="btnRefresh" title="Làm mới danh sách">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

        <div id="historyList" class="history-list">
            <div class="history-empty">
                <i class="bi bi-shield-lock"></i>
                <span>Chưa có proxy nào</span>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<!-- Modal hiển thị chi tiết Proxy -->
<div id="proxyModal" class="custom-modal">
    <div class="modal-overlay" onclick="closeProxyModal()"></div>
    <div class="modal-content proxy-modal">
        <div class="modal-header">
            <h3>Chi tiết Proxy</h3>
            <span class="close-modal" onclick="closeProxyModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div class="proxy-detail-section">
                <label>HTTP Proxy:</label>
                <div class="proxy-value-box">
                    <code id="httpProxy"></code>
                    <button class="btn-copy-inline" onclick="copyText('httpProxy')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="proxy-detail-section">
                <label>SOCKS5 Host:</label>
                <div class="proxy-value-box">
                    <code id="socks5Host"></code>
                    <button class="btn-copy-inline" onclick="copyText('socks5Host')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="proxy-detail-section">
                <label>SOCKS5 Port:</label>
                <div class="proxy-value-box">
                    <code id="socks5Port"></code>
                    <button class="btn-copy-inline" onclick="copyText('socks5Port')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="proxy-detail-section">
                <label>Username:</label>
                <div class="proxy-value-box">
                    <code id="socks5User"></code>
                    <button class="btn-copy-inline" onclick="copyText('socks5User')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="proxy-detail-section">
                <label>Password:</label>
                <div class="proxy-value-box">
                    <code id="socks5Pass"></code>
                    <button class="btn-copy-inline" onclick="copyText('socks5Pass')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="proxy-meta">
                <div class="meta-item">
                    <i class="bi bi-clock"></i>
                    <span>Hết hạn: <strong id="expiresAt"></strong></span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span>Chi phí: <strong id="creditCost"></strong> credits</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 🔥 DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay-delete" style="display: none;">
    <div class="modal-content-delete">
        <div class="modal-header-delete">
            <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 32px;"></i>
            <h3>Xác nhận xóa</h3>
        </div>
        <div class="modal-body-delete">
            <p>Bạn có chắc chắn muốn xóa <strong id="deleteCount">0</strong> proxy đã chọn?</p>
            <p style="color: #888; font-size: 14px; margin-top: 8px;">
                Hành động này không thể hoàn tác.
            </p>
        </div>
        <div class="modal-actions-delete">
            <button class="btn-cancel-delete" onclick="closeDeleteModal()">
                <i class="bi bi-x-circle"></i>
                <span>Hủy</span>
            </button>
            <button class="btn-confirm-delete-action" onclick="confirmDelete()">
                <i class="bi bi-trash"></i>
                <span>Xóa</span>
            </button>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const hasApiKey = <?php echo $has_api_key ? 'true' : 'false'; ?>;
</script>

<script src="/pages/AI/js/proxy.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>