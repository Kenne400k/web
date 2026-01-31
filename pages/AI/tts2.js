// pages/AI/tts2.js
// ========== GLOBAL VARIABLES ==========
let currentProvider = "elevenlabs";
let loadedVoices = { elevenlabs: [], minimax: [] };
let loadedModels = { elevenlabs: [], minimax: [] };
let allVoicesForFilter = [];
let isFilteringFavorites = false;
let favoriteVoices = JSON.parse(localStorage.getItem("favVoices")) || [];

// History & Audio
let currentOffset = 0;
let isLoadingHistory = false;
let hasMoreHistory = true;
let mainAudio = document.getElementById("globalAudio");
let previewAudio = document.getElementById("previewAudio");
let currentPlayingTaskId = null;
let currentPreviewUrl = null;
let selectedLanguage = "Auto";
let selectedMinimaxModel = "speech-2.6-hd";
let pendingDeleteId = null;
let pendingDeleteType = ""; // 'refund' hoặc 'history'
let pendingDeleteCost = 0;
let historyDataMap = {};
let detailedHistoryData = [];
let detailedProcessingTasks = [];
let detailedHistoryPerPage = 20;
let sharedVoicesRendered = 0;
let sharedVoicesRenderBatch = 50;
let isRenderingVoices = false;
let voiceGridScrollHandler = null;
let currentTab = "settings";
let pendingUploadFiles = null; // 🔥 [THÊM MỚI] Lưu file chờ chọn giọng

// ========== LANGUAGE SUPPORT ==========
const currentLang = document.documentElement.lang || "vi";

const langStrings = {
  vi: {
    selectVoice: "Chọn giọng nói...",
    processing: "Đang xử lý",
    done: "Hoàn thành",
    failed: "Thất bại",
    timeout: "Timeout",
    queued: "Hàng đợi",
    noVoicesFound: "Không tìm thấy giọng nói",
    clearFilters: "Xóa bộ lọc",
    searching: "Đang tìm kiếm...",
    notFound: "Không tìm thấy",
    deleteConfirm: "Bạn có chắc chắn muốn xóa?",
    deleteSuccess: "Đã xóa thành công",
    selectVoiceFirst: "Vui lòng chọn giọng nói trước!",
    enterText: "Vui lòng nhập văn bản!",
    copied: "Đã copy!",
    addedToFavorites: "Đã thêm vào yêu thích",
    removedFromFavorites: "Đã xóa khỏi yêu thích",
  },
  en: {
    selectVoice: "Select voice...",
    processing: "Processing",
    done: "Done",
    failed: "Failed",
    timeout: "Timeout",
    queued: "Queued",
    noVoicesFound: "No voices found",
    clearFilters: "Clear filters",
    searching: "Searching...",
    notFound: "Not found",
    deleteConfirm: "Are you sure you want to delete?",
    deleteSuccess: "Deleted successfully",
    selectVoiceFirst: "Please select a voice first!",
    enterText: "Please enter text!",
    copied: "Copied!",
    addedToFavorites: "Added to favorites",
    removedFromFavorites: "Removed from favorites",
  },
};

const jsTranslations = langStrings[currentLang];

// ==================================================================
// 🔥 DELAY TOOLTIP FUNCTIONS
// ==================================================================
let currentDelayProvider = 'elevenlabs';

function showDelayTooltip(event, provider) {
    event.stopPropagation();
    currentDelayProvider = provider;

    const btn = event.currentTarget;
    const tooltip = document.getElementById('delayTooltip');

    if (!tooltip) {
        console.log('delayTooltip element not found');
        return;
    }

    const rect = btn.getBoundingClientRect();

    tooltip.style.display = 'block';
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 12) + 'px';

    // Close on outside click
    setTimeout(() => {
        document.addEventListener('click', hideDelayTooltipOnClickOutside);
    }, 10);
}

function hideDelayTooltip() {
    const tooltip = document.getElementById('delayTooltip');
    if (tooltip) tooltip.style.display = 'none';
    document.removeEventListener('click', hideDelayTooltipOnClickOutside);
}

function hideDelayTooltipOnClickOutside(e) {
    const tooltip = document.getElementById('delayTooltip');
    if (tooltip && !tooltip.contains(e.target) && !e.target.closest('#btnDelayEleven') && !e.target.closest('#btnDelayMinimax')) {
        hideDelayTooltip();
    }
}

function insertDelay(seconds) {
    const textarea = document.getElementById('txtInput');
    if (!textarea) return;

    let delayTag = '';

    if (currentDelayProvider === 'elevenlabs') {
        delayTag = `<break time="${seconds}s"/>`;
    } else if (currentDelayProvider === 'minimax') {
        delayTag = `<#${seconds}#>`;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    textarea.value = text.substring(0, start) + delayTag + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + delayTag.length;
    textarea.focus();

    // Trigger input event
    textarea.dispatchEvent(new Event('input'));

    hideDelayTooltip();
    showNotification('success', currentLang === 'vi' ? `Đã thêm khoảng dừng ${seconds}s` : `Added ${seconds}s delay`);
}

// Hide tooltip on scroll
window.addEventListener('scroll', hideDelayTooltip, true);

// ========== MINIMAX ANNOUNCEMENT POPUP ==========
function showMinimaxAnnouncement() {
    let popup = document.getElementById('minimaxAnnouncementPopup');
    if (popup) {
        popup.style.display = 'flex';
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.style.transition = 'opacity 0.3s ease';
            popup.style.opacity = '1';
        }, 10);
    }
}

function closeMinimaxAnnouncement() {
    let popup = document.getElementById('minimaxAnnouncementPopup');
    if (popup) {
        popup.style.transition = 'opacity 0.2s ease';
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 200);
    }
}

// ==================================================================
// 🔔 NOTIFICATION SYSTEM
// ==================================================================
function showNotification(type, message, duration = 3000) {
  // Type: 'success', 'error', 'warning', 'info'

  const iconMap = {
    success: "bi-check-circle-fill",
    error: "bi-x-circle-fill",
    warning: "bi-exclamation-triangle-fill",
    info: "bi-info-circle-fill",
  };

  const colorMap = {
    success: "#4ade80",
    error: "#ef4444",
    warning: "#fbbf24",
    info: "#3b82f6",
  };

  const icon = iconMap[type] || iconMap.info;
  const color = colorMap[type] || colorMap.info;

  // Tạo notification element
  const notification = $(`
        <div class="custom-notification" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1a1a1a;
            border: 1px solid ${color};
            border-left: 4px solid ${color};
            border-radius: 8px;
            padding: 16px 20px;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease-out;
        ">
            <i class="bi ${icon}" style="font-size: 20px; color: ${color};"></i>
            <div style="flex: 1; color: #fff; font-size: 14px; line-height: 1.4;">${message}</div>
            <i class="bi bi-x-lg" style="font-size: 14px; color: #888; cursor: pointer; padding: 4px;" onclick="$(this).closest('.custom-notification').fadeOut(200, function(){ $(this).remove(); })"></i>
        </div>
    `);

  // Thêm vào body
  $("body").append(notification);

  // Tự động đóng sau duration
  setTimeout(() => {
    notification.fadeOut(300, function () {
      $(this).remove();
    });
  }, duration);
}

// Animation CSS (thêm vào <style> nếu chưa có)
if (!$("#notification-styles").length) {
  $("head").append(`
        <style id="notification-styles">
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .custom-notification:hover {
                box-shadow: 0 6px 25px rgba(0,0,0,0.7) !important;
            }
        </style>
    `);
}
// ========== HELPER: PARSE CUSTOM DATE FORMAT ==========
function parseCustomDateTime(dateStr) {
  if (!dateStr || typeof dateStr !== "string") {
    return null;
  }

  // ✅ FORMAT 1: DD/MM/YYYY HH:mm (ElevenLabs/AI33)
  let regex1 =
    /^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/;
  let match1 = dateStr.match(regex1);

  if (match1) {
    let [_, day, month, year, hour, minute, second] = match1;

    let date = new Date(
      parseInt(year),
      parseInt(month) - 1,
      parseInt(day),
      parseInt(hour),
      parseInt(minute),
      parseInt(second || 0),
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
      parseInt(second || 0),
    );

    if (!isNaN(date.getTime())) {
      return date.getTime();
    }
  }

  // ✅ FALLBACK: Try ISO format (YYYY-MM-DDTHH:mm:ss)
  try {
    let isoStr = dateStr.replace(" ", "T");
    let timestamp = new Date(isoStr).getTime();

    if (!isNaN(timestamp)) {
      return timestamp;
    }
  } catch (e) {
    // Ignore
  }

  console.warn("⚠️ Invalid date format:", dateStr);
  return null;
}
// ========================================
// 🛑 POPUP LIMIT REQUEST (RATE LIMIT) - BLACK & WHITE THEME
// ========================================
function showRateLimitPopup(message) {
    $('#rateLimitPopup').remove();
    let html = `
    <div id="rateLimitPopup" style="
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999999; opacity: 0; animation: rlFadeIn 0.3s forwards;
    ">
        <div style="
            background: #000000; border: 1px solid #333; border-radius: 16px;
            padding: 32px; max-width: 420px; width: 90%; text-align: center; 
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.9);
            transform: scale(0.9); animation: rlScaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        ">
            <div style="
                width: 70px; height: 70px; background: rgba(255, 255, 255, 0.1);
                border-radius: 50%; display: flex; align-items: center; justify-content: center;
                margin: 0 auto 20px auto;
            ">
                <i class="bi bi-hourglass-split" style="font-size: 36px; color: #ffffff;"></i>
            </div>
            <h3 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 10px;">Thao tác quá nhanh!</h3>
            <p style="color: #cccccc; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">${message}</p>
            <button onclick="$('#rateLimitPopup').fadeOut(200, function(){ $(this).remove(); })" style="
                background: #ffffff; color: #000000; border: none;
                padding: 12px 30px; border-radius: 8px; font-weight: 700; font-size: 14px;
                cursor: pointer; width: 100%; transition: transform 0.2s, opacity 0.2s;
            " onmouseover="this.style.opacity='0.9'; this.style.transform='scale(1.02)'" 
              onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                Đã hiểu, tôi sẽ đợi
            </button>
        </div>
    </div>
    <style>
        @keyframes rlFadeIn { to { opacity: 1; } }
        @keyframes rlScaleUp { to { transform: scale(1); } }
    </style>
    `;
    $('body').append(html);
}
// ========================================
// 🔤 CHUẨN HÓA TIẾNG VIỆT (Để AI đọc chuẩn)
// ========================================
function normalizeVietnamese() {
  let text = $("#txtInput").val();

  if (!text || text.trim() === "") {
    showToast("⚠️ Vui lòng nhập văn bản trước!");
    return;
  }

  // Dictionary: Cách viết thường -> Cách viết để AI đọc chuẩn
  const vnPronunciationMap = {
    ai: "aai",
    im: "yim",
  };

  let result = text;

  // Duyệt qua từng cặp trong dictionary
  Object.keys(vnPronunciationMap).forEach((key) => {
    // Tạo regex để thay thế (match whole word)
    let regex = new RegExp("\\b" + key + "\\b", "gi");

    result = result.replace(regex, function (match) {
      let replacement = vnPronunciationMap[key.toLowerCase()];

      // Giữ nguyên chữ hoa/thường
      if (match === match.toUpperCase()) {
        return replacement.toUpperCase();
      }
      if (match[0] === match[0].toUpperCase()) {
        return replacement.charAt(0).toUpperCase() + replacement.slice(1);
      }
      return replacement;
    });
  });

  // Cập nhật lại textarea
  $("#txtInput").val(result);
  localStorage.setItem("tts_input_draft", result);
  togglePlaceholder();
  updateEstimatedCost();

  showToast("✅ Đã chuẩn hóa để AI đọc chuẩn!");
}

// ========================================
// 🗑️ XÓA TOÀN BỘ VĂN BẢN (MỞ POPUP)
// ========================================
function clearTextInput() {
  let currentText = $("#txtInput").val();

  // 1. Kiểm tra có text không
  if (!currentText || currentText.trim() === "") {
    showToast("⚠️ Không có văn bản để xóa");
    return;
  }

  // 2. Hiển thị preview text trong popup
  let previewText = currentText.substring(0, 300); // Lấy 300 ký tự đầu
  if (currentText.length > 300) {
    previewText += "...";
  }

  $("#clearTextPreview").text(previewText);

  // 3. Mở popup xác nhận
  $("#clearTextModal")
    .css("display", "flex")
    .hide()
    .fadeIn(200)
    .addClass("show");
}

// ========================================
// 🔒 ĐÓNG POPUP XÓA VĂN BẢN
// ========================================
function closeClearTextModal() {
  $("#clearTextModal").removeClass("show").fadeOut(200);
}

// ========================================
// ✅ XÁC NHẬN XÓA (KHI CLICK "XÓA" TRONG POPUP)
// ========================================
function confirmClearText() {
  // 1. Xóa nội dung textarea
  $("#txtInput").val("");

  // 2. Xóa bộ nhớ tạm
  localStorage.removeItem("tts_input_draft");
  localStorage.removeItem("tts_filename");
  localStorage.removeItem("tts_is_srt");

  // 3. Reset các biến cờ
  window.isSrtFile = false;

  // 4. Ẩn các thông tin file đã tải
  $("#fileNameDisplay").hide().text("");
  $("#srtFeeInfo").hide();

  // 5. Reset chi phí ước tính
  updateEstimatedCost();

  // 6. Hiện lại placeholder
  togglePlaceholder();

  // 7. Đóng popup
  closeClearTextModal();

  // 8. Thông báo thành công
  showToast("✅ Đã xóa toàn bộ văn bản");

  // 9. Focus vào textarea
  setTimeout(() => {
    $("#txtInput").focus();
  }, 300);
}
function openDetailedHistory() {
  $("#detailedHistoryModal").fadeIn(200);
  loadDetailedHistoryData();
}

function closeDetailedHistory() {
  $("#detailedHistoryModal").fadeOut(200);
}
function loadDetailedHistoryData(page = 1) {
  let $listContainer = $("#detailedHistoryList");

  $listContainer.html(
    '<div style="text-align:center; padding:50px;"><div class="spinner-border text-primary"></div></div>',
  );

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "get_history_sidebar",
      limit: detailedHistoryPerPage || 20,
      page: page,
    },
    dataType: "json",
    timeout: 10000,
    success: function (res) {
      console.log("✅ Parsed Response:", res);

      if (res.status === "success" && res.data) {
        console.log("📋 Total items:", res.data.length);

        detailedHistoryData = res.data;
        renderDetailedList(res.data);

        if (res.total_pages > 1) {
          renderDetailedPagination(res.page, res.total_pages, res.total);
        }
      } else {
        $listContainer.html(`
                    <div style="padding:20px; text-align:center; color:#dc3545;">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${res.message || "Lỗi tải dữ liệu"}
                    </div>
                `);
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ AJAX error:", error);
      console.error("❌ Status:", status);
      console.error("❌ Response:", xhr.responseText);

      $listContainer.html(`
                <div style="padding:20px; text-align:center; color:#dc3545;">
                    <i class="fas fa-exclamation-circle"></i> 
                    Không thể kết nối đến server
                    <pre style="color:#999; font-size:11px; margin-top:10px; max-height:200px; overflow:auto;">${xhr.responseText.substring(0, 500)}</pre>
                </div>
            `);
    },
  });
}
// Biến toàn cục để lưu trữ các task ID cần được theo dõi (polling)
//let detailedProcessingTasks = [];
function renderDetailedPagination(currentPage, totalPages, totalItems) {
  let $pagination = $("#detailedHistoryPagination");

  if (!$pagination.length) {
    // Tạo container nếu chưa có
    $("#detailedHistoryModal .modal-body").append(
      '<div id="detailedHistoryPagination" class="mt-3"></div>',
    );
    $pagination = $("#detailedHistoryPagination");
  }

  let html = '<nav><ul class="pagination justify-content-center">';

  // Previous button
  html += `<li class="page-item ${currentPage === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${currentPage - 1}">Trước</a>
    </li>`;

  // Page numbers (show max 5 pages)
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, currentPage + 2);

  for (let i = startPage; i <= endPage; i++) {
    html += `<li class="page-item ${i === currentPage ? "active" : ""}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
  }

  // Next button
  html += `<li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">Sau</a>
    </li>`;

  html += `</ul></nav>`;
  html += `<div class="text-center text-muted small">Tổng ${totalItems} tasks</div>`;

  $pagination.html(html);

  // Event handler
  $pagination.find(".page-link").on("click", function (e) {
    e.preventDefault();
    let page = parseInt($(this).data("page"));
    if (page >= 1 && page <= totalPages) {
      loadDetailedHistoryData(page);
    }
  });
}
// ========================================
// 🔄 CẬP NHẬT TOOLTIP THEO REAL-TIME
// ========================================
function updateCostTooltip() {
  // 1. Xác định Provider
  const provider = currentProvider === "minimax" ? "Minimax" : "ElevenLabs";
  $("#tooltipProviderName").text(provider);

  // ============================================
  // 2. XỬ LÝ MINIMAX
  // ============================================
  if (currentProvider === "minimax") {
    // A. Model Fee
    let isHDModel =
      selectedMinimaxModel === "speech-2.6-hd" ||
      selectedMinimaxModel === "speech-02-hd";

    let modelName = $("#selectedMinimaxModel").text();
    let modelFee = isHDModel ? "x1.15" : "x1.0";

    $("#tooltipModelFee").text(modelFee);
    $("#tooltipModel").html(
      `• Model (<span style="color: #667eea;">${modelName}</span>): <span style="color: #fff; font-weight: 600;">${modelFee}</span>`,
    );

    // B. Voice Fee
    let isClone = false;
    let voiceId = $("#voiceIdVal").val();

    // Check từ Tab
    if (
      typeof currentVoiceTab !== "undefined" &&
      currentVoiceTab === "cloned"
    ) {
      isClone = true;
    }

    // Check từ tên
    if (!isClone) {
      let voiceName = $("#selectedVoiceName").text().toLowerCase();
      if (voiceName.includes("clone")) {
        isClone = true;
      }
    }

    // Check từ data
    if (
      !isClone &&
      voiceId &&
      typeof loadedVoices !== "undefined" &&
      loadedVoices.minimax
    ) {
      let voiceObj = loadedVoices.minimax.find((v) => v.id == voiceId);
      if (
        voiceObj &&
        (voiceObj.source === "cloned" ||
          (voiceObj.tags && voiceObj.tags.includes("Clone")))
      ) {
        isClone = true;
      }
    }

    let voiceFee = isClone ? "x1.3" : "x1.0";
    let voiceType = isClone ? "Clone" : "Thư viện";

    $("#tooltipVoiceFee").text(voiceFee);
    $("#tooltipVoice")
      .html(
        `• Voice (<span style="color: #667eea;">${voiceType}</span>): <span style="color: #fff; font-weight: 600;">${voiceFee}</span>`,
      )
      .show();

    // C. SRT Fee
    let hasSrt =
      $("#minimaxSubtitleCheck").is(":checked") || window.isSrtFile === true;
    if (hasSrt) {
      $("#tooltipSrt").show();
    } else {
      $("#tooltipSrt").hide();
    }

    // D. Formula
    let formulaParts = ["Ký tự", modelFee, voiceFee];
    if (hasSrt) formulaParts.push("x1.15");
    $("#tooltipFormula").text(formulaParts.join(" × "));
  }

  // ============================================
  // 3. XỬ LÝ ELEVENLABS
  // ============================================
  else {
    // A. Lấy tên model và làm sạch
    let currentModelName = $("#selectedModelName").text();

    // 🔥 XÓA TẤT CẢ BADGE ĐỂ KIỂM TRA ĐÚNG TÊN GỐC
    let cleanModelName = currentModelName
      .replace(/\(Đắt hơn\)/gi, "")
      .replace(/\(rẻ hơn\)/gi, "")
      .replace(/\(Không dùng cho Tiếng Việt\)/gi, "")
      .replace(/\([^)]*%[^)]*\)/g, "") // Xóa badge kiểu "(30% rẻ hơn)"
      .trim();

    console.log("🔍 Clean Model Name:", cleanModelName); // Debug

    // B. Kiểm tra V3
    let isV3 = cleanModelName.toLowerCase().includes("v3");
    let modelFee = isV3 ? "x1.3" : "x1.0";

    console.log("✅ Is V3?", isV3, "| Fee:", modelFee); // Debug

    // C. Hiển thị trong tooltip
    $("#tooltipModelFee").text(modelFee);
    $("#tooltipModel").html(
      `• Model (<span style="color: #667eea;">${cleanModelName}</span>): <span style="color: #fff; font-weight: 600;">${modelFee}</span>`,
    );

    // D. Ẩn Voice Fee
    $("#tooltipVoice").hide();

    // E. SRT Fee
    let hasSrt =
      $("#subtitleCheck").is(":checked") || window.isSrtFile === true;
    if (hasSrt) {
      $("#tooltipSrt").show();
    } else {
      $("#tooltipSrt").hide();
    }

    // F. Formula
    let formulaParts = ["Ký tự", modelFee];
    if (hasSrt) formulaParts.push("x1.15");
    $("#tooltipFormula").text(formulaParts.join(" × "));
  }
}
function renderDetailedList(data) {
  let html = "";
  detailedProcessingTasks = []; // Reset danh sách task đang xử lý

  // 🔥 Dừng tất cả interval đang chạy trước khi render mới
  for (const taskId in detailedIntervals) {
    clearInterval(detailedIntervals[taskId]);
  }
  detailedIntervals = {}; // Reset object

  if (!data || data.length === 0) {
    $("#detailedHistoryList").html(
      '<div style="padding:20px; text-align:center; color:#666;">Chưa có dữ liệu</div>',
    );
    return;
  }

  console.log("🎨 Rendering", data.length, "tasks");

  data.forEach((item, index) => {
    // 🔥 CHỈ VALIDATE task_id (BỎ QUA id)
    if (!item.task_id) {
      console.warn(`⚠️ Skipping item ${index} - Missing task_id`);
      return;
    }

    let statusBadge = "";
    let contentArea = "";
    let creditLabel = "Tín dụng sử dụng";
    // 🔥 PARSE TIMESTAMP
    let createdTimeMs = parseCustomDateTime(item.created_at);
    if (!createdTimeMs || isNaN(createdTimeMs)) {
      createdTimeMs = Date.now();
    }

    // Icon Provider
    let providerLogo =
      typeof getProviderLogo === "function"
        ? getProviderLogo(item.provider)
        : "";

    // 🔥 XỬ LÝ TEXT AN TOÀN (CÓ FALLBACK ĐẦY ĐỦ)
    let rawText = item.text_input || item.text || item.content || "";

    let safeText = rawText
      .replace(/'/g, "\\'")
      .replace(/"/g, "&quot;")
      .replace(/(\r\n|\n|\r)/g, " ")
      .substring(0, 500); // Cắt tối đa 500 ký tự cho tooltip

    let displayText = rawText.trim() || "(Không có nội dung)";

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
    let exportBtnHtml = "";
    let isGenAiTask = item.provider === "minimax" || item.is_genai_backup == 1;

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
    if (item.status === "done") {
      statusBadge = `<span class="dh-status-badge dh-badge-done">Xong</span>`;
      creditLabel = "Tín dụng sử dụng";

      // 🔥 [SỬA] LẤY DURATION TỪ NHIỀU NGUỒN
      let duration =
        item.duration || (item.metadata ? item.metadata.duration : null);
      let durationText = duration ? formatTime(duration) : "--:--";

      // 🔥 DROPDOWN DOWNLOAD - DÙNG downloadViaProxy()
      let downloadDropdownHtml = `
        <div class="dh-download-wrapper" style="position: relative;">
            <button class="dh-download-btn" onclick="toggleDownloadMenu(event, '${item.task_id}')" title="Tải xuống">
                <i class="bi bi-download"></i>
            </button>
            
            <div class="dh-download-menu" id="download-menu-${item.task_id}" style="display: none;">
                <div class="dh-download-header">Tải xuống (hết hạn sau 72 giờ)</div>
                
                ${
                  item.audio_url
                    ? `
                <a href="javascript:void(0)" 
                   onclick="downloadViaProxy('${item.audio_url}', 'audio_${item.task_id}.mp3', '${safeText}')" 
                   class="dh-download-item">
                    <i class="bi bi-music-note-beamed"></i>
                    <span>Audio</span>
                </a>`
                    : `
                <div class="dh-download-item dh-download-disabled">
                    <i class="bi bi-music-note-beamed"></i>
                    <span>Audio</span>
                </div>`
                }
                
                ${
                  item.srt_url
                    ? `
                <a href="javascript:void(0)" 
                   onclick="downloadViaProxy('${item.srt_url}', 'subtitle_${item.task_id}.srt', '${safeText}')" 
                   class="dh-download-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Phụ đề (SRT)</span>
                </a>`
                    : `
                <div class="dh-download-item dh-download-disabled">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Phụ đề (SRT)</span>
                </div>`
                }
            </div>
        </div>
    `;

      // 🔥 [THÊM NÚT REMAKE]
      let remakeBtn = `
        <button class="dh-remake-btn" onclick="openRemakeModal('${item.task_id}')" title="Tạo lại">
            <i class="bi bi-arrow-repeat"></i>
        </button>
    `;

      // Gom nhóm: [Remake] + [Dropdown] + [Delete]
      let actionGroup = `
        <div style="display: flex; align-items: center; gap: 5px; margin-left: auto;">
            ${remakeBtn}
            ${downloadDropdownHtml}
            ${deleteBtnHtml}
        </div>
    `;

      contentArea = `
        <div class="dh-player" id="dh-player-${item.task_id}">
            <button class="dh-play-btn" id="dh-play-btn-${item.task_id}" 
                    onclick="playAudio('${item.task_id}', '${item.audio_url}')"
                    ${!item.audio_url ? "disabled" : ""}>
                <i class="bi bi-play-fill"></i>
            </button>
            <div class="dh-progress-track" onclick="seekAudio(event, '${item.task_id}', true)"> 
                <div class="dh-progress-bar" id="dh-progress-${item.task_id}" style="width: 0%"></div>
            </div>
            <div class="dh-timer" id="dh-timer-${item.task_id}" 
                 data-audio-url="${item.audio_url || ""}" 
                 data-duration="${duration || ""}">0:00 / ${durationText}</div>
            ${actionGroup} 
        </div>
    `;

      // 🔥 [THÊM MỚI] TỰ ĐỘNG LOAD DURATION NẾU CHƯA CÓ
      if (!duration && item.audio_url) {
        setTimeout(() => {
          loadAudioDuration(item.task_id, item.audio_url);
        }, 100);
      }
    }
    // ❌ TRẠNG THÁI: FAILED
    else if (item.status === "failed") {
      statusBadge = `<span class="dh-status-badge dh-badge-error">Lỗi</span>`;
      creditLabel = "Đã hoàn trả";
      // Layout: Text lỗi --- Nút Xóa
      contentArea = `
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                    <div class="dh-status-text dh-text-error"><i class="bi bi-exclamation-circle"></i> ${item.error_message || "Lỗi không xác định"}</div>
                    ${deleteBtnHtml}
                </div>`;
    }
    // ⏳ TRẠNG THÁI: PROCESSING
    else if (
      ["queued", "pending", "processing", "doing"].includes(item.status)
    ) {
      let elapsedMs = Date.now() - createdTimeMs;
      let elapsedSeconds = Math.floor(elapsedMs / 1000);
      let initialTimeString =
        typeof formatTime === "function" ? formatTime(elapsedSeconds) : "0:00";
      let currentProgress = parseInt(item.progress) || 0;

      // Add to polling list
      detailedProcessingTasks.push({
        taskId: item.task_id,
        historyId: item.id,
        startTime: createdTimeMs,
        status: item.status,
      });

      statusBadge = `<span class="dh-status-badge dh-badge-processing">Đang xử lý</span>`;
      creditLabel = "Tín dụng đóng băng";

      // 🔥 [SỬA ĐOẠN NÀY] Logic hiển thị Text
      let initialText = "";

      if (item.status === "queued") {
        initialText = item.queue_position
          ? `Hàng đợi #${item.queue_position}`
          : `Hàng đợi`;
      } else {
        // Nếu là GenAI Backup -> Chỉ hiện "Đang xử lý" (Bỏ %)
        if (item.is_genai_backup == 1) {
          initialText = "Đang xử lý";
        } else {
          // Nếu là thường -> Hiện %
          initialText =
            currentProgress > 0 ? `Xử lý ${currentProgress}%` : `Đang xử lý`;
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
    if (
      item.created_at &&
      (item.created_at.includes("T") || item.created_at.includes("-"))
    ) {
      let d = new Date(item.created_at);
      timeDisplay =
        d.getHours() +
        ":" +
        String(d.getMinutes()).padStart(2, "0") +
        " " +
        d.getDate() +
        "/" +
        (d.getMonth() + 1);
    }

    // ===============================================
    // 🔥 [RENDER ROW HTML]
    // ===============================================
    html += `
        <div class="dh-row" id="row-${item.task_id}" data-start-time="${createdTimeMs}" data-history-id="${item.id}">
            <div class="dh-checkbox-wrapper">
                <input type="checkbox" class="dh-item-checkbox" value="${item.task_id}" 
                    data-audio="${item.audio_url || ""}" 
                    data-srt="${item.srt_url || ""}" 
                    data-json="${item.json_url || ""}" 
                    onchange="updateBulkActions()">
            </div>
            
            <div class="dh-info">
                <div class="dh-time">
                    ${timeDisplay} 
                    ${providerLogo ? `<img src="${providerLogo}" class="dh-provider-icon">` : ""}
                </div>
                <div class="dh-text-preview" title="${safeText}">${displayText}</div>
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

  $("#detailedHistoryList").html(html);

  // Start Polling
  detailedProcessingTasks.forEach((task) => {
    if (typeof startDetailedPolling === "function") {
      startDetailedPolling(task.taskId, task.historyId, task.startTime);
    }
  });
}
// ========================================
// 🔄 MỞ POPUP REMAKE (CẢI TIẾN)
// ========================================
function openRemakeModal(taskId) {
  console.log("🔄 Opening remake modal for:", taskId);

  // 🔥 KIỂM TRA CACHE TRƯỚC
  let cachedData = historyDataMap[taskId];

  if (cachedData && cachedData.voice_id && cachedData.text_input) {
    console.log("✅ Using cached data from historyDataMap");
    displayRemakeModal(cachedData);
    return;
  }

  // 🔥 GỌI API
  console.log("🌐 Fetching task info from API...");

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "get_task_info",
      task_id: taskId,
    },
    dataType: "json",
    success: function (response) {
      console.log("✅ API Response:", response);

      if (response.status === "success") {
        let taskData = response.data;

        // Lưu vào cache
        historyDataMap[taskId] = taskData;

        displayRemakeModal(taskData);
      } else {
        showToast("❌ " + response.message);
      }
    },
    error: function (xhr, status, error) {
      // 🔥 IN RA TOÀN BỘ THÔNG TIN LỖI
      console.error("❌ Get task info error:", {
        status: xhr.status,
        statusText: xhr.statusText,
        responseText: xhr.responseText,
        responseJSON: xhr.responseJSON,
        error: error,
      });

      // 🔥 THỬ PARSE ERROR MESSAGE
      let errorMsg = "Không thể lấy thông tin task";
      try {
        let errData = JSON.parse(xhr.responseText);
        errorMsg = errData.message || errData.error || errorMsg;
      } catch (e) {
        if (xhr.responseText && xhr.responseText.length < 200) {
          errorMsg = xhr.responseText;
        }
      }

      showToast("❌ " + errorMsg);
    },
  });
}
function displayRemakeModal(taskData) {
  console.log("📊 Displaying Remake Modal | Task Data:", taskData);

  // ============================================================
  // 1. VALIDATE & LẤY TEXT
  // ============================================================

  // 🔥 Lấy text từ nhiều nguồn (ưu tiên cao → thấp)
  let originalText =
    taskData.text || taskData.text_input || taskData.content || "";

  // Kiểm tra text có tồn tại không
  if (!originalText || originalText.trim() === "") {
    console.error("❌ No text found in task data:", taskData);
    showNotification("error", "Không tìm thấy nội dung văn bản");
    closeRemakeModal();
    return;
  }

  console.log("📝 Original text length:", originalText.length);

  // Lưu Task ID
  pendingRemakeTaskId = taskData.task_id;

  // ============================================================
  // 2. XÁC ĐỊNH PROVIDER
  // ============================================================

  let provider = taskData.provider || "elevenlabs";
  let model_id = taskData.model_id || "";

  // Auto-detect provider từ model_id nếu chưa có
  if (!taskData.provider && model_id) {
    if (model_id.startsWith("speech-")) {
      provider = "minimax";
    } else if (model_id.startsWith("eleven_")) {
      provider = "elevenlabs";
    }
  }

  console.log("🎯 Provider:", provider, "| Model:", model_id);

  // ============================================================
  // 3. HIỂN THỊ TEXT PREVIEW
  // ============================================================

  let textPreview = originalText;
  if (originalText.length > 300) {
    textPreview = originalText.substring(0, 300) + "...";
  }

  $("#remakeTextPreview").text(textPreview);

  // ============================================================
  // 4. HIỂN THỊ SETTINGS THEO PROVIDER
  // ============================================================

  let settingsHtml = "";

  if (provider === "minimax") {
    // --- MINIMAX SETTINGS ---
    settingsHtml = `
            <div class="remake-settings-row">
                <span>🎙️ Giọng:</span>
                <strong>${taskData.voice_name || "Unknown"}</strong>
            </div>
            <div class="remake-settings-row">
                <span>🤖 Model:</span>
                <strong>${model_id || "speech-2.6-hd"}</strong>
            </div>
            <div class="remake-settings-row">
                <span>⚡ Tốc độ:</span>
                <strong>${parseFloat(taskData.speed || 1.0).toFixed(2)}x</strong>
            </div>
            <div class="remake-settings-row">
                <span>🎵 Cao độ:</span>
                <strong>${parseInt(taskData.pitch || 0)}</strong>
            </div>
            <div class="remake-settings-row">
                <span>🔊 Âm lượng:</span>
                <strong>${parseFloat(taskData.vol || 1.0).toFixed(2)}</strong>
            </div>
            <div class="remake-settings-row">
                <span>🌍 Ngôn ngữ:</span>
                <strong>${taskData.language_boost || "Auto"}</strong>
            </div>
        `;
  } else {
    // --- ELEVENLABS SETTINGS ---

    // 🔥 Chuyển đổi giá trị 0.0-1.0 → 0-100%
    let stability = parseFloat(taskData.stability || 0.5);
    let similarity = parseFloat(taskData.similarity || 0.75);
    let style = parseFloat(taskData.style || 0);

    if (stability <= 1) stability = Math.round(stability * 100);
    if (similarity <= 1) similarity = Math.round(similarity * 100);
    if (style <= 1) style = Math.round(style * 100);

    settingsHtml = `
            <div class="remake-settings-row">
                <span>🎙️ Giọng:</span>
                <strong>${taskData.voice_name || "Unknown"}</strong>
            </div>
            <div class="remake-settings-row">
                <span>🤖 Model:</span>
                <strong>${model_id || "eleven_multilingual_v2"}</strong>
            </div>
            <div class="remake-settings-row">
                <span>⚡ Tốc độ:</span>
                <strong>${parseFloat(taskData.speed || 1.0).toFixed(2)}x</strong>
            </div>
            <div class="remake-settings-row">
                <span>🎚️ Stability:</span>
                <strong>${stability}%</strong>
            </div>
            <div class="remake-settings-row">
                <span>🔗 Similarity:</span>
                <strong>${similarity}%</strong>
            </div>
            <div class="remake-settings-row">
                <span>🎨 Style:</span>
                <strong>${style}%</strong>
            </div>
            <div class="remake-settings-row">
                <span>📢 Speaker Boost:</span>
                <strong>${taskData.use_boost !== false ? "Bật" : "Tắt"}</strong>
            </div>
        `;
  }

  $("#remakeSettingsInfo").html(settingsHtml);

  // ============================================================
  // 5. SET CHECKBOX PHỤ ĐỀ
  // ============================================================

  const hadSubtitle =
    taskData.with_transcript == 1 || taskData.with_transcript === true;
  $("#remakeSubtitleCheck").prop("checked", hadSubtitle);

  console.log("📄 Subtitle checkbox:", hadSubtitle);

  // ============================================================
  // 6. LƯU DỮ LIỆU VÀO MODAL
  // ============================================================

  // Tạo object hoàn chỉnh với tất cả thông tin cần thiết
  const completeTaskData = {
    task_id: taskData.task_id,
    text: originalText, // 🔥 QUAN TRỌNG: Luôn có field 'text'
    text_input: originalText, // Backup
    provider: provider,
    voice_id: taskData.voice_id || "",
    voice_name: taskData.voice_name || "Unknown",
    model_id: model_id,
    credit_cost: parseInt(taskData.credit_cost || 0),
    with_transcript: hadSubtitle,

    // Settings cho Minimax
    speed: parseFloat(taskData.speed || 1.0),
    pitch: parseInt(taskData.pitch || 0),
    vol: parseFloat(taskData.vol || 1.0),
    language_boost: taskData.language_boost || "Auto",

    // Settings cho ElevenLabs
    stability: parseFloat(taskData.stability || 0.5),
    similarity: parseFloat(taskData.similarity || 0.75),
    style: parseFloat(taskData.style || 0),
    use_boost: taskData.use_boost !== false,
  };

  $("#remakeTaskModal").data("task-data", completeTaskData);
  $("#remakeTaskModal").data("original-cost", completeTaskData.credit_cost);

  console.log("💾 Saved to modal data:", {
    task_id: completeTaskData.task_id,
    text_length: completeTaskData.text.length,
    provider: completeTaskData.provider,
    cost: completeTaskData.credit_cost,
  });

  // ============================================================
  // 7. TÍNH TOÁN CHI PHÍ
  // ============================================================

  updateRemakeCost();

  // ============================================================
  // 8. VALIDATION & BUTTON STATE
  // ============================================================

  const currentBalance =
    parseInt($("#userCredits").text().replace(/,/g, "")) || 0;
  const $btnConfirm = $("#btnConfirmRemake");

  // Kiểm tra text có hợp lệ không
  const hasValidText =
    textPreview &&
    textPreview.trim() !== "" &&
    textPreview !== "Không có nội dung" &&
    originalText.length > 0;

  if (!hasValidText) {
    // Text không hợp lệ → Disable button
    $btnConfirm.prop("disabled", true).addClass("disabled");
    $btnConfirm.html(
      '<i class="bi bi-x-circle"></i> <span>Không có nội dung</span>',
    );
    console.warn("⚠️ Invalid text, button disabled");
  } else {
    // Text hợp lệ → Enable button
    $btnConfirm.prop("disabled", false).removeClass("disabled");
    $btnConfirm.html('<i class="bi bi-magic"></i> <span>Tạo lại ngay</span>');
    console.log("✅ Valid text, button enabled");
  }

  // ============================================================
  // 9. HIỂN THỊ MODAL
  // ============================================================

  $("#remakeTaskModal").css("display", "flex");
  setTimeout(() => {
    $("#remakeTaskModal").addClass("show");
  }, 10);

  console.log("✅ Remake modal opened successfully");
}
function updateRemakeCost() {
  const task = $("#remakeTaskModal").data("taskData");

  if (!task) return;

  const text = task.text || "";
  const charCount = text.length;
  const provider = task.provider || "elevenlabs";

  let baseCost = charCount;
  let costFactor = 1.0;
  let voiceMultiplier = 1.0;
  let srtMultiplier = 1.0;

  // 🔥 TÍNH CHI PHÍ THEO PROVIDER
  if (provider === "minimax") {
    const modelId = task.model_id || "speech-2.6-hd";

    // Model HD: x1.15
    if (modelId === "speech-2.6-hd" || modelId === "speech-02-hd") {
      costFactor = 1.15;
    }

    // Voice Clone: x1.3 (nếu có)
    if (task.voice_id && task.voice_id.startsWith("vc_")) {
      voiceMultiplier = 1.3;
    }
  } else {
    const modelId = task.model_id || "eleven_multilingual_v2";

    // Model v3: x1.3
    if (modelId.includes("v3") || modelId.includes("_v3")) {
      costFactor = 1.3;
    }
  }

  // 🔥 KIỂM TRA TOGGLE SRT (QUAN TRỌNG!)
  const withTranscript = $("#remakeSubtitleCheck").is(":checked");

  if (withTranscript) {
    srtMultiplier = 1.15;
  }

  // 🔥 TÍNH TOÁN CUỐI CÙNG
  baseCost = charCount * costFactor * voiceMultiplier * srtMultiplier;
  const finalCost = Math.max(1, Math.round(baseCost));

  console.log(
    "💰 Remake Cost | Chars:",
    charCount,
    "| Model:",
    costFactor,
    "| Voice:",
    voiceMultiplier,
    "| SRT:",
    srtMultiplier,
    "| Final:",
    finalCost,
  );

  $("#remakeCostDisplay").text(finalCost + " credits");
}
function confirmRemakeTask() {
  const task = $("#remakeTaskModal").data("taskData");

  if (!task) {
    showNotification("error", "Không tìm thấy thông tin task");
    return;
  }

  // 🔥 FIX: Lấy text an toàn
  const originalText = task.text || task.text_input || "";

  if (!originalText || originalText.trim() === "") {
    showNotification("error", "Không có nội dung để tạo lại");
    return;
  }

  const withTranscript = $("#remakeSubtitleCheck").is(":checked")
    ? true
    : false;

  const payload = {
    action: "create_speech",
    text: originalText, // 🔥 SỬA: Dùng biến đã kiểm tra
    provider: task.provider,
    voice_id: task.voice_id,
    with_transcript: withTranscript,
    model_id: task.model_id,
  };

  // Settings theo provider
  if (task.provider === "minimax") {
    payload.speed = parseFloat(task.speed) || 1.0;
    payload.pitch = parseInt(task.pitch) || 0;
    payload.vol = parseFloat(task.vol) || 1.0;
    payload.language_boost = task.language_boost || "Auto";
  } else {
    payload.speed = parseFloat(task.speed) || 1.0;

    // 🔥 FIX: CHUYỂN TỪ 0.0-1.0 → 0-100
    let stability = parseFloat(task.stability) || 0.5;
    let similarity = parseFloat(task.similarity) || 0.75;
    let style = parseFloat(task.style) || 0;

    if (stability <= 1) stability = Math.round(stability * 100);
    if (similarity <= 1) similarity = Math.round(similarity * 100);
    if (style <= 1) style = Math.round(style * 100);

    payload.stability = stability;
    payload.similarity = similarity;
    payload.style = style;
    payload.use_boost = task.use_boost !== false;
  }

  console.log("📤 Sending remake payload:", payload);

  // Disable button
  $("#btnConfirmRemake")
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span> Đang tạo...');

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: payload,
    success: function (response) {
      if (response.status === "success") {
        const newTaskId = response.task_id;

        console.log("✅ Remake success | New Task ID:", newTaskId);

        showNotification("success", "Task đã được tạo lại thành công!");
        closeRemakeModal();

        // Update credits
        if (response.new_balance !== undefined) {
          $("#userCredits").text(response.new_balance.toLocaleString());
        }

        // Chuyển sang tab History
        switchTab("history");

        // Refresh history và bắt đầu polling
        setTimeout(function () {
          refreshHistory();

          setTimeout(function () {
            startPollingForTask(newTaskId);
          }, 500);
        }, 1000);
      } else {
        showNotification("error", response.message || "Lỗi không xác định");
        $("#btnConfirmRemake")
          .prop("disabled", false)
          .html('<i class="bi bi-magic"></i> <span>Tạo lại ngay</span>');
      }
    },
    error: function (xhr) {
      console.error("❌ Remake error:", xhr);
      showNotification("error", "Lỗi kết nối");
      $("#btnConfirmRemake")
        .prop("disabled", false)
        .html('<i class="bi bi-magic"></i> <span>Tạo lại ngay</span>');
    },
  });
}
// Đóng Remake Modal khi click ngoài
$(document).on("click", "#remakeTaskModal", function (e) {
  if (e.target.id === "remakeTaskModal") {
    closeRemakeModal();
  }
});

// Đóng khi nhấn ESC
$(document).on("keydown", function (e) {
  if (e.key === "Escape" && $("#remakeTaskModal").is(":visible")) {
    closeRemakeModal();
  }
});
function renderHistoryItem(task) {
  const taskId = task.task_id;
  const status = task.status || "pending";

  // 🔥 QUAN TRỌNG: PHẢI CÓ data-task-id="${taskId}"
  const html = `
        <div class="history-item" data-task-id="${taskId}">
            <div class="hi-header">
                <div class="hi-status status-${status}">
                    ${status === "processing" ? "⏳" : status === "done" ? "✅" : status === "failed" ? "❌" : "⏳"}
                </div>
                <div class="hi-text">${escapeHtml(task.text_input || "")}</div>
            </div>
            
            ${
              status === "processing"
                ? `
                <div class="task-progress">
                    <div class="task-progress-bar" style="width: 0%;"></div>
                    <div class="task-progress-text">0%</div>
                </div>
            `
                : ""
            }
            
            ${
              status === "done"
                ? `
                <div class="hi-actions">
                    <button onclick="playAudio('${task.audio_url}', '${taskId}')">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <button onclick="downloadAudio('${task.audio_url}', '${taskId}')">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            `
                : ""
            }
        </div>
    `;

  return html;
}
// ==================================================================
// 🔄 START POLLING FOR SPECIFIC TASK
// ==================================================================
function startPollingForTask(taskId) {
  console.log("🔄 Starting polling for task:", taskId);

  // 🔥 FIX: TÌM ĐÚNG SELECTOR - ƯU TIÊN MODAL CHI TIẾT
  let taskCard = $(`#row-${taskId}`);

  // 🔥 FALLBACK: Nếu không có trong modal → tìm trong sidebar
  if (taskCard.length === 0) {
    taskCard = $(`.history-item[data-task-id="${taskId}"]`);
  }

  if (taskCard.length === 0) {
    console.warn("⚠️ Task card not found, retrying in 2s...");
    setTimeout(() => startPollingForTask(taskId), 2000);
    return;
  }

  // Kiểm tra xem đã có polling chưa
  if (taskCard.data("polling-active")) {
    console.log("⚠️ Polling already active for this task");
    return;
  }

  taskCard.data("polling-active", true);

  // Bắt đầu polling
  pollTaskStatus(taskId, taskCard);
}

// ==================================================================
// 🔄 POLL TASK STATUS (RECURSIVE) - HỖ TRỢ CẢ SIDEBAR VÀ MODAL
// ==================================================================
function pollTaskStatus(taskId, taskCard) {
  console.log("🔍 Polling task:", taskId);

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "check_status",
      task_id: taskId,
    },
    success: function (response) {
      if (response.status !== "success") {
        console.error("❌ Status check failed:", response);
        taskCard.data("polling-active", false);
        return;
      }

      const taskStatus = response.task_status;
      const progress = response.progress || 0;

      console.log(
        `📊 Task ${taskId} | Status: ${taskStatus} | Progress: ${progress}%`,
      );

      // 🔥 CẬP NHẬT PROGRESS BAR (HỖ TRỢ CẢ 2 LOẠI)
      // Thử tìm trong Modal chi tiết
      let progressBar = $(`#dh-progress-${taskId}`);
      let progressText = $(`#dh-time-elapsed-${taskId}`);

      // Fallback: Tìm trong Sidebar
      if (progressBar.length === 0) {
        progressBar = taskCard.find(".task-progress-bar");
        progressText = taskCard.find(".task-progress-text");
      }

      if (progressBar.length) {
        progressBar.css("width", progress + "%");
      }

      // 🔥 CẬP NHẬT TEXT (CHỈ HIỆN % CHO TASK THƯỜNG, BỎ % CHO GENAI)
      if (progressText.length) {
        let isGenAI = progressText.attr("data-genai") == "1";

        if (taskStatus === "processing") {
          if (isGenAI) {
            progressText.text("Đang xử lý");
          } else {
            progressText.text(progress + "%");
          }
        }
      }

      // 🔥 CẬP NHẬT STATUS BADGE
      const statusBadge = taskCard.find(".task-status-badge, .dh-status-badge");
      if (statusBadge.length) {
        statusBadge.removeClass(
          "status-pending status-processing status-done status-failed",
        );
        statusBadge.addClass("status-" + taskStatus);

        if (taskStatus === "processing") {
          statusBadge.text(progress + "%");
        } else {
          statusBadge.text(taskStatus.toUpperCase());
        }
      }

      // 🔥 NẾU XONG HOẶC LỖI → DỪNG POLLING
      if (taskStatus === "done") {
        console.log("✅ Task completed:", taskId);
        taskCard.data("polling-active", false);

        // Refresh để hiện audio
        setTimeout(() => refreshHistory(), 1000);

        showNotification("success", "Task hoàn thành!");
      } else if (taskStatus === "failed") {
        console.log("❌ Task failed:", taskId);
        taskCard.data("polling-active", false);

        showNotification("error", response.error_message || "Task thất bại");
      } else {
        // 🔄 TIẾP TỤC POLLING SAU 3S
        setTimeout(() => pollTaskStatus(taskId, taskCard), 3000);
      }
    },
    error: function (xhr) {
      console.error("❌ Polling error:", xhr);
      taskCard.data("polling-active", false);
    },
  });
}
// ========================================
// 🔒 ĐÓNG POPUP
// ========================================
function closeRemakeModal() {
  $("#remakeTaskModal").removeClass("show");
  setTimeout(() => {
    $("#remakeTaskModal").css("display", "none");
  }, 300);
  pendingRemakeTaskId = null;
}

