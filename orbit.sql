-- ORBIT - Video Scheduler
-- MySQL database schema
-- Import: phpMyAdmin → Import → chọn file này
-- Hoặc chạy: mysql -u root -p orbit < orbit.sql

CREATE DATABASE IF NOT EXISTS orbit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orbit;

-- Bảng users
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(200),
  `role`         ENUM('admin','viewer') DEFAULT 'admin',
  `is_active`    TINYINT(1) DEFAULT 1,
  `last_login`   DATETIME,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng videos
CREATE TABLE IF NOT EXISTS `videos` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(500) NOT NULL,
  `description` TEXT,
  `file_path`   VARCHAR(1000) NOT NULL,
  `tags`        TEXT,
  `status`      ENUM('active','archived') DEFAULT 'active',
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng platforms
CREATE TABLE IF NOT EXISTS `platforms` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `platform_type` ENUM('facebook','tiktok','youtube') NOT NULL,
  `account_name`  VARCHAR(200),
  `account_id`    VARCHAR(200),
  `access_token`  TEXT,
  `is_active`     TINYINT(1) DEFAULT 1,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng schedules
CREATE TABLE IF NOT EXISTS `schedules` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `video_id`     INT NOT NULL,
  `platform_id`  INT NOT NULL,
  `post_title`   VARCHAR(500),
  `post_caption` TEXT,
  `scheduled_at` DATETIME NOT NULL,
  `status`       ENUM('pending','published','failed','cancelled') DEFAULT 'pending',
  `error_message` TEXT,
  `published_at` DATETIME,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`platform_id`) REFERENCES `platforms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng app_settings
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(200) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `setting_label` VARCHAR(300),
  `setting_group` VARCHAR(100) DEFAULT 'general',
  `is_secret`     TINYINT(1) DEFAULT 0,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `action_type` VARCHAR(100),
  `description` TEXT,
  `entity_type` VARCHAR(100),
  `entity_id`   INT,
  `user_id`     INT,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cài đặt mặc định
INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`, `setting_label`, `setting_group`, `is_secret`) VALUES
('openai_api_key', '', 'OpenAI API Key', 'ai', 1),
('openai_model', 'gpt-4o-mini', 'OpenAI Model', 'ai', 0),
('facebook_app_id', '', 'Facebook App ID', 'facebook', 0),
('facebook_app_secret', '', 'Facebook App Secret', 'facebook', 1),
('tiktok_client_key', '', 'TikTok Client Key', 'tiktok', 0),
('tiktok_client_secret', '', 'TikTok Client Secret', 'tiktok', 1),
('youtube_client_id', '', 'YouTube Client ID', 'youtube', 0),
('youtube_client_secret', '', 'YouTube Client Secret', 'youtube', 1);

-- Bảng comments (tương tác)
CREATE TABLE IF NOT EXISTS `comments` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `platform_id`   INT NOT NULL,
  `platform_comment_id` VARCHAR(300),
  `author_name`   VARCHAR(300),
  `content`       TEXT NOT NULL,
  `sentiment`     ENUM('positive','negative','neutral') DEFAULT 'neutral',
  `is_replied`    TINYINT(1) DEFAULT 0,
  `reply_content` TEXT,
  `replied_at`    DATETIME,
  `is_auto_replied` TINYINT(1) DEFAULT 0,
  `post_id`       VARCHAR(300),
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`platform_id`) REFERENCES `platforms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng reply_templates (mẫu trả lời)
CREATE TABLE IF NOT EXISTS `reply_templates` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(300) NOT NULL,
  `content`    TEXT NOT NULL,
  `use_count`  INT DEFAULT 0,
  `is_active`  TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng auto_reply_rules (quy tắc tự động)
CREATE TABLE IF NOT EXISTS `auto_reply_rules` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(300) NOT NULL,
  `keyword`      VARCHAR(500) NOT NULL,
  `match_type`   ENUM('contains','exact','startsWith','regex') DEFAULT 'contains',
  `template_id`  INT,
  `platform_type` ENUM('all','facebook','tiktok','youtube') DEFAULT 'all',
  `trigger_count` INT DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`template_id`) REFERENCES `reply_templates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
