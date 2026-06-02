-- RBAC: Roles, Permissions, and collaborateur auth migration
-- Run: mysql -u root center_domiciliation < database/migration_rbac.sql

USE center_domiciliation;

-- 1. Roles table (fusionne avec collaborateur_type)
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permissions_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role-permission pivot
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Alter collaborateurs for auth
ALTER TABLE collaborateurs
    ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER notes,
    ADD COLUMN role_id INT UNSIGNED DEFAULT NULL AFTER password_hash,
    ADD COLUMN can_login TINYINT(1) NOT NULL DEFAULT 0 AFTER role_id,
    ADD COLUMN last_login DATETIME DEFAULT NULL AFTER can_login,
    ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER last_login,
    ADD INDEX idx_collaborateurs_role_id (role_id),
    ADD INDEX idx_collaborateurs_can_login (can_login);