$(document).on("click", "#btnConfirmRemake", function () {
  closeRemakeModal();
  // Logic tạo lại (đã có trong openRemakeModal)
});
// ========================================
// 🔥 TỰ ĐỘNG LOAD DURATION TỪ AUDIO
// ========================================
function loadAudioDuration(taskId, audioUrl) {
  if (!audioUrl) return;

  let tempAudio = new Audio(audioUrl);

  tempAudio.addEventListener("loadedmetadata", function () {
    let duration = tempAudio.duration;

    if (duration && !isNaN(duration)) {
      let durationText = formatTime(duration);

      // Cập nhật Modal chi tiết
      let $timer = $(`#dh-timer-${taskId}`);
      if ($timer.length) {
        let currentText = $timer.text();
        // Thay thế --:-- bằng duration thật
        $timer.text(currentText.replace("--:--", durationText));
        $timer.attr("data-duration", duration);
      }

      // Cập nhật Sidebar (nếu có)
      $(`#time-total-${taskId}`).text(durationText);

      // Lưu vào map
      if (historyDataMap[taskId]) {
        historyDataMap[taskId].duration = duration;
      }
    }
  });

  tempAudio.addEventListener("error", function () {
    console.warn("⚠️ Cannot load audio duration for:", taskId);
  });
}
// ========================================
// 🔥 TOGGLE DOWNLOAD DROPDOWN
// ========================================
function toggleDownloadMenu(event, taskId) {
  event.stopPropagation();

  const menuId = `#download-menu-${taskId}`;
  const $menu = $(menuId);

  // Đóng tất cả menu khác
  $(".dh-download-menu").not($menu).hide();

  // Toggle menu hiện tại
  $menu.toggle();
}

// Đóng dropdown khi click ra ngoài
$(document).on("click", function (e) {
  if (!$(e.target).closest(".dh-download-wrapper").length) {
    $(".dh-download-menu").hide();
  }
});
function refreshDetailedHistory() {
  console.log("🔄 Refreshing detailed history...");

  // 1. Hiệu ứng xoay icon
  const $icon = $("#dhRefreshIcon");
  $icon.addClass("spin-anim");

  // 2. Disable nút
  $(".dh-refresh-with-text").prop("disabled", true);

  // 3. Gọi lại API
  loadDetailedHistoryData(1);

  // 4. Tắt hiệu ứng sau 800ms
  setTimeout(() => {
    $icon.removeClass("spin-anim");
    $(".dh-refresh-with-text").prop("disabled", false);
  }, 800);
}

function requestCreateSrt(taskId, btnElement) {
  let originalContent = btnElement.innerHTML;
  $(btnElement).html(
    '<div class="spinner-border spinner-border-sm" role="status"></div>',
  );
  $(btnElement).prop("disabled", true);

  $.ajax({
    url: "/ajaxs/tts.php", // Đảm bảo đường dẫn đúng
    method: "POST",
    dataType: "json",
    data: {
      action: "export_custom_srt",
      task_id: taskId,
    },
    success: function (response) {
      if (response.status === "success" && response.download_url) {
        // Đổi nút ngay lập tức trên giao diện
        let downloadBtnHtml = `
                    <a href="${response.download_url}" download="${response.filename || "subtitle.srt"}"
                        class="dh-delete-btn" 
                        title="Tải xuống SRT"
                        style="color: #667eea; border-color: #667eea; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </a>
                `;
        $(btnElement).replaceWith(downloadBtnHtml);
        if (typeof toastr !== "undefined")
          toastr.success("Đã tạo file SRT thành công!");
      } else {
        let msg = response.message || "Không thể tạo SRT lúc này.";
        if (typeof toastr !== "undefined") toastr.warning(msg);
        $(btnElement).html(originalContent);
        $(btnElement).prop("disabled", false);
      }
    },
    error: function (xhr, status, error) {
      console.error("Lỗi:", error);
      $(btnElement).html(originalContent);
      $(btnElement).prop("disabled", false);
      if (typeof toastr !== "undefined") toastr.error("Lỗi kết nối server");
    },
  });
}
// ========================================
// XÓA TASK TỪ MODAL CHI TIẾT
// ========================================
function deleteDetailedTask(taskId, textPreview, status, cost) {
  console.log("🗑️ Delete task from detailed modal:", taskId);

  // 🔥 GỌI CHUNG HÀM openDeleteModal()
  // Nếu status = 'done' hoặc 'failed' → type = 'history' (không hoàn tiền)
  // Nếu status khác → type = 'refund' (có hoàn tiền)
  let deleteType =
    status === "done" || status === "failed" ? "history" : "refund";

  // Truncate preview nếu quá dài
  let preview = textPreview || "Không có nội dung";
  if (preview.length > 200) {
    preview = preview.substring(0, 200) + "...";
  }

  openDeleteModal(taskId, preview, deleteType, cost);
}
// 1. Mở Modal
function openSrtModal(taskId) {
  $("#srtCurrentTaskId").val(taskId); // Lưu ID task đang chọn
  $("#srtSettingsModal").fadeIn(200);
}

// 2. Đóng Modal
function closeSrtModal() {
  $("#srtSettingsModal").fadeOut(200);
}

// 3. Reset về mặc định
function resetSrtSettings() {
  $("#srtMaxChars").val(42);
  $("#srtMaxLines").val(2);
  $("#srtMaxDuration").val(7);
}

function submitSrtExport() {
  // 1. Lấy dữ liệu từ Modal
  const taskId = $("#srtCurrentTaskId").val();
  const maxChars = $("#srtMaxChars").val();
  const maxLines = $("#srtMaxLines").val();
  const maxDuration = $("#srtMaxDuration").val();

  console.log("📤 [FE] Gửi yêu cầu SRT:", {
    taskId,
    maxChars,
    maxLines,
    maxDuration,
  });

  if (!taskId) {
    alert("Lỗi: Không tìm thấy Task ID!");
    return;
  }

  // 2. Hiệu ứng Loading trên nút trong Modal
  const $btn = $(".srt-btn-export");
  const oldText = $btn.text();
  $btn
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...',
    );

  // 3. Gửi Ajax
  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    dataType: "json",
    data: {
      action: "export_custom_srt",
      task_id: taskId,
      max_chars: maxChars,
      max_lines: maxLines,
      max_duration: maxDuration,
    },
    success: function (res) {
      // Trả lại trạng thái nút
      $btn.prop("disabled", false).text(oldText);
      console.log("📥 [FE] Nhận phản hồi:", res);

      // 🔥 [FIX] Kiểm tra cả trạng thái VÀ nội dung thông báo lỗi
      // Nếu server báo "Task is not completed", ta coi như nó đang xử lý để hiện Popup chờ
      let isProcessing =
        res.status === "processing" ||
        res.task_status === "processing" ||
        res.task_status === "pending" ||
        res.task_status === "queued";

      // 👇 THÊM DÒNG NÀY: Bắt lỗi "Task is not completed" và coi là đang xử lý
      if (res.message && res.message.includes("Task is not completed")) {
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
      if (res.status === "success") {
        // A. Đóng Modal
        closeSrtModal();

        // B. Tự động tải xuống file
        const a = document.createElement("a");
        a.style.display = "none";
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
            const blob = new Blob([byteArray], { type: "text/srt" });
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
          if (a.href.startsWith("blob:")) URL.revokeObjectURL(a.href);
        }, 100);

        // C. Cập nhật giao diện
        let newDownloadBtn = `
                    <a href="${res.download_url}" download="${res.filename || "subtitle.srt"}"
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
        if (typeof showToast === "function") {
          showToast("✅ Tạo & Tải phụ đề thành công!");
        } else if (typeof toastr !== "undefined") {
          toastr.success("Đã tạo file SRT thành công!");
        }
      } else {
        alert("⚠️ " + (res.message || "Lỗi không xác định từ server"));
      }
    },
    error: function (xhr, status, error) {
      $btn.prop("disabled", false).text(oldText);
      console.error("❌ AJAX Error Raw:", xhr.responseText);

      let errorMsg = "Lỗi kết nối server";
      try {
        let errJson = JSON.parse(xhr.responseText);
        if (errJson.message) errorMsg = errJson.message;
      } catch (e) {}

      alert(`❌ ${errorMsg} (${xhr.status})`);
    },
  });
}
// 🔥 HÀM HIỂN THỊ POPUP XÁC NHẬN (PHIÊN BẢN FIX LỖI CLICK)
function showBulkDeleteConfirm(count, onConfirmCallback) {
  // 1. Xóa popup cũ nếu bị kẹt
  $("#bulkDeletePopup").remove();

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
  $("body").append(html);

  // 4. 🔥 GẮN SỰ KIỆN CLICK BẰNG JQUERY (CHẮC CHẮN CHẠY)

  // Nút Hủy
  $("#bdCancelBtn").on("click", function () {
    $("#bulkDeletePopup").fadeOut(200, function () {
      $(this).remove();
    });
  });

  // Nút Xác nhận
  $("#bdConfirmBtn").on("click", function () {
    // Hiệu ứng loading nút
    $(this).prop("disabled", true).css("opacity", "0.7").text("Đang xóa...");
    $("#bdCancelBtn").prop("disabled", true);

    // Gọi callback xóa
    if (typeof onConfirmCallback === "function") {
      onConfirmCallback();
    }
  });
}
function showSrtProcessingPopup(taskId) {
  // Xóa popup cũ nếu có
  $("#srtProcessingPopup").remove();

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

  $("body").append(html);

  // Auto close sau 60 giây
  setTimeout(() => {
    if ($("#srtProcessingPopup").length > 0) {
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
      url: "../../ajaxs/tts2.php",
      method: "POST",
      data: {
        action: "check_history_status",
        history_id: parseInt(historyId),
      },
      dataType: "json",
      timeout: 5000,
      success: function (res) {
        if ($(`#row-${taskId}`).length === 0) return;

        // 🔥 FIX: Parse progress từ API
        let progress = parseInt(res.progress) || 0;

        console.log(`📊 Polling ${taskId}: progress = ${progress}%`); // ← DEBUG LOG

        $elapsedSpan.attr("data-progress", progress);

        // 🔥 [QUAN TRỌNG] Cập nhật vị trí hàng đợi vào data attribute (để đồng hồ đọc)
        if (res.queue_position) {
          $elapsedSpan.attr("data-queue-pos", res.queue_position);
        }

        // Xử lý trạng thái
        if (res.status === "done" || res.status === "failed") {
          clearInterval(detailedIntervals[taskId]);
          delete detailedIntervals[taskId];
          syncDetailedHistoryCard(
            taskId,
            res.status,
            res.audio_url,
            res.srt_url,
            res.json_url,
            res.duration,
          );
        }
        // Cập nhật text ngay lập tức khi API trả về để người dùng thấy ngay
        else {
          updateStatusText();
        }
      },
    });
  };

  // --- HÀM CẬP NHẬT TEXT TRÊN GIAO DIỆN ---
  const updateStatusText = () => {
    let elapsedMs = Date.now() - validStartTime;
    let elapsedSeconds = Math.floor(elapsedMs / 1000);
    let timeString =
      typeof formatTime === "function" ? formatTime(elapsedSeconds) : "0:00";

    let currentProgress = $elapsedSpan.attr("data-progress") || 0;
    let queuePos = $elapsedSpan.attr("data-queue-pos");

    // 🔥 [THÊM] Lấy cờ GenAI từ attribute
    let isGenAI = $elapsedSpan.attr("data-genai") == "1";

    let currentText = $elapsedSpan.text().trim();
    let newText = "";

    // Logic hiển thị thông minh
    if (
      currentText.includes("Hàng đợi") ||
      (queuePos && parseInt(queuePos) > 0)
    ) {
      let queueLabel = queuePos ? `Hàng đợi #${queuePos}` : `Hàng đợi`;
      newText = `${queueLabel} (${timeString})`;
    } else {
      // 🔥 [SỬA ĐOẠN NÀY] Kiểm tra GenAI
      if (isGenAI) {
        // Nếu là GenAI -> Không hiện %
        newText = `Đang xử lý (${timeString})`;
      } else {
        // Nếu thường -> Hiện %
        newText = `Đang xử lý - ${currentProgress}% - (${timeString})`;
      }
    }

    $elapsedSpan.html(newText);
  };

  // 1. Gọi API ngay lần đầu
  checkApiNow();

  // 2. Interval chạy mỗi 1 giây (Đồng hồ)
  let stopwatchInterval = setInterval(() => {
    if (
      !$("#detailedHistoryModal").is(":visible") ||
      $(`#row-${taskId}`).length === 0
    ) {
      clearInterval(stopwatchInterval);
      delete detailedIntervals[taskId];
      return;
    }

    // Cập nhật đồng hồ (dựa trên data đã lưu, không ghi đè bậy)
    updateStatusText();

    // Tính toán để gọi API (mỗi 3 giây)
    let elapsedSeconds = Math.floor((Date.now() - validStartTime) / 1000);
    let shouldPoll =
      elapsedSeconds > 0 &&
      elapsedSeconds % 3 === 0 &&
      elapsedSeconds !== lastPollTime;

    if (shouldPoll && attempts < maxAttempts) {
      attempts++;
      lastPollTime = elapsedSeconds;
      checkApiNow();
    }

    if (attempts >= maxAttempts) {
      clearInterval(stopwatchInterval);
      delete detailedIntervals[taskId];
      $elapsedSpan.html(`⏰ Timeout`);
      $row
        .find(".dh-badge-processing")
        .removeClass("dh-badge-processing")
        .addClass("dh-badge-error")
        .text("Timeout");
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
  const $icon = $("#dhRefreshIcon");

  $btn.prop("disabled", true); // Khóa nút để tránh spam
  $icon.addClass("spin-anim"); // Thêm class xoay (đã định nghĩa ở CSS trên)

  // 2. Reset các biến đếm nếu cần (tùy logic pagination của bạn)
  // Ví dụ: detailedHistoryData = [];

  // 3. Gọi hàm tải lại dữ liệu (Trang 1)
  loadDetailedHistoryData(1);

  // 4. Tắt hiệu ứng sau 1 giây (để user kịp nhìn thấy nó xoay)
  setTimeout(() => {
    $icon.removeClass("spin-anim");
    $btn.prop("disabled", false);

    // (Tùy chọn) Hiển thị toast thông báo
    if (typeof showToast === "function") {
      // showToast('Đã làm mới danh sách');
    }
  }, 800);
}
// 4. Logic Checkbox "Chọn tất cả" & Cập nhật nút
function toggleAllDetailed(source) {
  $(".dh-item-checkbox").prop("checked", source.checked);
  updateBulkActions();
}
function updateBulkActions() {
  let checkedBoxes = $(".dh-item-checkbox:checked");
  let count = checkedBoxes.length;
  let totalAudio = 0,
    totalSrt = 0,
    totalJson = 0;

  // Đếm số lượng file
  checkedBoxes.each(function () {
    if ($(this).data("audio")) totalAudio++;
    if ($(this).data("srt")) totalSrt++;
    if ($(this).data("json")) totalJson++;
  });

  // --- SỬA LẠI ĐOẠN NÀY ---
  // Không ẩn header nữa, chỉ enable/disable nút thôi

  // Cập nhật số trên nút Xóa & Trạng thái Enable/Disable
  $("#btnBulkDelete span").text(count);
  $("#btnBulkDelete").prop("disabled", count === 0);

  // Cập nhật nút Audio
  $("#btnBulkDownloadAudio span").text(totalAudio);
  $("#btnBulkDownloadAudio").prop("disabled", totalAudio === 0);

  // Cập nhật nút SRT
  $("#btnBulkDownloadSrt span").text(totalSrt);
  $("#btnBulkDownloadSrt").prop("disabled", totalSrt === 0);

  // Cập nhật nút JSON
  $("#btnBulkDownloadJson span").text(totalJson);
  $("#btnBulkDownloadJson").prop("disabled", totalJson === 0);

  // Xử lý checkbox chọn tất cả (nếu không còn item nào được chọn thì bỏ tick Select All)
  if (count === 0) {
    $("#dhSelectAll").prop("checked", false);
  }
}
// ========================================
// ⚡ BULK DELETE (XÓA NHIỀU) - TỐI ƯU
// ========================================
async function bulkDelete() {
  console.log("🖱️ Bulk delete clicked"); // Debug log

  // 1. Kiểm tra checkbox
  let checkedBoxes = $(".dh-item-checkbox:checked");
  if (checkedBoxes.length === 0) {
    if (typeof showToast === "function") showToast("⚠️ Chưa chọn task nào!");
    else alert("Chưa chọn task nào!");
    return;
  }

  // 2. Gọi Popup
  showBulkDeleteConfirm(checkedBoxes.length, async function () {
    // --- LOGIC XÓA (Chạy khi user bấm "Xóa ngay") ---

    // Disable nút gốc
    $("#btnBulkDelete").prop("disabled", true);

    let elements = checkedBoxes.toArray();
    for (const checkbox of elements) {
      let taskId = $(checkbox).val();
      deleteHistoryTask(taskId); // Gọi hàm xóa đơn lẻ
      await new Promise((r) => setTimeout(r, 50)); // Delay tạo hiệu ứng
    }

    // Đóng popup và dọn dẹp sau 500ms
    setTimeout(() => {
      $("#bulkDeletePopup").fadeOut(200, function () {
        $(this).remove();
      });
      $("#dhSelectAll").prop("checked", false);
      updateBulkActions();
      if (typeof showToast === "function") showToast(`✅ Đã xóa xong!`);

      // Mở lại nút gốc
      $("#btnBulkDelete").prop("disabled", false);
    }, 500);
  });
}

// 6. Hàm thực thi Bulk Download
// Thêm hàm này vào file JS của bạn
async function bulkDownload(type) {
  let checkedBoxes = $(".dh-item-checkbox:checked");
  if (checkedBoxes.length === 0) {
    alert("Chưa chọn file nào!");
    return;
  }

  // 1. UI Loading
  let btnId = "#btnBulkDownload" + type.charAt(0).toUpperCase() + type.slice(1);
  let $btn = $(btnId);
  let oldHtml = $btn.html();
  $btn
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span> Đang nén...');

  try {
    let zip = new JSZip();
    let promises = [];
    let count = 0;

    checkedBoxes.each(function () {
      let $box = $(this);
      let taskId = $box.val();

      // 🔥 [MỚI] KIỂM TRA TRẠNG THÁI TASK
      // Tìm dòng (row) chứa checkbox này để xem trạng thái
      let $row = $box.closest(".dh-row");
      let isDone = $row.find(".dh-badge-done").length > 0; // Chỉ lấy dòng có badge "Xong"

      // Nếu chưa xong -> Bỏ qua ngay lập tức, không gọi link
      if (!isDone) {
        console.log(`⏩ Bỏ qua task chưa xong: ${taskId}`);
        return; // Continue vòng lặp
      }

      let url = $box.data(type);
      let ext = type === "audio" ? "mp3" : type;
      let fileName = `file_${taskId}.${ext}`;

      if (url && url.length > 10) {
        let p = fetch(url)
          .then((response) => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.blob();
          })
          .then((blob) => {
            zip.file(fileName, blob);
            count++;
          })
          .catch((err) => {
            console.warn(`⚠️ Link hỏng (${taskId}):`, url);
          });

        promises.push(p);
      }
    });

    // 2. Đợi tất cả
    await Promise.all(promises);

    if (count === 0) {
      alert(
        "Không có file nào sẵn sàng để tải (Các task có thể đang chạy hoặc lỗi).",
      );
      $btn.prop("disabled", false).html(oldHtml);
      return;
    }

    // 3. Nén và Tải
    $btn.html(
      '<span class="spinner-border spinner-border-sm"></span> Đang lưu...',
    );
    let content = await zip.generateAsync({ type: "blob" });

    let a = document.createElement("a");
    a.href = URL.createObjectURL(content);
    a.download = `download_${type}_${Date.now()}.zip`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    if (typeof showToast === "function")
      showToast(`✅ Đã tải ${count} file thành công!`);
  } catch (e) {
    console.error("Lỗi zip:", e);
    alert("Có lỗi xảy ra.");
  } finally {
    $btn.prop("disabled", false).html(oldHtml);
  }
}
function openDeleteModal(taskId, textPreview, deleteType, cost = 0) {
  pendingDeleteId = taskId;
  pendingDeleteType = deleteType;
  pendingDeleteCost = cost;

  // Cập nhật preview
  $("#dmTextPreview").text(textPreview || "Không có nội dung preview");

  // 🔥 PHÂN BIỆT 2 LOẠI: 'refund' (đang chạy) vs 'history' (đã xong/lỗi)
  if (deleteType === "history") {
    // CASE 1: XÓA LỊCH SỬ (ĐÃ XONG/LỖI)
    $("#dmTitle").html(
      '<i class="bi bi-trash" style="color: #ef4444; margin-right: 8px;"></i>Xóa lịch sử',
    );
    $("#dmWarning").text(
      "Bạn có chắc chắn muốn xóa lịch sử này? Hành động này không thể hoàn tác.",
    );
    $("#dmNote")
      .html(
        `
            <i class="bi bi-info-circle"></i>
            Thao tác này chỉ xóa lịch sử, không hoàn tín dụng.
        `,
      )
      .css({
        background: "rgba(59, 130, 246, 0.1)",
        "border-color": "rgba(59, 130, 246, 0.3)",
        color: "#60a5fa",
      })
      .show();
    $("#btnDeleteText").text("Xóa lịch sử");
  } else {
    // CASE 2: XÓA TÁC VỤ ĐANG CHẠY (CÓ HOÀN TIỀN)
    $("#dmTitle").html("Xóa tác vụ");
    $("#dmWarning").text(
      "Bạn có chắc chắn muốn xóa tác vụ này? Hành động này không thể hoàn tác.",
    );
    $("#dmNote")
      .html(
        `
            <i class="bi bi-info-circle"></i>
            Nếu tác vụ bị treo quá 24h sẽ được hoàn tín dụng.
        `,
      )
      .css({
        background: "rgba(251, 191, 36, 0.1)",
        "border-color": "rgba(251, 191, 36, 0.3)",
        color: "#fbbf24",
      })
      .show();
    $("#btnDeleteText").text("Xóa");
  }

  // Hiện modal
  $("#deleteModal").css("display", "flex").hide().fadeIn(200).addClass("show");
}

function closeDeleteModal() {
  $("#deleteModal").removeClass("show").fadeOut(200);
  pendingDeleteId = null;
}

// Bắt sự kiện bấm nút Xóa trong Modal
// Xử lý sự kiện click nút Xóa trong Modal
// Xử lý sự kiện click nút Xóa trong Modal
$(document).on("click", "#btnConfirmDelete", function () {
  // 1. Kiểm tra ID
  if (!pendingDeleteId) {
    showToast("❌ Lỗi: Không tìm thấy ID tác vụ để xóa!");
    return;
  }

  // 🔥 Lưu ID vào biến tạm
  let idToDelete = pendingDeleteId;
  let typeToDelete = pendingDeleteType;
  let costToDelete = pendingDeleteCost;

  console.log("🟠 Confirmed Delete | ID:", idToDelete, "| Type:", typeToDelete);

  // 2. Disable nút
  $(this)
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span> Đang xóa...');

  // 3. Đóng Modal
  closeDeleteModal();

  // 4. Thực hiện xóa dựa trên type
  if (typeToDelete === "refund") {
    // XÓA TÁC VỤ ĐANG CHẠY (CÓ HOÀN TIỀN)
    deleteTask(idToDelete, costToDelete);
  } else {
    // XÓA LỊCH SỬ (KHÔNG HOÀN TIỀN)
    deleteHistoryTask(idToDelete);
  }

  // 5. Reset nút sau 2 giây
  setTimeout(() => {
    $("#btnConfirmDelete")
      .prop("disabled", false)
      .html('<i class="bi bi-trash"></i> <span id="btnDeleteText">Xóa</span>');
  }, 2000);
});
$(document).ready(function () {
  console.log("🚀 TTS2.js initialized");
  // ================================================================
  // 🔴 LOGIC BẢO TRÌ SERVER 2 (11LABS & MINIMAX) - ĐÃ BỎ GENAI
  // ================================================================

  // 1. Lấy trạng thái từ biến toàn cục (do PHP truyền xuống)
  // Lưu ý: Phải dùng đúng tên biến: isElevenLabsDown, isMinimaxDown
  let _11labsDown =
    typeof isElevenLabsDown !== "undefined" && isElevenLabsDown === true;
  let _minimaxDown =
    typeof isMinimaxDown !== "undefined" && isMinimaxDown === true;
  let _backupEligible =
    typeof isBackupEligible !== "undefined" && isBackupEligible === true;

  console.log("🔧 Maintenance Status:", {
    "11Labs": _11labsDown,
    Minimax: _minimaxDown,
    BackupUser: _backupEligible,
  });

  // 2. NẾU CẢ 2 NHÀ CUNG CẤP ĐỀU SẬP -> BẢO TRÌ TOÀN TRANG
  if (_11labsDown && _minimaxDown) {
    console.error("❌ ALL MAIN PROVIDERS DOWN (Server 2).");

    $("body").empty().css({
      background: "#050505",
      display: "flex",
      "align-items": "center",
      "justify-content": "center",
      height: "100vh",
      margin: "0",
      "font-family": "sans-serif",
      color: "#fff",
    }).html(`
            <div style="text-align: center; max-width: 500px; padding: 40px; border: 1px solid #333; border-radius: 20px; background: #111;">
                <i class="bi bi-cone-striped" style="font-size: 60px; color: #f59e0b; display: block; margin-bottom: 20px;"></i>
                <h1 style="font-size: 24px; margin-bottom: 10px; font-weight: 700;">Server 2 Đang Bảo Trì</h1>
                <p style="color: #888; font-size: 14px; margin-bottom: 30px;">
                    Hệ thống đang được nâng cấp.<br>Vui lòng quay lại sau hoặc sử dụng Server khác.
                </p>
                <div style="display:flex; gap:10px; justify-content:center;">
                    <a href="/" style="padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 8px;">Trang Chủ</a>
                    <a href="/ai/tts3" style="padding: 10px 20px; background: #667eea; color: #fff; text-decoration: none; border-radius: 8px;">Sang Server KingCong</a>
                </div>
            </div>
        `);
    return; // 🛑 DỪNG MỌI HOẠT ĐỘNG
  }

  // 3. NẾU CHỈ 11LABS SẬP
  if (_11labsDown && !_minimaxDown) {
    console.log("⚠️ ElevenLabs bảo trì.");

    if (_backupEligible) {
      // Nếu user được dùng Backup -> Chỉ hiện thông báo
      setTimeout(() => {
        $('.provider-option[data-provider="elevenlabs"] .provider-desc').html(
          'Đang dùng <strong style="color: #667eea;">Backup (Miễn phí)</strong>',
        );
        showToast("ℹ️ ElevenLabs đang dùng Backup (miễn phí)");
      }, 500);
    } else {
      // Nếu không được dùng Backup -> Ép sang Minimax
      console.log("⚠️ Chuyển hướng sang Minimax...");
      currentProvider = "minimax";

      setTimeout(() => {
        selectProvider("minimax"); // Hàm chuyển tab

        // Disable nút 11Labs
        let $btn11 = $('.provider-option[data-provider="elevenlabs"]');
        $btn11.css({
          opacity: "0.5",
          "pointer-events": "none",
          cursor: "not-allowed",
        });
        $btn11
          .find(".provider-desc")
          .html(
            '<span style="color:#ef4444; font-weight:bold">🔴 Đang bảo trì</span>',
          );

        showToast("⚠️ ElevenLabs bảo trì. Đã chuyển sang Minimax.");
      }, 500);
    }
  }

  // 4. NẾU CHỈ MINIMAX SẬP -> ÉP SANG 11LABS
  else if (_minimaxDown && !_11labsDown) {
    console.log("⚠️ Minimax bảo trì -> Chuyển sang ElevenLabs");
    currentProvider = "elevenlabs";

    setTimeout(() => {
      selectProvider("elevenlabs"); // Hàm chuyển tab

      // Disable nút Minimax
      let $btnMini = $('.provider-option[data-provider="minimax"]');
      $btnMini.css({
        opacity: "0.5",
        "pointer-events": "none",
        cursor: "not-allowed",
      });
      $btnMini
        .find(".provider-desc")
        .html(
          '<span style="color:#ef4444; font-weight:bold">🔴 Đang bảo trì</span>',
        );

      showToast("⚠️ Minimax đang bảo trì.");
    }, 500);
  }

  // ================================================================
  // 1. KHAI BÁO BIẾN TIMER
  // ================================================================

  let typingTimer;
  const doneTypingInterval = 1000;

  // ================================================================
  // 2. SỰ KIỆN GIAO DIỆN CƠ BẢN
  // ================================================================

  // Tự động tính tiền khi gõ hoặc PASTE văn bản
  $("#txtInput").on("input propertychange paste", function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(function () {
      updateEstimatedCost();
    }, doneTypingInterval);
  });

  // Tính tiền lại khi đổi Tab giọng (Default <-> Cloned)
  $(".voice-tab-btn").on("click", function () {
    window.currentVoiceTab = $(this).data("type");
    localStorage.setItem("tts_last_tab", window.currentVoiceTab);
    updateEstimatedCost();
  });

  // Credits tooltip (Hover)
  $("#creditsTrigger").hover(
    function () {
      $("#creditsTooltip").stop(true, true).fadeIn(200);
    },
    function () {
      $("#creditsTooltip").stop(true, true).fadeOut(200);
    },
  );

  // Close dropdowns khi click ra ngoài
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".provider-dropdown-wrapper").length) {
      $("#providerDropdown").removeClass("show");
      $("#providerDropdownIcon")
        .removeClass("bi-chevron-up")
        .addClass("bi-chevron-down");
    }
    if (
      !$(e.target).closest(".lang-selector-wrapper").length &&
      !$(e.target).closest('[onclick*="toggleLangDropdown"]').length
    ) {
      $("#langDropdown").removeClass("show");
      $("#langDropdownIcon")
        .removeClass("bi-chevron-up")
        .addClass("bi-chevron-down");
    }
    if (
      !$(e.target).closest("#minimaxModelBtn").length &&
      !$(e.target).closest("#minimaxModelDropdown").length
    ) {
      $("#minimaxModelDropdown").removeClass("show");
      $("#minimaxModelIcon")
        .removeClass("bi-chevron-up")
        .addClass("bi-chevron-down");
    }
  });

  // ================================================================
  // 3. KHÔI PHỤC TRẠNG THÁI (RESTORE STATE)
  // ================================================================

  // A. Khôi phục nội dung văn bản
  let savedText = localStorage.getItem("tts_input_draft");
  if (savedText) {
    $("#txtInput").val(savedText);
  }

  // B. Khôi phục tên file
  let savedFileName = localStorage.getItem("tts_filename");
  if (savedFileName) {
    $("#fileNameDisplay").text(`📂 ${savedFileName}`).show();
  }

  // C. Khôi phục cờ SRT
  let savedIsSrt = localStorage.getItem("tts_is_srt");
  if (savedIsSrt === "true") {
    window.isSrtFile = true;
    $("#srtFeeInfo").show();
  } else {
    window.isSrtFile = false;
    $("#srtFeeInfo").hide();
  }

  // D. Khôi phục Tab giọng nói
  let savedTab = localStorage.getItem("tts_last_tab");
  if (savedTab) {
    window.currentVoiceTab = savedTab;
    $(".voice-tab-btn").removeClass("active");
    $(`.voice-tab-btn[data-type="${savedTab}"]`).addClass("active");
  }

  // E. Tính toán lại chi phí sau khi restore
  setTimeout(() => {
    togglePlaceholder();
    updateEstimatedCost();
  }, 100);

  // ================================================================
  // 4. KHỞI TẠO HỆ THỐNG
  // ================================================================

  $("#pageLoader").css("display", "flex");

  // Kiểm tra API Key
  if (typeof hasApiKey !== "undefined" && !hasApiKey) {
    $("#apiKeyModal").css("display", "flex");
    $("#btnProcess").prop("disabled", true);
  } else {
    $("#btnProcess").prop("disabled", false);
  }

  // Thiết lập UI mặc định
  updateEmptyStateTips("elevenlabs");
  $("#elevenlabs-settings").removeClass("hidden");
  $("#minimax-settings").addClass("hidden");

  // Load dữ liệu
  loadResources();
  loadHistory();
  setupInfiniteScroll();
  setupAudioEvents();
  setupEventListeners();

  // ================================================================
  // 5. 🔥 AUTO-REFRESH CREDITS (MỚI)
  // ================================================================

  // Bật auto-refresh credits mỗi 10 giây
  startCreditsAutoRefresh();

  // Tắt auto-refresh khi rời trang (tiết kiệm tài nguyên)
  $(window).on("beforeunload", function () {
    stopCreditsAutoRefresh();
  });

  // Pause khi tab không active, resume khi quay lại
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
      stopCreditsAutoRefresh();
    } else {
      startCreditsAutoRefresh();
    }
  });

  // ================================================================
  // 6. 🔥 NÚT REFRESH CREDITS THỦ CÔNG (TÙY CHỌN)
  // ================================================================

  $("#btnRefreshCredits").on("click", function () {
    $(this).addClass("loading");
    refreshUserCredits();
    setTimeout(() => {
      $(this).removeClass("loading");
    }, 1000);
  });
  setTimeout(() => {
    if (typeof currentProvider !== "undefined") {
      if (currentProvider === "minimax") {
        $("#btnNormalizeVN").fadeIn(200);
      } else {
        $("#btnNormalizeVN").fadeOut(200);
      }
    }
  }, 500);
});

