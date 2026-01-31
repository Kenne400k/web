// ==========================================
// 🚀 DUBBING JS (FULL VERSION - UPDATED)
// ==========================================

// --- Global Variables ---
let historyPlayer = document.getElementById('dubAudio'); 
let currentPlayingId = null;
let autoRefreshInterval = null;

// File & Pricing
let currentFileDuration = 0; 
let currentFileSizeMB = 0;
let selectedTargetLangs = [];

// Bulk Selection
let isAllChecked = false;
let selectedItems = [];

$(document).ready(function() {
    // 1. Check API Key
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        $('#uploadZone').css('pointer-events', 'none').css('opacity', '0.5');
        showToast('error', 'Bạn chưa có API Key! Vui lòng liên hệ Admin.');
    }

    // 2. Init UI
    renderDropdowns();
    renderSpeakerList();
    loadHistory();
    setupDragDrop();
    setupHistoryPlayer();
    startAutoRefresh();

    // 3. Close dropdown event
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('.dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
    });
    // 🔥 THÊM: Đóng modal khi click bên ngoài
    $(document).on('click', '#deleteModal', function(e) {
        if (e.target.id === 'deleteModal') {
            closeDeleteModal();
        }
    });

    // 🔥 THÊM: Đóng modal khi nhấn ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#deleteModal').is(':visible')) {
            closeDeleteModal();
        }
    });
});

// ========== DATA NGÔN NGỮ ==========
const sourceLanguages = [
    { val: "auto", label: "✨ Auto-detect" }, // Giữ nguyên ở đầu
    { val: "af", label: "🇿🇦 Afrikaans" },
    { val: "ar", label: "🇸🇦 Arabic" },
    { val: "hy", label: "🇦🇲 Armenian" },
    { val: "az", label: "🇦🇿 Azerbaijani" },
    { val: "be", label: "🇧🇾 Belarusian" },
    { val: "bs", label: "🇧🇦 Bosnian" },
    { val: "bg", label: "🇧🇬 Bulgarian" },
    { val: "ca", label: "🏴 Catalan" },
    { val: "zh", label: "🇨🇳 Chinese" },
    { val: "hr", label: "🇭🇷 Croatian" },
    { val: "cs", label: "🇨🇿 Czech" },
    { val: "da", label: "🇩🇰 Danish" },
    { val: "nl", label: "🇳🇱 Dutch" },
    { val: "en", label: "🇺🇸 English" },
    { val: "et", label: "🇪🇪 Estonian" },
    { val: "fi", label: "🇫🇮 Finnish" },
    { val: "fr", label: "🇫🇷 French" },
    { val: "gl", label: "🏴 Galician" },
    { val: "de", label: "🇩🇪 German" },
    { val: "el", label: "🇬🇷 Greek" },
    { val: "he", label: "🇮🇱 Hebrew" },
    { val: "hi", label: "🇮🇳 Hindi" },
    { val: "hu", label: "🇭🇺 Hungarian" },
    { val: "is", label: "🇮🇸 Icelandic" },
    { val: "id", label: "🇮🇩 Indonesian" },
    { val: "it", label: "🇮🇹 Italian" },
    { val: "ja", label: "🇯🇵 Japanese" },
    { val: "kn", label: "🇮🇳 Kannada" },
    { val: "kk", label: "🇰🇿 Kazakh" },
    { val: "ko", label: "🇰🇷 Korean" },
    { val: "lv", label: "🇱🇻 Latvian" },
    { val: "lt", label: "🇱🇹 Lithuanian" },
    { val: "mk", label: "🇲🇰 Macedonian" },
    { val: "ms", label: "🇲🇾 Malay" },
    { val: "mi", label: "🇳🇿 Maori" },
    { val: "mr", label: "🇮🇳 Marathi" },
    { val: "ne", label: "🇳🇵 Nepali" },
    { val: "no", label: "🇳🇴 Norwegian" },
    { val: "fa", label: "🇮🇷 Persian" },
    { val: "pl", label: "🇵🇱 Polish" },
    { val: "pt", label: "🇵🇹 Portuguese" },
    { val: "ro", label: "🇷🇴 Romanian" },
    { val: "ru", label: "🇷🇺 Russian" },
    { val: "sr", label: "🇷🇸 Serbian" },
    { val: "sk", label: "🇸🇰 Slovak" },
    { val: "sl", label: "🇸🇮 Slovenian" },
    { val: "es", label: "🇪🇸 Spanish" },
    { val: "sw", label: "🇰🇪 Swahili" },
    { val: "sv", label: "🇸🇪 Swedish" },
    { val: "tl", label: "🇵🇭 Tagalog" },
    { val: "ta", label: "🇮🇳 Tamil" },
    { val: "th", label: "🇹🇭 Thai" },
    { val: "tr", label: "🇹🇷 Turkish" },
    { val: "uk", label: "🇺🇦 Ukrainian" },
    { val: "ur", label: "🇵🇰 Urdu" },
    { val: "vi", label: "🇻🇳 Vietnamese" },
    { val: "cy", label: "🏴󠁧󠁢󠁷󠁬󠁳󠁿 Welsh" }
];

