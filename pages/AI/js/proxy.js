// ==========================================
// 🚀 PROXY PURCHASE JS (FINAL)
// ==========================================

// --- Global Variables ---
let autoRefreshInterval = null;
let countdownInterval = null; // Biến quản lý đồng hồ
let isAllChecked = false;
let selectedItems = [];

// Cost calculation
const COST_PER_PROXY_5MIN = 1250;
const COST_PER_PROXY_10MIN = 2500;

$(document).ready(function() {
    // 1. Check API Key
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        showToast('error', 'Bạn chưa có API Key! Vui lòng liên hệ Admin.');
        $('#btnPurchase').prop('disabled', true);
    }

    // 2. Init UI
    loadHistory();
    startAutoRefresh();
    updateCost();

    // 3. Close dropdown (Click outside)
    $(document).on('click', function(e) {
        // Đóng dropdown chọn gói
        if (!$(e.target).closest('.custom-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('.dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
        }
        
        // Đóng menu copy nếu click ra ngoài
        if (!$(e.target).closest('.copy-wrapper').length) {
            $('.copy-dropdown').removeClass('show');
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
// ========================================
// 🔄 REFRESH HISTORY
// ========================================
function refreshHistory() {
    let $btn = $('#btnRefresh');
    
    // Disable nút & thêm animation xoay
    $btn.prop('disabled', true).addClass('spinning');
    
    // Gọi API lấy lịch sử mới
    $.ajax({
        url: '/ajaxs/proxy.php',
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
                renderHistory(res.data);
                
                // Reset trạng thái Bulk Select
                selectedItems = [];
                isAllChecked = false;
                $('#btnCheckAll').removeClass('active').find('i').removeClass('bi-check-square-fill').addClass('bi-square');
                updateBulkActions();
                
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
            // Bật lại nút & dừng animation
            setTimeout(() => {
                $btn.prop('disabled', false).removeClass('spinning');
            }, 500);
        }
    });
}
// ========== 1. DROPDOWN HANDLERS ==========
function toggleDropdown(id) {
    $('.dropdown-menu').not('#' + id + ' .dropdown-menu').removeClass('show');
    $('.dropdown-trigger i').not('#' + id + ' .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    
    let menu = $('#' + id + ' .dropdown-menu');
    let icon = $('#' + id + ' .dropdown-trigger i');
    
    menu.toggleClass('show');
    icon.toggleClass('bi-chevron-down bi-chevron-up');
}

function selectTTL(val, label, element) {
    $('#ttlLabel').text(label);
    $('#ttlVal').val(val);
    $(element).parent().find('.dropdown-item').removeClass('selected');
    $(element).addClass('selected');
    $('#dropdownTTL .dropdown-menu').removeClass('show');
    $('#dropdownTTL .dropdown-trigger i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
    updateCost();
}

// ========== 2. QUANTITY CONTROL ==========
function updateQuantity(change) {
    const input = $('#quantity');
    let val = parseInt(input.val()) || 1;
    val += change;
    
    if (val < 1) val = 1;
    if (val > 100) val = 100;
    
    input.val(val);
    updateCost();
}

function updateCost() {
    const ttl = parseInt($('#ttlVal').val());
    const qty = parseInt($('#quantity').val()) || 1;
    
    const costPerProxy = ttl === 5 ? COST_PER_PROXY_5MIN : COST_PER_PROXY_10MIN;
    const totalCost = costPerProxy * qty;
    
    $('#estimatedCost').text(totalCost.toLocaleString());
    $('#costBreakdown').text(`${costPerProxy.toLocaleString()} credits × ${qty} proxy`);
}

// ========== 3. PURCHASE PROXY ==========
function purchaseProxy() {
    if (typeof hasApiKey !== 'undefined' && !hasApiKey) {
        showToast('error', 'Bạn chưa có API Key!');
        return;
    }

    const ttl = parseInt($('#ttlVal').val());
    const quantity = parseInt($('#quantity').val()) || 1;

    if (quantity < 1 || quantity > 100) {
        showToast('error', 'Số lượng phải từ 1 đến 100!');
        return;
    }

    $('#btnPurchase').prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm"></span>
        <span>Đang xử lý...</span>
    `);

    $.ajax({
        url: '/ajaxs/proxy.php',
        type: 'POST',
        data: {
            action: 'purchase',
            ttl: ttl,
            quantity: quantity
        },
        dataType: 'json',
        timeout: 120000,
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', `Đã mua ${res.total_purchased} proxy thành công!`);
                loadHistory();
                
                if (res.remain_credits !== undefined) {
                    $('#userCredits').text(res.remain_credits.toLocaleString());
                }

                // Reset form
                $('#quantity').val(1);
                updateCost();
            } else {
                showToast('error', res.message || 'Có lỗi xảy ra!');
            }
        },
        error: function(xhr, status, error) {
            console.error('Purchase Error:', {xhr, status, error});
            showToast('error', 'Lỗi kết nối hoặc timeout!');
        },
        complete: function() {
            $('#btnPurchase').prop('disabled', false).html(`
                <i class="bi bi-cart-check-fill"></i>
                <span>Mua Proxy</span>
            `);
        }
    });
}

// ========== 4. HISTORY MANAGEMENT ==========
function loadHistory() {
    $.ajax({
        url: '/ajaxs/proxy.php',
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
                
                // Reset selection khi reload
                selectedItems = [];
                isAllChecked = false;
                $('#btnCheckAll').removeClass('active').find('i').removeClass('bi-check-square-fill').addClass('bi-square');
                updateBulkActions();
            } else {
                $('#historyList').html(`
                    <div class="history-empty">
                        <i class="bi bi-shield-lock"></i>
                        <span>Chưa có proxy nào</span>
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
                <i class="bi bi-shield-lock"></i>
                <span>Chưa có proxy nào</span>
            </div>
        `);
        return;
    }

    let html = '';
    const now = new Date();

    data.forEach(item => {
        // --- 1. XỬ LÝ HIỂN THỊ (Cắt bỏ user:pass để hiện IP:Port cho gọn) ---
        let displayProxy = item.proxy;
        if (displayProxy.includes('@')) {
            displayProxy = displayProxy.split('@')[1]; // Chỉ lấy phần sau @
        }

        // --- 2. XỬ LÝ SOCKS5 (Để copy) ---
        let socks5Str = '';
        if (item.socks5) {
            const s5 = item.socks5; 
            socks5Str = `socks5://${s5.username}:${s5.password}@${s5.host}:${s5.port}`;
        }

        // --- 3. XỬ LÝ THỜI GIAN & TRẠNG THÁI ---
        const expiresStr = item.expires_at.replace(' ', 'T'); 
        const expiresAt = new Date(expiresStr);
        const isExpired = expiresAt <= now;
        
        // Class cho CSS (active = xanh, expired = đỏ)
        const statusClass = isExpired ? 'expired' : 'active';
        
        // Nội dung hiển thị bên trong cục trạng thái
        let statusDisplay = '';
        if (isExpired) {
            statusDisplay = '<span>Hết hạn</span>';
        } else {
            // data-expire để hàm đếm ngược JS đọc
            statusDisplay = `<span class="timer-countdown" data-expire="${expiresStr}">Checking...</span>`;
        }

        // Format giờ tạo (dd/mm hh:mm)
        let timeStr = '';
        if (item.created_at) {
            const date = new Date(item.created_at);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            timeStr = `${day}/${month} ${hours}:${minutes}`;
        }

        // --- 4. RENDER HTML ---
        html += `
            <div class="proxy-item" id="proxy-${item.id}" data-proxy-id="${item.id}">
                <div class="item-checkbox" onclick="toggleItemCheck(this, ${item.id}, '${item.proxy}', '${socks5Str}', ${item.id})"></div>

                <div class="proxy-info">
                    <div class="proxy-address" title="${item.proxy}">${displayProxy}</div>
                    
                    <div class="proxy-meta">
                        <span><i class="bi bi-clock"></i> ${timeStr}</span>
                    </div>
                </div>

                <div class="proxy-status ${statusClass}" id="status-box-${item.id}">
                    <i class="bi bi-circle-fill"></i>
                    ${statusDisplay}
                </div>

                <div class="proxy-actions">
                    <button class="btn-view-proxy" onclick="viewProxyDetails(${item.id})" title="Xem chi tiết">
                        <i class="bi bi-eye"></i>
                    </button>
                    
                    <div class="copy-wrapper">
                        <button class="btn-copy-proxy" onclick="toggleCopyMenu(event, ${item.id})" title="Sao chép">
                            <i class="bi bi-clipboard"></i>
                        </button>
                        <div class="copy-dropdown" id="copy-menu-${item.id}">
                            <div class="copy-option" onclick="copySpecific('${item.proxy}', 'HTTP')">
                                <i class="bi bi-globe"></i> Copy HTTP
                            </div>
                            <div class="copy-option" onclick="copySpecific('${socks5Str}', 'SOCKS5')">
                                <i class="bi bi-hdd-network"></i> Copy SOCKS5
                            </div>
                        </div>
                    </div>
                </div>

                <div class="item-credits">
                    <div class="credits-amount">${item.credit_cost || 0}</div>
                    <div class="credits-status">credits</div>
                </div>
            </div>
        `;
    });

    $('#historyList').html(html);
    
    // 🔥 Kích hoạt đồng hồ đếm ngược ngay lập tức
    startCountdownTimer();
}

// === HÀM ĐẾM NGƯỢC (TIMER) ===
function startCountdownTimer() {
    // Xóa interval cũ để tránh trùng lặp
    if (countdownInterval) clearInterval(countdownInterval);

    const update = () => {
        const now = new Date().getTime();

        $('.timer-countdown').each(function() {
            const expireStr = $(this).data('expire');
            const expireTime = new Date(expireStr).getTime();
            const distance = expireTime - now;

            // Nếu đã hết giờ
            if (distance < 0) {
                $(this).parent().html('<span>Hết hạn</span>');
                $(this).closest('.proxy-status').removeClass('active warning').addClass('expired');
            } else {
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                const minStr = minutes < 10 ? "0" + minutes : minutes;
                const secStr = seconds < 10 ? "0" + seconds : seconds;

                $(this).text(`${minStr}:${secStr}`);

                // Nếu còn dưới 1 phút -> Chuyển màu vàng (Warning)
                if (minutes === 0) {
                    $(this).closest('.proxy-status').addClass('warning');
                }
            }
        });
    };

    update(); // Chạy ngay lần đầu
    countdownInterval = setInterval(update, 1000); // Lặp lại mỗi giây
}

// ========== 5. PROXY DETAILS MODAL ==========
function viewProxyDetails(id) {
    $.ajax({
        url: '/ajaxs/proxy.php',
        type: 'POST',
        data: {
            action: 'get_proxy_detail',
            id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data) {
                const proxy = res.data;
                
                $('#httpProxy').text(proxy.proxy);
                $('#socks5Host').text(proxy.socks5.host);
                $('#socks5Port').text(proxy.socks5.port);
                $('#socks5User').text(proxy.socks5.username);
                $('#socks5Pass').text(proxy.socks5.password);
                
                const expiresAt = new Date(proxy.expires_at);
                $('#expiresAt').text(expiresAt.toLocaleString('vi-VN'));
                $('#creditCost').text(proxy.credit_cost.toLocaleString());
                
                $('#proxyModal').addClass('show');
            } else {
                showToast('error', 'Không tìm thấy thông tin proxy!');
            }
        },
        error: function() {
            showToast('error', 'Lỗi khi tải chi tiết proxy!');
        }
    });
}

function closeProxyModal() {
    $('#proxyModal').removeClass('show');
}

function copyText(elementId) {
    const text = $('#' + elementId).text();
    navigator.clipboard.writeText(text).then(() => {
        showToast('success', 'Đã sao chép!');
    }).catch(() => {
        showToast('error', 'Không thể sao chép!');
    });
}

// ========== 6. COPY UTILS (DROPDOWN) ==========
function toggleCopyMenu(e, id) {
    e.stopPropagation(); // Ngăn click lan ra ngoài
    $('.copy-dropdown').not(`#copy-menu-${id}`).removeClass('show'); // Đóng menu khác
    const menu = $(`#copy-menu-${id}`);
    menu.toggleClass('show');
}

function copySpecific(text, type) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('success', `Đã copy ${type}!`);
        $('.copy-dropdown').removeClass('show');
    }).catch(() => {
        showToast('error', 'Không thể copy!');
    });
}

