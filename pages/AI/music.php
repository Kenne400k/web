<?php
$page_title = 'Tạo nhạc AI - Studio';
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

<link rel="stylesheet" href="/pages/AI/css/music.css?v=<?php echo time(); ?>">
<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
/* Container chính */
.custom-select-container {
    position: relative;
    user-select: none;
    width: 100%;
}

/* Cái thanh hiển thị (Trigger) */
.select-trigger {
    padding: 10px 14px;
    height: 42px; /* Cao bằng nút tạo nhạc */
    font-size: 13px;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    white-space: nowrap;
}
.custom-select-container.drop-up .select-options {
    bottom: calc(100% + 8px); /* Đẩy lên trên */
    top: auto;
    left: 0;
    width: 100%;
    transform-origin: bottom center;
}

.select-trigger:hover {
    border-color: #666;
    background: rgba(255, 255, 255, 0.02);
}

/* Icon mũi tên xoay khi mở */
.custom-select-container.open .select-trigger i {
    transform: rotate(180deg);
}

/* Danh sách xổ xuống */
.select-options {
    position: absolute;
    /* --- Thay đổi quan trọng ở đây --- */
    bottom: calc(100% + 8px); /* Neo vào đáy và đẩy lên trên trigger 8px */
    top: auto;                /* Hủy bỏ thuộc tính top cũ */
    left: 0;
    right: 0;
    /* -------------------------------- */
    
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.3); /* Chỉnh bóng đổ lên trên */
    z-index: 100;
    
    /* Animation: Trạng thái ẩn */
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px); /* Dịch xuống 1 chút để khi hiện nó trượt lên */
    transition: all 0.2s ease;
    overflow: hidden;
}

/* Hiện danh sách khi có class open */
.custom-select-container.open .select-options {
    opacity: 1;
    visibility: visible;
    transform: translateY(0); /* Trượt về vị trí gốc */
}
/* Từng dòng option */
.option-item {
    padding: 10px 16px;
    font-size: 14px;
    color: #ccc;
    cursor: pointer;
    transition: 0.2s;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.option-item:last-child {
    border-bottom: none;
}

.option-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

/* Dòng đang được chọn */
.option-item.selected {
    background: #fff;
    color: #000;
    font-weight: 600;
}
/* Layout nằm ngang */
.switches-row {
    display: flex;
    gap: 20px; /* Khoảng cách giữa 2 nút */
    margin-bottom: 20px;
    align-items: center;
}

.switch-item {
    position: relative; /* Để căn vị trí tooltip */
    display: flex;
    flex-direction: column;
}

.switch-control {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    background: var(--bg-main); /* Nền nhẹ cho nút */
    padding: 8px 12px;
    border: 0.5px solid var(--border-color);
    border-radius: 15px;
    transition: 0.2s;
}

.switch-control:hover {
    border-color: #666;
    background: rgba(255, 255, 255, 0.05);
}

.switch-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
}

/* Tooltip (Mô tả hiện khi hover) */
.custom-tooltip {
    position: absolute;
    bottom: 110%; /* Hiện bên trên nút */
    left: 50%;
    transform: translateX(-50%) translateY(10px);
    background: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11px;
    width: 200px;
    text-align: center;
    pointer-events: none; /* Không cho chuột bấm vào tooltip */
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 100;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* Mũi tên nhỏ dưới tooltip */
.custom-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #333 transparent transparent transparent;
}

/* Hiệu ứng khi hover vào switch-item */
.switch-item:hover .custom-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

/* Responsive cho điện thoại thì xuống dòng cho đỡ chật */
@media (max-width: 480px) {
    .switches-row {
        flex-direction: column;
        gap: 10px;
    }
    .switch-item {
        width: 100%;
    }
    .switch-control {
        width: 100%;
        justify-content: space-between;
    }
}</style>

<div class="music-container">
    <!-- LEFT PANEL -->
    <div class="settings-panel">
        <div class="panel-header">
            <div class="panel-title">
                <i class="bi bi-music-note-beamed"></i>
                Tạo nhạc AI
            </div>
        </div>

        <div class="form-group">
    <label class="form-label">
        Tiêu đề bài hát <span style="color: #888;">(Tùy chọn)</span>
    </label>
    <input type="text" 
           id="songTitle" 
           class="form-input" 
           placeholder="Bài Hát Chưa Có Tên" 
           >
    <div class="char-count">
        <span id="titleCount">0</span> ký tự
    </div>
</div>

<div class="form-group">
    <label class="form-label">
        Mô tả ý tưởng <span class="required">*</span>
    </label>
    <textarea id="ideaInput" 
              class="form-textarea" 
              placeholder="Nhập ý tưởng (prompt) và biến thành kiệt tác âm thanh&#10;Ví dụ: Bài nhạc guitar vui nhộn, sôi động về nỗi nhớ.&#10;(Sử dụng Minimax)" 
              rows="4"></textarea>
    <div class="char-count">
        <span id="ideaCount">0</span> ký tự
    </div>
