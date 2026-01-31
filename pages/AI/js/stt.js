// ==========================================
// 🚀 SPEECH TO TEXT JS (MULTIPLE FILES WITH LIST)
// ==========================================

// --- Global Variables ---
let historyPlayer = document.getElementById('sttAudio');
let currentPlayingId = null;
let autoRefreshInterval = null;

// Files với metadata
let selectedFiles = []; // Array of {file, duration, id}
let fileIdCounter = 0;

// Bulk Selection
let isAllChecked = false;
let selectedItems = [];

$(document).ready(function() {
    loadHistory();
    setupDragDrop();
    setupHistoryPlayer();
    startAutoRefresh();
});
// ========================================
// 🔄 REFRESH HISTORY (KHÔNG CẦN RELOAD TRANG)
// ========================================
function refreshHistory() {
    let $btn = $('#btnRefresh');
    let $icon = $btn.find('i');
    
    // 1. Disable nút & thêm animation xoay
    $btn.prop('disabled', true).addClass('spinning');
    
    // 2. Gọi API lấy lịch sử mới
    $.ajax({
        url: '/ajaxs/stt.php',
        type: 'POST',
        data: {
            action: 'get_history',
            page: 1,
            limit: 50
        },
        dataType: 'json',
        timeout: 10000,
        
        success: function(res) {
            if (res.status === 'success' && res.data) {
                // 3. Render lại danh sách
                renderHistory(res.data);
                
                // 4. Reset trạng thái Bulk Select
                selectedItems = [];
                isAllChecked = false;
                $('#btnCheckAll').removeClass('active').find('i').removeClass('bi-check-square-fill').addClass('bi-square');
                updateBulkActions();
                
                // 5. Thông báo thành công
                showToast('success', '✅ Đã làm mới!');
            } else {
                showToast('error', '❌ Không thể tải dữ liệu');
            }
        },
        
        error: function(xhr, status, error) {
            console.error('Refresh Error:', {xhr, status, error});
            showToast('error', '❌ Lỗi kết nối');
        },
        
        complete: function() {
            // 6. Bật lại nút & dừng animation
            setTimeout(() => {
                $btn.prop('disabled', false).removeClass('spinning');
            }, 500); // Delay 0.5s cho mượt
        }
    });
}
// ========== 1. FILE HANDLING (MULTIPLE FILES WITH LIST) ==========
function handleFile(input) {
    if (input.files.length === 0) return;
    
    const newFiles = Array.from(input.files);
    const allowedExts = ['mp3', 'aac', 'wav'];
    let invalidFiles = [];
    
    // Validate and add files
    newFiles.forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExts.includes(ext)) {
            invalidFiles.push(file.name + ' (định dạng không hỗ trợ)');
            return;
        }
        
        if (file.size > 200 * 1024 * 1024) {
            invalidFiles.push(file.name + ' (quá lớn, max 200MB)');
            return;
        }
        
        // Check duplicate
        const isDuplicate = selectedFiles.some(f => f.file.name === file.name && f.file.size === file.size);
        if (isDuplicate) {
            invalidFiles.push(file.name + ' (đã tồn tại)');
            return;
        }
        
        // Add to list
        const fileId = ++fileIdCounter;
        selectedFiles.push({
            id: fileId,
            file: file,
            duration: 0,
            loading: true
        });
        
        // Get duration
        getDuration(file, fileId);
    });
    
    if (invalidFiles.length > 0) {
        showToast('warning', `${invalidFiles.length} file không hợp lệ: ${invalidFiles.join(', ')}`);
    }
    
    // Reset input để có thể chọn lại cùng file
    input.value = '';
    
    // Update UI
    updateFileListUI();
}

