<?php
// pages/AI/tts3.php
$page_title = 'Text to Speech - Studio';
require_once '../../config/header.php';
require_once '../../config/api_config.php'; 
require_once '../../config/sidebar.php';
require_once '../../config/genai_backup.php';

// ========== KHỞI TẠO GENAI BACKUP ==========
$genai = new GenAIBackup($mysqli);

// ========== ĐỊNH NGHĨA ĐA NGÔN NGỮ ==========
$translations = [
    'vi' => [
        // Page & Sections
        'page_title' => 'Văn bản thành giọng nói',
        'settings' => 'Cài đặt',
        'history' => 'Lịch sử',
        'refresh' => 'Làm mới',
        'details' => 'Chi tiết',
        
        // Voice Selection
        'choose_voice' => 'Chọn giọng nói',
        'select_voice' => 'Chọn giọng nói...',
        'default' => 'Mặc định',
        'cloned' => 'Giọng nhân bản',
        'library' => 'Thư viện giọng nói',
        'favorites' => 'Yêu thích',
        'search_voice' => 'Tìm kiếm giọng đọc...',
        
        // Language & Model
        'select_language' => 'Chọn ngôn ngữ',
        'auto_detect' => 'Tự xác định',
        'select_model' => 'Chọn mô hình ngôn ngữ',
        
        // Audio Controls
        'speed' => 'Tốc độ',
        'pitch' => 'Cao độ',
        'volume' => 'Âm lượng',
        'stability' => 'Độ ổn định',
        'similarity' => 'Độ tương đồng',
        'style_exaggeration' => 'Phóng đại phong cách',
        'speaker_boost' => 'Tăng cường giọng nói',
        
        // Actions
        'reset' => 'Đặt lại',
        'generate_voice' => 'Tạo Giọng Nói',
        'upload' => 'Tải lên',
        'upload_file' => 'Tải lên tệp (.txt .zip)',
        'upload_folder' => 'Tải lên thư mục',
        'use' => 'Dùng',
        'play' => 'Nghe thử',
        'copy_id' => 'Copy ID',
        
        // Costs & Credits
        'estimated_cost' => 'Tín dụng đã tính',
        'current_credits' => 'Số dư hiện tại',
        'credits' => 'credits',
        'credit_used' => 'Tín dụng sử dụng',
        'processing_fee' => 'Bao gồm phí xử lý',
        'srt_fee' => 'Định dạng SRT đắt hơn',
        'clone_fee' => 'Giọng Clone',
        'subtitle' => 'Phụ đề',
        'fee' => 'phí',
        
        // Input Area
        'enter_text' => 'Nhập văn bản của bạn tại đây.',
        'no_char_limit' => 'Không giới hạn ký tự văn bản.',
        'tip_vietnamese' => 'Tiếng Việt nên sử dụng',
        'tip_break' => 'để nghỉ 0.5 giây',
        'tip_drag_drop' => 'Kéo thả tệp',
        'tip_here' => 'vào đây',
        
        // Filters
        'all' => 'Tất cả',
        'language' => 'Ngôn ngữ',
        'gender' => 'Giới tính',
        'age' => 'Độ tuổi',
        'style' => 'Phong cách',
        'accent' => 'Giọng',
        'male' => 'Nam',
        'female' => 'Nữ',
        'young' => 'Trẻ',
        'middle_aged' => 'Trung niên',
        'old' => 'Lớn tuổi',
        
        // Status
        'loading' => 'Đang tải',
        'processing' => 'Đang xử lý',
        'done' => 'Xong',
        'completed' => 'Hoàn thành',
        'failed' => 'Thất bại',
        'queued' => 'Hàng đợi',
        'waiting' => 'Đang chờ',
        
        // Messages
        'no_voices' => 'Không tìm thấy giọng nói',
        'no_results' => 'Không tìm thấy kết quả phù hợp',
        'clear_filters' => 'Xóa bộ lọc',
        'loading_voices' => 'Đang tải danh sách giọng...',
        'please_wait' => 'Vui lòng đợi trong giây lát',
        
        // Sort
        'sort_by' => 'Sắp xếp theo',
        'most_used' => 'Dùng nhiều nhất',
        'newest' => 'Mới nhất',
        'trending' => 'Xu hướng',
        'chars_generated' => 'Ký tự được tạo',

        // Common
        'chars' => 'ký tự',
        'cancel' => 'Hủy',
        'delete' => 'Xóa',
        'download' => 'Tải xuống',
        'close' => 'Đóng',
        'loading_data' => 'Đang tải dữ liệu...',
        'refresh_data' => 'Làm mới dữ liệu',

        // Drag & Drop Overlay
        'drop_overlay_title' => 'Thả tệp vào đây',
        'drop_overlay_desc' => 'Hỗ trợ .txt, .srt, .zip',

        // Clone Modal
        'clone_modal_title' => 'Nhân bản giọng nói',
        'clone_name_label' => 'Tên giọng',
        'clone_name_placeholder' => 'Ví dụ: Giọng của tôi',
        'clone_gender_label' => 'Giới tính',
        'clone_gender_male' => 'Nam (Male)',
        'clone_gender_female' => 'Nữ (Female)',
        'clone_sample_file' => 'File mẫu (MP3, < 5 phút, < 20MB)',
        'clone_sample_hint' => '* Nên dùng file thu âm rõ ràng, không tạp âm',
        'clone_start' => 'Bắt đầu Clone',

        // Delete Task Modal
        'delete_task_title' => 'Xóa tác vụ',
        'delete_task_warn' => 'Bạn có chắc chắn muốn xóa tác vụ này? Hành động này không thể hoàn tác.',
        'delete_task_note' => 'Nếu tác vụ bị treo quá 24h sẽ được hoàn tín dụng.',
        'delete_task_preview' => 'Nội dung text sẽ hiện ở đây...',

        // SRT Settings
        'srt_settings_title' => 'Cài đặt Subtitle',
        'srt_max_chars' => 'Số ký tự tối đa trên dòng:',
        'srt_max_lines' => 'Số dòng tối đa trên câu:',
        'srt_max_duration' => 'Số giây tối đa trên câu:',
        'srt_reset' => 'Đặt lại giá trị',
        'export' => 'Xuất',

        // Folder Upload Confirm
        'folder_confirm_title' => 'Bạn muốn tải <span id="folderFileCount">10</span> tệp lên trang web này?',
        'folder_confirm_desc' => 'Thao tác này sẽ tải tất cả các tệp từ "Import Folder" lên. Chỉ thực hiện thao tác này nếu bạn tin tưởng trang web.',

        // Pronunciation languages
        'pron_lang_vi' => 'Tiếng Việt',
        'pron_lang_en' => 'English',
        'pron_lang_fr' => 'Français',
        'pron_lang_zh' => '中文',

        // Normalize examples
        'ex_newline_before' => '"Xin chào\nCác bạn"',
        'ex_newline_after' => '"Xin chào. Các bạn"',
        'ex_whitespace_before' => '"Xin chào , các bạn"',
        'ex_whitespace_after' => '"Xin chào, các bạn"',
        'ex_collapse_before' => '"Gì!."',
        'ex_collapse_after' => '"Gì!"',
        'ex_stray_before' => '"Xin chào, ."',
        'ex_stray_after' => '"Xin chào."',
        'ex_unicode_before' => '"Xin@#$Chào"',
        'ex_unicode_after' => '"Xin Chào"',
    ],
    
    'en' => [
        // Page & Sections
        'page_title' => 'Text to Speech',
        'settings' => 'Settings',
        'history' => 'History',
        'refresh' => 'Refresh',
        'details' => 'Details',
        
        // Voice Selection
        'choose_voice' => 'Choose Voice',
        'select_voice' => 'Select voice...',
        'default' => 'Default',
        'cloned' => 'Cloned Voice',
        'library' => 'Voice Library',
        'favorites' => 'Favorites',
        'search_voice' => 'Search voices...',
        
        // Language & Model
        'select_language' => 'Select Language',
        'auto_detect' => 'Auto Detect',
        'select_model' => 'Select Language Model',
        
        // Audio Controls
        'speed' => 'Speed',
        'pitch' => 'Pitch',
        'volume' => 'Volume',
        'stability' => 'Stability',
        'similarity' => 'Similarity',
        'style_exaggeration' => 'Style Exaggeration',
        'speaker_boost' => 'Speaker Boost',
        
        // Actions
        'reset' => 'Reset',
        'generate_voice' => 'Generate Voice',
        'upload' => 'Upload',
        'upload_file' => 'Upload file (.txt .zip)',
        'upload_folder' => 'Upload folder',
        'use' => 'Use',
        'play' => 'Preview',
        'copy_id' => 'Copy ID',
        
        // Costs & Credits
        'estimated_cost' => 'Estimated Cost',
        'current_credits' => 'Current Balance',
        'credits' => 'credits',
        'credit_used' => 'Credits Used',
        'processing_fee' => 'Including processing fee',
        'srt_fee' => 'SRT format is more expensive',
        'clone_fee' => 'Clone Voice',
        'subtitle' => 'Subtitle',
        'fee' => 'fee',
        
        // Input Area
        'enter_text' => 'Enter your text here.',
        'no_char_limit' => 'No character limit.',
        'tip_vietnamese' => 'For Vietnamese, use',
        'tip_break' => 'to pause 0.5s',
        'tip_drag_drop' => 'Drag and drop',
        'tip_here' => 'here',
        
        // Filters
        'all' => 'All',
        'language' => 'Language',
        'gender' => 'Gender',
        'age' => 'Age',
        'style' => 'Style',
        'accent' => 'Accent',
        'male' => 'Male',
        'female' => 'Female',
        'young' => 'Young',
        'middle_aged' => 'Middle Aged',
        'old' => 'Old',
        
        // Status
        'loading' => 'Loading',
        'processing' => 'Processing',
        'done' => 'Done',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'queued' => 'Queued',
        'waiting' => 'Waiting',
        
        // Messages
        'no_voices' => 'No voices found',
        'no_results' => 'No results found',
        'clear_filters' => 'Clear filters',
        'loading_voices' => 'Loading voice list...',
        'please_wait' => 'Please wait a moment',
        
        // Sort
        'sort_by' => 'Sort by',
        'most_used' => 'Most Used',
        'newest' => 'Newest',
        'trending' => 'Trending',
        'chars_generated' => 'Characters Generated',

        // Common
        'chars' => 'chars',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'download' => 'Download',
        'close' => 'Close',
        'loading_data' => 'Loading data...',
        'refresh_data' => 'Refresh data',

        // Drag & Drop Overlay
        'drop_overlay_title' => 'Drop files here',
        'drop_overlay_desc' => 'Supports .txt, .srt, .zip',

        // Clone Modal
        'clone_modal_title' => 'Clone Voice',
        'clone_name_label' => 'Voice Name',
        'clone_name_placeholder' => 'E.g.: My voice',
        'clone_gender_label' => 'Gender',
        'clone_gender_male' => 'Male',
        'clone_gender_female' => 'Female',
        'clone_sample_file' => 'Sample file (MP3, < 5 minutes, < 20MB)',
        'clone_sample_hint' => '* Use a clear recording without background noise',
        'clone_start' => 'Start Cloning',

        // Delete Task Modal
        'delete_task_title' => 'Delete Task',
        'delete_task_warn' => 'Are you sure you want to delete this task? This action cannot be undone.',
        'delete_task_note' => 'If the task is stuck for more than 24 hours, credits will be refunded.',
        'delete_task_preview' => 'Text content will appear here...',

        // SRT Settings
        'srt_settings_title' => 'Subtitle Settings',
        'srt_max_chars' => 'Max characters per line:',
        'srt_max_lines' => 'Max lines per sentence:',
        'srt_max_duration' => 'Max seconds per sentence:',
        'srt_reset' => 'Reset values',
        'export' => 'Export',

        // Folder Upload Confirm
        'folder_confirm_title' => 'Do you want to upload <span id="folderFileCount">10</span> files to this website?',
        'folder_confirm_desc' => 'This action will upload all files from "Import Folder". Only proceed if you trust this website.',

        // Pronunciation languages
        'pron_lang_vi' => 'Vietnamese',
        'pron_lang_en' => 'English',
        'pron_lang_fr' => 'French',
        'pron_lang_zh' => 'Chinese',

        // Normalize examples
        'ex_newline_before' => '"Hello\nEveryone"',
        'ex_newline_after' => '"Hello. Everyone"',
        'ex_whitespace_before' => '"Hello , everyone"',
        'ex_whitespace_after' => '"Hello, everyone"',
        'ex_collapse_before' => '"What!."',
        'ex_collapse_after' => '"What!"',
        'ex_stray_before' => '"Hello, ."',
        'ex_stray_after' => '"Hello."',
        'ex_unicode_before' => '"Hello@#$World"',
        'ex_unicode_after' => '"Hello World"',
    ]
];

$t = $translations[$lang];
// ========== CHECK MAINTENANCE MODE (SERVER 3 - KINGCONG) ==========
$elevenlabs_down = false;
$minimax_down = false;
$kingcong_down = false;