</div>

        <!-- Toggle: Tối ưu ý tưởng -->
        <div class="switches-row">
            <div class="switch-item">
                <div class="switch-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="rewriteSwitch">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="switch-label">Tối ưu ý tưởng</span>
                </div>
            </div>

            <div class="switch-item">
                <div class="switch-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="lyricsSwitch" onchange="toggleLyricsInput()">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="switch-label">Thêm lời</span>
                </div>
            </div>

            <div class="settings-trigger">
                <button class="btn-settings" onclick="openSettingsModal()" title="Cấu hình âm nhạc">
                    <i class="bi bi-gear"></i>
                </button>
            </div>
        </div>

        <input type="hidden" id="styleVal" value="">
        <input type="hidden" id="moodVal" value="">
        <input type="hidden" id="scenarioVal" value="">

        <div id="settingsModal" class="custom-modal">
            <div class="modal-overlay" onclick="closeSettingsModal()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Cấu hình</h3>
                    <p>Chọn các đặc trưng âm nhạc bạn muốn</p>
                    <span class="close-modal" onclick="closeSettingsModal()">&times;</span>
                </div>
                
                <div class="modal-body">
                    <div class="section-group">
                        <label>Phong cách</label>
                        <div class="chips-container" id="modalStyleList"></div>
                    </div>

                    <div class="section-group">
                        <label>Tâm trạng</label>
                        <div class="chips-container" id="modalMoodList"></div>
                    </div>

                    <div class="section-group">
                        <label>Bối cảnh</label>
                        <div class="chips-container" id="modalScenarioList"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn-reset" onclick="resetSettings()">
                        <i class="bi bi-arrow-counterclockwise"></i> Đặt lại
                    </button>
                    <button class="btn-confirm" onclick="saveSettings()">Xác nhận</button>
                </div>
            </div>
        </div>
        

        <div id="lyricsInputSection" class="form-group mt-3" style="display: none;">
    <label class="form-label">Lời bài hát <span class="required">*</span></label>
    
    <textarea id="lyricsInput" class="form-textarea" rows="8" placeholder="Thêm lời bài hát của bạn tại đây
[Verse]
Cracked earth beneath my worn boots
Felt the weight of tangled roots
Heard the whispers of my doubts
Trying hard to find a way out
[Chorus]
Built of country dust and steel
Every scar a truth revealed
Stood tall broke every chain
Found my strength again and again
Empowered by the rising sun
My journey's just begun
"></textarea>

    <div class="char-count">
        <span id="lyricsCount">0</span> ký tự
    </div>
    
</div>
        <!-- Number of Tracks -->
        <div class="bottom-control-bar">
            
            <div class="control-item dropdown-wrapper">
                <div class="custom-select-container drop-up" id="trackDropdown">
                    <div class="select-trigger" onclick="toggleTrackDropdown()">
                        <span id="selectedTrackLabel">1 bản nhạc</span>
                        <i class="bi bi-chevron-up"></i>
                    </div>
                    
                    <div class="select-options">
                        <div class="option-item selected" onclick="chooseTrack(1, '1 bản nhạc', this)">1 bản nhạc</div>
                        <div class="option-item" onclick="chooseTrack(2, '2 bản nhạc', this)">2 bản nhạc</div>
                        <div class="option-item" onclick="chooseTrack(3, '3 bản nhạc', this)">3 bản nhạc</div>
                    </div>
                </div>
                <input type="hidden" id="trackCount" value="1">
            </div>

            <div class="control-item credit-info">
                <div class="credit-row">
                    <span class="c-value highlight" id="estimatedCost">~1,200</span>
                </div>
                <div class="credit-separator">/</div>
                <div class="credit-row">
                    <span class="c-value" id="userCredits">
                        <?php echo number_format($user_credits); ?>
                    </span>
                </div>
            </div>

            <div class="control-item button-wrapper">
                <button onclick="generateMusic()" id="btnGenerate" class="btn-generate">
                    <i class="bi bi-play-circle-fill"></i>
                    <span>Tạo ngay</span>
                </button>
            </div>
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
                <button class="btn-bulk" onclick="bulkDownload()" title="Tải xuống">
                    <i class="bi bi-download"></i>
                    <span>Tải xuống</span>
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

    <!-- Phần list giữ nguyên -->
    <div id="historyList" class="history-list">
        <div class="history-empty">
            <i class="bi bi-music-note-list"></i>
            <span>Chưa có bản nhạc nào</span>
        </div>
    </div>
</div>
</div>
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
}</style>
<audio id="musicPlayer" style="display: none;"></audio>

<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const hasApiKey = <?php echo $has_api_key ? 'true' : 'false'; ?>;
</script>

<script src="/pages/AI/js/music.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>