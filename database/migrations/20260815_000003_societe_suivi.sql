CREATE TABLE IF NOT EXISTS societe_suivi_etapes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    etape VARCHAR(80) NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (societe_id) REFERENCES societes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS societe_suivi_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etape_id INT UNSIGNED NOT NULL,
    nom VARCHAR(255) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etape_id) REFERENCES societe_suivi_etapes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default steps for all existing societes, selon le type de generation
INSERT INTO societe_suivi_etapes (societe_id, etape, ordre)
SELECT s.id, steps.etape, steps.ordre
FROM societes s
JOIN (
    SELECT 'creation' AS gen, 'certificat_negatif' AS etape, 1 AS ordre UNION ALL
    SELECT 'creation', 'redaction_statuts', 2 UNION ALL
    SELECT 'creation', 'signature', 3 UNION ALL
    SELECT 'creation', 'enregistrement', 4 UNION ALL
    SELECT 'creation', 'depot_greffe', 5 UNION ALL
    SELECT 'creation', 'publication_jal_bo', 6 UNION ALL
    SELECT 'creation', 'rc', 7 UNION ALL
    SELECT 'creation', 'remise', 8 UNION ALL
    SELECT 'domiciliation', 'contrat_domiciliation', 1 UNION ALL
    SELECT 'domiciliation', 'redaction', 2 UNION ALL
    SELECT 'domiciliation', 'signature', 3 UNION ALL
    SELECT 'domiciliation', 'enregistrement', 4 UNION ALL
    SELECT 'domiciliation', 'depot_greffe', 5 UNION ALL
    SELECT 'domiciliation', 'publication_jal', 6 UNION ALL
    SELECT 'domiciliation', 'rc_modificatif', 7 UNION ALL
    SELECT 'domiciliation', 'remise', 8
) steps ON steps.gen = CASE WHEN s.societe_type_generation = 'creation' THEN 'creation' ELSE 'domiciliation' END
WHERE NOT EXISTS (
    SELECT 1 FROM societe_suivi_etapes e WHERE e.societe_id = s.id
);
