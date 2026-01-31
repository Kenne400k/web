// ========== GLOBAL VARIABLES ==========
let currentProvider = 'elevenlabs';
let loadedVoices = { elevenlabs: [], minimax: [] };
let loadedModels = { elevenlabs: [], minimax: [] };
let allVoicesForFilter = [];
let isFilteringFavorites = false;
let favoriteVoices = JSON.parse(localStorage.getItem('favVoices')) || [];


// History & Audio
let currentOffset = 0;
let isLoadingHistory = false;
let hasMoreHistory = true;
let mainAudio = document.getElementById('globalAudio');
let previewAudio = document.getElementById('previewAudio');
let currentPlayingTaskId = null;
let currentPreviewUrl = null;
let selectedLanguage = 'Auto';
let selectedMinimaxModel = 'speech-2.6-hd';
let pendingDeleteId = null;
let pendingDeleteType = ''; // 'refund' hoặc 'history'
let pendingDeleteCost = 0;
let historyDataMap = {};
let detailedHistoryData = [];
let detailedProcessingTasks = [];
let detailedHistoryPerPage = 20;
let sharedVoicesRendered = 0;
let sharedVoicesRenderBatch = 50;
let isRenderingVoices = false;
let voiceGridScrollHandler = null;

// ========== LANGUAGE SUPPORT ==========
const currentLang = document.documentElement.lang || 'vi';

const langStrings = {
    vi: {
        selectVoice: 'Chọn giọng nói...',
        processing: 'Đang xử lý',
        done: 'Hoàn thành',
        failed: 'Thất bại',
        timeout: 'Timeout',
        queued: 'Hàng đợi',
        noVoicesFound: 'Không tìm thấy giọng nói',
        clearFilters: 'Xóa bộ lọc',
        searching: 'Đang tìm kiếm...',
        notFound: 'Không tìm thấy',
        deleteConfirm: 'Bạn có chắc chắn muốn xóa?',
        deleteSuccess: 'Đã xóa thành công',
        selectVoiceFirst: 'Vui lòng chọn giọng nói trước!',
        enterText: 'Vui lòng nhập văn bản!',
        copied: 'Đã copy!',
        addedToFavorites: 'Đã thêm vào yêu thích',
        removedFromFavorites: 'Đã xóa khỏi yêu thích',
    },
    en: {
        selectVoice: 'Select voice...',
        processing: 'Processing',
        done: 'Done',
        failed: 'Failed',
        timeout: 'Timeout',
        queued: 'Queued',
        noVoicesFound: 'No voices found',
        clearFilters: 'Clear filters',
        searching: 'Searching...',
        notFound: 'Not found',
        deleteConfirm: 'Are you sure you want to delete?',
        deleteSuccess: 'Deleted successfully',
        selectVoiceFirst: 'Please select a voice first!',
        enterText: 'Please enter text!',
        copied: 'Copied!',
        addedToFavorites: 'Added to favorites',
        removedFromFavorites: 'Removed from favorites',
    }
};

const jsTranslations = langStrings[currentLang];

// ========== HELPER: PARSE CUSTOM DATE FORMAT ==========
function parseCustomDateTime(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') {
        return null;
    }
    
    // ✅ FORMAT 1: DD/MM/YYYY HH:mm (ElevenLabs/AI33)
    let regex1 = /^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/;
    let match1 = dateStr.match(regex1);
    
    if (match1) {
        let [_, day, month, year, hour, minute, second] = match1;
        
        let date = new Date(
            parseInt(year),
            parseInt(month) - 1,
            parseInt(day),
            parseInt(hour),
            parseInt(minute),
            parseInt(second || 0)
        );
        
        if (!isNaN(date.getTime())) {
            return date.getTime();
        }
    }
    
    // ✅ FORMAT 2: YYYY-MM-DD HH:mm:ss (GenAI/MySQL)
    let regex2 = /^(\d{4})-(\d{1,2})-(\d{1,2})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/;
    let match2 = dateStr.match(regex2);
    
    if (match2) {
        let [_, year, month, day, hour, minute, second] = match2;
        
        let date = new Date(
            parseInt(year),
            parseInt(month) - 1,
            parseInt(day),
            parseInt(hour),
            parseInt(minute),
            parseInt(second || 0)
        );
        
        if (!isNaN(date.getTime())) {
            return date.getTime();
        }
    }
    
    // ✅ FALLBACK: Try ISO format (YYYY-MM-DDTHH:mm:ss)
    try {
        let isoStr = dateStr.replace(' ', 'T');
        let timestamp = new Date(isoStr).getTime();
        
        if (!isNaN(timestamp)) {
            return timestamp;
        }
    } catch (e) {
        // Ignore
    }
    
    console.warn('⚠️ Invalid date format:', dateStr);
    return null;
}
function openDetailedHistory() {
    $('#detailedHistoryModal').fadeIn(200);
    loadDetailedHistoryData();
}

function closeDetailedHistory() {
    $('#detailedHistoryModal').fadeOut(200);
}
function loadDetailedHistoryData(page = 1) {
    let $listContainer = $('#detailedHistoryList');
    
    // Show loading
    $listContainer.html('<div style="text-align:center; padding:50px;"><div class="spinner-border text-primary"></div></div>');
    
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        data: { 
            action: 'get_history_detailed',  // 🔥 Action riêng cho detailed view
            limit: detailedHistoryPerPage || 20,
            page: page
        },
        dataType: 'json',
        timeout: 10000,
        success: function(res) {
            console.log('📥 Full backend response:', res);
            
            if (res.status === 'success' && res.data) {
                console.log('📋 Total items:', res.data.length);
                
                // 🔥 LOG CHI TIẾT TỪNG TASK
                res.data.forEach((item, index) => {
                    console.log(`📦 Task ${index}:`, {
                        id: item.id,                    // History ID
                        task_id: item.task_id,          // Task ID (queue_39 hoặc task_xxx)
                        status: item.status,
                        created_at: item.created_at,
                        is_genai: item.is_genai_backup,
                        parsed_time: parseCustomDateTime(item.created_at)
                    });
                    
                    // ⚠️ WARNING nếu thiếu data
                    if (!item.id) {
                        console.error('❌ Task thiếu history ID:', item);
                    }
                    if (!item.task_id) {
                        console.error('❌ Task thiếu task_id:', item);
                    }
                    if (!item.created_at) {
                        console.warn('⚠️ Task thiếu created_at:', item);
                    }
                });
                
                // Save to global
                detailedHistoryData = res.data;
                
                // Render list
                renderDetailedList(res.data);
                
                // 🔥 RENDER PAGINATION (nếu có)
                if (res.total_pages > 1) {
                    renderDetailedPagination(res.page, res.total_pages, res.total);
                }
                
            } else {
                console.error('❌ Backend error:', res);
                $listContainer.html(`
                    <div style="padding:20px; text-align:center; color:#dc3545;">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${res.message || 'Lỗi tải dữ liệu'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            $listContainer.html(`
                <div style="padding:20px; text-align:center; color:#dc3545;">
                    <i class="fas fa-exclamation-circle"></i> 
                    Không thể kết nối đến server
                </div>
            `);
        }
    });
}
// Biến toàn cục để lưu trữ các task ID cần được theo dõi (polling)
//let detailedProcessingTasks = [];
function renderDetailedPagination(currentPage, totalPages, totalItems) {
    let $pagination = $('#detailedHistoryPagination');
    
    if (!$pagination.length) {
        // Tạo container nếu chưa có
        $('#detailedHistoryModal .modal-body').append('<div id="detailedHistoryPagination" class="mt-3"></div>');
        $pagination = $('#detailedHistoryPagination');
    }
    
    let html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage - 1}">Trước</a>
    </li>`;
    
    // Page numbers (show max 5 pages)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }
    
    // Next button
    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">Sau</a>
    </li>`;
    
    html += `</ul></nav>`;
    html += `<div class="text-center text-muted small">Tổng ${totalItems} tasks</div>`;
    
    $pagination.html(html);
    
    // Event handler
    $pagination.find('.page-link').on('click', function(e) {
        e.preventDefault();
        let page = parseInt($(this).data('page'));
        if (page >= 1 && page <= totalPages) {
            loadDetailedHistoryData(page);
        }
    });
}
function renderDetailedList(data) {
    let html = '';
    detailedProcessingTasks = []; // Reset danh sách task đang xử lý

    // 🔥 Dừng tất cả interval đang chạy trước khi render mới
    for (const taskId in detailedIntervals) {
         clearInterval(detailedIntervals[taskId]);
    }
    detailedIntervals = {}; // Reset object

    if (!data || data.length === 0) {
        $('#detailedHistoryList').html('<div style="padding:20px; text-align:center; color:#666;">Chưa có dữ liệu</div>');
        return;
    }

    console.log('🎨 Rendering', data.length, 'tasks');

    data.forEach((item, index) => {
        // 🔥 VALIDATE DATA
        if (!item.id) return; // Skip invalid
        if (!item.task_id) return; // Skip invalid
        
        let statusBadge = '';
        let contentArea = '';
        let creditLabel = 'Tín dụng sử dụng';
        
        // 🔥 PARSE TIMESTAMP
        let createdTimeMs = parseCustomDateTime(item.created_at);
        if (!createdTimeMs || isNaN(createdTimeMs)) {
            createdTimeMs = Date.now();
        }
        
        // Icon Provider
        let providerLogo = (typeof getProviderLogo === 'function') ? getProviderLogo(item.provider) : '';

        // Xử lý text an toàn
        let safeText = (item.text_input || '').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/(\r\n|\n|\r)/g, ' '); 

        // ===============================================
        // 🔥 [CHUẨN BỊ CÁC NÚT BẤM (BUTTONS)] 
        // ===============================================

        // 1. NÚT XÓA (Dùng chung cho mọi trạng thái)
        let deleteBtnHtml = `
            <button class="dh-delete-btn" 
                onclick="deleteDetailedTask('${item.task_id}', '${safeText}', '${item.status}', ${item.credit_cost || 0})" 
                title="Xóa task">
                <i class="bi bi-trash"></i>
            </button>`;

        // 2. NÚT SRT (Chỉ hiển thị cho GenAI/Minimax)
        // Logic: Có link ? Nút Tải xuống : Nút Bấm để tạo
        let exportBtnHtml = '';
        let isGenAiTask = (item.provider === 'minimax') || (item.is_genai_backup == 1);
        
        if (isGenAiTask) {
            // 🔥 KIỂM TRA: Nếu có link trong DB -> Hiện nút TẢI
            if (item.srt_url && item.srt_url.length > 5) {
                exportBtnHtml = `
                    <a href="${item.srt_url}" download
                        class="dh-delete-btn" 
                        title="Tải xuống SRT"
                        style="color: #667eea; border-color: #667eea; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </a>
                `;
            } else {
                // 🔥 CHƯA CÓ -> HIỆN NÚT TẠO
                // Sửa lại: Chỉ dùng 1 ID duy nhất
                exportBtnHtml = `
                    <button class="dh-delete-btn" 
                        id="btn-srt-req-${item.task_id}"
                        onclick="openSrtModal('${item.task_id}')" 
                        title="Cài đặt & Tạo SRT"
                        style="color: #667eea; border-color: #667eea;">
                        <i class="bi bi-file-earmark-plus"></i>
                    </button>
                `;
            }
        }

        // ===============================================
        // 🔥 [XỬ LÝ TRẠNG THÁI HIỂN THỊ] 
        // ===============================================

        // ▶️ TRẠNG THÁI: DONE
        if (item.status === 'done') {
            statusBadge = `<span class="dh-status-badge dh-badge-done">Xong</span>`;
            creditLabel = 'Tín dụng sử dụng';
            let durationText = item.duration ? formatTime(item.duration) : "--:--"; 

            // Nút Tải Audio gốc
            let downloadAudioBtn = `
                <a href="${item.audio_url}" download class="bi bi-download" 
                   style="color:#fff; font-size:16px; text-decoration:none; margin-right:4px;" 
                   title="Tải Audio MP3"></a>
            `;

            // Gom nhóm: [Tải Audio] + [SRT] + [Xóa]
            let actionGroup = `
                <div style="display: flex; align-items: center; gap: 5px; margin-left: auto;">
                    ${downloadAudioBtn}
                    ${exportBtnHtml}
                    ${deleteBtnHtml}
                </div>
            `;

            contentArea = `
                <div class="dh-player" id="dh-player-${item.task_id}">
                    <button class="dh-play-btn" id="dh-play-btn-${item.task_id}" onclick="playAudio('${item.task_id}', '${item.audio_url}')">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <div class="dh-progress-track" onclick="seekAudio(event, '${item.task_id}', true)"> 
                        <div class="dh-progress-bar" id="dh-progress-${item.task_id}" style="width: 0%"></div>
                    </div>
                    <div class="dh-timer" id="dh-timer-${item.task_id}">0:00 / ${durationText}</div>
                    
                    ${actionGroup} 
                </div>
            `;

        } 
        // ❌ TRẠNG THÁI: FAILED
        else if (item.status === 'failed') {
            statusBadge = `<span class="dh-status-badge dh-badge-error">Lỗi</span>`;
            creditLabel = 'Đã hoàn trả';
            // Layout: Text lỗi --- Nút Xóa
            contentArea = `
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                    <div class="dh-status-text dh-text-error"><i class="bi bi-exclamation-circle"></i> ${item.error_message || 'Lỗi không xác định'}</div>
                    ${deleteBtnHtml}
                </div>`;
        } 
        // ⏳ TRẠNG THÁI: PROCESSING
        // ... Bên trong vòng lặp data.forEach ...

// ⏳ TRẠNG THÁI: PROCESSING
else if (['queued', 'pending', 'processing', 'doing'].includes(item.status)) {

    let elapsedMs = Date.now() - createdTimeMs;
    let elapsedSeconds = Math.floor(elapsedMs / 1000);
    let initialTimeString = (typeof formatTime === 'function') ? formatTime(elapsedSeconds) : '0:00';
    let currentProgress = parseInt(item.progress) || 0;

    // Add to polling list
    detailedProcessingTasks.push({ 
        taskId: item.task_id,
        historyId: item.id,
        startTime: createdTimeMs,
        status: item.status
    });
    
    statusBadge = `<span class="dh-status-badge dh-badge-processing">Đang xử lý</span>`;
    creditLabel = 'Tín dụng đóng băng';
    
    // 🔥 [SỬA ĐOẠN NÀY] Logic hiển thị Text
    let initialText = '';

    if (item.status === 'queued') {
        initialText = item.queue_position ? `Hàng đợi #${item.queue_position}` : `Hàng đợi`;
    } else {
        // Nếu là GenAI Backup -> Chỉ hiện "Đang xử lý" (Bỏ %)
        if (item.is_genai_backup == 1) {
            initialText = 'Đang xử lý';
        } else {
            // Nếu là thường -> Hiện %
            initialText = (currentProgress > 0) ? `Xử lý ${currentProgress}%` : `Đang xử lý`;
        }
    }
    
    initialText += ` (${initialTimeString})`;

    // 🔥 [THÊM] data-genai="${item.is_genai_backup}" vào thẻ span để hàm polling nhận biết
    contentArea = `
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
            <div class="dh-status-text dh-text-processing">
                <div class="spinner-border spinner-border-sm" style="width:1rem; height:1rem;"></div>
                <span id="dh-time-elapsed-${item.task_id}" 
                      data-progress="${currentProgress}" 
                      data-genai="${item.is_genai_backup}">
                      ${initialText}
                </span>
            </div>
            ${deleteBtnHtml}
        </div>
    `;
}

        // Format Date Display
        let timeDisplay = item.created_at; 
        if (item.created_at && (item.created_at.includes('T') || item.created_at.includes('-'))) {
             let d = new Date(item.created_at);
             timeDisplay = d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0') + ' ' + d.getDate() + '/' + (d.getMonth()+1);
        }

        // ===============================================
        // 🔥 [RENDER ROW HTML] 
        // ===============================================
        html += `
        <div class="dh-row" id="row-${item.task_id}" data-start-time="${createdTimeMs}" data-history-id="${item.id}">
            <div class="dh-checkbox-wrapper">
                <input type="checkbox" class="dh-item-checkbox" value="${item.task_id}" 
                    data-audio="${item.audio_url || ''}" 
                    data-srt="${item.srt_url || ''}" 
                    data-json="${item.json_url || ''}" 
                    onchange="updateBulkActions()">
            </div>
            
            <div class="dh-info">
                <div class="dh-time">
                    ${timeDisplay} 
                    ${providerLogo ? `<img src="${providerLogo}" class="dh-provider-icon">` : ''}
                </div>
                <div class="dh-text-preview" title="${safeText}">${item.text_input || 'N/A'}</div>
            </div>

            ${statusBadge}

            <div class="dh-content-area">
                ${contentArea}
            </div>

            <div class="dh-credits">
                <span class="dh-credits-val">${item.credit_cost || 0}</span>
                <span class="dh-credits-label">${creditLabel}</span>
            </div>
        </div>
        `;
    });

    $('#detailedHistoryList').html(html);
    
    // Start Polling
    detailedProcessingTasks.forEach(task => {
        if (typeof startDetailedPolling === 'function') {
            startDetailedPolling(task.taskId, task.historyId, task.startTime);
        }
    });
}
function requestCreateSrt(taskId, btnElement) {
    let originalContent = btnElement.innerHTML;
    $(btnElement).html('<div class="spinner-border spinner-border-sm" role="status"></div>');
    $(btnElement).prop('disabled', true); 

    $.ajax({
        url: '/ajaxs/tts.php', // Đảm bảo đường dẫn đúng
        method: 'POST',
        dataType: 'json',
        data: { 
            action: 'export_custom_srt', 
            task_id: taskId
        },
        success: function(response) {
            if (response.status === 'success' && response.download_url) {
                // Đổi nút ngay lập tức trên giao diện
                let downloadBtnHtml = `
                    <a href="${response.download_url}" download="${response.filename || 'subtitle.srt'}"
                        class="dh-delete-btn" 
                        title="Tải xuống SRT"
                        style="color: #667eea; border-color: #667eea; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </a>
                `;
                $(btnElement).replaceWith(downloadBtnHtml);
                if(typeof toastr !== 'undefined') toastr.success('Đã tạo file SRT thành công!');
            } else {
                let msg = response.message || 'Không thể tạo SRT lúc này.';
                if(typeof toastr !== 'undefined') toastr.warning(msg);
                $(btnElement).html(originalContent);
                $(btnElement).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Lỗi:", error);
            $(btnElement).html(originalContent);
            $(btnElement).prop('disabled', false);
            if(typeof toastr !== 'undefined') toastr.error('Lỗi kết nối server');
        }
    });
}
// ========================================
// XÓA TASK TỪ MODAL CHI TIẾT
// ========================================
function deleteDetailedTask(taskId, textPreview, status, cost) {
    console.log('🗑️ Delete from detailed modal:', taskId);
    
    // Xác định loại xóa
    let deleteType = '';
    
    if (status === 'pending' || status === 'processing' || status === 'doing' || status === 'queued') {
        // Task đang chạy → Có hoàn tiền
        deleteType = 'refund';
    } else {
        // Task đã hoàn thành/lỗi → Xóa lịch sử
        deleteType = 'history';
    }
    
    // Mở Modal xác nhận
    openDeleteModal(taskId, textPreview, deleteType, cost);
}
// 1. Mở Modal
function openSrtModal(taskId) {
    $('#srtCurrentTaskId').val(taskId); // Lưu ID task đang chọn
    $('#srtSettingsModal').fadeIn(200);
}

// 2. Đóng Modal
function closeSrtModal() {
    $('#srtSettingsModal').fadeOut(200);
}

// 3. Reset về mặc định
function resetSrtSettings() {
    $('#srtMaxChars').val(42);
    $('#srtMaxLines').val(2);
    $('#srtMaxDuration').val(7);
}

function submitSrtExport() {
    // 1. Lấy dữ liệu từ Modal
    const taskId = $('#srtCurrentTaskId').val();
    const maxChars = $('#srtMaxChars').val();
    const maxLines = $('#srtMaxLines').val();
    const maxDuration = $('#srtMaxDuration').val();

    console.log('📤 [FE] Gửi yêu cầu SRT:', { taskId, maxChars, maxLines, maxDuration });

    if (!taskId) {
        alert("Lỗi: Không tìm thấy Task ID!");
        return;
    }

    // 2. Hiệu ứng Loading trên nút trong Modal
    const $btn = $('.srt-btn-export');
    const oldText = $btn.text();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');

    // 3. Gửi Ajax
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'export_custom_srt',
            task_id: taskId,
            max_chars: maxChars,
            max_lines: maxLines,
            max_duration: maxDuration
        },
        success: function(res) {
            // Trả lại trạng thái nút
            $btn.prop('disabled', false).text(oldText);
            console.log('📥 [FE] Nhận phản hồi:', res);

            // 🔥 [FIX] Kiểm tra cả trạng thái VÀ nội dung thông báo lỗi
            // Nếu server báo "Task is not completed", ta coi như nó đang xử lý để hiện Popup chờ
            let isProcessing = (res.status === 'processing' || res.task_status === 'processing' || 
                                res.task_status === 'pending' || res.task_status === 'queued');
            
            // 👇 THÊM DÒNG NÀY: Bắt lỗi "Task is not completed" và coi là đang xử lý
            if (res.message && res.message.includes('Task is not completed')) {
                isProcessing = true;
            }

            // 🔥 [MỚI] HIỂN THỊ POPUP NẾU ĐANG XỬ LÝ
            if (isProcessing) {
                // Đóng Modal Settings
                closeSrtModal();
                
                // Hiển thị Popup thông báo đẹp
                showSrtProcessingPopup(taskId);
                return;
            }
            if (res.status === 'success') {
                // A. Đóng Modal
                closeSrtModal();

                // B. Tự động tải xuống file
                const a = document.createElement('a');
                a.style.display = 'none';
                document.body.appendChild(a);

                if (res.download_url) {
                    a.href = res.download_url;
                } else if (res.file_content_base64) {
                    try {
                        const byteCharacters = atob(res.file_content_base64);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const blob = new Blob([byteArray], {type: "text/srt"});
                        a.href = URL.createObjectURL(blob);
                    } catch (e) {
                        alert("Lỗi tạo file tải xuống!");
                        return;
                    }
                }

                a.download = res.filename || `subtitle_${taskId}.srt`;
                a.click();
                
                setTimeout(() => {
                    document.body.removeChild(a);
                    if (a.href.startsWith('blob:')) URL.revokeObjectURL(a.href);
                }, 100);

                // C. Cập nhật giao diện
                let newDownloadBtn = `
                    <a href="${res.download_url}" download="${res.filename || 'subtitle.srt'}"
                        class="dh-delete-btn" 
                        title="Tải xuống SRT"
                        style="color: #667eea; border-color: #667eea; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </a>
                `;

                let $triggerBtn = $(`#btn-srt-req-${taskId}`);
                if ($triggerBtn.length === 0) {
                    $triggerBtn = $(`#btn-srt-trigger-${taskId}`); 
                }
                if ($triggerBtn.length === 0) {
                    $triggerBtn = $(`button[onclick*="openSrtModal('${taskId}')"]`);
                }

                if ($triggerBtn.length > 0) {
                    $triggerBtn.replaceWith(newDownloadBtn);
                }

                // D. Thông báo thành công
                if(typeof showToast === 'function') {
                    showToast('✅ Tạo & Tải phụ đề thành công!');
                } else if(typeof toastr !== 'undefined') {
                    toastr.success('Đã tạo file SRT thành công!');
                }

            } else {
                alert('⚠️ ' + (res.message || 'Lỗi không xác định từ server'));
            }
        },
        error: function(xhr, status, error) {
            $btn.prop('disabled', false).text(oldText);
            console.error("❌ AJAX Error Raw:", xhr.responseText);
            
            let errorMsg = 'Lỗi kết nối server';
            try {
                let errJson = JSON.parse(xhr.responseText);
                if (errJson.message) errorMsg = errJson.message;
            } catch(e) {}

            alert(`❌ ${errorMsg} (${xhr.status})`);
        }
    });
}
// 🔥 HÀM HIỂN THỊ POPUP XÁC NHẬN (PHIÊN BẢN FIX LỖI CLICK)
function showBulkDeleteConfirm(count, onConfirmCallback) {
    // 1. Xóa popup cũ nếu bị kẹt
    $('#bulkDeletePopup').remove();

    console.log("🟢 Đang mở popup xóa cho " + count + " items..."); // Debug log

    // 2. HTML Popup (Chỉ chứa giao diện, KHÔNG chứa thẻ <script>)
    let html = `
    <div id="bulkDeletePopup" style="
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); /* Đậm hơn chút để che nền */
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 2147483647; /* Z-index cao nhất có thể */
        opacity: 0; animation: bdFadeIn 0.2s forwards;
    ">
        <div class="bd-popup-content" style="
            background: #111; border: 1px solid #333;
            border-radius: 20px; padding: 32px; width: 90%; max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8); text-align: center;
            transform: scale(0.95); opacity: 0;
            animation: bdSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.05s forwards;
        ">
            <div style="width: 50px; height: 50px; margin: 0 auto 20px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-trash3-fill" style="font-size: 22px; color: #fff;"></i>
            </div>

            <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #fff;">Xác nhận xóa?</h3>
            <p style="color: #999; font-size: 14px; margin-bottom: 24px; line-height: 1.5;">
                Bạn có chắc chắn muốn xóa vĩnh viễn <b style="color:#fff">${count}</b> task này?<br>Hành động này không thể hoàn tác.
            </p>

            <div style="display: flex; gap: 10px;">
                <button id="bdCancelBtn" style="flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #333; background: transparent; color: #ccc; font-weight: 500; cursor: pointer;">Hủy bỏ</button>
                <button id="bdConfirmBtn" style="flex: 1; padding: 12px; border-radius: 10px; border: none; background: #fff; color: #000; font-weight: 700; cursor: pointer;">Xóa ngay</button>
            </div>
        </div>
        <style>
            @keyframes bdFadeIn { to { opacity: 1; } }
            @keyframes bdSlideUp { to { opacity: 1; transform: scale(1); } }
        </style>
    </div>`;

    // 3. Chèn vào body
    $('body').append(html);

    // 4. 🔥 GẮN SỰ KIỆN CLICK BẰNG JQUERY (CHẮC CHẮN CHẠY)
    
    // Nút Hủy
    $('#bdCancelBtn').on('click', function() {
        $('#bulkDeletePopup').fadeOut(200, function() { $(this).remove(); });
    });

    // Nút Xác nhận
    $('#bdConfirmBtn').on('click', function() {
        // Hiệu ứng loading nút
        $(this).prop('disabled', true).css('opacity', '0.7').text('Đang xóa...');
        $('#bdCancelBtn').prop('disabled', true);

        // Gọi callback xóa
        if (typeof onConfirmCallback === 'function') {
            onConfirmCallback();
        }
    });
}
function showSrtProcessingPopup(taskId) {
    // Xóa popup cũ nếu có
    $('#srtProcessingPopup').remove();
    
    let html = `
    <div id="srtProcessingPopup" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        animation: overlayFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    ">
        <div class="srt-popup-content" style="
            background: #050505;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            transform: scale(0.95) translateY(10px);
            opacity: 0;
            animation: modalSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.1s forwards;
        ">
            <div style="
                width: 50px;
                height: 50px;
                margin: 0 auto 24px;
                position: relative;
            ">
                <div style="
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    border: 2px solid rgba(255,255,255,0.1);
                    border-top: 2px solid #fff;
                    border-radius: 50%;
                    animation: spin 1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
                "></div>
            </div>

            <h3 style="
                margin: 0 0 8px 0;
                font-size: 20px;
                font-weight: 600;
                color: #fff;
                letter-spacing: -0.5px;
            ">Đang khởi tạo SRT</h3>
            
            <p style="
                color: #888;
                font-size: 14px;
                line-height: 1.5;
                margin-bottom: 30px;
                font-weight: 400;
            ">
                Hệ thống đang xử lý yêu cầu.<br>
                Vui lòng đợi khoảng <b>30s - 1 phút</b>.
            </p>

            <div style="
                width: 100%;
                height: 2px;
                background: rgba(255,255,255,0.1);
                border-radius: 2px;
                overflow: hidden;
                margin-bottom: 30px;
                position: relative;
            ">
                <div style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    height: 100%;
                    width: 0%;
                    background: #fff;
                    box-shadow: 0 0 10px rgba(255,255,255,0.5);
                    border-radius: 2px;
                    animation: smoothProgress 60s linear forwards;
                "></div>
            </div>

            <button onclick="closeSrtPopup()" class="srt-close-btn" style="
                width: 100%;
                padding: 12px;
                background: #fff;
                color: #000;
                border: none;
                border-radius: 12px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s;
            ">
                Đã hiểu
            </button>
        </div>
    </div>
    
    <style>
    /* Animation Overlay */
    @keyframes overlayFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Animation Modal: Slide lên nhẹ nhàng + Scale */
    @keyframes modalSlideUp {
        from { 
            opacity: 0; 
            transform: scale(0.95) translateY(15px); 
        }
        to { 
            opacity: 1; 
            transform: scale(1) translateY(0); 
        }
    }

    /* Animation Loading Spinner */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Animation Progress Bar */
    @keyframes smoothProgress {
        0% { width: 0%; }
        20% { width: 40%; } /* Chạy nhanh lúc đầu tạo cảm giác mượt */
        100% { width: 95%; }
    }

    /* Hiệu ứng Hover Nút */
    .srt-close-btn:hover {
        opacity: 0.9;
        transform: scale(0.98);
    }
    .srt-close-btn:active {
        transform: scale(0.95);
    }
    </style>
    
    <script>
    function closeSrtPopup() {
        const popup = document.getElementById('srtProcessingPopup');
        const content = popup.querySelector('.srt-popup-content');
        
        // Hiệu ứng đóng ngược lại
        content.style.transition = 'all 0.3s cubic-bezier(0.32, 0, 0.67, 0)';
        content.style.opacity = '0';
        content.style.transform = 'scale(0.95) translateY(10px)';
        
        popup.style.transition = 'opacity 0.3s ease';
        popup.style.opacity = '0';
        
        setTimeout(() => {
            popup.remove();
        }, 300);
    }
    <\/script>
    `;
    
    $('body').append(html);
    
    // Auto close sau 60 giây
    setTimeout(() => {
        if($('#srtProcessingPopup').length > 0) {
            closeSrtPopup(); // Gọi hàm đóng có animation
        }
    }, 60000);
}
// Biến để lưu trữ interval của Stopwatch trong Modal Chi tiết
let detailedIntervals = {};

