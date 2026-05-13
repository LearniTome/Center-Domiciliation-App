CREATE DATABASE IF NOT EXISTS `center_domiciliation`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `center_domiciliation`;

CREATE TABLE IF NOT EXISTS societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Identifiants
    dossier_domiciliation VARCHAR(120) DEFAULT NULL,
    raison_sociale VARCHAR(255) NOT NULL,
    -- Juridique
    forme_juridique VARCHAR(120) DEFAULT NULL,
    ice VARCHAR(100) DEFAULT NULL,
    date_ice DATE DEFAULT NULL,
    rc VARCHAR(100) DEFAULT NULL,
    if_number VARCHAR(100) DEFAULT NULL,
    -- Capital
    capital DECIMAL(15,2) DEFAULT NULL,
    part_social INT DEFAULT NULL,
    valeur_nominale DECIMAL(15,2) DEFAULT NULL,
    date_exp_cert_neg DATE DEFAULT NULL,
    -- Adresse
    adresse TEXT DEFAULT NULL,
    ste_adress TEXT DEFAULT NULL,
    ville VARCHAR(120) DEFAULT NULL,
    tribunal VARCHAR(120) DEFAULT NULL,
    -- Contact
    email VARCHAR(190) DEFAULT NULL,
    telephone VARCHAR(60) DEFAULT NULL,
    -- Procedure
    type_generation VARCHAR(120) DEFAULT NULL,
    procedure_creation VARCHAR(120) DEFAULT NULL,
    mode_depot_creation VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_societes_ice (ice),
    INDEX idx_societes_ville (ville)
);

CREATE TABLE IF NOT EXISTS associes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    -- Identite
    civilite VARCHAR(10) DEFAULT NULL,
    nom VARCHAR(120) DEFAULT NULL,
    prenom VARCHAR(120) DEFAULT NULL,
    nom_complet VARCHAR(255) NOT NULL,
    cin VARCHAR(100) DEFAULT NULL,
    date_validite_cin DATE DEFAULT NULL,
    date_naiss DATE DEFAULT NULL,
    lieu_naiss VARCHAR(120) DEFAULT NULL,
    nationalite VARCHAR(120) DEFAULT NULL,
    -- Contact
    adresse TEXT DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    -- Participation
    qualite_associe VARCHAR(150) DEFAULT NULL,
    parts INT DEFAULT NULL,
    capital_detenu DECIMAL(15,2) DEFAULT NULL,
    part_percent DECIMAL(7,2) DEFAULT NULL,
    is_gerant TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_associes_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_associes_societe_id (societe_id),
    INDEX idx_associes_nom_complet (nom_complet)
);

CREATE TABLE IF NOT EXISTS contrats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    -- Type et duree
    type_contrat VARCHAR(120) NOT NULL,
    date_contrat DATE DEFAULT NULL,
    duree_contrat_mois INT DEFAULT NULL,
    type_contrat_domiciliation VARCHAR(120) DEFAULT NULL,
    type_contrat_domiciliation_autre VARCHAR(190) DEFAULT NULL,
    -- Periode
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    -- Loyer
    loyer_mensuel_ttc DECIMAL(15,2) DEFAULT NULL,
    frais_intermediaire_contrat DECIMAL(15,2) DEFAULT NULL,
    caution_montant DECIMAL(15,2) DEFAULT NULL,
    taux_tva_pourcent DECIMAL(7,2) DEFAULT NULL,
    loyer_mensuel_ht DECIMAL(15,2) DEFAULT NULL,
    montant_total_ht_contrat DECIMAL(15,2) DEFAULT NULL,
    montant_pack_demarrage_ttc DECIMAL(15,2) DEFAULT NULL,
    loyer_mensuel_pack_demarrage_ttc DECIMAL(15,2) DEFAULT NULL,
    -- Renouvellement
    type_renouvellement VARCHAR(120) DEFAULT NULL,
    taux_tva_renouvellement_pourcent DECIMAL(7,2) DEFAULT NULL,
    loyer_mensuel_ht_renouvellement DECIMAL(15,2) DEFAULT NULL,
    montant_total_ht_renouvellement DECIMAL(15,2) DEFAULT NULL,
    loyer_mensuel_renouvellement_ttc DECIMAL(15,2) DEFAULT NULL,
    loyer_annuel_renouvellement_ttc DECIMAL(15,2) DEFAULT NULL,
    statut VARCHAR(80) DEFAULT 'actif',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contrats_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_contrats_societe_id (societe_id),
    INDEX idx_contrats_type (type_contrat)
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
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_tribunaux (tribunal)
);

CREATE TABLE IF NOT EXISTS ref_activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites (activite)
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
