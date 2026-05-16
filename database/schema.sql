CREATE DATABASE IF NOT EXISTS `center_domiciliation`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `center_domiciliation`;

CREATE TABLE IF NOT EXISTS societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_dossier VARCHAR(120) DEFAULT NULL,
    societe_raison_sociale VARCHAR(255) NOT NULL,
    societe_forme_juridique VARCHAR(120) DEFAULT NULL,
    societe_ice VARCHAR(100) DEFAULT NULL,
    societe_date_ice DATE DEFAULT NULL,
    societe_rc VARCHAR(100) DEFAULT NULL,
    societe_if VARCHAR(100) DEFAULT NULL,
    societe_activites_statuts TEXT DEFAULT NULL,
    societe_activites_ompic TEXT DEFAULT NULL,
    societe_capital DECIMAL(15,2) DEFAULT NULL,
    societe_part_social INT DEFAULT NULL,
    societe_valeur_nominale DECIMAL(15,2) DEFAULT NULL,
    societe_date_exp_cert_neg DATE DEFAULT NULL,
    societe_adresse TEXT DEFAULT NULL,
    societe_adresse_siege TEXT DEFAULT NULL,
    societe_ville VARCHAR(120) DEFAULT NULL,
    societe_tribunal VARCHAR(120) DEFAULT NULL,
    societe_email VARCHAR(190) DEFAULT NULL,
    societe_telephone VARCHAR(60) DEFAULT NULL,
    societe_type_generation VARCHAR(120) DEFAULT NULL,
    societe_procedure_creation VARCHAR(120) DEFAULT NULL,
    societe_mode_depot VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_societes_ice (societe_ice),
    INDEX idx_societes_ville (societe_ville)
);

CREATE TABLE IF NOT EXISTS associes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    associe_civilite VARCHAR(10) DEFAULT NULL,
    associe_nom VARCHAR(120) DEFAULT NULL,
    associe_prenom VARCHAR(120) DEFAULT NULL,
    associe_nom_complet VARCHAR(255) NOT NULL,
    associe_cin VARCHAR(100) DEFAULT NULL,
    associe_date_validite_cin DATE DEFAULT NULL,
    associe_date_naissance DATE DEFAULT NULL,
    associe_lieu_naissance VARCHAR(120) DEFAULT NULL,
    associe_nationalite VARCHAR(120) DEFAULT NULL,
    associe_adresse TEXT DEFAULT NULL,
    associe_telephone VARCHAR(60) DEFAULT NULL,
    associe_email VARCHAR(190) DEFAULT NULL,
    associe_qualite VARCHAR(150) DEFAULT NULL,
    associe_parts INT DEFAULT NULL,
    associe_capital_detenu DECIMAL(15,2) DEFAULT NULL,
    associe_part_percent DECIMAL(7,2) DEFAULT NULL,
    associe_est_gerant TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_associes_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_associes_societe_id (societe_id),
    INDEX idx_associes_nom_complet (associe_nom_complet)
);

