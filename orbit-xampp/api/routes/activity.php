<?php
// ─── ACTIVITY ROUTES ─────────────────────────────────
// GET /api/activity

function handleListActivity(): void {
    requireAuth();
    $db    = getDB();
    $limit = min((int)($_GET['limit'] ?? 50), 200);

    $rows = $db->query("
        SELECT a.*, u.username, u.display_name
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT $limit
    ")->fetchAll();

    json($rows);
}
