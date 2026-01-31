<?php
$page_title = 'Lồng tiếng tự động - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

// Get user credits & Check API Key
$user_credits = 0;
$has_api_key = false; // Mặc định là false

if (isset($_SESSION['Users']) && isset($mysqli)) {
    $user_identity = $_SESSION['Users'];
    // 🔥 Sửa câu Query để lấy thêm apikey
    $stmt = $mysqli->prepare("SELECT credits, apikey FROM Users WHERE taikhoan = ? OR google_id = ?");
    $stmt->bind_param("ss", $user_identity, $user_identity);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $user_credits = $result['credits'] ?? 0;
        // Kiểm tra xem có API key không
        if (!empty($result['apikey'])) {
            $has_api_key = true;
        }
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="/pages/AI/css/dubbing.css?v=<?php echo time(); ?>">
<style>/* ========================================
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
}
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
</style>
<div class="dub-container">
    <!-- LEFT PANEL -->
    <div class="settings-panel">
        <div class="alert-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Lưu ý quan trọng:</strong>
                <ul>
                    <li>Phiên bản thử nghiệm, giới hạn 20,000 tín dụng đầu vào. (phù hợp video dưới 5 phút - Youtube Shorts / Tiktok, video dài hãy chia video thành nhiều phần)</li>
                </ul>
            </div>
        </div>

        <div class="lang-row">
    <div class="lang-group">
        <label class="lang-label">
            Ngôn ngữ nguồn
        </label>
        <div class="custom-dropdown" id="dropdownSource">
            <div class="dropdown-trigger" onclick="toggleDropdown('dropdownSource')">
                <span id="sourceLangLabel">Tự xác định</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="dropdown-menu" id="sourceLangList">
            </div>
        </div>
        <input type="hidden" id="sourceLangVal" value="Auto">

        <div class="speaker-group" style="margin-top: 16px;">
            <label class="lang-label">Số lượng người nói</label>
            <div class="custom-dropdown" id="dropdownSpeakers">
                <div class="dropdown-trigger" onclick="toggleDropdown('dropdownSpeakers')">
                    <span id="numSpeakersLabel">Tự động phát hiện</span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                
                <div class="dropdown-menu" id="speakerList">
                </div>
            </div>
            <input type="hidden" id="numSpeakers" value="0">
        </div>
    </div>

    <div class="lang-arrow">
        <i class="bi bi-arrow-right"></i>
    </div>

    <div class="lang-group">
        <label class="lang-label">
            Ngôn ngữ đích <span class="required">*</span>
        </label>
        <div class="custom-dropdown" id="dropdownTarget">
            <div class="dropdown-trigger" onclick="toggleDropdown('dropdownTarget')">
                <span id="targetLangLabel">Chọn ngôn ngữ</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="dropdown-menu" id="targetLangList">
            </div>
        </div>
        <input type="hidden" id="targetLangVal" value="">
    </div>
</div>

        <div class="upload-zone-wrap">
    <label class="upload-label">
        Tệp audio <span class="required">*</span>
    </label>

    <input type="file" id="videoInput" accept=".mp3,.m4a,.wav" style="display: none;" onchange="handleFile(this)">

    <div class="upload-zone" id="uploadZone" onclick="$('#videoInput').click()">
        <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
        <div class="upload-title">Nhấp hoặc kéo thả file vào đây</div>
        <div class="upload-desc">
        Tối đa 20MB hoặc 5 phút<br>Hiện chỉ hỗ trợ: .m4a, .mp3<br>Nếu tệp của bạn khác, vui lòng chuyển đổi thủ công.
        </div>
    </div>

    <div id="filePreviewContainer" class="file-preview-card" style="display: none;">
        <div class="file-row">
            <div class="file-icon-box">
                <i class="bi bi-volume-up-fill"></i>
            </div>
            <div class="file-details">
                <div class="file-name" id="previewFileName">filename.mp3</div>
                <div class="file-size" id="previewFileSize">0.00 MB</div>
            </div>
            <button class="btn-trash" onclick="resetFile(event)">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <audio id="audioPlayer" controls class="custom-audio-player">
            Trình duyệt của bạn không hỗ trợ phát audio.
        </audio>
    </div>
</div>

        <div class="bottom-actions">
    <div class="toggle-group">
        
        <div class="toggle-control">
            <label class="toggle-switch">
                <input type="checkbox" id="disableCloning">
                <span class="toggle-slider"></span>
            </label>
            <label class="toggle-label" for="disableCloning">Tắt nhân bản giọng</label>
        </div>

        <div class="tooltip-popup">
            [Thử nghiệm] Thay vì sử dụng giọng nhân bản, sẽ dùng giọng tương tự từ Thư viện giọng ElevenLabs.
    </div>
    </div>

    <button type="button" id="btnStart" class="btn-dub">
        <i class="bi bi-play-circle-fill"></i>
        <span>Bắt đầu</span>
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
        
        <!-- Bulk Actions (giữ nguyên) -->
        <div class="bulk-actions" id="bulkActions">
            <div class="separator"></div>
            <button class="btn-bulk" onclick="bulkDownloadAudio()" title="Tải xuống Audio">
                <i class="bi bi-download"></i>
                <span>Tải Audio</span>
            </button>
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

        <div class="filter-tabs" id="filterTabs">
            <button class="filter-tab active" data-status="all" onclick="filterHistory('all')">
                Tất cả
            </button>
            <button class="filter-tab" data-status="done" onclick="filterHistory('done')">
                Hoàn thành
            </button>
            <button class="filter-tab" data-status="pending" onclick="filterHistory('pending')">
                Đang xử lý
            </button>
            <button class="filter-tab" data-status="failed" onclick="filterHistory('failed')">
                Thất bại
            </button>
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

<audio id="dubAudio" style="display: none;"></audio>

<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Pass API Key check từ PHP sang JS
    const hasApiKey = <?php echo $has_api_key ? 'true' : 'false'; ?>;

    // Dữ liệu ngôn ngữ (Khai báo ở đây để dùng chung)
    const languageData = [
        { val: "English", label: "🇺🇸 English" },
        { val: "Vietnamese", label: "🇻🇳 Vietnamese" },
        { val: "Arabic", label: "🇸🇦 Arabic" },
        { val: "Cantonese", label: "🇭🇰 Cantonese" },
        { val: "Chinese", label: "🇨🇳 Chinese (Mandarin)" },
        { val: "Dutch", label: "🇳🇱 Dutch" },
        { val: "French", label: "🇫🇷 French" },
        { val: "German", label: "🇩🇪 German" },
        { val: "Indonesian", label: "🇮🇩 Indonesian" },
        { val: "Italian", label: "🇮🇹 Italian" },
        { val: "Japanese", label: "🇯🇵 Japanese" },
        { val: "Korean", label: "🇰🇷 Korean" },
        { val: "Portuguese", label: "🇵🇹 Portuguese" },
        { val: "Russian", label: "🇷🇺 Russian" },
        { val: "Spanish", label: "🇪🇸 Spanish" },
        { val: "Turkish", label: "🇹🇷 Turkish" },
        { val: "Ukrainian", label: "🇺🇦 Ukrainian" },
        { val: "Thai", label: "🇹🇭 Thai" },
        { val: "Polish", label: "🇵🇱 Polish" },
        { val: "Romanian", label: "🇷🇴 Romanian" },
        { val: "Greek", label: "🇬🇷 Greek" },
        { val: "Czech", label: "🇨🇿 Czech" },
        { val: "Finnish", label: "🇫🇮 Finnish" },
        { val: "Hindi", label: "🇮🇳 Hindi" },
        { val: "Bulgarian", label: "🇧🇬 Bulgarian" },
        { val: "Danish", label: "🇩🇰 Danish" },
        { val: "Hebrew", label: "🇮🇱 Hebrew" },
        { val: "Malay", label: "🇲🇾 Malay" },
        { val: "Persian", label: "🇮🇷 Persian" },
        { val: "Slovak", label: "🇸🇰 Slovak" },
        { val: "Swedish", label: "🇸🇪 Swedish" },
        { val: "Croatian", label: "🇭🇷 Croatian" },
        { val: "Filipino", label: "🇵🇭 Filipino" },
        { val: "Hungarian", label: "🇭🇺 Hungarian" },
        { val: "Norwegian", label: "🇳🇴 Norwegian" },
        { val: "Slovenian", label: "🇸🇮 Slovenian" },
        { val: "Catalan", label: "🇪🇸 Catalan" },
        { val: "Nynorsk", label: "🇳🇴 Nynorsk" },
        { val: "Tamil", label: "🇱🇰 Tamil" },
        { val: "Afrikaans", label: "🇿🇦 Afrikaans" }
    ];
</script>

<script src="/pages/AI/js/dubbing.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>