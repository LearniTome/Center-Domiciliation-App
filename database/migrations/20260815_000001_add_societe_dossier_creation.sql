-- Add societe_dossier_creation_number column to societes for creation-type dossier numbering
-- Idempotent : sur un schema neuf (schema.sql) la colonne existe deja -> no-op.
ALTER TABLE societes
  ADD COLUMN IF NOT EXISTS societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)';
