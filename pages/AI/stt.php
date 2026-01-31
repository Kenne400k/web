<?php
$page_title = 'Chuyển giọng nói thành văn bản - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

$translations = [
    'vi' => [
        'important_note_title' => 'Lưu ý quan trọng:',
        'support_audio_files' => 'Hỗ trợ file audio: MP3, AAC, WAV',
        'max_size' => 'Kích thước tối đa: 200MB',
        'output_format' => 'Kết quả trả về: SRT subtitle',
        'audio_file_label' => 'Tệp audio',
        'upload_title' => 'Nhấp hoặc kéo thả file vào đây',
        'upload_desc_formats' => 'Định dạng hỗ trợ: MP3, AAC, WAV',
        'upload_desc_output' => 'Trả ra: SRT subtitle',
        'selected_files' => 'Đã chọn:',
        'file_unit' => 'file',
        'add_file' => 'Thêm file',
        'clear_all' => 'Xóa hết',
        'estimate_cost' => 'Chi phí dự kiến',
        'start_transcription' => 'Bắt đầu chuyển đổi',
        'select_all' => 'Chọn tất cả',
        'download_srt' => 'Tải SRT',
        'delete' => 'Xóa',
        'refresh_list_title' => 'Làm mới danh sách',
        'no_tasks' => 'Chưa có tác vụ nào',
        'delete_modal_title' => 'Xác nhận xóa',
        'delete_modal_prefix' => 'Bạn có chắc chắn muốn xóa',
        'delete_modal_suffix' => 'task đã chọn?',
        'delete_modal_note' => 'Hành động này không thể hoàn tác.',
        'cancel' => 'Hủy',
        'toast_refresh_success' => '✅ Đã làm mới!',
        'toast_refresh_fail' => '❌ Không thể tải dữ liệu',
        'toast_connection_error' => '❌ Lỗi kết nối',
        'invalid_format' => 'định dạng không hỗ trợ',
        'invalid_too_large' => 'quá lớn, max 200MB',
        'invalid_duplicate' => 'đã tồn tại',
        'invalid_files_prefix' => '{count} file không hợp lệ: {files}',
        'duration_loading' => 'Đang tải...',
        'duration_na' => 'N/A',
        'remove' => 'Xóa',
        'confirm_clear_all' => 'Xóa tất cả {count} file?',
        'select_audio_first' => 'Vui lòng chọn file audio!',
        'wait_metadata' => 'Vui lòng đợi tất cả file load xong metadata!',
        'insufficient_credits' => 'Không đủ credits! Cần {required}, còn {current}',
        'uploading_files' => 'Đang tải {count} file...',
        'task_create_success' => 'Đã tạo task thành công!',
        'upload_error_generic' => 'Có lỗi xảy ra!',
        'failed_files_header' => 'File thất bại:',
        'history_empty' => 'Không có dữ liệu',
        'download_subtitle' => 'Tải Subtitle',
        'status_failed' => 'Thất bại',
        'status_processing' => 'Đang xử lý',
        'status_processing_with_progress' => 'Đang xử lý ({progress}%)',
        'credits_used' => 'Tín dụng sử dụng',
        'bulk_select_min_one' => 'Vui lòng chọn ít nhất 1 item!',
        'bulk_download_json_start' => 'Đang tải {count} file JSON...',
        'bulk_download_srt_start' => 'Đang tải {count} file SRT...',
        'bulk_no_json' => 'Không có file JSON nào để tải!',
        'bulk_no_srt' => 'Không có file SRT nào để tải!',
        'bulk_select_delete' => 'Vui lòng chọn ít nhất 1 item để xóa!',
        'delete_no_ids' => 'Không có ID hợp lệ để xóa!',
        'delete_success' => '✅ Đã xóa {count} task!',
        'delete_error_generic' => 'Có lỗi xảy ra!',
        'delete_connection_error' => '❌ Lỗi kết nối!',
        'task_completed' => '✅ Task hoàn thành!',
        'error_prefix' => 'Lỗi kết nối:',
        'retry_timeout' => 'Timeout hoặc server không phản hồi',
    ],
    'en' => [
        'important_note_title' => 'Important notes:',
        'support_audio_files' => 'Supported audio files: MP3, AAC, WAV',
        'max_size' => 'Maximum size: 200MB',
        'output_format' => 'Output: SRT subtitle',
        'audio_file_label' => 'Audio file',
        'upload_title' => 'Click or drag & drop files here',
        'upload_desc_formats' => 'Supported formats: MP3, AAC, WAV',
        'upload_desc_output' => 'Output: SRT subtitle',
        'selected_files' => 'Selected:',
        'file_unit' => 'files',
        'add_file' => 'Add file',
        'clear_all' => 'Clear all',
        'estimate_cost' => 'Estimated cost',
        'start_transcription' => 'Start transcription',
        'select_all' => 'Select all',
        'download_srt' => 'Download SRT',
        'delete' => 'Delete',
        'refresh_list_title' => 'Refresh list',
        'no_tasks' => 'No tasks yet',
        'delete_modal_title' => 'Confirm deletion',
        'delete_modal_prefix' => 'Are you sure you want to delete',
        'delete_modal_suffix' => 'selected task(s)?',
        'delete_modal_note' => 'This action cannot be undone.',
        'cancel' => 'Cancel',
        'toast_refresh_success' => '✅ Refreshed!',
        'toast_refresh_fail' => '❌ Unable to load data',
        'toast_connection_error' => '❌ Connection error',
        'invalid_format' => 'unsupported format',
        'invalid_too_large' => 'too large, max 200MB',
        'invalid_duplicate' => 'already exists',
        'invalid_files_prefix' => '{count} invalid file(s): {files}',
        'duration_loading' => 'Loading...',
        'duration_na' => 'N/A',
        'remove' => 'Remove',
        'confirm_clear_all' => 'Remove all {count} file(s)?',
        'select_audio_first' => 'Please select audio files!',
        'wait_metadata' => 'Please wait for all files to finish loading metadata!',
        'insufficient_credits' => 'Not enough credits! Need {required}, have {current}',
        'uploading_files' => 'Uploading {count} file(s)...',
        'task_create_success' => 'Task created successfully!',
        'upload_error_generic' => 'An error occurred!',
        'failed_files_header' => 'Failed files:',
        'history_empty' => 'No data',
        'download_subtitle' => 'Download subtitle',
        'status_failed' => 'Failed',
        'status_processing' => 'Processing',
        'status_processing_with_progress' => 'Processing ({progress}%)',
        'credits_used' => 'Credits used',
        'bulk_select_min_one' => 'Please select at least one item!',
        'bulk_download_json_start' => 'Downloading {count} JSON file(s)...',
        'bulk_download_srt_start' => 'Downloading {count} SRT file(s)...',
        'bulk_no_json' => 'No JSON files to download!',
        'bulk_no_srt' => 'No SRT files to download!',
        'bulk_select_delete' => 'Please select at least one item to delete!',
        'delete_no_ids' => 'No valid IDs to delete!',
        'delete_success' => '✅ Deleted {count} task(s)!',
        'delete_error_generic' => 'An error occurred!',
        'delete_connection_error' => '❌ Connection error!',
        'task_completed' => '✅ Task completed!',
        'error_prefix' => 'Connection error:',
        'retry_timeout' => 'Timeout or server did not respond',
    ],
];

