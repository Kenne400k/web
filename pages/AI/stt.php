<?php
$page_title = 'Chuyển giọng nói thành văn bản - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

// Khởi tạo giá trị mặc định
$user_credits = 0;

// Kiểm tra session và kết nối DB
if (isset($_SESSION['Users']) && isset($mysqli)) {
    $user_identity = $_SESSION['Users'];

    $stmt = $mysqli->prepare("SELECT credits3 FROM Users WHERE taikhoan = ? OR google_id = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $user_identity, $user_identity);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $user_credits = intval($result['credits3']);
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="/pages/AI/css/stt.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/pages/AI/css/stt-light-theme.css?v=<?php echo time(); ?>">
<style>
/* ========================================
   DELETE MODAL
   ======================================== */
.modal-overlay {
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

.modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 16px;
    padding: 32px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    animation: slideUp 0.3s;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #333;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.modal-body {
    margin-bottom: 24px;
    color: #ccc;
    line-height: 1.6;
}

.modal-body p {
    margin: 0 0 8px 0;
    font-size: 15px;
}

.modal-body strong {
    color: #ef4444;
    font-weight: 600;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-cancel,
.btn-confirm-delete {
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

.btn-cancel {
    background: transparent;
    border: 1px solid #444;
    color: #aaa;
}

.btn-cancel:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #666;
    color: #fff;
}

.btn-confirm-delete {
    background: #ef4444;
    color: #fff;
}

.btn-confirm-delete:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-confirm-delete:active {
    transform: translateY(0);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
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
    margin-left: auto; /* Đẩy sang bên phải */
}

.btn-refresh:hover {
    border-color: #667eea;
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.btn-refresh:active {
    transform: scale(0.95);
}

/* Animation xoay khi đang refresh */
.btn-refresh.spinning i {
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Disable state */
.btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Fix layout để nút Refresh nằm đúng vị trí */
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
}</style>
<div class="stt-container">
    <!-- LEFT PANEL -->
    <div class="settings-panel">
        <div class="alert-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Lưu ý quan trọng:</strong>
                <ul>
                    <li>Hỗ trợ file audio: MP3, AAC, WAV</li>
                    <li>Kích thước tối đa: 200MB</li>
                    <li>Kết quả trả về: SRT subtitle</li>
                </ul>
            </div>
        </div>

        <div class="upload-zone-wrap">
    <label class="upload-label">
        Tệp audio <span class="required">*</span>
    </label>

    <input type="file" id="audioInput" accept=".mp3,.aac,.wav" style="display: none;" onchange="handleFile(this)" multiple>

    <div class="upload-zone" id="uploadZone" onclick="$('#audioInput').click()">
        <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
        <div class="upload-title">Nhấp hoặc kéo thả file vào đây</div>
        <div class="upload-desc">
            Định dạng hỗ trợ: MP3, AAC, WAV<br>Trả ra: SRT subtitle
        </div>
    </div>

    <!-- DANH SÁCH FILE ĐÃ CHỌN -->
    <div id="fileListContainer" style="display: none;">
        <div class="file-list-header">
            <span class="file-count">Đã chọn: <span id="fileCount">0</span> file</span>
            <div class="file-list-actions">
                <button class="btn-add-more" onclick="$('#audioInput').click()" title="Thêm file">
                    <i class="bi bi-plus-circle"></i>
                    <span>Thêm file</span>
                </button>
                <button class="btn-clear-all" onclick="clearAllFiles()" title="Xóa hết">
                    <i class="bi bi-trash"></i>
                    <span>Xóa hết</span>
                </button>
            </div>
        </div>
        
        <div id="fileList" class="file-list">
            <!-- Danh sách file sẽ được render ở đây -->
        </div>
    </div>
</div>

        <div class="bottom-actions">
    <div class="cost-display">
        <span class="cost-label">Chi phí dự kiến</span>
        <span class="cost-amount">
            <span id="estimatedCost">~0</span> 
            <span style="color: #888; font-weight: 400; margin: 0 6px;">/</span>
            
            <span id="userCreditsDisplay" style="color: #22c55e;">
                <?php echo number_format($user_credits, 0, ',', '.'); ?>
            </span>
        </span>
    </div>

    <button onclick="startTranscription()" id="btnSTT" class="btn-stt">
        <i class="bi bi-play-circle-fill"></i>
        <span>Bắt đầu chuyển đổi</span>
    </button>
</div>

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
        
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <div class="separator"></div>
            <button class="btn-bulk" onclick="bulkDownloadSRT()" title="Tải xuống SRT">
                <i class="bi bi-file-text"></i>
                <span>Tải SRT</span>
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
                <i class="bi bi-inbox"></i>
                <span>Chưa có tác vụ nào</span>
            </div>
        </div>
    </div>
</div>

<!-- 🔥 DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 32px;"></i>
            <h3>Xác nhận xóa</h3>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn xóa <strong id="deleteCount">0</strong> task đã chọn?</p>
            <p style="color: #888; font-size: 14px; margin-top: 8px;">
                Hành động này không thể hoàn tác.
            </p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">
                <i class="bi bi-x-circle"></i>
                <span>Hủy</span>
            </button>
            <button class="btn-confirm-delete" onclick="confirmDelete()">
                <i class="bi bi-trash"></i>
                <span>Xóa</span>
            </button>
        </div>
    </div>
</div>

<audio id="sttAudio" style="display: none;"></audio>

<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const userCreditsFromDB = <?php echo $user_credits; ?>;
</script>

<script src="/pages/AI/js/stt.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>