if (isset($mysqli)) {
    $stmt_check = $mysqli->prepare("
        SELECT service_path, is_active
        FROM maintenance_mode
        WHERE service_path IN (
            '/ai/kingcong/text_to_speech?provider=elevenlabs',
            '/ai/kingcong/text_to_speech?provider=minimax',
            '/ai/kingcong/text_to_speech?provider=kingcong'
        )
    ");

    if ($stmt_check) {
        $stmt_check->execute();
        $res = $stmt_check->get_result();

        while ($row = $res->fetch_assoc()) {
            if ($row['is_active'] == 1) {
                if ($row['service_path'] === '/ai/kingcong/text_to_speech?provider=elevenlabs') $elevenlabs_down = true;
                if ($row['service_path'] === '/ai/kingcong/text_to_speech?provider=minimax') $minimax_down = true;
                if ($row['service_path'] === '/ai/kingcong/text_to_speech?provider=kingcong') $kingcong_down = true;
            }
        }
        $stmt_check->close();
    }
}

// 1. Logic chuyển hướng: Nếu cả 3 cùng sập -> Ra trang bảo trì
if ($elevenlabs_down && $minimax_down && $kingcong_down) {
    header("Location: /pages/maintenance.php?path=" . urlencode('/ai/kingcong/text_to_speech'));
    exit();
}

// ========== GET USER INFO ==========
$current_credits = 0;
$user_id_real = null;

if (isset($_SESSION['Users'])) {
    $user_identity = $_SESSION['Users'];
    
    if (isset($mysqli)) {
        $stmt = $mysqli->prepare("
            SELECT id, credits3
            FROM Users 
            WHERE taikhoan = ? OR google_id = ?
        ");
        $stmt->bind_param("ss", $user_identity, $user_identity);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res) {
            $user_id_real = $res['id'];
            $current_credits = intval($res['credits3']); // ← LẤY credits3
        }
        $stmt->close();
    }
}
// ========== CHECK BACKUP ELIGIBILITY ==========
$backup_eligible = true;
$backup_message = '';

if ($elevenlabs_down && $user_id_real) {
    $eligibility = $genai->canUseBackup($user_id_real, $current_credits);
    
    if (!$eligibility['allowed']) {
        $backup_eligible = false;
        $backup_message = $eligibility['message'];
    }
}
?>
<link rel="stylesheet" href="/pages/AI/css/tts.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/pages/AI/css/tts-light-theme.css?v=<?php echo time(); ?>">

<style>/* Nút xóa trong Modal Chi tiết */
.dh-delete-btn {
    background: transparent;
    border: 1px solid #333;
    color: #888;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.dh-delete-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.dh-delete-btn:active {
    transform: scale(0.95);
}</style>

<div class="tts-container" style="position: relative;">
    
    <!-- ========== PAGE LOADER ========== -->
    <div id="pageLoader" style="
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(11, 11, 11, 0.95);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
        border-radius: 16px;
    ">
        <div style="text-align: center;">
            <div class="spinner-border" style="width: 50px; height: 50px; color: #667eea;"></div>
            <div style="color: #fff; font-size: 16px; font-weight: 600; margin-top: 20px;"><?php echo $t['loading']; ?>...</div>
            <div style="color: #888; font-size: 13px; margin-top: 8px;"><?php echo $t['please_wait']; ?></div>
        </div>
    </div>

    <!-- ========== INPUT AREA ========== -->
    <div class="input-area" id="dropZone">
        <div id="inputLoader" class="loading-overlay">
            <div style="text-align:center; color:#fff;">
                <span class="spinner-border spinner-border-sm"></span>
                <div style="margin-top:10px; font-size:14px;"><?php echo $t['processing']; ?>...</div>
            </div>
        </div>
        
        <div class="textarea-wrapper">
            <div class="textarea-inner">
                <textarea id="txtInput" class="tts-textarea" placeholder=""></textarea>
                
                <div id="emptyState" class="empty-state">
                    <div class="es-title"><?php echo $t['enter_text']; ?></div>
                    <div class="es-subtitle"><?php echo $t['no_char_limit']; ?></div>
                    
                    <div class="es-tips" id="emptyTips">
                        <div class="es-tip">
                            <i class="bi bi-flag-fill" style="color: var(--error);"></i>
                            <span><?php echo $t['tip_vietnamese']; ?> <b>Minimax</b></span>
                        </div>
                        <div class="es-tip">
                            <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i>
                            <span>💡 <code class="es-code">&lt;break time="0.5s" /&gt;</code> <?php echo $t['tip_break']; ?></span>
                        </div>
                        <div class="es-tip">
                            <i class="bi bi-folder-fill" style="color: #3b82f6;"></i>
                            <span>📁 <?php echo $t['tip_drag_drop']; ?> <b>.txt, .srt</b> <?php echo $t['tip_here']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="input-footer">
           <div style="display: flex; align-items: center; gap: 12px;">
    <!-- Dropdown Upload -->
    <div style="position: relative;">
    <button class="upload-btn" onclick="toggleUploadDropdown()" id="uploadDropdownBtn">
        <i class="bi bi-upload"></i>
        <?php echo $t['upload']; ?>
        <i class="bi bi-chevron-up" id="uploadChevron" style="font-size: 10px; margin-left: 4px;"></i>
    </button>
    
    <style>
        .upload-option {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
        }
        .upload-option span {
            white-space: nowrap; 
            margin-left: 8px;
        }
    </style>
<style>
/* 🔥 DROPDOWN DOWNLOAD - TRẮNG ĐEN */
.dh-download-wrapper {
    position: relative;
    display: inline-block;
}

.dh-download-btn {
    background: transparent;
    border: 1px solid #333;
    color: #888;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.dh-download-btn:hover {
    border-color: #666;
    color: #fff;
    background: #222;
}

.dh-download-btn:active {
    transform: scale(0.95);
}

/* Menu Dropdown */
.dh-download-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    overflow: hidden;
    min-width: 240px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    z-index: 1000;
}

.dh-download-header {
    padding: 10px 12px;
    background: #0a0a0a;
    color: #666;
    font-size: 11px;
    font-weight: 500;
    border-bottom: 1px solid #222;
}

/* Item bình thường */
.dh-download-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    color: #999;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 13px;
    border-bottom: 1px solid #222;
    cursor: pointer;
}

.dh-download-item:last-child {
    border-bottom: none;
}

.dh-download-item:hover {
    background: #222;
    color: #fff;
}

.dh-download-item i {
    font-size: 16px;
    color: #ccc;
}

.dh-download-item:hover i {
    color: #fff;
}

/* 🔥 Item DISABLED (không có link) */
.dh-download-disabled {
    color: #444 !important;
    cursor: not-allowed !important;
    pointer-events: none;
    opacity: 0.4;
}

.dh-download-disabled i {
    color: #444 !important;
}

.dh-download-disabled:hover {
    background: transparent !important;
    color: #444 !important;
}
    /* Style cho nút trên Header Modal */
    .dh-header-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #888;
        width: 32px;
        height: 32px;
        border-radius: 8px; /* Bo góc nhẹ */
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .dh-header-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.3);
    }

    .dh-header-btn:active {
        transform: scale(0.95);
    }

    /* Animation xoay tròn khi bấm refresh */
    .spin-anim {
        animation: spin 1s infinite linear;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Delay Tooltip */
    .delay-tooltip {
        position: fixed;
        background: #0a0a0a;
        border: 1px solid #333;
        border-radius: 10px;
        padding: 12px 14px;
        min-width: 220px;
        z-index: 999999;
        box-shadow: 0 8px 30px rgba(0,0,0,0.6);
    }
    .delay-tooltip-arrow {
        position: absolute;
        bottom: -6px;
        left: 50%;
        width: 12px;
        height: 12px;
        background: #0a0a0a;
        border-right: 1px solid #333;
        border-bottom: 1px solid #333;
        transform: translateX(-50%) rotate(45deg);
    }

    /* Custom Dropdown cho Modal Phát âm từ */
    .pronunciation-modal .custom-dropdown {
        position: relative;
        width: 100%;
    }
    .pronunciation-modal .custom-dropdown-selected {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #111;
        border: 1px solid #333;
        border-radius: 8px;
        color: #fff;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pronunciation-modal .custom-dropdown-selected:hover {
        border-color: #555;
    }
    .pronunciation-modal .custom-dropdown-selected i {
        color: #888;
        font-size: 12px;
        transition: transform 0.2s;
    }
    .pronunciation-modal .custom-dropdown.open .custom-dropdown-selected i {
        transform: rotate(180deg);
    }
    .pronunciation-modal .custom-dropdown-options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: #0a0a0a;
        border: 1px solid #333;
        border-radius: 8px;
        overflow: hidden;
        z-index: 100;
        display: none;
    }
    .pronunciation-modal .custom-dropdown.open .custom-dropdown-options {
        display: block;
    }
    .pronunciation-modal .custom-dropdown-option {
        padding: 10px 12px;
        color: #ccc;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .pronunciation-modal .custom-dropdown-option:hover {
        background: #1a1a1a;
        color: #fff;
    }
    .pronunciation-modal .custom-dropdown-option.selected {
        background: #1a1a1a;
        color: #fff;
    }
    .pronunciation-modal .custom-dropdown-small {
        min-width: 120px;
        width: auto;
    }
    .pronunciation-modal .custom-dropdown-small .custom-dropdown-selected {
        padding: 6px 10px;
        font-size: 12px;
    }
    .pronunciation-modal .custom-dropdown-small .custom-dropdown-option {
        padding: 8px 10px;
        font-size: 12px;
    }
    .delay-quick-btn {
        padding: 5px 10px;
        background: #252525;
        border: 1px solid #444;
        border-radius: 6px;
        color: #eee;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .delay-quick-btn:hover {
        background: #333;
        border-color: #667eea;
    }
    .delay-add-btn {
        padding: 6px 14px;
        background: #111;
        border: 1px solid #444;
        border-radius: 6px;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    .delay-add-btn:hover {
        background: #222;
        border-color: #555;
    }

    /* ========== MODAL PHÁT ÂM TỪ ========== */
    .pronunciation-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pronunciation-modal-content {
        background: #0a0a0a;
        border: 1px solid #222;
        border-radius: 12px;
        width: 95%;
        max-width: 550px;
        max-height: 85vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .pronunciation-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #222;
        background: #0a0a0a;
    }
    .pronunciation-modal-header h3 {
        margin: 0;
        font-size: 15px;
        color: #fff;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pronunciation-close-btn {
        background: none;
        border: none;
        color: #555;
        font-size: 16px;
        cursor: pointer;
        padding: 5px;
        transition: color 0.2s;
    }
    .pronunciation-close-btn:hover {
        color: #fff;
    }
    .pronunciation-modal-body {
        padding: 20px;
        overflow-y: auto;
        background: #0a0a0a;
    }
    .pronunciation-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #1a1a1a;
    }
    .pronunciation-form .full-width {
        grid-column: 1 / -1;
    }
    .pronunciation-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .pronunciation-input-group label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .pronunciation-input-group input,
    .pronunciation-input-group select {
        padding: 10px 12px;
        background: #111;
        border: 1px solid #222;
        border-radius: 6px;
        color: #fff;
        font-size: 13px;
        outline: none;
        transition: all 0.2s;
    }
    .pronunciation-input-group select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 35px;
        cursor: pointer;
    }
    .pronunciation-input-group select option {
        background: #111;
        color: #fff;
        padding: 10px;
    }
    .pronunciation-input-group input::placeholder {
        color: #444;
    }
    .pronunciation-input-group input:focus,
    .pronunciation-input-group select:focus {
        border-color: #555;
        background: #151515;
    }
    .pronunciation-input-group select:hover {
        border-color: #444;
    }
    .pronunciation-add-btn {
        padding: 10px 20px;
        background: #fff;
        border: none;
        border-radius: 6px;
        color: #000;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .pronunciation-add-btn:hover {
        background: #ddd;
    }
    .pronunciation-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    .pronunciation-filter label {
        font-size: 12px;
        color: #555;
    }
    .pronunciation-filter select {
        padding: 6px 10px;
        background: #111;
        border: 1px solid #222;
        border-radius: 6px;
        color: #fff;
        font-size: 12px;
        outline: none;
    }
    .pronunciation-history-header {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }
    .pronunciation-count {
        color: #444;
    }
    .pronunciation-list {
        max-height: 300px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .pronunciation-list::-webkit-scrollbar {
        width: 4px;
    }
    .pronunciation-list::-webkit-scrollbar-track {
        background: #0a0a0a;
    }
    .pronunciation-list::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 2px;
    }
    .pronunciation-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        background: #111;
        border: 1px solid #1a1a1a;
        border-radius: 6px;
        transition: border-color 0.2s;
    }
    .pronunciation-item:hover {
        border-color: #333;
    }
    .pronunciation-item-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .pronunciation-item-word {
        font-size: 13px;
        color: #fff;
        font-weight: 500;
    }
    .pronunciation-item-word span {
        color: #555;
        font-weight: 400;
    }
    .pronunciation-item-lang {
        font-size: 10px;
        padding: 2px 6px;
        background: #1a1a1a;
        border-radius: 3px;
        color: #555;
        display: inline-block;
        width: fit-content;
    }
    .pronunciation-item-delete {
        background: none;
        border: none;
        color: #333;
        cursor: pointer;
        padding: 5px;
        font-size: 14px;
        transition: color 0.2s;
    }
    .pronunciation-item-delete:hover {
        color: #fff;
    }
    .pronunciation-empty {
        text-align: center;
        color: #444;
        font-size: 12px;
        padding: 30px 20px;
    }
</style>
<style>/* 🔥 LOADING - ĐƠN GIẢN TRẮNG ĐEN */
.dh-loading-container {
    padding: 80px 20px;
    text-align: center;
}

.dh-loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e0e0e0;
    border-top-color: #333;
    border-radius: 50%;
    margin: 0 auto 16px;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.dh-loading-text {
    font-size: 14px;
    color: #666;
    font-weight: 400;
}

/* LOADING CHO SCROLL */
.dh-loading-more {
    text-align: center;
    padding: 30px 20px;
}

.dh-loading-more-spinner {
    width: 28px;
    height: 28px;
    border: 2px solid #e0e0e0;
    border-top-color: #333;
    border-radius: 50%;
    margin: 0 auto 10px;
    animation: spin 0.8s linear infinite;
}

.dh-loading-more-text {
    font-size: 13px;
    color: #999;
}
/* 🔥 NÚT REFRESH CÓ TEXT */
.dh-refresh-with-text {
    width: auto !important; /* Bỏ width cố định 32px */
    padding: 0 12px;
    gap: 6px;
    display: flex;
    align-items: center;
}

.dh-refresh-with-text span {
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}
/* ========================================
   🗑️ NÚT XÓA VĂN BẢN
   ======================================== */
#btnClearText:hover {
    border-color: #ef4444 !important;
    color: #ef4444 !important;
    background: rgba(239, 68, 68, 0.05) !important;
}

#btnClearText:active {
    transform: scale(0.95);
}

/* Disable state */
#btnClearText:disabled {
    opacity: 0.3 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}
</style>
    <div id="uploadDropdown" style="
        position: absolute;
        bottom: 100%;
        left: 0;
        margin-bottom: 6px;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        overflow: hidden;
        display: none;
        z-index: 100;
        min-width: 250px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    ">
        <div class="upload-option" onclick="$('#fileInput').click(); $('#uploadDropdown').hide(); updateChevron();">
            <i class="bi bi-file-earmark-text"></i>
            <span><?php echo $t['upload_file']; ?></span>
        </div>
        <div class="upload-option" onclick="$('#folderInput').click(); $('#uploadDropdown').hide(); updateChevron();">
            <i class="bi bi-folder2-open"></i>
            <span><?php echo $t['upload_folder']; ?></span>
        </div>
    </div>
</div>
    
