CREATE TABLE IF NOT EXISTS cession_suivi_etapes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cession_id INT UNSIGNED NOT NULL,
    etape VARCHAR(80) NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cession_id) REFERENCES cessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cession_suivi_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etape_id INT UNSIGNED NOT NULL,
    nom VARCHAR(255) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etape_id) REFERENCES cession_suivi_etapes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default steps for all existing cessions
INSERT INTO cession_suivi_etapes (cession_id, etape, ordre)
SELECT c.id, steps.etape, steps.ordre
FROM cessions c
CROSS JOIN (
    SELECT 'redaction' AS etape, 1 AS ordre UNION ALL
    SELECT 'signature', 2 UNION ALL
    SELECT 'enregistrement', 3 UNION ALL
    SELECT 'legalisation', 4 UNION ALL
    SELECT 'depot_greffe', 5 UNION ALL
    SELECT 'publication_jal', 6 UNION ALL
    SELECT 'publication_bo', 7 UNION ALL
    SELECT 'rc_modificatif', 8 UNION ALL
    SELECT 'reglement', 9 UNION ALL
    SELECT 'remise', 10
) steps
WHERE NOT EXISTS (
    SELECT 1 FROM cession_suivi_etapes e WHERE e.cession_id = c.id
);
