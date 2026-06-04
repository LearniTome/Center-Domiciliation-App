CREATE DATABASE IF NOT EXISTS `center_domiciliation`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `center_domiciliation`;

-- ============================================================
-- SCHEMA
-- ============================================================

CREATE TABLE IF NOT EXISTS societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_dossier VARCHAR(120) DEFAULT NULL,
    societe_raison_sociale VARCHAR(255) NOT NULL,
    den_ste VARCHAR(255) DEFAULT NULL,
    societe_forme_juridique VARCHAR(120) DEFAULT NULL,
    societe_ice VARCHAR(100) DEFAULT NULL,
    societe_date_ice DATE DEFAULT NULL,
    societe_rc VARCHAR(100) DEFAULT NULL,
    societe_if VARCHAR(100) DEFAULT NULL,
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
    INDEX idx_collaborateurs_nom (nom_complet),
    INDEX idx_collaborateurs_role_id (role_id),
    INDEX idx_collaborateurs_can_login (can_login)
);

CREATE TABLE IF NOT EXISTS ref_formes_juridiques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    forme_juridique VARCHAR(120) NOT NULL,
    template_folder VARCHAR(120) DEFAULT '' NOT NULL,
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
);

CREATE TABLE IF NOT EXISTS ref_qualites_associe (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qualite_associe VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_qualites_associe (qualite_associe)
);

