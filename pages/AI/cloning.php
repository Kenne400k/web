<?php
$page_title = 'Voice Cloning - Studio';
require_once '../../config/header.php';
require_once '../../config/api_config.php'; 
require_once '../../config/sidebar.php';

$translations = [
    'vi' => [
        'banner' => 'Nhân bản bất cứ giọng nói nào mà bạn muốn, không còn nỗi lo không tìm thấy giọng phù hợp.',
        'select_provider' => 'Chọn Provider',
        'select_model' => 'Chọn Mô Hình',
        'model_desc_lingual' => 'Hỗ trợ 10 ngôn ngữ: Việt Nam, Mỹ, Trung Quốc, Ấn Độ, Pháp, Phần Lan, Đức, Ý, Nga, Tây Ban Nha',
        'model_desc_jeck' => 'Hỗ trợ 4 ngôn ngữ: Mỹ, Trung Quốc, Nhật Bản, Hàn Quốc',
        'voice_name' => 'Tên Giọng Nói <span>*</span>',
        'voice_name_placeholder' => 'VD: Giọng đọc tin tức',
        'upload_audio' => 'Tải Lên File Âm Thanh <span>*</span>',
        'upload_drag_drop' => 'Kéo thả file hoặc nhấp để chọn',
        'upload_duration_hint' => 'Thời lượng 10s - 2 phút, tối đa 20MB',
        'upload_format_hint' => 'Hiện chỉ hỗ trợ: .mp3',
        'upload_convert_hint' => 'Nếu tệp của bạn khác, vui lòng chuyển đổi thủ công.',
        'upload_duration_hint_kingcong' => 'Thời lượng 1 - 6 giây, tối đa 1MB',
        'upload_format_hint_kingcong' => 'Hỗ trợ: .mp3, .wav',
        'remove_file' => 'Xóa file',
        'audio_not_supported' => 'Trình duyệt không hỗ trợ phát âm thanh.',
        'select_language' => 'Chọn Ngôn Ngữ',
        'auto_detect' => 'Tự xác định',
        'search_placeholder' => 'Tìm kiếm...',
        'select_gender' => 'Chọn Giới Tính',
        'male' => 'Nam',
        'female' => 'Nữ',
        'preview_text' => 'Văn Bản Nghe Trước (Tùy chọn)',
        'preview_placeholder' => 'Nhập văn bản (Nếu để trống, hệ thống sẽ tự dùng mẫu câu mặc định)...',
        'chars' => 'ký tự',
        'remove_noise' => 'Loại Bỏ Tiếng Ồn',
        'remove_noise_tooltip' => 'Nếu tệp âm thanh của bạn có tiếng ồn nền, việc bật tính năng này có thể giúp âm thanh sạch hơn. Nếu tệp đã sạch, nên tắt để đạt kết quả tốt nhất.',
        'clone_button' => 'Nhân Bản',
        'library_title' => 'Thư viện giọng nhân bản',
        'voices' => 'giọng',
        'refresh' => 'Làm mới',
        'loading_list' => 'Đang tải danh sách...',
        'confirm_delete_title' => 'Xác nhận xóa giọng',
        'confirm_delete_message' => 'Bạn có chắc chắn muốn xóa giọng',
        'cannot_undo' => 'Hành động này không thể hoàn tác.',
        'cancel' => 'Hủy',
        'delete' => 'Xóa',
        'trim_audio_title' => 'Cắt Audio',
        'trim_info_text' => 'File của bạn dài <strong id="trimOriginalDuration">0</strong> giây, vượt quá giới hạn <strong id="trimMaxDuration">6</strong> giây. Hãy chọn đoạn audio bạn muốn sử dụng.',
        'trim_range_label' => 'Thời gian bắt đầu - kết thúc',
        'trim_start_label' => 'Bắt đầu (giây)',
        'trim_end_label' => 'Kết thúc (giây)',
        'trim_duration_label' => 'Thời lượng đoạn cắt:',
        'seconds' => 'giây',
        'trim_cancel' => 'Hủy',
        'trim_confirm' => 'Cắt & Sử dụng',
        'processing' => 'Đang xử lý...',
        'processing_hint' => 'AI đang học giọng nói của bạn. Quá trình này có thể mất 1-2 phút.',
        'notification' => 'Thông báo',
    ],
    'en' => [
        'banner' => 'Clone any voice you want, no more worrying about not finding the right voice.',
        'select_provider' => 'Select Provider',
        'select_model' => 'Select Model',
        'model_desc_lingual' => 'Supports 10 languages: Vietnam, USA, China, India, France, Finland, Germany, Italy, Russia, Spain',
        'model_desc_jeck' => 'Supports 4 languages: USA, China, Japan, Korea',
        'voice_name' => 'Voice Name <span>*</span>',
        'voice_name_placeholder' => 'E.g.: News reader voice',
        'upload_audio' => 'Upload Audio File <span>*</span>',
        'upload_drag_drop' => 'Drag & drop file or click to select',
        'upload_duration_hint' => 'Duration 10s - 2 minutes, max 20MB',
        'upload_format_hint' => 'Currently supports: .mp3',
        'upload_convert_hint' => 'If your file is different, please convert it manually.',
        'upload_duration_hint_kingcong' => 'Duration 1 - 6 seconds, max 1MB',
        'upload_format_hint_kingcong' => 'Supports: .mp3, .wav',
        'remove_file' => 'Remove file',
        'audio_not_supported' => 'Your browser does not support audio playback.',
        'select_language' => 'Select Language',
        'auto_detect' => 'Auto detect',
        'search_placeholder' => 'Search...',
        'select_gender' => 'Select Gender',
        'male' => 'Male',
        'female' => 'Female',
        'preview_text' => 'Preview Text (Optional)',
        'preview_placeholder' => 'Enter text (If empty, system will use default sample)...',
        'chars' => 'characters',
        'remove_noise' => 'Remove Noise',
        'remove_noise_tooltip' => 'If your audio has background noise, enabling this can make it cleaner. If it is already clean, turn it off for best results.',
        'clone_button' => 'Clone Voice',
        'library_title' => 'Cloned Voice Library',
        'voices' => 'voices',
        'refresh' => 'Refresh',
        'loading_list' => 'Loading list...',
        'confirm_delete_title' => 'Confirm Delete Voice',
        'confirm_delete_message' => 'Are you sure you want to delete voice',
        'cannot_undo' => 'This action cannot be undone.',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'trim_audio_title' => 'Trim Audio',
        'trim_info_text' => 'Your file is <strong id="trimOriginalDuration">0</strong> seconds long, exceeding the limit of <strong id="trimMaxDuration">6</strong> seconds. Please select the segment you want to use.',
        'trim_range_label' => 'Start - End Time',
        'trim_start_label' => 'Start (seconds)',
        'trim_end_label' => 'End (seconds)',
        'trim_duration_label' => 'Trimmed duration:',
        'seconds' => 'seconds',
        'trim_cancel' => 'Cancel',
        'trim_confirm' => 'Trim & Use',
        'processing' => 'Processing...',
        'processing_hint' => 'AI is learning your voice. This process may take 1-2 minutes.',
        'notification' => 'Notification',
    ],
];

