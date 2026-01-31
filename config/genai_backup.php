<?php
class GenAIBackup {
    private $conn;
    private $min_credits = 100000; // Minimum 100k credits to use backup
    private $daily_limit = 200000; // 200k credits per day limit
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * ✅ GET GENAI API CONFIG (WITH MULTIPLE KEYS SUPPORT)
     */
    public function getApiConfig() {
        $stmt = $this->conn->prepare("
            SELECT api_keys, api_endpoint, is_active, max_concurrent_per_key 
            FROM genai_config 
            WHERE id = 1 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$result || !$result['is_active']) {
            return [
                'available' => false,
                'api_keys' => [],
                'api_endpoint' => null,
                'max_concurrent' => 0
            ];
        }
        
        // Handle JSON or String format for API Keys
        $keys = json_decode($result['api_keys'], true);
        if (!is_array($keys)) {
             // Fallback: Split by new line if it's not JSON
             $keys = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $result['api_keys']))));
        }

        return [
            'available' => true,
            'api_keys' => $keys,
            'api_endpoint' => $result['api_endpoint'],
            'max_concurrent' => (int)$result['max_concurrent_per_key']
        ];
    }

    /**
     * ✅ CHECK USER ELIGIBILITY
     */
    public function canUseBackup($user_id, $current_credits, $estimated_cost = 0) {
        $config = $this->getApiConfig();
        if (!$config['available']) {
            return ['allowed' => false, 'message' => 'GenAI Backup is disabled.'];
        }
        
        if ($current_credits < $this->min_credits) {
            return ['allowed' => false, 'message' => 'Minimum ' . number_format($this->min_credits) . ' credits required.'];
        }
        
        // Check daily limit
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT credits_used FROM genai_daily_usage WHERE user_id = ? AND date = ?");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $usage = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $used = $usage ? (int)$usage['credits_used'] : 0;
        $remaining = $this->daily_limit - $used;
        
        if ($remaining <= 0 || $estimated_cost > $remaining) {
            return ['allowed' => false, 'message' => 'Daily backup limit exceeded.'];
        }
        
        return ['allowed' => true, 'api_keys' => $config['api_keys']];
    }

    /**
     * ✅ LOAD BALANCING: SELECT LEAST LOADED KEY
     */
    private function selectLeastLoadedKey() {
        $config = $this->getApiConfig();
        if (!$config['available'] || empty($config['api_keys'])) return false;

        $all_keys = $config['api_keys'];
        $max_load = $config['max_concurrent'];

        // Count current load for each key index (queued + processing)
        $stmt = $this->conn->prepare("
            SELECT api_key_index, COUNT(*) as count 
            FROM genai_queue 
            WHERE status IN ('queued', 'processing') AND api_key_index IS NOT NULL
            GROUP BY api_key_index
        ");
        $stmt->execute();
        $res = $stmt->get_result();
        
        $usage_map = [];
        while ($row = $res->fetch_assoc()) {
            $usage_map[$row['api_key_index']] = (int)$row['count'];
        }
        $stmt->close();

        // Algorithm: Find key with lowest load
        $selected = null;
        $min_count = PHP_INT_MAX;

        // Randomize order to distribute evenly when loads are equal
        $indices = array_keys($all_keys);
        shuffle($indices);

        foreach ($indices as $idx) {
            $load = $usage_map[$idx] ?? 0;
            
            // If key is not full and has less load than current minimum
            if ($load < $max_load && $load < $min_count) {
                $min_count = $load;
                $selected = [
                    'key' => $all_keys[$idx],
                    'index' => $idx,
                    'load' => $load,
                    'max' => $max_load
                ];
            }
        }
        
        if ($selected) {
            error_log("⚖️ Load Balancer: Selected Key #{$selected['index']} (Load: {$selected['load']}/{$selected['max']})");
        } else {
            error_log("⚠️ Load Balancer: ALL KEYS FULL!");
        }

        return $selected;
    }

    /**
     * ✅ ADD TASK TO QUEUE (Assign Key Immediately)
     */
    public function addToQueue($user_id, $task_data, $priority = 0) {
        // 1. Select Key immediately
        $key_info = $this->selectLeastLoadedKey();
        
        // If all keys full, we set NULL (Cron might retry later, or we reject)
        // Here we allow adding to queue but Cron handles assignment if NULL
        $used_key = $key_info ? $key_info['key'] : null;
        $key_idx  = $key_info ? $key_info['index'] : null;

        $task_json = json_encode($task_data);
        
        // 2. Insert into DB
        $stmt = $this->conn->prepare("
            INSERT INTO genai_queue (user_id, task_data, priority, status, used_api_key, api_key_index, created_at, updated_at) 
            VALUES (?, ?, ?, 'queued', ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("isisi", $user_id, $task_json, $priority, $used_key, $key_idx);
        
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            $stmt->close();
            return $id;
        }
        
        error_log("❌ AddQueue Error: " . $stmt->error);
        return false;
    }

    /**
     * ✅ PROCESS QUEUE (Triggered by Cron or Ajax)
     */
    public function processQueue() {
        $config = $this->getApiConfig();
        if (!$config['available']) return false;

        // 1. Fetch 1 Queued Task
        // Prioritize tasks that already have a Key assigned
        $stmt = $this->conn->prepare("
            SELECT id, user_id, task_data, used_api_key, api_key_index 
            FROM genai_queue 
            WHERE status = 'queued' 
            ORDER BY (used_api_key IS NOT NULL) DESC, priority DESC, created_at ASC 
            LIMIT 1 
            FOR UPDATE
        ");
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$task) return false; // No tasks

        $queue_id = $task['id'];
        $user_id = $task['user_id'];
        $task_data = json_decode($task['task_data'], true);
        
        // 2. Assign Key if missing (Retry Assignment)
        if (empty($task['used_api_key'])) {
            $key_info = $this->selectLeastLoadedKey();
            if (!$key_info) {
                // Still no key available -> Skip this run
                return false;
            }
            $api_key = $key_info['key'];
            $key_index = $key_info['index'];
            
            // Update DB with assigned key
            $update_stmt = $this->conn->prepare("UPDATE genai_queue SET used_api_key = ?, api_key_index = ? WHERE id = ?");
            $update_stmt->bind_param("sii", $api_key, $key_index, $queue_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            $api_key = $task['used_api_key'];
            $key_index = $task['api_key_index'];
        }

        // 3. Prepare API Request
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $webhook_url = "$protocol://" . $_SERVER['HTTP_HOST'] . "/ajaxs/genai_webhook.php";
        
        $post_data = [
            'text' => $task_data['text'],
            'voice_id' => $task_data['voice_id'],
            'model_id' => $task_data['model_id'] ?? 'eleven_multilingual_v2',
            'output_format' => 'mp3_44100_128',
            'webhook_url' => $webhook_url,
            'metadata' => [
                'queue_id' => $queue_id,
                'user_id' => $user_id
            ]
        ];
        
        // Settings for voice
        if (isset($task_data['voice_settings'])) {
             $post_data['voice_settings'] = $task_data['voice_settings'];
        } elseif (isset($task_data['stability'])) {
            $post_data['voice_settings'] = [
                'stability' => $task_data['stability'],
                'similarity_boost' => $task_data['similarity'] ?? 0.75,
                'style' => $task_data['style'] ?? 0,
                'use_speaker_boost' => $task_data['use_boost'] ?? true
            ];
        }

        // 4. Call GenAI API
        $ch = curl_init($config['api_endpoint'] . '/labs/text-to-speech');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);

        // 5. Handle Result
        if ($http_code === 200 && isset($result['task_id'])) {
            $genai_task_id = $result['task_id'];
            
            // Update Queue -> Processing
            $stmt = $this->conn->prepare("UPDATE genai_queue SET status = 'processing', genai_task_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $genai_task_id, $queue_id);
            $stmt->execute();
            
            // Update History for User
            $stmt = $this->conn->prepare("UPDATE tts_history SET genai_task_id = ?, status = 'pending' WHERE task_id = ?");
            $temp_id = 'queue_' . $queue_id;
            $stmt->bind_param("ss", $genai_task_id, $temp_id);
            $stmt->execute();
            
            error_log("✅ GenAI Task Sent! Queue: $queue_id | Key Index: $key_index");
            return true;
        } else {
            $error = $result['message'] ?? $result['error'] ?? 'Unknown API Error';
            $this->markQueueFailed($queue_id, "API Error ($http_code): $error");
            error_log("❌ GenAI Failed: $error | Key Index: $key_index");
            return false;
        }
    }

    private function markQueueFailed($queue_id, $error) {
        $stmt = $this->conn->prepare("UPDATE genai_queue SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $error, $queue_id);
        $stmt->execute();
    }
    
    public function updateDailyUsage($user_id, $credits_used) {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("
            INSERT INTO genai_daily_usage (user_id, date, credits_used, tasks_count) 
            VALUES (?, ?, ?, 1) 
            ON DUPLICATE KEY UPDATE credits_used = credits_used + ?, tasks_count = tasks_count + 1
        ");
        $stmt->bind_param("isii", $user_id, $today, $credits_used, $credits_used);
        $stmt->execute();
    }
}
?>