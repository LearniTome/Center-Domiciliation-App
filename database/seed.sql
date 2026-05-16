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

INSERT INTO ref_activites_ompic (code, libelle, sort_order) VALUES
('A', 'AGRICULTURE, SYLVICULTURE ET PECHE', 1),
('B', 'INDUSTRIES EXTRACTIVES', 2),
('C', 'INDUSTRIE MANUFACTURIERE', 3),
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
('56', 'Restauration', 23),
('62', 'Programmation, conseil et autres activites informatiques', 25),
('68', 'Activites immobilieres', 26),
('69', 'Activites juridiques et comptables', 27),
('70', 'Activites des sieges sociaux; conseil de gestion', 28),
('85', 'Enseignement', 33),
('86', 'Activites pour la sante humaine', 34),
('96', 'Autres services personnels', 36);

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

INSERT IGNORE INTO ref_activites_ompic (code, libelle, sort_order) VALUES
('46', 'Commerce de gros', 19),
('47', 'Commerce de detail', 20),
('68', 'Activites immobilieres', 26),
('70', 'Activites des sieges sociaux; conseil de gestion', 28);

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
