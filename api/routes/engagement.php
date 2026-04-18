<?php
// =====================================================
// ORBIT — Engagement: Comments / Auto-Reply / Templates
// =====================================================

function handleListComments() {
    requireAuth();
    $db = getDb();
    $platform = $_GET['platform'] ?? null;
    $replied  = $_GET['isReplied'] ?? null;
    $limit    = min((int)($_GET['limit'] ?? 50), 200);

    $where  = [];
    $params = [];
    if ($platform) { $where[] = 'c.platform_type = ?'; $params[] = $platform; }
    if ($replied !== null) { $where[] = 'c.is_replied = ?'; $params[] = (int)$replied; }
    $sql = "SELECT c.*, p.platform_type, p.account_name
            FROM comments c
            JOIN platforms p ON p.id = c.platform_id"
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY c.created_at DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json($stmt->fetchAll());
}

function handleReplyComment($id) {
    requireAuth();
    $body    = jsonBody();
    $content = trim($body['content'] ?? '');
    if (!$content) json(['error' => 'Nội dung trả lời không được trống'], 400);
    $db   = getDb();
    $stmt = $db->prepare("UPDATE comments SET is_replied=1, reply_content=?, replied_at=NOW(), updated_at=NOW() WHERE id=?");
    $stmt->execute([$content, $id]);
    if (!$stmt->rowCount()) json(['error' => 'Không tìm thấy bình luận'], 404);
    json(['success' => true, 'message' => 'Đã trả lời bình luận']);
}

// ─── Templates ───────────────────────────────────────

function handleListTemplates() {
    requireAuth();
    $db   = getDb();
    $stmt = $db->query("SELECT * FROM reply_templates ORDER BY created_at DESC");
    json($stmt->fetchAll());
}

function handleCreateTemplate() {
    requireAuth();
    $body    = jsonBody();
    $name    = trim($body['name'] ?? '');
    $content = trim($body['content'] ?? '');
    if (!$name || !$content) json(['error' => 'Thiếu tên hoặc nội dung'], 400);
    $db   = getDb();
    $stmt = $db->prepare("INSERT INTO reply_templates (name, content) VALUES (?, ?)");
    $stmt->execute([$name, $content]);
    json(['id' => $db->lastInsertId(), 'name' => $name, 'content' => $content], 201);
}

function handleDeleteTemplate($id) {
    requireAuth();
    $db   = getDb();
    $stmt = $db->prepare("DELETE FROM reply_templates WHERE id=?");
    $stmt->execute([$id]);
    json(['success' => true]);
}

// ─── Auto-reply rules ────────────────────────────────

function handleListRules() {
    requireAuth();
    $db   = getDb();
    $stmt = $db->query("SELECT r.*, t.name as template_name FROM auto_reply_rules r LEFT JOIN reply_templates t ON t.id=r.template_id ORDER BY r.created_at DESC");
    json($stmt->fetchAll());
}

