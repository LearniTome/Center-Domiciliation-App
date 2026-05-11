CREATE DATABASE IF NOT EXISTS `center_domiciliation`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `center_domiciliation`;

CREATE TABLE IF NOT EXISTS societes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dossier_domiciliation VARCHAR(120) DEFAULT NULL,
    raison_sociale VARCHAR(255) NOT NULL,
    den_ste VARCHAR(255) DEFAULT NULL,
    forme_juridique VARCHAR(120) DEFAULT NULL,
    forme_jur VARCHAR(120) DEFAULT NULL,
    ice VARCHAR(100) DEFAULT NULL,
    date_ice DATE DEFAULT NULL,
    rc VARCHAR(100) DEFAULT NULL,
    if_number VARCHAR(100) DEFAULT NULL,
    capital DECIMAL(15,2) DEFAULT NULL,
    part_social INT DEFAULT NULL,
    valeur_nominale DECIMAL(15,2) DEFAULT NULL,
    date_exp_cert_neg DATE DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    ste_adress TEXT DEFAULT NULL,
    ville VARCHAR(120) DEFAULT NULL,
    tribunal VARCHAR(120) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    telephone VARCHAR(60) DEFAULT NULL,
    type_generation VARCHAR(120) DEFAULT NULL,
    procedure_creation VARCHAR(120) DEFAULT NULL,
    mode_depot_creation VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS associes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    den_ste VARCHAR(255) DEFAULT NULL,
    civil VARCHAR(50) DEFAULT NULL,
    prenom VARCHAR(120) DEFAULT NULL,
    nom VARCHAR(120) DEFAULT NULL,
    nom_complet VARCHAR(255) NOT NULL,
    cin VARCHAR(100) DEFAULT NULL,
    cin_num VARCHAR(100) DEFAULT NULL,
    cin_validaty DATE DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    date_naiss DATE DEFAULT NULL,
    lieu_naiss VARCHAR(120) DEFAULT NULL,
    nationalite VARCHAR(120) DEFAULT NULL,
    nationality VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    qualite_associe VARCHAR(150) DEFAULT NULL,
    qualite_gerant VARCHAR(150) DEFAULT NULL,
    part_percent DECIMAL(7,2) DEFAULT NULL,
    parts INT DEFAULT NULL,
    capital_detenu DECIMAL(15,2) DEFAULT NULL,
    is_gerant TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_associes_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contrats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    societe_id INT UNSIGNED NOT NULL,
    den_ste VARCHAR(255) DEFAULT NULL,
    type_contrat VARCHAR(120) NOT NULL,
    date_contrat DATE DEFAULT NULL,
    duree_contrat_mois INT DEFAULT NULL,
    type_contrat_domiciliation VARCHAR(120) DEFAULT NULL,
    type_contrat_domiciliation_autre VARCHAR(190) DEFAULT NULL,
    date_debut DATE DEFAULT NULL,
    date_debut_contrat DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    date_fin_contrat DATE DEFAULT NULL,
    loyer_mensuel_ttc DECIMAL(15,2) DEFAULT NULL,
    frais_intermediaire_contrat DECIMAL(15,2) DEFAULT NULL,
    caution_montant DECIMAL(15,2) DEFAULT NULL,
    taux_tva_pourcent DECIMAL(7,2) DEFAULT NULL,
    loyer_mensuel_ht DECIMAL(15,2) DEFAULT NULL,
    montant_total_ht_contrat DECIMAL(15,2) DEFAULT NULL,
    montant_pack_demarrage_ttc DECIMAL(15,2) DEFAULT NULL,
    loyer_mensuel_pack_demarrage_ttc DECIMAL(15,2) DEFAULT NULL,
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
        ON DELETE CASCADE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_collaborateurs_societe
        FOREIGN KEY (societe_id) REFERENCES societes(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ref_ste_adresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ste_adresse VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_ref_ste_adresses (ste_adresse)
);

CREATE TABLE IF NOT EXISTS ref_tribunaux (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tribunal VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_ref_tribunaux (tribunal)
);

CREATE TABLE IF NOT EXISTS ref_activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activite VARCHAR(190) NOT NULL,
    UNIQUE KEY uq_ref_activites (activite)
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

INSERT INTO societes (
    dossier_domiciliation, raison_sociale, den_ste, forme_juridique, forme_jur, ice, date_ice, rc, if_number,
    capital, part_social, valeur_nominale, date_exp_cert_neg, adresse, ste_adress, ville, tribunal, email,
    telephone, type_generation, procedure_creation, mode_depot_creation
) VALUES
('DOM-2026-001', 'Atlas Domiciliation', 'Atlas Domiciliation', 'SARL', 'SARL', '001122334455667', '2026-01-10', 'RC12345', 'IF778899', 100000.00, 100, 1000.00, '2026-12-31', '123 Boulevard Hassan II', '123 Boulevard Hassan II', 'Casablanca', 'Casablanca', 'contact@atlas.test', '+212600000001', 'Standard', 'Creation', 'Electronique'),
('DOM-2026-002', 'Maghreb Services', 'Maghreb Services', 'SARL AU', 'SARL AU', '998877665544332', '2026-03-15', 'RC54321', 'IF665544', 50000.00, 100, 500.00, '2027-03-14', '45 Avenue Mohammed V', '45 Avenue Mohammed V', 'Rabat', 'Casablanca', 'admin@maghreb.test', '+212600000002', 'Standard', 'Creation', 'Physique');

