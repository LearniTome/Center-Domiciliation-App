-- Taxonomie des qualifications d'intermediaire (types de collaborateurs externes).
-- Alimente la liste deroulante du code collaborateur et le segment [TYPE] du nom de dossier.
--
-- Volontairement distinct de la table roles : roles modelise les droits de connexion
-- (RBAC), cette table modelise la qualification de l'intermediaire sur un dossier.
-- Un role peut donc map vers plusieurs codes selon l'axe direct/coursier.
--
-- Idempotent : CREATE TABLE IF NOT EXISTS + INSERT IGNORE sur l'unicite du code.

CREATE TABLE IF NOT EXISTS ref_qualites_intermediaire (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL COMMENT 'Ex. CPT-EXP, COU-AGR, CLT-DIR',
    axe_mode VARCHAR(20) NOT NULL COMMENT 'direct | coursier | client',
    axe_qualif VARCHAR(20) NULL COMMENT 'expert | agre | independant | NULL',
    libelle VARCHAR(120) NOT NULL,
    icon VARCHAR(40) NULL COMMENT 'Nom Material Symbols pour l affichage',
    sort_order INT NOT NULL DEFAULT 0,
    is_system TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_qualites_code (code),
    KEY idx_ref_qualites_axe (axe_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO ref_qualites_intermediaire (code, axe_mode, axe_qualif, libelle, icon, sort_order) VALUES
('CPT-EXP', 'direct',   'expert',      'Expert-comptable',                 'calculate', 10),
('CPT-AGR', 'direct',   'agre',        'Comptable agree',                  'receipt_long', 20),
('CPT-IND', 'direct',   'independant', 'Comptable independant',            'receipt_long', 30),
('COU-EXP', 'coursier', 'expert',      'Coursier expert-comptable',        'local_shipping', 40),
('COU-AGR', 'coursier', 'agre',        'Coursier comptable agree',         'local_shipping', 50),
('COU-IND', 'coursier', 'independant', 'Coursier comptable independant',   'local_shipping', 60),
('CLT-DIR', 'client',   NULL,          'Client direct',                    'person', 70);
