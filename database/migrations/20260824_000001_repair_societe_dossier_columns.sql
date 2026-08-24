-- Reparation : les migrations 20260815_000001/000002 ont ete marquees comme appliquees
-- sans effet reel (erreur 42S22 avalée par le runner). Cet etat intermediaire est corrige ici.
-- Syntaxe conditionnelle MariaDB : idempotent quel que soit l'etat du schema.

-- 1. Renommer societe_dossier -> societe_dossier_domiciliation_number (donnees preservees)
ALTER TABLE societes
  CHANGE COLUMN IF EXISTS societe_dossier societe_dossier_domiciliation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier domiciliation (DOM-YYYY-NNN)';

-- 2. Renommer l'eventuel ancien nom intermediaire
ALTER TABLE societes
  CHANGE COLUMN IF EXISTS societe_dossier_creation societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)';

-- 3. Ajouter la colonne creation si toujours absente
ALTER TABLE societes
  ADD COLUMN IF NOT EXISTS societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)' AFTER societe_dossier_domiciliation_number;
