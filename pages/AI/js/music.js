// ==========================================
// 🚀 MUSIC GENERATION JS
// ==========================================

// --- Global Variables ---
let musicPlayer = document.getElementById('musicPlayer');
let currentPlayingId = null;
let autoRefreshInterval = null;

// Bulk Selection
let isAllChecked = false;
let selectedItems = [];

let tempSettings = {
    style: '',
    mood: '',
    scenario: ''
};

function stripEmoji(text) {
    return text.replace(/([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g, '').trim();
}

function renderChips() {
    // Render Style
    let styleHtml = '';
    styleOptions.forEach(opt => {
        const cleanLabel = stripEmoji(opt.label); // Bỏ icon nhạc
        styleHtml += `<div class="chip-btn" data-val="${opt.val}" onclick="selectChip('style', '${opt.val}', this)">${cleanLabel}</div>`;
    });
    $('#modalStyleList').html(styleHtml);

    // Render Mood
    let moodHtml = '';
    moodOptions.forEach(opt => {
        const cleanLabel = stripEmoji(opt.label);
        moodHtml += `<div class="chip-btn" data-val="${opt.val}" onclick="selectChip('mood', '${opt.val}', this)">${cleanLabel}</div>`;
    });
    $('#modalMoodList').html(moodHtml);

    // Render Scenario
    let scenarioHtml = '';
    scenarioOptions.forEach(opt => {
        const cleanLabel = stripEmoji(opt.label);
        scenarioHtml += `<div class="chip-btn" data-val="${opt.val}" onclick="selectChip('scenario', '${opt.val}', this)">${cleanLabel}</div>`;
    });
    $('#modalScenarioList').html(scenarioHtml);
}
function openSettingsModal() {
    // Load giá trị hiện tại vào biến tạm
    tempSettings.style = $('#styleVal').val();
    tempSettings.mood = $('#moodVal').val();
    tempSettings.scenario = $('#scenarioVal').val();

    // Render lại giao diện active dựa trên giá trị
    updateModalUI();
    
    $('#settingsModal').addClass('show');
}

function closeSettingsModal() {
    $('#settingsModal').removeClass('show');
}

function selectChip(type, val, el) {
    // Cập nhật biến tạm (Logic: Bấm lại cái đang chọn -> Bỏ chọn)
    if (tempSettings[type] === val) {
        tempSettings[type] = ''; // Toggle off
    } else {
        tempSettings[type] = val; // Select
    }
    updateModalUI();
}

function updateModalUI() {
    // Xóa hết class active cũ
    $('.chip-btn').removeClass('active');

    // Add active cho các chip trùng với tempSettings
    if(tempSettings.style) $(`#modalStyleList .chip-btn[data-val="${tempSettings.style}"]`).addClass('active');
    if(tempSettings.mood) $(`#modalMoodList .chip-btn[data-val="${tempSettings.mood}"]`).addClass('active');
    if(tempSettings.scenario) $(`#modalScenarioList .chip-btn[data-val="${tempSettings.scenario}"]`).addClass('active');
}

function resetSettings() {
    tempSettings = { style: '', mood: '', scenario: '' };
    updateModalUI();
}

function saveSettings() {
    // Lưu từ biến tạm vào input chính
    $('#styleVal').val(tempSettings.style);
    $('#moodVal').val(tempSettings.mood);
    $('#scenarioVal').val(tempSettings.scenario);
    
    // Đổi màu nút setting nếu có cài đặt để user biết
    if (tempSettings.style || tempSettings.mood || tempSettings.scenario) {
        $('.btn-settings').css('border-color', '#fff').css('color', '#fff');
    } else {
        $('.btn-settings').css('border-color', '').css('color', '');
    }

    closeSettingsModal();
}
$(document).ready(function() {
    // ========== 1. KHÔI PHỤC DỮ LIỆU TỪ BỘ NHỚ ==========
    
    // A. Khôi phục Title
    let savedTitle = localStorage.getItem('music_title_draft');
    if (savedTitle) {
        $('#songTitle').val(savedTitle);
        $('#titleCount').text(savedTitle.length);
    }
    
    // B. Khôi phục Idea (Prompt)
    let savedIdea = localStorage.getItem('music_idea_draft');
    if (savedIdea) {
        $('#ideaInput').val(savedIdea);
        $('#ideaCount').text(savedIdea.length);
    }
    
    // C. 🔥 Khôi phục Lyrics
let savedUseLyrics = localStorage.getItem('music_use_lyrics');

if (savedUseLyrics === 'true') {
    // 1. Bật switch
    $('#lyricsSwitch').prop('checked', true);
    
    // 2. Hiện section NGAY LẬP TỨC
    $('#lyricsInputSection').show();
    
    // 3. Khôi phục nội dung lyrics
    let savedLyrics = localStorage.getItem('music_lyrics_draft');
    if (savedLyrics) {
        $('#lyricsInput').val(savedLyrics);
        $('#lyricsCount').text(savedLyrics.length);
    }
} else {
    // 🔥 ĐẢM BẢO ẨN NẾU KHÔNG CÓ FLAG
    $('#lyricsSwitch').prop('checked', false);
    $('#lyricsInputSection').hide();
}
    
    // D. Tính toán lại chi phí
    setTimeout(() => {
        updateCost();
    }, 100);
    
    // ========== 2. TIẾP TỤC CODE CŨ ==========
    // 1. Check API Key
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        showToast('error', 'Bạn chưa có API Key! Vui lòng liên hệ Admin.');
        $('#btnGenerate').prop('disabled', true);
    }

    // 2. Init UI
    renderDropdowns();
    loadHistory();
    setupMusicPlayer();
    startAutoRefresh();
    
    // 3. Char counters
    setupCharCounters();
    renderChips();

    // 4. Close dropdown event
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('.dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
    });
});
// ========== LƯU TỰ ĐỘNG KHI NHẬP ==========
$(document).ready(function() {
    
    // Lưu Title
    $('#songTitle').on('input', function() {
        let content = $(this).val();
        localStorage.setItem('music_title_draft', content);
        $('#titleCount').text(content.length);
    });
    
    // Lưu Idea
    $('#ideaInput').on('input', function() {
        let content = $(this).val();
        localStorage.setItem('music_idea_draft', content);
        $('#ideaCount').text(content.length);
    });
    
    // Lưu Lyrics
    $('#lyricsInput').on('input', function() {
        let content = $(this).val();
        localStorage.setItem('music_lyrics_draft', content);
        $('#lyricsCount').text(content.length);
    });
});
// Tính lại chi phí khi chọn số lượng
function updateCost(n) {
    // Nếu không truyền n thì lấy từ input ẩn
    if (!n) n = parseInt($('#trackCount').val());
    
    const costPerTrack = 1200; 
    const total = n * costPerTrack;
    $('#estimatedCost').text(`~${total.toLocaleString()}`);
}
// ========== DATA ==========
const styleOptions = [
    { val: "1", label: "Pop" },
    { val: "2", label: "Urban" },
    { val: "3", label: "Rock" },
    { val: "4", label: "Hip Hop" },
    { val: "5", label: "Electronic" },
    { val: "6", label: "Reggae" },
    { val: "7", label: "Blues" },
    { val: "8", label: "Jazz" },
    { val: "9", label: "Folk" },
    { val: "10", label: "Country" },
    { val: "11", label: "Classical" },
    { val: "12", label: "R&B" },
    { val: "13", label: "Disco" },
    { val: "15", label: "Experimental" },
    { val: "17", label: "World" },
    { val: "18", label: "Ethnic" }
];

