<?php
// =====================================================
// ORBIT - Cấu hình database MySQL
// Chỉnh sửa các thông số bên dưới cho phù hợp XAMPP
// =====================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'orbit');
define('DB_USER', 'root');      // Mặc định XAMPP
define('DB_PASS', '');          // Mặc định XAMPP để trống

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Kết nối database thất bại: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
