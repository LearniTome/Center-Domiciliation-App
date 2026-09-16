CREATE DATABASE IF NOT EXISTS `center_domiciliation`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `center_domiciliation`;

CREATE TABLE IF NOT EXISTS societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_dossier_domiciliation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier domiciliation (DOM-YYYY-NNN)',
    societe_dossier_creation_number VARCHAR(120) DEFAULT NULL COMMENT 'Numero de dossier creation (CRE-YYYY-NNN)',
    societe_raison_sociale VARCHAR(255) NOT NULL,
    societe_sigle VARCHAR(100) DEFAULT NULL,
    den_ste VARCHAR(255) DEFAULT NULL,
    societe_forme_juridique VARCHAR(120) DEFAULT NULL,
    societe_source VARCHAR(20) DEFAULT 'creation' COMMENT 'creation|cession|augmentation_capital|transfert_siege',
    societe_ice VARCHAR(100) DEFAULT NULL,
    societe_date_ice DATE DEFAULT NULL,
    societe_rc VARCHAR(100) DEFAULT NULL,
    societe_if VARCHAR(100) DEFAULT NULL,
    societe_tp VARCHAR(50) NOT NULL DEFAULT '',
    societe_cnss VARCHAR(50) NOT NULL DEFAULT '',
    societe_activites_statuts TEXT DEFAULT NULL,
    societe_capital DECIMAL(15,2) DEFAULT NULL,
    societe_activites_ompic TEXT DEFAULT NULL,
    societe_part_social INT DEFAULT NULL,
    societe_valeur_nominale DECIMAL(15,2) DEFAULT NULL,
    societe_date_exp_cert_neg DATE DEFAULT NULL,
    societe_adresse TEXT DEFAULT NULL,
    societe_adresse_siege TEXT DEFAULT NULL,
    societe_ville VARCHAR(120) DEFAULT NULL,
    societe_tribunal VARCHAR(120) DEFAULT NULL,
    societe_tribunal_type VARCHAR(60) DEFAULT NULL,
    societe_email VARCHAR(190) DEFAULT NULL,
    societe_telephone VARCHAR(60) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    societe_type_generation VARCHAR(120) DEFAULT NULL,
    societe_procedure_creation VARCHAR(120) DEFAULT NULL,
    societe_mode_depot VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_societes_ice (societe_ice),
    INDEX idx_societes_ville (societe_ville),
    INDEX idx_created_by (created_by),
    INDEX idx_societes_type_generation (societe_type_generation),
    INDEX idx_societes_raison_sociale (societe_raison_sociale),
    INDEX idx_societes_date_exp_cert_neg (societe_date_exp_cert_neg),
    UNIQUE KEY uq_societes_dossier_domiciliation (societe_dossier_domiciliation_number),
    UNIQUE KEY uq_societes_dossier_creation (societe_dossier_creation_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    associe_duree_gerance VARCHAR(60) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_associes_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_associes_societe_id (societe_id),
    INDEX idx_associes_nom_complet (associe_nom_complet),
    INDEX idx_associes_date_validite_cin (associe_date_validite_cin),
    INDEX idx_associes_cin (associe_cin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    INDEX idx_contrats_type (contrat_type),
    INDEX idx_contrats_date_fin (contrat_date_fin),
    INDEX idx_contrats_statut (contrat_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collaborateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED DEFAULT NULL,
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
    password_hash VARCHAR(255) DEFAULT NULL,
    role_id INT UNSIGNED DEFAULT NULL,
    can_login TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_collaborateurs_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE SET NULL,
    INDEX fk_collaborateurs_societe (societe_id),
    INDEX idx_collaborateurs_societe_id (societe_id),
    INDEX idx_collaborateurs_nom (nom_complet),
    INDEX idx_collaborateurs_role_id (role_id),
    INDEX idx_collaborateurs_can_login (can_login),
    INDEX idx_collaborateurs_collaborateur_email (collaborateur_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    cession_dossier VARCHAR(120) DEFAULT NULL,
    cession_status VARCHAR(80) DEFAULT 'brouillon',
    cession_date DATE DEFAULT NULL,
    cession_motif TEXT DEFAULT NULL,
    capital_avant DECIMAL(15,2) DEFAULT NULL,
    parts_avant INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    pv_resolutions TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT cessions_ibfk_1
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX societe_id (societe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cession_parts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cession_id INT UNSIGNED NOT NULL,
    cedant_associe_id INT UNSIGNED DEFAULT NULL,
    cedant_nom_complet VARCHAR(255) NOT NULL,
    cedant_cin VARCHAR(100) DEFAULT NULL,
    cedant_type VARCHAR(20) DEFAULT 'existant',
    cessionnaire_associe_id INT UNSIGNED DEFAULT NULL,
    cessionnaire_nom_complet VARCHAR(255) NOT NULL,
    cessionnaire_cin VARCHAR(100) DEFAULT NULL,
    cessionnaire_type VARCHAR(20) DEFAULT 'existant',
    cessionnaire_civilite VARCHAR(10) DEFAULT NULL,
    cessionnaire_date_naissance DATE DEFAULT NULL,
    cessionnaire_lieu_naissance VARCHAR(120) DEFAULT NULL,
    cessionnaire_nationalite VARCHAR(120) DEFAULT NULL,
    cessionnaire_adresse TEXT DEFAULT NULL,
    cessionnaire_telephone VARCHAR(20) NOT NULL DEFAULT '',
    cessionnaire_email VARCHAR(120) NOT NULL DEFAULT '',
    cessionnaire_qualite VARCHAR(80) NOT NULL DEFAULT '',
    cessionnaire_parts INT UNSIGNED NOT NULL DEFAULT 0,
    cessionnaire_capital_detenu DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cessionnaire_est_gerant TINYINT(1) NOT NULL DEFAULT 0,
    parts_cedees INT NOT NULL,
    prix_unitaire DECIMAL(15,2) DEFAULT NULL,
    prix_total DECIMAL(15,2) DEFAULT NULL,
    pourcentage DECIMAL(7,2) DEFAULT NULL,
    nommer_gerant TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT cession_parts_ibfk_1
        FOREIGN KEY (cession_id) REFERENCES cessions(id)
        ON DELETE CASCADE,
    INDEX cession_id (cession_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pv_ago (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    dossier_numero VARCHAR(120) DEFAULT NULL,
    statut VARCHAR(80) DEFAULT 'brouillon',
    date_ago DATE DEFAULT NULL,
    heure_ago VARCHAR(20) DEFAULT '10:00',
    lieu_ago VARCHAR(255) DEFAULT 'au siege social',
    president_nom VARCHAR(255) DEFAULT NULL,
    president_qualite VARCHAR(100) DEFAULT 'Gerant',
    exercice_clos VARCHAR(10) DEFAULT NULL,
    total_parts INT UNSIGNED DEFAULT NULL,
    parts_presentes INT UNSIGNED DEFAULT NULL,
    resultat_net DECIMAL(15,2) DEFAULT NULL,
    resultat_type ENUM('benefice','perte') DEFAULT NULL,
    report_a_nouveau_debiteur DECIMAL(15,2) DEFAULT 0.00,
    reserve_legale_existante DECIMAL(15,2) DEFAULT 0.00,
    reserve_statutaire_existante DECIMAL(15,2) DEFAULT 0.00,
    reserve_facultative_existante DECIMAL(15,2) DEFAULT 0.00,
    capital_social DECIMAL(15,2) DEFAULT NULL,
    affectation_option ENUM('profit_distribution','loss_carryforward','loss_reserves') DEFAULT NULL,
    dividende_total DECIMAL(15,2) DEFAULT 0.00,
    reserve_statutaire_dotation DECIMAL(15,2) DEFAULT 0.00,
    reserve_facultative_dotation DECIMAL(15,2) DEFAULT 0.00,
    perte_reserve_prelevement DECIMAL(15,2) DEFAULT 0.00,
    resolutions LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(resolutions)),
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pv_ago_ibfk_1
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    CONSTRAINT pv_ago_ibfk_2
        FOREIGN KEY (created_by) REFERENCES collaborateurs(id)
        ON DELETE SET NULL,
    KEY created_by (created_by),
    INDEX idx_pv_ago_societe_id (societe_id),
    INDEX idx_pv_ago_statut (statut),
    INDEX idx_pv_ago_dossier_numero (dossier_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pv_resolutions_templates (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'cession',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents_generes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    cession_id INT DEFAULT NULL,
    pv_ago_id INT DEFAULT NULL,
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
    INDEX idx_documents_valide (valide),
    INDEX idx_documents_generes_cession_id (cession_id),
    INDEX idx_documents_generes_pv_ago_id (pv_ago_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uploaded_docs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    doc_type VARCHAR(50) NOT NULL COMMENT 'certificat_negatif or cin_gerant',
    associe_idx INT UNSIGNED DEFAULT NULL COMMENT 'Index in associes array for cin_gerant',
    filename_original VARCHAR(255) NOT NULL,
    filename_stored VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    taille_ko DECIMAL(10,1) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uploaded_docs_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX idx_uploaded_docs_societe_id (societe_id),
    INDEX idx_uploaded_docs_type (doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    target_user_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL = non direct',
    target_role_id INT UNSIGNED DEFAULT NULL COMMENT '1=super_admin, 2=admin, etc.',
    target_type VARCHAR(50) DEFAULT NULL COMMENT 'interne|externe-pm|externe-pp|NULL=tous',
    type VARCHAR(50) NOT NULL DEFAULT 'info' COMMENT 'info|warning|success|danger',
    title VARCHAR(255) NOT NULL,
    message TEXT DEFAULT NULL,
    link VARCHAR(500) DEFAULT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_global TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'force pour tous',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    INDEX idx_notif_user (target_user_id, is_read),
    INDEX idx_notif_role (target_role_id, is_read),
    INDEX idx_notif_type (target_type, is_read),
    INDEX idx_notif_global (is_global, is_read),
    INDEX idx_notif_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    current_page VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    UNIQUE KEY uq_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_last_active (last_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS societe_suivi_etapes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    etape VARCHAR(80) NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT societe_suivi_etapes_ibfk_1
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE,
    INDEX societe_id (societe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS societe_suivi_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etape_id INT UNSIGNED NOT NULL,
    nom VARCHAR(255) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT societe_suivi_documents_ibfk_1
        FOREIGN KEY (etape_id) REFERENCES societe_suivi_etapes(id)
        ON DELETE CASCADE,
    INDEX etape_id (etape_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cession_suivi_etapes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cession_id INT UNSIGNED NOT NULL,
    etape VARCHAR(80) NOT NULL,
    ordre INT UNSIGNED NOT NULL DEFAULT 0,
    statut ENUM('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT cession_suivi_etapes_ibfk_1
        FOREIGN KEY (cession_id) REFERENCES cessions(id)
        ON DELETE CASCADE,
    INDEX cession_id (cession_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cession_suivi_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etape_id INT UNSIGNED NOT NULL,
    nom VARCHAR(255) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT cession_suivi_documents_ibfk_1
        FOREIGN KEY (etape_id) REFERENCES cession_suivi_etapes(id)
        ON DELETE CASCADE,
    INDEX etape_id (etape_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS centre_affaires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    denomination VARCHAR(190) NOT NULL DEFAULT '',
    adresse VARCHAR(255) NOT NULL DEFAULT '',
    numero_if VARCHAR(50) NOT NULL DEFAULT '',
    numero_ice VARCHAR(50) NOT NULL DEFAULT '',
    numero_rc VARCHAR(50) NOT NULL DEFAULT '',
    numero_tp VARCHAR(50) NOT NULL DEFAULT '',
    numero_cnss VARCHAR(50) NOT NULL DEFAULT '',
    adresse_dgi VARCHAR(255) NOT NULL DEFAULT '',
    adresse_cnss VARCHAR(255) NOT NULL DEFAULT '',
    logo_path VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_formes_juridiques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    forme_juridique VARCHAR(120) NOT NULL,
    template_folder VARCHAR(120) DEFAULT '' NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_formes_juridiques (forme_juridique)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_ste_adresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ste_adresse VARCHAR(255) NOT NULL,
    ville VARCHAR(100) NOT NULL DEFAULT '',
    code_postal VARCHAR(20) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_ste_adresses (ste_adresse, ville)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_villes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ville VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_villes (ville)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_tribunaux (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tribunal VARCHAR(120) NOT NULL,
    tribunal_type VARCHAR(60) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_tribunaux (tribunal, tribunal_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites (activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_activites_ompic (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_activites_ompic_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_nationalites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nationalite VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_nationalites (nationalite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_lieux_naissance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lieu_naissance VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_lieux_naissance (lieu_naissance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_qualites_associe (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qualite_associe VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_qualites_associe (qualite_associe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ref_fonctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fonction VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_fonctions (fonction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    is_internal TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permissions_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    KEY fk_rp_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collaborateur_permissions (
    collaborateur_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    granted TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (collaborateur_id, permission_id),
    CONSTRAINT fk_cp_collaborateur FOREIGN KEY (collaborateur_id) REFERENCES collaborateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    KEY fk_cp_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    user_nom VARCHAR(255) DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    entity_label VARCHAR(255) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_email_time (email, attempted_at),
    KEY idx_login_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS _migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_migrations_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;