$t = $translations[$lang] ?? $translations['vi'];

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
                <strong><?php echo $t['important_note_title']; ?></strong>
                <ul>
                    <li><?php echo $t['support_audio_files']; ?></li>
                    <li><?php echo $t['max_size']; ?></li>
                    <li><?php echo $t['output_format']; ?></li>
                </ul>
            </div>
        </div>

        <div class="upload-zone-wrap">
    <label class="upload-label">
        <?php echo $t['audio_file_label']; ?> <span class="required">*</span>
    </label>

    <input type="file" id="audioInput" accept=".mp3,.aac,.wav" style="display: none;" onchange="handleFile(this)" multiple>

    <div class="upload-zone" id="uploadZone" onclick="$('#audioInput').click()">
        <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
        <div class="upload-title"><?php echo $t['upload_title']; ?></div>
        <div class="upload-desc">
            <?php echo $t['upload_desc_formats']; ?><br><?php echo $t['upload_desc_output']; ?>
        </div>
    </div>

    <!-- DANH SÁCH FILE ĐÃ CHỌN -->
    <div id="fileListContainer" style="display: none;">
        <div class="file-list-header">
            <span class="file-count"><?php echo $t['selected_files']; ?> <span id="fileCount">0</span> <?php echo $t['file_unit']; ?></span>
            <div class="file-list-actions">
                <button class="btn-add-more" onclick="$('#audioInput').click()" title="<?php echo $t['add_file']; ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span><?php echo $t['add_file']; ?></span>
                </button>
                <button class="btn-clear-all" onclick="clearAllFiles()" title="<?php echo $t['clear_all']; ?>">
                    <i class="bi bi-trash"></i>
                    <span><?php echo $t['clear_all']; ?></span>
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
        <span class="cost-label"><?php echo $t['estimate_cost']; ?></span>
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
        <span><?php echo $t['start_transcription']; ?></span>
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
            <span><?php echo $t['select_all']; ?> (<span id="selectedCount">0</span>)</span>
        </button>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <div class="separator"></div>
            <button class="btn-bulk" onclick="bulkDownloadSRT()" title="<?php echo $t['download_srt']; ?>">
                <i class="bi bi-file-text"></i>
                <span><?php echo $t['download_srt']; ?></span>
            </button>
            <button class="btn-bulk delete" onclick="bulkDelete()" title="<?php echo $t['delete']; ?>">
                <i class="bi bi-trash"></i>
                <span><?php echo $t['delete']; ?></span>
            </button>
        </div>
        
        <!-- 🔥 NÚT MỚI: LÀM MỚI -->
        <button class="btn-refresh" onclick="refreshHistory()" id="btnRefresh" title="<?php echo $t['refresh_list_title']; ?>">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

        <div id="historyList" class="history-list">
            <div class="history-empty">
                <i class="bi bi-inbox"></i>
                <span><?php echo $t['no_tasks']; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 🔥 DELETE CONFIRMATION MODAL -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 32px;"></i>
            <h3><?php echo $t['delete_modal_title']; ?></h3>
        </div>
        <div class="modal-body">
            <p><?php echo $t['delete_modal_prefix']; ?> <strong id="deleteCount">0</strong> <?php echo $t['delete_modal_suffix']; ?></p>
            <p style="color: #888; font-size: 14px; margin-top: 8px;">
                <?php echo $t['delete_modal_note']; ?>
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

<audio id="sttAudio" style="display: none;"></audio>

<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    const userCreditsFromDB = <?php echo $user_credits; ?>;
    window.sttLang = <?php echo json_encode($t); ?>;
</script>

<script src="/pages/AI/js/stt.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>