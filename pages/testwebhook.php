<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test FPAYAZ Webhook</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 30px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #16213e;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #00ff88;
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .section {
            background: #0f3460;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #00ff88;
        }
        
        .section h2 {
            color: #00ff88;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #aaa;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #1a1a2e;
            border: 1px solid #444;
            border-radius: 6px;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00ff88;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: #00ff88;
            color: #000;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #00cc6a;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #444;
            color: #888;
            cursor: not-allowed;
            transform: none;
        }
        
        #result {
            margin-top: 20px;
            padding: 20px;
            background: #1a1a2e;
            border-radius: 8px;
            white-space: pre-wrap;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        
        #result.show {
            display: block;
        }
        
        .success {
            color: #00ff88;
        }
        
        .error {
            color: #ff4444;
        }
        
        .info {
            color: #ffa500;
        }
        
        .code-block {
            background: #000;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .hash-display {
            background: #000;
            padding: 10px;
            border-radius: 6px;
            color: #ffa500;
            font-size: 12px;
            word-break: break-all;
            margin-top: 10px;
        }
        
        .loading {
            display: inline-block;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .bonus-calc {
            background: #0f3460;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            border: 2px dashed #00ff88;
        }
        
        .bonus-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        
        .bonus-row.total {
            border-top: 2px solid #444;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 16px;
            color: #00ff88;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 FPAYAZ WEBHOOK TESTER</h1>
        <p class="subtitle">Simulate FPAYAZ gửi webhook về server của bạn</p>

        <!-- Config Section -->
        <div class="section">
            <h2>⚙️ WEBHOOK CONFIGURATION</h2>
            <div class="code-block">
<span class="info">Webhook URL:</span> https://kingcongstudio.com/auth/nganhang/webhookquocte.php
<span class="info">Secret Key:</span> 77329da69ad69c723f0c102c4a455b09
            </div>
        </div>

        <!-- Test Form -->
        <div class="section">
            <h2>💳 TEST PAYMENT DATA</h2>
            
            <div class="form-group">
                <label>User ID (từ database của bạn)</label>
                <input type="number" id="user_id" value="1" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" value="testuser" required>
            </div>

            <div class="form-group">
                <label>Số tiền nạp (USD)</label>
                <input type="number" id="amount" value="100" step="0.01" min="1" required>
            </div>

            <div class="form-group">
                <label>Transaction ID (auto-generate)</label>
                <input type="text" id="trans_id" readonly>
            </div>

            <div id="bonus-preview" class="bonus-calc" style="display: none;">
                <div class="bonus-row">
                    <span>Số tiền gốc:</span>
                    <span id="preview-amount">$0.00</span>
                </div>
                <div class="bonus-row">
                    <span>Khuyến mãi:</span>
                    <span id="preview-bonus" class="success">+$0.00 (0%)</span>
                </div>
                <div class="bonus-row total">
                    <span>Tổng nhận được:</span>
                    <span id="preview-total">$0.00</span>
                </div>
            </div>

            <button class="btn" id="sendBtn" onclick="sendWebhook()">
                🚀 GỬI WEBHOOK TEST
            </button>
        </div>

        <!-- Result Section -->
        <div id="result"></div>
    </div>

    <script>
        // Generate random transaction ID
        function generateTransId() {
            return 'TEST_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9).toUpperCase();
        }

        // Calculate bonus
        function calculateBonus(amount) {
            if (amount >= 1000) return 7;
            if (amount >= 500) return 6;
            if (amount >= 100) return 5;
            return 0;
        }

        // Update preview when amount changes
        document.getElementById('amount').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const bonusPercent = calculateBonus(amount);
            const bonusAmount = (amount * bonusPercent / 100);
            const total = amount + bonusAmount;

            const preview = document.getElementById('bonus-preview');
            if (amount > 0) {
                preview.style.display = 'block';
                document.getElementById('preview-amount').textContent = '$' + amount.toFixed(2);
                document.getElementById('preview-bonus').textContent = '+$' + bonusAmount.toFixed(2) + ' (' + bonusPercent + '%)';
                document.getElementById('preview-total').textContent = '$' + total.toFixed(2);
            } else {
                preview.style.display = 'none';
            }
        });

        // Generate trans ID on load
        document.getElementById('trans_id').value = generateTransId();

        // SHA256 HMAC function
        async function sha256hmac(message, secret) {
            const encoder = new TextEncoder();
            const keyData = encoder.encode(secret);
            const messageData = encoder.encode(message);
            
            const key = await crypto.subtle.importKey(
                'raw',
                keyData,
                { name: 'HMAC', hash: 'SHA-256' },
                false,
                ['sign']
            );
            
            const signature = await crypto.subtle.sign('HMAC', key, messageData);
            const hashArray = Array.from(new Uint8Array(signature));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        }

        async function sendWebhook() {
            const btn = document.getElementById('sendBtn');
            const result = document.getElementById('result');
            
            // Get form values
            const userId = document.getElementById('user_id').value;
            const username = document.getElementById('username').value;
            const amount = parseFloat(document.getElementById('amount').value);
            const transId = document.getElementById('trans_id').value || generateTransId();
            
            if (!userId || !username || !amount || amount < 1) {
                result.innerHTML = '<span class="error">❌ Vui lòng điền đầy đủ thông tin!</span>';
                result.classList.add('show');
                return;
            }

            // Disable button
            btn.disabled = true;
            btn.innerHTML = '<span class="loading">⏳ Đang gửi webhook...</span>';
            
            result.innerHTML = '<span class="info">📤 Đang chuẩn bị dữ liệu...</span>';
            result.classList.add('show');

            try {
                // Prepare webhook data
                const timestamp = Math.floor(Date.now() / 1000);
                const description = `Test payment from ${username}`;
                const secretKey = '77329da69ad69c723f0c102c4a455b09';
                
                // Calculate bonus
                const bonusPercent = calculateBonus(amount);
                const bonusAmount = (amount * bonusPercent / 100);
                const totalAmount = amount + bonusAmount;
                
                // Generate hash
                const hashString = transId + amount + description + timestamp;
                const hash = await sha256hmac(hashString, secretKey);
                
                // Prepare payload
                const payload = {
                    TRANS_ID: transId,
                    AMOUNT: amount.toFixed(2),
                    DESCRIPTION: description,
                    TIMESTAMPGMT: timestamp.toString(),
                    HASH: hash,
                    ACCOUNT_ID: userId,
                    ACCOUNT_USER: username
                };

                // Display request info
                let output = '<span class="success">✅ Dữ liệu đã chuẩn bị xong!</span>\n\n';
                output += '<span class="info">📋 PAYLOAD GỬI ĐI:</span>\n';
                output += JSON.stringify(payload, null, 2) + '\n\n';
                output += '<span class="info">🔐 HASH CALCULATION:</span>\n';
                output += '<div class="hash-display">' + hashString + '</div>\n';
                output += '<div class="hash-display">Hash: ' + hash + '</div>\n\n';
                
                output += '<span class="info">💰 BONUS CALCULATION:</span>\n';
                output += `Số tiền gốc: $${amount.toFixed(2)}\n`;
                output += `Bonus: ${bonusPercent}% = $${bonusAmount.toFixed(2)}\n`;
                output += `<span class="success">Tổng cộng vào tài khoản: $${totalAmount.toFixed(2)}</span>\n\n`;
                
                output += '<span class="loading">🚀 Đang gửi đến webhook...</span>\n';
                result.innerHTML = output;

                // Send webhook
                const response = await fetch('https://kingcongstudio.com/auth/nganhang/webhookquocte.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const responseText = await response.text();
                
                output += '\n<span class="info">📥 RESPONSE TỪ SERVER:</span>\n';
                output += `Status: ${response.status} ${response.statusText}\n`;
                output += `Body: ${responseText}\n\n`;
                
                if (response.ok && responseText === 'OK') {
                    output += '<span class="success">✅✅✅ WEBHOOK THÀNH CÔNG! ✅✅✅</span>\n\n';
                    output += '<span class="success">🎉 Tiền đã được cộng vào tài khoản!</span>\n';
                    output += `<span class="success">• User ID: ${userId}</span>\n`;
                    output += `<span class="success">• Số tiền gốc: $${amount.toFixed(2)}</span>\n`;
                    output += `<span class="success">• Bonus: ${bonusPercent}% (+$${bonusAmount.toFixed(2)})</span>\n`;
                    output += `<span class="success">• Tổng nhận: $${totalAmount.toFixed(2)}</span>\n\n`;
                    output += '<span class="info">💡 Kiểm tra:</span>\n';
                    output += '1. Check balance trong database\n';
                    output += '2. Check bảng Transactions\n';
                    output += '3. Check bảng fpayaz_transactions_log\n';
                    output += '4. Check file log: _fpayaz_webhook_log.txt\n';
                } else {
                    output += '<span class="error">❌ WEBHOOK THẤT BẠI!</span>\n\n';
                    output += '<span class="error">Các nguyên nhân có thể:</span>\n';
                    output += '• Hash không hợp lệ\n';
                    output += '• User ID không tồn tại\n';
                    output += '• Database connection lỗi\n';
                    output += '• Webhook URL sai\n';
                }
                
                result.innerHTML = output;

                // Generate new trans ID for next test
                document.getElementById('trans_id').value = generateTransId();

            } catch (error) {
                result.innerHTML = '<span class="error">❌ LỖI KẾT NỐI:</span>\n' + error.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🚀 GỬI WEBHOOK TEST';
            }
        }

        // Trigger preview on load
        document.getElementById('amount').dispatchEvent(new Event('input'));
    </script>
</body>
</html>