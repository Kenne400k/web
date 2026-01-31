<?php $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<!-- =====================================================
   SIDEBAR HOÀN CHỈNH - VỚI MINI MODE
   ===================================================== -->

<aside class="sidebar active" id="sidebar">
    
    <!-- =====================================================
       SIDEBAR HEADER - USER MENU
       ===================================================== -->
    <div class="sidebar-header">
        <?php if ($is_logged_in): ?>
        <div class="sidebar-user-menu" onclick="window.location.href='/profile'" title="<?php echo $lang === 'vi' ? 'Hồ sơ của tôi' : 'My Profile'; ?>">
            <?php if ($user['avatar']): ?>
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="sidebar-user-avatar">
            <?php else: ?>
                <div class="sidebar-user-avatar-placeholder">
                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($display_name); ?></span>
                <span class="sidebar-user-role">
                    <?php 
                    if ($user['role'] === 'admin') {
                        echo $lang === 'vi' ? 'Quản Trị Viên' : 'Administrator';
                    } else {
                        echo $lang === 'vi' ? 'Thành Viên' : 'Member';
                    }
                    ?>
                </span>
            </div>
            <i class="bi bi-chevron-right sidebar-user-arrow"></i>
        </div>
        <?php else: ?>
        <div class="sidebar-guest">
            <a href="/auth/login" class="sidebar-login-btn">
                <i class="bi bi-box-arrow-in-right"></i>
                <span><?php echo $lang === 'vi' ? 'Đăng Nhập' : 'Login'; ?></span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- =====================================================
       SIDEBAR MENU
       ===================================================== -->
    <div class="sidebar-menu">
        
        <!-- ========== SECTION 1: DANH MỤC ========== -->
        <div class="menu-section">
            <div class="menu-section-title"><?php echo $lang === 'vi' ? 'DANH MỤC' : 'CATALOG'; ?></div>

            <a href="/ai/kingcong/billing" class="menu-item <?php echo $current_page === 'AI' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Công Cụ AI' : 'AI Tools'; ?>">
                <i class="bi bi-lightning-charge-fill"></i>
                <span><?php echo $lang === 'vi' ? 'Công Cụ AI' : 'AI Tools'; ?></span>
            </a>

            <a href="/orders" class="menu-item <?php echo $current_page === 'orders' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Dịch Vụ' : 'Services'; ?>">
                <i class="bi bi-cart-check-fill"></i>
                <span><?php echo $lang === 'vi' ? 'Dịch Vụ' : 'Services'; ?></span>
            </a>

            <a href="/products" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Sản Phẩm' : 'Products'; ?>">
                <i class="bi bi-box-fill"></i>
                <span><?php echo $lang === 'vi' ? 'Sản Phẩm' : 'Products'; ?></span>
            </a>
        </div>

        <!-- ========== SECTION: CÔNG CỤ AI (Chưa đăng nhập) ========== -->
        <?php if (!$is_logged_in): ?>
        <div class="menu-section">
            <div class="menu-section-title"><?php echo $lang === 'vi' ? 'CÔNG CỤ AI' : 'AI TOOLS'; ?></div>
            <?php $current_uri = $_SERVER['REQUEST_URI']; ?>

            <!-- Server KingCong -->
            <div class="menu-item-wrapper">
                <?php $is_kingcong_g = (strpos($current_uri, '/ai/kingcong/') !== false); ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_kingcong_g ? 'active' : ''; ?>" data-title="Server KingCong">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Server KingCong' : 'KingCong Server'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="submenu <?php echo $is_kingcong_g ? 'active' : ''; ?>">
                    <a href="/ai/kingcong/billing" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/billing') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-wallet-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Mua Tín Dụng' : 'Buy Credits'; ?></span>
                    </a>
                    <a href="/ai/kingcong/text_to_speech" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/text_to_speech') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-mic-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Text to Speech' : 'Text to Speech'; ?></span>
                    </a>
                    <a href="/ai/kingcong/voice_cloning" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/voice_cloning') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-lines-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Nhân Bản Giọng Nói' : 'Voice Cloning'; ?></span>
                    </a>
                </div>
            </div>

            <!-- Server 1 (ElevenLabs) -->
            <div class="menu-item-wrapper">
                <?php $is_s1_g = ((strpos($current_uri, '/ai/tts') !== false && strpos($current_uri, '/ai/tts2') === false && strpos($current_uri, '/ai/tts3') === false) || (strpos($current_uri, '/ai/voice_cloning') !== false && strpos($current_uri, '/ai/voice_cloning2') === false && strpos($current_uri, '/ai/kingcong/') === false)); ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_s1_g ? 'active' : ''; ?>" data-title="Server 1">
                    <i class="bi bi-cpu-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Server 1 (ElevenLabs)' : 'Server 1 (ElevenLabs)'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="submenu <?php echo $is_s1_g ? 'active' : ''; ?>">
                    <a href="/ai/tts" class="submenu-item <?php echo (strpos($current_uri, '/ai/tts') !== false && strpos($current_uri, '/ai/tts2') === false && strpos($current_uri, '/ai/tts3') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-chat-quote-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Text to Speech' : 'Text to Speech'; ?></span>
                    </a>
                    <a href="/ai/voice_cloning" class="submenu-item <?php echo (strpos($current_uri, '/ai/voice_cloning') !== false && strpos($current_uri, '/ai/voice_cloning2') === false && strpos($current_uri, '/ai/kingcong/') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-video3"></i>
                        <span><?php echo $lang === 'vi' ? 'Nhân Bản Giọng Nói' : 'Voice Cloning'; ?></span>
                    </a>
                </div>
            </div>

            <!-- Công Cụ AI Khác -->
            <div class="menu-item-wrapper">
                <?php $is_other_g = (strpos($current_uri, '/ai/dubbing') !== false || strpos($current_uri, '/ai/stt') !== false || strpos($current_uri, '/ai/music') !== false); ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_other_g ? 'active' : ''; ?>" data-title="Other AI">
                    <i class="bi bi-stars"></i>
                    <span><?php echo $lang === 'vi' ? 'Công Cụ AI Khác' : 'Other AI Tools'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                <div class="submenu <?php echo $is_other_g ? 'active' : ''; ?>">
                    <a href="/ai/dubbing" class="submenu-item <?php echo (strpos($current_uri, '/ai/dubbing') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-film"></i>
                        <span><?php echo $lang === 'vi' ? 'AI Dubbing' : 'AI Dubbing'; ?></span>
                    </a>
                    <a href="/ai/stt" class="submenu-item <?php echo (strpos($current_uri, '/ai/stt') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-soundwave"></i>
                        <span><?php echo $lang === 'vi' ? 'Speech to Text' : 'Speech to Text'; ?></span>
                    </a>
                    <a href="/ai/music" class="submenu-item <?php echo (strpos($current_uri, '/ai/music') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-music-note-beamed"></i>
                        <span><?php echo $lang === 'vi' ? 'AI Music' : 'AI Music'; ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== SECTION 2: QUẢN LÝ (Logged In Only) ========== -->
        <?php if ($is_logged_in): ?>
        <div class="menu-section">
            <div class="menu-section-title"><?php echo $lang === 'vi' ? 'QUẢN LÝ' : 'MANAGEMENT'; ?></div>
            
            <a href="/dashboard" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Tổng Quan' : 'Dashboard'; ?>">
                <i class="bi bi-speedometer2"></i>
                <span><?php echo $lang === 'vi' ? 'Tổng Quan' : 'Dashboard'; ?></span>
            </a>

            <!-- ========== KINGCONG AI ========== -->
            <div class="menu-item-wrapper">
                <?php
                    $current_uri = $_SERVER['REQUEST_URI'];
                    $is_kingcong = (strpos($current_uri, '/ai/kingcong/') !== false || strpos($current_uri, '/ai/stt') !== false);
                ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_kingcong ? 'active' : ''; ?>" data-title="Kingcong AI">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Công cụ AI Server KingCong' : 'AI Tools Server KingCong'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>

                <div class="submenu <?php echo $is_kingcong ? 'active' : ''; ?>">
                    <a href="/ai/kingcong/billing" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/billing') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-wallet-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Mua Tín Dụng (Server KingCong)' : 'Buy Credits (Server KingCong)'; ?></span>
                    </a>
                    <a href="/ai/kingcong/text_to_speech" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/text_to_speech') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-mic-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Văn bản thành giọng nói (Server KingCong)' : 'Text To Speech (Server KingCong)'; ?></span>
                    </a>
                    <a href="/ai/kingcong/voice_cloning" class="submenu-item <?php echo (strpos($current_uri, '/ai/kingcong/voice_cloning') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-lines-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Nhân bản giọng nói (Server KingCong)' : 'Voice Cloning (Server KingCong)'; ?></span>
                    </a>
                    <a href="/ai/stt" class="submenu-item <?php echo (strpos($current_uri, '/ai/stt') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-soundwave"></i>
                        <span><?php echo $lang === 'vi' ? 'Giọng nói thành văn bản (Server KingCong)' : 'Speech To Text (Server KingCong)'; ?></span>
                    </a>
                </div>
            </div>

            <!-- ========== AI SERVER 1 ========== -->
            <div class="menu-item-wrapper">
                <?php 
                    $current_uri = $_SERVER['REQUEST_URI'];
                    // 🔥 LOGIC CHÍNH XÁC: CHỈ ACTIVE KHI Ở /ai/tts HOẶC /ai/voice_cloning (KHÔNG CÓ 2 Ở CUỐI)
                    $is_server1 = (
                        (strpos($current_uri, '/ai/tts') !== false && strpos($current_uri, '/ai/tts2') === false) ||
                        (strpos($current_uri, '/ai/voice_cloning') !== false && strpos($current_uri, '/ai/voice_cloning2') === false)
                    );
                ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_server1 ? 'active' : ''; ?>" data-title="AI Server 1">
                    <i class="bi bi-cpu-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Công cụ AI server 1' : 'AI Tools Server 1'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                
                <div class="submenu <?php echo $is_server1 ? 'active' : ''; ?>">
                    <a href="/ai/tts" class="submenu-item <?php echo (strpos($current_uri, '/ai/tts') !== false && strpos($current_uri, '/ai/tts2') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-chat-quote-fill"></i>
                        <span><?php echo $lang === 'vi' ? 'Văn bản thành giọng nói (Server 1)' : 'Text To Speech (Server 1)'; ?></span>
                    </a>
                    <a href="/ai/voice_cloning" class="submenu-item <?php echo (strpos($current_uri, '/ai/voice_cloning') !== false && strpos($current_uri, '/ai/voice_cloning2') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-video3"></i>
                        <span><?php echo $lang === 'vi' ? 'Nhân bản giọng nói (Server 1)' : 'Voice Cloning (Server 1)'; ?></span>
                    </a>
                </div>
            </div>
            
            <!-- ========== AI SERVER 2 ========== -->
            <div class="menu-item-wrapper">
                <?php 
                    $current_uri = $_SERVER['REQUEST_URI'];
                    // 🔥 LOGIC CHÍNH XÁC: CHỈ ACTIVE KHI Ở /ai/tts2 HOẶC /ai/voice_cloning2
                    $is_server2 = (
                        strpos($current_uri, '/ai/tts2') !== false ||
                        strpos($current_uri, '/ai/voice_cloning2') !== false
                    );
                ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_server2 ? 'active' : ''; ?>" data-title="AI Server 2">
                    <i class="bi bi-hdd-network-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Công cụ AI server 2' : 'AI Tools Server 2'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                
                <div class="submenu <?php echo $is_server2 ? 'active' : ''; ?>">
                    <a href="/ai/tts2" class="submenu-item <?php echo (strpos($current_uri, '/ai/tts2') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-broadcast"></i>
                        <span><?php echo $lang === 'vi' ? 'Văn bản thành giọng nói (Server 2)' : 'Text To Speech (Server 2)'; ?></span>
                    </a>
                    <a href="/ai/voice_cloning2" class="submenu-item <?php echo (strpos($current_uri, '/ai/voice_cloning2') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-bounding-box"></i>
                        <span><?php echo $lang === 'vi' ? 'Nhân bản giọng nói (Server 2)' : 'Voice Cloning (Server 2)'; ?></span>
                    </a>
                </div>
            </div>
            
            <!-- ========== ĐỚN HÀNG ========== -->
            <div class="menu-item-wrapper">
                <?php 
                    $current_uri = $_SERVER['REQUEST_URI'];
                    $is_orders = (
                        strpos($current_uri, '/orderstatus') !== false ||
                        strpos($current_uri, '/product_orders') !== false
                    );
                ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_orders ? 'active' : ''; ?>">
                    <i class="bi bi-cart-check-fill"></i>
                    <span><?php echo $lang === 'vi' ? 'Đơn Hàng' : 'Orders'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                
                <div class="submenu <?php echo $is_orders ? 'active' : ''; ?>">
                    <a href="/orderstatus" class="submenu-item <?php echo (strpos($current_uri, '/orderstatus') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-dot"></i>
                        <span><?php echo $lang === 'vi' ? 'Dịch Vụ' : 'Services'; ?></span>
                    </a>
                    <a href="/product_orders" class="submenu-item <?php echo (strpos($current_uri, '/product_orders') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-dot"></i>
                        <span><?php echo $lang === 'vi' ? 'Sản Phẩm' : 'Products'; ?></span>
                    </a>
                </div>
            </div>

            <!-- ========== TÀI KHOẢN ========== -->
            <div class="menu-item-wrapper">
                <?php 
                    $current_uri = $_SERVER['REQUEST_URI'];
                    $is_account = (
                        strpos($current_uri, '/profile') !== false ||
                        strpos($current_uri, '/wallet') !== false ||
                        strpos($current_uri, '/recharge') !== false ||
                        strpos($current_uri, '/international_deposit') !== false
                    );
                ?>
                <a href="javascript:void(0)" class="menu-item has-submenu <?php echo $is_account ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $lang === 'vi' ? 'Tài Khoản' : 'Account'; ?></span>
                    <i class="bi bi-chevron-down menu-arrow"></i>
                </a>
                
                <div class="submenu <?php echo $is_account ? 'active' : ''; ?>">
                    <a href="/profile" class="submenu-item <?php echo (strpos($current_uri, '/profile') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-badge"></i>
                        <span><?php echo $lang === 'vi' ? 'Hồ Sơ' : 'Profile'; ?></span>
                    </a>
                    <a href="/wallet" class="submenu-item <?php echo (strpos($current_uri, '/wallet') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-wallet2"></i>
                        <span><?php echo $lang === 'vi' ? 'Ví Tiền' : 'Wallet'; ?></span>
                    </a>
                    <a href="/recharge" class="submenu-item <?php echo (strpos($current_uri, '/recharge') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-cash-stack"></i>
                        <span><?php echo $lang === 'vi' ? 'Nạp Tiền' : 'Recharge'; ?></span>
                    </a>
                    <a href="/international_deposit" class="submenu-item <?php echo (strpos($current_uri, '/international_deposit') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-cash-stack"></i>
                        <span><?php echo $lang === 'vi' ? 'Nạp Tiền Quốc Tế' : 'International Deposit'; ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== SECTION 3: QUẢN TRỊ (Admin Only) ========== -->
        <?php if ($is_logged_in && $user['role'] === 'admin'): ?>
        <div class="menu-section">
            <div class="menu-section-title"><?php echo $lang === 'vi' ? 'QUẢN TRỊ' : 'ADMIN'; ?></div>
            
            <a href="/admin/dashboard" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/dashboard') !== false ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Bảng Điều Khiển' : 'Admin Dashboard'; ?>">
                <i class="bi bi-speedometer"></i>
                <span><?php echo $lang === 'vi' ? 'Bảng Điều Khiển' : 'Admin Dashboard'; ?></span>
            </a>
        </div>
        <?php endif; ?>

        <!-- ========== SECTION 4: HỖ TRỢ & ĐĂNG XUẤT ========== -->
        <div class="menu-section">
            <?php if ($is_logged_in): ?>
            <!-- Logged In: Support & Logout -->
            <a href="/support" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Hỗ Trợ' : 'Support'; ?>">
                <i class="bi bi-headset"></i>
                <span><?php echo $lang === 'vi' ? 'Hỗ Trợ' : 'Support'; ?></span>
            </a>
            
            <a href="/logout" class="menu-item logout-btn" data-title="<?php echo $lang === 'vi' ? 'Đăng Xuất' : 'Logout'; ?>">
                <i class="bi bi-box-arrow-right"></i>
                <span><?php echo $lang === 'vi' ? 'Đăng Xuất' : 'Logout'; ?></span>
            </a>
            
            <?php else: ?>
            <!-- Guest: Login & Register -->
            <div class="menu-section-title"><?php echo $lang === 'vi' ? 'TÀI KHOẢN' : 'ACCOUNT'; ?></div>
            
            <a href="/auth/login" class="menu-item login-btn-menu" data-title="<?php echo $lang === 'vi' ? 'Đăng Nhập' : 'Login'; ?>">
                <i class="bi bi-box-arrow-in-right"></i>
                <span><?php echo $lang === 'vi' ? 'Đăng Nhập' : 'Login'; ?></span>
            </a>
            
            <a href="/auth/register" class="menu-item" data-title="<?php echo $lang === 'vi' ? 'Đăng Ký' : 'Register'; ?>">
                <i class="bi bi-person-plus"></i>
                <span><?php echo $lang === 'vi' ? 'Đăng Ký' : 'Register'; ?></span>
            </a>
            
            <a href="/support" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>" data-title="<?php echo $lang === 'vi' ? 'Hỗ Trợ' : 'Support'; ?>">
                <i class="bi bi-question-circle"></i>
                <span><?php echo $lang === 'vi' ? 'Hỗ Trợ' : 'Support'; ?></span>
            </a>
            <?php endif; ?>
        </div>

    </div>
