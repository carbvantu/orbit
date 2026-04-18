<?php
// ─── VIDEOS ROUTES ───────────────────────────────────
// GET    /api/videos
// POST   /api/videos
// GET    /api/videos/:id
// PUT    /api/videos/:id
// DELETE /api/videos/:id

function handleListVideos(): void {
    requireAuth();
    $db    = getDB();
    $rows  = $db->query("SELECT * FROM videos WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
    json($rows);
}

function handleCreateVideo(): void {
    $user = requireAuth();
    $body = getBody();

    $title    = trim($body['title'] ?? '');
    $filePath = trim($body['filePath'] ?? '');
    if (!$title || !$filePath) {
        jsonError('Tiêu đề và đường dẫn file là bắt buộc');
    }

    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO videos (title, description, file_path, tags)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $title,
        $body['description'] ?? null,
        $filePath,
        isset($body['tags']) ? (is_array($body['tags']) ? implode(',', $body['tags']) : $body['tags']) : null,
    ]);
    $id = (int)$db->lastInsertId();
    $video = $db->query("SELECT * FROM videos WHERE id = $id")->fetch();

    logActivity('video_added', "Thêm video: $title", 'video', $id, $user['id']);
    json($video, 201);
}

function handleGetVideo(int $id): void {
    requireAuth();
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    if (!$video) jsonError('Không tìm thấy video', 404);
    json($video);
}

function handleUpdateVideo(int $id): void {
    $user = requireAuth();
    $body = getBody();
    $db   = getDB();

    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    if (!$video) jsonError('Không tìm thấy video', 404);

    $title    = trim($body['title'] ?? $video['title']);
    $desc     = $body['description'] ?? $video['description'];
    $filePath = trim($body['filePath'] ?? $video['file_path']);
    $tags     = isset($body['tags'])
        ? (is_array($body['tags']) ? implode(',', $body['tags']) : $body['tags'])
        : $video['tags'];

    $db->prepare("UPDATE videos SET title=?, description=?, file_path=?, tags=?, updated_at=NOW() WHERE id=?")
       ->execute([$title, $desc, $filePath, $tags, $id]);

    logActivity('video_updated', "Cập nhật video: $title", 'video', $id, $user['id']);
    $updated = $db->query("SELECT * FROM videos WHERE id = $id")->fetch();
    json($updated);
}

function handleDeleteVideo(int $id): void {
    $user = requireAuth();
    $db   = getDB();

    $stmt = $db->prepare("SELECT title FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    if (!$video) jsonError('Không tìm thấy video', 404);

    $db->prepare("UPDATE videos SET status='archived', updated_at=NOW() WHERE id=?")->execute([$id]);
    logActivity('video_deleted', "Xóa video: {$video['title']}", 'video', $id, $user['id']);
    json(['message' => 'Đã xóa video']);
}