<!-- ========== 🔥 NÚT XÓA VĂN BẢN (MỚI THÊM) ========== -->
    <button id="btnClearText" onclick="clearTextInput()"
            class="upload-btn"
            style="
                background: transparent;
                border: 1px solid #444;
                color: #888;
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            "
            title="<?php echo $lang === 'vi' ? 'Xóa toàn bộ văn bản' : 'Clear all text'; ?>">
        <i class="bi bi-trash" style="font-size: 14px;"></i>
        <span><?php echo $lang === 'vi' ? 'Xóa' : 'Clear'; ?></span>
    </button>

    <!-- ========== 🔥 NÚT CHUẨN HÓA VĂN BẢN ========== -->
    <button id="btnTextNormalize" onclick="openTextNormalizeSidebar()"
            class="upload-btn"
            style="
                background: transparent;
                border: 1px solid #444;
                color: #888;
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            "
            onmouseover="this.style.borderColor='#10b981'; this.style.color='#10b981'"
            onmouseout="this.style.borderColor='#444'; this.style.color='#888'"
            title="<?php echo $lang === 'vi' ? 'Chuẩn hóa văn bản cho TTS' : 'Normalize text for TTS'; ?>">
        <i class="bi bi-text-paragraph" style="font-size: 14px;"></i>
        <span><?php echo $lang === 'vi' ? 'Chuẩn hóa' : 'Normalize'; ?></span>
    </button>
    
    <input type="file" id="fileInput" accept=".txt,.srt" multiple style="display: none;">
    <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
    
        <!-- ========== 🔥 NÚT CHUẨN HÓA TIẾNG VIỆT (11Labs & Minimax) ========== -->
    <button id="btnNormalizeVN" onclick="normalizeVietnamese()"
            class="upload-btn"
            style="
                background: transparent;
                border: 1px solid #444;
                color: #888;
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            "
            onmouseover="this.style.borderColor='#667eea'; this.style.color='#667eea'"
            onmouseout="this.style.borderColor='#444'; this.style.color='#888'"
            title="<?php echo $lang === 'vi' ? 'Chuyển đổi để AI đọc chuẩn (ai→aai, im→yim)' : 'Normalize Vietnamese pronunciation'; ?>">
        <i class="bi bi-spellcheck" style="font-size: 14px;"></i>
        <span><?php echo $lang === 'vi' ? 'Chuẩn hóa Tiếng Việt' : 'Normalize Vietnamese'; ?></span>
    </button>

    <!-- ========== 🔥 NÚT PHÁT ÂM TỪ (KingCong only) ========== -->
    <button id="btnPronunciation" onclick="togglePronunciationModal()"
            class="upload-btn"
            style="
                background: transparent;
                border: 1px solid #444;
                color: #888;
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                display: none;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            "
            onmouseover="this.style.borderColor='#667eea'; this.style.color='#667eea'"
            onmouseout="this.style.borderColor='#444'; this.style.color='#888'"
            title="<?php echo $lang === 'vi' ? 'Thêm cách phát âm cho từ viết tắt' : 'Add pronunciation for abbreviations'; ?>">
        <i class="bi bi-journal-text" style="font-size: 14px;"></i>
        <span><?php echo $lang === 'vi' ? 'Phát âm từ' : 'Pronunciation'; ?></span>
    </button>
    
    <input type="file" id="fileInput" accept=".txt,.srt" multiple style="display: none;">
    <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
    
    <div id="fileNameDisplay"></div>
</div>


            <div class="stats-display">
    <!-- 🔥 CHARACTER COUNTER - ĐƠN GIẢN TRẮNG ĐEN -->
    <div style="
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
    " id="charDisplay">
        <i class="bi bi-file-text" style="font-size: 14px; color: #888;"></i>
        <span id="charCount" style="color: #fff;">0</span>
        <span style="font-size: 11px; color: #666;"><?php echo $t['chars']; ?></span>
    </div>
    
    <span style="color: #333;">|</span>
    
    <!-- Credits đã tính với Tooltip -->
    <div style="position: relative; display: inline-block;">
        <span style="cursor: help;" id="creditsTrigger">
            <span style="color: #fbbf24; font-weight: 600;" id="estimatedCostDisplay">
                <span id="estimatedCost">0</span>
            </span>
        </span>
        
        <!-- Tooltip -->
        <div id="creditsTooltip" style="
            display: none;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 10px;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            min-width: 200px;
            color: #fff;
        ">
            <div style="font-size: 11px; color: #888; margin-bottom: 4px;"><?php echo $t['processing_fee']; ?></div>
            <div style="font-size: 12px; color: #ccc;">
                • <?php echo $t['processing_fee']; ?>: <span style="color: #fff; font-weight: 600;">x1.12</span>
            </div>
            <div style="font-size: 12px; color: #ccc; margin-top: 2px;" id="srtFeeInfo">
                • <?php echo $t['srt_fee']; ?>: <span style="color: #fff; font-weight: 600;">x1.15</span>
            </div>
        </div>
    </div>
    
    <span style="color: #333;">|</span>
    
    <!-- Credits hiện tại -->
    <span class="credits-display">
        <i class="bi bi-coin"></i>
        <span id="userCredits"><?php echo number_format($current_credits); ?></span>
    </span>
</div>
        </div>
    </div>

    <!-- ========== SIDEBAR PANEL ========== -->
    <div class="sidebar-panel">
        <div class="top-tabs">
    <!-- Tab buttons bên TRÁI -->
    <div style="display: flex; gap: 8px;">
        <button class="tab-btn active" id="btnSettings" onclick="switchTab('settings')">
            <i class="bi bi-sliders"></i>
            <span class="tab-text"><?php echo $t['settings']; ?></span>
        </button>
        <button class="tab-btn" id="btnHistory" onclick="switchTab('history')">
            <i class="bi bi-clock-history"></i>
            <span class="tab-text"><?php echo $t['history']; ?></span>
        </button>
    </div>
    
    <!-- Provider Selector bên PHẢI -->
    <div id="providerWrapper" class="provider-dropdown-wrapper" style="margin: 0;">
        <div class="provider-dropdown-btn" onclick="toggleProviderDropdown()">
            <div class="current-provider">
                <img src="https://help.elevenlabs.io/hc/theming_assets/01HZQ08B6SDY5X53YN9ABG4B99" id="currentProviderLogo" class="provider-logo-btn" alt="">
                <span id="currentProviderName">Elevenlabs</span>
            </div>
            <i class="bi bi-chevron-down" id="providerDropdownIcon"></i>
        </div>
        
        <div class="provider-dropdown-menu" id="providerDropdown">
            <div class="provider-option active" data-provider="elevenlabs" onclick="selectProvider('elevenlabs')">
                <img src="https://help.elevenlabs.io/hc/theming_assets/01HZQ08B6SDY5X53YN9ABG4B99" class="provider-logo" alt="ElevenLabs">
                <div class="provider-info">
                    <div class="provider-name">Elevenlabs</div>
                    <div class="provider-desc"><?php echo $lang === 'vi' ? 'Giọng đọc tự nhiên và nhiều người dùng hơn.' : 'Natural voice with more users.'; ?></div>
                </div>
                <i class="bi bi-check-lg check-icon"></i>
            </div>
            
            <div class="provider-option" data-provider="minimax" onclick="selectProvider('minimax')">
                <img src="https://registry.npmmirror.com/@lobehub/icons-static-png/latest/files/dark/minimax-color.png" class="provider-logo" alt="Minimax">
                <div class="provider-info">
                    <div class="provider-name">Minimax</div>
                    <div class="provider-desc"><?php echo $lang === 'vi' ? 'Nói tốt tiếng Việt. Có thể sử dụng giọng nhân bản.' : 'Good Vietnamese. Support voice cloning.'; ?></div>
                </div>
                <i class="bi bi-check-lg check-icon"></i>
            </div>

            <div class="provider-option" data-provider="kingcong" onclick="selectProvider('kingcong')">
                <img src="../../assets/media/logos/Kingkong.jpg" class="provider-logo" alt="KingCong">
                <div class="provider-info">
                    <div class="provider-name">KingCong</div>
                    <div class="provider-desc"><?php echo $lang === 'vi' ? 'Giọng đọc KingCong chất lượng cao (voice clone).' : 'High quality KingCong voices (voice clone).'; ?></div>
                </div>
                <i class="bi bi-check-lg check-icon"></i>
            </div>
        </div>
    </div>

    <div id="historyActions" style="display: none; gap: 8px;">
        <button class="tab-btn" onclick="refreshHistory()" style="background: #1a1a1a; border: 1px solid #333; color: #fff;">
            <i class="bi bi-arrow-clockwise"></i>
            <span class="tab-text"><?php echo $t['refresh']; ?></span>
        </button>
        
        <button class="tab-btn" onclick="openDetailedHistory()" style="background: #1a1a1a; border: 1px solid #333; color: #fff;">
    <i class="bi bi-list-columns-reverse"></i>
    <span class="tab-text"><?php echo $t['details']; ?></span>
</button>
    </div>
</div>

       <div id="viewSettings" class="sidebar-content show">
    <div class="setting-group">
        <label><?php echo $t['choose_voice']; ?></label>
        <div id="voiceSelectorBtn" onclick="openVoiceModal()" style="
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    background: #111; 
    border: 1px solid #333; 
    padding: 10px 15px; 
    border-radius: 8px; 
    cursor: pointer;">

    <div id="selectedVoiceName" style="font-weight: 500; color: #fff;"><?php echo $t['select_voice']; ?></div>

    <div id="minimax-badge" style="font-size: 11px; display: none;"></div>

    <i class="bi bi-chevron-right" style="color: #666; margin-left: 10px;"></i>
</div>
        <input type="hidden" id="voiceIdVal">
    </div>

    <div id="minimax-settings">
                <div class="setting-group">
                    <label><?php echo $t['select_language']; ?></label>
    <div class="lang-selector-wrapper" style="position: relative;">
        <div class="lang-selector-dropdown" onclick="toggleLangDropdown()">
            <span id="selectedLang"><?php echo $t['auto_detect']; ?></span>
            <i class="bi bi-chevron-down" id="langDropdownIcon"></i>
        </div>
                        
                        <div class="lang-dropdown-menu" id="langDropdown">
                            <div class="lang-option active" data-lang="Auto" onclick="selectLanguage('Auto', '<?php echo $t['auto_detect']; ?>')">
                                <?php echo $t['auto_detect']; ?>
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="English" onclick="selectLanguage('English', 'English')">
                                English
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Vietnamese" onclick="selectLanguage('Vietnamese', 'Vietnamese')">
                                Vietnamese
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Arabic" onclick="selectLanguage('Arabic', 'Arabic')">
                                Arabic
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Cantonese" onclick="selectLanguage('Cantonese', 'Cantonese')">
                                Cantonese
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Chinese" onclick="selectLanguage('Chinese', 'Chinese (Mandarin)')">
                                Chinese (Mandarin)
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Dutch" onclick="selectLanguage('Dutch', 'Dutch')">
                                Dutch
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="French" onclick="selectLanguage('French', 'French')">
                                French
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="German" onclick="selectLanguage('German', 'German')">
                                German
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Indonesian" onclick="selectLanguage('Indonesian', 'Indonesian')">
                                Indonesian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Italian" onclick="selectLanguage('Italian', 'Italian')">
                                Italian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Japanese" onclick="selectLanguage('Japanese', 'Japanese')">
                                Japanese
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Korean" onclick="selectLanguage('Korean', 'Korean')">
                                Korean
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Portuguese" onclick="selectLanguage('Portuguese', 'Portuguese')">
                                Portuguese
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Russian" onclick="selectLanguage('Russian', 'Russian')">
                                Russian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Spanish" onclick="selectLanguage('Spanish', 'Spanish')">
                                Spanish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Turkish" onclick="selectLanguage('Turkish', 'Turkish')">
                                Turkish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Ukrainian" onclick="selectLanguage('Ukrainian', 'Ukrainian')">
                                Ukrainian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Thai" onclick="selectLanguage('Thai', 'Thai')">
                                Thai
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Polish" onclick="selectLanguage('Polish', 'Polish')">
                                Polish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Romanian" onclick="selectLanguage('Romanian', 'Romanian')">
                                Romanian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Greek" onclick="selectLanguage('Greek', 'Greek')">
                                Greek
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Czech" onclick="selectLanguage('Czech', 'Czech')">
                                Czech
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Finnish" onclick="selectLanguage('Finnish', 'Finnish')">
                                Finnish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Hindi" onclick="selectLanguage('Hindi', 'Hindi')">
                                Hindi
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Bulgarian" onclick="selectLanguage('Bulgarian', 'Bulgarian')">
                                Bulgarian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Danish" onclick="selectLanguage('Danish', 'Danish')">
                                Danish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Hebrew" onclick="selectLanguage('Hebrew', 'Hebrew')">
                                Hebrew
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Malay" onclick="selectLanguage('Malay', 'Malay')">
                                Malay
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Persian" onclick="selectLanguage('Persian', 'Persian')">
                                Persian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Slovak" onclick="selectLanguage('Slovak', 'Slovak')">
                                Slovak
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Swedish" onclick="selectLanguage('Swedish', 'Swedish')">
                                Swedish
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Croatian" onclick="selectLanguage('Croatian', 'Croatian')">
                                Croatian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Filipino" onclick="selectLanguage('Filipino', 'Filipino')">
                                Filipino
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Hungarian" onclick="selectLanguage('Hungarian', 'Hungarian')">
                                Hungarian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Norwegian" onclick="selectLanguage('Norwegian', 'Norwegian')">
                                Norwegian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Slovenian" onclick="selectLanguage('Slovenian', 'Slovenian')">
                                Slovenian
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Catalan" onclick="selectLanguage('Catalan', 'Catalan')">
                                Catalan
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Nynorsk" onclick="selectLanguage('Nynorsk', 'Nynorsk')">
                                Nynorsk
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Tamil" onclick="selectLanguage('Tamil', 'Tamil')">
                                Tamil
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                            <div class="lang-option" data-lang="Afrikaans" onclick="selectLanguage('Afrikaans', 'Afrikaans')">
                                Afrikaans
                                <i class="bi bi-check-lg check-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

        <div class="setting-group">
            <label><?php echo $t['select_model']; ?></label>
            <div style="position: relative;">
                <div class="custom-select" id="minimaxModelBtn" onclick="toggleMinimaxModelDropdown()" style="cursor: pointer;">
                    <span id="selectedMinimaxModel">Speech HD 2.6</span>
                    <i class="bi bi-chevron-down" id="minimaxModelIcon" style="margin-left: auto;"></i>
                </div>
                <div class="provider-dropdown-menu" id="minimaxModelDropdown" style="max-height: 250px; overflow-y: auto;"></div>
            </div>
        </div>
        
        <div class="slider-container">
    <div class="slider-header"><span><?php echo $t['speed']; ?>: <span id="speedVal">1.00</span></span></div>
    <input type="range" id="speed" min="0.5" max="2.0" step="0.01" value="1.0">
</div>
        
        <div class="slider-container">
            <div class="slider-header"><span><?php echo $t['pitch']; ?>: <span id="pitchVal">0</span></span></div>
            <input type="range" id="pitch" min="-12" max="12" step="1" value="0">
        </div>
        
        <div class="slider-container">
    <div class="slider-header"><span><?php echo $t['volume']; ?>: <span id="volVal">1.00</span></span></div>
    <input type="range" id="vol" min="0.01" max="10.0" step="0.01" value="1.0">
</div>
        </div>

    <div id="elevenlabs-settings" class="hidden">
        <div class="setting-group">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <label><?php echo $t['select_model']; ?></label>
            </div>
            <div class="custom-select" id="elevenModelBtn" onclick="showModelDetails()" style="cursor: pointer;">
                <span id="selectedModelName"><?php echo $t['loading']; ?>...</span>
                <i class="bi bi-chevron-right" style="margin-left: auto; font-size: 14px;"></i>
            </div>
        </div>
        
        <div class="slider-container" id="slider-speed">
    <div class="slider-header"><span><?php echo $t['speed']; ?>: <span id="elevenSpeedVal">1.00</span></span></div>
    <input type="range" id="elevenSpeed" min="0.7" max="1.2" step="0.01" value="1.0">