$t = $translations[$lang] ?? $translations['vi'];

// Check Key & Get Credits
$has_api_key = false;
$current_credits = 0;

if (isset($_SESSION['Users'])) {
    $user_identity = $_SESSION['Users'];
    if (isset($mysqli)) {
        $stmt = $mysqli->prepare("SELECT apikey, credits FROM Users WHERE taikhoan = ? OR google_id = ?");
        $stmt->bind_param("ss", $user_identity, $user_identity);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res) {
            if (!empty($res['apikey'])) $has_api_key = true;
            $current_credits = $res['credits'];
        }
        $stmt->close();
    }
}
?>
<style>
/* Provider Icon */
.provider-icon {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    object-fit: cover;
    vertical-align: middle;
    margin-right: 6px;
}

/* ========================================
   MODEL DROPDOWN (KINGCONG)
   ======================================== */
.model-dropdown-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px 14px;
}

.model-dropdown-item .model-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.model-dropdown-item .model-name {
    font-weight: 600;
    font-size: 14px;
    color: #fff;
}

.model-dropdown-item .model-badge {
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 500;
}

.model-dropdown-item .model-badge.supported {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.model-dropdown-item .model-badge.not-supported {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.model-dropdown-item .model-desc {
    font-size: 12px;
    color: #888;
    line-height: 1.4;
}

.model-dropdown-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Dropup for model selector */
.custom-dropdown.dropup .dropdown-options {
    bottom: calc(100% + 4px);
    top: auto !important;
    margin-bottom: 0;
    margin-top: 0;
    max-height: 350px;
    overflow-y: auto;
}

.custom-dropdown.dropup .dropdown-selected i.bi-chevron-down {
    transform: rotate(180deg);
    transition: transform 0.2s;
}

.custom-dropdown.dropup .dropdown-selected.active i.bi-chevron-down {
    transform: rotate(0deg);
}

/* ========================================
   AUDIO TRIM MODAL
   ======================================== */
.trim-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: fadeIn 0.2s;
}

.trim-modal-content {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 16px;
    padding: 24px;
    max-width: 500px;
    width: 95%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
    animation: slideUp 0.3s;
}

.trim-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #333;
}

