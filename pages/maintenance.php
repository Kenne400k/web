<?php
ob_start();
$page_title = 'Đang Bảo Trì - Studio';

// ⚠️ QUAN TRỌNG: Phải lấy path TRƯỚC KHI require header (vì header có thể redirect)
$service_path = $_GET['path'] ?? '';

require_once '../config/header.php';
require_once '../config/sidebar.php';

// Lấy thông tin bảo trì
$stmt = $mysqli->prepare("SELECT service_name, message FROM maintenance_mode WHERE service_path = ? AND is_active = 1");
$stmt->bind_param("s", $service_path);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Nếu không còn bảo trì, redirect về trang gốc
    header("Location: $service_path");
    exit;
}

$maintenance = $result->fetch_assoc();
$service_name = $maintenance['service_name'];
$message = $maintenance['message'];

// Đa ngôn ngữ
$texts = [
    'vi' => [
        'title' => 'Đang bảo trì hệ thống',
        'back' => 'Quay lại',
        'support' => 'Hỗ trợ',
        'status' => 'Đang xử lý',
        'wait' => 'Sẽ sớm hoàn thành'
    ],
    'en' => [
        'title' => 'System Maintenance',
        'back' => 'Go Back',
        'support' => 'Support',
        'status' => 'In Progress',
        'wait' => 'Will be completed soon'
    ]
];

$t = $texts[$lang] ?? $texts['vi'];
?>

<style>
    /* Container chính */
    .maintenance-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 85vh;
        padding: 24px;
    }

    /* Card bảo trì với viền gradient */
    .maintenance-card {
        background: transparent;
        border-radius: 24px;
        padding: 60px 50px;
        text-align: center;
        max-width: 600px;
        width: 100%;
        position: relative;
        overflow: hidden;
        z-index: 0;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }

    /* Lớp 1: Gradient xoay */
    .maintenance-card::before {
        content: '';
        position: absolute;
        inset: -150%;
        background: conic-gradient(
            from 0deg,
            #1a1a2e 0deg,
            oklch(54.6% .245 262.881) 45deg,
            oklch(62.3% .214 259.815) 90deg,
            oklch(70.7% .165 254.624) 135deg,
            oklch(29.1% .149 302.717) 180deg,
            oklch(48.8% .243 264.376) 225deg,
            oklch(54.6% .245 262.881) 270deg,
            oklch(42.4% .199 265.638) 315deg,
            #1a1a2e 360deg
        );
        animation: spin 8s linear infinite, colorChange 16s ease-in-out infinite;
        z-index: -2;
    }

    /* Lớp 2: Nền tối */
    .maintenance-card::after {
        content: '';
        position: absolute;
        inset: 1.5px;
        background: #0f0f0f;
        border-radius: 22.5px;
        z-index: -1;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    @keyframes colorChange {
        0%, 100% { filter: hue-rotate(0deg) saturate(1); }
        50% { filter: hue-rotate(80deg) saturate(1.2); }
    }

    /* Icon công cụ */
    .maintenance-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        background: #222;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #888;
        position: relative;
        z-index: 2;
    }

    /* Tiêu đề */
    .maintenance-card h1 {
        font-size: 32px;
        margin-bottom: 20px;
        color: #e0e0e0;
        position: relative;
        z-index: 2;
        font-weight: 600;
    }

    /* Badge tên dịch vụ */
    .service-badge {
        display: inline-block;
        padding: 6px 16px;
        background: #2a2a2a;
        color: #bbb;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 25px;
        position: relative;
        z-index: 2;
        border: 1px solid #333;
    }

    /* Thông điệp */
    .maintenance-message {
        font-size: 15px;
        color: #999;
        line-height: 1.7;
        margin-bottom: 35px;
        position: relative;
        z-index: 2;
    }

    /* Nút hành động */
    .action-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .btn-primary {
        background: #444;
        color: #e0e0e0;
        border: none;
    }

    .btn-primary:hover {
        background: #555;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: transparent;
        border: 1px solid #444;
        color: #aaa;
    }

    .btn-secondary:hover {
        border-color: #666;
        color: #e0e0e0;
    }

    /* Thông tin dưới cùng */
    .estimate-info {
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #222;
        position: relative;
        z-index: 2;
    }

    .estimate-info p {
        font-size: 13px;
        color: #666;
        margin-top: 12px;
    }

    /* Chỉ báo trạng thái */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: rgba(255, 193, 7, 0.08);
        border-radius: 16px;
        font-size: 12px;
        color: #d4a017;
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .status-dot {
        width: 6px;
        height: 6px;
        background: #d4a017;
        border-radius: 50%;
        animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.15); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .maintenance-card { 
            padding: 40px 30px; 
        }
        .maintenance-card h1 { 
            font-size: 26px; 
        }
    }
</style>

<div class="maintenance-container">
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class="bi bi-tools"></i>
        </div>
        
        <h1><?php echo $t['title']; ?></h1>
        
        <span class="service-badge">
            <?php echo htmlspecialchars($service_name); ?>
        </span>
        
        <p class="maintenance-message">
            <?php echo htmlspecialchars($message); ?>
        </p>

        <div class="action-buttons">
            <a href="/dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i>
                <?php echo $t['back']; ?>
            </a>
            <a href="/support" class="btn btn-secondary">
                <i class="bi bi-headset"></i>
                <?php echo $t['support']; ?>
            </a>
        </div>

        <div class="estimate-info">
            <div class="status-indicator">
                <span class="status-dot"></span>
                <?php echo $t['status']; ?>
            </div>
            <p><?php echo $t['wait']; ?></p>
        </div>
    </div>
</div>

<script>
    // Auto refresh mỗi 10s
    setTimeout(() => location.reload(), 60000);
</script>

<?php require_once '../config/footer.php'; ?>