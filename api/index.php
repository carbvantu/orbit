<?php
// =====================================================
// ORBIT API - Router chính
// Xử lý tất cả request đến /api/*
// =====================================================

ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/videos.php';
require_once __DIR__ . '/routes/platforms.php';
require_once __DIR__ . '/routes/schedules.php';
require_once __DIR__ . '/routes/activity.php';
require_once __DIR__ . '/routes/settings.php';
require_once __DIR__ . '/routes/stats.php';
require_once __DIR__ . '/routes/tiktok.php';
require_once __DIR__ . '/routes/ai.php';
require_once __DIR__ . '/routes/health.php';
require_once __DIR__ . '/routes/engagement.php';

setCorsHeaders();

// ─── Parse URL ───────────────────────────────────────
$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName    = dirname($_SERVER['SCRIPT_NAME']);
$path          = '/' . ltrim(substr($requestUri, strlen($scriptName)), '/');
$path          = strtok($path, '?');   // Bỏ query string
$path          = rtrim($path, '/');    // Bỏ trailing slash
if ($path === '') $path = '/';

$method = $_SERVER['REQUEST_METHOD'];

// ─── Router ──────────────────────────────────────────

// Health & Ping (không cần auth)
if ($path === '/healthz' && $method === 'GET') { handleHealth(); exit; }
if ($path === '/ping'    && $method === 'GET') { handlePing();   exit; }

// Setup
if ($path === '/setup/status' && $method === 'GET')  { handleSetupStatus(); exit; }
if ($path === '/setup/init'   && $method === 'POST') { handleSetupInit();   exit; }

// Auth
if ($path === '/auth/login'  && $method === 'POST') { handleLogin();  exit; }
if ($path === '/auth/logout' && $method === 'POST') { handleLogout(); exit; }
if ($path === '/auth/me'     && $method === 'GET')  { handleMe();     exit; }

// Videos
if ($path === '/videos' && $method === 'GET')  { handleListVideos();  exit; }
if ($path === '/videos' && $method === 'POST') { handleCreateVideo(); exit; }
if (preg_match('#^/videos/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'GET')    { handleGetVideo($id);    exit; }
    if ($method === 'PUT')    { handleUpdateVideo($id); exit; }
    if ($method === 'DELETE') { handleDeleteVideo($id); exit; }
}

// Platforms
if ($path === '/platforms' && $method === 'GET')  { handleListPlatforms();  exit; }
if ($path === '/platforms' && $method === 'POST') { handleCreatePlatform(); exit; }
if (preg_match('#^/platforms/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'PUT')    { handleUpdatePlatform($id); exit; }
    if ($method === 'DELETE') { handleDeletePlatform($id); exit; }
}

// Schedules
if ($path === '/schedules' && $method === 'GET')  { handleListSchedules();  exit; }
if ($path === '/schedules' && $method === 'POST') { handleCreateSchedule(); exit; }
if (preg_match('#^/schedules/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'PUT')    { handleUpdateSchedule($id); exit; }
    if ($method === 'DELETE') { handleDeleteSchedule($id); exit; }
}

// Activity
if ($path === '/activity' && $method === 'GET') { handleListActivity(); exit; }

// Settings
if ($path === '/settings' && $method === 'GET') { handleListSettings(); exit; }
if (preg_match('#^/settings/(.+)$#', $path, $m)) {
    $key = urldecode($m[1]);
    if ($method === 'PUT') { handleUpdateSetting($key); exit; }
}

// Stats
if ($path === '/stats/dashboard' && $method === 'GET') { handleDashboardStats(); exit; }

// TikTok
if ($path === '/tiktok/fetch' && $method === 'POST') { handleTikTokFetch(); exit; }
if ($path === '/tiktok/batch' && $method === 'POST') { handleTikTokBatch(); exit; }

// AI
if ($path === '/ai/status' && $method === 'GET')  { handleAiStatus(); exit; }
if ($path === '/ai/chat'   && $method === 'POST') { handleAiChat();   exit; }

// Engagement — Comments
if ($path === '/engagement/comments'                   && $method === 'GET')  { handleListComments();  exit; }
if ($path === '/engagement/comments/seed-demo'         && $method === 'POST') { handleSeedComments();  exit; }
if (preg_match('#^/engagement/comments/(\d+)/reply$#', $path, $m)) {
    if ($method === 'POST') { handleReplyComment((int)$m[1]); exit; }
}

// Engagement — Templates
if ($path === '/engagement/templates' && $method === 'GET')  { handleListTemplates();  exit; }
if ($path === '/engagement/templates' && $method === 'POST') { handleCreateTemplate(); exit; }
if (preg_match('#^/engagement/templates/(\d+)$#', $path, $m)) {
    if ($method === 'DELETE') { handleDeleteTemplate((int)$m[1]); exit; }
}

// Engagement — Rules
if ($path === '/engagement/rules'                      && $method === 'GET')  { handleListRules();    exit; }
if ($path === '/engagement/rules'                      && $method === 'POST') { handleCreateRule();   exit; }
if ($path === '/engagement/rules/run-auto-reply'       && $method === 'POST') { handleRunAutoReply(); exit; }
if (preg_match('#^/engagement/rules/(\d+)/toggle$#', $path, $m)) {
    if ($method === 'POST') { handleToggleRule((int)$m[1]); exit; }
}
if (preg_match('#^/engagement/rules/(\d+)$#', $path, $m)) {
    if ($method === 'DELETE') { handleDeleteRule((int)$m[1]); exit; }
}

// Engagement — Stats
if ($path === '/engagement/stats' && $method === 'GET') { handleEngagementStats(); exit; }

// 404
json(['error' => "Route không tìm thấy: $method $path"], 404);
