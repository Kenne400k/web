<?php
$page_title = 'API Documentation - Studio';
require_once '../../config/header.php';
require_once '../../config/sidebar.php';

// Get user API Key & check refresh limit
$user_apikey = '';
$has_api_key = false;
$can_refresh_today = true;
$user_id = 0;

if (isset($_SESSION['Users']) && isset($mysqli)) {
    $user_identity = $_SESSION['Users'];
    
    // Get user info including apikey
    $stmt = $mysqli->prepare("SELECT id, apikey FROM Users WHERE taikhoan = ? OR google_id = ?");
    $stmt->bind_param("ss", $user_identity, $user_identity);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $user_id = $result['id'];
        
        // Check if apikey exists and is not NULL
        if (!empty($result['apikey']) && $result['apikey'] !== null) {
            $user_apikey = $result['apikey'];
            $has_api_key = true;
        }
        
        // Check if user can refresh today (1 refresh per day limit)
        $stmt2 = $mysqli->prepare("SELECT COUNT(*) as refresh_count FROM api_key_refresh_log 
                                   WHERE user_id = ? AND DATE(created_at) = CURDATE()");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $refresh_result = $stmt2->get_result()->fetch_assoc();
        
        if ($refresh_result && $refresh_result['refresh_count'] >= 1) {
            $can_refresh_today = false;
        }
        $stmt2->close();
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="/pages/AI/css/apidocs.css?v=<?php echo time(); ?>">

<div class="apidocs-container">
    <!-- API KEY SECTION -->
    <div class="apikey-section-wrapper">
        <label class="apikey-label" for="api_key">API Key</label>
        
        <fieldset class="apikey-controls">
            <?php if ($has_api_key): ?>
                <input 
                    class="apikey-input" 
                    id="api_key" 
                    type="password" 
                    value="<?php echo htmlspecialchars($user_apikey); ?>" 
                    readonly
                >
                
                <button class="btn-icon-action" id="btnToggleKey" onclick="toggleApiKeyVisibility()" title="Hiện/Ẩn API Key">
                    <i class="bi bi-eye"></i>
                </button>
                
                <button class="btn-icon-action" id="btnCopyKey" onclick="copyApiKey()" title="Copy API Key">
                    <i class="bi bi-clipboard"></i>
                </button>
                
                <button 
                    class="btn-refresh" 
                    id="btnRefresh"
                    onclick="regenerateApiKey()"
                    <?php echo !$can_refresh_today ? 'disabled' : ''; ?>
                    title="<?php echo !$can_refresh_today ? 'Đã refresh hôm nay' : 'Tạo lại API Key'; ?>"
                >
                    Refresh
                </button>
                
            <?php else: ?>
                <input 
                    class="apikey-input" 
                    id="api_key" 
                    type="text" 
                    placeholder="You need to buy premium credits to get API key" 
                    readonly
                >
                
                <button class="btn-icon-action" disabled title="Không có API Key">
                    <i class="bi bi-eye"></i>
                </button>
                
                <button class="btn-icon-action" disabled title="Không có API Key">
                    <i class="bi bi-clipboard"></i>
                </button>
                
                <button class="btn-refresh" onclick="generateFirstApiKey()">
                    Generate
                </button>
            <?php endif; ?>
        </fieldset>
        
        <?php if ($has_api_key && !$can_refresh_today): ?>
            <small class="refresh-warning">Đã dùng refresh hôm nay, vui lòng thử lại ngày mai</small>
        <?php endif; ?>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-wrapper">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="tab-elevenlabs">Elevenlabs</button>
            <button class="tab-btn" data-tab="tab-minimax">Minimax</button>
            <button class="tab-btn" data-tab="tab-proxy">Proxy</button>
            <button class="tab-btn" data-tab="tab-veo" disabled>Veo</button>
            <button class="tab-btn" data-tab="tab-common">Common</button>
        </div>
    </div>

    <!-- Tab Content: Elevenlabs -->
    <div id="tab-elevenlabs" class="tab-content active">
        
        <!-- 1. CREATE SPEECH (Text-to-Speech) -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">Create Speech (Text-to-Speech)</h3>
                <p class="endpoint-desc">Converts text into speech using a voice of your choice and returns audio.</p>
            </div>
            <div class="endpoint-body">
                <!-- Request -->
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl -X POST "https://api.kingcongstudio.com/v1/text-to-speech/$voice_id?output_format=mp3_44100_128" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  -d '{
  "text": "The first move is what sets everything in motion.",
  "model_id": "eleven_multilingual_v2",
  "with_transcript": false,
  "receive_url": "http://your-webhook-endpoint"
}'</code></pre>
                </div>

                <!-- Parameters -->
                <h4 class="section-heading">Parameters</h4>
                <div class="params-list">
                    <div class="param-item">
                        <span class="badge-param">voice_id</span>
                        <span class="badge-method">PATH</span>
                        Voice ID from List Voices endpoint
                    </div>
                    <div class="param-item">
                        <span class="badge-param">output_format</span>
                        <span class="badge-method">QUERY</span>
                        Audio format (mp3_44100_128, mp3_44100_192, etc.)
                    </div>
                    <div class="param-item">
                        <span class="badge-param">text</span>
                        <span class="badge-method">BODY</span>
                        Text content to convert to speech
                    </div>
                    <div class="param-item">
                        <span class="badge-param">model_id</span>
                        <span class="badge-method">BODY</span>
                        Model ID (eleven_multilingual_v2, eleven_monolingual_v1)
                    </div>
                    <div class="param-item">
                        <span class="badge-param">with_transcript</span>
                        <span class="badge-method">OPTIONAL</span>
                        Get transcript of the audio (true/false)
                    </div>
                    <div class="param-item">
                        <span class="badge-param">receive_url</span>
                        <span class="badge-method">OPTIONAL</span>
                        Webhook endpoint to receive audio file
                    </div>
                </div>

                <!-- Success Response -->
                <h4 class="section-heading">Success Response</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "success": true,
  "task_id": "uuid_task_id",
  "ec_remain_credits": 100
}</code></pre>
                </div>

                <!-- Webhook/Polling Response -->
                <h4 class="section-heading">Webhook Response (or GET Task polling)</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "done",
  "error_message": null,
  "credit_cost": 1,
  "metadata": {
    "audio_url": "https://example.com/audio.mp3",
    "srt_url": "https://example.com/audio.srt",
    "json_url": "https://example.com/audio.json"
  },
  "type": "tts"
}</code></pre>
                </div>

                <!-- Info Note -->
                <p class="info-note">
                    <i class="bi bi-info-circle"></i>
                    Read <a href="https://elevenlabs.io/docs/api-reference/text-to-speech" target="_blank" class="link-external">Elevenlabs documentation<i class="bi bi-box-arrow-up-right"></i></a> for more details (as Free user)
                </p>
            </div>
        </div>

        <!-- 2. DUB AN AUDIO FILE -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">Dub an Audio File</h3>
                <p class="endpoint-desc">Dubs a provided audio file into given language. Returns dubbed audio & transcript (srt).</p>
            </div>
            <div class="endpoint-body">
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl -X POST "https://api.kingcongstudio.com/v1/task/dubbing" \
  -H "xi-api-key: $API_KEY" \
  -H "Content-Type: multipart/form-data" \
  -F file=@audio.mp3 \
  -F num_speakers="0" \
  -F disable_voice_cloning="false" \
  -F source_lang="auto" \
  -F target_lang="vi" \
  -F receive_url="http://your-webhook-endpoint"</code></pre>
                </div>

                <h4 class="section-heading">Parameters</h4>
                <div class="params-list">
                    <div class="param-item">
                        <span class="badge-param">file</span>
                        <span class="badge-method">FILE</span>
                        Audio file (m4a, mp3) - Max: 20MB or 5 minutes
                        <i class="bi bi-question-circle help-icon" title="Supported formats: audio.m4a, audio.mp3"></i>
                    </div>
                    <div class="param-item">
                        <span class="badge-param">num_speakers</span>
                        <span class="badge-method">OPTIONAL</span>
                        Number of speakers (0 = auto detect) - Default: 0
                    </div>
                    <div class="param-item">
                        <span class="badge-param">disable_voice_cloning</span>
                        <span class="badge-method">OPTIONAL</span>
                        [BETA] Use similar voice from library instead of cloning - Default: true
                    </div>
                    <div class="param-item">
                        <span class="badge-param">source_lang</span>
                        <span class="badge-method">OPTIONAL</span>
                        Source language ("auto" for auto-detect) - Default: "auto"
                    </div>
                    <div class="param-item">
                        <span class="badge-param">target_lang</span>
                        <span class="badge-method">REQUIRED</span>
                        Target language to dub into (vi, en, es, fr, de, ja, ko, zh, etc.)
                    </div>
                    <div class="param-item">
                        <span class="badge-param">receive_url</span>
                        <span class="badge-method">OPTIONAL</span>
                        Webhook endpoint to receive result when done
                    </div>
                </div>

                <h4 class="section-heading">Success Response</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "success": true,
  "task_id": "uuid_task_id",
  "ec_remain_credits": 95
}</code></pre>
                </div>

                <h4 class="section-heading">Webhook Response (or GET Task polling)</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "done",
  "error_message": null,
  "credit_cost": 5,
  "metadata": {
    "audio_url": "https://example.com/dubbed_audio.mp3",
    "srt_url": "https://example.com/transcript.srt",
    "json_url": "https://example.com/transcript.json"
  },
  "type": "dubbing"
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 3. SPEECH TO TEXT -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">Speech to Text (Transcription)</h3>
                <p class="endpoint-desc">Transcribes a provided audio file and returns transcript as JSON and SRT.</p>
            </div>
            <div class="endpoint-body">
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl -X POST "https://api.kingcongstudio.com/v1/task/speech-to-text" \
  -H "xi-api-key: $API_KEY" \
  -H "Content-Type: multipart/form-data" \
  -F file=@audio.mp3 \
  -F receive_url="http://your-webhook-endpoint"</code></pre>
                </div>

                <h4 class="section-heading">Parameters</h4>
                <div class="params-list">
                    <div class="param-item">
                        <span class="badge-param">file</span>
                        <span class="badge-method">FILE</span>
                        Audio file - Max: 200MB
                        <i class="bi bi-question-circle help-icon" title="Supported: mp3, aac, aiff, ogg, opus, wav, webm, flac, m4a"></i>
                    </div>
                    <div class="param-item">
                        <span class="badge-param">receive_url</span>
                        <span class="badge-method">OPTIONAL</span>
                        Webhook endpoint to receive transcript when done
                    </div>
                </div>

                <h4 class="section-heading">Success Response</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "success": true,
  "task_id": "uuid_task_id",
  "ec_remain_credits": 98
}</code></pre>
                </div>

                <h4 class="section-heading">Webhook Response (or GET Task polling)</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "done",
  "error_message": null,
  "credit_cost": 2,
  "metadata": {
    "json_url": "https://example.com/transcript.json",
    "srt_url": "https://example.com/transcript.srt"
  },
  "type": "speech_to_text"
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 4. LIST MODELS -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">List Models</h3>
                <p class="endpoint-desc">Retrieve available voice synthesis models.</p>
            </div>
            <div class="endpoint-body">
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl "https://api.kingcongstudio.com/v1/models" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY"</code></pre>
                </div>

                <h4 class="section-heading">Success Response</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>{
  "models": [
    {
      "model_id": "eleven_multilingual_v2",
      "name": "Eleven Multilingual v2",
      "can_be_finetuned": false,
      "can_do_text_to_speech": true,
      "can_do_voice_conversion": true,
      "languages": ["en", "es", "fr", "de", "it", "pt", "pl", "hi", "ar", ...]
    },
    {
      "model_id": "eleven_monolingual_v1",
      "name": "Eleven English v1",
      "can_be_finetuned": false,
      "can_do_text_to_speech": true,
      "can_do_voice_conversion": false,
      "languages": ["en"]
    }
  ]
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 5. LIST RECOMMENDED VOICES -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">List Recommended Voices</h3>
                <p class="endpoint-desc">Gets a list of all available voices for a user with search, filtering and pagination (as free user).</p>
            </div>
            <div class="endpoint-body">
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl "https://api.kingcongstudio.com/v2/voices" \
  -H "xi-api-key: $API_KEY"</code></pre>
                </div>

                <p class="info-note">
                    <i class="bi bi-info-circle"></i>
                    Read <a href="https://elevenlabs.io/docs/api-reference/get-voices" target="_blank" class="link-external">Elevenlabs documentation<i class="bi bi-box-arrow-up-right"></i></a> for response format and filtering options
                </p>
            </div>
        </div>

        <!-- 6. LIST SHARED VOICES -->
        <div class="endpoint-card">
            <div class="endpoint-header">
                <h3 class="endpoint-title">List Shared Voices</h3>
                <p class="endpoint-desc">Retrieves a list of shared voices (as free user).</p>
            </div>
            <div class="endpoint-body">
                <h4 class="section-heading">Request</h4>
                <div class="code-block">
                    <button class="btn-copy-code" onclick="copyCode(this)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <pre><code>curl "https://api.kingcongstudio.com/v1/shared-voices" \
  -H "xi-api-key: $API_KEY"</code></pre>
                </div>

                <p class="info-note">
                    <i class="bi bi-info-circle"></i>
                    Read <a href="https://elevenlabs.io/docs/api-reference/get-shared-voices" target="_blank" class="link-external">Elevenlabs documentation<i class="bi bi-box-arrow-up-right"></i></a> for response format
                </p>
            </div>
        </div>

    </div>

    <!-- TAB CONTENT - MINIMAX -->
<div class="tab-content" id="tab-minimax">
    
    <!-- 1. GET COMMON CONFIG -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Get Common Configuration</h3>
            <p class="endpoint-desc">Retrieves common configuration settings from Minimax service (model, language support, etc.)</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1m/common/config" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>
        </div>
    </div>

    <!-- 2. TEXT-TO-SPEECH TASK -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Text-to-Speech Task</h3>
            <p class="endpoint-desc">Converts text to speech using Minimax TTS service. Supports both synchronous and asynchronous processing.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1m/task/text-to-speech" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  -d '{
  "text": "Hello, this is a test message for text-to-speech conversion.",
  "model": "speech-2.6-hd",
  "voice_setting": {
    "voice_id": "209533299589184", 
    "vol": 1, 
    "pitch": 0, 
    "speed": 1 
  },
  "language_boost": "Auto",
  "with_transcript": false,
  "receive_url": "http://your-webhook-endpoint"
}'</code></pre>
            </div>

            <h4 class="section-heading">Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">text</span>
                    <span class="badge-method">REQUIRED</span>
                    Text to convert to speech
                </div>
                <div class="param-item">
                    <span class="badge-param">model</span>
                    <span class="badge-method">REQUIRED</span>
                    TTS model (turbo models cost 0.6x credits)
                </div>
                <div class="param-item">
                    <span class="badge-param">voice_setting.voice_id</span>
                    <span class="badge-method">REQUIRED</span>
                    Your Voice ID
                </div>
                <div class="param-item">
                    <span class="badge-param">language_boost</span>
                    <span class="badge-method">OPTIONAL</span>
                    Language boost - Default: Auto
                </div>
                <div class="param-item">
                    <span class="badge-param">voice_setting.speed</span>
                    <span class="badge-method">OPTIONAL</span>
                    Speed - Default: 1 (Min: 0.01, Max: 10.00)
                </div>
                <div class="param-item">
                    <span class="badge-param">voice_setting.pitch</span>
                    <span class="badge-method">OPTIONAL</span>
                    Pitch - Default: 0 (Min: -12, Max: 12)
                </div>
                <div class="param-item">
                    <span class="badge-param">voice_setting.vol</span>
                    <span class="badge-method">OPTIONAL</span>
                    Volume - Default: 1 (Min: 0.5, Max: 2.0)
                </div>
                <div class="param-item">
                    <span class="badge-param">receive_url</span>
                    <span class="badge-method">OPTIONAL</span>
                    Webhook URL to receive the audio file
                </div>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "task_id": "uuid_task_id",
  "ec_remain_credits": 100
}</code></pre>
            </div>

            <h4 class="section-heading">Webhook Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "done",
  "error_message": null,
  "credit_cost": 1,
  "metadata": {
    "audio_url": "https://example.com/audio.mp3",
    "srt_url": "https://example.com/audio.srt"
  },
  "type": "minimax_tts"
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 3. CLONE VOICE -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Clone Voice</h3>
            <p class="endpoint-desc">Uploads an audio file to create a custom voice clone.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1m/voice/clone" \
  -H "xi-api-key: $API_KEY" \
  -H "Content-Type: multipart/form-data" \
  -F file=@audio_sample.mp3 \
  -F voice_name="My Voice" \
  -F preview_text="Hello world" \
  -F language_tag="English" \
  -F need_noise_reduction=true \
  -F gender_tag="male"</code></pre>
            </div>

            <h4 class="section-heading">Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">file</span>
                    <span class="badge-method">FILE</span>
                    Audio file (mp3) - Max: 20MB or 5 minutes
                </div>
                <div class="param-item">
                    <span class="badge-param">voice_name</span>
                    <span class="badge-method">REQUIRED</span>
                    Name for the cloned voice
                </div>
                <div class="param-item">
                    <span class="badge-param">preview_text</span>
                    <span class="badge-method">REQUIRED</span>
                    Preview text for testing
                </div>
                <div class="param-item">
                    <span class="badge-param">language_tag</span>
                    <span class="badge-method">REQUIRED</span>
                    Language (e.g. "English")
                </div>
                <div class="param-item">
                    <span class="badge-param">need_noise_reduction</span>
                    <span class="badge-method">OPTIONAL</span>
                    Remove noise from audio (true/false)
                </div>
                <div class="param-item">
                    <span class="badge-param">gender_tag</span>
                    <span class="badge-method">REQUIRED</span>
                    Gender: "male" or "female"
                </div>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "cloned_voice_id": 12345
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 4. DELETE VOICE CLONE -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Delete Voice Clone</h3>
            <p class="endpoint-desc">Deletes a voice clone and all its references.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X DELETE "https://api.kingcongstudio.com/v1m/voice/clone/$voice_clone_id" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 5. LIST VOICE CLONES -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">List Voice Clones</h3>
            <p class="endpoint-desc">Retrieves all voice clones owned by the authenticated user.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1m/voice/clone" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>
        </div>
    </div>

    <!-- 6. GET VOICE LIST -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Get Voice List</h3>
            <p class="endpoint-desc">Retrieves available voices from Minimax service.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1m/voice/list" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  -d '{
  "page": 1,
  "page_size": 30,
  "tag_list": []
}'</code></pre>
            </div>

            <h4 class="section-heading">Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">page</span>
                    <span class="badge-method">OPTIONAL</span>
                    Page number - Default: 1
                </div>
                <div class="param-item">
                    <span class="badge-param">page_size</span>
                    <span class="badge-method">OPTIONAL</span>
                    Items per page - Default: 30
                </div>
                <div class="param-item">
                    <span class="badge-param">tag_list</span>
                    <span class="badge-method">OPTIONAL</span>
                    Filter tags (language, accent, gender, age)
                </div>
            </div>
        </div>
    </div>

    <!-- 7. MUSIC GENERATION -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Music Generation</h3>
            <p class="endpoint-desc">Generates music using Minimax AI music generation service. Supports both idea-based and lyrics-based generation.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1m/task/music-generation" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  -d '{
  "title": "My Song Title",
  "idea": "A relaxing jazz piece for a quiet evening",
  "n": 1,
  "style_id": "8",
  "mood_id": "5",
  "scenario_id": "5",
  "rewrite_idea_switch": false,
  "receive_url": "http://your-webhook-endpoint"
}'</code></pre>
            </div>

            <h4 class="section-heading">Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">title</span>
                    <span class="badge-method">OPTIONAL</span>
                    Song title (max 40 characters)
                </div>
                <div class="param-item">
                    <span class="badge-param">idea</span>
                    <span class="badge-method">REQUIRED</span>
                    Music idea/description (min 20, max 300 characters)
                </div>
                <div class="param-item">
                    <span class="badge-param">lyrics</span>
                    <span class="badge-method">OPTIONAL</span>
                    Song lyrics (min 50, max 3000 characters)
                </div>
                <div class="param-item">
                    <span class="badge-param">style_id</span>
                    <span class="badge-method">OPTIONAL</span>
                    Music style ID (1-18): Pop, Rock, Jazz, etc.
                </div>
                <div class="param-item">
                    <span class="badge-param">mood_id</span>
                    <span class="badge-method">OPTIONAL</span>
                    Music mood ID (1-11): Relaxed, Happy, Sad, etc.
                </div>
                <div class="param-item">
                    <span class="badge-param">scenario_id</span>
                    <span class="badge-method">OPTIONAL</span>
                    Music scenario ID (1-10): Coffee shop, Travel, etc.
                </div>
                <div class="param-item">
                    <span class="badge-param">n</span>
                    <span class="badge-method">OPTIONAL</span>
                    Number of tracks (default: 1, min: 1, max: 3)
                </div>
                <div class="param-item">
                    <span class="badge-param">rewrite_idea_switch</span>
                    <span class="badge-method">OPTIONAL</span>
                    Rewrite idea for better results (default: false)
                </div>
                <div class="param-item">
                    <span class="badge-param">receive_url</span>
                    <span class="badge-method">OPTIONAL</span>
                    Webhook URL to receive generated music
                </div>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "task_id": "uuid_task_id",
  "ec_remain_credits": 100
}</code></pre>
            </div>

            <h4 class="section-heading">Webhook Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "done",
  "error_message": null,
  "credit_cost": 1,
  "metadata": {
    "audio_url": [
      "https://example.com/music_track_1.mp3",
      "https://example.com/music_track_2.mp3"
    ],
    "cover_url": [
      "https://example.com/cover_1.jpg",
      "https://example.com/cover_2.jpg"
    ]
  },
  "type": "minimax_music"
}</code></pre>
            </div>
        </div>
    </div>