</div>
        
        <div class="slider-container" id="slider-stability">
            <div class="slider-header"><span><?php echo $t['stability']; ?>: <span id="stabilityVal">50%</span></span></div>
            <input type="range" id="stability" min="0" max="100" step="1" value="50">
        </div>
        
        <div class="slider-container" id="slider-similarity">
            <div class="slider-header"><span><?php echo $t['similarity']; ?>: <span id="similarityVal">75%</span></span></div>
            <input type="range" id="similarity" min="0" max="100" step="1" value="75">
        </div>
        
        <div class="slider-container" id="slider-style">
            <div class="slider-header"><span><?php echo $t['style_exaggeration']; ?>: <span id="styleVal">0%</span></span></div>
            <input type="range" id="style" min="0" max="100" step="1" value="0">
        </div>
        
        <div class="toggle-row" id="toggle-boost">
            <div><?php echo $t['speaker_boost']; ?></div>
            <label class="toggle-switch">
                <input type="checkbox" id="boostCheck" checked>
                <span class="toggle-slider"></span>
            </label>
        </div>
        </div>

    <!-- ========== KINGCONG SETTINGS ========== -->
    <div id="kingcong-settings" class="hidden">
        <div class="setting-group">
            <label><?php echo $t['select_model']; ?></label>
            <div class="custom-select" id="kingcongModelBtn" onclick="showKingCongModelDetails()" style="cursor: pointer;">
                <span id="selectedKingCongModel">KingCong Speech V1</span>
                <i class="bi bi-chevron-right" style="margin-left: auto;"></i>
            </div>
        </div>

        <div class="slider-container">
            <div class="slider-header"><span><?php echo $t['speed']; ?>: <span id="kingcongSpeedVal">1.00</span></span></div>
            <input type="range" id="kingcongSpeed" min="0.5" max="1.5" step="0.01" value="1.0">
        </div>

        <div class="slider-container">
            <div class="slider-header"><span><?php echo $t['volume']; ?>: <span id="kingcongVolumeVal">1.0</span></span></div>
            <input type="range" id="kingcongVolume" min="0.1" max="2.0" step="0.1" value="1.0">
        </div>

        <div class="slider-container">
            <div class="slider-header"><span><?php echo $t['pitch']; ?>: <span id="kingcongPitchVal">1.0</span></span></div>
            <input type="range" id="kingcongPitch" min="0.5" max="1.5" step="0.1" value="1.0">
        </div>

        <!-- ========== ĐỘ TRỄ DẤU CÂU (BETA) - Chỉ hiện cho voice clone tiếng Việt ========== -->
        <div class="punctuation-delay-section" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #222;">
            <!-- Toggle Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label class="toggle-switch" style="margin: 0;">
                        <input type="checkbox" id="punctuationDelayToggle" onchange="togglePunctuationDelay()">
                        <span class="toggle-slider"></span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; color: #eee; font-weight: 500;">
                            <?php echo $lang === 'vi' ? 'Độ trễ dấu câu' : 'Punctuation Delay'; ?>
                        </span>
                        <span style="background: #f59e0b; color: #000; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">BETA</span>
                    </div>
                </div>
                <i class="bi bi-info-circle" style="color: #888; cursor: help;"
                   title="<?php echo $lang === 'vi' ? 'Tự động thêm khoảng dừng sau các dấu câu' : 'Automatically add pauses after punctuation marks'; ?>"></i>
            </div>

            <!-- Warning Tooltip (hiện khi bật toggle) -->
            <div id="punctuationDelayWarning" style="display: none; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b; font-size: 14px; margin-top: 1px;"></i>
                    <span style="font-size: 12px; color: #ccc; line-height: 1.4;">
                        <?php echo $lang === 'vi'
                            ? 'Tính năng này đang trong giai đoạn thử nghiệm, có thể không hoạt động ổn định.'
                            : 'This feature is in beta testing and may not work consistently.'; ?>
                    </span>
                </div>
            </div>

            <!-- 4 Sliders Container -->
            <div id="punctuationDelaySliders" style="display: none;">
                <!-- Slider 1: Dấu chấm (.) -->
                <div class="slider-container" style="margin-bottom: 12px;">
                    <div class="slider-header">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <?php echo $lang === 'vi' ? 'Dấu chấm' : 'Period'; ?> (.)
                            <i class="bi bi-question-circle" style="color: #666; font-size: 12px; cursor: help;"
                               title="<?php echo $lang === 'vi' ? 'Điều chỉnh độ dài dừng sau dấu chấm (.)' : 'Adjust pause duration after period (.)'; ?>"></i>
                            : <span id="punctPeriodVal">0.50s</span>
                        </span>
                    </div>
                    <input type="range" id="punctPeriod" min="0.10" max="2.00" step="0.05" value="0.50" oninput="updatePunctSlider('Period')">
                </div>

                <!-- Slider 2: Dấu phẩy (,) -->
                <div class="slider-container" style="margin-bottom: 12px;">
                    <div class="slider-header">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <?php echo $lang === 'vi' ? 'Dấu phẩy' : 'Comma'; ?> (,)
                            <i class="bi bi-question-circle" style="color: #666; font-size: 12px; cursor: help;"
                               title="<?php echo $lang === 'vi' ? 'Điều chỉnh độ dài dừng sau dấu phẩy (,)' : 'Adjust pause duration after comma (,)'; ?>"></i>
                            : <span id="punctCommaVal">0.30s</span>
                        </span>
                    </div>
                    <input type="range" id="punctComma" min="0.10" max="2.00" step="0.05" value="0.30" oninput="updatePunctSlider('Comma')">
                </div>

                <!-- Slider 3: Dấu chấm than (!) -->
                <div class="slider-container" style="margin-bottom: 12px;">
                    <div class="slider-header">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <?php echo $lang === 'vi' ? 'Dấu chấm than' : 'Exclamation'; ?> (!)
                            <i class="bi bi-question-circle" style="color: #666; font-size: 12px; cursor: help;"
                               title="<?php echo $lang === 'vi' ? 'Điều chỉnh độ dài dừng sau dấu chấm than (!)' : 'Adjust pause duration after exclamation mark (!)'; ?>"></i>
                            : <span id="punctExclamationVal">0.50s</span>
                        </span>
                    </div>
                    <input type="range" id="punctExclamation" min="0.10" max="2.00" step="0.05" value="0.50" oninput="updatePunctSlider('Exclamation')">
                </div>

                <!-- Slider 4: Dấu hỏi (?) -->
                <div class="slider-container" style="margin-bottom: 12px;">
                    <div class="slider-header">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <?php echo $lang === 'vi' ? 'Dấu hỏi' : 'Question'; ?> (?)
                            <i class="bi bi-question-circle" style="color: #666; font-size: 12px; cursor: help;"
                               title="<?php echo $lang === 'vi' ? 'Điều chỉnh độ dài dừng sau dấu hỏi (?)' : 'Adjust pause duration after question mark (?)'; ?>"></i>
                            : <span id="punctQuestionVal">0.50s</span>
                        </span>
                    </div>
                    <input type="range" id="punctQuestion" min="0.10" max="2.00" step="0.05" value="0.50" oninput="updatePunctSlider('Question')">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #222; margin-top: 10px;">
            <!-- Button chèn khoảng dừng (bên trái) -->
            <div style="position: relative;">
                <button class="reset-btn" id="delayBtn" onclick="toggleDelayTooltip()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                    <i class="bi bi-pause-circle"></i> <?php echo $lang === 'vi' ? 'Khoảng dừng' : 'Delay'; ?>
                </button>
                <div id="delayTooltip" class="delay-tooltip" style="display: none;">
                    <div class="delay-tooltip-arrow"></div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 8px;"><?php echo $lang === 'vi' ? 'Chọn nhanh:' : 'Quick select:'; ?></div>
                    <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                        <button type="button" class="delay-quick-btn" onclick="insertDelay(0.5)">0.5s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelay(1)">1s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelay(2)">2s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelay(3)">3s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelay(5)">5s</button>
                    </div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 6px;"><?php echo $lang === 'vi' ? 'Hoặc nhập:' : 'Or enter:'; ?></div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <input type="number" id="delayInput" min="0.1" max="10" step="0.1" value="1" style="width: 70px; padding: 6px 8px; background: #111; border: 1px solid #444; border-radius: 6px; color: #fff; font-size: 13px;">
                        <span style="color: #888; font-size: 13px;">s</span>
                        <button type="button" class="delay-add-btn" onclick="insertDelayFromInput()"><?php echo $lang === 'vi' ? 'Thêm' : 'Add'; ?></button>
                    </div>
                </div>
            </div>

            <!-- Button reset (bên phải) -->
            <button class="reset-btn" onclick="resetCurrentSettings()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                <i class="bi bi-arrow-counterclockwise"></i> <?php echo $t['reset']; ?>
            </button>
        </div>
    </div>

<!-- ========== MODAL PHÁT ÂM TỪ ========== -->
<div id="pronunciationModal" class="pronunciation-modal" style="display: none;">
    <div class="pronunciation-modal-content">
        <div class="pronunciation-modal-header">
            <h3><?php echo $lang === 'vi' ? 'Phát âm từ' : 'Pronunciation'; ?></h3>
            <button class="pronunciation-close-btn" onclick="togglePronunciationModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="pronunciation-modal-body">
            <!-- Form thêm từ -->
            <div class="pronunciation-form">
                <div class="pronunciation-input-group">
                    <label><?php echo $lang === 'vi' ? 'Từ gốc' : 'Word'; ?></label>
                    <input type="text" id="pronWordInput" placeholder="UBND, CEO, AI...">
                </div>
                <div class="pronunciation-input-group">
                    <label><?php echo $lang === 'vi' ? 'Phát âm' : 'Say as'; ?></label>
                    <input type="text" id="pronPronunciationInput" placeholder="<?php echo $lang === 'vi' ? 'ủy ban nhân dân' : 'pronunciation'; ?>">
                </div>
                <div class="pronunciation-input-group">
                    <label><?php echo $lang === 'vi' ? 'Ngôn ngữ' : 'Language'; ?></label>
                    <div class="custom-dropdown" id="pronLangDropdown">
                        <div class="custom-dropdown-selected" onclick="togglePronLangDropdown()">
                            <span id="pronLangText"><?php echo $t['pron_lang_vi']; ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="custom-dropdown-options" id="pronLangOptions">
                            <div class="custom-dropdown-option selected" data-value="vi" onclick="selectPronLang('vi', '<?php echo $t['pron_lang_vi']; ?>')"><?php echo $t['pron_lang_vi']; ?></div>
                            <div class="custom-dropdown-option" data-value="en" onclick="selectPronLang('en', '<?php echo $t['pron_lang_en']; ?>')"><?php echo $t['pron_lang_en']; ?></div>
                            <div class="custom-dropdown-option" data-value="fr" onclick="selectPronLang('fr', '<?php echo $t['pron_lang_fr']; ?>')"><?php echo $t['pron_lang_fr']; ?></div>
                            <div class="custom-dropdown-option" data-value="zh" onclick="selectPronLang('zh', '<?php echo $t['pron_lang_zh']; ?>')"><?php echo $t['pron_lang_zh']; ?></div>
                        </div>
                    </div>
                    <input type="hidden" id="pronLanguageSelect" value="vi">
                </div>
                <div class="pronunciation-input-group">
                    <label>&nbsp;</label>
                    <button class="pronunciation-add-btn" onclick="addPronunciation()">
                        <?php echo $lang === 'vi' ? 'Thêm' : 'Add'; ?>
                    </button>
                </div>
            </div>

            <!-- Filter + History Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div class="pronunciation-history-header" style="margin: 0;">
                    <span><?php echo $lang === 'vi' ? 'Danh sách' : 'List'; ?></span>
                    <span class="pronunciation-count">(<span id="pronCount">0</span>)</span>
                </div>
                <div class="custom-dropdown custom-dropdown-small" id="pronFilterDropdown">
                    <div class="custom-dropdown-selected" onclick="togglePronFilterDropdown()">
                        <span id="pronFilterText"><?php echo $lang === 'vi' ? 'Tất cả' : 'All'; ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="custom-dropdown-options" id="pronFilterOptions">
                        <div class="custom-dropdown-option selected" data-value="all" onclick="selectPronFilter('all', '<?php echo $lang === 'vi' ? 'Tất cả' : 'All'; ?>')"><?php echo $lang === 'vi' ? 'Tất cả' : 'All'; ?></div>
                        <div class="custom-dropdown-option" data-value="vi" onclick="selectPronFilter('vi', '<?php echo $t['pron_lang_vi']; ?>')"><?php echo $t['pron_lang_vi']; ?></div>
                        <div class="custom-dropdown-option" data-value="en" onclick="selectPronFilter('en', '<?php echo $t['pron_lang_en']; ?>')"><?php echo $t['pron_lang_en']; ?></div>
                        <div class="custom-dropdown-option" data-value="fr" onclick="selectPronFilter('fr', '<?php echo $t['pron_lang_fr']; ?>')"><?php echo $t['pron_lang_fr']; ?></div>
                        <div class="custom-dropdown-option" data-value="zh" onclick="selectPronFilter('zh', '<?php echo $t['pron_lang_zh']; ?>')"><?php echo $t['pron_lang_zh']; ?></div>
                    </div>
                </div>
                <input type="hidden" id="pronFilterSelect" value="all">
            </div>

            <!-- Lịch sử -->
            <div class="pronunciation-list" id="pronunciationList">
                <div class="pronunciation-empty"><?php echo $lang === 'vi' ? 'Chưa có từ nào được thêm' : 'No words added yet'; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ========== SIDEBAR CHUẨN HÓA VĂN BẢN ========== -->
<style>
/* ========== TEXT NORMALIZE SIDEBAR ========== */
.text-normalize-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: none;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.text-normalize-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    max-width: 900px;
    height: 100%;
    background: #0a0a0a;
    border-left: 1px solid #222;
    z-index: 10001;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.text-normalize-sidebar.show {
    transform: translateX(0);
}

/* Header */
.tn-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #0a0a0a;
    border-bottom: 1px solid #222;
    flex-shrink: 0;
}

.tn-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tn-cancel-btn {
    background: transparent;
    border: 1px solid #333;
    color: #888;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.tn-cancel-btn:hover {
    border-color: #555;
    color: #fff;
}

.tn-header-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #888;
}

.tn-header-title .tn-char-count {
    color: #10b981;
    font-weight: 600;
}

.tn-header-title i {
    color: #555;
}

.tn-apply-btn {
    background: #10b981;
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
}

.tn-apply-btn:hover {
    background: #059669;
}

.tn-apply-btn:disabled {
    background: #333;
    color: #666;
    cursor: not-allowed;
}

