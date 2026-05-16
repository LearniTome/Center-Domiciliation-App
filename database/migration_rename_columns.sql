-- Migration : Renommer les colonnes des tables societes, associes, contrats
-- Execute: Get-Content database/migration_rename_columns.sql | mysql -u root center_domiciliation

USE center_domiciliation;

-- ============================================================
-- Table: societes (colonnes encore anciennes)
-- ============================================================
ALTER TABLE societes CHANGE capital societe_capital DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE societes CHANGE activites_ompic societe_activites_ompic TEXT DEFAULT NULL;
ALTER TABLE societes CHANGE part_social societe_part_social INT DEFAULT NULL;
ALTER TABLE societes CHANGE valeur_nominale societe_valeur_nominale DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE societes CHANGE date_exp_cert_neg societe_date_exp_cert_neg DATE DEFAULT NULL;
ALTER TABLE societes CHANGE adresse societe_adresse TEXT DEFAULT NULL;
ALTER TABLE societes CHANGE ste_adress societe_adresse_siege TEXT DEFAULT NULL;
ALTER TABLE societes CHANGE ville societe_ville VARCHAR(120) DEFAULT NULL;
ALTER TABLE societes CHANGE tribunal societe_tribunal VARCHAR(120) DEFAULT NULL;
ALTER TABLE societes CHANGE email societe_email VARCHAR(190) DEFAULT NULL;
ALTER TABLE societes CHANGE telephone societe_telephone VARCHAR(60) DEFAULT NULL;
ALTER TABLE societes CHANGE type_generation societe_type_generation VARCHAR(120) DEFAULT NULL;
ALTER TABLE societes CHANGE procedure_creation societe_procedure_creation VARCHAR(120) DEFAULT NULL;
ALTER TABLE societes CHANGE mode_depot_creation societe_mode_depot VARCHAR(120) DEFAULT NULL;

-- Ajouter societe_activites_statuts si manquant
ALTER TABLE societes ADD COLUMN societe_activites_statuts TEXT DEFAULT NULL AFTER societe_if;

-- Renommer les index
ALTER TABLE societes DROP INDEX IF EXISTS idx_societes_ice;
ALTER TABLE societes DROP INDEX IF EXISTS idx_societes_ville;
ALTER TABLE societes ADD INDEX idx_societes_ice (societe_ice);
ALTER TABLE societes ADD INDEX idx_societes_ville (societe_ville);

-- ============================================================
-- Table: associes (toutes les colonnes sont encore anciennes)
-- ============================================================
ALTER TABLE associes CHANGE civilite associe_civilite VARCHAR(10) DEFAULT NULL;
ALTER TABLE associes CHANGE nom associe_nom VARCHAR(120) DEFAULT NULL;
ALTER TABLE associes CHANGE prenom associe_prenom VARCHAR(120) DEFAULT NULL;
ALTER TABLE associes CHANGE nom_complet associe_nom_complet VARCHAR(255) NOT NULL;
ALTER TABLE associes CHANGE cin associe_cin VARCHAR(100) DEFAULT NULL;
ALTER TABLE associes CHANGE date_validite_cin associe_date_validite_cin DATE DEFAULT NULL;
ALTER TABLE associes CHANGE date_naiss associe_date_naissance DATE DEFAULT NULL;
ALTER TABLE associes CHANGE lieu_naiss associe_lieu_naissance VARCHAR(120) DEFAULT NULL;
ALTER TABLE associes CHANGE nationalite associe_nationalite VARCHAR(120) DEFAULT NULL;
ALTER TABLE associes CHANGE adresse associe_adresse TEXT DEFAULT NULL;
ALTER TABLE associes CHANGE phone associe_telephone VARCHAR(60) DEFAULT NULL;
ALTER TABLE associes CHANGE email associe_email VARCHAR(190) DEFAULT NULL;
ALTER TABLE associes CHANGE qualite_associe associe_qualite VARCHAR(150) DEFAULT NULL;
ALTER TABLE associes CHANGE parts associe_parts INT DEFAULT NULL;
ALTER TABLE associes CHANGE capital_detenu associe_capital_detenu DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE associes CHANGE part_percent associe_part_percent DECIMAL(7,2) DEFAULT NULL;
ALTER TABLE associes CHANGE is_gerant associe_est_gerant TINYINT(1) NOT NULL DEFAULT 0;

-- Renommer les index
ALTER TABLE associes DROP INDEX IF EXISTS idx_associes_nom_complet;
ALTER TABLE associes ADD INDEX idx_associes_nom_complet (associe_nom_complet);

-- ============================================================
-- Table: contrats (toutes les colonnes sont encore anciennes)
-- ============================================================
ALTER TABLE contrats CHANGE type_contrat contrat_type VARCHAR(120) NOT NULL;
ALTER TABLE contrats CHANGE date_contrat contrat_date DATE DEFAULT NULL;
ALTER TABLE contrats CHANGE duree_contrat_mois contrat_duree_mois INT DEFAULT NULL;
ALTER TABLE contrats CHANGE type_contrat_domiciliation contrat_type_domiciliation VARCHAR(120) DEFAULT NULL;
ALTER TABLE contrats CHANGE type_contrat_domiciliation_autre contrat_type_domiciliation_autre VARCHAR(190) DEFAULT NULL;
ALTER TABLE contrats CHANGE date_debut contrat_date_debut DATE DEFAULT NULL;
ALTER TABLE contrats CHANGE date_fin contrat_date_fin DATE DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_mensuel_ttc contrat_loyer_ttc DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE frais_intermediaire_contrat contrat_frais_intermediaire DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE caution_montant contrat_caution DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE taux_tva_pourcent contrat_tva_pourcent DECIMAL(7,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_mensuel_ht contrat_loyer_ht DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE montant_total_ht_contrat contrat_total_ht DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE montant_pack_demarrage_ttc contrat_pack_montant_ttc DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_mensuel_pack_demarrage_ttc contrat_pack_loyer_ttc DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE type_renouvellement contrat_type_renouvellement VARCHAR(120) DEFAULT NULL;
ALTER TABLE contrats CHANGE taux_tva_renouvellement_pourcent contrat_renouv_tva_pourcent DECIMAL(7,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_mensuel_ht_renouvellement contrat_renouv_loyer_ht DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE montant_total_ht_renouvellement contrat_renouv_total_ht DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_mensuel_renouvellement_ttc contrat_renouv_loyer_ttc DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE loyer_annuel_renouvellement_ttc contrat_renouv_annuel_ttc DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE contrats CHANGE statut contrat_statut VARCHAR(80) DEFAULT 'actif';
ALTER TABLE contrats CHANGE notes contrat_notes TEXT DEFAULT NULL;
ALTER TABLE contrats ADD contrat_mode_signature VARCHAR(120) DEFAULT NULL AFTER contrat_notes;

-- Renommer les index
ALTER TABLE contrats DROP INDEX IF EXISTS idx_contrats_type;
ALTER TABLE contrats ADD INDEX idx_contrats_type (contrat_type);

-- ============================================================
-- Notes:
-- 1. Les colonnes FK societe_id ne changent pas
-- 2. Les tables collaborateurs, documents_generes et les tables ref_* ne sont pas renommees
-- 3. La colonne den_ste de societes est conservee telle quelle (hors scope)
-- ============================================================