// 🔥 THÊM PARAMETER historyId
// 🔥 HÀM POLLING & ĐỒNG HỒ (ĐÃ FIX LỖI NHẤP NHÁY HÀNG ĐỢI)
function startDetailedPolling(taskId, historyId, startTime) {
    // 1. Dọn dẹp interval cũ
    if (detailedIntervals[taskId]) {
        clearInterval(detailedIntervals[taskId]);
        delete detailedIntervals[taskId];
    }

    if (!historyId) return;

    let validStartTime = parseInt(startTime);
    if (isNaN(validStartTime)) validStartTime = Date.now();

    let $elapsedSpan = $(`#dh-time-elapsed-${taskId}`);
    let $row = $(`#row-${taskId}`);

    if ($row.length === 0 || $elapsedSpan.length === 0) return;

    let attempts = 0;
    const maxAttempts = 120; // 2 phút (để tránh treo browser)
    let lastPollTime = 0;

    // --- HÀM GỌI API KIỂM TRA ---
    const checkApiNow = () => {
        $.ajax({
            url: '../../ajaxs/tts.php',
            method: 'POST',
            data: { 
                action: 'check_history_status',
                history_id: parseInt(historyId)
            },
            dataType: 'json',
            timeout: 5000,
            success: function(res) {
    if ($(`#row-${taskId}`).length === 0) return;

    // 🔥 FIX: Parse progress từ API
    let progress = parseInt(res.progress) || 0;
    
    console.log(`📊 Polling ${taskId}: progress = ${progress}%`); // ← DEBUG LOG
    
    $elapsedSpan.attr('data-progress', progress);

                // 🔥 [QUAN TRỌNG] Cập nhật vị trí hàng đợi vào data attribute (để đồng hồ đọc)
                if (res.queue_position) {
                    $elapsedSpan.attr('data-queue-pos', res.queue_position);
                }

                // Xử lý trạng thái
                if (res.status === 'done' || res.status === 'failed') {
                    clearInterval(detailedIntervals[taskId]);
                    delete detailedIntervals[taskId];
                    syncDetailedHistoryCard(taskId, res.status, res.audio_url, res.srt_url, res.json_url, res.duration);
                } 
                // Cập nhật text ngay lập tức khi API trả về để người dùng thấy ngay
                else {
                    updateStatusText();
                }
            }
        });
    };

// --- HÀM CẬP NHẬT TEXT TRÊN GIAO DIỆN (MODAL) ---
    const updateStatusText = () => {
        let elapsedMs = Date.now() - validStartTime;
        let elapsedSeconds = Math.floor(elapsedMs / 1000);
        let timeString = (typeof formatTime === 'function') ? formatTime(elapsedSeconds) : '0:00';
        
        let currentProgress = $elapsedSpan.attr('data-progress') || 0;
        let queuePos = $elapsedSpan.attr('data-queue-pos');
        
        // Lấy cờ GenAI
        let isGenAI = $elapsedSpan.attr('data-genai') == '1';

        let currentText = $elapsedSpan.text().trim();
        let newText = '';

        // Logic hiển thị
        if (currentText.includes('Hàng đợi') || (queuePos && parseInt(queuePos) > 0)) {
            let queueLabel = queuePos ? `Hàng đợi #${queuePos}` : `Hàng đợi`;
            newText = `${queueLabel} (${timeString})`;
        } else {
            // 🔥 LOGIC SỬA ĐỔI Ở ĐÂY
            if (isGenAI) {
                // Nếu là GenAI -> CHỈ hiện thời gian đếm, BỎ %
                newText = `Đang tạo... (${timeString})`; 
            } else {
                // Nếu thường -> Hiện % + Thời gian
                // Nếu progress = 0 thì hiện "Đang xử lý" cho đẹp
                if (currentProgress == 0) {
                     newText = `Đang xử lý (${timeString})`;
                } else {
                     newText = `Xử lý ${currentProgress}% (${timeString})`;
                }
            }
        }

        $elapsedSpan.html(newText);
    };

    // 1. Gọi API ngay lần đầu
    checkApiNow();

    // 2. Interval chạy mỗi 1 giây (Đồng hồ)
    let stopwatchInterval = setInterval(() => {
        if (!$('#detailedHistoryModal').is(':visible') || $(`#row-${taskId}`).length === 0) {
            clearInterval(stopwatchInterval);
            delete detailedIntervals[taskId];
            return;
        }

        // Cập nhật đồng hồ (dựa trên data đã lưu, không ghi đè bậy)
        updateStatusText();

        // Tính toán để gọi API (mỗi 3 giây)
        let elapsedSeconds = Math.floor((Date.now() - validStartTime) / 1000);
        let shouldPoll = (elapsedSeconds > 0 && elapsedSeconds % 3 === 0 && elapsedSeconds !== lastPollTime);
        
        if (shouldPoll && attempts < maxAttempts) {
            attempts++;
            lastPollTime = elapsedSeconds;
            checkApiNow();
        }

        if (attempts >= maxAttempts) {
            clearInterval(stopwatchInterval);
            delete detailedIntervals[taskId];
            $elapsedSpan.html(`⏰ Timeout`);
            $row.find('.dh-badge-processing').removeClass('dh-badge-processing').addClass('dh-badge-error').text('Timeout');
        }
    }, 1000);

    detailedIntervals[taskId] = stopwatchInterval;
}
// ========================================
// 🔄 REFRESH DETAILED HISTORY
// ========================================
function refreshDetailedHistory() {
    console.log("🔄 Refreshing detailed history...");

    // 1. Tạo hiệu ứng xoay icon
    const $btn = $('.dh-header-btn[onclick="refreshDetailedHistory()"]');
    const $icon = $('#dhRefreshIcon');
    
    $btn.prop('disabled', true); // Khóa nút để tránh spam
    $icon.addClass('spin-anim'); // Thêm class xoay (đã định nghĩa ở CSS trên)

    // 2. Reset các biến đếm nếu cần (tùy logic pagination của bạn)
    // Ví dụ: detailedHistoryData = []; 

    // 3. Gọi hàm tải lại dữ liệu (Trang 1)
    loadDetailedHistoryData(1);

    // 4. Tắt hiệu ứng sau 1 giây (để user kịp nhìn thấy nó xoay)
    setTimeout(() => {
        $icon.removeClass('spin-anim');
        $btn.prop('disabled', false);
        
        // (Tùy chọn) Hiển thị toast thông báo
        if(typeof showToast === 'function') {
            // showToast('Đã làm mới danh sách');
        }
    }, 800);
}
// 4. Logic Checkbox "Chọn tất cả" & Cập nhật nút
function toggleAllDetailed(source) {
    $('.dh-item-checkbox').prop('checked', source.checked);
    updateBulkActions();
}
function updateBulkActions() {
    let checkedBoxes = $('.dh-item-checkbox:checked');
    let count = checkedBoxes.length;
    let totalAudio = 0, totalSrt = 0, totalJson = 0;

    // Đếm số lượng file
    checkedBoxes.each(function() {
        if($(this).data('audio')) totalAudio++;
        if($(this).data('srt')) totalSrt++;
        if($(this).data('json')) totalJson++;
    });

    // --- SỬA LẠI ĐOẠN NÀY ---
    // Không ẩn header nữa, chỉ enable/disable nút thôi
    
    // Cập nhật số trên nút Xóa & Trạng thái Enable/Disable
    $('#btnBulkDelete span').text(count);
    $('#btnBulkDelete').prop('disabled', count === 0);
    
    // Cập nhật nút Audio
    $('#btnBulkDownloadAudio span').text(totalAudio);
    $('#btnBulkDownloadAudio').prop('disabled', totalAudio === 0);

    // Cập nhật nút SRT
    $('#btnBulkDownloadSrt span').text(totalSrt);
    $('#btnBulkDownloadSrt').prop('disabled', totalSrt === 0);

    // Cập nhật nút JSON
    $('#btnBulkDownloadJson span').text(totalJson);
    $('#btnBulkDownloadJson').prop('disabled', totalJson === 0);
    
    // Xử lý checkbox chọn tất cả (nếu không còn item nào được chọn thì bỏ tick Select All)
    if (count === 0) {
        $('#dhSelectAll').prop('checked', false);
    }
}
// ========================================
// ⚡ BULK DELETE (XÓA NHIỀU) - TỐI ƯU
// ========================================
async function bulkDelete() {
    console.log("🖱️ Bulk delete clicked"); // Debug log

    // 1. Kiểm tra checkbox
    let checkedBoxes = $('.dh-item-checkbox:checked');
    if (checkedBoxes.length === 0) {
        if(typeof showToast === 'function') showToast('⚠️ Chưa chọn task nào!');
        else alert('Chưa chọn task nào!');
        return;
    }

    // 2. Gọi Popup
    showBulkDeleteConfirm(checkedBoxes.length, async function() {
        // --- LOGIC XÓA (Chạy khi user bấm "Xóa ngay") ---
        
        // Disable nút gốc
        $('#btnBulkDelete').prop('disabled', true);

        let elements = checkedBoxes.toArray();
        for (const checkbox of elements) {
            let taskId = $(checkbox).val();
            deleteHistoryTask(taskId); // Gọi hàm xóa đơn lẻ
            await new Promise(r => setTimeout(r, 50)); // Delay tạo hiệu ứng
        }

        // Đóng popup và dọn dẹp sau 500ms
        setTimeout(() => {
            $('#bulkDeletePopup').fadeOut(200, function() { $(this).remove(); });
            $('#dhSelectAll').prop('checked', false);
            updateBulkActions();
            if(typeof showToast === 'function') showToast(`✅ Đã xóa xong!`);
            
            // Mở lại nút gốc
            $('#btnBulkDelete').prop('disabled', false);
        }, 500);
    });
}

// 6. Hàm thực thi Bulk Download
// Thêm hàm này vào file JS của bạn
async function bulkDownload(type) {
    let checkedBoxes = $('.dh-item-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert("Chưa chọn file nào!");
        return;
    }

    // 1. UI Loading
    let btnId = '#btnBulkDownload' + type.charAt(0).toUpperCase() + type.slice(1);
    let $btn = $(btnId);
    let oldHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang nén...');

    try {
        let zip = new JSZip();
        let promises = [];
        let count = 0;

        checkedBoxes.each(function() {
            let $box = $(this);
            let taskId = $box.val();
            
            // 🔥 [MỚI] KIỂM TRA TRẠNG THÁI TASK
            // Tìm dòng (row) chứa checkbox này để xem trạng thái
            let $row = $box.closest('.dh-row');
            let isDone = $row.find('.dh-badge-done').length > 0; // Chỉ lấy dòng có badge "Xong"
            
            // Nếu chưa xong -> Bỏ qua ngay lập tức, không gọi link
            if (!isDone) {
                console.log(`⏩ Bỏ qua task chưa xong: ${taskId}`);
                return; // Continue vòng lặp
            }

            let url = $box.data(type); 
            let ext = (type === 'audio') ? 'mp3' : type;
            let fileName = `file_${taskId}.${ext}`;

            if (url && url.length > 10) {
                let p = fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        return response.blob();
                    })
                    .then(blob => {
                        zip.file(fileName, blob);
                        count++;
                    })
                    .catch(err => {
                        console.warn(`⚠️ Link hỏng (${taskId}):`, url);
                    });
                
                promises.push(p);
            }
        });

        // 2. Đợi tất cả
        await Promise.all(promises);

        if (count === 0) {
            alert("Không có file nào sẵn sàng để tải (Các task có thể đang chạy hoặc lỗi).");
            $btn.prop('disabled', false).html(oldHtml);
            return;
        }

        // 3. Nén và Tải
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Đang lưu...');
        let content = await zip.generateAsync({type: "blob"});
        
        let a = document.createElement("a");
        a.href = URL.createObjectURL(content);
        a.download = `download_${type}_${Date.now()}.zip`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        if(typeof showToast === 'function') showToast(`✅ Đã tải ${count} file thành công!`);

    } catch (e) {
        console.error("Lỗi zip:", e);
        alert("Có lỗi xảy ra.");
    } finally {
        $btn.prop('disabled', false).html(oldHtml);
    }
}
function openDeleteModal(taskId, textPreview, deleteType, cost = 0) {
    pendingDeleteId = taskId;
    pendingDeleteType = deleteType;
    pendingDeleteCost = cost;

    // Cập nhật nội dung Modal
    $('#dmTextPreview').text(textPreview || 'Không có nội dung preview');
    
    // Nếu là xóa lịch sử (đã xong/lỗi) thì ẩn dòng thông báo hoàn tiền màu tím
    if (deleteType === 'history') {
        $('#dmNote').hide();
    } else {
        $('#dmNote').show();
    }

    // Hiện Modal
    $('#deleteModal').css('display', 'flex').hide().fadeIn(200).addClass('show');
}

function closeDeleteModal() {
    $('#deleteModal').removeClass('show').fadeOut(200);
    pendingDeleteId = null;
}

// Bắt sự kiện bấm nút Xóa trong Modal
// Xử lý sự kiện click nút Xóa trong Modal
$(document).on('click', '#btnConfirmDelete', function() {
    // 1. Kiểm tra ID toàn cục xem có tồn tại không
    if (!pendingDeleteId) {
        alert("Lỗi: Không tìm thấy ID tác vụ để xóa!");
        return;
    }

    // 🔥 [QUAN TRỌNG] Lưu ID vào biến tạm trước khi đóng Modal
    // (Để tránh trường hợp hàm closeDeleteModal() xóa mất ID)
    let idToDelete = pendingDeleteId;
    let typeToDelete = pendingDeleteType;
    let costToDelete = pendingDeleteCost;

    console.log("🟠 Confirmed Delete for ID:", idToDelete); 

    // 2. Đóng Modal
    closeDeleteModal(); 

    // 3. Thực hiện xóa (Dùng biến tạm idToDelete)
    if (typeToDelete === 'refund') {
        deleteTask(idToDelete, costToDelete);
    } else {
        deleteHistoryTask(idToDelete);
    }
});
$(document).ready(function() {
    // Khai báo biến timer ở ngoài để kiểm soát
    let typingTimer;                
    const doneTypingInterval = 1000; 

    // Tự động tính tiền ngay khi gõ hoặc PASTE văn bản
    $('#txtInput').on('input propertychange paste', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            updateEstimatedCost();
        }, doneTypingInterval);
    });
    // ================================================================
    // 1. SỰ KIỆN GIAO DIỆN CƠ BẢN
    // ================================================================

    // Tự động tính tiền ngay khi gõ hoặc PASTE văn bản
    $('#txtInput').on('input propertychange paste', function() {
        // Dùng setTimeout nhỏ để chờ text paste vào xong hẳn mới tính
        setTimeout(function() {
            updateEstimatedCost();
        }, 50);
    });

    // Tính tiền lại khi đổi Tab giọng (Default <-> Cloned)
    // Lưu lại tab vào localStorage để khi F5 không bị mất
    $('.voice-tab-btn').on('click', function() {
        // Cập nhật biến trạng thái
        window.currentVoiceTab = $(this).data('type'); 
        
        // 🔥 LƯU VÀO BỘ NHỚ
        localStorage.setItem('tts_last_tab', window.currentVoiceTab);
        
        console.log("Đã đổi tab sang: " + window.currentVoiceTab);
        updateEstimatedCost();
    });

    // 5. Credits tooltip (Hover)
    $('#creditsTrigger').hover(
        function() { $('#creditsTooltip').stop(true, true).fadeIn(200); }, 
        function() { $('#creditsTooltip').stop(true, true).fadeOut(200); }
    );

    // Close dropdowns khi click ra ngoài
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.provider-dropdown-wrapper').length) {
            $('#providerDropdown').removeClass('show');
            $('#providerDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
        if (!$(e.target).closest('.lang-selector-wrapper').length && 
            !$(e.target).closest('[onclick*="toggleLangDropdown"]').length) {
            $('#langDropdown').removeClass('show');
            $('#langDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
        if (!$(e.target).closest('#minimaxModelBtn').length && 
            !$(e.target).closest('#minimaxModelDropdown').length) {
            $('#minimaxModelDropdown').removeClass('show');
            $('#minimaxModelIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
    });

    // ================================================================
    // 2. KHÔI PHỤC TRẠNG THÁI (RESTORE STATE) - QUAN TRỌNG
    // ================================================================

    // A. Khôi phục nội dung văn bản
    let savedText = localStorage.getItem('tts_input_draft');
    if (savedText) {
        $('#txtInput').val(savedText);
    }

    // B. Khôi phục tên file (nếu có)
    let savedFileName = localStorage.getItem('tts_filename');
    if (savedFileName) {
        $('#fileNameDisplay').text(`📂 ${savedFileName}`).show();
    }

    // C. Khôi phục cờ SRT (để tính tiền đúng)
    let savedIsSrt = localStorage.getItem('tts_is_srt');
    if (savedIsSrt === 'true') {
        window.isSrtFile = true;
        $('#srtFeeInfo').show();
    } else {
        window.isSrtFile = false;
        $('#srtFeeInfo').hide();
    }

    // 🔥 D. KHÔI PHỤC TAB GIỌNG NÓI (FIX LỖI GIÁ TIỀN)
    let savedTab = localStorage.getItem('tts_last_tab');
    if (savedTab) {
        window.currentVoiceTab = savedTab;
        console.log("♻️ Đã khôi phục Tab cũ:", savedTab);
        
        // (Tùy chọn) Cập nhật UI Active cho Tab nếu cần
        $('.voice-tab-btn').removeClass('active');
        $(`.voice-tab-btn[data-type="${savedTab}"]`).addClass('active');
    }

    // E. Tính toán lại chi phí ngay lập tức sau khi restore xong
    setTimeout(() => {
        togglePlaceholder(); 
        // Gọi updateEstimatedCost() thay vì calculateCost() để đảm bảo logic hiển thị UI chuẩn
        updateEstimatedCost(); 
    }, 100);

// ================================================================
    // 3. LOGIC BẢO TRÌ & BACKUP (MAINTENANCE)
    // ================================================================

    // Lấy biến từ PHP truyền xuống
    let isElevenLabsDown = (typeof elevenlabsDown !== 'undefined' && elevenlabsDown);
    let isMinimaxDown = (typeof minimaxDown !== 'undefined' && minimaxDown);
    let isBackupEligible = (typeof backupEligible !== 'undefined' && backupEligible);
    let isGenaiBackupDown = (typeof genaiBackupDown !== 'undefined' && genaiBackupDown);

    // 🔥 [FIX MỚI] CASE 1: CẢ 2 ĐỀU DOWN -> CHUYỂN HƯỚNG NGAY
    if (isElevenLabsDown && isMinimaxDown && isGenaiBackupDown) {
        console.error('❌ ALL SYSTEMS DOWN. Redirecting...');
        
        $('body').empty().css('background', '#000').html(`
            <div style="height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff;">
                <h1>🚧 Hệ thống đang bảo trì</h1>
                <p>Đang chuyển hướng...</p>
            </div>
        `);
        
        setTimeout(() => {
            window.location.href = '/pages/maintenance.php?path=' + encodeURIComponent('/ai/tts');
        }, 500);
        return; // ⛔ Dừng code tại đây, KHÔNG chạy loadResources()
    }
    
    // ✅ CASE 2: CHỈ ELEVENLABS DOWN
    if (isElevenLabsDown && !isMinimaxDown) {
        console.log('🔍 [DEBUG] ElevenLabs bảo trì.'); 
        
        // Kiểm tra Backup có dùng được không (Eligible + Server Backup còn sống)
        if (isBackupEligible && !isGenaiBackupDown) {
            console.log('✅ User được dùng Backup. Giữ nguyên ElevenLabs.');
            setTimeout(() => {
                $('.provider-option[data-provider="elevenlabs"] .provider-desc')
                    .html('Đang dùng <strong style="color: #667eea;">Backup (Miễn phí)</strong>');
                showToast('ℹ️ ElevenLabs đang dùng Backup (miễn phí)', 'info');
            }, 300);
        } else {
            console.log('⚠️ Chuyển sang Minimax...');
            currentProvider = 'minimax'; 
            setTimeout(() => {
                selectProvider('minimax');
                disableProviderOption('elevenlabs'); 
                
                if (isGenaiBackupDown) {
                     showToast('⚠️ ElevenLabs và hệ thống Backup đều đang bảo trì.');
                } else {
                     showToast('⚠️ ElevenLabs đang bảo trì. Đã chuyển sang Minimax.');
                }
            }, 500);
        }
    }
    
    // ✅ CASE 3: CHỈ MINIMAX DOWN
    else if (isMinimaxDown && !isElevenLabsDown) {
        console.log('⚠️ Minimax down, staying on ElevenLabs');
        currentProvider = 'elevenlabs'; 
        setTimeout(() => {
            selectProvider('elevenlabs');
            disableProviderOption('minimax');
            showToast('⚠️ Minimax đang bảo trì.');
        }, 500);
    }

    // ================================================================
    // 4. KHỞI TẠO HỆ THỐNG (CHỈ CHẠY NẾU KHÔNG BỊ RETURN)
    // ================================================================
    
    $('#pageLoader').css('display', 'flex');
    
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        $('#apiKeyModal').css('display', 'flex');
        $('#btnProcess').prop('disabled', true);
    } else {
        $('#btnProcess').prop('disabled', false);
    }
    
    updateEmptyStateTips('elevenlabs');
    $('#elevenlabs-settings').removeClass('hidden');
    $('#minimax-settings').addClass('hidden');
    // Cập nhật giao diện nếu đang chạy Backup
    if (isElevenLabsDown && isBackupEligible && !isGenaiBackupDown) {
        $('.provider-option[data-provider="elevenlabs"] .provider-desc').css('color', '#888').text('Đang sử dụng Backup (Miễn phí).');
        
        // Cập nhật nút chọn hiện tại
        if (currentProvider === 'elevenlabs') {
            $('#currentProviderName').html('ElevenLabs <span style="color:#4ade80; font-size:11px;">(Backup)</span>');
        }
    
    }
    setInterval(function() {
        // Lấy giờ hiện tại
        let time = new Date().toLocaleTimeString();
        console.log(`%c[${time}] ⏱️ Timer 10s kích hoạt...`, "color: #fbbf24; font-weight: bold;");

        // Kiểm tra điều kiện
        let isTabOpen = $('#viewHistory').hasClass('show');
        let isNotLoading = !isLoadingHistory;

        if (isTabOpen && isNotLoading) {
            console.log(`   ✅ Điều kiện thỏa mãn. Đang gọi hàm silentRefreshHistory()...`);
            silentRefreshHistory();
        } else {
            console.log(`   ⏸️ Bỏ qua. Lý do: ${!isTabOpen ? 'Tab Lịch sử đang đóng' : 'Đang tải dữ liệu khác'}`);
        }
    }, 30000); // 10 giây
    // Load dữ liệu
    loadResources();
    loadHistory();
    setupInfiniteScroll();
    setupAudioEvents();
    setupEventListeners();
});
// ========================================
// 🤫 LÀM MỚI LỊCH SỬ ÂM THẦM (ĐÃ BỎ LOADING)
// ========================================
function silentRefreshHistory() {
    // 1. Reset các biến đếm phân trang về ban đầu
    currentOffset = 0;
    hasMoreHistory = true;
    isLoadingHistory = false; // Mở khóa để đảm bảo loadHistory chạy được
    
    // 2. Xóa sạch danh sách hiện tại & Ẩn thông báo "Hết dữ liệu"
    // (Bắt buộc phải xóa để không bị trùng lặp task cũ và mới)
    $('#historyListContainer').empty();
    $('#noMoreData').hide();
    
    // 3. [ĐÃ BỎ THEO YÊU CẦU] - Không hiện loading spinner nữa

    // 4. Gọi lại hàm loadHistory để tải trang 1
    loadHistory();
}
function updateEmptyStateTips(provider) {
    if (provider === 'minimax') {
        $('#emptyTips').html(`
            <div class="es-tip">
                <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i>
                <span>💡 Chèn <b>&lt;#0.5#&gt;</b> để ngừng 0.5 giây.</span>
            </div>
            <div class="es-tip">
                <i class="bi bi-folder-fill" style="color: #3b82f6;"></i>
                <span>📁 Kéo thả tệp <b>.txt, .srt</b> vào đây.</span>
            </div>
        `);
    } else {
        $('#emptyTips').html(`
            <div class="es-tip">
                <i class="bi bi-flag-fill" style="color: var(--error);"></i>
                <span>Tiếng Việt nên sử dụng <b>Minimax</b></span>
            </div>
            <div class="es-tip">
                <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i>
                <span>Chèn <code class="es-code">&lt;break time="0.5s" /&gt;</code> để nghỉ 0.5 giây</span>
            </div>
            <div class="es-tip">
                <i class="bi bi-folder-fill" style="color: #3b82f6;"></i>
                <span>Kéo thả tệp <b>.txt, .srt</b> vào đây</span>
            </div>
        `);
    }
}