const moodOptions = [
    { val: "1", label: "Relaxed" },
    { val: "2", label: "Happy" },
    { val: "3", label: "Energetic" },
    { val: "4", label: "Romantic" },
    { val: "5", label: "Sad" },
    { val: "6", label: "Angry" },
    { val: "7", label: "Inspired" },
    { val: "8", label: "Warm" },
    { val: "9", label: "Passionate" },
    { val: "10", label: "Joyful" },
    { val: "11", label: "Longing" }
];

const scenarioOptions = [
    { val: "1", label: "Coffee shop" },
    { val: "2", label: "Solitary walk" },
    { val: "3", label: "Travel" },
    { val: "4", label: "Sunset by the sea" },
    { val: "5", label: "Quiet evening" },
    { val: "6", label: "Late-night bar" },
    { val: "7", label: "Urban romance" },
    { val: "8", label: "City nightlife" },
    { val: "9", label: "Rainy night" },
    { val: "10", label: "Sunlit Shores" }
];
// ========== 1. DROPDOWNS ==========
function renderDropdowns() {
    // Style
    let styleHtml = '';
    styleOptions.forEach(opt => {
        styleHtml += `<div class="dropdown-item" onclick="selectStyle('${opt.val}', '${opt.label}', this)">${opt.label}</div>`;
    });
    $('#styleList').html(styleHtml);
    
    // Mood
    let moodHtml = '';
    moodOptions.forEach(opt => {
        moodHtml += `<div class="dropdown-item" onclick="selectMood('${opt.val}', '${opt.label}', this)">${opt.label}</div>`;
    });
    $('#moodList').html(moodHtml);
    
    // Scenario
    let scenarioHtml = '';
    scenarioOptions.forEach(opt => {
        scenarioHtml += `<div class="dropdown-item" onclick="selectScenario('${opt.val}', '${opt.label}', this)">${opt.label}</div>`;
    });
    $('#scenarioList').html(scenarioHtml);
}

