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
    activites_statuts TEXT DEFAULT NULL,
    activites_ompic TEXT DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS ref_qualites_associe (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qualite_associe VARCHAR(150) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_qualites_associe (qualite_associe)
);

CREATE TABLE IF NOT EXISTS ref_ste_adresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ste_adresse VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_ref_ste_adresses (ste_adresse)
);

CREATE TABLE IF NOT EXISTS ref_villes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ville VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_ref_villes (ville)
);

CREATE TABLE IF NOT EXISTS ref_tribunaux (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tribunal VARCHAR(120) NOT NULL,
    tribunal_type VARCHAR(60) DEFAULT NULL,
    UNIQUE KEY uq_ref_tribunaux (tribunal, tribunal_type)
);

CREATE TABLE IF NOT EXISTS ref_activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
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
    UNIQUE KEY uq_ref_nationalites (nationalite)
);

CREATE TABLE IF NOT EXISTS ref_lieux_naissance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lieu_naissance VARCHAR(120) NOT NULL,
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
    UNIQUE KEY uq_ref_qualites_associe (qualite_associe)
);
USE `center_domiciliation`;

-- Données de référence pour les formes juridiques
INSERT INTO ref_formes_juridiques (forme_juridique) VALUES
('SARL AU'),
('SARL'),
('Personne Physique'),
('SA'),
('Succurssale Etrangère'),
('Succurssale Marocaine');

-- Données de référence pour les tribunaux
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

-- Données de référence pour les adresses
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

-- Données de référence pour les villes
INSERT INTO ref_villes (ville) VALUES
('Agadir'),
('Ait Melloul'),
('Al Hoceima'),
('Asilah'),
('Azemmour'),
('Azrou'),
('Beni Mellal'),
('Beni Ansar'),
('Berrechid'),
('Berkane'),
('Boujdour'),
('Boulemane'),
('Casablanca'),
('Chefchaouen'),
('Chichaoua'),
('Dakhla'),
('El Hajeb'),
('El Jadida'),
('El Kelaa Des Sraghna'),
('Errachidia'),
('Essaouira'),
('Fes'),
('Figuig'),
('Fnideq'),
('Guelmim'),
('Guercif'),
('Ifrane'),
('Inezgane'),
('Jerada'),
('Kelaat Mgouna'),
('Khemisset'),
('Khenifra'),
('Khouribga'),
('Ksar El Kebir'),
('Laayoune'),
('Larache'),
('Marrakech'),
('Martil'),
('Meknes'),
('Midelt'),
('Mohammedia'),
('Nador'),
('Ouarzazate'),
('Ouezzane'),
('Oujda'),
('Oulad Teima'),
('Rabat'),
('Safi'),
('Sale'),
('Sefrou'),
('Settat'),
('Sidi Bennour'),
('Sidi Ifni'),
('Sidi Kacem'),
('Sidi Slimane'),
('Skhirat'),
('Souk El Arbaa'),
('Tanger'),
('Tan-Tan'),
('Taourirt'),
('Taroudant'),
('Tata'),
('Taza'),
('Temara'),
('Tetouan'),
('Tiflet'),
('Tinghir'),
('Tiznit'),
('Youssoufia'),
('Zagora');

-- Données de référence pour les nationalités
INSERT INTO ref_nationalites (nationalite) VALUES
('Marocaine'),
('Française'),
('Belge'),
('Suisse'),
('Allemande'),
('Italienne'),
('Espagnole'),
('Portugaise'),
('Britannique'),
('Américaine'),
('Canadienne'),
('Algérienne'),
('Tunisienne'),
('Sénégalaise'),
('Camerounaise'),
('Gabonaise'),
('Ivoirienne'),
('Congolaise'),
('Guinéenne'),
('Malienne');

-- Données de référence pour les lieux de naissance
INSERT INTO ref_lieux_naissance (lieu_naissance) VALUES
('Casablanca'),
('Rabat'),
('Marrakech'),
('Fes'),
('Agadir'),
('Tangier'),
('Meknes'),
('Tetouan'),
('Oujda'),
('Beni Mellal'),
('Khouribga'),
('Essaouira'),
('Safi'),
('Azemmour'),
('Ouezzane'),
('Sefrou'),
('Taza'),
('Nador'),
('Hoceima'),
('Driouch');

-- Données de référence pour les qualités d'associé
INSERT INTO ref_qualites_associe (qualite_associe) VALUES
('Gerant'),
('Associe unique'),
('Associe majoritaire'),
('Associe minoritaire'),
('President'),
('Directeur General'),
('Actionnaire'),
('Porteur de parts');

-- Données de référence pour les activités
INSERT INTO ref_activites (activite) VALUES
('Commerce de gros'),
('Commerce de detail'),
('Restauration'),
('Hotel'),
('Transport'),
('Logistique'),
('Consulting'),
('Services IT'),
('Services de sante'),
('Education'),
('Immobilier'),
('Construction'),
('Manufacture'),
('Agriculture'),
('Peche'),
('Energie'),
('Telecommunications'),
('Banque et Finance'),
('Assurance'),
('Tourisme');