function updateEmptyStateTips(provider) {
  if (provider === "minimax") {
    $("#emptyTips").html(`
            <div class="es-tip">
                <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i>
                <span>💡 Chèn <b>&lt;#0.5#&gt;</b> để ngừng 0.5 giây.</span>
            </div>
            <div class="es-tip">
                <i class="bi bi-folder-fill" style="color: #3b82f6;"></i>
                <span>📁 Kéo thả tệp <b>.txt, .zip</b> vào đây.</span>
            </div>
        `);
  } else {
    $("#emptyTips").html(`
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
                <span>Kéo thả tệp <b>.txt, .zip</b> vào đây</span>
            </div>
        `);
  }
}

// ========== SETUP EVENT LISTENERS ==========
function setupEventListeners() {
  // 1. Text input events (Đã gộp: Lưu bộ nhớ + Hiện/Ẩn Placeholder + Tính tiền)
  $("#txtInput").on("input", function () {
    let content = $(this).val();

    // Luôn cập nhật nội dung text vào bộ nhớ
    localStorage.setItem("tts_input_draft", content);

    // 🔥 [MỚI] NẾU XÓA SẠCH CHỮ -> XÓA LUÔN FILE
    if (content.trim() === "") {
      localStorage.removeItem("tts_filename");
      localStorage.removeItem("tts_is_srt");
      window.isSrtFile = false;
      $("#fileNameDisplay").hide().text("");
      $("#srtFeeInfo").hide();
    }

    togglePlaceholder();
    updateEstimatedCost(); // ✅ SỬA: Đổi từ calculateCost() sang updateEstimatedCost()
  });

  // 🔥 [THÊM MỚI] 2. Checkbox phụ đề events
  $("#subtitleCheck, #minimaxSubtitleCheck").on("change", function () {
    console.log("✅ Subtitle checkbox changed:", $(this).prop("checked"));
    updateEstimatedCost(); // ← Tính lại giá ngay
    updateCostTooltip();
  });

  // 3. Các thanh trượt (Sliders) - GIỮ NGUYÊN
  $("#vol").on("input", function () {
    $("#volVal").text(parseFloat(this.value).toFixed(2));
    updateSliderFill(this);
  });

  $("#speed").on("input", function () {
    $("#speedVal").text(parseFloat(this.value).toFixed(2));
    updateSliderFill(this);
  });

  $("#pitch").on("input", function () {
    $("#pitchVal").text(this.value);
    updateSliderFill(this);
  });

  // 🔥 Logic hiển thị chữ cho Stability v3
  $("#stability").on("input", function () {
    let currentModelName = $("#selectedModelName").text();
    let val = parseInt(this.value);

    // Kiểm tra step=50 để biết là model V3
    let isV3 = $(this).attr("step") === "50";

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
      $("#stabilityVal").html(
        `<span style="color: ${color}; font-weight: bold; text-transform: uppercase;">${text}</span>`,
      );
    } else {
      // Model thường hiển thị %
      $("#stabilityVal").text(this.value + "%");
    }
    updateSliderFill(this);
  });

  $("#similarity").on("input", function () {
    $("#similarityVal").text(this.value + "%");
    updateSliderFill(this);
  });

  $("#style").on("input", function () {
    $("#styleVal").text(this.value + "%");
    updateSliderFill(this);
  });

  $("#elevenSpeed").on("input", function () {
    let val = parseFloat(this.value);
    $("#elevenSpeedVal").text(val.toFixed(2));
    updateSliderFill(this);

    if (val > 1.5) {
      $(this).closest(".slider-container").addClass("warning");
    } else {
      $(this).closest(".slider-container").removeClass("warning");
    }
  });

  // 3. Khởi tạo màu thanh trượt khi load
  setTimeout(() => {
    $("input[type=range]").each(function () {
      updateSliderFill(this);
    });
  }, 100);

  // 4. Khởi tạo Drag & Drop
  setupDragDrop();
}

// Hàm update fill
function updateSliderFill(slider) {
  let value = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
  slider.style.setProperty("--value", value + "%");
}

// ========== PROVIDER DROPDOWN ==========
function toggleProviderDropdown() {
  $("#providerDropdown").toggleClass("show");
  $("#providerDropdownIcon").toggleClass("bi-chevron-up bi-chevron-down");
}

// ========== SELECT PROVIDER (GỘP LOGIC TỐI ƯU) ==========
function selectProvider(provider) {
  // 🔥 LOG DEBUG (Có thể bật/tắt dễ dàng)
  const DEBUG = false; // Đổi true để bật log chi tiết

  if (DEBUG) {
    console.log(
      `%c🖱️ CLICK: Yêu cầu chuyển sang ${provider.toUpperCase()}`,
      "color: #00ffff; font-weight: bold; font-size: 14px;",
    );
    console.log("📊 Trạng thái biến toàn cục:", {
      currentProvider:
        typeof currentProvider !== "undefined" ? currentProvider : "undefined",
      elevenlabsDown:
        typeof elevenlabsDown !== "undefined" ? elevenlabsDown : "undefined",
      backupEligible:
        typeof backupEligible !== "undefined" ? backupEligible : "undefined",
      minimaxDown:
        typeof minimaxDown !== "undefined" ? minimaxDown : "undefined",
    });
  } else {
    console.log(`🖱️ CLICK: Yêu cầu chuyển sang ${provider.toUpperCase()}`);
  }

  // Hiện thông báo về tình trạng Minimax (chỉ hiện 1 lần mỗi session)
  if (provider === "minimax") {
    if (!sessionStorage.getItem('minimaxAnnouncementShown')) {
      showMinimaxAnnouncement();
      sessionStorage.setItem('minimaxAnnouncementShown', 'true');
    }
  }

  // ============================================================
  // 🚀 THỰC HIỆN CHUYỂN ĐỔI GIAO DIỆN
  // ============================================================

  currentProvider = provider;
  if (DEBUG) console.log(`✅ Đã gán currentProvider = ${currentProvider}`);

  // Update UI Active State
  $(".provider-option").removeClass("active");
  $(`.provider-option[data-provider="${provider}"]`).addClass("active");

  // Xử lý ẩn hiện Settings
  if (provider === "minimax") {
    console.log("🛠️ [DEBUG] Render giao diện: MINIMAX");

    $("#currentProviderName").text("Minimax");
    $("#currentProviderLogo").attr("src", "https://ai33.pro/minimax.png?v=3");

    $("#elevenlabs-settings").addClass("hidden");
    $("#minimax-settings").removeClass("hidden");

    updateEmptyStateTips("minimax");

    // 🔥 KIỂM TRA VOICES MINIMAX CÓ DATA KHÔNG
    console.log(
      "🔍 loadedVoices.minimax:",
      loadedVoices.minimax ? loadedVoices.minimax.length : "undefined",
    );

    // 🔥 NẾU RỖNG → GỌI LẠI loadResources()
    if (!loadedVoices.minimax || loadedVoices.minimax.length === 0) {
      console.warn("⚠️ MINIMAX VOICES EMPTY → Reloading resources...");
      loadResources(); // ← Gọi lại để load voices
    }

    // Logic Tabs trong Modal
    if ($("#voiceModal").is(":visible")) {
      $('.vm-tab[data-tab="library"]').hide();
      $('.vm-tab[data-tab="cloned"]').show();
      if (currentVoiceTab === "library") switchVoiceTab("default");
    }
  } else {
    if (DEBUG) console.log("🛠️ [DEBUG] Render giao diện: ELEVENLABS");

    $("#currentProviderName").text("Elevenlabs");
    $("#currentProviderLogo").attr("src", "https://ai33.pro/11max.png?v=3");

    $("#elevenlabs-settings").removeClass("hidden");
    $("#minimax-settings").addClass("hidden");

    updateEmptyStateTips("elevenlabs");

    // Logic Tabs trong Modal
    if ($("#voiceModal").is(":visible")) {
      $('.vm-tab[data-tab="library"]').show();
      $('.vm-tab[data-tab="cloned"]').hide();
      if (currentVoiceTab === "cloned") switchVoiceTab("default");
    }
  }

  // Reset các lựa chọn cũ
  $("#selectedVoiceName").text("Chọn giọng nói...");
  $("#voiceIdVal").val("");

  // Đóng dropdown menu
  $("#providerDropdown").removeClass("show");
  $("#providerDropdownIcon")
    .removeClass("bi-chevron-up")
    .addClass("bi-chevron-down");

  hideModelDetails();
  renderMinimaxModels();

  // 🔥 TÍNH TOÁN LẠI GIÁ TIỀN
  if (DEBUG) console.log("💰 [DEBUG] Gọi updateEstimatedCost()...");
  updateEstimatedCost();

  // Force update lần 2 để fix lỗi render CSS (nếu có)
  setTimeout(() => {
    updateEstimatedCost();
  }, 100);
  if (provider === "minimax") {
    $("#btnNormalizeVN").fadeIn(200); // Hiện nút
  } else {
    $("#btnNormalizeVN").fadeOut(200); // Ẩn nút
  }
}
// ========== LANGUAGE DROPDOWN ==========
// ========== LANGUAGE DROPDOWN ==========
function toggleLangDropdown() {
  $("#langDropdown").toggleClass("show");
  $("#langDropdownIcon").toggleClass("bi-chevron-up bi-chevron-down");
}

function selectLanguage(langCode, displayName) {
  selectedLanguage = langCode;
  $("#selectedLang").text(displayName);

  $(".lang-option").removeClass("active");
  $(`.lang-option[data-lang="${langCode}"]`).addClass("active");

  $("#langDropdown").removeClass("show");
  $("#langDropdownIcon")
    .removeClass("bi-chevron-up")
    .addClass("bi-chevron-down");
}

// Đóng dropdown khi click ngoài
$(document).on("click", function (e) {
  if (!$(e.target).closest(".provider-dropdown-wrapper").length) {
    $("#providerDropdown").removeClass("show");
    $("#providerDropdownIcon")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  }
  if (!$(e.target).closest(".lang-selector-wrapper").length) {
    $("#langDropdown").removeClass("show");
    $("#langDropdownIcon")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  }
});

function hidePageLoader() {
  $("#pageLoader").fadeOut(300, function () {
    if (hasApiKey) {
      $("#btnProcess").prop("disabled", false);
    }
  });
  // ✅ BỎ dòng sessionStorage.setItem()
}

// ========== LOAD RESOURCES ==========
function loadResources() {
  console.log("🚀 loadResources() called");

  $.get(
    "../../ajaxs/get_resources2.php",
    function (res) {
      console.log("✅ API Response received:", res);

      if (res.status === "success") {
        try {
          // 1. Kiểm tra an toàn trước khi map dữ liệu Voices
          // Nếu res.data.elevenlabs.voices bị null -> gán mảng rỗng []
          let elVoices =
            res.data && res.data.elevenlabs && res.data.elevenlabs.voices
              ? res.data.elevenlabs.voices
              : [];
          let mmVoices =
            res.data && res.data.minimax && res.data.minimax.voices
              ? res.data.minimax.voices
              : [];

          loadedVoices.elevenlabs = enhanceVoiceData(elVoices, "elevenlabs");
          let systemMinimaxVoices = enhanceVoiceData(mmVoices, "minimax");

          // 2. Kiểm tra an toàn Models
          let elModels =
            res.data && res.data.elevenlabs && res.data.elevenlabs.models
              ? res.data.elevenlabs.models
              : [];
          let mmModels =
            res.data && res.data.minimax && res.data.minimax.models
              ? res.data.minimax.models
              : [];

          loadedModels = {
            elevenlabs: elModels,
            minimax: mmModels,
          };

          // 3. Xử lý logic GenAI Backup (Nếu không có models nào)
          if (loadedModels.elevenlabs.length === 0) {
            // Tạo một model giả để giao diện không bị lỗi trắng
            loadedModels.elevenlabs.push({
              id: "genai_backup",
              name: "GenAI Backup Mode",
              description: "Chế độ dự phòng",
              cost_factor: 1,
            });
          }

          // 4. Load Models & Voices
          loadClonedVoicesAndMerge(systemMinimaxVoices);
          renderMinimaxModels();
          renderElevenLabsModels();

          // 🔥 THÊM: Cập nhật badge model ngay sau khi render
          if (currentProvider === "minimax" && selectedMinimaxModel) {
            let modelName = $("#selectedMinimaxModel").text();
            updateModelBadge(selectedMinimaxModel, modelName);
          }
        } catch (e) {
          console.error("❌ Error inside loadResources processing:", e);
        }
      }
    },
    "json",
  )
    .fail(function (xhr, status, error) {
      console.error("❌ API loadResources Failed:", error);
      // Fallback: Tạo model giả nếu API chết hẳn
      loadedModels.elevenlabs = [{ id: "backup", name: "Backup Mode" }];
      renderElevenLabsModels();
    })
    .always(function () {
      // 🔥 QUAN TRỌNG NHẤT: Luôn luôn tắt loading dù thành công hay thất bại
      console.log("🏁 loadResources finished. Hiding Loader.");
      hidePageLoader();
    });
}
function loadClonedVoicesAndMerge(systemVoices) {
  $.post(
    "../../ajaxs/voice_cloning2.php",
    { action: "list_clones" },
    function (res) {
      let clonedVoices = [];
      if (res.status === "success" && res.voices && res.voices.length > 0) {
        clonedVoices = res.voices.map((v) => ({
          id: v.voice_id, // Map đúng key từ API Minimax
          name: (v.voice_name || "Unknown") + " (Clone)",
          // Fallback avatar nếu API không trả về cover_url
          avatar:
            v.cover_url ||
            `https://ui-avatars.com/api/?name=${encodeURIComponent(v.voice_name)}&background=random&color=fff`,
          tags: ["Minimax", "Clone", "User"],
          gender: "Unknown",
          preview_url: v.sample_audio,

          // 🔥 QUAN TRỌNG: Phải có dòng này thì bộ lọc "Giọng nhân bản" mới nhận diện được
          source: "cloned",
        }));
      }

      // Gộp giọng clone + giọng hệ thống
      loadedVoices.minimax = [...clonedVoices, ...systemVoices];

      // Nếu đang ở tab clone thì render lại ngay
      if (currentVoiceTab === "cloned") {
        renderClonedVoices();
      }
    },
    "json",
  ).fail(function () {
    console.error("Lỗi tải danh sách clone");
    loadedVoices.minimax = systemVoices;
  });
}

function enhanceVoiceData(voices, provider) {
  if (!voices) return [];
  return voices.map((v) => {
    let tags = [];
    let gender = "Male";
    let nameLower = (v.name || "").toLowerCase();

    if (
      nameLower.includes("girl") ||
      nameLower.includes("woman") ||
      nameLower.includes("lady") ||
      nameLower.includes("female") ||
      nameLower.includes("mrs") ||
      (v.tags && v.tags.includes("Female"))
    ) {
      gender = "Female";
    }

    if (provider === "minimax") {
      tags = v.tags || [];
      gender = v.gender || gender;
    } else {
      tags.push("English");
      if (v.tags && Array.isArray(v.tags)) {
        tags = tags.concat(v.tags.slice(0, 2));
      }
    }

    tags.push(gender);

    // 🔥 XỬ LÝ AVATAR ĐẦY ĐỦ CHO MINIMAX
    let avatar;

    if (provider === "minimax") {
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
      avatar =
        v.avatar ||
        v.image_url ||
        `https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=random&size=128&color=fff&bold=true`;
    }

    return {
      id: v.id,
      name: v.name,
      avatar: avatar, // ✅ Avatar đã được xử lý đầy đủ
      tags: tags,
      gender: gender,
      preview_url: v.preview_url || v.sample_audio,
      source: v.source || "default",
      description: v.description || "",
    };
  });
}

// ========== RENDER MINIMAX MODELS AS DROPDOWN ==========
// ========================================
// 🔥 RENDER MINIMAX MODELS AS DROPDOWN (CẢI TIẾN)
// ========================================
function renderMinimaxModels() {
  if (currentProvider !== "minimax") return;

  let models = loadedModels.minimax || [];

  if (models.length === 0) {
    $("#minimaxModelDropdown").html(
      '<div style="padding:10px; color:#888;">Không có model khả dụng</div>',
    );
    $("#selectedMinimaxModel").text("Default");
    return;
  }

  let html = "";

  models.forEach((m, index) => {
    // 🔥 [MỚI] XÁC ĐỊNH MODEL HD
    let isHD = m.id === "speech-2.6-hd" || m.id === "speech-02-hd";

    // 🔥 [MỚI] BADGE TRẮNG ĐEN
    let badge = "";
    if (isHD) {
      badge = `<span style="
                background: #fff; 
                color: #000; 
                border: 1px solid #000;
                padding: 2px 8px; 
                border-radius: 4px; 
                font-size: 10px; 
                font-weight: 700; 
                margin-left: 8px;
            ">+15%</span>`;
    }

    let isActive = index === 0 ? "active" : "";
    if (index === 0) {
      selectedMinimaxModel = m.id;

      // 🔥 [MỚI] HIỂN THỊ BADGE NGAY KHI LOAD
      let displayName = m.name;
      $("#selectedMinimaxModel").html(displayName);

      // 🔥 CẬP NHẬT BADGE BÊN NGOÀI
      updateModelBadge(m.id, m.name);
    }

    html += `
        <div class="lang-option ${isActive}" data-model="${m.id}" onclick="selectMinimaxModelFromDropdown('${m.id}', '${m.name}')">
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #fff; margin-bottom: 3px;">
                    ${m.name}${badge}
                </div>
                <div style="font-size: 11px; color: #777;">${m.description || "Xử lý văn bản tự nhiên"}</div>
            </div>
            <i class="bi bi-check-lg check-icon"></i>
        </div>`;
  });

  $("#minimaxModelDropdown").html(html);
}

// Toggle Minimax Model Dropdown
function toggleMinimaxModelDropdown() {
  $("#minimaxModelDropdown").toggleClass("show");
  $("#minimaxModelIcon").toggleClass("bi-chevron-up bi-chevron-down");
}

// ========================================
// 🔥 SELECT MINIMAX MODEL (CẢI TIẾN)
// ========================================
function selectMinimaxModelFromDropdown(modelId, modelName) {
  selectedMinimaxModel = modelId;

  // 🔥 HIỂN THỊ TÊN MODEL (KHÔNG CÓ BADGE)
  $("#selectedMinimaxModel").html(modelName);

  // 🔥 CẬP NHẬT CLASS ACTIVE
  $(".lang-option[data-model]").removeClass("active");
  $(`.lang-option[data-model="${modelId}"]`).addClass("active");

  // 🔥 ĐÓNG DROPDOWN
  $("#minimaxModelDropdown").removeClass("show");
  $("#minimaxModelIcon")
    .removeClass("bi-chevron-up")
    .addClass("bi-chevron-down");

  // 🔥 CẬP NHẬT BADGE BÊN NGOÀI
  updateModelBadge(modelId, modelName);

  // 🔥 TÍNH LẠI GIÁ + TOOLTIP
  updateEstimatedCost();
  updateCostTooltip();
}
// ========================================
// 🤖 CẬP NHẬT BADGE MODEL HD (BÊN NGOÀI)
// ========================================
function updateModelBadge(modelId, modelName) {
  const $badge = $("#minimax-model-badge");

  if (currentProvider !== "minimax") {
    $badge.hide();
    return;
  }

  // 🔥 KIỂM TRA MODEL HD
  let isHD =
    modelId === "speech-2.6-hd" ||
    modelId === "speech-02-hd" ||
    (modelName && modelName.toLowerCase().includes("hd"));

  if (isHD) {
    $badge.html('<i class="bi bi-stars" style="margin-right: 4px;"></i>+15%');
    $badge
      .css({
        display: "flex",
        "align-items": "center",
        background: "#fff",
        color: "#000",
        border: "1px solid #000",
        "font-weight": "700",
      })
      .show();
  } else {
    $badge.hide();
  }
}
// Thêm vào document click handler
$(document).on("click", function (e) {
  if (!$(e.target).closest(".provider-dropdown-wrapper").length) {
    $("#providerDropdown").removeClass("show");
    $("#providerDropdownIcon")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  }
  if (!$(e.target).closest(".lang-selector-wrapper").length) {
    $("#langDropdown").removeClass("show");
    $("#langDropdownIcon")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  }
  // THÊM CHO MINIMAX MODEL
  if (
    !$(e.target).closest("#minimaxModelBtn").length &&
    !$(e.target).closest("#minimaxModelDropdown").length
  ) {
    $("#minimaxModelDropdown").removeClass("show");
    $("#minimaxModelIcon")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  }
});

function selectMinimaxModel(modelId, elem) {
  selectedMinimaxModel = modelId;
  $(".model-selector-item").removeClass("active");
  $(elem).addClass("active");
}

// ========== RENDER ELEVENLABS MODELS ==========
function renderElevenLabsModels() {
  console.log("🎯 renderElevenLabsModels()");

  let models = loadedModels.elevenlabs || [];

  if (models.length === 0) {
    $("#selectedModelName").text("GenAI Backup");
    $("#elevenlabs-settings").hide();
    return;
  }

  // Tìm model mặc định
  let targetId = "eleven_multilingual_v2";
  let defaultModel = models.find((m) => m.id === targetId) || models[0];

  if (defaultModel) {
    // 🔥 THÊM GHI CHÚ "Not used for Vietnamese" CHO V2
    let displayName = defaultModel.name;

    if (defaultModel.id === "eleven_multilingual_v2") {
      displayName += " (Không dùng cho Tiếng Việt)";
    }

    $("#selectedModelName").text(displayName);

    // Cập nhật UI
    updateElevenLabsUI(defaultModel.id);

    // 🔥 [THÊM 2 DÒNG NÀY]
    updateEstimatedCost(); // ← Tính giá lần đầu
    updateCostTooltip(); // ← Cập nhật tooltip lần đầu
  }
}