// ========== SETUP EVENT LISTENERS ==========
function setupEventListeners() {
    // 1. Text input events (Đã gộp: Lưu bộ nhớ + Hiện/Ẩn Placeholder + Tính tiền)
    $('#txtInput').on('input', function() {
        let content = $(this).val(); 
        
        // Luôn cập nhật nội dung text vào bộ nhớ
        localStorage.setItem('tts_input_draft', content);
        
        // 🔥 [MỚI] NẾU XÓA SẠCH CHỮ -> XÓA LUÔN FILE
        if (content.trim() === '') {
            localStorage.removeItem('tts_filename');
            localStorage.removeItem('tts_is_srt');
            window.isSrtFile = false;
            $('#fileNameDisplay').hide().text('');
            $('#srtFeeInfo').hide();
        }
        
        togglePlaceholder();
        updateEstimatedCost(); // ✅ SỬA: Đổi từ calculateCost() sang updateEstimatedCost()
    });
    
    // 🔥 [THÊM MỚI] 2. Checkbox phụ đề events
    $('#subtitleCheck, #minimaxSubtitleCheck').on('change', function() {
        console.log('✅ Subtitle checkbox changed:', $(this).prop('checked'));
        updateEstimatedCost(); // ← Tính lại giá ngay
    });
    
    // 3. Các thanh trượt (Sliders) - GIỮ NGUYÊN
    $('#vol').on('input', function() { 
        $('#volVal').text(parseFloat(this.value).toFixed(2)); 
        updateSliderFill(this);
    });

    $('#speed').on('input', function() { 
        $('#speedVal').text(parseFloat(this.value).toFixed(2));
        updateSliderFill(this);
    });
    
    $('#pitch').on('input', function() { 
        $('#pitchVal').text(this.value);
        updateSliderFill(this);
    });
    
    // 🔥 Logic hiển thị chữ cho Stability v3
    $('#stability').on('input', function() { 
        let currentModelName = $('#selectedModelName').text();
        let val = parseInt(this.value);

        // Kiểm tra step=50 để biết là model V3
        let isV3 = $(this).attr('step') === '50';

        if (isV3) {
            let text = "Natural"; 
            let color = "#ffffff"; 

            if (val === 0) {
                text = "Creative";
                color = "#ffffff"; 
            } else if (val === 100) {
                text = "Robust";
                color = "#ffffff"; 
            }
            
            // Hiển thị chữ
            $('#stabilityVal').html(`<span style="color: ${color}; font-weight: bold; text-transform: uppercase;">${text}</span>`);
        } else {
            // Model thường hiển thị %
            $('#stabilityVal').text(this.value + '%');
        }
        updateSliderFill(this);
    });
    
    $('#similarity').on('input', function() { 
        $('#similarityVal').text(this.value + '%');
        updateSliderFill(this);
    });
    
    $('#style').on('input', function() { 
        $('#styleVal').text(this.value + '%');
        updateSliderFill(this);
    });
    
    $('#elevenSpeed').on('input', function() {
        let val = parseFloat(this.value);
        $('#elevenSpeedVal').text(val.toFixed(2));
        updateSliderFill(this);
        
        if (val > 1.5) {
            $(this).closest('.slider-container').addClass('warning');
        } else {
            $(this).closest('.slider-container').removeClass('warning');
        }
    });
    
    // 3. Khởi tạo màu thanh trượt khi load
    setTimeout(() => {
        $('input[type=range]').each(function() {
            updateSliderFill(this);
        });
    }, 100);
    
    // 4. Khởi tạo Drag & Drop
    setupDragDrop();
}

// Hàm update fill
function updateSliderFill(slider) {
    let value = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.setProperty('--value', value + '%');
}

// ========== PROVIDER DROPDOWN ==========
function toggleProviderDropdown() {
    $('#providerDropdown').toggleClass('show');
    $('#providerDropdownIcon').toggleClass('bi-chevron-up bi-chevron-down');
}

// ========== SELECT PROVIDER (GỘP LOGIC TỐI ƯU) ==========
function selectProvider(provider) {
    // 🔥 LOG DEBUG (Có thể bật/tắt dễ dàng)
    const DEBUG = false; // Đổi true để bật log chi tiết
    
    if (DEBUG) {
        console.log(`%c🖱️ CLICK: Yêu cầu chuyển sang ${provider.toUpperCase()}`, "color: #00ffff; font-weight: bold; font-size: 14px;");
        console.log("📊 Trạng thái biến toàn cục:", {
            "currentProvider": typeof currentProvider !== 'undefined' ? currentProvider : 'undefined',
            "elevenlabsDown": typeof elevenlabsDown !== 'undefined' ? elevenlabsDown : 'undefined',
            "backupEligible": typeof backupEligible !== 'undefined' ? backupEligible : 'undefined',
            "minimaxDown": typeof minimaxDown !== 'undefined' ? minimaxDown : 'undefined'
        });
    } else {
        console.log(`🖱️ CLICK: Yêu cầu chuyển sang ${provider.toUpperCase()}`);
    }

    // ============================================================
    // 🔍 PHẦN KIỂM TRA BẢO TRÌ
    // ============================================================
    
    // 1. Kiểm tra ElevenLabs
    if (provider === 'elevenlabs') {
        if (typeof elevenlabsDown !== 'undefined' && elevenlabsDown) {
            if (DEBUG) console.log('🔍 [LOGIC] ElevenLabs đang bảo trì. Kiểm tra Backup...');
            
            // Kiểm tra Backup có khả dụng không
            let isBackupAvailable = (typeof backupEligible !== 'undefined' && backupEligible && 
                                     typeof genaiBackupDown !== 'undefined' && !genaiBackupDown);

            if (!isBackupAvailable) {
                if (DEBUG) console.warn('⛔ [BLOCKED] Không được phép dùng Backup.');
                showToast('❌ ElevenLabs đang bảo trì và không có Backup!');
                return; 
            }
            if (DEBUG) console.log('✅ [ALLOWED] Được phép dùng Backup.');
        }
    }

    // 2. Kiểm tra Minimax
    if (provider === 'minimax') {
        if (typeof minimaxDown !== 'undefined' && minimaxDown) {
            if (DEBUG) console.warn('⛔ [BLOCKED] Minimax đang bảo trì.');
            showToast('❌ Minimax đang bảo trì!');
            return;
        }
    }

    // ============================================================
    // 🚀 THỰC HIỆN CHUYỂN ĐỔI GIAO DIỆN
    // ============================================================
    
    currentProvider = provider;
    if (DEBUG) console.log(`✅ Đã gán currentProvider = ${currentProvider}`);

    // Update UI Active State
    $('.provider-option').removeClass('active');
    $(`.provider-option[data-provider="${provider}"]`).addClass('active');

    // Xử lý ẩn hiện Settings
    if (provider === 'minimax') {
        if (DEBUG) console.log('🛠️ [DEBUG] Render giao diện: MINIMAX');
        
        $('#currentProviderName').text('Minimax');
        $('#currentProviderLogo').attr('src', 'https://ai33.pro/minimax.png?v=3');
        
        $('#elevenlabs-settings').addClass('hidden');
        $('#minimax-settings').removeClass('hidden');
        
        updateEmptyStateTips('minimax');

        // Logic Tabs trong Modal
        if ($('#voiceModal').is(':visible')) {
            $('.vm-tab[data-tab="library"]').hide();
            $('.vm-tab[data-tab="cloned"]').show();
            if (currentVoiceTab === 'library') switchVoiceTab('default');
        }
    } else {
        if (DEBUG) console.log('🛠️ [DEBUG] Render giao diện: ELEVENLABS');

        $('#currentProviderName').text('Elevenlabs');
        $('#currentProviderLogo').attr('src', 'https://ai33.pro/11max.png?v=3');
        
        $('#elevenlabs-settings').removeClass('hidden');
        $('#minimax-settings').addClass('hidden');
        
        updateEmptyStateTips('elevenlabs');

        // Logic Tabs trong Modal
        if ($('#voiceModal').is(':visible')) {
            $('.vm-tab[data-tab="library"]').show();
            $('.vm-tab[data-tab="cloned"]').hide();
            if (currentVoiceTab === 'cloned') switchVoiceTab('default');
        }
    }

    // Reset các lựa chọn cũ
    $('#selectedVoiceName').text("Chọn giọng nói...");
    $('#voiceIdVal').val("");

    // Đóng dropdown menu
    $('#providerDropdown').removeClass('show');
    $('#providerDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');

    hideModelDetails();
    renderMinimaxModels();

    // 🔥 TÍNH TOÁN LẠI GIÁ TIỀN
    if (DEBUG) console.log('💰 [DEBUG] Gọi updateEstimatedCost()...');
    updateEstimatedCost();

    // Force update lần 2 để fix lỗi render CSS (nếu có)
    setTimeout(() => {
        updateEstimatedCost();
    }, 100);
}
// ========== LANGUAGE DROPDOWN ==========
// ========== LANGUAGE DROPDOWN ==========
function toggleLangDropdown() {
    $('#langDropdown').toggleClass('show');
    $('#langDropdownIcon').toggleClass('bi-chevron-up bi-chevron-down');
}