.trim-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.trim-modal-header h3 i {
    color: #f59e0b;
}

.trim-modal-close {
    background: none;
    border: none;
    color: #666;
    font-size: 20px;
    cursor: pointer;
    padding: 4px;
    transition: color 0.2s;
}

.trim-modal-close:hover {
    color: #fff;
}

.trim-info-box {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 20px;
}

.trim-info-box p {
    margin: 0;
    font-size: 13px;
    color: #f59e0b;
    line-height: 1.5;
}

.trim-audio-player {
    width: 100%;
    margin-bottom: 16px;
    background: #111;
    border-radius: 8px;
    padding: 12px;
}

.trim-audio-player audio {
    width: 100%;
    height: 40px;
    outline: none;
}

.trim-range-container {
    margin-bottom: 20px;
}

.trim-range-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 13px;
    color: #888;
}

.trim-range-label span {
    color: #fff;
    font-weight: 500;
}

.trim-range-inputs {
    display: flex;
    gap: 12px;
    align-items: center;
}

.trim-input-group {
    flex: 1;
}

.trim-input-group label {
    display: block;
    font-size: 12px;
    color: #888;
    margin-bottom: 6px;
}

.trim-input-group input {
    width: 100%;
    background: #111;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 10px 12px;
    color: #fff;
    font-size: 14px;
    text-align: center;
}

.trim-input-group input:focus {
    outline: none;
    border-color: #6366f1;
}

.trim-duration-display {
    text-align: center;
    padding: 10px;
    background: rgba(99, 102, 241, 0.1);
    border-radius: 8px;
    margin-bottom: 20px;
}

.trim-duration-display span {
    color: #6366f1;
    font-weight: 600;
    font-size: 15px;
}

.trim-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-trim-cancel,
.btn-trim-confirm {
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

.btn-trim-cancel {
    background: transparent;
    border: 1px solid #444;
    color: #aaa;
}

.btn-trim-cancel:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #666;
    color: #fff;
}

.btn-trim-confirm {
    background: #6366f1;
    color: #fff;
}

.btn-trim-confirm:hover {
    background: #4f46e5;
    transform: translateY(-1px);
}

.btn-trim-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Waveform Visualization */
.trim-waveform-container {
    position: relative;
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
    height: 100px;
}

#trimWaveform {
    width: 100%;
    height: 80px;
    display: block;
}