-- RBAC Tables
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
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collaborateur_permissions (
    collaborateur_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    granted TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (collaborateur_id, permission_id),
    CONSTRAINT fk_cp_collaborateur FOREIGN KEY (collaborateur_id) REFERENCES collaborateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
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

-- ============================================================
-- SEED DATA
-- ============================================================

-- Formes juridiques
INSERT INTO ref_formes_juridiques (forme_juridique, template_folder) VALUES
('SARL AU', 'SARL AU'),
('SARL', 'SARL'),
('Personne Physique', ''),
('SA', 'SA'),
('Succurssale Etrangère', ''),
('Succurssale Marocaine', '');

-- Tribunaux
INSERT INTO ref_tribunaux (tribunal, tribunal_type) VALUES
('Casablanca', 'Tribunal de commerce'),
('Rabat', 'Tribunal de commerce'),
('Marrakech', 'Tribunal de commerce'),
('Fes', 'Tribunal de commerce'),
('Agadir', 'Tribunal de commerce'),
('Tangier', 'Tribunal de commerce'),
('Meknes', 'Tribunal de commerce'),
('Tetouan', 'Tribunal de commerce'),
('Oujda', 'Tribunal de commerce'),
('Beni Mellal', 'Tribunal de commerce'),
('Khouribga', 'Tribunal de commerce'),
('Settat', 'Tribunal de commerce'),
('Casablanca', 'Tribunal de Première Instance'),
('Rabat', 'Tribunal de Première Instance'),
('Marrakech', 'Tribunal de Première Instance'),
('Fes', 'Tribunal de Première Instance'),
('Agadir', 'Tribunal de Première Instance'),
('Tangier', 'Tribunal de Première Instance'),
('Meknes', 'Tribunal de Première Instance'),
('Tetouan', 'Tribunal de Première Instance'),
('Oujda', 'Tribunal de Première Instance'),
('Beni Mellal', 'Tribunal de Première Instance'),
('Khouribga', 'Tribunal de Première Instance'),
('Oulad Teima', 'Tribunal de Première Instance'),
('Settat', 'Tribunal de Première Instance'),
('Khemisset', 'Tribunal de Première Instance'),
('Tiflet', 'Tribunal de Première Instance'),
('Skhirat-Temara', 'Tribunal de Première Instance'),
('Sidi Kacem', 'Tribunal de Première Instance'),
('Sidi Slimane', 'Tribunal de Première Instance'),
('Souk El Arbaa', 'Tribunal de Première Instance'),
('Taourirt', 'Tribunal de Première Instance');

-- Adresses
INSERT INTO ref_ste_adresses (ste_adresse) VALUES
('123 Boulevard Hassan II'),
('45 Avenue Mohammed V'),
('12 Rue Dar El Baraka'),
('78 Avenue des FAR'),
('34 Rue Ghandouri'),
('56 Boulevard de la Corniche'),
('89 Place de la Concordance'),
('11 Rue Ibn Sina'),
('25 Avenue de Marrakech'),
('67 Boulevard de Paris'),
('43 Route de Meknes'),
('55 Boulevard Allal El Fassi'),
('88 Rue Ahmed Chaouki'),
('22 Avenue Hassan II (Downtown)'),
('99 Boulevard Moulay Ismail');

-- Villes
INSERT INTO ref_villes (ville) VALUES
('Agadir'), ('Ait Melloul'), ('Al Hoceima'), ('Asilah'), ('Azemmour'),
('Azrou'), ('Beni Mellal'), ('Beni Ansar'), ('Berrechid'), ('Berkane'),
('Boujdour'), ('Boulemane'), ('Casablanca'), ('Chefchaouen'), ('Chichaoua'),
('Dakhla'), ('El Hajeb'), ('El Jadida'), ('El Kelaa Des Sraghna'), ('Errachidia'),
('Essaouira'), ('Fes'), ('Figuig'), ('Fnideq'), ('Guelmim'),
('Guercif'), ('Ifrane'), ('Inezgane'), ('Jerada'), ('Kelaat Mgouna'),
('Khemisset'), ('Khenifra'), ('Khouribga'), ('Ksar El Kebir'), ('Laayoune'),
('Larache'), ('Marrakech'), ('Martil'), ('Meknes'), ('Midelt'),
('Mohammedia'), ('Nador'), ('Ouarzazate'), ('Ouezzane'), ('Oujda'),
('Oulad Teima'), ('Rabat'), ('Safi'), ('Sale'), ('Sefrou'),
('Settat'), ('Sidi Bennour'), ('Sidi Ifni'), ('Sidi Kacem'), ('Sidi Slimane'),
('Skhirat'), ('Souk El Arbaa'), ('Tanger'), ('Tan-Tan'), ('Taourirt'),
('Taroudant'), ('Tata'), ('Taza'), ('Temara'), ('Tetouan'),
('Tiflet'), ('Tinghir'), ('Tiznit'), ('Youssoufia'), ('Zagora');

-- Nationalites
INSERT INTO ref_nationalites (nationalite) VALUES
('Marocaine'), ('Française'), ('Belge'), ('Suisse'), ('Allemande'),
('Italienne'), ('Espagnole'), ('Portugaise'), ('Britannique'), ('Américaine'),
('Canadienne'), ('Algérienne'), ('Tunisienne'), ('Sénégalaise'), ('Camerounaise'),
('Gabonaise'), ('Ivoirienne'), ('Congolaise'), ('Guinéenne'), ('Malienne');

-- Lieux de naissance
INSERT INTO ref_lieux_naissance (lieu_naissance) VALUES
('Casablanca'), ('Rabat'), ('Marrakech'), ('Fes'), ('Agadir'),
('Tangier'), ('Meknes'), ('Tetouan'), ('Oujda'), ('Beni Mellal'),
('Khouribga'), ('Essaouira'), ('Safi'), ('Azemmour'), ('Ouezzane'),
('Sefrou'), ('Taza'), ('Nador'), ('Hoceima'), ('Driouch');

-- Qualites associe
INSERT INTO ref_qualites_associe (qualite_associe) VALUES
('Gerant'), ('Associe unique'), ('Associe majoritaire'), ('Associe minoritaire'),
('President'), ('Directeur General'), ('Actionnaire'), ('Porteur de parts');

-- Activites
INSERT INTO ref_activites (activite) VALUES
('Commerce de gros'), ('Commerce de detail'), ('Restauration'), ('Hotel'),
('Transport'), ('Logistique'), ('Consulting'), ('Services IT'),
('Services de sante'), ('Education'), ('Immobilier'), ('Construction'),
('Manufacture'), ('Agriculture'), ('Peche'), ('Energie'),
('Telecommunications'), ('Banque et Finance'), ('Assurance'), ('Tourisme');

INSERT INTO ref_activites_ompic (code, libelle, sort_order) VALUES
('A', 'AGRICULTURE, SYLVICULTURE ET PECHE', 1),
('B', 'INDUSTRIES EXTRACTIVES', 2),
('C', 'INDUSTRIE MANUFACTURIERE', 3),
('D', 'PRODUCTION ET DISTRIBUTION D''ELECTRICITE, DE GAZ, DE VAPEUR ET D''AIR CONDITIONNE', 4),
('E', 'PRODUCTION ET DISTRIBUTION D''EAU; ASSAINISSEMENT, GESTION DES DECHETS ET DEPOLLUTION', 5),
('F', 'CONSTRUCTION', 6),
('G', 'COMMERCE; REPARATION D''AUTOMOBILES ET DE MOTOCYCLES', 7),
('H', 'TRANSPORT ET ENTREPOSAGE', 8),
('I', 'HEBERGEMENT ET RESTAURATION', 9),
('J', 'INFORMATION ET COMMUNICATION', 10),
('K', 'ACTIVITES FINANCIERES ET D''ASSURANCE', 11),
('L', 'ACTIVITES IMMOBILIERES', 12),
('M', 'ACTIVITES SPECIALISEES, SCIENTIFIQUES ET TECHNIQUES', 13),
('N', 'ACTIVITES DE SERVICES ADMINISTRATIFS ET DE SOUTIEN', 14),
('P', 'ENSEIGNEMENT', 15),
('Q', 'SANTE HUMAINE ET ACTION SOCIALE', 16),
('R', 'ARTS, SPECTACLES ET ACTIVITES RECREATIVES', 17),
('S', 'AUTRES ACTIVITES DE SERVICES', 18),
('46', 'Commerce de gros', 19),
('47', 'Commerce de detail', 20),
('49', 'Transports terrestres', 21),
('55', 'Hebergement', 22),
('56', 'Restauration', 23),
('58', 'Edition', 24),
('62', 'Programmation, conseil et autres activites informatiques', 25),
('68', 'Activites immobilieres', 26),
('69', 'Activites juridiques et comptables', 27),
('70', 'Activites des sieges sociaux; conseil de gestion', 28),
('71', 'Activites d''architecture et d''ingenierie', 29),
('73', 'Publicite et etudes de marche', 30),
('77', 'Activites de location et location-bail', 31),
('79', 'Agences de voyage', 32),
('85', 'Enseignement', 33),
('86', 'Activites pour la sante humaine', 34),
('93', 'Activites sportives, recreatives et de loisirs', 35),
('96', 'Autres services personnels', 36),
('4711', 'Commerce de detail alimentaire', 37),
('6201', 'Programmation informatique', 38),
('6202', 'Conseil informatique', 39),
('6910', 'Activites juridiques', 40),
('6920', 'Activites comptables', 41),
('7010', 'Activites des sieges sociaux', 42),
('7022', 'Conseil pour les affaires et autres conseils de gestion', 43),
('7111', 'Activites d''architecture', 44),
('7112', 'Activites d''ingenierie', 45),
('7311', 'Activites des agences de publicite', 46),
('8299', 'Autres activites de soutien aux entreprises', 47),
('9602', 'Coiffure et soins de beaute', 48),
('9609', 'Autres services personnels', 49);

-- RBAC : Roles
INSERT IGNORE INTO roles (id, nom, description, is_internal, is_system, sort_order) VALUES
(1,  'Super Admin',       'Accès total au système',                         1, 1, 1),
(2,  'Admin',             'Administrateur avec presque tous les droits',    1, 0, 2),
(3,  'Chef d équipes',    'Gère les dossiers et son équipe',               1, 0, 3),
(4,  'Employé',           'Agent de traitement des dossiers',              1, 0, 4),
(5,  'Assistante',        'Support administratif et documentaire',          1, 0, 5),
(6,  'Stagiaire',         'Accès lecture seule',                           1, 0, 6),
(7,  'Expert-comptable',  'Expert-comptable externe',                      0, 0, 10),
(8,  'Comptable agréé',   'Comptable agréé externe',                       0, 0, 11),
(9,  'Commissaire aux comptes', 'Commissaire aux comptes',                 0, 0, 12),
(10, 'Coursier',          'Coursier / livreur',                             0, 0, 13),
(11, 'Avocat',            'Avocat externe',                                 0, 0, 14),
(12, 'Notaire',           'Notaire externe',                                0, 0, 15),
(13, 'Conseil juridique', 'Conseil juridique externe',                      0, 0, 16),
(14, 'Banque',            'Représentant bancaire',                          0, 0, 17),
(15, 'Assurance',         'Représentant assurance',                         0, 0, 18),
(16, 'Autre',             'Autre type de collaborateur',                    0, 0, 99);

-- RBAC : Permissions
INSERT IGNORE INTO permissions (id, nom, permission_key, category, description) VALUES
(1,  'Voir le tableau de bord',                       'dashboard.view',       'dashboard',    'Accéder au tableau de bord'),
(2,  'Voir les sociétés',                             'societes.view',        'societes',     'Consulter la liste et les fiches sociétés'),
(3,  'Créer une société',                             'societes.create',      'societes',     'Créer une nouvelle société'),
(4,  'Modifier une société',                          'societes.edit',        'societes',     'Modifier les informations d une société'),
(5,  'Supprimer une société',                         'societes.delete',      'societes',     'Supprimer une société'),
(6,  'Exporter les sociétés',                         'societes.export',      'societes',     'Exporter la liste des sociétés en CSV'),
(7,  'Voir les associés',                             'associes.view',        'associes',     'Consulter la liste des associés'),
(8,  'Créer un associé',                              'associes.create',      'associes',     'Ajouter un associé'),
(9,  'Modifier un associé',                           'associes.edit',        'associes',     'Modifier les informations d un associé'),
(10, 'Supprimer un associé',                          'associes.delete',      'associes',     'Supprimer un associé'),
(11, 'Exporter les associés',                         'associes.export',      'associes',     'Exporter la liste des associés en CSV'),
(12, 'Voir les contrats',                             'contrats.view',        'contrats',     'Consulter la liste des contrats'),
(13, 'Créer un contrat',                              'contrats.create',      'contrats',     'Ajouter un contrat'),
(14, 'Modifier un contrat',                           'contrats.edit',        'contrats',     'Modifier les informations d un contrat'),
(15, 'Supprimer un contrat',                          'contrats.delete',      'contrats',     'Supprimer un contrat'),
(16, 'Exporter les contrats',                         'contrats.export',      'contrats',     'Exporter la liste des contrats en CSV'),
(17, 'Voir les collaborateurs',                       'collaborateurs.view',       'collaborateurs', 'Consulter la liste des collaborateurs'),
(18, 'Créer un collaborateur',                        'collaborateurs.create',     'collaborateurs', 'Ajouter un collaborateur'),
(19, 'Modifier un collaborateur',                     'collaborateurs.edit',       'collaborateurs', 'Modifier les informations d un collaborateur'),
(20, 'Supprimer un collaborateur',                    'collaborateurs.delete',     'collaborateurs', 'Supprimer un collaborateur'),
(21, 'Exporter les collaborateurs',                   'collaborateurs.export',     'collaborateurs', 'Exporter la liste des collaborateurs en CSV'),
(22, 'Utiliser l assistant de création',              'wizard.create',        'wizard',       'Accéder au wizard de création de dossier'),
(23, 'Voir les templates',                            'templates.view',       'templates',    'Consulter la liste des templates'),
(24, 'Créer un template',                             'templates.create',     'templates',    'Ajouter un nouveau template'),
(25, 'Modifier un template',                          'templates.edit',       'templates',    'Modifier un template existant'),
(26, 'Supprimer un template',                         'templates.delete',     'templates',    'Supprimer un template'),
(27, 'Utiliser le générateur de dossiers',            'generation.use',       'generation',   'Générer les documents d un dossier'),
(28, 'Voir les documents générés',                    'documents.view',       'documents',    'Consulter la liste des documents'),
(29, 'Télécharger les documents',                     'documents.download',   'documents',    'Télécharger les fichiers générés'),
(30, 'Voir la configuration',                         'configuration.view',   'configuration','Accéder à la page de configuration'),
(31, 'Modifier la configuration',                     'configuration.edit',   'configuration','Modifier les données de configuration'),
(32, 'Voir l analyse de couverture',                  'analyse.view',         'analyse',      'Accéder à l analyse de couverture'),
(33, 'Voir les variables',                            'variables.view',       'variables',    'Consulter la gestion des variables'),
(34, 'Modifier les variables',                        'variables.edit',       'variables',    'Renommer et supprimer des variables'),
(35, 'Modifier les valeurs par défaut',               'defaults.edit',        'defaults',     'Configurer les valeurs par défaut'),
(36, 'Utiliser la conversion Word → PDF',             'convert.use',          'convert',      'Accéder à l outil de conversion'),
(37, 'Utiliser l assistant IA',                       'ai.use',               'ai',           'Accéder à l assistant IA'),
(38, 'Gérer les rôles et permissions',                'roles.manage',         'roles',        'Créer, modifier et supprimer des rôles');

-- RBAC : Role-Permission assignments
SET @super_admin = 1, @admin = 2, @chef = 3, @employe = 4, @assistante = 5, @stagiaire = 6,
    @expert = 7, @comptable = 8, @commissaire = 9, @coursier = 10, @avocat = 11,
    @notaire = 12, @conseil = 13, @banque = 14, @assurance = 15, @autre = 16;

DELETE FROM role_permissions;

-- Super Admin: ALL permissions (1-38)
INSERT INTO role_permissions (role_id, permission_id)
SELECT @super_admin, id FROM permissions;

-- Admin: all except roles.manage (38)
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin, id FROM permissions WHERE id < 38;

-- Chef d équipes
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@chef, 1),
(@chef, 2), (@chef, 3), (@chef, 4), (@chef, 5), (@chef, 6),
(@chef, 7), (@chef, 8), (@chef, 9), (@chef, 10), (@chef, 11),
(@chef, 12), (@chef, 13), (@chef, 14), (@chef, 15), (@chef, 16),
(@chef, 17),
(@chef, 22),
(@chef, 23),
(@chef, 27),
(@chef, 28), (@chef, 29),
(@chef, 32),
(@chef, 33);