function selectLanguage(langCode, displayName) {
    selectedLanguage = langCode;
    $('#selectedLang').text(displayName);
    
    $('.lang-option').removeClass('active');
    $(`.lang-option[data-lang="${langCode}"]`).addClass('active');
    
    $('#langDropdown').removeClass('show');
    $('#langDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

// Đóng dropdown khi click ngoài
$(document).on('click', function(e) {
    if (!$(e.target).closest('.provider-dropdown-wrapper').length) {
        $('#providerDropdown').removeClass('show');
        $('#providerDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    }
    if (!$(e.target).closest('.lang-selector-wrapper').length) {
        $('#langDropdown').removeClass('show');
        $('#langDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    }
});

function hidePageLoader() {
    $('#pageLoader').fadeOut(300, function() {
        if (hasApiKey) {
            $('#btnProcess').prop('disabled', false);
        }
    });
    // ✅ BỎ dòng sessionStorage.setItem()
}

// ========== LOAD RESOURCES ==========
function loadResources() {
    console.log('🚀 loadResources() called');
    
    $.get('../../ajaxs/get_resources.php', function(res) {
        console.log('✅ API Response received:', res);
        
        if (res.status === 'success') {
            try {
                // 1. Kiểm tra an toàn trước khi map dữ liệu Voices
                // Nếu res.data.elevenlabs.voices bị null -> gán mảng rỗng []
                let elVoices = (res.data && res.data.elevenlabs && res.data.elevenlabs.voices) ? res.data.elevenlabs.voices : [];
                let mmVoices = (res.data && res.data.minimax && res.data.minimax.voices) ? res.data.minimax.voices : [];

                loadedVoices.elevenlabs = enhanceVoiceData(elVoices, 'elevenlabs');
                let systemMinimaxVoices = enhanceVoiceData(mmVoices, 'minimax');
                
                // 2. Kiểm tra an toàn Models
                let elModels = (res.data && res.data.elevenlabs && res.data.elevenlabs.models) ? res.data.elevenlabs.models : [];
                let mmModels = (res.data && res.data.minimax && res.data.minimax.models) ? res.data.minimax.models : [];

                loadedModels = {
                    elevenlabs: elModels,
                    minimax: mmModels
                };

                // 3. Xử lý logic GenAI Backup (Nếu không có models nào)
                if (loadedModels.elevenlabs.length === 0) {
                    // Tạo một model giả để giao diện không bị lỗi trắng
                    loadedModels.elevenlabs.push({
                        id: 'genai_backup',
                        name: 'GenAI Backup Mode',
                        description: 'Chế độ dự phòng',
                        cost_factor: 1
                    });
                }

                // 4. Load Models & Voices
                loadClonedVoicesAndMerge(systemMinimaxVoices);
                renderMinimaxModels();
                renderElevenLabsModels();
                
            } catch (e) {
                console.error("❌ Error inside loadResources processing:", e);
            }
        }
    }, 'json')
    .fail(function(xhr, status, error) {
        console.error("❌ API loadResources Failed:", error);
        // Fallback: Tạo model giả nếu API chết hẳn
        loadedModels.elevenlabs = [{ id: 'backup', name: 'Backup Mode' }];
        renderElevenLabsModels();
    })
    .always(function() {
        // 🔥 QUAN TRỌNG NHẤT: Luôn luôn tắt loading dù thành công hay thất bại
        console.log('🏁 loadResources finished. Hiding Loader.');
        hidePageLoader(); 
    });
}
function loadClonedVoicesAndMerge(systemVoices) {
    $.post('../../ajaxs/voice_cloning.php', { action: 'list_clones' }, function(res) {
        let clonedVoices = [];
        if (res.status === 'success' && res.voices && res.voices.length > 0) {
            clonedVoices = res.voices.map(v => ({
                id: v.voice_id, // Map đúng key từ API Minimax
                name: (v.voice_name || 'Unknown') + ' (Clone)',
                // Fallback avatar nếu API không trả về cover_url
                avatar: v.cover_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(v.voice_name)}&background=random&color=fff`,
                tags: ['Minimax', 'Clone', 'User'],
                gender: 'Unknown', 
                preview_url: v.sample_audio,
                
                // 🔥 QUAN TRỌNG: Phải có dòng này thì bộ lọc "Giọng nhân bản" mới nhận diện được
                source: 'cloned' 
            }));
        }
        
        // Gộp giọng clone + giọng hệ thống
        loadedVoices.minimax = [...clonedVoices, ...systemVoices];
        
        // Nếu đang ở tab clone thì render lại ngay
        if (currentVoiceTab === 'cloned') {
            renderClonedVoices();
        }
        
    }, 'json').fail(function() {
        console.error('Lỗi tải danh sách clone');
        loadedVoices.minimax = systemVoices;
    });
}

function enhanceVoiceData(voices, provider) {
    if (!voices) return [];
    return voices.map(v => {
        let tags = [];
        let gender = 'Male';
        let nameLower = (v.name || '').toLowerCase();

        if (nameLower.includes('girl') || nameLower.includes('woman') || 
            nameLower.includes('lady') || nameLower.includes('female') || 
            nameLower.includes('mrs') || (v.tags && v.tags.includes('Female'))) {
            gender = 'Female';
        }
        
        if (provider === 'minimax') {
            tags = v.tags || [];
            gender = v.gender || gender;
        } else {
            tags.push('English');
            if (v.tags && Array.isArray(v.tags)) {
                tags = tags.concat(v.tags.slice(0, 2));
            }
        }
        
        tags.push(gender);
        
        // 🔥 XỬ LÝ AVATAR ĐẦY ĐỦ CHO MINIMAX
        let avatar;
        
        if (provider === 'minimax') {
            // 1. Ưu tiên avatar từ API
            if (v.avatar) {
                avatar = v.avatar;
            }
            // 2. Fallback: cover_url (từ cloned voices)
            else if (v.cover_url) {
                avatar = v.cover_url;
            }
            // 3. Fallback: image_url (nếu có)
            else if (v.image_url) {
                avatar = v.image_url;
            }
            // 4. Fallback cuối: Generate avatar
            else {
                avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=random&size=128&color=fff&bold=true`;
            }
        } else {
            // ElevenLabs
            avatar = v.avatar || v.image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=random&size=128&color=fff&bold=true`;
        }

        return {
            id: v.id,
            name: v.name,
            avatar: avatar, // ✅ Avatar đã được xử lý đầy đủ
            tags: tags,
            gender: gender,
            preview_url: v.preview_url || v.sample_audio,
            source: v.source || 'default',
            description: v.description || ''
        };
    });
}


// ========== RENDER MINIMAX MODELS AS DROPDOWN ==========
function renderMinimaxModels() {
    if (currentProvider !== 'minimax') return;
    
    let models = loadedModels.minimax || [];
    
    // 🔥 FIX: Kiểm tra rỗng
    if (models.length === 0) {
        $('#minimaxModelDropdown').html('<div style="padding:10px; color:#888;">Không có model khả dụng</div>');
        $('#selectedMinimaxModel').text('Default');
        return;
    }

    let html = '';
    
    models.forEach((m, index) => {
        let badge = '';
        if (m.cost_factor < 1) {
            badge = `<span style="color: #fbbf24; font-size: 11px; margin-left: 6px;">${Math.round((1 - m.cost_factor) * 100)}% rẻ hơn</span>`;
        } else if (m.cost_factor > 1) {
            badge = `<span style="color: #4ade80; font-size: 11px; margin-left: 6px;">Chất lượng cao</span>`;
        }
        
        let isActive = (index === 0) ? 'active' : '';
        if (index === 0) {
            selectedMinimaxModel = m.id;
            $('#selectedMinimaxModel').text(m.name);
        }
        
        html += `
        <div class="lang-option ${isActive}" data-model="${m.id}" onclick="selectMinimaxModelFromDropdown('${m.id}', '${m.name}')">
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #fff; margin-bottom: 3px;">
                    ${m.name}${badge}
                </div>
                <div style="font-size: 11px; color: #777;">${m.description || 'Xử lý văn bản tự nhiên'}</div>
            </div>
            <i class="bi bi-check-lg check-icon"></i>
        </div>`;
    });
    
    $('#minimaxModelDropdown').html(html);
}

// Toggle Minimax Model Dropdown
function toggleMinimaxModelDropdown() {
    $('#minimaxModelDropdown').toggleClass('show');
    $('#minimaxModelIcon').toggleClass('bi-chevron-up bi-chevron-down');
}

// Select Minimax Model
function selectMinimaxModelFromDropdown(modelId, modelName) {
    selectedMinimaxModel = modelId;
    $('#selectedMinimaxModel').text(modelName);
    
    $('.lang-option[data-model]').removeClass('active');
    $(`.lang-option[data-model="${modelId}"]`).addClass('active');
    
    $('#minimaxModelDropdown').removeClass('show');
    $('#minimaxModelIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    
    // 🔥 SỬA: Gọi updateEstimatedCost() thay vì calculateCost()
    updateEstimatedCost(); 
}

// Thêm vào document click handler
$(document).on('click', function(e) {
    if (!$(e.target).closest('.provider-dropdown-wrapper').length) {
        $('#providerDropdown').removeClass('show');
        $('#providerDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    }
    if (!$(e.target).closest('.lang-selector-wrapper').length) {
        $('#langDropdown').removeClass('show');
        $('#langDropdownIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    }
    // THÊM CHO MINIMAX MODEL
    if (!$(e.target).closest('#minimaxModelBtn').length && !$(e.target).closest('#minimaxModelDropdown').length) {
        $('#minimaxModelDropdown').removeClass('show');
        $('#minimaxModelIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    }
});

function selectMinimaxModel(modelId, elem) {
    selectedMinimaxModel = modelId;
    $('.model-selector-item').removeClass('active');
    $(elem).addClass('active');
}

// ========== RENDER ELEVENLABS MODELS ==========
function renderElevenLabsModels() {
    console.log('🎯 renderElevenLabsModels()');
    
    let models = loadedModels.elevenlabs || [];
    
    // Nếu không có model nào -> Dừng
    if (models.length === 0) {
        $('#selectedModelName').text('GenAI Backup'); 
        $('#elevenlabs-settings').hide(); 
        return;
    }
    
    // 🔥 [ĐÃ SỬA] Ưu tiên tìm 'eleven_multilingual_v2', nếu không thấy thì lấy cái đầu tiên
    let targetId = 'eleven_multilingual_v2';
    let defaultModel = models.find(m => m.id === targetId) || models[0];

    if(defaultModel) {
        $('#selectedModelName').text(defaultModel.name);
        
        // Cập nhật UI (thanh trượt) theo model này
        updateElevenLabsUI(defaultModel.id);
    }
}

// ========== UPDATE ELEVENLABS UI BASED ON MODEL ==========
function updateElevenLabsUI(modelId) {
    let model = loadedModels.elevenlabs.find(m => m.id === modelId);
    
    if (!model || !model.full_data) {
        console.warn('⚠️ Model not found or missing full_data:', modelId);
        return;
    }
    
    // Lấy thông tin từ full_data
    let canUseStyle = model.full_data.can_use_style === true;
    let canUseSpeakerBoost = model.full_data.can_use_speaker_boost === true;
    
    // ========== LOGIC ẨN/HIỆN ==========
    
    // 1. Speed -> SỬA: Ẩn nếu là v3, hiện với các model khác
    if (modelId === 'eleven_v3') {
        $('#slider-speed').hide(); 
    } else {
        $('#slider-speed').show();
    }
    
    // 2. Stability -> LUÔN HIỆN
    $('#slider-stability').show();
    
    // 3. Similarity -> Ẩn với eleven_v3
    if (modelId === 'eleven_v3') {
        $('#slider-similarity').hide();
    } else {
        $('#slider-similarity').show();
    }
    
    // 4. Style -> Ẩn nếu là v3, ngược lại check canUseStyle
    if (modelId === 'eleven_v3') {
        $('#slider-style').hide(); 
    } else {
        if (canUseStyle) {
            $('#slider-style').show();
        } else {
            $('#slider-style').hide();
        }
    }
    
    // 5. Speaker Boost
    if (canUseSpeakerBoost) {
        $('#toggle-boost').show();
    } else {
        $('#toggle-boost').hide();
    }

    // ========== TÙY CHỈNH GIAO DIỆN V3 (Stability 3 NẤC) ==========
    if (modelId === 'eleven_v3') {
        // 1. Ép thanh trượt chỉ nhảy 3 nấc (0 - 50 - 100)
        $('#stability').attr('step', '50');
        $('#stability').attr('min', '0');
        $('#stability').attr('max', '100');

        // Thêm nhãn Creative / Natural / Robust ở dưới nếu chưa có
        if ($('#stability-labels').length === 0) {
            $('#stability').after(`
                <div id="stability-labels" style="display:flex; justify-content:space-between; font-size:12px; color:#888; margin-top:6px; font-weight:600;">
                    <span>Creative</span>
                    <span>Natural</span>
                    <span>Robust</span>
                </div>
            `);
        }
        $('#stability-labels').show();
        
        // Trigger input để cập nhật chữ và màu sắc ngay lập tức
        $('#stability').trigger('input');
        
    } else {
        // Revert về giao diện cũ (hiện %) cho các model khác
        // Trả lại step = 1 để kéo mượt %
        $('#stability').attr('step', '1');
        
        // Cập nhật lại text %
        let currentVal = $('#stability').val();
        $('#slider-stability .slider-header').html('<span>Độ ổn định: <span id="stabilityVal" style="color: #ffffff;">' + currentVal + '%</span></span>');
        
        // Ẩn nhãn 3 nấc
        $('#stability-labels').hide();
    }
}

// Tiếp tục ở phần 3...
// ========== MODEL DETAILS (ElevenLabs) ==========
const elevenLabsModelsData = {
    'eleven_v3': {
        name: 'Eleven v3 (Alpha)',
        badge: 'Mới nhất',
        badgeType: 'new',
        description: 'Mô hình biểu đạt tốt nhất. Hỗ trợ hơn 70 ngôn ngữ. Cần nhiều kỹ thuật prompt engineering hơn so với các mô hình trước đây. Hiện đang ở giai đoạn alpha và độ ổn định sẽ được cải thiện theo thời gian.',
        languages: 'English, Afrikaans, Arabic, Armenian, Assamese, Azerbaijani, Belarusian, Bengali, Bosnian, Bulgarian, Catalan, Cebuano, Chichewa, Croatian, Czech, Danish, Dutch, Estonian, Filipino, Finnish, French, Galician, Georgian, German, Greek, Gujarati, Hausa, Hebrew, Hindi, Hungarian, Icelandic, Indonesian, Irish, Italian, Japanese, Javanese, Kannada, Kazakh, Kirghiz, Korean, Latvian, Lingala, Lithuanian, Luxembourgish, Macedonian, Malay, Malayalam, Mandarin Chinese, Marathi, Nepali, Norwegian, Pashto, Persian, Polish, Portuguese, Punjabi, Romanian, Russian, Serbian, Sindhi, Slovak, Slovenian, Somali, Spanish, Swahili, Swedish, Tamil, Telugu, Thai, Turkish, Ukrainian, Urdu, Vietnamese, Welsh',
        cost: 1
    },
    'eleven_multilingual_v2': {
        name: 'Eleven Multilingual v2',
        badge: 'Chất lượng cao',
        badgeType: 'quality',
        description: 'Chế độ giống người thật và giàu cảm xúc nhất, hỗ trợ 29 ngôn ngữ. Phù hợp cho lồng tiếng, sách nói.',
        languages: 'English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Russian',
        cost: 1
    },
    'eleven_turbo_v2_5': {
        name: 'Eleven Turbo v2.5',
        badge: '50% rẻ hơn',
        badgeType: 'discount',
        description: 'Mô hình chất lượng cao, độ trễ thấp, hỗ trợ 32 ngôn ngữ. Phù hợp cho ứng dụng cần tốc độ.',
        languages: 'English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Russian, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Vietnamese, Norwegian, Hungarian',
        cost: 0.5
    },
    'eleven_turbo_v2': {
        name: 'Eleven Turbo v2',
        badge: '50% rẻ hơn',
        badgeType: 'discount',
        description: 'Mô hình tiếng Anh với độ trễ thấp. Phù hợp cho các ứng dụng developer.',
        languages: 'English',
        cost: 0.5
    },
    'eleven_flash_v2_5': {
        name: 'Eleven Flash v2.5',
        badge: '50% rẻ hơn',
        badgeType: 'discount',
        description: 'Mô hình độ trễ siêu thấp, hỗ trợ 32 ngôn ngữ. Lý tưởng cho chatbot và hội thoại.',
        languages: 'English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Russian, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Hungarian, Norwegian, Vietnamese',
        cost: 0.5
    },
    'eleven_flash_v2': {
        name: 'Eleven Flash v2',
        badge: '50% rẻ hơn',
        badgeType: 'discount',
        description: 'Mô hình tiếng Anh với độ trễ siêu thấp. Lý tưởng cho hội thoại.',
        languages: 'English',
        cost: 0.5
    },
    'eleven_monolingual_v1': {
        name: 'Eleven Monolingual v1',
        badge: 'Cơ bản',
        badgeType: 'basic',
        description: 'Mô hình tiếng Anh cơ bản với chi phí thấp.',
        languages: 'English',
        cost: 0.3
    }
};

function showModelDetails() {
    let provider = currentProvider;
    
    if (provider !== 'elevenlabs') return;
    
    let models = loadedModels.elevenlabs || [];
    
    // Lấy model hiện tại đang chọn
    let currentModelName = $('#selectedModelName').text();
    let currentModel = models.find(m => currentModelName.includes(m.name));
    let currentModelId = currentModel ? currentModel.id : models[0]?.id;
    
    let html = '';
    
    models.forEach(m => {
        let modelData = elevenLabsModelsData[m.id] || {
            name: m.name,
            badge: m.cost_factor < 1 ? 'Tiết kiệm' : 'Standard',
            badgeType: m.cost_factor < 1 ? 'discount' : 'quality',
            description: m.description || 'Mô hình text-to-speech chất lượng.',
            languages: 'English',
            cost: m.cost_factor || 1
        };
        
        let badgeClass = modelData.badgeType === 'discount' ? 'mo-badge discount' : 
                       modelData.badgeType === 'new' ? 'mo-badge new' : 'mo-badge';
        
        let selected = (currentModelId === m.id) ? 'selected' : '';
        
        let langCount = modelData.languages.split(',').length;
        
        html += `
        <div class="model-option ${selected}" onclick="selectModelFromSidebar('${m.id}', '${m.name}')">
            <div class="mo-header">
                <div>
                    <div class="mo-name">
                        ${modelData.name}
                        <span class="${badgeClass}" style="color: #000000;">${modelData.badge}</span>
                    </div>
                </div>
            </div>
            <div class="mo-desc">${modelData.description}</div>
            <div class="mo-languages" id="lang-${m.id}">
                <strong>Ngôn ngữ:</strong> ${modelData.languages}
            </div>
            ${langCount > 10 ? `
                <span class="mo-lang-toggle" onclick="event.stopPropagation(); toggleLanguages('${m.id}')">
                    <i class="bi bi-chevron-down"></i> Xem thêm
                </span>
            ` : ''}
        </div>`;
    });
    
    $('#mdContent').html(html);
    $('#modelSidebar').addClass('active');
}

// Select từ sidebar
function selectModelFromSidebar(modelId, modelName) {
    let model = loadedModels.elevenlabs.find(m => m.id === modelId);
    let badge = '';
    
    if (model) {
        if (model.cost_factor < 1) {
            badge = ` (${Math.round((1 - model.cost_factor) * 100)}% rẻ hơn)`;
        } else if (model.cost_factor > 1) {
            badge = ' (Đắt hơn)';
        }
    }
    
    $('#selectedModelName').text(modelName + badge);
    
    $('.model-option').removeClass('selected');
    $(event.currentTarget).addClass('selected');
    
    // 🔥 CẬP NHẬT UI
    updateElevenLabsUI(modelId);
    calculateCost(); 
    
    setTimeout(() => {
        hideModelDetails();
    }, 300);
}

function hideModelDetails() {
    $('#modelSidebar').removeClass('active');
}

function updateModelInfo() {
    if ($('#modelSidebar').hasClass('active')) {
        showModelDetails();
    }
}

function toggleLanguages(modelId) {
    let langDiv = $(`#lang-${modelId}`);
    let toggle = langDiv.next('.mo-lang-toggle');
    
    if (langDiv.hasClass('expanded')) {
        langDiv.removeClass('expanded');
        toggle.html('<i class="bi bi-chevron-down"></i> Xem thêm');
    } else {
        langDiv.addClass('expanded');
        toggle.html('<i class="bi bi-chevron-up"></i> Thu gọn');
    }
}

function selectModel(modelId) {
    $('#modelSelect').val(modelId);
    $('.model-option').removeClass('selected');
    $(event.currentTarget).addClass('selected');
    
    setTimeout(() => {
        hideModelDetails();
    }, 300);
}

// ========== VOICE MODAL - NEW FUNCTIONS ==========
let currentVoiceTab = 'library';
let sharedVoices = [];
let sharedVoicesLoaded = false;
let sharedVoicesLoading = false;

let clonedVoices = [];

function openVoiceModal() {
    $('#voiceModal').css('display', 'flex').hide().fadeIn(200);
    
    // 🔥 CẬP NHẬT TABS DỰA VÀO PROVIDER
    if (currentProvider === 'minimax') {
        // Minimax: Mặc định, Giọng nhân bản, Yêu thích
        $('.vm-tab[data-tab="library"]').hide();
        $('.vm-tab[data-tab="cloned"]').show();
        
        // Default tab cho Minimax
        switchVoiceTab('default');
    } else {
        // ElevenLabs: Mặc định, Thư viện, Yêu thích
        $('.vm-tab[data-tab="library"]').show();
        $('.vm-tab[data-tab="cloned"]').hide();
        
        // Default tab cho ElevenLabs
        switchVoiceTab('default');
    }
}
// ========================================
// ⚡ LOAD SHARED VOICES (OPTIMIZED)
// ========================================
function loadSharedVoices() {
    console.log('🔄 loadSharedVoices() called');
    
    if (sharedVoicesLoaded && sharedVoices.length > 0) {
        renderVoiceGridProgressive(sharedVoices);
        return;
    }
    
    if (sharedVoicesLoading) {
        showVoiceLoadingSpinner();
        return;
    }
    
    console.log('🌐 Fetching from NEW endpoint...');
    showVoiceLoadingSpinner();
    sharedVoicesLoading = true;
    
    $.ajax({
        url: '../../ajaxs/get_voices.php?v=' + Date.now(), // ✅ FILE MỚI
        method: 'GET',
        dataType: 'json',
        timeout: 60000,
        cache: false,
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        },
        
        success: function(res) {
            console.log('📦 NEW Response:', res);
            console.log('📊 Count:', res.count);
            console.log('🔥 Cached?', res.cache_info?.cached);
            
            if (res.status === 'success' && res.data && res.data.length > 0) {
                sharedVoices = enhanceSharedVoiceDataOptimized(res.data);
                sharedVoicesLoaded = true;
                
                console.log(`✅ SUCCESS: ${res.count} voices loaded!`);
                renderVoiceGridProgressive(sharedVoices);
            } else {
                console.error('❌ Invalid response');
                showVoiceErrorState('Lỗi dữ liệu');
            }
            sharedVoicesLoading = false;
        },
        
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error:', { status, error });
            console.error('Response:', xhr.responseText);
            showVoiceErrorState('Lỗi kết nối', error);
            sharedVoicesLoading = false;
        }
    });
}
function renderVoiceGridProgressive(voices) {
    console.log(`🎨 Progressive render: ${voices.length} voices total`);
    
    // Reset state
    sharedVoicesRendered = 0;
    isRenderingVoices = false;
    $('#voiceGrid').empty();
    
    // Remove old scroll handler
    if (voiceGridScrollHandler) {
        $('.vm-grid').off('scroll', voiceGridScrollHandler);
        voiceGridScrollHandler = null;
    }
    
    // ✅ RENDER BATCH ĐẦU TIÊN NGAY LẬP TỨC
    renderVoiceBatch(voices, 0);
    
    // ✅ SETUP SCROLL LISTENER
    voiceGridScrollHandler = function() {
        const $grid = $('.vm-grid');
        const scrollTop = $grid.scrollTop();
        const scrollHeight = $grid[0].scrollHeight;
        const clientHeight = $grid.height();
        
        // Khi scroll gần đến cuối (còn 500px)
        if (scrollTop + clientHeight >= scrollHeight - 500 && !isRenderingVoices) {
            renderNextBatch(voices);
        }
    };
    
    $('.vm-grid').on('scroll', voiceGridScrollHandler);
    
    console.log(`✅ First ${sharedVoicesRenderBatch} voices rendered, ${voices.length - sharedVoicesRenderBatch} remaining`);
}

// ========================================
// RENDER SINGLE BATCH
// ========================================
function renderVoiceBatch(voices, startIndex) {
    const endIndex = Math.min(startIndex + sharedVoicesRenderBatch, voices.length);
    
    console.log(`🎨 Rendering batch: ${startIndex} → ${endIndex}`);
    
    // Trong hàm renderVoiceBatch
for (let i = startIndex; i < endIndex; i++) {
    const voice = voices[i];
    let voiceCardHtml = '';

    // 🔥 LOGIC CHỌN CARD DỰA TRÊN PROVIDER
    if (currentProvider === 'minimax') {
        voiceCardHtml = createMinimaxVoiceCardHTML(voice); // Hàm mới
    } else {
        voiceCardHtml = createVoiceCardHTML(voice); // Hàm cũ cho ElevenLabs
    }

    $('#voiceGrid').append(voiceCardHtml);
}
    
    sharedVoicesRendered = endIndex;
}
// ========================================
// RENDER NEXT BATCH (LAZY LOAD)
// ========================================
function renderNextBatch(voices) {
    if (sharedVoicesRendered >= voices.length) {
        console.log('🏁 All voices rendered');
        return;
    }
    
    if (isRenderingVoices) return;
    
    isRenderingVoices = true;
    console.log(`⏳ Loading more... (${sharedVoicesRendered}/${voices.length})`);
    
    // ✅ setTimeout để không block UI
    setTimeout(() => {
        renderVoiceBatch(voices, sharedVoicesRendered);
        isRenderingVoices = false;
        
        const percent = Math.round((sharedVoicesRendered / voices.length) * 100);
        console.log(`✅ Progress: ${sharedVoicesRendered}/${voices.length} (${percent}%)`);
    }, 50);
}

// ========================================
// ⚡ CREATE VOICE CARD HTML
// ========================================
// Tìm function createVoiceCardHTML(voice) và thay thế nội dung bên trong:

// 1. Bảng đối chiếu Mã ngôn ngữ -> Cờ (Dựa trên select option bạn gửi)
const FLAG_MAP = {
    'en': '🇺🇸', 'vi': '🇻🇳', 'fr': '🇫🇷', 'de': '🇩🇪', 'es': '🇪🇸', 
    'it': '🇮🇹', 'pt': '🇵🇹', 'ru': '🇷🇺', 'ja': '🇯🇵', 'ko': '🇰🇷', 
    'zh': '🇨🇳', 'ar': '🇸🇦', 'hi': '🇮🇳', 'th': '🇹🇭', 'id': '🇮🇩', 
    'nl': '🇳🇱', 'pl': '🇵🇱', 'tr': '🇹🇷', 'uk': '🇺🇦', 'sv': '🇸🇪', 
    'da': '🇩🇰', 'fi': '🇫🇮', 'no': '🇳🇴', 'el': '🇬🇷', 'cs': '🇨🇿', 
    'ro': '🇷🇴', 'hu': '🇭🇺', 'sk': '🇸🇰', 'bg': '🇧🇬', 'hr': '🇭🇷', 
    'sl': '🇸🇮', 'he': '🇮🇱', 'fa': '🇮🇷', 'ms': '🇲🇾', 'ta': '🇮🇳', 
    'fil': '🇵🇭', 'af': '🇿🇦', 'ca': '🏴', 'yue': '🇭🇰'
};

// 2. Hàm format số liệu (ví dụ: 1200 -> 1.2k)
function formatMetric(num) {
    if (!num) return '0';
    if (num >= 1000000000) return (num / 1000000000).toFixed(1) + 'b';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'm';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num.toString();
}

// 3. Hàm tạo HTML thẻ Card
function createVoiceCardHTML(voice) {
    let isFav = favoriteVoices.includes(voice.id);
    let heartClass = isFav ? 'bi-heart-fill' : 'bi-heart';
    let heartStyle = isFav ? 'color: #ef4444;' : '';

    // Xử lý tên và mô tả an toàn
    let rawName = voice.name || 'Unknown';
    let safeName = rawName.replace(/'/g, "\\'");
    let desc = voice.description || 'Giọng đọc AI chất lượng cao.';

    // --- XỬ LÝ TAGS & METRICS ---
    let tagsHtml = '';

    // A. Tags phân loại (Accent, Gender...) - Lấy tối đa 2 cái đầu
    if (voice.tags && voice.tags.length > 0) {
        tagsHtml += voice.tags.slice(0, 2).map(t => 
            `<span class="vc-tag-pill">${t}</span>`
        ).join('');
    }

    // B. Metrics (Số lượt dùng & Clone)
    let usageCount = voice.usage_1y || 0; // Số lượt dùng 1 năm qua
    let clonedCount = voice.cloned || 0;  // Số lượt nhân bản
    
    // Icon người dùng (users)
    if(clonedCount > 0) {
        tagsHtml += `<span class="vc-tag-pill"><i class="bi bi-people-fill vc-tag-icon"></i> ${formatMetric(clonedCount)}</span>`;
    }
    // Icon tia sét (usage)
    if(usageCount > 0) {
        tagsHtml += `<span class="vc-tag-pill"><i class="bi bi-lightning-charge-fill vc-tag-icon"></i> ${formatMetric(usageCount)}</span>`;
    }

    // --- XỬ LÝ CỜ ---
    // Lấy mã ngôn ngữ (ví dụ: "en", "vi-VN" -> lấy "vi")
    let langCode = (voice.language || 'en').split('-')[0].toLowerCase();
    let flagIcon = FLAG_MAP[langCode] || '🌐'; // Mặc định là quả cầu nếu ko tìm thấy

    // Preview URL (nếu có)
    let previewUrl = (voice.preview_url || '').replace(/'/g, "\\'");

    return `
    <div class="voice-card" data-voice-id="${voice.id}">
        <div>
            <div class="vc-header">
                <div class="vc-name">
                    ${safeName} 
                    <i class="bi bi-check-circle-fill vc-verified-icon" title="Verified"></i>
                </div>
                <div class="vc-desc" title="${desc}">${desc}</div>
            </div>

            <div class="vc-tags-list">
                ${tagsHtml}
            </div>
        </div>

        <div class="vc-footer">
            <div class="vc-flags" title="Ngôn ngữ: ${langCode.toUpperCase()}">
                ${flagIcon}
            </div>

            <div class="vc-actions">
                <button class="vc-icon-btn" onclick="event.stopPropagation(); toggleFavorite(event, '${voice.id}')" title="Yêu thích">
                    <i class="bi ${heartClass}" style="${heartStyle}"></i>
                </button>
                
                <button class="vc-icon-btn" onclick="event.stopPropagation(); copyId('${voice.id}')" title="Copy ID">
                    <i class="bi bi-copy"></i>
                </button>

                ${previewUrl ? `
                <button class="vc-icon-btn" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${voice.id}')" title="Nghe thử">
                    <i class="bi bi-play-circle" style="font-size:20px;"></i>
                </button>
                ` : ''}

                <button class="vc-use-btn" onclick="event.stopPropagation(); chooseVoice('${voice.id}', '${safeName}')">
                    Dùng
                </button>
            </div>
        </div>
    </div>`;
}
// --- HÀM TẠO CARD RIÊNG CHO MINIMAX (GIỐNG ẢNH) ---
function createMinimaxVoiceCardHTML(voice) {
    let isFav = favoriteVoices.includes(voice.id);
    let heartClass = isFav ? 'bi-heart-fill active' : 'bi-heart';
    
    // Xử lý tên an toàn
    let rawName = voice.name || 'Unknown';
    let safeName = rawName.replace(/'/g, "\\'");
    
    // Xử lý Avatar (Fallback nếu lỗi)
    let avatar = voice.avatar;
    if (!avatar) {
        avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(rawName)}&background=random&color=fff&size=64&rounded=true`;
    }

    // Xử lý Tags (Ưu tiên: Language -> Gender -> Tags khác)
    let displayTags = [];
    
    // 1. Language
    let lang = voice.language || 'English';
    if(lang === 'vi-VN') lang = 'Vietnamese'; // Ví dụ mapping
    displayTags.push(lang);

    // 2. Gender
    if (voice.gender) {
        displayTags.push(voice.gender.charAt(0).toUpperCase() + voice.gender.slice(1));
    }

    // 3. Các tag khác (bỏ qua gender nếu trùng)
    if (voice.tags && Array.isArray(voice.tags)) {
        let otherTags = voice.tags.filter(t => 
            t.toLowerCase() !== (voice.gender || '').toLowerCase()
        );
        displayTags = displayTags.concat(otherTags);
    }

    // Tạo HTML cho tags (Lấy max 5 tags)
    let tagsHtml = displayTags.slice(0, 5).map(t => 
        `<span class="minimax-tag">${t}</span>`
    ).join('');

    // Preview URL
    let previewUrl = (voice.preview_url || '').replace(/'/g, "\\'");

    // --- RENDER HTML MINIMAX ---
    return `
    <div class="voice-card minimax-card-layout" data-voice-id="${voice.id}">
        
        <div class="vc-name" title="${safeName}">${safeName}</div>

        <div class="minimax-tags">
            ${tagsHtml}
        </div>

        <div class="minimax-footer">
            <img src="${avatar}" class="minimax-avatar" 
                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(rawName)}&background=333&color=fff'" 
                 alt="${safeName}">

            <div class="minimax-actions">
                <button class="minimax-icon-btn" onclick="event.stopPropagation(); toggleFavorite(event, '${voice.id}')" title="Yêu thích">
                    <i class="bi ${heartClass}"></i>
                </button>
                
                <button class="minimax-icon-btn" onclick="event.stopPropagation(); copyId('${voice.id}')" title="Copy ID">
                    <i class="bi bi-copy"></i>
                </button>

                ${previewUrl ? `
                <button class="minimax-icon-btn" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${voice.id}')" title="Nghe thử">
                    <i class="bi bi-play-circle" style="font-size: 20px;"></i>
                </button>
                ` : ''}

                <button class="minimax-use-btn" onclick="event.stopPropagation(); chooseVoice('${voice.id}', '${safeName}')">
                    Dùng
                </button>
            </div>
        </div>
    </div>`;
}
// ========================================
// ⚡ ENHANCE SHARED VOICE DATA (OPTIMIZED)
// ========================================
function enhanceSharedVoiceDataOptimized(voices) {
    if (!voices || !Array.isArray(voices)) {
        console.warn('Invalid voices data');
        return [];
    }
    
    return voices.map(v => ({
        // Core fields
        id: v.voice_id || v.id,
        name: v.name || 'Unknown',
        avatar: v.image_url || null,
        preview_url: v.preview_url || null,
        
        // Description (đã truncated ở BE)
        description: v.description || '',
        
        // Labels
        gender: v.gender || 'unknown',
        age: v.age || 'unknown',
        accent: v.accent || 'neutral',
        language: v.language || 'en',
        use_case: v.use_case || 'conversational',
        category: v.category || 'shared',
        featured: v.featured || false,
        
        // ✅ STATS (đã rút gọn tên từ BE)
        usage_1y: v.usage_1y || 0,
        cloned: v.cloned || 0,
        
        // Tags (build từ labels)
        tags: buildTags(v),
        
        source: v.source || 'elevenlabs_official'
    }));
}

// Helper: Build tags từ voice data
function buildTags(v) {
    let tags = ['Shared'];
    
    if (v.gender && v.gender !== 'unknown') {
        tags.push(v.gender.charAt(0).toUpperCase() + v.gender.slice(1));
    }
    
    if (v.age && v.age !== 'unknown') {
        tags.push(v.age.charAt(0).toUpperCase() + v.age.slice(1));
    }
    
    if (v.accent && v.accent !== 'neutral') {
        tags.push(v.accent.charAt(0).toUpperCase() + v.accent.slice(1));
    }
    
    if (v.featured) {
        tags.push('Featured');
    }
    
    return tags.filter(Boolean);
}

// Enhance Shared Voice Data
function enhanceSharedVoiceData(voices) {
    if (!voices || !Array.isArray(voices)) {
        console.warn('Invalid voices data');
        return [];
    }
    
    return voices.map(v => ({
        id: v.voice_id || v.id,
        name: v.name || 'Unknown',
        avatar: v.image_url || v.avatar || null,
        preview_url: v.preview_url || v.sample_audio || null,
        description: v.description || '',
        gender: v.gender || 'unknown',
        age: v.age || 'unknown',
        accent: v.accent || 'neutral',
        language: v.language || 'en',
        locale: v.locale || null,
        use_case: v.use_case || 'conversational',
        category: v.category || 'shared',
        featured: v.featured || false,
        can_be_finetuned: v.can_be_finetuned || false,
        live_moderation_enabled: v.live_moderation_enabled || false,
        tags: ['Shared', v.gender ? v.gender.charAt(0).toUpperCase() + v.gender.slice(1) : ''].filter(Boolean)
    }));
}
function renderVoiceGrid(voices) {
    // 1. Xử lý trường hợp không có dữ liệu (Empty State)
    if (!voices || voices.length === 0) {
        $('#voiceGrid').html(`
            <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                <i class="bi bi-inbox" style="font-size:48px; color:#555; display:block; margin-bottom:15px;"></i>
                <p style="color:#888; font-size:14px;">Không tìm thấy giọng nói</p>
                <button onclick="resetFilters()" style="
                    margin-top:15px;
                    background: transparent;
                    border: 1px solid #444;
                    color: #888;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                    transition: 0.2s;
                " onmouseover="this.style.borderColor='#666'; this.style.color='#ccc'" 
                  onmouseout="this.style.borderColor='#444'; this.style.color='#888'">
                    <i class="bi bi-arrow-counterclockwise"></i> Đặt lại bộ lọc
                </button>
            </div>
        `);
        return;
    }

    let html = '';

    // 2. Duyệt qua danh sách giọng và render
    voices.forEach(v => {
        // 🔥 LOGIC QUAN TRỌNG: Phân loại Card dựa trên Provider
        if (currentProvider === 'minimax') {
            // Sử dụng hàm tạo card mới (Avatar ở dưới, Tag viên thuốc) cho Minimax
            html += createMinimaxVoiceCardHTML(v);
        } else {
            // Sử dụng hàm tạo card cũ (Avatar ở trên, có Description) cho ElevenLabs
            html += createVoiceCardHTML(v);
        }
    });

    // 3. Đẩy HTML vào Grid
    $('#voiceGrid').html(html);
}


function toggleFavorite(event, voiceId) {
    event.stopPropagation();
    
    let index = favoriteVoices.indexOf(voiceId);
    let heartIcon = $(event.target);
    
    if (index > -1) {
        // Đã có → Xóa
        favoriteVoices.splice(index, 1);
        heartIcon.removeClass('bi-heart-fill active').addClass('bi-heart');
        showToast('💔 Đã xóa khỏi yêu thích');
    } else {
        // Chưa có → Thêm
        favoriteVoices.push(voiceId);
        heartIcon.removeClass('bi-heart').addClass('bi-heart-fill active');
        showToast('❤️ Đã thêm vào yêu thích');
    }
    
    // Lưu localStorage
    localStorage.setItem('favVoices', JSON.stringify(favoriteVoices));
}
// ========================================
// ⚡ SWITCH VOICE TAB (OPTIMIZED)
// ========================================
function switchVoiceTab(tab) {
    console.log('🔄 Switching Voice Tab to:', tab);
    currentVoiceTab = tab;
    
    // 1. Cập nhật giao diện Tab (Active state)
    $('.vm-tab').removeClass('active');
    $(`.vm-tab[data-tab="${tab}"]`).addClass('active');
    
    // 2. Reset ô tìm kiếm để tránh lỗi logic filter
    $('#voiceSearch').val('');
    
    // ============================================================
    // 🔥 3. XỬ LÝ HIỂN THỊ CÔNG CỤ (SORT & FILTERS)
    // ============================================================
    
    // Kiểm tra: Nếu là tab "Library" VÀ đang dùng "ElevenLabs"
    if (tab === 'library' && currentProvider === 'elevenlabs') {
        // A. Hiện nút Sắp xếp (Sort Dropdown)
        $('#sortDropdown').fadeIn(200); 
        
        // B. Hiện thanh Bộ lọc nâng cao (Lang, Gender, Age...)
        if ($('.vm-filters-bar').hasClass('hide-filters')) {
            $('.vm-filters-bar').removeClass('hide-filters');
            $('.filter-group, .filter-reset-btn').fadeIn(200);
        }
    } else {
        // A. Ẩn nút Sắp xếp ngay lập tức
        $('#sortDropdown').hide(); 
        
        // B. Ẩn thanh Bộ lọc nâng cao
        if (!$('.vm-filters-bar').hasClass('hide-filters')) {
            $('.filter-group, .filter-reset-btn').fadeOut(200, function() {
                $('.vm-filters-bar').addClass('hide-filters');
            });
        }
    }

    // 4. DỌN DẸP GRID CŨ & SCROLL EVENT
    $('.vm-grid').off('scroll'); // Quan trọng: Tắt sự kiện cuộn của tab trước
    $('#voiceGrid').empty();     // Xóa sạch nội dung cũ

    // ============================================================
    // 🚀 5. RENDER DỮ LIỆU THEO TỪNG TAB
    // ============================================================

    // 👉 CASE 1: GIỌNG MẶC ĐỊNH (DEFAULT)
    if (tab === 'default') {
        let sourceList = [];

        if (currentProvider === 'minimax') {
            // Minimax: Chỉ lấy giọng hệ thống (bỏ giọng clone)
            sourceList = (loadedVoices.minimax || []).filter(v => v.source === 'system');
        } else {
            // ElevenLabs: Lấy toàn bộ giọng mặc định
            sourceList = loadedVoices.elevenlabs || [];
        }

        if (sourceList.length === 0) {
             showVoiceErrorState('Không tìm thấy giọng mặc định.');
        } else {
            // 🔥 Dùng Progressive Render (Load từng đợt 50 cái) để KHÔNG LAG
            renderVoiceGridProgressive(sourceList);
        }
    } 
    
    // 👉 CASE 2: THƯ VIỆN (LIBRARY - SHARED VOICES)
    else if (tab === 'library') {
        // Logic Lazy Load: Chỉ tải từ API nếu chưa có dữ liệu
        if (!sharedVoicesLoaded && !sharedVoicesLoading) {
            loadSharedVoices(); // Gọi API lấy list
        } else if (sharedVoicesLoading) {
            showVoiceLoadingSpinner(); // Hiện loading...
        } else if (sharedVoicesLoaded && sharedVoices.length > 0) {
            // Đã có data -> Render luôn (Progressive)
            renderVoiceGridProgressive(sharedVoices); 
        } else {
            showVoiceErrorState('Thư viện trống hoặc lỗi tải dữ liệu.');
        }
    } 
    
    // 👉 CASE 3: YÊU THÍCH (FAVORITES)
    else if (tab === 'favorites') {
        // Gom tất cả giọng từ Default + Shared (nếu đã load) để tìm ID yêu thích
        let allVoices = [...(loadedVoices[currentProvider] || [])];

        // Nếu là ElevenLabs và đã load thư viện, merge thêm vào
        if (currentProvider === 'elevenlabs' && sharedVoices.length > 0) {
            const existingIds = new Set(allVoices.map(v => v.id));
            sharedVoices.forEach(sv => { 
                if (!existingIds.has(sv.id)) allVoices.push(sv); 
            });
        }
        
        // Lọc ra các giọng có ID nằm trong danh sách yêu thích
        let favs = allVoices.filter(v => favoriteVoices.includes(v.id));
        
        if (favs.length === 0) {
            // Empty State cho yêu thích
             $('#voiceGrid').html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <i class="bi bi-heart" style="font-size:48px; color:#333; display:block; margin-bottom:15px;"></i>
                    <p style="color:#888; font-size:14px;">Chưa có giọng yêu thích</p>
                </div>
             `);
        } else {
            // Favs thường ít nên render thẳng luôn (hoặc dùng Progressive cũng được)
            renderVoiceGrid(favs);
        }
    } 
    
    // 👉 CASE 4: GIỌNG NHÂN BẢN (CLONED - MINIMAX ONLY)
    else if (tab === 'cloned') {
        renderClonedVoices();
    }
}

// ========================================
// ⚡ SORTING LOGIC (MỚI)
// ========================================

// ========================================
// ⚡ FIX DROPDOWN: "GOD MODE" (BẤT CHẤP OVERFLOW)
// ========================================

// ========================================
// ⚡ SORT DROPDOWN: CƯỠNG CHẾ HIỂN THỊ
// ========================================
// 1. Hàm bật tắt Dropdown bất kỳ (Có fix lỗi bị che)
function toggleDropdown(e, menuId) {
    if (e) { e.preventDefault(); e.stopPropagation(); }

    const $menu = $(menuId);
    const $btn = $(e.currentTarget);

    if ($menu.length === 0) return;

    $('.universal-dropdown-menu').not($menu).hide();
    $('.dropdown-arrow').not($btn.find('.dropdown-arrow')).removeClass('bi-chevron-up').addClass('bi-chevron-down');

    if ($menu.is(':visible')) {
        $menu.hide();
        $btn.find('.dropdown-arrow').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        return;
    }

    $menu.addClass('universal-dropdown-menu');

    if ($menu.parent()[0] !== document.body) {
        $menu.detach().appendTo('body');
    }

    const rect = $btn[0].getBoundingClientRect();
    const menuWidth = 200;
    
    // 🔥 SỬA Ở ĐÂY: ƯU TIÊN CANH PHẢI
    let leftPos = rect.right - menuWidth; // Thay vì rect.left
    
    // Nếu tràn lề trái thì mới canh trái
    if (leftPos < 0) {
        leftPos = rect.left;
    }

    $menu.css({
        'display': 'block',
        'top': (rect.bottom + 5) + 'px',
        'left': leftPos + 'px',
        'width': menuWidth + 'px',
        'min-width': '160px'
    });

    $btn.find('.dropdown-arrow').removeClass('bi-chevron-down').addClass('bi-chevron-up');
}
function selectFilter(type, value, label, displayLabel) {
    console.log(`✅ Filter Selected: ${type} = ${value}`);

    // 1. Cập nhật giá trị vào Input ẩn
    $(`#filter${type}`).val(value);
    
    // 2. Cập nhật text hiển thị trên nút
    $(`#label${type}`).text(displayLabel || label);
    
    // 3. Highlight item được chọn trong menu
    $(`#menu${type} .dropdown-item`).css({
        'background':'transparent', 
        'color':'#ccc', 
        'font-weight':'normal'
    });
    $(event.target).css({
        'background':'#222', 
        'color':'#fff', 
        'font-weight':'600'
    });

    // 4. ✅ THÊM/XÓA CLASS has-value DỰA VÀO GIÁ TRỊ
    const $filterGroup = $(`.btn-${type}`).closest('.filter-dropdown-enhanced');
    
    if (value && value !== '') {
        $filterGroup.addClass('has-value'); // ✅ Hiện gradient
    } else {
        $filterGroup.removeClass('has-value'); // ❌ Ẩn gradient
    }

    // 5. Đóng menu
    $(`#menu${type}`).hide();
    $(`.btn-${type} .dropdown-arrow`).removeClass('bi-chevron-up').addClass('bi-chevron-down');

    // 6. Gọi hàm lọc
    filterVoices();
}
function toggleSortDropdown(e) {
    if (e) { 
        e.preventDefault(); 
        e.stopPropagation(); 
    }

    const $menu = $('#sortMenu');
    const $btn = $(e.currentTarget);

    console.log('🔍 Menu element:', $menu[0]); // Debug
    console.log('🔍 Menu display:', $menu.css('display'));
    console.log('🔍 Menu position:', $menu.offset());

    // Đóng các dropdown khác
    $('.dropdown-menu, .universal-dropdown-menu').not($menu).hide();
    $('.dropdown-arrow').removeClass('bi-chevron-up').addClass('bi-chevron-down');

    // Toggle
    if ($menu.is(':visible')) {
        $menu.hide();
        $btn.find('.dropdown-arrow')
            .removeClass('bi-chevron-up')
            .addClass('bi-chevron-down');
        return;
    }

    // 🔥 FORCE CSS ĐỂ ĐẢM BẢO HIỂN THỊ
    $menu.css({
        'display': 'block',
        'position': 'fixed',
        'visibility': 'visible', // ✅ Thêm
        'opacity': '1',          // ✅ Thêm
        'z-index': '999999',     // ✅ Tăng lên rất cao
    });

    // Tính toán vị trí
    const rect = $btn[0].getBoundingClientRect();
    const menuWidth = 200;
    let leftPos = rect.right - menuWidth;

    if (leftPos < 0) {
        leftPos = rect.left;
    }

    // Áp dụng vị trí
    $menu.css({
        'top': (rect.bottom + 5) + 'px',
        'left': leftPos + 'px',
        'width': menuWidth + 'px'
    });

    console.log('✅ Menu shown at:', {
        top: $menu.css('top'),
        left: $menu.css('left'),
        zIndex: $menu.css('z-index')
    });

    // Xoay mũi tên
    $btn.find('.dropdown-arrow')
        .removeClass('bi-chevron-down')
        .addClass('bi-chevron-up');
}
$(document).on('click', function(e) {
    // Bỏ qua nếu click vào nút hoặc menu
    if ($(e.target).closest('#sortDropdown, #sortMenu, .dropdown-btn').length) return;
    
    $('#sortMenu').fadeOut(100);
    $('.dropdown-arrow').removeClass('bi-chevron-up').addClass('bi-chevron-down');
});

function applySort(type, label) {
    console.log("✅ Selected sort:", type);

    // 1. Cập nhật text trên nút
    $('#currentSortLabel').text(label);
    
    // 2. ✅ ĐẶT LẠI TẤT CẢ ITEM VỀ TRẠNG THÁI BÌnh THƯỜNG
    $('#sortMenu .dropdown-item').css({ 
        'background': 'transparent', 
        'color': '#ccc', 
        'font-weight': 'normal' 
    }).removeClass('active'); // ✅ Xóa class active
    
    // 3. ✅ HIGHLIGHT ITEM ĐANG CHỌN (Dùng event.target)
    $(event.target).css({ 
        'background': '#222', 
        'color': '#fff', 
        'font-weight': '600' 
    }).addClass('active'); // ✅ Thêm class active
    
    // 4. Đóng menu
    $('#sortMenu').fadeOut(100);
    $('.dropdown-arrow').removeClass('bi-chevron-up').addClass('bi-chevron-down');

    // --- LOGIC SẮP XẾP (giữ nguyên) ---
    let sourceList = [];
    
    if (currentVoiceTab === 'library') {
        sourceList = [...sharedVoices];
    } else if (currentVoiceTab === 'default') {
        sourceList = (currentProvider === 'minimax') 
            ? [...(loadedVoices.minimax || [])].filter(v => v.source === 'system')
            : [...(loadedVoices.elevenlabs || [])];
    } else {
        return; 
    }

    // Sắp xếp
    if (type === 'most_used') {
        sourceList.sort((a, b) => (b.cloned || 0) - (a.cloned || 0));
    } else if (type === 'chars') {
        sourceList.sort((a, b) => (b.usage_1y || 0) - (a.usage_1y || 0));
    } else if (type === 'trending') {
        sourceList.sort((a, b) => ((b.cloned || 0) + (b.usage_1y / 10000)) - ((a.cloned || 0) + (a.usage_1y / 10000)));
    } else if (type === 'newest') {
        // sourceList.reverse();
    }

    renderVoiceGridProgressive(sourceList);
}

// ========================================
// ⚡ HELPER: SHOW LOADING SPINNER
// ========================================
function showVoiceLoadingSpinner() {
    $('#voiceGrid').html(`
        <div style="color:#888; text-align:center; padding:60px 20px; grid-column: 1 / -1;">
            <div class="spinner-border" style="width:40px; height:40px; color:#667eea;"></div>
            <p style="margin-top:15px; font-size:14px;">Đang tải thư viện giọng nói...</p>
            <p style="font-size:12px; color:#666; margin-top:8px;">Vui lòng đợi trong giây lát</p>
        </div>
    `);
}

// ========================================
// ⚡ HELPER: SHOW ERROR STATE
// ========================================
function showVoiceErrorState(message, details = '') {
    $('#voiceGrid').html(`
        <div style="color:#ef4444; text-align:center; padding:60px 20px; grid-column: 1 / -1;">
            <i class="bi bi-exclamation-triangle" style="font-size:48px; display:block; margin-bottom:16px; color:#f59e0b;"></i>
            <p style="font-size:14px; margin-bottom:8px; font-weight:600;">${message}</p>
            ${details ? `<p style="font-size:12px; color:#888; margin-bottom:16px;">${details}</p>` : ''}
            <button onclick="reloadSharedVoices()" class="btn-generate" style="margin:0; padding:8px 16px; font-size:13px;">
                <i class="bi bi-arrow-clockwise"></i> Thử lại
            </button>
        </div>
    `);
}

// ========================================
// ⚡ HELPER: RELOAD SHARED VOICES
// ========================================
function reloadSharedVoices() {
    console.log('🔄 Manual reload triggered');
    sharedVoicesLoaded = false;
    sharedVoicesLoading = false;
    sharedVoices = [];
    sharedVoicesRendered = 0;
    loadSharedVoices();
}

// ========================================
// ⚡ HELPER: WAIT FOR LOAD COMPLETE
// ========================================
function waitForSharedVoicesLoad() {
    let pollAttempts = 0;
    const maxAttempts = 300; // 60 giây (300 × 200ms)
    
    let checkInterval = setInterval(() => {
        pollAttempts++;
        
        if (sharedVoicesLoaded && sharedVoices.length > 0) {
            clearInterval(checkInterval);
            console.log('✅ Shared voices loaded via polling');
            renderVoiceGridProgressive(sharedVoices);
        } else if (pollAttempts >= maxAttempts) {
            clearInterval(checkInterval);
            console.warn('⚠️ Polling timeout');
            showVoiceErrorState('Timeout', 'Vui lòng thử lại');
        }
    }, 200);
}
function renderClonedVoices() {
    let clonedVoices = (loadedVoices.minimax || []).filter(v => v.source === 'cloned');
    $('#voiceGrid').empty();
    
    // Card "Nhân bản mới"
    let addCloneCardHtml = `
    <div class="voice-card add-clone-card" onclick="window.location.href='/ai/voice_cloning'">
        <div style="
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 30px 20px;
        ">
            <div class="add-avatar">
                <i class="bi bi-plus-lg"></i>
            </div>
            <div class="add-text">Nhân bản giọng mới</div>
        </div>
    </div>`;
    
    $('#voiceGrid').append(addCloneCardHtml);
    
    // Render cloned voices
    if (clonedVoices.length > 0) {
        clonedVoices.forEach(v => {
            let isFav = favoriteVoices.includes(v.id);
            let heartClass = isFav ? 'bi-heart-fill active' : 'bi-heart';
            let heartStyle = isFav ? 'color: #ef4444;' : '';
            
            // 🔥 XỬ LÝ AVATAR CHO CLONED VOICES
            let avatar = v.avatar;
            
            // Fallback chain
            if (!avatar) {
                avatar = v.cover_url || v.image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=random&color=fff&size=64`;
            }
            
            let tagsHtml = '';
            let tags = v.tags || [];
            tags.slice(0, 3).forEach(tag => {
                if (tag) {
                    tagsHtml += `<span class="vc-tag">${tag}</span>`;
                }
            });
            
            let desc = v.description || '';
            if (desc.length > 80) {
                desc = desc.substring(0, 80) + '...';
            }
            
            let previewUrl = (v.preview_url || v.sample_audio || '').replace(/'/g, "\\'");
            
            let cardHtml = `
            <div class="voice-card" data-voice-id="${v.id}">
                <div class="vc-top">
                    <img src="${avatar}" 
                         class="vc-avatar" 
                         onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=333&color=fff&size=64'" 
                         alt="${v.name}">
                    <div class="vc-info">
                        <div class="vc-name" title="${v.name}">${v.name}</div>
                        <div class="vc-tags">${tagsHtml}</div>
                    </div>
                </div>
                
                ${desc ? `<div class="vc-desc">${desc}</div>` : ''}
                
                <div class="vc-footer">
                    <div class="vc-actions">
                        <i class="bi ${heartClass}" style="${heartStyle}" title="Yêu thích" onclick="event.stopPropagation(); toggleFavorite(event, '${v.id}')"></i>
                        ${previewUrl ? `<i class="bi bi-play-circle" title="Nghe thử" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${v.id}')"></i>` : ''}
                    </div>
                    <button class="vc-use-btn" onclick="event.stopPropagation(); chooseVoice('${v.id}', '${v.name.replace(/'/g, "\\'")}')">Dùng</button>
                </div>
            </div>`;
            
            $('#voiceGrid').append(cardHtml);
        });
    }
}
// 🔥 MỞ CLONE MODAL
function openCloneModal() {
    closeVoiceModal();
    $('#cloneModal').fadeIn();
}

// ==========================================
// 🎯 CORE: FILTER VOICES (LOCAL + SERVER FALLBACK)
// ==========================================

// Biến hỗ trợ debounce (chờ người dùng gõ xong mới tìm)
let searchTimeout = null;

function filterVoices() {
    // 🛑 1. LẤY GIÁ TRỊ TỪ BỘ LỌC (Input ẩn & Search box)
    let searchRaw = $('#voiceSearch').val().trim();
    let search = searchRaw.toLowerCase();
    
    let lang = $('#filterLang').val();
    let gender = $('#filterGender').val();
    let age = $('#filterAge').val();
    let category = $('#filterCategory').val();
    let accent = $('#filterAccent').val();

    // 🛑 2. XÁC ĐỊNH NGUỒN DỮ LIỆU
    let sourceList = [];
    if (currentVoiceTab === 'default') {
        if (currentProvider === 'minimax') {
            sourceList = (loadedVoices.minimax || []).filter(v => v.source === 'system');
        } else {
            sourceList = loadedVoices.elevenlabs || [];
        }
    } else if (currentVoiceTab === 'library') {
        sourceList = sharedVoices;
    } else if (currentVoiceTab === 'favorites') {
        // Logic lấy fav list (như cũ)
        let all = [...(loadedVoices[currentProvider] || [])];
        if (currentProvider === 'elevenlabs' && sharedVoices.length > 0) {
            const existingIds = new Set(all.map(v => v.id));
            sharedVoices.forEach(sv => { if (!existingIds.has(sv.id)) all.push(sv); });
        }
        sourceList = all.filter(v => favoriteVoices.includes(v.id));
    } else if (currentVoiceTab === 'cloned') {
        sourceList = (loadedVoices.minimax || []).filter(v => v.source === 'cloned');
    }

    // 🛑 3. LỌC DỮ LIỆU (TỐC ĐỘ CAO)
    // Javascript lọc mảng 10k phần tử cực nhanh, không gây lag ở đây
    let filtered = sourceList.filter(v => {
        // A. Lọc từ khóa
        let matchSearch = true;
        if (search) {
            let name = (v.name || '').toLowerCase();
            let id = String(v.id || '').toLowerCase(); 
            // Chỉ tìm theo tên và ID cho nhanh, bỏ description nếu muốn siêu tốc
            matchSearch = name.includes(search) || id.includes(search);
        }

        // B. Lọc theo Dropdown (Nếu value rỗng = lấy hết)
        let matchLang = !lang || (v.language || '').toLowerCase().includes(lang.toLowerCase());
        let matchGender = !gender || (v.gender || '').toLowerCase() === gender.toLowerCase();
        
        // Với mảng tags
        let matchAge = !age || (v.tags || []).some(t => t.toLowerCase().includes(age));
        let matchCategory = !category || (v.tags || []).some(t => t.toLowerCase().includes(category));
        let matchAccent = !accent || (v.tags || []).some(t => t.toLowerCase().includes(accent));

        return matchSearch && matchLang && matchGender && matchAge && matchCategory && matchAccent;
    });

    // 🛑 4. RENDER KẾT QUẢ (QUAN TRỌNG NHẤT: DÙNG PROGRESSIVE)
    
    // Xóa sự kiện cuộn cũ để tránh xung đột
    $('.vm-grid').off('scroll'); 
    $('#voiceGrid').empty();

    if (filtered.length > 0) {
        // 🔥 CHÌA KHÓA CHỐNG LAG LÀ ĐÂY:
        // Thay vì renderVoiceGrid(filtered) -> gọi renderVoiceGridProgressive(filtered)
        renderVoiceGridProgressive(filtered);
        
        // Cập nhật UI highlight bộ lọc
        updateFilterIndicators();
    } 
    else {
        // Xử lý khi không tìm thấy kết quả
        if (currentProvider === 'elevenlabs' && searchRaw.length >= 15 && !searchRaw.includes(' ')) {
            // Logic tìm ID trên server (Debounce 1s)
            clearTimeout(searchTimeout);
            
            // Hiển thị loading tạm
            $('#voiceGrid').html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <div class="spinner-border" style="width:30px; height:30px; color:#667eea;"></div>
                    <p style="color:#888; margin-top:15px; font-size:14px;">Đang tìm ID trên server...</p>
                </div>
            `);

            searchTimeout = setTimeout(() => {
                if (typeof searchVoiceOnServer === 'function') {
                    searchVoiceOnServer(searchRaw);
                }
            }, 1000);
        } else {
            // Không tìm thấy
            $('#voiceGrid').html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <i class="bi bi-search" style="font-size:48px; color:#333; display:block; margin-bottom:15px;"></i>
                    <p style="color:#888; font-size:14px;">Không tìm thấy kết quả phù hợp</p>
                    <button onclick="resetFilters()" class="filter-reset-btn" style="margin: 15px auto; width: auto; padding: 8px 16px;">
                        Xóa bộ lọc
                    </button>
                </div>
            `);
        }
    }
}

// Sort Voices
function sortVoices() {
    let sortBy = $('#voiceSort').val();
    let sourceList = currentVoiceTab === 'default' ? loadedVoices[currentProvider] : sharedVoices;
    
    let sorted = [...sourceList];
    
    if (sortBy === 'newest') {
        // Mặc định
    } else if (sortBy === 'popular') {
        sorted.sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0));
    } else if (sortBy === 'name') {
        sorted.sort((a, b) => a.name.localeCompare(b.name));
    }
    
    renderVoiceGrid(sorted);
}

// Reset Filters
function resetFilters() {
    // Reset giá trị input ẩn
    $('#filterLang, #filterGender, #filterAge, #filterCategory, #filterAccent, #voiceSearch').val('');
    
    // Reset text trên nút về "Tất cả"
    $('#labelLang, #labelGender, #labelAge, #labelCategory, #labelAccent').text('Tất cả');
    
    // ✅ XÓA CLASS has-value (Ẩn gradient)
    $('.filter-dropdown-enhanced').removeClass('has-value');
    
    // Xóa active class trong menu
    $('.dropdown-item').css({
        'background':'transparent', 
        'color':'#ccc', 
        'font-weight':'normal'
    });
    
    // Re-render
    switchVoiceTab(currentVoiceTab);
}

function closeVoiceModal() {
    $('#voiceModal').fadeOut(200);
    stopPreview();
}


function togglePreview(url, id) {
    if (!url) {
        showToast('⚠️ Không có audio preview');
        return;
    }
    
    // Reset tất cả icons
    $('.vc-actions .bi-pause-circle').removeClass('bi-pause-circle').addClass('bi-play-circle');

    if (currentPreviewUrl === url && !previewAudio.paused) {
        // Đang play → pause
        previewAudio.pause();
        currentPreviewUrl = null;
    } else {
        // Play mới
        previewAudio.src = url;
        previewAudio.play().catch(e => {
            console.error('Play error:', e);
            showToast('⚠️ Không thể phát audio');
        });
        currentPreviewUrl = url;
        
        // Đổi icon thành pause
        $(`.voice-card[data-voice-id="${id}"] .bi-play-circle`).removeClass('bi-play-circle').addClass('bi-pause-circle');
    }
}

function stopPreview() {
    previewAudio.pause();
    previewAudio.currentTime = 0;
    currentPreviewUrl = null;
    $('.vc-actions .bi-pause-circle').removeClass('bi-pause-circle').addClass('bi-play-circle');
}

previewAudio.onended = function() {
    $('.vc-actions .bi-pause-circle').removeClass('bi-pause-circle').addClass('bi-play-circle');
    currentPreviewUrl = null;
};

function chooseVoice(id, name) {
    $('#voiceIdVal').val(id);
    $('#selectedVoiceName').text(name);
    
    closeVoiceModal();
    showToast(`✅ Đã chọn: ${name}`);

    // 🔥 THÊM DÒNG NÀY: Tính tiền ngay lập tức sau khi chọn giọng
    updateEstimatedCost(); 
}

function copyId(id) {
    navigator.clipboard.writeText(id).then(() => {
        let toast = $('<div>').css({
            position: 'fixed',
            bottom: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            background: '#22c55e',
            color: 'white',
            padding: '12px 24px',
            borderRadius: '8px',
            zIndex: 9999
        }).text('✓ ID đã được copy!');
        
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(() => toast.remove()), 2000);
    });
}

// ========== TEXT INPUT ==========
function calculateCost() {
    // 1. Lấy nội dung và đếm ký tự
    let text = $('#txtInput').val() || ''; // Thêm || '' để tránh lỗi nếu null
    let charCount = text.length;

    // 2. Lấy Cost Factor từ Model đang chọn
    let cost_factor = 1.0;

    if (currentProvider === 'minimax') {
        let model = loadedModels.minimax.find(m => m.id === selectedMinimaxModel);
        if (model) {
            cost_factor = model.cost_factor || 1.0;
        }
    } else {
        // ElevenLabs
        let currentModelName = $('#selectedModelName').text();
        let model = loadedModels.elevenlabs.find(m => currentModelName.includes(m.name));
        if (model) {
            cost_factor = model.cost_factor || 1.0;
        }
    }

    // --- BẮT ĐẦU DEBUG TÍNH TIỀN ---
    let base_rate = 1.12;
    
    // Check trạng thái
    let isSrtUpload = (window.isSrtFile === true); 
    let isSubtitleChecked = $('#subtitleCheck').is(':checked');
    let applySrtFee = isSrtUpload || isSubtitleChecked;
    let estimated_cost;
    
    // Tính toán
    if (applySrtFee) {
        // Có phí SRT
        estimated_cost = charCount * base_rate * cost_factor * 1.2;
        $('#srtFeeInfo').show();
    } else {
        // Không phí SRT
        estimated_cost = charCount * base_rate * cost_factor;
        $('#srtFeeInfo').hide();
    }

    // Làm tròn
    let total_cost = 0;
    if (charCount > 0) {
        if (cost_factor < 1.0) {
            total_cost = Math.ceil(estimated_cost);
        } else {
            total_cost = Math.floor(estimated_cost);
        }
        total_cost = Math.max(1, total_cost);
    }


    // Hiển thị ra màn hình
    $('#estimatedCost').text(total_cost.toLocaleString());

    let currentCredits = parseInt($('#userCredits').text().replace(/,/g, '') || '0');
    if (currentCredits < total_cost && charCount > 0) {
        $('#estimatedCost').css('color', '#ef4444'); 
    } else {
        $('#estimatedCost').css('color', '#fbbf24'); 
    }
}

// Gọi calculateCost khi thay đổi text
$('#txtInput').on('input', calculateCost);

function togglePlaceholder() {
    if ($('#txtInput').val().length > 0) {
        $('#emptyState').css('opacity', '0');
    } else {
        $('#emptyState').css('opacity', '1');
    }
    calculateCost();
}

function setupDragDrop() {
    const dropZone = document.getElementById('dropZone');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
    });

    dropZone.addEventListener('drop', handleDrop, false);
}

function handleDrop(e) {
    let dt = e.dataTransfer;
    let files = dt.files;
    handleFiles(files);
}

// Tìm function handleFiles(files) và thay thế TOÀN BỘ bằng đoạn này:
function handleFiles(files) {
    // Chuyển đổi danh sách file thành Array chuẩn
    let fileArray = Array.from(files);
    handleGlobalDrop(fileArray);
}
function handleFileSelect(input) {
    if (input.files && input.files.length > 0) {
        handleFiles(input.files);
    }
}

// ========== RESET FUNCTIONS ==========
function resetMinimaxSettings() {
    $('#speed').val(1.0);
    $('#speedVal').text('1.00');
    updateSliderFill(document.getElementById('speed')); // THÊM
    
    $('#vol').val(1.0);
    $('#volVal').text('1.00');
    updateSliderFill(document.getElementById('vol')); // THÊM
    
    $('#pitch').val(0);
    $('#pitchVal').text('0');
    updateSliderFill(document.getElementById('pitch')); // THÊM
    
    selectLanguage('Auto', 'Tự xác định');
}

function resetElevenLabsSettings() {
    // 1. Speed - ALWAYS RESET
    $('#elevenSpeed').val(1.0);
    $('#elevenSpeedVal').text('1.00');
    $('#elevenSpeed').closest('.slider-container').removeClass('warning');
    updateSliderFill(document.getElementById('elevenSpeed'));
    
    // 2. Stability - ALWAYS RESET
    $('#stability').val(50);
    
    // 🔥 QUAN TRỌNG: Trigger sự kiện input để code tự động nhận diện:
    // - Nếu là V3 (step=50) -> Nó tự đổi thành chữ "Natural" màu trắng.
    // - Nếu là Model thường (step=1) -> Nó tự đổi thành "50%" màu vàng.
    $('#stability').trigger('input'); 
    
    updateSliderFill(document.getElementById('stability'));
    
    // 3. Similarity - Chỉ reset nếu đang hiện
    if ($('#slider-similarity').is(':visible')) {
        $('#similarity').val(75);
        $('#similarityVal').text('75%');
        updateSliderFill(document.getElementById('similarity'));
    }
    
    // 4. Style - Chỉ reset nếu đang hiện
    if ($('#slider-style').is(':visible')) {
        $('#style').val(0);
        $('#styleVal').text('0%');
        updateSliderFill(document.getElementById('style'));
    }
    
    // 5. Boost - Chỉ reset nếu đang hiện
    if ($('#toggle-boost').is(':visible')) {
        $('#boostCheck').prop('checked', true);
    }
}

// ========== TAB SWITCHING WITH SMOOTH ANIMATION ==========
function switchTab(tabName) {
    console.log('🔄 SWITCHING TAB TO:', tabName);
    
    const currentTab = $('.tab-btn.active').attr('id') === 'btnSettings' ? 'settings' : 'history';
    
    // Nếu bấm lại tab đang active thì thôi
    if (currentTab === tabName) return;

    // 1. Cập nhật nút active ngay lập tức
    $('.tab-btn').removeClass('active');
    if (tabName === 'settings') {
        $('#btnSettings').addClass('active');
    } else {
        $('#btnHistory').addClass('active');
    }

    // 2. Xử lý Animation chuyển đổi
    const $currentContent = $('.sidebar-content.show');
    const $nextContent = tabName === 'settings' ? $('#viewSettings') : $('#viewHistory');
    
    // Xác định hướng animation
    // Settings -> History: Slide Left (Nội dung mới từ phải qua)
    // History -> Settings: Slide Right (Nội dung mới từ trái qua)
    const animationClass = tabName === 'settings' ? 'animate-slide-in-left' : 'animate-slide-in-right';

    // Fade out nội dung cũ
    $currentContent.addClass('animate-fade-out');
    
    setTimeout(() => {
        $currentContent.removeClass('show animate-fade-out');
        
        // Hiện nội dung mới và chạy animation
        $nextContent.addClass('show ' + animationClass);
        
        // Xóa class animation sau khi chạy xong để sạch sẽ
        setTimeout(() => {
            $nextContent.removeClass(animationClass);
        }, 400); // Khớp với thời gian animation trong CSS

    }, 200); // Thời gian fade out

    // 3. Logic ẩn/hiện Header (Provider/History Actions)
    // Phần này giữ nguyên logic cũ của bạn
    if (tabName === 'settings') {
        $('#providerWrapper').fadeIn(300);
        $('#historyActions').fadeOut(300);
    } else {
        $('#providerWrapper').fadeOut(300);
        setTimeout(() => {
             $('#historyActions').css('display', 'flex').hide().fadeIn(300);
        }, 300); // Đợi cái kia ẩn xong mới hiện cái này lên
    }
}
// ========== LÀM MỚI LỊCH SỬ ==========
function refreshHistory() {
    // 1. Reset các biến đếm phân trang về ban đầu
    currentOffset = 0;
    hasMoreHistory = true;
    isLoadingHistory = false; // Mở khóa nếu đang bị kẹt
    
    // 2. Xóa sạch danh sách hiện tại & Ẩn thông báo "Hết dữ liệu"
    $('#historyListContainer').empty();
    $('#noMoreData').hide();
    
    // 3. Hiển thị trạng thái đang tải (tùy chọn cho đẹp)
    $('#historyListContainer').html(`
        <div style="text-align:center; padding:40px 0; color:#666;">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <div style="margin-top:10px; font-size:12px;">Đang làm mới...</div>
        </div>
    `);

    // 4. Gọi lại hàm loadHistory để tải trang 1
    loadHistory();
}
function calculateSingleCost(text) {
    // 1. Kiểm tra đầu vào
    if (!text) return 0;
    let charCount = text.length;
    
    // ==========================================
    // 2. TÍNH HỆ SỐ MODEL (Cost Factor)
    // ==========================================
    let cost_factor = 1.0;

    if (currentProvider === 'minimax') {
        // Lấy từ biến global selectedMinimaxModel
        if (typeof loadedModels !== 'undefined' && loadedModels.minimax) {
            let model = loadedModels.minimax.find(m => m.id === selectedMinimaxModel);
            if (model) cost_factor = model.cost_factor || 1.0;
        }
    } else {
        // ElevenLabs: Lấy từ text hiển thị
        let currentModelName = $('#selectedModelName').text();
        if (typeof loadedModels !== 'undefined' && loadedModels.elevenlabs) {
            let model = loadedModels.elevenlabs.find(m => currentModelName.includes(m.name));
            if (model) cost_factor = model.cost_factor || 1.0;
        }
    }

    // ==========================================
    // 3. TÍNH HỆ SỐ CLONE (x1.15) - QUAN TRỌNG
    // ==========================================
    let clone_multiplier = 1.0;

    // Chỉ áp dụng logic này cho Minimax (theo yêu cầu của bạn)
    if (currentProvider === 'minimax') {
        let isClone = false;

        // --- Kiểm tra 1: Biến toàn cục Tab (Fix lỗi F5 reload trang) ---
        // Biến này đã được restore từ localStorage trong $(document).ready
        if (typeof currentVoiceTab !== 'undefined' && currentVoiceTab === 'cloned') {
            isClone = true;
            // console.log("💰 Cost: Detected Clone Tab");
        }

        // --- Kiểm tra 2: Dựa vào ID giọng đã chọn ---
        if (!isClone) {
            let voiceId = $('#voiceIdVal').val();
            if (voiceId && typeof loadedVoices !== 'undefined' && loadedVoices.minimax) {
                // Tìm giọng trong danh sách đã load
                // Lưu ý: Dùng == để so sánh lỏng (string vs number)
                let voiceObj = loadedVoices.minimax.find(v => v.id == voiceId);
                
                if (voiceObj) {
                    // Kiểm tra source hoặc tags
                    if (voiceObj.source === 'cloned') isClone = true;
                    if (voiceObj.tags && Array.isArray(voiceObj.tags)) {
                        if (voiceObj.tags.includes('Clone') || voiceObj.tags.includes('clone')) {
                            isClone = true;
                        }
                    }
                }
            }
        }

        // --- Kiểm tra 3: Fallback dựa vào tên hiển thị (UI) ---
        if (!isClone) {
            let voiceName = $('#selectedVoiceName').text().toLowerCase();
            if (voiceName.includes('clone') || voiceName.includes('(clone)')) {
                isClone = true;
            }
        }

        // 👉 Áp dụng hệ số nếu phát hiện là Clone
        if (isClone) {
            clone_multiplier = 1.15;
        }
    }

    // ==========================================
    // 4. TÍNH HỆ SỐ PHỤ ĐỀ (SRT) (x1.2)
    // ==========================================
    let with_transcript = false;
    
    // Kiểm tra đúng checkbox theo từng provider
    if (currentProvider === 'minimax') {
        with_transcript = $('#minimaxSubtitleCheck').is(':checked');
    } else {
        with_transcript = $('#subtitleCheck').is(':checked');
    }

    // Hoặc nếu đang upload file .srt
    if (typeof window.isSrtFile !== 'undefined' && window.isSrtFile === true) {
        with_transcript = true;
    }

    // ==========================================
    // 5. TỔNG HỢP & TÍNH TOÁN
    // ==========================================
    let base_rate = 1.12;

    // Công thức: Ký tự * Giá gốc * Model * Clone
    let estimated_cost = charCount * base_rate * cost_factor * clone_multiplier;

    // Nếu có SRT: nhân thêm 1.2
    if (with_transcript) {
        estimated_cost *= 1.2;
    }

    // ==========================================
    // 6. LÀM TRÒN (Rounding Strategy)
    // ==========================================
    let total_cost = 0;

    // Logic làm tròn để bảo vệ credit hệ thống
    if (cost_factor < 1.0) {
        // Model rẻ (Turbo/Flash): Làm tròn lên (Ceil) để tránh bị quá lỗ
        total_cost = Math.ceil(estimated_cost);
    } else {
        // Model thường/đắt: Làm tròn xuống (Floor) cho số đẹp, hoặc Ceil tùy bạn
        // Ở đây giữ logic cũ của bạn là Floor, nhưng tối ưu nhất là Math.round hoặc Math.ceil
        total_cost = Math.floor(estimated_cost);
    }

    // Giá tối thiểu luôn là 1 credit
    return Math.max(1, total_cost);
}
// ========== TTS GENERATION ==========
function startTTS() {
    // 1. Kiểm tra API Key
    if (!hasApiKey) {
        $('#apiKeyModal').fadeIn();
        return;
    }

    // --- 👇 SỬA ĐOẠN NÀY 👇 ---
    // Kiểm tra xem Backup có khả dụng không
    let isBackupAvailable = (typeof backupEligible !== 'undefined' && backupEligible && 
                             typeof genaiBackupDown !== 'undefined' && !genaiBackupDown);

    // Chỉ chặn nếu CẢ 3 (11Labs + Minimax + Backup) đều không dùng được
    if (typeof elevenlabsDown !== 'undefined' && elevenlabsDown && 
        typeof minimaxDown !== 'undefined' && minimaxDown && !isBackupAvailable) {
        
        alert("⚠️ Hệ thống đang bảo trì toàn diện.\nVui lòng quay lại sau!");
        window.location.href = '/pages/maintenance.php'; 
        return;
    }
    // --- 👆 HẾT ĐOẠN SỬA 👆 ---

    let text = $('#txtInput').val();
    let voiceId = $('#voiceIdVal').val();
    
    // 2. Kiểm tra giọng nói
    if (!voiceId) {
        showToast('⚠️ Vui lòng chọn giọng nói trước khi tạo!');
        openVoiceModal();
        return;
    }
    
    // 3. Kiểm tra nội dung
    if (!text) {
        showToast('⚠️ Vui lòng nhập nội dung văn bản!');
        $('#txtInput').focus();
        return;
    }

    // 4. Kiểm tra Minimax Down
    if (typeof minimaxDown !== 'undefined' && currentProvider === 'minimax' && minimaxDown) {
        showToast('⚠️ Minimax đang bảo trì. Vui lòng chọn ElevenLabs!');
        selectProvider('elevenlabs');
        return;
    }
    
    // 5. Kiểm tra ElevenLabs Down
    if (typeof elevenlabsDown !== 'undefined' && currentProvider === 'elevenlabs' && elevenlabsDown) {
        console.log('🛡️ [DEBUG] startTTS: ElevenLabs đang down.'); 

        // Kiểm tra biến genaiBackupDown
        let isBackupDown = (typeof genaiBackupDown !== 'undefined' && genaiBackupDown);
        
        if (typeof backupEligible !== 'undefined' && backupEligible && !isBackupDown) {
             console.log('   -> User được dùng Backup -> Tiếp tục.'); 
        } else {
             console.log('   -> Không dùng được Backup -> Force chuyển Minimax.');
             
             let msg = '⚠️ ElevenLabs đang bảo trì... Đã chuyển sang Minimax!';
             if (isBackupDown) {
                 msg = '⚠️ Hệ thống Backup đang bảo trì. Vui lòng dùng Minimax!';
             }
             
             showToast(msg);
             selectProvider('minimax');
             return;
        }
    }

    // 6. CHECK MAINTENANCE VIA API (Kiểm tra chốt hạ với Server)
    let checkPath = `/ai/tts?provider=${currentProvider}`;
    
    // 🔥 Disable nút & hiện loading
    $('#btnProcess').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> <span>Kiểm tra...</span>');

    $.post('../../ajaxs/check_maintenance.php', { 
        action: 'check_status', // 🔥 QUAN TRỌNG: Fix lỗi "Invalid action"
        path: checkPath 
    }, function(res) {
        
        // Nếu Server báo đang bảo trì
        if (res.is_active) {
            // Nếu là ElevenLabs và được quyền Backup -> Vẫn cho chạy
            if (currentProvider === 'elevenlabs' && typeof backupEligible !== 'undefined' && backupEligible) {
                proceedWithTTS(); 
            } else {
                showToast(`⚠️ ${currentProvider === 'minimax' ? 'Minimax' : 'ElevenLabs'} đang bảo trì. Vui lòng chọn provider khác!`);
                resetUI();
            }
            return;
        }
        
        // ✅ Server hoạt động bình thường -> Chạy tiếp
        proceedWithTTS();
        
    }, 'json').fail(function(xhr, status, error) {
        // Nếu lỗi kết nối check maintenance, vẫn cho chạy liều (fallback)
        console.warn('⚠️ Lỗi kiểm tra bảo trì:', error);
        proceedWithTTS();
    });
}

// ✅ HÀM XỬ LÝ TTS CHÍNH
function proceedWithTTS() {
    let text = $('#txtInput').val();
    let voiceId = $('#voiceIdVal').val();
    
    // 🔥 CHECK ĐÚNG CHECKBOX THEO PROVIDER
    let with_transcript = (currentProvider === 'minimax' 
        ? $('#minimaxSubtitleCheck').is(':checked') 
        : $('#subtitleCheck').is(':checked')) || (window.isSrtFile === true);
    
    let params = {
        action: 'create_speech',
        provider: currentProvider,
        text: text,
        voice_id: voiceId,
        voice_name: $('#selectedVoiceName').text(),
        with_transcript: with_transcript
    };
    
    // 🔥 QUAN TRỌNG: KIỂM TRA GENAI BACKUP
    if (currentProvider === 'elevenlabs' && 
        typeof elevenlabsDown !== 'undefined' && elevenlabsDown && 
        typeof backupEligible !== 'undefined' && backupEligible) {
        
        params.use_genai_backup = true;
        console.log('🟢 USING BACKUP MODE');
    }
    
    if (currentProvider === 'minimax') {
        params.model_id = selectedMinimaxModel || 'speech-01';
        params.vol = $('#vol').val();
        params.speed = $('#speed').val();
        params.pitch = $('#pitch').val();
        params.language_boost = selectedLanguage;
    } else {
        // --- XỬ LÝ MODEL ID AN TOÀN ---
        let currentModelName = $('#selectedModelName').text();
        let modelsList = loadedModels.elevenlabs || [];
        let model = modelsList.find(m => currentModelName.includes(m.name));
        
        if (model) {
            params.model_id = model.id;
        } else if (modelsList.length > 0) {
            params.model_id = modelsList[0].id;
        } else {
            params.model_id = 'eleven_multilingual_v2'; // Fallback cứng
        }
        // ------------------------------
        
        params.speed = $('#elevenSpeed').val();
        params.stability = $('#stability').val() / 100;
        params.similarity = $('#similarity').val() / 100;
        params.style = $('#style').val() / 100;
        params.use_boost = $('#boostCheck').is(':checked');
    }

    // 🔥 TẠO TEMP TASK ID ĐỂ HIỂN THỊ TRƯỚC KHI CÓ KẾT QUẢ
    let tempTaskId = 'temp_' + Date.now();
    let estimatedCost = calculateSingleCost(text);

    // 🔥 UI LOADING
    $('#btnProcess').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> <span>Đang gửi...</span>');
    $('#inputLoader').addClass('show');

    // 🔥 GỬI REQUEST
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        data: params,
        dataType: 'json',
        timeout: 20000, // Tăng timeout lên 20s cho chắc
        success: function(res) {
            if (res.status === 'success') {
                // 1. Cập nhật số dư
                let currentBalance = parseInt($('#userCredits').text().replace(/,/g, ''));
                let newBalance = res.new_balance || (currentBalance - res.credit_cost);
                $('#userCredits').text(newBalance.toLocaleString());
                
                // 2. Xử lý kết quả trả về
                // TH1: Vào hàng đợi (Backup Mode)
                if (res.queue_id && res.history_id) {
                    console.log('🔄 Using Backup - History ID:', res.history_id, '| Queue ID:', res.queue_id);
                    
                    addPendingCard(res.history_id, text.substring(0, 100) + '...', 0, 'elevenlabs', res.character_count);
                    startQueuePolling(res.history_id, res.queue_id);
                    showToast('✅ Đã thêm vào hàng đợi (Miễn phí)');
                } 
                // TH2: Xử lý trực tiếp (Direct)
                else if (res.task_id) {
                    console.log('✅ Direct processing - Task ID:', res.task_id);
                    
                    addPendingCard(res.task_id, text.substring(0, 100) + '...', res.credit_cost, currentProvider);
                    startPolling(res.task_id);
                    showToast('✅ Đang xử lý...');
                }
                
                switchTab('history');
                resetUI();
            } else {
                alert('Lỗi: ' + res.message);
                resetUI();
            }
        },
        error: function(xhr, status, error) {
            // 🔥 NẾU TIMEOUT: TẠO CARD GIẢ VÀ POLL TÌM TASK
            if (status === 'timeout') {
                console.warn('⏰ Request timeout, creating pending card...');
                
                // Trừ tiền tạm thời trên giao diện
                let currentBalance = parseInt($('#userCredits').text().replace(/,/g, ''));
                let newBalance = currentBalance - estimatedCost;
                $('#userCredits').text(newBalance.toLocaleString());
                
                addPendingCard(tempTaskId, text.substring(0, 100) + '...', estimatedCost, currentProvider);
                
                // Poll để tìm task thật (Hy vọng server vẫn xử lý xong)
                setTimeout(() => {
                    pollForNewTask(tempTaskId, text);
                }, 3000);
                
                switchTab('history');
                resetUI();
                showToast('⏳ Yêu cầu đang xử lý ngầm, vui lòng chờ...');
            } else {
                let errorMsg = 'Lỗi kết nối';
                try {
                    let errRes = JSON.parse(xhr.responseText);
                    if(errRes.message) errorMsg = errRes.message;
                } catch(e){}
                
                alert('❌ ' + errorMsg);
                resetUI();
            }
        }
    });
}
function updateEstimatedCost() {
    let text = $('#txtInput').val() || ""; 
    let charCount = text.length;
    
    // Tính toán chi phí cơ bản
    let cost = 0;
    if (charCount > 0) {
        cost = calculateSingleCost(text);
    }
    
    let isGenAIBackup = (typeof elevenlabsDown !== 'undefined' && elevenlabsDown && 
                         typeof backupEligible !== 'undefined' && backupEligible);

    if (currentProvider === 'minimax') {
        $('#minimax-cost-ui').attr('style', 'display: block !important');
        $('#elevenlabs-cost-ui').attr('style', 'display: none !important');

        // ============================================================
        // 🔍 KIỂM TRA GIỌNG CLONE
        // ============================================================
        let isClone = false;
        let voiceId = $('#voiceIdVal').val();
        
        // 1. Check Tab
        if (typeof currentVoiceTab !== 'undefined' && currentVoiceTab === 'cloned') {
            isClone = true;
        }
        
        // 2. Check tên hiển thị
        if (!isClone) {
            let voiceName = $('#selectedVoiceName').text().toLowerCase();
            if (voiceName.includes('clone') || voiceName.includes('(clone)')) {
                isClone = true;
            }
        }
        
        // 3. Check dữ liệu gốc
        if (!isClone && voiceId && typeof loadedVoices !== 'undefined' && loadedVoices.minimax) {
            let voiceObj = loadedVoices.minimax.find(v => v.id == voiceId);
            if (voiceObj) {
                if (voiceObj.source === 'cloned') isClone = true;
                if (voiceObj.tags && Array.isArray(voiceObj.tags)) {
                    if (voiceObj.tags.includes('Clone') || voiceObj.tags.includes('clone')) {
                        isClone = true;
                    }
                }
            }
        }
        
        // ============================================================
        // 🎨 CẬP NHẬT GIAO DIỆN (Làm giống ảnh mẫu)
        // ============================================================
        if (isClone) {
            // Hiển thị badge màu vàng cam, icon cảnh báo
            $('#minimax-badge')
                .html('<i class="bi bi-exclamation-triangle-fill" style="margin-right: 4px;"></i>+15% phí (Giọng Clone)')
                .css({
                    'display': 'flex',
                    'align-items': 'center',
                    'color': '#fbbf24',       // Màu vàng cam như ảnh
                    'font-weight': '600',
                    'margin-left': 'auto',    // Đẩy sang phải nếu container là flex
                    'margin-right': '10px'    // Cách mũi tên một chút
                });
        } else {
            $('#minimax-badge').hide().empty();
        }

        // Cập nhật hiển thị số credits
        $('#estimatedCostDisplay, #minimaxEstimatedCostDisplay').html(`<span id="estimatedCost">${cost.toLocaleString()}</span> credits`);

    } else {
        // --- LOGIC ELEVENLABS ---
        $('#elevenlabs-cost-ui').attr('style', 'display: block !important');
        $('#minimax-cost-ui').attr('style', 'display: none !important');
        $('#minimax-badge').hide(); 

        if (isGenAIBackup) {
            // 🔥 TRƯỜNG HỢP BACKUP: MIỄN PHÍ + DISABLE PHỤ ĐỀ
            $('#estimatedCostDisplay').html('<span class="badge bg-success">Miễn phí (Backup)</span>');
            
            // 1. Disable Checkbox phụ đề
            $('#subtitleCheck').prop('checked', false).prop('disabled', true);
            
            // 2. Làm mờ giao diện toggle để user hiểu là không bấm được
            $('#elevenlabs-cost-ui .toggle-switch').css({
                'opacity': '0.5',
                'pointer-events': 'none' // Chặn click chuột
            });
            
            // 3. Ẩn badge +15% phí đi cho đỡ rối
            $('#elevenlabs-badge').hide();

        } else {
            // 🔥 TRƯỜNG HỢP BÌNH THƯỜNG: HIỆN LẠI
            $('#estimatedCostDisplay').html(`<span id="estimatedCost">${cost.toLocaleString()}</span> credits`);
            
            // 1. Enable lại Checkbox
            $('#subtitleCheck').prop('disabled', false);
            
            // 2. Khôi phục giao diện
            $('#elevenlabs-cost-ui .toggle-switch').css({
                'opacity': '1',
                'pointer-events': 'auto'
            });
            
            // 3. Hiện lại badge phí
            $('#elevenlabs-badge').show();
        }
    }
}
// ✅ RESET UI NHANH HƠN
function resetUI() {
    $('#btnProcess').prop('disabled', false).html('<i class="bi bi-magic"></i> <span>Tạo Giọng Nói</span>');
    $('#inputLoader').removeClass('show');
}
// 🔥 HÀM POLL TÌM TASK MỚI (KHI TIMEOUT)
function pollForNewTask(tempTaskId, originalText) {
    let attempts = 0;
    let maxAttempts = 10;
    
    let interval = setInterval(() => {
        attempts++;
        
        $.post('../../ajaxs/tts.php', { 
            action: 'find_recent_task',
            text_snippet: originalText.substring(0, 50)
        }, function(res) {
            if (res.status === 'success' && res.task_id) {
                // Tìm thấy task thật
                clearInterval(interval);
                
                // Cập nhật card
                $(`#card-${tempTaskId}`).attr('id', `card-${res.task_id}`);
                $(`#status-${tempTaskId}`).attr('id', `status-${res.task_id}`);
                
                // Bắt đầu poll thật
                startPolling(res.task_id);
                
                console.log('✅ Found real task:', res.task_id);
            }
        }, 'json').fail(function() {
            // Tiếp tục thử
        });
        
        if (attempts >= maxAttempts) {
            clearInterval(interval);
            // Mark as failed
            updateCardToFailed(tempTaskId);
        }
    }, 3000);
}

// 🔥 TOAST HELPER
function showToast(msg) {
    // Remove existing toast
    $('.custom-toast').remove();
    
    let toast = $('<div class="custom-toast">').css({
        position: 'fixed',
        bottom: '24px',
        left: '50%',
        transform: 'translateX(-50%)',
        background: '#1a1a1a',
        color: 'white',
        padding: '12px 24px',
        borderRadius: '8px',
        zIndex: 99999,
        fontSize: '13px',
        fontWeight: '500',
        boxShadow: '0 4px 12px rgba(0,0,0,0.4)',
        border: '1px solid #333'
    }).text(msg);
    
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(300, () => toast.remove()), 2500);
}
// Biến toàn cục để lưu trữ các interval đang chạy (nếu cần)
// let pollingIntervals = {}; 

// 🔥 HÀM POLLING & ĐỒNG HỒ CHO SIDEBAR (ĐÃ SỬA: HIỆN THỜI GIAN THAY VÌ %)
function startPolling(taskId) {
    let attempts = 0;
    const maxAttempts = 300; // Tăng lên 300 (khoảng 15 phút) để backup chạy thoải mái

    // Polling API mỗi 3 giây
    let interval = setInterval(() => {
        // Kiểm tra nếu card không còn tồn tại hoặc đã xử lý xong thì dừng
        let $card = $(`#card-${taskId}`);
        if ($card.length === 0 || !$card.hasClass('processing')) {
            clearInterval(interval);
            return;
        }

        // --- 🕒 LOGIC TÍNH THỜI GIAN (MỚI) ---
        let startTime = parseInt($card.attr('data-start-time'));
        if (isNaN(startTime)) startTime = Date.now();
        
        let elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
        let timeString = formatTime(elapsedSeconds); 
        // -------------------------------------

        attempts++;

        // Kiểm tra Timeout
        if (attempts >= maxAttempts) {
            clearInterval(interval);
            updateCardToFailed(taskId);
            $(`#time-elapsed-${taskId}`).text('Timeout');
            return;
        }

        $.ajax({
            url: '../../ajaxs/tts2.php',
            method: 'POST',
            data: { action: 'check_status', task_id: taskId },
            dataType: 'json',
            timeout: 10000,
            success: function(res) {
                // Cập nhật thanh Progress bar (Vẫn giữ thanh loading chạy cho đẹp)
                let percent = res.progress || 0;
                $(`#progress-${taskId}`).css('width', (percent > 0 ? percent : 100) + '%'); // Nếu 0% thì cứ full cây loading effect
                $(`#progress-${taskId}`).attr('data-progress', percent);

                // TRƯỜNG HỢP: ĐANG CHẠY
                if (res.status === 'doing' || res.task_status === 'processing' || res.status === 'pending') {
                    // 🔥 THAY ĐỔI Ở ĐÂY: Luôn hiện thời gian thay vì %
                    $(`#time-elapsed-${taskId}`).html(`<i class="bi bi-clock"></i> ${timeString}`);
                }
                
                // TRƯỜNG HỢP: ĐANG CHỜ HÀNG ĐỢI
                else if (res.status === 'queued') {
                    let queueText = res.queue_position ? `Hàng đợi #${res.queue_position}` : 'Đang chờ...';
                    $(`#time-elapsed-${taskId}`).text(queueText);
                }

                // TRƯỜNG HỢP: HOÀN THÀNH
                else if (res.status === 'done' || res.task_status === 'done') {
                    clearInterval(interval);
                    
                    // Cập nhật UI hoàn thành
                    $(`#progress-${taskId}`).css('width', '100%');
                    
                    $(`#icon-spin-${taskId}`)
                        .removeClass('spinning bi-arrow-repeat')
                        .addClass('bi-check-circle-fill');
                        
                    $(`#time-elapsed-${taskId}`).text('Hoàn thành');
                    $card.removeClass('processing');
                    
                    // Lấy dữ liệu file
                    let audio = res.audio_url || (res.metadata ? res.metadata.audio_url : null);
                    let srt = res.srt_url || (res.metadata ? res.metadata.srt_url : null);
                    let json = res.json_url || (res.metadata ? res.metadata.json_url : null);

                    // Hiển thị Player
                    setTimeout(() => {
                        $(`#track-${taskId}`).fadeOut(); 
                        updateCardToDone(taskId, audio, srt, json);
                    }, 500);
                }
                
                // TRƯỜNG HỢP: LỖI
                else if (res.status === 'error' || res.task_status === 'failed') {
                    clearInterval(interval);
                    
                    $(`#track-${taskId}`).hide();
                    $(`#icon-spin-${taskId}`)
                        .removeClass('spinning bi-arrow-repeat')
                        .addClass('bi-exclamation-triangle-fill');
                        
                    $(`#time-elapsed-${taskId}`).text('Thất bại'); 
                    $card.removeClass('processing');
                        
                    updateCardToFailed(taskId);
                }
            },
            error: function() {
                console.log('Network glitch, retrying...');
                // Nếu lỗi mạng, vẫn cập nhật đồng hồ đếm giờ
                $(`#time-elapsed-${taskId}`).html(`<i class="bi bi-clock"></i> ${timeString}`);
            }
        });
    }, 3000);
}

// ========== SSE PROGRESS STREAM ==========
function startSSEProgress(historyId, genaiTaskId) {
    console.log('🔔 Starting SSE for task:', genaiTaskId);
    
    const eventSource = new EventSource(`../../ajaxs/genai_progress_stream.php?task_id=${genaiTaskId}`);
    
    eventSource.addEventListener('progress', (e) => {
        const data = JSON.parse(e.data);
        const progress = data.progress;
        
        console.log(`🔔 SSE Progress: ${progress}%`);
        
        $(`#status-${historyId}`)
            .text(`Xử lý ${progress}%`)
            .addClass('status-pending');
        
        $(`#progress-${historyId}`).css('width', progress + '%');
    });
    
    eventSource.addEventListener('complete', (e) => {
        console.log('✅ SSE Complete');
        eventSource.close();
        
        // Gọi API để lấy kết quả cuối
        checkHistoryComplete(historyId);
    });
    
    eventSource.addEventListener('error', (e) => {
        console.error('❌ SSE Error:', e);
        eventSource.close();
        
        // Fallback to polling
        startQueuePolling(historyId, 0);
    });
    
    // Auto cleanup sau 2 phút
    setTimeout(() => {
        eventSource.close();
    }, 120000);
}

function checkHistoryComplete(historyId) {
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        data: { 
            action: 'check_history_status',
            history_id: historyId
        },
        success: function(res) {
            if (res.status === 'done') {
                updateCardToDone(historyId, res.audio_url, res.srt_url, res.json_url);
                showToast('✅ Hoàn thành!');
            }
        }
    });
}

// ========== QUEUE POLLING (CHO GENAI BACKUP) ==========
function startQueuePolling(historyId, queueId) {
    let attempts = 0;
    let maxAttempts = 120; // 10 phút
    let pollInterval = 5000; // Mặc định 5s
    
    const poll = () => {
        attempts++;
        
        if (attempts >= maxAttempts) {
            console.warn('⏰ Queue polling timeout:', historyId);
            $(`#status-${historyId}`)
                .text('Timeout - Vui lòng refresh')
                .removeClass('status-queued status-pending')
                .addClass('status-failed');
            return;
        }
        
        $.ajax({
            url: '../../ajaxs/tts.php',
            method: 'POST',
            data: { 
                action: 'check_history_status',
                history_id: historyId
            },
            dataType: 'json',
            timeout: 5000,
            success: function(res) {
                console.log(`📊 History Status [${attempts}]:`, res);
                
                let progress = res.progress || 0;
                let queuePosition = res.queue_position;
                let totalSlots = res.total_slots || 60;
                let processingCount = res.processing_count || 0;
                
                // ✅ ĐANG CHỜ QUEUE
                if (res.status === 'queued') {
                    let statusText = '';
                    
                    if (queuePosition) {
                        // Tính thời gian ước tính (mỗi task ~30s)
                        let estimatedMinutes = Math.ceil((queuePosition * 30) / 60);
                        
                        statusText = `Vị trí #${queuePosition} (≈${estimatedMinutes} phút)`;
                    } else {
                        statusText = 'Đang chờ xử lý...';
                    }
                    
                    $(`#status-${historyId}`)
                        .text(statusText)
                        .removeClass('status-pending')
                        .addClass('status-queued');
                    
                    pollInterval = 5000; // Poll chậm
                    setTimeout(poll, pollInterval);
                }
                
                // ✅ ĐANG XỬ LÝ
                else if (res.status === 'pending') {
                    let statusText = progress > 0 
                        ? `Xử lý ${progress}%` 
                        : 'Đang xử lý...';
                    
                    $(`#status-${historyId}`)
                        .text(statusText)
                        .removeClass('status-queued')
                        .addClass('status-pending');
                    
                    // Update progress bar nếu có
                    if (progress > 0) {
                        $(`#progress-${historyId}`).css('width', progress + '%');
                    }
                    
                    pollInterval = 2000; // Poll nhanh
                    setTimeout(poll, pollInterval);
                }
                
                // ✅ HOÀN THÀNH
                else if (res.status === 'done') {
                    updateCardToDone(historyId, res.audio_url, res.srt_url, res.json_url);
                    showToast('✅ Hoàn thành!', 'success');
                    // STOP polling
                }
                
                // ✅ THẤT BẠI
                else if (res.status === 'failed') {
                    updateCardToFailed(historyId);
                    let errorMsg = res.error || 'Lỗi không xác định';
                    showToast('❌ Thất bại: ' + errorMsg, 'error');
                    // STOP polling
                }
            },
            error: function(xhr, status, error) {
                console.log('⚠️ Network error, retrying...', error);
                setTimeout(poll, 5000);
            }
        });
    };
    
    // Start polling ngay lập tức
    poll();
}
// ========== CLONE VOICE ==========
function submitCloneVoice() {
    let name = $('#cloneName').val().trim();
    let fileInput = $('#cloneFile')[0].files[0];
    let gender = $('#cloneGender').val();

    if (!name) {
        alert('Vui lòng nhập tên giọng!');
        return;
    }
    
    if (!fileInput) {
        alert('Vui lòng chọn file MP3!');
        return;
    }
    
    if (fileInput.type !== 'audio/mpeg' && !fileInput.name.endsWith('.mp3')) {
        alert('Chỉ hỗ trợ file .mp3');
        return;
    }
    
    if (fileInput.size > 20 * 1024 * 1024) {
        alert('File quá lớn! Tối đa 20MB');
        return;
    }

    let formData = new FormData();
    formData.append('action', 'create_clone');
    formData.append('voice_name', name);
    formData.append('gender', gender);
    formData.append('file', fileInput);

    $('#btnSubmitClone').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> <span>Đang upload...</span>');

    $.ajax({
        url: '../../ajaxs/voice_cloning.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                alert('✅ Clone thành công! Giọng mới đã được thêm vào thư viện.');
                $('#cloneModal').fadeOut();
                loadResources();
                
                // Reset form
                $('#cloneName').val('');
                $('#cloneFile').val('');
            } else {
                alert('❌ Lỗi: ' + res.message);
            }
            $('#btnSubmitClone').prop('disabled', false).html('<i class="bi bi-mic"></i> <span>Bắt đầu Clone</span>');
        },
        error: function() {
            alert('❌ Lỗi kết nối server');
            $('#btnSubmitClone').prop('disabled', false).html('<i class="bi bi-mic"></i> <span>Bắt đầu Clone</span>');
        }
    });
}