const targetLanguages = [
    { val: "ar", label: "🇸🇦 Arabic" },
    { val: "bg", label: "🇧🇬 Bulgarian" },
    { val: "zh", label: "🇨🇳 Chinese" },
    { val: "hr", label: "🇭🇷 Croatian" },
    { val: "cs", label: "🇨🇿 Czech" },
    { val: "da", label: "🇩🇰 Danish" },
    { val: "nl", label: "🇳🇱 Dutch" },
    { val: "en", label: "🇺🇸 English" },
    { val: "tl", label: "🇵🇭 Filipino" }, // API map Filipino -> tl
    { val: "fi", label: "🇫🇮 Finnish" },
    { val: "fr", label: "🇫🇷 French" },
    { val: "de", label: "🇩🇪 German" },
    { val: "el", label: "🇬🇷 Greek" },
    { val: "hi", label: "🇮🇳 Hindi" },
    { val: "id", label: "🇮🇩 Indonesian" },
    { val: "it", label: "🇮🇹 Italian" },
    { val: "ja", label: "🇯🇵 Japanese" },
    { val: "ko", label: "🇰🇷 Korean" },
    { val: "ms", label: "🇲🇾 Malay" },
    { val: "pl", label: "🇵🇱 Polish" },
    { val: "pt", label: "🇵🇹 Portuguese" },
    { val: "ro", label: "🇷🇴 Romanian" },
    { val: "ru", label: "🇷🇺 Russian" },
    { val: "sk", label: "🇸🇰 Slovak" },
    { val: "es", label: "🇪🇸 Spanish" },
    { val: "sv", label: "🇸🇪 Swedish" },
    { val: "ta", label: "🇮🇳 Tamil" },
    { val: "tr", label: "🇹🇷 Turkish" },
    { val: "uk", label: "🇺🇦 Ukrainian" },
    { val: "vi", label: "🇻🇳 Vietnamese" }
];
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
        url: '../../ajaxs/dubbing.php',
        type: 'POST',
        data: {
            action: 'get_history',
            filter_status: 'all',
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
// ========== 1. DROPDOWN HANDLERS ==========
function renderDropdowns() {
    // Source Language
    let sourceHtml = '';
    sourceLanguages.forEach(lang => {
        let isSelected = (lang.val === 'auto') ? 'selected' : '';
        sourceHtml += `<div class="dropdown-item ${isSelected}" onclick="selectSourceLang('${lang.val}', '${lang.label}', this)">${lang.label}</div>`;
    });
    $('#sourceLangList').html(sourceHtml);

    // Target Language
    let targetHtml = '';
    targetLanguages.forEach(lang => {
        targetHtml += `
            <div class="dropdown-item multi" data-val="${lang.val}" onclick="toggleTargetLang('${lang.val}', '${lang.label}', this)">
                <i class="bi bi-square"></i> 
                <span>${lang.label}</span>
            </div>`;
    });
    $('#targetLangList').html(targetHtml);
}

function renderSpeakerList() {
    let html = '<div class="dropdown-item selected" onclick="selectSpeaker(0, \'Tự động phát hiện\', this)">Tự động phát hiện</div>';
    for (let i = 1; i <= 9; i++) {
        html += `<div class="dropdown-item" onclick="selectSpeaker(${i}, '${i} người', this)">${i} người</div>`;
    }
    $('#speakerList').html(html);
}

function toggleDropdown(id) {
    $('.dropdown-menu').not('#' + id + ' .dropdown-menu').removeClass('show');
    $('.dropdown-trigger i').not('#' + id + ' .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    
    let menu = $('#' + id + ' .dropdown-menu');
    let icon = $('#' + id + ' .dropdown-trigger i');
    
    menu.toggleClass('show');
    icon.toggleClass('bi-chevron-down bi-chevron-up');
}

function selectSourceLang(val, label, element) {
    $('#sourceLangLabel').text(label);
    $('#sourceLangVal').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownSource .dropdown-menu').removeClass('show');
    $('#dropdownSource .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

function toggleTargetLang(val, label, element) {
    const el = $(element);
    const icon = el.find('i');
    
    if (selectedTargetLangs.includes(val)) {
        selectedTargetLangs = selectedTargetLangs.filter(item => item !== val);
        el.removeClass('checked');
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
    } else {
        selectedTargetLangs.push(val);
        el.addClass('checked');
        icon.removeClass('bi-square').addClass('bi-check-square-fill');
    }

    const count = selectedTargetLangs.length;
    if (count === 0) {
        $('#targetLangLabel').text('Chọn ngôn ngữ');
    } else if (count === 1) {
        const found = targetLanguages.find(l => l.val === selectedTargetLangs[0]);
        $('#targetLangLabel').text(found ? found.label : selectedTargetLangs[0]);
    } else {
        $('#targetLangLabel').html(`Đã chọn <span class="lang-badge">${count}</span> ngôn ngữ`);
    }

    $('#targetLangVal').val(selectedTargetLangs.join(','));
    calculateTotalCost();
}

function selectSpeaker(val, label, element) {
    $('#numSpeakersLabel').text(label);
    $('#numSpeakers').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownSpeakers .dropdown-menu').removeClass('show');
    $('#dropdownSpeakers .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

// ========== 2. FILE HANDLING ==========
function handleFile(input) {
    if (input.files.length > 0) {
        const file = input.files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        const ext = file.name.split('.').pop().toUpperCase();
        
        const allowedExts = ['MP3', 'M4A', 'MP4', 'MOV', 'WAV'];
        if (!allowedExts.includes(ext)) {
            showToast('error', 'Định dạng file không được hỗ trợ!');
            input.value = '';
            return;
        }
        
        if (file.size > 20 * 1024 * 1024) {
            showToast('error', 'File quá lớn! Tối đa 20MB.');
            input.value = '';
            return;
        }
        
        currentFileSizeMB = sizeMB;
        $('#uploadZone').hide();
        $('#filePreviewContainer').show();
        $('#previewFileName').text(file.name);
        
        const objectUrl = URL.createObjectURL(file);
        const previewAudio = document.getElementById('audioPlayer');
        previewAudio.src = objectUrl;
        
        previewAudio.onloadedmetadata = function() {
            currentFileDuration = previewAudio.duration;
            calculateTotalCost();
            URL.revokeObjectURL(objectUrl);
        };
    }
}

function calculateTotalCost() {
    if (currentFileDuration <= 0) {
        $('#previewFileSize').text(`${currentFileSizeMB} MB`);
        return;
    }

    const timeStr = formatTime(currentFileDuration);

    if (selectedTargetLangs.length === 0) {
        $('#previewFileSize').html(`
            <span style="color: #bbb;">${currentFileSizeMB} MB</span> 
            <span style="margin: 0 5px; color: #444;">|</span> 
            <span style="color: #bbb;">${timeStr}</span>
        `);
        return;
    }

    const costPerSec = 67.3;
    const numLangs = selectedTargetLangs.length;
    let totalCost = Math.ceil(currentFileDuration * costPerSec * numLangs);
    let formattedCost = totalCost.toLocaleString('en-US');
    let langText = numLangs > 1 ? ` (x${numLangs})` : '';

    $('#previewFileSize').html(`
        <span style="color: #bbb;">${currentFileSizeMB} MB</span> 
        <span style="margin: 0 5px; color: #444;">|</span> 
        <span style="color: #bbb;">${timeStr}</span>
        <span style="margin: 0 5px; color: #444;">|</span> 
        <span style="color: #fbbf24; font-weight: 600;">~${formattedCost} credits${langText}</span>
    `);
}

function resetFile(e) {
    if(e) e.preventDefault();
    $('#videoInput').val('');
    const previewAudio = document.getElementById('audioPlayer');
    previewAudio.pause();
    previewAudio.src = "";
    currentFileDuration = 0;
    currentFileSizeMB = 0;
    $('#filePreviewContainer').hide();
    $('#uploadZone').show();
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
            $('#videoInput')[0].files = files;
            handleFile($('#videoInput')[0]);
        }
    });
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

// ========== 3. START DUBBING ==========
function startDubbing() {
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        showToast('error', 'Bạn chưa có API Key!');
        return;
    }

    const file = $('#videoInput')[0].files[0];
    const sourceLang = $('#sourceLangVal').val();
    
    if (selectedTargetLangs.length === 0) {
        showToast('error', 'Vui lòng chọn ít nhất 1 ngôn ngữ đích!');
        toggleDropdown('dropdownTarget');
        return;
    }

    if (!file) {
        showToast('error', 'Vui lòng chọn file audio/video!');
        if($('#uploadZone').is(':visible')) $('#uploadZone').click();
        return;
    }
    
    const disableCloning = $('#disableCloning').is(':checked') ? 'true' : 'false';
    const numSpeakers = $('#numSpeakers').val();
    
    const formData = new FormData();
    formData.append('action', 'create_dub');
    formData.append('file', file);
    formData.append('target_lang', selectedTargetLangs.join(',')); 
    formData.append('source_lang', sourceLang);
    formData.append('num_speakers', numSpeakers);
    formData.append('disable_voice_cloning', disableCloning);
    
    $('#btnDub').prop('disabled', true).html(`<span class="spinner-border spinner-border-sm"></span> <span>Đang tải lên...</span>`);
    
    $.ajax({
        url: '../../ajaxs/dubbing.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 120000,
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', `Đã tạo ${selectedTargetLangs.length} task thành công! Đang xử lý...`);
                resetFile();
                loadHistory();
                if (res.remain_credits !== undefined) $('#userCredits').text(res.remain_credits.toLocaleString());
            } else {
                showToast('error', res.message || 'Có lỗi xảy ra!');
            }
        },
        error: function() {
            showToast('error', 'Lỗi kết nối hoặc timeout!');
        },
        complete: function() {
            $('#btnDub').prop('disabled', false).html(`<i class="bi bi-play-circle-fill"></i> <span>Bắt đầu Lồng tiếng</span>`);
        }
    });
}

// ========== 4. HISTORY MANAGEMENT ==========
function loadHistory() {
    $.post('../../ajaxs/dubbing.php', {
        action: 'get_history',
        filter_status: 'all',
        page: 1,
        limit: 50
    }, function(res) {
        if (res.status === 'success' && res.data) {
            renderHistory(res.data);
            selectedItems = [];
            isAllChecked = false;
            $('#btnCheckAll').removeClass('active').find('i').removeClass('bi-check-square-fill').addClass('bi-square');
            updateBulkActions();
        } else {
            $('#historyList').html(`<div class="history-empty"><i class="bi bi-exclamation-circle"></i><span>Không có dữ liệu</span></div>`);
        }
    }, 'json').fail(function() {
        $('#historyList').html(`<div class="history-empty"><i class="bi bi-wifi-off"></i><span>Lỗi kết nối</span></div>`);
    });
}

function renderHistory(data) {
    if (!data || data.length === 0) {
        $('#historyList').html(`<div class="history-empty"><i class="bi bi-inbox"></i><span>Chưa có tác vụ nào</span></div>`);
        return;
    }
    
    let html = '';
    data.forEach(item => {
        const statusClass = item.status;
        
        // Format thời gian: DD/MM HH:MM
        let timeStr = item.created_at_formatted || item.created_at;
        if (item.created_at) {
            const date = new Date(item.created_at);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            timeStr = `${day}/${month} ${hours}:${minutes}`;
        }
        
        // Lấy tên ngôn ngữ đích
        let targetLabel = item.target_lang;
        const found = targetLanguages.find(l => l.val === item.target_lang);
        if(found) {
            // Bỏ emoji, chỉ lấy tên ngôn ngữ
            targetLabel = found.label.replace(/[^\w\s]/gi, '').trim();
        }

        let actions = '';
        if (item.status === 'done') {
            actions = `
                <div class="action-buttons">
                    <button class="btn-play" id="btn-play-${item.task_id}" onclick="playAudio('${item.task_id}', '${item.url_result}')" title="Phát">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <a href="${item.url_result}" class="btn-download" download title="Tải Audio">
                        <i class="bi bi-download"></i>
                    </a>
                    ${item.url_srt ? `<a href="${item.url_srt}" class="btn-download" download title="Tải SRT"><i class="bi bi-file-text"></i></a>` : ''}
                </div>
            `;
        } else if (item.status === 'pending' || item.status === 'doing') {
            actions = `
                <div class="processing-indicator">
                    <span class="spinner-border spinner-border-sm" style="width:12px;height:12px;border-width:2px;"></span>
                    <span>Đang xử lý</span>
                </div>
            `;
            checkTaskStatus(item.task_id);
        }
        
        html += `
            <div class="history-item" 
                 id="task-${item.task_id}" 
                 data-status="${item.status}"
                 data-url-result="${item.url_result || ''}"
                 data-url-srt="${item.url_srt || ''}"
                 data-history-id="${item.id}">
                
                <div class="item-checkbox" onclick="toggleItemCheck(this, '${item.task_id}', '${item.status}', '${item.url_result || ''}', '${item.url_srt || ''}', ${item.id})"></div>
                
                <div class="item-info">
                    <div class="item-title">${item.file_name}</div>
                    <div class="item-meta">
                        <span>${timeStr}</span>
                        <span>-</span>
                        <span>${targetLabel}</span>
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

// ========== 5. BULK SELECTION ==========
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
            const urlResult = $(this).data('url-result');
            const urlSrt = $(this).data('url-srt');
            const historyId = $(this).data('history-id');
            selectedItems.push({ taskId, status, urlResult, urlSrt, historyId });
        });
    } else {
        $('.item-checkbox').removeClass('checked');
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
        btn.removeClass('active');
        selectedItems = [];
    }
    
    updateBulkActions();
}

function toggleItemCheck(element, taskId, status, urlResult, urlSrt, historyId) {
    $(element).toggleClass('checked');
    
    const itemData = { taskId, status, urlResult, urlSrt, historyId };
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
// ========== 6. BULK ACTIONS ==========
function bulkDownloadAudio() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item!');
        return;
    }
    
    selectedItems.forEach(item => {
        if (item.urlResult && item.status === 'done') {
            const link = document.createElement('a');
            link.href = item.urlResult;
            link.download = '';
            link.click();
        }
    });
    
    showToast('success', `Đang tải ${selectedItems.length} file audio...`);
}

function bulkDownloadSRT() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item!');
        return;
    }
    
    let count = 0;
    selectedItems.forEach(item => {
        if (item.urlSrt && item.status === 'done') {
            const link = document.createElement('a');
            link.href = item.urlSrt;
            link.download = '';
            link.click();
            count++;
        }
    });
    
    if (count > 0) {
        showToast('success', `Đang tải ${count} file SRT...`);
    } else {
        showToast('warning', 'Không có file SRT nào để tải!');
    }
}

function bulkDelete() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item để xóa!');
        return;
    }
    
    // 🔥 HIỂN THỊ MODAL THAY VÌ CONFIRM
    $('#deleteCount').text(selectedItems.length);
    $('#deleteModal').fadeIn(200);
}

function closeDeleteModal() {
    $('#deleteModal').fadeOut(200);
}

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
        url: '../../ajaxs/dubbing.php',
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

// ========== 7. AUDIO PLAYER ==========
function setupHistoryPlayer() {
    historyPlayer.onended = function() {
        if (currentPlayingId) {
            $(`#btn-play-${currentPlayingId}`).removeClass('playing').html('<i class="bi bi-play-fill"></i>');
            currentPlayingId = null;
        }
    };
}

function playAudio(taskId, url) {
    if (!url) return;
    const btn = $(`#btn-play-${taskId}`);
    
    const previewAudio = document.getElementById('audioPlayer');
    if(previewAudio && !previewAudio.paused) previewAudio.pause();

    if (currentPlayingId === taskId && !historyPlayer.paused) {
        historyPlayer.pause();
        btn.removeClass('playing').html('<i class="bi bi-play-fill"></i>');
        currentPlayingId = null;
    } else {
        if (currentPlayingId) $(`#btn-play-${currentPlayingId}`).removeClass('playing').html('<i class="bi bi-play-fill"></i>');
        currentPlayingId = taskId;
        historyPlayer.src = url;
        historyPlayer.play();
        btn.addClass('playing').html('<i class="bi bi-pause-fill"></i>');
    }
}

// ========== 8. TASK STATUS CHECK ==========
function checkTaskStatus(taskId) {
    setTimeout(() => {
        const taskElement = $(`#task-${taskId}`);
        if (taskElement.length === 0 || (taskElement.data('status') !== 'pending' && taskElement.data('status') !== 'doing')) return;
        
        $.post('../../ajaxs/dubbing.php', { action: 'check_status', task_id: taskId }, function(res) {
            if (res.status === 'success' && (res.task_status === 'done' || res.task_status === 'failed')) {
                loadHistory();
                if (res.task_status === 'done') showToast('success', '✅ Task hoàn thành!');
                if (res.new_balance) $('#userCredits').text(res.new_balance.toLocaleString());
            } else {
                checkTaskStatus(taskId);
            }
        }, 'json').fail(() => checkTaskStatus(taskId));
    }, 5000);
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        if ($('.status-pending, .status-doing').length > 0) loadHistory();
    }, 10000);
}

// ========== 9. UTILITIES ==========
function showToast(type, message) {
    const toast = $('#toast');
    const icon = toast.find('i');
    toast.removeClass('success error warning').addClass(type);
    icon.removeClass().addClass(type === 'success' ? 'bi-check-circle-fill' : (type === 'error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill'));
    toast.find('.toast-text').text(message);
    toast.addClass('show');
    setTimeout(() => toast.removeClass('show'), 3500);
}

$(window).on('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});