CREATE TABLE IF NOT EXISTS contrats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    contrat_type VARCHAR(120) NOT NULL,
    contrat_date DATE DEFAULT NULL,
    contrat_duree_mois INT DEFAULT NULL,
    contrat_type_domiciliation VARCHAR(120) DEFAULT NULL,
    contrat_type_domiciliation_autre VARCHAR(190) DEFAULT NULL,
    contrat_date_debut DATE DEFAULT NULL,
    contrat_date_fin DATE DEFAULT NULL,
    contrat_loyer_ttc DECIMAL(15,2) DEFAULT NULL,
    contrat_frais_intermediaire DECIMAL(15,2) DEFAULT NULL,
    contrat_caution DECIMAL(15,2) DEFAULT NULL,
    contrat_tva_pourcent DECIMAL(7,2) DEFAULT NULL,
    contrat_loyer_ht DECIMAL(15,2) DEFAULT NULL,
    contrat_total_ht DECIMAL(15,2) DEFAULT NULL,
    contrat_pack_montant_ttc DECIMAL(15,2) DEFAULT NULL,
    contrat_pack_loyer_ttc DECIMAL(15,2) DEFAULT NULL,
    contrat_type_renouvellement VARCHAR(120) DEFAULT NULL,
    contrat_renouv_tva_pourcent DECIMAL(7,2) DEFAULT NULL,
    contrat_renouv_loyer_ht DECIMAL(15,2) DEFAULT NULL,
    contrat_renouv_total_ht DECIMAL(15,2) DEFAULT NULL,
    contrat_renouv_loyer_ttc DECIMAL(15,2) DEFAULT NULL,
    contrat_renouv_annuel_ttc DECIMAL(15,2) DEFAULT NULL,
    contrat_statut VARCHAR(80) DEFAULT 'actif',
    contrat_notes TEXT DEFAULT NULL,
    contrat_mode_signature VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contrats_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_contrats_societe_id (societe_id),
    INDEX idx_contrats_type (contrat_type)
);

CREATE TABLE IF NOT EXISTS collaborateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    den_ste VARCHAR(255) DEFAULT NULL,
    nom_complet VARCHAR(255) NOT NULL,
    fonction VARCHAR(150) DEFAULT NULL,
    collaborateur_type VARCHAR(120) DEFAULT NULL,
    collaborateur_code VARCHAR(120) DEFAULT NULL,
    collaborateur_nom VARCHAR(255) DEFAULT NULL,
    collaborateur_ice VARCHAR(100) DEFAULT NULL,
    collaborateur_tp VARCHAR(100) DEFAULT NULL,
    collaborateur_rc VARCHAR(100) DEFAULT NULL,
    collaborateur_if VARCHAR(100) DEFAULT NULL,
    collaborateur_tel_fixe VARCHAR(60) DEFAULT NULL,
    collaborateur_tel_mobile VARCHAR(60) DEFAULT NULL,
    collaborateur_adresse TEXT DEFAULT NULL,
    collaborateur_email VARCHAR(190) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    telephone VARCHAR(60) DEFAULT NULL,
    date_debut DATE DEFAULT NULL,
    statut VARCHAR(80) DEFAULT 'actif',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collaborateurs_nom (nom_complet)
);

CREATE TABLE IF NOT EXISTS ref_formes_juridiques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    forme_juridique VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_formes_juridiques (forme_juridique)
);

CREATE TABLE IF NOT EXISTS ref_ste_adresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ste_adresse VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_ste_adresses (ste_adresse)
);

CREATE TABLE IF NOT EXISTS ref_villes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ville VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_villes (ville)
);

CREATE TABLE IF NOT EXISTS ref_tribunaux (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tribunal VARCHAR(120) NOT NULL,
    tribunal_type VARCHAR(60) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_tribunaux (tribunal, tribunal_type)
);

CREATE TABLE IF NOT EXISTS ref_activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites (activite)
);

CREATE TABLE IF NOT EXISTS ref_activites_ompic (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites_ompic_code (code)
);

CREATE TABLE IF NOT EXISTS ref_nationalites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nationalite VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_nationalites (nationalite)
);

CREATE TABLE IF NOT EXISTS ref_lieux_naissance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lieu_naissance VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_lieux_naissance (lieu_naissance)
);

CREATE TABLE IF NOT EXISTS documents_generes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    template_source VARCHAR(255) DEFAULT NULL,
    doc_type VARCHAR(100) DEFAULT NULL,
    fichier_docx VARCHAR(500) NOT NULL,
    fichier_pdf VARCHAR(500) DEFAULT NULL,
    taille_ko DECIMAL(10,1) DEFAULT NULL,
    valide TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_documents_societe_id (societe_id),
    INDEX idx_documents_doc_type (doc_type),
    INDEX idx_documents_valide (valide)
);

CREATE TABLE IF NOT EXISTS ref_qualites_associe (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qualite_associe VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_qualites_associe (qualite_associe)
);