// ========== HISTORY ==========
function loadHistory() {
    if (isLoadingHistory || !hasMoreHistory) return;
    
    isLoadingHistory = true;
    $('#loadingMore').show();
    
    $.post('../../ajaxs/tts.php', {
        action: 'get_history',
        page: Math.floor(currentOffset / 15) + 1,
        limit: 15
    }, function(res) {
        if (res.status === 'success') {
            if (currentOffset === 0) $('#historyListContainer').empty();
            
            if (res.data.length === 0 && currentOffset === 0) {
                $('#historyListContainer').html(`
                    <div style="text-align:center; padding:60px 20px; color:#666;">
                        <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                        <p style="font-size:14px;">Chưa có lịch sử nào</p>
                    </div>
                `);
                $('#loadingMore').hide();
                $('#noMoreData').hide();
                hasMoreHistory = false;
                isLoadingHistory = false;
                return;
            }
            
            res.data.forEach(item => {
                let createdTimeMs = new Date(item.created_at).getTime();
                historyDataMap[item.task_id] = item;
                let textPreview = item.text_input;
                if (textPreview.length > 100) {
                    textPreview = textPreview.substring(0, 100) + '...';
                }
                
                addHistoryCard(item.task_id, textPreview, item.credit_cost, item.created_at, item.provider, item.status, true, createdTimeMs);
                
                if (item.status === 'pending') {
                    startPolling(item.task_id);
                } else if (item.status === 'done') {
                    updateCardToDone(item.task_id, item.audio_url, item.srt_url, item.json_url);
                } else if (item.status === 'failed') {
                    updateCardToFailed(item.task_id);
                }
            });
            
            currentOffset += res.data.length;
            hasMoreHistory = res.pagination.has_more;
            
            if (!hasMoreHistory) {
                $('#noMoreData').show();
            }
        }
        
        $('#loadingMore').hide();
        isLoadingHistory = false;
    }, 'json').fail(function() {
        $('#loadingMore').hide();
        isLoadingHistory = false;
    });
}