/* Body */
.tn-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* Settings Panel - Bên trái */
.tn-settings {
    width: 320px;
    min-width: 320px;
    background: #0f0f0f;
    border-right: 1px solid #222;
    padding: 20px;
    overflow-y: auto;
    flex-shrink: 0;
}

.tn-settings-title {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
}

.tn-option-group {
    margin-bottom: 24px;
}

.tn-option-group-title {
    font-size: 12px;
    color: #888;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tn-option-group-title i {
    font-size: 14px;
    color: #555;
}

.tn-option {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px;
    background: #111;
    border: 1px solid #1a1a1a;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.2s;
}

.tn-option:hover {
    border-color: #333;
}

.tn-option-info {
    flex: 1;
    padding-right: 12px;
}

.tn-option-name {
    font-size: 13px;
    color: #fff;
    font-weight: 500;
    margin-bottom: 4px;
}

.tn-option-desc {
    font-size: 11px;
    color: #666;
    line-height: 1.4;
}

.tn-option-example {
    font-size: 10px;
    color: #555;
    margin-top: 6px;
    font-family: monospace;
}

.tn-option-example span {
    color: #ef4444;
}

.tn-option-example span.after {
    color: #10b981;
}

/* Toggle Switch */
.tn-toggle {
    position: relative;
    width: 40px;
    height: 22px;
    flex-shrink: 0;
}

.tn-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.tn-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #333;
    border-radius: 22px;
    transition: 0.3s;
}

.tn-toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background: #888;
    border-radius: 50%;
    transition: 0.3s;
}

.tn-toggle input:checked + .tn-toggle-slider {
    background: #10b981;
}

.tn-toggle input:checked + .tn-toggle-slider:before {
    transform: translateX(18px);
    background: #fff;
}

