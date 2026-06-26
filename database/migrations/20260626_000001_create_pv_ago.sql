CREATE TABLE IF NOT EXISTS pv_ago (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    dossier_numero VARCHAR(120) DEFAULT NULL,
    statut VARCHAR(80) DEFAULT 'brouillon',
    date_ago DATE DEFAULT NULL,
    heure_ago VARCHAR(20) DEFAULT '10:00',
    lieu_ago VARCHAR(255) DEFAULT 'au siege social',
    president_nom VARCHAR(255) DEFAULT NULL,
    president_qualite VARCHAR(100) DEFAULT 'Gerant',
    exercice_clos VARCHAR(9) DEFAULT NULL,
    total_parts INT UNSIGNED DEFAULT NULL,
    parts_presentes INT UNSIGNED DEFAULT NULL,
    resultat_net DECIMAL(15,2) DEFAULT NULL,
    resultat_type ENUM('benefice','perte') DEFAULT NULL,
    report_a_nouveau_debiteur DECIMAL(15,2) DEFAULT 0.00,
    reserve_legale_existante DECIMAL(15,2) DEFAULT 0.00,
    reserve_statutaire_existante DECIMAL(15,2) DEFAULT 0.00,
    reserve_facultative_existante DECIMAL(15,2) DEFAULT 0.00,
    capital_social DECIMAL(15,2) DEFAULT NULL,
    affectation_option ENUM('profit_distribution','loss_carryforward','loss_reserves') DEFAULT NULL,
    dividende_total DECIMAL(15,2) DEFAULT 0.00,
    reserve_statutaire_dotation DECIMAL(15,2) DEFAULT 0.00,
    reserve_facultative_dotation DECIMAL(15,2) DEFAULT 0.00,
    perte_reserve_prelevement DECIMAL(15,2) DEFAULT 0.00,
    resolutions JSON DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (societe_id) REFERENCES societes(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES collaborateurs(id) ON DELETE SET NULL,
    INDEX idx_pv_ago_societe_id (societe_id),
    INDEX idx_pv_ago_statut (statut),
    INDEX idx_pv_ago_dossier_numero (dossier_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents_generes ADD COLUMN pv_ago_id INT DEFAULT NULL AFTER cession_id,
    ADD INDEX idx_documents_generes_pv_ago_id (pv_ago_id);

INSERT IGNORE INTO permissions (permission_key, category, description)
VALUES
    ('pv_ago.view', 'pv_ago', 'Consulter les PV d assemblee generale ordinaire'),
    ('pv_ago.create', 'pv_ago', 'Creer un PV d assemblee generale ordinaire'),
    ('pv_ago.edit', 'pv_ago', 'Modifier un PV d assemblee generale ordinaire'),
    ('pv_ago.delete', 'pv_ago', 'Supprimer un PV d assemblee generale ordinaire');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE permission_key LIKE 'pv_ago.%';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key LIKE 'pv_ago.%';