// ========== UPDATE ELEVENLABS UI BASED ON MODEL ==========
function updateElevenLabsUI(modelId) {
  let model = loadedModels.elevenlabs.find((m) => m.id === modelId);

  if (!model || !model.full_data) {
    console.warn("⚠️ Model not found or missing full_data:", modelId);
    return;
  }

  // Lấy thông tin từ full_data
  let canUseStyle = model.full_data.can_use_style === true;
  let canUseSpeakerBoost = model.full_data.can_use_speaker_boost === true;

  // ========== LOGIC ẨN/HIỆN ==========

  // 1. Speed -> SỬA: Ẩn nếu là v3, hiện với các model khác
  if (modelId === "eleven_v3") {
    $("#slider-speed").hide();
  } else {
    $("#slider-speed").show();
  }

  // 2. Stability -> LUÔN HIỆN
  $("#slider-stability").show();

  // 3. Similarity -> Ẩn với eleven_v3
  if (modelId === "eleven_v3") {
    $("#slider-similarity").hide();
  } else {
    $("#slider-similarity").show();
  }

  // 4. Style -> Ẩn nếu là v3, ngược lại check canUseStyle
  if (modelId === "eleven_v3") {
    $("#slider-style").hide();
  } else {
    if (canUseStyle) {
      $("#slider-style").show();
    } else {
      $("#slider-style").hide();
    }
  }

  // 5. Speaker Boost
  if (canUseSpeakerBoost) {
    $("#toggle-boost").show();
  } else {
    $("#toggle-boost").hide();
  }

  // ========== TÙY CHỈNH GIAO DIỆN V3 (Stability 3 NẤC) ==========
  if (modelId === "eleven_v3") {
    // 1. Ép thanh trượt chỉ nhảy 3 nấc (0 - 50 - 100)
    $("#stability").attr("step", "50");
    $("#stability").attr("min", "0");
    $("#stability").attr("max", "100");

    // Thêm nhãn Creative / Natural / Robust ở dưới nếu chưa có
    if ($("#stability-labels").length === 0) {
      $("#stability").after(`
                <div id="stability-labels" style="display:flex; justify-content:space-between; font-size:12px; color:#888; margin-top:6px; font-weight:600;">
                    <span>Creative</span>
                    <span>Natural</span>
                    <span>Robust</span>
                </div>
            `);
    }
    $("#stability-labels").show();

    // Trigger input để cập nhật chữ và màu sắc ngay lập tức
    $("#stability").trigger("input");
  } else {
    // Revert về giao diện cũ (hiện %) cho các model khác
    // Trả lại step = 1 để kéo mượt %
    $("#stability").attr("step", "1");

    // Cập nhật lại text %
    let currentVal = $("#stability").val();
    $("#slider-stability .slider-header").html(
      '<span>Độ ổn định: <span id="stabilityVal" style="color: #ffffff;">' +
        currentVal +
        "%</span></span>",
    );

    // Ẩn nhãn 3 nấc
    $("#stability-labels").hide();
  }
}

// Tiếp tục ở phần 3...
// ========== MODEL DETAILS (ElevenLabs) ==========
const elevenLabsModelsData = {
  eleven_v3: {
    name: "Eleven v3 (alpha)",
    badge: "PREMIUM +30%",
    badgeType: "premium",
    description:
      "The most expressive model. Supports 70+ languages. Requires more prompt engineering than our previous models. In alpha and reliability will improve over time.",
    languages:
      "English, Afrikaans, Arabic, Armenian, Assamese, Azerbaijani, Belarusian, Bengali, Bosnian, Bulgarian, Catalan, Cebuano, Chichewa, Croatian, Czech, Danish, Dutch, Estonian, Filipino, Finnish, French, Galician, Georgian, German, Greek, Gujarati, Hausa, Hebrew, Hindi, Hungarian, Icelandic, Indonesian, Irish, Italian, Japanese, Javanese, Kannada, Kazakh, Kirghiz, Korean, Latvian, Lingala, Lithuanian, Luxembourgish, Macedonian, Malay, Malayalam, Mandarin Chinese, Marathi, Nepali, Norwegian, Pashto, Persian, Polish, Portuguese, Punjabi, Romanian, Russian, Serbian, Sindhi, Slovak, Slovenian, Somali, Spanish, Swahili, Swedish, Tamil, Telugu, Thai, Turkish, Ukrainian, Urdu, Vietnamese, Welsh",
    cost: 1,
  },
  eleven_multilingual_v2: {
    name: "Eleven Multilingual v2",
    badge: "HIGH QUALITY",
    badgeType: "high-quality",
    description:
      "Our most life-like, emotionally rich model in 29 languages. Best for voice overs, audiobooks, post-production, or any other content creation needs.",
    languages:
      "English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Russian",
    cost: 1,
    note: "(Not used for Vietnamese)",
  },
  eleven_turbo_v2_5: {
    name: "Eleven Turbo v2.5",
    badge: "TURBO",
    badgeType: "turbo",
    description:
      "Our high quality, low latency model in 32 languages. Best for developer use cases where speed matters and you need non-English languages.",
    languages:
      "English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Russian, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Vietnamese, Norwegian, Hungarian",
    cost: 1,
  },
  eleven_flash_v2_5: {
    name: "Eleven Flash v2.5",
    badge: "TURBO",
    badgeType: "turbo",
    description:
      "Our ultra low latency model in 32 languages. Ideal for conversational use cases.",
    languages:
      "English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish, Russian, Indonesian, Dutch, Turkish, Filipino, Polish, Swedish, Bulgarian, Romanian, Arabic, Czech, Greek, Finnish, Croatian, Malay, Slovak, Danish, Tamil, Ukrainian, Hungarian, Norwegian, Vietnamese",
    cost: 1,
  },
  eleven_turbo_v2: {
    name: "Eleven Turbo v2",
    badge: "TURBO",
    badgeType: "turbo",
    description:
      "English-only, low latency model. Best for developer use cases where speed matters and you only need English. Performance on par with Turbo v2.5.",
    languages: "English",
    cost: 1,
  },
  eleven_flash_v2: {
    name: "Eleven Flash v2",
    badge: "TURBO",
    badgeType: "turbo",
    description:
      "Our ultra low latency model in English. Ideal for conversational use cases.",
    languages: "English",
    cost: 1,
  },
  eleven_multilingual_v1: {
    name: "Eleven Multilingual v1",
    badge: "HIGH QUALITY",
    badgeType: "high-quality",
    description:
      "Our first Multilingual model, capable of generating speech in 10 languages. Now outclassed by Multilingual v2 (for content creation) and Turbo v2.5 (for low latency use cases).",
    languages:
      "English, Japanese, Chinese, German, Hindi, French, Korean, Portuguese, Italian, Spanish",
    cost: 1,
  },
  eleven_english_v1: {
    name: "Eleven English v1",
    badge: "HIGH QUALITY",
    badgeType: "high-quality",
    description:
      "Our first English text to speech model. Now outclassed by Multilingual v2 (for content creation) and Turbo v2.5 (for low latency use cases).",
    languages: "English",
    cost: 1,
  },
};

function showModelDetails() {
  let provider = currentProvider;

  if (provider !== "elevenlabs") return;

  let models = loadedModels.elevenlabs || [];

  // Lấy model hiện tại đang chọn
  let currentModelName = $("#selectedModelName").text();
  let currentModel = models.find((m) => currentModelName.includes(m.name));
  let currentModelId = currentModel ? currentModel.id : models[0]?.id;

  let html = "";

  models.forEach((m) => {
    let modelData = elevenLabsModelsData[m.id] || {
      name: m.name,
      badge: m.cost_factor < 1 ? "Tiết kiệm" : "Standard",
      badgeType: m.cost_factor < 1 ? "discount" : "quality",
      description: m.description || "Mô hình text-to-speech chất lượng.",
      languages: "English",
      cost: m.cost_factor || 1,
    };

    let badgeClass =
      modelData.badgeType === "discount"
        ? "mo-badge discount"
        : modelData.badgeType === "new"
          ? "mo-badge new"
          : "mo-badge";

    let selected = currentModelId === m.id ? "selected" : "";

    let langCount = modelData.languages.split(",").length;

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
            ${
              langCount > 10
                ? `
                <span class="mo-lang-toggle" onclick="event.stopPropagation(); toggleLanguages('${m.id}')">
                    <i class="bi bi-chevron-down"></i> Xem thêm
                </span>
            `
                : ""
            }
        </div>`;
  });

  $("#mdContent").html(html);
  $("#modelSidebar").addClass("active");
}

function selectModelFromSidebar(modelId, modelName) {
  let model = loadedModels.elevenlabs.find((m) => m.id === modelId);
  let badge = "";

  if (model) {
    if (model.cost_factor < 1) {
      badge = ` (${Math.round((1 - model.cost_factor) * 100)}% rẻ hơn)`;
    } else if (model.cost_factor > 1) {
      badge = " (Đắt hơn)";
    }
  }

  // 🔥 [FIX] XÓA BADGE "(Không dùng cho Tiếng Việt)" TRƯỚC KHI HIỂN THỊ
  let displayName = modelName;
  if (modelId === "eleven_multilingual_v2") {
    displayName += " (Không dùng cho Tiếng Việt)";
  }

  $("#selectedModelName").text(displayName + badge);

  $(".model-option").removeClass("selected");
  $(event.currentTarget).addClass("selected");

  // 🔥 CẬP NHẬT UI
  updateElevenLabsUI(modelId);
  calculateCost(); // ← Tính lại giá

  // 🔥 [THÊM DÒNG NÀY]
  updateCostTooltip(); // ← Cập nhật tooltip

  setTimeout(() => {
    hideModelDetails();
  }, 300);
}

function hideModelDetails() {
  $("#modelSidebar").removeClass("active");
}

function updateModelInfo() {
  if ($("#modelSidebar").hasClass("active")) {
    showModelDetails();
  }
}

function toggleLanguages(modelId) {
  let langDiv = $(`#lang-${modelId}`);
  let toggle = langDiv.next(".mo-lang-toggle");

  if (langDiv.hasClass("expanded")) {
    langDiv.removeClass("expanded");
    toggle.html('<i class="bi bi-chevron-down"></i> Xem thêm');
  } else {
    langDiv.addClass("expanded");
    toggle.html('<i class="bi bi-chevron-up"></i> Thu gọn');
  }
}

function selectModel(modelId) {
  $("#modelSelect").val(modelId);
  $(".model-option").removeClass("selected");
  $(event.currentTarget).addClass("selected");

  setTimeout(() => {
    hideModelDetails();
  }, 300);
}

// ========== VOICE MODAL - NEW FUNCTIONS ==========
let currentVoiceTab = "library";
let sharedVoices = [];
let sharedVoicesLoaded = false;
let sharedVoicesLoading = false;

let clonedVoices = [];

function openVoiceModal() {
  $("#voiceModal").css("display", "flex").hide().fadeIn(200);

  // 🔥 CẬP NHẬT TABS DỰA VÀO PROVIDER
  if (currentProvider === "minimax") {
    // Minimax: Mặc định, Giọng nhân bản, Yêu thích
    $('.vm-tab[data-tab="library"]').hide();
    $('.vm-tab[data-tab="cloned"]').show();

    // Default tab cho Minimax
    switchVoiceTab("default");
  } else {
    // ElevenLabs: Mặc định, Thư viện, Yêu thích
    $('.vm-tab[data-tab="library"]').show();
    $('.vm-tab[data-tab="cloned"]').hide();

    // Default tab cho ElevenLabs
    switchVoiceTab("default");
  }
}
// ========================================
// ⚡ LOAD SHARED VOICES (OPTIMIZED)
// ========================================
// ========================================
// ⚡ LOAD SHARED VOICES (OPTIMIZED)
// ========================================
function loadSharedVoices() {
  console.log("🔄 loadSharedVoices() called");

  if (sharedVoicesLoaded && sharedVoices.length > 0) {
    renderVoiceGridProgressive(sharedVoices);
    return;
  }

  if (sharedVoicesLoading) {
    showVoiceLoadingSpinner();
    return;
  }

  console.log("🌐 Fetching from NEW endpoint...");
  showVoiceLoadingSpinner();
  sharedVoicesLoading = true;

  $.ajax({
    url: "../../ajaxs/get_voices.php?v=" + Date.now(),
    method: "GET",
    dataType: "json",
    timeout: 60000,
    cache: false,
    headers: {
      "Cache-Control": "no-cache, no-store, must-revalidate",
      Pragma: "no-cache",
    },

    success: function (res) {
      console.log("📦 NEW Response:", res);

      if (res.status === "success" && res.data && res.data.length > 0) {
        sharedVoicesLoaded = true;

        // 🔥 [FIX QUAN TRỌNG] GỌI HÀM CHUẨN HÓA DỮ LIỆU
        // Thay vì: sharedVoices = res.data;
        // Hãy dùng:
        if (typeof enhanceSharedVoiceDataOptimized === 'function') {
            sharedVoices = enhanceSharedVoiceDataOptimized(res.data);
        } else {
            sharedVoices = res.data;
        }

        console.log(`✅ SUCCESS: ${res.count} voices loaded!`);
        renderVoiceGridProgressive(sharedVoices); 
      } else {
        console.error("❌ Invalid response");
        showVoiceErrorState("Lỗi dữ liệu");
      }
      sharedVoicesLoading = false;
    },

    error: function (xhr, status, error) {
      console.error("❌ AJAX Error:", { status, error });
      showVoiceErrorState("Lỗi kết nối", error);
      sharedVoicesLoading = false;
    },
  });
}
function renderVoiceGridProgressive(voices) {
  console.log(`🎨 Progressive render: ${voices.length} voices total`);

  // Reset state
  sharedVoicesRendered = 0;
  isRenderingVoices = false;
  $("#voiceGrid").empty();

  // Remove old scroll handler
  if (voiceGridScrollHandler) {
    $(".vm-grid").off("scroll", voiceGridScrollHandler);
    voiceGridScrollHandler = null;
  }

  // ✅ RENDER BATCH ĐẦU TIÊN NGAY LẬP TỨC
  renderVoiceBatch(voices, 0);

  // ✅ SETUP SCROLL LISTENER
  voiceGridScrollHandler = function () {
    const $grid = $(".vm-grid");
    const scrollTop = $grid.scrollTop();
    const scrollHeight = $grid[0].scrollHeight;
    const clientHeight = $grid.height();

    // Khi scroll gần đến cuối (còn 500px)
    if (scrollTop + clientHeight >= scrollHeight - 500 && !isRenderingVoices) {
      renderNextBatch(voices);
    }
  };

  $(".vm-grid").on("scroll", voiceGridScrollHandler);

  console.log(
    `✅ First ${sharedVoicesRenderBatch} voices rendered, ${voices.length - sharedVoicesRenderBatch} remaining`,
  );
}

// ========================================
// RENDER SINGLE BATCH
// ========================================
function renderVoiceBatch(voices, startIndex) {
  const endIndex = Math.min(
    startIndex + sharedVoicesRenderBatch,
    voices.length,
  );

  console.log(`🎨 Rendering batch: ${startIndex} → ${endIndex}`);

  // Trong hàm renderVoiceBatch
  for (let i = startIndex; i < endIndex; i++) {
    const voice = voices[i];
    let voiceCardHtml = "";

    // 🔥 LOGIC CHỌN CARD DỰA TRÊN PROVIDER
    if (currentProvider === "minimax") {
      voiceCardHtml = createMinimaxVoiceCardHTML(voice); // Hàm mới
    } else {
      voiceCardHtml = createVoiceCardHTML(voice); // Hàm cũ cho ElevenLabs
    }

    $("#voiceGrid").append(voiceCardHtml);
  }

  sharedVoicesRendered = endIndex;
}
// ========================================
// RENDER NEXT BATCH (LAZY LOAD)
// ========================================
function renderNextBatch(voices) {
  if (sharedVoicesRendered >= voices.length) {
    console.log("🏁 All voices rendered");
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
    console.log(
      `✅ Progress: ${sharedVoicesRendered}/${voices.length} (${percent}%)`,
    );
  }, 50);
}

// ========================================
// ⚡ CREATE VOICE CARD HTML
// ========================================
// Tìm function createVoiceCardHTML(voice) và thay thế nội dung bên trong:

// 1. Bảng đối chiếu Mã ngôn ngữ -> Cờ (Dựa trên select option bạn gửi)
const FLAG_MAP = {
  en: "🇺🇸",
  vi: "🇻🇳",
  fr: "🇫🇷",
  de: "🇩🇪",
  es: "🇪🇸",
  it: "🇮🇹",
  pt: "🇵🇹",
  ru: "🇷🇺",
  ja: "🇯🇵",
  ko: "🇰🇷",
  zh: "🇨🇳",
  ar: "🇸🇦",
  hi: "🇮🇳",
  th: "🇹🇭",
  id: "🇮🇩",
  nl: "🇳🇱",
  pl: "🇵🇱",
  tr: "🇹🇷",
  uk: "🇺🇦",
  sv: "🇸🇪",
  da: "🇩🇰",
  fi: "🇫🇮",
  no: "🇳🇴",
  el: "🇬🇷",
  cs: "🇨🇿",
  ro: "🇷🇴",
  hu: "🇭🇺",
  sk: "🇸🇰",
  bg: "🇧🇬",
  hr: "🇭🇷",
  sl: "🇸🇮",
  he: "🇮🇱",
  fa: "🇮🇷",
  ms: "🇲🇾",
  ta: "🇮🇳",
  fil: "🇵🇭",
  af: "🇿🇦",
  ca: "🏴",
  yue: "🇭🇰",
};

// 2. Hàm format số liệu (ví dụ: 1200 -> 1.2k)
function formatMetric(num) {
  if (!num) return "0";
  if (num >= 1000000000) return (num / 1000000000).toFixed(1) + "b";
  if (num >= 1000000) return (num / 1000000).toFixed(1) + "m";
  if (num >= 1000) return (num / 1000).toFixed(1) + "k";
  return num.toString();
}

// 3. Hàm tạo HTML thẻ Card (ĐÃ FIX + DEBUG LOG)
function createVoiceCardHTML(voice) {
    // 🔥 FIX: Check kỹ các trường ID có thể có
    let vId = voice.id || voice.voice_id || voice.public_owner_id;

    // 🛑 DEBUG: Nếu vẫn không tìm thấy ID thì in ra console để kiểm tra
    if (!vId) {
        console.error("❌ Voice object missing ID:", voice);
        vId = "error_id_" + Math.random().toString(36).substr(2, 9); // ID ảo để không vỡ giao diện
    }

    let isFav = favoriteVoices.includes(vId);
    let heartClass = isFav ? "bi-heart-fill" : "bi-heart";
    let heartStyle = isFav ? "color: #ef4444;" : "";

    // Xử lý tên và mô tả an toàn
    let rawName = voice.name || "Unknown Voice";
    let safeName = rawName.replace(/'/g, "\\'");
    let desc = voice.description || "Giọng đọc AI chất lượng cao.";

    // --- XỬ LÝ TAGS & METRICS ---
    let tagsHtml = "";

    // A. Tags phân loại (Accent, Gender...)
    if (voice.tags && voice.tags.length > 0) {
        tagsHtml += voice.tags
            .slice(0, 2)
            .map((t) => `<span class="vc-tag-pill">${t}</span>`)
            .join("");
    }

    // B. Metrics (Số lượt dùng & Clone)
    let usageCount = voice.usage_1y || 0; 
    let clonedCount = voice.cloned || 0; 

    if (clonedCount > 0) {
        tagsHtml += `<span class="vc-tag-pill"><i class="bi bi-people-fill vc-tag-icon"></i> ${formatMetric(clonedCount)}</span>`;
    }
    if (usageCount > 0) {
        tagsHtml += `<span class="vc-tag-pill"><i class="bi bi-lightning-charge-fill vc-tag-icon"></i> ${formatMetric(usageCount)}</span>`;
    }

    // --- XỬ LÝ CỜ ---
    let langCode = (voice.language || "en").split("-")[0].toLowerCase();
    let flagIcon = FLAG_MAP[langCode] || "🌐";

    // Preview URL
    let previewUrl = (voice.preview_url || "").replace(/'/g, "\\'");

    return `
    <div class="voice-card" data-voice-id="${vId}">
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
                <button class="vc-icon-btn" onclick="event.stopPropagation(); toggleFavorite(event, '${vId}')" title="Yêu thích">
                    <i class="bi ${heartClass}" style="${heartStyle}"></i>
                </button>
                
                <button class="vc-icon-btn" onclick="event.stopPropagation(); copyId('${vId}')" title="Copy ID">
                    <i class="bi bi-copy"></i>
                </button>

                ${
                  previewUrl
                    ? `
                <button class="vc-icon-btn" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${vId}')" title="Nghe thử">
                    <i class="bi bi-play-circle" style="font-size:20px;"></i>
                </button>
                `
                    : ""
                }

                <button class="vc-use-btn" onclick="event.stopPropagation(); chooseVoice('${vId}', '${safeName}')">
                    Dùng
                </button>
            </div>
        </div>
    </div>`;
}
// --- HÀM TẠO CARD RIÊNG CHO MINIMAX (GIỐNG ẢNH) ---
function createMinimaxVoiceCardHTML(voice) {
  let isFav = favoriteVoices.includes(voice.id);
  let heartClass = isFav ? "bi-heart-fill active" : "bi-heart";

  // Xử lý tên an toàn
  let rawName = voice.name || "Unknown";
  let safeName = rawName.replace(/'/g, "\\'");

  // Xử lý Avatar (Fallback nếu lỗi)
  let avatar = voice.avatar;
  if (!avatar) {
    avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(rawName)}&background=random&color=fff&size=64&rounded=true`;
  }

  // Xử lý Tags (Ưu tiên: Language -> Gender -> Tags khác)
  let displayTags = [];

  // 1. Language
  let lang = voice.language || "English";
  if (lang === "vi-VN") lang = "Vietnamese"; // Ví dụ mapping
  displayTags.push(lang);

  // 2. Gender
  if (voice.gender) {
    displayTags.push(
      voice.gender.charAt(0).toUpperCase() + voice.gender.slice(1),
    );
  }

  // 3. Các tag khác (bỏ qua gender nếu trùng)
  if (voice.tags && Array.isArray(voice.tags)) {
    let otherTags = voice.tags.filter(
      (t) => t.toLowerCase() !== (voice.gender || "").toLowerCase(),
    );
    displayTags = displayTags.concat(otherTags);
  }

  // Tạo HTML cho tags (Lấy max 5 tags)
  let tagsHtml = displayTags
    .slice(0, 5)
    .map((t) => `<span class="minimax-tag">${t}</span>`)
    .join("");

  // Preview URL
  let previewUrl = (voice.preview_url || "").replace(/'/g, "\\'");

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

                ${
                  previewUrl
                    ? `
                <button class="minimax-icon-btn" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${voice.id}')" title="Nghe thử">
                    <i class="bi bi-play-circle" style="font-size: 20px;"></i>
                </button>
                `
                    : ""
                }

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
    console.warn("Invalid voices data");
    return [];
  }

  return voices.map((v) => ({
    // Core fields
    id: v.voice_id || v.id,
    name: v.name || "Unknown",
    avatar: v.image_url || null,
    preview_url: v.preview_url || null,

    // Description (đã truncated ở BE)
    description: v.description || "",

    // Labels
    gender: v.gender || "unknown",
    age: v.age || "unknown",
    accent: v.accent || "neutral",
    language: v.language || "en",
    use_case: v.use_case || "conversational",
    category: v.category || "shared",
    featured: v.featured || false,

    // ✅ STATS (đã rút gọn tên từ BE)
    usage_1y: v.usage_1y || 0,
    cloned: v.cloned || 0,

    // Tags (build từ labels)
    tags: buildTags(v),

    source: v.source || "elevenlabs_official",
  }));
}

// Helper: Build tags từ voice data
function buildTags(v) {
  let tags = ["Shared"];

  if (v.gender && v.gender !== "unknown") {
    tags.push(v.gender.charAt(0).toUpperCase() + v.gender.slice(1));
  }

  if (v.age && v.age !== "unknown") {
    tags.push(v.age.charAt(0).toUpperCase() + v.age.slice(1));
  }

  if (v.accent && v.accent !== "neutral") {
    tags.push(v.accent.charAt(0).toUpperCase() + v.accent.slice(1));
  }

  if (v.featured) {
    tags.push("Featured");
  }

  return tags.filter(Boolean);
}

// Enhance Shared Voice Data
function enhanceSharedVoiceData(voices) {
  if (!voices || !Array.isArray(voices)) {
    console.warn("Invalid voices data");
    return [];
  }

  return voices.map((v) => ({
    id: v.voice_id || v.id,
    name: v.name || "Unknown",
    avatar: v.image_url || v.avatar || null,
    preview_url: v.preview_url || v.sample_audio || null,
    description: v.description || "",
    gender: v.gender || "unknown",
    age: v.age || "unknown",
    accent: v.accent || "neutral",
    language: v.language || "en",
    locale: v.locale || null,
    use_case: v.use_case || "conversational",
    category: v.category || "shared",
    featured: v.featured || false,
    can_be_finetuned: v.can_be_finetuned || false,
    live_moderation_enabled: v.live_moderation_enabled || false,
    tags: [
      "Shared",
      v.gender ? v.gender.charAt(0).toUpperCase() + v.gender.slice(1) : "",
    ].filter(Boolean),
  }));
}
function renderVoiceGrid(voices) {
  // 1. Xử lý trường hợp không có dữ liệu (Empty State)
  if (!voices || voices.length === 0) {
    $("#voiceGrid").html(`
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

  let html = "";

  // 2. Duyệt qua danh sách giọng và render
  voices.forEach((v) => {
    // 🔥 LOGIC QUAN TRỌNG: Phân loại Card dựa trên Provider
    if (currentProvider === "minimax") {
      // Sử dụng hàm tạo card mới (Avatar ở dưới, Tag viên thuốc) cho Minimax
      html += createMinimaxVoiceCardHTML(v);
    } else {
      // Sử dụng hàm tạo card cũ (Avatar ở trên, có Description) cho ElevenLabs
      html += createVoiceCardHTML(v);
    }
  });

  // 3. Đẩy HTML vào Grid
  $("#voiceGrid").html(html);
}

function toggleFavorite(event, voiceId) {
  event.stopPropagation();

  let index = favoriteVoices.indexOf(voiceId);
  let heartIcon = $(event.target);

  if (index > -1) {
    // Đã có → Xóa
    favoriteVoices.splice(index, 1);
    heartIcon.removeClass("bi-heart-fill active").addClass("bi-heart");
    showToast("💔 Đã xóa khỏi yêu thích");
  } else {
    // Chưa có → Thêm
    favoriteVoices.push(voiceId);
    heartIcon.removeClass("bi-heart").addClass("bi-heart-fill active");
    showToast("❤️ Đã thêm vào yêu thích");
  }

  // Lưu localStorage
  localStorage.setItem("favVoices", JSON.stringify(favoriteVoices));
}
// ========================================
// ⚡ SWITCH VOICE TAB (OPTIMIZED)
// ========================================
function switchVoiceTab(tab) {
  console.log("🔄 Switching Voice Tab to:", tab);
  currentVoiceTab = tab;

  // 1. Cập nhật giao diện Tab (Active state)
  $(".vm-tab").removeClass("active");
  $(`.vm-tab[data-tab="${tab}"]`).addClass("active");

  // 2. Reset ô tìm kiếm
  $("#voiceSearch").val("");

  // ============================================================
  // 🔥 3. XỬ LÝ HIỂN THỊ CÔNG CỤ (SORT & FILTERS)
  // ============================================================

  if (tab === "library" && currentProvider === "elevenlabs") {
    // A. Hiện nút Sort
    $("#sortDropdown").fadeIn(200);

    // 🔥 [FIX] SET MẶC ĐỊNH LÀ "MỚI NHẤT" (KHÔNG PHẢI DÙNG NHIỀU NHẤT)
    $("#currentSortLabel").text(jsLang.newest || "Newest");

    // Highlight nút "Mới nhất"
    $("#sortMenu .dropdown-item").removeClass("active").css({
      background: "transparent",
      color: "#ccc",
      "font-weight": "normal",
    });

    $('#sortMenu .dropdown-item[onclick*="newest"]').addClass("active").css({
      background: "#222",
      color: "#fff",
      "font-weight": "600",
    });

    // B. Hiện bộ lọc
    if ($(".vm-filters-bar").hasClass("hide-filters")) {
      $(".vm-filters-bar").removeClass("hide-filters");
      $(".filter-group, .filter-reset-btn").fadeIn(200);
    }
  } else {
    $("#sortDropdown").hide();

    if (!$(".vm-filters-bar").hasClass("hide-filters")) {
      $(".filter-group, .filter-reset-btn").fadeOut(200, function () {
        $(".vm-filters-bar").addClass("hide-filters");
      });
    }
  }

  // 4. Dọn dẹp
  $(".vm-grid").off("scroll");
  $("#voiceGrid").empty();

  // ============================================================
  // 🚀 5. RENDER DỮ LIỆU
  // ============================================================

  if (tab === "default") {
    let sourceList = [];

    if (currentProvider === "minimax") {
      // 🔥 LẤY VOICES MINIMAX (BỎ QUA CLONED)
      sourceList = (loadedVoices.minimax || []).filter(
        (v) => v.source !== "cloned",
      );

      console.log("🔍 MINIMAX DEFAULT VOICES:", sourceList.length); // ← DEBUG
    } else {
      sourceList = loadedVoices.elevenlabs || [];
    }

    if (sourceList.length === 0) {
      // 🔥 HIỂN THỊ EMPTY STATE
      $("#voiceGrid").html(`
            <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                <i class="bi bi-inbox" style="font-size:48px; color:#555; display:block; margin-bottom:15px;"></i>
                <p style="color:#888; font-size:14px;">Không tìm thấy giọng mặc định.</p>
                <p style="color:#666; font-size:12px; margin-top:8px;">
                    Vui lòng chọn tab <b>Giọng nhân bản</b> hoặc tải lại trang.
                </p>
            </div>
        `);
    } else {
      renderVoiceGridProgressive(sourceList);
    }
  } else if (tab === "library") {
    if (!sharedVoicesLoaded && !sharedVoicesLoading) {
      loadSharedVoices();
    } else if (sharedVoicesLoading) {
      showVoiceLoadingSpinner();
    } else if (sharedVoicesLoaded && sharedVoices.length > 0) {
      // 🔥 [FIX] KHÔNG SORT - GIỮ NGUYÊN THỨ TỰ TỪ API (= MỚI NHẤT)
      // API đã trả về theo thứ tự newest rồi
      renderVoiceGridProgressive(sharedVoices);
    } else {
      showVoiceErrorState("Thư viện trống hoặc lỗi tải dữ liệu.");
    }
  } else if (tab === "favorites") {
    let allVoices = [...(loadedVoices[currentProvider] || [])];

    if (currentProvider === "elevenlabs" && sharedVoices.length > 0) {
      const existingIds = new Set(allVoices.map((v) => v.id));
      sharedVoices.forEach((sv) => {
        if (!existingIds.has(sv.id)) allVoices.push(sv);
      });
    }

    let favs = allVoices.filter((v) => favoriteVoices.includes(v.id));

    if (favs.length === 0) {
      $("#voiceGrid").html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <i class="bi bi-heart" style="font-size:48px; color:#333; display:block; margin-bottom:15px;"></i>
                    <p style="color:#888; font-size:14px;">Chưa có giọng yêu thích</p>
                </div>
             `);
    } else {
      renderVoiceGrid(favs);
    }
  } else if (tab === "cloned") {
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
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }

  const $menu = $(menuId);
  const $btn = $(e.currentTarget);

  if ($menu.length === 0) return;

  $(".universal-dropdown-menu").not($menu).hide();
  $(".dropdown-arrow")
    .not($btn.find(".dropdown-arrow"))
    .removeClass("bi-chevron-up")
    .addClass("bi-chevron-down");

  if ($menu.is(":visible")) {
    $menu.hide();
    $btn
      .find(".dropdown-arrow")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
    return;
  }

  $menu.addClass("universal-dropdown-menu");

  if ($menu.parent()[0] !== document.body) {
    $menu.detach().appendTo("body");
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
    display: "block",
    top: rect.bottom + 5 + "px",
    left: leftPos + "px",
    width: menuWidth + "px",
    "min-width": "160px",
  });

  $btn
    .find(".dropdown-arrow")
    .removeClass("bi-chevron-down")
    .addClass("bi-chevron-up");
}
function selectFilter(type, value, label, displayLabel) {
  console.log(`✅ Filter Selected: ${type} = ${value}`);

  // 1. Cập nhật giá trị vào Input ẩn
  $(`#filter${type}`).val(value);

  // 2. Cập nhật text hiển thị trên nút
  $(`#label${type}`).text(displayLabel || label);

  // 3. Highlight item được chọn trong menu
  $(`#menu${type} .dropdown-item`).css({
    background: "transparent",
    color: "#ccc",
    "font-weight": "normal",
  });
  $(event.target).css({
    background: "#222",
    color: "#fff",
    "font-weight": "600",
  });

  // 4. ✅ THÊM/XÓA CLASS has-value DỰA VÀO GIÁ TRỊ
  const $filterGroup = $(`.btn-${type}`).closest(".filter-dropdown-enhanced");

  if (value && value !== "") {
    $filterGroup.addClass("has-value"); // ✅ Hiện gradient
  } else {
    $filterGroup.removeClass("has-value"); // ❌ Ẩn gradient
  }

  // 5. Đóng menu
  $(`#menu${type}`).hide();
  $(`.btn-${type} .dropdown-arrow`)
    .removeClass("bi-chevron-up")
    .addClass("bi-chevron-down");

  // 6. Gọi hàm lọc
  filterVoices();
}
function toggleSortDropdown(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
  }

  const $menu = $("#sortMenu");
  const $btn = $(e.currentTarget);

  console.log("🔍 Toggle Sort:", $menu.length);

  if ($menu.length === 0) {
    console.error("❌ #sortMenu not found!");
    return;
  }

  // Đóng các dropdown khác
  $(".dropdown-menu").not($menu).hide();
  $(".dropdown-arrow").removeClass("bi-chevron-up").addClass("bi-chevron-down");

  // Toggle hiện/ẩn
  if ($menu.is(":visible") && $menu.css("visibility") === "visible") {
    console.log("→ Closing");
    $menu.css({
      visibility: "hidden",
      opacity: "0",
    });
    setTimeout(() => $menu.hide(), 300);
    $("#sortArrow").removeClass("bi-chevron-up").addClass("bi-chevron-down");
    return;
  }

  console.log("→ Opening");

  // Đưa menu ra ngoài body
  if ($menu.parent()[0] !== document.body) {
    $menu.detach().appendTo("body");
  }

  // Tính vị trí
  const rect = $btn[0].getBoundingClientRect();
  const menuWidth = 200;
  let leftPos = rect.left;

  if (leftPos + menuWidth > window.innerWidth - 20) {
    leftPos = rect.right - menuWidth;
  }
  if (leftPos < 10) leftPos = 10;

  console.log("📍 Position:", { left: leftPos, top: rect.bottom + 5 });

  // 🔥 FIX: GHI ĐÈ CẢ VISIBILITY VÀ OPACITY
  $menu.attr(
    "style",
    `
        display: block !important;
        position: fixed !important;
        top: ${rect.bottom + 5}px !important;
        left: ${leftPos}px !important;
        width: ${menuWidth}px !important;
        z-index: 2147483647 !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        transition: none !important;
    `,
  );

  // Xoay mũi tên
  $("#sortArrow").removeClass("bi-chevron-down").addClass("bi-chevron-up");

  // Đóng khi click ngoài
  setTimeout(() => {
    $(document).one("click", function (evt) {
      if (!$(evt.target).closest("#sortDropdown, #sortMenu").length) {
        $menu.css({
          visibility: "hidden",
          opacity: "0",
        });
        setTimeout(() => $menu.hide(), 300);
        $("#sortArrow")
          .removeClass("bi-chevron-up")
          .addClass("bi-chevron-down");
      }
    });
  }, 100);
}

$(document).on("click", function (e) {
  // Bỏ qua nếu click vào nút hoặc menu
  if ($(e.target).closest("#sortDropdown, #sortMenu, .dropdown-btn").length)
    return;

  $("#sortMenu").fadeOut(100);
  $(".dropdown-arrow").removeClass("bi-chevron-up").addClass("bi-chevron-down");
});

function applySort(event, type, label) {
  console.log("✅ Selected sort:", type);

  // 1. Cập nhật text
  $("#currentSortLabel").text(label);

  // 2. 🔥 [FIX] RESET TẤT CẢ ITEM (BỎ event.target VÌ NÓ BỊ UNDEFINED)
  $("#sortMenu .dropdown-item")
    .css({
      background: "transparent",
      color: "#ccc",
      "font-weight": "normal",
    })
    .removeClass("active");

  // 3. 🔥 [FIX] HIGHLIGHT ĐÚNG ITEM (DÙNG SELECTOR THAY VÌ event.target)
  $(`#sortMenu .dropdown-item[onclick*="${type}"]`)
    .css({
      background: "#222",
      color: "#fff",
      "font-weight": "600",
    })
    .addClass("active");

  // 4. Đóng menu
  $("#sortMenu").fadeOut(100);
  $(".dropdown-arrow").removeClass("bi-chevron-up").addClass("bi-chevron-down");

  // --- LOGIC SẮP XẾP ---
  let sourceList = [];

  if (currentVoiceTab === "library") {
    sourceList = [...sharedVoices];
  } else if (currentVoiceTab === "default") {
    sourceList =
      currentProvider === "minimax"
        ? [...(loadedVoices.minimax || [])].filter((v) => v.source === "system")
        : [...(loadedVoices.elevenlabs || [])];
  } else {
    return;
  }

  // Sắp xếp
  if (type === "most_used") {
    sourceList.sort((a, b) => (b.cloned || 0) - (a.cloned || 0));
  } else if (type === "chars") {
    sourceList.sort((a, b) => (b.usage_1y || 0) - (a.usage_1y || 0));
  } else if (type === "trending") {
    sourceList.sort(
      (a, b) =>
        (b.cloned || 0) +
        b.usage_1y / 10000 -
        ((a.cloned || 0) + a.usage_1y / 10000),
    );
  } else if (type === "newest") {
    // 🔥 [FIX] KHÔNG REVERSE - GIỮ NGUYÊN THỨ TỰ TỪ API
    // sourceList.reverse(); // ← XÓA DÒNG NÀY
  }

  renderVoiceGridProgressive(sourceList);
}

// ========================================
// ⚡ HELPER: SHOW LOADING SPINNER
// ========================================
function showVoiceLoadingSpinner() {
  $("#voiceGrid").html(`
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
function showVoiceErrorState(message, details = "") {
  $("#voiceGrid").html(`
        <div style="color:#ef4444; text-align:center; padding:60px 20px; grid-column: 1 / -1;">
            <i class="bi bi-exclamation-triangle" style="font-size:48px; display:block; margin-bottom:16px; color:#f59e0b;"></i>
            <p style="font-size:14px; margin-bottom:8px; font-weight:600;">${message}</p>
            ${details ? `<p style="font-size:12px; color:#888; margin-bottom:16px;">${details}</p>` : ""}
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
  console.log("🔄 Manual reload triggered");
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
      console.log("✅ Shared voices loaded via polling");
      renderVoiceGridProgressive(sharedVoices);
    } else if (pollAttempts >= maxAttempts) {
      clearInterval(checkInterval);
      console.warn("⚠️ Polling timeout");
      showVoiceErrorState("Timeout", "Vui lòng thử lại");
    }
  }, 200);
}
function renderClonedVoices() {
  let clonedVoices = (loadedVoices.minimax || []).filter(
    (v) => v.source === "cloned",
  );
  $("#voiceGrid").empty();

  // Card "Nhân bản mới"
  let addCloneCardHtml = `
    <div class="voice-card add-clone-card" onclick="window.location.href='/ai/voice_cloning2'">
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

  $("#voiceGrid").append(addCloneCardHtml);

  // Render cloned voices
  if (clonedVoices.length > 0) {
    clonedVoices.forEach((v) => {
      let isFav = favoriteVoices.includes(v.id);
      let heartClass = isFav ? "bi-heart-fill active" : "bi-heart";
      let heartStyle = isFav ? "color: #ef4444;" : "";

      // 🔥 XỬ LÝ AVATAR CHO CLONED VOICES
      let avatar = v.avatar;

      // Fallback chain
      if (!avatar) {
        avatar =
          v.cover_url ||
          v.image_url ||
          `https://ui-avatars.com/api/?name=${encodeURIComponent(v.name)}&background=random&color=fff&size=64`;
      }

      let tagsHtml = "";
      let tags = v.tags || [];
      tags.slice(0, 3).forEach((tag) => {
        if (tag) {
          tagsHtml += `<span class="vc-tag">${tag}</span>`;
        }
      });

      let desc = v.description || "";
      if (desc.length > 80) {
        desc = desc.substring(0, 80) + "...";
      }

      let previewUrl = (v.preview_url || v.sample_audio || "").replace(
        /'/g,
        "\\'",
      );

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
                
                ${desc ? `<div class="vc-desc">${desc}</div>` : ""}
                
                <div class="vc-footer">
                    <div class="vc-actions">
                        <i class="bi ${heartClass}" style="${heartStyle}" title="Yêu thích" onclick="event.stopPropagation(); toggleFavorite(event, '${v.id}')"></i>
                        ${previewUrl ? `<i class="bi bi-play-circle" title="Nghe thử" onclick="event.stopPropagation(); togglePreview('${previewUrl}', '${v.id}')"></i>` : ""}
                    </div>
                    <button class="vc-use-btn" onclick="event.stopPropagation(); chooseVoice('${v.id}', '${v.name.replace(/'/g, "\\'")}')">Dùng</button>
                </div>
            </div>`;

      $("#voiceGrid").append(cardHtml);
    });
  }
}
// 🔥 MỞ CLONE MODAL
function openCloneModal() {
  closeVoiceModal();
  $("#cloneModal").fadeIn();
}

// ==========================================
// 🎯 CORE: FILTER VOICES (LOCAL + SERVER FALLBACK)
// ==========================================

// Biến hỗ trợ debounce (chờ người dùng gõ xong mới tìm)
let searchTimeout = null;

function filterVoices() {
  // 🛑 1. LẤY GIÁ TRỊ TỪ BỘ LỌC (Input ẩn & Search box)
  let searchRaw = $("#voiceSearch").val().trim();
  let search = searchRaw.toLowerCase();

  let lang = $("#filterLang").val();
  let gender = $("#filterGender").val();
  let age = $("#filterAge").val();
  let category = $("#filterCategory").val();
  let accent = $("#filterAccent").val();

  // 🛑 2. XÁC ĐỊNH NGUỒN DỮ LIỆU
  let sourceList = [];
  if (currentVoiceTab === "default") {
    if (currentProvider === "minimax") {
      sourceList = (loadedVoices.minimax || []).filter(
        (v) => v.source === "system",
      );
    } else {
      sourceList = loadedVoices.elevenlabs || [];
    }
  } else if (currentVoiceTab === "library") {
    sourceList = sharedVoices;
  } else if (currentVoiceTab === "favorites") {
    // Logic lấy fav list (như cũ)
    let all = [...(loadedVoices[currentProvider] || [])];
    if (currentProvider === "elevenlabs" && sharedVoices.length > 0) {
      const existingIds = new Set(all.map((v) => v.id));
      sharedVoices.forEach((sv) => {
        if (!existingIds.has(sv.id)) all.push(sv);
      });
    }
    sourceList = all.filter((v) => favoriteVoices.includes(v.id));
  } else if (currentVoiceTab === "cloned") {
    sourceList = (loadedVoices.minimax || []).filter(
      (v) => v.source === "cloned",
    );
  }

  // 🛑 3. LỌC DỮ LIỆU (TỐC ĐỘ CAO)
  // Javascript lọc mảng 10k phần tử cực nhanh, không gây lag ở đây
  let filtered = sourceList.filter((v) => {
    // A. Lọc từ khóa
    let matchSearch = true;
    if (search) {
      let name = (v.name || "").toLowerCase();
      let id = String(v.id || "").toLowerCase();
      // Chỉ tìm theo tên và ID cho nhanh, bỏ description nếu muốn siêu tốc
      matchSearch = name.includes(search) || id.includes(search);
    }

    // B. Lọc theo Dropdown (Nếu value rỗng = lấy hết)
    let matchLang =
      !lang || (v.language || "").toLowerCase().includes(lang.toLowerCase());
    let matchGender =
      !gender || (v.gender || "").toLowerCase() === gender.toLowerCase();

    // Với mảng tags
    let matchAge =
      !age || (v.tags || []).some((t) => t.toLowerCase().includes(age));
    let matchCategory =
      !category ||
      (v.tags || []).some((t) => t.toLowerCase().includes(category));
    let matchAccent =
      !accent || (v.tags || []).some((t) => t.toLowerCase().includes(accent));

    return (
      matchSearch &&
      matchLang &&
      matchGender &&
      matchAge &&
      matchCategory &&
      matchAccent
    );
  });

  // 🛑 4. RENDER KẾT QUẢ (QUAN TRỌNG NHẤT: DÙNG PROGRESSIVE)

  // Xóa sự kiện cuộn cũ để tránh xung đột
  $(".vm-grid").off("scroll");
  $("#voiceGrid").empty();

  if (filtered.length > 0) {
    // 🔥 CHÌA KHÓA CHỐNG LAG LÀ ĐÂY:
    // Thay vì renderVoiceGrid(filtered) -> gọi renderVoiceGridProgressive(filtered)
    renderVoiceGridProgressive(filtered);

    // Cập nhật UI highlight bộ lọc
    updateFilterIndicators();
  } else {
    // Xử lý khi không tìm thấy kết quả
    if (
      currentProvider === "elevenlabs" &&
      searchRaw.length >= 15 &&
      !searchRaw.includes(" ")
    ) {
      // Logic tìm ID trên server (Debounce 1s)
      clearTimeout(searchTimeout);

      // Hiển thị loading tạm
      $("#voiceGrid").html(`
                <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
                    <div class="spinner-border" style="width:30px; height:30px; color:#667eea;"></div>
                    <p style="color:#888; margin-top:15px; font-size:14px;">Đang tìm ID trên server...</p>
                </div>
            `);

      searchTimeout = setTimeout(() => {
        if (typeof searchVoiceOnServer === "function") {
          searchVoiceOnServer(searchRaw);
        }
      }, 1000);
    } else {
      // Không tìm thấy
      $("#voiceGrid").html(`
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
  let sortBy = $("#voiceSort").val();
  let sourceList =
    currentVoiceTab === "default"
      ? loadedVoices[currentProvider]
      : sharedVoices;

  let sorted = [...sourceList];

  if (sortBy === "newest") {
    // Mặc định
  } else if (sortBy === "popular") {
    sorted.sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0));
  } else if (sortBy === "name") {
    sorted.sort((a, b) => a.name.localeCompare(b.name));
  }

  renderVoiceGrid(sorted);
}