/* Preview Panel - Bên phải */
.tn-preview {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.tn-preview-panels {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.tn-preview-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.tn-preview-panel:first-child {
    border-right: 1px solid #222;
}

.tn-preview-header {
    padding: 12px 16px;
    background: #111;
    border-bottom: 1px solid #222;
    font-size: 12px;
    color: #888;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.tn-preview-header i {
    font-size: 14px;
}

.tn-preview-header.before i {
    color: #ef4444;
}

.tn-preview-header.after i {
    color: #10b981;
}

.tn-preview-content {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    font-size: 13px;
    line-height: 1.6;
    color: #ccc;
    white-space: pre-wrap;
    word-break: break-word;
    background: #0a0a0a;
}

.tn-preview-content.before {
    color: #888;
}

.tn-preview-content.after {
    color: #fff;
}

.tn-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #444;
    text-align: center;
    padding: 40px;
}

.tn-empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.tn-empty-state p {
    font-size: 13px;
}

/* Responsive */
@media (max-width: 768px) {
    .text-normalize-sidebar {
        max-width: 100%;
    }

    .tn-body {
        flex-direction: column;
    }

    .tn-settings {
        width: 100%;
        min-width: 100%;
        max-height: 40vh;
        border-right: none;
        border-bottom: 1px solid #222;
    }

    .tn-preview-panels {
        flex-direction: column;
    }

    .tn-preview-panel:first-child {
        border-right: none;
        border-bottom: 1px solid #222;
    }

    .tn-preview-panel {
        min-height: 150px;
    }

    .tn-header {
        flex-wrap: wrap;
        gap: 10px;
    }

    .tn-header-title {
        order: -1;
        width: 100%;
        justify-content: center;
    }
}
</style>

<div id="textNormalizeOverlay" class="text-normalize-overlay" onclick="closeTextNormalizeSidebar(event)"></div>

<div id="textNormalizeSidebar" class="text-normalize-sidebar">
    <!-- Header -->
    <div class="tn-header">
        <div class="tn-header-left">
            <button class="tn-cancel-btn" onclick="closeTextNormalizeSidebar()">
                <i class="bi bi-x-lg"></i> <?php echo $lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
            </button>
        </div>

        <div class="tn-header-title">
            <span class="tn-char-count" id="tnCharBefore">0</span>
            <span><?php echo $lang === 'vi' ? 'ký tự' : 'chars'; ?></span>
            <i class="bi bi-arrow-right"></i>
            <span class="tn-char-count" id="tnCharAfter">0</span>
            <span><?php echo $lang === 'vi' ? 'ký tự' : 'chars'; ?></span>
        </div>

        <button class="tn-apply-btn" id="tnApplyBtn" onclick="applyTextNormalize()">
            <i class="bi bi-check-lg"></i> <?php echo $lang === 'vi' ? 'Áp dụng thay đổi' : 'Apply Changes'; ?>
        </button>
    </div>

    <!-- Body -->
    <div class="tn-body">
        <!-- Settings - Bên trái -->
        <div class="tn-settings">
            <div class="tn-settings-title"><?php echo $lang === 'vi' ? 'Tùy chọn chuẩn hóa' : 'Normalize Options'; ?></div>

            <!-- Định dạng văn bản -->
            <div class="tn-option-group">
                <div class="tn-option-group-title">
                    <i class="bi bi-text-paragraph"></i>
                    <?php echo $lang === 'vi' ? 'Định dạng văn bản' : 'Text Formatting'; ?>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Xuống dòng → Dấu chấm' : 'Newline → Period'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Thay thế xuống dòng bằng dấu chấm' : 'Replace newlines with periods'; ?></div>
                        <div class="tn-option-example">
                            <span><?php echo $t['ex_newline_before']; ?></span> → <span class="after"><?php echo $t['ex_newline_after']; ?></span>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnNewlineToPeriod" onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Chuẩn hóa khoảng trắng' : 'Normalize Whitespace'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Sửa khoảng trắng xung quanh dấu câu' : 'Fix spacing around punctuation'; ?></div>
                        <div class="tn-option-example">
                            <span><?php echo $t['ex_whitespace_before']; ?></span> → <span class="after"><?php echo $t['ex_whitespace_after']; ?></span>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnNormalizeWhitespace" checked onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Làm sạch dấu câu -->
            <div class="tn-option-group">
                <div class="tn-option-group-title">
                    <i class="bi bi-eraser"></i>
                    <?php echo $lang === 'vi' ? 'Làm sạch dấu câu' : 'Clean Punctuation'; ?>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Thu gọn dấu câu' : 'Collapse Punctuation'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Loại bỏ dấu câu thừa' : 'Remove redundant punctuation'; ?></div>
                        <div class="tn-option-example">
                            <span><?php echo $t['ex_collapse_before']; ?></span> → <span class="after"><?php echo $t['ex_collapse_after']; ?></span>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnCollapsePunctuation" checked onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Xóa dấu câu lạc' : 'Remove Stray Punctuation'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Dọn dẹp dấu phẩy và dấu chấm sai vị trí' : 'Clean misplaced commas and periods'; ?></div>
                        <div class="tn-option-example">
                            <span><?php echo $t['ex_stray_before']; ?></span> → <span class="after"><?php echo $t['ex_stray_after']; ?></span>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnRemoveStrayPunctuation" checked onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Xử lý nội dung -->
            <div class="tn-option-group">
                <div class="tn-option-group-title">
                    <i class="bi bi-braces"></i>
                    <?php echo $lang === 'vi' ? 'Xử lý nội dung' : 'Content Processing'; ?>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Chữ cái & Số Unicode' : 'Unicode Letters & Numbers'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Chỉ giữ ký tự hợp lệ cho TTS' : 'Keep only valid TTS characters'; ?></div>
                        <div class="tn-option-example">
                            <span><?php echo $t['ex_unicode_before']; ?></span> → <span class="after"><?php echo $t['ex_unicode_after']; ?></span>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnUnicodeOnly" onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>

                <div class="tn-option">
                    <div class="tn-option-info">
                        <div class="tn-option-name"><?php echo $lang === 'vi' ? 'Thay thế viết tắt' : 'Expand Abbreviations'; ?></div>
                        <div class="tn-option-desc"><?php echo $lang === 'vi' ? 'Dùng danh sách từ "Phát âm từ"' : 'Use words from "Pronunciation" list'; ?></div>
                        <div class="tn-option-example">
                            <?php echo $lang === 'vi' ? 'Thêm từ trong mục "Phát âm từ" của KingCong' : 'Add words in KingCong "Pronunciation"'; ?>
                        </div>
                    </div>
                    <label class="tn-toggle">
                        <input type="checkbox" id="tnExpandAbbreviations" onchange="updateNormalizePreview()">
                        <span class="tn-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Preview - Bên phải -->
        <div class="tn-preview">
            <div class="tn-preview-panels">
                <!-- Trước -->
                <div class="tn-preview-panel">
                    <div class="tn-preview-header before">
                        <i class="bi bi-file-text"></i>
                        <?php echo $lang === 'vi' ? 'Văn bản gốc' : 'Original Text'; ?>
                    </div>
                    <div class="tn-preview-content before" id="tnPreviewBefore">
                        <div class="tn-empty-state">
                            <i class="bi bi-text-paragraph"></i>
                            <p><?php echo $lang === 'vi' ? 'Nhập văn bản vào ô nhập liệu để xem trước' : 'Enter text in input to preview'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sau -->
                <div class="tn-preview-panel">
                    <div class="tn-preview-header after">
                        <i class="bi bi-check-circle"></i>
                        <?php echo $lang === 'vi' ? 'Văn bản sau khi chuẩn hóa' : 'Normalized Text'; ?>
                    </div>
                    <div class="tn-preview-content after" id="tnPreviewAfter">
                        <div class="tn-empty-state">
                            <i class="bi bi-magic"></i>
                            <p><?php echo $lang === 'vi' ? 'Kết quả sẽ hiển thị ở đây' : 'Result will appear here'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== UI CHO ELEVENLABS ========== -->
<div id="elevenlabs-cost-ui" style="display: none;">
    <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #222; margin-top: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <label class="toggle-switch" style="margin: 0;">
                <input type="checkbox" id="subtitleCheck" onchange="updateEstimatedCost()">
                <span class="toggle-slider"></span>
            </label>
            <div style="line-height: 1.2;">
                <div style="font-size: 13px; color: #eee; font-weight: 500;"><?php echo $t['subtitle']; ?></div>
                <div class="badge-cost" id="elevenlabs-badge" style="margin: 0; display: inline-block;">+15% <?php echo $t['fee']; ?></div>
            </div>
        </div>
        <div id="elevenlabs-buttons-container" style="display: flex; gap: 8px;">
            <!-- Button khoảng dừng ElevenLabs -->
            <div style="position: relative;">
                <button class="reset-btn" id="delayBtnElevenlabs" onclick="toggleDelayTooltipElevenlabs()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                    <i class="bi bi-pause-circle"></i> <?php echo $lang === 'vi' ? 'Khoảng dừng' : 'Delay'; ?>
                </button>
                <div id="delayTooltipElevenlabs" class="delay-tooltip" style="display: none;">
                    <div class="delay-tooltip-arrow"></div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 8px;"><?php echo $lang === 'vi' ? 'Chọn nhanh:' : 'Quick select:'; ?></div>
                    <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                        <button type="button" class="delay-quick-btn" onclick="insertDelayElevenlabs(0.5)">0.5s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayElevenlabs(1)">1s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayElevenlabs(2)">2s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayElevenlabs(3)">3s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayElevenlabs(5)">5s</button>
                    </div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 6px;"><?php echo $lang === 'vi' ? 'Hoặc nhập:' : 'Or enter:'; ?></div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <input type="number" id="delayInputElevenlabs" min="0.1" max="10" step="0.1" value="1" style="width: 70px; padding: 6px 8px; background: #111; border: 1px solid #444; border-radius: 6px; color: #fff; font-size: 13px;">
                        <span style="color: #888; font-size: 13px;">s</span>
                        <button type="button" class="delay-add-btn" onclick="insertDelayFromInputElevenlabs()"><?php echo $lang === 'vi' ? 'Thêm' : 'Add'; ?></button>
                    </div>
                </div>
            </div>
            <button class="reset-btn" onclick="resetCurrentSettings()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                <i class="bi bi-arrow-counterclockwise"></i> <?php echo $t['reset']; ?>
            </button>
        </div>
    </div>
</div>

<!-- ========== UI CHO MINIMAX ========== -->
<div id="minimax-cost-ui" style="display: none;">
    <div class="toggle-row" style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #222; margin-top: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <label class="toggle-switch" style="margin: 0;">
                <input type="checkbox" id="minimaxSubtitleCheck" onchange="updateEstimatedCost()">
                <span class="toggle-slider"></span>
            </label>
            <div style="line-height: 1.2;">
                <div style="font-size: 13px; color: #eee; font-weight: 500;"><?php echo $t['subtitle']; ?></div>
                <div class="badge-cost" id="minimax-srt-badge" style="margin: 0; display: inline-block;">+15% <?php echo $t['fee']; ?></div>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <!-- Button khoảng dừng Minimax -->
            <div style="position: relative;">
                <button class="reset-btn" id="delayBtnMinimax" onclick="toggleDelayTooltipMinimax()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                    <i class="bi bi-pause-circle"></i> <?php echo $lang === 'vi' ? 'Khoảng dừng' : 'Delay'; ?>
                </button>
                <div id="delayTooltipMinimax" class="delay-tooltip" style="display: none;">
                    <div class="delay-tooltip-arrow"></div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 8px;"><?php echo $lang === 'vi' ? 'Chọn nhanh:' : 'Quick select:'; ?></div>
                    <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                        <button type="button" class="delay-quick-btn" onclick="insertDelayMinimax(0.5)">0.5s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayMinimax(1)">1s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayMinimax(2)">2s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayMinimax(3)">3s</button>
                        <button type="button" class="delay-quick-btn" onclick="insertDelayMinimax(5)">5s</button>
                    </div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 6px;"><?php echo $lang === 'vi' ? 'Hoặc nhập:' : 'Or enter:'; ?></div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <input type="number" id="delayInputMinimax" min="0.1" max="10" step="0.1" value="1" style="width: 70px; padding: 6px 8px; background: #111; border: 1px solid #444; border-radius: 6px; color: #fff; font-size: 13px;">
                        <span style="color: #888; font-size: 13px;">s</span>
                        <button type="button" class="delay-add-btn" onclick="insertDelayFromInputMinimax()"><?php echo $lang === 'vi' ? 'Thêm' : 'Add'; ?></button>
                    </div>
                </div>
            </div>
            <button class="reset-btn" onclick="resetCurrentSettings()" style="width: auto; margin: 0; padding: 8px 14px; background: #1a1a1a; border-color: #333;">
                <i class="bi bi-arrow-counterclockwise"></i> <?php echo $t['reset']; ?>
            </button>
        </div>
    </div>
</div>

<!-- 🔥🔥🔥 BUTTON TẠO GIỌNG NÓI Ở ĐÂY 🔥🔥🔥 -->
<button id="btnProcess" class="btn-generate" onclick="startTTS()">
    <i class="bi bi-magic"></i>
    <span><?php echo $t['generate_voice']; ?></span>
</button>
</div>

        <!-- Model Details Sidebar (ElevenLabs) -->
        <div id="modelSidebar" class="model-details-sidebar">
            <div class="md-header">
                <span class="md-title"><?php echo $lang === 'vi' ? 'Thông tin Mô hình' : 'Model Information'; ?></span>
                <i class="bi bi-x-lg md-close" onclick="hideModelDetails()"></i>
            </div>
            <div class="md-content" id="mdContent"></div>
        </div>

        <!-- Model Details Sidebar (KingCong) -->
        <div id="kingcongModelSidebar" class="model-details-sidebar">
            <div class="md-header">
                <span class="md-title"><?php echo $lang === 'vi' ? 'Chọn Mô Hình KingCong' : 'Select KingCong Model'; ?></span>
                <i class="bi bi-x-lg md-close" onclick="hideKingCongModelDetails()"></i>
            </div>
            <div class="md-content" id="kingcongMdContent"></div>
        </div>

        <!-- History Tab -->
        <div id="viewHistory" class="sidebar-content">
            <div id="historyListContainer"></div>
            <div id="loadingMore" class="loading-more">
                <div class="spinner"></div> <?php echo $t['loading']; ?>...
            </div>
            <div id="noMoreData" class="no-more-data">
                <i class="bi bi-check-circle"></i> <?php echo $lang === 'vi' ? 'Đã hiển thị toàn bộ lịch sử' : 'All history displayed'; ?>
            </div>
        </div>
    </div>
</div>

<audio id="globalAudio" style="display:none;"></audio>
<audio id="previewAudio" style="display:none;"></audio>

<!-- Voice Modal -->
<div id="voiceModal" class="voice-modal">
    <div class="voice-modal-content">
        <!-- Header -->
        <div class="vm-header">
            <div class="vm-tabs">
                <div class="vm-tab" data-tab="default" onclick="switchVoiceTab('default')">
                    <?php echo $t['default']; ?>
                </div>
                <div class="vm-tab" data-tab="cloned" id="tabCloned" onclick="switchVoiceTab('cloned')" style="display: none;">
                    <i class="bi bi-mic-fill"></i>
                    <?php echo $t['cloned']; ?>
                </div>
                <div class="vm-tab active" data-tab="library" onclick="switchVoiceTab('library')">
                    <i class="bi bi-collection"></i>
                    <?php echo $t['library']; ?>
                </div>
                <div class="vm-tab" data-tab="favorites" onclick="switchVoiceTab('favorites')">
                    <i class="bi bi-heart-fill"></i>
                    <?php echo $t['favorites']; ?>
                </div>
                
            </div>
            <i class="bi bi-x-lg vm-close" onclick="closeVoiceModal()"></i>
        </div>
        
        <div class="vm-search-bar" style="display: flex; gap: 12px; align-items: center;">
    
    <!-- Search Input -->
    <div class="vm-search-wrapper" style="flex: 1;">
        <i class="bi bi-search"></i>
        <input type="text" class="vm-search-input" placeholder="<?php echo $t['search_voice']; ?>" id="voiceSearch" onkeyup="filterVoices()">
    </div>
    
    <!-- Sort Dropdown -->
    <div class="custom-dropdown" id="sortDropdown" style="display: none; position: relative; min-width: 170px;">
        <button class="dropdown-btn" onclick="toggleSortDropdown(event)" style="height: 42px; background: #1a1a1a; border: 1px solid #333; color: #fff; display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 12px; border-radius: 8px; cursor: pointer;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-sort-down" style="color: #888;"></i> 
                <span id="currentSortLabel" style="font-size: 13px; font-weight: 500;"><?php echo $t['most_used']; ?></span>
            </div>
            <i class="bi bi-chevron-down dropdown-arrow" id="sortArrow" style="font-size: 10px; color: #666;"></i>
        </button>
        
        <!-- Menu Dropdown (BỎ STYLE INLINE) -->
        <div class="dropdown-menu" id="sortMenu" style="display: none;">
    <div class="dropdown-item" onclick="applySort(event, 'trending', '<?php echo $t['trending']; ?>')">
        <?php echo $t['trending']; ?>
    </div>
    <div class="dropdown-item active" onclick="applySort(event, 'newest', '<?php echo $t['newest']; ?>')">
        <?php echo $t['newest']; ?>
    </div>
    <div class="dropdown-item" onclick="applySort(event, 'most_used', '<?php echo $t['most_used']; ?>')">
        <?php echo $t['most_used']; ?>
    </div>
    <div class="dropdown-item" onclick="applySort(event, 'chars', '<?php echo $t['chars_generated']; ?>')">
        <?php echo $t['chars_generated']; ?>
    </div>
</div>
    </div>
    
</div>

 <!-- Filters -->
<div class="vm-filters-bar">
    
    <!-- ========== FILTER 1: NGÔN NGỮ ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced">
        <label class="filter-label"><?php echo $t['language']; ?></label>
        <input type="hidden" id="filterLang" value=""> 
        
        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Lang" onclick="toggleDropdown(event, '#menuLang')" style="width: 160px; justify-content: space-between;">
                <span id="labelLang"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <?php
        // Helper function for flag image
        function flagImg($code) {
            $map = [
                'en'=>'us','vi'=>'vn','zh'=>'cn','ja'=>'jp','ko'=>'kr','hi'=>'in','ar'=>'sa',
                'yue'=>'hk','ta'=>'in','fa'=>'ir','he'=>'il','uk'=>'ua','sv'=>'se','da'=>'dk',
                'no'=>'no','el'=>'gr','cs'=>'cz','fil'=>'ph','af'=>'za','ca'=>'es-ct','ms'=>'my',
                'sl'=>'si'
            ];
            $c = $map[$code] ?? $code;
            return '<img src="https://cdn.jsdelivr.net/gh/lipis/flag-icons/flags/4x3/'.$c.'.svg" style="width:18px;height:14px;border-radius:3px;object-fit:cover;margin-right:8px;vertical-align:middle;">';
        }
        ?>
        <div class="filter-dropdown-menu" id="menuLang" style="width: 200px;">
            <div class="dropdown-item" onclick="selectFilter('Lang', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'en', 'English')"><?php echo flagImg('en'); ?> English</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'vi', 'Vietnamese')"><?php echo flagImg('vi'); ?> Vietnamese</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'fr', 'French')"><?php echo flagImg('fr'); ?> French</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'de', 'German')"><?php echo flagImg('de'); ?> German</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'es', 'Spanish')"><?php echo flagImg('es'); ?> Spanish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'it', 'Italian')"><?php echo flagImg('it'); ?> Italian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'pt', 'Portuguese')"><?php echo flagImg('pt'); ?> Portuguese</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ru', 'Russian')"><?php echo flagImg('ru'); ?> Russian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ja', 'Japanese')"><?php echo flagImg('ja'); ?> Japanese</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ko', 'Korean')"><?php echo flagImg('ko'); ?> Korean</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'zh', 'Chinese')"><?php echo flagImg('zh'); ?> Chinese</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ar', 'Arabic')"><?php echo flagImg('ar'); ?> Arabic</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'hi', 'Hindi')"><?php echo flagImg('hi'); ?> Hindi</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'th', 'Thai')"><?php echo flagImg('th'); ?> Thai</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'id', 'Indonesian')"><?php echo flagImg('id'); ?> Indonesian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'nl', 'Dutch')"><?php echo flagImg('nl'); ?> Dutch</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'pl', 'Polish')"><?php echo flagImg('pl'); ?> Polish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'tr', 'Turkish')"><?php echo flagImg('tr'); ?> Turkish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'uk', 'Ukrainian')"><?php echo flagImg('uk'); ?> Ukrainian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'sv', 'Swedish')"><?php echo flagImg('sv'); ?> Swedish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'da', 'Danish')"><?php echo flagImg('da'); ?> Danish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'fi', 'Finnish')"><?php echo flagImg('fi'); ?> Finnish</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'no', 'Norwegian')"><?php echo flagImg('no'); ?> Norwegian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'el', 'Greek')"><?php echo flagImg('el'); ?> Greek</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'cs', 'Czech')"><?php echo flagImg('cs'); ?> Czech</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ro', 'Romanian')"><?php echo flagImg('ro'); ?> Romanian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'hu', 'Hungarian')"><?php echo flagImg('hu'); ?> Hungarian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'sk', 'Slovak')"><?php echo flagImg('sk'); ?> Slovak</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'bg', 'Bulgarian')"><?php echo flagImg('bg'); ?> Bulgarian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'hr', 'Croatian')"><?php echo flagImg('hr'); ?> Croatian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'sl', 'Slovenian')"><?php echo flagImg('sl'); ?> Slovenian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'he', 'Hebrew')"><?php echo flagImg('he'); ?> Hebrew</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'fa', 'Persian')"><?php echo flagImg('fa'); ?> Persian</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ms', 'Malay')"><?php echo flagImg('ms'); ?> Malay</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ta', 'Tamil')"><?php echo flagImg('ta'); ?> Tamil</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'fil', 'Filipino')"><?php echo flagImg('fil'); ?> Filipino</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'af', 'Afrikaans')"><?php echo flagImg('af'); ?> Afrikaans</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'ca', 'Catalan')"><?php echo flagImg('es'); ?> Catalan</div>
            <div class="dropdown-item" onclick="selectFilter('Lang', 'yue', 'Cantonese')"><?php echo flagImg('yue'); ?> Cantonese</div>
        </div>
    </div>

    <!-- ========== FILTER 2: GIỚI TÍNH ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced">
        <label class="filter-label"><?php echo $t['gender']; ?></label>
        <input type="hidden" id="filterGender" value="">

        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Gender" onclick="toggleDropdown(event, '#menuGender')" style="width: 120px; justify-content: space-between;">
                <span id="labelGender"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <div class="filter-dropdown-menu" id="menuGender" style="width: 120px;">
            <div class="dropdown-item" onclick="selectFilter('Gender', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Gender', 'male', '<?php echo $t['male']; ?>')"><?php echo $t['male']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Gender', 'female', '<?php echo $t['female']; ?>')"><?php echo $t['female']; ?></div>
        </div>
    </div>

    <!-- ========== FILTER: NHÀ CUNG CẤP (KINGCONG) ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced" id="filterProviderGroup" style="display: none;">
        <label class="filter-label"><?php echo $lang === 'vi' ? 'Nhà cung cấp' : 'Provider'; ?></label>
        <input type="hidden" id="filterProvider" value="">

        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Provider" onclick="toggleDropdown(event, '#menuProvider')" style="width: 140px; justify-content: space-between;">
                <span id="labelProvider"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <div class="filter-dropdown-menu" id="menuProvider" style="width: 180px;">
            <div class="dropdown-item" onclick="selectFilter('Provider', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Provider', 'maziao', 'Maziao (<?php echo $lang === 'vi' ? 'Khuyên dùng' : 'Recommended'; ?>)')">Maziao (<?php echo $lang === 'vi' ? 'Khuyên dùng' : 'Recommended'; ?>)</div>
            <div class="dropdown-item" onclick="selectFilter('Provider', 'vbee', 'Vbee')">Vbee</div>
            <div class="dropdown-item" onclick="selectFilter('Provider', 'elevenlabs', 'ElevenLabs')">ElevenLabs</div>
        </div>
    </div>

    <!-- ========== FILTER 3: ĐỘ TUỔI ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced">
        <label class="filter-label"><?php echo $t['age']; ?></label>
        <input type="hidden" id="filterAge" value="">
        
        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Age" onclick="toggleDropdown(event, '#menuAge')" style="width: 130px; justify-content: space-between;">
                <span id="labelAge"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <div class="filter-dropdown-menu" id="menuAge" style="width: 130px;">
            <div class="dropdown-item" onclick="selectFilter('Age', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Age', 'young', '<?php echo $t['young']; ?>')"><?php echo $t['young']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Age', 'middle_aged', '<?php echo $t['middle_aged']; ?>')"><?php echo $t['middle_aged']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Age', 'old', '<?php echo $t['old']; ?>')"><?php echo $t['old']; ?></div>
        </div>
    </div>

    <!-- ========== FILTER 4: PHONG CÁCH ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced">
        <label class="filter-label"><?php echo $t['style']; ?></label>
        <input type="hidden" id="filterCategory" value="">
        
        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Category" onclick="toggleDropdown(event, '#menuCategory')" style="width: 140px; justify-content: space-between;">
                <span id="labelCategory"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <div class="filter-dropdown-menu" id="menuCategory" style="width: 160px;">
            <div class="dropdown-item" onclick="selectFilter('Category', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'narrative', '<?php echo $lang === 'vi' ? 'Kể chuyện' : 'Narrative'; ?>')"><?php echo $lang === 'vi' ? 'Kể chuyện' : 'Narrative'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'conversational', '<?php echo $lang === 'vi' ? 'Hội thoại' : 'Conversational'; ?>')"><?php echo $lang === 'vi' ? 'Hội thoại' : 'Conversational'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'news', '<?php echo $lang === 'vi' ? 'Tin tức' : 'News'; ?>')"><?php echo $lang === 'vi' ? 'Tin tức' : 'News'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'asmr', 'ASMR')">ASMR</div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'audiobook', '<?php echo $lang === 'vi' ? 'Sách nói' : 'Audiobook'; ?>')"><?php echo $lang === 'vi' ? 'Sách nói' : 'Audiobook'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'informative_educational', '<?php echo $lang === 'vi' ? 'Giáo dục' : 'Educational'; ?>')"><?php echo $lang === 'vi' ? 'Giáo dục' : 'Educational'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'characters_animation', '<?php echo $lang === 'vi' ? 'Nhân vật' : 'Characters'; ?>')"><?php echo $lang === 'vi' ? 'Nhân vật' : 'Characters'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'social_media', '<?php echo $lang === 'vi' ? 'Mạng xã hội' : 'Social Media'; ?>')"><?php echo $lang === 'vi' ? 'Mạng xã hội' : 'Social Media'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'entertainment', '<?php echo $lang === 'vi' ? 'Giải trí' : 'Entertainment'; ?>')"><?php echo $lang === 'vi' ? 'Giải trí' : 'Entertainment'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'advertising', '<?php echo $lang === 'vi' ? 'Quảng cáo' : 'Advertising'; ?>')"><?php echo $lang === 'vi' ? 'Quảng cáo' : 'Advertising'; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Category', 'meditation', '<?php echo $lang === 'vi' ? 'Thiền định' : 'Meditation'; ?>')"><?php echo $lang === 'vi' ? 'Thiền định' : 'Meditation'; ?></div>
        </div>
    </div>

    <!-- ========== FILTER 5: GIỌNG ========== -->
    <div class="filter-group custom-dropdown filter-dropdown-enhanced">
        <label class="filter-label"><?php echo $t['accent']; ?></label>
        <input type="hidden" id="filterAccent" value="">
        
        <div class="filter-btn-wrapper">
            <button class="dropdown-btn btn-Accent" onclick="toggleDropdown(event, '#menuAccent')" style="width: 140px; justify-content: space-between;">
                <span id="labelAccent"><?php echo $t['all']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </button>
        </div>

        <div class="filter-dropdown-menu" id="menuAccent" style="width: 140px;">
            <div class="dropdown-item" onclick="selectFilter('Accent', '', '<?php echo $t['all']; ?>')"><?php echo $t['all']; ?></div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'american', 'American')">American</div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'british', 'British')">British</div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'australian', 'Australian')">Australian</div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'irish', 'Irish')">Irish</div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'indian', 'Indian')">Indian</div>
            <div class="dropdown-item" onclick="selectFilter('Accent', 'african', 'African')">African</div>
        </div>
    </div>

    <!-- ========== NÚT RESET ========== -->
    <button class="filter-reset-btn" onclick="resetFilters()" title="<?php echo $t['reset']; ?>" style="margin-left: auto;">
        <i class="bi bi-x-lg"></i>
    </button>
