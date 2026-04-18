<?php
// ─── HEALTH & KEEPALIVE ──────────────────────────────
// GET /api/healthz
// GET /api/ping

function handleHealth(): void {
    $db = getDB();
    try {
        $db->query("SELECT 1");
        $dbOk = true;
    } catch (Exception $e) {
        $dbOk = false;
    }

    json([
        'status'    => $dbOk ? 'ok' : 'degraded',
        'database'  => $dbOk ? 'connected' : 'error',
        'timestamp' => date('c'),
        'timezone'  => 'Asia/Ho_Chi_Minh',
    ]);
}

function handlePing(): void {
    json([
        'pong'      => true,
        'timestamp' => date('c'),
        'time_vn'   => (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('H:i:s d/m/Y'),
    ]);
}