function toggleDropdown(id) {
    $('.dropdown-menu').not('#' + id + ' .dropdown-menu').removeClass('show');
    $('.dropdown-trigger i').not('#' + id + ' .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    
    let menu = $('#' + id + ' .dropdown-menu');
    let icon = $('#' + id + ' .dropdown-trigger i');
    
    menu.toggleClass('show');
    icon.toggleClass('bi-chevron-down bi-chevron-up');
}

function selectStyle(val, label, element) {
    $('#styleLabel').text(label);
    $('#styleVal').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownStyle .dropdown-menu').removeClass('show');
    $('#dropdownStyle .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

function selectMood(val, label, element) {
    $('#moodLabel').text(label);
    $('#moodVal').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownMood .dropdown-menu').removeClass('show');
    $('#dropdownMood .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

function selectScenario(val, label, element) {
    $('#scenarioLabel').text(label);
    $('#scenarioVal').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownScenario .dropdown-menu').removeClass('show');
    $('#dropdownScenario .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
}

// ========== 2. TOGGLE LYRICS INPUT ==========
function toggleLyricsInput() {
    const isChecked = $('#lyricsSwitch').is(':checked');
    
    // 🔥 LƯU TRẠNG THÁI SWITCH NGAY KHI THAY ĐỔI
    localStorage.setItem('music_use_lyrics', isChecked ? 'true' : 'false');
    
    if (isChecked) {
        $('#lyricsInputSection').slideDown(300);
    } else {
        $('#lyricsInputSection').slideUp(300);
        
        // NẾU TẮT -> XÓA NỘI DUNG & XÓA BỘ NHỚ
        $('#lyricsInput').val('');
        $('#lyricsCount').text('0');
        localStorage.removeItem('music_lyrics_draft');
    }
}

// ========== 3. TRACK COUNT ==========
function selectTrackCount(n) {
    $('.track-btn').removeClass('active');
    $(`.track-btn[data-n="${n}"]`).addClass('active');
    $('#trackCount').val(n);
    
    // Update cost
    const costPerTrack = 1000;
    const total = n * costPerTrack;
    $('#estimatedCost').text(`~${total.toLocaleString()}`);
}

// ========== 4. CHAR COUNTERS ==========
function setupCharCounters() {
    $('#songTitle').on('input', function() {
        let content = $(this).val();
        $('#titleCount').text(content.length);
        localStorage.setItem('music_title_draft', content); // 🔥 LƯU LUÔN
    });
    
    $('#ideaInput').on('input', function() {
        let content = $(this).val();
        $('#ideaCount').text(content.length);
        localStorage.setItem('music_idea_draft', content); // 🔥 LƯU LUÔN
    });
    
    $('#lyricsInput').on('input', function() {
        let content = $(this).val();
        $('#lyricsCount').text(content.length);
        localStorage.setItem('music_lyrics_draft', content); // 🔥 LƯU LUÔN
    });
}

// ========== 5. GENERATE MUSIC ==========
function generateMusic() {
    // 1. CHECK API KEY
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        showToast('error', 'Bạn chưa có API Key!');
        return;
    }
    
    // 2. LẤY DỮ LIỆU TỪ FORM
    const title = $('#songTitle').val().trim();
    const n = parseInt($('#trackCount').val()) || 1;
    const styleId = $('#styleVal').val();
    const moodId = $('#moodVal').val();
    const scenarioId = $('#scenarioVal').val();
    const rewrite = $('#rewriteSwitch').is(':checked');
    const useLyrics = $('#lyricsSwitch').is(':checked');
    const idea = $('#ideaInput').val().trim();
    
    // 3. VALIDATE IDEA (BẮT BUỘC)
    if (idea.length === 0) {
        showToast('error', '❌ Mô tả ý tưởng không được để trống!');
        $('#ideaInput').focus();
        return;
    }
    
    // 4. VALIDATE LYRICS (NẾU BẬT SWITCH)
    let lyrics = '';
    if (useLyrics) {
        lyrics = $('#lyricsInput').val().trim();
        
        if (lyrics.length === 0) {
            showToast('error', '❌ Vui lòng nhập lời bài hát!');
            $('#lyricsInput').focus();
            return;
        }
    }
    
    // 5. CHUẨN BỊ DỮ LIỆU GỬI
    const data = {
        action: 'create_music',
        title: title,
        idea: idea,
        lyrics: lyrics,
        style_id: styleId,
        mood_id: moodId,
        scenario_id: scenarioId,
        n: n,
        rewrite_idea_switch: rewrite
    };
    
    // 6. DISABLE NÚT & HIỂN THỊ LOADING
    $('#btnGenerate').prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm"></span>
        <span>Đang xử lý...</span>
    `);
    
    // 7. GỌI API
    $.ajax({
        url: '/ajaxs/music.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        timeout: 120000,
        
        success: function(res) {
            if (res.status === 'success') {
                // ✅ THÀNH CÔNG
                showToast('success', `✅ Đã tạo ${n} task thành công! Đang xử lý...`);
                
                // 🔥 GIỮ NGUYÊN FORM - KHÔNG XÓA GÌ
                
                // Chỉ reload lịch sử
                loadHistory();
                
                // Cập nhật credits
                if (res.remain_credits !== undefined) {
                    $('#userCredits').text(res.remain_credits.toLocaleString());
                }
            } else {
                // ❌ LỖI TỪ BACKEND
                showFormattedError(res);
            }
        },
        
        error: function(xhr, status, error) {
            console.error('❌ Generate Error:', {xhr, status, error});
            
            // ❌ LỖI AJAX
            try {
                let errorData = JSON.parse(xhr.responseText);
                showFormattedError(errorData);
            } catch(e) {
                showToast('error', '❌ Lỗi kết nối hoặc timeout!');
            }
        },
        
        complete: function() {
            // 8. BẬT LẠI NÚT
            $('#btnGenerate').prop('disabled', false).html(`
                <i class="bi bi-play-circle-fill"></i>
                <span>Tạo ngay</span>
            `);
        }
    });
}
// ========================================
// 🎨 FORMAT ERROR MESSAGE
// ========================================
function showFormattedError(res) {
    let errorMessage = '';
    
    // 1. XỬ LÝ VALIDATION ERRORS (Array)
    if (res.errors && Array.isArray(res.errors) && res.errors.length > 0) {
        let errorList = [];
        let seenMessages = new Set(); // Tránh trùng lặp
        
        res.errors.forEach(err => {
            let msg = err.message || 'Lỗi không xác định';
            
            // Dịch sang tiếng Việt
            if (msg.includes('Music idea must be at least 20 characters')) {
                msg = '❌ Ý tưởng phải có ít nhất 20 ký tự';
            } else if (msg.includes('Music idea must be at least')) {
                let minChars = msg.match(/\d+/);
                msg = `❌ Ý tưởng phải có ít nhất ${minChars ? minChars[0] : '20'} ký tự`;
            } else if (msg.includes('Lyrics are required')) {
                msg = '❌ Vui lòng nhập lời bài hát';
            } else if (msg.includes('Lyrics must be at least 50 characters')) {
                msg = '❌ Lời bài hát phải có ít nhất 50 ký tự';
            } else if (msg.includes('Lyrics must be at least')) {
                let minChars = msg.match(/\d+/);
                msg = `❌ Lời bài hát phải có ít nhất ${minChars ? minChars[0] : '50'} ký tự`;
            }
            
            // Thêm vào list nếu chưa có
            if (!seenMessages.has(msg)) {
                seenMessages.add(msg);
                errorList.push(msg);
            }
        });
        
        errorMessage = errorList.join('\n');
    }
    // 2. LỖI CHUNG (String)
    else if (res.message) {
        errorMessage = res.message;
    }
    // 3. LỖI KHÔNG XÁC ĐỊNH
    else if (res.error) {
        errorMessage = res.error;
    }
    // 4. FALLBACK
    else {
        errorMessage = 'Có lỗi xảy ra, vui lòng thử lại!';
    }
    
    // 5. THÊM THÔNG BÁO HOÀN TIỀN (NẾU CÓ)
    if (res.refunded || (res.message && res.message.includes('Đã hoàn tiền'))) {
        errorMessage += '\n\n✅ Đã hoàn lại tín dụng.';
    }
    
    // 6. HIỂN THỊ MODAL (ĐẸP HƠN TOAST)
    showErrorModal(errorMessage);
}

// ========================================
// 🎨 SHOW ERROR MODAL
// ========================================
function showErrorModal(message) {
    // Xóa modal cũ nếu có
    $('#customErrorModal').remove();
    
    let html = `
    <div id="customErrorModal" style="
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
    ">
        <div style="
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        ">
            <div style="
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 16px;
                padding-bottom: 16px;
                border-bottom: 1px solid #333;
            ">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 32px; color: #ef4444;"></i>
                <h3 style="margin: 0; font-size: 18px; color: #fff;">Lỗi tạo nhạc</h3>
            </div>
            
            <div style="
                color: #ccc;
                font-size: 14px;
                line-height: 1.8;
                margin-bottom: 20px;
                white-space: pre-line;
            ">${message}</div>
            
            <button onclick="$('#customErrorModal').fadeOut(200, function(){ $(this).remove(); })" style="
                width: 100%;
                padding: 12px;
                background: #ef4444;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: 0.2s;
            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <i class="bi bi-x-circle"></i> Đóng
            </button>
        </div>
    </div>`;
    
    $('body').append(html);
    
    // Auto close sau 10 giây
    setTimeout(() => {
        $('#customErrorModal').fadeOut(200, function(){ $(this).remove(); });
    }, 10000);
}
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
        url: '/ajaxs/music.php',
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
        
        error: function() {
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
// ========== 6. HISTORY ==========
function loadHistory() {
    $.ajax({
        url: '/ajaxs/music.php',
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
                
                // 🔥 [MỚI] Nếu có task pending → Auto refresh sau 5s
                let hasPendingTasks = res.data.some(item => item.status === 'pending');
                if (hasPendingTasks) {
                    setTimeout(() => {
                        refreshHistory();
                    }, 5000);
                }
            } else {
                $('#historyList').html(`
                    <div class="history-empty">
                        <i class="bi bi-music-note-list"></i>
                        <span>Chưa có bản nhạc nào</span>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Load History Error:', {xhr, status, error});
            $('#historyList').html(`
                <div class="history-empty">
                    <i class="bi bi-wifi-off"></i>
                    <span>Lỗi kết nối</span>
                </div>
            `);
        }
    });
}

function renderHistory(data) {
    if (!data || data.length === 0) {
        $('#historyList').html(`
            <div class="history-empty">
                <i class="bi bi-music-note-list"></i>
                <span>Chưa có bản nhạc nào</span>
            </div>
        `);
        return;
    }
    
    let html = '';
    
    data.forEach(item => {
        const statusClass = item.status;
        
        // Format thời gian
        let timeStr = '';
        if (item.created_at) {
            const date = new Date(item.created_at);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            timeStr = `${hours}:${minutes} ${day}/${month}`;
        }

        // --- TRƯỜNG HỢP 1: HOÀN THÀNH (DONE) -> GỘP NHÓM ---
        if (item.status === 'done' && item.audio_urls && item.audio_urls.length > 0) {
            
            // 1. Mở thẻ Group
            html += `<div class="music-group" id="group-${item.task_id}">`;
            
            // 2. Header của Group (Chứa thông tin chung)
            html += `
                <div class="group-header">
                    <div class="group-info">
                        <div class="group-title" title="${item.title || item.prompt}">${item.title || 'Music Generation'}</div>
                        <div class="group-meta">
                            <span><i class="bi bi-clock"></i> ${timeStr}</span>
                            <span>•</span>
                            <span class="group-credits"><i class="bi bi-coin"></i> ${item.credit_cost || 0} credits</span>
                            <span>•</span>
                            <span>${item.style_name || 'AI Style'}</span>
                        </div>
                    </div>
                </div>
                <div class="group-tracks">
            `;

            // 3. Vòng lặp render từng bài hát con (Tracks)
            // 🔥 [MỚI] Tạo mảng chứa danh sách cần lấy thời gian
            let durationQueue = []; 

            item.audio_urls.forEach((audioUrl, index) => {
                const coverUrl = (item.cover_urls && item.cover_urls[index]) || '';
                const trackTitle = item.title 
                    ? `${item.title} #${index + 1}` 
                    : `Variation #${index + 1}`;

                const uniqueId = `${item.task_id}-${index}`;
                
                // 🔥 [MỚI] Đẩy vào hàng đợi để lát xử lý sau
                durationQueue.push({ id: uniqueId, url: audioUrl });

                html += `
                    <div class="music-item in-group" id="music-${uniqueId}" data-audio-url="${audioUrl}">
                        <div class="item-checkbox" onclick="toggleItemCheck(this, '${uniqueId}', '${item.status}', '${audioUrl}', ${item.id})"></div>
                        
                        <div class="music-cover" style="width: 48px; height: 48px;">
                            ${coverUrl ? 
                                `<img src="${coverUrl}" loading="lazy" onerror="this.style.display='none'; this.parentElement.querySelector('.placeholder-icon').style.display='flex';">` : 
                                ''
                            }
                            <i class="bi bi-music-note-beamed placeholder-icon" style="${coverUrl ? 'display:none;' : 'display:flex; font-size: 20px;'}"></i>
                            
                            <div class="play-overlay" onclick="togglePlayAudio('${uniqueId}')">
                                <i class="bi bi-play-fill" style="font-size: 24px;"></i>
                            </div>
                        </div>
                        
                        <div class="music-info">
                            <div class="music-title" style="font-size: 13px;">${trackTitle}</div>
                            
                            <div class="audio-controls" style="margin-top: 4px;">
                                <button class="btn-play-audio" onclick="togglePlayAudio('${uniqueId}')" style="width: 24px; height: 24px; border: 1px solid #555;">
                                    <i class="bi bi-play-fill" style="font-size: 10px;"></i>
                                </button>
                                
                                <div class="audio-progress">
                                    <span class="progress-time">0:00</span>
                                    <div class="progress-bar-container" onclick="seekAudio(event, '${uniqueId}')" style="width: 80px;">
                                        <div class="progress-bar-fill"></div>
                                    </div>
                                    <span class="progress-time total-time" id="duration-${uniqueId}">--:--</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="item-actions">
                            <button class="btn-action" onclick="downloadMusic('${audioUrl}', '${item.title}_v${index+1}')" title="Tải xuống">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            // 4. Đóng thẻ Group
            html += `</div></div>`;
            
            // 🔥 [MỚI] Xử lý lấy thời gian sau khi render xong HTML này
            // (Dùng setTimeout để đảm bảo HTML đã được chèn vào DOM)
            setTimeout(() => {
                durationQueue.forEach(d => {
                    getAudioDuration(d.url, d.id);
                });
            }, 500);

        // --- TRƯỜNG HỢP 2: ĐANG XỬ LÝ (PENDING/DOING) ---
        } else if (item.status === 'pending' || item.status === 'doing') {
            html += `
                <div class="history-item" id="task-${item.task_id}">
                    <div class="item-checkbox" onclick="toggleItemCheck(this, '${item.task_id}', '${item.status}', '', ${item.id})"></div>
                    
                    <div class="item-info">
                        <div class="item-title">${item.title || 'Đang khởi tạo...'}</div>
                        <div class="item-meta">${timeStr}</div>
                    </div>
                    
                    <div class="processing-indicator">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span>Đang xử lý (${item.n || 1} bài)</span>
                    </div>
                    
                    <div class="item-credits">
                        <div class="credits-amount">${item.credit_cost || 0}</div>
                        <div class="credits-status">Tín dụng</div>
                    </div>
                </div>
            `;
            checkTaskStatus(item.task_id);
            
        // --- TRƯỜNG HỢP 3: THẤT BẠI (FAILED) ---
        } else {
            html += `
                <div class="history-item failed" id="task-${item.task_id}">
                    <div class="item-checkbox" onclick="toggleItemCheck(this, '${item.task_id}', '${item.status}', '', ${item.id})"></div>
                    
                    <div class="item-info">
                        <div class="item-title">${item.title || 'Task failed'}</div>
                        <div class="item-meta error">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${item.error_message || 'Lỗi không xác định'}
                        </div>
                    </div>
                    
                    <div class="item-credits">
                        <div class="credits-amount">${item.credit_cost || 0}</div>
                        <div class="credits-status">Hoàn lại</div>
                    </div>
                </div>
            `;
        }
    });
    
    $('#historyList').html(html);
}
// ========== HÀM LẤY THỜI GIAN BÀI HÁT TỰ ĐỘNG ==========
function getAudioDuration(url, uniqueId) {
    let audio = new Audio(url);
    
    // Khi load được metadata (biết được thời lượng)
    audio.addEventListener('loadedmetadata', function() {
        let duration = audio.duration;
        let formatted = formatTime(duration);
        
        // Cập nhật lên giao diện
        $(`#duration-${uniqueId}`).text(formatted);
        
        // Giải phóng bộ nhớ
        audio = null;
    });

    // Nếu lỗi thì để mặc định
    audio.addEventListener('error', function() {
        $(`#duration-${uniqueId}`).text("0:00");
    });
}
// ========== AUDIO PLAYER ==========
let currentAudio = null;
let currentAudioId = null;

function togglePlayAudio(id) {
    const item = $(`#music-${id}`);
    const audioUrl = item.data('audio-url');
    const btn = item.find('.btn-play-audio');
    const icon = btn.find('i');
    
    if (currentAudioId === id && currentAudio && !currentAudio.paused) {
        currentAudio.pause();
        icon.removeClass('bi-pause-fill').addClass('bi-play-fill');
        btn.removeClass('playing');
    } else {
        if (currentAudio) {
            currentAudio.pause();
            $(`#music-${currentAudioId} .btn-play-audio`).removeClass('playing').find('i').removeClass('bi-pause-fill').addClass('bi-play-fill');
        }
        
        currentAudio = new Audio(audioUrl);
        currentAudioId = id;
        
        currentAudio.play();
        icon.removeClass('bi-play-fill').addClass('bi-pause-fill');
        btn.addClass('playing');
        
        currentAudio.ontimeupdate = () => updateProgress(id);
        currentAudio.onloadedmetadata = () => updateTotalTime(id);
        currentAudio.onended = () => {
            icon.removeClass('bi-pause-fill').addClass('bi-play-fill');
            btn.removeClass('playing');
            item.find('.progress-bar-fill').css('width', '0%');
            item.find('.progress-time:first').text('0:00');
        };
    }
}

function updateProgress(id) {
    if (!currentAudio) return;
    
    const item = $(`#music-${id}`);
    const current = currentAudio.currentTime;
    const duration = currentAudio.duration;
    
    const percent = (current / duration) * 100;
    item.find('.progress-bar-fill').css('width', percent + '%');
    item.find('.progress-time:first').text(formatTime(current));
}

function updateTotalTime(id) {
    if (!currentAudio) return;
    
    const item = $(`#music-${id}`);
    item.find('.total-time').text(formatTime(currentAudio.duration));
}

function seekAudio(event, id) {
    if (!currentAudio || currentAudioId !== id) return;
    
    const bar = $(event.currentTarget);
    const clickX = event.offsetX;
    const width = bar.width();
    const percent = clickX / width;
    
    currentAudio.currentTime = percent * currentAudio.duration;
}

function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function deleteItem(id) {
    if (!confirm('Xóa bản nhạc này?')) return;
    
    $.post('/ajaxs/music.php', {
        action: 'delete_item',
        id: id
    }, function(res) {
        if (res.status === 'success') {
            showToast('success', 'Đã xóa!');
            loadHistory();
        } else {
            showToast('error', res.message || 'Có lỗi xảy ra!');
        }
    }, 'json');
}

// ========== 7. MUSIC PLAYER ==========
function setupMusicPlayer() {
    if (musicPlayer) {
        musicPlayer.onended = function() {
            if (currentPlayingId) {
                $(`#music-${currentPlayingId} .play-overlay i`).removeClass('bi-pause-circle-fill').addClass('bi-play-circle-fill');
                currentPlayingId = null;
            }
        };
    }
}

function playMusic(id, url) {
    if (!url) return;
    
    if (currentPlayingId === id && !musicPlayer.paused) {
        musicPlayer.pause();
        $(`#music-${id} .play-overlay i`).removeClass('bi-pause-circle-fill').addClass('bi-play-circle-fill');
        currentPlayingId = null;
    } else {
        if (currentPlayingId) {
            $(`#music-${currentPlayingId} .play-overlay i`).removeClass('bi-pause-circle-fill').addClass('bi-play-circle-fill');
        }
        
        currentPlayingId = id;
        musicPlayer.src = url;
        musicPlayer.play();
        $(`#music-${id} .play-overlay i`).removeClass('bi-play-circle-fill').addClass('bi-pause-circle-fill');
    }
}

function downloadMusic(url, title) {
    const link = document.createElement('a');
    link.href = url;
    link.download = title + '.mp3';
    link.click();
}

// ========== 8. BULK SELECTION (ĐÃ SỬA LỖI) ==========
function toggleCheckAll() {
    isAllChecked = !isAllChecked;
    const btn = $('#btnCheckAll');
    const icon = btn.find('i');

    if (isAllChecked) {
        $('.item-checkbox').addClass('checked');
        icon.removeClass('bi-square').addClass('bi-check-square-fill');
        btn.addClass('active');

        selectedItems = [];
        $('.music-item, .history-item').each(function() {
            const el = $(this);
            // 1. Lấy ID
            const id = el.attr('id').replace('music-', '').replace('task-', '');
            
            // 2. Lấy Status (SỬA LỖI CRASH TẠI ĐÂY)
            // Thay vì tìm class status-indicator (không tồn tại), ta suy luận từ loại item
            let status = 'done'; // Mặc định coi là xong
            
            if (el.find('.processing-indicator').length > 0) {
                status = 'pending'; // Có vòng xoay -> đang xử lý
            } else if (el.hasClass('failed')) {
                status = 'failed';  // Có class failed -> lỗi
            } else if (el.hasClass('in-group')) {
                status = 'done';    // Nằm trong group -> đã xong
            }

            // 3. Lấy Audio URL (Sửa lại cho đúng với HTML renderHistory)
            // HTML renderHistory dùng data-audio-url trên thẻ cha, không phải onclick trên cover
            const audioUrl = el.attr('data-audio-url') || '';

            // 4. Lấy historyId (Parse từ onclick của checkbox)
            // Regex cũ match(/\d+$/) sai vì chuỗi kết thúc bằng dấu đóng ngoặc ')'
            const checkboxOnclick = el.find('.item-checkbox').attr('onclick');
            let historyId = 0;
            if (checkboxOnclick) {
                // Tìm số nằm trước dấu đóng ngoặc cuối cùng: , 1234)
                const match = checkboxOnclick.match(/,\s*(\d+)\s*\)/);
                if (match && match[1]) {
                    historyId = match[1];
                }
            }

            selectedItems.push({ id, status, audioUrl, historyId });
        });
    } else {
        $('.item-checkbox').removeClass('checked');
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
        btn.removeClass('active');
        selectedItems = [];
    }

    updateBulkActions();
}

function toggleItemCheck(element, id, status, audioUrl, historyId) {
    $(element).toggleClass('checked');
    
    const itemData = { id, status, audioUrl, historyId };
    const index = selectedItems.findIndex(item => item.id === id);
    
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

// ========== 9. BULK ACTIONS ==========
function bulkDownload() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 bản nhạc!');
        return;
    }
    
    selectedItems.forEach(item => {
        if (item.audioUrl && item.status === 'done') {
            // Extract URL from onclick attribute
            const match = item.audioUrl.match(/'([^']+)'\)/);
            if (match && match[1]) {
                const link = document.createElement('a');
                link.href = match[1];
                link.download = '';
                link.click();
            }
        }
    });
    
    showToast('success', `Đang tải ${selectedItems.length} bản nhạc...`);
}

function bulkDelete() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 item để xóa!');
        return;
    }
    
    if (!confirm(`Xóa ${selectedItems.length} item đã chọn?`)) return;
    
    const historyIds = selectedItems.map(item => item.historyId).filter(id => id);
    
    $.post('/ajaxs/music.php', {
        action: 'bulk_delete',
        history_ids: historyIds.join(',')
    }, function(res) {
        if (res.status === 'success') {
            showToast('success', `Đã xóa ${selectedItems.length} item!`);
            selectedItems = [];
            isAllChecked = false;
            loadHistory();
        } else {
            showToast('error', res.message || 'Có lỗi xảy ra!');
        }
    }, 'json');
}