// ========== 7. BULK SELECTION ==========
function toggleCheckAll() {
    isAllChecked = !isAllChecked;
    const btn = $('#btnCheckAll');
    const icon = btn.find('i');

    if (isAllChecked) {
        $('.item-checkbox').addClass('checked');
        icon.removeClass('bi-square').addClass('bi-check-square-fill');
        btn.addClass('active');

        selectedItems = [];
        $('.proxy-item').each(function() {
            const proxyId = $(this).data('proxy-id');
            const checkbox = $(this).find('.item-checkbox');
            
            // Lấy dữ liệu từ onclick
            const onClickAttr = checkbox.attr('onclick');
            const parts = onClickAttr.split("'"); // parts[1] là http, parts[3] là socks5
            
            if (parts.length >= 4) {
                 const httpStr = parts[1];
                 const socks5Str = parts[3];
                 // Lấy historyId (số cuối cùng)
                 const historyId = parseInt(onClickAttr.split(',').pop().replace(')', ''));

                 selectedItems.push({ proxyId, httpStr, socks5Str, historyId });
            }
        });
    } else {
        $('.item-checkbox').removeClass('checked');
        icon.removeClass('bi-check-square-fill').addClass('bi-square');
        btn.removeClass('active');
        selectedItems = [];
    }

    updateBulkActions();
}

