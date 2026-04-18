<?php
// ─── AUTH ROUTES ──────────────────────────────────────
// GET  /api/setup/status
// POST /api/setup/init
// POST /api/auth/login
// POST /api/auth/logout
// GET  /api/auth/me

function handleSetupStatus(): void {
    $db = getDB();
    $count = $db->query("SELECT COUNT(*) as c FROM users WHERE is_active = 1")->fetch()['c'];
    json(['isSetup' => (int)$count > 0]);
}

function handleSetupInit(): void {
    $db = getDB();
    $count = $db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
    if ((int)$count > 0) {
        jsonError('Hệ thống đã được thiết lập rồi', 409);
    }

    $body = getBody();
    $username    = trim($body['username'] ?? '');
    $password    = $body['password'] ?? '';
    $displayName = trim($body['displayName'] ?? $username);

    if (!$username || !$password) {
        jsonError('Tên đăng nhập và mật khẩu là bắt buộc');
    }
    if (strlen($password) < 6) {
        jsonError('Mật khẩu phải có ít nhất 6 ký tự');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$username, $hash, $displayName]);
    $userId = (int)$db->lastInsertId();

    startSession();
    $_SESSION['user_id']     = $userId;
    $_SESSION['username']    = $username;
    $_SESSION['display_name']= $displayName;
    $_SESSION['role']        = 'admin';

    logActivity('setup_complete', 'Thiết lập hệ thống lần đầu', 'user', $userId, $userId);

    json(['message' => 'Thiết lập thành công', 'user' => [
        'id'          => $userId,
        'username'    => $username,
        'displayName' => $displayName,
        'role'        => 'admin',
    ]]);
}

function handleLogin(): void {
    $body     = getBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if (!$username || !$password) {
        jsonError('Tên đăng nhập và mật khẩu là bắt buộc');
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonError('Tên đăng nhập hoặc mật khẩu không đúng', 401);
    }

    // Cập nhật last_login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['role']         = $user['role'];

    logActivity('login', "Đăng nhập: {$user['username']}", 'user', $user['id'], $user['id']);

    json([
        'id'          => $user['id'],
        'username'    => $user['username'],
        'displayName' => $user['display_name'],
        'role'        => $user['role'],
    ]);
}

function handleLogout(): void {
    startSession();
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        logActivity('logout', 'Đăng xuất', 'user', $userId, $userId);
    }
    session_destroy();
    json(['message' => 'Đã đăng xuất']);
}

function handleMe(): void {
    $user = requireAuth();
    json($user);
}
