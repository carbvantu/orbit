<?php
// ─── TIKTOK DOWNLOADER ───────────────────────────────
// POST /api/tiktok/fetch   — tải 1 video
// POST /api/tiktok/batch   — tải nhiều video (max 50)

function fetchTikTokVideo(string $url): array {
    if (!preg_match('#(tiktok\.com|douyin\.com|vm\.tiktok\.com|vt\.tiktok\.com)#i', $url)) {
        return ['success' => false, 'error' => 'URL không hợp lệ. Chỉ hỗ trợ TikTok và Douyin.'];
    }

    $ch = curl_init('https://tikwm.com/api/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['url' => $url, 'hd' => 1]),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 ORBIT/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'error' => "Lỗi kết nối: $err"];
    }

    $data = json_decode($response, true);
    if (!$data || ($data['code'] ?? -1) !== 0) {
        $msg = $data['msg'] ?? 'Không thể tải video này';
        return ['success' => false, 'error' => $msg];
    }

    $v = $data['data'];
    return [
        'success'     => true,
        'title'       => $v['title'] ?? '',
        'author'      => $v['author']['nickname'] ?? '',
        'authorId'    => $v['author']['unique_id'] ?? '',
        'cover'       => $v['cover'] ?? '',
        'duration'    => $v['duration'] ?? 0,
        'music'       => $v['music_info']['title'] ?? '',
        'downloadUrl' => $v['hdplay'] ?? $v['play'] ?? '',
        'sourceUrl'   => $url,
    ];
}

function handleTikTokFetch(): void {
    requireAuth();
    $body = getBody();
    $url  = trim($body['url'] ?? '');
    if (!$url) jsonError('URL là bắt buộc');

    json(fetchTikTokVideo($url));
}

function handleTikTokBatch(): void {
    requireAuth();
    $body = getBody();
    $urls = $body['urls'] ?? [];

    if (empty($urls) || !is_array($urls)) {
        jsonError('Cần cung cấp danh sách URLs');
    }
    if (count($urls) > 50) {
        jsonError('Tối đa 50 URL mỗi lần');
    }

    $results = [];
    foreach ($urls as $url) {
        $results[] = fetchTikTokVideo(trim($url));
        // Tránh rate limit
        usleep(300000); // 300ms
    }

    json([
        'total'     => count($results),
        'success'   => count(array_filter($results, fn($r) => $r['success'])),
        'failed'    => count(array_filter($results, fn($r) => !$r['success'])),
        'results'   => $results,
    ]);
}