// Reset Filters
function resetFilters() {
  // Reset giá trị input ẩn
  $(
    "#filterLang, #filterGender, #filterAge, #filterCategory, #filterAccent, #voiceSearch",
  ).val("");

  // Reset text trên nút về "Tất cả"
  $("#labelLang, #labelGender, #labelAge, #labelCategory, #labelAccent").text(
    "Tất cả",
  );

  // ✅ XÓA CLASS has-value (Ẩn gradient)
  $(".filter-dropdown-enhanced").removeClass("has-value");

  // Xóa active class trong menu
  $(".dropdown-item").css({
    background: "transparent",
    color: "#ccc",
    "font-weight": "normal",
  });

  // Re-render
  switchVoiceTab(currentVoiceTab);
}

function closeVoiceModal() {
  $("#voiceModal").fadeOut(200);
  stopPreview();
}

function togglePreview(url, id) {
  if (!url) {
    showToast("⚠️ Không có audio preview");
    return;
  }

  // Reset tất cả icons
  $(".vc-actions .bi-pause-circle")
    .removeClass("bi-pause-circle")
    .addClass("bi-play-circle");

  if (currentPreviewUrl === url && !previewAudio.paused) {
    // Đang play → pause
    previewAudio.pause();
    currentPreviewUrl = null;
  } else {
    // Play mới
    previewAudio.src = url;
    previewAudio.play().catch((e) => {
      console.error("Play error:", e);
      showToast("⚠️ Không thể phát audio");
    });
    currentPreviewUrl = url;

    // Đổi icon thành pause
    $(`.voice-card[data-voice-id="${id}"] .bi-play-circle`)
      .removeClass("bi-play-circle")
      .addClass("bi-pause-circle");
  }
}

function stopPreview() {
  previewAudio.pause();
  previewAudio.currentTime = 0;
  currentPreviewUrl = null;
  $(".vc-actions .bi-pause-circle")
    .removeClass("bi-pause-circle")
    .addClass("bi-play-circle");
}

previewAudio.onended = function () {
  $(".vc-actions .bi-pause-circle")
    .removeClass("bi-pause-circle")
    .addClass("bi-play-circle");
  currentPreviewUrl = null;
};

function chooseVoice(id, name) {
  $("#voiceIdVal").val(id);
  $("#selectedVoiceName").text(name);

  showToast(`✅ Đã chọn: ${name}`);
  updateEstimatedCost();
  updateCostTooltip();

  // 🔥 KIỂM TRA FILE ĐANG CHỜ
  if (pendingUploadFiles && pendingUploadFiles.length > 0) {
    console.log("🔄 Processing pending files:", pendingUploadFiles.length);

    // Đóng Voice Modal trước
    closeVoiceModal();

    // Delay để modal đóng hẳn
    setTimeout(() => {
      handleGlobalDrop(pendingUploadFiles);
      pendingUploadFiles = null;
    }, 400);

    return; // ⚠️ Dừng tại đây
  }

  // Trường hợp bình thường (không có file chờ)
  closeVoiceModal();
}

function copyId(id) {
  navigator.clipboard.writeText(id).then(() => {
    let toast = $("<div>")
      .css({
        position: "fixed",
        bottom: "20px",
        left: "50%",
        transform: "translateX(-50%)",
        background: "#22c55e",
        color: "white",
        padding: "12px 24px",
        borderRadius: "8px",
        zIndex: 9999,
      })
      .text("✓ ID đã được copy!");

    $("body").append(toast);
    setTimeout(() => toast.fadeOut(() => toast.remove()), 2000);
  });
}

