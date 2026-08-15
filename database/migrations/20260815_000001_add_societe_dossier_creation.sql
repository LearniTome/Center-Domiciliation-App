-- Add societe_dossier_creation column to societes for creation-type dossier numbering
ALTER TABLE societes
  ADD COLUMN societe_dossier_creation VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)' AFTER societe_dossier;
