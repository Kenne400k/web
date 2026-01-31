<?php
class AffiliateSystem {
    private $conn;
    
    public function __construct($mysqli) {
        $this->conn = $mysqli;
    }
    
    /**
     * Lấy cấu hình
     */
    public function getConfig($key, $default = null) {
        $stmt = $this->conn->prepare("SELECT config_value FROM affiliate_config WHERE config_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? $result['config_value'] : $default;
    }
    
    /**
     * Tạo hoa hồng khi người được giới thiệu nạp tiền
     */
    public function createDepositCommission($referee_id, $deposit_amount, $transaction_id, $commission_type = 'deposit_bank') {
        // 1. Kiểm tra user này có được ai giới thiệu không
        $stmt = $this->conn->prepare("SELECT referred_by, created_at FROM Users WHERE id = ?");
        $stmt->bind_param("i", $referee_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user || !$user['referred_by']) {
            $this->log("User #$referee_id không có người giới thiệu, bỏ qua hoa hồng.");
            return false;
        }
        
        $referrer_id = (int)$user['referred_by'];
        
        // 2. Kiểm tra thời gian hợp lệ (nếu có giới hạn)
        $commission_lifetime = (int)$this->getConfig('commission_lifetime', 0);
        if ($commission_lifetime > 0) {
            $user_created = strtotime($user['created_at']);
            $days_passed = (time() - $user_created) / 86400;
            
            if ($days_passed > $commission_lifetime) {
                $this->log("User #$referee_id đã quá thời gian hưởng hoa hồng ($days_passed ngày > $commission_lifetime ngày).");
                return false;
            }
        }
        
        // 3. Kiểm tra số tiền tối thiểu
        $min_deposit = (float)$this->getConfig('min_deposit_for_commission', 10);
        if ($deposit_amount < $min_deposit) {
            $this->log("Số tiền nạp $$deposit_amount < $$min_deposit (tối thiểu), bỏ qua hoa hồng.");
            return false;
        }
        
        // 4. Tính hoa hồng
        $commission_rate = (float)$this->getConfig('commission_rate_deposit', 10);
        $commission_amount = round($deposit_amount * $commission_rate / 100, 2);
        
        // 5. Giới hạn hoa hồng tối đa
        $max_commission = (float)$this->getConfig('max_commission_per_deposit', 1000);
        if ($commission_amount > $max_commission) {
            $commission_amount = $max_commission;
            $this->log("Hoa hồng vượt giới hạn, giới hạn lại ở $$max_commission");
        }
        
        // 6. Kiểm tra trùng (tránh tính 2 lần cho cùng 1 giao dịch)
        $stmt = $this->conn->prepare("SELECT id FROM affiliate_commissions WHERE transaction_id = ? AND referee_id = ?");
        $stmt->bind_param("si", $transaction_id, $referee_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            $this->log("Hoa hồng cho transaction #$transaction_id đã tồn tại, bỏ qua.");
            return false;
        }
        $stmt->close();
        
        // 7. Lưu hoa hồng vào database
        $status = 'approved';
        $notes = "Hoa hồng $commission_rate% từ nạp tiền $$deposit_amount của user #$referee_id";
        
        $stmt = $this->conn->prepare("
            INSERT INTO affiliate_commissions 
            (referrer_id, referee_id, commission_type, transaction_id, original_amount, commission_amount, commission_rate, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // ✅ FIX: Đúng thứ tự kiểu dữ liệu
        // i = integer, s = string, d = double
        $stmt->bind_param(
            "iissdddss",
            $referrer_id,      // i
            $referee_id,       // i
            $commission_type,  // s
            $transaction_id,   // s
            $deposit_amount,   // d
            $commission_amount,// d
            $commission_rate,  // d
            $status,           // s
            $notes             // s
        );
        
        if (!$stmt->execute()) {
            $this->log("❌ Lỗi lưu hoa hồng: " . $stmt->error);
            $stmt->close();
            return false;
        }
        $stmt->close();
        
        // 8. Cộng tiền vào tài khoản người giới thiệu
        $this->addBalance($referrer_id, $commission_amount, $transaction_id, $referee_id);
        
        $this->log("✅ Hoa hồng: User #$referrer_id nhận $$commission_amount ($commission_rate%) từ nạp tiền $$deposit_amount của user #$referee_id");
        
        return true;
    }
    
    /**
     * Cộng tiền vào tài khoản
     */
    private function addBalance($user_id, $amount, $transaction_id, $from_user_id) {
    // ✅ KHÔNG LÀM GÌ CẢ - CHỈ GHI LOG
    $this->log("💰 Hoa hồng $$amount đã được ghi nhận cho user #$user_id (chưa rút)");
}
    
    /**
     * Ghi log
     */
    private function log($message) {
        $log_file = __DIR__ . '/../logs/affiliate_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    /**
     * Lấy thống kê của user
     */
    public function getUserStats($user_id) {
        // Tổng số người giới thiệu
        $stmt1 = $this->conn->prepare("SELECT COUNT(*) as total FROM Users WHERE referred_by = ?");
        $stmt1->bind_param("i", $user_id);
        $stmt1->execute();
        $total_referrals = $stmt1->get_result()->fetch_assoc()['total'];
        $stmt1->close();
        
        // Tổng hoa hồng
        $stmt2 = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_commissions,
                SUM(commission_amount) as total_earned,
                SUM(CASE WHEN withdrawn = 0 THEN commission_amount ELSE 0 END) as available,
                SUM(CASE WHEN withdrawn = 1 THEN commission_amount ELSE 0 END) as withdrawn
            FROM affiliate_commissions 
            WHERE referrer_id = ? AND status = 'approved'
        ");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $earnings = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        
        return [
            'total_referrals' => $total_referrals,
            'total_commissions' => $earnings['total_commissions'] ?? 0,
            'total_earned' => $earnings['total_earned'] ?? 0,
            'available' => $earnings['available'] ?? 0,        // ✅ Chưa rút
            'withdrawn' => $earnings['withdrawn'] ?? 0          // ✅ Đã rút
        ];
    }
    /**
     * Lấy số dư hoa hồng có thể rút của user
     */
    public function getWithdrawableBalance($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(commission_amount) as available,
                COUNT(*) as total_commissions
            FROM affiliate_commissions 
            WHERE referrer_id = ? 
            AND status = 'approved' 
            AND withdrawn = 0
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return [
            'available' => (float)($result['available'] ?? 0),
            'total_commissions' => (int)($result['total_commissions'] ?? 0)
        ];
    }
    
    /**
     * Rút hoa hồng về credits
     * 
     * @param int $user_id ID người rút
     * @param float $amount Số tiền muốn rút (0 = rút tất cả)
     * @return array ['success' => bool, 'message' => string, 'amount' => float]
     */
    public function withdrawCommission($user_id, $amount = 0) {
        // 1. Lấy số dư có thể rút
        $balance = $this->getWithdrawableBalance($user_id);
        
        if ($balance['available'] <= 0) {
            return [
                'success' => false,
                'message' => 'Không có hoa hồng để rút',
                'amount' => 0
            ];
        }
        
        // 2. Xác định số tiền rút
        $withdraw_amount = ($amount > 0) ? min($amount, $balance['available']) : $balance['available'];
        
        if ($withdraw_amount <= 0) {
            return [
                'success' => false,
                'message' => 'Số tiền rút không hợp lệ',
                'amount' => 0
            ];
        }
        
        // 3. Bắt đầu transaction
        $this->conn->begin_transaction();
        
        try {
            // 4. Lấy danh sách hoa hồng chưa rút (theo thứ tự cũ nhất trước)
            $stmt = $this->conn->prepare("
                SELECT id, commission_amount 
                FROM affiliate_commissions 
                WHERE referrer_id = ? 
                AND status = 'approved' 
                AND withdrawn = 0 
                ORDER BY created_at ASC
                FOR UPDATE
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $commissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // 5. Đánh dấu các hoa hồng đã rút
            $remaining = $withdraw_amount;
            $commission_ids = [];
            
            foreach ($commissions as $comm) {
                if ($remaining <= 0) break;
                
                $commission_ids[] = $comm['id'];
                $remaining -= $comm['commission_amount'];
            }
            
            if (!empty($commission_ids)) {
                $ids_str = implode(',', $commission_ids);
                $now = date('Y-m-d H:i:s');
                
                $stmt = $this->conn->prepare("
                    UPDATE affiliate_commissions 
                    SET withdrawn = 1, withdrawn_at = ? 
                    WHERE id IN ($ids_str)
                ");
                $stmt->bind_param("s", $now);
                $stmt->execute();
                $stmt->close();
            }
            
            // 6. Cộng tiền vào credits
            $stmt = $this->conn->prepare("UPDATE Users SET credits = credits + ? WHERE id = ?");
            $stmt->bind_param("di", $withdraw_amount, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // 7. Ghi lịch sử giao dịch
            $type = 'affiliate_withdraw';
            $note = "Rút hoa hồng tiếp thị liên kết: $" . number_format($withdraw_amount, 2);
            
            $stmt = $this->conn->prepare("INSERT INTO Transactions (user_id, amount, type, content) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $user_id, $withdraw_amount, $type, $note);
            $stmt->execute();
            $stmt->close();
            
            $this->conn->commit();
            
            $this->log("✅ User #$user_id rút hoa hồng: $$withdraw_amount");
            
            return [
                'success' => true,
                'message' => 'Rút hoa hồng thành công',
                'amount' => $withdraw_amount
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->log("❌ Lỗi rút hoa hồng user #$user_id: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'amount' => 0
            ];
        }
    }
    
    /**
     * Lấy lịch sử rút hoa hồng
     */
    public function getWithdrawHistory($user_id, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT * FROM Transactions 
            WHERE user_id = ? AND type = 'affiliate_withdraw' 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }
}
?>