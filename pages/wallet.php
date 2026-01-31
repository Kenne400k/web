<?php
$page_title = 'Wallet';
require_once __DIR__ . '/../config/header.php';
require_once __DIR__ . '/../config/database.php';

$conn = $mysqli;
$current_user_id = $user['id'];

// Lấy số dư hiện tại
$balance = $user['sodu'];

// Lấy tất cả transactions
$transactions = [];
$transSql = "SELECT * FROM Transactions WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt = $conn->prepare($transSql)) {
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Tính toán thống kê
$stats = [
    'total_deposit' => 0,
    'total_spent' => 0,
    'total_refund' => 0,
    'transaction_count' => count($transactions)
];

foreach ($transactions as $trans) {
    switch ($trans['type']) {
        case 'deposit':
            $stats['total_deposit'] += $trans['amount'];
            break;
        case 'order':
            $stats['total_spent'] += $trans['amount'];
            break;
        case 'refund':
            $stats['total_refund'] += $trans['amount'];
            break;
    }
}

// Lấy transactions theo filter
$filter = $_GET['filter'] ?? 'all';
$filteredTransactions = $transactions;

if ($filter !== 'all') {
    $filteredTransactions = array_filter($transactions, function($trans) use ($filter) {
        return $trans['type'] === $filter;
    });
}

require_once '../config/sidebar.php';
?>