function getDuration(file, fileId) {
    const objectUrl = URL.createObjectURL(file);
    const audio = new Audio(objectUrl);
    
    audio.onloadedmetadata = () => {
        URL.revokeObjectURL(objectUrl);
        
        // Update duration
        const fileObj = selectedFiles.find(f => f.id === fileId);
        if (fileObj) {
            fileObj.duration = audio.duration;
            fileObj.loading = false;
            updateFileListUI();
            calculateTotalCost();
        }
    };
    
    audio.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        const fileObj = selectedFiles.find(f => f.id === fileId);
        if (fileObj) {
            fileObj.duration = 0;
            fileObj.loading = false;
            updateFileListUI();
        }
    };
}

function updateFileListUI() {
    if (selectedFiles.length === 0) {
        $('#uploadZone').show();
        $('#fileListContainer').hide();
        $('#estimatedCost').text('~0');
        return;
    }
    
    $('#uploadZone').hide();
    $('#fileListContainer').show();
    $('#fileCount').text(selectedFiles.length);
    
    let html = '';
    selectedFiles.forEach(fileObj => {
        const sizeMB = (fileObj.file.size / 1024 / 1024).toFixed(2);
        const durationStr = fileObj.loading ? 
            'Đang tải...' : 
            (fileObj.duration > 0 ? formatTime(fileObj.duration) : 'N/A');
        
        html += `
            <div class="file-item ${fileObj.loading ? 'loading' : ''}" data-id="${fileObj.id}">
                <div class="file-item-icon">
                    <i class="bi bi-file-earmark-music"></i>
                </div>
                <div class="file-item-info">
                    <div class="file-item-name">${fileObj.file.name}</div>
                    <div class="file-item-meta">
                        <span class="file-item-size">${sizeMB} MB</span>
                        <span class="separator">•</span>
                        <span class="file-item-duration">${durationStr}</span>
                    </div>
                </div>
                <button class="btn-remove-file" onclick="removeFile(${fileObj.id})" title="Xóa">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    });
    
    $('#fileList').html(html);
}

function removeFile(fileId) {
    selectedFiles = selectedFiles.filter(f => f.id !== fileId);
    updateFileListUI();
    calculateTotalCost();
}

function clearAllFiles() {
    if (selectedFiles.length === 0) return;
    
    if (!confirm(`Xóa tất cả ${selectedFiles.length} file?`)) return;
    
    selectedFiles = [];
    updateFileListUI();
}

function calculateTotalCost() {
    // Tính tổng thời lượng thực tế
    const totalSeconds = selectedFiles.reduce((sum, f) => sum + (f.duration || 0), 0);

    // Công thức: 5 credits/giây
    let estimatedCost = Math.ceil(totalSeconds * 5);

    $('#estimatedCost').text(estimatedCost.toLocaleString());
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

function setupDragDrop() {
    const dropZone = $('#uploadZone');
    dropZone.on('dragover', function(e) { e.preventDefault(); e.stopPropagation(); $(this).addClass('drag-over'); });
    dropZone.on('dragleave', function(e) { e.preventDefault(); e.stopPropagation(); $(this).removeClass('drag-over'); });
    dropZone.on('drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $('#audioInput')[0].files = files;
            handleFile($('#audioInput')[0]);
        }
    });
}

// ========== 2. START TRANSCRIPTION (MULTIPLE FILES) ==========
function startTranscription() {
    if (selectedFiles.length === 0) {
        showToast('error', 'Vui lòng chọn file audio!');
        $('#uploadZone').click();
        return;
    }
    
    const hasLoading = selectedFiles.some(f => f.loading);
    if (hasLoading) {
        showToast('warning', 'Vui lòng đợi tất cả file load xong metadata!');
        return;
    }
    
    // 🔥 TÍNH CHI PHÍ (5 credits/giây)
    const totalSeconds = selectedFiles.reduce((sum, f) => sum + (f.duration || 0), 0);
    let estimatedCost = Math.ceil(totalSeconds * 5);
    
    // ✅ LẤY CREDITS
    let currentCredits = (typeof userCreditsFromDB !== 'undefined') 
        ? userCreditsFromDB 
        : parseInt($('#userCreditsDisplay').text().replace(/[^0-9]/g, '')) || 0;
    
    console.log('💰 Debug Credits:', {
        'userCreditsFromDB': userCreditsFromDB,
        'DOM value': $('#userCreditsDisplay').text(),
        'Parsed DOM': parseInt($('#userCreditsDisplay').text().replace(/[^0-9]/g, '')),
        'Final currentCredits': currentCredits,
        'estimatedCost': estimatedCost
    });
    
    // Kiểm tra đủ tiền không
    if (currentCredits < estimatedCost) {
        showToast('error', `Không đủ credits! Cần ${estimatedCost.toLocaleString()}, còn ${currentCredits.toLocaleString()}`);
        return;
    }
    
    // 🔥 TRỪ TẠM SỐ CREDITS DỰ KIẾN
    let tempCredits = currentCredits - estimatedCost;
    $('#userCreditsDisplay').text(tempCredits.toLocaleString()).css('color', '#f59e0b');
    
    const formData = new FormData();
    formData.append('action', 'create_stt');
    formData.append('estimated_cost', estimatedCost);

    // Append all files với durations
    selectedFiles.forEach((fileObj, index) => {
        formData.append('files[]', fileObj.file);
        formData.append(`durations[${index}]`, Math.round(fileObj.duration || 0));
    });
    
    $('#btnSTT').prop('disabled', true).html(`<span class="spinner-border spinner-border-sm"></span> <span>Đang tải ${selectedFiles.length} file...</span>`);
    
    $.ajax({
        url: '/ajaxs/stt.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 180000,
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', res.message || 'Đã tạo task thành công!');
                
                // 🔥 XÓA FILE SAU KHI UPLOAD THÀNH CÔNG
                selectedFiles = [];
                updateFileListUI();
                
                loadHistory();
                
                // 🔥 CẬP NHẬT CREDITS THỰC TẾ TỪ SERVER
                if (res.remain_credits !== undefined) {
                    let actualCredits = res.remain_credits;
                    
                    $('#userCreditsDisplay')
                        .text(actualCredits.toLocaleString())
                        .css('color', '#22c55e');
                    
                    // ✅ CẬP NHẬT BIẾN GLOBAL
                    if (typeof userCreditsFromDB !== 'undefined') {
                        window.userCreditsFromDB = actualCredits;
                    }
                    
                    console.log('📊 Credits Flow:', {
                        'Số dư ban đầu': currentCredits.toLocaleString(),
                        'Chi phí ước tính': estimatedCost.toLocaleString(),
                        'Chi phí thực tế': res.actual_cost || 'N/A',
                        'Số dư cuối': actualCredits.toLocaleString(),
                        'Hoàn lại': res.refund_amount || 0
                    });
                } else {
                    $('#userCreditsDisplay').css('color', '#22c55e');
                    console.warn('⚠️ Server không trả về remain_credits');
                }
            } else {
                // 🔥 NẾU CÓ LỖI, HOÀN TRẢ LẠI CREDITS
                $('#userCreditsDisplay').text(currentCredits.toLocaleString()).css('color', '#22c55e');
                
                let errorMsg = res.message || 'Có lỗi xảy ra!';
                if (res.failed_files && res.failed_files.length > 0) {
                    errorMsg += '\n\nFile thất bại:\n' + res.failed_files.join('\n');
                }
                showToast('error', errorMsg);
                
                console.log('❌ Upload thất bại, đã hoàn trả credits:', currentCredits.toLocaleString());
            }
        },
        error: function(xhr, status, error) {
            // 🔥 NẾU AJAX LỖI, HOÀN TRẢ LẠI CREDITS
            $('#userCreditsDisplay').text(currentCredits.toLocaleString()).css('color', '#22c55e');
            
            console.error('AJAX Error:', {xhr, status, error});
            showToast('error', 'Lỗi kết nối: ' + (error || 'Timeout hoặc server không phản hồi'));
            
            console.log('❌ Lỗi kết nối, đã hoàn trả credits:', currentCredits.toLocaleString());
        },
        complete: function() {
            $('#btnSTT').prop('disabled', false).html(`<i class="bi bi-play-circle-fill"></i> <span>Bắt đầu chuyển đổi</span>`);
        }
    });
}

// ========== 3. HISTORY MANAGEMENT ==========
function loadHistory() {
    $.ajax({
        url: '/ajaxs/stt.php',
        type: 'POST',
        data: {
            action: 'get_history',
            page: 1,
            limit: 50
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data) {
                renderHistory(res.data);
                selectedItems = [];
                isAllChecked = false;
                $('#btnCheckAll').removeClass('active').find('i').removeClass('bi-check-square-fill').addClass('bi-square');
                updateBulkActions();
            } else {
                $('#historyList').html(`<div class="history-empty"><i class="bi bi-exclamation-circle"></i><span>Không có dữ liệu</span></div>`);
            }
        },
        error: function(xhr, status, error) {
            console.error('Load History Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let errorMsg = 'Lỗi kết nối';
            if (xhr.responseText) {
                try {
                    const errData = JSON.parse(xhr.responseText);
                    errorMsg = errData.message || errorMsg;
                } catch(e) {
                    errorMsg = xhr.responseText.substring(0, 100);
                }
            }
            
            $('#historyList').html(`<div class="history-empty"><i class="bi bi-wifi-off"></i><span>${errorMsg}</span></div>`);
        }
    });
}

function renderHistory(data) {
    console.log("🔥 Dữ liệu lịch sử:", data);
    if (!data || data.length === 0) {
        $('#historyList').html(`<div class="history-empty"><i class="bi bi-inbox"></i><span>Chưa có tác vụ nào</span></div>`);
        return;
    }
    
    let html = '';
    data.forEach(item => {
        const statusClass = item.status;
        
        let timeStr = item.created_at_formatted || item.created_at;
        if (item.created_at) {
            const date = new Date(item.created_at);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            timeStr = `${day}/${month} ${hours}:${minutes}`;
        }

        let actions = '';
        
        if (item.status === 'done') {
            // Lấy link an toàn
            let linkJson = item.url_json || item.json_url || '';
            let linkSrt = item.url_srt || item.srt_url || '';

            actions = `
                <div class="action-buttons">
                    ${linkSrt ? `
                        <a href="javascript:void(0)" onclick="forceDownload('${linkSrt}', 'stt_${item.task_id}.srt')" class="btn-text-dl" title="Tải Subtitle">
                            <i class="bi bi-download"></i> SRT
                        </a>
                    ` : ''}
                </div>
            `;
        } else if (item.status === 'failed') {
            actions = `
                <div class="failed-indicator" style="color: #ef4444; font-size: 13px;">
                    <i class="bi bi-x-circle-fill"></i>
                    <span>Thất bại</span>
                </div>
            `;
        } else if (item.status === 'pending' || item.status === 'doing' || item.status === 'processing') {
            const progress = item.progress || 0;
            actions = `
                <div class="processing-indicator">
                    <span class="spinner-border spinner-border-sm" style="width:12px;height:12px;border-width:2px;"></span>
                    <span>Đang xử lý ${progress > 0 ? `(${progress}%)` : ''}</span>
                </div>
            `;
            checkTaskStatus(item.task_id);
        }
        
        html += `
            <div class="history-item" 
                 id="task-${item.task_id}" 
                 data-status="${item.status}"
                 data-url-json="${item.url_json || ''}"
                 data-url-srt="${item.url_srt || ''}"
                 data-history-id="${item.id}">
                
                <div class="item-checkbox" onclick="toggleItemCheck(this, '${item.task_id}', '${item.status}', '${item.url_json || ''}', '${item.url_srt || ''}', ${item.id})"></div>
                
                <div class="item-info">
                    <div class="item-title">${item.file_name}</div>
                    <div class="item-meta">
                        <span>${timeStr}</span>
                    </div>
                </div>
                
                <div class="status-indicator ${statusClass}"></div>
                
                ${actions}
                
                <div class="item-credits">
                    <div class="credits-amount">${item.credit_cost || 0}</div>
                    <div class="credits-status">Tín dụng sử dụng</div>
                </div>
            </div>
        `;
    });
    
    $('#historyList').html(html);
}

// ========== 4. BULK SELECTION ==========
function toggleCheckAll() {
    isAllChecked = !isAllChecked;
    const btn = $('#btnCheckAll');
    const icon = btn.find('i');
    
    if (isAllChecked) {
        $('.item-checkbox').addClass('checked');
        icon.removeClass('bi-square').addClass('bi-check-square-fill');
        btn.addClass('active');
        
        selectedItems = [];
        $('.history-item').each(function() {
            const taskId = $(this).attr('id').replace('task-', '');
            const status = $(this).data('status');
            const urlJson = $(this).data('url-json');
            const urlSrt = $(this).data('url-srt');
            const historyId = $(this).data('history-id');
            selectedItems.push({ taskId, status, urlJson, urlSrt, historyId });
        });
    } else {
        $('.item-checkbox').removeClass('checked');
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
        btn.removeClass('active');
        selectedItems = [];
    }
    
    updateBulkActions();
}

function toggleItemCheck(element, taskId, status, urlJson, urlSrt, historyId) {
    $(element).toggleClass('checked');
    
    const itemData = { taskId, status, urlJson, urlSrt, historyId };
    const index = selectedItems.findIndex(item => item.taskId === taskId);
    
    if ($(element).hasClass('checked')) {
        if (index === -1) selectedItems.push(itemData);
    } else {
        if (index > -1) selectedItems.splice(index, 1);
    }
    
    const totalItems = $('.item-checkbox').length;
    const checkedItems = $('.item-checkbox.checked').length;
    const btn = $('#btnCheckAll');
    const icon = btn.find('i');
    
    if (checkedItems === totalItems && totalItems > 0) {
        isAllChecked = true;
        icon.removeClass('bi-square').addClass('bi-check-square-fill');
        btn.addClass('active');
    } else {
        isAllChecked = false;
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
        btn.removeClass('active');
    }
    
    updateBulkActions();
}

function updateBulkActions() {
    const count = selectedItems.length;
    $('#selectedCount').text(count);
    
    if (count > 0) {
        $('#bulkActions').addClass('show');
    } else {
        $('#bulkActions').removeClass('show');
    }
}

// ========== 5. BULK ACTIONS ==========
function bulkDownloadJSON() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item!');
        return;
    }
    
    let count = 0;
    selectedItems.forEach((item, idx) => {
        if (item.urlJson && item.status === 'done') {
            // Delay để tránh browser block
            setTimeout(() => {
                forceDownload(item.urlJson, `stt_${item.taskId}.json`);
            }, idx * 500);
            count++;
        }
    });

    if (count > 0) {
        showToast('success', `Đang tải ${count} file JSON...`);
    } else {
        showToast('warning', 'Không có file JSON nào để tải!');
    }
}

function bulkDownloadSRT() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item!');
        return;
    }

    let count = 0;
    selectedItems.forEach((item, idx) => {
        if (item.urlSrt && item.status === 'done') {
            // Delay để tránh browser block
            setTimeout(() => {
                forceDownload(item.urlSrt, `stt_${item.taskId}.srt`);
            }, idx * 500);
            count++;
        }
    });

    if (count > 0) {
        showToast('success', `Đang tải ${count} file SRT...`);
    } else {
        showToast('warning', 'Không có file SRT nào để tải!');
    }
}

// 🔥 HÀM NÀY PHẢI CÓ
function bulkDelete() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item để xóa!');
        return;
    }
    
    // Hiển thị modal
    $('#deleteCount').text(selectedItems.length);
    $('#deleteModal').fadeIn(200);
}

// 🔥 HÀM ĐÓNG MODAL
function closeDeleteModal() {
    $('#deleteModal').fadeOut(200);
}

// 🔥 HÀM XÁC NHẬN XÓA
function confirmDelete() {
    // Đóng modal
    $('#deleteModal').fadeOut(200);
    
    // Lấy danh sách ID
    const historyIds = selectedItems.map(item => item.historyId).filter(id => id);
    
    if (historyIds.length === 0) {
        showToast('error', 'Không có ID hợp lệ để xóa!');
        return;
    }
    
    // Gọi API xóa
    $.ajax({
        url: '/ajaxs/stt.php',
        type: 'POST',
        data: {
            action: 'bulk_delete',
            history_ids: historyIds.join(',')
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', `✅ Đã xóa ${selectedItems.length} task!`);
                
                // Reset selection
                selectedItems = [];
                isAllChecked = false;
                $('#btnCheckAll')
                    .removeClass('active')
                    .find('i')
                    .removeClass('bi-check-square-fill')
                    .addClass('bi-square');
                updateBulkActions();
                
                // Reload history
                loadHistory();
            } else {
                showToast('error', res.message || 'Có lỗi xảy ra!');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete Error:', {xhr, status, error});
            showToast('error', '❌ Lỗi kết nối!');
        }
    });
}
// ========== 6. AUDIO PLAYER ==========
function setupHistoryPlayer() {
    if (historyPlayer) {
        historyPlayer.onended = function() {
            if (currentPlayingId) {
                $(`#btn-play-${currentPlayingId}`).removeClass('playing').html('<i class="bi bi-play-fill"></i>');
                currentPlayingId = null;
            }
        };
    }
}

