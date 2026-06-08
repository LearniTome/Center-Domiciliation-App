CREATE TABLE IF NOT EXISTS ref_fonctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fonction VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_fonctions (fonction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO ref_fonctions (fonction, sort_order) VALUES
('Gestion administrative', 1),
('Support operationnel', 2),
('Agent de traitement', 3),
('Chef d''équipe', 4),
('Superviseur', 5),
('Comptable', 6),
('Assistant juridique', 7),
('Responsable clientèle', 8),
('Coursier', 9),
('Autre', 99);
