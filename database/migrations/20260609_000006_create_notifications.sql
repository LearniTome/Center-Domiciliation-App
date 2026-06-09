-- Migration: Create notifications system
-- Target levels: super_admin (role_id=1), admin (role_id=2),
-- interne (collaborator_type='interne'),
-- externe (collaborator_type IN ('externe-pm','externe-pp'))
-- Date: 2026-06-09

CREATE TABLE IF NOT EXISTS notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    target_user_id  INT UNSIGNED DEFAULT NULL COMMENT 'NULL = non direct',
    target_role_id  INT UNSIGNED DEFAULT NULL COMMENT '1=super_admin, 2=admin, etc.',
    target_type     VARCHAR(50) DEFAULT NULL COMMENT 'interne|externe-pm|externe-pp|NULL=tous',
    type            VARCHAR(50) NOT NULL DEFAULT 'info' COMMENT 'info|warning|success|danger',
    title           VARCHAR(255) NOT NULL,
    message         TEXT DEFAULT NULL,
    link            VARCHAR(500) DEFAULT NULL,
    entity_type     VARCHAR(50) DEFAULT NULL,
    entity_id       INT UNSIGNED DEFAULT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    is_global       TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'force pour tous',
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME DEFAULT NULL,
    INDEX idx_notif_user (target_user_id, is_read),
    INDEX idx_notif_role (target_role_id, is_read),
    INDEX idx_notif_type (target_type, is_read),
    INDEX idx_notif_global (is_global, is_read),
    INDEX idx_notif_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
