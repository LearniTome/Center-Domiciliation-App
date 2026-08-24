-- Reparation : collaborateurs.societe_id manquant (present dans schema.sql et le code,
-- mais jamais ajoute sur certaines bases via migration).
-- Idempotent : colonne/index conditionnels ; contrainte FK toleree deja existante par le runner (errno 1826).

ALTER TABLE collaborateurs
  ADD COLUMN IF NOT EXISTS societe_id INT UNSIGNED DEFAULT NULL AFTER id;

ALTER TABLE collaborateurs
  ADD INDEX IF NOT EXISTS idx_collaborateurs_societe_id (societe_id);

ALTER TABLE collaborateurs
  ADD CONSTRAINT fk_collaborateurs_societe FOREIGN KEY (societe_id) REFERENCES societes(id) ON DELETE SET NULL;
