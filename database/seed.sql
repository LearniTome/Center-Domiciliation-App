USE `center_domiciliation`;

-- Données de référence pour les formes juridiques
INSERT INTO ref_formes_juridiques (forme_juridique, template_folder) VALUES
('SARL AU', 'SARL AU'),
('SARL', 'SARL'),
('Personne Physique', ''),
('SA', 'SA'),
('Succurssale Etrangère', ''),
('Succurssale Marocaine', '');

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

-- RBAC Seed Data
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

-- Super Admin: ALL permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Admin: all except roles.manage
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE id < 38;
