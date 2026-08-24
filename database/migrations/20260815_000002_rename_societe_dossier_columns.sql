-- Rename societe dossier columns for clarity: <prefix>_dossier -> <prefix>_dossier_<type>_number
-- Idempotent : no-op si les colonnes portent deja leur nom final (schema neuf via schema.sql).
ALTER TABLE societes
  CHANGE COLUMN IF EXISTS societe_dossier societe_dossier_domiciliation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier domiciliation (DOM-YYYY-NNN)';

ALTER TABLE societes
  CHANGE COLUMN IF EXISTS societe_dossier_creation societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)';