</div>

    <!-- TAB CONTENT - PROXY -->
<div class="tab-content" id="tab-proxy">
    
    <!-- 1. PURCHASE PROXY -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Purchase Proxy</h3>
            <p class="endpoint-desc">Purchase proxy by quantity and TTL (minutes).</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1p/proxy/purchase" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  -d '{
  "ttl": 10,
  "quantity": 5
}'</code></pre>
            </div>

            <h4 class="section-heading">Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">ttl</span>
                    <span class="badge-method">REQUIRED</span>
                    Time to live: 5 or 10 minutes
                </div>
                <div class="param-item">
                    <span class="badge-param">quantity</span>
                    <span class="badge-method">REQUIRED</span>
                    Quantity to buy
                </div>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "data": {
    "purchased_proxies": [
      {
        "id": 1,
        "proxy": "http://user:pass@host:port",
        "socks5": {
          "host": "host",
          "port": 1080,
          "username": "user",
          "password": "pass"
        },
        "ttl_minutes": 60,
        "credit_cost": 1250,
        "expires_at": "2025-01-01T00:00:00.000Z"
      }
    ],
    "total_cost": 6250,
    "total_purchased": 5,
    "requested_quantity": 5,
    "refunded_credits": 0,
    "unavailable_proxies": 0
  }
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 2. YOUR PURCHASED PROXIES -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Your Purchased Proxies</h3>
            <p class="endpoint-desc">List of your purchased proxies.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1p/proxy/purchases" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "data": {
    "purchases": [
      {
        "id": 1,
        "proxy": "http://user:pass@host:port",
        "socks5": {
          "host": "host",
          "port": 1080,
          "username": "user",
          "password": "pass"
        },
        "ttl_minutes": 10,
        "credit_cost": 1250,
        "expires_at": "2025-01-01T00:00:00.000Z"
      }
    ],
    "total": 1
  }
}</code></pre>
            </div>
        </div>
    </div>