</div>


        <!-- Voice Grid -->
        <div class="vm-grid" id="voiceGrid">
            <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                <div class="spinner-border" style="width: 40px; height: 40px; color: #667eea;"></div>
                <p style="color:#888; margin-top:20px; font-size:14px;"><?php echo $t['loading_voices']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Clone Modal -->
<div id="cloneModal" class="custom-modal-key" style="z-index: 2500;">
    <div class="modal-box-key" style="text-align: left; max-width: 520px;">
        <h3 style="margin-bottom: 20px; text-align: center; font-size: 20px;">
            <i class="bi bi-mic-fill"></i> <?php echo $t['clone_modal_title']; ?>
        </h3>
        <div class="setting-group">
            <label><?php echo $t['clone_name_label']; ?></label>
            <input type="text" id="cloneName" class="custom-select" placeholder="<?php echo $t['clone_name_placeholder']; ?>" style="cursor: text;">
        </div>
        <div class="setting-group">
            <label><?php echo $t['clone_gender_label']; ?></label>
            <select id="cloneGender" class="custom-select">
                <option value="male"><?php echo $t['clone_gender_male']; ?></option>
                <option value="female"><?php echo $t['clone_gender_female']; ?></option>
            </select>
        </div>
        <div class="setting-group">
            <label><?php echo $t['clone_sample_file']; ?></label>
            <input type="file" id="cloneFile" accept=".mp3" class="custom-select" style="cursor: pointer;">
            <small style="color: #666; font-size: 11px; display: block; margin-top: 8px;">
                <?php echo $t['clone_sample_hint']; ?>
            </small>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button onclick="$('#cloneModal').fadeOut()" class="btn-close-modal" style="flex: 1;">
                <?php echo $t['cancel']; ?>
            </button>
            <button onclick="submitCloneVoice()" id="btnSubmitClone" class="btn-generate" style="flex: 1; margin-top: 0;">
                <i class="bi bi-mic"></i>
                <span><?php echo $t['clone_start']; ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal - NEW BLACK & WHITE THEME -->
<div id="bulkUploadModal" class="bulk-modal-overlay">
    <div class="bulk-modal-content">
        <!-- Header -->
        <div class="bulk-modal-header">
            <h3>
                <i class="bi bi-folder2-open"></i>
                <?php echo $lang === 'vi' ? 'Nhập hàng loạt' : 'Bulk Upload'; ?>
            </h3>
            <button class="bulk-modal-close" onclick="closeBulkModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <!-- Notice -->
        <div class="bulk-notice">
            <i class="bi bi-info-circle"></i>
            <span>
                <strong><?php echo $lang === 'vi' ? 'Lưu ý:' : 'Note:'; ?></strong> 
                <?php echo $lang === 'vi' 
                    ? 'Vui lòng chọn giọng nói trước khi tải file lên.' 
                    : 'Please select a voice before uploading files.'; ?>
            </span>
        </div>
        
        <!-- Scrollable Body -->
        <div class="bulk-modal-body" id="bulkModalScrollArea">
            <!-- Drop Zone -->
            <div class="bulk-drop-zone" id="bulkDropZone">
                <i class="bi bi-cloud-upload"></i>
                <h4><?php echo $lang === 'vi' ? 'Kéo thả file hoặc click để chọn' : 'Drag & drop or click to select'; ?></h4>
                <p><?php echo $lang === 'vi' 
                    ? 'Hỗ trợ: .txt, .srt, .zip (tối đa 20 file, mỗi file < 5MB)' 
                    : 'Supports: .txt, .srt, .zip (max 20 files, each < 5MB)'; ?></p>
                <input type="file" id="bulkFileInput" multiple accept=".txt,.srt,.zip" style="display: none;">
            </div>

            <!-- File List -->
            <div class="bulk-file-list" id="bulkFileList">
                <div class="bulk-file-list-header">
                    <h5><?php echo $lang === 'vi' ? 'Danh sách file' : 'File List'; ?> (<span id="fileCount">0</span>)</h5>
                    <button class="bulk-btn-clear" onclick="clearAllFiles()">
                        <i class="bi bi-trash"></i>
                        <?php echo $lang === 'vi' ? 'Xóa tất cả' : 'Clear All'; ?>
                    </button>
                </div>
                
                <div class="bulk-file-list-items" id="fileListContainer"></div>
            </div>

            <!-- Summary -->
            <div class="bulk-summary" id="bulkSummary">
                <div class="bulk-summary-row">
                    <span class="bulk-summary-label"><?php echo $lang === 'vi' ? 'Tổng ký tự:' : 'Total chars:'; ?></span>
                    <span class="bulk-summary-value" id="totalChars">0</span>
                </div>
                <div class="bulk-summary-row">
                    <span class="bulk-summary-label"><?php echo $lang === 'vi' ? 'Chi phí xử lý:' : 'Processing cost:'; ?></span>
                    <span class="bulk-summary-value small" id="baseCost">0 credits</span>
                </div>
                <div class="bulk-summary-divider"></div>
                <div class="bulk-summary-total">
                    <span class="bulk-summary-label"><?php echo $lang === 'vi' ? 'Tổng chi phí:' : 'Total cost:'; ?></span>
                    <span class="bulk-summary-value" id="bulkEstimatedCost">0 credits</span>
                </div>
                <div class="bulk-summary-balance">
                    <span class="bulk-summary-label"><?php echo $lang === 'vi' ? 'Số dư hiện tại:' : 'Current balance:'; ?></span>
                    <span class="bulk-summary-value" id="currentBalance">0 credits</span>
                </div>
                
                <!-- Settings Info -->
                <div class="bulk-settings-info">
                    <div>
                        <i class="bi bi-info-circle"></i>
                        Model: <span id="summaryModel">-</span>
                    </div>
                    <div id="summaryBoost">
                        <i class="bi bi-megaphone"></i>
                        <?php echo $lang === 'vi' ? 'Tăng cường giọng: ' : 'Speaker Boost: '; ?>
                        <span><?php echo $lang === 'vi' ? 'Có' : 'Yes'; ?></span>
                    </div>
                    <div id="summaryTranscript">
                        <i class="bi bi-file-earmark-text"></i>
                        <?php echo $lang === 'vi' ? 'Xuất phụ đề: ' : 'Export subtitle: '; ?>
                        <span><?php echo $lang === 'vi' ? 'Có' : 'Yes'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Process Button -->
            <button class="bulk-btn-process" id="btnBulkProcess" onclick="processBulkFiles()">
                <i class="bi bi-magic"></i>
                <span><?php echo $lang === 'vi' ? 'Xử lý tất cả' : 'Process All'; ?></span>
            </button>
        </div>
    </div>
</div>
<!-- Global Drop Overlay -->
<!-- ========== MINIMAX ANNOUNCEMENT POPUP ========== -->
<div id="minimaxAnnouncementPopup" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.92);
    backdrop-filter: blur(12px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999999;
    padding: 20px;
">
    <div style="
        background: #0a0a0a;
        border: 1px solid #333;
        border-radius: 20px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.9);
    ">
        <!-- Header -->
        <div style="padding: 24px 28px 20px; border-bottom: 1px solid #222; display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #1a1a1a; border: 1px solid #333; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-exclamation-triangle" style="font-size: 24px; color: #fff;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #fff;">
                    <?php echo $lang === 'vi' ? 'THÔNG BÁO VỀ MINIMAX' : 'MINIMAX ANNOUNCEMENT'; ?>
                </h3>
                <p style="margin: 4px 0 0; font-size: 13px; color: #666;">
                    <?php echo $lang === 'vi' ? 'Cập nhật tình trạng & giải pháp' : 'Status update & solutions'; ?>
                </p>
            </div>
        </div>

        <!-- Content -->
        <div style="padding: 24px 28px;">
            <!-- Main Notice -->
            <div style="background: #111; border: 1px solid #333; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #ccc;">
                    <?php if ($lang === 'vi'): ?>
                    Do thời gian vừa qua việc tìm kiếm nguyên liệu cho Minimax gặp nhiều khó khăn và bị fix liên tục. Hiện tại Minimax vẫn có thể chạy nhưng sẽ <strong style="color: #fff;">khó khăn và tốc độ chậm hơn</strong>.
                    <?php else: ?>
                    Due to recent difficulties in finding resources for Minimax and continuous fixes, Minimax is currently still operational but will face <strong style="color: #fff;">difficulties and slower speeds</strong>.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Tips -->
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Tip 1 -->
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; background: #1a1a1a; border: 1px solid #333; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-hourglass-split" style="font-size: 16px; color: #fff;"></i>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                            <?php echo $lang === 'vi' ? 'Nếu không vội' : 'If not urgent'; ?>
                        </div>
                        <div style="font-size: 13px; color: #888; line-height: 1.5;">
                            <?php echo $lang === 'vi'
                                ? 'Bạn có thể tạo nhiệm vụ và treo máy đợi. Khi xong vào mục <strong style="color:#fff;">Lịch Sử Chi Tiết</strong> để tải về.'
                                : 'You can create a task and wait. Once finished, go to <strong style="color:#fff;">Detailed History</strong> to download.'; ?>
                        </div>
                    </div>
                </div>

                <!-- Tip 2 -->
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 36px; height: 36px; background: #1a1a1a; border: 1px solid #333; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-arrow-repeat" style="font-size: 16px; color: #fff;"></i>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                            <?php echo $lang === 'vi' ? 'Hoàn Credits' : 'Refund Credits'; ?>
                        </div>
                        <div style="font-size: 13px; color: #888; line-height: 1.5;">
                            <?php echo $lang === 'vi'
                                ? 'Nhiệm vụ chạy 0% hoặc treo trên 24h bạn có thể xóa để được hoàn lại credits.'
                                : 'Tasks running at 0% or stuck for over 24h can be deleted to refund credits.'; ?>
                        </div>
                    </div>
                </div>

                <!-- Tip 3 - Alternative -->
                <div style="display: flex; gap: 14px; align-items: flex-start; background: #111; border: 1px solid #444; border-radius: 12px; padding: 16px;">
                    <div style="width: 36px; height: 36px; background: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-lightning-charge-fill" style="font-size: 18px; color: #000;"></i>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px;">
                            <?php echo $lang === 'vi' ? 'Giải pháp thay thế' : 'Alternative Solution'; ?>
                        </div>
                        <div style="font-size: 13px; color: #aaa; line-height: 1.5;">
                            <?php echo $lang === 'vi'
                                ? 'Bạn có thể sử dụng <strong style="color:#fff;">KingCong AI (Server KingCong)</strong> – có chức năng Clone và chất lượng voice tương tự Minimax.'
                                : 'You can use <strong style="color:#fff;">KingCong AI (KingCong Server)</strong> – which has Clone function and voice quality similar to Minimax.'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 0 28px 24px; display: flex; gap: 12px;">
            <button onclick="closeMinimaxAnnouncement()" style="flex: 1; padding: 14px 20px; background: #1a1a1a; border: 1px solid #333; border-radius: 12px; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#222'" onmouseout="this.style.background='#1a1a1a'">
                <?php echo $lang === 'vi' ? 'Đã hiểu, tiếp tục' : 'Understood, continue'; ?>
            </button>
            <button onclick="closeMinimaxAnnouncement(); selectProvider('kingcong');" style="flex: 1; padding: 14px 20px; background: #fff; border: none; border-radius: 12px; color: #000; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e5e5e5'" onmouseout="this.style.background='#fff'">
                <?php echo $lang === 'vi' ? 'Dùng KingCong AI' : 'Use KingCong AI'; ?>
            </button>
        </div>
    </div>
</div>

<div id="globalDropOverlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    pointer-events: none;
">
    <div style="text-align: center; pointer-events: auto;">
        <i class="bi bi-cloud-upload" style="font-size: 80px; color: #667eea; display: block; margin-bottom: 20px;"></i>
        <h2 style="font-size: 32px; font-weight: 700; margin-bottom: 12px;"><?php echo $t['drop_overlay_title']; ?></h2>
        <p style="font-size: 16px; color: #888;"><?php echo $t['drop_overlay_desc']; ?></p>
    </div>
</div>
<div id="deleteModal" class="custom-modal-overlay" style="display: none;">
    <div class="delete-modal-box">
        <div class="dm-header">
            <h3><?php echo $t['delete_task_title']; ?></h3>
            <i class="bi bi-x-lg" onclick="closeDeleteModal()"></i>
        </div>
        
        <p class="dm-warning">
            <?php echo $t['delete_task_warn']; ?>
        </p>
        
        <p class="dm-note" id="dmNote">
            <?php echo $t['delete_task_note']; ?>
        </p>

        <div class="dm-preview" id="dmTextPreview">
            <?php echo $t['delete_task_preview']; ?>
        </div>

        <div class="dm-footer">
            <button class="btn-cancel" onclick="closeDeleteModal()"><?php echo $t['cancel']; ?></button>
            <button class="btn-confirm-delete" id="btnConfirmDelete">
                <i class="bi bi-trash"></i> <?php echo $t['delete']; ?>
            </button>
        </div>
    </div>
</div>
<div id="detailedHistoryModal" class="dh-modal-overlay" style="display: none;">
    <div class="dh-modal-content">
        
        <div class="dh-header">
    <h3><?php echo $t['history']; ?></h3>
    
    <div style="display: flex; align-items: center; gap: 8px;">
        <button class="dh-header-btn dh-refresh-with-text" onclick="refreshDetailedHistory()" title="<?php echo $t['refresh_data']; ?>">
            <i class="bi bi-arrow-clockwise" id="dhRefreshIcon"></i>
            <span><?php echo $lang === 'vi' ? 'Làm mới' : 'Refresh'; ?></span>
        </button>

        <button class="dh-header-btn" onclick="closeDetailedHistory()" title="<?php echo $t['close']; ?>">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

        <div class="dh-toolbar">
            <div class="dh-checkbox-wrapper">
                <input type="checkbox" id="dhSelectAll" onchange="toggleAllDetailed(this)">
            </div>
            
            <div class="dh-actions-group">
                <button class="dh-btn-action" id="btnBulkDelete" onclick="bulkDelete()" disabled>
                    <?php echo $t['delete']; ?> (<span>0</span>)
                </button>
                <button class="dh-btn-action" id="btnBulkDownloadAudio" onclick="bulkDownload('audio')" disabled>
                    <?php echo $t['download']; ?> (<span>0</span>) Audio
                </button>
                <button class="dh-btn-action" id="btnBulkDownloadSrt" onclick="bulkDownload('srt')" disabled>
                    <?php echo $t['download']; ?> (<span>0</span>) SRT
                </button>
                <button class="dh-btn-action" id="btnBulkDownloadJson" onclick="bulkDownload('json')" disabled>
                    <?php echo $t['download']; ?> (<span>0</span>) JSON
                </button>
            </div>
        </div>

        <div class="dh-list-body" id="detailedHistoryList">
            <div style="text-align:center; padding: 50px; color: #666;">
                <div class="spinner-border text-primary" role="status"></div>
                <div style="margin-top:10px"><?php echo $t['loading_data']; ?></div>
            </div>
        </div>
    </div>
