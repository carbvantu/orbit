<?php
require_once __DIR__ . '/config/database.php';

// ─── Session ──────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('orbit_session');
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ─── Auth ──────────────────────────────────────────────
function requireAuth(): array {
    startSession();
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        exit;
    }
    return [
        'id'          => $_SESSION['user_id'],
        'username'    => $_SESSION['username'],
        'displayName' => $_SESSION['display_name'],
        'role'        => $_SESSION['role'],
    ];
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

// ─── Response helpers ─────────────────────────────────
function json(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    json(['error' => $message], $status);
}

// ─── Request helpers ──────────────────────────────────
function getBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function getParam(string $key, mixed $default = null): mixed {
    $body = getBody();
    if (isset($body[$key])) return $body[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    return $default;
}

// ─── Activity logger ─────────────────────────────────
function logActivity(string $action, string $desc, string $entityType = '', int $entityId = 0, ?int $userId = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (action_type, description, entity_type, entity_id, user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$action, $desc, $entityType, $entityId ?: null, $userId]);
    } catch (Exception $e) {
        // Không để lỗi log làm crash app
    }
}

// ─── CORS headers ────────────────────────────────────
function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