function setupInfiniteScroll() {
    $('#viewHistory').on('scroll', function() {
        let scrollTop = $(this).scrollTop();
        let scrollHeight = $(this)[0].scrollHeight;
        let clientHeight = $(this).height();
        
        if (scrollTop + clientHeight >= scrollHeight - 100) {
            loadHistory();
        }
    });
}

function setupAudioEvents() {
    // 1. Khi đang chạy (Update Progress)
    mainAudio.addEventListener('timeupdate', function() {
        if (currentPlayingTaskId) {
            let currentTime = mainAudio.currentTime;
            let duration = mainAudio.duration;
            
            if (isNaN(duration)) return;

            let progress = (currentTime / duration) * 100;
            let timeString = formatTime(currentTime); // + ' / ' + formatTime(duration);

            // ✅ Cập nhật Sidebar (Cũ)
            $(`#progress-${currentPlayingTaskId}`).css('width', progress + '%');
            $(`#time-current-${currentPlayingTaskId}`).text(timeString);

            // ✅ Cập nhật Modal Chi tiết (Mới) - Tìm theo ID có tiền tố dh-
            $(`#dh-progress-${currentPlayingTaskId}`).css('width', progress + '%');
            $(`#dh-timer-${currentPlayingTaskId}`).text(timeString + ' / ' + formatTime(duration));
        }
    });

    // 2. Khi Play (Đổi icon Play -> Pause)
    mainAudio.addEventListener('play', function() {
        if (currentPlayingTaskId) {
            // Đổi icon Sidebar
            $(`#play-btn-${currentPlayingTaskId}`).html('<i class="bi bi-pause-fill"></i>');
            // Đổi icon Modal
            $(`#dh-play-btn-${currentPlayingTaskId}`).html('<i class="bi bi-pause-fill"></i>');
        }
    });

    // 3. Khi Pause hoặc Kết thúc (Đổi icon Pause -> Play)
    ['pause', 'ended'].forEach(event => {
        mainAudio.addEventListener(event, function() {
            if (currentPlayingTaskId) {
                // Reset icon Sidebar
                $(`#play-btn-${currentPlayingTaskId}`).html('<i class="bi bi-play-fill"></i>');
                // Reset icon Modal
                $(`#dh-play-btn-${currentPlayingTaskId}`).html('<i class="bi bi-play-fill"></i>');
                
                if(event === 'ended') currentPlayingTaskId = null;
            }
        });
    });
}
function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    let mins = Math.floor(seconds / 60);
    let secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}