</div>
<div id="srtSettingsModal" class="srt-modal-overlay" style="display: none;">
    <div class="srt-modal-box">
        <div class="srt-modal-header">
            <h3><?php echo $t['srt_settings_title']; ?></h3>
            <button class="srt-close-btn" onclick="closeSrtModal()">&times;</button>
        </div>
        
        <div class="srt-modal-body">
            <input type="hidden" id="srtCurrentTaskId" value="">

            <div class="srt-form-group">
                <label><?php echo $t['srt_max_chars']; ?></label>
                <input type="number" id="srtMaxChars" value="42" min="1">
            </div>

            <div class="srt-form-group">
                <label><?php echo $t['srt_max_lines']; ?></label>
                <input type="number" id="srtMaxLines" value="2" min="1">
            </div>

            <div class="srt-form-group">
                <label><?php echo $t['srt_max_duration']; ?></label>
                <input type="number" id="srtMaxDuration" value="7" min="1">
            </div>
        </div>

        <div class="srt-modal-footer">
            <button class="srt-btn-reset" onclick="resetSrtSettings()">
                <i class="bi bi-arrow-counterclockwise"></i> <?php echo $t['srt_reset']; ?>
            </button>
            <div style="display: flex; gap: 10px;">
                <button class="srt-btn-close" onclick="closeSrtModal()"><?php echo $t['close']; ?></button>
                <button class="srt-btn-export" onclick="submitSrtExport()"><?php echo $t['export']; ?></button>
            </div>
        </div>
    </div>
</div>
<!-- ========== POPUP XÓA VĂN BẢN ========== -->
<div id="clearTextModal" class="custom-modal-overlay" style="display: none;">
    <div class="delete-modal-box">
        <div class="dm-header">
            <h3>
                <i class="bi bi-exclamation-triangle" style="color: #fbbf24; margin-right: 8px;"></i>
                <?php echo $lang === 'vi' ? 'Xóa văn bản' : 'Clear Text'; ?>
            </h3>
            <i class="bi bi-x-lg" onclick="closeClearTextModal()" style="cursor: pointer;"></i>
        </div>
        
        <p class="dm-warning">
            <?php echo $lang === 'vi' 
                ? 'Bạn có chắc chắn muốn xóa toàn bộ văn bản? Hành động này không thể hoàn tác.' 
                : 'Are you sure you want to clear all text? This action cannot be undone.'; ?>
        </p>
        
        <div class="dm-preview" id="clearTextPreview" style="
            max-height: 150px;
            overflow-y: auto;
            background: #0a0a0a;
            border: 1px solid #222;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: #aaa;
            font-family: 'Courier New', monospace;
        ">
            <!-- Nội dung text preview sẽ hiển thị ở đây -->
        </div>

        <div style="
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 12px;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
        ">
            <i class="bi bi-info-circle" style="color: #fbbf24; font-size: 18px;"></i>
            <span style="font-size: 12px; color: #ddd;">
                <?php echo $lang === 'vi' 
                    ? 'Văn bản đã tải lên và các cài đặt SRT cũng sẽ bị xóa.' 
                    : 'Uploaded files and SRT settings will also be cleared.'; ?>
            </span>
        </div>

        <div class="dm-footer" style="margin-top: 20px;">
            <button class="btn-cancel" onclick="closeClearTextModal()">
                <?php echo $lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
            </button>
            <button class="btn-confirm-delete" id="btnConfirmClear" onclick="confirmClearText()">
                <i class="bi bi-trash"></i> 
                <?php echo $lang === 'vi' ? 'Xóa' : 'Clear'; ?>
            </button>
        </div>
    </div>
</div>
<!-- ========== POPUP REMAKE TASK - WITH SUBTITLE TOGGLE ========== -->
<div id="remakeTaskModal" class="custom-modal-overlay" style="display: none;">
    <div class="remake-modal-box">
        <!-- HEADER -->
        <div class="remake-header">
            <h3>
                <i class="bi bi-arrow-repeat"></i>
                <?php echo $lang === 'vi' ? 'Tạo lại tác vụ' : 'Remake Task'; ?>
            </h3>
            <button class="remake-close-btn" onclick="closeRemakeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <!-- WARNING -->
        <p class="remake-warning">
            <?php echo $lang === 'vi'
                ? 'Tác vụ này sẽ được tạo lại ngay lập tức với nội dung và cài đặt cũ.'
                : 'This task will be remade immediately with the same content and settings.'; ?>
        </p>
        
        <!-- PREVIEW -->
        <div class="remake-preview" id="remakeTextPreview"></div>
        
        <!-- SETTINGS -->
        <div class="remake-settings">
            <div class="remake-settings-header">
                <i class="bi bi-gear"></i>
                <?php echo $lang === 'vi' ? 'Cài đặt' : 'Settings'; ?>
            </div>
            <div id="remakeSettingsInfo" class="remake-settings-content"></div>
        </div>
        
        <!-- 🔥 SUBTITLE TOGGLE (FIXED: +15%) -->
<div class="remake-subtitle-toggle">
    <div style="display: flex; align-items: center; gap: 10px;">
        <label class="toggle-switch" style="margin: 0;">
            <input type="checkbox" id="remakeSubtitleCheck" onchange="updateRemakeCost()">
            <span class="toggle-slider"></span>
        </label>
        <span style="font-size: 14px; color: #ccc;">
            <i class="bi bi-file-earmark-text"></i>
            <?php echo $lang === 'vi' ? 'Tạo phụ đề' : 'Generate Subtitle'; ?>
        </span>
    </div>
    <span id="remakeSubtitleBadge" style="
        display: none;
        font-size: 11px;
        color: #fbbf24;
        font-weight: 600;
        background: rgba(251, 191, 36, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid rgba(251, 191, 36, 0.3);
    ">
        +15%
    </span>
</div>
        
        <!-- COST -->
        <div class="remake-cost">
            <span>
                <i class="bi bi-coin"></i>
                <?php echo $lang === 'vi' ? 'Chi phí:' : 'Cost:'; ?>
            </span>
            <span id="remakeCostDisplay">0 credits</span>
        </div>
        
        <!-- ACTIONS -->
        <div class="remake-actions">
            <button class="remake-btn-cancel" onclick="closeRemakeModal()">
                <?php echo $lang === 'vi' ? 'Hủy' : 'Cancel'; ?>
            </button>
            <button class="remake-btn-confirm" id="btnConfirmRemake" onclick="confirmRemakeTask()">
                <i class="bi bi-magic"></i>
                <span><?php echo $lang === 'vi' ? 'Tạo lại ngay' : 'Remake Now'; ?></span>
            </button>
        </div>
    </div>
</div>
<!-- Popup xác nhận upload folder -->
<div id="folderConfirmPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(12px); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: #111; border: 1px solid #333; border-radius: 16px; padding: 32px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.9); text-align: center;">
        <div style="width: 60px; height: 60px; margin: 0 auto 20px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #444;">
            <i class="bi bi-folder-fill" style="font-size: 28px; color: #fff;"></i>
        </div>
        
        <h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #fff;"><?php echo $t['folder_confirm_title']; ?></h3>
        
        <p style="color: #999; font-size: 14px; margin-bottom: 28px; line-height: 1.6;">
            <?php echo $t['folder_confirm_desc']; ?>
        </p>
        
        <div id="folderFileList" style="max-height: 180px; overflow-y: auto; margin-bottom: 24px; text-align: left; background: #0a0a0a; border: 1px solid #222; border-radius: 8px; padding: 12px;">
            <!-- Danh sách file sẽ được render ở đây -->
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button id="folderCancelBtn" style="flex: 1; padding: 12px 20px; border-radius: 10px; border: 1px solid #444; background: transparent; color: #ccc; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s;">
                <?php echo $t['cancel']; ?>
            </button>
            <button id="folderUploadBtn" style="flex: 1; padding: 12px 20px; border-radius: 10px; border: none; background: #fff; color: #000; font-weight: 700; cursor: pointer; font-size: 14px; transition: 0.2s;">
                <?php echo $t['upload']; ?>
            </button>
        </div>
    </div>
</div>

<style>
#folderCancelBtn:hover {
    background: #222;
    border-color: #666;
    color: #fff;
}

#folderUploadBtn:hover {
    background: #e5e5e5;
}

#folderUploadBtn:active {
    transform: scale(0.98);
}

#folderCancelBtn:active {
    transform: scale(0.98);
}

#folderFileList::-webkit-scrollbar {
    width: 6px;
}

#folderFileList::-webkit-scrollbar-track {
    background: #0a0a0a;
}

#folderFileList::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 3px;
}

#folderFileList::-webkit-scrollbar-thumb:hover {
    background: #444;
}

/* ========================================
   KINGCONG CLONE VOICE CARD - MATCHING CLONING.PHP
   ======================================== */
.kc-clone-card {
    background: #131313;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    position: relative;
    min-height: 150px;
    cursor: pointer;
}

.kc-clone-card:hover {
    border-color: #444;
    background: #181818;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.4);
}

.kc-clone-card.selected {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.kc-clone-card .vc-title {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.3px;
}

.kc-clone-card .vc-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: auto;
}

.kc-clone-card .vc-tag {
    font-size: 11px;
    font-weight: 500;
    color: #bbb;
    background: #222;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #333;
}

.kc-clone-card .vc-tag.clone-tag {
    color: #4ade80;
    border-color: rgba(74, 222, 128, 0.3);
}

.kc-clone-card .vc-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 12px;
}

.kc-clone-card .vc-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #333;
    object-fit: cover;
}

.kc-clone-card .vc-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.kc-clone-card .btn-icon-action {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 18px;
    padding: 4px;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kc-clone-card .btn-icon-action:hover {
    transform: scale(1.2);
    color: #fff;
}

.kc-clone-card .btn-icon-action.play:hover {
    color: #4ade80;
}

.kc-clone-card .btn-use {
    background: #fff;
    color: #000;
    border: none;
    padding: 6px 16px;
    border-radius: 16px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: 0.2s;
}

.kc-clone-card .btn-use:hover {
    background: #e0e0e0;
    transform: translateY(-1px);
}

/* Add clone card - center content */
.kc-clone-card.add-clone-card {
    align-items: center;
    justify-content: center;
    text-align: center;
}

.kc-clone-card.add-clone-card:hover {
    border-color: #667eea;
    border-style: dashed;
}
</style>
<!-- Bulk Confirm Popup -->
<div id="bulkConfirmPopup" class="bulk-confirm-overlay">
    <div class="bulk-confirm-box">
        <!-- Icon -->
        <div class="bulk-confirm-icon">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>
        
        <!-- Title -->
        <h3 class="bulk-confirm-title">
            <?php echo $lang === 'vi' ? 'Xác nhận xử lý hàng loạt' : 'Confirm Bulk Processing'; ?>
        </h3>
        
        <!-- Message -->
        <p class="bulk-confirm-message">
            <?php echo $lang === 'vi' 
                ? 'Bạn đang chuẩn bị xử lý hàng loạt. Vui lòng kiểm tra thông tin trước khi tiếp tục.' 
                : 'You are about to process multiple files. Please review the information before proceeding.'; ?>
        </p>
        
        <!-- Stats -->
        <div class="bulk-confirm-stats">
            <div class="bulk-confirm-stat-row">
                <span class="bulk-confirm-stat-label">
                    <i class="bi bi-file-earmark-text"></i>
                    <?php echo $lang === 'vi' ? 'Số lượng file:' : 'Total files:'; ?>
                </span>
                <span class="bulk-confirm-stat-value" id="bcFileCount">0</span>
            </div>
            
            <div class="bulk-confirm-stat-row">
                <span class="bulk-confirm-stat-label">
                    <i class="bi bi-fonts"></i>
                    <?php echo $lang === 'vi' ? 'Tổng ký tự:' : 'Total characters:'; ?>
                </span>
                <span class="bulk-confirm-stat-value" id="bcCharCount">0</span>
            </div>
            
            <div class="bulk-confirm-stat-row">
                <span class="bulk-confirm-stat-label">
                    <i class="bi bi-coin"></i>
                    <?php echo $lang === 'vi' ? 'Chi phí:' : 'Cost:'; ?>
                </span>
                <span class="bulk-confirm-stat-value highlight" id="bcCost">0 credits</span>
            </div>
            
            <div class="bulk-confirm-stat-row">
                <span class="bulk-confirm-stat-label">
                    <i class="bi bi-wallet2"></i>
                    <?php echo $lang === 'vi' ? 'Số dư sau:' : 'Balance after:'; ?>
                </span>
                <span class="bulk-confirm-stat-value balance" id="bcBalanceAfter">0 credits</span>
            </div>
        </div>
        
        <!-- Warning -->
        <div class="bulk-confirm-warning" id="bcWarning" style="display: none;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="bulk-confirm-warning-text">
                <strong><?php echo $lang === 'vi' ? 'Cảnh báo:' : 'Warning:'; ?></strong>
                <span id="bcWarningText"></span>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="bulk-confirm-actions">
            <button class="bulk-confirm-btn bulk-confirm-btn-cancel" onclick="closeBulkConfirmPopup()">
                <i class="bi bi-x-lg"></i>
                <?php echo $lang === 'vi' ? 'Hủy bỏ' : 'Cancel'; ?>
            </button>
            <button class="bulk-confirm-btn bulk-confirm-btn-confirm" id="btnBulkConfirm" onclick="confirmBulkProcess()">
                <i class="bi bi-magic"></i>
                <span id="bcConfirmText"><?php echo $lang === 'vi' ? 'Xử lý ngay' : 'Process Now'; ?></span>
            </button>
        </div>
    </div>
</div>
<script>
    window.isElevenLabsDown = <?php echo $elevenlabs_down ? 'true' : 'false'; ?>;
    window.isMinimaxDown = <?php echo $minimax_down ? 'true' : 'false'; ?>;
    window.isKingCongDown = <?php echo $kingcong_down ? 'true' : 'false'; ?>;
    window.isBackupEligible = <?php echo $backup_eligible ? 'true' : 'false'; ?>;
    window.jsLang = <?php echo json_encode($t); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="/pages/AI/tts3.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>