-- Employé
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@employe, 1),
(@employe, 2), (@employe, 3), (@employe, 4),
(@employe, 7), (@employe, 8), (@employe, 9),
(@employe, 12), (@employe, 13), (@employe, 14),
(@employe, 22),
(@employe, 27),
(@employe, 28), (@employe, 29);

-- Assistante
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@assistante, 1),
(@assistante, 2), (@assistante, 3),
(@assistante, 7), (@assistante, 8),
(@assistante, 12), (@assistante, 13),
(@assistante, 22),
(@assistante, 23),
(@assistante, 27),
(@assistante, 28), (@assistante, 29);

-- Stagiaire
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@stagiaire, 1),
(@stagiaire, 2),
(@stagiaire, 7),
(@stagiaire, 12),
(@stagiaire, 23),
(@stagiaire, 28);

-- Expert-comptable
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@expert, 1),
(@expert, 2),
(@expert, 7),
(@expert, 12),
(@expert, 28), (@expert, 29);

-- Comptable agréé
INSERT INTO role_permissions (role_id, permission_id)
SELECT @comptable, permission_id FROM role_permissions WHERE role_id = @expert;

-- Commissaire aux comptes
INSERT INTO role_permissions (role_id, permission_id)
SELECT @commissaire, permission_id FROM role_permissions WHERE role_id = @expert;

