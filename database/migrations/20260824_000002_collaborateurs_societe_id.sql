-- Reparation : collaborateurs.societe_id manquant (present dans schema.sql et le code,
-- mais jamais ajoute sur certaines bases via migration).
-- Idempotent : colonne/index conditionnels ; contrainte FK via procedure stockee.

ALTER TABLE collaborateurs
  ADD COLUMN IF NOT EXISTS societe_id INT UNSIGNED DEFAULT NULL AFTER id;

ALTER TABLE collaborateurs
  ADD INDEX IF NOT EXISTS idx_collaborateurs_societe_id (societe_id);

-- FK via procedure pour gerer "already exists" proprement
DROP PROCEDURE IF EXISTS _add_fk_collab_societe;
DELIMITER //
CREATE PROCEDURE _add_fk_collab_societe()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'collaborateurs'
          AND CONSTRAINT_NAME = 'fk_collaborateurs_societe'
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        ALTER TABLE collaborateurs
            ADD CONSTRAINT fk_collaborateurs_societe
            FOREIGN KEY (societe_id) REFERENCES societes(id) ON DELETE SET NULL;
    END IF;
END //
DELIMITER ;

CALL _add_fk_collab_societe();
DROP PROCEDURE IF EXISTS _add_fk_collab_societe;