</aside>


<!-- =====================================================
   JAVASCRIPT - SUBMENU TOGGLE
   ===================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // 🔥 SUBMENU TOGGLE - CHỈ TOGGLE, KHÔNG ĐÓNG CÁC SUBMENU KHÁC
    // =====================================================
    document.querySelectorAll('.has-submenu').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const submenu = this.nextElementSibling;
            const arrow = this.querySelector('.menu-arrow');
            const wrapper = this.closest('.menu-item-wrapper');
            
            // 🔥 CHỈ TOGGLE SUBMENU HIỆN TẠI
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('active');
                
                // Xoay icon arrow
                if (submenu.classList.contains('active')) {
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                    if (wrapper) wrapper.classList.add('active');
                } else {
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                    if (wrapper) wrapper.classList.remove('active');
                }
            }
        });
    });
    
    // =====================================================
    // 🔥 AUTO-OPEN SUBMENU KHI CÓ ITEM ACTIVE BÊN TRONG
    // =====================================================
    document.querySelectorAll('.submenu-item.active').forEach(item => {
        const submenu = item.closest('.submenu');
        if (submenu) {
            const parentItem = submenu.previousElementSibling;
            const arrow = parentItem ? parentItem.querySelector('.menu-arrow') : null;
            const wrapper = submenu.closest('.menu-item-wrapper');
            
            submenu.classList.add('active');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
            if (wrapper) wrapper.classList.add('active');
        }
    });
    
    // =====================================================
    // 🔥 AUTO-OPEN SUBMENU KHI PARENT ACTIVE (PAGE LOAD)
    // =====================================================
    document.querySelectorAll('.submenu.active').forEach(submenu => {
        const parentItem = submenu.previousElementSibling;
        if (parentItem) {
            const arrow = parentItem.querySelector('.menu-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
            
            const wrapper = submenu.closest('.menu-item-wrapper');
            if (wrapper) wrapper.classList.add('active');
        }
    });
});
</script>
<style>
/* =====================================================
   COMPLETE SIDEBAR CSS - TTS DARKMODE SYNC
   CSS hoàn chỉnh cho toàn bộ Sidebar (menu + dropdown)
   ===================================================== */
