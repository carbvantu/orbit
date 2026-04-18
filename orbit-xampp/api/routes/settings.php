<?php
// ─── SETTINGS ROUTES ─────────────────────────────────
// GET /api/settings
// PUT /api/settings/:key

function handleListSettings(): void {
    requireAuth();
    $db   = getDB();
    $rows = $db->query("SELECT id, setting_key, setting_label, setting_group, is_secret,
        CASE WHEN is_secret = 1 AND setting_value != '' THEN '••••••••' ELSE setting_value END as setting_value,
        created_at, updated_at
        FROM app_settings ORDER BY setting_group, setting_key")->fetchAll();
    json($rows);
}

function handleUpdateSetting(string $key): void {
    $user = requireAuth();
    $body = getBody();
    $db   = getDB();

    $stmt = $db->prepare("SELECT * FROM app_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $setting = $stmt->fetch();
    if (!$setting) jsonError('Không tìm thấy cài đặt', 404);

    $value = $body['value'] ?? $body['settingValue'] ?? '';
    $db->prepare("UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?")->execute([$value, $key]);

    logActivity('setting_updated', "Cập nhật cài đặt: $key", 'setting', 0, $user['id']);
    json(['message' => 'Đã cập nhật', 'key' => $key]);
}