function toggleItemCheck(element, proxyId, httpStr, socks5Str, historyId) {
    $(element).toggleClass('checked');

    const itemData = { proxyId, httpStr, socks5Str, historyId };
    const index = selectedItems.findIndex(item => item.proxyId === proxyId);

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

// ========== 8. BULK ACTIONS (NEW) ==========

// Hàm Copy hàng loạt theo loại (HTTP hoặc SOCKS5)
function bulkCopyByType(type) {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 proxy!');
        return;
    }

    const textList = selectedItems.map(item => {
        return type === 'HTTP' ? item.httpStr : item.socks5Str;
    }).join('\n');

    navigator.clipboard.writeText(textList).then(() => {
        showToast('success', `Đã sao chép ${selectedItems.length} proxy (${type})!`);
    }).catch(() => {
        showToast('error', 'Không thể sao chép!');
    });
}

function bulkDelete() {
    if (selectedItems.length === 0) {
        showToast('warning', 'Vui lòng chọn ít nhất 1 proxy để xóa!');
        return;
    }

    // Hiển thị modal
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
        url: '/ajaxs/proxy.php',
        type: 'POST',
        data: {
            action: 'bulk_delete',
            history_ids: historyIds.join(',')
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', `✅ Đã xóa ${selectedItems.length} proxy!`);
                
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

// ========== 9. AUTO REFRESH & UTILS ==========
function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        loadHistory();
    }, 30000); // 30s refresh 1 lần
}

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
    if (countdownInterval) clearInterval(countdownInterval);
});