/* =====================================================
   SUBMENU TRANSITION - SMOOTH ANIMATION
   ===================================================== */
.submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.submenu.active {
    max-height: 1000px; /* Đủ lớn để chứa tất cả submenu items */
}

/* Animation cho Arrow */
.menu-arrow {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Highlight parent khi submenu đang mở */
.menu-item-wrapper.active > .menu-item {
    color: var(--primary-color) !important;
    font-weight: 600;
}
/* ========== SIDEBAR MENU CONTAINER ========== */
.sidebar-menu {
    padding: 20px 0;
    overflow-y: auto;
    max-height: calc(100vh - 100px);
}

.sidebar-menu::-webkit-scrollbar {
    width: 5px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: var(--sidebar-bg);
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}

/* ========== MENU SECTION ========== */
.menu-section {
    margin-bottom: 24px;
}

.menu-title {
    padding: 0 20px 10px;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-secondary) !important;
    transition: color 0.3s ease;
}

/* ========== MENU ITEMS (CẤP 1) ========== */
.menu-item {
    display: flex !important;
    align-items: center !important;
    gap: 12px;
    padding: 10px 20px !important;
    color: var(--text-color) !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    position: relative;
    border-radius: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    font-size: 13px !important;
    font-weight: 500 !important;
}

.menu-item i {
    width: 20px;
    text-align: center;
    font-size: 16px !important;
    color: var(--text-secondary);
    transition: color 0.2s ease;
    flex-shrink: 0;
}

