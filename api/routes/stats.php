<?php
// ─── STATS ROUTES ────────────────────────────────────
// GET /api/stats/dashboard

function handleDashboardStats(): void {
    requireAuth();
    $db = getDB();

    $totalVideos     = (int)$db->query("SELECT COUNT(*) FROM videos WHERE status='active'")->fetchColumn();
    $totalPlatforms  = (int)$db->query("SELECT COUNT(*) FROM platforms WHERE is_active=1")->fetchColumn();
    $scheduledToday  = (int)$db->query("SELECT COUNT(*) FROM schedules WHERE DATE(scheduled_at) = CURDATE() AND status='pending'")->fetchColumn();
    $publishedTotal  = (int)$db->query("SELECT COUNT(*) FROM schedules WHERE status='published'")->fetchColumn();
    $pendingSchedules= (int)$db->query("SELECT COUNT(*) FROM schedules WHERE status='pending'")->fetchColumn();
    $failedSchedules = (int)$db->query("SELECT COUNT(*) FROM schedules WHERE status='failed'")->fetchColumn();

    // Lịch sắp tới (5 cái gần nhất)
    $upcoming = $db->query("
        SELECT s.*, v.title as video_title, p.platform_type, p.account_name
        FROM schedules s
        JOIN videos v ON s.video_id = v.id
        JOIN platforms p ON s.platform_id = p.id
        WHERE s.status = 'pending' AND s.scheduled_at >= NOW()
        ORDER BY s.scheduled_at ASC
        LIMIT 5
    ")->fetchAll();

    // Hoạt động gần đây (5 cái)
    $recentActivity = $db->query("
        SELECT a.*, u.username
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ")->fetchAll();

    json([
        'totalVideos'      => $totalVideos,
        'totalPlatforms'   => $totalPlatforms,
        'scheduledToday'   => $scheduledToday,
        'publishedTotal'   => $publishedTotal,
        'pendingSchedules' => $pendingSchedules,
        'failedSchedules'  => $failedSchedules,
        'upcomingSchedules'=> $upcoming,
        'recentActivity'   => $recentActivity,
    ]);
}