// ========== 7. TASK STATUS CHECK ==========
function checkTaskStatus(taskId) {
    setTimeout(() => {
        const taskElement = $(`#task-${taskId}`);
        const currentStatus = taskElement.data('status');
        if (taskElement.length === 0 || (currentStatus !== 'pending' && currentStatus !== 'doing' && currentStatus !== 'processing')) return;

        $.post('/ajaxs/stt.php', { action: 'check_status', task_id: taskId }, function(res) {
            if (res.status === 'success') {
                // Update progress text
                if (res.progress > 0 && res.task_status !== 'done') {
                    taskElement.find('.processing-indicator span:last').text(`Đang xử lý (${res.progress}%)`);
                }

                if (res.task_status === 'done' || res.task_status === 'failed') {
                    loadHistory();
                    if (res.task_status === 'done') showToast('success', '✅ Task hoàn thành!');
                    if (res.new_balance) $('#userCreditsDisplay').text(res.new_balance.toLocaleString());
                } else {
                    checkTaskStatus(taskId);
                }
            } else {
                checkTaskStatus(taskId);
            }
        }, 'json').fail(() => checkTaskStatus(taskId));
    }, 5000);
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        // Check for pending/doing/processing items
        if ($('.history-item[data-status="pending"], .history-item[data-status="doing"], .history-item[data-status="processing"]').length > 0) {
            loadHistory();
        }
    }, 10000);
}

// ========== 8. UTILITIES ==========
function showToast(type, message) {
    const toast = $('#toast');
    const icon = toast.find('i');
    toast.removeClass('success error warning').addClass(type);
    icon.removeClass().addClass(type === 'success' ? 'bi-check-circle-fill' : (type === 'error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill'));
    toast.find('.toast-text').text(message);
    toast.addClass('show');
    setTimeout(() => toast.removeClass('show'), 3500);
}

// ========== 9. FORCE DOWNLOAD (Cross-Origin) ==========
function forceDownload(url, filename) {
    if (!url) return;

    // Tạo filename từ URL nếu không có
    if (!filename) {
        filename = url.split('/').pop().split('?')[0] || 'download';
    }

    // Fetch file và tạo blob để download
    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.blob();
        })
        .then(blob => {
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch(err => {
            console.error('Download error:', err);
            // Fallback: mở link mới
            window.open(url, '_blank');
        });
}

$(window).on('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});