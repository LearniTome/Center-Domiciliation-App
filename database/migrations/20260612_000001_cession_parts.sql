CREATE TABLE IF NOT EXISTS cessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    cession_dossier VARCHAR(120),
    cession_status VARCHAR(80) DEFAULT 'brouillon',
    cession_date DATE,
    cession_motif TEXT,
    capital_avant DECIMAL(15,2),
    parts_avant INT,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (societe_id) REFERENCES societes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cession_parts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cession_id INT UNSIGNED NOT NULL,
    cedant_associe_id INT UNSIGNED,
    cedant_nom_complet VARCHAR(255) NOT NULL,
    cedant_cin VARCHAR(100),
    cedant_type VARCHAR(20) DEFAULT 'existant',
    cessionnaire_associe_id INT UNSIGNED,
    cessionnaire_nom_complet VARCHAR(255) NOT NULL,
    cessionnaire_cin VARCHAR(100),
    cessionnaire_type VARCHAR(20) DEFAULT 'existant',
    cessionnaire_civilite VARCHAR(10),
    cessionnaire_date_naissance DATE,
    cessionnaire_lieu_naissance VARCHAR(120),
    cessionnaire_nationalite VARCHAR(120),
    cessionnaire_adresse TEXT,
    parts_cedees INT NOT NULL,
    prix_unitaire DECIMAL(15,2),
    prix_total DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cession_id) REFERENCES cessions(id) ON DELETE CASCADE
);

INSERT IGNORE INTO permissions (nom, permission_key, category, description) VALUES
('Voir les cessions', 'cessions.view', 'cessions', 'Consulter la liste des cessions de parts'),
('Creer une cession', 'cessions.create', 'cessions', 'Creer une nouvelle cession de parts'),
('Modifier une cession', 'cessions.edit', 'cessions', 'Modifier une cession existante'),
('Supprimer une cession', 'cessions.delete', 'cessions', 'Supprimer une cession'),
('Exporter les cessions', 'cessions.export', 'cessions', 'Exporter la liste des cessions');
