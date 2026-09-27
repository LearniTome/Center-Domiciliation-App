-- SaaS multi-tenancy : tenant (cabinet) et catalogue de plans
-- Phase 1/5 - Fondation donnees
--
-- Un cabinet est un client SaaS du Centre de Domiciliation.
-- Un plan est la grille tarifaire annuelle souscrite par un cabinet.

CREATE TABLE IF NOT EXISTS cabinets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    nom VARCHAR(150) NOT NULL,
    raison_sociale VARCHAR(190) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    telephone VARCHAR(40) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    ville VARCHAR(120) DEFAULT NULL,
    ice VARCHAR(40) DEFAULT NULL,
    rc VARCHAR(60) DEFAULT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'actif',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cabinets_code (code),
    KEY idx_cabinets_statut (statut),
    KEY idx_cabinets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    nom VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    prix_annuel DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    devise VARCHAR(3) NOT NULL DEFAULT 'MAD',
    max_utilisateurs INT DEFAULT NULL,
    max_societes INT DEFAULT NULL,
    max_dossiers INT DEFAULT NULL,
    trial_jours INT NOT NULL DEFAULT 0,
    auto_renew TINYINT(1) NOT NULL DEFAULT 1,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plans_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO plans (code, nom, description, prix_annuel, devise, max_utilisateurs, max_societes, max_dossiers, trial_jours, actif, sort_order) VALUES
    ('essai', 'Essai', 'Evaluation gratuite, acces en lecture seule', 0.00, 'MAD', 2, 5, 5, 30, 1, 10),
    ('starter', 'Starter', 'Cabinet debutant, dossier en cours limités', 3600.00, 'MAD', 3, 40, 40, 0, 1, 20),
    ('pro', 'Pro', 'Cabinet etabli, generations de documents illimitees', 8400.00, 'MAD', 10, 200, 200, 0, 1, 30),
    ('business', 'Business', 'Cabinet multi-agences, quotas etendus', 18000.00, 'MAD', 30, 1000, 1000, 0, 1, 40),
    ('illimite', 'Illimite', 'Quotas non contraints, support prioritaire', 36000.00, 'MAD', NULL, NULL, NULL, 0, 1, 50);