INSERT INTO associes (
    societe_id, den_ste, civil, prenom, nom, nom_complet, cin, cin_num, cin_validaty, adresse, date_naiss,
    lieu_naiss, nationalite, nationality, phone, email, qualite_associe, qualite_gerant, part_percent, parts,
    capital_detenu, is_gerant
) VALUES
(1, 'Atlas Domiciliation', 'Monsieur', 'Youssef', 'El Idrissi', 'Youssef El Idrissi', 'BK123456', 'BK123456', '2030-12-31', 'Casablanca', '1990-01-01', 'Casablanca', 'Marocaine', 'Marocaine', '+212600000101', 'youssef@atlas.test', 'Associé majoritaire', 'Gérant associé', 60.00, 60, 60000.00, 1),
(1, 'Atlas Domiciliation', 'Madame', 'Salma', 'Bennani', 'Salma Bennani', 'BE654321', 'BE654321', '2031-11-30', 'Casablanca', '1992-04-10', 'Casablanca', 'Marocaine', 'Marocaine', '+212600000102', 'salma@atlas.test', 'Associé minoritaire', '', 40.00, 40, 40000.00, 0),
(2, 'Maghreb Services', 'Madame', 'Imane', 'Alaoui', 'Imane Alaoui', 'CD987654', 'CD987654', '2032-01-15', 'Rabat', '1988-09-15', 'Rabat', 'Marocaine', 'Marocaine', '+212600000103', 'imane@maghreb.test', 'Associé unique', 'Gérant unique', 100.00, 100, 50000.00, 1);

INSERT INTO contrats (
    societe_id, den_ste, type_contrat, date_contrat, duree_contrat_mois, type_contrat_domiciliation, type_contrat_domiciliation_autre,
    date_debut, date_debut_contrat, date_fin, date_fin_contrat, loyer_mensuel_ttc, frais_intermediaire_contrat, caution_montant,
    taux_tva_pourcent, loyer_mensuel_ht, montant_total_ht_contrat, montant_pack_demarrage_ttc, loyer_mensuel_pack_demarrage_ttc,
    type_renouvellement, taux_tva_renouvellement_pourcent, loyer_mensuel_ht_renouvellement, montant_total_ht_renouvellement,
    loyer_mensuel_renouvellement_ttc, loyer_annuel_renouvellement_ttc, statut, notes
) VALUES
(1, 'Atlas Domiciliation', 'Domiciliation commerciale', '2026-01-01', 12, 'Personne Morale', NULL, '2026-01-01', '2026-01-01', '2026-12-31', '2026-12-31', 1200.00, 300.00, 1200.00, 20.00, 1000.00, 12000.00, 1500.00, 1250.00, 'Annuel', 20.00, 1000.00, 12000.00, 1200.00, 14400.00, 'actif', 'Contrat annuel standard'),
(2, 'Maghreb Services', 'Pack lancement', '2026-03-01', 12, 'Personne Morale', NULL, '2026-03-01', '2026-03-01', '2027-02-28', '2027-02-28', 900.00, 250.00, 900.00, 20.00, 750.00, 9000.00, 1000.00, 900.00, 'Annuel', 20.00, 750.00, 9000.00, 900.00, 10800.00, 'actif', 'Pack simplifie');

INSERT INTO collaborateurs (
    societe_id, den_ste, nom_complet, fonction, collaborateur_type, collaborateur_code, collaborateur_nom,
    collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if, collaborateur_tel_fixe,
    collaborateur_tel_mobile, collaborateur_adresse, collaborateur_email, email, telephone, date_debut, statut, notes
) VALUES
(1, 'Atlas Domiciliation', 'Nadia Chraibi', 'Gestion administrative', 'EXP -- Expert Comptable', 'EXP', 'Nadia Chraibi', 'ICE-COL-001', 'TP001', 'RC-C001', 'IF-C001', '0522000001', '+212600000010', 'Casablanca', 'nadia@atlas.test', 'nadia@atlas.test', '+212600000010', '2026-01-05', 'actif', 'Suivi dossiers clients'),
(NULL, NULL, 'Karim Tazi', 'Support operationnel', 'CLTD -- Client Direct', 'CLTD', 'Karim Tazi', 'ICE-COL-002', 'TP002', 'RC-C002', 'IF-C002', '0522000002', '+212600000011', 'Casablanca', 'karim@center.test', 'karim@center.test', '+212600000011', '2026-02-01', 'actif', 'Appui polyvalent');

INSERT IGNORE INTO ref_ste_adresses (ste_adresse) VALUES
('HAY MOULAY ABDELLAH RUE 300 N 152 ETG 2 AIN CHOCK, CASABLANCA'),
('46 BD ZERKTOUNI ETG 2 APPT 6 CASABLANCA'),
('56 BOULEVARD MOULAY YOUSSEF 3EME ETAGE APPT 14, CASABLANCA');

INSERT IGNORE INTO ref_tribunaux (tribunal) VALUES
('Casablanca'),
('Berrechid'),
('Mohammedia');

INSERT IGNORE INTO ref_activites (activite) VALUES
('Travaux Divers ou de Construction'),
('Marchand effectuant Import Export'),
('Négociant'),
('Conseil de Gestion');

INSERT IGNORE INTO ref_nationalites (nationalite) VALUES
('Marocaine'),
('Cameronnie');

INSERT IGNORE INTO ref_lieux_naissance (lieu_naissance) VALUES
('Casablanca'),
('Rabat'),
('Mohammedia');
