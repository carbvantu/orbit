<?php
// ─── PLATFORMS ROUTES ────────────────────────────────
// GET    /api/platforms
// POST   /api/platforms
// PUT    /api/platforms/:id
// DELETE /api/platforms/:id

function handleListPlatforms(): void {
    requireAuth();
    $db = getDB();
    $rows = $db->query("
        SELECT p.*,
            (SELECT COUNT(*) FROM schedules s WHERE s.platform_id = p.id AND s.status = 'pending') as pending_count,
            (SELECT COUNT(*) FROM schedules s WHERE s.platform_id = p.id AND s.status = 'published') as published_count
        FROM platforms p
        ORDER BY p.created_at DESC
    ")->fetchAll();
    json($rows);
}

function handleCreatePlatform(): void {
    $user = requireAuth();
    $body = getBody();

    $type = $body['platformType'] ?? '';
    if (!in_array($type, ['facebook', 'tiktok', 'youtube'])) {
        jsonError('Platform không hợp lệ. Chỉ hỗ trợ: facebook, tiktok, youtube');
    }

    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO platforms (platform_type, account_name, account_id, access_token)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $type,
        $body['accountName'] ?? null,
        $body['accountId']   ?? null,
        $body['accessToken'] ?? null,
    ]);
    $id = (int)$db->lastInsertId();
    $row = $db->query("SELECT * FROM platforms WHERE id = $id")->fetch();

    logActivity('platform_added', "Kết nối nền tảng: $type", 'platform', $id, $user['id']);
    json($row, 201);
}

function handleUpdatePlatform(int $id): void {
    $user = requireAuth();
    $body = getBody();
    $db   = getDB();

    $stmt = $db->prepare("SELECT * FROM platforms WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) jsonError('Không tìm thấy platform', 404);

    $fields = [];
    $values = [];
    $allowed = ['account_name' => 'accountName', 'account_id' => 'accountId', 'access_token' => 'accessToken', 'is_active' => 'isActive'];
    foreach ($allowed as $col => $key) {
        if (array_key_exists($key, $body)) {
            $fields[] = "$col = ?";
            $values[] = $body[$key];
        }
    }
    if (!$fields) jsonError('Không có dữ liệu cập nhật');

    $fields[] = 'updated_at = NOW()';
    $values[] = $id;
    $db->prepare("UPDATE platforms SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);

    logActivity('platform_updated', "Cập nhật platform ID $id", 'platform', $id, $user['id']);
    $updated = $db->query("SELECT * FROM platforms WHERE id = $id")->fetch();
    json($updated);
}

function handleDeletePlatform(int $id): void {
    $user = requireAuth();
    $db   = getDB();

    $stmt = $db->prepare("SELECT platform_type FROM platforms WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) jsonError('Không tìm thấy platform', 404);

    $db->prepare("DELETE FROM platforms WHERE id = ?")->execute([$id]);
    logActivity('platform_removed', "Xóa platform: {$p['platform_type']}", 'platform', $id, $user['id']);
    json(['message' => 'Đã xóa platform']);
}