.trim-selection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.trim-selection {
    position: absolute;
    top: 0;
    height: 100%;
    background: rgba(99, 102, 241, 0.25);
    border-left: 2px solid #6366f1;
    border-right: 2px solid #6366f1;
    transition: left 0.1s, width 0.1s;
}

.trim-time-markers {
    display: flex;
    justify-content: space-between;
    padding: 6px 10px;
    background: #111;
    font-size: 11px;
    color: #666;
    font-family: monospace;
}

/* Kingcong fields */
.kingcong-fields {
    display: none;
}

/* Form actions row alignment */
.form-actions-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.form-actions-row .toggle-wrapper:empty + .btn-submit-simple,
.form-actions-row > .btn-submit-simple:only-child {
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
}</style>
<link rel="stylesheet" href="/pages/AI/css/voice_cloning.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/pages/AI/css/cloning-light-theme.css?v=<?php echo time(); ?>">
<div class="clone-container">
    
    <div class="form-panel">
        <div class="form-content-inner">
            <div class="info-banner">
                <strong><?php echo $t['banner']; ?></strong>
            </div>

            <!-- Provider Dropdown -->
            <div class="form-group">
                <label><?php echo $t['select_provider']; ?></label>
                <div class="custom-dropdown" id="providerDropdown">
                    <div class="dropdown-selected" onclick="toggleDropdown('providerOptions', this)">
                        <span id="selectedProviderText"><img src="https://registry.npmmirror.com/@lobehub/icons-static-png/latest/files/dark/minimax-color.png" class="provider-icon"> Minimax</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-options" id="providerOptions">
                        <div class="dropdown-item selected" data-val="minimax" onclick="selectProvider('minimax', '<img src=\'https://registry.npmmirror.com/@lobehub/icons-static-png/latest/files/dark/minimax-color.png\' class=\'provider-icon\'> Minimax', this)">
                            <img src="https://registry.npmmirror.com/@lobehub/icons-static-png/latest/files/dark/minimax-color.png" class="provider-icon"> Minimax
                        </div>
                        <div class="dropdown-item" data-val="kingcong" onclick="selectProvider('kingcong', '<img src=\'../../assets/media/logos/Kingkong.jpg\' class=\'provider-icon\'> Kingcong', this)">
                            <img src="../../assets/media/logos/Kingkong.jpg" class="provider-icon"> Kingcong
                        </div>
                    </div>
                </div>
            </div>

            <form id="cloneForm" onsubmit="return false;">

                <!-- ========== KINGCONG FIELDS (MODEL SELECTOR) ========== -->
                <div class="kingcong-fields" id="kingcongFields" style="display: none;">
                    <div class="form-group">
                        <label><?php echo $t['select_model']; ?></label>
                        <div class="custom-dropdown dropup" id="modelDropdown">
                            <div class="dropdown-selected" onclick="toggleDropdown('modelOptions', this)">
                                <span id="selectedModelText">KingCong Speech V1</span>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="dropdown-options" id="modelOptions">
                                <div class="dropdown-item model-dropdown-item selected" data-val="lingual_speech_v1" onclick="selectModel('lingual_speech_v1', 'KingCong Speech V1', this)">
                                    <div class="model-header">
                                        <span class="model-name">KingCong Speech V1</span>
                                        <span class="model-badge supported">Voice Cloning</span>
                                    </div>
                                    <div class="model-desc"><?php echo $t['model_desc_lingual']; ?></div>
                                </div>
                                <div class="dropdown-item model-dropdown-item" data-val="jeck_speech" onclick="selectModel('jeck_speech', 'KingCong Speech', this)">
                                    <div class="model-header">
                                        <span class="model-name">KingCong Speech</span>
                                        <span class="model-badge supported">Voice Cloning</span>
                                    </div>
                                    <div class="model-desc"><?php echo $t['model_desc_jeck']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $t['voice_name']; ?></label>
                    <input type="text" id="cloneName" class="form-input" placeholder="<?php echo $t['voice_name_placeholder']; ?>" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label><?php echo $t['upload_audio']; ?></label>
                    <input type="file" id="fileInput" accept="audio/mpeg, .mp3, audio/wav, .wav" style="display: none;" onchange="handleFileSelect(this)">

                    <!-- Upload box cho Minimax -->
                    <div class="upload-box minimax-upload" id="uploadBox"
                         onclick="$('#fileInput').click()"
                         ondragover="handleDragOver(event)"
                         ondragleave="handleDragLeave(event)"
                         ondrop="handleDrop(event)">
                        <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="upload-text"><?php echo $t['upload_drag_drop']; ?></div>
                        <div class="upload-hint"><?php echo $t['upload_duration_hint']; ?></div>
                        <div class="upload-hint"><?php echo $t['upload_format_hint']; ?></div>
                        <div class="upload-hint"><?php echo $t['upload_convert_hint']; ?></div>
                    </div>

                    <!-- Upload box cho Kingcong -->
                    <div class="upload-box kingcong-upload" id="uploadBoxKingcong" style="display: none;"
                         onclick="$('#fileInput').click()"
                         ondragover="handleDragOver(event)"
                         ondragleave="handleDragLeave(event)"
                         ondrop="handleDrop(event)">
                        <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="upload-text"><?php echo $t['upload_drag_drop']; ?></div>
                        <div class="upload-hint"><?php echo $t['upload_duration_hint_kingcong']; ?></div>
                        <div class="upload-hint"><?php echo $t['upload_format_hint_kingcong']; ?></div>
                    </div>

                    <div class="file-preview-card" id="fileInfo" style="display: none;">
                        <div class="file-details-row">
                            <div class="file-icon-wrapper"><i class="bi bi-music-note-beamed"></i></div>
                            <div class="file-meta-info">
                                <div class="file-name" id="fileName">filename.mp3</div>
                                <div class="file-size" id="fileSize">0.00 MB</div>
                            </div>
                            <button type="button" class="btn-remove-file" onclick="removeFile()" title="<?php echo $t['remove_file']; ?>"><i class="bi bi-trash"></i></button>
                        </div>
                        <div class="audio-player-wrapper">
                            <audio id="audioPreviewPlayer" controls><?php echo $t['audio_not_supported']; ?></audio>
                        </div>
                    </div>
                </div>

                <!-- ========== MINIMAX FIELDS ========== -->
                <div class="minimax-fields" id="minimaxFields">
                    <div class="row-group">
                        <div class="form-group">
                            <label><?php echo $t['select_language']; ?></label>
                            <div class="custom-dropdown" id="langDropdown">
                                <div class="dropdown-selected" onclick="toggleDropdown('langOptions', this)">
                                    <span id="selectedLangText"><?php echo $t['auto_detect']; ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                                <div class="dropdown-options" id="langOptions">
                                    <div class="dropdown-search">
                                        <input type="text" placeholder="<?php echo $t['search_placeholder']; ?>" onkeyup="filterLang(this)">
                                    </div>
                                    <div class="dropdown-list-container">
                                        <div class="dropdown-item" data-val="Auto" onclick="selectItem('lang', 'Auto', '<?php echo $t['auto_detect']; ?>', this)"><?php echo $t['auto_detect']; ?></div>
                                        <div class="dropdown-item" data-val="English" onclick="selectItem('lang', 'English', 'English', this)">English</div>
                                        <div class="dropdown-item selected" data-val="Vietnamese" onclick="selectItem('lang', 'Vietnamese', 'Vietnamese', this)">Vietnamese</div>
                                        <div class="dropdown-item" data-val="Arabic" onclick="selectItem('lang', 'Arabic', 'Arabic', this)">Arabic</div>
                                        <div class="dropdown-item" data-val="Cantonese" onclick="selectItem('lang', 'Cantonese', 'Cantonese', this)">Cantonese</div>
                                        <div class="dropdown-item" data-val="Chinese" onclick="selectItem('lang', 'Chinese', 'Chinese (Mandarin)', this)">Chinese (Mandarin)</div>
                                        <div class="dropdown-item" data-val="Dutch" onclick="selectItem('lang', 'Dutch', 'Dutch', this)">Dutch</div>
                                        <div class="dropdown-item" data-val="French" onclick="selectItem('lang', 'French', 'French', this)">French</div>
                                        <div class="dropdown-item" data-val="German" onclick="selectItem('lang', 'German', 'German', this)">German</div>
                                        <div class="dropdown-item" data-val="Indonesian" onclick="selectItem('lang', 'Indonesian', 'Indonesian', this)">Indonesian</div>
                                        <div class="dropdown-item" data-val="Italian" onclick="selectItem('lang', 'Italian', 'Italian', this)">Italian</div>
                                        <div class="dropdown-item" data-val="Japanese" onclick="selectItem('lang', 'Japanese', 'Japanese', this)">Japanese</div>
                                        <div class="dropdown-item" data-val="Korean" onclick="selectItem('lang', 'Korean', 'Korean', this)">Korean</div>
                                        <div class="dropdown-item" data-val="Portuguese" onclick="selectItem('lang', 'Portuguese', 'Portuguese', this)">Portuguese</div>
                                        <div class="dropdown-item" data-val="Russian" onclick="selectItem('lang', 'Russian', 'Russian', this)">Russian</div>
                                        <div class="dropdown-item" data-val="Spanish" onclick="selectItem('lang', 'Spanish', 'Spanish', this)">Spanish</div>
                                        <div class="dropdown-item" data-val="Turkish" onclick="selectItem('lang', 'Turkish', 'Turkish', this)">Turkish</div>
                                        <div class="dropdown-item" data-val="Ukrainian" onclick="selectItem('lang', 'Ukrainian', 'Ukrainian', this)">Ukrainian</div>
                                        <div class="dropdown-item" data-val="Thai" onclick="selectItem('lang', 'Thai', 'Thai', this)">Thai</div>
                                        <div class="dropdown-item" data-val="Polish" onclick="selectItem('lang', 'Polish', 'Polish', this)">Polish</div>
                                        <div class="dropdown-item" data-val="Romanian" onclick="selectItem('lang', 'Romanian', 'Romanian', this)">Romanian</div>
                                        <div class="dropdown-item" data-val="Greek" onclick="selectItem('lang', 'Greek', 'Greek', this)">Greek</div>
                                        <div class="dropdown-item" data-val="Czech" onclick="selectItem('lang', 'Czech', 'Czech', this)">Czech</div>
                                        <div class="dropdown-item" data-val="Finnish" onclick="selectItem('lang', 'Finnish', 'Finnish', this)">Finnish</div>
                                        <div class="dropdown-item" data-val="Hindi" onclick="selectItem('lang', 'Hindi', 'Hindi', this)">Hindi</div>
                                        <div class="dropdown-item" data-val="Bulgarian" onclick="selectItem('lang', 'Bulgarian', 'Bulgarian', this)">Bulgarian</div>
                                        <div class="dropdown-item" data-val="Danish" onclick="selectItem('lang', 'Danish', 'Danish', this)">Danish</div>
                                        <div class="dropdown-item" data-val="Hebrew" onclick="selectItem('lang', 'Hebrew', 'Hebrew', this)">Hebrew</div>
                                        <div class="dropdown-item" data-val="Malay" onclick="selectItem('lang', 'Malay', 'Malay', this)">Malay</div>
                                        <div class="dropdown-item" data-val="Persian" onclick="selectItem('lang', 'Persian', 'Persian', this)">Persian</div>
                                        <div class="dropdown-item" data-val="Slovak" onclick="selectItem('lang', 'Slovak', 'Slovak', this)">Slovak</div>
                                        <div class="dropdown-item" data-val="Swedish" onclick="selectItem('lang', 'Swedish', 'Swedish', this)">Swedish</div>
                                        <div class="dropdown-item" data-val="Croatian" onclick="selectItem('lang', 'Croatian', 'Croatian', this)">Croatian</div>
                                        <div class="dropdown-item" data-val="Filipino" onclick="selectItem('lang', 'Filipino', 'Filipino', this)">Filipino</div>
                                        <div class="dropdown-item" data-val="Hungarian" onclick="selectItem('lang', 'Hungarian', 'Hungarian', this)">Hungarian</div>
                                        <div class="dropdown-item" data-val="Norwegian" onclick="selectItem('lang', 'Norwegian', 'Norwegian', this)">Norwegian</div>
                                        <div class="dropdown-item" data-val="Slovenian" onclick="selectItem('lang', 'Slovenian', 'Slovenian', this)">Slovenian</div>
                                        <div class="dropdown-item" data-val="Catalan" onclick="selectItem('lang', 'Catalan', 'Catalan', this)">Catalan</div>
                                        <div class="dropdown-item" data-val="Nynorsk" onclick="selectItem('lang', 'Nynorsk', 'Nynorsk', this)">Nynorsk</div>
                                        <div class="dropdown-item" data-val="Tamil" onclick="selectItem('lang', 'Tamil', 'Tamil', this)">Tamil</div>
                                        <div class="dropdown-item" data-val="Afrikaans" onclick="selectItem('lang', 'Afrikaans', 'Afrikaans', this)">Afrikaans</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?php echo $t['select_gender']; ?></label>
                            <div class="custom-dropdown" id="genderDropdown">
                                <div class="dropdown-selected" onclick="toggleDropdown('genderOptions', this)">
                                    <span id="selectedGenderText"><i class="bi bi-gender-male"></i> <?php echo $t['male']; ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                                <div class="dropdown-options" id="genderOptions">
                                    <div class="dropdown-item selected" data-val="male" onclick="selectItem('gender', 'male', '<i class=\'bi bi-gender-male\'></i> <?php echo $t['male']; ?>', this)"><i class="bi bi-gender-male"></i> <?php echo $t['male']; ?></div>
                                    <div class="dropdown-item" data-val="female" onclick="selectItem('gender', 'female', '<i class=\'bi bi-gender-female\'></i> <?php echo $t['female']; ?>', this)"><i class="bi bi-gender-female"></i> <?php echo $t['female']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo $t['preview_text']; ?></label>
                        <div class="textarea-wrapper">
                            <textarea id="previewText" class="form-textarea"
                                placeholder="<?php echo $t['preview_placeholder']; ?>"
                                maxlength="500" oninput="updateCharCount()"></textarea>
                            <div class="char-count" id="charCount">0 / 500 <?php echo $t['chars']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions-row">
                    <div class="toggle-wrapper minimax-only" id="noiseToggleWrapper">
                        <label class="switch-control">
                            <input type="checkbox" id="removeNoiseToggle" checked>
                            <span class="slider"></span>
                        </label>

                        <div class="label-with-tooltip" onclick="$('#removeNoiseToggle').click()">
                            <span><?php echo $t['remove_noise']; ?></span>
                            <div class="tooltip-content">
                                <?php echo $t['remove_noise_tooltip']; ?>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btnCreate" class="btn-submit-simple" onclick="createClone()">
                        <?php echo $t['clone_button']; ?>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div class="library-area">
        <div class="section-header">
            <div class="section-title">
                <i class="bi bi-collection-play-fill"></i>
                <?php echo $t['library_title']; ?>
                <span class="voice-count"><span id="voiceCount">0</span> <?php echo $t['voices']; ?></span>
            </div>
            <button class="btn-refresh" onclick="loadClonedVoices()">
                <i class="bi bi-arrow-clockwise"></i> <?php echo $t['refresh']; ?>
            </button>
        </div>

        <div id="clonedVoiceGrid" class="voice-grid">
            <div class="empty-state">
                <div class="spinner-border text-secondary" style="margin-bottom: 16px;"></div>
                <div class="title"><?php echo $t['loading_list']; ?></div>
            </div>
        </div>
    </div>

