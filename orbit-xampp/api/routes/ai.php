<?php
// ─── AI ROUTES ───────────────────────────────────────
// GET  /api/ai/status
// POST /api/ai/chat

function handleAiStatus(): void {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'openai_api_key'");
    $stmt->execute();
    $row = $stmt->fetch();
    $hasKey = !empty($row['setting_value']);
    json(['configured' => $hasKey]);
}

function handleAiChat(): void {
    requireAuth();
    $body = getBody();

    $messages = $body['messages'] ?? [];
    if (empty($messages)) {
        jsonError('Thiếu messages');
    }

    // Lấy API key và model từ DB
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('openai_api_key','openai_model')");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $apiKey = $settings['openai_api_key'] ?? '';
    $model  = $settings['openai_model']  ?? 'gpt-4o-mini';

    if (!$apiKey) {
        jsonError('Chưa cấu hình OpenAI API Key. Vào Cài đặt để thêm API Key.', 503);
    }

    // System prompt tiếng Việt
    $systemMsg = [
        'role'    => 'system',
        'content' => 'Bạn là trợ lý AI của ORBIT — hệ thống lên lịch đăng video. Hỗ trợ người dùng tạo caption, hashtag, lên ý tưởng nội dung cho Facebook, TikTok, YouTube. Trả lời bằng tiếng Việt, ngắn gọn và hữu ích.',
    ];

    $payload = [
        'model'       => $model,
        'messages'    => array_merge([$systemMsg], $messages),
        'max_tokens'  => 2000,
        'temperature' => 0.7,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer $apiKey",
        ],
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) jsonError("Lỗi kết nối OpenAI: $err", 502);

    $data = json_decode($response, true);
    if ($status !== 200 || empty($data['choices'])) {
        $errMsg = $data['error']['message'] ?? 'Không nhận được phản hồi từ AI';
        jsonError($errMsg, $status >= 400 ? $status : 502);
    }

    json([
        'message' => $data['choices'][0]['message']['content'],
        'model'   => $data['model'] ?? $model,
        'usage'   => $data['usage'] ?? null,
    ]);
}
