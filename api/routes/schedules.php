<?php
// ─── SCHEDULES ROUTES ────────────────────────────────
// GET    /api/schedules
// POST   /api/schedules
// PUT    /api/schedules/:id
// DELETE /api/schedules/:id

function handleListSchedules(): void {
    requireAuth();
    $db     = getDB();
    $status = $_GET['status'] ?? null;
    $limit  = min((int)($_GET['limit'] ?? 50), 200);

    $sql  = "
        SELECT s.*, v.title as video_title, v.file_path,
               p.platform_type, p.account_name
        FROM schedules s
        JOIN videos v ON s.video_id = v.id
        JOIN platforms p ON s.platform_id = p.id
    ";
    $params = [];
    if ($status) {
        $sql    .= " WHERE s.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY s.scheduled_at DESC LIMIT $limit";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json($stmt->fetchAll());
}

function handleCreateSchedule(): void {
    $user = requireAuth();
    $body = getBody();
    $db   = getDB();

    $videoId     = (int)($body['videoId'] ?? 0);
    $platformIds = $body['platformIds'] ?? [];
    $scheduledAt = $body['scheduledAt'] ?? '';

    if (!$videoId || empty($platformIds) || !$scheduledAt) {
        jsonError('videoId, platformIds và scheduledAt là bắt buộc');
    }

    $stmt = $db->prepare("SELECT id, title FROM videos WHERE id = ? AND status = 'active'");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch();
    if (!$video) jsonError('Không tìm thấy video', 404);

    $created = [];
    foreach ($platformIds as $platformId) {
        $pStmt = $db->prepare("SELECT id, platform_type FROM platforms WHERE id = ? AND is_active = 1");
        $pStmt->execute([$platformId]);
        $platform = $pStmt->fetch();
        if (!$platform) continue;

        $ins = $db->prepare("
            INSERT INTO schedules (video_id, platform_id, post_title, post_caption, scheduled_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $videoId,
            $platformId,
            $body['postTitle']   ?? $video['title'],
            $body['postCaption'] ?? null,
            date('Y-m-d H:i:s', strtotime($scheduledAt)),
        ]);
        $sid  = (int)$db->lastInsertId();
        $row  = $db->query("SELECT s.*, v.title as video_title, p.platform_type, p.account_name FROM schedules s JOIN videos v ON s.video_id=v.id JOIN platforms p ON s.platform_id=p.id WHERE s.id=$sid")->fetch();
        $created[] = $row;

        logActivity('schedule_created', "Lên lịch video '{$video['title']}' lên {$platform['platform_type']}", 'schedule', $sid, $user['id']);
    }

    json($created, 201);
}

function handleUpdateSchedule(int $id): void {
    $user = requireAuth();
    $body = getBody();
    $db   = getDB();

    $stmt = $db->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) jsonError('Không tìm thấy lịch', 404);

    $fields = [];
    $values = [];

    if (isset($body['status'])) {
        if (!in_array($body['status'], ['pending','published','failed','cancelled'])) {
            jsonError('Trạng thái không hợp lệ');
        }
        $fields[] = 'status = ?';
        $values[] = $body['status'];
        if ($body['status'] === 'published') {
            $fields[] = 'published_at = NOW()';
        }
    }
    if (isset($body['scheduledAt'])) {
        $fields[] = 'scheduled_at = ?';
        $values[] = date('Y-m-d H:i:s', strtotime($body['scheduledAt']));
    }
    if (isset($body['postCaption'])) {
        $fields[] = 'post_caption = ?';
        $values[] = $body['postCaption'];
    }
    if (isset($body['errorMessage'])) {
        $fields[] = 'error_message = ?';
        $values[] = $body['errorMessage'];
    }

    if (!$fields) jsonError('Không có dữ liệu cập nhật');
    $fields[] = 'updated_at = NOW()';
    $values[] = $id;
    $db->prepare("UPDATE schedules SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);

    logActivity('schedule_updated', "Cập nhật lịch ID $id", 'schedule', $id, $user['id']);
    $updated = $db->query("SELECT s.*, v.title as video_title, p.platform_type, p.account_name FROM schedules s JOIN videos v ON s.video_id=v.id JOIN platforms p ON s.platform_id=p.id WHERE s.id=$id")->fetch();
    json($updated);
}

function handleDeleteSchedule(int $id): void {
    $user = requireAuth();
    $db   = getDB();

    $stmt = $db->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if (!$s) jsonError('Không tìm thấy lịch', 404);

    $db->prepare("UPDATE schedules SET status='cancelled', updated_at=NOW() WHERE id = ?")->execute([$id]);
    logActivity('schedule_cancelled', "Hủy lịch ID $id", 'schedule', $id, $user['id']);
    json(['message' => 'Đã hủy lịch']);
}
