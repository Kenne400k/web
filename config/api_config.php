<?php
/**
 * API Configuration File
 */

// --- API GỐC CỦA BẠN ---
define('API_URL', 'https://1dg.me/api/v2');
define('API_KEY', 'b1dcb7f656ec8ab31b7fdd7e565a7063');
define('API_TIMEOUT', 30);
define('API_SSL_VERIFY', false);

// --- CẤU HÌNH MỚI CHO TTS (TEXT TO SPEECH) ---
define('TTS_API_ENDPOINT', 'https://api.ai33.pro');
// Key TTS bạn cung cấp:
define('TTS_API_KEY', 'sk_vsp5gp7hop9k0i90o1ivls80tm2tqtre1q7bkst1fgwobypu');

// --- CẤU HÌNH KINGCONG TTS (maziao.com) ---
define('KINGCONG_API_ENDPOINT', 'https://app.maziao.com/api');
define('KINGCONG_API_KEY', 'mz_zAZN2H6WQPesAIwx__euGXXrUvbjWVQ0'); // Thay bằng API key thật 

// Danh sách giọng (Lấy từ JSON bạn gửi)
$tts_voices = [
    'Rachel' => ['id' => '21m00Tcm4TlvDq8ikWAM', 'gender' => 'Female', 'type' => 'American, Casual'],
    'Domi'   => ['id' => 'AZnzlk1XvdvUeBnXmlld', 'gender' => 'Female', 'type' => 'Strong'],
    'Aria'   => ['id' => '9BWtsMINqrJLrRacOk9x', 'gender' => 'Female', 'type' => 'Husky'],
    'Alex'   => ['id' => 'yl2ZDV1MzN4HbQJbMihG', 'gender' => 'Male',   'type' => 'American, Upbeat'],
    'Drew'   => ['id' => '29vD33N1CtxCmqQRPOHJ', 'gender' => 'Male',   'type' => 'News'],
    'Clyde'  => ['id' => '2EiwWnXFnvU5JabPnv8n', 'gender' => 'Male',   'type' => 'Deep'],
    'Paul'   => ['id' => '5Q0t7uMcjvnagumLfvZi', 'gender' => 'Male',   'type' => 'Authoritative'],
    'Dave'   => ['id' => 'CYw3kZ02Hs0563khs1Fj', 'gender' => 'Male',   'type' => 'British'],
    'Fin'    => ['id' => 'D38z5RcWu1voky8WS1ja', 'gender' => 'Male',   'type' => 'Irish, Old'],
];
?>