-- Coursier
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@coursier, 1);

-- Avocat
INSERT INTO role_permissions (role_id, permission_id) VALUES
(@avocat, 1),
(@avocat, 2),
(@avocat, 12),
(@avocat, 28), (@avocat, 29);

-- Notaire
INSERT INTO role_permissions (role_id, permission_id)
SELECT @notaire, permission_id FROM role_permissions WHERE role_id = @avocat;

-- Conseil juridique
INSERT INTO role_permissions (role_id, permission_id)
SELECT @conseil, permission_id FROM role_permissions WHERE role_id = @avocat;

-- Banque, Assurance, Autre
INSERT INTO role_permissions (role_id, permission_id)
VALUES (@banque, 1), (@assurance, 1), (@autre, 1);

-- ============================================================
-- DEMO DATA (Societes, Associes, Contrats, Collaborateurs)
-- ============================================================

INSERT INTO societes (
    societe_dossier, societe_raison_sociale, societe_forme_juridique, societe_ice, societe_date_ice, societe_rc, societe_if,
    societe_capital, societe_part_social, societe_valeur_nominale, societe_date_exp_cert_neg, societe_adresse, societe_adresse_siege, societe_ville, societe_tribunal, societe_email,
    societe_telephone, societe_type_generation, societe_procedure_creation, societe_mode_depot
) VALUES
(
    'DOM-2026-001', 'Atlas Domiciliation', 'SARL', '001122334455667', '2026-01-10',
    'RC12345', 'IF778899', 100000.00, 100, 1000.00, '2026-12-31',
    '123 Boulevard Hassan II', '123 Boulevard Hassan II', 'Casablanca', 'Casablanca',
    'contact@atlas.test', '+212600000001', 'Standard', 'Creation', 'Electronique'
),
(
    'DOM-2026-002', 'Maghreb Services', 'SARL AU', '998877665544332', '2026-03-15',
    'RC54321', 'IF665544', 50000.00, 100, 500.00, '2027-03-14',
    '45 Avenue Mohammed V', '45 Avenue Mohammed V', 'Rabat', 'Casablanca',
    'admin@maghreb.test', '+212600000002', 'Standard', 'Creation', 'Physique'
);