// ========== DELETE TASK WITH API REFUND ==========
function deleteTask(taskId, originalCost) {
    let currentProgress = parseInt($(`#progress-${taskId}`).attr('data-progress') || 0);
    let cardStatus = $(`#card-${taskId}`).hasClass('processing') ? 'processing' : 'done';
    
    console.log('🔍 DELETE DEBUG:', {
        'taskId': taskId,
        'originalCost': originalCost,
        'currentProgress': currentProgress,
        'cardStatus': cardStatus
    });
    
    // Disable buttons
    $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm"></span>');
    
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        data: { 
            action: 'delete_task_with_refund',
            task_id: taskId,
            current_progress: currentProgress,
            original_cost: originalCost
        },
        dataType: 'json',
        success: function(res) {
            console.log('✅ DELETE RESPONSE:', res);
            
            if (res.status === 'success') {
                let refundAmount = res.refund_credits || 0;
                
                // Cập nhật credits
                if (refundAmount > 0) {
                    let currentBalance = parseInt($('#userCredits').text().replace(/[^0-9]/g, ''));
                    let newBalance = currentBalance + refundAmount;
                    $('#userCredits').text(newBalance.toLocaleString());
                    
                    showToast(`✅ Đã xóa task và hoàn ${refundAmount} credits`);
                } else {
                    showToast('✅ Đã xóa task');
                }
                
                // Xóa khỏi Sidebar
                $(`#card-${taskId}`).fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Xóa khỏi Modal Chi tiết
                $(`#row-${taskId}`).fadeOut(300, function() {
                    $(this).remove();
                    
                    if (typeof updateBulkActions === 'function') {
                        updateBulkActions();
                    }
                });
            } else {
                alert('❌ Lỗi: ' + res.message);
                $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
                    .prop('disabled', false)
                    .html('<i class="bi bi-trash"></i>');
            }
        },
        error: function(xhr) {
            console.error('❌ DELETE ERROR:', xhr.responseText);
            alert('❌ Lỗi kết nối server');
            $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
                .prop('disabled', false)
                .html('<i class="bi bi-trash"></i>');
        }
    });
}
function addPendingCard(taskId, textPreview, cost, provider, charCount) { 
    console.log('🔥 ADD PENDING CARD:', taskId);

    if ($(`#card-${taskId}`).length > 0) return;

    // ✅ TẠO DẤU THỜI GIAN
    let now = new Date();
    let d = String(now.getDate()).padStart(2, '0');
    let m = String(now.getMonth() + 1).padStart(2, '0');
    let y = now.getFullYear();
    let H = String(now.getHours()).padStart(2, '0');
    let i = String(now.getMinutes()).padStart(2, '0');
    let timeString = `${d}/${m}/${y} ${H}:${i}`; 
    let startTimeMs = now.getTime(); // <-- Dấu thời gian số (Cần lưu)

    let badgeStyle = "background: #ffffff; color: #000000; border: 1px solid #000000; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;";
    let costBadge = cost ? `<span class="hc-cost" style="${badgeStyle}">${cost}</span>` : '';
    let logoUrl = getProviderLogo(provider);

    let html = `
    <div class="history-card processing" id="card-${taskId}" data-start-time="${startTimeMs}">
        
        <div class="hc-header" style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #222; padding-bottom: 8px;">
            <span class="hc-time" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #888;">
                <img src="${logoUrl}" class="hc-provider-icon" title="${provider}" style="width: 18px; height: 18px; border-radius: 50%;">
                
                <i class="bi bi-arrow-repeat spinning" id="icon-spin-${taskId}" style="font-size: 14px;"></i>
                
                <span style="font-weight: 500;">${timeString}</span>
            </span>

            <div style="display: flex; gap: 8px; align-items: center;">
                ${costBadge}
                <span class="hc-status status-pending" id="status-${taskId}" style="${badgeStyle}">
                    <span id="time-elapsed-${taskId}">0%</span>
                </span>
                
                <button onclick="openDeleteModal('${taskId}', '${textPreview.replace(/'/g, "\\'")}', 'refund', ${cost})" 
    id="btn-delete-${taskId}"
                    style="
                        background: transparent;
                        border: 1px solid #333;
                        color: #888;
                        padding: 4px 8px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 11px;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.borderColor='#ef4444'; this.style.color='#ef4444'"
                    onmouseout="this.style.borderColor='#333'; this.style.color='#888'"
                    title="Xóa task">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <div class="hc-content" style="margin-bottom: 12px; font-size: 14px; line-height: 1.5;">${textPreview}</div>

        <div class="hc-progress-track" id="track-${taskId}">
            <div class="hc-progress-fill" id="progress-${taskId}" data-progress="0"></div>
        </div>

    </div>`;
    
    $('#historyListContainer').prepend(html);
}

// ✅ CHÚ Ý: Phải có "isLoadHistory = false" ở cuối dòng này
function addHistoryCard(taskId, textPreview, cost, time, provider, status, isLoadHistory = false, startTimeMs = Date.now()) {
    
    if ($(`#card-${taskId}`).length > 0) return;

    let badgeStyle = "background: #ffffff; color: #000000; border: 1px solid #000000; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; white-space: nowrap; display: inline-block; min-width: fit-content;";
    let costBadge = cost ? `<span class="hc-cost" style="${badgeStyle}">${cost}</span>` : '';
    let logoUrl = getProviderLogo(provider);

    let progressBarHtml = '';
    let iconClass = '';
    let deleteButtonHtml = '';  
    let statusTextContent = ''; // Nội dung sẽ hiển thị trong status

    // 🔥 1. Gom nhóm trạng thái đang chạy để dùng chung
    let isProcessing = ['pending', 'processing', 'doing', 'queued'].includes(status);

    // 🔥 2. Xử lý text an toàn (Fix lỗi xuống dòng & nháy đơn)
    let safeText = (textPreview || '')
        .replace(/'/g, "\\'")
        .replace(/"/g, '&quot;')
        .replace(/(\r\n|\n|\r)/g, ' '); 

    // 🔥 3. Nút Metadata
    let detailButtonHtml = ``;

    // Logic hiển thị theo trạng thái
    if (isProcessing) {
        iconClass = 'spinning bi-arrow-repeat';
        progressBarHtml = `<div class="hc-progress-track" id="track-${taskId}"><div class="hc-progress-fill" id="progress-${taskId}" data-progress="0"></div></div>`;
        
        // 🔥 [MỚI]: Nội dung đếm ngược/xử lý
        statusTextContent = `<span id="time-elapsed-${taskId}">${status === 'queued' ? 'Đang chờ' : '0:00'}</span>`;
        
        // Nút xóa hoàn tiền
        deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'refund', ${cost})" 
            id="btn-delete-${taskId}"
            style="background: transparent; border: 1px solid #333; color: #888; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa task"><i class="bi bi-trash"></i></button>`;
            
    } else if (status === 'done') {
        iconClass = 'bi-check-circle-fill';
        statusTextContent = 'Hoàn thành';
        
        // Nút xóa lịch sử
        deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'history')" 
            class="btn-delete-history"
            style="background: transparent; border: 1px solid #666; color: #999; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa lịch sử"><i class="bi bi-trash"></i></button>`;
            
    } else if (status === 'failed') {
        iconClass = 'bi-exclamation-triangle-fill';
        statusTextContent = 'Thất bại';
        
        // Nút xóa lịch sử
        deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'history')" 
            class="btn-delete-history"
            style="background: transparent; border: 1px solid #666; color: #999; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa lịch sử"><i class="bi bi-trash"></i></button>`;
            
    } else {
        iconClass = 'bi-clock'; 
        statusTextContent = 'Lỗi trạng thái';
    }

    // 🔥 [FIX]: Thêm class 'processing' nếu biến isProcessing = true
    let html = `
    <div class="history-card ${isProcessing ? 'processing' : ''}" id="card-${taskId}" data-start-time="${startTimeMs}">
        <div class="hc-header" style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #222; padding-bottom: 8px;">
            <span class="hc-time" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #888;">
                <img src="${logoUrl}" class="hc-provider-icon" title="${provider}" style="width: 18px; height: 18px; border-radius: 50%;">
                <i class="bi ${iconClass}" id="icon-spin-${taskId}" style="font-size: 14px;"></i> 
                <span>${time || 'Vừa xong'}</span>
            </span>
            <div style="display: flex; gap: 8px; align-items: center;">
                ${costBadge}
                <span class="hc-status status-${status}" id="status-${taskId}" style="${badgeStyle}">
                    ${statusTextContent}
                </span>
                
                <div style="display: flex; gap: 4px;">
                    ${detailButtonHtml}
                    ${deleteButtonHtml}
                </div>

            </div>
        </div>
        <div class="hc-content" style="margin-bottom: 12px; font-size: 14px; line-height: 1.5;">${textPreview}</div>
        ${progressBarHtml}
    </div>`;
    
    // Logic chèn vào danh sách
    if (isLoadHistory) {
        $('#historyListContainer').append(html); 
    } else {
        $('#historyListContainer').prepend(html); 
    }
    
    // 🔥 [MỚI] Nếu là tác vụ Tải lại từ Server và đang chạy, phải gọi Polling
    if (isLoadHistory && isProcessing) {
        // startPolling sẽ được gọi ở hàm loadHistory (chúng ta không cần gọi ở đây nữa)
    }
}
// ========== XÓA LỊCH SỬ TASK (FIX LỖI MISSING ID) ==========
function deleteHistoryTask(taskId) {
    console.log("🔴 Executing deleteHistoryTask for:", taskId);

    if (!taskId) {
        console.error("❌ Error: Missing Task ID");
        return;
    }

    // Disable button
    $(`#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`)
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm"></span>');
    
    $.ajax({
        url: '../../ajaxs/tts.php',
        method: 'POST',
        data: { 
            action: 'delete_history',
            task_id: taskId
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('✅ Đã xóa khỏi lịch sử');
                
                // 1. Xóa khỏi Sidebar
                $(`#card-${taskId}`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check empty state
                    if ($('.history-card').length === 0) {
                        $('#historyListContainer').html(`
                            <div style="text-align:center; padding:60px 20px; color:#666;">
                                <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                                <p style="font-size:14px;">Chưa có lịch sử nào</p>
                            </div>
                        `);
                    }
                });
                
                // 2. Xóa khỏi Modal Chi tiết
                $(`#row-${taskId}`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check empty state trong modal
                    if ($('#detailedHistoryList .dh-row').length === 0) {
                        $('#detailedHistoryList').html(`
                            <div style="text-align:center; padding:60px 20px; color:#666;">
                                <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                                <p style="font-size:14px;">Không còn lịch sử nào</p>
                            </div>
                        `);
                    }
                    
                    // Cập nhật bulk actions
                    if (typeof updateBulkActions === 'function') {
                        updateBulkActions();
                    }
                });
            } else {
                alert('❌ Lỗi API: ' + res.message);
                $(`#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`)
                    .prop('disabled', false)
                    .html('<i class="bi bi-trash"></i>');
            }
        },
        error: function(xhr) {
            console.error('❌ DELETE ERROR:', xhr.responseText);
            alert('❌ Lỗi kết nối server');
            $(`#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`)
                .prop('disabled', false)
                .html('<i class="bi bi-trash"></i>');
        }
    });
}
function updateCardToDone(taskId, audioUrl, srtUrl, jsonUrl) {
    // 1. Kiểm tra trùng lặp Player trong Sidebar
    if ($(`#card-${taskId} .hc-player`).length > 0) {
        return; 
    }
    
    // 2. Cập nhật Sidebar Card (code cũ giữ nguyên)
    $(`#card-${taskId}`).removeClass('processing');
    
    let $statusElement = $(`#card-${taskId} #status-${taskId}`);
    if ($statusElement.length) {
        $statusElement.text('Xong')
            .removeClass('status-pending status-queued')
            .addClass('status-done');
            
        $(`#time-elapsed-${taskId}`).replaceWith('Xong');
    }

    $(`#icon-spin-${taskId}`)
        .removeClass('spinning bi-arrow-repeat')
        .addClass('bi-check-circle-fill');

    let $deleteBtn = $(`#btn-delete-${taskId}`);
    if ($deleteBtn.length) {
        $deleteBtn
            .css({
                'border-color': '#666',
                'color': '#999'
            })
            .attr('title', 'Xóa lịch sử đã hoàn thành')
            .attr('onclick', `openDeleteModal('${taskId}', 'Nội dung preview', 'history')`)
            .prop('disabled', false);
    }

    // 3. Xây dựng Player HTML
    let playerHtml = `
    <div class="hc-player">
        <button class="hc-play-btn" id="play-btn-${taskId}" onclick="playAudio('${taskId}', '${audioUrl}')">
            <i class="bi bi-play-fill"></i>
        </button>
        <div class="hc-progress-container">
            <div class="hc-progress" onclick="seekAudio(event, '${taskId}')">
                <div class="hc-progress-bar" id="progress-${taskId}" style="width: 0%"></div>
            </div>
            <div class="hc-time-display">
                <span id="time-current-${taskId}">0:00</span>
                <span id="time-total-${taskId}">0:00</span>
            </div>
        </div>
        <div class="hc-actions">
            <a href="${audioUrl}" download class="hc-action-btn" title="Tải Audio">
                <i class="bi bi-download"></i>
            </a>
            ${srtUrl ? `<a href="${srtUrl}" download class="hc-action-btn" title="Tải SRT"><i class="bi bi-file-earmark-text"></i></a>` : ''}
            ${jsonUrl ? `<a href="${jsonUrl}" download class="hc-action-btn" title="Tải JSON"><i class="bi bi-file-earmark-code"></i></a>` : ''}
        </div>
    </div>`;
    
    $(`#card-${taskId}`).append(playerHtml);
    
    $(`#track-${taskId}`).fadeOut(300, function() {
        $(this).remove();
    });
    
    // 🔥 [THÊM MỚI] Đồng bộ sang Modal Chi tiết
    syncDetailedHistoryCard(taskId, 'done', audioUrl, srtUrl, jsonUrl, null);
}
function updateCardToFailed(taskId) {
    $(`#card-${taskId}`).removeClass('processing');
    
    $(`#card-${taskId} #status-${taskId}`)
        .text('Thất bại')
        .removeClass('status-pending')
        .addClass('status-failed');
    
    // 🔥 [THÊM MỚI] Đồng bộ sang Modal Chi tiết
    syncDetailedHistoryCard(taskId, 'failed', null, null, null, null);
}
function playAudio(taskId, url) {
    if (currentPlayingTaskId === taskId && !mainAudio.paused) {
        mainAudio.pause();
        $(`#play-btn-${taskId}`).html('<i class="bi bi-play-fill"></i>');
        currentPlayingTaskId = null;
    } else {
        if (currentPlayingTaskId) {
            $(`#play-btn-${currentPlayingTaskId}`).html('<i class="bi bi-play-fill"></i>');
        }
        
        currentPlayingTaskId = taskId;
        mainAudio.src = url;
        mainAudio.play();
        $(`#play-btn-${taskId}`).html('<i class="bi bi-pause-fill"></i>');
    }
}

// Thêm tham số isModal vào cuối
function seekAudio(event, taskId, isModal = false) {
    if (mainAudio.src && mainAudio.duration) {
        let progressBar = event.currentTarget;
        let clickX = event.offsetX;
        let width = progressBar.offsetWidth;
        let percent = clickX / width;
        
        mainAudio.currentTime = percent * mainAudio.duration;
        
        // Nếu user tua bài khác với bài đang phát, cần chuyển taskId để thanh chạy đúng
        if (currentPlayingTaskId !== taskId) {
             // Logic xử lý nếu cần (thường thì phải bấm play trước mới tua được)
        }
    }
}
// ========== BULK UPLOAD ==========
// ========== UPLOAD DROPDOWN ==========
function toggleUploadDropdown() {
    $('#uploadDropdown').toggle();
    updateChevron();
}

function updateChevron() {
    if ($('#uploadDropdown').is(':visible')) {
        $('#uploadChevron').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    } else {
        $('#uploadChevron').removeClass('bi-chevron-down').addClass('bi-chevron-up');
    }
}

$(document).on('click', function(e) {
    if (!$(e.target).closest('#uploadDropdownBtn').length && !$(e.target).closest('#uploadDropdown').length) {
        $('#uploadDropdown').hide();
        updateChevron();
    }
});

// ========== GLOBAL DROP HANDLER ==========
let dragCounter = 0;

$(document).on('dragenter', function(e) {
    e.preventDefault();
    dragCounter++;
    if (dragCounter === 1) {
        $('#globalDropOverlay').css('display', 'flex').hide().fadeIn(200);
    }
});

$(document).on('dragleave', function(e) {
    dragCounter--;
    if (dragCounter === 0) {
        $('#globalDropOverlay').fadeOut(200);
    }
});

$(document).on('dragover', function(e) {
    e.preventDefault();
});

$(document).on('drop', function(e) {
    e.preventDefault();
    dragCounter = 0;
    $('#globalDropOverlay').fadeOut(200);
    
    let files = Array.from(e.originalEvent.dataTransfer.files);
    handleGlobalDrop(files);
});

function handleGlobalDrop(files) {
    if (!hasApiKey) {
        $('#apiKeyModal').fadeIn();
        return;
    }
    
    let validFiles = files.filter(f => {
        let name = f.name.toLowerCase();
        return (name.endsWith('.txt') || name.endsWith('.srt') || name.endsWith('.zip')) && f.size < 5 * 1024 * 1024;
    });
    
    if (validFiles.length === 0) {
        alert('Không có file hợp lệ! Chỉ chấp nhận .txt, .srt, .zip < 5MB');
        return;
    }
    
    // 1 FILE -> Input Area
    if (validFiles.length === 1 && !validFiles[0].name.endsWith('.zip')) {
        let file = validFiles[0];

        // 1. Check SRT & Set Flag
        if (file.name.toLowerCase().endsWith('.srt')) {
            window.isSrtFile = true;
            $('#srtFeeInfo').show();
        } else {
            window.isSrtFile = false;
            $('#srtFeeInfo').hide();
        }

        // 🔥 [MỚI] LƯU THÔNG TIN FILE VÀO BỘ NHỚ
        localStorage.setItem('tts_filename', file.name);
        localStorage.setItem('tts_is_srt', window.isSrtFile); 

        let reader = new FileReader();
        reader.onload = function(e) {
            let currentText = $('#txtInput').val();
            let newContent = currentText + (currentText ? '\n\n' : '') + e.target.result;
            
            // Đổ text vào ô input
            $('#txtInput').val(newContent);
            
            // 🔥 [MỚI] LƯU NỘI DUNG VÀO BỘ NHỚ LUÔN (Đề phòng user chưa gõ gì thêm)
            localStorage.setItem('tts_input_draft', newContent);

            togglePlaceholder(); 
            $('#fileNameDisplay').text(`📂 ${file.name}`).fadeIn();
        };
        reader.readAsText(file);
    }
    // 2+ FILES or ZIP -> Bulk Modal (Giữ nguyên logic cũ)
    else {
        openBulkModal();
        handleBulkFiles(validFiles);
    }
}

