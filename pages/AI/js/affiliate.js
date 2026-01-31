// ========== GLOBAL VARIABLES ==========
let currentTab = 'commissions';
let currentPage = 1;

$(document).ready(function() {
    loadCommissions();
});

// ========== TAB SWITCHING ==========
function switchTab(tab) {
    currentTab = tab;
    currentPage = 1;
    
    $('.tab-btn').removeClass('active');
    $(`.tab-btn[data-tab="${tab}"]`).addClass('active');
    
    $('.tab-content').removeClass('active');
    $(`#tab${capitalize(tab)}`).addClass('active');
    
    if (tab === 'commissions') {
        loadCommissions();
    } else {
        loadWithdrawals();
    }
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ========== LOAD DATA ==========
function loadCommissions() {
    $.post('/ajaxs/affiliate.php', {
        action: 'get_history',
        type: 'commissions', // <--- THÊM DÒNG NÀY
        limit: 20,
        page: currentPage
    }, function(res) {
        if (res.status === 'success' && res.data && res.data.length > 0) {
            renderCommissions(res.data);
        } else {
            $('#commissionsList').html('<tr class="empty-row"><td colspan="3">Không có dữ liệu</td></tr>');
        }
    }, 'json').fail(function() {
        $('#commissionsList').html('<tr class="empty-row"><td colspan="3">Lỗi kết nối</td></tr>');
    });
}

function renderCommissions(data) {
    let html = '';
    data.forEach(item => {
        const date = new Date(item.created_at);
        const dateStr = `${date.getDate().toString().padStart(2,'0')}/${(date.getMonth()+1).toString().padStart(2,'0')}/${date.getFullYear()}`;
        
        // Lấy thông tin user được giới thiệu
        const refereeName = item.referee_username || 'N/A';
        
        html += `
            <tr>
                <td>${dateStr}</td>
                <td>${Number(item.original_amount).toLocaleString()} USD</td>
                <td>+${Number(item.commission_amount).toLocaleString()} USD</td>
            </tr>
        `;
    });
    $('#commissionsList').html(html);
}

function loadWithdrawals() {
    $.post('/ajaxs/affiliate.php', {
        action: 'get_history',
        type: 'withdrawals',
        limit: 20
    }, function(res) {
        if (res.status === 'success' && res.data && res.data.length > 0) {
            renderWithdrawals(res.data);
        } else {
            $('#withdrawalsList').html('<tr class="empty-row"><td colspan="3">Không có dữ liệu</td></tr>');
        }
    }, 'json');
}

function renderWithdrawals(data) {
    let html = '';
    data.forEach(item => {
        const date = new Date(item.created_at);
        const dateStr = `${date.getDate().toString().padStart(2,'0')}/${(date.getMonth()+1).toString().padStart(2,'0')}/${date.getFullYear()}`;
        
        html += `
            <tr>
                <td>${dateStr}</td>
                <td>${Number(item.amount).toLocaleString()} USD</td>
                <td>-</td>
            </tr>
        `;
    });
    $('#withdrawalsList').html(html);
}

// ========== ACTIONS ==========
function copyRefLink() {
    const input = document.getElementById('refLink');
    input.select();
    document.execCommand('copy');
    showToast('success', 'Đã sao chép link!');
}

function openPaymentInfo() {
    $('#paymentModal').addClass('show');
}

function closePaymentModal() {
    $('#paymentModal').removeClass('show');
}

function savePaymentInfo() {
    const bankName = $('#bankName').val();
    const bankAccount = $('#bankAccount').val();
    const bankOwner = $('#bankOwner').val();
    
    if (!bankName || !bankAccount || !bankOwner) {
        showToast('error', 'Vui lòng điền đầy đủ thông tin!');
        return;
    }
    
    $.post('/ajaxs/affiliate.php', {
        action: 'save_payment_info',
        bank_name: bankName,
        bank_account: bankAccount,
        bank_owner: bankOwner
    }, function(res) {
        if (res.success) {
            showToast('success', 'Đã lưu thông tin thanh toán!');
            closePaymentModal();
        } else {
            showToast('error', res.message || 'Có lỗi xảy ra!');
        }
    }, 'json');
}

function withdrawMoney() {
    const balanceText = $('#availableBalance').text();
    const balance = parseFloat(balanceText.replace(/[^0-9]/g, ''));
    
    if (balance <= 0) {
        showToast('error', 'Số dư không đủ để rút!');
        return;
    }
    
    if (!confirm(`Bạn có chắc muốn rút ${balance.toLocaleString()} USD?`)) return;
    
    $('#btnWithdraw').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');
    
    $.post('/ajaxs/affiliate.php', {
        action: 'withdraw',
        amount: 0  // 0 = rút tất cả
    }, function(res) {
        if (res.success) {
            showToast('success', `✅ Đã rút ${Number(res.amount).toLocaleString()} USD!`);
            $('#availableBalance').text('0 USD');
            
            // Reload cả 2 tab
            loadCommissions();
            loadWithdrawals();
            
            // Update credits nếu có
            if (typeof userCredits !== 'undefined') {
                $('#userCredits').text((parseFloat(userCredits) + res.amount).toLocaleString());
            }
        } else {
            showToast('error', res.message || 'Có lỗi xảy ra!');
        }
    }, 'json').fail(function() {
        showToast('error', 'Lỗi kết nối đến máy chủ!');
    }).always(function() {
        $('#btnWithdraw').prop('disabled', false).html('<i class="bi bi-wallet2"></i><span>Rút tiền</span>');
    });
}
// ========== TOAST ==========
function showToast(type, message) {
    const toast = $('#toast');
    const icon = toast.find('i');
    
    toast.removeClass('success error').addClass(type);
    
    if (type === 'success') {
        icon.removeClass().addClass('bi bi-check-circle-fill');
    } else {
        icon.removeClass().addClass('bi bi-x-circle-fill');
    }
    
    toast.find('.toast-text').text(message);
    toast.addClass('show');
    
    setTimeout(() => toast.removeClass('show'), 3500);
}