<style>
    .wallet-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Wallet Header */
    .wallet-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .wallet-header::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .wallet-header::after {
        content: '';
        position: absolute;
        bottom: -50px;
        left: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .wallet-header-content {
        position: relative;
        z-index: 1;
        color: white;
    }

    .wallet-balance-label {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .wallet-balance-amount {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: baseline;
        gap: 5px;
    }

    .currency-symbol {
        font-size: 36px;
        opacity: 0.8;
    }

    .wallet-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .wallet-btn {
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 10px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        text-decoration: none;
    }

    .wallet-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .wallet-btn i {
        font-size: 18px;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.green {
        background: linear-gradient(135deg, #56ab2f, #a8e063);
        color: white;
    }

    .stat-icon.red {
        background: linear-gradient(135deg, #f093fb, #f5576c);
        color: white;
    }

    .stat-icon.blue {
        background: linear-gradient(135deg, #4facfe, #00f2fe);
        color: white;
    }

    .stat-icon.purple {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-color);
    }

    .stat-trend {
        font-size: 12px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .trend-up {
        color: #28a745;
    }

    .trend-down {
        color: #dc3545;
    }

    /* Transactions Section */
    .transactions-section {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }

    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: #667eea;
    }

    /* Filter Tabs */
    .filter-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-tab {
        padding: 8px 16px;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .filter-tab:hover {
        background: var(--border-color);
    }

    .filter-tab.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: #667eea;
    }

    /* Transaction Table */
    .transaction-table {
        width: 100%;
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table thead {
        background: var(--input-bg);
    }

    .table th {
        padding: 15px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        padding: 15px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
    }

    .table tbody tr {
        transition: all 0.3s;
    }

    .table tbody tr:hover {
        background: var(--input-bg);
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Transaction Type Badge */
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .type-badge.deposit {
        background: rgba(86, 171, 47, 0.1);
        color: #56ab2f;
    }

    .type-badge.order {
        background: rgba(240, 147, 251, 0.1);
        color: #f5576c;
    }

    .type-badge.refund {
        background: rgba(79, 172, 254, 0.1);
        color: #4facfe;
    }

    .type-badge i {
        font-size: 14px;
    }

    /* Amount Display */
    .amount-display {
        font-size: 16px;
        font-weight: 700;
    }

    .amount-positive {
        color: #28a745;
    }

    .amount-negative {
        color: #dc3545;
    }

    /* Transaction ID */
    .transaction-id {
        font-family: monospace;
        font-size: 12px;
        color: var(--text-muted);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 64px;
        opacity: 0.3;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: var(--text-color);
    }

    .empty-state p {
        font-size: 14px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid var(--border-color);
    }

    .pagination-btn {
        padding: 8px 16px;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-color);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .pagination-btn:hover {
        background: var(--border-color);
    }

    .pagination-btn.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: #667eea;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wallet-wrapper {
            padding: 15px;
        }

        .wallet-header {
            padding: 25px;
        }

        .wallet-balance-amount {
            font-size: 36px;
        }

        .wallet-actions {
            flex-direction: column;
        }

        .wallet-btn {
            width: 100%;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .filter-tabs {
            width: 100%;
        }

        .filter-tab {
            flex: 1;
            justify-content: center;
        }

        .transaction-table {
            overflow-x: auto;
        }

        .table {
            min-width: 600px;
        }
    }

    /* Quick Actions Card */
    .quick-actions {
        background: var(--content-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .quick-actions-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .quick-action-card {
        padding: 20px;
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: var(--text-color);
    }

    .quick-action-card:hover {
        transform: translateY(-5px);
        border-color: #667eea;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    }

    .quick-action-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 15px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }

    .quick-action-icon.deposit {
        background: linear-gradient(135deg, #56ab2f, #a8e063);
        color: white;
    }

    .quick-action-icon.history {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .quick-action-icon.support {
        background: linear-gradient(135deg, #f093fb, #f5576c);
        color: white;
    }

    .quick-action-label {
        font-size: 14px;
        font-weight: 600;
    }
</style>

<div class="wallet-wrapper">
    <!-- Wallet Header -->
    <div class="wallet-header">
        <div class="wallet-header-content">
            <div class="wallet-balance-label">
                <i class="bi bi-wallet2"></i>
                <?php echo $lang === 'vi' ? 'Số dư ví của bạn' : 'Your Wallet Balance'; ?>
            </div>
            <div class="wallet-balance-amount">
                <span class="currency-symbol">$</span>
                <?php echo number_format($balance, 4); ?>
            </div>
            <div class="wallet-actions">
                <a href="/recharge" class="wallet-btn">
                    <i class="bi bi-cash"></i>
                    <?php echo $lang === 'vi' ? 'Nạp tiền' : 'Deposit'; ?>
                </a>
                <a href="/international_deposit" class="wallet-btn">
                    <i class="bi bi-plus-circle"></i>
                    <?php echo $lang === 'vi' ? 'Nạp tiền Quốc Tế' : 'international Deposit'; ?>
                </a>
                <a href="/orders" class="wallet-btn">
                    <i class="bi bi-cart"></i>
                    <?php echo $lang === 'vi' ? 'Đặt hàng' : 'Place Order'; ?>
                </a>
                <a href="/profile" class="wallet-btn">
                    <i class="bi bi-person"></i>
                    <?php echo $lang === 'vi' ? 'Hồ sơ' : 'Profile'; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon green">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Tổng nạp' : 'Total Deposits'; ?></div>
                <div class="stat-value">$<?php echo number_format($stats['total_deposit'], 2); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon red">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Tổng chi' : 'Total Spent'; ?></div>
                <div class="stat-value">$<?php echo number_format($stats['total_spent'], 2); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon blue">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Tổng hoàn' : 'Total Refunds'; ?></div>
                <div class="stat-value">$<?php echo number_format($stats['total_refund'], 2); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon purple">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
            <div class="stat-content">
                <div class="stat-label"><?php echo $lang === 'vi' ? 'Giao dịch' : 'Transactions'; ?></div>
                <div class="stat-value"><?php echo $stats['transaction_count']; ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-actions-title">
            <i class="bi bi-lightning-charge"></i>
            <?php echo $lang === 'vi' ? 'Thao tác nhanh' : 'Quick Actions'; ?>
        </div>
        <div class="quick-actions-grid">
            <a href="/recharge" class="quick-action-card">
                <div class="quick-action-icon deposit">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="quick-action-label">
                    <?php echo $lang === 'vi' ? 'Nạp tiền ngay' : 'Deposit Now'; ?>
                </div>
            </a>
            <a href="?filter=all" class="quick-action-card">
                <div class="quick-action-icon history">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="quick-action-label">
                    <?php echo $lang === 'vi' ? 'Lịch sử đầy đủ' : 'Full History'; ?>
                </div>
            </a>
            <a href="/support" class="quick-action-card">
                <div class="quick-action-icon support">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="quick-action-label">
                    <?php echo $lang === 'vi' ? 'Hỗ trợ' : 'Support'; ?>
                </div>
            </a>
        </div>
    </div>

    <!-- Transactions Section -->
    <div class="transactions-section">
        <div class="section-header">
            <div class="section-title">
                <i class="bi bi-list-ul"></i>
                <?php echo $lang === 'vi' ? 'Lịch sử giao dịch' : 'Transaction History'; ?>
            </div>
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>
                    <?php echo $lang === 'vi' ? 'Tất cả' : 'All'; ?>
                </a>
                <a href="?filter=deposit" class="filter-tab <?php echo $filter === 'deposit' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-down-circle"></i>
                    <?php echo $lang === 'vi' ? 'Nạp tiền' : 'Deposits'; ?>
                </a>
                <a href="?filter=order" class="filter-tab <?php echo $filter === 'order' ? 'active' : ''; ?>">
                    <i class="bi bi-cart"></i>
                    <?php echo $lang === 'vi' ? 'Đơn hàng' : 'Orders'; ?>
                </a>
                <a href="?filter=refund" class="filter-tab <?php echo $filter === 'refund' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <?php echo $lang === 'vi' ? 'Hoàn tiền' : 'Refunds'; ?>
                </a>
            </div>
        </div>

        <?php if (empty($filteredTransactions)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3><?php echo $lang === 'vi' ? 'Chưa có giao dịch' : 'No Transactions'; ?></h3>
                <p><?php echo $lang === 'vi' ? 'Bạn chưa có giao dịch nào trong mục này' : 'You don\'t have any transactions in this category yet'; ?></p>
            </div>
        <?php else: ?>
            <div class="transaction-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo $lang === 'vi' ? 'ID' : 'ID'; ?></th>
                            <th><?php echo $lang === 'vi' ? 'Loại' : 'Type'; ?></th>
                            <th><?php echo $lang === 'vi' ? 'Nội dung' : 'Description'; ?></th>
                            <th><?php echo $lang === 'vi' ? 'Số tiền' : 'Amount'; ?></th>
                            <th><?php echo $lang === 'vi' ? 'Thời gian' : 'Date'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredTransactions as $trans): ?>
                            <tr>
                                <td>
                                    <span class="transaction-id">#<?php echo str_pad($trans['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <span class="type-badge <?php echo htmlspecialchars($trans['type']); ?>">
                                        <?php 
                                        $icons = [
                                            'deposit' => 'bi-arrow-down-circle',
                                            'order' => 'bi-cart-check',
                                            'refund' => 'bi-arrow-counterclockwise'
                                        ];
                                        echo '<i class="bi ' . ($icons[$trans['type']] ?? 'bi-circle') . '"></i>';
                                        
                                        $typeLabels = [
                                            'deposit' => $lang === 'vi' ? 'Nạp tiền' : 'Deposit',
                                            'order' => $lang === 'vi' ? 'Đặt hàng' : 'Order',
                                            'refund' => $lang === 'vi' ? 'Hoàn tiền' : 'Refund'
                                        ];
                                        echo $typeLabels[$trans['type']] ?? ucfirst($trans['type']);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($trans['content']); ?></td>
                                <td>
                                    <span class="amount-display <?php echo $trans['type'] === 'deposit' ? 'amount-positive' : 'amount-negative'; ?>">
                                        <?php echo $trans['type'] === 'deposit' ? '+' : '-'; ?>$<?php echo number_format($trans['amount'], 4); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 14px;"><?php echo date('d/m/Y', strtotime($trans['created_at'])); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo date('H:i:s', strtotime($trans['created_at'])); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>