.menu-item span {
    font-size: 13px !important;
    font-weight: 500;
    flex: 1;
}

.menu-item:hover {
    background-color: var(--hover-bg) !important;
    color: var(--text-color) !important;
    padding-left: 24px !important;
}

.menu-item:hover i {
    color: var(--primary-color);
}

.menu-item.active {
    background-color: var(--input-bg) !important;
    color: var(--primary-color) !important;
    border-left: 3px solid var(--primary-color);
    padding-left: 17px !important; /* 20px - 3px */
    font-weight: 600 !important;
}

.menu-item.active i {
    color: var(--primary-color) !important;
}

.menu-item.active:hover {
    padding-left: 21px !important; /* 24px - 3px */
}

/* ========== DROPDOWN CẤP 1 ========== */
.menu-item-dropdown {
    position: relative;
}

.menu-item-dropdown > .menu-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    cursor: pointer;
}

.dropdown-arrow {
    font-size: 0.7rem !important;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    margin-left: auto;
    color: var(--text-secondary) !important;
    flex-shrink: 0;
}

.menu-item-dropdown.active > .menu-item .dropdown-arrow,
.menu-item-dropdown.active > .submenu-item .dropdown-arrow {
    transform: rotate(180deg);
    color: var(--primary-color) !important;
}