// ========== TEXT INPUT ==========
function calculateCost() {
  // 1. Lấy nội dung và đếm ký tự
  let text = $("#txtInput").val() || ""; // Thêm || '' để tránh lỗi nếu null
  let charCount = text.length;

  // 2. Lấy Cost Factor từ Model đang chọn
  let cost_factor = 1.0;

  if (currentProvider === "minimax") {
    let model = loadedModels.minimax.find((m) => m.id === selectedMinimaxModel);
    if (model) {
      cost_factor = model.cost_factor || 1.0;
    }
  } else {
    // ElevenLabs
    let currentModelName = $("#selectedModelName").text();
    let model = loadedModels.elevenlabs.find((m) =>
      currentModelName.includes(m.name),
    );
    if (model) {
      cost_factor = model.cost_factor || 1.0;
    }
  }

  // --- BẮT ĐẦU DEBUG TÍNH TIỀN ---
  let base_rate = 1.12;

  // Check trạng thái
  let isSrtUpload = window.isSrtFile === true;
  let isSubtitleChecked = $("#subtitleCheck").is(":checked");
  let applySrtFee = isSrtUpload || isSubtitleChecked;
  let estimated_cost;

  // Tính toán
  if (applySrtFee) {
    // Có phí SRT
    estimated_cost = charCount * base_rate * cost_factor * 1.2;
    $("#srtFeeInfo").show();
  } else {
    // Không phí SRT
    estimated_cost = charCount * base_rate * cost_factor;
    $("#srtFeeInfo").hide();
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
  $("#estimatedCost").text(total_cost.toLocaleString());

  let currentCredits = parseInt(
    $("#userCredits").text().replace(/,/g, "") || "0",
  );
  if (currentCredits < total_cost && charCount > 0) {
    $("#estimatedCost").css("color", "#ef4444");
  } else {
    $("#estimatedCost").css("color", "#fbbf24");
  }
}

// Gọi calculateCost khi thay đổi text
$("#txtInput").on("input", calculateCost);

function togglePlaceholder() {
  if ($("#txtInput").val().length > 0) {
    $("#emptyState").css("opacity", "0");
  } else {
    $("#emptyState").css("opacity", "1");
  }
  calculateCost();
}

function setupDragDrop() {
  const dropZone = document.getElementById("dropZone");

  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ["dragenter", "dragover"].forEach((eventName) => {
    dropZone.addEventListener(
      eventName,
      () => dropZone.classList.add("drag-over"),
      false,
    );
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(
      eventName,
      () => dropZone.classList.remove("drag-over"),
      false,
    );
  });

  dropZone.addEventListener("drop", handleDrop, false);
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
  $("#speed").val(1.0);
  $("#speedVal").text("1.00");
  updateSliderFill(document.getElementById("speed")); // THÊM

  $("#vol").val(1.0);
  $("#volVal").text("1.00");
  updateSliderFill(document.getElementById("vol")); // THÊM

  $("#pitch").val(0);
  $("#pitchVal").text("0");
  updateSliderFill(document.getElementById("pitch")); // THÊM

  selectLanguage("Auto", "Tự xác định");
}

function resetElevenLabsSettings() {
  // 1. Speed - ALWAYS RESET
  $("#elevenSpeed").val(1.0);
  $("#elevenSpeedVal").text("1.00");
  $("#elevenSpeed").closest(".slider-container").removeClass("warning");
  updateSliderFill(document.getElementById("elevenSpeed"));

  // 2. Stability - ALWAYS RESET
  $("#stability").val(50);

  // 🔥 QUAN TRỌNG: Trigger sự kiện input để code tự động nhận diện:
  // - Nếu là V3 (step=50) -> Nó tự đổi thành chữ "Natural" màu trắng.
  // - Nếu là Model thường (step=1) -> Nó tự đổi thành "50%" màu vàng.
  $("#stability").trigger("input");

  updateSliderFill(document.getElementById("stability"));

  // 3. Similarity - Chỉ reset nếu đang hiện
  if ($("#slider-similarity").is(":visible")) {
    $("#similarity").val(75);
    $("#similarityVal").text("75%");
    updateSliderFill(document.getElementById("similarity"));
  }

  // 4. Style - Chỉ reset nếu đang hiện
  if ($("#slider-style").is(":visible")) {
    $("#style").val(0);
    $("#styleVal").text("0%");
    updateSliderFill(document.getElementById("style"));
  }

  // 5. Boost - Chỉ reset nếu đang hiện
  if ($("#toggle-boost").is(":visible")) {
    $("#boostCheck").prop("checked", true);
  }
}

// ========== TAB SWITCHING WITH SMOOTH ANIMATION ==========
function switchTab(tabName) {
  console.log("🔄 SWITCHING TAB TO:", tabName);

  const currentTab =
    $(".tab-btn.active").attr("id") === "btnSettings" ? "settings" : "history";

  // Nếu bấm lại tab đang active thì thôi
  if (currentTab === tabName) return;

  // 1. Cập nhật nút active ngay lập tức
  $(".tab-btn").removeClass("active");
  if (tabName === "settings") {
    $("#btnSettings").addClass("active");
  } else {
    $("#btnHistory").addClass("active");
  }

  // 2. Xử lý Animation chuyển đổi
  const $currentContent = $(".sidebar-content.show");
  const $nextContent =
    tabName === "settings" ? $("#viewSettings") : $("#viewHistory");

  // Xác định hướng animation
  // Settings -> History: Slide Left (Nội dung mới từ phải qua)
  // History -> Settings: Slide Right (Nội dung mới từ trái qua)
  const animationClass =
    tabName === "settings" ? "animate-slide-in-left" : "animate-slide-in-right";

  // Fade out nội dung cũ
  $currentContent.addClass("animate-fade-out");

  setTimeout(() => {
    $currentContent.removeClass("show animate-fade-out");

    // Hiện nội dung mới và chạy animation
    $nextContent.addClass("show " + animationClass);

    // Xóa class animation sau khi chạy xong để sạch sẽ
    setTimeout(() => {
      $nextContent.removeClass(animationClass);
    }, 400); // Khớp với thời gian animation trong CSS
  }, 200); // Thời gian fade out

  // 3. Logic ẩn/hiện Header (Provider/History Actions)
  // Phần này giữ nguyên logic cũ của bạn
  if (tabName === "settings") {
    $("#providerWrapper").fadeIn(300);
    $("#historyActions").fadeOut(300);
  } else {
    $("#providerWrapper").fadeOut(300);
    setTimeout(() => {
      $("#historyActions").css("display", "flex").hide().fadeIn(300);
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
  $("#historyListContainer").empty();
  $("#noMoreData").hide();

  // 3. Hiển thị trạng thái đang tải (tùy chọn cho đẹp)
  $("#historyListContainer").html(`
        <div style="text-align:center; padding:40px 0; color:#666;">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <div style="margin-top:10px; font-size:12px;">Đang làm mới...</div>
        </div>
    `);

  // 4. Gọi lại hàm loadHistory để tải trang 1
  loadHistory();
}
function calculateSingleCost(text) {
  // ==========================================
  // 1. KIỂM TRA ĐẦU VÀO
  // ==========================================
  if (!text) return 0;
  let charCount = text.length;

  // ==========================================
  // 2. TÍNH HỆ SỐ MODEL & VOICE
  // ==========================================
  let cost_factor = 1.0;
  let voice_multiplier = 1.0;

  // --- MINIMAX: LOGIC MỚI ---
  if (currentProvider === "minimax") {
    // 🔥 2A. KIỂM TRA MODEL HD
    let isHDModel =
      selectedMinimaxModel === "speech-2.6-hd" ||
      selectedMinimaxModel === "speech-02-hd";

    if (isHDModel) {
      cost_factor = 1.15;
      console.log(
        "💰 Model HD detected:",
        selectedMinimaxModel,
        "→ Factor: 1.15",
      );
    } else {
      cost_factor = 1.0;
      console.log("💰 Standard Model:", selectedMinimaxModel, "→ Factor: 1.0");
    }

    // 🔥 2B. KIỂM TRA VOICE CLONE
    let isClone = false;
    let voiceId = $("#voiceIdVal").val();

    if (
      typeof currentVoiceTab !== "undefined" &&
      currentVoiceTab === "cloned"
    ) {
      isClone = true;
      console.log("🎤 Clone detected via Tab");
    }

    if (!isClone) {
      let voiceName = $("#selectedVoiceName").text().toLowerCase();
      if (voiceName.includes("clone") || voiceName.includes("(clone)")) {
        isClone = true;
        console.log("🎤 Clone detected via Voice Name:", voiceName);
      }
    }

    if (
      !isClone &&
      voiceId &&
      typeof loadedVoices !== "undefined" &&
      loadedVoices.minimax
    ) {
      let voiceObj = loadedVoices.minimax.find((v) => v.id == voiceId);

      if (voiceObj) {
        if (voiceObj.source === "cloned") {
          isClone = true;
          console.log("🎤 Clone detected via source:", voiceObj.source);
        }

        if (!isClone && voiceObj.tags && Array.isArray(voiceObj.tags)) {
          if (
            voiceObj.tags.includes("Clone") ||
            voiceObj.tags.includes("clone")
          ) {
            isClone = true;
            console.log("🎤 Clone detected via tags:", voiceObj.tags);
          }
        }
      }
    }

    if (isClone) {
      voice_multiplier = 1.3;
      console.log("✅ Voice Multiplier: 1.13Clone)");
    } else {
      voice_multiplier = 1.0;
      console.log("✅ Voice Multiplier: 1.0 (System)");
    }
  }
  // --- 🔥 ELEVENLABS: LOGIC MỚI (ĐỒNG NHẤT) ---
  else {
    let currentModelName = $("#selectedModelName").text();

    // 🔥 XÁC ĐỊNH COST FACTOR DỰA TRÊN TÊN MODEL
    if (currentModelName.includes("v3") || currentModelName.includes("V3")) {
      cost_factor = 1.3; // ✅ Eleven v3: x1.3
      console.log("💰 ElevenLabs v3 detected → Factor: 1.3");
    } else {
      cost_factor = 1.0; // ✅ TẤT CẢ CÁC MODEL KHÁC: x1.0
      console.log("💰 ElevenLabs Standard Model → Factor: 1.0");
    }

    voice_multiplier = 1.0; // ElevenLabs không có voice multiplier
  }

  // ==========================================
  // 3. 🔥 KIỂM TRA PHỤ ĐỀ (SRT) - ĐỒNG NHẤT +15%
  // ==========================================
  let srt_multiplier = 1.0;
  let with_transcript = false;

  if (currentProvider === "minimax") {
    with_transcript = $("#minimaxSubtitleCheck").is(":checked");
    if (with_transcript) {
      srt_multiplier = 1.15; // ✅ Minimax SRT: x1.15
      console.log("📄 Minimax SRT enabled → x1.15");
    }
  } else {
    with_transcript = $("#subtitleCheck").is(":checked");
    if (with_transcript) {
      srt_multiplier = 1.15; // 🔥 SỬA: ElevenLabs SRT: x1.15 (THAY VÌ x1.2)
      console.log("📄 ElevenLabs SRT enabled → x1.15");
    }
  }

  if (typeof window.isSrtFile !== "undefined" && window.isSrtFile === true) {
    with_transcript = true;
    srt_multiplier = 1.15; // 🔥 ĐỒNG NHẤT: x1.15 cho cả 2 provider
    console.log("📄 SRT file uploaded → x1.15");
  }

  // ==========================================
  // 4. 🔥 CÔNG THỨC MỚI
  // ==========================================
  let estimated_cost =
    charCount * cost_factor * voice_multiplier * srt_multiplier;

  // ==========================================
  // 5. 🔥 LÀM TRÒN: ≥ 0.5 → TRÒN LÊN
  // ==========================================
  let total_cost = Math.round(estimated_cost);
  total_cost = Math.max(1, total_cost);

  // ==========================================
  // 6. LOG KẾT QUẢ
  // ==========================================
  console.log("💰 COST CALCULATION:", {
    provider: currentProvider,
    chars: charCount,
    model_factor: cost_factor,
    voice_mult: voice_multiplier,
    srt_mult: srt_multiplier,
    with_transcript: with_transcript,
    result: estimated_cost.toFixed(2),
    final: total_cost,
  });

  return total_cost;
}
function startTTS() {
  if (!hasApiKey) {
    $("#apiKeyModal").fadeIn();
    return;
  }

  let text = $("#txtInput").val();
  let voiceId = $("#voiceIdVal").val();

  if (!voiceId) {
    showToast("⚠️ Vui lòng chọn giọng nói!");
    openVoiceModal();
    return;
  }

  if (!text) {
    showToast("⚠️ Vui lòng nhập nội dung!");
    $("#txtInput").focus();
    return;
  }

  // 🔥 GỌI TRỰC TIẾP
  proceedWithTTS();
}

// ✅ HÀM XỬ LÝ TTS CHÍNH (TTS2 - KHÔNG CÓ GENAI BACKUP)
// ✅ HÀM XỬ LÝ TTS CHÍNH (TTS2 - KHÔNG CÓ GENAI BACKUP)
function proceedWithTTS() {
  console.log("🚀 proceedWithTTS() started");

  // ============================================================
  // 1. LẤY DỮ LIỆU TỪ FORM
  // ============================================================

  let text = $("#txtInput").val().trim();
  let voiceId = $("#voiceIdVal").val();
  let voiceName = $("#selectedVoiceName").text() || "Unknown";

  // Validate text
  if (!text || text.length === 0) {
    showNotification("error", "Vui lòng nhập nội dung văn bản");
    return;
  }

  // Validate voice
  if (!voiceId) {
    showNotification("error", "Vui lòng chọn giọng đọc");
    return;
  }

  console.log("📝 Text length:", text.length);
  console.log("🎙️ Voice ID:", voiceId);
  console.log("🎯 Provider:", currentProvider);

  // ============================================================
  // 2. XÁC ĐỊNH CHECKBOX PHỤ ĐỀ ĐÚNG THEO PROVIDER
  // ============================================================

  let with_transcript = false;

  if (currentProvider === "minimax") {
    with_transcript = $("#minimaxSubtitleCheck").is(":checked");
  } else {
    with_transcript = $("#subtitleCheck").is(":checked");
  }

  // Override nếu đang xử lý file SRT
  if (window.isSrtFile === true) {
    with_transcript = true;
  }

  console.log("📄 with_transcript:", with_transcript);

  // ============================================================
  // 3. BUILD PAYLOAD THEO PROVIDER
  // ============================================================

  let params = {
    action: "create_speech",
    provider: currentProvider,
    text: text,
    voice_id: voiceId,
    voice_name: voiceName,
    with_transcript: with_transcript,
  };

  if (currentProvider === "minimax") {
    // --- MINIMAX SETTINGS ---
    params.model_id = selectedMinimaxModel || "speech-2.6-turbo";
    params.vol = parseFloat($("#vol").val() || 1.0);
    params.speed = parseFloat($("#speed").val() || 1.0);
    params.pitch = parseInt($("#pitch").val() || 0);
    params.language_boost = selectedLanguage || "Auto";

    console.log("🤖 Minimax Settings:", {
      model: params.model_id,
      speed: params.speed,
      pitch: params.pitch,
      vol: params.vol,
      language: params.language_boost,
    });
  } else {
    // --- ELEVENLABS SETTINGS ---

    // Xác định Model ID an toàn
    let currentModelName = $("#selectedModelName").text();
    let modelsList = loadedModels.elevenlabs || [];
    let model = modelsList.find((m) => currentModelName.includes(m.name));

    if (model) {
      params.model_id = model.id;
    } else if (modelsList.length > 0) {
      params.model_id = modelsList[0].id;
    } else {
      params.model_id = "eleven_multilingual_v2"; // Fallback
    }

    params.speed = parseFloat($("#elevenSpeed").val() || 1.0);
    params.stability = parseFloat($("#stability").val() || 50) / 100;
    params.similarity = parseFloat($("#similarity").val() || 75) / 100;
    params.style = parseFloat($("#style").val() || 0) / 100;
    params.use_boost = $("#boostCheck").is(":checked");

    console.log("🤖 ElevenLabs Settings:", {
      model: params.model_id,
      speed: params.speed,
      stability: params.stability,
      similarity: params.similarity,
      style: params.style,
      boost: params.use_boost,
    });
  }

  // ============================================================
  // 4. TÍNH CHI PHÍ ƯỚC TÍNH
  // ============================================================

  let estimatedCost = calculateSingleCost(text);
  console.log("💰 Estimated cost:", estimatedCost);

  // Lấy balance hiện tại
  let currentBalance =
    parseInt($("#userCredits").text().replace(/,/g, "")) || 0;

  // Kiểm tra đủ tiền không
  if (currentBalance < estimatedCost) {
    showNotification(
      "error",
      `Không đủ credits! Cần ${estimatedCost.toLocaleString()}, còn ${currentBalance.toLocaleString()}`,
    );
    return;
  }

  // ============================================================
  // 5. UI LOADING STATE
  // ============================================================

  $("#btnProcess")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm"></span> <span>Đang gửi...</span>',
    );

  $("#inputLoader").addClass("show");

  console.log("⏳ Sending request to server...");

  // ============================================================
  // 6. GỬI REQUEST TẠO TASK
  // ============================================================

  $.ajax({
    url: "/../../ajaxs/tts2.php",
    method: "POST",
    data: params,
    dataType: "json",
    timeout: 20000, // 20 seconds timeout

    // --- SUCCESS HANDLER ---
    success: function (res) {
      console.log("✅ API Response received:", res);

      if (res.status === "success" || res.success === true) {
        let taskId = res.task_id || res.job_id;

        if (!taskId) {
          console.error("⚠️ No task_id in response:", res);
          showNotification("error", "Lỗi: Không nhận được Task ID từ server");
          resetUI();
          return;
        }

        console.log("🎯 Task ID:", taskId);

        // --- BƯỚC 1: TRỪ TIỀN TẠM THEO ƯỚC TÍNH ---
        let tempBalance = currentBalance - estimatedCost;
        $("#userCredits").text(tempBalance.toLocaleString());
        console.log("💸 Temporary balance:", tempBalance);

        // --- BƯỚC 2: THÊM PENDING CARD ---
        let textPreview =
          text.length > 100 ? text.substring(0, 100) + "..." : text;
        addPendingCard(taskId, textPreview, estimatedCost, currentProvider);
        console.log("🔥 ADD PENDING CARD:", taskId);

        // --- BƯỚC 3: LẤY CREDIT_COST THỰC TẾ TỪ SERVER ---
        fetchRealCreditCost(taskId, estimatedCost, currentBalance);

        // --- BƯỚC 4: BẮT ĐẦU POLLING ---
        startPolling(taskId);

        // --- BƯỚC 5: CHUYỂN TAB & RESET UI ---
        switchTab("history");
        resetUI();

        // --- BƯỚC 6: 🔥 REFRESH CREDITS SAU 1 GIÂY ---
        setTimeout(() => {
          refreshUserCredits();
          console.log("🔄 Auto-refresh credits after task creation");
        }, 1000);

        showNotification("success", "✅ Task đã được tạo, đang xử lý...");
      } else {
        // Response có success = false
        let errorMsg = res.message || "Lỗi không xác định";
        console.error("❌ Server error:", errorMsg);
        showNotification("error", errorMsg);
        resetUI();
      }
    },

    // --- ERROR HANDLER ---
    error: function (xhr, status, error) {
        // 🔥 [MỚI] BẮT LỖI RATE LIMIT (429)
    if (xhr.status === 429) {
        let msg = 'Vui lòng thử lại sau.';
        try { msg = JSON.parse(xhr.responseText).message; } catch(e){}
        
        $("#btnProcess").prop("disabled", false).html('<i class="bi bi-magic"></i> Tạo âm thanh');
        $("#inputLoader").removeClass("show");
        
        showRateLimitPopup(msg);
        return;
    }
      console.error("❌ AJAX Error:", { xhr, status, error });

      // --- XỬ LÝ TIMEOUT ---
      if (status === "timeout") {
        console.warn("⏰ Request timeout, creating pending card...");

        // Trừ tiền tạm
        let newBalance = currentBalance - estimatedCost;
        $("#userCredits").text(newBalance.toLocaleString());

        // Tạo temp task ID
        let tempTaskId = "temp_" + Date.now();
        let textPreview =
          text.length > 100 ? text.substring(0, 100) + "..." : text;

        addPendingCard(tempTaskId, textPreview, estimatedCost, currentProvider);

        // Sau 3s thử poll để lấy task thật
        setTimeout(() => {
          pollForNewTask(tempTaskId, text);
        }, 3000);

        switchTab("history");
        resetUI();

        showNotification(
          "warning",
          "⏳ Yêu cầu đang xử lý ngầm, vui lòng chờ...",
        );

        // 🔥 REFRESH CREDITS SAU 2 GIÂY
        setTimeout(() => {
          refreshUserCredits();
        }, 2000);

        return;
      }

      // --- XỬ LÝ LỖI KHÁC ---
      let errorMsg = "Lỗi kết nối";

      try {
        let errRes = JSON.parse(xhr.responseText);
        if (errRes.message) {
          errorMsg = errRes.message;
        } else if (errRes.error) {
          errorMsg = errRes.error;
        }
      } catch (e) {
        // Không parse được JSON
        if (xhr.responseText) {
          errorMsg = xhr.responseText.substring(0, 200);
        }
      }

      console.error("📛 Final error message:", errorMsg);
      showNotification("error", "❌ " + errorMsg);
      resetUI();
    },

    // --- COMPLETE HANDLER (luôn chạy) ---
    complete: function () {
      console.log("🏁 Request complete");
    },
  });
}

// 🔥 AUTO-REFRESH CREDITS MỖI 10 GIÂY
let creditsRefreshInterval = null;

function startCreditsAutoRefresh() {
  // Clear interval cũ nếu có
  if (creditsRefreshInterval) {
    clearInterval(creditsRefreshInterval);
  }

  // Refresh ngay lần đầu
  refreshUserCredits();

  // Sau đó refresh mỗi 10 giây
  creditsRefreshInterval = setInterval(() => {
    refreshUserCredits();
  }, 10000); // 10 seconds

  console.log("🔄 Auto-refresh credits started (every 10s)");
}

function stopCreditsAutoRefresh() {
  if (creditsRefreshInterval) {
    clearInterval(creditsRefreshInterval);
    creditsRefreshInterval = null;
    console.log("⏹️ Auto-refresh credits stopped");
  }
}

function refreshUserCredits() {
  $.ajax({
    url: "../ajaxs/tts2.php",
    type: "POST",
    data: { action: "get_user_credits" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const credits = parseInt(response.credits || 0);

        // 🔥 FORMAT SỐ CREDITS ĐÚNG
        let formattedCredits = credits.toLocaleString("en-US"); // 80,537

        // Hoặc nếu muốn dùng dấu chấm (kiểu VN)
        // let formattedCredits = credits.toLocaleString('vi-VN'); // 80.537

        // Cập nhật UI
        $("#userCredits").text(formattedCredits);

        console.log(
          "💰 Credits updated:",
          credits,
          "→ Display:",
          formattedCredits,
        );

        // Cập nhật màu sắc
        if (credits < 1000) {
          $("#userCredits").css("color", "#ef4444");
        } else if (credits < 5000) {
          $("#userCredits").css("color", "#f59e0b");
        } else {
          $("#userCredits").css("color", "#10b981");
        }
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ AJAX error fetching credits:", error);
    },
  });
}
/**
 * 🔥 GỌI API LẤY CREDIT_COST THỰC TẾ TỪ SERVER
 * Sau khi tạo task xong, gọi GET /v1/task/:task_id để lấy chi phí chính xác
 */
function fetchRealCreditCost(taskId, estimatedCost, originalBalance) {
  console.log("💰 Fetching real credit cost for task:", taskId);

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "get_real_cost",
      task_id: taskId,
    },
    dataType: "json",
    timeout: 5000,
    success: function (res) {
      if (res.status === "success" && res.credit_cost !== undefined) {
        let realCost = parseInt(res.credit_cost);

        console.log("💰 Real Cost:", {
          task_id: taskId,
          estimated: estimatedCost,
          real: realCost,
          diff: realCost - estimatedCost,
        });

        // 🔥 NẾU KHÁC BIỆT → ĐIỀU CHỈNH SỐ DƯ
        if (realCost !== estimatedCost) {
          let diff = realCost - estimatedCost;
          let currentBalance = parseInt(
            $("#userCredits").text().replace(/,/g, ""),
          );
          let correctedBalance = currentBalance - diff;

          $("#userCredits").text(correctedBalance.toLocaleString());

          console.log("💰 Balance Adjusted:", {
            before: currentBalance,
            diff: diff,
            after: correctedBalance,
          });
        }

        // 🔥 CẬP NHẬT COST TRÊN CARD
        $(`#card-${taskId} .hc-cost`).text(realCost);

        showToast(`💰 Chi phí thực tế: ${realCost} credits`);
      } else {
        console.warn(
          "⚠️ Cannot get real cost, using estimated:",
          estimatedCost,
        );
      }
    },
    error: function (xhr, status, error) {
      console.error("❌ Failed to fetch real cost:", error);
      // Giữ nguyên estimated cost nếu lỗi
    },
  });
}
function updateEstimatedCost() {
  let text = $("#txtInput").val() || "";
  let charCount = text.length;

  // 🔥 [MỚI] CẬP NHẬT SỐ KÝ TỰ Ở HEADER
  $("#charCount").text(charCount.toLocaleString());

  // 🔥 ĐỔI MÀU THEO NGƯỠNG (CHỈ ĐỔI MÀU SỐ)
  let $charDisplay = $("#charDisplay");
  if (charCount > 50000) {
    $charDisplay.removeClass("warning").addClass("danger");
  } else if (charCount > 10000) {
    $charDisplay.removeClass("danger").addClass("warning");
  } else {
    $charDisplay.removeClass("warning danger");
  }

  // Tính toán chi phí cơ bản
  let cost = 0;
  if (charCount > 0) {
    cost = calculateSingleCost(text);
  }

  let isGenAIBackup =
    typeof elevenlabsDown !== "undefined" &&
    elevenlabsDown &&
    typeof backupEligible !== "undefined" &&
    backupEligible;

  if (currentProvider === "minimax") {
    $("#minimax-cost-ui").attr("style", "display: block !important");
    $("#elevenlabs-cost-ui").attr("style", "display: none !important");

    // ============================================================
    // 🔍 KIỂM TRA MODEL HD
    // ============================================================
    let isHDModel =
      selectedMinimaxModel === "speech-2.6-hd" ||
      selectedMinimaxModel === "speech-02-hd";

    // ============================================================
    // 🔍 KIỂM TRA VOICE CLONE
    // ============================================================
    let isClone = false;
    let voiceId = $("#voiceIdVal").val();

    // Check Tab
    if (
      typeof currentVoiceTab !== "undefined" &&
      currentVoiceTab === "cloned"
    ) {
      isClone = true;
    }

    // Check tên
    if (!isClone) {
      let voiceName = $("#selectedVoiceName").text().toLowerCase();
      if (voiceName.includes("clone") || voiceName.includes("(clone)")) {
        isClone = true;
      }
    }

    // Check data
    if (
      !isClone &&
      voiceId &&
      typeof loadedVoices !== "undefined" &&
      loadedVoices.minimax
    ) {
      let voiceObj = loadedVoices.minimax.find((v) => v.id == voiceId);
      if (voiceObj) {
        if (voiceObj.source === "cloned") isClone = true;
        if (voiceObj.tags && Array.isArray(voiceObj.tags)) {
          if (
            voiceObj.tags.includes("Clone") ||
            voiceObj.tags.includes("clone")
          ) {
            isClone = true;
          }
        }
      }
    }

    // ============================================================
    // 🎨 CẬP NHẬT BADGE (Hiển thị cả HD + Clone)
    // ============================================================
    let badgeHtml = "";

    if (isHDModel && isClone) {
      // Cả 2: HD + Clone
      badgeHtml =
        '<i class="bi bi-stars" style="margin-right: 4px;"></i>+15% (HD) + 30% (Clone)';
    } else if (isHDModel) {
      // Chỉ HD
      badgeHtml =
        '<i class="bi bi-stars" style="margin-right: 4px;"></i>+15% (Model HD)';
    } else if (isClone) {
      // Chỉ Clone
      badgeHtml =
        '<i class="bi bi-exclamation-triangle-fill" style="margin-right: 4px;"></i>+30% (Giọng Clone)';
    }

    if (badgeHtml) {
      $("#minimax-badge")
        .html(badgeHtml)
        .css({
          display: "flex",
          "align-items": "center",
          color: "#fbbf24",
          "font-weight": "600",
          "margin-left": "auto",
          "margin-right": "10px",
        })
        .show();
    } else {
      $("#minimax-badge").hide().empty();
    }

    // Cập nhật số credits
    $("#estimatedCostDisplay, #minimaxEstimatedCostDisplay").html(
      `<span id="estimatedCost">${cost.toLocaleString()}</span> credits`,
    );
  } else {
    // --- LOGIC ELEVENLABS ---
    $("#elevenlabs-cost-ui").attr("style", "display: block !important");
    $("#minimax-cost-ui").attr("style", "display: none !important");
    $("#minimax-badge").hide();

    if (isGenAIBackup) {
      // 🔥 TRƯỜNG HỢP BACKUP: MIỄN PHÍ + DISABLE PHỤ ĐỀ
      $("#estimatedCostDisplay").html(
        '<span class="badge bg-success">Miễn phí (Backup)</span>',
      );

      // 1. Disable Checkbox phụ đề
      $("#subtitleCheck").prop("checked", false).prop("disabled", true);

      // 2. Làm mờ giao diện toggle để user hiểu là không bấm được
      $("#elevenlabs-cost-ui .toggle-switch").css({
        opacity: "0.5",
        "pointer-events": "none", // Chặn click chuột
      });

      // 3. Ẩn badge +15% phí đi cho đỡ rối
      $("#elevenlabs-badge").hide();
    } else {
      // 🔥 TRƯỜNG HỢP BÌNH THƯỜNG: HIỆN LẠI
      $("#estimatedCostDisplay").html(
        `<span id="estimatedCost">${cost.toLocaleString()}</span> credits`,
      );

      // 1. Enable lại Checkbox
      $("#subtitleCheck").prop("disabled", false);

      // 2. Khôi phục giao diện
      $("#elevenlabs-cost-ui .toggle-switch").css({
        opacity: "1",
        "pointer-events": "auto",
      });

      // 3. Hiện lại badge phí
      $("#elevenlabs-badge").show();
    }
  }
  updateCostTooltip();
}
// ✅ RESET UI NHANH HƠN
function resetUI() {
  $("#btnProcess")
    .prop("disabled", false)
    .html('<i class="bi bi-magic"></i> <span>Tạo Giọng Nói</span>');
  $("#inputLoader").removeClass("show");
}
// 🔥 HÀM POLL TÌM TASK MỚI (KHI TIMEOUT)
function pollForNewTask(tempTaskId, originalText) {
  let attempts = 0;
  let maxAttempts = 10;

  let interval = setInterval(() => {
    attempts++;

    $.post(
      "../../ajaxs/tts.php",
      {
        action: "find_recent_task",
        text_snippet: originalText.substring(0, 50),
      },
      function (res) {
        if (res.status === "success" && res.task_id) {
          // Tìm thấy task thật
          clearInterval(interval);

          // Cập nhật card
          $(`#card-${tempTaskId}`).attr("id", `card-${res.task_id}`);
          $(`#status-${tempTaskId}`).attr("id", `status-${res.task_id}`);

          // Bắt đầu poll thật
          startPolling(res.task_id);

          console.log("✅ Found real task:", res.task_id);
        }
      },
      "json",
    ).fail(function () {
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
  $(".custom-toast").remove();

  let toast = $('<div class="custom-toast">')
    .css({
      position: "fixed",
      bottom: "24px",
      left: "50%",
      transform: "translateX(-50%)",
      background: "#1a1a1a",
      color: "white",
      padding: "12px 24px",
      borderRadius: "8px",
      zIndex: 99999,
      fontSize: "13px",
      fontWeight: "500",
      boxShadow: "0 4px 12px rgba(0,0,0,0.4)",
      border: "1px solid #333",
    })
    .text(msg);

  $("body").append(toast);
  setTimeout(() => toast.fadeOut(300, () => toast.remove()), 2500);
}
// Biến toàn cục để lưu trữ các interval đang chạy (nếu cần)
// let pollingIntervals = {};

function startPolling(taskId) {
  let attempts = 0;
  const maxAttempts = 200;

  // Polling API mỗi 3 giây
  let interval = setInterval(() => {
    // Kiểm tra nếu card không còn tồn tại hoặc đã xử lý xong thì dừng
    let $card = $(`#card-${taskId}`);
    if ($card.length === 0 || !$card.hasClass("processing")) {
      clearInterval(interval);
      return;
    }

    attempts++;

    // Kiểm tra Timeout
    if (attempts >= maxAttempts) {
      clearInterval(interval);
      updateCardToFailed(taskId);
      $(`#time-elapsed-${taskId}`).text("Timeout");
      return;
    }

    $.ajax({
      url: "../../ajaxs/tts2.php",
      method: "POST",
      data: { action: "check_status", task_id: taskId },
      dataType: "json",
      timeout: 10000,
      success: function (res) {
        // Cập nhật thanh Progress bar
        let percent = res.progress || 0;
        $(`#progress-${taskId}`).css("width", percent + "%");
        $(`#progress-${taskId}`).attr("data-progress", percent);

        // TRƯỜNG HỢP: ĐANG CHẠY
        if (
          res.status === "doing" ||
          res.task_status === "processing" ||
          res.status === "pending"
        ) {
          // Hiển thị % tiến độ
          $(`#time-elapsed-${taskId}`).text(percent + "%");
        }

        // TRƯỜNG HỢP: ĐANG CHỜ HÀNG ĐỢI
        else if (res.status === "queued") {
          let queueText = res.queue_position
            ? `Hàng đợi #${res.queue_position}`
            : "Đang chờ...";
          $(`#time-elapsed-${taskId}`).text(queueText);
        }

        // TRƯỜNG HỢP: HOÀN THÀNH
        // TRƯỜNG HỢP: HOÀN THÀNH
        else if (res.status === "done" || res.task_status === "done") {
          clearInterval(interval);

          // Cập nhật UI hoàn thành
          $(`#progress-${taskId}`).css("width", "100%");

          $(`#icon-spin-${taskId}`)
            .removeClass("spinning bi-arrow-repeat")
            .addClass("bi-check-circle-fill");

          $(`#time-elapsed-${taskId}`).text("Hoàn thành");
          $card.removeClass("processing");

          // 🔥 GỌI API /v1/task/:task_id ĐỂ LẤY CHÍNH XÁC URLs & Duration
          $.ajax({
            url: "../../ajaxs/tts2.php",
            method: "POST",
            data: {
              action: "get_task_urls",
              task_id: taskId,
            },
            dataType: "json",
            timeout: 5000,
            success: function (urlRes) {
              console.log("✅ Task URLs fetched:", urlRes);

              // 🔥 HELPER: Fix CDN URL
              function fixCdnUrl(url) {
                if (!url) return null;
                if (url.startsWith("https://") || url.startsWith("http://")) {
                  return url;
                }
                return "https://cdn.ai84.pro/" + url.replace(/^\/+/, "");
              }

              // Lấy URLs từ response
              let audio = fixCdnUrl(
                urlRes.audio_url ||
                  res.audio_url ||
                  (res.metadata ? res.metadata.audio_url : null),
              );

              // 🔥 SỬA: transcript_url thay vì srt_url
              let srt = fixCdnUrl(
                urlRes.transcript_url ||
                  urlRes.srt_url ||
                  res.transcript_url ||
                  res.srt_url ||
                  (res.metadata ? res.metadata.transcript_url : null) ||
                  (res.metadata ? res.metadata.srt_url : null),
              );

              let json = fixCdnUrl(
                urlRes.json_url ||
                  res.json_url ||
                  (res.metadata ? res.metadata.json_url : null),
              );
              let duration =
                urlRes.duration ||
                res.duration ||
                (res.metadata ? res.metadata.duration : null);

              console.log("📦 Final URLs:", { audio, srt, json, duration });

              // Hiển thị Player
              setTimeout(() => {
                $(`#track-${taskId}`).fadeOut();
                updateCardToDone(taskId, audio, srt, json, duration);
              }, 500);
            },
            error: function () {
              // 🔥 FALLBACK - SỬA TƯƠNG TỰ
              console.warn("⚠️ Failed to fetch URLs, using fallback");

              let audio =
                res.audio_url || (res.metadata ? res.metadata.audio_url : null);

              let srt =
                res.transcript_url ||
                res.srt_url ||
                (res.metadata ? res.metadata.transcript_url : null) ||
                (res.metadata ? res.metadata.srt_url : null);

              let json =
                res.json_url || (res.metadata ? res.metadata.json_url : null);
              let duration =
                res.duration || (res.metadata ? res.metadata.duration : null);

              setTimeout(() => {
                $(`#track-${taskId}`).fadeOut();
                updateCardToDone(taskId, audio, srt, json, duration);
              }, 500);
            },
          });
        }

        // TRƯỜNG HỢP: LỖI
        else if (res.status === "error" || res.task_status === "failed") {
          clearInterval(interval);

          $(`#track-${taskId}`).hide();
          $(`#icon-spin-${taskId}`)
            .removeClass("spinning bi-arrow-repeat")
            .addClass("bi-exclamation-triangle-fill");

          $(`#time-elapsed-${taskId}`).text("Thất bại");
          $card.removeClass("processing");

          updateCardToFailed(taskId);
        }
      },
      error: function () {
        console.log("Network glitch, retrying...");
      },
    });
  }, 3000);
}

// ========== SSE PROGRESS STREAM ==========
function startSSEProgress(historyId, genaiTaskId) {
  console.log("🔔 Starting SSE for task:", genaiTaskId);

  const eventSource = new EventSource(
    `../../ajaxs/genai_progress_stream.php?task_id=${genaiTaskId}`,
  );

  eventSource.addEventListener("progress", (e) => {
    const data = JSON.parse(e.data);
    const progress = data.progress;

    console.log(`🔔 SSE Progress: ${progress}%`);

    $(`#status-${historyId}`)
      .text(`Xử lý ${progress}%`)
      .addClass("status-pending");

    $(`#progress-${historyId}`).css("width", progress + "%");
  });

  eventSource.addEventListener("complete", (e) => {
    console.log("✅ SSE Complete");
    eventSource.close();

    // Gọi API để lấy kết quả cuối
    checkHistoryComplete(historyId);
  });

  eventSource.addEventListener("error", (e) => {
    console.error("❌ SSE Error:", e);
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
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "check_history_status",
      history_id: historyId,
    },
    success: function (res) {
      if (res.status === "done") {
        updateCardToDone(historyId, res.audio_url, res.srt_url, res.json_url);
        showToast("✅ Hoàn thành!");
      }
    },
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
      console.warn("⏰ Queue polling timeout:", historyId);
      $(`#status-${historyId}`)
        .text("Timeout - Vui lòng refresh")
        .removeClass("status-queued status-pending")
        .addClass("status-failed");
      return;
    }

    $.ajax({
      url: "../../ajaxs/tts2.php",
      method: "POST",
      data: {
        action: "check_history_status",
        history_id: historyId,
      },
      dataType: "json",
      timeout: 5000,
      success: function (res) {
        console.log(`📊 History Status [${attempts}]:`, res);

        let progress = res.progress || 0;
        let queuePosition = res.queue_position;
        let totalSlots = res.total_slots || 60;
        let processingCount = res.processing_count || 0;

        // ✅ ĐANG CHỜ QUEUE
        if (res.status === "queued") {
          let statusText = "";

          if (queuePosition) {
            // Tính thời gian ước tính (mỗi task ~30s)
            let estimatedMinutes = Math.ceil((queuePosition * 30) / 60);

            statusText = `Vị trí #${queuePosition} (≈${estimatedMinutes} phút)`;
          } else {
            statusText = "Đang chờ xử lý...";
          }

          $(`#status-${historyId}`)
            .text(statusText)
            .removeClass("status-pending")
            .addClass("status-queued");

          pollInterval = 5000; // Poll chậm
          setTimeout(poll, pollInterval);
        }

        // ✅ ĐANG XỬ LÝ
        else if (res.status === "pending") {
          let statusText =
            progress > 0 ? `Xử lý ${progress}%` : "Đang xử lý...";

          $(`#status-${historyId}`)
            .text(statusText)
            .removeClass("status-queued")
            .addClass("status-pending");

          // Update progress bar nếu có
          if (progress > 0) {
            $(`#progress-${historyId}`).css("width", progress + "%");
          }

          pollInterval = 2000; // Poll nhanh
          setTimeout(poll, pollInterval);
        }

        // ✅ HOÀN THÀNH
        else if (res.status === "done") {
          updateCardToDone(historyId, res.audio_url, res.srt_url, res.json_url);
          showToast("✅ Hoàn thành!", "success");
          // STOP polling
        }

        // ✅ THẤT BẠI
        else if (res.status === "failed") {
          updateCardToFailed(historyId);
          let errorMsg = res.error || "Lỗi không xác định";
          showToast("❌ Thất bại: " + errorMsg, "error");
          // STOP polling
        }
      },
      error: function (xhr, status, error) {
        console.log("⚠️ Network error, retrying...", error);
        setTimeout(poll, 5000);
      },
    });
  };

  // Start polling ngay lập tức
  poll();
}
// ========== CLONE VOICE ==========
function submitCloneVoice() {
  let name = $("#cloneName").val().trim();
  let fileInput = $("#cloneFile")[0].files[0];
  let gender = $("#cloneGender").val();

  if (!name) {
    alert("Vui lòng nhập tên giọng!");
    return;
  }

  if (!fileInput) {
    alert("Vui lòng chọn file MP3!");
    return;
  }

  if (fileInput.type !== "audio/mpeg" && !fileInput.name.endsWith(".mp3")) {
    alert("Chỉ hỗ trợ file .mp3");
    return;
  }

  if (fileInput.size > 20 * 1024 * 1024) {
    alert("File quá lớn! Tối đa 20MB");
    return;
  }

  let formData = new FormData();
  formData.append("action", "create_clone");
  formData.append("voice_name", name);
  formData.append("gender", gender);
  formData.append("file", fileInput);

  $("#btnSubmitClone")
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm"></span> <span>Đang upload...</span>',
    );

  $.ajax({
    url: "../../ajaxs/voice_cloning2.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        alert("✅ Clone thành công! Giọng mới đã được thêm vào thư viện.");
        $("#cloneModal").fadeOut();
        loadResources();

        // Reset form
        $("#cloneName").val("");
        $("#cloneFile").val("");
      } else {
        alert("❌ Lỗi: " + res.message);
      }
      $("#btnSubmitClone")
        .prop("disabled", false)
        .html('<i class="bi bi-mic"></i> <span>Bắt đầu Clone</span>');
    },
    error: function () {
      alert("❌ Lỗi kết nối server");
      $("#btnSubmitClone")
        .prop("disabled", false)
        .html('<i class="bi bi-mic"></i> <span>Bắt đầu Clone</span>');
    },
  });
}

function loadHistory() {
  if (isLoadingHistory || !hasMoreHistory) return;

  isLoadingHistory = true;
  $("#loadingMore").show();

  // 🔥 GỌI API AI84 (GIỐNG MODAL CHI TIẾT)
  $.post(
    "../../ajaxs/tts2.php",
    {
      action: "get_history_sidebar", // ← ENDPOINT MỚI
      page: Math.floor(currentOffset / 15) + 1,
      limit: 15,
    },
    function (res) {
      console.log("📥 Sidebar History Response:", res);

      if (res.status === "success") {
        // Nếu là trang đầu tiên thì xóa list cũ
        if (currentOffset === 0) {
          $("#historyListContainer").empty();
        }

        // Xử lý empty state
        if (res.data.length === 0 && currentOffset === 0) {
          $("#historyListContainer").html(`
                    <div style="text-align:center; padding:60px 20px; color:#666;">
                        <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                        <p style="font-size:14px;">Chưa có lịch sử nào</p>
                    </div>
                `);
          $("#loadingMore").hide();
          $("#noMoreData").hide();
          hasMoreHistory = false;
          isLoadingHistory = false;
          return;
        }

        // Render từng task
        res.data.forEach((item) => {
          let createdTimeMs = new Date(item.created_at).getTime();

          // Lưu vào map
          historyDataMap[item.task_id] = item;

          // 🔥 FIX URLs TRƯỚC KHI THÊM CARD
          let audio = item.audio_url || "";
          let srt = item.srt_url || "";
          let json = item.json_url || "";

          // Gắn token cho SRT/JSON nếu thiếu
          if (srt && !srt.includes("?token=") && audio.includes("?token=")) {
            let match = audio.match(/[?&]token=([^&]+)/);
            if (match) {
              srt += (srt.includes("?") ? "&" : "?") + "token=" + match[1];
            }
          }

          if (json && !json.includes("?token=") && audio.includes("?token=")) {
            let match = audio.match(/[?&]token=([^&]+)/);
            if (match) {
              json += (json.includes("?") ? "&" : "?") + "token=" + match[1];
            }
          }

          // Thêm card vào giao diện
          addHistoryCard(
            item.task_id,
            item.text_input,
            item.credit_cost,
            item.created_at_display,
            item.provider,
            item.status,
            true,
            createdTimeMs,
          );

          // Xử lý trạng thái
          if (item.status === "pending" || item.status === "processing") {
            startPolling(item.task_id);
          } else if (item.status === "done") {
            updateCardToDone(item.task_id, audio, srt, json, item.duration);
          } else if (item.status === "failed") {
            updateCardToFailed(item.task_id);
          }
        });

        // Cập nhật offset và pagination
        currentOffset += res.data.length;
        hasMoreHistory = res.pagination.has_more;

        if (!hasMoreHistory) {
          $("#noMoreData").show();
        }
      } else {
        console.error("❌ API Error:", res.message);
      }

      $("#loadingMore").hide();
      isLoadingHistory = false;
    },
    "json",
  ).fail(function (xhr, status, error) {
    console.error("❌ AJAX Error:", error);
    console.error("Response:", xhr.responseText);

    $("#loadingMore").hide();
    isLoadingHistory = false;
  });
}

function setupInfiniteScroll() {
  $("#viewHistory").on("scroll", function () {
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
  mainAudio.addEventListener("timeupdate", function () {
    if (currentPlayingTaskId) {
      let currentTime = mainAudio.currentTime;
      let duration = mainAudio.duration;

      if (isNaN(duration)) return;

      let progress = (currentTime / duration) * 100;
      let timeString = formatTime(currentTime); // + ' / ' + formatTime(duration);

      // ✅ Cập nhật Sidebar (Cũ)
      $(`#progress-${currentPlayingTaskId}`).css("width", progress + "%");
      $(`#time-current-${currentPlayingTaskId}`).text(timeString);

      // ✅ Cập nhật Modal Chi tiết (Mới) - Tìm theo ID có tiền tố dh-
      $(`#dh-progress-${currentPlayingTaskId}`).css("width", progress + "%");
      $(`#dh-timer-${currentPlayingTaskId}`).text(
        timeString + " / " + formatTime(duration),
      );
    }
  });
  // 🔥 [THÊM MỚI] 2. Khi load xong metadata (duration)
  mainAudio.addEventListener("loadedmetadata", function () {
    if (currentPlayingTaskId) {
      let duration = mainAudio.duration;
      if (duration && !isNaN(duration)) {
        let durationText = formatTime(duration);

        // Cập nhật Sidebar
        $(`#time-total-${currentPlayingTaskId}`).text(durationText);

        // Cập nhật Modal
        let currentText = $(`#dh-timer-${currentPlayingTaskId}`).text();
        if (currentText.includes("/ --:--")) {
          $(`#dh-timer-${currentPlayingTaskId}`).text(`0:00 / ${durationText}`);
        }
      }
    }
  });

  // 2. Khi Play (Đổi icon Play -> Pause)
  mainAudio.addEventListener("play", function () {
    if (currentPlayingTaskId) {
      // Đổi icon Sidebar
      $(`#play-btn-${currentPlayingTaskId}`).html(
        '<i class="bi bi-pause-fill"></i>',
      );
      // Đổi icon Modal
      $(`#dh-play-btn-${currentPlayingTaskId}`).html(
        '<i class="bi bi-pause-fill"></i>',
      );
    }
  });

  // 3. Khi Pause hoặc Kết thúc (Đổi icon Pause -> Play)
  ["pause", "ended"].forEach((event) => {
    mainAudio.addEventListener(event, function () {
      if (currentPlayingTaskId) {
        // Reset icon Sidebar
        $(`#play-btn-${currentPlayingTaskId}`).html(
          '<i class="bi bi-play-fill"></i>',
        );
        // Reset icon Modal
        $(`#dh-play-btn-${currentPlayingTaskId}`).html(
          '<i class="bi bi-play-fill"></i>',
        );

        if (event === "ended") currentPlayingTaskId = null;
      }
    });
  });
}
function formatTime(seconds) {
  if (isNaN(seconds)) return "0:00";
  let mins = Math.floor(seconds / 60);
  let secs = Math.floor(seconds % 60);
  return mins + ":" + (secs < 10 ? "0" : "") + secs;
}
// ========== DELETE TASK WITH API REFUND ==========
function deleteTask(taskId, originalCost) {
  let currentProgress = parseInt(
    $(`#progress-${taskId}`).attr("data-progress") || 0,
  );
  let cardStatus = $(`#card-${taskId}`).hasClass("processing")
    ? "processing"
    : "done";

  console.log("🔍 DELETE DEBUG:", {
    taskId: taskId,
    originalCost: originalCost,
    currentProgress: currentProgress,
    cardStatus: cardStatus,
  });

  // Disable buttons
  $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span>');

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "delete_task_with_refund",
      task_id: taskId,
      current_progress: currentProgress,
      original_cost: originalCost,
    },
    dataType: "json",
    success: function (res) {
      console.log("✅ DELETE RESPONSE:", res);

      if (res.status === "success") {
        let refundAmount = res.refund_credits || 0;

        // Cập nhật credits
        if (refundAmount > 0) {
          let currentBalance = parseInt(
            $("#userCredits")
              .text()
              .replace(/[^0-9]/g, ""),
          );
          let newBalance = currentBalance + refundAmount;
          $("#userCredits").text(newBalance.toLocaleString());

          showToast(`✅ Đã xóa task và hoàn ${refundAmount} credits`);
        } else {
          showToast("✅ Đã xóa task");
        }

        // Xóa khỏi Sidebar
        $(`#card-${taskId}`).fadeOut(300, function () {
          $(this).remove();
        });

        // Xóa khỏi Modal Chi tiết
        $(`#row-${taskId}`).fadeOut(300, function () {
          $(this).remove();

          if (typeof updateBulkActions === "function") {
            updateBulkActions();
          }
        });
      } else {
        alert("❌ Lỗi: " + res.message);
        $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
          .prop("disabled", false)
          .html('<i class="bi bi-trash"></i>');
      }
    },
    error: function (xhr) {
      console.error("❌ DELETE ERROR:", xhr.responseText);
      alert("❌ Lỗi kết nối server");
      $(`#btn-delete-${taskId}, .dh-delete-btn[onclick*="${taskId}"]`)
        .prop("disabled", false)
        .html('<i class="bi bi-trash"></i>');
    },
  });
}
function addPendingCard(taskId, textPreview, cost, provider, charCount) {
  console.log("🔥 ADD PENDING CARD:", taskId);

  if ($(`#card-${taskId}`).length > 0) return;

  // ✅ TẠO DẤU THỜI GIAN
  let now = new Date();
  let d = String(now.getDate()).padStart(2, "0");
  let m = String(now.getMonth() + 1).padStart(2, "0");
  let y = now.getFullYear();
  let H = String(now.getHours()).padStart(2, "0");
  let i = String(now.getMinutes()).padStart(2, "0");
  let timeString = `${d}/${m}/${y} ${H}:${i}`;
  let startTimeMs = now.getTime(); // <-- Dấu thời gian số (Cần lưu)

  let badgeStyle =
    "background: #ffffff; color: #000000; border: 1px solid #000000; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;";
  let costBadge = cost
    ? `<span class="hc-cost" style="${badgeStyle}">${cost}</span>`
    : "";
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

  $("#historyListContainer").prepend(html);
}

// ✅ CHÚ Ý: Phải có "isLoadHistory = false" ở cuối dòng này
function addHistoryCard(
  taskId,
  textPreview,
  cost,
  time,
  provider,
  status,
  isLoadHistory = false,
  startTimeMs = Date.now(),
) {
  if ($(`#card-${taskId}`).length > 0) return;

  let badgeStyle =
    "background: #ffffff; color: #000000; border: 1px solid #000000; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; white-space: nowrap; display: inline-block; min-width: fit-content;";
  let costBadge = cost
    ? `<span class="hc-cost" style="${badgeStyle}">${cost}</span>`
    : "";
  let logoUrl = getProviderLogo(provider);

  let progressBarHtml = "";
  let iconClass = "";
  let deleteButtonHtml = "";
  let statusTextContent = ""; // Nội dung sẽ hiển thị trong status

  // 🔥 1. Gom nhóm trạng thái đang chạy để dùng chung
  let isProcessing = ["pending", "processing", "doing", "queued"].includes(
    status,
  );

  // 🔥 2. Xử lý text an toàn (Fix lỗi xuống dòng & nháy đơn)
  let safeText = (textPreview || "")
    .replace(/'/g, "\\'")
    .replace(/"/g, "&quot;")
    .replace(/(\r\n|\n|\r)/g, " ");

  // 🔥 3. Nút Metadata
  let detailButtonHtml = ``;

  // Logic hiển thị theo trạng thái
  if (isProcessing) {
    iconClass = "spinning bi-arrow-repeat";
    progressBarHtml = `<div class="hc-progress-track" id="track-${taskId}"><div class="hc-progress-fill" id="progress-${taskId}" data-progress="0"></div></div>`;

    // 🔥 [MỚI]: Nội dung đếm ngược/xử lý
    statusTextContent = `<span id="time-elapsed-${taskId}">${status === "queued" ? "Đang chờ" : "0:00"}</span>`;

    // Nút xóa hoàn tiền
    deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'refund', ${cost})" 
            id="btn-delete-${taskId}"
            style="background: transparent; border: 1px solid #333; color: #888; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa task"><i class="bi bi-trash"></i></button>`;
  } else if (status === "done") {
    iconClass = "bi-check-circle-fill";
    statusTextContent = "Hoàn thành";

    // Nút xóa lịch sử
    deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'history')" 
            class="btn-delete-history"
            style="background: transparent; border: 1px solid #666; color: #999; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa lịch sử"><i class="bi bi-trash"></i></button>`;
  } else if (status === "failed") {
    iconClass = "bi-exclamation-triangle-fill";
    statusTextContent = "Thất bại";

    // Nút xóa lịch sử
    deleteButtonHtml = `
        <button onclick="openDeleteModal('${taskId}', '${safeText}', 'history')" 
            class="btn-delete-history"
            style="background: transparent; border: 1px solid #666; color: #999; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
            title="Xóa lịch sử"><i class="bi bi-trash"></i></button>`;
  } else {
    iconClass = "bi-clock";
    statusTextContent = "Lỗi trạng thái";
  }

  // 🔥 [FIX]: Thêm class 'processing' nếu biến isProcessing = true
  let html = `
    <div class="history-card ${isProcessing ? "processing" : ""}" id="card-${taskId}" data-start-time="${startTimeMs}">
        <div class="hc-header" style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #222; padding-bottom: 8px;">
            <span class="hc-time" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #888;">
                <img src="${logoUrl}" class="hc-provider-icon" title="${provider}" style="width: 18px; height: 18px; border-radius: 50%;">
                <i class="bi ${iconClass}" id="icon-spin-${taskId}" style="font-size: 14px;"></i> 
                <span>${time || "Vừa xong"}</span>
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
    $("#historyListContainer").append(html);
  } else {
    $("#historyListContainer").prepend(html);
  }

  // 🔥 [MỚI] Nếu là tác vụ Tải lại từ Server và đang chạy, phải gọi Polling
  if (isLoadHistory && isProcessing) {
    // startPolling sẽ được gọi ở hàm loadHistory (chúng ta không cần gọi ở đây nữa)
  }
}
function deleteHistoryTask(taskId) {
  console.log("🔴 Executing deleteHistoryTask for:", taskId);

  if (!taskId) {
    console.error("❌ Error: Missing Task ID");
    return;
  }

  // Disable button (cả sidebar và modal)
  $(`#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`)
    .prop("disabled", true)
    .html('<span class="spinner-border spinner-border-sm"></span>');

  $.ajax({
    url: "../../ajaxs/tts2.php",
    method: "POST",
    data: {
      action: "delete_history",
      task_id: taskId,
    },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        showToast("✅ Đã xóa khỏi lịch sử");

        // 1. Xóa khỏi Sidebar
        $(`#card-${taskId}`).fadeOut(300, function () {
          $(this).remove();

          if ($(".history-card").length === 0) {
            $("#historyListContainer").html(`
                            <div style="text-align:center; padding:60px 20px; color:#666;">
                                <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                                <p style="font-size:14px;">Chưa có lịch sử nào</p>
                            </div>
                        `);
          }
        });

        // 2. Xóa khỏi Modal Chi tiết
        $(`#row-${taskId}`).fadeOut(300, function () {
          $(this).remove();

          if ($("#detailedHistoryList .dh-row").length === 0) {
            $("#detailedHistoryList").html(`
                            <div style="text-align:center; padding:60px 20px; color:#666;">
                                <i class="bi bi-inbox" style="font-size:48px; display:block; margin-bottom:15px; opacity:0.5;"></i>
                                <p style="font-size:14px;">Không còn lịch sử nào</p>
                            </div>
                        `);
          }

          if (typeof updateBulkActions === "function") {
            updateBulkActions();
          }
        });
      } else {
        showToast("❌ Lỗi: " + res.message);
        $(
          `#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`,
        )
          .prop("disabled", false)
          .html('<i class="bi bi-trash"></i>');
      }
    },
    error: function (xhr) {
      console.error("❌ DELETE ERROR:", xhr.responseText);

      let errorMsg = "Lỗi kết nối server";
      try {
        let errData = JSON.parse(xhr.responseText);
        errorMsg = errData.message || errorMsg;
      } catch (e) {}

      showToast("❌ " + errorMsg);

      $(
        `#card-${taskId} .btn-delete-history, .dh-delete-btn[onclick*="${taskId}"]`,
      )
        .prop("disabled", false)
        .html('<i class="bi bi-trash"></i>');
    },
  });
}
function toggleSidebarDownloadMenu(event, taskId) {
  event.stopPropagation();

  const menuId = `#sidebar-download-menu-${taskId}`;
  const $menu = $(menuId);

  // Đóng tất cả menu khác (cả sidebar và modal)
  $(".hc-download-menu, .dh-download-menu").not($menu).hide();

  // Toggle menu hiện tại
  $menu.toggle();
}