</div>
<!-- 🔥 DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 32px;"></i>
            <h3><?php echo $t['confirm_delete_title']; ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo $t['confirm_delete_message']; ?> "<strong id="voiceNameToDelete"></strong>"?</p>
            <p style="color: #888; font-size: 14px; margin-top: 8px;">
                <?php echo $t['cannot_undo']; ?>
            </p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">
                <i class="bi bi-x-circle"></i>
                <span><?php echo $t['cancel']; ?></span>
            </button>
            <button class="btn-confirm-delete" onclick="confirmDelete()">
                <i class="bi bi-trash"></i>
                <span><?php echo $t['delete']; ?></span>
            </button>
        </div>
    </div>
</div>
<!-- AUDIO PREVIEW -->
<audio id="previewAudio" style="display: none;"></audio>

<!-- AUDIO TRIM MODAL -->
<div id="trimModal" class="trim-modal-overlay" style="display: none;">
    <div class="trim-modal-content">
        <div class="trim-modal-header">
            <h3><i class="bi bi-scissors"></i> <?php echo $t['trim_audio_title']; ?></h3>
            <button class="trim-modal-close" onclick="closeTrimModal()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="trim-info-box">
            <p id="trimInfoText"><?php echo $t['trim_info_text']; ?></p>
        </div>

        <div class="trim-audio-player">
            <audio id="trimAudioPlayer" controls></audio>
        </div>

        <!-- Waveform Visualization -->
        <div class="trim-waveform-container">
            <canvas id="trimWaveform"></canvas>
            <div class="trim-selection-overlay">
                <div class="trim-selection" id="trimSelection"></div>
            </div>
            <div class="trim-time-markers">
                <span id="waveformTimeStart">0:00</span>
                <span id="waveformTimeEnd">0:00</span>
            </div>
        </div>

        <div class="trim-range-container">
            <div class="trim-range-label">
                <span><?php echo $t['trim_range_label']; ?></span>
            </div>
            <div class="trim-range-inputs">
                <div class="trim-input-group">
                    <label><?php echo $t['trim_start_label']; ?></label>
                    <input type="number" id="trimStart" value="0" min="0" step="0.1" oninput="updateTrimDuration()" onchange="updateTrimDuration()">
                </div>
                <div class="trim-input-group">
                    <label><?php echo $t['trim_end_label']; ?></label>
                    <input type="number" id="trimEnd" value="6" min="0" step="0.1" oninput="updateTrimDuration()" onchange="updateTrimDuration()">
                </div>
            </div>
        </div>

        <div class="trim-duration-display">
            <?php echo $t['trim_duration_label']; ?> <span id="trimResultDuration">6.0</span> <?php echo $t['seconds']; ?>
        </div>

        <div class="trim-modal-actions">
            <button class="btn-trim-cancel" onclick="closeTrimModal()">
                <i class="bi bi-x-circle"></i> <?php echo $t['trim_cancel']; ?>
            </button>
            <button class="btn-trim-confirm" id="btnTrimConfirm" onclick="confirmTrim()">
                <i class="bi bi-scissors"></i> <?php echo $t['trim_confirm']; ?>
            </button>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text"><?php echo $t['processing']; ?></div>
    <div class="loading-hint"><?php echo $t['processing_hint']; ?></div>
    <div class="loading-progress">
        <div class="loading-bar" id="loadingBar"></div>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast-notification">
    <i class="bi bi-check-circle toast-icon success"></i>
    <span class="toast-text"><?php echo $t['notification']; ?></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const hasApiKey = <?php echo $has_api_key ? 'true' : 'false'; ?>;
    const currentCredits = <?php echo $current_credits; ?>;
</script>
<script src="/pages/AI/js/voice_cloning3.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>