// ========== SINGLE FILE UPLOAD ==========
$('#fileInput').on('change', function(e) {
    let files = Array.from(e.target.files);
    handleGlobalDrop(files);
    $(this).val(''); // Reset input
});

$('#folderInput').on('change', function(e) {
    let files = Array.from(e.target.files);
    handleGlobalDrop(files);
    $(this).val(''); // Reset input
});

// ========== BULK UPLOAD ==========
let bulkFiles = [];

function openBulkModal() {
    if (!hasApiKey) {
        $('#apiKeyModal').fadeIn();
        return;
    }
    
    if (!$('#voiceIdVal').val()) {
        alert('⚠️ Vui lòng chọn giọng nói trước!');
        openVoiceModal();
        return;
    }
    
    $('#bulkUploadModal').css('display', 'flex').hide().fadeIn();
    bulkFiles = [];
    $('#bulkFileList').hide();
    $('#bulkSummary').hide();
    $('#btnBulkProcess').hide();
    $('#currentBalance').text($('#userCredits').text() + ' credits');
}

function closeBulkModal() {
    $('#bulkUploadModal').fadeOut();
    bulkFiles = [];
}

// Drag & Drop trong modal
let bulkDropZone = document.getElementById('bulkDropZone');

$('#bulkDropZone').on('click', function() {
    if (!$('#voiceIdVal').val()) {
        alert('⚠️ Vui lòng chọn giọng nói trước!');
        closeBulkModal();
        openVoiceModal();
        return;
    }
    $('#bulkFileInput').click();
});

$('#bulkFileInput').on('change', function(e) {
    handleBulkFiles(Array.from(e.target.files));
    $(this).val(''); // Reset
});

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    bulkDropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

['dragenter', 'dragover'].forEach(eventName => {
    bulkDropZone.addEventListener(eventName, () => {
        $('#bulkDropZone').css('border-color', '#667eea');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    bulkDropZone.addEventListener(eventName, () => {
        $('#bulkDropZone').css('border-color', '#444');
    }, false);
});

bulkDropZone.addEventListener('drop', e => {
    let dt = e.dataTransfer;
    let files = Array.from(dt.files);
    handleBulkFiles(files);
}, false);

async function handleBulkFiles(files) {
    let validFiles = files.filter(f => {
        let name = f.name.toLowerCase();
        return (name.endsWith('.txt') || name.endsWith('.srt') || name.endsWith('.zip')) && f.size < 5 * 1024 * 1024;
    });
    
    if (validFiles.length === 0) {
        alert('Không có file hợp lệ! Chỉ chấp nhận .txt, .srt, .zip < 5MB');
        return;
    }
    
    if (bulkFiles.length + validFiles.length > 20) {
        alert('Tối đa 20 file!');
        return;
    }
    
    // Show loading
    $('#bulkDropZone').html('<div class="spinner-border" style="color: #667eea;"></div><p style="margin-top: 15px; color: #888;">Đang đọc file...</p>');
    
    // Process files
    for (let file of validFiles) {
        if (file.name.endsWith('.zip')) {
            await extractZipFile(file);
        } else {
            await readTextFile(file);
        }
    }
    
    // Reset drop zone
    $('#bulkDropZone').html(`
        <i class="bi bi-cloud-upload" style="font-size: 48px; color: #667eea; display: block; margin-bottom: 16px;"></i>
        <h4 style="margin-bottom: 8px;">Kéo thả file hoặc click để chọn</h4>
        <p style="color: #888; font-size: 13px;">Hỗ trợ: .txt, .srt, .zip (tối đa 20 file, mỗi file < 5MB)</p>
    `);
    
    renderFileList();
    calculateBulkCost();
}

async function extractZipFile(zipFile) {
    return new Promise((resolve, reject) => {
        let reader = new FileReader();
        reader.onload = async function(e) {
            try {
                let zip = await JSZip.loadAsync(e.target.result);
                let filePromises = [];
                
                zip.forEach((relativePath, zipEntry) => {
                    if (!zipEntry.dir && (relativePath.endsWith('.txt') || relativePath.endsWith('.srt'))) {
                        filePromises.push(
                            zipEntry.async('string').then(content => {
                                // 🔥 KHÔNG TRIM
                                bulkFiles.push({
                                    name: relativePath,
                                    content: content,
                                    chars: content.length,
                                    from: zipFile.name
                                });
                            })
                        );
                    }
                });
                
                await Promise.all(filePromises);
                resolve();
            } catch (err) {
                console.error('ZIP extract error:', err);
                alert('Lỗi khi giải nén file ZIP!');
                reject(err);
            }
        };
        reader.readAsArrayBuffer(zipFile);
    });
}
async function readTextFile(file) {
    return new Promise((resolve) => {
        let reader = new FileReader();
        reader.onload = function(e) {
            let content = e.target.result; // 🔥 KHÔNG TRIM
            
            bulkFiles.push({
                name: file.name,
                content: content,
                chars: content.length,
                from: 'upload'
            });
            resolve();
        };
        reader.readAsText(file);
    });
}

function renderFileList() {
    if (bulkFiles.length === 0) {
        $('#bulkFileList').hide();
        return;
    }
    
    $('#bulkFileList').show();
    $('#fileCount').text(bulkFiles.length);
    
    let html = '';
    bulkFiles.forEach((file, index) => {
        let fromBadge = file.from !== 'upload' ? `<span style="font-size: 10px; color: #888; margin-left: 6px;">từ ${file.from}</span>` : '';
        
        html += `
        <div style="
            padding: 12px;
            border-bottom: 1px solid #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
        ">
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${file.name}${fromBadge}
                </div>
                <div style="font-size: 11px; color: #888;">
                    ${file.chars.toLocaleString()} ký tự
                </div>
            </div>
            <button onclick="removeFile(${index})" style="
                background: transparent;
                border: 1px solid #333;
                color: #888;
                padding: 6px 10px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
            ">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    });
    
    $('#fileListContainer').html(html);
}

function removeFile(index) {
    bulkFiles.splice(index, 1);
    renderFileList();
    calculateBulkCost();
}

function clearAllFiles() {
    if (confirm('Xóa tất cả file?')) {
        bulkFiles = [];
        renderFileList();
        $('#bulkSummary').hide();
        $('#btnBulkProcess').hide();
    }
}

function calculateBulkCost() {
    if (bulkFiles.length === 0) {
        $('#bulkSummary').hide();
        $('#btnBulkProcess').hide();
        return;
    }

    // ========== BƯỚC 1: TÍNH TỔNG KÝ TỰ ==========
    let totalChars = bulkFiles.reduce((sum, f) => sum + f.chars, 0);

    // ========== BƯỚC 2: LẤY COST_FACTOR & CLONE_MULTIPLIER ==========
    let cost_factor = 1.0;
    let clone_multiplier = 1.0; 

    if (currentProvider === 'minimax') {
        // 2a. Lấy model factor
        let model = loadedModels.minimax.find(m => m.id === selectedMinimaxModel);
        if (model) {
            cost_factor = model.cost_factor || 1.0;
        }

        // 2b. 🔥 KIỂM TRA VOICE CLONE (ĐÃ FIX MẠNH MẼ HƠN)
        let voiceId = $('#voiceIdVal').val();
        let voiceObj = (loadedVoices.minimax || []).find(v => v.id == voiceId); // Dùng == để so sánh cả string/number
        
        // --- DEBUG LOG (Bấm F12 -> Console để xem) ---
        // console.log("🔍 Debug Cost:", { 
        //     provider: currentProvider, 
        //     id: voiceId, 
        //     found: !!voiceObj, 
        //     source: voiceObj?.source, 
        //     tags: voiceObj?.tags 
        // });

        if (voiceObj) {
            // Kiểm tra kỹ hơn: Hoặc source là 'cloned', HOẶC trong tags có chữ 'Clone'
            let isClonedSource = (voiceObj.source === 'cloned');
            let hasCloneTag = (voiceObj.tags && Array.isArray(voiceObj.tags) && voiceObj.tags.includes('Clone'));

            if (isClonedSource || hasCloneTag) {
                clone_multiplier = 1.15;
                console.log("✅ Đã phát hiện giọng Clone -> Áp dụng x1.15");
            }
        }

    } else {
        // ElevenLabs logic
        let currentModelName = $('#selectedModelName').text();
        let model = loadedModels.elevenlabs.find(m => currentModelName.includes(m.name));
        if (model) {
            cost_factor = model.cost_factor || 1.0;
        }
    }

    // ========== BƯỚC 3: CÔNG THỨC TÍNH ==========
    let base_rate = 1.12;
    
    // Checkbox subtitle
    let with_transcript = currentProvider === 'minimax' 
        ? $('#minimaxSubtitleCheck').is(':checked') 
        : $('#subtitleCheck').is(':checked');

    // 🔥 Công thức: Ký tự * 1.12 * Model * Clone (1.15)
    let base_cost = totalChars * base_rate * cost_factor * clone_multiplier;

    // Nếu có transcript -> x1.2 nữa
    if (with_transcript) {
        base_cost *= 1.2;
    }

   // ========== BƯỚC 4: LÀM TRÒN ==========
    let total_cost;
    if (cost_factor < 1.0) {
        total_cost = Math.ceil(base_cost); 
    } else {
        total_cost = Math.floor(base_cost);
    }
    total_cost = Math.max(bulkFiles.length, total_cost);

    // 🔥 [THÊM MỚI] CHECK GENAI BACKUP ĐỂ SET GIÁ VỀ 0
    let isGenAIBackup = (typeof elevenlabsDown !== 'undefined' && elevenlabsDown && 
                         typeof backupEligible !== 'undefined' && backupEligible &&
                         currentProvider === 'elevenlabs');

    if (isGenAIBackup) {
        total_cost = 0; // Miễn phí
    }

    // ========== HIỂN THỊ ==========
    $('#totalChars').text(totalChars.toLocaleString());
    
    // Format hiển thị
    $('#baseCost').text(base_cost.toFixed(0).toLocaleString() + ' credits');
    
    if (isGenAIBackup) {
        $('#estimatedCost').html('<span class="badge bg-success">Miễn phí (Backup)</span>');
        $('#btnBulkProcess').prop('disabled', false).css('opacity', '1');
    } else {
        $('#estimatedCost').text(total_cost.toLocaleString() + ' credits');

        // Logic check số dư cũ
        let currentCredits = parseInt($('#userCredits').text().replace(/,/g, '') || '0');
        if (currentCredits < total_cost) {
            $('#btnBulkProcess').prop('disabled', true).css('opacity', '0.5');
            $('#estimatedCost').css('color', '#ef4444');
        } else {
            $('#btnBulkProcess').prop('disabled', false).css('opacity', '1');
            $('#estimatedCost').css('color', '#fbbf24');
        }
    }

    $('#bulkSummary').show();
    $('#btnBulkProcess').show();

    if (currentProvider === 'elevenlabs') {
        let currentModelName = $('#selectedModelName').text();
        $('#summaryModel').text(currentModelName);
    } else {
        $('#summaryModel').text(selectedMinimaxModel);
    }

    if (with_transcript) {
        $('#summaryTranscript').show();
    } else {
        $('#summaryTranscript').hide();
    }

    setTimeout(() => {
        let scrollArea = document.getElementById('bulkModalScrollArea');
        if (scrollArea) {
            scrollArea.scrollTo({
                top: scrollArea.scrollHeight,
                behavior: 'smooth'
            });
        }
    }, 100);
}
async function processBulkFiles() {
    // 1. Xác nhận trước khi chạy
    if (!confirm(`Xử lý ${bulkFiles.length} file với tổng chi phí ${$('#estimatedCost').text()}?`)) {
        return;
    }
    
    // 2. Khóa nút và hiện loading
    $('#btnBulkProcess').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> <span>Đang xử lý...</span>');
    
    let successCount = 0;
    let failCount = 0;
    
    // 🔥 [QUAN TRỌNG] KIỂM TRA CHẾ ĐỘ BACKUP (Để gửi cờ miễn phí)
    let isGenAIBackup = (typeof elevenlabsDown !== 'undefined' && elevenlabsDown && 
                         typeof backupEligible !== 'undefined' && backupEligible &&
                         currentProvider === 'elevenlabs');

    // 3. Duyệt qua từng file
    for (let i = 0; i < bulkFiles.length; i++) {
        let file = bulkFiles[i];
        
        // Xác định checkbox phụ đề đúng theo provider
        let isSubtitleChecked = (currentProvider === 'minimax') 
            ? $('#minimaxSubtitleCheck').is(':checked') 
            : $('#subtitleCheck').is(':checked');

        let params = {
            action: 'create_speech',
            provider: currentProvider,
            text: file.content,
            voice_id: $('#voiceIdVal').val(),
            voice_name: $('#selectedVoiceName').text() + ` [${file.name}]`,
            with_transcript: isSubtitleChecked
        };
        
        // 🔥 GỬI CỜ BACKUP NẾU CẦN
        if (isGenAIBackup) {
            params.use_genai_backup = true;
        }
        
        // Cấu hình tham số theo Provider
        if (currentProvider === 'minimax') {
            params.model_id = selectedMinimaxModel;
            params.vol = $('#vol').val();
            params.speed = $('#speed').val();
            params.pitch = $('#pitch').val();
            params.language_boost = selectedLanguage;
        } else {
            // ElevenLabs
            let currentModelName = $('#selectedModelName').text();
            let model = loadedModels.elevenlabs.find(m => currentModelName.includes(m.name));
            params.model_id = model ? model.id : (loadedModels.elevenlabs[0]?.id || 'eleven_multilingual_v2');
            
            params.speed = $('#elevenSpeed').val();
            params.stability = $('#stability').val() / 100;
            params.similarity = $('#similarity').val() / 100;
            params.style = $('#style').val() / 100;
            params.use_boost = $('#boostCheck').is(':checked');
        }
        
        try {
            // Gửi request AJAX (dùng await để xử lý tuần tự)
            let res = await $.post('../../ajaxs/tts.php', params).promise();
            
            if (res.status === 'success') {
                successCount++;
                
                // A. TRƯỜNG HỢP BACKUP (QUEUE)
                if (res.queue_id && res.history_id) {
                    // Thêm card vào danh sách
                    addPendingCard(res.history_id, file.content.substring(0, 100) + '...', 0, 'elevenlabs', res.character_count);
                    // Bắt đầu polling hàng đợi
                    if (typeof startQueuePolling === 'function') {
                        startQueuePolling(res.history_id, res.queue_id);
                    }
                } 
                // B. TRƯỜNG HỢP THƯỜNG (DIRECT TASK)
                else if (res.task_id) {
                    addPendingCard(res.task_id, file.content.substring(0, 100) + '...', res.credit_cost, currentProvider);
                    if (typeof startPolling === 'function') {
                        startPolling(res.task_id);
                    }
                }
                
                // Cập nhật số dư (nếu có thay đổi)
                // Nếu là backup thì res.credit_cost = 0, số dư không đổi
                let currentBalance = parseInt($('#userCredits').text().replace(/,/g, ''));
                let newBalance = res.new_balance !== undefined ? res.new_balance : (currentBalance - (res.credit_cost || 0));
                $('#userCredits').text(newBalance.toLocaleString());

            } else {
                failCount++;
                console.error("Task failed:", res.message);
            }
        } catch (err) {
            failCount++;
            console.error("Request error:", err);
        }
        
        // Delay nhẹ 500ms giữa các file để tránh spam server quá nhanh
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    // 4. Kết thúc: Đóng modal, chuyển tab, thông báo
    closeBulkModal();
    switchTab('history');
    
    // Reset lại giao diện upload nếu cần
    if (typeof resetBulkUploadUI === 'function') resetBulkUploadUI();

    let resultMsg = `✅ Hoàn thành quy trình!\n\n- Thành công: ${successCount}\n- Thất bại: ${failCount}`;
    if (isGenAIBackup) {
        resultMsg += `\n\n(Đã sử dụng chế độ Backup miễn phí)`;
    }
    alert(resultMsg);
}
function updateFilterIndicators() {
    $('.filter-group').each(function() {
        let select = $(this).find('.filter-select');
        let value = select.val();
        
        if (value && value !== '' && value !== 'all') {
            $(this).addClass('has-value');
        } else {
            $(this).removeClass('has-value');
        }
    });
}

// Hàm Wrapper để reset setting dựa trên provider hiện tại
function resetCurrentSettings() {
    if (typeof currentProvider !== 'undefined' && currentProvider === 'minimax') {
        resetMinimaxSettings();
        // Thông báo nhẹ
        showToast('Đã đặt lại cài đặt Minimax');
    } else {
        resetElevenLabsSettings();
        showToast('Đã đặt lại cài đặt ElevenLabs');
    }
}

function getProviderLogo(provider) {
    // Chẩn hóa chữ thường để so sánh
    let p = (provider || 'elevenlabs').toLowerCase();
    
    if (p === 'minimax') {
        return 'https://ai33.pro/minimax.png?v=3';
    }
    // Mặc định là ElevenLabs
    return 'https://ai33.pro/11max.png?v=3';
}

// Biến cờ để chặn filterVoices chạy lung tung khi đang tìm ID
let isSearchingServer = false;

// ========================================
// 🔎 SEARCH VOICE BY ID (SERVER-SIDE) - FINAL FIX
// ========================================
function searchVoiceOnServer(voiceId) {
    // 1. Khóa bộ lọc local
    isSearchingServer = true; 

    // 2. Hiển thị UI Loading
    $('#voiceGrid').html(`
        <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
            <div class="spinner-border" style="width:30px; height:30px; color:#667eea;"></div>
            <p style="color:#888; margin-top:15px; font-size:14px;">Đang tìm kiếm ID trên server...</p>
        </div>
    `);

    // 3. Gửi Request
    $.post('../../ajaxs/get_resources.php?action=search_voice_id', {
        voice_id: voiceId
    }, function(res) {
        console.log("🔍 Server Search Result:", res);

        if (res.status === 'success' && res.data) {
            let v = res.data;

            // --- A. XỬ LÝ TAGS (Để hiện viên thuốc màu xám) ---
            let tags = ['ID Lookup']; // Tag đầu tiên
            
            // Lấy gender từ root hoặc labels
            let gender = v.gender || (v.labels ? v.labels.gender : '') || 'Unknown';
            if (gender && gender !== 'unknown') {
                // Viết hoa chữ cái đầu (male -> Male)
                tags.push(gender.charAt(0).toUpperCase() + gender.slice(1));
            }

            // Lấy accent
            let accent = v.accent || (v.labels ? v.labels.accent : '') || '';
            if (accent && accent !== 'neutral') {
                tags.push(accent.charAt(0).toUpperCase() + accent.slice(1));
            }

            // --- B. TẠO OBJECT VOICE CHUẨN (Khớp 100% với createVoiceCardHTML) ---
            let formattedVoice = {
                // ID & Name
                id: v.voice_id || v.id, 
                name: v.name || 'Unknown Voice',
                
                // Preview & Desc
                preview_url: v.preview_url || v.sample_audio || '',
                description: v.description || 'Kết quả tìm kiếm theo ID',
                
                // Avatar (fallback)
                avatar: v.image_url || null, 
                source: 'shared',

                // Tags đã xử lý ở trên
                tags: tags,

                // 🔥 CÁC CHỈ SỐ QUAN TRỌNG (Map đúng key từ API về)
                language: v.language || 'en', // Cờ
                usage_1y: parseInt(v.usage_character_count_1y || v.usage_1y || 0), // Icon tia sét
                cloned: parseInt(v.cloned_by_count || v.cloned || 0) // Icon người
            };

            // 4. Reset bộ lọc UI
            $('#filterLang, #filterGender, #filterAge, #filterCategory').val('');
            $('.filter-group').removeClass('has-value');

            // 5. Render bằng hàm chuẩn (Sẽ tự gọi createVoiceCardHTML)
            renderVoiceGrid([formattedVoice]);

            // 6. Cache tạm thời vào list hiện tại (để bấm play ko lỗi)
            if (loadedVoices.elevenlabs) {
                // Kiểm tra trùng trước khi push
                if (!loadedVoices.elevenlabs.find(item => item.id === formattedVoice.id)) {
                    loadedVoices.elevenlabs.push(formattedVoice);
                }
            }

        } else {
            // Trường hợp không tìm thấy
            $('#voiceGrid').html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <i class="bi bi-emoji-frown" style="font-size:48px; color:#555; display:block; margin-bottom:15px;"></i>
                    <p style="color:#ef4444; font-size:14px; font-weight:600;">Không tìm thấy giọng nói</p>
                    <p style="color:#666; font-size:12px; margin-top:5px;">ID "${voiceId}" không tồn tại hoặc sai định dạng.</p>
                    <button onclick="resetFilters()" class="btn-generate" style="margin:20px auto; width:auto; padding:8px 20px; font-size:13px;">
                        <i class="bi bi-arrow-left"></i> Quay lại thư viện
                    </button>
                </div>
            `);
        }
        
        // Mở khóa bộ lọc
        isSearchingServer = false; 

    }, 'json').fail(function() {
        // Xử lý lỗi mạng
        $('#voiceGrid').html(`
            <div style="padding:40px; text-align:center; color:#ef4444;">
                <i class="bi bi-wifi-off" style="font-size:32px; display:block; margin-bottom:10px;"></i>
                Lỗi kết nối server! Vui lòng thử lại.
            </div>
        `);
        isSearchingServer = false;
    });
}

function disableProviderOption(provider) {
    let $option = $(`.provider-option[data-provider="${provider}"]`);
    
    if ($option.length) {
        $option.addClass('provider-disabled');
        
        if (!$option.find('.maintenance-badge').length) {
            $option.find('.provider-desc').html(`
                <span class="maintenance-badge">🔴 Đang bảo trì</span>
            `);
        }
        
        $option.css('pointer-events', 'none');
        $option.css('opacity', '0.5');
        
        console.log('❌ Disabled provider:', provider);
    }
}

// ========== HIỆN MODAL CHI TIẾT (METADATA) ==========
function showMetadata(taskId) {
    const item = historyDataMap[taskId];
    if (!item) return;

    // 1. Điền text
    $('#dtText').text(item.text_input || '(Không có nội dung)');

    // 2. Điền thông tin kỹ thuật
    $('#dtTaskId').text(taskId);
    $('#dtTime').text(item.created_at || '-');
    $('#dtProvider').text(item.provider || 'elevenlabs');
    $('#dtModel').text(item.model_id || '-');
    $('#dtVoice').text(item.voice_name || 'Mặc định');
    $('#dtCost').text((item.credit_cost || 0) + ' credits');

    // 3. Xử lý Audio Player trong Modal
    const audioUrl = item.audio_url || item.url_audio; 
    if (audioUrl && item.status === 'done') {
        $('#dtAudio').attr('src', audioUrl);
        $('#dtPlayerGroup').show();
    } else {
        $('#dtPlayerGroup').hide();
        $('#dtAudio').attr('src', '');
    }

    // 4. Xử lý Lỗi
    if (item.status === 'failed') {
        $('#dtErrorMsg').text(item.error_message || 'Lỗi không xác định');
        $('#dtErrorBox').show();
    } else {
        $('#dtErrorBox').hide();
    }

    // 5. Footer Buttons (Tải xuống)
    let footerHtml = '';
    if (item.status === 'done') {
        let linkJson = item.url_json || item.json_url;
        let linkSrt = item.url_srt || item.srt_url;

        // Style nút tải cho đẹp
        const btnStyle = "text-decoration:none; background:#222; border:1px solid #444; padding:6px 12px; border-radius:6px; color:#fff; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px;";

        if (linkSrt) footerHtml += `<a href="${linkSrt}" download style="${btnStyle}"><i class="bi bi-file-text"></i> Tải SRT</a> `;
        if (linkJson) footerHtml += `<a href="${linkJson}" download style="${btnStyle}"><i class="bi bi-filetype-json"></i> Tải JSON</a> `;
        if (audioUrl) footerHtml += `<a href="${audioUrl}" download style="${btnStyle}"><i class="bi bi-download"></i> Tải Audio</a>`;
    }
    
    // Nút đóng
    footerHtml += `<button onclick="closeTTSDetailModal()" style="margin-left:auto; background:transparent; border:1px solid #444; color:#888; padding:6px 16px; border-radius:6px; cursor:pointer;">Đóng</button>`;
    
    $('#dtFooterActions').html(footerHtml);

    // 6. Hiện Modal
    $('#ttsDetailModal').css('display', 'flex').hide().fadeIn(200);
}

function closeTTSDetailModal() {
    $('#ttsDetailModal').fadeOut(200);
    const audio = document.getElementById('dtAudio');
    if (audio) {
        audio.pause();
        audio.currentTime = 0;
    }
}
// ========== HÀM ĐỒNG BỘ GIỮA SIDEBAR VÀ MODAL CHI TIẾT ==========
function syncDetailedHistoryCard(taskId, status, audioUrl, srtUrl, jsonUrl, duration) {
    // Kiểm tra xem modal có đang mở không
    if (!$('#detailedHistoryModal').is(':visible')) {
        return; // Modal đóng thì không cần sync
    }
    
    let $row = $(`#row-${taskId}`);
    if ($row.length === 0) {
        return; // Row không tồn tại
    }
    
    console.log('🔄 Syncing detailed card:', taskId, status);
    
    // Dừng interval nếu đang chạy
    if (detailedIntervals[taskId]) {
        clearInterval(detailedIntervals[taskId]);
        delete detailedIntervals[taskId];
    }
    
    if (status === 'done') {
        // Cập nhật badge
        $row.find('.dh-badge-processing')
            .removeClass('dh-badge-processing')
            .addClass('dh-badge-done')
            .text('Xong');
        
        // Cập nhật credit label
        $row.find('.dh-credits-label').text('Tín dụng sử dụng');
        
        // Thay thế spinner bằng Player
        let durationText = duration ? formatTime(duration) : "--:--";
        let playerHtml = `
            <div class="dh-player" id="dh-player-${taskId}">
                <button class="dh-play-btn" id="dh-play-btn-${taskId}" onclick="playAudio('${taskId}', '${audioUrl}')">
                    <i class="bi bi-play-fill"></i>
                </button>
                
                <div class="dh-progress-track" onclick="seekAudio(event, '${taskId}', true)"> 
                    <div class="dh-progress-bar" id="dh-progress-${taskId}" style="width: 0%"></div>
                </div>
                
                <div class="dh-timer" id="dh-timer-${taskId}">0:00 / ${durationText}</div>
                
                <a href="${audioUrl}" download class="bi bi-download" style="color:#fff; font-size:14px; margin-left: auto; text-decoration:none;" title="Tải nhanh"></a>
            </div>
        `;
        
        $row.find('.dh-content-area').html(playerHtml);
        
    } else if (status === 'failed') {
        // Cập nhật badge
        $row.find('.dh-badge-processing')
            .removeClass('dh-badge-processing')
            .addClass('dh-badge-error')
            .text('Lỗi');
        
        // Cập nhật credit label
        $row.find('.dh-credits-label').text('Đã hoàn trả');
        
        // Hiển thị lỗi
        let errorHtml = `<div class="dh-status-text dh-text-error"><i class="bi bi-exclamation-circle"></i> Lỗi không xác định</div>`;
        $row.find('.dh-content-area').html(errorHtml);
    }
}
