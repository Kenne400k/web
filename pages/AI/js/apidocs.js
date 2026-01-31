// ==========================================
// 🚀 API DOCS JS - COMPLETE VERSION
// Quản lý API Key & Documentation với Tab Switching
// ==========================================

// Đợi jQuery load xong
(function() {
    // Kiểm tra jQuery đã load chưa
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    // Wrap toàn bộ code trong function để đảm bảo jQuery sẵn sàng
    jQuery(document).ready(function($) {
        // Initialize tabs
        initializeTabs();
        
        // Initialize tooltips
        initializeTooltips();
        
        console.log('%c API Docs Loaded ', 'background: #667eea; color: white; padding: 5px 10px; border-radius: 3px;');
        console.log('Has API Key:', typeof hasApiKey !== 'undefined' ? hasApiKey : false);
    });

    // ========== TAB SWITCHING ==========
    function initializeTabs() {
        jQuery('.tab-btn').on('click', function() {
            // Nếu button bị disable thì không làm gì
            if (jQuery(this).prop('disabled')) {
                return;
            }
            
            const targetTab = jQuery(this).data('tab');
            
            // Update button states
            jQuery('.tab-btn').removeClass('active');
            jQuery(this).addClass('active');
            
            // Update content visibility
            jQuery('.tab-content').removeClass('active');
            jQuery('#' + targetTab).addClass('active');
            
            // Optional: Save to localStorage
            try {
                localStorage.setItem('apidocs_last_tab', targetTab);
            } catch (e) {
                // Ignore localStorage errors
            }
        });
        
        // Restore last active tab
        try {
            const lastTab = localStorage.getItem('apidocs_last_tab');
            if (lastTab && jQuery('#' + lastTab).length) {
                jQuery('.tab-btn[data-tab="' + lastTab + '"]').click();
            }
        } catch (e) {
            // Ignore localStorage errors
        }
    }

    window.switchTab = function(tabName) {
        jQuery('.tab-btn[data-tab="' + tabName + '"]').click();
    };

    // ========== API KEY VISIBILITY TOGGLE ==========
    window.toggleApiKeyVisibility = function() {
        const input = jQuery('#api_key');
        const btn = jQuery('#btnToggleKey');
        const icon = btn.find('i');
        
        if (!input.length) return;
        
        if (input.attr('type') === 'password') {
            // Show key
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            // Hide key
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    };

    // ========== COPY API KEY ==========
    window.copyApiKey = function() {
        const input = jQuery('#api_key');
        
        if (!input.length) {
            showToast('error', 'Không tìm thấy API Key input!');
            return;
        }
        
        const apiKey = input.val();
        
        if (!apiKey || apiKey === '' || apiKey.includes('You need to buy')) {
            showToast('error', 'Không có API Key để copy!');
            return;
        }
        
        console.log('Copying API Key:', apiKey); // Debug
        
        // Modern Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(apiKey).then(() => {
                updateCopyButtonFeedback('#btnCopyKey', true);
                showToast('success', 'Đã copy API Key vào clipboard!');
            }).catch((err) => {
                console.error('Clipboard API failed:', err);
                fallbackCopyText(apiKey);
            });
        } else {
            fallbackCopyText(apiKey);
        }
    };

    // Fallback copy method
    function fallbackCopyText(text) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        tempInput.style.position = 'absolute';
        tempInput.style.left = '-9999px';
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                updateCopyButtonFeedback('#btnCopyKey', true);
                showToast('success', 'Đã copy API Key vào clipboard!');
            } else {
                showToast('error', 'Không thể copy! Vui lòng copy thủ công.');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showToast('error', 'Không thể copy! Vui lòng copy thủ công.');
        }
        
        document.body.removeChild(tempInput);
    }

    // Update copy button visual feedback
    function updateCopyButtonFeedback(btnSelector, isSuccess) {
        const btn = jQuery(btnSelector);
        if (!btn.length) return;
        
        const icon = btn.find('i');
        const originalIconClass = icon.attr('class');
        
        icon.removeClass().addClass(isSuccess ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill');
        btn.css('color', isSuccess ? 'var(--success)' : 'var(--error)');
        btn.css('border-color', isSuccess ? 'var(--success)' : 'var(--error)');
        
        setTimeout(() => {
            icon.removeClass().addClass(originalIconClass);
            btn.css('color', '');
            btn.css('border-color', '');
        }, 2000);
    }

    // ========== REGENERATE API KEY ==========
    window.regenerateApiKey = function() {
        // Check if can refresh today
        if (typeof canRefreshToday !== 'undefined' && !canRefreshToday) {
            showToast('warning', 'Bạn đã refresh API Key hôm nay rồi! Vui lòng thử lại vào ngày mai.');
            return;
        }
        
        const confirmMsg = 'Bạn có chắc muốn tạo lại API Key?\n\n' +
                          '⚠️ API Key cũ sẽ KHÔNG hoạt động nữa!\n' +
                          '📅 Bạn chỉ có thể refresh 1 lần/ngày.';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        const btn = jQuery('#btnRefresh');
        if (!btn.length) return;
        
        const originalHtml = btn.html();
        
        // Show loading state
        btn.prop('disabled', true).html('<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Đang tạo...');
        
        jQuery.ajax({
            url: '/ajaxs/apidocs.php',
            method: 'POST',
            data: { action: 'regenerate_key' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', 'Đã tạo API Key mới thành công!');
                    
                    // Update new key
                    const input = jQuery('#api_key');
                    if (input.length) {
                        input.val(res.new_key);
                        input.attr('type', 'text'); // Auto show new key
                    }
                    
                    // Update toggle icon
                    const toggleIcon = jQuery('#btnToggleKey i');
                    if (toggleIcon.length) {
                        toggleIcon.removeClass('bi-eye').addClass('bi-eye-slash');
                    }
                    
                    // Update global variables
                    if (typeof window !== 'undefined') {
                        window.userApiKey = res.new_key;
                        window.canRefreshToday = false;
                    }
                    
                    // Update button state
                    btn.prop('disabled', true).html('Refresh');
                    
                    // Show warning
                    const warning = jQuery('.refresh-warning');
                    if (warning.length) {
                        warning.show();
                    } else {
                        btn.parent().after('<small class="refresh-warning">Đã dùng lượt refresh hôm nay</small>');
                    }
                    
                } else {
                    showToast('error', res.message || 'Có lỗi xảy ra khi tạo API Key!');
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                showToast('error', 'Lỗi kết nối server!');
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    };

    // ========== GENERATE FIRST API KEY ==========
    window.generateFirstApiKey = function() {
        if (!confirm('Tạo API Key đầu tiên cho tài khoản của bạn?')) {
            return;
        }
        
        const btn = jQuery('.btn-refresh');
        if (!btn.length) return;
        
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html(
            '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Đang tạo...'
        );
        
        jQuery.ajax({
            url: '/ajaxs/apidocs.php',
            method: 'POST',
            data: { action: 'generate_first_key' },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', 'Đã tạo API Key đầu tiên thành công!');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                    
                } else {
                    showToast('error', res.message || 'Có lỗi xảy ra khi tạo API Key!');
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                showToast('error', 'Lỗi kết nối server!');
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    };

    // ========== COPY CODE BLOCK ==========
    window.copyCode = function(button) {
        const codeBlock = jQuery(button).closest('.code-block');
        const codeElement = codeBlock.find('code');
        
        if (!codeElement.length) {
            showToast('error', 'Không tìm thấy code để copy!');
            return;
        }
        
        const code = codeElement.text();
        
        // Modern Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(() => {
                updateCodeCopyButton(button, true);
                showToast('success', 'Đã copy code!');
            }).catch(() => {
                fallbackCopyCode(code, button);
            });
        } else {
            fallbackCopyCode(code, button);
        }
    };

    // Fallback copy for code blocks
    function fallbackCopyCode(code, button) {
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = code;
        tempTextarea.style.position = 'absolute';
        tempTextarea.style.left = '-9999px';
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        
        try {
            document.execCommand('copy');
            updateCodeCopyButton(button, true);
            showToast('success', 'Đã copy code!');
        } catch (err) {
            showToast('error', 'Không thể copy code!');
        }
        
        document.body.removeChild(tempTextarea);
    }

    // Update code copy button feedback
    function updateCodeCopyButton(button, isSuccess) {
        const btn = jQuery(button);
        const icon = btn.find('i');
        const originalIconClass = icon.attr('class');
        
        icon.removeClass().addClass(isSuccess ? 'bi bi-check' : 'bi bi-x');
        
        setTimeout(() => {
            icon.removeClass().addClass(originalIconClass);
        }, 2000);
    }

    // ========== TOAST NOTIFICATION ==========
    function showToast(type, message) {
        let toast = jQuery('#toast');
        
        if (!toast.length) {
            toast = createToastElement();
        }
        
        const icon = toast.find('i');
        const textEl = toast.find('.toast-text');
        
        toast.removeClass('success error warning info').addClass(type);
        
        const iconClasses = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-x-circle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        
        icon.removeClass().addClass('bi ' + (iconClasses[type] || iconClasses['info']));
        textEl.text(message);
        
        setTimeout(() => {
            toast.addClass('show');
        }, 10);
        
        setTimeout(() => {
            toast.removeClass('show');
        }, 3500);
    }

    function createToastElement() {
        const toast = jQuery('<div>', {
            id: 'toast',
            class: 'toast',
            html: `
                <i class="bi bi-info-circle-fill"></i>
                <span class="toast-text"></span>
            `
        });
        
        jQuery('body').append(toast);
        return toast;
    }

    // ========== UTILITY FUNCTIONS ==========
    function initializeTooltips() {
        jQuery('.help-icon').each(function() {
            const title = jQuery(this).attr('title');
            if (title) {
                jQuery(this).on('mouseenter', function(e) {
                    const tooltip = jQuery('<div>', {
                        class: 'simple-tooltip',
                        text: title,
                        css: {
                            position: 'absolute',
                            background: '#333',
                            color: '#fff',
                            padding: '6px 10px',
                            borderRadius: '4px',
                            fontSize: '12px',
                            zIndex: 10000,
                            pointerEvents: 'none',
                            whiteSpace: 'nowrap'
                        }
                    });
                    
                    jQuery('body').append(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.css({
                        top: rect.top - tooltip.outerHeight() - 5,
                        left: rect.left + (rect.width / 2) - (tooltip.outerWidth() / 2)
                    });
                    
                    jQuery(this).data('tooltip', tooltip);
                }).on('mouseleave', function() {
                    const tooltip = jQuery(this).data('tooltip');
                    if (tooltip) {
                        tooltip.remove();
                        jQuery(this).removeData('tooltip');
                    }
                });
            }
        });
    }

    // ========== KEYBOARD SHORTCUTS ==========
    jQuery(document).on('keydown', function(e) {
        // ESC to hide API Key if shown
        if (e.key === 'Escape') {
            const input = jQuery('#api_key');
            if (input.length && input.attr('type') === 'text') {
                toggleApiKeyVisibility();
            }
        }
    });

    // ========== CSS ANIMATIONS ==========
    if (!jQuery('style#apidocs-animations').length) {
        jQuery('head').append(`
            <style id="apidocs-animations">
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                .simple-tooltip {
                    animation: fadeIn 0.2s ease;
                }
                
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-5px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
        `);
    }

})();