</div>

<!-- TAB CONTENT - COMMON -->
<div class="tab-content" id="tab-common">
    
    <!-- 1. GET TASK -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Get Task</h3>
            <p class="endpoint-desc">Retrieves a task detail by task id. For polling if you don't use webhook.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1/task/$task_id" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "id": "uuid_task_id",
  "created_at": "2025-01-01T00:00:00.000Z",
  "status": "doing",
  "error_message": null,
  "credit_cost": 1,
  "metadata": {
    "audio_url": "https://example.com/audio.mp3",
    "srt_url": "https://example.com/audio.srt",
    "json_url": "https://example.com/audio.json"
  },
  "progress": 60,
  "type": "tts"
}</code></pre>
            </div>

            <p class="info-note">
                <i class="bi bi-info-circle"></i>
                <span><strong>Status values:</strong> "doing", "done", "error" | <strong>Progress:</strong> 0-100</span>
            </p>
        </div>
    </div>

    <!-- 2. LIST TASKS -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">List Tasks</h3>
            <p class="endpoint-desc">List current user's tasks with pagination and optional type filter.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1/tasks?page=1&limit=20&type=tts" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Query Parameters</h4>
            <div class="params-list">
                <div class="param-item">
                    <span class="badge-param">page</span>
                    <span class="badge-method">OPTIONAL</span>
                    Page number (default: 1)
                </div>
                <div class="param-item">
                    <span class="badge-param">limit</span>
                    <span class="badge-method">OPTIONAL</span>
                    Items per page: 1-100 (default: 20)
                </div>
                <div class="param-item">
                    <span class="badge-param">type</span>
                    <span class="badge-method">OPTIONAL</span>
                    Task type filter (tts, dubbing, minimax_tts, etc.)
                </div>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "data": [
    {
      "id": "uuid_task_id",
      "created_at": "2025-01-01T00:00:00.000Z",
      "status": "doing",
      "error_message": null,
      "credit_cost": 1,
      "metadata": {
        "audio_url": "https://example.com/audio.mp3",
        "srt_url": "https://example.com/audio.srt"
      },
      "type": "tts",
      "progress": 60
    }
  ],
  "page": 1,
  "limit": 20,
  "total": 42
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 3. DELETE TASK -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Delete Task</h3>
            <p class="endpoint-desc">Delete tasks and get refund (if available).</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl -X POST "https://api.kingcongstudio.com/v1/task/delete" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY" \
  --data-raw '{"task_ids":["uuid_task_id"]}'</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "refund_credits": 100
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 4. GET CREDITS -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Get Credits</h3>
            <p class="endpoint-desc">Returns total available credits of current user.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1/credits" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "credits": 123
}</code></pre>
            </div>
        </div>
    </div>

    <!-- 5. HEALTH CHECK -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <h3 class="endpoint-title">Health Check</h3>
            <p class="endpoint-desc">Check service health/config status.</p>
        </div>
        <div class="endpoint-body">
            <h4 class="section-heading">Request</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>curl "https://api.kingcongstudio.com/v1/health-check" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $API_KEY"</code></pre>
            </div>

            <h4 class="section-heading">Success Response</h4>
            <div class="code-block">
                <button class="btn-copy-code" onclick="copyCode(this)">
                    <i class="bi bi-clipboard"></i>
                </button>
                <pre><code>{
  "success": true,
  "data": {
    "elevenlabs": "good",
    "minimax": "good"
  }
}</code></pre>
            </div>

            <p class="info-note">
                <i class="bi bi-info-circle"></i>
                <span><strong>Status values:</strong> "good", "degraded", "overloaded"</span>
            </p>
        </div>
    </div>

</div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span class="toast-text"></span>
</div>

<!-- QUAN TRỌNG: Load jQuery TRƯỚC -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Sau đó mới load script và variables -->
<script>
    const hasApiKey = <?php echo $has_api_key ? 'true' : 'false'; ?>;
    const userApiKey = <?php echo json_encode($user_apikey); ?>;
    const canRefreshToday = <?php echo $can_refresh_today ? 'true' : 'false'; ?>;
    const userId = <?php echo $user_id; ?>;
</script>

<!-- Load apidocs.js SAU CÙNG -->
<script src="/pages/AI/js/apidocs.js?v=<?php echo time(); ?>"></script>

<?php require_once '../../config/footer.php'; ?>