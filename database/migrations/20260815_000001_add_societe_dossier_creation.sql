-- Add societe_dossier_creation_number column to societes for creation-type dossier numbering
ALTER TABLE societes
  ADD COLUMN societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)' AFTER societe_dossier_domiciliation_number;
