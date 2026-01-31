        </div>
        <!-- End Content Area -->

        <!-- [NEW] Compact Footer -->
        <footer class="compact-dashboard-footer">
            <div class="footer-content-compact">
                <div class="footer-left">
                    <p>&copy; <?php echo date('Y'); ?> <strong>KingCongStudio</strong>. <?php echo $lang === 'vi' ? 'Tất cả quyền được bảo lưu.' : 'All rights reserved.'; ?></p>
                </div>
                <div class="footer-right">
                    <a href="/terms" class="footer-link"><?php echo $lang === 'vi' ? 'Điều Khoản' : 'Terms'; ?></a>
                    <a href="/support" class="footer-link"><?php echo $lang === 'vi' ? 'Liên Hệ' : 'Contact'; ?></a>
                    <div class="footer-divider"></div>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/61578186428817" aria-label="Facebook" class="social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.youtube.com/channel/UCgZ_H4Jc0voj6-QsENauJPQ" aria-label="Youtube" class="social-icon"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- End Main Content -->

</div>
<!-- End Dashboard Wrapper -->

<style>
/* =====================================================
   FOOTER DARKMODE SYNC WITH TTS INTERFACE
   Đồng bộ Footer với TTS (#0a0a0a + accent trắng)
   ===================================================== */