// ========== 10. TASK STATUS CHECK (Sửa lại cho nhanh) ==========
function checkTaskStatus(taskId) {
    // Lưu timeout ID để có thể clear nếu cần
    const timeoutId = setTimeout(() => {
        const taskElement = $(`#task-${taskId}`);
        if (taskElement.length === 0) return;
        
        const statusIndicator = taskElement.find('.status-indicator');
        if (!statusIndicator.length) return;
        
        const currentStatus = statusIndicator.attr('class').replace('status-indicator ', '').trim();
        
        // Chỉ check khi status là pending hoặc doing
        if (currentStatus !== 'pending' && currentStatus !== 'doing') {
            return;
        }
        
        $.post('/ajaxs/music.php', { 
            action: 'check_status', 
            task_id: taskId 
        }, function(res) {
            if (res.status === 'success') {
                // Nếu server báo done/failed mà HTML vẫn chưa đổi -> Reload ngay
                if (res.task_status === 'done' || res.task_status === 'failed') {
                    console.log('Task finished, reloading history...');
                    loadHistory(); // Reload lại list để hiện nhạc và ảnh
                    
                    if (res.task_status === 'done') {
                        showToast('success', '✅ Tạo nhạc thành công!');
                    } else {
                        showToast('error', '❌ Tạo nhạc thất bại!');
                    }
                    
                    // Update credits
                    if (res.new_balance !== undefined) {
                        $('#userCredits').text(res.new_balance.toLocaleString());
                    }
                } else {
                    // Vẫn đang xử lý, tiếp tục check sau 2 giây (Trước là 5s)
                    checkTaskStatus(taskId);
                }
            } else {
                checkTaskStatus(taskId);
            }
        }, 'json').fail(() => {
            checkTaskStatus(taskId);
        });
    }, 2000); // <--- SỬA THÀNH 2000 (2 giây) ĐỂ NÓ CHECK LIÊN TỤC
}

function startAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    
    autoRefreshInterval = setInterval(() => {
        // Chỉ refresh khi có item đang pending/doing trên màn hình
        const hasPendingTasks = $('.status-pending, .status-doing').length > 0;
        
        if (hasPendingTasks) {
            // Không cần loadHistory() toàn bộ nếu checkTaskStatus đang chạy
            // Nhưng cứ để đây làm phương án dự phòng, giảm xuống 5s
            // loadHistory(); 
        }
    }, 5000); // <--- SỬA THÀNH 5000 (5 giây)
}

// ========== 11. UTILITIES ==========
function showToast(type, message) {
    const toast = $('#toast');
    const icon = toast.find('i');
    toast.removeClass('success error warning').addClass(type);
    icon.removeClass().addClass(
        type === 'success' ? 'bi-check-circle-fill' : 
        (type === 'error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill')
    );
    toast.find('.toast-text').text(message);
    toast.addClass('show');
    setTimeout(() => toast.removeClass('show'), 3500);
}

$(window).on('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});
// --- LOGIC CUSTOM DROPDOWN ---

// 1. Bật tắt dropdown
function toggleTrackDropdown() {
    $('#trackDropdown').toggleClass('open');
}

// 2. Chọn track
function chooseTrack(val, label, el) {
    // Cập nhật giao diện
    $('#selectedTrackLabel').text(label);
    $('.option-item').removeClass('selected');
    $(el).addClass('selected');
    
    // Cập nhật giá trị vào input ẩn
    $('#trackCount').val(val);
    
    // Đóng dropdown
    $('#trackDropdown').removeClass('open');
    
    // Tính lại tiền
    updateCost(val);
}



// 4. Click ra ngoài thì đóng dropdown
$(document).on('click', function(e) {
    if (!$(e.target).closest('.custom-select-container').length) {
        $('.custom-select-container').removeClass('open');
    }
});