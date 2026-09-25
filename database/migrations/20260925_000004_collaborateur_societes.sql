-- Table de liaison collaborateurs <-> societes (Many-to-Many).
--
-- Pourquoi : le suivi "un collaborateur et ses clients domicilies" est
-- irrepresentable avec la colonne collaborateurs.societe_id existante, qui
-- autorise au mieux N collaborateurs pour UNE societe, mais limite chaque
-- collaborateur a une seule societe. Un expert-comptable suivi 40 societes
-- n'a donc aucun moyen d'exprimer ce portefeuille.
--
-- La colonne historique collaborateurs.societe_id est conservee et laissee en
-- place : elle n'est aujourd'hui alimentee nulle part (verifie sur le code et
-- sur les 4 lignes existantes), et la supprimer ferait perdre la FK
--fk_collaborateurs_societe. Elle n'est plus lue par le suivi.
--
-- is_principal : un seul collaborateur responsable par societe. C'est lui qui
-- porte le code dans le nom du dossier de sortie (voir DossierNaming).
-- L'unicite "un principal par societe" est garantie applicativement, pas par
-- un index unique, car MySQL ne sait pas indexer "une seule ligne a 1" sur
-- une colonne. Les lectures utilisent toujours ORDER BY id pour rester
-- deterministes.
--
-- Idempotent : CREATE TABLE IF NOT EXISTS + INSERT IGNORE sur la cle unique.

CREATE TABLE IF NOT EXISTS collaborateur_societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    collaborateur_id INT UNSIGNED NOT NULL,
    societe_id INT UNSIGNED NOT NULL,
    role_dossier VARCHAR(150) NULL COMMENT 'Fonction du collaborateur sur ce dossier (ex. comptable, coursier)',
    is_principal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Collaborateur responsable du dossier',
    date_debut DATE DEFAULT NULL,
    date_fin DATE NULL COMMENT 'Fin de la mission ; NULL tant que le suivi est actif',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_collaborateur_societe (collaborateur_id, societe_id),
    KEY idx_cs_collaborateur (collaborateur_id),
    KEY idx_cs_societe (societe_id),
    KEY idx_cs_societe_principal (societe_id, is_principal),
    CONSTRAINT fk_cs_collaborateur FOREIGN KEY (collaborateur_id)
        REFERENCES collaborateurs (id) ON DELETE CASCADE,
    CONSTRAINT fk_cs_societe FOREIGN KEY (societe_id)
        REFERENCES societes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
