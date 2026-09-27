-- SaaS multi-tenancy : comptes de connexion distincts des collaborateurs
-- Phase 1/5 - Fondation donnees
--
-- Pourquoi une table `users` separee de `collaborateurs` :
--   - `collaborateurs` = donnee metier (personne affectee a un dossier societe)
--   - `users` = compte de connexion (interne Centre ou adherent d'un cabinet)
-- Un administrateur de cabinet n'est pas un "collaborateur de societe" : il
-- n'existe aucun dossier auquel le rattacher. Conflater les deux notions
-- empeche le multi-tenancy.
--
-- Convention : users.cabinet_id IS NULL = employe interne du Centre
-- (acces transversal, jamais facture). Non NULL = adherent d'un cabinet.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_complet VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    cabinet_id INT UNSIGNED DEFAULT NULL,
    collaborateur_id INT UNSIGNED DEFAULT NULL,
    role_id INT UNSIGNED DEFAULT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'actif',
    telephone VARCHAR(40) DEFAULT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_cabinet (cabinet_id),
    KEY idx_users_role (role_id),
    KEY idx_users_statut (statut),
    KEY idx_users_collaborateur (collaborateur_id),
    CONSTRAINT fk_users_cabinet FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles multiples par compte. users.role_id reste le role primaire
-- (denormalise, conserve pour compatibilite avec les lectures existantes
-- de $user['role_id']).
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    KEY idx_user_roles_role (role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Surcharges individuelles (grant/refuse). Remplace collaborateur_permissions
-- qui etait indexe sur la table metier.
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    granted TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    KEY idx_up_permission (permission_id),
    CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_up_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reprise des comptes existants : collaborateurs.can_login = 1
INSERT IGNORE INTO users (nom_complet, email, password_hash, cabinet_id, collaborateur_id, role_id, statut, last_login)
SELECT
    c.nom_complet,
    COALESCE(NULLIF(c.email, ''), NULLIF(c.collaborateur_email, '')),
    c.password_hash,
    NULL,
    c.id,
    c.role_id,
    CASE WHEN c.statut = 'actif' THEN 'actif' ELSE 'suspendu' END,
    c.last_login
FROM collaborateurs c
WHERE c.can_login = 1
  AND c.email IS NOT NULL AND c.email <> ''
  AND (c.collaborateur_email IS NULL OR c.collaborateur_email = '' OR c.email = c.collaborateur_email)
  AND c.id IN (SELECT MIN(x.id) FROM collaborateurs x WHERE x.can_login = 1 AND x.email IS NOT NULL AND x.email <> '' GROUP BY x.email);

-- Reprise des surcharges individuelles depuis la table metier
INSERT IGNORE INTO user_permissions (user_id, permission_id, granted)
SELECT u.id, cp.permission_id, cp.granted
FROM collaborateur_permissions cp
JOIN users u ON u.collaborateur_id = cp.collaborateur_id;