INSERT INTO associes (
    societe_id, associe_nom_complet, associe_cin, associe_date_naissance, associe_lieu_naissance, associe_nationalite, associe_adresse, associe_telephone, associe_email,
    associe_qualite, associe_parts, associe_est_gerant
) VALUES
(
    1, 'Youssef El Idrissi', 'BK123456', '1990-01-01', 'Casablanca', 'Marocaine', 'Casablanca',
    '+212600000101', 'youssef@atlas.test', 'Associé majoritaire', 60, 1
),
(
    1, 'Salma Bennani', 'BE654321', '1992-04-10', 'Casablanca', 'Marocaine', 'Casablanca',
    '+212600000102', 'salma@atlas.test', 'Associé minoritaire', 40, 0
),
(
    2, 'Imane Alaoui', 'CD987654', '1988-09-15', 'Rabat', 'Marocaine', 'Rabat',
    '+212600000103', 'imane@maghreb.test', 'Associé unique', 100, 1
);

INSERT INTO contrats (
    societe_id, contrat_type, contrat_date, contrat_duree_mois, contrat_type_domiciliation,
    contrat_type_domiciliation_autre, contrat_date_debut, contrat_date_fin,
    contrat_loyer_ttc, contrat_frais_intermediaire, contrat_caution, contrat_tva_pourcent, contrat_loyer_ht,
    contrat_total_ht, contrat_pack_montant_ttc, contrat_pack_loyer_ttc, contrat_type_renouvellement,
    contrat_renouv_tva_pourcent, contrat_renouv_loyer_ht, contrat_renouv_total_ht,
    contrat_renouv_loyer_ttc, contrat_renouv_annuel_ttc, contrat_statut, contrat_notes
) VALUES
(
    1, 'Domiciliation commerciale', '2026-01-01', 12, 'Personne Morale', NULL,
    '2026-01-01', '2026-12-31', 1200.00, 300.00, 1200.00, 20.00, 1000.00,
    12000.00, 1500.00, 1250.00, 'Annuel', 20.00, 1000.00, 12000.00, 1200.00, 14400.00, 'actif',
    'Contrat annuel standard'
),
(
    2, 'Pack lancement', '2026-03-01', 12, 'Personne Morale', NULL,
    '2026-03-01', '2027-02-28', 900.00, 250.00, 900.00, 20.00, 750.00,
    9000.00, 1000.00, 900.00, 'Annuel', 20.00, 750.00, 9000.00, 900.00, 10800.00, 'actif',
    'Pack simplifie'
);