// Đóng dropdown khi click ra ngoài (Cập nhật để bao gồm sidebar)
$(document).on("click", function (e) {
  if (
    !$(e.target).closest(".hc-download-wrapper, .dh-download-wrapper").length
  ) {
    $(".hc-download-menu, .dh-download-menu").hide();
  }
});
function updateCardToDone(taskId, audioUrl, srtUrl, jsonUrl, duration) {
  // 1. Kiểm tra trùng lặp Player trong Sidebar
  if ($(`#card-${taskId} .hc-player`).length > 0) {
    return;
  }

  // 2. Cập nhật Sidebar Card
  $(`#card-${taskId}`).removeClass("processing");

  let $statusElement = $(`#card-${taskId} #status-${taskId}`);
  if ($statusElement.length) {
    $statusElement
      .text("Xong")
      .removeClass("status-pending status-queued")
      .addClass("status-done");

    $(`#time-elapsed-${taskId}`).replaceWith("Xong");
  }

  $(`#icon-spin-${taskId}`)
    .removeClass("spinning bi-arrow-repeat")
    .addClass("bi-check-circle-fill");

  let $deleteBtn = $(`#btn-delete-${taskId}`);
  if ($deleteBtn.length) {
    $deleteBtn
      .css({
        "border-color": "#666",
        color: "#999",
      })
      .attr("title", "Xóa lịch sử đã hoàn thành")
      .attr(
        "onclick",
        `openDeleteModal('${taskId}', 'Nội dung preview', 'history')`,
      )
      .prop("disabled", false);
  }

  // 🔥 3. XỬ LÝ DURATION
  let durationText = "--:--";
  if (duration && !isNaN(duration) && duration > 0) {
    durationText = formatTime(duration);
  }

  // 🔥 4. NÚT REMAKE
  let remakeBtn = `
        <button class="hc-action-btn" onclick="openRemakeModal('${taskId}')" title="Tạo lại">
            <i class="bi bi-arrow-repeat"></i>
        </button>
    `;

  // 🔥 5. DROPDOWN TẢI XUỐNG (QUA PROXY)
  let downloadDropdownHtml = `
    <div class="hc-download-wrapper" style="position: relative;">
        <button class="hc-action-btn" onclick="toggleSidebarDownloadMenu(event, '${taskId}')" title="Tải xuống">
            <i class="bi bi-download"></i>
        </button>
        
        <div class="hc-download-menu" id="sidebar-download-menu-${taskId}" style="display: none;">
            <div class="hc-download-header">Tải xuống (hết hạn sau 72 giờ)</div>
            
            <!-- 🔥 Audio -->
            ${
              audioUrl
                ? `
            <a href="javascript:void(0)" 
               onclick="downloadViaProxy('${audioUrl}', 'audio_${taskId}.mp3')" 
               class="hc-download-item">
                <i class="bi bi-music-note-beamed"></i>
                <span>Audio</span>
            </a>`
                : `
            <div class="hc-download-item hc-download-disabled">
                <i class="bi bi-music-note-beamed"></i>
                <span>Audio</span>
            </div>`
            }
            
            <!-- 🔥 SRT -->
            ${
              srtUrl
                ? `
            <a href="javascript:void(0)" 
               onclick="downloadViaProxy('${srtUrl}', 'subtitle_${taskId}.srt')" 
               class="hc-download-item">
                <i class="bi bi-file-earmark-text"></i>
                <span>Phụ đề (SRT)</span>
            </a>`
                : `
            <div class="hc-download-item hc-download-disabled">
                <i class="bi bi-file-earmark-text"></i>
                <span>Phụ đề (SRT)</span>
            </div>`
            }
        </div>
    </div>
`;

  // 🔥 6. GOM NHÓM CÁC NÚT: [Remake] [Download Dropdown] [Delete]
  let actionGroup = `
        <div style="display: flex; align-items: center; gap: 5px; margin-left: 8px;">
            ${remakeBtn}
            ${downloadDropdownHtml}
            ${$deleteBtn.length ? $deleteBtn[0].outerHTML : ""}
        </div>
    `;

  // Xóa nút delete cũ để tránh trùng lặp
  if ($deleteBtn.length) {
    $deleteBtn.remove();
  }

  // 🔥 7. XÂY DỰNG PLAYER HTML
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
                <span id="time-total-${taskId}">${durationText}</span>
            </div>
        </div>
        <div class="hc-actions">
            ${actionGroup}
        </div>
    </div>`;

  $(`#card-${taskId}`).append(playerHtml);

  $(`#track-${taskId}`).fadeOut(300, function () {
    $(this).remove();
  });

  // 🔥 8. TỰ ĐỘNG LOAD DURATION NẾU CHƯA CÓ
  if (!duration || isNaN(duration) || duration <= 0) {
    if (audioUrl) {
      console.log(
        "⏳ Duration not available, loading from audio file...",
        taskId,
      );
      setTimeout(() => {
        loadAudioDuration(taskId, audioUrl);
      }, 100);
    }
  }

  // 9. Đồng bộ sang Modal Chi tiết

  syncDetailedHistoryCard(taskId, "done", audioUrl, srtUrl, jsonUrl, duration);
}
// ========================================
// 📥 DOWNLOAD VIA PROXY (ẨN LINK AI84)
// ========================================
function downloadViaProxy(url, filename, textContent) {
  if (!url) {
    showToast("❌ Không có link tải xuống");
    return;
  }

  // 🔥 LẤY TEXT NỘI DUNG (ƯU TIÊN CAO → THẤP)
  let text = textContent || $("#txtInput").val() || "";

  // 🔥 NẾU VẪN KHÔNG CÓ TEXT → TÌM TRONG historyDataMap
  if (!text || text.trim() === "") {
    let taskId = null;

    // Trích xuất task_id từ filename (pattern: audio_TASKID.mp3)
    let match = filename.match(/audio_([a-zA-Z0-9\-]+)\./);
    if (match) {
      taskId = match[1];
    }

    // Hoặc từ URL (pattern: /TASKID/audio.mp3)
    if (!taskId) {
      let urlMatch = url.match(/audio\/([a-zA-Z0-9\-]{30,})\//);
      if (urlMatch) {
        taskId = urlMatch[1];
      }
    }

    // Lấy text từ historyDataMap
    if (
      taskId &&
      typeof historyDataMap !== "undefined" &&
      historyDataMap[taskId]
    ) {
      text =
        historyDataMap[taskId].text_input ||
        historyDataMap[taskId].text ||
        historyDataMap[taskId].content ||
        "";

      console.log(
        "✅ Found text from historyDataMap:",
        taskId,
        text.substring(0, 50),
      );
    }
  }

  // Truncate text xuống 200 ký tự để tránh URL quá dài
  if (text && text.length > 200) {
    text = text.substring(0, 200);
  }

  // Debug log
  console.log("🔽 Download Debug:", {
    url: url.substring(0, 80),
    filename: filename,
    textLength: text.length,
    textPreview: text.substring(0, 30) + "...",
  });

  showToast("📥 Đang chuẩn bị tải xuống...", "info");

  let iframe = document.createElement("iframe");
  iframe.style.display = "none";

  // 🔥 XÁC ĐỊNH PATH (TÙY THUỘC VỊ TRÍ HIỆN TẠI)
  let proxyPath = "../../ajaxs/download_audio.php";

  let proxyUrl = `${proxyPath}?url=${encodeURIComponent(url)}&filename=${encodeURIComponent(filename)}&text=${encodeURIComponent(text)}`;

  iframe.src = proxyUrl;
  document.body.appendChild(iframe);

  setTimeout(() => {
    if (iframe.parentNode) {
      document.body.removeChild(iframe);
    }
  }, 5000);
}
function updateCardToFailed(taskId) {
  $(`#card-${taskId}`).removeClass("processing");

  $(`#card-${taskId} #status-${taskId}`)
    .text("Thất bại")
    .removeClass("status-pending")
    .addClass("status-failed");

  // 🔥 [THÊM MỚI] Đồng bộ sang Modal Chi tiết
  syncDetailedHistoryCard(taskId, "failed", null, null, null, null);
}
function playAudio(taskId, url) {
  if (currentPlayingTaskId === taskId && !mainAudio.paused) {
    mainAudio.pause();
    $(`#play-btn-${taskId}`).html('<i class="bi bi-play-fill"></i>');
    currentPlayingTaskId = null;
  } else {
    if (currentPlayingTaskId) {
      $(`#play-btn-${currentPlayingTaskId}`).html(
        '<i class="bi bi-play-fill"></i>',
      );
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
  $("#uploadDropdown").toggle();
  updateChevron();
}

function updateChevron() {
  if ($("#uploadDropdown").is(":visible")) {
    $("#uploadChevron")
      .removeClass("bi-chevron-up")
      .addClass("bi-chevron-down");
  } else {
    $("#uploadChevron")
      .removeClass("bi-chevron-down")
      .addClass("bi-chevron-up");
  }
}

$(document).on("click", function (e) {
  if (
    !$(e.target).closest("#uploadDropdownBtn").length &&
    !$(e.target).closest("#uploadDropdown").length
  ) {
    $("#uploadDropdown").hide();
    updateChevron();
  }
});

// ========== GLOBAL DROP HANDLER ==========
let dragCounter = 0;

$(document).on("dragenter", function (e) {
  e.preventDefault();
  dragCounter++;
  if (dragCounter === 1) {
    $("#globalDropOverlay").css("display", "flex").hide().fadeIn(200);
  }
});

$(document).on("dragleave", function (e) {
  dragCounter--;
  if (dragCounter === 0) {
    $("#globalDropOverlay").fadeOut(200);
  }
});

$(document).on("dragover", function (e) {
  e.preventDefault();
});

$(document).on("drop", function (e) {
  e.preventDefault();
  dragCounter = 0;
  $("#globalDropOverlay").fadeOut(200);

  let files = Array.from(e.originalEvent.dataTransfer.files);
  handleGlobalDrop(files);
});

function handleGlobalDrop(files) {
  if (!hasApiKey) {
    $("#apiKeyModal").fadeIn();
    return;
  }

  let validFiles = files.filter((f) => {
    let name = f.name.toLowerCase();
    return (
      (name.endsWith(".txt") || name.endsWith(".zip")) &&
      f.size < 5 * 1024 * 1024
    );
  });

  if (validFiles.length === 0) {
    alert("Không có file hợp lệ! Chỉ chấp nhận .txt, .zip < 5MB");
    return;
  }

  // 1 FILE -> Input Area
  if (validFiles.length === 1 && !validFiles[0].name.endsWith(".zip")) {
    let file = validFiles[0];

    // 1. Check SRT & Set Flag
    if (file.name.toLowerCase().endsWith(".srt")) {
      window.isSrtFile = true;
      $("#srtFeeInfo").show();
    } else {
      window.isSrtFile = false;
      $("#srtFeeInfo").hide();
    }

    // 🔥 [MỚI] LƯU THÔNG TIN FILE VÀO BỘ NHỚ
    localStorage.setItem("tts_filename", file.name);
    localStorage.setItem("tts_is_srt", window.isSrtFile);

    let reader = new FileReader();
    reader.onload = function (e) {
      let currentText = $("#txtInput").val();
      let newContent =
        currentText + (currentText ? "\n\n" : "") + e.target.result;

      // Đổ text vào ô input
      $("#txtInput").val(newContent);

      // 🔥 [MỚI] LƯU NỘI DUNG VÀO BỘ NHỚ LUÔN
      localStorage.setItem("tts_input_draft", newContent);

      togglePlaceholder();
      $("#fileNameDisplay").text(`📂 ${file.name}`).fadeIn();

      // 🔥🔥🔥 THÊM DÒNG NÀY 🔥🔥🔥
      updateEstimatedCost(); // ← GỌI HÀM TÍNH KÝ TỰ & COST
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
// ========== SINGLE FILE UPLOAD ==========
$("#fileInput").on("change", function (e) {
  let files = e.target.files;
  if (!files || files.length === 0) return;

  // Lọc file hợp lệ
  let validFiles = Array.from(files).filter((f) => {
    let name = f.name.toLowerCase();
    return (
      (name.endsWith(".txt") ||
        name.endsWith(".srt") ||
        name.endsWith(".zip")) &&
      f.size < 5 * 1024 * 1024
    );
  });

  if (validFiles.length === 0) {
    alert("Không có file hợp lệ! Chỉ chấp nhận .txt, .srt, .zip < 5MB");
    $(this).val("");
    return;
  }

  // 🔥 KIỂM TRA GIỌNG TRƯỚC
  if (!$("#voiceIdVal").val()) {
    // Lưu file
    pendingUploadFiles = validFiles;

    // Reset input
    $(this).val("");

    // Hiện popup
    showModernConfirm(
      "Chưa chọn giọng nói",
      "Vui lòng chọn giọng nói trước khi tải file lên.",
      function () {
        openVoiceModal();
      },
      {
        type: "warning",
        confirmText: "Chọn giọng",
        cancelText: "Hủy",
      },
    );
    return;
  }

  // ✅ ĐÃ CÓ GIỌNG → XỬ LÝ FILE
  console.log("📄 File upload: Processing", validFiles.length, "files");

  // Reset input
  $(this).val("");

  // Gọi hàm chung
  handleGlobalDrop(validFiles);
});

$("#folderInput").on("change", function (e) {
  let files = e.target.files;
  if (!files || files.length === 0) return;

  // Lọc file hợp lệ
  let validFiles = Array.from(files).filter((f) => {
    let name = f.name.toLowerCase();
    return (
      (name.endsWith(".txt") ||
        name.endsWith(".srt") ||
        name.endsWith(".zip")) &&
      f.size < 5 * 1024 * 1024
    );
  });

  if (validFiles.length === 0) {
    alert("Không có file hợp lệ! Chỉ chấp nhận .txt, .srt, .zip < 5MB");
    $(this).val("");
    return;
  }

  // 🔥 KIỂM TRA GIỌNG TRƯỚC
  if (!$("#voiceIdVal").val()) {
    // Lưu file
    pendingUploadFiles = validFiles;

    // Reset input
    $(this).val("");

    // Hiện popup
    showModernConfirm(
      "Chưa chọn giọng nói",
      "Vui lòng chọn giọng nói trước khi tải folder lên.",
      function () {
        openVoiceModal();
      },
      {
        type: "warning",
        confirmText: "Chọn giọng",
        cancelText: "Hủy",
      },
    );
    return;
  }

  // ✅ ĐÃ CÓ GIỌNG → XỬ LÝ FOLDER
  console.log("📁 Folder upload: Processing", validFiles.length, "files");

  // Reset input
  $(this).val("");

  // Gọi hàm chung
  handleGlobalDrop(validFiles);
});
// ========================================
// 🎨 MODERN CONFIRM POPUP
// ========================================
function showModernConfirm(title, message, onConfirm, options = {}) {
  const type = options.type || "info";
  const confirmText = options.confirmText || "OK";
  const cancelText = options.cancelText || "Hủy";

  const iconMap = {
    warning: "bi-exclamation-triangle-fill",
    error: "bi-x-circle-fill",
    info: "bi-info-circle-fill",
  };

  const colorMap = {
    warning: "#fbbf24",
    error: "#ef4444",
    info: "#3b82f6",
  };

  const icon = iconMap[type] || iconMap.info;
  const color = colorMap[type] || colorMap.info;

  // Xóa popup cũ nếu có
  $("#modernConfirmPopup").remove();

  const html = `
    <div id="modernConfirmPopup" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        animation: fadeIn 0.2s forwards;
    ">
        <div style="
            background: #111;
            border: 1px solid #333;
            border-radius: 20px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            text-align: center;
            transform: scale(0.95);
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.05s forwards;
        ">
            <div style="
                width: 50px;
                height: 50px;
                margin: 0 auto 20px;
                background: #222;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <i class="bi ${icon}" style="font-size: 22px; color: ${color};"></i>
            </div>
            
            <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #fff;">${title}</h3>
            <p style="color: #999; font-size: 14px; margin-bottom: 24px; line-height: 1.5;">${message}</p>
            
            <div style="display: flex; gap: 10px;">
                <button id="btnConfirmCancel" style="
                    flex: 1;
                    padding: 12px;
                    border-radius: 10px;
                    border: 1px solid #333;
                    background: transparent;
                    color: #ccc;
                    font-weight: 500;
                    cursor: pointer;
                ">${cancelText}</button>
                
                <button id="btnConfirmOk" style="
                    flex: 1;
                    padding: 12px;
                    border-radius: 10px;
                    border: none;
                    background: ${color};
                    color: #000;
                    font-weight: 700;
                    cursor: pointer;
                ">${confirmText}</button>
            </div>
        </div>
        
        <style>
            @keyframes fadeIn { to { opacity: 1; } }
            @keyframes slideUp { to { opacity: 1; transform: scale(1); } }
        </style>
    </div>`;

  $("body").append(html);

  // Event handlers
  $("#btnConfirmCancel").on("click", function () {
    $("#modernConfirmPopup").fadeOut(200, function () {
      $(this).remove();
    });
  });

  $("#btnConfirmOk").on("click", function () {
    $("#modernConfirmPopup").fadeOut(200, function () {
      $(this).remove();
    });
    if (typeof onConfirm === "function") {
      onConfirm();
    }
  });
}
// ========================================
// ⚡ BULK UPLOAD HOÀN CHỈNH (Ported from Server 3)
// ========================================
let bulkFiles = [];

function openBulkModal() {
    if (!hasApiKey) {
        $("#apiKeyModal").fadeIn();
        return;
    }
    if (!$("#voiceIdVal").val()) {
        alert("⚠️ Vui lòng chọn giọng nói trước!");
        openVoiceModal();
        return;
    }

    $("#bulkUploadModal").css("display", "flex").hide().fadeIn(200);
    
    // Reset giao diện
    bulkFiles = [];
    $("#bulkFileList").hide();
    $("#bulkSummary").hide();
    $("#btnBulkProcess").hide();
    $("#currentBalance").text($("#userCredits").text() + " credits");

    // 🔥 FIX: Gọi lại setup dropzone mỗi khi mở modal để đảm bảo sự kiện được gán
    setTimeout(() => {
        setupBulkDropZone();
    }, 100);
}

function closeBulkModal() {
    $("#bulkUploadModal").fadeOut();
    bulkFiles = [];
}

// ========================================
// ⚡ FIX: SETUP DROPZONE (Sử dụng Event Delegation)
// ========================================
// ========================================
// ⚡ FIX: SETUP DROPZONE (Sử dụng Event Delegation)
// ========================================
function setupBulkDropZone() {
    console.log("🔧 Setting up Bulk Dropzone (Delegated)...");
    
    // Hủy sự kiện cũ để tránh double click
    $(document).off('click', '#bulkDropZone');
    $(document).off('change', '#bulkFileInput');
    
    // 1. Xử lý Click vào vùng Dropzone -> Mở chọn file
    $(document).on('click', '#bulkDropZone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Kiểm tra giọng trước
        if (!$('#voiceIdVal').val()) {
            alert('⚠️ Vui lòng chọn giọng nói trước!');
            return;
        }
        
        // Kích hoạt input file ẩn
        $('#bulkFileInput').trigger('click');
    });

    // 2. Xử lý khi người dùng chọn file xong
    $(document).on('change', '#bulkFileInput', function(e) {
        if (this.files.length > 0) {
            handleBulkFiles(Array.from(this.files));
            $(this).val(''); // Reset input để chọn lại được file cũ nếu cần
        }
    });

    // 3. Xử lý Kéo & Thả (Drag & Drop) - Gán trực tiếp vào Element
    const dropZone = document.getElementById('bulkDropZone');
    if(dropZone) {
        // Ngăn hành vi mặc định
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Hiệu ứng hover
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                $('#bulkDropZone').css({'border-color': '#667eea', 'background': 'rgba(102, 126, 234, 0.05)'});
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                $('#bulkDropZone').css({'border-color': '#333', 'background': '#0a0a0a'});
            }, false);
        });

        // Xử lý khi thả file
        dropZone.addEventListener('drop', function(e) {
            if (!$('#voiceIdVal').val()) {
                alert('⚠️ Vui lòng chọn giọng nói trước!');
                return;
            }
            const files = Array.from(e.dataTransfer.files);
            handleBulkFiles(files);
        }, false);
    }
}

// ========== XỬ LÝ FILES ==========
async function handleBulkFiles(files) {
    console.log('📦 handleBulkFiles called with:', files.length, 'files');
    
    let validFiles = files.filter(f => {
        let name = f.name.toLowerCase();
        return (name.endsWith('.txt') || name.endsWith('.zip')) && f.size < 5 * 1024 * 1024;
    });
    
    if (validFiles.length === 0) {
        alert('Không có file hợp lệ! Chỉ chấp nhận .txt, .zip < 5MB');
        return;
    }
    
    if (bulkFiles.length + validFiles.length > 20) {
        alert('Tối đa 20 file!');
        return;
    }
    
    // Show loading UI inside dropzone
    $('#bulkDropZone').html('<div class="spinner-border" style="color: #667eea;"></div><p style="margin-top: 15px; color: #888;">Đang đọc file...</p>');
    
    // Process files
    for (let file of validFiles) {
        if (file.name.endsWith('.zip')) {
            await extractZipFile(file);
        } else {
            await readTextFile(file);
        }
    }
    
    // Restore dropzone UI
    $('#bulkDropZone').html(`
        <i class="bi bi-cloud-upload" style="font-size: 48px; color: #667eea; display: block; margin-bottom: 16px;"></i>
        <h4 style="margin-bottom: 8px;">Kéo thả file hoặc click để chọn</h4>
        <p style="color: #888; font-size: 13px;">Hỗ trợ: .txt, .zip (tối đa 20 file, mỗi file < 5MB)</p>
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
                    if (!zipEntry.dir && (relativePath.endsWith('.txt'))) {
                        filePromises.push(
                            zipEntry.async('string').then(content => {
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
            let content = e.target.result;
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
        let fromBadge = file.from !== 'upload' 
            ? `<span style="font-size:10px; color:#888; margin-left:6px; background:#222; padding:2px 6px; border-radius:4px;">zip: ${file.from}</span>` 
            : '';
        
        html += `
        <div class="bulk-file-item">
            <div style="flex:1; overflow:hidden;">
                <div style="font-weight:600; color:#fff; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <i class="bi bi-file-earmark-text" style="color:#667eea; margin-right:6px;"></i>
                    ${file.name}${fromBadge}
                </div>
                <div style="font-size:11px; color:#888; margin-top:2px;">
                    ${file.chars.toLocaleString()} ký tự
                </div>
            </div>
            <button class="bulk-btn-remove" onclick="removeFile(${index})">
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

// ========== TÍNH TOÁN CHI PHÍ BULK (QUAN TRỌNG) ==========
function calculateBulkCost() {
    console.log('💰 calculateBulkCost() called for TTS2');
    
    if (bulkFiles.length === 0) {
        $('#bulkSummary').hide();
        $('#btnBulkProcess').hide();
        return;
    }

    // 1. TỔNG KÝ TỰ
    let totalChars = bulkFiles.reduce((sum, f) => sum + f.chars, 0);

    // 2. HỆ SỐ MODEL & VOICE & SRT
    let cost_factor = 1.0;
    let voice_multiplier = 1.0;
    let srt_multiplier = 1.0;
    let with_transcript = false;

    if (currentProvider === 'minimax') {
        // Model HD Check
        let isHDModel = (selectedMinimaxModel === 'speech-2.6-hd' || selectedMinimaxModel === 'speech-02-hd');
        if (isHDModel) cost_factor = 1.15;
        
        // Voice Clone Check
        let isClone = false;
        let voiceId = $('#voiceIdVal').val();
        if (typeof currentVoiceTab !== 'undefined' && currentVoiceTab === 'cloned') isClone = true;
        
        // Check data source
        if (!isClone && voiceId && typeof loadedVoices !== 'undefined' && loadedVoices.minimax) {
            let voiceObj = loadedVoices.minimax.find(v => v.id == voiceId);
            if (voiceObj && (voiceObj.source === 'cloned' || (voiceObj.tags && voiceObj.tags.includes('Clone')))) {
                isClone = true;
            }
        }
        if (isClone) voice_multiplier = 1.3;
        
        // SRT Check
        with_transcript = $('#minimaxSubtitleCheck').is(':checked');
    } else {
        // ElevenLabs Check
        let currentModelName = $('#selectedModelName').text();
        if (currentModelName.includes('v3') || currentModelName.includes('V3')) {
            cost_factor = 1.3;
        }
        with_transcript = $('#subtitleCheck').is(':checked');
    }

    if (with_transcript) srt_multiplier = 1.15;

    // 4. CÔNG THỨC TÍNH (TTS2 Logic)
    let base_cost = totalChars * cost_factor * voice_multiplier * srt_multiplier;
    let total_cost = Math.round(base_cost);
    total_cost = Math.max(bulkFiles.length, total_cost);

    // 5. CẬP NHẬT UI
    $('#totalChars').text(totalChars.toLocaleString());
    $('#baseCost').text(Math.round(base_cost).toLocaleString() + ' credits');
    $('#bulkEstimatedCost').text(total_cost.toLocaleString() + ' credits');

    let currentCredits = parseInt($('#userCredits').text().replace(/,/g, '') || '0');
    
    if (currentCredits < total_cost) {
        $('#btnBulkProcess').prop('disabled', true).css('opacity', '0.5');
        $('#bulkEstimatedCost').css('color', '#ef4444');
    } else {
        $('#btnBulkProcess').prop('disabled', false).css('opacity', '1');
        $('#bulkEstimatedCost').css('color', '#fbbf24');
    }

    $('#bulkSummary').show();
    $('#btnBulkProcess').show();

    // Show model info
    if (currentProvider === 'elevenlabs') {
        $('#summaryModel').text($('#selectedModelName').text());
    } else {
        $('#summaryModel').text(selectedMinimaxModel);
    }
    
    // Show subtitle status
    if (with_transcript) {
        $('#summaryTranscript').show();
    } else {
        $('#summaryTranscript').hide();
    }

    // Auto scroll
    setTimeout(() => {
        let scrollArea = document.getElementById('bulkModalScrollArea');
        if (scrollArea) {
            scrollArea.scrollTo({ top: scrollArea.scrollHeight, behavior: 'smooth' });
        }
    }, 100);
}

function processBulkFiles() {
    console.log("🖱️ processBulkFiles clicked - Opening custom popup");

    // 1. Validate - Phải có file
    if (!bulkFiles || bulkFiles.length === 0) {
        showModernAlert('Lỗi', '⚠️ Chưa có file nào để xử lý', 'warning');
        return;
    }
    
    // 2. Lấy thông tin chi phí từ giao diện Bulk Modal
    let totalCharsText = $('#totalChars').text().trim();
    let totalChars = parseInt(totalCharsText.replace(/[^0-9]/g, '')) || 0;
    
    let estimatedCostText = $('#bulkEstimatedCost').text();
    let estimatedCost = parseInt(estimatedCostText.replace(/[^0-9]/g, '')) || 0;
    
    let currentCreditsText = $('#userCredits').text();
    let currentCredits = parseInt(currentCreditsText.replace(/[^0-9]/g, '')) || 0;
    
    let balanceAfter = currentCredits - estimatedCost;
    
    console.log("📊 Popup Data:", { 
        totalChars, 
        estimatedCost, 
        currentCredits, 
        balanceAfter,
        filesCount: bulkFiles.length 
    });

    // 3. Điền thông tin vào Popup Xác nhận
    $('#bcFileCount').text(bulkFiles.length);
    $('#bcCharCount').text(totalChars.toLocaleString());
    $('#bcCost').text(estimatedCost.toLocaleString() + ' credits');
    $('#bcBalanceAfter').text(balanceAfter.toLocaleString() + ' credits');
    
    // 4. Kiểm tra số dư
    let $warning = $('#bcWarning');
    let $confirmBtn = $('#btnBulkConfirm');
    let $balanceDisplay = $('#bcBalanceAfter');
    
    if (balanceAfter < 0) {
        // ❌ Không đủ tiền
        $balanceDisplay.removeClass('balance').addClass('danger');
        $warning.show();
        $('#bcWarningText').text(`Bạn thiếu ${Math.abs(balanceAfter).toLocaleString()} credits. Vui lòng nạp thêm.`);
        $confirmBtn.prop('disabled', true).css({
            'opacity': '0.5',
            'cursor': 'not-allowed'
        });
    } else {
        // ✅ Đủ tiền
        $balanceDisplay.removeClass('danger').addClass('balance');
        $warning.hide();
        $confirmBtn.prop('disabled', false).css({
            'opacity': '1',
            'cursor': 'pointer'
        });
    }
    
    // 5. 🔥 HIỆN POPUP (QUAN TRỌNG NHẤT)
    $('#bulkConfirmPopup').addClass('show');
    console.log("✅ Custom popup opened successfully");
}

// ========================================
// 🔥 FIX 2: XỬ LÝ KHI BẤM "XÁC NHẬN XỬ LÝ" TRONG POPUP
// ========================================
async function confirmBulkProcess() {
    console.log("🚀 Starting bulk process (after user confirmed)...");
    
    // 🔥 XÓA ĐOẠN confirm() CŨ - KHÔNG CẦN NỮA
    
    // 1. Khóa nút để tránh click lại
    let $btn = $('#btnBulkConfirm');
    let $btnText = $btn.find('span');
    let originalText = $btnText.text();
    
    $btn.addClass('loading').prop('disabled', true);
    
    let successCount = 0;
    let failCount = 0;
    
    // 2. Duyệt qua từng file
    for (let i = 0; i < bulkFiles.length; i++) {
        let file = bulkFiles[i];
        
        // Hiển thị tiến độ
        let progress = Math.round(((i + 1) / bulkFiles.length) * 100);
        $btnText.text(`${i + 1}/${bulkFiles.length} (${progress}%)`);
        
        // Check xem có export subtitle không
        let isSubtitleChecked = (currentProvider === 'minimax') 
            ? $('#minimaxSubtitleCheck').is(':checked') 
            : $('#subtitleCheck').is(':checked');

        // Build request params
        let params = {
            action: 'create_speech',
            provider: currentProvider,
            text: file.content,
            voice_id: $('#voiceIdVal').val(),
            voice_name: $('#selectedVoiceName').text() + ` [${file.name}]`,
            with_transcript: isSubtitleChecked ? 1 : 0
        };
        
        // Thêm settings theo provider
        if (currentProvider === 'minimax') {
            params.model_id = selectedMinimaxModel || 'speech-01-240228';
            params.vol = $('#vol').val();
            params.speed = $('#speed').val();
            params.pitch = $('#pitch').val();
            params.language_boost = selectedLanguage || 'Auto';
        } else {
            // ElevenLabs
            let currentModelName = $('#selectedModelName').text();
            let model = loadedModels && loadedModels.elevenlabs 
                ? loadedModels.elevenlabs.find(m => currentModelName.includes(m.name))
                : null;
            
            params.model_id = model ? model.id : 'eleven_multilingual_v2';
            params.speed = $('#elevenSpeed').val();
            params.stability = parseFloat($('#stability').val()) / 100;
            params.similarity = parseFloat($('#similarity').val()) / 100;
            params.style = parseFloat($('#style').val()) / 100;
            params.use_boost = $('#boostCheck').is(':checked') ? 1 : 0;
        }
        
        try {
            // Gửi request
            let res = await $.post('../../ajaxs/tts2.php', params).promise();
            
            console.log(`File ${i+1} response:`, res);
            
            if (res.status === 'success' || res.success === true) {
                successCount++;
                
                // Thêm vào history nếu có task_id
                if (res.task_id) {
                    let previewText = file.content.substring(0, 100);
                    if (file.content.length > 100) previewText += '...';
                    
                    addPendingCard(
                        res.task_id, 
                        previewText, 
                        res.credit_cost || 0, 
                        currentProvider
                    );
                    
                    // Bắt đầu polling nếu có hàm này
                    if (typeof startPolling === 'function') {
                        startPolling(res.task_id);
                    }
                }
                
                // Cập nhật credits
                if (res.new_balance !== undefined) {
                    $('#userCredits').text(res.new_balance.toLocaleString());
                } else if (res.credit_cost) {
                    let currentBalance = parseInt($('#userCredits').text().replace(/[^0-9]/g, ''));
                    let newBalance = currentBalance - res.credit_cost;
                    $('#userCredits').text(newBalance.toLocaleString());
                }
            } else {
                failCount++;
                console.error(`File ${i+1} failed:`, res.message || res.error);
            }
        } catch (err) {
            failCount++;
            console.error(`File ${i+1} request error:`, err);
        }
        
        // Delay giữa các request để tránh spam
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    // 3. Hoàn tất - Đóng popup
    $btn.removeClass('loading').prop('disabled', false);
    $btnText.text(originalText);
    
    closeBulkConfirmPopup();
    
    // Đợi animation đóng xong
    setTimeout(() => {
        // Đóng Bulk Modal
        if (typeof closeBulkModal === 'function') {
            closeBulkModal();
        }
        
        // Chuyển sang tab History
        if (typeof switchTab === 'function') {
            switchTab('history');
        }
        
        // 🔥 HIỂN THỊ KẾT QUẢ
        showModernAlert(
            'Xử lý hoàn tất',
            `✅ Thành công: ${successCount} file\n❌ Thất bại: ${failCount} file`,
            successCount > 0 && failCount === 0 ? 'success' : 'warning'
        );
        
        // Refresh history sau 1s
        setTimeout(() => {
            if (typeof refreshHistory === 'function') {
                refreshHistory();
            }
        }, 1000);
    }, 250);
}

function closeBulkConfirmPopup() {
    $('#bulkConfirmPopup').removeClass('show');
    console.log("❌ Popup closed");
}


function showModernAlert(title, message, type = 'info') {
    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    const colorMap = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#fbbf24',
        info: '#3b82f6'
    };
    
    // Xóa popup cũ nếu có
    $('#modernAlertPopup').remove();
    
    const html = `
    <div id="modernAlertPopup" style="
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 999999; opacity: 0; animation: fadeIn 0.2s forwards;
    ">
        <div style="background: #111; border: 1px solid #333; border-radius: 20px;
            padding: 32px; max-width: 400px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            transform: scale(0.95); animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) 0.05s forwards;">
            
            <div style="width: 50px; height: 50px; margin: 0 auto 20px;
                background: #222; border-radius: 50%; display: flex;
                align-items: center; justify-content: center;">
                <i class="bi ${iconMap[type]}" style="font-size: 22px; color: ${colorMap[type]};"></i>
            </div>
            
            <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #fff;">${title}</h3>
            <p style="color: #999; font-size: 14px; margin-bottom: 24px; line-height: 1.5; white-space: pre-line;">${message}</p>
            
            <button onclick="$('#modernAlertPopup').fadeOut(200, function(){ $(this).remove(); })" 
                style="width: 100%; padding: 12px; border-radius: 10px; border: none;
                background: ${colorMap[type]}; color: #000; font-weight: 700; cursor: pointer;">
                Đóng
            </button>
        </div>
        <style>
            @keyframes fadeIn { to { opacity: 1; } }
            @keyframes slideUp { to { opacity: 1; transform: scale(1); } }
        </style>
    </div>`;
    
    $('body').append(html);
}

// Đóng popup khi click outside
$(document).on('click', '#bulkConfirmPopup', function(e) {
    if (e.target.id === 'bulkConfirmPopup') {
        closeBulkConfirmPopup();
    }
});

// Đóng popup khi nhấn ESC
$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#bulkConfirmPopup').hasClass('show')) {
        closeBulkConfirmPopup();
    }
});