/* [ADDED] Sticky Footer Fix */
/* This ensures the main content area fills the screen height, pushing the footer down */
.main-content {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ========== COMPACT FOOTER STYLES - TTS DESIGN ========== */
.compact-dashboard-footer {
    background-color: var(--content-bg) !important;
    border-top: 1px solid var(--border-color) !important;
    padding: 0 24px !important;
    height: 56px !important;
    margin-top: auto;
    display: flex;
    align-items: center;
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

.footer-content-compact {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

/* ========== FOOTER LEFT - COPYRIGHT ========== */
.footer-left {
    display: flex;
    align-items: center;
}

.footer-left p {
    margin: 0;
    font-size: 12px !important;
    color: var(--text-secondary) !important;
    font-weight: 400;
    line-height: 1.5;
    transition: color 0.3s ease;
}

.footer-left strong {
    color: var(--text-color) !important;
    font-weight: 600;
    transition: color 0.3s ease;
}

/* ========== FOOTER RIGHT - LINKS & SOCIALS ========== */
.footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Footer Links - Clean Minimal */
.footer-link {
    font-size: 12px !important;
    color: var(--text-secondary) !important;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
}

.footer-link:hover {
    color: var(--primary-color) !important;
    background: var(--hover-bg);
}

/* Footer Divider - Subtle */
.footer-divider {
    width: 1px;
    height: 14px !important;
    background-color: var(--border-color) !important;
    transition: background-color 0.3s ease;
}

/* ========== SOCIAL ICONS - TTS STYLE ========== */
.social-icons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.social-icon {
    width: 32px !important;
    height: 32px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary) !important;
    background: transparent;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 15px !important;
    text-decoration: none;
    transition: all 0.2s ease;
}

.social-icon:hover {
    color: var(--primary-color) !important;
    background: var(--hover-bg);
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.social-icon:active {
    transform: translateY(0);
}

/* ========== RESPONSIVE - MOBILE & TABLET ========== */
@media (max-width: 1024px) {
    .compact-dashboard-footer {
        padding: 0 20px !important;
        height: 60px !important;
    }
    
    .footer-content-compact {
        gap: 12px;
    }
    
    .footer-left p {
        font-size: 11px !important;
    }
    
    .footer-link {
        font-size: 11px !important;
        padding: 3px 6px;
    }
}

@media (max-width: 768px) {
    .compact-dashboard-footer {
        height: auto !important;
        padding: 16px 16px !important;
    }
    
    .footer-content-compact {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .footer-left {
        order: 2;
    }
    
    .footer-right {
        order: 1;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .footer-left p {
        font-size: 11px !important;
    }
    
    .footer-link {
        font-size: 11px !important;
    }
    
    .footer-divider {
        height: 12px !important;
    }
    
    .social-icon {
        width: 34px !important;
        height: 34px !important;
        font-size: 16px !important;
    }
}

@media (max-width: 480px) {
    .compact-dashboard-footer {
        padding: 14px 12px !important;
    }
    
    .footer-content-compact {
        gap: 10px;
    }
    
    .footer-left p {
        font-size: 10px !important;
    }
    
    .footer-link {
        font-size: 10px !important;
        padding: 2px 4px;
    }
    
    .social-icons {
        gap: 6px;
    }
    
    .social-icon {
        width: 30px !important;
        height: 30px !important;
        font-size: 14px !important;
    }
}

/* ========== DARK/LIGHT MODE SPECIFIC ========== */
html.dark .compact-dashboard-footer {
    background-color: var(--content-bg) !important;
    border-top-color: var(--border-color) !important;
}

html:not(.dark) .compact-dashboard-footer {
    background-color: var(--content-bg) !important;
    border-top-color: var(--border-color) !important;
}

/* ========== SMOOTH TRANSITIONS ========== */
.footer-link,
.social-icon {
    will-change: transform, color, background-color;
    backface-visibility: hidden;
}

/* ========== HOVER EFFECTS ENHANCEMENT ========== */
.footer-link:hover,
.social-icon:hover {
    transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* ========== ACCESSIBILITY ========== */
.footer-link:focus-visible,
.social-icon:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
    border-radius: 4px;
}

/* ========== PRINT STYLES ========== */
@media print {
    .compact-dashboard-footer {
        border-top: 1px solid #000 !important;
        padding: 10px 20px !important;
        height: auto !important;
    }
    
    .social-icons {
        display: none !important;
    }
    
    .footer-link {
        color: #000 !important;
    }
}

/* ========== PERFORMANCE OPTIMIZATION ========== */
.compact-dashboard-footer {
    transform: translateZ(0);
    perspective: 1000px;
}
</style>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">

<script>
    // Toastr configuration (Giữ nguyên)
    toastr.options = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: "toast-top-right",
        preventDuplicates: true,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut"
    };

    $(document).ready(function() {
        // --- THEME TOGGLER --- (Giữ nguyên)
        const themeToggle = $('#themeToggle');
        const html = $('html');
        
        const applyTheme = (theme) => {
            if (theme === 'dark') {
                html.addClass('dark');
                $('.theme-icon-light').removeClass('d-none');
                $('.theme-icon-dark').addClass('d-none');
            } else {
                html.removeClass('dark');
                $('.theme-icon-light').addClass('d-none');
                $('.theme-icon-dark').removeClass('d-none');
            }
        };
        
        let savedTheme = localStorage.getItem('theme') || 'dark';
        applyTheme(savedTheme);

        themeToggle.on('click', function() {
            let newTheme = html.hasClass('dark') ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });

        // --- SIDEBAR TOGGLE (Mobile) --- (Giữ nguyên)
        const sidebar = $('#sidebar');
        const sidebarOverlay = $('#sidebarOverlay');
        const menuToggle = $('#menuToggle');

        menuToggle.on('click', function() {
            sidebar.toggleClass('active');
            sidebarOverlay.toggleClass('active');
        });

        sidebarOverlay.on('click', function() {
            sidebar.removeClass('active');
            sidebarOverlay.removeClass('active');
        });

        $('.menu-item').on('click', function() {
            if ($(window).width() <= 1024) {
                setTimeout(() => {
                    sidebar.removeClass('active');
                    sidebarOverlay.removeClass('active');
                }, 300);
            }
        });

        // --- SEARCH FUNCTIONALITY --- (Giữ nguyên)
        let searchTimeout;
        $('.search-box input').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    console.log('Searching for:', query);
                    // Add your search logic here
                }, 500);
            }
        });

        // --- [BẮT ĐẦU CODE MỚI CHO NOTIFICATION DROPDOWN] ---
        const notiToggle = $('#notification-toggle'); // Dùng ID mới từ HTML
        const notiDropdown = $('#notification-dropdown'); // Dùng ID mới từ HTML
        const notiCountBadge = $('#notification-count');
        const markAllReadBtn = $('#mark-all-read-btn');
        const notiList = $('#notification-list-content');

        if (notiToggle.length && notiDropdown.length) { // Kiểm tra element có tồn tại không
            
            // 1. Mở/Đóng dropdown khi click vào nút chuông
            notiToggle.on('click', function(event) {
                event.stopPropagation(); // Ngăn click lan ra ngoài làm đóng dropdown ngay lập tức
                notiDropdown.toggleClass('show'); // Thêm/xóa class 'show' để CSS hiển thị/ẩn
            });

            // 2. Đóng dropdown khi click ra ngoài
            $(document).on('click', function(event) {
                // Kiểm tra xem click có nằm ngoài dropdown VÀ ngoài nút chuông không
                if (!notiDropdown.is(event.target) && notiDropdown.has(event.target).length === 0 && !notiToggle.is(event.target) && notiToggle.has(event.target).length === 0) {
                    notiDropdown.removeClass('show');
                }
            });
            
            // 3. Xử lý "Đánh dấu tất cả đã đọc"
            if (markAllReadBtn.length) {
                markAllReadBtn.on('click', function(e) {
                    e.preventDefault();
                    
                    // Mày phải tự tạo file /api/mark-all-read.php
                    $.ajax({
                        url: '/api/mark-all-read.php', // API endpoint mày cần tạo
                        method: 'POST',
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                if (notiCountBadge.length) notiCountBadge.hide(); // Ẩn số
                                if (notiList.length) {
                                    notiList.find('.notification-item.unread').removeClass('unread'); // Bỏ highlight
                                }
                                markAllReadBtn.hide(); // Ẩn nút "đánh dấu"
                            } else {
                                toastr.error(data.error || 'Có lỗi xảy ra.');
                            }
                        },
                        error: function() {
                            toastr.error('Lỗi kết nối đến server.');
                        }
                    });
                });
            }
        }
        // --- [KẾT THÚC CODE MỚI CHO NOTIFICATION DROPDOWN] ---

        // --- SMOOTH SCROLL --- (Giữ nguyên)
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100 // Điều chỉnh offset nếu cần
                }, 600);
            }
        });

        // --- TOOLTIP INITIALIZATION --- (Giữ nguyên)
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // --- AUTO HIDE ALERTS --- (Giữ nguyên)
        setTimeout(() => {
            $('.alert').fadeOut('slow');
        }, 5000);

        // --- CONFIRM LOGOUT --- (Giữ nguyên)
        $('a[href="/logout"]').on('click', function(e) {
            if (!confirm('<?php echo $lang === "vi" ? "Bạn có chắc chắn muốn đăng xuất?" : "Are you sure you want to logout?"; ?>')) {
                e.preventDefault();
            }
        });

        // --- ACTIVE MENU HIGHLIGHT --- (Giữ nguyên)
        const currentPath = window.location.pathname;
        $('.menu-item').each(function() {
            const href = $(this).attr('href');
            if (href) { // Chỉ kiểm tra nếu có href
                 // So sánh path chính xác hoặc path + / hoặc là file .php tương ứng
                if (currentPath === href || currentPath === href + '/' || currentPath.endsWith(href + '.php')) {
                     $(this).addClass('active');
                }
            }
        });
    }); // End $(document).ready

    // --- WINDOW RESIZE HANDLER --- (Giữ nguyên)
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if ($(window).width() > 1024) {
                $('#sidebar').removeClass('active');
                $('#sidebarOverlay').removeClass('active');
            }
        }, 250);
    });

    // --- PREVENT FORM RESUBMISSION --- (Giữ nguyên)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
</body>
</html>