INSERT INTO collaborateurs (
    den_ste, nom_complet, fonction, collaborateur_type, collaborateur_code, collaborateur_nom,
    collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if, collaborateur_tel_fixe,
    collaborateur_tel_mobile, collaborateur_adresse, collaborateur_email, email, telephone, date_debut, statut, notes
) VALUES
(
    'Atlas Domiciliation', 'Nadia Chraibi', 'Gestion administrative', 'externe-pm', 'EXP',
    'Nadia Chraibi', 'ICE-COL-001', 'TP001', 'RC-C001', 'IF-C001', '0522000001', '+212600000010',
    'Casablanca', 'nadia@atlas.test', 'nadia@atlas.test', '+212600000010', '2026-01-05', 'actif',
    'Suivi dossiers clients'
),
(
    NULL, 'Karim Tazi', 'Support operationnel', 'externe-pp', 'CLTD',
    'Karim Tazi', 'ICE-COL-002', 'TP002', 'RC-C002', 'IF-C002', '0522000002', '+212600000011',
    'Casablanca', 'karim@center.test', 'karim@center.test', '+212600000011', '2026-02-01', 'actif',
    'Appui polyvalent'
);

-- Super Admin collaborator (default password: admin123)
INSERT INTO collaborateurs (nom_complet, fonction, role_id, collaborateur_email, email, can_login, password_hash, statut, notes)
SELECT 'Super Admin', 'Administrateur système', @super_admin, 'admin@center.test', 'admin@center.test', 1,
       '$2y$10$QOZo9.7oOayIbJEsGwRxLuuS6BvQ9rJT6oX1rAsQoFG4cAvwyHZBG', 'actif',
       'Compte super admin par defaut'
