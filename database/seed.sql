USE `center_domiciliation`;

INSERT INTO societes (
    dossier_domiciliation, raison_sociale, den_ste, forme_juridique, forme_jur, ice, date_ice, rc, if_number,
    capital, part_social, valeur_nominale, date_exp_cert_neg, adresse, ste_adress, ville, tribunal, email,
    telephone, type_generation, procedure_creation, mode_depot_creation
) VALUES
(
    'DOM-2026-001', 'Atlas Domiciliation', 'Atlas Domiciliation', 'SARL', 'SARL', '001122334455667', '2026-01-10',
    'RC12345', 'IF778899', 100000.00, 100, 1000.00, '2026-12-31',
    '123 Boulevard Hassan II', '123 Boulevard Hassan II', 'Casablanca', 'Casablanca',
    'contact@atlas.test', '+212600000001', 'Standard', 'Creation', 'Electronique'
),
(
    'DOM-2026-002', 'Maghreb Services', 'Maghreb Services', 'SARL AU', 'SARL AU', '998877665544332', '2026-03-15',
    'RC54321', 'IF665544', 50000.00, 100, 500.00, '2027-03-14',
    '45 Avenue Mohammed V', '45 Avenue Mohammed V', 'Rabat', 'Casablanca',
    'admin@maghreb.test', '+212600000002', 'Standard', 'Creation', 'Physique'
);

INSERT INTO associes (
    societe_id, den_ste, civil, prenom, nom, nom_complet, cin, cin_num, cin_validaty, adresse, date_naiss,
    lieu_naiss, nationalite, nationality, phone, email, qualite_associe, qualite_gerant, part_percent, parts,
    capital_detenu, is_gerant
) VALUES
(
    1, 'Atlas Domiciliation', 'Monsieur', 'Youssef', 'El Idrissi', 'Youssef El Idrissi', 'BK123456', 'BK123456',
    '2030-12-31', 'Casablanca', '1990-01-01', 'Casablanca', 'Marocaine', 'Marocaine', '+212600000101',
    'youssef@atlas.test', 'Associé majoritaire', 'Gérant associé', 60.00, 60, 60000.00, 1
),
(
    1, 'Atlas Domiciliation', 'Madame', 'Salma', 'Bennani', 'Salma Bennani', 'BE654321', 'BE654321',
    '2031-11-30', 'Casablanca', '1992-04-10', 'Casablanca', 'Marocaine', 'Marocaine', '+212600000102',
    'salma@atlas.test', 'Associé minoritaire', '', 40.00, 40, 40000.00, 0
),
(
    2, 'Maghreb Services', 'Madame', 'Imane', 'Alaoui', 'Imane Alaoui', 'CD987654', 'CD987654',
    '2032-01-15', 'Rabat', '1988-09-15', 'Rabat', 'Marocaine', 'Marocaine', '+212600000103',
    'imane@maghreb.test', 'Associé unique', 'Gérant unique', 100.00, 100, 50000.00, 1
);

INSERT INTO contrats (
    societe_id, den_ste, type_contrat, date_contrat, duree_contrat_mois, type_contrat_domiciliation,
    type_contrat_domiciliation_autre, date_debut, date_debut_contrat, date_fin, date_fin_contrat,
    loyer_mensuel_ttc, frais_intermediaire_contrat, caution_montant, taux_tva_pourcent, loyer_mensuel_ht,
    montant_total_ht_contrat, montant_pack_demarrage_ttc, loyer_mensuel_pack_demarrage_ttc, type_renouvellement,
    taux_tva_renouvellement_pourcent, loyer_mensuel_ht_renouvellement, montant_total_ht_renouvellement,
    loyer_mensuel_renouvellement_ttc, loyer_annuel_renouvellement_ttc, statut, notes
) VALUES
(
    1, 'Atlas Domiciliation', 'Domiciliation commerciale', '2026-01-01', 12, 'Personne Morale', NULL,
    '2026-01-01', '2026-01-01', '2026-12-31', '2026-12-31', 1200.00, 300.00, 1200.00, 20.00, 1000.00,
    12000.00, 1500.00, 1250.00, 'Annuel', 20.00, 1000.00, 12000.00, 1200.00, 14400.00, 'actif',
    'Contrat annuel standard'
),
(
    2, 'Maghreb Services', 'Pack lancement', '2026-03-01', 12, 'Personne Morale', NULL,
    '2026-03-01', '2026-03-01', '2027-02-28', '2027-02-28', 900.00, 250.00, 900.00, 20.00, 750.00,
    9000.00, 1000.00, 900.00, 'Annuel', 20.00, 750.00, 9000.00, 900.00, 10800.00, 'actif',
    'Pack simplifie'
);

INSERT INTO collaborateurs (
    societe_id, den_ste, nom_complet, fonction, collaborateur_type, collaborateur_code, collaborateur_nom,
    collaborateur_ice, collaborateur_tp, collaborateur_rc, collaborateur_if, collaborateur_tel_fixe,
    collaborateur_tel_mobile, collaborateur_adresse, collaborateur_email, email, telephone, date_debut, statut, notes
) VALUES
(
    1, 'Atlas Domiciliation', 'Nadia Chraibi', 'Gestion administrative', 'EXP -- Expert Comptable', 'EXP',
    'Nadia Chraibi', 'ICE-COL-001', 'TP001', 'RC-C001', 'IF-C001', '0522000001', '+212600000010',
    'Casablanca', 'nadia@atlas.test', 'nadia@atlas.test', '+212600000010', '2026-01-05', 'actif',
    'Suivi dossiers clients'
),
(
    NULL, NULL, 'Karim Tazi', 'Support operationnel', 'CLTD -- Client Direct', 'CLTD',
    'Karim Tazi', 'ICE-COL-002', 'TP002', 'RC-C002', 'IF-C002', '0522000002', '+212600000011',
    'Casablanca', 'karim@center.test', 'karim@center.test', '+212600000011', '2026-02-01', 'actif',
    'Appui polyvalent'
);

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