INSERT INTO ref_activites_ompic (code, libelle) VALUES
('A', 'AGRICULTURE, SYLVICULTURE ET PECHE'),
('B', 'INDUSTRIES EXTRACTIVES'),
('C', 'INDUSTRIE MANUFACTURIERE'),
('D', 'PRODUCTION ET DISTRIBUTION D''ELECTRICITE, DE GAZ, DE VAPEUR ET D''AIR CONDITIONNE'),
('E', 'PRODUCTION ET DISTRIBUTION D''EAU; ASSAINISSEMENT, GESTION DES DECHETS ET DEPOLLUTION'),
('F', 'CONSTRUCTION'),
('G', 'COMMERCE; REPARATION D''AUTOMOBILES ET DE MOTOCYCLES'),
('H', 'TRANSPORT ET ENTREPOSAGE'),
('I', 'HEBERGEMENT ET RESTAURATION'),
('J', 'INFORMATION ET COMMUNICATION'),
('K', 'ACTIVITES FINANCIERES ET D''ASSURANCE'),
('L', 'ACTIVITES IMMOBILIERES'),
('M', 'ACTIVITES SPECIALISEES, SCIENTIFIQUES ET TECHNIQUES'),
('N', 'ACTIVITES DE SERVICES ADMINISTRATIFS ET DE SOUTIEN'),
('P', 'ENSEIGNEMENT'),
('Q', 'SANTE HUMAINE ET ACTION SOCIALE'),
('R', 'ARTS, SPECTACLES ET ACTIVITES RECREATIVES'),
('S', 'AUTRES ACTIVITES DE SERVICES'),
('46', 'Commerce de gros'),
('47', 'Commerce de detail'),
('49', 'Transports terrestres'),
('55', 'Hebergement'),
('56', 'Restauration'),
('58', 'Edition'),
('62', 'Programmation, conseil et autres activites informatiques'),
('68', 'Activites immobilieres'),
('69', 'Activites juridiques et comptables'),
('70', 'Activites des sieges sociaux; conseil de gestion'),
('71', 'Activites d''architecture et d''ingenierie'),
('73', 'Publicite et etudes de marche'),
('77', 'Activites de location et location-bail'),
('79', 'Agences de voyage'),
('85', 'Enseignement'),
('86', 'Activites pour la sante humaine'),
('93', 'Activites sportives, recreatives et de loisirs'),
('96', 'Autres services personnels'),
('4711', 'Commerce de detail alimentaire'),
('6201', 'Programmation informatique'),
('6202', 'Conseil informatique'),
('6910', 'Activites juridiques'),
('6920', 'Activites comptables'),
('7010', 'Activites des sieges sociaux'),
('7022', 'Conseil pour les affaires et autres conseils de gestion'),
('7111', 'Activites d''architecture'),
('7112', 'Activites d''ingenierie'),
('7311', 'Activites des agences de publicite'),
('8299', 'Autres activites de soutien aux entreprises'),
('9602', 'Coiffure et soins de beaute'),
('9609', 'Autres services personnels');

INSERT INTO societes (
    dossier_domiciliation, raison_sociale, forme_juridique, ice, date_ice, rc, if_number,
    capital, part_social, valeur_nominale, date_exp_cert_neg, adresse, ste_adress, ville, tribunal, email,
    telephone, type_generation, procedure_creation, mode_depot_creation
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
    societe_id, nom_complet, cin, date_naiss, lieu_naiss, nationalite, adresse, phone, email,
    qualite_associe, parts, is_gerant
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
    societe_id, type_contrat, date_contrat, duree_contrat_mois, type_contrat_domiciliation,
    type_contrat_domiciliation_autre, date_debut, date_fin,
    loyer_mensuel_ttc, frais_intermediaire_contrat, caution_montant, taux_tva_pourcent, loyer_mensuel_ht,
    montant_total_ht_contrat, montant_pack_demarrage_ttc, loyer_mensuel_pack_demarrage_ttc, type_renouvellement,
    taux_tva_renouvellement_pourcent, loyer_mensuel_ht_renouvellement, montant_total_ht_renouvellement,
    loyer_mensuel_renouvellement_ttc, loyer_annuel_renouvellement_ttc, statut, notes
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
    'Atlas Domiciliation', 'Nadia Chraibi', 'Gestion administrative', 'EXP -- Expert Comptable', 'EXP',
    'Nadia Chraibi', 'ICE-COL-001', 'TP001', 'RC-C001', 'IF-C001', '0522000001', '+212600000010',
    'Casablanca', 'nadia@atlas.test', 'nadia@atlas.test', '+212600000010', '2026-01-05', 'actif',
    'Suivi dossiers clients'
),
(
    NULL, 'Karim Tazi', 'Support operationnel', 'CLTD -- Client Direct', 'CLTD',
    'Karim Tazi', 'ICE-COL-002', 'TP002', 'RC-C002', 'IF-C002', '0522000002', '+212600000011',
    'Casablanca', 'karim@center.test', 'karim@center.test', '+212600000011', '2026-02-01', 'actif',
    'Appui polyvalent'
);

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

INSERT IGNORE INTO ref_activites_ompic (code, libelle) VALUES
('F', 'CONSTRUCTION'),
('G', 'COMMERCE; REPARATION D''AUTOMOBILES ET DE MOTOCYCLES'),
('M', 'ACTIVITES SPECIALISEES, SCIENTIFIQUES ET TECHNIQUES'),
('N', 'ACTIVITES DE SERVICES ADMINISTRATIFS ET DE SOUTIEN'),
('46', 'Commerce de gros'),
('47', 'Commerce de detail'),
('68', 'Activites immobilieres'),
('70', 'Activites des sieges sociaux; conseil de gestion');

INSERT IGNORE INTO ref_nationalites (nationalite) VALUES
('Marocaine'),
('Cameronnie');

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