WHERE NOT EXISTS (SELECT 1 FROM collaborateurs WHERE email = 'admin@center.test' OR collaborateur_email = 'admin@center.test');

INSERT IGNORE INTO ref_ste_adresses (ste_adresse) VALUES
('HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA'),
('46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA'),
('56 BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA');

INSERT IGNORE INTO ref_tribunaux (tribunal, tribunal_type) VALUES
('Casablanca', 'Tribunal de commerce'),
('Berrechid', 'Tribunal de commerce'),
('Mohammedia', 'Tribunal de commerce'),
('Berrechid', 'Tribunal de Première Instance'),
('Mohammedia', 'Tribunal de Première Instance');

INSERT IGNORE INTO ref_activites (activite) VALUES
('Travaux Divers ou de Construction'),
('Marchand effectuant Import Export'),
('Négociant'),
('Conseil de Gestion');

INSERT IGNORE INTO ref_activites_ompic (code, libelle, sort_order) VALUES
('F', 'CONSTRUCTION', 6),
('46', 'Commerce de gros', 19),
('47', 'Commerce de detail', 20),
('68', 'Activites immobilieres', 26),
('70', 'Activites des sieges sociaux; conseil de gestion', 28);

INSERT IGNORE INTO ref_nationalites (nationalite) VALUES
('Marocaine');

INSERT IGNORE INTO ref_lieux_naissance (lieu_naissance) VALUES
('Casablanca'),
('Rabat'),
('Mohammedia');

INSERT IGNORE INTO ref_villes (ville) VALUES
('Casablanca'),
('Rabat');

INSERT IGNORE INTO ref_qualites_associe (qualite_associe) VALUES
('Gerant'),
('Associe unique');