console.log("✅ Bulk Confirm Popup Fix loaded!");
// Support folder confirm popup closing
$('#folderConfirmPopup').on('click', function(e) {
    if (e.target === this) $(this).fadeOut(200);
});

async function extractZipFile(zipFile) {
  return new Promise((resolve, reject) => {
    let reader = new FileReader();
    reader.onload = async function (e) {
      try {
        let zip = await JSZip.loadAsync(e.target.result);
        let filePromises = [];

        zip.forEach((relativePath, zipEntry) => {
          if (!zipEntry.dir && relativePath.endsWith(".txt")) {
            filePromises.push(
              zipEntry.async("string").then((content) => {
                // 🔥 KHÔNG TRIM
                bulkFiles.push({
                  name: relativePath,
                  content: content,
                  chars: content.length,
                  from: zipFile.name,
                });
              }),
            );
          }
        });

        await Promise.all(filePromises);
        resolve();
      } catch (err) {
        console.error("ZIP extract error:", err);
        alert("Lỗi khi giải nén file ZIP!");
        reject(err);
      }
    };
    reader.readAsArrayBuffer(zipFile);
  });
}
async function readTextFile(file) {
  return new Promise((resolve) => {
    let reader = new FileReader();
    reader.onload = function (e) {
      let content = e.target.result; // 🔥 KHÔNG TRIM

      bulkFiles.push({
        name: file.name,
        content: content,
        chars: content.length,
        from: "upload",
      });
      resolve();
    };
    reader.readAsText(file);
  });
}

function renderFileList() {
  if (bulkFiles.length === 0) {
    $("#bulkFileList").hide();
    return;
  }

  $("#bulkFileList").show();
  $("#fileCount").text(bulkFiles.length);

  let html = "";
  bulkFiles.forEach((file, index) => {
    let fromBadge =
      file.from !== "upload"
        ? `<span style="font-size: 10px; color: #888; margin-left: 6px;">từ ${file.from}</span>`
        : "";

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

  $("#fileListContainer").html(html);
}

function removeFile(index) {
  bulkFiles.splice(index, 1);
  renderFileList();
  calculateBulkCost();
}

function clearAllFiles() {
  if (confirm("Xóa tất cả file?")) {
    bulkFiles = [];
    renderFileList();
    $("#bulkSummary").hide();
    $("#btnBulkProcess").hide();
  }
}

function calculateBulkCost() {
  // ==========================================
  // 0. KIỂM TRA CÓ FILE KHÔNG
  // ==========================================
  if (bulkFiles.length === 0) {
    $("#bulkSummary").hide();
    $("#btnBulkProcess").hide();
    return;
  }

  // ==========================================
  // 1. TÍNH TỔNG KÝ TỰ
  // ==========================================
  let totalChars = bulkFiles.reduce((sum, f) => sum + f.chars, 0);

  // ==========================================
  // 2. TÍNH HỆ SỐ MODEL & VOICE
  // ==========================================
  let cost_factor = 1.0;
  let voice_multiplier = 1.0;

  // --- MINIMAX ---
  if (currentProvider === "minimax") {
    // 🔥 KIỂM TRA MODEL HD
    let isHDModel =
      selectedMinimaxModel === "speech-2.6-hd" ||
      selectedMinimaxModel === "speech-02-hd";

    if (isHDModel) {
      cost_factor = 1.15;
      console.log(
        "💰 [BULK] Model HD detected:",
        selectedMinimaxModel,
        "→ Factor: 1.15",
      );
    } else {
      cost_factor = 1.0;
      console.log(
        "💰 [BULK] Standard Model:",
        selectedMinimaxModel,
        "→ Factor: 1.0",
      );
    }

    // 🔥 KIỂM TRA VOICE CLONE
    let isClone = false;
    let voiceId = $("#voiceIdVal").val();

    if (
      typeof currentVoiceTab !== "undefined" &&
      currentVoiceTab === "cloned"
    ) {
      isClone = true;
      console.log("🎤 [BULK] Clone detected via Tab");
    }

    if (!isClone) {
      let voiceName = $("#selectedVoiceName").text().toLowerCase();
      if (voiceName.includes("clone") || voiceName.includes("(clone)")) {
        isClone = true;
        console.log("🎤 [BULK] Clone detected via Voice Name");
      }
    }

    if (
      !isClone &&
      voiceId &&
      typeof loadedVoices !== "undefined" &&
      loadedVoices.minimax
    ) {
      let voiceObj = loadedVoices.minimax.find((v) => v.id == voiceId);

      if (voiceObj) {
        let isClonedSource = voiceObj.source === "cloned";
        let hasCloneTag =
          voiceObj.tags &&
          Array.isArray(voiceObj.tags) &&
          voiceObj.tags.includes("Clone");

        if (isClonedSource || hasCloneTag) {
          isClone = true;
          console.log("🎤 [BULK] Clone detected via Data");
        }
      }
    }

    if (isClone) {
      voice_multiplier = 1.3;
      console.log("✅ [BULK] Voice Multiplier: 1.30Clone)");
    } else {
      voice_multiplier = 1.0;
      console.log("✅ [BULK] Voice Multiplier: 1.0 (System)");
    }
  }
  // --- ELEVENLABS ---
  else {
    let currentModelName = $("#selectedModelName").text();

    // 🔥 XÁC ĐỊNH COST FACTOR
    if (currentModelName.includes("v3") || currentModelName.includes("V3")) {
      cost_factor = 1.3; // ✅ Eleven v3: x1.3
      console.log("💰 [BULK] ElevenLabs v3 → Factor: 1.3");
    } else {
      cost_factor = 1.0; // ✅ Tất cả model khác: x1.0
      console.log("💰 [BULK] ElevenLabs Standard → Factor: 1.0");
    }

    voice_multiplier = 1.0;
  }

  // ==========================================
  // 3. 🔥 KIỂM TRA PHỤ ĐỀ (SRT) - ĐỒNG NHẤT +15%
  // ==========================================
  let srt_multiplier = 1.0;
  let with_transcript = false;

  if (currentProvider === "minimax") {
    with_transcript = $("#minimaxSubtitleCheck").is(":checked");
    if (with_transcript) {
      srt_multiplier = 1.15;
      console.log("📄 [BULK] Minimax SRT enabled → x1.15");
    }
  } else {
    with_transcript = $("#subtitleCheck").is(":checked");
    if (with_transcript) {
      srt_multiplier = 1.15; // 🔥 SỬA: x1.15 thay vì x1.2
      console.log("📄 [BULK] ElevenLabs SRT enabled → x1.15");
    }
  }

  // ==========================================
  // 4. 🔥 CÔNG THỨC MỚI (BỎ x1.12, GIỮ SRT)
  // ==========================================
  let base_cost = totalChars * cost_factor * voice_multiplier * srt_multiplier;

  // ==========================================
  // 5. 🔥 LÀM TRÒN MỚI: ≥ 0.5 → TRÒN LÊN
  // ==========================================
  let total_cost = Math.round(base_cost); // 6.5 → 7, 6.4 → 6
  total_cost = Math.max(bulkFiles.length, total_cost); // Tối thiểu = số file

  // ==========================================
  // 6. KIỂM TRA GENAI BACKUP (MIỄN PHÍ)
  // ==========================================
  let isGenAIBackup =
    typeof elevenlabsDown !== "undefined" &&
    elevenlabsDown &&
    typeof backupEligible !== "undefined" &&
    backupEligible &&
    currentProvider === "elevenlabs";
  /*
    if (isGenAIBackup) {
        total_cost = 0;
        console.log('🆓 [BULK] GenAI Backup Mode → Cost: 0');
    } */

  // ==========================================
  // 7. LOG KẾT QUẢ
  // ==========================================
  console.log("💰 [BULK] COST CALCULATION:", {
    provider: currentProvider,
    files: bulkFiles.length,
    total_chars: totalChars,
    model_factor: cost_factor,
    voice_mult: voice_multiplier,
    srt_mult: srt_multiplier,
    with_transcript: with_transcript,
    base_cost: base_cost.toFixed(2),
    final_cost: total_cost,
    backup_mode: isGenAIBackup,
  });

  // ==========================================
  // 8. CẬP NHẬT GIAO DIỆN
  // ==========================================

  $("#totalChars").text(totalChars.toLocaleString());
  $("#baseCost").text(base_cost.toFixed(0).toLocaleString() + " credits");

  if (isGenAIBackup) {
    $("#bulkEstimatedCost").html(
      '<span class="badge bg-success">Miễn phí (Backup)</span>',
    );
    $("#btnBulkProcess").prop("disabled", false).css("opacity", "1");
  } else {
    $("#bulkEstimatedCost").text(total_cost.toLocaleString() + " credits");

    let currentCredits = parseInt(
      $("#userCredits").text().replace(/,/g, "") || "0",
    );

    if (currentCredits < total_cost) {
      $("#btnBulkProcess").prop("disabled", true).css("opacity", "0.5");
      $("#bulkEstimatedCost").css("color", "#ef4444");
    } else {
      $("#btnBulkProcess").prop("disabled", false).css("opacity", "1");
      $("#bulkEstimatedCost").css("color", "#fbbf24");
    }
  }

  $("#bulkSummary").show();
  $("#btnBulkProcess").show();

  if (currentProvider === "elevenlabs") {
    let currentModelName = $("#selectedModelName").text();
    $("#summaryModel").text(currentModelName);
  } else {
    $("#summaryModel").text(selectedMinimaxModel);
  }

  // 🔥 HIỂN THỊ/ẨN PHỤ ĐỀ THEO TRẠNG THÁI
  if (with_transcript) {
    $("#summaryTranscript").show();
  } else {
    $("#summaryTranscript").hide();
  }

  setTimeout(() => {
    let scrollArea = document.getElementById("bulkModalScrollArea");
    if (scrollArea) {
      scrollArea.scrollTo({
        top: scrollArea.scrollHeight,
        behavior: "smooth",
      });
    }
  }, 100);
}
// ========================================
// 🔥 XỬ LÝ BULK UPLOAD VỚI RATE LIMIT
// ========================================
async function processBulkFiles() {
    const RATE_LIMIT = 5; // Lấy từ config nếu có
    const fileCount = bulkFiles.length;
    
    console.log("🚀 Starting bulk process with", fileCount, "files");
    
// ============================================================
// BƯỚC 1: KIỂM TRA & CẢNH BÁO RATE LIMIT
// ============================================================
if (fileCount > RATE_LIMIT) {
    const batchCount = Math.ceil(fileCount / RATE_LIMIT);
    const estimatedMinutes = Math.ceil((batchCount - 1) * 1.5);
    
    // 🔥 POPUP ĐẸP THAY CHO CONFIRM()
    const userConfirmed = await new Promise((resolve) => {
        // Xóa popup cũ nếu có
        $('#rateLimitWarningPopup').remove();
        
        const popup = $(`
        <div id="rateLimitWarningPopup" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 0;
            animation: fadeIn 0.3s forwards;
        ">
            <div style="
                background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
                border: 1px solid rgba(251, 191, 36, 0.3);
                border-radius: 24px;
                padding: 40px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(251, 191, 36, 0.1);
                text-align: center;
                transform: scale(0.9);
                animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            ">
                <!-- Icon cảnh báo -->
                <div style="
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 24px;
                    background: rgba(251, 191, 36, 0.1);
                    border: 2px solid rgba(251, 191, 36, 0.3);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                ">
                    <i class="bi bi-exclamation-triangle-fill" style="
                        font-size: 40px;
                        color: #fbbf24;
                        animation: pulse 2s ease-in-out infinite;
                    "></i>
                </div>
                
                <!-- Tiêu đề -->
                <h2 style="
                    margin: 0 0 16px 0;
                    font-size: 24px;
                    font-weight: 700;
                    color: #ffffff;
                    letter-spacing: -0.5px;
                ">Upload nhiều file</h2>
                
                <!-- Thông tin chính -->
                <div style="
                    background: rgba(251, 191, 36, 0.05);
                    border: 1px solid rgba(251, 191, 36, 0.2);
                    border-radius: 16px;
                    padding: 20px;
                    margin-bottom: 24px;
                    text-align: left;
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        margin-bottom: 12px;
                        padding-bottom: 12px;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    ">
                        <i class="bi bi-file-earmark-text" style="font-size: 20px; color: #fbbf24; margin-right: 12px;"></i>
                        <span style="color: #999; font-size: 14px;">Số file:</span>
                        <strong style="color: #fff; font-size: 18px; margin-left: auto;">${fileCount}</strong>
                    </div>
                    
                    <div style="
                        display: flex;
                        align-items: center;
                        margin-bottom: 12px;
                        padding-bottom: 12px;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    ">
                        <i class="bi bi-speedometer2" style="font-size: 20px; color: #fbbf24; margin-right: 12px;"></i>
                        <span style="color: #999; font-size: 14px;">Giới hạn:</span>
                        <strong style="color: #fff; font-size: 18px; margin-left: auto;">${RATE_LIMIT} file/phút</strong>
                    </div>
                    
                    <div style="
                        display: flex;
                        align-items: center;
                        margin-bottom: 12px;
                        padding-bottom: 12px;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    ">
                        <i class="bi bi-layers" style="font-size: 20px; color: #fbbf24; margin-right: 12px;"></i>
                        <span style="color: #999; font-size: 14px;">Số batch:</span>
                        <strong style="color: #fff; font-size: 18px; margin-left: auto;">${batchCount}</strong>
                    </div>
                    
                    <div style="
                        display: flex;
                        align-items: center;
                    ">
                        <i class="bi bi-clock-history" style="font-size: 20px; color: #fbbf24; margin-right: 12px;"></i>
                        <span style="color: #999; font-size: 14px;">Thời gian:</span>
                        <strong style="color: #10b981; font-size: 18px; margin-left: auto;">~${estimatedMinutes} phút</strong>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 12px;">
                    <button id="rlwCancelBtn" style="
                        flex: 1;
                        padding: 14px;
                        border-radius: 12px;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        background: rgba(255, 255, 255, 0.05);
                        color: #ccc;
                        font-weight: 600;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.2s;
                    ">
                        <i class="bi bi-x-lg" style="margin-right: 6px;"></i>
                        Hủy bỏ
                    </button>
                    
                    <button id="rlwConfirmBtn" style="
                        flex: 2;
                        padding: 14px;
                        border-radius: 12px;
                        border: none;
                        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                        color: #000;
                        font-weight: 700;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
                    ">
                        <i class="bi bi-check-lg" style="margin-right: 6px;"></i>
                        Tiếp tục xử lý
                    </button>
                </div>
            </div>
            
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes scaleIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9) translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) translateY(0);
                    }
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                
                #rlwCancelBtn:hover {
                    background: rgba(255, 255, 255, 0.08);
                    border-color: rgba(255, 255, 255, 0.2);
                    transform: translateY(-1px);
                }
                
                #rlwConfirmBtn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
                }
                
                #rlwCancelBtn:active,
                #rlwConfirmBtn:active {
                    transform: translateY(0);
                }
            </style>
        </div>
        `);
        
        $('body').append(popup);
        
        // Event handlers
        $('#rlwCancelBtn').on('click', function() {
            popup.fadeOut(200, function() {
                $(this).remove();
            });
            resolve(false);
        });
        
        $('#rlwConfirmBtn').on('click', function() {
            popup.fadeOut(200, function() {
                $(this).remove();
            });
            resolve(true);
        });
        
        // ESC key
        $(document).one('keydown', function(e) {
            if (e.key === 'Escape') {
                popup.fadeOut(200, function() {
                    $(this).remove();
                });
                resolve(false);
            }
        });
        
        // Click outside
        popup.on('click', function(e) {
            if (e.target === popup[0]) {
                popup.fadeOut(200, function() {
                    $(this).remove();
                });
                resolve(false);
            }
        });
    });
    
    if (!userConfirmed) {
        console.log("❌ User cancelled bulk upload");
        return;
    }
}
    
    // ============================================================
    // BƯỚC 2: CHIA FILE THÀNH CÁC BATCH
    // ============================================================
    const batches = [];
    for (let i = 0; i < bulkFiles.length; i += RATE_LIMIT) {
        batches.push(bulkFiles.slice(i, i + RATE_LIMIT));
    }
    
    console.log(`📦 Chia thành ${batches.length} batch:`, batches.map(b => b.length));
    
    // ============================================================
    // BƯỚC 3: XỬ LÝ TỪNG BATCH
    // ============================================================
    let totalSuccess = 0;
    let totalFail = 0;
    let rateLimitHit = false;
    
    for (let batchIdx = 0; batchIdx < batches.length; batchIdx++) {
        const batch = batches[batchIdx];
        const isLastBatch = (batchIdx === batches.length - 1);
        
        console.log(`\n📦 Processing BATCH ${batchIdx + 1}/${batches.length} (${batch.length} files)`);
        
        // Hiển thị progress
        showToast(`📦 Đang xử lý batch ${batchIdx + 1}/${batches.length} (${batch.length} file)...`, 'info');
        
        // Xử lý từng file trong batch
        for (let i = 0; i < batch.length; i++) {
            const file = batch[i];
            
            // Build payload (giữ nguyên logic cũ)
            const isSubtitleChecked = (currentProvider === 'minimax') 
                ? $('#minimaxSubtitleCheck').is(':checked') 
                : $('#subtitleCheck').is(':checked');
            
            const params = {
                action: 'create_speech',
                provider: currentProvider,
                text: file.content,
                voice_id: $('#voiceIdVal').val(),
                voice_name: $('#selectedVoiceName').text() + ` [${file.name}]`,
                with_transcript: isSubtitleChecked ? 1 : 0
            };
            
            // Thêm settings theo provider
            if (currentProvider === 'minimax') {
                params.model_id = selectedMinimaxModel || 'speech-01-240228';
                params.vol = $('#vol').val();
                params.speed = $('#speed').val();
                params.pitch = $('#pitch').val();
                params.language_boost = selectedLanguage || 'Auto';
            } else {
                const currentModelName = $('#selectedModelName').text();
                const model = loadedModels?.elevenlabs?.find(m => currentModelName.includes(m.name));
                
                params.model_id = model ? model.id : 'eleven_multilingual_v2';
                params.speed = $('#elevenSpeed').val();
                params.stability = parseFloat($('#stability').val()) / 100;
                params.similarity = parseFloat($('#similarity').val()) / 100;
                params.style = parseFloat($('#style').val()) / 100;
                params.use_boost = $('#boostCheck').is(':checked') ? 1 : 0;
            }
            
            try {
                console.log(`  → File ${i + 1}/${batch.length}: ${file.name.substring(0, 30)}...`);
                
                const res = await $.post('../../ajaxs/tts2.php', params).promise();
                
                if (res.status === 'success' || res.success === true) {
                    totalSuccess++;
                    console.log(`    ✅ Success`);
                    
                    // Thêm vào history (giống logic cũ)
                    if (res.task_id) {
                        const previewText = file.content.substring(0, 100);
                        addPendingCard(res.task_id, previewText, res.credit_cost || 0, currentProvider);
                        
                        if (typeof startPolling === 'function') {
                            startPolling(res.task_id);
                        }
                    }
                    
                    // Cập nhật credits
                    if (res.new_balance !== undefined) {
                        $('#userCredits').text(res.new_balance.toLocaleString());
                    } else if (res.credit_cost) {
                        const currentBalance = parseInt($('#userCredits').text().replace(/[^0-9]/g, ''));
                        const newBalance = currentBalance - res.credit_cost;
                        $('#userCredits').text(newBalance.toLocaleString());
                    }
                } else {
                    totalFail++;
                    console.error(`    ❌ Failed:`, res.message || res.error);
                }
                
            } catch (xhr) {
                // 🔥 BẮT LỖI 429 - RATE LIMIT
                if (xhr.status === 429) {
                    console.error(`    ⛔ RATE LIMIT HIT at file ${i + 1}`);
                    rateLimitHit = true;
                    
                    const remainingInBatch = batch.length - i;
                    const remainingBatches = batches.length - batchIdx - 1;
                    const totalRemaining = remainingInBatch + (remainingBatches * RATE_LIMIT);
                    
                    showModernAlert(
                        '⏸️ Bị chặn tốc độ',
                        `Đã xử lý: ${totalSuccess} file thành công\n` +
                        `Bị chặn: ${totalRemaining} file còn lại\n\n` +
                        `Nguyên nhân: Vượt giới hạn ${RATE_LIMIT} file/phút\n\n` +
                        `💡 Giải pháp: Đợi 1 phút rồi thử lại với ${totalRemaining} file còn lại.`,
                        'error'
                    );
                    
                    break; // Dừng batch hiện tại
                }
                
                totalFail++;
                console.error(`    ❌ Request error:`, xhr.status, xhr.statusText);
            }
            
            // Delay 500ms giữa các file
            await new Promise(r => setTimeout(r, 500));
        }
        
        // Nếu bị rate limit thì dừng hẳn
        if (rateLimitHit) {
            break;
        }
        
        // ============================================================
        // BƯỚC 4: ĐỢI 60S GIỮA CÁC BATCH (TRỪ BATCH CUỐI)
        // ============================================================
        if (!isLastBatch) {
            console.log(`⏳ Waiting 60s before next batch...`);
            await showCountdownPopup(60, `⏳ Đợi để xử lý batch ${batchIdx + 2}/${batches.length}`);
        }
    }
    
    // ============================================================
    // BƯỚC 5: THÔNG BÁO KẾT QUẢ
    // ============================================================
    console.log(`\n✅ BULK PROCESS COMPLETE | Success: ${totalSuccess} | Fail: ${totalFail}`);
    
    // Đóng modal
    closeBulkModal();
    
    // Chuyển sang tab History
    if (typeof switchTab === 'function') {
        switchTab('history');
    }
    
    // Refresh history sau 1s
    setTimeout(() => {
        if (typeof refreshHistory === 'function') {
            refreshHistory();
        }
    }, 1000);
    
    // Hiển thị kết quả
    const resultType = totalFail === 0 ? 'success' : (totalSuccess > 0 ? 'warning' : 'error');
    let resultMessage = `✅ Thành công: ${totalSuccess} file\n❌ Thất bại: ${totalFail} file`;
    
    if (rateLimitHit) {
        resultMessage += `\n\n⚠️ Đã dừng do vượt giới hạn tốc độ`;
    }
    
    showModernAlert('📊 Kết quả xử lý', resultMessage, resultType);
}

// ========================================
// 🔥 HÀM HỖ TRỢ: POPUP ĐẾM NGƯỢC
// ========================================
function showCountdownPopup(seconds, message = 'Đang chờ...') {
    return new Promise((resolve) => {
        let remaining = seconds;
        
        const popup = $(`
            <div id="countdownPopup" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
                display: flex; align-items: center; justify-content: center; z-index: 999999;">
                <div style="background: #111; padding: 40px; border-radius: 20px; text-align: center; border: 1px solid #333;">
                    <i class="bi bi-hourglass-split" style="font-size: 48px; color: #fbbf24; display: block; margin-bottom: 20px;"></i>
                    <h3 style="color: #fff; margin-bottom: 20px; font-size: 18px;">${message}</h3>
                    <div id="countdownTimer" style="font-size: 64px; color: #fbbf24; font-weight: bold; font-family: monospace;">${remaining}s</div>
                    <p style="color: #888; margin-top: 20px; font-size: 13px;">Hệ thống sẽ tự động tiếp tục...</p>
                    <button onclick="$('#countdownPopup').remove(); window.countdownResolve();" 
                        style="margin-top: 20px; padding: 8px 20px; background: transparent; border: 1px solid #444; 
                        color: #888; border-radius: 8px; cursor: pointer; font-size: 12px;">
                        Bỏ qua (tiếp tục ngay)
                    </button>
                </div>
            </div>
        `);
        
        $('body').append(popup);
        
        // Lưu resolve vào window để button "Bỏ qua" gọi được
        window.countdownResolve = resolve;
        
        const interval = setInterval(() => {
            remaining--;
            $('#countdownTimer').text(remaining + 's');
            
            if (remaining <= 0) {
                clearInterval(interval);
                popup.fadeOut(300, () => {
                    popup.remove();
                    delete window.countdownResolve;
                    resolve();
                });
            }
        }, 1000);
    });
}
function updateFilterIndicators() {
  $(".filter-group").each(function () {
    let select = $(this).find(".filter-select");
    let value = select.val();

    if (value && value !== "" && value !== "all") {
      $(this).addClass("has-value");
    } else {
      $(this).removeClass("has-value");
    }
  });
}

// Hàm Wrapper để reset setting dựa trên provider hiện tại
function resetCurrentSettings() {
  if (typeof currentProvider !== "undefined" && currentProvider === "minimax") {
    resetMinimaxSettings();
    // Thông báo nhẹ
    showToast("Đã đặt lại cài đặt Minimax");
  } else {
    resetElevenLabsSettings();
    showToast("Đã đặt lại cài đặt ElevenLabs");
  }
}

function getProviderLogo(provider) {
  // Chẩn hóa chữ thường để so sánh
  let p = (provider || "elevenlabs").toLowerCase();

  if (p === "minimax") {
    return "https://ai33.pro/minimax.png?v=3";
  }
  // Mặc định là ElevenLabs
  return "https://ai33.pro/11max.png?v=3";
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
  $("#voiceGrid").html(`
        <div style="grid-column: 1 / -1; text-align:center; padding:60px 20px;">
            <div class="spinner-border" style="width:30px; height:30px; color:#667eea;"></div>
            <p style="color:#888; margin-top:15px; font-size:14px;">Đang tìm kiếm ID trên server...</p>
        </div>
    `);

  // 3. Gửi Request
  $.post(
    "../../ajaxs/get_resources2.php?action=search_voice_id",
    {
      voice_id: voiceId,
    },
    function (res) {
      console.log("🔍 Server Search Result:", res);

      if (res.status === "success" && res.data) {
        let v = res.data;

        // --- A. XỬ LÝ TAGS (Để hiện viên thuốc màu xám) ---
        let tags = ["ID Lookup"]; // Tag đầu tiên

        // Lấy gender từ root hoặc labels
        let gender = v.gender || (v.labels ? v.labels.gender : "") || "Unknown";
        if (gender && gender !== "unknown") {
          // Viết hoa chữ cái đầu (male -> Male)
          tags.push(gender.charAt(0).toUpperCase() + gender.slice(1));
        }

        // Lấy accent
        let accent = v.accent || (v.labels ? v.labels.accent : "") || "";
        if (accent && accent !== "neutral") {
          tags.push(accent.charAt(0).toUpperCase() + accent.slice(1));
        }

        // --- B. TẠO OBJECT VOICE CHUẨN (Khớp 100% với createVoiceCardHTML) ---
        let formattedVoice = {
          // ID & Name
          id: v.voice_id || v.id,
          name: v.name || "Unknown Voice",

          // Preview & Desc
          preview_url: v.preview_url || v.sample_audio || "",
          description: v.description || "Kết quả tìm kiếm theo ID",

          // Avatar (fallback)
          avatar: v.image_url || null,
          source: "shared",

          // Tags đã xử lý ở trên
          tags: tags,

          // 🔥 CÁC CHỈ SỐ QUAN TRỌNG (Map đúng key từ API về)
          language: v.language || "en", // Cờ
          usage_1y: parseInt(v.usage_character_count_1y || v.usage_1y || 0), // Icon tia sét
          cloned: parseInt(v.cloned_by_count || v.cloned || 0), // Icon người
        };

        // 4. Reset bộ lọc UI
        $("#filterLang, #filterGender, #filterAge, #filterCategory").val("");
        $(".filter-group").removeClass("has-value");

        // 5. Render bằng hàm chuẩn (Sẽ tự gọi createVoiceCardHTML)
        renderVoiceGrid([formattedVoice]);

        // 6. Cache tạm thời vào list hiện tại (để bấm play ko lỗi)
        if (loadedVoices.elevenlabs) {
          // Kiểm tra trùng trước khi push
          if (
            !loadedVoices.elevenlabs.find(
              (item) => item.id === formattedVoice.id,
            )
          ) {
            loadedVoices.elevenlabs.push(formattedVoice);
          }
        }
      } else {
        // Trường hợp không tìm thấy
        $("#voiceGrid").html(`
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
    },
    "json",
  ).fail(function () {
    // Xử lý lỗi mạng
    $("#voiceGrid").html(`
            <div style="padding:40px; text-align:center; color:#ef4444;">
                <i class="bi bi-wifi-off" style="font-size:32px; display:block; margin-bottom:10px;"></i>
                Lỗi kết nối server! Vui lòng thử lại.
            </div>
        `);
    isSearchingServer = false;
  });
}

// ========== HIỆN MODAL CHI TIẾT (METADATA) ==========
function showMetadata(taskId) {
  const item = historyDataMap[taskId];
  if (!item) return;

  // 1. Điền text
  $("#dtText").text(item.text_input || "(Không có nội dung)");

  // 2. Điền thông tin kỹ thuật
  $("#dtTaskId").text(taskId);
  $("#dtTime").text(item.created_at || "-");
  $("#dtProvider").text(item.provider || "elevenlabs");
  $("#dtModel").text(item.model_id || "-");
  $("#dtVoice").text(item.voice_name || "Mặc định");
  $("#dtCost").text((item.credit_cost || 0) + " credits");

  // 3. Xử lý Audio Player trong Modal
  const audioUrl = item.audio_url || item.url_audio;
  if (audioUrl && item.status === "done") {
    $("#dtAudio").attr("src", audioUrl);
    $("#dtPlayerGroup").show();
  } else {
    $("#dtPlayerGroup").hide();
    $("#dtAudio").attr("src", "");
  }

  // 4. Xử lý Lỗi
  if (item.status === "failed") {
    $("#dtErrorMsg").text(item.error_message || "Lỗi không xác định");
    $("#dtErrorBox").show();
  } else {
    $("#dtErrorBox").hide();
  }

  // 5. Footer Buttons (Tải xuống)
  let footerHtml = "";
  if (item.status === "done") {
    let linkJson = item.url_json || item.json_url;
    let linkSrt = item.url_srt || item.srt_url;

    // Style nút tải cho đẹp
    const btnStyle =
      "text-decoration:none; background:#222; border:1px solid #444; padding:6px 12px; border-radius:6px; color:#fff; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px;";

    if (linkSrt)
      footerHtml += `<a href="${linkSrt}" download style="${btnStyle}"><i class="bi bi-file-text"></i> Tải SRT</a> `;
    if (linkJson)
      footerHtml += `<a href="${linkJson}" download style="${btnStyle}"><i class="bi bi-filetype-json"></i> Tải JSON</a> `;
    if (audioUrl)
      footerHtml += `<a href="${audioUrl}" download style="${btnStyle}"><i class="bi bi-download"></i> Tải Audio</a>`;
  }

  // Nút đóng
  footerHtml += `<button onclick="closeTTSDetailModal()" style="margin-left:auto; background:transparent; border:1px solid #444; color:#888; padding:6px 16px; border-radius:6px; cursor:pointer;">Đóng</button>`;

  $("#dtFooterActions").html(footerHtml);

  // 6. Hiện Modal
  $("#ttsDetailModal").css("display", "flex").hide().fadeIn(200);
}

function closeTTSDetailModal() {
  $("#ttsDetailModal").fadeOut(200);
  const audio = document.getElementById("dtAudio");
  if (audio) {
    audio.pause();
    audio.currentTime = 0;
  }
}
// ========== HÀM ĐỒNG BỘ GIỮA SIDEBAR VÀ MODAL CHI TIẾT ==========
function syncDetailedHistoryCard(
  taskId,
  status,
  audioUrl,
  srtUrl,
  jsonUrl,
  duration,
) {
  if (!$("#detailedHistoryModal").is(":visible")) {
    return;
  }

  let $row = $(`#row-${taskId}`);
  if ($row.length === 0) {
    return;
  }

  if (detailedIntervals[taskId]) {
    clearInterval(detailedIntervals[taskId]);
    delete detailedIntervals[taskId];
  }

  if (status === "done") {
    $row
      .find(".dh-badge-processing")
      .removeClass("dh-badge-processing")
      .addClass("dh-badge-done")
      .text("Xong");

    $row.find(".dh-credits-label").text("Tín dụng sử dụng");

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

    $row.find(".dh-content-area").html(playerHtml);
  } else if (status === "failed") {
    $row
      .find(".dh-badge-processing")
      .removeClass("dh-badge-processing")
      .addClass("dh-badge-error")
      .text("Lỗi");
    $row.find(".dh-credits-label").text("Đã hoàn trả");

    let errorHtml = `<div class="dh-status-text dh-text-error"><i class="bi bi-exclamation-circle"></i> Lỗi không xác định</div>`;
    $row.find(".dh-content-area").html(errorHtml);
  }
}
// ========================================
// 🔄 BIẾN LƯU TRỮ TASK CẦN REMAKE
// ========================================
let pendingRemakeTaskId = null;