.submenu {
    max-height: 0;
    overflow: hidden;
    padding-left: 0;
    background: transparent;
}

.menu-item-dropdown.active > .submenu {
    max-height: 1000px;
}

/* Transition chỉ khi user click */
.menu-item-dropdown.user-interacted > .submenu {
    transition: max-height 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* ========== SUBMENU ITEMS - TTS STYLE ========== */
.submenu-item {
    display: flex !important;
    align-items: center !important;
    padding: 9px 20px 9px 44px !important; /* 20px left + 24px indent */
    color: var(--text-secondary) !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    border-radius: 0 !important;
    margin: 0 !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    background: transparent !important;
    gap: 10px;
}

.submenu-item i {
    font-size: 14px !important;
    width: 18px;
    text-align: center;
    flex-shrink: 0;
    color: var(--text-secondary);
    transition: color 0.2s ease;
}

.submenu-item span {
    font-size: 12px !important;
    flex: 1;
}

.submenu-item:hover {
    background: var(--hover-bg) !important;
    color: var(--text-color) !important;
    padding-left: 48px !important;
    transform: translateX(2px);
}

.submenu-item:hover i {
    color: var(--primary-color);
}

/* Active State - Clean Border */
.submenu-item.active {
    background: var(--input-bg) !important;
    color: var(--primary-color) !important;
    font-weight: 600 !important;
    border-left: 3px solid var(--primary-color);
    padding-left: 41px !important; /* 44px - 3px border */
}

.submenu-item.active i {
    color: var(--primary-color) !important;
}

.submenu-item.active:hover {
    padding-left: 45px !important; /* 48px - 3px border */
    background: var(--hover-bg) !important;
}

/* ========== DROPDOWN CẤP 2 (NESTED) ========== */
.menu-item-dropdown.nested {
    margin: 0;
}

.menu-item-dropdown.nested > .submenu-item {
    justify-content: space-between !important;
    cursor: pointer;
}

.nested-submenu {
    max-height: 0;
    overflow: hidden;
    padding-left: 0;
    margin-left: 0;
    background: transparent;
}

.menu-item-dropdown.nested.active > .nested-submenu {
    max-height: 500px;
}

/* Transition cho nested dropdown */
.menu-item-dropdown.nested.user-interacted > .nested-submenu {
    transition: max-height 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Style riêng cho nested submenu items */
.nested-submenu .submenu-item {
    padding-left: 64px !important; /* 44px + 20px more indent */
    font-size: 11px !important;
}

.nested-submenu .submenu-item:hover {
    padding-left: 68px !important;
}

.nested-submenu .submenu-item.active {
    padding-left: 61px !important; /* 64px - 3px border */
}

.nested-submenu .submenu-item.active:hover {
    padding-left: 65px !important; /* 68px - 3px border */
}

/* Icon cho nested dropdown */
.menu-item-dropdown.nested .dropdown-arrow {
    font-size: 0.6rem !important;
}

/* ========== PARENT HIGHLIGHT ========== */
/* Highlight parent khi nested submenu active */
.menu-item-dropdown.nested:has(.submenu-item.active) > .submenu-item {
    color: var(--text-color) !important;
    font-weight: 500;
}

/* Highlight cả 2 cấp parent */
.menu-item-dropdown:has(.nested .submenu-item.active) > .menu-item {
    color: var(--text-color) !important;
    font-weight: 500;
}

/* ========== LOGOUT LINK - RED COLOR ========== */
a.menu-item[href="/logout"] {
    color: #ef4444 !important;
}

a.menu-item[href="/logout"]:hover {
    color: #dc2626 !important;
}

a.menu-item[href="/logout"] i {
    color: #ef4444 !important;
}

a.menu-item[href="/logout"]:hover i {
    color: #dc2626 !important;
}

/* ========== LOGIN/REGISTER LINKS ========== */
a.menu-item[href="/auth/login"] {
    color: var(--primary-color) !important;
}

a.menu-item[href="/auth/login"] i {
    color: var(--primary-color) !important;
}

/* ========== SMOOTH ANIMATIONS ========== */
.menu-item,
.submenu-item,
.dropdown-arrow {
    will-change: transform, color, background-color;
    backface-visibility: hidden;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1024px) {
    .menu-item {
        padding: 11px 20px !important;
        font-size: 14px !important;
    }
    
    .menu-item:hover {
        padding-left: 24px !important;
    }
    
    .menu-item.active {
        padding-left: 17px !important;
    }
    
    .menu-item.active:hover {
        padding-left: 21px !important;
    }
    
    .submenu-item {
        padding: 10px 20px 10px 44px !important;
        font-size: 13px !important;
    }
    
    .submenu-item:hover {
        padding-left: 48px !important;
    }
    
    .submenu-item.active {
        padding-left: 41px !important;
    }
    
    .submenu-item.active:hover {
        padding-left: 45px !important;
    }
}

@media (max-width: 768px) {
    .menu-section {
        margin-bottom: 20px;
    }
    
    .menu-item {
        padding: 10px 16px !important;
    }
    
    .menu-item:hover {
        padding-left: 20px !important;
    }
    
    .menu-item.active {
        padding-left: 13px !important;
    }
    
    .menu-item.active:hover {
        padding-left: 17px !important;
    }
    
    .submenu-item {
        padding: 9px 16px 9px 40px !important;
    }
    
    .submenu-item:hover {
        padding-left: 44px !important;
    }
    
    .submenu-item.active {
        padding-left: 37px !important;
    }
    
    .submenu-item.active:hover {
        padding-left: 41px !important;
    }
    
    .nested-submenu .submenu-item {
        padding-left: 56px !important;
    }
    
    .nested-submenu .submenu-item:hover {
        padding-left: 60px !important;
    }
    
    .nested-submenu .submenu-item.active {
        padding-left: 53px !important;
    }
}

/* ========== DARK/LIGHT MODE SPECIFIC ========== */
html.dark .menu-item,
html.dark .submenu-item {
    background: transparent !important;
}

html.dark .menu-item:hover,
html.dark .submenu-item:hover {
    background: var(--hover-bg) !important;
}

html.dark .menu-item.active,
html.dark .submenu-item.active {
    background: var(--input-bg) !important;
}

html:not(.dark) .menu-item,
html:not(.dark) .submenu-item {
    background: transparent !important;
}

html:not(.dark) .menu-item:hover,
html:not(.dark) .submenu-item:hover {
    background: var(--hover-bg) !important;
}

html:not(.dark) .menu-item.active {
    background: rgba(0, 0, 0, 0.05) !important;
}

html:not(.dark) .submenu-item.active {
    background: rgba(0, 0, 0, 0.05) !important;
}

/* ========== PERFORMANCE OPTIMIZATION ========== */
.menu-item-dropdown,
.submenu,
.submenu-item,
.menu-item {
    transform: translateZ(0);
    perspective: 1000px;
}
</style>

<!-- JavaScript cho Dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tự động mở menu nếu đang ở trang con (Active state)
    const activeItems = document.querySelectorAll('.submenu-item.active');
    activeItems.forEach(item => {
        const parentDropdown = item.closest('.menu-item-dropdown');
        if (parentDropdown) {
            parentDropdown.classList.add('active');
        }
        const nestedParent = item.closest('.nested');
        if (nestedParent) {
            nestedParent.classList.add('active');
            const grandParent = nestedParent.closest('.menu-item-dropdown:not(.nested)');
            if (grandParent) grandParent.classList.add('active');
        }
    });

    // 2. Khôi phục trạng thái từ LocalStorage
    try {
        const openDropdowns = JSON.parse(localStorage.getItem('openDropdowns') || '[]');
        const allDropdowns = document.querySelectorAll('.menu-item-dropdown');
        openDropdowns.forEach(index => {
            if (allDropdowns[index]) allDropdowns[index].classList.add('active');
        });
    } catch (e) {
        console.log('Lỗi đọc LocalStorage', e);
    }

    // 3. Xử lý click cho dropdown
    const toggles = document.querySelectorAll('.menu-item-dropdown > .menu-item, .menu-item-dropdown.nested > .submenu-item');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation();

            const parent = this.parentElement;
            parent.classList.add('user-interacted');
            parent.classList.toggle('active');

            saveMenuState();
        });
    });

    function saveMenuState() {
        const activeIndexes = [];
        const allDropdowns = document.querySelectorAll('.menu-item-dropdown');
        allDropdowns.forEach((dropdown, index) => {
            if (dropdown.classList.contains('active')) {
                activeIndexes.push(index);
            }
        });
        localStorage.setItem('openDropdowns', JSON.stringify(activeIndexes));
    }
});
</script>


    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- =====================================================
       MAIN CONTENT
       ===================================================== -->
    <div class="main-content">
        
        <!-- =====================================================
           TOP HEADER
           ===================================================== -->
        <header class="top-header">
            <div class="header-left">
                <!-- Sidebar Toggle Button -->
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" title="<?php echo $lang === 'vi' ? 'Đóng/Mở Sidebar' : 'Toggle Sidebar'; ?>">
                    <i class="bi bi-layout-sidebar-inset sidebar-toggle-icon"></i>
                </button>
                
                <!-- Language Dropdown -->
                <div class="header-dropdown-container" id="language-dropdown-container">
                    <button class="header-btn" id="language-toggle" title="<?php echo $lang === 'vi' ? 'Ngôn ngữ' : 'Language'; ?>">
                        <?php if ($lang === 'vi'): ?>
                            <img src="/../assets/flags/vi.png?v=3" alt="VN" class="flag-icon-button">
                        <?php else: ?>
                            <img src="/../assets/flags/en.png?v=3" alt="EN" class="flag-icon-button">
                        <?php endif; ?>
                    </button>
                    
                    <div class="header-dropdown-menu" id="language-dropdown">
                        <div class="header-dropdown-header">
                            <span><?php echo $lang === 'vi' ? 'Chọn ngôn ngữ' : 'Select Language'; ?></span>
                        </div>
                        <div class="header-dropdown-list">
                            <a href="<?php 
                                $current_query = $_GET;
                                $current_query['lang'] = 'vi';
                                echo '?' . http_build_query($current_query);
                            ?>" class="header-dropdown-item <?php echo $lang === 'vi' ? 'active' : ''; ?>">
                                <img src="/../assets/flags/vi.png?v=3" alt="VN" class="flag-icon-dropdown">
                                <span>Tiếng Việt</span>
                                <?php if ($lang === 'vi'): ?>
                                    <i class="bi bi-check2 dropdown-item-check"></i>
                                <?php endif; ?>
                            </a>
                            
                            <a href="<?php 
                                $current_query = $_GET;
                                $current_query['lang'] = 'en';
                                echo '?' . http_build_query($current_query);
                            ?>" class="header-dropdown-item <?php echo $lang === 'en' ? 'active' : ''; ?>">
                                <img src="/../assets/flags/en.png?v=3" alt="EN" class="flag-icon-dropdown">
                                <span>English</span>
                                <?php if ($lang === 'en'): ?>
                                    <i class="bi bi-check2 dropdown-item-check"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Theme Dropdown -->
                <div class="header-dropdown-container" id="theme-dropdown-container">
                    <button class="header-btn" id="theme-toggle" title="<?php echo $lang === 'vi' ? 'Giao diện' : 'Theme'; ?>">
                        <i class="bi bi-sun-fill theme-icon-light"></i>
                        <i class="bi bi-moon-stars-fill theme-icon-dark active"></i>
                        <i class="bi bi-circle-half theme-icon-system"></i>
                    </button>
                    
                    <div class="header-dropdown-menu" id="theme-dropdown">
                        <div class="header-dropdown-header">
                            <span><?php echo $lang === 'vi' ? 'Chọn giao diện' : 'Select Theme'; ?></span>
                        </div>
                        <div class="header-dropdown-list">
                            <button class="header-dropdown-item" data-theme="light">
                                <i class="bi bi-sun-fill dropdown-item-icon"></i>
                                <span><?php echo $lang === 'vi' ? 'Giao diện sáng' : 'Light Mode'; ?></span>
                                <i class="bi bi-check2 dropdown-item-check d-none" id="check-light"></i>
                            </button>
                            
                            <button class="header-dropdown-item" data-theme="dark">
                                <i class="bi bi-moon-stars-fill dropdown-item-icon"></i>
                                <span><?php echo $lang === 'vi' ? 'Giao diện tối' : 'Dark Mode'; ?></span>
                                <i class="bi bi-check2 dropdown-item-check d-none" id="check-dark"></i>
                            </button>
                            
                            <button class="header-dropdown-item" data-theme="system">
                                <i class="bi bi-circle-half dropdown-item-icon"></i>
                                <span><?php echo $lang === 'vi' ? 'Theo hệ thống' : 'System Default'; ?></span>
                                <i class="bi bi-check2 dropdown-item-check d-none" id="check-system"></i>
                            </button>
                        </div>
                    </div>
                </div>

<!-- Notification Button -->
<?php if ($is_logged_in): ?>
<div id="notification-container">
    <button class="header-btn notification-btn" onclick="showNotificationFromButton()" title="<?php echo $lang === 'vi' ? 'Thông báo' : 'Notifications'; ?>">
        <i class="bi bi-bell-fill"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notification-badge" id="notification-count"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </button>
</div>
<?php endif; ?>
            </div>

            <div class="header-right">
                <?php if (!$is_logged_in): ?>
                <a href="/auth/login" class="login-btn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span><?php echo $lang === 'vi' ? 'Đăng Nhập' : 'Login'; ?></span>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- =====================================================
           CONTENT AREA
           ===================================================== -->
        <div class="content-area">