function handleCreateRule() {
    requireAuth();
    $body     = jsonBody();
    $name     = trim($body['name'] ?? '');
    $keyword  = trim($body['keyword'] ?? '');
    $match    = $body['matchType'] ?? 'contains';
    $template = $body['templateId'] ?? null;
    $platform = $body['platformType'] ?? 'all';
    if (!$name || !$keyword) json(['error' => 'Thiếu tên hoặc từ khóa'], 400);
    $db   = getDb();
    $stmt = $db->prepare("INSERT INTO auto_reply_rules (name, keyword, match_type, template_id, platform_type) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $keyword, $match, $template ?: null, $platform]);
    json(['id' => $db->lastInsertId(), 'name' => $name], 201);
}

function handleToggleRule($id) {
    requireAuth();
    $db   = getDb();
    $stmt = $db->prepare("UPDATE auto_reply_rules SET is_active = 1 - is_active, updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    json(['success' => true]);
}

function handleDeleteRule($id) {
    requireAuth();
    $db   = getDb();
    $stmt = $db->prepare("DELETE FROM auto_reply_rules WHERE id=?");
    $stmt->execute([$id]);
    json(['success' => true]);
}

function handleRunAutoReply() {
    requireAuth();
    $db    = getDb();
    $rules = $db->query("SELECT r.*, t.content as template_content FROM auto_reply_rules r LEFT JOIN reply_templates t ON t.id=r.template_id WHERE r.is_active=1")->fetchAll();
    $comments = $db->query("SELECT * FROM comments WHERE is_replied=0")->fetchAll();
    $processed = 0;
    foreach ($comments as $comment) {
        foreach ($rules as $rule) {
            $matched = false;
            $kw = $rule['keyword'];
            $text = mb_strtolower($comment['content']);
            switch ($rule['match_type']) {
                case 'exact':      $matched = $text === mb_strtolower($kw); break;
                case 'startsWith': $matched = str_starts_with($text, mb_strtolower($kw)); break;
                case 'regex':      $matched = @preg_match('/' . $kw . '/iu', $comment['content']) === 1; break;
                default:           $matched = str_contains($text, mb_strtolower($kw));
            }
            if ($matched && $rule['template_content']) {
                $reply = str_replace(['{name}', '{platform}'], [$comment['author_name'] ?? 'bạn', $comment['platform_type'] ?? ''], $rule['template_content']);
                $db->prepare("UPDATE comments SET is_replied=1, is_auto_replied=1, reply_content=?, replied_at=NOW() WHERE id=?")->execute([$reply, $comment['id']]);
                $db->prepare("UPDATE auto_reply_rules SET trigger_count=trigger_count+1 WHERE id=?")->execute([$rule['id']]);
                $db->prepare("UPDATE reply_templates SET use_count=use_count+1 WHERE id=?")->execute([$rule['template_id']]);
                $processed++;
                break;
            }
        }
    }
    json(['processed' => $processed, 'message' => "Đã xử lý $processed bình luận"]);
}

function handleSeedComments() {
    requireAuth();
    $db = getDb();
    $p  = $db->query("SELECT id, platform_type FROM platforms LIMIT 1")->fetch();
    if (!$p) json(['error' => 'Chưa có nền tảng nào. Thêm nền tảng trước.'], 400);
    $samples = [
        ['Nội dung hay quá, tiếp tục nhé!',  'Nguyễn Văn A', 'positive'],
        ['Video này dở quá, chán thật.',       'Trần Thị B',   'negative'],
        ['Bao giờ ra video mới?',              'Lê Văn C',     'neutral'],
        ['Cảm ơn bạn đã chia sẻ!',            'Phạm Thị D',   'positive'],
        ['Nội dung không hay lắm.',            'Hoàng Văn E',  'negative'],
        ['Cho mình hỏi link tải ở đâu vậy?',  'Đỗ Thị F',     'neutral'],
        ['Video rất bổ ích, cảm ơn bạn!',     'Ngô Văn G',    'positive'],
        ['Bình thường thôi, không có gì mới.', 'Vũ Thị H',     'neutral'],
        ['Hay lắm! Theo dõi lâu rồi.',         'Đinh Văn I',   'positive'],
        ['Lần sau làm tốt hơn nhé.',            'Bùi Thị J',    'negative'],
    ];
    $stmt = $db->prepare("INSERT INTO comments (platform_id, author_name, content, sentiment) VALUES (?,?,?,?)");
    foreach ($samples as [$content, $name, $sentiment]) {
        $stmt->execute([$p['id'], $name, $content, $sentiment]);
    }
    json(['inserted' => count($samples)]);
}

function handleEngagementStats() {
    requireAuth();
    $db = getDb();
    $total    = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $replied  = (int)$db->query("SELECT COUNT(*) FROM comments WHERE is_replied=1")->fetchColumn();
    $auto     = (int)$db->query("SELECT COUNT(*) FROM comments WHERE is_auto_replied=1")->fetchColumn();
    $positive = (int)$db->query("SELECT COUNT(*) FROM comments WHERE sentiment='positive'")->fetchColumn();
    $negative = (int)$db->query("SELECT COUNT(*) FROM comments WHERE sentiment='negative'")->fetchColumn();
    $neutral  = (int)$db->query("SELECT COUNT(*) FROM comments WHERE sentiment='neutral'")->fetchColumn();
    json([
        'totalComments'   => $total,
        'repliedComments' => $replied,
        'autoReplied'     => $auto,
        'positiveCount'   => $positive,
        'negativeCount'   => $negative,
        'neutralCount'    => $neutral,
        'replyRate'       => $total > 0 ? round($replied / $total * 100) : 0,
        'autoRate'        => $replied > 0 ? round($auto / $replied * 100) : 0,
    ]);
}
